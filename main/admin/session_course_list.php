<?php
/* For licensing terms, see /license.txt */
/**
*	@package chamilo.admin
*/


// name of the language file that needs to be included
$language_file = 'admin';
$cidReset = true;

require_once '../inc/global.inc.php';

// setting the section (for the tabs)
$this_section=SECTION_PLATFORM_ADMIN;

api_protect_admin_script(true);

// Database Table Definitions
$tbl_course							= Database::get_main_table(TABLE_MAIN_COURSE);
$tbl_session						= Database::get_main_table(TABLE_MAIN_SESSION);
$tbl_session_rel_course				= Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
$tbl_session_rel_course_rel_user	= Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

$id_session = intval($_GET['id_session']);

if (empty($id_session)) {
    api_not_allowed();
}

$page   = intval($_GET['page']);
$action = $_REQUEST['action'];
$sort   = in_array($_GET['sort'],array('title','nbr_users'))?$_GET['sort']:'title';

$result = Database::query("SELECT name FROM $tbl_session WHERE id='$id_session'");

if (!list($session_name)=Database::fetch_row($result)) {
	header('Location: session_list.php');
	exit;
}

if ($action == 'delete') {
	$idChecked = $_REQUEST['idChecked'];
	if (is_array($idChecked) && count($idChecked)>0) {
		$my_temp = array();
		foreach ($idChecked as $id){
			$my_temp[]= Database::escape_string($id);// forcing the escape_string
		}
		$idChecked = $my_temp;        
		$idChecked="'".implode("','", $idChecked)."'";
		Database::query("DELETE FROM $tbl_session_rel_course WHERE id_session='$id_session' AND course_code IN($idChecked)");
		$nbr_affected_rows=Database::affected_rows();
		Database::query("DELETE FROM $tbl_session_rel_course_rel_user WHERE id_session='$id_session' AND course_code IN($idChecked)");
		Database::query("UPDATE $tbl_session SET nbr_courses=nbr_courses-$nbr_affected_rows WHERE id='$id_session'");
	}
	header('Location: '.api_get_self().'?id_session='.$id_session.'&sort='.$sort);
	exit();
}

$limit  = 20;
$from   = $page * $limit;

$sql = "SELECT code, title, nbr_users FROM $tbl_session_rel_course, $tbl_course WHERE course_code=code AND id_session='$id_session' ORDER BY $sort LIMIT $from,".($limit+1);
$result=Database::query($sql);
$Courses=Database::store_result($result);
$tool_name = api_htmlentities($session_name,ENT_QUOTES,$charset).' : '.get_lang('CourseListInSession');

$interbreadcrumb[]=array('url' => 'index.php',"name" => get_lang('PlatformAdmin'));
$interbreadcrumb[]=array('url' => "session_list.php","name" => get_lang('SessionList'));
$interbreadcrumb[]=array('url' => "resume_session.php?id_session=".Security::remove_XSS($_REQUEST['id_session']),"name" => get_lang('SessionOverview'));

Display::display_header($tool_name);
echo Display::page_header($tool_name);
?>
<form method="post" action="<?php echo api_get_self(); ?>?id_session=<?php echo $id_session; ?>&sort=<?php echo $sort; ?>" onsubmit="javascript:if(!confirm('<?php echo get_lang('ConfirmYourChoice'); ?>')) return false;">
<?php
$tableHeader = array();
$tableHeader[] = array(' ');
$tableHeader[] = array(get_lang('CourseTitle'));
$tableHeader[] = array(get_lang('NbUsers'));
$tableHeader[] = array(get_lang('Actions'));

$tableCourses = array();
foreach($Courses as $key=>$enreg) {
	$course = array();
	$course[] = '<input type="checkbox" name="idChecked[]" value="'.$enreg['code'].'">';
	$course[] = api_htmlentities($enreg['title'],ENT_QUOTES,$charset);
	$course[] = '<a href="session_course_user_list.php?id_session='.$id_session.'&course_code='.$enreg['code'].'">'.$enreg['nbr_users'].' '.get_lang('Users').'</a>';
	$course[] = '<a href="'.api_get_path(WEB_COURSE_PATH).$enreg['code'].'/?id_session='.$id_session.'">'.Display::return_icon('course_home.gif', get_lang('Course')).'</a>
                <a href="session_course_edit.php?id_session='.$id_session.'&page=session_course_list.php&course_code='.$enreg['code'].'">'.Display::return_icon('edit.gif', get_lang('Edit')).'</a>
				<a href="'.api_get_self().'?id_session='.$id_session.'&sort='.$sort.'&action=delete&idChecked[]='.$enreg['code'].'" onclick="javascript:if(!confirm(\''.addslashes(api_htmlentities(get_lang("ConfirmYourChoice"),ENT_QUOTES,$charset)).'\')) return false;">'.Display::return_icon('delete.gif', get_lang('Delete')).'</a>';
	$tableCourses[] = $course;
}
echo '<form method="post" action="'.api_get_self().'">';
Display :: display_sortable_table($tableHeader, $tableCourses, array (), array ());
echo '<select name="action">
	<option value="delete">'.get_lang('UnsubscribeCoursesFromSession').'</option>
	</select>
	<button class="save" type="submit">'.get_lang('Ok').'</button>
	</form>';
Display::display_footer();