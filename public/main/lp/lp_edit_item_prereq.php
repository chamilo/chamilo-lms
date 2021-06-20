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

/* Constants and variables */
$is_allowed_to_edit = api_is_allowed_to_edit(null, true);

$isStudentView = isset($_REQUEST['isStudentView']) ? (int) $_REQUEST['isStudentView'] : null;
$learnpath_id = isset($_REQUEST['lp_id']) ? (int) $_REQUEST['lp_id'] : null;
$submit = $_POST['submit_button'] ?? null;

/* MAIN CODE */
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
        'name' => get_lang('Assessments'),
    ];
}

$interbreadcrumb[] = [
    'url' => 'lp_controller.php?action=list',
    'name' => get_lang('Learning paths'),
];
$interbreadcrumb[] = [
    'url' => api_get_self()."?action=add_item&lp_id=$learnpath_id",
    'name' => $lp->getNameNoTags(),
];
$interbreadcrumb[] = [
    'url' => api_get_self()."?action=add_item&type=step&lp_id=$learnpath_id&".api_get_cidreq(),
    'name' => get_lang('Add learning object or activity'),
];

$right = '';
if (isset($is_success) && true == $is_success) {
    $right .= $lp->displayItemMenu($lpItem);
    $right .= Display::return_message(get_lang('Prerequisites to the current learning object have been added.'));
} else {
    $right .= $lp->displayItemMenu($lpItem);
    $right .= $lp->display_item_prerequisites_form($lpItem);
}

$tpl = new Template(get_lang('Prerequisites'));
$tpl->assign('actions', $lp->build_action_menu(true));
$tpl->assign('left', $lp->showBuildSideBar());
$tpl->assign('right', $right);
$tpl->displayTwoColTemplate();

