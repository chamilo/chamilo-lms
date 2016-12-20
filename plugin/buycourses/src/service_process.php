<?php
/* For license terms, see /license.txt */
/**
 * Process payments for the Buy Courses plugin
 * @package chamilo.plugin.buycourses
 */
/**
 * Initialization
 */

$cidReset = true;

require_once '../config.php';

if (!isset($_REQUEST['t'], $_REQUEST['i'])) {
    header('Location: ' . api_get_path(WEB_PLUGIN_PATH) . 'buycourses/src/service_catalog.php');
}

$currentUserId = api_get_user_id();
$serviceId = intval($_REQUEST['i']);

if (empty($currentUserId)) {
    api_not_allowed(true);
}

$em = Database::getManager();
$plugin = BuyCoursesPlugin::create();
$includeServices = $plugin->get('include_services');
$paypalEnabled = $plugin->get('paypal_enable') === 'true';
$transferEnabled = $plugin->get('transfer_enable') === 'true';
$wizard = true;
$additionalQueryString = '';
if ($includeServices !== 'true') {
    api_not_allowed(true);
}

$typeUser = intval($_REQUEST['t']) === BuyCoursesPlugin::SERVICE_TYPE_USER;
$typeCourse = intval($_REQUEST['t']) === BuyCoursesPlugin::SERVICE_TYPE_COURSE;
$typeSession = intval($_REQUEST['t']) === BuyCoursesPlugin::SERVICE_TYPE_SESSION;
$queryString = 'i=' . intval($_REQUEST['i']) . '&t=' . intval($_REQUEST['t']).$additionalQueryString;

$serviceInfo = $plugin->getServices(intval($_REQUEST['i']));
$userInfo = api_get_user_info($currentUserId);

$form = new FormValidator('confirm_sale');

if ($form->validate()) {
    $formValues = $form->getSubmitValues();
    
    if (!$formValues['payment_type']) {
        Display::addFlash(
            Display::return_message($plugin->get_lang('NeedToSelectPaymentType'), 'error', false)
        );
        header('Location:' . api_get_self() . '?' . $queryString);
        exit;
    }

    if (!$formValues['info_select']) {
        Display::addFlash(
            Display::return_message($plugin->get_lang('AdditionalInfoRequired'), 'error', false)
        );
        header('Location:' . api_get_self() . '?' . $queryString);
        exit;
    }

    $userGroup = $em->getRepository('ChamiloCoreBundle:Usergroup')->findBy(['name' => $formValues['info_select']]);

    if ($userGroup) {
        Display::addFlash(
            Display::return_message($plugin->get_lang('StoreNameAlreadyExist'), 'error', false)
        );
        header('Location:' . api_get_self() . '?' . $queryString);
        exit;
    }
    
    $serviceSaleId = $plugin->registerServiceSale($serviceId, $formValues['payment_type'], $formValues['info_select'], $formValues['enable_trial']);

    if (!empty($formValues['store_code'])) {
        $data = [
            'store_code' => Security::remove_XSS($formValues['store_code']),
            'store_name' => Security::remove_XSS($formValues['info_select']),
            'parent_id' => 0,
            'description' => 'Registered by User in buying process',
            'type' => 1,
            'discount' => 0
        ];

        $verification = $plugin->getDiscountByCode($data['store_code']);

        if (!$verification) {
            $plugin->addDiscountCode($data);
        }
    }

    if ($serviceSaleId !== false) {
        $_SESSION['bc_service_sale_id'] = $serviceSaleId;
        
        if ($verification['discount'] == 100) {

            $serviceSale = $plugin->getServiceSale($serviceSaleId);

            $serviceSaleIsCompleted = $plugin->completeServiceSale($serviceSale['id']);
            if ($serviceSaleIsCompleted) {
                Display::addFlash(Display::return_message(sprintf($plugin->get_lang('SubscriptionToServiceXSuccessful'), $serviceSale['service']['name']), 'success'));

                $plugin->SendSubscriptionMail(intval($serviceSale['id']));

                unset($_SESSION['bc_service_sale_id']);

                header('Location: ' . api_get_path(WEB_PLUGIN_PATH) . 'buycourses/src/package_panel.php?id='.$serviceSale['id']);
                exit;
            }
        }
        
        if ($wizard) {
            header('Location: ' . api_get_path(WEB_PLUGIN_PATH) . 'buycourses/src/service_process_confirm.php?from=register');
        } else {
            header('Location: ' . api_get_path(WEB_PLUGIN_PATH) . 'buycourses/src/service_process_confirm.php');
        }
    }

    exit;
}

// Reset discount code
unset($_SESSION['s_discount']);

$paymentTypesOptions = $plugin->getPaymentTypes(true);

$form->addHeader('');
$form->addRadio('payment_type', null, $paymentTypesOptions);
$form->addHtml('<h3 class="panel-heading">'.$plugin->get_lang('AdditionalInfo').'</h3>');
$form->addHeader('');
$form->addHtml(Display::return_message($plugin->get_lang('PleaseSelectTheCorrectInfoToApplyTheService'), 'info'));
$selectOptions = [];

if ($typeUser) {
    $users = $em->getRepository('ChamiloUserBundle:User')->findAll();
    $selectOptions[$userInfo['user_id']] = api_get_person_name($userInfo['firstname'], $userInfo['lastname']) . ' (' . get_lang('Myself') . ')';
    if (!empty($users)) {
        foreach ($users as $user) {
            if (intval($userInfo['user_id']) !== intval($user->getId())) {
                $selectOptions[$user->getId()] = $user->getCompleteNameWithUsername();
            }
        }
    }
    $form->addSelect('info_select', get_lang('User'), $selectOptions);
} elseif ($typeCourse) {
    $user = $em->getRepository('ChamiloUserBundle:User')->find($currentUserId);
    $courses = $user->getCourses();
    if (!empty($courses)) {
        foreach ($courses as $course) {
            $selectOptions[$course->getCourse()->getId()] = $course->getCourse()->getTitle();
        }
    }
    $form->addSelect('info_select', get_lang('Course'), $selectOptions);
} elseif ($typeSession) {
    $user = $em->getRepository('ChamiloUserBundle:User')->find($currentUserId);
    $sessions = $user->getSessionCourseSubscriptions();
    if (!empty($sessions)) {
        foreach ($sessions as $session) {
            $selectOptions[$session->getSession()->getId()] = $session->getSession()->getName();
        }
    }
    $form->addSelect('info_select', get_lang('Session'), $selectOptions);
} elseif ($typeSubscriptionPackage) {
    $trial = intval($serviceInfo['allow_trial']);

    if ($trial) {
        $trialTime = $serviceInfo['trial_period'] == 'Month' ? get_lang($serviceInfo['trial_period']) . '(es)' : get_lang($serviceInfo['trial_period']) . '(s)';
        $form->addHtml('
            <div class="form-group ">
                <label for="qf_373cc5" class="col-sm-6">
                    ' . sprintf($plugin->get_lang('EnableTrialSubscription'), $serviceInfo['trial_frequency'] . ' ' . $trialTime) . '
                </label>
                <div class="col-sm-6">
                    <input cols-size="" name="enable_trial" value="1" id="qf_373cc5" type="checkbox"></div>
                <div class="col-sm-0"></div>
            </div>
            <div class="form-group ">
                <div class="col-sm-12">
                    <p class="help-block">' . sprintf($plugin->get_lang('EnableTrialSubscriptionHelpText'), $serviceInfo['trial_frequency'] . ' ' . $trialTime) . '</p>
                </div>
            </div>
        ');
    }
    $form->addText('store_code', $plugin->get_lang('DiscountCodeProcess'), true, ['cols-size' => [6, 6, 0], 'id' => 'store_code']);
    $form->addText('info_select_trick', $plugin->get_lang('StoreName'), true, ['cols-size' => [6, 6, 0], 'id' => 'info_select_trick']);
    $form->addHidden('info_select', '');
    $form->addHtml('
        <div class="form-group">
            <div class="col-sm-2 pull-right">
                <a id="code-checker" class="btn btn-xs btn-warning">' . $plugin->get_lang('Check') . '</a>
            </div>
            <div id="code-verificator-text" class="col-sm-4 pull-right">
            </div>
        </div>
        <div id="code-verificator-info">

        </div>
        <div class="form-group">
            <div class="col-sm-12">
                <p class="help-block">' . $plugin->get_lang('DiscountCodeInfoText') . '</p>
            </div>
        </div>
    ');
}


$form->addHidden('t', intval($_GET['t']));
$form->addHidden('i', intval($_GET['i']));

$form->addButton('submit', $plugin->get_lang('ConfirmOrder'), 'check', 'success');

// View
$templateName = $plugin->get_lang('PaymentMethods');
$interbreadcrumb[] = array("url" => "service_catalog.php", "name" => $plugin->get_lang('ListOfServicesOnSale'));

$tpl = new Template($templateName);
if (isset($_GET['from'])) {
    if($_GET['from'] == 'register') {
        $tpl->assign('wizard', true);
    }
}
$tpl->assign('buying_service', true);
$tpl->assign('service', $serviceInfo);
$tpl->assign('user', api_get_user_info());
$tpl->assign('form', $form->returnForm());


$content = $tpl->fetch('buycourses/view/process.tpl');

$tpl->assign('content', $content);
$tpl->display_one_col_template();
