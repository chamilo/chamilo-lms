<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseUser;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\IllustrationRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use CourseManager;
use Doctrine\ORM\EntityManagerInterface;
use ExtraField;
use ExtraFieldOption;
use ExtraFieldValue;
use GroupManager;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use UserManager;

use const ANONYMOUS;
use const INVITEE;

final readonly class CourseUserManager
{
    public const int TYPE_TEACHER = CourseRelUser::TEACHER;
    public const int TYPE_STUDENT = CourseRelUser::STUDENT;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
        private SettingsManager $settingsManager,
        private IllustrationRepository $illustrationRepository,
    ) {}

    /**
     * @return array{0: Course, 1: Session|null}
     */
    public function resolveContext(Request $request): array
    {
        $courseId = $request->query->getInt('cid');
        if ($courseId <= 0) {
            throw new BadRequestHttpException('A valid course id is required.');
        }

        $course = $this->entityManager->getRepository(Course::class)->find($courseId);
        if (!$course instanceof Course) {
            throw new BadRequestHttpException('The requested course was not found.');
        }

        $sessionId = $request->query->getInt('sid');
        $session = null;

        if ($sessionId > 0) {
            $session = $this->entityManager->getRepository(Session::class)->find($sessionId);
            if (!$session instanceof Session) {
                throw new BadRequestHttpException('The requested session was not found.');
            }

            if (!$session->hasCourse($course)) {
                throw new AccessDeniedHttpException('The requested session does not contain the current course.');
            }
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
        if ($session instanceof Session || !$this->canManage($course, $session)) {
            return '';
        }

        $courseId = (int) $course->getId();
        $limit = CourseManager::getEffectiveUsersPerCourseLimit($courseId);
        if ($limit <= 0) {
            return '';
        }

        $count = CourseManager::countStudentsForUsersPerCourseLimit($courseId);
        if ($count < $limit) {
            return '';
        }

        return CourseManager::getUsersPerCourseLimitCancelMessage($courseId);
    }

    /**
     * @return array<string, mixed>
     */
    public function getListData(Request $request): array
    {
        [$course, $session] = $this->resolveContext($request);
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
        $limitWarning = self::TYPE_STUDENT === $type ? $this->getLimitWarning($course, $session) : '';

        return [
            'items' => $items,
            'totalItems' => $totalItems,
            'courseId' => (int) $course->getId(),
            'sessionId' => $session?->getId(),
            'type' => $type,
            'canManage' => $canManage,
            'canSubscribe' => $this->canSubscribe($course, $session) && '' === $limitWarning,
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
            'warning' => $limitWarning,
            'sessionManagementUrl' => $this->buildLegacyUrl('/main/user/session_list.php', $course, $session),
            'showSessionManagement' => $canManage && $this->isEnabled(
                $this->settingsManager->getSetting('allow_tutors_to_assign_students_to_session', true),
            ),
            'showClasses' => $this->canManageClasses($course, $session),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getAvailableData(Request $request): array
    {
        [$course, $session] = $this->resolveContext($request);
        $this->assertCanManage($course, $session);
        if (!$this->canSubscribe($course, $session)) {
            throw new AccessDeniedHttpException('Course user subscription is disabled for the current manager.');
        }

        $type = $this->normalizeType($request);
        $keyword = trim((string) $request->query->get('search', ''));
        $extraFieldId = $request->query->getInt('extraFieldId');
        $extraFieldValue = trim((string) $request->query->get('extraFieldValue', ''));
        $memberIds = $this->getContextMemberIds($course, $session);
        $rows = UserManager::get_user_list([], [$this->getUserOrderExpression($request)]);
        $items = [];
        $showEmail = $this->isEnabled($this->settingsManager->getSetting('show_email_addresses', true));

        foreach ($rows as $row) {
            $userId = (int) ($row['user_id'] ?? $row['id'] ?? 0);
            if ($userId <= 0 || isset($memberIds[$userId])) {
                continue;
            }

            $user = $this->entityManager->getRepository(User::class)->find($userId);
            if (!$user instanceof User
                || User::SOFT_DELETED === $user->getActive()
                || ANONYMOUS === $user->getStatus()
                || !$this->matchesAvailableKeyword($user, $keyword)
                || !$this->matchesRequestedRole($user, $type)
                || !$this->matchesExtraField($userId, $extraFieldId, $extraFieldValue)
            ) {
                continue;
            }

            if ((self::TYPE_STUDENT === $type || $session instanceof Session)
                && 'ADMIN' === strtoupper((string) $user->getOfficialCode())
            ) {
                continue;
            }

            $items[] = [
                'id' => $userId,
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

        $items = $this->sortItems($items, $request);
        $totalItems = \count($items);

        return [
            'items' => $this->paginate($items, $request),
            'totalItems' => $totalItems,
            'courseId' => (int) $course->getId(),
            'sessionId' => $session?->getId(),
            'type' => $type,
            'canManage' => true,
            'canSubscribe' => $this->canSubscribe($course, $session)
                && (self::TYPE_TEACHER === $type || '' === $this->getLimitWarning($course, $session)),
            'showEmail' => $showEmail,
            'westernNameOrder' => api_is_western_name_order(),
            'extraFields' => $this->getProfilingFields(),
            'warning' => self::TYPE_STUDENT === $type ? $this->getLimitWarning($course, $session) : '',
        ];
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

    private function matchesRequestedRole(User $user, int $type): bool
    {
        if (self::TYPE_STUDENT === $type) {
            return true;
        }

        return $user->isTeacher() || $user->isAdmin() || $user->isSessionAdmin();
    }

    private function matchesExtraField(int $userId, int $fieldId, string $expectedValue): bool
    {
        if ($fieldId <= 0 || '' === $expectedValue) {
            return true;
        }

        $valueManager = new ExtraFieldValue('user');
        $data = $valueManager->get_values_by_handler_and_field_id($userId, $fieldId);
        $value = (string) ($data['value'] ?? $data['field_value'] ?? '');

        return $expectedValue === $value;
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
            'isInvitee' => INVITEE === $user->getStatus() || $user->isInvitee(),
            'extraValues' => $extraValues,
            'canReport' => $canManage,
            'canLoginAs' => $this->security->isGranted('ROLE_ADMIN'),
            'canEdit' => null === $session && $canManage && (bool) api_get_configuration_value('extra'),
            'canSetTutor' => $this->canSetTutor($course, $session)
                && self::TYPE_STUDENT === $type
                && !$user->isInvitee(),
            'canUnsubscribe' => $canUnsubscribe || $canSelfUnsubscribe,
            'reportingUrl' => $this->buildLegacyUrl('/main/my_space/myStudents.php', $course, $session, [
                'student' => $userId,
                'details' => 'true',
                'course' => (int) $course->getId(),
                'origin' => 'user_course',
                'id_session' => $session?->getId() ?? 0,
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

    private function matchesAvailableKeyword(User $user, string $keyword): bool
    {
        if ($this->matchesKeyword($user, $keyword, true)) {
            return true;
        }

        if ('' === $keyword
            || !$this->isEnabled($this->settingsManager->getSetting('course.profiling_filter_adding_users', true))
        ) {
            return false;
        }

        $valueManager = new ExtraFieldValue('user');
        foreach ($this->getFilteredExtraFields() as $field) {
            $fieldId = (int) ($field['id'] ?? 0);
            if ($fieldId <= 0) {
                continue;
            }

            $data = $valueManager->get_values_by_handler_and_field_id((int) $user->getId(), $fieldId);
            $value = (string) ($data['value'] ?? $data['field_value'] ?? '');
            if (false !== mb_stripos($value, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function getUserOrderExpression(Request $request): string
    {
        return match ((string) $request->query->get('sort', 'lastname')) {
            'firstname' => 'firstname ASC, lastname ASC',
            'username' => 'username ASC',
            'officialCode' => 'official_code ASC',
            default => 'lastname ASC, firstname ASC',
        };
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
