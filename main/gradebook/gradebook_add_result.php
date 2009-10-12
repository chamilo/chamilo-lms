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
$language_file = 'gradebook';
//$cidReset = true;
require_once ('../inc/global.inc.php');
require_once ('lib/be.inc.php');
require_once ('lib/gradebook_functions.inc.php');
require_once ('lib/fe/evalform.class.php');
require_once ('lib/fe/displaygradebook.php');
require_once ('lib/scoredisplay.class.php');
api_block_anonymous_users();
block_students();

$resultadd = new Result();
$resultadd->set_evaluation_id($_GET['selecteval']);
$evaluation = Evaluation :: load($_GET['selecteval']);
$add_result_form = new EvalForm(EvalForm :: TYPE_RESULT_ADD, $evaluation[0], $resultadd, 'add_result_form', null, api_get_self() . '?selectcat=' . Security::remove_XSS($_GET['selectcat']) . '&selecteval=' . Security::remove_XSS($_GET['selecteval']));
if ($add_result_form->validate()) {
	$values = $add_result_form->exportValues();
	$nr_users = $values['nr_users'];
	if ($nr_users == '0') {
		header('Location: gradebook_view_result.php?addresultnostudents=&selecteval=' . Security::remove_XSS($_GET['selecteval']));
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
	header('Location: gradebook_view_result.php?addresult=&selecteval=' . Security::remove_XSS($_GET['selecteval']));
	exit;
}
$interbreadcrumb[] = array (
	'url' => $_SESSION['gradebook_dest'],
	'name' => get_lang('Gradebook'
));
Display :: display_header(get_lang('AddResult'));
DisplayGradebook :: display_header_result ($evaluation[0], null, 0,0);
echo '<div class="main">';
echo $add_result_form->toHtml();
echo '</div>';
Display :: display_footer();