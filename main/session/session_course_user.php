<?php
/* For licensing terms, see /license.txt */
/**
 *	@package chamilo.admin
 */

// resetting the course id
$cidReset = true;

require_once '../inc/global.inc.php';

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;

$id_session = intval($_GET['id_session']);
SessionManager::protectSession($id_session);

// setting breadcrumbs
$interbreadcrumb[] = array('url' => 'session_list.php','name' => get_lang('SessionList'));
$interbreadcrumb[] = array(
    'url' => 'resume_session.php?id_session='.$id_session,
    'name' => get_lang('SessionOverview'),
);

// Database Table Definitions
$tbl_session_rel_course_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
$tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
$tbl_session_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_USER);
$tbl_session_rel_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
$tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);

// setting the name of the tool
$tool_name = get_lang('EditSessionCoursesByUser');

$id_user = intval($_GET['id_user']);

if (empty($id_user) || empty($id_session)) {
    header('Location: resume_session.php?id_session='.$id_session);
}

if (!api_is_platform_admin()) {
    $sql = 'SELECT session_admin_id
            FROM '.Database :: get_main_table(TABLE_MAIN_SESSION).'
            WHERE id='.$id_session;
    $rs = Database::query($sql);
    if (Database::result($rs,0,0)!=$_user['user_id']) {
        api_not_allowed(true);
    }
}

$formSent = 0;
$CourseList = $SessionList = array();
$courses = $sessions = array();
$noPHP_SELF = true;

if (isset($_POST['formSent']) && $_POST['formSent']) {
    $formSent = $_POST['formSent'];
    $CourseList = isset($_POST['SessionCoursesList']) ? ($_POST['SessionCoursesList']) : null;

    if (!is_array($CourseList)) {
        $CourseList = array();
    }

    $sql = "SELECT DISTINCT course.id
			FROM $tbl_course course
			LEFT JOIN $tbl_session_rel_course session_rel_course
			ON course.id = session_rel_course.c_id
			INNER JOIN $tbl_session_rel_course_rel_user as srcru
			ON (srcru.session_id = session_rel_course.session_id)
			WHERE
			    user_id = $id_user AND
			    session_rel_course.session_id = $id_session";

    $rs = Database::query($sql);
    $existingCourses = Database::store_result($rs);

    if (empty($CourseList) && empty($existingCourses)) {
        Display::addFlash(Display::return_message(get_lang('NoCoursesForThisSession'), 'warning'));
        header('Location: session_course_user.php?id_session='.$id_session.'&id_user='.$id_user);
        exit;
    }

    if (count($CourseList) == count($existingCourses)) {
        Display::addFlash(Display::return_message(get_lang('MaybeYouWantToDeleteThisUserFromSession')));
        header('Location: session_course_user.php?id_session='.$id_session.'&id_user='.$id_user);
        exit;
    }

    foreach ($CourseList as $enreg_course) {
        $exists = false;
        foreach($existingCourses as $existingCourse) {
            if ($enreg_course == $existingCourse['id']) {
                $exists=true;
            }
        }

        if (!$exists) {
            $enreg_course = Database::escape_string($enreg_course);
            $sql = "DELETE FROM $tbl_session_rel_course_rel_user
                    WHERE user_id = '".$id_user."' AND c_id='".$enreg_course."' AND session_id=$id_session";
            $result = Database::query($sql);
            if (Database::affected_rows($result)) {
                //update session rel course table
                $sql = "UPDATE $tbl_session_rel_course
                        SET nbr_users= nbr_users - 1
                        WHERE session_id='$id_session' AND c_id = '$enreg_course'";
                Database::query($sql);
            }
        }
    }

    foreach ($existingCourses as $existingCourse) {
        if (!in_array($existingCourse['id'], $CourseList)){
            $existingCourse = Database::escape_string($existingCourse['id']);
            $sql = "INSERT IGNORE INTO $tbl_session_rel_course_rel_user (session_id,c_id,user_id)
                    VALUES ('$id_session','$existingCourse','$id_user')";
            $result = Database::query($sql);
            if (Database::affected_rows($result)) {
                //update session rel course table
                $sql = "UPDATE $tbl_session_rel_course
                        SET nbr_users= nbr_users + 1
                        WHERE session_id='$id_session' AND c_id = '$existingCourse'";
                Database::query($sql);
            }
        }
    }
    Display::addFlash(Display::return_message(get_lang('CoursesUpdated')));

    header('Location: session_course_user.php?id_session='.$id_session.'&id_user='.$id_user);
    exit;
}

Display::display_header($tool_name);

// the form header
$session_info = SessionManager::fetch($id_session);
$user_info = api_get_user_info($id_user);
echo '<legend>'.$tool_name.': '.$session_info['name'].' - '.$user_info['complete_name'].'</legend>';

$nosessionCourses = $sessionCourses = array();
// actual user
$sql = "SELECT course.id, code, title, visual_code, srcru.session_id
        FROM $tbl_course course
        INNER JOIN $tbl_session_rel_course_rel_user as srcru
        ON course.id = srcru.c_id
        WHERE srcru.user_id = $id_user AND session_id = $id_session";

//all
$sql_all="SELECT course.id, code, title, visual_code, src.session_id
        FROM $tbl_course course
        INNER JOIN $tbl_session_rel_course  as src
        ON course.id = src.c_id AND session_id = $id_session";
$result = Database::query($sql);
$Courses = Database::store_result($result);

$result = Database::query($sql_all);
$CoursesAll = Database::store_result($result);

$course_temp = array();
foreach ($Courses as $course) {
    $course_temp[] = $course['id'];
}

foreach ($CoursesAll as $course) {
    if (in_array($course['id'], $course_temp)) {
        $nosessionCourses[$course['id']] = $course ;
    } else {
        $sessionCourses[$course['id']] = $course ;
    }
}
unset($Courses);
?>

<form name="formulaire" method="post" action="<?php echo api_get_self(); ?>?id_user=<?php echo $id_user; ?>&id_session=<?php echo $id_session; ?>" style="margin:0px;">
    <input type="hidden" name="formSent" value="1" />
    <table border="0" cellpadding="5" cellspacing="0" width="100%" align="center">
        <tr>
            <td width="45%" align="center"><b><?php echo get_lang('CurrentCourses') ?> :</b></td>
            <td width="10%">&nbsp;</td>
            <td align="center" width="45%"><b><?php echo get_lang('CoursesToAvoid') ?> :</b></td>
        </tr>
        </td>
        <tr>
            <td width="45%" align="center">
                <div id="ajax_list_courses_multiple">
                    <select id="origin" name="NoSessionCoursesList[]" multiple="multiple" size="20" style="width:320px;"> <?php
                        foreach($nosessionCourses as $enreg) {
                            ?>
                            <option value="<?php echo $enreg['id']; ?>" <?php echo 'title="'.htmlspecialchars($enreg['title'].' ('.$enreg['visual_code'].')',ENT_QUOTES).'"'; if(in_array($enreg['code'],$CourseList)) echo 'selected="selected"'; ?>><?php echo $enreg['title'].' ('.$enreg['visual_code'].')'; ?></option>
                        <?php
                        }
                        ?> </select></div> <?php
                unset($nosessionCourses);
                ?>

                </select></td>
            <td width="10%" valign="middle" align="center">
                <button class="btn btn-default" type="button" onclick="moveItem(document.getElementById('origin'), document.getElementById('destination'))" onclick="moveItem(document.getElementById('origin'), document.getElementById('destination'))">
                    <em class="fa fa-arrow-right"></em>
                </button>
                <br /><br />
                <button class="btn btn-default" type="button" onclick="moveItem(document.getElementById('destination'), document.getElementById('origin'))" onclick="moveItem(document.getElementById('destination'), document.getElementById('origin'))">
                    <em class="fa fa-arrow-left"></em>
                </button>
                <br /><br /><br /><br /><br /><br />
                <?php
                echo '<button class="btn btn-primary" type="button" value="" onclick="valide()" >'.
                    get_lang('EditSessionCourses').'</button>';
                ?>
            </td>
            <td width="45%" align="center">
                <select id='destination' name="SessionCoursesList[]" multiple="multiple" size="20" style="width:320px;">
            <?php
            foreach($sessionCourses as $enreg) {
                ?>
                <option value="<?php echo $enreg['id']; ?>" title="<?php echo htmlspecialchars($enreg['title'].' ('.$enreg['visual_code'].')',ENT_QUOTES); ?>"><?php echo $enreg['title'].' ('.$enreg['visual_code'].')'; ?></option>
            <?php
            }
            unset($sessionCourses);
            ?>
            </select></td>
        </tr>
    </table>
</form>
<script>
    function moveItem(origin , destination) {
        for(var i = 0 ; i<origin.options.length ; i++) {
            if(origin.options[i].selected) {
                destination.options[destination.length] = new Option(origin.options[i].text,origin.options[i].value);
                origin.options[i]=null;
                i = i-1;
            }
        }
        destination.selectedIndex = -1;
        sortOptions(destination.options);
    }

    function sortOptions(options) {

        newOptions = new Array();

        for (i = 0 ; i<options.length ; i++) {
            newOptions[i] = options[i];
        }

        newOptions = newOptions.sort(mysort);
        options.length = 0;

        for(i = 0 ; i < newOptions.length ; i++){
            options[i] = newOptions[i];
        }

    }

    function mysort(a, b){
        if(a.text.toLowerCase() > b.text.toLowerCase()){
            return 1;
        }
        if(a.text.toLowerCase() < b.text.toLowerCase()){
            return -1;
        }
        return 0;
    }

    function valide(){
        var options = document.getElementById('destination').options;
        for (i = 0 ; i<options.length ; i++)
            options[i].selected = true;

        document.forms.formulaire.submit();
    }
</script>
<?php
Display::display_footer();
