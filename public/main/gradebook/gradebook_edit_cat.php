<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;

require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();
GradebookUtils::block_students();
$edit_cat = isset($_REQUEST['editcat']) ? (int) $_REQUEST['editcat'] : 0;

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
        $cat->setCourseId(null);
    } else {
        $cat->setCourseId(api_get_course_int_id($values['course_code']));
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

    if (0 == $values['hid_parent_id']) {
        $cat->set_certificate_min_score($values['certif_min_score']);
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
    'name' => get_lang('Assessments'),
];
$this_section = SECTION_COURSES;

$pageTitle = get_lang('Edit this category');
Display::display_header($pageTitle);

$backUrl = Category::getUrl().'selectcat='.(int) $selectcat;

echo '<div class="w-full mx-auto px-4 sm:px-6 lg:px-8">';
echo '<div class="mb-4">';
echo Display::toolbarAction('actions', [
    Display::url(
        Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Back')),
        $backUrl,
        ['class' => 'inline-flex items-center']
    ),
]);
echo '</div>';

echo '<div class="mb-6">';
echo Display::page_header($pageTitle);
echo '</div>';

$form->display();

echo '</div>';

Display::display_footer();

