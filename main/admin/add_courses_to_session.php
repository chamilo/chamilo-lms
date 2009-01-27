<?php
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

// resetting the course id
$cidReset=true;

// including some necessary dokeos files
require('../inc/global.inc.php');

require_once ('../inc/lib/xajax/xajax.inc.php');
$xajax = new xajax();
//$xajax->debugOn();
$xajax -> registerFunction ('search_courses');

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;

// Access restrictions
api_protect_admin_script(true);

// setting breadcrumbs
$interbreadcrumb[] = array('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array('url' => 'session_list.php','name' => get_lang('SessionList'));

// Database Table Definitions
$tbl_session_rel_course_rel_user	= Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
$tbl_session						= Database::get_main_table(TABLE_MAIN_SESSION);
$tbl_session_rel_user				= Database::get_main_table(TABLE_MAIN_SESSION_USER);
$tbl_session_rel_course				= Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
$tbl_course							= Database::get_main_table(TABLE_MAIN_COURSE);

// setting the name of the tool
$tool_name= get_lang('SubscribeCoursesToSession');

$id_session=intval($_GET['id_session']);

$add_type = 'unique';
if(isset($_GET['add_type']) && $_GET['add_type']!=''){
	$add_type = $_GET['add_type'];
}

if(!api_is_platform_admin())
{
	$sql = 'SELECT session_admin_id FROM '.Database :: get_main_table(TABLE_MAIN_SESSION).' WHERE id='.$id_session;
	$rs = api_sql_query($sql,__FILE__,__LINE__);
	if(mysql_result($rs,0,0)!=$_user['user_id'])
	{
		api_not_allowed(true);
	}
}



function search_courses($needle)
{
	global $tbl_course, $tbl_session_rel_course, $id_session;
	
	$xajax_response = new XajaxResponse();
	$return = '';
	if(!empty($needle))
	{
		// xajax send utf8 datas... datas in db can be non-utf8 datas
		$charset = api_get_setting('platform_charset');
		$needle = mb_convert_encoding($needle, $charset, 'utf-8');
		
		// search users where username or firstname or lastname begins likes $needle
		$sql = 'SELECT course.code, course.visual_code, course.title, session_rel_course.id_session
				FROM '.$tbl_course.' course
				LEFT JOIN '.$tbl_session_rel_course.' session_rel_course
					ON course.code = session_rel_course.course_code
					AND session_rel_course.id_session = '.intval($id_session).'
				WHERE course.visual_code LIKE "'.$needle.'%"
				OR course.title LIKE "'.$needle.'%"';
				
		$rs = api_sql_query($sql, __FILE__, __LINE__);
		$course_list = array();
		while($course = Database :: fetch_array($rs))
		{	
			$course_list[] = $course['code'];					
			$return .= '<a href="#" onclick="add_course_to_session(\''.$course['code'].'\',\''.$course['title'].' ('.$course['visual_code'].')'.'\')">'.$course['title'].' ('.$course['visual_code'].')</a><br />';
		}
	}
	$_SESSION['course_list'] = $course_list;
	$xajax_response -> addAssign('ajax_list_courses','innerHTML',utf8_encode($return));
	return $xajax_response;
}
$xajax -> processRequests();

$htmlHeadXtra[] = $xajax->getJavascript('../inc/lib/xajax/');
$htmlHeadXtra[] = '
<script type="text/javascript">
function add_course_to_session (code, content) {

	document.getElementById("course_to_add").value = "";
	document.getElementById("ajax_list_courses").innerHTML = "";
	
	destination = document.getElementById("destination");
	
	for (i=0;i<destination.length;i++) {
		if(destination.options[i].text == content) {
				return false;
		} 
	}			
			
	destination.options[destination.length] = new Option(content,code);	
	
	destination.selectedIndex = -1;
	sortOptions(destination.options);	
}
function remove_item(origin)
{
	for(var i = 0 ; i<origin.options.length ; i++) {
		if(origin.options[i].selected) {
			origin.options[i]=null;
			i = i-1;
		}
	}
}
</script>';


$formSent=0;
$errorMsg=$firstLetterCourse=$firstLetterSession='';
$CourseList=$SessionList=array();
$courses=$sessions=array();
$noPHP_SELF=true;




if($_POST['formSent'])
{
		
	$formSent=$_POST['formSent'];
	$firstLetterCourse=$_POST['firstLetterCourse'];
	$firstLetterSession=$_POST['firstLetterSession'];
	$CourseList=$_POST['SessionCoursesList'];
	if(!is_array($CourseList))
	{
		$CourseList=array();
	}
	$nbr_courses=0;

	$id_coach = api_sql_query("SELECT id_coach FROM $tbl_session WHERE id=$id_session");
	$id_coach = mysql_fetch_array($id_coach);
	$id_coach = $id_coach[0];

	$rs = api_sql_query("SELECT course_code FROM $tbl_session_rel_course WHERE id_session=$id_session");
	$existingCourses = api_store_result($rs);

	$sql="SELECT id_user
		FROM $tbl_session_rel_user
		WHERE id_session = $id_session";
	$result=api_sql_query($sql,__FILE__,__LINE__);

	$UserList=api_store_result($result);


	foreach($CourseList as $enreg_course)
	{
		$exists = false;
		foreach($existingCourses as $existingCourse)
		{
			if($enreg_course == $existingCourse['course_code'])
			{
				$exists=true;
			}
		}
		if(!$exists)
		{
			api_sql_query("INSERT INTO $tbl_session_rel_course(id_session,course_code, id_coach) VALUES('$id_session','$enreg_course','$id_coach')",__FILE__,__LINE__);
			
			//We add in the existing courses table the current course, to not try to add another time the current course
			$existingCourses[]=array('course_code'=>$enreg_course);

			$nbr_users=0;
			foreach($UserList as $enreg_user)
			{
				$enreg_user = $enreg_user['id_user'];
				api_sql_query("INSERT IGNORE INTO $tbl_session_rel_course_rel_user(id_session,course_code,id_user) VALUES('$id_session','$enreg_course','$enreg_user')",__FILE__,__LINE__);

				if(mysql_affected_rows())
				{
					$nbr_users++;
				}
			}
			api_sql_query("UPDATE $tbl_session_rel_course SET nbr_users=$nbr_users WHERE id_session='$id_session' AND course_code='$enreg_course'",__FILE__,__LINE__);
		}

	}

	foreach($existingCourses as $existingCourse) {
		if(!in_array($existingCourse['course_code'], $CourseList)){
			api_sql_query("DELETE FROM $tbl_session_rel_course WHERE course_code='".$existingCourse['course_code']."' AND id_session=$id_session");
			api_sql_query("DELETE FROM $tbl_session_rel_course_rel_user WHERE course_code='".$existingCourse['course_code']."' AND id_session=$id_session");

		}
	}
	$nbr_courses=count($CourseList);
	api_sql_query("UPDATE $tbl_session SET nbr_courses=$nbr_courses WHERE id='$id_session'",__FILE__,__LINE__);

	if(isset($_GET['add']))
		header('Location: add_users_to_session.php?id_session='.$id_session.'&add=true');
	else
		header('Location: resume_session.php?id_session='.$id_session);
		//header('Location: '.$_GET['page'].'?id_session='.$id_session);




}

Display::display_header($tool_name);

api_display_tool_title($tool_name);
/*$sql = 'SELECT COUNT(1) FROM '.$tbl_course;
$rs = api_sql_query($sql, __FILE__, __LINE__);
$count_courses = mysql_result($rs, 0, 0);*/

$ajax_search = $add_type == 'unique' ? true : false;
$nosessionCourses = $sessionCourses = array();
if($ajax_search)
{
	
	$sql="SELECT code, title, visual_code, id_session
			FROM $tbl_course course
			INNER JOIN $tbl_session_rel_course session_rel_course
				ON course.code = session_rel_course.course_code
				AND session_rel_course.id_session = ".intval($id_session)."
			ORDER BY ".(sizeof($courses)?"(code IN(".implode(',',$courses).")) DESC,":"")." title";			
	$result=api_sql_query($sql,__FILE__,__LINE__);	
	$Courses=api_store_result($result);
	
	
	foreach($Courses as $course)
	{
		$sessionCourses[$course['code']] = $course ;
	}
}
else
{
	$sql="SELECT code, title, visual_code, id_session
			FROM $tbl_course course
			LEFT JOIN $tbl_session_rel_course session_rel_course
				ON course.code = session_rel_course.course_code
				AND session_rel_course.id_session = ".intval($id_session)."
			ORDER BY ".(sizeof($courses)?"(code IN(".implode(',',$courses).")) DESC,":"")." title";			

	$result=api_sql_query($sql,__FILE__,__LINE__);	
	$Courses=api_store_result($result);
	foreach($Courses as $course)
	{
		if($course['id_session'] == $id_session)
		{
			$sessionCourses[$course['code']] = $course ;
		}
		else
		{
			$nosessionCourses[$course['code']] = $course ;
		}
	}	
}		

unset($Courses);

if($add_type == 'multiple'){
	$link_add_type_unique = '<a href="'.api_get_self().'?id_session='.$id_session.'&add='.$_GET['add'].'&add_type=unique">'.get_lang('SessionAddTypeUnique').'</a>';
	$link_add_type_multiple = get_lang('SessionAddTypeMultiple');
}
else{
	$link_add_type_unique = get_lang('SessionAddTypeUnique');
	$link_add_type_multiple = '<a href="'.api_get_self().'?id_session='.$id_session.'&add='.$_GET['add'].'&add_type=multiple">'.get_lang('SessionAddTypeMultiple').'</a>';
}

?>

<div style="text-align: left;">
	<?php echo $link_add_type_unique ?>&nbsp;|&nbsp;<?php echo $link_add_type_multiple ?>
</div>
<br><br>

<form name="formulaire" method="post" action="<?php echo api_get_self(); ?>?page=<?php echo $_GET['page'] ?>&id_session=<?php echo $id_session; ?><?php if(!empty($_GET['add'])) echo '&add=true' ; ?>" style="margin:0px;">
<input type="hidden" name="formSent" value="1" />

<?php
if(!empty($errorMsg))
{
	Display::display_normal_message($errorMsg); //main API
}
?>

<table border="0" cellpadding="5" cellspacing="0" width="100%" align="center">
<tr>
  <td width="45%" align="center"><b><?php echo get_lang('CourseListInPlatform') ?> :</b></td>

  <td width="10%">&nbsp;</td>
  <td align="center" width="45%"><b><?php echo get_lang('CourseListInSession') ?> :</b></td>
</tr>
<tr>
  <td width="45%" align="center">

<?php
if($ajax_search){
	?>
	<input type="text" id="course_to_add" onkeyup="xajax_search_courses(this.value)" />
	<div id="ajax_list_courses"></div>
	<?php
}
else
{
	?> <select id="origin" name="NoSessionCoursesList[]" multiple="multiple" size="20" style="width:300px;"> <?php
	foreach($nosessionCourses as $enreg)
	{
		?>
		<option value="<?php echo $enreg['code']; ?>" <?php if(in_array($enreg['code'],$CourseList)) echo 'selected="selected"'; ?>><?php echo $enreg['title'].' ('.$enreg['visual_code'].')'; ?></option>
		<?php
	}
	?>  </select> <?php
}	
unset($nosessionCourses);
?>

  </select></td>
  <td width="10%" valign="middle" align="center">
  <?php
  if($ajax_search)
  {
  ?>
  	<input type="button" onclick="remove_item(document.getElementById('destination'))" value="<<" />
  <?php
  }
  else
  {
  ?>
	<input type="button" onclick="moveItem(document.getElementById('origin'), document.getElementById('destination'))" value=">>" />
	<br /><br />
	<input type="button" onclick="moveItem(document.getElementById('destination'), document.getElementById('origin'))" value="<<" />
  <?php 
  } 
  ?>
	<br /><br /><br /><br /><br /><br />
	<?php
	if(isset($_GET['add']))
	{
		echo '<input type="button" value="'.get_lang('NextStep').'" onclick="valide()" />';
	}
	else
	{
		echo '<input type="button" value="'.get_lang('Ok').'" onclick="valide()" />';
	}
	?>
  </td>
  <td width="45%" align="center"><select id='destination' name="SessionCoursesList[]" multiple="multiple" size="20" style="width:300px;">

<?php
foreach($sessionCourses as $enreg)
{	
?>	
	<option value="<?php echo $enreg['code']; ?>"><?php echo $enreg['title'].' ('.$enreg['visual_code'].')'; ?></option>

<?php 
}
unset($sessionCourses);
?>

  </select></td>
</tr>
</table>

</form>
<script type="text/javascript">
<!--
function moveItem(origin , destination){

	for(var i = 0 ; i<origin.options.length ; i++) {
		if(origin.options[i].selected) {
			destination.options[destination.length] = new Option(origin.options[i].text,origin.options[i].value);
			origin.options[i]=null;
			i = i-1;
		}
	}
	destination.selectedIndex = -1;
	sortOptions(destination.options);
	

}

function sortOptions(options) {

	newOptions = new Array();
	
	for (i = 0 ; i<options.length ; i++) {
		newOptions[i] = options[i];							
	}

	newOptions = newOptions.sort(mysort);
	options.length = 0;

	for(i = 0 ; i < newOptions.length ; i++){		
		options[i] = newOptions[i];			
	}
	
}

function mysort(a, b){
	if(a.text.toLowerCase() > b.text.toLowerCase()){
		return 1;
	}
	if(a.text.toLowerCase() < b.text.toLowerCase()){
		return -1;
	}
	return 0;
}

function valide(){
	var options = document.getElementById('destination').options;
	for (i = 0 ; i<options.length ; i++)
		options[i].selected = true;

	document.forms.formulaire.submit();
}
-->

</script>
<?php
/*
==============================================================================
		FOOTER
==============================================================================
*/
Display::display_footer();
?>
