<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)

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

// TODO: Is this file deprecated?

/**
==============================================================================
*	@package dokeos.user
==============================================================================
*/

/*==========================
            INIT
  ==========================*/

// name of the language file that needs to be included
$language_file="registration";


include("../inc/global.inc.php");
require_once(api_get_path(INCLUDE_PATH).'lib/mail.lib.inc.php');
$this_section=SECTION_COURSES;

if (! ($is_courseAdmin || $is_platformAdmin)) api_not_allowed(true);

$currentCourseID   = $_course['sysCode'];
$currentCourseName = $_course['official_code'];
$tbl_user          = "user";
$tbl_courseUser    = "course_rel_user";



/*==========================
         DATA CHECKING
  ==========================*/

if($register)
{
	/*
	 * Fields Checking
	 */

	$lastname_form      = trim($lastname_form);
	$firstname_form   = trim($firstname_form);
	$password_form = trim($password_form);
	$username_form = trim($username_form);
	$email_form    = trim($email_form);
	$official_code_form = trim($official_code_form);

	// empty field checking

	if(empty($lastname_form) || empty($firstname_form) || empty($password_form) || empty($username_form) || empty($email_form))
	{
		$dataChecked = false;
		$message     = get_lang('Filled');
	}

	// valid mail address checking

	elseif(!eregi('^[0-9a-z_.-]+@([0-9a-z-]+\.)+([0-9a-z]){2,4}$',$email_form))
	{
		$dataChecked = false;
		$message     = get_lang('EmailWrong');
	}
	else
	{
		$dataChecked = true;
	}

	// prevent conflict with existing user account

	if($dataChecked)
	{
		$result=Database::query("SELECT user_id,
		                       (username='$username_form') AS loginExists,
		                       (lastname='$lastname_form' AND firstname='$firstname_form' AND email='$email_form') AS userExists
		                     FROM $tbl_user
		                     WHERE username='$username_form' OR (lastname='$lastname_form' AND firstname='$firstname_form' AND email='$email_form')
		                     ORDER BY userExists DESC, loginExists DESC");

		if(mysql_num_rows($result))
		{
			while($user=Database::fetch_array($result))
			{
				// check if the user is already registered to the platform

				if($user['userExists'])
				{
					$userExists = true;
					$userId     = $user['user_id'];
					break;
				}

				// check if the login name choosen is already taken by another user

				if($user['loginExists'])
				{
					$loginExists = true;
					$userId      = 0;

					$message     = get_lang('UserNo')." (".stripslashes($username_form).") ".get_lang('Taken');

					break;
				}
			}				// end while $result
		}					// end if num rows
	}						// end if datachecked





/*=============================
  NEW USER REGISTRATION PROCESS
  =============================*/

	if($dataChecked && !$userExists && !$loginExists)
	{
			/*---------------------------
			      PLATFORM REGISTRATION
			  ----------------------------*/

		if ($_cid) $platformStatus = STUDENT;          // course registrartion context...
		else       $platformStatus = $platformStatus; // admin section of the platform context...

		//if ($userPasswordCrypted) $pw = md5($password_form);
		//else                      $pw = $password_form;
		$pw = api_get_encrypted_password($password_form);
		$result = Database::query("INSERT INTO $tbl_user
		                       SET lastname       = '$lastname_form',
		                           firstname    = '$firstname_form',
		                           username  = '$username_form',
		                           password  = '$pw',
		                           email     = '$email_form',
		                           status    = '$platformStatus',
		                           official_code = '$official_code_form',
		                           creator_id = '".$_user['user_id']."'");

		$userId = mysql_insert_id();

		if ($userId) $platformRegSucceed = true;
	}

	if($userId && $_cid)
	{
		/*
		  Note : As we temporarly use this script in the platform administration
		  section to also add user to the platform, We have to prevent course
		  registration. That's why we check if $_cid is initialized, it gives us
		  an hint about the use context of the script
		*/

			/*---------------------------
			      COURSE REGISTRATION
			  ----------------------------*/

		/*
		 * check the return value of the query
		 * if 0, the user is already registered to the course
		 */

		if (Database::query("INSERT INTO $tbl_courseUser
						SET user_id     = '$userId',
							course_code  = '$currentCourseID',
							status      = '$admin_form',
							tutor_id       = '$tutor_form'"))
		{
			$courseRegSucceed = true;
		}
	} // if $platformRegSucceed && $_cid


	/*---------------------------
	   MAIL NOTIFICATION TO NEW USER
	  ----------------------------*/

	if ($platformRegSucceed)
	{

		$emailto       = "$lastname_form $firstname_form <$email_form>";
		$emailfromaddr = $administratorEmail;
		$emailfromname = api_get_setting('siteName');
		$emailsubject  = get_lang('YourReg').' '.api_get_setting('siteName');

		$emailheaders  = "From: ".api_get_setting('administratorSurname')." ".api_get_setting('administratorName')." <".$administratorEmail.">\n";
		$emailheaders .= "Reply-To: ".$administratorEmail."\n";
		$emailheaders .= "Return-Path: ".$administratorEmail."\n";
		$emailheaders .= "charset: ".$charset."\n";
		$emailheaders .= "X-Mailer: PHP/" . phpversion() . "\n";
		$emailheaders .= "X-Sender-IP: $REMOTE_ADDR"; // (small security precaution...)
		$recipient_name = $firstname_form.' '.$lastname_form;
		$sender_name = api_get_setting('administratorName').' '.api_get_setting('administratorSurname');
	    $email_admin = api_get_setting('emailAdministrator');

		$portal_url = $_configuration['root_web'];
		if ($_configuration['multiple_access_urls']==true) {
			$access_url_id = api_get_current_access_url_id();
			if ($access_url_id != -1 ){
				$url = api_get_access_url($access_url_id);
				$portal_url = $url['url'];
			}
		}

		if ($courseRegSucceed)
		{
			$emailbody = get_lang('Dear')." ".stripslashes("$firstname_form $lastname_form").",\n".get_lang('OneResp')." $currentCourseName ".get_lang('RegYou')." ".api_get_setting('siteName')." ".get_lang('Settings')." $username_form\n".get_lang('Pass').": $password_form\n".get_lang('Address')." ".api_get_setting('siteName')." ".get_lang('Is').": ".$portal_url."\n".get_lang('Problem')."\n".get_lang('Formula').",\n".api_get_setting('administratorSurname')." ".api_get_setting('administratorName')."\n".get_lang('Manager')." ".api_get_setting('siteName')." \nT. ".api_get_setting('administratorTelephone')."\n".get_lang('Email').": ".api_get_setting('emailAdministrator')."\n";
			$message = get_lang('TheU')." ".stripslashes("$firstname_form $lastname_form")." ".get_lang('AddedToCourse')."<a href=\"user.php\">".get_lang('BackUser')."</a>\n";
		}
		else
		{
			$emailbody = get_lang('Dear')."  $firstname_form $lastname_form,\n ".get_lang('YouAreReg')."  ".api_get_setting('siteName')."  ".get_lang('Settings')." $username_form\n".get_lang('Pass').": $password_form\n".get_lang('Address')." ".api_get_setting('siteName')." ".get_lang('Is').": ".$portal_url."\n".get_lang('Problem')."\n".get_lang('Formula').",\n".api_get_setting('administratorSurname')." ".api_get_setting('administratorName')."\n".get_lang('Manager')." ".api_get_setting('siteName')." \nT. ".api_get_setting('administratorTelephone')."\n".get_lang('Email').": ".api_get_setting('emailAdministrator')."\n";

			$message = stripslashes("$firstname_form $lastname_form")." ".get_lang('AddedU');
		}

		@api_mail($recipient_name, $email_form, $emailsubject, $emailbody, $sender_name,$email_admin);

		/*
		 * remove <form> variables to prevent any pre-filled fields
		 */

		unset($lastname_form, $firstname_form, $username_form, $password_form, $email_form, $admin_form, $tutor_form);

	} 	// end if ($platformRegSucceed)
	//else
	//{
	//	$message = get_lang('UserAlreadyRegistered');
	//}

} // end if register request

$interbreadcrumb[] = array ("url"=>"user.php", "name"=> get_lang('Users'));

$nameTools        = get_lang('AddAU');

Display::display_header($nameTools,"User");


?>



<h3><?php echo get_lang('Users'); ?></h3>

<table border="0" cellpadding="0" cellspacing="0" width="100%">
<tr>
  <td><h4><?php echo $nameTools; ?></h4></td>
  <td></td>
</tr>
</table>

<?php
/*==========================
     ADD ONE USER FORM
  ==========================*/
?>

<?php echo get_lang('OneByOne'); ?>. <?php echo get_lang('UserOneByOneExplanation'); ?>

<form method="post" action="<?php echo api_get_self(); ?>?register=yes">
<table cellpadding="3" cellspacing="0" border="0">

<?php
if(!empty($message))
{
?>

<tr>
  <td colspan="2">

<?php
	Display::display_normal_message($message); //main API
?>

  </td>
</tr>

<?php
}
?>

<tr>
<td align="right"><?php echo get_lang('LastName'); ?> :</td>
<td><input type="text" size="15" name="lastname_form" value="<?php echo htmlentities(stripslashes($lastname_form),ENT_QUOTES,$charset); ?>" /></td>
</tr>
<tr>
<td align="right"><?php echo get_lang('FirstName'); ?> :</td>
<td><input type="text" size="15" name="firstname_form" value="<?php echo api_htmlentities(stripslashes($firstname_form),ENT_QUOTES,$charset); ?>" /></td>
</tr>
<tr>
<td align="right"><?php echo get_lang('OfficialCode'); ?> :</td>
<td><input type="text" size="15" name="official_code_form" value="<?php echo api_htmlentities(stripslashes($official_code_form),ENT_QUOTES,$charset); ?>" /></td>
</tr>
<tr>
<td align="right"><?php echo  get_lang('UserName') ?> :</td>
<td><input type="text" size="15" name="username_form" value="<?php echo api_htmlentities(stripslashes($username_form), ENT_QUOTES, $charset); ?>" /></td>
</tr>
<tr>
<td align="right"><?php echo  get_lang('Pass') ?> :</td>
<td><input type="password" size="15" name="password_form" value="<?php echo api_htmlentities(stripslashes($password_form), ENT_QUOTES, $charset) ?>" /></td>
</tr>
<tr>
<td align="right"><?php echo  get_lang('Email'); ?> :</td>
<td><input type="text" size="15" name="email_form" value="<?php echo $email_form; ?>" /></td>
</tr>
<tr>
<?php

if ($_cid) // if we're inside a course, then it's a course registration
{

?>
<td align="right"><?php echo  get_lang('Tutor'); ?> :</td>
<td><input class="checkbox" type="radio" name="tutor_form" value="0" <?php if(!isset($tutor_form) || !$tutor_form) echo 'checked="checked"'; ?> /> <?php echo get_lang('No'); ?>
<input class="checkbox" type="radio" name="tutor_form" value="1" <?php if($tutor_form == 1) echo 'checked="checked"'; ?> /> <?php echo  get_lang('Yes') ?></td>
</tr>
<tr>
<td align="right"><?php echo  get_lang('Manager') ?> :</td>
<td><input class="checkbox" type="radio" name="admin_form" value="5" <?php if(!isset($admin_form) || $admin_form == 5) echo 'checked="checked"'; ?> /> <?php echo get_lang('No') ?>
<input class="checkbox" type="radio" name="admin_form" value="1" <?php if($admin_form == 1) echo 'checked="checked"'; ?> /> <?php echo  get_lang('Yes'); ?></td>
</tr>
<?php

}			// end if $_cid - for the case we're not in a course registration
			// but a platform registration
else
{

?>
<tr>
<td align="right"><?php echo  get_lang('Status') ?> : </td>
<td>
<select name="platformStatus">
<option value="<?php echo STUDENT ?>"><?php echo  get_lang('RegStudent') ?></option>
<option value="<?php echo COURSEMANAGER ?>"><?php echo  get_lang('RegAdmin') ?></option>
</select>
</td>
</tr>

<?php
} // end else if $_cid
?>
<tr>
<td>&nbsp;</td>
<td><input type="submit" name="submit" value="<?php echo  get_lang('Ok') ?>" /></td>
</tr>
</table>
</form>

<?php

/*==========================
    IMPORT XML/CSV USER LIST
  ==========================*/

if($is_platformAdmin)
{
	echo "<a href=\"".$rootAdminWeb."importUserList.php\">".get_lang('UserMany')."</a>";
} // if is_platformAdmin
else
{
	echo "<p>".get_lang('IfYouWantToAddManyUsers')."</p>";
}

Display::display_footer();
?>
