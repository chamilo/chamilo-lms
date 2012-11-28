<?php
/* For licensing terms, see /license.txt */

/**
 *	@package chamilo.tracking
 */

/* INIT SECTION */

$pathopen = isset($_REQUEST['pathopen']) ? $_REQUEST['pathopen'] : null;

// Language files that need to be included.
$language_file = array('admin', 'tracking', 'scorm', 'exercice');

// Including the global initialization file
require_once '../inc/global.inc.php';
$current_course_tool = TOOL_TRACKING;

$course_info = api_get_course_info(api_get_course_id());

if (!empty($course_info)) {
    //api_protect_course_script();
}

$from_myspace = false;
$from = isset($_GET['from']) ? $_GET['from'] : null;

if ($from == 'myspace') {
    $from_myspace = true;
    $this_section = "session_my_space";    
} else {
    $this_section = SECTION_COURSES;
}

// Access restrictions.
$is_allowedToTrack = api_is_platform_admin() || api_is_allowed_to_create_course() || api_is_session_admin() || api_is_drh() || api_is_course_tutor();

if (!$is_allowedToTrack) {    
    api_not_allowed();    
    exit;
}

// Including additional libraries.

require_once api_get_path(SYS_CODE_PATH).'newscorm/learnpath.class.php';
require_once api_get_path(SYS_CODE_PATH).'newscorm/learnpathItem.class.php';
require_once api_get_path(SYS_CODE_PATH).'newscorm/learnpathList.class.php';
require_once api_get_path(SYS_CODE_PATH).'newscorm/scorm.class.php';
require_once api_get_path(SYS_CODE_PATH).'newscorm/scormItem.class.php';
require_once api_get_path(LIBRARY_PATH).'export.lib.inc.php';
require_once api_get_path(LIBRARY_PATH).'statsUtils.lib.inc.php';
require_once api_get_path(SYS_CODE_PATH).'resourcelinker/resourcelinker.inc.php';
require_once api_get_path(SYS_CODE_PATH).'survey/survey.lib.php';
require_once api_get_path(SYS_CODE_PATH).'exercice/exercise.lib.php';

// Starting the output buffering when we are exporting the information.
$export_csv = isset($_GET['export']) && $_GET['export'] == 'csv' ? true : false;
$session_id = intval($_REQUEST['id_session']);

if ($export_csv) {
    if (!empty($session_id)) {
        $_SESSION['id_session'] = $session_id;
    }
    ob_start();
}
$csv_content = array();
// Scripts for reporting array hide / unhide columns
$js = "
    <script>
        // hide column and display the button to unhide it
        function foldup(in_id) {
            $('div#reporting_table table tr td:nth-child('+in_id+')').fadeToggle();
            $('div#reporting_table table tr th:nth-child('+in_id+')').fadeToggle();
            $('div#unhideButtons span:nth-child('+in_id+')').fadeToggle();
        }
        // add the red cross on top of each column
        function init_hide() {
            $('div#reporting_table table tr th').each(
                function(index) {
                    num_index = index + 1;
                    $(this).prepend('<div style=\"cursor:pointer\" onclick=\"foldup('+num_index+')\">".Display :: return_icon('visible.png', get_lang('HideColumn'), array('align' => 'absmiddle', 'hspace' => '3px'), 22)."</div>');                    
                 }
               )
             }
        // hide some column at startup
        // be sure that these columns always exists
        // see $tab_table_header = array();    // tab of header texts
        $(document).ready( function() {
            init_hide();
            foldup(1);foldup(9);foldup(10);foldup(11);foldup(12);
        })
    </script>";
        
$htmlHeadXtra[] = "<style type='text/css'>
/*<![CDATA[*/
.secLine {background-color : #E6E6E6;}
.content {padding-left : 15px;padding-right : 15px; }
.specialLink{color : #0000FF;}
/*]]>*/
/* Style for reporting array hide / unhide columns */
.unhide_button {
    cursor : pointer;
    border:1px solid black;
    background-color: #FAFAFA;
    padding: 5px;
    border-radius : 3px;
    margin-right:3px;
}
div#reporting_table table th {
  vertical-align:top;
}
</style>
<style media='print' type='text/css'>

</style>";
$htmlHeadXtra[] .= $js;

// Database table definitions.
//@todo remove this calls
$TABLETRACK_ACCESS      = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_LASTACCESS);
$TABLETRACK_LINKS       = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_LINKS);
$TABLETRACK_DOWNLOADS   = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_DOWNLOADS);
$TABLETRACK_ACCESS_2    = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ACCESS);
$TABLETRACK_EXERCISES 	= Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
$TABLECOURSUSER	        = Database::get_main_table(TABLE_MAIN_COURSE_USER);
$TABLECOURSE	        = Database::get_main_table(TABLE_MAIN_COURSE);
$table_user             = Database::get_main_table(TABLE_MAIN_USER);
$TABLEQUIZ              = Database::get_course_table(TABLE_QUIZ_TEST);

// Breadcrumbs.
if (isset($_GET['origin']) && $_GET['origin'] == 'resume_session') {
    $interbreadcrumb[] = array('url' => '../admin/index.php','name' => get_lang('PlatformAdmin'));
    $interbreadcrumb[] = array('url' => '../admin/session_list.php','name' => get_lang('SessionList'));
    $interbreadcrumb[] = array('url' => '../admin/resume_session.php?id_session='.api_get_session_id(), 'name' => get_lang('SessionOverview'));
}

$view = (isset($_REQUEST['view']) ? $_REQUEST['view'] : '');
$nameTools = get_lang('Tracking');

// Display the header.
Display::display_header($nameTools, 'Tracking');

// getting all the students of the course
if (empty($session_id)) {	
	// Registered students in a course outside session.
	$a_students = CourseManager :: get_student_list_from_course_code(api_get_course_id());	
} else {
	// Registered students in session.
	$a_students = CourseManager :: get_student_list_from_course_code(api_get_course_id(), true, api_get_session_id());    
}

$nbStudents = count($a_students);

// Gettting all the additional information of an additional profile field.
if (isset($_GET['additional_profile_field']) && is_numeric($_GET['additional_profile_field'])) {
    $user_array = array();
    foreach ($a_students as $key=>$item) {
        $user_array[] = $key;
    }
    // Fetching only the user that are loaded NOT ALL user in the portal.
    $additional_user_profile_info = TrackingCourseLog::get_addtional_profile_information_of_field_by_user($_GET['additional_profile_field'],$user_array);
    $extra_info = UserManager::get_extra_field_information($_GET['additional_profile_field']);    
}


/* MAIN CODE */

echo '<div class="actions" style="height:32px">';

echo Display::url(Display::return_icon('user.png', get_lang('StudentsTracking'), array(), 32), 'courseLog.php?'.api_get_cidreq());  
echo Display::url(Display::return_icon('course.png', get_lang('CourseTracking'), array(), 32), 'course_log_tools.php?'.api_get_cidreq());
echo Display::return_icon('tools_na.png', get_lang('ResourcesTracking'), array(), 32);

echo '<span style="float:right; padding-top:0px;">';
echo '<a href="javascript: void(0);" onclick="javascript: window.print();">'.Display::return_icon('printer.png', get_lang('Print'),'',ICON_SIZE_MEDIUM).'</a>';

if ($_GET['studentlist'] == '' || $_GET['studentlist'] == 'true') {
    $addional_param = '';
    if (isset($_GET['additional_profile_field'])) {
        $addional_param ='additional_profile_field='.intval($_GET['additional_profile_field']);
    }
    $users_tracking_per_page = '';
    if (isset($_GET['users_tracking_per_page'])) {
        $users_tracking_per_page= '&users_tracking_per_page='.intval($_GET['users_tracking_per_page']);
    }
    echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&export=csv&'.$addional_param.$users_tracking_per_page.'">
	'.Display::return_icon('export_csv.png', get_lang('ExportAsCSV'),'',ICON_SIZE_MEDIUM).'</a>';
}
echo '</span>';
echo '</div>';

// Create a search-box.
$form = new FormValidator('search_simple', 'get', api_get_path(WEB_CODE_PATH).'tracking/course_log_resources.php?'.api_get_cidreq(), '', 'width=200px', false);
$renderer =& $form->defaultRenderer();
$renderer->setElementTemplate('<span>{element}</span>');
$form->addElement('text', 'keyword', get_lang('keyword'));
$form->addElement('style_submit_button', 'submit', get_lang('SearchUsers'), 'class="search"');
echo '<div class="actions">';
$form->display();
echo '</div>';

$table = new SortableTable('resources', array('TrackingCourseLog', 'count_item_resources'), array('TrackingCourseLog', 'get_item_resources_data'), 5, 20, 'DESC');
$parameters = array();

if (isset($_GET['keyword'])) {
    $parameters['keyword'] = Security::remove_XSS($_GET['keyword']);
}

$parameters['studentlist'] = 'resources';

$table->set_additional_parameters($parameters);
$table->set_header(0, get_lang('Tool'));
$table->set_header(1, get_lang('EventType'));
$table->set_header(2, get_lang('Session'), false);
$table->set_header(3, get_lang('UserName'), true, 'width=65px');
$table->set_header(4, get_lang('Document'), false);
$table->set_header(5, get_lang('Date'), true, 'width=190px');
$table->display();

Display::display_footer();