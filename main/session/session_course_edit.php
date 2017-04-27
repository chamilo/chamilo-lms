<?php
/* For licensing terms, see /license.txt */

/**
 * Implements the edition of course-session settings
 * @package chamilo.admin
 */

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

$id_session = intval($_GET['id_session']);
SessionManager::protectSession($id_session);
$course_code = $_GET['course_code'];

$formSent = 0;
$errorMsg = '';

// Database Table Definitions
$tbl_user = Database::get_main_table(TABLE_MAIN_USER);
$tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);
$tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
$tbl_session_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
$tbl_session_rel_course_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

$course_info = api_get_course_info($_REQUEST['course_code']);
$courseId = $course_info['real_id'];
$tool_name = $course_info['name'];
$sql = "SELECT s.name, c.title
        FROM $tbl_session_course sc, $tbl_session s, $tbl_course c
        WHERE
            sc.session_id = s.id AND
            sc.c_id = c.id AND
            sc.session_id='$id_session' AND
            sc.c_id ='".$courseId."'";
$result = Database::query($sql);

if (!list($session_name,$course_title) = Database::fetch_row($result)) {
    header('Location: session_course_list.php?id_session='.$id_session);
    exit();
}

//$interbreadcrumb[]=array('url' => 'index.php',"name" => get_lang('PlatformAdmin'));
$interbreadcrumb[]=array('url' => "session_list.php","name" => get_lang("SessionList"));
$interbreadcrumb[]=array('url' => "resume_session.php?id_session=".$id_session,"name" => get_lang('SessionOverview'));
$interbreadcrumb[]=array('url' => "session_course_list.php?id_session=$id_session","name" =>api_htmlentities($session_name, ENT_QUOTES, $charset));

$arr_infos = array();
if (isset($_POST['formSent']) && $_POST['formSent']) {
    $formSent = 1;

    // get all tutor by course_code in the session
    $sql = "SELECT user_id
	        FROM $tbl_session_rel_course_rel_user
	        WHERE session_id = '$id_session' AND c_id = '".$courseId."' AND status = 2";
    $rs_coaches = Database::query($sql);

    $coaches_course_session = array();
    if (Database::num_rows($rs_coaches) > 0){
        while ($row_coaches = Database::fetch_row($rs_coaches)) {
            $coaches_course_session[] = $row_coaches[0];
        }
    }

    $id_coaches= $_POST['id_coach'];

    if (is_array($id_coaches) && count($id_coaches) > 0) {

        foreach ($id_coaches as $id_coach) {
            $id_coach = intval($id_coach);
            $rs1 = SessionManager::set_coach_to_course_session(
                $id_coach,
                $id_session,
                $courseId
            );
        }

        // set status to 0 other tutors from multiple list
        $array_intersect = array_diff($coaches_course_session,$id_coaches);

        foreach ($array_intersect as $no_coach_user_id) {
            $rs2 = SessionManager::set_coach_to_course_session(
                $no_coach_user_id,
                $id_session,
                $courseId,
                true
            );
        }

        header('Location: '.Security::remove_XSS($_GET['page']).'?id_session='.$id_session);
        exit();
    }
} else {
    $sql = "SELECT user_id
	        FROM $tbl_session_rel_course_rel_user
	        WHERE
                session_id = '$id_session' AND
                c_id = '".$courseId."' AND
                status = 2 ";
    $rs = Database::query($sql);

    if (Database::num_rows($rs) > 0) {
        while ($infos = Database::fetch_array($rs)) {
            $arr_infos[] = $infos['user_id'];
        }
    }
}

$order_clause = api_sort_by_first_name() ? ' ORDER BY firstname, lastname, username' : ' ORDER BY lastname, firstname, username';

if (api_is_multiple_url_enabled()) {
    $tbl_access_rel_user= Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
    $access_url_id = api_get_current_access_url_id();
    $sql = "SELECT u.user_id,lastname,firstname,username
            FROM $tbl_user u
            LEFT JOIN $tbl_access_rel_user  a
            ON(u.user_id= a.user_id)
            WHERE
                status='1' AND
                active = 1 AND
                access_url_id = $access_url_id ".
            $order_clause;
} else {
    $sql = "SELECT user_id,lastname,firstname,username
            FROM $tbl_user
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
$tool_name = get_lang('ModifySessionCourse');
api_display_tool_title($tool_name);

?>
<div class="session-course-edit">

<form method="post" action="<?php echo api_get_self(); ?>?id_session=<?php echo $id_session; ?>&course_code=<?php echo urlencode($course_code); ?>&page=<?php echo Security::remove_XSS($_GET['page']) ?>" style="margin:0px;">
<input type="hidden" name="formSent" value="1">

<div class="row">
    <div class="col-md-12">
        <div class="title"></div>
        <?php
            if(!empty($errorMsg)) {
                echo Display::return_message($errorMsg);
            }
        ?>
    </div>
</div>
<div class="row">
    <div class="col-md-2">
        <?php echo get_lang("CoachName") ?>
    </div>
    <div class="col-md-8">

        <select name="id_coach[]" class="form-control" multiple>
            <option value="0">----- <?php echo get_lang("Choose") ?> -----</option>
            <option value="0" <?php if(count($arr_infos) == 0) echo 'selected="selected"'; ?>>
                <?php echo get_lang('None') ?>
            </option>
            <?php
            foreach ($coaches as $enreg) {
                ?>
                <option value="<?php echo $enreg['user_id']; ?>" <?php if(((is_array($arr_infos) && in_array($enreg['user_id'], $arr_infos)))) echo 'selected="selected"'; ?>>
                    <?php echo api_get_person_name($enreg['firstname'], $enreg['lastname']).' ('.$enreg['username'].')'; ?>
                </option>
            <?php
            }
            unset($coaches);
            ?>
        </select>
        <div class="control">
        <button class="btn btn-success" type="submit" name="name" value="<?php echo get_lang('AssignCoach') ?>">
            <em class="fa fa-plus"></em>
            <?php echo get_lang('AssignCoach') ?>
        </button>
        </div>
    </div>
    <div class="col-md-2"></div>
</div>
</form>
</div>
<?php
Display::display_footer();
