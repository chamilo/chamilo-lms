<?php

/* For licensing terms, see /license.txt */

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;

$id_session = isset($_GET['id_session']) ? (int) $_GET['id_session'] : 0;
SessionManager::protectSession($id_session);

// Database Table Definitions
$tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);
$tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
$tbl_session_rel_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
$tbl_session_rel_course_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

if (empty($id_session)) {
    api_not_allowed();
}

$page = isset($_GET['page']) ? intval($_GET['page']) : 0;
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
$sort = isset($_GET['sort']) && in_array($_GET['sort'], ['title', 'nbr_users']) ? $_GET['sort'] : 'title';

$result = Database::query("SELECT name FROM $tbl_session WHERE id='$id_session'");

if (!list($session_name) = Database::fetch_row($result)) {
    header('Location: session_list.php');
    exit;
}

if ($action === 'delete') {
    $idChecked = $_REQUEST['idChecked'];
    if (is_array($idChecked) && count($idChecked) > 0) {
        $my_temp = [];
        foreach ($idChecked as $id) {
            $my_temp[] = Database::escape_string($id); // forcing the escape_string
        }
        $idChecked = $my_temp;
        $idChecked = "'".implode("','", $idChecked)."'";
        $result = Database::query("DELETE FROM $tbl_session_rel_course WHERE session_id='$id_session' AND c_id IN($idChecked)");
        $nbr_affected_rows = Database::affected_rows($result);
        Database::query("DELETE FROM $tbl_session_rel_course_rel_user WHERE session_id='$id_session' AND c_id IN($idChecked)");
        Database::query("UPDATE $tbl_session SET nbr_courses=nbr_courses-$nbr_affected_rows WHERE id='$id_session'");
    }
    header('Location: '.api_get_self().'?id_session='.$id_session.'&sort='.$sort);
    exit();
}

$limit = 20;
$from = $page * $limit;

$sql = "SELECT c.id, c.code, c.title, nbr_users
		FROM $tbl_session_rel_course, $tbl_course c
		WHERE c_id = c.id AND session_id='$id_session'
		ORDER BY `$sort`
		LIMIT $from,".($limit + 1);
$result = Database::query($sql);
$Courses = Database::store_result($result);
$tool_name = api_htmlentities($session_name, ENT_QUOTES, $charset).' : '.get_lang('CourseListInSession');

$interbreadcrumb[] = ['url' => "session_list.php", "name" => get_lang('SessionList')];
$interbreadcrumb[] = ['url' => "resume_session.php?id_session=".Security::remove_XSS($_REQUEST['id_session']), "name" => get_lang('SessionOverview')];

Display::display_header($tool_name);
echo Display::page_header($tool_name);
?>
<form method="post" action="<?php echo api_get_self(); ?>?id_session=<?php echo $id_session; ?>&sort=<?php echo $sort; ?>" onsubmit="javascript:if(!confirm('<?php echo get_lang('ConfirmYourChoice'); ?>')) return false;">
<?php
$tableHeader = [];
$tableHeader[] = [' '];
$tableHeader[] = [get_lang('CourseTitle')];
$tableHeader[] = [get_lang('NbUsers')];
$tableHeader[] = [get_lang('Actions')];

$tableCourses = [];

foreach ($Courses as $key => $enreg) {
    $course = [];
    $course[] = '<input type="checkbox" name="idChecked[]" value="'.$enreg['id'].'">';
    $course[] = api_htmlentities($enreg['title'], ENT_QUOTES, $charset);
    $course[] = '<a href="session_course_user_list.php?id_session='.$id_session.'&course_code='.$enreg['code'].'">'.$enreg['nbr_users'].' '.get_lang('Users').'</a>';
    $course[] = '<a href="'.api_get_path(WEB_COURSE_PATH).$enreg['code'].'/?id_session='.$id_session.'">'.
        Display::return_icon('course_home.gif', get_lang('Course')).'</a>
			<a href="session_course_edit.php?id_session='.$id_session.'&page=session_course_list.php&course_code='.$enreg['code'].'">'.
        Display::return_icon('edit.png', get_lang('Edit')).'</a>
			<a href="'.api_get_self().'?id_session='.$id_session.'&sort='.$sort.'&action=delete&idChecked[]='.$enreg['id'].'" onclick="javascript:if(!confirm(\''.addslashes(api_htmlentities(get_lang("ConfirmYourChoice"), ENT_QUOTES, $charset)).'\')) return false;">'.
        Display::return_icon('delete.png', get_lang('Delete')).'</a>';
    $tableCourses[] = $course;
}
echo '<form method="post" action="'.api_get_self().'">';
Display::display_sortable_table($tableHeader, $tableCourses, [], []);
echo '<select name="action">
	<option value="delete">'.get_lang('UnsubscribeCoursesFromSession').'</option>
	</select>
	<button class="save" type="submit">'.get_lang('Ok').'</button>
	</form>';
Display::display_footer();
