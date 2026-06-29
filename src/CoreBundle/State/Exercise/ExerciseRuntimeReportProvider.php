<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Exercise;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Exercise\ExerciseRuntimeReport;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\GradebookLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Entity\CGroupRelUser;
use Chamilo\CoreBundle\Settings\SettingsManager;
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
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Read-only provider for the migrated exercise learner attempts report.
 *
 * @implements ProviderInterface<ExerciseRuntimeReport>
 */
final readonly class ExerciseRuntimeReportProvider implements ProviderInterface
{
    public const BULK_ACTION_CSRF_TOKEN_ID = 'exercise_runtime_report_bulk_action';
    public const EMAIL_ACTION_CSRF_TOKEN_ID = 'exercise_runtime_report_email_action';

    private const VISIBILITY_PUBLISHED = 2;
    private const LINK_TYPE_EXERCISE = 1;
    private const STATUS_INCOMPLETE = 'incomplete';
    private const STATUS_PENDING_CORRECTION = 'pending_correction';
    private const STATUS_COMPLETED = 'completed';

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private CQuizRepository $quizRepository,
        private Security $security,
        private SettingsManager $settingsManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
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

        $course = $this->getCourse($request);
        $session = $this->getSession($request);
        $exerciseId = isset($uriVariables['exerciseId']) ? (int) $uriVariables['exerciseId'] : 0;
        if (0 >= $exerciseId) {
            throw new BadRequestHttpException('A valid exercise id is required.');
        }

        if (!$this->canManageExercises()) {
            throw new AccessDeniedHttpException('You are not allowed to view this exercise report.');
        }

        $quiz = $this->getExerciseFromCurrentContext($exerciseId, $course, $session);
        $lockedByGradebook = $this->isGradebookLocked((int) $quiz->getIid(), $course);
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
        $response->actionUrls = $this->getActionUrls($quiz, $request);
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
        $response->bulkActionToken = $this->csrfTokenManager->getToken(self::BULK_ACTION_CSRF_TOKEN_ID)->getValue();
        $response->emailActionToken = $this->csrfTokenManager->getToken(self::EMAIL_ACTION_CSRF_TOKEN_ID)->getValue();

        return $response;
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

    private function canManageExercises(): bool
    {
        return $this->security->isGranted('ROLE_CURRENT_COURSE_TEACHER')
            || $this->security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER');
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
        if (0 !== $visibility && self::VISIBILITY_PUBLISHED !== $visibility && !$this->canManageExercises()) {
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
        $percentage = 0.0 < $maxScore ? round(($score * 100) / $maxScore, 2) : 0.0;
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
        if (0 < $attempt->getOrigLpId()) {
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
            if (0 < $id) {
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
            if (0 >= $groupId || isset($options[$groupId])) {
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

    private function isGradebookLocked(int $exerciseId, Course $course): bool
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return false;
        }

        if (!$this->isSettingEnabled('gradebook.gradebook_locking_enabled')) {
            return false;
        }

        $lockedLink = $this->entityManager->createQueryBuilder()
            ->select('link.id')
            ->from(GradebookLink::class, 'link')
            ->andWhere('link.locked = :locked')
            ->andWhere('link.refId = :exerciseId')
            ->andWhere('link.type = :linkType')
            ->andWhere('IDENTITY(link.course) = :courseId')
            ->setParameter('locked', 1, Types::INTEGER)
            ->setParameter('exerciseId', $exerciseId, Types::INTEGER)
            ->setParameter('linkType', self::LINK_TYPE_EXERCISE, Types::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return null !== $lockedLink;
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
     * @return array<string, string>
     */
    private function getActionUrls(CQuiz $quiz, Request $request): array
    {
        $exerciseId = (int) $quiz->getIid();

        return [
            'exportCsv' => $this->getModernExportUrl($exerciseId, 'csv', $request),
            'exportXlsx' => $this->getModernExportUrl($exerciseId, 'xlsx', $request),
            'exportAllAttempts' => $this->getModernExportAllAttemptsUrl($exerciseId, $request),
        ];
    }

    private function getModernExportUrl(int $exerciseId, string $extension, Request $request): string
    {
        $params = $this->getExportParams($exerciseId, $request);

        return '/api/exercise/runtime/'.$exerciseId.'/attempts/export.'.$extension.'?'.http_build_query($params);
    }

    private function getModernExportAllAttemptsUrl(int $exerciseId, Request $request): string
    {
        $params = $this->getExportParams($exerciseId, $request);

        return '/api/exercise/runtime/'.$exerciseId.'/attempts/export-all.zip?'.http_build_query($params);
    }

    /**
     * @return array<string, int|string>
     */
    private function getExportParams(int $exerciseId, Request $request): array
    {
        $params = $this->getBaseParams($exerciseId, $request);

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
    private function getBaseParams(int $exerciseId, Request $request): array
    {
        return ['exerciseId' => $exerciseId] + $this->getContextParams($request);
    }

    /**
     * @return array<string, int|string>
     */
    private function getContextParams(Request $request): array
    {
        $params = [
            'cid' => $request->query->getInt('cid'),
            'gid' => $request->query->getInt('gid'),
        ];

        $sessionId = $request->query->getInt('sid');
        if (0 < $sessionId) {
            $params['sid'] = $sessionId;
        }

        return $params;
    }
}
