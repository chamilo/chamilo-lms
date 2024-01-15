<?php
/* For licensing terms, see /license.txt */

/**
 * Show information about Mozilla OpenBadges.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 *
 * @deprecated use skill_list.php
 */

use Chamilo\CoreBundle\Component\Utils\ObjectIcon;

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

api_protect_admin_script();
SkillModel::isAllowed();

$this_section = SECTION_PLATFORM_ADMIN;

$objSkill = new SkillModel();
$skills = $objSkill->getAllSkills();

$interbreadcrumb = [
    [
        'url' => api_get_path(WEB_CODE_PATH).'admin/index.php',
        'name' => get_lang('Administration'),
    ],
    [
        'url' => api_get_path(WEB_CODE_PATH).'skills/skill_badge.php',
        'name' => get_lang('Badges'),
    ],
];

$toolbar = Display::url(
    Display::getMdiIcon(ObjectIcon::LIST, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Manage skills')),
    api_get_path(WEB_CODE_PATH).'skills/skill_list.php',
    ['title' => get_lang('Manage skills')]
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
