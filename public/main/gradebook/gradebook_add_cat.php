<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;

require_once __DIR__.'/../inc/global.inc.php';
$_in_course = true;
$course_code = api_get_course_id();
$courseId = api_get_course_int_id();
if (empty($courseId)) {
    $_in_course = false;
}

api_block_anonymous_users();
GradebookUtils::block_students();

$edit_cat = isset($_REQUEST['editcat']) ? (int) $_REQUEST['editcat'] : '';
$get_select_cat = isset($_GET['selectcat']) ? (int) $_GET['selectcat'] : 0;

$catadd = new Category();
$my_user_id = api_get_user_id();
$catadd->set_user_id($my_user_id);
$catadd->set_parent_id($get_select_cat);
$catcourse = Category::load($get_select_cat);

if ($_in_course) {
    $catadd->setCourseId($courseId);
} else {
    $catadd->setCourseId($catcourse[0]->getCourseId());
}

// Todo: Fix this overwriting of code. Doesn't make sense given the previous block
$catadd->setCourseId(api_get_course_int_id());

$form = new CatForm(
    CatForm::TYPE_ADD,
    $catadd,
    'add_cat_form',
    null,
    api_get_self().'?selectcat='.$get_select_cat.'&'.api_get_cidreq()
);

if ($form->validate()) {
    $values = $form->exportValues();
    $select_course = isset($values['select_course']) ? $values['select_course'] : [];
    $cat = new Category();
    if ('0' == $values['hid_parent_id']) {
        if ('COURSEINDEPENDENT' == $select_course) {
            $cat->set_name($values['name']);
            $cat->setCourseId(null);
        } else {
            $cat->setCourseId(api_get_course_int_id($select_course));
            $cat->set_name($values['name']);
        }
    } else {
        $cat->set_name($values['name']);
        $cat->setCourseId(api_get_course_int_id($values['course_code']));
    }

    $cat->set_session_id(api_get_session_id());

    // Todo: Fix this reassignment that ignores the block above
    // Always add the gradebook to the course
    $cat->setCourseId(api_get_course_int_id());
    if (isset($values['skills'])) {
        $cat->set_skills($values['skills']);
    }

    $cat->set_description($values['description']);
    $cat->set_user_id($values['hid_user_id']);
    $cat->set_parent_id($values['hid_parent_id']);
    $cat->set_weight($values['weight']);

    if (isset($values['generate_certificates'])) {
        $cat->setGenerateCertificates(true);
    } else {
        $cat->setGenerateCertificates(false);
    }

    if (isset($values['is_requirement'])) {
        $cat->setIsRequirement(true);
    } else {
        $cat->setIsRequirement(false);
    }

    if (empty($values['visible'])) {
        $visible = 0;
    } else {
        $visible = 1;
    }
    $cat->set_visible($visible);
    $result = $cat->add();

    $logInfo = [
        'tool' => TOOL_GRADEBOOK,
        'tool_id' => 0,
        'tool_id_detail' => 0,
        'action' => 'new-cat',
        'action_details' => 'parent_id='.$cat->get_parent_id(),
    ];
    Event::registerLog($logInfo);

    header('Location: '.Category::getUrl().'addcat=&selectcat='.$cat->get_parent_id());
    exit;
}

$logInfo = [
    'tool' => TOOL_GRADEBOOK,
    'action' => 'add-cat',
    'action_details' => Category::getUrl().'selectcat='.$get_select_cat,
];
Event::registerLog($logInfo);

if (!$_in_course) {
    $interbreadcrumb[] = [
        'url' => Category::getUrl().'selectcat='.$get_select_cat,
        'name' => get_lang('Assessments'),
    ];
}
$interbreadcrumb[] = ['url' => 'index.php?'.api_get_cidreq(), 'name' => get_lang('Assessments')];
Display::display_header(get_lang('New category'));
$defaultBackUrl = Category::getUrl().'selectcat='.$get_select_cat;
if ($_in_course) {
    $defaultBackUrl = 'index.php?selectcat='.$get_select_cat.'&'.api_get_cidreq();
}

$backUrl = $defaultBackUrl;

$referer = $_SERVER['HTTP_REFERER'] ?? '';
if (!empty($referer)) {
    $platformHost = (string) parse_url(api_get_path(WEB_PATH), PHP_URL_HOST);
    $refererHost = (string) parse_url($referer, PHP_URL_HOST);
    if (empty($refererHost)) {
        $backUrl = $referer;
    } elseif (!empty($platformHost) && $refererHost === $platformHost) {
        $backUrl = $referer;
    }
}

echo '<div class="mb-4">';

// Back icon only
echo '<div class="mb-2">';
echo Display::url(
    Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Back')),
    $backUrl
);
echo '</div>';

// Visible page title
echo '<h1 class="text-xl font-semibold text-gray-90 m-0">'.get_lang('New category').'</h1>';

echo '</div>';

$display_form = true;
if ($display_form) {
    $form->display();
}

Display::display_footer();
