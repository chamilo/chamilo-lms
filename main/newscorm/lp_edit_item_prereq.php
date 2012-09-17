<?php
/* For licensing terms, see /license.txt */
/**
 * This is a learning path creation and player tool in Chamilo - previously learnpath_handler.php
 *
 * @author Patrick Cool
 * @author Denes Nagy
 * @author Roan Embrechts, refactoring and code cleaning
 * @author Yannick Warnier <ywarnier@beeznest.org> - cleaning and update for new SCORM tool
 * @package chamilo.learnpath
 */
$this_section = SECTION_COURSES;

api_protect_course_script();

/* Libraries */

include 'learnpath_functions.inc.php';
//include '../resourcelinker/resourcelinker.inc.php';
include 'resourcelinker.inc.php';
// Rewrite the language file, sadly overwritten by resourcelinker.inc.php.
// Name of the language file that needs to be included.
$language_file = 'learnpath';

/* Constants and variables */

$is_allowed_to_edit = api_is_allowed_to_edit(null, true);

$tbl_lp = Database::get_course_table(TABLE_LP_MAIN);
$tbl_lp_item = Database::get_course_table(TABLE_LP_ITEM);
$tbl_lp_view = Database::get_course_table(TABLE_LP_VIEW);

$isStudentView = (int) $_REQUEST['isStudentView'];
$learnpath_id = (int) $_REQUEST['lp_id'];
$submit = $_POST['submit_button'];

/* MAIN CODE */

// Using the resource linker as a tool for adding resources to the learning path.
if ($action == 'add' and $type == 'learnpathitem') {
    $htmlHeadXtra[] = "<script language='JavaScript' type='text/javascript'> window.location=\"../resourcelinker/resourcelinker.php?source_id=5&action=$action&learnpath_id=$learnpath_id&chapter_id=$chapter_id&originalresource=no\"; </script>";
}
if ((!$is_allowed_to_edit) || ($isStudentView)) {
    error_log('New LP - User not authorized in lp_edit_item_prereq.php');
    header('location:lp_controller.php?action=view&lp_id=' . $learnpath_id);
}
$course_id = api_get_course_int_id();


$sql_query = "SELECT * FROM $tbl_lp WHERE c_id = $course_id AND id = $learnpath_id";
$result = Database::query($sql_query);
$therow = Database::fetch_array($result);

/* SHOWING THE ADMIN TOOLS */

if (isset($_SESSION['gradebook'])) {
    $gradebook = $_SESSION['gradebook'];
}

if (!empty($gradebook) && $gradebook == 'view') {
    $interbreadcrumb[] = array(
        'url' => '../gradebook/' . $_SESSION['gradebook_dest'],
        'name' => get_lang('ToolGradebook')
    );
}

$interbreadcrumb[] = array('url' => 'lp_controller.php?action=list', 'name' => get_lang('LearningPaths'));
$interbreadcrumb[] = array('url' => api_get_self() . "?action=build&lp_id=$learnpath_id", 'name' => stripslashes("{$therow['name']}"));
$interbreadcrumb[] = array('url' => api_get_self() . "?action=add_item&type=step&lp_id=$learnpath_id", 'name' => get_lang('NewStep'));

// Theme calls.
$show_learn_path = true;
$lp_theme_css = $_SESSION['oLP']->get_theme();

Display::display_header(get_lang('Prerequisites'), 'Path');

$suredel = trim(get_lang('AreYouSureToDelete'));
?>
<script>
    /* <![CDATA[ */
    function stripslashes(str) {
        str=str.replace(/\\'/g,'\'');
        str=str.replace(/\\"/g,'"');
        str=str.replace(/\\\\/g,'\\');
        str=str.replace(/\\0/g,'\0');
        return str;
    }
    function confirmation(name) {
        name=stripslashes(name);
        if (confirm("<?php echo $suredel; ?> " + name + " ?")) {
            return true;
        } else {
            return false;
        }
    }
</script>
<?php

/* DISPLAY SECTION */

echo $_SESSION['oLP']->build_action_menu();

echo '<div class="row-fluid">';
echo '<div class="span3">';
echo $_SESSION['oLP']->return_new_tree();
echo '</div>';
echo '<div class="span9">';
if (isset($is_success) && $is_success == true) {
    echo $_SESSION['oLP']->display_manipulate($_GET['id'], null);
    echo Display::return_message(get_lang("PrerequisitesAdded"));
} else {
    echo $_SESSION['oLP']->display_manipulate($_GET['id'], null);
    echo $_SESSION['oLP']->display_item_prerequisites_form($_GET['id']);
}
echo '</div>';

Display::display_footer();