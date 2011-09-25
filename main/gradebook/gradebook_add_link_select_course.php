<?php // $Id: $
/* For licensing terms, see /license.txt */
/**
 * Script
 * @package chamilo.gradebook
 */
/**
 * Init
 */

$language_file = 'gradebook';
//$cidReset = true;
require_once '../inc/global.inc.php';
require_once 'lib/be.inc.php';
require_once 'lib/gradebook_functions.inc.php';
require_once 'lib/fe/catform.class.php';
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
	'url' => Security::remove_XSS($_SESSION['gradebook_dest']).'?selectcat='.Security::remove_XSS($_GET['selectcat']),
	'name' => get_lang('Gradebook'
));
Display :: display_header(get_lang('NewCategory'));
$form->display();
Display :: display_footer();
