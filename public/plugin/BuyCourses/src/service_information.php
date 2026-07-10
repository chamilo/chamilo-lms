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

$isServiceActive = $plugin->isServiceActive($service);
if (!$isServiceActive && !$isPurchasedContext && !api_is_platform_admin()) {
    $renderPageMessage(
        $plugin->get_lang('ServiceNotFound'),
        $plugin->get_lang('ServiceInactiveForPurchase'),
        404
    );
}

$purchaseUpsaleChainBlock = $isServiceActive && !$isPurchasedContext
    ? $plugin->getCurrentUserServicePurchaseUpsaleChainBlock($serviceId)
    : null;
$upgradeOffer = $isServiceActive && !$isPurchasedContext && null === $purchaseUpsaleChainBlock
    ? $plugin->getCurrentUserServiceUpgradeOffer($serviceId)
    : null;
$plugin->applyServiceUpgradeOfferToPricing($service, $upgradeOffer);
$service['upgrade_offer'] = $upgradeOffer;
$service['is_upgrade'] = null !== $upgradeOffer;

$serviceDetailsHtml = '';
if (!empty($service['service_information'])) {
    $serviceDetailsHtml = $plugin->filterServiceMultilingualHtml((string) $service['service_information']);
} elseif (!empty($service['description'])) {
    $serviceDetailsHtml = $plugin->filterServiceMultilingualHtml((string) $service['description']);
}

$durationDays = (int) ($service['duration_days'] ?? 0);
$durationLabel = $durationDays > 0
    ? sprintf($plugin->get_lang('ServiceDurationXDays'), $durationDays)
    : get_lang('None');

$serviceTypes = $plugin->getServiceTypes();
$appliesToLabel = $serviceTypes[(int) ($service['applies_to'] ?? 0)] ?? '';

$basePriceFormatted = '';
if (!empty($service['price_formatted'])) {
    $basePriceFormatted = (string) $service['price_formatted'];
} elseif (isset($service['price'])) {
    $basePriceFormatted = $plugin->getPriceWithCurrencyFromIsoCode(
        (float) $service['price'],
        (string) ($service['iso_code'] ?? '')
    );
}

$priceDisplay = $basePriceFormatted;
if ('' !== $basePriceFormatted && !empty($service['tax_enable'])) {
    $priceDisplay = sprintf($plugin->get_lang('ServicePricePlusTax'), $basePriceFormatted);
}

$serviceDescriptionHtml = '';
$serviceDescription = '';
if (!empty($service['description'])) {
    $serviceDescriptionHtml = $plugin->filterServiceMultilingualHtml((string) $service['description']);
    $serviceDescription = $plugin->filterServiceMultilingualPlainText((string) $service['description']);
}

$canCurrentUserBuyService = $plugin->canCurrentUserBuyService($service);
$hasBlockingSale = $plugin->hasBlockingUserServiceSaleForCurrentBuyer($serviceId);
$canBuyService = $canCurrentUserBuyService
    && !$hasBlockingSale
    && null === $purchaseUpsaleChainBlock
    && (null === $upgradeOffer || !empty($upgradeOffer['purchasable']));
$buyerRoleNotice = null;
$purchaseBlockNotice = !$isServiceActive
    ? $plugin->get_lang('ServiceInactiveForPurchase')
    : (null !== $purchaseUpsaleChainBlock
        ? $plugin->formatServicePurchaseUpsaleChainBlockMessage($purchaseUpsaleChainBlock)
        : null);

if ($isServiceActive && !$canCurrentUserBuyService && !$isPurchasedContext && !$hasBlockingSale) {
    $buyerRoleNotice = $plugin->get_lang('ServicesOnlyForTeachers');
}

$pageUrl = api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/service_information.php?service_id='.$serviceId;
$backUrl = $isPurchasedContext
    ? api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/service_panel.php'
    : api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/service_catalog.php';

$template = new Template($service['name'] ?? $plugin->get_lang('ServiceInformation'));
$template->assign('service', $service);
$template->assign('service_sale', $serviceSale);
$template->assign('service_details_html', $serviceDetailsHtml);
$template->assign('service_description', $serviceDescription);
$template->assign('service_description_html', $serviceDescriptionHtml);
$template->assign('pageUrl', $pageUrl);
$template->assign('duration_label', $durationLabel);
$template->assign('applies_to_label', $appliesToLabel);
$template->assign('price_display', $priceDisplay);
$template->assign('is_purchased_context', $isPurchasedContext);
$template->assign('can_buy_service', $canBuyService);
$template->assign('has_blocking_sale', $hasBlockingSale);
$template->assign('purchase_blocked_by_active_upsale_chain', null !== $purchaseUpsaleChainBlock);
$template->assign('purchase_block_notice', $purchaseBlockNotice);
$template->assign('upgrade_offer', $upgradeOffer);
$template->assign('is_upgrade', null !== $upgradeOffer);
$template->assign('buyer_role_notice', $buyerRoleNotice);
$template->assign('back_url', $backUrl);

$content = $template->fetch('BuyCourses/view/service_information.tpl');

$template->assign('header', !empty($service['name']) ? $service['name'] : $plugin->get_lang('ServiceInformation'));
$template->assign('content', $content);
$template->display_one_col_template();
