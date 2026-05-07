<?php

declare(strict_types=1);

/* For license terms, see /license.txt */

require_once '../config.php';

$plugin = BuyCoursesPlugin::create();

Display::addFlash(
    Display::return_message(
        $plugin->get_lang('StripeCheckoutCompletedPendingConfirmation'),
        'success',
        false
    )
);

header('Location: '.api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/service_catalog.php');
exit;
