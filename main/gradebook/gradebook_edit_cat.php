<?php
/* For licensing terms, see /license.txt */

/**
 * @package chamilo.gradebook
 */
require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();
GradebookUtils::block_students();
$edit_cat = isset($_REQUEST['editcat']) ? (int) $_REQUEST['editcat'] : 0;
$enableGradeSubCategorySkills = (true === api_get_configuration_value('gradebook_enable_subcategory_skills_independant_assignement'));
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
    } else {
        if ($enableGradeSubCategorySkills) {
            $allowSkillsBySubCategory = $cat->getAllowSkillBySubCategory($cat->get_parent_id());
            if ($allowSkillsBySubCategory) {
                $cat->set_certificate_min_score($values['certif_min_score']);
            }
        }
    }

    $visible = 1;
    if (empty($values['visible'])) {
        $visible = 0;
    }
    $cat->set_visible($visible);

    if (isset($values['is_requirement'])) {
        $cat->setIsRequirement(true);
    } else {
        $cat->setIsRequirement(false);
    }

    if ($enableGradeSubCategorySkills) {
        $allowSkillsBySubCategory = isset($values['allow_skills_by_subcategory']);
        $cat->updateAllowSkillBySubCategory($allowSkillsBySubCategory);
    }

    $cat->save();
    header('Location: '.Category::getUrl().'editcat=&selectcat='.$cat->get_parent_id());
    exit;
}
$selectcat = isset($_GET['selectcat']) ? (int) $_GET['selectcat'] : 0;

$action_details = '';
$current_id = 0;
if (isset($_GET['editcat'])) {
    $action_details = 'editcat';
    $current_id = (int) $_GET['editcat'];
}

$logInfo = [
    'tool' => TOOL_GRADEBOOK,
    'tool_id' => 0,
    'tool_id_detail' => 0,
    'action' => 'edit-cat',
    'action_details' => $action_details,
];
Event::registerLog($logInfo);

$interbreadcrumb[] = [
    'url' => Category::getUrl().'selectcat='.$selectcat,
    'name' => get_lang('Gradebook'),
];
$this_section = SECTION_COURSES;
Display::display_header(get_lang('EditCategory'));
$form->display();
Display::display_footer();
