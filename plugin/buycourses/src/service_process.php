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
$culqiEnabled = $plugin->get('culqi_enable') === 'true';
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
    
    $serviceSaleId = $plugin->registerServiceSale($serviceId, $formValues['payment_type'], $formValues['info_select'], $formValues['enable_trial']);

    if ($serviceSaleId !== false) {
        $_SESSION['bc_service_sale_id'] = $serviceSaleId;

        header('Location: ' . api_get_path(WEB_PLUGIN_PATH) . 'buycourses/src/service_process_confirm.php');
    }

    exit;
}

$paymentTypesOptions = $plugin->getPaymentTypes();

if (!$paypalEnabled) {
    unset($paymentTypesOptions[BuyCoursesPlugin::PAYMENT_TYPE_PAYPAL]);
}

if (!$transferEnabled) {
    unset($paymentTypesOptions[BuyCoursesPlugin::PAYMENT_TYPE_TRANSFER]);
}

if (!$culqiEnabled) {
    unset($paymentTypesOptions[BuyCoursesPlugin::PAYMENT_TYPE_CULQI]);
}

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
}


$form->addHidden('t', intval($_GET['t']));
$form->addHidden('i', intval($_GET['i']));

$form->addButton('submit', $plugin->get_lang('ConfirmOrder'), 'check', 'success');

// View
$templateName = $plugin->get_lang('PaymentMethods');
$interbreadcrumb[] = array("url" => "service_catalog.php", "name" => $plugin->get_lang('ListOfServicesOnSale'));

$tpl = new Template($templateName);

$tpl->assign('buying_service', true);
$tpl->assign('service', $serviceInfo);
$tpl->assign('user', api_get_user_info());
$tpl->assign('form', $form->returnForm());


$content = $tpl->fetch('buycourses/view/process.tpl');

$tpl->assign('content', $content);
$tpl->display_one_col_template();
