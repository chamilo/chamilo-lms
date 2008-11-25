<?php // $Id: $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2008 Dokeos Latinoamerica SAC
	Copyright (c) 2007 Dokeos SPRL
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

$catadd = new Category();
$catadd->set_user_id($_user['user_id']);
$catadd->set_parent_id($_GET['selectcat']);
$catcourse = Category :: load ($_GET['selectcat']);
//$catadd->set_course_code($catcourse[0]->get_course_code());
$form = new CatForm(CatForm :: TYPE_SELECT_COURSE, $catadd, 'add_cat_form', null, api_get_self().'?selectcat=' . Security::remove_XSS($_GET['selectcat']));

if ($form->validate()) {
	$values = $form->exportValues();
	$cat = new Category();
	$cat->set_course_code($values['select_course']);
	$cat->set_name($values['name']);
	header('location: gradebook_add_link.php?selectcat=' .Security::remove_XSS($_GET['selectcat']).'&course_code='.Security::remove_XSS($values['select_course']));
	exit;
}

$interbreadcrumb[] = array (
	'url' => $_SESSION['gradebook_dest'].'?selectcat='.Security::remove_XSS($_GET['selectcat']),
	'name' => get_lang('Gradebook'
));
Display :: display_header(get_lang('NewCategory'));
$form->display();
Display :: display_footer();