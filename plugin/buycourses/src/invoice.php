<?php
/* For license terms, see /license.txt */

use Chamilo\CoreBundle\Component\Utils\ChamiloApi;

/**
 * Print invoice of the Buy Courses plugin.
 *
 * @package chamilo.plugin.buycourses
 */
$cidReset = true;

require_once '../config.php';

api_protect_admin_script();

$plugin = BuyCoursesPlugin::create();

$invoicingEnable = $plugin->get('invoicing_enable') === 'true';
if (!$invoicingEnable) {
    api_not_allowed(true, $plugin->get_lang('NoInvoiceEnable'));
}

$saleId = isset($_GET['invoice']) ? (int) $_GET['invoice'] : 0;
$isService = isset($_GET['is_service']) ? (int) $_GET['is_service'] : 0;

$globalParameters = $plugin->getGlobalParameters();
$infoSale = $plugin->getDataSaleInvoice($saleId, $isService);
$buyer = api_get_user_info($infoSale['user_id']);
$extraUserInfoData = UserManager::get_extra_user_data($infoSale['user_id']);
$infoInvoice = $plugin->getDataInvoice($saleId, $isService);

$taxAppliesTo = $globalParameters['tax_applies_to'];
$taxEnable = $plugin->get('tax_enable') === 'true' &&
    ($taxAppliesTo == BuyCoursesPlugin::TAX_APPLIES_TO_ALL ||
    ($taxAppliesTo == BuyCoursesPlugin::TAX_APPLIES_TO_ONLY_COURSE && !$isService) ||
    ($taxAppliesTo == BuyCoursesPlugin::TAX_APPLIES_TO_ONLY_SESSION && $isService));

$htmlText = '<html>';
$htmlText .= '<link rel="stylesheet" type="text/css" href="plugin.css">';
$htmlText .= '<link rel="stylesheet" type="text/css" href="'.api_get_path(WEB_CSS_PATH).'base.css">';
$htmlText .= '<body>';

$organization = ChamiloApi::getPlatformLogo('', [], true);
// Use custom logo image.
$pdfLogo = api_get_setting('pdf_logo_header');
if ($pdfLogo === 'true') {
    $visualTheme = api_get_visual_theme();
    $img = api_get_path(SYS_CSS_PATH).'themes/'.$visualTheme.'/images/pdf_logo_header.png';
    if (file_exists($img)) {
        $organization = "<img src='$img'>";
    }
}
$htmlText .= $organization;

// Seller and customer info
$htmlText .= '<table width="100%">';
$htmlText .= '<tr>';
$htmlText .= '<td>';
$htmlText .= '<b>'.$globalParameters['seller_name'].'</b><br/>';
$htmlText .= $globalParameters['seller_id'].'<br/>';
$htmlText .= $globalParameters['seller_address'].'<br/>';
$htmlText .= $globalParameters['seller_email'].'<br/>';
$htmlText .= '</td>';
$htmlText .= '<td style="text-align:right;">';
$htmlText .= '<b>'.$buyer['complete_name'].'</b><br/>';
$htmlText .= ($extraUserInfoData['buycourses_company'] ? $extraUserInfoData['buycourses_company'].'<br>' : '');
$htmlText .= ($extraUserInfoData['buycourses_vat'] ? $extraUserInfoData['buycourses_vat'].'<br>' : '');
$htmlText .= ($extraUserInfoData['buycourses_address'] ? $extraUserInfoData['buycourses_address'].'<br/>' : '');
$htmlText .= ($buyer['phone'] ? $buyer['phone'].'<br/>' : '');
$htmlText .= ($buyer['email'] ? $buyer['email'].'<br>' : '');
$htmlText .= '</td>';
$htmlText .= '</tr>';
$htmlText .= '</table>';

$htmlText .= '<br><br>';
$htmlText .= '<p>';
$htmlText .= $plugin->get_lang('InvoiceDate').': <span style="font-weight:bold;">'
    .api_convert_and_format_date($infoInvoice['date_invoice'], DATE_TIME_FORMAT_LONG_24H).'</span><br>';
$htmlText .= $plugin->get_lang('InvoiceNumber').': <span style="font-weight:bold;">'
    .$infoInvoice['serie'].$infoInvoice['year'].'/'.$infoInvoice['num_invoice'].'</span><br>';
$htmlText .= '</p><br><br>';

$header = [
    $plugin->get_lang('OrderReference'),
    $plugin->get_lang('ProductType'),
    $plugin->get_lang('Price'),
];

if ($taxEnable) {
    $header[] = $globalParameters['tax_name'];
    $header[] = $plugin->get_lang('Total');
}

$data = [];
$row = [
    $infoSale['reference'],
    $infoSale['product_name'],
];

//var_dump($infoSale);exit;
$isoCode = $plugin->getCurrency($infoSale['currency_id'])['iso_code'];

if ($taxEnable) {
    $row[] = $plugin->getPriceWithCurrencyFromIsoCode($infoSale['price_without_tax'], $isoCode);
    $row[] = $plugin->getPriceWithCurrencyFromIsoCode($infoSale['tax_amount'], $isoCode).' ('.(int) $infoSale['tax_perc'].'%)';
}

$totalPrice = $plugin->getPriceWithCurrencyFromIsoCode(
    $infoSale['price'],
    $plugin->getCurrency($infoSale['currency_id'])['iso_code']
);

$row[] = $totalPrice;
$data[] = $row;

$totalPrice = $plugin->getPriceWithCurrencyFromIsoCode(
    $infoSale['price'],
    $plugin->getCurrency($infoSale['currency_id'])['iso_code']
);

if ($taxEnable) {
    $row = [
        '',
        '',
        '',
        $plugin->get_lang('TotalPayout'),
        $totalPrice,
    ];
} else {
    $row = [
        '',
        $plugin->get_lang('TotalPayout'),
        $totalPrice,
    ];
}
$data[] = $row;
$attr = [];
$attr['class'] = 'table table-hover table-striped data_table';
$attr['width'] = '100%';
$htmlText .= Display::table($header, $data, $attr);
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
