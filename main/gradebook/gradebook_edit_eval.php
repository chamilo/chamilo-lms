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
api_block_anonymous_users();
block_students();

$evaledit = Evaluation :: load($_GET['editeval']);
$form = new EvalForm(EvalForm :: TYPE_EDIT, $evaledit[0], null, 'edit_eval_form',null,api_get_self() . '?editeval=' . $_GET['editeval']);
if ($form->validate()) {
	$values = $form->exportValues();
	$eval = new Evaluation();
	$eval->set_id($values['hid_id']);
	$eval->set_name($values['name']);
	$eval->set_description($values['description']);
	$eval->set_user_id($values['hid_user_id']);
	$eval->set_course_code($values['hid_course_code']);
	$eval->set_category_id($values['hid_category_id']);
	$eval->set_weight($values['weight']);
	$eval->set_date(strtotime($values['date']));
	$eval->set_max($values['max']);
	if (empty ($values['visible'])) {
		$visible = 0;
	} else {
		$visible = 1;
	}
	$eval->set_visible($visible);
	$eval->save();
	header('Location: '.$_SESSION['gradebook_dest'].'?editeval=&selectcat=' . $eval->get_category_id());
	exit;
}
$selectcat_inter=isset($_GET['selectcat'])?Security::remove_XSS($_GET['selectcat']):'';
$interbreadcrumb[] = array (
	'url' => $_SESSION['gradebook_dest'].'?selectcat='.$selectcat_inter,
	'name' => get_lang('Gradebook'
));
Display :: display_header(get_lang('EditEvaluation'));
$form->display();
Display :: display_footer();