<?php
/* For licensing terms, see /license.txt */

/**
 *  @package chamilo.admin
 */

// Language files that need to be included.
$language_file = array('admin');
$cidReset = true;

require_once '../inc/global.inc.php';

$this_section = SECTION_SOCIAL;

if (api_get_setting('allow_skills_tool') != 'true') {
    api_not_allowed();
}

//Adds the JS needed to use the jqgrid
$htmlHeadXtra[] = api_get_js('d3/d3.v2.min.js');
$htmlHeadXtra[] = api_get_js('d3/colorbrewer.js');
$htmlHeadXtra[] = api_get_js('d3/jquery.xcolor.js');

$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/tag/jquery.fcbkcomplete.js" type="text/javascript" language="javascript"></script>';
$htmlHeadXtra[] = '<link  href="'.api_get_path(WEB_LIBRARY_PATH).'javascript/tag/style.css" rel="stylesheet" type="text/css" />';


$tpl = new Template(null, false, false);

$load_user = api_get_user_id();

$skill_condition = '';
if (isset($_GET['skill_id'])) {
    $skill_condition = '&skill_id='.intval($_GET['skill_id']);
    $tpl->assign('skill_id_to_load', $_GET['skill_id']);
}

$url = api_get_path(WEB_AJAX_PATH)."skill.ajax.php?a=get_skills_tree_json&load_user=$load_user";
$tpl->assign('wheel_url', $url);


$url  = api_get_path(WEB_AJAX_PATH).'skill.ajax.php?1=1';
$tpl->assign('url', $url);

$content = $tpl->fetch('default/skill/skill_wheel_student.tpl');
$tpl->assign('content', $content);
$tpl->display_no_layout_template();
