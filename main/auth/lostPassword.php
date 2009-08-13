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

// Forbidden to retrieve the lost password
if (get_setting('allow_lostpassword') == "false")
{
	api_not_allowed();
}
echo '<div class="actions-title">';
echo $tool_name;
echo '</div>';
$tbl_user = Database :: get_main_table(TABLE_MAIN_USER);
if (isset ($_GET["reset"]) && isset ($_GET["id"])) {
	$msg = reset_password($_GET["reset"], $_GET["id"]);	
	$msg1= '<a href="'.api_get_path(WEB_PATH).'main/auth/lostPassword.php" class="fake_button_back" >'.get_lang('Back').'</a>';
	echo '<br/><br/><div class="actions" >'.$msg1.'</div>';
} else {
	$form = new FormValidator('lost_password');
	$form->add_textfield('email', get_lang('Email'), false, 'size="40"');
	$form->applyFilter('email','strtolower');
	$form->addElement('style_submit_button', 'submit', get_lang('Send'),'class="save"');
	if ($form->validate())
	{
		$values = $form->exportValues();
		$email = $values['email'];
		$result = api_sql_query("SELECT user_id AS uid, lastname AS lastName, firstname AS firstName,
											username AS loginName, password, email, status AS status,
											official_code, phone, picture_uri, creator_id
											FROM ".$tbl_user."
											WHERE LOWER(email) = '".mysql_real_escape_string($email)."'
											AND   email != '' ", __FILE__, __LINE__);
		if ($result && Database::num_rows($result))
		{
			while ($data = Database::fetch_array($result))
			{
				$user[] = $data;
			}
			if ($userPasswordCrypted!='none')
			{
				$msg = handle_encrypted_password($user);
			}
			else
			{
				send_password_to_user($user);
			}
		}
		else
		{
			Display::display_error_message(get_lang('_no_user_account_with_this_email_address'));
		}
		$msg .= '<a href="'.api_get_path(WEB_PATH).'main/auth/lostPassword.php" class="fake_button_back" >'.get_lang('Back').'</a>';
		echo '<br/><br/><div class="actions" >'.$msg.'</div>';
	}
	else
	{
		echo '<p>';
		echo get_lang('_enter_email_and_well_send_you_password');
		echo '</p>';
		$form->display();
		?>
		<br/>
		<div class="actions">
		<a href="<?php echo api_get_path(WEB_PATH); ?>" class="fake_button_back" ><?php echo get_lang('Back'); ?></a>
		</div>
		<?php
	}
}

Display :: display_footer();
//////////////////////////////////////////////////////////////////////////////
?>