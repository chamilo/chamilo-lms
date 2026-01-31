<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Enums\ObjectIcon;
use Chamilo\CoreBundle\Enums\StateIcon;
use Chamilo\CoreBundle\Enums\ToolIcon;
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
        Display::getMdiIcon(
            ToolIcon::TRACKING,
            'ch-tool-icon',
            null,
            ICON_SIZE_MEDIUM,
            get_lang('Reporting')
        ),
        api_get_path(WEB_CODE_PATH).'my_space/myStudents.php?'.http_build_query([
            'student' => $userId,
        ]),
        ['title' => get_lang('Reporting')]
    ),
];

if (api_can_login_as($userId)) {
    // Using get_token() is safe here because it generates the token if missing.
    $secToken = Security::get_token();
    $actions[] = Display::url(
        Display::getMdiIcon(
            ActionIcon::LOGIN_AS,
            'ch-tool-icon',
            null,
            ICON_SIZE_MEDIUM,
            get_lang('Login as')
        ),
        api_get_path(WEB_CODE_PATH).
        'admin/user_list.php?action=login_as&user_id='.$userId.'&sec_token='.$secToken
    );
}

if (api_is_platform_admin()) {
    $actions[] = Display::url(
        Display::getMdiIcon(
            ActionIcon::EDIT,
            'ch-tool-icon',
            null,
            ICON_SIZE_MEDIUM,
            get_lang('Edit')
        ),
        api_get_path(WEB_CODE_PATH).'admin/user_edit.php?user_id='.$userId
    );

    $actions[] = Display::url(
        Display::getMdiIcon(
            ActionIcon::EXPORT_CSV,
            'ch-tool-icon',
            null,
            ICON_SIZE_MEDIUM,
            get_lang('CSV export')
        ),
        api_get_self().'?user_id='.$userId.'&action=export'
    );
    $actions[] = Display::url(
        Display::getMdiIcon(
            ObjectIcon::VCARD,
            'ch-tool-icon',
            null,
            ICON_SIZE_MEDIUM,
            get_lang('user information')
        ),
        api_get_path(WEB_PATH).'main/social/vcard_export.php?userId='.$userId
    );
    $actions[] = Display::url(
        Display::getMdiIcon(
            ObjectIcon::GROUP,
            'ch-tool-icon',
            null,
            ICON_SIZE_MEDIUM,
            get_lang('Add Human Resources Manager to user')
        ),
        api_get_path(WEB_CODE_PATH).'admin/add_drh_to_user.php?u='.$userId
    );

    if (SkillModel::isAllowed($userId, false)) {
        $actions[] = Display::url(
            Display::getMdiIcon(
                ObjectIcon::BADGE,
                'ch-tool-icon',
                null,
                ICON_SIZE_MEDIUM,
                get_lang('Add skill')
            ),
            api_get_path(WEB_CODE_PATH).'skills/assign.php?user='.$userId
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
    get_lang('E-mail') => $user->getEmail(),
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
    $createdAt = $user->getCreatedAt()->format('Y-m-d H:i:s');
    $userInfo['created'] = sprintf(
        get_lang('Create by <a href="%s">%s</a> on %s'),
        'user_information.php?user_id='.$creatorId,
        $creatorInfo['username'],
        api_get_utc_datetime($createdAt)
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
    $icon = Display::getMdiIcon(StateIcon::COMPLETE, 'ch-tool-icon-disabled');
    if (!empty($value['value'])) {
        [$legalId, $legalLanguageId, $legalTime] = explode(':', $value['value']);
        $icon = Display::getMdiIcon(StateIcon::COMPLETE, 'ch-tool-icon');
        $timeLegalAccept = api_get_local_time($legalTime);
        $btn = Display::url(
            get_lang('Delete legal agreement'),
            api_get_self().'?action=delete_legal&user_id='.$userId,
            ['class' => 'btn btn--danger btn-xs']
        );
    } else {
        $btn = Display::url(
            get_lang('Send legal agreement'),
            api_get_self().'?action=send_legal&user_id='.$userId,
            ['class' => 'btn btn--primary btn-xs']
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
if (false) {
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

    $params['social'] = [
        'messages_posted' => $wallMessagesPosted,
        'messages_sent' => $messagesSent,
        'messages_received' => $messagesReceived,
    ];
}

/**
 * Show the sessions in which this user is subscribed.
 */
//$sessions = SessionManager::get_sessions_by_user($userId, true);
$sessions = Container::getSessionRepository()->getSessionsByUser($user, api_get_url_entity())->getQuery()->getResult();

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

        $csvContent[] = [$session->getTitle()];
        $csvContent[] = $headerList;
        foreach ($session->getCourses() as $sessionRelCourse) {
            $course = $sessionRelCourse->getCourse();
            $courseId = $sessionRelCourse->getCourse()->getId();
            $courseCode = $sessionRelCourse->getCourse()->getCode();

            $courseUrl = api_get_course_url($courseId, $sessionId);

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
                Display::getMdiIcon(ToolIcon::TRACKING, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Statistics')),
                api_get_path(WEB_CODE_PATH).'my_space/myStudents.php?details=true&student='.$userId.'&sid='.$sessionId.'&course='.$courseCode
            );
            $tools .= '&nbsp;<a href="course_information.php?id='.$courseId.'&id_session='.$sessionId.'">'.
                Display::getMdiIcon(ActionIcon::INFORMATION, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Overview')).'</a>'.
                '<a href="'.$courseUrl.'">'.
                Display::getMdiIcon(ToolIcon::COURSE_HOME, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Course home')).'</a>';

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
                    $courseUrl
                ),
                $course->getTitle(),
                $status,
                $timeSpent,
                $totalForumMessages,
                $tools,
            ];

            $csvContent[] = array_map('strip_tags', $row);
            $data[] = $row;
        }

        $dates = SessionManager::parseSessionDates($session);

        $certificateLink = Display::url(
            Display::getMdiIcon(ActionIcon::EXPORT_PDF, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Certificate of achievement')),
            api_get_path(WEB_CODE_PATH).'my_space/session.php?'
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
            $session->getTitle().'</a>',
            $certificateLink.' '.$dates['access']
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
    $sessionInformation = '<p>'.get_lang('No course sessions for this user').'</p>';
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

        $courseUrl = api_get_course_url($courseId);
        $tools = Display::url(
            Display::getMdiIcon(ToolIcon::TRACKING, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Statistics')),
            api_get_path(WEB_CODE_PATH).'my_space/myStudents.php?details=true&student='.$userId.'&sid=0&course='.$courseCode
        );
        $tools .= '&nbsp;<a href="course_information.php?id='.$courseId.'">'.
            Display::getMdiIcon(ActionIcon::INFORMATION, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Overview')).'</a>'.
            '<a href="'.$courseUrl.'">'.
            Display::getMdiIcon(ToolIcon::COURSE_HOME, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Course home')).'</a>'.
            '<a href="course_edit.php?id='.$courseId.'">'.
            Display::getMdiIcon(ActionIcon::EDIT, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Edit')).'</a>';
        if (STUDENT == $courseRelUser->getStatus()) {
            $tools .= '<a href="user_information.php?action=unsubscribe&course_id='.$courseId.'&user_id='.$userId.'">'.
                Display::getMdiIcon(ActionIcon::DELETE, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Delete')).'</a>';
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
            $subject = get_lang('Legal conditions');
            $content = sprintf(
                get_lang('Please accept our legal conditions here: %s'),
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
        case 'export':
            Export::arrayToCsv(
                $csvContent,
                'user_information_'.$userId
            );
            exit;
    }
}

$flashMessage = '';
if (!empty($_SESSION['flash_message'])) {
    $messageType = isset($_SESSION['flash_message']['type']) ? $_SESSION['flash_message']['type'] : 'warning';
    $messageText = isset($_SESSION['flash_message']['message']) ? $_SESSION['flash_message']['message'] : '';
    $flashMessage = Display::return_message($messageText, $messageType);
    unset($_SESSION['flash_message']);
}
Display::display_header($tool_name);
echo Display::toolbarAction('toolbar-user-information', [implode(PHP_EOL, $actions)]);
echo $flashMessage;

$achievedSkillsTitle = get_lang('Achieved skills');
$viewBadgeLabel = get_lang('View badge');
$defaultBadge = api_get_path(WEB_PATH).'img/icons/32/badges-default.png';
$apiUserSkillsUrl = api_get_path(WEB_PATH).'api/users/'.$userId.'/skills';

echo '<div class="space-y-8">';

$fullUrlBig = UserManager::getUserPicture(
    $userId,
    USER_IMAGE_SIZE_BIG
);

$fullUrl = UserManager::getUserPicture(
    $userId,
    USER_IMAGE_SIZE_ORIGINAL
);

if ($studentBossList) {
    echo Display::page_subheader(get_lang('Superior (n+1)'));
    echo $studentBossListToString;
}

$em = Database::getManager();
$userRepository = Container::getUserRepository();

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

// Sessions
echo '<div class="bg-white border border-gray-30 rounded-2xl shadow-sm p-6">';
echo Display::page_subheader(get_lang('Session list'), null, 'h3', ['class' => 'section-title mb-4']);
echo '<div class="overflow-x-auto">';
echo $sessionInformation;
echo '</div>';
echo '</div>';

// Courses
echo '<div class="bg-white border border-gray-30 rounded-2xl shadow-sm p-6">';
echo Display::page_subheader(get_lang('Course list'), null, 'h3', ['class' => 'section-title mb-4']);
echo '<div class="overflow-x-auto">';
echo $courseInformation;
echo $urlInformation;
echo '</div>';
echo '</div>';

// Achieved skills (keep legacy output, enhance visually when possible)
$legacySkillsHtml = Tracking::displayUserSkills($userId, 0, 0);

echo '<div class="bg-white border border-gray-30 rounded-2xl shadow-sm p-6">';
echo '<div id="achieved-skills-section"'
    .' data-api-url="'.htmlspecialchars($apiUserSkillsUrl, ENT_QUOTES).'"'
    .' data-user-id="'.htmlspecialchars((string) $userId, ENT_QUOTES).'"'
    .' data-default-badge="'.htmlspecialchars($defaultBadge, ENT_QUOTES).'"'
    .' data-title="'.htmlspecialchars($achievedSkillsTitle, ENT_QUOTES).'"'
    .' data-view-badge="'.htmlspecialchars($viewBadgeLabel, ENT_QUOTES).'"'
    .' data-origin="'.htmlspecialchars($currentUrl, ENT_QUOTES).'"'
    .'>';

// Enhanced container (hidden until rendered)
echo '<div id="achieved-skills-enhanced" class="hidden"></div>';

// Legacy container (always rendered, can be hidden by JS after enhanced view is ready)
echo '<div id="achieved-skills-legacy">'.$legacySkillsHtml.'</div>';

echo '</div>';
echo '</div>';

// Careers
if ('true' === api_get_setting('session.allow_career_users')) {
    $careers = UserManager::getUserCareers($userId);
    if (!empty($careers)) {
        echo '<div class="bg-white border border-gray-30 rounded-2xl shadow-sm p-6">';
        echo Display::page_subheader(get_lang('Careers'), null, 'h3', ['class' => 'section-title mb-4']);
        $table = new HTML_Table(['class' => 'data_table']);
        $table->setHeaderContents(0, 0, get_lang('Career'));
        $row = 1;
        foreach ($careers as $carerData) {
            $table->setCellContents($row, 0, $carerData['title']);
            $row++;
        }
        echo '<div class="overflow-x-auto">'.$table->toHtml().'</div>';
        echo '</div>';
    }
}

// Close spacing wrapper
echo '</div>';

// Client-side enhancement: show badges grid using the API, keep legacy as fallback.
echo '<script>
(function () {
  var root = document.getElementById("achieved-skills-section");
  if (!root) return;

  var apiUrl = root.getAttribute("data-api-url") || "";
  var userId = root.getAttribute("data-user-id") || "";
  var defaultBadge = root.getAttribute("data-default-badge") || "/img/icons/32/badges-default.png";
  var titleText = root.getAttribute("data-title") || "Achieved skills";
  var viewBadgeText = root.getAttribute("data-view-badge") || "View badge";
  var enhanced = document.getElementById("achieved-skills-enhanced");
  var legacy = document.getElementById("achieved-skills-legacy");

  if (!apiUrl || !userId || !enhanced || !legacy) return;

  var origin = window.location.pathname + window.location.search;

  function normalizeImageUrl(url) {
    if (!url || typeof url !== "string") return "";
    if (url.indexOf("http://") === 0 || url.indexOf("https://") === 0 || url.indexOf("/") === 0) return url;
    return "/" + url;
  }

  function safeId(value) {
    if (value === null || value === undefined) return "";
    return String(value);
  }

  function createEl(tag, className, text) {
    var el = document.createElement(tag);
    if (className) el.className = className;
    if (text !== undefined && text !== null) el.textContent = text;
    return el;
  }

  function buildGrid(skills) {
    var container = createEl("div", "space-y-4");

    var header = createEl("div", "flex items-center justify-between gap-3");
    var title = createEl("h3", "text-lg font-semibold text-gray-900", titleText);
    header.appendChild(title);
    container.appendChild(header);

    var grid = createEl("div", "grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6");
    skills.forEach(function (s) {
      var id = safeId(s.id ?? s.skillId ?? s.skill_id);
      var name = (s.name ?? s.title ?? "").toString();
      var image = normalizeImageUrl(s.image ?? s.badge ?? s.illustrationUrl ?? "");
      if (!image) image = defaultBadge;

      var a = document.createElement("a");
      a.className = "group flex flex-col items-center text-center p-3 rounded-2xl border border-gray-30 bg-white hover:bg-gray-15 hover:border-gray-200 transition";
      a.title = name || "";
      a.href = "/main/skills/issued_all.php?skill=" + encodeURIComponent(id) +
        "&user=" + encodeURIComponent(userId) +
        "&origin=" + encodeURIComponent(origin);

      var badgeWrap = createEl("div", "w-16 h-16 sm:w-18 sm:h-18 flex items-center justify-center rounded-2xl bg-gray-15 border border-gray-30 shadow-sm overflow-hidden");

      var img = document.createElement("img");
      img.className = "w-12 h-12 sm:w-14 sm:h-14 object-contain";
      img.loading = "lazy";
      img.alt = name || "";
      img.src = image;
      img.onerror = function () {
        if (img.src && img.src.indexOf(defaultBadge) === -1) {
          img.src = defaultBadge;
        }
      };

      badgeWrap.appendChild(img);

      var nameEl = createEl("div", "mt-2 text-sm font-semibold text-gray-900 leading-snug line-clamp-2", name || "");
      var hintEl = createEl("div", "mt-1 text-xs text-gray-600 group-hover:text-gray-700", viewBadgeText);

      a.appendChild(badgeWrap);
      a.appendChild(nameEl);
      a.appendChild(hintEl);

      grid.appendChild(a);
    });

    container.appendChild(grid);
    return container;
  }

  fetch(apiUrl, { credentials: "same-origin" })
    .then(function (res) {
      if (!res.ok) throw new Error("HTTP " + res.status);
      return res.json();
    })
    .then(function (data) {
      if (!Array.isArray(data) || data.length === 0) return;

      enhanced.innerHTML = "";
      enhanced.appendChild(buildGrid(data));
      enhanced.classList.remove("hidden");
      legacy.classList.add("hidden");
    })
    .catch(function (err) {
      console.warn("Unable to load achieved skills badges:", err);
    });
})();
</script>';

Display::display_footer();
