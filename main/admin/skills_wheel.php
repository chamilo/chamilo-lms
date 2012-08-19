<?php
/* For licensing terms, see /license.txt */

/**
 *  @package chamilo.admin
 */

// Language files that need to be included.
$language_file = array('admin');
$cidReset = true;

require_once '../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();

if (api_get_setting('allow_skills_tool') != 'true') {
    api_not_allowed();
}

//Adds the JS needed to use the jqgrid
$htmlHeadXtra[] = api_get_js('d3/d3.v2.min.js');
$htmlHeadXtra[] = api_get_js('d3/colorbrewer.js');
$htmlHeadXtra[] = api_get_js('d3/jquery.xcolor.js');

//$htmlHeadXtra[] = api_get_css(api_get_path(WEB_LIBRARY_PATH).'javascript/d3/colorbrewer.css');

$tpl = new Template(null, false, false);

$load_user = 0;
if (isset($_GET['load_user'])) {
    $load_user = 1;
}

$skill_id = null;
if (isset($_GET['skill_id'])) {
    $skill_id = intval($_GET['skill_id']);
}

$url = api_get_path(WEB_AJAX_PATH)."skill.ajax.php?a=get_skills_tree_json&load_user=$load_user&skill_id=$skill_id";

$tpl->assign('wheel_url', $url);

$content = $tpl->fetch('default/skill/skill_wheel.tpl');
$tpl->assign('content', $content);
$tpl->display_no_layout_template();