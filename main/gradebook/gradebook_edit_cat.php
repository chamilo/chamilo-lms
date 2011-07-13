<?php
/* For licensing terms, see /license.txt */
$language_file = 'gradebook';

require_once '../inc/global.inc.php';
require_once 'lib/be.inc.php';
require_once 'lib/gradebook_functions.inc.php';
require_once 'lib/fe/catform.class.php';

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
	header('Location: '.Security::remove_XSS($_SESSION['gradebook_dest']).'?editcat=&selectcat=' . $cat->get_parent_id());
	exit;
}
$selectcat = isset($_GET['selectcat']) ? Security::remove_XSS($_GET['selectcat']) : '';
$interbreadcrumb[] = array ('url' => Security::remove_XSS($_SESSION['gradebook_dest']).'?selectcat='.$selectcat,'name' => get_lang('Gradebook'));
$this_section = SECTION_COURSES;
Display :: display_header(get_lang('EditCategory'));
echo '<div class="actions-message">'.get_lang('EditCategory').'</div>';
$form->display();
Display :: display_footer();