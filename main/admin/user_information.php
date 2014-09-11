<?php
/* For licensing terms, see /license.txt */
/**
 * Script showing information about a user (name, e-mail, courses and sessions)
 * @author Bart Mollet
 * @package chamilo.admin
 */

// name of the language file that needs to be included
$language_file = array('registration', 'index', 'tracking', 'exercice', 'admin', 'gradebook');
$cidReset = true;
require_once '../inc/global.inc.php';
$this_section = SECTION_PLATFORM_ADMIN;
require_once api_get_path(LIBRARY_PATH).'export.lib.inc.php';

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
        .'?action=login_as&amp;user_id='.$user['user_id'].'&amp;'
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
            'export_csv.png', get_lang('ExportAsCSV'),'', ICON_SIZE_MEDIUM),
        api_get_self().'?user_id='.$user['user_id'].'&action=export'
    );
}

// Getting the user image
$sysdir_array = UserManager::get_user_picture_path_by_id($user['user_id'], 'system', false, true);
$sysdir = $sysdir_array['dir'];
$webdir_array = UserManager::get_user_picture_path_by_id($user['user_id'], 'web', false, true);
$webdir = $webdir_array['dir'];
$fullurl = $webdir.$webdir_array['file'];
$system_image_path = $sysdir.$webdir_array['file'];
list($width, $height, $type, $attr) = @getimagesize($system_image_path);
$resizing = (($height > 200) ? 'height="200"' : '');
$height += 30;
$width += 30;
$window_name = 'window'.uniqid('');
$onclick = $window_name."=window.open('".$fullurl."','".$window_name
    ."','alwaysRaised=yes, alwaysLowered=no,alwaysOnTop=yes,toolbar=no,"
    ."location=no,directories=no,status=no,menubar=no,scrollbars=no,"
    ."resizable=no,width=".$width.",height=".$height.",left=200,top=20');"
    ." return false;";

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
        Display::return_icon('online.png') : Display::return_icon('offline.png'),
    get_lang('Status') => $user['status'] == 1 ? get_lang('Teacher') : get_lang('Student'),
    null => sprintf(get_lang('CreatedByXYOnZ'), 'user_information.php?user_id='
        .$creatorId, $creatorInfo['username'], api_get_utc_datetime($registrationDate))
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
            $course_info = api_get_course_info($my_course['code']);
            $sessionStatus = SessionManager::get_user_status_in_session(
                $user['user_id'],
                $my_course['code'],
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
            $tools = '<a href="course_information.php?code='.$course_info['code'].'&id_session='.$id_session.'">'.
                Display::return_icon('synthese_view.gif', get_lang('Overview')).'</a>'.
                '<a href="'.api_get_path(WEB_COURSE_PATH).$course_info['path'].'?id_session='.$id_session.'">'.
                Display::return_icon('course_home.gif', get_lang('CourseHomepage')).'</a>';

            if ($my_course['status'] == STUDENT) {
                $tools .= '<a href="user_information.php?action=unsubscribe&course_code='.$course_info['code'].'&user_id='.$user['user_id'].'">'.
                    Display::return_icon('delete.png', get_lang('Delete')).'</a>';
            }

            $timeSpent = api_time_to_hms(
                Tracking :: get_time_spent_on_the_course(
                    $user['user_id'],
                    $course_info['code'],
                    $id_session
                )
            );

            $totalForumMessages = CourseManager::getCountPostInForumPerUser(
                $user['user_id'],
                $course_info['real_id'],
                $id_session
            );

            $row = array(
                Display::url(
                    $my_course['code'],
                    $course_info['course_public_url'].'?id_session='.$id_session
                ),
                $course_info['title'],
                $status,
                $timeSpent,
                $totalForumMessages,
                $tools
            );

            $csvContent[] = array_map('strip_tags', $row);
            $data[] = $row;
        }

        if ($session_item['date_start'] == '0000-00-00') {
            $session_item['date_start'] = null;
        }

        if ($session_item['date_end'] == '0000-00-00') {
            $session_item['date_end'] = null;
        }

        $dates = array_filter(
            array($session_item['date_start'], $session_item['date_end'])
        );

        $sessionInformation .= Display::page_subheader(
            '<a href="'.api_get_path(WEB_CODE_PATH).'admin/resume_session.php?id_session='.$id_session.'">'.
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
    }
} else {
    $sessionInformation = '<p>'.get_lang('NoSessionsForThisUser').'</p>';
}

/**
 * Show the courses in which this user is subscribed
 */
$sql = 'SELECT * FROM '.$table_course_user.' cu, '.$table_course.' c'.
    ' WHERE cu.user_id = '.$user['user_id'].' AND cu.course_code = c.code '.
    ' AND cu.relation_type <> '.COURSE_RELATION_TYPE_RRHH.' ';
$res = Database::query($sql);
if (Database::num_rows($res) > 0) {
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
    $csvContent[] = array(get_lang('Courses'));
    $csvContent[] = $headerList;

    $data = array();
    while ($course = Database::fetch_object($res)) {

        $tools = '<a href="course_information.php?code='.$course->code.'">'.Display::return_icon('synthese_view.gif', get_lang('Overview')).'</a>'.
            '<a href="'.api_get_path(WEB_COURSE_PATH).$course->directory.'">'.Display::return_icon('course_home.gif', get_lang('CourseHomepage')).'</a>' .
            '<a href="course_edit.php?course_code='.$course->code.'">'.Display::return_icon('edit.gif', get_lang('Edit')).'</a>';
        if ($course->status == STUDENT) {
            $tools .= '<a href="user_information.php?action=unsubscribe&course_code='.$course->code.'&user_id='.$user['user_id'].'">'.Display::return_icon('delete.png', get_lang('Delete')).'</a>';
        }

        $timeSpent = api_time_to_hms(
            Tracking :: get_time_spent_on_the_course(
                $user['user_id'],
                $course->code,
                0
            )
        );

        $totalForumMessages = CourseManager::getCountPostInForumPerUser(
            $user['user_id'],
            $course->id,
            0
        );
        $courseInfo = api_get_course_info($course->code);
        $row = array (
            Display::url($course->code, $courseInfo['course_public_url']),
            $course->title,
            $course->status == STUDENT ? get_lang('Student') : get_lang('Teacher'),
            $timeSpent,
            $totalForumMessages,
            $tools,
        );

        $csvContent[] = array_map('strip_tags', $row);
        $data[] = $row;
    }
    $courseInformation = Display::page_subheader(get_lang('Courses'));
    $courseInformation .= Display::return_sortable_table(
        $header,
        $data,
        array(),
        array(),
        array('user_id' => intval($_GET['user_id']))
    );
} else {
    $courseInformation = '<p>'.get_lang('NoCoursesForThisUser').'</p>';
}

/**
 * Show the URL in which this user is subscribed
 */
$urlInformation = null;
if (api_is_multiple_url_enabled()) {
    $url_list= UrlManager::get_access_url_from_user($user['user_id']);
    if (count($url_list) > 0) {
        $header = array();
        $header[] = array('URL', true);
        $data = array();

        $csvContent[] = array();
        $csvContent[] = array('Url');
        foreach ($url_list as $url) {
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
                CourseManager::unsubscribe_user($_GET['user_id'], $_GET['course_code']);
                $message = Display::return_message(get_lang('UserUnsubscribed'));
            } else {
                $message = Display::return_message(
                    get_lang('CannotUnsubscribeUserFromCourse'),
                    'error'
                );
            }
            break;
        case 'export':
            Export :: export_table_csv_utf8($csvContent, 'user_information_'.$user);
            exit;
            break;
    }
}

Display::display_header($tool_name);

echo '<div class="actions">
        <a href="'.api_get_path(WEB_CODE_PATH).'mySpace/myStudents.php?student='.intval($_GET['user_id']).'" title="'.get_lang('Reporting').'">'.Display::return_icon('statistics.png', get_lang('Reporting'), '', ICON_SIZE_MEDIUM).'
        </a>
        '.$login_as_icon.'
        '.$editUser.'
        '.$exportLink.'
    </div>';

echo Display::page_header($tool_name);

echo '<div class="row">';
echo '<div class="span2">';
echo '<a href="javascript: void(0);" onclick="'.$onclick.'" >'
    .'<img src="'.$fullurl.'" '.$resizing.' /></a><br />';
echo '</div>';

echo $message;

echo '<div class="span5">';
echo $userInformation;
echo '</div>';

echo '<div class="span5">';
echo $trackingInformation;
echo '</div>';
echo '</div>';

echo Display::page_subheader(get_lang('SessionList'));
echo $sessionInformation;
echo $courseInformation;
echo $urlInformation;

Display::display_footer();
