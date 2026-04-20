<?php

declare(strict_types=1);

/* For license terms, see /license.txt */

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CLp;
use ChamiloSession as Session;

/**
 * Process purchase confirmation script for the Buy Courses plugin.
 */
$cidReset = true;

require_once '../config.php';

$plugin = BuyCoursesPlugin::create();
$serviceSaleId = (int) Session::read('bc_service_sale_id');
$couponId = (int) Session::read('bc_coupon_id');

if (empty($serviceSaleId)) {
    api_not_allowed(true);
}

$serviceSale = $plugin->getServiceSale($serviceSaleId);

if (empty($serviceSale)) {
    api_not_allowed(true);
}

$userInfo = api_get_user_info($serviceSale['buyer']['id']);

if (!empty($couponId)) {
    $coupon = $plugin->getCouponService($couponId, $serviceSale['service_id']);

    if (!empty($coupon)) {
        $serviceSale['item'] = $plugin->getService($serviceSale['service_id'], $coupon);
    }
}

if (empty($serviceSale['item'])) {
    $serviceSale['item'] = $plugin->getService($serviceSale['service_id']);
}

$service = $serviceSale['service'];
$serviceItem = $serviceSale['item'];
$currency = $plugin->getCurrency($serviceSale['currency_id']);
$globalParameters = $plugin->getGlobalParameters();
$terms = $globalParameters['terms_and_conditions'] ?? '';
$catalogUrl = api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/service_catalog.php';
$templateName = $plugin->get_lang('PaymentMethods');

$interbreadcrumb[] = [
    'url' => 'service_catalog.php',
    'name' => $plugin->get_lang('ListOfServicesOnSale'),
];

switch ($serviceSale['payment_type']) {
    case BuyCoursesPlugin::PAYMENT_TYPE_PAYPAL:
        $paypalParams = $plugin->getPaypalParams();

        $isSandbox = !empty($paypalParams['sandbox']) && 1 == (int) $paypalParams['sandbox'];
        $paypalUsername = trim((string) ($paypalParams['username'] ?? ''));
        $paypalPassword = trim((string) ($paypalParams['password'] ?? ''));
        $paypalSignature = trim((string) ($paypalParams['signature'] ?? ''));

        if ('' === $paypalUsername || '' === $paypalPassword || '' === $paypalSignature) {
            Display::addFlash(
                Display::return_message(
                    $plugin->get_lang('PayPalCredentialsIncomplete'),
                    'error',
                    false
                )
            );

            $plugin->cancelServiceSale($serviceSale['id']);
            header('Location: '.$catalogUrl);
            exit;
        }

        $itemPrice = (float) $serviceSale['price'];
        $returnUrl = api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/service_success.php';
        $cancelUrl = api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/service_error.php';
        $extra = '';

        require_once 'paypalfunctions.php';

        $extra .= '&L_PAYMENTREQUEST_0_NAME0='.urlencode((string) $serviceSale['service']['name']);
        $extra .= '&L_PAYMENTREQUEST_0_QTY0=1';
        $extra .= '&L_PAYMENTREQUEST_0_AMT0='.urlencode(number_format($itemPrice, 2, '.', ''));

        $expressCheckout = CallShortcutExpressCheckout(
            $itemPrice,
            (string) $currency['iso_code'],
            'Sale',
            $returnUrl,
            $cancelUrl,
            $extra
        );

        $ack = strtoupper((string) ($expressCheckout['ACK'] ?? ''));

        if ('SUCCESS' !== $ack && 'SUCCESSWITHWARNING' !== $ack) {
            $errorCode = $expressCheckout['L_ERRORCODE0'] ?? 'Unknown';
            $errorMessage = $expressCheckout['L_LONGMESSAGE0'] ?? 'Unknown PayPal error';

            error_log(
                'BuyCourses SetExpressCheckout failed for service sale '.
                (int) $serviceSale['id'].
                ' | code='.$errorCode.
                ' | message='.$errorMessage
            );

            Display::addFlash(
                Display::return_message(
                    'PayPal checkout could not be initialized. Code: '.$errorCode.' | Message: '.$errorMessage,
                    'error',
                    false
                )
            );

            $plugin->cancelServiceSale($serviceSale['id']);
            header('Location: '.$catalogUrl);
            exit;
        }

        if (empty($expressCheckout['TOKEN'])) {
            error_log('BuyCourses SetExpressCheckout succeeded without token for service sale '.(int) $serviceSale['id']);

            Display::addFlash(
                Display::return_message(
                    'PayPal checkout could not be initialized because no token was returned.',
                    'error',
                    false
                )
            );

            $plugin->cancelServiceSale($serviceSale['id']);
            header('Location: '.$catalogUrl);
            exit;
        }

        /*
         * Do not send sale notification here.
         * The payment is not completed yet at this stage.
         */

        RedirectToPayPal((string) $expressCheckout['TOKEN']);
        exit;

    case BuyCoursesPlugin::PAYMENT_TYPE_TRANSFER:
        $transferAccounts = $plugin->getTransferAccounts();

        if ('POST' === $_SERVER['REQUEST_METHOD']) {
            $action = trim((string) ($_POST['action'] ?? ''));

            if ('cancel' === $action) {
                $plugin->cancelServiceSale($serviceSale['id']);

                unset($_SESSION['bc_service_sale_id'], $_SESSION['bc_coupon_id']);

                Display::addFlash(
                    Display::return_message($plugin->get_lang('OrderCancelled'), 'error', false)
                );

                header('Location: '.$catalogUrl);
                exit;
            }

            if ('confirm' === $action) {
                $messageTemplate = new Template();
                $messageTemplate->assign(
                    'service_sale',
                    [
                        'name' => $serviceSale['service']['name'],
                        'buyer' => $serviceSale['buyer']['name'],
                        'buy_date' => $serviceSale['buy_date'],
                        'date_start' => $serviceSale['date_start'],
                        'date_end' => $serviceSale['date_end'],
                        'currency' => $currency['iso_code'],
                        'price' => $serviceSale['price'],
                        'reference' => $serviceSale['reference'],
                    ]
                );
                $messageTemplate->assign('transfer_accounts', $transferAccounts);

                $buyer = api_get_user_info($serviceSale['buyer']['id']);

                MessageManager::send_message_simple(
                    $buyer['user_id'],
                    $plugin->get_lang('bc_subject'),
                    $messageTemplate->fetch('BuyCourses/view/service_message_transfer.tpl')
                );

                if (!empty($globalParameters['sale_email'])) {
                    $messageConfirmTemplate = new Template();
                    $messageConfirmTemplate->assign('user', $userInfo);
                    $messageConfirmTemplate->assign(
                        'sale',
                        [
                            'date' => $serviceSale['buy_date'],
                            'product' => $serviceSale['service']['name'],
                            'currency' => $currency['iso_code'],
                            'price' => $serviceSale['price'],
                            'reference' => $serviceSale['reference'],
                        ]
                    );

                    api_mail_html(
                        '',
                        $globalParameters['sale_email'],
                        $plugin->get_lang('bc_subject'),
                        $messageConfirmTemplate->fetch('BuyCourses/view/message_confirm.tpl')
                    );
                }

                Display::addFlash(
                    Display::return_message(
                        sprintf(
                            $plugin->get_lang('PurchaseStatusX'),
                            $plugin->get_lang('PendingReasonByTransfer')
                        ),
                        'success',
                        false
                    )
                );

                unset($_SESSION['bc_service_sale_id'], $_SESSION['bc_coupon_id']);

                header('Location: '.$catalogUrl);
                exit;
            }
        }

        $template = new Template($templateName);
        $template->assign('header', $templateName);
        $template->assign('back_url', $catalogUrl);
        $template->assign('confirm_url', api_get_self());
        $template->assign('terms', $terms);
        $template->assign('service_sale', $serviceSale);
        $template->assign('service', $service);
        $template->assign('service_item', $serviceItem);
        $template->assign('currency', $currency);
        $template->assign('user', $userInfo);
        $template->assign('transfer_accounts', $transferAccounts);
        $template->assign('is_bank_transfer', true);
        $template->assign('is_culqi_payment', false);
        $template->assign('is_cecabank_payment', false);

        $content = $template->fetch('BuyCourses/view/process_confirm.tpl');
        $template->assign('content', $content);
        $template->display_one_col_template();
        exit;

    case BuyCoursesPlugin::PAYMENT_TYPE_CULQI:
        if ('POST' === $_SERVER['REQUEST_METHOD']) {
            $action = trim((string) ($_POST['action'] ?? ''));

            if ('cancel' === $action) {
                $plugin->cancelServiceSale($serviceSale['id']);
                unset($_SESSION['bc_service_sale_id'], $_SESSION['bc_coupon_id']);

                Display::addFlash(
                    Display::return_message(
                        $plugin->get_lang('OrderCancelled'),
                        'warning',
                        false
                    )
                );

                header('Location: '.$catalogUrl);
                exit;
            }
        }

        $culqiParams = $plugin->getCulqiParams();
        $culqiPublicKey = trim((string) ($culqiParams['commerce_code'] ?? ''));

        if ('' === $culqiPublicKey) {
            Display::addFlash(
                Display::return_message(
                    'Culqi public key is not configured.',
                    'error',
                    false
                )
            );

            header('Location: '.$catalogUrl);
            exit;
        }

        $currencyCode = strtoupper(trim((string) ($currency['iso_code'] ?? 'PEN')));
        $amountInCents = (int) round((float) ($serviceSale['price'] ?? 0) * 100);

        if ($amountInCents <= 0) {
            Display::addFlash(
                Display::return_message(
                    'Invalid service sale amount.',
                    'error',
                    false
                )
            );

            header('Location: '.$catalogUrl);
            exit;
        }

        $htmlHeadXtra[] = '<script src="https://checkout.culqi.com/js/v4"></script>';

        $template = new Template($templateName);
        $template->assign('header', $templateName);
        $template->assign('back_url', $catalogUrl);
        $template->assign('confirm_url', api_get_self());
        $template->assign('terms', $terms);
        $template->assign('service_sale', $serviceSale);
        $template->assign('service', $service);
        $template->assign('service_item', $serviceItem);
        $template->assign('currency', $currency);
        $template->assign('user', $userInfo);
        $template->assign('transfer_accounts', []);
        $template->assign('is_bank_transfer', false);
        $template->assign('is_culqi_payment', true);
        $template->assign('is_cecabank_payment', false);
        $template->assign('culqi_public_key', $culqiPublicKey);
        $template->assign(
            'culqi_charge_url',
            api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/buycourses.ajax.php?a=culqi_cargo_service'
        );
        $template->assign('catalog_url', $catalogUrl);
        $template->assign('culqi_amount_cents', $amountInCents);
        $template->assign('culqi_currency_code', $currencyCode);

        $content = $template->fetch('BuyCourses/view/process_confirm.tpl');
        $template->assign('content', $content);
        $template->display_one_col_template();
        exit;

    case BuyCoursesPlugin::PAYMENT_TYPE_TPV_CECABANK:
        $cecabankParams = $plugin->getcecabankParams();

        if ('POST' === $_SERVER['REQUEST_METHOD']) {
            $action = trim((string) ($_POST['action'] ?? ''));

            if ('cancel' === $action) {
                $plugin->cancelServiceSale($serviceSale['id']);

                unset($_SESSION['bc_service_sale_id'], $_SESSION['bc_coupon_id']);

                Display::addFlash(
                    Display::return_message(
                        $plugin->get_lang('OrderCancelled'),
                        'warning',
                        false
                    )
                );

                header('Location: '.$catalogUrl);
                exit;
            }

            if ('confirm' === $action) {
                $urlTpv = $cecabankParams['merchart_id'];
                $signature = $plugin->getCecabankSignature(
                    $serviceSale['reference'],
                    $serviceSale['price']
                );

                echo '<form name="tpv_chamilo" action="'.$urlTpv.'" method="POST">';
                echo '<input type="hidden" name="MerchantID" value="'.$cecabankParams['merchant_id'].'" />';
                echo '<input type="hidden" name="AcquirerBIN" value="'.$cecabankParams['acquirer_bin'].'" />';
                echo '<input type="hidden" name="TerminalID" value="'.$cecabankParams['terminal_id'].'" />';
                echo '<input type="hidden" name="URL_OK" value="'.api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/cecabank_success.php" />';
                echo '<input type="hidden" name="URL_NOK" value="'.api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/cecabank_cancel.php" />';
                echo '<input type="hidden" name="Firma" value="'.$signature.'" />';
                echo '<input type="hidden" name="Cifrado" value="'.$cecabankParams['cypher'].'" />';
                echo '<input type="hidden" name="Num_operacion" value="'.$serviceSale['reference'].'" />';
                echo '<input type="hidden" name="Importe" value="'.($serviceSale['price'] * 100).'" />';
                echo '<input type="hidden" name="TipoMoneda" value="'.$cecabankParams['currency'].'" />';
                echo '<input type="hidden" name="Exponente" value="'.$cecabankParams['exponent'].'" />';
                echo '<input type="hidden" name="Pago_soportado" value="'.$cecabankParams['supported_payment'].'" />';
                echo '</form>';
                echo '<script>document.tpv_chamilo.submit();</script>';

                exit;
            }
        }

        $template = new Template($templateName);
        $template->assign('header', $templateName);
        $template->assign('back_url', $catalogUrl);
        $template->assign('confirm_url', api_get_self());
        $template->assign('terms', $terms);
        $template->assign('service_sale', $serviceSale);
        $template->assign('service', $service);
        $template->assign('service_item', $serviceItem);
        $template->assign('currency', $currency);
        $template->assign('user', $userInfo);
        $template->assign('transfer_accounts', []);
        $template->assign('is_bank_transfer', false);
        $template->assign('is_culqi_payment', false);
        $template->assign('is_cecabank_payment', true);

        $content = $template->fetch('BuyCourses/view/process_confirm.tpl');
        $template->assign('content', $content);
        $template->display_one_col_template();
        exit;

    default:
        api_not_allowed(true);
}
