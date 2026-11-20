<?php

declare(strict_types=1);

/* For license terms, see /license.txt */

/**
 * List and delete purchase-attempt reminders of the Buy Courses plugin.
 */
$cidReset = true;

require_once '../config.php';
require_once __DIR__.'/admin_token.lib.php';

api_protect_admin_script();

$plugin = BuyCoursesPlugin::create();
$helper = $plugin->getPurchaseReminderHelper();

if ('POST' === ($_SERVER['REQUEST_METHOD'] ?? 'GET') && isset($_POST['action'], $_POST['reminder_id'])) {
    buycoursesRequireAdminToken(api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/reminders.php');

    $reminderId = (int) $_POST['reminder_id'];
    if ('delete' === (string) $_POST['action']) {
        if ($helper->deleteReminder($reminderId)) {
            Display::addFlash(Display::return_message($plugin->get_lang('PurchaseReminderDeleted'), 'success', false));
        } else {
            Display::addFlash(Display::return_message($plugin->get_lang('PurchaseReminderNotFound'), 'warning', false));
        }
    }

    header('Location: '.api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/reminders.php');
    exit;
}

$reminders = $helper->listReminders();
$discountTypes = $plugin->getCouponDiscountTypes();
$currency = $plugin->getSelectedCurrency();
$currencyIso = $currency['iso_code'] ?? '';

foreach ($reminders as &$reminder) {
    $discountType = (int) ($reminder['discount_type'] ?? 0);
    if ($discountType > 0) {
        if (BuyCoursesPlugin::COUPON_DISCOUNT_TYPE_PERCENTAGE === $discountType) {
            $reminder['discount_value'] = (float) $reminder['discount_amount'].' %';
        } elseif ($currencyIso) {
            $reminder['discount_value'] = $plugin->getPriceWithCurrencyFromIsoCode(
                (float) $reminder['discount_amount'],
                $currencyIso
            );
        } else {
            $reminder['discount_value'] = (string) $reminder['discount_amount'];
        }
        $reminder['discount_type_label'] = $discountTypes[$discountType] ?? (string) $discountType;
    } else {
        $reminder['discount_value'] = $plugin->get_lang('PurchaseReminderNoDiscount');
        $reminder['discount_type_label'] = '—';
    }
    $reminder['product_count'] = \count($reminder['products'] ?? []);
}
unset($reminder);

$interbreadcrumb[] = [
    'url' => '../index.php',
    'name' => $plugin->get_lang('plugin_title'),
];

$templateName = $plugin->get_lang('PurchaseReminders');
$template = new Template($templateName);
$template->assign('page_title', $templateName);
$template->assign('plugin_title', $plugin->get_lang('plugin_title'));
$template->assign('back_url', api_get_path(WEB_PLUGIN_PATH).'BuyCourses/index.php');
$template->assign('new_reminder_url', api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/reminder_add.php');
$template->assign('reminder_list', $reminders);
$template->assign('csrf_token_name', buycoursesAdminTokenName());
$template->assign('csrf_token', buycoursesAdminToken());
$template->assign('cron_is_stale', $helper->cronIsStale());
$template->assign('feature_enabled', $helper->isFeatureEnabled($plugin));
$template->assign('cron_command', 'php bin/console buycourses:send-purchase-reminders');

$content = $template->fetch('BuyCourses/view/reminders.tpl');
$template->assign('header', $templateName);
$template->assign('content', $content);
$template->display_one_col_template();
