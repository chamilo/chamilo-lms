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

$content = $tpl->fetch('BuyCourses/view/index.tpl');
$tpl->assign('content', $content);
$tpl->display_one_col_template(false);
