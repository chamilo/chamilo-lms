<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Admin;

use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserAuthSource;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\AccessUrlScopeHelper;
use Chamilo\CoreBundle\Helpers\AuthenticationConfigHelper;
use Chamilo\CoreBundle\Repository\AssetRepository;
use Chamilo\CoreBundle\Repository\CourseRelUserRepository;
use Chamilo\CoreBundle\Repository\Node\IllustrationRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use CourseManager;
use Database;
use Doctrine\ORM\EntityManagerInterface;
use ExtraField;
use ExtraFieldValue;
use Psr\Log\LoggerInterface;
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
use Throwable;
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
    // FIELD_TYPE_TAG's values are submitted as an array too (one entry per
    // chip the user committed) -- ExtraFieldValue::saveFieldValues() already
    // accepts either an array or a bare string for it, but only the array
    // form actually creates one tag per entry; a bare string (the old plain-
    // text fallback) got saved as a single tag containing the whole string.
    private const array MULTIPLE_VALUE_TYPES = [
        ExtraField::FIELD_TYPE_SELECT_MULTIPLE,
        ExtraField::FIELD_TYPE_CHECKBOX,
        ExtraField::FIELD_TYPE_TAG,
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
        private readonly AssetRepository $assetRepository,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly TranslatorInterface $translator,
        private readonly LoggerInterface $logger,
    ) {}

    #[Route('/admin/user-edit-data', name: 'admin_user_edit_data', methods: ['GET'])]
    public function data(Request $request): JsonResponse
    {
        $userId = (int) $request->query->get('user_id', '0');
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

        $extraFieldErrors = $this->validateExtraFieldValues($request);
        if ([] !== $extraFieldErrors) {
            return $this->json([
                'error' => $this->translator->trans('One or more fields contain invalid values.'),
                'fieldErrors' => $extraFieldErrors,
            ], Response::HTTP_BAD_REQUEST);
        }

        $groupedValueTypes = [
            ExtraField::FIELD_TYPE_DOUBLE_SELECT,
            ExtraField::FIELD_TYPE_SELECT_WITH_TEXT_FIELD,
            ExtraField::FIELD_TYPE_TRIPLE_SELECT,
        ];
        $geolocationValueTypes = [
            ExtraField::FIELD_TYPE_GEOLOCALIZATION,
            ExtraField::FIELD_TYPE_GEOLOCALIZATION_COORDINATES,
        ];
        $fileValueTypes = [
            ExtraField::FIELD_TYPE_FILE_IMAGE,
            ExtraField::FIELD_TYPE_FILE,
        ];

        // Grouped/geolocation/file values are deliberately kept out of $extra:
        // UserManager::update_user() applies $extra itself through its own
        // per-field update_extra_field_value() loop, a narrower code path than
        // the explicit, well-tested saveFieldValues() call this controller
        // makes further down. Routing a nested array (grouped selects) or a
        // raw upload array (file fields) through that narrower path risks a
        // type error before the explicit call ever runs, for no benefit since
        // that explicit call re-saves the definitive value anyway.
        $extra = [];
        $extraDeferred = [];
        $removedExtraFiles = [];
        foreach ($this->buildExtraFieldDefinitions() as $field) {
            $key = 'extra_'.$field['variable'];
            $valueType = $field['valueType'];

            if (ExtraField::FIELD_TYPE_DIVIDER === $valueType) {
                continue;
            }

            if (\in_array($valueType, self::MULTIPLE_VALUE_TYPES, true)) {
                $values = $payload->all($key);
                if ([] !== $values) {
                    $extra[$key] = $values;
                }

                continue;
            }

            if (\in_array($valueType, $geolocationValueTypes, true)) {
                if ($payload->has($key)) {
                    $extraDeferred[$key] = $payload->get($key);
                }
                $coordinatesKey = $key.'_coordinates';
                if ($payload->has($coordinatesKey)) {
                    $extraDeferred[$coordinatesKey] = $payload->get($coordinatesKey);
                }

                continue;
            }

            if (\in_array($valueType, $groupedValueTypes, true)) {
                $group = (array) $payload->all($key);
                if ([] !== $group) {
                    $extraDeferred[$key] = $group;
                }

                continue;
            }

            if (\in_array($valueType, $fileValueTypes, true)) {
                if ('1' === (string) $payload->get($key.'_remove', '0')) {
                    $removedExtraFiles[] = $field['variable'];

                    continue;
                }

                $uploadedFile = $request->files->get($key);
                if ($uploadedFile instanceof UploadedFile) {
                    $extraDeferred[$key] = [
                        'name' => $uploadedFile->getClientOriginalName(),
                        'type' => $uploadedFile->getClientMimeType(),
                        'tmp_name' => $uploadedFile->getPathname(),
                        'error' => $uploadedFile->getError(),
                        'size' => $uploadedFile->getSize(),
                    ];
                }

                continue;
            }

            if (ExtraField::FIELD_TYPE_MOBILE_PHONE_NUMBER === $valueType && $payload->has($key)) {
                $extra[$key] = self::filterMobilePhoneNumber((string) $payload->get($key));

                continue;
            }

            if (ExtraField::FIELD_TYPE_DURATION === $valueType && $payload->has($key)) {
                $extra[$key] = self::durationToSeconds((string) $payload->get($key));

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
        foreach ($extraDeferred as $deferredKey => $deferredValue) {
            $extra[$deferredKey] = $deferredValue;
        }
        foreach ($removedExtraFiles as $variable) {
            $this->deleteExtraFieldValue($userId, $variable);
        }

        try {
            (new ExtraFieldValue('user'))->saveFieldValues($extra);
        } catch (Throwable $exception) {
            // The user's other fields are already saved at this point (see
            // UserManager::update_user() above) -- only the additional profile
            // fields failed. Log the real cause (prod hides it from the
            // response) and say so plainly instead of leaking a bare "Error"
            // with no indication of what actually happened.
            $this->logger->error('Failed to save extra field values while editing a user.', [
                'userId' => $userId,
                'exception' => $exception,
            ]);

            return $this->json([
                'error' => $this->translator->trans('The user was updated, but one of the additional profile fields could not be saved.'),
                'userId' => $userId,
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

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

        $hierarchicalTypes = [
            ExtraField::FIELD_TYPE_DOUBLE_SELECT,
            ExtraField::FIELD_TYPE_SELECT_WITH_TEXT_FIELD,
            ExtraField::FIELD_TYPE_TRIPLE_SELECT,
        ];

        $items = [];
        foreach ($fields as $field) {
            $valueType = (int) $field['value_type'];

            if (ExtraField::FIELD_TYPE_TIMEZONE === $valueType) {
                // FIELD_TYPE_TIMEZONE has no rows in extra_field_option at all --
                // the legacy page populates its <select> straight from PHP's own
                // DateTimeZone::listIdentifiers() (via api_get_timezones()), not
                // from stored options, so the normal loop below would otherwise
                // always leave it with zero choices.
                $options = [];
                foreach (array_keys(api_get_timezones()) as $timezone) {
                    if ('' === $timezone) {
                        continue;
                    }
                    $options[] = ['value' => $timezone, 'label' => $timezone];
                }
            } elseif (\in_array($valueType, $hierarchicalTypes, true)) {
                // Cascading selects: legacy chains options via option_value,
                // where '0' marks a top-level option and any other value is
                // the parent option's own id (see
                // ExtraField::extra_field_double_select_convert_array_to_ordered_array()
                // / tripleSelectConvertArrayToOrderedArray()). The submitted
                // value for each level is the option's id, not option_value,
                // so both are exposed and the frontend builds the tree itself
                // instead of porting the legacy AJAX cascade endpoint.
                $options = [];
                foreach ($field['options'] ?: [] as $option) {
                    $options[] = [
                        'id' => (int) $option['id'],
                        'parentId' => (int) $option['option_value'],
                        'label' => $option['display_text'],
                    ];
                }
            } else {
                $options = [];
                foreach ($field['options'] ?: [] as $option) {
                    $options[] = [
                        'value' => $option['option_value'],
                        'label' => $option['display_text'],
                    ];
                }
            }

            $items[] = [
                'variable' => $field['variable'],
                'valueType' => $valueType,
                'displayText' => $field['display_text'],
                'helperText' => $field['helper_text'] ?? '',
                'defaultValue' => $field['default_value'] ?? '',
                'options' => $options,
            ];
        }

        return $items;
    }

    /**
     * Server-side enforcement of the same rules FormValidator attaches to these
     * extra-field types (public/main/inc/lib/formvalidator/FormValidator.class.php
     * and the Rule/MobilePhoneNumber.php callback) -- the client already blocks
     * these live, but a request bypassing the SPA must not be able to store a
     * value the legacy form itself would have rejected.
     *
     * @return array<string, string> variable => translated error message
     */
    private function validateExtraFieldValues(Request $request): array
    {
        $payload = $request->request;
        $errors = [];
        $allowedImageExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        // These all submit an ARRAY at this key -- grouped selects as
        // extra_<var>[extra_<var>]=..., and SELECT_MULTIPLE/CHECKBOX/TAG as
        // repeated extra_<var>[]=... entries. InputBag::get() throws
        // BadRequestException on a non-scalar value, so none of them may ever
        // reach it (confirmed live: any user with a real checkbox or tag value
        // -- i.e. basically every real user -- 500'd on save until this list
        // covered these three too, not just the grouped selects).
        $nonScalarValueTypes = [
            ExtraField::FIELD_TYPE_DOUBLE_SELECT,
            ExtraField::FIELD_TYPE_SELECT_WITH_TEXT_FIELD,
            ExtraField::FIELD_TYPE_TRIPLE_SELECT,
            ExtraField::FIELD_TYPE_SELECT_MULTIPLE,
            ExtraField::FIELD_TYPE_CHECKBOX,
            ExtraField::FIELD_TYPE_TAG,
        ];

        foreach ($this->buildExtraFieldDefinitions() as $field) {
            $key = 'extra_'.$field['variable'];

            if (\in_array($field['valueType'], $nonScalarValueTypes, true)) {
                continue;
            }

            if (ExtraField::FIELD_TYPE_FILE_IMAGE === $field['valueType']) {
                $uploadedFile = $request->files->get($key);
                if ($uploadedFile instanceof UploadedFile) {
                    $extension = strtolower((string) $uploadedFile->getClientOriginalExtension());
                    if (!\in_array($extension, $allowedImageExtensions, true)) {
                        $errors[$field['variable']] = $this->translator->trans('Only PNG, JPG or GIF images allowed').' ('.implode(',', $allowedImageExtensions).')';
                    }
                }

                continue;
            }

            if (!$payload->has($key)) {
                continue;
            }

            $value = trim((string) $payload->get($key));
            if ('' === $value) {
                continue;
            }

            $error = match ($field['valueType']) {
                ExtraField::FIELD_TYPE_MOBILE_PHONE_NUMBER => preg_match('/^\d{11}$/', self::filterMobilePhoneNumber($value))
                    ? null
                    : $this->translator->trans('Mobile phone number is incomplete or contains invalid characters'),
                ExtraField::FIELD_TYPE_LETTERS_ONLY => preg_match('/^[a-zA-ZñÑ]+$/', $value)
                    ? null
                    : $this->translator->trans('Only letters'),
                ExtraField::FIELD_TYPE_ALPHANUMERIC => preg_match('/^[a-zA-Z0-9ñÑ]+$/', $value)
                    ? null
                    : $this->translator->trans('Only letters (a-z) and numbers (0-9)'),
                ExtraField::FIELD_TYPE_LETTERS_SPACE => preg_match('/^[a-zA-ZñÑ\s]+$/', $value)
                    ? null
                    : $this->translator->trans('Only letters and spaces'),
                ExtraField::FIELD_TYPE_ALPHANUMERIC_SPACE => preg_match('/^[a-zA-Z0-9ñÑ\s]+$/', $value)
                    ? null
                    : $this->translator->trans('Only letters, numbers and spaces'),
                ExtraField::FIELD_TYPE_DURATION => preg_match('/^\d+:[0-5]?\d:[0-5]?\d$/', $value)
                    ? null
                    : $this->translator->trans('Invalid format'),
                default => null,
            };

            if (null !== $error) {
                $errors[$field['variable']] = $error;
            }
        }

        return $errors;
    }

    /**
     * Mirrors FormValidator's mobile_phone_number_filter(): strips '+', '(', ')'
     * and left-trims leading zeros, before the exactly-11-digits rule is checked.
     */
    private static function filterMobilePhoneNumber(string $value): string
    {
        return ltrim(str_replace(['+', '(', ')'], '', $value), '0');
    }

    /**
     * Mirrors the inline QuickForm filter ExtraField::addElements() attaches to
     * FIELD_TYPE_DURATION: "hh:mm:ss" text converted to a raw integer-seconds
     * string (or '0' when the text doesn't match, same as the legacy filter).
     */
    private static function durationToSeconds(string $value): string
    {
        if (!preg_match('/^(\d+):([0-5]?\d):([0-5]?\d)$/', $value, $matches)) {
            return '0';
        }

        return (string) ((int) $matches[1] * 3600 + (int) $matches[2] * 60 + (int) $matches[3]);
    }

    /**
     * Inverse of durationToSeconds(), mirroring ExtraField::formatDuration()
     * (private in that class, so re-implemented here rather than exposed).
     */
    private static function secondsToDuration(int $seconds): string
    {
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $remainingSeconds = $seconds % 60;

        return \sprintf('%02d:%02d:%02d', $hours, $minutes, $remainingSeconds);
    }

    /**
     * Removes an existing FILE/FILE_IMAGE extra field value for one user, used
     * when the edit form's "delete" action clears a previously uploaded file.
     * Only the ExtraFieldValues row is removed -- the underlying Asset (and its
     * stored file) is left in place, matching this codebase's general policy
     * against deleting files outright.
     */
    private function deleteExtraFieldValue(int $userId, string $variable): void
    {
        $fieldInfo = (new ExtraField('user'))->get_handler_field_info_by_field_variable($variable);
        if (!$fieldInfo) {
            return;
        }

        $repository = $this->entityManager->getRepository(ExtraFieldValues::class);
        $row = $repository->findOneBy(['itemId' => $userId, 'field' => $fieldInfo['id']]);
        if ($row instanceof ExtraFieldValues) {
            $this->entityManager->remove($row);
            $this->entityManager->flush();
        }
    }

    /**
     * @return array<string, mixed> extra_<variable> => current value (string, or list<string> for multi-value fields)
     */
    private function buildExtraFieldValues(int $userId): array
    {
        // Deliberately NOT ExtraFieldValue::getAllValuesByItem(): it intersects its
        // result with ExtraField::get_all(['filter = ?' => 1]), and every extra_field
        // row on this platform (like most) has filter=0 -- that call always returns
        // an empty array here, silently dropping every existing value regardless of
        // type. getAllValuesByItemAndField() (per-field, no such intersection) is the
        // correct primitive; looping it once per field is one query per field, which
        // is fine for an admin edit page loaded once per visit.
        $extraFieldValue = new ExtraFieldValue('user');
        $fileValueTypes = [ExtraField::FIELD_TYPE_FILE_IMAGE, ExtraField::FIELD_TYPE_FILE];

        $values = [];
        foreach ((new ExtraField('user'))->get_all([], 'option_order') as $field) {
            $variable = $field['variable'];
            $valueType = (int) $field['value_type'];

            // FIELD_TYPE_TAG never has a row in the generic extra_field_values table --
            // tags live in their own tag/user_rel_tag tables instead, exactly like the
            // legacy page's own get_handler_extra_data() special-cases this same type.
            if (ExtraField::FIELD_TYPE_TAG === $valueType) {
                $values[$variable] = array_values(array_map(
                    static fn (array $tag): string => $tag['tag'],
                    UserManager::get_user_tags($userId, (int) $field['id']) ?: []
                ));

                continue;
            }

            $rows = $extraFieldValue->getAllValuesByItemAndField($userId, (int) $field['id']);
            if (!$rows) {
                continue;
            }

            // TAG already returned above; only SELECT_MULTIPLE/CHECKBOX remain here.
            if (\in_array($valueType, [ExtraField::FIELD_TYPE_SELECT_MULTIPLE, ExtraField::FIELD_TYPE_CHECKBOX], true)) {
                $values[$variable] = array_column($rows, 'field_value');

                continue;
            }

            $fieldValue = (string) $rows[0]['field_value'];

            // GEOLOCALIZATION(_COORDINATES): saved as "<address>::<lat,lng>"
            // (or just "<address>" if no coordinates were ever set).
            if (\in_array($valueType, [ExtraField::FIELD_TYPE_GEOLOCALIZATION, ExtraField::FIELD_TYPE_GEOLOCALIZATION_COORDINATES], true)) {
                $values[$variable] = array_pad(explode('::', $fieldValue, 2), 2, '');

                continue;
            }

            // DOUBLE_SELECT / SELECT_WITH_TEXT_FIELD: saved as "<first>::<second>".
            if (\in_array($valueType, [ExtraField::FIELD_TYPE_DOUBLE_SELECT, ExtraField::FIELD_TYPE_SELECT_WITH_TEXT_FIELD], true)) {
                $values[$variable] = array_pad(explode('::', $fieldValue, 2), 3, '');

                continue;
            }

            // TRIPLE_SELECT: saved as "<level1>;<level2>;<level3>".
            if (ExtraField::FIELD_TYPE_TRIPLE_SELECT === $valueType) {
                $values[$variable] = array_pad(explode(';', $fieldValue, 3), 3, '');

                continue;
            }

            // FILE_IMAGE/FILE: field_value is just a "1" marker -- the real,
            // usable value is the asset's resolved URL.
            if (\in_array($valueType, $fileValueTypes, true)) {
                $url = '';
                $assetId = $rows[0]['asset_id'] ?? null;
                $asset = $assetId ? $this->assetRepository->find($assetId) : null;
                if ($asset) {
                    $url = $this->assetRepository->getAssetUrl($asset);
                }
                $values[$variable] = $url;

                continue;
            }

            // DURATION: stored as raw integer seconds, displayed as "hh:mm:ss".
            if (ExtraField::FIELD_TYPE_DURATION === $valueType) {
                $values[$variable] = is_numeric($fieldValue) ? self::secondsToDuration((int) $fieldValue) : '';

                continue;
            }

            $values[$variable] = $fieldValue;
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
