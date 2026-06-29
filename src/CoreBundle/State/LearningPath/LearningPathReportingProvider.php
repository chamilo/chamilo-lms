<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\LearningPath;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\LearningPath\LearningPathReporting;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\Usergroup;
use Chamilo\CoreBundle\Entity\UsergroupRelUser;
use Chamilo\CoreBundle\Repository\ExtraFieldValuesRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CGroupRelUser;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpCategoryRelUser;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Entity\CLpItemView;
use Chamilo\CourseBundle\Entity\CLpIvInteraction;
use Chamilo\CourseBundle\Entity\CLpIvObjective;
use Chamilo\CourseBundle\Entity\CLpRelUser;
use Chamilo\CourseBundle\Entity\CLpView;
use Chamilo\CourseBundle\Repository\CGroupRepository;
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

/** @implements ProviderInterface<LearningPathReporting> */
final readonly class LearningPathReportingProvider implements ProviderInterface
{
    use LearningPathStateHelperTrait;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack,
        private Security $security,
        private SettingsManager $settingsManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private CGroupRepository $groupRepository,
        private ExtraFieldValuesRepository $extraFieldValuesRepository,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): LearningPathReporting
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('Request is missing.');
        }

        $this->assertLearningPathTeacher($this->security);
        $course = $this->getContextCourse($this->entityManager, $request);
        $session = $this->getContextSession($this->entityManager, $request, $course);
        $group = $this->getContextGroup($this->entityManager, $request, $course);
        $lp = $this->getLearningPath($uriVariables);
        $this->getEditableResourceLink($lp, $course, $session, $group, $this->security);

        $showTeachers = $request->query->getBoolean('showTeachers');
        $groupFilter = trim((string) $request->query->get('groupFilter', ''));
        $users = $this->getUsersForContext($lp, $course, $session, $request);
        $metrics = $this->getMetrics($lp, $course, $session, array_keys($users));
        $groupNames = $this->getCourseGroupNames(array_keys($users), $course);
        $classNames = $this->getClassNames(array_keys($users));

        $result = new LearningPathReporting();
        $result->lpId = (int) $lp->getIid();
        $result->lpTitle = $lp->getTitle();
        $result->courseId = (int) $course->getId();
        $result->courseTitle = $course->getTitle();
        $result->sessionId = (int) ($session?->getId() ?? 0);
        $result->showEmail = $this->settingEnabled('show_email_addresses');
        $result->hideTime = $this->settingEnabled('lp.hide_lp_time');
        $result->reducedReport = $this->settingEnabled('lp.lp_show_reduced_report');
        $result->allowUserGroups = $this->settingEnabled('lp.allow_lp_subscription_to_usergroups');
        $result->showTeachers = $showTeachers;
        $result->groupFilter = $groupFilter;
        $result->groupOptions = $this->getGroupOptions($course, $session, $result->allowUserGroups);
        $result->csrfToken = $this->csrfTokenManager->getToken('learning_path_action')->getValue();

        foreach ($users as $userId => $entry) {
            $user = $entry['user'];
            $userMetrics = $metrics[$userId] ?? $this->emptyMetrics();
            $result->learners[] = [
                'id' => $userId,
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getLastname(),
                'username' => $user->getUsername(),
                'email' => $result->showEmail ? $user->getEmail() : '',
                'role' => $entry['role'],
                'groups' => $groupNames[$userId] ?? [],
                'classes' => $classNames[$userId] ?? [],
                'timeSeconds' => $userMetrics['timeSeconds'],
                'progress' => $userMetrics['progress'],
                'score' => $userMetrics['score'],
                'lastConnection' => $userMetrics['lastConnection'],
                'generalReportingUrl' => $this->buildGeneralReportingUrl($course, $session, $userId),
            ];
        }

        usort(
            $result->learners,
            static fn (array $left, array $right): int => strcasecmp(
                $left['lastname'].' '.$left['firstname'],
                $right['lastname'].' '.$right['firstname'],
            ),
        );

        $studentId = $request->query->getInt('studentId');
        if ($studentId > 0) {
            if (!isset($users[$studentId])) {
                throw new AccessDeniedHttpException('The requested learner is outside this learning path context.');
            }
            $result->detail = $this->buildDetail(
                $lp,
                $course,
                $session,
                $group,
                $users[$studentId]['user'],
                $metrics[$studentId] ?? $this->emptyMetrics(),
            );
        }

        return $result;
    }

    /**
     * @return array<int, array{user: User, role: string}>
     */
    public function getUsersForContext(
        CLp $lp,
        Course $course,
        ?Session $session,
        Request $request,
    ): array {
        return $this->getReportUsers(
            $lp,
            $course,
            $session,
            $request->query->getBoolean('showTeachers'),
            trim((string) $request->query->get('groupFilter', '')),
        );
    }

    /** @param array<string, mixed> $uriVariables */
    private function getLearningPath(array $uriVariables): CLp
    {
        $lpId = (int) ($uriVariables['lpId'] ?? 0);
        if ($lpId <= 0) {
            throw new BadRequestHttpException('Invalid learning path id.');
        }

        $lp = $this->entityManager->getRepository(CLp::class)->find($lpId);
        if (!$lp instanceof CLp) {
            throw new NotFoundHttpException('Learning path not found.');
        }

        return $lp;
    }

    /**
     * @return array<int, array{user: User, role: string}>
     */
    private function getReportUsers(
        CLp $lp,
        Course $course,
        ?Session $session,
        bool $showTeachers,
        string $groupFilter,
    ): array {
        $users = [];

        if (1 === $lp->getSubscribeUsers()) {
            $users = $this->getLearningPathSubscribers($lp, $course, $session);
        } else {
            if (null !== $lp->getCategory()) {
                foreach ($lp->getCategory()->getUsers() as $relation) {
                    if (!$relation instanceof CLpCategoryRelUser) {
                        continue;
                    }
                    $user = $relation->getUser();
                    if ($user instanceof User && null !== $user->getId()) {
                        $users[(int) $user->getId()] = ['user' => $user, 'role' => 'student'];
                    }
                }
            }

            if ([] === $users) {
                $users = $this->getCourseStudents($course, $session);
            }
        }

        if ('' !== $groupFilter) {
            $allowedIds = $this->getFilterUserIds($groupFilter, $course);
            $users = array_intersect_key($users, array_fill_keys($allowedIds, true));
        }

        if ($showTeachers) {
            foreach ($this->getCourseTeachers($course, $session) as $userId => $entry) {
                $users[$userId] = $entry;
            }
        }

        return array_filter(
            $users,
            static fn (array $entry): bool => $entry['user']->isEnabled() && !$entry['user']->isSoftDeleted(),
        );
    }

    /** @return array<int, array{user: User, role: string}> */
    private function getLearningPathSubscribers(CLp $lp, Course $course, ?Session $session): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('relation', 'user')
            ->from(CLpRelUser::class, 'relation')
            ->innerJoin('relation.user', 'user')
            ->andWhere('IDENTITY(relation.lp) = :lpId')
            ->andWhere('IDENTITY(relation.course) = :courseId')
            ->setParameter('lpId', (int) $lp->getIid(), Types::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
        ;
        $this->applySessionCondition($qb, 'relation.session', $session);

        /** @var array<int, CLpRelUser> $relations */
        $relations = $qb->getQuery()->getResult();
        $users = [];
        foreach ($relations as $relation) {
            $user = $relation->getUser();
            if (null !== $user->getId()) {
                $users[(int) $user->getId()] = ['user' => $user, 'role' => 'student'];
            }
        }

        foreach ($lp->getResourceNode()?->getResourceLinks() ?? [] as $resourceLink) {
            if (!$resourceLink instanceof ResourceLink
                || $resourceLink->getCourse()?->getId() !== $course->getId()
                || $resourceLink->getSession()?->getId() !== $session?->getId()
                || null === $resourceLink->getGroup()?->getIid()
            ) {
                continue;
            }

            /** @var array<int, User> $groupUsers */
            $groupUsers = $this->entityManager->createQueryBuilder()
                ->select('user')
                ->from(CGroupRelUser::class, 'relation')
                ->innerJoin('relation.user', 'user')
                ->andWhere('relation.cId = :courseId')
                ->andWhere('IDENTITY(relation.group) = :groupId')
                ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
                ->setParameter('groupId', (int) $resourceLink->getGroup()->getIid(), Types::INTEGER)
                ->getQuery()
                ->getResult()
            ;

            foreach ($groupUsers as $user) {
                if (null !== $user->getId()) {
                    $users[(int) $user->getId()] = ['user' => $user, 'role' => 'student'];
                }
            }
        }

        return $users;
    }

    /** @return array<int, array{user: User, role: string}> */
    private function getCourseStudents(Course $course, ?Session $session): array
    {
        $users = [];
        if (null === $session) {
            foreach ($course->getStudentSubscriptions() as $subscription) {
                if (!$subscription instanceof CourseRelUser) {
                    continue;
                }
                $user = $subscription->getUser();
                if (null !== $user->getId()) {
                    $users[(int) $user->getId()] = ['user' => $user, 'role' => 'student'];
                }
            }

            return $users;
        }

        foreach ($session->getSessionRelCourseRelUsers() as $subscription) {
            if (!$subscription instanceof SessionRelCourseRelUser
                || Session::STUDENT !== $subscription->getStatus()
                || $subscription->getCourse()->getId() !== $course->getId()
            ) {
                continue;
            }
            $user = $subscription->getUser();
            if (null !== $user->getId()) {
                $users[(int) $user->getId()] = ['user' => $user, 'role' => 'student'];
            }
        }

        return $users;
    }

    /** @return array<int, array{user: User, role: string}> */
    private function getCourseTeachers(Course $course, ?Session $session): array
    {
        $users = [];
        if (null === $session) {
            foreach ($course->getTeachersSubscriptions() as $subscription) {
                if (!$subscription instanceof CourseRelUser) {
                    continue;
                }
                $user = $subscription->getUser();
                if (null !== $user->getId()) {
                    $users[(int) $user->getId()] = ['user' => $user, 'role' => 'teacher'];
                }
            }

            return $users;
        }

        foreach ($session->getSessionRelCourseRelUsers() as $subscription) {
            if (!$subscription instanceof SessionRelCourseRelUser
                || Session::COURSE_COACH !== $subscription->getStatus()
                || $subscription->getCourse()->getId() !== $course->getId()
            ) {
                continue;
            }
            $user = $subscription->getUser();
            if (null !== $user->getId()) {
                $users[(int) $user->getId()] = ['user' => $user, 'role' => 'teacher'];
            }
        }

        return $users;
    }

    /** @return array<int, int> */
    private function getFilterUserIds(string $filter, Course $course): array
    {
        [$type, $rawId] = array_pad(explode(':', $filter, 2), 2, '');
        $id = (int) $rawId;
        if ($id <= 0) {
            return [];
        }

        if ('group' === $type) {
            $rows = $this->entityManager->createQueryBuilder()
                ->select('IDENTITY(relation.user) AS userId')
                ->from(CGroupRelUser::class, 'relation')
                ->andWhere('relation.cId = :courseId')
                ->andWhere('IDENTITY(relation.group) = :groupId')
                ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
                ->setParameter('groupId', $id, Types::INTEGER)
                ->getQuery()
                ->getArrayResult()
            ;

            return array_values(array_unique(array_map(static fn (array $row): int => (int) $row['userId'], $rows)));
        }

        if ('class' === $type && $this->settingEnabled('lp.allow_lp_subscription_to_usergroups')) {
            $rows = $this->entityManager->createQueryBuilder()
                ->select('IDENTITY(relation.user) AS userId')
                ->from(UsergroupRelUser::class, 'relation')
                ->innerJoin('relation.usergroup', 'usergroup')
                ->innerJoin('usergroup.courses', 'courseRelation')
                ->andWhere('IDENTITY(relation.usergroup) = :userGroupId')
                ->andWhere('IDENTITY(courseRelation.course) = :courseId')
                ->setParameter('userGroupId', $id, Types::INTEGER)
                ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
                ->getQuery()
                ->getArrayResult()
            ;

            return array_values(array_unique(array_map(static fn (array $row): int => (int) $row['userId'], $rows)));
        }

        return [];
    }

    /**
     * @param array<int, int> $userIds
     *
     * @return array<int, array{timeSeconds: int, progress: int, score: float|null, lastConnection: int|null}>
     */
    private function getMetrics(CLp $lp, Course $course, ?Session $session, array $userIds): array
    {
        if ([] === $userIds) {
            return [];
        }

        $viewQb = $this->entityManager->createQueryBuilder()
            ->select('view', 'user')
            ->from(CLpView::class, 'view')
            ->innerJoin('view.user', 'user')
            ->andWhere('IDENTITY(view.lp) = :lpId')
            ->andWhere('IDENTITY(view.course) = :courseId')
            ->andWhere('IDENTITY(view.user) IN (:userIds)')
            ->setParameter('lpId', (int) $lp->getIid(), Types::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->setParameter('userIds', $userIds, ArrayParameterType::INTEGER)
            ->orderBy('view.viewCount', 'ASC')
        ;
        $this->applySessionCondition($viewQb, 'view.session', $session);

        /** @var array<int, CLpView> $views */
        $views = $viewQb->getQuery()->getResult();
        if ([] === $views) {
            return [];
        }

        $viewIds = [];
        $viewUserIds = [];
        $latestViews = [];
        foreach ($views as $view) {
            $viewId = (int) $view->getIid();
            $userId = (int) $view->getUser()->getId();
            $viewIds[] = $viewId;
            $viewUserIds[$viewId] = $userId;
            if (!isset($latestViews[$userId]) || $view->getViewCount() >= $latestViews[$userId]->getViewCount()) {
                $latestViews[$userId] = $view;
            }
        }

        $itemViews = $this->getItemViews($viewIds);
        $metrics = [];
        $scorableAttempts = [];
        $scorableItemViewIds = [];
        foreach ($userIds as $userId) {
            $metrics[$userId] = $this->emptyMetrics();
        }

        foreach ($itemViews as $itemView) {
            $viewId = (int) $itemView->getView()->getIid();
            $userId = $viewUserIds[$viewId] ?? 0;
            if (0 === $userId) {
                continue;
            }

            $metrics[$userId]['timeSeconds'] += max(0, (int) $itemView->getTotalTime());
            if ('not attempted' !== $itemView->getStatus()) {
                $metrics[$userId]['lastConnection'] = max(
                    (int) ($metrics[$userId]['lastConnection'] ?? 0),
                    max(0, (int) $itemView->getStartTime()),
                );
            }

            $latestView = $latestViews[$userId] ?? null;
            if (!$latestView instanceof CLpView || $latestView->getIid() !== $itemView->getView()->getIid()) {
                continue;
            }

            if (\in_array($itemView->getItem()->getItemType(), ['quiz', 'sco'], true)) {
                $scorableAttempts[$userId][] = $itemView;
                if (null !== $itemView->getIid()) {
                    $scorableItemViewIds[] = (int) $itemView->getIid();
                }
            }
        }

        $exerciseAttempts = $this->getLatestExerciseAttempts(
            array_values(array_unique($scorableItemViewIds)),
            $course,
            $session,
        );

        foreach ($latestViews as $userId => $latestView) {
            $metrics[$userId]['progress'] = max(0, min(100, (int) $latestView->getProgress()));
            $metrics[$userId]['score'] = $this->calculateScore(
                $lp,
                $scorableAttempts[$userId] ?? [],
                $exerciseAttempts,
            );
        }

        if ($this->minimumTimeAvailable((int) $course->getId(), $session?->getId())) {
            foreach ($this->getAccessCompletionTimes($lp, $course, $session, $userIds) as $userId => $timestamp) {
                $metrics[$userId]['lastConnection'] = $timestamp;
            }
        }

        return $metrics;
    }

    /**
     * @param array<int, CLpItemView>     $attempts
     * @param array<int, TrackEExercise> $exerciseAttempts
     */
    private function calculateScore(CLp $lp, array $attempts, array $exerciseAttempts): ?float
    {
        $percentages = [];

        foreach ($attempts as $attempt) {
            $item = $attempt->getItem();
            $score = (float) $attempt->getScore();
            $maxScore = (float) $item->getMaxScore();

            if ('quiz' === $item->getItemType()) {
                $exerciseAttempt = null !== $attempt->getIid()
                    ? ($exerciseAttempts[(int) $attempt->getIid()] ?? null)
                    : null;
                if ($exerciseAttempt instanceof TrackEExercise) {
                    $score = $exerciseAttempt->getScore();
                    $maxScore = $exerciseAttempt->getMaxScore();
                } elseif ($maxScore <= 0) {
                    $maxScore = (float) $attempt->getMaxScore();
                }
            } elseif ('sco' === $item->getItemType() && $maxScore <= 0) {
                $maxScore = 1 === $lp->getUseMaxScore()
                    ? 100.0
                    : (float) $attempt->getMaxScore();
            }

            if ($maxScore <= 0) {
                continue;
            }

            $percentages[] = max(0.0, min(100.0, 100 * $score / $maxScore));
        }

        if ([] === $percentages) {
            return null;
        }

        return round(array_sum($percentages) / \count($percentages), 2);
    }

    /**
     * @param array<int, int> $itemViewIds
     *
     * @return array<int, TrackEExercise>
     */
    private function getLatestExerciseAttempts(
        array $itemViewIds,
        Course $course,
        ?Session $session,
    ): array {
        if ([] === $itemViewIds) {
            return [];
        }

        $qb = $this->entityManager->createQueryBuilder()
            ->select('exercise')
            ->from(TrackEExercise::class, 'exercise')
            ->andWhere('exercise.origLpItemViewId IN (:itemViewIds)')
            ->andWhere('IDENTITY(exercise.course) = :courseId')
            ->andWhere('exercise.status = :exerciseStatus')
            ->setParameter('itemViewIds', $itemViewIds, ArrayParameterType::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->setParameter('exerciseStatus', '', Types::STRING)
            ->orderBy('exercise.exeDate', 'DESC')
        ;
        $this->applySessionCondition($qb, 'exercise.session', $session);

        /** @var array<int, TrackEExercise> $rows */
        $rows = $qb->getQuery()->getResult();
        $result = [];
        foreach ($rows as $row) {
            $itemViewId = $row->getOrigLpItemViewId();
            if ($itemViewId > 0 && !isset($result[$itemViewId])) {
                $result[$itemViewId] = $row;
            }
        }

        return $result;
    }

    /** @param array<int, int> $viewIds @return array<int, CLpItemView> */
    private function getItemViews(array $viewIds): array
    {
        if ([] === $viewIds) {
            return [];
        }

        return $this->entityManager->createQueryBuilder()
            ->select('itemView', 'item', 'view')
            ->from(CLpItemView::class, 'itemView')
            ->innerJoin('itemView.item', 'item')
            ->innerJoin('itemView.view', 'view')
            ->andWhere('IDENTITY(itemView.view) IN (:viewIds)')
            ->setParameter('viewIds', $viewIds, ArrayParameterType::INTEGER)
            ->orderBy('item.displayOrder', 'ASC')
            ->addOrderBy('itemView.viewCount', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @param array{timeSeconds: int, progress: int, score: float|null, lastConnection: int|null} $metrics
     *
     * @return array<string, mixed>
     */
    private function buildDetail(
        CLp $lp,
        Course $course,
        ?Session $session,
        ?CGroup $group,
        User $user,
        array $metrics,
    ): array {
        $viewsQb = $this->entityManager->createQueryBuilder()
            ->select('view')
            ->from(CLpView::class, 'view')
            ->andWhere('IDENTITY(view.lp) = :lpId')
            ->andWhere('IDENTITY(view.course) = :courseId')
            ->andWhere('IDENTITY(view.user) = :userId')
            ->setParameter('lpId', (int) $lp->getIid(), Types::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->setParameter('userId', (int) $user->getId(), Types::INTEGER)
            ->orderBy('view.viewCount', 'DESC')
        ;
        $this->applySessionCondition($viewsQb, 'view.session', $session);
        /** @var array<int, CLpView> $views */
        $views = $viewsQb->getQuery()->getResult();
        $viewIds = array_values(array_filter(array_map(static fn (CLpView $view): int => (int) $view->getIid(), $views)));
        $itemViews = $this->getItemViews($viewIds);

        /** @var array<int, CLpItem> $items */
        $items = $this->entityManager->createQueryBuilder()
            ->select('item')
            ->from(CLpItem::class, 'item')
            ->andWhere('IDENTITY(item.lp) = :lpId')
            ->setParameter('lpId', (int) $lp->getIid(), Types::INTEGER)
            ->orderBy('item.displayOrder', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        $itemViewIds = array_values(array_filter(array_map(
            static fn (CLpItemView $itemView): int => (int) $itemView->getIid(),
            $itemViews,
        )));
        $interactions = $this->getInteractions($itemViewIds);
        $objectives = $this->getObjectives($itemViewIds);
        $exerciseAttempts = $this->getExerciseAttempts($itemViewIds, $course, $session, $user);

        $attemptsByItem = [];
        foreach ($itemViews as $itemView) {
            $itemViewId = (int) $itemView->getIid();
            $itemId = (int) $itemView->getItem()->getIid();
            $exercise = $exerciseAttempts[$itemViewId] ?? null;
            $score = $exercise instanceof TrackEExercise
                ? $exercise->getScore()
                : (float) $itemView->getScore();
            $maxScore = $exercise instanceof TrackEExercise
                ? $exercise->getMaxScore()
                : (float) ($itemView->getMaxScore() ?? $itemView->getItem()->getMaxScore() ?? 0);
            $attemptsByItem[$itemId][] = [
                'itemViewId' => $itemViewId,
                'lpAttempt' => (int) $itemView->getView()->getViewCount(),
                'itemAttempt' => (int) $itemView->getViewCount(),
                'status' => $itemView->getStatus(),
                'score' => $score,
                'maxScore' => $maxScore,
                'timeSeconds' => max(0, (int) $itemView->getTotalTime()),
                'startTime' => max(0, (int) $itemView->getStartTime()),
                'interactions' => $interactions[$itemViewId] ?? [],
                'objectives' => $objectives[$itemViewId] ?? [],
                'exerciseAttemptId' => $exercise instanceof TrackEExercise ? $exercise->getExeId() : null,
                'exerciseResultUrl' => $exercise instanceof TrackEExercise
                    ? $this->buildExerciseResultUrl($exercise, $course, $session, $group)
                    : '',
            ];
        }

        $detailItems = [];
        foreach ($items as $item) {
            $itemId = (int) $item->getIid();
            $detailItems[] = [
                'id' => $itemId,
                'title' => $item->getTitle(),
                'type' => $item->getItemType(),
                'level' => max(0, (int) $item->getLvl()),
                'displayOrder' => (int) $item->getDisplayOrder(),
                'attempts' => $attemptsByItem[$itemId] ?? [],
            ];
        }

        return [
            'user' => [
                'id' => (int) $user->getId(),
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getLastname(),
                'username' => $user->getUsername(),
                'email' => $this->settingEnabled('show_email_addresses') ? $user->getEmail() : '',
            ],
            'timeSeconds' => $metrics['timeSeconds'],
            'progress' => $metrics['progress'],
            'score' => $metrics['score'],
            'lastConnection' => $metrics['lastConnection'],
            'lpAttempts' => array_map(
                static fn (CLpView $view): array => [
                    'id' => (int) $view->getIid(),
                    'attempt' => (int) $view->getViewCount(),
                    'progress' => (int) $view->getProgress(),
                    'lastItem' => (int) $view->getLastItem(),
                ],
                $views,
            ),
            'items' => $detailItems,
        ];
    }

    /** @param array<int, int> $itemViewIds @return array<int, array<int, array<string, mixed>>> */
    private function getInteractions(array $itemViewIds): array
    {
        if ([] === $itemViewIds) {
            return [];
        }

        /** @var array<int, CLpIvInteraction> $rows */
        $rows = $this->entityManager->createQueryBuilder()
            ->select('interaction')
            ->from(CLpIvInteraction::class, 'interaction')
            ->andWhere('interaction.lpIvId IN (:itemViewIds)')
            ->setParameter('itemViewIds', $itemViewIds, ArrayParameterType::INTEGER)
            ->orderBy('interaction.orderId', 'ASC')
            ->getQuery()
            ->getResult()
        ;
        $result = [];
        foreach ($rows as $row) {
            $result[$row->getLpIvId()][] = [
                'order' => $row->getOrderId(),
                'id' => $row->getInteractionId(),
                'type' => $row->getInteractionType(),
                'response' => $row->getStudentResponse(),
                'result' => $row->getResult(),
                'latency' => $row->getLatency(),
                'time' => $row->getCompletionTime(),
            ];
        }

        return $result;
    }

    /** @param array<int, int> $itemViewIds @return array<int, array<int, array<string, mixed>>> */
    private function getObjectives(array $itemViewIds): array
    {
        if ([] === $itemViewIds) {
            return [];
        }

        /** @var array<int, CLpIvObjective> $rows */
        $rows = $this->entityManager->createQueryBuilder()
            ->select('objective')
            ->from(CLpIvObjective::class, 'objective')
            ->andWhere('objective.lpIvId IN (:itemViewIds)')
            ->setParameter('itemViewIds', $itemViewIds, ArrayParameterType::INTEGER)
            ->orderBy('objective.orderId', 'ASC')
            ->getQuery()
            ->getResult()
        ;
        $result = [];
        foreach ($rows as $row) {
            $result[$row->getLpIvId()][] = [
                'order' => $row->getOrderId(),
                'id' => $row->getObjectiveId(),
                'status' => $row->getStatus(),
                'score' => $row->getScoreRaw(),
                'minScore' => $row->getScoreMin(),
                'maxScore' => $row->getScoreMax(),
            ];
        }

        return $result;
    }

    /**
     * @param array<int, int> $itemViewIds
     *
     * @return array<int, TrackEExercise>
     */
    private function getExerciseAttempts(array $itemViewIds, Course $course, ?Session $session, User $user): array
    {
        if ([] === $itemViewIds) {
            return [];
        }

        $qb = $this->entityManager->createQueryBuilder()
            ->select('exercise')
            ->from(TrackEExercise::class, 'exercise')
            ->andWhere('exercise.origLpItemViewId IN (:itemViewIds)')
            ->andWhere('IDENTITY(exercise.course) = :courseId')
            ->andWhere('IDENTITY(exercise.user) = :userId')
            ->andWhere('exercise.status = :exerciseStatus')
            ->setParameter('itemViewIds', $itemViewIds, ArrayParameterType::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->setParameter('userId', (int) $user->getId(), Types::INTEGER)
            ->setParameter('exerciseStatus', '', Types::STRING)
            ->orderBy('exercise.exeDate', 'DESC')
        ;
        $this->applySessionCondition($qb, 'exercise.session', $session);
        /** @var array<int, TrackEExercise> $rows */
        $rows = $qb->getQuery()->getResult();
        $result = [];
        foreach ($rows as $row) {
            $itemViewId = $row->getOrigLpItemViewId();
            if (!isset($result[$itemViewId])) {
                $result[$itemViewId] = $row;
            }
        }

        return $result;
    }

    /** @return array<int, array{label: string, value: string}> */
    private function getGroupOptions(Course $course, ?Session $session, bool $allowUserGroups): array
    {
        $options = [];
        /** @var array<int, CGroup> $groups */
        $groups = $this->groupRepository->getResourcesByCourse($course, $session)->getQuery()->getResult();
        foreach ($groups as $group) {
            if (null !== $group->getIid()) {
                $options[] = ['label' => $group->getTitle(), 'value' => 'group:'.$group->getIid()];
            }
        }

        if ($allowUserGroups) {
            /** @var array<int, Usergroup> $userGroups */
            $userGroups = $this->entityManager->createQueryBuilder()
                ->select('DISTINCT usergroup')
                ->from(Usergroup::class, 'usergroup')
                ->innerJoin('usergroup.courses', 'courseRelation')
                ->andWhere('IDENTITY(courseRelation.course) = :courseId')
                ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
                ->orderBy('usergroup.title', 'ASC')
                ->getQuery()
                ->getResult()
            ;
            foreach ($userGroups as $userGroup) {
                if (null !== $userGroup->getId()) {
                    $options[] = ['label' => $userGroup->getTitle(), 'value' => 'class:'.$userGroup->getId()];
                }
            }
        }

        return $options;
    }

    /** @param array<int, int> $userIds @return array<int, array<int, string>> */
    private function getCourseGroupNames(array $userIds, Course $course): array
    {
        if ([] === $userIds) {
            return [];
        }

        $rows = $this->entityManager->createQueryBuilder()
            ->select('IDENTITY(relation.user) AS userId', 'groupEntity.title AS title')
            ->from(CGroupRelUser::class, 'relation')
            ->innerJoin('relation.group', 'groupEntity')
            ->andWhere('relation.cId = :courseId')
            ->andWhere('IDENTITY(relation.user) IN (:userIds)')
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->setParameter('userIds', $userIds, ArrayParameterType::INTEGER)
            ->orderBy('groupEntity.title', 'ASC')
            ->getQuery()
            ->getArrayResult()
        ;
        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['userId']][] = (string) $row['title'];
        }

        return $result;
    }

    /** @param array<int, int> $userIds @return array<int, array<int, string>> */
    private function getClassNames(array $userIds): array
    {
        if ([] === $userIds || !$this->settingEnabled('lp.allow_lp_subscription_to_usergroups')) {
            return [];
        }

        $rows = $this->entityManager->createQueryBuilder()
            ->select('IDENTITY(relation.user) AS userId', 'usergroup.title AS title')
            ->from(UsergroupRelUser::class, 'relation')
            ->innerJoin('relation.usergroup', 'usergroup')
            ->andWhere('IDENTITY(relation.user) IN (:userIds)')
            ->setParameter('userIds', $userIds, ArrayParameterType::INTEGER)
            ->orderBy('usergroup.title', 'ASC')
            ->getQuery()
            ->getArrayResult()
        ;
        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['userId']][] = (string) $row['title'];
        }

        return $result;
    }

    /** @param array<int, int> $userIds @return array<int, int> */
    private function getAccessCompletionTimes(
        CLp $lp,
        Course $course,
        ?Session $session,
        array $userIds,
    ): array {
        if ([] === $userIds) {
            return [];
        }

        $rows = $this->entityManager->getConnection()->executeQuery(
            'SELECT user_id, MAX(date_reg) AS last_connection
             FROM track_e_access_complete
             WHERE tool = :tool
               AND tool_id = :toolId
               AND c_id = :courseId
               AND session_id = :sessionId
               AND action = :action
               AND login_as = 0
               AND user_id IN (:userIds)
             GROUP BY user_id',
            [
                'tool' => 'learnpath',
                'toolId' => (int) $lp->getIid(),
                'courseId' => (int) $course->getId(),
                'sessionId' => (int) ($session?->getId() ?? 0),
                'action' => 'view',
                'userIds' => $userIds,
            ],
            [
                'tool' => Types::STRING,
                'toolId' => Types::INTEGER,
                'courseId' => Types::INTEGER,
                'sessionId' => Types::INTEGER,
                'action' => Types::STRING,
                'userIds' => ArrayParameterType::INTEGER,
            ],
        )->fetchAllAssociative();

        $result = [];
        foreach ($rows as $row) {
            $lastConnection = trim((string) ($row['last_connection'] ?? ''));
            if ('' === $lastConnection) {
                continue;
            }

            $timestamp = strtotime($lastConnection.' UTC');
            if (false !== $timestamp) {
                $result[(int) $row['user_id']] = $timestamp;
            }
        }

        return $result;
    }

    private function minimumTimeAvailable(int $courseId, ?int $sessionId): bool
    {
        if (!$this->settingEnabled('lp.lp_minimum_time')) {
            return false;
        }

        $itemType = null !== $sessionId ? ExtraField::SESSION_FIELD_TYPE : ExtraField::COURSE_FIELD_TYPE;
        $value = $this->extraFieldValuesRepository->getValueByVariableAndItem(
            'new_tracking_system',
            $sessionId ?? $courseId,
            $itemType,
        );

        return $value instanceof ExtraFieldValues && 1 === (int) $value->getFieldValue();
    }

    private function settingEnabled(string $name): bool
    {
        $value = $this->settingsManager->getSetting($name);
        if (\is_bool($value)) {
            return $value;
        }

        return \in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }

    private function applySessionCondition(QueryBuilder $qb, string $field, ?Session $session): void
    {
        if (null === $session) {
            $qb->andWhere($field.' IS NULL');

            return;
        }

        $qb->andWhere('IDENTITY('.$field.') = :sessionId')
            ->setParameter('sessionId', (int) $session->getId(), Types::INTEGER)
        ;
    }

    /** @return array{timeSeconds: int, progress: int, score: float|null, lastConnection: int|null} */
    private function emptyMetrics(): array
    {
        return [
            'timeSeconds' => 0,
            'progress' => 0,
            'score' => null,
            'lastConnection' => null,
        ];
    }

    private function buildGeneralReportingUrl(Course $course, ?Session $session, int $userId): string
    {
        return '/main/my_space/myStudents.php?'.http_build_query([
            'details' => 'true',
            'cid' => (int) $course->getId(),
            'sid' => (int) ($session?->getId() ?? 0),
            'course' => $course->getCode(),
            'origin' => 'tracking_course',
            'student' => $userId,
        ]);
    }

    private function buildExerciseResultUrl(
        TrackEExercise $exercise,
        Course $course,
        ?Session $session,
        ?CGroup $group,
    ): string {
        return '/main/exercise/exercise_show.php?'.http_build_query([
            'id' => $exercise->getExeId(),
            'origin' => 'correct_exercise_in_lp',
            'cid' => (int) $course->getId(),
            'sid' => (int) ($session?->getId() ?? 0),
            'gid' => (int) ($group?->getIid() ?? 0),
        ]);
    }
}
