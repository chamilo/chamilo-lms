<?php
/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Entity\CLpCategory;
use ChamiloSession as Session;

/**
 * Implements the tracking of students in the Reporting pages.
 */
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
$student_id = isset($_GET['student']) ? (int) $_GET['student'] : 0;
$coachId = isset($_GET['id_coach']) ? (int) $_GET['id_coach'] : 0;
$details = isset($_GET['details']) ? Security::remove_XSS($_GET['details']) : '';
$currentUrl = api_get_self().'?student='.$student_id.'&course='.$course_code.'&id_session='.$sessionId
    .'&origin='.$origin.'&details='.$details;
$allowMessages = api_get_configuration_value('private_messages_about_user');

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

$nameTools = get_lang('Learner details');

if (!empty($details)) {
    if ($origin === 'user_course') {
        if (empty($cidReq)) {
            $interbreadcrumb[] = [
                'url' => api_get_path(WEB_COURSE_PATH).$courseInfo['directory'],
                'name' => $courseInfo['title'],
            ];
        }
        $interbreadcrumb[] = [
            'url' => '../user/user.php?cidReq='.$course_code,
            'name' => get_lang('Users'),
        ];
    } else {
        if ($origin === 'tracking_course') {
            $interbreadcrumb[] = [
                'url' => '../tracking/courseLog.php?cidReq='.$course_code.'&id_session='.api_get_session_id(),
                'name' => get_lang('Reporting'),
            ];
        } else {
            if ($origin === 'resume_session') {
                $interbreadcrumb[] = [
                    'url' => "../session/session_list.php",
                    'name' => get_lang('Session list'),
                ];
                $interbreadcrumb[] = [
                    'url' => '../session/resume_session.php?id_session='.$sessionId,
                    'name' => get_lang('Session overview'),
                ];
            } else {
                $interbreadcrumb[] = [
                    'url' => api_is_student_boss() ? '#' : 'index.php',
                    'name' => get_lang('Reporting'),
                ];
                if (!empty($coachId)) {
                    $interbreadcrumb[] = [
                        'url' => 'student.php?id_coach='.$coachId,
                        'name' => get_lang('Learners of trainer'),
                    ];
                    $interbreadcrumb[] = [
                        'url' => 'myStudents.php?student='.$student_id.'&id_coach='.$coachId,
                        'name' => get_lang('Learner details'),
                    ];
                } else {
                    $interbreadcrumb[] = [
                        'url' => 'student.php',
                        'name' => get_lang('My learners'),
                    ];
                    $interbreadcrumb[] = [
                        'url' => 'myStudents.php?student='.$student_id,
                        'name' => get_lang('Learner details'),
                    ];
                }
            }
        }
    }
    $nameTools = get_lang('Learner details in course');
} else {
    if ($origin === 'resume_session') {
        $interbreadcrumb[] = [
            'url' => '../session/session_list.php',
            'name' => get_lang('Session list'),
        ];
        if (!empty($sessionId)) {
            $interbreadcrumb[] = [
                'url' => '../session/resume_session.php?id_session='.$sessionId,
                'name' => get_lang('Session overview'),
            ];
        }
    } elseif ($origin === 'teacher_details') {
        $this_section = SECTION_TRACKING;
        $interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('Reporting')];
        $interbreadcrumb[] = ['url' => 'teachers.php', 'name' => get_lang('Trainers')];
        $nameTools = $user_info['complete_name'];
    } else {
        $interbreadcrumb[] = [
            'url' => api_is_student_boss() ? '#' : 'index.php',
            'name' => get_lang('Reporting'),
        ];
        if (!empty($coachId)) {
            if ($sessionId) {
                $interbreadcrumb[] = [
                    'url' => 'student.php?id_coach='.$coachId.'&id_session='.$sessionId,
                    'name' => get_lang('Learners of trainer'),
                ];
            } else {
                $interbreadcrumb[] = [
                    'url' => 'student.php?id_coach='.$coachId,
                    'name' => get_lang('Learners of trainer'),
                ];
            }
        } else {
            $interbreadcrumb[] = [
                'url' => 'student.php',
                'name' => get_lang('My learners'),
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
            get_lang('Time spent'),
            get_lang('Number of visits'),
            get_lang('Global progress'),
            get_lang('First connection'),
            get_lang('Last connexion date'),
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
                    <th>'.get_lang('Formation unit').'</th>
                    <th>'.get_lang('Connection time').'</th>
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
        $tpl->assign('title', get_lang('Attestation of attendance'));
        $tpl->assign('session_title', $sessionInfo['name']);
        $tpl->assign('student', $studentInfo['complete_name']);
        $tpl->assign('table_progress', $table->toHtml());
        $tpl->assign('subtitle', sprintf(
            get_lang('In session %s, you had the following results'),
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
                        sprintf(get_lang('Follow up message about student %s'), $user_info['complete_name']),
                        sprintf(
                            get_lang('Hi,<br/><br/>'),
                            $currentUserInfo['complete_name'],
                            $user_info['complete_name'],
                            $link
                        )
                    );
                }
            }

            Display::addFlash(Display::return_message(get_lang('Message Sent')));
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
            $subject = get_lang('Legal conditions');
            $content = sprintf(
                get_lang('Hello,<br />Your tutor sent you your terms and conditions. You can sign it following this URL: %s'),
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
            Display::addFlash(Display::return_message(get_lang('Learning path was reset for the learner'), 'success'));
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
    .Display::return_icon('export_csv.png', get_lang('CSV export'), '', ICON_SIZE_MEDIUM).'</a> ';

echo '<a href="'.api_get_self().'?'.Security::remove_XSS($_SERVER['QUERY_STRING']).'&export=xls">'
    .Display::return_icon('export_excel.png', get_lang('Excel export'), '', ICON_SIZE_MEDIUM).'</a> ';

echo Display::url(
    Display::return_icon('attendance.png', get_lang('Access details'), '', ICON_SIZE_MEDIUM),
    api_get_path(WEB_CODE_PATH).'mySpace/access_details_session.php?user_id='.$student_id
);

if (!empty($user_info['email'])) {
    $send_mail = '<a href="mailto:'.$user_info['email'].'">'.
        Display::return_icon('mail_send.png', get_lang('Send message mail'), '', ICON_SIZE_MEDIUM).'</a>';
} else {
    $send_mail = Display::return_icon('mail_send_na.png', get_lang('Send message mail'), '', ICON_SIZE_MEDIUM);
}
echo $send_mail;
if (!empty($student_id) && !empty($course_code)) {
    // Only show link to connection details if course and student were defined in the URL
    echo '<a href="access_details.php?student='.$student_id.'&course='.$course_code.'&origin='.$origin.'&cidReq='
        .$course_code.'&id_session='.$sessionId.'">'
        .Display::return_icon('statistics.png', get_lang('Access details'), '', ICON_SIZE_MEDIUM)
        .'</a>';
}

$notebookTeacherEnable = api_get_plugin_setting('notebookteacher', 'enable_plugin_notebookteacher') === 'true';
if ($notebookTeacherEnable && !empty($student_id) && !empty($course_code)) {
    // link notebookteacher
    $optionsLink = 'student_id='.$student_id.'&origin='.$origin.'&cidReq='.$course_code.'&id_session='.$sessionId;
    echo '<a href="'.api_get_path(WEB_PLUGIN_PATH).'notebookteacher/src/index.php?'.$optionsLink.'">'
        .Display::return_icon('notebookteacher.png', get_lang('Notebook'), '', ICON_SIZE_MEDIUM)
        .'</a>';
}

if (api_can_login_as($student_id)) {
    echo '<a href="'.api_get_path(WEB_CODE_PATH).'admin/user_list.php?action=login_as&user_id='.$student_id
        .'&sec_token='.$token.'">'
        .Display::return_icon('login_as.png', get_lang('Login as'), null, ICON_SIZE_MEDIUM).'</a>&nbsp;&nbsp;';
}

if (Skill::isAllowed($student_id, false)) {
    echo Display::url(
        Display::return_icon(
            'skill-badges.png',
            get_lang('Assign skill'),
            null,
            ICON_SIZE_MEDIUM
        ),
        api_get_path(WEB_CODE_PATH).'badge/assign.php?'.http_build_query(['user' => $student_id])
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

// is the user online ?
$online = get_lang('No');
if (user_is_online($student_id)) {
    $online = get_lang('Yes');
}

// get average of score and average of progress by student
$avg_student_progress = $avg_student_score = 0;

if (empty($sessionId)) {
    $isSubscribedToCourse = CourseManager::is_user_subscribed_in_course($user_info['user_id'], $course_code);
} else {
    $isSubscribedToCourse = CourseManager::is_user_subscribed_in_course(
        $user_info['user_id'],
        $course_code,
        true,
        $sessionId
    );
}

if ($isSubscribedToCourse) {
    $avg_student_progress = Tracking::get_avg_student_progress(
        $user_info['user_id'],
        $course_code,
        [],
        $sessionId
    );

    // the score inside the Reporting table
    $avg_student_score = Tracking::get_avg_student_score(
        $user_info['user_id'],
        $course_code,
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
    $first_connection_date = get_lang('No connection');
}

$last_connection_date = Tracking::get_last_connection_date(
    $user_info['user_id'],
    true
);
if ($last_connection_date == '') {
    $last_connection_date = get_lang('No connection');
}

// cvs information
$csv_content[] = [
    get_lang('Information'),
];
$csv_content[] = [
    get_lang('Name'),
    get_lang('e-mail'),
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
    get_lang('Reporting'),
];
$csv_content[] = [
    get_lang('First connectionInPlatform'),
    get_lang('Latest login in platform'),
    get_lang('Time spentInTheCourse'),
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

if (!empty($course_code)) {
    $userInfo['url_access'] = Display::url(
        get_lang('See accesses'),
        'access_details.php?'
        .http_build_query(
            [
                'student' => $student_id,
                'course' => $course_code,
                'origin' => $origin,
                'cidReq' => $course_code,
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

$userInfo['student_score'] = $score;
$userInfo['student_progress'] = $avg_student_progress;
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
                get_lang('Delete legal agreement'),
                api_get_self().'?action=delete_legal&student='.$student_id.'&course='.$course_code,
                ['class' => 'btn btn-danger']
            );
            $timeLegalAccept = api_get_local_time($legalTime);
        } else {
            $btn = Display::url(
                get_lang('Send message legal agreement'),
                api_get_self().'?action=send_legal&student='.$student_id.'&course='.$course_code,
                ['class' => 'btn btn-primary']
            );
            $timeLegalAccept = get_lang('Not Registered');
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
    $nb_assignments = Tracking::count_student_assignments($student_id, $course_code, $sessionId);
    $messages = Tracking::count_student_messages($student_id, $course_code, $sessionId);
    $links = Tracking::count_student_visited_links($student_id, $courseInfo['real_id'], $sessionId);
    $chat_last_connection = Tracking::chat_last_connection($student_id, $courseInfo['real_id'], $sessionId);
    $documents = Tracking::count_student_downloaded_documents($student_id, $courseInfo['real_id'], $sessionId);
    $uploaded_documents = Tracking::count_student_uploaded_documents($student_id, $course_code, $sessionId);
    $tpl->assign('title', $courseInfo['title']);

    $userInfo['tools'] = [
        'tasks' => $nb_assignments,
        'messages' => $messages,
        'links' => $links,
        'chat_connection' => $chat_last_connection,
        'documents' => $documents,
        'upload_documents' => $uploaded_documents,
    ];
} else {
    $details = false;
}

$tpl->assign('user', $userInfo);
$tpl->assign('details', $details);
$templateName = $tpl->get_template('my_space/user_details.tpl');
$content = $tpl->fetch($templateName);

echo $content;

$allowAll = api_get_configuration_value('allow_teacher_access_student_skills');
if ($allowAll) {
    // Show all skills
    echo Tracking::displayUserSkills(
        $user_info['user_id'],
        0,
        0,
        true
    );
} else {
    // Default behaviour - Show all skills depending the course and session id
    echo Tracking::displayUserSkills(
        $user_info['user_id'],
        $courseInfo ? $courseInfo['real_id'] : 0,
        $sessionId
    );
}

echo '<br /><br />';
echo '<div class="row">
        <div class="col-sm-5">';
if (!empty($userGroups)) {
    echo '<table class="table table-striped table-hover">
           <thead>
            <tr>
            <th>';
    echo get_lang('Classes');
    echo '</th>
                    </tr>
                    </thead>
                    <tbody>';
    foreach ($userGroups as $class) {
        echo '<tr><td>'.$class.'</td></tr>';
    }
    echo '</tbody></table>';
}
echo '</div></div>';

$exportCourseList = [];
$lpIdList = [];
if (empty($details)) {
    $csv_content[] = [];
    $csv_content[] = [
        get_lang('Session'),
        get_lang('Course'),
        get_lang('Time'),
        get_lang('Progress'),
        get_lang('Score'),
        get_lang('Not attended'),
        get_lang('Evaluations'),
    ];

    $attendance = new Attendance();
    foreach ($courses_in_session as $sId => $courses) {
        $session_name = '';
        $access_start_date = '';
        $access_end_date = '';
        $date_session = '';
        $title = Display::return_icon('course.png', get_lang('Courses')).' '.get_lang('Courses');

        $session_info = api_get_session_info($sId);
        if ($session_info) {
            $session_name = $session_info['name'];
            if (!empty($session_info['access_start_date'])) {
                $access_start_date = api_format_date($session_info['access_start_date'], DATE_FORMAT_SHORT);
            }

            if (!empty($session_info['access_end_date'])) {
                $access_end_date = api_format_date($session_info['access_end_date'], DATE_FORMAT_SHORT);
            }

            if (!empty($access_start_date) && !empty($access_end_date)) {
                $date_session = get_lang('From').' '.$access_start_date.' '.get_lang('Until').' '.$access_end_date;
            }
            $title = Display::return_icon('session.png', get_lang('Session'))
                .' '.$session_name.($date_session ? ' ('.$date_session.')' : '');
        }

        // Courses
        echo '<h3>'.$title.'</h3>';
        echo '<div class="table-responsive">';
        echo '<table class="table table-striped table-hover courses-tracking">';
        echo '<thead>';
        echo '<tr>
            <th>'.get_lang('Course').'</th>
            <th>'.get_lang('Time').'</th>
            <th>'.get_lang('Progress').'</th>
            <th>'.get_lang('Score').'</th>
            <th>'.get_lang('Not attended').'</th>
            <th>'.get_lang('Evaluations').'</th>
            <th>'.get_lang('Details').'</th>
        </tr>';
        echo '</thead>';
        echo '<tbody>';

        $csvRow = [
            '',
            get_lang('Course'),
            get_lang('Time'),
            get_lang('Progress'),
            get_lang('Score'),
            get_lang('Not attended'),
            get_lang('Evaluations'),
            get_lang('Details'),
        ];

        $exportCourseList[$sId][] = $csvRow;

        if (!empty($courses)) {
            $totalCourseTime = 0;
            $totalAttendance = [0, 0];
            $totalScore = 0;
            $totalProgress = 0;
            $gradeBookTotal = [0, 0];
            $totalCourses = count($courses);
            $scoreDisplay = ScoreDisplay::instance();

            foreach ($courses as $courseId) {
                $courseInfoItem = api_get_course_info_by_id($courseId);
                $courseId = $courseInfoItem['real_id'];
                $courseCodeItem = $courseInfoItem['code'];

                if (empty($session_info)) {
                    $isSubscribed = CourseManager::is_user_subscribed_in_course(
                        $student_id,
                        $courseCodeItem
                    );
                } else {
                    $isSubscribed = CourseManager::is_user_subscribed_in_course(
                        $student_id,
                        $courseCodeItem,
                        true,
                        $sId
                    );
                }

                if ($isSubscribed) {
                    $timeInSeconds = Tracking::get_time_spent_on_the_course(
                        $user_info['user_id'],
                        $courseId,
                        $sId
                    );
                    $totalCourseTime += $timeInSeconds;
                    $time_spent_on_course = api_time_to_hms($timeInSeconds);

                    // get average of faults in attendances by student
                    $results_faults_avg = $attendance->get_faults_average_by_course(
                        $student_id,
                        $courseCodeItem,
                        $sId
                    );

                    $attendances_faults_avg = '0/0 (0%)';
                    if (!empty($results_faults_avg['total'])) {
                        if (api_is_drh()) {
                            $attendances_faults_avg = '<a title="'.get_lang('Go to attendances').'" href="'.api_get_path(WEB_CODE_PATH)
                                .'attendance/index.php?cidReq='.$courseCodeItem.'&id_session='.$sId.'&student_id='
                                .$student_id.'">'
                                .$results_faults_avg['faults'].'/'.$results_faults_avg['total'].' ('
                                .$results_faults_avg['porcent'].'%)</a>';
                        } else {
                            $attendances_faults_avg = $results_faults_avg['faults'].'/'
                                .$results_faults_avg['total']
                                .' ('.$results_faults_avg['porcent'].'%)';
                        }
                        $totalAttendance[0] += $results_faults_avg['faults'];
                        $totalAttendance[1] += $results_faults_avg['total'];
                    }

                    // Get evaluations by student
                    $cats = Category::load(
                        null,
                        null,
                        $courseCodeItem,
                        null,
                        null,
                        $sId
                    );

                    $scoretotal = [];
                    if (isset($cats) && isset($cats[0])) {
                        if (!empty($sId)) {
                            $scoretotal = $cats[0]->calc_score($student_id, null, $courseCodeItem, $sId);
                        } else {
                            $scoretotal = $cats[0]->calc_score($student_id, null, $courseCodeItem);
                        }
                    }

                    $scoretotal_display = '0/0 (0%)';
                    if (!empty($scoretotal) && !empty($scoretotal[1])) {
                        $scoretotal_display =
                            round($scoretotal[0], 1).'/'.
                            round($scoretotal[1], 1).
                            ' ('.round(($scoretotal[0] / $scoretotal[1]) * 100, 2).' %)';

                        $gradeBookTotal[0] += $scoretotal[0];
                        $gradeBookTotal[1] += $scoretotal[1];
                    }

                    $progress = Tracking::get_avg_student_progress(
                        $user_info['user_id'],
                        $courseCodeItem,
                        [],
                        $sId
                    );

                    $totalProgress += $progress;

                    $score = Tracking::get_avg_student_score(
                        $user_info['user_id'],
                        $courseCodeItem,
                        [],
                        $sId
                    );

                    if (is_numeric($score)) {
                        $totalScore += $score;
                    }

                    $progress = empty($progress) ? '0%' : $progress.'%';
                    $score = empty($score) ? '0%' : $score.'%';

                    $csvRow = [
                        $session_name,
                        $courseInfoItem['title'],
                        $time_spent_on_course,
                        $progress,
                        $score,
                        $attendances_faults_avg,
                        $scoretotal_display,
                    ];

                    $csv_content[] = $csvRow;
                    $exportCourseList[$sId][] = $csvRow;

                    echo '<tr>
                    <td ><a href="'.$courseInfoItem['course_public_url'].'?id_session='.$sId.'">'.
                        $courseInfoItem['title'].'</a></td>
                    <td >'.$time_spent_on_course.'</td>
                    <td >'.$progress.'</td>
                    <td >'.$score.'</td>
                    <td >'.$attendances_faults_avg.'</td>
                    <td >'.$scoretotal_display.'</td>';

                    if (!empty($coachId)) {
                        echo '<td width="10"><a href="'.api_get_self().'?student='.$user_info['user_id']
                            .'&details=true&course='.$courseInfoItem['code'].'&id_coach='.$coachId.'&origin='.$origin
                            .'&id_session='.$sId.'#infosStudent">'
                            .Display::return_icon('2rightarrow.png', get_lang('Details')).'</a></td>';
                    } else {
                        echo '<td width="10"><a href="'.api_get_self().'?student='.$user_info['user_id']
                            .'&details=true&course='.$courseInfoItem['code'].'&origin='.$origin.'&id_session='.$sId.'#infosStudent">'
                            .Display::return_icon('2rightarrow.png', get_lang('Details')).'</a></td>';
                    }
                    echo '</tr>';
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
            echo '<tr>
                <th>'.get_lang('Total').'</th>
                <th>'.$totalTimeFormatted.'</th>
                <th>'.$totalProgressFormatted.'</th>
                <th>'.$totalScoreFormatted.'</th>
                <th>'.$totalAttendanceFormatted.'</th>
                <th>'.$totalEvaluations.'</th>
                <th></th>
            </tr>';

            $csvRow = [
                get_lang('Total'),
                '',
                $totalTimeFormatted,
                $totalProgressFormatted,
                $totalScoreFormatted,
                $totalAttendanceFormatted,
                $totalEvaluations,
                '',
            ];

            $csv_content[] = $csvRow;
            $exportCourseList[$sId][] = $csvRow;
            $sessionAction = Display::url(
                Display::return_icon('export_csv.png', get_lang('CSV export'), [], ICON_SIZE_MEDIUM),
                $currentUrl
                    .'&'
                    .http_build_query(
                        ['action' => 'export_one_session_row', 'export' => 'csv', 'session_to_export' => $sId]
                    )
            );
            $sessionAction .= Display::url(
                Display::return_icon('export_excel.png', get_lang('Excel export'), [], ICON_SIZE_MEDIUM),
                $currentUrl
                    .'&'
                    .http_build_query(
                        ['action' => 'export_one_session_row', 'export' => 'xls', 'session_to_export' => $sId]
                    )
            );

            if (!empty($sId)) {
                $sessionAction .= Display::url(
                    Display::return_icon('pdf.png', get_lang('Export to PDF'), [], ICON_SIZE_MEDIUM),
                    $currentUrl
                    .'&'
                    .http_build_query(
                        ['action' => 'export_to_pdf', 'session_to_export' => $sId]
                    )
                );
            }
            echo $sessionAction;
        } else {
            echo "<tr><td colspan='5'>".get_lang('This course could not be found')."</td></tr>";
        }
        Session::write('export_course_list', $exportCourseList);
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
    }
} else {
    $columnHeaders = [
        'lp' => get_lang('Learning paths'),
        'time' => get_lang('Time').
            Display::return_icon(
                'info3.gif',
                get_lang('Total time in course'),
                ['align' => 'absmiddle', 'hspace' => '3px']
            ),
        'best_score' => get_lang('Best score'),
        'latest_attempt_avg_score' => get_lang('Latest attempt average score').
            Display::return_icon(
                'info3.gif',
                get_lang('Average is calculated based on the latest attempts'),
                ['align' => 'absmiddle', 'hspace' => '3px']
            ),
        'progress' => get_lang('Progress').
            Display::return_icon(
                'info3.gif',
                get_lang('% of learning objects visited'),
                ['align' => 'absmiddle', 'hspace' => '3px']
            ),
        'last_connection' => get_lang('Latest login').
            Display::return_icon(
                'info3.gif',
                get_lang('Last time learner entered the course'),
                ['align' => 'absmiddle', 'hspace' => '3px']
            ),
    ];

    $timeCourse = null;
    if (Tracking::minimumTimeAvailable($sessionId, $courseInfo['real_id'])) {
        $timeCourse = Tracking::getCalculateTime($student_id, $courseInfo['real_id'], $sessionId);
    }

    if ($user_info['status'] != INVITEE) {
        $csv_content[] = [];
        $csv_content[] = [str_replace('&nbsp;', '', strip_tags($userInfo['complete_name']))];
        $trackingColumns = api_get_configuration_value('tracking_columns');
        if (isset($trackingColumns['my_students_lp'])) {
            foreach ($columnHeaders as $key => $value) {
                if (!isset($trackingColumns['my_progress_lp'][$key]) ||
                    $trackingColumns['my_students_lp'][$key] == false
                ) {
                    unset($columnHeaders[$key]);
                }
            }
        }

        $headers = '';
        $columnHeadersToExport = [];
        // csv export headers
        foreach ($columnHeaders as $key => $columnName) {
            $columnHeadersToExport[] = strip_tags($columnName);
            $headers .= Display::tag(
                'th',
                $columnName
            );
        }
        $csv_content[] = $columnHeadersToExport;
        $columnHeadersKeys = array_keys($columnHeaders);
        $categoriesTempList = learnpath::getCategories($courseInfo['real_id']);
        $categoryTest = new CLpCategory();
        $categoryTest->setId(0);
        $categoryTest->setName(get_lang('Without category'));
        $categoryTest->setPosition(0);
        $categories = [
            $categoryTest,
        ];

        if (!empty($categoriesTempList)) {
            $categories = array_merge($categories, $categoriesTempList);
        }

        $userEntity = api_get_user_entity(api_get_user_id());

        /** @var CLpCategory $item */
        foreach ($categories as $item) {
            $categoryId = $item->getId();
            if (!learnpath::categoryIsVisibleForStudent($item, $userEntity, $courseInfo['real_id'], $sessionId)) {
                continue;
            }

            $list = new LearnpathList(
                api_get_user_id(),
                $courseInfo,
                $sessionId,
                null,
                false,
                $categoryId,
                false,
                true
            );

            $flat_list = $list->get_flat_list();
            $i = 0;
            if (count($categories) > 1) {
                echo Display::page_subheader2($item->getName());
            }

            echo '<div class="table-responsive">';
            echo '<table class="table table-striped table-hover"><thead><tr>';
            echo $headers;
            echo '<th>'.get_lang('Details').'</th>';
            if (api_is_allowed_to_edit()) {
                echo '<th>'.get_lang('Reset Learning path').'</th>';
            }
            echo '</tr></thead><tbody>';

            foreach ($flat_list as $learnpath) {
                $lpIdList[] = $learnpath['iid'];
                $lp_id = $learnpath['lp_old_id'];
                $lp_name = $learnpath['lp_name'];
                $any_result = false;

                // Get progress in lp
                $progress = Tracking::get_avg_student_progress(
                    $student_id,
                    $course_code,
                    [$lp_id],
                    $sessionId
                );

                if ($progress === null) {
                    $progress = '0%';
                } else {
                    $any_result = true;
                }

                // Get time in lp
                if (!empty($timeCourse)) {
                    $lpTime = isset($timeCourse[TOOL_LEARNPATH]) ? $timeCourse[TOOL_LEARNPATH] : 0;
                    $total_time = isset($lpTime[$lp_id]) ? (int) $lpTime[$lp_id] : 0;
                } else {
                    $total_time = Tracking::get_time_spent_in_lp(
                        $student_id,
                        $course_code,
                        [$lp_id],
                        $sessionId
                    );
                }

                if (!empty($total_time)) {
                    $any_result = true;
                }

                // Get last connection time in lp
                $start_time = Tracking::get_last_connection_time_in_lp(
                    $student_id,
                    $course_code,
                    $lp_id,
                    $sessionId
                );

                if (!empty($start_time)) {
                    $start_time = api_convert_and_format_date($start_time, DATE_TIME_FORMAT_LONG);
                } else {
                    $start_time = '-';
                }

                if (!empty($total_time)) {
                    $any_result = true;
                }

                // Quiz in lp
                $score = Tracking::get_avg_student_score(
                    $student_id,
                    $course_code,
                    [$lp_id],
                    $sessionId
                );

                // Latest exercise results in a LP
                $score_latest = Tracking::get_avg_student_score(
                    $student_id,
                    $course_code,
                    [$lp_id],
                    $sessionId,
                    false,
                    true
                );

                $bestScore = Tracking::get_avg_student_score(
                    $student_id,
                    $course_code,
                    [$lp_id],
                    $sessionId,
                    false,
                    false,
                    true
                );

                if (empty($bestScore)) {
                    $bestScore = '';
                } else {
                    $bestScore = $bestScore.'%';
                }

                if ($i % 2 == 0) {
                    $css_class = 'row_even';
                } else {
                    $css_class = 'row_odd';
                }

                $i++;

                if (isset($score_latest) && !is_null($score_latest)) {
                    if (is_numeric($score_latest)) {
                        $score_latest = $score_latest.'%';
                    }
                }

                if (is_numeric($progress)) {
                    $progress = $progress.'%';
                } else {
                    $progress = '-';
                }

                echo '<tr class="'.$css_class.'">';
                $contentToExport = [];
                if (in_array('lp', $columnHeadersKeys)) {
                    $contentToExport[] = api_html_entity_decode(
                        stripslashes($lp_name),
                        ENT_QUOTES,
                        $charset
                    );
                    echo Display::tag('td', stripslashes($lp_name));
                }
                if (in_array('time', $columnHeadersKeys)) {
                    $contentToExport[] = api_time_to_hms($total_time);
                    echo Display::tag('td', api_time_to_hms($total_time));
                }

                if (in_array('best_score', $columnHeadersKeys)) {
                    $contentToExport[] = $bestScore;
                    echo Display::tag('td', $bestScore);
                }
                if (in_array('latest_attempt_avg_score', $columnHeadersKeys)) {
                    $contentToExport[] = $score_latest;
                    echo Display::tag('td', $score_latest);
                }

                if (in_array('progress', $columnHeadersKeys)) {
                    $contentToExport[] = $progress;
                    echo Display::tag('td', $progress);
                }

                if (in_array('last_connection', $columnHeadersKeys)) {
                    // Do not change with api_convert_and_format_date, because this value came from the lp_item_view table
                    // which implies several other changes not a priority right now
                    $contentToExport[] = $start_time;
                    echo Display::tag('td', $start_time);
                }

                $csv_content[] = $contentToExport;

                if ($any_result === true) {
                    $from = '';
                    if ($from_myspace) {
                        $from = '&from=myspace';
                    }
                    $link = Display::url(
                        Display::return_icon('2rightarrow.png', get_lang('Details')),
                        'lp_tracking.php?cidReq='.$course_code.'&course='.$course_code.$from.'&origin='.$origin
                        .'&lp_id='.$lp_id.'&student_id='.$user_info['user_id'].'&id_session='.$sessionId
                    );
                    echo Display::tag('td', $link);
                }

                if (api_is_allowed_to_edit()) {
                    echo '<td>';
                    if ($any_result === true) {
                        $url = 'myStudents.php?action=reset_lp&sec_token='.$token.'&cidReq='.$course_code.'&course='
                            .$course_code.'&details='.$details.'&origin='.$origin.'&lp_id='.$lp_id.'&student='
                            .$user_info['user_id'].'&details=true&id_session='.$sessionId;
                        echo Display::url(
                            Display::return_icon('clean.png', get_lang('Clean')),
                            $url,
                            [
                                'onclick' => "javascript:if(!confirm('"
                                    .addslashes(
                                        api_htmlentities(get_lang('Are you sure you want to delete'))
                                    )
                                    ."')) return false;",
                            ]
                        );
                    }
                    echo '</td>';
                    echo '</tr>';
                }
            }
            echo '</tbody></table></div>';
        }
    }

    if ($user_info['status'] != INVITEE) {
        echo '<div class="table-responsive">
        <table class="table table-striped table-hover">
        <thead>
        <tr>';
        echo '<th>'.get_lang('Tests').'</th>';
        echo '<th>'.get_lang('Learning paths').'</th>';
        echo '<th>'.get_lang('Average score in learning paths').' '.
            Display::return_icon(
                'info3.gif',
                get_lang('Average score'),
                ['align' => 'absmiddle', 'hspace' => '3px']
            ).'</th>';
        echo '<th>'.get_lang('Attempts').'</th>';
        echo '<th>'.get_lang('Latest attempt').'</th>';
        echo '<th>'.get_lang('All attempts').'</th>';
        echo '</tr></thead><tbody>';

        $csv_content[] = [];
        $csv_content[] = [
            get_lang('Tests'),
            get_lang('Learning paths'),
            get_lang('Average score in learning paths'),
            get_lang('Attempts'),
        ];

        $t_quiz = Database::get_course_table(TABLE_QUIZ_TEST);
        $sessionCondition = api_get_session_condition(
            $sessionId,
            true,
            true,
            'quiz.session_id'
        );

        $sql = "SELECT quiz.title, id 
                FROM $t_quiz AS quiz
                WHERE
                    quiz.c_id = ".$courseInfo['real_id']." AND
                    active IN (0, 1)
                    $sessionCondition                    
                ORDER BY quiz.title ASC ";

        $result_exercices = Database::query($sql);
        $i = 0;
        if (Database::num_rows($result_exercices) > 0) {
            while ($exercices = Database::fetch_array($result_exercices)) {
                $exercise_id = (int) $exercices['id'];
                $count_attempts = Tracking::count_student_exercise_attempts(
                    $student_id,
                    $courseInfo['real_id'],
                    $exercise_id,
                    0,
                    0,
                    $sessionId,
                    2
                );
                $score_percentage = Tracking::get_avg_student_exercise_score(
                    $student_id,
                    $course_code,
                    $exercise_id,
                    $sessionId,
                    1,
                    0
                );

                $lp_name = '-';
                if (!isset($score_percentage) && $count_attempts > 0) {
                    $scores_lp = Tracking::get_avg_student_exercise_score(
                        $student_id,
                        $course_code,
                        $exercise_id,
                        $sessionId,
                        2,
                        1
                    );
                    $score_percentage = $scores_lp[0];
                    $lp_name = $scores_lp[1];
                }
                $lp_name = !empty($lp_name) ? $lp_name : get_lang('No learning path');

                $css_class = 'row_even';
                if ($i % 2) {
                    $css_class = 'row_odd';
                }

                echo '<tr class="'.$css_class.'"><td>'.Exercise::get_formated_title_variable($exercices['title']).'</td>';
                echo '<td>';

                if (!empty($lp_name)) {
                    echo $lp_name;
                } else {
                    echo '-';
                }

                echo '</td>';
                echo '<td>';

                if ($count_attempts > 0) {
                    echo $score_percentage.'%';
                } else {
                    echo '-';
                    $score_percentage = 0;
                }

                echo '</td>';
                echo '<td>'.$count_attempts.'</td>';
                echo '<td>';

                $sql = 'SELECT exe_id FROM '.$tbl_stats_exercices.'
                         WHERE
                            exe_exo_id = "'.$exercise_id.'" AND
                            exe_user_id ="'.$student_id.'" AND
                            c_id = '.$courseInfo['real_id'].' AND
                            session_id = "'.$sessionId.'" AND
                            status = ""
                        ORDER BY exe_date DESC
                        LIMIT 1';
                $result_last_attempt = Database::query($sql);
                if (Database::num_rows($result_last_attempt) > 0) {
                    $id_last_attempt = Database::result($result_last_attempt, 0, 0);
                    if ($count_attempts > 0) {
                        $qualifyLink = '';
                        if ($allowToQualify) {
                            $qualifyLink = '&action=qualify';
                        }
                        $attemptLink = '../exercise/exercise_show.php?id='.$id_last_attempt.'&cidReq='.$course_code
                            .'&id_session='.$sessionId.'&session_id='.$sessionId.'&student='.$student_id.'&origin='
                            .(empty($origin) ? 'tracking' : $origin).$qualifyLink;
                        echo Display::url(
                            Display::return_icon('quiz.png', get_lang('Test')),
                            $attemptLink
                        );
                    }
                }
                echo '</td>';

                echo '<td>';
                if ($count_attempts > 0) {
                    $all_attempt_url = "../exercise/exercise_report.php?exerciseId=$exercise_id&"
                        ."cidReq=$course_code&filter_by_user=$student_id&id_session=$sessionId";
                    echo Display::url(
                        Display::return_icon(
                            'test_results.png',
                            get_lang('All attempts'),
                            [],
                            ICON_SIZE_SMALL
                        ),
                        $all_attempt_url
                    );
                }
                echo '</td>';
                echo '</tr>';
                $data_exercices[$i][] = $exercices['title'];
                $data_exercices[$i][] = $score_percentage.'%';
                $data_exercices[$i][] = $count_attempts;

                $csv_content[] = [
                    $exercices['title'],
                    $lp_name,
                    $score_percentage,
                    $count_attempts,
                ];

                $i++;
            }
        } else {
            echo '<tr><td colspan="6">'.get_lang('NoTest').'</td></tr>';
        }
        echo '</tbody></table></div>';
    }

    // @when using sessions we do not show the survey list
    if (empty($sessionId)) {
        $survey_list = SurveyManager::get_surveys($course_code, $sessionId);
        if (!empty($survey_list)) {
            $survey_data = [];
            foreach ($survey_list as $survey) {
                $user_list = SurveyManager::get_people_who_filled_survey(
                    $survey['survey_id'],
                    false,
                    $courseInfo['real_id']
                );
                $survey_done = Display::return_icon(
                    "accept_na.png",
                    get_lang('There is no answer for the moment'),
                    [],
                    ICON_SIZE_SMALL
                );
                if (in_array($student_id, $user_list)) {
                    $survey_done = Display::return_icon(
                        "accept.png",
                        get_lang('Answered'),
                        [],
                        ICON_SIZE_SMALL
                    );
                }
                $data = ['title' => $survey['title'], 'done' => $survey_done];
                $survey_data[] = $data;
            }

            if (!empty($survey_data)) {
                $table = new HTML_Table(['class' => 'data_table']);
                $header_names = [get_lang('Survey'), get_lang('Answered')];
                $row = 0;
                $column = 0;
                foreach ($header_names as $item) {
                    $table->setHeaderContents($row, $column, $item);
                    $column++;
                }
                $row = 1;
                foreach ($survey_data as $data) {
                    $column = 0;
                    $table->setCellContents($row, $column, $data);
                    $class = 'class="row_odd"';
                    if ($row % 2) {
                        $class = 'class="row_even"';
                    }
                    $table->setRowAttributes($row, $class, true);
                    $column++;
                    $row++;
                }
                echo $table->toHtml();
            }
        }
    }

    require_once '../work/work.lib.php';
    $userWorks = getWorkPerUser($student_id, $courseInfo['real_id'], $sessionId);
    echo '
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>'.get_lang('Tasks').'</th>
                        <th class="text-center">'.get_lang('Document ID').'</th>
                        <th class="text-center">'.get_lang('Note').'</th>
                        <th class="text-center">'.get_lang('Handed out').'</th>
                        <th class="text-center">'.get_lang('Deadline').'</th>
                        <th class="text-center">'.get_lang('Assignment work time').'</th>
                    </tr>
                </thead>
                <tbody>
    ';
    $workingTime = api_get_configuration_value('considered_working_time');
    foreach ($userWorks as $work) {
        $work = $work['work'];
        foreach ($work->user_results as $key => $results) {
            echo '<tr>';
            echo '<td>'.$work->title.'</td>';
            $documentNumber = $key + 1;
            $url = api_get_path(WEB_CODE_PATH).'work/view.php?cidReq='.$course_code.'&id_session='.$sessionId.'&id='
                .$results['id'];
            echo '<td class="text-center"><a href="'.$url.'">('.$documentNumber.')</a></td>';
            $qualification = !empty($results['qualification']) ? $results['qualification'] : '-';
            echo '<td class="text-center">'.$qualification.'</td>';
            echo '<td class="text-center">'.$results['formatted_date'].'</td>';
            $assignment = get_work_assignment_by_id($work->id, $courseInfo['real_id']);

            echo '<td class="text-center">';
            if (!empty($assignment['expires_on'])) {
                echo api_convert_and_format_date($assignment['expires_on']);
            }
            echo '</td>';

            $fieldValue = new ExtraFieldValue('work');
            $resultExtra = $fieldValue->getAllValuesForAnItem(
                $work->iid,
                true
            );

            foreach ($resultExtra as $field) {
                $field = $field['value'];
                if ($workingTime == $field->getField()->getVariable()) {
                    echo '<td class="text-center">'.$field->getValue().'</td>';
                }
            }
            echo '</tr>';
        }
    }

    echo '</tbody>
            </table>
        </div>
    ';

    $csv_content[] = [];

    $csv_content[] = [
        get_lang('OTI (Online Training Interaction) settings report'),
    ];

    $csv_content[] = [
        get_lang('Assignments'),
        $nb_assignments,
    ];
    $csv_content[] = [
        get_lang('Messages'),
        $messages,
    ];
    $csv_content[] = [
        get_lang('Links accessed'),
        $links,
    ];
    $csv_content[] = [
        get_lang('Documents downloaded'),
        $documents,
    ];
    $csv_content[] = [
        get_lang('Uploaded documents'),
        $uploaded_documents,
    ];
    $csv_content[] = [
        get_lang('Latest chat connection'),
        $chat_last_connection,
    ];
} //end details

if ($allowMessages === true) {
    // Messages
    echo Display::page_subheader2(get_lang('Messages'));
    echo MessageManager::getMessagesAboutUserToString($user_info);
    echo Display::url(
        get_lang('New message'),
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
    $form->addButtonSend(get_lang('Send message'));
    $form->addHidden('sec_token', $token);
    $form->addHtml('</div>');
    $form->display();
}

$allow = api_get_configuration_value('allow_user_message_tracking');
if ($allow && (api_is_drh() || api_is_platform_admin())) {
    $users = MessageManager::getUsersThatHadConversationWithUser($student_id);
    echo Display::page_subheader2(get_lang('MessageReporting'));

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
