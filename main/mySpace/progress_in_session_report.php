<?php
/* For licensing terms, see /license.txt */

/**
 * Generate a session report during a period.
 */
$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();

$this_section = SECTION_TRACKING;

$toolName = get_lang('ProgressInSessionReport');

$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'mySpace/index.php',
    'name' => get_lang('Reporting'),
];
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'mySpace/session.php',
    'name' => get_lang('FollowedSessions'),
];

$sessionId = isset($_GET['session_id']) ? (int) $_GET['session_id'] : 0;

$script = '<script>
var sessionId = '.$sessionId.';
$(function() {
  $(".export").click(function(e) {
    var actionFrm = $(this).attr("href");
    submitFrm(actionFrm, e);
  });

  $("#session_progress_report_submitReport").click(function(e) {
    var actionFrm = "'.api_get_self().'";
    submitFrm(actionFrm, e);
  });

  if (sessionId > 0) {
    $("#session_progress_report_submitReport").click();
  }
})

function submitFrm(actionFrm, e) {
  e.stopImmediatePropagation();
  e.preventDefault();
  $("#session_progress_report").attr("action", actionFrm);
  $("#session_progress_report").submit();
}
</script>';

$actions = null;
$actions .= Display::url(
    Display::return_icon('back.png', get_lang('Back'),
        null,
        ICON_SIZE_MEDIUM
    ),
    '../mySpace/session.php'
);

if (api_is_platform_admin()) {
    $sessionList = SessionManager::get_sessions_list();
} elseif (api_is_drh()) {
    $sessionList = SessionManager::get_sessions_followed_by_drh(api_get_user_id());
} elseif (api_is_session_admin()) {
    $sessionList = SessionManager::getSessionsFollowedByUser(api_get_user_id(), SESSIONADMIN);
} else {
    $sessionList = Tracking::get_sessions_coached_by_user(api_get_user_id());
}

$form = new FormValidator('session_progress_report');
$selectSession = $form->addSelect('session_id', get_lang('Session'), [0 => get_lang('None')]);

foreach ($sessionList as $sessionInfo) {
    $selectSession->addOption($sessionInfo['name'], $sessionInfo['id']);
}

$form->addDateRangePicker(
    'date_range',
    get_lang('DateRange'),
    false,
    ['id' => 'date_range']
);
$form->addButtonFilter(get_lang('Filter'), 'submitReport');

if (!empty($sessionId)) {
    $form->setDefaults(['session_id' => $sessionId]);
}

$users = [];
$courses = [];
$sessionName = '';
if ($form->validate()) {
    $formValues = $form->getSubmitValues();
    $sessionId = $formValues['session_id'];
    $startDate = $formValues['date_range_start'];
    $endDate = $formValues['date_range_end'];
    $accessSessionCourse = CourseManager::getAccessCourse(
        0,
        1,
        0,
        $startDate,
        $endDate,
        $sessionId
    );

    if (!empty($accessSessionCourse)) {
        $session = api_get_session_entity($sessionId);
        $sessionName = $session->getName();

        foreach ($accessSessionCourse as $access) {
            $user = api_get_user_entity($access['user_id']);

            $studentLink = Display::url(
                UserManager::formatUserFullName($user),
                api_get_path(WEB_CODE_PATH).'mySpace/myStudents.php?student='.$user->getId().'&origin=session_report'
            );

            $users[$user->getId()] = [
                'complete_name' => $studentLink,
                'time_in_platform' => api_time_to_hms(
                   Tracking::get_time_spent_on_the_course($user->getId(), $access['c_id'], $sessionId, $startDate, $endDate)
                ),
            ];
            $course = api_get_course_entity($access['c_id']);
            $courses[$course->getCode()] = $course->getCode();
        }

        if (!empty($courses)) {
            foreach ($courses as $courseCode => $name) {
                foreach ($users as $userId => $user) {
                    $progress = Tracking::get_avg_student_progress(
                        $userId,
                        $courseCode,
                        [],
                        $sessionId,
                        false,
                        false,
                        false,
                        $startDate,
                        $endDate
                    );
                    $infoGradeCertificate = UserManager::get_info_gradebook_certificate(
                        $courseCode,
                        $sessionId,
                        $userId,
                        $startDate,
                        $endDate
                    );
                    $users[$userId][$courseCode.'_progress'] = is_numeric($progress) ? "$progress %" : '0 %';
                    $users[$userId][$courseCode.'_certificate'] = $infoGradeCertificate ? get_lang('Yes') : get_lang('No');
                }
            }
        }
    }
}

if (isset($_GET['export']) && !empty($users)) {
    $fileName = 'progress_in_session_'.api_get_local_time();

    $dataToExport = [];
    $dataToExport['headers'][] = get_lang('StudentName');
    $dataToExport['headers'][] = get_lang('TimeSpentOnThePlatform');

    foreach ($courses as $courseCode) {
        $dataToExport['headers'][] = get_lang('Progress').' '.$courseCode;
        $dataToExport['headers'][] = get_lang('Certificate').' '.$courseCode;
    }

    foreach ($users as $user) {
        $dataToExport[] = $user;
    }

    switch ($_GET['export']) {
        case 'xls':
            Export::export_table_xls_html($dataToExport, $fileName);
            break;
        case 'csv':
            Export::arrayToCsv($dataToExport, $fileName);
            break;
    }
}

$view = new Template($toolName);
$view->assign('form', $form->returnForm());
$view->assign('script', $script);
if (!empty($users)) {
    $view->assign('sessionName', $sessionName);
    $view->assign('courses', $courses);
    $view->assign('users', $users);
    $actions .= Display::url(
        Display::return_icon('export_csv.png', get_lang('ExportAsCSV'), [], 32),
        api_get_self().'?export=csv',
        ['class' => 'export']
    );
    $actions .= Display::url(
        Display::return_icon('export_excel.png', get_lang('ExportAsXLS'), [], ICON_SIZE_MEDIUM),
        api_get_self().'?export=xls',
        ['class' => 'export']
    );
}
$template = $view->get_template('my_space/progress_in_session_report.tpl');
$content = $view->fetch($template);
$view->assign(
    'actions',
    Display::toolbarAction('toolbar', [$actions])
);
$view->assign('header', $toolName);
$view->assign('content', $content);
$view->display_one_col_template();
