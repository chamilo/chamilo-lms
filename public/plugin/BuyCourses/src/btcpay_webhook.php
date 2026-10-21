<?php

declare(strict_types=1);

/* For license terms, see /license.txt */

require_once '../config.php';
require_once __DIR__.'/btcpay_client.php';

header('Content-Type: application/json; charset=utf-8');

$respond = static function (int $statusCode, bool $received = false): void {
    http_response_code($statusCode);
    if ($received) {
        echo '{"received":true}';
    }
    exit;
};

if ('POST' !== ($_SERVER['REQUEST_METHOD'] ?? '')) {
    header('Allow: POST');
    $respond(405);
}

$plugin = BuyCoursesPlugin::create();

if ('true' !== $plugin->get('btcpay_enable') || !BuyCoursesBtcPayClient::isConfigured($plugin)) {
    $respond(503);
}

$rawBody = file_get_contents('php://input');
$signature = trim((string) ($_SERVER['HTTP_BTCPAY_SIG'] ?? ''));

if ('' === $signature && function_exists('getallheaders')) {
    foreach ((array) getallheaders() as $headerName => $headerValue) {
        if ('btcpay-sig' === strtolower((string) $headerName)) {
            $signature = trim((string) $headerValue);
            break;
        }
    }
}

$btcpay = BuyCoursesBtcPayClient::fromPlugin($plugin);

if (!is_string($rawBody) || !$btcpay->verifyWebhook($rawBody, $signature)) {
    $respond(401);
}

try {
    $event = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException) {
    $respond(400);
}

if (!is_array($event)) {
    $respond(400);
}

$eventType = (string) ($event['type'] ?? '');
if (!in_array($eventType, ['InvoiceSettled', 'InvoiceExpired', 'InvoiceInvalid'], true)) {
    $respond(200, true);
}

$invoiceId = trim((string) ($event['invoiceId'] ?? ''));
$storeId = trim((string) ($event['storeId'] ?? ''));

if ('' === $invoiceId || $storeId !== $btcpay->getStoreId()) {
    $respond(400);
}

try {
    $invoice = $btcpay->getInvoice($invoiceId);
} catch (Throwable $exception) {
    error_log('[BuyCourses][BTCPay] Failed to retrieve a webhook invoice: '.$exception->getMessage());
    $respond(503);
}

if (
    $invoiceId !== (string) ($invoice['id'] ?? '')
    || $storeId !== (string) ($invoice['storeId'] ?? '')
) {
    $respond(409);
}

$metadata = $invoice['metadata'] ?? null;

// A BTCPay store can be shared with other applications. Ignore signed invoices
// that were not created by BuyCourses instead of asking BTCPay to redeliver them.
if (!is_array($metadata) || 'BuyCourses' !== (string) ($metadata['orderType'] ?? '')) {
    $respond(200, true);
}

$saleId = filter_var($metadata['orderId'] ?? null, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);

if (false === $saleId) {
    $respond(409);
}

$sale = $plugin->getSale((int) $saleId);
if (empty($sale)) {
    // Keep the delivery retryable in case the application database is temporarily
    // unavailable or the order has not become visible yet.
    $respond(503);
}

$currency = $plugin->getCurrency((int) $sale['currency_id']);
$currencyCode = (string) ($currency['iso_code'] ?? '');

if (
    BuyCoursesPlugin::PAYMENT_TYPE_BTCPAY !== (int) ($sale['payment_type'] ?? 0)
    || !in_array(
        (int) ($sale['product_type'] ?? 0),
        [BuyCoursesPlugin::PRODUCT_TYPE_COURSE, BuyCoursesPlugin::PRODUCT_TYPE_SESSION],
        true
    )
    || !$btcpay->invoiceBelongsToSale($invoice, $sale, $currencyCode)
) {
    $respond(409);
}

if ('InvoiceExpired' === $eventType) {
    if (
        $btcpay->isInvoiceExpiredWithoutPayment($invoice)
        && BuyCoursesPlugin::SALE_STATUS_PENDING === (int) ($sale['status'] ?? 0)
    ) {
        $plugin->cancelSale((int) $sale['id']);
    } else {
        error_log(
            '[BuyCourses][BTCPay] Expired invoice '.$invoiceId
            .' requires no automatic action because API status is '.(string) ($invoice['status'] ?? 'Unknown')
            .' ('.(string) ($invoice['additionalStatus'] ?? 'Unknown').').'
        );
    }

    $respond(200, true);
}

if ('InvoiceInvalid' === $eventType) {
    if (
        'Invalid' === (string) ($invoice['status'] ?? '')
        && BuyCoursesPlugin::SALE_STATUS_PENDING === (int) ($sale['status'] ?? 0)
    ) {
        $plugin->cancelSale((int) $sale['id']);
    } else {
        error_log(
            '[BuyCourses][BTCPay] Invalid invoice event for '.$invoiceId
            .' was not applied automatically because the local or remote state changed.'
        );
    }

    $respond(200, true);
}

// Fulfill only invoices whose authoritative API state is Settled. Regular
// settlements, overpayments and invoices explicitly marked as settled by a BTCPay
// store administrator are accepted. PaidLate, PaidPartial and other exceptional
// states remain in manual review.
if (!$btcpay->isInvoiceReadyForFulfillment($invoice)) {
    error_log(
        '[BuyCourses][BTCPay] Settled webhook ignored for invoice '.$invoiceId
        .' because API status is '.(string) ($invoice['status'] ?? 'Unknown')
        .' ('.(string) ($invoice['additionalStatus'] ?? 'Unknown').').'
    );
    $respond(200, true);
}

try {
    if (!$btcpay->fulfillSale($plugin, $sale, $invoice, $currencyCode)) {
        error_log('[BuyCourses][BTCPay] Could not fulfill settled invoice '.$invoiceId.'.');
        $respond(503);
    }
} catch (Throwable $exception) {
    error_log('[BuyCourses][BTCPay] Could not fulfill a settled invoice: '.$exception->getMessage());
    $respond(503);
}

$respond(200, true);
