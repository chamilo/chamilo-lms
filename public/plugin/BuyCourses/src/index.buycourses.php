<?php

declare(strict_types=1);

/* For license terms, see /license.txt */

use ChamiloSession as Session;

/**
 * Entry point of the Buy Courses plugin landing page.
 */
$plugin = BuyCoursesPlugin::create();

$allowAnonymousUsers = 'true' === $plugin->get('unregistered_users_enable');
$includeServices = 'true' === $plugin->get('include_services');
$includeSessions = 'true' === $plugin->get('include_sessions');
$userIsAnonymous = api_is_anonymous();

$registrationUrl = api_get_path(WEB_CODE_PATH).'auth/registration.php';

$normalizeRelativePath = static function (string $url): string {
    $path = (string) parse_url($url, PHP_URL_PATH);
    $query = (string) parse_url($url, PHP_URL_QUERY);

    if ('' === $path) {
        $path = '/plugin/BuyCourses/index.php';
    }

    return '' !== $query ? $path.'?'.$query : $path;
};

$pluginIndexUrl = api_get_path(WEB_PLUGIN_PATH).'BuyCourses/index.php';
$pluginIndexPath = $normalizeRelativePath($pluginIndexUrl);

if ($userIsAnonymous && !$allowAnonymousUsers) {
    Session::write('buy_course_redirect', $pluginIndexPath);
    header('Location: '.$registrationUrl);
    exit;
}

$tpl = new Template();
$tpl->assign('services_are_included', $includeServices);

/*
 * For non-admin users, build the product sections rendered directly on the
 * landing page (one section per non-empty product family), so visitors see the
 * products without first having to pick a product type. Each section shows a
 * preview (BuyCoursesPlugin::PAGINATION_PAGE_SIZE items) and links to the full
 * catalog when more items exist. Admins keep the navigation/configuration grid.
 */
$sections = [];
$canBuyServices = false;
$buyerRoleNotice = null;

if (!api_is_platform_admin()) {
    $previewSize = BuyCoursesPlugin::PAGINATION_PAGE_SIZE;
    $withBuyMetadata = static function (array $items, string $buyScript, int $buyType): array {
        foreach ($items as &$item) {
            $item['buy_script'] = $buyScript;
            $item['buy_type'] = $buyType;
        }
        unset($item);

        return $items;
    };

    if ($includeServices) {
        $appliesTo = (string) BuyCoursesPlugin::SERVICE_TYPE_USER;
        $billingCycleFilter = isset($_GET['billing_cycle'])
            ? trim((string) $_GET['billing_cycle'])
            : 'monthly';
        $allowedBillingCycleFilters = [
            'monthly',
            'yearly',
        ];
        if (!in_array($billingCycleFilter, $allowedBillingCycleFilters, true)) {
            $billingCycleFilter = 'monthly';
        }

        $monthlyServiceTotal = (int) $plugin->getCatalogServiceList(
            0,
            $previewSize,
            null,
            0,
            0,
            $appliesTo,
            'count',
            'monthly'
        );
        $yearlyServiceTotal = (int) $plugin->getCatalogServiceList(
            0,
            $previewSize,
            null,
            0,
            0,
            $appliesTo,
            'count',
            'yearly'
        );
        $availableBillingCycles = [];
        if ($monthlyServiceTotal > 0) {
            $availableBillingCycles['monthly'] = [
                'label' => $plugin->get_lang('MonthlyPlans'),
                'total' => $monthlyServiceTotal,
            ];
        }
        if ($yearlyServiceTotal > 0) {
            $availableBillingCycles['yearly'] = [
                'label' => $plugin->get_lang('YearlyPlans'),
                'total' => $yearlyServiceTotal,
            ];
        }

        $serviceCatalogTotal = $monthlyServiceTotal + $yearlyServiceTotal;
        if ($serviceCatalogTotal > 0) {
            if (!isset($availableBillingCycles[$billingCycleFilter])) {
                $billingCycleFilter = (string) array_key_first($availableBillingCycles);
            }

            $serviceItems = $plugin->getCatalogServiceList(
                0,
                $previewSize,
                null,
                0,
                0,
                $appliesTo,
                'all',
                $billingCycleFilter
            );
            $serviceTotal = (int) $plugin->getCatalogServiceList(
                0,
                $previewSize,
                null,
                0,
                0,
                $appliesTo,
                'count',
                $billingCycleFilter
            );

            try {
                $selectedCurrency = $plugin->getSelectedCurrency();
            } catch (Exception) {
                $selectedCurrency = [];
            }
            $selectedCurrencyIsoCode = (string) ($selectedCurrency['iso_code'] ?? '');

            foreach ($serviceItems as &$service) {
                $serviceId = (int) $service['id'];
                $durationDays = (int) ($service['duration_days'] ?? 0);
                $service['description'] = $plugin->filterServiceMultilingualHtml((string) ($service['description'] ?? ''));
                $service['service_information'] = $plugin->filterServiceMultilingualHtml((string) ($service['service_information'] ?? ''));
                $service['has_blocking_sale'] = $plugin->hasBlockingUserServiceSaleForCurrentBuyer($serviceId);
                $service['has_pending_sale'] = $plugin->hasPendingUserServiceSaleForCurrentBuyer($serviceId);

                $isoCode = (string) ($service['iso_code'] ?? $selectedCurrencyIsoCode);
                $priceValue = (float) ($service['total_price'] ?? 0);
                if (!empty($service['total_price_formatted'])) {
                    $service['display_price'] = (string) $service['total_price_formatted'];
                } elseif ('' !== $isoCode) {
                    $service['display_price'] = $plugin->getPriceWithCurrencyFromIsoCode($priceValue, $isoCode);
                } else {
                    $service['display_price'] = number_format($priceValue, 2, '.', ',');
                }

                $service['billing_cycle_label'] = $durationDays >= 365
                    ? $plugin->get_lang('YearlyPlan')
                    : $plugin->get_lang('MonthlyPlan');

                $service['duration_label'] = $durationDays > 0
                    ? sprintf($plugin->get_lang('ServiceDurationXDays'), $durationDays)
                    : '';
            }
            unset($service);

            $canBuyServices = api_is_allowed_to_create_course();
            if (!$canBuyServices) {
                $buyerRoleNotice = $plugin->get_lang('ServicesOnlyForTeachers');
            }

            $billingCycleTabs = [];
            if (count($availableBillingCycles) > 1) {
                foreach ($availableBillingCycles as $cycle => $billingCycle) {
                    $billingCycleTabs[] = [
                        'label' => $billingCycle['label'],
                        'url' => 'index.php?'.http_build_query(['billing_cycle' => $cycle]),
                        'active' => $cycle === $billingCycleFilter,
                    ];
                }
            }

            $sections[] = [
                'card' => 'service',
                'title' => $plugin->get_lang('Services'),
                'items' => $serviceItems,
                'total' => $serviceTotal,
                'see_all_url' => 'src/service_catalog.php?'.http_build_query(['billing_cycle' => $billingCycleFilter]),
                'billing_cycle_tabs' => $billingCycleTabs,
            ];
        }
    }

    $courseItems = $withBuyMetadata(
        $plugin->getCatalogCourseList(0, $previewSize),
        'process.php',
        BuyCoursesPlugin::PRODUCT_TYPE_COURSE
    );
    $courseTotal = (int) $plugin->getCatalogCourseList(0, $previewSize, null, 0, 0, 'count');
    $subscriptionCourseItems = $withBuyMetadata(
        $plugin->getCatalogSubscriptionCourseList(0, $previewSize),
        'subscription_process.php',
        BuyCoursesPlugin::PRODUCT_TYPE_COURSE
    );
    $subscriptionCourseTotal = (int) $plugin->getCatalogSubscriptionCourseList(0, $previewSize, null, 'count');
    $courseCatalogTotal = $courseTotal + $subscriptionCourseTotal;
    if ($courseCatalogTotal > 0) {
        $courseSectionItems = array_slice(
            array_merge($courseItems, $subscriptionCourseItems),
            0,
            $previewSize
        );
        $courseSeeAllLinks = [];
        if ($courseCatalogTotal > count($courseSectionItems)) {
            if ($courseTotal > 0) {
                $courseSeeAllLinks[] = [
                    'label' => get_lang('Courses'),
                    'url' => 'src/course_catalog.php',
                    'total' => $courseTotal,
                ];
            }
            if ($subscriptionCourseTotal > 0) {
                $courseSeeAllLinks[] = [
                    'label' => get_lang('Courses'),
                    'url' => 'src/subscription_course_catalog.php',
                    'total' => $subscriptionCourseTotal,
                ];
            }
        }

        $sections[] = [
            'card' => 'course',
            'title' => get_lang('Courses'),
            'items' => $courseSectionItems,
            'total' => $courseCatalogTotal,
            'buy_script' => 'process.php',
            'buy_type' => BuyCoursesPlugin::PRODUCT_TYPE_COURSE,
            'see_all_links' => $courseSeeAllLinks,
        ];
    }

    if ($includeSessions) {
        $sessionItems = $withBuyMetadata(
            $plugin->getCatalogSessionList(0, $previewSize, null, 0, 0, 'all', 0),
            'process.php',
            BuyCoursesPlugin::PRODUCT_TYPE_SESSION
        );
        $sessionTotal = (int) $plugin->getCatalogSessionList(0, $previewSize, null, 0, 0, 'count', 0);
        $subscriptionSessionItems = $withBuyMetadata(
            $plugin->getCatalogSubscriptionSessionList(0, $previewSize, null, 'all', 0),
            'subscription_process.php',
            BuyCoursesPlugin::PRODUCT_TYPE_SESSION
        );
        $subscriptionSessionTotal = (int) $plugin->getCatalogSubscriptionSessionList(0, $previewSize, null, 'count', 0);
        $sessionCatalogTotal = $sessionTotal + $subscriptionSessionTotal;
        if ($sessionCatalogTotal > 0) {
            $sessionSectionItems = array_slice(
                array_merge($sessionItems, $subscriptionSessionItems),
                0,
                $previewSize
            );
            $sessionSeeAllLinks = [];
            if ($sessionCatalogTotal > count($sessionSectionItems)) {
                if ($sessionTotal > 0) {
                    $sessionSeeAllLinks[] = [
                        'label' => get_lang('Sessions'),
                        'url' => 'src/session_catalog.php',
                        'total' => $sessionTotal,
                    ];
                }
                if ($subscriptionSessionTotal > 0) {
                    $sessionSeeAllLinks[] = [
                        'label' => get_lang('Sessions'),
                        'url' => 'src/subscription_session_catalog.php',
                        'total' => $subscriptionSessionTotal,
                    ];
                }
            }

            $sections[] = [
                'card' => 'session',
                'title' => get_lang('Sessions'),
                'items' => $sessionSectionItems,
                'total' => $sessionCatalogTotal,
                'buy_script' => 'process.php',
                'buy_type' => BuyCoursesPlugin::PRODUCT_TYPE_SESSION,
                'see_all_links' => $sessionSeeAllLinks,
            ];
        }
    }
}

$tpl->assign('sections', $sections);
$tpl->assign('can_buy_services', $canBuyServices);
$tpl->assign('buyer_role_notice', $buyerRoleNotice);

$content = $tpl->fetch('BuyCourses/view/index.tpl');
$tpl->assign('content', $content);
$tpl->display_one_col_template(false);
