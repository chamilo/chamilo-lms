<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ObjectIcon;

// Resetting the course id
$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

// Setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;

// Setting the name of the tool
$tool_name = get_lang('Enroll trainers from existing sessions');

$form_sent = 0;
$errorMsg = '';
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$session = api_get_session_entity($id);
SessionManager::protectSession($session);

// Breadcrumbs (keep consistent with the session subscription flow)
$interbreadcrumb[] = [
    'url' => 'session_list.php',
    'name' => get_lang('Session list'),
];
$interbreadcrumb[] = [
    'url' => 'resume_session.php?id_session='.$id,
    'name' => get_lang('Session overview'),
];
$interbreadcrumb[] = [
    'url' => api_get_self().'?id='.$id,
    'name' => $tool_name,
];

// Process form
$htmlResult = '';
if (isset($_POST['form_sent']) && $_POST['form_sent']) {
    $form_sent = (int) $_POST['form_sent'];

    if (1 === $form_sent && isset($_POST['sessions'], $_POST['courses'])) {
        $sessions = $_POST['sessions'];
        $courses = $_POST['courses'];

        // Copy trainers from selected sessions to selected course(s)
        $htmlResult = SessionManager::copyCoachesFromSessionToCourse($sessions, $courses);
    }
}

// Build options for selects (keep original data sources)
$session_list = SessionManager::get_sessions_list([], ['name']);
$sessionList = [];
foreach ($session_list as $row) {
    $sessionList[(int) $row['id']] = $row['title'];
}

$courseList = CourseManager::get_courses_list(0, 0, 'title');
$courseOptions = [];
foreach ($courseList as $course) {
    $courseOptions[(int) $course['id']] = $course['title'];
}
Display::display_header($tool_name);

// Tabs URLs
$urlUsers = api_get_path(WEB_CODE_PATH).'session/add_users_to_session.php?id_session='.$id.'&add=true';
$urlByClasses = api_get_path(WEB_CODE_PATH).'admin/usergroups.php?from_session='.$id.'&return_to='.rawurlencode($urlUsers);
$urlFromTeachers = api_get_path(WEB_CODE_PATH).'session/add_teachers_to_session.php?id='.$id;
$urlFromStudents = api_get_path(WEB_CODE_PATH).'session/add_students_to_session.php?id='.$id;

$sessionTitle = $session && method_exists($session, 'getTitle') ? (string) $session->getTitle() : '';
$backUrl = api_get_path(WEB_CODE_PATH).'session/resume_session.php?id_session='.$id;

// Page wrapper
echo '<div class="mx-auto w-full p-4 space-y-4">';

// Header card
echo '  <div class="rounded-lg border border-gray-30 bg-white p-4 shadow-sm">';
echo '    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">';
echo '      <div class="min-w-0">';
echo '        <h1 class="text-lg font-semibold text-gray-90">'.htmlspecialchars($tool_name, ENT_QUOTES, api_get_system_encoding()).'</h1>';
echo '        <p class="text-sm text-gray-50">'.htmlspecialchars($sessionTitle, ENT_QUOTES, api_get_system_encoding()).'</p>';
echo '      </div>';
echo '      <div class="flex items-center gap-2">';
echo '        <a href="'.$backUrl.'" class="inline-flex items-center gap-2 rounded-md border border-gray-30 bg-white px-3 py-1.5 text-sm font-medium text-gray-90 shadow-sm hover:bg-gray-10">';
echo            get_lang('Back');
echo '        </a>';
echo '      </div>';
echo '    </div>';
echo '  </div>';

// Tabs (options)
echo '  <div class="rounded-lg border border-gray-30 bg-white shadow-sm">';
echo '    <div class="flex flex-wrap items-center gap-2 border-b border-gray-20 px-3 py-2">';

echo '      <a href="'.$urlUsers.'" class="inline-flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-gray-90 hover:bg-gray-10">';
echo            Display::getMdiIcon(ObjectIcon::USER, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Users'));
echo '        <span>'.get_lang('Users').'</span>';
echo '      </a>';

echo '      <a href="'.$urlByClasses.'" class="inline-flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-gray-90 hover:bg-gray-10">';
echo            Display::getMdiIcon(ObjectIcon::MULTI_ELEMENT, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Enrolment by classes'));
echo '        <span>'.get_lang('Enrolment by classes').'</span>';
echo '      </a>';

// Active tab: teachers
echo '      <a href="'.$urlFromTeachers.'" class="inline-flex items-center gap-2 rounded-md px-3 py-2 text-sm font-semibold bg-gray-10 text-gray-90">';
echo            Display::getMdiIcon(ObjectIcon::TEACHER, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Enroll trainers from existing sessions'));
echo '        <span>'.get_lang('Enroll trainers from existing sessions').'</span>';
echo '      </a>';

echo '      <a href="'.$urlFromStudents.'" class="inline-flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-gray-90 hover:bg-gray-10">';
echo            Display::getMdiIcon(ObjectIcon::USER, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Enroll students from existing sessions'));
echo '        <span>'.get_lang('Enroll students from existing sessions').'</span>';
echo '      </a>';

echo '    </div>';

// Content
echo '    <div class="p-4 space-y-4">';

if (!empty($htmlResult)) {
    // Keep original output from SessionManager (may include HTML)
    echo '      <div class="rounded-md border border-gray-20 bg-white p-3">';
    echo            $htmlResult;
    echo '      </div>';
}

echo '      <div class="rounded-md border border-gray-20 bg-gray-10 p-3 text-sm text-gray-70">';
echo '        <span class="font-semibold text-gray-90">'.get_lang('Tip').':</span> ';
echo          get_lang('Select one or more sessions to copy trainers from and choose the destination course').'.';
echo '      </div>';

echo '      <form name="formulaire" method="post" action="'.api_get_self().'?id='.$id.'" class="space-y-4">';
echo            Display::input('hidden', 'form_sent', '1');

echo '        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">';
echo '          <div class="space-y-2">';
echo '            <label for="sessions" class="block text-sm font-medium text-gray-90">'.get_lang('Course sessions').'</label>';
echo              Display::select(
    'sessions[]',
    $sessionList,
    '',
    [
        'id' => 'sessions',
        'multiple' => 'multiple',
        'size' => '14',
        'class' => 'w-full rounded-md border border-gray-30 bg-white p-2 text-sm text-gray-90 focus:outline-none focus:ring-1 focus:ring-primary',
    ],
    false
);
echo '            <p class="text-xs text-gray-60">'.get_lang('Hold Ctrl (or Cmd) to select multiple items').'.</p>';
echo '          </div>';

echo '          <div class="space-y-2">';
echo '            <label for="courses" class="block text-sm font-medium text-gray-90">'.get_lang('Courses').'</label>';
echo              Display::select(
    'courses[]',
    $courseOptions,
    '',
    [
        'id' => 'courses',
        'size' => '14',
        'class' => 'w-full rounded-md border border-gray-30 bg-white p-2 text-sm text-gray-90 focus:outline-none focus:ring-1 focus:ring-primary',
    ],
    false
);
echo '            <p class="text-xs text-gray-60">'.get_lang('Choose the destination course').'.</p>';
echo '          </div>';
echo '        </div>';

echo '        <div class="pt-2">';
echo '          <button type="submit" class="inline-flex items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-semibold text-white shadow-sm hover:opacity-90">';
echo              get_lang('Subscribe teachers to session(s)');
echo '          </button>';
echo '        </div>';

echo '      </form>';

echo '    </div>';
echo '  </div>';

echo '</div>';
Display::display_footer();
