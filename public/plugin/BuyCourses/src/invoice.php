<?php

declare(strict_types=1);
/* For license terms, see /license.txt */

/**
 * Print invoice of the Buy Courses plugin.
 */
$cidReset = true;

require_once '../config.php';

$plugin = BuyCoursesPlugin::create();

if (api_is_anonymous()) {
    api_not_allowed(true);
}

$invoicingEnable = 'true' === $plugin->get('invoicing_enable');
if (!$invoicingEnable) {
    api_not_allowed(true, $plugin->get_lang('NoInvoiceEnable'));
}

$saleId = isset($_GET['sale_id']) ? (int) $_GET['sale_id'] : 0;
if ($saleId <= 0) {
    $saleId = isset($_GET['invoice']) ? (int) $_GET['invoice'] : 0;
}
$isService = isset($_GET['is_service']) ? (int) $_GET['is_service'] : 0;

if ($saleId <= 0 || !$plugin->canUserAccessInvoice($saleId, $isService)) {
    api_not_allowed(true);
}

function buycourses_invoice_escape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function buycourses_invoice_nl2br($value): string
{
    return nl2br(buycourses_invoice_escape($value));
}

function buycourses_invoice_label(BuyCoursesPlugin $plugin, string $key, string $fallback): string
{
    $label = $plugin->get_lang($key);

    return empty($label) || $label === $key ? $fallback : $label;
}

function buycourses_invoice_yes_no(?int $value): string
{
    if (1 === $value) {
        return 'Valid';
    }

    if (0 === $value) {
        return 'Invalid';
    }

    return 'Not checked';
}

$globalParameters = $plugin->getGlobalParameters();
$infoSale = $plugin->getDataSaleInvoice($saleId, $isService);
if (empty($infoSale)) {
    api_not_allowed(true);
}

$buyer = api_get_user_info((int) $infoSale['user_id']);
$extraUserInfoData = UserManager::get_extra_user_data((int) $infoSale['user_id']);
$infoInvoice = $plugin->getDataInvoice($saleId, $isService);
if (empty($infoInvoice)) {
    // Some service sales can be completed by asynchronous gateways/webhooks before
    // an invoice row exists. Create it lazily here when invoicing is enabled and
    // the current user is allowed to access this sale.
    $plugin->setInvoice($saleId, $isService);
    $infoInvoice = $plugin->getDataInvoice($saleId, $isService);
}

if (empty($infoInvoice)) {
    api_not_allowed(true);
}

$vatEvidence = [];
if (!empty($infoSale['vat_evidence_json'])) {
    $decodedVatEvidence = json_decode((string) $infoSale['vat_evidence_json'], true);
    if (JSON_ERROR_NONE === json_last_error() && is_array($decodedVatEvidence)) {
        $vatEvidence = $decodedVatEvidence;
    }
}
$vatInfo = $vatEvidence['vat'] ?? [];
$vatTreatment = (string) ($infoSale['vat_treatment'] ?? ($vatInfo['treatment'] ?? ''));
$invoiceNote = (string) ($vatInfo['invoice_note'] ?? '');

$taxAppliesTo = $globalParameters['tax_applies_to'];
$taxEnable = 'true' === $plugin->get('tax_enable')
    && (BuyCoursesPlugin::TAX_APPLIES_TO_ALL == $taxAppliesTo
    || (BuyCoursesPlugin::TAX_APPLIES_TO_ONLY_COURSE == $taxAppliesTo && !$isService)
    || (BuyCoursesPlugin::TAX_APPLIES_TO_ONLY_SESSION == $taxAppliesTo && $isService));

$hasVatData = $isService && (
    '' !== $vatTreatment
    || isset($infoSale['price_without_tax'])
    || isset($infoSale['vat_rate'])
    || isset($infoSale['buyer_country'])
    || !empty($vatEvidence)
);
$showTaxColumns = $taxEnable || $hasVatData;

$currency = $plugin->getCurrency((int) $infoSale['currency_id']);
$isoCode = $currency['iso_code'] ?? '';

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

// Seller and customer info.
$htmlText .= '<table width="100%">';
$htmlText .= '<tr>';
$htmlText .= '<td style="vertical-align:top; width:50%;">';
$htmlText .= '<b>'.buycourses_invoice_escape($globalParameters['seller_name']).'</b><br/>';
$htmlText .= !empty($globalParameters['seller_id']) ? buycourses_invoice_escape($globalParameters['seller_id']).'<br/>' : '';
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
$buyerAddress = $infoSale['buyer_business_address'] ?? ($extraUserInfoData['buycourses_address'] ?? '');
$htmlText .= !empty($buyerCompany) ? buycourses_invoice_escape($buyerCompany).'<br/>' : '';
$htmlText .= !empty($buyerVatNumber) ? 'VAT: '.buycourses_invoice_escape($buyerVatNumber).'<br/>' : '';
$htmlText .= !empty($buyerAddress) ? buycourses_invoice_nl2br($buyerAddress).'<br/>' : '';
$htmlText .= !empty($infoSale['buyer_postcode']) ? buycourses_invoice_escape($infoSale['buyer_postcode']).'<br/>' : '';
$htmlText .= !empty($infoSale['buyer_country']) ? buycourses_invoice_escape($infoSale['buyer_country']).'<br/>' : '';
$htmlText .= !empty($buyer['phone']) ? buycourses_invoice_escape($buyer['phone']).'<br/>' : '';
$htmlText .= !empty($buyer['email']) ? buycourses_invoice_escape($buyer['email']).'<br/>' : '';
$htmlText .= '</td>';
$htmlText .= '</tr>';
$htmlText .= '</table>';

$htmlText .= '<br><br>';
$htmlText .= '<p>';
$htmlText .= $plugin->get_lang('InvoiceDate').': <span style="font-weight:bold;">'
    .api_convert_and_format_date($infoInvoice['date_invoice'], DATE_TIME_FORMAT_LONG_24H).'</span><br>';
$htmlText .= $plugin->get_lang('InvoiceNumber').': <span style="font-weight:bold;">'
    .buycourses_invoice_escape($infoInvoice['serie'].$infoInvoice['year'].'/'.$infoInvoice['num_invoice']).'</span><br>';
$htmlText .= '</p><br><br>';

$taxName = $globalParameters['tax_name'] ?: buycourses_invoice_label($plugin, 'VAT', 'VAT');
$header = [
    $plugin->get_lang('OrderReference'),
    $plugin->get_lang('ProductType'),
];

if ($showTaxColumns) {
    $header[] = buycourses_invoice_label($plugin, 'Subtotal', 'Subtotal');
    $header[] = $taxName;
    $header[] = $plugin->get_lang('Total');
} else {
    $header[] = $plugin->get_lang('Price');
}

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

$row = [
    buycourses_invoice_escape($infoSale['reference']),
    buycourses_invoice_escape($infoSale['product_name']),
];

if ($showTaxColumns) {
    $row[] = $plugin->getPriceWithCurrencyFromIsoCode($priceWithoutTax, $isoCode);
    $row[] = $plugin->getPriceWithCurrencyFromIsoCode($taxAmount, $isoCode).' ('.number_format($taxRate, 2).'%)';
    $row[] = $totalPrice;
} else {
    $row[] = $totalPrice;
}

$data = [$row];

if ($showTaxColumns) {
    $data[] = [
        '',
        '',
        '',
        $plugin->get_lang('TotalPayout'),
        $totalPrice,
    ];
} else {
    $data[] = [
        '',
        $plugin->get_lang('TotalPayout'),
        $totalPrice,
    ];
}

$attr = [];
$attr['class'] = 'table table-hover table-striped data_table';
$attr['width'] = '100%';
$htmlText .= Display::table($header, $data, $attr);

if ($hasVatData) {
    $htmlText .= '<br><h3>'.buycourses_invoice_label($plugin, 'FiscalInformation', 'Fiscal information').'</h3>';
    $htmlText .= '<table width="100%" class="table table-hover table-striped data_table">';
    $htmlText .= '<tr><td><b>'.buycourses_invoice_label($plugin, 'VatTreatment', 'VAT treatment').'</b></td><td>'.buycourses_invoice_escape($vatTreatment).'</td></tr>';
    $htmlText .= '<tr><td><b>'.buycourses_invoice_label($plugin, 'VatRate', 'VAT rate').'</b></td><td>'.number_format($taxRate, 2).'%</td></tr>';
    $htmlText .= '<tr><td><b>'.buycourses_invoice_label($plugin, 'BuyerVatNumber', 'Buyer VAT number').'</b></td><td>'.buycourses_invoice_escape($buyerVatNumber).'</td></tr>';
    $htmlText .= '<tr><td><b>'.buycourses_invoice_label($plugin, 'BuyerVatValidation', 'Buyer VAT validation').'</b></td><td>'.buycourses_invoice_yes_no(isset($infoSale['buyer_vat_valid']) ? (null === $infoSale['buyer_vat_valid'] ? null : (int) $infoSale['buyer_vat_valid']) : null).'</td></tr>';
    if (!empty($invoiceNote)) {
        $htmlText .= '<tr><td><b>'.buycourses_invoice_label($plugin, 'InvoiceNote', 'Invoice note').'</b></td><td>'.buycourses_invoice_escape($invoiceNote).'</td></tr>';
    }
    $htmlText .= '</table>';
}

$htmlText .= '</body></html>';

$fileName = $infoInvoice['serie'].$infoInvoice['year'].'-'.$infoInvoice['num_invoice'];
$fileName = api_replace_dangerous_char($fileName);
$params = [
    'filename' => $fileName,
    'pdf_title' => $plugin->get_lang('Invoice'),
    'pdf_description' => '',
    'format' => 'A4',
    'orientation' => 'P',
];
$pdf = new PDF($params['format'], $params['orientation'], $params);
@$pdf->content_to_pdf($htmlText, '', $fileName, null, 'D', false, null, false, false, false);

exit;
