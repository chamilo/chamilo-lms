<?php
// $Id: gradebook_add_result.php 252 2007-03-29 13:46:31Z stijn $
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
include_once ('lib/fe/displaygradebook.php');
include_once ('lib/gradebook_functions.inc.php');
include_once ('lib/fe/evalform.class.php');
include_once ('lib/scoredisplay.class.php');
api_block_anonymous_users();
block_students();

$resultedit = Result :: load (null,null,$_GET['selecteval']);
$evaluation = Evaluation :: load ($_GET['selecteval']);
$edit_result_form = new EvalForm(EvalForm :: TYPE_ALL_RESULTS_EDIT, $evaluation[0], $resultedit, 'edit_result_form', null, api_get_self() . '?&selecteval=' . $_GET['selecteval']);
if ($edit_result_form->validate()) {
	$values = $edit_result_form->exportValues();
	$scores = ($values['score']);
	foreach ($scores as $row){
		$resultedit = Result :: load (key($scores));
		if ((!empty ($row)) || ($row == '0')) $resultedit[0]->set_score($row);
		$resultedit[0]->save();
		next($scores);
	}
	header('Location: gradebook_view_result.php?selecteval='.$_GET['selecteval'].'&editallresults=');
	exit;
}
$interbreadcrumb[] = array (
	'url' => 'gradebook.php',
	'name' => get_lang('Gradebook'
));
$interbreadcrumb[]= array (
	'url' => 'gradebook_view_result.php?selecteval='.$_GET['selecteval'],
	'name' => get_lang('ViewResult'
));
Display :: display_header(get_lang('EditResult'));
DisplayGradebook :: display_header_result ($evaluation[0],null,0,0);
echo '<div class="main">';
echo $edit_result_form->toHtml();
echo '</div>';
Display :: display_footer();
?>
