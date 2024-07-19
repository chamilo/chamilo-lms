<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\User;
use ChamiloSession as Session;

/**
 * Class Login.
 *
 * @deprecated
 *
 * @author Olivier Cauberghe <olivier.cauberghe@UGent.be>, Ghent University
 * @author Julio Montoya <gugli100@gmail.com>
 */
class Login
{
    /**
     * Get user account list.
     *
     * @param array $user        array with keys: email, password, uid, loginName
     * @param bool  $reset
     * @param bool  $by_username
     *
     * @return string
     */
    public static function get_user_account_list($user, $reset = false, $by_username = false)
    {
        $portal_url = api_get_path(WEB_PATH);

        if (api_is_multiple_url_enabled()) {
            $access_url_id = api_get_current_access_url_id();
            if (-1 != $access_url_id) {
                $url = api_get_access_url($access_url_id);
                $portal_url = $url['url'];
            }
        }

        if ($reset) {
            if ($by_username) {
                $secret_word = self::get_secret_word($user['email']);
                if ($reset) {
                    $reset_link = $portal_url."main/auth/lostPassword.php?reset=".$secret_word."&id=".$user['uid'];
                    $reset_link = Display::url($reset_link, $reset_link);
                } else {
                    $reset_link = get_lang('Pass')." : $user[password]";
                }
                $user_account_list = get_lang('Your registration data')." : \n".
                    get_lang('Username').' : '.$user['loginName']."\n".
                    get_lang('Click here to recover your password').' : '.$reset_link;

                if ($user_account_list) {
                    $user_account_list = "\n-----------------------------------------------\n".$user_account_list;
                }
            } else {
                foreach ($user as $this_user) {
                    $secret_word = self::get_secret_word($this_user['email']);
                    if ($reset) {
                        $reset_link = $portal_url."main/auth/lostPassword.php?reset=".$secret_word."&id=".$this_user['uid'];
                        $reset_link = Display::url($reset_link, $reset_link);
                    } else {
                        $reset_link = get_lang('Pass')." : $this_user[password]";
                    }
                    $user_account_list[] =
                        get_lang('Your registration data')." : \n".
                        get_lang('Username').' : '.$this_user['loginName']."\n".
                        get_lang('Click here to recover your password').' : '.$reset_link;
                }
                if ($user_account_list) {
                    $user_account_list = implode("\n-----------------------------------------------\n", $user_account_list);
                }
            }
        } else {
            if (!$by_username) {
                $user = $user[0];
            }
            $reset_link = get_lang('Pass')." : $user[password]";
            $user_account_list =
                get_lang('Your registration data')." : \n".
                get_lang('Username').' : '.$user['loginName']."\n".
                $reset_link.'';
        }

        return $user_account_list;
    }

    /**
     * This function sends the actual password to the user.
     *
     * @param int $user
     *
     * @return string
     *
     * @author Olivier Cauberghe <olivier.cauberghe@UGent.be>, Ghent University
     */
    public static function send_password_to_user($user, $by_username = false)
    {
        $email_subject = "[".api_get_setting('siteName')."] ".get_lang('Login request'); // SUBJECT

        if ($by_username) { // Show only for lost password
            $user_account_list = self::get_user_account_list($user, false, $by_username); // BODY
            $email_to = $user['email'];
        } else {
            $user_account_list = self::get_user_account_list($user); // BODY
            $email_to = $user[0]['email'];
        }

        $portal_url = api_get_path(WEB_PATH);
        if (api_is_multiple_url_enabled()) {
            $access_url_id = api_get_current_access_url_id();
            if (-1 != $access_url_id) {
                $url = api_get_access_url($access_url_id);
                $portal_url = $url['url'];
            }
        }

        $email_body = get_lang('This is your information to connect to')." ".$portal_url."\n\n$user_account_list";
        // SEND MESSAGE
        $sender_name = api_get_person_name(
            api_get_setting('administratorName'),
            api_get_setting('administratorSurname'),
            null,
            PERSON_NAME_EMAIL_ADDRESS
        );
        $email_admin = api_get_setting('emailAdministrator');

        if (1 == api_mail_html('', $email_to, $email_subject, $email_body, $sender_name, $email_admin)) {
            return get_lang('Your password has been reset');
        } else {
            $admin_email = Display:: encrypted_mailto_link(
                api_get_setting('emailAdministrator'),
                api_get_person_name(
                    api_get_setting('administratorName'),
                    api_get_setting('administratorSurname')
                )
            );

            return sprintf(
                get_lang('This platform was unable to send the email. Please contact %s for more information.'),
                $admin_email
            );
        }
    }

    /**
     * Handle encrypted password, send an email to a user with his password.
     *
     * @param int user id
     * @param bool $by_username
     *
     * @return string
     *
     * @author Olivier Cauberghe <olivier.cauberghe@UGent.be>, Ghent University
     */
    public static function handle_encrypted_password($user, $by_username = false)
    {
        $email_subject = "[".api_get_setting('siteName')."] ".get_lang('Login request'); // SUBJECT

        if ($by_username) {
            // Show only for lost password
            $user_account_list = self::get_user_account_list($user, true, $by_username); // BODY
            $email_to = $user['email'];
        } else {
            $user_account_list = self::get_user_account_list($user, true); // BODY
            $email_to = $user[0]['email'];
        }
        $email_body = get_lang('Dear user')." :\n".get_lang('You have asked to reset your password. If you did not ask, then ignore this mail.')."\n";
        $email_body .= $user_account_list."\n-----------------------------------------------\n\n";
        $email_body .= get_lang('Your password is encrypted for security reasons. Thus, after pressing the link an e-mail will be sent to you again with your password.');
        $email_body .= "\n\n".
            get_lang('Sincerely').",\n".
            api_get_setting('administratorName')." ".
            api_get_setting('administratorSurname')."\n".
            get_lang('Portal Admin')." - ".
            api_get_setting('siteName');

        $sender_name = api_get_person_name(
            api_get_setting('administratorName'),
            api_get_setting('administratorSurname'),
            null,
            PERSON_NAME_EMAIL_ADDRESS
        );
        $email_admin = api_get_setting('emailAdministrator');
        $email_body = nl2br($email_body);

        $result = @api_mail_html(
            '',
            $email_to,
            $email_subject,
            $email_body,
            $sender_name,
            $email_admin
        );

        if (1 == $result) {
            return get_lang('Your password has been emailed to you.');
        } else {
            $admin_email = Display:: encrypted_mailto_link(
                api_get_setting('emailAdministrator'),
                api_get_person_name(
                    api_get_setting('administratorName'),
                    api_get_setting('administratorSurname')
                )
            );
            $message = sprintf(
                get_lang('This platform was unable to send the email. Please contact %s for more information.'),
                $admin_email
            );

            return $message;
        }
    }

    public static function sendResetEmail(User $user)
    {
        $uniqueId = api_get_unique_id();
        $user->setConfirmationToken($uniqueId);
        $user->setPasswordRequestedAt(new \DateTime());

        Database::getManager()->persist($user);
        Database::getManager()->flush();

        $url = api_get_path(WEB_CODE_PATH).'auth/reset.php?token='.$uniqueId;
        $mailSubject = get_lang('Instructions for the password change procedure');
        $mailBody = sprintf(
            get_lang('You are receiving this message because you (or someone pretending to be you) have requested a new password to be generated for you.<br/>'),
            $url
        );

        api_mail_html(
            UserManager::formatUserFullName($user),
            $user->getEmail(),
            $mailSubject,
            $mailBody
        );
        Display::addFlash(Display::return_message(get_lang('Check your e-mail and follow the instructions.')));
    }

    /**
     * Gets the secret word.
     *
     * @author Olivier Cauberghe <olivier.cauberghe@UGent.be>, Ghent University
     */
    public static function get_secret_word($add)
    {
        return $secret_word = sha1($add);
    }

    /**
     * Resets a password.
     *
     * @author Olivier Cauberghe <olivier.cauberghe@UGent.be>, Ghent University
     */
    public static function reset_password($secret, $id, $by_username = false)
    {
        $tbl_user = Database::get_main_table(TABLE_MAIN_USER);
        $id = intval($id);
        $sql = "SELECT
                    id AS uid,
                    lastname AS lastName,
                    firstname AS firstName,
                    username AS loginName,
                    password,
                    email,
                    auth_source
                FROM ".$tbl_user."
                WHERE id = $id";
        $result = Database::query($sql);
        $num_rows = Database::num_rows($result);

        if ($result && $num_rows > 0) {
            $user = Database::fetch_array($result);

            if ('extldap' == $user['auth_source']) {
                return get_lang('Could not reset password');
            }
        } else {
            return get_lang('Could not reset password');
        }

        if (self::get_secret_word($user['email']) == $secret) {
            // OK, secret word is good. Now change password and mail it.
            $user['password'] = api_generate_password();

            UserManager::updatePassword($id, $user['password']);

            return self::send_password_to_user($user, $by_username);
        } else {
            return get_lang('You are not allowed to see this page. Either your connection has expired or you are trying to access a page for which you do not have the sufficient privileges.');
        }
    }

    /**
     * @global bool   $is_platformAdmin
     * @global bool   $is_allowedCreateCourse
     * @global object $_user
     *
     * @param bool $reset
     */
    public static function init_user($user_id, $reset)
    {
        global $is_platformAdmin;
        global $is_allowedCreateCourse;
        global $_user;

        if (isset($reset) && $reset) {    // session data refresh requested
            unset($_SESSION['_user']['uidReset']);
            $is_platformAdmin = false;
            $is_allowedCreateCourse = false;
            $_user['user_id'] = $user_id;

            if (isset($_user['user_id']) && $_user['user_id'] && !api_is_anonymous()) {
                // a uid is given (log in succeeded)
                $user_table = Database::get_main_table(TABLE_MAIN_USER);
                $admin_table = Database::get_main_table(TABLE_MAIN_ADMIN);
                $track_e_login = Database::get_main_table(TABLE_STATISTIC_TRACK_E_LOGIN);

                $sql = "SELECT user.*, a.user_id is_admin, UNIX_TIMESTAMP(login.login_date) login_date
                        FROM $user_table
                        LEFT JOIN $admin_table a
                        ON user.user_id = a.user_id
                        LEFT JOIN $track_e_login login
                        ON user.user_id  = login.login_user_id
                        WHERE user.user_id = '".$_user['user_id']."'
                        ORDER BY login.login_date DESC LIMIT 1";

                $result = Database::query($sql);

                if (Database::num_rows($result) > 0) {
                    // Extracting the user data

                    $uData = Database::fetch_array($result);

                    $_user['firstName'] = $uData['firstname'];
                    $_user['lastName'] = $uData['lastname'];
                    $_user['mail'] = $uData['email'];
                    $_user['official_code'] = $uData['official_code'];
                    $_user['picture_uri'] = $uData['picture_uri'];
                    $_user['user_id'] = $uData['user_id'];
                    $_user['language'] = $uData['language'];
                    $_user['auth_source'] = $uData['auth_source'];
                    $_user['theme'] = $uData['theme'];
                    $_user['status'] = $uData['status'];

                    $is_platformAdmin = (bool) (!is_null($uData['is_admin']));
                    $is_allowedCreateCourse = (bool) ((1 == $uData['status']) or (api_get_setting('drhCourseManagerRights') and 4 == $uData['status']));
                    ConditionalLogin::check_conditions($uData);

                    Session::write('_user', $_user);
                    UserManager::update_extra_field_value($_user['user_id'], 'already_logged_in', 'true');
                    Session::write('is_platformAdmin', $is_platformAdmin);
                    Session::write('is_allowedCreateCourse', $is_allowedCreateCourse);
                } else {
                    header('location:'.api_get_path(WEB_PATH));
                    //exit("WARNING UNDEFINED UID !! ");
                }
            } else { // no uid => logout or Anonymous
                Session::erase('_user');
                Session::erase('_uid');
            }

            Session::write('is_platformAdmin', $is_platformAdmin);
            Session::write('is_allowedCreateCourse', $is_allowedCreateCourse);
        } else { // continue with the previous values
            $_user = $_SESSION['_user'];
            $is_platformAdmin = $_SESSION['is_platformAdmin'];
            $is_allowedCreateCourse = $_SESSION['is_allowedCreateCourse'];
        }
    }

    /**
     * Returns true if user exists in the platform when asking the password.
     *
     * @param string $username (email or username)
     *
     * @return array|bool
     */
    public static function get_user_accounts_by_username($username)
    {
        if (strpos($username, '@')) {
            $username = api_strtolower($username);
            $email = true;
        } else {
            $username = api_strtolower($username);
            $email = false;
        }

        if ($email) {
            $condition = "LOWER(email) = '".Database::escape_string($username)."' ";
        } else {
            $condition = "LOWER(username) = '".Database::escape_string($username)."'";
        }

        $tbl_user = Database::get_main_table(TABLE_MAIN_USER);
        $query = "SELECT
                    id AS uid,
		            lastname AS lastName,
		            firstname AS firstName,
		            username AS loginName,
		            password,
		            email,
                    status AS status,
                    official_code,
                    phone,
                    picture_uri,
                    creator_id,
                    auth_source
				 FROM $tbl_user
				 WHERE ( $condition AND active = 1) ";
        $result = Database::query($query);
        $num_rows = Database::num_rows($result);
        if ($result && $num_rows > 0) {
            return Database::fetch_assoc($result);
        }

        return false;
    }
}
