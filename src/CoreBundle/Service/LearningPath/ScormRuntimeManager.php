<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\LearningPath;

use Chamilo\CoreBundle\Entity\Asset;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\AssetRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Entity\CLpItemView;
use Chamilo\CourseBundle\Entity\CLpIvInteraction;
use Chamilo\CourseBundle\Entity\CLpIvObjective;
use Doctrine\ORM\EntityManagerInterface;
use JsonException;
use RuntimeException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Throwable;

use const JSON_THROW_ON_ERROR;

final readonly class ScormRuntimeManager
{
    public const VERSION_12 = '1.2';
    public const VERSION_2004 = '2004';

    private const PROGRESS_OBJECTIVE_ID = '__chamilo_progress_measure__';
    private const SCALED_SCORE_OBJECTIVE_ID = '__chamilo_scaled_score__';
    private const MAX_VALUE_LENGTH = 65535;
    private const MAX_PAYLOAD_LENGTH = 1048576;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private AssetRepository $assetRepository,
        private ScormManifestParser $manifestParser,
        private SettingsManager $settingsManager,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    public function isScormLearningPath(CLp $lp): bool
    {
        return CLp::SCORM_TYPE === $lp->getLpType();
    }

    public function isScormItem(CLpItem $item): bool
    {
        return 'sco' === strtolower(trim($item->getItemType()));
    }

    public function resolveVersion(CLp $lp): string
    {
        $jsLib = strtolower(trim($lp->getJsLib()));
        if (str_contains($jsLib, '2004')) {
            return self::VERSION_2004;
        }
        if (str_contains($jsLib, '1_2') || str_contains($jsLib, '1.2')) {
            return self::VERSION_12;
        }

        try {
            $manifestPath = $this->resolveAssetFilePath($lp, 'imsmanifest.xml');
            $filesystem = $this->assetRepository->getFileSystem();
            if ($filesystem->fileExists($manifestPath)) {
                $manifest = $this->manifestParser->parse($filesystem->read($manifestPath));
                if (self::VERSION_2004 === ($manifest['version'] ?? null)) {
                    return self::VERSION_2004;
                }
            }
        } catch (Throwable) {
            // Existing packages can still use the SCORM 1.2-compatible fallback.
        }

        return self::VERSION_12;
    }

    /**
     * @param array<string, scalar> $contextParams
     */
    public function buildLaunchUrl(
        CLp $lp,
        CLpItem $item,
        Course $course,
        ?Session $session,
        ?CGroup $group,
        array $contextParams,
    ): string {
        $path = trim((string) $item->getPath());
        if ('' === $path) {
            return '';
        }

        if (1 === preg_match('#^https?://#i', $path)) {
            return $this->appendItemParameters($path, (string) $item->getParameters());
        }

        $url = $this->urlGenerator->generate('chamilo_core_learning_path_scorm_content', [
            'cid' => (int) $course->getId(),
            'sid' => (int) ($session?->getId() ?? 0),
            'gid' => (int) ($group?->getIid() ?? 0),
            'lpId' => (int) $lp->getIid(),
            'itemId' => (int) $item->getIid(),
            'path' => $this->normalizeRelativePath($path),
        ]);

        $query = $contextParams;
        unset($query['cid'], $query['sid'], $query['gid']);
        $url = $this->appendQuery($url, $query);

        return $this->appendItemParameters($url, (string) $item->getParameters());
    }

    /**
     * @return array{
     *     enabled: bool,
     *     version: string,
     *     itemViewId: int,
     *     forceCommit: bool,
     *     debug: bool,
     *     values: array<string, string>
     * }
     */
    public function buildRuntimeConfiguration(
        CLp $lp,
        CLpItem $item,
        ?CLpItemView $itemView,
        User $user,
    ): array {
        if (!$this->isScormLearningPath($lp)
            || !$this->isScormItem($item)
            || 1 === preg_match('#^https?://#i', trim((string) $item->getPath()))
            || !$itemView instanceof CLpItemView
            || null === $itemView->getIid()
        ) {
            return [
                'enabled' => false,
                'version' => '',
                'itemViewId' => 0,
                'forceCommit' => false,
                'debug' => false,
                'values' => [],
            ];
        }

        $version = $this->resolveVersion($lp);
        $values = self::VERSION_2004 === $version
            ? $this->buildScorm2004Values($item, $itemView, $user)
            : $this->buildScorm12Values($item, $itemView, $user);

        return [
            'enabled' => true,
            'version' => $version,
            'itemViewId' => (int) $itemView->getIid(),
            'forceCommit' => $lp->getForceCommit(),
            'debug' => $lp->getDebug(),
            'values' => $values,
        ];
    }

    public function resolveAssetFilePath(CLp $lp, string $relativePath): string
    {
        $asset = $lp->getAsset();
        if (!$asset instanceof Asset || Asset::SCORM !== $asset->getCategory()) {
            throw new RuntimeException('The SCORM package asset is missing.');
        }

        $folder = rtrim((string) $this->assetRepository->getFolder($asset), '/');
        if ('' === $folder) {
            throw new RuntimeException('The SCORM package folder could not be resolved.');
        }

        $lpPath = $this->normalizeRelativePath((string) $lp->getPath(), true);
        $path = $this->normalizeRelativePath($relativePath);

        return $folder.('' !== $lpPath ? '/'.$lpPath : '').'/'.$path;
    }

    /**
     * @param array<string, mixed> $values
     * @param array<int, string>   $changedKeys
     */
    public function applyValues(
        CLp $lp,
        CLpItem $item,
        CLpItemView $itemView,
        Course $course,
        string $version,
        array $values,
        array $changedKeys,
        bool $terminated,
        string $reason,
    ): void {
        $expectedVersion = $this->resolveVersion($lp);
        if ($expectedVersion !== $version) {
            throw new RuntimeException('The SCORM runtime version does not match the imported package.');
        }

        $normalized = $this->normalizeValues($values);
        $normalizedChangedKeys = $this->normalizeChangedKeys($changedKeys);
        $this->applyCommonValues(
            $lp,
            $item,
            $itemView,
            $version,
            $normalized,
            $normalizedChangedKeys,
            $terminated,
            $reason,
        );
        $this->saveInteractions($itemView, $course, $normalized);
        $this->saveObjectives($itemView, $course, $normalized);

        $this->entityManager->flush();
    }

    /** @return array<string, string> */
    private function buildScorm12Values(CLpItem $item, CLpItemView $itemView, User $user): array
    {
        $status = $this->normalizeStatus((string) $itemView->getStatus());
        $score = (float) $itemView->getScore();
        $maxScore = $this->getMaxScore($item, $itemView);
        $entry = $this->getEntryValue((string) $itemView->getCoreExit());
        $student = $this->getStudentData($user);

        $values = [
            'cmi.core._children' => implode(',', [
                'entry',
                'exit',
                'lesson_status',
                'student_id',
                'student_name',
                'lesson_location',
                'total_time',
                'credit',
                'lesson_mode',
                'score',
                'session_time',
            ]),
            'cmi.core.student_id' => $student['id'],
            'cmi.core.student_name' => $student['name'],
            'cmi.core.score._children' => 'raw,min,max',
            'cmi.student_data._children' => 'mastery_score,max_time_allowed,time_limit_action',
            'cmi.student_data.mastery_score' => null !== $item->getMasteryScore()
                ? $this->formatNumber((float) $item->getMasteryScore())
                : '',
            'cmi.student_data.max_time_allowed' => (string) ($item->getMaxTimeAllowed() ?? ''),
            'cmi.student_data.time_limit_action' => '',
            'cmi.student_preference._children' => 'audio,language,speed,text',
            'cmi.interactions._children' => 'id,objectives,time,type,correct_responses,weighting,student_response,result,latency',
            'cmi.objectives._children' => 'id,score,status',
            'cmi.core.lesson_location' => (string) ($itemView->getLessonLocation() ?? ''),
            'cmi.core.credit' => 'credit',
            'cmi.core.lesson_status' => $status,
            'cmi.core.entry' => $entry,
            'cmi.core.score.raw' => 0.0 === $score && 'not attempted' === $status ? '' : $this->formatNumber($score),
            'cmi.core.score.max' => $this->formatNumber($maxScore),
            'cmi.core.score.min' => $this->formatNumber((float) $item->getMinScore()),
            'cmi.core.total_time' => $this->formatScorm12Time((int) $itemView->getTotalTime()),
            'cmi.core.lesson_mode' => 'normal',
            'cmi.core.exit' => '',
            'cmi.core.session_time' => '',
            'cmi.suspend_data' => (string) ($itemView->getSuspendData() ?? ''),
            'cmi.launch_data' => (string) $item->getLaunchData(),
            'cmi.comments' => '',
            'cmi.comments_from_lms' => '',
        ];

        return $this->appendInteractionAndObjectiveValues($values, $itemView, false);
    }

    /** @return array<string, string> */
    private function buildScorm2004Values(CLpItem $item, CLpItemView $itemView, User $user): array
    {
        $status = $this->normalizeStatus((string) $itemView->getStatus());
        $completionStatus = $this->getCompletionStatus($status);
        $successStatus = $this->getSuccessStatus($status);
        $score = (float) $itemView->getScore();
        $maxScore = $this->getMaxScore($item, $itemView);
        $scaledScore = $maxScore > 0.0 ? max(-1.0, min(1.0, $score / $maxScore)) : 0.0;
        $progressMeasure = $this->loadReservedObjectiveValue($itemView, self::PROGRESS_OBJECTIVE_ID);
        if (null === $progressMeasure) {
            $progressMeasure = 'completed' === $completionStatus ? 1.0 : 0.0;
        }
        $student = $this->getStudentData($user);
        $masteryScore = $item->getMasteryScore();

        $values = [
            'cmi._version' => '1.0',
            'cmi._children' => implode(',', [
                'completion_status',
                'credit',
                'entry',
                'exit',
                'interactions',
                'launch_data',
                'learner_id',
                'learner_name',
                'location',
                'mode',
                'objectives',
                'progress_measure',
                'score',
                'session_time',
                'success_status',
                'suspend_data',
                'total_time',
            ]),
            'cmi.learner_id' => $student['id'],
            'cmi.learner_name' => $student['name'],
            'cmi.location' => (string) ($itemView->getLessonLocation() ?? ''),
            'cmi.credit' => 'credit',
            'cmi.completion_status' => $completionStatus,
            'cmi.success_status' => $successStatus,
            'cmi.entry' => $this->getEntryValue((string) $itemView->getCoreExit()),
            'cmi.score._children' => 'scaled,raw,min,max',
            'cmi.score.raw' => 0.0 === $score && 'not attempted' === $status ? '' : $this->formatNumber($score),
            'cmi.score.max' => $this->formatNumber($maxScore),
            'cmi.score.min' => $this->formatNumber((float) $item->getMinScore()),
            'cmi.score.scaled' => $this->formatNumber($scaledScore),
            'cmi.progress_measure' => $this->formatNumber($progressMeasure),
            'cmi.total_time' => $this->formatScorm2004Duration((int) $itemView->getTotalTime()),
            'cmi.mode' => 'normal',
            'cmi.exit' => '',
            'cmi.session_time' => '',
            'cmi.suspend_data' => (string) ($itemView->getSuspendData() ?? ''),
            'cmi.launch_data' => (string) $item->getLaunchData(),
            'cmi.comments_from_learner._count' => '0',
            'cmi.comments_from_lms._count' => '0',
            'cmi.interactions._children' => 'id,type,objectives,timestamp,correct_responses,weighting,learner_response,result,latency,description',
            'cmi.objectives._children' => 'id,score,success_status,completion_status,progress_measure,description',
            'cmi.completion_threshold' => '1',
            'cmi.scaled_passing_score' => null !== $masteryScore
                ? $this->formatNumber(max(-1.0, min(1.0, (float) $masteryScore / 100.0)))
                : '',
        ];

        return $this->appendInteractionAndObjectiveValues($values, $itemView, true);
    }

    /**
     * @param array<string, string> $values
     *
     * @return array<string, string>
     */
    private function appendInteractionAndObjectiveValues(
        array $values,
        CLpItemView $itemView,
        bool $scorm2004,
    ): array {
        $itemViewId = (int) $itemView->getIid();
        /** @var array<int, CLpIvInteraction> $interactions */
        $interactions = $this->entityManager->getRepository(CLpIvInteraction::class)->findBy(
            ['lpIvId' => $itemViewId],
            ['orderId' => 'ASC'],
        );
        $values['cmi.interactions._count'] = (string) \count($interactions);

        foreach ($interactions as $interaction) {
            $index = (int) $interaction->getOrderId();
            $prefix = 'cmi.interactions.'.$index.'.';
            $values[$prefix.'id'] = (string) $interaction->getInteractionId();
            $values[$prefix.'type'] = (string) $interaction->getInteractionType();
            $values[$prefix.'weighting'] = $this->formatNumber((float) $interaction->getWeighting());
            $values[$prefix.($scorm2004 ? 'timestamp' : 'time')] = (string) $interaction->getCompletionTime();
            $responseKey = $scorm2004 ? 'learner_response' : 'student_response';
            $values[$prefix.$responseKey] = (string) $interaction->getStudentResponse();
            $values[$prefix.'result'] = (string) $interaction->getResult();
            $values[$prefix.'latency'] = (string) $interaction->getLatency();

            $correctResponses = $this->decodeStringList((string) $interaction->getCorrectResponses());
            $values[$prefix.'correct_responses._count'] = (string) \count($correctResponses);
            foreach ($correctResponses as $responseIndex => $response) {
                $values[$prefix.'correct_responses.'.$responseIndex.'.pattern'] = $response;
            }
        }

        /** @var array<int, CLpIvObjective> $objectives */
        $objectives = $this->entityManager->getRepository(CLpIvObjective::class)->findBy(
            ['lpIvId' => $itemViewId],
            ['orderId' => 'ASC'],
        );
        $publicObjectives = array_values(array_filter(
            $objectives,
            static fn (CLpIvObjective $objective): bool => !str_starts_with(
                (string) $objective->getObjectiveId(),
                '__chamilo_',
            ),
        ));
        $values['cmi.objectives._count'] = (string) \count($publicObjectives);

        foreach ($publicObjectives as $index => $objective) {
            $prefix = 'cmi.objectives.'.$index.'.';
            $values[$prefix.'id'] = (string) $objective->getObjectiveId();
            $values[$prefix.'score.raw'] = $this->formatNumber((float) $objective->getScoreRaw());
            $values[$prefix.'score.max'] = $this->formatNumber((float) $objective->getScoreMax());
            $values[$prefix.'score.min'] = $this->formatNumber((float) $objective->getScoreMin());
            if ($scorm2004) {
                $values[$prefix.'completion_status'] = $this->getCompletionStatus((string) $objective->getStatus());
                $values[$prefix.'success_status'] = $this->getSuccessStatus((string) $objective->getStatus());
            } else {
                $values[$prefix.'status'] = (string) $objective->getStatus();
            }
        }

        return $values;
    }

    /**
     * @param array<string, string> $values
     * @param array<int, string>    $changedKeys
     */
    private function applyCommonValues(
        CLp $lp,
        CLpItem $item,
        CLpItemView $itemView,
        string $version,
        array $values,
        array $changedKeys,
        bool $terminated,
        string $reason,
    ): void {
        $scorm2004 = self::VERSION_2004 === $version;
        $locationKey = $scorm2004 ? 'cmi.location' : 'cmi.core.lesson_location';
        $statusKey = $scorm2004 ? 'cmi.completion_status' : 'cmi.core.lesson_status';
        $sessionTimeKey = $scorm2004 ? 'cmi.session_time' : 'cmi.core.session_time';
        $totalTimeKey = $scorm2004 ? 'cmi.total_time' : 'cmi.core.total_time';
        $exitKey = $scorm2004 ? 'cmi.exit' : 'cmi.core.exit';

        if (isset($values[$locationKey])) {
            $itemView->setLessonLocation($values[$locationKey]);
        }
        if (isset($values['cmi.suspend_data'])) {
            $itemView->setSuspendData($values['cmi.suspend_data']);
        }
        if (isset($values[$exitKey])) {
            $itemView->setCoreExit('' !== $values[$exitKey] ? $values[$exitKey] : 'none');
        } elseif ($terminated) {
            $itemView->setCoreExit('none');
        }

        $rawScore = $this->nullableFloat($values[$scorm2004 ? 'cmi.score.raw' : 'cmi.core.score.raw'] ?? null);
        $maxScore = $this->nullableFloat($values[$scorm2004 ? 'cmi.score.max' : 'cmi.core.score.max'] ?? null);
        $scaledScore = $this->nullableFloat($values['cmi.score.scaled'] ?? null);
        if (null === $rawScore && null !== $scaledScore) {
            $effectiveMax = null !== $maxScore ? $maxScore : $this->getMaxScore($item, $itemView);
            $rawScore = $scaledScore * $effectiveMax;
        }
        if (null !== $rawScore) {
            $itemView->setScore($rawScore);
        }
        if (null !== $maxScore && $maxScore > 0.0) {
            $itemView->setMaxScore($this->formatNumber($maxScore));
        }

        $status = isset($values[$statusKey]) ? $this->normalizeStatus($values[$statusKey]) : '';
        $statusWasSet = \in_array($statusKey, $changedKeys, true);
        if ($scorm2004) {
            $success = isset($values['cmi.success_status'])
                ? strtolower(trim($values['cmi.success_status']))
                : 'unknown';
            $successWasSet = \in_array('cmi.success_status', $changedKeys, true);
            if ($successWasSet && \in_array($success, ['passed', 'failed'], true)) {
                $status = $success;
            } elseif ('completed' === $status) {
                $status = 'completed';
            } elseif ('incomplete' === $status) {
                $status = 'incomplete';
            }

            if ($this->shouldFinalizeWithoutStatus($terminated, $reason)
                && !$successWasSet
                && !\in_array($status, ['passed', 'failed'], true)
            ) {
                $status = $this->resolveMasteryStatus($item, $rawScore, $scaledScore) ?? $status;
            }
        } elseif ($this->shouldFinalizeWithoutStatus($terminated, $reason)
            && !$statusWasSet
            && !\in_array($status, ['completed', 'passed', 'failed', 'browsed'], true)
        ) {
            $status = $this->resolveMasteryStatus($item, $rawScore, null) ?? 'completed';
        }

        if ('' !== $status && 'unknown' !== $status) {
            $itemView->setStatus($status);
        }

        $sessionSeconds = $this->parseSessionTime($values[$sessionTimeKey] ?? '', $version);
        if ($sessionSeconds > 0) {
            $baselineSeconds = $this->parseSessionTime($values[$totalTimeKey] ?? '', $version);
            $currentSeconds = max(0, (int) $itemView->getTotalTime());
            $targetSeconds = $lp->getAccumulateScormTime() > 0
                ? $baselineSeconds + $sessionSeconds
                : max($baselineSeconds, $sessionSeconds);
            $itemView->setTotalTime(max($currentSeconds, $targetSeconds));
        }

        if ($scorm2004) {
            $progress = $this->nullableFloat($values['cmi.progress_measure'] ?? null);
            if (null !== $progress) {
                $this->saveReservedObjectiveValue(
                    $itemView,
                    self::PROGRESS_OBJECTIVE_ID,
                    max(0.0, min(1.0, $progress)),
                    $values['cmi.completion_status'] ?? 'unknown',
                );
            }
            if (null !== $scaledScore) {
                $this->saveReservedObjectiveValue(
                    $itemView,
                    self::SCALED_SCORE_OBJECTIVE_ID,
                    max(-1.0, min(1.0, $scaledScore)),
                    $values['cmi.success_status'] ?? 'unknown',
                );
            }
        }
    }

    /**
     * @param array<string, string> $values
     */
    private function saveInteractions(CLpItemView $itemView, Course $course, array $values): void
    {
        $grouped = $this->groupIndexedValues($values, 'cmi.interactions.');
        $itemViewId = (int) $itemView->getIid();
        foreach ($grouped as $index => $data) {
            if (!isset($data['id']) || '' === trim($data['id'])) {
                continue;
            }

            /** @var CLpIvInteraction|null $interaction */
            $interaction = $this->entityManager->getRepository(CLpIvInteraction::class)->findOneBy([
                'lpIvId' => $itemViewId,
                'orderId' => $index,
            ]);
            if (!$interaction instanceof CLpIvInteraction) {
                $interaction = new CLpIvInteraction();
                $interaction
                    ->setLpIvId($itemViewId)
                    ->setOrderId($index)
                    ->setCId((int) $course->getId())
                ;
                $this->entityManager->persist($interaction);
            }

            $correctResponses = [];
            foreach ($data as $key => $value) {
                if (preg_match('/^correct_responses\.\d+\.pattern$/', $key)) {
                    $correctResponses[] = $value;
                }
            }

            $completionTime = $data['timestamp'] ?? $data['time'] ?? date('H:i:s');
            $interaction
                ->setInteractionId(mb_substr($data['id'], 0, 255))
                ->setInteractionType(mb_substr($data['type'] ?? '', 0, 255))
                ->setWeighting((float) ($this->nullableFloat($data['weighting'] ?? null) ?? 0.0))
                ->setCompletionTime(mb_substr($completionTime, 0, 16))
                ->setCorrectResponses($this->encodeStringList($correctResponses))
                ->setStudentResponse($data['learner_response'] ?? $data['student_response'] ?? '')
                ->setResult(mb_substr($data['result'] ?? '', 0, 255))
                ->setLatency(mb_substr($data['latency'] ?? '', 0, 16))
            ;
        }
    }

    /**
     * @param array<string, string> $values
     */
    private function saveObjectives(CLpItemView $itemView, Course $course, array $values): void
    {
        $grouped = $this->groupIndexedValues($values, 'cmi.objectives.');
        $itemViewId = (int) $itemView->getIid();
        foreach ($grouped as $index => $data) {
            if (!isset($data['id']) || '' === trim($data['id'])) {
                continue;
            }

            /** @var CLpIvObjective|null $objective */
            $objective = $this->entityManager->getRepository(CLpIvObjective::class)->findOneBy([
                'lpIvId' => $itemViewId,
                'orderId' => $index,
            ]);
            if (!$objective instanceof CLpIvObjective) {
                $objective = new CLpIvObjective();
                $objective
                    ->setLpIvId($itemViewId)
                    ->setOrderId($index)
                    ->setCId((int) $course->getId())
                ;
                $this->entityManager->persist($objective);
            }

            $status = $data['success_status'] ?? $data['completion_status'] ?? $data['status'] ?? 'unknown';
            $objective
                ->setObjectiveId(mb_substr($data['id'], 0, 255))
                ->setScoreRaw((float) ($this->nullableFloat($data['score.raw'] ?? null) ?? 0.0))
                ->setScoreMax((float) ($this->nullableFloat($data['score.max'] ?? null) ?? 0.0))
                ->setScoreMin((float) ($this->nullableFloat($data['score.min'] ?? null) ?? 0.0))
                ->setStatus(mb_substr($status, 0, 32))
            ;
        }
    }

    private function saveReservedObjectiveValue(
        CLpItemView $itemView,
        string $objectiveId,
        float $value,
        string $status,
    ): void {
        $itemViewId = (int) $itemView->getIid();
        /** @var CLpIvObjective|null $objective */
        $objective = $this->entityManager->getRepository(CLpIvObjective::class)->findOneBy([
            'lpIvId' => $itemViewId,
            'objectiveId' => $objectiveId,
        ]);
        if (!$objective instanceof CLpIvObjective) {
            $objective = new CLpIvObjective();
            $objective
                ->setLpIvId($itemViewId)
                ->setOrderId(-1)
                ->setCId((int) $itemView->getView()->getCourse()->getId())
                ->setObjectiveId($objectiveId)
            ;
            $this->entityManager->persist($objective);
        }

        $objective
            ->setScoreRaw($value)
            ->setScoreMax(1.0)
            ->setScoreMin(0.0)
            ->setStatus(mb_substr($status, 0, 32))
        ;
    }

    private function loadReservedObjectiveValue(CLpItemView $itemView, string $objectiveId): ?float
    {
        /** @var CLpIvObjective|null $objective */
        $objective = $this->entityManager->getRepository(CLpIvObjective::class)->findOneBy([
            'lpIvId' => (int) $itemView->getIid(),
            'objectiveId' => $objectiveId,
        ]);

        return $objective instanceof CLpIvObjective ? (float) $objective->getScoreRaw() : null;
    }

    /**
     * @param array<string, string> $values
     *
     * @return array<int, array<string, string>>
     */
    private function groupIndexedValues(array $values, string $prefix): array
    {
        $grouped = [];
        foreach ($values as $key => $value) {
            if (!str_starts_with($key, $prefix)) {
                continue;
            }

            $suffix = substr($key, strlen($prefix));
            if (!preg_match('/^(\d+)\.(.+)$/', $suffix, $matches)) {
                continue;
            }

            $grouped[(int) $matches[1]][$matches[2]] = $value;
        }

        ksort($grouped);

        return $grouped;
    }

    /**
     * @param array<string, mixed> $values
     *
     * @return array<string, string>
     */
    private function normalizeValues(array $values): array
    {
        $normalized = [];
        $totalLength = 0;
        foreach ($values as $key => $value) {
            if (!\is_string($key) || !preg_match('/^cmi(?:\.|$)/', $key)) {
                continue;
            }
            if (!\is_scalar($value) && null !== $value) {
                continue;
            }

            $stringValue = null === $value ? '' : (string) $value;
            if (strlen($stringValue) > self::MAX_VALUE_LENGTH) {
                throw new RuntimeException('A SCORM runtime value is too large.');
            }
            $totalLength += strlen($key) + strlen($stringValue);
            if ($totalLength > self::MAX_PAYLOAD_LENGTH) {
                throw new RuntimeException('The SCORM runtime payload is too large.');
            }

            $normalized[$key] = $stringValue;
        }

        return $normalized;
    }

    /** @return array{id: string, name: string} */
    private function getStudentData(User $user): array
    {
        $useUsername = $this->isTruthy(
            $this->settingsManager->getSetting('lp.scorm_api_username_as_student_id', true),
        );
        $id = $useUsername ? $user->getUsername() : (string) $user->getId();
        $name = trim((string) $user->getLastname()).', '.trim((string) $user->getFirstname());

        return [
            'id' => '' !== trim($id) ? trim($id) : (string) $user->getId(),
            'name' => trim($name, ' ,'),
        ];
    }

    private function getMaxScore(CLpItem $item, CLpItemView $itemView): float
    {
        $itemViewMax = $this->nullableFloat($itemView->getMaxScore());
        if (null !== $itemViewMax && $itemViewMax > 0.0) {
            return $itemViewMax;
        }

        $itemMax = $item->getMaxScore();

        return null !== $itemMax && $itemMax > 0.0 ? $itemMax : 100.0;
    }

    private function getEntryValue(string $coreExit): string
    {
        return match (strtolower(trim($coreExit))) {
            'suspend' => 'resume',
            'none', '' => 'ab-initio',
            default => '',
        };
    }

    private function normalizeStatus(string $status): string
    {
        $status = strtolower(trim($status));

        return '' !== $status ? $status : 'not attempted';
    }

    private function getCompletionStatus(string $status): string
    {
        return match ($this->normalizeStatus($status)) {
            'completed', 'passed', 'failed', 'succeeded', 'browsed' => 'completed',
            'incomplete' => 'incomplete',
            'not attempted' => 'not attempted',
            default => 'unknown',
        };
    }

    private function getSuccessStatus(string $status): string
    {
        return match ($this->normalizeStatus($status)) {
            'passed', 'succeeded' => 'passed',
            'failed' => 'failed',
            default => 'unknown',
        };
    }

    /** @param array<int, string> $changedKeys */
    private function normalizeChangedKeys(array $changedKeys): array
    {
        $normalized = [];
        foreach ($changedKeys as $key) {
            if (!\is_string($key) || !preg_match('/^cmi(?:\.|$)/', $key)) {
                continue;
            }
            $normalized[$key] = true;
        }

        return array_keys($normalized);
    }

    private function shouldFinalizeWithoutStatus(bool $terminated, string $reason): bool
    {
        if ($terminated) {
            return true;
        }

        return \in_array(strtolower(trim($reason)), ['navigation', 'pagehide', 'unmount'], true);
    }

    private function resolveMasteryStatus(CLpItem $item, ?float $rawScore, ?float $scaledScore): ?string
    {
        $masteryScore = $this->nullableFloat($item->getMasteryScore());
        if (null === $masteryScore) {
            return null;
        }

        if (null !== $scaledScore && $masteryScore >= -1.0 && $masteryScore <= 1.0) {
            return $scaledScore >= $masteryScore ? 'passed' : 'failed';
        }
        if (null === $rawScore) {
            return null;
        }

        return $rawScore >= $masteryScore ? 'passed' : 'failed';
    }

    private function parseSessionTime(string $value, string $version): int
    {
        $value = trim($value);
        if ('' === $value) {
            return 0;
        }

        if (self::VERSION_2004 === $version) {
            if (!preg_match('/^PT(?:(\d+)H)?(?:(\d+)M)?(?:(\d+(?:\.\d+)?)S)?$/i', $value, $matches)) {
                return 0;
            }

            return (int) round(
                ((int) ($matches[1] ?? 0) * 3600)
                + ((int) ($matches[2] ?? 0) * 60)
                + (float) ($matches[3] ?? 0),
            );
        }

        if (!preg_match('/^(\d+):(\d{1,2}):(\d{1,2})(?:\.(\d+))?$/', $value, $matches)) {
            return 0;
        }

        return ((int) $matches[1] * 3600) + ((int) $matches[2] * 60) + (int) $matches[3];
    }

    private function formatScorm12Time(int $seconds): string
    {
        $seconds = max(0, $seconds);
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $remaining = $seconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $remaining);
    }

    private function formatScorm2004Duration(int $seconds): string
    {
        $seconds = max(0, $seconds);
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $remaining = $seconds % 60;

        return 'PT'.$hours.'H'.$minutes.'M'.$remaining.'S';
    }

    private function formatNumber(float $value): string
    {
        return rtrim(rtrim(number_format($value, 6, '.', ''), '0'), '.');
    }

    private function nullableFloat(mixed $value): ?float
    {
        if (null === $value || '' === trim((string) $value) || !is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    private function normalizeRelativePath(string $path, bool $allowEmpty = false): string
    {
        $path = str_replace('\\', '/', trim(rawurldecode($path)));
        $path = ltrim($path, '/');
        if ('' === $path) {
            if ($allowEmpty) {
                return '';
            }

            throw new RuntimeException('The SCORM content path is empty.');
        }
        if (str_contains($path, "\0") || preg_match('/[\x00-\x1F\x7F]/', $path)) {
            throw new RuntimeException('The SCORM content path is invalid.');
        }

        $segments = [];
        foreach (explode('/', $path) as $segment) {
            if ('' === $segment || '.' === $segment) {
                continue;
            }
            if ('..' === $segment) {
                throw new RuntimeException('The SCORM content path is unsafe.');
            }
            $segments[] = $segment;
        }

        if ([] === $segments && !$allowEmpty) {
            throw new RuntimeException('The SCORM content path is empty.');
        }

        return implode('/', $segments);
    }

    /** @param array<string, scalar> $query */
    private function appendQuery(string $url, array $query): string
    {
        $query = array_filter(
            $query,
            static fn (mixed $value): bool => null !== $value && '' !== (string) $value,
        );
        if ([] === $query) {
            return $url;
        }

        return $url.(str_contains($url, '?') ? '&' : '?').http_build_query($query);
    }

    private function appendItemParameters(string $url, string $parameters): string
    {
        $parameters = ltrim(trim($parameters), '?&');
        if ('' === $parameters) {
            return $url;
        }

        parse_str($parameters, $query);
        if (!\is_array($query)) {
            return $url;
        }

        /** @var array<string, scalar> $safeQuery */
        $safeQuery = [];
        foreach ($query as $key => $value) {
            if (\is_scalar($value)) {
                $safeQuery[(string) $key] = $value;
            }
        }

        return $this->appendQuery($url, $safeQuery);
    }

    /** @param array<int, string> $values */
    private function encodeStringList(array $values): string
    {
        try {
            return json_encode(array_values($values), JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return '[]';
        }
    }

    /** @return array<int, string> */
    private function decodeStringList(string $value): array
    {
        try {
            $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return '' !== $value ? [$value] : [];
        }

        if (!\is_array($decoded)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn (mixed $item): string => \is_scalar($item) ? (string) $item : '',
            $decoded,
        ), static fn (string $item): bool => '' !== $item));
    }

    private function isTruthy(mixed $value): bool
    {
        return \in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }
}
