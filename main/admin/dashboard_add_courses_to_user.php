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
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';

global $_configuration;


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
$tbl_course_rel_access_url 	= 	Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);

// initializing variables
$id_session=intval($_GET['id_session']);
$user_id = intval($_GET['user']);
$user_info = api_get_user_info($user_id);
$user_anonymous  = api_get_anonymous_id();
$current_user_id = api_get_user_id();

// setting the name of the tool
if (UserManager::is_admin($user_id)) {
	$tool_name= get_lang('AssignCoursesToPlatformAdministrator');
} else if ($user_info['status'] == SESSIONADMIN) {
	$tool_name= get_lang('AssignCoursesToSessionsAdministrator');
} else {
	$tool_name= get_lang('AssignCoursesToHumanResourcesManager');
}

$add_type = 'multiple';
if(isset($_GET['add_type']) && $_GET['add_type']!=''){
	$add_type = Security::remove_XSS($_REQUEST['add_type']);
}

if (!api_is_platform_admin()) {
	api_not_allowed(true);
}

function search_courses($needle,$type) {
	global $_configuration, $tbl_course, $tbl_course_rel_user, $tbl_course_rel_access_url,$user_id;

	$xajax_response = new XajaxResponse();
	$return = '';
	if(!empty($needle) && !empty($type)) {
		// xajax send utf8 datas... datas in db can be non-utf8 datas
		$charset = api_get_system_encoding();
		$needle = api_convert_encoding($needle, $charset, 'utf-8');

		$assigned_courses_to_hrm = CourseManager::get_courses_followed_by_drh($user_id);
		$assigned_courses_code = array_keys($assigned_courses_to_hrm);
		foreach ($assigned_courses_code as &$value) {
			$value = "'".$value."'";
		}
		$without_assigned_courses = '';
		if (count($assigned_courses_code) > 0) {
			$without_assigned_courses = " AND c.code NOT IN(".implode(',',$assigned_courses_code).")";
		}

		if ($_configuration['multiple_access_urls']) {
			$sql = "SELECT c.code, c.title FROM $tbl_course c LEFT JOIN $tbl_course_rel_access_url a ON (a.course_code = c.code)
				WHERE  c.code LIKE '$needle%' $without_assigned_courses AND access_url_id = ".api_get_current_access_url_id()."";
		} else {
			$sql = "SELECT c.code, c.title FROM $tbl_course c
				WHERE  c.code LIKE '$needle%' $without_assigned_courses ";
		}


		$rs	= Database::query($sql);

		$return .= '<select id="origin" name="NoAssignedCoursesList[]" multiple="multiple" size="20" style="width:340px;">';
		while($course = Database :: fetch_array($rs)) {
			$return .= '<option value="'.$course['code'].'" title="'.htmlspecialchars($course['title'],ENT_QUOTES).'">'.$course['title'].' ('.$course['code'].')</option>';
		}
		$return .= '</select>';
		$xajax_response -> addAssign('ajax_list_courses_multiple','innerHTML',api_utf8_encode($return));
	}
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
	$affected_rows = CourseManager::suscribe_courses_to_hr_manager($user_id,$courses_list);
	if ($affected_rows)	{
		$msg = get_lang('AssignedCoursesHaveBeenUpdatedSuccessfully');
	}
}

// display header
Display::display_header($tool_name);

// actions
echo '<div class="actions" style="height:22px;">
<span style="float: right;margin:0px;padding:0px;">
<a href="dashboard_add_users_to_user.php?user='.$user_id.'">'.Display::return_icon('add_user_big.gif', get_lang('AssignUsers'), array('style'=>'vertical-align:middle')).' '.get_lang('AssignUsers').'</a>
<a href="dashboard_add_sessions_to_user.php?user='.$user_id.'">'.Display::return_icon('view_more_stats.gif', get_lang('AssignSessions'), array('style'=>'vertical-align:middle')).' '.get_lang('AssignSessions').'</a></span>
<span style="vertical-align:middle">'.sprintf(get_lang('AssignCoursesToX'), api_get_person_name($user_info['firstname'], $user_info['lastname'])).'</span></div>';

// *******************

$assigned_courses_to_hrm = CourseManager::get_courses_followed_by_drh($user_id);
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

if ($_configuration['multiple_access_urls']) {
	$sql 	= " SELECT c.code, c.title FROM $tbl_course c LEFT JOIN $tbl_course_rel_access_url a ON (a.course_code = c.code)
			WHERE  c.code LIKE '$needle' $without_assigned_courses AND access_url_id = ".api_get_current_access_url_id()."";

} else {
	$sql 	= " SELECT c.code, c.title FROM $tbl_course c
			WHERE  c.code LIKE '$needle' $without_assigned_courses ";
}


$result	= Database::query($sql);

?>
<form name="formulaire" method="post" action="<?php echo api_get_self(); ?>?user=<?php echo $user_id ?>" style="margin:0px;" <?php if($ajax_search){echo ' onsubmit="valide();"';}?>>
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
  <td align="center" width="45%"><b>
  	<?php
	  	if (UserManager::is_admin($user_id)) {
			echo get_lang('AssignedCoursesListToPlatformAdministrator');
		} else if ($user_info['status'] == SESSIONADMIN) {
			echo get_lang('AssignedCoursesListToSessionsAdministrator');
		} else {
			echo get_lang('AssignedCoursesListToHumanResourcesManager');
		}
  	?>
  	:</b></td>
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
		echo '<button class="save" type="button" value="" onclick="valide()" >'.$tool_name.'</button>';
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
