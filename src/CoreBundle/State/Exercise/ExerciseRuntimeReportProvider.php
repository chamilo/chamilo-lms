<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Exercise;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Exercise\ExerciseRuntimeReport;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\IsAllowedToEditHelper;
use Chamilo\CoreBundle\Service\Gradebook\GradebookLinkManager;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CoreBundle\State\Gradebook\GradebookLinkResourceResolver;
use Chamilo\CourseBundle\Entity\CGroupRelUser;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Repository\CQuizRepository;
use DateTimeInterface;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Read-only provider for the migrated exercise learner attempts report.
 *
 * @implements ProviderInterface<ExerciseRuntimeReport>
 */
final readonly class ExerciseRuntimeReportProvider implements ProviderInterface
{
    private const VISIBILITY_PUBLISHED = 2;
    private const STATUS_INCOMPLETE = 'incomplete';
    private const STATUS_PENDING_CORRECTION = 'pending_correction';
    private const STATUS_COMPLETED = 'completed';

    public function __construct(
        private CidReqHelper $cidReqHelper,
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private CQuizRepository $quizRepository,
        private Security $security,
        private GradebookLinkManager $gradebookLinkManager,
        private SettingsManager $settingsManager,
        private IsAllowedToEditHelper $isAllowedToEditHelper,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ExerciseRuntimeReport
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $course = $this->cidReqHelper->requireDoctrineCourseEntity();
        $session = $this->cidReqHelper->getDoctrineSessionEntity();
        $exerciseId = isset($uriVariables['exerciseId']) ? (int) $uriVariables['exerciseId'] : 0;
        if ($exerciseId <= 0) {
            throw new BadRequestHttpException('A valid exercise id is required.');
        }

        if (!$this->isAllowedToEditHelper->check(coach: true)) {
            throw new AccessDeniedHttpException('You are not allowed to view this exercise report.');
        }

        $quiz = $this->getExerciseFromCurrentContext($exerciseId, $course, $session);
        $lockedByGradebook = $this->isGradebookLocked((int) $quiz->getIid(), $course, $session);
        $showUsername = $this->shouldShowUsername();
        $showIp = $this->shouldShowIp();
        $attempts = $this->getAttempts($request, $quiz, $course, $session, $lockedByGradebook, $showUsername, $showIp);
        $showOfficialCode = $this->shouldShowOfficialCode();
        $groupOptions = $this->getGroupOptions($course);

        $response = new ExerciseRuntimeReport();
        $response->exerciseId = $exerciseId;
        $response->title = $quiz->getTitle();
        $response->description = (string) $quiz->getDescription();
        $response->attempts = $attempts;
        $response->filters = [
            'firstName' => trim((string) $request->query->get('firstName', '')),
            'lastName' => trim((string) $request->query->get('lastName', '')),
            'status' => trim((string) $request->query->get('status', '')),
            'groupId' => $this->getGroupFilterValue($request),
        ];
        $response->groupOptions = $groupOptions;
        $response->actionUrls = $this->getActionUrls($operation, $quiz, $request);
        $response->totalItems = \count($attempts);
        $response->canManage = true;
        $response->lockedByGradebook = $lockedByGradebook;
        $response->canBulkDelete = !$lockedByGradebook && $this->canDeleteResults();
        $response->canCleanResults = !$lockedByGradebook && $this->canCleanResults();
        $response->canBulkRecalculate = !$lockedByGradebook;
        $response->showOfficialCode = $showOfficialCode;
        $response->showUsername = $showUsername;
        $response->showIp = $showIp;
        $response->extraFields = $this->getFilterableUserExtraFields();

        return $response;
    }

    private function getExerciseFromCurrentContext(int $exerciseId, Course $course, ?Session $session): CQuiz
    {
        $quiz = $this->quizRepository->find($exerciseId);
        if (!$quiz instanceof CQuiz) {
            throw new NotFoundHttpException('The requested exercise was not found.');
        }

        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('quiz.iid')
            ->addSelect('links.visibility AS linkVisibility')
            ->from(CQuiz::class, 'quiz')
            ->innerJoin('quiz.resourceNode', 'node')
            ->innerJoin('node.resourceLinks', 'links')
            ->andWhere('quiz.iid = :exerciseId')
            ->andWhere('IDENTITY(links.course) = :courseId')
            ->andWhere('links.deletedAt IS NULL')
            ->andWhere('links.endVisibilityAt IS NULL')
            ->setParameter('exerciseId', $exerciseId, Types::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->setMaxResults(1)
        ;

        if (null !== $session) {
            $queryBuilder
                ->andWhere('(IDENTITY(links.session) = :sessionId OR links.session IS NULL)')
                ->setParameter('sessionId', (int) $session->getId(), Types::INTEGER)
            ;
        } else {
            $queryBuilder->andWhere('links.session IS NULL');
        }

        $row = $queryBuilder->getQuery()->getOneOrNullResult();
        if (null === $row) {
            throw new AccessDeniedHttpException('The requested exercise does not belong to the current course context.');
        }

        $visibility = \is_array($row) ? (int) ($row['linkVisibility'] ?? 0) : 0;
        if (0 !== $visibility && self::VISIBILITY_PUBLISHED !== $visibility && !$this->isAllowedToEditHelper->check(coach: true)) {
            throw new AccessDeniedHttpException('The requested exercise is not visible.');
        }

        return $quiz;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getAttempts(Request $request, CQuiz $quiz, Course $course, ?Session $session, bool $lockedByGradebook, bool $showUsername, bool $showIp): array
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('attempt', 'user')
            ->from(TrackEExercise::class, 'attempt')
            ->innerJoin('attempt.user', 'user')
            ->andWhere('IDENTITY(attempt.quiz) = :exerciseId')
            ->andWhere('IDENTITY(attempt.course) = :courseId')
            ->setParameter('exerciseId', (int) $quiz->getIid(), Types::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->orderBy('attempt.exeDate', 'DESC')
            ->addOrderBy('attempt.exeId', 'DESC')
        ;

        if (null !== $session) {
            $queryBuilder
                ->andWhere('IDENTITY(attempt.session) = :sessionId')
                ->setParameter('sessionId', (int) $session->getId(), Types::INTEGER)
            ;
        } else {
            $queryBuilder->andWhere('attempt.session IS NULL');
        }

        $this->applyUserFilters($queryBuilder, $request);
        $this->applyGroupFilter($queryBuilder, $course, $request);

        $status = trim((string) $request->query->get('status', ''));
        if (self::STATUS_PENDING_CORRECTION === $status) {
            $queryBuilder->andWhere("attempt.questionsToCheck <> ''");
        } elseif (self::STATUS_INCOMPLETE === $status) {
            $queryBuilder
                ->andWhere('attempt.status = :status')
                ->setParameter('status', self::STATUS_INCOMPLETE, Types::STRING)
            ;
        } elseif (self::STATUS_COMPLETED === $status) {
            $queryBuilder
                ->andWhere('attempt.status = :status')
                ->andWhere("attempt.questionsToCheck = ''")
                ->setParameter('status', self::STATUS_COMPLETED, Types::STRING)
            ;
        }

        $attemptRows = [];
        foreach ($queryBuilder->getQuery()->getResult() as $attempt) {
            if ($attempt instanceof TrackEExercise) {
                $attemptRows[] = $attempt;
            }
        }

        $groupNamesByUser = $this->getGroupNamesByUserIds($this->collectUserIdsFromAttempts($attemptRows), $course);

        $attempts = [];
        foreach ($attemptRows as $attempt) {
            $userId = (int) $attempt->getUser()->getId();
            $attempts[] = $this->normalizeAttempt(
                $attempt,
                $quiz,
                $request,
                $lockedByGradebook,
                $groupNamesByUser[$userId] ?? '-',
                $showUsername,
                $showIp
            );
        }

        return $attempts;
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeAttempt(TrackEExercise $attempt, CQuiz $quiz, Request $request, bool $lockedByGradebook, string $groupName, bool $showUsername, bool $showIp): array
    {
        $user = $attempt->getUser();
        $questionsToCheck = $this->parseQuestionIds($attempt->getQuestionsToCheck());
        $pendingCorrection = [] !== $questionsToCheck;
        $status = $this->getAttemptStatus($attempt, $pendingCorrection);
        $statusLabel = match ($status) {
            self::STATUS_PENDING_CORRECTION => 'Pending correction',
            self::STATUS_INCOMPLETE => 'Ongoing',
            default => 'Completed',
        };
        $score = $attempt->getScore();
        $maxScore = $attempt->getMaxScore();
        $percentage = $maxScore > 0.0 ? round(($score * 100) / $maxScore, 2) : 0.0;
        $attemptId = (int) $attempt->getExeId();
        $userId = (int) $user->getId();

        return [
            'id' => $attemptId,
            'attemptId' => $attemptId,
            'exerciseId' => (int) $quiz->getIid(),
            'userId' => $userId,
            'username' => $showUsername ? $user->getUsername() : '',
            'officialCode' => (string) ($user->getOfficialCode() ?? ''),
            'firstName' => (string) $user->getFirstname(),
            'lastName' => (string) $user->getLastname(),
            'fullName' => $user->getFullName(),
            'groupName' => $groupName,
            'duration' => $attempt->getExeDuration(),
            'startedAt' => $this->formatDate($attempt->getStartDate()),
            'completedAt' => $this->formatDate($attempt->getExeDate()),
            'score' => $score,
            'maxScore' => $maxScore,
            'percentage' => $percentage,
            'ip' => $showIp ? $attempt->getUserIp() : '',
            'status' => $status,
            'statusLabel' => $statusLabel,
            'pendingCorrection' => $pendingCorrection,
            'questionsToCheck' => $questionsToCheck,
            'learningPath' => $this->formatLearningPath($attempt),
            'canReview' => self::STATUS_INCOMPLETE !== $status,
            'canClose' => self::STATUS_INCOMPLETE === $status && !$lockedByGradebook,
            'canRecalculate' => !$lockedByGradebook,
            'canDelete' => !$lockedByGradebook && $this->canDeleteResults(),
        ];
    }

    private function getAttemptStatus(TrackEExercise $attempt, bool $pendingCorrection): string
    {
        if ($pendingCorrection) {
            return self::STATUS_PENDING_CORRECTION;
        }

        if (self::STATUS_INCOMPLETE === (string) $attempt->getStatus()) {
            return self::STATUS_INCOMPLETE;
        }

        return self::STATUS_COMPLETED;
    }

    private function formatLearningPath(TrackEExercise $attempt): string
    {
        if ($attempt->getOrigLpId() > 0) {
            return '#'.$attempt->getOrigLpId();
        }

        return '-';
    }

    private function formatDate(DateTimeInterface $date): string
    {
        return $date->format(DateTimeInterface::ATOM);
    }

    /**
     * @return array<int, int>
     */
    private function parseQuestionIds(string $value): array
    {
        $ids = [];
        foreach (preg_split('/[,;]+/', $value) ?: [] as $rawId) {
            $id = (int) trim($rawId);
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    private function applyUserFilters(QueryBuilder $queryBuilder, Request $request): void
    {
        $firstName = trim((string) $request->query->get('firstName', ''));
        if ('' !== $firstName) {
            $queryBuilder
                ->andWhere('LOWER(user.firstname) LIKE :firstName')
                ->setParameter('firstName', '%'.mb_strtolower($firstName).'%', Types::STRING)
            ;
        }

        $lastName = trim((string) $request->query->get('lastName', ''));
        if ('' !== $lastName) {
            $queryBuilder
                ->andWhere('LOWER(user.lastname) LIKE :lastName')
                ->setParameter('lastName', '%'.mb_strtolower($lastName).'%', Types::STRING)
            ;
        }
    }

    private function applyGroupFilter(QueryBuilder $queryBuilder, Course $course, Request $request): void
    {
        $groupFilter = $this->getGroupFilterValue($request);
        if ('' === $groupFilter || 'group_all' === $groupFilter) {
            return;
        }

        $joinType = 'group_none' === $groupFilter ? 'leftJoin' : 'innerJoin';
        $queryBuilder
            ->{$joinType}(
                CGroupRelUser::class,
                'groupRelFilter',
                'WITH',
                'groupRelFilter.user = user AND groupRelFilter.cId = :groupCourseId'
            )
            ->setParameter('groupCourseId', (int) $course->getId(), Types::INTEGER)
        ;

        if ('group_none' === $groupFilter) {
            $queryBuilder->andWhere('groupRelFilter.iid IS NULL');

            return;
        }

        $groupId = (int) $groupFilter;
        if ($groupId > 0) {
            $queryBuilder
                ->andWhere('IDENTITY(groupRelFilter.group) = :groupId')
                ->setParameter('groupId', $groupId, Types::INTEGER)
            ;
        }
    }

    private function getGroupFilterValue(Request $request): string
    {
        return trim((string) $request->query->get(
            'groupId',
            $request->query->get('group_id', $request->query->get('group_id_in_toolbar', ''))
        ));
    }

    /**
     * @param array<int, TrackEExercise> $attempts
     *
     * @return array<int, int>
     */
    private function collectUserIdsFromAttempts(array $attempts): array
    {
        $userIds = [];
        foreach ($attempts as $attempt) {
            $userIds[] = (int) $attempt->getUser()->getId();
        }

        return array_values(array_unique(array_filter($userIds, static fn (int $userId): bool => $userId > 0)));
    }

    /**
     * @param array<int, int> $userIds
     *
     * @return array<int, string>
     */
    private function getGroupNamesByUserIds(array $userIds, Course $course): array
    {
        if ([] === $userIds) {
            return [];
        }

        $rows = $this->entityManager->createQueryBuilder()
            ->select('rel', 'groupInfo')
            ->from(CGroupRelUser::class, 'rel')
            ->innerJoin('rel.group', 'groupInfo')
            ->andWhere('rel.cId = :courseId')
            ->andWhere('IDENTITY(rel.user) IN (:userIds)')
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->setParameter('userIds', $userIds, ArrayParameterType::INTEGER)
            ->orderBy('groupInfo.title', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        $names = [];
        foreach ($rows as $row) {
            if (!$row instanceof CGroupRelUser) {
                continue;
            }

            $userId = (int) $row->getUser()->getId();
            $title = trim($row->getGroup()->getTitle());
            if ('' === $title) {
                continue;
            }

            if (!isset($names[$userId])) {
                $names[$userId] = [];
            }
            $names[$userId][] = $title;
        }

        $formatted = [];
        foreach ($names as $userId => $groupNames) {
            $formatted[$userId] = implode(', ', array_values(array_unique($groupNames)));
        }

        return $formatted;
    }

    /**
     * @return array<int, array<string, int|string>>
     */
    private function getGroupOptions(Course $course): array
    {
        $rows = $this->entityManager->createQueryBuilder()
            ->select('rel', 'groupInfo')
            ->from(CGroupRelUser::class, 'rel')
            ->innerJoin('rel.group', 'groupInfo')
            ->andWhere('rel.cId = :courseId')
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->orderBy('groupInfo.title', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        $options = [];
        foreach ($rows as $row) {
            if (!$row instanceof CGroupRelUser) {
                continue;
            }

            $group = $row->getGroup();
            $groupId = (int) $group->getIid();
            if ($groupId <= 0 || isset($options[$groupId])) {
                continue;
            }

            $options[$groupId] = [
                'label' => $group->getTitle(),
                'value' => $groupId,
            ];
        }

        return array_values($options);
    }

    private function canDeleteResults(): bool
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        return !$this->isSettingEnabled('exercise.limit_exercise_teacher_access');
    }

    private function canCleanResults(): bool
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        return !$this->isSettingEnabled('exercise.limit_exercise_teacher_access')
            && !$this->isSettingEnabled('exercise.disable_clean_exercise_results_for_teachers');
    }

    private function isGradebookLocked(int $exerciseId, Course $course, ?Session $session): bool
    {
        return $this->gradebookLinkManager->isResourceLocked(
            $course,
            $session,
            GradebookLinkResourceResolver::LINK_EXERCISE,
            $exerciseId,
        );
    }

    private function isSettingEnabled(string $name): bool
    {
        return 'true' === $this->settingsManager->getSetting($name, true);
    }

    private function shouldShowOfficialCode(): bool
    {
        return $this->isSettingEnabled('exercise.show_official_code_exercise_result_list');
    }

    private function shouldShowUsername(): bool
    {
        return $this->isSettingEnabled('exercise.exercise_attempts_report_show_username');
    }

    private function shouldShowIp(): bool
    {
        return !$this->isSettingEnabled('exercise.exercise_hide_ip');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getFilterableUserExtraFields(): array
    {
        $rows = $this->entityManager->createQueryBuilder()
            ->select('field')
            ->from(ExtraField::class, 'field')
            ->andWhere('field.itemType = :itemType')
            ->andWhere('field.filter = :filter')
            ->setParameter('itemType', ExtraField::USER_FIELD_TYPE, Types::INTEGER)
            ->setParameter('filter', true, Types::BOOLEAN)
            ->orderBy('field.fieldOrder', 'ASC')
            ->addOrderBy('field.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        $fields = [];
        foreach ($rows as $row) {
            if (!$row instanceof ExtraField) {
                continue;
            }

            $fieldId = (int) $row->getId();
            if ($fieldId <= 0) {
                continue;
            }

            $fields[] = [
                'id' => $fieldId,
                'variable' => $row->getVariable(),
                'label' => (string) ($row->getDisplayText() ?: $row->getVariable()),
            ];
        }

        return $fields;
    }

    /**
     * @return array<string, string>
     */
    private function getActionUrls(Operation $operation, CQuiz $quiz, Request $request): array
    {
        $exerciseId = (int) $quiz->getIid();

        return [
            'exportCsv' => $this->getModernExportUrl($operation, $exerciseId, 'csv', $request),
            'exportXlsx' => $this->getModernExportUrl($operation, $exerciseId, 'xlsx', $request),
            'exportAllAttempts' => $this->getModernExportAllAttemptsUrl($operation, $exerciseId, $request),
        ];
    }

    private function getModernExportUrl(Operation $operation, int $exerciseId, string $extension, Request $request): string
    {
        $params = $this->getExportParams($operation, $exerciseId, $request);

        return '/api/exercise/runtime/'.$exerciseId.'/attempts/export.'.$extension.'?'.http_build_query($params);
    }

    private function getModernExportAllAttemptsUrl(Operation $operation, int $exerciseId, Request $request): string
    {
        $params = $this->getExportParams($operation, $exerciseId, $request);

        return '/api/exercise/runtime/'.$exerciseId.'/attempts/export-all.zip?'.http_build_query($params);
    }

    /**
     * @return array<string, int|string>
     */
    private function getExportParams(Operation $operation, int $exerciseId, Request $request): array
    {
        $params = $this->getBaseParams($operation, $exerciseId, $request);

        foreach (['firstName', 'lastName', 'status'] as $filterName) {
            $value = trim((string) $request->query->get($filterName, ''));
            if ('' !== $value) {
                $params[$filterName] = $value;
            }
        }

        $groupFilter = $this->getGroupFilterValue($request);
        if ('' !== $groupFilter && 'group_all' !== $groupFilter) {
            $params['groupId'] = $groupFilter;
        }

        return $params;
    }

    /**
     * @return array<string, int|string>
     */
    private function getBaseParams(Operation $operation, int $exerciseId, Request $request): array
    {
        return ['exerciseId' => $exerciseId] + $this->getContextParams($operation);
    }

    /**
     * @return array<string, int|string>
     */
    private function getContextParams(Operation $operation): array
    {
        $params = [
            'cid' => (int) $this->cidReqHelper->getCourseId(),
            'gid' => (int) $this->cidReqHelper->getGroupId(),
        ];

        $sessionId = (int) $this->cidReqHelper->getSessionId();
        if ($sessionId > 0) {
            $params['sid'] = $sessionId;
        }

        return $params;
    }
}
