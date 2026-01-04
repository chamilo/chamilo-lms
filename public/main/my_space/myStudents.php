<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Entity\Usergroup;
use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Enums\ObjectIcon;
use Chamilo\CoreBundle\Enums\StateIcon;
use Chamilo\CoreBundle\Enums\ToolIcon;
use Chamilo\CoreBundle\Event\AbstractEvent;
use Chamilo\CoreBundle\Event\Events;
use Chamilo\CoreBundle\Event\MyStudentsLpTrackingEvent;
use Chamilo\CoreBundle\Event\MyStudentsQuizTrackingEvent;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CLpCategory;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use ChamiloSession as Session;

if (!isset($_GET['course'])) {
    $cidReset = true;
}

require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();

$htmlHeadXtra[] = null;
$export = $_GET['export'] ?? false;
$sessionId = isset($_GET['sid']) ? (int) $_GET['sid'] : 0;
$action = $_GET['action'] ?? '';
$origin = api_get_origin();
$courseId = isset($_GET['cid']) ? (int) $_GET['cid'] : 0;
$course = api_get_course_entity($courseId);
$courseCode = '';
if (null !== $course) {
    $courseCode = $course->getCode();
}
$session = api_get_session_entity($sessionId);
$studentId = isset($_GET['student']) ? (int) $_GET['student'] : 0;
$coachId = isset($_GET['id_coach']) ? (int) $_GET['id_coach'] : 0;
$details = isset($_GET['details']) ? Security::remove_XSS($_GET['details']) : '';
$currentUrl = api_get_self().'?student='.$studentId.'&course='.$courseCode.'&sid='.$sessionId
    .'&origin='.$origin.'&details='.$details.'&cid='.$courseId;
$allowMessages = ('true' === api_get_setting('message.private_messages_about_user'));
$workingTime = api_get_setting('work.considered_working_time');
$workingTimeEdit = api_get_setting('workflows.allow_working_time_edition');

$allowToQualify = api_is_allowed_to_edit(null, true) ||
    api_is_course_tutor() ||
    api_is_session_admin() ||
    api_is_drh() ||
    api_is_student_boss();

$allowedToTrackUser =
    api_is_platform_admin(true, true) ||
    api_is_allowed_to_edit(null, true) ||
    api_is_session_admin() ||
    api_is_drh() ||
    api_is_student_boss() ||
    api_is_course_admin() ||
    api_is_teacher()
;

if (false === $allowedToTrackUser && null !== $course) {
    if (empty($sessionId)) {
        $isTeacher = CourseManager::isCourseTeacher(
            api_get_user_id(),
            $course->getId()
        );

        if ($isTeacher) {
            $allowedToTrackUser = true;
        } else {
            // Check if the user is tutor of the course
            $userCourseStatus = CourseManager::get_tutor_in_course_status(
                api_get_user_id(),
                $course->getId()
            );

            if (1 === $userCourseStatus) {
                $allowedToTrackUser = true;
            }
        }
    } else {
        $coach = api_is_coach($sessionId, $course->getId());

        if ($coach) {
            $allowedToTrackUser = true;
        }
    }
}

if (!$allowedToTrackUser) {
    api_not_allowed(true);
}
if (empty($studentId)) {
    api_not_allowed(true);
}

$user = api_get_user_entity($studentId);
if (null === $user) {
    api_not_allowed(true);
}

$completeName = UserManager::formatUserFullName($user);

if ($export) {
    ob_start();
}
$codePath = api_get_path(WEB_CODE_PATH);
$csv_content = [];
$from_myspace = false;
$this_section = SECTION_COURSES;
if (isset($_GET['from']) && 'myspace' === $_GET['from']) {
    $from_myspace = true;
    $this_section = SECTION_TRACKING;
}

$nameTools = get_lang('Learner details');

if (!empty($details)) {
    if ('user_course' === $origin) {
        if (empty($cidReq)) {
            $interbreadcrumb[] = [
                'url' => api_get_course_url($course->getId()),
                'name' => $course->getTitle(),
            ];
        }
        $interbreadcrumb[] = [
            'url' => '../user/user.php?cid='.$courseId,
            'name' => get_lang('Users'),
        ];
    } else {
        if ('tracking_course' === $origin) {
            $interbreadcrumb[] = [
                'url' => '../tracking/courseLog.php?cid='.$courseId.'&sid='.api_get_session_id(),
                'name' => get_lang('Reporting'),
            ];
        } else {
            if ('resume_session' === $origin) {
                $interbreadcrumb[] = [
                    'url' => '../session/session_list.php',
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
                        'url' => 'myStudents.php?student='.$studentId.'&id_coach='.$coachId,
                        'name' => get_lang('Learner details'),
                    ];
                } else {
                    $interbreadcrumb[] = [
                        'url' => 'student.php',
                        'name' => get_lang('My learners'),
                    ];
                    $interbreadcrumb[] = [
                        'url' => 'myStudents.php?student='.$studentId,
                        'name' => get_lang('Learner details'),
                    ];
                }
            }
        }
    }
    $nameTools = get_lang('Learner details in course');
} else {
    if ('resume_session' === $origin) {
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
    } elseif ('teacher_details' === $origin) {
        $this_section = SECTION_TRACKING;
        $interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('Reporting')];
        $interbreadcrumb[] = ['url' => 'teachers.php', 'name' => get_lang('Trainers')];
        $nameTools = $completeName;
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

switch ($action) {
    case 'add_work_time':
        if (false === $workingTimeEdit) {
            api_not_allowed(true);
        }
        $workingTime = $_GET['time'] ?? '';
        $workId = $_GET['work_id'] ?? '';
        Event::eventAddVirtualCourseTime($courseId, $studentId, $sessionId, $workingTime, $workId);
        Display::addFlash(Display::return_message(get_lang('Updated')));

        header('Location: '.$currentUrl);
        exit;
    case 'remove_work_time':
        if (false === $workingTimeEdit) {
            api_not_allowed(true);
        }
        $workingTime = $_GET['time'] ?? '';
        $workId = $_GET['work_id'] ?? '';
        Event::eventRemoveVirtualCourseTime($courseId, $studentId, $sessionId, $workingTime, $workId);

        Display::addFlash(Display::return_message(get_lang('Updated')));

        header('Location: '.$currentUrl);
        exit;
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
        $timeSpentPerCourse = [];
        $progressPerCourse = [];
        foreach ($courses as $course) {
            $courseId = $course['c_id'];
            $courseTimeSpent = Tracking::get_time_spent_on_the_course($studentId, $courseId, $sessionToExport);
            $timeSpentPerCourse[$courseId] = $courseTimeSpent;
            $timeSpent += $courseTimeSpent;
            $sql = "SELECT DISTINCT count(course_access_id) as count
                    FROM $table
                    WHERE
                        c_id = $courseId AND
                        session_id = $sessionToExport AND
                        user_id = $studentId";
            $result = Database::query($sql);
            $row = Database::fetch_array($result);
            $numberVisits += $row['count'];
            $courseProgress = Tracking::get_avg_student_progress($studentId, $course['code'], [], api_get_session_entity($sessionToExport));
            $progressPerCourse[$courseId] = $courseProgress;
            $progress += $courseProgress;
        }

        $average = round($progress / count($courses), 1);
        $average = empty($average) ? '0%' : $average.'%';

        $first = Tracking::get_first_connection_date($studentId);
        $last = Tracking::get_last_connection_date($studentId);

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
                $course = api_get_course_entity($courseId);
                $courseId = $course->getId();
                $courseCodeItem = $course->getCode();

                $isSubscribed = CourseManager::is_user_subscribed_in_course(
                    $studentId,
                    $courseCodeItem,
                    true,
                    $sId
                );

                if ($isSubscribed) {
                    $timeInSeconds = $timeSpentPerCourse[$courseId];
                    $totalCourseTime += $timeInSeconds;
                    $time_spent_on_course = api_time_to_hms($timeInSeconds);
                    $progress = $progressPerCourse[$courseId];
                    $totalProgress += $progress;

                    $bestScore = Tracking::get_avg_student_score(
                        $studentId,
                        $course,
                        [],
                        api_get_session_entity($sId),
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
                        <td>
                            <a href="'.api_get_course_url($courseId, $sId).'">'.
                        $course->getTitle().'</a>
                        </td>
                        <td>'.$time_spent_on_course.'</td>
                        <td>'.$progress.'</td>
                        <td>'.$score.'</td>';
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

        $tpl = new Template('', false, false, false, true, false, false);
        $tpl->assign('title', get_lang('Attestation of attendance'));
        $tpl->assign('session_title', $sessionInfo['name']);
        $tpl->assign('student', $completeName);
        $tpl->assign('table_progress', $table->toHtml());
        $tpl->assign(
            'subtitle',
            sprintf(
                get_lang('In session %s, you had the following results'),
                $sessionInfo['name']
            )
        );
        $tpl->assign('table_course', $courseTable);
        $content = $tpl->fetch($tpl->get_template('my_space/pdf_export_student.tpl'));

        $userInfo = api_get_user_info($studentId);
        $params = [
            'pdf_title' => get_lang('Resume'),
            'session_info' => $sessionInfo,
            'course_info' => '',
            'pdf_date' => '',
            'student_info' => $userInfo,
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
                null,
                '',
                null,
                'D',
                false,
                null,
                false,
                true,
                false
            );
        } catch (\Mpdf\MpdfException $e) {
            error_log($e);
        }
        exit;
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

            $fileName = 'report'.$title.'_'.$completeName;
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
    case 'delete_message':
        $messageId = $_REQUEST['message_id'] ?? 0;
        $currentUser = api_get_current_user();
        if (!empty($messageId)) {
            $messageRepo = Container::getMessageRepository();
            $message = $messageRepo->find($messageId);

            // Only delete a message I created.
            if (null !== $message && $message->getSender()->getId() === $currentUser->getId()) {
                Event::addEvent(
                    LOG_MESSAGE_DELETE,
                    LOG_MESSAGE_DATA,
                    $messageId.' - '.$message->getTitle(),
                );
                $messageRepo->delete($message);
                Display::addFlash(Display::return_message(get_lang('Message deleted')));
            }
        }

        api_location($currentUrl);
        break;
    case 'send_message':
        if ($allowMessages) {
            $subject = $_POST['subject'] ?? '';
            $content = $_POST['message'] ?? '';
            $currentUser = api_get_user_entity();
            $student = api_get_user_entity($studentId);

            // @todo move in a repo
            if (!empty($subject) && !empty($content)) {
                $em = Database::getManager();
                $message = (new Message())
                    ->setTitle($subject)
                    ->setContent($content)
                    ->setSender(api_get_user_entity())
                    ->addReceiverTo($student)
                    ->setMsgType(Message::MESSAGE_TYPE_CONVERSATION)
                ;
                $em->persist($message);
                $em->flush();

                $senderName = UserManager::formatUserFullName($currentUser);
                $emailAdmin = api_get_setting('admin.administrator_email');

                // Send also message to all student bosses
                $bossList = UserManager::getStudentBossList($studentId);

                if (!empty($bossList)) {
                    $url = api_get_path(WEB_CODE_PATH).'my_space/myStudents.php?student='.$studentId;
                    $link = Display::url($url, $url);

                    foreach ($bossList as $boss) {
                        $studentFullName = UserManager::formatUserFullName($student);
                        $contentBoss = sprintf(
                            get_lang('Hi,<br/><br/>User %s sent a follow up message about student %s.<br/><br/>The message can be seen here %s'),
                            UserManager::formatUserFullName($currentUser),
                            $studentFullName,
                            $link
                        );
                        $messageBoss = (new Message())
                            ->setTitle(sprintf(get_lang('Follow up message about student %s'), $studentFullName))
                            ->setContent($contentBoss)
                            ->setSender(api_get_user_entity())
                            ->addReceiverTo(api_get_user_entity($boss['boss_id']))
                            ->setMsgType(Message::MESSAGE_TYPE_INBOX)
                        ;
                        $em->persist($messageBoss);

                        api_mail_html(
                            UserManager::formatUserFullName(api_get_user_entity($boss['boss_id'])),
                            api_get_user_entity($boss['boss_id'])->getEmail(),
                            sprintf(get_lang('Follow up message about student %s'), $studentFullName),
                            $contentBoss,
                            $senderName,
                            $emailAdmin
                        );
                    }

                    $em->flush();
                }

                Display::addFlash(Display::return_message(get_lang('Message Sent')));
            } else {
                Display::addFlash(Display::return_message(get_lang('all fields required'), 'warning'));
            }

            header('Location: '.$currentUrl);
            exit;
        }

        break;
    case 'generate_certificate':
        $gradebookCertificateRepo = Container::getGradeBookCertificateRepository();
        $gradebookCertificateRepo->deleteCertificateAndRelatedFiles($studentId, 0);
        $certificate = new Certificate(0, $studentId);
        $certificate->generatePdfFromCustomCertificate();
        exit;
    case 'send_legal':
        $isBoss = UserManager::userIsBossOfStudent(api_get_user_id(), $studentId);
        if ($isBoss || api_is_platform_admin()) {
            LegalManager::sendLegal($studentId, api_get_user_id());
        }
        break;
    case 'delete_legal':
        $isBoss = UserManager::userIsBossOfStudent(api_get_user_id(), $studentId);
        if ($isBoss || api_is_platform_admin()) {
            $extraFieldValue = new ExtraFieldValue('user');
            $value = $extraFieldValue->get_values_by_handler_and_field_variable(
                $studentId,
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
            !empty($studentId) &&
            api_is_allowed_to_edit() &&
            Security::check_token('get')
        ) {
            Event::delete_student_lp_events(
                $studentId,
                $lp_id,
                ['real_id' => $courseId],
                $sessionId
            );

            // @todo delete the stats.track_e_exercises records.
            // First implement this http://support.chamilo.org/issues/1334
            Display::addFlash(Display::return_message(get_lang('Learning path was reset for the learner'), 'success'));
            Security::clear_token();
        }
        break;
    case 'all_attendance':
        /* Display all attendances */
        $startDate = new DateTime();
        $startDate = $startDate->modify('-1 week');
        if (isset($_GET['startDate'])) {
            $startDate = new DateTime($_GET['startDate']);
        }
        $startDate = $startDate->setTime(0, 0, 0);

        $endDate = new DateTime();
        if (isset($_GET['endDate'])) {
            $endDate = new DateTime($_GET['endDate']);
        }
        $endDate = $endDate->setTime(23, 59, 0);

        if ($startDate > $endDate) {
            $dataTemp = $startDate;
            $startDate = $endDate;
            $endDate = $dataTemp;
        }
        $startDateText = api_get_local_time($startDate);
        $endDateText = api_get_local_time($endDate);

        $defaults['startDate'] = $startDateText;
        $defaults['endDate'] = $endDateText;
        $form = new FormValidator(
            'all_attendance_list',
            'GET',
            'myStudents.php?action=all_attendance&student='.$studentId.'&startDate='.$defaults['startDate'].'&endDate='.$defaults['endDate'].'&'.api_get_cidreq()
        );
        $form->addElement('html', '<input type="hidden" name="student" value="'.$studentId.'" >');
        $form->addElement('html', '<input type="hidden" name="action" value="all_attendance" >');

        $form->addDateTimePicker(
            'startDate',
            [
                get_lang('Start date'),
            ],
            [
                'form_name' => 'attendance_calendar_edit',
            ],
            5
        );
        $form->addDateTimePicker(
            'endDate',
            [
                get_lang('End date'),
            ],
            [
                'form_name' => 'attendance_calendar_edit',
            ],
            5
        );

        $form->addButtonSave(get_lang('Submit'));
        $form->setDefaults($defaults);

        Display::display_header($nameTools);
        $form->display();

        $attendance = new Attendance();
        $data = $attendance->getCoursesWithAttendance($studentId, $startDate, $endDate);

        $title = sprintf(get_lang('Attendance from %s to %s'), $startDateText, $endDateText);
        echo '<h3>'.$title.'</h3>';
        echo '<div class="">
        <table class="table table-striped table-hover table-responsive">
            <thead>
                <tr>
                    <th>'.get_lang('Date').'</th>
                    <th>'.get_lang('Course').'</th>
                    <th>'.get_lang('Present').'</th>
                </tr>
            </thead>
        <tbody>';

        foreach ($data as $attendanceData => $attendanceSheet) {
            $totalAttendance = count($attendanceSheet);
            for ($i = 0; $i < $totalAttendance; $i++) {
                $attendanceWork = $attendanceSheet[$i];
                $courseInfoItem = api_get_course_info_by_id($attendanceWork['courseId']);
                $date = api_get_local_time($attendanceWork[1]);
                $sId = $attendanceWork['session'];
                $printSession = '';
                if (0 != $sId) {
                    $printSession = '('.
                        $attendanceWork['sessionName'].')';
                }
                echo '
                <tr>
                    <td>'.$date.'</td>
                    <td>'
                    .'<a
                        title="'.get_lang('Go to attendances').'"
                        href="'.api_get_path(WEB_CODE_PATH).
                    'attendance/index.php?cid='.$attendanceWork['courseId'].'&sid='.$sId.'&student_id='.$studentId.'">'
                    .$attendanceWork['courseTitle'].' '.$printSession.'
                        </a>
                    </td>
                    <td>'.$attendanceWork['presence'].'</td>
                </tr>';
            }
        }
        echo '</tbody>
        </table></div>';
        Display::display_footer();
        exit();
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

// DRH or session admin
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
            user_id = ".$studentId;
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
        WHERE user_id='.$studentId;
$rs = Database::query($sql);
$tmp_sessions = [];
while ($row = Database::fetch_assoc($rs)) {
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

$isDrhOfCourse = CourseManager::isUserSubscribedInCourseAsDrh(api_get_user_id(), ['real_id' => $courseId]);

if (api_is_drh() && !api_is_platform_admin()) {
    if (!empty($studentId)) {
        if (!api_drh_can_access_all_session_content()) {
            if (!$isDrhOfCourse) {
                if (api_is_drh() &&
                    !UserManager::is_user_followed_by_drh($studentId, api_get_user_id())
                ) {
                    api_not_allowed(true);
                }
            }
        }
    }
}

$pluginCalendar = 'true' === api_get_plugin_setting('learning_calendar', 'enabled');

if ($pluginCalendar) {
    $plugin = LearningCalendarPlugin::create();
    $plugin->setJavaScript();
}

Display::display_header($nameTools);

// Local layout styles for learner detail page
echo '<style>
.skills-badges .skill-badge-wrapper .item > a,
.skills-badges .skill-badge-wrapper .caption > a {
  display: inline-block;
  width: auto;
}
#skillList {
    background: transparent !important;
    border: 0 !important;
    box-shadow: none !important;
    padding: 0 !important;
    margin: 0 !important;
}
.skills-badges.skills-badges--cards {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 1.25rem;
    margin-top: 1rem;
}
.skills-badges.skills-badges--cards .skill-badge-wrapper {
    border: 1px solid #e5e7eb; /* gray-200 */
    border-radius: 1rem;
    background: #fff;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
    padding: 1.25rem 1rem;
    text-align: center;
    transition: box-shadow 0.15s ease, transform 0.15s ease;
}
.skills-badges.skills-badges--cards .skill-badge-wrapper:hover {
    box-shadow: 0 8px 18px rgba(15, 23, 42, 0.10);
    transform: translateY(-1px);
}
.skills-badges.skills-badges--cards .skill-badge-wrapper .item {
    margin: 0 auto;
    width: 96px;
    height: 96px;
    border-radius: 1rem;
    background: #f9fafb;
    border: 1px solid #f3f4f6;
    display: flex;
    align-items: center;
    justify-content: center;
}
.skills-badges.skills-badges--cards .skill-badge-wrapper .item img {
    width: 64px !important;
    height: 64px !important;
    object-fit: contain;
}
.skills-badges.skills-badges--cards .skill-badge-wrapper .caption {
    margin-top: 0.75rem;
    font-weight: 600;
    color: #111827;
    font-size: 0.95rem;
}
.skills-badges.skills-badges--cards .skill-badge-wrapper .skill-badge-action {
    margin-top: 0.25rem;
    font-size: 0.8125rem;
    font-weight: 500;
    color: #2563eb;
}
.skills-badges.skills-badges--cards .skill-badge-wrapper .skill-badge-action a {
    color: inherit;
    text-decoration: none;
}
.skills-badges.skills-badges--cards .skill-badge-wrapper:hover .skill-badge-action a {
    text-decoration: underline;
}
/* Main container */
.learner-page-container {
    max-width: 100%;
    margin: 0 auto;
    padding: 0 1.5rem 2.5rem;
}

/* Toolbar wrapper */
.learner-toolbar-wrapper {
    margin-bottom: 1.5rem;
}

/* Generic card for sections */
.learner-card {
    background-color: #ffffff;
    border-radius: 0.75rem;
    border: 1px solid #e5e7eb; /* gray-200 */
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
    padding: 1rem 1rem;
}
.learner-section-spacing {
    margin-top: 1.75rem;
}
.learner-card h1,
.learner-card h2,
.learner-card h3 {
    margin-top: 0;
    margin-bottom: 0.75rem;
    font-weight: 600;
    font-size: 1.1rem;
}
.learner-section-title {
    font-size: 1rem;
    font-weight: 600;
    color: #111827;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.learner-subtitle {
    font-size: 0.875rem;
    color: #6b7280;
    margin-top: 0.25rem;
}
.learner-table-card {
    margin-top: 0.75rem;
    background-color: #ffffff;
    border-radius: 0.75rem;
    border: 1px solid #e5e7eb;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
    padding: 0.75rem 1rem 1.25rem;
}
.learner-table-wrapper {
    overflow-x: auto;
}
.learner-table-card .table {
    margin-bottom: 0;
}
.learner-messages-header {
    margin-top: 2rem;
}
.learner-page-container h3 {
    margin-top: 1.25rem;
    margin-bottom: 0.75rem;
}
</style>';

$token = Security::get_token();

// Start main page container
echo '<div class="learner-page-container">';

// Actions bar
$actions = '<a href="javascript: window.history.go(-1);">'
    .Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Back')).'</a>';

$actions .= '<a href="javascript: void(0);" onclick="javascript: window.print();">'
    .Display::getMdiIcon(ActionIcon::PRINT, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Print')).'</a>';

$actions .= '<a href="'.api_get_self().'?'.Security::remove_XSS($_SERVER['QUERY_STRING']).'&export=csv">'
    .Display::getMdiIcon(ActionIcon::EXPORT_CSV, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('CSV export')).'</a> ';

$actions .= '<a href="'.api_get_self().'?'.Security::remove_XSS($_SERVER['QUERY_STRING']).'&export=xls">'
    .Display::getMdiIcon(ActionIcon::EXPORT_SPREADSHEET, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Excel export')).'</a> ';

$actions .= Display::url(
    Display::getMdiIcon(ObjectIcon::ATTENDANCE, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Access details')),
    api_get_path(WEB_CODE_PATH).'my_space/access_details_session.php?user_id='.$studentId
);
$email = $user->getEmail();
if (!empty($email)) {
    $send_mail = '<a href="mailto:'.$email.'">'.
        Display::getMdiIcon(ActionIcon::SEND_MESSAGE, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Send message by e-mail')).'</a>';
} else {
    $send_mail = Display::getMdiIcon(ActionIcon::SEND_MESSAGE, 'ch-tool-icon-disabled', null, ICON_SIZE_MEDIUM, get_lang('Send message by e-mail'));
}
$actions .= $send_mail;
if (!empty($studentId) && !empty($courseCode)) {
    // Only show link to connection details if the course and student were defined in the URL
    $actions .= '<a href="access_details.php?student='.$studentId.'&course='.$courseCode.'&origin='.$origin.'&cid='
        .$courseId.'&id_session='.$sessionId.'">'
        .Display::getMdiIcon(ToolIcon::TRACKING, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Access details'))
        .'</a>';
}

$notebookTeacherEnable = 'true' === api_get_plugin_setting('notebookteacher', 'enable_plugin_notebookteacher');
if ($notebookTeacherEnable && !empty($studentId) && !empty($courseCode)) {
    // link notebookteacher
    $optionsLink = 'student_id='.$studentId.'&origin='.$origin.'&cid='.$courseId.'&id_session='.$sessionId;
    $actions .= '<a href="'.api_get_path(WEB_PLUGIN_PATH).'NotebookTeacher/src/index.php?'.$optionsLink.'">'
        .Display::getMdiIcon(ToolIcon::NOTEBOOK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Notebook'))
        .'</a>';
}

if (api_can_login_as($studentId)) {
    $actions .= '<a href="'.api_get_path(WEB_CODE_PATH).'admin/user_list.php?action=login_as&user_id='.$studentId
        .'&sec_token='.$token.'">'
        .Display::getMdiIcon(ActionIcon::LOGIN_AS, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Login as')).'</a>&nbsp;&nbsp;';
}

if (SkillModel::isAllowed($studentId, false)) {
    $actions .= Display::url(
        Display::getMdiIcon(ObjectIcon::BADGE, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Assign skill')),
        api_get_path(WEB_CODE_PATH).'skills/assign.php?'.http_build_query(['user' => $studentId])
    );
}

if (SkillModel::isAllowed($studentId, false)) {
    $actions .= Display::url(
        Display::getMdiIcon(ObjectIcon::ATTENDANCE, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('# attended')),
        api_get_path(WEB_CODE_PATH).'my_space/myStudents.php?action=all_attendance&student='.$studentId
    );
}

$permissions = StudentFollowUpPlugin::getPermissions(
    $studentId,
    api_get_user_id()
);

$isAllow = $permissions['is_allow'];
if ($isAllow) {
    $actions .= Display::url(
        Display::getMdiIcon(ToolIcon::BLOG, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Blog')),
        api_get_path(WEB_PLUGIN_PATH).'StudentFollowUp/posts.php?student_id='.$studentId
    );
}

// Toolbar inside its own wrapper for spacing
echo '<div class="learner-toolbar-wrapper">';
echo Display::toolbarAction('my_students', [$actions]);
echo '</div>';

// is the user online ?
$online = get_lang('No');
if (user_is_online($studentId)) {
    $online = get_lang('Yes');
}

// get average of score and average of progress by student
$avg_student_progress = $avg_student_score = 0;

if (empty($sessionId)) {
    $isSubscribedToCourse = CourseManager::is_user_subscribed_in_course($studentId, $courseCode);
} else {
    $isSubscribedToCourse = CourseManager::is_user_subscribed_in_course(
        $studentId,
        $courseCode,
        true,
        $sessionId
    );
}

if ($isSubscribedToCourse) {
    $avg_student_progress = Tracking::get_avg_student_progress(
        $studentId,
        $course,
        [],
        $session
    );

    // the score inside the Reporting table
    $avg_student_score = Tracking::get_avg_student_score(
        $studentId,
        $course,
        [],
        $session
    );
}

$avg_student_progress = round($avg_student_progress, 2);
$time_spent_on_the_course = 0;
if (null !== $course) {
    $time_spent_on_the_course = api_time_to_hms(
        Tracking::get_time_spent_on_the_course(
            $studentId,
            $courseId,
            $sessionId
        )
    );
}

// get information about connections on the platform by student
$first_connection_date = Tracking::get_first_connection_date($studentId);
if ('' == $first_connection_date) {
    $first_connection_date = get_lang('No connection');
}

$last_connection_date = Tracking::get_last_connection_date(
    $studentId,
    true
);
if ('' == $last_connection_date) {
    $last_connection_date = get_lang('No connection');
}

// cvs information
$csv_content[] = [
    get_lang('Information'),
];
$csv_content[] = [
    get_lang('Name'),
    get_lang('E-mail'),
    get_lang('Tel'),
];
$csv_content[] = [
    $completeName,
    $user->getEmail(),
    $user->getPhone(),
];

$csv_content[] = [];

// csv tracking
$csv_content[] = [
    get_lang('Reporting'),
];
$csv_content[] = [
    get_lang('First login in platform'),
    get_lang('Latest login in platform'),
    get_lang('Time spent in the course'),
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
$userGroupManager = new UserGroupModel();
$userGroups = $userGroupManager->getNameListByUser(
    $studentId,
    Usergroup::NORMAL_CLASS
);

$userInfoExtra = [];
$userInfoExtra['groups'] = $userGroupManager;
$userInfoExtra['online'] = $online;

$bossList = [];
$studentBossList = Usermanager::getStudentBossList($studentId);
foreach ($studentBossList as $boss) {
    $bossInfo = api_get_user_info($boss['boss_id']);
    if ($bossInfo) {
        $bossList[] = $bossInfo['complete_name_with_username'];
    }
}
$userInfoExtra['boss_list'] = $bossList;

if (!empty($courseCode)) {
    $userInfoExtra['url_access'] = Display::url(
        get_lang('See accesses'),
        'access_details.php?'
        .http_build_query(
            [
                'student' => $studentId,
                'course' => $courseCode,
                'origin' => $origin,
                'cid' => $courseId,
                'sid' => $sessionId,
            ]
        ),
        ['class' => 'btn btn--plain']
    );
}

// Display timezone if the user selected one and if the admin allows the use of user's timezone
$timezone = null;
$timezone_user = $user->getTimezone();
$use_users_timezone = api_get_setting('use_users_timezone', 'timezones');

if (null != $timezone_user && 'true' === $use_users_timezone) {
    $timezone = $timezone_user;
}
if (null !== $timezone) {
    $userInfoExtra['timezone'] = $timezone;
}

if (is_numeric($avg_student_score)) {
    $score = $avg_student_score.'%';
} else {
    $score = $avg_student_score;
}

$userInfoExtra['student_score'] = (float) $score;
$userInfoExtra['student_progress'] = $avg_student_progress;
$userInfoExtra['first_connection'] = $first_connection_date;
$userInfoExtra['last_connection'] = $last_connection_date;
$userInfoExtra['last_connection_in_course'] = api_format_date(
    Tracking::getLastConnectionInAnyCourse($studentId),
    DATE_FORMAT_SHORT
);
if ('true' === $details) {
    $userInfoExtra['time_spent_course'] = $time_spent_on_the_course;
}

$icon = '';
$timeLegalAccept = '';
$btn = '';
if ('true' === api_get_setting('allow_terms_conditions')) {
    $isBoss = UserManager::userIsBossOfStudent(api_get_user_id(), $studentId);
    if ($isBoss || api_is_platform_admin()) {
        $extraFieldValue = new ExtraFieldValue('user');
        $value = $extraFieldValue->get_values_by_handler_and_field_variable(
            $studentId,
            'legal_accept'
        );
        $icon = Display::getMdiIcon(StateIcon::COMPLETE, 'ch-tool-icon-disabled', null, ICON_SIZE_SMALL);
        $legalTime = null;

        if (isset($value['value']) && !empty($value['value'])) {
            [$legalId, $legalLanguageId, $legalTime] = explode(':', $value['value']);
            $icon = Display::getMdiIcon(StateIcon::COMPLETE, 'ch-tool-icon', null, ICON_SIZE_SMALL);
            $btn = Display::url(
                get_lang('Delete legal agreement'),
                api_get_self().'?action=delete_legal&student='.$studentId.'&course='.$courseCode,
                ['class' => 'btn btn--danger']
            );
            $timeLegalAccept = api_get_local_time($legalTime);
        } else {
            $btn = Display::url(
                get_lang('Send legal agreement'),
                api_get_self().'?action=send_legal&student='.$studentId.'&course='.$courseCode,
                ['class' => 'btn btn--primary']
            );
            $timeLegalAccept = get_lang('Not Registered');
        }
    }
    $userInfoExtra['legal'] = [
        'label' => get_lang('Legal accepted').$icon,
        'datetime' => $timeLegalAccept,
        'url_send' => $btn,
        'icon' => $icon,
    ];
}
$iconCertificate = ' '.Display::url(
        get_lang('Generate'),
        api_get_self().'?action=generate_certificate&student='.$studentId.'&cid='.$courseId.'&course='.$courseCode,
        ['class' => 'btn btn--primary btn-xs']
    );
$userInfoExtra['certificate'] = [
    'label' => get_lang('Certificate'),
    'content' => $iconCertificate,
];

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

if (null !== $course) {
    $session = api_get_session_entity($sessionId);
    $nb_assignments = Container::getStudentPublicationRepository()->countUserPublications(
        $user,
        $course,
        $session
    );
    $messages = Container::getForumPostRepository()->countUserForumPosts($user, $course, $session);
    $links = Tracking::count_student_visited_links($studentId, $course->getId(), $sessionId);
    $chat_last_connection = Tracking::chat_last_connection($studentId, $course->getId(), $sessionId);
    $documents = Tracking::countStudentDownloadedDocuments($studentId, $course->getId(), $sessionId);
    $uploaded_documents = Tracking::count_student_uploaded_documents($studentId, $course->getCode(), $sessionId);

    $tpl->assign('title', $course->getTitle());

    $userInfoExtra['tools'] = [
        'tasks' => $nb_assignments,
        'messages' => $messages,
        'links' => $links,
        'chat_connection' => $chat_last_connection,
        'documents' => $documents,
        'upload_documents' => $uploaded_documents,
        'course_first_access' => Tracking::get_first_connection_date_on_the_course(
            $studentId,
            $course->getId(),
            $sessionId
        ),
        'course_last_access' => Tracking::get_last_connection_date_on_the_course(
            $studentId,
            ['real_id' => $course->getId()],
            $sessionId
        ),
        'count_access_dates' => Tracking::getNumberOfCourseAccessDates($studentId, $course->getId(), $sessionId),
    ];
} else {
    $details = false;
}
$tpl->assign('user_extra', $userInfoExtra);
$tpl->assign('user', $user);
$tpl->assign('details', $details);
$templateName = $tpl->get_template('my_space/user_details.tpl');
$content = $tpl->fetch($templateName);

// Wrap user header/content into a card to avoid huge white space and give structure
echo '<section class="learner-card" id="infosStudent">';
echo $content;
echo '</section>';

// ----- Skills + classes card -----
echo '<section class="learner-card learner-section-spacing">';

$allowAll = ('true' === api_get_setting('skill.allow_teacher_access_student_skills'));
if ($allowAll) {
    // Show all skills
    echo Tracking::displayUserSkills(
        $studentId,
        0,
        0,
        true
    );
} else {
    // Default behaviour - Show all skills depending the course and session id
    echo Tracking::displayUserSkills(
        $studentId,
        $courseId,
        $sessionId
    );
}

// User classes table with nicer spacing
if (!empty($userGroups)) {
    echo '<div class="learner-section-spacing">';
    echo '<h3 class="learner-section-title">'.get_lang('Classes').'</h3>';
    echo '<div class="learner-table-card"><div class="learner-table-wrapper">';
    echo '<table class="table table-striped table-hover">
           <thead>
            <tr>
                <th>'.get_lang('Classes').'</th>
            </tr>
            </thead>
            <tbody>';
    foreach ($userGroups as $class) {
        echo '<tr><td>'.$class.'</td></tr>';
    }
    echo '</tbody></table>';
    echo '</div></div>';
}

echo '</section>';

// ----- Courses / sessions or LP/tests depending on $details -----
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
        $title = Display::getMdiIcon(ObjectIcon::COURSE, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Courses')).' '.get_lang('Courses');

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
            $title = Display::getMdiIcon(ObjectIcon::SESSION, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Session'))
                .' '.$session_name.($date_session ? ' ('.$date_session.')' : '');
        }

        echo '<section class="learner-section-spacing">';
        echo '<h3 class="learner-section-title">'.$title.'</h3>';

        echo '<div class="learner-table-card"><div class="learner-table-wrapper">';
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

            $session = api_get_session_entity($sId);
            foreach ($courses as $courseId) {
                $course = api_get_course_entity($courseId);
                $courseId = $course->getId();
                $courseCodeItem = $course->getCode();

                if (empty($session_info)) {
                    $isSubscribed = CourseManager::is_user_subscribed_in_course(
                        $studentId,
                        $courseCodeItem
                    );
                } else {
                    $isSubscribed = CourseManager::is_user_subscribed_in_course(
                        $studentId,
                        $courseCodeItem,
                        true,
                        $sId
                    );
                }

                if ($isSubscribed) {
                    $timeInSeconds = Tracking::get_time_spent_on_the_course(
                        $studentId,
                        $courseId,
                        $sId
                    );
                    $totalCourseTime += $timeInSeconds;
                    $time_spent_on_course = api_time_to_hms($timeInSeconds);

                    // get average of faults in attendances by student
                    $results_faults_avg = $attendance->get_faults_average_by_course(
                        $studentId,
                        $course,
                        $session
                    );

                    $attendances_faults_avg = '0/0 (0%)';
                    if (!empty($results_faults_avg['total'])) {
                        if (api_is_drh()) {
                            $attendances_faults_avg = Display::url(
                                $results_faults_avg['faults'].'/'.$results_faults_avg['total']
                                .' ('.$results_faults_avg['percent'].'%)',
                                api_get_path(WEB_CODE_PATH)
                                .'attendance/index.php?cid='.$courseId.'&sid='.$sId.'&student_id='
                                .$studentId,
                                ['title' => get_lang('Go to attendances')]
                            );
                        } else {
                            $attendances_faults_avg = $results_faults_avg['faults'].'/'
                                .$results_faults_avg['total']
                                .' ('.$results_faults_avg['percent'].'%)';
                        }
                        $totalAttendance[0] += $results_faults_avg['faults'];
                        $totalAttendance[1] += $results_faults_avg['total'];
                    }

                    // Get evaluations by student
                    $cats = Category::load(
                        null,
                        null,
                        $courseId,
                        null,
                        null,
                        $sId
                    );

                    $scoretotal = [];
                    if (isset($cats) && isset($cats[0])) {
                        if (!empty($sId)) {
                            $scoretotal = $cats[0]->calc_score($studentId, null, $courseId, $sId);
                        } else {
                            $scoretotal = $cats[0]->calc_score($studentId, null, $courseId);
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
                        $studentId,
                        $course,
                        [],
                        $session
                    );

                    $totalProgress += $progress;

                    $score = Tracking::get_avg_student_score(
                        $studentId,
                        $course,
                        [],
                        $session
                    );

                    if (is_numeric($score)) {
                        $totalScore += $score;
                    }

                    $progress = empty($progress) ? '0%' : $progress.'%';
                    $score = empty($score) ? '0%' : $score.'%';

                    $csvRow = [
                        $session_name,
                        $course->getTitle(),
                        $time_spent_on_course,
                        $progress,
                        $score,
                        $attendances_faults_avg,
                        $scoretotal_display,
                    ];

                    $csv_content[] = $csvRow;
                    $exportCourseList[$sId][] = $csvRow;

                    echo '<tr>
                    <td>
                        <a href="'.api_get_course_url($courseId, $sId).'">'.
                        $course->getTitle().'
                        </a>
                    </td>
                    <td>'.$time_spent_on_course.'</td>
                    <td>'.$progress.'</td>
                    <td>'.$score.'</td>
                    <td>'.$attendances_faults_avg.'</td>
                    <td>'.$scoretotal_display.'</td>';

                    if (!empty($coachId)) {
                        echo '<td><a href="'.api_get_self().'?student='.$studentId
                            .'&details=true&cid='.$courseId.'&id_coach='.$coachId.'&origin='.$origin
                            .'&sid='.$sId.'#infosStudent">'
                            .Display::getMdiIcon(ActionIcon::VIEW_DETAILS, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Details')).'</a></td>';
                    } else {
                        echo '<td><a href="'.api_get_self().'?student='.$studentId
                            .'&details=true&cid='.$courseId.'&origin='.$origin.'&sid='.$sId.'#infosStudent">'
                            .Display::getMdiIcon(ActionIcon::VIEW_DETAILS, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Details')).'</a></td>';
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
                Display::getMdiIcon(ActionIcon::EXPORT_CSV, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('CSV export')),
                $currentUrl.'&'
                .http_build_query(
                    [
                        'action' => 'export_one_session_row',
                        'export' => 'csv',
                        'session_to_export' => $sId,
                    ]
                )
            );
            $sessionAction .= Display::url(
                Display::getMdiIcon(ActionIcon::EXPORT_SPREADSHEET, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Export to XLS')),
                $currentUrl.'&'
                .http_build_query(
                    [
                        'action' => 'export_one_session_row',
                        'export' => 'xls',
                        'session_to_export' => $sId,
                    ]
                )
            );

            if (!empty($sId)) {
                $sessionAction .= Display::url(
                    Display::getMdiIcon(ActionIcon::EXPORT_PDF, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Export to PDF')),
                    api_get_path(WEB_CODE_PATH).'my_space/session.php?'
                    .http_build_query(
                        [
                            'student' => $studentId,
                            'action' => 'export_to_pdf',
                            'type' => 'attendance',
                            'session_to_export' => $sId,
                        ]
                    )
                );
                $sessionAction .= Display::url(
                    Display::getMdiIcon(ObjectIcon::CERTIFICATE, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Certificate of achievement')),
                    api_get_path(WEB_CODE_PATH).'my_space/session.php?'
                    .http_build_query(
                        [
                            'student' => $studentId,
                            'action' => 'export_to_pdf',
                            'type' => 'achievement',
                            'session_to_export' => $sId,
                        ]
                    )
                );
            }
            echo '</tbody></table></div>'; // table wrapper + card
            Session::write('export_course_list', $exportCourseList);

            echo Display::toolbarAction('sessions', [$sessionAction]);
        } else {
            echo "<tr><td colspan='5'>".get_lang('This course could not be found')."</td></tr>";
            echo '</tbody></table></div>';
        }

        echo '</section>';
    }
} else {
    // Details == true: LP, quizzes, tasks, etc. in one big card with internal tables.
    echo '<section class="learner-section-spacing"><div class="learner-card">';

    $columnHeaders = [
        'lp' => get_lang('Learning paths'),
        'time' => get_lang('Time').
            Display::getMdiIcon(
                ActionIcon::INFORMATION,
                'ch-tool-icon',
                'align: absmiddle; hspace: 3px',
                ICON_SIZE_SMALL,
                get_lang('Total time in course')
            ),
        'best_score' => get_lang('Best score'),
        'latest_attempt_avg_score' => get_lang('Latest attempt average score').
            Display::getMdiIcon(
                ActionIcon::INFORMATION,
                'ch-tool-icon',
                'align: absmiddle; hspace: 3px',
                ICON_SIZE_SMALL,
                get_lang('Average is calculated based on the latest attempts')
            ),
        'progress' => get_lang('Progress').
            Display::getMdiIcon(
                ActionIcon::INFORMATION,
                'ch-tool-icon',
                'align: absmiddle; hspace: 3px',
                ICON_SIZE_SMALL,
                get_lang('% of learning objects visited')
            ),
        'last_connection' => get_lang('Latest login').
            Display::getMdiIcon(
                ActionIcon::INFORMATION,
                'ch-tool-icon',
                'align: absmiddle; hspace: 3px',
                ICON_SIZE_SMALL,
                get_lang('Last time learner entered the course')
            ),
    ];

    if (empty($courseId) && null !== $course) {
        $courseId = $course->getId();
    }

    $timeCourse = null;
    if (Tracking::minimumTimeAvailable($sessionId, $courseId)) {
        $timeCourse = Tracking::getCalculateTime($studentId, $courseId, $sessionId);
    }

    if (INVITEE != $user->getStatus()) {
        $csv_content[] = [];
        $csv_content[] = [str_replace('&nbsp;', '', strip_tags($completeName))];
        $trackingColumns = api_get_setting('session.tracking_columns', true);
        if (isset($trackingColumns['my_students_lp'])) {
            foreach ($columnHeaders as $key => $value) {
                if (!isset($trackingColumns['my_progress_lp'][$key]) ||
                    false == $trackingColumns['my_students_lp'][$key]
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

        $lpTrackingEvent = new MyStudentsLpTrackingEvent([], AbstractEvent::TYPE_PRE);

        Container::getEventDispatcher()->dispatch($lpTrackingEvent, Events::MY_STUDENTS_LP_TRACKING);

        foreach ($lpTrackingEvent->getHeaders() as $eventHeader) {
            $columnHeadersToExport[] = $eventHeader['title'];

            $headers .= Display::tag('th', $eventHeader['title'], $eventHeader['attrs']);
        }

        $csv_content[] = $columnHeadersToExport;
        $columnHeadersKeys = array_keys($columnHeaders);
        $categoriesTempList = learnpath::getCategories($courseId);
        $categoryTest = new CLpCategory();
        $categoryTest->setTitle(get_lang('Without category'));
        $categories = [
            $categoryTest,
        ];

        if (!empty($categoriesTempList)) {
            $categories = array_merge($categories, $categoriesTempList);
        }

        $userEntity = api_get_user_entity(api_get_user_id());
        $session = api_get_session_entity();

        /** @var CLpCategory $item */
        foreach ($categories as $item) {
            $categoryId = $item->getIid();
            if (!learnpath::categoryIsVisibleForStudent($item, $userEntity, $course, $session)) {
                continue;
            }

            $list = new LearnpathList(
                api_get_user_id(),
                ['real_id' => $courseId],
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
                echo Display::page_subheader2($item->getTitle());
            }

            echo '<div class="learner-table-card learner-section-spacing"><div class="learner-table-wrapper">';
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
                    $studentId,
                    $course,
                    [$lp_id],
                    $session
                );

                if (null === $progress) {
                    $progress = '0%';
                } else {
                    $any_result = true;
                }

                if (!empty($timeCourse)) {
                    $lpTime = isset($timeCourse[TOOL_LEARNPATH]) ? $timeCourse[TOOL_LEARNPATH] : 0;
                    $total_time = isset($lpTime[$lp_id]) ? (int) $lpTime[$lp_id] : 0;
                } else {
                    $total_time = Tracking::get_time_spent_in_lp(
                        $studentId,
                        $course,
                        [$lp_id],
                        $sessionId
                    );
                }

                if (!empty($total_time)) {
                    $any_result = true;
                }

                // Get last connection time in lp
                $start_time = Tracking::get_last_connection_time_in_lp(
                    $studentId,
                    $course->getCode(),
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
                    $studentId,
                    $course,
                    [$lp_id],
                    $session
                );

                // Latest exercise results in a LP
                $score_latest = Tracking::get_avg_student_score(
                    $studentId,
                    $course,
                    [$lp_id],
                    $session,
                    false,
                    true
                );

                $bestScore = Tracking::get_avg_student_score(
                    $studentId,
                    $course,
                    [$lp_id],
                    $session,
                    false,
                    false,
                    true
                );

                if (empty($bestScore)) {
                    $bestScore = '';
                } else {
                    $bestScore = $bestScore.'%';
                }

                $css_class = (0 == $i % 2) ? 'row_even' : 'row_odd';
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
                    $contentToExport[] = api_html_entity_decode(stripslashes($lp_name));
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
                    $contentToExport[] = $start_time;
                    echo Display::tag('td', $start_time);
                }

                $lpTrackingEvent = new MyStudentsLpTrackingEvent(
                    ['lp_id' => $lp_id, 'student_id' => $studentId],
                    AbstractEvent::TYPE_POST
                );

                Container::getEventDispatcher()->dispatch($lpTrackingEvent, Events::MY_STUDENTS_LP_TRACKING);

                foreach ($lpTrackingEvent->getContents() as $eventContent) {
                    if (isset($eventContent['value'])) {
                        $contentToExport[] = strip_tags($eventContent['value']);

                        echo Display::tag('td', $eventContent['value'], $eventContent['attrs']);
                    }
                }

                $csv_content[] = $contentToExport;

                if (true === $any_result) {
                    $from = '';
                    if ($from_myspace) {
                        $from = '&from=myspace';
                    }
                    $link = Display::url(
                        Display::getMdiIcon(ActionIcon::VIEW_DETAILS, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Details')),
                        $codePath.'my_space/lp_tracking.php?cid='.$courseId.'&course='.$courseCode.$from.'&origin='.$origin
                        .'&lp_id='.$lp_id.'&student_id='.$studentId.'&sid='.$sessionId
                    );
                    echo Display::tag('td', $link);
                }

                if (api_is_allowed_to_edit()) {
                    echo '<td>';
                    if (true === $any_result) {
                        $url = 'myStudents.php?action=reset_lp&sec_token='.$token.'&cid='.$courseId.'&course='
                            .$courseCode.'&details='.$details.'&origin='.$origin.'&lp_id='.$lp_id.'&student='
                            .$studentId.'&details=true&sid='.$sessionId;
                        echo Display::url(
                            Display::getMdiIcon(ActionIcon::RESET, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Clean')),
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
                }
                echo '</tr>';
            }
            echo '</tbody></table></div></div>'; // close table wrapper & card
        }
    }

    if (INVITEE != $user->getStatus()) {
        echo '<div class="learner-table-card learner-section-spacing"><div class="learner-table-wrapper">
        <table class="table table-striped table-hover">
        <thead>
        <tr>';
        echo '<th>'.get_lang('Tests').'</th>';
        echo '<th>'.get_lang('Learning paths').'</th>';
        echo '<th>'.get_lang('Average score in learning paths').' '.
            Display::getMdiIcon(
                ActionIcon::INFORMATION,
                'ch-tool-icon',
                'align: absmiddle; hspace: 3px',
                ICON_SIZE_SMALL,
                get_lang('Average score')
            ).'</th>';
        echo '<th>'.get_lang('Attempts').'</th>';
        echo '<th>'.get_lang('Latest attempt').'</th>';
        echo '<th>'.get_lang('All attempts').'</th>';

        $myStudentsQuizTrackingEvent = new MyStudentsQuizTrackingEvent([], AbstractEvent::TYPE_PRE);

        Container::getEventDispatcher()->dispatch($myStudentsQuizTrackingEvent, Events::MY_STUDENTS_EXERCISE_TRACKING);

        $eventHeaders = array_map(
            fn(array $eventHeader) => Display::tag('th', $eventHeader['title'], $eventHeader['attrs']),
            $myStudentsQuizTrackingEvent->getHeaders()
        );

        echo implode(PHP_EOL, $eventHeaders);
        echo '</tr></thead><tbody>';

        $csv_content[] = [];
        $csv_content[] = [
            get_lang('Tests'),
            get_lang('Learning paths'),
            get_lang('Average score in learning paths'),
            get_lang('Attempts'),
        ];

        $eventHeaders = array_map(
            fn(array $eventHeader) => strip_tags($eventHeader['title']),
            $myStudentsQuizTrackingEvent->getHeaders()
        );

        $csvContentIndex = count($csv_content) - 1;
        $csv_content[$csvContentIndex] = array_merge($csv_content[$csvContentIndex], $eventHeaders);

        $course = api_get_course_entity($courseId);
        $session = api_get_session_entity($sessionId);
        $repo = Container::getQuizRepository();
        $exerciseInSession = $repo->findAllByCourse($course, $session, null, null, false)
            ->getQuery()->getResult();

        $exerciseGlobal = [];
        if ((int) $sessionId > 0) {
            $exerciseGlobal = $repo->findAllByCourse($course, null, null, null, false)
                ->getQuery()->getResult();
        }
        $seen = [];
        $exerciseList = [];
        foreach (array_merge($exerciseInSession, $exerciseGlobal) as $quiz) {
            $id = $quiz->getIid();
            if (!isset($seen[$id])) {
                $seen[$id] = true;
                $exerciseList[] = $quiz;
            }
        }

        if ($exerciseList) {
            /** @var CQuiz $exercise */
            $i = 0;
            foreach ($exerciseList as $exercise) {
                $exercise_id = (int) $exercise->getIid();
                $count_attempts = Tracking::count_student_exercise_attempts(
                    $studentId,
                    $courseId,
                    $exercise_id,
                    0,
                    0,
                    $sessionId,
                    2
                );
                $score_percentage = Tracking::get_avg_student_exercise_score(
                    $studentId,
                    $courseCode,
                    $exercise_id,
                    $sessionId,
                    1,
                    0
                );

                $lp_name = '-';

                $myStudentsQuizTrackingEvent = new MyStudentsQuizTrackingEvent(
                    ['exercise_id' => $exercise_id, 'student_id' => $studentId],
                    AbstractEvent::TYPE_POST
                );

                Container::getEventDispatcher()->dispatch($myStudentsQuizTrackingEvent, Events::MY_STUDENTS_EXERCISE_TRACKING);

                $eventContents = $myStudentsQuizTrackingEvent->getContents();

                $hookContents = [];
                if (!isset($score_percentage) && $count_attempts > 0) {
                    $scores_lp = Tracking::get_avg_student_exercise_score(
                        $studentId,
                        $courseCode,
                        $exercise_id,
                        $sessionId,
                        2,
                        1
                    );

                    if (is_array($scores_lp) && array_key_exists(0, $scores_lp) && array_key_exists(1, $scores_lp)) {
                        $score_percentage = $scores_lp[0];
                        $lp_name = $scores_lp[1];
                    } else {
                        $score_percentage = $score_percentage ?: 0;
                        $lp_name = $lp_name ?: '';
                    }
                }
                $lp_name = !empty($lp_name) ? $lp_name : get_lang('No learning path');

                $css_class = ($i % 2) ? 'row_odd' : 'row_even';
                $exerciseTitle = Exercise::get_formated_title_variable($exercise->getTitle());
                echo '<tr class="'.$css_class.'"><td>'.$exerciseTitle.'</td>';
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
                $sessionCondition = api_get_session_condition($sessionId);
                $sql = 'SELECT exe_id FROM '.$tbl_stats_exercices.'
                        WHERE
                            exe_exo_id = "'.$exercise_id.'" AND
                            exe_user_id ="'.$studentId.'" AND
                            c_id = '.$courseId.' AND
                            status = ""
                            '.$sessionCondition.'
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
                        $attemptLink =
                            '../exercise/exercise_show.php?id='.$id_last_attempt.'&cid='.$courseId
                            .'&sid='.$sessionId.'&student='.$studentId.'&origin='
                            .(empty($origin) ? 'tracking' : $origin).$qualifyLink;
                        echo Display::url(
                            Display::getMdiIcon(ToolIcon::QUIZ, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Test')),
                            $attemptLink
                        );
                    }
                }
                echo '</td>';

                echo '<td>';
                if ($count_attempts > 0) {
                    $all_attempt_url = "../exercise/exercise_report.php?id=$exercise_id&"
                        ."cid=".$courseId."&filter_by_user=$studentId&sid=$sessionId";
                    echo Display::url(
                        Display::getMdiIcon('format-annotation-plus', 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('All attempts')),
                        $all_attempt_url
                    );
                }
                echo '</td>';

                foreach ($eventContents as $eventContent) {
                    echo Display::tag('td', $eventContent['value'], $eventContent['attrs']);
                }

                echo '</tr>';
                $data_exercices[$i][] = $exercise->getTitle();
                $data_exercices[$i][] = $score_percentage.'%';
                $data_exercices[$i][] = $count_attempts;

                $csv_content[] = [
                    $exercise->getTitle(),
                    $lp_name,
                    $score_percentage,
                    $count_attempts,
                ];

                if (!empty($hookContents)) {
                    $csvContentIndex = count($csv_content) - 1;

                    foreach ($eventContents as $eventContent) {
                        $csv_content[$csvContentIndex][] = strip_tags($eventContent['value']);
                    }
                }
                $i++;
            }
        } else {
            echo '<tr><td colspan="6">'.get_lang('There is no test for the moment').'</td></tr>';
        }
        echo '</tbody></table></div></div>'; // end tests card
    }

    // @when using sessions we do not show the survey list
    if (empty($sessionId)) {
        if (!empty($survey_list)) {
            $survey_data = [];
            foreach ($survey_list as $survey) {
                $user_list = SurveyManager::get_people_who_filled_survey(
                    $survey['survey_id'],
                    false,
                    $course->getId()
                );
                $survey_done = Display::getMdiIcon(StateIcon::COMPLETE, 'ch-tool-icon-disabled', null, ICON_SIZE_SMALL, get_lang('There is no answer for the moment'));
                if (in_array($studentId, $user_list)) {
                    $survey_done = Display::getMdiIcon(StateIcon::COMPLETE, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Answered'));
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

    echo '
        <div class="learner-table-card learner-section-spacing">
            <div class="learner-table-wrapper">
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

    $repo = Container::getStudentPublicationRepository();
    $works = $repo->getStudentPublicationByUser($user, $course, $session);

    foreach ($works as $workData) {
        /** @var CStudentPublication $work */
        $work = $workData['work'];
        /** @var CStudentPublication[] $results */
        $results = $workData['results'];
        $showOnce = true;
        $key = 1;
        foreach ($results as $result) {
            $resultId = $result->getIid();
            $assignment = $work->getAssignment();
            echo '<tr>';
            echo '<td>'.$work->getTitle().'</td>';
            $documentNumber = $key + 1;
            $key++;
            $url = api_get_path(WEB_CODE_PATH).'work/view.php?cid='.$courseId.'&sid='.$sessionId.'&id='.$resultId;
            echo '<td class="text-center"><a href="'.$url.'">('.$documentNumber.')</a></td>';
            $qualification = $result->getQualification();
            $qualification = !empty($qualification) ? $qualification : '-';
            echo '<td class="text-center">'.$qualification.'</td>';
            echo '<td class="text-center">'.
                api_convert_and_format_date($result->getSentDate()).' </td>';

            echo '<td class="text-center">';
            if ($assignment && !empty($assignment->getExpiresOn())) {
                echo api_convert_and_format_date($assignment->getExpiresOn());
            }
            echo '</td>';

            $fieldValue = new ExtraFieldValue('work');
            $resultExtra = $fieldValue->getAllValuesForAnItem(
                $resultId,
                true
            );

            foreach ($resultExtra as $field) {
                $field = $field['value'];
                if ($workingTime == $field->getField()->getVariable()) {
                    $time = $field->getValue();
                    echo '<td class="text-center">';
                    echo $time;
                    if ($workingTimeEdit && $showOnce) {
                        $showOnce = false;
                        echo '&nbsp;'.Display::url(
                                get_lang('Add time'),
                                $currentUrl.'&action=add_work_time&time='.$time.'&work_id='.$work->getIid()
                            );

                        echo '&nbsp;'.Display::url(
                                get_lang('Remove time'),
                                $currentUrl.'&action=remove_work_time&time='.$time.'&work_id='.$work->getIid()
                            );
                    }
                    echo '</td>';
                }
            }
            echo '</tr>';
        }
    }

    echo '</tbody>
            </table>
        </div>
    </div>'; // end tasks card

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

    echo '</div></section>'; // close main LP/tests/tasks card
}

// ----- Messages section -----
if ($allowMessages) {
    echo '<section class="learner-card learner-section-spacing">';
    echo Display::page_subheader2(get_lang('Messages'));
    echo MessageManager::getMessagesAboutUserToString($user, $currentUrl);
    echo Display::url(
        get_lang('New message'),
        'javascript: void(0);',
        [
            'onClick' => "$('#compose_message').show();",
            'class' => 'btn btn--plain mb-6',
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
    echo '</section>';
}

// ----- Message tracking -----
$allow = ('true' === api_get_setting('message.allow_user_message_tracking'));
if ($allow && (api_is_drh() || api_is_platform_admin())) {
    $users = MessageManager::getUsersThatHadConversationWithUser($studentId);
    echo '<section class="learner-card learner-section-spacing">';
    echo Display::page_subheader2(get_lang('Message tracking'));
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
        $url = api_get_path(WEB_CODE_PATH).'tracking/messages.php?from_user='.$studentId.'&to_user='.$followedUserId;
        $link = Display::url(
            $userFollowed['complete_name'],
            $url
        );
        $table->setCellContents($row, $column, $link);
        $row++;
    }
    $table->display();
    echo '</section>';
}

// ----- Calendar plugin panel -----
if ($pluginCalendar) {
    echo '<section class="learner-card learner-section-spacing">';
    echo $plugin->getUserStatsPanel($studentId, $courses_in_session);
    echo '</section>';
}

// Close main page container
echo '</div>';

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
