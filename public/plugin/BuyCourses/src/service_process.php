<?php

declare(strict_types=1);
/* For license terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CoreBundle\Entity\User;

/**
 * Process payments for the Buy Courses plugin.
 */
$cidReset = true;

require_once '../config.php';

if (!isset($_REQUEST['t'], $_REQUEST['i'])) {
    header('Location: '.api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/service_catalog.php');

    exit;
}

$currentUserId = api_get_user_id();
$serviceId = (int) $_REQUEST['i'];
$type = (int) $_REQUEST['t'];

if (empty($currentUserId)) {
    api_not_allowed(true);
}
$htmlHeadXtra[] = '<link rel="stylesheet" type="text/css" href="'.api_get_path(
    WEB_PLUGIN_PATH
).'BuyCourses/resources/css/style.css"/>';
$em = Database::getManager();
$plugin = BuyCoursesPlugin::create();
$includeServices = $plugin->get('include_services');
$additionalQueryString = '';
if ('true' !== $includeServices) {
    api_not_allowed(true);
}

$typeUser = BuyCoursesPlugin::SERVICE_TYPE_USER === $type;
$typeCourse = BuyCoursesPlugin::SERVICE_TYPE_COURSE === $type;
$typeSession = BuyCoursesPlugin::SERVICE_TYPE_SESSION === $type;
$typeFinalLp = BuyCoursesPlugin::SERVICE_TYPE_LP_FINAL_ITEM === $type;
$queryString = 'i='.$serviceId.'&t='.$type.$additionalQueryString;

if (isset($_REQUEST['c'])) {
    $couponCode = $_REQUEST['c'];
    $coupon = $plugin->getCouponServiceByCode($couponCode, $_REQUEST['i']);
}

$serviceInfo = $plugin->getService($serviceId, $coupon);
$userInfo = api_get_user_info($currentUserId);

$form = new FormValidator('confirm_sale');
$paymentTypesOptions = $plugin->getPaymentTypes(true);

$form->addHtml(
    Display::return_message(
        $plugin->get_lang('PleaseSelectThePaymentMethodBeforeConfirmYourOrder'),
        'info'
    )
);
$form->addRadio('payment_type', null, $paymentTypesOptions);

$infoRequired = false;
if ($typeUser || $typeCourse || $typeSession || $typeFinalLp) {
    $infoRequired = true;
    $form->addHtml(
        Display::return_message(
            $plugin->get_lang('PleaseSelectTheCorrectInfoToApplyTheService'),
            'info'
        )
    );
}

$selectOptions = [
    0 => get_lang('None'),
];

if ($typeUser) {
    $users = UserManager::getRepository()->findAll();
    $selectOptions[$userInfo['user_id']] = api_get_person_name(
        $userInfo['firstname'],
        $userInfo['lastname']
    ).' ('.get_lang('Myself').')';

    if (!empty($users)) {
        /** @var User $user */
        foreach ($users as $user) {
            if ((int) $userInfo['user_id'] !== (int) $user->getId()) {
                $selectOptions[$user->getId()] = $user->getFullNameWithUsername();
            }
        }
    }
    $form->addSelect('info_select', get_lang('User'), $selectOptions);
} elseif ($typeCourse) {
    /** @var User $user */
    $user = UserManager::getRepository()->find($currentUserId);
    $courses = $user->getCourses();
    $checker = false;
    foreach ($courses as $course) {
        $checker = true;
        $selectOptions[$course->getCourse()->getId()] = $course->getCourse()->getTitle();
    }
    if (!$checker) {
        $form->addHtml(
            Display::return_message(
                $plugin->get_lang('YouNeedToBeRegisteredInAtLeastOneCourse'),
                'error'
            )
        );
    }
    $form->addSelect('info_select', get_lang('Course'), $selectOptions);
} elseif ($typeSession) {
    $sessions = [];

    /** @var User $user */
    $user = UserManager::getRepository()->find($currentUserId);
    $userSubscriptions = $user->getSessionCourseSubscriptions();

    /** @var SessionRelCourseRelUser $userSubscription */
    foreach ($userSubscriptions as $userSubscription) {
        $sessions[$userSubscription->getSession()->getId()] = $userSubscription->getSession()->getName();
    }

    $sessionsAsGeneralCoach = $user->getSessionAsGeneralCoach();

    /** @var Session $sessionAsGeneralCoach */
    foreach ($sessionsAsGeneralCoach as $sessionAsGeneralCoach) {
        $sessions[$sessionAsGeneralCoach->getId()] = $sessionAsGeneralCoach->getName();
    }

    if (!$sessions) {
        $form->addHtml(Display::return_message($plugin->get_lang('YouNeedToBeRegisteredInAtLeastOneSession'), 'error'));
    } else {
        $selectOptions = $sessions;
        $form->addSelect('info_select', get_lang('Session'), $selectOptions);
    }
} elseif ($typeFinalLp) {
    // We need here to check the current user courses first
    /** @var User $user */
    $user = UserManager::getRepository()->find($currentUserId);
    $courses = $user->getCourses();
    $courseLpList = [];
    $sessionLpList = [];
    $checker = false;
    foreach ($courses as $course) {
        // Now get all the courses lp's
        $thisLpList = $em->getRepository('ChamiloCourseBundle:CLp')->findBy(['cId' => $course->getCourse()->getId()]);
        foreach ($thisLpList as $lp) {
            $courseLpList[$lp->getCId()] = $lp->getName().' ('.$course->getCourse()->getTitle().')';
        }
    }

    // Here now checking the current user sessions
    $sessions = $user->getSessionCourseSubscriptions();
    foreach ($sessions as $session) {
        $thisLpList = $em
            ->getRepository('ChamiloCourseBundle:CLp')
            ->findBy(['sessionId' => $session->getSession()->getId()])
        ;

        // Here check all the lpItems
        foreach ($thisLpList as $lp) {
            $thisLpItems = $em->getRepository('ChamiloCourseBundle:CLpItem')->findBy(['lpId' => $lp->getId()]);

            foreach ($thisLpItems as $item) {
                // Now only we need the final item and return the current LP
                if (TOOL_LP_FINAL_ITEM == $item->getItemType()) {
                    $checker = true;
                    $sessionLpList[$lp->getCId()] = $lp->getName().' ('.$session->getSession()->getName().')';
                }
            }
        }

        $thisLpList = $em->getRepository('ChamiloCourseBundle:CLp')->findBy(['cId' => $session->getCourse()->getId()]);

        // Here check all the lpItems
        foreach ($thisLpList as $lp) {
            $thisLpItems = $em->getRepository('ChamiloCourseBundle:CLpItem')->findBy(['lpId' => $lp->getId()]);
            foreach ($thisLpItems as $item) {
                // Now only we need the final item and return the current LP
                if (TOOL_LP_FINAL_ITEM == $item->getItemType()) {
                    $checker = true;
                    $sessionLpList[$lp->getCId()] = $lp->getName().' ('.$session->getSession()->getName().')';
                }
            }
        }
    }

    $selectOptions = $selectOptions + $courseLpList + $sessionLpList;
    if (!$checker) {
        $form->addHtml(
            Display::return_message(
                $plugin->get_lang('YourCoursesNeedAtLeastOneLearningPath'),
                'error'
            )
        );
    }
    $form->addSelect('info_select', get_lang('LearningPath'), $selectOptions);
}

$form->addHidden('t', (int) $_GET['t']);
$form->addHidden('i', (int) $_GET['i']);
$form->addButton('submit', $plugin->get_lang('ConfirmOrder'), 'check', 'success');

if ($form->validate()) {
    $formValues = $form->getSubmitValues();

    if (!isset($formValues['payment_type'])) {
        Display::addFlash(
            Display::return_message($plugin->get_lang('NeedToSelectPaymentType'), 'error', false)
        );
        header('Location:'.api_get_self().'?'.$queryString);

        exit;
    }

    $infoSelected = [];
    if ($infoRequired) {
        if (isset($formValues['info_select'])) {
            $infoSelected = $formValues['info_select'];
        } else {
            Display::addFlash(
                Display::return_message($plugin->get_lang('AdditionalInfoRequired'), 'error', false)
            );
            header('Location:'.api_get_self().'?'.$queryString);

            exit;
        }
    }

    $serviceSaleId = $plugin->registerServiceSale(
        $serviceId,
        $formValues['payment_type'],
        $infoSelected,
        $formValues['c']
    );

    if (false !== $serviceSaleId) {
        $_SESSION['bc_service_sale_id'] = $serviceSaleId;

        if (isset($formValues['c'])) {
            $couponSaleId = $plugin->registerCouponServiceSale($serviceSaleId, $formValues['c']);
            if (false !== $couponSaleId) {
                $plugin->updateCouponDelivered($formValues['c']);
                $_SESSION['bc_coupon_id'] = $formValues['c'];
            }
        }

        header('Location: '.api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/service_process_confirm.php');
    }

    exit;
}

$formCoupon = new FormValidator('confirm_coupon');
if ($formCoupon->validate()) {
    $formCouponValues = $formCoupon->getSubmitValues();

    if (!$formCouponValues['coupon_code']) {
        Display::addFlash(
            Display::return_message($plugin->get_lang('NeedToAddCouponCode'), 'error', false)
        );
        header('Location:'.api_get_self().'?'.$queryString);

        exit;
    }

    $coupon = $plugin->getCouponServiceByCode($formCouponValues['coupon_code'], $formCouponValues['i']);

    if (null == $coupon) {
        Display::addFlash(
            Display::return_message($plugin->get_lang('CouponNotValid'), 'error', false)
        );
        header('Location:'.api_get_self().'?'.$queryString);

        exit;
    }

    Display::addFlash(
        Display::return_message($plugin->get_lang('CouponRedeemed'), 'success', false)
    );

    header('Location: '.api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/service_process.php?i='.$_REQUEST['i'].'&t='.$_REQUEST['t'].'&c='.$formCouponValues['coupon_code']);

    exit;
}
$formCoupon->addText('coupon_code', $plugin->get_lang('CouponsCode'), true);
$formCoupon->addHidden('t', (int) $_GET['t']);
$formCoupon->addHidden('i', (int) $_GET['i']);
if (null != $coupon) {
    $form->addHidden('c', (int) $coupon['id']);
}
$formCoupon->addButton('submit', $plugin->get_lang('RedeemCoupon'), 'check', 'success', 'btn-lg pull-right');

// View
$templateName = $plugin->get_lang('PaymentMethods');
$interbreadcrumb[] = [
    'url' => 'service_catalog.php',
    'name' => $plugin->get_lang('ListOfServicesOnSale'),
];

$tpl = new Template($templateName);
$tpl->assign('buying_service', true);
$tpl->assign('service', $serviceInfo);
$tpl->assign('user', api_get_user_info());
$tpl->assign('form_coupon', $formCoupon->returnForm());
$tpl->assign('form', $form->returnForm());
$content = $tpl->fetch('BuyCourses/view/service_process.tpl');
$tpl->assign('content', $content);
$tpl->display_one_col_template();
