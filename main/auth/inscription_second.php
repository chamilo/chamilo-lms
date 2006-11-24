<?php // $Id: inscription_second.php 10190 2006-11-24 00:23:20Z pcool $ 
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
*	This script reacts on the form data normally delivered by inscription.php
*	to register users into Dokeos.
*	(but malicious users can easily send POST data from their own scripts).
*
*	@package dokeos.auth
*   @deprecated File not in use
============================================================================== 
*/

/*==========================
             INIT
  ==========================*/

$langFile = "registration";
require("../inc/global.inc.php");

$TABLEUSER      = Database::get_main_table(TABLE_MAIN_USER);

define ("CHECK_PASS_EASY_TO_FIND", false);

if (!isset($userPasswordCrypted))  $userPasswordCrypted	 = false;

$regDataOk = false; // default value...

if(!empty($_POST['submitRegistration']))
{
	$regexp = "^[0-9a-z_\.-]+@(([0-9]{1,3}\.){3}[0-9]{1,3}|([0-9a-z][0-9a-z-]*[0-9a-z]\.)+[a-z]{2,4})$";
	$uname         = trim ($_POST["uname"     ]);
	$email         = trim ($_POST["email"     ]);
	$official_code = trim ($_POST["official_code" ]);
	$lastname      = trim ($_POST["lastname"  ]);
	$firstname     = trim ($_POST["firstname" ]);
	$password      = trim ($_POST["password"  ]);
	$password1     = trim ($_POST["password1" ]);
	$language	= $_POST['form_user_language'];
	
	$status=($_POST['status'] == COURSEMANAGER)?COURSEMANAGER:STUDENT;

	$uname=eregi_replace('[^a-z0-9_.-]','_',strtr(stripslashes($uname),'ÀÁÂÃÄÅàáâãäåÒÓÔÕÖØòóôõöøÈÉÊËèéêëÇçÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ','AAAAAAaaaaaaOOOOOOooooooEEEEeeeeCcIIIIiiiiUUUUuuuuyNn'));

	/*==========================
	   DATA SUBIMITED CHECKIN
	  ==========================*/

	// CHECK THE LANGUAGE OF THE USER PROFILE
	// we use the platform language if the user cannot select his language or if the chosen language is not valid (spoofed form)
	if (get_setting('registration', 'language') == "true")
	{
	// check if the submitted value is a valid language. If not we use the platform language (against form sppofing)
		$language_list=api_get_languages();
		$language_name=$language_list['name'];
		$language_folder=$language_list['folder'];
		if (!in_array($language,$language_folder))
		{
			$language=get_setting('platformLanguage');
		}

	}
	else
	{
	// we use the platform languange as the language for the profile
		$language=get_setting('platformLanguage');
	}




	// CHECK IF THERE IS NO EMPTY FIELD

	if (
		   empty($lastname)
		OR empty($firstname)
		OR (empty($official_code) 
			AND (get_setting('registration','officialcode')=="true"))
		OR empty($password1)
		OR empty($password)
		OR empty($uname)
		OR (empty($email) AND api_get_setting('registration','email')=="true")
			)
	{
		$regDataOk = false;

		unset($password1, $password);

		$error_message .=	"<p>".get_lang('EmptyFields')."</p>\n";
	}

	// CHECK IF THE TWO PASSWORD TOKEN ARE IDENTICAL

	elseif($password1 != $password)
	{
		$regDataOk = false;
		unset($password1, $password);

		$error_message .=		"<p>".get_lang('PassTwice')."</p>\n";
	}
	elseif(CHECK_PASS_EASY_TO_FIND && !api_check_password($password))
	{
		$error_message .= "<p>".get_lang('PassTooEasy')." : <code>".api_generate_password()."</code><br></p>";
	}
	// CHECK EMAIL ADDRESS VALIDITY

    elseif( !empty($email) && ! eregi( $regexp, $email ))
	{
		$regDataOk = false;
		unset($password1, $password, $email);

		$error_message .=	"<p>".get_lang('EmailWrong').".</p>\n";
	}

	// CHECK IF THE LOGIN NAME IS ALREADY OWNED BY ANOTHER USER

	else
	{
		$result = api_sql_query("SELECT user_id FROM $TABLEUSER
							   WHERE username='$uname'");

		if (mysql_num_rows($result) > 0)
		{
			$regDataOk = false;
			unset($password1, $password, $uname);

			$error_message .=	"<p>".get_lang('UserFree')."</p>";
		}
		else
		{
			$regDataOk = true;
		}
	}
}
$nameTools = get_lang('Registration');
if ( ! $regDataOk)
{
	Display::display_header($nameTools);
	api_display_tool_title($nameTools);
	echo 	$error_message;
	echo	"<p>",
			"<a href=\"inscription.php?lastname=",urlencode(stripslashes($lastname)),"&firstname=",urlencode(stripslashes($firstname)),"&official_code=",urlencode(stripslashes($official_code)),"&uname=",urlencode(stripslashes($uname)),"&email=",urlencode(stripslashes($email)),"&status=",$status,"\">",
			get_lang('Again'),
			"</a>",
			"</p>\n";
	Display::display_footer();
	exit;		
	
}


/*> > > > > > > > > > > > REGISTRATION ACCEPTED < < < < < < < < < < < <*/

if ($regDataOk)
{
	/*-----------------------------------------------------
	  STORE THE NEW USER DATA INSIDE THE CLAROLINE DATABASE
	  -----------------------------------------------------*/

	api_sql_query("INSERT INTO ".$TABLEUSER."
	             SET lastname     = '".$lastname."',
	                 firstname   	= '".$firstname."',
	                 username 	= '".$uname."',
	                 password 	= '".($userPasswordCrypted?md5($password):$password)."',
	                 email    	= '".$email."',
	                 status   	= '".$status."',
	                 official_code	= '".$official_code."',
					 language	= '".$language."'
					 ");

	$_user['user_id'] = mysql_insert_id();


if ($_user['user_id'])
{
	/*--------------------------------------
	          SESSION REGISTERING
	  --------------------------------------*/

		$_user['firstName']     = stripslashes($firstname);
		$_user['lastName' ]     = stripslashes($lastname);
		$_user['mail'     ]     = $email;
		$_user['language'] 		= $language; 
		$is_allowedCreateCourse = ($status == 1) ? true : false ;

    	api_session_register('_uid');
		api_session_register('_user');
		api_session_register('is_allowedCreateCourse');

        //stats
        include(api_get_path(LIBRARY_PATH)."events.lib.inc.php");
        event_login();
        // last user login date is now
        $user_last_login_datetime = 0; // used as a unix timestamp it will correspond to : 1 1 1970

        api_session_register('user_last_login_datetime');

	/*--------------------------------------
	             EMAIL NOTIFICATION
	  --------------------------------------*/

	if(strstr($email,'@'))
	{
		// Lets predefine some variables. Be sure to change the from address!

		$emailto       = "\"$firstname $lastname\" <$email>";
		$emailfromaddr = api_get_setting('emailAdministrator');
		$emailfromname = api_get_setting('siteName');
		$emailsubject  = "[".get_setting('siteName')."] ".get_lang('YourReg')." ".get_setting('siteName');

		// The body can be as long as you wish, and any combination of text and variables

		$emailbody=get_lang('Dear')." ".stripslashes("$firstname $lastname").",\n\n".get_lang('YouAreReg')." ". get_setting('siteName') ." ".get_lang('Settings')." ". $uname ."\n". get_lang('Pass')." : ".stripslashes($password)."\n\n" .get_lang('Address') ." ". api_get_setting('siteName') ." ". get_lang('Is') ." : ". $_configuration['root_web'] ."\n\n". get_lang('Problem'). "\n\n". get_lang('Formula').",\n\n".get_setting('administratorName')." ".get_setting('administratorSurname')."\n". get_lang('Manager'). " ".get_setting('siteName')."\nT. ".get_setting('administratorTelephone')."\n" .get_lang('Email') ." : ".get_setting('emailAdministrator');

		// Here we are forming one large header line
		// Every header must be followed by a \n except the last
		$emailheaders = "From: ".get_setting('administratorSurname')." ".get_setting('administratorName')." <".get_setting('emailAdministrator').">\n";
		$emailheaders .= "Reply-To: ".get_setting('emailAdministrator');

		// Because I predefined all of my variables, this api_send_mail() function looks nice and clean hmm?
		@api_send_mail( $emailto, $emailsubject, $emailbody, $emailheaders);
	}
}


Display::display_header($nameTools);
api_display_tool_title($nameTools);

	echo "<p>".get_lang('Dear')." ".stripslashes("$firstname $lastname").",<br><br>".get_lang('PersonalSettings').".</p>\n";

	if(!empty($email))
	{
		echo "<p>".get_lang('MailHasBeenSent').".</p>";
	}

	if($is_allowedCreateCourse)
	{
		echo "<p>",get_lang('NowGoCreateYourCourse'),".</p>\n";
		$actionUrl = "../create_course/add_course.php";
	}
	else
	{
		echo "<p>",get_lang('NowGoChooseYourCourses'),".</p>\n";
		$actionUrl = "courses.php?action=subscribe";
	}
// ?uidReset=true&uidReq=$_user['user_id']
	echo	"<form action=\"",$actionUrl,"\"  method=\"post\">\n",
			"<input type=\"submit\" name=\"next\" value=\"",get_lang('Next'),"\" validationmsg=\" ",get_lang('Next')," \">\n",
			"</form>\n";

}	// else Registration accepted

$already_second=1;

Display::display_footer();
?>
