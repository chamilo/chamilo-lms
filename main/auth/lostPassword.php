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
require_once api_get_path(LIBRARY_PATH).'mail.lib.inc.php';
require_once api_get_path(LIBRARY_PATH).'custompages.lib.php';

// Custom pages
// Had to move the form handling in here, because otherwise there would 
// already be some display output.
global $_configuration;
if (api_get_setting('use_custom_pages') == 'true') {
	if (isset ($_POST['user']) && isset ($_POST['email'])) {
		$user = $_POST['user'];
		$email = $_POST['email'];

		$condition = '';
		if (!empty($email)) {
			$condition = " AND LOWER(email) = '".Database::escape_string($email)."' ";
		}

		$tbl_user = Database :: get_main_table(TABLE_MAIN_USER);
		$query = " SELECT user_id AS uid, lastname AS lastName, firstname AS firstName,
					username AS loginName, password, email, status AS status,
					official_code, phone, picture_uri, creator_id
					FROM ".$tbl_user."
					WHERE ( username = '".Database::escape_string($user)."' $condition ) ";

		$result 	= Database::query($query);
		$num_rows 	= Database::num_rows($result);

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
			if ($_configuration['password_encryption'] != 'none') {
				Login::handle_encrypted_password($user, $by_username);
			} else {
				Login::send_password_to_user($user, $by_username);
			}
		} else {
			Display::display_error_message(get_lang('NoUserAccountWithThisEmailAddress'));
		}
		$msg = Login::reset_password($_GET["reset"], $_GET["id"], true);
		CustomPages::displayPage('lostpassword-feedback');
	}
	else {
		CustomPages::displayPage('lostpassword');
	}
}
$tool_name = get_lang('LostPassword');
Display :: display_header($tool_name);

$this_section 	= SECTION_CAMPUS;
$tool_name 		= get_lang('LostPass');

// Forbidden to retrieve the lost password
if (api_get_setting('allow_lostpassword') == 'false') {
	api_not_allowed();
}

if (isset ($_GET['reset']) && isset ($_GET['id'])) {
	//$msg = Login::reset_password($_GET["reset"], $_GET["id"], true);
	$msg1= '<a href="'.api_get_path(WEB_CODE_PATH).'auth/lostPassword.php" class="fake_button_back" >'.get_lang('Back').'</a>';	
	echo '<br /><br /><div class="actions" >'.$msg1.'</div>';
} else {
	$form = new FormValidator('lost_password');
    $form->addElement('header', '', $tool_name);
	$form->addElement('text', 'user', array(get_lang('LoginOrEmailAddress'), get_lang('EnterEmailUserAndWellSendYouPassword')), array('size'=>'40'));

	//$form->applyFilter('email','strtolower');
	$form->addElement('style_submit_button', 'submit', get_lang('Send'),'class="a_button gray"');

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
                if ($_configuration['password_encryption'] != 'none') {
                    Login::handle_encrypted_password($user, $by_username);
                } else {
                    Login::send_password_to_user($user, $by_username);
                }
            }
		} else {
			Display::display_warning_message(get_lang('NoUserAccountWithThisEmailAddress'));			
		}
	} else {						
		$form->display();
	}
}
Display::display_footer();
