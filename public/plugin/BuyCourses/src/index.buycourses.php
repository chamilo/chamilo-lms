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
 * landing page (one section per non-empty catalog list), so visitors see the
 * products without first having to pick a product type. Each section shows a
 * preview (BuyCoursesPlugin::PAGINATION_PAGE_SIZE items) and links to the full
 * catalog when more items exist. Admins keep the navigation/configuration grid.
 */
$sections = [];
$canBuyServices = false;
$buyerRoleNotice = null;

if (!api_is_platform_admin()) {
    $previewSize = BuyCoursesPlugin::PAGINATION_PAGE_SIZE;

    $courseItems = $plugin->getCatalogCourseList(0, $previewSize);
    $courseTotal = (int) $plugin->getCatalogCourseList(0, $previewSize, null, 0, 0, 'count');
    if ($courseTotal > 0) {
        $sections[] = [
            'card' => 'course',
            'title' => get_lang('Courses'),
            'items' => $courseItems,
            'total' => $courseTotal,
            'see_all_url' => 'src/course_catalog.php',
            'buy_script' => 'process.php',
            'buy_type' => 1,
        ];
    }

    if ($includeSessions) {
        $sessionItems = $plugin->getCatalogSessionList(0, $previewSize, null, 0, 0, 'all', 0);
        $sessionTotal = (int) $plugin->getCatalogSessionList(0, $previewSize, null, 0, 0, 'count', 0);
        if ($sessionTotal > 0) {
            $sections[] = [
                'card' => 'session',
                'title' => get_lang('Sessions'),
                'items' => $sessionItems,
                'total' => $sessionTotal,
                'see_all_url' => 'src/session_catalog.php',
                'buy_script' => 'process.php',
                'buy_type' => 2,
            ];
        }
    }

    if ($includeServices) {
        $appliesTo = (string) BuyCoursesPlugin::SERVICE_TYPE_USER;
        $serviceItems = $plugin->getCatalogServiceList(0, $previewSize, null, 0, 0, $appliesTo);
        $serviceTotal = (int) $plugin->getCatalogServiceList(0, $previewSize, null, 0, 0, $appliesTo, 'count');
        if ($serviceTotal > 0) {
            try {
                $selectedCurrency = $plugin->getSelectedCurrency();
            } catch (Exception) {
                $selectedCurrency = [];
            }
            $selectedCurrencyIsoCode = (string) ($selectedCurrency['iso_code'] ?? '');

            foreach ($serviceItems as &$service) {
                $serviceId = (int) $service['id'];
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
            }
            unset($service);

            $canBuyServices = api_is_allowed_to_create_course();
            if (!$canBuyServices) {
                $buyerRoleNotice = $plugin->get_lang('ServicesOnlyForTeachers');
            }

            $sections[] = [
                'card' => 'service',
                'title' => $plugin->get_lang('Services'),
                'items' => $serviceItems,
                'total' => $serviceTotal,
                'see_all_url' => 'src/service_catalog.php',
            ];
        }
    }

    $subscriptionCourseItems = $plugin->getCatalogSubscriptionCourseList(0, $previewSize);
    $subscriptionCourseTotal = (int) $plugin->getCatalogSubscriptionCourseList(0, $previewSize, null, 'count');
    if ($subscriptionCourseTotal > 0) {
        $sections[] = [
            'card' => 'course',
            'title' => $plugin->get_lang('Subscriptions').' – '.get_lang('Courses'),
            'items' => $subscriptionCourseItems,
            'total' => $subscriptionCourseTotal,
            'see_all_url' => 'src/subscription_course_catalog.php',
            'buy_script' => 'subscription_process.php',
            'buy_type' => 1,
        ];
    }

    if ($includeSessions) {
        $subscriptionSessionItems = $plugin->getCatalogSubscriptionSessionList(0, $previewSize, null, 'all', 0);
        $subscriptionSessionTotal = (int) $plugin->getCatalogSubscriptionSessionList(0, $previewSize, null, 'count', 0);
        if ($subscriptionSessionTotal > 0) {
            $sections[] = [
                'card' => 'session',
                'title' => $plugin->get_lang('Subscriptions').' – '.get_lang('Sessions'),
                'items' => $subscriptionSessionItems,
                'total' => $subscriptionSessionTotal,
                'see_all_url' => 'src/subscription_session_catalog.php',
                'buy_script' => 'subscription_process.php',
                'buy_type' => 2,
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
