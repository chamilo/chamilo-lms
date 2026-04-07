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

$culqiEnable = $plugin->get('culqi_enable');
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

        $pruebas = 1 == $paypalParams['sandbox'];
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
        if (!$culqiEnable) {
            break;
        }

        $tokenId = $httpRequest->query->get(
            'token_id',
            $httpRequest->request->get('token_id')
        );
        $serviceSaleId = $httpRequest->query->get(
            'service_sale_id',
            $httpRequest->request->get('service_sale_id')
        );

        if (!$tokenId || !$serviceSaleId) {
            break;
        }

        $serviceSale = $plugin->getServiceSale((int) $serviceSaleId);

        if (!$serviceSale || !bcUserOwnsServiceSale($serviceSale)) {
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

        try {
            $cargo = $culqi->Cargos->create([
                'moneda' => $serviceSale['currency'],
                'monto' => (int) ((float) $serviceSale['price'] * 100),
                'usuario' => $user['username'],
                'descripcion' => $serviceSale['service']['name'],
                'pedido' => $serviceSale['reference'],
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
                $saleIsCompleted = $plugin->completeServiceSale($serviceSale['id']);
                if ($saleIsCompleted) {
                    Display::addFlash(
                        Display::return_message(
                            sprintf(
                                $plugin->get_lang('SubscriptionToCourseXSuccessful'),
                                $serviceSale['service']['name']
                            ),
                            'success'
                        )
                    );
                }
            }

            echo json_encode($cargo);
        } catch (Exception $e) {
            $cargo = json_decode($e->getMessage(), true);
            $plugin->cancelServiceSale($serviceSale['id']);

            unset($_SESSION['bc_sale_id']);

            if (is_array($cargo)) {
                Display::addFlash(
                    Display::return_message(
                        sprintf($plugin->get_lang('ErrorOccurred'), $cargo['codigo'], $cargo['mensaje']),
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

    case 'service_sale_info':
        $id = $httpRequest->request->getInt('id');
        $serviceSale = $plugin->getServiceSale($id);
        $isAdmin = api_is_platform_admin();
        if (!$serviceSale) {
            break;
        }

        $ajaxCallFile = $plugin->getPath('SRC').'buycourses.ajax.php';
        $serviceImg = $serviceSale['service']['image'] ?: Template::get_icon_path('session_default.png');
        $ajaxCallFileEscaped = bcEscapeHtml((string) $ajaxCallFile);
        $serviceImgEscaped = bcEscapeHtml((string) $serviceImg);
        $html = "<img class='img-responsive text-center' src='$serviceImgEscaped' alt='".bcEscapeHtml((string) $serviceSale['service']['name'])."'>";
        $html .= '<br />';
        $html .= "<legend>{$plugin->get_lang('ServiceInformation')}</legend>";
        $html .= '<ul>';
        $html .= '<li><b>'.$plugin->get_lang('ServiceName').':</b> '.bcEscapeHtml((string) $serviceSale['service']['name']).'</li> ';
        $html .= '<li><b>'.$plugin->get_lang('Description').':</b> '.bcEscapeHtml((string) $serviceSale['service']['description']).'</li> ';
        $nodeType = $serviceSale['node_type'];
        $nodeName = '';
        switch ($nodeType) {
            case BuyCoursesPlugin::SERVICE_TYPE_USER:
                $nodeType = get_lang('User');
                $user = api_get_user_entity($serviceSale['node_id']);
                $nodeName = $user?->getFullNameWithUsername();
                break;
            case BuyCoursesPlugin::SERVICE_TYPE_COURSE:
                $nodeType = get_lang('Course');

                /** @var Course $course */
                $course = $em->find(Course::class, $serviceSale['node_id']);
                $nodeName = $course?->getTitle();
                break;
            case BuyCoursesPlugin::SERVICE_TYPE_SESSION:
                $nodeType = get_lang('Session');
                $session = api_get_session_entity($serviceSale['node_id']);
                $nodeName = $session?->getTitle();
                break;
            case BuyCoursesPlugin::SERVICE_TYPE_LP_FINAL_ITEM:
                $nodeType = get_lang('TemplateTitleCertificate');

                /** @var CLp $lp */
                $lp = $em->find(CLp::class, $serviceSale['node_id']);
                $nodeName = $lp?->getTitle();
                break;
        }

        if (!empty($nodeName)) {
            $html .= '<li><b>'.$plugin->get_lang('AppliesTo').':</b> '.bcEscapeHtml((string) $nodeType).' - '.bcEscapeHtml((string) $nodeName).'</li> ';
        }

        $html .= '</ul>';
        $html .= "<legend>{$plugin->get_lang('SaleInfo')}</legend>";
        $html .= '<ul>';
        $html .= '<li><b>'.$plugin->get_lang('BoughtBy').':</b> '.bcEscapeHtml((string) $serviceSale['buyer']['name']).'</li> ';
        $html .= '<li><b>'.$plugin->get_lang('PurchaserUser').':</b> '.bcEscapeHtml((string) $serviceSale['buyer']['username']).'</li> ';
        $html .= '<li><b>'.$plugin->get_lang('Total').':</b> '.bcEscapeHtml((string) $serviceSale['service']['total_price']).'</li> ';
        $orderDate = api_format_date($serviceSale['buy_date'], DATE_FORMAT_LONG);
        $html .= '<li><b>'.$plugin->get_lang('OrderDate').':</b> '.bcEscapeHtml((string) $orderDate).'</li> ';
        $paymentType = match ($serviceSale['payment_type']) {
            BuyCoursesPlugin::PAYMENT_TYPE_PAYPAL => 'PayPal',
            BuyCoursesPlugin::PAYMENT_TYPE_TRANSFER => $plugin->get_lang('BankTransfer'),
            BuyCoursesPlugin::PAYMENT_TYPE_CULQI => 'Culqi',
            default => $serviceSale['payment_type'],
        };
        $html .= '<li><b>'.$plugin->get_lang('PaymentMethod').':</b> '.bcEscapeHtml((string) $paymentType).'</li> ';
        $status = $serviceSale['status'];
        $buttons = '';
        if (BuyCoursesPlugin::SERVICE_STATUS_COMPLETED == $status) {
            $status = $plugin->get_lang('Active');
        } elseif (BuyCoursesPlugin::SERVICE_STATUS_PENDING == $status) {
            $status = $plugin->get_lang('Pending');
            if ($isAdmin) {
                $buttons .= "<a id='".bcEscapeHtml((string) $serviceSale['id'])."' tag='service_sale_confirm' class='btn btn--success pull-left'>".$plugin->get_lang('ConfirmOrder')."</a>";
                $buttons .= "<a id='".bcEscapeHtml((string) $serviceSale['id'])."' tag='service_sale_cancel' class='btn btn--danger pull-right'>".$plugin->get_lang('CancelOrder')."</a>";
            }
        } elseif (BuyCoursesPlugin::SERVICE_STATUS_CANCELLED == $status) {
            $status = $plugin->get_lang('Cancelled');
        }
        $html .= '<li><b>'.$plugin->get_lang('Status').':</b> '.bcEscapeHtml((string) $status).'</li> ';
        $html .= '</ul>';
        $html .= '<br />';
        $html .= "<div class='row'>";
        $html .= "<div class='col-md-2'></div>";
        $html .= "<div class='col-md-8 text-center'>";
        $html .= "<div class='bc-action-buttons'>";
        $html .= $buttons;
        $html .= '</div>';
        $html .= '</div>';
        $html .= "<div class='col-md-2'></div>";
        $html .= '<script>';
        $html .= "$('.bc-action-buttons a').click(function() {";
        $html .= "var id = $(this).attr('id');";
        $html .= "var action = $(this).attr('tag');";
        $html .= '$.ajax({';
        $html .= "data: 'id='+id,";
        $html .= "url: '$ajaxCallFileEscaped?a='+action,";
        $html .= "type: 'POST',";
        $html .= 'beforeSend: function() {';
        $processingLoaderText = json_encode($plugin->get_lang('ProcessingDontCloseThisWindow'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
        $html .= "$('.bootbox-close-button').remove();";
        $html .= "$('.btn--plain').attr('disabled', true);";
        $html .= "$('.bc-action-buttons').html('<div class=\"wobblebar-loader\"></div><p>' + $processingLoaderText + '</p>');";
        $html .= '},';
        $html .= 'success: function(response) {';
        $html .= "$('.bc-action-buttons').html(response);";
        $html .= '},';
        $html .= '});';
        $html .= '});';
        $html .= '</script>';

        echo $html;

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
