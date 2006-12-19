<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2005 Dokeos S.A.
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

	Contact address: Dokeos, 44 rue des palais, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/
/**
==============================================================================
*	Install the Dokeos database
*	Notice : This script has to be included by index.php
*
*	@package dokeos.install
==============================================================================
*/

require_once("install_upgrade.lib.php");

/*
==============================================================================
		MAIN CODE
==============================================================================
*/ 

//this page can only be access through including from the install script.

if( ! defined('DOKEOS_INSTALL'))
{
	echo 'You are not allowed here!';
	exit;
}

set_file_folder_permissions();

@mysql_connect($dbHostForm,$dbUsernameForm,$dbPassForm);

if(mysql_errno() > 0)
{
	$no=mysql_errno();
	$msg=mysql_error();

	echo '<hr />['.$no.'] &ndash; '.$msg.'<hr>
	The MySQL server doesn\'t work or login / pass is bad.<br /><br />
	Please check these values:<br /><br />
	<b>host</b> : '.$dbHostForm.'<br />
	<b>user</b> : '.$dbUsernameForm.'<br />
	<b>password</b> : '.$dbPassForm.'<br /><br />
	Please go back to step 3.
	<p><input type="submit" name="step3" value="&lt; Back" /></p>
	</td></tr></table></form></body></html>';

	exit();
}

if($urlForm[strlen($urlForm)-1] != '/')
{
	$urlForm=$urlForm.'/';
}

if($encryptPassForm)
{
	$passToStore=md5($passForm);
}
else
{
	$passToStore=($passForm);
}

$dbPrefixForm=eregi_replace('[^a-z0-9_-]','',$dbPrefixForm);

$dbNameForm=eregi_replace('[^a-z0-9_-]','',$dbNameForm);
$dbStatsForm=eregi_replace('[^a-z0-9_-]','',$dbStatsForm);
$dbScormForm=eregi_replace('[^a-z0-9_-]','',$dbScormForm);
$dbUserForm=eregi_replace('[^a-z0-9_-]','',$dbUserForm);

if(!empty($dbPrefixForm) && !ereg('^'.$dbPrefixForm,$dbNameForm))
{
	$dbNameForm=$dbPrefixForm.$dbNameForm;
}

if(!empty($dbPrefixForm) && !ereg('^'.$dbPrefixForm,$dbStatsForm))
{
	$dbStatsForm=$dbPrefixForm.$dbStatsForm;
}

if(!empty($dbPrefixForm) && !ereg('^'.$dbPrefixForm,$dbScormForm))
{
	$dbScormForm=$dbPrefixForm.$dbScormForm;
}

if(!empty($dbPrefixForm) && !ereg('^'.$dbPrefixForm,$dbUserForm))
{
	$dbUserForm=$dbPrefixForm.$dbUserForm;
}

$mysqlMainDb=$dbNameForm;
$mysqlStatsDb=$dbStatsForm;
$mysqlScormDb=$dbScormForm;
$mysqlUserDb=$dbUserForm;

if(empty($mysqlMainDb) || $mysqlMainDb == 'mysql' || $mysqlMainDb == $dbPrefixForm)
{
	$mysqlMainDb=$dbPrefixForm.'main';
}

if(empty($mysqlStatsDb) || $mysqlStatsDb == 'mysql' || $mysqlStatsDb == $dbPrefixForm)
{
	$mysqlStatsDb=$dbPrefixForm.'stats';
}

if(empty($mysqlScormDb) || $mysqlScormDb == 'mysql' || $mysqlScormDb == $dbPrefixForm)
{
	$mysqlScormDb=$dbPrefixForm.'scorm';
}

if(empty($mysqlUserDb) || $mysqlUserDb == 'mysql' || $mysqlUserDb == $dbPrefixForm)
{
	$mysqlUserDb=$dbPrefixForm.'user';
}

$result=mysql_query("SHOW VARIABLES LIKE 'datadir'") or die(mysql_error());

$mysqlRepositorySys=mysql_fetch_array($result);
$mysqlRepositorySys=$mysqlRepositorySys['Value'];

if(!$singleDbForm)
{
	mysql_query("DROP DATABASE IF EXISTS `$mysqlMainDb`") or die(mysql_error());
}
mysql_query("CREATE DATABASE IF NOT EXISTS `$mysqlMainDb`") or die(mysql_error());

if($mysqlStatsDb == $mysqlMainDb && $mysqlScormDb == $mysqlMainDb && $mysqlUserDb == $mysqlMainDb)
{
	$singleDbForm=true;
}

/**
* CREATING THE STATISTICS DATABASE
*/
if($mysqlStatsDb != $mysqlMainDb)
{
	if(!$singleDbForm)
	{
		// multi DB mode AND tracking has its own DB so create it
		mysql_query("DROP DATABASE IF EXISTS `$mysqlStatsDb`") or die(mysql_error());
		mysql_query("CREATE DATABASE `$mysqlStatsDb`") or die(mysql_error());
	}
	else
	{
		// single DB mode so $mysqlStatsDb MUST BE the SAME than $mysqlMainDb
		$mysqlStatsDb=$mysqlMainDb;
	}
}

/**
* CREATING THE SCORM DATABASE
*/
if($mysqlScormDb != $mysqlMainDb)
{
	if(!$singleDbForm)
	{
		// multi DB mode AND scorm has its own DB so create it
		mysql_query("DROP DATABASE IF EXISTS `$mysqlScormDb`") or die(mysql_error());
		mysql_query("CREATE DATABASE `$mysqlScormDb`") or die(mysql_error());
	}
	else
	{
		// single DB mode so $mysqlScormDb MUST BE the SAME than $mysqlMainDb
		$mysqlScormDb=$mysqlMainDb;
	}
}

/**
* CREATING THE USER DATABASE
*/
if($mysqlUserDb != $mysqlMainDb)
{
	if(!$singleDbForm)
	{
		// multi DB mode AND user data has its own DB so create it
		mysql_query("DROP DATABASE IF EXISTS `$mysqlUserDb`") or die(mysql_error());
		mysql_query("CREATE DATABASE `$mysqlUserDb`") or die(mysql_error());
	}
	else
	{
		// single DB mode so $mysqlUserDb MUST BE the SAME than $mysqlMainDb
		$mysqlUserDb=$mysqlMainDb;
	}
}

include("../lang/english/create_course.inc.php");

if($languageForm != 'english')
{
	include("../lang/$languageForm/create_course.inc.php");
}

/**
* creating the tables of the main database
*/
mysql_select_db($mysqlMainDb) or die(mysql_error());

$installation_settings['{ORGANISATIONNAME}'] = $institutionForm;
$installation_settings['{ORGANISATIONURL}'] = $institutionUrlForm;
$installation_settings['{CAMPUSNAME}'] = $campusForm;
$installation_settings['{PLATFORMLANGUAGE}'] = $languageForm;
$installation_settings['{ALLOWSELFREGISTRATION}'] = trueFalse($allowSelfReg);
$installation_settings['{ALLOWTEACHERSELFREGISTRATION}'] = trueFalse($allowSelfRegProf);
$installation_settings['{ADMINLASTNAME}'] = $adminLastName;
$installation_settings['{ADMINFIRSTNAME}'] = $adminFirstName;
$installation_settings['{ADMINLOGIN}'] = $loginForm;
$installation_settings['{ADMINPASSWORD}'] = $passToStore;
$installation_settings['{ADMINEMAIL}'] = $emailForm;
$installation_settings['{ADMINPHONE}'] = $adminPhoneForm;
$installation_settings['{PLATFORM_AUTH_SOURCE}'] = PLATFORM_AUTH_SOURCE;

load_main_database($installation_settings);

/**
* creating the tables of the tracking database
*/

mysql_select_db($mysqlStatsDb) or die(mysql_error());

mysql_query("CREATE TABLE `track_c_browsers` (
			`id` int(11) NOT NULL auto_increment,
			`browser` varchar(255) NOT NULL default '',
			`counter` int(11) NOT NULL default '0',
			PRIMARY KEY  (`id`)
			) TYPE=MyISAM") or die(mysql_error());

mysql_query("CREATE TABLE `track_c_countries` (
			`id` int(11) NOT NULL auto_increment,
			`code` varchar(40) NOT NULL default '',
			`country` varchar(50) NOT NULL default '',
			`counter` int(11) NOT NULL default '0',
			PRIMARY KEY  (`id`)
			) TYPE=MyISAM") or die(mysql_error());

mysql_query("CREATE TABLE `track_c_os` (
			`id` int(11) NOT NULL auto_increment,
			`os` varchar(255) NOT NULL default '',
			`counter` int(11) NOT NULL default '0',
			PRIMARY KEY  (`id`)
			) TYPE=MyISAM") or die(mysql_error());

mysql_query("CREATE TABLE `track_c_providers` (
			`id` int(11) NOT NULL auto_increment,
			`provider` varchar(255) NOT NULL default '',
			`counter` int(11) NOT NULL default '0',
			PRIMARY KEY  (`id`)
			) TYPE=MyISAM") or die(mysql_error());

mysql_query("CREATE TABLE `track_c_referers` (
			`id` int(11) NOT NULL auto_increment,
			`referer` varchar(255) NOT NULL default '',
			`counter` int(11) NOT NULL default '0',
			PRIMARY KEY  (`id`)
			) TYPE=MyISAM") or die(mysql_error());

mysql_query("CREATE TABLE `track_e_access` (
			`access_id` int(11) NOT NULL auto_increment,
			`access_user_id` int unsigned default NULL,
			`access_date` datetime NOT NULL default '0000-00-00 00:00:00',
			`access_cours_code` varchar(40) NOT NULL default '0',
			`access_tool` varchar(30) default NULL,
			PRIMARY KEY  (`access_id`),
			KEY `access_user_id` (`access_user_id`),
			KEY `access_cours_code` (`access_cours_code`)
			) TYPE=MyISAM") or die(mysql_error());

mysql_query("CREATE TABLE `track_e_lastaccess` (
			`access_id` bigint(20) NOT NULL auto_increment,
			`access_user_id` int unsigned default NULL,
			`access_date` datetime NOT NULL default '0000-00-00 00:00:00',
			`access_cours_code` varchar(40) NOT NULL default '0',
			`access_tool` varchar(30) default NULL,
			`access_session_id` int unsigned default NULL,
			PRIMARY KEY  (`access_id`),
			KEY `access_user_id` (`access_user_id`),
			KEY `access_cours_code` (`access_cours_code`)
			) TYPE=MyISAM") or die(mysql_error());

mysql_query("CREATE TABLE `track_e_default` (
			`default_id` int(11) NOT NULL auto_increment,
			`default_user_id` int unsigned NOT NULL default '0',
			`default_cours_code` varchar(40) NOT NULL default '',
			`default_date` datetime NOT NULL default '0000-00-00 00:00:00',
			`default_event_type` varchar(20) NOT NULL default '',
			`default_value_type` varchar(20) NOT NULL default '',
			`default_value` tinytext NOT NULL,
			PRIMARY KEY  (`default_id`)
			) TYPE=MyISAM") or die(mysql_error());

mysql_query("CREATE TABLE `track_e_downloads` (
			`down_id` int(11) NOT NULL auto_increment,
			`down_user_id` int unsigned default NULL,
			`down_date` datetime NOT NULL default '0000-00-00 00:00:00',
			`down_cours_id` varchar(20) NOT NULL default '0',
			`down_doc_path` varchar(255) NOT NULL default '0',
			PRIMARY KEY  (`down_id`)
			) TYPE=MyISAM") or die(mysql_error());

mysql_query("CREATE TABLE `track_e_exercices` (
			`exe_id` int(11) NOT NULL auto_increment,
			`exe_user_id` int unsigned default NULL,
			`exe_date` datetime NOT NULL default '0000-00-00 00:00:00',
			`exe_cours_id` varchar(40) NOT NULL default '',
			`exe_exo_id` mediumint unsigned NOT NULL default '0',
			`exe_result` smallint NOT NULL default '0',
			`exe_weighting` smallint NOT NULL default '0',
			PRIMARY KEY  (`exe_id`)
			) TYPE=MyISAM") or die(mysql_error());
			
mysql_query("CREATE TABLE `track_e_attempt` (
		    `exe_id` int default NULL,
		    `user_id` int NOT NULL default '0',
		    `question_id` int NOT NULL default '0',
		    `answer` text NOT NULL,
		    `teacher_comment` text NOT NULL,
		    `marks` int NOT NULL default '0',
		    `course_code` varchar(40) NOT NULL default '',
		    `position` int default '0'
			) TYPE=MyISAM") or die(mysql_error());

mysql_query("CREATE TABLE `track_e_hotpotatoes` (
			`exe_name` VARCHAR( 255 ) NOT NULL ,
			`exe_user_id` int unsigned DEFAULT NULL ,
			`exe_date` DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL ,
			`exe_cours_id` varchar(40) NOT NULL ,
			`exe_result` smallint DEFAULT '0' NOT NULL ,
			`exe_weighting` smallint DEFAULT '0' NOT NULL
			) TYPE=MyISAM") or die(mysql_error());

mysql_query("CREATE TABLE `track_e_links` (
			`links_id` int NOT NULL auto_increment,
			`links_user_id` int unsigned default NULL,
			`links_date` datetime NOT NULL default '0000-00-00 00:00:00',
			`links_cours_id` varchar(20) NOT NULL default '0',
			`links_link_id` int(11) NOT NULL default '0',
			PRIMARY KEY  (`links_id`)
			) TYPE=MyISAM") or die(mysql_error());

mysql_query("CREATE TABLE `track_e_login` (
			`login_id` int NOT NULL auto_increment,
			`login_user_id` int unsigned NOT NULL default '0',
			`login_date` datetime NOT NULL default '0000-00-00 00:00:00',
			`login_ip` varchar(39) NOT NULL default '',
			`logout_date` datetime NULL default NULL,
			PRIMARY KEY  (`login_id`),
			KEY `login_user_id` (`login_user_id`)
			) TYPE=MyISAM") or die(mysql_error());

mysql_query("CREATE TABLE `track_e_online` (
			`login_id` int NOT NULL auto_increment,
			`login_user_id` int unsigned NOT NULL default '0',
			`login_date` datetime NOT NULL default '0000-00-00 00:00:00',
			`login_ip` varchar(39) NOT NULL default '',
			`course` varchar(40) default NULL, 
			PRIMARY KEY  (`login_id`),
			KEY `login_user_id` (`login_user_id`)
			) TYPE=MyISAM") or die(mysql_error());

mysql_query("CREATE TABLE `track_e_open` (
			`open_id` int(11) NOT NULL auto_increment,
			`open_remote_host` tinytext NOT NULL,
			`open_agent` tinytext NOT NULL,
			`open_referer` tinytext NOT NULL,
			`open_date` datetime NOT NULL default '0000-00-00 00:00:00',
			PRIMARY KEY  (`open_id`)
			) TYPE=MyISAM") or die(mysql_error());

mysql_query("CREATE TABLE `track_e_uploads` (
			`upload_id` int NOT NULL auto_increment,
			`upload_user_id` int unsigned default NULL,
			`upload_date` datetime NOT NULL default '0000-00-00 00:00:00',
			`upload_cours_id` varchar(20) NOT NULL default '0',
			`upload_work_id` int(11) NOT NULL default '0',
			PRIMARY KEY  (`upload_id`)
			) TYPE=MyISAM") or die(mysql_error());

mysql_query("CREATE TABLE `track_e_course_access` (
			  `course_access_id` int(11) NOT NULL auto_increment,
			  `course_code` varchar(40) NOT NULL,
			  `user_id` int(11) NOT NULL,
			  `login_course_date` datetime NOT NULL default '0000-00-00 00:00:00',
			  `logout_course_date` datetime default NULL,
			  `counter` int(11) NOT NULL,
			  PRIMARY KEY  (`course_access_id`)
			)"
) or die(mysql_error());


$track_countries_table = "track_c_countries";
fill_track_countries_table($track_countries_table);

/**
* creating the tables of the SCORM database
*/

mysql_select_db($mysqlScormDb) or die(mysql_error());

mysql_query("CREATE TABLE `scorm_main` (
			`contentId` int(5) unsigned NOT NULL auto_increment,
			`contentTitle` varchar(100) default NULL,
			`dokeosCourse` varchar(100) default NULL,
			PRIMARY KEY  (`contentId`)
			) TYPE=MyISAM") or die(mysql_error());

mysql_query("CREATE TABLE `scorm_sco_data` (
			`contentId` int(5) default NULL,
			`scoId` int(5) NOT NULL auto_increment,
			`scoIdentifier` varchar(100) default NULL,
			`scoTitle` varchar(100) default NULL,
			`status` varchar(100) default NULL,
			`studentId` int(10) default NULL,
			`score` int(10) default NULL,
			`time` varchar(20) default NULL,
			KEY `scoId` (`scoId`)
			) TYPE=MyISAM") or die(mysql_error());

/**
* creating the tables of the USER database
* this is where the personal agenda items are storen, the user defined course categories (sorting of my courses)
*/

mysql_select_db($mysqlUserDb) or die(mysql_error());

// creating the table where the personal agenda items are stored
mysql_query("CREATE TABLE `personal_agenda` (
			`id` int NOT NULL auto_increment,
			`user` int unsigned,
			`title` text,
			`text` text,
			`date` datetime default NULL,
			`enddate` datetime default NULL,
			`course` varchar(255),
			UNIQUE KEY `id` (`id`))
			TYPE=MyISAM") or die(mysql_error());

// creating the table that is used for the user defined course categories
mysql_query("CREATE TABLE `user_course_category` (
			`id` int unsigned NOT NULL auto_increment,
			`user_id` int unsigned NOT NULL default '0',
			`title` text NOT NULL,
			`sort` INT(11), 
			PRIMARY KEY  (`id`)
			) TYPE=MyISAM") or die(mysql_error());
?>