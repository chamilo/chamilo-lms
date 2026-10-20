<?php

declare(strict_types=1);

/* For license terms, see /license.txt */

require_once '../config.php';
require_once __DIR__.'/btcpay_client.php';

header('Content-Type: application/json; charset=utf-8');

if ('POST' !== ($_SERVER['REQUEST_METHOD'] ?? '')) {
    header('Allow: POST');
    http_response_code(405);
    exit;
}

$plugin = BuyCoursesPlugin::create();

if ('true' !== $plugin->get('btcpay_enable') || !BuyCoursesBtcPayClient::isConfigured()) {
    http_response_code(503);
    exit;
}

$rawBody = file_get_contents('php://input');
$signature = (string) ($_SERVER['HTTP_BTCPAY_SIG'] ?? '');
$btcpay = BuyCoursesBtcPayClient::fromEnvironment();

if (!is_string($rawBody) || !$btcpay->verifyWebhook($rawBody, $signature)) {
    http_response_code(401);
    exit;
}

try {
    $event = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException) {
    http_response_code(400);
    exit;
}

if (!is_array($event)) {
    http_response_code(400);
    exit;
}

if ('InvoiceSettled' !== (string) ($event['type'] ?? '')) {
    http_response_code(200);
    echo '{"received":true}';
    exit;
}

$invoiceId = (string) ($event['invoiceId'] ?? '');
$storeId = (string) ($event['storeId'] ?? '');

if ('' === $invoiceId || $storeId !== $btcpay->getStoreId()) {
    http_response_code(400);
    exit;
}

try {
    $invoice = $btcpay->getInvoice($invoiceId);
} catch (Throwable $exception) {
    error_log('[BuyCourses][BTCPay] Failed to retrieve a webhook invoice: '.$exception->getMessage());
    http_response_code(503);
    exit;
}

if (
    $invoiceId !== (string) ($invoice['id'] ?? '')
    || $storeId !== (string) ($invoice['storeId'] ?? '')
    || 'Settled' !== (string) ($invoice['status'] ?? '')
    || 'None' !== (string) ($invoice['additionalStatus'] ?? '')
) {
    http_response_code(409);
    exit;
}

$reference = 'btcpay:'.$invoiceId;
$sale = $plugin->getSaleFromReference($reference);

if (empty($sale)) {
    http_response_code(503);
    exit;
}

$currency = $plugin->getCurrency((int) $sale['currency_id']);
$metadata = $invoice['metadata'] ?? [];

if (
    BuyCoursesPlugin::PAYMENT_TYPE_BTCPAY !== (int) $sale['payment_type']
    || !in_array(
        (int) $sale['product_type'],
        [BuyCoursesPlugin::PRODUCT_TYPE_COURSE, BuyCoursesPlugin::PRODUCT_TYPE_SESSION],
        true
    )
    || !is_array($metadata)
    || (string) $sale['id'] !== (string) ($metadata['orderId'] ?? '')
    || 'BuyCourses' !== (string) ($metadata['orderType'] ?? '')
    || strtoupper((string) ($currency['iso_code'] ?? '')) !== strtoupper((string) ($invoice['currency'] ?? ''))
    || number_format((float) $sale['price'], 2, '.', '') !== number_format((float) ($invoice['amount'] ?? 0), 2, '.', '')
) {
    http_response_code(409);
    exit;
}

$connection = Database::getManager()->getConnection();
$lockName = 'bc_btcpay_'.sha1($invoiceId);
$locked = false;
$responseStatus = 503;

try {
    $locked = '1' === (string) $connection->fetchOne('SELECT GET_LOCK(?, 5)', [$lockName]);

    if (!$locked) {
        $responseStatus = 503;
    } else {
        $sale = $plugin->getSale((int) $sale['id']);

        if (
            empty($sale)
            || $reference !== (string) $sale['reference']
            || BuyCoursesPlugin::PAYMENT_TYPE_BTCPAY !== (int) $sale['payment_type']
        ) {
            $responseStatus = 409;
        } elseif (BuyCoursesPlugin::SALE_STATUS_COMPLETED === (int) $sale['status']) {
            $responseStatus = 200;
        } elseif (BuyCoursesPlugin::SALE_STATUS_PENDING !== (int) $sale['status']) {
            $responseStatus = 409;
        } elseif ($plugin->completeSale((int) $sale['id'])) {
            $plugin->storePayouts((int) $sale['id']);
            $responseStatus = 200;
        }
    }
} catch (Throwable $exception) {
    error_log('[BuyCourses][BTCPay] Could not fulfill a settled invoice: '.$exception->getMessage());
    $responseStatus = 503;
} finally {
    if ($locked) {
        try {
            $connection->fetchOne('SELECT RELEASE_LOCK(?)', [$lockName]);
        } catch (Throwable $exception) {
            error_log('[BuyCourses][BTCPay] Could not release the invoice lock: '.$exception->getMessage());
        }
    }
}

http_response_code($responseStatus);

if (200 === $responseStatus) {
    echo '{"received":true}';
}
