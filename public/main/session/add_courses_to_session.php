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
    [],
    [
        'id' => 'courses',
        'url' => api_get_path(WEB_AJAX_PATH).'course.ajax.php?a=search_course',
        'multiple' => 'multiple',
        'class' => 'w-full',
    ]
);

$form->addCheckBox('copy_evaluation', '', get_lang('Import gradebook from base course'));
$form->addCheckBox(
    'import_teachers_as_course_coach',
    '',
    get_lang('Import course teachers as course coach in the session')
);
$form->addCheckBox(
    'import_assignments',
    '',
    get_lang('Import assignments from base course')
);
$form->addButtonSave(get_lang('Add'));

$contentForm = $form->returnForm();
if ($form->validate()) {
    $data = $form->getSubmitValues();
    $courseList = $data['courses'] ?? [];

    if (!empty($courseList)) {
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
    }

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

$sessionTitle = $session ? (string) $session->getTitle() : '';
$backUrl = api_get_path(WEB_CODE_PATH).'session/resume_session.php?id_session='.$sessionId;
$listUrl = api_get_path(WEB_CODE_PATH).'session/session_list.php';

echo '<div class="mx-auto w-full p-4 space-y-4">';

echo '  <div class="rounded-lg border border-gray-30 bg-white p-4 shadow-sm">';
echo '    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">';
echo '      <div class="min-w-0">';
echo '        <h1 class="text-lg font-semibold text-gray-90">'.htmlspecialchars($tool_name, ENT_QUOTES, api_get_system_encoding()).'</h1>';
echo '        <p class="text-sm text-gray-50">'.htmlspecialchars($sessionTitle, ENT_QUOTES, api_get_system_encoding()).'</p>';
echo '      </div>';
echo '      <div class="flex items-center gap-2">';
echo '        <a href="'.$backUrl.'" class="inline-flex items-center gap-2 rounded-md border border-gray-30 bg-white px-3 py-1.5 text-sm font-medium text-gray-90 shadow-sm hover:bg-gray-10">';
echo              get_lang('Back');
echo '        </a>';
echo '        <a href="'.$listUrl.'" class="inline-flex items-center gap-2 rounded-md border border-gray-30 bg-white px-3 py-1.5 text-sm font-medium text-gray-90 shadow-sm hover:bg-gray-10">';
echo              get_lang('Session list');
echo '        </a>';
echo '      </div>';
echo '    </div>';
echo '  </div>';

echo '  <div class="rounded-lg border border-gray-30 bg-gray-10 p-4">';
echo '    <p class="text-sm text-gray-70 leading-relaxed">';
echo '      <span class="font-semibold text-gray-90">'.get_lang('Tip').':</span> '.get_lang('You can select multiple items').'. ';
echo        get_lang('Use the search box to find courses quickly').'.';
echo '    </p>';
echo '  </div>';

echo '  <div class="rounded-lg border border-gray-30 bg-white p-4 shadow-sm">';
echo        $contentForm;
echo '  </div>';

echo '</div>';
Display::display_footer();
