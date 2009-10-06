<?php
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
require_once ('../inc/global.inc.php');
require_once ('lib/be.inc.php');
require_once ('lib/gradebook_functions.inc.php');
require_once ('lib/fe/evalform.class.php');
api_block_anonymous_users();
block_students();

$select_cat=isset($_GET['selectcat']) ? Security::remove_XSS($_GET['selectcat']) : '';
$is_allowedToEdit = $is_courseAdmin;
$evaladd = new Evaluation();
$evaladd->set_user_id($_user['user_id']);
if (isset ($_GET['selectcat']) && (!empty ($_GET['selectcat']))) {
	$evaladd->set_category_id(Database::escape_string($_GET['selectcat']));
	$cat = Category :: load($_GET['selectcat']);
	$evaladd->set_course_code($cat[0]->get_course_code());
} else {
	$evaladd->set_category_id(0);
}
$form = new EvalForm(EvalForm :: TYPE_ADD, $evaladd, null, 'add_eval_form',null,api_get_self() . '?selectcat=' .$select_cat);
if ($form->validate()) {
	$values = $form->exportValues();
	$eval = new Evaluation();
	$eval->set_name($values['name']);
	$eval->set_description($values['description']);
	$eval->set_user_id($values['hid_user_id']);
	if (!empty ($values['hid_course_code'])) {
		$eval->set_course_code($values['hid_course_code']);
	}
	$eval->set_category_id($values['hid_category_id']);
	$eval->set_weight($values['weight']);
	//converts the date back to unix timestamp format
	$eval->set_date(strtotime($values['date']));
	$eval->set_max($values['max']);
	if (empty ($values['visible'])) {
		$visible = 0;
	} else {
		$visible = 1;
	}
	$eval->set_visible($visible);
	$eval->add();
	if ($eval->get_course_code() == null) {
		if ($values['adduser'] == 1) {
			header('Location: gradebook_add_user.php?selecteval=' . $eval->get_id());
			exit;
		} else {
			header('Location: '.$_SESSION['gradebook_dest'].'?selectcat=' . $eval->get_category_id());
			exit;
		}
	} else {
		$val_addresult=isset($values['addresult'])?$values['addresult']:null;
		if ($val_addresult == 1) {
			header('Location: gradebook_add_result.php?selecteval=' . $eval->get_id());
			exit;
		} else {
			header('Location: '.$_SESSION['gradebook_dest'].'?selectcat=' . $eval->get_category_id());
			exit;
		}
	}
}

$interbreadcrumb[] = array (
	'url' => $_SESSION['gradebook_dest'].'?selectcat='.$select_cat,
	'name' => get_lang('Gradebook'
));
Display :: display_header(get_lang('NewEvaluation'));
if ($evaladd->get_course_code() == null) {
	Display :: display_normal_message(get_lang('CourseIndependentEvaluation'),false);
}
$form->display();
Display :: display_footer();