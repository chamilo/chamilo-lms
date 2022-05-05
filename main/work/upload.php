<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';
$current_course_tool = TOOL_STUDENTPUBLICATION;

api_protect_course_script(true);

// Including necessary files
require_once 'work.lib.php';

$this_section = SECTION_COURSES;

$work_id = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : null;

$is_allowed_to_edit = api_is_allowed_to_edit();
$course_id = api_get_course_int_id();
$user_id = api_get_user_id();
$userInfo = api_get_user_info();
$session_id = api_get_session_id();
$course_info = api_get_course_info();
$course_code = $course_info['code'];
$group_id = api_get_group_id();

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

if ($is_course_member == false || api_is_invitee()) {
    api_not_allowed(true);
}

$check = Security::check_token('post');
$token = Security::get_token();

$student_can_edit_in_session = api_is_allowed_to_session_edit(false, true);

$onlyOnePublication = api_get_configuration_value('allow_only_one_student_publication_per_user');

if ($onlyOnePublication) {
    $count = get_work_count_by_student($user_id, $work_id);
    if ($count >= 1) {
        api_not_allowed(true);
    }
}

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
$interbreadcrumb[] = ['url' => '#', 'name' => get_lang('UploadADocument')];

$form = new FormValidator(
    'form-work',
    'POST',
    api_get_self().'?'.api_get_cidreq().'&id='.$work_id,
    '',
    ['enctype' => 'multipart/form-data']
);

setWorkUploadForm($form, $workInfo['allow_text_assignment']);

$form->addHidden('id', $work_id);
$form->addHidden('sec_token', $token);

$allowRedirect = api_get_configuration_value('allow_redirect_to_main_page_after_work_upload');
$urlToRedirect = '';
if ($allowRedirect) {
    $urlToRedirect = api_get_path(WEB_CODE_PATH).'work/work.php?'.api_get_cidreq();
}

$succeed = false;
if ($form->validate()) {
    if ($student_can_edit_in_session && $check) {
        $values = $form->getSubmitValues();
        // Process work
        $result = processWorkForm(
            $workInfo,
            $values,
            $course_info,
            $session_id,
            $group_id,
            $user_id,
            $_FILES['file'],
            api_get_configuration_value('assignment_prevent_duplicate_upload')
        );

        if ($allowRedirect) {
            header('Location: '.$urlToRedirect);
            exit;
        }

        $script = 'work_list.php';
        if ($is_allowed_to_edit) {
            $script = 'work_list_all.php';
        }
        header('Location: '.api_get_path(WEB_CODE_PATH).'work/'.$script.'?'.api_get_cidreq().'&id='.$work_id);
        exit;
    } else {
        // Bad token or can't add works
        Display::addFlash(
            Display::return_message(get_lang('ImpossibleToSaveTheDocument'), 'error')
        );
    }
}

$url = api_get_path(WEB_AJAX_PATH).'work.ajax.php?'.api_get_cidreq().'&a=upload_file&id='.$work_id;

$htmlHeadXtra[] = api_get_jquery_libraries_js(['jquery-ui', 'jquery-upload']);
$htmlHeadXtra[] = to_javascript_work();
Display::display_header(null);

// Only text
if (1 == $workInfo['allow_text_assignment']) {
    $tabs = $form->returnForm();
} else {
    $headers = [
        get_lang('Upload'),
        get_lang('Upload').' ('.get_lang('Simple').')',
    ];

    $multipleForm = new FormValidator('post');
    $multipleForm->addMultipleUpload($url, $urlToRedirect);

    $tabs = Display::tabs(
        $headers,
        [$multipleForm->returnForm(), $form->returnForm()],
        'tabs'
    );
}

if (!empty($work_id)) {
    echo $validationStatus['message'];
    if ($is_allowed_to_edit) {
        if (api_resource_is_locked_by_gradebook($work_id, LINK_STUDENTPUBLICATION)) {
            echo Display::return_message(get_lang('ResourceLockedByGradebook'), 'warning');
        } else {
            echo $tabs;
        }
    } elseif ($student_can_edit_in_session && $validationStatus['has_ended'] == false) {
        echo $tabs;
    } else {
        Display::addFlash(Display::return_message(get_lang('ActionNotAllowed'), 'error'));
    }
} else {
    Display::addFlash(Display::return_message(get_lang('ActionNotAllowed'), 'error'));
}

Display::display_footer();
