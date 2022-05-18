<?php
/* For licensing terms, see /license.txt */

require_once '../inc/global.inc.php';

$allow = api_get_configuration_value('extra');
if (empty($allow)) {
    exit;
}

api_block_anonymous_users();
$export_csv = isset($_GET['export']) && 'csv' === $_GET['export'] ? true : false;
$course_code = isset($_GET['course']) ? Security::remove_XSS($_GET['course']) : null;
$_course = api_get_course_info();
$coment = '';

if (!api_is_allowed_to_create_course() &&
    !api_is_session_admin() &&
    !api_is_drh() &&
    !api_is_student_boss() &&
    !api_is_platform_admin()
) {
    // Check if the user is tutor of the course
    $user_course_status = CourseManager::get_tutor_in_course_status(
        api_get_user_id(),
        api_get_course_int_id()
    );

    // Francois Belisle Kezber...
    // If user is NOT a teacher -> student, but IS the teacher of the course... Won't have the global teacher status
    // and won't be tutor... So have to check is_course_teacher
    if (($user_course_status != 1) && !(CourseManager::is_course_teacher(api_get_user_id(), $course_code))) {
        api_not_allowed(true);
    }
}

$htmlHeadXtra[] = '<script>
function show_image(image,width,height) {
    width = parseInt(width) + 20;
    height = parseInt(height) + 20;
    window_x = window.open(image,\'windowX\',\'width=\'+ width + \', height=\'+ height + \'\');
}
</script>';

$export = isset($_GET['export']) ? $_GET['export'] : false;
$sessionId = isset($_GET['id_session']) ? intval($_GET['id_session']) : 0;
$origin = isset($_GET['origin']) ? Security::remove_XSS($_GET['origin']) : '';
$studentId = (int) $_GET['student'];
$coachId = isset($_GET['id_coach']) ? (int) $_GET['id_coach'] : 0;

// time spent on the course
$courseInfo = api_get_course_info($course_code);

if ($export) {
    ob_start();
}
$csv_content = [];
$from_myspace = false;

if (isset($_GET['from']) && 'myspace' == $_GET['from']) {
    $from_myspace = true;
    $this_section = SECTION_TRACKING;
} else {
    $this_section = SECTION_COURSES;
}

$nameTools = get_lang('StudentDetails');
$em = Database::getManager();

if (isset($_GET['details'])) {
    if ('user_course' === $origin) {
        if (empty($cidReq)) {
            $interbreadcrumb[] = [
                "url" => api_get_path(WEB_COURSE_PATH).$courseInfo['directory'],
                'name' => $courseInfo['title'],
            ];
        }
        $interbreadcrumb[] = [
            "url" => "../user/user.php?cidReq=".$course_code,
            "name" => get_lang('Users'),
        ];
    } else {
        if ('tracking_course' === $origin) {
            $interbreadcrumb[] = [
                "url" => "../tracking/courseLog.php?cidReq=".$course_code.'&id_session='.api_get_session_id(),
                "name" => get_lang('Tracking'),
            ];
        } else {
            if ($origin === 'resume_session') {
                $interbreadcrumb[] = [
                    'url' => "../session/session_list.php",
                    "name" => get_lang('SessionList'),
                ];
                $interbreadcrumb[] = [
                    'url' => "../session/resume_session.php?id_session=".$sessionId,
                    "name" => get_lang('SessionOverview'),
                ];
            } else {
                $interbreadcrumb[] = [
                    "url" => api_is_student_boss() ? "#" : "index.php",
                    "name" => get_lang('MySpace'),
                ];
                if (!empty($coachId)) {
                    $interbreadcrumb[] = [
                        "url" => "student.php?id_coach=$coachId",
                        "name" => get_lang('CoachStudents'),
                    ];
                    $interbreadcrumb[] = [
                        "url" => "myStudents.php?student=$studentId&id_coach=$coachId",
                        "name" => get_lang("StudentDetails"),
                    ];
                } else {
                    $interbreadcrumb[] = [
                        "url" => "student.php",
                        "name" => get_lang("MyStudents"),
                    ];
                    $interbreadcrumb[] = [
                        "url" => "myStudents.php?student=".$studentId,
                        "name" => get_lang("StudentDetails"),
                    ];
                }
            }
        }
    }
    $nameTools = get_lang("DetailsStudentInCourse");
} else {
    if ($origin == 'resume_session') {
        $interbreadcrumb[] = [
            'url' => "../session/session_list.php",
            "name" => get_lang('SessionList'),
        ];
        if (!empty($sessionId)) {
            $interbreadcrumb[] = [
                'url' => "../session/resume_session.php?id_session=$sessionId",
                "name" => get_lang('SessionOverview'),
            ];
        }
    } else {
        $interbreadcrumb[] = [
            "url" => api_is_student_boss() ? "#" : "index.php",
            "name" => get_lang('MySpace'),
        ];
        if (!empty($coachId)) {
            if ($sessionId) {
                $interbreadcrumb[] = [
                    "url" => "student.php?id_coach=$coachId&id_session=$sessionId",
                    "name" => get_lang("CoachStudents"),
                ];
            } else {
                $interbreadcrumb[] = [
                    "url" => "student.php?id_coach=$coachId",
                    "name" => get_lang("CoachStudents"),
                ];
            }
        } else {
            $interbreadcrumb[] = [
                "url" => "student.php",
                "name" => get_lang("MyStudents"),
            ];
        }
    }
}

// Database Table Definitions
$tbl_course_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
$tbl_stats_access = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ACCESS);
$tbl_stats_exercices = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
$tbl_personal_agenda = Database::get_main_table(TABLE_PERSONAL_AGENDA);

$TABLECALHORAIRE = Database::get_course_table(TABLE_CAL_HORAIRE);

if (isset($_GET['user_id']) && $_GET['user_id'] != '') {
    $user_id = intval($_GET['user_id']);
} else {
    $user_id = api_get_user_id();
}

// Action behaviour
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'send_legal':
        $subject = get_lang('SendLegalSubject');
        $content = sprintf(
            get_lang('SendLegalDescriptionToUrlX'),
            api_get_path(WEB_PATH)
        );
        MessageManager::send_message_simple($studentId, $subject, $content);
        Display::addFlash(Display::return_message(get_lang('Sent')));
        break;
    case 'delete_legal':
        $extraFieldValue = new ExtraFieldValue('user');
        $value = $extraFieldValue->get_values_by_handler_and_field_variable($studentId, 'legal_accept');
        $result = $extraFieldValue->delete($value['id']);
        if ($result) {
            Display::addFlash(Display::return_message(get_lang('Deleted')));
        }
        break;
    case 'reset_lp':
        $lp_id = isset($_GET['lp_id']) ? (int) $_GET['lp_id'] : '';

        if (api_is_allowed_to_edit() &&
            !empty($lp_id) &&
            !empty($studentId)
        ) {
            Event::delete_student_lp_events(
                $studentId,
                $lp_id,
                $courseInfo,
                $sessionId
            );

            // @todo delete the stats.track_e_exercises records.
            // First implement this http://support.chamilo.org/issues/1334
            $message = Display::return_message(
                get_lang('LPWasReset'),
                'success'
            );
        }
        break;
    default:
        break;
}

// user info
$user_info = api_get_user_info($studentId);
$courses_in_session = [];

//See #4676
$drh_can_access_all_courses = false;

if (api_is_drh() || api_is_platform_admin() || api_is_student_boss()) {
    $drh_can_access_all_courses = true;
}

$courses = CourseManager::get_course_list_of_user_as_course_admin(api_get_user_id());
$courses_in_session_by_coach = [];
$sessions_coached_by_user = Tracking::get_sessions_coached_by_user(api_get_user_id());

// RRHH or session admin
if (api_is_session_admin() || api_is_drh()) {
    $courses = CourseManager::get_courses_followed_by_drh(api_get_user_id());
    $session_by_session_admin = SessionManager::get_sessions_followed_by_drh(api_get_user_id());

    if (!empty($session_by_session_admin)) {
        foreach ($session_by_session_admin as $session_coached_by_user) {
            $courses_followed_by_coach = Tracking::get_courses_list_from_session($session_coached_by_user['id']);
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
            user_id = ".intval($user_info['user_id']);
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
        WHERE user_id='.intval($user_info['user_id']);
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
    api_get_course_info()
);

if (api_is_drh() && !api_is_platform_admin()) {
    if (!empty($studentId)) {
        if (!api_drh_can_access_all_session_content()) {
            if (!($isDrhOfCourse)) {
                if (api_is_drh() &&
                    !UserManager::is_user_followed_by_drh($studentId, api_get_user_id())
                ) {
                    api_not_allowed(true);
                }
            }
        }
    }
}

Display::display_header($nameTools);

if (isset($message)) {
    echo $message;
}

$token = Security::get_token();
if (!empty($studentId)) {
    // Actions bar
    echo '<div class="actions">';
    echo '<a href="javascript: window.history.go(-1);">'.
        Display::return_icon('back.png', get_lang('Back'), '', ICON_SIZE_MEDIUM).'</a>';

    echo '<a href="javascript: void(0);" onclick="javascript: window.print();">'.
        Display::return_icon('printer.png', get_lang('Print'), '', ICON_SIZE_MEDIUM).'</a>';

    echo '<a href="'.api_get_self().'?'.Security::remove_XSS($_SERVER['QUERY_STRING']).'&export=csv">'.
        Display::return_icon('export_csv.png', get_lang('ExportAsCSV'), '', ICON_SIZE_MEDIUM).'</a> ';

    echo '<a href="'.api_get_self().'?'.Security::remove_XSS($_SERVER['QUERY_STRING']).'&export=xls">'.
        Display::return_icon('export_excel.png', get_lang('ExportAsXLS'), '', ICON_SIZE_MEDIUM).'</a> ';

    if (!empty($user_info['email'])) {
        $send_mail = '<a href="mailto:'.$user_info['email'].'">'.
            Display::return_icon('mail_send.png', get_lang('SendMail'), '', ICON_SIZE_MEDIUM).'</a>';
    } else {
        $send_mail = Display::return_icon('mail_send_na.png', get_lang('SendMail'), '', ICON_SIZE_MEDIUM);
    }
    echo $send_mail;
    if (!empty($studentId) && !empty($course_code)) {
        // Only show link to connection details if course and student were defined in the URL
        echo '<a href="access_details.php?student='.$studentId.'&course='.$course_code.'&origin='.$origin.'&cidReq='.$course_code.'&id_session='.$sessionId.'">'.
            Display::return_icon('statistics.png', get_lang('AccessDetails'), '', ICON_SIZE_MEDIUM).'</a>';
    }
    if (api_can_login_as($studentId)) {
        echo '<a href="'.api_get_path(
                WEB_CODE_PATH
            ).'admin/user_list.php?action=login_as&user_id='.$studentId.'&sec_token='.$token.'">'.
            Display::return_icon('login_as.png', get_lang('LoginAs'), null, ICON_SIZE_MEDIUM).'</a>&nbsp;&nbsp;';
    }

    echo Display::url(
        Display::return_icon('skill-badges.png', get_lang('AssignSkill'), null, ICON_SIZE_MEDIUM),
        api_get_path(WEB_CODE_PATH).'badge/assign.php?'.http_build_query(['user' => $studentId])
    );
    echo '</div>';

    // is the user online ?
    if (user_is_online($studentId)) {
        $online = get_lang('Yes');
    } else {
        $online = get_lang('No');
    }

    // get average of score and average of progress by student
    $avg_student_progress = $avg_student_score = 0;
    if (CourseManager::is_user_subscribed_in_course($user_info['user_id'], $course_code, true)) {
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
        $first_connection_date = get_lang('NoConnexion');
    }

    $last_connection_date = Tracking::get_last_connection_date($user_info['user_id'], true);
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
    $table_title = Display::return_icon(
            'user.png',
            get_lang('User'),
            [],
            ICON_SIZE_SMALL
        ).$user_info['complete_name'];

    echo Display::page_subheader($table_title);

    $userPicture = UserManager::getUserPicture($user_info['user_id']);
    $userGroupManager = new UserGroup();
    $userGroups = $userGroupManager->getNameListByUser($user_info['user_id'], UserGroup::NORMAL_CLASS); ?>
    <img src="<?php echo $userPicture; ?>">
    <div class="row">
        <div class="col-sm-6">
            <table class="table table-striped table-hover">
                <thead>
                <tr>
                    <th><?php echo get_lang('Information'); ?></th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td><?php echo get_lang('Name').' : '.$user_info['complete_name']; ?></td>
                </tr>
                <tr>
                    <td><?php echo get_lang('Email').' : ';
    if (!empty($user_info['email'])) {
        echo '<a href="mailto:'.$user_info['email'].'">'.$user_info['email'].'</a>';
    } else {
        echo get_lang('NoEmail');
    } ?>
                    </td>
                </tr>
                <tr>
                    <td> <?php echo get_lang('Tel').' : ';
    if (!empty($user_info['phone'])) {
        echo $user_info['phone'];
    } else {
        echo get_lang('NoTel');
    } ?>
                    </td>
                </tr>
                <tr>
                    <td><?php echo get_lang('OfficialCode').' : ';
    if (!empty($user_info['official_code'])) {
        echo $user_info['official_code'];
    } else {
        echo get_lang('NoOfficialCode');
    } ?>
                    </td>
                </tr>
                <tr>
                    <td><?php echo get_lang('OnLine').' : '.$online; ?> </td>
                </tr>
                <?php

                // Display timezone if the user selected one and if the admin allows the use of user's timezone
                $timezone = null;
    $timezone_user = UserManager::get_extra_user_data_by_field($user_info['user_id'], 'timezone');
    $use_users_timezone = api_get_setting('use_users_timezone', 'timezones');
    if ($timezone_user['timezone'] != null && $use_users_timezone == 'true') {
        $timezone = $timezone_user['timezone'];
    }
    if ($timezone !== null) {
        ?>
                    <tr>
                        <td> <?php echo get_lang('Timezone').' : '.$timezone; ?> </td>
                    </tr>
                    <?php
    } ?>
                </tbody>
            </table>
        </div>
        <div class="col-sm-6">
            <table class="table table-striped table-hover">
                <thead>
                <tr>
                    <th colspan="2" class="text-center"><?php echo get_lang('Tracking'); ?></th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td align="right"><?php echo get_lang('FirstLoginInPlatform'); ?></td>
                    <td align="left"><?php echo $first_connection_date; ?></td>
                </tr>
                <tr>
                    <td align="right"><?php echo get_lang('LatestLoginInPlatform'); ?></td>
                    <td align="left"><?php echo $last_connection_date; ?></td>
                </tr>
                <?php if (isset($_GET['details']) && $_GET['details'] == 'true') {
        ?>
                    <tr>
                        <td align="right"><?php echo get_lang('TimeSpentInTheCourse'); ?></td>
                        <td align="left"><?php echo $time_spent_on_the_course; ?></td>
                    </tr>
                    <tr>
                        <td align="right">
                            <?php
                            echo get_lang('Progress').' ';
        Display::display_icon(
                                'info3.gif',
                                get_lang('ScormAndLPProgressTotalAverage'),
                                ['align' => 'absmiddle', 'hspace' => '3px']
                            ); ?>
                        </td>
                        <td align="left"><?php echo $avg_student_progress.'%'; ?></td>
                    </tr>
                    <tr>
                        <td align="right">
                            <?php
                            echo get_lang('Score').' ';
        Display::display_icon(
                                'info3.gif',
                                get_lang('ScormAndLPTestTotalAverage'),
                                ['align' => 'absmiddle', 'hspace' => '3px']
                            ); ?>
                        </td>
                        <td align="left"><?php
                            if (is_numeric($avg_student_score)) {
                                echo $avg_student_score.'%';
                            } else {
                                echo $avg_student_score;
                            } ?>
                        </td>
                    </tr>
                    <?php
    }

    if (api_get_setting('allow_terms_conditions') === 'true') {
        $isBoss = UserManager::userIsBossOfStudent(api_get_user_id(), $studentId);
        if ($isBoss || api_is_platform_admin()) {
            $extraFieldValue = new ExtraFieldValue('user');
            $value = $extraFieldValue->get_values_by_handler_and_field_variable(
                            $studentId,
                            'legal_accept'
                        );
            $icon = Display::return_icon('accept_na.png');
            if (isset($value['value'])) {
                list($legalId, $legalLanguageId, $legalTime) = explode(':', $value['value']);
                $icon = Display::return_icon('accept.png').' '.api_get_local_time($legalTime);
                $icon .= ' '.Display::url(
                                    get_lang('DeleteLegal'),
                                    api_get_self().'?action=delete_legal&student='.$studentId.'&course='.$course_code,
                                    ['class' => 'btn btn-danger btn-xs']
                                );
            } else {
                $icon .= ' '.Display::url(
                                    get_lang('SendLegal'),
                                    api_get_self().'?action=send_legal&student='.$studentId.'&course='.$course_code,
                                    ['class' => 'btn btn-primary btn-xs']
                                );
            }
            echo '<tr>
                        <td align="right">';
            echo get_lang('LegalAccepted').' </td>  <td align="left">'.$icon;
            echo '</td></tr>';
        }
    } ?>
                </tbody>
            </table>
            <?php if (!empty($userGroups)) {
        ?>
                <table class="table table-striped table-hover">
                    <thead>
                    <tr>
                        <th><?php echo get_lang('Classes'); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($userGroups as $class) {
            ?>
                        <tr>
                            <td><?php echo $class; ?></td>
                        </tr>
                    <?php
        } ?>
                    </tbody>
                </table>
            <?php
    } ?>
        </div>
    </div>
    <?php

    if (empty($_GET['details'])) {
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
        foreach ($courses_in_session as $sId => $courses) {
            $session_name = '';
            $access_start_date = '';
            $access_end_date = '';
            $date_session = '';
            $title = Display::return_icon('course.png', get_lang('Courses'), [], ICON_SIZE_SMALL).' '.get_lang('Courses');

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
                $title = Display::return_icon(
                        'session.png',
                        get_lang('Session'),
                        [],
                        ICON_SIZE_SMALL
                    ).' '.$session_name.($date_session ? ' ('.$date_session.')' : '');
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

            if (!empty($courses)) {
                foreach ($courses as $courseId) {
                    $courseInfoItem = api_get_course_info_by_id($courseId);
                    $courseId = $courseInfoItem['real_id'];
                    $courseCodeItem = $courseInfoItem['code'];

                    if (CourseManager::is_user_subscribed_in_course($studentId, $courseCodeItem, true)) {
                        $time_spent_on_course = api_time_to_hms(
                            Tracking::get_time_spent_on_the_course($user_info['user_id'], $courseId, $sId)
                        );

                        // get average of faults in attendances by student
                        $results_faults_avg = $attendance->get_faults_average_by_course(
                            $studentId,
                            $courseCodeItem,
                            $sId
                        );

                        if (!empty($results_faults_avg['total'])) {
                            if (api_is_drh()) {
                                $attendances_faults_avg =
                                    '<a title="'.get_lang('GoAttendance').'" href="'.api_get_path(
                                        WEB_CODE_PATH
                                    ).'attendance/index.php?cidReq='.$courseCodeItem.'&id_session='.$sId.'&student_id='.$studentId.'">'.
                                    $results_faults_avg['faults'].'/'.$results_faults_avg['total'].' ('.$results_faults_avg['porcent'].'%)</a>';
                            } else {
                                $attendances_faults_avg =
                                    $results_faults_avg['faults'].'/'.
                                    $results_faults_avg['total'].
                                    ' ('.$results_faults_avg['porcent'].'%)';
                            }
                        } else {
                            $attendances_faults_avg = '0/0 (0%)';
                        }

                        // Get evaluations by student
                        $cats = Category::load(null, null, $courseCodeItem, null, null, $sId);
                        $scoretotal = [];
                        if (isset($cats) && isset($cats[0])) {
                            if (!empty($sId)) {
                                $scoretotal = $cats[0]->calc_score($studentId, null, $courseCodeItem, $sId);
                            } else {
                                $scoretotal = $cats[0]->calc_score($studentId, null, $courseCodeItem);
                            }
                        }

                        $scoretotal_display = '0/0 (0%)';
                        if (!empty($scoretotal) && !empty($scoretotal[1])) {
                            $scoretotal_display =
                                round($scoretotal[0], 1).'/'.
                                round($scoretotal[1], 1).
                                ' ('.round(($scoretotal[0] / $scoretotal[1]) * 100, 2).' %)';
                        }

                        $progress = Tracking::get_avg_student_progress(
                            $user_info['user_id'],
                            $courseCodeItem,
                            null,
                            $sId
                        );

                        $score = Tracking::get_avg_student_score($user_info['user_id'], $courseCodeItem, [], $sId);
                        $progress = empty($progress) ? '0%' : $progress.'%';
                        $score = empty($score) ? '0%' : $score.'%';

                        $csv_content[] = [
                            $session_name,
                            $courseInfoItem['title'],
                            $time_spent_on_course,
                            $progress,
                            $score,
                            $attendances_faults_avg,
                            $scoretotal_display,
                        ];

                        echo '<tr>
                        <td ><a href="'.$courseInfoItem['course_public_url'].'?id_session='.$sId.'">'.
                            $courseInfoItem['title'].'</a></td>
                        <td >'.$time_spent_on_course.'</td>
                        <td >'.$progress.'</td>
                        <td >'.$score.'</td>
                        <td >'.$attendances_faults_avg.'</td>
                        <td >'.$scoretotal_display.'</td>';

                        if (!empty($coachId)) {
                            echo '<td width="10"><a href="'.api_get_self().
                                '?student='.$user_info['user_id'].'&details=true&course='.$courseInfoItem['code'].'&id_coach='.$coachId.
                                '&origin='.$origin.'&id_session='.$sId.'#infosStudent">
                            '.Display::return_icon('2rightarrow.png', get_lang('Details')).'</a></td>';
                        } else {
                            echo '<td width="10"><a href="'.api_get_self(
                                ).'?student='.$user_info['user_id'].'&details=true&course='.$courseInfoItem['code'].'&origin='.$origin.'&id_session='.$sId.'#infosStudent">
                            '.Display::return_icon('2rightarrow.png', get_lang('Details')).'</a></td>';
                        }
                        echo '</tr>';
                    }
                }
            } else {
                echo "<tr><td colspan='5'>".get_lang('NoCourse')."</td></tr>";
            }
            echo '</tbody>';
            echo '</table>';
            echo '</div>';
        }
    } else {
        if ($user_info['status'] != INVITEE) {
            $csv_content[] = [];
            $csv_content[] = [str_replace('&nbsp;', '', $table_title)];
            $t_lp = Database::get_course_table(TABLE_LP_MAIN);

            // csv export headers
            $csv_content[] = [];
            $csv_content[] = [
                get_lang('Learnpath'),
                get_lang('Time'),
                get_lang('AverageScore'),
                get_lang('LatestAttemptAverageScore'),
                get_lang('Progress'),
                get_lang('LastConnexion'),
            ];

            $query = $em
                ->createQuery(
                    '
                    SELECT lp FROM ChamiloCourseBundle:CLp lp
                    WHERE lp.sessionId = :session AND lp.cId = :course
                    ORDER BY lp.displayOrder ASC
                '
                );

            if (empty($sessionId)) {
                $query->setParameters(
                    [
                        'session' => 0,
                        'course' => $courseInfo['real_id'],
                    ]
                );
            } else {
                $query->setParameters(
                    [
                        'session' => $sessionId,
                        'course' => $courseInfo['real_id'],
                    ]
                );
            }

            $rs_lp = $query->getResult();

            if (count($rs_lp) > 0) {
                ?>
                <!-- LPs-->
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                        <tr>
                            <th><?php echo get_lang('LearningPath'); ?></th>
                            <th>
                                <?php
                                echo get_lang('Time').' ';
                Display::display_icon(
                                    'info3.gif',
                                    get_lang('TotalTimeByCourse'),
                                    ['align' => 'absmiddle', 'hspace' => '3px']
                                ); ?>
                            </th>
                            <th>
                                <?php
                                echo get_lang('AverageScore').' ';
                Display::display_icon(
                                    'info3.gif',
                                    get_lang('AverageIsCalculatedBasedInAllAttempts'),
                                    ['align' => 'absmiddle', 'hspace' => '3px']
                                ); ?>
                            </th>
                            <th><?php
                                echo get_lang('LatestAttemptAverageScore').' ';
                Display::display_icon(
                                    'info3.gif',
                                    get_lang('AverageIsCalculatedBasedInTheLatestAttempts'),
                                    ['align' => 'absmiddle', 'hspace' => '3px']
                                ); ?>
                            </th>
                            <th><?php
                                echo get_lang('Progress').' ';
                Display::display_icon(
                                    'info3.gif',
                                    get_lang('LPProgressScore'),
                                    ['align' => 'absmiddle', 'hspace' => '3px']
                                ); ?>
                            </th>
                            <th><?php
                                echo get_lang('LastConnexion').' ';
                Display::display_icon(
                                    'info3.gif',
                                    get_lang('LastTimeTheCourseWasUsed'),
                                    ['align' => 'absmiddle', 'hspace' => '3px']
                                ); ?>
                            </th>
                            <?php
                            echo '<th>'.get_lang('Details').'</th>';
                if (api_is_allowed_to_edit()) {
                    echo '<th>'.get_lang('ResetLP').'</th>';
                } ?>
                        </tr>
                        </thead>
                        <tbody>
                        <?php

                        $i = 0;
                foreach ($rs_lp as $learnpath) {
                    $lp_id = $learnpath->getId();
                    $lp_name = $learnpath->getName();
                    $any_result = false;

                    // Get progress in lp
                    $progress = Tracking::get_avg_student_progress(
                                $studentId,
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
                    $total_time = Tracking::get_time_spent_in_lp(
                                $studentId,
                                $course_code,
                                [$lp_id],
                                $sessionId
                            );

                    if (!empty($total_time)) {
                        $any_result = true;
                    }

                    // Get last connection time in lp
                    $start_time = Tracking::get_last_connection_time_in_lp(
                                $studentId,
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
                                $studentId,
                                $course_code,
                                [$lp_id],
                                $sessionId
                            );

                    // Latest exercise results in a LP
                    $score_latest = Tracking::get_avg_student_score(
                                $studentId,
                                $course_code,
                                [$lp_id],
                                $sessionId,
                                false,
                                true
                            );

                    if ($i % 2 == 0) {
                        $css_class = "row_even";
                    } else {
                        $css_class = "row_odd";
                    }
                    $i++;

                    // csv export content
                    $csv_content[] = [
                                api_html_entity_decode(stripslashes($lp_name), ENT_QUOTES, $charset),
                                api_time_to_hms($total_time),
                                $score.'%',
                                $score_latest.'%',
                                $progress.'%',
                                $start_time,
                            ];

                    echo '<tr class="'.$css_class.'">';
                    echo Display::tag('td', stripslashes($lp_name));
                    echo Display::tag('td', api_time_to_hms($total_time));

                    if (!is_null($score)) {
                        if (is_numeric($score)) {
                            $score = $score.'%';
                        }
                    }

                    echo Display::tag('td', $score);
                    if (!is_null($score_latest)) {
                        if (is_numeric($score_latest)) {
                            $score_latest = $score_latest.'%';
                        }
                    }
                    echo Display::tag('td', $score_latest);

                    if (is_numeric($progress)) {
                        $progress = $progress.'%';
                    } else {
                        $progress = '-';
                    }

                    echo Display::tag('td', $progress);
                    // Do not change with api_convert_and_format_date, because
                    // this value came from the lp_item_view table
                    // which implies several other changes not a priority right now
                    echo Display::tag('td', $start_time);

                    if ($any_result === true) {
                        $from = '';
                        if ($from_myspace) {
                            $from = '&from=myspace';
                        }
                        $link = Display::url(
                                    Display::return_icon('2rightarrow.png', get_lang('Details')),
                                    'lp_tracking.php?cidReq='.$course_code.'&course='.$course_code.$from.'&origin='.$origin.'&lp_id='.$learnpath->getId(
                                    ).'&student_id='.$user_info['user_id'].'&id_session='.$sessionId
                                );
                        echo Display::tag('td', $link);
                    }

                    if (api_is_allowed_to_edit()) {
                        echo '<td>';
                        if ($any_result === true) {
                            echo '<a href="myStudents.php?action=reset_lp&sec_token='.$token.
                                '&cidReq='.$course_code.
                                '&course='.$course_code.
                                '&details='.Security::remove_XSS($_GET['details']).
                                '&origin='.$origin.
                                '&lp_id='.$learnpath->getId().
                                '&student='.$user_info['user_id'].
                                '&details=true&id_session='.$sessionId.'">';
                            echo Display::return_icon(
                                            'clean.png',
                                            get_lang('Clean'),
                                            '',
                                            ICON_SIZE_SMALL
                                        ).'</a>';
                            echo '</a>';
                        }
                        echo '</td>';
                        echo '</tr>';
                    }
                    $data_learnpath[$i][] = $lp_name;
                    $data_learnpath[$i][] = $progress.'%';
                } ?>
                        </tbody>
                    </table>
                </div>
                <?php
            }
        } ?>
        <!-- line about exercises -->
        <?php if ($user_info['status'] != INVITEE) {
            ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                    <tr>
                        <th><?php echo get_lang('Exercises'); ?></th>
                        <th><?php echo get_lang('LearningPath'); ?></th>
                        <th><?php echo get_lang('AvgCourseScore').' '.Display::return_icon(
                                    'info3.gif',
                                    get_lang('AverageScore'),
                                    ['align' => 'absmiddle', 'hspace' => '3px']
                                ); ?></th>
                        <th><?php echo get_lang('Attempts'); ?></th>
                        <th><?php echo get_lang('LatestAttempt'); ?></th>
                        <th><?php echo get_lang('AllAttempts'); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $csv_content[] = [];
            $csv_content[] = [
                        get_lang('Exercises'),
                        get_lang('LearningPath'),
                        get_lang('AvgCourseScore'),
                        get_lang('Attempts'),
                    ];

            $t_quiz = Database::get_course_table(TABLE_QUIZ_TEST);
            $sessionCondition = api_get_session_condition(
                $sessionId,
                true,
                true,
                'quiz.session_id'
            );

            $sql = "SELECT quiz.title, iid FROM $t_quiz AS quiz
                            WHERE
                                quiz.c_id = {$courseInfo['real_id']} AND
                                active IN (0, 1)
                                $sessionCondition
                            ORDER BY quiz.title ASC ";

            $result_exercices = Database::query($sql);
            $i = 0;
            if (Database::num_rows($result_exercices) > 0) {
                while ($exercices = Database::fetch_array($result_exercices)) {
                    $exercise_id = intval($exercices['iid']);
                    $count_attempts = Tracking::count_student_exercise_attempts(
                                $studentId,
                                $courseInfo['real_id'],
                                $exercise_id,
                                0,
                                0,
                                $sessionId,
                                2
                            );
                    $score_percentage = Tracking::get_avg_student_exercise_score(
                                $studentId,
                                $course_code,
                                $exercise_id,
                                $sessionId,
                                1,
                                0
                            );

                    if (!isset($score_percentage) && $count_attempts > 0) {
                        $scores_lp = Tracking::get_avg_student_exercise_score(
                                    $studentId,
                                    $course_code,
                                    $exercise_id,
                                    $sessionId,
                                    2,
                                    1
                                );
                        $score_percentage = $scores_lp[0];
                        $lp_name = $scores_lp[1];
                    } else {
                        $lp_name = '-';
                    }
                    $lp_name = !empty($lp_name) ? $lp_name : get_lang('NoLearnpath');

                    if ($i % 2) {
                        $css_class = 'row_odd';
                    } else {
                        $css_class = 'row_even';
                    }

                    echo '<tr class="'.$css_class.'"><td>'.$exercices['title'].'</td>';
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

                    $sql = "SELECT exe_id FROM $tbl_stats_exercices
                            WHERE
                                exe_exo_id = $exercise_id AND
                                exe_user_id = $studentId AND
                                c_id = ".$courseInfo['real_id']." AND
                                session_id = $sessionId AND
                                status = ''
                            ORDER BY exe_date DESC
                            LIMIT 1";
                    $result_last_attempt = Database::query($sql);
                    if (Database::num_rows($result_last_attempt) > 0) {
                        $id_last_attempt = Database::result($result_last_attempt, 0, 0);
                        if ($count_attempts > 0) {
                            echo '<a href="../exercise/exercise_show.php?id='.$id_last_attempt.
                                '&cidReq='.$course_code.
                                '&session_id='.$sessionId.
                                '&student='.$studentId.
                                '&origin='.(empty($origin) ? 'tracking' : $origin).
                                '">';
                            echo Display::return_icon('quiz.gif');
                            echo '</a>';
                        }
                    }
                    echo '</td>';
                    echo '<td>';
                    $all_attempt_url = "../exercise/exercise_report.php?exerciseId=$exercise_id&cidReq=$course_code&filter_by_user=$studentId&id_session=$sessionId";
                    echo Display::url(
                                Display::return_icon(
                                    'test_results.png',
                                    get_lang('AllAttempts'),
                                    [],
                                    ICON_SIZE_SMALL
                                ),
                                $all_attempt_url
                            );

                    echo '</td></tr>';
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
                echo '<tr><td colspan="6">'.get_lang('NoExercise').'</td></tr>';
            } ?>
                    </tbody>
                </table>
            </div>
            <?php
        }

        // @when using sessions we do not show the survey list
        if (empty($sessionId)) {
            $survey_list = SurveyManager::get_surveys($course_code, $sessionId);
            $survey_data = [];
            foreach ($survey_list as $survey) {
                $user_list = SurveyManager::get_people_who_filled_survey(
                    $survey['survey_id'],
                    false,
                    $courseInfo['real_id']
                );
                $survey_done = Display::return_icon("accept_na.png", get_lang('NoAnswer'), [], ICON_SIZE_SMALL);
                if (in_array($studentId, $user_list)) {
                    $survey_done = Display::return_icon("accept.png", get_lang('Answered'), [], ICON_SIZE_SMALL);
                }
                $data = ['title' => $survey['title'], 'done' => $survey_done];
                $survey_data[] = $data;
            }

            if (!empty($survey_list)) {
                $table = new HTML_Table(['class' => 'table table-hover table-striped data_table']);
                $header_names = [get_lang('Survey'), get_lang('Answered')];
                $row = 0;
                $column = 0;
                foreach ($header_names as $item) {
                    $table->setHeaderContents($row, $column, $item);
                    $column++;
                }
                $row = 1;
                if (!empty($survey_data)) {
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
                }
                echo $table->toHtml();
            }
        }

        // line about other tools?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                <tr>
                    <th colspan="2"><?php echo get_lang('OtherTools'); ?></th>
                </tr>
                </thead>
                <tbody>
                <?php
                $csv_content[] = [];
        $nb_assignments = Tracking::count_student_assignments($studentId, $course_code, $sessionId);
        $messages = Tracking::count_student_messages($studentId, $course_code, $sessionId);
        $links = Tracking::count_student_visited_links($studentId, $courseInfo['real_id'], $sessionId);
        $chat_last_connection = Tracking::chat_last_connection($studentId, $courseInfo['real_id'], $sessionId);
        $documents = Tracking::count_student_downloaded_documents(
                    $studentId,
                    $courseInfo['real_id'],
                    $sessionId
                );
        $uploaded_documents = Tracking::count_student_uploaded_documents($studentId, $course_code, $sessionId);
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
                ]; ?>
                <tr><!-- assignments -->
                    <td width="40%"><?php echo get_lang('Student_publication'); ?></td>
                    <td><?php echo $nb_assignments; ?></td>
                </tr>
                <tr><!-- messages -->
                    <td><?php echo get_lang('Forum').' - '.get_lang('NumberOfPostsForThisUser'); ?></td>
                    <td><?php echo $messages; ?></td>
                </tr>
                <tr><!-- links -->
                    <td><?php echo get_lang('LinksDetails'); ?></td>
                    <td><?php echo $links; ?></td>
                </tr>
                <tr><!-- downloaded documents -->
                    <td><?php echo get_lang('DocumentsDetails'); ?></td>
                    <td><?php echo $documents; ?></td>
                </tr>
                <tr><!-- uploaded documents -->
                    <td><?php echo get_lang('UploadedDocuments'); ?></td>
                    <td><?php echo $uploaded_documents; ?></td>
                </tr>
                <tr><!-- Chats -->
                    <td><?php echo get_lang('ChatLastConnection'); ?></td>
                    <td><?php echo $chat_last_connection; ?></td>
                </tr>
                </tbody>
            </table>
        </div>
        <?php
    } //end details

    echo Tracking::displayUserSkills(
        $user_info['user_id'],
        $courseInfo ? $courseInfo['real_id'] : 0,
        $sessionId
    );
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
//Adding AB stuff 2016-12-14 CEMEQ
$TABLECALHORAIRE = 'c_cal_horaire';

$coursesList = [];
//On cherche le calendrier pour ce user dans ce cours, groupe
$sql = "SELECT * FROM user WHERE user_id = $studentId";
$result = Database::query($sql);
$horaire_id = Database::fetch_array($result);
$nom_hor = $horaire_id['official_code'];
$course_code_real = $_course['real_id'];
//avec le nom d'horaire= official code, on trouve le nombre de jour a faire
$sql = "SELECT * FROM $TABLECALHORAIRE
        where
          name = '$nom_hor' and
          c_id = $course_code_real ";

$res = Database::query($sql);
$resulta = Database::fetch_array($res);

$num_hours = $resulta['num_hours'];
$num_minute = $resulta['num_minute'];
if ($num_minute == '0') {
    $num_minute = '1';
}
$minute_mod = $num_hours * 60;
$num_days = 0;
if (!empty($num_minute)) {
    $num_days = $minute_mod / $num_minute;
}

// affichage des jours complétés dans les parcours l'élève
//on recherche les cours où sont inscrit les user
$sql2 = "SELECT c_id, user_id FROM course_rel_user WHERE user_id = $studentId";

$result2 = Database::query($sql2);
$Total = 0;
while ($a_courses = Database::fetch_array($result2)) {
    $Courses_code = $a_courses['c_id'];
    //on sort le c_id avec le code du cours
    $sql8 = "SELECT * FROM course WHERE code = '$Courses_code'
        ";
    $result8 = Database::query($sql8);
    $course_code_id = Database::fetch_array($result8);
    $c_id = $Courses_code;
    // pours chaque cours dans lequel il est inscrit, on cherche les jours complétés

    $Req1 = "SELECT * FROM c_lp_view
            WHERE user_id = $studentId AND c_id = $c_id";
    $res = Database::query($Req1);

    while ($result = Database::fetch_array($res)) {
        $lp_id = $result['lp_id'];
        $lp_id_view = $result['id'];
        $c_id_view = $result['c_id'];

        $Req2 = "SELECT id, lp_id ,title ,item_type
                 FROM  c_lp_item
                 WHERE lp_id =  $lp_id
                 AND title LIKE '(+)%'
                 AND c_id = $c_id_view
                 AND item_type = 'document'
            ";
        $res2 = Database::query($Req2);

        while ($resulta = Database::fetch_array($res2)) {
            $lp_item_id = $resulta['id'];
            $Req3 = "SELECT MAX(id)
                      FROM c_lp_item_view
                      WHERE
                        lp_item_id =  $lp_item_id AND
                        lp_view_id =  $lp_id_view AND
                        c_id = $c_id_view AND
                        status =  'completed'
                      ";
            $res3 = Database::query($Req3);
            while ($resul = Database::fetch_array($res3)) {
                $max = $resul['0'];
                $Req4 = "SELECT COUNT( id )
                         FROM  c_lp_item_view
                         WHERE
                            id = $max AND
                            c_id = $c_id_view";
                $res4 = Database::query($Req4);
                while ($resultat = Database::fetch_array($res4)) {
                    $Total = $Total + $resultat[0];
                }
            }
        }
    }
}

api_display_tool_title($nameTools);
$tbl_personal_agenda = Database::get_main_table(TABLE_PERSONAL_AGENDA);
$now = date('Y-m-d');
//on compte le nombre de m% dans l'agenda pour chaque module
$sqljtot = "SELECT COUNT( * ) AS TOT
             FROM $tbl_personal_agenda
             WHERE user = $studentId
             And title like 'm%'";
$resultjt = Database::query($sqljtot);
$jour_realise = 0;
while ($jtot = Database::fetch_array($resultjt)) {
    $jour_realise_tot = ($jour_realise + $jtot['TOT']) / 2;
}
//fin des jour de l'agenda
//recherche du jour inséré dans agenda par le calendrier
$jour_agenda = '';
$tour = -1;
while ($jour_agenda == '') {
    $tour++;
    $date = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") - $tour, date("Y")));
    $sql4 = "SELECT *  FROM $tbl_personal_agenda
             WHERE
              user = $studentId AND
                text='Pour le calendrier, ne pas effacer' AND
                date like '".$date." %:%' ";
    $result4 = Database::query($sql4);
    $res4 = Database::fetch_array($result4);
    $jour_agenda = $res4['title'];
    if ($tour > 300) {
        break;
    }
}

$diff = $jour_agenda - $Total;
if ($diff > 0) {
    $sing = get_lang('retard');
} else {
    $sing = get_lang('avance');
}
$diff = abs($diff);

//pour trouver la date de fin de sa formation, on ajoute les retard (%diff) au nombre de jours total de la formation et
//on regarde dans son agenda pour cette valeur qui donne ainsi la date
$diff2 = $jour_agenda - $Total;
$goto = $num_days + $diff2;
$goto = number_format($goto);
$sqlgo = "SELECT *  FROM $tbl_personal_agenda
         WHERE user = $studentId
            AND title = '".$goto."'
         ";
$result7 = Database::query($sqlgo);
$res7 = Database::fetch_array($result7);
$end_dates = $res7['date'];
$end_date = date("Y-m-d", strtotime($end_dates));

if ($end_date < '2010-01-01') {
    $end_date = get_lang('hors_cal');
}

?>
<table class="table table-hover table-striped data_table">
    <th rowspan="6">
        <?php
        //on récupere les points de controle de l'élève
        /*$pt[] = '0';
        $pt[] = '0';
        $sqlcontrole = "SELECT diff
                         FROM $tbl_stats_exercices
                         WHERE exe_user_id = ".$studentId."
                         AND diff  != ''
                         ORDER BY exe_date ASC
                         ";
        $result = Database::query($sqlcontrole);
        while ($ptctl = Database::fetch_array($result)) {
            $pt[] = $ptctl ['diff'];
        }

        //graphique de suivi

        include "../inc/teechartphp/sources/TChart.php";

        $chart = new TChart(500, 300);
        $chart->getAspect()->setView3D(false);
        $chart->getHeader()->setText("Graphique de suivi");


        $chart->getAxes()->getLeft()->setMinimumOffset(10);
        $chart->getAxes()->getLeft()->setMaximumOffset(10);

        $chart->getAxes()->getBottom()->setMinimumOffset(10);
        $chart->getAxes()->getBottom()->setMaximumOffset(10);

        $line1 = new Line($chart->getChart());
        $data = $pt;
        $line1->addArray($data);
        foreach ($chart->getSeries() as $serie) {
            $pointer = $serie->getPointer();
            $pointer->setVisible(true);
            $pointer->getPen()->setVisible(false);
            $pointer->setHorizSize(2);
            $pointer->setVertSize(2);

            $marks = $serie->getMarks();
            $marks->setVisible(true);
            $marks->setArrowLength(5);
            $marks->getArrow()->setVisible(false);
            $marks->setTransparent(true);
        }
        $x = $student_id;
        $line1->getPointer()->setStyle(PointerStyle::$CIRCLE);
        $chart->getLegend()->setVisible(false);
        $chart->render("../garbage/$x-image.png");
        $rand = rand();
        print '<img src="../garbage/'.$x.'-image.png?rand='.$rand.'">';
        */
        ?>
        <tr>
            <th align="left" width="412">
                <?php echo get_lang('Cumulatif_agenda'); ?>: <b><font
                            color=#CC0000> <?php echo $jour_realise_tot; ?></font></b></p>
            </th>
        </tr>
        <tr>
            <th align="left">
                <?php echo get_lang('Cumulatif'); ?> <b><font color=#CC0000> <?php echo $Total; ?></font></b></p>
            </th>
        </tr>
        <tr>
            <th align="left">
                <?php echo get_lang('jours_selon_horaire'); ?>: <b><font
                            color=#CC0000> <?php echo $jour_agenda; ?><?php echo $Days; ?></font></b></p>
            </th>
        </tr>
        <tr>
            <th align="left">
                <?php echo get_lang('dif2'); ?>: <b><font
                            color=#CC0000> <?php echo $diff; ?><?php echo $Days, $sing; ?></font></b></p>
            </th>
        </tr>
        <tr>
            <th align="left">
                <?php echo get_lang('date_fin'); ?>: <b><font color=#CC0000> <?php echo $end_date; ?></font></b></p>
            </th>
        </tr>
    </th>
</table>
<hr>
<br>
<form action="create_intervention.php" method="post" name="create_intervention">
    <table class='table table-hover table-striped data_table'>
        <tr>
            <th colspan="6">
                <?php echo get_lang('create_interventions_commentaires');
                echo $user_info['complete_name']; ?>
            </th>
        <tr>
            <th><?php echo get_lang('level'); ?></th>
            <th><?php echo get_lang('lang_date'); ?></th>
            <th><?php echo get_lang('consignes_interventions'); ?></th>
            <th><?php echo get_lang('Actions'); ?></th>
        </tr>
        <tr>
            <td>
                <select name="level">
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                </select>
            </td>
            <td>
                <input type="text" name="date" value="<?php echo date("Y-m-d"); ?>">
            </td>
            <td><textarea name="inter_coment" style="width:90%;" rows="2"> </textarea></td>
            <INPUT type=hidden name=ex_user_id value= <?php echo $studentId; ?>>
            <td><input type="SUBMIT" value="Sauvegarder" name="B1"></td>
        </tr>
    </table>
</form>
<?php

// formulaire d'édition des commentaires
?>
<form>
    <table class='table table-hover table-striped data_table'>
        <tr>
            <th><?php echo get_lang('level'); ?> </th>
            <th>
                <?php echo get_lang('lang_date'); ?>
            </th>
            <th>
                <?php echo get_lang('interventions_commentaires'); ?>
            </th>
            <th>
                <?php echo get_lang('Actions'); ?>
            </th>
        </tr>

        <?php
        $tbl_stats_exercices = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $sqlinter = "SELECT *
                     FROM $tbl_stats_exercices
                     WHERE
                        exe_user_id = $studentId AND
                        level != 0
                     ORDER BY exe_date ASC, level ASC
                             ";
        $resultinter = Database::query($sqlinter);
        $mod_no = null;
        while ($a_inter = Database::fetch_array($resultinter)) {
            $level = $a_inter['level'];
            $mod_no = $a_inter['mod_no'];
            $inter_coment = stripslashes($a_inter['inter_coment']);
            $inter_date = substr($a_inter['exe_date'], 0, 11);
            echo "
                <tr><center>
                    <td> ".$a_inter['level']."
                    </td>
                <td>
                     $inter_date
                    </td>

                    <td>$inter_coment
                </td>
                ";
            $exe_id = $a_inter['exe_id']; ?>
        <td class="highlight">
            <a href="edit_intervention.php?num=<?php echo $exe_id; ?>&student_id=<?php echo $studentId; ?> ">
               <?php echo Display::return_icon('edit.png', get_lang('Edit')); ?></a>
        &nbsp;&nbsp;  <a
                href="delete_exam.php?num=<?php echo $exe_id; ?>&student_id=<?php echo $studentId; ?>">
                <img
                src="../img/delete.gif" border="0"
                onClick='return confirmDelete2()'>
            </a>
            <?php
        }
            ?>
        </td>
</form>
<?php

$table_title = '';
if (!empty($session_id)) {
    $session_name = api_get_session_name($session_id);
    $table_title = ($session_name ? Display::return_icon(
            'session.png',
            get_lang('Session'),
            [],
            ICON_SIZE_SMALL
        ).' '.$session_name.' ' : '');
}
if (!empty($info_course['title'])) {
    $table_title .= ($info_course ? Display::return_icon(
            'course.png',
            get_lang('Course'),
            [],
            ICON_SIZE_SMALL
        ).' '.$info_course['title'].'  ' : '');
}

echo Display::page_subheader($table_title);

if (empty($_GET['details'])) {
    $csv_content[] = [];
    $csv_content[] = [
        get_lang('Session', ''),
        get_lang('Course', ''),
        get_lang('Time', ''),
        get_lang('Progress', ''),
        get_lang('Score', ''),
        get_lang('AttendancesFaults', ''),
        get_lang('Evaluations'),
    ];

    $attendance = new Attendance();
    foreach ($courses_in_session as $key => $courses) {
        $session_id = $key;
        $session_info = api_get_session_info($session_id);
        $session_name = '';
        if (!empty($session_info)) {
            $session_name = $session_info['name'];
        }
        $date_start = '';

        if (!empty($session_info['date_start']) && $session_info['date_start'] != '0000-00-00') {
            $date_start = api_format_date($session_info['date_start'], DATE_FORMAT_SHORT);
        }

        $date_end = '';
        if (!empty($session_info['date_end']) && $session_info['date_end'] != '0000-00-00') {
            $date_end = api_format_date($session_info['date_end'], DATE_FORMAT_SHORT);
        }
        if (!empty($date_start) && !empty($date_end)) {
            $date_session = get_lang('From').' '.$date_start.' '.get_lang('Until').' '.$date_end;
        }
        $title = '';
        if (empty($session_id)) {
            $title = Display::return_icon('course.png', get_lang('Course'), [], ICON_SIZE_SMALL).' '.get_lang(
                    'Course'
                );
        } else {
            $title = Display::return_icon(
                    'session.png',
                    get_lang('Session'),
                    [],
                    ICON_SIZE_SMALL
                ).' '.$session_name.($date_session ? ' ('.$date_session.')' : '');
        }

        // Courses
        echo '<table class="table table-hover table-striped data_table">';
        echo '<h3>'.$title.'</h3>';
        echo '<tr>
                <th>'.get_lang('Course').'</th>
                <th>'.get_lang('Time').'</th>
                <th>'.get_lang('Score').'</th>
                <th>'.get_lang('FirstConnexion').'</th>
                <th>'.get_lang('Progress').'</th>
                <th>'.get_lang('fin_mod_prevue').'</th>
                <th>'.get_lang('Details').'</th>
            </tr>';

        if (!empty($courses)) {
            foreach ($courses as $course_code) {
                if (CourseManager::is_user_subscribed_in_course($studentId, $course_code, true)) {
                    $course_info = CourseManager::get_course_information($course_code);
                    $time_spent_on_course = api_time_to_hms(
                        Tracking::get_time_spent_on_the_course($user_info['user_id'], $course_code, $session_id)
                    );
                    //on sort le c_id avec le code du cours
                    $sql8 = "SELECT *
                      FROM course
                      WHERE code = '  $course_code'
                    ";
                    $result8 = Database::query($sql8);
                    $course_code_id = Database::fetch_array($result8);
                    $c_id = $course_code_id['id'];

                    //  firts connection date
                    $sql2 = 'SELECT STR_TO_DATE(access_date,"%Y-%m-%d")
                              FROM '.$tbl_stats_access.'
                                WHERE access_user_id = '.$studentId.'
                                AND c_id = '.$c_id.'
                                    ORDER BY access_id ASC LIMIT 0,1
                    ';
                    $rs2 = Database::query($sql2);
                    $first_connection_date_to_module = Database::result($rs2, 0, 0);
                    //pour trouver la date de fin prévue du module
                    $end_date_module = get_lang('hors_cal');
                    //avec le nom d'horaire= official code, on trouve la date de
                    // début de chaque module nombre de jour a faire
                    $sql = "SELECT * FROM c_cal_dates
                            where horaire_name = '$nom_hor'
                             and c_id = '$c_id'
                             AND date = date_format('$first_connection_date_to_module','%Y-%m-%d')
                             ";
                    $res = Database::query($sql);
                    $resulta = Database::fetch_array($res);
                    $date_debut = $resulta['date'];
                    //on trouve le nombre de jour pour ce module
                    $sql = "SELECT * FROM c_cal_set_module where c_id = '$c_id'";
                    $res = Database::query($sql);
                    $resulta = Database::fetch_array($res);
                    $nombre_heure = $resulta['minutes'];
                    // on trouve le nombre de minute par jour
                    // Julio
                    /*$sql = "SELECT * FROM c_cal_horaire where c_id = '$course_code_real'";
                    $res = Database::query($sql);
                    $resulta = Database::fetch_array($res);
                    $nombre_minutes = $resulta['num_minute'];*/
                    $nombre_minutes = 0;
                    //on calcule le nombre de jour par module
                    $nombre_jours_module = $nombre_heure * '60' / $nombre_minutes;
                    //on arrondi
                    $nombre_jours_module = number_format($nombre_jours_module, 0);
                    //on trouve la date de fin de chaque module AND date = date_format('$first_connection_date_to_module','%Y-%m-%d')
                    $sql = "SELECT * FROM `c_cal_dates`
                            WHERE
                                horaire_name = '$nom_hor' AND
                                c_id = '$course_code_real' AND
                                STR_TO_DATE(date,'%Y-%m-%d') >= STR_TO_DATE('$first_connection_date_to_module','%Y-%m-%d')
                            ORDER BY STR_TO_DATE(date, '%Y-%m-%d') ASC ";
                    $res = Database::query($sql);
                    mysql_data_seek($res, $nombre_jours_module);
                    $row = mysql_fetch_row($res);
                    $end_date_module = $row[1];
                    //fin de trouver la date de fin prévue du module
                    // get average of faults in attendances by student
                    $results_faults_avg = $attendance->get_faults_average_by_course(
                        $studentId,
                        $course_code,
                        $session_id
                    );
                    if (!empty($results_faults_avg['total'])) {
                        if (api_is_drh()) {
                            $attendances_faults_avg = '<a title="'.get_lang('GoAttendance').'" href="'.api_get_path(
                                    WEB_CODE_PATH
                                ).'attendance/index.php?cidReq='.$course_code.'&id_session='.$session_id.'&student_id='.$studentId.'">'.$results_faults_avg['faults'].'/'.$results_faults_avg['total'].' ('.$results_faults_avg['porcent'].'%)</a>';
                        } else {
                            $attendances_faults_avg = $results_faults_avg['faults'].'/'.$results_faults_avg['total'].' ('.$results_faults_avg['porcent'].'%)';
                        }
                    } else {
                        $attendances_faults_avg = '0/0 (0%)';
                    }

                    // Get evaluations by student
                    $cats = Category::load(null, null, $course_code, null, null, $session_id);

                    $scoretotal = [];
                    if (isset($cats) && isset($cats[0])) {
                        if (!empty($session_id)) {
                            $scoretotal = $cats[0]->calc_score($studentId, $course_code, $session_id);
                        } else {
                            $scoretotal = $cats[0]->calc_score($studentId, $course_code);
                        }
                    }

                    $scoretotal_display = '0/0 (0%)';
                    if (!empty($scoretotal)) {
                        $scoretotal_display = round($scoretotal[0], 1).'/'.round($scoretotal[1], 1).' ('.round(
                                ($scoretotal[0] / $scoretotal[1]) * 100,
                                2
                            ).' %)';
                    }

                    $progress = Tracking::get_avg_student_progress(
                        $user_info['user_id'],
                        $course_code,
                        null,
                        $session_id
                    );
                    $score = Tracking::get_avg_student_score($user_info['user_id'], $course_code, null, $session_id);
                    $progress = empty($progress) ? '0%' : $progress.'%';
                    $score = empty($score) ? '0%' : $score.'%';

                    $csv_content[] = [
                        $session_name,
                        $course_info['title'],
                        $time_spent_on_course,
                        $progress,
                        $score,
                        $attendances_faults_avg,
                        $scoretotal_display,
                    ];
                    $warming = '';
                    $today = date('Y-m-d');

                    if ($end_date_module <= $today and $progress != '100%') {
                        $warming = '<b><font color=#CC0000>  '.get_lang('limite_atteinte').'</font></b>';
                    }

                    $end_date_module = $end_date_module.$warming;

                    echo '<tr>
                    <td >'.$course_info['title'].'</td>
                    <td >'.$time_spent_on_course.'</td>
                    <td >'.$score.'</td>
                    <td >'.$first_connection_date_to_module.'</td>
                    <td >'.$progress.'</td>
                    <td >'.$end_date_module.'</td>';
                    if (!empty($coachId)) {
                        echo '<td width="10">
                        <a href="'.api_get_self().'?student='.$user_info['user_id'].'&details=true&course='.$course_info['code'].'&id_coach='.$coachId.
                            '&origin='.Security::remove_XSS($_GET['origin']).'&id_session='.$session_id.'#infosStudent">'.
                            Display::return_icon('2rightarrow.png', get_lang('Details')).'</a></td>';
                    } else {
                        echo '<td width="10"><a href="'.api_get_self(
                            ).'?student='.$user_info['user_id'].'&details=true&course='.$course_info['code'].'&origin='.Security::remove_XSS(
                                $_GET['origin']
                            ).'&id_session='.$session_id.'#infosStudent">'.
                            Display::return_icon('2rightarrow.png', get_lang('Details')).'</a></td>';
                    }
                    echo '</tr>';
                }
            }
        } else {
            echo "<tr><td colspan='5'>".get_lang('NoCourse')."</td></tr>";
        }
        echo '</table>';
    }
} else {
    $csv_content[] = [];
    $csv_content[] = [str_replace('&nbsp;', '', $table_title)];
    $t_lp = Database::get_course_table(TABLE_LP_MAIN);

    // csv export headers
    $csv_content[] = [];
    $csv_content[] = [
        get_lang('Learnpath'),
        get_lang('Time'),
        get_lang('AverageScore'),
        get_lang('LatestAttemptAverageScore'),
        get_lang('Progress'),
        get_lang('LastConnexion'),
    ];

    $sql8 = "SELECT * FROM course WHERE code = '$course_code'";
    $result8 = Database::query($sql8);
    $course_code_id = Database::fetch_array($result8);
    $c_id = $course_code_id['id'];
    if (empty($session_id)) {
        $sql_lp = " SELECT lp.name, lp.id FROM $t_lp lp
                    WHERE session_id = 0 AND c_id = $c_id
                    ORDER BY lp.display_order";
    } else {
        $sql_lp = " SELECT lp.name, lp.id FROM $t_lp lp
                    WHERE c_id =  $c_id
                    ORDER BY lp.display_order";
    }

    $rs_lp = Database::query($sql_lp);
    if (Database::num_rows($rs_lp) > 0) {
        ?>
        <!-- LPs-->
        <table class="table table-hover table-striped data_table">
        <tr>
            <th><?php echo get_lang('Learnpaths'); ?></th>
            <th><?php echo get_lang('FirstConnexion'); ?></th>
            <th><?php echo get_lang('Time').' ';
        Display::display_icon(
                    'info3.gif',
                    get_lang('TotalTimeByCourse'),
                    ['align' => 'absmiddle', 'hspace' => '3px']
                ); ?></th>
            <th><?php echo get_lang('AverageScore').' ';
        Display::display_icon(
                    'info3.gif',
                    get_lang('AverageIsCalculatedBasedInAllAttempts'),
                    ['align' => 'absmiddle', 'hspace' => '3px']
                ); ?></th>
            <th><?php echo get_lang('LatestAttemptAverageScore').' ';
        Display::display_icon(
                    'info3.gif',
                    get_lang('AverageIsCalculatedBasedInTheLatestAttempts'),
                    ['align' => 'absmiddle', 'hspace' => '3px']
                ); ?></th>
            <th><?php echo get_lang('Progress').' ';
        Display::display_icon(
                    'info3.gif',
                    get_lang('LPProgressScore'),
                    ['align' => 'absmiddle', 'hspace' => '3px']
                ); ?></th>
            <th><?php echo get_lang('LastConnexion').' ';
        Display::display_icon(
                    'info3.gif',
                    get_lang('LastTimeTheCourseWasUsed'),
                    ['align' => 'absmiddle', 'hspace' => '3px']
                ); ?></th>
            <?php
            echo '<th>'.get_lang('Details').'</th>';
        if (api_is_allowed_to_edit()) {
            echo '<th>'.get_lang('ResetLP').'</th>';
        } ?>
        </tr>
        <?php

        //  firts connection date
        $sql2 = 'SELECT access_date
                FROM '.$tbl_stats_access.'
                WHERE access_user_id = '.$studentId.'
                AND c_id = '.$c_id.'
                ORDER BY access_id ASC LIMIT 0,1
                ';

        $rs2 = Database::query($sql2);
        $first_connection_date_to_module = Database::result($rs2, 0, 0);
        $i = 0;
        while ($learnpath = Database::fetch_array($rs_lp)) {
            $lp_id = intval($learnpath['id']);
            $lp_name = $learnpath['name'];
            $any_result = false;

            // Get progress in lp
            $progress = Tracking::get_avg_student_progress(
                $studentId,
                $course_code,
                [$lp_id],
                $session_id
            );

            if ($progress === null) {
                $progress = '0%';
            } else {
                $any_result = true;
            }

            // Get time in lp
            $total_time = Tracking::get_time_spent_in_lp(
                $studentId,
                $course_code,
                [$lp_id],
                $session_id
            );

            if (!empty($total_time)) {
                $any_result = true;
            }

            // Get last connection time in lp
            $start_time = Tracking::get_last_connection_time_in_lp(
                $studentId,
                $course_code,
                $lp_id,
                $session_id
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
                $course_code,
                [$lp_id],
                $session_id
            );

            // Latest exercise results in a LP
            $score_latest = Tracking::get_avg_student_score(
                $studentId,
                $course_code,
                [$lp_id],
                $session_id,
                false,
                true
            );

            if ($i % 2 == 0) {
                $css_class = "row_even";
            } else {
                $css_class = "row_odd";
            }

            $i++;
            // csv export content
            $csv_content[] = [
                api_html_entity_decode(stripslashes($lp_name), ENT_QUOTES, $charset),
                api_time_to_hms($total_time),
                $score.'%',
                $score_latest.'%',
                $progress.'%',
                $start_time,
            ];

            echo '<tr class="'.$css_class.'">';

            echo Display::tag('td', stripslashes($lp_name));
            echo Display::tag('td', stripslashes($first_connection_date_to_module));
            echo Display::tag('td', api_time_to_hms($total_time));

            if (!is_null($score)) {
                if (is_numeric($score)) {
                    $score = $score.'%';
                }
            }
            echo Display::tag('td', $score);

            if (!is_null($score_latest)) {
                if (is_numeric($score_latest)) {
                    $score_latest = $score_latest.'%';
                }
            }
            echo Display::tag('td', $score_latest);

            if (is_numeric($progress)) {
                $progress = $progress.'%';
            } else {
                $progress = '-';
            }

            echo Display::tag('td', $progress);
            //Do not change with api_convert_and_format_date, because this value came from the lp_item_view table
            //which implies several other changes not a priority right now
            echo Display::tag('td', $start_time);

            if ($any_result === true) {
                $from = '';
                if ($from_myspace) {
                    $from = '&from=myspace';
                }
                $link = Display::url(
                            Display::return_icon('2rightarrow.png', get_lang('Details')),
                    'lp_tracking.php?cidReq='.Security::remove_XSS($_GET['course']).'&course='.Security::remove_XSS(
                        $_GET['course']
                    ).$from.'&origin='.Security::remove_XSS(
                        $_GET['origin']
                    ).'&lp_id='.$learnpath['id'].'&student_id='.$user_info['user_id'].'&id_session='.$session_id
                );
                echo Display::tag('td', $link);
            }

            if (api_is_allowed_to_edit()) {
                echo '<td>';
                if ($any_result === true) {
                    echo '<a href="myStudents.php?action=reset_lp&sec_token='.$token.'&cidReq='.Security::remove_XSS(
                            $_GET['course']
                        ).'&course='.Security::remove_XSS($_GET['course']).'&details='.Security::remove_XSS(
                            $_GET['details']
                        ).'&origin='.Security::remove_XSS(
                            $_GET['origin']
                        ).'&lp_id='.$learnpath['id'].'&student='.$user_info['user_id'].'&details=true&id_session='.Security::remove_XSS(
                            $_GET['id_session']
                        ).'">';
                    echo Display::return_icon('clean.png', get_lang('Clean'), '', ICON_SIZE_SMALL).'</a>';
                    echo '</a>';
                }
                echo '</td>';
                echo '</tr>';
            }
            $data_learnpath[$i][] = $lp_name;
            $data_learnpath[$i][] = $progress.'%';
        }
    } else {
        //echo '<tr><td colspan="6">'.get_lang('NoLearnpath').'</td></tr>';
    } ?>
    </table>
    <!-- line about exercises -->
    <table class="table table-hover table-striped data_table">
        <tr>
            <th><?php echo get_lang('Exercises'); ?></th>
            <th><?php echo get_lang('LearningPath'); ?></th>
            <th><?php echo get_lang('AvgCourseScore').' '.Display::return_icon(
                        'info3.gif',
                        get_lang('AverageScore'),
                        ['align' => 'absmiddle', 'hspace' => '3px']
                    ); ?></th>
            <th><?php echo get_lang('Attempts'); ?></th>
            <th><?php echo get_lang('LatestAttempt'); ?></th>
            <th><?php echo get_lang('AllAttempts'); ?></th>
        </tr>
        <?php

        $csv_content[] = [];
    $csv_content[] = [
            get_lang('Exercises'),
            get_lang('LearningPath'),
            get_lang('AvgCourseScore'),
            get_lang('Attempts'),
        ];

    $t_quiz = Database::get_course_table(TABLE_QUIZ_TEST);
    $sql = "SELECT quiz.title, iid FROM $t_quiz AS quiz
                WHERE
                    quiz.c_id = $c_id AND
                    (quiz.session_id = $session_id OR quiz.session_id = 0) AND
                    active IN (0, 1)
                ORDER BY quiz.title ASC ";
    $result_exercices = Database::query($sql);
    $i = 0;
    if (Database::num_rows($result_exercices) > 0) {
        while ($exercices = Database::fetch_array($result_exercices)) {
            $exercise_id = intval($exercices['iid']);
            $count_attempts = Tracking::count_student_exercise_attempts(
                    $studentId,
                    $course_code,
                    $exercise_id,
                    0,
                    0,
                    $session_id,
                    2
                );
            $score_percentage = Tracking::get_avg_student_exercise_score(
                    $studentId,
                    $course_code,
                    $exercise_id,
                    $session_id,
                    1,
                    0
                );

            if (!isset($score_percentage) && $count_attempts > 0) {
                $scores_lp = Tracking::get_avg_student_exercise_score(
                        $studentId,
                        $course_code,
                        $exercise_id,
                        $session_id,
                        2,
                        1
                    );
                $score_percentage = $scores_lp[0];
                $lp_name = $scores_lp[1];
            } else {
                $lp_name = '-';
            }
            $lp_name = !empty($lp_name) ? $lp_name : get_lang('NoLearnpath');

            if ($i % 2) {
                $css_class = 'row_odd';
            } else {
                $css_class = 'row_even';
            }

            echo '<tr class="'.$css_class.'"><td>'.$exercices['title'].'</td>';
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

            $sql_last_attempt = 'SELECT exe_id FROM '.$tbl_stats_exercices.'
                                     WHERE  exe_exo_id      ="'.$exercise_id.'" AND
                                            exe_user_id     ="'.$studentId.'" AND
                                            c_id    ="'.$course_code.'" AND
                                            session_id      ="'.$session_id.'" AND
                                            status          = ""
                                            ORDER BY exe_date DESC LIMIT 1';
            $result_last_attempt = Database::query($sql_last_attempt);
            if (Database::num_rows($result_last_attempt) > 0) {
                $id_last_attempt = Database::result($result_last_attempt, 0, 0);
                if ($count_attempts > 0) {
                    echo '<a href="../exercice/exercise_show.php?id='.$id_last_attempt.'&cidReq='.$course_code.'&session_id='.$session_id.'&student='.$studentId.'&origin='.(empty($_GET['origin']) ? 'tracking' : Security::remove_XSS(
                                $_GET['origin']
                            )).'"> <img src="'.api_get_path(WEB_IMG_PATH).'quiz.gif" border="0" /> </a>';
                }
            }
            echo '</td>';

            echo '<td>';
            $all_attempt_url = "../exercice/exercise_report.php?exerciseId=$exercise_id&cidReq=$course_code&filter_by_user=$studentId&id_session=$session_id";
            echo Display::url(
                    Display::return_icon('test_results.png', get_lang('AllAttempts'), [], ICON_SIZE_SMALL),
                    $all_attempt_url
                );

            echo '</td></tr>';
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
        echo '<tr><td colspan="6">'.get_lang('NoExercise').'</td></tr>';
    }
    echo '</table>';

    //@when using sessions we do not show the survey list
    if (empty($session_id)) {
        $survey_list = SurveyManager::get_surveys($course_code, $session_id);
        $survey_data = [];
        foreach ($survey_list as $survey) {
            $user_list = SurveyManager::get_people_who_filled_survey(
                    $survey['survey_id'],
                    false,
                    $info_course['real_id']
                );
            $survey_done = Display::return_icon("accept_na.png", get_lang('NoAnswer'), [], ICON_SIZE_SMALL);
            if (in_array($studentId, $user_list)) {
                $survey_done = Display::return_icon("accept.png", get_lang('Answered'), [], ICON_SIZE_SMALL);
            }
            $data = ['title' => $survey['title'], 'done' => $survey_done];
            $survey_data[] = $data;
        }

        if (!empty($survey_list)) {
            $table = new HTML_Table(['class' => 'table table-hover table-striped data_table']);
            $header_names = [get_lang('Survey'), get_lang('Answered')];
            $row = 0;
            $column = 0;
            foreach ($header_names as $item) {
                $table->setHeaderContents($row, $column, $item);
                $column++;
            }
            $row = 1;
            if (!empty($survey_data)) {
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
            }
            echo $table->toHtml();
        }
    }

    // line about other tools
    echo '<table class="table table-hover table-striped data_table">';

    $csv_content[] = [];
    $nb_assignments = Tracking::count_student_assignments($studentId, $course_code, $session_id);
    $messages = Tracking::count_student_messages($studentId, $course_code, $session_id);
    $links = Tracking::count_student_visited_links($studentId, $course_code, $session_id);
    $chat_last_connection = Tracking::chat_last_connection($studentId, $course_code, $session_id);
    $documents = Tracking::count_student_downloaded_documents($studentId, $course_code, $session_id);
    $uploaded_documents = Tracking::count_student_uploaded_documents($studentId, $course_code, $session_id);

    $csv_content[] = [
            get_lang('OtherTools'),
        ];

    $csv_content[] = [
            get_lang('StudentPublications'),
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
        ]; ?>
        <tr>
            <th colspan="2"><?php echo get_lang('OtherTools'); ?></th>
        </tr>
        <tr><!-- assignments -->
            <td width="40%"><?php echo get_lang('StudentPublications'); ?></td>
            <td><?php echo $nb_assignments; ?></td>
        </tr>
        <tr><!-- messages -->
            <td><?php echo get_lang('Forum').' - '.get_lang('NumberOfPostsForThisUser'); ?></td>
            <td><?php echo $messages; ?></td>
        </tr>
        <tr><!-- links -->
            <td><?php echo get_lang('LinksDetails'); ?></td>
            <td><?php echo $links; ?></td>
        </tr>
        <tr><!-- downloaded documents -->
            <td><?php echo get_lang('DocumentsDetails'); ?></td>
            <td><?php echo $documents; ?></td>
        </tr>
        <tr><!-- uploaded documents -->
            <td><?php echo get_lang('UploadedDocuments'); ?></td>
            <td><?php echo $uploaded_documents; ?></td>
        </tr>
        <tr><!-- Chats -->
            <td><?php echo get_lang('ChatLastConnection'); ?></td>
            <td><?php echo $chat_last_connection; ?></td>
        </tr>
    </table>
    </td>
    </tr>
    </table>

    <?php
} //end details

if ($export_csv) {
    ob_end_clean();
    Export::export_table_csv($csv_content, 'reporting_student');
    exit;
}
?>
<br>
<form action="create_exam.php" method="post" name="create_exam">
    <table class='table table-hover table-striped data_table'>
        <tr>
            <th colspan="6">
                <?php echo get_lang('Title');
                echo $user_info['complete_name']; ?>
            </th>

        <tr>
            <th style="width: 60px">
                <?php echo get_lang('Module'); ?>
            </th>
            <th style="width: 120px">
                <?php echo get_lang('Results'); ?>
            </th>
            <th style="width: 100px">
                <?php echo get_lang('result_rep_1'); ?>
            </th>
            <th style="width: 100px">
                <?php echo get_lang('result_rep_2'); ?>
            </th>
            <th style="width: 720px">
                <?php echo get_lang('Comment'); ?>
            </th>
            <th style="width: 60px">
                <?php echo get_lang('Actions'); ?>
            </th>
        </tr>
        <tr>
            <td><input type="text" style="width:90%;" NAME="mod_no" size="1" <?php echo "$mod_no "; ?></textarea></td>
            <td>
                <select name="score_ex">
                    <option value="-">-</option>
                    <option value="SU">SU</option>
                    <option value="EC">EC</option>
                </select>
            </td>
            <td>
                <select name="score_rep1">
                    <option value="-">-</option>
                    <option value="SU">SU</option>
                    <option value="EC">EC</option>
                </select>
            </td>
            <td>
                <select name="score_rep2">
                    <option value="-">-</option>
                    <option value="SU">SU</option>
                    <option value="EC">EC</option>
                </select>
            </td>
            <td><textarea name="coment" style="width:70%; rows=" 2"><?php echo "$coment"; ?></textarea></td>
            <INPUT type=hidden name=ex_user_id value= <?php echo $studentId; ?>>
            <td><input type="SUBMIT" value="<?php echo get_lang('Save'); ?>" name="create_exam"></td>
        </tr>
    </table>
</form>

<form name="save_exam">
    <table class='table table-hover table-striped data_table'>
        <tr>
            <th colspan="6">
                <?php echo get_lang('result_exam_title');
                echo $user_info['complete_name']; ?>
            </th>
        <tr>
            <th><?php echo get_lang('Module'); ?> </th>
            <th>
                <?php echo get_lang('Results'); ?>
            </th>
            <th>
                <?php echo get_lang('result_rep_1'); ?>
            </th>
            <th>
                <?php echo get_lang('result_rep_2'); ?>
            </th>
            <th>
                <?php echo get_lang('Comment'); ?>
            </th>
            <th>
                <?php echo get_lang('Actions'); ?>
            </th>
        </tr>
        <?php
        $sqlexam = "SELECT *
                     FROM $tbl_stats_exercices
                     WHERE exe_user_id = $studentId
                     AND c_id = 0 AND mod_no != '0'
                     ORDER BY mod_no ASC";
        $resultexam = Database::query($sqlexam);
        $coment = '';
        while ($a_exam = Database::fetch_array($resultexam)) {
            //$ex_id = $a_exam['ex_id'];
            $mod_no = $a_exam['mod_no'];
            $score_ex = $a_exam['score_ex'];
            $score_rep1 = $a_exam['score_rep1'];
            $score_rep2 = $a_exam['score_rep2'];
            $coment = stripslashes($a_exam['coment']);
            echo "
            <tr><center>
                <td> ".$a_exam['mod_no']."
                </td>
            <td><center>
                    ".$a_exam['score_ex']."
                </td>
            <td><center>
                    ".$a_exam['score_rep1']."
                </td>
                <td><center>
                    ".$a_exam['score_rep2']."
                </td>
                <td>$coment

            ";
            $exe_idd = $a_exam['exe_id']; ?>
            <INPUT type=hidden name=ex_idd value= <?php echo "$exe_idd"; ?>>
            <td class="highlight">&nbsp;
                <a href="edit_exam.php?num=<?php echo $exe_idd; ?>&student_id=<?php echo $studentId; ?>">
                    <?php echo Display::return_icon('edit.png', get_lang('Edit')); ?>
                </a>
                &nbsp;&nbsp;<a href="delete_exam.php?num=<?php echo $exe_idd; ?>&student_id=<?php echo $studentId; ?>">
                    <img
                     src="../img/delete.gif" border="0"
                    onClick='return confirmDelete2()'></a>
            </td>
            </tr>
            <?php
        }
        ?>
    </table>
</form>
<strong><?php echo get_lang('imprime_sommaire'); ?> </strong>
<?php
echo '<a target="_blank"
    href="print_myStudents.php?student='.$studentId.'&details=true&course='.$course_code.'&origin=tracking_course">
<img src="'.api_get_path(WEB_IMG_PATH).'printmgr.gif" border="0" /></a>';
// tableau pour date de fin prévue pour chaque module
?>
<table class='table table-hover table-striped data_table'>
    <tr>
        <th colspan="6">
            <?php echo get_lang('fin_mod_prevue');
            echo $user_info['complete_name']; ?>
        </th>
    <tr>
        <th style="width: 17%">
            <?php echo get_lang('Module'); ?>
        </th>
        <th style="width: 17%">
            <?php echo get_lang('FirstLogin'); ?>
        </th>
        <th style="width: 16%">
            <?php echo get_lang('ToDo'); ?>
        </th>
        <th style="width: 16%">
            <?php echo get_lang('realise'); ?>
        </th>
        <th style="width: 16%">
            <?php echo get_lang('pour_realise'); ?>
        </th>
        <th style="width: 16%">
            <?php echo get_lang('fin_mod_prevue'); ?>
        </th>
    </tr>
</table>

<?php Display::display_footer();
