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

$TABLEQUIZ              = Database::get_course_table(TABLE_QUIZ_TEST);

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


/* MAIN CODE */

echo '<div class="actions">';

echo Display::url(Display::return_icon('user.png', get_lang('StudentsTracking'), array(), 32), 'courseLog.php?'.api_get_cidreq());        
echo Display::return_icon('course_na.png', get_lang('CourseTracking'), array(), 32);
echo Display::url(Display::return_icon('tools.png', get_lang('ResourcesTracking'), array(), 32), 'course_log_resources.php?'.api_get_cidreq());        

echo '<span style="float:right; padding-top:0px;">';
echo '<a href="javascript: void(0);" onclick="javascript: window.print();">'.Display::return_icon('printer.png', get_lang('Print'),'',ICON_SIZE_MEDIUM).'</a>';

echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&id_session='.api_get_session_id().'&export=csv">
	'.Display::return_icon('export_csv.png', get_lang('ExportAsCSV'),'',ICON_SIZE_MEDIUM).'</a>';	

echo '</span>';
echo '</div>';

$course_code = api_get_course_id();

$list = new LearnpathList(null, $course_code, $session_id);

$flat_list = $list->get_flat_list();
    
    
if (count($flat_list) > 0) {

    // learning path tracking
    echo '<div class="report_section">
            '.Display::page_subheader(Display::return_icon('scorms.gif',get_lang('AverageProgressInLearnpath')).get_lang('AverageProgressInLearnpath')).'
            <table class="data_table">';
    
    if ($export_csv) {
        $temp = array(get_lang('AverageProgressInLearnpath', ''), '');
        $csv_content[] = array('', '');
        $csv_content[] = $temp;
    }

    foreach ($flat_list as $lp_id => $lp) {
        $lp_avg_progress = 0;
        foreach ($a_students as $student_id => $student) {
            // get the progress in learning pathes
            $lp_avg_progress += Tracking::get_avg_student_progress($student_id, $course_code, array($lp_id), $session_id);                
        }
        if ($nbStudents > 0) {
            $lp_avg_progress = $lp_avg_progress / $nbStudents;
        } else {
            $lp_avg_progress = null;
        }
        // Separated presentation logic.
        if (is_null($lp_avg_progress)) {
            $lp_avg_progress = '0%';
        } else {
            $lp_avg_progress = round($lp_avg_progress, 1).'%';
        }
        echo '<tr><td>'.$lp['lp_name'].'</td><td align="right">'.$lp_avg_progress.'</td></tr>';
        if ($export_csv) {
            $temp = array($lp['lp_name'], $lp_avg_progress);
            $csv_content[] = $temp;
        }
    }
    echo '</table></div>';
} else {        
    if ($export_csv) {
        $temp = array(get_lang('NoLearningPath', ''), '');
        $csv_content[] = $temp;
    }
}   

// Exercices tracking.
echo '<div class="report_section">
           '.Display::page_subheader(Display::return_icon('quiz.gif',get_lang('AverageResultsToTheExercices')).get_lang('AverageResultsToTheExercices')).'
        <table class="data_table">';

$course_id = api_get_course_int_id();

$sql = "SELECT id, title FROM $TABLEQUIZ 
        WHERE c_id = $course_id AND active <> -1 AND session_id = $session_id";
$rs = Database::query($sql);

if ($export_csv) {
    $temp = array(get_lang('AverageProgressInLearnpath'), '');
    $csv_content[] = array('', '');
    $csv_content[] = $temp;
}

$course_path_params = '&cidReq='.$course_code.'&id_session='.$session_id;

if (Database::num_rows($rs) > 0) {
    $student_ids = array_keys($a_students);
    $count_students = count($student_ids);
    while ($quiz = Database::fetch_array($rs)) {
        $quiz_avg_score = 0;
        if ($count_students > 0) {
            foreach ($student_ids as $student_id) {
                $avg_student_score = Tracking::get_avg_student_exercise_score($student_id, $course_code, $quiz['id'], $session_id);                    
                $quiz_avg_score += $avg_student_score;
            }
        }
        $count_students = ($count_students == 0 || is_null($count_students) || $count_students == '') ? 1 : $count_students;
        $quiz_avg_score = round(($quiz_avg_score / $count_students), 2).'%';
        $url = api_get_path(WEB_CODE_PATH).'exercice/overview.php?exerciseId='.$quiz['id'].$course_path_params;

        echo '<tr><td>'.Display::url($quiz['title'], $url).'</td><td align="right">'.$quiz_avg_score.'</td></tr>';
        if ($export_csv) {
            $temp = array($quiz['title'], $quiz_avg_score);
            $csv_content[] = $temp;
        }
    }
} else {
    echo '<tr><td>'.get_lang('NoExercises').'</td></tr>';
    if ($export_csv) {
        $temp = array(get_lang('NoExercises', ''), '');
        $csv_content[] = $temp;
    }
}    
echo '</table></div>';
echo '<div class="clear"></div>';

// Forums tracking.
echo '<div class="report_section">
        '.Display::page_subheader(Display::return_icon('forum.gif', get_lang('Forum')).get_lang('Forum').'&nbsp;-&nbsp;<a href="../forum/index.php?cidReq='.$_course['id'].'">'.get_lang('SeeDetail').'</a>').
        '<table class="data_table">';
$count_number_of_posts_by_course    = Tracking :: count_number_of_posts_by_course($course_code, $session_id);
$count_number_of_forums_by_course   = Tracking :: count_number_of_forums_by_course($course_code, $session_id);
$count_number_of_threads_by_course  = Tracking :: count_number_of_threads_by_course($course_code, $session_id);
if ($export_csv) {
    $csv_content[] = array(get_lang('Forum'), '');
    $csv_content[] = array(get_lang('ForumForumsNumber', ''), $count_number_of_forums_by_course);
    $csv_content[] = array(get_lang('ForumThreadsNumber', ''), $count_number_of_threads_by_course);
    $csv_content[] = array(get_lang('ForumPostsNumber', ''), $count_number_of_posts_by_course);
}
echo '<tr><td>'.get_lang('ForumForumsNumber').'</td><td align="right">'.$count_number_of_forums_by_course.'</td></tr>';
echo '<tr><td>'.get_lang('ForumThreadsNumber').'</td><td align="right">'.$count_number_of_threads_by_course.'</td></tr>';
echo '<tr><td>'.get_lang('ForumPostsNumber').'</td><td align="right">'.$count_number_of_posts_by_course.'</td></tr>';
echo '</table></div>';
echo '<div class="clear"></div>';

// Chat tracking.

echo '<div class="report_section">
        '.Display::page_subheader(Display::return_icon('chat.gif',get_lang('Chat')).get_lang('Chat')).'
        <table class="data_table">';
$chat_connections_during_last_x_days_by_course = Tracking::chat_connections_during_last_x_days_by_course($course_code, 7, $session_id);
if ($export_csv) {
    $csv_content[] = array(get_lang('Chat', ''), '');
    $csv_content[] = array(sprintf(get_lang('ChatConnectionsDuringLastXDays', ''), '7'), $chat_connections_during_last_x_days_by_course);
}
echo '<tr><td>'.sprintf(get_lang('ChatConnectionsDuringLastXDays'), '7').'</td><td align="right">'.$chat_connections_during_last_x_days_by_course.'</td></tr>';

echo '</table></div>';
echo '<div class="clear"></div>';

// Tools tracking.
echo '<div class="report_section">
            '.Display::page_subheader(Display::return_icon('acces_tool.gif', get_lang('ToolsMostUsed')).get_lang('ToolsMostUsed')).'
        <table class="data_table">';

$tools_most_used = Tracking::get_tools_most_used_by_course($course_code, $session_id);    

if ($export_csv) {
    $temp = array(get_lang('ToolsMostUsed'), '');
    $csv_content[] = $temp;
}

if (!empty($tools_most_used)) {
    foreach ($tools_most_used as $row) {
        echo '	<tr>
                    <td>'.get_lang(ucfirst($row['access_tool'])).'</td>
                    <td align="right">'.$row['count_access_tool'].' '.get_lang('Clicks').'</td>
                </tr>';
        if ($export_csv) {
            $temp = array(get_lang(ucfirst($row['access_tool']), ''), $row['count_access_tool'].' '.get_lang('Clicks', ''));
            $csv_content[] = $temp;
        }
    }
}

echo '</table></div>';
echo '<div class="clear"></div>';

// Documents tracking.
if (!isset($_GET['num']) || empty($_GET['num'])) {
    $num = 3;
    $link = '&nbsp;-&nbsp;<a href="'.api_get_self().'?'.api_get_cidreq().'&num=1#documents_tracking">'.get_lang('SeeDetail').'</a>';
} else {
    $num = 1000;
    $link = '&nbsp;-&nbsp;<a href="'.api_get_self().'?'.api_get_cidreq().'&num=0#documents_tracking">'.get_lang('ViewMinus').'</a>';
}

 echo '<a name="documents_tracking" id="a"></a><div class="report_section">
            '.Display::page_subheader(Display::return_icon('documents.gif',get_lang('DocumentsMostDownloaded')).'&nbsp;'.get_lang('DocumentsMostDownloaded').$link).'
        <table class="data_table">';

$documents_most_downloaded = Tracking::get_documents_most_downloaded_by_course($course_code, $session_id, $num);

if ($export_csv) {
    $temp = array(get_lang('DocumentsMostDownloaded', ''), '');
    $csv_content[] = array('', '');
    $csv_content[] = $temp;
}

if (!empty($documents_most_downloaded)) {        
    foreach ($documents_most_downloaded as $row) {
        echo '<tr>
                <td>'.Display::url($row['down_doc_path'], api_get_path(WEB_CODE_PATH).'document/show_content.php?file='.$row['down_doc_path'].$course_path_params).'</td>
                <td align="right">'.$row['count_down'].' '.get_lang('Clicks').'</td>
              </tr>';
        if ($export_csv) {
            $temp = array($row['down_doc_path'], $row['count_down'].' '.get_lang('Clicks', ''));
            $csv_content[] = $temp;
        }
    }
} else {
    echo '<tr><td>'.get_lang('NoDocumentDownloaded').'</td></tr>';
    if ($export_csv) {
        $temp = array(get_lang('NoDocumentDownloaded', ''), '');
        $csv_content[] = $temp;
    }
}
echo '</table></div>';

echo '<div class="clear"></div>';

// links tracking
 echo '<div class="report_section">
            '.Display::page_subheader(Display::return_icon('link.gif',get_lang('LinksMostClicked')).'&nbsp;'.get_lang('LinksMostClicked')).'
        <table class="data_table">';

$links_most_visited = Tracking::get_links_most_visited_by_course($course_code, $session_id);

if ($export_csv) {
    $temp = array(get_lang('LinksMostClicked'), '');
    $csv_content[] = array('', '');
    $csv_content[] = $temp;
}

if (!empty($links_most_visited)) {
    foreach ($links_most_visited as $row) {
        echo '	<tr>
                    <td>'.Display::url($row['title'].' ('.$row['url'].')', $row['url']).'</td>
                    <td align="right">'.$row['count_visits'].' '.get_lang('Clicks').'</td>
                </tr>';
        if ($export_csv){
            $temp = array($row['title'], $row['count_visits'].' '.get_lang('Clicks', ''));
            $csv_content[] = $temp;
        }
    }
} else {
    echo '<tr><td>'.get_lang('NoLinkVisited').'</td></tr>';
    if ($export_csv) {
        $temp = array(get_lang('NoLinkVisited'), '');
        $csv_content[] = $temp;
    }
}
echo '</table></div>';
echo '<div class="clear"></div>';

// send the csv file if asked
if ($export_csv) {
    ob_end_clean();
    Export :: export_table_csv($csv_content, 'reporting_course_tools');
    exit;
}

Display::display_footer();