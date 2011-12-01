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

$isStudentView  = (int) $_REQUEST['isStudentView'];
$learnpath_id   = (int) $_REQUEST['lp_id'];
$submit			= $_POST['submit_button'];

/* MAIN CODE */

// Using the resource linker as a tool for adding resources to the learning path.
if ($action == 'add' and $type == 'learnpathitem') {
     $htmlHeadXtra[] = "<script language='JavaScript' type='text/javascript'> window.location=\"../resourcelinker/resourcelinker.php?source_id=5&action=$action&learnpath_id=$learnpath_id&chapter_id=$chapter_id&originalresource=no\"; </script>";
}
if ((! $is_allowed_to_edit) || ($isStudentView)) {
    error_log('New LP - User not authorized in lp_edit_item_prereq.php');
    header('location:lp_controller.php?action=view&lp_id='.$learnpath_id);
}
$course_id = api_get_course_int_id();


$sql_query = "SELECT * FROM $tbl_lp WHERE c_id = $course_id AND id = $learnpath_id";
$result = Database::query($sql_query);
$therow = Database::fetch_array($result);

/* SHOWING THE ADMIN TOOLS */

if (isset($_SESSION['gradebook'])){
    $gradebook = $_SESSION['gradebook'];
}

if (!empty($gradebook) && $gradebook == 'view') {
    $interbreadcrumb[] = array (
            'url' => '../gradebook/'.$_SESSION['gradebook_dest'],
            'name' => get_lang('ToolGradebook')
        );
}

$interbreadcrumb[] = array('url' => 'lp_controller.php?action=list', 'name' => get_lang('LearningPaths'));
$interbreadcrumb[] = array('url' => api_get_self()."?action=build&lp_id=$learnpath_id", 'name' => stripslashes("{$therow['name']}"));

// Theme calls.
$show_learn_path = true;
$lp_theme_css = $_SESSION['oLP']->get_theme();

Display::display_header(null,'Path');
//api_display_tool_title($therow['name']);

$suredel = trim(get_lang('AreYouSureToDelete'));
//$suredelstep = trim(get_lang('AreYouSureToDeleteSteps'));
?>
<script type='text/javascript'>
/* <![CDATA[ */
function stripslashes(str) {
    str=str.replace(/\\'/g,'\'');
    str=str.replace(/\\"/g,'"');
    str=str.replace(/\\\\/g,'\\');
    str=str.replace(/\\0/g,'\0');
    return str;
}
function confirmation(name)
{
    name=stripslashes(name);
    if (confirm("<?php echo $suredel; ?> " + name + " ?"))
    {
        return true;
    }
    else
    {
        return false;
    }
}
</script>
<?php

//echo $admin_output;

/* DISPLAY SECTION */

echo $_SESSION['oLP']->build_action_menu();
echo '<table cellpadding="0" cellspacing="0" class="lp_build">';
    echo '<tr>';

        echo '<td class="tree">';
            echo '<div class="lp_tree">';
                // Build the tree with the menu items in it.
                echo $_SESSION['oLP']->build_tree();

            echo '</div>';
        echo '</td>';
        echo '<td class="workspace">';

            if (isset($is_success) && $is_success === true) {
                echo '<div class="lp_message" style="margin:3px 10px;">';
                echo get_lang("PrerequisitesAdded");
                echo '</div>';
            } else {
                echo $_SESSION['oLP']->display_manipulate($_GET['id'], null);
                echo $_SESSION['oLP']->display_item_prerequisites_form($_GET['id']);
            }
        echo '</td>';
    echo '</tr>';
echo '</table>';

/* FOOTER */
Display::display_footer();