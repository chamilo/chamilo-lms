<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Admin;

use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserAuthSource;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\AccessUrlScopeHelper;
use Chamilo\CoreBundle\Helpers\AuthenticationConfigHelper;
use Chamilo\CoreBundle\Repository\CourseRelUserRepository;
use Chamilo\CoreBundle\Repository\Node\IllustrationRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use CourseManager;
use Database;
use Doctrine\ORM\EntityManagerInterface;
use ExtraField;
use ExtraFieldValue;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;
use UserManager;

use const FILTER_VALIDATE_EMAIL;
use const UPLOAD_ERR_OK;

/**
 * Data/action endpoints for the "Edit user" admin page, replacing
 * public/main/admin/user_edit.php. See CLAUDE.md's "Playwright" and
 * "Legacy link locations" sections for the migration this belongs to.
 */
#[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_SESSION_MANAGER")'))]
final class UserEditController extends AbstractController
{
    private const array MULTIPLE_VALUE_TYPES = [
        ExtraField::FIELD_TYPE_SELECT_MULTIPLE,
        ExtraField::FIELD_TYPE_CHECKBOX,
    ];

    /**
     * Mirrors the legacy page's own addEmailTemplate(['user_edit_content.tpl']) call --
     * unlike user_add.php, the edit page only has this single template slot.
     */
    private const array EMAIL_TEMPLATE_TYPES = [
        'user_edit_content.tpl',
    ];

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly IllustrationRepository $illustrationRepository,
        private readonly AuthenticationConfigHelper $authenticationConfigHelper,
        private readonly AccessUrlHelper $accessUrlHelper,
        private readonly AccessUrlScopeHelper $accessUrlScope,
        private readonly EntityManagerInterface $entityManager,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly TranslatorInterface $translator,
    ) {}

    #[Route('/admin/user-edit-data', name: 'admin_user_edit_data', methods: ['GET'])]
    public function data(Request $request): JsonResponse
    {
        $userId = (int) $request->query->get('user_id', 0);
        $user = $userId > 0 ? $this->userRepository->find($userId) : null;
        if (!$user instanceof User) {
            return $this->json(['error' => $this->translator->trans('User not found')], Response::HTTP_NOT_FOUND);
        }

        if (null !== ($denied = $this->denyIfCannotEditTargetUser($user))) {
            return $denied;
        }

        $currentUser = $this->getCurrentUser();
        $isSelf = (int) $currentUser->getId() === $userId;
        $hideFields = $isSelf || User::SOFT_DELETED === $user->getActive();
        $hideNeverExpireOption = 'true' === api_get_setting('registration.user_hide_never_expire_option')
            && !$this->isGranted('ROLE_ADMIN');
        $adminsCanSetUsersPass = 'true' === api_get_setting('security.admins_can_set_users_pass');
        $loginIsEmail = 'true' === api_get_setting('login_is_email');

        $expirationDate = $user->getExpirationDate();
        $defaultExpiration = null;
        if (!$hideFields && $hideNeverExpireOption && null === $expirationDate) {
            $days = (int) api_get_setting('account_valid_duration');
            $defaultExpiration = api_get_local_time('+'.$days.' day');
        }

        $creatorInfo = null;
        $creator = $user->getCreatorId() ? $this->userRepository->find($user->getCreatorId()) : null;
        if ($creator instanceof User) {
            $creatorInfo = [
                'id' => $creator->getId(),
                'username' => $creator->getUsername(),
            ];
        }

        $studentBossIds = array_map(
            static fn (array $row): int => (int) $row['boss_id'],
            UserManager::getStudentBossList($userId) ?: []
        );

        return $this->json([
            'westernNameOrder' => api_is_western_name_order(),
            'loginIsEmail' => $loginIsEmail,
            'emailRequired' => 'true' === api_get_setting('registration', 'email'),
            'hideNeverExpireOption' => $hideNeverExpireOption,
            'adminsCanSetUsersPass' => $adminsCanSetUsersPass,
            'hideFields' => $hideFields,
            'authSources' => $this->buildAuthSources(),
            'roleOptions' => $this->buildRoleOptions(),
            'extraFields' => $this->buildExtraFieldDefinitions(),
            'extraValues' => $this->buildExtraFieldValues($userId),
            'frozenExtraFieldVariables' => $this->buildFrozenExtraFieldVariables(),
            'studentBossOptions' => $this->buildStudentBossOptions(),
            'emailTemplateTypes' => $this->buildEmailTemplateTypes(),
            'loginAsToken' => $this->csrfTokenManager->getToken('login_as')->getValue(),
            'canLoginAs' => $this->canLoginAs($currentUser, $user),
            'user' => [
                'id' => $user->getId(),
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getLastname(),
                'officialCode' => $user->getOfficialCode(),
                'email' => $user->getEmail(),
                'username' => $user->getUsername(),
                'phone' => $user->getPhone(),
                'locale' => $user->getLocale(),
                'authSources' => $user->getAuthSourcesAuthentications($this->accessUrlHelper->getCurrent()),
                'roles' => $this->buildSelectedRoleOptionKeys($user),
                'active' => $user->getActive(),
                'expirationDate' => $expirationDate?->format('c') ?? ($defaultExpiration ?? null),
                'hasExpirationDate' => $hideNeverExpireOption || null !== $expirationDate,
                'studentBoss' => $studentBossIds,
                'hasPicture' => $this->illustrationRepository->hasIllustration($user),
                'pictureUrl' => $this->illustrationRepository->hasIllustration($user)
                    ? $this->illustrationRepository->getIllustrationUrl($user, 'user_picture_profile')
                    : null,
                'createdAt' => $user->getCreatedAt()?->format('c'),
                'creator' => $creatorInfo,
            ],
        ]);
    }

    #[Route('/admin/user-edit-action', name: 'admin_user_edit_action', methods: ['POST'])]
    public function update(Request $request): JsonResponse
    {
        $payload = $request->request;
        $userId = (int) $payload->get('user_id', 0);
        $user = $userId > 0 ? $this->userRepository->find($userId) : null;
        if (!$user instanceof User) {
            return $this->json(['error' => $this->translator->trans('User not found')], Response::HTTP_NOT_FOUND);
        }

        if (null !== ($denied = $this->denyIfCannotEditTargetUser($user))) {
            return $denied;
        }

        $currentUser = $this->getCurrentUser();
        $isSelf = (int) $currentUser->getId() === $userId;
        $hideFields = $isSelf || User::SOFT_DELETED === $user->getActive();
        $hideNeverExpireOption = 'true' === api_get_setting('registration.user_hide_never_expire_option')
            && !$this->isGranted('ROLE_ADMIN');
        $adminsCanSetUsersPass = 'true' === api_get_setting('security.admins_can_set_users_pass');
        $loginIsEmail = 'true' === api_get_setting('login_is_email');

        $firstname = trim((string) $payload->get('firstname', ''));
        $lastname = trim((string) $payload->get('lastname', ''));
        if ('' === $firstname || '' === $lastname) {
            return $this->json(['error' => $this->translator->trans('Required field')], Response::HTTP_BAD_REQUEST);
        }

        $email = trim((string) $payload->get('email', ''));
        if ('' === $email && ('true' === api_get_setting('registration', 'email') || $loginIsEmail)) {
            return $this->json(['error' => $this->translator->trans('Required field')], Response::HTTP_BAD_REQUEST);
        }
        if ('' !== $email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json(['error' => $this->translator->trans('The email address is not complete or contains some invalid characters')], Response::HTTP_BAD_REQUEST);
        }

        $username = $loginIsEmail ? $email : trim((string) $payload->get('username', $user->getUsername()));
        if ('' === $username) {
            return $this->json(['error' => $this->translator->trans('Required field')], Response::HTTP_BAD_REQUEST);
        }
        if (!$loginIsEmail) {
            if (\strlen($username) > User::USERNAME_MAX_LENGTH) {
                return $this->json([
                    'error' => \sprintf($this->translator->trans('The login needs to be maximum %s characters long'), (string) User::USERNAME_MAX_LENGTH),
                ], Response::HTTP_BAD_REQUEST);
            }
            if (!UserManager::is_username_valid($username)) {
                return $this->json(['error' => $this->translator->trans('Only letters and numbers allowed')], Response::HTTP_BAD_REQUEST);
            }
        }
        if ($username !== $user->getUsername() && !$this->userRepository->isUsernameAvailable($username)) {
            return $this->json(['error' => $this->translator->trans('This login is already in use')], Response::HTTP_CONFLICT);
        }

        $roles = array_values(array_unique(array_map(
            'api_normalize_role_code',
            array_filter((array) $payload->all('roles'), 'strlen')
        )));
        if ([] === $roles || !UserManager::areRolesAllowedInUserForm($roles)) {
            return $this->json(['error' => $this->translator->trans('Error')], Response::HTTP_FORBIDDEN);
        }
        $newStatus = api_status_from_roles($roles);

        if (DRH === $newStatus && CourseManager::is_user_subscribed_in_course($userId)) {
            return $this->json([
                'error' => $this->translator->trans('The status of this user cannot be changed to human resources manager.'),
            ], Response::HTTP_CONFLICT);
        }

        $oldStatus = (int) $user->getStatus();
        if ($oldStatus !== $newStatus && STUDENT === $newStatus) {
            $conflicts = $this->collectRoleDowngradeConflicts($user);
            if ([] !== $conflicts) {
                return $this->json([
                    'error' => $this->translator->trans('Role change denied due to incompatible current assignments:'),
                    'conflicts' => $conflicts,
                ], Response::HTTP_CONFLICT);
            }
        }

        $allowedAuthSources = array_map('strval', array_keys($this->authSourceMap()));
        $submittedAuthSources = array_values(array_intersect(
            array_map('strval', (array) $payload->all('authSource')),
            $allowedAuthSources
        ));
        if ([] === $submittedAuthSources) {
            return $this->json(['error' => $this->translator->trans('Required field')], Response::HTTP_BAD_REQUEST);
        }
        $hasPlatformAuth = \in_array(UserAuthSource::PLATFORM, $submittedAuthSources, true);

        // 0 = don't reset, 1 = auto-generate, 2 = set manually (only when allowed).
        $resetPassword = (int) $payload->get('resetPassword', 0);
        $password = (string) $payload->get('password', '');
        if (!$hasPlatformAuth || (2 === $resetPassword && !$adminsCanSetUsersPass)) {
            $resetPassword = 0;
            $password = '';
        }
        if (2 === $resetPassword) {
            if ('' === $password || '0' === $password) {
                return $this->json(['error' => $this->translator->trans('The password is too short')], Response::HTTP_BAD_REQUEST);
            }
            if ('true' === api_get_setting('security.check_password') && !api_check_password($password)) {
                return $this->json([
                    'error' => $this->translator->trans('this password  is too simple. Use a pass like this').': '.api_generate_password(),
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        // Mirrors the legacy page's unconditional server-side override: when
        // hideNeverExpireOption is on, the "Never expires" radio isn't even
        // rendered, so a client claiming hasExpirationDate=0 must be ignored.
        $hasExpirationDate = !$hideFields && ($hideNeverExpireOption || '1' === (string) $payload->get('hasExpirationDate', '0'));
        $expirationDate = null;
        if ($hasExpirationDate) {
            $expirationDate = (string) $payload->get('expirationDate', '');
            if ('' === $expirationDate) {
                $days = (int) api_get_setting('account_valid_duration');
                $expirationDate = api_get_local_time('+'.$days.' day');
            }
        }

        $officialCode = trim((string) $payload->get('officialCode', ''));
        $phone = trim((string) $payload->get('phone', ''));
        $locale = (string) $payload->get('locale', $user->getLocale());
        $active = $hideFields
            ? $user->getActive()
            : (User::ACTIVE === (int) $payload->get('active', User::ACTIVE) ? User::ACTIVE : User::INACTIVE);
        $sendMail = (int) $payload->get('sendMail', 0);
        $emailTemplate = (array) $payload->all('emailTemplateOption');

        $extra = [];
        foreach ($this->buildExtraFieldDefinitions() as $field) {
            $key = 'extra_'.$field['variable'];

            if (\in_array($field['valueType'], self::MULTIPLE_VALUE_TYPES, true)) {
                $values = $payload->all($key);
                if ([] !== $values) {
                    $extra[$key] = $values;
                }

                continue;
            }

            if ($payload->has($key)) {
                $extra[$key] = $payload->get($key);
            }
        }

        $pictureUri = null;
        if ('1' === (string) $payload->get('deletePicture', '0')) {
            UserManager::deleteUserPicture($userId);
        }

        UserManager::update_user(
            $userId,
            $firstname,
            $lastname,
            $username,
            $password,
            $submittedAuthSources,
            $email,
            $newStatus,
            $officialCode,
            $phone,
            $pictureUri,
            $expirationDate,
            $active,
            null,
            0,
            null,
            $locale,
            '',
            (bool) $sendMail,
            $resetPassword,
            null,
            $emailTemplate
        );

        UserManager::subscribeUserToBossList(
            $userId,
            array_map('intval', (array) $payload->all('studentBoss')),
            true
        );

        $userEntity = $this->userRepository->find($userId);
        if ($userEntity instanceof User) {
            $userEntity->setRoles($roles);
            $this->userRepository->updateUser($userEntity);
        }

        $extra['item_id'] = $userId;
        $extraFieldValue = new ExtraFieldValue('user');
        $extraFieldValue->saveFieldValues($extra);

        return $this->json(['success' => true, 'userId' => $userId]);
    }

    #[Route('/admin/user-edit-picture', name: 'admin_user_edit_picture', methods: ['POST'])]
    public function uploadPicture(Request $request): JsonResponse
    {
        $userId = (int) $request->request->get('user_id', 0);
        $user = $userId > 0 ? $this->userRepository->find($userId) : null;
        if (!$user instanceof User) {
            return $this->json(['error' => 'User not found.'], Response::HTTP_NOT_FOUND);
        }

        if (null !== ($denied = $this->denyIfCannotEditTargetUser($user))) {
            return $denied;
        }

        $file = $request->files->get('file');
        if (!$file instanceof UploadedFile || UPLOAD_ERR_OK !== $file->getError()) {
            return $this->json(['error' => 'A valid image file is required.'], Response::HTTP_BAD_REQUEST);
        }

        UserManager::update_user_picture($userId, $file, (string) $request->request->get('cropResult', ''));

        return $this->json([
            'success' => true,
            'url' => $this->illustrationRepository->getIllustrationUrl($user, 'user_picture_profile'),
        ]);
    }

    /**
     * Combines the legacy escalation check (api_global_admin_can_edit_admin -- role-hierarchy
     * based: a session admin may not edit a global admin, a subtree-scoped global admin may not
     * edit an unrestricted one) with AccessUrlScopeHelper::canEditUser() (multi-URL tenant
     * scoping: the two users must share at least one access URL). Both are required, per an
     * explicit decision to combine both models rather than pick one -- they close different
     * gaps (role escalation vs. cross-tenant reach) and neither implies the other.
     */
    private function denyIfCannotEditTargetUser(User $target): ?JsonResponse
    {
        $currentUser = $this->getCurrentUser();
        $currentId = (int) $currentUser->getId();
        $targetId = (int) $target->getId();

        if ($currentId === $targetId) {
            return null;
        }

        if (!api_global_admin_can_edit_admin($targetId, $currentId, true)) {
            return $this->json(['error' => 'Access denied.'], Response::HTTP_FORBIDDEN);
        }

        if (!$this->accessUrlScope->canEditUser($currentUser, $target)) {
            return $this->json(['error' => 'Access denied.'], Response::HTTP_FORBIDDEN);
        }

        return null;
    }

    private function getCurrentUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedException();
        }

        return $user;
    }

    private function canLoginAs(User $currentUser, User $targetUser): bool
    {
        if (User::ANONYMOUS === $targetUser->getStatus() || (int) $currentUser->getId() === (int) $targetUser->getId()) {
            return false;
        }

        if ($this->isGranted('ROLE_ADMIN')) {
            return true;
        }

        return $this->isGranted('ROLE_SESSION_MANAGER') && STUDENT === $targetUser->getStatus();
    }

    /**
     * Mirrors the role-conflict validation in the legacy page: downgrading a user to STUDENT
     * is refused when they still hold assignments that only make sense for a higher role.
     */
    private function collectRoleDowngradeConflicts(User $user): array
    {
        $conflicts = [];

        /** @var CourseRelUserRepository $cruRepo */
        $cruRepo = $this->entityManager->getRepository(CourseRelUser::class);
        if ($cruRepo->countTaughtCoursesForUser($user) > 0) {
            $conflicts[] = $this->translator->trans('User is teacher in some courses');
        }
        if ([] !== $user->getSessionsAsGeneralCoach()) {
            $conflicts[] = $this->translator->trans('User is general tutor in some sessions');
        }
        if ([] !== $user->getSessionsAsAdmin()) {
            $conflicts[] = $this->translator->trans('User is session admin in some sessions');
        }

        return $conflicts;
    }

    /**
     * @return array<string, string> auth source key => translated label
     */
    private function authSourceMap(): array
    {
        $accessUrl = $this->accessUrlHelper->getCurrent();
        $enabled = $this->authenticationConfigHelper->getAuthSourceAuthentications($accessUrl);

        $map = [UserAuthSource::PLATFORM => $this->translator->trans('Platform')];
        foreach ($enabled as $key) {
            if (UserAuthSource::PLATFORM === $key) {
                continue;
            }
            if (UserAuthSource::CAS === $key && 'true' !== api_get_setting('cas_activate')) {
                continue;
            }
            $map[$key] = $key;
        }

        return $map;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function buildAuthSources(): array
    {
        $items = [];
        foreach ($this->authSourceMap() as $value => $label) {
            $items[] = ['value' => $value, 'label' => $label];
        }

        return $items;
    }

    /**
     * User::getRoles() includes Symfony's implicit base roles (e.g. ROLE_USER), which are not
     * selectable "roleOptions" entries -- feeding them back into the roles[] payload verbatim
     * would fail UserManager::areRolesAllowedInUserForm() on save. Mirrors the legacy page's
     * own $optionKeyByCanon / $userCanonRoles filtering.
     *
     * @return list<string>
     */
    private function buildSelectedRoleOptionKeys(User $user): array
    {
        $optionKeyByCanon = [];
        foreach (UserManager::getAllowedRoleOptionsForUserForm() as $optKey => $label) {
            $optionKeyByCanon[api_normalize_role_code((string) $optKey)] = (string) $optKey;
        }

        $selected = [];
        foreach (array_map('api_normalize_role_code', $user->getRoles()) as $canon) {
            if (isset($optionKeyByCanon[$canon])) {
                $selected[] = $optionKeyByCanon[$canon];
            }
        }

        return array_values(array_unique($selected));
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function buildRoleOptions(): array
    {
        $items = [];
        foreach (UserManager::getAllowedRoleOptionsForUserForm() as $code => $label) {
            $items[] = ['value' => (string) $code, 'label' => $this->translator->trans((string) $label)];
        }

        return $items;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildExtraFieldDefinitions(): array
    {
        $extraField = new ExtraField('user');
        $fields = $extraField->get_all([], 'option_order');

        $items = [];
        foreach ($fields as $field) {
            $options = [];
            foreach ($field['options'] ?: [] as $option) {
                $options[] = [
                    'value' => $option['option_value'],
                    'label' => $option['display_text'],
                ];
            }

            $items[] = [
                'variable' => $field['variable'],
                'valueType' => (int) $field['value_type'],
                'displayText' => $field['display_text'],
                'helperText' => $field['helper_text'] ?? '',
                'defaultValue' => $field['default_value'] ?? '',
                'options' => $options,
            ];
        }

        return $items;
    }

    /**
     * @return array<string, mixed> extra_<variable> => current value (string, or list<string> for multi-value fields)
     */
    private function buildExtraFieldValues(int $userId): array
    {
        $extraFieldValue = new ExtraFieldValue('user');
        $rows = $extraFieldValue->getAllValuesByItem($userId);
        if (false === $rows) {
            return [];
        }

        $values = [];
        foreach ($rows as $row) {
            $variable = $row['variable'];
            $valueType = (int) $row['value_type'];

            if (\in_array($valueType, self::MULTIPLE_VALUE_TYPES, true)) {
                $values[$variable][] = $row['field_value'];

                continue;
            }

            $values[$variable] = $row['field_value'];
        }

        return $values;
    }

    /**
     * Mirrors the legacy page's "freeze user conditions, admin cannot update them" behaviour,
     * driven by the profile.show_conditions_to_user setting (a JSON-ish array with a
     * 'conditions' list of extra-field variables that must be shown read-only once a user
     * has already answered them -- e.g. GDPR-style consent fields).
     *
     * @return list<string>
     */
    private function buildFrozenExtraFieldVariables(): array
    {
        $extraConditions = api_get_setting('profile.show_conditions_to_user', true);
        if (!\is_array($extraConditions) || !isset($extraConditions['conditions'])) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn ($condition) => \is_array($condition) ? ($condition['variable'] ?? null) : null,
            (array) $extraConditions['conditions']
        )));
    }

    /**
     * @return list<array{value: int, label: string}>
     */
    private function buildStudentBossOptions(): array
    {
        $rows = UserManager::get_user_list(['status' => STUDENT_BOSS]);
        $items = [];
        foreach ($rows ?: [] as $row) {
            $bossInfo = api_get_user_info((int) $row['user_id']);
            if (!$bossInfo) {
                continue;
            }
            $items[] = ['value' => (int) $bossInfo['user_id'], 'label' => $bossInfo['complete_name_with_username']];
        }

        return $items;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildEmailTemplateTypes(): array
    {
        $urlId = api_get_current_access_url_id();
        $items = [];

        foreach (self::EMAIL_TEMPLATE_TYPES as $type) {
            $rows = Database::select(
                'id, title, template, default_template',
                'mail_template',
                ['where' => ['type = ? AND url_id = ?' => [$type, $urlId]]]
            );

            if (empty($rows)) {
                continue;
            }

            $options = [];
            $defaultId = null;
            $previewById = [];
            foreach ($rows as $row) {
                $id = (int) $row['id'];
                $options[] = ['value' => $id, 'label' => $row['title']];
                $previewById[$id] = $row['template'];
                if (1 === (int) $row['default_template']) {
                    $defaultId = $id;
                }
            }

            $items[] = [
                'type' => $type,
                'options' => $options,
                'defaultId' => $defaultId,
                'previewById' => $previewById,
            ];
        }

        return $items;
    }
}
