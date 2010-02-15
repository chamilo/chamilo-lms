<?php
/* For licensing terms, see /license.txt */

/**
==============================================================================
*	Interface for assigning courses to Human Resources Manager
*	@package chamilo.admin
==============================================================================
*/

// name of the language file that needs to be included
$language_file='admin';
// resetting the course id
$cidReset=true;

// including some necessary dokeos files
require_once '../inc/global.inc.php';
require_once '../inc/lib/xajax/xajax.inc.php';
require_once api_get_path(LIBRARY_PATH).'course.lib.php';

// create an ajax object
$xajax = new xajax();
$xajax -> registerFunction ('search_courses');

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;

// Access restrictions
api_protect_admin_script(true);

// setting breadcrumbs
$interbreadcrumb[] = array('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array('url' => 'user_list.php','name' => get_lang('UserList'));


// Database Table Definitions
$tbl_course 			= 	Database::get_main_table(TABLE_MAIN_COURSE);
$tbl_course_rel_user 	= 	Database::get_main_table(TABLE_MAIN_COURSE_USER);

// setting the name of the tool
$tool_name= get_lang('AssignCoursesToHumanResourcesManager');

// initializing variables
$id_session=intval($_GET['id_session']);
$hrm_id = intval($_GET['user']);
$hrm_info = api_get_user_info($hrm_id);
$user_anonymous  = api_get_anonymous_id();
$current_user_id = api_get_user_id();

$add_type = 'multiple';
if(isset($_GET['add_type']) && $_GET['add_type']!=''){
	$add_type = Security::remove_XSS($_REQUEST['add_type']);
}

if (!api_is_platform_admin()) {
	api_not_allowed(true);
}

function search_courses($needle,$type) {
	global $tbl_course, $tbl_course_rel_user, $hrm_id;

	$xajax_response = new XajaxResponse();
	$return = '';
	if(!empty($needle) && !empty($type)) {
		// xajax send utf8 datas... datas in db can be non-utf8 datas
		$charset = api_get_setting('platform_charset');
		$needle = api_convert_encoding($needle, $charset, 'utf-8');

		$assigned_courses_to_hrm = CourseManager::get_courses_followed_by_drh($hrm_id);
		$assigned_courses_code = array_keys($assigned_courses_to_hrm);
		foreach ($assigned_courses_code as &$value) {
			$value = "'".$value."'";
		}
		$without_assigned_courses = '';
		if (count($assigned_courses_code) > 0) {
			$without_assigned_courses = " AND c.code NOT IN(".implode(',',$assigned_courses_code).")";
		}

		$sql = "SELECT c.code, c.title FROM $tbl_course c
				WHERE  c.code LIKE '$needle%' $without_assigned_courses ";
		$rs	= Database::query($sql);

		$course_list = array();
		$return .= '<select id="origin" name="NoAssignedCoursesList[]" multiple="multiple" size="20" style="width:340px;">';
		while($course = Database :: fetch_array($rs)) {
			$course_list[] = $course['id'];
			$return .= '<option value="'.$course['code'].'" title="'.htmlspecialchars($course['title'],ENT_QUOTES).'">'.$course['title'].' ('.$course['code'].')</option>';
		}
		$return .= '</select>';
		$xajax_response -> addAssign('ajax_list_courses_multiple','innerHTML',api_utf8_encode($return));
	}
	$_SESSION['course_list'] = $course_list;
	return $xajax_response;
}

$xajax -> processRequests();
$htmlHeadXtra[] = $xajax->getJavascript('../inc/lib/xajax/');
$htmlHeadXtra[] = '
<script type="text/javascript">
<!--
function moveItem(origin , destination) {
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
	var newOptions = new Array();
	for (i = 0 ; i<options.length ; i++) {
		newOptions[i] = options[i];
	}
	newOptions = newOptions.sort(mysort);
	options.length = 0;
	for(i = 0 ; i < newOptions.length ; i++){
		options[i] = newOptions[i];
	}
}
function mysort(a, b) {
	if (a.text.toLowerCase() > b.text.toLowerCase()) {
		return 1;
	}
	if (a.text.toLowerCase() < b.text.toLowerCase()) {
		return -1;
	}
	return 0;
}

function valide() {
	var options = document.getElementById("destination").options;
	for (i = 0 ; i<options.length ; i++) {
		options[i].selected = true;
	}
	document.forms.formulaire.submit();
}
function remove_item(origin) {
	for(var i = 0 ; i<origin.options.length ; i++) {
		if(origin.options[i].selected) {
			origin.options[i]=null;
			i = i-1;
		}
	}
}
-->
</script>';

$formSent=0;
$errorMsg = $firstLetterCourse = '';
$UserList = array();

$msg = '';
if (intval($_POST['formSent']) == 1) {
	$courses_list = $_POST['CoursesList'];
	$affected_rows = CourseManager::suscribe_courses_to_hr_manager($hrm_id,$courses_list);
	if ($affected_rows)	{
		$msg = get_lang('AssignedCoursesHasBeenUpdatedSuccesslly');
	}
}

// display the dokeos header
Display::display_header($tool_name);
echo '<div class="row"><div class="form_header">'.get_lang('AssignedCoursesTo').'&nbsp;'.api_get_person_name($hrm_info['firstname'], $hrm_info['lastname']).'</div></div><br />';

// *******************

$assigned_courses_to_hrm = CourseManager::get_courses_followed_by_drh($hrm_id);
$assigned_courses_code = array_keys($assigned_courses_to_hrm);
foreach ($assigned_courses_code as &$value) {
	$value = "'".$value."'";
}

$without_assigned_courses = '';
if (count($assigned_courses_code) > 0) {
	$without_assigned_courses = " AND c.code NOT IN(".implode(',',$assigned_courses_code).")";
}

$needle = '%';
if (isset($_POST['firstLetterCourse'])) {
	$needle = Database::escape_string($_POST['firstLetterCourse']);
	$needle = "$needle%";
}

$sql 	= " SELECT c.code, c.title FROM $tbl_course c
			WHERE  c.code LIKE '$needle' $without_assigned_courses ";
$result	= Database::query($sql);

?>
<form name="formulaire" method="post" action="<?php echo api_get_self(); ?>?user=<?php echo $hrm_id ?>" style="margin:0px;" <?php if($ajax_search){echo ' onsubmit="valide();"';}?>>
<input type="hidden" name="formSent" value="1" />
<?php
if(!empty($msg)) {
	Display::display_normal_message($msg); //main API
}
?>
<table border="0" cellpadding="5" cellspacing="0" width="100%" align="center">
<tr>
	<td align="left"></td>
	<td align="left"></td>
	<td width="" align="center"> &nbsp;	</td>
</tr>
<tr>
  <td width="45%" align="center"><b><?php echo get_lang('CoursesListInPlatform') ?> :</b></td>
  <td width="10%">&nbsp;</td>
  <td align="center" width="45%"><b><?php echo get_lang('AssignedCoursesListToHumanResourceManager') ?> :</b></td>
</tr>

<?php if($add_type == 'multiple') { ?>
<tr><td width="45%" align="center">
 <?php echo get_lang('FirstLetterCourse');?> :
     <select name="firstLetterCourse" onchange = "xajax_search_courses(this.value,'multiple')">
      <option value="%">--</option>
      <?php
      echo Display :: get_alphabet_options($_POST['firstLetterCourse']);
      ?>
     </select>
</td>
<td>&nbsp;</td></tr>
<?php } ?>
<tr>
  <td width="45%" align="center">
	<div id="ajax_list_courses_multiple">
	<select id="origin" name="NoAssignedCoursesList[]" multiple="multiple" size="20" style="width:340px;">
	<?php
	while ($enreg = Database::fetch_array($result)) {
	?>
		<option value="<?php echo $enreg['code']; ?>" <?php echo 'title="'.htmlspecialchars($enreg['title'],ENT_QUOTES).'"';?>><?php echo $enreg['title'].' ('.$enreg['code'].')'; ?></option>
	<?php } ?>
	</select></div>
  </td>

  <td width="10%" valign="middle" align="center">
  <?php
  if ($ajax_search) {
  ?>
  	<button class="arrowl" type="button" onclick="remove_item(document.getElementById('destination'))"></button>
  <?php
  }
  else
  {
  ?>
  	<button class="arrowr" type="button" onclick="moveItem(document.getElementById('origin'), document.getElementById('destination'))" onclick="moveItem(document.getElementById('origin'), document.getElementById('destination'))"></button>
	<br /><br />
	<button class="arrowl" type="button" onclick="moveItem(document.getElementById('destination'), document.getElementById('origin'))" onclick="moveItem(document.getElementById('destination'), document.getElementById('origin'))"></button>
  <?php
  }
  ?>
	<br /><br /><br /><br /><br /><br />
	<?php
		echo '<button class="save" type="button" value="" onclick="valide()" >'.get_lang('AssignCoursesToHumanResourceManager').'</button>';
	?>
  </td>
  <td width="45%" align="center">
  <select id='destination' name="CoursesList[]" multiple="multiple" size="20" style="width:320px;">
	<?php
	if (is_array($assigned_courses_to_hrm)) {
		foreach($assigned_courses_to_hrm as $enreg) {
	?>
		<option value="<?php echo $enreg['code']; ?>" <?php echo 'title="'.htmlspecialchars($enreg['title'],ENT_QUOTES).'"'; ?>><?php echo $enreg['title'].' ('.$enreg['code'].')'; ?></option>
	<?php }
	}?>
  </select></td>
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