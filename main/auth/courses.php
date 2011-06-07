<?php
/* For licensing terms, see /license.txt */

/**
* Template (front controller in MVC pattern) used for distpaching to the controllers depend on the current action
* @author Christian Fasanando <christian1827@gmail.com> - Beeznest
* @package chamilo.auth
*/

// Names of the language file that needs to be included.
$language_file = array ('courses', 'registration');

// Delete the globals['_cid'], we don't need it here.
$cidReset = true; // Flag forcing the 'current course' reset

// including files
require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'auth.lib.php'; 
require_once api_get_path(LIBRARY_PATH).'app_view.php';
require_once 'courses_controller.php';
//require_once api_get_path(LIBRARY_PATH).'debug.lib.inc.php';
require_once api_get_path(LIBRARY_PATH).'course.lib.php';
require_once api_get_path(LIBRARY_PATH).'mail.lib.inc.php';

if (api_get_setting('show_courses_descriptions_in_catalog') == 'true') {

	$htmlHeadXtra[] = api_get_jquery_ui_js();
    $htmlHeadXtra[] = '
    <script language="javascript">
        $(\'a.ajax\').live(\'click\', function() {
            var url     = this.href;
            var dialog  = $("#dialog");
            if ($("#dialog").length == 0) {
                dialog  = $(\'<div id="dialog" style="display:hidden"></div>\').appendTo(\'body\');
            }
                                
            $("#dialog").dialog({modal :true, width:540, height:400});
            
            // load remote content
            dialog.load(
                    url,                    
                    {},
                    function(responseText, textStatus, XMLHttpRequest) {
                        dialog.dialog();
                    }
                );
            //prevent the browser to follow the link
            return false;
        });
    </script>';
}


// Section for the tabs.
$this_section = SECTION_COURSES;

// Acces rights: anonymous users can't do anything usefull here.
api_block_anonymous_users();

if (!(api_is_platform_admin() || api_is_course_admin() || api_is_allowed_to_create_course())) {
	if (api_get_setting('allow_students_to_browse_courses') == 'false') {
		api_not_allowed();
	}
}

// filter actions
$actions = array('sortmycourses', 'createcoursecategory', 'subscribe', 'deletecoursecategory', 'unsubscribe', 'display_courses','display_random_courses');
$action = 'subscribe';
$nameTools = get_lang('SortMyCourses');

if (isset($_GET['action']) && in_array($_GET['action'],$actions)) {
	$action = $_GET['action'];
}

if ($action == 'createcoursecategory') {
	$nameTools = get_lang('CreateCourseCategory');
}
if ($action == 'subscribe') {
	$nameTools = get_lang('SubscribeToCourse');
}

// Breadcrumbs.
$interbreadcrumb[] = array('url' => api_get_path(WEB_PATH).'user_portal.php', 'name' => get_lang('MyCourses'));
if (empty($nameTools)) {
	$nameTools = get_lang('CourseManagement');
} else {
	$interbreadcrumb[] = array('url' => api_get_path(WEB_CODE_PATH).'auth/courses.php', 'name' => get_lang('CourseManagement'));
        $interbreadcrumb[] = array('url' => '#', 'name' => $nameTools);
}


// course description controller object
$courses_controller = new CoursesController();

$ctok = $_SESSION['sec_token'];

// We are moving a course or category of the user up/down the list (=Sort My Courses).
if (isset($_GET['move'])) {
	if (isset($_GET['course'])) {
		if ($ctok == $_GET['sec_token']) {
            $courses_controller->move_course($_GET['move'], $_GET['course'], $_GET['category']);
		}
	}
	if (isset($_GET['category']) && !$_GET['course']) {
		if ($ctok == $_GET['sec_token']) {
            $courses_controller->move_category($_GET['move'], $_GET['category']);
		}
	}
}

// We are moving the course of the user to a different user defined course category (=Sort My Courses).
if (isset($_POST['submit_change_course_category'])) {
    if ($ctok == $_POST['sec_token']) {
        $courses_controller->change_course_category($_POST['course_2_edit_category'], $_POST['course_categories']);
    }
}

// We edit course category
if (isset($_POST['submit_edit_course_category']) && isset($_POST['title_course_category']) && strlen(trim($_POST['title_course_category'])) > 0) {
	if ($ctok == $_POST['sec_token']) {
		$courses_controller->edit_course_category($_POST['title_course_category'], $_POST['edit_course_category']);
	}
}

// we are deleting a course category
if ($action == 'deletecoursecategory' && isset($_GET['id'])) {
	if ($ctok == $_GET['sec_token']) {
		$get_id_cat = intval($_GET['id']);
		$courses_controller->delete_course_category($get_id_cat);
	}
}

// We are unsubscribing from a course (=Unsubscribe from course).
if (isset($_POST['unsubscribe'])) {
	if ($ctok == $_POST['sec_token']) {
        $courses_controller->unsubscribe_user_from_course($_POST['unsubscribe']);
            //$message = remove_user_from_course($_user['user_id'], $_POST['unsubscribe']);
	}
}

// We are creating a new user defined course category (= Create Course Category).
if (isset($_POST['create_course_category']) && isset($_POST['title_course_category']) && strlen(trim($_POST['title_course_category'])) > 0) {
	if ($ctok == $_POST['sec_token']) {
        $courses_controller->add_course_category($_POST['title_course_category']);        
	}
}

// search courses
if (isset($_REQUEST['search_course'])) {
    //echo "<p><strong>".get_lang('SearchResultsFor')." ".api_htmlentities($_POST['search_term'], ENT_QUOTES, api_get_system_encoding())."</strong><br />";
    if ($ctok == $_REQUEST['sec_token']) {
        $courses_controller->search_courses($_REQUEST['search_term']);
    }
}

// subscribe user to course
if (isset($_GET['subscribe_course'])) {
    //if ($ctok == $_GET['sec_token'] || $_POST['token'] == $_GET['sec_token']) {
            $courses_controller->subscribe_user($_GET['subscribe_course'], $_GET['search_term'], $_GET['category_code']);
    //}
}

switch ($action) {
    case 'createcoursecategory':
        $courses_controller->categories_list($action);
        break;
    case 'deletecoursecategory':
    case 'sortmycourses':
        $courses_controller->courses_list($action);
        break;
    case 'subscribe':
    case 'display_random_courses':
        $courses_controller->courses_categories($action);
        break;
    case 'display_courses':
        $courses_controller->courses_categories($action, $_GET['category_code']);
        break;
}
