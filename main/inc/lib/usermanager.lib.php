<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\ExtraField as EntityExtraField;
use Chamilo\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Encoder\BCryptPasswordEncoder;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;
use Symfony\Component\Security\Core\Encoder\PlaintextPasswordEncoder;

/**
 *
 * Class UserManager
 *
 * This library provides functions for user management.
 * Include/require it in your code to use its functionality.
 * @package chamilo.library
 * @author Julio Montoya <gugli100@gmail.com> Social network groups added 2009/12
 *
 */
class UserManager
{
    // This constants are deprecated use the constants located in ExtraField
    const USER_FIELD_TYPE_TEXT = 1;
    const USER_FIELD_TYPE_TEXTAREA = 2;
    const USER_FIELD_TYPE_RADIO = 3;
    const USER_FIELD_TYPE_SELECT = 4;
    const USER_FIELD_TYPE_SELECT_MULTIPLE = 5;
    const USER_FIELD_TYPE_DATE = 6;
    const USER_FIELD_TYPE_DATETIME = 7;
    const USER_FIELD_TYPE_DOUBLE_SELECT = 8;
    const USER_FIELD_TYPE_DIVIDER = 9;
    const USER_FIELD_TYPE_TAG = 10;
    const USER_FIELD_TYPE_TIMEZONE = 11;
    const USER_FIELD_TYPE_SOCIAL_PROFILE = 12;
    const USER_FIELD_TYPE_FILE = 13;
    const USER_FIELD_TYPE_MOBILE_PHONE_NUMBER  = 14;

    private static $encryptionMethod;

    /**
     * The default constructor only instanciates an empty user object
     * @assert () === null
     */
    public function __construct()
    {

    }

    /**
     * Repository is use to query the DB, selects, etc
     * @return Chamilo\UserBundle\Entity\Repository\UserRepository
     */
    public static function getRepository()
    {
        return Database::getManager()->getRepository('ChamiloUserBundle:User');
    }

    /**
     * Create/update/delete methods are available in the UserManager
     * (based in the Sonata\UserBundle\Entity\UserManager)
     *
     * @return Chamilo\UserBundle\Entity\Manager\UserManager
     */
    public static function getManager()
    {
        $encoderFactory = self::getEncoderFactory();

        $userManager = new Chamilo\UserBundle\Entity\Manager\UserManager(
            $encoderFactory,
            new \FOS\UserBundle\Util\Canonicalizer(),
            new \FOS\UserBundle\Util\Canonicalizer(),
            Database::getManager(),
            'Chamilo\\UserBundle\\Entity\\User'
        );

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
     * @return EncoderFactory
     */
    private static function getEncoderFactory()
    {
        $encryption = self::getPasswordEncryption();
        switch ($encryption) {
            case 'none':
                $defaultEncoder = new PlaintextPasswordEncoder();
                break;
            case 'sha1':
            case 'md5':
                $defaultEncoder = new MessageDigestPasswordEncoder($encryption, false, 1);
                break;
            case 'bcrypt':
                $defaultEncoder = new BCryptPasswordEncoder(4);
        }

        $encoders = array(
            'Chamilo\\UserBundle\\Entity\\User' => $defaultEncoder
        );

        $encoderFactory = new EncoderFactory($encoders);

        return $encoderFactory;
    }

    /**
     * @param User $user
     *
     * @return \Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface
     */
    private static function getEncoder(User $user)
    {
        $encoderFactory = self::getEncoderFactory();

        return $encoderFactory->getEncoder($user);
    }

    /**
     * Validates the password
     * @param string $password
     * @param User   $user
     *
     * @return bool
     */
    public static function isPasswordValid($password, User $user)
    {
        $encoder = self::getEncoder($user);

        $validPassword = $encoder->isPasswordValid(
            $user->getPassword(),
            $password,
            $user->getSalt()
        );

        return $validPassword;
    }

    /**
     * @param string $raw
     * @param User   $user
     *
     * @return bool
     */
    public static function encryptPassword($raw, User $user)
    {
        $encoder = self::getEncoder($user);

        $encodedPassword = $encoder->encodePassword(
            $raw,
            $user->getSalt()
        );

        return $encodedPassword;
    }

    /**
     * @param int $userId
     * @param string $password
     *
     */
    public static function updatePassword($userId, $password)
    {
        $repository = self::getRepository();
        /** @var User $user */
        $user = $repository->find($userId);
        $userManager = self::getManager();
        $user->setPlainPassword($password);
        $userManager->updateUser($user, true);
    }

    /**
     * Creates a new user for the platform
     * @author Hugues Peeters <peeters@ipm.ucl.ac.be>,
     * @author Roan Embrechts <roan_embrechts@yahoo.com>
     * @param  string Firstname
     * @param  string Lastname
     * @param  int    Status (1 for course tutor, 5 for student, 6 for anonymous)
     * @param  string e-mail address
     * @param  string Login
     * @param  string Password
     * @param  string Any official code (optional)
     * @param  string User language    (optional)
     * @param  string Phone number    (optional)
     * @param  string Picture URI        (optional)
     * @param  string Authentication source    (optional, defaults to 'platform', dependind on constant)
     * @param  string Account expiration date (optional, defaults to null)
     * @param  int     Whether the account is enabled or disabled by default
     * @param  int     The department of HR in which the user is registered (optional, defaults to 0)
     * @param  array Extra fields
     * @param  string Encrypt method used if password is given encrypted. Set to an empty string by default
     * @param  bool $send_mail
     * @param  bool $isAdmin
     *
     * @return mixed   new user id - if the new user creation succeeds, false otherwise
     * @desc The function tries to retrieve user id from the session.
     * If it exists, the current user id is the creator id. If a problem arises,
     * it stores the error message in global $api_failureList
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
        $auth_source = PLATFORM_AUTH_SOURCE,
        $expirationDate = null,
        $active = 1,
        $hr_dept_id = 0,
        $extra = null,
        $encrypt_method = '',
        $send_mail = false,
        $isAdmin = false
    ) {
        $currentUserId = api_get_user_id();
        $hook = HookCreateUser::create();
        if (!empty($hook)) {
            $hook->notifyCreateUser(HOOK_EVENT_TYPE_PRE);
        }
        global $_configuration;
        $original_password = $password;
        $access_url_id = 1;

        if (api_get_multiple_access_url()) {
            $access_url_id = api_get_current_access_url_id();
        }

        if (is_array($_configuration[$access_url_id]) &&
            isset($_configuration[$access_url_id]['hosting_limit_users']) &&
            $_configuration[$access_url_id]['hosting_limit_users'] > 0) {
            $num = self::get_number_of_users();
            if ($num >= $_configuration[$access_url_id]['hosting_limit_users']) {
                api_warn_hosting_contact('hosting_limit_users');
                return api_set_failure('portal users limit reached');
            }
        }

        if ($status === 1 &&
            is_array($_configuration[$access_url_id]) &&
            isset($_configuration[$access_url_id]['hosting_limit_teachers']) &&
            $_configuration[$access_url_id]['hosting_limit_teachers'] > 0
        ) {
            $num = self::get_number_of_users(1);
            if ($num >= $_configuration[$access_url_id]['hosting_limit_teachers']) {
                api_warn_hosting_contact('hosting_limit_teachers');
                return api_set_failure('portal teachers limit reached');
            }
        }

        if (empty($password)) {
            return api_set_failure('ThisFieldIsRequired');
        }

        // database table definition
        $table_user = Database::get_main_table(TABLE_MAIN_USER);

        //Checking the user language
        $languages = api_get_languages();
        $language = strtolower($language);
        if (!in_array($language, $languages['folder'])) {
            $language = api_get_setting('platformLanguage');
        }

        if (!empty($currentUserId)) {
            $creator_id = $currentUserId;
        } else {
            $creator_id = '';
        }

        // First check wether the login already exists
        if (!self::is_username_available($loginName)) {
            return api_set_failure('login-pass already taken');
        }

        $currentDate = api_get_utc_datetime();
        $now = new DateTime($currentDate);

        if (empty($expirationDate)) {
            // Default expiration date
            // if there is a default duration of a valid account then
            // we have to change the expiration_date accordingly
            if (api_get_setting('account_valid_duration') != '') {
                $expirationDate = new DateTime($currentDate);
                $days = intval(api_get_setting('account_valid_duration'));
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
            ->setCreatorId($creator_id)
            ->setAuthSource($auth_source)
            ->setPhone($phone)
            ->setLanguage($language)
            ->setRegistrationDate($now)
            ->setHrDeptId($hr_dept_id)
            ->setActive($active);

        if (!empty($expirationDate)) {
            $user->setExpirationDate($expirationDate);
        }

        $userManager->updateUser($user, true);
        $userId = $user->getId();

        if (!empty($userId)) {
            $return = $userId;
            $sql = "UPDATE $table_user SET user_id = $return WHERE id = $return";
            Database::query($sql);

            if ($isAdmin) {
                UserManager::add_user_as_admin($userId);
            }

            if (api_get_multiple_access_url()) {
                UrlManager::add_user_to_url($return, api_get_current_access_url_id());
            } else {
                //we are adding by default the access_url_user table with access_url_id = 1
                UrlManager::add_user_to_url($return, 1);
            }

            if (!empty($email) && $send_mail) {
                $recipient_name = api_get_person_name(
                    $firstName,
                    $lastName,
                    null,
                    PERSON_NAME_EMAIL_ADDRESS
                );
                $tplSubject = new Template(null, false, false, false, false, false);
                $layoutSubject = $tplSubject->get_template(
                    'mail/subject_registration_platform.tpl'
                );
                $emailSubject = $tplSubject->fetch($layoutSubject);
                $sender_name = api_get_person_name(
                    api_get_setting('administratorName'),
                    api_get_setting('administratorSurname'),
                    null,
                    PERSON_NAME_EMAIL_ADDRESS
                );
                $email_admin = api_get_setting('emailAdministrator');

                if (api_is_multiple_url_enabled()) {
                    $access_url_id = api_get_current_access_url_id();
                    if ($access_url_id != -1) {
                        $url = api_get_access_url($access_url_id);
                    }
                } else {
                    $url = $_configuration['root_web'];
                }
                $tplContent = new Template(null, false, false, false, false, false);
                // variables for the default template
                $tplContent->assign('complete_name', stripslashes(api_get_person_name($firstName, $lastName)));
                $tplContent->assign('login_name', $loginName);
                $tplContent->assign('original_password', stripslashes($original_password));
                $tplContent->assign('mailWebPath', $url);

                $layoutContent = $tplContent->get_template('mail/content_registration_platform.tpl');
                $emailBody = $tplContent->fetch($layoutContent);
                /* MANAGE EVENT WITH MAIL */
                if (EventsMail::check_if_using_class('user_registration')) {
                    $values["about_user"] = $return;
                    $values["password"] = $original_password;
                    $values["send_to"] = array($return);
                    $values["prior_lang"] = null;
                    EventsDispatcher::events('user_registration', $values);
                } else {
                    $phoneNumber = isset($extra['mobile_phone_number']) ? $extra['mobile_phone_number'] : null;

                    $additionalParameters = array(
                        'smsType' => SmsPlugin::WELCOME_LOGIN_PASSWORD,
                        'userId' => $return,
                        'mobilePhoneNumber' => $phoneNumber,
                        'password' => $original_password
                    );

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
                        $additionalParameters
                    );
                }
                /* ENDS MANAGE EVENT WITH MAIL */
            }
            Event::addEvent(LOG_USER_CREATE, LOG_USER_ID, $return);
        } else {
            return api_set_failure('error inserting in Database');
        }

        if (is_array($extra) && count($extra) > 0) {
            $res = true;
            foreach ($extra as $fname => $fvalue) {
                $res = $res && self::update_extra_field_value($return, $fname, $fvalue);
            }
        }
        self::update_extra_field_value($return, 'already_logged_in', 'false');

        if (!empty($hook)) {
            $hook->setEventData(array(
                'return' => $return,
                'originalPassword' => $original_password
            ));
            $hook->notifyCreateUser(HOOK_EVENT_TYPE_POST);
        }
        return $return;
    }

    /**
     * Can user be deleted? This function checks whether there's a course
     * in which the given user is the
     * only course administrator. If that is the case, the user can't be
     * deleted because the course would remain without a course admin.
     * @param int $user_id The user id
     * @return boolean true if user can be deleted
     * @assert (null) === false
     * @assert (-1) === false
     * @assert ('abc') === false
     */
    public static function can_delete_user($user_id)
    {
        global $_configuration;
        if (isset($_configuration['deny_delete_users']) &&
            $_configuration['deny_delete_users'] == true
        ) {
            return false;
        }
        $table_course_user = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
        if ($user_id != strval(intval($user_id))) {
            return false;
        }
        if ($user_id === false) {
            return false;
        }
        $sql = "SELECT * FROM $table_course_user
                WHERE status = '1' AND user_id = '".$user_id."'";
        $res = Database::query($sql);
        while ($course = Database::fetch_object($res)) {
            $sql = "SELECT user_id FROM $table_course_user
                    WHERE status='1' AND c_id ='".Database::escape_string($course->c_id)."'";
            $res2 = Database::query($sql);
            if (Database::num_rows($res2) == 1) {
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
     * @param int The ID of th user to be deleted
     * @return boolean true if user is successfully deleted, false otherwise
     * @assert (null) === false
     * @assert ('abc') === false
     */
    public static function delete_user($user_id)
    {
        if ($user_id != strval(intval($user_id))) {
            return false;
        }

        if ($user_id === false) {
            return false;
        }

        if (!self::can_delete_user($user_id)) {
            return false;
        }

        $table_user = Database :: get_main_table(TABLE_MAIN_USER);
        $usergroup_rel_user = Database :: get_main_table(TABLE_USERGROUP_REL_USER);
        $table_course_user = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
        $table_course = Database :: get_main_table(TABLE_MAIN_COURSE);
        $table_session = Database :: get_main_table(TABLE_MAIN_SESSION);
        $table_admin = Database :: get_main_table(TABLE_MAIN_ADMIN);
        $table_session_user = Database :: get_main_table(TABLE_MAIN_SESSION_USER);
        $table_session_course_user = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $table_group = Database :: get_course_table(TABLE_GROUP_USER);
        $table_work = Database :: get_course_table(TABLE_STUDENT_PUBLICATION);

        // Unsubscribe the user from all groups in all his courses
        $sql = "SELECT c.id FROM $table_course c, $table_course_user cu
                WHERE
                    cu.user_id = '".$user_id."' AND
                    relation_type<>".COURSE_RELATION_TYPE_RRHH." AND
                    c.id = cu.c_id";
        $res = Database::query($sql);
        while ($course = Database::fetch_object($res)) {
            $sql = "DELETE FROM $table_group
                    WHERE c_id = {$course->id} AND user_id = $user_id";
            Database::query($sql);
        }

        // Unsubscribe user from all classes
        //Classes are not longer supported
        /* $sql = "DELETE FROM $table_class_user WHERE user_id = '".$user_id."'";
          Database::query($sql); */

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
        $sql = "UPDATE $table_session SET id_coach = $currentUserId  WHERE id_coach = '".$user_id."'";
        Database::query($sql);

        $sql = "UPDATE $table_session SET id_coach = $currentUserId  WHERE session_admin_id = '".$user_id."'";
        Database::query($sql);

        // Unsubscribe user from all sessions
        $sql = "DELETE FROM $table_session_user WHERE user_id = '".$user_id."'";
        Database::query($sql);

        // Delete user picture
        /* TODO: Logic about api_get_setting('split_users_upload_directory') == 'true'
        a user has 4 different sized photos to be deleted. */
        $user_info = api_get_user_info($user_id);
        if (strlen($user_info['picture_uri']) > 0) {
            $path = self::getUserPathById($user_id);
            $img_path = $path.$user_info['picture_uri'];
            if (file_exists($img_path))
                unlink($img_path);
        }

        // Delete the personal course categories
        $course_cat_table = Database::get_main_table(TABLE_USER_COURSE_CATEGORY);
        $sql = "DELETE FROM $course_cat_table WHERE user_id = '".$user_id."'";
        Database::query($sql);

        // Delete user from database
        $sql = "DELETE FROM $table_user WHERE user_id = '".$user_id."'";
        Database::query($sql);

        // Delete user from the admin table
        $sql = "DELETE FROM $table_admin WHERE user_id = '".$user_id."'";
        Database::query($sql);

        // Delete the personal agenda-items from this user
        $agenda_table = Database :: get_main_table(TABLE_PERSONAL_AGENDA);
        $sql = "DELETE FROM $agenda_table WHERE user = '".$user_id."'";
        Database::query($sql);

        $gradebook_results_table = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_RESULT);
        $sql = 'DELETE FROM '.$gradebook_results_table.' WHERE user_id = '.$user_id;
        Database::query($sql);

        $extraFieldValue = new ExtraFieldValue('user');
        $extraFieldValue->deleteValuesByItem($user_id);

        if (api_get_multiple_access_url()) {
            $url_id = api_get_current_access_url_id();
            UrlManager::delete_url_rel_user($user_id, $url_id);
        } else {
            //we delete the user from the url_id =1
            UrlManager::delete_url_rel_user($user_id, 1);
        }

        if (api_get_setting('allow_social_tool') == 'true') {
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
        return true;
    }

    /**
     * Deletes users completely. Can be called either as:
     * - UserManager :: delete_users(1, 2, 3); or
     * - UserManager :: delete_users(array(1, 2, 3));
     * @param array|int $ids
     * @return boolean  True if at least one user was successfuly deleted. False otherwise.
     * @author Laurent Opprecht
     * @uses UserManager::delete_user() to actually delete each user
     * @assert (null) === false
     * @assert (-1) === false
     * @assert (array(-1)) === false
     */
    static function delete_users($ids = array())
    {
        $result = false;
        $ids = is_array($ids) ? $ids : func_get_args();
        if (!is_array($ids) or count($ids) == 0) { return false; }
        $ids = array_map('intval', $ids);
        foreach ($ids as $id) {
            if (empty($id) or $id < 1) { continue; }
            $deleted = self::delete_user($id);
            $result = $deleted || $result;
        }

        return $result;
    }

    /**
     * Disable users. Can be called either as:
     * - UserManager :: deactivate_users(1, 2, 3);
     * - UserManager :: deactivate_users(array(1, 2, 3));
     * @param array|int $ids
     * @return boolean
     * @author Laurent Opprecht
     * @assert (null) === false
     * @assert (array(-1)) === false
     */
    static function deactivate_users($ids = array())
    {
        if (empty($ids)) {
            return false;
        }

        $table_user = Database :: get_main_table(TABLE_MAIN_USER);

        $ids = is_array($ids) ? $ids : func_get_args();
        $ids = array_map('intval', $ids);
        $ids = implode(',', $ids);

        $sql = "UPDATE $table_user SET active = 0 WHERE user_id IN ($ids)";
        $r = Database::query($sql);
        if ($r !== false) {
            Event::addEvent(LOG_USER_DISABLE, LOG_USER_ID, $ids);
        }
        return $r;
    }

    /**
     * Enable users. Can be called either as:
     * - UserManager :: activate_users(1, 2, 3);
     * - UserManager :: activate_users(array(1, 2, 3));
     * @param array|int IDs of the users to enable
     * @return boolean
     * @author Laurent Opprecht
     * @assert (null) === false
     * @assert (array(-1)) === false
     */
    static function activate_users($ids = array())
    {
        if (empty($ids)) {
            return false;
        }

        $table_user = Database :: get_main_table(TABLE_MAIN_USER);

        $ids = is_array($ids) ? $ids : func_get_args();
        $ids = array_map('intval', $ids);
        $ids = implode(',', $ids);

        $sql = "UPDATE $table_user SET active = 1 WHERE user_id IN ($ids)";
        $r = Database::query($sql);
        if ($r !== false) {
            Event::addEvent(LOG_USER_ENABLE,LOG_USER_ID,$ids);
        }
        return $r;
    }

    /**
     * Update user information with new openid
     * @param int $user_id
     * @param string $openid
     * @return boolean true if the user information was updated
     * @assert (false,'') === false
     * @assert (-1,'') === false
     */
    public static function update_openid($user_id, $openid)
    {
        $table_user = Database :: get_main_table(TABLE_MAIN_USER);
        if ($user_id != strval(intval($user_id)))
            return false;
        if ($user_id === false)
            return false;
        $sql = "UPDATE $table_user SET
                openid='".Database::escape_string($openid)."'";
        $sql .= " WHERE user_id='$user_id'";
        return Database::query($sql);
    }

    /**
     * Update user information with all the parameters passed to this function
     * @param int The ID of the user to be updated
     * @param string The user's firstname
     * @param string The user's lastname
     * @param string The user's username (login)
     * @param string The user's password
     * @param string The authentication source (default: "platform")
     * @param string The user's e-mail address
     * @param int The user's status
     * @param string The user's official code (usually just an internal institutional code)
     * @param string The user's phone number
     * @param string The user's picture URL (internal to the Chamilo directory)
     * @param int The user ID of the person who registered this user (optional, defaults to null)
     * @param int The department of HR in which the user is registered (optional, defaults to 0)
     * @param array A series of additional fields to add to this user as extra fields (optional, defaults to null)
     * @return boolean true if the user information was updated
     * @assert (false, false, false, false, false, false, false, false, false, false, false, false, false) === false
     */
    public static function update_user(
        $user_id,
        $firstname,
        $lastname,
        $username,
        $password = null,
        $auth_source = null,
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
        $reset_password = 0
    ) {
        $hook = HookUpdateUser::create();
        if (!empty($hook)) {
            $hook->notifyUpdateUser(HOOK_EVENT_TYPE_PRE);
        }
        global $_configuration;
        $original_password = $password;

        if (empty($user_id)) {
            return false;
        }
        $user_info = api_get_user_info($user_id, false, true);

        if ($reset_password == 0) {
            $password = null;
            $auth_source = $user_info['auth_source'];
        } elseif ($reset_password == 1) {
            $original_password = $password = api_generate_password();
            $auth_source = PLATFORM_AUTH_SOURCE;
        } elseif ($reset_password == 2) {
            $password = $password;
            $auth_source = PLATFORM_AUTH_SOURCE;
        } elseif ($reset_password == 3) {
            $password = $password;
            $auth_source = $auth_source;
        }

        if ($user_id != strval(intval($user_id))) {
            return false;
        }

        if ($user_id === false) {
            return false;
        }

        //Checking the user language
        $languages = api_get_languages();
        if (!in_array($language, $languages['folder'])) {
            $language = api_get_setting('platformLanguage');
        }

        $change_active = 0;
        if ($user_info['active'] != $active) {
            $change_active = 1;
        }

        $userManager = self::getManager();

        /** @var Chamilo\UserBundle\Entity\User $user */
        $user = self::getRepository()->find($user_id);

        if (empty($user)) {
            return false;
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
            ->setPictureUri($picture_uri)
            ->setExpirationDate($expiration_date)
            ->setActive($active)
            ->setHrDeptId($hr_dept_id)
        ;

        if (!is_null($password)) {
            $user->setPlainPassword($password);
        }

        $userManager->updateUser($user, true);

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
            $sender_name = api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname'), null, PERSON_NAME_EMAIL_ADDRESS);
            $email_admin = api_get_setting('emailAdministrator');

            if (api_is_multiple_url_enabled()) {
                $access_url_id = api_get_current_access_url_id();
                if ($access_url_id != -1) {
                    $url = api_get_access_url($access_url_id);
                    $emailbody = get_lang('Dear')." ".stripslashes(api_get_person_name($firstname, $lastname)).",\n\n".get_lang('YouAreReg')." ".api_get_setting('siteName')." ".get_lang('WithTheFollowingSettings')."\n\n".get_lang('Username')." : ".$username.(($reset_password > 0) ? "\n".get_lang('Pass')." : ".stripslashes($original_password) : "")."\n\n".get_lang('Address')." ".api_get_setting('siteName')." ".get_lang('Is')." : ".$url['url']."\n\n".get_lang('Problem')."\n\n".get_lang('SignatureFormula').",\n\n".api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname'))."\n".get_lang('Manager')." ".api_get_setting('siteName')."\nT. ".api_get_setting('administratorTelephone')."\n".get_lang('Email')." : ".api_get_setting('emailAdministrator');
                }
            } else {
                $emailbody = get_lang('Dear')." ".stripslashes(api_get_person_name($firstname, $lastname)).",\n\n".get_lang('YouAreReg')." ".api_get_setting('siteName')." ".get_lang('WithTheFollowingSettings')."\n\n".get_lang('Username')." : ".$username.(($reset_password > 0) ? "\n".get_lang('Pass')." : ".stripslashes($original_password) : "")."\n\n".get_lang('Address')." ".api_get_setting('siteName')." ".get_lang('Is')." : ".$_configuration['root_web']."\n\n".get_lang('Problem')."\n\n".get_lang('SignatureFormula').",\n\n".api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname'))."\n".get_lang('Manager')." ".api_get_setting('siteName')."\nT. ".api_get_setting('administratorTelephone')."\n".get_lang('Email')." : ".api_get_setting('emailAdministrator');
            }
            api_mail_html(
                $recipient_name,
                $email,
                $emailsubject,
                $emailbody,
                $sender_name,
                $email_admin
            );
        }

        if (!empty($hook)) {
            $hook->notifyUpdateUser(HOOK_EVENT_TYPE_POST);
        }

        return $user->getId();
    }

    /**
     * Disables or enables a user
     * @param int user_id
     * @param int Enable or disable
     * @return void
     * @assert (-1,0) === false
     * @assert (1,1) === true
     */
    private static function change_active_state($user_id, $active)
    {
        if (strval(intval($user_id)) != $user_id) {
            return false;
        }
        if ($user_id < 1) {
            return false;
        }
        $user_id = intval($user_id);
        $table_user = Database :: get_main_table(TABLE_MAIN_USER);
        $sql = "UPDATE $table_user SET active = '$active' WHERE user_id = '$user_id';";
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
     * Disables a user
     * @param int User id
     * @return bool
     * @uses UserManager::change_active_state() to actually disable the user
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
     * Enable a user
     * @param int User id
     * @return bool
     * @uses UserManager::change_active_state() to actually disable the user
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
     * mostly useful in the context of a web services-based sinchronization
     * @param string Original user id
     * @param string Original field name
     * @return int User id
     * @assert ('0','---') === 0
     */
    public static function get_user_id_from_original_id($original_user_id_value, $original_user_id_name)
    {
        $t_uf = Database::get_main_table(TABLE_EXTRA_FIELD);
        $t_ufv = Database::get_main_table(TABLE_EXTRA_FIELD_VALUES);
        $extraFieldType = EntityExtraField::USER_FIELD_TYPE;
        $sql = "SELECT item_id as user_id
                FROM $t_uf uf
                INNER JOIN $t_ufv ufv
                ON ufv.field_id=uf.id
                WHERE
                    variable='$original_user_id_name' AND
                    value='$original_user_id_value' AND
                    extra_field_type = $extraFieldType
                ";
        $res = Database::query($sql);
        $row = Database::fetch_object($res);
        if ($row) {
            return $row->user_id;
        } else {
            return 0;
        }
    }

    /**
     * Check if a username is available
     * @param string the wanted username
     * @return boolean true if the wanted username is available
     * @assert ('') === false
     * @assert ('xyzxyzxyz') === true
     */
    public static function is_username_available($username)
    {
        if (empty($username)) {
            return false;
        }
        $table_user = Database :: get_main_table(TABLE_MAIN_USER);
        $sql = "SELECT username FROM $table_user
                WHERE username = '".Database::escape_string($username)."'";
        $res = Database::query($sql);
        return Database::num_rows($res) == 0;
    }

    /**
     * Creates a username using person's names, i.e. creates jmontoya from Julio Montoya.
     * @param string $firstname The first name of the user.
     * @param string $lastname The last name of the user.
     * @param string $language (optional)    The language in which comparison is to be made. If language is omitted, interface language is assumed then.
     * @param string $encoding (optional)    The character encoding for the input names. If it is omitted, the platform character set will be used by default.
     * @return string Suggests a username that contains only ASCII-letters and digits, without check for uniqueness within the system.
     * @author Julio Montoya Armas
     * @author Ivan Tcholakov, 2009 - rework about internationalization.
     * @assert ('','') === false
     * @assert ('a','b') === 'ab'
     */
    public static function create_username($firstname, $lastname, $language = null, $encoding = null)
    {
        if (empty($firstname) && empty($lastname)) {
            return false;
        }

        $firstname = api_substr(preg_replace(USERNAME_PURIFIER, '', $firstname), 0, 1); // The first letter only.
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
     * @param string $firstname                The first name of a given user. If the second parameter $lastname is NULL, then this
     * parameter is treated as username which is to be checked for uniqueness and to be modified when it is necessary.
     * @param string $lastname                The last name of the user.
     * @param string $language (optional)    The language in which comparison is to be made. If language is omitted, interface language is assumed then.
     * @param string $encoding (optional)    The character encoding for the input names. If it is omitted, the platform character set will be used by default.
     * @return string                        Returns a username that contains only ASCII-letters and digits, and that is unique within the system.
     * Note: When the method is called several times with same parameters, its results look like the following sequence: ivan, ivan2, ivan3, ivan4, ...
     * @author Ivan Tcholakov, 2009
     */
    public static function create_unique_username($firstname, $lastname = null, $language = null, $encoding = null)
    {
        if (is_null($lastname)) {
            // In this case the actual input parameter $firstname should contain ASCII-letters and digits only.
            // For making this method tolerant of mistakes, let us transliterate and purify the suggested input username anyway.
            // So, instead of the sentence $username = $firstname; we place the following:
            $username = strtolower(preg_replace(USERNAME_PURIFIER, '', $firstname));
        } else {
            $username = self::create_username($firstname, $lastname, $language, $encoding);
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
     * @param $username string                The input username.
     * @param bool $strict (optional)        When this flag is TRUE, the result is guaranteed for full compliance, otherwise compliance may be partial. The default value is FALSE.
     * @param string $encoding (optional)    The character encoding for the input names. If it is omitted, the platform character set will be used by default.
     * @return string                        The resulting purified username.
     */
    public static function purify_username($username, $strict = false, $encoding = null)
    {
        if ($strict) {
            // 1. Conversion of unacceptable letters (latinian letters with accents for example) into ASCII letters in order they not to be totally removed.
            // 2. Applying the strict purifier.
            // 3. Length limitation.
            $return  = api_get_setting('login_is_email') == 'true' ? substr(preg_replace(USERNAME_PURIFIER_MAIL, '', $username), 0, USERNAME_MAX_LENGTH) : substr(preg_replace(USERNAME_PURIFIER, '', $username), 0, USERNAME_MAX_LENGTH);
            $return = URLify::transliterate($return);
            return $return;
        }
        // 1. Applying the shallow purifier.
        // 2. Length limitation.
        return substr(preg_replace(USERNAME_PURIFIER_SHALLOW, '', $username), 0, USERNAME_MAX_LENGTH);
    }

    /**
     * Checks whether the user id exists in the database
     *
     * @param int User id
     * @return bool True if user id was found, false otherwise
     */
    public static function is_user_id_valid($userId)
    {
        $resultData = Database::select(
            'COUNT(1) AS count',
            Database::get_main_table(TABLE_MAIN_USER),
            [
                'where' => ['id = ?' => intval($userId)]
            ],
            'first'
        );

        if ($resultData === false) {
            return false;
        }

        return $resultData['count'] > 0;
    }

    /**
     * Checks whether a given username matches to the specification strictly. The empty username is assumed here as invalid.
     * Mostly this function is to be used in the user interface built-in validation routines for providing feedback while usernames are enterd manually.
     * @param string $username The input username.
     * @param string $encoding (optional) The character encoding for the input names. If it is omitted, the platform character set will be used by default.
     * @return bool Returns TRUE if the username is valid, FALSE otherwise.
     */
    public static function is_username_valid($username, $encoding = null)
    {
        return !empty($username) && $username == self::purify_username($username, true);
    }

    /**
     * Checks whether a username is empty. If the username contains whitespace characters, such as spaces, tabulators, newlines, etc.,
     * it is assumed as empty too. This function is safe for validation unpurified data (during importing).
     * @param string $username The given username.
     * @return bool  Returns TRUE if length of the username exceeds the limit, FALSE otherwise.
     */
    public static function is_username_empty($username)
    {
        return (strlen(self::purify_username($username, false)) == 0);
    }

    /**
     * Checks whether a username is too long or not.
     * @param string $username The given username, it should contain only ASCII-letters and digits.
     * @return bool Returns TRUE if length of the username exceeds the limit, FALSE otherwise.
     */
    public static function is_username_too_long($username)
    {
        return (strlen($username) > USERNAME_MAX_LENGTH);
    }

    /**
    * Get the users by ID
    * @param array $ids student ids
    * @param string $active
    * @param string $order
    * @param string $limit
    * @return array $result student information
    */
    public static function get_user_list_by_ids($ids = array(), $active = null, $order = null, $limit = null)
    {
        if (empty($ids)) {
            return array();
        }

        $ids = is_array($ids) ? $ids : array($ids);
        $ids = array_map('intval', $ids);
        $ids = implode(',', $ids);

        $tbl_user = Database::get_main_table(TABLE_MAIN_USER);
        $sql = "SELECT * FROM $tbl_user WHERE user_id IN ($ids)";
        if (!is_null($active)) {
            $sql .= ' AND active='.($active ? '1' : '0');
        }

        if (!is_null($order)) {
            $order = Database::escape_string($order);
            $sql .= ' ORDER BY ' . $order;
        }

        if (!is_null($limit)) {
            $limit = Database::escape_string($limit);
            $sql .= ' LIMIT ' . $limit;
        }

        $rs = Database::query($sql);
        $result = array();
        while ($row = Database::fetch_array($rs)) {
            $result[] = $row;
        }
        return $result;
    }

    /**
     * Get a list of users of which the given conditions match with an = 'cond'
     * @param array $conditions a list of condition (exemple : status=>STUDENT)
     * @param array $order_by a list of fields on which sort
     * @return array An array with all users of the platform.
     * @todo optional course code parameter, optional sorting parameters...
     * @todo security filter order by
     */
    public static function get_user_list($conditions = array(), $order_by = array(), $limit_from = false, $limit_to = false)
    {
        $user_table = Database :: get_main_table(TABLE_MAIN_USER);
        $return_array = array();
        $sql_query = "SELECT * FROM $user_table";
        if (count($conditions) > 0) {
            $sql_query .= ' WHERE ';
            foreach ($conditions as $field => $value) {
                $field = Database::escape_string($field);
                $value = Database::escape_string($value);
                $sql_query .= "$field = '$value'";
            }
        }
        if (count($order_by) > 0) {
            $sql_query .= ' ORDER BY '.Database::escape_string(implode(',', $order_by), null, false);
        }

        if (is_numeric($limit_from) && is_numeric($limit_from)) {
            $limit_from = intval($limit_from);
            $limit_to = intval($limit_to);
            $sql_query .= " LIMIT $limit_from, $limit_to";
        }
        $sql_result = Database::query($sql_query);
        while ($result = Database::fetch_array($sql_result)) {
            $return_array[] = $result;
        }
        return $return_array;
    }

    /**
     * Get a list of users of which the given conditions match with a LIKE '%cond%'
     * @param array $conditions a list of condition (exemple : status=>STUDENT)
     * @param array $order_by a list of fields on which sort
     * @return array An array with all users of the platform.
     * @todo optional course code parameter, optional sorting parameters...
     * @todo security filter order_by
     */
    public static function get_user_list_like($conditions = array(), $order_by = array(), $simple_like = false, $condition = 'AND')
    {
        $user_table = Database :: get_main_table(TABLE_MAIN_USER);
        $return_array = array();
        $sql_query = "SELECT * FROM $user_table";
        if (count($conditions) > 0) {
            $sql_query .= ' WHERE ';
            $temp_conditions = array();
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
                $sql_query .= implode(' '.$condition.' ', $temp_conditions);
            }
        }
        if (count($order_by) > 0) {
            $sql_query .= ' ORDER BY '.Database::escape_string(implode(',', $order_by), null, false);
        }
        $sql_result = Database::query($sql_query);
        while ($result = Database::fetch_array($sql_result)) {
            $return_array[] = $result;
        }
        return $return_array;
    }

    /**
     * Get user information
     * @param     string     The username
     * @deprecated use api_get_user_info
     * @return array All user information as an associative array
     */
    public static function get_user_info($username)
    {
        $user_table = Database :: get_main_table(TABLE_MAIN_USER);
        $username = Database::escape_string($username);
        $sql = "SELECT * FROM $user_table WHERE username='".$username."'";
        $res = Database::query($sql);
        if (Database::num_rows($res) > 0) {
            return Database::fetch_array($res);
        }
        return false;
    }

    /**
     * Get user picture URL or path from user ID (returns an array).
     * The return format is a complete path, enabling recovery of the directory
     * with dirname() or the file with basename(). This also works for the
     * functions dealing with the user's productions, as they are located in
     * the same directory.
     * @param   integer   $id User ID
     * @param   string    $type Type of path to return (can be 'system', 'web')
     * @param   array $userInfo user information to avoid query the DB
     * returns the /main/img/unknown.jpg image set it at true
     *
     * @return    array     Array of 2 elements: 'dir' and 'file' which contain
     * the dir and file as the name implies if image does not exist it will
     * return the unknow image if anonymous parameter is true if not it returns an empty array
     */
    public static function get_user_picture_path_by_id($id, $type = 'web', $userInfo = [])
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

        $anonymousPath = array(
            'dir' => $base.'img/',
            'file' => 'unknown.jpg',
            'email' => '',
        );

        if (empty($id) || empty($type)) {
            return $anonymousPath;
        }

        $id = intval($id);
        if (empty($userInfo)) {
            $user_table = Database:: get_main_table(TABLE_MAIN_USER);
            $sql = "SELECT email, picture_uri FROM $user_table
                    WHERE user_id=".$id;
            $res = Database::query($sql);

            if (!Database::num_rows($res)) {
                return $anonymousPath;
            }
            $user = Database::fetch_array($res);
        } else {
            $user = $userInfo;
        }

        $pictureFilename = trim($user['picture_uri']);

        $dir = self::getUserPathById($id, $type);

        return array(
            'dir' => $dir,
            'file' => $pictureFilename,
            'email' => $user['email'],
        );
    }

    /**
     * Get user path from user ID (returns an array).
     * The return format is a complete path to a folder ending with "/"
     * In case the first level of subdirectory of users/ does not exist, the
     * function will attempt to create it. Probably not the right place to do it
     * but at least it avoids headaches in many other places.
     * @param   integer $id User ID
     * @param   string  $type Type of path to return (can be 'system', 'web', 'rel', 'last')
     * @return  string  User folder path (i.e. /var/www/chamilo/app/upload/users/1/1/)
     */
    public static function getUserPathById($id, $type)
    {
        $id = intval($id);
        if (!$id) {
            return null;
        }

        $userPath = "users/$id/";
        if (api_get_setting('split_users_upload_directory') === 'true') {
            $userPath = 'users/'.substr((string) $id, 0, 1).'/'.$id.'/';
            // In exceptional cases, on some portals, the intermediate base user
            // directory might not have been created. Make sure it is before
            // going further.
            $rootPath = api_get_path(SYS_UPLOAD_PATH) . 'users/' . substr((string) $id, 0, 1);
            if (!is_dir($rootPath)) {
                $perm = api_get_permissions_for_new_directories();
                try {
                    mkdir($rootPath, $perm);
                } catch (Exception $e) {
                    //
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
            case 'rel': // Relative to the document root (e.g. app/upload/users/1/13/)
                $userPath = api_get_path(REL_UPLOAD_PATH).$userPath;
                break;
            case 'last': // Only the last part starting with users/
                break;
        }

        return $userPath;
    }

    /**
     * Gets the current user image
     * @param string $user_id
     * @param int $size it can be USER_IMAGE_SIZE_SMALL,
     * USER_IMAGE_SIZE_MEDIUM, USER_IMAGE_SIZE_BIG or  USER_IMAGE_SIZE_ORIGINAL
     * @param bool $addRandomId
     * @param array $userInfo to avoid query the DB
     *
     * @return string
     */
    public static function getUserPicture(
        $user_id,
        $size = USER_IMAGE_SIZE_MEDIUM,
        $addRandomId = true,
        $userInfo = []
    ) {
        $imageWebPath = self::get_user_picture_path_by_id($user_id, 'web', $userInfo);
        $pictureWebFile = $imageWebPath['file'];
        $pictureWebDir = $imageWebPath['dir'];

        $pictureAnonymous = 'icons/128/unknown.png';
        $gravatarSize = 22;
        $realSizeName = 'small_';

        switch ($size) {
            case USER_IMAGE_SIZE_SMALL:
                $pictureAnonymous = 'icons/22/unknown.png';
                $realSizeName = 'small_';
                $gravatarSize = 22;
                break;
            case USER_IMAGE_SIZE_MEDIUM:
                $pictureAnonymous = 'icons/64/unknown.png';
                $realSizeName = 'medium_';
                $gravatarSize = 50;
                break;
            case USER_IMAGE_SIZE_ORIGINAL:
                $pictureAnonymous = 'icons/128/unknown.png';
                $realSizeName = '';
                $gravatarSize = 108;
                break;
            case USER_IMAGE_SIZE_BIG:
                $pictureAnonymous = 'icons/128/unknown.png';
                $realSizeName = 'big_';
                $gravatarSize = 200;
                break;
        }

        $gravatarEnabled = api_get_setting('gravatar_enabled');

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

        $anonymousPath = api_get_path(WEB_CODE_PATH).'img/'.$pictureAnonymous;

        if ($pictureWebFile == 'unknown.jpg' || empty($pictureWebFile)) {

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
     * Note: This method relies on configuration setting from main/inc/conf/profile.conf.php
     * @param   int $user_id The user internal identification number.
     * @param   string $file The common file name for the newly created photos.
     *                       It will be checked and modified for compatibility with the file system.
     *                       If full name is provided, path component is ignored.
     *                       If an empty name is provided, then old user photos are deleted only,
     * @see     UserManager::delete_user_picture() as the prefered way for deletion.
     * @param   string $source_file The full system name of the image from which user photos will be created.
     * @return  string/bool Returns the resulting common file name of created images which usually should be stored in database.
     * When deletion is requested returns empty string. In case of internal error or negative validation returns FALSE.
     */
    public static function update_user_picture($user_id, $file = null, $source_file = null)
    {
        if (empty($user_id)) {
            return false;
        }
        $delete = empty($file);
        if (empty($source_file)) {
            $source_file = $file;
        }

        // User-reserved directory where photos have to be placed.
        $path_info = self::get_user_picture_path_by_id($user_id, 'system');

        $path = $path_info['dir'];
        // If this directory does not exist - we create it.
        if (!file_exists($path)) {
            mkdir($path, api_get_permissions_for_new_directories(), true);
        }

        // The old photos (if any).
        $old_file = $path_info['file'];

        // Let us delete them.
        if (!empty($old_file)) {
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
        if (KEEP_THE_NAME_WHEN_CHANGE_IMAGE && !empty($old_file)) {
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

        // Storing the new photos in 4 versions with various sizes.

        $small = self::resize_picture($source_file, 22);
        $medium = self::resize_picture($source_file, 85);
        $normal = self::resize_picture($source_file, 200);

        $big = new Image($source_file); // This is the original picture.

        $ok = $small && $small->send_image($path.'small_'.$filename) &&
            $medium && $medium->send_image($path.'medium_'.$filename) &&
            $normal && $normal->send_image($path.$filename) &&
            $big && $big->send_image($path.'big_'.$filename);
        return $ok ? $filename : false;
    }

    /**
     * Update User extra field file type into {user_folder}/{$extra_field}
     * @param int $user_id          The user internal identification number
     * @param string $extra_field   The $extra_field The extra field name
     * @param null $file            The filename
     * @param null $source_file     The temporal filename
     * @return bool|null            return filename if success, but false
     */
    public static function update_user_extra_file($user_id, $extra_field = '', $file = null, $source_file = null)
    {
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
            $path .= $extra_field . '/';
        }
        // If this directory does not exist - we create it.
        if (!file_exists($path)) {
            @mkdir($path, api_get_permissions_for_new_directories(), true);
        }

        if (filter_extension($file)) {
            if (@move_uploaded_file($source_file,$path.$file)) {
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
     * Note: This method relies on configuration setting from main/inc/conf/profile.conf.php
     * @param int $user_id            The user internal identitfication number.
     * @return string/bool            Returns empty string on success, FALSE on error.
     */
    public static function delete_user_picture($user_id)
    {
        return self::update_user_picture($user_id);
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
     * @param    int $user_id    User id
     * @param    $force    Optional parameter to force building after a removal request
     *
     * @return    A string containing the XHTML code to dipslay the production list, or FALSE
     */
    public static function build_production_list($user_id, $force = false, $showdelete = false)
    {
        if (!$force && !empty($_POST['remove_production'])) {
            return true; // postpone reading from the filesystem
        }
        $productions = self::get_user_productions($user_id);

        if (empty($productions)) {
            return false;
        }

        $production_path = self::get_user_picture_path_by_id($user_id, 'web');
        $production_dir = $production_path['dir'];
        $del_image = api_get_path(WEB_CODE_PATH).'img/delete.png';
        $add_image = api_get_path(WEB_CODE_PATH).'img/archive.png';
        $del_text = get_lang('Delete');
        $production_list = '';
        if (count($productions) > 0) {
            $production_list = '<div class="files-production"> <ul id="productions">';
            foreach ($productions as $file) {
                $production_list .= '<li><img src="'.$add_image.'" /><a href="'.$production_dir.urlencode($file).'" target="_blank">'.htmlentities($file).'</a>';
                if ($showdelete) {
                    $production_list .= '&nbsp;&nbsp;<input style="width:16px;" type="image" name="remove_production['.urlencode($file).']" src="'.$del_image.'" alt="'.$del_text.'" title="'.$del_text.' '.htmlentities($file).'" onclick="javascript: return confirmation(\''.htmlentities($file).'\');" /></li>';
                }
            }
            $production_list .= '</ul></div>';
        }

        return $production_list;
    }

    /**
     * Returns an array with the user's productions.
     *
     * @param    $user_id    User id
     * @return   array  An array containing the user's productions
     */
    public static function get_user_productions($user_id)
    {
        $production_path = self::get_user_picture_path_by_id($user_id, 'system');
        $production_repository = $production_path['dir'];
        $productions = array();

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
     * @param   int $user_id        User id
     * @param   string $production    The production to remove
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
     * Update an extra field value for a given user
     * @param    integer   $userId User ID
     * @param    string    $variable Field variable name
     * @param    string    $value Field value
     *
     * @return    boolean    true if field updated, false otherwise
     */
    public static function update_extra_field_value($userId, $variable, $value = '')
    {
        $extraFieldValue = new ExtraFieldValue('user');
        $params = [
            'item_id' => $userId,
            'variable' => $variable,
            'value' => $value
        ];
        $extraFieldValue->save($params);
    }

    /**
     * Get an array of extra fields with field details (type, default value and options)
     * @param    integer    Offset (from which row)
     * @param    integer    Number of items
     * @param    integer    Column on which sorting is made
     * @param    string    Sorting direction
     * @param    boolean    Optional. Whether we get all the fields or just the visible ones
     * @param    int        Optional. Whether we get all the fields with field_filter 1 or 0 or everything
     * @return    array    Extra fields details (e.g. $list[2]['type'], $list[4]['options'][2]['title']
     */
    public static function get_extra_fields(
        $from = 0,
        $number_of_items = 0,
        $column = 5,
        $direction = 'ASC',
        $all_visibility = true,
        $field_filter = null
    ) {
        $fields = array();
        $t_uf = Database :: get_main_table(TABLE_EXTRA_FIELD);
        $t_ufo = Database :: get_main_table(TABLE_EXTRA_FIELD_OPTIONS);
        $columns = array(
            'id',
            'variable',
            'field_type',
            'display_text',
            'default_value',
            'field_order',
            'filter'
        );
        $column = intval($column);
        $sort_direction = '';
        if (in_array(strtoupper($direction), array('ASC', 'DESC'))) {
            $sort_direction = strtoupper($direction);
        }
        $extraFieldType = EntityExtraField::USER_FIELD_TYPE;
        $sqlf = "SELECT * FROM $t_uf WHERE extra_field_type = $extraFieldType ";
        if (!$all_visibility) {
            $sqlf .= " AND visible = 1 ";
        }
        if (!is_null($field_filter)) {
            $field_filter = intval($field_filter);
            $sqlf .= " AND filter = $field_filter ";
        }
        $sqlf .= " ORDER BY ".$columns[$column]." $sort_direction ";
        if ($number_of_items != 0) {
            $sqlf .= " LIMIT ".intval($from).','.intval($number_of_items);
        }
        $resf = Database::query($sqlf);
        if (Database::num_rows($resf) > 0) {
            while ($rowf = Database::fetch_array($resf)) {
                $fields[$rowf['id']] = array(
                    0 => $rowf['id'],
                    1 => $rowf['variable'],
                    2 => $rowf['field_type'],
                    3 => (empty($rowf['display_text']) ? '' : $rowf['display_text']),
                    4 => $rowf['default_value'],
                    5 => $rowf['field_order'],
                    6 => $rowf['visible'],
                    7 => $rowf['changeable'],
                    8 => $rowf['filter'],
                    9 => array(),
                    10 => '<a name="'.$rowf['id'].'"></a>',
                );

                $sqlo = "SELECT * FROM $t_ufo
                         WHERE field_id = ".$rowf['id']."
                         ORDER BY option_order ASC";
                $reso = Database::query($sqlo);
                if (Database::num_rows($reso) > 0) {
                    while ($rowo = Database::fetch_array($reso)) {
                        $fields[$rowf['id']][9][$rowo['id']] = array(
                            0 => $rowo['id'],
                            1 => $rowo['option_value'],
                            2 => (empty($rowo['display_text']) ? '' : $rowo['display_text']),
                            3 => $rowo['option_order']
                        );
                    }
                }
            }
        }

        return $fields;
    }

    /**
     * Build a list of extra file already uploaded in $user_folder/{$extra_field}/
     * @param $user_id
     * @param $extra_field
     * @param bool $force
     * @param bool $showdelete
     * @return bool|string
     */
    public static function build_user_extra_file_list($user_id, $extra_field, $force = false, $showdelete = false)
    {
        if (!$force && !empty($_POST['remove_'.$extra_field])) {
            return true; // postpone reading from the filesystem
        }
        $extra_files = self::get_user_extra_files($user_id, $extra_field);
        if (empty($extra_files)) {
            return false;
        }

        $path_info = self::get_user_picture_path_by_id($user_id, 'web');
        $path = $path_info['dir'];
        $del_image = api_get_path(WEB_CODE_PATH).'img/delete.png';
        $del_text = get_lang('Delete');
        $extra_file_list = '';
        if (count($extra_files) > 0) {
            $extra_file_list = '<div class="files-production"><ul id="productions">';
            foreach ($extra_files as $file) {
                $filename = substr($file,strlen($extra_field)+1);
                $extra_file_list .= '<li>'.Display::return_icon('archive.png').'<a href="'.$path.$extra_field.'/'.urlencode($filename).'" target="_blank">'.htmlentities($filename).'</a> ';
                if ($showdelete) {
                    $extra_file_list .= '<input style="width:16px;" type="image" name="remove_extra_' . $extra_field . '['.urlencode($file).']" src="'.$del_image.'" alt="'.$del_text.'" title="'.$del_text.' '.htmlentities($filename).'" onclick="javascript: return confirmation(\''.htmlentities($filename).'\');" /></li>';
                }
            }
            $extra_file_list .= '</ul></div>';
        }

        return $extra_file_list;
    }

    /**
     * Get valid filenames in $user_folder/{$extra_field}/
     * @param $user_id
     * @param $extra_field
     * @param bool $full_path
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
     * Remove an {$extra_file} from the user folder $user_folder/{$extra_field}/
     * @param $user_id
     * @param $extra_field
     * @param $extra_file
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
     * Creates a new extra field
     * @param    string    $variable Field's internal variable name
     * @param    int       $fieldType  Field's type
     * @param    string    $displayText Field's language var name
     * @param    string    $default Field's default value
     * @return int
     */
    public static function create_extra_field($variable, $fieldType, $displayText, $default)
    {
        $extraField = new ExtraField('user');
        $params = [
            'variable' => $variable,
            'field_type' => $fieldType,
            'display_text' => $displayText,
            'default_value' => $default
        ];

        return $extraField->save($params);
    }

    /**
     * Check if a field is available
     * @param    string    th$variable
     * @return    boolean
     */
    public static function is_extra_field_available($variable)
    {
        $extraField = new ExtraField('user');
        $data = $extraField->get_handler_field_info_by_field_variable($variable);
        return empty($data) ? true : false;
    }

    /**
     * Gets user extra fields data
     * @param    integer    User ID
     * @param    boolean    Whether to prefix the fields indexes with "extra_" (might be used by formvalidator)
     * @param    boolean    Whether to return invisible fields as well
     * @param    boolean    Whether to split multiple-selection fields or not
     * @return    array    Array of fields => value for the given user
     */
    public static function get_extra_user_data(
        $user_id,
        $prefix = false,
        $all_visibility = true,
        $splitmultiple = false,
        $field_filter = null
    ) {
        // A sanity check.
        if (empty($user_id)) {
            $user_id = 0;
        } else {
            if ($user_id != strval(intval($user_id)))
                return array();
        }
        $extra_data = array();
        $t_uf = Database::get_main_table(TABLE_EXTRA_FIELD);
        $t_ufv = Database::get_main_table(TABLE_EXTRA_FIELD_VALUES);
        $user_id = intval($user_id);
        $sql = "SELECT f.id as id, f.variable as fvar, f.field_type as type
                FROM $t_uf f
                WHERE
                    extra_field_type = ".EntityExtraField::USER_FIELD_TYPE."
                ";
        $filter_cond = '';

        if (!$all_visibility) {
            if (isset($field_filter)) {
                $field_filter = intval($field_filter);
                $filter_cond .= " AND filter = $field_filter ";
            }
            $sql .= " AND f.visible = 1 $filter_cond ";
        } else {
            if (isset($field_filter)) {
                $field_filter = intval($field_filter);
                $sql .= " AND filter = $field_filter ";
            }
        }

        $sql .= " ORDER BY f.field_order";

        $res = Database::query($sql);
        if (Database::num_rows($res) > 0) {
            while ($row = Database::fetch_array($res)) {
                if ($row['type'] == self::USER_FIELD_TYPE_TAG) {
                    $tags = self::get_user_tags_to_string($user_id, $row['id'], false);
                    $extra_data['extra_'.$row['fvar']] = $tags;
                } else {
                    $sqlu = "SELECT value as fval
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
                        if ($row['type'] == self::USER_FIELD_TYPE_SELECT_MULTIPLE) {
                            $fval = explode(';', $rowu['fval']);
                        }
                    } else {
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

    /** Get extra user data by field
     * @param int    user ID
     * @param string the internal variable name of the field
     * @return array with extra data info of a user i.e array('field_variable'=>'value');
     */
    public static function get_extra_user_data_by_field(
        $user_id,
        $field_variable,
        $prefix = false,
        $all_visibility = true,
        $splitmultiple = false
    ) {
        // A sanity check.
        if (empty($user_id)) {
            $user_id = 0;
        } else {
            if ($user_id != strval(intval($user_id)))
                return array();
        }
        $extra_data = array();
        $t_uf = Database::get_main_table(TABLE_EXTRA_FIELD);
        $t_ufv = Database::get_main_table(TABLE_EXTRA_FIELD_VALUES);
        $user_id = intval($user_id);

        $sql = "SELECT f.id as id, f.variable as fvar, f.field_type as type
                FROM $t_uf f
                WHERE f.variable = '$field_variable' ";

        if (!$all_visibility) {
            $sql .= " AND f.visible = 1 ";
        }

        $sql .= " AND extra_field_type = ".EntityExtraField::USER_FIELD_TYPE;

        $sql .= " ORDER BY f.field_order";

        $res = Database::query($sql);
        if (Database::num_rows($res) > 0) {
            while ($row = Database::fetch_array($res)) {
                $sqlu = "SELECT value as fval FROM $t_ufv v INNER JOIN $t_uf f
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
     * Get the extra field information for a certain field (the options as well)
     * @param  int     $variable The name of the field we want to know everything about
     * @return array   Array containing all the information about the extra profile field
     * (first level of array contains field details, then 'options' sub-array contains options details,
     * as returned by the database)
     * @author Julio Montoya
     * @since v1.8.6
     */
    public static function get_extra_field_information_by_name($variable)
    {
        $extraField = new ExtraField('user');

        return $extraField->get_handler_field_info_by_field_variable($variable);
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
     * Get all the extra field information of a certain field (also the options)
     *
     * @param int $field_name the name of the field we want to know everything of
     * @return array $return containing all th information about the extra profile field
     * @author Julio Montoya
     * @deprecated
     * @since v1.8.6
     */
    public static function get_extra_field_information($fieldId)
    {
        $extraField = new ExtraField('user');

        return $extraField->getFieldInfoByFieldId($fieldId);
    }

    /** Get extra user data by value
     * @param string the internal variable name of the field
     * @param string the internal value of the field
     * @return array with extra data info of a user i.e array('field_variable'=>'value');
     */
    public static function get_extra_user_data_by_value($field_variable, $field_value, $all_visibility = true)
    {
        $extraField = new ExtraFieldValue('user');

        $data = $extraField->get_values_by_handler_and_field_variable(
            $field_variable,
            $field_value,
            null,
            true,
            intval($all_visibility)
        );

        $result = [];
        if (!empty($data)) {
            foreach ($data as $data) {
                $result[] = $data['item_id'];
            }
        }

        return $result;
    }

    /**
     * Get extra user data by field variable
     * @param string    field variable
     * @return array    data
     */
    public static function get_extra_user_data_by_field_variable($field_variable)
    {
        $extra_information_by_variable = self::get_extra_field_information_by_name($field_variable);
        $field_id = intval($extra_information_by_variable['id']);

        $extraField = new ExtraFieldValue('user');
        $data = $extraField->getValuesByFieldId($field_id);

        if (!empty($data) > 0) {
            foreach ($data as $row) {
                $user_id = $row['item_id'];
                $data[$user_id] = $row;
            }
        }

        return $data;
    }

    /**
     * Gives a list of [session_category][session_id] for the current user.
     * @param integer $user_id
     * @param boolean whether to fill the first element or not (to give space for courses out of categories)
     * @param boolean  optional true if limit time from session is over, false otherwise
     * @return array  list of statuses [session_category][session_id]
     * @todo ensure multiple access urls are managed correctly
     */
    public static function get_sessions_by_category(
        $user_id,
        $is_time_over = true,
        $ignore_visibility_for_admins = false
    ) {
        // Database Table Definitions
        $tbl_session = Database :: get_main_table(TABLE_MAIN_SESSION);
        $tbl_session_course_user = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $tbl_session_category = Database :: get_main_table(TABLE_MAIN_SESSION_CATEGORY);

        if ($user_id != strval(intval($user_id))) {
            return array();
        }

        // Get the list of sessions per user
        $now = api_get_utc_datetime();

        $sql = "SELECT DISTINCT
                    session.id,
                    session.name,
                    session.access_start_date,
                    session.access_end_date,
                    session_category_id,
                    session_category.name as session_category_name,
                    session_category.date_start session_category_date_start,
                    session_category.date_end session_category_date_end,
                    coach_access_start_date,
                    coach_access_end_date
              FROM $tbl_session as session
                  LEFT JOIN $tbl_session_category session_category
                  ON (session_category_id = session_category.id)
                  LEFT JOIN $tbl_session_course_user as session_rel_course_user
                  ON (session_rel_course_user.session_id = session.id)
              WHERE (
                    session_rel_course_user.user_id = $user_id OR
                    session.id_coach = $user_id
              )
              ORDER BY session_category_name, name";

        $result = Database::query($sql);
        $categories = array();

        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result, 'ASSOC')) {

                // User portal filters:
                if ($is_time_over) {
                    // History
                    if (empty($row['access_end_date']) || $row['access_end_date'] == '0000-00-00 00:00:00') {
                        continue;
                    }

                    if (isset($row['access_end_date'])) {
                        if ($row['access_end_date'] > $now) {
                            continue;
                        }

                    }
                } else {
                    // Current user portal
                    if (api_is_allowed_to_create_course()) {
                        // Teachers can access the session depending in the access_coach date
                    } else {
                        if (isset($row['access_end_date']) && $row['access_end_date'] != '0000-00-00 00:00:00') {
                            if ($row['access_end_date'] <= $now) {
                                continue;
                            }
                        }
                    }
                }

                $categories[$row['session_category_id']]['session_category'] = array(
                    'id' => $row['session_category_id'],
                    'name' => $row['session_category_name'],
                    'date_start' => $row['session_category_date_start'],
                    'date_end' => $row['session_category_date_end']
                );

                $session_id = $row['id'];

                $courseList = UserManager::get_courses_list_by_session(
                    $user_id,
                    $row['id']
                );

                // Session visibility.
                $visibility = api_get_session_visibility(
                    $session_id,
                    null,
                    $ignore_visibility_for_admins
                );

                // Course Coach session visibility.
                $blockedCourseCount = 0;
                $closedVisibilityList = array(
                    COURSE_VISIBILITY_CLOSED,
                    COURSE_VISIBILITY_HIDDEN
                );

                foreach ($courseList as $course) {
                    // Checking session visibility
                    $visibility = api_get_session_visibility(
                        $session_id,
                        $course['real_id'],
                        $ignore_visibility_for_admins
                    );

                    $courseIsVisible = !in_array($course['visibility'], $closedVisibilityList);

                    if ($courseIsVisible == false || $visibility == SESSION_INVISIBLE) {
                        $blockedCourseCount++;
                    }
                }

                // If all courses are blocked then no show in the list.
                if ($blockedCourseCount == count($courseList)) {
                    $visibility = SESSION_INVISIBLE;
                }

                switch ($visibility) {
                    case SESSION_VISIBLE_READ_ONLY:
                    case SESSION_VISIBLE:
                    case SESSION_AVAILABLE:
                        break;
                    case SESSION_INVISIBLE:
                        continue(2);
                }

                $categories[$row['session_category_id']]['sessions'][$row['id']] = array(
                    'session_name' => $row['name'],
                    'session_id' => $row['id'],
                    'access_start_date' => $row['access_start_date'],
                    'access_end_date' => $row['access_end_date'],
                    'coach_access_start_date' => $row['coach_access_start_date'],
                    'coach_access_end_date' => $row['coach_access_end_date'],
                    'courses' => $courseList
                );
            }
        }

        return $categories;
    }

    /**
     * Gives a list of [session_id-course_code] => [status] for the current user.
     * @param integer $user_id
     * @return array  list of statuses (session_id-course_code => status)
     */
    public static function get_personal_session_course_list($user_id)
    {
        // Database Table Definitions
        $tbl_course = Database :: get_main_table(TABLE_MAIN_COURSE);
        $tbl_user = Database :: get_main_table(TABLE_MAIN_USER);
        $tbl_session = Database :: get_main_table(TABLE_MAIN_SESSION);
        $tbl_session_user = Database :: get_main_table(TABLE_MAIN_SESSION_USER);
        $tbl_course_user = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
        $tbl_session_course_user = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

        if ($user_id != strval(intval($user_id))) {
            return array();
        }

        // We filter the courses from the URL
        $join_access_url = $where_access_url = '';

        if (api_get_multiple_access_url()) {
            $access_url_id = api_get_current_access_url_id();
            if ($access_url_id != -1) {
                $tbl_url_course = Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
                $join_access_url = "LEFT JOIN $tbl_url_course url_rel_course ON url_rel_course.c_id = course.id";
                $where_access_url = " AND access_url_id = $access_url_id ";
            }
        }

        // Courses in which we subscribed out of any session
        $tbl_user_course_category = Database :: get_main_table(TABLE_USER_COURSE_CATEGORY);

        $sql = "SELECT
                    course.code,
                    course_rel_user.status course_rel_status,
                    course_rel_user.sort sort,
                    course_rel_user.user_course_cat user_course_cat
                 FROM ".$tbl_course_user." course_rel_user
                 LEFT JOIN ".$tbl_course." course
                 ON course.id = course_rel_user.c_id
                 LEFT JOIN ".$tbl_user_course_category." user_course_category
                 ON course_rel_user.user_course_cat = user_course_category.id
                 $join_access_url
                 WHERE
                    course_rel_user.user_id = '".$user_id."' AND
                    course_rel_user.relation_type <> ".COURSE_RELATION_TYPE_RRHH."
                    $where_access_url
                 ORDER BY user_course_category.sort, course_rel_user.sort, course.title ASC";

        $course_list_sql_result = Database::query($sql);

        $personal_course_list = array();
        if (Database::num_rows($course_list_sql_result) > 0) {
            while ($result_row = Database::fetch_array($course_list_sql_result, 'ASSOC')) {
                $course_info = api_get_course_info($result_row['code']);
                $result_row['course_info'] = $course_info;
                $personal_course_list[] = $result_row;
            }
        }

        $coachCourseConditions = null;

        // Getting sessions that are related to a coach in the session_rel_course_rel_user table

        if (api_is_allowed_to_create_course()) {
            $sessionListFromCourseCoach = array();
            $sql =" SELECT DISTINCT session_id
                    FROM $tbl_session_course_user
                    WHERE user_id = $user_id AND status = 2 ";

            $result = Database::query($sql);
            if (Database::num_rows($result)) {
                $result = Database::store_result($result);
                foreach ($result as $session) {
                    $sessionListFromCourseCoach[]= $session['session_id'];
                }
            }
            if (!empty($sessionListFromCourseCoach)) {
                $condition = implode("','", $sessionListFromCourseCoach);
                $coachCourseConditions = " OR ( s.id IN ('$condition'))";
            }
        }

        // Get the list of sessions where the user is subscribed
        // This is divided into two different queries
        $sessions = array();
        $sql = "SELECT DISTINCT s.id, name, access_start_date, access_end_date
                FROM $tbl_session_user, $tbl_session s
                WHERE (
                    session_id = s.id AND
                    user_id = $user_id AND
                    relation_type <> ".SESSION_RELATION_TYPE_RRHH."
                )
                $coachCourseConditions
                ORDER BY access_start_date, access_end_date, name";

        $result = Database::query($sql);
        if (Database::num_rows($result)>0) {
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
        if (Database::num_rows($result)>0) {
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
                $id_session = $enreg['id'];
                $personal_course_list_sql = "SELECT DISTINCT
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
                            ON user.user_id = session_course_user.user_id OR session.id_coach = user.user_id
                    WHERE
                        session_course_user.session_id = $id_session AND (
                            (session_course_user.user_id = $user_id AND session_course_user.status = 2)
                            OR session.id_coach = $user_id
                        )
                    ORDER BY i";
                $course_list_sql_result = Database::query($personal_course_list_sql);

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
            $personal_course_list_sql = "SELECT DISTINCT
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
                INNER JOIN $tbl_session as session ON session_course_user.session_id = session.id
                LEFT JOIN $tbl_user as user ON user.user_id = session_course_user.user_id
            WHERE session_course_user.user_id = $user_id
            ORDER BY i";

            $course_list_sql_result = Database::query($personal_course_list_sql);

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
     * Gives a list of courses for the given user in the given session
     * @param integer $user_id
     * @param integer $session_id
     * @return array  list of statuses (session_id-course_code => status)
     */
    public static function get_courses_list_by_session($user_id, $session_id)
    {
        // Database Table Definitions
        $tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
        $tableCourse = Database::get_main_table(TABLE_MAIN_COURSE);
        $tbl_session_course_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $tbl_session_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);

        $user_id = intval($user_id);
        $session_id = intval($session_id);
        //we filter the courses from the URL
        $join_access_url = $where_access_url = '';

        if (api_get_multiple_access_url()) {
            $urlId = api_get_current_access_url_id();
            if ($urlId != -1) {
                $tbl_url_session = Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);
                $join_access_url = " ,  $tbl_url_session url_rel_session ";
                $where_access_url = " AND access_url_id = $urlId AND url_rel_session.session_id = $session_id ";
            }
        }

        $personal_course_list = array();
        $courses = array();

        /* This query is very similar to the query below, but it will check the
        session_rel_course_user table if there are courses registered
        to our user or not */

        $sql = "SELECT DISTINCT
                    c.visibility,
                    c.id as real_id
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

        $result = Database::query($sql);

        if (Database::num_rows($result) > 0) {
            while ($result_row = Database::fetch_array($result, 'ASSOC')) {
                $result_row['status'] = 5;
                if (!in_array($result_row['real_id'], $courses)) {
                    $personal_course_list[] = $result_row;
                    $courses[] = $result_row['real_id'];
                }
            }
        }

        if (api_is_allowed_to_create_course()) {
            $sql = "SELECT DISTINCT
                        c.visibility, c.id as real_id
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
                        (scu.user_id=$user_id AND scu.status=2) OR
                        s.id_coach = $user_id
                      )
                    $where_access_url
                    ORDER BY sc.position ASC";
            $result = Database::query($sql);

            if (Database::num_rows($result) > 0) {
                while ($result_row = Database::fetch_array($result, 'ASSOC')) {
                    $result_row['status'] = 2;
                    if (!in_array($result_row['real_id'], $courses)) {
                        $personal_course_list[] = $result_row;
                        $courses[] = $result_row['real_id'];
                    }
                }
            }
        }

        if (api_is_drh()) {
            $session_list = SessionManager::get_sessions_followed_by_drh($user_id);
            $session_list = array_keys($session_list);
            if (in_array($session_id, $session_list)) {
                $course_list = SessionManager::get_course_list_by_session_id($session_id);
                if (!empty($course_list)) {
                    foreach ($course_list as $course) {
                        $personal_course_list[] = $course;
                    }
                }
            }
        } else {
            //check if user is general coach for this session
            $s = api_get_session_info($session_id);
            if ($s['id_coach'] == $user_id) {
                $course_list = SessionManager::get_course_list_by_session_id($session_id);

                if (!empty($course_list)) {
                    foreach ($course_list as $course) {
                        if (!in_array($course['id'], $courses)) {
                            $personal_course_list[] = $course;
                        }
                    }
                }
            }
        }

        return $personal_course_list;
    }

    /**
     * Get user id from a username
     * @param    string    Username
     * @return    int        User ID (or false if not found)
     */
    public static function get_user_id_from_username($username)
    {
        if (empty($username)) {
            return false;
        }
        $username = trim($username);
        $username = Database::escape_string($username);
        $t_user = Database::get_main_table(TABLE_MAIN_USER);
        $sql = "SELECT user_id FROM $t_user WHERE username = '$username'";
        $res = Database::query($sql);
        if ($res === false) {
            return false;
        }
        if (Database::num_rows($res) !== 1) {
            return false;
        }
        $row = Database::fetch_array($res);
        return $row['user_id'];
    }

    /**
     * Get the users files upload from his share_folder
     * @param    string    User ID
     * @param   string  course directory
     * @param   string  resourcetype: images, all
     * @return    int        User ID (or false if not found)
     */
    public static function get_user_upload_files_by_course($user_id, $course, $resourcetype = 'all')
    {
        $return = '';
        if (!empty($user_id) && !empty($course)) {
            $user_id = intval($user_id);
            $path = api_get_path(SYS_COURSE_PATH).$course.'/document/shared_folder/sf_user_'.$user_id.'/';
            $web_path = api_get_path(WEB_COURSE_PATH).$course.'/document/shared_folder/sf_user_'.$user_id.'/';
            $file_list = array();

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
                foreach ($file_list as $file) {
                    if ($resourcetype == "all") {
                        $return .= '<li><a href="'.$web_path.urlencode($file).'" target="_blank">'.htmlentities($file).'</a></li>';
                    } elseif ($resourcetype == "images") {
                        //get extension
                        $ext = explode('.', $file);
                        if ($ext[1] == 'jpg' || $ext[1] == 'jpeg' || $ext[1] == 'png' || $ext[1] == 'gif' || $ext[1] == 'bmp' || $ext[1] == 'tif') {
                            $return .= '<li class="span2"><a class="thumbnail" href="'.$web_path.urlencode($file).'" target="_blank">
                                            <img src="'.$web_path.urlencode($file).'" ></a>
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
     * Gets the API key (or keys) and return them into an array
     * @param   int     Optional user id (defaults to the result of api_get_user_id())
     * @return  array   Non-indexed array containing the list of API keys for this user, or FALSE on error
     */
    public static function get_api_keys($user_id = null, $api_service = 'dokeos')
    {
        if ($user_id != strval(intval($user_id)))
            return false;
        if (empty($user_id)) {
            $user_id = api_get_user_id();
        }
        if ($user_id === false)
            return false;
        $service_name = Database::escape_string($api_service);
        if (is_string($service_name) === false) {
            return false;
        }
        $t_api = Database::get_main_table(TABLE_MAIN_USER_API_KEY);
        $sql = "SELECT * FROM $t_api WHERE user_id = $user_id AND api_service='$api_service';";
        $res = Database::query($sql);
        if ($res === false)
            return false; //error during query
        $num = Database::num_rows($res);
        if ($num == 0)
            return false;
        $list = array();
        while ($row = Database::fetch_array($res)) {
            $list[$row['id']] = $row['api_key'];
        }
        return $list;
    }

    /**
     * Adds a new API key to the users' account
     * @param   int     Optional user ID (defaults to the results of api_get_user_id())
     * @return  boolean True on success, false on failure
     */
    public static function add_api_key($user_id = null, $api_service = 'dokeos')
    {
        if ($user_id != strval(intval($user_id)))
            return false;
        if (empty($user_id)) {
            $user_id = api_get_user_id();
        }
        if ($user_id === false)
            return false;
        $service_name = Database::escape_string($api_service);
        if (is_string($service_name) === false) {
            return false;
        }
        $t_api = Database::get_main_table(TABLE_MAIN_USER_API_KEY);
        $md5 = md5((time() + ($user_id * 5)) - rand(10000, 10000)); //generate some kind of random key
        $sql = "INSERT INTO $t_api (user_id, api_key,api_service) VALUES ($user_id,'$md5','$service_name')";
        $res = Database::query($sql);
        if ($res === false)
            return false; //error during query
        $num = Database::insert_id();
        return ($num == 0) ? false : $num;
    }

    /**
     * Deletes an API key from the user's account
     * @param   int     API key's internal ID
     * @return  boolean True on success, false on failure
     */
    public static function delete_api_key($key_id)
    {
        if ($key_id != strval(intval($key_id)))
            return false;
        if ($key_id === false)
            return false;
        $t_api = Database::get_main_table(TABLE_MAIN_USER_API_KEY);
        $sql = "SELECT * FROM $t_api WHERE id = ".$key_id;
        $res = Database::query($sql);
        if ($res === false)
            return false; //error during query
        $num = Database::num_rows($res);
        if ($num !== 1)
            return false;
        $sql = "DELETE FROM $t_api WHERE id = ".$key_id;
        $res = Database::query($sql);
        if ($res === false)
            return false; //error during query
        return true;
    }

    /**
     * Regenerate an API key from the user's account
     * @param   int     user ID (defaults to the results of api_get_user_id())
     * @param   string  API key's internal ID
     * @return  int        num
     */
    public static function update_api_key($user_id, $api_service)
    {
        if ($user_id != strval(intval($user_id)))
            return false;
        if ($user_id === false)
            return false;
        $service_name = Database::escape_string($api_service);
        if (is_string($service_name) === false) {
            return false;
        }
        $t_api = Database::get_main_table(TABLE_MAIN_USER_API_KEY);
        $sql = "SELECT id FROM $t_api WHERE user_id=".$user_id." AND api_service='".$api_service."'";
        $res = Database::query($sql);
        $num = Database::num_rows($res);
        if ($num == 1) {
            $id_key = Database::fetch_array($res, 'ASSOC');
            self::delete_api_key($id_key['id']);
            $num = self::add_api_key($user_id, $api_service);
        } elseif ($num == 0) {
            $num = self::add_api_key($user_id);
        }
        return $num;
    }

    /**
     * @param   int     user ID (defaults to the results of api_get_user_id())
     * @param   string    API key's internal ID
     * @return  int    row ID, or return false if not found
     */
    public static function get_api_key_id($user_id, $api_service)
    {
        if ($user_id != strval(intval($user_id)))
            return false;
        if ($user_id === false)
            return false;
        if (empty($api_service))
            return false;
        $t_api = Database::get_main_table(TABLE_MAIN_USER_API_KEY);
        $api_service = Database::escape_string($api_service);
        $sql = "SELECT id FROM $t_api WHERE user_id=".$user_id." AND api_service='".$api_service."'";
        $res = Database::query($sql);
        if (Database::num_rows($res) < 1) {
            return false;
        }
        $row = Database::fetch_array($res, 'ASSOC');
        return $row['id'];
    }

    /**
     * Checks if a user_id is platform admin
     * @param   int user ID
     * @return  boolean True if is admin, false otherwise
     * @see main_api.lib.php::api_is_platform_admin() for a context-based check
     */
    public static function is_admin($user_id)
    {
        if (empty($user_id) or $user_id != strval(intval($user_id))) {
            return false;
        }
        $admin_table = Database::get_main_table(TABLE_MAIN_ADMIN);
        $sql = "SELECT * FROM $admin_table WHERE user_id = $user_id";
        $res = Database::query($sql);
        return Database::num_rows($res) === 1;
    }

    /**
     * Get the total count of users
     * @param   int     Status of users to be counted
     * @param   int     Access URL ID (optional)
     * @return    mixed    Number of users or false on error
     */
    public static function get_number_of_users($status = 0, $access_url_id = null)
    {
        $t_u = Database::get_main_table(TABLE_MAIN_USER);
        $t_a = Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        $sql = "SELECT count(*) FROM $t_u u";
        $sql2 = '';
        if (is_int($status) && $status > 0) {
            $sql2 .= " WHERE u.status = $status ";
        }
        if (!empty($access_url_id) && $access_url_id == intval($access_url_id)) {
            $sql .= ", $t_a a ";
            $sql2 .= " AND a.access_url_id = $access_url_id AND u.user_id = a.user_id ";
        }
        $sql = $sql.$sql2;
        $res = Database::query($sql);
        if (Database::num_rows($res) === 1) {
            return (int) Database::result($res, 0, 0);
        }
        return false;
    }

    /**
     * Resize a picture
     *
     * @param  string file picture
     * @param  int size in pixels
     * @todo move this function somewhere else image.lib?
     * @return obj image object
     */
    public static function resize_picture($file, $max_size_for_picture)
    {
        $temp = false;
        if (file_exists($file)) {
            $temp = new Image($file);
            $image_size = $temp->get_image_size($file);
            $width = $image_size['width'];
            $height = $image_size['height'];
            if ($width >= $height) {
                if ($width >= $max_size_for_picture) {
                    // scale height
                    $new_height = round($height * ($max_size_for_picture / $width));
                    $temp->resize($max_size_for_picture, $new_height, 0);
                }
            } else { // height > $width
                if ($height >= $max_size_for_picture) {
                    // scale width
                    $new_width = round($width * ($max_size_for_picture / $height));
                    $temp->resize($new_width, $max_size_for_picture, 0);
                }
            }
        }
        return $temp;
    }

    /**
     * @author Isaac flores <isaac.flores@dokeos.com>
     * @param string The email administrator
     * @param integer The user id
     * @param string The message title
     * @param string The content message
     */
    public static function send_message_in_outbox($email_administrator, $user_id, $title, $content)
    {
        $table_message = Database::get_main_table(TABLE_MESSAGE);
        $table_user = Database::get_main_table(TABLE_MAIN_USER);
        $title = api_utf8_decode($title);
        $content = api_utf8_decode($content);
        $email_administrator = Database::escape_string($email_administrator);
        //message in inbox
        $sql_message_outbox = 'SELECT user_id from '.$table_user.' WHERE email="'.$email_administrator.'" ';
        //$num_row_query = Database::num_rows($sql_message_outbox);
        $res_message_outbox = Database::query($sql_message_outbox);
        $array_users_administrator = array();
        while ($row_message_outbox = Database::fetch_array($res_message_outbox, 'ASSOC')) {
            $array_users_administrator[] = $row_message_outbox['user_id'];
        }
        //allow to insert messages in outbox
        for ($i = 0; $i < count($array_users_administrator); $i++) {
            $sql_insert_outbox = "INSERT INTO $table_message(user_sender_id, user_receiver_id, msg_status, send_date, title, content ) ".
                " VALUES (".
                "'".(int) $user_id."', '".(int) ($array_users_administrator[$i])."', '4', '".api_get_utc_datetime()."','".Database::escape_string($title)."','".Database::escape_string($content)."'".
                ")";
            Database::query($sql_insert_outbox);
        }
    }

    /*
     *
     * USER TAGS
     *
     * Intructions to create a new user tag by Julio Montoya <gugli100@gmail.com>
     *
     * 1. Create a new extra field in main/admin/user_fields.php with the "TAG" field type make it available and visible. Called it "books" for example.
     * 2. Go to profile main/auth/profile.php There you will see a special input (facebook style) that will show suggestions of tags.
     * 3. All the tags are registered in the user_tag table and the relationship between user and tags is in the user_rel_tag table
     * 4. Tags are independent this means that tags can't be shared between tags + book + hobbies.
     * 5. Test and enjoy.
     *
     */

    /**
     * Gets the tags of a specific field_id
     *
     * @param int field_id
     * @param string how we are going to result value in array or in a string (json)
     * @return mixed
     * @since Nov 2009
     * @version 1.8.6.2
     */
    public static function get_tags($tag, $field_id, $return_format = 'json', $limit = 10)
    {
        // database table definition
        $table_user_tag = Database::get_main_table(TABLE_MAIN_TAG);
        $field_id = intval($field_id);
        $limit = intval($limit);
        $tag = trim(Database::escape_string($tag));

        // all the information of the field
        $sql = "SELECT DISTINCT id, tag from $table_user_tag
                WHERE field_id = $field_id AND tag LIKE '$tag%' ORDER BY tag LIMIT $limit";
        $result = Database::query($sql);
        $return = array();
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result, 'ASSOC')) {
                $return[] = array('caption' => $row['tag'], 'value' => $row['tag']);
            }
        }
        if ($return_format == 'json') {
            $return = json_encode($return);
        }
        return $return;
    }

    /**
     * @param int $field_id
     * @param int $limit
     * @return array
     */
    public static function get_top_tags($field_id, $limit = 100)
    {
        // database table definition
        $table_user_tag = Database::get_main_table(TABLE_MAIN_TAG);
        $table_user_tag_values = Database::get_main_table(TABLE_MAIN_USER_REL_TAG);
        $field_id = intval($field_id);
        $limit = intval($limit);
        // all the information of the field
        $sql = "SELECT count(*) count, tag FROM $table_user_tag_values  uv
                INNER JOIN $table_user_tag ut
                ON(ut.id = uv.tag_id)
                WHERE field_id = $field_id
                GROUP BY tag_id
                ORDER BY count DESC
                LIMIT $limit";
        $result = Database::query($sql);
        $return = array();
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result, 'ASSOC')) {
                $return[] = $row;
            }
        }
        return $return;
    }

    /**
     * Get user's tags
     * @param int field_id
     * @param int user_id
     * @return array
     */
    public static function get_user_tags($user_id, $field_id)
    {
        // database table definition
        $table_user_tag = Database::get_main_table(TABLE_MAIN_TAG);
        $table_user_tag_values = Database::get_main_table(TABLE_MAIN_USER_REL_TAG);
        $field_id = intval($field_id);
        $user_id = intval($user_id);

        // all the information of the field
        $sql = "SELECT ut.id, tag, count
                FROM $table_user_tag ut
                INNER JOIN $table_user_tag_values uv
                ON (uv.tag_id=ut.ID)
                WHERE field_id = $field_id AND user_id = $user_id
                ORDER BY tag";
        $result = Database::query($sql);
        $return = array();
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result, 'ASSOC')) {
                $return[$row['id']] = array('tag' => $row['tag'], 'count' => $row['count']);
            }
        }

        return $return;
    }

    /**
     * Get user's tags
     * @param int user_id
     * @param int field_id
     * @param bool show links or not
     * @return array
     */
    public static function get_user_tags_to_string($user_id, $field_id, $show_links = true)
    {
        // database table definition
        $table_user_tag = Database::get_main_table(TABLE_MAIN_TAG);
        $table_user_tag_values = Database::get_main_table(TABLE_MAIN_USER_REL_TAG);
        $field_id = intval($field_id);
        $user_id = intval($user_id);

        // all the information of the field
        $sql = "SELECT ut.id, tag,count FROM $table_user_tag ut
                INNER JOIN $table_user_tag_values uv
                ON (uv.tag_id = ut.id)
                WHERE field_id = $field_id AND user_id = $user_id
                ORDER BY tag";

        $result = Database::query($sql);
        $return = array();
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result, 'ASSOC')) {
                $return[$row['id']] = array('tag' => $row['tag'], 'count' => $row['count']);
            }
        }
        $user_tags = $return;
        $tag_tmp = array();
        foreach ($user_tags as $tag) {
            if ($show_links) {
                $tag_tmp[] = '<a href="'.api_get_path(WEB_PATH).'main/search/index.php?q='.$tag['tag'].'">'.$tag['tag'].'</a>';
            } else {
                $tag_tmp[] = $tag['tag'];
            }
        }
        if (is_array($user_tags) && count($user_tags) > 0) {
            $return = implode(', ', $tag_tmp);
        } else {
            return '';
        }
        return $return;
    }

    /**
     * Get the tag id
     * @param int tag
     * @param int field_id
     * @return int returns 0 if fails otherwise the tag id
     */
    public static function get_tag_id($tag, $field_id)
    {
        $table_user_tag = Database::get_main_table(TABLE_MAIN_TAG);
        $tag = Database::escape_string($tag);
        $field_id = intval($field_id);
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
     * Get the tag id
     * @param int tag
     * @param int field_id
     * @return int 0 if fails otherwise the tag id
     */
    public static function get_tag_id_from_id($tag_id, $field_id)
    {
        $table_user_tag = Database::get_main_table(TABLE_MAIN_TAG);
        $tag_id = intval($tag_id);
        $field_id = intval($field_id);
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
     * Adds a user-tag value
     * @param mixed tag
     * @param int The user id
     * @param int field id of the tag
     * @return bool
     */
    public static function add_tag($tag, $user_id, $field_id)
    {
        // database table definition
        $table_user_tag = Database::get_main_table(TABLE_MAIN_TAG);
        $table_user_tag_values = Database::get_main_table(TABLE_MAIN_USER_REL_TAG);
        $tag = trim(Database::escape_string($tag));
        $user_id = intval($user_id);
        $field_id = intval($field_id);

        $tag_id = UserManager::get_tag_id($tag, $field_id);

        /* IMPORTANT
         *  @todo we don't create tags with numbers
         *
         */
        if (is_numeric($tag)) {
            //the form is sending an id this means that the user select it from the list so it MUST exists
            /* $new_tag_id = UserManager::get_tag_id_from_id($tag,$field_id);
              if ($new_tag_id !== false) {
              $sql = "UPDATE $table_user_tag SET count = count + 1 WHERE id  = $new_tag_id";
              $result = Database::query($sql);
              $last_insert_id = $new_tag_id;
              } else {
              $sql = "INSERT INTO $table_user_tag (tag, field_id,count) VALUES ('$tag','$field_id', count + 1)";
              $result = Database::query($sql);
              $last_insert_id = Database::insert_id();
              } */
        } else {

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
        }
    }

    /**
     * Deletes an user tag
     * @param int user id
     * @param int field id
     *
     */
    public static function delete_user_tags($user_id, $field_id)
    {
        // database table definition
        $table_user_tag = Database::get_main_table(TABLE_MAIN_TAG);
        $table_user_tag_values = Database::get_main_table(TABLE_MAIN_USER_REL_TAG);
        $tags = UserManager::get_user_tags($user_id, $field_id);
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
     * Process the tag list comes from the UserManager::update_extra_field_value() function
     * @param array the tag list that will be added
     * @param int user id
     * @param int field id
     * @return bool
     */
    public static function process_tags($tags, $user_id, $field_id)
    {
        // We loop the tags and add it to the DB
        if (is_array($tags)) {
            foreach ($tags as $tag) {
                UserManager::add_tag($tag, $user_id, $field_id);
            }
        } else {
            UserManager::add_tag($tags, $user_id, $field_id);
        }

        return true;
    }

    /**
     * Returns a list of all administrators
     * @author jmontoya
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
                    ON (u.user_id=admin.user_id)
                    WHERE access_url_id ='".$access_url_id."'";
        } else {
            $sql = "SELECT admin.user_id, username, firstname, lastname, email, active
                    FROM $table_admin as admin
                    INNER JOIN $table_user u
                    ON (u.user_id=admin.user_id)";
        }
        $result = Database::query($sql);
        $return = array();
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result, 'ASSOC')) {
                $return[$row['user_id']] = $row;
            }
        }

        return $return;
    }

    /**
     * Search an user (tags, first name, last name and email )
     * @param string $tag
     * @param int $field_id field id of the tag
     * @param int $from where to start in the query
     * @param int $number_of_items
     * @param bool $getCount get count or not
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
        $access_url_rel_user_table = Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);

        $field_id = intval($field_id);
        $from = intval($from);
        $number_of_items = intval($number_of_items);

        $where_field = "";
        $where_extra_fields = UserManager::get_search_form_where_extra_fields();
        if ($field_id != 0) {
            $where_field = " field_id = $field_id AND ";
        }

        // all the information of the field

        if ($getCount) {
            $select = "SELECT count(DISTINCT u.user_id) count";
        } else {
            $select = "SELECT DISTINCT u.user_id, u.username, firstname, lastname, email, tag, picture_uri";
        }

        $sql = " $select
                FROM $user_table u
                INNER JOIN $access_url_rel_user_table url_rel_user
                ON (u.user_id = url_rel_user.user_id)
                LEFT JOIN $table_user_tag_values uv
                ON (u.user_id AND uv.user_id AND uv.user_id = url_rel_user.user_id)
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
                     AND
                     url_rel_user.access_url_id=".api_get_current_access_url_id();

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
        $return = array();

        if (Database::num_rows($result) > 0) {
            if ($getCount) {
                $row = Database::fetch_array($result, 'ASSOC');
                return $row['count'];
            }
            while ($row = Database::fetch_array($result, 'ASSOC')) {
                if (isset($return[$row['user_id']]) &&
                    !empty($return[$row['user_id']]['tag'])
                ) {
                    $url = Display::url(
                        $row['tag'],
                        api_get_path(WEB_PATH).'main/social/search.php?q='.$row['tag'],
                        array('class' => 'tag')
                    );
                    $row['tag'] = $url;
                }
                $return[$row['user_id']] = $row;
            }
        }

        return $return;
    }

    /**
      * Get extra filtrable user fields (only type select)
      * @return array
      */
    public static function get_extra_filtrable_fields()
    {
        $extraFieldList = UserManager::get_extra_fields();

        $extraFiltrableFields = array();
        if (is_array($extraFieldList)) {
            foreach ($extraFieldList as $extraField) {
                // If is enabled to filter and is a "<select>" field type
                if ($extraField[8] == 1 && $extraField[2] == 4) {
                    $extraFiltrableFields[] = array(
                        'name' => $extraField[3],
                        'variable' => $extraField[1],
                        'data' => $extraField[9]
                    );
                }
            }
        }

        if (is_array($extraFiltrableFields) && count($extraFiltrableFields) > 0) {
            return $extraFiltrableFields;
        }
    }

    /**
      * Get extra where clauses for finding users based on extra filtrable user fields (type select)
      * @return string With AND clauses based on user's ID which have the values to search in extra user fields
      */
    public static function get_search_form_where_extra_fields()
    {
        $useExtraFields = false;
        $extraFields = UserManager::get_extra_filtrable_fields();
        $extraFieldResult = array();
        if (is_array($extraFields) && count($extraFields)>0 ) {
            foreach ($extraFields as $extraField) {
                $varName = 'field_'.$extraField['variable'];
                if (UserManager::is_extra_field_available($extraField['variable'])) {
                    if (isset($_GET[$varName]) && $_GET[$varName]!='0') {
                        $useExtraFields = true;
                        $extraFieldResult[]= UserManager::get_extra_user_data_by_value(
                            $extraField['variable'],
                            $_GET[$varName]
                        );
                    }
                }
            }
        }

        if ($useExtraFields) {
            $finalResult = array();
            if (count($extraFieldResult)>1) {
                for ($i=0; $i < count($extraFieldResult) -1; $i++) {
                    if (is_array($extraFieldResult[$i+1])) {
                        $finalResult  = array_intersect($extraFieldResult[$i], $extraFieldResult[$i+1]);
                    }
                }
            } else {
                $finalResult = $extraFieldResult[0];
            }

            if (is_array($finalResult) && count($finalResult)>0) {
                $whereFilter = " AND u.user_id IN  ('".implode("','", $finalResult)."') ";
            } else {
                //no results
                $whereFilter = " AND u.user_id  = -1 ";
            }

            return $whereFilter;
        }
    }

    /**
     * Show the search form
     * @param string $query the value of the search box
     * @return string HTML form
     */
    public static function get_search_form($query)
    {
        $searchType = isset($_GET['search_type']) ? $_GET['search_type'] : null;
        $form = new FormValidator(
            'search_user',
            'get',
            api_get_path(WEB_PATH).'main/social/search.php',
            '',
            array(),
            FormValidator::LAYOUT_HORIZONTAL
        );

        $form->addText('q', get_lang('UsersGroups'));
        $options = array(
            0 => get_lang('Select'),
            1 => get_lang('User'),
            2 => get_lang('Group'),
        );
        $form->addSelect(
            'search_type',
            get_lang('Type'),
            $options,
            array('onchange' => 'javascript: extra_field_toogle();')
        );

        // Extra fields

        $extraFields = UserManager::get_extra_filtrable_fields();
        $defaults = [];
        if (is_array($extraFields) && count($extraFields) > 0) {
            foreach ($extraFields as $extraField) {
                $varName = 'field_'.$extraField['variable'];

                $options = [
                    0 => get_lang('Select')
                ];
                foreach ($extraField['data'] as $option) {
                    $checked = '';
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

        $defaults['search_type'] = intval($searchType);
        $defaults['q'] = api_htmlentities(Security::remove_XSS($query));
        $form->setDefaults($defaults);

        $form->addButtonSearch(get_lang('Search'));

        $js = '<script>
        extra_field_toogle();
        function extra_field_toogle() {
            if (jQuery("select[name=search_type]").val() != "1") { jQuery(".extra_field").hide(); } else { jQuery(".extra_field").show(); }
        }
        </script>';

        return $js.$form->returnForm();
    }

    /**
     * Shows the user menu
     */
    public static function show_menu()
    {
        echo '<div class="actions">';
        echo '<a href="/main/auth/profile.php">'.Display::return_icon('profile.png').' '.get_lang('PersonalData').'</a>';
        echo '<a href="/main/messages/inbox.php">'.Display::return_icon('inbox.png').' '.get_lang('Inbox').'</a>';
        echo '<a href="/main/messages/outbox.php">'.Display::return_icon('outbox.png').' '.get_lang('Outbox').'</a>';
        echo '<span style="float:right; padding-top:7px;">'.
        '<a href="/main/auth/profile.php?show=1">'.Display::return_icon('edit.gif').' '.get_lang('Configuration').'</a>';
        '</span>';
        echo '</div>';
    }

    /**
     * Allow to register contact to social network
     * @param int $friend_id user friend id
     * @param int $my_user_id user id
     * @param int $relation_type relation between users see constants definition
     */
    public static function relate_users($friend_id, $my_user_id, $relation_type)
    {
        $tbl_my_friend = Database :: get_main_table(TABLE_MAIN_USER_REL_USER);

        $friend_id = intval($friend_id);
        $my_user_id = intval($my_user_id);
        $relation_type = intval($relation_type);

        $sql = 'SELECT COUNT(*) as count FROM '.$tbl_my_friend.'
                WHERE
                    friend_user_id='.$friend_id.' AND
                    user_id='.$my_user_id.' AND
                    relation_type <> '.USER_RELATION_TYPE_RRHH.' ';
        $result = Database::query($sql);
        $row = Database :: fetch_array($result, 'ASSOC');
        $current_date = date('Y-m-d H:i:s');

        if ($row['count'] == 0) {
            $sql = 'INSERT INTO '.$tbl_my_friend.'(friend_user_id,user_id,relation_type,last_edit)
                    VALUES ('.$friend_id.','.$my_user_id.','.$relation_type.',"'.$current_date.'")';
            Database::query($sql);
            return true;
        } else {
            $sql = 'SELECT COUNT(*) as count, relation_type  FROM '.$tbl_my_friend.'
                    WHERE
                        friend_user_id='.$friend_id.' AND
                        user_id='.$my_user_id.' AND
                        relation_type <> '.USER_RELATION_TYPE_RRHH.' ';
            $result = Database::query($sql);
            $row = Database :: fetch_array($result, 'ASSOC');
            if ($row['count'] == 1) {
                //only for the case of a RRHH
                if ($row['relation_type'] != $relation_type && $relation_type == USER_RELATION_TYPE_RRHH) {
                    $sql = 'INSERT INTO '.$tbl_my_friend.'(friend_user_id,user_id,relation_type,last_edit)
                            VALUES ('.$friend_id.','.$my_user_id.','.$relation_type.',"'.$current_date.'")';
                } else {
                    $sql = 'UPDATE '.$tbl_my_friend.' SET relation_type='.$relation_type.'
                            WHERE friend_user_id='.$friend_id.' AND user_id='.$my_user_id;
                }
                Database::query($sql);

                return true;
            } else {

                return false;
            }
        }
    }

    /**
     * Deletes a contact
     * @param int user friend id
     * @param bool true will delete ALL friends relationship from $friend_id
     * @author isaac flores paz <isaac.flores@dokeos.com>
     * @author Julio Montoya <gugli100@gmail.com> Cleaning code
     */
    public static function remove_user_rel_user($friend_id, $real_removed = false, $with_status_condition = '')
    {
        $tbl_my_friend = Database :: get_main_table(TABLE_MAIN_USER_REL_USER);
        $tbl_my_message = Database :: get_main_table(TABLE_MESSAGE);
        $friend_id = intval($friend_id);

        if ($real_removed) {
            $extra_condition = '';
            if ($with_status_condition != '') {
                $extra_condition = ' AND relation_type = '.intval($with_status_condition);
            }
            $sql = 'DELETE FROM '.$tbl_my_friend.'
                    WHERE relation_type <> '.USER_RELATION_TYPE_RRHH.' AND friend_user_id='.$friend_id.' '.$extra_condition;
            Database::query($sql);
            $sql= 'DELETE FROM '.$tbl_my_friend.'
                   WHERE relation_type <> '.USER_RELATION_TYPE_RRHH.' AND user_id='.$friend_id.' '.$extra_condition;
            Database::query($sql);
        } else {
            $user_id = api_get_user_id();
            $sql = 'SELECT COUNT(*) as count FROM '.$tbl_my_friend.'
                    WHERE user_id='.$user_id.' AND relation_type NOT IN('.USER_RELATION_TYPE_DELETED.', '.USER_RELATION_TYPE_RRHH.') AND friend_user_id='.$friend_id;
            $result = Database::query($sql);
            $row = Database :: fetch_array($result, 'ASSOC');
            if ($row['count'] == 1) {
                //Delete user rel user
                $sql_i = 'UPDATE '.$tbl_my_friend.' SET relation_type='.USER_RELATION_TYPE_DELETED.' WHERE user_id='.$user_id.' AND friend_user_id='.$friend_id;
                $sql_j = 'UPDATE '.$tbl_my_message.' SET msg_status='.MESSAGE_STATUS_INVITATION_DENIED.' WHERE user_receiver_id='.$user_id.' AND user_sender_id='.$friend_id.' AND update_date="0000-00-00 00:00:00" ';
                //Delete user
                $sql_ij = 'UPDATE '.$tbl_my_friend.'  SET relation_type='.USER_RELATION_TYPE_DELETED.' WHERE user_id='.$friend_id.' AND friend_user_id='.$user_id;
                $sql_ji = 'UPDATE '.$tbl_my_message.' SET msg_status='.MESSAGE_STATUS_INVITATION_DENIED.' WHERE user_receiver_id='.$friend_id.' AND user_sender_id='.$user_id.' AND update_date="0000-00-00 00:00:00" ';
                Database::query($sql_i);
                Database::query($sql_j);
                Database::query($sql_ij);
                Database::query($sql_ji);
            }
        }
    }

    /**
     * @param int $userId
     * @return array
     */
    public static function getDrhListFromUser($userId)
    {
        $tblUser = Database::get_main_table(TABLE_MAIN_USER);
        $tblUserRelUser = Database::get_main_table(TABLE_MAIN_USER_REL_USER);
        $tblUserRelAccessUrl = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        $userId = intval($userId);

        $orderBy = null;
        if (api_is_western_name_order()) {
            $orderBy .= " ORDER BY firstname, lastname ";
        } else {
            $orderBy .= " ORDER BY lastname, firstname ";
        }

        $sql = "SELECT u.user_id, username, u.firstname, u.lastname
                FROM $tblUser u
                INNER JOIN $tblUserRelUser uru ON (uru.friend_user_id = u.user_id)
                INNER JOIN $tblUserRelAccessUrl a ON (a.user_id = u.user_id)
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
     * get users followed by human resource manager
     * @param int $userId
     * @param int $userStatus (STUDENT, COURSEMANAGER, etc)
     * @param bool $getOnlyUserId
     * @param bool $getSql
     * @param bool $getCount
     * @param int $from
     * @param int $numberItems
     * @param int $column
     * @param string $direction
     * @param int $active
     * @param string $lastConnectionDate
     * @return array     users
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
    * Get users followed by human resource manager
    * @param int $userId
    * @param int  $userStatus Filter users by status (STUDENT, COURSEMANAGER, etc)
    * @param bool $getOnlyUserId
    * @param bool $getSql
    * @param bool $getCount
    * @param int $from
    * @param int $numberItems
    * @param int $column
    * @param string $direction
    * @param int $active
    * @param string $lastConnectionDate
    * @param int $status the function is called by who? COURSEMANAGER, DRH?
    * @param string $keyword
     *
    * @return array user list
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
        $keyword = null
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

        $userId = intval($userId);

        $limitCondition = null;

        if (isset($from) && isset($numberItems)) {
            $from = intval($from);
            $numberItems = intval($numberItems);
            $limitCondition = "LIMIT $from, $numberItems";
        }

        $column = Database::escape_string($column);
        $direction = in_array(strtolower($direction), array('asc', 'desc')) ? $direction : null;

        $userConditions = '';
        if (!empty($userStatus)) {
            $userConditions .= ' AND u.status = '.intval($userStatus);
        }

        $select = " SELECT DISTINCT u.user_id, u.username, u.lastname, u.firstname, u.email ";
        if ($getOnlyUserId) {
            $select = " SELECT DISTINCT u.user_id";
        }

        $masterSelect = "SELECT DISTINCT * FROM ";

        if ($getCount) {
            $masterSelect = "SELECT COUNT(DISTINCT(user_id)) as count FROM ";
            $select = " SELECT DISTINCT(u.user_id) ";
        }

        if (!is_null($active)) {
            $active = intval($active);
            $userConditions .= " AND u.active = $active ";
        }

        if (!empty($keyword)) {
            $keyword = Database::escape_string($keyword);
            $userConditions .= " AND (
                u.username LIKE '%$keyword%' OR
                u.firstname LIKE '%$keyword%' OR
                u.lastname LIKE '%$keyword%' OR
                u.official_code LIKE '%$keyword%' OR
                u.email LIKE '%$keyword%'
            )";
        }

        if (!empty($lastConnectionDate)) {
            if (isset($_configuration['save_user_last_login']) &&
                $_configuration['save_user_last_login']
            ) {
                $lastConnectionDate = Database::escape_string($lastConnectionDate);
                $userConditions .=  " AND u.last_login <= '$lastConnectionDate' ";
            }
        }

        $courseConditions = null;
        $sessionConditionsCoach = null;
        $sessionConditionsTeacher = null;
        $drhConditions = null;
        $teacherSelect = null;

        switch($status) {
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

                $sessionConditionsTeacher .= " AND
                    (scu.status = 2 AND scu.user_id = '$userId')
                ";

                $teacherSelect =
                "UNION ALL (
                        $select
                        FROM $tbl_user u
                        INNER JOIN $tbl_session_rel_user sru ON (sru.user_id = u.user_id)
                        WHERE
                            sru.session_id IN (
                                SELECT DISTINCT(s.id) FROM $tbl_session s INNER JOIN
                                $tbl_session_rel_access_url
                                WHERE access_url_id = ".api_get_current_access_url_id()."
                                $sessionConditionsCoach
                                UNION (
                                    SELECT DISTINCT(s.id) FROM $tbl_session s
                                    INNER JOIN $tbl_session_rel_access_url url
                                    ON (url.session_id = s.id)
                                    INNER JOIN $tbl_session_rel_course_rel_user scu
                                    ON (scu.session_id = s.id)
                                    WHERE access_url_id = ".api_get_current_access_url_id()."
                                    $sessionConditionsTeacher
                                )
                            )
                            $userConditions
                    )
                    UNION ALL(
                        $select
                        FROM $tbl_user u
                        INNER JOIN $tbl_course_user cu ON (cu.user_id = u.user_id)
                        WHERE cu.c_id IN (
                            SELECT DISTINCT(c_id) FROM $tbl_course_user
                            WHERE user_id = $userId AND status = ".COURSEMANAGER."
                        )
                        $userConditions
                    )"
                ;
                break;
            case STUDENT_BOSS :
                $drhConditions = " AND friend_user_id = $userId AND "
                . "relation_type = " . USER_RELATION_TYPE_BOSS;
                break;
        }

        $join = null;
        $sql = " $masterSelect
                (
                    (
                        $select
                        FROM $tbl_user u
                        INNER JOIN $tbl_user_rel_user uru ON (uru.user_id = u.user_id)
                        LEFT JOIN $tbl_user_rel_access_url a ON (a.user_id = u.user_id)
                        $join
                        WHERE
                            access_url_id = ".api_get_current_access_url_id()."
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
        if (api_is_western_name_order()) {
            $orderBy .= " ORDER BY firstname, lastname ";
        } else {
            $orderBy .= " ORDER BY lastname, firstname ";
        }

        if (!empty($column) && !empty($direction)) {
            // Fixing order due the UNIONs
            $column = str_replace('u.', '', $column);
            $orderBy = " ORDER BY $column $direction ";
        }

        $sql .= $orderBy;
        $sql .= $limitCondition;

        $result = Database::query($sql);
        $users = array();
        if (Database::num_rows($result) > 0) {

            while ($row = Database::fetch_array($result)) {
                $users[$row['user_id']] = $row;
            }
        }

        return $users;
    }

    /**
     * Subscribes users to human resource manager (Dashboard feature)
     *    @param    int         hr dept id
     * @param    array        Users id
     * @param    int            affected rows
     * */
    public static function suscribe_users_to_hr_manager($hr_dept_id, $users_id)
    {
        return self::subscribeUsersToUser($hr_dept_id, $users_id, USER_RELATION_TYPE_RRHH);
    }

    /**
     * Add subscribed users to a user by relation type
     * @param int $userId The user id
     * @param array $subscribedUsersId The id of suscribed users
     * @param action $relationType The relation type
     */
    public static function subscribeUsersToUser($userId, $subscribedUsersId, $relationType)
    {
        $userRelUserTable = Database::get_main_table(TABLE_MAIN_USER_REL_USER);
        $userRelAccessUrlTable = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);

        $userId = intval($userId);
        $relationType = intval($relationType);
        $affectedRows = 0;

        if (api_get_multiple_access_url()) {
            //Deleting assigned users to hrm_id
            $sql = "SELECT s.user_id FROM $userRelUserTable s "
                . "INNER JOIN $userRelAccessUrlTable a ON (a.user_id = s.user_id) "
                . "WHERE friend_user_id = $userId "
                . "AND relation_type = $relationType "
                . "AND access_url_id = " . api_get_current_access_url_id() . "";
        } else {
            $sql = "SELECT user_id FROM $userRelUserTable "
                . "WHERE friend_user_id = $userId "
                . "AND relation_type = $relationType";
        }

        $result = Database::query($sql);

        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                $sql = "DELETE FROM $userRelUserTable "
                    . "WHERE user_id = {$row['user_id']} "
                    . "AND friend_user_id = $userId "
                    . "AND relation_type = $relationType";

                Database::query($sql);
            }
        }

        // Inserting new user list
        if (is_array($subscribedUsersId)) {
            foreach ($subscribedUsersId as $subscribedUserId) {
                $subscribedUserId = intval($subscribedUserId);

                $sql = "INSERT IGNORE INTO $userRelUserTable(user_id, friend_user_id, relation_type) "
                    . "VALUES ($subscribedUserId, $userId, $relationType)";

                $result = Database::query($sql);

                $affectedRows = Database::affected_rows($result);
            }
        }

        return $affectedRows;
    }

    /**
     * This function check if an user is followed by human resources manager
     * @param     int     User id
     * @param    int        Human resources manager
     * @return    bool
     */
    public static function is_user_followed_by_drh($user_id, $hr_dept_id)
    {
        // Database table and variables Definitions
        $tbl_user_rel_user = Database::get_main_table(TABLE_MAIN_USER_REL_USER);
        $user_id = intval($user_id);
        $hr_dept_id = intval($hr_dept_id);
        $result = false;

        $sql = "SELECT user_id FROM $tbl_user_rel_user
                WHERE
                    user_id='$user_id' AND
                    friend_user_id='$hr_dept_id' AND
                    relation_type = ".USER_RELATION_TYPE_RRHH;
        $rs = Database::query($sql);
        if (Database::num_rows($rs) > 0) {
            $result = true;
        }
        return $result;
    }

    /**
     * get user id of teacher or session administrator
     * @param array $courseInfo
     *
     * @return int The user id
     */
    public static function get_user_id_of_course_admin_or_session_admin($courseInfo)
    {
        $session = api_get_session_id();
        $table_user = Database::get_main_table(TABLE_MAIN_USER);
        $table_course_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $table_session_course_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $courseId = $courseInfo['real_id'];
        $courseCode = $courseInfo['code'];

        if ($session == 0 || is_null($session)) {
            $sql = 'SELECT u.user_id FROM '.$table_user.' u
                    INNER JOIN '.$table_course_user.' ru
                    ON ru.user_id=u.user_id
                    WHERE
                        ru.status = 1 AND
                        ru.c_id = "'.$courseId.'" ';
            $rs = Database::query($sql);
            $num_rows = Database::num_rows($rs);
            if ($num_rows == 1) {
                $row = Database::fetch_array($rs);
                return $row['user_id'];
            } else {
                $my_num_rows = $num_rows;
                $my_user_id = Database::result($rs, $my_num_rows - 1, 'user_id');
                return $my_user_id;
            }
        } elseif ($session > 0) {
            $sql = 'SELECT u.user_id FROM '.$table_user.' u
                    INNER JOIN '.$table_session_course_user.' sru
                    ON sru.user_id=u.user_id
                    WHERE
                        sru.c_id="'.$courseId.'" AND
                        sru.status=2';
            $rs = Database::query($sql);
            $row = Database::fetch_array($rs);

            return $row['user_id'];
        }
    }

    /**
     * Determines if a user is a gradebook certified
     * @param int The category id of gradebook
     * @param int The user id
     * @return boolean
     */
    public static function is_user_certified($cat_id, $user_id)
    {
        $table_certificate = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CERTIFICATE);
        $sql = 'SELECT path_certificate FROM '.$table_certificate.'
                WHERE
                    cat_id="'.intval($cat_id).'" AND
                    user_id="'.intval($user_id).'"';
        $rs = Database::query($sql);
        $row = Database::fetch_array($rs);
        if ($row['path_certificate'] == '' || is_null($row['path_certificate'])) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Gets the info about a gradebook certificate for a user by course
     * @param string The course code
     * @param int The user id
     * @return array  if there is not information return false
     */
    public static function get_info_gradebook_certificate($course_code, $user_id)
    {
        $tbl_grade_certificate = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CERTIFICATE);
        $tbl_grade_category = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);
        $session_id = api_get_session_id();

        if (empty($session_id)) {
            $session_condition = ' AND (session_id = "" OR session_id = 0 OR session_id IS NULL )';
        } else {
            $session_condition = " AND session_id = $session_id";
        }

        $sql = 'SELECT * FROM '.$tbl_grade_certificate.' WHERE cat_id = (SELECT id FROM '.$tbl_grade_category.'
                WHERE
                    course_code = "'.Database::escape_string($course_code).'" '.$session_condition.' LIMIT 1 ) AND
                    user_id='.intval($user_id);

        $rs = Database::query($sql);
        if (Database::num_rows($rs) > 0) {
            $row = Database::fetch_array($rs, 'ASSOC');
            $score = $row['score_certificate'];
            $category_id = $row['cat_id'];
            $cat = Category::load($category_id);
            $displayscore = ScoreDisplay::instance();
            if (isset($cat) && $displayscore->is_custom()) {
                $grade = $displayscore->display_score(array($score, $cat[0]->get_weight()), SCORE_DIV_PERCENT_WITH_CUSTOM);
            } else {
                $grade = $displayscore->display_score(array($score, $cat[0]->get_weight()));
            }
            $row['grade'] = $grade;
            return $row;
        }
        return false;
    }

    /**
     * Gets the user path of user certificated
     * @param int The user id
     * @return array  containing path_certificate and cat_id
     */
    public static function get_user_path_certificate($user_id)
    {
        $my_certificate = array();
        $table_certificate = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CERTIFICATE);
        $table_gradebook_category = Database::get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY);

        $session_id = api_get_session_id();
        $user_id = intval($user_id);
        if ($session_id == 0 || is_null($session_id)) {
            $sql_session = 'AND (session_id='.intval($session_id).' OR isnull(session_id)) ';
        } elseif ($session_id > 0) {
            $sql_session = 'AND session_id='.intval($session_id);
        } else {
            $sql_session = '';
        }
        $sql = "SELECT tc.path_certificate,tc.cat_id,tgc.course_code,tgc.name
                FROM $table_certificate tc, $table_gradebook_category tgc
                WHERE tgc.id = tc.cat_id AND tc.user_id='$user_id'
                ORDER BY tc.date_certificate DESC limit 5";

        $rs = Database::query($sql);
        while ($row = Database::fetch_array($rs)) {
            $my_certificate[] = $row;
        }
        return $my_certificate;
    }

    /**
     * This function check if the user is a coach inside session course
     * @param  int     User id
     * @param  int  $courseId
     * @param  int     Session id
     * @return bool    True if the user is a coach
     *
     */
    public static function is_session_course_coach($user_id, $courseId, $session_id)
    {
        $tbl_session_course_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        // Protect data
        $user_id = intval($user_id);
        $courseId = intval($courseId);
        $session_id = intval($session_id);
        $result = false;

        $sql = "SELECT session_id FROM $tbl_session_course_rel_user
                WHERE
                  session_id=$session_id AND
                  c_id='$courseId' AND
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
     * Defaults to the current Chamilo favicon
     * @param    string    URL of website where to look for favicon.ico
     * @param    string    Optional second URL of website where to look for favicon.ico
     * @return    string    Path of icon to load
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

    /**
     *
     * @param int   student id
     * @param int   years
     * @param bool  show warning_message
     * @param bool  return_timestamp
     */
    public static function delete_inactive_student($student_id, $years = 2, $warning_message = false, $return_timestamp = false)
    {
        $tbl_track_login = Database :: get_main_table(TABLE_STATISTIC_TRACK_E_LOGIN);
        $sql = 'SELECT login_date FROM '.$tbl_track_login.'
                WHERE login_user_id = '.intval($student_id).'
                ORDER BY login_date DESC LIMIT 0,1';
        if (empty($years)) {
            $years = 1;
        }
        $inactive_time = $years * 31536000;  //1 year
        $rs = Database::query($sql);
        if (Database::num_rows($rs) > 0) {
            if ($last_login_date = Database::result($rs, 0, 0)) {
                $last_login_date = api_get_local_time($last_login_date, null, date_default_timezone_get());
                if ($return_timestamp) {
                    return api_strtotime($last_login_date);
                } else {
                    if (!$warning_message) {
                        return api_format_date($last_login_date, DATE_FORMAT_SHORT);
                    } else {
                        $timestamp = api_strtotime($last_login_date);
                        $currentTimestamp = time();

                        //If the last connection is > than 7 days, the text is red
                        //345600 = 7 days in seconds 63072000= 2 ans
                        // if ($currentTimestamp - $timestamp > 184590 )
                        if ($currentTimestamp - $timestamp > $inactive_time && UserManager::delete_user($student_id)) {
                            Display :: display_normal_message(get_lang('UserDeleted'));
                            echo '<p>', 'id', $student_id, ':', $last_login_date, '</p>';
                        }
                    }
                }
            }
        }
        return false;
    }

    /**
     * @param FormValidator $form
     * @param $extra_data
     * @param $form_name
     * @param bool $admin_permissions
     * @param null $user_id
     * @deprecated
     * @return array
     */
    static function set_extra_fields_in_form(
        $form,
        $extra_data,
        $admin_permissions = false,
        $user_id = null
    ) {
        $user_id = intval($user_id);

        // EXTRA FIELDS
        $extra = UserManager::get_extra_fields(0, 50, 5, 'ASC');
        $jquery_ready_content = null;
        foreach ($extra as $field_details) {

            if (!$admin_permissions) {
                if ($field_details[6] == 0) {
                    continue;
                }
            }

            switch ($field_details[2]) {
                case ExtraField::FIELD_TYPE_TEXT:
                    $form->addElement('text', 'extra_'.$field_details[1], $field_details[3], array('size' => 40));
                    $form->applyFilter('extra_'.$field_details[1], 'stripslashes');
                    $form->applyFilter('extra_'.$field_details[1], 'trim');
                    $form->applyFilter('extra_'.$field_details[1], 'html_filter');

                    if (!$admin_permissions) {
                        if ($field_details[7] == 0) {
                            $form->freeze('extra_'.$field_details[1]);
                        }
                    }
                    break;
                case ExtraField::FIELD_TYPE_TEXTAREA:
                    $form->addHtmlEditor(
                        'extra_'.$field_details[1],
                        $field_details[3],
                        false,
                        false,
                        array('ToolbarSet' => 'Profile', 'Width' => '100%', 'Height' => '130')
                    );
                    $form->applyFilter('extra_'.$field_details[1], 'stripslashes');
                    $form->applyFilter('extra_'.$field_details[1], 'trim');
                    if (!$admin_permissions) {
                        if ($field_details[7] == 0)
                            $form->freeze('extra_'.$field_details[1]);
                    }
                    break;
                case ExtraField::FIELD_TYPE_RADIO:
                    $group = array();
                    foreach ($field_details[9] as $option_id => $option_details) {
                        $options[$option_details[1]] = $option_details[2];
                        $group[] = $form->createElement(
                            'radio',
                            'extra_'.$field_details[1],
                            $option_details[1],
                            $option_details[2].'<br />',
                            $option_details[1]
                        );
                    }
                    $form->addGroup($group, 'extra_'.$field_details[1], $field_details[3], '');
                    if (!$admin_permissions) {
                        if ($field_details[7] == 0)
                            $form->freeze('extra_'.$field_details[1]);
                    }
                    break;
                case ExtraField::FIELD_TYPE_SELECT:
                    $get_lang_variables = false;
                    if (in_array(
                        $field_details[1],
                        array(
                            'mail_notify_message',
                            'mail_notify_invitation',
                            'mail_notify_group_message',
                        )
                    )) {
                        $get_lang_variables = true;
                    }
                    $options = array();

                    foreach ($field_details[9] as $option_id => $option_details) {
                        if ($get_lang_variables) {
                            $options[$option_details[1]] = get_lang($option_details[2]);
                        } else {
                            $options[$option_details[1]] = $option_details[2];
                        }
                    }

                    if ($get_lang_variables) {
                        $field_details[3] = get_lang($field_details[3]);
                    }

                    $form->addElement(
                        'select',
                        'extra_'.$field_details[1],
                        $field_details[3],
                        $options,
                        array('class' => 'chzn-select', 'id' => 'extra_'.$field_details[1])
                    );

                    if (!$admin_permissions) {
                        if ($field_details[7] == 0)
                            $form->freeze('extra_'.$field_details[1]);
                    }
                    break;
                case ExtraField::FIELD_TYPE_SELECT_MULTIPLE:
                    $options = array();
                    foreach ($field_details[9] as $option_id => $option_details) {
                        $options[$option_details[1]] = $option_details[2];
                    }
                    $form->addElement(
                        'select',
                        'extra_'.$field_details[1],
                        $field_details[3],
                        $options,
                        array('multiple' => 'multiple')
                    );
                    if (!$admin_permissions) {
                        if ($field_details[7] == 0)
                            $form->freeze('extra_'.$field_details[1]);
                    }
                    break;
                case ExtraField::FIELD_TYPE_DATE:
                    $form->addDatePicker('extra_'.$field_details[1], $field_details[3]);
                    $defaults['extra_'.$field_details[1]] = date('Y-m-d 12:00:00');
                    $form->setDefaults($defaults);
                    if (!$admin_permissions) {
                        if ($field_details[7] == 0)
                            $form->freeze('extra_'.$field_details[1]);
                    }
                    $form->applyFilter('theme', 'trim');
                    break;
                case ExtraField::FIELD_TYPE_DATETIME:
                    $form->addDateTimePicker('extra_'.$field_details[1], $field_details[3]);
                    $defaults['extra_'.$field_details[1]] = date('Y-m-d 12:00:00');
                    $form->setDefaults($defaults);
                    if (!$admin_permissions) {
                        if ($field_details[7] == 0)
                            $form->freeze('extra_'.$field_details[1]);
                    }
                    $form->applyFilter('theme', 'trim');
                    break;
                case ExtraField::FIELD_TYPE_DOUBLE_SELECT:
                    foreach ($field_details[9] as $key => $element) {
                        if ($element[2][0] == '*') {
                            $values['*'][$element[0]] = str_replace('*', '', $element[2]);
                        } else {
                            $values[0][$element[0]] = $element[2];
                        }
                    }

                    $group = '';
                    $group[] = $form->createElement('select', 'extra_'.$field_details[1], '', $values[0], '');
                    $group[] = $form->createElement('select', 'extra_'.$field_details[1].'*', '', $values['*'], '');
                    $form->addGroup($group, 'extra_'.$field_details[1], $field_details[3], '&nbsp;');

                    if (!$admin_permissions) {
                        if ($field_details[7] == 0)
                            $form->freeze('extra_'.$field_details[1]);
                    }

                    /* Recoding the selected values for double : if the user has
                    selected certain values, we have to assign them to the
                    correct select form */
                    if (array_key_exists('extra_'.$field_details[1], $extra_data)) {
                        // exploding all the selected values (of both select forms)
                        $selected_values = explode(';', $extra_data['extra_'.$field_details[1]]);
                        $extra_data['extra_'.$field_details[1]] = array();

                        // looping through the selected values and assigning the selected values to either the first or second select form
                        foreach ($selected_values as $key => $selected_value) {
                            if (array_key_exists($selected_value, $values[0])) {
                                $extra_data['extra_'.$field_details[1]]['extra_'.$field_details[1]] = $selected_value;
                            } else {
                                $extra_data['extra_'.$field_details[1]]['extra_'.$field_details[1].'*'] = $selected_value;
                            }
                        }
                    }
                    break;
                case ExtraField::FIELD_TYPE_DIVIDER:
                    $form->addElement('static', $field_details[1], '<br /><strong>'.$field_details[3].'</strong>');
                    break;
                case ExtraField::FIELD_TYPE_TAG:
                    //the magic should be here
                    $user_tags = UserManager::get_user_tags($user_id, $field_details[0]);

                    $tag_list = '';
                    if (is_array($user_tags) && count($user_tags) > 0) {
                        foreach ($user_tags as $tag) {
                            $tag_list .= '<option value="'.$tag['tag'].'" class="selected">'.$tag['tag'].'</option>';
                        }
                    }

                    $multi_select = '<select id="extra_'.$field_details[1].'" name="extra_'.$field_details[1].'">
                                    '.$tag_list.'
                                    </select>';

                    $form->addElement('label', $field_details[3], $multi_select);
                    $url = api_get_path(WEB_AJAX_PATH).'user_manager.ajax.php';
                    $complete_text = get_lang('StartToType');
                    //if cache is set to true the jquery will be called 1 time
                    $jquery_ready_content = <<<EOF
                    $("#extra_$field_details[1]").fcbkcomplete({
                        json_url: "$url?a=search_tags&field_id=$field_details[0]",
                        cache: false,
                        filter_case: true,
                        filter_hide: true,
                        complete_text:"$complete_text",
                        firstselected: true,
                        //onremove: "testme",
                        //onselect: "testme",
                        filter_selected: true,
                        newel: true
                    });
EOF;
                    break;
                case ExtraField::FIELD_TYPE_TIMEZONE:
                    $form->addElement('select', 'extra_'.$field_details[1], $field_details[3], api_get_timezones(), '');
                    if ($field_details[7] == 0)
                        $form->freeze('extra_'.$field_details[1]);
                    break;
                case ExtraField::FIELD_TYPE_SOCIAL_PROFILE:
                    // get the social network's favicon
                    $icon_path = UserManager::get_favicon_from_url($extra_data['extra_'.$field_details[1]], $field_details[4]);
                    // special hack for hi5
                    $leftpad = '1.7';
                    $top = '0.4';
                    $domain = parse_url($icon_path, PHP_URL_HOST);
                    if ($domain == 'www.hi5.com' or $domain == 'hi5.com') {
                        $leftpad = '3';
                        $top = '0';
                    }
                    // print the input field
                    $form->addElement(
                        'text',
                        'extra_'.$field_details[1],
                        $field_details[3],
                        array(
                            'size' => 60,
                            'style' => 'background-image: url(\''.$icon_path.'\'); background-repeat: no-repeat; background-position: 0.4em '.$top.'em; padding-left: '.$leftpad.'em; '
                        )
                    );
                    $form->applyFilter('extra_'.$field_details[1], 'stripslashes');
                    $form->applyFilter('extra_'.$field_details[1], 'trim');
                    if ($field_details[7] == 0)
                        $form->freeze('extra_'.$field_details[1]);
                    break;
                case ExtraField::FIELD_TYPE_FILE:
                    $extra_field = 'extra_'.$field_details[1];
                    $form->addElement('file', $extra_field, $field_details[3], null, '');
                    if ($extra_file_list = UserManager::build_user_extra_file_list($user_id, $field_details[1], '', true)) {
                        $form->addElement('static', $extra_field . '_list', null, $extra_file_list);
                    }
                    if ($field_details[7] == 0) {
                        $form->freeze($extra_field);
                    }
                    break;
                case ExtraField::FIELD_TYPE_MOBILE_PHONE_NUMBER:
                    $form->addElement(
                        'text',
                        'extra_'.$field_details[1],
                        $field_details[3]." (".get_lang('CountryDialCode').")",
                        array('size' => 40, 'placeholder'  => '(xx)xxxxxxxxx')
                    );
                    $form->applyFilter('extra_'.$field_details[1], 'stripslashes');
                    $form->applyFilter('extra_'.$field_details[1], 'trim');
                    $form->applyFilter('extra_'.$field_details[1], 'mobile_phone_number_filter');
                    $form->addRule(
                        'extra_'.$field_details[1],
                        get_lang('MobilePhoneNumberWrong'),
                        'mobile_phone_number'
                    );
                    if (!$admin_permissions) {
                        if ($field_details[7] == 0) {
                            $form->freeze('extra_'.$field_details[1]);
                        }
                    }
                    break;
            }
        }
        $return = array();
        $return['jquery_ready_content'] = $jquery_ready_content;
        return $return;
    }

    /**
     * @return array
     */
    static function get_user_field_types()
    {
        $types = array();
        $types[self::USER_FIELD_TYPE_TEXT] = get_lang('FieldTypeText');
        $types[self::USER_FIELD_TYPE_TEXTAREA] = get_lang('FieldTypeTextarea');
        $types[self::USER_FIELD_TYPE_RADIO] = get_lang('FieldTypeRadio');
        $types[self::USER_FIELD_TYPE_SELECT] = get_lang('FieldTypeSelect');
        $types[self::USER_FIELD_TYPE_SELECT_MULTIPLE] = get_lang('FieldTypeSelectMultiple');
        $types[self::USER_FIELD_TYPE_DATE] = get_lang('FieldTypeDate');
        $types[self::USER_FIELD_TYPE_DATETIME] = get_lang('FieldTypeDatetime');
        $types[self::USER_FIELD_TYPE_DOUBLE_SELECT] = get_lang('FieldTypeDoubleSelect');
        $types[self::USER_FIELD_TYPE_DIVIDER] = get_lang('FieldTypeDivider');
        $types[self::USER_FIELD_TYPE_TAG] = get_lang('FieldTypeTag');
        $types[self::USER_FIELD_TYPE_TIMEZONE] = get_lang('FieldTypeTimezone');
        $types[self::USER_FIELD_TYPE_SOCIAL_PROFILE] = get_lang('FieldTypeSocialProfile');
        $types[self::USER_FIELD_TYPE_FILE] = get_lang('FieldTypeFile');
        $types[self::USER_FIELD_TYPE_MOBILE_PHONE_NUMBER] = get_lang('FieldTypeMobilePhoneNumber');

        return $types;
    }

    /**
     * @param int $user_id
     */
    static function add_user_as_admin($user_id)
    {
        $table_admin = Database :: get_main_table(TABLE_MAIN_ADMIN);
        $user_id = intval($user_id);

        if (!self::is_admin($user_id)) {
            $sql = "INSERT INTO $table_admin SET user_id = '".$user_id."'";
            Database::query($sql);
        }
    }

    /**
     * @param int $user_id
     */
    public static function remove_user_admin($user_id)
    {
        $table_admin = Database :: get_main_table(TABLE_MAIN_ADMIN);
        $user_id = intval($user_id);
        if (self::is_admin($user_id)) {
            $sql = "DELETE FROM $table_admin WHERE user_id = '".$user_id."'";
            Database::query($sql);
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
     * Subscribe users to student boss
     * @param int $bossId The boss id
     * @param array $usersId The users array
     * @return int Affected rows
     */
    public static function subscribeUsersToBoss($bossId, $usersId)
    {
        return self::subscribeUsersToUser($bossId, $usersId, USER_RELATION_TYPE_BOSS);
    }

    /**
     * Get users followed by student boss
     * @param int $userId
     * @param int $userStatus (STUDENT, COURSEMANAGER, etc)
     * @param bool $getOnlyUserId
     * @param bool $getSql
     * @param bool $getCount
     * @param int $from
     * @param int $numberItems
     * @param int $column
     * @param string $direction
     * @param int $active
     * @param string $lastConnectionDate
     * @return array     users
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
    ){
        return self::getUsersFollowedByUser(
                $userId, $userStatus, $getOnlyUserId, $getSql, $getCount, $from, $numberItems, $column, $direction,
                $active, $lastConnectionDate, STUDENT_BOSS
        );
    }

    /**
     * Get the teacher (users with COURSEMANGER status) list
     * @return array The list
     */
    public static function getTeachersList()
    {
        $userTable = Database::get_main_table(TABLE_MAIN_USER);

        $resultData = Database::select('user_id, lastname, firstname, username', $userTable, array(
            'where' => array(
                'status = ?' => COURSEMANAGER
            )
        ));

        foreach ($resultData as &$teacherData) {
            $teacherData['completeName'] = api_get_person_name($teacherData['firstname'], $teacherData['lastname']);
        }

        return $resultData;
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

        $result = array();
        foreach ($values as $value) {
            $result[$value['official_code']] = $value['official_code'];
        }
        return $result;
    }

    /**
     * @param string $officialCode
     * @return array
     */
    public static function getUsersByOfficialCode($officialCode)
    {
        $user = Database::get_main_table(TABLE_MAIN_USER);
        $officialCode = Database::escape_string($officialCode);

        $sql = "SELECT DISTINCT user_id
                FROM $user
                WHERE official_code = '$officialCode'
                ";
        $result = Database::query($sql);

        $users = array();
        while ($row = Database::fetch_array($result)) {
            $users[] = $row['user_id'];
        }
        return $users;
    }

    /**
     * Calc the expended time (in seconds) by a user in a course
     * @param int $userId The user id
     * @param int $courseId The course id
     * @param int $sessionId Optional. The session id
     * @param string $from Optional. From date
     * @param string $until Optional. Until date
     * @return int The time
     */
    public static function getTimeSpentInCourses($userId, $courseId, $sessionId = 0, $from = '', $until = '')
    {
        $userId = intval($userId);
        $sessionId = intval($sessionId);

        $trackCourseAccessTable = Database::get_main_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);

        $whereConditions = array(
            'user_id = ? ' => $userId,
            'AND c_id = ? ' => $courseId,
            'AND session_id = ? ' => $sessionId
        );

        if (!empty($from) && !empty($until)) {
            $whereConditions["AND (login_course_date >= '?' "] = $from;
            $whereConditions["AND logout_course_date <= DATE_ADD('?', INTERVAL 1 DAY)) "] = $until;
        }

        $trackResult = Database::select(
            'SUM(UNIX_TIMESTAMP(logout_course_date) - UNIX_TIMESTAMP(login_course_date)) as total_time',
            $trackCourseAccessTable,
            array(
                'where' => $whereConditions
            ), 'first'
        );

        if ($trackResult != false) {
            return $trackResult['total_time'] ? $trackResult['total_time'] : 0;
        }

        return 0;
    }

    /**
     * Get the boss user ID from a followed user id
     * @param $userId
     * @return bool
     */
    public static function getStudentBoss($userId)
    {
        $userId = intval($userId);
        if ($userId > 0) {
            $userRelTable = Database::get_main_table(TABLE_MAIN_USER_REL_USER);
            $row = Database::select(
                'DISTINCT friend_user_id AS boss_id',
                $userRelTable,
                array(
                    'where' => array(
                        'user_id = ? AND relation_type = ? LIMIT 1' => array(
                            $userId,
                            USER_RELATION_TYPE_BOSS,
                        )
                    )
                )
            );
            if (!empty($row)) {

                return $row[0]['boss_id'];
            }
        }

        return false;
    }

    /**
     * Get either a Gravatar URL or complete image tag for a specified email address.
     *
     * @param string $email The email address
     * @param string $s Size in pixels, defaults to 80px [ 1 - 2048 ]
     * @param string $d Default imageset to use [ 404 | mm | identicon | monsterid | wavatar ]
     * @param string $r Maximum rating (inclusive) [ g | pg | r | x ]
     * @param boole $img True to return a complete IMG tag False for just the URL
     * @param array $atts Optional, additional key/value attributes to include in the IMG tag
     * @return String containing either just a URL or a complete image tag
     * @source http://gravatar.com/site/implement/images/php/
     */
    private static function getGravatar(
        $email,
        $s = 80,
        $d = 'mm',
        $r = 'g',
        $img = false,
        $atts = array()
    ) {
        $url = 'http://www.gravatar.com/avatar/';
        $url .= md5( strtolower( trim( $email ) ) );
        $url .= "?s=$s&d=$d&r=$r";
        if ( $img ) {
            $url = '<img src="' . $url . '"';
            foreach ( $atts as $key => $val )
                $url .= ' ' . $key . '="' . $val . '"';
            $url .= ' />';
        }
        return $url;
    }



    /**
     * Displays the name of the user and makes the link to the user profile
     * @param array $userInfo
     *
     * @return string
     */
    public static function getUserProfileLink($userInfo)
    {
        if (isset($userInfo) && isset($userInfo['user_id'])) {
            return Display::url($userInfo['complete_name'], $userInfo['profile_url']);
        } else {
            return get_lang('Anonymous');
        }
    }

    /**
     * Displays the name of the user and makes the link to the user profile
     *
     * @param $userInfo
     *
     * @return string
     */
    public static function getUserProfileLinkWithPicture($userInfo)
    {
        return Display::url(Display::img($userInfo['avatar']), $userInfo['profile_url']);
    }

    /**
     * Get users whose name matches $firstname and $lastname
     * @param string $firstname Firstname to search
     * @param string $lastname Lastname to search
     * @return array The user list
     */
    public static function getUserByName($firstname, $lastname)
    {
        $firstname = Database::escape_string($firstname);
        $lastname = Database::escape_string($lastname);

        $userTable = Database::get_main_table(TABLE_MAIN_USER);

        $sql = <<<SQL
            SELECT id, username, lastname, firstname
            FROM $userTable
            WHERE firstname LIKE '$firstname%' AND
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
     * @return string
     */
    public static function getUserSubscriptionTab($optionSelected = 1)
    {
        $allowAdmin = api_get_setting('allow_user_course_subscription_by_course_admin');
        if (($allowAdmin == 'true' && api_is_allowed_to_edit()) ||
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
                [
                    'url' => $userPath.'class.php?'.api_get_cidreq(),
                    'content' => get_lang('Classes'),
                ],
            ];

            return Display::tabsOnlyLink($headers, $optionSelected);
        }
    }

}
