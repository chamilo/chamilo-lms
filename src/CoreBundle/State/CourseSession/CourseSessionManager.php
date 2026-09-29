<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseSession;

use Chamilo\CoreBundle\Entity\AccessUrlRelUser;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourse;
use Chamilo\CoreBundle\Entity\SessionRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Settings\SettingsManager;
use CourseManager;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use SessionManager;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use UrlManager;
use UserManager;

use const ANONYMOUS;
use const DRH;

final readonly class CourseSessionManager
{
    public const string VIEW_AVAILABLE = 'available';
    public const string VIEW_REGISTERED = 'registered';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
        private SettingsManager $settingsManager,
        private AccessUrlHelper $accessUrlHelper,
    ) {}

    public function assertFeatureEnabled(): void
    {
        if ($this->isEnabled(
            $this->settingsManager->getSetting('allow_tutors_to_assign_students_to_session', true),
        )) {
            return;
        }

        throw new AccessDeniedHttpException('Session user management is disabled by platform configuration.');
    }

    public function assertCanManage(Session $session): void
    {
        $this->assertFeatureEnabled();

        if ($this->canManage($session)) {
            return;
        }

        throw new AccessDeniedHttpException('You are not allowed to manage this session.');
    }

    public function canManage(Session $session): bool
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return $this->sessionBelongsToCurrentUrl($session);
        }

        $user = $this->security->getUser();
        if (!$user instanceof User || !$this->sessionBelongsToCurrentUrl($session)) {
            return false;
        }

        if (api_is_session_admin() && $this->isEnabled(
            $this->settingsManager->getSetting('session.allow_session_admins_to_manage_all_sessions', true),
        )) {
            return true;
        }

        return $session->hasUserAsSessionAdmin($user)
            || $session->hasUserAsGeneralCoach($user)
            || $session->hasCourseCoachInCourse($user);
    }

    public function canManageUserCourses(Session $session): bool
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        $user = $this->security->getUser();

        return $user instanceof User && $session->hasUserAsSessionAdmin($user);
    }

    public function findManagedSession(int $sessionId): Session
    {
        if ($sessionId <= 0) {
            throw new BadRequestHttpException('A valid session id is required.');
        }

        $session = $this->entityManager->getRepository(Session::class)->find($sessionId);
        if (!$session instanceof Session) {
            throw new BadRequestHttpException('The requested session was not found.');
        }

        $this->assertCanManage($session);

        return $session;
    }

    /**
     * @return array<string, mixed>
     */
    public function getListData(Request $request): array
    {
        $this->assertFeatureEnabled();

        $nameFilter = mb_strtolower(trim((string) $request->query->get('name', $request->query->get('search', ''))));
        $coursesFilter = trim((string) $request->query->get('courses', ''));
        $usersFilter = trim((string) $request->query->get('users', ''));
        $categoryFilter = mb_strtolower(trim((string) $request->query->get('category', '')));
        $startDateFilter = mb_strtolower(trim((string) $request->query->get('startDate', '')));
        $endDateFilter = mb_strtolower(trim((string) $request->query->get('endDate', '')));
        $activeFilter = $request->query->getInt('active', 1);
        if (!\in_array($activeFilter, [-1, 0, 1], true)) {
            $activeFilter = 1;
        }

        $items = [];
        foreach ($this->getManagedSessions() as $session) {
            $item = $this->normalizeSession($session);
            if (!$this->matchesSessionFilters(
                $item,
                $nameFilter,
                $coursesFilter,
                $usersFilter,
                $categoryFilter,
                $startDateFilter,
                $endDateFilter,
                $activeFilter,
            )) {
                continue;
            }

            $items[] = $item;
        }

        $defaultSort = $this->isEnabled(
            $this->settingsManager->getSetting('session.session_list_order', true),
        ) ? 'position' : 'title';
        $items = $this->sortItems($items, $request, [
            'position',
            'title',
            'nbrCourses',
            'nbrUsers',
            'categoryName',
            'accessStartDate',
            'accessEndDate',
            'coachName',
            'active',
            'visibility',
        ], $defaultSort);
        $totalItems = \count($items);

        return [
            'items' => $this->paginate($items, $request),
            'totalItems' => $totalItems,
            'active' => $activeFilter,
            'canCreate' => $this->security->isGranted('ROLE_ADMIN'),
            'createSessionUrl' => '/main/session/session_add.php',
            'addToCategoryUrl' => '/main/session/add_many_session_to_category.php',
            'categoriesUrl' => '/main/session/session_category_list.php',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getOverviewData(int $sessionId): array
    {
        $session = $this->findManagedSession($sessionId);
        $courses = [];
        foreach ($session->getCourses() as $sessionRelCourse) {
            if (!$sessionRelCourse instanceof SessionRelCourse) {
                continue;
            }

            $course = $sessionRelCourse->getCourse();
            $coachNames = [];
            foreach ($session->getSessionRelCourseRelUsersByStatus($course, Session::COURSE_COACH) as $relation) {
                $coachNames[] = UserManager::formatUserFullName($relation->getUser());
            }

            $courses[] = [
                'id' => (int) $course->getId(),
                'title' => $course->getTitle(),
                'code' => $course->getVisualCode(),
                'url' => api_get_course_url((int) $course->getId(), (int) $session->getId()),
                'coaches' => $coachNames,
                'nbrUsers' => $sessionRelCourse->getNbrUsers(),
            ];
        }

        $users = [];
        foreach ($this->getSessionStudentRelations($session) as $relation) {
            $user = $relation->getUser();
            $inCurrentUrl = $this->userBelongsToCurrentUrl($user);
            $users[] = [
                'id' => (int) $user->getId(),
                'fullname' => UserManager::formatUserFullName($user),
                'username' => $user->getUsername(),
                'profileUrl' => '/main/admin/user_information.php?user_id='.(int) $user->getId(),
                'reportingUrl' => '/reporting/learners/'.(int) $user->getId().'?'.http_build_query([
                    'sid' => (int) $session->getId(),
                ]),
                'inCurrentUrl' => $inCurrentUrl,
                'canAddToCurrentUrl' => $this->accessUrlHelper->isMultiple() && !$inCurrentUrl,
                'canManageCourses' => $this->canManageUserCourses($session),
            ];
        }

        usort(
            $users,
            static fn (array $left, array $right): int => strcasecmp(
                (string) $left['fullname'],
                (string) $right['fullname'],
            ),
        );

        $generalCoaches = [];
        foreach ($session->getGeneralCoaches() as $coach) {
            $generalCoaches[] = UserManager::formatUserFullName($coach);
        }

        $urls = [];
        if ($this->accessUrlHelper->isMultiple()) {
            foreach ($session->getUrls() as $relation) {
                $url = $relation->getUrl();
                if (null !== $url) {
                    $urls[] = (string) $url->getUrl();
                }
            }
        }

        return [
            'session' => [
                'id' => (int) $session->getId(),
                'title' => $session->getTitle(),
                'generalCoaches' => $generalCoaches,
                'category' => $session->getCategory()?->getTitle() ?? '',
                'duration' => $session->getDuration(),
                'accessDates' => $this->formatDateRange(
                    $session->getAccessStartDate(),
                    $session->getAccessEndDate(),
                ),
                'coachDates' => $this->formatDateRange(
                    $session->getCoachAccessStartDate(),
                    $session->getCoachAccessEndDate(),
                ),
                'visibility' => SessionManager::getSessionVisibility($session),
                'urls' => $urls,
            ],
            'courses' => $courses,
            'users' => $users,
            'canManageUsers' => true,
            'canManageUserCourses' => $this->canManageUserCourses($session),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getUsersData(Request $request, int $sessionId): array
    {
        $session = $this->findManagedSession($sessionId);
        $view = self::VIEW_AVAILABLE === (string) $request->query->get('view')
            ? self::VIEW_AVAILABLE
            : self::VIEW_REGISTERED;
        $keyword = trim((string) $request->query->get('search', ''));
        $scope = 'no_session' === (string) $request->query->get('scope') ? 'no_session' : 'all';
        $firstLetter = mb_strtoupper(trim((string) $request->query->get('firstLetter', '')));
        if (1 !== mb_strlen($firstLetter) || 1 !== preg_match('/^[[:alpha:]]$/u', $firstLetter)) {
            $firstLetter = '';
        }

        $profilingFields = $this->getProfilingFields();
        $extraFilters = [];
        foreach ($profilingFields as $field) {
            $variable = (string) ($field['variable'] ?? '');
            if ('' === $variable) {
                continue;
            }

            $value = trim((string) $request->query->get('field_'.$variable, ''));
            if ('' !== $value && '0' !== $value) {
                $extraFilters[$variable] = $value;
            }
        }

        $queryBuilder = $this->createUserListQuery(
            $session,
            $view,
            $scope,
            $keyword,
            self::VIEW_AVAILABLE === $view ? $firstLetter : '',
            $extraFilters,
        );
        $totalItems = $this->countUserListQuery($queryBuilder);
        $this->applyUserListOrder($queryBuilder, $request);
        $this->applyPagination($queryBuilder, $request);

        /** @var User[] $userList */
        $userList = $queryBuilder->getQuery()->getResult();
        $items = array_map(
            fn (User $user): array => $this->normalizeUser($user),
            $userList,
        );

        return [
            'items' => $items,
            'totalItems' => $totalItems,
            'view' => $view,
            'scope' => $scope,
            'sessionId' => (int) $session->getId(),
            'profilingFields' => $profilingFields,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getUserCoursesData(int $sessionId, int $userId): array
    {
        $session = $this->findManagedSession($sessionId);
        if (!$this->canManageUserCourses($session)) {
            throw new AccessDeniedHttpException('You are not allowed to edit session courses for this user.');
        }

        $user = $this->findSessionStudent($session, $userId);
        $avoidedIds = array_map(
            'intval',
            SessionManager::getAvoidedCoursesInSession($user, $session),
        );
        $courses = [];

        foreach ($session->getCourses() as $relation) {
            if (!$relation instanceof SessionRelCourse) {
                continue;
            }

            $course = $relation->getCourse();
            $courses[] = [
                'id' => (int) $course->getId(),
                'title' => $course->getTitle(),
                'code' => $course->getCode(),
                'avoided' => \in_array((int) $course->getId(), $avoidedIds, true),
            ];
        }

        return [
            'sessionId' => (int) $session->getId(),
            'sessionTitle' => $session->getTitle(),
            'user' => [
                'id' => (int) $user->getId(),
                'fullname' => UserManager::formatUserFullName($user),
                'username' => $user->getUsername(),
            ],
            'courses' => $courses,
        ];
    }

    /**
     * @param int[] $userIds
     */
    public function subscribeUsers(int $sessionId, array $userIds): bool
    {
        $session = $this->findManagedSession($sessionId);
        $validUserIds = [];

        foreach (array_values(array_unique(array_map('intval', $userIds))) as $userId) {
            if ($userId <= 0) {
                continue;
            }

            $user = $this->entityManager->getRepository(User::class)->find($userId);
            if (!$user instanceof User
                || User::SOFT_DELETED === $user->getActive()
                || ANONYMOUS === $user->getStatus()
                || DRH === $user->getStatus()
                || !$this->userBelongsToCurrentUrl($user)
            ) {
                throw new AccessDeniedHttpException('One or more selected users cannot be subscribed to this session.');
            }

            $validUserIds[] = $userId;
        }

        if ([] === $validUserIds) {
            throw new BadRequestHttpException('At least one valid user is required.');
        }

        $this->assertUsersPerCourseLimits($session, $validUserIds);

        return SessionManager::subscribeUsersToSession(
            (int) $session->getId(),
            $validUserIds,
            null,
            false,
            true,
        );
    }

    /**
     * @param int[] $userIds
     */
    public function unsubscribeUsers(int $sessionId, array $userIds): bool
    {
        $session = $this->findManagedSession($sessionId);
        $processed = false;

        foreach (array_values(array_unique(array_map('intval', $userIds))) as $userId) {
            if ($userId <= 0) {
                continue;
            }

            $this->findSessionStudent($session, $userId);
            SessionManager::unsubscribe_user_from_session((int) $session->getId(), $userId);
            $processed = true;
        }

        if (!$processed) {
            throw new BadRequestHttpException('At least one registered user is required.');
        }

        return true;
    }

    public function addUserToCurrentUrl(int $sessionId, int $userId): bool
    {
        $session = $this->findManagedSession($sessionId);
        $user = $this->findSessionStudent($session, $userId);

        if (!$this->accessUrlHelper->isMultiple()) {
            throw new BadRequestHttpException('The platform is not configured for multiple access URLs.');
        }

        if ($this->userBelongsToCurrentUrl($user)) {
            return true;
        }

        $currentUrl = $this->accessUrlHelper->getCurrent();

        return UrlManager::add_user_to_url((int) $user->getId(), (int) $currentUrl->getId());
    }

    /**
     * @param int[] $avoidedCourseIds
     */
    public function updateUserCourses(int $sessionId, int $userId, array $avoidedCourseIds): bool
    {
        $session = $this->findManagedSession($sessionId);
        if (!$this->canManageUserCourses($session)) {
            throw new AccessDeniedHttpException('You are not allowed to edit session courses for this user.');
        }

        $user = $this->findSessionStudent($session, $userId);
        $sessionCourseIds = [];
        foreach ($session->getCourses() as $relation) {
            if ($relation instanceof SessionRelCourse) {
                $sessionCourseIds[] = (int) $relation->getCourse()->getId();
            }
        }

        $avoidedCourseIds = array_values(array_unique(array_map('intval', $avoidedCourseIds)));
        foreach ($avoidedCourseIds as $courseId) {
            if (!\in_array($courseId, $sessionCourseIds, true)) {
                throw new AccessDeniedHttpException('One or more selected courses do not belong to this session.');
            }
        }

        if ([] !== $sessionCourseIds && \count($avoidedCourseIds) === \count($sessionCourseIds)) {
            throw new UnprocessableEntityHttpException('A user cannot be blocked from every course in the session. Unsubscribe the user instead.');
        }

        $currentAvoidedIds = array_map(
            'intval',
            SessionManager::getAvoidedCoursesInSession($user, $session),
        );

        foreach ($avoidedCourseIds as $courseId) {
            $course = $this->entityManager->getRepository(Course::class)->find($courseId);
            if (!$course instanceof Course || 0 === $session->getUserInCourse($user, $course)->count()) {
                continue;
            }

            $session->removeUserCourseSubscription($user, $course);
        }

        foreach (array_diff($currentAvoidedIds, $avoidedCourseIds) as $courseId) {
            $course = $this->entityManager->getRepository(Course::class)->find((int) $courseId);
            if (!$course instanceof Course || $session->getUserInCourse($user, $course)->count() > 0) {
                continue;
            }

            $session->addUserInCourse(Session::STUDENT, $user, $course);
        }

        $this->entityManager->persist($session);
        $this->entityManager->flush();

        return true;
    }

    /**
     * @return SessionRelUser[]
     */
    private function getSessionStudentRelations(Session $session): array
    {
        $relations = [];
        foreach ($session->getUsers() as $relation) {
            if ($relation instanceof SessionRelUser
                && Session::STUDENT === $relation->getRelationType()
                && User::SOFT_DELETED !== $relation->getUser()->getActive()
            ) {
                $relations[] = $relation;
            }
        }

        return $relations;
    }

    private function findSessionStudent(Session $session, int $userId): User
    {
        foreach ($this->getSessionStudentRelations($session) as $relation) {
            if ((int) $relation->getUser()->getId() === $userId) {
                return $relation->getUser();
            }
        }

        throw new AccessDeniedHttpException('The requested user is not a student in this session.');
    }

    /**
     * @return Session[]
     */
    private function getManagedSessions(): array
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('DISTINCT session')
            ->from(Session::class, 'session')
        ;

        if ($this->accessUrlHelper->isMultiple()) {
            $queryBuilder
                ->innerJoin('session.urls', 'sessionUrl')
                ->andWhere('IDENTITY(sessionUrl.url) = :currentUrlId')
                ->setParameter(
                    'currentUrlId',
                    (int) $this->accessUrlHelper->getCurrent()->getId(),
                    Types::INTEGER,
                )
            ;
        }

        $canManageAll = $this->security->isGranted('ROLE_ADMIN') || (api_is_session_admin() && $this->isEnabled(
            $this->settingsManager->getSetting('session.allow_session_admins_to_manage_all_sessions', true),
        ));

        if (!$canManageAll) {
            $user = $this->security->getUser();
            if (!$user instanceof User) {
                return [];
            }

            $queryBuilder
                ->leftJoin(
                    'session.users',
                    'managementRelation',
                    'WITH',
                    'IDENTITY(managementRelation.user) = :currentUserId'
                    .' AND managementRelation.relationType IN (:managementTypes)',
                )
                ->leftJoin(
                    'session.sessionRelCourseRelUsers',
                    'courseCoachRelation',
                    'WITH',
                    'IDENTITY(courseCoachRelation.user) = :currentUserId'
                    .' AND courseCoachRelation.status = :courseCoachStatus',
                )
                ->andWhere('managementRelation.id IS NOT NULL OR courseCoachRelation.id IS NOT NULL')
                ->setParameter('currentUserId', (int) $user->getId(), Types::INTEGER)
                ->setParameter(
                    'managementTypes',
                    [Session::SESSION_ADMIN, Session::GENERAL_COACH],
                    ArrayParameterType::INTEGER,
                )
                ->setParameter('courseCoachStatus', Session::COURSE_COACH, Types::INTEGER)
            ;
        }

        /** @var Session[] $sessions */
        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param array<string, string> $extraFilters
     */
    private function createUserListQuery(
        Session $session,
        string $view,
        string $scope,
        string $keyword,
        string $firstLetter,
        array $extraFilters,
    ): QueryBuilder {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('DISTINCT user')
            ->from(User::class, 'user')
            ->andWhere('user.active <> :softDeleted')
            ->andWhere('user.status NOT IN (:excludedStatuses)')
            ->setParameter('softDeleted', User::SOFT_DELETED, Types::INTEGER)
            ->setParameter('excludedStatuses', [ANONYMOUS, DRH], ArrayParameterType::INTEGER)
        ;

        if (self::VIEW_REGISTERED === $view) {
            $queryBuilder
                ->innerJoin('user.sessionsRelUser', 'currentSessionRelation')
                ->andWhere('IDENTITY(currentSessionRelation.session) = :sessionId')
                ->andWhere('currentSessionRelation.relationType = :studentRelationType')
            ;
        } else {
            $queryBuilder
                ->leftJoin(
                    'user.sessionsRelUser',
                    'currentSessionRelation',
                    'WITH',
                    'IDENTITY(currentSessionRelation.session) = :sessionId'
                    .' AND currentSessionRelation.relationType = :studentRelationType',
                )
                ->andWhere('currentSessionRelation.id IS NULL')
            ;

            if ('no_session' === $scope) {
                $queryBuilder
                    ->leftJoin('user.sessionsRelUser', 'anySessionRelation')
                    ->andWhere('anySessionRelation.id IS NULL')
                ;
            }

            $extraUserIds = $this->resolveExtraFilterUserIds($extraFilters);
            if (null !== $extraUserIds) {
                if ([] === $extraUserIds) {
                    $queryBuilder->andWhere('1 = 0');
                } else {
                    $queryBuilder
                        ->andWhere('user.id IN (:extraUserIds)')
                        ->setParameter('extraUserIds', $extraUserIds, ArrayParameterType::INTEGER)
                    ;
                }
            }

            if ('' !== $firstLetter) {
                $nameProperty = api_sort_by_first_name() ? 'user.firstname' : 'user.lastname';
                $queryBuilder
                    ->andWhere('LOWER('.$nameProperty.') LIKE :firstLetter')
                    ->setParameter('firstLetter', mb_strtolower($firstLetter).'%', Types::STRING)
                ;
            }
        }

        $queryBuilder
            ->setParameter('sessionId', (int) $session->getId(), Types::INTEGER)
            ->setParameter('studentRelationType', Session::STUDENT, Types::INTEGER)
        ;

        if ($this->accessUrlHelper->isMultiple()) {
            $queryBuilder
                ->innerJoin('user.portals', 'userPortal')
                ->andWhere('IDENTITY(userPortal.url) = :currentUrlId')
                ->setParameter(
                    'currentUrlId',
                    (int) $this->accessUrlHelper->getCurrent()->getId(),
                    Types::INTEGER,
                )
            ;
        }

        $keyword = mb_strtolower(trim($keyword));
        if ('' !== $keyword) {
            $queryBuilder
                ->andWhere(
                    $queryBuilder->expr()->orX(
                        'LOWER(user.firstname) LIKE :keyword',
                        'LOWER(user.lastname) LIKE :keyword',
                        'LOWER(user.username) LIKE :keyword',
                    ),
                )
                ->setParameter('keyword', $keyword.'%', Types::STRING)
            ;
        }

        return $queryBuilder;
    }

    private function countUserListQuery(QueryBuilder $queryBuilder): int
    {
        $countQueryBuilder = clone $queryBuilder;
        $countQueryBuilder
            ->resetDQLPart('orderBy')
            ->select('COUNT(DISTINCT user.id)')
        ;

        return (int) $countQueryBuilder->getQuery()->getSingleScalarResult();
    }

    private function applyUserListOrder(QueryBuilder $queryBuilder, Request $request): void
    {
        $direction = 'desc' === strtolower((string) $request->query->get('order', 'asc')) ? 'DESC' : 'ASC';
        $sort = (string) $request->query->get('sort', 'lastname');

        $sortMap = [
            'firstname' => 'user.firstname',
            'lastname' => 'user.lastname',
            'username' => 'user.username',
            'officialCode' => 'user.officialCode',
        ];
        $property = $sortMap[$sort] ?? (api_sort_by_first_name() ? 'user.firstname' : 'user.lastname');

        $queryBuilder->orderBy($property, $direction);
        if ('user.firstname' === $property) {
            $queryBuilder->addOrderBy('user.lastname', $direction);
        } elseif ('user.lastname' === $property) {
            $queryBuilder->addOrderBy('user.firstname', $direction);
        }
        $queryBuilder->addOrderBy('user.username', 'ASC');
    }

    private function applyPagination(QueryBuilder $queryBuilder, Request $request): void
    {
        $page = max(1, $request->query->getInt('page', 1));
        $itemsPerPage = min(100, max(5, $request->query->getInt('itemsPerPage', 20)));

        $queryBuilder
            ->setFirstResult(($page - 1) * $itemsPerPage)
            ->setMaxResults($itemsPerPage)
        ;
    }

    /**
     * @param array<string, string> $extraFilters
     *
     * @return int[]|null
     */
    private function resolveExtraFilterUserIds(array $extraFilters): ?array
    {
        if ([] === $extraFilters) {
            return null;
        }

        $matchingIds = null;
        foreach ($extraFilters as $variable => $value) {
            $fieldIds = UserManager::get_extra_user_data_by_value($variable, $value);
            $fieldIds = array_values(array_unique(array_filter(array_map(
                'intval',
                \is_array($fieldIds) ? $fieldIds : [],
            ))));
            $matchingIds = null === $matchingIds
                ? $fieldIds
                : array_values(array_intersect($matchingIds, $fieldIds));

            if ([] === $matchingIds) {
                break;
            }
        }

        return $matchingIds ?? [];
    }

    /**
     * @param int[] $userIds
     */
    private function assertUsersPerCourseLimits(Session $session, array $userIds): void
    {
        if (api_is_platform_admin()) {
            return;
        }

        foreach ($session->getCourses() as $relation) {
            if (!$relation instanceof SessionRelCourse) {
                continue;
            }

            $course = $relation->getCourse();
            $courseId = (int) $course->getId();
            if (!CourseManager::wouldOperationExceedUsersPerCourseLimit($courseId, $userIds)) {
                continue;
            }

            throw new UnprocessableEntityHttpException(CourseManager::getUsersPerCourseLimitCancelMessage($courseId).' '.$course->getTitle());
        }
    }

    /**
     * @param array<string, mixed> $item
     */
    private function matchesSessionFilters(
        array $item,
        string $name,
        string $courses,
        string $users,
        string $category,
        string $startDate,
        string $endDate,
        int $active,
    ): bool {
        if ('' !== $name && false === mb_stripos((string) $item['title'], $name)) {
            return false;
        }

        if ('' !== $courses && (!ctype_digit($courses) || (int) $courses !== (int) $item['nbrCourses'])) {
            return false;
        }

        if ('' !== $users && (!ctype_digit($users) || (int) $users !== (int) $item['nbrUsers'])) {
            return false;
        }

        if ('' !== $category && false === mb_stripos((string) $item['categoryName'], $category)) {
            return false;
        }

        if ('' !== $startDate && false === mb_stripos(mb_strtolower((string) $item['accessStartDate']), $startDate)) {
            return false;
        }

        if ('' !== $endDate && false === mb_stripos(mb_strtolower((string) $item['accessEndDate']), $endDate)) {
            return false;
        }

        return -1 === $active || (int) $item['active'] === $active;
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeSession(Session $session): array
    {
        $coachNames = [];
        foreach ($session->getGeneralCoaches() as $coach) {
            $coachNames[] = UserManager::formatUserFullName($coach);
        }

        return [
            'id' => (int) $session->getId(),
            'position' => $session->getPosition(),
            'title' => $session->getTitle(),
            'nbrCourses' => $session->getNbrCourses(),
            'nbrUsers' => $session->getNbrUsers(),
            'categoryName' => $session->getCategory()?->getTitle() ?? '',
            'accessStartDate' => $this->formatDate($session->getAccessStartDate()),
            'accessEndDate' => $this->formatDate($session->getAccessEndDate()),
            'coachName' => implode(', ', $coachNames),
            'active' => $this->isSessionActive($session) ? 1 : 0,
            'visibility' => SessionManager::getSessionVisibility($session),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeUser(User $user): array
    {
        return [
            'id' => (int) $user->getId(),
            'fullname' => UserManager::formatUserFullName($user),
            'firstname' => $user->getFirstname(),
            'lastname' => $user->getLastname(),
            'username' => $user->getUsername(),
            'officialCode' => (string) $user->getOfficialCode(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getProfilingFields(): array
    {
        $fields = UserManager::get_extra_fields();
        $result = [];

        foreach (\is_array($fields) ? $fields : [] as $field) {
            if (!\is_array($field) || 1 !== (int) ($field[8] ?? 0) || 4 !== (int) ($field[2] ?? 0)) {
                continue;
            }

            $fieldId = (int) ($field[0] ?? 0);
            $variable = (string) ($field[1] ?? '');
            if ($fieldId <= 0 || '' === $variable) {
                continue;
            }

            $options = [];
            foreach ((array) ($field[9] ?? []) as $option) {
                $value = (string) ($option[1] ?? '');
                if ('' === $value) {
                    continue;
                }

                $options[] = [
                    'value' => $value,
                    'label' => $value,
                ];
            }

            $result[] = [
                'id' => $fieldId,
                'variable' => $variable,
                'label' => (string) ($field[3] ?? $variable),
                'options' => $options,
            ];
        }

        return $result;
    }

    private function sessionBelongsToCurrentUrl(Session $session): bool
    {
        if (!$this->accessUrlHelper->isMultiple()) {
            return true;
        }

        $currentUrlId = (int) $this->accessUrlHelper->getCurrent()->getId();
        foreach ($session->getUrls() as $relation) {
            if ((int) $relation->getUrl()->getId() === $currentUrlId) {
                return true;
            }
        }

        return false;
    }

    private function userBelongsToCurrentUrl(User $user): bool
    {
        if (!$this->accessUrlHelper->isMultiple()) {
            return true;
        }

        $currentUrlId = (int) $this->accessUrlHelper->getCurrent()->getId();

        return $user->getPortals()->exists(
            static fn (int|string $key, AccessUrlRelUser $relation): bool => (int) $relation->getUrl()->getId() === $currentUrlId,
        );
    }

    private function isSessionActive(Session $session): bool
    {
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $start = $session->getAccessStartDate();
        $end = $session->getAccessEndDate();

        return (null === $start || $start <= $now)
            && (null === $end || $end >= $now);
    }

    private function formatDate(?DateTimeInterface $date): string
    {
        if (null === $date) {
            return '';
        }

        return api_get_local_time($date->format('Y-m-d H:i:s'));
    }

    private function formatDateRange(?DateTimeInterface $start, ?DateTimeInterface $end): string
    {
        if (null === $start && null === $end) {
            return '';
        }

        return trim($this->formatDate($start).' - '.$this->formatDate($end), ' -');
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @param string[]                         $allowedFields
     *
     * @return array<int, array<string, mixed>>
     */
    private function sortItems(
        array $items,
        Request $request,
        array $allowedFields,
        string $defaultField = '',
    ): array {
        $defaultField = \in_array($defaultField, $allowedFields, true) ? $defaultField : $allowedFields[0];
        $sort = (string) $request->query->get('sort', $defaultField);
        if (!\in_array($sort, $allowedFields, true)) {
            $sort = $defaultField;
        }

        $direction = 'desc' === strtolower((string) $request->query->get('order', 'asc')) ? -1 : 1;
        usort(
            $items,
            static fn (array $left, array $right): int => $direction * strnatcasecmp(
                (string) ($left[$sort] ?? ''),
                (string) ($right[$sort] ?? ''),
            ),
        );

        return $items;
    }

    /**
     * @param array<int, array<string, mixed>> $items
     *
     * @return array<int, array<string, mixed>>
     */
    private function paginate(array $items, Request $request): array
    {
        $page = max(1, $request->query->getInt('page', 1));
        $itemsPerPage = min(100, max(5, $request->query->getInt('itemsPerPage', 20)));

        return array_values(\array_slice($items, ($page - 1) * $itemsPerPage, $itemsPerPage));
    }

    private function isEnabled(mixed $value): bool
    {
        if (\is_bool($value)) {
            return $value;
        }

        return \in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }
}
