<?php // $Id: $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2008 Dokeos Latinoamerica SAC
	Copyright (c) 2006 Dokeos SPRL
	Copyright (c) 2006 Ghent University (UGent)
	Copyright (c) various contributors

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
$language_file= 'gradebook';
//$cidReset= true;
require_once ('../inc/global.inc.php');
require_once ('lib/be.inc.php');
require_once ('lib/gradebook_functions.inc.php');
require_once ('lib/fe/dataform.class.php');
require_once ('lib/scoredisplay.class.php');
require_once ('lib/fe/displaygradebook.php');
api_block_anonymous_users();
$eval= Evaluation :: load($_GET['selecteval']);
if ($eval[0]->get_category_id() < 0) {
	// if category id is negative, then the evaluation's origin is a link
	$link= LinkFactory :: get_evaluation_link($eval[0]->get_id());
	$currentcat= Category :: load($link->get_category_id());
} else {
	$currentcat= Category :: load($eval[0]->get_category_id());
}

$interbreadcrumb[]= array (
	'url' => $_SESSION['gradebook_dest'].'?selectcat=' . $currentcat[0]->get_id(), 'name' => get_lang('Gradebook'));

if (api_is_allowed_to_create_course()){
	$interbreadcrumb[]= array (
	'url' => 'gradebook_view_result.php?selecteval=' . Security::remove_XSS($_GET['selecteval']),
	'name' => get_lang('ViewResult'
	));
}
$displayscore= ScoreDisplay :: instance();

Display :: display_header(get_lang('EvaluationStatistics'));
DisplayGradebook :: display_header_result($eval[0], $currentcat[0]->get_id(), 0, 0);

if (!$displayscore->is_custom()) {
	//Display :: display_error_message(get_lang('PleaseEnableScoringSystem'),false);
} else {
	$displays= $displayscore->get_custom_score_display_settings();
	$allresults = Result :: load(null,null,$eval[0]->get_id());
	$nr_items = array();
	foreach ($displays as $itemsdisplay) {
		$nr_items[$itemsdisplay['display']] = 0;		
	}
	$resultcount = 0;
	foreach ($allresults as $result) {	
		$score = $result->get_score();
		if (isset($score)) {
			$display = $displayscore->display_score(array($score, $eval[0]->get_max()),SCORE_DIV | SCORE_IGNORE_SPLIT, SCORE_ONLY_CUSTOM);
			$nr_items[$display] ++;
			$resultcount ++;
		}
	}

	$keys = array_keys($nr_items);

	// find the region with the most scores, this is 100% of the bar

	$highest_ratio = 0;
	foreach ($keys as $key) {
		if ($nr_items[$key] > $highest_ratio){
		$highest_ratio = $nr_items[$key];		
		}
	}


	// generate table

	$stattable= '<br><table class="data_table" cellspacing="0" cellpadding="3">';
	$stattable .= '<tr><th colspan="4">' . get_lang('Statistics') . '</th></tr>';

	$counter=0;
	foreach ($keys as $key) {
		$bar = ($nr_items[$key] / $highest_ratio) * 100;
		$stattable .= '<tr class="row_' . ($counter % 2 == 0 ? 'odd' : 'even') . '">';
		$stattable .= '<td width="150">' . $key . '</td>';
		$stattable .= '<td width="550"><img src="../img/bar_1u.gif" width="' . $bar . '%" height="10"/></td>';
		$stattable .= '<td align="right">' . $nr_items[$key] . '</td>';
		$stattable .= '<td align="right"> ' . round(($nr_items[$key] / $resultcount) * 100,2) . '%</td>';
		$counter++;
	}
	$stattable .= '</tr></table>';
	echo $stattable;
}
Display :: display_footer();