<?php
/* For licensing terms, see /license.txt */

/**
 *	@package chamilo.tracking
 */

/* INIT SECTION */

$pathopen = isset($_REQUEST['pathopen']) ? $_REQUEST['pathopen'] : null;

// Language files that need to be included.
$language_file = array('admin', 'tracking','scorm');

// Including the global initialization file
require_once '../inc/global.inc.php';

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

$htmlHeadXtra[] = "<style type='text/css'>
/*<![CDATA[*/
.secLine {background-color : #E6E6E6;}
.content {padding-left : 15px;padding-right : 15px; }
.specialLink{color : #0000FF;}
/*]]>*/
</style>
<style media='print' type='text/css'>

</style>";

// Database table definitions.
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

if (empty($_GET['studentlist'])) {
    $_GET['studentlist'] = 'true';
}

switch($_GET['studentlist']) {
    case 'true':
        echo Display::return_icon('user_na.png', get_lang('StudentsTracking'), array(), 32);        
        echo Display::url(Display::return_icon('course.png', get_lang('CourseTracking'), array(), 32), 'courseLog.php?'.api_get_cidreq().'&studentlist=false');     
        echo '<a href="courseLog.php?'.api_get_cidreq().'&studentlist=resources">'.Display::return_icon('tools.png', get_lang('ResourcesTracking'), array(), 32).'</a>';
        break;
    case 'false':
        echo Display::url(Display::return_icon('user.png', get_lang('StudentsTracking'), array(), 32), 'courseLog.php?'.api_get_cidreq().'&studentlist=true');        
        echo Display::return_icon('course_na.png', get_lang('CourseTracking'), array(), 32);
        echo Display::url(Display::return_icon('tools.png', get_lang('ResourcesTracking'), array(), 32), 'courseLog.php?'.api_get_cidreq().'&studentlist=resources');
        break;
    case 'resources':        
        echo Display::url(Display::return_icon('user.png', get_lang('StudentsTracking'), array(), 32), 'courseLog.php?'.api_get_cidreq().'&studentlist=true');  
        echo Display::url(Display::return_icon('course.png', get_lang('CourseTracking'), array(), 32), 'courseLog.php?'.api_get_cidreq().'&studentlist=false');
        echo Display::return_icon('tools_na.png', get_lang('ResourcesTracking'), array(), 32);
        break;            
}

echo '<span style="float:right; padding-top:0px;">';
echo '<a href="javascript: void(0);" onclick="javascript: window.print();">'.Display::return_icon('printer.png', get_lang('Print'),'','32').'</a>';

if ($_GET['studentlist'] == 'false') {
    echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&id_session='.api_get_session_id().'&export=csv&studentlist=false">
	'.Display::return_icon('export_csv.png', get_lang('ExportAsCSV'),'','32').'</a>';	
} elseif ($_GET['studentlist'] == '' || $_GET['studentlist'] == 'true') {
    $addional_param = '';
    if (isset($_GET['additional_profile_field'])) {
        $addional_param ='additional_profile_field='.intval($_GET['additional_profile_field']);
    }
    $users_tracking_per_page = '';
    if (isset($_GET['users_tracking_per_page'])) {
        $users_tracking_per_page= '&users_tracking_per_page='.intval($_GET['users_tracking_per_page']);
    }
    echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&export=csv&'.$addional_param.$users_tracking_per_page.'">
	'.Display::return_icon('export_csv.png', get_lang('ExportAsCSV'),'','32').'</a>';
}

echo '</span>';

echo '</div>';


//Actions
if ($_GET['studentlist'] == '' || $_GET['studentlist'] == 'true') {
    echo '<div class="actions">';    
    // Create a search-box.
    $form_search = new FormValidator('search_simple', 'get', api_get_path(WEB_CODE_PATH).'tracking/courseLog.php?'.api_get_cidreq().'&studentlist=true', '', 'width=200px', false);
    $renderer =& $form_search->defaultRenderer();
    $renderer->setElementTemplate('<span>{element}</span>');
    $form_search->addElement('hidden', 'studentlist', 'true');
    $form_search->addElement('hidden', 'from', Security::remove_XSS($from));
    $form_search->addElement('hidden', 'session_id', api_get_session_id());
    $form_search->addElement('text', 'user_keyword');
    $form_search->addElement('style_submit_button', 'submit', get_lang('SearchUsers'), 'class="search"');    
    $form_search->display();
    echo '</div>';
}

if ($_GET['studentlist'] == 'false') {
    $course_code = api_get_course_id();

    echo'<br /><br />';

    if (count($flat_list) > 0) {
        
        // learning path tracking
        echo '<div class="report_section">
                <h2>'.Display::return_icon('scorms.gif',get_lang('AverageProgressInLearnpath')).get_lang('AverageProgressInLearnpath').'</h2>
                <table class="data_table">';
    
        $list = new LearnpathList('', $course_code, $session_id);
        
        $flat_list = $list->get_flat_list();
    
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
        //echo '<tr><td>'.get_lang('NoLearningPath').'</td></tr>';
        if ($export_csv) {
            $temp = array(get_lang('NoLearningPath', ''), '');
            $csv_content[] = $temp;
        }
    }

    
    
    // Exercices tracking.
    echo '<div class="report_section">
                <h2>'.Display::return_icon('quiz.gif',get_lang('AverageResultsToTheExercices')).get_lang('AverageResultsToTheExercices').'</h2>
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
            echo '<tr><td>'.$quiz['title'].'</td><td align="right">'.$quiz_avg_score.'</td></tr>';
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
            <h2>'.Display::return_icon('forum.gif', get_lang('Forum')).get_lang('Forum').'&nbsp;-&nbsp;<a href="../forum/index.php?cidReq='.$_course['id'].'">'.get_lang('SeeDetail').'</a></h2>
            <table class="data_table">';
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
            <h2>'.Display::return_icon('chat.gif',get_lang('Chat')).get_lang('Chat').'</h2>
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
                <h2>'.Display::return_icon('acces_tool.gif', get_lang('ToolsMostUsed')).get_lang('ToolsMostUsed').'</h2>
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
        $link = '&nbsp;-&nbsp;<a href="'.api_get_self().'?'.api_get_cidreq().'&studentlist=false&num=1#documents_tracking">'.get_lang('SeeDetail').'</a>';
    } else {
        $num = 1000;
        $link = '&nbsp;-&nbsp;<a href="'.api_get_self().'?'.api_get_cidreq().'&studentlist=false&num=0#documents_tracking">'.get_lang('ViewMinus').'</a>';
    }

     echo '<a name="documents_tracking" id="a"></a><div class="report_section">
                <h2>'.Display::return_icon('documents.gif',get_lang('DocumentsMostDownloaded')).'&nbsp;'.get_lang('DocumentsMostDownloaded').$link.'</h2>
            <table class="data_table">';

    $documents_most_downloaded = Tracking::get_documents_most_downloaded_by_course($course_code, $session_id, $num);

    if ($export_csv) {
        $temp = array(get_lang('DocumentsMostDownloaded', ''), '');
        $csv_content[] = array('', '');
        $csv_content[] = $temp;
    }

    if (!empty($documents_most_downloaded)) {
        foreach ($documents_most_downloaded as $row) {
            echo '	<tr>
                        <td>'.$row['down_doc_path'].'</td>
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
                <h2>'.Display::return_icon('link.gif',get_lang('LinksMostClicked')).'&nbsp;'.get_lang('LinksMostClicked').'</h2>
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
                        <td>'.$row['title'].'</td>
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
        Export :: export_table_csv($csv_content, 'reporting_course_tracking');
        exit;
    }
} elseif ($_GET['studentlist'] == 'true' or $_GET['studentlist'] == '') {
    // BEGIN : form to remind inactives susers
        
    if (count($a_students) > 0) {
        
        $form = new FormValidator('reminder_form', 'get', api_get_path(REL_CODE_PATH).'announcements/announcements.php');
    
        $renderer = $form->defaultRenderer();
        $renderer->setElementTemplate('<span>{label} {element}</span>&nbsp;<button class="save" type="submit">'.get_lang('SendNotification').'</button>','since');
    
        $options = array (
            2 => '2 '.get_lang('Days'),
            3 => '3 '.get_lang('Days'),
            4 => '4 '.get_lang('Days'),
            5 => '5 '.get_lang('Days'),
            6 => '6 '.get_lang('Days'),
            7 => '7 '.get_lang('Days'),
            15 => '15 '.get_lang('Days'),
            30 => '30 '.get_lang('Days'),
            'never' => get_lang('Never')
        );
    
        $el = $form -> addElement('select', 'since', '<img width="22" align="middle" src="'.api_get_path(WEB_IMG_PATH).'messagebox_warning.gif" border="0" />'.get_lang('RemindInactivesLearnersSince'), $options);
        $el -> setSelected(7);
    
        $form -> addElement('hidden', 'action', 'add');
        $form -> addElement('hidden', 'remindallinactives', 'true');
        
        $course_info = api_get_course_info(api_get_course_id());
        $course_name = get_lang('Course').' '.$course_info['name'];
        
        if ($session_id) {    	
            echo '<h2>'.Display::return_icon('session.png', get_lang('Session'), array(), 22).' '.api_get_session_name($session_id).' '.
                        Display::return_icon('course.png', get_lang('Course'), array(), 22).' '.$course_name.'</h2>';
        } else {
        	echo '<h2>'.Display::return_icon('course.png', get_lang('Course'), array(), 22).' '.$course_info['name'].'</h2>';
        }
        
        $extra_field_select = TrackingCourseLog::display_additional_profile_fields();
        
        if (!empty($extra_field_select)) {
            echo $extra_field_select;
        }              
        $form->display();
    
        // END : form to remind inactives susers
        /*
        if ($export_csv) {
            $is_western_name_order = api_is_western_name_order(PERSON_NAME_DATA_EXPORT);
        } else {
            $is_western_name_order = api_is_western_name_order();
        }*/
        
        //PERSON_NAME_DATA_EXPORT is buggy    
        $is_western_name_order = api_is_western_name_order();
        
        //$sort_by_first_name = api_sort_by_first_name();
    
        //$tracking_column = isset($_GET['tracking_column']) ? $_GET['tracking_column'] : 0;
        //$tracking_direction = isset($_GET['tracking_direction']) ? $_GET['tracking_direction'] : 'DESC';

        if ($export_csv) {
            $csv_content = array();
            //override the SortableTable "per page" limit if CSV
            $_GET['users_tracking_per_page'] = 1000000;
        }

        $all_datas = array();
        $course_code = $_course['id'];

        $user_ids = array_keys($a_students);
        
        $table = new SortableTable('users_tracking', array('TrackingCourseLog', 'get_number_of_users'), array('TrackingCourseLog', 'get_user_data'), (api_is_western_name_order() xor api_sort_by_first_name()) ? 3 : 2);

        $parameters['cidReq'] 		= Security::remove_XSS($_GET['cidReq']);
        $parameters['id_session'] 	= $session_id;
        $parameters['studentlist'] 	= Security::remove_XSS($_GET['studentlist']);
        $parameters['from'] 		= isset($_GET['myspace']) ? Security::remove_XSS($_GET['myspace']) : null;

        $table->set_additional_parameters($parameters);
        $tab_table_header = array();    // tab of header texts
        $table->set_header(0, get_lang('OfficialCode'), true, 'align="center"');
        $tab_table_header[] = get_lang('OfficialCode');
        if ($is_western_name_order) {
            $table->set_header(1, get_lang('FirstName'), true, 'align="center"');
            $tab_table_header[] = get_lang('FirstName');
            $table->set_header(2, get_lang('LastName'),  true, 'align="center"');
            $tab_table_header[] = get_lang('LastName');
        } else {
            $table->set_header(1, get_lang('LastName'),  true, 'align="center"');
            $tab_table_header[] = get_lang('LastName');
            $table->set_header(2, get_lang('FirstName'), true, 'align="center"');
            $tab_table_header[] = get_lang('FirstName');
        }
        $table->set_header(3, get_lang('Login'), false, 'align="center"');  // hubr
        $tab_table_header[] = get_lang('Login');
        
        $table->set_header(4, get_lang('TrainingTime'), false);
        $tab_table_header[] = get_lang('TrainingTime');
        $table->set_header(5, get_lang('CourseProgress').'&nbsp;'.Display::return_icon('info3.gif', get_lang('ScormAndLPProgressTotalAverage'), array('align' => 'absmiddle', 'hspace' => '3px')), false, array('style' => 'width:110px;'));
        $tab_table_header[] = get_lang('CourseProgress');
        
        $table->set_header(6, get_lang('ExerciseProgress'), false);
        $tab_table_header[] = get_lang('ExerciseProgress');
        $table->set_header(7, get_lang('ExerciseAverage'), false);
        $tab_table_header[] = get_lang('ExerciseAverage');
        $table->set_header(8, get_lang('Score').'&nbsp;'.Display::return_icon('info3.gif', get_lang('ScormAndLPTestTotalAverage'), array('align' => 'absmiddle', 'hspace' => '3px')), false, array('style' => 'width:110px;'));
        $tab_table_header[] = get_lang('Score');
        $table->set_header(9, get_lang('Student_publication'), false);
        $tab_table_header[] = get_lang('Student_publication');
        $table->set_header(10, get_lang('Messages'), false);
        $tab_table_header[] = get_lang('Messages');
        
        if (empty($session_id)) {
            $table->set_header(11, get_lang('Survey'), false);
            $tab_table_header[] = get_lang('Survey');
            $table->set_header(12, get_lang('FirstLogin'), false, 'align="center"');
            $tab_table_header[] = get_lang('FirstLogin');
            $table->set_header(13, get_lang('LatestLogin'), false, 'align="center"');
            $tab_table_header[] = get_lang('LatestLogin');
            if (isset($_GET['additional_profile_field']) AND is_numeric($_GET['additional_profile_field'])) {
                $table->set_header(14, $extra_info['field_display_text'], false);
                $tab_table_header[] = $extra_info['field_display_text'];
                $table->set_header(15, get_lang('Details'), false);                        
                $tab_table_header[] = get_lang('Details');
            } else {
                $table->set_header(14, get_lang('Details'), false);
                $tab_table_header[] = get_lang('Details');
            }
            
        } else {
            $table->set_header(11, get_lang('FirstLogin'), false, 'align="center"');
            $tab_table_header[] = get_lang('FirstLogin');
            $table->set_header(12, get_lang('LatestLogin'), false, 'align="center"');
            $tab_table_header[] = get_lang('LatestLogin');
            
            if (isset($_GET['additional_profile_field']) AND is_numeric($_GET['additional_profile_field'])) {                                
                $table->set_header(13, $extra_info['field_display_text'], false);
                $tab_table_header[] = $extra_info['field_display_text'];
                $table->set_header(14, get_lang('Details'), false);                
                $tab_table_header[] = get_lang('Details');
            } else {                
                $table->set_header(13, get_lang('Details'), false);
                $tab_table_header[] = get_lang('Details');
            }            
        }
        // display buttons to unhide hidden columns
        echo "<br/><br/><div id='unhideButtons'>";
        for ($i=0; $i < count($tab_table_header); $i++) {
            $index = $i + 1;
            echo "<span title='".get_lang('DisplayColumn')." ".$tab_table_header[$i]."' class='unhide_button hide' onclick='foldup($index)'>".Display :: return_icon('move.png', get_lang('DisplayColumn'), array('align'=>'absmiddle', 'hspace'=>'3px'), 16)." ".$tab_table_header[$i]."</span>";
        }
        echo "</div>";
        // display the table
        echo "<div id='reporting_table'>";
        $table->display();
        echo "</div>";
        // some scripts and style
        echo "<script type='text/javascript'>";
        echo "function foldup(in_id) {\n";
        echo "  $('div#reporting_table table tr td:nth-child('+in_id+')').fadeToggle();\n";
        echo "  $('div#reporting_table table tr th:nth-child('+in_id+')').fadeToggle();\n";
        echo "  $('div#unhideButtons span:nth-child('+in_id+')').fadeToggle();\n";
        echo "}\n";
        echo "function init_hide() {\n";
        echo "    $('div#reporting_table table tr th').each(\n";
        echo "        function(index) {\n";
        echo "            num_index = index + 1;\n";
        echo "            $(this).prepend('<span style=\"cursor:pointer\" onclick=\"foldup('+num_index+')\">".Display :: return_icon('delete.png', get_lang('HideColumn'), array('align' => 'absmiddle', 'hspace' => '3px'), 16)."</span><br/>');\n";
        echo "        }\n";
        echo "    );\n";
        echo "}\n";
        echo "$(document).ready( function() {";
        echo "  init_hide();\n";
        // hide several column at startup
        echo "  foldup(1);foldup(9);foldup(10);foldup(11);foldup(12);\n";            
        echo "});\n";
        echo "</script>";
        echo "<style type='text/css'>";
        echo ".unhide_button {";
        echo "    cursor : pointer;";
        echo "    border:1px solid black;";
        echo "    background-color: #FAFAFA;";
        echo "    padding: 5px;";
        echo "    border-radius : 3px;";
        echo "    margin-right:3px;";
        echo "}";
        echo "div#reporting_table table th {";
        echo "  vertical-align:top;";
        echo "}";
        echo "</style>";  
    } else {
        echo Display::display_warning_message(get_lang('NoUsersInCourse'));
    }

    // Send the csv file if asked.
    if ($export_csv) {
        $csv_headers = array();
        
        $csv_headers[] = get_lang('OfficialCode', '');
        if ($is_western_name_order) {       
            $csv_headers[] = get_lang('FirstName', '');
            $csv_headers[] = get_lang('LastName', '');            
        } else {
            $csv_headers[] = get_lang('LastName', '');   
            $csv_headers[] = get_lang('FirstName', '');            
        }
        $csv_headers[] = get_lang('Login', ''); // hubr
        $csv_headers[] = get_lang('TrainingTime', '');
        $csv_headers[] = get_lang('CourseProgress', '');
        $csv_headers[] = get_lang('ExerciseProgress','');
        $csv_headers[] = get_lang('ExerciseAverage','');
        $csv_headers[] = get_lang('Score', '');
        $csv_headers[] = get_lang('Student_publication', '');
        $csv_headers[] = get_lang('Messages', '');
        
        if (empty($session_id)) {
            $csv_headers[] = get_lang('Survey');
        }
        
        $csv_headers[] = get_lang('FirstLogin', '');
        $csv_headers[] = get_lang('LatestLogin', '');    

        if (isset($_GET['additional_profile_field']) AND is_numeric($_GET['additional_profile_field'])) {
            $csv_headers[] = $extra_info['field_display_text'];
        }
        ob_end_clean();        
        array_unshift($csv_content, $csv_headers); // Adding headers before the content.
                
        Export::export_table_csv($csv_content, 'reporting_student_list');
        exit;
    }
} elseif($_GET['studentlist'] == 'resources') {

    // Create a search-box.
    $form = new FormValidator('search_simple', 'get', api_get_path(WEB_CODE_PATH).'tracking/courseLog.php?'.api_get_cidreq().'&studentlist=resources', '', 'width=200px', false);
    $renderer =& $form->defaultRenderer();
    $renderer->setElementTemplate('<span>{element}</span>');
    $form->addElement('hidden', 'studentlist', 'resources');
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
}

Display::display_footer();
