<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

/*
 * Requires extra_field_values.field_value to be longtext to save diagram data.
 */

require_once __DIR__.'/../inc/global.inc.php';

if ('false' === api_get_setting('session.allow_career_diagram')) {
    api_not_allowed(true);
}
api_block_anonymous_users();

$this_section = SECTION_COURSES;

$careerIdentifier = isset($_GET['career_id']) ? trim((string) $_GET['career_id']) : '';
$userId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : api_get_user_id();

if ('' === $careerIdentifier) {
    api_not_allowed(true);
}

$career = new Career();
$careerInfo = $career->getCareerFromId($careerIdentifier);

if (empty($careerInfo)) {
    api_not_allowed(true);
}

$careerId = (int) $careerInfo['id'];

$allow = UserManager::userHasCareer($userId, $careerId) || api_is_platform_admin() || api_is_drh();

if (false === $allow) {
    api_not_allowed(true);
}

$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'auth/my_progress.php',
    'name' => get_lang('Progress'),
];

$interbreadcrumb[] = [
    'url' => '#',
    'name' => get_lang('Careers'),
];

$interbreadcrumb[] = [
    'url' => '#',
    'name' => get_lang('Diagram'),
];

$extraFieldValue = new ExtraFieldValue('career');

$itemUrls = $extraFieldValue->get_values_by_handler_and_field_variable(
    $careerId,
    'career_urls',
    false,
    false,
    false
);

$urlToString = '';

if (!empty($itemUrls) && !empty($itemUrls['value'])) {
    $urls = explode(',', (string) $itemUrls['value']);
    $urlToString = '&nbsp;&nbsp;';

    foreach ($urls as $urlData) {
        $urlData = explode('@', $urlData);

        if (isset($urlData[1])) {
            $urlToString .= Display::url($urlData[0], $urlData[1]).'&nbsp;';

            continue;
        }

        $urlToString .= $urlData[0].'&nbsp;';
    }
}

$showFullPage = !(isset($_REQUEST['iframe']) && 1 === (int) $_REQUEST['iframe']);

$tpl = new Template(get_lang('Diagram'), $showFullPage, $showFullPage, !$showFullPage);

$careerTitle = $careerInfo['title'] ?? $careerInfo['name'] ?? '';

$html = Display::page_subheader2($careerTitle.$urlToString);

$diagram = Career::renderDiagramByColumn($careerInfo, $tpl, $userId);

if (!empty($diagram)) {
    $html .= $diagram;
} else {
    Display::addFlash(
        Display::return_message(
            sprintf(get_lang('Career %s doesn\'t have a diagram.'), $careerTitle),
            'warning'
        )
    );
}

$tpl->assign('content', $html);

$layout = $showFullPage
    ? Template::findTemplateFilePath('career/diagram_full.html.twig')
    : Template::findTemplateFilePath('career/diagram_iframe.html.twig');

$tpl->display($layout);
