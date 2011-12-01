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
                Display::display_confirmation_message(get_lang('ItemRemoved'));
            } else {
                if ($is_new) {
                    Display::display_normal_message(get_lang('LearnpathAdded'), false);
                }
                // Display::display_normal_message(get_lang('LPCreatedAddChapterStep'), false);
                $gradebook = isset($_GET['gradebook']) ? Security::remove_XSS($_GET['gradebook']) : null;

                $learnpathadded  = '<p><h2>'.get_lang('LearnPathAddedTitle').'</h2><br />';
                $learnpathadded .= '<a href="lp_controller.php?'.api_get_cidreq().'&amp;gradebook='.$gradebook.'&amp;action=add_item&amp;type=step&amp;lp_id=' . $_SESSION['oLP']->lp_id . '" title="'.get_lang('NewStep').'">'.Display::return_icon('new_learnigpath_object.png', get_lang('NewStep'), array('style' => 'vertical-align: middle;'),'22').' '.get_lang('NewStep').'</a>: '.get_lang('NewStepComment').'<br />';
                $learnpathadded .= '<a href="lp_controller.php?'.api_get_cidreq().'&amp;gradebook='.$gradebook.'&amp;action=add_item&amp;type=chapter&amp;lp_id=' . $_SESSION['oLP']->lp_id . '" title="'.get_lang('NewChapter').'">'.Display::return_icon('add_learnpath_section.png', get_lang('NewChapter'), array('style' => 'vertical-align: middle;'),'22').' '.get_lang('NewChapter').'</a>: '.get_lang('NewChapterComment').'<br />';
                $learnpathadded .= '<a href="lp_controller.php?'.api_get_cidreq().'&amp;action=build&amp;lp_id='.Security::remove_XSS($_GET['lp_id']).'" target="_parent">'.Display::return_icon('build_learnpath.png', get_lang('Build'), array('style' => 'vertical-align: middle;'),'22').' '.get_lang('Build')."</a>: ".get_lang('BuildComment').'<br />';
                $learnpathadded .= '<a href="lp_controller.php?'.api_get_cidreq().'&amp;gradebook='.$gradebook.'&amp;action=admin_view&amp;lp_id=' . $_SESSION['oLP']->lp_id . '" title="'.get_lang("BasicOverview").'">'.Display::return_icon('move_learnpath.png', get_lang('BasicOverview'), array('style' => 'vertical-align: middle;'),'22').' '.get_lang('BasicOverview').'</a>: '.get_lang('BasicOverviewComment').'<br />';
                $learnpathadded .= '<a href="lp_controller.php?'.api_get_cidreq().'&amp;gradebook='.$gradebook.'&action=view&lp_id='.$_SESSION['oLP']->lp_id.'">'.Display::return_icon('view_left_right.png', get_lang('Display'),array('style' => 'vertical-align: middle;'),'22').' '.get_lang('Display').'</a>: '.get_lang('DisplayComment').'<br />';
                $learnpathadded .= '<br /></p>';
                echo $learnpathadded;
            }
        echo '</td>';
    echo '</tr>';
echo '</table>';

/* FOOTER */

Display::display_footer();
