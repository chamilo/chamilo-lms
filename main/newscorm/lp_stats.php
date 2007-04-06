<?php
//$id:$
/**
 * This script displays statistics on the current learning path (scorm)
 * 
 * This script must be included by lp_controller.php to get basic initialisation
 * @package dokeos.learnpath
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */
/**
 * Script
 */
require_once('learnpath.class.php');
//require_once('scorm.class.php');
require_once ('resourcelinker.inc.php');

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
		$charset_lang = $stats_charset;
	} else {
		$charset_lang = 'ISO-8859-1';
	}
	$dokeos_charset = 'ISO-8859-1';
	$charset = $charset_lang;
	$w = $tablewidth -20;
	$htmlHeadXtra[] = ''.'<style type="text/css" media="screen, projection">
		/*<![CDATA[*/
		@import "../css/default/scorm.css";
		/*]]>*/
	</style>';
	include_once ('../inc/reduced_header.inc.php');
	echo '<body>';
}

//if display in fullscreen required
if (strcmp($_GET["fs"], "true") == 0) {
	$output .= "<table align='center'>";
} else {
	$output .= "<table class='margin_table'>";
}

//check if the user asked for the "extend all" option
$extend_all_link = '';
$extend_all = 0;

if($origin == 'tracking')
{
	$url_suffix = '&course='.$_GET['course'].'&student_id='.$_GET['student_id'].'&lp_id='.$_GET['lp_id'];
}
else
{
	$url_suffix = '';
}
	
if (!empty ($_GET['extend_all'])) {
	$extend_all_link = '<a href="'.api_get_self().'?action=stats'.$url_suffix.'"><img src="../img/view_less_stats.gif" alt="fold_view" border="0"></a>';
	$extend_all = 1;
} else {
	$extend_all_link = '<a href="'.api_get_self().'?action=stats&extend_all=1'.$url_suffix.'"><img src="../img/view_more_stats.gif" alt="extend_view" border="0"></a>';
}

if($origin != 'tracking')
{
	$output .= "<tr><td><div class='title'>".htmlentities(get_lang('ScormMystatus'), ENT_QUOTES, $dokeos_charset)."</div></td></tr>";
}
$output .= "<tr><td>&nbsp;</td></tr>"."<tr><td>"."<table border='0' class='data_table'><tr>\n".'<td width="16">'.$extend_all_link.'</td>'.'<td colspan="4" class="title"><div class="mystatusfirstrow">'.htmlentities(get_lang('ScormLessonTitle'), ENT_QUOTES, $dokeos_charset)."</div></td>\n".'<td colspan="2" class="title"><div class="mystatusfirstrow">'.htmlentities(get_lang('ScormStatus'), ENT_QUOTES, $dokeos_charset)."</div></td>\n".'<td colspan="2" class="title"><div class="mystatusfirstrow">'.htmlentities(get_lang('ScormScore'), ENT_QUOTES, $dokeos_charset)."</div></td>\n".'<td colspan="2" class="title"><div class="mystatusfirstrow">'.htmlentities(get_lang('ScormTime'), ENT_QUOTES, $dokeos_charset)."</div></td></tr>\n";
//going through the items using the $items[] array instead of the database order ensures
// we get them in the same order as in the imsmanifest file, which is rather random when using
// the database table
$TBL_LP_ITEM = Database :: get_course_table('lp_item');
$TBL_LP_ITEM_VIEW = Database :: get_course_table('lp_item_view');
$TBL_LP_VIEW = Database :: get_course_table('lp_view');
$sql = "SELECT max(view_count) FROM $TBL_LP_VIEW WHERE lp_id = $lp_id AND user_id = '".$user_id."'";
$res = api_sql_query($sql, __FILE__, __LINE__);
$view = '';
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

if($export_csv)
{
	$csv_content[] = array ( 
							get_lang('ScormLessonTitle'),
							get_lang('ScormStatus'),
							get_lang('ScormScore'),
							get_lang('ScormTime')
						   );
}

foreach ($list as $my_item_id) {
	$extend_this = 0;
	$qry_order = 'DESC';
	if ((!empty ($_GET['extend_id']) and $_GET['extend_id'] == $my_item_id) OR $extend_all) {
		$extend_this = 1;
		$qry_order = 'ASC';
	}
	if (!empty ($view)) {
		$sql = "SELECT iv.status as mystatus, v.view_count as mycount, iv.score as myscore, iv.total_time as mytime, i.id as myid, i.title as mytitle, i.max_score as mymaxscore, i.item_type as item_type, iv.view_count as iv_view_count, iv.id as iv_id "." FROM $TBL_LP_ITEM as i, $TBL_LP_ITEM_VIEW as iv, $TBL_LP_VIEW as v "." WHERE i.id = iv.lp_item_id "." AND i.id = $my_item_id "." AND iv.lp_view_id = v.id "." AND i.lp_id = $lp_id "." AND v.user_id = ".$user_id.""." AND v.view_count = $view "." ORDER BY iv.view_count $qry_order ";
	} else {
		$sql = "SELECT iv.status as mystatus, v.view_count as mycount, iv.score as myscore, iv.total_time as mytime, i.id as myid, i.title as mytitle, i.max_score as mymaxscore, i.item_type as item_type, iv.view_count as iv_view_count, iv.id as iv_id "." FROM $TBL_LP_ITEM as i, $TBL_LP_ITEM_VIEW as iv, $TBL_LP_VIEW as v "." WHERE i.id = iv.lp_item_id "." AND i.id = $my_item_id "." AND iv.lp_view_id = v.id "." AND i.lp_id = $lp_id "." AND v.user_id = ".$user_id." "." ORDER BY iv.view_count $qry_order ";
	}
	$result = api_sql_query($sql, __FILE__, __LINE__);
	$num = Database :: num_rows($result);
	$time_for_total = 'NaN';
	if (($extend_this OR $extend_all) && $num > 0) {
		$row = Database :: fetch_array($result);

		//if there are several attempts, and the link to extend has been clicked...
		if (($counter % 2) == 0) {
			$oddclass = "row_odd";
		} else {
			$oddclass = "row_even";
		}
		if ($inter_num)
			$extend_link = '<a href="lp_controller.php?action=stats&fold_id='.$my_item_id.'"><img src="../img/visible.gif" alt="fold_view" border="0"></a>'."\n";

		$title = $row['mytitle'];
		$title = stripslashes($title);
		if (empty ($title)) {
			$title = rl_get_resource_name(api_get_course_id(), $lp_id, $row['myid']);
		}

		if ($row['item_type'] != 'dokeos_chapter') {
			$output .= "<tr class='$oddclass'>\n"."<td>$extend_link</td>\n".'<td colspan="4" class="content"><div class="mystatus">'.$title."</div></td>\n".'<td colspan="2" class="content"></td>'."\n".'<td colspan="2" class="content"></td>'."\n".'<td colspan="2" class="content"></td>'."\n"."</tr>\n";
		}

		$counter ++;
		do {
			//check if there are interactions below
			$extend_attempt_link = '';
			$extend_this_attempt = 0;
			if (learnpath :: get_interactions_count_from_db($row['iv_id']) > 0 && !$extend_all) {
				if (!empty ($_GET['extend_attempt_id']) && $_GET['extend_attempt_id'] == $row['iv_id']) {
					//the extend button for this attempt has been clicked
					$extend_this_attempt = 1;
					$extend_attempt_link = '<a href="lp_controller.php?action=stats&extend_id='.$my_item_id.'&fold_attempt_id='.$row['iv_id'].'"><img src="../img/visible.gif" alt="fold_attempt_view" border="0"></a>'."\n";
				} else { //same case if fold_attempt_id is set, so not implemented explicitly
					//the extend button for this attempt has not been clicked
					$extend_attempt_link = '<a href="lp_controller.php?action=stats&extend_id='.$my_item_id.'&extend_attempt_id='.$row['iv_id'].'"><img src="../img/invisible.gif" alt="extend_attempt_view" border="0"></a>'."\n";
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
				$maxscore = 0;
			} else {
				$maxscore = $row['mymaxscore'];
			}
			//Remove "NaN" if any (@todo: locate the source of these NaN)
			$time = str_replace('NaN', '00'.$h.'00\'00"', $time);
			if (($lesson_status == 'completed') or ($lesson_status == 'passed')) {
				$color = 'green';
			} else {
				$color = 'black';
			}
			$mylanglist = array ('completed' => 'ScormCompstatus', 'incomplete' => 'ScormIncomplete', 'failed' => 'ScormFailed', 'passed' => 'ScormPassed', 'browsed' => 'ScormBrowsed', 'not attempted' => 'ScormNotAttempted',);
			$my_lesson_status = htmlentities(get_lang($mylanglist[$lesson_status]), ENT_QUOTES, $dokeos_charset);
			//$my_lesson_status = get_lang($mylanglist[$lesson_status]);
			if ($row['item_type'] != 'dokeos_chapter') {
				$output .= "<tr class='$oddclass'>\n"."<td></td>\n"."<td>$extend_attempt_link</td>\n".'<td colspan="3">Attempt '.$row['iv_view_count']."</td>\n"
				//."<td><font color='$color'><div class='mystatus'>".htmlentities($array_status[$lesson_status],ENT_QUOTES,$charset_lang)."</div></font></td>\n"
				.'<td colspan="2"><font color="'.$color.'"><div class="mystatus">'.$my_lesson_status."</div></font></td>\n".'<td colspan="2"><div class="mystatus" align="center">'. ($score == 0 ? '-' : $score.'/'.$maxscore)."</div></td>\n".'<td colspan="2"><div class="mystatus">'.$time."</div></td>\n"."</tr>\n";
			}

			$counter ++;
			if ($extend_this_attempt OR $extend_all) {
				$list = learnpath :: get_iv_interactions_array($row['iv_id']);
				foreach ($list as $id => $interaction) {
					if (($counter % 2) == 0) {
						$oddclass = "row_odd";
					} else {
						$oddclass = "row_even";
					}
					$output .= "<tr class='$oddclass'>\n".'<td></td>'."\n".'<td></td>'."\n".'<td>&nbsp;</td>'."\n".'<td>'.$interaction['order_id'].'</td>'."\n".'<td>'.$interaction['id'].'</td>'."\n"
					//."<td><font color='$color'><div class='mystatus'>".htmlentities($array_status[$lesson_status],ENT_QUOTES,$charset_lang)."</div></font></td>\n"
					.'<td colspan="2">'.$interaction['type']."</td>\n"
					//.'<td>'.$interaction['correct_responses']."</td>\n"
					.'<td>'.$interaction['student_response']."</td>\n".'<td>'.$interaction['result']."</td>\n".'<td>'.$interaction['latency']."</td>\n".'<td>'.$interaction['time']."</td>\n"."</tr>\n";
					$counter ++;
				}
			}
		} while ($row = Database :: fetch_array($result));
	} else {
		$row = Database :: fetch_array($result);

		//check if there are interactions below
		$extend_attempt_link = '';
		$extend_this_attempt = 0;
		$inter_num = learnpath :: get_interactions_count_from_db($row['iv_id']);
		if ($inter_num > 0 && !$extend_all) {
			if (!empty ($_GET['extend_attempt_id']) && $_GET['extend_attempt_id'] == $row['iv_id']) {
				//the extend button for this attempt has been clicked
				$extend_this_attempt = 1;
				$extend_attempt_link = '<a href="lp_controller.php?action=stats&extend_id='.$my_item_id.'&fold_attempt_id='.$row['iv_id'].'"><img src="../img/visible.gif" alt="fold_attempt_view" border="0"></a>'."\n";
			} else { //same case if fold_attempt_id is set, so not implemented explicitly
				//the extend button for this attempt has not been clicked
				$extend_attempt_link = '<a href="lp_controller.php?action=stats&extend_id='.$my_item_id.'&extend_attempt_id='.$row['iv_id'].'"><img src="../img/invisible.gif" alt="extend_attempt_view" border="0"></a>'."\n";
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
			$extend_link = '<a href="lp_controller.php?action=stats&extend_id='.$my_item_id.'&extend_attempt_id='.$row['iv_id'].'"><img src="../img/invisible.gif" alt="extend_view" border="0"></a>';
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
		$title = stripslashes($title);
		if ($score == 0) {
			$maxscore = 0;
		} else {
			$maxscore = $row['mymaxscore'];
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
		$mylanglist = array ('completed' => 'ScormCompstatus', 'incomplete' => 'ScormIncomplete', 'failed' => 'ScormFailed', 'passed' => 'ScormPassed', 'browsed' => 'ScormBrowsed', 'not attempted' => 'ScormNotAttempted',);
		$my_lesson_status = htmlentities(get_lang($mylanglist[$lesson_status]), ENT_QUOTES, $dokeos_charset);

		if ($row['item_type'] != 'dokeos_chapter') {
			$output .= "<tr class='$oddclass'>\n"."<td>$extend_link</td>\n".'<td colspan="4"><div class="mystatus">'.$title.'</div></td>'."\n"
			//."<td><font color='$color'><div class='mystatus'>".htmlentities($array_status[$lesson_status],ENT_QUOTES,$charset_lang)."</div></font></td>\n"
			.'<td colspan="2"><font color="'.$color.'"><div class="mystatus">'.$my_lesson_status."</div></font></td>\n".'<td colspan="2"><div class="mystatus" align="center">'. ($score == 0 ? '-' : $score.'/'.$maxscore)."</div></td>\n".'<td colspan="2"><div class="mystatus">'.$time."</div></td>\n"."</tr>\n";
						
			if($export_csv)
			{
				$temp = array();
				$temp[] = $title;
				$temp[] = html_entity_decode($my_lesson_status);
				$temp[] = ($score == 0 ? '-' : $score.'/'.$maxscore);
				$temp[] = $time;
				$csv_content[] = $temp;
			}
		}

		$counter ++;

		if ($extend_this_attempt OR $extend_all) {
			$list = learnpath :: get_iv_interactions_array($row['iv_id']);
			foreach ($list as $id => $interaction) {
				if (($counter % 2) == 0) {
					$oddclass = "row_odd";
				} else {
					$oddclass = "row_even";
				}
				$output .= "<tr class='$oddclass'>\n".'<td></td>'."\n".'<td></td>'."\n".'<td>&nbsp;</td>'."\n".'<td>'.$interaction['order_id'].'</td>'."\n".'<td>'.$interaction['id'].'</td>'."\n"
				//."<td><font color='$color'><div class='mystatus'>".htmlentities($array_status[$lesson_status],ENT_QUOTES,$charset_lang)."</div></font></td>\n"
				.'<td colspan="2">'.$interaction['type']."</td>\n"
				//.'<td>'.$interaction['correct_responses']."</td>\n"
				.'<td>'.$interaction['student_response']."</td>\n".'<td>'.$interaction['result']."</td>\n".'<td>'.$interaction['latency']."</td>\n".'<td>'.$interaction['time']."</td>\n"."</tr>\n";
				$counter ++;
			}
		}
		
	}
	//only sum up the latest attempt each time
	$total_max_score += $maxscore;
	$total_score += $score;
	$total_time += $time_for_total;
}

		
$total_time = learnpathItem :: get_scorm_time('js', $total_time);
//$total_time = str_replace('NaN','00:00:00',$total_time);
$total_time = str_replace('NaN', '00'.$h.'00\'00"', $total_time);
if ($total_max_score == 0) {
	$total_max_score = 1;
}
$total_percent = number_format((((float) $total_score / (float) $total_max_score) * 100), 1, '.', '');
if (($counter % 2) == 0) {
	$oddclass = "row_odd";
} else {
	$oddclass = "row_even";
}

$output .= "<tr class='$oddclass'>\n"."<td></td>\n".'<td colspan="4"><div class="mystatus"><i>'.htmlentities(get_lang('AccomplishedStepsTotal'), ENT_QUOTES, $dokeos_charset)."</i></div></td>\n"
//."<td><font color='$color'><div class='mystatus'>".htmlentities($array_status[$lesson_status],ENT_QUOTES,$charset_lang)."</div></font></td>\n"
.'<td colspan="2"></td>'."\n".'<td colspan="2"><div class="mystatus" align="center">'. ($total_score == 0 ? '-' : $total_percent.'%')."</div></td>\n".'<td colspan="2"><div class="mystatus">'.$total_time.'</div></td>'."\n"."</tr>\n";

$output .= "</table></td></tr></table>";

if($export_csv)
{
	$temp = array('','','','');
	$csv_content[] = $temp;
	$temp = array(get_lang('AccomplishedStepsTotal'),'',($total_score == 0 ? '-' : $total_percent.'%'),$total_time);
	$csv_content[] = $temp;
	ob_end_clean();
	Export :: export_table_csv($csv_content, 'reporting_learning_path_details');
}

if($origin != 'tracking')
{
	$output .= "</body></html>";
}
echo $output;
?>

