<?php

declare(strict_types=1);

/* For license terms, see /license.txt */

require_once '../config.php';
require_once __DIR__.'/btcpay_client.php';

$plugin = BuyCoursesPlugin::create();
$saleId = isset($_GET['sale_id']) && is_scalar($_GET['sale_id']) ? (int) $_GET['sale_id'] : 0;
$sale = $saleId > 0 ? $plugin->getSale($saleId) : null;

if (
    'true' !== $plugin->get('btcpay_enable')
    || !BuyCoursesBtcPayClient::isConfigured($plugin)
    || empty($sale)
    || (int) $sale['user_id'] !== (int) api_get_user_id()
    || BuyCoursesPlugin::PAYMENT_TYPE_BTCPAY !== (int) $sale['payment_type']
) {
    api_not_allowed(true);
}

$redirectUrl = api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/'.(
    BuyCoursesPlugin::PRODUCT_TYPE_SESSION === (int) $sale['product_type']
        ? 'session_catalog.php'
        : 'course_catalog.php'
    );

if (BuyCoursesPlugin::SALE_STATUS_COMPLETED !== (int) $sale['status']) {
    $reference = (string) ($sale['reference'] ?? '');

    if (str_starts_with($reference, 'btcpay:')) {
        $invoiceId = substr($reference, 7);

        try {
            $btcpay = BuyCoursesBtcPayClient::fromPlugin($plugin);
            $invoice = $btcpay->getInvoice($invoiceId);
            $currency = $plugin->getCurrency((int) $sale['currency_id']);
            $currencyCode = (string) ($currency['iso_code'] ?? '');

            if (!$btcpay->invoiceBelongsToSale($invoice, $sale, $currencyCode)) {
                throw new RuntimeException('BTCPay invoice does not match the local sale.');
            }

            if ($btcpay->isInvoiceReadyForFulfillment($invoice)) {
                if ($btcpay->fulfillSale($plugin, $sale, $invoice, $currencyCode)) {
                    $sale = $plugin->getSale($saleId);
                } else {
                    Display::addFlash(
                        Display::return_message($plugin->get_lang('BtcPayNeedsReviewMessage'), 'warning', false)
                    );
                    header('Location: '.$redirectUrl);
                    exit;
                }
            } elseif ($btcpay->isInvoiceExpiredWithoutPayment($invoice)) {
                if (BuyCoursesPlugin::SALE_STATUS_PENDING === (int) $sale['status']) {
                    $plugin->cancelSale($saleId);
                }

                unset($_SESSION['bc_sale_id'], $_SESSION['bc_coupon_id']);
                Display::addFlash(
                    Display::return_message($plugin->get_lang('BtcPayExpiredMessage'), 'warning', false)
                );
                header('Location: '.$redirectUrl);
                exit;
            } elseif (!$btcpay->isInvoicePayableOrProcessing($invoice)) {
                Display::addFlash(
                    Display::return_message($plugin->get_lang('BtcPayNeedsReviewMessage'), 'warning', false)
                );
                header('Location: '.$redirectUrl);
                exit;
            }
        } catch (Throwable $exception) {
            error_log('[BuyCourses][BTCPay] Return reconciliation failed for sale '.$saleId.': '.$exception->getMessage());
        }
    }
}

if (BuyCoursesPlugin::SALE_STATUS_COMPLETED === (int) ($sale['status'] ?? 0)) {
    Display::addFlash(
        Display::return_message($plugin->get_lang('BtcPayCompletedMessage'), 'success', false)
    );
    unset($_SESSION['bc_sale_id'], $_SESSION['bc_coupon_id']);
} else {
    Display::addFlash(
        Display::return_message($plugin->get_lang('BtcPayPendingMessage'), 'info', false)
    );
}

header('Location: '.$redirectUrl);
exit;
