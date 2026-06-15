<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Exercise;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CGroupRelUser;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Repository\CQuizRepository;
use DateTimeInterface;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class ExerciseRuntimeReportExportService
{
    private const VISIBILITY_PUBLISHED = 2;
    private const STATUS_INCOMPLETE = 'incomplete';
    private const STATUS_PENDING_CORRECTION = 'pending_correction';
    private const STATUS_COMPLETED = 'completed';
    private const STATUS_NOT_ATTEMPTED = 'not_attempted';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private CQuizRepository $quizRepository,
        private Security $security,
        private SettingsManager $settingsManager,
    ) {}

    public function exportCsv(int $exerciseId, Request $request): StreamedResponse
    {
        $quiz = $this->getValidatedExercise($exerciseId, $request);
        $exportData = $this->buildExportData($quiz, $request);
        $fileName = $this->buildFileName($quiz, 'csv');

        $response = new StreamedResponse(static function () use ($exportData): void {
            $handle = fopen('php://output', 'w');
            if (!\is_resource($handle)) {
                return;
            }

            fputcsv($handle, $exportData['headers']);
            foreach ($exportData['rows'] as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $fileName)
        );

        return $response;
    }

    public function exportXlsx(int $exerciseId, Request $request): BinaryFileResponse
    {
        $quiz = $this->getValidatedExercise($exerciseId, $request);
        $exportData = $this->buildExportData($quiz, $request);
        $fileName = $this->buildFileName($quiz, 'xlsx');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Learner score');
        $sheet->fromArray($exportData['headers'], null, 'A1');

        $rowNumber = 2;
        foreach ($exportData['rows'] as $row) {
            $sheet->fromArray($row, null, 'A'.$rowNumber);
            ++$rowNumber;
        }

        $columnCount = \count($exportData['headers']);
        for ($columnIndex = 1; $columnIndex <= $columnCount; ++$columnIndex) {
            $sheet->getColumnDimensionByColumn($columnIndex)->setAutoSize(true);
        }

        $filePath = tempnam(sys_get_temp_dir(), 'exercise-report-');
        if (false === $filePath) {
            throw new BadRequestHttpException('The export file could not be created.');
        }

        $xlsxPath = $filePath.'.xlsx';
        rename($filePath, $xlsxPath);

        $writer = new Xlsx($spreadsheet);
        $writer->save($xlsxPath);
        $spreadsheet->disconnectWorksheets();

        $response = new BinaryFileResponse(new File($xlsxPath));
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $fileName);
        $response->deleteFileAfterSend(true);

        return $response;
    }

    private function getValidatedExercise(int $exerciseId, Request $request): CQuiz
    {
        if (0 >= $exerciseId) {
            throw new BadRequestHttpException('A valid exercise id is required.');
        }

        if (!$this->canExportReport()) {
            throw new AccessDeniedHttpException('You are not allowed to export this exercise report.');
        }

        $course = $this->getCourse($request);
        $session = $this->getSession($request);

        return $this->getExerciseFromCurrentContext($exerciseId, $course, $session);
    }

    private function getCourse(Request $request): Course
    {
        $courseId = $request->query->getInt('cid');
        if (0 >= $courseId) {
            throw new BadRequestHttpException('A valid course id is required.');
        }

        $course = $this->entityManager->getRepository(Course::class)->find($courseId);
        if (!$course instanceof Course) {
            throw new BadRequestHttpException('The requested course was not found.');
        }

        return $course;
    }

    private function getSession(Request $request): ?Session
    {
        $sessionId = $request->query->getInt('sid');
        if (0 >= $sessionId) {
            return null;
        }

        $session = $this->entityManager->getRepository(Session::class)->find($sessionId);
        if (!$session instanceof Session) {
            throw new BadRequestHttpException('The requested session was not found.');
        }

        return $session;
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
        if (0 !== $visibility && self::VISIBILITY_PUBLISHED !== $visibility && !$this->canExportReport()) {
            throw new AccessDeniedHttpException('The requested exercise is not visible.');
        }

        return $quiz;
    }

    /**
     * @return array{headers: array<int, string>, rows: array<int, array<int, int|float|string>>}
     */
    private function buildExportData(CQuiz $quiz, Request $request): array
    {
        $course = $this->getCourse($request);
        $session = $this->getSession($request);
        $showOfficialCode = $this->shouldShowOfficialCode();
        $loadExtraData = $this->getBooleanQuery($request, 'extraData', 'extra_data');
        $includeAllUsers = $this->getBooleanQuery($request, 'includeAllUsers', 'include_all_users');
        $extraFields = $loadExtraData ? $this->getFilterableUserExtraFields() : [];
        $attempts = $this->getAttempts($quiz, $course, $session, $request);

        if ($this->getBooleanQuery($request, 'onlyBestAttempts', 'only_best_attempts')) {
            $attempts = $this->filterBestAttempts($attempts);
        }

        $users = $includeAllUsers ? $this->getSubscribedUsers($course, $session, $request) : [];
        $userIds = $this->collectUserIds($attempts, $users);
        $groupNamesByUser = $this->getGroupNamesByUserIds($userIds, $course);
        $extraFieldValues = [] !== $extraFields ? $this->getExtraFieldValues($extraFields, $userIds) : [];

        $rows = [];
        $attemptedUserIds = [];
        foreach ($attempts as $attempt) {
            $userId = (int) $attempt->getUser()->getId();
            $attemptedUserIds[$userId] = true;
            $rows[] = $this->buildAttemptRow(
                $attempt,
                $showOfficialCode,
                $extraFields,
                $extraFieldValues,
                $groupNamesByUser[$userId] ?? '-'
            );
        }

        if ($includeAllUsers && $this->shouldAppendNotAttemptedUsers($request)) {
            foreach ($users as $user) {
                $userId = (int) $user->getId();
                if (isset($attemptedUserIds[$userId])) {
                    continue;
                }

                $rows[] = $this->buildNotAttemptedUserRow(
                    $user,
                    $showOfficialCode,
                    $extraFields,
                    $extraFieldValues,
                    $groupNamesByUser[$userId] ?? '-'
                );
            }
        }

        return [
            'headers' => $this->getHeaders($showOfficialCode, $extraFields),
            'rows' => $rows,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function getHeaders(bool $showOfficialCode, array $extraFields): array
    {
        $headers = [
            'First name',
            'Last name',
            'Username',
            'Group',
            'Duration',
            'Started at',
            'Completed at',
            'Score',
            'Max score',
            'Percentage',
            'IP',
            'Status',
            'Learning path',
        ];

        if ($showOfficialCode) {
            array_unshift($headers, 'Official code');
        }

        foreach ($extraFields as $field) {
            $headers[] = (string) ($field['label'] ?? $field['variable'] ?? '');
        }

        return $headers;
    }

    /**
     * @param array<int, array<string, mixed>> $extraFields
     * @param array<int, array<int, string>>  $extraFieldValues
     *
     * @return array<int, int|float|string>
     */
    private function buildAttemptRow(
        TrackEExercise $attempt,
        bool $showOfficialCode,
        array $extraFields,
        array $extraFieldValues,
        string $groupName,
    ): array {
        $user = $attempt->getUser();
        $score = $attempt->getScore();
        $maxScore = $attempt->getMaxScore();
        $percentage = 0.0 < $maxScore ? round(($score * 100) / $maxScore, 2) : 0.0;

        $row = [
            (string) $user->getFirstname(),
            (string) $user->getLastname(),
            (string) $user->getUsername(),
            $groupName,
            $this->formatDuration($attempt->getExeDuration()),
            $this->formatDate($attempt->getStartDate()),
            $this->formatDate($attempt->getExeDate()),
            round($score, 2),
            round($maxScore, 2),
            $percentage,
            $attempt->getUserIp(),
            $this->getAttemptStatusLabel($attempt),
            $this->formatLearningPath($attempt),
        ];

        if ($showOfficialCode) {
            array_unshift($row, (string) ($user->getOfficialCode() ?? ''));
        }

        $this->appendExtraFieldValues($row, $user, $extraFields, $extraFieldValues);

        return $row;
    }

    /**
     * @param array<int, array<string, mixed>> $extraFields
     * @param array<int, array<int, string>>  $extraFieldValues
     *
     * @return array<int, int|float|string>
     */
    private function buildNotAttemptedUserRow(
        User $user,
        bool $showOfficialCode,
        array $extraFields,
        array $extraFieldValues,
        string $groupName,
    ): array {
        $row = [
            (string) $user->getFirstname(),
            (string) $user->getLastname(),
            (string) $user->getUsername(),
            $groupName,
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            'Not attempted',
            '-',
        ];

        if ($showOfficialCode) {
            array_unshift($row, (string) ($user->getOfficialCode() ?? ''));
        }

        $this->appendExtraFieldValues($row, $user, $extraFields, $extraFieldValues);

        return $row;
    }

    /**
     * @param array<int, int|float|string>    $row
     * @param array<int, array<string, mixed>> $extraFields
     * @param array<int, array<int, string>>  $extraFieldValues
     */
    private function appendExtraFieldValues(array &$row, User $user, array $extraFields, array $extraFieldValues): void
    {
        $userValues = $extraFieldValues[(int) $user->getId()] ?? [];
        foreach ($extraFields as $field) {
            $fieldId = (int) ($field['id'] ?? 0);
            $row[] = $userValues[$fieldId] ?? '';
        }
    }

    /**
     * @return array<int, TrackEExercise>
     */
    private function getAttempts(CQuiz $quiz, Course $course, ?Session $session, Request $request): array
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
        $this->applyStatusFilter($queryBuilder, $request);

        $attempts = [];
        foreach ($queryBuilder->getQuery()->getResult() as $attempt) {
            if ($attempt instanceof TrackEExercise) {
                $attempts[] = $attempt;
            }
        }

        return $attempts;
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
        if (0 < $groupId) {
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

    private function applyStatusFilter(QueryBuilder $queryBuilder, Request $request): void
    {
        $status = trim((string) $request->query->get('status', ''));
        if (self::STATUS_PENDING_CORRECTION === $status) {
            $queryBuilder->andWhere("attempt.questionsToCheck <> ''");

            return;
        }

        if (self::STATUS_INCOMPLETE === $status) {
            $queryBuilder
                ->andWhere('attempt.status = :status')
                ->setParameter('status', self::STATUS_INCOMPLETE, Types::STRING)
            ;

            return;
        }

        if (self::STATUS_COMPLETED === $status) {
            $queryBuilder
                ->andWhere('attempt.status = :status')
                ->andWhere("attempt.questionsToCheck = ''")
                ->setParameter('status', self::STATUS_COMPLETED, Types::STRING)
            ;
        }
    }

    /**
     * @param array<int, TrackEExercise> $attempts
     *
     * @return array<int, TrackEExercise>
     */
    private function filterBestAttempts(array $attempts): array
    {
        $bestAttempts = [];
        foreach ($attempts as $attempt) {
            $userId = (int) $attempt->getUser()->getId();
            $current = $bestAttempts[$userId] ?? null;
            if (!$current instanceof TrackEExercise || $this->isBetterAttempt($attempt, $current)) {
                $bestAttempts[$userId] = $attempt;
            }
        }

        return array_values($bestAttempts);
    }

    private function isBetterAttempt(TrackEExercise $candidate, TrackEExercise $current): bool
    {
        $candidateMaxScore = $candidate->getMaxScore();
        $currentMaxScore = $current->getMaxScore();
        $candidatePercentage = 0.0 < $candidateMaxScore ? ($candidate->getScore() * 100) / $candidateMaxScore : 0.0;
        $currentPercentage = 0.0 < $currentMaxScore ? ($current->getScore() * 100) / $currentMaxScore : 0.0;

        if ($candidatePercentage > $currentPercentage) {
            return true;
        }

        if ($candidatePercentage < $currentPercentage) {
            return false;
        }

        return $candidate->getExeDate() > $current->getExeDate();
    }

    /**
     * @return array<int, User>
     */
    private function getSubscribedUsers(Course $course, ?Session $session, Request $request): array
    {
        if (null !== $session) {
            $queryBuilder = $this->entityManager->createQueryBuilder()
                ->select('rel', 'user')
                ->from(SessionRelCourseRelUser::class, 'rel')
                ->innerJoin('rel.user', 'user')
                ->andWhere('IDENTITY(rel.course) = :courseId')
                ->andWhere('IDENTITY(rel.session) = :sessionId')
                ->andWhere('rel.status = :status')
                ->andWhere('user.status <> :softDeleted')
                ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
                ->setParameter('sessionId', (int) $session->getId(), Types::INTEGER)
                ->setParameter('status', Session::STUDENT, Types::INTEGER)
                ->setParameter('softDeleted', User::SOFT_DELETED, Types::INTEGER)
            ;
        } else {
            $queryBuilder = $this->entityManager->createQueryBuilder()
                ->select('rel', 'user')
                ->from(CourseRelUser::class, 'rel')
                ->innerJoin('rel.user', 'user')
                ->andWhere('IDENTITY(rel.course) = :courseId')
                ->andWhere('rel.status = :status')
                ->andWhere('user.status <> :softDeleted')
                ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
                ->setParameter('status', CourseRelUser::STUDENT, Types::INTEGER)
                ->setParameter('softDeleted', User::SOFT_DELETED, Types::INTEGER)
            ;
        }

        $this->applyUserFilters($queryBuilder, $request);
        $this->applyGroupFilter($queryBuilder, $course, $request);
        $queryBuilder
            ->orderBy('user.lastname', 'ASC')
            ->addOrderBy('user.firstname', 'ASC')
            ->addOrderBy('user.username', 'ASC')
        ;

        $users = [];
        foreach ($queryBuilder->getQuery()->getResult() as $relation) {
            if (!$relation instanceof CourseRelUser && !$relation instanceof SessionRelCourseRelUser) {
                continue;
            }

            $user = $relation->getUser();
            $userId = (int) $user->getId();
            if (0 >= $userId) {
                continue;
            }

            $users[$userId] = $user;
        }

        return array_values($users);
    }

    /**
     * @param array<int, TrackEExercise> $attempts
     * @param array<int, User>           $users
     *
     * @return array<int, int>
     */
    private function collectUserIds(array $attempts, array $users): array
    {
        $userIds = [];
        foreach ($attempts as $attempt) {
            $userIds[] = (int) $attempt->getUser()->getId();
        }
        foreach ($users as $user) {
            $userIds[] = (int) $user->getId();
        }

        return array_values(array_unique(array_filter($userIds, static fn (int $userId): bool => 0 < $userId)));
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
            if (0 >= $fieldId) {
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
     * @param array<int, array<string, mixed>> $extraFields
     * @param array<int, int>                  $userIds
     *
     * @return array<int, array<int, string>>
     */
    private function getExtraFieldValues(array $extraFields, array $userIds): array
    {
        $fieldIds = array_values(array_filter(
            array_map(static fn (array $field): int => (int) ($field['id'] ?? 0), $extraFields),
            static fn (int $fieldId): bool => 0 < $fieldId
        ));

        if ([] === $fieldIds || [] === $userIds) {
            return [];
        }

        $rows = $this->entityManager->createQueryBuilder()
            ->select('value', 'field')
            ->from(ExtraFieldValues::class, 'value')
            ->innerJoin('value.field', 'field')
            ->andWhere('field.id IN (:fieldIds)')
            ->andWhere('value.itemId IN (:userIds)')
            ->setParameter('fieldIds', $fieldIds, ArrayParameterType::INTEGER)
            ->setParameter('userIds', $userIds, ArrayParameterType::INTEGER)
            ->getQuery()
            ->getResult()
        ;

        $values = [];
        foreach ($rows as $row) {
            if (!$row instanceof ExtraFieldValues) {
                continue;
            }

            $values[$row->getItemId()][(int) $row->getField()->getId()] = (string) ($row->getFieldValue() ?? '');
        }

        return $values;
    }

    private function canExportReport(): bool
    {
        return $this->security->isGranted('ROLE_CURRENT_COURSE_TEACHER')
            || $this->security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER');
    }

    private function shouldShowOfficialCode(): bool
    {
        return 'true' === $this->settingsManager->getSetting('exercise.show_official_code_exercise_result_list', true);
    }

    private function shouldAppendNotAttemptedUsers(Request $request): bool
    {
        $status = trim((string) $request->query->get('status', ''));

        return '' === $status || self::STATUS_NOT_ATTEMPTED === $status;
    }

    private function getBooleanQuery(Request $request, string $modernName, string $legacyName): bool
    {
        $value = $request->query->get($modernName, $request->query->get($legacyName, '0'));

        return \in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }

    private function getAttemptStatusLabel(TrackEExercise $attempt): string
    {
        if ('' !== trim($attempt->getQuestionsToCheck())) {
            return 'Pending correction';
        }

        if (self::STATUS_INCOMPLETE === (string) $attempt->getStatus()) {
            return 'Ongoing';
        }

        return 'Completed';
    }

    private function formatLearningPath(TrackEExercise $attempt): string
    {
        if (0 < $attempt->getOrigLpId()) {
            return '#'.$attempt->getOrigLpId();
        }

        return '-';
    }

    private function formatDuration(int $duration): string
    {
        $seconds = max(0, $duration);
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $remainingSeconds = $seconds % 60;

        if (0 < $hours) {
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $remainingSeconds);
        }

        return sprintf('%02d:%02d', $minutes, $remainingSeconds);
    }

    private function formatDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }

    private function buildFileName(CQuiz $quiz, string $extension): string
    {
        $safeTitle = preg_replace('/[^A-Za-z0-9_-]+/', '-', $quiz->getTitle()) ?: 'exercise-report';
        $safeTitle = trim($safeTitle, '-');
        if ('' === $safeTitle) {
            $safeTitle = 'exercise-report';
        }

        return strtolower($safeTitle).'-learner-score.'.$extension;
    }
}
