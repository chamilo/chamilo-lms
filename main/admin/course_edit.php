<?php
/* For licensing terms, see /license.txt */
/**
*	@package chamilo.admin
*/
/* Initialization section */
// name of the language file that needs to be included
$language_file = 'admin';
$cidReset = true;
require_once '../inc/global.inc.php';
$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();
require_once api_get_path(LIBRARY_PATH).'fileManage.lib.php';

$course_table = Database::get_main_table(TABLE_MAIN_COURSE);
$course_user_table = Database::get_main_table(TABLE_MAIN_COURSE_USER);
$course_code = isset($_GET['course_code']) ? $_GET['course_code'] : $_POST['code'];
$noPHP_SELF = true;
$tool_name = get_lang('ModifyCourseInfo');
$interbreadcrumb[] = array ("url" => 'index.php',       "name" => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array ("url" => "course_list.php", "name" => get_lang('CourseList'));

define('USER_FIELD_TYPE_CHECKBOX', 10);

/* Libraries */
/* MAIN CODE */
// Get all course categories
$table_user = Database :: get_main_table(TABLE_MAIN_USER);


//Get the course infos
$sql = "SELECT * FROM $course_table WHERE code='".Database::escape_string($course_code)."'";
$result = Database::query($sql);
if (Database::num_rows($result) != 1) {
	header('Location: course_list.php');
	exit ();
}
$course = Database::fetch_array($result,'ASSOC');

// Get course teachers
$table_course_user = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
$order_clause = api_sort_by_first_name() ? ' ORDER BY firstname, lastname' : ' ORDER BY lastname, firstname';
$sql = "SELECT user.user_id,lastname,firstname FROM $table_user as user,$table_course_user as course_user WHERE course_user.status='1' AND course_user.user_id=user.user_id AND course_user.course_code='".$course_code."'".$order_clause;
$res = Database::query($sql);
$course_teachers = array();
while ($obj = Database::fetch_object($res)) {
	$course_teachers[$obj->user_id] = api_get_person_name($obj->firstname, $obj->lastname);
}

// Get all possible teachers without the course teachers
if ($_configuration['multiple_access_urls']) {
	$access_url_rel_user_table= Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
	$sql = "SELECT u.user_id,lastname,firstname FROM $table_user as u
			INNER JOIN $access_url_rel_user_table url_rel_user
			ON (u.user_id=url_rel_user.user_id) WHERE url_rel_user.access_url_id=".api_get_current_access_url_id()." AND status=1".$order_clause;
} else {
	
	$sql = "SELECT user_id,lastname,firstname FROM $table_user WHERE status='1'".$order_clause;
}

$res = Database::query($sql);
$teachers = array();


$platform_teachers[0] = '-- '.get_lang('NoManager').' --';
while ($obj = Database::fetch_object($res)) {
	if (!array_key_exists($obj->user_id,$course_teachers)) {
		$teachers[$obj->user_id] = api_get_person_name($obj->firstname, $obj->lastname);
	}


	if ($course['tutor_name']==$course_teachers[$obj->user_id]) {
		$course['tutor_name']=$obj->user_id;
	}
	//We add in the array platform teachers
	$platform_teachers[$obj->user_id] = api_get_person_name($obj->firstname, $obj->lastname);
}

//Case where there is no teacher in the course
if (count($course_teachers)==0) {
	$sql='SELECT tutor_name FROM '.$course_table.' WHERE code="'.$course_code.'"';
	$res = Database::query($sql);
	$tutor_name=Database::result($res,0,0);
	$course['tutor_name']=array_search($tutor_name,$platform_teachers);
}

// Build the form
$form = new FormValidator('update_course');
$form->addElement('hidden','code',$course_code);

//title
$form->add_textfield( 'title', get_lang('Title'),true, array ('size' => '60'));
$form->applyFilter('title','html_filter');
$form->applyFilter('title','trim');
// code
$form->add_textfield('visual_code', array(get_lang('CourseCode'), get_lang('OnlyLettersAndNumbers')));

$form->applyFilter('visual_code','strtoupper');
$form->applyFilter('visual_code','html_filter');
//$form->add_textfield('tutor_name', get_lang('CourseTitular'));
$form->addElement('select', 'tutor_name', get_lang('CourseTitular'), $platform_teachers, array('style'=>'width:350px','id'=>'tutor_name_id', 'class'=>'chzn-select'));
$form->applyFilter('tutor_name','html_filter');

//$form->addElement('select', 'course_teachers', get_lang('CourseTeachers'), $teachers, 'multiple=multiple size="4" style="width: 150px;"');

$group=array();
$group[] = FormValidator::createElement('select', 'platform_teachers', '', $teachers,        'id="platform_teachers" multiple=multiple size="4" style="width:280px;"');
$group[] = FormValidator::createElement('select', 'course_teachers', '',   $course_teachers, 'id="course_teachers" multiple=multiple size="4" style="width:280px;"');

$element_template = <<<EOT
	<div class="row">
		<div class="label">
			<!-- BEGIN required --><span class="form_required">*</span> <!-- END required -->{label}
		</div>
		<div class="formw">
			<table cellpadding="0" cellspacing="0">
				<tr>
					<!-- BEGIN error --><span class="form_error">{error}</span><br /><!-- END error -->	<td>{element}</td>
				</tr>
			</table>
		</div>
	</div>
EOT;

$renderer = $form->defaultRenderer();
$renderer -> setElementTemplate($element_template, 'group');
$form -> addGroup($group,'group',get_lang('CourseTeachers'),'</td><td width="80" align="center">'.
		'<input class="arrowr" style="width:30px;height:30px;padding-right:12px" type="button" onclick="moveItem(document.getElementById(\'platform_teachers\'), document.getElementById(\'course_teachers\'))" ><br><br>' .
		'<input class="arrowl" style="width:30px;height:30px;padding-left:13px" type="button" onclick="moveItem(document.getElementById(\'course_teachers\'), document.getElementById(\'platform_teachers\'))" ></td><td>');


$categories_select = $form->addElement('select', 'category_code', get_lang('CourseFaculty'), $categories , array('style'=>'width:350px','id'=>'category_code_id', 'class'=>'chzn-select'));
$categories_select->addOption('-','');
CourseManager::select_and_sort_categories($categories_select);

$form->add_textfield( 'department_name', get_lang('CourseDepartment'), false,array ('size' => '60'));
$form->applyFilter('department_name','html_filter');
$form->applyFilter('department_name','trim');

$form->add_textfield( 'department_url', get_lang('CourseDepartmentURL'),false, array ('size' => '60'));
$form->applyFilter('department_url','html_filter');
$form->applyFilter('department_url','trim');


$form->addElement('select_language', 'course_language', get_lang('CourseLanguage'));
$form->applyFilter('select_language','html_filter');

$group = array();
$group[]= $form->createElement('radio', 'visibility', get_lang("CourseAccess"), get_lang('OpenToTheWorld'), COURSE_VISIBILITY_OPEN_WORLD);
$group[]= $form->createElement('radio', 'visibility', null, get_lang('OpenToThePlatform'), COURSE_VISIBILITY_OPEN_PLATFORM);
$group[]= $form->createElement('radio', 'visibility', null, get_lang('Private'), COURSE_VISIBILITY_REGISTERED);
$group[]= $form->createElement('radio', 'visibility', null, get_lang('CourseVisibilityClosed'), COURSE_VISIBILITY_CLOSED);
$form->addGroup($group,'', get_lang('CourseAccess'), '<br />');


$group = array();
$group[]= $form->createElement('radio', 'subscribe', get_lang('Subscription'), get_lang('Allowed'), 1);
$group[]= $form->createElement('radio', 'subscribe', null, get_lang('Denied'), 0);
$form->addGroup($group,'', get_lang('Subscription'), '<br />');

$group = array();
$group[]= $form->createElement('radio', 'unsubscribe', get_lang('Unsubscription'), get_lang('AllowedToUnsubscribe'), 1);
$group[]= $form->createElement('radio', 'unsubscribe', null, get_lang('NotAllowedToUnsubscribe'), 0);
$form->addGroup($group,'', get_lang('Unsubscription'), '<br />');


$form->addElement('text','disk_quota',get_lang('CourseQuota'));
$form->addRule('disk_quota', get_lang('ThisFieldIsRequired'),'required');
$form->addRule('disk_quota',get_lang('ThisFieldShouldBeNumeric'),'numeric');

$list_course_extra_field = CourseManager::get_course_extra_field_list($course_code);
foreach ($list_course_extra_field as $extra_field) {
	switch ($extra_field['field_type']) {
		/* case USER_FIELD_TYPE_TEXT:
		case USER_FIELD_TYPE_TEXTAREA:
		case USER_FIELD_TYPE_RADIO: */
		case USER_FIELD_TYPE_CHECKBOX:
			$checked = (array_key_exists('extra_field_value', $extra_field) && $extra_field['extra_field_value'] == 1)? array('checked'=>'checked'): '';
			$form->addElement('hidden', '_extra_'.$extra_field['field_variable'], 0);
			$field_display_text=$extra_field['field_display_text'];
			$form->addElement('checkbox', 'extra_'.$extra_field['field_variable'],get_lang('SpecialCourse') , get_lang($extra_field['field_default_value']).' '.Display::return_icon('synthese_view.gif',get_lang('AllUsersAreAutomaticallyRegistered')), $checked);

			break;
		/* case USER_FIELD_TYPE_SELECT_MULTIPLE:
		case USER_FIELD_TYPE_DATE:
		case USER_FIELD_TYPE_DATETIME:
		case USER_FIELD_TYPE_DOUBLE_SELECT:
		case USER_FIELD_TYPE_DIVIDER: */
	}
}
$form->addElement('style_submit_button', 'button', get_lang('ModifyCourseInfo'),'onclick="valide()"; class="save"');
// Set some default values

$course_db_name = $course['db_name'];
$course['title'] = api_html_entity_decode($course['title'], ENT_QUOTES, $charset);
$form->setDefaults($course);
// Validate form
if ($form->validate()) {
	$course = $form->getSubmitValues();
	$dbName = $_POST['dbName'];
	$course_code = $course['code'];
	$visual_code = $course['visual_code'];	
	$visual_code = generate_course_code($visual_code);   

    // Check if the visual code is already used by *another* course
    $visual_code_is_used = false;

    $warn = get_lang('TheFollowingCoursesAlreadyUseThisVisualCode').':';
    if (!empty($visual_code)) {
        $list = CourseManager::get_courses_info_from_visual_code($visual_code);
        foreach ($list as $course_temp) {
        	if ($course_temp['code'] != $course_code) {
        	   $visual_code_is_used = true;
               $warn .= ' '.$course_temp['title'].' ('.$course_temp['code'].'),';
            }
        }
        $warn = substr($warn,0,-1);
    }
    // an extra field
    $extras = array();
    foreach ($course as $key => $value) {
	    if (substr($key,0,6)=='extra_') {
			$extras[substr($key,6)] = $value;
		}
		if (substr($key,0,7)=='_extra_') {
			if(!array_key_exists(substr($key,7), $extras)) $extras[substr($key,7)] = $value;
		}
    }

	$tutor_id = $course['tutor_name'];
	$tutor_name=$platform_teachers[$tutor_id];

	$teachers = $course['group']['course_teachers'];

	$title = $course['title'];
	$category_code = $course['category_code'];
	$department_name = $course['department_name'];
	$department_url = $course['department_url'];
	$course_language = $course['course_language'];
	$disk_quota = $course['disk_quota'];
	$visibility = $course['visibility'];
	$subscribe = $course['subscribe'];
	$unsubscribe = $course['unsubscribe'];
	if (!stristr($department_url, 'http://')) {
		$department_url = 'http://'.$department_url;
	}
	$sql = "UPDATE $course_table SET course_language='".Database::escape_string($course_language)."',
								title='".Database::escape_string($title)."',
								category_code='".Database::escape_string($category_code)."',
								tutor_name='".Database::escape_string($tutor_name)."',
								visual_code='".Database::escape_string($visual_code)."',
								department_name='".Database::escape_string($department_name)."',
								department_url='".Database::escape_string($department_url)."',
								disk_quota='".Database::escape_string($disk_quota)."',
								visibility = '".Database::escape_string($visibility)."',
								subscribe = '".Database::escape_string($subscribe)."',
								unsubscribe='".Database::escape_string($unsubscribe)."'
							WHERE code='".Database::escape_string($course_code)."'";
	Database::query($sql);

	//update the extra fields
	if (count($extras) > 0) {
		foreach ($extras as $key => $value) {
			CourseManager::update_course_extra_field_value($course_code, $key, $value);
		}
	}

	//Delete only teacher relations that doesn't match the selected teachers
	$cond='';
	if (count($teachers)>0) {
		foreach($teachers as $key) $cond.=" AND user_id<>'".$key."'";
	}
	$sql='DELETE FROM '.$course_user_table.' WHERE course_code="'.Database::escape_string($course_code).'" AND status="1"'.$cond;
	Database::query($sql);

	if (count($teachers)>0) {
		foreach ($teachers as $key) {

			//We check if the teacher is already subscribed in this course
			$sql_select_teacher = 'SELECT 1 FROM '.$course_user_table.' WHERE user_id = "'.$key.'" AND course_code = "'.$course_code.'" ';
			$result = Database::query($sql_select_teacher);

			if (Database::num_rows($result) == 1) {
				$sql = 'UPDATE '.$course_user_table.' SET status = "1" WHERE course_code = "'.$course_code.'" AND user_id = "'.$key.'"  ';
			} else {
				$sql = "INSERT INTO ".$course_user_table . " SET
					course_code = '".Database::escape_string($course_code). "',
					user_id = '".$key . "',
					status = '1',
					role = '',
					tutor_id='0',
					sort='0',
					user_course_cat='0'";
			}
			Database::query($sql);
		}
	}

	$sql = "INSERT IGNORE INTO ".$course_user_table . " SET
				course_code = '".Database::escape_string($course_code). "',
				user_id = '".$tutor_id . "',
				status = '1',
				role = '',
				tutor_id='0',
				sort='0',
				user_course_cat='0'";
	Database::query($sql);
	
	$course_info = api_get_course_info($course_code);
	$course_id = $course_info['real_id'];
	$forum_config_table = Database::get_course_table(TOOL_FORUM_CONFIG_TABLE);
	$sql = "UPDATE ".$forum_config_table." SET default_lang='".Database::escape_string($course_language)."' WHERE c_id = $course_id ";
	if ($visual_code_is_used) {
	    header('Location: course_list.php?action=show_msg&warn='.urlencode($warn));
	} else {
        header('Location: course_list.php');
	}
	exit ();
}
Display::display_header($tool_name);

echo "<script>
function moveItem(origin , destination){

	for (var i = 0 ; i<origin.options.length ; i++) {
		if (origin.options[i].selected) {
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
	for (i = 0 ; i < newOptions.length ; i++) {
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
	var options = document.getElementById('course_teachers').options;
	for (i = 0 ; i<options.length ; i++) {
		options[i].selected = true;
    }
	document.update_course.submit();
}
</script>";
//api_display_tool_title($tool_name);
// Display the form
$form->display();
/* FOOTER */
Display :: display_footer();