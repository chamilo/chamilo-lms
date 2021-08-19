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

require_once __DIR__.'/../inc/global.inc.php';

if (false === api_get_configuration_value('allow_career_diagram')) {
    api_not_allowed(true);
}

api_block_anonymous_users();

$this_section = SECTION_COURSES;

$careerId = $_GET['career_id'] ?? null;
$userId = isset($_GET['user_id']) ? $_GET['user_id'] : api_get_user_id();

if (empty($careerId)) {
    api_not_allowed(true);
}

$career = new Career();
$careerInfo = $career->getCareerFromId($careerId);
if (empty($careerInfo)) {
    api_not_allowed(true);
}
$careerId = $careerInfo['id'];

/*$allow = UserManager::userHasCareer($userId, $careerId) || api_is_platform_admin() || api_is_drh();
if (false === $allow) {
    api_not_allowed(true);
}*/

$htmlHeadXtra[] = api_get_js('jsplumb2.js');
$htmlHeadXtra[] = api_get_asset('qtip2/jquery.qtip.min.js');
$htmlHeadXtra[] = api_get_css_asset('qtip2/jquery.qtip.min.css');

$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'auth/my_progress.php',
    'name' => get_lang('Progress'),
];

$interbreadcrumb[] = [
    'url' => '#',
    'name' => get_lang('Careers'),
];

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

$showFullPage = isset($_REQUEST['iframe']) && 1 === (int) $_REQUEST['iframe'] ? false : true;
$tpl = new Template(get_lang('Diagram'), $showFullPage, $showFullPage, !$showFullPage);
$html = Display::page_subheader2($careerInfo['name'].$urlToString);
$diagram = Career::renderDiagramByColumn($careerInfo, $tpl, $userId, $showFullPage);

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
if ($showFullPage) {
    $layout = $tpl->get_template('career/diagram_full.tpl');
} else {
    $layout = $tpl->get_template('career/diagram_iframe.tpl');
}

$tpl->display($layout);
