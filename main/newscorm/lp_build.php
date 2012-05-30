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
 * INIT SECTION 
*/

$_SESSION['whereami'] = 'lp/build';
$this_section = SECTION_COURSES;

api_protect_course_script();

/* Libraries */

// The main_api.lib.php, database.lib.php and display.lib.php
// libraries are included by default.

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
if ($action=="add" and $type=="learnpathitem") {
     $htmlHeadXtra[] = "<script language='JavaScript' type='text/javascript'> window.location=\"../resourcelinker/resourcelinker.php?source_id=5&action=$action&learnpath_id=$learnpath_id&chapter_id=$chapter_id&originalresource=no\"; </script>";
}
if ((!$is_allowed_to_edit) || ($isStudentView)) {
    error_log('New LP - User not authorized in lp_build.php');
    header('location:lp_controller.php?action=view&lp_id='.$learnpath_id);
}
// From here on, we are admin because of the previous condition, so don't check anymore.

/* The learnpath has been just created, go get the last id. */
$is_new = false;

$course_id = api_get_course_int_id();

if ($learnpath_id == 0) {
    $is_new = true;

    $sql        = "SELECT id FROM " . $tbl_lp . " WHERE c_id = $course_id ORDER BY id DESC LIMIT 0, 1";
    $result     = Database::query($sql);
    $row        = Database::fetch_array($result);
    $learnpath_id = $row['id'];
}

$sql_query = "SELECT * FROM $tbl_lp WHERE c_id = $course_id AND id = $learnpath_id";

$result = Database::query($sql_query);
$therow = Database::fetch_array($result);

/* SHOWING THE ADMIN TOOLS */

if (!empty($_GET['gradebook']) && $_GET['gradebook'] == 'view') {
    $_SESSION['gradebook'] = Security::remove_XSS($_GET['gradebook']);
    $gradebook = $_SESSION['gradebook'];
} elseif (empty($_GET['gradebook'])) {
    unset($_SESSION['gradebook']);
    $gradebook = '';
}

if (!empty($gradebook) && $gradebook == 'view') {
    $interbreadcrumb[] = array (
        'url' => '../gradebook/' . $_SESSION['gradebook_dest'],
        'name' => get_lang('ToolGradebook')
    );
}
$interbreadcrumb[] = array('url' => 'lp_controller.php?action=list', 'name' => get_lang('LearningPaths'));
$interbreadcrumb[] = array('url' => '#', "name" => $therow['name']);

// Theme calls.
$lp_theme_css=$_SESSION['oLP']->get_theme();
$show_learn_path = true;
Display::display_header('', 'Path');
$suredel = trim(get_lang('AreYouSureToDelete'));

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

/* DISPLAY SECTION */

//echo $_SESSION['oLP']->build_action_menu();

echo '<div class="row-fluid">';
echo '<div class="span12">';

/*    echo '<div class="lp_tree">';
        // Build the tree with the menu items in it.
        echo $_SESSION['oLP']->build_tree();
    echo '</div>';
echo '</div>';        
echo '<div class="span9">';        */
if (isset($is_success) && $is_success === true) {
    Display::display_confirmation_message(get_lang('ItemRemoved'));
} else {
    if ($is_new) {
        Display::display_normal_message(get_lang('LearnpathAdded'), false);
    }
    // Display::display_normal_message(get_lang('LPCreatedAddChapterStep'), false);
    $gradebook = isset($_GET['gradebook']) ? Security::remove_XSS($_GET['gradebook']) : null;

    echo Display::page_header(get_lang('LearnPathAddedTitle'));
    
    echo '<ul id="lp_overview" class="thumbnails">';
    
    echo show_block('lp_controller.php?'.api_get_cidreq().'&amp;gradebook='.$gradebook.'&amp;action=add_item&amp;type=step&amp;lp_id=' . $_SESSION['oLP']->lp_id, get_lang("NewStep"), get_lang('NewStepComment'), 'tools.png');
    
    echo show_block('lp_controller.php?'.api_get_cidreq().'&amp;gradebook='.$gradebook.'&amp;action=admin_view&amp;updateaudio=true&amp;lp_id=' . $_SESSION['oLP']->lp_id, get_lang("BasicOverview"), get_lang('BasicOverviewComment'), 'audio.png');
    
    echo show_block('lp_controller.php?'.api_get_cidreq().'&amp;gradebook='.$gradebook.'&amp;action=view&amp;lp_id=' . $_SESSION['oLP']->lp_id, get_lang("Display"), get_lang('DisplayComment'), 'view.png');
    
    echo show_block('lp_controller.php?'.api_get_cidreq().'&amp;gradebook='.$gradebook.'&amp;action=edit&amp;lp_id=' . $_SESSION['oLP']->lp_id, get_lang("Settings"), null, 'reference.png');
    

    
    echo '</ul>';    
}

function show_block($link, $title, $subtitle, $icon) {
    $html = '<li class="span3">';
        $html .=  '<div class="thumbnail">';
        $html .=  '<a href="'.$link.'" title="'.$title.'">';
        $html .=  Display::return_icon($icon, $title, array(), ICON_SIZE_BIG);
        $html .=  '</a>';
        $html .=  '<div class="caption">';
        $html .=  '<strong>'.$title.'</strong></a> '.$subtitle;
        $html .=  '</div>';
        $html .=  '</div>';
    $html .=  '</li>';
    return $html;
}
echo '</div>';
echo '</div>';

/* FOOTER */

Display::display_footer();
