<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

/**
 * Service information page.
 */
$cidReset = true;

require_once __DIR__.'/../../../main/inc/global.inc.php';

$serviceId = isset($_GET['service_id']) ? (int) $_GET['service_id'] : 0;
$plugin = BuyCoursesPlugin::create();
$includeServices = 'true' === $plugin->get('include_services');

$renderPageMessage = static function (string $title, string $message, int $statusCode = 200): void {
    http_response_code($statusCode);

    $safeTitle = Security::remove_XSS($title);
    $safeMessage = Security::remove_XSS($message);

    $template = new Template($safeTitle);
    $content = '
        <div class="mx-auto w-full max-w-screen-lg px-4 py-8 sm:px-6 lg:px-8">
            <div class="rounded-2xl border border-gray-25 bg-white p-6 shadow-sm">
                <h1 class="text-xl font-semibold text-gray-90">'.$safeTitle.'</h1>
                <p class="mt-3 text-sm leading-6 text-gray-50">'.$safeMessage.'</p>
            </div>
        </div>
    ';

    $template->assign('header', $safeTitle);
    $template->assign('content', $content);
    $template->display_one_col_template();
    exit;
};

if (!$includeServices) {
    api_not_allowed(true);
}

if ($serviceId <= 0) {
    $renderPageMessage(
        'Service not found',
        'The selected service could not be found.',
        404
    );
}

$service = $plugin->getService($serviceId);

if (empty($service) || empty($service['id'])) {
    $renderPageMessage(
        'Service not available',
        'This service is not currently available in the catalog.',
        404
    );
}

$serviceDetailsHtml = '';
if (!empty($service['service_information'])) {
    $serviceDetailsHtml = (string) $service['service_information'];
} elseif (!empty($service['description'])) {
    $serviceDetailsHtml = (string) $service['description'];
}

$essence = null;
if (!empty($service['video_url']) && class_exists(\Essence\Essence::class)) {
    $essence = new \Essence\Essence();
}

$pageUrl = api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/service_information.php?service_id='.$serviceId;

$template = new Template($service['name'] ?? $plugin->get_lang('ServiceInformation'));
$template->assign('service', $service);
$template->assign('service_details_html', $serviceDetailsHtml);
$template->assign('pageUrl', $pageUrl);
$template->assign('essence', $essence);

$content = $template->fetch('BuyCourses/view/service_information.tpl');

$template->assign(
    'header',
    !empty($service['name']) ? $service['name'] : $plugin->get_lang('ServiceInformation')
);
$template->assign('content', $content);
$template->display_one_col_template();
