<?php

// $Id: gradebook_add_result.php 905 2007-05-07 14:00:29Z stijn $
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
$language_file = 'gradebook';
$cidReset = true;
include_once ('../inc/global.inc.php');
include_once ('lib/be.inc.php');
include_once ('lib/gradebook_functions.inc.php');
include_once ('lib/fe/evalform.class.php');
include_once ('lib/fe/displaygradebook.php');
include_once ('lib/scoredisplay.class.php');
api_block_anonymous_users();
block_students();

$resultadd = new Result();
$resultadd->set_evaluation_id($_GET['selecteval']);
$evaluation = Evaluation :: load($_GET['selecteval']);
$add_result_form = new EvalForm(EvalForm :: TYPE_RESULT_ADD, $evaluation[0], $resultadd, 'add_result_form', null, api_get_self() . '?selectcat=' . $_GET['selectcat'] . '&selecteval=' . $_GET['selecteval']);
if ($add_result_form->validate()) {
	$values = $add_result_form->exportValues();
	$nr_users = $values['nr_users'];
	if ($nr_users == '0') {
		header('Location: gradebook_view_result.php?addresultnostudents=&selecteval=' . $_GET['selecteval']);
		exit;
	}
	$scores = ($values['score']);
	foreach ($scores as $row) {
		$res = new Result();
		$res->set_evaluation_id($values['evaluation_id']);
		$res->set_user_id(key($scores));
		//if no scores are given, don't set the score
		if ((!empty ($row)) || ($row == '0')) $res->set_score($row);
		$res->add();
		next($scores);
	}
	header('Location: gradebook_view_result.php?addresult=&selecteval=' . $_GET['selecteval']);
	exit;
}
$interbreadcrumb[] = array (
	'url' => 'gradebook.php',
	'name' => get_lang('Gradebook'
));
Display :: display_header(get_lang('AddResult'));
DisplayGradebook :: display_header_result ($evaluation[0], null, 0,0);
echo '<div class="main">';
echo $add_result_form->toHtml();
echo '</div>';
Display :: display_footer();
?>
