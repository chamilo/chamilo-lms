<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Admin;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserAuthSource;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\AuthenticationConfigHelper;
use Chamilo\CoreBundle\Repository\Node\IllustrationRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Database;
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
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;
use UserManager;

use const FILTER_VALIDATE_EMAIL;
use const UPLOAD_ERR_OK;

/**
 * Data/action endpoints for the "Add a user" admin page, replacing
 * public/main/admin/user_add.php. See CLAUDE.md's "Playwright" and
 * "Legacy link locations" sections for the migration this belongs to.
 */
#[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_SESSION_MANAGER")'))]
final class UserAddController extends AbstractController
{
    /**
     * Mirrors user_add.php's own addEmailTemplate() call — the 4 template
     * "slots" a platform can override with a custom mail_template row.
     */
    private const array EMAIL_TEMPLATE_TYPES = [
        'subject_registration_platform.tpl',
        'content_registration_platform.tpl',
        'new_user_first_email_confirmation.tpl',
        'new_user_second_email_confirmation.tpl',
    ];

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly IllustrationRepository $illustrationRepository,
        private readonly AuthenticationConfigHelper $authenticationConfigHelper,
        private readonly AccessUrlHelper $accessUrlHelper,
        private readonly TranslatorInterface $translator,
        private readonly LoggerInterface $logger,
    ) {}

    #[Route('/admin/user-add-data', name: 'admin_user_add_data', methods: ['GET'])]
    public function data(): JsonResponse
    {
        if (null !== ($denied = $this->denyIfSessionAdminLimited())) {
            return $denied;
        }

        $isSessionAdmin = $this->isGranted('ROLE_SESSION_MANAGER') && !$this->isGranted('ROLE_ADMIN');
        $days = (int) api_get_setting('account_valid_duration');

        return $this->json([
            'westernNameOrder' => api_is_western_name_order(),
            'loginIsEmail' => 'true' === api_get_setting('login_is_email'),
            'emailRequired' => 'true' === api_get_setting('registration', 'email'),
            'hideNeverExpireOption' => 'true' === api_get_setting('registration.user_hide_never_expire_option') && !$this->isGranted('ROLE_ADMIN'),
            'adminsCanSetUsersPass' => 'true' === api_get_setting('security.admins_can_set_users_pass'),
            'defaultExpirationDate' => api_get_local_time('+'.$days.' day'),
            'defaultLocale' => api_get_language_isocode(),
            'redirectToAddAnotherAfterCreate' => $isSessionAdmin && 'true' === api_get_setting('session.limit_session_admin_list_users'),
            'authSources' => $this->buildAuthSources(),
            'roleOptions' => $this->buildRoleOptions(),
            'extraFields' => $this->buildExtraFieldDefinitions(),
            'emailTemplateTypes' => $this->buildEmailTemplateTypes(),
        ]);
    }

    #[Route('/admin/user-add-action', name: 'admin_user_add_action', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        if (null !== ($denied = $this->denyIfSessionAdminLimited())) {
            return $denied;
        }

        $payload = $request->request;

        $loginIsEmail = 'true' === api_get_setting('login_is_email');
        $hideNeverExpireOption = 'true' === api_get_setting('registration.user_hide_never_expire_option') && !$this->isGranted('ROLE_ADMIN');
        $adminsCanSetUsersPass = 'true' === api_get_setting('security.admins_can_set_users_pass');

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

        $username = $loginIsEmail ? $email : trim((string) $payload->get('username', ''));
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
        if (!$this->userRepository->isUsernameAvailable($username)) {
            return $this->json(['error' => $this->translator->trans('This login is already in use')], Response::HTTP_CONFLICT);
        }

        $roles = array_values(array_unique(array_map(
            'api_normalize_role_code',
            array_filter((array) $payload->all('roles'), 'strlen')
        )));
        if ([] === $roles || !UserManager::areRolesAllowedInUserForm($roles)) {
            return $this->json(['error' => $this->translator->trans('Error')], Response::HTTP_FORBIDDEN);
        }
        $status = api_status_from_roles($roles);

        $allowedAuthSources = array_map('strval', array_keys($this->authSourceMap()));
        $submittedAuthSources = array_values(array_intersect(
            array_map('strval', (array) $payload->all('authSource')),
            $allowedAuthSources
        ));
        if ([] === $submittedAuthSources) {
            return $this->json(['error' => $this->translator->trans('Required field')], Response::HTTP_BAD_REQUEST);
        }
        $hasPlatformAuth = \in_array(UserAuthSource::PLATFORM, $submittedAuthSources, true);

        if (!$hasPlatformAuth) {
            $password = 'PLACEHOLDER';
        } elseif ($adminsCanSetUsersPass && 'manual' === $payload->get('passwordMode')) {
            $password = (string) $payload->get('password', '');
            // Mirrors the legacy page's empty($password) check — for a string,
            // that's true for '' AND the literal '0', not just the empty string.
            if ('' === $password || '0' === $password) {
                return $this->json(['error' => $this->translator->trans('The password is too short')], Response::HTTP_BAD_REQUEST);
            }
            if ('true' === api_get_setting('security.check_password') && !api_check_password($password)) {
                return $this->json([
                    'error' => $this->translator->trans('this password  is too simple. Use a pass like this').': '.api_generate_password(),
                ], Response::HTTP_BAD_REQUEST);
            }
        } else {
            $password = api_generate_password();
        }

        // Mirrors the legacy page's unconditional server-side override: when
        // hideNeverExpireOption is on, the "Never expires" radio isn't even
        // rendered, so a client claiming hasExpirationDate=0 must be ignored,
        // not just defaulted around.
        $hasExpirationDate = $hideNeverExpireOption || '1' === (string) $payload->get('hasExpirationDate', '0');

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
        $locale = (string) $payload->get('locale', api_get_language_isocode());
        // Allowlist, tighter than the legacy page: only a real "active"/"inactive" toggle
        // is a legitimate value at creation time — never SOFT_DELETED or an arbitrary int.
        $active = User::ACTIVE === (int) $payload->get('active', User::ACTIVE) ? User::ACTIVE : User::INACTIVE;
        $sendMail = (int) $payload->get('sendMail', 0);
        $emailTemplate = (array) $payload->all('emailTemplateOption');

        $extraFieldErrors = $this->validateExtraFieldValues($request);
        if ([] !== $extraFieldErrors) {
            return $this->json([
                'error' => $this->translator->trans('One or more fields contain invalid values.'),
                'fieldErrors' => $extraFieldErrors,
            ], Response::HTTP_BAD_REQUEST);
        }

        // FIELD_TYPE_TAG's values are submitted as an array too (one entry per
        // chip the user committed) -- ExtraFieldValue::saveFieldValues() already
        // accepts either an array or a bare string for it, but only the array
        // form actually creates one tag per entry; a bare string (the old plain-
        // text fallback) got saved as a single tag containing the whole string.
        $multipleValueTypes = [
            ExtraField::FIELD_TYPE_SELECT_MULTIPLE,
            ExtraField::FIELD_TYPE_CHECKBOX,
            ExtraField::FIELD_TYPE_TAG,
        ];
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

        // FILE_IMAGE/FILE are deliberately kept out of $extra: UserManager::create_user()
        // itself saves $extra a first time as soon as the user row exists (see its own
        // is_array($extra) branch), and this controller saves it again explicitly below
        // to get a scoped try/catch. That double-save is harmless for scalar/array values
        // (same value written twice) but would create two separate Asset rows for a file
        // upload, so file fields are held back and merged in only for the second, explicit
        // call once $userId is known.
        $extra = [];
        $extraFiles = [];
        foreach ($this->buildExtraFieldDefinitions() as $field) {
            $key = 'extra_'.$field['variable'];
            $valueType = $field['valueType'];

            if (ExtraField::FIELD_TYPE_DIVIDER === $valueType) {
                continue;
            }

            if (\in_array($valueType, $multipleValueTypes, true)) {
                $values = $payload->all($key);
                if ([] !== $values) {
                    $extra[$key] = $values;
                }

                continue;
            }

            if (\in_array($valueType, $geolocationValueTypes, true)) {
                if ($payload->has($key)) {
                    $extra[$key] = $payload->get($key);
                }
                $coordinatesKey = $key.'_coordinates';
                if ($payload->has($coordinatesKey)) {
                    $extra[$coordinatesKey] = $payload->get($coordinatesKey);
                }

                continue;
            }

            if (\in_array($valueType, $groupedValueTypes, true)) {
                $group = (array) $payload->all($key);
                if ([] !== $group) {
                    $extra[$key] = $group;
                }

                continue;
            }

            if (\in_array($valueType, $fileValueTypes, true)) {
                $uploadedFile = $request->files->get($key);
                if ($uploadedFile instanceof UploadedFile) {
                    $extraFiles[$key] = [
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

        $userId = UserManager::create_user(
            $firstname,
            $lastname,
            $status,
            $email,
            $username,
            $password,
            $officialCode,
            $locale,
            $phone,
            null,
            $submittedAuthSources,
            $expirationDate,
            $active,
            0,
            $extra,
            '',
            (bool) $sendMail,
            false,
            '',
            false,
            null,
            0,
            $emailTemplate
        );

        if (!$userId) {
            return $this->json(['error' => $this->translator->trans('Error')], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $userEntity = $this->userRepository->find($userId);
        if ($userEntity instanceof User) {
            $userEntity->setRoles($roles);
            $this->userRepository->updateUser($userEntity);
        }

        $extra['item_id'] = $userId;
        foreach ($extraFiles as $fileKey => $fileArray) {
            $extra[$fileKey] = $fileArray;
        }

        try {
            (new ExtraFieldValue('user'))->saveFieldValues($extra);
        } catch (Throwable $exception) {
            // The user row itself is already created at this point -- only the
            // additional profile fields failed. Log the real cause (prod hides
            // it from the response) and say so plainly instead of leaking a
            // bare "Error" with no indication of what actually happened, which
            // is otherwise indistinguishable from a totally failed request.
            $this->logger->error('Failed to save extra field values for a newly created user.', [
                'userId' => $userId,
                'exception' => $exception,
            ]);

            return $this->json([
                'error' => $this->translator->trans('The user was created, but one of the additional profile fields could not be saved.'),
                'userId' => $userId,
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json([
            'success' => true,
            'userId' => $userId,
        ]);
    }

    #[Route('/admin/user-add-picture', name: 'admin_user_add_picture', methods: ['POST'])]
    public function uploadPicture(Request $request): JsonResponse
    {
        if (null !== ($denied = $this->denyIfSessionAdminLimited())) {
            return $denied;
        }

        $userId = (int) $request->request->get('user_id', 0);
        $user = $userId > 0 ? $this->userRepository->find($userId) : null;
        if (!$user instanceof User) {
            return $this->json(['error' => 'User not found.'], Response::HTTP_NOT_FOUND);
        }

        // This endpoint may only set a user's FIRST picture, never overwrite an
        // existing one: it is reachable by session admins right after they create
        // a user, and without this guard a session admin could otherwise use it to
        // overwrite an arbitrary existing user's photo just by supplying their id.
        if ($this->illustrationRepository->hasIllustration($user)) {
            return $this->json(['error' => 'This user already has a picture.'], Response::HTTP_CONFLICT);
        }

        $file = $request->files->get('file');
        if (!$file instanceof UploadedFile || UPLOAD_ERR_OK !== $file->getError()) {
            return $this->json(['error' => 'A valid image file is required.'], Response::HTTP_BAD_REQUEST);
        }

        UserManager::update_user_picture($userId, $file);

        return $this->json([
            'success' => true,
            // 'user_photo_medium' doesn't exist as a glide_media_filters entry (config/services.yaml)
            // — an unknown filter name silently falls back to unfiltered params, not an error, so this
            // went unnoticed until checked directly. 'user_picture_profile' (94x94, square) is the real,
            // existing filter for this — the only other one, 'user_picture_small' (48x48), is used for
            // list-row thumbnails, too small for a just-uploaded confirmation preview.
            'url' => $this->illustrationRepository->getIllustrationUrl($user, 'user_picture_profile'),
        ]);
    }

    private function denyIfSessionAdminLimited(): ?JsonResponse
    {
        $isSessionAdmin = $this->isGranted('ROLE_SESSION_MANAGER') && !$this->isGranted('ROLE_ADMIN');
        if ($isSessionAdmin && 'true' === api_get_setting('limit_session_admin_role')) {
            return $this->json(['error' => 'Access denied.'], Response::HTTP_FORBIDDEN);
        }

        return null;
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
