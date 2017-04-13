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

/* Constants and variables */
$is_allowed_to_edit = api_is_allowed_to_edit(null, true);

$tbl_lp = Database::get_course_table(TABLE_LP_MAIN);
$tbl_lp_item = Database::get_course_table(TABLE_LP_ITEM);
$tbl_lp_view = Database::get_course_table(TABLE_LP_VIEW);

$isStudentView = isset($_REQUEST['isStudentView']) ? (int) $_REQUEST['isStudentView'] : null;
$learnpath_id = isset($_REQUEST['lp_id']) ? (int) $_REQUEST['lp_id'] : null;
$submit = isset($_POST['submit_button']) ? $_POST['submit_button'] : null;

/* MAIN CODE */
if ((!$is_allowed_to_edit) || ($isStudentView)) {
    error_log('New LP - User not authorized in lp_edit_item_prereq.php');
    header('location:lp_controller.php?action=view&lp_id=' . $learnpath_id);
    exit;
}

// Theme calls.
$show_learn_path = true;
/** @var learnpath $lp */
$lp = $_SESSION['oLP'];
$lp_theme_css = $lp->get_theme();

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
$interbreadcrumb[] = array(
    'url' => api_get_self()."?action=build&lp_id=$learnpath_id",
    'name' => stripslashes($lp->get_name()),
);
$interbreadcrumb[] = array(
    'url' => api_get_self()."?action=add_item&type=step&lp_id=$learnpath_id&".api_get_cidreq(),
    'name' => get_lang('NewStep'),
);


Display::display_header(get_lang('LearnpathPrerequisites'), 'Path');

$suredel = trim(get_lang('AreYouSureToDeleteJS'));
?>
<script>
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
echo $lp->build_action_menu();
echo '<div class="row">';
echo '<div class="col-md-3">';
echo $lp->return_new_tree();
echo '</div>';
echo '<div class="col-md-9">';
$lpItem = new learnpathItem($_GET['id']);
if (isset($is_success) && $is_success == true) {
    echo $lp->display_manipulate($_GET['id'], $lpItem->get_type());
    echo Display::return_message(get_lang("PrerequisitesAdded"));
} else {
    echo $lp->display_manipulate($_GET['id'], $lpItem->get_type());
    echo $lp->display_item_prerequisites_form($_GET['id']);
}
echo '</div>';

Display::display_footer();
