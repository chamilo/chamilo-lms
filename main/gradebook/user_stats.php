<?php
// $Id: gradebook_view_result.php 479 2007-04-12 11:50:58Z stijn $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2006 Dokeos S.A.
	Copyright (c) 2006 Ghent University (UGent)
	Copyright (c) various contributors

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, 44 rue des palais, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/
$language_file= 'gradebook';
$cidReset= true;
include_once ('../inc/global.inc.php');
include_once ('lib/be.inc.php');
include_once ('lib/gradebook_functions.inc.php');
include_once ('lib/fe/userform.class.php');
include_once ('lib/user_data_generator.class.php');
include_once ('lib/fe/usertable.class.php');
include_once ('lib/fe/displaygradebook.php');
include_once ('lib/scoredisplay.class.php');
include_once (api_get_path(LIBRARY_PATH).'ezpdf/class.ezpdf.php');
api_block_anonymous_users();
block_students();
$interbreadcrumb[]= array (
	'url' => 'gradebook.php',
	'name' => get_lang('Gradebook'
));
$category= Category :: load(0);
$allevals= $category[0]->get_evaluations($_GET['userid'], true);
$alllinks= $category[0]->get_links($_GET['userid'], true);
if ($_GET['selectcat'] != null)
	$addparams= array (
		'userid' => $_GET['userid'],
		'selectcat' => $_GET['selectcat']
	);
else
	$addparams= array (
		'userid' => $_GET['userid'],
		'selecteval' => $_GET['selecteval']
	);
$user_table= new UserTable($_GET['userid'], $allevals, $alllinks, $addparams);
if (isset ($_GET['exportpdf']))
{
	$pdf= new Cezpdf();
	$pdf->selectFont(api_get_path(LIBRARY_PATH).'ezpdf/fonts/Courier.afm');
	$pdf->ezSetMargins(30, 30, 50, 30);
	$pdf->ezSetY(800);
	$datagen= new UserDataGenerator($_GET['userid'], $allevals,$alllinks);
	$data_array= $datagen->get_data(UserDataGenerator :: UDG_SORT_NAME, 0, null, true);
	$newarray= array ();
	$displayscore= Scoredisplay :: instance();
	$newitem= array ();
	foreach ($data_array as $data)
	{
		$newarray[] = array_slice($data, 1);	
	}
	$pdf->ezSetY(810);
	$userinfo = get_user_info_from_id($_GET['userid']);
	$pdf->ezText(get_lang('Results').' : '.$userinfo['lastname']. ' '. $userinfo['firstname'].' ('. date('j/n/Y g:i') .')',12,array('justification'=>'center'));
	$pdf->line(50,790,550,790);
	$pdf->line(50,40,550,40);	
	
	$pdf->ezSetY(750);
	if ($displayscore->is_custom())
		$header_names= array (
			get_lang('Evaluation'
		), get_lang('Course'), get_lang('Category'), get_lang('EvaluationAverage'),get_lang('Result'),get_lang('Display'));
	else
		$header_names= array (
			get_lang('Evaluation'
		), get_lang('Course'), get_lang('Category'), get_lang('EvaluationAverage'),get_lang('Result'));
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
if (isset ($_GET['selectcat']))
{
	$interbreadcrumb[]= array (
		'url' => 'gradebook_flatview.php?selecteval=' . $_GET['selectcat'],
		'name' => get_lang('FlatView'
	));
	$backto= '<a href=gradebook_flatview.php?selectcat=' . $_GET['selectcat'] . '><img src=../img/lp_leftarrow.gif alt=' . get_lang('BackToOverview') . ' align=absmiddle/> ' . get_lang('BackToOverview') . '</a>&nbsp;&nbsp;';

}
if (isset ($_GET['selecteval']))
{
	$interbreadcrumb[]= array (
		'url' => 'gradebook_view_result.php?selecteval=' . $_GET['selecteval'],
		'name' => get_lang('ViewResult'
	));
	$backto= '<a href=gradebook_view_result.php?selecteval=' . $_GET['selecteval'] . '><img src=../img/lp_leftarrow.gif alt=' . get_lang('BackToEvaluation') . ' align=absmiddle/> ' . get_lang('BackToEvaluation') . '</a>&nbsp;&nbsp;';
}
$backto .= '<a href="' . api_get_self() . '?exportpdf=&userid='.$_GET['userid'].'&selectcat=' . $category[0]->get_id() . '" target="_blank"><img src=../img/calendar_up.gif alt=' . get_lang('ExportPDF') . '/> ' . get_lang('ExportPDF') . '</a>';

Display :: display_header(get_lang('ResultsPerUser'));
DisplayGradebook :: display_header_user($_GET['userid']);
echo $backto;
$user_table->display();
Display :: display_footer();
?>
