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

$interbreadcrumb = array(
    array(
        'url' => api_get_path(WEB_CODE_PATH) . 'admin/index.php',
        'name' => get_lang('Administration')
    )
);

$tpl = new Template(get_lang('Badges'));

$contentTemplate = $tpl->get_template('skill/badge.tpl');

$tpl->display($contentTemplate);
