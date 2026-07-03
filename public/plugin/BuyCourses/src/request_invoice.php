<?php

declare(strict_types=1);
/* For license terms, see /license.txt */

/**
 * Let a buyer who skipped the "I want a VAT invoice" checkbox at checkout request one
 * later, within a short self-service window (see BuyCoursesPlugin::INVOICE_REQUEST_WINDOW_MONTHS).
 * Generates the invoice immediately, so its date reflects the moment of the request
 * rather than a fictitious backdated value, then redirects straight into the PDF download.
 */
$cidReset = true;

require_once '../config.php';

$plugin = BuyCoursesPlugin::create();
$currentUserId = api_get_user_id();
$panelUrl = api_get_path(WEB_PATH).'my-services';

if ($currentUserId <= 0) {
    api_not_allowed(true);
}

if ('true' !== $plugin->get('invoicing_enable')) {
    api_not_allowed(true);
}

$saleId = isset($_GET['sale_id']) ? (int) $_GET['sale_id'] : 0;
$isService = isset($_GET['is_service']) ? (int) $_GET['is_service'] : 0;

if ($saleId <= 0
    || !in_array($isService, [BuyCoursesPlugin::INVOICE_SOURCE_SALE, BuyCoursesPlugin::INVOICE_SOURCE_SERVICE], true)
    || !$plugin->canUserAccessInvoice($saleId, $isService, $currentUserId)
) {
    api_not_allowed(true);
}

$sale = $plugin->getDataSaleInvoice($saleId, $isService);

$existingInvoice = $plugin->getDataInvoice($saleId, $isService);
if (!empty($existingInvoice)) {
    header('Location: '.$plugin->getInvoiceUrl($saleId, $isService));

    exit;
}

if (!$plugin->canRequestInvoiceForSale($sale)) {
    Display::addFlash(
        Display::return_message($plugin->get_lang('InvoiceRequestWindowExpired'), 'warning', false)
    );

    header('Location: '.$panelUrl);

    exit;
}

$plugin->setInvoice($saleId, $isService);

header('Location: '.$plugin->getInvoiceUrl($saleId, $isService));

exit;
