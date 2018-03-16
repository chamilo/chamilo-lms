<?php
/* For license terms, see /license.txt */

/**
 * Success page for the purchase of a service in the Buy Courses plugin.
 *
 * @package chamilo.plugin.buycourses
 */
require_once '../config.php';

$plugin = BuyCoursesPlugin::create();
$paypalEnabled = $plugin->get('paypal_enable') === 'true';

if (!$paypalEnabled) {
    api_not_allowed(true);
}

$serviceSaleId = $_SESSION['bc_service_sale_id'];
$serviceSale = $plugin->getServiceSale($serviceSaleId);
$itemPrice = $serviceSale['price'];

if (empty($serviceSale)) {
    api_not_allowed(true);
}

$paypalParams = $plugin->getPaypalParams();

$pruebas = $paypalParams['sandbox'] == 1;
$paypalUsername = $paypalParams['username'];
$paypalPassword = $paypalParams['password'];
$paypalSignature = $paypalParams['signature'];

require_once "paypalfunctions.php";

$buyerInformation = GetShippingDetails(urlencode($_SESSION['TOKEN']));

$form = new FormValidator(
    'success',
    'POST',
    api_get_self(),
    null,
    null,
    FormValidator::LAYOUT_INLINE
);
$form->addButton(
    'confirm',
    $plugin->get_lang('ConfirmOrder'),
    'check',
    'success'
);
$form->addButtonCancel($plugin->get_lang('CancelOrder'), 'cancel');

if ($form->validate()) {
    $formValues = $form->getSubmitValues();
    if (isset($formValues['cancel'])) {
        $plugin->cancelServiceSale($serviceSale['id']);

        unset($_SESSION['bc_service_sale_id']);

        Display::addFlash(
            Display::return_message($plugin->get_lang('OrderCancelled'), 'error', false)
        );

        header('Location: '.api_get_path(WEB_PLUGIN_PATH).'buycourses/src/service_catalog.php');
        exit;
    }

    $confirmPayments = ConfirmPayment($itemPrice);
    if ($confirmPayments['ACK'] !== 'Success') {
        $erroMessage = vsprintf(
            $plugin->get_lang('ErrorOccurred'),
            [$expressCheckout['L_ERRORCODE0'], $confirmPayments['L_LONGMESSAGE0']]
        );
        Display::addFlash(
            Display::return_message($erroMessage, 'error', false)
        );
        unset($_SESSION['wizard']);
        header('Location: '.api_get_path(WEB_PLUGIN_PATH).'buycourses/src/service_catalog.php');
        exit;
    }

    switch ($confirmPayments["PAYMENTINFO_0_PAYMENTSTATUS"]) {
        case 'Completed':
            $serviceSaleIsCompleted = $plugin->completeServiceSale($serviceSale['id']);

            if ($serviceSaleIsCompleted) {
                Display::addFlash(
                    Display::return_message(
                        sprintf($plugin->get_lang('SubscriptionToServiceXSuccessful'), $serviceSale['service']['name']),
                        'success'
                    )
                );

                break;
            }

            Display::addFlash(
                Display::return_message($plugin->get_lang('ErrorContactPlatformAdmin'), 'error')
            );
            break;
        case 'Pending':
            switch ($confirmPayments["PAYMENTINFO_0_PENDINGREASON"]) {
                case 'address':
                    $purchaseStatus = $plugin->get_lang('PendingReasonByAddress');
                    break;
                case 'authorization':
                    $purchaseStatus = $plugin->get_lang('PendingReasonByAuthorization');
                    break;
                case 'echeck':
                    $purchaseStatus = $plugin->get_lang('PendingReasonByEcheck');
                    break;
                case 'intl':
                    $purchaseStatus = $plugin->get_lang('PendingReasonByIntl');
                    break;
                case 'multicurrency':
                    $purchaseStatus = $plugin->get_lang('PendingReasonByMulticurrency');
                    break;
                case 'order':
                    $purchaseStatus = $plugin->get_lang('PendingReasonByOrder');
                    break;
                case 'paymentreview':
                    $purchaseStatus = $plugin->get_lang('PendingReasonByPaymentReview');
                    break;
                case 'regulatoryreview':
                    $purchaseStatus = $plugin->get_lang('PendingReasonByRegulatoryReview');
                    break;
                case 'unilateral':
                    $purchaseStatus = $plugin->get_lang('PendingReasonByUnilateral');
                    break;
                case 'upgrade':
                    $purchaseStatus = $plugin->get_lang('PendingReasonByUpgrade');
                    break;
                case 'verify':
                    $purchaseStatus = $plugin->get_lang('PendingReasonByVerify');
                    break;
                case 'other':
                default:
                    $purchaseStatus = $plugin->get_lang('PendingReasonByOther');
                    break;
            }

            Display::addFlash(
                Display::return_message(
                    sprintf($plugin->get_lang('PurchaseStatusX'), $purchaseStatus),
                    'warning',
                    false
                )
            );
            break;
        default:
            $plugin->cancelServiceSale(intval($serviceSale['id']));

            Display::addFlash(
                Display::return_message($plugin->get_lang('ErrorContactPlatformAdmin'), 'error')
            );
            break;
    }

    unset($_SESSION['bc_service_sale_id']);
    header('Location: '.api_get_path(WEB_PLUGIN_PATH).'buycourses/src/service_catalog.php');
    exit;
}

$token = isset($_GET['token']) ? Security::remove_XSS($_GET['token']) : null;
if (empty($token)) {
    api_not_allowed(true);
}

$interbreadcrumb[] = [
    "url" => "service_catalog.php",
    "name" => $plugin->get_lang('ListOfServicesOnSale'),
];

$templateName = $plugin->get_lang('PaymentMethods');
$tpl = new Template($templateName);
$tpl->assign('title', $serviceSale['service']['name']);
$tpl->assign('price', $serviceSale['price']);
$tpl->assign('currency', $serviceSale['currency_id']);
$tpl->assign('service', $serviceSale);
$tpl->assign('buying_service', true);
$tpl->assign('user', api_get_user_info($serviceSale['buyer']['id']));
$tpl->assign('form', $form->returnForm());

$content = $tpl->fetch('buycourses/view/success.tpl');
$tpl->assign('content', $content);
$tpl->display_one_col_template();
