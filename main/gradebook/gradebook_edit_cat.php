<?php
/* For licensing terms, see /license.txt */

/**
 * Script
 * @package chamilo.gradebook
 */
require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();
GradebookUtils::block_students();

$edit_cat = isset($_REQUEST['editcat']) ? intval($_REQUEST['editcat']) : '';

$catedit = Category::load($edit_cat);
$form = new CatForm(
    CatForm::TYPE_EDIT,
    $catedit[0],
    'edit_cat_form',
    'post',
    api_get_self().'?'.api_get_cidreq().'&editcat='.$edit_cat
);

if ($form->validate()) {
    $values = $form->getSubmitValues();

    $cat = new Category();

    if (!empty($values['hid_id'])) {
        $cat = $cat->load($values['hid_id']);
        if (isset($cat[0])) {
            $cat = $cat[0];
        }
    }

    $cat->set_id($values['hid_id']);
    $cat->set_name($values['name']);

    if (empty($values['course_code'])) {
        $cat->set_course_code(null);
    } else {
        $cat->set_course_code($values['course_code']);
    }

    if (isset($values['grade_model_id'])) {
        $cat->set_grade_model_id($values['grade_model_id']);
    }

    $cat->set_description($values['description']);

    if (isset($values['skills'])) {
        $cat->set_skills($values['skills']);
    }

    $cat->set_user_id($values['hid_user_id']);
    $cat->set_parent_id($values['hid_parent_id']);
    $cat->set_weight($values['weight']);

    if (isset($values['generate_certificates'])) {
        $cat->setGenerateCertificates($values['generate_certificates']);
    } else {
        $cat->setGenerateCertificates(false);
    }

    if ($values['hid_parent_id'] == 0) {
        $cat->set_certificate_min_score($values['certif_min_score']);
    }

    if (empty($values['visible'])) {
        $visible = 0;
    } else {
        $visible = 1;
    }

    $cat->set_visible($visible);

    if (isset($values['is_requirement'])) {
        $cat->setIsRequirement(true);
    } else {
        $cat->setIsRequirement(false);
    }
    $cat->save();
    header('Location: '.Security::remove_XSS($_SESSION['gradebook_dest']).'?editcat=&selectcat='.$cat->get_parent_id().'&'.api_get_cidreq());
    exit;
}
$selectcat = isset($_GET['selectcat']) ? (int) $_GET['selectcat'] : '';
$interbreadcrumb[] = array(
    'url' => Security::remove_XSS($_SESSION['gradebook_dest']).'?selectcat='.$selectcat.'&'.api_get_cidreq(),
    'name' => get_lang('Gradebook')
);
$this_section = SECTION_COURSES;
Display :: display_header(get_lang('EditCategory'));
$form->display();
Display :: display_footer();
