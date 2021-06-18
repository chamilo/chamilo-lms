<?php

/* For licensing terms, see /license.txt */

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

if (false === api_get_configuration_value('allow_career_diagram')) {
    api_not_allowed(true);
}

$careerId = $careerIdFromRequest = isset($_GET['id']) ? $_GET['id'] : 0;

if (empty($careerId)) {
    api_not_allowed(true);
}

$career = new Career();
$careerInfo = $career->getCareerFromId($careerId);
if (empty($careerInfo)) {
    api_not_allowed(true);
}
$careerId = $careerInfo['id'];

// Redirect to user/career_diagram.php if not admin/drh BT#18720
if (!(api_is_platform_admin() || api_is_drh())) {
    if (api_get_configuration_value('use_career_external_id_as_identifier')) {
        $careerId = Security::remove_XSS($careerIdFromRequest);
    }
    $url = api_get_path(WEB_CODE_PATH).'user/career_diagram.php?career_id='.$careerId;
    api_location($url);
}

$this_section = SECTION_PLATFORM_ADMIN;

$allowCareer = api_get_configuration_value('allow_session_admin_read_careers');
api_protect_admin_script($allowCareer);

$htmlHeadXtra[] = api_get_js('jsplumb2.js');

$career = new Career();
$careerInfo = $career->get($careerId);
if (empty($careerInfo)) {
    api_not_allowed(true);
}

// setting breadcrumbs
$interbreadcrumb[] = [
    'url' => 'index.php',
    'name' => get_lang('PlatformAdmin'),
];
$interbreadcrumb[] = [
    'url' => 'career_dashboard.php',
    'name' => get_lang('CareersAndPromotions'),
];

$interbreadcrumb[] = [
    'url' => 'careers.php',
    'name' => get_lang('Careers'),
];

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action === 'add') {
    $interbreadcrumb[] = ['url' => 'careers.php', 'name' => get_lang('Careers')];
    $toolName = get_lang('Add');
} elseif ($action === 'edit') {
    $interbreadcrumb[] = ['url' => 'careers.php', 'name' => get_lang('Careers')];
    $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Edit')];
    $toolName = get_lang('Edit');
} else {
    $toolName = get_lang('Careers');
}

$extraFieldValue = new ExtraFieldValue('career');

// Check urls
$itemUrls = $extraFieldValue->get_values_by_handler_and_field_variable(
    $careerId,
    'career_urls',
    false,
    false,
    false
);

$urlToString = '';
if (!empty($itemUrls) && !empty($itemUrls['value'])) {
    $urls = explode(',', $itemUrls['value']);
    $urlToString = '&nbsp;&nbsp;';
    if (!empty($urls)) {
        foreach ($urls as $urlData) {
            $urlData = explode('@', $urlData);
            if (isset($urlData[1])) {
                $urlToString .= Display::url($urlData[0], $urlData[1]).'&nbsp;';
            } else {
                $urlToString .= $urlData[0].'&nbsp;';
            }
        }
    }
}

$tpl = new Template(get_lang('Diagram'));
$html = Display::page_subheader2($careerInfo['name'].$urlToString);
$diagram = Career::renderDiagramByColumn($careerInfo, $tpl);

if (!empty($diagram)) {
    $html .= $diagram;
} else {
    Display::addFlash(
        Display::return_message(
            sprintf(get_lang('CareerXDoesntHaveADiagram'), $careerInfo['name']),
            'warning'
        )
    );
}

$tpl->assign('content', $html);
$layout = $tpl->get_template('career/diagram_full.tpl');
$tpl->display($layout);
