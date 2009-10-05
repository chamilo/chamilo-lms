<?php
/* For licensing terms, see /dokeos_license.txt */

/**
 * Get email headers
 *
 * @return string
 * @author Olivier Cauberghe <olivier.cauberghe@UGent.be>, Ghent University
 */
function get_email_headers()
{
	global $charset;
	$emailHeaders = "From: \"".addslashes(api_get_setting('administratorSurname')." ".api_get_setting('administratorName'))."\" <".api_get_setting('emailAdministrator').">\n";
	$emailHeaders .= "Reply-To: ".api_get_setting('emailAdministrator')."\n";
	$emailHeaders .= "Return-Path: ".api_get_setting('emailAdministrator')."\n";
	$emailHeaders .= "X-Sender: ".api_get_setting('emailAdministrator')."\n";	
	$emailHeaders .= "X-Mailer: PHP / ".phpversion()."\n";
	$emailHeaders .= "Content-Type: text/plain;\n\tcharset=\"".$charset."\"\n";
	$emailHeaders .= "Mime-Version: 1.0";
	return $emailHeaders;
}
/**
 * Enter description here...
 *
 * @param unknown_type $user
 * @param boolean $reset
 * @param boolean $by_username
 * @return unknown
 * @author Olivier Cauberghe <olivier.cauberghe@UGent.be>, Ghent University
 */
function get_user_account_list($user, $reset = false, $by_username = false)
{
	global $_configuration;
	$portal_url = $_configuration['root_web'];
	if ($_configuration['multiple_access_urls']==true) {
		$access_url_id = api_get_current_access_url_id();				
		if ($access_url_id != -1 ){
			$url = api_get_access_url($access_url_id);
			$portal_url = $url['url'];
		}
	}
	
	if ($reset == true) {
		
		if ($by_username == true) {
			
			$secretword = get_secret_word($user["email"]);
			if ($reset)	{
				$reset_link = $portal_url."main/auth/lostPassword.php?reset=".$secretword."&id=".$user['uid'];			
			} else {
				$reset_link = get_lang('Pass')." : $user[password]";
			}
			$userAccountList = get_lang('YourRegistrationData')." : \n".get_lang('UserName').' : '.$user['loginName']."\n".get_lang('ResetLink').' : '.$reset_link.'';
			
			if ($userAccountList) {
				$userAccountList = "\n------------------------\n" . $userAccountList;
			}
			
		} else {
			
			foreach ($user as $thisUser) {
				$secretword = get_secret_word($thisUser["email"]);
				if ($reset)	{								
					$reset_link = $portal_url."main/auth/lostPassword.php?reset=".$secretword."&id=".$thisUser['uid'];			
				} else {
					$reset_link = get_lang('Pass')." : $thisUser[password]";
				}
				$userAccountList[] = get_lang('YourRegistrationData')." : \n".get_lang('UserName').' : '.$thisUser['loginName']."\n".get_lang('ResetLink').' : '.$reset_link.'';
			}
		
			if ($userAccountList) {
				$userAccountList = implode("\n------------------------\n", $userAccountList);
			}
		
		}
		
	} else {
		
		if ($by_username == false) {
			$user = $user[0];
		}
	    $reset_link = get_lang('Pass')." : $user[password]";
       	$userAccountList = get_lang('YourRegistrationData')." : \n".get_lang('UserName').' : '.$user['loginName']."\n".$reset_link.'';	
		
	}
	return $userAccountList;
}
/**
 * This function sends the actual password to the user
 *
 * @param unknown_type $user
 * @author Olivier Cauberghe <olivier.cauberghe@UGent.be>, Ghent University
 */
function send_password_to_user($user, $by_username = false)
{
	global $charset;
	global $_configuration;
	$emailHeaders = get_email_headers(); // Email Headers
	$emailSubject = "[".get_setting('siteName')."] ".get_lang('LoginRequest'); // SUBJECT
	
	if ($by_username == true) { // Show only for lost password
		$userAccountList = get_user_account_list($user, false, $by_username); // BODY
		$emailTo = $user["email"];
	} else {
		$userAccountList = get_user_account_list($user); // BODY
		$emailTo = $user[0]["email"];
	}
	
	$portal_url = $_configuration['root_web'];
	if ($_configuration['multiple_access_urls'] == true) {
		$access_url_id = api_get_current_access_url_id();				
		if ($access_url_id != -1 ){
			$url = api_get_access_url($access_url_id);
			$portal_url = $url['url'];
		}
	}
	
	$emailBody = get_lang('YourAccountParam')." ".$portal_url."\n\n$userAccountList";
	// SEND MESSAGE			
	$sender_name = get_setting('administratorName').' '.get_setting('administratorSurname');
    $email_admin = get_setting('emailAdministrator');			
				
	if (@api_mail('', $emailTo, $emailSubject, $emailBody, $sender_name, $email_admin) == 1) {
		Display::display_confirmation_message(get_lang('YourPasswordHasBeenEmailed'));
	} else {
		$message = get_lang('SystemUnableToSendEmailContact') . ' ' . Display :: encrypted_mailto_link(get_setting('emailAdministrator'), get_lang('PlatformAdmin')).".</p>";
	}
}
/**
 * Enter description here...
 *
 * @param unknown_type	$user
 * @param bool	$by_username
 * @return unknown
 *
 * @author Olivier Cauberghe <olivier.cauberghe@UGent.be>, Ghent University
 */
function handle_encrypted_password($user, $by_username = false)
{
	global $charset;
	global $_configuration;
	
	$emailHeaders = get_email_headers(); // Email Headers
	$emailSubject = "[".get_setting('siteName')."] ".get_lang('LoginRequest'); // SUBJECT
	
	if ($by_username == true) { // Show only for lost password
		$userAccountList = get_user_account_list($user, true, $by_username); // BODY
		$emailTo = $user["email"];
	} else {
		$userAccountList = get_user_account_list($user, true); // BODY
		$emailTo = $user[0]["email"];
	}
	
	$secretword = get_secret_word($emailTo);
	$emailBody = get_lang('DearUser')." :\n".get_lang("password_request")."\n\n";
	$emailBody .= "-----------------------------------------------\n".$userAccountList."\n-----------------------------------------------\n\n";
	$emailBody .= get_lang('PasswordEncryptedForSecurity');
	$emailBody .= "\n\n".get_lang('Formula').",\n".get_lang('PlataformAdmin');
	$sender_name = get_setting('administratorName').' '.get_setting('administratorSurname');
    $email_admin = get_setting('emailAdministrator');
	
	if (@api_mail('', $emailTo, $emailSubject, $emailBody, $sender_name, $email_admin) == 1) {
		Display::display_confirmation_message(get_lang('YourPasswordHasBeenEmailed'));
	} else {
		$message = get_lang('SystemUnableToSendEmailContact') . ' ' . Display :: encrypted_mailto_link(get_setting('emailAdministrator'), get_lang('PlatformAdmin')).".</p>";
		Display::display_error_message($message, false);
	}
}
/**
 * Enter description here...
 * @author Olivier Cauberghe <olivier.cauberghe@UGent.be>, Ghent University
 */
function get_secret_word($add)
{
	global $_configuration;
	return $secretword = md5($_configuration['security_key'].$add);
}
/**
 * Enter description here...
 * @author Olivier Cauberghe <olivier.cauberghe@UGent.be>, Ghent University
 */
function reset_password($secret, $id, $by_username = false)
{
	$tbl_user = Database::get_main_table(TABLE_MAIN_USER);
	$id = intval($id);
	$sql = "SELECT user_id AS uid, lastname AS lastName, firstname AS firstName, username AS loginName, password, email FROM ".$tbl_user." WHERE user_id=$id";
	$result = Database::query($sql,__FILE__,__LINE__);
	$num_rows = Database::num_rows($result);
	
	if ($result && $num_rows > 0) {
		$user = Database::fetch_array($result);
	} else {
		return "Could not reset password.";
	}
	
	if (get_secret_word($user["email"]) == $secret) { // OK, secret word is good. Now change password and mail it.
		
		$user["password"] = api_generate_password();
		$crypted = $user["password"];
		$crypted = api_get_encrypted_password($crypted);
		$sql = "UPDATE ".$tbl_user." SET password='$crypted' WHERE user_id=$id";
		$result = Database::query($sql,__FILE__,__LINE__);
		return send_password_to_user($user, $by_username);
		
	} else {
		
		return "Not allowed.";
		
	}
}
?>
