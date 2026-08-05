<?php

declare(strict_types=1);
/* For license terms, see /license.txt */

/**
 * Print a purchase receipt for the Buy Courses plugin.
 *
 * Unlike invoice.php, this is NOT a fiscal/VAT invoice: it carries no invoice series or
 * number, and is always available regardless of the invoicing_enable setting or whether
 * the buyer requested a formal invoice. It exists so every buyer — invoiced or not — has
 * a document proving their purchase, showing the VAT amount charged, the seller's VAT ID,
 * the payment method, and (when available) the payment gateway's own transaction id.
 */
$cidReset = true;

require_once '../config.php';

require_once 'invoice_receipt_helpers.php';

$plugin = BuyCoursesPlugin::create();

if (api_is_anonymous()) {
    api_not_allowed(true);
}

$saleId = isset($_GET['sale_id']) ? (int) $_GET['sale_id'] : 0;
$isService = isset($_GET['is_service']) ? (int) $_GET['is_service'] : 0;

if ($saleId <= 0
    || !in_array($isService, [BuyCoursesPlugin::INVOICE_SOURCE_SALE, BuyCoursesPlugin::INVOICE_SOURCE_SERVICE], true)
    || !$plugin->canUserAccessInvoice($saleId, $isService)
) {
    api_not_allowed(true);
}

$globalParameters = $plugin->getGlobalParameters();
$infoSale = $plugin->getDataSaleInvoice($saleId, $isService);
if (empty($infoSale)) {
    api_not_allowed(true);
}

$buyer = api_get_user_info((int) $infoSale['user_id']);
$extraUserInfoData = UserManager::get_extra_user_data((int) $infoSale['user_id']);

$currency = $plugin->getCurrency((int) $infoSale['currency_id']);
$isoCode = $currency['iso_code'] ?? '';

$priceWithoutTax = isset($infoSale['price_without_tax']) && null !== $infoSale['price_without_tax']
    ? (float) $infoSale['price_without_tax']
    : (float) $infoSale['price'];
$taxAmount = isset($infoSale['tax_amount']) && null !== $infoSale['tax_amount']
    ? (float) $infoSale['tax_amount']
    : 0.0;
$taxRate = isset($infoSale['vat_rate']) && null !== $infoSale['vat_rate']
    ? (float) $infoSale['vat_rate']
    : (float) ($infoSale['tax_perc'] ?? 0);
$totalPrice = $plugin->getPriceWithCurrencyFromIsoCode((float) $infoSale['price'], $isoCode);

$paymentTypeLabels = $plugin->getPaymentTypes();
$paymentMethod = $paymentTypeLabels[(int) ($infoSale['payment_type'] ?? 0)] ?? buycourses_invoice_label($plugin, 'Unknown', 'Unknown');
$gatewayTransactionId = trim((string) ($infoSale['gateway_transaction_id'] ?? ''));

$htmlText = '<html>';
$htmlText .= '<link rel="stylesheet" type="text/css" href="plugin.css">';
$htmlText .= '<link rel="stylesheet" type="text/css" href="'.api_get_path(WEB_CSS_PATH).'base.css">';
$htmlText .= '<body>';

$organization = '<h2>'.buycourses_invoice_escape($globalParameters['seller_name'] ?: 'Chamilo').'</h2>';

// Use custom PDF logo image when available.
$pdfLogo = api_get_setting('pdf_logo_header');
if ('true' === $pdfLogo) {
    $visualTheme = api_get_visual_theme();
    $img = api_get_path(SYS_CSS_PATH).'themes/'.$visualTheme.'/images/pdf_logo_header.png';
    if (file_exists($img)) {
        $organization = '<img src="'.buycourses_invoice_escape($img).'" alt="" style="max-height:80px;">';
    }
}
$htmlText .= $organization;
$htmlText .= '<h3>'.buycourses_invoice_label($plugin, 'Receipt', 'Receipt').'</h3>';

// Seller and customer info (seller VAT ID is kept here even though this is not a
// fiscal invoice, so the buyer can always see who they paid).
$htmlText .= '<table width="100%">';
$htmlText .= '<tr>';
$htmlText .= '<td style="vertical-align:top; width:50%;">';
$htmlText .= '<b>'.buycourses_invoice_escape($globalParameters['seller_name']).'</b><br/>';
$htmlText .= !empty($globalParameters['seller_address']) ? buycourses_invoice_nl2br($globalParameters['seller_address']).'<br/>' : '';
$htmlText .= !empty($globalParameters['seller_postcode']) ? buycourses_invoice_escape($globalParameters['seller_postcode']).'<br/>' : '';
$htmlText .= !empty($globalParameters['seller_country']) ? buycourses_invoice_escape($globalParameters['seller_country']).'<br/>' : '';
$htmlText .= !empty($globalParameters['seller_vat_number']) ? 'VAT: '.buycourses_invoice_escape($globalParameters['seller_vat_number']).'<br/>' : '';
$htmlText .= !empty($globalParameters['seller_email']) ? buycourses_invoice_escape($globalParameters['seller_email']).'<br/>' : '';
$htmlText .= '</td>';
$htmlText .= '<td style="text-align:right; vertical-align:top; width:50%;">';
$htmlText .= '<b>'.buycourses_invoice_escape($buyer['complete_name'] ?? '').'</b><br/>';
$buyerCompany = $infoSale['buyer_business_name'] ?? ($extraUserInfoData['buycourses_company'] ?? '');
$buyerVatNumber = $infoSale['buyer_vat_number'] ?? ($extraUserInfoData['buycourses_vat'] ?? '');
$htmlText .= !empty($buyerCompany) ? buycourses_invoice_escape($buyerCompany).'<br/>' : '';
$htmlText .= !empty($buyerVatNumber) ? 'VAT: '.buycourses_invoice_escape($buyerVatNumber).'<br/>' : '';
$htmlText .= !empty($buyer['email']) ? buycourses_invoice_escape($buyer['email']).'<br/>' : '';
$htmlText .= '</td>';
$htmlText .= '</tr>';
$htmlText .= '</table>';

$htmlText .= '<br><br>';
$htmlText .= '<p>';
$htmlText .= buycourses_invoice_label($plugin, 'PurchaseDate', 'Purchase date').': <span style="font-weight:bold;">'
    .api_convert_and_format_date($infoSale['date'], DATE_TIME_FORMAT_LONG_24H).'</span><br>';
$htmlText .= buycourses_invoice_label($plugin, 'ReceiptNumber', 'Receipt number').': <span style="font-weight:bold;">'
    .buycourses_invoice_escape($infoSale['reference']).'</span><br>';
$htmlText .= buycourses_invoice_label($plugin, 'PaymentMethod', 'Payment method').': <span style="font-weight:bold;">'
    .buycourses_invoice_escape($paymentMethod).'</span><br>';
if ('' !== $gatewayTransactionId) {
    $htmlText .= buycourses_invoice_label($plugin, 'PaymentId', 'Payment ID').': <span style="font-weight:bold;">'
        .buycourses_invoice_escape($gatewayTransactionId).'</span><br>';
}
$htmlText .= '</p><br><br>';

$taxName = $globalParameters['tax_name'] ?: buycourses_invoice_label($plugin, 'VAT', 'VAT');
$header = [
    $plugin->get_lang('OrderReference'),
    $plugin->get_lang('ProductType'),
    buycourses_invoice_label($plugin, 'Subtotal', 'Subtotal'),
    $taxName,
    $plugin->get_lang('Total'),
];

$row = [
    buycourses_invoice_escape($infoSale['reference']),
    buycourses_invoice_escape($infoSale['product_name']),
    $plugin->getPriceWithCurrencyFromIsoCode($priceWithoutTax, $isoCode),
    $plugin->getPriceWithCurrencyFromIsoCode($taxAmount, $isoCode).' ('.number_format($taxRate, 2).'%)',
    $totalPrice,
];

$data = [
    $row,
    ['', '', '', $plugin->get_lang('TotalPayout'), $totalPrice],
];

$attr = [];
$attr['class'] = 'table table-hover table-striped data_table';
$attr['width'] = '100%';
$htmlText .= Display::table($header, $data, $attr);

$htmlText .= '</body></html>';

$fileName = api_replace_dangerous_char((string) $infoSale['reference']);
$params = [
    'filename' => $fileName,
    'pdf_title' => buycourses_invoice_label($plugin, 'Receipt', 'Receipt'),
    'pdf_description' => '',
    'format' => 'A4',
    'orientation' => 'P',
];
$pdf = new PDF($params['format'], $params['orientation'], $params);
@$pdf->content_to_pdf($htmlText, '', $fileName, null, 'D', false, null, false, false, false);

exit;
