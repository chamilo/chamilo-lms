<?php

declare(strict_types=1);

/* For license terms, see /license.txt */

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CLp;

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

$coupon = null;

if (isset($_REQUEST['c']) && '' !== trim((string) $_REQUEST['c'])) {
    $couponCode = trim((string) $_REQUEST['c']);
    $coupon = $plugin->getCouponServiceByCode($couponCode, $serviceId);
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

if (1 === count($paymentTypesOptions)) {
    $firstPaymentTypeId = (int) array_key_first($paymentTypesOptions);
    $form->setDefaults([
        'payment_type' => $firstPaymentTypeId,
    ]);
}

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
    $currentUserLabel = api_get_person_name(
            $userInfo['firstname'],
            $userInfo['lastname']
        ).' ('.get_lang('Myself').')';

    $form->addHidden('info_select', (string) $currentUserId);
    $form->addHtml(
        '<div class="rounded-2xl border border-gray-20 bg-support-2 p-4">'.
        '<div class="text-body-2 font-semibold text-primary">'.get_lang('User').'</div>'.
        '<div class="mt-2 text-body-2 font-medium text-gray-90">'.$currentUserLabel.'</div>'.
        '<div class="mt-1 text-caption text-gray-50">This service will be applied to your account.</div>'.
        '</div>'
    );
} elseif ($typeCourse) {
    /** @var User $user */
    $user = Container::getUserRepository()->find($currentUserId);
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
    $user = Container::getUserRepository()->find($currentUserId);
    $userSubscriptions = $user->getSessionRelCourseRelUsers();

    foreach ($userSubscriptions as $userSubscription) {
        $sessions[$userSubscription->getSession()->getId()] = $userSubscription->getSession()->getTitle();
    }

    $sessionsAsGeneralCoach = $user->getSessionsAsGeneralCoach();

    foreach ($sessionsAsGeneralCoach as $sessionAsGeneralCoach) {
        $sessions[$sessionAsGeneralCoach->getId()] = $sessionAsGeneralCoach->getTitle();
    }

    if (!$sessions) {
        $form->addHtml(
            Display::return_message(
                $plugin->get_lang('YouNeedToBeRegisteredInAtLeastOneSession'),
                'error'
            )
        );
    } else {
        $selectOptions = $sessions;
        $form->addSelect('info_select', get_lang('Session'), $selectOptions);
    }
} elseif ($typeFinalLp) {
    /** @var User $user */
    $user = Container::getUserRepository()->find($currentUserId);
    $lpRepo = Container::getLpRepository();
    $courseLpList = [];
    $sessionLpList = [];
    $checker = false;

    foreach ($user->getCourses() as $course) {
        $thisLpList = $lpRepo
            ->getResourcesByCourse($course->getCourse())
            ->getQuery()
            ->getResult();

        foreach ($thisLpList as $lp) {
            $courseLpList[$course->getId()] = $lp->getTitle().' ('.$course->getCourse()->getTitle().')';
        }
    }

    foreach ($user->getSessionRelCourseRelUsers() as $session) {
        $subscriptionCourse = $session->getCourse();
        $subscriptionSession = $session->getSession();

        /** @var array<int, CLp> $thisLpList */
        $thisLpList = $lpRepo
            ->getResourcesByCourse($subscriptionCourse, $subscriptionSession)
            ->getQuery()
            ->getResult();

        foreach ($thisLpList as $lp) {
            foreach ($lp->getItems() as $item) {
                if (TOOL_LP_FINAL_ITEM == $item->getItemType()) {
                    $checker = true;
                    $sessionLpList[$subscriptionCourse->getId()] = $lp->getTitle().' ('.$subscriptionSession->getTitle().')';
                }
            }
        }

        /** @var array<int, CLp> $thisLpList */
        $thisLpList = $lpRepo
            ->getResourcesByCourse($subscriptionCourse)
            ->getQuery()
            ->getResult();

        foreach ($thisLpList as $lp) {
            foreach ($lp->getItems() as $item) {
                if (TOOL_LP_FINAL_ITEM == $item->getItemType()) {
                    $checker = true;
                    $sessionLpList[$subscriptionCourse->getId()] = $lp->getTitle().' ('.$subscriptionSession->getTitle().')';
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

$form->addHtml(
    '<div class="mt-6 rounded-2xl border border-gray-20 bg-white p-4 shadow-sm">'.
    '<h3 class="mb-2 text-body-1 font-semibold text-gray-90">'.$plugin->get_lang('VATBuyerInformation').'</h3>'.
    '<p class="mb-4 text-body-2 text-gray-60">'.$plugin->get_lang('VATBuyerInformationHelp').'</p>'.
    '</div>'
);

$form->addSelect(
    'buyer_country',
    $plugin->get_lang('BuyerCountry'),
    $plugin->getVatCountryOptions()
);
$form->addText('buyer_postcode', $plugin->get_lang('BuyerPostcode'));
$form->addSelect(
    'buyer_type',
    $plugin->get_lang('BuyerType'),
    [
        'individual' => $plugin->get_lang('BuyerTypeIndividual'),
        'business' => $plugin->get_lang('BuyerTypeBusiness'),
    ]
);
$form->addText('buyer_vat_number', $plugin->get_lang('BuyerVatNumber'));
$form->addText('buyer_business_name', $plugin->get_lang('BuyerBusinessName'));
$form->addTextarea('buyer_business_address', $plugin->get_lang('BuyerBusinessAddress'));

$form->setDefaults([
    'buyer_type' => 'individual',
]);

$form->addHidden('t', $type);
$form->addHidden('i', $serviceId);
$form->addHidden('buycourses_service_checkout_action', 'confirm_order');

if (null !== $coupon) {
    $form->addHidden('c', (int) $coupon['id']);
}

$form->addButton('submit', $plugin->get_lang('ConfirmOrder'), 'check', 'success');

$checkoutAction = (string) ($_POST['buycourses_service_checkout_action'] ?? '');

if ('confirm_order' === $checkoutAction) {
    // Read posted values directly for the checkout action.
    // QuickForm validation can be affected by other forms on this page,
    // so the required checkout fields are validated explicitly below.
    $formValues = $_POST;

    if (!isset($formValues['payment_type'])) {
        Display::addFlash(
            Display::return_message(
                $plugin->get_lang('NeedToSelectPaymentType'),
                'error',
                false
            )
        );

        header('Location: '.api_get_self().'?'.$queryString);
        exit;
    }

    $infoSelected = 0;

    if ($typeUser) {
        $infoSelected = $currentUserId;
    } elseif ($infoRequired) {
        if (isset($formValues['info_select'])) {
            $infoSelected = (int) $formValues['info_select'];
        } else {
            Display::addFlash(
                Display::return_message(
                    $plugin->get_lang('AdditionalInfoRequired'),
                    'error',
                    false
                )
            );

            header('Location: '.api_get_self().'?'.$queryString);
            exit;
        }
    }

    $vatErrors = $plugin->validateVatBuyerData($formValues);

    if (!empty($vatErrors)) {
        foreach ($vatErrors as $vatError) {
            Display::addFlash(
                Display::return_message(
                    $vatError,
                    'error',
                    false
                )
            );
        }

        header('Location: '.api_get_self().'?'.$queryString);
        exit;
    }

    $serviceSaleId = $plugin->registerServiceSale(
        $serviceId,
        (int) $formValues['payment_type'],
        (int) $infoSelected,
        $formValues['c'] ?? null,
        $formValues
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
        exit;
    }

    exit;
}

$formCoupon = new FormValidator('confirm_coupon');
$formCoupon->addText('coupon_code', $plugin->get_lang('CouponsCode'), true);
$formCoupon->addHidden('t', $type);
$formCoupon->addHidden('i', $serviceId);
$formCoupon->addHidden('buycourses_service_checkout_action', 'coupon');
$formCoupon->addButton('submit', $plugin->get_lang('RedeemCoupon'), 'check', 'success', 'btn-lg pull-right');

if ('coupon' === $checkoutAction) {
    $formCouponValues = $_POST;
    $couponCode = trim((string) ($formCouponValues['coupon_code'] ?? ''));

    if ('' === $couponCode) {
        Display::addFlash(
            Display::return_message(
                $plugin->get_lang('NeedToAddCouponCode'),
                'error',
                false
            )
        );

        header('Location: '.api_get_self().'?'.$queryString);
        exit;
    }

    $coupon = $plugin->getCouponServiceByCode($couponCode, $serviceId);

    if (null === $coupon) {
        Display::addFlash(
            Display::return_message(
                $plugin->get_lang('CouponNotValid'),
                'error',
                false
            )
        );

        header('Location: '.api_get_self().'?'.$queryString);
        exit;
    }

    Display::addFlash(
        Display::return_message(
            $plugin->get_lang('CouponRedeemed'),
            'success',
            false
        )
    );

    header(
        'Location: '.api_get_path(WEB_PLUGIN_PATH)
        .'BuyCourses/src/service_process.php?i='.$serviceId.'&t='.$type.'&c='.urlencode($couponCode)
    );
    exit;
}

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
