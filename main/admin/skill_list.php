<?php
/* For licensing terms, see /license.txt */
/**
 * Skill list for management
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @package chamilo.admin
 */
use ChamiloSession as Session;

$cidReset = true;

require_once '../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();

if (api_get_setting('allow_skills_tool') != 'true') {
    api_not_allowed();
}

$interbreadcrumb[] = array ("url" => 'index.php', "name" => get_lang('PlatformAdmin'));

$message = Session::has('message') ? Session::read('message') : null;

/* View */
$skill = new Skill();
$skillList = $skill->get_all();

$tpl = new Template(get_lang('ManageSkills'));
$tpl->assign('message', $message);
$tpl->assign('skills', $skillList);

$content = $tpl->fetch('default/skill/list.tpl');

$tpl->assign('content', $content);
$tpl->display_one_col_template();

Session::erase('message');
