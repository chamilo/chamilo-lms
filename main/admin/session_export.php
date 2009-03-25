<?php // $Id: session_export.php,v 1.1 2006/04/20 09:58:01 elixir_inter Exp $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Olivier Brouckaert

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
$language_file='admin';

$cidReset=true;

include('../inc/global.inc.php');

// setting the section (for the tabs)
$this_section=SECTION_PLATFORM_ADMIN;

api_protect_admin_script(true);
include(api_get_path(LIBRARY_PATH).'/fileManage.lib.php');

$session_id=$_GET['session_id'];
$formSent=0;
$errorMsg='';

// Database Table Definitions
$tbl_user					= Database::get_main_table(TABLE_MAIN_USER);
$tbl_course      			= Database::get_main_table(TABLE_MAIN_COURSE);
$tbl_course_user 			= Database::get_main_table(TABLE_MAIN_COURSE_USER);
$tbl_session      			= Database::get_main_table(TABLE_MAIN_SESSION);
$tbl_session_user      		= Database::get_main_table(TABLE_MAIN_SESSION_USER);
$tbl_session_course      	= Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
$tbl_session_course_user 	= Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);


$archivePath=api_get_path(SYS_PATH).$archiveDirName.'/';
$archiveURL=api_get_path(WEB_CODE_PATH).'course_info/download.php?archive=';

$tool_name=get_lang('ExportSessionListXMLCSV');

$interbreadcrumb[]=array('url' => 'index.php',"name" => get_lang('PlatformAdmin'));

set_time_limit(0);

if($_POST['formSent'] )
{
	$formSent=$_POST['formSent'];
	$file_type=($_POST['file_type'] == 'csv')?'csv':'xml';
	$session_id=$_POST['session_id'];
	if(empty($session_id))
	{
		$sql = "SELECT id,name,id_coach,date_start,date_end FROM $tbl_session ORDER BY id";		
		global $_configuration;	
		if ($_configuration['multiple_access_urls']==true) {	
			$tbl_session_rel_access_url= Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);	
			$access_url_id = api_get_current_access_url_id();
			if ($access_url_id != -1){
			$sql = "SELECT id, name,id_coach,date_start,date_end FROM $tbl_session s INNER JOIN $tbl_session_rel_access_url as session_rel_url 		
				ON (s.id= session_rel_url.session_id)		
				WHERE access_url_id = $access_url_id
				ORDER BY id";		
			}
		} 

		$result=api_sql_query($sql,__FILE__,__LINE__);
	}
	else
	{
		$sql = "SELECT id,name,username,date_start,date_end
				FROM $tbl_session
				INNER JOIN $tbl_user
					ON $tbl_user.user_id = $tbl_session.id_coach
				WHERE id='$session_id'";

		$result = api_sql_query($sql,__FILE__,__LINE__);

	}

	if(Database::num_rows($result))
	{
		if(!file_exists($archivePath))
		{
			mkpath($archivePath);
		}

		if(!file_exists($archivePath.'index.html'))
		{
			$fp=fopen($archivePath.'index.html','w');

			fputs($fp,'<html><head></head><body></body></html>');

			fclose($fp);
		}

		$archiveFile='export_sessions_'.$session_id.'_'.date('Y-m-d_H-i-s').'.'.$file_type;

		while( file_exists($archivePath.$archiveFile))
		{
			$archiveFile='export_users_'.$session_id.'_'.date('Y-m-d_H-i-s').'_'.uniqid('').'.'.$file_type;
		}
		$fp=fopen($archivePath.$archiveFile,'w');

		if($file_type == 'csv')
		{
			$cvs = true;
			fputs($fp,"SessionName;Coach;DateStart;DateEnd;Users;Courses;\n");
		}
		else
		{
			$cvs = false;
			fputs($fp,"<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n<Sessions>\n");
		}

		while($row=Database::fetch_array($result))
		{


			$add = '';
			$row['name'] = str_replace(';',',',$row['name']);
			$row['username'] = str_replace(';',',',$row['username']);
			$row['date_start'] = str_replace(';',',',$row['date_start']);
			$row['date_end'] = str_replace(';',',',$row['date_end']);
			if($cvs){
				$add.= $row['name'].';'.$row['username'].';'.$row['date_start'].';'.$row['date_end'].';';
			}
			else {
				$add = "\t<Session>\n"
						 ."\t\t<SessionName>$row[name]</SessionName>\n"
						 ."\t\t<Coach>$row[username]</Coach>\n"
						 ."\t\t<DateStart>$row[date_start]</DateStart>\n"
						 ."\t\t<DateEnd>$row[date_end]</DateEnd>\n";
			}

			//users
			$sql = "SELECT DISTINCT $tbl_user.username FROM $tbl_user
					INNER JOIN $tbl_session_user
						ON $tbl_user.user_id = $tbl_session_user.id_user
						AND $tbl_session_user.id_session = '".$row['id']."'";

			$rsUsers = api_sql_query($sql,__FILE__,__LINE__);
			$users = '';
			while($rowUsers = Database::fetch_array($rsUsers)){
				if($cvs){
					$users .= str_replace(';',',',$rowUsers['username']).'|';
				}
				else {
					$users .= "\t\t<User>$rowUsers[username]</User>\n";
				}
			}
			if(!empty($users) && $cvs)
				$users = substr($users , 0, strlen($users)-1);

			if($cvs)
				$users .= ';';

			$add .= $users;




			//courses
			$sql = "SELECT DISTINCT $tbl_course.code, $tbl_user.username FROM $tbl_course
					INNER JOIN $tbl_session_course
						ON $tbl_course.code = $tbl_session_course.course_code
						AND $tbl_session_course.id_session = '".$row['id']."'
					LEFT JOIN $tbl_user
						ON $tbl_user.user_id = $tbl_session_course.id_coach";
			$rsCourses = api_sql_query($sql,__FILE__,__LINE__);

			$courses = '';
			while($rowCourses = Database::fetch_array($rsCourses)){

				if($cvs){
					$courses .= str_replace(';',',',$rowCourses['code']);
					$courses .= '['.str_replace(';',',',$rowCourses['username']).'][';
				}
				else {
					$courses .= "\t\t<Course>\n";
					$courses .= "\t\t\t<CourseCode>$rowCourses[code]</CourseCode>\n";
					$courses .= "\t\t\t<Coach>$rowCourses[username]</Coach>\n";
				}

				// rel user courses
				$sql = "SELECT DISTINCT username
						FROM $tbl_user
						INNER JOIN $tbl_session_course_user
							ON $tbl_session_course_user.id_user = $tbl_user.user_id
							AND $tbl_session_course_user.course_code='".$rowCourses['code']."'
							AND id_session='".$row['id']."'";

				$rsUsersCourse = api_sql_query($sql,__FILE__,__LINE__);
				while($rowUsersCourse = Database::fetch_array($rsUsersCourse)){
					if($cvs){
						$userscourse .= str_replace(';',',',$rowUsersCourse['username']).',';
					}
					else {
						$courses .= "\t\t\t<User>$rowUsersCourse[username]</User>\n";
					}
				}
				if($cvs){
					if(!empty($userscourse))
						$userscourse = substr($userscourse , 0, strlen($userscourse)-1);

					$courses .= $userscourse.']|';
				}
				else {
					$courses .= "\t\t</Course>\n";
				}
			}
			if(!empty($courses) && $cvs)
				$courses = substr($courses , 0, strlen($courses)-1);
			$add .= $courses;

			if($cvs)
				$add .= ';';
			else
				$add .= "\t</Session>\n";

			fputs($fp, $add);
		}

		if(!$cvs)
			fputs($fp,"</Sessions>\n");
		fclose($fp);

		$errorMsg=get_lang('UserListHasBeenExported').'<br/><a href="'.$archiveURL.$archiveFile.'">'.get_lang('ClickHereToDownloadTheFile').'</a>';
	}
}

Display::display_header($tool_name);

api_display_tool_title($tool_name);


//select of sessions
$sql = "SELECT id, name FROM $tbl_session ORDER BY name";
global $_configuration;	
if ($_configuration['multiple_access_urls']==true) {		
	$tbl_session_rel_access_url= Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);	
	$access_url_id = api_get_current_access_url_id();
	if ($access_url_id != -1){
	$sql = "SELECT id, name FROM $tbl_session s INNER JOIN $tbl_session_rel_access_url as session_rel_url 		
		ON (s.id= session_rel_url.session_id)		
		WHERE access_url_id = $access_url_id
		ORDER BY name";		
	}
} 


$result=api_sql_query($sql,__FILE__,__LINE__);

$Sessions=api_store_result($result);
?>

<?php
if(!empty($errorMsg))
{
	Display::display_normal_message($errorMsg, false); //main API
}
?>

<form method="post" action="<?php echo api_get_self(); ?>" style="margin:0px;">
<input type="hidden" name="formSent" value="1">
<table border="0" cellpadding="5" cellspacing="0">
<tr>
  <td nowrap="nowrap" valign="top"><?php echo get_lang('OutputFileType'); ?> :</td>
  <td>
	<input class="checkbox" type="radio" name="file_type" id="file_type_xml" value="xml" <?php if($formSent && $file_type == 'xml') echo 'checked="checked"'; ?>> <label for="file_type_xml">XML</label><br>
	<input class="checkbox" type="radio" name="file_type" id="file_type_csv"  value="csv" <?php if(!$formSent || $file_type == 'csv') echo 'checked="checked"'; ?>> <label for="file_type_csv">CSV</label><br>
  </td>
</tr>
<tr>
  <td><?php echo get_lang('WhichSessionToExport'); ?> :</td>
  <td><select name="session_id">
	<option value=""><?php echo get_lang('AllSessions') ?></option>

<?php
foreach($Sessions as $enreg)
{
?>

	<option value="<?php echo $enreg['id']; ?>" <?php if($session_id == $enreg['id']) echo 'selected="selected"'; ?>><?php echo $enreg['name']; ?></option>

<?php
}

unset($Courses);
?>

  </select></td>
</tr>
<tr>
  <td>&nbsp;</td>
  <td>
  <button class="save" type="submit" name="name" value="<?php echo get_lang('ExportSession') ?>"><?php echo get_lang('ExportSession') ?></button>
  </td>
</tr>
</table>
</form>

<?php
/*
==============================================================================
		FOOTER
==============================================================================
*/
Display::display_footer();
?>
