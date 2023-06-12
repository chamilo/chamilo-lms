<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\UserRelUser;
use Chamilo\UserBundle\Entity\User;

/**
 * Script showing information about a user (name, e-mail, courses and sessions).
 *
 * @author Bart Mollet
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_PLATFORM_ADMIN;
require_once api_get_path(SYS_CODE_PATH).'forum/forumfunction.inc.php';
require_once api_get_path(SYS_CODE_PATH).'work/work.lib.php';

$userId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;

if (empty($userId)) {
    api_not_allowed(true);
}
$user = api_get_user_info($userId, true);

if (empty($user)) {
    api_not_allowed(true);
}
$tpl = new Template(null, false, false, false, false, false, false);
/** @var User $userEntity */
$userEntity = api_get_user_entity($user['user_id']);
$myUserId = api_get_user_id();

if (!api_is_student_boss()) {
    api_protect_admin_script(true);
} else {
    $isBoss = UserManager::userIsBossOfStudent($myUserId, $user['user_id']);
    if (!$isBoss) {
        api_not_allowed(true);
    }
}

$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('PlatformAdmin')];
$interbreadcrumb[] = ['url' => 'user_list.php', 'name' => get_lang('UserList')];
$userId = $user['user_id'];
$currentUrl = api_get_self().'?user_id='.$userId;
$tool_name = UserManager::formatUserFullName($userEntity);
$table_course_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
$table_course = Database::get_main_table(TABLE_MAIN_COURSE);
$csvContent = [];

// only allow platform admins to login_as, or session admins only for students (not teachers nor other admins)
$actions = [
    Display::url(
        Display::return_icon(
            'statistics.png',
            get_lang('Reporting'),
            [],
            ICON_SIZE_MEDIUM
        ),
        api_get_path(WEB_CODE_PATH).'mySpace/myStudents.php?'.http_build_query([
            'student' => $userId,
        ]),
        ['title' => get_lang('Reporting')]
    ),
];

if (api_can_login_as($userId)) {
    $actions[] = Display::url(
        Display::return_icon(
            'login_as.png',
            get_lang('LoginAs'),
            [],
            ICON_SIZE_MEDIUM
        ),
        api_get_path(WEB_CODE_PATH).'admin/user_list.php?action=login_as&user_id='.$userId.'&sec_token='.Security::getTokenFromSession()
    );
}

if (api_is_platform_admin()) {
    $actions[] = Display::url(
        Display::return_icon(
            'edit.png',
            get_lang('Edit'),
            [],
            ICON_SIZE_MEDIUM
        ),
        api_get_path(WEB_CODE_PATH).'admin/user_edit.php?user_id='.$userId
    );

    $actions[] = Display::url(
        Display::return_icon(
            'export_csv.png',
            get_lang('ExportAsCSV'),
            [],
            ICON_SIZE_MEDIUM
        ),
        api_get_self().'?user_id='.$userId.'&action=export'
    );
    $actions[] = Display::url(
        Display::return_icon(
            'vcard.png',
            get_lang('UserInfo'),
            [],
            ICON_SIZE_MEDIUM
        ),
        api_get_path(WEB_PATH).'main/social/vcard_export.php?userId='.$userId
    );
    $actions[] = Display::url(
        Display::return_icon('new_group.png', get_lang('AddHrmToUser'), [], ICON_SIZE_MEDIUM),
        api_get_path(WEB_CODE_PATH).'admin/add_drh_to_user.php?u='.$userId
    );

    if (Skill::isAllowed($userId, false)) {
        $actions[] = Display::url(
            Display::return_icon(
                'skill-badges.png',
                get_lang('AddSkill'),
                [],
                ICON_SIZE_MEDIUM,
                false
            ),
            api_get_path(WEB_CODE_PATH).'badge/assign.php?user='.$userId
        );
    }
}
$userInfo = null;
$studentBossList = UserManager::getStudentBossList($userId);
$studentBossListToString = '';
if (!empty($studentBossList)) {
    $table = new HTML_Table(['class' => 'table table-hover table-striped data_table']);
    $table->setHeaderContents(0, 0, get_lang('User'));
    $csvContent[] = [get_lang('StudentBoss')];

    $row = 1;
    foreach ($studentBossList as $studentBossId) {
        $studentBoss = api_get_user_info($studentBossId['boss_id']);
        $table->setCellContents($row, 0, $studentBoss['complete_name_with_message_link']);
        $csvContent[] = [$studentBoss['complete_name_with_username']];
        $row++;
    }
    $studentBossListToString = $table->toHtml();
}

$registrationDate = $user['registration_date'];

$table = new HTML_Table(['class' => 'table table-hover table-striped data_table']);
$table->setHeaderContents(0, 0, get_lang('Information'));

$csvContent[] = [get_lang('Information')];
$data = [
    get_lang('Name') => $user['complete_name'],
    get_lang('Email') => $user['email'],
    get_lang('Phone') => $user['phone'],
    get_lang('OfficialCode') => $user['official_code'],
    get_lang('Online') => !empty($user['user_is_online']) ? Display::return_icon('online.png') : Display::return_icon('offline.png'),
    get_lang('Status') => $user['status'] == 1 ? get_lang('Teacher') : get_lang('Student'),
];

$userInfo = [
    'complete_name' => $user['complete_name'],
    'email' => $user['email'],
    'phone' => $user['phone'],
    'official_code' => $user['official_code'],
    'user_is_online' => !empty($user['user_is_online']) ? Display::return_icon('online.png') : Display::return_icon('offline.png'),
    'status' => $user['status'] == 1 ? get_lang('Teacher') : get_lang('Student'),
    'avatar' => $user['avatar'],
];

// Show info about who created this user and when
$creatorId = $user['creator_id'];
$creatorInfo = api_get_user_info($creatorId);
if (!empty($creatorId) && !empty($creatorInfo)) {
    $userInfo['created'] = sprintf(
        get_lang('CreatedByXYOnZ'),
        'user_information.php?user_id='.$creatorId,
        $creatorInfo['username'],
        api_get_utc_datetime($registrationDate)
    );
}

$row = 1;
foreach ($data as $label => $item) {
    if (!empty($label)) {
        $label = $label.': ';
    }
    $table->setCellContents($row, 0, $label.$item);
    $csvContent[] = [$label, strip_tags($item)];
    $row++;
}

$table = new HTML_Table(['class' => 'table table-hover table-striped data_table']);
$table->setHeaderContents(0, 0, get_lang('Tracking'));
$csvContent[] = [get_lang('Tracking')];
$userInfo['first_connection'] = Tracking::get_first_connection_date($userId);
$userInfo['last_connection'] = Tracking::get_last_connection_date($userId, true);
$userInfo['last_connection_in_course'] = api_format_date(
    Tracking::getLastConnectionInAnyCourse($userId),
    DATE_FORMAT_SHORT
);
$data = [
    get_lang('FirstLogin') => $userInfo['first_connection'],
    get_lang('LatestLogin') => $userInfo['last_connection'],
    get_lang('LatestLoginInAnyCourse') => $userInfo['last_connection_in_course'],
];

if (api_get_setting('allow_terms_conditions') === 'true') {
    $extraFieldValue = new ExtraFieldValue('user');
    $value = $extraFieldValue->get_values_by_handler_and_field_variable(
        $userId,
        'legal_accept'
    );
    $icon = Display::return_icon('accept_na.png');
    if (!empty($value['value'])) {
        list($legalId, $legalLanguageId, $legalTime) = explode(':', $value['value']);
        $icon = Display::return_icon('accept.png');
        $timeLegalAccept = api_get_local_time($legalTime);
        $btn = Display::url(
            get_lang('DeleteLegal'),
            api_get_self().'?action=delete_legal&user_id='.$userId,
            ['class' => 'btn btn-danger btn-xs']
        );
    } else {
        $btn = Display::url(
            get_lang('SendLegal'),
            api_get_self().'?action=send_legal&user_id='.$userId,
            ['class' => 'btn btn-primary btn-xs']
        );
        $timeLegalAccept = get_lang('NotRegistered');
    }

    $data[get_lang('LegalAccepted')] = $icon;
    $userInfo['legal'] = [
        'icon' => $icon,
        'datetime' => $timeLegalAccept,
        'url_send' => $btn,
    ];
}
$row = 1;
foreach ($data as $label => $item) {
    if (!empty($label)) {
        $label = $label.': ';
    }
    $table->setCellContents($row, 0, $label.$item);
    $csvContent[] = [$label, strip_tags($item)];
    $row++;
}

/**
 * Show social activity.
 */
if (api_get_setting('allow_social_tool') === 'true') {
    $userObject = api_get_user_entity($userId);
    $data = [];
    // Calculate values
    if (api_get_setting('allow_message_tool') === 'true') {
        $messagesSent = SocialManager::getCountMessagesSent($userId);
        $data[] = [get_lang('MessagesSent'), $messagesSent];
        $messagesReceived = SocialManager::getCountMessagesReceived($userId);
        $data[] = [get_lang('MessagesReceived'), $messagesReceived];
    }
    $wallMessagesPosted = SocialManager::getCountWallPostedMessages($userId);
    $data[] = [get_lang('WallMessagesPosted'), $wallMessagesPosted];

    $friends = SocialManager::getCountFriends($userId);
    $data[] = [get_lang('Friends'), $friends];

    $countSent = SocialManager::getCountInvitationSent($userId);
    $data[] = [get_lang('InvitationSent'), $countSent];

    $countReceived = SocialManager::get_message_number_invitation_by_user_id($userId);
    $data[] = [get_lang('InvitationReceived'), $countReceived];

    $userInfo['social'] = [
        'friends' => $friends,
        'invitation_sent' => $countSent,
        'invitation_received' => $countReceived,
        'messages_posted' => $wallMessagesPosted,
        'message_sent' => $messagesSent,
        'message_received' => $messagesReceived,
    ];
}

/**
 * Show the sessions in which this user is subscribed.
 */
$sessions = SessionManager::get_sessions_by_user($userId, true);
$personal_course_list = [];
$sessionInformation = '';
if (count($sessions) > 0) {
    $header = [
        [get_lang('Code'), true],
        [get_lang('Title'), true],
        [get_lang('Status'), true],
        [get_lang('TimeSpentInTheCourse'), true],
        [get_lang('TotalPostsInAllForums'), true],
        ['', false],
    ];

    $headerList = [];
    foreach ($header as $item) {
        $headerList[] = $item[0];
    }

    $csvContent[] = [];
    $csvContent[] = [get_lang('Sessions')];

    foreach ($sessions as $session_item) {
        $data = [];
        $personal_course_list = [];
        $id_session = $session_item['session_id'];
        $csvContent[] = [$session_item['session_name']];
        $csvContent[] = $headerList;
        $courseToolInformationTotal = '';
        foreach ($session_item['courses'] as $my_course) {
            $courseInfo = api_get_course_info_by_id($my_course['real_id']);
            $sessionStatus = SessionManager::get_user_status_in_course_session(
                $userId,
                $courseInfo['real_id'],
                $id_session
            );
            $status = null;
            switch ($sessionStatus) {
                case 0:
                case STUDENT:
                    $status = get_lang('Student');
                    break;
                case 2:
                    $status = get_lang('CourseCoach');
                    break;
            }

            $tools = Display::url(
                Display::return_icon('statistics.png', get_lang('Stats')),
                api_get_path(WEB_CODE_PATH).'mySpace/myStudents.php?details=true&student='.$userId.'&id_session='.$id_session.'&course='.$courseInfo['code']
            );
            $tools .= '&nbsp;<a href="course_information.php?code='.$courseInfo['code'].'&id_session='.$id_session.'">'.
                Display::return_icon('info2.png', get_lang('Overview')).'</a>'.
                '<a href="'.$courseInfo['course_public_url'].'?id_session='.$id_session.'">'.
                Display::return_icon('course_home.png', get_lang('CourseHomepage')).'</a>';

            if (!empty($my_course['status']) && $my_course['status'] == STUDENT) {
                $tools .= '<a
                    href="user_information.php?action=unsubscribe_session_course&course_id='.$courseInfo['real_id'].'&user_id='.$userId.'&id_session='.$id_session.'">'.
                    Display::return_icon('delete.png', get_lang('Delete')).'</a>';
            }

            $timeSpent = api_time_to_hms(
                Tracking::get_time_spent_on_the_course(
                    $userId,
                    $courseInfo['real_id'],
                    $id_session
                )
            );

            $totalForumMessages = CourseManager::getCountPostInForumPerUser(
                $userId,
                $courseInfo['real_id'],
                $id_session
            );

            $row = [
                Display::url(
                    $courseInfo['code'],
                    $courseInfo['course_public_url'].'?id_session='.$id_session
                ),
                $courseInfo['title'],
                $status,
                $timeSpent,
                $totalForumMessages,
                $tools,
            ];

            $csvContent[] = array_map('strip_tags', $row);
            $data[] = $row;

            $result = Tracking::getToolInformation(
                $userId,
                $courseInfo,
                $id_session
            );

            if (!empty($result['html'])) {
                $courseToolInformationTotal .= $result['html'];
                $csvContent = array_merge($csvContent, $result['array']);
            }
        }

        $dates = array_filter(
            [$session_item['access_start_date'], $session_item['access_end_date']]
        );
        $certificateLink = Display::url(
            Display::return_icon('pdf.png', get_lang('CertificateOfAchievement'), [], ICON_SIZE_SMALL),
            api_get_path(WEB_CODE_PATH).'mySpace/session.php?'
            .http_build_query(
                [
                    'action' => 'export_to_pdf',
                    'type' => 'achievement',
                    'session_to_export' => $id_session,
                    'student' => $userId,
                ]
            ),
            ['target' => '_blank']
        );
        $sessionInformation .= Display::page_subheader(
            '<a href="'.api_get_path(WEB_CODE_PATH).'session/resume_session.php?id_session='.$id_session.'">'.
            $session_item['session_name'].'</a>',
            $certificateLink.' '.implode(' - ', $dates)
        );
        $sessionInformation .= Display::return_sortable_table(
            $header,
            $data,
            [],
            [],
            ['user_id' => $userId]
        );
        $sessionInformation .= $courseToolInformationTotal;
    }
} else {
    $sessionInformation = '<p>'.get_lang('NoSessionsForThisUser').'</p>';
}

/**
 * Show the courses in which this user is subscribed.
 */
$sql = 'SELECT * FROM '.$table_course_user.' cu, '.$table_course.' c
        WHERE
            cu.user_id = '.$userId.' AND
            cu.c_id = c.id AND
            cu.relation_type <> '.COURSE_RELATION_TYPE_RRHH.' ';
$res = Database::query($sql);
if (Database::num_rows($res) > 0) {
    $header = [
        [get_lang('Code')],
        [get_lang('Title')],
        [get_lang('Status')],
        [get_lang('TimeSpentInTheCourse')],
        [get_lang('TotalPostsInAllForums')],
        [''],
    ];

    $headerList = [];
    foreach ($header as $item) {
        $headerList[] = $item[0];
    }
    $csvContent[] = [];
    $csvContent[] = [get_lang('Courses')];
    $csvContent[] = $headerList;

    $data = [];
    $courseToolInformationTotal = '';
    while ($course = Database::fetch_object($res)) {
        $courseInfo = api_get_course_info_by_id($course->c_id);
        $courseCode = $courseInfo['code'];
        $courseToolInformation = null;

        $tools = Display::url(
            Display::return_icon('statistics.png', get_lang('Stats')),
            api_get_path(WEB_CODE_PATH).'mySpace/myStudents.php?details=true&student='.$userId.'&id_session=0&course='.$courseCode
        );

        $tools .= '&nbsp;<a href="course_information.php?code='.$courseCode.'">'.
            Display::return_icon('info2.png', get_lang('Overview')).'</a>'.
            '<a href="'.$courseInfo['course_public_url'].'">'.
            Display::return_icon('course_home.png', get_lang('CourseHomepage')).'</a>'.
            '<a href="course_edit.php?id='.$course->c_id.'">'.
            Display::return_icon('edit.png', get_lang('Edit')).'</a>';
        if ($course->status == STUDENT) {
            $tools .= '<a href="user_information.php?action=unsubscribe&course_id='.$courseInfo['real_id'].'&user_id='.$userId.'">'.
                Display::return_icon('delete.png', get_lang('Delete')).'</a>';
        }

        $timeSpent = api_time_to_hms(
            Tracking::get_time_spent_on_the_course(
                $userId,
                $courseInfo['real_id']
            )
        );

        $totalForumMessages = CourseManager::getCountPostInForumPerUser(
            $userId,
            $course->id,
            0
        );

        $row = [
            Display::url($courseCode, $courseInfo['course_public_url']),
            $course->title,
            $course->status == STUDENT ? get_lang('Student') : get_lang('Teacher'),
            $timeSpent,
            $totalForumMessages,
            $tools,
        ];

        $csvContent[] = array_map('strip_tags', $row);
        $data[] = $row;

        $result = Tracking::getToolInformation(
            $userId,
            $courseInfo,
            0
        );
        $courseToolInformationTotal .= $result['html'];
        $csvContent = array_merge($csvContent, $result['array']);
    }

    $courseInformation = Display::return_sortable_table(
        $header,
        $data,
        [],
        [],
        ['user_id' => $userId]
    );
    $courseInformation .= $courseToolInformationTotal;
} else {
    $courseInformation = '<p>'.get_lang('NoCoursesForThisUser').'</p>';
}

/**
 * Show the URL in which this user is subscribed.
 */
$urlInformation = '';
if (api_is_multiple_url_enabled()) {
    $urlList = UrlManager::get_access_url_from_user($userId);
    if (count($urlList) > 0) {
        $header = [];
        $header[] = ['URL', true];
        $data = [];

        $csvContent[] = [];
        $csvContent[] = ['Url'];
        foreach ($urlList as $url) {
            $row = [];
            $row[] = Display::url($url['url'], $url['url']);
            $csvContent[] = array_map('strip_tags', $row);
            $data[] = $row;
        }

        $urlInformation = Display::page_subheader(get_lang('URLList'));
        $urlInformation .= Display::return_sortable_table(
            $header,
            $data,
            [],
            [],
            ['user_id' => $userId]
        );
    } else {
        $urlInformation = '<p>'.get_lang('NoUrlForThisUser').'</p>';
    }
}

if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'send_legal':
            $subject = get_lang('SendLegalSubject');
            $content = sprintf(
                get_lang('SendLegalDescriptionToUrlX'),
                api_get_path(WEB_PATH)
            );
            MessageManager::send_message_simple($userId, $subject, $content);
            Display::addFlash(Display::return_message(get_lang('Sent')));
            break;
        case 'delete_legal':
            $extraFieldValue = new ExtraFieldValue('user');
            $value = $extraFieldValue->get_values_by_handler_and_field_variable(
                $userId,
                'legal_accept'
            );
            $result = $extraFieldValue->delete($value['id']);
            if ($result) {
                Display::addFlash(Display::return_message(get_lang('Deleted')));
            }
            break;
        case 'unsubscribe':
            $courseId = !empty($_GET['course_id']) ? (int) $_GET['course_id'] : 0;
            $sessionId = !empty($_GET['id_session']) ? (int) $_GET['id_session'] : 0;
            $courseInfo = api_get_course_info_by_id($courseId);
            if (empty($courseInfo)) {
                break;
            }

            if (CourseManager::getUserInCourseStatus($userId, $courseInfo['real_id']) == STUDENT) {
                CourseManager::unsubscribe_user($userId, $courseInfo['code'], $sessionId);
                Display::addFlash(Display::return_message(get_lang('UserUnsubscribed')));
            } else {
                Display::addFlash(Display::return_message(
                    get_lang('CannotUnsubscribeUserFromCourse'),
                    'error',
                    false
                ));
            }
            header('Location: '.$currentUrl);
            exit;
            break;
        case 'unsubscribe_session_course':
            $courseId = !empty($_GET['course_id']) ? (int) $_GET['course_id'] : 0;
            $sessionId = !empty($_GET['id_session']) ? (int) $_GET['id_session'] : 0;

            SessionManager::removeUsersFromCourseSession(
                [$userId],
                $sessionId,
                api_get_course_info_by_id($courseId)
            );
            Display::addFlash(Display::return_message(get_lang('UserUnsubscribed')));
            header('Location: '.$currentUrl);
            exit;
            break;
        case 'export':
            Export::arrayToCsv(
                $csvContent,
                'user_information_'.$userId
            );
            exit;
            break;
    }
}

Display::display_header($tool_name);
echo Display::toolbarAction('toolbar-user-information', [implode(PHP_EOL, $actions)]);

$fullUrlBig = UserManager::getUserPicture(
    $userId,
    USER_IMAGE_SIZE_BIG
);

$fullUrl = UserManager::getUserPicture(
    $userId,
    USER_IMAGE_SIZE_ORIGINAL
);

if ($studentBossList) {
    echo Display::page_subheader(get_lang('StudentBossList'));
    echo $studentBossListToString;
}

$em = Database::getManager();
$userRepository = UserManager::getRepository();

$hrmList = $userRepository->getAssignedHrmUserList(
    $userEntity->getId(),
    api_get_current_access_url_id()
);

if ($hrmList) {
    echo Display::page_subheader(get_lang('HrmList'));
    echo '<div class="row">';

    /** @var UserRelUser $hrm */
    foreach ($hrmList as $hrm) {
        $hrmInfo = api_get_user_info($hrm->getFriendUserId());

        $userPicture = isset($hrmInfo['avatar_medium']) ? $hrmInfo['avatar_medium'] : $hrmInfo['avatar'];
        echo '<div class="col-sm-4 col-md-3">';
        echo '<div class="media">';
        echo '<div class="media-left">';
        echo Display::img($userPicture, $hrmInfo['complete_name'], ['class' => 'media-object'], false);
        echo '</div>';
        echo '<div class="media-body">';
        echo '<h4 class="media-heading">'.$hrmInfo['complete_name_with_message_link'].'</h4>';
        echo $hrmInfo['username'];
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }
    echo '</div>';
}

if (DRH == $user['status']) {
    $usersAssigned = UserManager::get_users_followed_by_drh($userId);

    if ($usersAssigned) {
        echo Display::page_subheader(get_lang('AssignedUsersListToHumanResourcesManager'));
        echo '<div class="row">';
        foreach ($usersAssigned as $userAssigned) {
            $userAssigned = api_get_user_info($userAssigned['user_id']);
            $userPicture = isset($userAssigned['avatar_medium']) ? $userAssigned['avatar_medium'] : $userAssigned['avatar'];

            echo '<div class="col-sm-4 col-md-3">';
            echo '<div class="media">';
            echo '<div class="media-left">';
            echo Display::img($userPicture, $userAssigned['complete_name'], ['class' => 'media-object'], false);
            echo '</div>';
            echo '<div class="media-body">';
            echo '<h4 class="media-heading">'.$userAssigned['complete_name_with_message_link'].'</h4>';
            echo $userAssigned['official_code'];
            echo '</div>';
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';
    }
}
$socialTool = api_get_setting('allow_social_tool');
$tpl->assign('social_tool', $socialTool);
$tpl->assign('user', $userInfo);
$layoutTemplate = $tpl->get_template('admin/user_information.tpl');
$content = $tpl->fetch($layoutTemplate);
echo $content;
if (api_get_configuration_value('allow_career_users')) {
    if (!empty($sessions)) {
        $sessions = array_column($sessions, 'session_id');
        echo SessionManager::getCareerDiagramPerSessionList($sessions, $userId);
    }
    echo MyStudents::userCareersTable($userId);
}

echo Display::page_subheader(get_lang('SessionList'), null, 'h3', ['class' => 'section-title']);
echo $sessionInformation;

echo Display::page_subheader(get_lang('CourseList'), null, 'h3', ['class' => 'section-title']);
echo $courseInformation;
echo $urlInformation;

echo Tracking::displayUserSkills(
    $userId,
    0,
    0
);

Display::display_footer();
