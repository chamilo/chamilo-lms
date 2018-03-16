<?php
/* For licensing terms, see /license.txt */

/**
 *  @package chamilo.admin
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_MYPROFILE;

api_block_anonymous_users();
Skill::isAllowed(api_get_user_id());

//Adds the JS needed to use the jqgrid
$htmlHeadXtra[] = api_get_jqgrid_js();
$htmlHeadXtra[] = api_get_js('jqueryui-touch-punch/jquery.ui.touch-punch.min.js');
$htmlHeadXtra[] = api_get_js('jquery.jsPlumb.all.js');
$htmlHeadXtra[] = api_get_js('skills.js');

$skill = new Skill();
$type = 'read'; //edit
$tree = $skill->getSkillsTree(api_get_user_id(), null, true);
$skill_visualizer = new SkillVisualizer($tree, $type);
$url = api_get_path(WEB_AJAX_PATH).'skill.ajax.php?1=1';
$tpl = new Template(null, false, false);

$tpl->assign('url', $url);
$tpl->assign('skill_visualizer', $skill_visualizer);

$template = $tpl->get_template('skill/skill_tree_student.tpl');
$content = $tpl->fetch($template);
$tpl->assign('content', $content);
$tpl->display_no_layout_template();
