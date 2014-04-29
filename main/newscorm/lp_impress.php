<?php
/* For licensing terms, see /license.txt */

/**
*
* @package chamilo.learnpath
*/
/**
 * Code
 */

require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH) . 'banner.lib.php';

$_SESSION['whereami'] = 'lp/impress';
$this_section = SECTION_COURSES;

//To prevent the template class
$show_learnpath = true;

api_protect_course_script();

$lp_id = intval($_GET['lp_id']);

// Check if the learning path is visible for student - (LP requisites)
if (!api_is_allowed_to_edit(null, true) && !learnpath::is_lp_visible_for_student($lp_id, api_get_user_id())) {
    api_not_allowed();
}

//Checking visibility (eye icon)
$visibility = api_get_item_visibility(api_get_course_info(), TOOL_LEARNPATH, $lp_id, $action, api_get_user_id(), api_get_session_id());
if (!api_is_allowed_to_edit(null, true) && intval($visibility) == 0 ) {
     api_not_allowed();
}

if (empty($_SESSION['oLP'])) {
    api_not_allowed(true);
}

$debug = 0;

if ($debug) { error_log('------ Entering lp_impress.php -------'); }

$course_code    = api_get_course_id();
$course_id      = api_get_course_int_id();
$htmlHeadXtra[] = api_get_css(api_get_path(WEB_LIBRARY_PATH).'javascript/impress/impress-demo.css');

$list = $_SESSION['oLP']->get_toc();

$is_allowed_to_edit = api_is_allowed_to_edit(null, true, false, false);
if ($is_allowed_to_edit) {
    echo '<div style="position: fixed; top: 0px; left: 0px; pointer-events: auto;width:100%">';
    global $interbreadcrumb;
    $interbreadcrumb[] = array('url' => 'lp_controller.php?action=list&isStudentView=false', 'name' => get_lang('LearningPaths'));
    $interbreadcrumb[] = array('url' => api_get_self()."?action=add_item&type=step&lp_id=".$_SESSION['oLP']->lp_id."&isStudentView=false", 'name' => $_SESSION['oLP']->get_name());
    $interbreadcrumb[] = array('url' => '#', 'name' => get_lang('Preview'));
    echo return_breadcrumb($interbreadcrumb, null, null);
    echo '</div>';
}

$html = '';
$step = 1;
foreach ($list as $toc) {
    $x = 1000*$step;
    //data-scale="'.$step.'"
    //data-x="850" data-y="3000" data-rotate="90" data-scale="5"


    $html .= '<div id="step-'.$step.'" class="step slide" data-x="'.$x.'" data-y="-1500"  >';
    $html .= '<h2>'.$toc['title'].'</h2>';
    $src = $_SESSION['oLP']->get_link('http', $toc['id']);
    //just showing the src in a iframe ...
    $html .= '<iframe border="0" frameborder="0" style="width:100%;height:600px" src="'.$src.'"></iframe>';
    $html .= "</div>";
    $step ++;
}

//Setting the template
$tpl = new Template($tool_name, false, false, true);
$tpl->assign('html', $html);
$content = $tpl->fetch('default/learnpath/impress.tpl');
$tpl->assign('content', $content);
$tpl->display_one_col_template();