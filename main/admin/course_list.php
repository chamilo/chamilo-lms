<?php
/* For licensing terms, see /license.txt */

/**
 * This script shows a list of courses and allows searching for courses codes
 * and names
 * @package chamilo.admin
 */

/*	INIT SECTION	*/

// Language files that need to be included.
$language_file = array('admin', 'courses');
$cidReset = true;
require_once '../inc/global.inc.php';
$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();
require_once api_get_path(LIBRARY_PATH).'course.lib.php';
require_once api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php';
require_once '../gradebook/lib/be/gradebookitem.class.php';
require_once '../gradebook/lib/be/category.class.php';

/**
 * Get the number of courses which will be displayed
 */
function get_number_of_courses() {
    $course_table = Database :: get_main_table(TABLE_MAIN_COURSE);
    $sql = "SELECT COUNT(code) AS total_number_of_items FROM $course_table";

    global $_configuration;
    if ((api_is_platform_admin() || api_is_session_admin()) && $_configuration['multiple_access_urls'] && api_get_current_access_url_id() != -1) {
        $access_url_rel_course_table = Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
        $sql.= " INNER JOIN $access_url_rel_course_table url_rel_course ON (code=url_rel_course.course_code)";
    }

    if (isset ($_GET['keyword'])) {
        $keyword = Database::escape_string($_GET['keyword']);
        $sql .= " WHERE (title LIKE '%".$keyword."%' OR code LIKE '%".$keyword."%' OR visual_code LIKE '%".$keyword."%')";
    }
    elseif (isset ($_GET['keyword_code'])) {
        $keyword_code = Database::escape_string($_GET['keyword_code']);
        $keyword_title = Database::escape_string($_GET['keyword_title']);
        $keyword_category = Database::escape_string($_GET['keyword_category']);
        $keyword_language = Database::escape_string($_GET['keyword_language']);
        $keyword_visibility = Database::escape_string($_GET['keyword_visibility']);
        $keyword_subscribe = Database::escape_string($_GET['keyword_subscribe']);
        $keyword_unsubscribe = Database::escape_string($_GET['keyword_unsubscribe']);
        $sql .= " WHERE (code LIKE '%".$keyword_code."%' OR visual_code LIKE '%".$keyword_code."%') AND title LIKE '%".$keyword_title."%' AND category_code LIKE '%".$keyword_category."%'  AND course_language LIKE '%".$keyword_language."%'   AND visibility LIKE '%".$keyword_visibility."%'    AND subscribe LIKE '".$keyword_subscribe."'AND unsubscribe LIKE '".$keyword_unsubscribe."'";
    }

     // adding the filter to see the user's only of the current access_url
    if ((api_is_platform_admin() || api_is_session_admin()) && $_configuration['multiple_access_urls'] && api_get_current_access_url_id() != -1) {
        $sql.= " AND url_rel_course.access_url_id=".api_get_current_access_url_id();
    }

    $res = Database::query($sql);
    $obj = Database::fetch_object($res);
    return $obj->total_number_of_items;
}

/**
 * Get course data to display
 */
function get_course_data($from, $number_of_items, $column, $direction) {
    $course_table = Database :: get_main_table(TABLE_MAIN_COURSE);
    $users_table = Database :: get_main_table(TABLE_MAIN_USER);
    $course_users_table = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
    $sql = "SELECT code AS col0, visual_code AS col1, title AS col2, course_language AS col3, category_code AS col4, subscribe AS col5, unsubscribe AS col6, tutor_name as col7, code AS col8, visibility AS col9,directory as col10 
    		FROM $course_table";
    //$sql = "SELECT code AS col0, visual_code AS col1, title AS col2, course_language AS col3, category_code AS col4, subscribe AS col5, unsubscribe AS col6, code AS col7, tutor_name as col8, code AS col9, visibility AS col10,directory as col11 FROM $course_table";
    global $_configuration;
    
    if ((api_is_platform_admin() || api_is_session_admin()) && $_configuration['multiple_access_urls'] && api_get_current_access_url_id() != -1) {
        $access_url_rel_course_table = Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
        $sql.= " INNER JOIN $access_url_rel_course_table url_rel_course ON (code=url_rel_course.course_code)";
    }

    if (isset ($_GET['keyword'])) {
        $keyword = Database::escape_string(trim($_GET['keyword']));
        $sql .= " WHERE (title LIKE '%".$keyword."%' OR code LIKE '%".$keyword."%' OR visual_code LIKE '%".$keyword."%' ) ";
    } elseif (isset ($_GET['keyword_code'])) {
        $keyword_code           = Database::escape_string($_GET['keyword_code']);
        $keyword_title          = Database::escape_string($_GET['keyword_title']);
        $keyword_category       = Database::escape_string($_GET['keyword_category']);
        $keyword_language       = Database::escape_string($_GET['keyword_language']);
        $keyword_visibility     = Database::escape_string($_GET['keyword_visibility']);
        $keyword_subscribe      = Database::escape_string($_GET['keyword_subscribe']);
        $keyword_unsubscribe    = Database::escape_string($_GET['keyword_unsubscribe']);
        $sql .= " WHERE (code LIKE '%".$keyword_code."%' OR visual_code LIKE '%".$keyword_code."%') AND title LIKE '%".$keyword_title."%' AND category_code LIKE '%".$keyword_category."%'  AND course_language LIKE '%".$keyword_language."%'   AND visibility LIKE '%".$keyword_visibility."%'    AND subscribe LIKE '".$keyword_subscribe."'AND unsubscribe LIKE '".$keyword_unsubscribe."'";
    }

    // Adding the filter to see the user's only of the current access_url.
    if ((api_is_platform_admin() || api_is_session_admin()) && $_configuration['multiple_access_urls'] && api_get_current_access_url_id() != -1) {
        $sql.= " AND url_rel_course.access_url_id=".api_get_current_access_url_id();
    }

    $sql .= " ORDER BY col$column $direction ";
    $sql .= " LIMIT $from,$number_of_items";

    $res = Database::query($sql);
    $courses = array ();
    while ($course = Database::fetch_row($res)) {
        // Place colour icons in front of courses.
        //$course[1] = '<nobr>'.get_course_visibility_icon($course[9]).'<a href="'.api_get_path(WEB_COURSE_PATH).$course[9].'/index.php">'.$course[1].'</a></nobr>';
        $course[1] = '<nobr>'.get_course_visibility_icon($course[9]).'<a href="'.api_get_path(WEB_COURSE_PATH).$course[10].'/index.php">'.$course[1].'</a></nobr>';
        $course[5] = $course[5] == SUBSCRIBE_ALLOWED ? get_lang('Yes') : get_lang('No');
        $course[6] = $course[6] == UNSUBSCRIBE_ALLOWED ? get_lang('Yes') : get_lang('No');

        $course_rem = array($course[0], $course[1], $course[2], $course[3], $course[4], $course[5], $course[6], $course[7], $course[8]);
        $courses[] = $course_rem;
    }
    return $courses;
}

/**
 * Filter to display the edit-buttons
 */
function modify_filter($code) {
	$icourse = api_get_course_info($code);
        return
        '<a href="course_information.php?code='.$code.'">'.Display::return_icon('synthese_view.gif', get_lang('Info')).'</a>&nbsp;'.
        //'<a href="../course_home/course_home.php?cidReq='.$code.'">'.Display::return_icon('course_home.gif', get_lang('CourseHomepage')).'</a>&nbsp;'. // This is not the preferable way to go to the homepage.
        '<a href="'.api_get_path(WEB_COURSE_PATH).$icourse['path'].'/index.php">'.Display::return_icon('course_home.gif', get_lang('CourseHomepage')).'</a>&nbsp;'.
        '<a href="../tracking/courseLog.php?cidReq='.$code.'">'.Display::return_icon('statistics.gif', get_lang('Tracking')).'</a>&nbsp;'.
        '<a href="course_edit.php?course_code='.$code.'">'.Display::return_icon('edit.png', get_lang('Edit'), array(), 22).'</a>&nbsp;'.        
        '<a href="../coursecopy/backup.php?cidReq='.$code.'">'.Display::return_icon('backup.gif', get_lang('CreateBackup')).'</a>&nbsp;'.
        '<a href="course_list.php?delete_course='.$code.'"  onclick="javascript: if (!confirm('."'".addslashes(api_htmlentities(get_lang('ConfirmYourChoice'), ENT_QUOTES))."'".')) return false;">'.Display::return_icon('delete.png', get_lang('Delete'), array(), 22).'</a>';
}

/**
 * Return an icon representing the visibility of the course
 */
function get_course_visibility_icon($v) {
    $path = api_get_path(REL_CODE_PATH);
    $style = 'margin-bottom:-5px;margin-right:5px;';
    switch($v) {
        case 0:
            return Display::return_icon('bullet_red.gif', get_lang('CourseVisibilityClosed'), array('style' => $style));
            break;
        case 1:
            return Display::return_icon('bullet_orange.gif', get_lang('Private'), array('style' => $style));
            break;
        case 2:
            return Display::return_icon('bullet_green.gif', get_lang('OpenToThePlatform'), array('style' => $style));
            break;
        case 3:
            return Display::return_icon('bullet_blue.gif', get_lang('OpenToTheWorld'), array('style' => $style));
            break;
        default:
            return '';
    }
}

if (isset ($_POST['action'])) {
    switch ($_POST['action']) {
        // Delete selected courses
        case 'delete_courses' :
            $course_codes = $_POST['course'];
            if (count($course_codes) > 0) {
                foreach ($course_codes as $index => $course_code) {
                    CourseManager :: delete_course($course_code);
                    $obj_cat=new Category();
                    $obj_cat->update_category_delete($course_code);
                }
            }
            break;
    }
}
$content = '';
$message = '';
$actions = '';

if (isset ($_GET['search']) && $_GET['search'] == 'advanced') {
    // Get all course categories
    $table_course_category = Database :: get_main_table(TABLE_MAIN_CATEGORY);
    $interbreadcrumb[] = array('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));
    $interbreadcrumb[] = array('url' => 'course_list.php', 'name' => get_lang('CourseList'));
    $tool_name = get_lang('SearchACourse');
    
    //api_display_tool_title($tool_name);
    $form = new FormValidator('advanced_course_search', 'get');
    $form->addElement('header', '', $tool_name);
    $form->add_textfield('keyword_code', get_lang('CourseCode'), false);
    $form->add_textfield('keyword_title', get_lang('Title'), false);
    $categories = array();
    $categories_select = $form->addElement('select', 'keyword_category', get_lang('CourseFaculty'), $categories);
    CourseManager::select_and_sort_categories($categories_select);
    $el = & $form->addElement('select_language', 'keyword_language', get_lang('CourseLanguage'));
    $el->addOption(get_lang('All'), '%');
    $form->addElement('radio', 'keyword_visibility', get_lang("CourseAccess"), get_lang('OpenToTheWorld'), COURSE_VISIBILITY_OPEN_WORLD);
    $form->addElement('radio', 'keyword_visibility', null, get_lang('OpenToThePlatform'), COURSE_VISIBILITY_OPEN_PLATFORM);
    $form->addElement('radio', 'keyword_visibility', null, get_lang('Private'), COURSE_VISIBILITY_REGISTERED);
    $form->addElement('radio', 'keyword_visibility', null, get_lang('CourseVisibilityClosed'), COURSE_VISIBILITY_CLOSED);
    $form->addElement('radio', 'keyword_visibility', null, get_lang('All'), '%');
    $form->addElement('radio', 'keyword_subscribe', get_lang('Subscription'), get_lang('Allowed'), 1);
    $form->addElement('radio', 'keyword_subscribe', null, get_lang('Denied'), 0);
    $form->addElement('radio', 'keyword_subscribe', null, get_lang('All'), '%');
    $form->addElement('radio', 'keyword_unsubscribe', get_lang('Unsubscription'), get_lang('AllowedToUnsubscribe'), 1);
    $form->addElement('radio', 'keyword_unsubscribe', null, get_lang('NotAllowedToUnsubscribe'), 0);
    $form->addElement('radio', 'keyword_unsubscribe', null, get_lang('All'), '%');
    $form->addElement('style_submit_button', 'submit', get_lang('SearchCourse'),'class="search"');
    $defaults['keyword_language'] = '%';
    $defaults['keyword_visibility'] = '%';
    $defaults['keyword_subscribe'] = '%';
    $defaults['keyword_unsubscribe'] = '%';
    $form->setDefaults($defaults);
    $content .= $form->return_form();
} else {
    $interbreadcrumb[] = array ('url' => 'index.php', "name" => get_lang('PlatformAdmin'));
    $tool_name = get_lang('CourseList');
    
    if (isset($_GET['action'])) {
        switch ($_GET['action']) {
            case 'show_msg':
                if (!empty($_GET['warn'])) {
                    $message = Display::return_message(urldecode($_GET['warn']), 'warning');
                }
                if (!empty($_GET['msg'])) {
                    $message = Display::return_message(urldecode($_GET['msg']));
                }
                break;
            default:
                break;
        }
    }
    if (isset ($_GET['delete_course'])) {
        CourseManager :: delete_course($_GET['delete_course']);
        $obj_cat = new Category();
        $obj_cat->update_category_delete($_GET['delete_course']);

    }
    // Create a search-box
    $form = new FormValidator('search_simple', 'get', '', '', 'width=200px', false);
    $renderer =& $form->defaultRenderer();
    $renderer->setElementTemplate('<span>{element}</span> ');
    $form->addElement('text', 'keyword', get_lang('keyword'));
    $form->addElement('style_submit_button', 'submit', get_lang('SearchCourse'), 'class="search"');
    $form->addElement('static', 'search_advanced_link', null, '<a href="course_list.php?search=advanced">'.get_lang('AdvancedSearch').'</a>');
    
    $actions .= '<div style="float: right; margin-top: 5px; margin-right: 5px;">';    
    $actions .= '<a href="course_add.php">'.Display::return_icon('new_course.png', get_lang('AddCourse'),'','32').'</a> ';
    
    if (api_get_setting('course_validation') == 'true') {    
        $actions .= '<a href="course_request_review.php">'.Display::return_icon('course_request_pending.png', get_lang('ReviewCourseRequests'),'','32').'</a>';
    }
    $actions .= '</div>';    
    $actions .= $form->return_form();
    
    // Create a sortable table with the course data
    $table = new SortableTable('courses', 'get_number_of_courses', 'get_course_data', 2);
    $parameters=array();

    if (isset ($_GET['keyword'])) {
        $parameters = array ('keyword' => Security::remove_XSS($_GET['keyword']));
    } elseif (isset ($_GET['keyword_code'])) {
        $parameters['keyword_code'] 		= Security::remove_XSS($_GET['keyword_code']);
        $parameters['keyword_title'] 		= Security::remove_XSS($_GET['keyword_title']);
        $parameters['keyword_category'] 	= Security::remove_XSS($_GET['keyword_category']);
        $parameters['keyword_language'] 	= Security::remove_XSS($_GET['keyword_language']);
        $parameters['keyword_visibility'] 	= Security::remove_XSS($_GET['keyword_visibility']);
        $parameters['keyword_subscribe'] 	= Security::remove_XSS($_GET['keyword_subscribe']);
        $parameters['keyword_unsubscribe'] 	= Security::remove_XSS($_GET['keyword_unsubscribe']);
    }

    $table->set_additional_parameters($parameters);

    $table->set_header(0, '', false, 'width="8px"');
    $table->set_header(1, get_lang('Code'));
    $table->set_header(2, get_lang('Title'));
    $table->set_header(3, get_lang('Language'), true, 'width="70px"');
    $table->set_header(4, get_lang('Category'));
    $table->set_header(5, get_lang('SubscriptionAllowed'), true, 'width="60px"');
    $table->set_header(6, get_lang('UnsubscriptionAllowed'), false, 'width="50px"');
    //$table->set_header(7, get_lang('IsVirtualCourse'));
    $table->set_header(7, get_lang('Teacher'));
    $table->set_header(8, get_lang('Action'), false, 'width="150px"');
    $table->set_column_filter(8, 'modify_filter');
    $table->set_form_actions(array('delete_courses' => get_lang('DeleteCourse')), 'course');
    $content .= $table->return_table();
}

$tpl = new Template($tool_name);
$tpl->assign('actions', $actions);
$tpl->assign('message', $message);
$tpl->assign('content', $content);
$tpl->display_one_col_template();