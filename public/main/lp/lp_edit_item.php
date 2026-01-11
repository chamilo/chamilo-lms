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
 * @author Julio Montoya  - Improving the list of templates
 */
$this_section = SECTION_COURSES;
api_protect_course_script();

/** @var learnpath $learnPath */
$learnPath = Session::read('oLP');

/* Header and action code */
$htmlHeadXtra[] = '<script>'.$learnPath->get_js_dropdown_array().'</script>';

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

$itemTitle = '';
if (isset($lpItem) && $lpItem) {
    $itemTitle = (string) $lpItem->getTitle();
}
$itemTitle = trim(strip_tags($itemTitle));
if ('' === $itemTitle) {
    $itemTitle = get_lang('Edit item');
}

$htmlHeadXtra[] = '<style>
    .lp-edit-right-wrap { padding-left: 24px; padding-right: 16px; }
    .lp-edit-right-title { font-size: 18px; font-weight: 600; margin: 6px 0 14px 0; }
</style>';

$excludeExtraFields = [
    'authors',
    'authorlp',
    'authorlpitem',
    'price',
];
if (api_is_platform_admin()) {
    $excludeExtraFields = [];
}
$right = '';
if (isset($is_success) && true === $is_success) {
    $right = '<div class="lp_message" style="margin-bottom:10px;">';
    $right .= 'The item has been edited.';
    $right .= '</div>';
    $right .= $learnPath->display_item($lpItem, $msg);
} else {
    if ($lpItem && method_exists($lpItem, 'getItemType') && $lpItem->getItemType() === TOOL_LP_FINAL_ITEM) {
        $right .= $learnPath->getFinalItemForm();

        $right .= "<script>
            (function () {
                var el = document.getElementById('frmModel');
                if (el) el.remove();
            })();
        </script>";
    } else {
        $right .= $learnPath->display_edit_item($lpItem, $excludeExtraFields);
    }
    Session::erase('finalItem');
}
$right =
    '<div class="lp-edit-right-wrap">'
    . '<div class="lp-edit-right-title">' . Security::remove_XSS($itemTitle) . '</div>'
    . $right
    . '</div>';

$tpl = new Template();
$tpl->assign('actions', $learnPath->build_action_menu(true));
$tpl->assign('left', $learnPath->showBuildSideBar());
$tpl->assign('right', $right);
$tpl->displayTwoColTemplate();
