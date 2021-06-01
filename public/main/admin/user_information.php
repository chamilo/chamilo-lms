<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;

/**
 * Script showing information about a user (name, e-mail, courses and sessions).
 *
 * @author Bart Mollet
 */

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_PLATFORM_ADMIN;
$userId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;

if (empty($userId)) {
    api_not_allowed(true);
}
$user = api_get_user_entity($userId);

if (null === $user) {
    api_not_allowed(true);
}

$myUserId = api_get_user_id();
if (!api_is_student_boss()) {
    api_protect_admin_script();
} else {
    $isBoss = UserManager::userIsBossOfStudent($myUserId, $userId);
    if (!$isBoss) {
        api_not_allowed(true);
    }
}

$currentUrl = api_get_self().'?user_id='.$userId;
$tool_name = $completeName = UserManager::formatUserFullName($user);
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
            get_lang('Login as'),
            [],
            ICON_SIZE_MEDIUM
        ),
        api_get_path(WEB_CODE_PATH).
        'admin/user_list.php?action=login_as&user_id='.$userId.'&sec_token='.Security::getTokenFromSession()
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
            get_lang('CSV export'),
            [],
            ICON_SIZE_MEDIUM
        ),
        api_get_self().'?user_id='.$userId.'&action=export'
    );
    $actions[] = Display::url(
        Display::return_icon(
            'vcard.png',
            get_lang('user information'),
            [],
            ICON_SIZE_MEDIUM
        ),
        api_get_path(WEB_PATH).'main/social/vcard_export.php?userId='.$userId
    );
    $actions[] = Display::url(
        Display::return_icon('new_group.png', get_lang('Add Human Resources Manager to user'), [], ICON_SIZE_MEDIUM),
        api_get_path(WEB_CODE_PATH).'admin/add_drh_to_user.php?u='.$userId
    );

    if (SkillModel::isAllowed($userId, false)) {
        $actions[] = Display::url(
            Display::return_icon(
                'skill-badges.png',
                get_lang('Add skill'),
                [],
                ICON_SIZE_MEDIUM,
                false
            ),
            api_get_path(WEB_CODE_PATH).'badge/assign.php?user='.$userId
        );
    }
}

$studentBossList = UserManager::getStudentBossList($userId);
$studentBossListToString = '';
if (!empty($studentBossList)) {
    $table = new HTML_Table(['class' => 'data_table']);
    $table->setHeaderContents(0, 0, get_lang('User'));
    $csvContent[] = [get_lang('Superior (n+1)')];

    $row = 1;
    foreach ($studentBossList as $studentBossId) {
        $studentBoss = api_get_user_info($studentBossId['boss_id']);
        $table->setCellContents($row, 0, $studentBoss['complete_name_with_message_link']);
        $csvContent[] = [$studentBoss['complete_name_with_username']];
        $row++;
    }
    $studentBossListToString = $table->toHtml();
}

$table = new HTML_Table(['class' => 'data_table']);
$table->setHeaderContents(0, 0, get_lang('Information'));
$csvContent[] = [get_lang('Information')];
$data = [
    get_lang('Name') => $completeName,
    get_lang('e-mail') => $user->getEmail(),
    get_lang('Phone') => $user->getPhone(),
    get_lang('Course code') => $user->getOfficialCode(),
    //get_lang('Online') => !empty($user['user_is_online']) ? Display::return_icon('online.png') : Display::return_icon('offline.png'),
    get_lang('Status') => 1 === $user->getStatus() ? get_lang('Trainer') : get_lang('Learner'),
];

$params = [];

// Show info about who created this user and when
$creatorId = $user->getCreatorId();
$creatorInfo = api_get_user_info($creatorId);
if (!empty($creatorId) && !empty($creatorInfo)) {
    $registrationDate = $user->getRegistrationDate()->format('Y-m-d H:i:s');
    $userInfo['created'] = sprintf(
        get_lang('Create by <a href="%s">%s</a> on %s'),
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

$table = new HTML_Table(['class' => 'data_table']);
$table->setHeaderContents(0, 0, get_lang('Reporting'));
$csvContent[] = [get_lang('Reporting')];
$params['first_connection'] = Tracking::get_first_connection_date($userId);
$params['last_connection'] = Tracking::get_last_connection_date($userId, true);
$data = [
    get_lang('First connection') => $params['first_connection'],
    get_lang('Latest login') => $params['last_connection'],
];

if ('true' === api_get_setting('allow_terms_conditions')) {
    $extraFieldValue = new ExtraFieldValue('user');
    $value = $extraFieldValue->get_values_by_handler_and_field_variable(
        $userId,
        'legal_accept'
    );
    $icon = Display::return_icon('accept_na.png');
    if (!empty($value['value'])) {
        [$legalId, $legalLanguageId, $legalTime] = explode(':', $value['value']);
        $icon = Display::return_icon('accept.png');
        $timeLegalAccept = api_get_local_time($legalTime);
        $btn = Display::url(
            get_lang('Delete legal agreement'),
            api_get_self().'?action=delete_legal&user_id='.$userId,
            ['class' => 'btn btn-danger btn-xs']
        );
    } else {
        $btn = Display::url(
            get_lang('Send legal agreement'),
            api_get_self().'?action=send_legal&user_id='.$userId,
            ['class' => 'btn btn-primary btn-xs']
        );
        $timeLegalAccept = get_lang('Not Registered');
    }

    $data[get_lang('Legal accepted')] = $icon;
    $params['legal'] = [
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
if ('true' === api_get_setting('allow_social_tool')) {
    $data = [];
    $messagesSent = '';
    // Calculate values
    if ('true' === api_get_setting('allow_message_tool')) {
        $messagesSent = SocialManager::getCountMessagesSent($userId);
        $data[] = [get_lang('Number of messages sent'), $messagesSent];
        $messagesReceived = SocialManager::getCountMessagesReceived($userId);
        $data[] = [get_lang('Number of messages received'), $messagesReceived];
    }
    $wallMessagesPosted = SocialManager::getCountWallPostedMessages($userId);
    $data[] = [get_lang('Wall messages posted by him/herself'), $wallMessagesPosted];

    $friends = SocialManager::getCountFriends($userId);
    $data[] = [get_lang('Friends'), $friends];

    $countSent = SocialManager::getCountInvitationSent($userId);
    $data[] = [get_lang('Invitation sent'), $countSent];

    $countReceived = SocialManager::get_message_number_invitation_by_user_id($userId);
    $data[] = [get_lang('Invitation received'), $countReceived];

    $params['social'] = [
        'friends' => $friends,
        'invitation_sent' => $countSent,
        'invitation_received' => $countReceived,
        'messages_posted' => $wallMessagesPosted,
        'messages_sent' => $messagesSent,
        'messages_received' => $messagesReceived,
    ];
}

/**
 * Show the sessions in which this user is subscribed.
 */
//$sessions = SessionManager::get_sessions_by_user($userId, true);
$sessions = Container::getSessionRepository()->getSessionsByUser($user, api_get_url_entity());

$personal_course_list = [];
$courseToolInformationTotal = null;
$sessionInformation = '';
if (count($sessions) > 0) {
    $header = [
        [get_lang('Course code'), true],
        [get_lang('Title'), true],
        [get_lang('Status'), true],
        [get_lang('Time spent in the course'), true],
        [get_lang('Total posts in all forums.'), true],
        ['', false],
    ];

    $headerList = [];
    foreach ($header as $item) {
        $headerList[] = $item[0];
    }

    $csvContent[] = [];
    $csvContent[] = [get_lang('Course sessions')];

    foreach ($sessions as $session) {
        $data = [];
        $personal_course_list = [];
        $sessionId = $session->getId();

        $csvContent[] = [$session->getName()];
        $csvContent[] = $headerList;
        foreach ($session->getCourses() as $sessionRelCourse) {
            $course = $sessionRelCourse->getCourse();
            $courseId = $sessionRelCourse->getCourse()->getId();
            $courseCode = $sessionRelCourse->getCourse()->getCode();

            $courseUrl = api_get_course_url($courseCode);

            $sessionStatus = SessionManager::get_user_status_in_course_session(
                $userId,
                $courseId,
                $sessionId
            );
            $status = null;
            switch ($sessionStatus) {
                case 0:
                case STUDENT:
                    $status = get_lang('Learner');
                    break;
                case 2:
                    $status = get_lang('Course coach');
                    break;
            }

            $tools = Display::url(
                Display::return_icon('statistics.png', get_lang('Statistics')),
                api_get_path(WEB_CODE_PATH).'mySpace/myStudents.php?details=true&student='.$userId.'&id_session='.$sessionId.'&course='.$courseCode
            );
            $tools .= '&nbsp;<a href="course_information.php?code='.$courseCode.'&id_session='.$sessionId.'">'.
                Display::return_icon('info2.png', get_lang('Overview')).'</a>'.
                '<a href="'.$courseUrl.'?id_session='.$sessionId.'">'.
                Display::return_icon('course_home.png', get_lang('Course home')).'</a>';

            /*if (!empty($my_course['status']) && STUDENT == $my_course['status']) {
                $tools .= '<a
                    href="user_information.php?action=unsubscribe_session_course&course_id='.$courseId.'&user_id='.$userId.'&id_session='.$sessionId.'">'.
                    Display::return_icon('delete.png', get_lang('Delete')).'</a>';
            }*/

            $timeSpent = api_time_to_hms(
                Tracking::get_time_spent_on_the_course(
                    $userId,
                    $courseId,
                    $sessionId
                )
            );

            $totalForumMessages = Container::getForumPostRepository()->countUserForumPosts(
                $user,
                $course,
                $session
            );

            $row = [
                Display::url(
                    $courseCode,
                    $courseUrl.'?id_session='.$sessionId
                ),
                $course->getTitle(),
                $status,
                $timeSpent,
                $totalForumMessages,
                $tools,
            ];

            $csvContent[] = array_map('strip_tags', $row);
            $data[] = $row;

            /*$result = Tracking::getToolInformation(
                $userId,
                $courseInfo,
                $sessionId
            );
            if (!empty($result['html'])) {
                $courseToolInformationTotal .= $result['html'];
                $csvContent = array_merge($csvContent, $result['array']);
            }*/
        }

        $dates = array_filter(
            [
                $session->getAccessStartDate()->format('Y-m-d H:i:s'),
                $session->getAccessEndDate()->format('Y-m-d H:i:s'),
            ]
        );

        $certificateLink = Display::url(
            Display::return_icon('pdf.png', get_lang('CertificateOfAchievement'), [], ICON_SIZE_SMALL),
            api_get_path(WEB_CODE_PATH).'mySpace/session.php?'
            .http_build_query(
                [
                    'action' => 'export_to_pdf',
                    'type' => 'achievement',
                    'session_to_export' => $sessionId,
                    'student' => $userId,
                ]
            ),
            ['target' => '_blank']
        );
        $sessionInformation .= Display::page_subheader(
            '<a href="'.api_get_path(WEB_CODE_PATH).'session/resume_session.php?id_session='.$sessionId.'">'.
            $session->getName().'</a>',
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
    $sessionInformation = '<p>'.get_lang('NoCourse sessionsForThisUser').'</p>';
}
$courseToolInformationTotal = '';

$courseRelUserList = Container::getCourseRepository()->getCoursesByUser($user, api_get_url_entity());

if (count($courseRelUserList) > 0) {
    $header = [
        [get_lang('Course code')],
        [get_lang('Title')],
        [get_lang('Status')],
        [get_lang('Time spent in the course')],
        [get_lang('Total posts in all forums.')],
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
    $courseToolInformationTotal = null;
    foreach ($courseRelUserList as $courseRelUser) {
        $course = $courseRelUser->getCourse();
        $courseId = $course->getId();
        $courseCode = $course->getCode();
        $courseToolInformation = null;

        $courseUrl = api_get_course_url($courseCode);
        $tools = Display::url(
            Display::return_icon('statistics.png', get_lang('Statistics')),
            api_get_path(WEB_CODE_PATH).'mySpace/myStudents.php?details=true&student='.$userId.'&id_session=0&course='.$courseCode
        );
        $tools .= '&nbsp;<a href="course_information.php?code='.$courseCode.'">'.
            Display::return_icon('info2.png', get_lang('Overview')).'</a>'.
            '<a href="'.$courseUrl.'">'.
            Display::return_icon('course_home.png', get_lang('Course home')).'</a>'.
            '<a href="course_edit.php?id='.$courseId.'">'.
            Display::return_icon('edit.png', get_lang('Edit')).'</a>';
        if (STUDENT == $courseRelUser->getStatus()) {
            $tools .= '<a href="user_information.php?action=unsubscribe&course_id='.$courseId.'&user_id='.$userId.'">'.
                Display::return_icon('delete.png', get_lang('Delete')).'</a>';
        }

        $timeSpent = api_time_to_hms(
            Tracking::get_time_spent_on_the_course(
                $userId,
                $courseId
            )
        );

        $totalForumMessages = Container::getForumPostRepository()->countUserForumPosts($user, $course);

        $row = [
            Display::url($courseCode, $courseUrl),
            $course->getTitle(),
            STUDENT == $courseRelUser->getStatus() ? get_lang('Learner') : get_lang('Trainer'),
            $timeSpent,
            $totalForumMessages,
            $tools,
        ];

        $csvContent[] = array_map('strip_tags', $row);
        $data[] = $row;

        /*$result = Tracking::getToolInformation(
            $userId,
            $courseInfo,
            0
        );
        $courseToolInformationTotal .= $result['html'];
        $csvContent = array_merge($csvContent, $result['array']);*/
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
    $courseInformation = '<p>'.get_lang('This user isn\'t subscribed in a course').'</p>';
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

        $urlInformation = Display::page_subheader(get_lang('URL list'));
        $urlInformation .= Display::return_sortable_table(
            $header,
            $data,
            [],
            [],
            ['user_id' => $userId]
        );
    } else {
        $urlInformation = '<p>'.get_lang('This user doesn\'t have a related URL.').'</p>';
    }
}

if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'send_legal':
            $subject = get_lang('Send legal agreementSubject');
            $content = sprintf(
                get_lang('Send legal agreementDescriptionToUrlX'),
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

            if (STUDENT == CourseManager::getUserInCourseStatus($userId, $courseInfo['real_id'])) {
                CourseManager::unsubscribe_user($userId, $courseInfo['code'], $sessionId);
                Display::addFlash(Display::return_message(get_lang('User is now unsubscribed')));
            } else {
                Display::addFlash(Display::return_message(
                    get_lang('User can not be unsubscribed because he is one of the teachers.'),
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
            Display::addFlash(Display::return_message(get_lang('User is now unsubscribed')));
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
    echo Display::page_subheader(get_lang('Superior (n+1)List'));
    echo $studentBossListToString;
}

$em = Database::getManager();
$userRepository = UserManager::getRepository();

$hrmList = $userRepository->getAssignedHrmUserList($user->getId(), api_get_current_access_url_id());

if ($hrmList) {
    echo Display::page_subheader(get_lang('Human Resource Managers list'));
    echo '<div class="row">';
    $repo = Container::getIllustrationRepository();
    foreach ($hrmList as $hrm) {
        $url = $repo->getIllustrationUrl($hrm);
        $fullName = UserManager::formatUserFullName($hrm);
        echo '<div class="col-sm-4 col-md-3">';
        echo '<div class="media">';
        echo '<div class="media-left">';
        echo Display::img($url, $fullName, ['class' => 'media-object'], false);
        echo '</div>';
        echo '<div class="media-body">';
        echo '<h4 class="media-heading">'.$fullName.'</h4>';
        echo $hrm->getUsername();
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }
    echo '</div>';
}

if (DRH == $user->getStatus()) {
    $usersAssigned = UserManager::get_users_followed_by_drh($userId);
    if ($usersAssigned) {
        echo Display::page_subheader(get_lang('List of users assigned to Human Resources manager'));
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
$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('Administration')];
$interbreadcrumb[] = ['url' => 'user_list.php', 'name' => get_lang('User list')];
$tpl = new Template(null, false, false, false, false, false, false);

$tpl->assign('social_tool', $socialTool);
$tpl->assign('user', $user);
$tpl->assign('params', $params);

$layoutTemplate = $tpl->get_template('admin/user_information.tpl');
$content = $tpl->fetch($layoutTemplate);
echo $content;

echo Display::page_subheader(get_lang('Session list'), null, 'h3', ['class' => 'section-title']);
echo $sessionInformation;

echo Display::page_subheader(get_lang('Course list'), null, 'h3', ['class' => 'section-title']);
echo $courseInformation;
echo $urlInformation;

echo Tracking::displayUserSkills(
    $userId,
    0,
    0
);
if (api_get_configuration_value('allow_career_users')) {
    $careers = UserManager::getUserCareers($userId);
    if (!empty($careers)) {
        echo Display::page_subheader(get_lang('Careers'), null, 'h3', ['class' => 'section-title']);
        $table = new HTML_Table(['class' => 'data_table']);
        $table->setHeaderContents(0, 0, get_lang('Career'));
        $row = 1;
        foreach ($careers as $carerData) {
            $table->setCellContents($row, 0, $carerData['name']);
            $row++;
        }
        echo $table->toHtml();
    }
}

Display::display_footer();
