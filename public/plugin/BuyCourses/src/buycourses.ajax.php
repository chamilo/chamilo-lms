<?php

declare(strict_types=1);
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CLp;

/**
 * Responses to AJAX calls.
 */
$cidReset = true;

require_once __DIR__.'/../../../main/inc/global.inc.php';

if (api_is_anonymous()) {
    api_not_allowed(true);
}

$plugin = BuyCoursesPlugin::create();
$httpRequest = Container::getRequest();

$culqiEnable = 'true' === $plugin->get('culqi_enable');
$action = $httpRequest->query->get('a');

$em = Database::getManager();

function bcEscapeHtml(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function bcRequireAdminAjax(): void
{
    if (!api_is_platform_admin()) {
        api_not_allowed(true);
    }
}

function bcUserOwnsSale(array $sale): bool
{
    return isset($sale['user_id']) && (int) $sale['user_id'] === api_get_user_id();
}

function bcUserOwnsServiceSale(array $serviceSale): bool
{
    return isset($serviceSale['buyer']['id']) && (int) $serviceSale['buyer']['id'] === api_get_user_id();
}

$adminActions = [
    'verifyPaypal',
    'saleInfo',
    'stats',
    'processPayout',
    'proceedPayout',
    'cancelPayout',
    'service_sale_info',
    'service_sale_confirm',
    'service_sale_cancel',
];

if (in_array((string) $action, $adminActions, true)) {
    bcRequireAdminAjax();
}

switch ($action) {
    case 'verifyPaypal':
        $userId = $httpRequest->request->getInt('id');
        $isUserHavePaypalAccount = $plugin->verifyPaypalAccountByBeneficiary($userId);
        if ($isUserHavePaypalAccount) {
            echo '';
        } else {
            echo '<b style="color: red; font-size: 70%;">* '.$plugin->get_lang('NoPayPalAccountDetected').'</b>';
        }

        break;

    case 'saleInfo':
        $saleId = $httpRequest->request->getInt('id');
        $sale = $plugin->getSale($saleId);
        $productType = 1 == $sale['product_type'] ? get_lang('Course') : get_lang('Session');
        $paymentType = 1 == $sale['payment_type'] ? 'Paypal' : $plugin->get_lang('BankTransfer');
        $productInfo = 1 == $sale['product_type']
            ? api_get_course_info_by_id($sale['product_id'])
            : api_get_session_info($sale['product_id']);
        $currency = $plugin->getSelectedCurrency();
        if (1 == $sale['product_type']) {
            $productImage = $productInfo['course_image_large'];
        } else {
            $productImage = ($productInfo['image'])
                ?: Template::get_icon_path('session_default.png');
        }

        $userInfo = api_get_user_info($sale['user_id']);

        $html = '<h2>'.bcEscapeHtml((string) $sale['product_name']).'</h2>';
        $html .= '<div class="row">';
        $html .= '<div class="col-sm-6 col-md-6">';
        $html .= '<ul>';
        $html .= '<li><b>'.$plugin->get_lang('OrderPrice').':</b> '.bcEscapeHtml((string) $sale['total_price']).'</li>';
        $html .= '<li><b>'.$plugin->get_lang('CurrencyType').':</b> '.bcEscapeHtml((string) $currency['iso_code']).'</li>';
        $html .= '<li><b>'.$plugin->get_lang('ProductType').':</b> '.bcEscapeHtml((string) $productType).'</li>';
        $html .= '<li><b>'.$plugin->get_lang('OrderDate').':</b> '.bcEscapeHtml((string) api_format_date(
            $sale['date'],
            DATE_TIME_FORMAT_LONG_24H
        )).'</li>';
        $html .= '<li><b>'.$plugin->get_lang('Buyer').':</b> '.bcEscapeHtml((string) $userInfo['complete_name']).'</li>';
        $html .= '<li><b>'.$plugin->get_lang('PaymentMethods').':</b> '.bcEscapeHtml((string) $paymentType).'</li>';
        $html .= '</ul>';
        $html .= '</div>';
        $html .= '<div class="col-sm-6 col-md-6">';
        $html .= '<img class="thumbnail" src="'.bcEscapeHtml((string) $productImage).'" alt="'.bcEscapeHtml((string) $sale['product_name']).'">';
        $html .= '</div>';
        $html .= '</div>';

        echo $html;

        break;

    case 'stats':
        $stats = [];
        $stats['completed_count'] = 0;
        $stats['completed_total_amount'] = 0;
        $stats['pending_count'] = 0;
        $stats['pending_total_amount'] = 0;
        $stats['canceled_count'] = 0;
        $stats['canceled_total_amount'] = 0;

        $completedPayouts = $plugin->getPayouts(BuyCoursesPlugin::PAYOUT_STATUS_COMPLETED);
        $pendingPayouts = $plugin->getPayouts(BuyCoursesPlugin::PAYOUT_STATUS_PENDING);
        $canceledPayouts = $plugin->getPayouts(BuyCoursesPlugin::PAYOUT_STATUS_CANCELED);
        $currency = $plugin->getSelectedCurrency();

        foreach ($completedPayouts as $completed) {
            $stats['completed_count'] = count($completedPayouts);
            $stats['completed_total_amount'] += $completed['commission'];
            $stats['completed_total_amount'] = number_format($stats['completed_total_amount'], 2);
        }

        foreach ($pendingPayouts as $pending) {
            $stats['pending_count'] = count($pendingPayouts);
            $stats['pending_total_amount'] += $pending['commission'];
            $stats['pending_total_amount'] = number_format($stats['pending_total_amount'], 2);
        }

        foreach ($canceledPayouts as $canceled) {
            $stats['canceled_count'] = count($canceledPayouts);
            $stats['canceled_total_amount'] += $canceled['commission'];
            $stats['canceled_total_amount'] = number_format($stats['canceled_total_amount'], 2);
        }

        $html = '<div class="row">'
            .'<p>'
            .'<ul>'
            .'<li>'.get_plugin_lang('PayoutsTotalCompleted', 'BuyCoursesPlugin').' <b>'.$stats['completed_count']
            .'</b> - '.get_plugin_lang('TotalAmount', 'BuyCoursesPlugin').' <b>'.$stats['completed_total_amount'].' '
            .$currency['iso_code'].'</b></li>'
            .'<li>'.get_plugin_lang('PayoutsTotalPending', 'BuyCoursesPlugin').' <b>'.$stats['pending_count'].'</b> - '
            .get_plugin_lang('TotalAmount', 'BuyCoursesPlugin').' <b>'.$stats['pending_total_amount'].' '
            .$currency['iso_code'].'</b></li>'
            .'<li>'.get_plugin_lang('PayoutsTotalCanceled', 'BuyCoursesPlugin').' <b>'.$stats['canceled_count']
            .'</b> - '.get_plugin_lang('TotalAmount', 'BuyCoursesPlugin').' <b>'.$stats['canceled_total_amount'].' '
            .$currency['iso_code'].'</b></li>'
            .'</ul>'
            .'</p>';
        $html .= '</div>';
        echo $html;

        break;

    case 'processPayout':
        $html = '';
        $allPays = [];
        $totalAccounts = 0;
        $totalPayout = 0;

        $payouts = $httpRequest->request->all('payouts');

        if (!$payouts) {
            echo Display::return_message(
                get_plugin_lang('SelectOptionToProceed', 'BuyCoursesPlugin'),
                'error',
                false
            );

            break;
        }

        foreach ($payouts as $index => $id) {
            $allPays[] = $plugin->getPayouts(BuyCoursesPlugin::PAYOUT_STATUS_PENDING, (int) $id);
        }

        foreach ($allPays as $payout) {
            $totalPayout += number_format($payout['commission'], 2);
            $totalAccounts++;
        }

        $currentCurrency = $plugin->getSelectedCurrency();
        $isoCode = $currentCurrency['iso_code'];
        $html .= '<p>'.get_plugin_lang('VerifyTotalAmountToProceedPayout', 'BuyCoursesPlugin').'</p>';
        $html .= ''
            .'<p>'
            .'<ul>'
            .'<li>'.get_plugin_lang('TotalAcounts', 'BuyCoursesPlugin').' <b>'.$totalAccounts.'</b></li>'
            .'<li>'.get_plugin_lang('TotalPayout', 'BuyCoursesPlugin').' <b>'.$isoCode.' '.$totalPayout.'</b></li>'
            .'</ul>'
            .'</p>';
        $html .= '<p>'.get_plugin_lang('CautionThisProcessCantBeCanceled', 'BuyCoursesPlugin').'</p>';
        $html .= '<br /><br />';
        $html .= '<div id="spinner" class="text-center"></div>';

        echo $html;

        break;

    case 'proceedPayout':
        $paypalParams = $plugin->getPaypalParams();

        $test = 1 == $paypalParams['sandbox'];
        $paypalUsername = $paypalParams['username'];
        $paypalPassword = $paypalParams['password'];
        $paypalSignature = $paypalParams['signature'];

        require_once 'paypalfunctions.php';

        $allPayouts = [];
        $totalAccounts = 0;
        $totalPayout = 0;

        $payouts = $httpRequest->request->all('payouts');

        if (!$payouts) {
            echo Display::return_message(
                get_plugin_lang('SelectOptionToProceed', 'BuyCoursesPlugin'),
                'error',
                false
            );

            break;
        }

        foreach ($payouts as $index => $id) {
            $allPayouts[] = $plugin->getPayouts(
                BuyCoursesPlugin::PAYOUT_STATUS_PENDING,
                (int) $id
            );
        }

        $currentCurrency = $plugin->getSelectedCurrency();
        $isoCode = $currentCurrency['iso_code'];
        $result = MassPayment($allPayouts, $isoCode);
        if ('Success' === $result['ACK']) {
            foreach ($allPayouts as $payout) {
                $plugin->setStatusPayouts(
                    $payout['id'],
                    BuyCoursesPlugin::PAYOUT_STATUS_COMPLETED
                );
                if ('true' === $plugin->get('invoicing_enable')) {
                    $plugin->setInvoice($payout['id']);
                }
            }

            echo Display::return_message(
                get_plugin_lang('PayoutSuccess', 'BuyCoursesPlugin'),
                'success',
                false
            );
        } else {
            echo Display::return_message(
                '<b>'.bcEscapeHtml((string) ($result['L_SEVERITYCODE0'] ?? '')).' '.bcEscapeHtml((string) ($result['L_ERRORCODE0'] ?? '')).'</b> - '
                .bcEscapeHtml((string) ($result['L_SHORTMESSAGE0'] ?? ''))
                .'<br /><ul><li>'.bcEscapeHtml((string) ($result['L_LONGMESSAGE0'] ?? '')).'</li></ul>',
                'error',
                false
            );
        }

        break;

    case 'cancelPayout':
        // $payoutId only gets used in setStatusPayout(), where it is filtered
        $payoutId = $httpRequest->request->getInt('id');
        $plugin->setStatusPayouts(
            $payoutId,
            BuyCoursesPlugin::PAYOUT_STATUS_CANCELED
        );

        echo '';

        break;

    case 'culqi_cargo':
        if (!$culqiEnable) {
            break;
        }

        $tokenId = $httpRequest->query->get(
            'token_id',
            $httpRequest->request->get('token_id')
        );
        $saleId = $httpRequest->query->get(
            'sale_id',
            $httpRequest->request->get('sale_id')
        );

        if (!$tokenId || !$saleId) {
            break;
        }
        $sale = $plugin->getSale((int) $saleId);
        if (!$sale || !bcUserOwnsSale($sale)) {
            api_not_allowed(true);
        }

        require_once 'Requests.php';
        Requests::register_autoloader();

        require_once 'culqi.php';

        $culqiParams = $plugin->getCulqiParams();

        // API Key y autenticación
        $SECRET_API_KEY = $culqiParams['api_key'];
        $culqi = new Culqi\Culqi(['api_key' => $SECRET_API_KEY]);

        $environment = $culqiParams['integration'];
        $environment = $environment
            ? BuyCoursesPlugin::CULQI_INTEGRATION_TYPE
            : BuyCoursesPlugin::CULQI_PRODUCTION_TYPE;

        $culqi->setEnv($environment);

        $user = api_get_user_info();
        $currency = $plugin->getSelectedCurrency();

        try {
            $cargo = $culqi->Cargos->create([
                'moneda' => $currency['iso_code'],
                'monto' => (int) ((float) $sale['price'] * 100),
                'usuario' => $user['username'],
                'descripcion' => $sale['product_name'],
                'pedido' => $sale['reference'],
                'codigo_pais' => 'PE',
                'direccion' => get_lang('None'),
                'ciudad' => get_lang('None'),
                'telefono' => 0,
                'nombres' => $user['firstname'],
                'apellidos' => $user['lastname'],
                'correo_electronico' => $user['email'],
                'token' => $tokenId,
            ]);

            if (is_object($cargo)) {
                $saleIsCompleted = $plugin->completeSale($sale['id']);

                if ($saleIsCompleted) {
                    Display::addFlash(
                        $plugin->getSubscriptionSuccessMessage($sale)
                    );

                    if (!empty($cargo->id)) {
                        $plugin->updateSaleGatewayTransactionId((int) $sale['id'], (string) $cargo->id);
                    }
                }
            }

            echo json_encode($cargo);
        } catch (Exception $e) {
            $cargo = json_decode($e->getMessage(), true);
            $plugin->cancelSale($sale['id']);
            unset($_SESSION['bc_sale_id']);
            if (is_array($cargo)) {
                Display::addFlash(
                    Display::return_message(
                        sprintf(get_lang('An error occurred.'), $cargo['codigo'], $cargo['mensaje']),
                        'error',
                        false
                    )
                );
            } else {
                Display::addFlash(
                    Display::return_message(
                        $plugin->get_lang('ErrorContactPlatformAdmin'),
                        'error',
                        false
                    )
                );
            }
        }

        break;

    case 'culqi_cargo_service':
        header('Content-Type: application/json');

        if (!$culqiEnable) {
            echo json_encode([
                'success' => false,
                'message' => 'Culqi is disabled.',
            ]);
            break;
        }

        $tokenId = trim((string) $httpRequest->request->get('token_id', ''));
        $serviceSaleId = $httpRequest->request->getInt('service_sale_id');

        if ('' === $tokenId || $serviceSaleId <= 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Missing Culqi token or service sale ID.',
            ]);
            break;
        }

        $serviceSale = $plugin->getServiceSale($serviceSaleId);

        if (empty($serviceSale) || !bcUserOwnsServiceSale($serviceSale)) {
            api_not_allowed(true);
        }

        if ((int) ($serviceSale['status'] ?? BuyCoursesPlugin::SERVICE_STATUS_CANCELLED) !== BuyCoursesPlugin::SERVICE_STATUS_PENDING) {
            echo json_encode([
                'success' => false,
                'message' => 'This service sale is no longer pending.',
            ]);
            break;
        }

        $culqiParams = $plugin->getCulqiParams();
        $culqiSecretKey = trim((string) ($culqiParams['api_key'] ?? ''));

        if ('' === $culqiSecretKey) {
            echo json_encode([
                'success' => false,
                'message' => 'Culqi secret key is not configured.',
            ]);
            break;
        }

        $currency = $plugin->getCurrency((int) ($serviceSale['currency_id'] ?? 0));
        $currencyCode = strtoupper(trim((string) ($currency['iso_code'] ?? 'PEN')));
        $amountInCents = (int) round((float) ($serviceSale['price'] ?? 0) * 100);

        if ($amountInCents <= 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid service sale amount.',
            ]);
            break;
        }

        $buyerId = (int) ($serviceSale['buyer']['id'] ?? 0);
        $buyerInfo = api_get_user_info($buyerId);
        $buyerEmail = trim((string) ($buyerInfo['email'] ?? ''));

        if ('' === $buyerEmail) {
            echo json_encode([
                'success' => false,
                'message' => 'Buyer email is required to process the payment.',
            ]);
            break;
        }

        $payload = [
            'amount' => $amountInCents,
            'currency_code' => $currencyCode,
            'email' => $buyerEmail,
            'source_id' => $tokenId,
            'capture' => true,
            'description' => (string) ($serviceSale['service']['name'] ?? 'Service purchase'),
            'metadata' => [
                'service_sale_id' => (string) $serviceSaleId,
                'reference' => (string) ($serviceSale['reference'] ?? ''),
            ],
        ];

        $ch = curl_init('https://api.culqi.com/v2/charges');

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Bearer '.$culqiSecretKey,
            ],
            CURLOPT_TIMEOUT => 30,
        ]);

        $rawResponse = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if (false === $rawResponse || '' !== $curlError) {
            error_log(
                'BuyCourses Culqi service charge request failed | service_sale_id='
                .$serviceSaleId
                .' | curl_error='
                .$curlError
            );

            echo json_encode([
                'success' => false,
                'message' => 'Culqi charge request failed.',
            ]);
            break;
        }

        $response = json_decode($rawResponse, true);

        if (!is_array($response)) {
            error_log(
                'BuyCourses Culqi service charge returned invalid JSON | service_sale_id='
                .$serviceSaleId
                .' | http_code='
                .$httpCode
                .' | response='
                .$rawResponse
            );

            echo json_encode([
                'success' => false,
                'message' => 'Invalid Culqi response.',
            ]);
            break;
        }

        if ($httpCode >= 200 && $httpCode < 300 && !empty($response['id'])) {
            $saleCompleted = $plugin->completeServiceSale($serviceSaleId);

            if (!$saleCompleted) {
                error_log(
                    'BuyCourses Culqi payment succeeded but service sale completion failed | service_sale_id='
                    .$serviceSaleId
                    .' | charge_id='
                    .(string) $response['id']
                );

                echo json_encode([
                    'success' => false,
                    'message' => 'The payment was processed but the service sale could not be completed.',
                ]);
                break;
            }

            $plugin->updateServiceSaleGatewayData($serviceSaleId, [
                'gateway_transaction_id' => (string) $response['id'],
            ]);

            unset($_SESSION['bc_service_sale_id'], $_SESSION['bc_coupon_id']);

            echo json_encode([
                'success' => true,
                'redirect_url' => api_get_path(WEB_PLUGIN_PATH)
                    .'BuyCourses/src/service_catalog.php?payment_status=completed&service_name='
                    .urlencode((string) ($serviceSale['service']['name'] ?? '')),
                'charge_id' => (string) $response['id'],
            ]);
            break;
        }

        error_log(
            'BuyCourses Culqi service charge failed | service_sale_id='
            .$serviceSaleId
            .' | http_code='
            .$httpCode
            .' | response='
            .$rawResponse
        );

        $errorMessage = (string) (
            $response['user_message']
            ?? $response['merchant_message']
            ?? $response['message']
            ?? 'Culqi charge failed.'
        );

        echo json_encode([
            'success' => false,
            'message' => $errorMessage,
            'response_code' => $httpCode,
        ]);
        break;

    case 'service_sale_info':
        $id = $httpRequest->request->getInt('id');
        $serviceSale = $plugin->getServiceSale($id);
        if (!$serviceSale) {
            break;
        }

        $globalParameters = $plugin->getGlobalParameters();
        $paymentTypeLabels = $plugin->getPaymentTypes();

        $nodeType = $serviceSale['node_type'];
        $nodeTypeLabel = '';
        $nodeName = '';
        switch ($nodeType) {
            case BuyCoursesPlugin::SERVICE_TYPE_USER:
                $nodeTypeLabel = get_lang('User');
                $user = api_get_user_entity($serviceSale['node_id']);
                $nodeName = $user?->getFullNameWithUsername();
                break;
            case BuyCoursesPlugin::SERVICE_TYPE_COURSE:
                $nodeTypeLabel = get_lang('Course');

                /** @var Course $course */
                $course = $em->find(Course::class, $serviceSale['node_id']);
                $nodeName = $course?->getTitle();
                break;
            case BuyCoursesPlugin::SERVICE_TYPE_SESSION:
                $nodeTypeLabel = get_lang('Session');
                $session = api_get_session_entity($serviceSale['node_id']);
                $nodeName = $session?->getTitle();
                break;
            case BuyCoursesPlugin::SERVICE_TYPE_LP_FINAL_ITEM:
                $nodeTypeLabel = get_lang('TemplateTitleCertificate');

                /** @var CLp $lp */
                $lp = $em->find(CLp::class, $serviceSale['node_id']);
                $nodeName = $lp?->getTitle();
                break;
        }

        $status = (int) $serviceSale['status'];
        $statusLabel = match ($status) {
            BuyCoursesPlugin::SERVICE_STATUS_COMPLETED => $plugin->get_lang('Active'),
            BuyCoursesPlugin::SERVICE_STATUS_PENDING => $plugin->get_lang('Pending'),
            BuyCoursesPlugin::SERVICE_STATUS_CANCELLED => $plugin->get_lang('Cancelled'),
            default => (string) $status,
        };

        $isoCode = (string) ($serviceSale['service']['currency'] ?? '');
        $priceWithoutTax = isset($serviceSale['price_without_tax']) && null !== $serviceSale['price_without_tax']
            ? (float) $serviceSale['price_without_tax']
            : (float) $serviceSale['price'];
        $taxAmount = (float) ($serviceSale['tax_amount'] ?? 0);
        $taxRate = isset($serviceSale['vat_rate']) && null !== $serviceSale['vat_rate']
            ? (float) $serviceSale['vat_rate']
            : (float) ($serviceSale['tax_perc'] ?? 0);

        $buyerId = (int) ($serviceSale['buyer']['id'] ?? 0);
        $buyerProfileUrl = $buyerId > 0
            ? api_get_path(WEB_CODE_PATH).'admin/user_information.php?user_id='.$buyerId
            : null;

        $buyerIp = trim((string) ($serviceSale['buyer_ip'] ?? ''));
        $buyerVatNumber = trim((string) ($serviceSale['buyer_vat_number'] ?? ''));
        $buyerBusinessName = trim((string) ($serviceSale['buyer_business_name'] ?? ''));
        $sellerVatNumber = trim((string) ($globalParameters['seller_vat_number'] ?? ''));
        $gatewayTransactionId = trim((string) ($serviceSale['gateway_transaction_id'] ?? ''));
        $paymentMethodLabel = (string) ($paymentTypeLabels[(int) ($serviceSale['payment_type'] ?? 0)] ?? '');

        $serviceRows = [
            ['label' => $plugin->get_lang('ServiceName'), 'value' => (string) $serviceSale['service']['name']],
            ['label' => $plugin->get_lang('Description'), 'value' => (string) $serviceSale['service']['description']],
        ];

        if (!empty($nodeName)) {
            $serviceRows[] = ['label' => $plugin->get_lang('AppliesTo'), 'value' => $nodeTypeLabel.' - '.$nodeName];
        }

        $saleRows = [
            ['label' => $plugin->get_lang('OrderReference'), 'value' => (string) ($serviceSale['reference'] ?? '')],
            ['label' => $plugin->get_lang('BoughtBy'), 'value' => (string) ($serviceSale['buyer']['name'] ?? ''), 'url' => $buyerProfileUrl],
            ['label' => $plugin->get_lang('PurchaserUser'), 'value' => (string) ($serviceSale['buyer']['username'] ?? '')],
            ['label' => get_lang('Email'), 'value' => (string) ($serviceSale['buyer']['email'] ?? '')],
        ];

        if ('' !== $buyerIp) {
            $saleRows[] = ['label' => $plugin->get_lang('BuyerIp'), 'value' => $buyerIp];
        }

        $saleRows[] = ['label' => $plugin->get_lang('OrderDate'), 'value' => (string) api_format_date($serviceSale['buy_date'], DATE_FORMAT_LONG)];

        if ($taxAmount > 0.0 || $taxRate > 0.0) {
            $saleRows[] = ['label' => $plugin->get_lang('Subtotal'), 'value' => $plugin->formatSaleAmount($priceWithoutTax, $isoCode)];
            $saleRows[] = ['label' => $plugin->get_lang('VAT'), 'value' => $plugin->formatSaleAmount($taxAmount, $isoCode).' ('.number_format($taxRate, 2).'%)'];
        }

        $saleRows[] = ['label' => $plugin->get_lang('Total'), 'value' => (string) $serviceSale['service']['total_price']];

        if ('' !== $sellerVatNumber) {
            $saleRows[] = ['label' => $plugin->get_lang('SellerVatNumber'), 'value' => $sellerVatNumber];
        }

        if ('' !== $buyerVatNumber) {
            $saleRows[] = ['label' => $plugin->get_lang('BuyerVatNumber'), 'value' => $buyerVatNumber];
        }

        if ('' !== $buyerBusinessName) {
            $saleRows[] = ['label' => $plugin->get_lang('BuyerBusinessName'), 'value' => $buyerBusinessName];
        }

        $saleRows[] = ['label' => $plugin->get_lang('PaymentMethod'), 'value' => $paymentMethodLabel];

        if ('' !== $gatewayTransactionId) {
            $saleRows[] = ['label' => $plugin->get_lang('PaymentId'), 'value' => $gatewayTransactionId];
        }

        $saleRows[] = ['label' => $plugin->get_lang('Status'), 'value' => (string) $statusLabel];

        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'imageUrl' => (string) ($serviceSale['service']['image'] ?: Template::get_icon_path('session_default.png')),
            'imageAlt' => (string) $serviceSale['service']['name'],
            'sections' => [
                ['title' => $plugin->get_lang('ServiceInformation'), 'rows' => $serviceRows],
                ['title' => $plugin->get_lang('SaleInfo'), 'rows' => $saleRows],
            ],
        ], \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES);

        break;

    case 'service_sale_confirm':
        $id = $httpRequest->request->getInt('id');
        $serviceSale = $plugin->getServiceSale($id);
        if (empty($serviceSale)) {
            echo Display::return_message($plugin->get_lang('ErrorContactPlatformAdmin'), 'error');

            break;
        }

        $response = $plugin->completeServiceSale($id);
        $html = "<div class='text-center'>";

        if ($response) {
            $html .= Display::return_message(
                sprintf($plugin->get_lang('SubscriptionToServiceXSuccessful'), bcEscapeHtml((string) ($serviceSale['service']['title'] ?? $serviceSale['service']['name']))),
                'success'
            );
        } else {
            $html .= Display::return_message($plugin->get_lang('ErrorContactPlatformAdmin'), 'error');
        }

        $html .= "<a id='finish-button' class='btn btn--primary'>".$plugin->get_lang('ClickHereToFinish').'</a>';
        $html .= '</div>';
        $html .= '<script>';
        $html .= "$('#finish-button').click(function() {";
        $html .= 'location.reload();';
        $html .= '});';
        $html .= '</script>';
        echo $html;

        break;

    case 'service_sale_cancel':
        $id = $httpRequest->request->getInt('id');
        $serviceSale = $plugin->getServiceSale($id);
        if (empty($serviceSale)) {
            echo Display::return_message($plugin->get_lang('ErrorContactPlatformAdmin'), 'error');

            break;
        }

        $response = $plugin->cancelServiceSale($id);
        $html = '';
        $html .= "<div class='text-center'>";

        if ($response) {
            $html .= Display::return_message(
                $plugin->get_lang('OrderCancelled'),
                'warning'
            );
        } else {
            $html .= Display::return_message($plugin->get_lang('ErrorContactPlatformAdmin'), 'error');
        }

        $html .= "<a id='finish-button' class='btn btn--primary'>".$plugin->get_lang('ClickHereToFinish').'</a>';
        $html .= '</div>';
        $html .= '<script>';
        $html .= "$('#finish-button').click(function() {";
        $html .= 'location.reload();';
        $html .= '});';
        $html .= '</script>';
        echo $html;

        break;
}
