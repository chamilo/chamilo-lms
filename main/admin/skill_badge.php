<?php
/* For licensing terms, see /license.txt */
/**
 * Show information about Mozilla OpenBadges.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();
Skill::isAllowed();

$backpack = 'https://backpack.openbadges.org/';

$configBackpack = api_get_setting('openbadges_backpack');
if (strcmp($backpack, $configBackpack) !== 0) {
    $backpack = $configBackpack;
}

$interbreadcrumb = [
    [
        'url' => api_get_path(WEB_CODE_PATH).'admin/index.php',
        'name' => get_lang('Administration'),
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

$tpl = new Template(get_lang('Badges'));
$tpl->assign('backpack', $backpack);

$templateName = $tpl->get_template('skill/badge.tpl');
$contentTemplate = $tpl->fetch($templateName);

$tpl->assign(
    'actions',
    Display::toolbarAction('toolbar', [$toolbar])
);
$tpl->assign('content', $contentTemplate);
$tpl->display_one_col_template();
