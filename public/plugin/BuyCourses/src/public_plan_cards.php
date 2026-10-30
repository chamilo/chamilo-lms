<?php

declare(strict_types=1);

/* For license terms, see /license.txt */

$cidReset = true;
require_once __DIR__.'/../../../main/inc/global.inc.php';
require_once __DIR__.'/public_plan_cards.lib.php';

api_protect_admin_script(true);

$plugin = BuyCoursesPlugin::create();
$errors = [];
$saved = false;
$config = buycoursesGetPublicPlanSettings($plugin);

$tokenKey = 'buycourses_public_plan_cards_csrf';
if (empty($_SESSION[$tokenKey])) {
    $_SESSION[$tokenKey] = bin2hex(random_bytes(32));
}

if ('POST' === $_SERVER['REQUEST_METHOD']) {
    $postedToken = (string) ($_POST['csrf_token'] ?? '');
    if (!hash_equals((string) $_SESSION[$tokenKey], $postedToken)) {
        $errors[] = $plugin->get_lang('PublicPlanInvalidToken');
    } else {
        $submitted = [
            'show_free' => isset($_POST['show_free']),
            'free' => is_array($_POST['free'] ?? null) ? $_POST['free'] : [],
            'cards' => is_array($_POST['cards'] ?? null) ? $_POST['cards'] : [],
        ];

        foreach (array_slice(array_values($submitted['cards']), 0, 8) as $position => $card) {
            if (!is_array($card) || !empty($card['remove']) || empty($card['enabled'])) {
                continue;
            }
            if (!buycoursesIsHttpsPublicPlanUrl(trim((string) ($card['url'] ?? '')))) {
                $errors[] = sprintf($plugin->get_lang('PublicPlanInvalidUrl'), $position + 1);
            }
            $titles = buycoursesPublicPlanLocalizedInput($card['title'] ?? [], 120);
            if ('' === implode('', $titles)) {
                $errors[] = sprintf($plugin->get_lang('PublicPlanMissingTitle'), $position + 1);
            }
        }
        foreach (array_slice(array_values($submitted['cards']), 0, 8) as $position => $card) {
            if (!is_array($card) || !empty($card['remove'])) {
                continue;
            }
            $imageUrl = trim((string) ($card['image_url'] ?? ''));
            if ('' !== $imageUrl && !buycoursesIsHttpsPublicPlanUrl($imageUrl)) {
                $errors[] = sprintf($plugin->get_lang('PublicPlanInvalidImageUrl'), $position + 1);
            }
        }

        // Show submitted content again when validation fails.
        $config = buycoursesNormalizePublicPlanSettings($submitted);
        if (!$errors) {
            try {
                buycoursesSavePublicPlanSettings($plugin, $submitted);
                $config = buycoursesNormalizePublicPlanSettings($submitted);
                $saved = true;
                $_SESSION[$tokenKey] = bin2hex(random_bytes(32));
            } catch (Throwable $exception) {
                error_log('[BuyCourses][PublicPlans] Save failed: '.$exception->getMessage());
                $errors[] = $plugin->get_lang('PublicPlanSaveFailed');
            }
        }
    }
}

if (empty($config['cards'])) {
    $empty = buycoursesPublicPlanDefaults()['cards'][0];
    $empty['title'] = array_fill_keys(BUYCOURSES_PUBLIC_PLAN_LANGUAGES, '');
    $config['cards'][] = $empty;
}

$view = new Template($plugin->get_lang('PublicPlansManageTitle'));
$view->assign('page_title', $plugin->get_lang('PublicPlansManageTitle'));
$view->assign('config', $config);
$view->assign('plan_languages', BUYCOURSES_PUBLIC_PLAN_LANGUAGES);
$view->assign('plan_current_language', buycoursesPublicPlanLanguage());
$view->assign('plan_language_labels', ['en_US' => 'English', 'fr_FR' => 'Français', 'es' => 'Español']);
$view->assign('errors', $errors);
$view->assign('saved', $saved);
$view->assign('csrf_token', $_SESSION[$tokenKey]);
$view->assign('catalog_url', api_get_path(WEB_PLUGIN_PATH).'BuyCourses/index.php?preview_public=1');
$view->assign('plugin_url', api_get_path(WEB_PLUGIN_PATH).'BuyCourses/index.php');
$view->assign('plan_labels', [
    'title' => $plugin->get_lang('PublicPlansManageTitle'),
    'intro' => $plugin->get_lang('PublicPlansManageIntro'),
    'free' => $plugin->get_lang('PublicFreePlanTitle'),
    'showFree' => $plugin->get_lang('PublicPlansShowFree'),
    'freeHelp' => $plugin->get_lang('PublicPlansFreeHelp'),
    'promo' => $plugin->get_lang('PublicPlansPromotional'),
    'promoHelp' => $plugin->get_lang('PublicPlansPromotionalHelp'),
    'enabled' => $plugin->get_lang('PublicPlansEnabled'),
    'position' => $plugin->get_lang('PublicPlansPosition'),
    'description' => $plugin->get_lang('PublicPlansDescription'),
    'features' => $plugin->get_lang('PublicPlansFeatures'),
    'featuresHelp' => $plugin->get_lang('PublicPlansFeaturesHelp'),
    'button' => $plugin->get_lang('PublicPlansButton'),
    'url' => $plugin->get_lang('PublicPlansExternalUrl'),
    'imageUrl' => $plugin->get_lang('PublicPlansImageUrl'),
    'add' => $plugin->get_lang('PublicPlansAdd'),
    'remove' => $plugin->get_lang('PublicPlansRemove'),
    'save' => $plugin->get_lang('PublicPlansSave'),
    'saved' => $plugin->get_lang('PublicPlansSaved'),
    'preview' => $plugin->get_lang('PublicPlansPreview'),
    'back' => $plugin->get_lang('PublicPlansBack'),
    'card' => $plugin->get_lang('PublicPlansCard'),
    'language' => $plugin->get_lang('PublicPlansLanguage'),
    'allLanguagesSaved' => $plugin->get_lang('PublicPlansAllLanguagesSaved'),
]);

$view->assign('header', $plugin->get_lang('PublicPlansManageTitle'));
$view->assign('content', $view->fetch('BuyCourses/view/public_plan_cards_admin.tpl'));
$view->display_one_col_template();
