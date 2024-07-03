<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';
$current_course_tool = TOOL_GRADEBOOK;

api_protect_course_script(true);
api_block_anonymous_users();
GradebookUtils::block_students();

$selectCat = isset($_GET['selectcat']) ? (int) $_GET['selectcat'] : 0;

$catadd = new Category();
$catadd->set_user_id(api_get_user_id());
$catadd->set_parent_id($selectCat);
$catcourse = Category::load($selectCat);
$form = new CatForm(
    CatForm::TYPE_SELECT_COURSE,
    $catadd,
    'add_cat_form',
    null,
    api_get_self().'?selectcat='.$selectCat.'&'.api_get_cidreq()
);

if ($form->validate()) {
    $values = $form->exportValues();
    $cat = new Category();
    $cat->set_course_code($values['select_course']);
    $cat->set_name($values['name']);
    header('Location: gradebook_add_link.php?selectcat='.$selectCat.'&course_code='.Security::remove_XSS($values['select_course']).'&'.api_get_cidreq());
    exit;
}

$interbreadcrumb[] = [
    'url' => Category::getUrl().'selectcat='.$selectCat,
    'name' => get_lang('Gradebook'),
];
Display::display_header(get_lang('NewCategory'));
$form->display();
Display::display_footer();
