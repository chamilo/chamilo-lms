<?php

/* For licensing terms, see /license.txt */

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request as HttpRequest;

require __DIR__.'/../../../main/inc/global.inc.php';

$httpRequest = HttpRequest::createFromGlobals();

$plugin = OAuth2::create();

$iss = $httpRequest->get('iss');
$loginHint = $httpRequest->get('login_hint');
$targetLinkUri = $httpRequest->get('target_link_uri');

try {
    if ('true' !== $plugin->get(OAuth2::SETTING_ALLOW_THIRD_PARTY_LOGIN)) {
        throw new Exception();
    }

    if (empty($iss)) {
        throw new Exception($plugin->get_lang('IssuerNotFound'));
    }

    $authorizeUrlSetting = $plugin->get(OAuth2::SETTING_AUTHORIZE_URL);

    if (empty($authorizeUrlSetting) || 0 !== strpos($authorizeUrlSetting, $iss)) {
        throw new Exception($plugin->get_lang('AuthorizeUrlNotAllowed'));
    }

    $provider = $plugin->getProvider();

    $authorizationUrl = $provider->getAuthorizationUrl(
        [
            'login_hint' => $loginHint,
            'target_link_uri' => $targetLinkUri,
        ]
    );

    ChamiloSession::write('oauth2state', $provider->getState());

    $httpResponse = new RedirectResponse($authorizationUrl);
    $httpResponse->send();
} catch (Exception $e) {
    $message = $e->getMessage()
        ? Display::return_message($e->getMessage(), 'error')
        : null;

    api_not_allowed(true, $message);
}
