<?php
/* For licensing terms, see /license.txt */
/**
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
*	@package chamilo.auth
*/
/**
 * Code
 */
// name of the language file that needs to be included
$language_file = 'registration';

require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'login.lib.php';
require_once api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php';
require_once api_get_path(LIBRARY_PATH).'mail.lib.inc.php';

$tool_name = get_lang('LostPassword');
Display :: display_header($tool_name);

$this_section 	= SECTION_CAMPUS;
$tool_name 		= get_lang('LostPass');

// Forbidden to retrieve the lost password
if (api_get_setting('allow_lostpassword') == 'false') {
	api_not_allowed();
}
echo Display::tag('h1', $tool_name);

if (isset ($_GET['reset']) && isset ($_GET['id'])) {
	$msg = Login::reset_password($_GET["reset"], $_GET["id"], true);
	$msg1= '<a href="'.api_get_path(WEB_CODE_PATH).'auth/lostPassword.php" class="fake_button_back" >'.get_lang('Back').'</a>';	
	echo '<br /><br /><div class="actions" >'.$msg1.'</div>';
} else {
	$form = new FormValidator('lost_password');
	$form->addElement('text', 'user', get_lang('LoginOrEmailAddress'), array('size'=>'40'));

	//$form->applyFilter('email','strtolower');
	$form->addElement('style_submit_button', 'submit', get_lang('Send'),'class="save"');

	// setting the rules
	$form->addRule('user', get_lang('ThisFieldIsRequired'), 'required');

	if ($form->validate()) {
		$values = $form->exportValues();
		
        if(strpos($values['user'],'@')){
            $user = strtolower($values['user']);
            $email = TRUE;
        } else {
            $user = strtolower($values['user']);
            $email = FALSE;
        }

		$condition = '';
		if ($email) {
			$condition = "LOWER(email) = '".Database::escape_string($user)."' ";
		} else {
            $condition = "LOWER(username) = '".Database::escape_string($user)."'";
        }

		$tbl_user = Database :: get_main_table(TABLE_MAIN_USER);
		$query = "SELECT user_id AS uid, lastname AS lastName, firstname AS firstName, ".
				 "username AS loginName, password, email, status AS status, ".
		         "official_code, phone, picture_uri, creator_id ".
				 "FROM ".$tbl_user." ".
				 "WHERE ( $condition ) ";

		$result 	= Database::query($query);
		$num_rows 	= Database::num_rows($result);

		if ($result && $num_rows > 0) {
            $by_username = true;
            $users = Database::store_result($result);
            foreach( $users as $user ) {
                if ($userPasswordCrypted != 'none') {
                    Login::handle_encrypted_password($user, $by_username);
                } else {
                    Login::send_password_to_user($user, $by_username);
                }
            }
		} else {
			Display::display_error_message(get_lang('NoUserAccountWithThisEmailAddress'));			
		}
		//$msg .= '<a href="'.api_get_path(WEB_CODE_PATH).'auth/lostPassword.php" class="fake_button_back" >'.get_lang('Back').'</a>';
		//echo '<br /><br /><div class="actions" >'.$msg.'</div>';

	} else {		
		echo get_lang('EnterEmailUserAndWellSendYouPassword');
		echo '<br /><br />';				
		$form->display();
	}
}
Display::display_footer();
