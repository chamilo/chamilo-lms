<?php

/* For licensing terms, see /license.txt */

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

$id_session = isset($_GET['id_session']) ? (int) $_GET['id_session'] : 0;

SessionManager::protectSession($id_session);

if (empty($id_session)) {
    api_not_allowed();
}

$action = $_REQUEST['action'] ?? null;
$idChecked = isset($_REQUEST['idChecked']) && is_array($_REQUEST['idChecked']) ? $_REQUEST['idChecked'] : [];

$course_code = Database::escape_string(trim($_GET['course_code']));
$courseInfo = api_get_course_info($course_code);
$courseId = $courseInfo['real_id'];
$apiIsWesternNameOrder = api_is_western_name_order();

$check = Security::check_token('get');

if ($check) {
    switch ($action) {
        case 'delete':
            foreach ($idChecked as $userId) {
                SessionManager::unSubscribeUserFromCourseSession((int) $userId, $courseId, $id_session);
            }
            header(
                'Location: '.api_get_self().'?'
                .http_build_query(['id_session' => $id_session, 'course_code' => $course_code])
            );
            exit;
        case 'add':
            SessionManager::subscribe_users_to_session_course($idChecked, $id_session, $course_code);
            header(
                'Location: '.api_get_self().'?'
                .http_build_query(['id_session' => $id_session, 'course_code' => $course_code])
            );
            exit;
    }
    Security::clear_token();
}

$tblSessionRelUser = Database::get_main_table(TABLE_MAIN_SESSION_USER);
$tblUser = Database::get_main_table(TABLE_MAIN_USER);
$tblCourse = Database::get_main_table(TABLE_MAIN_COURSE);
$tblSession = Database::get_main_table(TABLE_MAIN_SESSION);
$urlTable = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
$tblSessionRelCourseRelUser = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
$tblSessionRelCourse = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);

$sql = "SELECT s.name, c.title
        FROM $tblSessionRelCourse src
		INNER JOIN $tblSession s ON s.id = src.session_id
		INNER JOIN $tblCourse c ON c.id = src.c_id
		WHERE src.session_id='$id_session' AND src.c_id='$courseId' ";

$result = Database::query($sql);
if (!list($session_name, $course_title) = Database::fetch_row($result)) {
    header('Location: session_course_list.php?id_session='.$id_session);
    exit();
}

function get_number_of_users(): int
{
    $tblSessionRelUser = $GLOBALS['tblSessionRelUser'];
    $tblUser = $GLOBALS['tblUser'];
    $urlTable = $GLOBALS['urlTable'];
    $tblSessionRelCourseRelUser = $GLOBALS['tblSessionRelCourseRelUser'];

    $sessionId = (int) $GLOBALS['id_session'];
    $courseId = (int) $GLOBALS['courseId'];
    $urlId = api_get_current_access_url_id();

    $sql = "SELECT COUNT(DISTINCT u.user_id) AS nbr
        FROM $tblSessionRelUser s
        INNER JOIN $tblUser u ON (u.id = s.user_id)
        INNER JOIN $urlTable url ON (url.user_id = u.id)
        LEFT JOIN $tblSessionRelCourseRelUser scru
            ON (s.session_id = scru.session_id AND s.user_id = scru.user_id AND scru.c_id = $courseId)
        WHERE
            s.session_id = $sessionId AND
            url.access_url_id = $urlId";

    $row = Database::fetch_assoc(Database::query($sql));

    return (int) $row['nbr'];
}

function get_user_list(int $from, int $limit, int $column, string $direction): array
{
    $tblSessionRelUser = $GLOBALS['tblSessionRelUser'];
    $tblUser = $GLOBALS['tblUser'];
    $urlTable = $GLOBALS['urlTable'];
    $tblSessionRelCourseRelUser = $GLOBALS['tblSessionRelCourseRelUser'];
    $apiIsWesternNameOrder = $GLOBALS['apiIsWesternNameOrder'];

    $sessionId = (int) $GLOBALS['id_session'];
    $courseId = (int) $GLOBALS['courseId'];
    $urlId = api_get_current_access_url_id();

    $orderBy = "is_subscribed $direction, u.lastname";

    if ($column == 1) {
        $orderBy = $apiIsWesternNameOrder ? "u.firstname $direction, u.lastname" : "u.lastname $direction, u.firstname";
    } elseif ($column == 2) {
        $orderBy = $apiIsWesternNameOrder ? "u.lastname $direction, u.firstname" : "u.firstname $direction, u.lastname";
    } elseif (3 == $column) {
        $orderBy = "u.username $direction";
    }

    $sql = "SELECT DISTINCT u.user_id,"
        .($apiIsWesternNameOrder ? 'u.firstname, u.lastname' : 'u.lastname, u.firstname')
        .", u.username, scru.user_id as is_subscribed
        FROM $tblSessionRelUser s
        INNER JOIN $tblUser u
            ON (u.id = s.user_id)
        INNER JOIN $urlTable url
            ON (url.user_id = u.id)
        LEFT JOIN $tblSessionRelCourseRelUser scru
            ON (s.session_id = scru.session_id AND s.user_id = scru.user_id AND scru.c_id = $courseId)
        WHERE
            s.session_id = $sessionId AND
            url.access_url_id = $urlId
        ORDER BY $orderBy
        LIMIT $from, $limit";

    $result = Database::query($sql);

    return Database::store_result($result);
}

function actions_filter(?int $sessionCourseSubscriptionId, string $urlParams, array $row): string
{
    $params = [
        'idChecked[]' => $row['user_id'],
        'action' => 'add',
    ];

    $icon = Display::return_icon('add.png', get_lang('Add'));

    if ($sessionCourseSubscriptionId) {
        $params['action'] = 'delete';

        $icon = Display::return_icon('delete.png', get_lang('Delete'));
    }

    return Display::url(
        $icon,
        api_get_self().'?'.http_build_query($params)."&$urlParams",
        [
            'onclick' => 'javascript:if(!confirm(\''.get_lang('ConfirmYourChoice').'\')) return false;',
        ]
    );
}

$table = new SortableTable(
    'users',
    'get_number_of_users',
    'get_user_list'
);
$table->set_additional_parameters(
    [
        'sec_token' => Security::get_token(),
        'id_session' => $id_session,
        'course_code' => $course_code,
    ]
);
$table->set_header(0, '&nbsp;', false);

if ($apiIsWesternNameOrder) {
    $table->set_header(1, get_lang('FirstName'));
    $table->set_header(2, get_lang('LastName'));
} else {
    $table->set_header(1, get_lang('LastName'));
    $table->set_header(2, get_lang('FirstName'));
}

$table->set_header(3, get_lang('LoginName'));
$table->set_header(4, get_lang('Action'));
$table->set_column_filter(4, 'actions_filter');
$table->set_form_actions(
    [
        'delete' => get_lang('UnsubscribeSelectedUsersFromSession'),
        'add' => get_lang('AddUsers'),
    ],
    'idChecked'
);

$tool_name = get_lang('Session').': '.$session_name.' - '.get_lang('Course').': '.$course_title;

$interbreadcrumb[] = ['url' => 'session_list.php', 'name' => get_lang('SessionList')];
$interbreadcrumb[] = [
    'url' => "resume_session.php?id_session=".$id_session,
    'name' => get_lang('SessionOverview'),
];

Display::display_header($tool_name);
echo Display::page_header($tool_name);

$table->display();

Display::display_footer();
