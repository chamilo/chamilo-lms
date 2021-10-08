<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Session;

/**
 * Implements the edition of course-session settings.
 */

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

$sessionId = isset($_GET['id_session']) ? (int) $_GET['id_session'] : 0;
$session = api_get_session_entity($sessionId);
SessionManager::protectSession($session);
$courseCode = $_GET['course_code'];
$course_info = api_get_course_info($_REQUEST['course_code']);

if (empty($course_info)) {
    api_not_allowed(true);
}

// Database Table Definitions
$tblUser = Database::get_main_table(TABLE_MAIN_USER);
$tblCourse = Database::get_main_table(TABLE_MAIN_COURSE);
$tblSession = Database::get_main_table(TABLE_MAIN_SESSION);
$tblSessionRelCourse = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
$tblSessionRelCourseRelUser = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

$courseId = $course_info['real_id'];
$tool_name = $course_info['name'];
$sql = "SELECT s.name, c.title
        FROM $tblSessionRelCourse sc, $tblSession s, $tblCourse c
        WHERE
            sc.session_id = s.id AND
            sc.c_id = c.id AND
            sc.session_id='$sessionId' AND
            sc.c_id ='".$courseId."'";
$result = Database::query($sql);

if (!list($session_name, $course_title) = Database::fetch_row($result)) {
    header('Location: session_course_list.php?id_session='.$sessionId);
    exit();
}

$interbreadcrumb[] = ['url' => "session_list.php", "name" => get_lang("Session list")];
$interbreadcrumb[] = [
    'url' => "resume_session.php?id_session=".$sessionId,
    "name" => get_lang('Session overview'),
];
$interbreadcrumb[] = [
    'url' => "session_course_list.php?id_session=$sessionId",
    "name" => api_htmlentities($session_name),
];

$arr_infos = [];
if (isset($_POST['formSent']) && $_POST['formSent']) {
    // get all tutor by course_code in the session
    $sql = "SELECT user_id
	        FROM $tblSessionRelCourseRelUser
	        WHERE session_id = '$sessionId' AND c_id = '".$courseId."' AND status = ".Session::COURSE_COACH;
    $rs_coaches = Database::query($sql);

    $coaches_course_session = [];
    if (Database::num_rows($rs_coaches) > 0) {
        while ($row_coaches = Database::fetch_row($rs_coaches)) {
            $coaches_course_session[] = $row_coaches[0];
        }
    }

    $id_coaches = isset($_POST['id_coach']) ? $_POST['id_coach'] : [0];
    if (is_array($id_coaches) && count($id_coaches) > 0) {
        foreach ($id_coaches as $id_coach) {
            $id_coach = (int) $id_coach;
            $rs1 = SessionManager::set_coach_to_course_session(
                $id_coach,
                $sessionId,
                $courseId
            );
        }

        // set status to 0 other tutors from multiple list
        $array_intersect = array_diff($coaches_course_session, $id_coaches);

        foreach ($array_intersect as $no_coach_user_id) {
            SessionManager::set_coach_to_course_session(
                $no_coach_user_id,
                $sessionId,
                $courseId,
                true
            );
        }
        Display::addFlash(Display::return_message(get_lang('Update successful')));
        header('Location: '.Security::remove_XSS($_GET['page']).'?id_session='.$sessionId);
        exit();
    }
} else {
    $sql = "SELECT user_id
	        FROM $tblSessionRelCourseRelUser
	        WHERE
                session_id = '$sessionId' AND
                c_id = '".$courseId."' AND
                status = ".Session::COURSE_COACH;
    $rs = Database::query($sql);

    if (Database::num_rows($rs) > 0) {
        while ($infos = Database::fetch_array($rs)) {
            $arr_infos[] = $infos['user_id'];
        }
    }
}

$order_clause = api_sort_by_first_name() ? ' ORDER BY firstname, lastname, username' : ' ORDER BY lastname, firstname, username';

if (api_is_multiple_url_enabled()) {
    $tbl_access_rel_user = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
    $access_url_id = api_get_current_access_url_id();
    $sql = "SELECT u.id as user_id,lastname,firstname,username
            FROM $tblUser u
            LEFT JOIN $tbl_access_rel_user  a
            ON(u.id= a.user_id)
            WHERE
                status='1' AND
                active = 1 AND
                access_url_id = $access_url_id ".
            $order_clause;
} else {
    $sql = "SELECT id as user_id,lastname,firstname,username
            FROM $tblUser
            WHERE
                status = '1' AND
                active = 1 ".
            $order_clause;
}

$result = Database::query($sql);
$coaches = Database::store_result($result);

if (!api_is_platform_admin() && api_is_teacher()) {
    $userInfo = api_get_user_info();
    $coaches = [$userInfo];
}

Display::display_header($tool_name);
$tool_name = get_lang('Edit session course');
Display::page_subheader2($tool_name);

$form = new FormValidator(
    'form',
    'post',
    api_get_self().'?id_session='.$sessionId.'&course_code='.$courseCode.'&page='.Security::remove_XSS($_GET['page'])
);

$options = [];
$selected = [];
foreach ($coaches as $enreg) {
    $options[$enreg['user_id']] = api_get_person_name($enreg['firstname'], $enreg['lastname']).' ('.$enreg['username'].')';
    if (in_array($enreg['user_id'], $arr_infos)) {
        $selected[] = $enreg['user_id'];
    }
}

$form->addSelect('id_coach', get_lang('Coach name'), $options, ['multiple' => 'multiple']);
$form->addHidden('formSent', 1);
$form->addButtonSave(get_lang('Assign coach'));
$form->setDefaults(['id_coach' => $selected]);
$form->display();

Display::display_footer();
