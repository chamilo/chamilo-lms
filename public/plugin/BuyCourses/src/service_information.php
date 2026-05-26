<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

$cidReset = true;

require_once __DIR__.'/../../../main/inc/global.inc.php';

$serviceId = isset($_GET['service_id']) ? (int) $_GET['service_id'] : 0;
$saleId = isset($_GET['sale_id']) ? (int) $_GET['sale_id'] : 0;
$plugin = BuyCoursesPlugin::create();
$includeServices = 'true' === $plugin->get('include_services');
$currentUserId = api_get_user_id();

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
    $renderPageMessage('Service not found', 'The selected service could not be found.', 404);
}

$service = $plugin->getService($serviceId);

if (empty($service) || empty($service['id'])) {
    $renderPageMessage('Service not available', 'This service is not currently available in the catalog.', 404);
}

$isPurchasedContext = false;
$serviceSale = [];

if ($saleId > 0) {
    $serviceSale = $plugin->getServiceSale($saleId);

    if (!empty($serviceSale)) {
        $saleBelongsToCurrentUser = (int) ($serviceSale['buyer']['id'] ?? 0) === $currentUserId;
        $saleMatchesService = (int) ($serviceSale['service']['id'] ?? 0) === $serviceId;

        if (!$saleBelongsToCurrentUser && !api_is_platform_admin()) {
            api_not_allowed(true);
        }

        if ($saleMatchesService) {
            $isPurchasedContext = true;
        }
    }
}

$serviceDetailsHtml = '';
if (!empty($service['service_information'])) {
    $serviceDetailsHtml = (string) $service['service_information'];
} elseif (!empty($service['description'])) {
    $serviceDetailsHtml = (string) $service['description'];
}

$serviceImage = !empty($service['image'])
    ? (string) $service['image']
    : Template::get_icon_path('session_default.png');

$durationDays = (int) ($service['duration_days'] ?? 0);
$durationLabel = $durationDays > 0
    ? $durationDays.' '.($durationDays === 1 ? 'day' : 'days')
    : get_lang('None');

$serviceTypes = $plugin->getServiceTypes();
$appliesToLabel = $serviceTypes[(int) ($service['applies_to'] ?? 0)] ?? '';

$totalPriceFormatted = '';
if (!empty($service['total_price_formatted'])) {
    $totalPriceFormatted = (string) $service['total_price_formatted'];
} elseif (isset($service['total_price'])) {
    $totalPriceFormatted = (string) $service['total_price'];
} elseif (isset($service['price'])) {
    $totalPriceFormatted = (string) $service['price'];
}

$serviceDescription = '';
if (!empty($service['description'])) {
    $serviceDescription = strip_tags((string) $service['description']);
}

$pageUrl = api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/service_information.php?service_id='.$serviceId;
$backUrl = $isPurchasedContext
    ? api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/service_panel.php'
    : api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/service_catalog.php';

$template = new Template($service['name'] ?? $plugin->get_lang('ServiceInformation'));
$template->assign('service', $service);
$template->assign('service_sale', $serviceSale);
$template->assign('service_image', $serviceImage);
$template->assign('service_details_html', $serviceDetailsHtml);
$template->assign('service_description', $serviceDescription);
$template->assign('pageUrl', $pageUrl);
$template->assign('duration_label', $durationLabel);
$template->assign('applies_to_label', $appliesToLabel);
$template->assign('total_price_formatted', $totalPriceFormatted);
$template->assign('is_purchased_context', $isPurchasedContext);
$template->assign('back_url', $backUrl);

$content = $template->fetch('BuyCourses/view/service_information.tpl');

$template->assign('header', !empty($service['name']) ? $service['name'] : $plugin->get_lang('ServiceInformation'));
$template->assign('content', $content);
$template->display_one_col_template();
