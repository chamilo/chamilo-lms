<?php
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

	Contact address: Dokeos, 44 rue des palais, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/
/**
==============================================================================
* Update the Dokeos database from an older version
* Notice : This script has to be included by index.php and update_courses.php
*
* @package dokeos.install
* @todo
* - conditional changing of tables. Currently we execute for example
* ALTER TABLE `$dbNameForm`.`cours` instructions without checking wether this is necessary.
* - reorganise code into functions
==============================================================================
*/

require_once("install_upgrade.lib.php");

/*
==============================================================================
		MAIN CODE
==============================================================================
*/

if (defined('DOKEOS_INSTALL') || defined('DOKEOS_COURSE_UPDATE'))
{
	if (empty ($updateFromConfigFile) || !file_exists($_POST['updatePath'].$updateFromConfigFile) || !in_array(get_config_param('clarolineVersion'), $updateFromVersion))
	{
		echo '<b>Error !</b> Dokeos '.implode('|', $updateFromVersion).' has not been found.<br><br>
								Please go back to step 1.
							    <p><input type="submit" name="step1" value="&lt; Back"></p>
							    </td></tr></table></form></body></html>';

		exit ();
	}

	$dbGlu = get_config_param('dbGlu');

	if ($singleDbForm)
	{
		$courseTablePrefix = get_config_param('courseTablePrefix');
	}

	$dbScormForm = eregi_replace('[^a-z0-9_-]', '', $dbScormForm);

	if (!empty ($dbPrefixForm) && !ereg('^'.$dbPrefixForm, $dbScormForm))
	{
		$dbScormForm = $dbPrefixForm.$dbScormForm;
	}

	if (empty ($dbScormForm) || $dbScormForm == 'mysql' || $dbScormForm == $dbPrefixForm)
	{
		$dbScormForm = $dbPrefixForm.'scorm';
	}
	@ mysql_connect($dbHostForm, $dbUsernameForm, $dbPassForm);

	if (mysql_errno() > 0)
	{
		$no = mysql_errno();
		$msg = mysql_error();

		echo '<hr>['.$no.'] - '.$msg.'<hr>
								The mySQL server doesn\'t work or login / pass is bad.<br><br>
								Please check these values :<br><br>
							    <b>host</b> : '.$dbHostForm.'<br>
								<b>user</b> : '.$dbUsernameForm.'<br>
								<b>password</b> : '.$dbPassForm.'<br><br>
								Please go back to step '. (defined('DOKEOS_INSTALL') ? '3' : '1').'.
							    <p><input type="submit" name="step'. (defined('DOKEOS_INSTALL') ? '3' : '1').'" value="&lt; Back"></p>
							    </td></tr></table></form></body></html>';

		exit ();
	}

	/*
	-----------------------------------------------------------
		Normal upgrade procedure:
		start by updating main, statistic, scorm, user databases
	-----------------------------------------------------------
	*/
	if (defined('DOKEOS_INSTALL'))
	{
		/*
		-----------------------------------------------------------
			Update the main Dokeos database
		-----------------------------------------------------------
		*/
		include ("../lang/english/create_course.inc.php");

		if ($languageForm != 'english')
		{
			include ("../lang/$languageForm/create_course.inc.php");
		}

		mysql_query("CREATE TABLE `$dbNameForm`.`language` (
								 `id` tinyint(3) unsigned NOT NULL auto_increment,
								 `original_name` varchar(255) default NULL,
								 `english_name` varchar(255) default NULL,
								 `isocode` varchar(10) default NULL,
								 `dokeos_folder` varchar(250) default NULL,
								 `available` tinyint(4) NOT NULL default '1',
								 PRIMARY KEY (`id`)
								) TYPE=MyISAM");

		mysql_query("CREATE TABLE `$dbNameForm`.`session` (
								 `sess_id` varchar(32) NOT NULL default '',
								 `sess_name` varchar(10) NOT NULL default '',
								 `sess_time` int(11) NOT NULL default '0',
								 `sess_start` int(11) NOT NULL default '0',
								 `sess_value` text NOT NULL,
								 PRIMARY KEY (`sess_id`)
								) TYPE=MyISAM");

		mysql_query("CREATE TABLE `$dbNameForm`.`settings_current` (
								 `id` int(10) unsigned NOT NULL auto_increment,
								 `variable` varchar(255) default NULL,
								 `subkey` varchar(255) default NULL,
								 `type` varchar(255) default NULL,
								 `category` varchar(255) default NULL,
								 `selected_value` varchar(255) default NULL,
								 `title` varchar(255) NOT NULL default '',
								 `comment` varchar(255) default NULL,
								 `scope` varchar(50) default NULL,
								 `subkeytext` varchar(255) default NULL,
								 UNIQUE KEY `id` (`id`)
								) TYPE=MyISAM");

		mysql_query("CREATE TABLE `$dbNameForm`.`settings_options` (
								 `id` int(10) unsigned NOT NULL auto_increment,
								 `variable` varchar(255) default NULL,
								 `value` varchar(255) default NULL,
								 `display_text` varchar(255) NOT NULL default '',
								 PRIMARY KEY (`id`),
								 UNIQUE KEY `id` (`id`)
								) TYPE=MyISAM");

		mysql_query("CREATE TABLE `$dbNameForm`.`sys_announcement` (
								 `id` int(10) unsigned NOT NULL auto_increment,
								 `date_start` datetime NOT NULL default '0000-00-00 00:00:00',
								 `date_end` datetime NOT NULL default '0000-00-00 00:00:00',
								 `visible_teacher` enum('true','false') NOT NULL default 'false',
								 `visible_student` enum('true','false') NOT NULL default 'false',
								 `visible_guest` enum('true','false') NOT NULL default 'false',
								 `title` varchar(250) NOT NULL default '',
								 `content` text NOT NULL,
								 PRIMARY KEY (`id`)
								) TYPE=MyISAM");

		mysql_query("DROP TABLE `$dbNameForm`.`todo`");

		mysql_query("DROP TABLE `$dbNameForm`.`pma_bookmark`");

		mysql_query("DROP TABLE `$dbNameForm`.`pma_column_comments`");

		mysql_query("DROP TABLE `$dbNameForm`.`pma_pdf_pages`");

		mysql_query("DROP TABLE `$dbNameForm`.`pma_relation`");

		mysql_query("DROP TABLE `$dbNameForm`.`pma_table_coords`");

		mysql_query("DROP TABLE `$dbNameForm`.`pma_table_info`");

		mysql_query("ALTER TABLE `$dbNameForm`.`admin` CHANGE `idUser` `user_id` INT UNSIGNED DEFAULT '0' NOT NULL");
		mysql_query("ALTER TABLE `$dbNameForm`.`admin` DROP INDEX `idUser`");
		mysql_query("ALTER TABLE `$dbNameForm`.`admin` ADD UNIQUE (`user_id`)");

		mysql_query("ALTER TABLE `$dbNameForm`.`class` ADD `code` VARCHAR(40) DEFAULT '' AFTER `id`");
		mysql_query("ALTER TABLE `$dbNameForm`.`class` CHANGE `name` `name` TEXT NOT NULL");

		mysql_query("ALTER TABLE `$dbNameForm`.`class_user` CHANGE `id_class` `class_id` MEDIUMINT(8) UNSIGNED DEFAULT '0' NOT NULL");
		mysql_query("ALTER TABLE `$dbNameForm`.`class_user` CHANGE `id_user` `user_id` INT UNSIGNED DEFAULT '0' NOT NULL");

		mysql_query("ALTER TABLE `$dbNameForm`.`cours` RENAME `$dbNameForm`.`course`");
		mysql_query("ALTER TABLE `$dbNameForm`.`course` DROP `cours_id`");
		mysql_query("ALTER TABLE `$dbNameForm`.`course` CHANGE `code` `code` VARCHAR(40) NOT NULL");
		mysql_query("ALTER TABLE `$dbNameForm`.`course` CHANGE `directory` `directory` VARCHAR(40) DEFAULT NULL");
		mysql_query("ALTER TABLE `$dbNameForm`.`course` CHANGE `dbName` `db_name` VARCHAR(40) DEFAULT NULL");
		mysql_query("ALTER TABLE `$dbNameForm`.`course` CHANGE `languageCourse` `course_language` VARCHAR(20) DEFAULT NULL");
		mysql_query("ALTER TABLE `$dbNameForm`.`course` CHANGE `intitule` `title` VARCHAR(250) DEFAULT NULL");
		mysql_query("ALTER TABLE `$dbNameForm`.`course` CHANGE `faculte` `category_code` VARCHAR(40) DEFAULT NULL");
		mysql_query("ALTER TABLE `$dbNameForm`.`course` CHANGE `visible` `visibility` TINYINT(4) DEFAULT '0'");
		mysql_query("ALTER TABLE `$dbNameForm`.`course` DROP `cahier_charges`");
		mysql_query("ALTER TABLE `$dbNameForm`.`course` CHANGE `scoreShow` `show_score` INT(11) DEFAULT '1' NOT NULL");
		mysql_query("ALTER TABLE `$dbNameForm`.`course` CHANGE `titulaires` `tutor_name` VARCHAR(200) DEFAULT NULL");
		mysql_query("ALTER TABLE `$dbNameForm`.`course` CHANGE `fake_code` `visual_code` VARCHAR(40) DEFAULT NULL");
		mysql_query("ALTER TABLE `$dbNameForm`.`course` CHANGE `departmentUrlName` `department_name` VARCHAR(30) DEFAULT NULL");
		mysql_query("ALTER TABLE `$dbNameForm`.`course` CHANGE `departmentUrl` `department_url` VARCHAR(180) DEFAULT NULL");
		mysql_query("ALTER TABLE `$dbNameForm`.`course` CHANGE `diskQuota` `disk_quota` INT(10) UNSIGNED DEFAULT NULL");
		mysql_query("ALTER TABLE `$dbNameForm`.`course` DROP `versionDb`");
		mysql_query("ALTER TABLE `$dbNameForm`.`course` DROP `versionClaro`");
		mysql_query("ALTER TABLE `$dbNameForm`.`course` CHANGE `lastVisit` `last_visit` DATETIME DEFAULT NULL");
		mysql_query("ALTER TABLE `$dbNameForm`.`course` CHANGE `lastEdit` `last_edit` DATETIME DEFAULT NULL");
		mysql_query("ALTER TABLE `$dbNameForm`.`course` CHANGE `creationDate` `creation_date` DATETIME DEFAULT NULL");
		mysql_query("ALTER TABLE `$dbNameForm`.`course` CHANGE `expirationDate` `expiration_date` DATETIME DEFAULT NULL");
		mysql_query("ALTER TABLE `$dbNameForm`.`course` ADD `target_course_code` VARCHAR(40)");
		mysql_query("ALTER TABLE `$dbNameForm`.`course` ADD `subscribe` TINYINT(4) DEFAULT '1' NOT NULL");
		mysql_query("ALTER TABLE `$dbNameForm`.`course` ADD `unsubscribe` TINYINT(4) DEFAULT '1' NOT NULL");
		mysql_query("ALTER TABLE `$dbNameForm`.`course` DROP PRIMARY KEY");
		mysql_query("ALTER TABLE `$dbNameForm`.`course` ADD PRIMARY KEY (`code`)");

		mysql_query("UPDATE `$dbNameForm`.`course` SET visibility='1' WHERE visibility='0'");
		mysql_query("UPDATE `$dbNameForm`.`course` SET visibility='3' WHERE visibility='2'");

		mysql_query("ALTER TABLE `$dbNameForm`.`faculte` RENAME `$dbNameForm`.`course_category`");
		mysql_query("ALTER TABLE `$dbNameForm`.`course_category` CHANGE `id` `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT");
		mysql_query("ALTER TABLE `$dbNameForm`.`course_category` CHANGE `code_P` `parent_id` VARCHAR(40) DEFAULT NULL");
		mysql_query("ALTER TABLE `$dbNameForm`.`course_category` DROP `bc`");
		mysql_query("ALTER TABLE `$dbNameForm`.`course_category` CHANGE `treePos` `tree_pos` INT(10) UNSIGNED DEFAULT NULL");
		mysql_query("ALTER TABLE `$dbNameForm`.`course_category` CHANGE `nb_childs` `children_count` SMALLINT(6) DEFAULT NULL");
		mysql_query("ALTER TABLE `$dbNameForm`.`course_category` CHANGE `canHaveCoursesChild` `auth_course_child` ENUM('TRUE', 'FALSE') DEFAULT 'TRUE'");
		mysql_query("ALTER TABLE `$dbNameForm`.`course_category` CHANGE `canHaveCatChild` `auth_cat_child` ENUM('TRUE', 'FALSE') DEFAULT 'TRUE'");
		mysql_query("ALTER TABLE `$dbNameForm`.`course_category` DROP INDEX `code_P`");
		mysql_query("ALTER TABLE `$dbNameForm`.`course_category` DROP INDEX `treePos`");
		mysql_query("ALTER TABLE `$dbNameForm`.`course_category` ADD UNIQUE (`code`)");
		mysql_query("ALTER TABLE `$dbNameForm`.`course_category` ADD INDEX (`parent_id`)");
		mysql_query("ALTER TABLE `$dbNameForm`.`course_category` ADD INDEX (`tree_pos`)");

		mysql_query("ALTER TABLE `$dbNameForm`.`tools_basic` RENAME `$dbNameForm`.`course_module`");
		mysql_query("ALTER TABLE `$dbNameForm`.`course_module` CHANGE `id` `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT");
		mysql_query("ALTER TABLE `$dbNameForm`.`course_module` CHANGE `rubrique` `name` VARCHAR(100) NOT NULL");
		mysql_query("ALTER TABLE `$dbNameForm`.`course_module` CHANGE `lien` `link` VARCHAR(255) NOT NULL");
		mysql_query("ALTER TABLE `$dbNameForm`.`course_module` CHANGE `row` `row` INT(10) UNSIGNED DEFAULT '0' NOT NULL");
		mysql_query("ALTER TABLE `$dbNameForm`.`course_module` CHANGE `column` `column` INT(10) UNSIGNED DEFAULT '0' NOT NULL");

		mysql_query("ALTER TABLE `$dbNameForm`.`cours_class` RENAME `$dbNameForm`.`course_rel_class`");
		mysql_query("ALTER TABLE `$dbNameForm`.`course_rel_class` CHANGE `code_cours` `course_code` CHAR(40) NOT NULL");
		mysql_query("ALTER TABLE `$dbNameForm`.`course_rel_class` CHANGE `id_class` `class_id` MEDIUMINT(8) UNSIGNED DEFAULT '0' NOT NULL");

		mysql_query("ALTER TABLE `$dbNameForm`.`cours_user` RENAME `$dbNameForm`.`course_rel_user`");
		mysql_query("ALTER TABLE `$dbNameForm`.`course_rel_user` CHANGE `code_cours` `course_code` VARCHAR(40) NOT NULL");
		mysql_query("ALTER TABLE `$dbNameForm`.`course_rel_user` CHANGE `statut` `status` TINYINT(4) DEFAULT '5' NOT NULL");
		mysql_query("ALTER TABLE `$dbNameForm`.`course_rel_user` CHANGE `user_id` `user_id` INT UNSIGNED DEFAULT '0' NOT NULL");
		mysql_query("ALTER TABLE `$dbNameForm`.`course_rel_user` CHANGE `team` `group_id` INT(11) DEFAULT '0' NOT NULL");
		mysql_query("ALTER TABLE `$dbNameForm`.`course_rel_user` CHANGE `tutor` `tutor_id` INT UNSIGNED DEFAULT '0' NOT NULL");
		mysql_query("ALTER TABLE `$dbNameForm`.`course_rel_user` ADD `sort` INT");
		mysql_query("ALTER TABLE `$dbNameForm`.`course_rel_user` ADD `user_course_cat` INT DEFAULT '0'");

		mysql_query("ALTER TABLE `$dbNameForm`.`user` CHANGE `user_id` `user_id` INT UNSIGNED NOT NULL AUTO_INCREMENT");
		mysql_query("ALTER TABLE `$dbNameForm`.`user` CHANGE `nom` `lastname` VARCHAR(60) DEFAULT NULL");
		mysql_query("ALTER TABLE `$dbNameForm`.`user` CHANGE `prenom` `firstname` VARCHAR(60) DEFAULT NULL");
		mysql_query("ALTER TABLE `$dbNameForm`.`user` CHANGE `username` `username` VARCHAR(20) NOT NULL");
		mysql_query("ALTER TABLE `$dbNameForm`.`user` CHANGE `password` `password` VARCHAR(50) NOT NULL");
		mysql_query("ALTER TABLE `$dbNameForm`.`user` CHANGE `authSource` `auth_source` VARCHAR(50) DEFAULT '".LOCAL_AUTH_SOURCE."'");
		mysql_query("ALTER TABLE `$dbNameForm`.`user` CHANGE `statut` `status` TINYINT(4) DEFAULT '5' NOT NULL");
		mysql_query("ALTER TABLE `$dbNameForm`.`user` CHANGE `officialCode` `official_code` VARCHAR(40) DEFAULT NULL");
		mysql_query("ALTER TABLE `$dbNameForm`.`user` CHANGE `phoneNumber` `phone` VARCHAR(30) DEFAULT NULL");
		mysql_query("ALTER TABLE `$dbNameForm`.`user` CHANGE `pictureUri` `picture_uri` VARCHAR(250) DEFAULT NULL");
		mysql_query("ALTER TABLE `$dbNameForm`.`user` CHANGE `creatorId` `creator_id` INT UNSIGNED DEFAULT NULL");
		mysql_query("ALTER TABLE `$dbNameForm`.`user` ADD `competences` TEXT AFTER `creator_id`");
		mysql_query("ALTER TABLE `$dbNameForm`.`user` ADD `diplomas` TEXT AFTER `competences`");
		mysql_query("ALTER TABLE `$dbNameForm`.`user` ADD `openarea` TEXT AFTER `diplomas`");
		mysql_query("ALTER TABLE `$dbNameForm`.`user` ADD `teach` TEXT AFTER `openarea`");
		mysql_query("ALTER TABLE `$dbNameForm`.`user` ADD `productions` VARCHAR(250) AFTER `teach`");
		mysql_query("ALTER TABLE `$dbNameForm`.`user` ADD `chatcall_user_id` INT UNSIGNED NOT NULL AFTER `productions`");
		mysql_query("ALTER TABLE `$dbNameForm`.`user` ADD `chatcall_date` DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL AFTER `chatcall_user_id`");
		mysql_query("ALTER TABLE `$dbNameForm`.`user` ADD `chatcall_text` VARCHAR(50) NOT NULL AFTER `chatcall_date`");
		
		$language_table = "`$dbNameForm`.`language`";
		fill_language_table($language_table);
		
		$installation_settings['institution_form'] = $institutionForm;
		$installation_settings['institution_url_form'] = $institutionUrlForm;
		$installation_settings['campus_form'] = $campusForm;
		$installation_settings['email_form'] = $emailForm;
		$installation_settings['admin_last_name'] = $adminLastName;
		$installation_settings['admin_first_name'] = $adminFirstName;
		$installation_settings['language_form'] = $languageForm;
		$installation_settings['allow_self_registration'] = $allowSelfReg;
		$installation_settings['allow_teacher_self_registration'] = $allowSelfRegProf;
		$installation_settings['admin_phone_form'] = $adminPhoneForm;
		
		$current_settings_table = "`$dbNameForm`.`settings_current`";
		fill_current_settings_table($current_settings_table, $installation_settings);
		
		$settings_options_table = "`$dbNameForm`.`settings_options`";
		fill_settings_options_table($settings_options_table);

		mysql_query("INSERT INTO `$dbNameForm`.`course_module` (`name`,`link`,`image`,`row`,`column`,`position`) VALUES
										('AddedLearnpath', NULL, 'scormbuilder.gif', 0, 0, 'external'),
										('".TOOL_BACKUP."', 'coursecopy/backup.php' , 'backup.gif', 2, 1, 'courseadmin'),
										('".TOOL_COPY_COURSE_CONTENT."', 'coursecopy/copy_course.php' , 'copy.gif', 2, 2, 'courseadmin'),
										('".TOOL_RECYCLE_COURSE."', 'coursecopy/recycle_course.php' , 'recycle.gif', 2, 3, 'courseadmin')");
		mysql_query("UPDATE `$dbNameForm`.`course_module` SET name='".TOOL_COURSE_DESCRIPTION."' WHERE link LIKE 'course_description/%'");
		mysql_query("UPDATE `$dbNameForm`.`course_module` SET name='".TOOL_CALENDAR_EVENT."' WHERE link LIKE 'calendar/%'");
		mysql_query("UPDATE `$dbNameForm`.`course_module` SET name='".TOOL_DOCUMENT."' WHERE link LIKE 'document/%'");
		mysql_query("UPDATE `$dbNameForm`.`course_module` SET name='".TOOL_ANNOUNCEMENT."' WHERE link LIKE 'announcements/%'");
		mysql_query("UPDATE `$dbNameForm`.`course_module` SET name='".TOOL_BB_FORUM."' WHERE link LIKE 'phpbb/%'");
		mysql_query("UPDATE `$dbNameForm`.`course_module` SET name='".TOOL_LINK."' WHERE link = 'link/link.php'");
		mysql_query("UPDATE `$dbNameForm`.`course_module` SET name='".TOOL_DROPBOX."' WHERE link LIKE 'dropbox/%'");
		mysql_query("UPDATE `$dbNameForm`.`course_module` SET name='".TOOL_QUIZ."' WHERE link LIKE 'exercice/%'");
		mysql_query("UPDATE `$dbNameForm`.`course_module` SET name='".TOOL_USER."' WHERE link LIKE 'user/%'");
		mysql_query("UPDATE `$dbNameForm`.`course_module` SET name='".TOOL_GROUP."' WHERE link LIKE 'group/%'");
		mysql_query("UPDATE `$dbNameForm`.`course_module` SET name='".TOOL_CHAT."' WHERE link LIKE 'chat/%'");
		mysql_query("UPDATE `$dbNameForm`.`course_module` SET name='".TOOL_CONFERENCE."' WHERE link LIKE 'online/%'");
		mysql_query("UPDATE `$dbNameForm`.`course_module` SET name='".TOOL_STUDENTPUBLICATION."' WHERE link LIKE 'work/%'");
		mysql_query("UPDATE `$dbNameForm`.`course_module` SET name='".TOOL_TRACKING."' WHERE link LIKE 'tracking/%'");
		mysql_query("UPDATE `$dbNameForm`.`course_module` SET name='".TOOL_HOMEPAGE_LINK."' WHERE link = 'link/link.php?action=addlink'");
		mysql_query("UPDATE `$dbNameForm`.`course_module` SET name='".TOOL_COURSE_SETTING."' WHERE link LIKE 'course_info/%'");
		mysql_query("UPDATE `$dbNameForm`.`course_module` SET name='".TOOL_LEARNPATH."' WHERE link LIKE 'scorm/%'");
		
		// existing courses should have a value entered for sort into the course_rel_user table
		$tbl_user=`$dbNameForm`.`user`;
		$tbl_course_user=`$dbNameForm`.`course_rel_user`;
		
		$sqlusers="SELECT * FROM $tbl_user";
		$resultusers=api_sql_query($sqlusers);
		while ($row=mysql_fetch_array($resultusers))
		{
			$counter=1;
			$sql_course_user="SELECT * FROM $tbl_course_user WHERE user_id='".$row['user_id']."'";
			$result_course_user=api_sql_query($sql_course_user);
			while ($rowcu=mysql_fetch_array($result_course_user))
			{
				$update="UPDATE $tbl_course_user SET sort='$counter' WHERE user_id='".$row['user_id']."' AND course_code='".$rowcu['course_code']."'";	
				$resultupdate=api_sql_query($update);
				$counter++;
			}
		}
		

		/*
		-----------------------------------------------------------
			Update the tracking Dokeos database
		-----------------------------------------------------------
		*/
		mysql_query("CREATE TABLE `$dbStatsForm`.`track_e_hotpotatoes` (
							 `exe_name` varchar(255) NOT NULL default '',
							 `exe_user_id` int unsigned default NULL,
							 `exe_date` datetime NOT NULL default '0000-00-00 00:00:00',
							 `exe_cours_id` varchar(20) NOT NULL default '',
							 `exe_result` tinyint(4) NOT NULL default '0',
							 `exe_weighting` tinyint(4) NOT NULL default '0'
							) TYPE=MyISAM");

		mysql_query("CREATE TABLE `$dbStatsForm`.`track_e_online` (
							 `login_id` int(11) NOT NULL auto_increment,
							 `login_user_id` int unsigned NOT NULL default '0',
							 `login_date` datetime NOT NULL default '0000-00-00 00:00:00',
							 `login_ip` varchar(39) NOT NULL default '',
							 PRIMARY KEY (`login_id`),
							 KEY `login_user_id` (`login_user_id`)
							) TYPE=MyISAM");

		mysql_query("ALTER TABLE `$dbStatsForm`.`track_e_access` CHANGE `access_user_id` `access_user_id` INT UNSIGNED DEFAULT NULL");

		mysql_query("ALTER TABLE `$dbStatsForm`.`track_e_default` CHANGE `default_user_id` `default_user_id` INT UNSIGNED DEFAULT '0' NOT NULL");

		mysql_query("ALTER TABLE `$dbStatsForm`.`track_e_downloads` CHANGE `down_user_id` `down_user_id` INT UNSIGNED DEFAULT NULL");

		mysql_query("ALTER TABLE `$dbStatsForm`.`track_e_exercices` CHANGE `exe_user_id` `exe_user_id` INT UNSIGNED DEFAULT NULL");
		
		mysql_query("ALTER TABLE `$dbStatsForm`.`track_e_exercices` CHANGE `exe_cours_id` `exe_cours_id` VARCHAR(40) NOT NULL DEFAULT ''");

		mysql_query("ALTER TABLE `$dbStatsForm`.`track_e_exercices` CHANGE `exe_exo_id` `exe_exo_id` MEDIUMINT UNSIGNED NOT NULL DEFAULT '0'");

		mysql_query("ALTER TABLE `$dbStatsForm`.`track_e_exercices` CHANGE `exe_result` `exe_result` SMALLINT NOT NULL DEFAULT '0'");

		mysql_query("ALTER TABLE `$dbStatsForm`.`track_e_exercices` CHANGE `exe_weighting` `exe_weighting` SMALLINT NOT NULL DEFAULT '0'");

		mysql_query("ALTER TABLE `$dbStatsForm`.`track_e_hotpotatoes` CHANGE `exe_cours_id` `exe_cours_id` VARCHAR(40) NOT NULL DEFAULT ''");

		mysql_query("ALTER TABLE `$dbStatsForm`.`track_e_hotpotatoes` CHANGE `exe_result` `exe_result` SMALLINT NOT NULL DEFAULT '0'");

		mysql_query("ALTER TABLE `$dbStatsForm`.`track_e_hotpotatoes` CHANGE `exe_weighting` `exe_weighting` SMALLINT NOT NULL DEFAULT '0'");

		mysql_query("ALTER TABLE `$dbStatsForm`.`track_e_lastaccess` CHANGE `access_user_id` `access_user_id` INT UNSIGNED DEFAULT NULL");

		mysql_query("ALTER TABLE `$dbStatsForm`.`track_e_links` CHANGE `links_user_id` `links_user_id` INT UNSIGNED DEFAULT NULL");

		mysql_query("ALTER TABLE `$dbStatsForm`.`track_e_login` CHANGE `login_user_id` `login_user_id` INT UNSIGNED DEFAULT '0' NOT NULL");

		mysql_query("ALTER TABLE `$dbStatsForm`.`track_e_uploads` CHANGE `upload_user_id` `upload_user_id` INT UNSIGNED DEFAULT NULL");

		/*
		-----------------------------------------------------------
			Create the User database
		-----------------------------------------------------------
		*/
		$sql = "CREATE DATABASE IF NOT EXISTS `$dbUserForm`";
		mysql_query($sql);

		mysql_query("CREATE TABLE `$dbUserForm`.`personal_agenda` (
							`id` int NOT NULL auto_increment,
							`user` int unsigned,
							`title` text,
							`text` text,
							`date` datetime default NULL,
							`enddate` datetime default NULL,
							`course` varchar(255),
							UNIQUE KEY `id` (`id`))
							TYPE=MyISAM");

		mysql_query("CREATE TABLE `$dbUserForm`.`user_course_category` (
							`id` int unsigned NOT NULL auto_increment,
							`user_id` int unsigned NOT NULL default '0',
							`title` text NOT NULL,
							PRIMARY KEY  (`id`)
							) TYPE=MyISAM");
	}

	/*
	-----------------------------------------------------------
		Update the Dokeos course databases
		this part can be accessed in two ways:
		- from the normal upgrade process
		- from the script update_courses.php,
		which is used to upgrade more than MAX_COURSE_TRANSFER courses

		Every time this script is accessed, only
		MAX_COURSE_TRANSFER courses are upgraded.
	-----------------------------------------------------------
	*/
	$newPath = str_replace('\\', '/', realpath('../..')).'/';

	$coursePath = array ();
	$courseDB = array ();
	$nbr_courses = 0;

	if ($result = mysql_query("SELECT code,db_name,directory,course_language FROM `$dbNameForm`.`course` WHERE target_course_code IS NULL"))
	{
		$i = 0;

		$nbr_courses = mysql_num_rows($result);

		while ($i < MAX_COURSE_TRANSFER && (list ($course_code, $mysql_base_course, $directory, $languageCourse) = mysql_fetch_row($result)))
		{
			if (!file_exists($newPath.'courses/'.$directory))
			{
				if ($singleDbForm)
				{
					$prefix = $courseTablePrefix.$mysql_base_course.$dbGlu;

					$mysql_base_course = $dbNameForm.'`.`'.$courseTablePrefix.$mysql_base_course;
				}
				else
				{
					$prefix = '';
				}

				$coursePath[$course_code] = $directory;
				$courseDB[$course_code] = $mysql_base_course;

				include ("../lang/english/create_course.inc.php");

				if ($languageCourse != 'english')
				{
					include ("../lang/$languageCourse/create_course.inc.php");
				}

				mysql_query("CREATE TABLE `$mysql_base_course".$dbGlu."chat_connected` (
																 `user_id` int unsigned NOT NULL default '0',
																 `last_connection` datetime NOT NULL default '0000-00-00 00:00:00',
																 PRIMARY KEY (`user_id`)
																) TYPE=MyISAM");

				mysql_query("CREATE TABLE `$mysql_base_course".$dbGlu."online_connected` (
																 `user_id` int unsigned NOT NULL default '0',
																 `last_connection` datetime NOT NULL default '0000-00-00 00:00:00',
																 PRIMARY KEY (`user_id`)
																) TYPE=MyISAM");

				mysql_query("CREATE TABLE `$mysql_base_course".$dbGlu."online_link` (
																 `id` smallint(5) unsigned NOT NULL auto_increment,
																 `name` char(50) NOT NULL default '',
																 `url` char(100) NOT NULL default '',
																 PRIMARY KEY (`id`)
																) TYPE=MyISAM");

				mysql_query("DROP TABLE `$mysql_base_course".$dbGlu."online`");

				mysql_query("DROP TABLE `$mysql_base_course".$dbGlu."pages`");

				mysql_query("DROP TABLE `$mysql_base_course".$dbGlu."work_student`");

				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."last_tooledit` RENAME `".$prefix."item_property`");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."item_property` CHANGE `last_date` `lastedit_date` DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."item_property` CHANGE `ref` `ref` INT(10) NOT NULL");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."item_property` CHANGE `type` `lastedit_type` VARCHAR(100) NOT NULL");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."item_property` CHANGE `user_id` `lastedit_user_id` INT UNSIGNED DEFAULT '0' NOT NULL");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."item_property` CHANGE `group_id` `to_group_id` INT(10) UNSIGNED DEFAULT NULL");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."item_property` ADD `to_user_id` INT UNSIGNED DEFAULT NULL");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."item_property` ADD `visibility` TINYINT(1) DEFAULT '1' NOT NULL");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."item_property` ADD `start_visible` DATETIME NOT NULL");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."item_property` ADD `end_visible` DATETIME NOT NULL");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."item_property` ADD `insert_user_id` INT UNSIGNED NOT NULL AFTER `tool`");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."item_property` ADD `insert_date` DATETIME NOT NULL AFTER `insert_user_id`");

				/*
				-----------------------------------------------------------
				Update the announcement table
				-----------------------------------------------------------
				*/
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."announcement` CHANGE `contenu` `content` TEXT DEFAULT NULL");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."announcement` CHANGE `id` `id` MEDIUMINT(8) UNSIGNED NOT NULL AUTO_INCREMENT");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."announcement` CHANGE `temps` `end_date` DATE DEFAULT NULL");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."announcement` DROP `code_cours`");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."announcement` CHANGE `ordre` `display_order` MEDIUMINT(9) DEFAULT '0' NOT NULL");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."announcement` ADD `title` TEXT AFTER `id`");
				// Set item-properties of announcements and generate a title for the announcement
				$sql = "SELECT id,end_date,content FROM `$mysql_base_course".$dbGlu."announcement`";
				$res = mysql_query($sql);
				while ($obj = mysql_fetch_object($res))
				{
					$content_parts = explode('<br>',trim($obj->content));
					$title = strip_tags($content_parts[0]);
					if( strlen(trim($title)) == 0)
					{
						$title = substr(strip_tags($title),0,50).'...';
					}
					$sql = "UPDATE `$mysql_base_course".$dbGlu."announcement` SET title = '".mysql_real_escape_string($title)."' WHERE id='".$obj->id."'";
					mysql_query($sql);
					$sql = "INSERT INTO `$mysql_base_course".$dbGlu."item_property` SET ";
					$sql .= " tool = '".TOOL_ANNOUNCEMENT."', ";
					$sql .= " insert_date = '".$obj->end_date." 00:00:00', ";
					$sql .= " lastedit_date = '".$obj->end_date." 00:00:00', ";
					$sql .= " ref = '".$obj->id."', ";
					$sql .= " lastedit_type = 'AnnouncementAdded', ";
					$sql .= " to_group_id = '0' ";
					mysql_query($sql);
				}

				/*
				-----------------------------------------------------------
				Update the bb_whosonline table
				-----------------------------------------------------------
				*/
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."bb_whosonline` CHANGE `date` `online_date` VARCHAR(255) DEFAULT NULL");

				/*
				-----------------------------------------------------------
				Update the calendar_event table
				-----------------------------------------------------------
				*/
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."calendar_event` CHANGE `id` `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."calendar_event` CHANGE `titre` `title` VARCHAR(200) NOT NULL");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."calendar_event` CHANGE `contenu` `content` TEXT DEFAULT NULL");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."calendar_event` CHANGE `day` `start_date` DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."calendar_event` DROP `hour`");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."calendar_event` DROP `lasting`");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."calendar_event` ADD `end_date` DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL");
				// Set item-properties of calendar events
				$sql = "SELECT id,start_date FROM `$mysql_base_course".$dbGlu."calendar_event`";
				$res = mysql_query($sql);
				while ($obj = mysql_fetch_object($res))
				{
					$sql = "INSERT INTO `$mysql_base_course".$dbGlu."item_property` SET ";
					$sql .= " tool = '".TOOL_CALENDAR_EVENT."', ";
					$sql .= " insert_date = NOW(), ";
					$sql .= " lastedit_date = NOW(), ";
					$sql .= " ref = '".$obj->id."', ";
					$sql .= " lastedit_type = 'AgendaAdded', ";
					$sql .= " to_group_id = '0' ";
					mysql_query($sql);
				}

				/*
				-----------------------------------------------------------
				Update the course_description table
				-----------------------------------------------------------
				*/
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."course_description` CHANGE `id` `id` TINYINT(3) UNSIGNED NOT NULL AUTO_INCREMENT");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."course_description` DROP `upDate`");

				/*
				-----------------------------------------------------------
				Update the document table
				-----------------------------------------------------------
				*/
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."document` CHANGE `id` `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."document` CHANGE `comment` `comment` TEXT DEFAULT NULL");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."document` ADD `title` VARCHAR(255) AFTER `comment`");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."document` ADD `size` INT(16) NOT NULL");
				// @note: Item properties of documents are set in update_files.inc.php

				/*
				-----------------------------------------------------------
				Update the dropbox tables
				-----------------------------------------------------------
				*/
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."dropbox_file` CHANGE `id` `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."dropbox_file` CHANGE `uploaderId` `uploader_id` INT(10) UNSIGNED DEFAULT '0' NOT NULL");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."dropbox_file` CHANGE `filesize` `filesize` INT(10) UNSIGNED DEFAULT '0' NOT NULL");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."dropbox_file` CHANGE `uploadDate` `upload_date` DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."dropbox_file` CHANGE `lastUploadDate` `last_upload_date` DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL");

				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."dropbox_person` CHANGE `fileId` `file_id` INT(10) UNSIGNED DEFAULT '0' NOT NULL");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."dropbox_person` CHANGE `personId` `user_id` INT UNSIGNED DEFAULT '0' NOT NULL");

				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."dropbox_post` CHANGE `fileId` `file_id` INT(10) UNSIGNED DEFAULT '0' NOT NULL");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."dropbox_post` CHANGE `recipientId` `dest_user_id` INT UNSIGNED DEFAULT '0' NOT NULL");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."dropbox_post` ADD `feedback_date` DATETIME NOT NULL");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."dropbox_post` ADD `feedback` TEXT");

				// Set item-properties of dropbox files
				$sql = "SELECT * FROM `$mysql_base_course".$dbGlu."dropbox_file` f, `$mysql_base_course".$dbGlu."dropbox_post` p WHERE f.id = p.file_id";
				$res = mysql_query($sql);
				while ($obj = mysql_fetch_object($res))
				{
					$sql = "INSERT INTO `$mysql_base_course".$dbGlu."item_property` SET ";
					$sql .= " tool = '".TOOL_DROPBOX."', ";
					$sql .= " insert_date = '".$obj->upload_date."', ";
					$sql .= " lastedit_date = '".$obj->last_upload_date."', ";
					$sql .= " ref = '".$obj->id."', ";
					$sql .= " lastedit_type = 'DropboxFileAdded', ";
					$sql .= " to_group_id = '0', ";
					$sql .= " to_user_id = '".$obj->dest_user_id."', ";
					$sql .= " insert_user_id = '".$obj->uploader_id."'";
					mysql_query($sql);
				}
				
				/*
				-----------------------------------------------------------
				Update the forum tables (functions are at the end of this file)
				-----------------------------------------------------------
				*/			
				// Define the phpbb tables
				$phpbb_categories="`".$_course["dbNameGlu"]."bb_categories"."`"; 
				$phpbb_forums="`".$_course["dbNameGlu"]."bb_forums"."`"; 
				$phpbb_threads="`".$_course["dbNameGlu"]."bb_topics"."`"; 
				$phpbb_posts="`".$_course["dbNameGlu"]."bb_posts"."`"; 
				$phpbb_poststext="`".$_course["dbNameGlu"]."bb_posts_text"."`"; 
				
				$group_info="`".$_course["dbNameGlu"]."group_info"."`"; 

				// get all the added resources
				$added_resources=get_added_resources();	
				
				// ************* MIGRATE FORUMCATEGORIES ************* 	
				$sql_phpbb_categories="SELECT * FROM $phpbb_categories";
				$result_phpbb_categories=api_sql_query($sql_phpbb_categories, __LINE__, __FILE__);
				while ($row_phpbb_categories=mysql_fetch_array($result_phpbb_categories))
				{
					$values['forum_category_title']=$row_phpbb_categories['cat_title'];
					$return=store_forumcategory($values);
				}
				
				// ************* MIGRATE FORUMS ************* 
				$sql_phpbb_forums="SELECT forums.*, forumscat.cat_title, group_info.id as group_id , group_info.forum_state 
										FROM $phpbb_forums forums, $phpbb_categories forumscat 
											LEFT JOIN $group_info group_info
											ON forums.forum_id=group_info.forum_id
										WHERE forums.cat_id=forumscat.cat_id";
				$result_phpbb_forums=api_sql_query($sql_phpbb_forums,__LINE__,__FILE__);
				while ($row_phpbb_forums=mysql_fetch_array($result_phpbb_forums))
				{
					$values['forum_title']=$row_phpbb_forums['forum_name'];
					$values['forum_comment']=$row_phpbb_forums['forum_desc'];
					$values['forum_category']=get_forumcategory_id_by_name($row_phpbb_forums['cat_title']);
					$values['allow_anonymous_group']['allow_anonymous']=0;
					$values['students_can_edit_group']['students_can_edit']=0;
					$values['approval_direct_group']['approval_direct']=0;
					$values['allow_attachments_group']['allow_attachments']=1;
					$values['allow_new_threads_group']['allow_new_threads']=1;
					$values['default_view_type_group']['default_view_type']='flat';
					if (is_null($row_phpbb_forums['group_id']))
					{
						$groupid_of_groupforum=0;
					}
					else 
					{
						$groupid_of_groupforum=$row_phpbb_forums['group_id'];
					}
					$values['group_forum']=$groupid_of_groupforum;
					if ($row_phpbb_forums['forum_state']=='2')
					{
						$group_forum_status='private';
					}
					else 
					{
						$group_forum_status='public';
					}
					$values['public_private_group_forum_group']['public_private_group_forum']=$group_forum_status;
					
					//echo get_forumcategory_id_by_name($row_phpbb_forums['forum_name']); 
					$return=store_forum($values);
					
					// ************* MIGRATE THREADS ************* 
					// We now migrate all the threads in this forum
					$threads_posts_counter=migrate_threads_of_forum($row_phpbb_forums['forum_id'], $return['id']);	
				}			
				
				
				
				
				
				
				/*
				-----------------------------------------------------------
				Update the group tables
				-----------------------------------------------------------
				*/
				mysql_query("CREATE TABLE `$mysql_base_course".$dbGlu."group_category` (
																 `id` int(10) unsigned NOT NULL auto_increment,
																 `title` varchar(255) NOT NULL default '',
																 `description` text NOT NULL,
																 `forum_state` tinyint(3) unsigned NOT NULL default '1',
																 `doc_state` tinyint(3) unsigned NOT NULL default '1',
																 `max_student` smallint(5) unsigned NOT NULL default '8',
																 `self_reg_allowed` enum('0','1') NOT NULL default '0',
																 `self_unreg_allowed` enum('0','1') NOT NULL default '0',
																 `groups_per_user` smallint(5) unsigned NOT NULL default '0',
																 `display_order` smallint(5) unsigned NOT NULL default '0',
																 PRIMARY KEY (`id`)
																) TYPE=MyISAM");

				// Get the group-properties from old portal
				$sql = "SELECT * FROM `$mysql_base_course".$dbGlu."group_property`";
				$res = mysql_query($sql);

				$group_properties = mysql_fetch_array($res,MYSQL_ASSOC);

				mysql_query("DROP TABLE `$mysql_base_course".$dbGlu."group_property`");

				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."group_team` RENAME `".$prefix."group_info`");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."group_info` CHANGE `id` `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."group_info` CHANGE `tutor` `tutor_id` MEDIUMINT(8) UNSIGNED DEFAULT NULL");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."group_info` CHANGE `forumId` `forum_id` INT(10) UNSIGNED DEFAULT NULL");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."group_info` CHANGE `maxStudent` `max_student` SMALLINT(5) UNSIGNED DEFAULT '8' NOT NULL");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."group_info` CHANGE `secretDirectory` `secret_directory` VARCHAR(200) DEFAULT NULL");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."group_info` ADD `self_registration_allowed` ENUM('0', '1') DEFAULT '0' NOT NULL");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."group_info` ADD `self_unregistration_allowed` ENUM('0', '1') DEFAULT '0' NOT NULL");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."group_info` ADD `category_id` INT(10) UNSIGNED NOT NULL AFTER `name`");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."group_info` ADD `forum_state` ENUM('0', '1', '2') DEFAULT '0' NOT NULL AFTER `tutor_id`");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."group_info` ADD `doc_state` ENUM('0', '1', '2') DEFAULT '0' NOT NULL AFTER `max_student`");
				// Update group-properties (doc_state = always private, forum_state ~ old group properties, category_id = default category)
				$forum_state = ($group_properties['private']) == '0' ? '1' : '2';
				mysql_query("UPDATE `$mysql_base_course".$dbGlu."group_info` SET category_id='2', doc_state='2', forum_state = '".$forum_state."', secret_directory = CONCAT('/',secret_directory)");
				mysql_query("UPDATE `$mysql_base_course".$dbGlu."group_info` SET tutor_id='0' WHERE tutor_id IS NULL");

				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."group_rel_team_user` RENAME `".$prefix."group_rel_user`");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."group_rel_user` CHANGE `id` `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."group_rel_user` CHANGE `user` `user_id` INT UNSIGNED DEFAULT '0' NOT NULL");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."group_rel_user` CHANGE `team` `group_id` INT(10) UNSIGNED DEFAULT '0' NOT NULL");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."group_rel_user` CHANGE `role` `role` CHAR(50) NOT NULL");

				mysql_query("INSERT INTO `$mysql_base_course".$dbGlu."group_category` (`id`,`title`,`groups_per_user`) VALUES ('2','".get_lang('DefaultGroupCategory')."','".$group_properties['nbCoursPerUser']."')");

				/*
				-----------------------------------------------------------
				Update the learnpath tables
				-----------------------------------------------------------
				*/
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."learnpath_chapters` RENAME `".$prefix."learnpath_chapter`");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."learnpath_chapter` CHANGE `id` `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."learnpath_chapter` CHANGE `learnpath_id` `learnpath_id` INT(10) UNSIGNED DEFAULT NULL");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."learnpath_chapter` CHANGE `ordre` `display_order` MEDIUMINT(8) UNSIGNED DEFAULT '0' NOT NULL");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."learnpath_chapter` ADD `parent_chapter_id` INT UNSIGNED DEFAULT 0 NOT NULL AFTER `chapter_description`");

				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."learnpath_items` RENAME `".$prefix."learnpath_item`");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."learnpath_item` CHANGE `id` `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."learnpath_item` CHANGE `chapter` `chapter_id` INT(10) UNSIGNED DEFAULT NULL");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."learnpath_item` CHANGE `item_id` `item_id` INT(10) UNSIGNED DEFAULT NULL");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."learnpath_item` CHANGE `ordre` `display_order` SMALLINT(6) DEFAULT NULL");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."learnpath_item` CHANGE `prereq` `prereq_id` INT(10) UNSIGNED DEFAULT NULL");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."learnpath_item` ADD `prereq_completion_limit` VARCHAR(10) DEFAULT NULL");

				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."learnpath_main` CHANGE `learnpath_id` `learnpath_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT");

				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."learnpath_users` RENAME `".$prefix."learnpath_user`");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."learnpath_user` CHANGE `user_id` `user_id` INT UNSIGNED DEFAULT '0' NOT NULL");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."learnpath_user` CHANGE `learnpath_id` `learnpath_id` INT(10) UNSIGNED DEFAULT '0' NOT NULL");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."learnpath_user` CHANGE `learnpath_item_id` `learnpath_item_id` INT(10) UNSIGNED DEFAULT NULL");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."learnpath_user` CHANGE `score` `score` SMALLINT(6) DEFAULT NULL");

				/*
				-----------------------------------------------------------
				Update the link tables
				-----------------------------------------------------------
				*/
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."link` CHANGE `id` `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."link` CHANGE `url` `url` TEXT NOT NULL");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."link` CHANGE `titre` `title` VARCHAR(150) DEFAULT NULL");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."link` CHANGE `category` `category_id` SMALLINT(5) UNSIGNED DEFAULT NULL");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."link` CHANGE `ordre` `display_order` SMALLINT(5) UNSIGNED DEFAULT '0' NOT NULL");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."link` ADD `on_homepage` ENUM('0', '1') DEFAULT '0' NOT NULL");

				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."link_categories` RENAME `".$prefix."link_category`");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."link_category` CHANGE `id` `id` SMALLINT(5) UNSIGNED NOT NULL AUTO_INCREMENT");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."link_category` CHANGE `categoryname` `category_title` VARCHAR(255) NOT NULL");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."link_category` CHANGE `ordre` `display_order` MEDIUMINT(8) UNSIGNED DEFAULT '0' NOT NULL");

				// Set item-properties of links
				$sql = "SELECT id FROM `$mysql_base_course".$dbGlu."link`";
				$res = mysql_query($sql);
				while ($obj = mysql_fetch_object($res))
				{
					$sql = "INSERT INTO `$mysql_base_course".$dbGlu."item_property` SET ";
					$sql .= " tool = '".TOOL_LINK."', ";
					$sql .= " insert_date = NOW(), ";
					$sql .= " lastedit_date = NOW(), ";
					$sql .= " ref = '".$obj->id."', ";
					$sql .= " lastedit_type = 'LinkAdded', ";
					$sql .= " to_group_id = '0' ";
					mysql_query($sql);
				}
				
				// move all the links on the course homepage to the links tool
				// step 1: count the max display order of the 0 category_id
				$sql="SELECT * FROM `$mysql_base_course".$dbGlu."link` WHERE category_id='0' ORDER BY display_order DESC";
				$result2=mysql_query($sql);
				$row=mysql_fetch_array($result2);
				$maxsort=$row['display_order']; 
				
				// step 2: select all the links that were added to the course homepage
				$sql="SELECT * FROM `$mysql_base_course".$dbGlu."tool` WHERE link LIKE 'http://%'";
				$result2 = mysql_query($sql);
				while ($row=mysql_fetch_array($result2))
				{
					$maxsort++;
					// step 3: for each link on homepage: add to the links table
					$sqlinsert="INSERT INTO `$mysql_base_course".$dbGlu."link` (url, title, category_id, display_order, on_homepage) VALUES('".$row['link']."','".$row['name']."','0','".$maxsort."','1')";
					$resultinsert=mysql_query($sqlinsert);
					$insertid=mysql_insert_id();
					
					// step 4: for each link on homepage: add the link in the item_property table
					$sql_item_property = "INSERT INTO `$mysql_base_course".$dbGlu."item_property` SET ";
					$sql_item_property .= " tool = '".TOOL_LINK."', ";
					$sql_item_property .= " ref = '".$insertid."', ";
					$sql_item_property .= " lastedit_type = 'LinkAdded', ";
					$sql_item_property .= " to_group_id = '0' ";
					api_sql_query($sql_item_property);	

					// step 5: for each link on homepage: delete the link in the tool table.				
					$sqldelete="DELETE FROM `$mysql_base_course".$dbGlu."tool` WHERE id='".$row['id']."'";
					$resultdelete=mysql_query($sqldelete);
					
				}
				

				/*
				-----------------------------------------------------------
				Update the quiz tables
				-----------------------------------------------------------
				*/
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."quiz_rel_test_question` RENAME `".$prefix."quiz_rel_question`");

				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."quiz_test` RENAME `".$prefix."quiz`");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."quiz` CHANGE `titre` `title` VARCHAR(200) NOT NULL");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."quiz` CHANGE `description` `description` TEXT");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."quiz` CHANGE `sound` `sound` VARCHAR(50)");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."quiz` CHANGE `type` `type` TINYINT(3) UNSIGNED DEFAULT '1' NOT NULL");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."quiz` CHANGE `active` `active` ENUM('0', '1') DEFAULT '0' NOT NULL");

				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."quiz_answer` CHANGE `reponse` `answer` TEXT NOT NULL");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."quiz_answer` CHANGE `ponderation` `ponderation` SMALLINT(6) DEFAULT NULL");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."quiz_answer` CHANGE `r_position` `position` MEDIUMINT(8) UNSIGNED DEFAULT '1' NOT NULL");

				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."quiz_question` CHANGE `description` `description` TEXT");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."quiz_question` CHANGE `q_position` `position` MEDIUMINT(8) UNSIGNED DEFAULT '1' NOT NULL");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."quiz_question` CHANGE `picture` `picture` VARCHAR(50)");

				/*
				-----------------------------------------------------------
				Update the resource linker table
				-----------------------------------------------------------
				*/
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."added_resources` RENAME `".$prefix."resource`");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."resource` CHANGE `id` `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."resource` CHANGE `source_id` `source_id` INT(10) UNSIGNED DEFAULT NULL");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."resource` CHANGE `resource_id` `resource_id` INT(10) UNSIGNED DEFAULT NULL");

				/*
				-----------------------------------------------------------
				Update the scormdocument table
				-----------------------------------------------------------
				*/
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."scormdocument` CHANGE `id` `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."scormdocument` ADD `name` VARCHAR(100)");

				/*
				-----------------------------------------------------------
				Update the student_publication table
				-----------------------------------------------------------
				*/
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."assignment_doc` RENAME `".$prefix."student_publication`");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."student_publication` CHANGE `id` `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."student_publication` CHANGE `titre` `title` VARCHAR(200) DEFAULT NULL");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."student_publication` CHANGE `auteurs` `author` VARCHAR(200) DEFAULT NULL");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."student_publication` CHANGE `active` `active` TINYINT(4) DEFAULT NULL");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."student_publication` CHANGE `accepted` `accepted` TINYINT(4) DEFAULT '0'");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."student_publication` CHANGE `date` `sent_date` DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL");

				/*
				-----------------------------------------------------------
				Update the tool introduction table
				-----------------------------------------------------------
				*/
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."tool_intro` CHANGE `id` `id` VARCHAR(50) NOT NULL");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."tool_intro` CHANGE `texte_intro` `intro_text` TEXT NOT NULL");

				mysql_query("UPDATE `$mysql_base_course".$dbGlu."tool_intro` SET id='".TOOL_COURSE_HOMEPAGE."' WHERE id = '1'");

				/*
				-----------------------------------------------------------
				Update the user information tables
				-----------------------------------------------------------
				*/
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."userinfo_content` CHANGE `user_id` `user_id` INT(10) UNSIGNED DEFAULT '0' NOT NULL");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."userinfo_content` CHANGE `def_id` `definition_id` INT(10) UNSIGNED DEFAULT '0' NOT NULL");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."userinfo_content` CHANGE `ed_ip` `editor_ip` VARCHAR(39) DEFAULT NULL");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."userinfo_content` CHANGE `ed_date` `edition_time` DATETIME DEFAULT NULL");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."userinfo_content` CHANGE `content` `content` TEXT NOT NULL");

				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."userinfo_def` CHANGE `nbLine` `line_count` TINYINT(3) UNSIGNED DEFAULT '5' NOT NULL");

				/*
				-----------------------------------------------------------
				Update the tool table
				-----------------------------------------------------------
				*/
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."tool_list` RENAME `".$prefix."tool`");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."tool` CHANGE `id` `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."tool` CHANGE `rubrique` `name` VARCHAR(100) NOT NULL");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."tool` CHANGE `lien` `link` VARCHAR(255) NOT NULL");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."tool` CHANGE `visible` `visibility` TINYINT(3) UNSIGNED DEFAULT '0'");
				mysql_query("ALTER TABLE `$mysql_base_course".$dbGlu."tool` CHANGE `addedTool` `added_tool` ENUM('0', '1') DEFAULT '0'");
				mysql_query("UPDATE `$mysql_base_course".$dbGlu."tool` SET name='".TOOL_COURSE_DESCRIPTION."' WHERE link LIKE 'course_description/%'");
				mysql_query("UPDATE `$mysql_base_course".$dbGlu."tool` SET name='".TOOL_CALENDAR_EVENT."' WHERE link LIKE 'calendar/%'");
				mysql_query("UPDATE `$mysql_base_course".$dbGlu."tool` SET name='".TOOL_DOCUMENT."' WHERE link LIKE 'document/%'");
				mysql_query("UPDATE `$mysql_base_course".$dbGlu."tool` SET name='".TOOL_ANNOUNCEMENT."' WHERE link LIKE 'announcements/%'");
				mysql_query("UPDATE `$mysql_base_course".$dbGlu."tool` SET name='".TOOL_BB_FORUM."' WHERE link LIKE 'phpbb/%'");
				mysql_query("UPDATE `$mysql_base_course".$dbGlu."tool` SET name='".TOOL_LINK."' WHERE link = 'link/link.php'");
				mysql_query("Update `$mysql_base_course".$dbGlu."tool` SET name='".TOOL_DROPBOX."' WHERE link LIKE 'dropbox/%'");
				mysql_query("UPDATE `$mysql_base_course".$dbGlu."tool` SET name='".TOOL_QUIZ."' WHERE link LIKE 'exercice/%'");
				mysql_query("UPDATE `$mysql_base_course".$dbGlu."tool` SET name='".TOOL_USER."' WHERE link LIKE 'user/%'");
				mysql_query("UPDATE `$mysql_base_course".$dbGlu."tool` SET name='".TOOL_GROUP."' WHERE link LIKE 'group/%'");
				mysql_query("UPDATE `$mysql_base_course".$dbGlu."tool` SET name='".TOOL_CHAT."' WHERE link LIKE 'chat/%'");
				mysql_query("UPDATE `$mysql_base_course".$dbGlu."tool` SET name='".TOOL_CONFERENCE."' WHERE link LIKE 'online/%'");
				mysql_query("UPDATE `$mysql_base_course".$dbGlu."tool` SET name='".TOOL_STUDENTPUBLICATION."' WHERE link LIKE 'work/%'");
				mysql_query("UPDATE `$mysql_base_course".$dbGlu."tool` SET name='".TOOL_TRACKING."' WHERE link LIKE 'tracking/%'");
				mysql_query("UPDATE `$mysql_base_course".$dbGlu."tool` SET name='".TOOL_COURSE_SETTING."' WHERE link LIKE 'course_info/%'");
				mysql_query("UPDATE `$mysql_base_course".$dbGlu."tool` SET name='".TOOL_LEARNPATH."' WHERE link LIKE 'scorm/%'");
				mysql_query("UPDATE `$mysql_base_course".$dbGlu."tool` SET name='".TOOL_HOMEPAGE_LINK."', link='link/link.php?action=addlink' WHERE link LIKE 'external_module/%'");
				//mysql_query("INSERT INTO `$mysql_base_course".$dbGlu."tool` (`id`, `name`, `link`, `image`, `visibility`, `admin`, `address`, `added_tool`, `target`) VALUES ('', '".TOOL_BACKUP."', 'coursecopy/backup.php', 'backup.gif', '0', '1', '', '0', '_self')");
				mysql_query("INSERT INTO `$mysql_base_course".$dbGlu."tool` (`id`, `name`, `link`, `image`, `visibility`, `admin`, `address`, `added_tool`, `target`) VALUES ('', '".TOOL_COPY_COURSE_CONTENT."', 'coursecopy/copy_course.php', 'copy.gif', '0', '1', '', '0', '_self')");
				//mysql_query("INSERT INTO `$mysql_base_course".$dbGlu."tool` (`id`, `name`, `link`, `image`, `visibility`, `admin`, `address`, `added_tool`, `target`) VALUES ('', '".TOOL_RECYCLE_COURSE."', 'coursecopy/recycle_course.php', 'recycle.gif', '0', '1', '', '0', '_self')");
				mysql_query("UPDATE `$mysql_base_course".$dbGlu."tool` SET `added_tool` = '0' WHERE `added_tool` = ''");

				$i ++;
			}
			else
			{
				$nbr_courses --;
			}
		}
	}
}
else
{
	echo 'You are not allowed here !';
}








/**
* This function stores the forum category in the database. The new category is added to the end. 
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @todo is this the same function as in forumfunction.inc.php? If this is the case then it should not appear here.
*/
function store_forumcategory($values)
{
	global $table_categories; 
	global $_course;
	global $_user; 
	
	// find the max cat_order. The new forum category is added at the end => max cat_order + &
	$sql="SELECT MAX(cat_order) as sort_max FROM ".mysql_real_escape_string($table_categories);
	$result=api_sql_query($sql);
	$row=mysql_fetch_array($result);
	$new_max=$row['sort_max']+1;

	$sql="INSERT INTO ".$table_categories." (cat_title, cat_comment, cat_order) VALUES ('".mysql_real_escape_string($values['forum_category_title'])."','".mysql_real_escape_string($values['forum_category_comment'])."','".mysql_real_escape_string($new_max)."')";
	api_sql_query($sql); 
	$last_id=mysql_insert_id();
	api_item_property_update($_course, TOOL_FORUM_CATEGORY, $last_id,"ForumCategoryAdded", $_user['user_id']);
	return array('id'=>$last_id,'title'=>$values['forum_category_title']) ;
}

/**
* This function stores the forum in the database. The new forum is added to the end. 
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @todo is this the same function as in forumfunction.inc.php? If this is the case then it should not appear here.
*/
function store_forum($values)
{
	global $table_forums; 
	global $_course;
	global $_user; 

	// find the max forum_order for the given category. The new forum is added at the end => max cat_order + &
	$sql="SELECT MAX(forum_order) as sort_max FROM ".$table_forums." WHERE forum_category=".mysql_real_escape_string($values['forum_category']);
	$result=api_sql_query($sql);
	$row=mysql_fetch_array($result);
	$new_max=$row['sort_max']+1;

	
	$sql="INSERT INTO ".$table_forums." 
				(forum_title, forum_comment, forum_category, allow_anonymous, allow_edit, approval_direct_post, allow_attachments, allow_new_threads, default_view, forum_of_group, forum_group_public_private, forum_order) 
				VALUES ('".mysql_real_escape_string($values['forum_title'])."',
					'".mysql_real_escape_string($values['forum_comment'])."',
					'".mysql_real_escape_string($values['forum_category'])."',
					'".mysql_real_escape_string($values['allow_anonymous_group']['allow_anonymous'])."',
					'".mysql_real_escape_string($values['students_can_edit_group']['students_can_edit'])."',
					'".mysql_real_escape_string($values['approval_direct_group']['approval_direct'])."',
					'".mysql_real_escape_string($values['allow_attachments_group']['allow_attachments'])."',
					'".mysql_real_escape_string($values['allow_new_threads_group']['allow_new_threads'])."', 
					'".mysql_real_escape_string($values['default_view_type_group']['default_view_type'])."', 
					'".mysql_real_escape_string($values['group_forum'])."', 
					'".mysql_real_escape_string($values['public_private_group_forum_group']['public_private_group_forum'])."', 
					'".mysql_real_escape_string($new_max)."')";
	api_sql_query($sql, __LINE__,__FILE__); 
	$last_id=mysql_insert_id();
	api_item_property_update($_course, TOOL_FORUM, $last_id,"ForumCategoryAdded", $_user['user_id']);
	return array('id'=>$last_id, 'title'=>$values['forum_title']);
}

/**
* This function stores a new thread. This is done through an entry in the forum_thread table AND 
* in the forum_post table because. The threads are also stored in the item_property table. (forum posts are not (yet))
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @todo is this the same function as in forumfunction.inc.php? If this is the case then it should not appear here.
*/
function store_thread($values)
{
	global $table_threads; 
	global $table_posts; 
	global $_user; 
	global $_course; 
	global $current_forum; 
	
	// We first store an entry in the forum_thread table because the thread_id is used in the forum_post table
	$sql="INSERT INTO $table_threads (thread_title, forum_id, thread_poster_id, thread_poster_name, thread_views, thread_date, thread_sticky) 
			VALUES ('".mysql_real_escape_string($values['post_title'])."',
					'".mysql_real_escape_string($values['forum_id'])."',
					'".mysql_real_escape_string($values['user_id'])."', 
					'".mysql_real_escape_string($values['poster_name'])."', 
					'".mysql_real_escape_string($values['topic_views'])."', 
					'".mysql_real_escape_string($values['post_date'])."',
					'".mysql_real_escape_string($values['thread_sticky'])."')";
	$result=api_sql_query($sql, __LINE__, __FILE__);
	$last_thread_id=mysql_insert_id();
	api_item_property_update($_course, TOOL_FORUM_THREAD, $last_thread_id,"ForumThreadAdded", $_user['user_id']);
	// if the forum properties tell that the posts have to be approved we have to put the whole thread invisible
	// because otherwise the students will see the thread and not the post in the thread. 
	// we also have to change $visible because the post itself has to be visible in this case (otherwise the teacher would have 
	// to make the thread visible AND the post
	if ($values['visible']==0)
	{
		api_item_property_update($_course, TOOL_FORUM_THREAD, $last_thread_id,"invisible", $_user['user_id']);
		$visible=1;
	}

	return $last_thread_id; 
}

/**
* This function migrates the threads of a given phpbb forum to a new forum of the new forum tool
* @param $phpbb_forum_id the forum_id of the old (phpbb) forum
* @param $new_forum_id the forum_id in the new forum
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @todo is this the same function as in forumfunction.inc.php? If this is the case then it should not appear here.
*/
function migrate_threads_of_forum($phpbb_forum_id, $new_forum_id)
{
	global $phpbb_threads; 
	global $table_forums; 
	
	$table_users = Database :: get_main_table(MAIN_USER_TABLE);
	
	$sql_phpbb_threads="SELECT forum.*, users.user_id 
							FROM $phpbb_threads forum, $table_users users 
							WHERE forum_id='".mysql_real_escape_string($phpbb_forum_id)."' 
							AND forum.nom=users.lastname AND forum.prenom=users.firstname
							";
	$result_phpbb_threads=api_sql_query($sql_phpbb_threads);
	$threads_counter=0;
	while ($row_phpbb_threads=mysql_fetch_array($result_phpbb_threads))
	{
		$values['post_title']=$row_phpbb_threads['topic_title']; 
		$values['forum_id']=$new_forum_id;
		$values['user_id']=$row_phpbb_threads['user_id'];
		$values['poster_name']=0;
		$values['topic_views']=$row_phpbb_threads['topic_views'];
		$values['post_date']=$row_phpbb_threads['topic_time'];
		$values['thread_sticky']=0;
		$values['visible']=$row_phpbb_threads['visible'];
		//my_print_r($values);
		$new_forum_thread_id=store_thread($values);
		
		// now we migrate the posts of the given thread
		$posts_counter=$posts_counter+migrate_posts_of_thread($row_phpbb_threads['topic_id'], $new_forum_thread_id, $new_forum_id);
		
		$threads_counter++;
	}
	
	// Now we update the forum_forum table with the total number of posts for the given forum.
	$sql="UPDATE $table_forums 
			SET forum_posts='".mysql_real_escape_string($posts_counter)."',
			forum_threads='".mysql_real_escape_string($threads_counter)."' 
			WHERE forum_id='".mysql_real_escape_string($new_forum_id)."'";
	//echo $sql; 
	$result=api_sql_query($sql);
	return array("threads"=>$threads_counter, "posts"=>$posts_counter);
}

/**
* This function migrates the posts of a given phpbb thread (topic) to a thread in the new forum tool
* @param $phpbb_forum_id the forum_id of the old (phpbb) forum
* @param $new_forum_id the forum_id in the new forum
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @todo is this the same function as in forumfunction.inc.php? If this is the case then it should not appear here.
*/
function migrate_posts_of_thread($phpbb_thread_id, $new_forum_thread_id, $new_forum_id)
{
	global $phpbb_posts;
	global $phpbb_poststext; 
	global $table_posts; 
	global $table_threads; 
	global $added_resources; 
	
	$table_users = Database :: get_main_table(MAIN_USER_TABLE);
	$table_added_resources = Database::get_course_table(LINKED_RESOURCES_TABLE);
	
	
	$post_counter=0;
	
	$sql_phpbb_posts="SELECT posts.*, posts_text.*, users.user_id, users.lastname, users.firstname FROM $phpbb_posts posts, $phpbb_poststext posts_text, $table_users users 
						WHERE posts.post_id=posts_text.post_id
						AND posts.nom=users.lastname 
						AND posts.prenom=users.firstname
						AND posts.topic_id='".mysql_real_escape_string($phpbb_thread_id)."'
						";
	$result_phpbb_posts=api_sql_query($sql_phpbb_posts);
	while($row_phpbb_posts=mysql_fetch_array($result_phpbb_posts))
	{
		$values=array();
		$values['post_title']=$row_phpbb_posts['post_title'];
		$values['post_text']=$row_phpbb_posts['post_text'];
		$values['thread_id']=$new_forum_thread_id;
		$values['forum_id']=$new_forum_id; 
		$values['user_id']=$row_phpbb_posts['user_id']; 
		$values['post_date']=$row_phpbb_posts['post_time'];
		$values['post_notification']=$row_phpbb_posts['topic_notify'];
		$values['post_parent_id']=0; 
		$values['visible']=1;		
	
		// We first store an entry in the forum_post table
		$sql="INSERT INTO $table_posts (post_title, post_text, thread_id, forum_id, poster_id,  post_date, post_notification, post_parent_id, visible) 
				VALUES ('".mysql_real_escape_string($values['post_title'])."',
						'".mysql_real_escape_string($values['post_text'])."',
						'".mysql_real_escape_string($values['thread_id'])."',
						'".mysql_real_escape_string($values['forum_id'])."',
						'".mysql_real_escape_string($values['user_id'])."',
						'".mysql_real_escape_string($values['post_date'])."',
						'".mysql_real_escape_string($values['post_notification'])."',
						'".mysql_real_escape_string($values['post_parent_id'])."',
						'".mysql_real_escape_string($values['visible'])."')";
		$result=api_sql_query($sql, __LINE__, __FILE__);
		$post_counter++;
		$last_post_id=mysql_insert_id();
		
		
		// We check if there added resources and if so we update them
		if (in_array($row_phpbb_posts['post_id'],$added_resources))
		{
			$sql_update_added_resource="UPDATE $table_added_resources 
					SET source_type='forum_post', source_id='".mysql_real_escape_string($last_post_id)."'
					WHERE source_type='".mysql_real_escape_string(TOOL_BB_POST)."' AND source_id='".mysql_real_escape_string($row_phpbb_posts['post_id'])."'";
			echo $sql_update_added_resource;
			$result=api_sql_query($sql_update_added_resource, __LINE__, __FILE__);
		}


	}
	
	// update the thread_last_post of the post table AND the 
	$sql="UPDATE $table_threads SET thread_last_post='".mysql_real_escape_string($last_post_id)."', 
			thread_replies='".mysql_real_escape_string($post_counter-1)."'
			WHERE thread_id='".mysql_real_escape_string($new_forum_thread_id)."'";
	//echo $sql; 
	$result=api_sql_query($sql, __LINE__, __FILE__);
	//echo $sql; 
	return $post_counter; 
}

/**
* This function gets all the added resources for phpbb forum posts
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @todo is this the same function as in forumfunction.inc.php? If this is the case then it should not appear here.
*/
function get_added_resources()
{
	$table_added_resources = Database::get_course_table(LINKED_RESOURCES_TABLE);
	$return_array=array();

	// TODO: now we also migrate the added resources. 
	$sql_added_resources="SELECT * FROM $table_added_resources WHERE source_type='".mysql_real_escape_string(TOOL_BB_POST)."'";
	$result=api_sql_query($sql_added_resources);
	while ($row=mysql_fetch_array($result))
	{
		$return_array[]=$row['source_id'];
	}
	return $return_array;
}

/**
* This function gets the forum category information based on the name 
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @todo is this the same function as in forumfunction.inc.php? If this is the case then it should not appear here.
*/
function get_forumcategory_id_by_name($forum_category_name)
{
	global $table_categories;
	
	$sql="SELECT cat_id FROM $table_categories WHERE cat_title='".mysql_real_escape_string($forum_category_name)."'";
	//echo $sql; 
	$result=api_sql_query($sql,__LINE__,__FILE__);
	$row=mysql_fetch_array($result);
	//echo $row['cat_id'];
	return $row['cat_id'];
}



?>