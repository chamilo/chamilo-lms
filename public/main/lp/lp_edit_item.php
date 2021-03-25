<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use ChamiloSession as Session;

/**
 * This is a learning path creation and player tool in Chamilo - previously learnpath_handler.php.
 *
 * @author Patrick Cool
 * @author Denes Nagy
 * @author Roan Embrechts, refactoring and code cleaning
 * @author Yannick Warnier <ywarnier@beeznest.org> - cleaning and update for new SCORM tool
 * @author Julio Montoya  - Improving the list of templates
 */
$this_section = SECTION_COURSES;
api_protect_course_script();

/** @var learnpath $learnPath */
$learnPath = Session::read('oLP');

/* Header and action code */
$htmlHeadXtra[] = '<script>'.$learnPath->get_js_dropdown_array().'
$(function() {
    CKEDITOR.on("instanceReady", function (e) {
        showTemplates("content_lp");
    });
});
</script>';

/* Constants and variables */
$is_allowed_to_edit = api_is_allowed_to_edit(null, true);
$tbl_lp = Database::get_course_table(TABLE_LP_MAIN);

$isStudentView = isset($_REQUEST['isStudentView']) ? intval($_REQUEST['isStudentView']) : null;
$learnpath_id = (int) $_REQUEST['lp_id'];
$submit = isset($_POST['submit_button']) ? $_POST['submit_button'] : null;

if (!$is_allowed_to_edit || $isStudentView) {
    header('location:lp_controller.php?action=view&lp_id='.$learnpath_id.'&'.api_get_cidreq());
    exit;
}
// From here on, we are admin because of the previous condition, so don't check anymore.
/** @var learnpath $learnPath */
$learnPath = Session::read('oLP');
$course_id = api_get_course_int_id();

/*
    Course admin section
    - all the functions not available for students - always available in this case (page only shown to admin)
*/
if (api_is_in_gradebook()) {
    $interbreadcrumb[] = [
        'url' => Category::getUrl(),
        'name' => get_lang('Assessments'),
    ];
}
$interbreadcrumb[] = [
    'url' => 'lp_controller.php?action=list&'.api_get_cidreq(),
    'name' => get_lang('Learning paths'),
];
$interbreadcrumb[] = [
    'url' => api_get_self()."?action=add_item&lp_id=$learnpath_id&".api_get_cidreq(),
    'name' => $learnPath->getNameNoTags(),
];
$interbreadcrumb[] = [
    'url' => api_get_self()."?action=add_item&type=step&lp_id=$learnpath_id&".api_get_cidreq(),
    'name' => get_lang('Add learning object or activity'),
];

$show_learn_path = true;
$lp_theme_css = $learnPath->get_theme();
Display::display_header(get_lang('Edit'), 'Path');
echo $learnPath->build_action_menu();
echo '<div class="row">';
echo $learnPath->showBuildSideBar();
echo '<div id="doc_form" class="col-md-8">';
$excludeExtraFields = [
    'authors',
    'authorlp',
    'authorlpitem',
    'price',
];
if (api_is_platform_admin()) {
    // Only admins can edit this items
    $excludeExtraFields = [];
}
if (isset($is_success) && true === $is_success) {
    $msg = '<div class="lp_message" style="margin-bottom:10px;">';
    $msg .= 'The item has been edited.';
    $msg .= '</div>';
    echo $learnPath->display_item($lpItem, $msg);
} else {
    echo $learnPath->display_edit_item($lpItem, $excludeExtraFields);
    $finalItem = Session::read('finalItem');
    if ($finalItem) {
        echo '<script>$("#frmModel").remove()</script>';
    }
    Session::erase('finalItem');
}

echo '</div>';
echo '</div>';

Display::display_footer();
