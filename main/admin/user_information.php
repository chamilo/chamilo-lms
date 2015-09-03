<?php
/* For licensing terms, see /license.txt */

/**
 * Script showing information about a user (name, e-mail, courses and sessions)
 * @author Bart Mollet
 * @package chamilo.admin
 */

$cidReset = true;
require_once '../inc/global.inc.php';
$this_section = SECTION_PLATFORM_ADMIN;
require_once api_get_path(SYS_CODE_PATH).'forum/forumfunction.inc.php';
require_once api_get_path(SYS_CODE_PATH).'work/work.lib.php';

api_protect_admin_script();

$interbreadcrumb[] = array("url" => 'index.php', "name" => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array("url" => 'user_list.php', "name" => get_lang('UserList'));
if (!isset($_GET['user_id'])) {
    api_not_allowed();
}
$user = api_get_user_info($_GET['user_id'], true);
$tool_name = $user['complete_name'].(empty($user['official_code'])?'':' ('.$user['official_code'].')');
$table_course_user = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
$table_course = Database :: get_main_table(TABLE_MAIN_COURSE);

// only allow platform admins to login_as, or session admins only for students (not teachers nor other admins)
$login_as_icon = null;
$editUser = null;
if (api_is_platform_admin()) {
    $login_as_icon =
        '<a href="'.api_get_path(WEB_CODE_PATH).'admin/user_list.php'
        .'?action=login_as&user_id='.$user['user_id'].'&'
        .'sec_token='.$_SESSION['sec_token'].'">'
        .Display::return_icon('login_as.png', get_lang('LoginAs'),
            array(), ICON_SIZE_MEDIUM).'</a>';
    $editUser = Display::url(
        Display::return_icon(
            'edit.png',
            get_lang('Edit'),
            array(),
            ICON_SIZE_MEDIUM
        ),
        api_get_path(WEB_CODE_PATH).'admin/user_edit.php?user_id='.$user['user_id']
    );

    $exportLink = Display::url(
        Display::return_icon(
            'export_csv.png', get_lang('ExportAsCSV'),'', ICON_SIZE_MEDIUM
        ),
        api_get_self().'?user_id='.$user['user_id'].'&action=export'
    );
}

// Show info about who created this user and when
$creatorId = $user['creator_id'];
$creatorInfo = api_get_user_info($creatorId);
$registrationDate = $user['registration_date'];

$csvContent = array();

$table = new HTML_Table(array('class' => 'data_table'));
$table->setHeaderContents(0, 0, get_lang('Information'));
$csvContent[] = get_lang('Information');
$data = array(
    get_lang('Name') => $user['complete_name'],
    get_lang('Email') => $user['email'],
    get_lang('Phone') => $user['phone'],
    get_lang('OfficialCode') => $user['official_code'],
    get_lang('Online') => $user['user_is_online'] ?
        Display::return_icon('online.png') : Display::return_icon(
            'offline.png'
        ),
    get_lang('Status') => $user['status'] == 1 ? get_lang('Teacher') : get_lang(
        'Student'
    ),
    null => sprintf(
        get_lang('CreatedByXYOnZ'),
        'user_information.php?user_id='
        .$creatorId,
        $creatorInfo['username'],
        api_get_utc_datetime($registrationDate)
    ),
);

$row = 1;
foreach ($data as $label => $item) {
    if (!empty($label)) {
        $label = $label.': ';
    }
    $table->setCellContents($row, 0, $label.$item);
    $csvContent[] = array($label, strip_tags($item));
    $row++;
}
$userInformation = $table->toHtml();

$table = new HTML_Table(array('class' => 'data_table'));
$table->setHeaderContents(0, 0, get_lang('Tracking'));
$csvContent[] = get_lang('Tracking');
$data = array(
    get_lang('FirstLogin') => Tracking :: get_first_connection_date($user['user_id']),
    get_lang('LatestLogin') => Tracking :: get_last_connection_date($user['user_id'], true)
);
$row = 1;
foreach ($data as $label => $item) {
    if (!empty($label)) {
        $label = $label.': ';
    }
    $table->setCellContents($row, 0, $label.$item);
    $csvContent[] = array($label, strip_tags($item));
    $row++;
}
$trackingInformation = $table->toHtml();

$tbl_session_course = Database:: get_main_table(TABLE_MAIN_SESSION_COURSE);
$tbl_session_course_user = Database:: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
$tbl_session = Database:: get_main_table(TABLE_MAIN_SESSION);
$tbl_course = Database:: get_main_table(TABLE_MAIN_COURSE);
$tbl_user = Database:: get_main_table(TABLE_MAIN_USER);

$user_id = $user['user_id'];
$sessions = SessionManager::get_sessions_by_user($user_id, true);
$personal_course_list = array();
$courseToolInformationTotal = null;
if (count($sessions) > 0) {
    $sessionInformation = null;
    $header = array(
        array(get_lang('Code'), true),
        array(get_lang('Title'), true),
        array(get_lang('Status'), true),
        array(get_lang('TimeSpentInTheCourse'), true),
        array(get_lang('TotalPostsInAllForums'), true),
        array('', false)
    );

    $headerList = array();
    foreach ($header as $item) {
        $headerList[] = $item[0];
    }

    $csvContent[] = array();
    $csvContent[] = array(get_lang('Sessions'));

    foreach ($sessions as $session_item) {
        $data = array();
        $personal_course_list = array();
        $id_session = $session_item['session_id'];

        $csvContent[] = array($session_item['session_name']);
        $csvContent[] = $headerList;
        foreach ($session_item['courses'] as $my_course) {
            $courseInfo = api_get_course_info_by_id($my_course['real_id']);
            $sessionStatus = SessionManager::get_user_status_in_session(
                $user['user_id'],
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
            $tools = '<a href="course_information.php?code='.$courseInfo['code'].'&id_session='.$id_session.'">'.
                Display::return_icon('synthese_view.gif', get_lang('Overview')).'</a>'.
                '<a href="'.$courseInfo['course_public_url'].'?id_session='.$id_session.'">'.
                Display::return_icon('course_home.gif', get_lang('CourseHomepage')).'</a>';

            if ($my_course['status'] == STUDENT) {
                $tools .= '<a href="user_information.php?action=unsubscribeSessionCourse&course_code='.$courseInfo['code'].'&user_id='.$user['user_id'].'&id_session='.$id_session.'">'.
                    Display::return_icon('delete.png', get_lang('Delete')).'</a>';
            }

            $timeSpent = api_time_to_hms(
                Tracking :: get_time_spent_on_the_course(
                    $user['user_id'],
                    $courseInfo['real_id'],
                    $id_session
                )
            );

            $totalForumMessages = CourseManager::getCountPostInForumPerUser(
                $user['user_id'],
                $courseInfo['real_id'],
                $id_session
            );

            $row = array(
                Display::url(
                    $courseInfo['code'],
                    $courseInfo['course_public_url'].'?id_session='.$id_session
                ),
                $courseInfo['title'],
                $status,
                $timeSpent,
                $totalForumMessages,
                $tools
            );

            $csvContent[] = array_map('strip_tags', $row);
            $data[] = $row;

            $result = TrackingUserLogCSV::getToolInformation(
                $user['user_id'],
                $courseInfo,
                $id_session
            );

            if (!empty($result['html'])) {
                $courseToolInformationTotal .= $result['html'];
                $csvContent = array_merge($csvContent, $result['array']);
            }
        }

        if ($session_item['access_start_date'] == '0000-00-00') {
            $session_item['access_start_date'] = null;
        }

        if ($session_item['access_end_date'] == '0000-00-00') {
            $session_item['access_end_date'] = null;
        }

        $dates = array_filter(
            array($session_item['access_start_date'], $session_item['access_end_date'])
        );

        $sessionInformation .= Display::page_subheader(
            '<a href="'.api_get_path(WEB_CODE_PATH).'session/resume_session.php?id_session='.$id_session.'">'.
            $session_item['session_name'].'</a>',
            ' '.implode(' - ', $dates)
        );

        $sessionInformation .= Display::return_sortable_table(
            $header,
            $data,
            array(),
            array(),
            array('user_id' => intval($_GET['user_id']))
        );
        $sessionInformation .= $courseToolInformationTotal;
    }
} else {
    $sessionInformation = '<p>'.get_lang('NoSessionsForThisUser').'</p>';
}
$courseToolInformationTotal = null;

/**
 * Show the courses in which this user is subscribed
 */
$sql = 'SELECT * FROM '.$table_course_user.' cu, '.$table_course.' c
        WHERE
            cu.user_id = '.$user['user_id'].' AND
            cu.c_id = c.id AND
            cu.relation_type <> '.COURSE_RELATION_TYPE_RRHH.' ';
$res = Database::query($sql);
if (Database::num_rows($res) > 0) {
    $header = array(
        array(get_lang('Code')),
        array(get_lang('Title')),
        array(get_lang('Status')),
        array(get_lang('TimeSpentInTheCourse')),
        array(get_lang('TotalPostsInAllForums')),
        array('')
    );

    $headerList = array();
    foreach ($header as $item) {
        $headerList[] = $item[0];
    }
    $csvContent[] = array();
    $csvContent[] = array(get_lang('Courses'));
    $csvContent[] = $headerList;

    $data = array();
    $courseToolInformationTotal = null;
    while ($course = Database::fetch_object($res)) {
        $courseInfo = api_get_course_info_by_id($course->c_id);
        $courseCode = $courseInfo['code'];
        $courseToolInformation = null;

        $tools = '<a href="course_information.php?code='.$courseCode.'">'.
            Display::return_icon('synthese_view.gif', get_lang('Overview')).'</a>'.
            '<a href="'.$courseInfo['course_public_url'].'">'.
            Display::return_icon('course_home.gif', get_lang('CourseHomepage')).'</a>' .
            '<a href="course_edit.php?id='.$course->c_id.'">'.
            Display::return_icon('edit.gif', get_lang('Edit')).'</a>';
        if ($course->status == STUDENT) {
            $tools .= '<a href="user_information.php?action=unsubscribe&course_code='.$courseCode.'&user_id='.$user['user_id'].'">'.
                Display::return_icon('delete.png', get_lang('Delete')).'</a>';
        }

        $timeSpent = api_time_to_hms(
            Tracking :: get_time_spent_on_the_course(
                $user['user_id'],
                $courseInfo['real_id'],
                0
            )
        );

        $totalForumMessages = CourseManager::getCountPostInForumPerUser(
            $user['user_id'],
            $course->id,
            0
        );

        $row = array(
            Display::url($courseCode, $courseInfo['course_public_url']),
            $course->title,
            $course->status == STUDENT ? get_lang('Student') : get_lang('Teacher'),
            $timeSpent,
            $totalForumMessages,
            $tools,
        );

        $csvContent[] = array_map('strip_tags', $row);
        $data[] = $row;

        $result = TrackingUserLogCSV::getToolInformation(
            $user['user_id'],
            $courseInfo,
            0
        );
        $courseToolInformationTotal .= $result['html'];
        $csvContent = array_merge($csvContent, $result['array']);
    }

    $courseInformation = Display::page_subheader(get_lang('Courses'));
    $courseInformation .= Display::return_sortable_table(
        $header,
        $data,
        array(),
        array(),
        array('user_id' => intval($_GET['user_id']))
    );
    $courseInformation .= $courseToolInformationTotal;
} else {
    $courseInformation = '<p>'.get_lang('NoCoursesForThisUser').'</p>';
}

/**
 * Show the URL in which this user is subscribed
 */
$urlInformation = null;
if (api_is_multiple_url_enabled()) {
    $urlList = UrlManager::get_access_url_from_user($user['user_id']);
    if (count($urlList) > 0) {
        $header = array();
        $header[] = array('URL', true);
        $data = array();

        $csvContent[] = array();
        $csvContent[] = array('Url');
        foreach ($urlList as $url) {
            $row = array();
            $row[] = Display::url($url['url'], $url['url']);
            $csvContent[] = array_map('strip_tags', $row);
            $data[] = $row;
        }

        $urlInformation = Display::page_subheader(get_lang('URLList'));
        $urlInformation .= Display::return_sortable_table(
            $header,
            $data,
            array(),
            array(),
            array('user_id' => intval($_GET['user_id']))
        );
    } else {
        $urlInformation = '<p>'.get_lang('NoUrlForThisUser').'</p>';
    }
}
$message = null;

if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'unsubscribe':
            if (CourseManager::get_user_in_course_status($_GET['user_id'], $_GET['course_code']) == STUDENT) {
                CourseManager::unsubscribe_user($_GET['user_id'], $_GET['course_code'], $_GET['id_session']);
                $message = Display::return_message(get_lang('UserUnsubscribed'));
            } else {
                $message = Display::return_message(
                    get_lang('CannotUnsubscribeUserFromCourse'),
                    'error'
                );
            }
            break;
        case 'unsubscribeSessionCourse':
            SessionManager::removeUsersFromCourseSession(
                array($_GET['user_id']),
                $_GET['id_session'],
                api_get_course_info($_GET['course_code'])
            );
            $message = Display::return_message(get_lang('UserUnsubscribed'));
            break;
        case 'export':
            Export :: arrayToCsv($csvContent, 'user_information_'.$user);
            exit;
            break;
    }
}

Display::display_header($tool_name);

echo '<div class="actions">
    <a href="'.api_get_path(WEB_CODE_PATH).'mySpace/myStudents.php?student='.intval($_GET['user_id']).'" title="'.get_lang('Reporting').'">'.
    Display::return_icon('statistics.png', get_lang('Reporting'), '', ICON_SIZE_MEDIUM).'
    </a>
    '.$login_as_icon.'
    '.$editUser.'
    '.$exportLink.'
</div>';

echo Display::page_header($tool_name);


$fullUrlBig = UserManager::getUserPicture(
    $user['user_id'],
    USER_IMAGE_SIZE_BIG
);

$fullUrl = UserManager::getUserPicture(
    $user['user_id'],
    USER_IMAGE_SIZE_ORIGINAL
);

echo '<div class="row">';
echo '<div class="col-md-2">';
echo '<a class="expand-image" href="'.$fullUrlBig.'">'
    .'<img src="'.$fullUrl.'" /></a><br />';
echo '</div>';

echo $message;

echo '<div class="col-md-5">';
echo $userInformation;
echo '</div>';

echo '<div class="col-md-5">';
echo $trackingInformation;
echo '</div>';
echo '</div>';

echo Display::page_subheader(get_lang('SessionList'));
echo $sessionInformation;
echo $courseInformation;
echo $urlInformation;

Display::display_footer();
