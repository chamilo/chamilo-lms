<?php

/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Entity\CLpCategory;
use ChamiloSession as Session;

if (!isset($_GET['course'])) {
    $cidReset = true;
}

require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();

$htmlHeadXtra[] = '<script type="text/javascript" src="'.api_get_path(WEB_PUBLIC_PATH).'assets/jquery.easy-pie-chart/dist/jquery.easypiechart.js"></script>';

$export = isset($_GET['export']) ? $_GET['export'] : false;
$sessionId = isset($_GET['id_session']) ? (int) $_GET['id_session'] : 0;
$origin = api_get_origin();
$course_code = isset($_GET['course']) ? Security::remove_XSS($_GET['course']) : '';
$courseInfo = api_get_course_info($course_code);
$courseCode = '';
if ($courseInfo) {
    $courseCode = $courseInfo['code'];
}
$student_id = isset($_GET['student_id']) ? (int) $_GET['student_id'] : 0;
$coachId = isset($_GET['id_coach']) ? (int) $_GET['id_coach'] : 0;
$details = isset($_GET['details']) ? Security::remove_XSS($_GET['details']) : '';
$currentUrl = api_get_self().'?student='.$student_id.'&course='.$courseCode.'&id_session='.$sessionId
    .'&origin='.$origin.'&details='.$details;
$allowMessages = api_get_configuration_value('private_messages_about_user');




 // Varible for all attendance list


$startDate = new DateTime();
$startDate = $startDate->modify('-1 week');
if(isset($_GET['startDate'])){
    $startDate = new DateTime($_GET['startDate']);

}
$startDate = $startDate->setTime(0,0,0);

$endDate =  new DateTime();

if(isset($_GET['endDate'])){
    $endDate = new DateTime($_GET['endDate']);

}
$endDate = $endDate->setTime(23,59,0);
// $startDate = new DateTime(api_get_local_time($startDate));
// $endDate = new DateTime(api_get_local_time($endDate));
if($startDate > $endDate){
    $a = $startDate;
    $startDate = $endDate;
    $endDate = $a;
}

// Varible for all attendance list



if (empty($student_id)) {
    api_not_allowed(true);
}

// user info
$user_info = api_get_user_info($student_id);

if (empty($user_info)) {
    api_not_allowed(true);
}

$allowToQualify = api_is_allowed_to_edit(null, true) ||
    api_is_course_tutor() ||
    api_is_session_admin() ||
    api_is_drh() ||
    api_is_student_boss();

$allowedToTrackUser = true;
if (!api_is_session_admin() &&
    !api_is_drh() &&
    !api_is_student_boss() &&
    !api_is_platform_admin()
) {
    if (empty($sessionId)) {
        $isTeacher = false;
        // Check if is current teacher if set
        if (!empty($courseInfo)) {
            $isTeacher = CourseManager::is_course_teacher(
                api_get_user_id(),
                $courseInfo['code']
            );
        }

        if (!api_is_course_admin() && $isTeacher == false) {
            if (!empty($courseInfo)) {
                // Check if the user is tutor of the course
                $userCourseStatus = CourseManager::get_tutor_in_course_status(
                    api_get_user_id(),
                    $courseInfo['real_id']
                );

                if ($userCourseStatus != 1) {
                    $allowedToTrackUser = false;
                }
            }
        }
    } else {
        $coach = api_is_coach($sessionId, $courseInfo['real_id']);

        if (!$coach) {
            $allowedToTrackUser = false;
        }
    }
}

if (!$allowedToTrackUser) {
    api_not_allowed(true);
}

if (api_is_student()) {
    api_not_allowed(true);
}

if ($export) {
    ob_start();
}
$csv_content = [];
$from_myspace = false;
$this_section = SECTION_COURSES;
if (isset($_GET['from']) && $_GET['from'] === 'myspace') {
    $from_myspace = true;
    $this_section = SECTION_TRACKING;
}

$nameTools = get_lang('StudentDetails');

if (!empty($details)) {
    if ($origin === 'user_course') {
        if (empty($cidReq)) {
            $interbreadcrumb[] = [
                'url' => api_get_path(WEB_COURSE_PATH).$courseInfo['directory'],
                'name' => $courseInfo['title'],
            ];
        }
        $interbreadcrumb[] = [
            'url' => '../user/user.php?cidReq='.$courseCode,
            'name' => get_lang('Users'),
        ];
    } else {
        if ('tracking_course' === $origin) {
            $interbreadcrumb[] = [
                'url' => '../tracking/courseLog.php?cidReq='.$courseCode.'&id_session='.api_get_session_id(),
                'name' => get_lang('Tracking'),
            ];
        } else {
            if ('resume_session' === $origin) {
                $interbreadcrumb[] = [
                    'url' => "../session/session_list.php",
                    'name' => get_lang('SessionList'),
                ];
                $interbreadcrumb[] = [
                    'url' => '../session/resume_session.php?id_session='.$sessionId,
                    'name' => get_lang('SessionOverview'),
                ];
            } else {
                $interbreadcrumb[] = [
                    'url' => api_is_student_boss() ? '#' : 'index.php',
                    'name' => get_lang('MySpace'),
                ];
                if (!empty($coachId)) {
                    $interbreadcrumb[] = [
                        'url' => 'student.php?id_coach='.$coachId,
                        'name' => get_lang('CoachStudents'),
                    ];
                    $interbreadcrumb[] = [
                        'url' => 'myStudents.php?student='.$student_id.'&id_coach='.$coachId,
                        'name' => get_lang('StudentDetails'),
                    ];
                } else {
                    $interbreadcrumb[] = [
                        'url' => 'student.php',
                        'name' => get_lang('MyStudents'),
                    ];
                    $interbreadcrumb[] = [
                        'url' => 'myStudents.php?student='.$student_id,
                        'name' => get_lang('StudentDetails'),
                    ];
                }
            }
        }
    }
    $nameTools = get_lang('DetailsStudentInCourse');
} else {
    if ($origin === 'resume_session') {
        $interbreadcrumb[] = [
            'url' => '../session/session_list.php',
            'name' => get_lang('SessionList'),
        ];
        if (!empty($sessionId)) {
            $interbreadcrumb[] = [
                'url' => '../session/resume_session.php?id_session='.$sessionId,
                'name' => get_lang('SessionOverview'),
            ];
        }
    } elseif ($origin === 'teacher_details') {
        $this_section = SECTION_TRACKING;
        $interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('MySpace')];
        $interbreadcrumb[] = ['url' => 'teachers.php', 'name' => get_lang('Teachers')];
        $nameTools = $user_info['complete_name'];
    } else {
        $interbreadcrumb[] = [
            'url' => api_is_student_boss() ? '#' : 'index.php',
            'name' => get_lang('MySpace'),
        ];
        if (!empty($coachId)) {
            if ($sessionId) {
                $interbreadcrumb[] = [
                    'url' => 'student.php?id_coach='.$coachId.'&id_session='.$sessionId,
                    'name' => get_lang('CoachStudents'),
                ];
            } else {
                $interbreadcrumb[] = [
                    'url' => 'student.php?id_coach='.$coachId,
                    'name' => get_lang('CoachStudents'),
                ];
            }
        } else {
            $interbreadcrumb[] = [
                'url' => 'student.php',
                'name' => get_lang('MyStudents'),
            ];
        }
    }
}

// Database Table Definitions
$tbl_course_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
$tbl_stats_exercices = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'export_to_pdf':
        $sessionToExport = $sId = isset($_GET['session_to_export']) ? (int) $_GET['session_to_export'] : 0;
        $sessionInfo = api_get_session_info($sessionToExport);
        if (empty($sessionInfo)) {
            api_not_allowed(true);
        }
        $courses = Tracking::get_courses_list_from_session($sessionToExport);
        $timeSpent = 0;
        $numberVisits = 0;
        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
        $progress = 0;
        foreach ($courses as $course) {
            $courseId = $course['c_id'];
            $timeSpent += Tracking::get_time_spent_on_the_course($student_id, $courseId, $sessionToExport);

            $sql = 'SELECT DISTINCT count(course_access_id) as count
                    FROM '.$table.'
                    WHERE
                        user_id = '.$student_id.' AND
                        c_id = '.$courseId.' AND
                        session_id = '.$sessionToExport.'
                    ORDER BY login_course_date ASC';
            $result = Database::query($sql);
            $row = Database::fetch_array($result);
            $numberVisits += $row['count'];
            $progress += Tracking::get_avg_student_progress($student_id, $course['code'], [], $sessionToExport);
        }

        $average = round($progress / count($courses), 1);
        $average = empty($average) ? '0%' : $average.'%';

        $first = Tracking::get_first_connection_date($student_id);
        $last = Tracking::get_last_connection_date($student_id);

        $table = new HTML_Table(['class' => 'data_table']);
        $column = 0;
        $row = 0;
        $headers = [
            get_lang('TimeSpent'),
            get_lang('NumberOfVisits'),
            get_lang('GlobalProgress'),
            get_lang('FirstLogin'),
            get_lang('LastConnexionDate'),
        ];

        foreach ($headers as $header) {
            $table->setHeaderContents($row, $column, $header);
            $column++;
        }
        $table->setCellContents(1, 0, api_time_to_hms($timeSpent));
        $table->setCellContents(1, 1, $numberVisits);
        $table->setCellContents(1, 2, $average);
        $table->setCellContents(1, 3, $first);
        $table->setCellContents(1, 4, $last);

        $courseTable = '';

        if (!empty($courses)) {
            $courseTable .= '<table class="data_table">';
            $courseTable .= '<thead>';
            $courseTable .= '<tr>
                    <th>'.get_lang('FormationUnit').'</th>
                    <th>'.get_lang('ConnectionTime').'</th>
                    <th>'.get_lang('Progress').'</th>
                    <th>'.get_lang('Score').'</th>
                </tr>';
            $courseTable .= '</thead>';
            $courseTable .= '<tbody>';

            $totalCourseTime = 0;
            $totalAttendance = [0, 0];
            $totalScore = 0;
            $totalProgress = 0;
            $gradeBookTotal = [0, 0];
            $totalEvaluations = '0/0 (0%)';
            $totalCourses = count($courses);
            $scoreDisplay = ScoreDisplay::instance();

            foreach ($courses as $course) {
                $courseId = $course['c_id'];
                $courseInfoItem = api_get_course_info_by_id($courseId);
                $courseId = $courseInfoItem['real_id'];
                $courseCodeItem = $courseInfoItem['code'];

                $isSubscribed = CourseManager::is_user_subscribed_in_course(
                    $student_id,
                    $courseCodeItem,
                    true,
                    $sId
                );

                if ($isSubscribed) {
                    $timeInSeconds = Tracking::get_time_spent_on_the_course(
                        $user_info['user_id'],
                        $courseId,
                        $sessionToExport
                    );
                    $totalCourseTime += $timeInSeconds;
                    $time_spent_on_course = api_time_to_hms($timeInSeconds);

                    $progress = Tracking::get_avg_student_progress(
                        $user_info['user_id'],
                        $courseCodeItem,
                        [],
                        $sId
                    );

                    $totalProgress += $progress;

                    $bestScore = Tracking::get_avg_student_score(
                        $user_info['user_id'],
                        $courseCodeItem,
                        [],
                        $sId,
                        false,
                        false,
                        true
                    );

                    if (is_numeric($bestScore)) {
                        $totalScore += $bestScore;
                    }

                    $progress = empty($progress) ? '0%' : $progress.'%';
                    $score = empty($bestScore) ? '0%' : $bestScore.'%';

                    $courseTable .= '<tr>
                        <td ><a href="'.$courseInfoItem['course_public_url'].'?id_session='.$sId.'">'.
                            $courseInfoItem['title'].'</a></td>
                        <td >'.$time_spent_on_course.'</td>
                        <td >'.$progress.'</td>
                        <td >'.$score.'</td>';
                    $courseTable .= '</tr>';
                }
            }

            $totalAttendanceFormatted = $scoreDisplay->display_score($totalAttendance);
            $totalScoreFormatted = $scoreDisplay->display_score([$totalScore / $totalCourses, 100], SCORE_AVERAGE);
            $totalProgressFormatted = $scoreDisplay->display_score(
                [$totalProgress / $totalCourses, 100],
                SCORE_AVERAGE
            );
            $totalEvaluations = $scoreDisplay->display_score($gradeBookTotal);
            $totalTimeFormatted = api_time_to_hms($totalCourseTime);
            $courseTable .= '
            <tr>
                <th>'.get_lang('Total').'</th>
                <th>'.$totalTimeFormatted.'</th>
                <th>'.$totalProgressFormatted.'</th>
                <th>'.$totalScoreFormatted.'</th>
            </tr>';
            $courseTable .= '</tbody></table>';
        }

        $studentInfo = api_get_user_info($student_id);

        $tpl = new Template('', false, false, false, true, false, false);
        $tpl->assign('title', get_lang('AttestationOfAttendance'));
        $tpl->assign('session_title', $sessionInfo['name']);
        $tpl->assign('student', $studentInfo['complete_name']);
        $tpl->assign('table_progress', $table->toHtml());
        $tpl->assign('subtitle', sprintf(
            get_lang('InSessionXYouHadTheFollowingResults'),
            $sessionInfo['name']
        ));
        $tpl->assign('table_course', $courseTable);
        $content = $tpl->fetch($tpl->get_template('my_space/pdf_export_student.tpl'));

        $params = [
            'pdf_title' => get_lang('Resume'),
            'session_info' => $sessionInfo,
            'course_info' => '',
            'pdf_date' => '',
            'student_info' => $studentInfo,
            'show_grade_generated_date' => true,
            'show_real_course_teachers' => false,
            'show_teacher_as_myself' => false,
            'orientation' => 'P',
        ];
        $pdf = new PDF('A4', $params['orientation'], $params);

        try {
            $pdf->setBackground($tpl->theme);
            @$pdf->content_to_pdf(
                $content,
                '',
                '',
                null,
                'D',
                false,
                null,
                false,
                true,
                false
            );
        } catch (MpdfException $e) {
            error_log($e);
        }
        exit;
        break;
    case 'export_one_session_row':
        $sessionToExport = isset($_GET['session_to_export']) ? (int) $_GET['session_to_export'] : 0;
        $exportList = Session::read('export_course_list');

        if (isset($exportList[$sessionToExport])) {
            $dataToExport = $exportList[$sessionToExport];
            $title = '';
            if (!empty($sessionToExport)) {
                $sessionInfo = api_get_session_info($sessionToExport);
                $title .= '_'.$sessionInfo['name'];
            }

            $fileName = 'report'.$title.'_'.$user_info['complete_name'];
            switch ($export) {
                case 'csv':
                    Export::arrayToCsv($dataToExport, $fileName);
                    break;
                case 'xls':
                    Export::arrayToXls($dataToExport, $fileName);
                    break;
            }
        } else {
            api_not_allowed(true);
        }
        break;
    case 'send_message':
        if ($allowMessages === true) {
            $subject = isset($_POST['subject']) ? $_POST['subject'] : '';
            $message = isset($_POST['message']) ? $_POST['message'] : '';
            $currentUserInfo = api_get_user_info();
            MessageManager::sendMessageAboutUser(
                $user_info,
                $currentUserInfo,
                $subject,
                $message
            );

            // Send also message to all student bosses
            $bossList = UserManager::getStudentBossList($student_id);

            if (!empty($bossList)) {
                $url = api_get_path(WEB_CODE_PATH).'mySpace/myStudents.php?student='.$student_id;
                $link = Display::url($url, $url);

                foreach ($bossList as $boss) {
                    MessageManager::send_message_simple(
                        $boss['boss_id'],
                        sprintf(get_lang('BossAlertMsgSentToUserXTitle'), $user_info['complete_name']),
                        sprintf(
                            get_lang('BossAlertUserXSentMessageToUserYWithLinkZ'),
                            $currentUserInfo['complete_name'],
                            $user_info['complete_name'],
                            $link
                        )
                    );
                }
            }

            Display::addFlash(Display::return_message(get_lang('MessageSent')));
            header('Location: '.$currentUrl);
            exit;
        }
        break;
    case 'generate_certificate':
        // Delete old certificate
        $myCertificate = GradebookUtils::get_certificate_by_user_id(
            0,
            $student_id
        );
        if ($myCertificate) {
            $certificate = new Certificate($myCertificate['id'], $student_id);
            $certificate->delete(true);
        }
        // Create new one
        $certificate = new Certificate(0, $student_id);
        $certificate->generatePdfFromCustomCertificate();
        exit;
        break;
    case 'send_legal':
        $isBoss = UserManager::userIsBossOfStudent(api_get_user_id(), $student_id);
        if ($isBoss || api_is_platform_admin()) {
            $subject = get_lang('SendLegalSubject');
            $content = sprintf(
                get_lang('SendTermsDescriptionToUrlX'),
                api_get_path(WEB_PATH)
            );
            MessageManager::send_message_simple($student_id, $subject, $content);
            Display::addFlash(Display::return_message(get_lang('Sent')));
        }
        break;
    case 'delete_legal':
        $isBoss = UserManager::userIsBossOfStudent(api_get_user_id(), $student_id);
        if ($isBoss || api_is_platform_admin()) {
            $extraFieldValue = new ExtraFieldValue('user');
            $value = $extraFieldValue->get_values_by_handler_and_field_variable(
                $student_id,
                'legal_accept'
            );
            $result = $extraFieldValue->delete($value['id']);
            if ($result) {
                Display::addFlash(Display::return_message(get_lang('Deleted')));
            }
        }
        break;
    case 'reset_lp':
        $lp_id = isset($_GET['lp_id']) ? (int) $_GET['lp_id'] : '';
        $check = true;

        if (!empty($lp_id) &&
            !empty($student_id) &&
            api_is_allowed_to_edit() &&
            Security::check_token('get')
        ) {
            Event::delete_student_lp_events(
                $student_id,
                $lp_id,
                $courseInfo,
                $sessionId
            );

            // @todo delete the stats.track_e_exercises records.
            // First implement this http://support.chamilo.org/issues/1334
            Display::addFlash(Display::return_message(get_lang('LPWasReset'), 'success'));
            Security::clear_token();
        }
        break;
    default:
        break;
}

$courses_in_session = [];

// See #4676
$drh_can_access_all_courses = false;
if (api_is_drh() || api_is_platform_admin() || api_is_student_boss() || api_is_session_admin()) {
    $drh_can_access_all_courses = true;
}

$courses = CourseManager::get_course_list_of_user_as_course_admin(api_get_user_id());
$courses_in_session_by_coach = [];
$sessions_coached_by_user = Tracking::get_sessions_coached_by_user(api_get_user_id());

// RRHH or session admin
if (api_is_session_admin() || api_is_drh()) {
    $courses = CourseManager::get_courses_followed_by_drh(api_get_user_id());
    if (!empty($courses)) {
        $courses = array_column($courses, 'real_id');
    }
    $session_by_session_admin = SessionManager::get_sessions_followed_by_drh(api_get_user_id());

    if (!empty($session_by_session_admin)) {
        foreach ($session_by_session_admin as $session_coached_by_user) {
            $courses_followed_by_coach = Tracking::get_courses_list_from_session(
                $session_coached_by_user['id']
            );
            $courses_in_session_by_coach[$session_coached_by_user['id']] = $courses_followed_by_coach;
        }
    }
}

// Teacher or admin
if (!empty($sessions_coached_by_user)) {
    foreach ($sessions_coached_by_user as $session_coached_by_user) {
        $sid = (int) $session_coached_by_user['id'];
        $courses_followed_by_coach = Tracking::get_courses_followed_by_coach(api_get_user_id(), $sid);
        $courses_in_session_by_coach[$sid] = $courses_followed_by_coach;
    }
}

$sql = "SELECT c_id
        FROM $tbl_course_user
        WHERE
            relation_type <> ".COURSE_RELATION_TYPE_RRHH." AND
            user_id = ".$student_id;
$rs = Database::query($sql);

while ($row = Database::fetch_array($rs)) {
    if ($drh_can_access_all_courses) {
        $courses_in_session[0][] = $row['c_id'];
    } else {
        if (isset($courses[$row['c_id']])) {
            $courses_in_session[0][] = $row['c_id'];
        }
    }
}

// Get the list of sessions where the user is subscribed as student
$sql = 'SELECT session_id, c_id
        FROM '.Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER).'
        WHERE user_id='.$student_id;
$rs = Database::query($sql);
$tmp_sessions = [];
while ($row = Database::fetch_array($rs, 'ASSOC')) {
    $tmp_sessions[] = $row['session_id'];
    if ($drh_can_access_all_courses) {
        if (in_array($row['session_id'], $tmp_sessions)) {
            $courses_in_session[$row['session_id']][] = $row['c_id'];
        }
    } else {
        if (isset($courses_in_session_by_coach[$row['session_id']])) {
            if (in_array($row['session_id'], $tmp_sessions)) {
                $courses_in_session[$row['session_id']][] = $row['c_id'];
            }
        }
    }
}

$isDrhOfCourse = CourseManager::isUserSubscribedInCourseAsDrh(
    api_get_user_id(),
    $courseInfo
);

if (api_is_drh() && !api_is_platform_admin()) {
    if (!empty($student_id)) {
        if (api_drh_can_access_all_session_content()) {
        } else {
            if (!$isDrhOfCourse) {
                if (api_is_drh() &&
                   !UserManager::is_user_followed_by_drh($student_id, api_get_user_id())
                ) {
                    api_not_allowed(true);
                }
            }
        }
    }
}

$pluginCalendar = api_get_plugin_setting('learning_calendar', 'enabled') === 'true';

if ($pluginCalendar) {
    $plugin = LearningCalendarPlugin::create();
    $plugin->setJavaScript($htmlHeadXtra);
}

Display::display_header($nameTools);
$token = Security::get_token();

// Actions bar
echo '<div class="actions">';
echo '<a href="javascript: window.history.go(-1);">'
    .Display::return_icon('back.png', get_lang('Back'), '', ICON_SIZE_MEDIUM).'</a>';

echo '<a href="javascript: void(0);" onclick="javascript: window.print();">'
    .Display::return_icon('printer.png', get_lang('Print'), '', ICON_SIZE_MEDIUM).'</a>';

echo '<a href="'.api_get_self().'?'.Security::remove_XSS($_SERVER['QUERY_STRING']).'&export=csv">'
    .Display::return_icon('export_csv.png', get_lang('ExportAsCSV'), '', ICON_SIZE_MEDIUM).'</a> ';

echo '<a href="'.api_get_self().'?'.Security::remove_XSS($_SERVER['QUERY_STRING']).'&export=xls">'
    .Display::return_icon('export_excel.png', get_lang('ExportAsXLS'), '', ICON_SIZE_MEDIUM).'</a> ';

echo Display::url(
    Display::return_icon('attendance.png', get_lang('AccessDetails'), '', ICON_SIZE_MEDIUM),
    api_get_path(WEB_CODE_PATH).'mySpace/access_details_session.php?user_id='.$student_id
);

if (!empty($user_info['email'])) {
    $send_mail = '<a href="mailto:'.$user_info['email'].'">'.
        Display::return_icon('mail_send.png', get_lang('SendMail'), '', ICON_SIZE_MEDIUM).'</a>';
} else {
    $send_mail = Display::return_icon('mail_send_na.png', get_lang('SendMail'), '', ICON_SIZE_MEDIUM);
}
echo $send_mail;
if (!empty($student_id) && !empty($courseCode)) {
    // Only show link to connection details if course and student were defined in the URL
    echo '<a href="access_details.php?student='.$student_id.'&course='.$courseCode.'&origin='.$origin.'&cidReq='
        .$courseCode.'&id_session='.$sessionId.'">'
        .Display::return_icon('statistics.png', get_lang('AccessDetails'), '', ICON_SIZE_MEDIUM)
        .'</a>';
}

$notebookTeacherEnable = api_get_plugin_setting('notebookteacher', 'enable_plugin_notebookteacher') === 'true';
if ($notebookTeacherEnable && !empty($student_id) && !empty($courseCode)) {
    // link notebookteacher
    $optionsLink = 'student_id='.$student_id.'&origin='.$origin.'&cidReq='.$courseCode.'&id_session='.$sessionId;
    echo '<a href="'.api_get_path(WEB_PLUGIN_PATH).'notebookteacher/src/index.php?'.$optionsLink.'">'
        .Display::return_icon('notebookteacher.png', get_lang('Notebook'), '', ICON_SIZE_MEDIUM)
        .'</a>';
}

if (api_can_login_as($student_id)) {
    echo '<a href="'.api_get_path(WEB_CODE_PATH).'admin/user_list.php?action=login_as&user_id='.$student_id
        .'&sec_token='.$token.'">'
        .Display::return_icon('login_as.png', get_lang('LoginAs'), null, ICON_SIZE_MEDIUM).'</a>&nbsp;&nbsp;';
}

if (Skill::isAllowed($student_id, false)) {
    echo Display::url(
        Display::return_icon(
            'skill-badges.png',
            get_lang('AssignSkill'),
            null,
            ICON_SIZE_MEDIUM
        ),
        api_get_path(WEB_CODE_PATH).'badge/assign.php?'.http_build_query(['user' => $student_id])
    );
}
// All Attendance
if (Skill::isAllowed($student_id, false)) {
    echo Display::url(
        Display::return_icon(
            'attendance.png',
            get_lang('AssignSkill')." ** ",
            null,
            ICON_SIZE_MEDIUM
        ),
        api_get_path(WEB_CODE_PATH).'mySpace/all_attendance_list.php?student_id='.$student_id
    );
}

$permissions = StudentFollowUpPlugin::getPermissions(
    $student_id,
    api_get_user_id()
);

$isAllow = $permissions['is_allow'];
if ($isAllow) {
    echo Display::url(
        Display::return_icon(
            'blog.png',
            get_lang('Blog'),
            null,
            ICON_SIZE_MEDIUM
        ),
        api_get_path(WEB_PLUGIN_PATH).'studentfollowup/posts.php?student_id='.$student_id
    );
}
echo '</div>';
// End Actions bar
// is the user online ?
$online = get_lang('No');
if (user_is_online($student_id)) {
    $online = get_lang('Yes');
}

// get average of score and average of progress by student
$avg_student_progress = $avg_student_score = 0;

if (empty($sessionId)) {
    $isSubscribedToCourse = CourseManager::is_user_subscribed_in_course($user_info['user_id'], $courseCode);
} else {
    $isSubscribedToCourse = CourseManager::is_user_subscribed_in_course(
        $user_info['user_id'],
        $courseCode,
        true,
        $sessionId
    );
}

if ($isSubscribedToCourse) {
    $avg_student_progress = Tracking::get_avg_student_progress(
        $user_info['user_id'],
        $courseCode,
        [],
        $sessionId
    );

    // the score inside the Reporting table
    $avg_student_score = Tracking::get_avg_student_score(
        $user_info['user_id'],
        $courseCode,
        [],
        $sessionId
    );
}

$avg_student_progress = round($avg_student_progress, 2);
$time_spent_on_the_course = 0;
if (!empty($courseInfo)) {
    $time_spent_on_the_course = api_time_to_hms(
        Tracking::get_time_spent_on_the_course(
            $user_info['user_id'],
            $courseInfo['real_id'],
            $sessionId
        )
    );
}

// get information about connections on the platform by student
$first_connection_date = Tracking::get_first_connection_date($user_info['user_id']);
if ($first_connection_date == '') {
    $first_connection_date = get_lang('NoConnexion');
}

$last_connection_date = Tracking::get_last_connection_date(
    $user_info['user_id'],
    true
);
if ($last_connection_date == '') {
    $last_connection_date = get_lang('NoConnexion');
}

// cvs information
$csv_content[] = [
    get_lang('Information'),
];
$csv_content[] = [
    get_lang('Name'),
    get_lang('Email'),
    get_lang('Tel'),
];
$csv_content[] = [
    $user_info['complete_name'],
    $user_info['email'],
    $user_info['phone'],
];

$csv_content[] = [];

// csv tracking
$csv_content[] = [
    get_lang('Tracking'),
];
$csv_content[] = [
    get_lang('FirstLoginInPlatform'),
    get_lang('LatestLoginInPlatform'),
    get_lang('TimeSpentInTheCourse'),
    get_lang('Progress'),
    get_lang('Score'),
];
$csv_content[] = [
    strip_tags($first_connection_date),
    strip_tags($last_connection_date),
    $time_spent_on_the_course,
    $avg_student_progress.'%',
    $avg_student_score,
];

$coachs_name = '';
$session_name = '';

$userPicture = UserManager::getUserPicture($user_info['user_id'], USER_IMAGE_SIZE_BIG);

$userGroupManager = new UserGroup();
$userGroups = $userGroupManager->getNameListByUser(
    $user_info['user_id'],
    UserGroup::NORMAL_CLASS
);

$userInfo = [
    'id' => $user_info['user_id'],
    'complete_name' => $user_info['complete_name'],
    'complete_name_link' => $user_info['complete_name_with_message_link'],
    'phone' => $user_info['phone'],
    'code' => $user_info['official_code'],
    'username' => $user_info['username'],
    'registration_date' => $user_info['registration_date'],
    'email' => $user_info['email'],
    'has_certificates' => $user_info['has_certificates'],
    'last_login' => $user_info['last_login'],
    'profile_url' => $user_info['profile_url'],
    'groups' => $userGroupManager,
    'avatar' => $userPicture,
    'online' => $online,
];

if (!empty($courseCode)) {
    $userInfo['url_access'] = Display::url(
        get_lang('SeeAccesses'),
        'access_details.php?'
        .http_build_query(
            [
                'student' => $student_id,
                'course' => $courseCode,
                'origin' => $origin,
                'cidReq' => $courseCode,
                'id_session' => $sessionId,
            ]
        ),
        ['class' => 'btn btn-default']
    );
}

// Display timezone if the user selected one and if the admin allows the use of user's timezone
$timezone = null;
$timezone_user = UserManager::get_extra_user_data_by_field(
    $user_info['user_id'],
    'timezone'
);
$use_users_timezone = api_get_setting('use_users_timezone', 'timezones');
    if ($timezone_user['timezone'] != null && $use_users_timezone === 'true') {
        $timezone = $timezone_user['timezone'];
    }
    if ($timezone !== null) {
        $userInfo['timezone'] = $timezone;
    }

    if (is_numeric($avg_student_score)) {
        $score = $avg_student_score.'%';
    } else {
        $score = $avg_student_score;
    }

$userInfo['student_score'] = (float) $score;
$userInfo['student_progress'] = (float) $avg_student_progress;
$userInfo['first_connection'] = $first_connection_date;
$userInfo['last_connection'] = $last_connection_date;
if ($details === 'true') {
    $userInfo['time_spent_course'] = $time_spent_on_the_course;
}

$icon = '';
$timeLegalAccept = '';
$btn = '';

if (api_get_setting('allow_terms_conditions') === 'true') {
    $isBoss = UserManager::userIsBossOfStudent(api_get_user_id(), $student_id);
    if ($isBoss || api_is_platform_admin()) {
        $extraFieldValue = new ExtraFieldValue('user');
        $value = $extraFieldValue->get_values_by_handler_and_field_variable(
            $student_id,
            'legal_accept'
        );
        $icon = Display::return_icon('accept_na.png');
        $legalTime = null;

        if (isset($value['value']) && !empty($value['value'])) {
            list($legalId, $legalLanguageId, $legalTime) = explode(':', $value['value']);
            $icon = Display::return_icon('accept.png');
            $btn = Display::url(
                get_lang('DeleteLegal'),
                api_get_self().'?action=delete_legal&student='.$student_id.'&course='.$courseCode,
                ['class' => 'btn btn-danger']
            );
            $timeLegalAccept = api_get_local_time($legalTime);
        } else {
            $btn = Display::url(
                get_lang('SendLegal'),
                api_get_self().'?action=send_legal&student='.$student_id.'&course='.$courseCode,
                ['class' => 'btn btn-primary']
            );
            $timeLegalAccept = get_lang('NotRegistered');
        }
    }
    $userInfo['legal'] = [
        'icon' => $icon,
        'datetime' => $timeLegalAccept,
        'url_send' => $btn,
    ];
}

$details = true;
$tpl = new Template(
    '',
    false,
    false,
    false,
    false,
    false,
    false
);

if (!empty($courseInfo)) {
    $nb_assignments = Tracking::count_student_assignments($student_id, $courseCode, $sessionId);
    $messages = Tracking::count_student_messages($student_id, $courseCode, $sessionId);
    $links = Tracking::count_student_visited_links($student_id, $courseInfo['real_id'], $sessionId);
    $chat_last_connection = Tracking::chat_last_connection($student_id, $courseInfo['real_id'], $sessionId);
    $documents = Tracking::count_student_downloaded_documents($student_id, $courseInfo['real_id'], $sessionId);
    $uploaded_documents = Tracking::count_student_uploaded_documents($student_id, $courseCode, $sessionId);
    $tpl->assign('title', $courseInfo['title']);

    $userInfo['tools'] = [
        'tasks' => $nb_assignments,
        'messages' => $messages,
        'links' => $links,
        'chat_connection' => $chat_last_connection,
        'documents' => $documents,
        'upload_documents' => $uploaded_documents,
        'course_first_access' => Tracking::get_first_connection_date_on_the_course($student_id, $courseInfo['real_id'], $sessionId),
        'course_last_access' => Tracking::get_last_connection_date_on_the_course($student_id, $courseInfo, $sessionId),
        'count_access_dates' => Tracking::getNumberOfCourseAccessDates($student_id, $courseInfo['real_id'], $sessionId),
    ];
} else {
    $details = false;
}

$tpl->assign('user', $userInfo);
$tpl->assign('details', $details);
$templateName = $tpl->get_template('my_space/user_details.tpl');
$content = $tpl->fetch($templateName);

echo $content;



/** Start date and end date*/


$defaults['startDate'] =$startDate->format('Y-m-d H:i:s');
$defaults['endDate'] = $endDate->format('Y-m-d H:i:s');


$form = new FormValidator(
    'all_attendance_list',
    'GET',

    'all_attendance_list.php?student_id='.$_GET['student_id'].'&startDate='.$defaults['startDate'].'&endDate='.$defaults['endDate'].'&&'.api_get_cidreq(),
    ''
);
$form->addElement('html', '<input type="hidden" name="student_id" value="'.$student_id.'" >');

$form->addDateTimePicker(
    'startDate',
    [get_lang('ExeStartTime')],
    ['form_name' => 'attendance_calendar_edit'],
    5
);
$form->addDateTimePicker(
    'endDate',
    [get_lang('ExeEndTime')],
    ['form_name' => 'attendance_calendar_edit'],
    5
);

$form->addButtonSave(get_lang('Upload'));
$form->setDefaults($defaults);
$form->display();

/** Start date and end date*/
/** Display dates */


$attendance = new Attendance();

// @todo make api_get_local_time()
$data =  $attendance->getCoursesWithAttendance($student_id,$startDate,$endDate);
foreach ($data as $k => $v) {
    $title = '';
    if(isset($v[0])){
        $title = new DateTime($v[0][1]);
        $title = $title->format('Y-m-d');
    }
    echo '
    <h3>'.$title.'</h3>
    <div class="">
    <table class="table table-striped table-hover table-responsive">
        <thead>
            <tr>
                <th>'.get_lang('Training').'</th>
                <th>'.get_lang('DateExo').'</th>
                <th>'.get_lang('Present').'</th>
            </tr>
        </thead>
    <tbody>';
    $totalAttendance = count($v);
    for($i = 0;$i<$totalAttendance;$i++){
        $w = $v[$i];
        $date = api_get_local_time($w[1]);
        echo '
        <tr>
            <td>'.$w['courseTitle'].'</td>
            <td>'.$date.'</td>
            <td>'.$w['presence'].'</td>
        </tr>';

    }
    echo '</tbody>
    </table></div>';
}

/** Display dates */


if ($allowMessages === true) {
    // Messages
    echo Display::page_subheader2(get_lang('Messages'));
    echo MessageManager::getMessagesAboutUserToString($user_info);
    echo Display::url(
        get_lang('NewMessage'),
        'javascript: void(0);',
        [
            'onClick' => "$('#compose_message').show();",
            'class' => 'btn btn-default',
        ]
    );

    $form = new FormValidator(
        'messages',
        'post',
        $currentUrl.'&action=send_message'
    );
    $form->addHtml('<div id="compose_message" style="display:none;">');
    $form->addText('subject', get_lang('Subject'));
    $form->addHtmlEditor('message', get_lang('Message'));
    $form->addButtonSend(get_lang('Send'));
    $form->addHidden('sec_token', $token);
    $form->addHtml('</div>');
    $form->display();
}

$allow = api_get_configuration_value('allow_user_message_tracking');
if ($allow && (api_is_drh() || api_is_platform_admin())) {
    $users = MessageManager::getUsersThatHadConversationWithUser($student_id);
    echo Display::page_subheader2(get_lang('MessageTracking'));

    $table = new HTML_Table(['class' => 'table']);
    $column = 0;
    $row = 0;
    $headers = [
        get_lang('User'),
    ];
    foreach ($headers as $header) {
        $table->setHeaderContents($row, $column, $header);
        $column++;
    }
    $column = 0;
    $row++;
    foreach ($users as $userFollowed) {
        $followedUserId = $userFollowed['user_id'];
        $url = api_get_path(WEB_CODE_PATH).'tracking/messages.php?from_user='.$student_id.'&to_user='.$followedUserId;
        $link = Display::url(
            $userFollowed['complete_name'],
            $url
        );
        $table->setCellContents($row, $column, $link);
        $row++;
    }
    $table->display();
}

if ($pluginCalendar) {
    echo $plugin->getUserStatsPanel($student_id, $courses_in_session);
}

if ($export) {
    ob_end_clean();
    switch ($export) {
        case 'csv':
            Export::arrayToCsv($csv_content, 'reporting_student');
            break;
        case 'xls':
            Export::arrayToXls($csv_content, 'reporting_student');
            break;
    }
    exit;
}

Display::display_footer();
