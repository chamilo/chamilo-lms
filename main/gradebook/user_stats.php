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
require_once ('lib/fe/userform.class.php');
require_once ('lib/user_data_generator.class.php');
require_once ('lib/fe/usertable.class.php');
require_once ('lib/fe/displaygradebook.php');
require_once ('lib/scoredisplay.class.php');
require_once (api_get_path(LIBRARY_PATH).'ezpdf/class.ezpdf.php');
require_once (api_get_path(LIBRARY_PATH).'usermanager.lib.php');
api_block_anonymous_users();
block_students();
$interbreadcrumb[]= array (
	'url' => $_SESSION['gradebook_dest'],
	'name' => get_lang('Gradebook'
));
$category= Category :: load(0);
$my_user_id=Security::remove_XSS($_GET['userid']);
$allevals= $category[0]->get_evaluations($my_user_id, true);
$alllinks= $category[0]->get_links($my_user_id, true);
if ($_GET['selectcat'] != null) {
	$addparams= array (
		'userid' => $my_user_id,
		'selectcat' => Security::remove_XSS($_GET['selectcat'])
	);	
} else {
	$addparams= array (
		'userid' => $my_user_id,
		'selecteval' => Security::remove_XSS($_GET['selecteval'])
	);	
}

$user_table= new UserTable($my_user_id, $allevals, $alllinks, $addparams);
if (isset ($_GET['exportpdf'])) {
	$pdf= new Cezpdf();
	$pdf->selectFont(api_get_path(LIBRARY_PATH).'ezpdf/fonts/Courier.afm');
	$pdf->ezSetMargins(30, 30, 50, 30);
	$pdf->ezSetY(800);
	$datagen= new UserDataGenerator($my_user_id, $allevals,$alllinks);
	$data_array= $datagen->get_data(UserDataGenerator :: UDG_SORT_NAME, 0, null, true);
	$newarray= array ();
	$displayscore= Scoredisplay :: instance();
	$newitem= array ();
	foreach ($data_array as $data) {
		$newarray[] = array_slice($data, 1);	
	}

	$pdf->ezSetY(810);
	$userinfo = get_user_info_from_id($my_user_id);
	$pdf->ezText(get_lang('Results').' : '.$userinfo['lastname']. ' '. $userinfo['firstname'].' ('. date('j/n/Y g:i') .')',12,array('justification'=>'center'));
	$pdf->line(50,790,550,790);
	$pdf->line(50,40,550,40);	
	
	$pdf->ezSetY(750);
	if ($displayscore->is_custom()) {
		$header_names= array (
			get_lang('Evaluation'
		), get_lang('Course'), get_lang('Category'), get_lang('EvaluationAverage'),get_lang('Result'),get_lang('Display'));		
	} else {
		$header_names= array (
			get_lang('Evaluation'
		), get_lang('Course'), get_lang('Category'), get_lang('EvaluationAverage'),get_lang('Result'));		
	}
	$pdf->ezTable($newarray, $header_names, '', array (
		'showHeadings' => 1,
		'shaded' => 1,
		'showLines' => 1,
		'rowGap' => 3,
		'width' => 500
	));
	$pdf->ezStream();
	exit;
}
$actions = '<div class="actions">';

if (isset ($_GET['selectcat'])) {
	$interbreadcrumb[]= array ('url' => 'gradebook_flatview.php?selectcat=' . Security::remove_XSS($_GET['selectcat']), 'name' => get_lang('FlatView'));
	$actions.= '<a href=gradebook_flatview.php?selectcat=' .Security::remove_XSS($_GET['selectcat']) . '> &#60;&#60; ' . get_lang('BackToOverview') . '</a>&nbsp&nbsp';

}
if (isset ($_GET['selecteval'])) {
	$interbreadcrumb[]= array (
		'url' => 'gradebook_view_result.php?selecteval=' . Security::remove_XSS($_GET['selecteval']),
		'name' => get_lang('ViewResult'
	));
	$actions.= '<a href=gradebook_view_result.php?selecteval=' . Security::remove_XSS($_GET['selecteval']) . '><img src=../img/lp_leftarrow.gif alt=' . get_lang('BackToEvaluation') . ' align=absmiddle/> ' . get_lang('BackToEvaluation') . '</a>&nbsp&nbsp';
}
$actions.= '<a href="' . api_get_self() . '?exportpdf=&userid='.Security::remove_XSS($_GET['userid']).'&selectcat=' . $category[0]->get_id() . '" target="_blank"><img src=../img/calendar_up.gif alt=' . get_lang('ExportPDF') . '/> ' . get_lang('ExportPDF') . '</a>';
$actions.='</div>';

Display :: display_header(get_lang('ResultsPerUser'));
DisplayGradebook :: display_header_user(Security::remove_XSS($_GET['userid']));
echo $actions;
$user_table->display();
Display :: display_footer();