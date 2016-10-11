<?php
/* For licensing terms, see /license.txt */
/**
 * Show information about Mozilla OpenBadges
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @package chamilo.admin.openbadges
 */
$cidReset = true;

require_once '../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;

if (!api_is_platform_admin() || api_get_setting('allow_skills_tool') !== 'true') {
    api_not_allowed(true);
}
$backpack = 'https://backpack.openbadges.org/';

$configBackpack = api_get_setting('openbadges_backpack');
if (strcmp($backpack, $configBackpack) !== 0) {
    $backpack = $configBackpack;
}

$interbreadcrumb = array(
    array(
        'url' => api_get_path(WEB_CODE_PATH) . 'admin/index.php',
        'name' => get_lang('Administration')
    )
);

$toolbar = Display::toolbarButton(
    get_lang('ManageSkills'),
    api_get_path(WEB_CODE_PATH) . 'admin/skill_list.php',
    'list',
    'primary',
    ['title' => get_lang('ManageSkills')]
);

$tpl = new Template(get_lang('Badges'));
$tpl->assign('backpack', $backpack);

$templateName = $tpl->get_template('skill/badge.tpl');
$contentTemplate = $tpl->fetch($templateName);

$tpl->assign('actions', $toolbar);
$tpl->assign('content', $contentTemplate);
$tpl->display_one_col_template();
