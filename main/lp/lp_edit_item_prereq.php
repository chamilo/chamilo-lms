<?php

/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * This is a learning path creation and player tool in Chamilo - previously learnpath_handler.php.
 *
 * @author Patrick Cool
 * @author Denes Nagy
 * @author Roan Embrechts, refactoring and code cleaning
 * @author Yannick Warnier <ywarnier@beeznest.org> - cleaning and update for new SCORM tool
 */
$this_section = SECTION_COURSES;

api_protect_course_script();

$is_allowed_to_edit = api_is_allowed_to_edit(null, true);
$isStudentView = isset($_REQUEST['isStudentView']) ? (int) $_REQUEST['isStudentView'] : null;
$learnpath_id = isset($_REQUEST['lp_id']) ? (int) $_REQUEST['lp_id'] : null;
$submit = isset($_POST['submit_button']) ? $_POST['submit_button'] : null;

if (!$is_allowed_to_edit || $isStudentView) {
    header('location:lp_controller.php?action=view&lp_id='.$learnpath_id);
    exit;
}

// Theme calls.
$show_learn_path = true;
/** @var learnpath $lp */
$lp = Session::read('oLP');
$lp_theme_css = $lp->get_theme();

if (api_is_in_gradebook()) {
    $interbreadcrumb[] = [
        'url' => Category::getUrl(),
        'name' => get_lang('ToolGradebook'),
    ];
}

$interbreadcrumb[] = [
    'url' => 'lp_controller.php?action=list',
    'name' => get_lang('LearningPaths'),
];
$interbreadcrumb[] = [
    'url' => api_get_self()."?action=build&lp_id=$learnpath_id",
    'name' => $lp->getNameNoTags(),
];
$interbreadcrumb[] = [
    'url' => api_get_self()."?action=add_item&type=step&lp_id=$learnpath_id&".api_get_cidreq(),
    'name' => get_lang('NewStep'),
];

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

echo $lp->build_action_menu();
echo '<div class="row">';
echo '<div class="col-md-3">';
echo $lp->return_new_tree();
echo '</div>';
echo '<div class="col-md-9">';
echo '<div class="prerequisites">';
$lpItem = new learnpathItem($_GET['id']);
if (isset($is_success) && $is_success == true) {
    echo $lp->display_manipulate($_GET['id'], $lpItem->get_type());
    echo Display::return_message(get_lang('PrerequisitesAdded'));
} else {
    echo $lp->display_manipulate($_GET['id'], $lpItem->get_type());
    echo $lp->display_item_prerequisites_form($_GET['id']);
}
echo '</div>';
echo '</div>';
Display::display_footer();
