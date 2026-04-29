<?php

declare(strict_types=1);
/* For license terms, see /license.txt */

/**
 * Process purchase confirmation script for subscription sales in the Buy Courses plugin.
 */
require_once '../config.php';

$plugin = BuyCoursesPlugin::create();

$saleId = isset($_SESSION['bc_sale_id']) && is_scalar($_SESSION['bc_sale_id'])
    ? (int) $_SESSION['bc_sale_id']
    : 0;

$couponId = isset($_SESSION['bc_coupon_id']) && is_scalar($_SESSION['bc_coupon_id'])
    ? (int) $_SESSION['bc_coupon_id']
    : 0;

if ($saleId <= 0) {
    api_not_allowed(true);
}

$sale = $plugin->getSubscriptionSale($saleId);

if (empty($sale) || (int) $sale['user_id'] !== api_get_user_id()) {
    api_not_allowed(true);
}

$coupon = [];
if ($couponId > 0) {
    $coupon = $plugin->getCoupon(
        $couponId,
        (int) $sale['product_type'],
        (int) $sale['product_id']
    );
}

$userInfo = api_get_user_info((int) $sale['user_id']);
$currency = $plugin->getCurrency((int) $sale['currency_id']);
$globalParameters = $plugin->getGlobalParameters();

$catalogRedirectUrl = BuyCoursesPlugin::PRODUCT_TYPE_SESSION === (int) $sale['product_type']
    ? api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/subscription_session_catalog.php'
    : api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/subscription_course_catalog.php';

$buyingCourse = false;
$buyingSession = false;
$course = [];
$session = [];

switch ((int) $sale['product_type']) {
    case BuyCoursesPlugin::PRODUCT_TYPE_COURSE:
        $buyingCourse = true;
        $course = $plugin->getSubscriptionCourseInfo((int) $sale['product_id'], $coupon);

        break;

    case BuyCoursesPlugin::PRODUCT_TYPE_SESSION:
        $buyingSession = true;
        $session = $plugin->getSubscriptionSessionInfo((int) $sale['product_id'], $coupon);

        break;
}

switch ((int) $sale['payment_type']) {
    case BuyCoursesPlugin::PAYMENT_TYPE_PAYPAL:
        $paypalParams = $plugin->getPaypalParams();

        $test = 1 == ($paypalParams['sandbox'] ?? 0);
        $paypalUsername = trim((string) ($paypalParams['username'] ?? ''));
        $paypalPassword = trim((string) ($paypalParams['password'] ?? ''));
        $paypalSignature = trim((string) ($paypalParams['signature'] ?? ''));

        error_log('[BuyCourses][Subscription][PayPal] Starting Express Checkout for subscription sale '.$sale['id']);
        error_log('[BuyCourses][Subscription][PayPal] Sandbox='.($test ? 'true' : 'false'));
        error_log('[BuyCourses][Subscription][PayPal] API username configured='.($paypalUsername !== '' ? 'yes' : 'no'));
        error_log('[BuyCourses][Subscription][PayPal] API password configured='.($paypalPassword !== '' ? 'yes' : 'no'));
        error_log('[BuyCourses][Subscription][PayPal] API signature configured='.($paypalSignature !== '' ? 'yes' : 'no'));

        if ('' === $paypalUsername || '' === $paypalPassword || '' === $paypalSignature) {
            Display::addFlash(
                Display::return_message($plugin->get_lang('PayPalApiCredentialsIncomplete'), 'error', false)
            );

            error_log('[BuyCourses][Subscription][PayPal] Missing API credentials');

            header('Location: '.$catalogRedirectUrl);
            exit;
        }

        require_once 'paypalfunctions.php';

        $extra = "&L_PAYMENTREQUEST_0_NAME0=".urlencode((string) $sale['product_name']);
        $extra .= "&L_PAYMENTREQUEST_0_AMT0=".urlencode((string) $sale['price']);
        $extra .= '&L_PAYMENTREQUEST_0_QTY0=1';

        $returnUrl = api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/subscription_success.php';
        $cancelUrl = api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/subscription_error.php';

        error_log('[BuyCourses][Subscription][PayPal] Return URL: '.$returnUrl);
        error_log('[BuyCourses][Subscription][PayPal] Cancel URL: '.$cancelUrl);

        $expressCheckout = CallShortcutExpressCheckout(
            (string) $sale['price'],
            (string) ($currency['iso_code'] ?? ''),
            'Sale',
            $returnUrl,
            $cancelUrl,
            $extra
        );

        error_log('[BuyCourses][Subscription][PayPal] SetExpressCheckout completed for subscription sale '.$sale['id']);

        $ack = strtoupper((string) ($expressCheckout['ACK'] ?? ''));

        if (!in_array($ack, ['SUCCESS', 'SUCCESSWITHWARNING'], true)) {
            $errorCode = (string) ($expressCheckout['L_ERRORCODE0'] ?? 'unknown');
            $longMessage = (string) ($expressCheckout['L_LONGMESSAGE0'] ?? $plugin->get_lang('UnknownPayPalError'));
            $correlationId = (string) ($expressCheckout['CORRELATIONID'] ?? '');

            error_log('[BuyCourses][Subscription][PayPal] SetExpressCheckout failed. ACK='.$ack.' CODE='.$errorCode.' MESSAGE='.$longMessage.' CORRELATION='.$correlationId);

            Display::addFlash(
                Display::return_message(
                    sprintf($plugin->get_lang('PayPalErrorCodeMessage'), $errorCode, $longMessage),
                    'error',
                    false
                )
            );

            header('Location: '.$catalogRedirectUrl);
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
                    'currency' => $currency['iso_code'] ?? '',
                    'price' => $sale['price'],
                    'reference' => $sale['reference'],
                ]
            );

            api_mail_html(
                '',
                $globalParameters['sale_email'],
                $plugin->get_lang('bc_subject'),
                $messageConfirmTemplate->fetch('BuyCourses/view/message_confirm.tpl')
            );
        }

        error_log('[BuyCourses][Subscription][PayPal] Redirecting to PayPal for subscription sale '.$sale['id']);

        RedirectToPayPal((string) ($expressCheckout['TOKEN'] ?? ''));
        exit;

    case BuyCoursesPlugin::PAYMENT_TYPE_TRANSFER:
        $transferAccounts = $plugin->getTransferAccounts();
        $infoEmailExtra = $plugin->getTransferInfoExtra()['tinfo_email_extra'] ?? '';

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
                $plugin->cancelSubscriptionSale((int) $sale['id']);

                unset($_SESSION['bc_sale_id'], $_SESSION['bc_coupon_id']);

                header('Location: '.$catalogRedirectUrl);
                exit;
            }

            $messageTemplate = new Template();
            $messageTemplate->assign('user', $userInfo);
            $messageTemplate->assign(
                'sale',
                [
                    'date' => $sale['date'],
                    'product' => $sale['product_name'],
                    'currency' => $currency['iso_code'] ?? '',
                    'price' => $sale['price'],
                    'reference' => $sale['reference'],
                ]
            );
            $messageTemplate->assign('transfer_accounts', $transferAccounts);
            $messageTemplate->assign('info_email_extra', $infoEmailExtra);

            MessageManager::send_message_simple(
                (int) $userInfo['user_id'],
                $plugin->get_lang('bc_subject'),
                $messageTemplate->fetch('BuyCourses/view/message_transfer.tpl')
            );

            if (!empty($globalParameters['sale_email'])) {
                $messageConfirmTemplate = new Template();
                $messageConfirmTemplate->assign('user', $userInfo);
                $messageConfirmTemplate->assign(
                    'sale',
                    [
                        'date' => $sale['date'],
                        'product' => $sale['product_name'],
                        'currency' => $currency['iso_code'] ?? '',
                        'price' => $sale['price'],
                        'reference' => $sale['reference'],
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

            unset($_SESSION['bc_sale_id'], $_SESSION['bc_coupon_id']);

            header('Location: '.$catalogRedirectUrl);
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
        $template->assign('terms', $globalParameters['terms_and_conditions'] ?? '');
        $template->assign('title', $sale['product_name']);
        $template->assign('price', (float) $sale['price']);
        $template->assign('currency', $currency);
        $template->assign('user', $userInfo);
        $template->assign('transfer_accounts', $transferAccounts);
        $template->assign('form', $form->returnForm());
        $template->assign('is_bank_transfer', true);

        $content = $template->fetch('BuyCourses/view/subscription_process_confirm.tpl');

        $template->assign('content', $content);
        $template->display_one_col_template();

        break;

    case BuyCoursesPlugin::PAYMENT_TYPE_CULQI:
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
                $plugin->cancelSubscriptionSale((int) $sale['id']);

                unset($_SESSION['bc_sale_id'], $_SESSION['bc_coupon_id']);

                Display::addFlash(
                    Display::return_message(
                        $plugin->get_lang('OrderCanceled'),
                        'warning',
                        false
                    )
                );

                header('Location: '.$catalogRedirectUrl);
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
        $template->assign('terms', $globalParameters['terms_and_conditions'] ?? '');
        $template->assign('title', $sale['product_name']);
        $template->assign('price', (float) $sale['price']);
        $template->assign('currency', $currency);
        $template->assign('user', $userInfo);
        $template->assign('sale', $sale);
        $template->assign('form', $form->returnForm());
        $template->assign('is_culqi_payment', true);
        $template->assign('culqi_params', $plugin->getCulqiParams());

        $content = $template->fetch('BuyCourses/view/subscription_process_confirm.tpl');

        $template->assign('content', $content);
        $template->display_one_col_template();

        break;

    case BuyCoursesPlugin::PAYMENT_TYPE_TPV_REDSYS:
        $tpvRedsysParams = $plugin->getTpvRedsysParams();

        require_once '../resources/apiRedsys.php';
        $tpv = new RedsysAPI();

        $merchantcode = $tpvRedsysParams['merchantcode'];
        $terminal = $tpvRedsysParams['terminal'];
        $currencyCode = $tpvRedsysParams['currency'];
        $transactionType = '0';
        $urlMerchant = api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/tpv_response.php';
        $urlSuccess = api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/tpv_success.php';
        $urlFailed = api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/tpv_error.php';
        $order = str_pad((string) $saleId, 4, '0', \STR_PAD_LEFT);
        $amount = (float) $sale['price'] * 100;
        $description = $plugin->get_lang('OrderReference').': '.$sale['reference'];

        $tpv->setParameter('DS_MERCHANT_AMOUNT', $amount);
        $tpv->setParameter('DS_MERCHANT_ORDER', $order);
        $tpv->setParameter('DS_MERCHANT_MERCHANTCODE', $merchantcode);
        $tpv->setParameter('DS_MERCHANT_CURRENCY', $currencyCode);
        $tpv->setParameter('DS_MERCHANT_TRANSACTIONTYPE', $transactionType);
        $tpv->setParameter('DS_MERCHANT_TERMINAL', $terminal);
        $tpv->setParameter('DS_MERCHANT_MERCHANTURL', $urlMerchant);
        $tpv->setParameter('DS_MERCHANT_URLOK', $urlSuccess);
        $tpv->setParameter('DS_MERCHANT_URLKO', $urlFailed);
        $tpv->setParameter('DS_MERCHANT_PRODUCTDESCRIPTION', $description);

        $version = 'HMAC_SHA256_V1';
        $kc = $tpvRedsysParams['kc'];

        $urlTpv = $tpvRedsysParams['url_redsys'];
        $sandboxFlag = 1 == $tpvRedsysParams['sandbox'];

        if (true === $sandboxFlag) {
            $urlTpv = $tpvRedsysParams['url_redsys_sandbox'];
        }

        $params = $tpv->createMerchantParameters();
        $signature = $tpv->createMerchantSignature($kc);

        echo '<form name="tpv_chamilo" action="'.$urlTpv.'" method="POST">';
        echo '<input type="hidden" name="Ds_SignatureVersion" value="'.$version.'" />';
        echo '<input type="hidden" name="Ds_MerchantParameters" value="'.$params.'" />';
        echo '<input type="hidden" name="Ds_Signature" value="'.$signature.'" />';
        echo '</form>';
        echo '<script>document.tpv_chamilo.submit();</script>';

        break;

    default:
        Display::addFlash(
            Display::return_message($plugin->get_lang('NoPaymentOptionAvailable'), 'error', false)
        );

        header('Location: '.$catalogRedirectUrl);
        exit;
}
