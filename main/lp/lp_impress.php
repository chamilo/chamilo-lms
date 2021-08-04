<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * @package chamilo.learnpath
 */
require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_COURSES;

//To prevent the template class
$show_learnpath = true;

api_protect_course_script();

$lp_id = intval($_GET['lp_id']);

// Check if the learning path is visible for student - (LP requisites)
if (!api_is_allowed_to_edit(null, true) &&
    !learnpath::is_lp_visible_for_student($lp_id, api_get_user_id(), api_get_course_info())
) {
    api_not_allowed();
}

//Checking visibility (eye icon)
$visibility = api_get_item_visibility(
    api_get_course_info(),
    TOOL_LEARNPATH,
    $lp_id,
    api_get_session_id(),
    api_get_user_id(),
    null,
    api_get_group_id()
);
if (!api_is_allowed_to_edit(null, true) && intval($visibility) == 0) {
    api_not_allowed();
}
/** @var learnpath $lp */
$lp = Session::read('oLP');
if (!$lp) {
    api_not_allowed(true);
}

$debug = 0;
$course_code = api_get_course_id();
$course_id = api_get_course_int_id();
$htmlHeadXtra[] = api_get_css(api_get_path(WEB_LIBRARY_PATH).'javascript/impress/impress-demo.css');
$list = $lp->get_toc();
$content = '';

$is_allowed_to_edit = api_is_allowed_to_edit(null, true, false, false);
if ($is_allowed_to_edit) {
    $content .= '<div style="position: fixed; top: 0px; left: 0px; pointer-events: auto;width:100%">';
    global $interbreadcrumb;
    $interbreadcrumb[] = [
        'url' => 'lp_controller.php?action=list&isStudentView=false&'.api_get_cidreq(),
        'name' => get_lang('LearningPaths'),
    ];
    $interbreadcrumb[] = [
        'url' => api_get_self()."?action=add_item&type=step&lp_id=".$lp->lp_id."&isStudentView=false&".api_get_cidreq(),
        'name' => $lp->getNameNoTags(),
    ];
    $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Preview')];
    $content .= return_breadcrumb($interbreadcrumb, null, null);
    $content .= '</div>';
}

$html = '';
$step = 1;
foreach ($list as $toc) {
    $stepId = "$step-".api_replace_dangerous_char($toc['title']);
    $x = 1000 * $step;
    $html .= '<div id="'.strtolower($stepId).'" title="'.$toc['title'].'" class="step slide" data-x="'.$x.'" data-y="-1500"  >';
    $html .= '<div class="impress-content">';
    $src = $lp->get_link('http', $toc['id']);
    if ($toc['type'] !== 'dir') {
        //just showing the src in a iframe ...
        $html .= '<h2>'.$toc['title'].'</h2>';
        $html .= '<iframe border="0" frameborder="0" src="'.$src.'"></iframe>';
    } else {
        $html .= "<div class='impress-title'>";
        $html .= '<h1>'.$toc['title'].'</h1>';
        $html .= "</div>";
    }
    $html .= "</div>";
    $html .= "</div>";
    $step++;
}

//Setting the template
$tool_name = get_lang('ViewModeImpress');
$tpl = new Template($tool_name, false, false, true);
$tpl->assign('html', $html);
$templateName = $tpl->get_template('learnpath/impress.tpl');
$content .= $tpl->fetch($templateName);
$tpl->assign('content', $content);
$tpl->display_no_layout_template();
