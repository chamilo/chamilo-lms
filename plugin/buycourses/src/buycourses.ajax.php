<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\UserBundle\Entity\User;

/**
 * Responses to AJAX calls.
 *
 * @package chamilo.plugin.buycourses
 */
$cidReset = true;

require_once __DIR__.'/../../../main/inc/global.inc.php';

if (api_is_anonymous()) {
    api_not_allowed(true);
}

$plugin = BuyCoursesPlugin::create();
$culqiEnable = $plugin->get('culqi_enable');
$action = isset($_GET['a']) ? $_GET['a'] : null;

$em = Database::getManager();

switch ($action) {
    case 'verifyPaypal':
        if (api_is_anonymous()) {
            break;
        }

        $userId = isset($_POST['id']) ? (int) $_POST['id'] : '';
        $isUserHavePaypalAccount = $plugin->verifyPaypalAccountByBeneficiary($userId);
        if ($isUserHavePaypalAccount) {
            echo '';
        } else {
            echo '<b style="color: red; font-size: 70%;">* '.$plugin->get_lang('NoPayPalAccountDetected').'</b>';
        }
        break;
    case 'saleInfo':
        if (api_is_anonymous()) {
            break;
        }

        $saleId = isset($_POST['id']) ? (int) $_POST['id'] : '';
        $sale = $plugin->getSale($saleId);
        $productType = $sale['product_type'] == 1 ? get_lang('Course') : get_lang('Session');
        $paymentType = $sale['payment_type'] == 1 ? 'Paypal' : $plugin->get_lang('BankTransfer');
        $productInfo = $sale['product_type'] == 1
            ? api_get_course_info_by_id($sale['product_id'])
            : api_get_session_info($sale['product_id']);
        $currency = $plugin->getSelectedCurrency();
        if ($sale['product_type'] == 1) {
            $productImage = $productInfo['course_image_large'];
        } else {
            $productImage = ($productInfo['image'])
                ? $productInfo['image']
                : Template::get_icon_path('session_default.png');
        }

        $userInfo = api_get_user_info($sale['user_id']);

        $html = '<h2>'.$sale['product_name'].'</h2>';
        $html .= '<div class="row">';
        $html .= '<div class="col-sm-6 col-md-6">';
        $html .= '<ul>';
        $html .= '<li><b>'.$plugin->get_lang('OrderPrice').':</b> '.$sale['total_price'].'</li>';
        $html .= '<li><b>'.$plugin->get_lang('CurrencyType').':</b> '.$currency['iso_code'].'</li>';
        $html .= '<li><b>'.$plugin->get_lang('ProductType').':</b> '.$productType.'</li>';
        $html .= '<li><b>'.$plugin->get_lang('OrderDate').':</b> '.
            api_format_date(
                $sale['date'],
                DATE_TIME_FORMAT_LONG_24H
            ).'</li>';
        $html .= '<li><b>'.$plugin->get_lang('Buyer').':</b> '.$userInfo['complete_name'].'</li>';
        $html .= '<li><b>'.$plugin->get_lang('PaymentMethods').':</b> '.$paymentType.'</li>';
        $html .= '</ul>';
        $html .= '</div>';
        $html .= '<div class="col-sm-6 col-md-6">';
        $html .= '<img class="thumbnail" src="'.$productImage.'" >';
        $html .= '</div>';
        $html .= '</div>';

        echo $html;
        break;
    case 'stats':
        if (api_is_anonymous()) {
            break;
        }

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
            .'<li>'.get_plugin_lang("PayoutsTotalCompleted", "BuyCoursesPlugin").' <b>'.$stats['completed_count']
            .'</b> - '.get_plugin_lang("TotalAmount", "BuyCoursesPlugin").' <b>'.$stats['completed_total_amount'].' '
            .$currency['iso_code'].'</b></li>'
            .'<li>'.get_plugin_lang("PayoutsTotalPending", "BuyCoursesPlugin").' <b>'.$stats['pending_count'].'</b> - '
            .get_plugin_lang("TotalAmount", "BuyCoursesPlugin").' <b>'.$stats['pending_total_amount'].' '
            .$currency['iso_code'].'</b></li>'
            .'<li>'.get_plugin_lang("PayoutsTotalCanceled", "BuyCoursesPlugin").' <b>'.$stats['canceled_count']
            .'</b> - '.get_plugin_lang("TotalAmount", "BuyCoursesPlugin").' <b>'.$stats['canceled_total_amount'].' '
            .$currency['iso_code'].'</b></li>'
            .'</ul>'
            .'</p>';
        $html .= '</div>';
        echo $html;
        break;
    case 'processPayout':
        if (api_is_anonymous()) {
            break;
        }

        $html = '';
        $allPays = [];
        $totalAccounts = 0;
        $totalPayout = 0;

        $payouts = isset($_POST['payouts']) ? $_POST['payouts'] : '';

        if (!$payouts) {
            echo Display::return_message(
                get_plugin_lang("SelectOptionToProceed", "BuyCoursesPlugin"),
                'error',
                false
            );
            break;
        }

        foreach ($payouts as $index => $id) {
            $allPays[] = $plugin->getPayouts(BuyCoursesPlugin::PAYOUT_STATUS_PENDING, $id);
        }

        foreach ($allPays as $payout) {
            $totalPayout += number_format($payout['commission'], 2);
            $totalAccounts++;
        }

        $currentCurrency = $plugin->getSelectedCurrency();
        $isoCode = $currentCurrency['iso_code'];
        $html .= '<p>'.get_plugin_lang("VerifyTotalAmountToProceedPayout", "BuyCoursesPlugin").'</p>';
        $html .= ''
            .'<p>'
            .'<ul>'
            .'<li>'.get_plugin_lang("TotalAcounts", "BuyCoursesPlugin").' <b>'.$totalAccounts.'</b></li>'
            .'<li>'.get_plugin_lang("TotalPayout", "BuyCoursesPlugin").' <b>'.$isoCode.' '.$totalPayout.'</b></li>'
            .'</ul>'
            .'</p>';
        $html .= '<p>'.get_plugin_lang("CautionThisProcessCantBeCanceled", "BuyCoursesPlugin").'</p>';
        $html .= '<br /><br />';
        $html .= '<div id="spinner" class="text-center"></div>';

        echo $html;
        break;
    case 'proceedPayout':
        if (api_is_anonymous()) {
            break;
        }

        $paypalParams = $plugin->getPaypalParams();

        $pruebas = $paypalParams['sandbox'] == 1;
        $paypalUsername = $paypalParams['username'];
        $paypalPassword = $paypalParams['password'];
        $paypalSignature = $paypalParams['signature'];

        require_once "paypalfunctions.php";

        $allPayouts = [];
        $totalAccounts = 0;
        $totalPayout = 0;

        $payouts = isset($_POST['payouts']) ? $_POST['payouts'] : '';

        if (!$payouts) {
            echo Display::return_message(
                get_plugin_lang("SelectOptionToProceed", "BuyCoursesPlugin"),
                'error',
                false
            );
            break;
        }

        foreach ($payouts as $index => $id) {
            $allPayouts[] = $plugin->getPayouts(
                BuyCoursesPlugin::PAYOUT_STATUS_PENDING,
                $id
            );
        }

        $currentCurrency = $plugin->getSelectedCurrency();
        $isoCode = $currentCurrency['iso_code'];
        $result = MassPayment($allPayouts, $isoCode);
        if ($result['ACK'] === 'Success') {
            foreach ($allPayouts as $payout) {
                $plugin->setStatusPayouts(
                    $payout['id'],
                    BuyCoursesPlugin::PAYOUT_STATUS_COMPLETED
                );
                if ($plugin->get('invoicing_enable') === 'true') {
                    $plugin->setInvoice($payout['id']);
                }
            }

            echo Display::return_message(
                get_plugin_lang("PayoutSuccess", "BuyCoursesPlugin"),
                'success',
                false
            );
        } else {
            echo Display::return_message(
                '<b>'.$result['L_SEVERITYCODE0'].' '.$result['L_ERRORCODE0'].'</b> - '.$result['L_SHORTMESSAGE0']
                .'<br /><ul><li>'.$result['L_LONGMESSAGE0'].'</li></ul>',
                'error',
                false
            );
        }
        break;
    case 'cancelPayout':
        if (api_is_anonymous()) {
            break;
        }

        // $payoutId only gets used in setStatusPayout(), where it is filtered
        $payoutId = isset($_POST['id']) ? $_POST['id'] : '';
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

        $tokenId = $_REQUEST['token_id'];
        $saleId = $_REQUEST['sale_id'];

        if (!$tokenId || !$saleId) {
            break;
        }
        $sale = $plugin->getSale($saleId);
        if (!$sale) {
            break;
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
                "moneda" => $currency['iso_code'],
                "monto" => intval(floatval($sale['price']) * 100),
                "usuario" => $user['username'],
                "descripcion" => $sale['product_name'],
                "pedido" => $sale['reference'],
                "codigo_pais" => "PE",
                "direccion" => get_lang('None'),
                "ciudad" => get_lang('None'),
                "telefono" => 0,
                "nombres" => $user['firstname'],
                "apellidos" => $user['lastname'],
                "correo_electronico" => $user['email'],
                "token" => $tokenId,
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
    case 'culqi_cargo_service':
        if (!$culqiEnable) {
            break;
        }

        $tokenId = $_REQUEST['token_id'];
        $serviceSaleId = $_REQUEST['service_sale_id'];

        if (!$tokenId || !$serviceSaleId) {
            break;
        }

        $serviceSale = $plugin->getServiceSale($serviceSaleId);

        if (!$serviceSale) {
            break;
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
                "moneda" => $serviceSale['currency'],
                "monto" => intval(floatval($serviceSale['price']) * 100),
                "usuario" => $user['username'],
                "descripcion" => $serviceSale['service']['name'],
                "pedido" => $serviceSale['reference'],
                "codigo_pais" => "PE",
                "direccion" => get_lang('None'),
                "ciudad" => get_lang('None'),
                "telefono" => 0,
                "nombres" => $user['firstname'],
                "apellidos" => $user['lastname'],
                "correo_electronico" => $user['email'],
                "token" => $tokenId,
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
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $serviceSale = $plugin->getServiceSale($id);
        $isAdmin = api_is_platform_admin();
        if (!$serviceSale) {
            break;
        }

        $ajaxCallFile = $plugin->getPath('SRC').'buycourses.ajax.php';
        $serviceImg = $plugin->getPath('SERVICE_IMAGES').$serviceSale['service']['image'];
        $html = "<img class='img-responsive text-center' src='$serviceImg'>";
        $html .= "<br />";
        $html .= "<legend>{$plugin->get_lang('ServiceInformation')}</legend>";
        $html .= "<ul>";
        $html .= "<li><b>{$plugin->get_lang('ServiceName')}:</b> {$serviceSale['service']['name']}</li> ";
        $html .= "<li><b>{$plugin->get_lang('Description')}:</b> {$serviceSale['service']['description']}</li> ";
        $nodeType = $serviceSale['node_type'];
        $nodeName = '';
        if ($nodeType == BuyCoursesPlugin::SERVICE_TYPE_USER) {
            $nodeType = get_lang('User');
            /** @var User $user */
            $user = UserManager::getManager()->find($serviceSale['node_id']);
            $nodeName = $user ? $user->getCompleteNameWithUsername() : null;
        } else {
            if ($nodeType == BuyCoursesPlugin::SERVICE_TYPE_COURSE) {
                $nodeType = get_lang('Course');
                /** @var Course $course */
                $course = $em->find('ChamiloCoreBundle:Course', $serviceSale['node_id']);
                $nodeName = $course ? $course->getTitle() : null;
            } else {
                if ($nodeType == BuyCoursesPlugin::SERVICE_TYPE_SESSION) {
                    $nodeType = get_lang('Session');
                    /** @var Session $session */
                    $session = $em->find('ChamiloCoreBundle:Session', $serviceSale['node_id']);
                    $nodeName = $session ? $session->getName() : null;
                } else {
                    if ($nodeType == BuyCoursesPlugin::SERVICE_TYPE_LP_FINAL_ITEM) {
                        $nodeType = get_lang('TemplateTitleCertificate');
                        /** @var CLp $lp */
                        $lp = $em->find('ChamiloCourseBundle:CLp', $serviceSale['node_id']);
                        $nodeName = $lp ? $lp->getName() : null;
                    }
                }
            }
        }

        $html .= "</ul>";
        $html .= "<legend>{$plugin->get_lang('SaleInfo')}</legend>";
        $html .= "<ul>";
        $html .= "<li><b>{$plugin->get_lang('BoughtBy')}:</b> {$serviceSale['buyer']['name']}</li> ";
        $html .= "<li><b>{$plugin->get_lang('PurchaserUser')}:</b> {$serviceSale['buyer']['username']}</li> ";
        $html .= "<li><b>{$plugin->get_lang('Total')}:</b> {$serviceSale['service']['total_price']}</li> ";
        $orderDate = api_format_date($serviceSale['buy_date'], DATE_FORMAT_LONG);
        $html .= "<li><b>{$plugin->get_lang('OrderDate')}:</b> $orderDate</li> ";
        $paymentType = $serviceSale['payment_type'];
        if ($paymentType == BuyCoursesPlugin::PAYMENT_TYPE_PAYPAL) {
            $paymentType = 'PayPal';
        } else {
            if ($paymentType == BuyCoursesPlugin::PAYMENT_TYPE_TRANSFER) {
                $paymentType = $plugin->get_lang('BankTransfer');
            } else {
                if ($paymentType == BuyCoursesPlugin::PAYMENT_TYPE_CULQI) {
                    $paymentType = 'Culqi';
                }
            }
        }
        $html .= "<li><b>{$plugin->get_lang('PaymentMethod')}:</b> $paymentType</li> ";
        $status = $serviceSale['status'];
        $buttons = '';
        if ($status == BuyCoursesPlugin::SERVICE_STATUS_COMPLETED) {
            $status = $plugin->get_lang('Active');
        } else {
            if ($status == BuyCoursesPlugin::SERVICE_STATUS_PENDING) {
                $status = $plugin->get_lang('Pending');
                if ($isAdmin) {
                    $buttons .= "<a id='{$serviceSale['id']}' tag='service_sale_confirm' class='btn btn-success pull-left'>{$plugin->get_lang('ConfirmOrder')}</a>";
                    $buttons .= "<a id='{$serviceSale['id']}' tag='service_sale_cancel' class='btn btn-danger pull-right'>{$plugin->get_lang('CancelOrder')}</a>";
                }
            } else {
                if ($status == BuyCoursesPlugin::SERVICE_STATUS_CANCELLED) {
                    $status = $plugin->get_lang('Cancelled');
                }
            }
        }
        $html .= "<li><b>{$plugin->get_lang('Status')}:</b> $status</li> ";
        $html .= "</ul>";
        $html .= "<br />";
        $html .= "<div class='row'>";
        $html .= "<div class='col-md-2'></div>";
        $html .= "<div class='col-md-8 text-center'>";
        $html .= "<div class='bc-action-buttons'>";
        $html .= $buttons;
        $html .= "</div>";
        $html .= "</div>";
        $html .= "<div class='col-md-2'></div>";
        $html .= "<script>";
        $html .= "$('.bc-action-buttons a').click(function() {";
        $html .= "var id = $(this).attr('id');";
        $html .= "var action = $(this).attr('tag');";
        $html .= "$.ajax({";
        $html .= "data: 'id='+id,";
        $html .= "url: '$ajaxCallFile?a='+action,";
        $html .= "type: 'POST',";
        $html .= "beforeSend: function() {";
        $processingLoaderText = $plugin->get_lang('ProcessingDontCloseThisWindow');
        $html .= "$('.bootbox-close-button').remove();";
        $html .= "$('.btn-default').attr('disabled', true);";
        $html .= "$('.bc-action-buttons').html('<div class=\"wobblebar-loader\"></div><p> $processingLoaderText</p>');";
        $html .= "},";
        $html .= "success: function(response) {";
        $html .= "$('.bc-action-buttons').html(response);";
        $html .= "},";
        $html .= "});";
        $html .= "});";
        $html .= "</script>";

        echo $html;
        break;
    case 'service_sale_confirm':
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $serviceSale = $plugin->getServiceSale($id);
        $response = $plugin->completeServiceSale($id);
        $html = "<div class='text-center'>";

        if ($response) {
            $html .= Display::return_message(
                sprintf($plugin->get_lang('SubscriptionToServiceXSuccessful'), $serviceSale['service']['name']),
                'success'
            );
        } else {
            $html .= Display::return_message('Error - '.$plugin->get_lang('ErrorContactPlatformAdmin'), 'error');
        }

        $html .= "<a id='finish-button' class='btn btn-primary'>".$plugin->get_lang('ClickHereToFinish')."</a>";
        $html .= "</div>";
        $html .= "<script>";
        $html .= "$('#finish-button').click(function() {";
        $html .= "location.reload();";
        $html .= "});";
        $html .= "</script>";
        echo $html;
        break;
    case 'service_sale_cancel':
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $response = $plugin->cancelServiceSale($id);
        $html = '';
        $html .= "<div class='text-center'>";

        if ($response) {
            $html .= Display::return_message(
                $plugin->get_lang('OrderCancelled'),
                'warning'
            );
        } else {
            $html .= Display::return_message('Error - '.$plugin->get_lang('ErrorContactPlatformAdmin'), 'error');
        }

        $html .= "<a id='finish-button' class='btn btn-primary'>".$plugin->get_lang('ClickHereToFinish')."</a>";
        $html .= "</div>";
        $html .= "<script>";
        $html .= "$('#finish-button').click(function() {";
        $html .= "location.reload();";
        $html .= "});";
        $html .= "</script>";
        echo $html;
        break;
}
