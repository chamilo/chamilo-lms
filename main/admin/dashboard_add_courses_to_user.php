<?php
/* For licensing terms, see /license.txt */

/**
 * Interface for assigning courses to Human Resources Manager.
 */
// resetting the course id
$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

$xajax = new xajax();
$xajax->registerFunction('search_courses');

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;

// Access restrictions
api_protect_admin_script(true);

// setting breadcrumbs
$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('PlatformAdmin')];
$interbreadcrumb[] = ['url' => 'user_list.php', 'name' => get_lang('UserList')];

// Database Table Definitions
$tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);
$tbl_course_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);

// initializing variables
$user_id = (int) ($_GET['user']);
$user_info = api_get_user_info($user_id);
$user_anonymous = api_get_anonymous_id();
$current_user_id = api_get_user_id();

// setting the name of the tool
if (UserManager::is_admin($user_id)) {
    $tool_name = get_lang('AssignCoursesToPlatformAdministrator');
} elseif ($user_info['status'] == SESSIONADMIN) {
    $tool_name = get_lang('AssignCoursesToSessionsAdministrator');
} else {
    $tool_name = get_lang('AssignCoursesToHumanResourcesManager');
}

$add_type = 'multiple';
if (isset($_GET['add_type']) && $_GET['add_type'] != '') {
    $add_type = Security::remove_XSS($_REQUEST['add_type']);
}

if (!api_is_platform_admin()) {
    api_not_allowed(true);
}

function search_courses($needle, $type)
{
    global $tbl_course, $tbl_course_rel_access_url, $user_id;

    $xajax_response = new xajaxResponse();
    $return = '';
    if (!empty($needle) && !empty($type)) {
        // xajax send utf8 datas... datas in db can be non-utf8 datas
        $needle = Database::escape_string($needle);
        $assigned_courses_to_hrm = CourseManager::get_courses_followed_by_drh($user_id);
        $assigned_courses_code = array_keys($assigned_courses_to_hrm);
        foreach ($assigned_courses_code as &$value) {
            $value = "'".$value."'";
        }
        $without_assigned_courses = '';
        if (count($assigned_courses_code) > 0) {
            $without_assigned_courses = " AND c.code NOT IN(".implode(',', $assigned_courses_code).")";
        }

        if (api_is_multiple_url_enabled()) {
            $sql = "SELECT c.code, c.title
                    FROM $tbl_course c
                    LEFT JOIN $tbl_course_rel_access_url a
                    ON (a.c_id = c.id)
                    WHERE
                        c.code LIKE '$needle%' $without_assigned_courses AND
                        access_url_id = ".api_get_current_access_url_id();
        } else {
            $sql = "SELECT c.code, c.title
                    FROM $tbl_course c
                    WHERE
                        c.code LIKE '$needle%'
                        $without_assigned_courses ";
        }

        $rs = Database::query($sql);

        $return .= '<select id="origin" name="NoAssignedCoursesList[]" multiple="multiple" size="20" >';
        while ($course = Database::fetch_array($rs)) {
            $return .= '<option value="'.$course['code'].'" title="'.htmlspecialchars($course['title'], ENT_QUOTES).'">'.$course['title'].' ('.$course['code'].')</option>';
        }
        $return .= '</select>';
        $xajax_response->addAssign('ajax_list_courses_multiple', 'innerHTML', api_utf8_encode($return));
    }

    return $xajax_response;
}

$xajax->processRequests();
$htmlHeadXtra[] = $xajax->getJavascript('../inc/lib/xajax/');
$htmlHeadXtra[] = '<script>
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
    var newOptions = new Array();
    for (i = 0 ; i<options.length ; i++) {
        newOptions[i] = options[i];
    }
    newOptions = newOptions.sort(mysort);
    options.length = 0;
    for(i = 0 ; i < newOptions.length ; i++){
        options[i] = newOptions[i];
    }
}
function mysort(a, b) {
    if (a.text.toLowerCase() > b.text.toLowerCase()) {
        return 1;
    }
    if (a.text.toLowerCase() < b.text.toLowerCase()) {
        return -1;
    }
    return 0;
}

function valide() {
    var options = document.getElementById("destination").options;
    for (i = 0 ; i<options.length ; i++) {
        options[i].selected = true;
    }
    document.forms.formulaire.submit();
}
function remove_item(origin) {
    for(var i = 0 ; i<origin.options.length ; i++) {
        if(origin.options[i].selected) {
            origin.options[i]=null;
            i = i-1;
        }
    }
}
</script>';

$formSent = 0;
$errorMsg = $firstLetterCourse = '';
$UserList = [];

$msg = '';
if (isset($_POST['formSent']) && intval($_POST['formSent']) == 1) {
    $courses_list = isset($_POST['CoursesList']) ? $_POST['CoursesList'] : [];
    $affected_rows = CourseManager::subscribeCoursesToDrhManager($user_id, $courses_list);
    if ($affected_rows) {
        $msg = get_lang('AssignedCoursesHaveBeenUpdatedSuccessfully');
    }
}

// display header
Display::display_header($tool_name);

// actions
$actionsLeft = '<a href="dashboard_add_users_to_user.php?user='.$user_id.'">'.
    Display::return_icon('add-user.png', get_lang('AssignUsers'), null, ICON_SIZE_MEDIUM).'</a>';
$actionsLeft .= '<a href="dashboard_add_sessions_to_user.php?user='.$user_id.'">'.
    Display::return_icon('session-add.png', get_lang('AssignSessions'), null, ICON_SIZE_MEDIUM).'</a>';

echo $html = Display::toolbarAction('toolbar-dashboard', [$actionsLeft]);

echo Display::page_header(
    sprintf(get_lang('AssignCoursesToX'), api_get_person_name($user_info['firstname'], $user_info['lastname'])),
    null,
    'h3'
);

$assigned_courses_to_hrm = CourseManager::get_courses_followed_by_drh($user_id);
$assigned_courses_code = array_keys($assigned_courses_to_hrm);
foreach ($assigned_courses_code as &$value) {
    $value = "'".$value."'";
}

$without_assigned_courses = '';
if (count($assigned_courses_code) > 0) {
    $without_assigned_courses = " AND c.code NOT IN(".implode(',', $assigned_courses_code).")";
}

$needle = '%';
$firstLetter = null;
if (isset($_POST['firstLetterCourse'])) {
    $firstLetter = $_POST['firstLetterCourse'];
    $needle = Database::escape_string($firstLetter.'%');
}

if (api_is_multiple_url_enabled()) {
    $sql = " SELECT c.code, c.title
            FROM $tbl_course c
            LEFT JOIN $tbl_course_rel_access_url a
            ON (a.c_id = c.id)
            WHERE
                c.code LIKE '$needle' $without_assigned_courses AND
                access_url_id = ".api_get_current_access_url_id()."
            ORDER BY c.title";
} else {
    $sql = " SELECT c.code, c.title
            FROM $tbl_course c
            WHERE  c.code LIKE '$needle' $without_assigned_courses
            ORDER BY c.title";
}

$result = Database::query($sql);

?>
<form name="formulaire" method="post" action="<?php echo api_get_self(); ?>?user=<?php echo $user_id; ?>" style="margin:0px;">
<input type="hidden" name="formSent" value="1" />
<?php
if (!empty($msg)) {
    echo Display::return_message($msg, 'normal'); //main API
}
?>

<div class="row">
    <div class="col-md-4">
        <h5><?php echo get_lang('CoursesListInPlatform'); ?> :</h5>

        <div id="ajax_list_courses_multiple">
    <select id="origin" name="NoAssignedCoursesList[]" multiple="multiple" size="20" style="width:340px;">
    <?php while ($enreg = Database::fetch_array($result)) {
    ?>
            <option value="<?php echo $enreg['code']; ?>" <?php echo 'title="'.htmlspecialchars($enreg['title'], ENT_QUOTES).'"'; ?>><?php echo $enreg['title'].' ('.$enreg['code'].')'; ?></option>
    <?php
} ?>
    </select>
        </div>

    </div>
    <div class="col-md-4">
        <div class="code-course">
        <?php if ($add_type == 'multiple') {
        ?>
        <p><?php echo get_lang('FirstLetterCourse'); ?> :</p>
        <select name="firstLetterCourse" class="selectpicker form-control" onchange = "xajax_search_courses(this.value,'multiple')">
            <option value="%">--</option>
            <?php echo Display::get_alphabet_options($firstLetter); ?>
        </select>
        <?php
    } ?>
        </div>
        <div class="control-course">
            <div class="separate-action">
                <button class="btn btn-primary" type="button" onclick="moveItem(document.getElementById('origin'), document.getElementById('destination'))" onclick="moveItem(document.getElementById('origin'), document.getElementById('destination'))">
                    <em class="fa fa-arrow-right"></em>
                </button>
            </div>
            <div class="separate-action">
                <button class="btn btn-primary" type="button" onclick="moveItem(document.getElementById('destination'), document.getElementById('origin'))" onclick="moveItem(document.getElementById('destination'), document.getElementById('origin'))">
                    <em class="fa fa-arrow-left"></em>
                </button>
            </div>
            <div class="separate-action">
                <?php echo '<button class="btn btn-success" type="button" value="" onclick="valide()" >'.$tool_name.'</button>'; ?>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <h5><?php
        if (UserManager::is_admin($user_id)) {
            echo get_lang('AssignedCoursesListToPlatformAdministrator');
        } elseif ($user_info['status'] == SESSIONADMIN) {
            echo get_lang('AssignedCoursesListToSessionsAdministrator');
        } else {
            echo get_lang('AssignedCoursesListToHumanResourcesManager');
        }
            ?>: </h5>

        <select id='destination' name="CoursesList[]" multiple="multiple" size="20" style="width:320px;">
            <?php
            if (is_array($assigned_courses_to_hrm)) {
                foreach ($assigned_courses_to_hrm as $enreg) {
                    ?>
                <option value="<?php echo $enreg['code']; ?>" <?php echo 'title="'.htmlspecialchars($enreg['title'], ENT_QUOTES).'"'; ?>><?php echo $enreg['title'].' ('.$enreg['code'].')'; ?></option>
            <?php
                }
            }
            ?>
        </select>
    </div>
</div>

</form>
<?php
Display::display_footer();
