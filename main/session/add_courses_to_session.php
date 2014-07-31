<?php
/* For licensing terms, see /license.txt */
/**
 * @package chamilo.admin
 * @todo use formvalidator
 */

// name of the language file that needs to be included
$language_file='admin';

// resetting the course id
$cidReset = true;

//require_once '../inc/global.inc.php';

$id_session = isset($_GET['id_session']) ? intval($_GET['id_session']) : null;

SessionManager::protect_session_edit($id_session);

$xajax = new xajax();
$xajax -> registerFunction (array('search_courses', 'AddCourseToSession', 'search_courses'));

// Setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;

// setting breadcrumbs
$interbreadcrumb[] = array('url' => 'index.php', 'name' => get_lang('Sessions'));
$interbreadcrumb[] = array('url' => 'session_list.php','name' => get_lang('SessionList'));
$interbreadcrumb[] = array('url' => "resume_session.php?id_session=".$id_session,"name" => get_lang('SessionOverview'));

// Database Table Definitions
$tbl_session_rel_course_rel_user	= Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
$tbl_session						= Database::get_main_table(TABLE_MAIN_SESSION);
$tbl_session_rel_user				= Database::get_main_table(TABLE_MAIN_SESSION_USER);
$tbl_session_rel_course				= Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
$tbl_course							= Database::get_main_table(TABLE_MAIN_COURSE);

// setting the name of the tool
$tool_name= get_lang('SubscribeCoursesToSession');

$add_type = 'multiple';
if (isset($_GET['add_type']) && $_GET['add_type']!=''){
	$add_type = Security::remove_XSS($_REQUEST['add_type']);
}

$page = isset($_GET['page']) ? Security::remove_XSS($_GET['page']) : null;
$xajax->processRequests();

$htmlHeadXtra[] = $xajax->getJavascript('../inc/lib/xajax/');
$htmlHeadXtra[] = '<script>
function add_course_to_session (code, content) {
	document.getElementById("course_to_add").value = "";
	document.getElementById("ajax_list_courses_single").innerHTML = "";

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
function remove_item(origin) {
	for(var i = 0 ; i<origin.options.length ; i++) {
		if(origin.options[i].selected) {
			origin.options[i]=null;
			i = i-1;
		}
	}
}
</script>';


$formSent = 0;
$errorMsg = $firstLetterCourse=$firstLetterSession='';
$CourseList = $SessionList=array();
$courses = $sessions=array();
$noPHP_SELF = true;

if (isset($_POST['formSent']) && $_POST['formSent']) {

	$formSent              = $_POST['formSent'];
	$firstLetterCourse     = $_POST['firstLetterCourse'];
	$firstLetterSession    = $_POST['firstLetterSession'];
	$CourseList            = $_POST['SessionCoursesList'];

    SessionManager::add_courses_to_session($id_session, $CourseList);
	if (isset($_GET['add'])) {
		header('Location: add_users_to_session.php?id_session='.$id_session.'&add=true');
        exit;
    } else {
		header('Location: resume_session.php?id_session='.$id_session);
        exit;
    }
}

// display the dokeos header
Display::display_header($tool_name);

if ($add_type == 'multiple') {
	$link_add_type_unique = '<a href="'.api_get_self().'?id_session='.$id_session.'&add='.Security::remove_XSS($_GET['add']).'&add_type=unique">'.Display::return_icon('single.gif').get_lang('SessionAddTypeUnique').'</a>';
	$link_add_type_multiple = Display::return_icon('multiple.gif').get_lang('SessionAddTypeMultiple').' ';
} else {
	$link_add_type_unique = Display::return_icon('single.gif').get_lang('SessionAddTypeUnique').'&nbsp;&nbsp;&nbsp;';
	$link_add_type_multiple = '<a href="'.api_get_self().'?id_session='.$id_session.'&add='.Security::remove_XSS($_GET['add']).'&add_type=multiple">'.Display::return_icon('multiple.gif').get_lang('SessionAddTypeMultiple').'</a>';
}


// the form header
$session_info = SessionManager::fetch($id_session);
echo '<div class="actions">';
echo $link_add_type_unique.$link_add_type_multiple;
echo '</div>';

/*$sql = 'SELECT COUNT(1) FROM '.$tbl_course;
$rs = Database::query($sql);
$count_courses = Database::result($rs, 0, 0);*/

$ajax_search = $add_type == 'unique' ? true : false;
$nosessionCourses = $sessionCourses = array();
if ($ajax_search) {

	$sql="SELECT code, title, visual_code, id_session
			FROM $tbl_course course
			INNER JOIN $tbl_session_rel_course session_rel_course
				ON course.id = session_rel_course.c_id
				AND session_rel_course.id_session = ".intval($id_session)."
			ORDER BY ".(sizeof($courses)?"(course.code IN(".implode(',',$courses).")) DESC,":"")." title";

	if (api_is_multiple_url_enabled()) {
		$tbl_course_rel_access_url= Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
		$access_url_id = api_get_current_access_url_id();
		if ($access_url_id != -1){
			$sql="SELECT code, title, visual_code, id_session
			FROM $tbl_course course
			INNER JOIN $tbl_session_rel_course session_rel_course
				ON course.code = session_rel_course.course_code
				AND session_rel_course.id_session = ".intval($id_session)."
				INNER JOIN $tbl_course_rel_access_url url_course ON (url_course.c_id = course.id)
				WHERE access_url_id = $access_url_id
			ORDER BY ".(sizeof($courses)?"(course.code IN(".implode(',',$courses).")) DESC,":"")." title";
		}
	}

	$result = Database::query($sql);
	$Courses = Database::store_result($result);

	foreach($Courses as $course) {
		$sessionCourses[$course['code']] = $course ;
	}
} else {
	$sql="SELECT code, title, visual_code, id_session, course.id as real_id
			FROM $tbl_course course
			LEFT JOIN $tbl_session_rel_course session_rel_course
				ON course.id = session_rel_course.c_id
				AND session_rel_course.id_session = ".intval($id_session)."
			ORDER BY ".(sizeof($courses)?"(course.code IN(".implode(',',$courses).")) DESC,":"")." title";

	if (api_is_multiple_url_enabled()) {
		$tbl_course_rel_access_url= Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
		$access_url_id = api_get_current_access_url_id();
		if ($access_url_id != -1){
			$sql="SELECT code, title, visual_code, id_session, course.id as real_id
				FROM $tbl_course course
				LEFT JOIN $tbl_session_rel_course session_rel_course
					ON course.id = session_rel_course.c_id
					AND session_rel_course.id_session = ".intval($id_session)."
				INNER JOIN $tbl_course_rel_access_url url_course ON (url_course.c_id = course.id)
				WHERE access_url_id = $access_url_id
				ORDER BY ".(sizeof($courses)?"(course.code IN(".implode(',',$courses).")) DESC,":"")." title";
		}
	}
	$result = Database::query($sql);
	$Courses = Database::store_result($result);
	foreach($Courses as $course) {
		if ($course['id_session'] == $id_session) {
			$sessionCourses[$course['real_id']] = $course ;
		} else {
			$nosessionCourses[$course['real_id']] = $course ;
		}
	}
}
unset($Courses);
?>
<form name="formulaire" method="post" action="<?php echo api_get_self(); ?>?page=<?php echo $page; ?>&id_session=<?php echo $id_session; ?><?php if(!empty($_GET['add'])) echo '&add=true' ; ?>" style="margin:0px;" <?php if($ajax_search){echo ' onsubmit="valide();"';}?>>
<legend><?php echo $tool_name.' ('.$session_info['name'].')'; ?></legend>
<input type="hidden" name="formSent" value="1" />

<?php
if(!empty($errorMsg)) {
	Display::display_normal_message($errorMsg); //main API
}
?>

<table border="0" cellpadding="5" cellspacing="0" width="100%" align="center">
<tr>
  <td width="45%" align="center"><b><?php echo get_lang('CourseListInPlatform') ?> :</b></td>
  <td width="10%">&nbsp;</td>
  <td align="center" width="45%"><b><?php echo get_lang('CourseListInSession') ?> :</b></td>
</tr>

<?php if($add_type == 'multiple') { ?>
<tr><td width="45%" align="center">
 <?php echo get_lang('FirstLetterCourse'); ?> :
     <select name="firstLetterCourse" onchange = "xajax_search_courses(this.value,'multiple')">
      <option value="%">--</option>
      <?php
      echo Display :: get_alphabet_options();
      echo Display :: get_numeric_options(0,9,'');
      ?>
     </select>
</td>
<td>&nbsp;</td></tr>
<?php } ?>
<tr>
  <td width="45%" align="center">
<?php
if(!($add_type == 'multiple')){
	?>
	<input type="text" id="course_to_add" onkeyup="xajax_search_courses(this.value,'single')" />
	<div id="ajax_list_courses_single"></div>
	<?php
} else {
	?>
	<div id="ajax_list_courses_multiple">
	<select id="origin" name="NoSessionCoursesList[]" multiple="multiple" size="20" style="width:360px;"> <?php
	foreach($nosessionCourses as $enreg) {
		?>
		<option value="<?php echo $enreg['real_id']; ?>" <?php echo 'title="'.htmlspecialchars($enreg['title'].' ('.$enreg['visual_code'].')',ENT_QUOTES).'"'; if(in_array($enreg['code'],$CourseList)) echo 'selected="selected"'; ?>><?php echo $enreg['title'].' ('.$enreg['visual_code'].')'; ?></option>
    <?php } ?>
    </select>
	</div>
	<?php
}
unset($nosessionCourses);
?>
	</td>
	<td width="10%" valign="middle" align="center">
  <?php
  if ($ajax_search) {
  ?>
  	<button class="arrowl" type="button" onclick="remove_item(document.getElementById('destination'))"></button>
  <?php
  } else {
  ?>
  	<button class="arrowr" type="button" onclick="moveItem(document.getElementById('origin'), document.getElementById('destination'))" onclick="moveItem(document.getElementById('origin'), document.getElementById('destination'))"></button>
	<br /><br />
	<button class="arrowl" type="button" onclick="moveItem(document.getElementById('destination'), document.getElementById('origin'))" onclick="moveItem(document.getElementById('destination'), document.getElementById('origin'))"></button>
  <?php
  }
  ?>
	<br /><br /><br /><br /><br /><br />
	<?php
	if (isset($_GET['add'])) {
	    echo '<button class="save" type="button" value="" onclick="valide()" >'.get_lang('NextStep').'</button>';
	} else {
		echo '<button class="save" type="button" value="" onclick="valide()" >'.get_lang('SubscribeCoursesToSession').'</button>';
	}
	?>
  </td>
  <td width="45%" align="center">
      <select id='destination' name="SessionCoursesList[]" multiple="multiple" size="20" style="width:360px;">
<?php foreach($sessionCourses as $enreg) { ?>
	<option value="<?php echo $enreg['real_id']; ?>" title="<?php echo htmlspecialchars($enreg['title'].' ('.$enreg['visual_code'].')',ENT_QUOTES); ?>"><?php echo $enreg['title'].' ('.$enreg['visual_code'].')'; ?></option>
<?php }
unset($sessionCourses);
?>
  </select>
  </td>
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
Display::display_footer();
