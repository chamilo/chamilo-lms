<?php // $Id:$
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
require_once ('lib/fe/catform.class.php');
api_block_anonymous_users();
block_students();
$edit_cat= isset($_GET['editcat']) ? $_GET['editcat'] : '';
$catedit = Category :: load($edit_cat);
$form = new CatForm(CatForm :: TYPE_EDIT, $catedit[0], 'edit_cat_form');
if ($form->validate()) {
	$values = $form->exportValues();
	$cat = new Category();
	$cat->set_id($values['hid_id']);
	$cat->set_name($values['name']);
	if (empty ($values['course_code'])) {
		$cat->set_course_code(null);
	}else {
		$cat->set_course_code($values['course_code']);
	}
	$cat->set_description($values['description']);
	$cat->set_user_id($values['hid_user_id']);
	$cat->set_parent_id($values['hid_parent_id']);
	$cat->set_weight($values['weight']);
	$cat->set_certificate_min_score($values['certif_min_score']);
	if (empty ($values['visible'])) {
		$visible = 0;
	} else {
		$visible = 1;
	}
	$cat->set_visible($visible);
	$cat->save();
	header('Location: '.$_SESSION['gradebook_dest'].'?editcat=&selectcat=' . $cat->get_parent_id());
	exit;
}
$selectcat = isset($_GET['selectcat']) ? Security::remove_XSS($_GET['selectcat']) : '';
$interbreadcrumb[] = array (
	'url' => $_SESSION['gradebook_dest'].'?selectcat='.$selectcat,
	'name' => get_lang('Gradebook'
));
Display :: display_header(get_lang('EditCategory'));
$form->display();
Display :: display_footer();