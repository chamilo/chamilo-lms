<?php
/* For licensing terms, see /license.txt */
/**
 * Validate requirements for a open session.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 *
 * @package chamilo.plugin.advanced_subscription
 */
require_once __DIR__.'/../config.php';

$plugin = AdvancedSubscriptionPlugin::create();

if (!isset($_GET['session_id'], $_GET['user_id'], $_GET['profile_completed'])) {
    exit;
}

$sessionInfo = api_get_session_info($_GET['session_id']);

$tpl = new Template(
    $plugin->get_lang('plugin_title'),
    false,
    false,
    false,
    false,
    false
);
$tpl->assign('session', $sessionInfo);

if (SessionManager::isUserSubscribedAsStudent(
    $_GET['session_id'],
    $_GET['user_id']
)) {
    $tpl->assign('is_subscribed', false);
    $tpl->assign(
        'errorMessages',
        [sprintf(
            $plugin->get_lang('YouAreAlreadySubscribedToSessionX'),
            $sessionInfo['name']
        )]
    );
} else {
    if (!$plugin->isAllowedSubscribeToOpenSession($_GET)) {
        $tpl->assign('is_subscribed', false);
        $tpl->assign('errorMessages', $plugin->getErrorMessages());
    } else {
        SessionManager::subscribeUsersToSession(
            $_GET['session_id'],
            [$_GET['user_id']],
            SESSION_VISIBLE_READ_ONLY,
            false
        );

        $tpl->assign('is_subscribed', true);
    }
}

$content = $tpl->fetch('/advanced_subscription/views/open_session.tpl');

$tpl->assign('content', $content);
$tpl->display_one_col_template();
