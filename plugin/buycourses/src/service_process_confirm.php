<?php
/* For license terms, see /license.txt */

use ChamiloSession as Session;

/**
 * Process purchase confirmation script for the Buy Courses plugin.
 *
 * @package chamilo.plugin.buycourses
 */
require_once '../config.php';

$plugin = BuyCoursesPlugin::create();
$serviceSaleId = Session::read('bc_service_sale_id');
$couponId = Session::read('bc_coupon_id');

if (empty($serviceSaleId)) {
    api_not_allowed(true);
}

$serviceSale = $plugin->getServiceSale($serviceSaleId, $coupon);
$userInfo = api_get_user_info($serviceSale['buyer']['id']);

if (!empty($couponId)) {
    $coupon = $plugin->getCouponService($couponId, $serviceSale['service_id']);
    $serviceSale['item'] = $plugin->getService($serviceSale['service_id'], $coupon);
}

if (empty($serviceSale)) {
    api_not_allowed(true);
}

$currency = $plugin->getCurrency($serviceSale['currency_id']);
$globalParameters = $plugin->getGlobalParameters();

switch ($serviceSale['payment_type']) {
    case BuyCoursesPlugin::PAYMENT_TYPE_PAYPAL:
        $paypalParams = $plugin->getPaypalParams();

        $pruebas = $paypalParams['sandbox'] == 1;
        $paypalUsername = $paypalParams['username'];
        $paypalPassword = $paypalParams['password'];
        $paypalSignature = $paypalParams['signature'];

        // This var $itemPrice may be "0" if the transaction does not include a one-time purchase such as when you set up
        // a billing agreement for a recurring payment that is not immediately charged. When the field is set to 0,
        // purchase-specific fields are ignored. This little condition handle this fact.
        $itemPrice = $serviceSale['price'];

        $returnUrl = api_get_path(WEB_PLUGIN_PATH).'buycourses/src/service_success.php';
        $cancelUrl = api_get_path(WEB_PLUGIN_PATH).'buycourses/src/service_error.php';

        // The extra params for handle the hard job, this var is VERY IMPORTANT !!
        $extra = '';
        require_once 'paypalfunctions.php';

        $extra .= "&L_PAYMENTREQUEST_0_NAME0={$serviceSale['service']['name']}";
        $extra .= "&L_PAYMENTREQUEST_0_QTY0=1";
        $extra .= "&L_PAYMENTREQUEST_0_AMT0=$itemPrice";

        // Full Checkout express
        $expressCheckout = CallShortcutExpressCheckout(
            $itemPrice,
            $currency['iso_code'],
            'paypal',
            $returnUrl,
            $cancelUrl,
            $extra
        );

        if ($expressCheckout['ACK'] !== 'Success') {
            $erroMessage = vsprintf(
                $plugin->get_lang('ErrorOccurred'),
                [$expressCheckout['L_ERRORCODE0'], $expressCheckout['L_LONGMESSAGE0']]
            );
            Display::addFlash(
                Display::return_message($erroMessage, 'error', false)
            );

            $plugin->cancelServiceSale($serviceSale['id']);
            header('Location: '.api_get_path(WEB_PLUGIN_PATH).'buycourses/src/service_catalog.php');
            exit;
        }

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
                $messageConfirmTemplate->fetch('buycourses/view/message_confirm.tpl')
            );
        }

        RedirectToPayPal($expressCheckout['TOKEN']);
        break;
    case BuyCoursesPlugin::PAYMENT_TYPE_TRANSFER:
        $transferAccounts = $plugin->getTransferAccounts();

        $form = new FormValidator(
            'success',
            'POST',
            api_get_self(),
            null,
            null,
            FormValidator::LAYOUT_INLINE
        );

        if ($form->validate()) {
            $formValues = $form->getSubmitValues();

            if (isset($formValues['cancel'])) {
                $plugin->cancelServiceSale($serviceSale['id']);

                unset($_SESSION['bc_service_sale_id']);
                unset($_SESSION['bc_coupon_id']);
                Display::addFlash(
                    Display::return_message($plugin->get_lang('OrderCancelled'), 'error', false)
                );
                header('Location: '.api_get_path(WEB_PLUGIN_PATH).'buycourses/src/service_catalog.php');
                exit;
            }

            $messageTemplate = new Template();
            $messageTemplate->assign(
                'service_sale',
                [
                    'name' => $serviceSale['service']['name'],
                    'buyer' => $serviceSale['buyer']['name'],
                    'buy_date' => $serviceSale['buy_date'],
                    'start_date' => $serviceSale['start_date'],
                    'end_date' => $serviceSale['end_date'],
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
                $messageTemplate->fetch('buycourses/view/service_message_transfer.tpl')
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
                    $messageConfirmTemplate->fetch('buycourses/view/message_confirm.tpl')
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

            unset($_SESSION['bc_service_sale_id']);
            unset($_SESSION['bc_coupon_id']);
            header('Location: '.api_get_path(WEB_PLUGIN_PATH).'buycourses/src/service_catalog.php');
            exit;
        }

        $form->addButton(
            'confirm',
            $plugin->get_lang('ConfirmOrder'),
            'check',
            'success',
            'default',
            null,
            ['id' => 'confirm']
        );
        $form->addButton(
            'cancel',
            $plugin->get_lang('CancelOrder'),
            'times',
            'danger',
            'default',
            null,
            ['id' => 'cancel']
        );

        $template = new Template();
        $template->assign('terms', $globalParameters['terms_and_conditions']);
        $template->assign('title', $serviceSale['service']['name']);
        $template->assign('price', $serviceSale['price']);
        $template->assign('currency', $serviceSale['currency_id']);
        $template->assign('buying_service', $serviceSale);
        $template->assign('user', $userInfo);
        $template->assign('service', $serviceSale['service']);
        $template->assign('service_item', $serviceSale['item']);
        $template->assign('transfer_accounts', $transferAccounts);
        $template->assign('form', $form->returnForm());

        $content = $template->fetch('buycourses/view/process_confirm.tpl');

        $template->assign('content', $content);
        $template->display_one_col_template();
        break;
    case BuyCoursesPlugin::PAYMENT_TYPE_CULQI:
        // We need to include the main online script, acording to the Culqi documentation the JS needs to be loeaded
        // directly from the main url "https://integ-pago.culqi.com" because a local copy of this JS is not supported
        $htmlHeadXtra[] = '<script src="//integ-pago.culqi.com/js/v1"></script>';

        $form = new FormValidator(
            'success',
            'POST',
            api_get_self(),
            null,
            null,
            FormValidator::LAYOUT_INLINE
        );

        if ($form->validate()) {
            $formValues = $form->getSubmitValues();
            if (isset($formValues['cancel'])) {
                $plugin->cancelServiceSale($serviceSale['id']);

                unset($_SESSION['bc_service_sale_id']);
                unset($_SESSION['bc_coupon_id']);

                Display::addFlash(
                    Display::return_message(
                        $plugin->get_lang('OrderCanceled'),
                        'warning',
                        false
                    )
                );

                header('Location: '.api_get_path(WEB_PLUGIN_PATH).'buycourses/index.php');
                exit;
            }
        }
        $form->addButton(
            'confirm',
            $plugin->get_lang('ConfirmOrder'),
            'check',
            'success',
            'default',
            null,
            ['id' => 'confirm']
        );
        $form->addButton(
            'cancel',
            $plugin->get_lang('CancelOrder'),
            'times',
            'danger',
            'default',
            null,
            ['id' => 'cancel']
        );

        $template = new Template();
        $template->assign('terms', $globalParameters['terms_and_conditions']);
        $template->assign('title', $serviceSale['service']['name']);
        $template->assign('price', floatval($serviceSale['price']));
        $template->assign('currency', $plugin->getSelectedCurrency());
        $template->assign('buying_service', $serviceSale);
        $template->assign('user', $userInfo);
        $template->assign('service', $serviceSale['service']);
        $template->assign('service_item', $serviceSale['item']);
        $template->assign('form', $form->returnForm());
        $template->assign('is_culqi_payment', true);
        $template->assign('culqi_params', $culqiParams = $plugin->getCulqiParams());
        $content = $template->fetch('buycourses/view/process_confirm.tpl');
        $template->assign('content', $content);
        $template->display_one_col_template();
        break;

    case BuyCoursesPlugin::PAYMENT_TYPE_TPV_CECABANK:
        $cecabankParams = $plugin->getcecabankParams();
        $currency = $plugin->getCurrency($sale['currency_id']);

        $form = new FormValidator(
            'success',
            'POST',
            api_get_self(),
            null,
            null,
            FormValidator::LAYOUT_INLINE
        );

        if ($form->validate()) {
            $formValues = $form->getSubmitValues();

            if (isset($formValues['cancel'])) {
                $plugin->cancelServiceSale($sale['id']);

                unset($_SESSION['bc_sale_id']);
                unset($_SESSION['bc_coupon_id']);

                header('Location: '.api_get_path(WEB_PLUGIN_PATH).'buycourses/index.php');
                exit;
            }

            $urlTpv = $cecabankParams['merchart_id'];
            $currency = $plugin->getCurrency($sale['currency_id']);
            $signature = $plugin->getCecabankSignature($sale['reference'], $sale['price']);

            echo '<form name="tpv_chamilo" action="'.$urlTpv.'" method="POST">';
            echo '<input type="hidden" name="MerchantID" value="'.$cecabankParams['merchant_id'].'" />';
            echo '<input type="hidden" name="AcquirerBIN" value="'.$cecabankParams['acquirer_bin'].'" />';
            echo '<input type="hidden" name="TerminalID" value="'.$cecabankParams['terminal_id'].'" />';
            echo '<input type="hidden" name="URL_OK" value="'.api_get_path(WEB_PLUGIN_PATH).'buycourses/src/cecabank_success.php'.'" />';
            echo '<input type="hidden" name="URL_NOK" value="'.api_get_path(WEB_PLUGIN_PATH).'buycourses/src/cecabank_cancel.php'.'" />';
            echo '<input type="hidden" name="Firma" value="'.$signature.'" />';
            echo '<input type="hidden" name="Cifrado" value="'.$cecabankParams['cypher'].'" />';
            echo '<input type="hidden" name="Num_operacion" value="'.$sale['reference'].'" />';
            echo '<input type="hidden" name="Importe" value="'.($sale['price'] * 100).'" />';
            echo '<input type="hidden" name="TipoMoneda" value="'.$cecabankParams['currency'].'" />';
            echo '<input type="hidden" name="Exponente" value="'.$cecabankParams['exponent'].'" />';
            echo '<input type="hidden" name="Pago_soportado" value="'.$cecabankParams['supported_payment'].'" />';
            echo '</form>';

            echo '<SCRIPT language=javascript>';
            echo 'document.tpv_chamilo.submit();';
            echo '</script>';

            exit;
        }

        $form->addButton(
            'confirm',
            $plugin->get_lang('ConfirmOrder'),
            'check',
            'success',
            'default',
            null,
            ['id' => 'confirm']
        );
        $form->addButtonCancel($plugin->get_lang('CancelOrder'), 'cancel');

        $template = new Template();
        $template->assign('terms', $globalParameters['terms_and_conditions']);
        $template->assign('title', $serviceSale['service']['name']);
        $template->assign('price', $serviceSale['price']);
        $template->assign('currency', $serviceSale['currency_id']);
        $template->assign('buying_service', $serviceSale);
        $template->assign('user', $userInfo);
        $template->assign('service', $serviceSale['service']);
        $template->assign('service_item', $serviceSale['item']);
        $template->assign('transfer_accounts', $transferAccounts);
        $template->assign('form', $form->returnForm());

        $content = $template->fetch('buycourses/view/process_confirm.tpl');

        $template->assign('content', $content);
        $template->display_one_col_template();

        break;
}
