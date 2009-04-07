<?php

/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2008 Dokeos SPRL

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/
/**
 * This script displays statistics on the current learning path (scorm)
 * 
 * This script must be included by lp_controller.php to get basic initialisation
 * @package dokeos.learnpath
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */

require_once('learnpath.class.php');
//require_once('scorm.class.php');
require_once ('resourcelinker.inc.php');
require_once ('../inc/lib/tracking.lib.php');
require_once ('../inc/lib/course.lib.php');


if(empty($_SESSION['_course']['id']) && isset($_GET['course']))
{
	$course_code = Database :: escape_string($_GET['course']);
}
else
{
	$course_code = $_SESSION['_course']['id'];
}

//The two following variables have to be declared by the includer script 
//$lp_id = $_SESSION['oLP']->get_id();
//$list = $_SESSION['oLP']->get_flat_ordered_items_list($lp_id);
//$user_id = $_user['user_id'];
//$stats_charset = $_SESSION['oLP']->encoding
if(!isset($origin))
	$origin = '';
if($origin != 'tracking')
{
	if (!empty ($stats_charset)) {
		$lp_charset = $stats_charset;
	} else {
		$lp_charset = api_get_setting('platform_charset');
	}
	$charset = $lp_charset;
	//$w = $tablewidth -20;
	$htmlHeadXtra[] = ''.'<style type="text/css" media="screen, projection">
		/*<![CDATA[*/
		@import "../css/public_admin/scorm.css";
		/*]]>*/
	</style>';
	include_once ('../inc/reduced_header.inc.php');
	echo '<body>';
}
else
{
    //Get learning path's encoding
    $TBL_LP = Database :: get_course_table(TABLE_LP_MAIN);
    $sql = "SELECT default_encoding FROM $TBL_LP " .
                "WHERE id = '".(int)$_GET['lp_id']."'";
    $res = api_sql_query($sql, __FILE__, __LINE__);
    if (Database :: num_rows($res) > 0)
    {
        $row = Database::fetch_array($res);
        $lp_charset = $row['default_encoding'];
    }
}

// The dokeos interface's encoding
$dokeos_charset = api_get_setting('platform_charset');
$output = '';
//if display in fullscreen required
if (!empty($_GET['fs']) && strcmp($_GET['fs'], 'true') == 0) 
{
	$output .= '<table width="100%" align="center">';
} 
else 
{
	$output .= '<table width="100%">';
}

//check if the user asked for the "extend all" option
$extend_all_link = '';
$extend_all = 0;

if ($origin == 'tracking') {
	$url_suffix = '&course=' . $_GET['course'] . '&student_id=' . $_GET['student_id'] . '&lp_id=' . $_GET['lp_id'] . '&origin=' . $_GET['origin'];
} else {
	$url_suffix = '';
}

if (!empty ($_GET['extend_all'])) {
	$extend_all_link = '<a href="' . api_get_self() . '?action=stats' . $url_suffix . '"><img src="../img/view_less_stats.gif" alt="fold_view" border="0" title="'.get_lang('HideAllAttempts').'"></a>';
	$extend_all = 1;
} else {
	$extend_all_link = '<a href="' . api_get_self() . '?action=stats&extend_all=1' . $url_suffix . '"><img src="../img/view_more_stats.gif" alt="extend_view" border="0" title="'.get_lang('ShowAllAttempts').'"></a>';
}

if ($origin != 'tracking') {
	$output .= "<tr><td><div class='title'>" . htmlentities(get_lang('ScormMystatus'), ENT_QUOTES, $dokeos_charset) . "</div></td></tr>";
}
$output .= "<tr><td>&nbsp;</td></tr>" . "<tr><td>" . "<table border='0' class='data_table'><tr>\n" . '<td width="16">' . $extend_all_link . '</td>' . '<td colspan="4" class="title"><div class="mystatusfirstrow">' . htmlentities(get_lang('ScormLessonTitle'), ENT_QUOTES, $dokeos_charset) . "</div></td>\n" . '<td colspan="2" class="title"><div class="mystatusfirstrow">' . htmlentities(get_lang('ScormStatus'), ENT_QUOTES, $dokeos_charset) . "</div></td>\n" . '<td colspan="2" class="title"><div class="mystatusfirstrow">' . htmlentities(get_lang('ScormScore'), ENT_QUOTES, $dokeos_charset) . "</div></td>\n" . '<td colspan="2" class="title"><div class="mystatusfirstrow">' . htmlentities(get_lang('ScormTime'), ENT_QUOTES, $dokeos_charset) . "</div></td><td class='title'><div class='mystatusfirstrow'>" . htmlentities(get_lang('Actions'), ENT_QUOTES, $dokeos_charset) . "</div></td></tr>\n";
//going through the items using the $items[] array instead of the database order ensures
// we get them in the same order as in the imsmanifest file, which is rather random when using
// the database table
$TBL_LP_ITEM = Database :: get_course_table('lp_item');
$TBL_LP_ITEM_VIEW = Database :: get_course_table('lp_item_view');
$TBL_LP_VIEW = Database :: get_course_table('lp_view');
$tbl_stats_exercices = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
$tbl_stats_attempts= Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
$tbl_quiz_questions= Database :: get_course_table(TABLE_QUIZ_QUESTION);
$sql = "SELECT max(view_count) FROM $TBL_LP_VIEW " .
"WHERE lp_id = $lp_id AND user_id = '" . $user_id . "'";
$res = api_sql_query($sql, __FILE__, __LINE__);
$view = '';
$num = 0;
if (Database :: num_rows($res) > 0) {
	$myrow = Database :: fetch_array($res);
	$view = $myrow[0];
}

$counter = 0;
//error_log('New LP - Querying views for latest attempt: '.$sql,0);
$total_score = 0;
$total_max_score = 0;
$total_time = 0;
$h = get_lang('h');

if (!empty($export_csv)) {
	$csv_content[] = array (
		get_lang('ScormLessonTitle'),
		get_lang('ScormStatus'),
		get_lang('ScormScore'),
		get_lang('ScormTime')
	);
}

// get attempts of a exercise
if (isset($_GET['lp_id']) && isset($_GET['my_lp_id'])) {
	$clean_lp_item_id = Database::escape_string($_GET['my_lp_id']);
	$clean_lp_id = Database::escape_string($_GET['lp_id']);
	$clean_course_code = Database :: escape_string($course_code);
	$sql_path = "SELECT path FROM $TBL_LP_ITEM WHERE id = '$clean_lp_item_id' AND lp_id = '$clean_lp_id'";
	$res_path = api_sql_query($sql_path,__FILE__,__LINE__); 
	$row_path = Database::fetch_array($res_path);
	
	if (Database::num_rows($res_path) > 0 ){		
		if ($origin != 'tracking') {		    
			$sql_attempts = 'SELECT * FROM ' . $tbl_stats_exercices . ' WHERE exe_exo_id="' . (int)$row_path['path'] . '" AND exe_user_id="' . (int)api_get_user_id() . '" AND orig_lp_id = "'.(int)$clean_lp_id.'" AND orig_lp_item_id = "'.(int)$clean_lp_item_id.'" AND exe_cours_id="' . $clean_course_code. '" AND status <> "incomplete" ORDER BY exe_date';
		} else {										
			$sql_attempts = 'SELECT * FROM ' . $tbl_stats_exercices . ' WHERE exe_exo_id="' . (int)$row_path['path'] . '" AND exe_user_id="' . (int)$_GET['student_id'] . '" AND orig_lp_id = "'.(int)$clean_lp_id.'" AND orig_lp_item_id = "'.(int)$clean_lp_item_id.'" AND exe_cours_id="' . $clean_course_code. '" AND status <> "incomplete" ORDER BY exe_date';
		}		
	}					
						
}

$TBL_QUIZ = Database :: get_course_table('quiz');
foreach ($list as $my_item_id) {
	$extend_this = 0;
	$qry_order = 'DESC';
	if ((!empty ($_GET['extend_id']) and $_GET['extend_id'] == $my_item_id) OR $extend_all) {
		$extend_this = 1;
		$qry_order = 'ASC';
	}
	//prepare statement to go through each attempt

	if (!empty ($view)) {
		$sql = "SELECT iv.status as mystatus, v.view_count as mycount, " .
		"iv.score as myscore, iv.total_time as mytime, i.id as myid, i.lp_id as mylpid, " .
		"i.title as mytitle, i.max_score as mymaxscore, " .
		"iv.max_score as myviewmaxscore, " .
		"i.item_type as item_type, iv.view_count as iv_view_count, " .
		"iv.id as iv_id, path as path" .
		" FROM $TBL_LP_ITEM as i, $TBL_LP_ITEM_VIEW as iv, $TBL_LP_VIEW as v" .
		" WHERE i.id = iv.lp_item_id " .
		" AND i.id = $my_item_id " .
		" AND iv.lp_view_id = v.id " .
		" AND i.lp_id = $lp_id " .
		" AND v.user_id = " . $user_id . " " .
		" AND v.view_count = $view " .
		" ORDER BY iv.view_count $qry_order ";
	} else {
		$sql = "SELECT iv.status as mystatus, v.view_count as mycount, " .
		"iv.score as myscore, iv.total_time as mytime, i.id as myid, i.lp_id as mylpid,  " .
		"i.title as mytitle, i.max_score as mymaxscore, " .
		"iv.max_score as myviewmaxscore, " .
		"i.item_type as item_type, iv.view_count as iv_view_count, " .
		"iv.id as iv_id, path as path " .
		" FROM $TBL_LP_ITEM as i, $TBL_LP_ITEM_VIEW as iv, $TBL_LP_VIEW as v " .
		" WHERE i.id = iv.lp_item_id " .
		" AND i.id = $my_item_id " .
		" AND iv.lp_view_id = v.id " .
		" AND i.lp_id = $lp_id " .
		" AND v.user_id = " . $user_id . " " .
		" ORDER BY iv.view_count $qry_order ";
	}
	
	
	$result = api_sql_query($sql, __FILE__, __LINE__);
	$num = Database :: num_rows($result);	
	$time_for_total = 'NaN';
	if (($extend_this || $extend_all) && $num > 0) {
		$row = Database :: fetch_array($result);
		//echo '<br><pre>'; print_r($row); echo '</pre><br>';
		//if there are several attempts, and the link to extend has been clicked, show each attempt...
		if (($counter % 2) == 0) {
			$oddclass = "row_odd";
		} else {
			$oddclass = "row_even";
		}
        $extend_link='';
		if (!empty($inter_num)) {
			$extend_link = '<a href="' . api_get_self() . '?action=stats&fold_id=' . $my_item_id . $url_suffix . '"><img src="../img/visible.gif" alt="fold_view" border="0"></a>' . "\n";
		}
		$title = $row['mytitle'];
		$title = stripslashes(html_entity_decode($title, ENT_QUOTES, $dokeos_charset));

		if (empty ($title)) {
			$title = rl_get_resource_name(api_get_course_id(), $lp_id, $row['myid']);
		}
		
		if ($row['item_type'] != 'dokeos_chapter') {
			$correct_test_link = array ();
			if ($row['item_type'] == 'quiz') 
			{			
				if ($origin != 'tracking') {
					 $sql_last_attempt = 'SELECT exe_id FROM ' . $tbl_stats_exercices . ' WHERE exe_exo_id="' . $row['path'] . '" AND exe_user_id="' . api_get_user_id() . '" AND orig_lp_id = "'.$lp_id.'" AND orig_lp_item_id = "'.$row['myid'].'" AND exe_cours_id="' . $course_code . '" AND status <> "incomplete" ORDER BY exe_date DESC limit 1';
				} else {
					 $sql_last_attempt = 'SELECT exe_id FROM ' . $tbl_stats_exercices . ' WHERE exe_exo_id="' . $row['path'] . '" AND exe_user_id="' . $_GET['student_id'] . '" AND orig_lp_id = "'.$lp_id.'" AND orig_lp_item_id = "'.$row['myid'].'" AND exe_cours_id="' . $course_code . '" AND status <> "incomplete" ORDER BY exe_date DESC limit 1';
				}

				$resultLastAttempt = api_sql_query($sql_last_attempt, __FILE__, __LINE__);

				$num = Database :: num_rows($resultLastAttempt);
				
				if ($num > 0) {
					if ($num > 1) {
						while ($rowLA = Database :: fetch_row($resultLastAttempt)) {
							
							$id_last_attempt = $rowLA[0];
							if ($origin != 'tracking') {
								$correct_test_link = '<a href="../exercice/exercise_show.php?origin=student_progress&id=' . $id_last_attempt . '&cidReq=' . $course_code . '" target="_parent"><img src="' . api_get_path(WEB_IMG_PATH) . 'quiz.gif"></a>';
							} else {
								$correct_test_link = '<a href="../exercice/exercise_show.php?origin=tracking_course&myid='.$my_id.'&my_lp_id='.$my_lp_id.'&id=' . $id_last_attempt . '&cidReq=' . $course_code . '&student=' . $_GET['student_id'] . '" target="_parent"><img src="' . api_get_path(WEB_IMG_PATH) . 'quiz.gif"></a>';
							}
						}
					} else {
						$id_last_attempt = Database :: result($resultLastAttempt, 0, 0);
						if ($origin != 'tracking') {
							$correct_test_link = '<a href="../exercice/exercise_show.php?origin=student_progress&id=' . $id_last_attempt . '&cidReq=' . $course_code . '" target="_parent"><img src="' . api_get_path(WEB_IMG_PATH) . 'quiz.gif"></a>';
						} else {
							$correct_test_link = '<a href="../exercice/exercise_show.php?origin=tracking_course&myid='.$my_id.'&my_lp_id='.$my_lp_id.'&id=' . $id_last_attempt . '&cidReq=' . $course_code . '&student=' . $_GET['student_id'] . '" target="_parent"><img src="' . api_get_path(WEB_IMG_PATH) . 'quiz.gif"></a>';
						}
					}
				}

			} else {
				$correct_test_link = '-';
			}
			//new attempt
			$output .= "<tr class='$oddclass'>\n" . "<td>$extend_link</td>\n" . '<td colspan="4" class="content"><div class="mystatus">' . htmlentities($title, ENT_QUOTES, $lp_charset) . "</div></td>\n" . '<td colspan="2" class="content"></td>' . "\n" . '<td colspan="2" class="content"></td>' . "\n" . '<td colspan="2" class="content"></td><td class="content"></td>' . "\n" . "</tr>\n";
		}

		$counter++;
		do {
			//check if there are interactions below
			$extend_attempt_link = '';
			$extend_this_attempt = 0;
			if ((learnpath :: get_interactions_count_from_db($row['iv_id']) > 0 || learnpath :: get_objectives_count_from_db($row['iv_id']) > 0) && !$extend_all) {
				if (!empty ($_GET['extend_attempt_id']) && $_GET['extend_attempt_id'] == $row['iv_id']) {
					//the extend button for this attempt has been clicked
					$extend_this_attempt = 1;
					$extend_attempt_link = '<a href="' . api_get_self() . '?action=stats&extend_id=' . $my_item_id . '&fold_attempt_id=' . $row['iv_id'] . $url_suffix . '"><img src="../img/visible.gif" alt="fold_attempt_view" border="0"></a>' . "\n";
				} else { //same case if fold_attempt_id is set, so not implemented explicitly
					//the extend button for this attempt has not been clicked
					$extend_attempt_link = '<a href="' . api_get_self() . '?action=stats&extend_id=' . $my_item_id . '&extend_attempt_id=' . $row['iv_id'] . $url_suffix . '"><img src="../img/invisible.gif" alt="extend_attempt_view" border="0"></a>' . "\n";
				}
			}

			if (($counter % 2) == 0) {
				$oddclass = "row_odd";
			} else {
				$oddclass = "row_even";
			}
			$lesson_status = $row['mystatus'];
			$score = $row['myscore'];
			$time_for_total = $row['mytime'];
			$time = learnpathItem :: get_scorm_time('js', $row['mytime']);
			$type;
			$scoIdentifier = $row['myid'];
			if ($score == 0) {
				$maxscore = $row['mymaxscore'];
			} else {
				if ($row['item_type'] == 'sco') {

					if (!empty ($row['myviewmaxscore']) && $row['myviewmaxscore'] > 0) {
						$maxscore = $row['myviewmaxscore'];
					}
					elseif ($row['myviewmaxscore'] === '') {
						$maxscore = 0;
					} else {
						$maxscore = $row['mymaxscore'];
					}
				}
				else 
				{
					if ($row['item_type'] == 'quiz')
					{						
						$myid= $row['myid'];							
						// selecting the max score from an attempt
						$sql = "SELECT SUM(t.ponderation) as maxscore from ( SELECT distinct question_id, marks,ponderation FROM $tbl_stats_attempts as at " .
							   "INNER JOIN  $tbl_quiz_questions as q  on(q.id = at.question_id) where exe_id ='$id_last_attempt' ) as t";
																		
						$result = api_sql_query($sql, __FILE__, __LINE__);
						$row_max_score = Database :: fetch_array($result);							
						$maxscore = $row_max_score['maxscore'];	
					} else {
						$maxscore = $row['mymaxscore'];
					}
				}
			}
			//Remove "NaN" if any (@todo: locate the source of these NaN)
			$time = str_replace('NaN', '00' . $h . '00\'00"', $time);
			if (($lesson_status == 'completed') or ($lesson_status == 'passed')) {
				$color = 'green';
			} else {
				$color = 'black';
			}
			$mylanglist = array (
				'completed' => 'ScormCompstatus',
				'incomplete' => 'ScormIncomplete',
				'failed' => 'ScormFailed',
				'passed' => 'ScormPassed',
				'browsed' => 'ScormBrowsed',
				'not attempted' => 'ScormNotAttempted',
				
			);
			
			$my_lesson_status = htmlentities(get_lang($mylanglist[$lesson_status]), ENT_QUOTES, $dokeos_charset);
		
			if ($origin != 'tracking') {					
					$sql_attempts = 'SELECT * FROM ' . $tbl_stats_exercices . ' WHERE exe_exo_id="' . (int)$row['path'] . '" AND exe_user_id="' . (int)api_get_user_id() . '" AND orig_lp_id = "'.(int)$lp_id.'" AND orig_lp_item_id = "'.(int)$row['myid'].'" AND exe_cours_id="' . Database :: escape_string($course_code) . '" AND status <> "incomplete" ORDER BY exe_date';
			} else {										
					$sql_attempts = 'SELECT * FROM ' . $tbl_stats_exercices . ' WHERE exe_exo_id="' . (int)$row['path'] . '" AND exe_user_id="' . (int)$_GET['student_id'] . '" AND orig_lp_id = "'.(int)$lp_id.'" AND orig_lp_item_id = "'.(int)$row['myid'].'" AND exe_cours_id="' . Database :: escape_string($course_code) . '" AND status <> "incomplete" ORDER BY exe_date';
			}													 
				
			$res_attempts = api_sql_query($sql_attempts,__FILE__,__LINE__);
			$num_attempts = Database :: num_rows($res_attempts);				
									
			if ($row['item_type'] === 'quiz') {									
				if ($num_attempts > 0) {					
					$n=1;										
					while ($row_attempts = Database :: fetch_array($res_attempts)) {
						$my_score = $row_attempts['exe_result'];
						$my_maxscore = $row_attempts['exe_weighting'];
						$my_exe_id	= $row_attempts['exe_id'];
						$my_orig_lp = $row_attempts['orig_lp_id'];
						$my_orig_lp_item = $row_attempts['orig_lp_item_id'];						
						$mktime_start_date = convert_mysql_date($row_attempts['start_date']);
						$mktime_exe_date = convert_mysql_date($row_attempts['exe_date']);
						$mytime = ((int)$mktime_exe_date-(int)$mktime_start_date);					 
						$time_attemp = learnpathItem :: get_scorm_time('js', $mytime);
						$time_attemp = str_replace('NaN', '00' . $h . '00\'00"', $time_attemp);
																		
						$output .= '<tr class="'.$oddclass.'"><td>&nbsp;</td><td>'.$extend_attempt_link.'</td><td colspan="3">' . htmlentities(get_lang('Attempt'), ENT_QUOTES, $dokeos_charset) . ' ' . $n . '</td>'				
					 			. '<td colspan="2"><font color="' . $color . '"><div class="mystatus">' . $my_lesson_status . '</div></font></td><td colspan="2"><div class="mystatus" align="center">' . ($my_score == 0 ? '0.00/'.$my_maxscore : ($my_maxscore == 0 ? $my_score : $my_score . '/' . $my_maxscore)) . '</div></td><td colspan="2"><div class="mystatus">' . $time_attemp . '</div></td>';
					 	if ($origin != 'tracking') {
					 		$output .= '<td><a href="../exercice/exercise_show.php?origin=student_progress&myid='.$my_orig_lp.'&my_lp_id='.$my_orig_lp_item.'&id=' . $my_exe_id . '&cidReq=' . $course_code . '&student=' . $_GET['student_id'] . '" target="_parent"><img src="' . api_get_path(WEB_IMG_PATH) . 'quiz.gif" title="'.get_lang('ShowAttempt').'"></a></td>';						
						} else {
							$output .= '<td><a href="../exercice/exercise_show.php?origin=tracking_course&myid='.$my_orig_lp.'&my_lp_id='.$my_orig_lp_item.'&id=' . $my_exe_id . '&cidReq=' . $course_code . '&student=' . $_GET['student_id'] . '&total_time='.$mytime.'" target="_parent"><img src="' . api_get_path(WEB_IMG_PATH) . 'quiz.gif" title="'.get_lang('ShowAndQualifyAttempt').'"></a></td>';							
						}		 				 	        
					 	$output .= '</tr>';
						$n++;												
					}																								
				}						
			}

			$counter++;
			if ($extend_this_attempt OR $extend_all) {
				$list1 = learnpath :: get_iv_interactions_array($row['iv_id']);
				foreach ($list1 as $id => $interaction) {
					if (($counter % 2) == 0) {
						$oddclass = "row_odd";
					} else {
						$oddclass = "row_even";
					}
					$output .= "<tr class='$oddclass'>\n" . '<td></td>' . "\n" . '<td></td>' . "\n" . '<td>&nbsp;</td>' . "\n" . '<td>' . $interaction['order_id'] . '</td>' . "\n" . '<td>' . $interaction['id'] . '</td>' . "\n"
					//."<td><font color='$color'><div class='mystatus'>".htmlentities($array_status[$lesson_status],ENT_QUOTES,$lp_charset)."</div></font></td>\n"
					 . '<td colspan="2">' . $interaction['type'] . "</td>\n"
					//.'<td>'.$interaction['correct_responses']."</td>\n"
					 . '<td>' . urldecode($interaction['student_response']) . "</td>\n" . '<td>' . $interaction['result'] . "</td>\n" . '<td>' . $interaction['latency'] . "</td>\n" . '<td>' . $interaction['time'] . "</td>\n<td></td>\n</tr>\n";
					$counter++;
				}
				$list2 = learnpath :: get_iv_objectives_array($row['iv_id']);
				foreach ($list2 as $id => $interaction) {
					if (($counter % 2) == 0) {
						$oddclass = "row_odd";
					} else {
						$oddclass = "row_even";
					}
					$output .= "<tr class='$oddclass'>\n" . '<td></td>' . "\n" . '<td></td>' . "\n" . '<td>&nbsp;</td>' . "\n" . '<td>' . $interaction['order_id'] . '</td>' . "\n" . '<td colspan="2">' . $interaction['objective_id'] . '</td>' . "\n" .
					'<td colspan="2">' . $interaction['status'] . "</td>\n" .
					'<td>' . $interaction['score_raw'] . "</td>\n" . '<td>' . $interaction['score_max'] . "</td>\n" . '<td>' . $interaction['score_min'] . "</td>\n<td></td>\n</tr>\n";
					$counter++;
				}
			}
		} while ($row = Database :: fetch_array($result));
	}
	elseif ($num > 0) {
		$row = Database :: fetch_array($result);

		//check if there are interactions below
		$extend_attempt_link = '';
		$extend_this_attempt = 0;
		$inter_num = learnpath :: get_interactions_count_from_db($row['iv_id']);
		$objec_num = learnpath :: get_objectives_count_from_db($row['iv_id']);
		if (($inter_num > 0 || $objec_num > 0) && !$extend_all) {
			if (!empty ($_GET['extend_attempt_id']) && $_GET['extend_attempt_id'] == $row['iv_id']) {
				//the extend button for this attempt has been clicked
				$extend_this_attempt = 1;
				$extend_attempt_link = '<a href="' . api_get_self() . '?action=stats&extend_id=' . $my_item_id . '&fold_attempt_id=' . $row['iv_id'] . $url_suffix . '"><img src="../img/visible.gif" alt="fold_attempt_view" border="0"></a>' . "\n";
			} else { //same case if fold_attempt_id is set, so not implemented explicitly
				//the extend button for this attempt has not been clicked
				$extend_attempt_link = '<a href="' . api_get_self() . '?action=stats&extend_id=' . $my_item_id . '&extend_attempt_id=' . $row['iv_id'] . $url_suffix . '"><img src="../img/invisible.gif" alt="extend_attempt_view" border="0"></a>' . "\n";
			}
		}

		if (($counter % 2) == 0) {
			$oddclass = "row_odd";
		} else {
			$oddclass = "row_even";
		}
		//$extend_link = '<img src="../img/invisible.gif" alt="extend_disabled">';
		$extend_link = '';
		if ($inter_num > 1) {
			$extend_link = '<a href="' . api_get_self() . '?action=stats&extend_id=' . $my_item_id . '&extend_attempt_id=' . $row['iv_id'] . $url_suffix . '"><img src="../img/invisible.gif" alt="extend_view" border="0"></a>';
		}
		if (($counter % 2) == 0) {
			$oddclass = "row_odd";
		} else {
			$oddclass = "row_even";
		}
		$lesson_status = $row['mystatus'];
		$score = $row['myscore'];
		$subtotal_time = $row['mytime'];
		//if($row['mytime']==0){
		while ($tmp_row = Database :: fetch_array($result)) {
			$subtotal_time += $tmp_row['mytime'];
		}
		//}
		$time_for_total = $subtotal_time;
		$time = learnpathItem :: get_scorm_time('js', $subtotal_time);
		$scoIdentifier = $row['myid'];
		$title = $row['mytitle'];
		$title = stripslashes(html_entity_decode($title, ENT_QUOTES, $dokeos_charset));
		
		
		// selecting the exe_id from stats attempts tables in order to look the max score value
		if ($origin != 'tracking') {
			$sql_last_attempt = 'SELECT exe_id FROM ' . $tbl_stats_exercices . ' WHERE exe_exo_id="' . $row['path'] . '" AND exe_user_id="' . api_get_user_id() . '" AND orig_lp_id = "'.$lp_id.'" AND orig_lp_item_id = "'.$row['myid'].'" AND exe_cours_id="' . $course_code . '" AND status <> "incomplete" ORDER BY exe_date DESC limit 1';
		} else {
			$sql_last_attempt = 'SELECT exe_id FROM ' . $tbl_stats_exercices . ' WHERE exe_exo_id="' . $row['path'] . '" AND exe_user_id="' . $_GET['student_id'] . '" AND orig_lp_id = "'.$lp_id.'" AND orig_lp_item_id = "'.$row['myid'].'" AND exe_cours_id="' . $course_code . '" AND status <> "incomplete" ORDER BY exe_date DESC limit 1';
		}

		$resultLastAttempt = api_sql_query($sql_last_attempt, __FILE__, __LINE__);
		$num = Database :: num_rows($resultLastAttempt);
		if ($num > 0) {
			if ($num > 1) {
				while ($rowLA = Database :: fetch_row($resultLastAttempt)) {
					$id_last_attempt = $rowLA[0];					
				}
			} else {
				$id_last_attempt = Database :: result($resultLastAttempt, 0, 0);				
			}
		}
	
		if ($score == 0) 
		{
			$maxscore = $row['mymaxscore'];
		}
		else 
		{
			if ($row['item_type'] == 'sco') 
			{
				if (!empty ($row['myviewmaxscore']) and $row['myviewmaxscore'] > 0) {
					$maxscore = $row['myviewmaxscore'];
				}
				elseif ($row['myviewmaxscore'] === '') {
					$maxscore = 0;
				} else {
					$maxscore = $row['mymaxscore'];
				}
			} 
			else 
			{
				if ($row['item_type'] == 'quiz') 
				{
					$myid= $row['myid'];												
					// selecting the max score from an attempt
					$sql = "SELECT SUM(t.ponderation) as maxscore from ( SELECT distinct question_id, marks,ponderation FROM $tbl_stats_attempts as at " .
						  "INNER JOIN  $tbl_quiz_questions as q  on(q.id = at.question_id) where exe_id ='$id_last_attempt' ) as t";
																
					$result = api_sql_query($sql, __FILE__, __LINE__);
					$row_max_score = Database :: fetch_array($result);							
					$maxscore = $row_max_score['maxscore'];
				} 
				else 
				{
					$maxscore = $row['mymaxscore'];
				}
			}
		}
		if (empty ($title)) {
			$title = rl_get_resource_name(api_get_course_id(), $lp_id, $row['myid']);
		}
		//Remove "NaN" if any (@todo: locate the source of these NaN)
		//$time = str_replace('NaN', '00'.$h.'00\'00"', $time);

		if (($lesson_status == 'completed') or ($lesson_status == 'passed')) {
			$color = 'green';
		} else {
			$color = 'black';
		}
		$mylanglist = array (
			'completed' => 'ScormCompstatus',
			'incomplete' => 'ScormIncomplete',
			'failed' => 'ScormFailed',
			'passed' => 'ScormPassed',
			'browsed' => 'ScormBrowsed',
			'not attempted' => 'ScormNotAttempted',
			
		);
		$my_lesson_status = htmlentities(get_lang($mylanglist[$lesson_status]), ENT_QUOTES, $dokeos_charset);
		$my_id = $row['myid'];
		$my_lp_id = $row['mylpid'];
		if ($row['item_type'] != 'dokeos_chapter') {
			if ($row['item_type'] == 'quiz') {
				$correct_test_link = '';
				$my_url_suffix ='';
				if ($origin != 'tracking' && $origin != 'tracking_course') {
					$my_url_suffix = '&course=' . api_get_course_id() . '&student_id=' . api_get_user_id() . '&lp_id=' . Security::remove_XSS($row['mylpid']);
					$sql_last_attempt = 'SELECT exe_id FROM ' . $tbl_stats_exercices . ' WHERE exe_exo_id="' . $row['path'] . '" AND exe_user_id="' . api_get_user_id() . '" AND orig_lp_id = "'.$lp_id.'" AND orig_lp_item_id = "'.$row['myid'].'" AND exe_cours_id="' . $course_code . '" AND status <> "incomplete" ORDER BY exe_date DESC ';
				} else {
					$my_url_suffix = '&course=' . $_GET['course'] . '&student_id=' . Security::remove_XSS($_GET['student_id']) . '&lp_id=' . Security::remove_XSS($row['mylpid']).'&origin=' . Security::remove_XSS($_GET['origin']);									
					$sql_last_attempt = 'SELECT exe_id FROM ' . $tbl_stats_exercices . ' WHERE exe_exo_id="' . $row['path'] . '" AND exe_user_id="' . Database :: escape_string($_GET['student_id']) . '" AND orig_lp_id = "'.$lp_id.'" AND orig_lp_item_id = "'.$row['myid'].'" AND exe_cours_id="' . Database :: escape_string($_GET['course']) . '" AND status <> "incomplete"  ORDER BY exe_date DESC ';
				}

				$resultLastAttempt = api_sql_query($sql_last_attempt, __FILE__, __LINE__);
				$num = Database :: num_rows($resultLastAttempt);
				if ($num > 0) {																																							
					if (isset($_GET['extend_attempt']) && $_GET['extend_attempt'] == 1 && (isset($_GET['lp_id']) && $_GET['lp_id'] == $my_lp_id) && (isset($_GET['my_lp_id']) && $_GET['my_lp_id'] == $my_id)  ) {						
						$correct_test_link = '<a href="' . api_get_self() . '?action=stats' . $my_url_suffix . '&my_ext_lp_id='.$my_id.'#anchor_ext_hidden_'.$my_lp_id.'"><img src="../img/view_less_stats.gif" alt="fold_view" border="0"></a>';
						$extend_attempt = 1;
					} else {						
						$correct_test_link = '<a href="' . api_get_self() . '?action=stats&extend_attempt=1'.$my_url_suffix.'&my_lp_id='.$my_id.'#anchor_ext_show_'.$my_lp_id.'"><img src="../img/view_more_stats.gif" alt="extend_view" border="0" title="'.get_lang('ShowAllAttemptsByExercise').'"></a>';
					}
				} else {
					$correct_test_link = '-';
				}
			} else {
				$correct_test_link = '-';
			}

			//."<td><font color='$color'><div class='mystatus'>".htmlentities($array_status[$lesson_status],ENT_QUOTES,$lp_charset)."</div></font></td>\n"

			if ( (isset($_GET['lp_id']) && $_GET['lp_id'] == $my_lp_id ) && (isset($_GET['my_lp_id']) && $_GET['my_lp_id'] == $my_id)) {
				$output .= "<tr class='$oddclass' id='anchor_ext_show_$my_lp_id'>\n" . "<td>$extend_link</td>\n" . '<td colspan="4"><div class="mystatus">' . htmlentities($title, ENT_QUOTES, $lp_charset) . '</div></td>' . "\n";				
				$output .= '<td colspan="2">&nbsp;</td><td colspan="2">&nbsp;</td><td colspan="2">&nbsp;</td><td>'.$correct_test_link.'</td></tr>';
				$output .= "</tr>\n";						 
			} else {
				if ( (isset($_GET['lp_id']) && $_GET['lp_id'] == $my_lp_id ) && (isset($_GET['my_ext_lp_id']) && $_GET['my_ext_lp_id'] == $my_id)) {
					$output .= "<tr class='$oddclass' id='anchor_ext_hidden_$my_lp_id'>\n"; 
				} else {
					$output .= "<tr class='$oddclass'>\n";	
				}
				$output .= "<td>$extend_link</td>\n" . '<td colspan="4"><div class="mystatus">' . htmlentities($title, ENT_QUOTES, $lp_charset) . '</div></td>' . "\n";							
				$output .= '<td colspan="2"><font color="' . $color . '"><div class="mystatus">' . $my_lesson_status . "</div></font></td>\n" . '<td colspan="2"><div class="mystatus" align="center">';			  
				 if ($row['item_type'] == 'quiz') {
				 	$output .= ($score == 0 ? '0/'.$maxscore : ($maxscore == 0 ? $score : $score . '/' . $maxscore));//$maxscore == 0 ? $score : $score . '/' . $maxscore;
				 } else {
				    $output .= ($score == 0 ? '-' : ($maxscore == 0 ? $score : $score . '/' . $maxscore));	
				 }			 			  
				 $output .= "</div></td>\n" . '<td colspan="2"><div class="mystatus">' . $time . "</div></td><td>$correct_test_link</td>\n";
				 $output .= "</tr>\n";	
			}			 
			 			 
			if (!empty($export_csv)) {
				$temp = array ();
				$temp[] = $title;
				$temp[] = html_entity_decode($my_lesson_status);
				if ($row['item_type'] == 'quiz') {
					$temp[] = ($score == 0 ? '0/'.$maxscore : ($maxscore == 0 ? $score : $score . '/' . $maxscore));
				} else {
					$temp[] = ($score == 0 ? '-' : ($maxscore == 0 ? $score : $score . '/' . $maxscore));
				}
				$temp[] = $time;
				$csv_content[] = $temp;
			}
		}

		$counter++;

		if ($extend_this_attempt OR $extend_all) {
			$list1 = learnpath :: get_iv_interactions_array($row['iv_id']);
			foreach ($list1 as $id => $interaction) {
				if (($counter % 2) == 0) {
					$oddclass = "row_odd";
				} else {
					$oddclass = "row_even";
				}
				$output .= "<tr class='$oddclass'>\n" . '<td></td>' . "\n" . '<td></td>' . "\n" . '<td>&nbsp;</td>' . "\n" . '<td>' . $interaction['order_id'] . '</td>' . "\n" . '<td>' . $interaction['id'] . '</td>' . "\n"
				//."<td><font color='$color'><div class='mystatus'>".htmlentities($array_status[$lesson_status],ENT_QUOTES,$lp_charset)."</div></font></td>\n"
				 . '<td colspan="2">' . $interaction['type'] . "</td>\n"
				//.'<td>'.$interaction['correct_responses']."</td>\n"
				 . '<td>' . urldecode($interaction['student_response']) . "</td>\n" . '<td>' . $interaction['result'] . "</td>\n" . '<td>' . $interaction['latency'] . "</td>\n" . '<td>' . $interaction['time'] . "</td>\n<td></td>\n</tr>\n";
				$counter++;
			}
			$list2 = learnpath :: get_iv_objectives_array($row['iv_id']);
			foreach ($list2 as $id => $interaction) {
				if (($counter % 2) == 0) {
					$oddclass = "row_odd";
				} else {
					$oddclass = "row_even";
				}
				$output .= "<tr class='$oddclass'>\n" . '<td></td>' . "\n" . '<td></td>' . "\n" . '<td>&nbsp;</td>' . "\n" . '<td>' . $interaction['order_id'] . '</td>' . "\n" . '<td colspan="2">' . $interaction['objective_id'] . '</td>' . "\n" .
				'<td colspan="2">' . $interaction['status'] . "</td>\n" .
				'<td>' . $interaction['score_raw'] . "</td>\n" . '<td>' . $interaction['score_max'] . "</td>\n" . '<td>' . $interaction['score_min'] . "</td>\n<td></td>\n</tr>\n";
				$counter++;
			}
		}
																																												
		// attempts list by exercise 
		if ( (isset($_GET['lp_id']) && $_GET['lp_id'] == $my_lp_id ) && (isset($_GET['my_lp_id']) && $_GET['my_lp_id'] == $my_id)) {
			
				$res_attempts = api_sql_query($sql_attempts,__FILE__,__LINE__);
				$num_attempts = Database :: num_rows($res_attempts);																	
				if ($row['item_type'] === 'quiz') {									
					if ($num_attempts > 0) {					
						$n=1;										
						while ($row_attempts = Database :: fetch_array($res_attempts)) {
							$my_score = $row_attempts['exe_result'];
							$my_maxscore = $row_attempts['exe_weighting'];
							$my_exe_id	= $row_attempts['exe_id'];
							$my_orig_lp = $row_attempts['orig_lp_id'];
							$my_orig_lp_item = $row_attempts['orig_lp_item_id'];												
							$mktime_start_date = convert_mysql_date($row_attempts['start_date']);
							$mktime_exe_date = convert_mysql_date($row_attempts['exe_date']);
							$mytime = ((int)$mktime_exe_date-(int)$mktime_start_date);					 
							$time_attemp = learnpathItem :: get_scorm_time('js', $mytime);
							$time_attemp = str_replace('NaN', '00' . $h . '00\'00"', $time_attemp);
																			
							$output .= '<tr class="'.$oddclass.'" ><td>&nbsp;</td><td>'.$extend_attempt_link.'</td><td colspan="3">' . htmlentities(get_lang('Attempt'), ENT_QUOTES, $dokeos_charset) . ' ' . $n . '</td>'				
						 			. '<td colspan="2"><font color="' . $color . '"><div class="mystatus">' . $my_lesson_status . '</div></font></td><td colspan="2"><div class="mystatus" align="center">' . ($my_score == 0 ? '0.00/'.$my_maxscore : ($my_maxscore == 0 ? $my_score : $my_score . '/' . $my_maxscore)) . '</div></td><td colspan="2"><div class="mystatus">' . $time_attemp . '</div></td>';
						 	if ($origin != 'tracking') {
						 		$output .= '<td><a href="../exercice/exercise_show.php?origin=student_progress&myid='.$my_orig_lp.'&my_lp_id='.$my_orig_lp_item.'&id=' . $my_exe_id . '&cidReq=' . $course_code . '&student=' . $_GET['student_id'] . '" target="_parent"><img src="' . api_get_path(WEB_IMG_PATH) . 'quiz.gif" title="'.get_lang('ShowAttempt').'"></a></td>';						
							} else {
								$output .= '<td><a href="../exercice/exercise_show.php?origin=tracking_course&myid='.$my_orig_lp.'&my_lp_id='.$my_orig_lp_item.'&id=' . $my_exe_id . '&cidReq=' . $course_code . '&student=' . $_GET['student_id'] . '&total_time='.$mytime.'" target="_parent"><img src="' . api_get_path(WEB_IMG_PATH) . 'quiz.gif" title="'.get_lang('ShowAndQualifyAttempt').'"></a></td>';							
							}		 				 	        
						 	$output .= '</tr>';
							$n++;												
						}																								
					}
					$output .= '<tr><td colspan="12">&nbsp;</td></tr>';							
				}								
			}

	}

	$total_time += $time_for_total;
	//QUIZZ IN LP
	$a_my_id = array();	
	if (!empty($my_lp_id)) {
		$a_my_id[] = $my_lp_id;	
	}	    
}

if (!empty($a_my_id)) {
	$my_studen_id = 0;
	$my_course_id = '';
	if ($origin == 'tracking') {
		$my_studen_id = intval($_GET['student_id']);
		$my_course_id = Database::escape_string($_GET['course']);
	} else {
		$my_studen_id = intval(api_get_user_id());
		$my_course_id = Database::escape_string(api_get_course_id());
	}		
	$total_score = Tracking::get_avg_student_score($my_studen_id, $my_course_id, $a_my_id);			
} else {
	$total_score = 0;
}

$total_time = learnpathItem :: get_scorm_time('js', $total_time);
//$total_time = str_replace('NaN','00:00:00',$total_time);
$total_time = str_replace('NaN', '00' . $h . '00\'00"', $total_time);
$lp_type = learnpath :: get_type_static($lp_id);
$total_percent = 0;
$final_score = $total_score.'%';

if (($counter % 2) == 0) {
	$oddclass = "row_odd";
} else {
	$oddclass = "row_even";
}

if (empty($extend_all)) {
	$output .= "<tr class='$oddclass'>\n" . "<td></td>\n" . '<td colspan="4"><div class="mystatus"><i>' . htmlentities(get_lang('AccomplishedStepsTotal'), ENT_QUOTES, $dokeos_charset) . "</i></div></td>\n"
 			. '<td colspan="2"></td>' . "\n" . '<td colspan="2"><div class="mystatus" align="center">' . $final_score . "</div></td>\n" . '<td colspan="2"><div class="mystatus">' . $total_time . '</div></td><td></td>' . "\n" . "</tr>\n";		
}

$output .= "</table></td></tr></table>";

if (!empty($export_csv)) {
	$temp = array (
		'',
		'',
		'',
		''
	);
	$csv_content[] = $temp;
	$temp = array (
		get_lang('AccomplishedStepsTotal'),
		'',
		$final_score,
		$total_time
	);
	$csv_content[] = $temp;
	ob_end_clean();
	Export :: export_table_csv($csv_content, 'reporting_learning_path_details');
}

if ($origin != 'tracking') {
	$output .= "</body></html>";
}

if (empty($export_csv)) {
	echo $output;
}