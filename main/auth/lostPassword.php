<?php
/* For licensing terms, see /dokeos_license.txt */
/**
==============================================================================
 * SCRIPT PURPOSE :
 *
 * This script allows users to retrieve the password of their profile(s)
 * on the basis of their e-mail address. The password is send via email
 * to the user.
 *
 * Special case : If the password are encrypted in the database, we have
 * to generate a new one.
*
*	@todo refactor, move relevant functions to code libraries
*
*	@package dokeos.auth
==============================================================================
*/
// name of the language file that needs to be included
$language_file = "registration";
require ('../inc/global.inc.php');
require_once ('lost_password.lib.php');
require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
require_once(api_get_path(INCLUDE_PATH).'lib/mail.lib.inc.php');
$tool_name = get_lang('LostPassword');
Display :: display_header($tool_name);

$this_section = SECTION_CAMPUS;
$tool_name = get_lang('LostPass');

// Forbidden to retrieve the lost password
if (get_setting('allow_lostpassword') == "false") {
	api_not_allowed();
}
echo '<div class="actions-title">';
echo $tool_name;
echo '</div>';

if (isset ($_GET["reset"]) && isset ($_GET["id"])) {
	
	$msg = reset_password($_GET["reset"], $_GET["id"], true);
	$msg1= '<a href="'.api_get_path(WEB_PATH).'main/auth/lostPassword.php" class="fake_button_back" >'.get_lang('Back').'</a>';
	echo '<br/><br/><div class="actions" >'.$msg1.'</div>';
	
} else {
	$form = new FormValidator('lost_password');
	$form->addElement('text', 'user', get_lang('User'), array('size'=>'40'));
	$form->addElement('text', 'email', get_lang('Email'), array('size'=>'40'));
	
	$form->applyFilter('email','strtolower');
	$form->addElement('style_submit_button', 'submit', get_lang('Send'),'class="save"');
	
	// setting the rules
	$form->addRule('user', '<div class="required">'.get_lang('ThisFieldIsRequired'), 'required');
	
	if ($form->validate()) {
		$values = $form->exportValues();
		$user = $values['user'];
		$email = $values['email'];
		
		$condition = '';
		if (!empty($email)) {
			$condition = " AND LOWER(email) = '".mysql_real_escape_string($email)."' ";
		}
		
		$tbl_user = Database :: get_main_table(TABLE_MAIN_USER);
		$query = " SELECT user_id AS uid, lastname AS lastName, firstname AS firstName,
				   	username AS loginName, password, email, status AS status,
					official_code, phone, picture_uri, creator_id
				   FROM ".$tbl_user."
					WHERE ( username = '".mysql_real_escape_string($user)."' $condition ) ";
		
		$result = Database::query($query, __FILE__, __LINE__);
		$num_rows = Database::num_rows($result);
		
		if ($result && $num_rows > 0) {
			if ($num_rows > 1) {
				$by_username = false; // more than one user
				while ($data = Database::fetch_array($result)) {
					$user[] = $data;
				}
			} else {
				$by_username = true; // single user (valid user + email)
				$user = Database::fetch_array($result);
			}
			if ($userPasswordCrypted != 'none') {
				handle_encrypted_password($user, $by_username);
			} else {
				send_password_to_user($user, $by_username);
			}
		} else {
			Display::display_error_message(get_lang('NoUserAccountWithThisEmailAddress'));
		}
		
		$msg .= '<a href="'.api_get_path(WEB_PATH).'main/auth/lostPassword.php" class="fake_button_back" >'.get_lang('Back').'</a>';
		echo '<br/><br/><div class="actions" >'.$msg.'</div>';
		
	} else {
		
		echo '<p>';
		echo get_lang('EnterEmailUserAndWellSendYouPassword');
		echo '</p>';
		$form->display();
	}
}

Display :: display_footer();
//////////////////////////////////////////////////////////////////////////////
?>
