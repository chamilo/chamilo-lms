<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\ExtraField as EntityExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldValues as EntityExtraFieldValues;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\Session as SessionEntity;
use Chamilo\CoreBundle\Entity\SessionRelCourse;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserAuthSource;
use Chamilo\CoreBundle\Entity\UserRelUser;
use Chamilo\CoreBundle\Event\AbstractEvent;
use Chamilo\CoreBundle\Event\Events;
use Chamilo\CoreBundle\Event\UserCreatedEvent;
use Chamilo\CoreBundle\Event\UserUpdatedEvent;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Repository\LanguageRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Helpers\NameConventionHelper;
use ChamiloSession as Session;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * This library provides functions for user management.
 * Include/require it in your code to use its functionality.
 *
 * @author Julio Montoya <gugli100@gmail.com> Social network groups added 2009/12
 */
class UserManager
{
    // This constants are deprecated use the constants located in ExtraField
    public const USER_FIELD_TYPE_TEXT = 1;
    public const USER_FIELD_TYPE_TEXTAREA = 2;
    public const USER_FIELD_TYPE_RADIO = 3;
    public const USER_FIELD_TYPE_SELECT = 4;
    public const USER_FIELD_TYPE_SELECT_MULTIPLE = 5;
    public const USER_FIELD_TYPE_DATE = 6;
    public const USER_FIELD_TYPE_DATETIME = 7;
    public const USER_FIELD_TYPE_DOUBLE_SELECT = 8;
    public const USER_FIELD_TYPE_DIVIDER = 9;
    public const USER_FIELD_TYPE_TAG = 10;
    public const USER_FIELD_TYPE_TIMEZONE = 11;
    public const USER_FIELD_TYPE_SOCIAL_PROFILE = 12;
    public const USER_FIELD_TYPE_FILE = 13;
    public const USER_FIELD_TYPE_MOBILE_PHONE_NUMBER = 14;

    public function __construct()
    {
    }

    /**
     * Validates the password.
     */
    public static function isPasswordValid(User $user, string $plainPassword): bool
    {
        /**
         * @psalm-suppress PrivateService
         */
        $hasher = Container::$container->get('security.user_password_hasher');

        return $hasher->isPasswordValid($user, $plainPassword);
    }

    /**
     * @param int    $userId
     * @param string $password
     */
    public static function updatePassword($userId, $password)
    {
        $user = api_get_user_entity($userId);
        $user->setPlainPassword($password);
        Container::getUserRepository()->updateUser($user, true);
    }

    /**
     * Creates a new user for the platform.
     *
     * @param string        $firstName
     * @param string        $lastName
     * @param int           $status                  (1 for course tutor, 5 for student, 6 for anonymous)
     * @param string        $email
     * @param string        $loginName
     * @param string        $password
     * @param string        $officialCode           Any official code (optional)
     * @param string        $language                User language    (optional)
     * @param string        $phone                   Phone number    (optional)
     * @param string        $pictureUri             Picture URI        (optional)
     * @param string        $authSources              Authentication source (defaults to 'platform', dependind on constant)
     * @param string $expirationDate          Account expiration date (optional, defaults to null)
     * @param int           $active                  Whether the account is enabled or disabled by default
     * @param int           $hrDeptId              The department of HR in which the user is registered (defaults to 0)
     * @param array         $extra                   Extra fields (labels must be prefixed by "extra_")
     * @param string        $encryptMethod          Used if password is given encrypted. Set to an empty string by default
     * @param bool          $sendMail
     * @param bool          $isAdmin
     * @param string        $address
     * @param bool          $sendEmailToAllAdmins
     * @param FormValidator $form
     * @param int           $creatorId
     * @param array         $emailTemplate
     * @param string        $redirectToURLAfterLogin
     *
     * @return mixed new user id - if the new user creation succeeds, false otherwise
     * @desc The function tries to retrieve user id from the session.
     * If it exists, the current user id is the creator id. If a problem arises,
     * @assert ('Sam','Gamegie',5,'sam@example.com','jo','jo') > 1
     * @assert ('Pippin','Took',null,null,'jo','jo') === false
     *@author Hugues Peeters <peeters@ipm.ucl.ac.be>,
     * @author Roan Embrechts <roan_embrechts@yahoo.com>
     *
     */
    public static function create_user(
        $firstName,
        $lastName,
        $status,
        $email,
        $loginName,
        $password,
        $officialCode = '',
        $language = '',
        $phone = '',
        $pictureUri = '',
        ?array $authSources = [],
        $expirationDate = null,
        $active = 1,
        $hrDeptId = 0,
        $extra = [],
        $encryptMethod = '',
        $sendMail = false,
        $isAdmin = false,
        $address = '',
        $sendEmailToAllAdmins = false,
        $form = null,
        $creatorId = 0,
        $emailTemplate = [],
        $redirectToURLAfterLogin = ''
    ) {
        $authSources = !empty($authSources) ? $authSources : [UserAuthSource::PLATFORM];
        $creatorId = empty($creatorId) ? api_get_user_id() : $creatorId;

        if (0 === $creatorId) {
            Display::addFlash(
                Display::return_message(get_lang('A user creator is required'))
            );

            return false;
        }

        $creatorInfo = api_get_user_info($creatorId);
        $creatorEmail = $creatorInfo['email'] ?? '';

        // First check if the login exists.
        if (!Container::getUserRepository()->isUsernameAvailable($loginName)) {
            Display::addFlash(
                Display::return_message(get_lang('This login is already taken !'))
            );

            return false;
        }

        Container::getEventDispatcher()
            ->dispatch(
                new UserCreatedEvent([], AbstractEvent::TYPE_PRE),
                Events::USER_CREATED
            )
        ;

        $original_password = $password;

        $accessUrl = Container::getAccessUrlUtil()->getCurrent();
        $access_url_id = $accessUrl->getId();

        $hostingLimitUsers = get_hosting_limit($access_url_id, 'users');

        if ($hostingLimitUsers !== null && $hostingLimitUsers > 0) {
            $num = self::get_number_of_users();
            if ($num >= $hostingLimitUsers) {
                api_warn_hosting_contact('users');
                Display::addFlash(
                    Display::return_message(
                        get_lang('Sorry, this installation has a users limit, which has now been reached. To increase the number of users allowed on this Chamilo installation, please contact your hosting provider or, if available, upgrade to a superior hosting plan.'),
                        'warning'
                    )
                );

                return false;
            }
        }

        if (1 === $status) {
            $hostingLimitTeachers = get_hosting_limit($access_url_id, 'teachers');

            if ($hostingLimitTeachers !== null && $hostingLimitTeachers > 0) {
                $num = self::get_number_of_users(1);
                if ($num >= $hostingLimitTeachers) {
                    Display::addFlash(
                        Display::return_message(
                            get_lang('Sorry, this installation has a teachers limit, which has now been reached. To increase the number of teachers allowed on this Chamilo installation, please contact your hosting provider or, if available, upgrade to a superior hosting plan.'),
                            'warning'
                        )
                    );
                    api_warn_hosting_contact('hosting_limit_teachers');

                    return false;
                }
            }
        }

        if (empty($password)) {
            if (in_array(UserAuthSource::PLATFORM, $authSources)) {
                Display::addFlash(
                    Display::return_message(
                        get_lang('Required field').': '.get_lang('Password'),
                        'warning'
                    )
                );

                return false;
            }
            // Use authSource as pseudo-password (validated by external auth).
            $password = $authSources;
        }

        // Checking the user language
        $languages = api_get_languages();
        if (!in_array($language, array_keys($languages), true)) {
            $language = 'en_US'; // default
        }

        $now = new DateTime();
        if (empty($expirationDate) || '0000-00-00 00:00:00' === $expirationDate) {
            $expirationDate = null;
        } else {
            $expirationDate = api_get_utc_datetime($expirationDate, true, true);
        }

        $repo = Container::getUserRepository();
        $user = $repo->createUser()
            ->setLastname($lastName)
            ->setFirstname($firstName)
            ->setUsername($loginName)
            ->setStatus($status)
            ->setPlainPassword($password)
            ->setEmail($email)
            ->setOfficialCode($officialCode)
            ->setCreatorId($creatorId)
            ->setPhone($phone)
            ->setAddress($address)
            ->setLocale($language)
            ->setHrDeptId($hrDeptId)
            ->setActive($active)
            ->setTimezone(api_get_timezone())
        ;

        foreach ($authSources as $authSource) {
            $user->addAuthSourceByAuthentication($authSource, $accessUrl);
        }

        if (null !== $expirationDate) {
            $user->setExpirationDate($expirationDate);
        }

        $user->setRoleFromStatus($status);
        $dobStr = $_POST['date_of_birth'] ?? null;
        if ($dobStr) {
            $dob = \DateTime::createFromFormat('Y-m-d', $dobStr)
                ?: \DateTime::createFromFormat('d/m/Y', $dobStr)
                    ?: \DateTime::createFromFormat('d-m-Y', $dobStr);

            if ($dob instanceof \DateTime) {
                $user->setDateOfBirth($dob);
            }
        }

        $repo->updateUser($user, true);
        $userId = $user->getId();

        if (empty($userId)) {
            Display::addFlash(
                Display::return_message(get_lang('There happened an unknown error. Please contact the platform administrator.'))
            );
            return false;
        }

        $userLocale = $user->getLocale();
        if ($isAdmin) {
            self::addUserAsAdmin($user);
        }

        if (api_get_multiple_access_url()) {
            UrlManager::add_user_to_url($userId, api_get_current_access_url_id());
        } else {
            UrlManager::add_user_to_url($userId, 1);
        }

        if (is_array($extra) && count($extra) > 0) {
            $extra['item_id'] = $userId;
            $userFieldValue = new ExtraFieldValue('user');
            /* Force saving of extra fields (otherwise fields not visible are ignored) */
            $userFieldValue->saveFieldValues(
                $extra,
                true,
                false,
                [],
                [],
                true
            );
        } else {
            // Create notify settings by default
            self::update_extra_field_value($userId, 'mail_notify_invitation', '1');
            self::update_extra_field_value($userId, 'mail_notify_message', '1');
            self::update_extra_field_value($userId, 'mail_notify_group_message', '1');
        }

        self::update_extra_field_value($userId, 'already_logged_in', 'false');

        if (!empty($redirectToURLAfterLogin) && ('true' === api_get_setting('workflows.plugin_redirection_enabled'))) {
            RedirectionPlugin::insert($userId, $redirectToURLAfterLogin);
        }

        if (!empty($email) && $sendMail) {
            $recipient_name = api_get_person_name(
                $firstName,
                $lastName,
                null,
                PERSON_NAME_EMAIL_ADDRESS
            );
            $tpl = Container::getTwig();

            $emailSubject = $tpl->render(
                '@ChamiloCore/Mailer/Legacy/subject_registration_platform.html.twig',
                ['locale' => $userLocale]
            );

            $sender_name = api_get_setting('mail.mailer_from_name');
            $email_admin = api_get_setting('mail.mailer_from_email');

            $url = api_get_path(WEB_PATH);
            if (api_is_multiple_url_enabled()) {
                $access_url_id = api_get_current_access_url_id();
                if (-1 != $access_url_id) {
                    $urlInfo = api_get_access_url($access_url_id);
                    if ($urlInfo) {
                        $url = $urlInfo['url'];
                    }
                }
            }

            // Variables for the default template
            $params = [
                'complete_name' => stripslashes(api_get_person_name($firstName, $lastName)),
                'login_name' => $loginName,
                'original_password' => stripslashes((string) $original_password),
                'mailWebPath' => $url,
                'new_user' => $user,
                'search_link' => $url,
                'locale' => $userLocale,
            ];

            if ('true' === api_get_setting('session.allow_search_diagnostic')) {
                $urlSearch = api_get_path(WEB_CODE_PATH).'search/search.php';
                $linkSearch = Display::url($urlSearch, $urlSearch);
                $params['search_link'] = $linkSearch;
            }

            // Default Twig bodies: one for email (with password) and one for inbox (without password)
            $emailBodyEmail = $tpl->render(
                '@ChamiloCore/Mailer/Legacy/content_registration_platform.html.twig',
                $params + ['show_password' => true]
            );
            $emailBodyInbox = $tpl->render(
                '@ChamiloCore/Mailer/Legacy/content_registration_platform.html.twig',
                $params + ['show_password' => false]
            );

            $userInfo = api_get_user_info($userId);
            $mailTemplateManager = new MailTemplateManager();

            $emailBodyTemplate = '';
            if (!empty($emailTemplate)) {
                if (isset($emailTemplate['content_registration_platform.tpl']) &&
                    !empty($emailTemplate['content_registration_platform.tpl'])
                ) {
                    $emailBodyTemplate = $mailTemplateManager->parseTemplate(
                        $emailTemplate['content_registration_platform.tpl'],
                        $userInfo
                    );
                }
            }

            // If a custom email template is provided, use it only for the email (inbox copy stays sanitized Twig)
            if (!empty($emailBodyTemplate)) {
                $emailBodyEmail = $emailBodyTemplate;
            }

            $twoEmail = ('true' === api_get_setting('mail.send_two_inscription_confirmation_mail'));

            if (true === $twoEmail) {
                // Keep existing 2-email behavior (no structural changes)
                $emailBody = $tpl->render('@ChamiloCore/Mailer/Legacy/new_user_first_email_confirmation.html.twig');
                if (!empty($emailBodyTemplate) &&
                    isset($emailTemplate['new_user_first_email_confirmation.tpl']) &&
                    !empty($emailTemplate['new_user_first_email_confirmation.tpl'])
                ) {
                    $emailBody = $mailTemplateManager->parseTemplate(
                        $emailTemplate['new_user_first_email_confirmation.tpl'],
                        $userInfo
                    );
                }

                api_mail_html(
                    $recipient_name,
                    $email,
                    $emailSubject,
                    $emailBody,
                    $sender_name,
                    $email_admin,
                    [],
                    [],
                    false,
                    [],
                    $creatorEmail
                );

                $emailBody = $tpl->render('@ChamiloCore/Mailer/Legacy/new_user_second_email_confirmation.html.twig');
                if (!empty($emailBodyTemplate) &&
                    isset($emailTemplate['new_user_second_email_confirmation.tpl']) &&
                    !empty($emailTemplate['new_user_second_email_confirmation.tpl'])
                ) {
                    $emailBody = $mailTemplateManager->parseTemplate(
                        $emailTemplate['new_user_second_email_confirmation.tpl'],
                        $userInfo
                    );
                }

                api_mail_html(
                    $recipient_name,
                    $email,
                    $emailSubject,
                    $emailBody,
                    $sender_name,
                    $email_admin,
                    [],
                    [],
                    false,
                    [],
                    $creatorEmail
                );

                // Optional inbox copy (sanitized, and no email notification)
                $sendToInbox = ('true' === api_get_setting('registration.send_inscription_msg_to_inbox'));
                if ($sendToInbox) {
                    $adminList = self::get_all_administrators();
                    $senderId = 1;
                    if (!empty($adminList)) {
                        $adminInfo = current($adminList);
                        $senderId = $adminInfo['user_id'];
                    }

                    MessageManager::send_message_simple(
                        $userId,
                        $emailSubject,
                        $emailBodyInbox,
                        $senderId,
                        false,
                        false,
                        false,
                        [],
                        false
                    );
                }
            } else {
                // 1) Always send the registration email
                api_mail_html(
                    $recipient_name,
                    $email,
                    $emailSubject,
                    $emailBodyEmail,
                    $sender_name,
                    $email_admin,
                    [],
                    [],
                    false,
                    [],
                    $creatorEmail
                );

                // 2) Optionally copy to Chamilo inbox (sanitized, no email notification)
                $sendToInbox = ('true' === api_get_setting('registration.send_inscription_msg_to_inbox'));
                if ($sendToInbox) {
                    $adminList = self::get_all_administrators();
                    $senderId = 1;
                    if (!empty($adminList)) {
                        $adminInfo = current($adminList);
                        $senderId = $adminInfo['user_id'];
                    }

                    MessageManager::send_message_simple(
                        $userId,
                        $emailSubject,
                        $emailBodyInbox,
                        $senderId,
                        false,
                        false,
                        false,
                        [],
                        false
                    );
                }
            }

            // Admin notifications (keep behavior; use the email version as the reference body)
            $notification = api_get_setting('profile.send_notification_when_user_added', true);
            if (!empty($notification) && isset($notification['admins']) && is_array($notification['admins'])) {
                foreach ($notification['admins'] as $adminId) {
                    $emailSubjectToAdmin = get_lang('The user has been added').': '.
                        api_get_person_name($firstName, $lastName);

                    MessageManager::send_message_simple(
                        $adminId,
                        $emailSubjectToAdmin,
                        $emailBodyEmail,
                        $userId
                    );
                }
            }

            /** @var TranslatorInterface $translator */
            $translator   = Container::$container->get('translator');
            $currentLocale = $translator->getLocale();

            $visibleCoreFields = [];
            $visibleExtraVars  = [];

            if ($form) {
                $formNames = [];
                if (property_exists($form, '_elements') && is_array($form->_elements)) {
                    foreach ($form->_elements as $el) {
                        if (is_object($el) && method_exists($el, 'getName')) {
                            $formNames[] = (string) $el->getName();
                        }
                    }
                }

                $cfg = api_get_setting('registration.allow_fields_inscription', true);
                $cfgCore = (is_array($cfg) && isset($cfg['fields']) && is_array($cfg['fields']))
                    ? $cfg['fields']
                    : [];

                $visibleCoreFields = array_values(array_intersect($cfgCore, $formNames));

                foreach ($formNames as $n) {
                    if (strpos($n, 'extra_') === 0) {
                        $visibleExtraVars[] = substr($n, 6);
                    }
                }
            }

            if ($sendEmailToAllAdmins) {
                $adminList = self::get_all_administrators();
                $url = api_get_path(WEB_CODE_PATH).'admin/user_information.php?user_id='.$user->getId();

                foreach ($adminList as $adminId => $adminData) {
                    $adminLocale = $adminData['locale'] ?? 'en_US';
                    $translator->setLocale($adminLocale);

                    $profileHtml      = self::renderRegistrationProfileHtml(
                        $user,
                        $extra ?? [],
                        $adminLocale,
                        $visibleCoreFields,
                        $visibleExtraVars
                    );
                    $userLanguageName = self::resolveLanguageName($user->getLocale());

                    $paramsAdmin = [
                        'complete_name' => stripslashes(api_get_person_name($firstName, $lastName)),
                        'user_added'    => $user,
                        'link'          => Display::url($url, $url),
                        'form'          => $profileHtml,
                        'user_language' => $userLanguageName,
                    ];

                    $emailBodyAdmin = $tpl->render(
                        '@ChamiloCore/Mailer/Legacy/content_registration_platform_to_admin.html.twig',
                        $paramsAdmin
                    );

                    $subject = get_lang('The user has been added', $adminLocale);
                    MessageManager::send_message_simple(
                        $adminId,
                        $subject,
                        $emailBodyAdmin,
                        $userId
                    );

                    $translator->setLocale($currentLocale);
                }
            }
        }

        Container::getEventDispatcher()
            ->dispatch(
                new UserCreatedEvent(
                    ['return' => $user, 'originalPassword' => $original_password],
                    AbstractEvent::TYPE_POST
                ),
                Events::USER_CREATED
            )
        ;

        Event::addEvent(LOG_USER_CREATE, LOG_USER_ID, $userId, null, $creatorId);

        return $userId;
    }

    /**
     * Returns the human-readable language name for a given ISO code.
     *
     * Accepts ISO 639-1/2 codes (e.g. "en", "es", "eng"). If $iso is null or the
     * code is unknown, an empty string is returned.
     *
     * @param string|null $iso Two- or three-letter ISO 639 language code.
     * @return string          Language name in English (e.g. "English", "Spanish").
     */
    private static function resolveLanguageName(?string $iso): string
    {
        if (empty($iso)) {
            return '';
        }

        /** @var LanguageRepository $langRepo */
        $langRepo = Container::$container->get(LanguageRepository::class);
        $entity   = $langRepo->findOneBy(['isocode' => $iso]);

        return $entity ? $entity->getOriginalName() : $iso;
    }

    /**
     * Build the “profile” HTML (core + dynamic extra fields) for the admin email, located in $adminLocale.
     *
     * @param User $user
     * @param array $extraParams Raw POST values from registration (keys: "extra_*").
     * @param string $adminLocale e.g. "es_ES", "fr_FR".
     * @param array $visibleCoreFields Visible core names (email, firstname, status, date_of_birth, etc.)
     * @param array $visibleExtraVars Visible extra variables (without the "extra_" prefix)
     */
    private static function renderRegistrationProfileHtml(
        User $user,
        array $extraParams,
        string $adminLocale,
        array $visibleCoreFields = [],
        array $visibleExtraVars = []
    ): string {
        $showAllCore  = empty($visibleCoreFields);
        $showAllExtra = empty($visibleExtraVars);

        $coreVisible = array_fill_keys($visibleCoreFields, true);
        $extraVisible = array_fill_keys($visibleExtraVars, true);

        $languageName = self::resolveLanguageName($user->getLocale());

        $corePairs = [];

        if ($showAllCore || !empty($coreVisible['email'])) {
            $corePairs[get_lang('E-mail', $adminLocale)] = $user->getEmail();
        }
        if ($showAllCore || !empty($coreVisible['firstname'])) {
            $corePairs[get_lang('First name', $adminLocale)] = (string) $user->getFirstname();
        }
        if ($showAllCore || !empty($coreVisible['lastname'])) {
            $corePairs[get_lang('Last name', $adminLocale)] = (string) $user->getLastname();
        }
        if ($showAllCore || !empty($coreVisible['username'])) {
            $corePairs[get_lang('Username', $adminLocale)] = $user->getUsername();
        }
        if ($showAllCore || !empty($coreVisible['official_code'])) {
            $corePairs[get_lang('Official code', $adminLocale)] = $user->getOfficialCode() ?? '';
        }
        if ($showAllCore || !empty($coreVisible['phone'])) {
            $corePairs[get_lang('Phone', $adminLocale)] = $user->getPhone() ?? '';
        }
        if ($showAllCore || !empty($coreVisible['address'])) {
            $corePairs[get_lang('User address', $adminLocale)] = $user->getAddress() ?? '';
        }
        if ($showAllCore || !empty($coreVisible['language'])) {
            $corePairs[get_lang('Language', $adminLocale)] = $languageName;
        }

        if ($showAllCore || !empty($coreVisible['status'])) {
            $statusLabel = ((int) $user->getStatus() === COURSEMANAGER)
                ? get_lang('Teach courses', $adminLocale)
                : get_lang('Follow courses', $adminLocale);
            $corePairs[get_lang('What do you want to do?', $adminLocale)] = $statusLabel;
        }

        if (($showAllCore || !empty($coreVisible['date_of_birth'])) &&
            $user->getDateOfBirth() instanceof \DateTimeInterface
        ) {
            $corePairs[get_lang('Date of birth', $adminLocale)] = $user->getDateOfBirth()->format('Y-m-d');
        }

        $efv = new \ExtraFieldValue('user');
        $ef  = new \ExtraField('user');

        $extraPairs = [];
        $presentVars = [];
        $rows = $efv->getAllValuesByItem((int) $user->getId());

        if (is_array($rows)) {
            foreach ($rows as $row) {
                $fieldId   = (int)$row['id'];
                $variable  = (string)$row['variable'];
                $presentVars[$variable] = true;

                if (!$showAllExtra && empty($extraVisible[$variable])) {
                    continue;
                }

                $tr = $efv->get_values_by_handler_and_field_id((int) $user->getId(), $fieldId, true);
                $val = null;

                if ($tr && array_key_exists('value', $tr)) {
                    $val = $tr['value'];
                } else {
                    $val = $row['value'];
                }

                $type = (int)$row['value_type'];
                if ($type === \ExtraField::FIELD_TYPE_CHECKBOX) {
                    $val = ((string)$val === '1') ? get_lang('Yes', $adminLocale) : get_lang('No', $adminLocale);
                }
                if ($type === \ExtraField::FIELD_TYPE_TAG && is_array($val)) {
                    $val = implode(', ', array_map(fn($t) => is_array($t) ? (string)($t['value'] ?? '') : (string)$t, $val));
                }
                if (is_string($val)) {
                    $val = str_replace(['<br />','<br>','<br/>'], ', ', $val);
                }

                $label = !empty($row['display_text'])
                    ? get_lang($row['display_text'], $adminLocale)
                    : get_lang(ucwords(str_replace('_',' ',$variable)), $adminLocale);

                if ($val !== '' && $val !== null) {
                    $extraPairs[$label] = (string)$val;
                }
            }
        }

        foreach ($extraParams as $k => $v) {
            if (strpos($k, 'extra_') !== 0) continue;
            $variable = substr($k, 6);
            if (isset($presentVars[$variable])) continue;
            if (!$showAllExtra && empty($extraVisible[$variable])) continue;

            $def = $ef->get_handler_field_info_by_field_variable($variable);
            $label = $def && !empty($def['display_text'])
                ? get_lang($def['display_text'], $adminLocale)
                : get_lang(ucwords(str_replace('_',' ',$variable)), $adminLocale);

            $val = $v;
            if (is_array($val)) {
                $val = implode(', ', array_map('strval', $val));
            } elseif ($val === '1') {
                $val = get_lang('Yes', $adminLocale);
            } elseif ($val === '0') {
                $val = get_lang('No', $adminLocale);
            }

            if ($val !== '' && $val !== null) {
                $extraPairs[$label] = (string)$val;
            }
        }

        $html = '<div class="form-horizontal">';
        foreach ($corePairs as $k => $v) {
            if ($v === '' || $v === null) continue;
            $html .= '<div>'.$k.': '.\Security::remove_XSS((string)$v).'</div>';
        }
        foreach ($extraPairs as $k => $v) {
            $html .= '<div>'.$k.': '.\Security::remove_XSS((string)$v).'</div>';
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * Can user be deleted? This function checks whether there's a course
     * in which the given user is the
     * only course administrator. If that is the case, the user can't be
     * deleted because the course would remain without a course admin.
     *
     * @param int $user_id The user id
     *
     * @return bool true if user can be deleted
     *
     * @assert (null) === false
     * @assert (-1) === false
     * @assert ('abc') === false
     */
    public static function canDeleteUser($user_id)
    {
        $deny = api_get_env_variable('DENY_DELETE_USERS', false);

        if ($deny) {
            return false;
        }

        $table_course_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $user_id = (int) $user_id;

        if (empty($user_id)) {
            return false;
        }

        $sql = "SELECT * FROM $table_course_user
                WHERE status = 1 AND user_id = ".$user_id;
        $res = Database::query($sql);
        while ($course = Database::fetch_object($res)) {
            $sql = "SELECT id FROM $table_course_user
                    WHERE status=1 AND c_id = ".intval($course->c_id);
            $res2 = Database::query($sql);
            if (1 == Database::num_rows($res2)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Deletes a user from the system or marks the user as deleted based on the $destroy flag.
     * If $destroy is false, the user is only marked as deleted (e.g., active = -1) but not actually removed from the database.
     * This allows for the possibility of restoring the user at a later time. If $destroy is true, the user and all their relations
     * are permanently removed from the database.
     *
     * Note: When $destroy is false, the user's relations are not removed, allowing for potential restoration. When $destroy is true,
     * the function proceeds to remove all the user's relations, effectively cleaning up all references to the user in the system.
     */
    public static function delete_user(int $user_id, bool $destroy = false): bool
    {
        $user_id = (int) $user_id;

        if (empty($user_id)) {
            return false;
        }

        if (!self::canDeleteUser($user_id)) {
            return false;
        }

        $repository = Container::getUserRepository();

        /** @var User $user */
        $user = $repository->find($user_id);

        if (!$user) {
            return false;
        }

        $fallbackUser = $repository->getFallbackUser();
        $fallbackId = (int) $fallbackUser->getId();
        $affectedIds = [];
        if ($destroy && $fallbackId && $fallbackId !== $user_id) {
            $em = Database::getManager();
            $rows = $em->createQuery(
                'SELECT u.id
                     FROM ' . User::class . ' u
                     WHERE u.creatorId = :oldCreator AND u.id <> :fallbackId'
            )
                ->setParameter('oldCreator', $user_id)
                ->setParameter('fallbackId', $fallbackId)
                ->getScalarResult();

            $affectedIds = array_map(
                static fn(array $r): int => (int) $r['id'],
                $rows
            );

            $em->createQuery(
                'UPDATE ' . User::class . ' u
                 SET u.creatorId = :newCreator
                 WHERE u.creatorId = :oldCreator AND u.id <> :fallbackId'
            )
                ->setParameter('newCreator', $fallbackId)
                ->setParameter('oldCreator', $user_id)
                ->setParameter('fallbackId', $fallbackId)
                ->execute();
        }

        $repository->deleteUser($user, $destroy);

        if ($destroy) {
            Event::addEvent(
                LOG_USER_DELETE,
                LOG_USER_OBJECT,
                api_get_user_info($user_id),
                api_get_utc_datetime(),
                api_get_user_id()
            );

            // Log one event per affected user AFTER the deletion
            if (!empty($affectedIds) && $fallbackId && $fallbackId !== $user_id) {
                $nowUtc = api_get_utc_datetime();
                $actor  = api_get_user_id();
                foreach ($affectedIds as $affectedId) {
                    Event::addEvent(
                        LOG_USER_CREATOR_DELETED,
                        LOG_USER_ID,
                        [
                            'user_id'        => $affectedId,
                            'old_creator_id' => $user_id,
                            'new_creator_id' => $fallbackId,
                        ],
                        $nowUtc,
                        $actor
                    );
                }
            }
        }

        return true;
    }

    /**
     * Deletes users completely. Can be called either as:
     * - UserManager::delete_users(1, 2, 3); or
     * - UserManager::delete_users(array(1, 2, 3));.
     *
     * @param array|int $ids
     *
     * @return bool True if at least one user was successfuly deleted. False otherwise.
     *
     * @author Laurent Opprecht
     *
     * @uses \UserManager::delete_user() to actually delete each user
     * @assert (null) === false
     * @assert (-1) === false
     * @assert (array(-1)) === false
     */
    public static function delete_users($ids = [])
    {
        $result = false;
        $ids = is_array($ids) ? $ids : func_get_args();
        if (!is_array($ids) || 0 == count($ids)) {
            return false;
        }
        $ids = array_map('intval', $ids);
        foreach ($ids as $id) {
            if (empty($id) || $id < 1) {
                continue;
            }
            $deleted = self::delete_user($id);
            $result = $deleted || $result;
        }

        return $result;
    }

    /**
     * Disable users. Can be called either as:
     * - UserManager::deactivate_users(1, 2, 3);
     * - UserManager::deactivate_users(array(1, 2, 3));.
     *
     * @param array|int $ids
     *
     * @return bool
     *
     * @author Laurent Opprecht
     * @assert (null) === false
     * @assert (array(-1)) === false
     */
    public static function deactivate_users($ids = [])
    {
        if (empty($ids)) {
            return false;
        }

        $table_user = Database::get_main_table(TABLE_MAIN_USER);

        $ids = is_array($ids) ? $ids : func_get_args();
        $ids = array_map('intval', $ids);
        $ids = implode(',', $ids);

        $sql = "UPDATE $table_user SET active = 0 WHERE id IN ($ids)";
        $r = Database::query($sql);
        if (false !== $r) {
            Event::addEvent(LOG_USER_DISABLE, LOG_USER_ID, $ids);

            return true;
        }

        return false;
    }

    /**
     * Enable users. Can be called either as:
     * - UserManager::activate_users(1, 2, 3);
     * - UserManager::activate_users(array(1, 2, 3));.
     *
     * @param array|int IDs of the users to enable
     *
     * @return bool
     *
     * @author Laurent Opprecht
     * @assert (null) === false
     * @assert (array(-1)) === false
     */
    public static function activate_users($ids = [])
    {
        if (empty($ids)) {
            return false;
        }

        $table_user = Database::get_main_table(TABLE_MAIN_USER);

        $ids = is_array($ids) ? $ids : func_get_args();
        $ids = array_map('intval', $ids);
        $ids = implode(',', $ids);

        $sql = "UPDATE $table_user SET active = 1 WHERE id IN ($ids)";
        $r = Database::query($sql);
        if (false !== $r) {
            Event::addEvent(LOG_USER_ENABLE, LOG_USER_ID, $ids);

            return true;
        }

        return false;
    }

    /**
     * Update user information with all the parameters passed to this function.
     *
     * @param int    $user_id         The ID of the user to be updated
     * @param string $firstname       The user's firstname
     * @param string $lastname        The user's lastname
     * @param string $username        The user's username (login)
     * @param string $password        The user's password
     * @param string $auth_sources     The authentication source (default: "platform")
     * @param string $email           The user's e-mail address
     * @param int    $status          The user's status
     * @param string $official_code   The user's official code (usually just an internal institutional code)
     * @param string $phone           The user's phone number
     * @param string $picture_uri     The user's picture URL (internal to the Chamilo directory)
     * @param string $expiration_date The date at which this user will be automatically disabled
     * @param int    $active          Whether this account needs to be enabled (1) or disabled (0)
     * @param int    $creator_id      The user ID of the person who registered this user (optional, defaults to null)
     * @param int    $hr_dept_id      The department of HR in which the user is registered (optional, defaults to 0)
     * @param array  $extra           Additional fields to add to this user as extra fields (defaults to null)
     * @param string $language        The language to which the user account will be set
     * @param string $encrypt_method  The cipher method. This parameter is deprecated. It will use the system's default
     * @param bool   $send_email      Whether to send an e-mail to the user after the update is complete
     * @param int    $reset_password  Method used to reset password (0, 1, 2 or 3 - see usage examples for details)
     * @param string $address
     * @param array  $emailTemplate
     *
     * @return bool|int False on error, or the user ID if the user information was updated
     * @assert (false, false, false, false, false, false, false, false, false, false, false, false, false) === false
     */
    public static function update_user(
        $user_id,
        $firstname,
        $lastname,
        $username,
        $password,
        array $auth_sources,
        $email,
        $status,
        $official_code,
        $phone,
        $picture_uri,
        $expiration_date,
        $active,
        $creator_id = null,
        $hr_dept_id = 0,
        $extra = null,
        $language = 'en_US',
        $encrypt_method = '',
        $send_email = false,
        $reset_password = 0,
        $address = null,
        $emailTemplate = []
    ) {
        $eventDispatcher = Container::getEventDispatcher();

        $eventDispatcher->dispatch(
            new UserUpdatedEvent([], AbstractEvent::TYPE_PRE),
            Events::USER_UPDATED
        );

        $original_password = $password;
        $user_id = (int) $user_id;
        $creator_id = (int) $creator_id;

        if (empty($user_id)) {
            return false;
        }

        $user = api_get_user_entity($user_id);

        if (null === $user) {
            return false;
        }

        $accessUrl = Container::getAccessUrlUtil()->getCurrent();

        if (0 == $reset_password) {
            $password = null;
            $auth_sources = $user->getAuthSourcesAuthentications($accessUrl);
        } elseif (1 == $reset_password) {
            $original_password = $password = api_generate_password();
            $auth_sources = [UserAuthSource::PLATFORM];
        } elseif (2 == $reset_password) {
            $auth_sources = [UserAuthSource::PLATFORM];
        }

        // Checking the user language
        $languages = array_keys(api_get_languages());
        if (!in_array($language, $languages)) {
            $language = api_get_setting('platformLanguage');
        }

        $change_active = 0;
        $isUserActive = $user->isActive();
        if ($active != USER_SOFT_DELETED) {
            if ($isUserActive != $active) {
                $change_active = 1;
            }
        }

        $originalUsername = $user->getUsername();

        // If username is different from original then check if it exists.
        if ($originalUsername !== $username) {
            $available = Container::getUserRepository()->isUsernameAvailable($username);
            if (false === $available) {
                return false;
            }
        }

        if (!empty($expiration_date)) {
            $expiration_date = api_get_utc_datetime($expiration_date);
            $expiration_date = new \DateTime($expiration_date, new DateTimeZone('UTC'));
        }

        $previousStatus = $user->getStatus();
        $previousRole = $user->getRoleFromStatus($previousStatus);

        $user
            ->removeRole($previousRole)
            ->setLastname($lastname)
            ->setFirstname($firstname)
            ->setUsername($username)
            ->setStatus($status)
            ->setLocale($language)
            ->setEmail($email)
            ->setOfficialCode($official_code)
            ->setPhone($phone)
            ->setAddress($address)
            ->setExpirationDate($expiration_date)
            ->setActive($active)
            ->setHrDeptId((int) $hr_dept_id)
            ->removeAuthSources()
        ;

        foreach ($auth_sources as $authSource) {
            $user->addAuthSourceByAuthentication($authSource, $accessUrl);
        }

        if (!is_null($password)) {
            $user->setPlainPassword($password);
        }

        $user->setRoleFromStatus($status);
        Container::getUserRepository()->updateUser($user, true);
        Event::addEvent(LOG_USER_UPDATE, LOG_USER_ID, $user_id);

        if (1 == $change_active) {
            $event_title = LOG_USER_DISABLE;
            if (1 == $active) {
                $event_title = LOG_USER_ENABLE;
            }
            Event::addEvent($event_title, LOG_USER_ID, $user_id);
        }

        if (is_array($extra) && count($extra) > 0) {
            $res = true;
            foreach ($extra as $fname => $fvalue) {
                $res = $res && self::update_extra_field_value(
                    $user_id,
                    $fname,
                    $fvalue
                );
            }
        }

        if (!empty($email) && $send_email) {
            $recipient_name = api_get_person_name($firstname, $lastname, null, PERSON_NAME_EMAIL_ADDRESS);
            $emailsubject = '['.api_get_setting('site_name').'] '.sprintf(get_lang('Your registration on %s'), api_get_setting('site_name'));
            $sender_name = api_get_person_name(
                api_get_setting('administratorName'),
                api_get_setting('administratorSurname'),
                null,
                PERSON_NAME_EMAIL_ADDRESS
            );
            $email_admin = api_get_setting('emailAdministrator');
            $url = api_get_path(WEB_PATH);
            if (api_is_multiple_url_enabled()) {
                $access_url_id = api_get_current_access_url_id();
                if (-1 != $access_url_id) {
                    $url = api_get_access_url($access_url_id);
                    $url = $url['url'];
                }
            }

            $tplContent = new Template(
                null,
                false,
                false,
                false,
                false,
                false
            );
            // variables for the default template
            $tplContent->assign('complete_name', stripslashes(api_get_person_name($firstname, $lastname)));
            $tplContent->assign('login_name', $username);

            $originalPassword = '';
            if ($reset_password > 0) {
                $originalPassword = stripslashes($original_password);
            }
            $tplContent->assign('original_password', $originalPassword);
            $tplContent->assign('portal_url', $url);

            $emailBody = $tplContent->fetch('@ChamiloCore/Mailer/Legacy/user_edit_content.html.twig');

            $mailTemplateManager = new MailTemplateManager();

            if (!empty($emailTemplate) &&
                isset($emailTemplate['user_edit_content.tpl']) &&
                !empty($emailTemplate['user_edit_content.tpl'])
            ) {
                $userInfo = api_get_user_info($user_id);
                $emailBody = $mailTemplateManager->parseTemplate($emailTemplate['user_edit_content.tpl'], $userInfo);
            }

            $creatorInfo = api_get_user_info($creator_id);
            $creatorEmail = $creatorInfo['email'] ?? '';

            api_mail_html(
                $recipient_name,
                $email,
                $emailsubject,
                $emailBody,
                $sender_name,
                $email_admin,
                [],
                [],
                false,
                [],
                $creatorEmail
            );
        }

        $eventDispatcher->dispatch(
            new UserUpdatedEvent(['user' => $user], AbstractEvent::TYPE_POST),
            Events::USER_UPDATED
        );

        return $user->getId();
    }

    /**
     * Disables a user.
     *
     * @param int User id
     *
     * @return bool
     *
     * @uses \UserManager::change_active_state() to actually disable the user
     * @assert (0) === false
     */
    public static function disable($user_id)
    {
        if (empty($user_id)) {
            return false;
        }
        self::change_active_state($user_id, 0);

        return true;
    }

    /**
     * Enable a user.
     *
     * @param int User id
     *
     * @return bool
     *
     * @uses \UserManager::change_active_state() to actually disable the user
     * @assert (0) === false
     */
    public static function enable($user_id)
    {
        if (empty($user_id)) {
            return false;
        }
        self::change_active_state($user_id, 1);

        return true;
    }

    /**
     * Returns the user's id based on the original id and field name in
     * the extra fields. Returns 0 if no user was found. This function is
     * mostly useful in the context of a web services-based sinchronization.
     *
     * @param string Original user id
     * @param string Original field name
     *
     * @return int User id
     * @assert ('0','---') === 0
     */
    public static function get_user_id_from_original_id(
        $original_user_id_value,
        $original_user_id_name
    ) {
        $t_uf = Database::get_main_table(TABLE_EXTRA_FIELD);
        $t_ufv = Database::get_main_table(TABLE_EXTRA_FIELD_VALUES);
        $extraFieldType = EntityExtraField::USER_FIELD_TYPE;

        $original_user_id_name = Database::escape_string($original_user_id_name);
        $original_user_id_value = Database::escape_string($original_user_id_value);

        $sql = "SELECT item_id as user_id
                FROM $t_uf uf
                INNER JOIN $t_ufv ufv
                ON ufv.field_id = uf.id
                WHERE
                    variable = '$original_user_id_name' AND
                    field_value = '$original_user_id_value' AND
                    item_type = $extraFieldType
                ";
        $res = Database::query($sql);
        $row = Database::fetch_object($res);
        if ($row) {
            return $row->user_id;
        }

        return 0;
    }

    /**
     * Creates a username using person's names, i.e. creates jmontoya from Julio Montoya.
     *
     * @param string $firstname the first name of the user
     * @param string $lastname  the last name of the user
     *
     * @return string suggests a username that contains only ASCII-letters and digits,
     *                without check for uniqueness within the system
     *
     * @author Julio Montoya Armas
     * @author Ivan Tcholakov, 2009 - rework about internationalization.
     * @assert ('','') === false
     * @assert ('a','b') === 'ab'
     */
    public static function create_username($firstname, $lastname)
    {
        if (empty($firstname) && empty($lastname)) {
            return false;
        }

        // The first letter only.
        $firstname = api_substr(
            preg_replace(USERNAME_PURIFIER, '', $firstname),
            0,
            1
        );
        //Looking for a space in the lastname
        $pos = api_strpos($lastname, ' ');
        if (false !== $pos) {
            $lastname = api_substr($lastname, 0, $pos);
        }

        $lastname = preg_replace(USERNAME_PURIFIER, '', $lastname);
        $username = $firstname.$lastname;
        if (empty($username)) {
            $username = 'user';
        }

        $username = URLify::transliterate($username);

        return strtolower(substr($username, 0, User::USERNAME_MAX_LENGTH - 3));
    }

    /**
     * Creates a unique username, using:
     * 1. the first name and the last name of a user;
     * 2. an already created username but not checked for uniqueness yet.
     *
     * @param string $firstname The first name of a given user. If the second parameter $lastname is NULL, then this
     *                          parameter is treated as username which is to be checked f
     *                          or uniqueness and to be modified when it is necessary.
     * @param string $lastname  the last name of the user
     *
     * @return string Returns a username that contains only ASCII-letters and digits and that is unique in the system.
     *                Note: When the method is called several times with same parameters,
     *                its results look like the following sequence: ivan, ivan2, ivan3, ivan4, ...
     *
     * @author Ivan Tcholakov, 2009
     */
    public static function create_unique_username($firstname, $lastname = null)
    {
        if (is_null($lastname)) {
            // In this case the actual input parameter $firstname should contain ASCII-letters and digits only.
            // For making this method tolerant of mistakes,
            // let us transliterate and purify the suggested input username anyway.
            // So, instead of the sentence $username = $firstname; we place the following:
            $username = strtolower(preg_replace(USERNAME_PURIFIER, '', $firstname));
        } else {
            $username = self::create_username($firstname, $lastname);
        }
        if (!Container::getUserRepository()->isUsernameAvailable($username)) {
            $i = 2;
            $temp_username = substr($username, 0, User::USERNAME_MAX_LENGTH - strlen((string) $i)).$i;
            while (!Container::getUserRepository()->isUsernameAvailable($temp_username)) {
                $i++;
                $temp_username = substr($username, 0, User::USERNAME_MAX_LENGTH - strlen((string) $i)).$i;
            }
            $username = $temp_username;
        }

        $username = URLify::transliterate($username);

        return $username;
    }

    /**
     * Modifies a given username accordingly to the specification for valid characters and length.
     *
     * @param $username string          The input username
     * @param bool $strict (optional)   When this flag is TRUE, the result is guaranteed for full compliance,
     *                     otherwise compliance may be partial. The default value is FALSE.
     *
     * @return string the resulting purified username
     */
    public static function purify_username($username, $strict = false)
    {
        if ($strict) {
            // 1. Conversion of unacceptable letters (latinian letters with accents for example)
            // into ASCII letters in order they not to be totally removed.
            // 2. Applying the strict purifier.
            // 3. Length limitation.
            $return = 'true' === api_get_setting('login_is_email') ? substr(preg_replace(USERNAME_PURIFIER_MAIL, '', $username), 0, User::USERNAME_MAX_LENGTH) : substr(preg_replace(USERNAME_PURIFIER, '', $username), 0, User::USERNAME_MAX_LENGTH);
            $return = URLify::transliterate($return);

            // We want everything transliterate() does except converting @ to '(at)'. This is a hack to avoid this.
            $return = str_replace(' (at) ', '@', $return);

            return $return;
        }

        // 1. Applying the shallow purifier.
        // 2. Length limitation.
        return substr(
            preg_replace(USERNAME_PURIFIER_SHALLOW, '', $username),
            0,
            User::USERNAME_MAX_LENGTH
        );
    }

    /**
     * Checks whether the user id exists in the database.
     *
     * @param int $userId User id
     *
     * @return bool True if user id was found, false otherwise
     */
    public static function is_user_id_valid($userId)
    {
        $resultData = Database::select(
            'COUNT(1) AS count',
            Database::get_main_table(TABLE_MAIN_USER),
            [
                'where' => ['id = ?' => (int) $userId],
            ],
            'first'
        );

        if (false === $resultData) {
            return false;
        }

        return $resultData['count'] > 0;
    }

    /**
     * Checks whether a given username matches to the specification strictly.
     * The empty username is assumed here as invalid.
     * Mostly this function is to be used in the user interface built-in validation routines
     * for providing feedback while usernames are enterd manually.
     *
     * @param string $username the input username
     *
     * @return bool returns TRUE if the username is valid, FALSE otherwise
     */
    public static function is_username_valid($username)
    {
        return !empty($username) && $username == self::purify_username($username, true);
    }

    /**
     * Checks whether a username is empty. If the username contains whitespace characters,
     * such as spaces, tabulators, newlines, etc.,
     * it is assumed as empty too. This function is safe for validation unpurified data (during importing).
     *
     * @param string $username the given username
     *
     * @return bool returns TRUE if length of the username exceeds the limit, FALSE otherwise
     */
    public static function is_username_empty($username)
    {
        return 0 == strlen(self::purify_username($username, false));
    }

    /**
     * Checks whether a username is too long or not.
     *
     * @param string $username the given username, it should contain only ASCII-letters and digits
     *
     * @return bool returns TRUE if length of the username exceeds the limit, FALSE otherwise
     */
    public static function is_username_too_long($username)
    {
        return strlen($username) > User::USERNAME_MAX_LENGTH;
    }

    /**
     * Get the users by ID.
     *
     * @param array  $ids    student ids
     * @param bool   $active
     * @param string $order
     * @param string $limit
     *
     * @return array $result student information
     */
    public static function get_user_list_by_ids($ids = [], $active = null, $order = null, $limit = null)
    {
        if (empty($ids)) {
            return [];
        }

        $ids = is_array($ids) ? $ids : [$ids];
        $ids = array_map('intval', $ids);
        $ids = implode(',', $ids);

        $tbl_user = Database::get_main_table(TABLE_MAIN_USER);
        $sql = "SELECT * FROM $tbl_user WHERE id IN ($ids)";
        if (!is_null($active)) {
            $sql .= ' AND active='.($active ? '1' : '0');
        }

        if (!is_null($order)) {
            $order = Database::escape_string($order);
            $sql .= ' ORDER BY '.$order;
        }

        if (!is_null($limit)) {
            $limit = Database::escape_string($limit);
            $sql .= ' LIMIT '.$limit;
        }

        $rs = Database::query($sql);
        $result = [];
        while ($row = Database::fetch_array($rs)) {
            $result[] = $row;
        }

        return $result;
    }

    /**
     * Get a list of users of which the given conditions match with an = 'cond'.
     *
     * @param array $conditions a list of condition (example : status=>STUDENT)
     * @param array $order_by   a list of fields on which sort
     *
     * @return array an array with all users of the platform
     *
     * @todo security filter order by
     */
    public static function get_user_list(
        $conditions = [],
        $order_by = [],
        $limit_from = false,
        $limit_to = false,
        $idCampus = null
    ) {
        $user_table = Database::get_main_table(TABLE_MAIN_USER);
        $userUrlTable = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        $return_array = [];
        $sql = "SELECT user.*, user.id as user_id FROM $user_table user ";

        if (api_is_multiple_url_enabled()) {
            if ($idCampus) {
                $urlId = $idCampus;
            } else {
                $urlId = api_get_current_access_url_id();
            }
            $sql .= " INNER JOIN $userUrlTable url_user
                      ON (user.id = url_user.user_id)
                      WHERE url_user.access_url_id = $urlId";
        } else {
            $sql .= " WHERE 1=1 ";
        }

        if (count($conditions) > 0) {
            foreach ($conditions as $field => $value) {
                $field = Database::escape_string($field);
                $value = Database::escape_string($value);
                $sql .= " AND $field = '$value'";
            }
        }

        if (count($order_by) > 0) {
            $sql .= ' ORDER BY '.Database::escape_string(implode(',', $order_by));
        }

        if (is_numeric($limit_from) && is_numeric($limit_from)) {
            $limit_from = (int) $limit_from;
            $limit_to = (int) $limit_to;
            $sql .= " LIMIT $limit_from, $limit_to";
        }
        $sql_result = Database::query($sql);
        while ($result = Database::fetch_array($sql_result)) {
            $result['complete_name'] = api_get_person_name($result['firstname'], $result['lastname']);
            $return_array[] = $result;
        }

        return $return_array;
    }

    public static function getUserListExtraConditions(
        $conditions = [],
        $order_by = [],
        $limit_from = false,
        $limit_to = false,
        $idCampus = null,
        $extraConditions = '',
        $getCount = false
    ) {
        $user_table = Database::get_main_table(TABLE_MAIN_USER);
        $userUrlTable = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        $return_array = [];
        $sql = "SELECT user.*, user.id as user_id FROM $user_table user ";

        if ($getCount) {
            $sql = "SELECT count(user.id) count FROM $user_table user ";
        }

        if (api_is_multiple_url_enabled()) {
            if ($idCampus) {
                $urlId = $idCampus;
            } else {
                $urlId = api_get_current_access_url_id();
            }
            $sql .= " INNER JOIN $userUrlTable url_user
                      ON (user.id = url_user.user_id)
                      WHERE url_user.access_url_id = $urlId";
        } else {
            $sql .= " WHERE 1=1 ";
        }

        $sql .= " AND status <> ".ANONYMOUS." ";

        if (count($conditions) > 0) {
            foreach ($conditions as $field => $value) {
                $field = Database::escape_string($field);
                $value = Database::escape_string($value);
                $sql .= " AND $field = '$value'";
            }
        }

        $sql .= str_replace("\'", "'", Database::escape_string($extraConditions));

        if (!empty($order_by) && count($order_by) > 0) {
            $sql .= ' ORDER BY '.Database::escape_string(implode(',', $order_by));
        }

        if (is_numeric($limit_from) && is_numeric($limit_from)) {
            $limit_from = (int) $limit_from;
            $limit_to = (int) $limit_to;
            $sql .= " LIMIT $limit_from, $limit_to";
        }

        $sql_result = Database::query($sql);

        if ($getCount) {
            $result = Database::fetch_array($sql_result);

            return $result['count'];
        }

        while ($result = Database::fetch_array($sql_result)) {
            $result['complete_name'] = api_get_person_name($result['firstname'], $result['lastname']);
            $return_array[] = $result;
        }

        return $return_array;
    }

    /**
     * Get a list of users of which the given conditions match with a LIKE '%cond%'.
     *
     * @param array  $conditions       a list of condition (exemple : status=>STUDENT)
     * @param array  $order_by         a list of fields on which sort
     * @param bool   $simple_like      Whether we want a simple LIKE 'abc' or a LIKE '%abc%'
     * @param string $condition        Whether we want the filters to be combined by AND or OR
     * @param array  $onlyThisUserList
     *
     * @return array an array with all users of the platform
     *
     * @todo optional course code parameter, optional sorting parameters...
     * @todo security filter order_by
     */
    public static function getUserListLike(
        $conditions = [],
        $order_by = [],
        $simple_like = false,
        $condition = 'AND',
        $onlyThisUserList = [],
        int $limit = 0,
        int $offset = 0
    ) {
        $user_table = Database::get_main_table(TABLE_MAIN_USER);
        $tblAccessUrlRelUser = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        $return_array = [];
        $sql_query = "SELECT user.id, user.username, user.firstname, user.lastname, user.official_code, user.status
                  FROM $user_table user ";

        if (api_get_multiple_access_url()) {
            $sql_query .= " INNER JOIN $tblAccessUrlRelUser auru ON auru.user_id = user.id ";
        }

        $sql_query .= ' WHERE 1 = 1 ';
        if (count($conditions) > 0) {
            $temp_conditions = [];
            foreach ($conditions as $field => $value) {
                $field = Database::escape_string($field);
                $value = Database::escape_string($value);
                if ($simple_like) {
                    $temp_conditions[] = "$field LIKE '$value%'";
                } else {
                    if (in_array($field, ['user.id', 'user.status'])) {
                        $temp_conditions[] = "$field = '$value'";
                    } else {
                        $temp_conditions[] = "$field LIKE '%$value%'";
                    }
                }
            }
            if (!empty($temp_conditions)) {
                $sql_query .= ' AND '.implode(" $condition ", $temp_conditions);
            }

            if (api_get_multiple_access_url()) {
                $sql_query .= ' AND auru.access_url_id = '.api_get_current_access_url_id();
            }
        } else {
            if (api_get_multiple_access_url()) {
                $sql_query .= ' AND auru.access_url_id = '.api_get_current_access_url_id();
            }
        }

        if (!empty($onlyThisUserList)) {
            $onlyThisUserListToString = implode("','", array_map('intval', $onlyThisUserList));
            $sql_query .= " AND user.id IN ('$onlyThisUserListToString') ";
        }

        if (!empty($order_by)) {
            $sql_query .= ' ORDER BY '.Database::escape_string(implode(',', $order_by));
        }

        if ($limit > 0) {
            $sql_query .= ' LIMIT '.intval($limit);
            if ($offset > 0) {
                $sql_query .= ' OFFSET '.intval($offset);
            }
        }

        $sql_result = Database::query($sql_query);
        while ($result = Database::fetch_array($sql_result)) {
            $return_array[] = $result;
        }

        return $return_array;
    }

    /**
     * Get user path from user ID (returns an array).
     * The return format is a complete path to a folder ending with "/"
     * In case the first level of subdirectory of users/ does not exist, the
     * function will attempt to create it. Probably not the right place to do it
     * but at least it avoids headaches in many other places.
     *
     * @param int    $id   User ID
     * @param string $type Type of path to return (can be 'system', 'web', 'last')
     *
     * @return string User folder path (i.e. /var/www/chamilo/app/upload/users/1/1/)
     */
    public static function getUserPathById($id, $type)
    {
        $id = (int) $id;
        if (!$id) {
            return null;
        }

        $userPath = "users/$id/";
        if (api_get_setting('split_users_upload_directory') === 'true') {
            $userPath = 'users/'.substr((string) $id, 0, 1).'/'.$id.'/';
            // In exceptional cases, on some portals, the intermediate base user
            // directory might not have been created. Make sure it is before
            // going further.

            $rootPath = api_get_path(SYS_PATH).'../app/upload/users/'.substr((string) $id, 0, 1);
            if (!is_dir($rootPath)) {
                $perm = api_get_permissions_for_new_directories();
                try {
                    mkdir($rootPath, $perm);
                } catch (Exception $e) {
                    error_log($e->getMessage());
                }
            }
        }
        switch ($type) {
            case 'system': // Base: absolute system path.
                $userPath = api_get_path(SYS_PATH).'../app/upload/'.$userPath;
                break;
            case 'web': // Base: absolute web path.
                $userPath = api_get_path(WEB_PATH).'../app/upload/'.$userPath;
                break;
            case 'last': // Only the last part starting with users/
                break;
        }

        return $userPath;
    }

    /**
     * Gets the current user image.
     *
     * @param string $userId
     * @param int    $size        it can be USER_IMAGE_SIZE_SMALL,
     *                            USER_IMAGE_SIZE_MEDIUM, USER_IMAGE_SIZE_BIG or  USER_IMAGE_SIZE_ORIGINAL
     * @param bool   $addRandomId
     * @param array  $userInfo    to avoid query the DB
     *
     * @todo add gravatar support
     * @todo replace $userId with User entity
     *
     * @return string
     */
    public static function getUserPicture(
        $userId,
        int $size = USER_IMAGE_SIZE_MEDIUM,
        $addRandomId = true,
        $userInfo = []
    ) {
        $user = api_get_user_entity($userId);
        $illustrationRepo = Container::getIllustrationRepository();

        switch ($size) {
            case USER_IMAGE_SIZE_SMALL:
                $width = 32;
                break;
            case USER_IMAGE_SIZE_MEDIUM:
                $width = 64;
                break;
            case USER_IMAGE_SIZE_BIG:
                $width = 128;
                break;
            case USER_IMAGE_SIZE_ORIGINAL:
            default:
                $width = 0;
                break;
        }

        $url = $illustrationRepo->getIllustrationUrl($user);
        $params = [];
        if (!empty($width)) {
            $params['w'] = $width;
        }

        if ($addRandomId) {
            $params['rand'] = uniqid('u_', true);
        }

        $paramsToString = '';
        if (!empty($params)) {
            $paramsToString = '?'.http_build_query($params);
        }

        return $url.$paramsToString;

        /*
        // Make sure userInfo is defined. Otherwise, define it!
        if (empty($userInfo) || !is_array($userInfo) || 0 == count($userInfo)) {
            if (empty($user_id)) {
                return '';
            } else {
                $userInfo = api_get_user_info($user_id);
            }
        }

        $imageWebPath = self::get_user_picture_path_by_id(
            $user_id,
            'web',
            $userInfo
        );
        $pictureWebFile = $imageWebPath['file'];
        $pictureWebDir = $imageWebPath['dir'];

        $pictureAnonymousSize = '128';
        $gravatarSize = 22;
        $realSizeName = 'small_';

        switch ($size) {
            case USER_IMAGE_SIZE_SMALL:
                $pictureAnonymousSize = '32';
                $realSizeName = 'small_';
                $gravatarSize = 32;
                break;
            case USER_IMAGE_SIZE_MEDIUM:
                $pictureAnonymousSize = '64';
                $realSizeName = 'medium_';
                $gravatarSize = 64;
                break;
            case USER_IMAGE_SIZE_ORIGINAL:
                $pictureAnonymousSize = '128';
                $realSizeName = '';
                $gravatarSize = 128;
                break;
            case USER_IMAGE_SIZE_BIG:
                $pictureAnonymousSize = '128';
                $realSizeName = 'big_';
                $gravatarSize = 128;
                break;
        }

        $gravatarEnabled = api_get_setting('gravatar_enabled');
        $anonymousPath = Display::returnIconPath('unknown.png', $pictureAnonymousSize);
        if ('unknown.jpg' == $pictureWebFile || empty($pictureWebFile)) {
            if ('true' === $gravatarEnabled) {
                $file = self::getGravatar(
                    $imageWebPath['email'],
                    $gravatarSize,
                    api_get_setting('gravatar_type')
                );

                if ($addRandomId) {
                    $file .= '&rand='.uniqid();
                }

                return $file;
            }

            return $anonymousPath;
        }

        if ($addRandomId) {
            $picture .= '?rand='.uniqid();
        }

        return $picture;*/
    }

    /**
     * Creates new user photos in various sizes of a user, or deletes user photos.
     * Note: This method relies on configuration setting from main/inc/conf/profile.conf.php.
     *
     * @param int    $user_id the user internal identification number
     * @param string $file    The common file name for the newly created photos.
     *                        It will be checked and modified for compatibility with the file system.
     *                        If full name is provided, path component is ignored.
     *                        If an empty name is provided, then old user photos are deleted only,
     *
     * @see     UserManager::delete_user_picture() as the prefered way for deletion.
     *
     * @param string $source_file    the full system name of the image from which user photos will be created
     * @param string $cropParameters Optional string that contents "x,y,width,height" of a cropped image format
     *
     * @return bool Returns the resulting common file name of created images which usually should be stored in database.
     *              When deletion is requested returns empty string.
     *              In case of internal error or negative validation returns FALSE.
     */
    public static function update_user_picture($userId, UploadedFile $file, string $crop = '')
    {
        if (empty($userId) || empty($file)) {
            return false;
        }

        $repo = Container::getUserRepository();
        $user = $repo->find($userId);
        if ($user) {
            $repoIllustration = Container::getIllustrationRepository();
            $repoIllustration->addIllustration($user, $user, $file, $crop);
        }
    }

    /**
     * Deletes user photos.
     *
     * @param int $userId the user internal identification number
     *
     * @return mixed returns empty string on success, FALSE on error
     */
    public static function deleteUserPicture($userId)
    {
        $repo = Container::getUserRepository();
        $user = $repo->find($userId);
        if ($user) {
            $illustrationRepo = Container::getIllustrationRepository();
            $illustrationRepo->deleteIllustration($user);
        }
    }

    /**
     * Returns an XHTML formatted list of productions for a user, or FALSE if he
     * doesn't have any.
     *
     * If there has been a request to remove a production, the function will return
     * without building the list unless forced to do so by the optional second
     * parameter. This increases performance by avoiding to read through the
     * productions on the filesystem before the removal request has been carried
     * out because they'll have to be re-read afterwards anyway.
     *
     * @deprecated This method is being removed from chamilo 2.0
     * @param int  $user_id    User id
     * @param bool $force      Optional parameter to force building after a removal request
     * @param bool $showDelete
     *
     * @return string A string containing the XHTML code to display the production list, or FALSE
     */
    public static function build_production_list($user_id, $force = false, $showDelete = false)
    {
        if (!$force && !empty($_POST['remove_production'])) {
            return true; // postpone reading from the filesystem
        }

        $productions = self::get_user_productions($user_id);

        if (empty($productions)) {
            return false;
        }

        return false;

        $production_dir = self::getUserPathById($user_id, 'web');
        $del_image = Display::returnIconPath('delete.png');
        $add_image = Display::returnIconPath('archive.png');
        $del_text = get_lang('Delete');
        $production_list = '';
        if (count($productions) > 0) {
            $production_list = '<div class="files-production"><ul id="productions">';
            foreach ($productions as $file) {
                $production_list .= '<li>
                    <img src="'.$add_image.'" />
                    <a href="'.$production_dir.urlencode($file).'" target="_blank">
                        '.htmlentities($file).'
                    </a>';
                if ($showDelete) {
                    $production_list .= '&nbsp;&nbsp;
                        <input
                            style="width:16px;"
                            type="image"
                            name="remove_production['.urlencode($file).']"
                            src="'.$del_image.'"
                            alt="'.$del_text.'"
                            title="'.$del_text.' '.htmlentities($file).'"
                            onclick="javascript: return confirmation(\''.htmlentities($file).'\');" /></li>';
                }
            }
            $production_list .= '</ul></div>';
        }

        return $production_list;
    }

    /**
     * Returns an array with the user's productions.
     *
     * @param int $user_id User id
     *
     * @return array An array containing the user's productions
     */
    public static function get_user_productions($user_id)
    {
        return [];

        $production_repository = self::getUserPathById($user_id, 'system');
        $productions = [];

        if (is_dir($production_repository)) {
            $handle = opendir($production_repository);
            while ($file = readdir($handle)) {
                if ('.' == $file ||
                    '..' == $file ||
                    '.htaccess' == $file ||
                    is_dir($production_repository.$file)
                ) {
                    // skip current/parent directory and .htaccess
                    continue;
                }

                if (preg_match('/('.$user_id.'|[0-9a-f]{13}|saved)_.+\.(png|jpg|jpeg|gif)$/i', $file)) {
                    // User's photos should not be listed as productions.
                    continue;
                }
                $productions[] = $file;
            }
        }

        return $productions;
    }

    /**
     * Remove a user production.
     *
     * @param int    $user_id    User id
     * @param string $production The production to remove
     *
     * @return bool
     */
    public static function remove_user_production($user_id, $production)
    {
        throw new Exception('remove_user_production');
        /*$production_path = self::get_user_picture_path_by_id($user_id, 'system');
        $production_file = $production_path['dir'].$production;
        if (is_file($production_file)) {
            unlink($production_file);

            return true;
        }

        return false;*/
    }

    /**
     * Update an extra field value for a given user.
     *
     * @param int    $userId   User ID
     * @param string $variable Field variable name
     * @param string $value    Field value
     *
     * @return bool true if field updated, false otherwise
     */
    public static function update_extra_field_value($userId, $variable, $value = '')
    {
        $extraFieldValue = new ExtraFieldValue('user');
        $params = [
            'item_id' => $userId,
            'variable' => $variable,
            'field_value' => $value,
        ];

        return $extraFieldValue->save($params);
    }

    /**
     * Get an array of extra fields with field details (type, default value and options).
     *
     * @param    int    Offset (from which row)
     * @param    int    Number of items
     * @param    int    Column on which sorting is made
     * @param    string    Sorting direction
     * @param    bool    Optional. Whether we get all the fields or just the visible ones
     * @param    int        Optional. Whether we get all the fields with field_filter 1 or 0 or everything
     *
     * @return array Extra fields details (e.g. $list[2]['type'], $list[4]['options'][2]['title']
     */
    public static function get_extra_fields(
        $from = 0,
        $number_of_items = 0,
        $column = 5,
        $direction = 'ASC',
        $all_visibility = true,
        $field_filter = null
    ) {
        $fields = [];
        $t_uf = Database::get_main_table(TABLE_EXTRA_FIELD);
        $t_ufo = Database::get_main_table(TABLE_EXTRA_FIELD_OPTIONS);
        $columns = [
            'id',
            'variable',
            'value_type',
            'display_text',
            'default_value',
            'field_order',
            'filter',
        ];
        $column = (int) $column;
        $sort_direction = '';
        if (in_array(strtoupper($direction), ['ASC', 'DESC'])) {
            $sort_direction = strtoupper($direction);
        }
        $extraFieldType = EntityExtraField::USER_FIELD_TYPE;
        $sqlf = "SELECT * FROM $t_uf WHERE item_type = $extraFieldType ";
        if (!$all_visibility) {
            $sqlf .= " AND visible_to_self = 1 ";
        }
        if (!is_null($field_filter)) {
            $field_filter = (int) $field_filter;
            $sqlf .= " AND filter = $field_filter ";
        }
        $sqlf .= " ORDER BY `".$columns[$column]."` $sort_direction ";
        if (0 != $number_of_items) {
            $sqlf .= " LIMIT ".intval($from).','.intval($number_of_items);
        }
        $resf = Database::query($sqlf);
        if (Database::num_rows($resf) > 0) {
            while ($rowf = Database::fetch_array($resf)) {
                $fields[$rowf['id']] = [
                    0 => $rowf['id'],
                    1 => $rowf['variable'],
                    2 => $rowf['value_type'],
                    3 => empty($rowf['display_text']) ? '' : $rowf['display_text'],
                    4 => $rowf['default_value'],
                    5 => $rowf['field_order'],
                    6 => $rowf['visible_to_self'],
                    7 => $rowf['changeable'],
                    8 => $rowf['filter'],
                    9 => [],
                    10 => '<a name="'.$rowf['id'].'"></a>',
                ];

                $sqlo = "SELECT * FROM $t_ufo
                         WHERE field_id = ".$rowf['id']."
                         ORDER BY option_order ASC";
                $reso = Database::query($sqlo);
                if (Database::num_rows($reso) > 0) {
                    while ($rowo = Database::fetch_array($reso)) {
                        $fields[$rowf['id']][9][$rowo['id']] = [
                            0 => $rowo['id'],
                            1 => $rowo['option_value'],
                            2 => empty($rowo['display_text']) ? '' : $rowo['display_text'],
                            3 => $rowo['option_order'],
                        ];
                    }
                }
            }
        }

        return $fields;
    }

    /**
     * Creates a new extra field.
     *
     * @param string $variable    Field's internal variable name
     * @param int    $fieldType   Field's type
     * @param string $displayText Field's language var name
     * @param string $default     Field's default value
     *
     * @return int
     */
    public static function create_extra_field(
        $variable,
        $valueType,
        $displayText,
        $default
    ) {
        $extraField = new ExtraField('user');
        $params = [
            'variable' => $variable,
            'value_type' => $valueType,
            'display_text' => $displayText,
            'default_value' => $default,
        ];

        return $extraField->save($params);
    }

    /**
     * Check if a field is available.
     *
     * @param string $variable
     *
     * @return bool
     */
    public static function is_extra_field_available($variable)
    {
        $extraField = new ExtraField('user');
        $data = $extraField->get_handler_field_info_by_field_variable($variable);

        return !empty($data) ? true : false;
    }

    /**
     * Gets user extra fields data.
     *
     * @param    int    User ID
     * @param    bool    Whether to prefix the fields indexes with "extra_" (might be used by formvalidator)
     * @param    bool    Whether to return invisible fields as well
     * @param    bool    Whether to split multiple-selection fields or not
     *
     * @return array Array of fields => value for the given user
     */
    public static function get_extra_user_data(
        $user_id,
        $prefix = false,
        $allVisibility = true,
        $splitMultiple = false,
        $fieldFilter = null
    ) {
        $user_id = (int) $user_id;

        if (empty($user_id)) {
            return [];
        }

        $extra_data = [];
        $t_uf = Database::get_main_table(TABLE_EXTRA_FIELD);
        $t_ufv = Database::get_main_table(TABLE_EXTRA_FIELD_VALUES);
        $sql = "SELECT f.id as id, f.variable as fvar, f.value_type as type
                FROM $t_uf f
                WHERE
                    item_type = ".EntityExtraField::USER_FIELD_TYPE."
                ";
        $filter_cond = '';

        if (!$allVisibility) {
            if (isset($fieldFilter)) {
                $fieldFilter = (int) $fieldFilter;
                $filter_cond .= " AND filter = $fieldFilter ";
            }
            $sql .= " AND f.visible_to_self = 1 $filter_cond ";
        } else {
            if (isset($fieldFilter)) {
                $fieldFilter = (int) $fieldFilter;
                $sql .= " AND filter = $fieldFilter ";
            }
        }

        $sql .= ' ORDER BY f.field_order';

        $res = Database::query($sql);
        if (Database::num_rows($res) > 0) {
            while ($row = Database::fetch_array($res)) {
                if (self::USER_FIELD_TYPE_TAG == $row['type']) {
                    $tags = self::get_user_tags_to_string($user_id, $row['id'], false);
                    $extra_data['extra_'.$row['fvar']] = $tags;
                } else {
                    $sqlu = "SELECT field_value as fval
                            FROM $t_ufv
                            WHERE field_id=".$row['id']." AND item_id = ".$user_id;
                    $resu = Database::query($sqlu);
                    // get default value
                    $sql_df = "SELECT default_value as fval_df FROM $t_uf
                               WHERE id=".$row['id'];
                    $res_df = Database::query($sql_df);

                    if (Database::num_rows($resu) > 0) {
                        $rowu = Database::fetch_array($resu);
                        $fval = $rowu['fval'];
                        if (self::USER_FIELD_TYPE_SELECT_MULTIPLE == $row['type']) {
                            $fval = explode(';', $rowu['fval']);
                        }
                    } else {
                        $row_df = Database::fetch_array($res_df);
                        $fval = $row_df['fval_df'];
                    }
                    // We get here (and fill the $extra_data array) even if there
                    // is no user with data (we fill it with default values)
                    if ($prefix) {
                        if (self::USER_FIELD_TYPE_RADIO == $row['type']) {
                            $extra_data['extra_'.$row['fvar']]['extra_'.$row['fvar']] = $fval;
                        } else {
                            $extra_data['extra_'.$row['fvar']] = $fval;
                        }
                    } else {
                        if (self::USER_FIELD_TYPE_RADIO == $row['type']) {
                            $extra_data['extra_'.$row['fvar']]['extra_'.$row['fvar']] = $fval;
                        } else {
                            $extra_data[$row['fvar']] = $fval;
                        }
                    }
                }
            }
        }

        return $extra_data;
    }

    /** Get extra user data by field.
     * @param int    user ID
     * @param string the internal variable name of the field
     *
     * @return array with extra data info of a user i.e array('field_variable'=>'value');
     */
    public static function get_extra_user_data_by_field(
        $user_id,
        $field_variable,
        $prefix = false,
        $all_visibility = true,
        $splitmultiple = false
    ) {
        $user_id = (int) $user_id;

        if (empty($user_id)) {
            return [];
        }

        $extra_data = [];
        $t_uf = Database::get_main_table(TABLE_EXTRA_FIELD);
        $t_ufv = Database::get_main_table(TABLE_EXTRA_FIELD_VALUES);

        $sql = "SELECT f.id as id, f.variable as fvar, f.value_type as type
                FROM $t_uf f
                WHERE f.variable = '$field_variable' ";

        if (!$all_visibility) {
            $sql .= " AND f.visible_to_self = 1 ";
        }

        $sql .= " AND item_type = ".EntityExtraField::USER_FIELD_TYPE;
        $sql .= " ORDER BY f.field_order ";

        $res = Database::query($sql);
        if (Database::num_rows($res) > 0) {
            while ($row = Database::fetch_array($res)) {
                $sqlu = "SELECT field_value as fval FROM $t_ufv v
                         INNER JOIN $t_uf f
                         ON (v.field_id = f.id)
                         WHERE
                            item_type = ".EntityExtraField::USER_FIELD_TYPE." AND
                            field_id = ".$row['id']." AND
                            item_id = ".$user_id;
                $resu = Database::query($sqlu);
                $fval = '';
                if (Database::num_rows($resu) > 0) {
                    $rowu = Database::fetch_array($resu);
                    $fval = $rowu['fval'];
                    if (self::USER_FIELD_TYPE_SELECT_MULTIPLE == $row['type']) {
                        $fval = explode(';', $rowu['fval']);
                    }
                }
                if ($prefix) {
                    $extra_data['extra_'.$row['fvar']] = $fval;
                } else {
                    $extra_data[$row['fvar']] = $fval;
                }
            }
        }

        return $extra_data;
    }

    /**
     * Get the extra field information for a certain field (the options as well).
     *
     * @param int $variable The name of the field we want to know everything about
     *
     * @return array Array containing all the information about the extra profile field
     *               (first level of array contains field details, then 'options' sub-array contains options details,
     *               as returned by the database)
     *
     * @author Julio Montoya
     *
     * @since v1.8.6
     */
    public static function get_extra_field_information_by_name($variable)
    {
        $extraField = new ExtraField('user');

        return $extraField->get_handler_field_info_by_field_variable($variable);
    }

    /**
     * Get the extra field information for user tag (the options as well).
     *
     * @param int $variable The name of the field we want to know everything about
     *
     * @return array Array containing all the information about the extra profile field
     *               (first level of array contains field details, then 'options' sub-array contains options details,
     *               as returned by the database)
     *
     * @author José Loguercio
     *
     * @since v1.11.0
     */
    public static function get_extra_field_tags_information_by_name($variable)
    {
        $extraField = new ExtraField('user');

        return $extraField->get_handler_field_info_by_tags($variable);
    }

    /**
     * Get all the extra field information of a certain field (also the options).
     *
     * @param int $fieldId the ID of the field we want to know everything of
     *
     * @return array $return containing all th information about the extra profile field
     *
     * @author Julio Montoya
     *
     * @deprecated
     * @since v1.8.6
     */
    public static function get_extra_field_information($fieldId)
    {
        $extraField = new ExtraField('user');

        return $extraField->getFieldInfoByFieldId($fieldId);
    }

    /**
     * Get extra user data by value.
     *
     * @param string $variable       the internal variable name of the field
     * @param string $value          the internal value of the field
     * @param bool   $all_visibility
     *
     * @return array with extra data info of a user i.e array('field_variable'=>'value');
     */
    public static function get_extra_user_data_by_value($variable, $value, $all_visibility = true)
    {
        $extraFieldValue = new ExtraFieldValue('user');
        $extraField = new ExtraField('user');

        $info = $extraField->get_handler_field_info_by_field_variable($variable);

        if (false === $info) {
            return [];
        }

        $data = $extraFieldValue->get_item_id_from_field_variable_and_field_value(
            $variable,
            $value,
            false,
            false,
            true
        );

        $result = [];
        if (!empty($data)) {
            foreach ($data as $item) {
                $result[] = $item['item_id'];
            }
        }

        return $result;
    }

    /**
     * Get extra user data by tags value.
     *
     * @param int    $fieldId the ID of the field we want to know everything of
     * @param string $tag     the tag name for search
     *
     * @return array with extra data info of a user
     *
     * @author José Loguercio
     *
     * @since v1.11.0
     */
    public static function get_extra_user_data_by_tags($fieldId, $tag)
    {
        $extraField = new ExtraField('user');
        $result = $extraField->getAllUserPerTag($fieldId, $tag);
        $array = [];
        foreach ($result as $index => $user) {
            $array[] = $user['user_id'];
        }

        return $array;
    }

    /**
     * Get extra user data by field variable.
     *
     * @param string $variable field variable
     *
     * @return array data
     */
    public static function get_extra_user_data_by_field_variable($variable)
    {
        $extraInfo = self::get_extra_field_information_by_name($variable);
        $field_id = (int) $extraInfo['id'];

        $extraField = new ExtraFieldValue('user');
        $data = $extraField->getValuesByFieldId($field_id);

        if (!empty($data)) {
            foreach ($data as $row) {
                $user_id = $row['item_id'];
                $data[$user_id] = $row;
            }
        }

        return $data;
    }

    /**
     * Get extra user data tags by field variable.
     *
     * @param string $variable field variable
     *
     * @return array
     */
    public static function get_extra_user_data_for_tags($variable)
    {
        $data = self::get_extra_field_tags_information_by_name($variable);

        return $data;
    }

    /**
     * Gives a list of [session_category][session_id] for the current user.
     *
     * @param int  $user_id
     * @param bool $is_time_over                 whether to fill the first element or not
     *                                           (to give space for courses out of categories)
     * @param bool $ignore_visibility_for_admins optional true if limit time from session is over, false otherwise
     * @param bool $ignoreTimeLimit              ignore time start/end
     * @param bool $getCount
     *
     * @return array list of statuses [session_category][session_id]
     *
     * @todo ensure multiple access urls are managed correctly
     */
    public static function get_sessions_by_category(
        $user_id,
        $is_time_over = true,
        $ignore_visibility_for_admins = false,
        $ignoreTimeLimit = false,
        $getCount = false
    ) {
        $user_id = (int) $user_id;

        if (empty($user_id)) {
            return [];
        }

        // Get the list of sessions per user
        $now = new DateTime('now', new DateTimeZone('UTC'));

        // LEFT JOIN is used for session_rel_course_rel_user because an inner
        // join would not catch session-courses where the user is general
        // session coach but which do not have students nor coaches registered
        $dqlSelect = ' COUNT(DISTINCT s.id) ';

        if (!$getCount) {
            $dqlSelect = " DISTINCT
                s.id,
                s.title,
                s.accessStartDate AS access_start_date,
                s.accessEndDate AS access_end_date,
                s.duration,
                sc.id AS session_category_id,
                sc.title AS session_category_title,
                sc.dateStart AS session_category_date_start,
                sc.dateEnd AS session_category_date_end,
                s.coachAccessStartDate AS coach_access_start_date,
                s.coachAccessEndDate AS coach_access_end_date,
                CASE WHEN s.accessEndDate IS NULL THEN 1 ELSE 0 END HIDDEN _isFieldNull
                , s.position AS position
            ";
        }

        // A single OR operation on scu.user = :user OR s.generalCoach = :user
        // is awfully inefficient for large sets of data (1m25s for 58K
        // sessions, BT#14115) but executing a similar query twice and grouping
        // the results afterwards in PHP takes about 1/1000th of the time
        // (0.1s + 0.0s) for the same set of data, so we do it this way...
        $dqlStudent = "SELECT $dqlSelect
            FROM ChamiloCoreBundle:Session AS s
            LEFT JOIN ChamiloCoreBundle:SessionRelCourseRelUser AS scu WITH scu.session = s
            INNER JOIN ChamiloCoreBundle:AccessUrlRelSession AS url WITH url.session = s.id
            LEFT JOIN ChamiloCoreBundle:SessionCategory AS sc WITH s.category = sc
            WHERE scu.user = :user AND url.url = :url ";
        $dqlCoach = "SELECT $dqlSelect
            FROM ChamiloCoreBundle:Session AS s
            INNER JOIN ChamiloCoreBundle:AccessUrlRelSession AS url WITH url.session = s.id
            LEFT JOIN ChamiloCoreBundle:SessionCategory AS sc WITH s.category = sc
            INNER JOIN ChamiloCoreBundle:SessionRelUser AS su WITH su.session = s
            WHERE (su.user = :user AND su.relationType = ".SessionEntity::GENERAL_COACH.") AND url.url = :url ";

        // Default order
        $order = 'ORDER BY sc.title, s.title';

        // Order by date if showing all sessions
        $showAllSessions = ('true' === api_get_setting('session.show_all_sessions_on_my_course_page'));
        if ($showAllSessions) {
            $order = 'ORDER BY s.accessStartDate';
        }

        // Order by position
        if ('true' === api_get_setting('session.session_list_order')) {
            $order = 'ORDER BY s.position';
        }

        // Order by dates according to settings
        $orderBySettings = api_get_setting('session.my_courses_session_order', true);
        if (!empty($orderBySettings) && isset($orderBySettings['field']) && isset($orderBySettings['order'])) {
            $field = $orderBySettings['field'];
            $orderSetting = $orderBySettings['order'];
            switch ($field) {
                case 'start_date':
                    $order = " ORDER BY s.accessStartDate $orderSetting";
                    break;
                case 'end_date':
                    $order = " ORDER BY s.accessEndDate $orderSetting ";
                    if ('asc' == $orderSetting) {
                        // Put null values at the end
                        // https://stackoverflow.com/questions/12652034/how-can-i-order-by-null-in-dql
                        $order = ' ORDER BY _isFieldNull asc, s.accessEndDate asc';
                    }
                    break;
                case 'name':
                case 'title':
                    $order = " ORDER BY s.title $orderSetting ";
                    break;
            }
        }

        $dqlStudent .= $order;
        $dqlCoach .= $order;

        $accessUrlId = api_get_current_access_url_id();
        $dqlStudent = Database::getManager()
            ->createQuery($dqlStudent)
            ->setParameters(
                ['user' => $user_id, 'url' => $accessUrlId]
            )
        ;
        $dqlCoach = Database::getManager()
            ->createQuery($dqlCoach)
            ->setParameters(
                ['user' => $user_id, 'url' => $accessUrlId]
            )
        ;

        if ($getCount) {
            return $dqlStudent->getSingleScalarResult() + $dqlCoach->getSingleScalarResult();
        }

        $sessionDataStudent = $dqlStudent->getResult();
        $sessionDataCoach = $dqlCoach->getResult();

        $sessionData = [];
        // First fill $sessionData with student sessions
        if (!empty($sessionDataStudent)) {
            foreach ($sessionDataStudent as $row) {
                $sessionData[$row['id']] = $row;
            }
        }
        // Overwrite session data of the user as a student with session data
        // of the user as a coach.
        // There shouldn't be such duplicate rows, but just in case...
        if (!empty($sessionDataCoach)) {
            foreach ($sessionDataCoach as $row) {
                $sessionData[$row['id']] = $row;
            }
        }

        $collapsable = ('true' === api_get_setting('session.allow_user_session_collapsable'));



        $extraField = new ExtraFieldValue('session');
        $collapsableLink = api_get_path(WEB_PATH).'user_portal.php?action=collapse_session';

        if (empty($sessionData)) {
            return [];
        }
        $categories = [];
        foreach ($sessionData as $row) {
            $session_id = $row['id'];
            $coachList = SessionManager::getCoachesBySession($session_id);
            $categoryStart = $row['session_category_date_start'] ? $row['session_category_date_start']->format('Y-m-d') : '';
            $categoryEnd = $row['session_category_date_end'] ? $row['session_category_date_end']->format('Y-m-d') : '';
            $courseList = self::get_courses_list_by_session($user_id, $session_id);
            $daysLeft = SessionManager::getDayLeftInSession($row, $user_id);

            // User portal filters:
            if (false === $ignoreTimeLimit) {
                if ($is_time_over) {
                    // History
                    if ($row['duration']) {
                        if ($daysLeft >= 0) {
                            continue;
                        }
                    } else {
                        if (empty($row['access_end_date'])) {
                            continue;
                        } else {
                            if ($row['access_end_date'] > $now) {
                                continue;
                            }
                        }
                    }
                } else {
                    // Current user portal
                    $isGeneralCoach = api_get_session_entity($row['id'])->hasUserAsGeneralCoach(api_get_user_entity($user_id));
                    $isCoachOfCourse = in_array($user_id, $coachList);

                    if (api_is_platform_admin() || $isGeneralCoach || $isCoachOfCourse) {
                        // Teachers can access the session depending in the access_coach date
                    } else {
                        if ($row['duration']) {
                            if ($daysLeft <= 0) {
                                continue;
                            }
                        } else {
                            if (isset($row['access_end_date']) &&
                                !empty($row['access_end_date'])
                            ) {
                                if ($row['access_end_date'] <= $now) {
                                    continue;
                                }
                            }
                        }
                    }
                }
            }

            $categories[$row['session_category_id']]['session_category'] = [
                'id' => $row['session_category_id'],
                'name' => $row['session_category_title'],
                'date_start' => $categoryStart,
                'date_end' => $categoryEnd,
            ];

            $visibility = api_get_session_visibility(
                $session_id,
                null,
                $ignore_visibility_for_admins
            );

            if (SESSION_VISIBLE != $visibility) {
                // Course Coach session visibility.
                $blockedCourseCount = 0;
                $closedVisibilityList = [
                    COURSE_VISIBILITY_CLOSED,
                    COURSE_VISIBILITY_HIDDEN,
                ];

                foreach ($courseList as $course) {
                    // Checking session visibility
                    $sessionCourseVisibility = api_get_session_visibility(
                        $session_id,
                        $course['real_id'],
                        $ignore_visibility_for_admins
                    );

                    $courseIsVisible = !in_array($course['visibility'], $closedVisibilityList);
                    if (false === $courseIsVisible || SESSION_INVISIBLE == $sessionCourseVisibility) {
                        $blockedCourseCount++;
                    }
                }

                // If all courses are blocked then no show in the list.
                if ($blockedCourseCount === count($courseList)) {
                    $visibility = SESSION_INVISIBLE;
                } else {
                    $visibility = $sessionCourseVisibility;
                }
            }

            switch ($visibility) {
                case SESSION_VISIBLE_READ_ONLY:
                case SESSION_VISIBLE:
                case SESSION_AVAILABLE:
                    break;
                case SESSION_INVISIBLE:
                    if (false === $ignore_visibility_for_admins) {
                        continue 2;
                    }
            }

            $collapsed = '';
            $collapsedAction = '';
            if ($collapsable) {
                $collapsableData = SessionManager::getCollapsableData(
                    $user_id,
                    $session_id,
                    $extraField,
                    $collapsableLink
                );
                $collapsed = $collapsableData['collapsed'];
                $collapsedAction = $collapsableData['collapsable_link'];
            }

            $categories[$row['session_category_id']]['sessions'][] = [
                'session_name' => $row['title'],
                'session_id' => $row['id'],
                'access_start_date' => $row['access_start_date'] ? $row['access_start_date']->format('Y-m-d H:i:s') : null,
                'access_end_date' => $row['access_end_date'] ? $row['access_end_date']->format('Y-m-d H:i:s') : null,
                'coach_access_start_date' => $row['coach_access_start_date'] ? $row['coach_access_start_date']->format('Y-m-d H:i:s') : null,
                'coach_access_end_date' => $row['coach_access_end_date'] ? $row['coach_access_end_date']->format('Y-m-d H:i:s') : null,
                'courses' => $courseList,
                'collapsed' => $collapsed,
                'collapsable_link' => $collapsedAction,
                'duration' => $row['duration'],
            ];
        }

        return $categories;
    }

    /**
     * Gives a list of [session_id-course_code] => [status] for the current user.
     *
     * @param  int  $user_id
     * @param  int  $sessionLimit
     *
     * @return array list of statuses (session_id-course_code => status)
     *
     * @throws Exception
     */
    public static function get_personal_session_course_list($user_id, $sessionLimit = null)
    {
        // Database Table Definitions
        $tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);
        $tbl_user = Database::get_main_table(TABLE_MAIN_USER);
        $tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
        $tbl_session_user = Database::get_main_table(TABLE_MAIN_SESSION_USER);
        $tbl_course_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $tbl_session_course_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $tbl_url_course = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);

        $user_id = (int) $user_id;

        if (empty($user_id)) {
            return [];
        }

        $sessionRepo = Container::getSessionRepository();

        $user = api_get_user_entity($user_id);
        $url = null;
        $formattedUserName = Container::$container->get(NameConventionHelper::class)->getPersonName($user);

        // We filter the courses from the URL (MultiURL)
        $join_access_url = $where_access_url = '';
        $access_url_id = -1;

        if (api_get_multiple_access_url()) {
            $access_url_id = api_get_current_access_url_id();
            if (-1 != $access_url_id) {
                $url = api_get_url_entity($access_url_id);
                $join_access_url = " LEFT JOIN $tbl_url_course url_rel_course ON url_rel_course.c_id = course.id ";
                $where_access_url = " AND url_rel_course.access_url_id = $access_url_id ";
            }
        }

        // Courses in which we subscribed out of any session
        $sql = "SELECT
                course.code,
                course_rel_user.status course_rel_status,
                course_rel_user.sort sort,
                course_rel_user.user_course_cat user_course_cat
            FROM $tbl_course_user course_rel_user
            LEFT JOIN $tbl_course course
                ON course.id = course_rel_user.c_id
            $join_access_url
            WHERE
                course_rel_user.user_id = '".$user_id."' AND
                course_rel_user.relation_type <> ".COURSE_RELATION_TYPE_RRHH."
                $where_access_url
            ORDER BY course_rel_user.sort, course.title ASC";

        $course_list_sql_result = Database::query($sql);
        $personal_course_list = [];
        if (Database::num_rows($course_list_sql_result) > 0) {
            while ($result_row = Database::fetch_assoc($course_list_sql_result)) {
                $course_info = api_get_course_info($result_row['code']);
                $result_row['course_info'] = $course_info;
                $personal_course_list[] = $result_row;
            }
        }

        $coachCourseConditions = '';
        // Getting sessions that are related to a coach in the session_rel_course_rel_user table
        if (api_is_allowed_to_create_course()) {
            $sessionListFromCourseCoach = [];
            $sql = " SELECT DISTINCT session_id
                FROM $tbl_session_course_user
                WHERE user_id = $user_id AND status = ".SessionEntity::COURSE_COACH;

            $result = Database::query($sql);
            if (Database::num_rows($result)) {
                $result = Database::store_result($result);
                foreach ($result as $session) {
                    $sessionListFromCourseCoach[] = $session['session_id'];
                }
            }
            if (!empty($sessionListFromCourseCoach)) {
                $condition = implode("','", $sessionListFromCourseCoach);
                $coachCourseConditions = " OR ( s.id IN ('$condition'))";
            }
        }

        // Get the list of sessions where the user is subscribed
        // This is divided into two different queries
        $sessions = [];
        $sessionLimitRestriction = '';
        if (!empty($sessionLimit)) {
            $sessionLimit = (int) $sessionLimit;
            $sessionLimitRestriction = "LIMIT $sessionLimit";
        }

        $sql = "SELECT DISTINCT
                    s.id,
                    s.title,
                    su.access_start_date AS access_start_date,
                    su.access_end_date AS access_end_date
                FROM $tbl_session_user su
                INNER JOIN $tbl_session s ON (s.id = su.session_id)
                WHERE (
                    su.user_id = $user_id AND
                    su.relation_type = ".SessionEntity::STUDENT."
                )
                $coachCourseConditions
                ORDER BY su.access_start_date, su.access_end_date, s.title
                $sessionLimitRestriction
        ";


        $result = Database::query($sql);
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_assoc($result)) {
                $sessions[$row['id']] = $row;
            }
        }

        $sql = "SELECT DISTINCT
            s.id, s.title, s.access_start_date, s.access_end_date
            FROM $tbl_session s
            INNER JOIN $tbl_session_user sru ON sru.session_id = s.id
            WHERE (
                sru.user_id = $user_id AND sru.relation_type = ".SessionEntity::GENERAL_COACH."
            )
            $coachCourseConditions
            ORDER BY s.access_start_date, s.access_end_date, s.title";

        $result = Database::query($sql);
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_assoc($result)) {
                if (empty($sessions[$row['id']])) {
                    $sessions[$row['id']] = $row;
                }
            }
        }

        if (api_is_allowed_to_create_course()) {
            foreach ($sessions as $enreg) {
                $session_id = $enreg['id'];
                $session_visibility = api_get_session_visibility($session_id);
                $session = api_get_session_entity($session_id);

                if (SESSION_INVISIBLE == $session_visibility) {
                    continue;
                }

                $coursesAsGeneralCoach = $sessionRepo->getSessionCoursesByStatusInUserSubscription(
                    $user,
                    $session,
                    SessionEntity::GENERAL_COACH,
                    $url
                );
                $coursesAsCourseCoach = $sessionRepo->getSessionCoursesByStatusInCourseSubscription(
                    $user,
                    $session,
                    SessionEntity::COURSE_COACH,
                    $url
                );

                $sessionRelCourses = array_merge($coursesAsGeneralCoach, $coursesAsCourseCoach);

                // MultiURL: ensure courses are filtered by access_url_rel_course (same behavior as legacy SQL above)
                if (api_get_multiple_access_url() && -1 != $access_url_id && !empty($sessionRelCourses)) {
                    $courseIds = [];
                    foreach ($sessionRelCourses as $src) {
                        $courseIds[] = (int) $src->getCourse()->getId();
                    }
                    $courseIds = array_values(array_unique($courseIds));

                    if (!empty($courseIds)) {
                        $ids = implode(',', $courseIds);
                        $sqlAllowed = "SELECT c_id FROM $tbl_url_course WHERE access_url_id = $access_url_id AND c_id IN ($ids)";
                        $resAllowed = Database::query($sqlAllowed);

                        $allowed = [];
                        while ($r = Database::fetch_assoc($resAllowed)) {
                            $allowed[(int) $r['c_id']] = true;
                        }

                        $sessionRelCourses = array_values(array_filter(
                            $sessionRelCourses,
                            function (SessionRelCourse $src) use ($allowed) {
                                return isset($allowed[(int) $src->getCourse()->getId()]);
                            }
                        ));
                    } else {
                        $sessionRelCourses = [];
                    }
                }

                $coursesInSession = array_map(
                    function (SessionRelCourse $courseInSession) {
                        $course = $courseInSession->getCourse();

                        return [
                            'code' => $course->getCode(),
                            'i' => $course->getTitle(),
                            'l' => $course->getCourseLanguage(),
                            'sort' => 1,
                        ];
                    },
                    $sessionRelCourses
                );

                foreach ($coursesInSession as $result_row) {
                    $result_row['t'] = $formattedUserName;
                    $result_row['email'] = $user->getEmail();
                    $result_row['access_start_date'] = $session->getAccessStartDate()?->format('Y-m-d H:i:s');
                    $result_row['access_end_date'] = $session->getAccessEndDate()?->format('Y-m-d H:i:s');
                    $result_row['session_id'] = $session->getId();
                    $result_row['session_name'] = $session->getTitle();
                    $result_row['course_info'] = api_get_course_info($result_row['code']);
                    $key = $result_row['session_id'].' - '.$result_row['code'];
                    $personal_course_list[$key] = $result_row;
                }
            }
        }

        foreach ($sessions as $enreg) {
            $session_id = $enreg['id'];
            $session_visibility = api_get_session_visibility($session_id);
            if (SESSION_INVISIBLE == $session_visibility) {
                continue;
            }

            // MultiURL filter for this legacy SQL too (otherwise it may return courses from other URLs)
            $join_access_url_2 = '';
            $where_access_url_2 = '';
            if (api_get_multiple_access_url() && -1 != $access_url_id) {
                $join_access_url_2 = " INNER JOIN $tbl_url_course url_rel_course ON url_rel_course.c_id = course.id ";
                $where_access_url_2 = " AND url_rel_course.access_url_id = $access_url_id ";
            }

            $sql = "SELECT DISTINCT
            course.code code,
            course.title i, CONCAT(user.lastname,' ',user.firstname) t,
            email,
            course.course_language l,
            1 sort,
            access_start_date,
            access_end_date,
            session.id as session_id,
            session.title as session_name,
            IF((session_course_user.user_id = $user_id AND session_course_user.status = ".SessionEntity::COURSE_COACH."),'2', '5')
        FROM $tbl_session_course_user as session_course_user
        INNER JOIN $tbl_course AS course
            ON course.id = session_course_user.c_id AND session_course_user.session_id = $session_id
        $join_access_url_2
        INNER JOIN $tbl_session as session
            ON session_course_user.session_id = session.id
        LEFT JOIN $tbl_user as user ON user.id = session_course_user.user_id
        WHERE session_course_user.user_id = $user_id
            $where_access_url_2
        ORDER BY i";

            $course_list_sql_result = Database::query($sql);
            while ($result_row = Database::fetch_assoc($course_list_sql_result)) {
                $result_row['course_info'] = api_get_course_info($result_row['code']);
                $key = $result_row['session_id'].' - '.$result_row['code'];
                if (!isset($personal_course_list[$key])) {
                    $personal_course_list[$key] = $result_row;
                }
            }
        }

        return $personal_course_list;
    }

    /**
     * Gives a list of courses for the given user in the given session.
     *
     * @param int $user_id
     * @param int $session_id
     *
     * @return array list of statuses (session_id-course_code => status)
     */
    public static function get_courses_list_by_session($user_id, $session_id)
    {
        // Database Table Definitions
        $tableCourse = Database::get_main_table(TABLE_MAIN_COURSE);
        $tbl_session_course_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $tbl_session_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);

        $user_id = (int) $user_id;
        $session_id = (int) $session_id;

        $sessionRepo = Container::getSessionRepository();

        $user = api_get_user_entity($user_id);
        $session = api_get_session_entity($session_id);
        $url = null;

        // We filter the courses from the URL
        $join_access_url = $where_access_url = '';
        if (api_get_multiple_access_url()) {
            $urlId = api_get_current_access_url_id();
            if (-1 != $urlId) {
                $url = api_get_url_entity($urlId);
                $tbl_url_session = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);
                $join_access_url = " ,  $tbl_url_session url_rel_session ";
                $where_access_url = " AND access_url_id = $urlId AND url_rel_session.session_id = $session_id ";
            }
        }

        /* This query is very similar to the query below, but it will check the
        session_rel_course_user table if there are courses registered
        to our user or not */
        $sql = "SELECT DISTINCT
                    c.title,
                    c.visibility,
                    c.id as real_id,
                    c.code as course_code,
                    sc.position,
                    c.unsubscribe
                FROM $tbl_session_course_user as scu
                INNER JOIN $tbl_session_course sc
                ON (scu.session_id = sc.session_id AND scu.c_id = sc.c_id)
                INNER JOIN $tableCourse as c
                ON (scu.c_id = c.id)
                $join_access_url
                WHERE
                    scu.user_id = $user_id AND
                    scu.session_id = $session_id
                    $where_access_url
                ORDER BY sc.position ASC";

        $myCourseList = [];
        $courses = [];
        $result = Database::query($sql);
        if (Database::num_rows($result) > 0) {
            while ($result_row = Database::fetch_assoc($result)) {
                $result_row['status'] = 5;
                if (!in_array($result_row['real_id'], $courses)) {
                    $position = $result_row['position'];
                    if (!isset($myCourseList[$position])) {
                        $myCourseList[$position] = $result_row;
                    } else {
                        $myCourseList[] = $result_row;
                    }
                    $courses[] = $result_row['real_id'];
                }
            }
        }

        if (api_is_allowed_to_create_course()) {
            $coursesAsGeneralCoach = $sessionRepo->getSessionCoursesByStatusInUserSubscription(
                $user,
                $session,
                SessionEntity::GENERAL_COACH,
                $url
            );
            $coursesAsCourseCoach = $sessionRepo->getSessionCoursesByStatusInCourseSubscription(
                $user,
                $session,
                SessionEntity::COURSE_COACH,
                $url
            );

            $coursesInSession = array_map(
                function (SessionRelCourse $courseInSession) {
                    $course = $courseInSession->getCourse();

                    return [
                        'title' => $course->getTitle(),
                        'visibility' => $course->getVisibility(),
                        'real_id' => $course->getId(),
                        'course_code' => $course->getCode(),
                        'position' => $courseInSession->getPosition(),
                        'unsubscribe' => $course->getUnsubscribe(),
                    ];
                },
                array_merge($coursesAsGeneralCoach, $coursesAsCourseCoach)
            );

            foreach ($coursesInSession as $result_row) {
                $result_row['status'] = 2;
                if (!in_array($result_row['real_id'], $courses)) {
                    $position = $result_row['position'];
                    if (!isset($myCourseList[$position])) {
                        $myCourseList[$position] = $result_row;
                    } else {
                        $myCourseList[] = $result_row;
                    }
                    $courses[] = $result_row['real_id'];
                }
            }
        }

        if (api_is_drh()) {
            $sessionList = SessionManager::get_sessions_followed_by_drh($user_id);
            $sessionList = array_keys($sessionList);
            if (in_array($session_id, $sessionList)) {
                $courseList = SessionManager::get_course_list_by_session_id($session_id);
                if (!empty($courseList)) {
                    foreach ($courseList as $course) {
                        if (!in_array($course['id'], $courses)) {
                            $position = $course['position'];
                            if (!isset($myCourseList[$position])) {
                                $myCourseList[$position] = $course;
                            } else {
                                $myCourseList[] = $course;
                            }
                        }
                    }
                }
            }
        } else {
            //check if user is general coach for this session
            if ($session && $session->hasUserAsGeneralCoach($user)) {
                $courseList = SessionManager::get_course_list_by_session_id($session_id);
                if (!empty($courseList)) {
                    foreach ($courseList as $course) {
                        if (!in_array($course['id'], $courses)) {
                            $position = $course['position'];
                            if (!isset($myCourseList[$position])) {
                                $myCourseList[$position] = $course;
                            } else {
                                $myCourseList[] = $course;
                            }
                        }
                    }
                }
            }
        }

        if (!empty($myCourseList)) {
            ksort($myCourseList);
            $checkPosition = array_filter(array_column($myCourseList, 'position'));
            if (empty($checkPosition)) {
                // The session course list doesn't have any position,
                // then order the course list by course code
                $list = array_column($myCourseList, 'course_code');
                array_multisort($myCourseList, SORT_ASC, $list);
            }
        }

        return $myCourseList;
    }

    /**
     * Get user id from a username.
     *
     * @param string $username
     *
     * @return int User ID (or false if not found)
     */
    public static function get_user_id_from_username($username)
    {
        if (empty($username)) {
            return false;
        }
        $username = trim($username);
        $username = Database::escape_string($username);
        $t_user = Database::get_main_table(TABLE_MAIN_USER);
        $sql = "SELECT id FROM $t_user WHERE username = '$username'";
        $res = Database::query($sql);

        if (false === $res) {
            return false;
        }
        if (1 !== Database::num_rows($res)) {
            return false;
        }
        $row = Database::fetch_array($res);

        return $row['id'];
    }

    /**
     * Get the users files upload from his share_folder.
     *
     * @param string $user_id      User ID
     * @param string $course       course directory
     * @param string $resourceType resource type: images, all
     *
     * @return string
     */
    /*public static function get_user_upload_files_by_course(
        $user_id,
        $course,
        $resourceType = 'all'
    ) {
        $return = '';
        $user_id = (int) $user_id;

        if (!empty($user_id) && !empty($course)) {
            $path = api_get_path(SYS_COURSE_PATH).$course.'/document/shared_folder/sf_user_'.$user_id.'/';
            $web_path = api_get_path(WEB_COURSE_PATH).$course.'/document/shared_folder/sf_user_'.$user_id.'/';
            $file_list = [];

            if (is_dir($path)) {
                $handle = opendir($path);
                while ($file = readdir($handle)) {
                    if ('.' == $file || '..' == $file || '.htaccess' == $file || is_dir($path.$file)) {
                        continue; // skip current/parent directory and .htaccess
                    }
                    $file_list[] = $file;
                }
                if (count($file_list) > 0) {
                    $return = "<h4>$course</h4>";
                    $return .= '<ul class="thumbnails">';
                }
                $extensionList = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'tif'];
                foreach ($file_list as $file) {
                    if ('all' == $resourceType) {
                        $return .= '<li>
                            <a href="'.$web_path.urlencode($file).'" target="_blank">'.htmlentities($file).'</a></li>';
                    } elseif ('images' == $resourceType) {
                        //get extension
                        $ext = explode('.', $file);
                        if (isset($ext[1]) && in_array($ext[1], $extensionList)) {
                            $return .= '<li class="span2">
                                            <a class="thumbnail" href="'.$web_path.urlencode($file).'" target="_blank">
                                                <img src="'.$web_path.urlencode($file).'" >
                                            </a>
                                        </li>';
                        }
                    }
                }
                if (count($file_list) > 0) {
                    $return .= '</ul>';
                }
            }
        }

        return $return;
    }*/

    /**
     * Gets the API key (or keys) and return them into an array.
     *
     * @param int     Optional user id (defaults to the result of api_get_user_id())
     * @param string $api_service
     *
     * @return mixed Non-indexed array containing the list of API keys for this user, or FALSE on error
     */
    public static function get_api_keys($user_id = null, $api_service = 'default')
    {
        if ($user_id != strval(intval($user_id))) {
            return false;
        }
        if (empty($user_id)) {
            $user_id = api_get_user_id();
        }
        if (false === $user_id) {
            return false;
        }
        $service_name = Database::escape_string($api_service);
        if (false === is_string($service_name)) {
            return false;
        }
        $t_api = Database::get_main_table(TABLE_MAIN_USER_API_KEY);
        $sql = "SELECT * FROM $t_api WHERE user_id = $user_id AND api_service='$api_service';";
        $res = Database::query($sql);
        if (false === $res) {
            return false;
        } //error during query
        $num = Database::num_rows($res);
        if (0 == $num) {
            return false;
        }
        $list = [];
        while ($row = Database::fetch_array($res)) {
            $list[$row['id']] = $row['api_key'];
        }

        return $list;
    }

    /**
     * Adds a new API key to the users' account.
     *
     * @param   int     Optional user ID (defaults to the results of api_get_user_id())
     * @param string $api_service
     *
     * @return bool True on success, false on failure
     */
    public static function add_api_key($user_id = null, $api_service = 'default')
    {
        if ($user_id != strval(intval($user_id))) {
            return false;
        }
        if (empty($user_id)) {
            $user_id = api_get_user_id();
        }
        if (false === $user_id) {
            return false;
        }
        $service_name = Database::escape_string($api_service);
        if (false === is_string($service_name)) {
            return false;
        }
        $t_api = Database::get_main_table(TABLE_MAIN_USER_API_KEY);
        $md5 = md5((time() + ($user_id * 5)) - rand(10000, 10000)); //generate some kind of random key
        $sql = "INSERT INTO $t_api (user_id, api_key,api_service) VALUES ($user_id,'$md5','$service_name')";
        $res = Database::query($sql);
        if (false === $res) {
            return false;
        } //error during query
        $num = Database::insert_id();

        return 0 == $num ? false : $num;
    }

    /**
     * Deletes an API key from the user's account.
     *
     * @param   int     API key's internal ID
     *
     * @return bool True on success, false on failure
     */
    public static function delete_api_key($key_id)
    {
        if ($key_id != strval(intval($key_id))) {
            return false;
        }
        if (false === $key_id) {
            return false;
        }
        $t_api = Database::get_main_table(TABLE_MAIN_USER_API_KEY);
        $sql = "SELECT * FROM $t_api WHERE id = ".$key_id;
        $res = Database::query($sql);
        if (false === $res) {
            return false;
        } //error during query
        $num = Database::num_rows($res);
        if (1 !== $num) {
            return false;
        }
        $sql = "DELETE FROM $t_api WHERE id = ".$key_id;
        $res = Database::query($sql);
        if (false === $res) {
            return false;
        } //error during query

        return true;
    }

    /**
     * Regenerate an API key from the user's account.
     *
     * @param   int     user ID (defaults to the results of api_get_user_id())
     * @param   string  API key's internal ID
     *
     * @return int num
     */
    public static function update_api_key($user_id, $api_service)
    {
        if ($user_id != strval(intval($user_id))) {
            return false;
        }
        if (false === $user_id) {
            return false;
        }
        $service_name = Database::escape_string($api_service);
        if (false === is_string($service_name)) {
            return false;
        }
        $t_api = Database::get_main_table(TABLE_MAIN_USER_API_KEY);
        $sql = "SELECT id FROM $t_api
                WHERE user_id=".$user_id." AND api_service='".$api_service."'";
        $res = Database::query($sql);
        $num = Database::num_rows($res);
        if (1 == $num) {
            $id_key = Database::fetch_assoc($res);
            self::delete_api_key($id_key['id']);
            $num = self::add_api_key($user_id, $api_service);
        } elseif (0 == $num) {
            $num = self::add_api_key($user_id, $api_service);
        }

        return $num;
    }

    /**
     * @param   int     user ID (defaults to the results of api_get_user_id())
     * @param   string    API key's internal ID
     *
     * @return int row ID, or return false if not found
     */
    public static function get_api_key_id($user_id, $api_service)
    {
        if ($user_id != strval(intval($user_id))) {
            return false;
        }
        if (false === $user_id) {
            return false;
        }
        if (empty($api_service)) {
            return false;
        }
        $t_api = Database::get_main_table(TABLE_MAIN_USER_API_KEY);
        $api_service = Database::escape_string($api_service);
        $sql = "SELECT id FROM $t_api
                WHERE user_id=".$user_id." AND api_service='".$api_service."'";
        $res = Database::query($sql);
        if (Database::num_rows($res) < 1) {
            return false;
        }
        $row = Database::fetch_assoc($res);

        return $row['id'];
    }

    /**
     * Checks if a user_id is platform admin.
     *
     * @param   int user ID
     *
     * @return bool True if is admin, false otherwise
     *
     * @see main_api.lib.php::api_is_platform_admin() for a context-based check
     */
    public static function is_admin($user_id)
    {
        $user_id = (int) $user_id;
        if (empty($user_id)) {
            return false;
        }
        $admin_table = Database::get_main_table(TABLE_MAIN_ADMIN);
        $sql = "SELECT * FROM $admin_table WHERE user_id = $user_id";
        $res = Database::query($sql);

        return 1 === Database::num_rows($res);
    }

    /**
     * Get the total count of users.
     *
     * @param ?int $status Status of users to be counted
     * @param ?int $access_url_id Access URL ID (optional)
     * @param ?int $active
     *
     * @return mixed Number of users or false on error
     * @throws \Doctrine\DBAL\Exception
     */
    public static function get_number_of_users(
        ?int $status = 0,
        ?int $access_url_id = 1,
        ?int $active = null,
        ?string $dateFrom = null,
        ?string $dateUntil = null
    ): mixed {
        $tableUser = Database::get_main_table(TABLE_MAIN_USER);
        $tableAccessUrlRelUser = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);

        if (api_is_multiple_url_enabled()) {
            $sql = "SELECT count(u.id)
                    FROM $tableUser u
                    INNER JOIN $tableAccessUrlRelUser url_user
                    ON (u.id = url_user.user_id)
                    WHERE url_user.access_url_id = $access_url_id
            ";
        } else {
            $sql = "SELECT count(u.id)
                    FROM $tableUser u
                    WHERE 1 = 1 ";
        }

        $status = (int) $status;
        if (!empty($status) && $status > 0) {
            $sql .= " AND u.status = $status ";
        }

        if (isset($active)) {
            $active = (int) $active;
            $sql .= " AND u.active = $active ";
        }

        if (!empty($dateFrom)) {
            $dateFrom = api_get_utc_datetime("$dateFrom 00:00:00");
            $sql .= " AND u.created_at >= '$dateFrom' ";
        }
        if (!empty($dateUntil)) {
            $dateUntil = api_get_utc_datetime("$dateUntil 23:59:59");
            $sql .= " AND u.created_at <= '$dateUntil' ";
        }

        $res = Database::query($sql);
        if (1 === Database::num_rows($res)) {
            return (int) Database::result($res, 0, 0);
        }

        return false;
    }

    /**
     * Gets the tags of a specific field_id
     * USER TAGS.
     *
     * Instructions to create a new user tag by Julio Montoya <gugli100@gmail.com>
     *
     * 1. Create a new extra field in main/admin/user_fields.php with the "TAG" field type make it available and visible.
     *    Called it "books" for example.
     * 2. Go to profile main/auth/profile.php There you will see a special input (facebook style) that will show suggestions of tags.
     * 3. All the tags are registered in the user_tag table and the relationship between user and tags is in the user_rel_tag table
     * 4. Tags are independent this means that tags can't be shared between tags + book + hobbies.
     * 5. Test and enjoy.
     *
     * @param string $tag
     * @param int    $field_id      field_id
     * @param string $return_format how we are going to result value in array or in a string (json)
     * @param $limit
     *
     * @return mixed
     */
    public static function get_tags($tag, $field_id, $return_format = 'json', $limit = 10)
    {
        // database table definition
        $table_user_tag = Database::get_main_table(TABLE_MAIN_TAG);
        $field_id = (int) $field_id;
        $limit = (int) $limit;
        $tag = trim(Database::escape_string($tag));

        // all the information of the field
        $sql = "SELECT DISTINCT id, tag from $table_user_tag
                WHERE field_id = $field_id AND tag LIKE '$tag%' ORDER BY tag LIMIT $limit";
        $result = Database::query($sql);
        $return = [];
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_assoc($result)) {
                $return[] = ['id' => $row['tag'], 'text' => $row['tag']];
            }
        }
        if ('json' === $return_format) {
            $return = json_encode($return);
        }

        return $return;
    }

    /**
     * @param int $field_id
     * @param int $limit
     *
     * @return array
     */
    public static function get_top_tags($field_id, $limit = 100)
    {
        // database table definition
        $table_user_tag = Database::get_main_table(TABLE_MAIN_TAG);
        $table_user_tag_values = Database::get_main_table(TABLE_MAIN_USER_REL_TAG);
        $field_id = (int) $field_id;
        $limit = (int) $limit;
        // all the information of the field
        $sql = "SELECT count(*) count, tag FROM $table_user_tag_values  uv
                INNER JOIN $table_user_tag ut
                ON (ut.id = uv.tag_id)
                WHERE field_id = $field_id
                GROUP BY tag_id
                ORDER BY count DESC
                LIMIT $limit";
        $result = Database::query($sql);
        $return = [];
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_assoc($result)) {
                $return[] = $row;
            }
        }

        return $return;
    }

    /**
     * Get user's tags.
     *
     * @param int $user_id
     * @param int $field_id
     *
     * @return array
     */
    public static function get_user_tags($user_id, $field_id)
    {
        // database table definition
        $table_user_tag = Database::get_main_table(TABLE_MAIN_TAG);
        $table_user_tag_values = Database::get_main_table(TABLE_MAIN_USER_REL_TAG);
        $field_id = (int) $field_id;
        $user_id = (int) $user_id;

        // all the information of the field
        $sql = "SELECT ut.id, tag, count
                FROM $table_user_tag ut
                INNER JOIN $table_user_tag_values uv
                ON (uv.tag_id=ut.ID)
                WHERE field_id = $field_id AND user_id = $user_id
                ORDER BY tag";
        $result = Database::query($sql);
        $return = [];
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_assoc($result)) {
                $return[$row['id']] = ['tag' => $row['tag'], 'count' => $row['count']];
            }
        }

        return $return;
    }

    /**
     * Get user's tags.
     *
     * @param int  $user_id
     * @param int  $field_id
     * @param bool $show_links show links or not
     *
     * @return string
     */
    public static function get_user_tags_to_string($user_id, $field_id, $show_links = true)
    {
        // database table definition
        $table_user_tag = Database::get_main_table(TABLE_MAIN_TAG);
        $table_user_tag_values = Database::get_main_table(TABLE_MAIN_USER_REL_TAG);
        $field_id = (int) $field_id;
        $user_id = (int) $user_id;

        // all the information of the field
        $sql = "SELECT ut.id, tag,count FROM $table_user_tag ut
                INNER JOIN $table_user_tag_values uv
                ON (uv.tag_id = ut.id)
                WHERE field_id = $field_id AND user_id = $user_id
                ORDER BY tag";

        $result = Database::query($sql);
        $return = [];
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_assoc($result)) {
                $return[$row['id']] = ['tag' => $row['tag'], 'count' => $row['count']];
            }
        }
        $user_tags = $return;
        $tag_tmp = [];
        foreach ($user_tags as $tag) {
            if ($show_links) {
                $tag_tmp[] = '<a href="'.api_get_path(WEB_PATH).'main/search/index.php?q='.$tag['tag'].'">'.
                    $tag['tag'].
                '</a>';
            } else {
                $tag_tmp[] = $tag['tag'];
            }
        }

        if (is_array($user_tags) && count($user_tags) > 0) {
            return implode(', ', $tag_tmp);
        } else {
            return '';
        }
    }

    /**
     * Get the tag id.
     *
     * @param int $tag
     * @param int $field_id
     *
     * @return int returns 0 if fails otherwise the tag id
     */
    public static function get_tag_id($tag, $field_id)
    {
        $table_user_tag = Database::get_main_table(TABLE_MAIN_TAG);
        $tag = Database::escape_string($tag);
        $field_id = (int) $field_id;
        //with COLLATE latin1_bin to select query in a case sensitive mode
        $sql = "SELECT id FROM $table_user_tag
                WHERE tag LIKE '$tag' AND field_id = $field_id";
        $result = Database::query($sql);
        if (Database::num_rows($result) > 0) {
            $row = Database::fetch_assoc($result);

            return $row['id'];
        } else {
            return 0;
        }
    }

    /**
     * Get the tag id.
     *
     * @param int $tag_id
     * @param int $field_id
     *
     * @return int 0 if fails otherwise the tag id
     */
    public static function get_tag_id_from_id($tag_id, $field_id)
    {
        $table_user_tag = Database::get_main_table(TABLE_MAIN_TAG);
        $tag_id = (int) $tag_id;
        $field_id = (int) $field_id;
        $sql = "SELECT id FROM $table_user_tag
                WHERE id = '$tag_id' AND field_id = $field_id";
        $result = Database::query($sql);
        if (Database::num_rows($result) > 0) {
            $row = Database::fetch_assoc($result);

            return $row['id'];
        } else {
            return false;
        }
    }

    /**
     * Adds a user-tag value.
     *
     * @param mixed $tag
     * @param int   $user_id
     * @param int   $field_id field id of the tag
     *
     * @return bool True if the tag was inserted or updated. False otherwise.
     *              The return value doesn't take into account *values* added to the tag.
     *              Only the creation/update of the tag field itself.
     */
    public static function add_tag($tag, $user_id, $field_id)
    {
        // database table definition
        $table_user_tag = Database::get_main_table(TABLE_MAIN_TAG);
        $table_user_tag_values = Database::get_main_table(TABLE_MAIN_USER_REL_TAG);
        $tag = trim(Database::escape_string($tag));
        $user_id = (int) $user_id;
        $field_id = (int) $field_id;

        $tag_id = self::get_tag_id($tag, $field_id);

        /* IMPORTANT
         *  @todo we don't create tags with numbers
         *
         */

        //this is a new tag
        if (0 == $tag_id) {
            //the tag doesn't exist
            $sql = "INSERT INTO $table_user_tag (tag, field_id,count) VALUES ('$tag','$field_id', count + 1)";
            Database::query($sql);
            $last_insert_id = Database::insert_id();
        } else {
            //the tag exists we update it
            $sql = "UPDATE $table_user_tag SET count = count + 1 WHERE id  = $tag_id";
            Database::query($sql);
            $last_insert_id = $tag_id;
        }

        if (!empty($last_insert_id) && (0 != $last_insert_id)) {
            //we insert the relationship user-tag
            $sql = "SELECT tag_id FROM $table_user_tag_values
                    WHERE user_id = $user_id AND tag_id = $last_insert_id ";
            $result = Database::query($sql);
            //if the relationship does not exist we create it
            if (0 == Database::num_rows($result)) {
                $sql = "INSERT INTO $table_user_tag_values SET user_id = $user_id, tag_id = $last_insert_id";
                Database::query($sql);
            }

            return true;
        }

        return false;
    }

    /**
     * Deletes an user tag.
     *
     * @param int $user_id
     * @param int $field_id
     */
    public static function delete_user_tags($user_id, $field_id)
    {
        // database table definition
        $table_user_tag = Database::get_main_table(TABLE_MAIN_TAG);
        $table_user_tag_values = Database::get_main_table(TABLE_MAIN_USER_REL_TAG);
        $user_id = (int) $user_id;

        $tags = self::get_user_tags($user_id, $field_id);
        if (is_array($tags) && count($tags) > 0) {
            foreach ($tags as $key => $tag) {
                if ($tag['count'] > '0') {
                    $sql = "UPDATE $table_user_tag SET count = count - 1  WHERE id = $key ";
                    Database::query($sql);
                }
                $sql = "DELETE FROM $table_user_tag_values
                        WHERE user_id = $user_id AND tag_id = $key";
                Database::query($sql);
            }
        }
    }

    /**
     * Process the tag list comes from the UserManager::update_extra_field_value() function.
     *
     * @param array $tags     the tag list that will be added
     * @param int   $user_id
     * @param int   $field_id
     *
     * @return bool
     */
    public static function process_tags($tags, $user_id, $field_id)
    {
        // We loop the tags and add it to the DB
        if (is_array($tags)) {
            foreach ($tags as $tag) {
                self::add_tag($tag, $user_id, $field_id);
            }
        } else {
            self::add_tag($tags, $user_id, $field_id);
        }

        return true;
    }

    /**
     * Returns a list of all administrators.
     *
     * @return array
     */
    public static function get_all_administrators()
    {
        $table_user = Database::get_main_table(TABLE_MAIN_USER);
        $table_admin = Database::get_main_table(TABLE_MAIN_ADMIN);
        $tbl_url_rel_user = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        $access_url_id = api_get_current_access_url_id();
        if (api_get_multiple_access_url()) {
            $sql = "SELECT admin.user_id, username, firstname, lastname, email, active, locale
                    FROM $tbl_url_rel_user as url
                    INNER JOIN $table_admin as admin
                    ON (admin.user_id=url.user_id)
                    INNER JOIN $table_user u
                    ON (u.id=admin.user_id)
                    WHERE access_url_id ='".$access_url_id."'";
        } else {
            $sql = "SELECT admin.user_id, username, firstname, lastname, email, active, locale
                    FROM $table_admin as admin
                    INNER JOIN $table_user u
                    ON (u.id=admin.user_id)";
        }
        $sql .= !str_contains($sql, 'WHERE') ? ' WHERE u.active <> '.USER_SOFT_DELETED : ' AND u.active <> '.USER_SOFT_DELETED;
        $result = Database::query($sql);
        $return = [];
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_assoc($result)) {
                $return[$row['user_id']] = $row;
            }
        }

        return $return;
    }

    /**
     * Search an user (tags, first name, last name and email ).
     *
     * @param string $tag
     * @param int    $field_id        field id of the tag
     * @param int    $from            where to start in the query
     * @param int    $number_of_items
     * @param bool   $getCount        get count or not
     *
     * @return array
     */
    public static function get_all_user_tags(
        $tag,
        $field_id = 0,
        $from = 0,
        $number_of_items = 10,
        $getCount = false
    ) {
        $user_table = Database::get_main_table(TABLE_MAIN_USER);
        $table_user_tag = Database::get_main_table(TABLE_MAIN_TAG);
        $table_user_tag_values = Database::get_main_table(TABLE_MAIN_USER_REL_TAG);
        $access_url_rel_user_table = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);

        $field_id = intval($field_id);
        $from = intval($from);
        $number_of_items = intval($number_of_items);

        $where_field = "";
        $where_extra_fields = self::get_search_form_where_extra_fields();
        if (0 != $field_id) {
            $where_field = " field_id = $field_id AND ";
        }

        // all the information of the field
        if ($getCount) {
            $select = "SELECT count(DISTINCT u.id) count";
        } else {
            $select = "SELECT DISTINCT u.id, u.username, firstname, lastname, email, tag, picture_uri";
        }

        $sql = " $select
                FROM $user_table u
                INNER JOIN $access_url_rel_user_table url_rel_user
                ON (u.id = url_rel_user.user_id)
                LEFT JOIN $table_user_tag_values uv
                ON (u.id AND uv.user_id AND uv.user_id = url_rel_user.user_id)
                LEFT JOIN $table_user_tag ut ON (uv.tag_id = ut.id)
                WHERE
                    ($where_field tag LIKE '".Database::escape_string($tag."%")."') OR
                    (
                        u.firstname LIKE '".Database::escape_string("%".$tag."%")."' OR
                        u.lastname LIKE '".Database::escape_string("%".$tag."%")."' OR
                        u.username LIKE '".Database::escape_string("%".$tag."%")."' OR
                        concat(u.firstname, ' ', u.lastname) LIKE '".Database::escape_string("%".$tag."%")."' OR
                        concat(u.lastname, ' ', u.firstname) LIKE '".Database::escape_string("%".$tag."%")."'
                     )
                     ".(!empty($where_extra_fields) ? $where_extra_fields : '')."
                     AND url_rel_user.access_url_id=".api_get_current_access_url_id();

        $keyword_active = true;
        // only active users
        if ($keyword_active) {
            $sql .= " AND u.active='1'";
        }
        // avoid anonymous
        $sql .= " AND u.status <> 6 ";
        $sql .= " ORDER BY username";
        $sql .= " LIMIT $from , $number_of_items";

        $result = Database::query($sql);
        $return = [];

        if (Database::num_rows($result) > 0) {
            if ($getCount) {
                $row = Database::fetch_assoc($result);

                return $row['count'];
            }
            while ($row = Database::fetch_assoc($result)) {
                $return[$row['id']] = $row;
            }
        }

        return $return;
    }

    /**
     * Get extra filterable user fields (only type select).
     *
     * @return array Array of extra fields as [int => ['name' => ..., 'variable' => ..., 'data' => ...]] (
     *               or empty array if no extra field)
     */
    public static function getExtraFilterableFields()
    {
        $extraFieldList = self::get_extra_fields();
        $fields = [];
        if (is_array($extraFieldList)) {
            foreach ($extraFieldList as $extraField) {
                // If is enabled to filter and is a "<select>" field type
                if (1 == $extraField[8] && 4 == $extraField[2]) {
                    $fields[] = [
                        'name' => $extraField[3],
                        'variable' => $extraField[1],
                        'data' => $extraField[9],
                    ];
                }
            }
        }

        return $fields;
    }

    /**
     * Get extra where clauses for finding users based on extra filterable user fields (type select).
     *
     * @return string With AND clauses based on user's ID which have the values to search in extra user fields
     *                (or empty if no extra field exists)
     */
    public static function get_search_form_where_extra_fields()
    {
        $useExtraFields = false;
        $extraFields = self::getExtraFilterableFields();
        $extraFieldResult = [];
        if (is_array($extraFields) && count($extraFields) > 0) {
            foreach ($extraFields as $extraField) {
                $varName = 'field_'.$extraField['variable'];
                if (self::is_extra_field_available($extraField['variable'])) {
                    if (isset($_GET[$varName]) && '0' != $_GET[$varName]) {
                        $useExtraFields = true;
                        $extraFieldResult[] = self::get_extra_user_data_by_value(
                            $extraField['variable'],
                            $_GET[$varName]
                        );
                    }
                }
            }
        }

        if ($useExtraFields) {
            $finalResult = [];
            if (count($extraFieldResult) > 1) {
                for ($i = 0; $i < count($extraFieldResult) - 1; $i++) {
                    if (is_array($extraFieldResult[$i]) && is_array($extraFieldResult[$i + 1])) {
                        $finalResult = array_intersect($extraFieldResult[$i], $extraFieldResult[$i + 1]);
                    }
                }
            } else {
                $finalResult = $extraFieldResult[0];
            }

            if (is_array($finalResult) && count($finalResult) > 0) {
                $whereFilter = " AND u.id IN  ('".implode("','", $finalResult)."') ";
            } else {
                //no results
                $whereFilter = " AND u.id  = -1 ";
            }

            return $whereFilter;
        }

        return '';
    }

    /**
     * Show the search form.
     *
     * @param string $query the value of the search box
     *
     * @throws Exception
     *
     * @return string HTML form
     */
    public static function get_search_form($query, $defaultParams = [])
    {
        $searchType = isset($_GET['search_type']) ? $_GET['search_type'] : null;
        $form = new FormValidator(
            'search_user',
            'get',
            api_get_path(WEB_PATH).'main/social/search.php',
            '',
            [],
            FormValidator::LAYOUT_HORIZONTAL
        );

        $query = Security::remove_XSS($query);

        if (!empty($query)) {
            $form->addHeader(get_lang('Results and feedback').' "'.$query.'"');
        }

        $form->addText(
            'q',
            get_lang('Users, Groups'),
            false,
            [
                'id' => 'q',
            ]
        );
        $options = [
            0 => get_lang('Select'),
            1 => get_lang('User'),
            2 => get_lang('Group'),
        ];
        $form->addSelect(
            'search_type',
            get_lang('Type'),
            $options,
            ['onchange' => 'javascript: extra_field_toogle();', 'id' => 'search_type']
        );

        // Extra fields
        $extraFields = self::getExtraFilterableFields();
        $defaults = [];
        if (is_array($extraFields) && count($extraFields) > 0) {
            foreach ($extraFields as $extraField) {
                $varName = 'field_'.$extraField['variable'];
                $options = [
                    0 => get_lang('Select'),
                ];
                foreach ($extraField['data'] as $option) {
                    if (isset($_GET[$varName])) {
                        if ($_GET[$varName] == $option[1]) {
                            $defaults[$option[1]] = true;
                        }
                    }

                    $options[$option[1]] = $option[1];
                }
                $form->addSelect($varName, $extraField['name'], $options);
            }
        }

        $defaults['search_type'] = (int) $searchType;
        $defaults['q'] = $query;

        if (!empty($defaultParams)) {
            $defaults = array_merge($defaults, $defaultParams);
        }
        $form->setDefaults($defaults);
        $form->addButtonSearch(get_lang('Search'));

        $js = '<script>
        extra_field_toogle();
        function extra_field_toogle() {
            if (jQuery("select[name=search_type]").val() != "1") {
                jQuery(".extra_field").hide();
            } else {
                jQuery(".extra_field").show();
            }
        }
        </script>';

        return $js.$form->returnForm();
    }

    /**
     * @param int $userId
     *
     * @return array
     */
    public static function getDrhListFromUser($userId)
    {
        $tblUser = Database::get_main_table(TABLE_MAIN_USER);
        $tblUserRelUser = Database::get_main_table(TABLE_MAIN_USER_REL_USER);
        $tblUserRelAccessUrl = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        $userId = (int) $userId;

        $orderBy = null;
        if (api_is_western_name_order()) {
            $orderBy .= ' ORDER BY firstname, lastname ';
        } else {
            $orderBy .= ' ORDER BY lastname, firstname ';
        }

        $sql = "SELECT u.id, username, u.firstname, u.lastname
                FROM $tblUser u
                INNER JOIN $tblUserRelUser uru ON (uru.friend_user_id = u.id)
                INNER JOIN $tblUserRelAccessUrl a ON (a.user_id = u.id)
                WHERE
                    access_url_id = ".api_get_current_access_url_id()." AND
                    uru.user_id = '$userId' AND
                    relation_type = '".UserRelUser::USER_RELATION_TYPE_RRHH."'
                    $orderBy
                ";
        $result = Database::query($sql);

        return Database::store_result($result);
    }

    /**
     * get users followed by human resource manager.
     *
     * @param int    $userId
     * @param int    $userStatus         (STUDENT, COURSEMANAGER, etc)
     * @param bool   $getOnlyUserId
     * @param bool   $getSql
     * @param bool   $getCount
     * @param int    $from
     * @param int    $numberItems
     * @param int    $column
     * @param string $direction
     * @param int    $active
     * @param string $lastConnectionDate
     *
     * @return array users
     */
    public static function get_users_followed_by_drh(
        $userId,
        $userStatus = 0,
        $getOnlyUserId = false,
        $getSql = false,
        $getCount = false,
        $from = null,
        $numberItems = null,
        $column = null,
        $direction = null,
        $active = null,
        $lastConnectionDate = null
    ) {
        return self::getUsersFollowedByUser(
            $userId,
            $userStatus,
            $getOnlyUserId,
            $getSql,
            $getCount,
            $from,
            $numberItems,
            $column,
            $direction,
            $active,
            $lastConnectionDate,
            DRH
        );
    }

    /**
     * Get users followed by human resource manager.
     *
     * @param int    $userId
     * @param int    $userStatus             Filter users by status (STUDENT, COURSEMANAGER, etc)
     * @param bool   $getOnlyUserId
     * @param bool   $getSql
     * @param bool   $getCount
     * @param int    $from
     * @param int    $numberItems
     * @param int    $column
     * @param string $direction
     * @param int    $active
     * @param string $lastConnectionDate
     * @param int    $status                 the function is called by who? COURSEMANAGER, DRH?
     * @param string $keyword
     * @param bool   $checkSessionVisibility
     *
     * @return mixed Users list (array) or the SQL query if $getSQL was set to true
     */
    public static function getUsersFollowedByUser(
        $userId,
        $userStatus = null,
        $getOnlyUserId = false,
        $getSql = false,
        $getCount = false,
        $from = null,
        $numberItems = null,
        $column = null,
        $direction = null,
        $active = null,
        $lastConnectionDate = null,
        $status = null,
        $keyword = null,
        $checkSessionVisibility = false
    ) {
        // Database Table Definitions
        $tbl_user = Database::get_main_table(TABLE_MAIN_USER);
        $tbl_user_rel_user = Database::get_main_table(TABLE_MAIN_USER_REL_USER);
        $tbl_user_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        $tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
        $tbl_course_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $tbl_session_rel_course_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $tbl_session_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);
        $tbl_session_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_USER);

        $userId = (int) $userId;
        $limitCondition = '';

        if (isset($from) && isset($numberItems)) {
            $from = (int) $from;
            $numberItems = (int) $numberItems;
            $limitCondition = "LIMIT $from, $numberItems";
        }

        $column = Database::escape_string($column);
        $direction = in_array(strtolower($direction), ['asc', 'desc']) ? $direction : null;

        $userConditions = '';
        if (!empty($userStatus)) {
            $userConditions .= ' AND u.status = '.intval($userStatus);
        }

        $select = " SELECT DISTINCT u.id user_id, u.username, u.lastname, u.firstname, u.email ";
        if ($getOnlyUserId) {
            $select = " SELECT DISTINCT u.id user_id";
        }

        $masterSelect = "SELECT DISTINCT * FROM ";

        if ($getCount) {
            $masterSelect = "SELECT COUNT(DISTINCT(user_id)) as count FROM ";
            $select = " SELECT DISTINCT(u.id) user_id";
        }

        if (!is_null($active)) {
            $active = intval($active);
            $userConditions .= " AND u.active = $active ";
        }

        if (!empty($keyword)) {
            $keyword = trim(Database::escape_string($keyword));
            $keywordParts = array_filter(explode(' ', $keyword));
            $extraKeyword = '';
            if (!empty($keywordParts)) {
                $keywordPartsFixed = Database::escape_string(implode('%', $keywordParts));
                if (!empty($keywordPartsFixed)) {
                    $extraKeyword .= " OR
                        CONCAT(u.firstname, ' ', u.lastname) LIKE '%$keywordPartsFixed%' OR
                        CONCAT(u.lastname, ' ', u.firstname) LIKE '%$keywordPartsFixed%' ";
                }
            }
            $userConditions .= " AND (
                u.username LIKE '%$keyword%' OR
                u.firstname LIKE '%$keyword%' OR
                u.lastname LIKE '%$keyword%' OR
                u.official_code LIKE '%$keyword%' OR
                u.email LIKE '%$keyword%' OR
                CONCAT(u.firstname, ' ', u.lastname) LIKE '%$keyword%' OR
                CONCAT(u.lastname, ' ', u.firstname) LIKE '%$keyword%'
                $extraKeyword
            )";
        }

        if (!empty($lastConnectionDate)) {
            $lastConnectionDate = Database::escape_string($lastConnectionDate);
            $userConditions .= " AND u.last_login <= '$lastConnectionDate' ";
        }

        $sessionConditionsCoach = null;
        $dateCondition = '';
        $drhConditions = null;
        $teacherSelect = null;
        $urlId = api_get_current_access_url_id();

        switch ($status) {
            case DRH:
                $drhConditions .= " AND
                    friend_user_id = '$userId' AND
                    relation_type = '".UserRelUser::USER_RELATION_TYPE_RRHH."'
                ";
                break;
            case COURSEMANAGER:
                $drhConditions .= " AND
                    friend_user_id = '$userId' AND
                    relation_type = '".UserRelUser::USER_RELATION_TYPE_RRHH."'
                ";

                $sessionConditionsTeacher = " AND
                    (scu.status = ".SessionEntity::COURSE_COACH." AND scu.user_id = '$userId')
                ";

                if ($checkSessionVisibility) {
                    $today = api_strtotime('now', 'UTC');
                    $today = date('Y-m-d', $today);
                    $dateCondition = "
                        AND
                        (
                            (s.access_start_date <= '$today' AND '$today' <= s.access_end_date) OR
                            (s.access_start_date IS NULL AND s.access_end_date IS NULL) OR
                            (s.access_start_date <= '$today' AND s.access_end_date IS NULL) OR
                            ('$today' <= s.access_end_date AND s.access_start_date IS NULL)
                        )
					";
                }

                // Use $tbl_session_rel_course_rel_user instead of $tbl_session_rel_user
                /*
                INNER JOIN $tbl_session_rel_user sru
                ON (sru.user_id = u.id)
                INNER JOIN $tbl_session_rel_course_rel_user scu
                ON (scu.user_id = u.id AND scu.c_id IS NOT NULL AND visibility = 1)*/
                $teacherSelect =
                "UNION ALL (
                        $select
                        FROM $tbl_user u
                        INNER JOIN $tbl_session_rel_user sru ON (sru.user_id = u.id)
                        WHERE
                            (
                                sru.session_id IN (
                                    SELECT DISTINCT(s.id) FROM $tbl_session s INNER JOIN
                                    $tbl_session_rel_access_url session_rel_access_rel_user
                                    ON session_rel_access_rel_user.session_id = s.id
                                    INNER JOIN $tbl_session_rel_user sru ON s.id = sru.session_id
                                    WHERE access_url_id = ".$urlId."
                                        AND (sru.relation_type = ".SessionEntity::GENERAL_COACH."
                                        AND sru.user_id = $userId)
                                ) OR sru.session_id IN (
                                    SELECT DISTINCT(s.id) FROM $tbl_session s
                                    INNER JOIN $tbl_session_rel_access_url url
                                    ON (url.session_id = s.id)
                                    INNER JOIN $tbl_session_rel_course_rel_user scu
                                    ON (scu.session_id = s.id)
                                    WHERE access_url_id = ".$urlId."
                                    $sessionConditionsTeacher
                                    $dateCondition
                                )
                            )
                            $userConditions
                    )
                    UNION ALL(
                        $select
                        FROM $tbl_user u
                        INNER JOIN $tbl_course_user cu ON (cu.user_id = u.id)
                        WHERE cu.c_id IN (
                            SELECT DISTINCT(c_id) FROM $tbl_course_user
                            WHERE user_id = $userId AND status = ".COURSEMANAGER."
                        )
                        $userConditions
                    )"
                ;
                break;
            case STUDENT_BOSS:
                $drhConditions = " AND friend_user_id = $userId AND relation_type = ".UserRelUser::USER_RELATION_TYPE_BOSS;
                break;
            case HRM_REQUEST:
                $drhConditions .= " AND
                    friend_user_id = '$userId' AND
                    relation_type = '".UserRelUser::USER_RELATION_TYPE_HRM_REQUEST."'
                ";
                break;
        }

        $join = null;
        $sql = " $masterSelect
                (
                    (
                        $select
                        FROM $tbl_user u
                        INNER JOIN $tbl_user_rel_user uru ON (uru.user_id = u.id)
                        LEFT JOIN $tbl_user_rel_access_url a ON (a.user_id = u.id)
                        $join
                        WHERE
                            access_url_id = ".$urlId."
                            $drhConditions
                            $userConditions
                    )
                    $teacherSelect

                ) as t1";

        if ($getSql) {
            return $sql;
        }
        if ($getCount) {
            $result = Database::query($sql);
            $row = Database::fetch_array($result);

            return $row['count'];
        }

        $orderBy = null;
        if (false == $getOnlyUserId) {
            if (api_is_western_name_order()) {
                $orderBy .= " ORDER BY firstname, lastname ";
            } else {
                $orderBy .= " ORDER BY lastname, firstname ";
            }

            if (!empty($column) && !empty($direction)) {
                // Fixing order due the UNIONs
                $column = str_replace('u.', '', $column);
                $orderBy = " ORDER BY `$column` $direction ";
            }
        }

        $sql .= $orderBy;
        $sql .= $limitCondition;

        $result = Database::query($sql);
        $users = [];
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                $users[$row['user_id']] = $row;
            }
        }

        return $users;
    }

    /**
     * Subscribes users to human resource manager (Dashboard feature).
     *
     * @param int   $hr_dept_id
     * @param array $users_id
     * @param bool  $deleteOtherAssignedUsers
     */
    public static function subscribeUsersToHRManager(
        $hr_dept_id,
        $users_id,
        $deleteOtherAssignedUsers = true
    ): void {
        self::subscribeUsersToUser(
            $hr_dept_id,
            $users_id,
            UserRelUser::USER_RELATION_TYPE_RRHH,
            false,
            $deleteOtherAssignedUsers
        );
    }

    /**
     * Register request to assign users to HRM.
     *
     * @param int   $hrmId   The HRM ID
     * @param array $usersId The users IDs
     */
    public static function requestUsersToHRManager($hrmId, $usersId): void
    {
        self::subscribeUsersToUser(
            $hrmId,
            $usersId,
            UserRelUser::USER_RELATION_TYPE_HRM_REQUEST,
            false,
            false
        );
    }

    /**
     * Remove the requests for assign a user to a HRM.
     *
     * @param array $usersId List of user IDs from whom to remove all relations requests with HRM
     */
    public static function clearHrmRequestsForUser(User $hrmId, $usersId)
    {
        $users = implode(', ', $usersId);
        Database::getManager()
            ->createQuery('
                DELETE FROM ChamiloCoreBundle:UserRelUser uru
                WHERE uru.friendUserId = :hrm_id AND uru.relationType = :relation_type AND uru.userId IN (:users_ids)
            ')
            ->execute(['hrm_id' => $hrmId, 'relation_type' => UserRelUser::USER_RELATION_TYPE_HRM_REQUEST, 'users_ids' => $users]);
    }

    /**
     * Add subscribed users to a user by relation type.
     *
     * @param int   $userId                   The user id
     * @param array $subscribedUsersId        The id of subscribed users
     * @param int   $relationType             The relation type
     * @param bool  $deleteUsersBeforeInsert
     * @param bool  $deleteOtherAssignedUsers
     */
    public static function subscribeUsersToUser(
        $userId,
        $subscribedUsersId,
        $relationType,
        $deleteUsersBeforeInsert = false,
        $deleteOtherAssignedUsers = true
    ): void {
        $userRelUserTable = Database::get_main_table(TABLE_MAIN_USER_REL_USER);
        $userRelAccessUrlTable = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);

        $userId = (int) $userId;
        $relationType = (int) $relationType;

        if ($deleteOtherAssignedUsers) {
            if (api_get_multiple_access_url()) {
                // Deleting assigned users to hrm_id
                $sql = "SELECT s.user_id
                        FROM $userRelUserTable s
                        INNER JOIN $userRelAccessUrlTable a
                        ON (a.user_id = s.user_id)
                        WHERE
                            friend_user_id = $userId AND
                            relation_type = $relationType AND
                            access_url_id = ".api_get_current_access_url_id();
            } else {
                $sql = "SELECT user_id
                        FROM $userRelUserTable
                        WHERE
                            friend_user_id = $userId AND
                            relation_type = $relationType";
            }
            $result = Database::query($sql);

            if (Database::num_rows($result) > 0) {
                while ($row = Database::fetch_array($result)) {
                    $sql = "DELETE FROM $userRelUserTable
                            WHERE
                                user_id = {$row['user_id']} AND
                                friend_user_id = $userId AND
                                relation_type = $relationType";
                    Database::query($sql);
                }
            }
        }

        if ($deleteUsersBeforeInsert) {
            $sql = "DELETE FROM $userRelUserTable
                    WHERE
                        user_id = $userId AND
                        relation_type = $relationType";
            Database::query($sql);
        }

        // Inserting new user list.
        if (is_array($subscribedUsersId)) {
            foreach ($subscribedUsersId as $subscribedUserId) {
                $subscribedUserId = (int) $subscribedUserId;
                $sql = "SELECT id
                        FROM $userRelUserTable
                        WHERE
                            user_id = $subscribedUserId AND
                            friend_user_id = $userId AND
                            relation_type = $relationType";

                $result = Database::query($sql);
                $num = Database::num_rows($result);
                if (0 === $num) {
                    $userRelUser = (new UserRelUser())
                        ->setUser(api_get_user_entity($subscribedUserId))
                        ->setFriend(api_get_user_entity($userId))
                        ->setRelationType($relationType)
                    ;
                    $em = Database::getManager();
                    $em->persist($userRelUser);
                    $em->flush();
                }
            }
        }
    }

    /**
     * This function checks if a user is followed by provided human resources managers.
     *
     * @param int $user_id
     * @param int $hr_dept_id Human resources manager
     *
     * @return bool
     * @throws Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public static function is_user_followed_by_drh(int $user_id, int $hr_dept_id): bool
    {
        $tbl_user_rel_user = Database::get_main_table(TABLE_MAIN_USER_REL_USER);
        $result = false;

        $sql = "SELECT user_id FROM $tbl_user_rel_user
                WHERE
                    user_id = $user_id AND
                    friend_user_id = $hr_dept_id AND
                    relation_type = ".UserRelUser::USER_RELATION_TYPE_RRHH;
        $rs = Database::query($sql);
        if (Database::num_rows($rs) > 0) {
            $result = true;
        }

        return $result;
    }

    /**
     * Return the user id of teacher or session administrator.
     *
     * @param array $courseInfo
     *
     * @return int The user id, or 0 if the session ID was negative
     * @throws Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public static function get_user_id_of_course_admin_or_session_admin(array $courseInfo): int
    {
        $session = api_get_session_id();
        $table_user = Database::get_main_table(TABLE_MAIN_USER);
        $table_course_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $table_session_course_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

        if (empty($courseInfo)) {
            return 0;
        }

        $courseId = $courseInfo['real_id'];

        if (0 == $session) {
            $sql = 'SELECT u.id uid FROM '.$table_user.' u
                    INNER JOIN '.$table_course_user.' ru
                    ON ru.user_id = u.id
                    WHERE
                        ru.status = 1 AND
                        ru.c_id = "'.$courseId.'" ';
            $rs = Database::query($sql);
            $num_rows = Database::num_rows($rs);
            if (1 == $num_rows) {
                $row = Database::fetch_array($rs);

                return (int) $row['uid'];
            } else {
                $my_num_rows = $num_rows;

                return (int) Database::result($rs, $my_num_rows - 1, 'uid');
            }
        } elseif ($session > 0) {
            $sql = 'SELECT u.id as uid FROM '.$table_user.' u
                    INNER JOIN '.$table_session_course_user.' sru
                    ON sru.user_id = u.id
                    WHERE
                        sru.c_id = '.$courseId.' AND
                        sru.status = '.SessionEntity::COURSE_COACH;
            $rs = Database::query($sql);
            if (Database::num_rows($rs) > 0) {
                $row = Database::fetch_assoc($rs);

                return (int) $row['uid'];
            }
        }

        return 0;
    }

    /**
     * Determines if a user is a gradebook certified.
     *
     * @param int $cat_id  The category id of gradebook
     * @param int $user_id The user id
     *
     * @return bool
     */
    public static function is_user_certified($cat_id, $user_id)
    {
        $cat_id = (int) $cat_id;
        $user_id = (int) $user_id;

        $table = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CERTIFICATE);
        $sql = 'SELECT path_certificate
                FROM '.$table.'
                WHERE
                    cat_id = "'.$cat_id.'" AND
                    user_id = "'.$user_id.'"';
        $rs = Database::query($sql);
        $row = Database::fetch_array($rs);

        if (!isset($row['path_certificate']) || '' == $row['path_certificate'] || is_null($row['path_certificate'])) {
            return false;
        }

        return true;
    }

    /**
     * Gets the info about a gradebook certificate for a user by course.
     *
     * @param array $course_info The course code
     * @param int   $session_id
     * @param int   $user_id     The user id
     *
     * @return array if there is not information return false
     */
    public static function get_info_gradebook_certificate($course_info, $session_id, $user_id)
    {
        $tbl_grade_certificate = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CERTIFICATE);
        $tbl_grade_category = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);
        $session_id = (int) $session_id;
        $user_id = (int) $user_id;
        $courseId = $course_info['real_id'];

        if (empty($session_id)) {
            $session_condition = ' AND (session_id = "" OR session_id = 0 OR session_id IS NULL )';
        } else {
            $session_condition = " AND session_id = $session_id";
        }

        $sql = 'SELECT * FROM '.$tbl_grade_certificate.'
                WHERE cat_id = (
                    SELECT id FROM '.$tbl_grade_category.'
                    WHERE
                        c_id = "'.$courseId.'" '.$session_condition.'
                    LIMIT 1
                ) AND user_id='.$user_id;

        $rs = Database::query($sql);
        if (Database::num_rows($rs) > 0) {
            $row = Database::fetch_assoc($rs);
            $score = $row['score_certificate'];
            $category_id = $row['cat_id'];
            $cat = Category::load($category_id);
            $displayscore = ScoreDisplay::instance();
            if (isset($cat) && $displayscore->is_custom()) {
                $grade = $displayscore->display_score(
                    [$score, $cat[0]->get_weight()],
                    SCORE_DIV_PERCENT_WITH_CUSTOM
                );
            } else {
                $grade = $displayscore->display_score(
                    [$score, $cat[0]->get_weight()]
                );
            }
            $row['grade'] = $grade;

            return $row;
        }

        return false;
    }

    /**
     * This function check if the user is a coach inside session course.
     *
     * @param int $user_id    User id
     * @param int $courseId
     * @param int $session_id
     *
     * @return bool True if the user is a coach
     */
    public static function is_session_course_coach($user_id, $courseId, $session_id)
    {
        $table = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        // Protect data
        $user_id = intval($user_id);
        $courseId = intval($courseId);
        $session_id = intval($session_id);
        $result = false;

        $sql = "SELECT session_id FROM $table
                WHERE
                  session_id = $session_id AND
                  c_id = $courseId AND
                  user_id = $user_id AND
                  status = ".SessionEntity::COURSE_COACH;
        $res = Database::query($sql);

        if (Database::num_rows($res) > 0) {
            $result = true;
        }

        return $result;
    }

    /**
     * This function returns an icon path that represents the favicon of the website of which the url given.
     * Defaults to the current Chamilo favicon.
     *
     * @param string $url1 URL of website where to look for favicon.ico
     * @param string $url2 Optional second URL of website where to look for favicon.ico
     *
     * @return string Path of icon to load
     */
    public static function get_favicon_from_url($url1, $url2 = null)
    {
        $icon_link = '';
        $url = $url1;
        if (empty($url1)) {
            $url = $url2;
            if (empty($url)) {
                $url = api_get_access_url(api_get_current_access_url_id());
                $url = $url[0];
            }
        }
        if (!empty($url)) {
            $pieces = parse_url($url);
            $icon_link = $pieces['scheme'].'://'.$pieces['host'].'/favicon.ico';
        }

        return $icon_link;
    }

    public static function addUserAsAdmin(User $user)
    {
        $userId = $user->getId();

        if (!self::is_admin($userId)) {
            $table = Database::get_main_table(TABLE_MAIN_ADMIN);
            $sql = "INSERT INTO $table SET user_id = $userId";
            Database::query($sql);
        }

        $user->addRole('ROLE_ADMIN');
        Container::getUserRepository()->updateUser($user, true);
    }

    public static function removeUserAdmin(User $user)
    {
        $userId = (int) $user->getId();
        if (self::is_admin($userId)) {
            $table = Database::get_main_table(TABLE_MAIN_ADMIN);
            $sql = "DELETE FROM $table WHERE user_id = $userId";
            Database::query($sql);
            $user->removeRole('ROLE_ADMIN');
            Container::getUserRepository()->updateUser($user, true);
        }
    }

    /**
     * @param string $from
     * @param string $to
     */
    public static function update_all_user_languages($from, $to)
    {
        $table_user = Database::get_main_table(TABLE_MAIN_USER);
        $from = Database::escape_string($from);
        $to = Database::escape_string($to);

        if (!empty($to) && !empty($from)) {
            $sql = "UPDATE $table_user SET language = '$to'
                    WHERE language = '$from'";
            Database::query($sql);
        }
    }

    /**
     * Subscribe boss to students.
     *
     * @param int   $bossId                   The boss id
     * @param array $usersId                  The users array
     * @param bool  $deleteOtherAssignedUsers
     */
    public static function subscribeBossToUsers($bossId, $usersId, $deleteOtherAssignedUsers = true): void
    {
        self::subscribeUsersToUser(
            $bossId,
            $usersId,
            UserRelUser::USER_RELATION_TYPE_BOSS,
            false,
            $deleteOtherAssignedUsers
        );
    }

    /**
     * @param int $userId
     *
     * @return bool
     */
    public static function removeAllBossFromStudent($userId)
    {
        $userId = (int) $userId;

        if (empty($userId)) {
            return false;
        }

        $userRelUserTable = Database::get_main_table(TABLE_MAIN_USER_REL_USER);
        $sql = "DELETE FROM $userRelUserTable
                WHERE user_id = $userId AND relation_type = ".UserRelUser::USER_RELATION_TYPE_BOSS;
        Database::query($sql);

        return true;
    }

    /**
     * Subscribe boss to students, if $bossList is empty then the boss list will be empty too.
     *
     * @param int   $studentId
     * @param array $bossList
     * @param bool  $sendNotification
     *
     * @return mixed Affected rows or false on failure
     */
    public static function subscribeUserToBossList(
        $studentId,
        $bossList,
        $sendNotification = false
    ) {
        $inserted = 0;
        if (!empty($bossList)) {
            sort($bossList);
            $studentId = (int) $studentId;
            $studentInfo = api_get_user_info($studentId);

            if (empty($studentInfo)) {
                return false;
            }

            $previousBossList = self::getStudentBossList($studentId);
            $previousBossList = !empty($previousBossList) ? array_column($previousBossList, 'boss_id') : [];
            sort($previousBossList);

            // Boss list is the same, nothing changed.
            if ($bossList == $previousBossList) {
                return false;
            }

            $userRelUserTable = Database::get_main_table(TABLE_MAIN_USER_REL_USER);
            self::removeAllBossFromStudent($studentId);

            foreach ($bossList as $bossId) {
                $bossId = (int) $bossId;
                $bossInfo = api_get_user_info($bossId);

                if (empty($bossInfo)) {
                    continue;
                }

                $bossLanguage = $bossInfo['locale'];
                $sql = "INSERT IGNORE INTO $userRelUserTable (user_id, friend_user_id, relation_type)
                        VALUES ($studentId, $bossId, ".UserRelUser::USER_RELATION_TYPE_BOSS.")";
                $insertId = Database::query($sql);

                if ($insertId) {
                    if ($sendNotification) {
                        $name = $studentInfo['complete_name'];
                        $url = api_get_path(WEB_CODE_PATH).'my_space/myStudents.php?student='.$studentId;
                        $url = Display::url($url, $url);
                        $subject = sprintf(get_lang('You have been assigned the learner %s', $bossLanguage), $name);
                        $message = sprintf(get_lang('You have been assigned the learner %s with url %s', $bossLanguage), $name, $url);
                        MessageManager::send_message_simple(
                            $bossId,
                            $subject,
                            $message
                        );
                    }
                    $inserted++;
                }
            }
        } else {
            self::removeAllBossFromStudent($studentId);
        }

        return $inserted;
    }

    /**
     * Get users followed by student boss.
     *
     * @param int    $userId
     * @param int    $userStatus         (STUDENT, COURSEMANAGER, etc)
     * @param bool   $getOnlyUserId
     * @param bool   $getSql
     * @param bool   $getCount
     * @param int    $from
     * @param int    $numberItems
     * @param int    $column
     * @param string $direction
     * @param int    $active
     * @param string $lastConnectionDate
     *
     * @return array users
     */
    public static function getUsersFollowedByStudentBoss(
        $userId,
        $userStatus = 0,
        $getOnlyUserId = false,
        $getSql = false,
        $getCount = false,
        $from = null,
        $numberItems = null,
        $column = null,
        $direction = null,
        $active = null,
        $lastConnectionDate = null
    ) {
        return self::getUsersFollowedByUser(
            $userId,
            $userStatus,
            $getOnlyUserId,
            $getSql,
            $getCount,
            $from,
            $numberItems,
            $column,
            $direction,
            $active,
            $lastConnectionDate,
            STUDENT_BOSS
        );
    }

    /**
     * @return array
     */
    public static function getOfficialCodeGrouped()
    {
        $user = Database::get_main_table(TABLE_MAIN_USER);
        $sql = "SELECT DISTINCT official_code
                FROM $user
                GROUP BY official_code";
        $result = Database::query($sql);
        $values = Database::store_result($result, 'ASSOC');
        $result = [];
        foreach ($values as $value) {
            $result[$value['official_code']] = $value['official_code'];
        }

        return $result;
    }

    /**
     * @param string $officialCode
     *
     * @return array
     */
    public static function getUsersByOfficialCode($officialCode)
    {
        $user = Database::get_main_table(TABLE_MAIN_USER);
        $officialCode = Database::escape_string($officialCode);

        $sql = "SELECT DISTINCT id
                FROM $user
                WHERE official_code = '$officialCode'
                ";
        $result = Database::query($sql);

        $users = [];
        while ($row = Database::fetch_array($result)) {
            $users[] = $row['id'];
        }

        return $users;
    }

    /**
     * Calc the expended time (in seconds) by a user in a course.
     *
     * @param int    $userId    The user id
     * @param int    $courseId  The course id
     * @param int    $sessionId Optional. The session id
     * @param string $from      Optional. From date
     * @param string $until     Optional. Until date
     *
     * @return int The time
     */
    public static function getTimeSpentInCourses(
        $userId,
        $courseId,
        $sessionId = 0,
        $from = '',
        $until = ''
    ) {
        $userId = (int) $userId;
        $sessionId = (int) $sessionId;

        $trackCourseAccessTable = Database::get_main_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
        $whereConditions = [
            'user_id = ? ' => $userId,
            'AND c_id = ? ' => $courseId,
            'AND session_id = ? ' => $sessionId,
        ];

        if (!empty($from) && !empty($until)) {
            $whereConditions["AND (login_course_date >= '?' "] = $from;
            $whereConditions["AND logout_course_date <= DATE_ADD('?', INTERVAL 1 DAY)) "] = $until;
        }

        $trackResult = Database::select(
            'SUM(UNIX_TIMESTAMP(logout_course_date) - UNIX_TIMESTAMP(login_course_date)) as total_time',
            $trackCourseAccessTable,
            [
                'where' => $whereConditions,
            ],
            'first'
        );

        if (false != $trackResult) {
            return $trackResult['total_time'] ? $trackResult['total_time'] : 0;
        }

        return 0;
    }

    /**
     * Get the boss user ID from a followed user id.
     *
     * @param $userId
     *
     * @return bool
     */
    public static function getFirstStudentBoss($userId)
    {
        $userId = (int) $userId;
        if ($userId > 0) {
            $userRelTable = Database::get_main_table(TABLE_MAIN_USER_REL_USER);
            $row = Database::select(
                'DISTINCT friend_user_id AS boss_id',
                $userRelTable,
                [
                    'where' => [
                        'user_id = ? AND relation_type = ? LIMIT 1' => [
                            $userId,
                            UserRelUser::USER_RELATION_TYPE_BOSS,
                        ],
                    ],
                ]
            );
            if (!empty($row)) {
                return $row[0]['boss_id'];
            }
        }

        return false;
    }

    /**
     * Get the boss user ID from a followed user id.
     *
     * @param int $userId student id
     *
     * @return array
     */
    public static function getStudentBossList($userId)
    {
        $userId = (int) $userId;

        if ($userId > 0) {
            $userRelTable = Database::get_main_table(TABLE_MAIN_USER_REL_USER);

            return Database::select(
                'DISTINCT friend_user_id AS boss_id',
                $userRelTable,
                [
                    'where' => [
                        'user_id = ? AND relation_type = ? ' => [
                            $userId,
                            UserRelUser::USER_RELATION_TYPE_BOSS,
                        ],
                    ],
                ]
            );
        }

        return [];
    }

    /**
     * @param int $bossId
     * @param int $studentId
     *
     * @return bool
     */
    public static function userIsBossOfStudent($bossId, $studentId)
    {
        $result = false;
        $bossList = self::getStudentBossList($studentId);
        if (!empty($bossList)) {
            $bossList = array_column($bossList, 'boss_id');
            if (in_array($bossId, $bossList)) {
                $result = true;
            }
        }

        return $result;
    }

    /**
     * Displays the name of the user and makes the link to the user profile.
     *
     * @param array $userInfo
     *
     * @return string
     */
    public static function getUserProfileLink($userInfo)
    {
        if (isset($userInfo) && isset($userInfo['user_id'])) {
            return Display::url(
                $userInfo['complete_name_with_username'],
                $userInfo['profile_url']
            );
        }

        return get_lang('Anonymous');
    }

    /**
     * Get users whose name matches $firstname and $lastname.
     *
     * @param string $firstname Firstname to search
     * @param string $lastname  Lastname to search
     *
     * @return array The user list
     */
    public static function getUsersByName($firstname, $lastname)
    {
        $firstname = Database::escape_string($firstname);
        $lastname = Database::escape_string($lastname);
        $userTable = Database::get_main_table(TABLE_MAIN_USER);

        $sql = <<<SQL
            SELECT id, username, lastname, firstname
            FROM $userTable
            WHERE
                firstname LIKE '$firstname%' AND
                lastname LIKE '$lastname%'
SQL;
        $result = Database::query($sql);
        $users = [];
        while ($resultData = Database::fetch_object($result)) {
            $users[] = $resultData;
        }

        return $users;
    }

    /**
     * @param int $optionSelected
     *
     * @return string
     */
    public static function getUserSubscriptionTab($optionSelected = 1)
    {
        $allowAdmin = api_get_setting('workflows.allow_user_course_subscription_by_course_admin');
        if (('true' === $allowAdmin && api_is_allowed_to_edit()) ||
            api_is_platform_admin()
        ) {
            $userPath = api_get_path(WEB_CODE_PATH).'user/';

            $headers = [
                [
                    'url' => $userPath.'user.php?'.api_get_cidreq().'&type='.STUDENT,
                    'content' => get_lang('Learners'),
                ],
                [
                    'url' => $userPath.'user.php?'.api_get_cidreq().'&type='.COURSEMANAGER,
                    'content' => get_lang('Trainers'),
                ],
                /*[
                    'url' => $userPath.'subscribe_user.php?'.api_get_cidreq(),
                    'content' => get_lang('Learners'),
                ],
                [
                    'url' => $userPath.'subscribe_user.php?type=teacher&'.api_get_cidreq(),
                    'content' => get_lang('Trainers'),
                ],*/
                [
                    'url' => api_get_path(WEB_CODE_PATH).'group/group.php?'.api_get_cidreq(),
                    'content' => get_lang('Groups'),
                ],
                [
                    'url' => $userPath.'class.php?'.api_get_cidreq(),
                    'content' => get_lang('Classes'),
                ],
            ];

            return Display::tabsOnlyLink($headers, $optionSelected);
        }

        return '';
    }

    /**
     * Make sure this function is protected because it does NOT check password!
     *
     * This function defines globals.
     *
     * @param int  $userId
     * @param bool $checkIfUserCanLoginAs
     *
     * @return bool
     *
     * @author Evie Embrechts
     * @author Yannick Warnier <yannick.warnier@dokeos.com>
     */
    public static function loginAsUser($userId, $checkIfUserCanLoginAs = true)
    {
        $userId = (int) $userId;
        $userInfo = api_get_user_info($userId);

        // Check if the user is allowed to 'login_as'
        $canLoginAs = true;
        if ($checkIfUserCanLoginAs) {
            $canLoginAs = api_can_login_as($userId);
        }

        if (!$canLoginAs || empty($userInfo)) {
            return false;
        }

        if ($userId) {
            Event::registerLog([
                'tool' => 'logout',
                'tool_id' => 0,
                'tool_id_detail' => 0,
                'action' => '',
                'info' => 'Change user (login as)',
            ]);

            // Logout current user
            self::loginDelete(api_get_user_id());

            return true;
        }

        return false;
    }

    /**
     * Remove all login records from the track_e_online stats table,
     * for the given user ID.
     *
     * @param int $userId User ID
     */
    public static function loginDelete($userId)
    {
        $online_table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ONLINE);
        $userId = (int) $userId;
        $query = "DELETE FROM $online_table WHERE login_user_id = $userId";
        Database::query($query);
    }

    /**
     * Login as first admin user registered in the platform.
     *
     * @return array
     */
    public static function logInAsFirstAdmin()
    {
        $adminList = self::get_all_administrators();

        if (!empty($adminList)) {
            $userInfo = current($adminList);
            if (!empty($userInfo)) {
                $result = self::loginAsUser($userInfo['user_id'], false);
                if ($result && api_is_platform_admin()) {
                    return api_get_user_info();
                }
            }
        }

        return [];
    }

    /**
     * Check if user is teacher of a student based in their courses.
     *
     * @param $teacherId
     * @param $studentId
     *
     * @return array
     */
    public static function getCommonCoursesBetweenTeacherAndStudent($teacherId, $studentId)
    {
        $courses = CourseManager::getCoursesFollowedByUser(
            $teacherId,
            COURSEMANAGER
        );
        if (empty($courses)) {
            return false;
        }

        $coursesFromUser = CourseManager::get_courses_list_by_user_id($studentId);
        if (empty($coursesFromUser)) {
            return false;
        }

        $coursesCodeList = array_column($courses, 'code');
        $coursesCodeFromUserList = array_column($coursesFromUser, 'code');
        $commonCourses = array_intersect($coursesCodeList, $coursesCodeFromUserList);
        $commonCourses = array_filter($commonCourses);

        if (!empty($commonCourses)) {
            return $commonCourses;
        }

        return [];
    }

    /**
     * @param int $teacherId
     * @param int $studentId
     *
     * @return bool
     */
    public static function isTeacherOfStudent($teacherId, $studentId)
    {
        $courses = self::getCommonCoursesBetweenTeacherAndStudent(
            $teacherId,
            $studentId
        );

        if (!empty($courses)) {
            return true;
        }

        return false;
    }

    /**
     * Send user confirmation mail.
     *
     * @throws Exception
     */
    public static function sendUserConfirmationMail(User $user)
    {
        $uniqueId = api_get_unique_id();
        $user->setConfirmationToken($uniqueId);

        Database::getManager()->persist($user);
        Database::getManager()->flush();

        $url = api_get_path(WEB_CODE_PATH).'auth/user_mail_confirmation.php?token='.$uniqueId;

        // Check if the user was originally set for an automated subscription to a course or session
        $courseCodeToRedirect = Session::read('course_redirect');
        $sessionToRedirect = Session::read('session_redirect');
        if (!empty($courseCodeToRedirect)) {
            $url .= '&c='.$courseCodeToRedirect;
        }
        if (!empty($sessionToRedirect)) {
            $url .= '&s='.$sessionToRedirect;
        }
        $mailSubject = get_lang('Registration confirmation');
        $mailBody = get_lang('To complete your platform registration you need to confirm your account by clicking the following link')
            .PHP_EOL
            .Display::url($url, $url);

        api_mail_html(
            self::formatUserFullName($user),
            $user->getEmail(),
            $mailSubject,
            $mailBody
        );
        Display::addFlash(Display::return_message(get_lang('Check your e-mail and follow the instructions.')));
    }

    /**
     * Anonymize a user. Replace personal info by anonymous info.
     *
     * @param int  $userId   User id
     * @param bool $deleteIP Whether to replace the IP address in logs tables by 127.0.0.1 or to leave as is
     *
     * @throws \Exception
     *
     * @return bool
     * @assert (0) === false
     */
    public static function anonymize($userId, $deleteIP = true)
    {
        global $debug;

        $userId = (int) $userId;

        if (empty($userId)) {
            return false;
        }

        $em = Database::getManager();
        $user = api_get_user_entity($userId);
        $uniqueId = uniqid('anon', true);
        $user
            ->setFirstname($uniqueId)
            ->setLastname($uniqueId)
            ->setBiography('')
            ->setAddress('')
            //->setCurriculumItems(null)
            ->setDateOfBirth(null)
            ->setCompetences('')
            ->setDiplomas('')
            ->setOpenarea('')
            ->setTeach('')
            ->setProductions(null)
            ->setOpenid('')
            ->setEmailCanonical($uniqueId.'@example.com')
            ->setEmail($uniqueId.'@example.com')
            ->setUsername($uniqueId)
            ->setUsernameCanonical($uniqueId)
            ->setPhone('')
            ->setOfficialCode('')
        ;

        self::deleteUserPicture($userId);
        self::cleanUserRequestsOfRemoval($userId);

        // The IP address is a border-case personal data, as it does
        // not directly allow for personal identification (it is not
        // a completely safe value in most countries - the IP could
        // be used by neighbours and crackers)
        if ($deleteIP) {
            $substitute = '127.0.0.1';
            $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ACCESS);
            $sql = "UPDATE $table set user_ip = '$substitute' WHERE access_user_id = $userId";
            $res = Database::query($sql);
            if (false === $res && $debug > 0) {
                error_log("Could not anonymize IP address for user $userId ($sql)");
            }

            $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
            $sql = "UPDATE $table set user_ip = '$substitute' WHERE user_id = $userId";
            $res = Database::query($sql);
            if (false === $res && $debug > 0) {
                error_log("Could not anonymize IP address for user $userId ($sql)");
            }

            $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
            $sql = "UPDATE $table SET user_ip = '$substitute' WHERE exe_user_id = $userId";
            $res = Database::query($sql);
            if (false === $res && $debug > 0) {
                error_log("Could not anonymize IP address for user $userId ($sql)");
            }

            $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_LOGIN);
            $sql = "UPDATE $table SET user_ip = '$substitute' WHERE login_user_id = $userId";
            $res = Database::query($sql);
            if (false === $res && $debug > 0) {
                error_log("Could not anonymize IP address for user $userId ($sql)");
            }

            $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ONLINE);
            $sql = "UPDATE $table set user_ip = '$substitute' WHERE login_user_id = $userId";
            $res = Database::query($sql);
            if (false === $res && $debug > 0) {
                error_log("Could not anonymize IP address for user $userId ($sql)");
            }

            $table = Database::get_course_table(TABLE_WIKI);
            $sql = "UPDATE $table set user_ip = '$substitute' WHERE user_id = $userId";
            $res = Database::query($sql);
            if (false === $res && $debug > 0) {
                error_log("Could not anonymize IP address for user $userId ($sql)");
            }

            $table = Database::get_main_table(TABLE_TICKET_MESSAGE);
            $sql = "UPDATE $table set ip_address = '$substitute' WHERE sys_insert_user_id = $userId";
            $res = Database::query($sql);
            if (false === $res && $debug > 0) {
                error_log("Could not anonymize IP address for user $userId ($sql)");
            }

            $table = Database::get_course_table(TABLE_WIKI);
            $sql = "UPDATE $table set user_ip = '$substitute' WHERE user_id = $userId";
            $res = Database::query($sql);
            if (false === $res && $debug > 0) {
                error_log("Could not anonymize IP address for user $userId ($sql)");
            }
        }

        $extraFieldRepository = $em->getRepository(EntityExtraField::class);
        $autoRemoveFields = $extraFieldRepository->findBy([
            'autoRemove' => 1,
            'itemType' => EntityExtraField::USER_FIELD_TYPE
        ]);

        foreach ($autoRemoveFields as $field) {
            $extraFieldValueRepository = $em->getRepository(EntityExtraFieldValues::class);
            $extraFieldValue = $extraFieldValueRepository->findOneBy([
                'field' => $field,
                'itemId' => $userId
            ]);

            if ($extraFieldValue) {
                $em->remove($extraFieldValue);
            }
        }

        $em->persist($user);
        $em->flush();
        Event::addEvent(LOG_USER_ANONYMIZE, LOG_USER_ID, $userId);

        return true;
    }

    /**
     * @param int $userId
     *
     * @throws Exception
     *
     * @return string
     */
    public static function anonymizeUserWithVerification($userId)
    {
        $allowDelete = ('true' === api_get_setting('session.allow_delete_user_for_session_admin'));

        $message = '';
        if (api_is_platform_admin() ||
            ($allowDelete && api_is_session_admin())
        ) {
            $userToUpdateInfo = api_get_user_info($userId);
            $currentUserId = api_get_user_id();

            if ($userToUpdateInfo &&
                api_global_admin_can_edit_admin($userId, null, $allowDelete)
            ) {
                if ($userId != $currentUserId &&
                    self::anonymize($userId)
                ) {
                    $message = Display::return_message(
                        sprintf(get_lang("User %s's information anonymized."), $userToUpdateInfo['complete_name_with_username']),
                        'confirmation'
                    );
                } else {
                    $message = Display::return_message(
                        sprintf(get_lang("We could not anonymize user %s's information. Please try again or check the logs."), $userToUpdateInfo['complete_name_with_username']),
                        'error'
                    );
                }
            } else {
                $message = Display::return_message(
                    sprintf(get_lang('You don\'t have permissions to anonymize user %s. You need the same permissions as to delete users.'), $userToUpdateInfo['complete_name_with_username']),
                    'error'
                );
            }
        }

        return $message;
    }

    public static function deleteUserWithVerification(int $userId, bool $destroy = false): string
    {
        $allowDelete = ('true' === api_get_setting('session.allow_delete_user_for_session_admin'));
        $message = Display::return_message(get_lang('You cannot delete this user'), 'error');
        $userToUpdateInfo = api_get_user_info($userId);

        // User must exist.
        if (empty($userToUpdateInfo)) {
            return $message;
        }

        $currentUserId = api_get_user_id();

        // Cannot delete myself.
        if ($userId == $currentUserId) {
            return $message;
        }

        if (api_is_platform_admin() ||
            ($allowDelete && api_is_session_admin())
        ) {
            if (api_global_admin_can_edit_admin($userId, null, $allowDelete)) {
                if (self::delete_user($userId, $destroy)) {
                    $message = Display::return_message(
                        get_lang('The user has been deleted').': '.$userToUpdateInfo['complete_name_with_username'],
                        'confirmation'
                    );
                } else {
                    $message = Display::return_message(get_lang('This user cannot be deleted because he is still teacher in a course. You can either remove his teacher status from these courses and then delete his account, or disable his account instead of deleting it.'), 'error');
                }
            }
        }

        return $message;
    }

    /**
     * @return array
     */
    public static function createDataPrivacyExtraFields()
    {
        self::create_extra_field(
            'request_for_legal_agreement_consent_removal_justification',
            1, //text
            'Request for legal agreement consent removal justification	',
            ''
        );

        self::create_extra_field(
            'request_for_delete_account_justification',
            1, //text
            'Request for delete account justification',
            ''
        );

        $extraFieldId = self::create_extra_field(
            'request_for_legal_agreement_consent_removal',
            1, //text
            'Request for legal agreement consent removal',
            ''
        );

        $extraFieldIdDeleteAccount = self::create_extra_field(
            'request_for_delete_account',
            1, //text
            'Request for delete user account',
            ''
        );

        return [
            'delete_account_extra_field' => $extraFieldIdDeleteAccount,
            'delete_legal' => $extraFieldId,
        ];
    }

    /**
     * @param int $userId
     */
    public static function cleanUserRequestsOfRemoval($userId)
    {
        $userId = (int) $userId;

        $extraFieldValue = new ExtraFieldValue('user');
        $extraFieldsToDelete = [
            'legal_accept',
            'request_for_legal_agreement_consent_removal',
            'request_for_legal_agreement_consent_removal_justification',
            'request_for_delete_account_justification', // just in case delete also this
            'request_for_delete_account',
        ];

        foreach ($extraFieldsToDelete as $variable) {
            $value = $extraFieldValue->get_values_by_handler_and_field_variable(
                $userId,
                $variable
            );
            if ($value && isset($value['id'])) {
                $extraFieldValue->delete($value['id']);
            }
        }
    }

    /**
     * @param int $searchYear
     *
     * @throws Exception
     *
     * @return array
     */
    public static function getSubscribedSessionsByYear(array $userInfo, $searchYear)
    {
        $timezone = new DateTimeZone(api_get_timezone());

        $sessions = [];
        if (DRH == $userInfo['status']) {
            $sessions = SessionManager::get_sessions_followed_by_drh($userInfo['id']);
        } elseif (api_is_platform_admin(true)) {
            $sessions = SessionManager::getSessionsForAdmin($userInfo['id']);
        } else {
            $sessionsByCategory = self::get_sessions_by_category($userInfo['id'], false, true, true);
            $sessionsByCategory = array_column($sessionsByCategory, 'sessions');

            foreach ($sessionsByCategory as $sessionsInCategory) {
                $sessions = array_merge($sessions, $sessionsInCategory);
            }
        }

        $sessions = array_map(
            function ($sessionInfo) {
                if (!isset($sessionInfo['session_id'])) {
                    $sessionInfo['session_id'] = $sessionInfo['id'];
                }
                if (!isset($sessionInfo['session_name'])) {
                    $sessionInfo['session_name'] = $sessionInfo['name'];
                }

                return $sessionInfo;
            },
            $sessions
        );

        $calendarSessions = [];

        foreach ($sessions as $sessionInfo) {
            if (!empty($sessionInfo['duration'])) {
                $courseAccess = CourseManager::getFirstCourseAccessPerSessionAndUser(
                    $sessionInfo['session_id'],
                    $userInfo['id']
                );

                if (empty($courseAccess)) {
                    continue;
                }

                $firstAcessDate = new DateTime(api_get_local_time($courseAccess['login_course_date']), $timezone);
                $lastAccessDate = clone $firstAcessDate;
                $lastAccessDate->modify("+{$sessionInfo['duration']} days");

                $firstAccessYear = (int) $firstAcessDate->format('Y');
                $lastAccessYear = (int) $lastAccessDate->format('Y');

                if ($firstAccessYear <= $searchYear && $lastAccessYear >= $searchYear) {
                    $calendarSessions[$sessionInfo['session_id']] = [
                        'name' => $sessionInfo['session_name'],
                        'access_start_date' => $firstAcessDate->format('Y-m-d h:i:s'),
                        'access_end_date' => $lastAccessDate->format('Y-m-d h:i:s'),
                    ];
                }

                continue;
            }

            $accessStartDate = !empty($sessionInfo['access_start_date'])
                ? new DateTime(api_get_local_time($sessionInfo['access_start_date']), $timezone)
                : null;
            $accessEndDate = !empty($sessionInfo['access_end_date'])
                ? new DateTime(api_get_local_time($sessionInfo['access_end_date']), $timezone)
                : null;
            $accessStartYear = $accessStartDate ? (int) $accessStartDate->format('Y') : 0;
            $accessEndYear = $accessEndDate ? (int) $accessEndDate->format('Y') : 0;

            $isValid = false;

            if ($accessStartYear && $accessEndYear) {
                if ($accessStartYear <= $searchYear && $accessEndYear >= $searchYear) {
                    $isValid = true;
                }
            }

            if ($accessStartYear && !$accessEndYear) {
                if ($accessStartYear == $searchYear) {
                    $isValid = true;
                }
            }

            if (!$accessStartYear && $accessEndYear) {
                if ($accessEndYear == $searchYear) {
                    $isValid = true;
                }
            }

            if ($isValid) {
                $calendarSessions[$sessionInfo['session_id']] = [
                    'name' => $sessionInfo['session_name'],
                    'access_start_date' => $accessStartDate ? $accessStartDate->format('Y-m-d h:i:s') : null,
                    'access_end_date' => $accessEndDate ? $accessEndDate->format('Y-m-d h:i:s') : null,
                ];
            }
        }

        return $calendarSessions;
    }

    /**
     * Get sessions info for planification calendar.
     *
     * @param array $sessionsList Session list from UserManager::getSubscribedSessionsByYear
     * @param int   $searchYear
     *
     * @throws Exception
     *
     * @return array
     */
    public static function getSessionsCalendarByYear(array $sessionsList, $searchYear)
    {
        $timezone = new DateTimeZone(api_get_timezone());
        $calendar = [];

        foreach ($sessionsList as $sessionId => $sessionInfo) {
            $startDate = $sessionInfo['access_start_date']
                ? new DateTime(api_get_local_time($sessionInfo['access_start_date']), $timezone)
                : null;
            $endDate = $sessionInfo['access_end_date']
                ? new DateTime(api_get_local_time($sessionInfo['access_end_date']), $timezone)
                : null;

            $startYear = $startDate ? (int) $startDate->format('Y') : 0;
            $startWeekYear = $startDate ? (int) $startDate->format('o') : 0;
            $startWeek = $startDate ? (int) $startDate->format('W') : 0;
            $endYear = $endDate ? (int) $endDate->format('Y') : 0;
            $endWeekYear = $endDate ? (int) $endDate->format('o') : 0;
            $endWeek = $endDate ? (int) $endDate->format('W') : 0;

            $start = $startWeekYear < $searchYear ? 0 : $startWeek - 1;
            $duration = $endWeekYear > $searchYear ? 52 - $start : $endWeek - $start;

            $calendar[] = [
                'id' => $sessionId,
                'name' => $sessionInfo['name'],
                'human_date' => SessionManager::convertSessionDateToString($startDate, $endDate, false, true),
                'start_in_last_year' => $startYear < $searchYear,
                'end_in_next_year' => $endYear > $searchYear,
                'no_start' => !$startWeek,
                'no_end' => !$endWeek,
                'start' => $start,
                'duration' => $duration > 0 ? $duration : 1,
            ];
        }

        usort(
            $calendar,
            function ($sA, $sB) {
                if ($sA['start'] == $sB['start']) {
                    return 0;
                }

                if ($sA['start'] < $sB['start']) {
                    return -1;
                }

                return 1;
            }
        );

        return $calendar;
    }

    /**
     * Return the user's full name. Optionally with the username.
     */
    public static function formatUserFullName(User $user, bool $includeUsername = false): string
    {
        $fullName = api_get_person_name($user->getFirstname(), $user->getLastname());

        if ($includeUsername && 'false' === api_get_setting('profile.hide_username_with_complete_name')) {
            $username = $user->getUsername();

            return "$fullName ($username)";
        }

        return $fullName;
    }

    /**
     * @param int $userId
     *
     * @return array
     */
    public static function getUserCareers($userId)
    {
        $table = Database::get_main_table(TABLE_MAIN_USER_CAREER);
        $tableCareer = Database::get_main_table(TABLE_CAREER);
        $userId = (int) $userId;

        $sql = "SELECT c.id, c.title
                FROM $table uc
                INNER JOIN $tableCareer c
                ON uc.career_id = c.id
                WHERE user_id = $userId
                ORDER BY uc.created_at
                ";
        $result = Database::query($sql);

        return Database::store_result($result, 'ASSOC');
    }

    /**
     * @param int $userId
     * @param int $careerId
     */
    public static function addUserCareer($userId, $careerId)
    {
        if ('true' !== api_get_setting('session.allow_career_users')) {
            return false;
        }

        if (false === self::userHasCareer($userId, $careerId)) {
            $params = ['user_id' => $userId, 'career_id' => $careerId, 'created_at' => api_get_utc_datetime(), 'updated_at' => api_get_utc_datetime()];
            $table = Database::get_main_table(TABLE_MAIN_USER_CAREER);
            Database::insert($table, $params);
        }

        return true;
    }

    /**
     * @param int   $userCareerId
     * @param array $data
     *
     * @return bool
     */
    public static function updateUserCareer($userCareerId, $data)
    {
        if ('true' !== api_get_setting('session.allow_career_users')) {
            return false;
        }

        $params = ['extra_data' => $data, 'updated_at' => api_get_utc_datetime()];
        $table = Database::get_main_table(TABLE_MAIN_USER_CAREER);
        Database::update(
            $table,
            $params,
            ['id = ?' => (int) $userCareerId]
        );

        return true;
    }

    /**
     * @param int $userId
     * @param int $careerId
     *
     * @return array
     */
    public static function getUserCareer($userId, $careerId)
    {
        $userId = (int) $userId;
        $careerId = (int) $careerId;
        $table = Database::get_main_table(TABLE_MAIN_USER_CAREER);

        $sql = "SELECT * FROM $table WHERE user_id = $userId AND career_id = $careerId";
        $result = Database::query($sql);

        return Database::fetch_assoc($result);
    }

    /**
     * @param int $userId
     * @param int $careerId
     *
     * @return bool
     */
    public static function userHasCareer($userId, $careerId)
    {
        $userId = (int) $userId;
        $careerId = (int) $careerId;
        $table = Database::get_main_table(TABLE_MAIN_USER_CAREER);

        $sql = "SELECT id FROM $table WHERE user_id = $userId AND career_id = $careerId";
        $result = Database::query($sql);

        return Database::num_rows($result) > 0;
    }

    /**
     * Disables or enables a user.
     *
     * @param int $user_id
     * @param int $active  Enable or disable
     *
     * @return bool True on success, false on failure
     * @assert (-1,0) === false
     * @assert (1,1) === true
     */
    public static function change_active_state($user_id, $active)
    {
        $user_id = (int) $user_id;
        $active = (int) $active;

        if (empty($user_id)) {
            return false;
        }

        $table_user = Database::get_main_table(TABLE_MAIN_USER);
        $sql = "UPDATE $table_user SET active = '$active' WHERE id = $user_id";
        $r = Database::query($sql);
        $ev = LOG_USER_DISABLE;
        if (1 == $active) {
            $ev = LOG_USER_ENABLE;
        }
        if (false !== $r) {
            Event::addEvent($ev, LOG_USER_ID, $user_id);
        }

        return $r;
    }

    /**
     * Get either a Gravatar URL or complete image tag for a specified email address.
     *
     * @param string $email The email address
     * @param int    $s     Size in pixels, defaults to 80px [ 1 - 2048 ]
     * @param string $d     Default imageset to use [ 404 | mm | identicon | monsterid | wavatar ]
     * @param string $r     Maximum rating (inclusive) [ g | pg | r | x ]
     * @param bool   $img   True to return a complete IMG tag False for just the URL
     * @param array  $atts  Optional, additional key/value attributes to include in the IMG tag
     *
     * @return string containing either just a URL or a complete image tag
     * @source http://gravatar.com/site/implement/images/php/
     */
    private static function getGravatar(
        $email,
        $s = 80,
        $d = 'mm',
        $r = 'g',
        $img = false,
        $atts = []
    ) {
        $url = 'http://www.gravatar.com/avatar/';
        if (!empty($_SERVER['HTTPS'])) {
            $url = 'https://secure.gravatar.com/avatar/';
        }
        $url .= md5(strtolower(trim($email)));
        $url .= "?s=$s&d=$d&r=$r";
        if ($img) {
            $url = '<img src="'.$url.'"';
            foreach ($atts as $key => $val) {
                $url .= ' '.$key.'="'.$val.'"';
            }
            $url .= ' />';
        }

        return $url;
    }

    /**
     * Count users in courses and if they have certificate.
     * This function is resource intensive.
     *
     * @return array
     * @throws Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public static function countUsersWhoFinishedCourses()
    {
        $courses = [];
        $currentAccessUrlId = api_get_current_access_url_id();
        $sql = "SELECT course.code, course.id as cid, cru.user_id
                FROM course_rel_user cru
                    JOIN course ON cru.c_id = course.id
                    JOIN access_url_rel_user auru on cru.user_id = auru.user_id
                    JOIN access_url_rel_course ON course.id = access_url_rel_course.c_id
                WHERE access_url_rel_course.access_url_id = $currentAccessUrlId
                ORDER BY course.code
        ";
        $res = Database::query($sql);
        if (Database::num_rows($res) > 0) {
            while ($row = Database::fetch_array($res)) {
                if (!isset($courses[$row['code']])) {
                    $courses[$row['code']] = [
                        'subscribed' => 0,
                        'finished' => 0,
                    ];
                }
                $courses[$row['code']]['subscribed']++;
                $entityManager = Database::getManager();
                $repository = $entityManager->getRepository(GradebookCategory::class);
                //todo check when have more than 1 gradebook
                /** @var GradebookCategory $gradebook */
                $gradebook = $repository->findOneBy(['course' => $row['cid']]);
                if (!empty($gradebook)) {
                    $finished = 0;
                    Database::getManager()->persist($gradebook);
                    $certificateRepo = $entityManager->getRepository(\Chamilo\CoreBundle\Entity\GradebookCertificate::class);
                    $finished = $certificateRepo->getCertificateByUserId($gradebook->getId(), $row['user_id']);
                    if (!empty($finished)) {
                        $courses[$row['code']]['finished']++;
                    }
                }
            }
        }
        return $courses;
    }

    /**
     * Count users in sessions and if they have certificate.
     * This function is resource intensive.
     *
     * @return array
     * @throws Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public static function countUsersWhoFinishedCoursesInSessions()
    {
        $coursesInSessions = [];
        $currentAccessUrlId = api_get_current_access_url_id();
        $sql = "SELECT course.code, srcru.session_id, srcru.user_id, session.title
                FROM session_rel_course_rel_user srcru
                    JOIN course ON srcru.c_id = course.id
                    JOIN access_url_rel_session aurs on srcru.session_id = aurs.session_id
                    JOIN session ON srcru.session_id = session.id
                WHERE aurs.access_url_id = $currentAccessUrlId
                ORDER BY course.code, session.title
        ";
        $res = Database::query($sql);
        if (Database::num_rows($res) > 0) {
            while ($row = Database::fetch_array($res)) {
                $index = $row['code'].' ('.$row['title'].')';
                if (!isset($coursesInSessions[$index])) {
                    $coursesInSessions[$index] = [
                        'subscribed' => 0,
                        'finished' => 0,
                    ];
                }
                $coursesInSessions[$index]['subscribed']++;
                $entityManager = Database::getManager();
                $repository = $entityManager->getRepository(GradebookCategory::class);
                /** @var GradebookCategory $gradebook */
                $gradebook = $repository->findOneBy(
                    [
                        'course' => $row['cid'],
                        'sessionId' => $row['session_id'],
                    ]
                );
                if (!empty($gradebook)) {
                    $finished = 0;
                    Database::getManager()->persist($gradebook);
                    $certificateRepo = $entityManager->getRepository(\Chamilo\CoreBundle\Entity\GradebookCertificate::class);
                    $finished = $certificateRepo->getCertificateByUserId($gradebook->getId(), $row['user_id']);
                    if (!empty($finished)) {
                        $coursesInSessions[$index]['finished']++;
                    }
                }
            }
        }
        return $coursesInSessions;
    }

    public static function redirectToResetPassword($userId): void
    {
        if ('true' !== api_get_setting('security.force_renew_password_at_first_login')) {
            return;
        }
        $askPassword = self::get_extra_user_data_by_field(
            $userId,
            'ask_new_password'
        );
        if (!empty($askPassword) && isset($askPassword['ask_new_password']) &&
            1 === (int) $askPassword['ask_new_password']
        ) {
            $uniqueId = api_get_unique_id();
            $userObj = api_get_user_entity($userId);
            $userObj->setConfirmationToken($uniqueId);
            $userObj->setPasswordRequestedAt(new \DateTime());
            Database::getManager()->persist($userObj);
            Database::getManager()->flush();
            $url = api_get_path(WEB_CODE_PATH).'auth/reset.php?token='.$uniqueId;
            api_location($url);
        }
    }

}
