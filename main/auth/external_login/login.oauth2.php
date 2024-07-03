<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/functions.inc.php';

/** @var array $uData */
if ('oauth2' === $uData['auth_source']) {
    $plugin = OAuth2::create();

    if ('true' !== $plugin->get(OAuth2::SETTING_ENABLE)) {
        api_not_allowed(true);
    }

    $oauth2IdField = new ExtraFieldValue('user');
    $oauth2IdValue = $oauth2IdField->get_values_by_handler_and_field_variable(
        $uData['user_id'],
        OAuth2::EXTRA_FIELD_OAUTH2_ID
    );

    if (empty($oauth2IdValue) || empty($oauth2IdValue['value'])) {
        api_not_allowed(true);
    }

    $provider = $plugin->getProvider();

    // Redirect to OAuth2 login.
    $authUrl = $provider->getAuthorizationUrl();

    ChamiloSession::write('oauth2state', $provider->getState());

    if (OAuth2::isFirstLoginAfterAuthSource($uData['user_id'])) {
        ChamiloSession::write('aouth2_authorization_url', $authUrl);
        $authUrl = api_get_path(WEB_PLUGIN_PATH).'oauth2/redirect_info.php';
    }

    header('Location: '.$authUrl);
    // Avoid execution from here in local.inc.php script.
    exit;
}
