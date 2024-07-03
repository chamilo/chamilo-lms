<?php
/* For license terms, see /license.txt */

/**
 * Process purchase confirmation script for the Buy Courses plugin.
 *
 * @package chamilo.plugin.buycourses
 */
require_once '../config.php';

$plugin = BuyCoursesPlugin::create();

$saleId = $_SESSION['bc_sale_id'];
$couponId = (!empty($_SESSION['bc_coupon_id']) ?? '');

if (empty($saleId)) {
    api_not_allowed(true);
}

$sale = $plugin->getSale($saleId);

$coupon = [];
if (!empty($couponId)) {
    $coupon = $plugin->getCoupon($couponId, $sale['product_type'], $sale['product_id']);
}

$userInfo = api_get_user_info($sale['user_id']);

if (empty($sale)) {
    api_not_allowed(true);
}

$currency = $plugin->getCurrency($sale['currency_id']);
$globalParameters = $plugin->getGlobalParameters();

switch ($sale['payment_type']) {
    case BuyCoursesPlugin::PAYMENT_TYPE_PAYPAL:
        $paypalParams = $plugin->getPaypalParams();

        $pruebas = $paypalParams['sandbox'] == 1;
        $paypalUsername = $paypalParams['username'];
        $paypalPassword = $paypalParams['password'];
        $paypalSignature = $paypalParams['signature'];

        require_once "paypalfunctions.php";

        $i = 0;
        $extra = "&L_PAYMENTREQUEST_0_NAME0={$sale['product_name']}";
        $extra .= "&L_PAYMENTREQUEST_0_AMT0={$sale['price']}";
        $extra .= "&L_PAYMENTREQUEST_0_QTY0=1";

        $expressCheckout = CallShortcutExpressCheckout(
            $sale['price'],
            $currency['iso_code'],
            'paypal',
            api_get_path(WEB_PLUGIN_PATH).'buycourses/src/success.php',
            api_get_path(WEB_PLUGIN_PATH).'buycourses/src/error.php',
            $extra
        );

        if ($expressCheckout["ACK"] !== 'Success') {
            $erroMessage = vsprintf(
                $plugin->get_lang('ErrorOccurred'),
                [$expressCheckout['L_ERRORCODE0'], $expressCheckout['L_LONGMESSAGE0']]
            );
            Display::addFlash(
                Display::return_message($erroMessage, 'error', false)
            );
            header('Location: ../index.php');
            exit;
        }

        if (!empty($globalParameters['sale_email'])) {
            $messageConfirmTemplate = new Template();
            $messageConfirmTemplate->assign('user', $userInfo);
            $messageConfirmTemplate->assign(
                'sale',
                [
                    'date' => $sale['date'],
                    'product' => $sale['product_name'],
                    'currency' => $currency['iso_code'],
                    'price' => $sale['price'],
                    'reference' => $sale['reference'],
                ]
            );

            api_mail_html(
                '',
                $globalParameters['sale_email'],
                $plugin->get_lang('bc_subject'),
                $messageConfirmTemplate->fetch('buycourses/view/message_confirm.tpl')
            );
        }

        RedirectToPayPal($expressCheckout["TOKEN"]);
        break;
    case BuyCoursesPlugin::PAYMENT_TYPE_TRANSFER:
        $buyingCourse = false;
        $buyingSession = false;

        switch ($sale['product_type']) {
            case BuyCoursesPlugin::PRODUCT_TYPE_COURSE:
                $buyingCourse = true;
                $course = $plugin->getCourseInfo($sale['product_id'], $coupon);
                break;
            case BuyCoursesPlugin::PRODUCT_TYPE_SESSION:
                $buyingSession = true;
                $session = $plugin->getSessionInfo($sale['product_id'], $coupon);
                break;
        }

        $transferAccounts = $plugin->getTransferAccounts();
        $infoEmailExtra = $plugin->getTransferInfoExtra()['tinfo_email_extra'];

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
                $plugin->cancelSale($sale['id']);

                unset($_SESSION['bc_sale_id']);
                unset($_SESSION['bc_coupon_id']);

                header('Location: '.api_get_path(WEB_PLUGIN_PATH).'buycourses/index.php');
                exit;
            }

            $messageTemplate = new Template();
            $messageTemplate->assign('user', $userInfo);
            $messageTemplate->assign(
                'sale',
                [
                    'date' => $sale['date'],
                    'product' => $sale['product_name'],
                    'currency' => $currency['iso_code'],
                    'price' => $sale['price'],
                    'reference' => $sale['reference'],
                ]
            );
            $messageTemplate->assign('transfer_accounts', $transferAccounts);
            $messageTemplate->assign('info_email_extra', $infoEmailExtra);

            MessageManager::send_message_simple(
                $userInfo['user_id'],
                $plugin->get_lang('bc_subject'),
                $messageTemplate->fetch('buycourses/view/message_transfer.tpl')
            );

            if (!empty($globalParameters['sale_email'])) {
                $messageConfirmTemplate = new Template();
                $messageConfirmTemplate->assign('user', $userInfo);
                $messageConfirmTemplate->assign(
                    'sale',
                    [
                        'date' => $sale['date'],
                        'product' => $sale['product_name'],
                        'currency' => $currency['iso_code'],
                        'price' => $sale['price'],
                        'reference' => $sale['reference'],
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

            unset($_SESSION['bc_sale_id']);
            unset($_SESSION['bc_coupon_id']);
            header('Location: '.api_get_path(WEB_PLUGIN_PATH).'buycourses/src/course_catalog.php');
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

        if ($buyingCourse) {
            $template->assign('course', $course);
        } elseif ($buyingSession) {
            $template->assign('session', $session);
        }

        $template->assign('buying_course', $buyingCourse);
        $template->assign('buying_session', $buyingSession);
        $template->assign('terms', $globalParameters['terms_and_conditions']);
        $template->assign('title', $sale['product_name']);
        $template->assign('price', $sale['price']);
        $template->assign('currency', $sale['currency_id']);
        $template->assign('user', $userInfo);
        $template->assign('transfer_accounts', $transferAccounts);
        $template->assign('form', $form->returnForm());
        $template->assign('is_bank_transfer', true);

        $content = $template->fetch('buycourses/view/process_confirm.tpl');

        $template->assign('content', $content);
        $template->display_one_col_template();
        break;
    case BuyCoursesPlugin::PAYMENT_TYPE_CULQI:
        // We need to include the main online script, acording to the Culqi documentation the JS needs to be loeaded
        // directly from the main url "https://integ-pago.culqi.com" because a local copy of this JS is not supported
        $htmlHeadXtra[] = '<script src="//integ-pago.culqi.com/js/v1"></script>';

        $buyingCourse = false;
        $buyingSession = false;

        switch ($sale['product_type']) {
            case BuyCoursesPlugin::PRODUCT_TYPE_COURSE:
                $buyingCourse = true;
                $course = $plugin->getCourseInfo($sale['product_id'], $coupon);
                break;
            case BuyCoursesPlugin::PRODUCT_TYPE_SESSION:
                $buyingSession = true;
                $session = $plugin->getSessionInfo($sale['product_id'], $coupon);
                break;
        }

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
                $plugin->cancelSale($sale['id']);

                unset($_SESSION['bc_sale_id']);
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

        if ($buyingCourse) {
            $template->assign('course', $course);
        } elseif ($buyingSession) {
            $template->assign('session', $session);
        }

        $template->assign('buying_course', $buyingCourse);
        $template->assign('buying_session', $buyingSession);
        $template->assign('terms', $globalParameters['terms_and_conditions']);
        $template->assign('title', $sale['product_name']);
        $template->assign('price', floatval($sale['price']));
        $template->assign('currency', $plugin->getSelectedCurrency());
        $template->assign('user', $userInfo);
        $template->assign('sale', $sale);
        $template->assign('form', $form->returnForm());
        $template->assign('is_culqi_payment', true);
        $template->assign('culqi_params', $culqiParams = $plugin->getCulqiParams());

        $content = $template->fetch('buycourses/view/process_confirm.tpl');

        $template->assign('content', $content);
        $template->display_one_col_template();

        break;
    case BuyCoursesPlugin::PAYMENT_TYPE_TPV_REDSYS:
        $tpvRedsysParams = $plugin->getTpvRedsysParams();

        require_once '../resources/apiRedsys.php';
        $tpv = new RedsysAPI();

        $merchantcode = $tpvRedsysParams['merchantcode'];
        $terminal = $tpvRedsysParams['terminal'];
        $currency = $tpvRedsysParams['currency'];
        $transactionType = "0";
        $urlMerchant = api_get_path(WEB_PLUGIN_PATH).'buycourses/src/tpv_response.php';
        $urlSuccess = api_get_path(WEB_PLUGIN_PATH).'buycourses/src/tpv_success.php';
        $urlFailed = api_get_path(WEB_PLUGIN_PATH).'buycourses/src/tpv_error.php';
        $order = str_pad(strval($saleId), 4, "0", STR_PAD_LEFT);
        $amount = $sale['price'] * 100;
        $description = $plugin->get_lang('OrderReference').": ".$sale['reference'];
        $tpv->setParameter("DS_MERCHANT_AMOUNT", $amount);
        $tpv->setParameter("DS_MERCHANT_ORDER", $order);
        $tpv->setParameter("DS_MERCHANT_MERCHANTCODE", $merchantcode);
        $tpv->setParameter("DS_MERCHANT_CURRENCY", $currency);
        $tpv->setParameter("DS_MERCHANT_TRANSACTIONTYPE", $transactionType);
        $tpv->setParameter("DS_MERCHANT_TERMINAL", $terminal);
        $tpv->setParameter("DS_MERCHANT_MERCHANTURL", $urlMerchant);
        $tpv->setParameter("DS_MERCHANT_URLOK", $urlSuccess);
        $tpv->setParameter("DS_MERCHANT_URLKO", $urlFailed);
        $tpv->setParameter("DS_MERCHANT_PRODUCTDESCRIPTION", $description);

        $version = "HMAC_SHA256_V1";
        $kc = $tpvRedsysParams['kc'];

        $urlTpv = $tpvRedsysParams['url_redsys'];
        $sandboxFlag = $tpvRedsysParams['sandbox'] == 1;
        if ($sandboxFlag === true) {
            $urlTpv = $tpvRedsysParams['url_redsys_sandbox'];
        }

        $params = $tpv->createMerchantParameters();
        $signature = $tpv->createMerchantSignature($kc);

        echo '<form name="tpv_chamilo" action="'.$urlTpv.'" method="POST">';
        echo '<input type="hidden" name="Ds_SignatureVersion" value="'.$version.'" />';
        echo '<input type="hidden" name="Ds_MerchantParameters" value="'.$params.'" />';
        echo '<input type="hidden" name="Ds_Signature" value="'.$signature.'" />';
        echo '</form>';

        echo '<SCRIPT language=javascript>';
        echo 'document.tpv_chamilo.submit();';
        echo '</script>';

        break;
    case BuyCoursesPlugin::PAYMENT_TYPE_STRIPE:
        $buyingCourse = false;
        $buyingSession = false;

        switch ($sale['product_type']) {
            case BuyCoursesPlugin::PRODUCT_TYPE_COURSE:
                $buyingCourse = true;
                $course = $plugin->getCourseInfo($sale['product_id'], $coupon);
                break;
            case BuyCoursesPlugin::PRODUCT_TYPE_SESSION:
                $buyingSession = true;
                $session = $plugin->getSessionInfo($sale['product_id'], $coupon);
                break;
        }

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
                $plugin->cancelSale($sale['id']);

                unset($_SESSION['bc_sale_id']);
                unset($_SESSION['bc_coupon_id']);

                header('Location: '.api_get_path(WEB_PLUGIN_PATH).'buycourses/index.php');
                exit;
            }

            $stripeParams = $plugin->getStripeParams();
            $currency = $plugin->getCurrency($sale['currency_id']);

            \Stripe\Stripe::setApiKey($stripeParams['secret_key']);
            \Stripe\Stripe::setAppInfo("ChamiloBuyCoursesPlugin");

            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'unit_amount_decimal' => $sale['price'] * 100,
                        'currency' => $currency['iso_code'],
                        'product_data' => [
                            'name' => $sale['product_name'],
                        ],
                    ],
                    'quantity' => 1,
                ]],
                'customer_email' => $_SESSION['_user']['email'],
                'mode' => 'payment',
                'success_url' => api_get_path(WEB_PLUGIN_PATH).'buycourses/src/stripe_success.php',
                'cancel_url' => api_get_path(WEB_PLUGIN_PATH).'buycourses/src/stripe_cancel.php',
            ]);

            if (!empty($session)) {
                $plugin->updateSaleReference($saleId, $session->id);

                unset($_SESSION['bc_coupon_id']);

                header('HTTP/1.1 301 Moved Permanently');
                header('Location: '.$session->url);
            } else {
                Display::addFlash(
                    Display::return_message(
                        $plugin->get_lang('ErrorOccurred'),
                         'error',
                         false
                        )
                );
                header('Location: ../index.php');
            }

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

        if ($buyingCourse) {
            $template->assign('course', $course);
        } elseif ($buyingSession) {
            $template->assign('session', $session);
        }

        $template->assign('buying_course', $buyingCourse);
        $template->assign('buying_session', $buyingSession);
        $template->assign('terms', $globalParameters['terms_and_conditions']);
        $template->assign('title', $sale['product_name']);
        $template->assign('price', $sale['price']);
        $template->assign('currency', $sale['currency_id']);
        $template->assign('user', $userInfo);
        $template->assign('transfer_accounts', $transferAccounts);
        $template->assign('form', $form->returnForm());
        $template->assign('is_bank_transfer', false);

        $content = $template->fetch('buycourses/view/process_confirm.tpl');

        $template->assign('content', $content);
        $template->display_one_col_template();

        break;

    case BuyCoursesPlugin::PAYMENT_TYPE_TPV_CECABANK:
        $buyingCourse = false;
        $buyingSession = false;

        switch ($sale['product_type']) {
            case BuyCoursesPlugin::PRODUCT_TYPE_COURSE:
                $buyingCourse = true;
                $course = $plugin->getCourseInfo($sale['product_id']);
                break;
            case BuyCoursesPlugin::PRODUCT_TYPE_SESSION:
                $buyingSession = true;
                $session = $plugin->getSessionInfo($sale['product_id']);
                break;
        }

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
                $plugin->cancelSale($sale['id']);

                unset($_SESSION['bc_sale_id']);
                unset($_SESSION['bc_coupon_id']);

                header('Location: '.api_get_path(WEB_PLUGIN_PATH).'buycourses/index.php');
                exit;
            }

            $urlTpv = $cecabankParams['url'];
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
            echo '<input type="hidden" name="TipoMoneda" value="978" />';
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

        if ($buyingCourse) {
            $template->assign('course', $course);
        } elseif ($buyingSession) {
            $template->assign('session', $session);
        }

        $template->assign('buying_course', $buyingCourse);
        $template->assign('buying_session', $buyingSession);
        $template->assign('terms', $globalParameters['terms_and_conditions']);
        $template->assign('title', $sale['product_name']);
        $template->assign('price', $sale['price']);
        $template->assign('currency', $sale['currency_id']);
        $template->assign('user', $userInfo);
        $template->assign('transfer_accounts', $transferAccounts);
        $template->assign('form', $form->returnForm());
        $template->assign('is_bank_transfer', false);

        $content = $template->fetch('buycourses/view/process_confirm.tpl');

        $template->assign('content', $content);
        $template->display_one_col_template();

        break;
}
