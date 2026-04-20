<?php

declare(strict_types=1);

/* For license terms, see /license.txt */

/**
 * List page for PayPal Payout for the Buy Courses plugin.
 */

use Throwable;

/**
 * Initialization.
 */
$cidReset = true;

require_once __DIR__.'/../../../main/inc/global.inc.php';

function formatPayoutAmount(BuyCoursesPlugin $plugin, float $amount, mixed $isoCode): string
{
    $normalizedIsoCode = strtoupper(trim((string) $isoCode));

    if ('' === $normalizedIsoCode) {
        return number_format($amount, 2, '.', ',');
    }

    try {
        return $plugin->getPriceWithCurrencyFromIsoCode($amount, $normalizedIsoCode);
    } catch (Throwable) {
        return number_format($amount, 2, '.', ',').' '.$normalizedIsoCode;
    }
}

api_protect_admin_script(true);

$plugin = BuyCoursesPlugin::create();

$paypalEnable = 'true' === $plugin->get('paypal_enable');
$commissionsEnable = 'true' === $plugin->get('commissions_enable');

if (!$paypalEnable || !$commissionsEnable) {
    api_not_allowed(true);
}

$payouts = $plugin->getPayouts();
$payouts = is_array($payouts) ? $payouts : [];

$payoutList = [];
$eligiblePayoutsCount = 0;
$missingPaypalAccountCount = 0;

foreach ($payouts as $payout) {
    $hasPaypalAccount = '' !== trim((string) ($payout['paypal_account'] ?? ''));

    if ($hasPaypalAccount) {
        ++$eligiblePayoutsCount;
    } else {
        ++$missingPaypalAccountCount;
    }

    $payoutList[] = [
        'id' => $payout['id'],
        'reference' => $payout['sale_reference'],
        'date' => api_format_date($payout['date'], DATE_TIME_FORMAT_LONG_24H),
        'currency' => $payout['iso_code'],
        'price' => $payout['item_price'],
        'commission' => $payout['commission'],
        'commission_formatted' => formatPayoutAmount(
            $plugin,
            (float) ($payout['commission'] ?? 0),
            $payout['iso_code'] ?? null
        ),
        'paypal_account' => $payout['paypal_account'],
        'has_paypal_account' => $hasPaypalAccount,
    ];
}

$templateName = $plugin->get_lang('PaypalPayoutCommissions');

$template = new Template($templateName);

$template->assign('header', $templateName);
$template->assign('page_title', $templateName);
$template->assign('plugin_title', $plugin->get_lang('plugin_title'));
$template->assign('back_url', api_get_path(WEB_PLUGIN_PATH).'BuyCourses/index.php');

$template->assign('payout_list', $payoutList);
$template->assign('payouts_count', count($payoutList));
$template->assign('eligible_payouts_count', $eligiblePayoutsCount);
$template->assign('missing_paypal_account_count', $missingPaypalAccountCount);
$template->assign('has_eligible_payouts', $eligiblePayoutsCount > 0);

$content = $template->fetch('BuyCourses/view/paypal_payout.tpl');

$template->assign('content', $content);
$template->display_one_col_template();
