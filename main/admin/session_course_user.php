<?php //$id: $
/* For licensing terms, see /license.txt */
/**
*	@package chamilo.admin
*/

// name of the language file that needs to be included
$language_file='admin';

// resetting the course id
$cidReset=true;

// including some necessary dokeos files
require_once '../inc/global.inc.php';

// including additonal libraries
require_once api_get_path(LIBRARY_PATH).'sessionmanager.lib.php';

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;

// Access restrictions
api_protect_admin_script(true);

// setting breadcrumbs
$interbreadcrumb[] = array('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array('url' => 'session_list.php','name' => get_lang('SessionList'));
$interbreadcrumb[] = array('url' => 'resume_session.php?id_session='.Security::remove_XSS($_GET['id_session']),'name' => get_lang('SessionOverview'));

// Database Table Definitions
$tbl_session_rel_course_rel_user	= Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
$tbl_session						= Database::get_main_table(TABLE_MAIN_SESSION);
$tbl_session_rel_user				= Database::get_main_table(TABLE_MAIN_SESSION_USER);
$tbl_session_rel_course				= Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
$tbl_course							= Database::get_main_table(TABLE_MAIN_COURSE);

// setting the name of the tool
$tool_name= get_lang('EditSessionCoursesByUser');
$id_session=intval($_GET['id_session']);
$id_user=intval($_GET['id_user']);

if (empty($id_user) || empty($id_session)) {
	header('Location: resume_session.php?id_session='.$id_session);
}

if (!api_is_platform_admin()) {
	$sql = 'SELECT session_admin_id FROM '.Database :: get_main_table(TABLE_MAIN_SESSION).' WHERE id='.$id_session;
	$rs = Database::query($sql);
	if (Database::result($rs,0,0)!=$_user['user_id']) {
		api_not_allowed(true);
	}
}

$formSent=0;
$errorMsg=$firstLetterCourse=$firstLetterSession='';
$CourseList=$SessionList=array();
$courses=$sessions=array();
$noPHP_SELF=true;

if ($_POST['formSent']) {
	$formSent			= $_POST['formSent'];
	$CourseList			= $_POST['SessionCoursesList'];

	if (!is_array($CourseList)) {
		$CourseList=array();
	}

	$sql="SELECT distinct code
			FROM $tbl_course course LEFT JOIN $tbl_session_rel_course session_rel_course
			ON course.code = session_rel_course.course_code inner join $tbl_session_rel_course_rel_user as srcru
			ON (srcru.id_session =  session_rel_course.id_session)
			WHERE id_user = $id_user and session_rel_course.id_session = $id_session";

	$rs = Database::query($sql);
	$existingCourses = Database::store_result($rs);
	if (count($CourseList) == count($existingCourses)) {
		header('Location: session_course_user.php?id_session='.$id_session.'&id_user='.$id_user.'&msg='.get_lang('MaybeYouWantToDeleteThisUserFromSession'));
		exit;
	}
	foreach($CourseList as $enreg_course) {
		$exists = false;
		foreach($existingCourses as $existingCourse) {
			if($enreg_course == $existingCourse['course_code']) {
				$exists=true;
			}
		}
		if(!$exists) {
			$enreg_course = Database::escape_string($enreg_course);
			$sql_delete = "DELETE FROM $tbl_session_rel_course_rel_user
							WHERE id_user='".$id_user."'  AND course_code='".$enreg_course."' AND id_session=$id_session";
			Database::query($sql_delete);
			if(Database::affected_rows()) {
				//update session rel course table
				$sql_update  = "UPDATE $tbl_session_rel_course SET nbr_users= nbr_users - 1 WHERE id_session='$id_session' AND course_code='$enreg_course'";
				Database::query($sql_update);
			}
		}
	}
	foreach($existingCourses as $existingCourse) {
		//$sql_insert_rel_course= "INSERT INTO $tbl_session_rel_course(id_session,course_code, id_coach) VALUES('$id_session','$enreg_course','$id_coach')";
		if(!in_array($existingCourse['code'], $CourseList)){
			$existingCourse = Database::escape_string($existingCourse['code']);
			$sql_insert = "INSERT IGNORE INTO $tbl_session_rel_course_rel_user(id_session,course_code,id_user) VALUES('$id_session','$existingCourse','$id_user')";
			Database::query($sql_insert);
			if(Database::affected_rows()) {
				//update session rel course table
				$sql_update  = "UPDATE $tbl_session_rel_course SET nbr_users= nbr_users + 1 WHERE id_session='$id_session' AND course_code='$existingCourse'";
				Database::query($sql_update);
			}

		}
	}
	//header('Location: session_course_user.php?id_user='.$id_user.'&id_session='.$id_session);
	header('Location: session_course_user.php?id_session='.$id_session.'&id_user='.$id_user.'&msg='.get_lang('CoursesUpdated'));
	exit;
}

// display the dokeos header
Display::display_header($tool_name);

if (!empty($_GET['msg'])) {
    Display::display_normal_message(urldecode($_GET['msg']));
}

// the form header
$session_info = SessionManager::fetch($id_session);
echo '<div class="row"><div class="form_header">'.$tool_name.' ('.$session_info['name'].')</div></div><br />';
$nosessionCourses = $sessionCourses = array();

/*$sql="SELECT distinct code, title, visual_code, session_rel_course.id_session
FROM $tbl_course course LEFT JOIN $tbl_session_rel_course session_rel_course
ON course.code = session_rel_course.course_code inner join $tbl_session_rel_course_rel_user as srcru
ON (srcru.id_session =  session_rel_course.id_session)
WHERE id_user = $id_user and session_rel_course.id_session = $id_session";
*/
// actual user
$sql="SELECT code, title, visual_code, srcru.id_session " .
			"FROM $tbl_course course inner JOIN $tbl_session_rel_course_rel_user   as srcru  " .
			"ON course.code = srcru.course_code  WHERE srcru.id_user = $id_user AND id_session = $id_session";

//all
$sql_all="SELECT code, title, visual_code, src.id_session " .
			"FROM $tbl_course course inner JOIN $tbl_session_rel_course  as src  " .
			"ON course.code = src.course_code AND id_session = $id_session";


/*
	echo $sql="SELECT code, title, visual_code, id_session
			FROM $tbl_course course
			LEFT JOIN $tbl_session_rel_course session_rel_course
				ON course.code = session_rel_course.course_code
				AND session_rel_course.id_session = ".intval($id_session)."
			ORDER BY ".(sizeof($courses)?"(code IN(".implode(',',$courses).")) DESC,":"")." title";
	*/
/*global $_configuration;
if ($_configuration['multiple_access_urls']) {
	$tbl_course_rel_access_url= Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
	$access_url_id = api_get_current_access_url_id();
	if ($access_url_id != -1){
		$sql="SELECT code, title, visual_code, id_session
			FROM $tbl_course course
			LEFT JOIN $tbl_session_rel_course session_rel_course
				ON course.code = session_rel_course.course_code
				AND session_rel_course.id_session = ".intval($id_session)."
			INNER JOIN $tbl_course_rel_access_url url_course ON (url_course.course_code=course.code)
			WHERE access_url_id = $access_url_id
			ORDER BY ".(sizeof($courses)?"(code IN(".implode(',',$courses).")) DESC,":"")." title";
	}
}*/

$result=Database::query($sql);
$Courses=Database::store_result($result);

$result=Database::query($sql_all);
$CoursesAll=Database::store_result($result);

$course_temp = array();
foreach($Courses as $course) {
	$course_temp[] = $course['code'];
}
foreach($CoursesAll as $course) {
	if (in_array($course['code'], $course_temp)) {
		$nosessionCourses[$course['code']] = $course ;
	} else {
		$sessionCourses[$course['code']] = $course ;
	}
}

unset($Courses);
?>

<form name="formulaire" method="post" action="<?php echo api_get_self(); ?>?page=<?php echo Security::remove_XSS($_GET['page']) ?>&id_user=<?php echo $id_user; ?>&id_session=<?php echo $id_session; ?>" style="margin:0px;">
<input type="hidden" name="formSent" value="1" />

<?php
if(!empty($errorMsg)) {
	Display::display_normal_message($errorMsg); //main API
}
?>
<table border="0" cellpadding="5" cellspacing="0" width="100%" align="center">
<tr>
  <td width="45%" align="center"><b><?php echo get_lang('CurrentCourses') ?> :</b></td>
  <td width="10%">&nbsp;</td>
  <td align="center" width="45%"><b><?php echo get_lang('CoursesToAvoid') ?> :</b></td>
</tr>
</td>
<tr>
  <td width="45%" align="center">
	<div id="ajax_list_courses_multiple">
	<select id="origin" name="NoSessionCoursesList[]" multiple="multiple" size="20" style="width:320px;"> <?php
	foreach($nosessionCourses as $enreg)
	{
		?>
		<option value="<?php echo $enreg['code']; ?>" <?php echo 'title="'.htmlspecialchars($enreg['title'].' ('.$enreg['visual_code'].')',ENT_QUOTES).'"'; if(in_array($enreg['code'],$CourseList)) echo 'selected="selected"'; ?>><?php echo $enreg['title'].' ('.$enreg['visual_code'].')'; ?></option>
		<?php
	}
	?>  </select></div> <?php
unset($nosessionCourses);
?>

  </select></td>
  <td width="10%" valign="middle" align="center">
  	<button class="arrowr" type="button" onclick="moveItem(document.getElementById('origin'), document.getElementById('destination'))" onclick="moveItem(document.getElementById('origin'), document.getElementById('destination'))"></button>
	<br /><br />
	<button class="arrowl" type="button" onclick="moveItem(document.getElementById('destination'), document.getElementById('origin'))" onclick="moveItem(document.getElementById('destination'), document.getElementById('origin'))"></button>
	<br /><br /><br /><br /><br /><br />
	<?php
	echo '<button class="save" type="button" value="" onclick="valide()" >'.get_lang('EditSessionCourses').'</button>';
	?>
  </td>
  <td width="45%" align="center"><select id='destination' name="SessionCoursesList[]" multiple="multiple" size="20" style="width:320px;">
<?php
foreach($sessionCourses as $enreg)
{
?>
	<option value="<?php echo $enreg['code']; ?>" title="<?php echo htmlspecialchars($enreg['title'].' ('.$enreg['visual_code'].')',ENT_QUOTES); ?>"><?php echo $enreg['title'].' ('.$enreg['visual_code'].')'; ?></option>

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