<?php

// $Id: gradebook_edit_cat.php 880 2007-05-07 09:32:52Z bert $
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
include_once ('lib/fe/catform.class.php');
api_block_anonymous_users();
block_students();

$catedit = Category :: load($_GET['editcat']);
$form = new CatForm(CatForm :: TYPE_EDIT, $catedit[0], 'edit_cat_form');
if ($form->validate()) {
	$values = $form->exportValues();
	$cat = new Category();
	$cat->set_id($values['hid_id']);
	$cat->set_name($values['name']);
	if (empty ($values['course_code']))
	{
		$cat->set_course_code(null);
	}
	else
	{	
		$cat->set_course_code($values['course_code']);
	}
	$cat->set_description($values['description']);
	$cat->set_user_id($values['hid_user_id']);
	$cat->set_parent_id($values['hid_parent_id']);
	$cat->set_weight($values['weight']);
	if (empty ($values['visible']))
		$visible = 0;
	else
		$visible = 1;
	$cat->set_visible($visible);
	$cat->save();
	header('Location: gradebook.php?editcat=&selectcat=' . $cat->get_parent_id());
	exit;
}
$interbreadcrumb[] = array (
	'url' => 'gradebook.php?selectcat='.$_GET['selectcat'],
	'name' => get_lang('Gradebook'
));
Display :: display_header(get_lang('EditCategory'));
$form->display();
Display :: display_footer();
?>
