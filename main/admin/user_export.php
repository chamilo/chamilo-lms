<?php
// $Id: user_export.php 18050 2009-01-28 18:54:19Z cfasanando $
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
$result = api_sql_query($sql, __FILE__, __LINE__);
while ($course = mysql_fetch_object($result))
{
	$courses[$course->code] = $course->visual_code.' - '.$course->title;
}
$form = new FormValidator('export_users');
$form->addElement('radio', 'file_type', get_lang('OutputFileType'), 'XML','xml');
$form->addElement('radio', 'file_type', null, 'CSV','csv');
$form->addElement('checkbox', 'addcsvheader', get_lang('AddCSVHeader'), get_lang('YesAddCSVHeader'),'1');
$form->addElement('select', 'course_code', get_lang('OnlyUsersFromCourse'), $courses);
$form->addElement('submit', 'submit', get_lang('Ok'));
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
					".(($userPasswordCrypted)?" ":"u.password AS Password, ")."
					u.auth_source	AS AuthSource,
					u.status		AS Status,
					u.official_code	AS OfficialCode,
					u.phone		AS Phone";
	if (strlen($course_code) > 0)
	{
		$sql .= " FROM $user_table u, $course_user_table cu WHERE u.user_id = cu.user_id AND course_code = '$course_code' ORDER BY lastname,firstname";
		$filename = 'export_users_'.$course_code.'_'.date('Y-m-d_H-i-s');
	}
	else
	{
		$sql .= " FROM $user_table u ORDER BY lastname,firstname";
		$filename = 'export_users_'.date('Y-m-d_H-i-s');
	}
	$data = array();
	if ($export['addcsvheader']=='1' AND $export['file_type']=='csv')
	{
		if($userPasswordCrypted){
		$data[] = array('UserId', 'LastName', 'FirstName', 'Email', 'UserName', 'AuthSource', 'Status', 'OfficialCode', 'Phone');
		} else {
		$data[] = array('UserId', 'LastName', 'FirstName', 'Email', 'UserName','Password',  'AuthSource', 'Status', 'OfficialCode', 'Phone');	
		}
	}
	$res = api_sql_query($sql,__FILE__,__LINE__);	
	while($user = mysql_fetch_array($res,MYSQL_ASSOC))
	{		
		$data[] = $user	;							
	}
	
	switch($file_type)
	{
		case 'xml':
			Export::export_table_xml($data,$filename,'Contact','Contacts');
			break;
		case 'csv':
			Export::export_table_csv($data,$filename);
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