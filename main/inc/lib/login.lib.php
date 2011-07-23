<?php
/* For licensing terms, see /license.txt */
/**
*	Code library for login process
*
* @author Olivier Cauberghe <olivier.cauberghe@UGent.be>, Ghent University
* @author Julio Montoya		<gugli100@gmail.com>
* @package chamilo.login
*/
/**
 * Class
 * @package chamilo.login
 */
class Login 
{
	/**
	 * Get user account list
	 *
	 * @param unknown_type $user
	 * @param boolean $reset
	 * @param boolean $by_username
	 * @return unknown
	 */
	public static function get_user_account_list($user, $reset = false, $by_username = false) {
		global $_configuration;
		$portal_url = $_configuration['root_web'];
		if ($_configuration['multiple_access_urls']) {
			$access_url_id = api_get_current_access_url_id();
			if ($access_url_id != -1 ) {
				$url = api_get_access_url($access_url_id);
				$portal_url = $url['url'];
			}
		}
	
		if ($reset) {	
			if ($by_username) {	
				$secret_word = self::get_secret_word($user['email']);
				if ($reset)	{
					$reset_link = $portal_url."main/auth/lostPassword.php?reset=".$secret_word."&id=".$user['uid'];
				} else {
					$reset_link = get_lang('Pass')." : $user[password]";
				}
				$user_account_list = get_lang('YourRegistrationData')." : \n".get_lang('UserName').' : '.$user['loginName']."\n".get_lang('ResetLink').' : '.$reset_link.'';
	
				if ($user_account_list) {
					$user_account_list = "\n-----------------------------------------------\n" . $user_account_list;
				}
	
			} else {	
				foreach ($user as $this_user) {
					$secret_word = self::get_secret_word($this_user['email']);
					if ($reset)	{
						$reset_link = $portal_url."main/auth/lostPassword.php?reset=".$secret_word."&id=".$this_user['uid'];
					} else {
						$reset_link = get_lang('Pass')." : $this_user[password]";
					}
					$user_account_list[] = get_lang('YourRegistrationData')." : \n".get_lang('UserName').' : '.$this_user['loginName']."\n".get_lang('ResetLink').' : '.$reset_link.'';
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
	       	$user_account_list = get_lang('YourRegistrationData')." : \n".get_lang('UserName').' : '.$user['loginName']."\n".$reset_link.'';	
		}
		return $user_account_list;
	}
	
	/**
	 * This function sends the actual password to the user
	 *
	 * @param unknown_type $user
	 * @author Olivier Cauberghe <olivier.cauberghe@UGent.be>, Ghent University
	 */
	public static function send_password_to_user($user, $by_username = false) {
	
		global $_configuration;
		/*
		$emailHeaders = get_email_headers(); // Email Headers
		*/
		$email_subject = "[".api_get_setting('siteName')."] ".get_lang('LoginRequest'); // SUBJECT
	
		if ($by_username) { // Show only for lost password
			$user_account_list = self::get_user_account_list($user, false, $by_username); // BODY
			$email_to = $user['email'];
		} else {
			$user_account_list = self::get_user_account_list($user); // BODY
			$email_to = $user[0]['email'];
		}
	
		$portal_url = $_configuration['root_web'];
		if ($_configuration['multiple_access_urls']) {
			$access_url_id = api_get_current_access_url_id();
			if ($access_url_id != -1 ) {
				$url = api_get_access_url($access_url_id);
				$portal_url = $url['url'];
			}
		}
	
		$email_body = get_lang('YourAccountParam')." ".$portal_url."\n\n$user_account_list";
		// SEND MESSAGE
		$sender_name = api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname'), null, PERSON_NAME_EMAIL_ADDRESS);
	    $email_admin = api_get_setting('emailAdministrator');

		if (@api_mail('', $email_to, $email_subject, $email_body, $sender_name, $email_admin) == 1) {
			Display::display_confirmation_message(get_lang('YourPasswordHasBeenEmailed'));
		} else {
			$message = get_lang('SystemUnableToSendEmailContact').' '.Display :: encrypted_mailto_link(api_get_setting('emailAdministrator'), get_lang('PlatformAdmin')).".</p>";
		}
	}
	
	/**
	 * Handle encrypted password, send an email to a user with his password 
	 *
	 * @param int	user id
	 * @param bool	$by_username
	 *
	 * @author Olivier Cauberghe <olivier.cauberghe@UGent.be>, Ghent University
	 */
	public static function handle_encrypted_password($user, $by_username = false) {	
		global $_configuration;
		$email_subject = "[".api_get_setting('siteName')."] ".get_lang('LoginRequest'); // SUBJECT
	
		if ($by_username) { // Show only for lost password
			$user_account_list = self::get_user_account_list($user, true, $by_username); // BODY
			$email_to = $user['email'];
		} else {
			$user_account_list = self::get_user_account_list($user, true); // BODY
			$email_to = $user[0]['email'];
		}
			
		$secret_word = self::get_secret_word($email_to);
		$email_body = get_lang('DearUser')." :\n".get_lang('password_request')."\n";
		$email_body .= $user_account_list."\n-----------------------------------------------\n\n";
		$email_body .= get_lang('PasswordEncryptedForSecurity');
		//$email_body .= "\n\n".get_lang('Formula').",\n".get_lang('PlataformAdmin');
		$email_body .= "\n\n".get_lang('Formula').",\n".api_get_setting('administratorName')." ".api_get_setting('administratorSurname')."\n".get_lang('PlataformAdmin')." - ".api_get_setting('siteName');
		
		$sender_name = api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname'), null, PERSON_NAME_EMAIL_ADDRESS);
	    $email_admin = api_get_setting('emailAdministrator');
		
		if (@api_mail('', $email_to, $email_subject, $email_body, $sender_name, $email_admin) == 1) {
			Display::display_confirmation_message(get_lang('YourPasswordHasBeenEmailed'));
		} else {
			$message = get_lang('SystemUnableToSendEmailContact').' '.Display :: encrypted_mailto_link(api_get_setting('emailAdministrator'), get_lang('PlatformAdmin')).".</p>";
			Display::display_error_message($message, false);
		}
	}
	
	/**
	 * Gets the secret word
	 * @author Olivier Cauberghe <olivier.cauberghe@UGent.be>, Ghent University
	 */
	public static function get_secret_word($add) {
		global $_configuration;
		return $secret_word = md5($_configuration['security_key'].$add);
	}
	
	/**
	 * Resets a password
	 * @author Olivier Cauberghe <olivier.cauberghe@UGent.be>, Ghent University
	 */
	public static function reset_password($secret, $id, $by_username = false) {
		$tbl_user = Database::get_main_table(TABLE_MAIN_USER);
		$id = intval($id);
		$sql = "SELECT user_id AS uid, lastname AS lastName, firstname AS firstName, username AS loginName, password, email FROM ".$tbl_user." WHERE user_id=$id";
		$result = Database::query($sql);
		$num_rows = Database::num_rows($result);
	
		if ($result && $num_rows > 0) {
			$user = Database::fetch_array($result);
		} else {
			return get_lang('CouldNotResetPassword'); 
		}
	
		if (self::get_secret_word($user['email']) == $secret) { // OK, secret word is good. Now change password and mail it.
			$user['password'] = api_generate_password();
			$crypted = $user['password'];
			$crypted = api_get_encrypted_password($crypted);
			$sql = "UPDATE ".$tbl_user." SET password='$crypted' WHERE user_id=$id";
			$result = Database::query($sql);
			return self::send_password_to_user($user, $by_username);
		} else {
			return get_lang('NotAllowed');
		}
	}	
}
