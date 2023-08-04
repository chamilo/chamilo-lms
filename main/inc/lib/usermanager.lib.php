<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\ExtraField as EntityExtraField;
use Chamilo\CoreBundle\Entity\Repository\AccessUrlRepository;
use Chamilo\CoreBundle\Entity\Session as SessionEntity;
use Chamilo\CoreBundle\Entity\SkillRelUser;
use Chamilo\CoreBundle\Entity\SkillRelUserComment;
use Chamilo\CoreBundle\Entity\TrackELoginAttempt;
use Chamilo\UserBundle\Entity\User;
use Chamilo\UserBundle\Repository\UserRepository;
use ChamiloSession as Session;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;

/**
 * Class UserManager.
 *
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

    private static $encryptionMethod;

    /**
     * Constructor.
     *
     * @assert () === null
     */
    public function __construct()
    {
    }

    /**
     * Repository is use to query the DB, selects, etc.
     *
     * @return UserRepository
     */
    public static function getRepository()
    {
        /** @var UserRepository $userRepository */

        return Database::getManager()->getRepository('ChamiloUserBundle:User');
    }

    /**
     * Create/update/delete methods are available in the UserManager
     * (based in the Sonata\UserBundle\Entity\UserManager).
     *
     * @return Chamilo\UserBundle\Entity\Manager\UserManager
     */
    public static function getManager()
    {
        static $userManager;

        if (!isset($userManager)) {
            $encoderFactory = self::getEncoderFactory();
            $passwordUpdater = new \FOS\UserBundle\Util\PasswordUpdater($encoderFactory);
            $canonicalFieldUpdater = new \FOS\UserBundle\Util\CanonicalFieldsUpdater(
                new \FOS\UserBundle\Util\Canonicalizer(), new \FOS\UserBundle\Util\Canonicalizer()
            );
            $userManager = new Chamilo\UserBundle\Entity\Manager\UserManager(
                $passwordUpdater,
                $canonicalFieldUpdater,
                Database::getManager(),
                'Chamilo\\UserBundle\\Entity\\User'
            );
        }

        return $userManager;
    }

    /**
     * @param string $encryptionMethod
     */
    public static function setPasswordEncryption($encryptionMethod)
    {
        self::$encryptionMethod = $encryptionMethod;
    }

    /**
     * @return bool|mixed
     */
    public static function getPasswordEncryption()
    {
        $encryptionMethod = self::$encryptionMethod;
        if (empty($encryptionMethod)) {
            $encryptionMethod = api_get_configuration_value('password_encryption');
        }

        return $encryptionMethod;
    }

    /**
     * Validates the password.
     *
     * @param $encoded
     * @param $raw
     * @param $salt
     *
     * @return bool
     */
    public static function isPasswordValid($encoded, $raw, $salt)
    {
        $encoder = new \Chamilo\UserBundle\Security\Encoder(self::getPasswordEncryption());

        return $encoder->isPasswordValid($encoded, $raw, $salt);
    }

    /**
     * Detects and returns the type of encryption of the given encrypted
     * password.
     *
     * @param string $encoded The encrypted password
     * @param string $salt    The user salt, if any
     */
    public static function detectPasswordEncryption(string $encoded, string $salt): string
    {
        $encryption = false;

        $length = strlen($encoded);

        $pattern = '/^\$2y\$04\$[A-Za-z0-9\.\/]{53}$/';

        if ($length == 60 && preg_match($pattern, $encoded)) {
            $encryption = 'bcrypt';
        } elseif ($length == 32 && ctype_xdigit($encoded)) {
            $encryption = 'md5';
        } elseif ($length == 40 && ctype_xdigit($encoded)) {
            $encryption = 'sha1';
        } else {
            $start = strpos($encoded, '{');
            if ($start !== false && substr($encoded, -1, 1) == '}') {
                if (substr($encoded, $start + 1, -1) == $salt) {
                    $encryption = 'none';
                }
            }
        }

        return $encryption;
    }

    /**
     * Checks if the password is correct for this user.
     * If the password_conversion setting is true, also update the password
     * in the database to a new encryption method.
     *
     * @param string $encoded Encrypted password
     * @param string $raw     Clear password given through login form
     * @param string $salt    User salt, if any
     * @param int    $userId  The user's internal ID
     */
    public static function checkPassword(string $encoded, string $raw, string $salt, int $userId): bool
    {
        $result = false;

        if (true === api_get_configuration_value('password_conversion')) {
            $detectedEncryption = self::detectPasswordEncryption($encoded, $salt);
            if (self::getPasswordEncryption() != $detectedEncryption) {
                $encoder = new \Chamilo\UserBundle\Security\Encoder($detectedEncryption);
                $result = $encoder->isPasswordValid($encoded, $raw, $salt);
                if ($result) {
                    $raw = $encoder->encodePassword($encoded, $salt);
                    self::updatePassword($userId, $raw);
                }
            } else {
                $result = self::isPasswordValid($encoded, $raw, $salt);
            }
        } else {
            $result = self::isPasswordValid($encoded, $raw, $salt);
        }

        return $result;
    }

    /**
     * Encrypt the password using the current encoder.
     *
     * @param string $raw The clear password
     */
    public static function encryptPassword(string $raw, User $user): string
    {
        $encoder = self::getEncoder($user);

        return $encoder->encodePassword(
            $raw,
            $user->getSalt()
        );
    }

    /**
     * Update the password of the given user to the given (in-clear) password.
     *
     * @param int    $userId   Internal user ID
     * @param string $password Password in clear
     */
    public static function updatePassword(int $userId, string $password): void
    {
        $repository = self::getRepository();
        /** @var User $user */
        $user = $repository->find($userId);
        $userManager = self::getManager();
        $user->setPlainPassword($password);
        $userManager->updateUser($user, true);
        Event::addEvent(LOG_USER_PASSWORD_UPDATE, LOG_USER_ID, $userId);
    }

    /**
     * Updates user expiration date.
     *
     * @param int    $userId
     * @param string $expirationDate
     */
    public static function updateExpirationDate($userId, $expirationDate)
    {
        $repository = self::getRepository();
        /** @var User $user */
        $user = $repository->find($userId);
        $userManager = self::getManager();
        $expirationDate = api_get_utc_datetime($expirationDate, false, true);
        $user->setExpirationDate($expirationDate);
        $userManager->updateUser($user, true);
    }

    /**
     * Creates a new user for the platform.
     *
     * @author Hugues Peeters <peeters@ipm.ucl.ac.be>,
     * @author Roan Embrechts <roan_embrechts@yahoo.com>
     *
     * @param string        $firstName
     * @param string        $lastName
     * @param int           $status                  (1 for course tutor, 5 for student, 6 for anonymous)
     * @param string        $email
     * @param string        $loginName
     * @param string        $password
     * @param string        $official_code           Any official code (optional)
     * @param string        $language                User language    (optional)
     * @param string        $phone                   Phone number    (optional)
     * @param string        $picture_uri             Picture URI        (optional)
     * @param string        $authSource              Authentication source (defaults to 'platform', dependind on constant)
     * @param string        $expirationDate          Account expiration date (optional, defaults to null)
     * @param int           $active                  Whether the account is enabled or disabled by default
     * @param int           $hr_dept_id              The department of HR in which the user is registered (defaults to 0)
     * @param array         $extra                   Extra fields (prefix labels with "extra_")
     * @param string        $encrypt_method          Used if password is given encrypted. Set to an empty string by default
     * @param bool          $send_mail
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
     */
    public static function create_user(
        $firstName,
        $lastName,
        $status,
        $email,
        $loginName,
        $password,
        $official_code = '',
        $language = '',
        $phone = '',
        $picture_uri = '',
        $authSource = PLATFORM_AUTH_SOURCE,
        $expirationDate = null,
        $active = 1,
        $hr_dept_id = 0,
        $extra = [],
        $encrypt_method = '',
        $send_mail = false,
        $isAdmin = false,
        $address = '',
        $sendEmailToAllAdmins = false,
        $form = null,
        $creatorId = 0,
        $emailTemplate = [],
        $redirectToURLAfterLogin = ''
    ) {
        $creatorId = empty($creatorId) ? api_get_user_id() : 0;
        $creatorInfo = api_get_user_info($creatorId);
        $creatorEmail = isset($creatorInfo['email']) ? $creatorInfo['email'] : '';

        $hook = HookCreateUser::create();
        if (!empty($hook)) {
            $hook->notifyCreateUser(HOOK_EVENT_TYPE_PRE);
        }

        if ('true' === api_get_setting('registration', 'email')) {
            // Force email validation.
            if (false === api_valid_email($email)) {
                Display::addFlash(
                   Display::return_message(get_lang('PleaseEnterValidEmail').' - '.$email, 'warning')
               );

                return false;
            }
        } else {
            // Allow empty email. If email is set, check if is valid.
            if (!empty($email) && false === api_valid_email($email)) {
                Display::addFlash(
                    Display::return_message(get_lang('PleaseEnterValidEmail').' - '.$email, 'warning')
                );

                return false;
            }
        }

        if ('true' === api_get_setting('login_is_email')) {
            if (false === api_valid_email($loginName)) {
                Display::addFlash(
                    Display::return_message(get_lang('PleaseEnterValidEmail').' - '.$loginName, 'warning')
                );

                return false;
            }
        } else {
            if (false === self::is_username_valid($loginName)) {
                Display::addFlash(
                    Display::return_message(get_lang('UsernameWrong').' - '.$loginName, 'warning')
                );

                return false;
            }
        }

        // First check wether the login already exists
        if (!self::is_username_available($loginName)) {
            Display::addFlash(
                Display::return_message(get_lang('LoginAlreadyTaken').' - '.$loginName, 'warning')
            );

            return false;
        }

        global $_configuration;
        $original_password = $password;

        $access_url_id = 1;
        if (api_get_multiple_access_url()) {
            $access_url_id = api_get_current_access_url_id();
        } else {
            // In some cases, the first access_url ID might be different from 1
            // for example when using a DB cluster or hacking the DB manually.
            // In this case, we want the first row, not necessarily "1".
            $dbm = Database::getManager();
            /** @var AccessUrlRepository $accessUrlRepository */
            $accessUrlRepository = $dbm->getRepository('ChamiloCoreBundle:AccessUrl');
            $accessUrl = $accessUrlRepository->getFirstId();
            if (!empty($accessUrl[0]) && !empty($accessUrl[0][1])) {
                $access_url_id = $accessUrl[0][1];
            }
        }

        if (isset($_configuration[$access_url_id]) &&
            is_array($_configuration[$access_url_id]) &&
            isset($_configuration[$access_url_id]['hosting_limit_users']) &&
            $_configuration[$access_url_id]['hosting_limit_users'] > 0) {
            $num = self::get_number_of_users(null, $access_url_id);
            if ($num >= $_configuration[$access_url_id]['hosting_limit_users']) {
                api_warn_hosting_contact('hosting_limit_users');
                Display::addFlash(
                    Display::return_message(
                        get_lang('PortalUsersLimitReached'),
                        'warning'
                    )
                );

                return false;
            }
        }

        if ($status === 1 &&
            isset($_configuration[$access_url_id]) &&
            is_array($_configuration[$access_url_id]) &&
            isset($_configuration[$access_url_id]['hosting_limit_teachers']) &&
            $_configuration[$access_url_id]['hosting_limit_teachers'] > 0
        ) {
            $num = self::get_number_of_users(1, $access_url_id);
            if ($num >= $_configuration[$access_url_id]['hosting_limit_teachers']) {
                Display::addFlash(
                    Display::return_message(
                        get_lang('PortalTeachersLimitReached'),
                        'warning'
                    )
                );
                api_warn_hosting_contact('hosting_limit_teachers');

                return false;
            }
        }

        if (empty($password)) {
            if ($authSource === PLATFORM_AUTH_SOURCE) {
                Display::addFlash(
                    Display::return_message(
                        get_lang('ThisFieldIsRequired').': '.get_lang(
                            'Password'
                        ),
                        'warning'
                    )
                );

                return false;
            }

            // We use the authSource as password.
            // The real validation will be by processed by the auth
            // source not Chamilo
            $password = $authSource;
        }

        // database table definition
        $table_user = Database::get_main_table(TABLE_MAIN_USER);

        // Checking the user language
        $languages = api_get_languages();
        $language = strtolower($language);

        if (isset($languages['folder'])) {
            if (!in_array($language, $languages['folder'])) {
                $language = api_get_setting('platformLanguage');
            }
        }

        $currentDate = api_get_utc_datetime();
        $now = new DateTime();

        if (empty($expirationDate) || $expirationDate == '0000-00-00 00:00:00') {
            // Default expiration date
            // if there is a default duration of a valid account then
            // we have to change the expiration_date accordingly
            // Accept 0000-00-00 00:00:00 as a null value to avoid issues with
            // third party code using this method with the previous (pre-1.10)
            // value of 0000...
            if (api_get_setting('account_valid_duration') != '') {
                $expirationDate = new DateTime($currentDate);
                $days = (int) api_get_setting('account_valid_duration');
                $expirationDate->modify('+'.$days.' day');
            }
        } else {
            $expirationDate = api_get_utc_datetime($expirationDate);
            $expirationDate = new \DateTime($expirationDate, new DateTimeZone('UTC'));
        }

        $userManager = self::getManager();

        /** @var User $user */
        $user = $userManager->createUser();

        $user
            ->setLastname($lastName)
            ->setFirstname($firstName)
            ->setUsername($loginName)
            ->setStatus($status)
            ->setPlainPassword($password)
            ->setEmail($email)
            ->setOfficialCode($official_code)
            ->setPictureUri($picture_uri)
            ->setCreatorId($creatorId)
            ->setAuthSource($authSource)
            ->setPhone($phone)
            ->setAddress($address)
            ->setLanguage($language)
            ->setRegistrationDate($now)
            ->setHrDeptId($hr_dept_id)
            ->setActive($active)
            ->setEnabled($active)
        ;

        if (!empty($expirationDate)) {
            $user->setExpirationDate($expirationDate);
        }

        $userManager->updateUser($user);
        $userId = $user->getId();

        if (!empty($userId)) {
            $return = $userId;
            $sql = "UPDATE $table_user SET user_id = $return WHERE id = $return";
            Database::query($sql);

            if ($isAdmin) {
                self::addUserAsAdmin($user);
            }

            if (api_get_multiple_access_url()) {
                UrlManager::add_user_to_url($userId, api_get_current_access_url_id());
            } else {
                //we are adding by default the access_url_user table with access_url_id = 1
                UrlManager::add_user_to_url($userId, 1);
            }

            $extra['item_id'] = $userId;

            if (is_array($extra) && count($extra) > 0) {
                $userFieldValue = new ExtraFieldValue('user');
                // Force saving of extra fields (otherwise, if the current
                // user is not admin, fields not visible to the user - most
                // of them - are just ignored)
                $userFieldValue->saveFieldValues(
                    $extra,
                    true,
                    null,
                    null,
                    null,
                    true
                );
            } else {
                // Create notify settings by default
                self::update_extra_field_value(
                    $userId,
                    'mail_notify_invitation',
                    '1'
                );
                self::update_extra_field_value(
                    $userId,
                    'mail_notify_message',
                    '1'
                );
                self::update_extra_field_value(
                    $userId,
                    'mail_notify_group_message',
                    '1'
                );
            }

            self::update_extra_field_value(
                $userId,
                'already_logged_in',
                'false'
            );

            if (!empty($redirectToURLAfterLogin) && api_get_configuration_value('plugin_redirection_enabled')) {
                RedirectionPlugin::insert($userId, $redirectToURLAfterLogin);
            }

            if (!empty($email) && $send_mail) {
                $recipient_name = api_get_person_name(
                    $firstName,
                    $lastName,
                    null,
                    PERSON_NAME_EMAIL_ADDRESS
                );
                $tplSubject = new Template(
                    null,
                    false,
                    false,
                    false,
                    false,
                    false
                );
                $layoutSubject = $tplSubject->get_template('mail/subject_registration_platform.tpl');
                $emailSubject = $tplSubject->fetch($layoutSubject);
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
                    if ($access_url_id != -1) {
                        $urlInfo = api_get_access_url($access_url_id);
                        if ($urlInfo) {
                            $url = $urlInfo['url'];
                        }
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
                $tplContent->assign('complete_name', stripslashes(api_get_person_name($firstName, $lastName)));
                $tplContent->assign('login_name', $loginName);
                $tplContent->assign('original_password', stripslashes($original_password));
                $tplContent->assign('mailWebPath', $url);
                $tplContent->assign('new_user', $user);
                // Adding this variable but not used in default template, used for task BT19518 with a customized template
                $tplContent->assign('status_type', $status);

                $layoutContent = $tplContent->get_template('mail/content_registration_platform.tpl');
                $emailBody = $tplContent->fetch($layoutContent);

                $userInfo = api_get_user_info($userId);
                $mailTemplateManager = new MailTemplateManager();

                /* MANAGE EVENT WITH MAIL */
                if (EventsMail::check_if_using_class('user_registration')) {
                    $values["about_user"] = $return;
                    $values["password"] = $original_password;
                    $values["send_to"] = [$return];
                    $values["prior_lang"] = null;
                    EventsDispatcher::events('user_registration', $values);
                } else {
                    $phoneNumber = isset($extra['mobile_phone_number']) ? $extra['mobile_phone_number'] : null;
                    $additionalParameters = [
                        'smsType' => SmsPlugin::WELCOME_LOGIN_PASSWORD,
                        'userId' => $return,
                        'mobilePhoneNumber' => $phoneNumber,
                        'password' => $original_password,
                    ];

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

                    $twoEmail = api_get_configuration_value('send_two_inscription_confirmation_mail');
                    if ($twoEmail === true) {
                        $layoutContent = $tplContent->get_template('mail/new_user_first_email_confirmation.tpl');
                        $emailBody = $tplContent->fetch($layoutContent);

                        if (!empty($emailTemplate) &&
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
                            null,
                            null,
                            null,
                            $additionalParameters,
                            $creatorEmail
                        );

                        $layoutContent = $tplContent->get_template('mail/new_user_second_email_confirmation.tpl');
                        $emailBody = $tplContent->fetch($layoutContent);

                        if (!empty($emailTemplate) &&
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
                            null,
                            null,
                            null,
                            $additionalParameters,
                            $creatorEmail
                        );
                    } else {
                        if (!empty($emailBodyTemplate)) {
                            $emailBody = $emailBodyTemplate;
                        }
                        $sendToInbox = api_get_configuration_value('send_inscription_msg_to_inbox');
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
                                $emailBody,
                                $senderId
                            );
                        } else {
                            api_mail_html(
                                $recipient_name,
                                $email,
                                $emailSubject,
                                $emailBody,
                                $sender_name,
                                $email_admin,
                                null,
                                null,
                                null,
                                $additionalParameters,
                                $creatorEmail
                            );
                        }
                    }

                    $notification = api_get_configuration_value('send_notification_when_user_added');
                    if (!empty($notification) && isset($notification['admins']) && is_array($notification['admins'])) {
                        foreach ($notification['admins'] as $adminId) {
                            $emailSubjectToAdmin = get_lang('UserAdded').': '.api_get_person_name($firstName, $lastName);
                            MessageManager::send_message_simple($adminId, $emailSubjectToAdmin, $emailBody, $userId);
                        }
                    }
                }

                if ($sendEmailToAllAdmins) {
                    $adminList = self::get_all_administrators();

                    $tplContent = new Template(
                        null,
                        false,
                        false,
                        false,
                        false,
                        false
                    );
                    // variables for the default template
                    $tplContent->assign('complete_name', stripslashes(api_get_person_name($firstName, $lastName)));
                    $tplContent->assign('user_added', $user);
                    $renderer = FormValidator::getDefaultRenderer();
                    // Form template
                    $elementTemplate = ' {label}: {element} <br />';
                    $renderer->setElementTemplate($elementTemplate);
                    /** @var FormValidator $form */
                    $form->freeze(null, $elementTemplate);
                    $form->removeElement('submit');
                    $formData = $form->returnForm();
                    $url = api_get_path(WEB_CODE_PATH).'admin/user_information.php?user_id='.$user->getId();
                    $tplContent->assign('link', Display::url($url, $url));
                    $tplContent->assign('form', $formData);

                    $layoutContent = $tplContent->get_template('mail/content_registration_platform_to_admin.tpl');
                    $emailBody = $tplContent->fetch($layoutContent);

                    if (!empty($emailTemplate) &&
                        isset($emailTemplate['content_registration_platform_to_admin.tpl']) &&
                        !empty($emailTemplate['content_registration_platform_to_admin.tpl'])
                    ) {
                        $emailBody = $mailTemplateManager->parseTemplate(
                            $emailTemplate['content_registration_platform_to_admin.tpl'],
                            $userInfo
                        );
                    }

                    $subject = get_lang('UserAdded');
                    foreach ($adminList as $adminId => $data) {
                        MessageManager::send_message_simple(
                            $adminId,
                            $subject,
                            $emailBody,
                            $userId
                        );
                    }
                }
                /* ENDS MANAGE EVENT WITH MAIL */
            }

            if (!empty($hook)) {
                $hook->setEventData([
                    'return' => $userId,
                    'originalPassword' => $original_password,
                ]);
                $hook->notifyCreateUser(HOOK_EVENT_TYPE_POST);
            }
            Event::addEvent(LOG_USER_CREATE, LOG_USER_ID, $userId);
        } else {
            Display::addFlash(
                Display::return_message(get_lang('ErrorContactPlatformAdmin'))
            );

            return false;
        }

        return $return;
    }

    /**
     * Ensure the CAS-authenticated user exists in the database.
     *
     * @param $casUser string the CAS user identifier
     *
     * @throws Exception if more than one user share the same CAS user identifier
     *
     * @return string|bool the recognised user login name or false if not found
     */
    public static function casUserLoginName($casUser)
    {
        $loginName = false;

        // look inside the casUser extra field
        if (UserManager::is_extra_field_available('cas_user')) {
            $valueModel = new ExtraFieldValue('user');
            $itemList = $valueModel->get_item_id_from_field_variable_and_field_value(
                'cas_user',
                $casUser,
                false,
                false,
                true
            );
            if (false !== $itemList) {
                // at least one user has $casUser in the 'cas_user' extra field
                // we attempt to load each candidate user because there might be deleted ones
                // (extra field values of a deleted user might remain)
                foreach ($itemList as $item) {
                    $userId = intval($item['item_id']);
                    $user = UserManager::getRepository()->find($userId);
                    if (!is_null($user)) {
                        if (false === $loginName) {
                            $loginName = $user->getUsername();
                        } else {
                            throw new Exception(get_lang('MoreThanOneUserMatched'));
                        }
                    }
                }
            }
        }

        if (false === $loginName) {
            // no matching 'cas_user' extra field value, or no such extra field
            // falling back to the old behaviour: $casUser must be the login name
            $userId = UserManager::get_user_id_from_username($casUser);
            if (false !== $userId) {
                $loginName = $casUser;
            }
        }

        return $loginName;
    }

    /**
     * Checks the availability of extra field 'cas_user'
     * and creates it if missing.
     *
     * @throws Exception on failure
     */
    public static function ensureCASUserExtraFieldExists()
    {
        if (!self::is_extra_field_available('cas_user')) {
            $extraField = new ExtraField('user');
            if (false === $extraField->save(
                    [
                        'variable' => 'cas_user',
                        'field_type' => ExtraField::FIELD_TYPE_TEXT,
                        'display_text' => get_lang('CAS User Identifier'),
                        'visible_to_self' => true,
                        'filter' => true,
                    ]
                )) {
                throw new Exception(get_lang('FailedToCreateExtraFieldCasUser'));
            }

            $rules = api_get_configuration_value('cas_user_map');
            if (!empty($rules) && isset($rules['extra'])) {
                foreach ($rules['extra'] as $extra) {
                    $extraField->save(
                        [
                            'variable' => $extra,
                            'field_type' => ExtraField::FIELD_TYPE_TEXT,
                            'display_text' => $extra,
                            'visible_to_self' => false,
                            'filter' => false,
                        ]
                    );
                }
            }
        }
    }

    /**
     * Create a CAS-authenticated user from scratch, from its CAS user identifier, with temporary default values.
     *
     * @param string $casUser the CAS user identifier
     *
     * @throws Exception on error
     *
     * @return string the login name of the new user
     */
    public static function createCASAuthenticatedUserFromScratch($casUser)
    {
        self::ensureCASUserExtraFieldExists();

        $loginName = 'cas_user_'.$casUser;
        $defaultValue = get_lang('EditInProfile');
        $defaultEmailValue = get_lang('EditInProfile');
        require_once __DIR__.'/../../auth/external_login/functions.inc.php';
        if ('true' === api_get_setting('login_is_email')) {
            $defaultEmailValue = $casUser;
        }
        $userId = external_add_user(
            [
                'username' => $loginName,
                'auth_source' => CAS_AUTH_SOURCE,
                'firstname' => $defaultValue,
                'lastname' => $defaultValue,
                'email' => $defaultEmailValue,
            ]
        );
        if (false === $userId) {
            throw new Exception(get_lang('FailedUserCreation'));
        }
        // Not checking function update_extra_field_value return value because not reliable
        self::update_extra_field_value($userId, 'cas_user', $casUser);

        return $loginName;
    }

    public static function updateCasUser($_user)
    {
        $rules = api_get_configuration_value('cas_user_map');

        if (empty($_user)) {
            return false;
        }

        if (!empty($rules)) {
            $userEntity = api_get_user_entity($_user['id']);
            $attributes = phpCAS::getAttributes();
            if (isset($rules['fields'])) {
                $isAdmin = false;
                foreach ($rules['fields'] as $field => $attributeName) {
                    if (!isset($attributes[$attributeName])) {
                        continue;
                    }
                    $value = $attributes[$attributeName];
                    // Check replace.
                    if (isset($rules['replace'][$attributeName])) {
                        $value = $rules['replace'][$attributeName][$value];
                    }

                    switch ($field) {
                        case 'email':
                            $userEntity->setEmail($value);
                            break;
                        case 'firstname':
                            $userEntity->setFirstname($value);
                            break;
                        case 'lastname':
                            $userEntity->setLastname($value);
                            break;
                        case 'active':
                            $userEntity->setActive('false' === $value);
                            break;
                        case 'status':
                            if (PLATFORM_ADMIN === (int) $value) {
                                $value = COURSEMANAGER;
                                $isAdmin = true;
                            }
                            $userEntity->setStatus($value);
                            break;
                    }

                    Database::getManager()->persist($userEntity);
                    Database::getManager()->flush();

                    if ($isAdmin) {
                        self::addUserAsAdmin($userEntity);
                    }
                }
            }

            if (isset($rules['extra'])) {
                foreach ($rules['extra'] as $variable) {
                    if (isset($attributes[$variable])) {
                        self::update_extra_field_value(
                            $_user['id'],
                            $variable,
                            $attributes[$variable]
                        );
                    }
                }
            }
        }
    }

    /**
     * Create a CAS-authenticated user from LDAP, from its CAS user identifier.
     *
     * @param $casUser
     *
     * @throws Exception
     *
     * @return string login name of the new user
     */
    public static function createCASAuthenticatedUserFromLDAP($casUser)
    {
        self::ensureCASUserExtraFieldExists();

        require_once __DIR__.'/../../auth/external_login/ldap.inc.php';
        $login = extldapCasUserLogin($casUser);
        if (false !== $login) {
            $ldapUser = extldap_authenticate($login, 'nopass', true);
            if (false !== $ldapUser) {
                require_once __DIR__.'/../../auth/external_login/functions.inc.php';
                $user = extldap_get_chamilo_user($ldapUser);
                $user['username'] = $login;
                $user['auth_source'] = CAS_AUTH_SOURCE;
                $userId = external_add_user($user);
                if (false !== $userId) {
                    // Not checking function update_extra_field_value return value because not reliable
                    self::update_extra_field_value($userId, 'cas_user', $casUser);

                    return $login;
                } else {
                    throw new Exception('Could not create the new user '.$login);
                }
            } else {
                throw new Exception('Could not load the new user from LDAP using its login '.$login);
            }
        } else {
            throw new Exception('Could not find the new user from LDAP using its cas user identifier '.$casUser);
        }
    }

    /**
     * updates user record in database from its LDAP record
     * copies relevant LDAP attribute values : firstname, lastname and email.
     *
     * @param $login string the user login name
     *
     * @throws Exception when the user login name is not found in the LDAP or in the database
     */
    public static function updateUserFromLDAP($login)
    {
        require_once __DIR__.'/../../auth/external_login/ldap.inc.php';

        $ldapUser = extldap_authenticate($login, 'nopass', true);
        if (false === $ldapUser) {
            throw new Exception(get_lang('NoSuchUserInLDAP'));
        }

        $user = extldap_get_chamilo_user($ldapUser);
        $userInfo = api_get_user_info_from_username($login);
        if (false === $userInfo) {
            throw new Exception(get_lang('NoSuchUserInInternalDatabase'));
        }

        $userId = UserManager::update_user(
            $userInfo['user_id'],
            $user["firstname"],
            $user["lastname"],
            $login,
            null,
            $userInfo['auth_source'],
            $user["email"],
            $userInfo['status'],
            $userInfo['official_code'],
            $userInfo['phone'],
            $userInfo['picture_uri'],
            $userInfo['expiration_date'],
            $userInfo['active'],
            $userInfo['creator_id'],
            $userInfo['hr_dept_id'],
            null,
            $userInfo['language']
        );
        if (false === $userId) {
            throw new Exception(get_lang('CouldNotUpdateUser'));
        }
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
        $deny = api_get_configuration_value('deny_delete_users');

        if ($deny) {
            return false;
        }

        $table_course_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $user_id = (int) $user_id;

        if (empty($user_id)) {
            return false;
        }

        $res = Database::query(
            "SELECT c_id FROM $table_course_user WHERE status = 1 AND user_id = $user_id"
        );
        while ($course = Database::fetch_assoc($res)) {
            $sql = Database::query(
                "SELECT COUNT(id) number FROM $table_course_user WHERE status = 1 AND c_id = {$course['c_id']}"
            );
            $res2 = Database::fetch_assoc($sql);

            if ($res2['number'] == 1) {
                return false;
            }
        }

        return true;
    }

    /**
     * Delete a user from the platform, and all its belongings. This is a
     * very dangerous function that should only be accessible by
     * super-admins. Other roles should only be able to disable a user,
     * which removes access to the platform but doesn't delete anything.
     *
     * @param int The ID of th user to be deleted
     *
     * @throws Exception
     *
     * @return bool true if user is successfully deleted, false otherwise
     * @assert (null) === false
     * @assert ('abc') === false
     */
    public static function delete_user($user_id)
    {
        $user_id = (int) $user_id;

        if (empty($user_id)) {
            return false;
        }

        if (!self::canDeleteUser($user_id)) {
            return false;
        }

        $table_user = Database::get_main_table(TABLE_MAIN_USER);
        $usergroup_rel_user = Database::get_main_table(TABLE_USERGROUP_REL_USER);
        $table_course_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $table_course = Database::get_main_table(TABLE_MAIN_COURSE);
        $table_session = Database::get_main_table(TABLE_MAIN_SESSION);
        $table_admin = Database::get_main_table(TABLE_MAIN_ADMIN);
        $table_session_user = Database::get_main_table(TABLE_MAIN_SESSION_USER);
        $table_session_course_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $table_group = Database::get_course_table(TABLE_GROUP_USER);
        $table_work = Database::get_course_table(TABLE_STUDENT_PUBLICATION);

        // Unsubscribe the user from all groups in all his courses
        $sql = "SELECT c.id
                FROM $table_course c
                INNER JOIN $table_course_user cu
                ON (c.id = cu.c_id)
                WHERE
                    cu.user_id = '".$user_id."' AND
                    relation_type<>".COURSE_RELATION_TYPE_RRHH."
                ";

        $res = Database::query($sql);
        while ($course = Database::fetch_object($res)) {
            $sql = "DELETE FROM $table_group
                    WHERE c_id = {$course->id} AND user_id = $user_id";
            Database::query($sql);
        }

        // Unsubscribe user from usergroup_rel_user
        $sql = "DELETE FROM $usergroup_rel_user WHERE user_id = '".$user_id."'";
        Database::query($sql);

        // Unsubscribe user from all courses
        $sql = "DELETE FROM $table_course_user WHERE user_id = '".$user_id."'";
        Database::query($sql);

        // Unsubscribe user from all courses in sessions
        $sql = "DELETE FROM $table_session_course_user WHERE user_id = '".$user_id."'";
        Database::query($sql);

        // If the user was added as a id_coach then set the current admin as coach see BT#
        $currentUserId = api_get_user_id();
        $sql = "UPDATE $table_session SET id_coach = $currentUserId
                WHERE id_coach = '".$user_id."'";
        Database::query($sql);

        $sql = "UPDATE $table_session SET session_admin_id = $currentUserId
                WHERE session_admin_id = '".$user_id."'";
        Database::query($sql);

        // Unsubscribe user from all sessions
        $sql = "DELETE FROM $table_session_user
                WHERE user_id = '".$user_id."'";
        Database::query($sql);

        if (api_get_configuration_value('plugin_redirection_enabled')) {
            RedirectionPlugin::deleteUserRedirection($user_id);
        }

        $user_info = api_get_user_info($user_id);

        try {
            self::deleteUserFiles($user_id);
        } catch (Exception $exception) {
            error_log('Delete user exception: '.$exception->getMessage());
        }

        // Delete the personal course categories
        $course_cat_table = Database::get_main_table(TABLE_USER_COURSE_CATEGORY);
        $sql = "DELETE FROM $course_cat_table WHERE user_id = '".$user_id."'";
        Database::query($sql);

        // Delete user from the admin table
        $sql = "DELETE FROM $table_admin WHERE user_id = '".$user_id."'";
        Database::query($sql);

        // Delete the personal agenda-items from this user
        $agenda_table = Database::get_main_table(TABLE_PERSONAL_AGENDA);
        $sql = "DELETE FROM $agenda_table WHERE user = '".$user_id."'";
        Database::query($sql);

        $gradebook_results_table = Database::get_main_table(TABLE_MAIN_GRADEBOOK_RESULT);
        $sql = 'DELETE FROM '.$gradebook_results_table.' WHERE user_id = '.$user_id;
        Database::query($sql);

        $extraFieldValue = new ExtraFieldValue('user');
        $extraFieldValue->deleteValuesByItem($user_id);

        UrlManager::deleteUserFromAllUrls($user_id);

        if (api_get_setting('allow_social_tool') === 'true') {
            $userGroup = new UserGroup();
            //Delete user from portal groups
            $group_list = $userGroup->get_groups_by_user($user_id);
            if (!empty($group_list)) {
                foreach ($group_list as $group_id => $data) {
                    $userGroup->delete_user_rel_group($user_id, $group_id);
                }
            }

            // Delete user from friend lists
            SocialManager::remove_user_rel_user($user_id, true);
        }

        // Removing survey invitation
        SurveyManager::delete_all_survey_invitations_by_user($user_id);

        // Delete students works
        $sql = "DELETE FROM $table_work WHERE user_id = $user_id AND c_id <> 0";
        Database::query($sql);

        $sql = "UPDATE c_item_property SET to_user_id = NULL
                WHERE to_user_id = '".$user_id."'";
        Database::query($sql);

        $sql = "UPDATE c_item_property SET insert_user_id = NULL
                WHERE insert_user_id = '".$user_id."'";
        Database::query($sql);

        $sql = "UPDATE c_item_property SET lastedit_user_id = NULL
                WHERE lastedit_user_id = '".$user_id."'";
        Database::query($sql);

        // Skills
        $em = Database::getManager();

        $criteria = ['user' => $user_id];
        $skills = $em->getRepository('ChamiloCoreBundle:SkillRelUser')->findBy($criteria);
        if ($skills) {
            /** @var SkillRelUser $skill */
            foreach ($skills as $skill) {
                $comments = $skill->getComments();
                if ($comments) {
                    /** @var SkillRelUserComment $comment */
                    foreach ($comments as $comment) {
                        $em->remove($comment);
                    }
                }
                $em->remove($skill);
            }
            $em->flush();
        }

        // ExtraFieldSavedSearch
        $criteria = ['user' => $user_id];
        $searchList = $em->getRepository('ChamiloCoreBundle:ExtraFieldSavedSearch')->findBy($criteria);
        if ($searchList) {
            foreach ($searchList as $search) {
                $em->remove($search);
            }
            $em->flush();
        }

        $connection = Database::getManager()->getConnection();
        $tableExists = $connection->getSchemaManager()->tablesExist(['plugin_bbb_room']);
        if ($tableExists) {
            // Delete user from database
            $sql = "DELETE FROM plugin_bbb_room WHERE participant_id = $user_id";
            Database::query($sql);
        }

        // Delete user/ticket relationships :(
        $tableExists = $connection->getSchemaManager()->tablesExist(['ticket_ticket']);
        if ($tableExists) {
            TicketManager::deleteUserFromTicketSystem($user_id);
        }

        $tableExists = $connection->getSchemaManager()->tablesExist(['c_lp_category_user']);
        if ($tableExists) {
            $sql = "DELETE FROM c_lp_category_user WHERE user_id = $user_id";
            Database::query($sql);
        }

        $app_plugin = new AppPlugin();
        $app_plugin->performActionsWhenDeletingItem('user', $user_id);

        // Delete user from database
        $sql = "DELETE FROM $table_user WHERE id = '".$user_id."'";
        Database::query($sql);

        // Add event to system log
        $user_id_manager = api_get_user_id();

        Event::addEvent(
            LOG_USER_DELETE,
            LOG_USER_ID,
            $user_id,
            api_get_utc_datetime(),
            $user_id_manager
        );

        Event::addEvent(
            LOG_USER_DELETE,
            LOG_USER_OBJECT,
            $user_info,
            api_get_utc_datetime(),
            $user_id_manager
        );
        $cacheAvailable = api_get_configuration_value('apc');
        if ($cacheAvailable === true) {
            $apcVar = api_get_configuration_value('apc_prefix').'userinfo_'.$user_id;
            if (apcu_exists($apcVar)) {
                apcu_delete($apcVar);
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
        if (!is_array($ids) || count($ids) == 0) {
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
        if ($r !== false) {
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
        if ($r !== false) {
            Event::addEvent(LOG_USER_ENABLE, LOG_USER_ID, $ids);

            return true;
        }

        return false;
    }

    /**
     * Update user information with new openid.
     *
     * @param int    $user_id
     * @param string $openid
     *
     * @return bool true if the user information was updated
     * @assert (false,'') === false
     * @assert (-1,'') === false
     */
    public static function update_openid($user_id, $openid)
    {
        $table_user = Database::get_main_table(TABLE_MAIN_USER);
        if ($user_id != strval(intval($user_id))) {
            return false;
        }
        if ($user_id === false) {
            return false;
        }
        $sql = "UPDATE $table_user SET
                openid='".Database::escape_string($openid)."'";
        $sql .= " WHERE id= $user_id";

        if (Database::query($sql) !== false) {
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
     * @param string $auth_source     The authentication source (default: "platform")
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
        $auth_source,
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
        $language = 'english',
        $encrypt_method = '',
        $send_email = false,
        $reset_password = 0,
        $address = null,
        $emailTemplate = []
    ) {
        $hook = HookUpdateUser::create();
        if (!empty($hook)) {
            $hook->notifyUpdateUser(HOOK_EVENT_TYPE_PRE);
        }
        $original_password = $password;
        $user_id = (int) $user_id;
        $creator_id = (int) $creator_id;

        if (empty($user_id)) {
            return false;
        }

        $userManager = self::getManager();
        /** @var User $user */
        $user = self::getRepository()->find($user_id);

        if (empty($user)) {
            return false;
        }

        if ($reset_password == 0) {
            $password = null;
            $auth_source = $user->getAuthSource();
        } elseif ($reset_password == 1) {
            $original_password = $password = api_generate_password();
            $auth_source = PLATFORM_AUTH_SOURCE;
        } elseif ($reset_password == 2) {
            //$password = $password;
            $auth_source = PLATFORM_AUTH_SOURCE;
        } elseif ($reset_password == 3) {
            //$password = $password;
            //$auth_source = $auth_source;
        }

        // Checking the user language
        $languages = api_get_languages();
        if (!in_array($language, $languages['folder'])) {
            $language = api_get_setting('platformLanguage');
        }

        $change_active = 0;
        $isUserActive = $user->getActive();
        if ($isUserActive != $active) {
            $change_active = 1;
        }

        $originalUsername = $user->getUsername();

        // If username is different from original then check if it exists.
        if ($originalUsername !== $username) {
            $available = self::is_username_available($username);
            if ($available === false) {
                return false;
            }
        }

        if (!empty($expiration_date)) {
            $expiration_date = api_get_utc_datetime($expiration_date);
            $expiration_date = new \DateTime(
                $expiration_date,
                new DateTimeZone('UTC')
            );
        }

        $user
            ->setLastname($lastname)
            ->setFirstname($firstname)
            ->setUsername($username)
            ->setStatus($status)
            ->setAuthSource($auth_source)
            ->setLanguage($language)
            ->setEmail($email)
            ->setOfficialCode($official_code)
            ->setPhone($phone)
            ->setAddress($address)
            ->setPictureUri($picture_uri)
            ->setExpirationDate($expiration_date)
            ->setActive($active)
            ->setEnabled($active)
            ->setHrDeptId($hr_dept_id)
        ;

        if (!is_null($password)) {
            $user->setPlainPassword($password);
            Event::addEvent(LOG_USER_PASSWORD_UPDATE, LOG_USER_ID, $user_id);
        }

        $userManager->updateUser($user, true);
        Event::addEvent(LOG_USER_UPDATE, LOG_USER_ID, $user_id);

        if ($change_active == 1) {
            if ($active == 1) {
                $event_title = LOG_USER_ENABLE;
            } else {
                $event_title = LOG_USER_DISABLE;
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
            $emailsubject = '['.api_get_setting('siteName').'] '.get_lang('YourReg').' '.api_get_setting('siteName');
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
                if ($access_url_id != -1) {
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
            // Adding this variable but not used in default template, used for task BT19518 with a customized template
            $tplContent->assign('status_type', $status);

            $layoutContent = $tplContent->get_template('mail/user_edit_content.tpl');
            $emailBody = $tplContent->fetch($layoutContent);

            $mailTemplateManager = new MailTemplateManager();

            if (!empty($emailTemplate) &&
                isset($emailTemplate['user_edit_content.tpl']) &&
                !empty($emailTemplate['user_edit_content.tpl'])
            ) {
                $userInfo = api_get_user_info($user_id);
                $emailBody = $mailTemplateManager->parseTemplate($emailTemplate['user_edit_content.tpl'], $userInfo);
            }

            $creatorInfo = api_get_user_info($creator_id);
            $creatorEmail = isset($creatorInfo['email']) ? $creatorInfo['email'] : '';

            api_mail_html(
                $recipient_name,
                $email,
                $emailsubject,
                $emailBody,
                $sender_name,
                $email_admin,
                null,
                null,
                null,
                null,
                $creatorEmail
            );
        }

        if (!empty($hook)) {
            $hook->setEventData(['user' => $user]);
            $hook->notifyUpdateUser(HOOK_EVENT_TYPE_POST);
        }

        $cacheAvailable = api_get_configuration_value('apc');
        if ($cacheAvailable === true) {
            $apcVar = api_get_configuration_value('apc_prefix').'userinfo_'.$user_id;
            if (apcu_exists($apcVar)) {
                apcu_delete($apcVar);
            }
        }

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
                    value = '$original_user_id_value' AND
                    extra_field_type = $extraFieldType
                ";
        $res = Database::query($sql);
        $row = Database::fetch_object($res);
        if ($row) {
            return $row->user_id;
        }

        return 0;
    }

    /**
     * Check if a username is available.
     *
     * @param string $username the wanted username
     *
     * @return bool true if the wanted username is available
     * @assert ('') === false
     * @assert ('xyzxyzxyz') === true
     */
    public static function is_username_available($username)
    {
        if (empty($username)) {
            return false;
        }
        $table_user = Database::get_main_table(TABLE_MAIN_USER);
        $sql = "SELECT username FROM $table_user
                WHERE username = '".Database::escape_string($username)."'";
        $res = Database::query($sql);

        return Database::num_rows($res) == 0;
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
        if ($pos !== false) {
            $lastname = api_substr($lastname, 0, $pos);
        }

        $lastname = preg_replace(USERNAME_PURIFIER, '', $lastname);
        $username = $firstname.$lastname;
        if (empty($username)) {
            $username = 'user';
        }

        $username = URLify::transliterate($username);

        return strtolower(substr($username, 0, USERNAME_MAX_LENGTH - 3));
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
        if (!self::is_username_available($username)) {
            $i = 2;
            $temp_username = substr($username, 0, USERNAME_MAX_LENGTH - strlen((string) $i)).$i;
            while (!self::is_username_available($temp_username)) {
                $i++;
                $temp_username = substr($username, 0, USERNAME_MAX_LENGTH - strlen((string) $i)).$i;
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
            if ('true' === api_get_setting('login_is_email')) {
                $return = substr(preg_replace(USERNAME_PURIFIER_MAIL, '', $username), 0, USERNAME_MAX_LENGTH);
            } else {
                $return = substr(preg_replace(USERNAME_PURIFIER, '', $username), 0, USERNAME_MAX_LENGTH);
            }

            return URLify::transliterate($return);
        }

        // 1. Applying the shallow purifier.
        // 2. Length limitation.
        return substr(
            preg_replace(USERNAME_PURIFIER_SHALLOW, '', $username),
            0,
            USERNAME_MAX_LENGTH
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

        if ($resultData === false) {
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
        return strlen(self::purify_username($username, false)) == 0;
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
        return strlen($username) > USERNAME_MAX_LENGTH;
    }

    /**
     * Get the users by ID.
     *
     * @param array  $ids    student ids
     * @param string $active
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
        $idCampus = null,
        $keyword = null,
        $lastConnectionDate = null,
        $getCount = false,
        $filterUsers = null
    ) {
        $user_table = Database::get_main_table(TABLE_MAIN_USER);
        $userUrlTable = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        $return_array = [];

        if ($getCount) {
            $sql = "SELECT count(user.id) as nbUsers FROM $user_table user ";
        } else {
            $sql = "SELECT user.* FROM $user_table user ";
        }

        if (api_is_multiple_url_enabled()) {
            if ($idCampus) {
                $urlId = $idCampus;
            } else {
                $urlId = api_get_current_access_url_id();
            }
            $sql .= " INNER JOIN $userUrlTable url_user
                      ON (user.user_id = url_user.user_id)
                      WHERE url_user.access_url_id = $urlId";
        } else {
            $sql .= " WHERE 1=1 ";
        }

        if (!empty($keyword)) {
            $keyword = trim(Database::escape_string($keyword));
            $keywordParts = array_filter(explode(' ', $keyword));
            $extraKeyword = '';
            if (!empty($keywordParts)) {
                $keywordPartsFixed = Database::escape_string(implode('%', $keywordParts));
                if (!empty($keywordPartsFixed)) {
                    $extraKeyword .= " OR
                        CONCAT(user.firstname, ' ', user.lastname) LIKE '%$keywordPartsFixed%' OR
                        CONCAT(user.lastname, ' ', user.firstname) LIKE '%$keywordPartsFixed%' ";
                }
            }

            $sql .= " AND (
                user.username LIKE '%$keyword%' OR
                user.firstname LIKE '%$keyword%' OR
                user.lastname LIKE '%$keyword%' OR
                user.official_code LIKE '%$keyword%' OR
                user.email LIKE '%$keyword%' OR
                CONCAT(user.firstname, ' ', user.lastname) LIKE '%$keyword%' OR
                CONCAT(user.lastname, ' ', user.firstname) LIKE '%$keyword%'
                $extraKeyword
            )";
        }

        if (!empty($lastConnectionDate)) {
            $lastConnectionDate = Database::escape_string($lastConnectionDate);
            $sql .= " AND user.last_login <= '$lastConnectionDate' ";
        }

        if (count($conditions) > 0) {
            foreach ($conditions as $field => $value) {
                $field = Database::escape_string($field);
                $value = Database::escape_string($value);
                $sql .= " AND $field = '$value'";
            }
        }

        if (!empty($filterUsers)) {
            $sql .= " AND user.id IN(".implode(',', $filterUsers).")";
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

        if ($getCount) {
            $result = Database::fetch_array($sql_result);

            return $result['nbUsers'];
        }

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
        $sql = "SELECT user.* FROM $user_table user ";

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
                      ON (user.user_id = url_user.user_id)
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
        $onlyThisUserList = []
    ) {
        $user_table = Database::get_main_table(TABLE_MAIN_USER);
        $tblAccessUrlRelUser = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        $return_array = [];
        $sql_query = "SELECT user.id FROM $user_table user ";

        if (api_is_multiple_url_enabled()) {
            $sql_query .= " INNER JOIN $tblAccessUrlRelUser auru ON auru.user_id = user.id ";
        }

        $sql_query .= ' WHERE 1 = 1 ';
        if (count($conditions) > 0) {
            $temp_conditions = [];
            foreach ($conditions as $field => $value) {
                $field = Database::escape_string($field);
                $value = Database::escape_string($value);
                if ($simple_like) {
                    $temp_conditions[] = $field." LIKE '$value%'";
                } else {
                    $temp_conditions[] = $field.' LIKE \'%'.$value.'%\'';
                }
            }
            if (!empty($temp_conditions)) {
                $sql_query .= ' AND '.implode(' '.$condition.' ', $temp_conditions);
            }

            if (api_is_multiple_url_enabled()) {
                $sql_query .= ' AND auru.access_url_id = '.api_get_current_access_url_id();
            }
        } else {
            if (api_is_multiple_url_enabled()) {
                $sql_query .= ' AND auru.access_url_id = '.api_get_current_access_url_id();
            }
        }

        if (!empty($onlyThisUserList)) {
            $onlyThisUserListToString = implode("','", $onlyThisUserList);
            $sql_query .= " AND user.id IN ('$onlyThisUserListToString') ";
        }

        if (count($order_by) > 0) {
            $sql_query .= ' ORDER BY '.Database::escape_string(implode(',', $order_by));
        }

        $sql_result = Database::query($sql_query);
        while ($result = Database::fetch_array($sql_result)) {
            $userInfo = api_get_user_info($result['id']);
            $return_array[] = $userInfo;
        }

        return $return_array;
    }

    /**
     * Get user picture URL or path from user ID (returns an array).
     * The return format is a complete path, enabling recovery of the directory
     * with dirname() or the file with basename(). This also works for the
     * functions dealing with the user's productions, as they are located in
     * the same directory.
     *
     * @param int    $id       User ID
     * @param string $type     Type of path to return (can be 'system', 'web')
     * @param array  $userInfo user information to avoid query the DB
     *                         returns the /main/img/unknown.jpg image set it at true
     *
     * @return array Array of 2 elements: 'dir' and 'file' which contain
     *               the dir and file as the name implies if image does not exist it will
     *               return the unknow image if anonymous parameter is true if not it returns an empty array
     */
    public static function get_user_picture_path_by_id(
        $id,
        $type = 'web',
        $userInfo = []
    ) {
        switch ($type) {
            case 'system': // Base: absolute system path.
                $base = api_get_path(SYS_CODE_PATH);
                break;
            case 'web': // Base: absolute web path.
            default:
                $base = api_get_path(WEB_CODE_PATH);
                break;
        }

        $anonymousPath = [
            'dir' => $base.'img/',
            'file' => 'unknown.jpg',
            'email' => '',
        ];

        if (empty($id) || empty($type)) {
            return $anonymousPath;
        }

        $id = (int) $id;
        if (empty($userInfo)) {
            $user_table = Database::get_main_table(TABLE_MAIN_USER);
            $sql = "SELECT email, picture_uri FROM $user_table
                    WHERE id = ".$id;
            $res = Database::query($sql);

            if (!Database::num_rows($res)) {
                return $anonymousPath;
            }
            $user = Database::fetch_array($res);
            if (empty($user['picture_uri'])) {
                return $anonymousPath;
            }
        } else {
            $user = $userInfo;
        }

        $pictureFilename = trim($user['picture_uri']);

        $dir = self::getUserPathById($id, $type);

        return [
            'dir' => $dir,
            'file' => $pictureFilename,
            'email' => $user['email'],
        ];
    }

    /**
     * *** READ BEFORE REVIEW THIS FUNCTION ***
     * This function is a exact copy from get_user_picture_path_by_id() and it was create it to avoid
     * a recursive calls for get_user_picture_path_by_id() in another functions when you update a user picture
     * in same script, so you can find this function usage in update_user_picture() function.
     *
     * @param int    $id       User ID
     * @param string $type     Type of path to return (can be 'system', 'web')
     * @param array  $userInfo user information to avoid query the DB
     *                         returns the /main/img/unknown.jpg image set it at true
     *
     * @return array Array of 2 elements: 'dir' and 'file' which contain
     *               the dir and file as the name implies if image does not exist it will
     *               return the unknown image if anonymous parameter is true if not it returns an empty array
     */
    public static function getUserPicturePathById($id, $type = 'web', $userInfo = [])
    {
        switch ($type) {
            case 'system': // Base: absolute system path.
                $base = api_get_path(SYS_CODE_PATH);
                break;
            case 'web': // Base: absolute web path.
            default:
                $base = api_get_path(WEB_CODE_PATH);
                break;
        }

        $anonymousPath = [
            'dir' => $base.'img/',
            'file' => 'unknown.jpg',
            'email' => '',
        ];

        if (empty($id) || empty($type)) {
            return $anonymousPath;
        }

        $id = (int) $id;
        if (empty($userInfo)) {
            $user_table = Database::get_main_table(TABLE_MAIN_USER);
            $sql = "SELECT email, picture_uri FROM $user_table WHERE id = $id";
            $res = Database::query($sql);

            if (!Database::num_rows($res)) {
                return $anonymousPath;
            }
            $user = Database::fetch_array($res);

            if (empty($user['picture_uri'])) {
                return $anonymousPath;
            }
        } else {
            $user = $userInfo;
        }

        $pictureFilename = trim($user['picture_uri']);
        $dir = self::getUserPathById($id, $type);

        return [
            'dir' => $dir,
            'file' => $pictureFilename,
            'email' => $user['email'],
        ];
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

            $rootPath = api_get_path(SYS_UPLOAD_PATH).'users/'.substr((string) $id, 0, 1);
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
                $userPath = api_get_path(SYS_UPLOAD_PATH).$userPath;
                break;
            case 'web': // Base: absolute web path.
                $userPath = api_get_path(WEB_UPLOAD_PATH).$userPath;
                break;
            case 'last': // Only the last part starting with users/
                break;
        }

        return $userPath;
    }

    /**
     * Gets the current user image.
     *
     * @param string $user_id
     * @param int    $size        it can be USER_IMAGE_SIZE_SMALL,
     *                            USER_IMAGE_SIZE_MEDIUM, USER_IMAGE_SIZE_BIG or  USER_IMAGE_SIZE_ORIGINAL
     * @param bool   $addRandomId
     * @param array  $userInfo    to avoid query the DB
     *
     * @return string
     */
    public static function getUserPicture(
        $user_id,
        $size = USER_IMAGE_SIZE_MEDIUM,
        $addRandomId = true,
        $userInfo = []
    ) {
        // Make sure userInfo is defined. Otherwise, define it!
        if (empty($userInfo) || !is_array($userInfo) || count($userInfo) == 0) {
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
        if ($pictureWebFile == 'unknown.jpg' || empty($pictureWebFile)) {
            if ($gravatarEnabled === 'true') {
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

        $pictureSysPath = self::get_user_picture_path_by_id($user_id, 'system');
        $file = $pictureSysPath['dir'].$realSizeName.$pictureWebFile;
        $picture = '';
        if (file_exists($file)) {
            $picture = $pictureWebDir.$realSizeName.$pictureWebFile;
        } else {
            $file = $pictureSysPath['dir'].$pictureWebFile;
            if (file_exists($file) && !is_dir($file)) {
                $picture = $pictureWebFile['dir'].$pictureWebFile;
            }
        }

        if (empty($picture)) {
            return $anonymousPath;
        }

        if ($addRandomId) {
            $picture .= '?rand='.uniqid();
        }

        return $picture;
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
    public static function update_user_picture(
        $user_id,
        $file = null,
        $source_file = null,
        $cropParameters = ''
    ) {
        if (empty($user_id)) {
            return false;
        }
        $delete = empty($file);
        if (empty($source_file)) {
            $source_file = $file;
        }

        // User-reserved directory where photos have to be placed.
        $path_info = self::getUserPicturePathById($user_id, 'system');
        $path = $path_info['dir'];

        // If this directory does not exist - we create it.
        if (!file_exists($path)) {
            mkdir($path, api_get_permissions_for_new_directories(), true);
        }

        // The old photos (if any).
        $old_file = $path_info['file'];

        // Let us delete them.
        if ($old_file != 'unknown.jpg') {
            if (KEEP_THE_OLD_IMAGE_AFTER_CHANGE) {
                $prefix = 'saved_'.date('Y_m_d_H_i_s').'_'.uniqid('').'_';
                @rename($path.'small_'.$old_file, $path.$prefix.'small_'.$old_file);
                @rename($path.'medium_'.$old_file, $path.$prefix.'medium_'.$old_file);
                @rename($path.'big_'.$old_file, $path.$prefix.'big_'.$old_file);
                @rename($path.$old_file, $path.$prefix.$old_file);
            } else {
                @unlink($path.'small_'.$old_file);
                @unlink($path.'medium_'.$old_file);
                @unlink($path.'big_'.$old_file);
                @unlink($path.$old_file);
            }
        }

        // Exit if only deletion has been requested. Return an empty picture name.
        if ($delete) {
            return '';
        }

        // Validation 2.
        $allowed_types = api_get_supported_image_extensions();
        $file = str_replace('\\', '/', $file);
        $filename = (($pos = strrpos($file, '/')) !== false) ? substr($file, $pos + 1) : $file;
        $extension = strtolower(substr(strrchr($filename, '.'), 1));
        if (!in_array($extension, $allowed_types)) {
            return false;
        }

        // This is the common name for the new photos.
        if (KEEP_THE_NAME_WHEN_CHANGE_IMAGE && $old_file != 'unknown.jpg') {
            $old_extension = strtolower(substr(strrchr($old_file, '.'), 1));
            $filename = in_array($old_extension, $allowed_types) ? substr($old_file, 0, -strlen($old_extension)) : $old_file;
            $filename = (substr($filename, -1) == '.') ? $filename.$extension : $filename.'.'.$extension;
        } else {
            $filename = api_replace_dangerous_char($filename);
            if (PREFIX_IMAGE_FILENAME_WITH_UID) {
                $filename = uniqid('').'_'.$filename;
            }
            // We always prefix user photos with user ids, so on setting
            // api_get_setting('split_users_upload_directory') === 'true'
            // the correspondent directories to be found successfully.
            $filename = $user_id.'_'.$filename;
        }

        if (!file_exists($source_file)) {
            return false;
        }

        $mimeContentType = mime_content_type($source_file);
        if (false === strpos($mimeContentType, 'image')) {
            return false;
        }

        //Crop the image to adjust 1:1 ratio
        $image = new Image($source_file);
        $image->crop($cropParameters);

        // Storing the new photos in 4 versions with various sizes.
        $userPath = self::getUserPathById($user_id, 'system');

        // If this path does not exist - we create it.
        if (!file_exists($userPath)) {
            mkdir($userPath, api_get_permissions_for_new_directories(), true);
        }
        $small = new Image($source_file);
        $small->resize(32);
        $small->send_image($userPath.'small_'.$filename);
        $medium = new Image($source_file);
        $medium->resize(85);
        $medium->send_image($userPath.'medium_'.$filename);
        $normal = new Image($source_file);
        $normal->resize(200);
        $normal->send_image($userPath.$filename);

        $big = new Image($source_file); // This is the original picture.
        $big->send_image($userPath.'big_'.$filename);

        $result = $small && $medium && $normal && $big;

        return $result ? $filename : false;
    }

    /**
     * Update User extra field file type into {user_folder}/{$extra_field}.
     *
     * @param int    $user_id     The user internal identification number
     * @param string $extra_field The $extra_field The extra field name
     * @param null   $file        The filename
     * @param null   $source_file The temporal filename
     *
     * @return bool|null return filename if success, but false
     */
    public static function update_user_extra_file(
        $user_id,
        $extra_field = '',
        $file = null,
        $source_file = null
    ) {
        // Add Filter
        $source_file = Security::filter_filename($source_file);
        $file = Security::filter_filename($file);

        if (empty($user_id)) {
            return false;
        }

        if (empty($source_file)) {
            $source_file = $file;
        }

        // User-reserved directory where extra file have to be placed.
        $path_info = self::get_user_picture_path_by_id($user_id, 'system');
        $path = $path_info['dir'];
        if (!empty($extra_field)) {
            $path .= $extra_field.'/';
        }
        // If this directory does not exist - we create it.
        if (!file_exists($path)) {
            @mkdir($path, api_get_permissions_for_new_directories(), true);
        }

        if (filter_extension($file)) {
            if (@move_uploaded_file($source_file, $path.$file)) {
                if ($extra_field) {
                    return $extra_field.'/'.$file;
                } else {
                    return $file;
                }
            }
        }

        return false; // this should be returned if anything went wrong with the upload
    }

    /**
     * Deletes user photos.
     * Note: This method relies on configuration setting from main/inc/conf/profile.conf.php.
     *
     * @param int $userId the user internal identification number
     *
     * @return mixed returns empty string on success, FALSE on error
     */
    public static function deleteUserPicture($userId)
    {
        return self::update_user_picture($userId);
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
        $production_repository = self::getUserPathById($user_id, 'system');
        $productions = [];

        if (is_dir($production_repository)) {
            $handle = opendir($production_repository);
            while ($file = readdir($handle)) {
                if ($file == '.' ||
                    $file == '..' ||
                    $file == '.htaccess' ||
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
        $production_path = self::get_user_picture_path_by_id($user_id, 'system');
        $production_file = $production_path['dir'].$production;
        if (is_file($production_file)) {
            unlink($production_file);

            return true;
        }

        return false;
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
            'value' => $value,
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
            'field_type',
            'display_text',
            'default_value',
            'field_order',
            'filter',
        ];
        $column = (int) $column;
        $sort_direction = '';
        if (!empty($direction)) {
            if (in_array(strtoupper($direction), ['ASC', 'DESC'])) {
                $sort_direction = strtoupper($direction);
            }
        }
        $extraFieldType = EntityExtraField::USER_FIELD_TYPE;
        $sqlf = "SELECT * FROM $t_uf WHERE extra_field_type = $extraFieldType ";
        if (!$all_visibility) {
            $sqlf .= " AND visible_to_self = 1 ";
        }
        if (!is_null($field_filter)) {
            $field_filter = (int) $field_filter;
            $sqlf .= " AND filter = $field_filter ";
        }
        $sqlf .= " ORDER BY `".$columns[$column]."` $sort_direction ";
        if ($number_of_items != 0) {
            $sqlf .= " LIMIT ".intval($from).','.intval($number_of_items);
        }
        $resf = Database::query($sqlf);
        if (Database::num_rows($resf) > 0) {
            while ($rowf = Database::fetch_array($resf)) {
                $fields[$rowf['id']] = [
                    0 => $rowf['id'],
                    1 => $rowf['variable'],
                    2 => $rowf['field_type'],
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
     * Build a list of extra file already uploaded in $user_folder/{$extra_field}/.
     *
     * @param $user_id
     * @param $extra_field
     * @param bool $force
     * @param bool $showDelete
     *
     * @return bool|string
     */
    public static function build_user_extra_file_list(
        $user_id,
        $extra_field,
        $force = false,
        $showDelete = false
    ) {
        if (!$force && !empty($_POST['remove_'.$extra_field])) {
            return true; // postpone reading from the filesystem
        }

        $extra_files = self::get_user_extra_files($user_id, $extra_field);
        if (empty($extra_files)) {
            return false;
        }

        $path_info = self::get_user_picture_path_by_id($user_id, 'web');
        $path = $path_info['dir'];
        $del_image = Display::returnIconPath('delete.png');

        $del_text = get_lang('Delete');
        $extra_file_list = '';
        if (count($extra_files) > 0) {
            $extra_file_list = '<div class="files-production"><ul id="productions">';
            foreach ($extra_files as $file) {
                $filename = substr($file, strlen($extra_field) + 1);
                $extra_file_list .= '<li>'.Display::return_icon('archive.png').
                    '<a href="'.$path.$extra_field.'/'.urlencode($filename).'" target="_blank">
                        '.htmlentities($filename).
                    '</a> ';
                if ($showDelete) {
                    $extra_file_list .= '<input
                        style="width:16px;"
                        type="image"
                        name="remove_extra_'.$extra_field.'['.urlencode($file).']"
                        src="'.$del_image.'"
                        alt="'.$del_text.'"
                        title="'.$del_text.' '.htmlentities($filename).'"
                        onclick="javascript: return confirmation(\''.htmlentities($filename).'\');" /></li>';
                }
            }
            $extra_file_list .= '</ul></div>';
        }

        return $extra_file_list;
    }

    /**
     * Get valid filenames in $user_folder/{$extra_field}/.
     *
     * @param $user_id
     * @param $extra_field
     * @param bool $full_path
     *
     * @return array
     */
    public static function get_user_extra_files($user_id, $extra_field, $full_path = false)
    {
        if (!$full_path) {
            // Nothing to do
        } else {
            $path_info = self::get_user_picture_path_by_id($user_id, 'system');
            $path = $path_info['dir'];
        }
        $extra_data = self::get_extra_user_data_by_field($user_id, $extra_field);
        $extra_files = $extra_data[$extra_field];

        $files = [];
        if (is_array($extra_files)) {
            foreach ($extra_files as $key => $value) {
                if (!$full_path) {
                    // Relative path from user folder
                    $files[] = $value;
                } else {
                    $files[] = $path.$value;
                }
            }
        } elseif (!empty($extra_files)) {
            if (!$full_path) {
                // Relative path from user folder
                $files[] = $extra_files;
            } else {
                $files[] = $path.$extra_files;
            }
        }

        return $files; // can be an empty array
    }

    /**
     * Remove an {$extra_file} from the user folder $user_folder/{$extra_field}/.
     *
     * @param int    $user_id
     * @param string $extra_field
     * @param string $extra_file
     *
     * @return bool
     */
    public static function remove_user_extra_file($user_id, $extra_field, $extra_file)
    {
        $extra_file = Security::filter_filename($extra_file);
        $path_info = self::get_user_picture_path_by_id($user_id, 'system');
        if (strpos($extra_file, $extra_field) !== false) {
            $path_extra_file = $path_info['dir'].$extra_file;
        } else {
            $path_extra_file = $path_info['dir'].$extra_field.'/'.$extra_file;
        }
        if (is_file($path_extra_file)) {
            unlink($path_extra_file);

            return true;
        }

        return false;
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
        $fieldType,
        $displayText,
        $default
    ) {
        $extraField = new ExtraField('user');
        $params = [
            'variable' => $variable,
            'field_type' => $fieldType,
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
     * @param    mixed   Whether to filter on the value of filter
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
        $user_id = (int) $user_id;
        $sql = "SELECT f.id as id, f.variable as fvar, f.field_type as type
                FROM $t_uf f
                WHERE
                    extra_field_type = ".EntityExtraField::USER_FIELD_TYPE."
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
                if ($row['type'] == self::USER_FIELD_TYPE_TAG) {
                    $tags = self::get_user_tags_to_string($user_id, $row['id'], false);
                    $extra_data['extra_'.$row['fvar']] = $tags;
                } else {
                    $sqlu = "SELECT value as fval
                            FROM $t_ufv
                            WHERE field_id = ".$row['id']." AND item_id = ".$user_id;
                    $resu = Database::query($sqlu);

                    if (Database::num_rows($resu) > 0) {
                        $rowu = Database::fetch_array($resu);
                        $fval = $rowu['fval'];
                        if ($row['type'] == self::USER_FIELD_TYPE_SELECT_MULTIPLE) {
                            $fval = explode(';', $rowu['fval']);
                        }
                    } else {
                        // get default value
                        $sql_df = "SELECT default_value as fval_df FROM $t_uf
                               WHERE id = ".$row['id'];
                        $res_df = Database::query($sql_df);
                        $row_df = Database::fetch_array($res_df);
                        $fval = $row_df['fval_df'];
                    }
                    // We get here (and fill the $extra_data array) even if there
                    // is no user with data (we fill it with default values)
                    if ($prefix) {
                        if ($row['type'] == self::USER_FIELD_TYPE_RADIO) {
                            $extra_data['extra_'.$row['fvar']]['extra_'.$row['fvar']] = $fval;
                        } else {
                            $extra_data['extra_'.$row['fvar']] = $fval;
                        }
                    } else {
                        if ($row['type'] == self::USER_FIELD_TYPE_RADIO) {
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

    /**
     * Get extra user data by field.
     *
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

        $sql = "SELECT f.id as id, f.variable as fvar, f.field_type as type
                FROM $t_uf f
                WHERE f.variable = '$field_variable' ";

        if (!$all_visibility) {
            $sql .= " AND f.visible_to_self = 1 ";
        }

        $sql .= " AND extra_field_type = ".EntityExtraField::USER_FIELD_TYPE;
        $sql .= " ORDER BY f.field_order ";

        $res = Database::query($sql);
        if (Database::num_rows($res) > 0) {
            while ($row = Database::fetch_array($res)) {
                $sqlu = "SELECT value as fval FROM $t_ufv v
                         INNER JOIN $t_uf f
                         ON (v.field_id = f.id)
                         WHERE
                            extra_field_type = ".EntityExtraField::USER_FIELD_TYPE." AND
                            field_id = ".$row['id']." AND
                            item_id = ".$user_id;
                $resu = Database::query($sqlu);
                $fval = '';
                if (Database::num_rows($resu) > 0) {
                    $rowu = Database::fetch_array($resu);
                    $fval = $rowu['fval'];
                    if ($row['type'] == self::USER_FIELD_TYPE_SELECT_MULTIPLE) {
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
     * @param string $variable The name of the field we want to know everything about
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
     * @param string $type
     *
     * @return array
     */
    public static function get_all_extra_field_by_type($type)
    {
        $extraField = new ExtraField('user');

        return $extraField->get_all_extra_field_by_type($type);
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
     * @param string $variable the internal variable name of the field
     * @param string $value    the internal value of the field
     * @param bool   $useLike
     *
     * @return array with extra data info of a user i.e array('field_variable'=>'value');
     */
    public static function get_extra_user_data_by_value($variable, $value, $useLike = false)
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
            true,
            $useLike
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

        $allowOrder = api_get_configuration_value('session_list_order');
        $position = '';
        if ($allowOrder) {
            $position = ', s.position AS position ';
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
                s.name,
                s.accessStartDate AS access_start_date,
                s.accessEndDate AS access_end_date,
                s.duration,
                sc.id AS session_category_id,
                sc.name AS session_category_name,
                sc.dateStart AS session_category_date_start,
                sc.dateEnd AS session_category_date_end,
                s.coachAccessStartDate AS coach_access_start_date,
                s.coachAccessEndDate AS coach_access_end_date,
                CASE WHEN s.accessEndDate IS NULL THEN 1 ELSE 0 END HIDDEN _isFieldNull
                $position
            ";
        }

        $dql = "SELECT $dqlSelect
                FROM ChamiloCoreBundle:Session AS s
                LEFT JOIN ChamiloCoreBundle:SessionRelCourseRelUser AS scu WITH scu.session = s
                INNER JOIN ChamiloCoreBundle:AccessUrlRelSession AS url WITH url.sessionId = s.id
                LEFT JOIN ChamiloCoreBundle:SessionCategory AS sc WITH s.category = sc ";

        // A single OR operation on scu.user = :user OR s.generalCoach = :user
        // is awfully inefficient for large sets of data (1m25s for 58K
        // sessions, BT#14115) but executing a similar query twice and grouping
        // the results afterwards in PHP takes about 1/1000th of the time
        // (0.1s + 0.0s) for the same set of data, so we do it this way...
        $dqlStudent = $dql.' WHERE scu.user = :user AND url.accessUrlId = :url ';
        $dqlCoach = $dql.' WHERE s.generalCoach = :user AND url.accessUrlId = :url ';

        // Default order
        $order = 'ORDER BY sc.name, s.name';

        // Order by date if showing all sessions
        $showAllSessions = api_get_configuration_value('show_all_sessions_on_my_course_page') === true;
        if ($showAllSessions) {
            $order = 'ORDER BY s.accessStartDate';
        }

        // Order by position
        if ($allowOrder) {
            $order = 'ORDER BY s.position';
        }

        // Order by dates according to settings
        $orderBySettings = api_get_configuration_value('my_courses_session_order');
        if (!empty($orderBySettings) && isset($orderBySettings['field']) && isset($orderBySettings['order'])) {
            $field = $orderBySettings['field'];
            $orderSetting = $orderBySettings['order'];
            switch ($field) {
                case 'start_date':
                    $order = " ORDER BY s.accessStartDate $orderSetting";
                    break;
                case 'end_date':
                    $order = " ORDER BY s.accessEndDate $orderSetting ";
                    if ($orderSetting === 'asc') {
                        // Put null values at the end
                        // https://stackoverflow.com/questions/12652034/how-can-i-order-by-null-in-dql
                        $order = ' ORDER BY _isFieldNull asc, s.accessEndDate asc';
                    }
                    break;
                case 'name':
                    $order = " ORDER BY s.name $orderSetting ";
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

        $collapsable = api_get_configuration_value('allow_user_session_collapsable');
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
                    $isGeneralCoach = SessionManager::user_is_general_coach($user_id, $row['id']);
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
                'name' => $row['session_category_name'],
                'date_start' => $categoryStart,
                'date_end' => $categoryEnd,
            ];

            $visibility = api_get_session_visibility(
                $session_id,
                null,
                $ignore_visibility_for_admins
            );

            if ($visibility != SESSION_VISIBLE) {
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
                    if ($courseIsVisible === false || $sessionCourseVisibility == SESSION_INVISIBLE) {
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
                    if ($ignore_visibility_for_admins === false) {
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
                'session_name' => $row['name'],
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
     * @param int $user_id
     * @param int $sessionLimit
     *
     * @return array list of statuses (session_id-course_code => status)
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

        $user_id = (int) $user_id;

        if (empty($user_id)) {
            return [];
        }

        // We filter the courses from the URL
        $join_access_url = $where_access_url = '';
        if (api_get_multiple_access_url()) {
            $access_url_id = api_get_current_access_url_id();
            if ($access_url_id != -1) {
                $tbl_url_course = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
                $join_access_url = "LEFT JOIN $tbl_url_course url_rel_course ON url_rel_course.c_id = course.id";
                $where_access_url = " AND access_url_id = $access_url_id ";
            }
        }

        // Courses in which we subscribed out of any session
        $tbl_user_course_category = Database::get_main_table(TABLE_USER_COURSE_CATEGORY);

        $sql = "SELECT
                    course.code,
                    course_rel_user.status course_rel_status,
                    course_rel_user.sort sort,
                    course_rel_user.user_course_cat user_course_cat
                 FROM $tbl_course_user course_rel_user
                 LEFT JOIN $tbl_course course
                 ON course.id = course_rel_user.c_id
                 LEFT JOIN $tbl_user_course_category user_course_category
                 ON course_rel_user.user_course_cat = user_course_category.id
                 $join_access_url
                 WHERE
                    course_rel_user.user_id = '".$user_id."' AND
                    course_rel_user.relation_type <> ".COURSE_RELATION_TYPE_RRHH."
                    $where_access_url
                 ORDER BY user_course_category.sort, course_rel_user.sort, course.title ASC";

        $course_list_sql_result = Database::query($sql);

        $personal_course_list = [];
        if (Database::num_rows($course_list_sql_result) > 0) {
            while ($result_row = Database::fetch_array($course_list_sql_result, 'ASSOC')) {
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
                    WHERE user_id = $user_id AND status = 2 ";

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

        $sql = "SELECT DISTINCT s.id, name, access_start_date, access_end_date
                FROM $tbl_session_user su INNER JOIN $tbl_session s
                ON (s.id = su.session_id)
                WHERE (
                    su.user_id = $user_id AND
                    su.relation_type <> ".SESSION_RELATION_TYPE_RRHH."
                )
                $coachCourseConditions
                ORDER BY access_start_date, access_end_date, name
                $sessionLimitRestriction
        ";

        $result = Database::query($sql);
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_assoc($result)) {
                $sessions[$row['id']] = $row;
            }
        }

        $sql = "SELECT DISTINCT
                id, name, access_start_date, access_end_date
                FROM $tbl_session s
                WHERE (
                    id_coach = $user_id
                )
                $coachCourseConditions
                ORDER BY access_start_date, access_end_date, name";

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

                if ($session_visibility == SESSION_INVISIBLE) {
                    continue;
                }

                // This query is horribly slow when more than a few thousand
                // users and just a few sessions to which they are subscribed
                $sql = "SELECT DISTINCT
                        course.code code,
                        course.title i,
                        ".(api_is_western_name_order() ? "CONCAT(user.firstname,' ',user.lastname)" : "CONCAT(user.lastname,' ',user.firstname)")." t,
                        email, course.course_language l,
                        1 sort,
                        category_code user_course_cat,
                        access_start_date,
                        access_end_date,
                        session.id as session_id,
                        session.name as session_name
                    FROM $tbl_session_course_user as session_course_user
                    INNER JOIN $tbl_course AS course
                        ON course.id = session_course_user.c_id
                    INNER JOIN $tbl_session as session
                        ON session.id = session_course_user.session_id
                    LEFT JOIN $tbl_user as user
                        ON user.id = session_course_user.user_id OR session.id_coach = user.id
                    WHERE
                        session_course_user.session_id = $session_id AND (
                            (session_course_user.user_id = $user_id AND session_course_user.status = 2)
                            OR session.id_coach = $user_id
                        )
                    ORDER BY i";
                $course_list_sql_result = Database::query($sql);
                while ($result_row = Database::fetch_array($course_list_sql_result, 'ASSOC')) {
                    $result_row['course_info'] = api_get_course_info($result_row['code']);
                    $key = $result_row['session_id'].' - '.$result_row['code'];
                    $personal_course_list[$key] = $result_row;
                }
            }
        }

        foreach ($sessions as $enreg) {
            $session_id = $enreg['id'];
            $session_visibility = api_get_session_visibility($session_id);
            if ($session_visibility == SESSION_INVISIBLE) {
                continue;
            }

            /* This query is very similar to the above query,
               but it will check the session_rel_course_user table if there are courses registered to our user or not */
            $sql = "SELECT DISTINCT
                course.code code,
                course.title i, CONCAT(user.lastname,' ',user.firstname) t,
                email,
                course.course_language l,
                1 sort,
                category_code user_course_cat,
                access_start_date,
                access_end_date,
                session.id as session_id,
                session.name as session_name,
                IF((session_course_user.user_id = 3 AND session_course_user.status=2),'2', '5')
            FROM $tbl_session_course_user as session_course_user
            INNER JOIN $tbl_course AS course
            ON course.id = session_course_user.c_id AND session_course_user.session_id = $session_id
            INNER JOIN $tbl_session as session
            ON session_course_user.session_id = session.id
            LEFT JOIN $tbl_user as user ON user.id = session_course_user.user_id
            WHERE session_course_user.user_id = $user_id
            ORDER BY i";

            $course_list_sql_result = Database::query($sql);
            while ($result_row = Database::fetch_array($course_list_sql_result, 'ASSOC')) {
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
        $tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
        $tableCourse = Database::get_main_table(TABLE_MAIN_COURSE);
        $tbl_session_course_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $tbl_session_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);

        $user_id = (int) $user_id;
        $session_id = (int) $session_id;
        // We filter the courses from the URL
        $join_access_url = $where_access_url = '';
        if (api_get_multiple_access_url()) {
            $urlId = api_get_current_access_url_id();
            if (-1 != $urlId) {
                $tbl_url_session = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);
                $join_access_url = " ,  $tbl_url_session url_rel_session ";
                $where_access_url = " AND access_url_id = $urlId AND url_rel_session.session_id = $session_id ";
            }
        }

        $exlearnerCondition = "";
        if (false !== api_get_configuration_value('user_edition_extra_field_to_check')) {
            $exlearnerCondition = " AND scu.status NOT IN(".COURSE_EXLEARNER.")";
        }

        /* This query is very similar to the query below, but it will check the
        session_rel_course_user table if there are courses registered
        to our user or not */
        $sql = "SELECT DISTINCT
                    c.title,
                    c.visibility,
                    c.id as real_id,
                    c.code as course_code,
                    c.course_language,
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
                    $exlearnerCondition
                ORDER BY sc.position ASC";

        $myCourseList = [];
        $courses = [];
        $result = Database::query($sql);
        if (Database::num_rows($result) > 0) {
            while ($result_row = Database::fetch_array($result, 'ASSOC')) {
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
            $sql = "SELECT DISTINCT
                        c.title,
                        c.visibility,
                        c.id as real_id,
                        c.code as course_code,
                        c.course_language,
                        sc.position,
                        c.unsubscribe
                    FROM $tbl_session_course_user as scu
                    INNER JOIN $tbl_session as s
                    ON (scu.session_id = s.id)
                    INNER JOIN $tbl_session_course sc
                    ON (scu.session_id = sc.session_id AND scu.c_id = sc.c_id)
                    INNER JOIN $tableCourse as c
                    ON (scu.c_id = c.id)
                    $join_access_url
                    WHERE
                      s.id = $session_id AND
                      (
                        (scu.user_id = $user_id AND scu.status = 2) OR
                        s.id_coach = $user_id
                      )
                    $where_access_url
                    $exlearnerCondition
                    ORDER BY sc.position ASC";
            $result = Database::query($sql);

            if (Database::num_rows($result) > 0) {
                while ($result_row = Database::fetch_array($result, 'ASSOC')) {
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
            $sessionInfo = api_get_session_info($session_id);
            if ($sessionInfo['id_coach'] == $user_id) {
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
                // then order the course list by course code.
                $orderByCode = array_column($myCourseList, 'course_code');
                sort($orderByCode, SORT_NATURAL);
                $newCourseList = [];
                foreach ($orderByCode as $code) {
                    foreach ($myCourseList as $course) {
                        if ($code === $course['course_code']) {
                            $newCourseList[] = $course;
                            break;
                        }
                    }
                }
                $myCourseList = $newCourseList;
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

        if ($res === false) {
            return false;
        }
        if (Database::num_rows($res) !== 1) {
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
    public static function get_user_upload_files_by_course(
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
                    if ($file == '.' || $file == '..' || $file == '.htaccess' || is_dir($path.$file)) {
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
                    if ($resourceType == 'all') {
                        $return .= '<li>
                            <a href="'.$web_path.urlencode($file).'" target="_blank">'.htmlentities($file).'</a></li>';
                    } elseif ($resourceType == 'images') {
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
    }

    /**
     * Gets the API key (or keys) and return them into an array.
     *
     * @param int     Optional user id (defaults to the result of api_get_user_id())
     * @param string $api_service
     *
     * @return mixed Non-indexed array containing the list of API keys for this user, or FALSE on error
     */
    public static function get_api_keys($user_id = null, $api_service = 'dokeos')
    {
        if ($user_id != strval(intval($user_id))) {
            return false;
        }
        if (empty($user_id)) {
            $user_id = api_get_user_id();
        }
        if ($user_id === false) {
            return false;
        }
        $service_name = Database::escape_string($api_service);
        if (is_string($service_name) === false) {
            return false;
        }
        $t_api = Database::get_main_table(TABLE_MAIN_USER_API_KEY);
        $sql = "SELECT * FROM $t_api WHERE user_id = $user_id AND api_service='$api_service';";
        $res = Database::query($sql);
        if ($res === false) {
            return false;
        } //error during query
        $num = Database::num_rows($res);
        if ($num == 0) {
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
    public static function add_api_key($user_id = null, $api_service = 'dokeos')
    {
        if ($user_id != strval(intval($user_id))) {
            return false;
        }
        if (empty($user_id)) {
            $user_id = api_get_user_id();
        }
        if ($user_id === false) {
            return false;
        }
        $service_name = Database::escape_string($api_service);
        if (is_string($service_name) === false) {
            return false;
        }
        $t_api = Database::get_main_table(TABLE_MAIN_USER_API_KEY);
        $md5 = md5((time() + ($user_id * 5)) - rand(10000, 10000)); //generate some kind of random key
        $sql = "INSERT INTO $t_api (user_id, api_key,api_service) VALUES ($user_id,'$md5','$service_name')";
        $res = Database::query($sql);
        if ($res === false) {
            return false;
        } //error during query
        $num = Database::insert_id();

        return $num == 0 ? false : $num;
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
        if ($key_id === false) {
            return false;
        }
        $t_api = Database::get_main_table(TABLE_MAIN_USER_API_KEY);
        $sql = "SELECT * FROM $t_api WHERE id = ".$key_id;
        $res = Database::query($sql);
        if ($res === false) {
            return false;
        } //error during query
        $num = Database::num_rows($res);
        if ($num !== 1) {
            return false;
        }
        $sql = "DELETE FROM $t_api WHERE id = ".$key_id;
        $res = Database::query($sql);
        if ($res === false) {
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
        if ($user_id === false) {
            return false;
        }
        $service_name = Database::escape_string($api_service);
        if (is_string($service_name) === false) {
            return false;
        }
        $t_api = Database::get_main_table(TABLE_MAIN_USER_API_KEY);
        $sql = "SELECT id FROM $t_api
                WHERE user_id=".$user_id." AND api_service='".$api_service."'";
        $res = Database::query($sql);
        $num = Database::num_rows($res);
        if ($num == 1) {
            $id_key = Database::fetch_array($res, 'ASSOC');
            self::delete_api_key($id_key['id']);
            $num = self::add_api_key($user_id, $api_service);
        } elseif ($num == 0) {
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
        if ($user_id === false) {
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
        $row = Database::fetch_array($res, 'ASSOC');

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

        return Database::num_rows($res) === 1;
    }

    /**
     * Get the total count of users.
     *
     * @param int|null $status        Status of users to be counted
     * @param int|null $access_url_id Access URL ID (optional)
     *
     * @return mixed Number of users or false on error
     */
    public static function get_number_of_users(
        int $status = null,
        int $access_url_id = null,
        int $active = null,
        string $dateFrom = null,
        string $dateUntil = null
    ) {
        $tableUser = Database::get_main_table(TABLE_MAIN_USER);
        $tableAccessUrlRelUser = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);

        if (empty($access_url_id)) {
            $access_url_id = api_get_current_access_url_id();
        }

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

        if (is_int($status) && $status > 0) {
            $status = (int) $status;
            $sql .= " AND u.status = $status ";
        }

        if (isset($active)) {
            $active = (int) $active;
            $sql .= " AND u.active = $active ";
        }

        if (!empty($dateFrom)) {
            $dateFrom = api_get_utc_datetime("$dateFrom 00:00:00");
            $sql .= " AND u.registration_date >= '$dateFrom' ";
        }

        if (!empty($dateUntil)) {
            $dateUntil = api_get_utc_datetime("$dateUntil 23:59:59");
            $sql .= " AND u.registration_date <= '$dateUntil' ";
        }

        $res = Database::query($sql);
        if (Database::num_rows($res) === 1) {
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
                WHERE field_id = $field_id AND tag LIKE '$tag%'
                ORDER BY tag
                LIMIT $limit";
        $result = Database::query($sql);
        $return = [];
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result, 'ASSOC')) {
                $return[] = ['id' => $row['tag'], 'text' => $row['tag']];
            }
        }
        if ($return_format === 'json') {
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
            while ($row = Database::fetch_array($result, 'ASSOC')) {
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
            while ($row = Database::fetch_array($result, 'ASSOC')) {
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
            while ($row = Database::fetch_array($result, 'ASSOC')) {
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
            $row = Database::fetch_array($result, 'ASSOC');

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
            $row = Database::fetch_array($result, 'ASSOC');

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
        if (is_numeric($tag)) {
            //the form is sending an id this means that the user select it from the list so it MUST exists
            /* $new_tag_id = self::get_tag_id_from_id($tag,$field_id);
              if ($new_tag_id !== false) {
              $sql = "UPDATE $table_user_tag SET count = count + 1 WHERE id  = $new_tag_id";
              $result = Database::query($sql);
              $last_insert_id = $new_tag_id;
              } else {
              $sql = "INSERT INTO $table_user_tag (tag, field_id,count) VALUES ('$tag','$field_id', count + 1)";
              $result = Database::query($sql);
              $last_insert_id = Database::insert_id();
              } */
        }

        //this is a new tag
        if ($tag_id == 0) {
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

        if (!empty($last_insert_id) && ($last_insert_id != 0)) {
            //we insert the relationship user-tag
            $sql = "SELECT tag_id FROM $table_user_tag_values
                    WHERE user_id = $user_id AND tag_id = $last_insert_id ";
            $result = Database::query($sql);
            //if the relationship does not exist we create it
            if (Database::num_rows($result) == 0) {
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
            $sql = "SELECT admin.user_id, username, firstname, lastname, email, active
                    FROM $tbl_url_rel_user as url
                    INNER JOIN $table_admin as admin
                    ON (admin.user_id=url.user_id)
                    INNER JOIN $table_user u
                    ON (u.id=admin.user_id)
                    WHERE access_url_id ='".$access_url_id."'";
        } else {
            $sql = "SELECT admin.user_id, username, firstname, lastname, email, active
                    FROM $table_admin as admin
                    INNER JOIN $table_user u
                    ON (u.id=admin.user_id)";
        }
        $result = Database::query($sql);
        $return = [];
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result, 'ASSOC')) {
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
        if ($field_id != 0) {
            $where_field = " field_id = $field_id AND ";
        }

        // all the information of the field
        if ($getCount) {
            $select = "SELECT count(DISTINCT u.id) count";
        } else {
            $select = "SELECT DISTINCT u.id, u.username, firstname, lastname, email, picture_uri";
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
                $row = Database::fetch_array($result, 'ASSOC');

                return $row['count'];
            }
            while ($row = Database::fetch_array($result, 'ASSOC')) {
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
                if ($extraField[8] == 1 && $extraField[2] == 4) {
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
                    if (isset($_GET[$varName]) && $_GET[$varName] != '0') {
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
            $form->addHeader(get_lang('Results').' "'.$query.'"');
        }

        $form->addText(
            'q',
            get_lang('UsersGroups'),
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
     * Shows the user menu.
     */
    public static function show_menu()
    {
        echo '<div class="actions">';
        echo '<a href="/main/auth/profile.php">'.
            Display::return_icon('profile.png').' '.get_lang('PersonalData').'</a>';
        echo '<a href="/main/messages/inbox.php">'.
            Display::return_icon('inbox.png').' '.get_lang('Inbox').'</a>';
        echo '<a href="/main/messages/outbox.php">'.
            Display::return_icon('outbox.png').' '.get_lang('Outbox').'</a>';
        echo '<span style="float:right; padding-top:7px;">'.
        '<a href="/main/auth/profile.php?show=1">'.
            Display::return_icon('edit.gif').' '.get_lang('Configuration').'</a>';
        echo '</span>';
        echo '</div>';
    }

    /**
     * Allow to register contact to social network.
     *
     * @param int $friend_id     user friend id
     * @param int $my_user_id    user id
     * @param int $relation_type relation between users see constants definition
     *
     * @return bool
     */
    public static function relate_users($friend_id, $my_user_id, $relation_type)
    {
        $tbl_my_friend = Database::get_main_table(TABLE_MAIN_USER_REL_USER);

        $friend_id = (int) $friend_id;
        $my_user_id = (int) $my_user_id;
        $relation_type = (int) $relation_type;

        $sql = 'SELECT COUNT(*) as count FROM '.$tbl_my_friend.'
                WHERE
                    friend_user_id='.$friend_id.' AND
                    user_id='.$my_user_id.' AND
                    relation_type NOT IN('.USER_RELATION_TYPE_RRHH.', '.USER_RELATION_TYPE_BOSS.') ';
        $result = Database::query($sql);
        $row = Database::fetch_array($result, 'ASSOC');
        $current_date = api_get_utc_datetime();

        if ($row['count'] == 0) {
            $sql = 'INSERT INTO '.$tbl_my_friend.'(friend_user_id,user_id,relation_type,last_edit)
                    VALUES ('.$friend_id.','.$my_user_id.','.$relation_type.',"'.$current_date.'")';
            Database::query($sql);

            return true;
        }

        $sql = 'SELECT COUNT(*) as count, relation_type  FROM '.$tbl_my_friend.'
                WHERE
                    friend_user_id='.$friend_id.' AND
                    user_id='.$my_user_id.' AND
                    relation_type NOT IN('.USER_RELATION_TYPE_RRHH.', '.USER_RELATION_TYPE_BOSS.') ';
        $result = Database::query($sql);
        $row = Database::fetch_array($result, 'ASSOC');

        if ($row['count'] == 1) {
            //only for the case of a RRHH or a Student BOSS
            if ($row['relation_type'] != $relation_type &&
                ($relation_type == USER_RELATION_TYPE_RRHH || $relation_type == USER_RELATION_TYPE_BOSS)
            ) {
                $sql = 'INSERT INTO '.$tbl_my_friend.'(friend_user_id,user_id,relation_type,last_edit)
                        VALUES ('.$friend_id.','.$my_user_id.','.$relation_type.',"'.$current_date.'")';
            } else {
                $sql = 'UPDATE '.$tbl_my_friend.' SET relation_type='.$relation_type.'
                        WHERE friend_user_id='.$friend_id.' AND user_id='.$my_user_id;
            }
            Database::query($sql);

            return true;
        }

        return false;
    }

    /**
     * Deletes a contact.
     *
     * @param bool   $friend_id
     * @param bool   $real_removed          true will delete ALL friends relationship
     * @param string $with_status_condition
     *
     * @author isaac flores paz <isaac.flores@dokeos.com>
     * @author Julio Montoya <gugli100@gmail.com> Cleaning code
     */
    public static function remove_user_rel_user(
        $friend_id,
        $real_removed = false,
        $with_status_condition = ''
    ) {
        $tbl_my_friend = Database::get_main_table(TABLE_MAIN_USER_REL_USER);
        $tbl_my_message = Database::get_main_table(TABLE_MESSAGE);
        $friend_id = (int) $friend_id;
        $user_id = api_get_user_id();

        if ($real_removed) {
            $extra_condition = '';
            if ($with_status_condition != '') {
                $extra_condition = ' AND relation_type = '.intval($with_status_condition);
            }
            $sql = 'DELETE FROM '.$tbl_my_friend.'
                    WHERE
                        relation_type <> '.USER_RELATION_TYPE_RRHH.' AND
                        friend_user_id='.$friend_id.' '.$extra_condition;
            Database::query($sql);
            $sql = 'DELETE FROM '.$tbl_my_friend.'
                   WHERE
                    relation_type <> '.USER_RELATION_TYPE_RRHH.' AND
                    user_id='.$friend_id.' '.$extra_condition;
            Database::query($sql);
        } else {
            $sql = 'SELECT COUNT(*) as count FROM '.$tbl_my_friend.'
                    WHERE
                        user_id='.$user_id.' AND
                        relation_type NOT IN('.USER_RELATION_TYPE_DELETED.', '.USER_RELATION_TYPE_RRHH.') AND
                        friend_user_id='.$friend_id;
            $result = Database::query($sql);
            $row = Database::fetch_array($result, 'ASSOC');
            if ($row['count'] == 1) {
                //Delete user rel user
                $sql_i = 'UPDATE '.$tbl_my_friend.' SET relation_type='.USER_RELATION_TYPE_DELETED.'
                          WHERE user_id='.$user_id.' AND friend_user_id='.$friend_id;

                $sql_j = 'UPDATE '.$tbl_my_message.' SET msg_status='.MESSAGE_STATUS_INVITATION_DENIED.'
                          WHERE
                                user_receiver_id='.$user_id.' AND
                                user_sender_id='.$friend_id.' AND update_date="0000-00-00 00:00:00" ';
                // Delete user
                $sql_ij = 'UPDATE '.$tbl_my_friend.'  SET relation_type='.USER_RELATION_TYPE_DELETED.'
                           WHERE user_id='.$friend_id.' AND friend_user_id='.$user_id;
                $sql_ji = 'UPDATE '.$tbl_my_message.' SET msg_status='.MESSAGE_STATUS_INVITATION_DENIED.'
                           WHERE
                                user_receiver_id='.$friend_id.' AND
                                user_sender_id='.$user_id.' AND
                                update_date="0000-00-00 00:00:00" ';
                Database::query($sql_i);
                Database::query($sql_j);
                Database::query($sql_ij);
                Database::query($sql_ji);
            }
        }

        // Delete accepted invitations
        $sql = "DELETE FROM $tbl_my_message
                WHERE
                    msg_status = ".MESSAGE_STATUS_INVITATION_ACCEPTED." AND
                    (
                        user_receiver_id = $user_id AND
                        user_sender_id = $friend_id
                    ) OR
                    (
                        user_sender_id = $user_id AND
                        user_receiver_id = $friend_id
                    )
        ";
        Database::query($sql);
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
                    relation_type = '".USER_RELATION_TYPE_RRHH."'
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
        $checkSessionVisibility = false,
        $filterUsers = null
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

        if (!empty($filterUsers)) {
            $userConditions .= " AND u.id IN(".implode(',', $filterUsers).")";
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
                    relation_type = '".USER_RELATION_TYPE_RRHH."'
                ";
                break;
            case COURSEMANAGER:
                $drhConditions .= " AND
                    friend_user_id = '$userId' AND
                    relation_type = '".USER_RELATION_TYPE_RRHH."'
                ";

                $sessionConditionsCoach .= " AND
                    (s.id_coach = '$userId')
                ";

                $sessionConditionsTeacher = " AND
                    (scu.status = 2 AND scu.user_id = '$userId')
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
                                    WHERE access_url_id = ".$urlId."
                                    $sessionConditionsCoach
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
                $drhConditions = " AND friend_user_id = $userId AND relation_type = ".USER_RELATION_TYPE_BOSS;
                break;
            case HRM_REQUEST:
                $drhConditions .= " AND
                    friend_user_id = '$userId' AND
                    relation_type = '".USER_RELATION_TYPE_HRM_REQUEST."'
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
        if ($getOnlyUserId == false) {
            if (api_is_western_name_order()) {
                $orderBy .= " ORDER BY firstname, lastname ";
            } else {
                $orderBy .= " ORDER BY lastname, firstname ";
            }

            if (!empty($column) && !empty($direction)) {
                // Fixing order due the UNIONs
                $column = str_replace('u.', '', $column);
                $column = trim($column);
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
     *
     * @return int
     */
    public static function subscribeUsersToHRManager(
        $hr_dept_id,
        $users_id,
        $deleteOtherAssignedUsers = true
    ) {
        return self::subscribeUsersToUser(
            $hr_dept_id,
            $users_id,
            USER_RELATION_TYPE_RRHH,
            false,
            $deleteOtherAssignedUsers
        );
    }

    /**
     * Register request to assign users to HRM.
     *
     * @param int   $hrmId   The HRM ID
     * @param array $usersId The users IDs
     *
     * @return int
     */
    public static function requestUsersToHRManager($hrmId, $usersId)
    {
        return self::subscribeUsersToUser(
            $hrmId,
            $usersId,
            USER_RELATION_TYPE_HRM_REQUEST,
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
            ->execute(['hrm_id' => $hrmId, 'relation_type' => USER_RELATION_TYPE_HRM_REQUEST, 'users_ids' => $users]);
    }

    /**
     * Add subscribed users to a user by relation type.
     *
     * @param int    $userId                   The user id
     * @param array  $subscribedUsersId        The id of subscribed users
     * @param string $relationType             The relation type
     * @param bool   $deleteUsersBeforeInsert
     * @param bool   $deleteOtherAssignedUsers
     *
     * @return int
     */
    public static function subscribeUsersToUser(
        $userId,
        $subscribedUsersId,
        $relationType,
        $deleteUsersBeforeInsert = false,
        $deleteOtherAssignedUsers = true
    ) {
        $userRelUserTable = Database::get_main_table(TABLE_MAIN_USER_REL_USER);
        $userRelAccessUrlTable = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);

        $userId = (int) $userId;
        $relationType = (int) $relationType;
        $affectedRows = 0;

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

        // Inserting new user list
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
                if ($num === 0) {
                    $date = api_get_utc_datetime();
                    $sql = "INSERT INTO $userRelUserTable (user_id, friend_user_id, relation_type, last_edit)
                            VALUES ($subscribedUserId, $userId, $relationType, '$date')";
                    $result = Database::query($sql);
                    $affectedRows += Database::affected_rows($result);
                }
            }
        }

        return $affectedRows;
    }

    /**
     * This function check if an user is followed by human resources manager.
     *
     * @param int $user_id
     * @param int $hr_dept_id Human resources manager
     *
     * @return bool
     */
    public static function is_user_followed_by_drh($user_id, $hr_dept_id)
    {
        $tbl_user_rel_user = Database::get_main_table(TABLE_MAIN_USER_REL_USER);
        $user_id = (int) $user_id;
        $hr_dept_id = (int) $hr_dept_id;
        $result = false;

        $sql = "SELECT user_id FROM $tbl_user_rel_user
                WHERE
                    user_id = $user_id AND
                    friend_user_id = $hr_dept_id AND
                    relation_type = ".USER_RELATION_TYPE_RRHH;
        $rs = Database::query($sql);
        if (Database::num_rows($rs) > 0) {
            $result = true;
        }

        return $result;
    }

    /**
     * Return the user id of teacher or session administrator.
     *
     * @return int|bool The user id, or false if the session ID was negative
     */
    public static function get_user_id_of_course_admin_or_session_admin(array $courseInfo)
    {
        $session = api_get_session_id();
        $table_user = Database::get_main_table(TABLE_MAIN_USER);
        $table_course_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $table_session_course_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

        if (empty($courseInfo)) {
            return false;
        }

        $courseId = $courseInfo['real_id'];

        if (empty($session)) {
            $sql = 'SELECT u.id uid FROM '.$table_user.' u
                    INNER JOIN '.$table_course_user.' ru
                    ON ru.user_id = u.id
                    WHERE
                        ru.status = '.COURSEMANAGER.' AND
                        ru.c_id = "'.$courseId.'" ';
        } else {
            $sql = 'SELECT u.id uid FROM '.$table_user.' u
                    INNER JOIN '.$table_session_course_user.' sru
                    ON sru.user_id=u.id
                    WHERE
                        sru.c_id="'.$courseId.'" AND
                        sru.session_id="'.$session.'" AND
                        sru.status = '.SessionEntity::COACH;
        }

        $rs = Database::query($sql);
        $num_rows = Database::num_rows($rs);

        if (0 === $num_rows) {
            return false;
        }

        if (1 === $num_rows) {
            $row = Database::fetch_array($rs);

            return (int) $row['uid'];
        }

        $my_num_rows = $num_rows;
        $my_user_id = Database::result($rs, $my_num_rows - 1, 'uid');

        return (int) $my_user_id;
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

        if ($row['path_certificate'] == '' || is_null($row['path_certificate'])) {
            return false;
        }

        return true;
    }

    /**
     * Gets the info about a gradebook certificate for a user by course.
     *
     * @param string $course_code The course code
     * @param int    $session_id
     * @param int    $user_id     The user id
     * @param string $startDate   date string
     * @param string $endDate     date string
     *
     * @return array if there is not information return false
     */
    public static function get_info_gradebook_certificate(
        $course_code,
        $session_id,
        $user_id,
        $startDate = null,
        $endDate = null
    ) {
        $tbl_grade_certificate = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CERTIFICATE);
        $tbl_grade_category = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);
        $session_id = (int) $session_id;
        $user_id = (int) $user_id;

        if (empty($session_id)) {
            $session_condition = ' AND (session_id = "" OR session_id = 0 OR session_id IS NULL )';
        } else {
            $session_condition = " AND session_id = $session_id";
        }

        $dateConditions = "";
        if (!empty($startDate)) {
            $startDate = api_get_utc_datetime($startDate, false, true);
            $dateConditions .= " AND created_at >= '".$startDate->format('Y-m-d 00:00:00')."' ";
        }
        if (!empty($endDate)) {
            $endDate = api_get_utc_datetime($endDate, false, true);
            $dateConditions .= " AND created_at <= '".$endDate->format('Y-m-d 23:59:59')."' ";
        }

        $sql = 'SELECT * FROM '.$tbl_grade_certificate.'
                WHERE cat_id = (
                    SELECT id FROM '.$tbl_grade_category.'
                    WHERE
                        course_code = "'.Database::escape_string($course_code).'" '.$session_condition.' '.$dateConditions.'
                    LIMIT 1
                ) AND user_id='.$user_id;

        $rs = Database::query($sql);
        if (Database::num_rows($rs) > 0) {
            $row = Database::fetch_array($rs, 'ASSOC');
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
                  status = 2 ";
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
        if ($user) {
            $userId = $user->getId();
            if (!self::is_admin($userId)) {
                $table = Database::get_main_table(TABLE_MAIN_ADMIN);
                $sql = "INSERT INTO $table SET user_id = $userId";
                Database::query($sql);
            }

            $user->addRole('ROLE_SUPER_ADMIN');
            self::getManager()->updateUser($user, true);
        }
    }

    public static function removeUserAdmin(User $user)
    {
        $userId = (int) $user->getId();
        if (self::is_admin($userId)) {
            $table = Database::get_main_table(TABLE_MAIN_ADMIN);
            $sql = "DELETE FROM $table WHERE user_id = $userId";
            Database::query($sql);
            $user->removeRole('ROLE_SUPER_ADMIN');
            self::getManager()->updateUser($user, true);
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
     *
     * @return int Affected rows
     */
    public static function subscribeBossToUsers($bossId, $usersId, $deleteOtherAssignedUsers = true)
    {
        return self::subscribeUsersToUser(
            $bossId,
            $usersId,
            USER_RELATION_TYPE_BOSS,
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
                WHERE user_id = $userId AND relation_type = ".USER_RELATION_TYPE_BOSS;
        Database::query($sql);

        return true;
    }

    /**
     * It updates course relation type as EX-LEARNER if project name (extra field from user_edition_extra_field_to_check) is changed.
     *
     * @param $userId
     * @param $extraValue
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public static function updateCourseRelationTypeExLearner($userId, $extraValue)
    {
        if (false !== api_get_configuration_value('user_edition_extra_field_to_check')) {
            $extraToCheck = api_get_configuration_value('user_edition_extra_field_to_check');

            // Get the old user extra value to check
            $userExtra = UserManager::get_extra_user_data_by_field($userId, $extraToCheck);
            if (isset($userExtra[$extraToCheck]) && $userExtra[$extraToCheck] != $extraValue) {
                // it searchs the courses with the user old extravalue
                $extraFieldValues = new ExtraFieldValue('course');
                $extraItems = $extraFieldValues->get_item_id_from_field_variable_and_field_value($extraToCheck, $userExtra[$extraToCheck], false, false, true);
                $coursesTocheck = [];
                if (!empty($extraItems)) {
                    foreach ($extraItems as $items) {
                        $coursesTocheck[] = $items['item_id'];
                    }
                }

                $tblUserGroupRelUser = Database::get_main_table(TABLE_USERGROUP_REL_USER);
                $tblUserGroupRelCourse = Database::get_main_table(TABLE_USERGROUP_REL_COURSE);
                $tblUserGroupRelSession = Database::get_main_table(TABLE_USERGROUP_REL_SESSION);
                $tblSessionUser = Database::get_main_table(TABLE_MAIN_SESSION_USER);
                $tblCourseUser = Database::get_main_table(TABLE_MAIN_COURSE_USER);

                // To check in main course
                if (!empty($coursesTocheck)) {
                    foreach ($coursesTocheck as $courseId) {
                        $sql = "SELECT id FROM $tblCourseUser
                                WHERE user_id = $userId AND c_id = $courseId";
                        $rs = Database::query($sql);
                        if (Database::num_rows($rs) > 0) {
                            $id = Database::result($rs, 0, 0);
                            $sql = "UPDATE $tblCourseUser SET relation_type = ".COURSE_EXLEARNER."
                                    WHERE id = $id";
                            Database::query($sql);
                        }
                    }
                }

                // To check in sessions
                if (!empty($coursesTocheck)) {
                    $tblSessionCourseUser = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
                    $sessionsToCheck = [];
                    foreach ($coursesTocheck as $courseId) {
                        $sql = "SELECT id, session_id FROM $tblSessionCourseUser
                                WHERE user_id = $userId AND c_id = $courseId";
                        $rs = Database::query($sql);
                        if (Database::num_rows($rs) > 0) {
                            $row = Database::fetch_array($rs);
                            $id = $row['id'];
                            $sessionId = $row['session_id'];
                            $sql = "UPDATE $tblSessionCourseUser SET status = ".COURSE_EXLEARNER."
                                    WHERE id = $id";
                            Database::query($sql);
                            $sessionsToCheck[] = $sessionId;
                        }
                    }
                    // It checks if user is ex-learner in all courses in the session to update the session relation type
                    if (!empty($sessionsToCheck)) {
                        $sessionsToCheck = array_unique($sessionsToCheck);
                        foreach ($sessionsToCheck as $sessionId) {
                            $checkAll = Database::query("SELECT count(id) FROM $tblSessionCourseUser WHERE user_id = $userId AND session_id = $sessionId");
                            $countAll = Database::result($checkAll, 0, 0);
                            $checkExLearner = Database::query("SELECT count(id) FROM $tblSessionCourseUser WHERE status = ".COURSE_EXLEARNER." AND user_id = $userId AND session_id = $sessionId");
                            $countExLearner = Database::result($checkExLearner, 0, 0);
                            if ($countAll > 0 && $countAll == $countExLearner) {
                                $sql = "UPDATE $tblSessionUser SET relation_type = ".COURSE_EXLEARNER."
                                    WHERE user_id = $userId AND session_id = $sessionId";
                                Database::query($sql);
                            }
                        }
                    }
                }
                // To check users inside a class
                $rsUser = Database::query("SELECT usergroup_id FROM $tblUserGroupRelUser WHERE user_id = $userId");
                if (Database::num_rows($rsUser) > 0) {
                    while ($rowUser = Database::fetch_array($rsUser)) {
                        $usergroupId = $rowUser['usergroup_id'];

                        // Count courses with exlearners
                        $sqlC1 = "SELECT count(id) FROM $tblUserGroupRelCourse WHERE usergroup_id = $usergroupId";
                        $rsCourses = Database::query($sqlC1);
                        $countGroupCourses = Database::result($rsCourses, 0, 0);

                        $sqlC2 = "SELECT count(cu.id)
                                FROM $tblCourseUser cu
                                INNER JOIN $tblUserGroupRelCourse gc
                                    ON gc.course_id = cu.c_id
                                WHERE
                                    cu.user_id = $userId AND
                                    usergroup_id = $usergroupId AND
                                    relation_type = ".COURSE_EXLEARNER;
                        $rsExCourses = Database::query($sqlC2);
                        $countExCourses = Database::result($rsExCourses, 0, 0);
                        $checkedExCourses = $countGroupCourses > 0 && ($countExCourses == $countGroupCourses);

                        // Count sessions with exlearners
                        $sqlS1 = "SELECT count(id) FROM $tblUserGroupRelSession WHERE usergroup_id = $usergroupId";
                        $rsSessions = Database::query($sqlS1);
                        $countGroupSessions = Database::result($rsSessions, 0, 0);

                        $sqlS2 = "SELECT count(su.id)
                                FROM $tblSessionUser su
                                INNER JOIN $tblUserGroupRelSession gs
                                    ON gs.session_id = su.session_id
                                WHERE
                                    su.user_id = $userId AND
                                    usergroup_id = $usergroupId AND
                                    relation_type = ".COURSE_EXLEARNER;
                        $rsExSessions = Database::query($sqlS2);
                        $countExSessions = Database::result($rsExSessions, 0, 0);
                        $checkedExSessions = $countGroupSessions > 0 && ($countExSessions == $countGroupSessions);

                        // it checks if usergroup user should be set to EXLEARNER
                        $checkedExClassLearner = false;
                        if ($countGroupCourses > 0 && $countGroupSessions == 0) {
                            $checkedExClassLearner = $checkedExCourses;
                        } elseif ($countGroupCourses == 0 && $countGroupSessions > 0) {
                            $checkedExClassLearner = $checkedExSessions;
                        } elseif ($countGroupCourses > 0 && $countGroupSessions > 0) {
                            $checkedExClassLearner = ($checkedExCourses && $checkedExSessions);
                        }

                        if ($checkedExClassLearner) {
                            Database::query("UPDATE $tblUserGroupRelUser SET relation_type = ".COURSE_EXLEARNER." WHERE user_id = $userId AND usergroup_id = $usergroupId");
                        }
                    }
                }
            }
        }
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

                $bossLanguage = $bossInfo['language'];

                $sql = "INSERT IGNORE INTO $userRelUserTable (user_id, friend_user_id, relation_type)
                        VALUES ($studentId, $bossId, ".USER_RELATION_TYPE_BOSS.")";
                $insertId = Database::query($sql);

                if ($insertId) {
                    if ($sendNotification) {
                        $name = $studentInfo['complete_name'];
                        $url = api_get_path(WEB_CODE_PATH).'mySpace/myStudents.php?student='.$studentId;
                        $url = Display::url($url, $url);
                        $subject = sprintf(
                            get_lang('UserXHasBeenAssignedToBoss', false, $bossLanguage),
                            $name
                        );
                        $message = sprintf(
                            get_lang('UserXHasBeenAssignedToBossWithUrlX', false, $bossLanguage),
                            $name,
                            $url
                        );
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

        if ($trackResult != false) {
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
                            USER_RELATION_TYPE_BOSS,
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
                            USER_RELATION_TYPE_BOSS,
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
        $allowAdmin = api_get_setting('allow_user_course_subscription_by_course_admin');
        if (($allowAdmin === 'true' && api_is_allowed_to_edit()) ||
            api_is_platform_admin()
        ) {
            $userPath = api_get_path(WEB_CODE_PATH).'user/';

            $headers = [
                [
                    'url' => $userPath.'user.php?'.api_get_cidreq().'&type='.STUDENT,
                    'content' => get_lang('Students'),
                ],
                [
                    'url' => $userPath.'user.php?'.api_get_cidreq().'&type='.COURSEMANAGER,
                    'content' => get_lang('Teachers'),
                ],
                /*[
                    'url' => $userPath.'subscribe_user.php?'.api_get_cidreq(),
                    'content' => get_lang('Students'),
                ],
                [
                    'url' => $userPath.'subscribe_user.php?type=teacher&'.api_get_cidreq(),
                    'content' => get_lang('Teachers'),
                ],*/
                [
                    'url' => api_get_path(WEB_CODE_PATH).'group/group.php?'.api_get_cidreq(),
                    'content' => get_lang('Groups'),
                ],
                'classes' => [
                    'url' => $userPath.'class.php?'.api_get_cidreq(),
                    'content' => get_lang('Classes'),
                ],
            ];

            if (api_get_configuration_value('session_classes_tab_disable')
                && !api_is_platform_admin()
                && api_get_session_id()
            ) {
                unset($headers['classes']);
            }

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
            $logInfo = [
                'tool' => 'logout',
                'tool_id' => 0,
                'tool_id_detail' => 0,
                'action' => '',
                'info' => 'Change user (login as)',
            ];
            Event::registerLog($logInfo);

            // Logout the current user
            self::loginDelete(api_get_user_id());

            Session::erase('_user');
            Session::erase('is_platformAdmin');
            Session::erase('is_allowedCreateCourse');
            Session::erase('_uid');

            // Cleaning session variables
            $_user['firstName'] = $userInfo['firstname'];
            $_user['lastName'] = $userInfo['lastname'];
            $_user['mail'] = $userInfo['email'];
            $_user['official_code'] = $userInfo['official_code'];
            $_user['picture_uri'] = $userInfo['picture_uri'];
            $_user['user_id'] = $userId;
            $_user['id'] = $userId;
            $_user['status'] = $userInfo['status'];

            // Filling session variables with new data
            Session::write('_uid', $userId);
            Session::write('_user', $userInfo);
            Session::write('is_platformAdmin', (bool) self::is_admin($userId));
            Session::write('is_allowedCreateCourse', $userInfo['status'] == 1);
            // will be useful later to know if the user is actually an admin or not (example reporting)
            Session::write('login_as', true);
            $logInfo = [
                'tool' => 'login',
                'tool_id' => 0,
                'tool_id_detail' => 0,
                'info' => $userId,
            ];
            Event::registerLog($logInfo);

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

    public static function blockIfMaxLoginAttempts(array $userInfo)
    {
        if (false === (bool) $userInfo['active'] || null === $userInfo['last_login']) {
            return;
        }

        $maxAllowed = (int) api_get_configuration_value('login_max_attempt_before_blocking_account');

        if ($maxAllowed <= 0) {
            return;
        }

        $em = Database::getManager();

        $countFailedAttempts = $em
            ->getRepository(TrackELoginAttempt::class)
            ->createQueryBuilder('la')
            ->select('COUNT(la)')
            ->where('la.username = :username')
            ->andWhere('la.loginDate >= :last_login')
            ->andWhere('la.success <> TRUE')
            ->setParameters(
                [
                    'username' => $userInfo['username'],
                    'last_login' => $userInfo['last_login'],
                ]
            )
            ->getQuery()
            ->getSingleScalarResult()
        ;

        if ($countFailedAttempts >= $maxAllowed) {
            Database::update(
                Database::get_main_table(TABLE_MAIN_USER),
                ['active' => false],
                ['username = ?' => $userInfo['username']]
            );

            Display::addFlash(
                Display::return_message(
                    sprintf(
                        get_lang('XAccountDisabledByYAttempts'),
                        $userInfo['username'],
                        $countFailedAttempts
                    ),
                    'error',
                    false
                )
            );
        }
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
        $mailSubject = get_lang('RegistrationConfirmation');
        $mailBody = get_lang('RegistrationConfirmationEmailMessage')
            .PHP_EOL
            .Display::url($url, $url);

        api_mail_html(
            self::formatUserFullName($user),
            $user->getEmail(),
            $mailSubject,
            $mailBody
        );
        Display::addFlash(Display::return_message(get_lang('CheckYourEmailAndFollowInstructions')));
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
            ->setCurriculumItems(null)
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
            if ($res === false && $debug > 0) {
                error_log("Could not anonymize IP address for user $userId ($sql)");
            }

            $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
            $sql = "UPDATE $table set user_ip = '$substitute' WHERE user_id = $userId";
            $res = Database::query($sql);
            if ($res === false && $debug > 0) {
                error_log("Could not anonymize IP address for user $userId ($sql)");
            }

            $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
            $sql = "UPDATE $table set user_ip = '$substitute' WHERE exe_user_id = $userId";
            $res = Database::query($sql);
            if ($res === false && $debug > 0) {
                error_log("Could not anonymize IP address for user $userId ($sql)");
            }

            $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_LOGIN);
            $sql = "UPDATE $table set user_ip = '$substitute' WHERE login_user_id = $userId";
            $res = Database::query($sql);
            if ($res === false && $debug > 0) {
                error_log("Could not anonymize IP address for user $userId ($sql)");
            }

            $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ONLINE);
            $sql = "UPDATE $table set user_ip = '$substitute' WHERE login_user_id = $userId";
            $res = Database::query($sql);
            if ($res === false && $debug > 0) {
                error_log("Could not anonymize IP address for user $userId ($sql)");
            }

            $table = Database::get_course_table(TABLE_WIKI);
            $sql = "UPDATE $table set user_ip = '$substitute' WHERE user_id = $userId";
            $res = Database::query($sql);
            if ($res === false && $debug > 0) {
                error_log("Could not anonymize IP address for user $userId ($sql)");
            }

            $table = Database::get_main_table(TABLE_TICKET_MESSAGE);
            $sql = "UPDATE $table set ip_address = '$substitute' WHERE sys_insert_user_id = $userId";
            $res = Database::query($sql);
            if ($res === false && $debug > 0) {
                error_log("Could not anonymize IP address for user $userId ($sql)");
            }

            $table = Database::get_course_table(TABLE_WIKI);
            $sql = "UPDATE $table set user_ip = '$substitute' WHERE user_id = $userId";
            $res = Database::query($sql);
            if ($res === false && $debug > 0) {
                error_log("Could not anonymize IP address for user $userId ($sql)");
            }
        }
        $em->persist($user);
        $em->flush($user);
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
        $allowDelete = api_get_configuration_value('allow_delete_user_for_session_admin');

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
                        sprintf(get_lang('UserXAnonymized'), $userToUpdateInfo['complete_name_with_username']),
                        'confirmation'
                    );
                } else {
                    $message = Display::return_message(
                        sprintf(get_lang('CannotAnonymizeUserX'), $userToUpdateInfo['complete_name_with_username']),
                        'error'
                    );
                }
            } else {
                $message = Display::return_message(
                    sprintf(get_lang('NoPermissionToAnonymizeUserX'), $userToUpdateInfo['complete_name_with_username']),
                    'error'
                );
            }
        }

        return $message;
    }

    /**
     * @param int $userId
     *
     * @throws Exception
     *
     * @return string
     */
    public static function deleteUserWithVerification($userId)
    {
        $allowDelete = api_get_configuration_value('allow_delete_user_for_session_admin');
        $message = Display::return_message(get_lang('CannotDeleteUser'), 'error');
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
                if (self::delete_user($userId)) {
                    $message = Display::return_message(
                        get_lang('UserDeleted').': '.$userToUpdateInfo['complete_name_with_username'],
                        'confirmation'
                    );
                } else {
                    $message = Display::return_message(get_lang('CannotDeleteUserBecauseOwnsCourse'), 'error');
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
     *
     * @param bool $includeUsername Optional. By default username is not included.
     *
     * @return string
     */
    public static function formatUserFullName(User $user, $includeUsername = false)
    {
        $fullName = api_get_person_name($user->getFirstname(), $user->getLastname());

        if ($includeUsername && api_get_configuration_value('hide_username_with_complete_name') !== true) {
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

        $sql = "SELECT c.id, c.name
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
        if (!api_get_configuration_value('allow_career_users')) {
            return false;
        }

        if (self::userHasCareer($userId, $careerId) === false) {
            $params = [
                'user_id' => $userId,
                'career_id' => $careerId,
                'created_at' => api_get_utc_datetime(),
                'updated_at' => api_get_utc_datetime(),
            ];
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
        if (!api_get_configuration_value('allow_career_users')) {
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

        return Database::fetch_array($result, 'ASSOC');
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
     * @param int $userId
     *
     * @throws Exception
     */
    public static function deleteUserFiles($userId)
    {
        $path = self::getUserPathById($userId, 'system');

        $fs = new Filesystem();
        $fs->remove($path);
    }

    public static function redirectToResetPassword($userId)
    {
        if (!api_get_configuration_value('force_renew_password_at_first_login')) {
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

    /**
     * It returns the list of user status available.
     *
     * @return array
     */
    public static function getUserStatusList()
    {
        $userStatusConfig = [];
        // it gets the roles to show in creation/edition user
        if (true === api_get_configuration_value('user_status_show_options_enabled')) {
            $userStatusConfig = api_get_configuration_value('user_status_show_option');
        }
        // it gets the roles to show in creation/edition user (only for admins)
        if (true === api_get_configuration_value('user_status_option_only_for_admin_enabled') && api_is_platform_admin()) {
            $userStatusConfig = api_get_configuration_value('user_status_option_show_only_for_admin');
        }

        $status = [];
        if (!empty($userStatusConfig)) {
            $statusLang = api_get_status_langvars();
            foreach ($userStatusConfig as $role => $enabled) {
                if ($enabled) {
                    $constStatus = constant($role);
                    $status[$constStatus] = $statusLang[$constStatus];
                }
            }
        } else {
            $status[COURSEMANAGER] = get_lang('Teacher');
            $status[STUDENT] = get_lang('Learner');
            $status[DRH] = get_lang('Drh');
            $status[SESSIONADMIN] = get_lang('SessionsAdmin');
            $status[STUDENT_BOSS] = get_lang('RoleStudentBoss');
            $status[INVITEE] = get_lang('Invitee');
        }

        return $status;
    }

    /**
     * Get the expiration date by user status from configuration value.
     *
     * @param $status
     *
     * @throws Exception
     *
     * @return array
     */
    public static function getExpirationDateByRole($status)
    {
        $status = (int) $status;
        $nbDaysByRole = api_get_configuration_value('user_number_of_days_for_default_expiration_date_per_role');
        $dates = [];
        if (!empty($nbDaysByRole)) {
            $date = new DateTime();
            foreach ($nbDaysByRole as $strVariable => $nDays) {
                $constStatus = constant($strVariable);
                if ($status == $constStatus) {
                    $duration = "P{$nDays}D";
                    $date->add(new DateInterval($duration));
                    $newExpirationDate = $date->format('Y-m-d H:i');
                    $formatted = api_format_date($newExpirationDate, DATE_TIME_FORMAT_LONG_24H);
                    $dates = ['formatted' => $formatted, 'date' => $newExpirationDate];
                }
            }
        }

        return $dates;
    }

    public static function getAllowedRolesAsTeacher(): array
    {
        return [
            COURSEMANAGER,
            SESSIONADMIN,
        ];
    }

    /**
     * Count users in courses and if they have certificate.
     * This function is resource intensive.
     *
     * @return array
     */
    public static function countUsersWhoFinishedCourses()
    {
        $courses = [];
        $currentAccessUrlId = api_get_current_access_url_id();
        $sql = "SELECT course.code, cru.user_id
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
                $repository = $entityManager->getRepository('ChamiloCoreBundle:GradebookCategory');
                //todo check when have more than 1 gradebook
                /** @var \Chamilo\CoreBundle\Entity\GradebookCategory $gradebook */
                $gradebook = $repository->findOneBy(['courseCode' => $row['code']]);

                if (!empty($gradebook)) {
                    $finished = 0;
                    $gb = Category::createCategoryObjectFromEntity($gradebook);
                    $finished = $gb->is_certificate_available($row['user_id']);
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
     */
    public static function countUsersWhoFinishedCoursesInSessions()
    {
        $coursesInSessions = [];
        $currentAccessUrlId = api_get_current_access_url_id();
        $sql = "SELECT course.code, srcru.session_id, srcru.user_id, session.name
                FROM session_rel_course_rel_user srcru
                    JOIN course ON srcru.c_id = course.id
                    JOIN access_url_rel_session aurs on srcru.session_id = aurs.session_id
                    JOIN session ON srcru.session_id = session.id
                WHERE aurs.access_url_id = $currentAccessUrlId
                ORDER BY course.code, session.name
        ";

        $res = Database::query($sql);
        if (Database::num_rows($res) > 0) {
            while ($row = Database::fetch_array($res)) {
                $index = $row['code'].' ('.$row['name'].')';
                if (!isset($coursesInSessions[$index])) {
                    $coursesInSessions[$index] = [
                        'subscribed' => 0,
                        'finished' => 0,
                    ];
                }

                $coursesInSessions[$index]['subscribed']++;
                $entityManager = Database::getManager();
                $repository = $entityManager->getRepository('ChamiloCoreBundle:GradebookCategory');
                /** @var \Chamilo\CoreBundle\Entity\GradebookCategory $gradebook */
                $gradebook = $repository->findOneBy(
                    [
                        'courseCode' => $row['code'],
                        'sessionId' => $row['session_id'],
                    ]
                );

                if (!empty($gradebook)) {
                    $finished = 0;
                    $gb = Category::createCategoryObjectFromEntity($gradebook);
                    $finished = $gb->is_certificate_available($row['user_id']);
                    if (!empty($finished)) {
                        $coursesInSessions[$index]['finished']++;
                    }
                }
            }
        }

        return $coursesInSessions;
    }

    /**
     * Build the active-column of the table to lock or unlock a certain user
     * lock = the user can no longer use this account.
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     *
     * @return string Some HTML-code with the lock/unlock button
     */
    public static function getActiveFilterForTable(string $active, string $params, array $row): string
    {
        if ('1' == $active) {
            $action = 'Lock';
            $image = 'accept';
        } elseif ('-1' == $active) {
            $action = 'edit';
            $image = 'warning';
        } elseif ('0' == $active) {
            $action = 'Unlock';
            $image = 'error';
        }

        if ('edit' === $action) {
            $langAccountExpired = get_lang('AccountExpired');

            return Display::return_icon(
                $image.'.png',
                    $langAccountExpired,
                [],
                ICON_SIZE_TINY
            ).'<span class="sr-only" aria-hidden="true">'.$langAccountExpired.'</span>';
        }

        if ($row['0'] != api_get_user_id()) {
            $langAction = get_lang(ucfirst($action));
            // you cannot lock yourself out otherwise you could disable all the
            // accounts including your own => everybody is locked out and nobody
            // can change it anymore.
            return Display::return_icon(
                $image.'.png',
                $langAction,
                ['onclick' => 'active_user(this);', 'id' => 'img_'.$row['0'], 'style' => 'cursor: pointer;'],
                ICON_SIZE_TINY
                ).'<span class="sr-only" aria-hidden="true">'.$langAction.'</span>';
        }

        return '';
    }

    public static function getScriptFunctionForActiveFilter(): string
    {
        return 'function active_user(element_div) {
            id_image = $(element_div).attr("id");
            image_clicked = $(element_div).attr("src");
            image_clicked_info = image_clicked.split("/");
            image_real_clicked = image_clicked_info[image_clicked_info.length-1];
            var status = 1;
            if (image_real_clicked == "accept.png") {
                status = 0;
            }
            user_id = id_image.split("_");
            ident = "#img_"+user_id[1];
            if (confirm("'.get_lang('AreYouSureToEditTheUserStatus', '').'")) {
                 $.ajax({
                    contentType: "application/x-www-form-urlencoded",
                    beforeSend: function(myObject) {
                        $(ident).attr("src","'.Display::returnIconPath('loading1.gif').'"); //candy eye stuff
                    },
                    type: "GET",
                    url: _p.web_ajax + "user_manager.ajax.php?a=active_user",
                    data: "user_id=" + user_id[1] + "&status=" + status,
                    success: function(data) {
                        if (data == 1) {
                            $(ident).attr("src", "'.Display::returnIconPath('accept.png', ICON_SIZE_TINY).'");
                            $(ident).attr("title","'.get_lang('Lock').'");
                        }
                        if (data == 0) {
                            $(ident).attr("src","'.Display::returnIconPath('error.png').'");
                            $(ident).attr("title","'.get_lang('Unlock').'");
                        }
                        if (data == -1) {
                            $(ident).attr("src", "'.Display::returnIconPath('warning.png').'");
                            $(ident).attr("title","'.get_lang('ActionNotAllowed').'");
                        }
                    }
                });
            }
        }';
    }

    /**
     * @return EncoderFactory
     */
    private static function getEncoderFactory()
    {
        $encryption = self::getPasswordEncryption();
        $encoders = [
            'Chamilo\\UserBundle\\Entity\\User' => new \Chamilo\UserBundle\Security\Encoder($encryption),
        ];

        return new EncoderFactory($encoders);
    }

    /**
     * @return \Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface
     */
    private static function getEncoder(User $user)
    {
        $encoderFactory = self::getEncoderFactory();

        return $encoderFactory->getEncoder($user);
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
    private static function change_active_state($user_id, $active)
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
        if ($active == 1) {
            $ev = LOG_USER_ENABLE;
        }
        if ($r !== false) {
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
}
