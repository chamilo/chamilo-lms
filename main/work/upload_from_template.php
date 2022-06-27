<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';
$current_course_tool = TOOL_STUDENTPUBLICATION;

api_protect_course_script(true);

// Including necessary files
require_once 'work.lib.php';

$this_section = SECTION_COURSES;

$work_id = isset($_REQUEST['id']) ? (int) ($_REQUEST['id']) : null;
$documentId = isset($_REQUEST['document_id']) ? (int) ($_REQUEST['document_id']) : null;

$is_allowed_to_edit = api_is_allowed_to_edit();
$course_id = api_get_course_int_id();
$user_id = api_get_user_id();
$userInfo = api_get_user_info();
$session_id = api_get_session_id();
$course_info = api_get_course_info();
$course_code = $course_info['code'];
$group_id = api_get_group_id();
$sessionId = api_get_session_id();

if (empty($work_id)) {
    api_not_allowed(true);
}

protectWork($course_info, $work_id);

$workInfo = get_work_data_by_id($work_id);

$is_course_member = CourseManager::is_user_subscribed_in_real_or_linked_course(
    $user_id,
    $course_id,
    $session_id
);

$is_course_member = $is_course_member || api_is_platform_admin();

if ($is_course_member == false) {
    api_not_allowed(true);
}

$check = Security::check_token('post');
$token = Security::get_token();

$student_can_edit_in_session = api_is_allowed_to_session_edit(false, true);

$homework = get_work_assignment_by_id($workInfo['id']);
$validationStatus = getWorkDateValidationStatus($homework);

$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'work/work.php?'.api_get_cidreq(),
    'name' => get_lang('StudentPublications'),
];
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'work/work_list.php?'.api_get_cidreq().'&id='.$work_id,
    'name' => $workInfo['title'],
];
$interbreadcrumb[] = ['url' => '#', 'name' => get_lang('UploadFromTemplate')];

$form = new FormValidator(
    'form',
    'POST',
    api_get_self()."?".api_get_cidreq()."&id=".$work_id,
    '',
    ['enctype' => "multipart/form-data"]
);
setWorkUploadForm($form, $workInfo['allow_text_assignment']);
$form->addElement('hidden', 'document_id', $documentId);
$form->addElement('hidden', 'id', $work_id);
$form->addElement('hidden', 'sec_token', $token);

$documentTemplateData = getDocumentTemplateFromWork($work_id, $course_info, $documentId);

$defaults = [];
if (!empty($documentTemplateData)) {
    $defaults['title'] = $userInfo['complete_name'].'_'.$documentTemplateData['title'].'_'.substr(api_get_utc_datetime(), 0, 10);
    $defaults['description'] = $documentTemplateData['file_content'];
}

$form->setDefaults($defaults);

$succeed = false;
if ($form->validate()) {
    if ($student_can_edit_in_session && $check) {
        $values = $form->getSubmitValues();
        // Process work
        $error_message = processWorkForm(
            $workInfo,
            $values,
            $course_info,
            $sessionId,
            $group_id,
            $user_id,
            [],
            api_get_configuration_value('assignment_prevent_duplicate_upload')
        );
        $script = 'work_list.php';
        if ($is_allowed_to_edit) {
            $script = 'work_list_all.php';
        }

        Display::addFlash($error_message);
        header('Location: '.api_get_path(WEB_CODE_PATH).'work/'.$script.'?'.api_get_cidreq().'&id='.$work_id);
        exit;
    } else {
        // Bad token or can't add works
        Display::addFlash(Display::return_message(get_lang('ImpossibleToSaveTheDocument'), 'error'));
    }
}

$htmlHeadXtra[] = to_javascript_work();
Display::display_header(null);

if (!empty($work_id)) {
    echo $validationStatus['message'];
    if ($is_allowed_to_edit) {
        if (api_resource_is_locked_by_gradebook($work_id, LINK_STUDENTPUBLICATION)) {
            echo Display::return_message(get_lang('ResourceLockedByGradebook'), 'warning');
        } else {
            $form->display();
        }
    } elseif ($student_can_edit_in_session && $validationStatus['has_ended'] == false) {
        $form->display();
    } else {
        api_not_allowed();
    }
} else {
    api_not_allowed();
}

Display::display_footer();
