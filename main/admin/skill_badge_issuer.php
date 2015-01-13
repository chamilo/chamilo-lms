<?php
/* For licensing terms, see /license.txt */
/**
 * Show information about Mozilla OpenBadges
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @package chamilo.admin.openbadges
 */
$cidReset = true;

require_once '../inc/global.inc.php';

if (!api_is_platform_admin()) {
    api_not_allowed(true);
}

$backpack = 'https://backpack.openbadges.org/';

if (array_key_exists('openbadges_backpack', $_configuration)) {
    $backpack = $_configuration['openbadges_backpack'];
}

$this_section = SECTION_PLATFORM_ADMIN;

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

$tpl = new Template(get_lang('IssuerDetails'));

$tpl->assign('backpack', $backpack);

$contentTemplate = $tpl->get_template('skill/badge_issuer.tpl');

$tpl->display($contentTemplate);
