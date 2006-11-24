<?php 
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2006 Dokeos S.A.
	Copyright (c) 2006 Ghent University (UGent)
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
 * --------------------------------------
 *       DEBUGGING VARS
 * --------------------------------------
 */
$DEBUG = TRUE;

/**
 * --------------------------------------
 *       DATABASE TABLE VARIABLES
 * --------------------------------------
 */
$dropbox_cnf["postTbl"] = $_course["dbNameGlu"] . "dropbox_post";
$dropbox_cnf["fileTbl"] = $_course["dbNameGlu"] . "dropbox_file";
$dropbox_cnf["personTbl"] = $_course["dbNameGlu"] . "dropbox_person";
$dropbox_cnf["introTbl"] = $_course["dbNameGlu"] . "tool_intro";
$dropbox_cnf["userTbl"] = $_configuration['main_database'] . "`.`user";
$dropbox_cnf["courseUserTbl"] = Database::get_main_table(TABLE_MAIN_COURSE_USER);
$dropbox_cnf['tbl_category'] = $_course["dbNameGlu"] . "dropbox_category";
$dropbox_cnf['tbl_feedback'] = $_course["dbNameGlu"] . "dropbox_feedback";

/**
 * --------------------------------------
 *       INITIALISE OTHER VARIABLES & CONSTANTS
 * --------------------------------------
 */
$dropbox_cnf["courseId"] = $_cid;
$dropbox_cnf["sysPath"] = api_get_path('SYS_COURSE_PATH') . $_course["path"] . "/dropbox"; //path to dropbox subdir in course containing the uploaded files
$dropbox_cnf["webPath"] = api_get_path('WEB_COURSE_PATH') . $_course["path"] . "/dropbox";
$dropbox_cnf["maxFilesize"] = get_setting("dropbox_max_filesize"); //file size limit as imposed by the platform admin (see Dokeos Config Settings on the platform administration section)
//$dropbox_cnf["version"] = "1.4";
$dropbox_cnf["allowOverwrite"] = string_2_boolean(get_setting("dropbox_allow_overwrite"));
$dropbox_cnf["allowJustUpload"] = string_2_boolean(get_setting("dropbox_allow_just_upload"));
$dropbox_cnf["allowStudentToStudent"] = string_2_boolean(get_setting("dropbox_allow_student_to_student"));
$dropbox_cnf["allowGroup"] = string_2_boolean(get_setting("dropbox_allow_group"));

/**
 * --------------------------------------
 * RH:   INITIALISE MAILING VARIABLES
 * --------------------------------------
 */
$dropbox_cnf["allowMailing"] = string_2_boolean(get_setting("dropbox_allow_mailing"));  // false = no mailing functionality
$dropbox_cnf["mailingIdBase"] = 10000000;  // bigger than any user_id,
// allowing enough space for pseudo_ids as uploader_id, dest_user_id, user_id:
// mailing pseudo_id = dropbox_cnf("mailingIdBase") + mailing id
$dropbox_cnf["mailingZipRegexp"] = '/^(.*)(STUDENTID|USERID|LOGINNAME)(.*)\.ZIP$/i';
$dropbox_cnf["mailingWhereSTUDENTID"] = "official_code";
$dropbox_cnf["mailingWhereUSERID"] = "username";
$dropbox_cnf["mailingWhereLOGINNAME"] = "username";
$dropbox_cnf["mailingFileRegexp"] = '/^(.+)\.\w{1,4}$/';

$dropbox_cnf['sent_received_tabs']=true;




?>