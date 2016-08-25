<?php
/* For licensing terms, see /license.txt */
/**
 * This script gives information about a course
 * @author Bart Mollet
 * @package chamilo.admin
 */
$cidReset = true;
require_once '../inc/global.inc.php';
$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();

if (!isset($_GET['code'])) {
    api_not_allowed(true);
}

$courseInfo = api_get_course_info($_GET['code']);
if (empty($courseInfo)) {
    api_not_allowed(true);
}

$sessionId = isset($_GET['id_session']) ? (int) $_GET['id_session'] : 0;

function get_course_usage($course, $session_id = 0)
{
    $courseId = $course['real_id'];
    // Learnpaths
    $table   = Database :: get_course_table(TABLE_LP_MAIN);
    $usage[] = array(
        get_lang(ucfirst(TOOL_LEARNPATH)),
        CourseManager::count_rows_course_table($table, $session_id, $courseId)
    );
    // Forums
    $table   = Database :: get_course_table(TABLE_FORUM);
    $usage[] = array(get_lang('Forums'), CourseManager::count_rows_course_table($table, $session_id, $courseId));
    // Quizzes
    $table   = Database :: get_course_table(TABLE_QUIZ_TEST);
    $usage[] = array(
        get_lang(ucfirst(TOOL_QUIZ)),
        CourseManager::count_rows_course_table($table, $session_id, $courseId)
    );
    // Documents
    $table   = Database :: get_course_table(TABLE_DOCUMENT);
    $usage[] = array(
        get_lang(ucfirst(TOOL_DOCUMENT)),
        CourseManager::count_rows_course_table($table, $session_id, $courseId)
    );
    // Groups
    $table   = Database :: get_course_table(TABLE_GROUP);
    $usage[] = array(
        get_lang(ucfirst(TOOL_GROUP)),
        CourseManager::count_rows_course_table($table, $session_id, $courseId)
    );
    // Calendar
    $table   = Database :: get_course_table(TABLE_AGENDA);
    $usage[] = array(
        get_lang(ucfirst(TOOL_CALENDAR_EVENT)),
        CourseManager::count_rows_course_table($table, $session_id, $courseId)
    );
    // Link
    $table   = Database::get_course_table(TABLE_LINK);
    $usage[] = array(
        get_lang(ucfirst(TOOL_LINK)),
        CourseManager::count_rows_course_table($table, $session_id, $courseId)
    );
    // Announcements
    $table   = Database::get_course_table(TABLE_ANNOUNCEMENT);
    $usage[] = array(
        get_lang(ucfirst(TOOL_ANNOUNCEMENT)),
        CourseManager::count_rows_course_table($table, $session_id, $courseId)
    );
    return $usage;
}

$interbreadcrumb[] = array("url" => 'index.php', "name" => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array("url" => 'course_list.php', "name" => get_lang('Courses'));
$courseId = $courseInfo['real_id'];
$tool_name = $courseInfo['title'].' ('.$courseInfo['visual_code'].')';
Display::display_header($tool_name);
?>
    <div class="actions">
        <a href="<?php echo $courseInfo['course_public_url']; ?>">
            <?php Display::display_icon('home.png', get_lang('CourseHomepage'), array(), ICON_SIZE_MEDIUM); ?>
        </a>
    </div>
<?php

echo Display::page_header(get_lang('CourseUsage'));

$table = new SortableTableFromArray(get_course_usage($courseInfo, $sessionId), 0, 20, 'usage_table');
$table->set_additional_parameters(array('code' => $courseInfo['code']));
$table->set_other_tables(array('user_table', 'class_table'));
$table->set_header(0, get_lang('Tool'), true);
$table->set_header(1, get_lang('NumberOfItems'), true);
$table->display();

/**
 * Show all users subscribed in this course
 */
echo Display::page_header(get_lang('Users'));

$table_course_user = Database:: get_main_table(TABLE_MAIN_COURSE_USER);
$table_user = Database:: get_main_table(TABLE_MAIN_USER);
$sql = "SELECT *, cu.status as course_status
        FROM $table_course_user cu, $table_user u";
if (api_is_multiple_url_enabled()) {
    $sql .= " INNER JOIN ".Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER)." url_rel_user
        ON 
            u.user_id = url_rel_user.user_id AND 
            url_rel_user.access_url_id = ".api_get_current_access_url_id();
}
$sql .= " WHERE
            cu.user_id = u.user_id AND
            cu.c_id = '".$courseId."' AND
            cu.relation_type <> ".COURSE_RELATION_TYPE_RRHH;
$res = Database::query($sql);
$is_western_name_order = api_is_western_name_order();
if (Database::num_rows($res) > 0) {
    $users = array();
    while ($obj = Database::fetch_object($res)) {
        $user   = array();
        $user[] = $obj->official_code;
        if ($is_western_name_order) {
            $user[] = $obj->firstname;
            $user[] = $obj->lastname;
        } else {
            $user[] = $obj->lastname;
            $user[] = $obj->firstname;
        }
        $user[] = Display:: encrypted_mailto_link($obj->email, $obj->email);
        $user[] = $obj->course_status == 5 ? get_lang('Student') : get_lang('Teacher');
        $user[] = '<a href="user_information.php?user_id='.$obj->user_id.'">'.
            Display::return_icon('synthese_view.gif', get_lang('UserInfo')).'</a>';
        $users[] = $user;
    }
    $table = new SortableTableFromArray($users, 0, 20, 'user_table');
    $table->set_additional_parameters(array('code' => $courseInfo['code']));
    $table->set_other_tables(array('usage_table', 'class_table'));
    $table->set_header(0, get_lang('OfficialCode'), true);
    if ($is_western_name_order) {
        $table->set_header(1, get_lang('FirstName'), true);
        $table->set_header(2, get_lang('LastName'), true);
    } else {
        $table->set_header(1, get_lang('LastName'), true);
        $table->set_header(2, get_lang('FirstName'), true);
    }
    $table->set_header(3, get_lang('Email'), true);
    $table->set_header(4, get_lang('Status'), true);
    $table->set_header(5, '', false);
    $table->display();
} else {
    echo get_lang('NoUsersInCourse');
}

$sessionList = SessionManager::get_session_by_course($courseInfo['real_id']);

$url = api_get_path(WEB_CODE_PATH);
if (!empty($sessionList)) {
    foreach ($sessionList as &$session) {
        $session[0] = Display::url($session[0], $url.'session/resume_session.php?id_session='.$session['id']);
        unset($session[1]);
    }
    echo Display::page_header(get_lang('Sessions'));
    $table = new SortableTableFromArray($sessionList, 0, 20, 'user_table');
    $table->display();
}

Display::display_footer();
