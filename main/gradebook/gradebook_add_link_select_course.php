<?php
/* For licensing terms, see /license.txt */

/**
 * Script
 * @package chamilo.gradebook
 */

require_once '../inc/global.inc.php';
$current_course_tool  = TOOL_GRADEBOOK;

api_protect_course_script(true);
api_block_anonymous_users();
GradebookUtils::block_students();

$catadd = new Category();
$catadd->set_user_id(api_get_user_id());
$catadd->set_parent_id($_GET['selectcat']);
$catcourse = Category :: load ($_GET['selectcat']);
$form = new CatForm(
    CatForm :: TYPE_SELECT_COURSE,
    $catadd,
    'add_cat_form',
    null,
    api_get_self() . '?selectcat=' . Security::remove_XSS($_GET['selectcat']).'&'.api_get_cidreq()
);

if ($form->validate()) {
    $values = $form->exportValues();
    $cat = new Category();
    $cat->set_course_code($values['select_course']);
    $cat->set_name($values['name']);
    header('location: gradebook_add_link.php?selectcat=' .Security::remove_XSS($_GET['selectcat']).'&course_code='.Security::remove_XSS($values['select_course']).'&'.api_get_cidreq());
    exit;
}

$interbreadcrumb[] = array (
    'url' => Security::remove_XSS($_SESSION['gradebook_dest']).'?selectcat='.Security::remove_XSS($_GET['selectcat']).'&'.api_get_cidreq(),
    'name' => get_lang('Gradebook')
);
Display :: display_header(get_lang('NewCategory'));
$form->display();
Display :: display_footer();
