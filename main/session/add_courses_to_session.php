<?php
/* For licensing terms, see /license.txt */

/**
 * @package chamilo.admin
 *
 * @todo use formvalidator
 */
$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

$sessionId = isset($_GET['id_session']) ? (int) $_GET['id_session'] : 0;
$add = isset($_GET['add']) ? Security::remove_XSS($_GET['add']) : null;

SessionManager::protectSession($sessionId);

$xajax = new xajax();
$xajax->registerFunction(['search_courses', 'AddCourseToSession', 'search_courses']);

// Setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;

// setting breadcrumbs
$interbreadcrumb[] = [
    'url' => 'session_list.php',
    'name' => get_lang('SessionList'),
];
$interbreadcrumb[] = [
    'url' => "resume_session.php?id_session=$sessionId",
    'name' => get_lang('SessionOverview'),
];

// Database Table Definitions
$tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
$tbl_session_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_USER);
$tbl_session_rel_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
$tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);

// setting the name of the tool
$tool_name = get_lang('SubscribeCoursesToSession');

$add_type = 'multiple';
if (isset($_GET['add_type']) && $_GET['add_type'] != '') {
    $add_type = Security::remove_XSS($_REQUEST['add_type']);
}

$page = isset($_GET['page']) ? Security::remove_XSS($_GET['page']) : null;

$xajax->processRequests();

$htmlHeadXtra[] = $xajax->getJavascript('../inc/lib/xajax/');
$htmlHeadXtra[] = '<script>
function add_course_to_session(code, content) {
	document.getElementById("course_to_add").value = "";
	document.getElementById("ajax_list_courses_single").innerHTML = "";
	destination = document.getElementById("destination");
	for (i=0;i<destination.length;i++) {
		if (destination.options[i].text == content) {
            return false;
		}
	}

	destination.options[destination.length] = new Option(content,code);
	destination.selectedIndex = -1;
	sortOptions(destination.options);
}

function remove_item(origin)
{
	for(var i = 0 ; i<origin.options.length ; i++) {
		if(origin.options[i].selected) {
			origin.options[i]=null;
			i = i-1;
		}
	}
}
</script>';

$CourseList = $SessionList = [];
$courses = $sessions = [];

if (isset($_POST['formSent']) && $_POST['formSent']) {
    $courseList = $_POST['SessionCoursesList'];
    $copyEvaluation = isset($_POST['copy_evaluation']);
    $copyCourseTeachersAsCoach = isset($_POST['import_teachers_as_course_coach']);
    $importAssignments = isset($_POST['import_assignments']);

    SessionManager::add_courses_to_session(
        $sessionId,
        $courseList,
        true,
        $copyEvaluation,
        $copyCourseTeachersAsCoach,
        $importAssignments
    );

    Display::addFlash(Display::return_message(get_lang('Updated')));

    $url = api_get_path(WEB_CODE_PATH).'session/';
    if (isset($add)) {
        header('Location: '.$url.'add_users_to_session.php?id_session='.$sessionId.'&add=true');
    } else {
        header('Location: '.$url.'resume_session.php?id_session='.$sessionId);
    }

    exit;
}

// Display the header
Display::display_header($tool_name);

if ($add_type === 'multiple') {
    $link_add_type_unique = '<a href="'.api_get_self().'?id_session='.$sessionId.'&add='.$add.'&add_type=unique">'.
        Display::return_icon('single.gif').get_lang('SessionAddTypeUnique').'</a>';
    $link_add_type_multiple = Display::return_icon('multiple.gif').get_lang('SessionAddTypeMultiple').' ';
} else {
    $link_add_type_unique = Display::return_icon('single.gif').get_lang('SessionAddTypeUnique').'&nbsp;&nbsp;&nbsp;';
    $link_add_type_multiple = '<a href="'.api_get_self().'?id_session='.$sessionId.'&add='.$add.'&add_type=multiple">'.
        Display::return_icon('multiple.gif').get_lang('SessionAddTypeMultiple').'</a>';
}

// the form header
$session_info = SessionManager::fetch($sessionId);
echo '<div class="actions">';
echo $link_add_type_unique.$link_add_type_multiple;
echo '</div>';

$ajax_search = $add_type === 'unique' ? true : false;
$nosessionCourses = $sessionCourses = [];
if ($ajax_search) {
    $sql = "SELECT course.id, code, title, visual_code, session_id
			FROM $tbl_course course
			INNER JOIN $tbl_session_rel_course session_rel_course
            ON
                course.id = session_rel_course.c_id AND
                session_rel_course.session_id = $sessionId
			ORDER BY ".(count($courses) ? "(code IN (".implode(',', $courses).")) DESC," : '')." title";

    if (api_is_multiple_url_enabled()) {
        $tbl_course_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
        $access_url_id = api_get_current_access_url_id();
        if ($access_url_id != -1) {
            $sql = "SELECT course.id, code, title, visual_code, session_id
                    FROM $tbl_course course
                    INNER JOIN $tbl_session_rel_course session_rel_course
                    ON course.id = session_rel_course.c_id AND session_rel_course.session_id = $sessionId
                    INNER JOIN $tbl_course_rel_access_url url_course
                    ON (url_course.c_id = course.id)
                    WHERE access_url_id = $access_url_id
                    ORDER BY ".(count($courses) ? " (code IN(".implode(',', $courses).")) DESC," : '')." title";
        }
    }

    $result = Database::query($sql);
    $Courses = Database::store_result($result);
    foreach ($Courses as $course) {
        $sessionCourses[$course['id']] = $course;
    }
} else {
    $sql = "SELECT course.id, code, title, visual_code, session_id
			FROM $tbl_course course
			LEFT JOIN $tbl_session_rel_course session_rel_course
            ON
                course.id = session_rel_course.c_id AND
                session_rel_course.session_id = $sessionId
			ORDER BY ".(count($courses) ? "(code IN(".implode(',', $courses).")) DESC," : '')." title";

    if (api_is_multiple_url_enabled()) {
        $tbl_course_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
        $access_url_id = api_get_current_access_url_id();
        if ($access_url_id != -1) {
            $sql = "SELECT course.id, code, title, visual_code, session_id
                    FROM $tbl_course course
                    LEFT JOIN $tbl_session_rel_course session_rel_course
                    ON
                        course.id = session_rel_course.c_id AND
                        session_rel_course.session_id = $sessionId
                    INNER JOIN $tbl_course_rel_access_url url_course
                    ON (url_course.c_id = course.id)
                    WHERE access_url_id = $access_url_id
                    ORDER BY ".(count($courses) ? "(code IN(".implode(',', $courses).")) DESC," : '')." title";
        }
    }
    $result = Database::query($sql);
    $Courses = Database::store_result($result);
    foreach ($Courses as $course) {
        if ($course['session_id'] == $sessionId) {
            $sessionCourses[$course['id']] = $course;
        } else {
            $nosessionCourses[$course['id']] = $course;
        }
    }
}

if (!api_is_platform_admin() && api_is_teacher()) {
    $coursesFromTeacher = CourseManager::getCoursesFollowedByUser(
        api_get_user_id(),
        COURSEMANAGER
    );

    foreach ($nosessionCourses as &$course) {
        if (in_array($course['code'], array_keys($coursesFromTeacher))) {
            continue;
        } else {
            unset($nosessionCourses[$course['id']]);
        }
    }
}

unset($Courses);
?>
<form name="formulaire"
      method="post" action="<?php echo api_get_self(); ?>?page=<?php echo $page; ?>&id_session=<?php echo $sessionId; if (!empty($_GET['add'])) {
    echo '&add=true';
} ?>" style="margin:0px;" <?php if ($ajax_search) {
    echo ' onsubmit="valide();"';
}?>>
    <legend><?php echo $tool_name.' ('.Security::remove_XSS($session_info['name']).')'; ?></legend>
    <input type="hidden" name="formSent" value="1" />
    <div id="multiple-add-session" class="row">
        <div class="col-md-4">
            <label><?php echo get_lang('CourseListInPlatform'); ?> :</label>
            <?php
            if (!($add_type == 'multiple')) {
                ?>
                <input type="text" id="course_to_add" onkeyup="xajax_search_courses(this.value, 'single', <?php echo $sessionId; ?>)" class="form-control"/>
                <div id="ajax_list_courses_single"></div>
            <?php
            } else {
                ?>
                <div id="ajax_list_courses_multiple">
                    <select id="origin" name="NoSessionCoursesList[]" multiple="multiple" size="20" class="form-control">
                        <?php foreach ($nosessionCourses as $enreg) {
                    ?>
                            <option value="<?php echo $enreg['id']; ?>" <?php echo 'title="'.htmlspecialchars($enreg['title'].' ('.$enreg['visual_code'].')', ENT_QUOTES).'"';
                    if (in_array($enreg['code'], $CourseList)) {
                        echo 'selected="selected"';
                    } ?>>
                                <?php echo $enreg['title'].' ('.$enreg['visual_code'].')'; ?>
                            </option>
                        <?php
                } ?>
                    </select>
                </div>
            <?php
            }
            unset($nosessionCourses);
            ?>
        </div>
        <div class="col-md-4">
            <?php if ($add_type == 'multiple') {
                ?>
                <div class="code-course">
                    <?php echo get_lang('FirstLetterCourse'); ?> :

                    <select name="firstLetterCourse" onchange = "xajax_search_courses(this.value,'multiple', <?php echo $sessionId; ?>)" class="selectpicker form-control">
                        <option value="%">--</option>
                        <?php
                        echo Display::get_alphabet_options();
                echo Display::get_numeric_options(0, 9, ''); ?>
                    </select>
                </div>
            <?php
            } ?>
            <div class="control-course">
            <?php
            if ($ajax_search) {
                ?>
                <div class="separate-action">
                    <button class="btn btn-primary" type="button" onclick="remove_item(document.getElementById('destination'))">
                        <em class="fa fa-chevron-left"></em>
                    </button>
                </div>
            <?php
            } else {
                ?>
                <div class="separate-action">
                    <button name="add_course" class="btn btn-primary" type="button" onclick="moveItem(document.getElementById('origin'), document.getElementById('destination'))" onclick="moveItem(document.getElementById('origin'), document.getElementById('destination'))">
                        <em class="fa fa-chevron-right"></em>
                    </button>
                </div>
                <div class="separate-action">
                    <button name="remove_course" class="btn btn-primary" type="button" onclick="moveItem(document.getElementById('destination'), document.getElementById('origin'))" onclick="moveItem(document.getElementById('destination'), document.getElementById('origin'))">
                        <em class="fa fa-chevron-left"></em>
                    </button>
                </div>
            <?php
            } ?>
                <div class="separate-action">
                    <label>
                        <input type="checkbox" name="copy_evaluation">
                        <?php echo get_lang('ImportGradebookInCourse'); ?>
                    </label>
                    <label>
                        <input type="checkbox" name="import_teachers_as_course_coach">
                        <?php echo get_lang('ImportCourseTeachersAsCourseCoach'); ?>
                    </label>
                    <label>
                        <input type="checkbox" name="import_assignments">
                        <?php echo get_lang('SessionImportAssignments'); ?>
                    </label>
                </div>
            <?php
            echo '<div class="separate-action">';
            if (isset($_GET['add'])) {
                echo '<button name="next" class="btn btn-success" type="button" value="" onclick="valide()" >'.get_lang('NextStep').'</button>';
            } else {
                echo '<button name="next" class="btn btn-success" type="button" value="" onclick="valide()" >'.get_lang('SubscribeCoursesToSession').'</button>';
            }
            echo '</div>';
            ?>
            </div>
        </div>
        <div class="col-md-4">
            <label><?php echo get_lang('CourseListInSession'); ?> :</label>
            <select id='destination' name="SessionCoursesList[]" multiple="multiple" size="20" class="form-control">
                <?php
                foreach ($sessionCourses as $enreg) {
                    ?>
                    <option value="<?php echo $enreg['id']; ?>" title="<?php echo htmlspecialchars($enreg['title'].' ('.$enreg['visual_code'].')', ENT_QUOTES); ?>">
                        <?php echo $enreg['title'].' ('.$enreg['visual_code'].')'; ?>
                    </option>
                <?php
                }
                unset($sessionCourses);
                ?>
            </select>
        </div>
    </div>
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

    function mysort(a, b) {
        if (a.text.toLowerCase() > b.text.toLowerCase()){
            return 1;
        }
        if (a.text.toLowerCase() < b.text.toLowerCase()){
            return -1;
        }
        return 0;
    }

    function valide() {
        var options = document.getElementById('destination').options;
        for (i = 0 ; i<options.length ; i++)
            options[i].selected = true;

        document.forms.formulaire.submit();
    }
</script>
<?php
Display::display_footer();
