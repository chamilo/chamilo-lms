<?php
/* For licensing terms, see /license.txt */

/**
 * Interface for assigning courses to Human Resources Manager.
 */

use Chamilo\CoreBundle\Enums\ObjectIcon;

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
$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('Administration')];
$interbreadcrumb[] = ['url' => '/admin/user-list', 'name' => get_lang('User list')];

// Database Table Definitions
$tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);
$tbl_course_rel_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
$tbl_course_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);

// initializing variables
$user_id = (int) ($_GET['user']);

$user_anonymous = api_get_anonymous_id();
$current_user_id = api_get_user_id();
$user = api_get_user_entity($user_id);
$isSessionAdmin = api_is_session_admin($user);

// setting the name of the tool
if (UserManager::is_admin($user_id)) {
    $tool_name = get_lang('Assign courses to platform\'s administrator');
} elseif ($isSessionAdmin) {
    $tool_name = get_lang('Assign courses to session\'s administrator');
} else {
    $tool_name = get_lang('Assign courses to HR manager');
}

$add_type = 'multiple';
if (isset($_GET['add_type']) && '' != $_GET['add_type']) {
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
            $without_assigned_courses = ' AND c.code NOT IN('.implode(',', $assigned_courses_code).')';
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

        $return .= '<select id="origin" ondblclick="moveItem(document.getElementById(&quot;origin&quot;), document.getElementById(&quot;destination&quot;))" class="form-control h-96 w-full min-w-0 rounded-xl border-gray-25 text-body-2 text-gray-90" name="NoAssignedCoursesList[]" multiple="multiple" size="15">';
        while ($course = Database :: fetch_array($rs)) {
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
var assignmentFormChanged = false;

function setAssignmentFormChanged() {
    assignmentFormChanged = true;
    var field = document.getElementById("assignmentChanged");
    if (field) {
        field.value = "1";
    }

    var saveButton = document.getElementById("assign_user");
    if (saveButton) {
        saveButton.disabled = false;
    }
}

function moveItem(origin , destination) {
    var moved = false;

    for(var i = 0 ; i<origin.options.length ; i++) {
        if(origin.options[i].selected) {
            destination.options[destination.length] = new Option(origin.options[i].text,origin.options[i].value);
            origin.options[i]=null;
            i = i-1;
            moved = true;
        }
    }

    if (moved) {
        destination.selectedIndex = -1;
        sortOptions(destination.options);
        setAssignmentFormChanged();
    }

    return moved;
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

function syncAssignedItems() {
    var destination = document.getElementById("destination");
    var assignedItems = document.getElementById("assignedItems");

    if (!destination || !assignedItems) {
        return;
    }

    var values = [];
    for (var i = 0; i < destination.options.length; i++) {
        values.push(destination.options[i].value);
    }

    assignedItems.value = values.join(",");
}

function valide() {
    var changedField = document.getElementById("assignmentChanged");

    if (!assignmentFormChanged && (!changedField || changedField.value !== "1")) {
        return false;
    }

    var options = document.getElementById("destination").options;
    for (i = 0 ; i<options.length ; i++) {
        options[i].selected = true;
    }

    syncAssignedItems();
    document.forms.formulaire.submit();
}
function remove_item(origin) {
    var removed = false;

    for(var i = 0 ; i<origin.options.length ; i++) {
        if(origin.options[i].selected) {
            origin.options[i]=null;
            i = i-1;
            removed = true;
        }
    }

    if (removed) {
        setAssignmentFormChanged();
    }

    return removed;
}
</script>';

$formSent = 0;
$errorMsg = $firstLetterCourse = '';
$UserList = [];

$msg = '';
if (isset($_POST['formSent']) && 1 == (int) ($_POST['formSent']) && isset($_POST['assignmentChanged']) && '1' === (string) $_POST['assignmentChanged'] && Security::check_token('post')) {
    if (isset($_POST['assignedItems']) && '' !== (string) $_POST['assignedItems']) {
        $courses_list = array_filter(explode(',', (string) Security::remove_XSS($_POST['assignedItems'])));
    } else {
        $courses_list = isset($_POST['CoursesList']) ? Security::remove_XSS($_POST['CoursesList']) : [];
    }

    $affected_rows = CourseManager::subscribeCoursesToDrhManager($user_id, $courses_list);
    if ($affected_rows || empty($courses_list)) {
        $msg = get_lang('The assigned courses have been updated');
    }
}

// display header
Display::display_header($tool_name);

// actions
$actionsLeft = '<a href="dashboard_add_users_to_user.php?user='.$user_id.'">'.
    Display::getMdiIcon(ObjectIcon::USER, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Assign users')).'</a>';
$actionsLeft .= '<a href="dashboard_add_sessions_to_user.php?user='.$user_id.'">'.
    Display::getMdiIcon(ObjectIcon::SESSION, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Assign sessions')).'</a>';

echo $html = Display::toolbarAction('toolbar-dashboard', [$actionsLeft]);

echo Display::page_header(
    sprintf(get_lang('Assign courses to %s'), UserManager::formatUserFullName($user)),
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
    $without_assigned_courses = ' AND c.code NOT IN('.implode(',', $assigned_courses_code).')';
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
                access_url_id = ".api_get_current_access_url_id().'
            ORDER BY c.title';
} else {
    $sql = " SELECT c.code, c.title
            FROM $tbl_course c
            WHERE  c.code LIKE '$needle' $without_assigned_courses
            ORDER BY c.title";
}

$result = Database::query($sql);

?>
<form name="formulaire" method="post" action="<?php echo api_get_self(); ?>?user=<?php echo $user_id; ?>" class="form-horizontal w-full w-100">
<input type="hidden" name="formSent" value="1" />
<input type="hidden" id="assignmentChanged" name="assignmentChanged" value="0" />
<input type="hidden" id="assignedItems" name="assignedItems" value="" />
<?php echo Security::get_HTML_token(); ?>
<?php
if (!empty($msg)) {
    echo Display::return_message($msg, 'normal');
}
?>

<div class="row g-4 align-items-stretch w-full w-100">
    <div class="col-12 col-md-5 col-xl-5">
        <section class="min-w-0 h-full rounded-2xl border border-gray-20 bg-white p-4 shadow-sm">
            <label for="origin" class="mb-3 block text-body-2 font-semibold text-gray-90">
                <?php echo get_lang('Platform courses list'); ?>
            </label>

            <div id="ajax_list_courses_multiple">
                <select id="origin" ondblclick="moveItem(document.getElementById(&quot;origin&quot;), document.getElementById(&quot;destination&quot;))" class="form-control h-96 w-full min-w-0 rounded-xl border-gray-25 text-body-2 text-gray-90" name="NoAssignedCoursesList[]" multiple="multiple" size="15">
                <?php while ($enreg = Database::fetch_array($result)) { ?>
                    <option value="<?php echo $enreg['code']; ?>" <?php echo 'title="'.htmlspecialchars($enreg['title'], ENT_QUOTES).'"'; ?>>
                        <?php echo $enreg['title'].' ('.$enreg['code'].')'; ?>
                    </option>
                <?php } ?>
                </select>
            </div>
        </section>
    </div>

    <div class="col-12 col-md-2 col-xl-1">
        <section class="min-w-0 h-full rounded-2xl border border-gray-20 bg-support-2 p-4 shadow-sm">
            <div class="h-full min-h-96 flex flex-col justify-center gap-3">
                <?php if ('multiple' == $add_type) { ?>
                    <div class="mb-3">
                        <label for="firstLetterCourse" class="mb-2 block text-body-2 font-semibold text-gray-90">
                            <?php echo get_lang('First letter (code)'); ?>
                        </label>
                        <select id="firstLetterCourse" name="firstLetterCourse" class="selectpicker form-control w-full rounded-xl border-gray-25 text-body-2 text-gray-90" onchange="xajax_search_courses(this.value,'multiple')">
                            <option value="%">--</option>
                            <?php echo Display::get_alphabet_options($firstLetter); ?>
                        </select>
                    </div>
                <?php } ?>

                <div class="flex flex-col items-center justify-center gap-3">
                    <button class="inline-flex h-12 w-12 min-w-12 min-h-12 items-center justify-center rounded-xl p-0 text-center border-0 bg-secondary text-secondary-button-text hover:bg-secondary-hover focus:outline-none focus:ring-2 focus:ring-secondary" type="button" onclick="moveItem(document.getElementById('origin'), document.getElementById('destination'))" title="<?php echo htmlspecialchars(get_lang('Add'), ENT_QUOTES); ?>" aria-label="<?php echo htmlspecialchars(get_lang('Add'), ENT_QUOTES); ?>" data-bs-toggle="tooltip" data-bs-placement="right">
                        <span class="mdi mdi-arrow-right-bold text-white text-lg" aria-hidden="true"></span>
                        <span class="sr-only"><?php echo get_lang('Add'); ?></span>
                    </button>

                    <button class="inline-flex h-12 w-12 min-w-12 min-h-12 items-center justify-center rounded-xl p-0 text-center border-0 bg-secondary text-secondary-button-text hover:bg-secondary-hover focus:outline-none focus:ring-2 focus:ring-secondary" type="button" onclick="moveItem(document.getElementById('destination'), document.getElementById('origin'))" title="<?php echo htmlspecialchars(get_lang('Remove'), ENT_QUOTES); ?>" aria-label="<?php echo htmlspecialchars(get_lang('Remove'), ENT_QUOTES); ?>" data-bs-toggle="tooltip" data-bs-placement="right">
                        <span class="mdi mdi-arrow-left-bold text-white text-lg" aria-hidden="true"></span>
                        <span class="sr-only"><?php echo get_lang('Remove'); ?></span>
                    </button>

                    <button id="assign_user" class="inline-flex h-12 w-12 min-w-12 min-h-12 items-center justify-center rounded-xl p-0 text-center disabled:cursor-not-allowed disabled:opacity-50 border-0 bg-success text-success-button-text hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-success" type="button" onclick="valide()" disabled="disabled" title="<?php echo htmlspecialchars($tool_name, ENT_QUOTES); ?>" aria-label="<?php echo htmlspecialchars($tool_name, ENT_QUOTES); ?>" data-bs-toggle="tooltip" data-bs-placement="right">
                        <span class="mdi mdi-content-save-outline text-white text-lg" aria-hidden="true"></span>
                        <span class="sr-only"><?php echo $tool_name; ?></span>
                    </button>
                </div>
            </div>
        </section>
    </div>

    <div class="col-12 col-md-5 col-xl-6">
        <section class="min-w-0 h-full rounded-2xl border border-gray-20 bg-white p-4 shadow-sm">
            <label for="destination" class="mb-3 block text-body-2 font-semibold text-gray-90">
                <?php
                if (UserManager::is_admin($user_id)) {
                    echo get_lang('Assigned courses list to platform administrator');
                } elseif ($isSessionAdmin) {
                    echo get_lang('Assigned courses list to sessions administrator');
                } else {
                    echo get_lang('Courses assigned to the HR manager');
                }
                ?>
            </label>

            <select id="destination" ondblclick="moveItem(document.getElementById(&quot;destination&quot;), document.getElementById(&quot;origin&quot;))" class="form-control h-96 w-full min-w-0 rounded-xl border-gray-25 text-body-2 text-gray-90" name="CoursesList[]" multiple="multiple" size="15">
                <?php
                if (is_array($assigned_courses_to_hrm)) {
                    foreach ($assigned_courses_to_hrm as $enreg) {
                        ?>
                        <option value="<?php echo $enreg['code']; ?>" <?php echo 'title="'.htmlspecialchars($enreg['title'], ENT_QUOTES).'"'; ?>>
                            <?php echo $enreg['title'].' ('.$enreg['code'].')'; ?>
                        </option>
                    <?php
                    }
                }
                ?>
            </select>
        </section>
    </div>
</div>
</form>
<?php
Display::display_footer();
