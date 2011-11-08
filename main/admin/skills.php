<?php
/* For licensing terms, see /license.txt */

/**
 *  @package chamilo.admin
 */

// Language files that need to be included.
$language_file = array('admin');

$cidReset = true;
require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'skill.lib.php';
require_once api_get_path(LIBRARY_PATH).'skill.visualizer.lib.php';
$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();

//Adds the JS needed to use the jqgrid
$htmlHeadXtra[] = api_get_jquery_ui_js(true);
$htmlHeadXtra[] = api_get_js('jquery.jsPlumb.all.js');
$htmlHeadXtra[] = api_get_js('skills.js');

$skill  = new Skill();
$type   = 'edit'; //edit
$tree   = $skill->get_skills_tree(null, true);
$skill_visualizer = new SkillVisualizer($tree, $type);

$html = $skill_visualizer->return_html();
$url  = api_get_path(WEB_AJAX_PATH).'skill.ajax.php?1=1';

$tpl = new Template(null, false, false);

$tpl->assign('url', $url);
$tpl->assign('html', $html);
$tpl->assign('skill_visualizer', $skill_visualizer);
$tpl->assign('js', $skill_visualizer->return_js());

//
$content = $tpl->fetch('default/skill/skill_tree.tpl');
$tpl->assign('content', $content);
$tpl->display_no_layout_template();