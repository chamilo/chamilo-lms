<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 *  @package chamilo.admin
 */

/*
 *
 * Requires extra_field_values.value to be longtext to save diagram:
 *
UPDATE extra_field_values SET created_at = NULL WHERE CAST(created_at AS CHAR(20)) = '0000-00-00 00:00:00';
UPDATE extra_field_values SET updated_at = NULL WHERE CAST(updated_at AS CHAR(20)) = '0000-00-00 00:00:00';
ALTER TABLE extra_field_values modify column value longtext null;
*/

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

if (api_get_configuration_value('allow_career_diagram') == false) {
    api_not_allowed(true);
}

$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();

$htmlHeadXtra[] = api_get_js('jsplumb2.js');

$careerId = isset($_GET['id']) ? $_GET['id'] : 0;
if (empty($careerId)) {
    api_not_allowed(true);
}

// setting breadcrumbs
$interbreadcrumb[] = array(
    'url' => 'index.php',
    'name' => get_lang('PlatformAdmin'),
);
$interbreadcrumb[] = array(
    'url' => 'career_dashboard.php',
    'name' => get_lang('CareersAndPromotions'),
);

$interbreadcrumb[] = array(
    'url' => 'careers.php',
    'name' => get_lang('Careers'),
);

$action = isset($_GET['action']) ? $_GET['action'] : null;

$check = Security::check_token('request');
$token = Security::get_token();

if ($action == 'add') {
    $interbreadcrumb[] = array('url' => 'careers.php', 'name' => get_lang('Careers'));
    $tool_name = get_lang('Add');
} elseif ($action == 'edit') {
    $interbreadcrumb[] = array('url' => 'careers.php', 'name' => get_lang('Careers'));
    $interbreadcrumb[] = array('url' => '#', 'name' => get_lang('Edit'));
    $tool_name = get_lang('Edit');
} else {
    $tool_name = get_lang('Careers');
}



$career = new Career();
$careerInfo = $career->get($careerId);
if (empty($careerInfo)) {
    api_not_allowed(true);
}

$extraFieldValue = new ExtraFieldValue('career');
$item = $extraFieldValue->get_values_by_handler_and_field_variable(
    $careerId,
    'career_diagram',
    false,
    false,
    false
);

if (!empty($item) && isset($item['value']) && !empty($item['value'])) {
    $graph = unserialize($item['value']);
    $html = Career::renderDiagram($careerInfo, $graph);
    $tpl = new Template(get_lang('Diagram'));
    $tpl->assign('content', $html);
    $tpl->display_one_col_template();
}
