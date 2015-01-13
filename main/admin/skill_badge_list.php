<?php
/* For licensing terms, see /license.txt */
/**
 * Show information about Mozilla OpenBadges
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @package chamilo.admin.openbadges
 */
$cidReset = true;

require_once '../inc/global.inc.php';
require_once '../inc/lib/fileUpload.lib.php';

if (!api_is_platform_admin()) {
    api_not_allowed(true);
}

$this_section = SECTION_PLATFORM_ADMIN;

$objSkill = new Skill();
$skills = $objSkill->get_all();

$interbreadcrumb = array(
    array(
        'url' => api_get_path(WEB_CODE_PATH) . 'admin/index.php',
        'name' => get_lang('Administration')
    ),
    array(
        'url' => api_get_path(WEB_CODE_PATH) . 'admin/skill_badge.php',
        'name' => get_lang('Badges')
    )
);

$tpl = new Template(get_lang('Skills'));
$tpl->assign('platformAdminEmail', get_setting('emailAdministrator'));
$tpl->assign('skills', $skills);

$contentTemplate = $tpl->get_template('skill/badge_list.tpl');

$tpl->display($contentTemplate);
