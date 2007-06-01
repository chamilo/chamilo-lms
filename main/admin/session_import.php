<?php // $Id: user_import.php,v 1.17 2005/06/22 08:00:31 bmol Exp $
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

$language_file = array('admin','registration');

$cidReset=true;

include('../inc/global.inc.php');

api_protect_admin_script();

include(api_get_path(LIBRARY_PATH).'/fileManage.lib.php');
include (api_get_path(LIBRARY_PATH)."/add_course.conf.php");
include_once (api_get_path(LIBRARY_PATH)."/add_course.lib.inc.php");

$formSent=0;
$errorMsg='';

$tbl_user      = Database::get_main_table(TABLE_MAIN_USER);
$tbl_course      = Database::get_main_table(TABLE_MAIN_COURSE);
$tbl_course_user      = Database::get_main_table(TABLE_MAIN_COURSE_USER);
$tbl_session      = Database::get_main_table(TABLE_MAIN_SESSION);
$tbl_session_user      = Database::get_main_table(TABLE_MAIN_SESSION_USER);
$tbl_session_course      = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
$tbl_session_course_user      = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

$tool_name=get_lang('ImportSessionListXMLCSV');

$interbreadcrumb[]=array("url" => "index.php","name" => get_lang('AdministrationTools'));

set_time_limit(0);

if($_POST['formSent'])
{
	if(isset($_FILES['import_file']['tmp_name']))
	{

		$formSent=$_POST['formSent'];
		$file_type=$_POST['file_type'];
		$sendMail=$_POST['sendMail']?1:0;


		$sessions=array();

		///////////////////////
		//XML/////////////////
		/////////////////////

		if($file_type == 'xml')
		{

			$racine = simplexml_load_file($_FILES['import_file']['tmp_name']);
			if(is_object($racine))
			{
				foreach($racine->Users->User as $userNode)
				{
					$username = $userNode->Username;
					$isCut = 0; // if the username given is too long
					if(strlen($username)>20)
					{
						$user_name_dist = $username;
						$username = substr($username,0,20);
						$isCut = 1;
					}

					$sql = "SELECT 1 FROM $tbl_user WHERE username='".addslashes($username)."'";
					$rs = api_sql_query($sql, __FILE__, __LINE__);

					if(mysql_affected_rows()==0)
					{
						if($isCut)
						{
							$errorMsg .= get_lang('UsernameTooLongWasCut').' '.get_lang('From').' '.$user_name_dist.' '.get_lang('To').' '.$username.' <br />';
						}

						$lastname = $userNode->Lastname;
						$firstname = $userNode->Firstname;
						$password = $userNode->Password;
						if(empty($password))
							$password = base64_encode(rand(1000,10000));
						$email = $userNode->Email;
						$official_code = $userNode->OfficialCode;
						$phone = $userNode->Phone;
						$status = $userNode->Status;
						switch($status)
						{
							case 'student' : $status = 5; break;
							case 'teacher' : $status = 1; break;
							default : $status = 5; $errorMsg = get_lang('StudentStatusWasGivenTo').' : '.$username.'<br />';
						}



						$sql = "INSERT INTO $tbl_user SET
								username = '".Database::escape_string($username)."',
								lastname = '".Database::escape_string($lastname)."',
								firstname = '".Database::escape_string($firstname)."',
								password = '".($userPasswordCrypted==true ? md5($password) : $password)."',
								email = '".Database::escape_string($email)."',
								official_code = '".Database::escape_string($official_code)."',
								phone = '".Database::escape_string($phone)."',
								status = '".Database::escape_string($status)."'";

						api_sql_query($sql, __FILE__, __LINE__);

						if(mysql_affected_rows()>0 && $sendMail)
						{
							$emailto='"'.$firstname.' '.$lastname.'" <'.$email.'>';
							$emailsubject='['.get_setting('siteName').'] '.get_lang('YourReg').' '.get_setting('siteName');
							$emailbody="[NOTE:] Ceci est un e-mail automatique, veuillez ne pas y répondre.\n\n".get_lang('langDear')." $firstname $lastname,\n\n".get_lang('langYouAreReg')." ". get_setting('siteName') ." ".get_lang('langSettings')." $username\n". get_lang('langPass')." : $password\n\n".get_lang('langAddress') ." ". get_lang('langIs') ." ". $serverAddress ."\n\nVous recevrez prochainement un e-mail de votre coach responsable. Nous vous invitons à bien lire ses recommandations.\n\n". get_lang('langProblem'). "\n\n". get_lang('langFormula');
							//#287 modifiée par Stéphane DEBIEVE - FOREM
							$emailheaders='From: '.get_setting('administratorName').' '.get_setting('administratorSurname').' <'.get_setting('emailAdministrator').">\n";
							$emailheaders.='Reply-To: '.get_setting('emailAdministrator');

							@api_send_mail($emailto,$emailsubject,$emailbody,$emailheaders);
						}
					}

				}
				foreach($racine->Courses->Course as $courseNode)
				{
					$course_code = $courseNode->CourseCode;
					$title = $courseNode->CourseTitle;
					$description = $courseNode->CourseDescription;
					$language = $courseNode->CourseLanguage;
					$username = $courseNode->CourseTeacher;

					$sql = "SELECT user_id, lastname, firstname FROM $tbl_user WHERE username='$username'";
					$rs = api_sql_query($sql, __FILE__, __LINE__);

					list($user_id, $lastname, $firstname) = mysql_fetch_array($rs);
					$keys = define_course_keys($course_code, "", $dbNamePrefix);

					if (sizeof($keys))
					{

						$currentCourseCode = $keys['visual_code'];
						$currentCourseId = $keys["currentCourseId"];
						if(empty($currentCourseCode))
							$currentCourseCode = $currentCourseId;
						$currentCourseDbName = $keys["currentCourseDbName"];
						$currentCourseRepository = $keys["currentCourseRepository"];

						if($currentCourseId == strtoupper($course_code))
						{
							if (empty ($title))
							{
								$title = $keys["currentCourseCode"];
							}
							prepare_course_repository($currentCourseRepository, $currentCourseId);
							update_Db_course($currentCourseDbName);
							fill_course_repository($currentCourseRepository);
							fill_Db_course($currentCourseDbName, $currentCourseRepository, 'french');
							//register_course($currentCourseId, $currentCourseCode, $currentCourseRepository, $currentCourseDbName, "$lastname $firstname", $course['unit_code'], addslashes($course['FR']['title']), $language, $user_id);
							$sql = "INSERT INTO ".$tbl_course." SET
										code = '".$currentCourseId."',
										db_name = '".$currentCourseDbName."',
										directory = '".$currentCourseRepository."',
										course_language = '".$language."',
										title = '".$title."',
										description = '".lang2db($description)."',
										category_code = '',
										visibility = '".$defaultVisibilityForANewCourse."',
										show_score = '',
										disk_quota = NULL,
										creation_date = now(),
										expiration_date = NULL,
										last_edit = now(),
										last_visit = NULL,
										tutor_name = '".$lastname." ".$firstname."',
										visual_code = '".$currentCourseCode."'";

							api_sql_query($sql, __FILE__, __LINE__);

							$sql = "INSERT INTO ".$tbl_course_user." SET
										course_code = '".$currentCourseId."',
										user_id = '".$user_id."',
										status = '1',
										role = '".lang2db('Professor')."',
										tutor_id='1',
										sort='". ($sort +1)."',
										user_course_cat='0'";

							api_sql_query($sql, __FILE__, __LINE__);
						}

					}
				}
				foreach ($racine->Session as $sessionNode){ // foreach session

					$countCourses = 0;
					$countUsers = 0;

					$SessionName = $sessionNode->SessionName;
					$Coach = $sessionNode->Coach;

					if(!empty($Coach)){
						$sqlCoach = "SELECT user_id FROM $tbl_user WHERE username='$Coach'";
						$rsCoach = api_sql_query($sqlCoach);
						list($CoachId) = (mysql_fetch_array($rsCoach));
						if(empty($CoachId))
						{
							$errorMsg .= get_lang('UserDoesNotExist').' : '.$Coach.'<br />';
						}
					}

					$DateStart = $sessionNode->DateStart;
					if(!empty($DateStart))
					{
						list($YearStart,$MonthStart, $DayStart) = explode('-',$DateStart);
						if(empty($YearStart) || empty($MonthStart) || empty($DayStart))
						{
							$errorMsg .= get_lang('WrongDate').' : '.$DateStart.'<br />';
							break;
						}
						else
						{
							$timeStart = mktime(0,0,0,$MonthStart,$DayStart,$YearStart);
						}

						$DateEnd = $sessionNode->DateEnd;
						if(!empty($DateStart))
						{
							list($YearEnd,$MonthEnd, $DayEnd) = explode('-',$DateEnd);
							if(empty($YearEnd) || empty($MonthEnd) || empty($DayEnd))
							{
								$errorMsg .= get_lang('WrongDate').' : '.$DateEnd.'<br />';
								break;
							}
							else
							{
								$timeEnd = mktime(0,0,0,$MonthEnd,$DayEnd,$YearEnd);
							}
						}
						if($timeEnd - $timeStart < 0)
						{
							$errorMsg .= get_lang('DateStartMoreThanDateEnd').' : '.$DateEnd.'<br />';
						}
					}


					// verify that session doesn't exist
					while(!$uniqueName)
					{
						if($i>1)
							$suffix = ' - '.$i;
						$sql = 'SELECT 1 FROM '.$tbl_session.' WHERE name="'.Database::escape_string($SessionName.$suffix).'"';
						$rs = api_sql_query($sql, __FILE__, __LINE__);

						if(mysql_result($rs,0,0))
						{
							$i++;
						}
						else
						{
							$uniqueName = true;
							$SessionName .= $suffix;
						}
					}

					$sqlSession = "INSERT IGNORE INTO $tbl_session SET
									name = '".Database::escape_string($SessionName)."',
									id_coach = '$CoachId',
									date_start = '$DateStart',
									date_end = '$DateEnd'";
					$rsSession = api_sql_query($sqlSession, __FILE__, __LINE__);
					$session_id = mysql_insert_id();

					foreach ($sessionNode->User as $userNode){
						$username = substr($userNode->nodeValue(),0,20);
						$sqlUser = "SELECT user_id FROM $tbl_user WHERE username='".Database::escape_string($username)."'";
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

					foreach($sessionNode->Course as $courseNode){

						$CourseCode = $courseNode->CourseCode;

						// verify that the course pointed by the course code node exists
						$sql = 'SELECT 1 FROM '.$tbl_course.' WHERE code="'.mysql_escape_string($CourseCode).'"';
						$rs = api_sql_query($sql, __FILE__, __LINE__);
						if(mysql_num_rows($rs)>0)
						{ // if the course exists we continue

							$Coach = substr($courseNode->Coach,0,20);
							if(!empty($Coach)){
								$sqlCoach = "SELECT user_id FROM $tbl_user WHERE username='$Coach'";
								$rsCoach = api_sql_query($sqlCoach,__FILE__,__LINE__);
								list($CoachId) = (mysql_fetch_array($rsCoach));
								if(empty($CoachId))
								{
									$errorMsg .= get_lang('UserDoesNotExist').' : '.$Coach.'<br />';
								}
							}
							else {
								$Coach = '';
							}

							$sqlCourse = "INSERT INTO $tbl_session_course SET
										  course_code = '$CourseCode',
										  id_coach='$CoachId',
										  id_session='$session_id'";
							$rsCourse = api_sql_query($sqlCourse,__FILE__,__LINE__);
							if(mysql_affected_rows()){
								$countCourses++;

								$countUsersCourses = 0;
								foreach ($courseNode->User as $userNode){
									$username = substr($userNode,0,20);
									$sqlUser = "SELECT user_id FROM $tbl_user WHERE username='".$username."'";
									$rsUser = api_sql_query($sqlUser);
									list($user_id) = (mysql_fetch_array($rsUser));
									if(!empty($user_id))
									{
										$sql = "INSERT IGNORE INTO $tbl_session_user SET
											id_user='$user_id',
											id_session = '$session_id'";

										if(mysql_affected_rows())
											$countUsers++;
										$rsUser = api_sql_query($sql,__FILE__,__LINE__);

										$sql = "INSERT IGNORE INTO $tbl_session_course_user SET
												id_user='$user_id',
												course_code='$CourseCode',
												id_session = '$session_id'";
										$rsUsers = api_sql_query($sql,__FILE__,__LINE__);
										if(mysql_affected_rows())
											$countUsersCourses++;
									}
									else
									{
										$errorMsg .= get_lang('UserDoesNotExist').' : '.$username.'<br />';
									}
								}
								api_sql_query("UPDATE $tbl_session_course SET nbr_users='$countUsersCourses' WHERE course_code='$CourseCode'",__FILE__,__LINE__);
							}
						}
						else
						{ // if the course does not exists
							$errorMsg .= get_lang('CourseDoesNotExist').' : '.$CourseCode.'<br />';
						}
					}
					api_sql_query("UPDATE $tbl_session SET nbr_users='$countUsers', nbr_courses='$countCourses' WHERE id='$session_id'",__FILE__,__LINE__);

				}

			}
			else
			{
				$errorMsg .= get_lang('XMLNotValid');
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
		if(!empty($errorMsg))
		{
			$errorMsg = get_lang('ButProblemsOccured').' :<br />'.$errorMsg;
		}
		header('Location: session_list.php?action=show_message&message='.urlencode(get_lang('FileImported').' '.$errorMsg));
	}
	else
	{
	$errorMsg = get_lang('NoInputFile');
	}
}


Display::display_header($tool_name);

api_display_tool_title($tool_name);


?>

<form method="post" action="<?php echo api_get_self(); ?>" enctype="multipart/form-data" style="margin:0px;">
<input type="hidden" name="formSent" value="1">
<table border="0" cellpadding="5" cellspacing="0">

<?php
if(!empty($errorMsg))
{
?>

<tr>
  <td colspan="2">

<?php
	Display::display_normal_message($errorMsg,false); //main API
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
	<input class="checkbox" type="radio" name="file_type" id="file_type_xml" value="xml" checked="checked" /> <label for="file_type_xml">XML</label> (<a href="exemple.xml" target="_blank"><?php echo get_lang('ExampleXMLFile'); ?></a>)<br>
	<input class="checkbox" type="radio" name="file_type" id="file_type_csv"  value="csv" <?php if($formSent && $file_type == 'csv') echo 'checked="checked"'; ?>> <label for="file_type_csv">CSV</label> (<a href="exempleSession.csv" target="_blank"><?php echo get_lang('ExampleCSVFile'); ?></a>)<br>
  </td>
</tr>
<tr>
  <td nowrap="nowrap" valign="top"><?php echo get_lang('SendMailToUsers'); ?> :</td>
  <td>
	<input class="checkbox" type="checkbox" name="sendMail" id="sendMail" value="true" />
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
    &lt;Users&gt;
        &lt;User&gt;
            &lt;Username&gt;<b>username1</b>&lt;/Username&gt;
            &lt;Lastname&gt;xxx&lt;/Lastname&gt;
            &lt;Firstname&gt;xxx&lt;/Firstname&gt;
            &lt;Password&gt;xxx&lt;/Password&gt;
            &lt;Email&gt;xxx@xx.xx&lt;/Email&gt;
            &lt;OfficialCode&gt;xxx&lt;/OfficialCode&gt;
            &lt;Phone&gt;xxx&lt;/Phone&gt;
            &lt;Status&gt;student|teacher&lt;/Status&gt;
        &lt;/User&gt;
    &lt;/Users&gt;
    &lt;Courses&gt;
        &lt;Course&gt;
            &lt;CourseCode&gt;<b>xxx</b>&lt;/CourseCode&gt;
            &lt;CourseTeacher&gt;xxx&lt;/CourseTeacher&gt;
            &lt;CourseLanguage&gt;xxx&lt;/CourseLanguage&gt;
            &lt;CourseTitle&gt;xxx&lt;/CourseTitle&gt;
            &lt;CourseDescription&gt;xxx&lt;/CourseDescription&gt;
        &lt;/Course&gt;
    &lt;/Courses&gt;
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
