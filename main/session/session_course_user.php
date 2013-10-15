<?php
/* For licensing terms, see /license.txt */
/**
*	@package chamilo.admin
*/

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;

$id_session = intval($_GET['id_session']);
SessionManager::protect_session_edit($id_session);

// setting breadcrumbs
$interbreadcrumb[] = array('url' => 'index.php', 'name' => get_lang('Sessions'));
$interbreadcrumb[] = array('url' => 'session_list.php','name' => get_lang('SessionList'));
$interbreadcrumb[] = array('url' => 'resume_session.php?id_session='.Security::remove_XSS($_GET['id_session']),'name' => get_lang('SessionOverview'));

// Database Table Definitions
$tbl_session_rel_course_rel_user	= Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
$tbl_session						= Database::get_main_table(TABLE_MAIN_SESSION);
$tbl_session_rel_user				= Database::get_main_table(TABLE_MAIN_SESSION_USER);
$tbl_session_rel_course				= Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
$tbl_course							= Database::get_main_table(TABLE_MAIN_COURSE);

// setting the name of the tool
$tool_name = get_lang('EditSessionCoursesByUser');

$id_user = intval($_GET['id_user']);

if (empty($id_user) || empty($id_session)) {
	header('Location: resume_session.php?id_session='.$id_session);
    exit;
}

$formSent=0;
$errorMsg=$firstLetterCourse=$firstLetterSession='';
$CourseList=$SessionList=array();
$courses=$sessions=array();
$noPHP_SELF=true;

if (isset($_POST['formSent']) && $_POST['formSent']) {
	$formSent			= $_POST['formSent'];
	$CourseList			= $_POST['SessionCoursesList'];

	if (!is_array($CourseList)) {
		$CourseList=array();
	}

	$sql="SELECT DISTINCT course.id
			FROM $tbl_course course LEFT JOIN $tbl_session_rel_course session_rel_course
			ON course.id = session_rel_course.c_id inner join $tbl_session_rel_course_rel_user as srcru
			ON (srcru.id_session =  session_rel_course.id_session)
			WHERE id_user = $id_user and session_rel_course.id_session = $id_session";

	$rs = Database::query($sql);
	$existingCourses = Database::store_result($rs);
	if (count($CourseList) == count($existingCourses)) {
		header('Location: session_course_user.php?id_session='.$id_session.'&id_user='.$id_user.'&msg='.get_lang('MaybeYouWantToDeleteThisUserFromSession'));
		exit;
	}
	foreach ($CourseList as $courseId) {
		$exists = false;
		foreach($existingCourses as $existingCourse) {
			if ($enreg_course == $existingCourse['id']) {
				$exists=true;
			}
		}
		if (!$exists) {
            $courseId = Database::escape_string($courseId);
			$sql_delete = "DELETE FROM $tbl_session_rel_course_rel_user
							WHERE id_user='".$id_user."' AND c_id ='".$courseId."' AND id_session = $id_session";
			$result = Database::query($sql_delete);
			if (Database::affected_rows($result)) {
				//update session rel course table
				$sql_update  = "UPDATE $tbl_session_rel_course SET nbr_users= nbr_users - 1 WHERE id_session='$id_session' AND c_id = '$courseId'";
				Database::query($sql_update);
			}
		}
	}

	foreach ($existingCourses as $existingCourse) {
		if(!in_array($existingCourse['id'], $CourseList)) {
            $courseId = Database::escape_string($existingCourse['id']);
			$sql_insert = "INSERT IGNORE INTO $tbl_session_rel_course_rel_user(id_session,c_id,id_user) VALUES('$id_session','$courseId','$id_user')";
			$result = Database::query($sql_insert);
			if (Database::affected_rows($result)) {
				//update session rel course table
				$sql_update  = "UPDATE $tbl_session_rel_course SET nbr_users= nbr_users + 1 WHERE id_session='$id_session' AND c_id='$courseId'";
				Database::query($sql_update);
			}
		}
	}
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
$user_info = api_get_user_info($id_user);

echo '<legend>'.$tool_name.': '.$session_info['name'].' - '.$user_info['complete_name'].'</legend>';

$nosessionCourses = $sessionCourses = array();
// actual user
$sql = "SELECT course.id, course.code, title, visual_code, srcru.id_session
        FROM $tbl_course course INNER JOIN $tbl_session_rel_course_rel_user as srcru
        ON course.id = srcru.c_id
        WHERE srcru.id_user = $id_user AND id_session = $id_session";

//all
$sql_all="SELECT course.id, code, title, visual_code, src.id_session " .
			"FROM $tbl_course course INNER JOIN $tbl_session_rel_course  as src  " .
			"ON course.id = src.c_id AND id_session = $id_session";
$result=Database::query($sql);
$Courses=Database::store_result($result);

$result=Database::query($sql_all);
$CoursesAll=Database::store_result($result);

$course_temp = array();
foreach($Courses as $course) {
	$course_temp[] = $course['id'];
}
foreach($CoursesAll as $course) {
	if (in_array($course['id'], $course_temp)) {
		$nosessionCourses[$course['id']] = $course ;
	} else {
		$sessionCourses[$course['id']] = $course ;
	}
}
unset($Courses);
?>

<form name="formulaire" method="post" action="<?php echo api_get_self(); ?>?id_user=<?php echo $id_user; ?>&id_session=<?php echo $id_session; ?>" style="margin:0px;">
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
	foreach($nosessionCourses as $enreg) {
		?>
		<option value="<?php echo $enreg['id']; ?>" <?php echo 'title="'.htmlspecialchars($enreg['title'].' ('.$enreg['visual_code'].')',ENT_QUOTES).'"'; if(in_array($enreg['code'],$CourseList)) echo 'selected="selected"'; ?>><?php echo $enreg['title'].' ('.$enreg['visual_code'].')'; ?></option>
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
  <td width="45%" align="center">
      <select id='destination' name="SessionCoursesList[]" multiple="multiple" size="20" style="width:320px;">
<?php
foreach($sessionCourses as $enreg) {
?>
	<option value="<?php echo $enreg['id']; ?>" title="<?php echo htmlspecialchars($enreg['title'].' ('.$enreg['visual_code'].')',ENT_QUOTES); ?>"><?php echo $enreg['title'].' ('.$enreg['visual_code'].')'; ?></option>
<?php
}
unset($sessionCourses);
?>

  </select></td>
</tr>
</table>

</form>
<script>
function valide(){
	var options = document.getElementById('destination').options;
	for (i = 0 ; i<options.length ; i++)
		options[i].selected = true;

	document.forms.formulaire.submit();
}
</script>
<?php
