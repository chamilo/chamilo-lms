<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseUser;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\ExtraField as ExtraFieldEntity;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\CourseHelper;
use Chamilo\CoreBundle\Repository\Node\IllustrationRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use CourseManager;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use ExtraField;
use ExtraFieldOption;
use ExtraFieldValue;
use GroupManager;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final readonly class CourseUserManager
{
    private const int UPGRADE_CTA_REMAINING_USERS_THRESHOLD = 5;

    public const int TYPE_TEACHER = CourseRelUser::TEACHER;
    public const int TYPE_STUDENT = CourseRelUser::STUDENT;

    public function __construct(
        private CidReqHelper $cidReqHelper,
        private EntityManagerInterface $entityManager,
        private Security $security,
        private SettingsManager $settingsManager,
        private IllustrationRepository $illustrationRepository,
        private AccessUrlHelper $accessUrlHelper,
        private CourseHelper $courseHelper,
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

    public function normalizeType(Request $request): int
    {
        return self::TYPE_TEACHER === $request->query->getInt('type')
            ? self::TYPE_TEACHER
            : self::TYPE_STUDENT;
    }

    public function assertCanRead(Course $course, ?Session $session): void
    {
        if ($this->canRead($course, $session)) {
            return;
        }

        throw new AccessDeniedHttpException('You are not allowed to view course users in this context.');
    }

    public function assertCanManage(Course $course, ?Session $session): void
    {
        if ($this->canManage($course, $session)) {
            return;
        }

        throw new AccessDeniedHttpException('You are not allowed to manage course users in this context.');
    }

    public function canRead(Course $course, ?Session $session): bool
    {
        if ($this->security->isGranted('ROLE_ADMIN') || $this->canManage($course, $session)) {
            return true;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return false;
        }

        if (3 === (int) $course->getVisibility()
            && $this->isEnabled($this->settingsManager->getSetting('privacy.disable_change_user_visibility_for_public_courses', true))
        ) {
            return false;
        }

        $isMember = null === $session
            ? $course->hasSubscriptionByUser($user)
            : $session->hasUserInCourse($user, $course);

        return $isMember && 1 === $this->getCourseSettingAsInt($course, 'allow_user_view_user_list');
    }

    public function canManage(Course $course, ?Session $session): bool
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
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

    public function canSubscribe(Course $course, ?Session $session): bool
    {
        return $this->canManage($course, $session)
            && ($this->security->isGranted('ROLE_ADMIN') || $this->isEnabled(
                $this->settingsManager->getSetting('workflows.allow_user_course_subscription_by_course_admin', true),
            ));
    }

    public function canShowSubscriptionTabs(Course $course, ?Session $session): bool
    {
        return $this->canSubscribe($course, $session);
    }

    /**
     * Whether the current user may open the "Invite by email" course-invitation UI.
     * Same rules as CourseInvitationAccessHelperTrait::canManageCourseInvitations:
     * course teachers outside sessions; only platform/session admins or general coaches
     * inside a session (because an invitation there joins the whole session).
     */
    public function canInviteByEmail(Course $course, ?Session $session): bool
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return false;
        }

        if (!$session instanceof Session) {
            return $course->hasUserAsTeacher($user)
                || $this->security->isGranted('ROLE_CURRENT_COURSE_TEACHER');
        }

        return $user->isSessionAdmin() || $session->hasUserAsGeneralCoach($user);
    }

    public function canUnsubscribe(Course $course, ?Session $session): bool
    {
        return $this->canSubscribe($course, $session);
    }

    public function canSelfUnsubscribe(Course $course, ?Session $session): bool
    {
        return null === $session && $course->getUnsubscribe();
    }

    public function canSetTutor(Course $course, ?Session $session): bool
    {
        return null === $session && $this->canManage($course, $session);
    }

    public function canManageClasses(Course $course, ?Session $session): bool
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        if (!$this->canSubscribe($course, $session)) {
            return false;
        }

        return !$session instanceof Session || !$this->isEnabled(
            $this->settingsManager->getSetting('session.session_classes_tab_disable', true),
        );
    }

    public function getLimitWarning(Course $course, ?Session $session): string
    {
        return $this->getUsersPerCourseLimitState($course, $session)['warning'];
    }

    /**
     * @return array{warning: string, reached: bool, showUpgradeCta: bool}
     */
    private function getUsersPerCourseLimitState(Course $course, ?Session $session): array
    {
        $state = [
            'warning' => '',
            'reached' => false,
            'showUpgradeCta' => false,
        ];

        if ($session instanceof Session || !$this->canManage($course, $session)) {
            return $state;
        }

        $courseId = (int) $course->getId();
        $limit = CourseManager::getEffectiveUsersPerCourseLimit($courseId);
        if ($limit <= 0) {
            return $state;
        }

        $count = CourseManager::countStudentsForUsersPerCourseLimit($courseId);
        $state['reached'] = $count >= $limit;

        $warningFrom = max(0, $limit - self::UPGRADE_CTA_REMAINING_USERS_THRESHOLD);
        $state['showUpgradeCta'] = $count >= $warningFrom
            && $this->courseHelper->shouldOfferBuyCoursesHostingLimitUpgrade($course);

        if ($state['reached']) {
            $state['warning'] = CourseManager::getUsersPerCourseLimitCancelMessage($courseId);

            return $state;
        }

        if ($state['showUpgradeCta']) {
            $state['warning'] = \sprintf(
                get_lang('This course is close to its user subscription limit (%d/%d).'),
                $count,
                $limit,
            );
        }

        return $state;
    }

    /**
     * @return array<string, mixed>
     */
    public function getListData(Request $request): array
    {
        [$course, $session] = $this->resolveContext();
        $this->assertCanRead($course, $session);

        $type = $this->normalizeType($request);
        $status = $session instanceof Session
            ? (self::TYPE_TEACHER === $type ? Session::COURSE_COACH : Session::STUDENT)
            : $type;
        $active = $this->resolveActiveFilter($request);
        $users = CourseManager::get_user_list_from_course_code(
            $course->getCode(),
            $session?->getId() ?? 0,
            null,
            null,
            $status,
            null,
            false,
            false,
            [],
            [],
            [],
            $active,
        );

        $items = [];
        $keyword = trim((string) $request->query->get('search', ''));
        $canManage = $this->canManage($course, $session);
        $extraFields = $canManage ? $this->getFilteredExtraFields() : [];

        foreach ($users as $userId => $userData) {
            $userId = (int) ($userData['user_id'] ?? $userId);
            $user = $this->entityManager->getRepository(User::class)->find($userId);
            if (!$user instanceof User || !$this->matchesKeyword($user, $keyword)) {
                continue;
            }

            $items[] = $this->normalizeMember($course, $session, $type, $user, $userData, $extraFields);
        }

        $items = $this->sortItems($items, $request);
        $totalItems = \count($items);
        $items = $this->paginate($items, $request);
        $currentUser = $this->security->getUser();
        $currentUserId = $currentUser instanceof User ? (int) $currentUser->getId() : 0;
        $limitState = self::TYPE_STUDENT === $type
            ? $this->getUsersPerCourseLimitState($course, $session)
            : ['warning' => '', 'reached' => false, 'showUpgradeCta' => false];

        return [
            'items' => $items,
            'totalItems' => $totalItems,
            'courseId' => (int) $course->getId(),
            'sessionId' => $session?->getId(),
            'type' => $type,
            'canManage' => $canManage,
            'canSubscribe' => $this->canSubscribe($course, $session) && !$limitState['reached'],
            'canUnsubscribe' => $this->canUnsubscribe($course, $session),
            'canImport' => $canManage && $this->canUnsubscribe($course, $session),
            'canSetTutor' => $this->canSetTutor($course, $session),
            'canSelfUnsubscribe' => $this->canSelfUnsubscribe($course, $session),
            'currentUserId' => $currentUserId,
            'extraFields' => array_map(
                static fn (array $field): array => [
                    'id' => (int) $field['id'],
                    'label' => (string) ($field['display_text'] ?? $field['variable'] ?? ''),
                    'variable' => (string) ($field['variable'] ?? ''),
                ],
                $extraFields,
            ),
            'hiddenFields' => $this->getHiddenFields(),
            'showEmail' => $canManage
                && $this->isEnabled($this->settingsManager->getSetting('show_email_addresses', true)),
            'westernNameOrder' => api_is_western_name_order(),
            'showLegalAgreement' => $canManage && 1 === (int) $course->getActivateLegal(),
            'warning' => $limitState['warning'],
            'showUpgradeCta' => $limitState['showUpgradeCta'],
            'sessionManagementUrl' => $this->buildLegacyUrl('/main/user/session_list.php', $course, $session),
            'showSessionManagement' => $canManage && $this->isEnabled(
                $this->settingsManager->getSetting('allow_tutors_to_assign_students_to_session', true),
            ),
            'showClasses' => $this->canManageClasses($course, $session),
            'showSubscriptionTabs' => $this->canShowSubscriptionTabs($course, $session),
            'canInviteByEmail' => $this->canInviteByEmail($course, $session),
            'groupsUrl' => $this->buildLegacyUrl('/main/group/group.php', $course, $session, [
                'gid' => (int) $this->cidReqHelper->getGroupId(),
            ]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getAvailableData(Request $request): array
    {
        [$course, $session] = $this->resolveContext();
        $this->assertCanManage($course, $session);
        if (!$this->canSubscribe($course, $session)) {
            throw new AccessDeniedHttpException('Course user subscription is disabled for the current manager.');
        }

        $type = $this->normalizeType($request);
        $keyword = trim((string) $request->query->get('search', ''));
        $extraFieldId = $request->query->getInt('extraFieldId');
        $extraFieldValue = trim((string) $request->query->get('extraFieldValue', ''));
        $page = max(1, $request->query->getInt('page', 1));
        $itemsPerPage = min(100, max(5, $request->query->getInt('itemsPerPage', 20)));
        $showEmail = $this->isEnabled($this->settingsManager->getSetting('show_email_addresses', true));

        $qb = $this->createAvailableUsersQueryBuilder(
            $course,
            $session,
            $type,
            $keyword,
            $extraFieldId,
            $extraFieldValue,
        );

        $totalItems = (int) (clone $qb)
            ->select('COUNT(DISTINCT u.id)')
            ->getQuery()
            ->getSingleScalarResult()
        ;

        [$sortField, $sortDirection] = $this->resolveAvailableSort($request);
        $qb
            ->select('u')
            ->orderBy($sortField, $sortDirection)
        ;
        if ('u.firstname' === $sortField) {
            $qb->addOrderBy('u.lastname', $sortDirection);
        } elseif ('u.lastname' === $sortField) {
            $qb->addOrderBy('u.firstname', $sortDirection);
        }
        $users = $qb
            ->addOrderBy('u.id', 'ASC')
            ->setFirstResult(($page - 1) * $itemsPerPage)
            ->setMaxResults($itemsPerPage)
            ->getQuery()
            ->getResult()
        ;

        $items = [];
        foreach ($users as $user) {
            if (!$user instanceof User) {
                continue;
            }

            $items[] = [
                'id' => (int) $user->getId(),
                'officialCode' => (string) $user->getOfficialCode(),
                'firstname' => (string) $user->getFirstname(),
                'lastname' => (string) $user->getLastname(),
                'fullName' => trim($user->getFullName()),
                'username' => $user->getUsername(),
                'email' => $showEmail ? $user->getEmail() : '',
                'active' => $user->isActive(),
                'pictureUrl' => trim((string) $this->illustrationRepository->getIllustrationUrl($user)),
            ];
        }

        $limitState = self::TYPE_STUDENT === $type
            ? $this->getUsersPerCourseLimitState($course, $session)
            : ['warning' => '', 'reached' => false, 'showUpgradeCta' => false];

        return [
            'items' => $items,
            'totalItems' => $totalItems,
            'courseId' => (int) $course->getId(),
            'sessionId' => $session?->getId(),
            'type' => $type,
            'canManage' => true,
            'showSubscriptionTabs' => $this->canShowSubscriptionTabs($course, $session),
            'showClasses' => $this->canManageClasses($course, $session),
            'canInviteByEmail' => $this->canInviteByEmail($course, $session),
            'groupsUrl' => $this->buildLegacyUrl('/main/group/group.php', $course, $session, [
                'gid' => (int) $this->cidReqHelper->getGroupId(),
            ]),
            'canSubscribe' => $this->canSubscribe($course, $session)
                && (self::TYPE_TEACHER === $type || !$limitState['reached']),
            'showEmail' => $showEmail,
            'westernNameOrder' => api_is_western_name_order(),
            'extraFields' => $this->getProfilingFields(),
            'warning' => $limitState['warning'],
            'showUpgradeCta' => $limitState['showUpgradeCta'],
        ];
    }

    /**
     * Builds a SQL-level filter for users available to subscribe.
     * Pagination, sorting, keyword search and membership exclusion all happen in the database
     * so large portals (5k+ users) do not load every row into PHP memory.
     */
    private function createAvailableUsersQueryBuilder(
        Course $course,
        ?Session $session,
        int $type,
        string $keyword,
        int $extraFieldId,
        string $extraFieldValue,
    ): QueryBuilder {
        $qb = $this->entityManager->createQueryBuilder()
            ->from(User::class, 'u')
            ->andWhere('u.active != :softDeleted')
            ->setParameter('softDeleted', User::SOFT_DELETED, Types::INTEGER)
        ;

        // Role checks always use the persisted roles JSON, never user.status.
        $this->applyPersistedPlatformRoleFilter($qb);

        if ($this->accessUrlHelper->isMultiple()) {
            $currentUrl = $this->accessUrlHelper->getCurrent();
            if (null !== $currentUrl) {
                $qb->innerJoin('u.portals', 'p')
                    ->andWhere('p.url = :currentUrlId')
                    ->setParameter('currentUrlId', (int) $currentUrl->getId(), Types::INTEGER)
                ;
            }
        }

        $courseId = (int) $course->getId();
        if ($session instanceof Session) {
            $qb->andWhere('NOT EXISTS (
                SELECT 1
                FROM '.SessionRelCourseRelUser::class.' scru
                WHERE scru.user = u
                  AND scru.course = :courseId
                  AND scru.session = :sessionId
            )')
                ->setParameter('courseId', $courseId, Types::INTEGER)
                ->setParameter('sessionId', (int) $session->getId(), Types::INTEGER)
            ;
        } else {
            $qb->andWhere('NOT EXISTS (
                SELECT 1
                FROM '.CourseRelUser::class.' cru
                WHERE cru.user = u
                  AND cru.course = :courseId
            )')
                ->setParameter('courseId', $courseId, Types::INTEGER)
            ;
        }

        if (self::TYPE_TEACHER === $type) {
            $this->applyTeacherRoleFilter($qb);
        }

        if (self::TYPE_STUDENT === $type || $session instanceof Session) {
            $qb->andWhere('u.officialCode IS NULL OR UPPER(u.officialCode) != :adminCode')
                ->setParameter('adminCode', 'ADMIN', Types::STRING)
            ;
        }

        $this->applyAvailableKeywordFilter($qb, $keyword);
        $this->applyAvailableExtraFieldFilter($qb, $extraFieldId, $extraFieldValue);

        return $qb;
    }

    /**
     * Keep only users that have a real platform role in the roles column.
     * System accounts (anonymous / fallback) typically have an empty roles array or ROLE_ANONYMOUS.
     */
    private function applyPersistedPlatformRoleFilter(QueryBuilder $qb): void
    {
        $qb->andWhere('u.roles NOT LIKE :roleAnonymous')
            ->andWhere(
                $qb->expr()->orX(
                    'u.roles LIKE :roleStudent',
                    'u.roles LIKE :roleTeacher',
                    'u.roles LIKE :roleAdmin',
                    'u.roles LIKE :roleGlobalAdmin',
                    'u.roles LIKE :roleSessionManager',
                    'u.roles LIKE :roleHr',
                    'u.roles LIKE :roleStudentBoss',
                    'u.roles LIKE :roleInvitee',
                    'u.roles LIKE :roleQuestionManager',
                )
            )
            ->setParameter('roleAnonymous', '%"ROLE_ANONYMOUS"%', Types::STRING)
            ->setParameter('roleStudent', '%"ROLE_STUDENT"%', Types::STRING)
            ->setParameter('roleTeacher', '%"ROLE_TEACHER"%', Types::STRING)
            ->setParameter('roleAdmin', '%"ROLE_ADMIN"%', Types::STRING)
            ->setParameter('roleGlobalAdmin', '%"ROLE_GLOBAL_ADMIN"%', Types::STRING)
            ->setParameter('roleSessionManager', '%"ROLE_SESSION_MANAGER"%', Types::STRING)
            ->setParameter('roleHr', '%"ROLE_HR"%', Types::STRING)
            ->setParameter('roleStudentBoss', '%"ROLE_STUDENT_BOSS"%', Types::STRING)
            ->setParameter('roleInvitee', '%"ROLE_INVITEE"%', Types::STRING)
            ->setParameter('roleQuestionManager', '%"ROLE_QUESTION_MANAGER"%', Types::STRING)
        ;
    }

    private function applyTeacherRoleFilter(QueryBuilder $qb): void
    {
        $qb->andWhere(
            $qb->expr()->orX(
                'u.roles LIKE :roleTeacher',
                'u.roles LIKE :roleAdmin',
                'u.roles LIKE :roleGlobalAdmin',
                'u.roles LIKE :roleSessionManager',
            )
        )
            ->setParameter('roleTeacher', '%"ROLE_TEACHER"%', Types::STRING)
            ->setParameter('roleAdmin', '%"ROLE_ADMIN"%', Types::STRING)
            ->setParameter('roleGlobalAdmin', '%"ROLE_GLOBAL_ADMIN"%', Types::STRING)
            ->setParameter('roleSessionManager', '%"ROLE_SESSION_MANAGER"%', Types::STRING)
        ;
    }

    private function applyAvailableKeywordFilter(QueryBuilder $qb, string $keyword): void
    {
        if ('' === $keyword) {
            return;
        }

        $terms = array_values(array_filter(array_map('trim', preg_split('/\s+/', $keyword) ?: [])));
        if ([] === $terms) {
            return;
        }

        $nameMatchParts = [];
        foreach ($terms as $index => $term) {
            $param = 'kw'.$index;
            $nameMatchParts[] = '(
                u.firstname LIKE :'.$param.' OR
                u.lastname LIKE :'.$param.' OR
                u.username LIKE :'.$param.' OR
                u.email LIKE :'.$param.' OR
                u.officialCode LIKE :'.$param.'
            )';
            $qb->setParameter($param, '%'.$term.'%', Types::STRING);
        }

        $nameMatch = implode(' AND ', $nameMatchParts);
        $profilingEnabled = $this->isEnabled(
            $this->settingsManager->getSetting('course.profiling_filter_adding_users', true),
        );

        if (!$profilingEnabled) {
            $qb->andWhere($nameMatch);

            return;
        }

        $qb->andWhere('('.$nameMatch.' OR EXISTS (
            SELECT 1
            FROM '.ExtraFieldValues::class.' efv_kw
            JOIN efv_kw.field ef_kw
            WHERE efv_kw.itemId = u.id
              AND ef_kw.itemType = :userItemType
              AND ef_kw.filter = true
              AND efv_kw.fieldValue LIKE :kwFull
        ))')
            ->setParameter('userItemType', ExtraFieldEntity::USER_FIELD_TYPE, Types::INTEGER)
            ->setParameter('kwFull', '%'.$keyword.'%', Types::STRING)
        ;
    }

    private function applyAvailableExtraFieldFilter(QueryBuilder $qb, int $extraFieldId, string $extraFieldValue): void
    {
        if ($extraFieldId <= 0 || '' === $extraFieldValue) {
            return;
        }

        $qb->andWhere('EXISTS (
            SELECT 1
            FROM '.ExtraFieldValues::class.' efv_filter
            WHERE efv_filter.itemId = u.id
              AND IDENTITY(efv_filter.field) = :extraFieldId
              AND efv_filter.fieldValue = :extraFieldValue
        )')
            ->setParameter('extraFieldId', $extraFieldId, Types::INTEGER)
            ->setParameter('extraFieldValue', $extraFieldValue, Types::STRING)
        ;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function resolveAvailableSort(Request $request): array
    {
        $field = match ((string) $request->query->get('sort', 'lastname')) {
            'firstname' => 'u.firstname',
            'username' => 'u.username',
            'officialCode' => 'u.officialCode',
            'email' => 'u.email',
            'fullName' => 'u.lastname',
            default => 'u.lastname',
        };
        $direction = 'desc' === strtolower((string) $request->query->get('order', 'asc')) ? 'DESC' : 'ASC';

        return [$field, $direction];
    }

    /**
     * @return array<int, int>
     */
    public function filterContextUserIds(Course $course, ?Session $session, array $userIds, int $type): array
    {
        $memberIds = $this->getContextMemberIds($course, $session, $type);

        return array_values(array_filter(
            array_values(array_unique(array_map('intval', $userIds))),
            static fn (int $userId): bool => $userId > 0 && isset($memberIds[$userId]),
        ));
    }

    /**
     * @return array<int, true>
     */
    public function getContextMemberIds(Course $course, ?Session $session, ?int $type = null): array
    {
        $status = null;
        if (null !== $type) {
            $status = $session instanceof Session
                ? (self::TYPE_TEACHER === $type ? Session::COURSE_COACH : Session::STUDENT)
                : $type;
        }

        $rows = CourseManager::get_user_list_from_course_code(
            $course->getCode(),
            $session?->getId() ?? 0,
            null,
            null,
            $status,
        );
        $result = [];

        foreach ($rows as $key => $row) {
            $userId = (int) ($row['user_id'] ?? $key);
            if ($userId > 0) {
                $result[$userId] = true;
            }
        }

        return $result;
    }

    public function getCourseSettingAsInt(Course $course, string $name): int
    {
        return (int) api_get_course_setting($name, api_get_course_info($course->getCode()));
    }

    public function buildLegacyUrl(string $path, Course $course, ?Session $session, array $extra = []): string
    {
        $query = array_filter([
            'cid' => (int) $course->getId(),
            'sid' => $session?->getId(),
            ...$extra,
        ], static fn (mixed $value): bool => null !== $value && '' !== $value && 0 !== $value);

        return $path.'?'.http_build_query($query);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getFilteredExtraFields(): array
    {
        $manager = new ExtraField('user');
        $fields = $manager->get_all(['filter = ?' => 1]);

        return \is_array($fields) ? array_values($fields) : [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getProfilingFields(): array
    {
        if (!$this->isEnabled($this->settingsManager->getSetting('course.profiling_filter_adding_users', true))) {
            return [];
        }

        $optionManager = new ExtraFieldOption('user');
        $result = [];

        foreach ($this->getFilteredExtraFields() as $field) {
            $fieldId = (int) ($field['id'] ?? 0);
            if ($fieldId <= 0) {
                continue;
            }

            $options = [];
            foreach ((array) $optionManager->get_field_options_by_field($fieldId, true) as $optionValue => $optionLabel) {
                $options[] = [
                    'value' => (string) $optionValue,
                    'label' => (string) $optionLabel,
                ];
            }

            $result[] = [
                'id' => $fieldId,
                'label' => (string) ($field['display_text'] ?? $field['variable'] ?? ''),
                'options' => $options,
            ];
        }

        return $result;
    }

    /**
     * @param array<string, mixed>             $userData
     * @param array<int, array<string, mixed>> $extraFields
     *
     * @return array<string, mixed>
     */
    private function normalizeMember(
        Course $course,
        ?Session $session,
        int $type,
        User $user,
        array $userData,
        array $extraFields,
    ): array {
        $userId = (int) $user->getId();
        $groups = GroupManager::getAllGroupPerUserSubscription($userId, (int) $course->getId(), $session?->getId());
        $groupNames = array_values(array_filter(array_map(
            static fn (array $group): string => (string) ($group['name'] ?? $group['title'] ?? ''),
            \is_array($groups) ? $groups : [],
        )));
        $isTutor = 1 === (int) ($userData['is_tutor'] ?? 0);
        $status = self::TYPE_TEACHER === $type ? 'Teacher' : ($isTutor ? 'Course tutor' : 'Learner');
        $extraValues = [];
        $valueManager = new ExtraFieldValue('user');
        $optionManager = new ExtraFieldOption('user');

        foreach ($extraFields as $field) {
            $fieldId = (int) ($field['id'] ?? 0);
            if ($fieldId <= 0) {
                continue;
            }

            $data = $valueManager->get_values_by_handler_and_field_id($userId, $fieldId);
            $value = (string) ($data['value'] ?? $data['field_value'] ?? '');
            $options = (array) $optionManager->get_field_options_by_field($fieldId, true);
            $extraValues[(string) $fieldId] = isset($options[$value]) ? (string) $options[$value] : $value;
        }

        $currentUser = $this->security->getUser();
        $currentUserId = $currentUser instanceof User ? (int) $currentUser->getId() : 0;
        $canManage = $this->canManage($course, $session);
        $canUnsubscribe = $this->canUnsubscribe($course, $session)
            && ($this->security->isGranted('ROLE_ADMIN') || $currentUserId !== $userId);
        $canSelfUnsubscribe = !$canManage && $currentUserId === $userId && $this->canSelfUnsubscribe($course, $session);

        return [
            'id' => $userId,
            'officialCode' => (string) $user->getOfficialCode(),
            'firstname' => (string) $user->getFirstname(),
            'lastname' => (string) $user->getLastname(),
            'fullName' => trim($user->getFullName()),
            'username' => $user->getUsername(),
            'email' => $canManage ? $user->getEmail() : '',
            'phone' => $canManage ? (string) $user->getPhone() : '',
            'legalAgreement' => $canManage && 1 === (int) ($userData['legal_agreement'] ?? 0),
            'pictureUrl' => trim((string) $this->illustrationRepository->getIllustrationUrl($user)),
            'groups' => $groupNames,
            'status' => $status,
            'active' => $user->isActive(),
            'isTutor' => $isTutor,
            'isInvitee' => $user->isInvitee(),
            'extraValues' => $extraValues,
            'canReport' => $canManage,
            'canLoginAs' => $this->security->isGranted('ROLE_ADMIN'),
            'canEdit' => null === $session && $canManage && (bool) api_get_configuration_value('extra'),
            'canSetTutor' => $this->canSetTutor($course, $session)
                && self::TYPE_STUDENT === $type
                && !$user->isInvitee(),
            'canUnsubscribe' => $canUnsubscribe || $canSelfUnsubscribe,
            'reportingUrl' => '/reporting/learners/'.$userId.'/courses/'.(int) $course->getId().'?'.http_build_query([
                'sid' => (int) ($session?->getId() ?? 0),
            ]),
            'loginAsUrl' => '/?_switch_user='.rawurlencode($user->getUsername()),
            'editUrl' => $this->buildLegacyUrl('/main/extra/userInfo.php', $course, $session, [
                'editMainUserInfo' => $userId,
            ]),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $items
     *
     * @return array<int, array<string, mixed>>
     */
    private function sortItems(array $items, Request $request): array
    {
        $field = (string) $request->query->get('sort', 'lastname');
        $field = match ($field) {
            'firstname', 'lastname', 'username', 'officialCode', 'email', 'fullName' => $field,
            default => 'lastname',
        };
        $direction = 'desc' === strtolower((string) $request->query->get('order', 'asc')) ? -1 : 1;

        usort($items, static function (array $left, array $right) use ($field, $direction): int {
            return $direction * strcasecmp((string) ($left[$field] ?? ''), (string) ($right[$field] ?? ''));
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

    private function matchesKeyword(User $user, string $keyword, bool $includeEmail = false): bool
    {
        if ('' === $keyword) {
            return true;
        }

        $haystack = [
            (string) $user->getFirstname(),
            (string) $user->getLastname(),
            $user->getUsername(),
            (string) $user->getOfficialCode(),
        ];

        if ($includeEmail) {
            $haystack[] = $user->getEmail();
        }

        foreach (array_values(array_filter(preg_split('/\s+/', $keyword) ?: [])) as $term) {
            $found = false;
            foreach ($haystack as $value) {
                if (false !== mb_stripos($value, $term)) {
                    $found = true;

                    break;
                }
            }

            if (!$found) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return string[]
     */
    private function getHiddenFields(): array
    {
        $setting = $this->settingsManager->getSetting('profile.hide_user_field_from_list', true);
        if (\is_array($setting) && isset($setting['fields']) && \is_array($setting['fields'])) {
            return array_values(array_map('strval', $setting['fields']));
        }

        return [];
    }

    private function resolveActiveFilter(Request $request): ?bool
    {
        if (!$request->query->has('active')) {
            return null;
        }

        return $request->query->getBoolean('active');
    }

    private function isEnabled(mixed $value): bool
    {
        if (\is_bool($value)) {
            return $value;
        }

        return \in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }
}
