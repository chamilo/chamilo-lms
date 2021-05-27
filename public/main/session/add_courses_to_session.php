<?php

/* For licensing terms, see /license.txt */

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

$sessionId = isset($_GET['id_session']) ? (int) $_GET['id_session'] : 0;
$add = isset($_GET['add']) ? 1 : 0;
$session = api_get_session_entity($sessionId);
SessionManager::protectSession($session);

// Setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;

// setting breadcrumbs
$interbreadcrumb[] = [
    'url' => 'session_list.php',
    'name' => get_lang('Session list'),
];
$interbreadcrumb[] = [
    'url' => "resume_session.php?id_session=$sessionId",
    'name' => get_lang('Session overview'),
];

$tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
$tbl_session_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_USER);
$tbl_session_rel_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
$tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);

$tool_name = get_lang('Add courses to this session');

Display::display_header($tool_name);

$form = new FormValidator(
    'add_course_to_session',
    'post',
    api_get_self().'?id_session='.$sessionId.'&add='.$add
);
$form->addHidden('id_session', $sessionId);
$form->addHidden('add', $add);

$form->addSelectAjax(
    'courses',
    get_lang('Course'),
    null,
    [
        'url' => api_get_path(WEB_AJAX_PATH).'course.ajax.php?a=search_course',
        'multiple' => 'multiple'
    ]
);

$form->addCheckBox('copy_evaluation', null, get_lang('Import gradebook from base course'));
$form->addCheckBox(
    'import_teachers_as_course_coach',
    null,
    get_lang('Import course teachers as course coach in the session')
);
$form->addCheckBox(
    'import_assignments',
    null,
    get_lang('Import assignments from base course')
);
$form->addButtonSave(get_lang('Add'));

$contentForm = $form->returnForm();
if ($form->validate()) {
    $data = $form->getSubmitValues();
    $courseList = $data['courses'];
    $copyEvaluation = isset($data['copy_evaluation']);
    $copyCourseTeachersAsCoach = isset($data['import_teachers_as_course_coach']);
    $importAssignments = isset($data['import_assignments']);

    SessionManager::add_courses_to_session(
        $sessionId,
        $courseList,
        false,
        $copyEvaluation,
        $copyCourseTeachersAsCoach,
        $importAssignments
    );

    Display::addFlash(Display::return_message(get_lang('Update successful')));

    $url = api_get_path(WEB_CODE_PATH).'session/';
    if ($add) {
        header('Location: '.$url.'add_users_to_session.php?id_session='.$sessionId.'&add=true');
    } else {
        header('Location: '.$url.'resume_session.php?id_session='.$sessionId);
    }
    exit;
}

if (!api_is_platform_admin() && api_is_teacher()) {
    $coursesFromTeacher = CourseManager::getCoursesFollowedByUser(
        api_get_user_id(),
        COURSEMANAGER
    );
}

unset($Courses);

echo Display::page_header($tool_name.' ('.$session->getName().')');
echo $contentForm;
Display::display_footer();
