<?php

// $Id: lostPassword.php 18942 2009-03-10 23:42:21Z juliomontoya $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) various contributors

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact: Dokeos, 181 rue Royale, B-1000 Brussels, Belgium, info@dokeos.com
==============================================================================
*/
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
api_display_tool_title($tool_name);
$tbl_user = Database :: get_main_table(TABLE_MAIN_USER);
if (isset ($_GET["reset"]) && isset ($_GET["id"]))
{
	$msg = reset_password($_GET["reset"], $_GET["id"]);
	$msg .= '. <br/>'.get_lang('YourPasswordHasBeenEmailed');
	$msg .= '<br/><br/><a href="'.api_get_path(WEB_PATH).'main/auth/lostPassword.php">&lt;&lt; '.get_lang('Back').'</a>';
	echo $msg;
}
else
{
	$form = new FormValidator('lost_password');
	$form->add_textfield('email', get_lang('Email'), false, 'size="40"');
	$form->applyFilter('email','strtolower');
	$form->addElement('submit', 'submit', get_lang('Ok'));
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
		if ($result && mysql_num_rows($result))
		{
			while ($data = mysql_fetch_array($result))
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
		$msg .= '<br/><br/><a href="'.api_get_path(WEB_PATH).'main/auth/lostPassword.php">&lt;&lt; '.get_lang('Back').'</a>';
		echo '<p>'.$msg.'</p>';
	}
	else
	{
		echo '<p>';
		echo get_lang('_enter_email_and_well_send_you_password');
		echo '</p>';
		$form->display();
		?>
		<a href="<?php echo api_get_path(WEB_PATH); ?>">&lt;&lt; <?php echo get_lang('Back'); ?></a>
		<?php
	}
}

Display :: display_footer();
//////////////////////////////////////////////////////////////////////////////
?>