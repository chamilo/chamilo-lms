<?php
/* For licensing terms, see /license.txt */

/**
 * Show information about Mozilla OpenBadges.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 *
 * @package chamilo.admin.openbadges
 *
 * @deprecated use skill_list.php
 */
$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

api_protect_admin_script();
Skill::isAllowed();

$this_section = SECTION_PLATFORM_ADMIN;

$objSkill = new Skill();
$skills = $objSkill->get_all();

$interbreadcrumb = [
    [
        'url' => api_get_path(WEB_CODE_PATH).'admin/index.php',
        'name' => get_lang('Administration'),
    ],
    [
        'url' => api_get_path(WEB_CODE_PATH).'admin/skill_badge.php',
        'name' => get_lang('Badges'),
    ],
];

$toolbar = Display::url(
    Display::return_icon(
        'list_badges.png',
        get_lang('ManageSkills'),
        null,
        ICON_SIZE_MEDIUM
    ),
    api_get_path(WEB_CODE_PATH).'admin/skill_list.php',
    ['title' => get_lang('ManageSkills')]
);

$tpl = new Template(get_lang('Skills'));
$tpl->assign('skills', $skills);
$templateName = $tpl->get_template('skill/badge_list.tpl');
$contentTemplate = $tpl->fetch($templateName);

$tpl->assign(
    'actions',
    Display::toolbarAction('toolbar', [$toolbar])
);
$tpl->assign('content', $contentTemplate);
$tpl->display_one_col_template();
