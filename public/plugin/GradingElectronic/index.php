<?php

/* For licensing terms, see /license.txt */

$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$route = $_GET['_route'] ?? '';

if (
    false === strpos($scriptName, 'gradebook/index.php')
    && false === strpos($requestUri, 'gradebook/index.php')
    && false === strpos((string) $route, 'gradebook/index.php')
) {
    return;
}

$gradingElectronic = GradingElectronicPlugin::create();

if (!$gradingElectronic->isAllowed()) {
    return;
}

$_template['show'] = true;
$_template['plugin_title'] = $gradingElectronic->get_title();

if ($gradingElectronic->isGenerateRequest()) {
    $_template['generation_result'] = $gradingElectronic->generateFromRequest();

    return;
}

$generateUrl = $gradingElectronic->getGenerateUrl();

$_template['form_html'] = $gradingElectronic->renderFormHtml(
    $generateUrl,
    'grading-electronic-template-result'
);
$_template['generate_url'] = $generateUrl;
