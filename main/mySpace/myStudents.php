<?php

/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Entity\CItemProperty;
use Chamilo\CourseBundle\Entity\CLpCategory;
use ChamiloSession as Session;

if (!isset($_GET['course'])) {
    $cidReset = true;
}

require_once __DIR__.'/../inc/global.inc.php';
require_once '../work/work.lib.php';

api_block_anonymous_users();
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_PUBLIC_PATH)
    .'assets/jquery.easy-pie-chart/dist/jquery.easypiechart.js"></script>';

$export = isset($_GET['export']) ? $_GET['export'] : false;
$sessionId = isset($_GET['id_session']) ? (int) $_GET['id_session'] : 0;
$action = isset($_GET['action']) ? $_GET['action'] : '';
$origin = api_get_origin();
$course_code = isset($_GET['course']) ? Security::remove_XSS($_GET['course']) : '';
$courseInfo = api_get_course_info($course_code);
$courseCode = '';
if ($courseInfo) {
    $courseCode = $courseInfo['code'];
}
$student_id = isset($_GET['student']) ? (int) $_GET['student'] : 0;
$coachId = isset($_GET['id_coach']) ? (int) $_GET['id_coach'] : 0;
$details = isset($_GET['details']) ? Security::remove_XSS($_GET['details']) : '';
$currentUrl = api_get_self().'?student='.$student_id.'&course='.$courseCode.'&id_session='.$sessionId
    .'&origin='.$origin.'&details='.$details;
$allowMessages = api_get_configuration_value('private_messages_about_user');
$workingTime = api_get_configuration_value('considered_working_time');
$workingTimeEdit = api_get_configuration_value('allow_working_time_edition');

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

$em = Database::getManager();
$itemRepo = $em->getRepository(CItemProperty::class);

if (false === $allowedToTrackUser && !empty($courseInfo)) {
    if (empty($sessionId)) {
        $isTeacher = CourseManager::is_course_teacher(
            api_get_user_id(),
            $courseInfo['code']
        );

        if ($isTeacher) {
            $allowedToTrackUser = true;
        } else {
            // Check if the user is tutor of the course
            $userCourseStatus = CourseManager::get_tutor_in_course_status(
                api_get_user_id(),
                $courseInfo['real_id']
            );
            if ($userCourseStatus == 1) {
                $allowedToTrackUser = true;
            }
        }
    } else {
        $coach = api_is_coach($sessionId, $courseInfo['real_id']);

        if ($coach) {
            $allowedToTrackUser = true;
        }
    }
}

if (!$allowedToTrackUser) {
    api_not_allowed(true);
}
if (empty($student_id)) {
    api_not_allowed(true);
}

$user_info = api_get_user_info($student_id);

if (empty($user_info)) {
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

// Database Table Definitions
$tbl_course_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
$tbl_stats_exercices = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);

switch ($action) {
    case 'delete_msg':
        $messageId = (int) $_GET['msg_id'];
        MessageManager::delete_message_by_user_sender(api_get_user_id(), $messageId);
        break;
    case 'add_work_time':
        if (false === $workingTimeEdit) {
            api_not_allowed(true);
        }
        $workingTime = isset($_GET['time']) ? $_GET['time'] : '';
        $workId = isset($_GET['work_id']) ? $_GET['work_id'] : '';
        Event::eventAddVirtualCourseTime($courseInfo['real_id'], $student_id, $sessionId, $workingTime, $workId);
        Display::addFlash(Display::return_message(get_lang('Updated')));

        header('Location: '.$currentUrl);
        exit;
    case 'remove_work_time':
        if (false === $workingTimeEdit) {
            api_not_allowed(true);
        }
        $workingTime = isset($_GET['time']) ? $_GET['time'] : '';
        $workId = isset($_GET['work_id']) ? $_GET['work_id'] : '';
        Event::eventRemoveVirtualCourseTime($courseInfo['real_id'], $student_id, $sessionId, $workingTime, $workId);

        Display::addFlash(Display::return_message(get_lang('Updated')));

        header('Location: '.$currentUrl);
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
                $title .= '_'.Security::remove_XSS($sessionInfo['name']);
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
        if (true === $allowMessages) {
            $subject = isset($_POST['subject']) ? $_POST['subject'] : '';
            $message = isset($_POST['message']) ? $_POST['message'] : '';

            if (!empty($subject) && !empty($message)) {
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
            } else {
                Display::addFlash(Display::return_message(get_lang('AllFieldsRequired'), 'warning'));
            }

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
    case 'lp_quiz_to_export_pdf':

        $sessionToExport = isset($_GET['session_to_export']) ? (int) $_GET['session_to_export'] : 0;
        $sessionInfo = api_get_session_info($sessionToExport);
        if (empty($sessionInfo)) {
            api_not_allowed(true);
        }

        $studentInfo = api_get_user_info($student_id);
        $lpQuizTable = Tracking::getLpQuizContentToPdf($student_id, $sessionToExport);

        $tpl = new Template('', false, false, false, true, false, false);
        $tpl->assign('title', get_lang('ElearningResults'));
        $tpl->assign('session_title', $sessionInfo['name']);
        $tpl->assign('student', $studentInfo['complete_name']);
        $tpl->assign('table_test', $lpQuizTable);

        $content = $tpl->fetch($tpl->get_template('my_space/pdf_export_results.tpl'));
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

        @$pdf = new PDF('A4', $params['orientation'], $params);
        try {
            $theme = $tpl->theme;
            $themeName = empty($theme) ? api_get_visual_theme() : $theme;
            $themeDir = \Template::getThemeDir($theme);
            $customLetterhead = $themeDir.'images/letterhead.png';
            $urlPathLetterhead = api_get_path(SYS_CSS_PATH).$customLetterhead;

            $urlWebLetterhead = '#FFFFFF';
            $fullPage = false;
            if (file_exists($urlPathLetterhead)) {
                $fullPage = true;
                $urlWebLetterhead = 'url('.api_get_path(WEB_CSS_PATH).$customLetterhead.')';
            }

            if ($fullPage) {
                $pdf->pdf->SetDisplayMode('fullpage');
                $pdf->pdf->SetDefaultBodyCSS('background', $urlWebLetterhead);
                $pdf->pdf->SetDefaultBodyCSS('background-image-resize', '6');
            }

            @$pdf->content_to_pdf($content,
                $css = '',
                $pdf_name = '',
                $course_code = null,
                $outputMode = 'D',
                $saveInFile = false,
                $fileToSave = null,
                $returnHtml = false,
                $addDefaultCss = true,
                $completeHeader = false
            );
        } catch (MpdfException $e) {
            error_log($e);
        }
        break;
    case 'cert_to_export_pdf':

        $sId = isset($_GET['session_to_export']) ? (int) $_GET['session_to_export'] : 0;
        $sessionInfo = api_get_session_info($sId);
        if (empty($sessionInfo)) {
            api_not_allowed(true);
        }

        $studentInfo = api_get_user_info($student_id);
        $tablesToExport = Tracking::getLpCertificateTablesToPdf($student_id, $sId);

        $tpl = new Template('', false, false, false, true, false, false);
        $tpl->assign('title', get_lang('AttestationOfAttendance'));
        $tpl->assign('session_title', $sessionInfo['name']);
        $tpl->assign('student', $studentInfo['complete_name']);
        $tpl->assign('table_progress', $tablesToExport['progress_table']);
        $tpl->assign('subtitle', sprintf(get_lang('InSessionXYouHadTheFollowingResults'), $sessionInfo['name']));
        $tpl->assign('table_course', $tablesToExport['course_table']);
        $tpl->assign('table_parcours', $tablesToExport['lp_table']);

        $content = $tpl->fetch($tpl->get_template('my_space/pdf_export_certificate.tpl'));
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

        @$pdf = new PDF('A4', $params['orientation'], $params);
        try {
            $theme = $tpl->theme;
            $themeName = empty($theme) ? api_get_visual_theme() : $theme;
            $themeDir = \Template::getThemeDir($theme);
            $customLetterhead = $themeDir.'images/letterhead.png';
            $urlPathLetterhead = api_get_path(SYS_CSS_PATH).$customLetterhead;

            $urlWebLetterhead = '#FFFFFF';
            $fullPage = false;
            if (file_exists($urlPathLetterhead)) {
                $fullPage = true;
                $urlWebLetterhead = 'url('.api_get_path(WEB_CSS_PATH).$customLetterhead.')';
            }

            if ($fullPage) {
                $pdf->pdf->SetDisplayMode('fullpage');
                $pdf->pdf->SetDefaultBodyCSS('background', $urlWebLetterhead);
                $pdf->pdf->SetDefaultBodyCSS('background-image-resize', '6');
            }

            @$pdf->content_to_pdf($content,
                $css = '',
                $pdf_name = '',
                $course_code = null,
                $outputMode = 'D',
                $saveInFile = false,
                $fileToSave = null,
                $returnHtml = false,
                $addDefaultCss = true,
                $completeHeader = false
            );
        } catch (MpdfException $e) {
            error_log($e);
        }
        break;
    case 'lp_stats_to_export_pdf':
        $categoriesTempList = learnpath::getCategories($courseInfo['real_id']);
        $categoryTest = new CLpCategory();
        $categoryTest->setId(0);
        $categoryTest->setName(get_lang('WithOutCategory'));
        $categoryTest->setPosition(0);
        $categories = [
            $categoryTest,
        ];

        if (!empty($categoriesTempList)) {
            $categories = array_merge($categories, $categoriesTempList);
        }

        $userEntity = api_get_user_entity($student_id);
        $courseTable = '';
        /** @var CLpCategory $item */
        foreach ($categories as $item) {
            $categoryId = $item->getId();
            if (!learnpath::categoryIsVisibleForStudent($item, $userEntity, $courseInfo['real_id'], $sessionId)) {
                continue;
            }

            $list = new LearnpathList(
                $student_id,
                $courseInfo,
                $sessionId,
                null,
                false,
                $categoryId,
                false,
                true
            );
            $flatList = $list->get_flat_list();
            foreach ($flatList as $learnpath) {
                $lpId = $learnpath['lp_old_id'];
                $output = Tracking::getLpStatsContentToPdf(
                    $student_id,
                    $courseInfo,
                    $sessionId,
                    $lpId,
                    $learnpath['lp_name']
                );
                $courseTable .= $output;
            }
        }

        $pdfTitle = get_lang('TestResult');
        $sessionInfo = api_get_session_info($sessionId);
        $studentInfo = api_get_user_info($student_id);
        $tpl = new Template('', false, false, false, true, false, false);
        $tpl->assign('title', $pdfTitle);
        $tpl->assign('session_title', $sessionInfo['name']);
        $tpl->assign('session_info', $sessionInfo);
        $tpl->assign('table_course', $courseTable);

        $content = $tpl->fetch($tpl->get_template('my_space/pdf_lp_stats.tpl'));

        $params = [
            'pdf_title' => $pdfTitle,
            'session_info' => $sessionInfo,
            'course_info' => '',
            'pdf_date' => '',
            'student_info' => $studentInfo,
            'show_grade_generated_date' => true,
            'show_real_course_teachers' => false,
            'show_teacher_as_myself' => false,
            'orientation' => 'P',
        ];
        @$pdf = new PDF('A4', $params['orientation'], $params);
        $pdf->setBackground($tpl->theme);
        $mode = 'D';
        $pdfName = $sessionInfo['name'].'_'.$studentInfo['complete_name'];
        $pdf->set_footer();
        $result = @$pdf->content_to_pdf(
            $content,
            '',
            $pdfName,
            null,
            $mode,
            false,
            null,
            false,
            true,
            false
        );
        break;
    default:
        break;
}

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
                    'url' => '../session/session_list.php',
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

$sessionTable = Database::get_main_table(TABLE_MAIN_SESSION);

// Get the list of sessions where the user is subscribed as student
$sql = 'SELECT scu.session_id, scu.c_id
        FROM '.Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER).' scu
        INNER JOIN '.$sessionTable.' as s
        ON (s.id = scu.session_id)
        WHERE user_id = '.$student_id.'
        ORDER BY display_end_date DESC
        ';
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

$isDrhOfCourse = CourseManager::isUserSubscribedInCourseAsDrh(api_get_user_id(), $courseInfo);
$drhCanAccessAllStudents = (api_drh_can_access_all_session_content() || api_get_configuration_value('drh_allow_access_to_all_students'));

if (api_is_drh() && !api_is_platform_admin()) {
    if (!empty($student_id)) {
        if ($drhCanAccessAllStudents) {
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
if ('session_report' === $origin) {
    echo '<a href="'.api_get_path(WEB_CODE_PATH).'mySpace/progress_in_session_report.php">'
        .Display::return_icon('back.png', get_lang('Back'), '', ICON_SIZE_MEDIUM).'</a>';
} else {
    echo '<a href="javascript: window.history.go(-1);">'
        .Display::return_icon('back.png', get_lang('Back'), '', ICON_SIZE_MEDIUM).'</a>';
}

echo '<a href="javascript: void(0);" onclick="javascript: window.print();">'
    .Display::return_icon('printer.png', get_lang('Print'), '', ICON_SIZE_MEDIUM).'</a>';

echo '<a href="'.api_get_self().'?'.Security::remove_XSS($_SERVER['QUERY_STRING']).'&export=csv">'
    .Display::return_icon('export_csv.png', get_lang('ExportAsCSV'), '', ICON_SIZE_MEDIUM).'</a> ';

echo '<a href="'.api_get_self().'?'.Security::remove_XSS($_SERVER['QUERY_STRING']).'&export=xls">'
    .Display::return_icon('export_excel.png', get_lang('ExportAsXLS'), '', ICON_SIZE_MEDIUM).'</a> ';

if (!empty($student_id) && empty($courseCode)) {
    echo Display::url(
        Display::return_icon('export_pdf.png', get_lang('ExportToPDF'), [], ICON_SIZE_MEDIUM),
        'student_follow_export.php?'.http_build_query(['student' => $student_id]),
        ['class' => 'ajax', 'data-title' => get_lang('ExportToPDF')]
    );
}
if (true === api_get_configuration_value('course_tracking_student_detail_show_certificate_of_achievement')) {
    echo Display::url(
        Display::return_icon('activity_monitor.png', get_lang('AccessDetails'), '', ICON_SIZE_MEDIUM),
        api_get_path(WEB_CODE_PATH).'mySpace/access_details_session.php?user_id='.$student_id
    );
}

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
            get_lang('CountDoneAttendance'),
            null,
            ICON_SIZE_MEDIUM
        ),
        api_get_path(WEB_CODE_PATH).'mySpace/myStudents.php?action=all_attendance&student='.$student_id
    );
}

$permissions = StudentFollowUpPlugin::getPermissions($student_id, api_get_user_id());
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
    $isSubscribedToCourse = CourseManager::is_user_subscribed_in_course($student_id, $courseCode);
} else {
    $isSubscribedToCourse = CourseManager::is_user_subscribed_in_course(
        $student_id,
        $courseCode,
        true,
        $sessionId
    );
}

if ($isSubscribedToCourse) {
    $avg_student_progress = Tracking::get_avg_student_progress(
        $student_id,
        $courseCode,
        [],
        $sessionId
    );

    // the score inside the Reporting table
    $avg_student_score = Tracking::get_avg_student_score(
        $student_id,
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
            $student_id,
            $courseInfo['real_id'],
            $sessionId
        )
    );
}

// get information about connections on the platform by student
$first_connection_date = Tracking::get_first_connection_date($student_id);
if ($first_connection_date == '') {
    $first_connection_date = get_lang('NoConnexion');
}

$last_connection_date = Tracking::get_last_connection_date($student_id, true);
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
$userPicture = UserManager::getUserPicture($student_id, USER_IMAGE_SIZE_BIG);
$userGroupManager = new UserGroup();
$userGroups = $userGroupManager->getNameListByUser(
    $student_id,
    UserGroup::NORMAL_CLASS
);

$userInfo = [
    'id' => $student_id,
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
$timezone_user = UserManager::get_extra_user_data_by_field($student_id, 'timezone');
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
$userInfo['last_connection_in_course'] = api_format_date(
    Tracking::getLastConnectionInAnyCourse($student_id),
    DATE_FORMAT_SHORT
);
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
            [$legalId, $legalLanguageId, $legalTime] = explode(':', $value['value']);
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

if (isset($_GET['action']) && $_GET['action'] === 'all_attendance') {
    // Variable for all attendance list
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

    // $startDate = new DateTime(api_get_local_time($startDate));
    // $endDate = new DateTime(api_get_local_time($endDate));
    if ($startDate > $endDate) {
        $dataTemp = $startDate;
        $startDate = $endDate;
        $endDate = $dataTemp;
    }
    $startDateText = api_get_local_time($startDate);
    $endDateText = api_get_local_time($endDate);
    // Varible for all attendance list

    /** Start date and end date*/
    $defaults['startDate'] = $startDateText;
    $defaults['endDate'] = $endDateText;
    $form = new FormValidator(
        'all_attendance_list',
        'GET',
        'myStudents.php?action=all_attendance&student='.$student_id.'&startDate='.$defaults['startDate'].'&endDate='.$defaults['endDate'].'&&'.api_get_cidreq(),
        ''
    );
    $form->addElement('html', '<input type="hidden" name="student" value="'.$student_id.'" >');
    $form->addElement('html', '<input type="hidden" name="action" value="all_attendance" >');

    $form->addDateTimePicker(
        'startDate',
        [
            get_lang('ExeStartTime'),
        ],
        [
            'form_name' => 'attendance_calendar_edit',
        ],
        5
    );
    $form->addDateTimePicker(
        'endDate',
        [
            get_lang('ExeEndTime'),
        ],
        [
            'form_name' => 'attendance_calendar_edit',
        ],
        5
    );

    $form->addButtonSave(get_lang('Submit'));
    $form->setDefaults($defaults);
    $form->display();
    /** Display dates */
    $attendance = new Attendance();
    $data = $attendance->getCoursesWithAttendance($student_id, $startDate, $endDate);

    // 'attendance from %s to %s'
    $title = sprintf(get_lang('AttendanceFromXToY'), $startDateText, $endDateText);
    echo '
    <h3>'.$title.'</h3>
    <div class="">
    <table class="table table-striped table-hover table-responsive">
        <thead>
            <tr>
                <th>'.get_lang('DateExo').'</th>
                <th>'.get_lang('Training').'</th>
                <th>'.get_lang('Present').'</th>
            </tr>
        </thead>
    <tbody>';
    foreach ($data as $attendanceData => $attendanceSheet) {
        // $attendanceData  can be in_category or not_category for courses
        $totalAttendance = count($attendanceSheet);
        for ($i = 0; $i < $totalAttendance; $i++) {
            $attendanceWork = $attendanceSheet[$i];
            $courseInfoItem = api_get_course_info_by_id($attendanceWork['courseId']);
            $date = api_get_local_time($attendanceWork[1]);
            $sId = $attendanceWork['session'];
            $printSession = '';
            if ($sId != 0) {
                // get session name
                $printSession = "(".$attendanceWork['sessionName'].")";
            }
            echo '
            <tr>
                <td>'.$date.'</td>
                <td>'
                    .'<a title="'.get_lang('GoAttendance').'" href="'.api_get_path(WEB_CODE_PATH)
                    .'attendance/index.php?cidReq='.$attendanceWork['courseCode'].'&id_session='.$sId.'&student_id='
                    .$student_id.'">'
                    .$attendanceWork['courseTitle']." $printSession ".'</a>
                </td>

                <td>'.$attendanceWork['presence'].'</td>
            </tr>';
        }
    }
    echo '</tbody>
    </table></div>';
    Display::display_footer();
    exit();
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
        'course_first_access' => Tracking::get_first_connection_date_on_the_course(
            $student_id,
            $courseInfo['real_id'],
            $sessionId
        ),
        'course_last_access' => Tracking::get_last_connection_date_on_the_course($student_id, $courseInfo, $sessionId),
        'count_access_dates' => Tracking::getNumberOfCourseAccessDates($student_id, $courseInfo['real_id'], $sessionId),
    ];
} else {
    $details = false;
}

$hideLpTestAverageIcon = api_get_configuration_value('student_follow_page_hide_lp_tests_average');
$tpl->assign('user', $userInfo);
$tpl->assign('details', $details);
$tpl->assign('hide_lp_test_average', $hideLpTestAverageIcon);
$templateName = $tpl->get_template('my_space/user_details.tpl');
$content = $tpl->fetch($templateName);
echo $content;

// Careers.
if (api_get_configuration_value('allow_career_users')) {
    if (!empty($courses_in_session)) {
        echo SessionManager::getCareerDiagramPerSessionList(array_keys($courses_in_session), $student_id);
    }
    echo MyStudents::userCareersTable($student_id);
}

echo MyStudents::getBlockForSkills(
    $student_id,
    $courseInfo ? $courseInfo['real_id'] : 0,
    $sessionId
);
echo '<br /><br />';

$installed = AppPlugin::getInstance()->isInstalled('studentfollowup');

if ($installed) {
    echo Display::page_subheader(get_lang('Guidance'));
    echo '
       <script>
        resizeIframe = function(iFrame) {
            iFrame.height = iFrame.contentWindow.document.body.scrollHeight + 20;
        }
        </script>
    ';
    $url = api_get_path(WEB_PLUGIN_PATH).'studentfollowup/posts.php?iframe=1&student_id='.$student_id;
    echo '<iframe
        onload="resizeIframe(this)"
        style="width:100%;"
        border="0"
        frameborder="0"
        scrolling="no"
        src="'.$url.'"
    ></iframe>';
    echo '<br /><br />';
}

echo '<div class="row"><div class="col-sm-5">';
echo MyStudents::getBlockForClasses($student_id);
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
        get_lang('AttendancesFaults'),
        get_lang('Evaluations'),
    ];

    $attendance = new Attendance();
    $extraFieldValueSession = new ExtraFieldValue('session');
    $extraFieldValueCareer = new ExtraFieldValue('career');

    foreach ($courses_in_session as $sId => $courses) {
        $session_name = '';
        $access_start_date = '';
        $access_end_date = '';
        $date_session = '';
        $title = Display::return_icon('course.png', get_lang('Courses')).' '.get_lang('Courses');

        $session_info = api_get_session_info($sId);
        if ($session_info) {
            $session_name = Security::remove_XSS($session_info['name']);
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
            <th>'.get_lang('AttendancesFaults').'</th>
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
            get_lang('AttendancesFaults'),
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
                        $student_id,
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
                            $attendances_faults_avg = Display::url(
                                $results_faults_avg['faults'].'/'.$results_faults_avg['total']
                                    .' ('.$results_faults_avg['porcent'].'%)',
                                api_get_path(WEB_CODE_PATH)
                                    .'attendance/index.php?cidReq='.$courseCodeItem.'&id_session='.$sId.'&student_id='
                                    .$student_id,
                                ['title' => get_lang('GoAttendance')]
                            );
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
                        $student_id,
                        $courseCodeItem,
                        [],
                        $sId
                    );

                    $totalProgress += $progress;

                    $score = Tracking::get_avg_student_score(
                        $student_id,
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
                    <td>
                        <a href="'.$courseInfoItem['course_public_url'].'?id_session='.$sId.'">'.
                            $courseInfoItem['title'].'
                        </a>
                    </td>
                    <td>'.$time_spent_on_course.'</td>
                    <td>'.$progress.'</td>
                    <td>'.$score.'</td>
                    <td>'.$attendances_faults_avg.'</td>
                    <td>'.$scoretotal_display.'</td>';
                    if (!empty($coachId)) {
                        echo '<td width="10"><a href="'.api_get_self().'?student='.$student_id
                            .'&details=true&course='.$courseInfoItem['code'].'&id_coach='.$coachId.'&origin='.$origin
                            .'&id_session='.$sId.'#infosStudent">'
                            .Display::return_icon('2rightarrow.png', get_lang('Details')).'</a></td>';
                    } else {
                        echo '<td width="10"><a href="'.api_get_self().'?student='.$student_id
                            .'&details=true&course='.$courseInfoItem['code'].'&origin='.$origin.'&id_session='.$sId
                            .'#infosStudent">'
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
                Display::return_icon('export_csv.png', get_lang('ExportAsCSV'), [], ICON_SIZE_MEDIUM),
                $currentUrl.'&'
                .http_build_query(
                    [
                        'action' => 'export_one_session_row',
                        'export' => 'csv',
                        'session_to_export' => $sId,
                    ]
                ),
                ['class' => 'user-tracking-csv']
            );
            $sessionAction .= Display::url(
                Display::return_icon('export_excel.png', get_lang('ExportAsXLS'), [], ICON_SIZE_MEDIUM),
                $currentUrl.'&'
                    .http_build_query(
                        [
                            'action' => 'export_one_session_row',
                            'export' => 'xls',
                            'session_to_export' => $sId,
                        ]
                    ),
                ['class' => 'user-tracking-xls']
            );

            if (!empty($sId)) {
                $sessionAction .= Display::url(
                    Display::return_icon('attendance_certificate_pdf.png', get_lang('AttestationOfAttendance'), [], ICON_SIZE_MEDIUM),
                    api_get_path(WEB_CODE_PATH).'mySpace/session.php?'
                    .http_build_query(
                        [
                            'student' => $student_id,
                            'action' => 'export_to_pdf',
                            'type' => 'attendance',
                            'session_to_export' => $sId,
                        ]
                    ),
                    ['class' => 'user-tracking-export-pdf']
                );
                $sessionAction .= Display::url(
                    Display::return_icon('achievement_certificate_pdf.png', get_lang('CertificateOfAchievement'), [], ICON_SIZE_MEDIUM),
                    api_get_path(WEB_AJAX_PATH).'myspace.ajax.php?'
                    .http_build_query(
                        [
                            'a' => 'show_conditional_to_export_pdf',
                            'student' => $student_id,
                            'session_to_export' => $sId,
                            'type' => 'achievement',
                        ]
                    ),
                    [
                        'class' => "ajax user-tracking-achievement",
                        'data-size' => 'sm',
                        'data-title' => get_lang('CertificateOfAchievement'),
                    ]
                );
                $sessionAction .= Display::url(
                    Display::return_icon('test_results_pdf.png', get_lang('TestResult'), [], ICON_SIZE_MEDIUM),
                    api_get_path(WEB_CODE_PATH).'mySpace/myStudents.php?'
                    .http_build_query(
                        [
                            'action' => 'lp_stats_to_export_pdf',
                            'student' => $student_id,
                            'id_session' => $sId,
                            'course' => $courseInfoItem['code'],
                        ]
                    ),
                    ['class' => 'user-tracking-test-results']
                );

                // New reports from MJTecnoid
                $sessionAction .= Display::url(
                    Display::return_icon('achievement_certificate_by_lp_pdf.png', get_lang('CertificateOfAchievement2'), [], ICON_SIZE_MEDIUM),
                    api_get_path(WEB_CODE_PATH).'mySpace/myStudents.php?'
                    .http_build_query(
                        [
                            'action' => 'cert_to_export_pdf',
                            'student' => $student_id,
                            'session_to_export' => $sId,
                            'course' => $courseInfoItem['code'],
                        ]
                    ),
                    ['class' => 'user-tracking-achievement-by-lp']
                );

                $sessionAction .= Display::url(
                    Display::return_icon('test_result_by_lp_pdf.png', get_lang('ExportLpQuizResults'), [], ICON_SIZE_MEDIUM),
                    api_get_path(WEB_CODE_PATH).'mySpace/myStudents.php?'
                    .http_build_query(
                        [
                            'action' => 'lp_quiz_to_export_pdf',
                            'student' => $student_id,
                            'session_to_export' => $sId,
                            'course' => $courseInfoItem['code'],
                        ]
                    ),
                    ['class' => 'user-tracking-test-results-by-lp']
                );
            }
            echo $sessionAction;
        } else {
            echo "<tr><td colspan='5'>".get_lang('NoCourse')."</td></tr>";
        }
        Session::write('export_course_list', $exportCourseList);
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
    }
} else {
    $columnHeaders = [
        'lp' => get_lang('LearningPath'),
        'time' => get_lang('Time').
            Display::return_icon(
                'info3.gif',
                get_lang('TotalTimeByCourse'),
                ['align' => 'absmiddle', 'hspace' => '3px']
            ),
        'best_score' => get_lang('BestScore'),
        'latest_attempt_avg_score' => get_lang('LatestAttemptAverageScore').
            Display::return_icon(
                'info3.gif',
                get_lang('AverageIsCalculatedBasedInTheLatestAttempts'),
                ['align' => 'absmiddle', 'hspace' => '3px']
            ),
        'progress' => get_lang('Progress').
            Display::return_icon('info3.gif', get_lang('LPProgressScore'), ['align' => 'absmiddle', 'hspace' => '3px']),
        'last_connection' => get_lang('LastConnexion').
            Display::return_icon(
                'info3.gif',
                get_lang('LastTimeTheCourseWasUsed'),
                ['align' => 'absmiddle', 'hspace' => '3px']
            ),
    ];

    $timeCourse = null;
    if (Tracking::minimumTimeAvailable($sessionId, $courseInfo['real_id'])) {
        $timeCourse = Tracking::getCalculateTime($student_id, $courseInfo['real_id'], $sessionId);
    }

    if (INVITEE != $user_info['status']) {
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

        if (true === api_get_configuration_value('student_follow_page_add_LP_invisible_checkbox')) {
            echo StudentFollowPage::getLpVisibleScript();

            $chkb = Display::input('checkbox', 'chkb_category[]', '')
                .PHP_EOL.get_lang('Invisible');

            $columnHeaders = array_merge(
                ['student_follow_page_add_LP_invisible_checkbox' => $chkb],
                $columnHeaders
            );
        }

        if (true === api_get_configuration_value('student_follow_page_add_LP_subscription_info')) {
            $columnHeaders['student_follow_page_add_LP_subscription_info'] = get_lang('Unlock');
        }

        if (true === api_get_configuration_value('student_follow_page_add_LP_acquisition_info')) {
            $columnHeaders['student_follow_page_add_LP_acquisition_info'] = get_lang('Acquisition');
        }

        $headers = '';
        $columnHeadersToExport = [];
        // csv export headers
        foreach ($columnHeaders as $key => $columnName) {
            if ('student_follow_page_add_LP_invisible_checkbox' !== $key) {
                $columnHeadersToExport[] = strip_tags($columnName);
            }

            $headers .= Display::tag(
                'th',
                $columnName
            );
        }

        $hookLpTracking = HookMyStudentsLpTracking::create();
        if ($hookLpTracking) {
            $hookHeaders = $hookLpTracking->notifyTrackingHeader();
            foreach ($hookHeaders as $hookHeader) {
                if (isset($hookHeader['value'])) {
                    $columnHeadersToExport[] = $hookHeader['value'];
                    $headers .= Display::tag('th', $hookHeader['value'], $hookHeader['attrs']);
                }
            }
        }

        $csv_content[] = $columnHeadersToExport;
        $columnHeadersKeys = array_keys($columnHeaders);
        $categoriesTempList = learnpath::getCategories($courseInfo['real_id']);
        $categoryTest = new CLpCategory();
        $categoryTest->setId(0);
        $categoryTest->setName(get_lang('WithOutCategory'));
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
                echo '<th>'.get_lang('ResetLP').'</th>';
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
                    $courseCode,
                    [$lp_id],
                    $sessionId
                );

                if ($progress === null) {
                    $progress = '0%';
                } else {
                    $any_result = true;
                }

                // Get time in lp
                $linkMinTime = '';
                $formattedLpTime = '';
                if (!empty($timeCourse)) {
                    $lpTime = $timeCourse[TOOL_LEARNPATH] ?? 0;
                    $totalLpTime = isset($lpTime[$lp_id]) ? (int) $lpTime[$lp_id] : 0;

                    if (Tracking::minimumTimeAvailable($sessionId, $courseInfo['real_id'])) {
                        $accumulateWorkTime = learnpath::getAccumulateWorkTimePrerequisite(
                            $lp_id,
                            $courseInfo['real_id']
                        );
                        if ($accumulateWorkTime > 0) {

                            // If the time spent is less than necessary,
                            // then we show an icon in the actions column indicating the warning
                            $formattedLpTime = api_time_to_hms($totalLpTime);
                            $formattedWorkTime = api_time_to_hms($accumulateWorkTime * 60);

                            if ($totalLpTime < ($accumulateWorkTime * 60)) {
                                $linkMinTime = Display::return_icon(
                                    'warning.png',
                                    get_lang('LpMinTimeWarning').' - '.
                                    $formattedLpTime.' / '.
                                    $formattedWorkTime
                                );
                            }
                        } else {
                            $formattedLpTime = api_time_to_hms($totalLpTime);
                        }
                    }
                } else {
                    $totalLpTime = Tracking::get_time_spent_in_lp(
                        $student_id,
                        $courseCode,
                        [$lp_id],
                        $sessionId
                    );
                    $formattedLpTime = api_time_to_hms($totalLpTime);
                }

                if (!empty($totalLpTime)) {
                    $any_result = true;
                }

                // Get last connection time in lp
                $start_time = Tracking::get_last_connection_time_in_lp(
                    $student_id,
                    $courseCode,
                    $lp_id,
                    $sessionId
                );

                if (!empty($start_time)) {
                    $start_time = api_convert_and_format_date($start_time, DATE_TIME_FORMAT_LONG);
                } else {
                    $start_time = '-';
                }

                // Quiz in lp
                $score = Tracking::get_avg_student_score(
                    $student_id,
                    $courseCode,
                    [$lp_id],
                    $sessionId
                );

                // Latest exercise results in a LP
                $score_latest = Tracking::get_avg_student_score(
                    $student_id,
                    $courseCode,
                    [$lp_id],
                    $sessionId,
                    false,
                    true
                );

                $bestScore = Tracking::get_avg_student_score(
                    $student_id,
                    $courseCode,
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

                if (in_array('student_follow_page_add_LP_invisible_checkbox', $columnHeadersKeys)) {
                    echo Display::tag(
                        'td',
                        StudentFollowPage::getLpVisibleField(
                            $learnpath,
                            $student_id,
                            $courseInfo['real_id'],
                            $sessionId
                        )
                    );
                }

                if (in_array('lp', $columnHeadersKeys)) {
                    $contentToExport[] = strip_tags($lp_name);
                    echo Display::tag('td', stripslashes($lp_name));
                }
                if (in_array('time', $columnHeadersKeys)) {
                    $contentToExport[] = $formattedLpTime;
                    echo Display::tag('td', $linkMinTime.$formattedLpTime, ['style' => 'width: 10%']);
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

                if (in_array('student_follow_page_add_LP_subscription_info', $columnHeadersKeys)) {
                    $lpSubscription = StudentFollowPage::getLpSubscription(
                        $learnpath,
                        $student_id,
                        $courseInfo['real_id'],
                        $sessionId
                    );
                    $contentToExport[] = strip_tags(str_replace('<br>', "\n", $lpSubscription));
                    echo Display::tag('td', $lpSubscription);
                }

                if (in_array('student_follow_page_add_LP_acquisition_info', $columnHeadersKeys)) {
                    $lpAcquisition = StudentFollowPage::getLpAcquisition(
                        $learnpath,
                        $student_id,
                        $courseInfo['real_id'],
                        $sessionId,
                        true
                    );
                    $contentToExport[] = strip_tags(str_replace('<br>', "\n", $lpAcquisition));
                    echo Display::tag('td', $lpAcquisition);
                }

                if ($hookLpTracking) {
                    $hookContents = $hookLpTracking->notifyTrackingContent($lp_id, $student_id);

                    foreach ($hookContents as $hookContent) {
                        if (isset($hookContent['value'])) {
                            $contentToExport[] = strip_tags($hookContent['value']);
                            echo Display::tag('td', $hookContent['value'], $hookContent['attrs']);
                        }
                    }
                }

                $csv_content[] = $contentToExport;

                if ($any_result === true) {
                    $from = '';
                    if ($from_myspace) {
                        $from = '&from=myspace';
                    }
                    $link = Display::url(
                        Display::return_icon('2rightarrow.png', get_lang('Details')),
                        'lp_tracking.php?cidReq='.$courseCode.'&course='.$courseCode.$from.'&origin='.$origin
                        .'&lp_id='.$lp_id.'&student_id='.$student_id.'&id_session='.$sessionId
                    );
                    echo Display::tag('td', $link);
                }

                if (api_is_allowed_to_edit()) {
                    echo '<td>';
                    if ($any_result === true) {
                        $url = 'myStudents.php?action=reset_lp&sec_token='.$token.'&cidReq='.$courseCode.'&course='
                            .$courseCode.'&details='.$details.'&origin='.$origin.'&lp_id='.$lp_id.'&student='
                            .$student_id.'&details=true&id_session='.$sessionId;
                        echo Display::url(
                            Display::return_icon('clean.png', get_lang('Clean')),
                            $url,
                            [
                                'onclick' => "javascript:if(!confirm('"
                                    .addslashes(
                                        api_htmlentities(get_lang('AreYouSureToDelete'))
                                    )
                                    ."')) return false;",
                            ]
                        );
                    }
                    echo '</td>';
                }
                echo '</tr>';
            }
            echo '</tbody></table></div>';
        }
    }

    if ($user_info['status'] != INVITEE) {
        echo '<div class="table-responsive">
            <table class="table table-striped table-hover">
            <thead>
            <tr>';
        echo '<th>'.get_lang('Exercises').'</th>';
        echo '<th>'.get_lang('LearningPath').'</th>';
        echo '<th>'.get_lang('AvgCourseScore').PHP_EOL
            .Display::return_icon('info3.gif', get_lang('AverageScore'), ['align' => 'absmiddle', 'hspace' => '3px'])
            .'</th>';
        echo '<th>'.get_lang('Attempts').'</th>';
        echo '<th>'.get_lang('LatestAttempt').'</th>';
        echo '<th>'.get_lang('AllAttempts').'</th>';

        $hookQuizTracking = HookMyStudentsQuizTracking::create();
        if ($hookQuizTracking) {
            $hookHeaders = array_map(
                function ($hookHeader) {
                    if (isset($hookHeader['value'])) {
                        return Display::tag('th', $hookHeader['value'], $hookHeader['attrs']);
                    }
                },
                $hookQuizTracking->notifyTrackingHeader()
            );

            echo implode(PHP_EOL, $hookHeaders);
        }

        echo '</tr></thead><tbody>';

        $csv_content[] = [];
        $csv_content[] = [
            get_lang('Exercises'),
            get_lang('LearningPath'),
            get_lang('AvgCourseScore'),
            get_lang('Attempts'),
        ];

        if ($hookQuizTracking) {
            $hookHeaders = array_map(
                function ($hookHeader) {
                    if (isset($hookHeader['value'])) {
                        return strip_tags($hookHeader['value']);
                    }
                },
                $hookQuizTracking->notifyTrackingHeader()
            );

            $csvContentIndex = count($csv_content) - 1;
            $csv_content[$csvContentIndex] = array_merge($csv_content[$csvContentIndex], $hookHeaders);
        }

        $t_quiz = Database::get_course_table(TABLE_QUIZ_TEST);
        $sessionCondition = api_get_session_condition(
            $sessionId,
            true,
            true,
            'quiz.session_id'
        );

        $sql = "SELECT quiz.title, iid
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
                $exercise_id = (int) $exercices['iid'];
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
                    $courseCode,
                    $exercise_id,
                    $sessionId,
                    1,
                    0
                );

                $lp_name = '-';
                $hookContents = $hookQuizTracking
                    ? $hookQuizTracking->notifyTrackingContent($exercise_id, $student_id)
                    : [];

                if (!isset($score_percentage) && $count_attempts > 0) {
                    $scores_lp = Tracking::get_avg_student_exercise_score(
                        $student_id,
                        $courseCode,
                        $exercise_id,
                        $sessionId,
                        2,
                        1
                    );
                    $score_percentage = $scores_lp[0];
                    $lp_name = $scores_lp[1];
                }
                $lp_name = !empty($lp_name) ? $lp_name : get_lang('NoLearnpath');

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
                        $attemptLink = '../exercise/exercise_show.php?id='.$id_last_attempt.'&cidReq='.$courseCode
                            .'&id_session='.$sessionId.'&session_id='.$sessionId.'&student='.$student_id.'&origin='
                            .(empty($origin) ? 'tracking' : $origin).$qualifyLink;
                        echo Display::url(
                            Display::return_icon('quiz.png', get_lang('Exercise')),
                            $attemptLink
                        );
                    }
                }
                echo '</td>';

                echo '<td>';
                if ($count_attempts > 0) {
                    $all_attempt_url = "../exercise/exercise_report.php?exerciseId=$exercise_id&"
                        ."cidReq=$courseCode&filter_by_user=$student_id&id_session=$sessionId";
                    echo Display::url(
                        Display::return_icon(
                            'test_results.png',
                            get_lang('AllAttempts'),
                            [],
                            ICON_SIZE_SMALL
                        ),
                        $all_attempt_url
                    );
                }
                echo '</td>';

                if (!empty($hookContents)) {
                    foreach ($hookContents as $hookContent) {
                        if (isset($hookContent['value'])) {
                            echo Display::tag('td', $hookContent['value'], $hookContent['attrs']);
                        }
                    }
                }

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

                if (!empty($hookContents)) {
                    $csvContentIndex = count($csv_content) - 1;

                    foreach ($hookContents as $hookContent) {
                        if (isset($hookContent['value'])) {
                            $csv_content[$csvContentIndex][] = strip_tags($hookContent['value']);
                        }
                    }
                }
                $i++;
            }
        } else {
            echo '<tr><td colspan="6">'.get_lang('NoExercise').'</td></tr>';
        }
        echo '</tbody></table></div>';
    }

    // @when using sessions we do not show the survey list
    if (empty($sessionId)) {
        if (!empty($survey_list)) {
            $survey_data = [];
            foreach ($survey_list as $survey) {
                $user_list = SurveyManager::get_people_who_filled_survey(
                    $survey['survey_id'],
                    false,
                    $courseInfo['real_id']
                );
                $survey_done = Display::return_icon(
                    'accept_na.png',
                    get_lang('NoAnswer'),
                    [],
                    ICON_SIZE_SMALL
                );
                if (in_array($student_id, $user_list)) {
                    $survey_done = Display::return_icon(
                        'accept.png',
                        get_lang('Answered'),
                        [],
                        ICON_SIZE_SMALL
                    );
                }
                $data = ['title' => $survey['title'], 'done' => $survey_done];
                $survey_data[] = $data;
            }

            if (!empty($survey_data)) {
                $table = new HTML_Table(['class' => 'table table-hover table-striped data_table']);
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

    $userWorks = getWorkPerUser($student_id, $courseInfo['real_id'], $sessionId);
    echo '
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>'.get_lang('Tasks').'</th>
                        <th class="text-center">'.get_lang('DocumentNumber').'</th>
                        <th class="text-center">'.get_lang('Note').'</th>
                        <th class="text-center">'.get_lang('HandedOut').'</th>
                        <th class="text-center">'.get_lang('HandOutDateLimit').'</th>
                        <th class="text-center">'.get_lang('ConsideredWorkingTime').'</th>
                    </tr>
                </thead>
                <tbody>
    ';

    foreach ($userWorks as $work) {
        $work = $work['work'];
        $showOnce = true;
        foreach ($work->user_results as $key => $results) {
            $resultId = $results['id'];
            echo '<tr>';
            echo '<td>'.$work->title.'</td>';
            $documentNumber = $key + 1;
            $url = api_get_path(WEB_CODE_PATH).'work/view.php?cidReq='.$courseCode.'&id_session='.$sessionId.'&id='
                .$resultId;
            echo '<td class="text-center"><a href="'.$url.'">('.$documentNumber.')</a></td>';
            $qualification = !empty($results['qualification']) ? $results['qualification'] : '-';
            echo '<td class="text-center">'.$qualification.'</td>';
            echo '<td class="text-center">'.api_convert_and_format_date($results['sent_date_from_db']).' '.$results['expiry_note'].'</td>';
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
                    $time = $field->getValue();
                    echo '<td class="text-center">';
                    echo $time;
                    if ($workingTimeEdit && $showOnce) {
                        $showOnce = false;
                        echo '&nbsp;'.Display::url(
                            get_lang('AddTime'),
                            $currentUrl.'&action=add_work_time&time='.$time.'&work_id='.$work->id
                        );

                        echo '&nbsp;'.Display::url(
                            get_lang('RemoveTime'),
                            $currentUrl.'&action=remove_work_time&time='.$time.'&work_id='.$work->id
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
    ';

    $csv_content[] = [];
    $csv_content[] = [
        get_lang('OtherTools'),
    ];
    $csv_content[] = [
        get_lang('Student_publication'),
        $nb_assignments,
    ];
    $csv_content[] = [
        get_lang('Messages'),
        $messages,
    ];
    $csv_content[] = [
        get_lang('LinksDetails'),
        $links,
    ];
    $csv_content[] = [
        get_lang('DocumentsDetails'),
        $documents,
    ];
    $csv_content[] = [
        get_lang('UploadedDocuments'),
        $uploaded_documents,
    ];
    $csv_content[] = [
        get_lang('ChatLastConnection'),
        $chat_last_connection,
    ];
} //end details

if ($allowMessages === true) {
    // Messages
    echo Display::page_subheader2(get_lang('Messages'));
    echo MessageManager::getMessagesAboutUserToString($user_info, 'my_space');
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

$coachAccessStartDate = null;
$coachAccessEndDate = null;

if (!empty($sessionId)) {
    $filterMessages = api_get_configuration_value('filter_interactivity_messages');

    if ($filterMessages) {
        $sessionInfo = api_get_session_info($sessionId);
        if (!empty($sessionInfo)) {
            $coachAccessStartDate = $sessionInfo['coach_access_start_date'];
            $coachAccessEndDate = $sessionInfo['coach_access_end_date'];
        }
    }
}

$allow = api_get_configuration_value('allow_user_message_tracking');
if ($allow && (api_is_drh() || api_is_platform_admin())) {
    if ($filterMessages) {
        $users = MessageManager::getUsersThatHadConversationWithUser($student_id, $coachAccessStartDate, $coachAccessEndDate);
    } else {
        $users = MessageManager::getUsersThatHadConversationWithUser($student_id);
    }
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

        if ($filterMessages) {
            $url = api_get_path(WEB_CODE_PATH).'tracking/messages.php?from_user='.$student_id.'&to_user='.$followedUserId.'&start_date='.$coachAccessStartDate.'&end_date='.$coachAccessEndDate;
        } else {
            $url = api_get_path(WEB_CODE_PATH).'tracking/messages.php?from_user='.$student_id.'&to_user='.$followedUserId;
        }

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
