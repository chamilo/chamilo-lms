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

$this_section = SECTION_MYPROFILE;

api_block_anonymous_users();

//Adds the JS needed to use the jqgrid
$htmlHeadXtra[] = api_get_jqgrid_js();
$htmlHeadXtra[] = api_get_js('jquery.jsPlumb.all.js');
$htmlHeadXtra[] = api_get_js('skills.js');

$skill  = new Skill();
$type   = 'read'; //edit

$tree   = $skill->get_skills_tree(api_get_user_id(), true);
$skill_visualizer = new SkillVisualizer($tree, $type);

$html = $skill_visualizer->return_html();
$url  = api_get_path(WEB_AJAX_PATH).'skill.ajax.php?1=1';

$tpl = new Template(null, false, false);

$tpl->assign('url', $url);
$tpl->assign('html', $html);
$tpl->assign('skill_visualizer', $skill_visualizer);
$tpl->assign('js', $skill_visualizer->return_js());


$content = $tpl->fetch('default/skill/skill_tree_student.tpl');
$tpl->assign('content', $content);
$tpl->display_no_layout_template();