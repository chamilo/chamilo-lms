<?php // $Id: session_import.php,v 1.1 2006/04/20 09:58:01 elixir_inter Exp $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 University of Ghent (UGent)
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

$langFile = array('admin','registration');

$cidReset=true;

include('../inc/global.inc.php');

api_protect_admin_script();

include(api_get_library_path().'/fileManage.lib.php');
include(api_get_library_path().'/xmllib.php');

$formSent=0;
$errorMsg='';

// Database table definitions
$tbl_user      				= Database::get_main_table(TABLE_MAIN_USER);
$tbl_course      			= Database::get_main_table(TABLE_MAIN_COURSE);
$tbl_session      			= Database::get_main_table(TABLE_MAIN_SESSION);
$tbl_session_user      		= Database::get_main_table(TABLE_MAIN_SESSION_USER);
$tbl_session_course      	= Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
$tbl_session_course_user	= Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

$tool_name=get_lang('ImportSessionListXMLCSV');

$interbreadcrumb[]=array("url" => "index.php","name" => get_lang('AdministrationTools'));

set_time_limit(0);

if($_POST['formSent'])
{
	$formSent=$_POST['formSent'];
	$file_type=$_POST['file_type'];
	$sendMail=$_POST['sendMail']?1:0;
	

	/*if($_FILES['import_file']['size'])
	{
		$content=file($_FILES['import_file']['tmp_name']);
	}
	else
	{
		$content=array('');
	}*/

	$sessions=array();
	
	///////////////////////
	//XML/////////////////
	/////////////////////

	if($file_type == 'xml')
	{
		$xmlparser = new XmlLib_xmlParser($_FILES['import_file']['tmp_name']);
		$racine = $xmlparser->getDocument();
		$sessionNodes = $racine->children('Session');
		foreach ($sessionNodes as $sessionNode){ // foreach session
		
			$countCourses = 0;
			$countUsers = 0;
			
			list($SessionName) = $sessionNode->children('SessionName'); 
			$SessionName = $SessionName->nodeValue();
			
			list($Coach) = $sessionNode->children('Coach'); 
			if(!empty($Coach)){
				$Coach = $Coach->nodeValue();
				$sqlCoach = "SELECT user_id FROM $tbl_user WHERE username='$Coach'";
				$rsCoach = api_sql_query($sqlCoach);
				list($Coach) = (mysql_fetch_array($rsCoach));
			}
			else {
				$Coach = '';
			}
			
			list($DateStart) = $sessionNode->children('DateStart'); 
			$DateStart = $DateStart->nodeValue();
			
			list($DateEnd) = $sessionNode->children('DateEnd'); 
			$DateEnd = $DateEnd->nodeValue();
			
			$sqlSession = "INSERT IGNORE INTO $tbl_session SET
							name = '$SessionName',
							id_coach = '$Coach',
							date_start = '$DateStart',
							date_end = '$DateEnd'";
			$rsSession = api_sql_query($sqlSession, __FILE__, __LINE__);
			$update = false;
			if(!mysql_affected_rows($rsSession)){	
				$update = true;
				$sqlSession = "UPDATE $tbl_session SET								
								id_coach = '$Coach',
								date_start = '$DateStart',
								date_end = '$DateEnd'
								WHERE name = '$SessionName'";
				$rsSession = api_sql_query($sqlSession, __FILE__, __LINE__);
				
				$session_id = api_sql_query("SELECT id FROM $tbl_session WHERE name='$SessionName'",__FILE__,__LINE__);
				list($session_id) = mysql_fetch_array($session_id);				
				
				api_sql_query("DELETE FROM $tbl_session_user WHERE id_session='$session_id'",__FILE__,__LINE__);
				api_sql_query("DELETE FROM $tbl_session_course WHERE id_session='$session_id'",__FILE__,__LINE__);
				api_sql_query("DELETE FROM $tbl_session_course_user WHERE id_session='$session_id'",__FILE__,__LINE__);
			}
			else {
				$session_id = mysql_insert_id($rsSession);
			}
			
			$userNodes = $sessionNode->children('User');
			foreach ($userNodes as $userNode){				
				$sqlUser = "SELECT user_id FROM $tbl_user WHERE username='".$userNode->nodeValue()."'";
				$rsUser = api_sql_query($sqlUser);
				list($user_id) = (mysql_fetch_array($rsUser));
				if(!empty($user_id)){
					$sql = "INSERT INTO $tbl_session_user SET 
							id_user='$user_id',
							id_session = '$session_id'";
					
					$rsUser = api_sql_query($sql,__FILE__,__LINE__);
					if(mysql_affected_rows()){
						$countUsers++;
					}
				}
			}
			
			$courseNodes = $sessionNode->children('Course');
			foreach($courseNodes as $courseNode){
				
				list($CourseCode) = $courseNode->children('CourseCode'); 
				$CourseCode = $CourseCode->nodeValue();
				
				list($Coach) = $courseNode->children('Coach'); 
				if(!empty($Coach)){
					$Coach = $Coach->nodeValue();
					$sqlCoach = "SELECT user_id FROM $tbl_user WHERE username='$Coach'";
					$rsCoach = api_sql_query($sqlCoach,__FILE__,__LINE__);
					list($Coach) = (mysql_fetch_array($rsCoach));
				}
				else {
					$Coach = '';
				}
				
				$sqlCourse = "INSERT INTO $tbl_session_course SET
							  course_code = '$CourseCode',
							  id_coach='$Coach',
							  id_session='$session_id'";
				$rsCourse = api_sql_query($sqlCourse,__FILE__,__LINE__);
				if(mysql_affected_rows()){
					$countCourses++;
					
					$userNodes = $courseNode->children('User');
					$countUsersCourses = 0;
					foreach ($userNodes as $userNode){
						$sqlUser = "SELECT user_id FROM $tbl_user WHERE username='".$userNode->nodeValue()."'";
						$rsUser = api_sql_query($sqlUser);
						list($user_id) = (mysql_fetch_array($rsUser));
						$sql = "INSERT INTO $tbl_session_course_user SET 
								id_user='$user_id',
								course_code='$CourseCode',
								id_session = '$session_id'";
						$rsUsers = api_sql_query($sql,__FILE__,__LINE__);
						if(mysql_affected_rows())
							$countUsersCourses++;
					}		
					api_sql_query("UPDATE $tbl_session_course SET nbr_users='$countUsersCourses' WHERE course_code='$CourseCode'",__FILE__,__LINE__);	
				}
			}
			api_sql_query("UPDATE $tbl_session SET nbr_users='$countUsers', nbr_courses='$countCourses' WHERE id='$session_id'",__FILE__,__LINE__);
			
		}
		
	}
	
	
	
	
	/////////////////////
	// CSV /////////////
	///////////////////
	
	
	else
	{
		$content=file($_FILES['import_file']['tmp_name']);
		if(!strstr($content[0],';'))
		{
			$errorMsg=get_lang('NotCSV');
		}
		else
		{
			
			$tag_names=array();

			foreach($content as $key=>$enreg)
			{
				$enreg=explode(';',trim($enreg));

				if($key)
				{
					foreach($tag_names as $tag_key=>$tag_name)
					{
						$sessions[$key-1][$tag_name]=$enreg[$tag_key];
					}
				}
				else
				{
					foreach($enreg as $tag_name)
					{
						$tag_names[]=eregi_replace('[^a-z0-9_-]','',$tag_name);
					}

					if(!in_array('SessionName',$tag_names) || !in_array('DateStart',$tag_names) || !in_array('DateEnd',$tag_names))
					{
						$errorMsg=get_lang('NoNeededData');

						break;
					}
				}
			}
			
			foreach($sessions as $enreg) {
				$SessionName = $enreg['SessionName'];
				$DateStart = $enreg['DateStart'];
				$DateEnd = $enreg['DateEnd'];
				if(!empty($enreg['Coach'])){
					$sqlCoach = "SELECT user_id FROM $tbl_user WHERE username='".$enreg['Coach']."'";
					$rsCoach = api_sql_query($sqlCoach);
					list($Coach) = (mysql_fetch_array($rsCoach));
				}
				else {
					$Coach = '';
				}
				
				$sqlSession = "INSERT IGNORE INTO $tbl_session SET
							name = '$SessionName',
							id_coach = '$Coach',
							date_start = '$DateStart',
							date_end = '$DateEnd'";
				$rsSession = api_sql_query($sqlSession, __FILE__, __LINE__);
				$update = false;
				if(!mysql_affected_rows($rsSession)){
					$update = true;
					$sqlSession = "UPDATE $tbl_session SET								
									id_coach = '$Coach',
									date_start = '$DateStart',
									date_end = '$DateEnd'
									WHERE name = '$SessionName'";
					$rsSession = api_sql_query($sqlSession, __FILE__, __LINE__);
					
					$session_id = api_sql_query("SELECT id FROM $tbl_session WHERE name='$SessionName'",__FILE__,__LINE__);
					list($session_id) = mysql_fetch_array($session_id);				
					
					api_sql_query("DELETE FROM $tbl_session_user WHERE id_session='$session_id'",__FILE__,__LINE__);
					api_sql_query("DELETE FROM $tbl_session_course WHERE id_session='$session_id'",__FILE__,__LINE__);
					api_sql_query("DELETE FROM $tbl_session_course_user WHERE id_session='$session_id'",__FILE__,__LINE__);
				}
				else {
					$session_id = mysql_insert_id($rsSession);
				}
				
				$users = explode('|',$enreg['Users']);
				foreach ($users as $user){				
					$sqlUser = "SELECT user_id FROM $tbl_user WHERE username='".$user."'";
					$rsUser = api_sql_query($sqlUser);
					list($user_id) = (mysql_fetch_array($rsUser));
					$sql = "INSERT INTO $tbl_session_user SET 
							id_user='$user_id',
							id_session = '$session_id'";
					
					$rsUser = api_sql_query($sql,__FILE__,__LINE__);
					if(mysql_affected_rows()){
						$countUsers++;
					}
				}
				
				$courses = explode('|',$enreg['Courses']);
				foreach($courses as $course){
					$CourseCode = substr($course,0,strpos($course,'['));
					
					$Coach = strstr($course,'[');
					$Coach = substr($Coach,1,strpos($Coach,']')-1);
					
					if(!empty($Coach)){
						$sqlCoach = "SELECT user_id FROM $tbl_user WHERE username='$Coach'";
						$rsCoach = api_sql_query($sqlCoach,__FILE__,__LINE__);
						list($Coach) = (mysql_fetch_array($rsCoach));
					}
					else {
						$Coach = '';
					}
					
					$sqlCourse = "INSERT INTO $tbl_session_course SET
								  course_code = '$CourseCode',
								  id_coach='$Coach',
								  id_session='$session_id'";
					
					$rsCourse = api_sql_query($sqlCourse,__FILE__,__LINE__);
					if(mysql_affected_rows()){
						$countCourses++;
						
						$users = substr($course , strpos($course,'[',1)+1 , strpos($course,']',1));
						$users = explode('|',$enreg['Users']);
						$countUsersCourses = 0;
						foreach ($users as $user){
							$sqlUser = "SELECT user_id FROM $tbl_user WHERE username='".$user."'";
							$rsUser = api_sql_query($sqlUser);
							list($user_id) = (mysql_fetch_array($rsUser));
							$sql = "INSERT INTO $tbl_session_course_user SET 
									id_user='$user_id',
									course_code='$CourseCode',
									id_session = '$session_id'";
							$rsUsers = api_sql_query($sql,__FILE__,__LINE__);
							if(mysql_affected_rows())
								$countUsersCourses++;
						}		
						api_sql_query("UPDATE $tbl_session_course SET nbr_users='$countUsersCourses' WHERE course_code='$CourseCode'",__FILE__,__LINE__);	
					}
				}
				api_sql_query("UPDATE $tbl_session SET nbr_users='$countUsers', nbr_courses='$countCourses' WHERE id='$session_id'",__FILE__,__LINE__);
				
			}
		}
	}
	header('Location: session_list.php?action=show_message&message='.urlencode(get_lang('FileImported')));
}

Display::display_header($tool_name);

api_display_tool_title($tool_name);


?>

<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>" enctype="multipart/form-data" style="margin:0px;">
<input type="hidden" name="formSent" value="1">
<table border="0" cellpadding="5" cellspacing="0">

<?php
if(!empty($errorMsg))
{
?>

<tr>
  <td colspan="2">

<?php
	Display::display_normal_message($errorMsg); //main API
?>

  </td>
</tr>

<?php
}
?>

<tr>
  <td nowrap="nowrap"><?php echo get_lang('ImportFileLocation'); ?> :</td>
  <td><input type="file" name="import_file" size="30"></td>
</tr>
<tr>
  <td nowrap="nowrap" valign="top"><?php echo get_lang('FileType'); ?> :</td>
  <td>
	<input class="checkbox" type="radio" name="file_type" id="file_type_xml" value="xml" <?php if($formSent && $file_type == 'xml') echo 'checked="checked"'; ?>> <label for="file_type_xml">XML</label> (<a href="exemple.xml" target="_blank"><?php echo get_lang('ExampleXMLFile'); ?></a>)<br>
	<input class="checkbox" type="radio" name="file_type" id="file_type_csv"  value="csv" <?php if(!$formSent || $file_type == 'csv') echo 'checked="checked"'; ?>> <label for="file_type_csv">CSV</label> (<a href="exemple.csv" target="_blank"><?php echo get_lang('ExampleCSVFile'); ?></a>)<br>
  </td>
</tr>
<tr>
  <td>&nbsp;</td>
  <td><input type="submit" value="<?php echo get_lang('Ok'); ?>"></td>
</tr>
</table>
</form>

<font color="gray">
<p><?php echo get_lang('CSVMustLookLike').' ('.get_lang('MandatoryFields').')'; ?> :</p>

<blockquote>
<pre>
<b>SessionName</b>;Coach;<b>DateStart</b>;<b>DateEnd</b>;Users;Courses
<b>xxx</b>;xxx;<b>xxx;xxx</b>;username1|username2;course1[coach1][username1,username2,...]|course2[coach1][username1,username2,...]
</pre>
</blockquote>

<p><?php echo get_lang('XMLMustLookLike').' ('.get_lang('MandatoryFields').')'; ?> :</p>

<blockquote>
<pre>
&lt;?xml version=&quot;1.0&quot; encoding=&quot;ISO-8859-1&quot;?&gt;
&lt;Sessions&gt;
    &lt;Session&gt;
        <b>&lt;SessionName&gt;xxx&lt;/SessionName&gt;</b>
        &lt;Coach&gt;xxx&lt;/Coach&gt;
        <b>&lt;DateStart&gt;xxx&lt;/DateStart&gt;</b>
        <b>&lt;DateEnd&gt;xxx&lt;/DateEnd&gt;</b>
        &lt;User&gt;xxx&lt;/User&gt;
        &lt;User&gt;xxx&lt;/User&gt;
    	&lt;Course&gt;
    		&lt;CourseCode&gt;coursecode1&lt;/CourseCode&gt;
    		&lt;Coach&gt;coach1&lt;/Coach&gt;
		&lt;User&gt;username1&lt;/User&gt;
		&lt;User&gt;username2&lt;/User&gt;
    	&lt;/Course&gt;
    &lt;/Session&gt;
&lt;/Sessions&gt;
</pre>
</blockquote>
</font>

<?php
/*
==============================================================================
		FOOTER
==============================================================================
*/
Display::display_footer();
?>
