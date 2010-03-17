<?php //$id: $
/* For licensing terms, see /dokeos_license.txt */
/**
==============================================================================
*	@author Thomas Depraetere
*	@author Hugues Peeters
*	@author Christophe Gesche
*	@author Sebastien Piraux
*	@author Toon Keppens (Vi-Host.net)
*
*	@package dokeos.tracking
==============================================================================
*/
/**
 *	INIT SECTION
 */
$pathopen = isset($_REQUEST['pathopen']) ? $_REQUEST['pathopen'] : null;

// name of the language file that needs to be included
$language_file[] = 'admin';
$language_file[] = 'tracking';
$language_file[] = 'scorm';
//$cidReset = true; //TODO: delete this line bug 457
// including the global initialization file
require_once '../inc/global.inc.php';

// the section (for the tabs)
//$this_section = "session_my_space";
$from_myspace = false;
if (isset($_GET['from']) && $_GET['from'] == 'myspace') {
	$from_myspace = true;
	$this_section = "session_my_space";
} else {
	$this_section = SECTION_COURSES;
}

// access restrictions
$is_allowedToTrack = $is_courseAdmin || $is_platformAdmin || $is_courseCoach || $is_sessionAdmin || api_is_drh();

if (!$is_allowedToTrack && !api_is_session_admin()) {
	Display :: display_header(null);
	api_not_allowed();
	Display :: display_footer();
	exit;

// Including additional libraries
require_once api_get_path(LIBRARY_PATH).'sortabletable.class.php';
require_once api_get_path(SYS_CODE_PATH).'newscorm/learnpath.class.php';
require_once api_get_path(SYS_CODE_PATH).'newscorm/learnpathItem.class.php';
require_once api_get_path(SYS_CODE_PATH).'newscorm/learnpathList.class.php';
require_once api_get_path(SYS_CODE_PATH).'newscorm/scorm.class.php';
require_once api_get_path(SYS_CODE_PATH).'newscorm/scormItem.class.php';
require_once api_get_path(LIBRARY_PATH).'tracking.lib.php';
require_once api_get_path(LIBRARY_PATH).'course.lib.php';
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'export.lib.inc.php';
require_once api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php';
require api_get_path(LIBRARY_PATH).'statsUtils.lib.inc.php';
require api_get_path(SYS_CODE_PATH).'resourcelinker/resourcelinker.inc.php';

// starting the output buffering when we are exporting the information
$export_csv = isset($_GET['export']) && $_GET['export'] == 'csv' ? true : false;
$session_id = intval($_REQUEST['id_session']);

if ($export_csv) {
	if (!empty($session_id)) {
    	$_SESSION['id_session'] = $session_id;
	}
	ob_start();
}
$csv_content = array();

// charset determination
if (!empty($_GET['scormcontopen'])) {
    $tbl_lp = Database::get_course_table(TABLE_LP_MAIN);
	$contopen = (int) $_GET['scormcontopen'];
	$sql = "SELECT default_encoding FROM $tbl_lp WHERE id = $contopen AND session_id = $session_id";
	$res = Database::query($sql);
	$row = Database::fetch_array($res);
	$lp_charset = $row['default_encoding'];
}

$htmlHeadXtra[] = "<style type='text/css'>
/*<![CDATA[*/
.secLine {background-color : #E6E6E6;}
.content {padding-left : 15px;padding-right : 15px; }
.specialLink{color : #0000FF;}
/*]]>*/
</style>
<style media='print' type='text/css'>

</style>";

// Database table definitions
$TABLETRACK_ACCESS      = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_LASTACCESS);
$TABLETRACK_LINKS       = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_LINKS);
$TABLETRACK_DOWNLOADS   = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_DOWNLOADS);
$TABLETRACK_ACCESS_2    = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ACCESS);
$TABLETRACK_EXERCISES 	= Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
$TABLECOURSUSER	        = Database::get_main_table(TABLE_MAIN_COURSE_USER);
$TABLECOURSE	        = Database::get_main_table(TABLE_MAIN_COURSE);
$TABLECOURSE_LINKS      = Database::get_course_table(TABLE_LINK);
$table_user = Database::get_main_table(TABLE_MAIN_USER);
$TABLEQUIZ = Database :: get_course_table(TABLE_QUIZ_TEST);

$tbl_learnpath_main = Database::get_course_table(TABLE_LP_MAIN);
$tbl_learnpath_item = Database::get_course_table(TABLE_LP_ITEM);
$tbl_learnpath_view = Database::get_course_table(TABLE_LP_VIEW);
$tbl_learnpath_item_view = Database::get_course_table(TABLE_LP_ITEM_VIEW);

// breadcrumbs
if (isset($_GET['origin']) && $_GET['origin'] == 'resume_session') {
  	$interbreadcrumb[] = array('url' => '../admin/index.php','name' => get_lang('PlatformAdmin'));
    $interbreadcrumb[] = array('url' => '../admin/session_list.php','name' => get_lang('SessionList'));
    $interbreadcrumb[] = array('url' => '../admin/resume_session.php?id_session='.$_SESSION['id_session'], 'name' => get_lang('SessionOverview'));
}

$view = (isset($_REQUEST['view']) ? $_REQUEST['view'] : '');


$nameTools = get_lang('Tracking');

// display the header
Display::display_header($nameTools, 'Tracking');

// getting all the students of the course
if (!empty($_SESSION['id_session'])) {
	// registered students in session
	$a_students = CourseManager :: get_student_list_from_course_code($_course['id'], true, $_SESSION['id_session']);
} else {
	// registered students in a course outside session
	$a_students = CourseManager :: get_student_list_from_course_code($_course['id']);
}

$nbStudents = count($a_students);

// gettting all the additional information of an additional profile field
if (isset($_GET['additional_profile_field']) && is_numeric($_GET['additional_profile_field'])) {
	//$additional_user_profile_info = get_addtional_profile_information_of_field($_GET['additional_profile_field']);
	$user_array = array();
	foreach ($a_students as $key=>$item) {
		$user_array[] = $key;
	}
	//fetching only the user that are loaded NOT ALL user in the portal
	$additional_user_profile_info = TrackingCourseLog::get_addtional_profile_information_of_field_by_user($_GET['additional_profile_field'],$user_array);
}

/*
==============================================================================
		MAIN CODE
==============================================================================
*/

echo '<div class="actions">';
if ($_GET['studentlist'] == 'false') {
	echo '<a href="courseLog.php?'.api_get_cidreq().'&studentlist=true">'.get_lang('StudentsTracking').'</a> | '.get_lang('CourseTracking').'&nbsp;|&nbsp;<a href="courseLog.php?'.api_get_cidreq().'&studentlist=resources">'.get_lang('ResourcesTracking');
} elseif($_GET['studentlist'] == 'resources') {
	echo '<a href="courseLog.php?'.api_get_cidreq().'&studentlist=true">'.get_lang('StudentsTracking').'</a> | <a href="courseLog.php?'.api_get_cidreq().'&studentlist=false">'.get_lang('CourseTracking').'</a> | '.get_lang('ResourcesTracking');
} elseif($_GET['studentlist'] == '' || $_GET['studentlist'] == 'true') {
	echo get_lang('StudentsTracking').' | <a href="courseLog.php?'.api_get_cidreq().'&studentlist=false">'.get_lang('CourseTracking').'</a> | <a href="courseLog.php?'.api_get_cidreq().'&studentlist=resources">'.get_lang('ResourcesTracking').'</a>';
}
echo '&nbsp;<a href="javascript: void(0);" onclick="javascript: window.print();">'.Display::return_icon('printmgr.gif',get_lang('Print')).get_lang('Print').'</a>';
if($_GET['studentlist'] == 'false') {
	echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&id_session='.api_get_session_id().'&export=csv&studentlist=false"><img align="absbottom" src="../img/excel.gif">&nbsp;'.get_lang('ExportAsCSV').'</a>';
} elseif ($_GET['studentlist'] == '' || $_GET['studentlist'] == 'true') {
	$addional_param = '';
	if (isset($_GET['additional_profile_field'])) {
		$addional_param ='additional_profile_field='.intval($_GET['additional_profile_field']);
	}
	echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&export=csv&'.$addional_param.'">'.Display::return_icon('csv.gif',get_lang('ExportAsCSV')).get_lang('ExportAsCSV').'</a>';
}
if($_GET['studentlist'] == 'true' || empty($_GET['studentlist'])) {
	echo TrackingCourseLog::display_additional_profile_fields();
}
echo '</div>';


if ($_GET['studentlist'] == 'false') {
	$course_code = api_get_course_id();

	echo'<br /><br />';

	// learning path tracking
	 echo '<div class="report_section">
			<h4>'.Display::return_icon('scormbuilder.gif',get_lang('AverageProgressInLearnpath')).get_lang('AverageProgressInLearnpath').'</h4>
			<table class="data_table">';

	$list = new LearnpathList($student, $course_code, $session_id);
	$flat_list = $list->get_flat_list();

	if ($export_csv) {
    	$temp = array(get_lang('AverageProgressInLearnpath', ''), '');
    	$csv_content[] = array('', '');
    	$csv_content[] = $temp;
    }

	if (count($flat_list) > 0) {
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
	} else {
		echo '<tr><td>'.get_lang('NoLearningPath').'</td></tr>';
		if ($export_csv) {
    		$temp = array(get_lang('NoLearningPath', ''), '');
			$csv_content[] = $temp;
    	}
	}
	echo '</table></div>';
	echo '<div class="clear"></div>';

	 // Exercices tracking
	 echo '<div class="report_section">
				<h4>'.Display::return_icon('quiz.gif',get_lang('AverageResultsToTheExercices')).get_lang('AverageResultsToTheExercices').'&nbsp;-&nbsp;<a href="../exercice/exercice.php?'.api_get_cidreq().'&show=result">'.get_lang('SeeDetail').'</a></h4>
			<table class="data_table">';

	$sql = "SELECT id, title
			FROM $TABLEQUIZ WHERE active <> -1 AND session_id = $session_id";
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
			echo '<tr><td>'.$quiz['title'].'</td><td align="right">'.round(($quiz_avg_score / $count_students), 2).'%'.'</td></tr>';
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

	// forums tracking
	echo '<div class="report_section">
			<h4>'.Display::return_icon('forum.gif', get_lang('Forum')).get_lang('Forum').'&nbsp;-&nbsp;<a href="../forum/index.php?cidReq='.$_course['id'].'">'.get_lang('SeeDetail').'</a></h4>
			<table class="data_table">';
	$count_number_of_posts_by_course = Tracking :: count_number_of_posts_by_course($course_code, $session_id);
	$count_number_of_forums_by_course = Tracking :: count_number_of_forums_by_course($course_code, $session_id);
	$count_number_of_threads_by_course = Tracking :: count_number_of_threads_by_course($course_code, $session_id);
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

	// chat tracking

	echo '<div class="report_section">
			<h4>'.Display::return_icon('chat.gif',get_lang('Chat')).get_lang('Chat').'</h4>
			<table class="data_table">';
	$chat_connections_during_last_x_days_by_course = Tracking::chat_connections_during_last_x_days_by_course($course_code, 7, $session_id);
	if ($export_csv) {
		$csv_content[] = array(get_lang('Chat', ''), '');
    	$csv_content[] = array(sprintf(get_lang('ChatConnectionsDuringLastXDays', ''), '7'), $chat_connections_during_last_x_days_by_course);
    }
	echo '<tr><td>'.sprintf(get_lang('ChatConnectionsDuringLastXDays'), '7').'</td><td align="right">'.$chat_connections_during_last_x_days_by_course.'</td></tr>';

	echo '</table></div>';
	echo '<div class="clear"></div>';

	// tools tracking
	echo '<div class="report_section">
				<h4>'.Display::return_icon('acces_tool.gif', get_lang('ToolsMostUsed')).get_lang('ToolsMostUsed').'</h4>
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

	// Documents tracking
	if ($_GET['num'] == 0 or empty($_GET['num'])) {
		$num = 3;
		$link='&nbsp;-&nbsp;<a href="'.api_get_self().'?'.api_get_cidreq().'&studentlist=false&num=1#documents_tracking">'.get_lang('SeeDetail').'</a>';
	} else {
		$num = 1000;
		$link='&nbsp;-&nbsp;<a href="'.api_get_self().'?'.api_get_cidreq().'&studentlist=false&num=0#documents_tracking">'.get_lang('ViewMinus').'</a>';
	}

	 echo '<a name="documents_tracking" id="a"></a><div class="report_section">
				<h4>'.Display::return_icon('documents.gif',get_lang('DocumentsMostDownloaded')).'&nbsp;'.get_lang('DocumentsMostDownloaded').$link.'</h4>
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
    		$temp = array(get_lang('NoDocumentDownloaded', ''),'');
			$csv_content[] = $temp;
    	}
    }
	echo '</table></div>';

	echo '<div class="clear"></div>';

	// links tracking
	 echo '<div class="report_section">
				<h4>'.Display::return_icon('link.gif',get_lang('LinksMostClicked')).'&nbsp;'.get_lang('LinksMostClicked').'</h4>
			<table class="data_table">';

	$links_most_visited = Tracking::get_links_most_visited_by_course($course_code, $session_id);

    if ($export_csv) {
    	$temp = array(get_lang('LinksMostClicked'),'');
    	$csv_content[] = array('','');
    	$csv_content[] = $temp;
    }

    if (!empty($links_most_visited)) {
	    foreach ($links_most_visited as $row) {
	    	echo '	<tr>
						<td>'.$row['title'].'</td>
						<td align="right">'.$row['count_visits'].' '.get_lang('Clicks').'</td>
					</tr>';
			if ($export_csv){
				$temp = array($row['title'],$row['count_visits'].' '.get_lang('Clicks', ''));
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
// else display student list with all the informations

	// BEGIN : form to remind inactives susers
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

	$form -> display();
	// END : form to remind inactives susers

	if ($export_csv) {
		$is_western_name_order = api_is_western_name_order(PERSON_NAME_DATA_EXPORT);
	} else {
		$is_western_name_order = api_is_western_name_order();
	}
	$sort_by_first_name = api_sort_by_first_name();

	$tracking_column = isset($_GET['tracking_column']) ? $_GET['tracking_column'] : 0;
	$tracking_direction = isset($_GET['tracking_direction']) ? $_GET['tracking_direction'] : 'DESC';

	if (count($a_students) > 0) {

	    if ($export_csv) {
			$csv_content[] = array ();
		}

	    $all_datas = array();
	    $course_code = $_course['id'];




		$user_ids = array_keys($a_students);



		$table = new SortableTable('users_tracking', array('TrackingCourseLog','get_number_of_users'), array('TrackingCourseLog','get_user_data'), (api_is_western_name_order() xor api_sort_by_first_name()) ? 3 : 2);

		$parameters['cidReq'] 		= Security::remove_XSS($_GET['cidReq']);
		$parameters['id_session'] 	= $session_id;
		$parameters['studentlist'] 	= Security::remove_XSS($_GET['studentlist']);
		$parameters['from'] 		= Security::remove_XSS($_GET['myspace']);

		$table->set_additional_parameters($parameters);

		$table -> set_header(0, get_lang('OfficialCode'), false, 'align="center"');
		if ($is_western_name_order) {
			$table -> set_header(1, get_lang('FirstName'), false, 'align="center"');
			$table -> set_header(2, get_lang('LastName'), true, 'align="center"');
		} else {
    		$table -> set_header(1, get_lang('LastName'), true, 'align="center"');
			$table -> set_header(2, get_lang('FirstName'), false, 'align="center"');
		}
		$table -> set_header(3, get_lang('TrainingTime'),false);
		$table -> set_header(4, get_lang('CourseProgress'),false);
		$table -> set_header(5, get_lang('Score'),false);
		$table -> set_header(6, get_lang('Student_publication'),false);
		$table -> set_header(7, get_lang('Messages'),false);
		$table -> set_header(8, get_lang('FirstLogin'), false, 'align="center"');
		$table -> set_header(9, get_lang('LatestLogin'), false, 'align="center"');
		//if (isset($_GET['additional_profile_field']) AND is_numeric($_GET['additional_profile_field'])) {
			$table -> set_header(10, get_lang('AdditionalProfileField'),false);
        /*} else {
        	$table -> set_header(10, ,false);
        }*/
		$table -> set_header(11, get_lang('Details'),false);
		$table->display();


	} else {
		echo get_lang('NoUsersInCourseTracking');
	}

	// send the csv file if asked
	if ($export_csv) {
		if ($is_western_name_order) {
			$csv_headers = array (
				get_lang('OfficialCode', ''),
				get_lang('FirstName', ''),
				get_lang('LastName', ''),
				get_lang('TrainingTime', ''),
				get_lang('CourseProgress', ''),
				get_lang('Score', ''),
				get_lang('Student_publication', ''),
				get_lang('Messages', ''),
				get_lang('FirstLogin', ''),
				get_lang('LatestLogin', '')
			);
		} else {
			$csv_headers = array (
				get_lang('OfficialCode', ''),
				get_lang('LastName', ''),
				get_lang('FirstName', ''),
				get_lang('TrainingTime', ''),
				get_lang('CourseProgress', ''),
				get_lang('Score', ''),
				get_lang('Student_publication', ''),
				get_lang('Messages', ''),
				get_lang('FirstLogin', ''),
				get_lang('LatestLogin', '')
			);
		}

		if (isset($_GET['additional_profile_field']) AND is_numeric($_GET['additional_profile_field'])) {
			$csv_headers[]=get_lang('AdditionalProfileField');
		}
		ob_end_clean();
		array_unshift($csv_content, $csv_headers); // adding headers before the content
		Export :: export_table_csv($csv_content, 'reporting_student_list');
		exit;
	}
} elseif($_GET['studentlist'] == 'resources') {

	// Create a search-box
	$form = new FormValidator('search_simple','get',api_get_path(WEB_CODE_PATH).'tracking/courseLog.php?'.api_get_cidreq().'&studentlist=resources','','width=200px',false);
	$renderer =& $form->defaultRenderer();
	$renderer->setElementTemplate('<span>{element}</span>');
	$form->addElement('hidden','studentlist','resources');
	$form->addElement('text','keyword',get_lang('keyword'));
	$form->addElement('style_submit_button', 'submit', get_lang('Search'),'class="search"');
	echo '<div class="actions">';
		$form->display();
	echo '</div>';

	$table = new SortableTable('resources', array('TrackingCourseLog','count_item_resources'), array('TrackingCourseLog','get_item_resources_data'), 5, 20, 'DESC');
	$parameters = array();

	if (isset($_GET['keyword'])) {
		$parameters['keyword'] = Security::remove_XSS($_GET['keyword']);
	}

	$parameters['studentlist'] = 'resources';

	$table->set_additional_parameters($parameters);
	$table->set_header(0, get_lang('Tool'));
	$table->set_header(1, get_lang('EventType'));
	$table->set_header(2, get_lang('Session'), false);
	$table->set_header(3, get_lang('UserName'));
	$table->set_header(4, get_lang('Document'), false);
	$table->set_header(5, get_lang('Date'));
	$table->display();

}
?>
</table>
<?php
Display::display_footer();