<?php
/* For licensing terms, see /license.txt */
/**
 * Show information about Mozilla OpenBadges
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @package chamilo.admin.openbadges
 */
$cidReset = true;

require_once '../../inc/global.inc.php';
require_once '../../inc/lib/fileUpload.lib.php';

$this_section = SECTION_PLATFORM_ADMIN;

$skillId = intval($_GET['id']);

$objSkill = new Skill();
$skills = $objSkill->get_all();

$interbreadcrumb = array(
    array(
        'url' => api_get_path(WEB_CODE_PATH) . 'admin/index.php',
        'name' => get_lang('Administration')
    ),
    array(
        'url' => api_get_path(WEB_CODE_PATH) . 'admin/openbadges/index.php',
        'name' => get_lang('OpenBadges')
    )
);

$tpl = new Template('Skills');
$tpl->assign('platformAdminEmail', get_setting('emailAdministrator'));
$tpl->assign('skills', $skills);

$contentTemplate = $tpl->get_template('openbadges/list.tpl');

$tpl->display($contentTemplate);
