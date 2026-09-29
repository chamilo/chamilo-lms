<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseClass;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\Usergroup;
use Chamilo\CoreBundle\Entity\UsergroupRelCourse;
use Chamilo\CoreBundle\Entity\UsergroupRelSession;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Repository\Node\UsergroupRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use UserGroupModel;

final readonly class CourseClassManager
{
    public const string VIEW_AVAILABLE = 'not_registered';
    public const string VIEW_REGISTERED = 'registered';

    public function __construct(
        private CidReqHelper $cidReqHelper,
        private EntityManagerInterface $entityManager,
        private Security $security,
        private SettingsManager $settingsManager,
        private UsergroupRepository $usergroupRepository,
        private AccessUrlHelper $accessUrlHelper,
    ) {}

    /**
     * @return array{0: Course, 1: Session|null}
     */
    public function resolveContext(): array
    {
        $course = $this->cidReqHelper->requireDoctrineCourseEntity();

        $session = $this->cidReqHelper->getDoctrineSessionEntity();
        if ($session instanceof Session && !$session->hasCourse($course)) {
            throw new AccessDeniedHttpException('The requested session does not contain the current course.');
        }

        return [$course, $session];
    }

    public function assertCanManage(Course $course, ?Session $session): void
    {
        if ($this->canManage($course, $session)) {
            return;
        }

        throw new AccessDeniedHttpException('You are not allowed to manage classes in this course context.');
    }

    public function canManage(Course $course, ?Session $session): bool
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        if (!$this->isEnabled(
            $this->settingsManager->getSetting('workflows.allow_user_course_subscription_by_course_admin', true),
        )) {
            return false;
        }

        if ($session instanceof Session && $this->isEnabled(
            $this->settingsManager->getSetting('session.session_classes_tab_disable', true),
        )) {
            return false;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return false;
        }

        if ($course->hasUserAsTeacher($user) || $this->security->isGranted('ROLE_CURRENT_COURSE_TEACHER')) {
            return true;
        }

        if (!$session instanceof Session) {
            return false;
        }

        return $session->hasUserAsGeneralCoach($user)
            || $session->hasCourseCoachInCourse($user, $course)
            || $this->security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER');
    }

    /**
     * @return array<string, mixed>
     */
    public function getListData(Request $request): array
    {
        [$course, $session] = $this->resolveContext();
        $this->assertCanManage($course, $session);

        $view = self::VIEW_AVAILABLE === (string) $request->query->get('view')
            ? self::VIEW_AVAILABLE
            : self::VIEW_REGISTERED;
        $groupFilter = $request->query->getInt('groupFilter', Usergroup::NORMAL_CLASS);
        if (!\in_array($groupFilter, [-1, Usergroup::NORMAL_CLASS, Usergroup::SOCIAL_CLASS], true)) {
            $groupFilter = Usergroup::NORMAL_CLASS;
        }

        $registeredGroups = $session instanceof Session
            ? $this->usergroupRepository->findBySession($session, true)
            : $this->usergroupRepository->findByCourse($course, true);
        $registeredIds = array_map(
            static fn (Usergroup $group): int => (int) $group->getId(),
            $registeredGroups,
        );

        $groups = self::VIEW_REGISTERED === $view
            ? $registeredGroups
            : $this->findAvailableGroups(
                $registeredIds,
                $groupFilter,
                trim((string) $request->query->get('search', '')),
            );

        if (self::VIEW_REGISTERED === $view && -1 !== $groupFilter) {
            $groups = array_values(array_filter(
                $groups,
                static fn (Usergroup $group): bool => $group->getGroupType() === $groupFilter,
            ));
        }

        $items = array_map(
            fn (Usergroup $group): array => $this->normalizeGroup($group, $course, $session, $view),
            $groups,
        );
        $items = $this->sortItems($items, $request);
        $totalItems = \count($items);

        return [
            'items' => $this->paginate($items, $request),
            'totalItems' => $totalItems,
            'courseId' => (int) $course->getId(),
            'sessionId' => $session?->getId(),
            'view' => $view,
            'groupFilter' => $groupFilter,
            'canManage' => true,
            'groupsUrl' => $this->buildLegacyUrl('/main/group/group.php', $course, $session, [
                'gid' => (int) $this->cidReqHelper->getGroupId(),
            ]),
            'information' => self::VIEW_REGISTERED === $view
                ? 'Information: This list shows the classes already linked to this course. '
                    .'Use the add icon above to browse available classes.'
                : 'Information: This list shows the classes that are not yet linked to this course. '
                    .'Use the add action in the detail column to link a class to this course.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getMyClassesData(Request $request): array
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('Authentication is required.');
        }

        $queryBuilder = $this->usergroupRepository->createQueryBuilder('ug')
            ->innerJoin('ug.users', 'membership')
            ->andWhere('membership.user = :userId')
            ->setParameter('userId', (int) $user->getId(), Types::INTEGER)
            ->orderBy('ug.title', 'ASC')
        ;

        if ($this->accessUrlHelper->isMultiple()) {
            $accessUrl = $this->accessUrlHelper->getCurrent();
            if (null !== $accessUrl) {
                $queryBuilder
                    ->innerJoin('ug.urls', 'urlRelation')
                    ->andWhere('urlRelation.url = :accessUrlId')
                    ->setParameter('accessUrlId', (int) $accessUrl->getId(), Types::INTEGER)
                ;
            }
        }

        $requestedId = $request->query->getInt('id');
        if ($requestedId > 0) {
            $queryBuilder
                ->andWhere('ug.id = :requestedId')
                ->setParameter('requestedId', $requestedId, Types::INTEGER)
            ;
        }

        /** @var Usergroup[] $groups */
        $groups = $queryBuilder->getQuery()->getResult();
        $items = array_map(
            static fn (Usergroup $group): array => [
                'id' => (int) $group->getId(),
                'title' => $group->getTitle(),
                'description' => (string) $group->getDescription(),
                'groupType' => $group->getGroupType(),
                'groupTypeLabel' => Usergroup::SOCIAL_CLASS === $group->getGroupType() ? 'Social' : 'Class',
            ],
            $groups,
        );

        return [
            'items' => $items,
            'totalItems' => \count($items),
            'canAddClasses' => [] === $items && $this->security->isGranted('ROLE_ADMIN'),
            'addClassesUrl' => '/main/admin/usergroups.php?action=add',
        ];
    }

    public function add(Usergroup $usergroup, Course $course, ?Session $session): bool
    {
        $this->assertCanManage($course, $session);
        if ($this->isRegistered($usergroup, $course, $session)) {
            return true;
        }

        $legacyManager = new UserGroupModel();
        if ($session instanceof Session) {
            $legacyManager->subscribe_sessions_to_usergroup((int) $usergroup->getId(), [(int) $session->getId()]);
        } else {
            $legacyManager->subscribe_courses_to_usergroup((int) $usergroup->getId(), [(int) $course->getId()], false);
        }

        $this->entityManager->clear();

        return $this->isRegisteredById((int) $usergroup->getId(), $course, $session);
    }

    public function remove(Usergroup $usergroup, Course $course, ?Session $session): bool
    {
        $this->assertCanManage($course, $session);
        if (!$this->isRegistered($usergroup, $course, $session)) {
            return true;
        }

        $legacyManager = new UserGroupModel();
        if ($session instanceof Session) {
            $remainingSessionIds = array_values(array_filter(
                array_map('intval', $legacyManager->get_sessions_by_usergroup((int) $usergroup->getId())),
                static fn (int $sessionId): bool => $sessionId !== (int) $session->getId(),
            ));
            $legacyManager->subscribe_sessions_to_usergroup(
                (int) $usergroup->getId(),
                $remainingSessionIds,
                true,
            );
        } else {
            $legacyManager->unsubscribe_courses_from_usergroup(
                (int) $usergroup->getId(),
                [(int) $course->getId()],
            );
        }

        $this->entityManager->clear();

        return !$this->isRegisteredById((int) $usergroup->getId(), $course, $session);
    }

    public function removeOnly(Usergroup $usergroup, Course $course, ?Session $session): bool
    {
        $this->assertCanManage($course, $session);
        if (!$this->isRegistered($usergroup, $course, $session)) {
            return true;
        }

        if ($session instanceof Session) {
            $relation = $this->entityManager->getRepository(UsergroupRelSession::class)->findOneBy([
                'usergroup' => $usergroup,
                'session' => $session,
            ]);
            if ($relation instanceof UsergroupRelSession) {
                $this->entityManager->remove($relation);
                $this->entityManager->flush();
            }
        } else {
            (new UserGroupModel())->unsubscribeOnlyCoursesFromUsergroup(
                (int) $usergroup->getId(),
                [(int) $course->getId()],
            );
            $this->entityManager->clear();
        }

        return !$this->isRegisteredById((int) $usergroup->getId(), $course, $session);
    }

    public function findAccessibleGroup(int $usergroupId): Usergroup
    {
        if ($usergroupId <= 0) {
            throw new BadRequestHttpException('A valid class id is required.');
        }

        $queryBuilder = $this->usergroupRepository->createQueryBuilder('ug')
            ->andWhere('ug.id = :usergroupId')
            ->setParameter('usergroupId', $usergroupId, Types::INTEGER)
        ;

        if ($this->accessUrlHelper->isMultiple()) {
            $accessUrl = $this->accessUrlHelper->getCurrent();
            if (null !== $accessUrl) {
                $queryBuilder
                    ->innerJoin('ug.urls', 'urlRelation')
                    ->andWhere('urlRelation.url = :accessUrlId')
                    ->setParameter('accessUrlId', (int) $accessUrl->getId(), Types::INTEGER)
                ;
            }
        }

        $usergroup = $queryBuilder->getQuery()->getOneOrNullResult();
        if (!$usergroup instanceof Usergroup) {
            throw new BadRequestHttpException('The requested class was not found.');
        }

        return $usergroup;
    }

    private function isRegistered(Usergroup $usergroup, Course $course, ?Session $session): bool
    {
        if ($session instanceof Session) {
            return null !== $this->entityManager->getRepository(UsergroupRelSession::class)->findOneBy([
                'usergroup' => $usergroup,
                'session' => $session,
            ]);
        }

        return null !== $this->entityManager->getRepository(UsergroupRelCourse::class)->findOneBy([
            'usergroup' => $usergroup,
            'course' => $course,
        ]);
    }

    private function isRegisteredById(int $usergroupId, Course $course, ?Session $session): bool
    {
        if ($session instanceof Session) {
            $count = $this->entityManager->createQueryBuilder()
                ->select('COUNT(relation.id)')
                ->from(UsergroupRelSession::class, 'relation')
                ->andWhere('IDENTITY(relation.usergroup) = :usergroupId')
                ->andWhere('IDENTITY(relation.session) = :sessionId')
                ->setParameter('usergroupId', $usergroupId, Types::INTEGER)
                ->setParameter('sessionId', (int) $session->getId(), Types::INTEGER)
                ->getQuery()
                ->getSingleScalarResult()
            ;

            return (int) $count > 0;
        }

        $count = $this->entityManager->createQueryBuilder()
            ->select('COUNT(relation.id)')
            ->from(UsergroupRelCourse::class, 'relation')
            ->andWhere('IDENTITY(relation.usergroup) = :usergroupId')
            ->andWhere('IDENTITY(relation.course) = :courseId')
            ->setParameter('usergroupId', $usergroupId, Types::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (int) $count > 0;
    }

    /**
     * @param int[] $registeredIds
     *
     * @return Usergroup[]
     */
    private function findAvailableGroups(array $registeredIds, int $groupFilter, string $keyword): array
    {
        $queryBuilder = $this->usergroupRepository->createQueryBuilder('ug');

        if ([] !== $registeredIds) {
            $queryBuilder
                ->andWhere('ug.id NOT IN (:registeredIds)')
                ->setParameter('registeredIds', $registeredIds, ArrayParameterType::INTEGER)
            ;
        }

        if (-1 !== $groupFilter) {
            $queryBuilder
                ->andWhere('ug.groupType = :groupType')
                ->setParameter('groupType', $groupFilter, Types::INTEGER)
            ;
        }

        if ('' !== $keyword) {
            $queryBuilder
                ->andWhere('LOWER(ug.title) LIKE :keyword')
                ->setParameter('keyword', '%'.mb_strtolower($keyword).'%')
            ;
        }

        if ($this->accessUrlHelper->isMultiple()) {
            $accessUrl = $this->accessUrlHelper->getCurrent();
            if (null !== $accessUrl) {
                $queryBuilder
                    ->innerJoin('ug.urls', 'urlRelation')
                    ->andWhere('urlRelation.url = :accessUrlId')
                    ->setParameter('accessUrlId', (int) $accessUrl->getId(), Types::INTEGER)
                ;
            }
        }

        /** @var Usergroup[] $groups */
        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeGroup(Usergroup $group, Course $course, ?Session $session, string $view): array
    {
        $currentUser = $this->security->getUser();
        $currentUserId = $currentUser instanceof User ? (int) $currentUser->getId() : 0;
        $role = $currentUserId > 0
            ? $this->usergroupRepository->getUserGroupRole((int) $group->getId(), $currentUserId)
            : null;
        $memberCount = $this->usergroupRepository->countMembers((int) $group->getId());
        $isAdmin = $this->security->isGranted('ROLE_ADMIN');
        $showStatistics = $isAdmin
            && $currentUserId > 0
            && $group->getAuthorId() === $currentUserId
            && $this->isEnabled($this->settingsManager->getSetting('profile.allow_teachers_to_classes', true));

        $overviewQuery = 'usergroup='.(int) $group->getId()
            .'&course='.(int) $course->getId()
            .'&cid='.(int) $course->getId();
        if ($session instanceof Session) {
            $overviewQuery .= '&sid='.(int) $session->getId();
        }

        return [
            'id' => (int) $group->getId(),
            'title' => $group->getTitle(),
            'users' => $memberCount,
            'status' => $this->roleToString($role),
            'groupType' => $group->getGroupType(),
            'groupTypeLabel' => Usergroup::SOCIAL_CLASS === $group->getGroupType() ? 'Social' : 'Class',
            'isRegistered' => self::VIEW_REGISTERED === $view,
            'canAdd' => self::VIEW_AVAILABLE === $view,
            'canRemove' => self::VIEW_REGISTERED === $view,
            'canRemoveOnly' => self::VIEW_REGISTERED === $view,
            'overviewUrl' => '/user/usergroup_overview?'.$overviewQuery,
            'usersUrl' => $isAdmin ? '/admin/usergroup-users/'.(int) $group->getId() : '',
            'statisticsUrl' => $showStatistics ? '/admin/usergroup-users/'.(int) $group->getId() : '',
        ];
    }

    private function roleToString(?int $role): string
    {
        return match ($role) {
            Usergroup::GROUP_USER_PERMISSION_ADMIN => 'Admin',
            Usergroup::GROUP_USER_PERMISSION_READER => 'Reader',
            Usergroup::GROUP_USER_PERMISSION_PENDING_INVITATION => 'Pending invitation',
            Usergroup::GROUP_USER_PERMISSION_MODERATOR => 'Moderator',
            Usergroup::GROUP_USER_PERMISSION_HRM => 'Human Resources Manager',
            default => '',
        };
    }

    /**
     * @param array<int, array<string, mixed>> $items
     *
     * @return array<int, array<string, mixed>>
     */
    private function sortItems(array $items, Request $request): array
    {
        $sortMap = [
            'title' => 'title',
            'users' => 'users',
            'status' => 'status',
            'groupType' => 'groupType',
        ];
        $sort = $sortMap[(string) $request->query->get('sort', 'title')] ?? 'title';
        $direction = 'desc' === strtolower((string) $request->query->get('order', 'asc')) ? -1 : 1;

        usort($items, static function (array $first, array $second) use ($sort, $direction): int {
            $firstValue = $first[$sort] ?? '';
            $secondValue = $second[$sort] ?? '';

            return $direction * ($firstValue <=> $secondValue);
        });

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

    /**
     * @param array<string, int|string|null> $extra
     */
    private function buildLegacyUrl(string $path, Course $course, ?Session $session, array $extra = []): string
    {
        $query = array_filter([
            'cid' => (int) $course->getId(),
            'sid' => $session?->getId(),
            ...$extra,
        ], static fn (mixed $value): bool => null !== $value && '' !== $value && 0 !== $value);

        return $path.'?'.http_build_query($query);
    }

    private function isEnabled(mixed $value): bool
    {
        if (\is_bool($value)) {
            return $value;
        }

        return \in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }
}
