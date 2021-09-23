<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;

/**
 * This script gives information about a course.
 *
 * @author Bart Mollet
 */

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();

if (!isset($_GET['id'])) {
    api_not_allowed(true);
}

$course = api_get_course_entity($_GET['id']);
if (null === $course) {
    api_not_allowed(true);
}

$courseId = $course->getId();
$sessionId = isset($_GET['id_session']) ? (int) $_GET['id_session'] : 0;

/**
 * @param array $course
 * @param int   $session_id
 *
 * @return array
 */
function get_course_usage($course, $session_id = 0)
{
    $courseId = $course['real_id'];
    $tables = [
        [
            Database::get_course_table(TABLE_LP_MAIN),
            get_lang(ucfirst(TOOL_LEARNPATH)),
        ],
        [
            Database::get_course_table(TABLE_FORUM),
            get_lang('Forums'),
        ],
        [
            Database::get_course_table(TABLE_QUIZ_TEST),
            get_lang(ucfirst(TOOL_QUIZ)),
        ],
        [
            Database::get_course_table(TABLE_DOCUMENT),
            get_lang(ucfirst(TOOL_DOCUMENT)),
        ],
        [
            Database::get_course_table(TABLE_GROUP),
            get_lang(ucfirst(TOOL_GROUP)),
        ],
        [
            Database::get_course_table(TABLE_AGENDA),
            get_lang('Calendar'),
        ],
        [
            Database::get_course_table(TABLE_LINK),
            get_lang(ucfirst(TOOL_LINK)),
        ],
        [
            Database::get_course_table(TABLE_ANNOUNCEMENT),
            get_lang(ucfirst(TOOL_ANNOUNCEMENT)),
        ],
    ];

    $usage = [];
    $conditionSession = '';
    if ('' !== $session_id) {
        $session_id = (int) $session_id;
        $conditionSession = " AND session_id = '$session_id' ";
    }

    foreach ($tables as $tableInfo) {
        $table = $tableInfo[0];
        $title = $tableInfo[1];
        $sql = "SELECT COUNT(*) count FROM $table
                WHERE c_id = '$courseId' $conditionSession ";
        $rs = Database::query($sql);
        $row = Database::fetch_array($rs);

        $usage[] = [
            $title,
            $row['count'],
        ];
    }

    return $usage;
}

$courseUrl = api_get_course_url($courseId);
$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('Administration')];
$interbreadcrumb[] = ['url' => 'course_list.php', 'name' => get_lang('Courses')];

Display::display_header($course->getTitleAndCode());

echo Display::toolbarAction(
    'info',
    [
        Display::url(
            Display::return_icon('home.png', get_lang('Course home'), [], ICON_SIZE_MEDIUM),
            $courseUrl
        ),
    ]
);

$illustrationRepo = Container::getIllustrationRepository();
$illustrationUrl = $illustrationRepo->getIllustrationUrl($course, 'course_picture_medium');
echo '  <img class="img-thumbnail" src="'.$illustrationUrl.'" />';

/*echo Display::page_header(get_lang('Course usage'));
$table = new SortableTableFromArray(
    get_course_usage($courseInfo, $sessionId),
    0,
    20,
    'usage_table'
);
$table->set_additional_parameters(['code' => $courseInfo['code']]);
$table->set_other_tables(['user_table', 'class_table']);
$table->set_header(0, get_lang('tool'), true);
$table->set_header(1, get_lang('number of items'), true);
$table->display();*/

/*
 * Show all users subscribed in this course.
 */
echo Display::page_header(get_lang('Users'));

$table_course_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
$table_user = Database::get_main_table(TABLE_MAIN_USER);
$sql = "SELECT *, cu.status as course_status
        FROM $table_course_user cu, $table_user u";
if (api_is_multiple_url_enabled()) {
    $sql .= " INNER JOIN ".Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER)." url_rel_user
        ON
            u.id = url_rel_user.user_id AND
            url_rel_user.access_url_id = ".api_get_current_access_url_id();
}
$sql .= " WHERE
            cu.user_id = u.id AND
            cu.c_id = '".$courseId."' AND
            cu.relation_type <> ".COURSE_RELATION_TYPE_RRHH;
$res = Database::query($sql);
$is_western_name_order = api_is_western_name_order();
if (Database::num_rows($res) > 0) {
    $users = [];
    while ($obj = Database::fetch_object($res)) {
        $user = [];
        $user[] = $obj->official_code;
        if ($is_western_name_order) {
            $user[] = $obj->firstname;
            $user[] = $obj->lastname;
        } else {
            $user[] = $obj->lastname;
            $user[] = $obj->firstname;
        }
        $user[] = Display:: encrypted_mailto_link($obj->email, $obj->email);
        $user[] = 5 == $obj->course_status ? get_lang('Learner') : get_lang('Trainer');
        $user[] = '<a href="user_information.php?user_id='.$obj->user_id.'">'.
            Display::return_icon('info2.png', get_lang('user information')).'</a>';
        $users[] = $user;
    }
    $table = new SortableTableFromArray($users, 0, 20, 'user_table');
    $table->set_additional_parameters(['code' => $course->getCode()]);
    $table->set_other_tables(['usage_table', 'class_table']);
    $table->set_header(0, get_lang('Code'), true);
    if ($is_western_name_order) {
        $table->set_header(1, get_lang('First name'), true);
        $table->set_header(2, get_lang('Last name'), true);
    } else {
        $table->set_header(1, get_lang('Last name'), true);
        $table->set_header(2, get_lang('First name'), true);
    }
    $table->set_header(3, get_lang('e-mail'), true);
    $table->set_header(4, get_lang('Status'), true);
    $table->set_header(5, '', false);
    $table->display();
} else {
    echo get_lang('No users in course');
}

$sessionList = SessionManager::get_session_by_course($courseId);

$url = api_get_path(WEB_CODE_PATH);
if (!empty($sessionList)) {
    foreach ($sessionList as &$session) {
        $session[0] = Display::url($session[0], $url.'session/resume_session.php?id_session='.$session['id']);
        unset($session[1]);
    }
    echo Display::page_header(get_lang('Course sessions'));
    $table = new SortableTableFromArray($sessionList, 0, 20, 'user_table');
    $table->display();
}

Display::display_footer();
