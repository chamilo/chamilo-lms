<?php
/* For license terms, see /license.txt */
/**
 * Process purchase confirmation script for the Buy Courses plugin
 * @package chamilo.plugin.buycourses
 */
/**
 * Init
 */
require_once '../config.php';

$plugin = BuyCoursesPlugin::create();

$saleId = $_SESSION['bc_sale_id'];

if (empty($saleId)) {
    api_not_allowed(true);
}

$sale = $plugin->getSale($saleId);

if (empty($sale)) {
    api_not_allowed(true);
}

$currency = $plugin->getCurrency($sale['currency_id']);

switch ($sale['payment_type']) {
    case BuyCoursesPlugin::PAYMENT_TYPE_PAYPAL:
        $paypalParams = $plugin->getPaypalParams();

        $pruebas = $paypalParams['sandbox'] == 1;
        $paypalUsername = $paypalParams['username'];
        $paypalPassword = $paypalParams['password'];
        $paypalSignature = $paypalParams['signature'];

        require_once("paypalfunctions.php");

        $i = 0;
        $extra = "&L_PAYMENTREQUEST_0_NAME0={$sale['product_name']}";
        $extra .= "&L_PAYMENTREQUEST_0_AMT0={$sale['price']}";
        $extra .= "&L_PAYMENTREQUEST_0_QTY0=1";

        $expressCheckout = CallShortcutExpressCheckout(
            $sale['price'],
            $currency['iso_code'],
            'paypal',
            api_get_path(WEB_PLUGIN_PATH) . 'buycourses/src/success.php',
            api_get_path(WEB_PLUGIN_PATH) . 'buycourses/src/error.php',
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

        RedirectToPayPal($expressCheckout["TOKEN"]);
        break;
    case BuyCoursesPlugin::PAYMENT_TYPE_TRANSFER:
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

        $transferAccounts = $plugin->getTransferAccounts();
        $userInfo = api_get_user_info($sale['user_id']);

        $form = new FormValidator('success', 'POST', api_get_self(), null, null, FormValidator::LAYOUT_INLINE);

        if ($form->validate()) {
            $formValues = $form->getSubmitValues();

            if (isset($formValues['cancel'])) {
                $plugin->cancelSale($sale['id']);

                unset($_SESSION['bc_sale_id']);

                header('Location: ' . api_get_path(WEB_PLUGIN_PATH) . 'buycourses/index.php');
                exit;
            }

            $messageTemplate = new Template();
            $messageTemplate->assign('user', $userInfo);
            $messageTemplate->assign(
                'sale',
                [
                    'date' => api_format_date($sale['date'], DATE_FORMAT_LONG_NO_DAY),
                    'product' => $sale['product_name'],
                    'currency' => $currency['iso_code'],
                    'price' => $sale['price'],
                    'reference' => $sale['reference']
                ]
            );
            $messageTemplate->assign('transfer_accounts', $transferAccounts);

            api_mail_html(
                $userInfo['complete_name'],
                $userInfo['email'],
                $plugin->get_lang('bc_subject'),
                $messageTemplate->fetch('buycourses/view/message_transfer.tpl')
            );

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
            header('Location: ' . api_get_path(WEB_PLUGIN_PATH) . 'buycourses/src/course_catalog.php');
            exit;
        }

        $form->addButton('confirm', $plugin->get_lang('ConfirmOrder'), 'check', 'success');
        $form->addButtonCancel($plugin->get_lang('CancelOrder'), 'cancel');

        $template = new Template();

        if ($buyingCourse) {
            $template->assign('course', $course);
        } elseif ($buyingSession) {
            $template->assign('session', $session);
        }

        $template->assign('buying_course', $buyingCourse);
        $template->assign('buying_session', $buyingSession);

        $template->assign('title', $sale['product_name']);
        $template->assign('price', $sale['price']);
        $template->assign('currency', $sale['currency_id']);
        $template->assign('user', $userInfo);
        $template->assign('transfer_accounts', $transferAccounts);
        $template->assign('form', $form->returnForm());

        $content = $template->fetch('buycourses/view/process_confirm.tpl');

        $template->assign('content', $content);
        $template->display_one_col_template();
        break;
}
