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

// including the global Dokeos file
require '../inc/global.inc.php';

// the section (for the tabs)
//$this_section = "session_my_space";
$from_myspace = false;
if (isset($_GET['from']) && $_GET['from'] == 'myspace') {
	$from_myspace = true;
	$this_section = "session_my_space";
} else {
	$this_section = SECTION_COURSES;
}

$is_allowedToTrack = $is_courseAdmin || $is_platformAdmin || $is_courseCoach || $is_sessionAdmin;

if (!$is_allowedToTrack) {
	Display :: display_header(null);
	api_not_allowed();
	Display :: display_footer();
	exit;
}
// including additional libraries
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

$export_csv = isset($_GET['export']) && $_GET['export'] == 'csv' ? true : false;
if ($export_csv) {
	ob_start();
}
$csv_content = array();

// charset determination
if (!empty($_GET['scormcontopen'])) {
    $tbl_lp = Database::get_course_table(TABLE_LP_MAIN);
	$contopen = (int) $_GET['scormcontopen'];
	$sql = "SELECT default_encoding FROM $tbl_lp WHERE id = ".$contopen;
	$res = Database::query($sql,__FILE__,__LINE__);
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

/*
-----------------------------------------------------------
	Constants and variables
-----------------------------------------------------------
*/
// regroup table names for maintenance purpose
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

//$table_scormdata = Database::get_scorm_table(TABLE_SCORM_SCO_DATA);
//$table_scormmain = Database::get_scorm_table(TABLE_SCORM_MAIN);
//$tbl_learnpath_main = Database::get_course_table(TABLE_LEARNPATH_MAIN);
//$tbl_learnpath_item = Database::get_course_table(TABLE_LEARNPATH_ITEM);
//$tbl_learnpath_chapter = Database::get_course_table(TABLE_LEARNPATH_CHAPTER);

$tbl_learnpath_main = Database::get_course_table(TABLE_LP_MAIN);
$tbl_learnpath_item = Database::get_course_table(TABLE_LP_ITEM);
$tbl_learnpath_view = Database::get_course_table(TABLE_LP_VIEW);
$tbl_learnpath_item_view = Database::get_course_table(TABLE_LP_ITEM_VIEW);

if (isset($_GET['origin']) && $_GET['origin'] == 'resume_session') {
    $interbreadcrumb[] = array('url' => '../admin/index.php','name' => get_lang('PlatformAdmin'));
    $interbreadcrumb[] = array('url' => '../admin/session_list.php','name' => get_lang('SessionList'));
    $interbreadcrumb[] = array('url' => '../admin/resume_session.php?id_session='.$_SESSION['id_session'], 'name' => get_lang('SessionOverview'));
}

$view = (isset($_REQUEST['view']) ? $_REQUEST['view'] : '');

$nameTools = get_lang('Tracking');

Display::display_header($nameTools, 'Tracking');

require api_get_path(LIBRARY_PATH).'statsUtils.lib.inc.php';
require api_get_path(SYS_CODE_PATH).'resourcelinker/resourcelinker.inc.php';

$a_students = CourseManager :: get_student_list_from_course_code($_course['id'], true, (empty($_SESSION['id_session']) ? null : $_SESSION['id_session']));
$nbStudents = count($a_students);

/**
 * count the number of students in this course (used for SortableTable)
 */
function count_student_in_course() {
	global $nbStudents;
	return $nbStudents;
}

function sort_users($a, $b) {
	return api_strcmp(trim(api_strtolower($a[$_SESSION['tracking_column']])), trim(api_strtolower($b[$_SESSION['tracking_column']])));
}

function count_item_resources() {
	$table_item_property = Database :: get_course_table(TABLE_ITEM_PROPERTY);
	$table_user = Database :: get_main_table(TABLE_MAIN_USER);
	$sql = "SELECT count(tool) AS total_number_of_items FROM $table_item_property track_resource, $table_user user" .
			" WHERE track_resource.insert_user_id = user.user_id";
	
	if (isset($_GET['keyword'])) {
		$keyword = Database::escape_string($_GET['keyword']);
		$sql .= " AND (user.username LIKE '%".$keyword."%' OR lastedit_type LIKE '%".$keyword."%' OR tool LIKE '%".$keyword."%')";
	}
	
	$sql .= " AND tool IN ('document', 'learnpath', 'quiz', 'glossary', 'link', 'course_description')";
	$res = api_sql_query($sql, __FILE__, __LINE__);
	$obj = Database::fetch_object($res);
	return $obj->total_number_of_items;
}

function get_item_resources_data($from, $number_of_items, $column, $direction) {
	global $dateTimeFormatLong;
	$table_item_property = Database :: get_course_table(TABLE_ITEM_PROPERTY);
	$table_user = Database :: get_main_table(TABLE_MAIN_USER);
	$table_session = Database :: get_main_table(TABLE_MAIN_SESSION);
	$sql = "SELECT
			 	tool as col0,
				lastedit_type as col1,
				ref as ref,
				user.username as col3,
				insert_date as col5,
				visibility as col6
			FROM $table_item_property track_resource, $table_user user
			WHERE track_resource.insert_user_id = user.user_id ";
	
	if (isset($_GET['keyword'])) {
		$keyword = Database::escape_string($_GET['keyword']);
		$sql .= " AND (user.username LIKE '%".$keyword."%' OR lastedit_type LIKE '%".$keyword."%' OR tool LIKE '%".$keyword."%') ";
	}
	
	$sql .= " AND tool IN ('document', 'learnpath', 'quiz', 'glossary', 'link', 'course_description')";
	
	if ($column == 0) { $column = '0'; }
	if ($column != '' && $direction != '') {
		if ($column != 2 && $column != 4) {
			$sql .=	" ORDER BY col$column $direction";
		}
	} else {
		$sql .=	" ORDER BY col5 DESC ";
	}
	
	$sql .=	" LIMIT $from, $number_of_items ";
	
	$res = api_sql_query($sql, __FILE__, __LINE__) or die(mysql_error());
	$resources = array ();
	
	while ($row = Database::fetch_array($res)) {
		$ref = $row['ref'];
		$table_name = get_tool_name_table($row['col0']);
		$table_tool = Database :: get_course_table($table_name['table_name']);
		$id = $table_name['id_tool'];
		$query = "SELECT session.id, session.name, user.username FROM $table_tool tool, $table_session session, $table_user user" .
					" WHERE tool.session_id = session.id AND session.id_coach = user.user_id AND tool.$id = $ref";
		$recorset = api_sql_query($query, __FILE__, __LINE__);
		
		if (!empty($recorset)) {
			
			$obj = Database::fetch_object($recorset);
			
			$name_session = '';
			$coach_name = '';
			if (!empty($obj)) {
				$name_session = $obj->name;
				$coach_name = $obj->username;
			}
			
			$url_tool = api_get_path(WEB_CODE_PATH).$table_name['link_tool'];
			
			$row[0] = '';
			if ($row['col6'] != 2) {
				$row[0] = '<a href="'.$url_tool.'?'.api_get_cidreq().'&'.$obj->id.'">'.api_ucfirst($row['col0']).'</a>';
			} else {
				$row[0] = api_ucfirst($row['col0']);
			}
			
			$row[1] = get_lang($row[1]);
			
			$row[5] = api_ucfirst(format_locale_date($dateTimeFormatLong, strtotime($row['col5'])));
			
			$row[4] = '';
			if ($table_name['table_name'] == 'document') {
				$condition = 'tool.title as title';
				$query_document = "SELECT $condition FROM $table_tool tool" .
									" WHERE id = $ref";
				$rs_document = api_sql_query($query_document, __FILE__, __LINE__) or die(mysql_error());
				$obj_document = Database::fetch_object($rs_document);
				$row[4] = $obj_document->title;
			}
			
			$row2 = $name_session;
			if (!empty($coach_name)) {
				$row2 .= '<br />'.get_lang('Coach').': '.$coach_name;
			}
			$row[2] = $row2;
			
			$resources[] = $row;
		}
		
	}
	
	return $resources;
}

function get_tool_name_table($tool) {
	switch ($tool) {
		case 'document':
			$table_name = TABLE_DOCUMENT;
			$link_tool = 'document/document.php';
			$id_tool = 'id';
			break;
		case 'learnpath':
			$table_name = TABLE_LP_MAIN;
			$link_tool = 'newscorm/lp_controller.php';
			$id_tool = 'id';
			break;
		case 'quiz':
			$table_name = TABLE_QUIZ_TEST;
			$link_tool = 'exercice/exercice.php';
			$id_tool = 'id';
			break;
		case 'glossary':
			$table_name = TABLE_GLOSSARY;
			$link_tool = 'glossary/index.php';
			$id_tool = 'glossary_id';
			break;
		case 'link':
			$table_name = TABLE_LINK;
			$link_tool = 'link/link.php';
			$id_tool = 'id';
			break;
		case 'course_description':
			$table_name = TABLE_COURSE_DESCRIPTION;
			$link_tool = 'course_description/';
			$id_tool = 'id';
			break;
		default:
			$table_name = $tool;
			break;
	}
	return array('table_name' => $table_name,
				 'link_tool' => $link_tool,
				 'id_tool' => $id_tool);
}

/*
==============================================================================
		MAIN CODE
==============================================================================
*/

echo '<div class="actions">';
if ($_GET['studentlist'] == 'false') {
	echo '<a href="courseLog.php?'.api_get_cidreq().'&studentlist=true">'.get_lang('StudentsTracking').'</a> | '.get_lang('CourseTracking').' | <a href="courseLog.php?'.api_get_cidreq().'&studentlist=resources">'.get_lang('ResourcesTracking');
} elseif($_GET['studentlist'] == 'resources') {
	echo '<a href="courseLog.php?'.api_get_cidreq().'&studentlist=true">'.get_lang('StudentsTracking').'</a> | <a href="courseLog.php?'.api_get_cidreq().'&studentlist=false">'.get_lang('CourseTracking').'</a> | '.get_lang('ResourcesTracking');
} elseif($_GET['studentlist'] == '' || $_GET['studentlist'] == 'true') {
	echo get_lang('StudentsTracking').' | <a href="courseLog.php?'.api_get_cidreq().'&studentlist=false">'.get_lang('CourseTracking').'</a> | <a href="courseLog.php?'.api_get_cidreq().'&studentlist=resources">'.get_lang('ResourcesTracking').'</a>';
}
echo '&nbsp;<a href="javascript: void(0);" onclick="javascript: window.print();"><img align="absbottom" src="../img/printmgr.gif">&nbsp;'.get_lang('Print').'</a>';
if($_GET['studentlist'] == 'false') {
	echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&export=csv&studentlist=false"><img align="absbottom" src="../img/excel.gif">&nbsp;'.get_lang('ExportAsCSV').'</a>';
} elseif ($_GET['studentlist'] == '' || $_GET['studentlist'] == 'true') {
	echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&export=csv"><img align="absbottom" src="../img/excel.gif">&nbsp;'.get_lang('ExportAsCSV').'</a>';
}
echo '</div>';


if ($_GET['studentlist'] == 'false') {
	echo'<br /><br />';

	/***************************
	 * LEARNING PATHS
	 ***************************/
	 echo '<div class="report_section">
				<h4>
					<img src="../img/scormbuilder.gif" align="absbottom">&nbsp;'.get_lang('AverageProgressInLearnpath').'
				</h4>
			<table class="data_table">';

	$list = new LearnpathList($student);
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
				$lp_avg_progress += learnpath::get_db_progress($lp_id, $student_id);
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

	/***************************
	 * EXERCICES
	 ***************************/
	 echo '<div class="report_section">
				<h4>
					<img src="../img/quiz.gif" align="absbottom">&nbsp;'.get_lang('AverageResultsToTheExercices').' &nbsp;-&nbsp;<a href="../exercice/exercice.php?'.api_get_cidreq().'&show=result">'.get_lang('SeeDetail').'</a>
				</h4>
			<table class="data_table">';

	$sql = "SELECT id, title
			FROM $TABLEQUIZ WHERE active <> -1";
	$rs = Database::query($sql, __FILE__, __LINE__);

	if ($export_csv) {
    	$temp = array(get_lang('AverageProgressInLearnpath'), '');
    	$csv_content[] = array('', '');
    	$csv_content[] = $temp;
    }

	if (Database::num_rows($rs) > 0) {
		// gets course actual administrators
		$sql = "SELECT user.user_id FROM $table_user user, $TABLECOURSUSER course_user
			WHERE course_user.user_id=user.user_id AND course_user.course_code='".api_get_course_id()."' AND course_user.status <> '1' ";
		$res = Database::query($sql, __FILE__, __LINE__);

		$student_ids = array();

		while($row = Database::fetch_row($res)) {
			$student_ids[] = $row[0];
		}
		$count_students = count($student_ids);
		while ($quiz = Database::fetch_array($rs)) {
			$quiz_avg_score = 0;
			if ($count_students > 0) {
				foreach ($student_ids as $student_id) {
					// get the scorn in exercises
					$sql = 'SELECT exe_result , exe_weighting
						FROM '.$TABLETRACK_EXERCISES.'
						WHERE exe_exo_id = '.$quiz['id'].'
							AND exe_user_id = '.(int)$student_id.'
							AND exe_cours_id = "'.api_get_course_id().'"
						AND orig_lp_id = 0
						AND orig_lp_item_id = 0
						ORDER BY exe_date DESC';
					$rsAttempt = Database::query($sql, __FILE__, __LINE__);
					$nb_attempts = 0;
					$avg_student_score = 0;
					while ($attempt = Database::fetch_array($rsAttempt)) {
						$nb_attempts++;
						$exe_weight = $attempt['exe_weighting'];
						if ($exe_weight > 0) {
							$avg_student_score += round(($attempt['exe_result'] / $exe_weight * 100), 2);
						}
					}
					if ($nb_attempts > 0) {
						$avg_student_score = $avg_student_score / $nb_attempts;
					}
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

	/**********************
	 * FORUMS
	 **********************/

	echo '<div class="report_section">
				<h4>
					<img src="../img/forum.gif" align="absbottom">&nbsp;'.get_lang('Forum').'&nbsp;-&nbsp;<a href="../forum/index.php?cidReq='.$_course['id'].'">'.get_lang('SeeDetail').'</a>
				</h4>
			<table class="data_table">';
	$count_number_of_posts_by_course = Tracking :: count_number_of_posts_by_course($_course['id']);
	$count_number_of_forums_by_course = Tracking :: count_number_of_forums_by_course($_course['id']);
	$count_number_of_threads_by_course = Tracking :: count_number_of_threads_by_course($_course['id']);
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

	/**********************
	 * CHAT
	 **********************/

	echo '<div class="report_section">
				<h4>
					<img src="../img/chat.gif" align="absbottom">&nbsp;'.get_lang('Chat').'</a>
				</h4>
			<table class="data_table">';
	$chat_connections_during_last_x_days_by_course = Tracking :: chat_connections_during_last_x_days_by_course($_course['id'], 7);
	if ($export_csv) {
		$csv_content[] = array(get_lang('Chat', ''), '');
    	$csv_content[] = array(sprintf(get_lang('ChatConnectionsDuringLastXDays', ''), '7'), $chat_connections_during_last_x_days_by_course);
    }
	echo '<tr><td>'.sprintf(get_lang('ChatConnectionsDuringLastXDays'), '7').'</td><td align="right">'.$chat_connections_during_last_x_days_by_course.'</td></tr>';

	echo '</table></div>';
	echo '<div class="clear"></div>';

	/**********************
	 * TOOLS
	 **********************/
	echo '<div class="report_section">
				<h4>
					<img src="../img/acces_tool.gif" align="absbottom">&nbsp;'.get_lang('ToolsMostUsed').'
				</h4>
			<table class="data_table">';

	$sql = "SELECT access_tool, COUNT(DISTINCT access_user_id),count( access_tool ) as count_access_tool
            FROM $TABLETRACK_ACCESS
            WHERE access_tool IS NOT NULL
                AND access_cours_code = '$_cid'
            GROUP BY access_tool
			ORDER BY count_access_tool DESC
			LIMIT 0, 3";
	$rs = Database::query($sql, __FILE__, __LINE__);

	if ($export_csv) {
    	$temp = array(get_lang('ToolsMostUsed'), '');
    	$csv_content[] = $temp;
    }

	while ($row = Database::fetch_array($rs)) {
		echo '	<tr>
					<td>'.get_lang(ucfirst($row['access_tool'])).'</td>
					<td align="right">'.$row['count_access_tool'].' '.get_lang('Clicks').'</td>
				</tr>';
		if ($export_csv) {
			$temp = array(get_lang(ucfirst($row['access_tool']), ''), $row['count_access_tool'].' '.get_lang('Clicks', ''));
			$csv_content[] = $temp;
		}
	}

	echo '</table></div>';
	echo '<div class="clear"></div>';

	/***************************
	 * DOCUMENTS
	 ***************************/
	if ($_GET['num'] == 0 or empty($_GET['num'])) {
		$num = 3;
		$link = '&nbsp;-&nbsp;<a href="'.api_get_self().'?'.api_get_cidreq().'&studentlist=false&num=1#ancle">'.get_lang('SeeDetail').'</a>';
	} else {
		$num = 1000;
		$link = '&nbsp;-&nbsp;<a href="'.api_get_self().'?'.api_get_cidreq().'&studentlist=false&num=0#ancle">'.get_lang('ViewMinus').'</a>';
	}

	echo '<a name="ancle" id="a"></a><div class="report_section">
				<h4>
					<img src="../img/documents.gif" align="absbottom">&nbsp;'.get_lang('DocumentsMostDownloaded').$link.'
				</h4>
			<table class="data_table">';

	$sql = "SELECT down_doc_path, COUNT(DISTINCT down_user_id), COUNT(down_doc_path) as count_down
            FROM $TABLETRACK_DOWNLOADS
            WHERE down_cours_id = '$_cid'
            GROUP BY down_doc_path
			ORDER BY count_down DESC
			LIMIT 0,  $num";
    $rs = Database::query($sql, __FILE__, __LINE__);

    if ($export_csv) {
    	$temp = array(get_lang('DocumentsMostDownloaded', ''), '');
    	$csv_content[] = array('', '');
    	$csv_content[] = $temp;
    }

    if (Database::num_rows($rs) > 0) {
	    while($row = Database::fetch_array($rs)) {
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

	/***************************
	 * LINKS
	 ***************************/
	 echo '<div class="report_section">
				<h4>
					<img src="../img/link.gif" align="absbottom">&nbsp;'.get_lang('LinksMostClicked').'
				</h4>
			<table class="data_table">';

	$sql = "SELECT cl.title, cl.url,count(DISTINCT sl.links_user_id), count(cl.title) as count_visits
            FROM $TABLETRACK_LINKS AS sl, $TABLECOURSE_LINKS AS cl
            WHERE sl.links_link_id = cl.id
                AND sl.links_cours_id = '$_cid'
            GROUP BY cl.title, cl.url
			ORDER BY count_visits DESC
			LIMIT 0, 3";
    $rs = Database::query($sql, __FILE__, __LINE__);

    if ($export_csv) {
    	$temp = array(get_lang('LinksMostClicked'),'');
    	$csv_content[] = array('','');
    	$csv_content[] = $temp;
    }

    if (Database::num_rows($rs) > 0) {
	    while ($row = Database::fetch_array($rs)) {
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
	}
} elseif($_GET['studentlist'] == 'true' or $_GET['studentlist'] == '') {
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
		30 => '30 '.get_lang('Days')
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

	$tracking_column = isset($_GET['tracking_column']) ? $_GET['tracking_column'] : ($is_western_name_order xor $sort_by_first_name) ? 2 : 1;
	$tracking_direction = isset($_GET['tracking_direction']) ? $_GET['tracking_direction'] : 'DESC';

	if (count($a_students) > 0) {
		$table = new SortableTable('tracking', 'count_student_in_course', null, ($is_western_name_order xor $sort_by_first_name) ? 2 : 1);
		$table -> set_header(0, get_lang('OfficialCode'), true, 'align="center"');
		if ($is_western_name_order) {
			$table -> set_header(1, get_lang('FirstName'), true, 'align="center"');
			$table -> set_header(2, get_lang('LastName'), true, 'align="center"');
		} else {
			$table -> set_header(1, get_lang('LastName'), true, 'align="center"');
			$table -> set_header(2, get_lang('FirstName'), true, 'align="center"');
		}
		$table -> set_header(3, get_lang('TrainingTime'), false);
		$table -> set_header(4, get_lang('CourseProgress'), false);
		$table -> set_header(5, get_lang('Score'), false);
		$table -> set_header(6, get_lang('Student_publication'), false);
		$table -> set_header(7, get_lang('Messages'), false);
		$table -> set_header(8, get_lang('FirstLogin'), false, 'align="center"');
		$table -> set_header(9, get_lang('LatestLogin'), false, 'align="center"');
		$table -> set_header(10, get_lang('Details'), false);

	    $all_datas = array();
	    $course_code = $_course['id'];
		foreach ($a_students as $student_id => $student) {
			$student_datas = UserManager :: get_user_info_by_id($student_id);

			$avg_time_spent = $avg_student_score = $avg_student_progress = $total_assignments = $total_messages = 0;
			$nb_courses_student = 0;
			$avg_time_spent = Tracking :: get_time_spent_on_the_course($student_id, $course_code);
			$avg_student_score = Tracking :: get_average_test_scorm_and_lp($student_id, $course_code);
			$avg_student_progress = Tracking :: get_avg_student_progress($student_id, $course_code);
			$total_assignments = Tracking :: count_student_assignments($student_id, $course_code);
			$total_messages = Tracking :: count_student_messages($student_id, $course_code);

			$row = array();
			$row[] = $student_datas['official_code'];
			if ($is_western_name_order) {
				$row[] = $student_datas['firstname'];
				$row[] = $student_datas['lastname'];
			} else {
				$row[] = $student_datas['lastname'];
				$row[] = $student_datas['firstname'];
			}
			$row[] = api_time_to_hms($avg_time_spent);
			if (is_null($avg_student_score)) {$avg_student_score=0;}
			if (is_null($avg_student_progress)) {$avg_student_progress=0;}
			$row[] = $avg_student_progress.'%';
			$row[] = $avg_student_score.'%';
			$row[] = $total_assignments;
			$row[] = $total_messages;
			$row[] = Tracking :: get_first_connection_date_on_the_course($student_id, $course_code);
			$row[] = Tracking :: get_last_connection_date_on_the_course($student_id, $course_code);

			if ($export_csv) {
				$row[8] = strip_tags($row[8]);
				$csv_content[] = $row;
			}
			$from = '';
			if ($from_myspace) {
				$from ='&from=myspace';
			}
			$row[] = '<center><a href="../mySpace/myStudents.php?student='.$student_id.'&details=true&course='.$course_code.'&origin=tracking_course'.$from.'"><img src="'.api_get_path(WEB_IMG_PATH).'2rightarrow.gif" border="0" /></a></center>';

			$all_datas[] = $row;
		}

		usort($all_datas, 'sort_users');
		$page = $table->get_pager()->getCurrentPageID();
		$all_datas = array_slice($all_datas, ($page-1)*$table -> per_page, $table -> per_page);

		if ($export_csv) {
			usort($csv_content, 'sort_users');
		}

		foreach ($all_datas as $row) {
			$table -> addRow($row,'align="right"');
		}
		$table -> setColAttributes(0, array('align' => 'left'));
		$table -> setColAttributes(1, array('align' => 'left'));
		$table -> setColAttributes(2, array('align' => 'left'));
		$table -> setColAttributes(7, array('align' => 'right'));
		$table -> setColAttributes(8, array('align' => 'center'));
		$table -> setColAttributes(9, array('align' => 'center'));
		$table -> display();

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
		ob_end_clean();
		array_unshift($csv_content, $csv_headers); // adding headers before the content
		Export :: export_table_csv($csv_content, 'reporting_student_list');
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
	
	$table = new SortableTable('resources', 'count_item_resources', 'get_item_resources_data', 5, 20, 'DESC');
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
