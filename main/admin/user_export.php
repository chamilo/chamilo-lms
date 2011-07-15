<?php
// $Id: user_export.php 19597 2009-04-07 14:38:36Z pcool $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Olivier Brouckaert
	Copyright (c) Bart Mollet, Hogeschool Gent

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
*	@package dokeos.admin
==============================================================================
*/
// name of the language file that needs to be included
$language_file = 'admin';

$cidReset = true;

include ('../inc/global.inc.php');
$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();
include (api_get_path(LIBRARY_PATH).'fileManage.lib.php');
include (api_get_path(LIBRARY_PATH).'export.lib.inc.php');
include (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');

// Database table definitions
$course_table 		= Database :: get_main_table(TABLE_MAIN_COURSE);
$user_table 		= Database :: get_main_table(TABLE_MAIN_USER);
$course_user_table 	= Database :: get_main_table(TABLE_MAIN_COURSE_USER);

$tool_name = get_lang('ExportUserListXMLCSV');

$interbreadcrumb[] = array ("url" => 'index.php', "name" => get_lang('PlatformAdmin'));

set_time_limit(0);

$courses = array ();
$courses[''] = '--';
$sql = "SELECT code,visual_code,title FROM $course_table ORDER BY visual_code";

global $_configuration;
if ($_configuration['multiple_access_urls']) {
	$tbl_course_rel_access_url= Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
	$access_url_id = api_get_current_access_url_id();
	if ($access_url_id != -1){
	$sql = "SELECT code,visual_code,title FROM $course_table as c INNER JOIN $tbl_course_rel_access_url as course_rel_url
		ON (c.code = course_rel_url.course_code)
		WHERE access_url_id = $access_url_id
		ORDER BY visual_code";
	}
}
$result = Database::query($sql);
while ($course = Database::fetch_object($result))
{
	$courses[$course->code] = $course->visual_code.' - '.$course->title;
}
$form = new FormValidator('export_users');
$form->addElement('header', '', $tool_name);
$form->addElement('radio', 'file_type', get_lang('OutputFileType'), 'XML','xml');
$form->addElement('radio', 'file_type', null, 'CSV','csv');
$form->addElement('checkbox', 'addcsvheader', get_lang('AddCSVHeader'), get_lang('YesAddCSVHeader'),'1');
$form->addElement('select', 'course_code', get_lang('OnlyUsersFromCourse'), $courses);
$form->addElement('style_submit_button', 'submit',get_lang('Export'),'class="save"');
$form->setDefaults(array('file_type'=>'csv'));

if ($form->validate())
{
	global $userPasswordCrypted;

	$export = $form->exportValues();
	$file_type = $export['file_type'];
	$course_code = $export['course_code'];

	$sql = "SELECT  u.user_id 	AS UserId,
					u.lastname 	AS LastName,
					u.firstname 	AS FirstName,
					u.email 		AS Email,
					u.username	AS UserName,
					".(($userPasswordCrypted!='none')?" ":"u.password AS Password, ")."
					u.auth_source	AS AuthSource,
					u.status		AS Status,
					u.official_code	AS OfficialCode,
					u.phone		AS Phone";
	if (strlen($course_code) > 0) {
		$sql .= " FROM $user_table u, $course_user_table cu WHERE u.user_id = cu.user_id AND course_code = '$course_code' AND cu.relation_type<>".COURSE_RELATION_TYPE_RRHH." ORDER BY lastname,firstname";
		$filename = 'export_users_'.$course_code.'_'.date('Y-m-d_H-i-s');
	} else {
		global $_configuration;
		if ($_configuration['multiple_access_urls']) {
			$tbl_user_rel_access_url= Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
			$access_url_id = api_get_current_access_url_id();
			if ($access_url_id != -1) {
			$sql.= " FROM $user_table u INNER JOIN $tbl_user_rel_access_url as user_rel_url
				ON (u.user_id= user_rel_url.user_id)
				WHERE access_url_id = $access_url_id
				ORDER BY lastname,firstname";
			}
		} else {
			$sql .= " FROM $user_table u ORDER BY lastname,firstname";
		}
		$filename = 'export_users_'.date('Y-m-d_H-i-s');
	}
	require_once (api_get_path(LIBRARY_PATH).'usermanager.lib.php');
	$data = array();
	$extra_fields = Usermanager::get_extra_fields(0, 0, 5, 'ASC',false);
	if ($export['addcsvheader']=='1' AND $export['file_type']=='csv')
	{
		if($userPasswordCrypted!='none') {
			$data[] = array('UserId', 'LastName', 'FirstName', 'Email', 'UserName', 'AuthSource', 'Status', 'OfficialCode', 'PhoneNumber');
		} else {
			$data[] = array('UserId', 'LastName', 'FirstName', 'Email', 'UserName','Password',  'AuthSource', 'Status', 'OfficialCode', 'PhoneNumber');
		}

		foreach($extra_fields as $extra) {
			$data[0][]=$extra[1];
		}

	}
	$res = Database::query($sql);
	while($user = Database::fetch_array($res,'ASSOC')) {
		$student_data= UserManager :: get_extra_user_data($user['UserId'],true,false);
		foreach($student_data as $key=>$value) {
			$key= substr($key,6);
			if (is_array($value))
				$user[$key]= $value[$key];
			else {
				$user[$key]= $value;
			}
		}
		$data[] = $user	;
	}

	switch($file_type) {
		case 'xml':
			Export::export_table_xml($data,$filename,'Contact','Contacts');
			exit;
			break;
		case 'csv':
			Export::export_table_csv($data,$filename);
			exit;
			break;
	}
}
Display :: display_header($tool_name);
//api_display_tool_title($tool_name);
$form->display();
/*
==============================================================================
		FOOTER
==============================================================================
*/
Display :: display_footer();
?>