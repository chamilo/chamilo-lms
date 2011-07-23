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
/**
 * Code
 */
// Prevents FF 3.6 + Adobe Reader 9 bug see BT#794 when calling a pdf file in a LP.
if (isset($_GET['src'])) {
    // Including the global initialization file.
    require_once '../inc/global.inc.php';
    api_protect_course_script();
    // Get parameter only came from lp_view.php.
    $url_info 		= parse_url($_GET['src']);
    $real_url_info	= parse_url(api_get_path(WEB_PATH));

    // The host must be the same.
    if ($url_info['host'] == $real_url_info['host']) {
        header("Location: ".urldecode(Security::remove_XSS($_GET['src'])));
        exit;
    } else {
        header("Location: blank.php?error=document_not_found");
        exit;
    }
}

/* INIT SECTION */

$_SESSION['whereami'] = 'lp/build';
if (isset($_SESSION['oLP']) && isset($_GET['id'])) {
    $_SESSION['oLP'] -> current = intval($_GET['id']);
}
$this_section=SECTION_COURSES;

api_protect_course_script();

/* Libraries */

// The main_api.lib.php, database.lib.php and display.lib.php
// libraries are included by default.

require_once 'learnpath_functions.inc.php';
//include '../resourcelinker/resourcelinker.inc.php';
require_once 'resourcelinker.inc.php';
//rewrite the language file, sadly overwritten by resourcelinker.inc.php
// name of the language file that needs to be included
$language_file = "learnpath";

/* Header and action code */
/* Constants and variables */
$is_allowed_to_edit = api_is_allowed_to_edit(null, true);

$tbl_lp = Database::get_course_table(TABLE_LP_MAIN);
$tbl_lp_item = Database::get_course_table(TABLE_LP_ITEM);
$tbl_lp_view = Database::get_course_table(TABLE_LP_VIEW);

$isStudentView  = (empty($_REQUEST['isStudentView']) ? 0 : (int) $_REQUEST['isStudentView']);
$learnpath_id   = (int) $_REQUEST['lp_id'];
/*
$chapter_id     = $_GET['chapter_id'];
$title          = $_POST['title'];
$description   = $_POST['description'];
$Submititem     = $_POST['Submititem'];
$action         = $_REQUEST['action'];
$id             = (int) $_REQUEST['id'];
$type           = $_REQUEST['type'];
$direction      = $_REQUEST['direction'];
$moduleid       = $_REQUEST['moduleid'];
$prereq         = $_REQUEST['prereq'];
$type           = $_REQUEST['type'];
*/

/* MAIN CODE */

// Using the resource linker as a tool for adding resources to the learning path.
if ($action == 'add' && $type == 'learnpathitem') {
     $htmlHeadXtra[] = "<script language='JavaScript' type='text/javascript'> window.location=\"../resourcelinker/resourcelinker.php?source_id=5&action=$action&learnpath_id=$learnpath_id&chapter_id=$chapter_id&originalresource=no\"; </script>";
}
if ((!$is_allowed_to_edit) || ($isStudentView)) {
    error_log('New LP - User not authorized in lp_view_item.php');
    header('location:lp_controller.php?action=view&lp_id='.$learnpath_id);
}
// From here on, we are admin because of the previous condition, so don't check anymore.

$sql_query = "SELECT * FROM $tbl_lp WHERE id = $learnpath_id";
$result=Database::query($sql_query);
$therow=Database::fetch_array($result);

//$admin_output = '';
/*
    Course admin section
    - all the functions not available for students - always available in this case (page only shown to admin)
*/

/* SHOWING THE ADMIN TOOLS	*/

if (isset($_SESSION['gradebook'])) {
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

// Theme calls
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

//echo $admin_output;

/* DISPLAY SECTION	*/

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
            echo $_SESSION['oLP']->display_item((isset($new_item_id)) ? $new_item_id : $_GET['id']);
        echo '</td>';
    echo '</tr>';
echo '</table>';

/* FOOTER */

Display::display_footer();
