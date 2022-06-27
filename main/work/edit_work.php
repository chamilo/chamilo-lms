<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';

api_protect_course_script(true);

$lib_path = api_get_path(LIBRARY_PATH);

/* Libraries */
require_once 'work.lib.php';

// Section (for the tabs)
$this_section = SECTION_COURSES;

if (!api_is_allowed_to_edit()) {
    api_not_allowed(true);
}

$blockEdition = api_get_configuration_value('block_student_publication_edition');

if ($blockEdition && !api_is_platform_admin()) {
    api_not_allowed(true);
}

$courseInfo = api_get_course_info();
$sessionId = api_get_session_id();
$groupId = api_get_group_id();
$workId = isset($_GET['id']) ? (int) ($_GET['id']) : null;
$workData = get_work_data_by_id($workId);
$homework = get_work_assignment_by_id($workId);
$locked = api_resource_is_locked_by_gradebook($workId, LINK_STUDENTPUBLICATION);

if (false == api_is_platform_admin() && true == $locked) {
    api_not_allowed(true);
}

$htmlHeadXtra[] = to_javascript_work();
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'work/work.php?'.api_get_cidreq(),
    'name' => get_lang('StudentPublications'),
];
$interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Edit')];

$form = new FormValidator(
    'edit_dir',
    'post',
    api_get_path(WEB_CODE_PATH).'work/edit_work.php?id='.$workId.'&'.api_get_cidreq()
);
$form->addElement('header', get_lang('Edit'));

$title = !empty($workData['title']) ? $workData['title'] : basename($workData['url']);

$defaults = $workData;
$defaults['new_dir'] = Security::remove_XSS($title);

$there_is_a_end_date = false;

if (Gradebook::is_active()) {
    $link_info = GradebookUtils::isResourceInCourseGradebook(
        api_get_course_id(),
        LINK_STUDENTPUBLICATION,
        $workId
    );
    if (!empty($link_info)) {
        $defaults['weight'] = $link_info['weight'];
        $defaults['category_id'] = $link_info['category_id'];
        $defaults['make_calification'] = 1;
    }
} else {
    $defaults['category_id'] = '';
}
if (!empty($homework['expires_on'])) {
    $homework['expires_on'] = api_get_local_time($homework['expires_on']);
    $defaults['enableExpiryDate'] = true;
    $defaults['expires_on'] = $homework['expires_on'];
} else {
    $homework['expires_on'] = null;
}

if (!empty($homework['ends_on'])) {
    $homework['ends_on'] = api_get_local_time($homework['ends_on']);
    $defaults['ends_on'] = $homework['ends_on'];
    $defaults['enableEndDate'] = true;
} else {
    $homework['ends_on'] = null;
    $defaults['enableEndDate'] = false;
    $defaults['ends_on'] = null;
}

$defaults['add_to_calendar'] = isset($homework['add_to_calendar']) ? $homework['add_to_calendar'] : null;
$form = getFormWork($form, $defaults, $workId);
$form->addElement('hidden', 'work_id', $workId);
$form->addButtonUpdate(get_lang('ModifyDirectory'));

$currentUrl = api_get_path(WEB_CODE_PATH).'work/edit_work.php?id='.$workId.'&'.api_get_cidreq();
if ($form->validate()) {
    $params = $form->getSubmitValues();
    $params['enableEndDate'] = isset($params['enableEndDate']) ? true : false;
    $params['enableExpiryDate'] = isset($params['enableExpiryDate']) ? true : false;

    if ($params['enableExpiryDate'] &&
        $params['enableEndDate']
    ) {
        if ($params['expires_on'] > $params['ends_on']) {
            Display::addFlash(
                Display::return_message(
                    get_lang('DateExpiredNotBeLessDeadLine'),
                    'warning'
                )
            );
            header('Location: '.$currentUrl);
            exit;
        }
    }

    $workId = $params['work_id'];
    $editCheck = false;
    $workData = get_work_data_by_id($workId);

    if (!empty($workData)) {
        $editCheck = true;
    } else {
        $editCheck = true;
    }

    if ($editCheck) {
        updateWork($workData['iid'], $params, $courseInfo, $sessionId);
        updatePublicationAssignment($workId, $params, $courseInfo, $groupId);
        updateDirName($workData, $params['new_dir']);
        Skill::saveSkills($form, ITEM_TYPE_STUDENT_PUBLICATION, $workData['iid']);
        Display::addFlash(Display::return_message(get_lang('Updated'), 'success'));
        header('Location: '.$currentUrl);
        exit;
    } else {
        Display::addFlash(Display::return_message(get_lang('FileExists'), 'warning'));
    }
}

Display::display_header();

$form->display();

Display::display_footer();
