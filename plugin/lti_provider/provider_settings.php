<?php
/* For licensing terms, see /license.txt */

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$cidReset = true;

require_once __DIR__.'/../../main/inc/global.inc.php';
require_once __DIR__.'/LtiProviderPlugin.php';

api_block_anonymous_users(false);

$plugin = LtiProviderPlugin::create();
$webPluginPath = api_get_path(WEB_PLUGIN_PATH).'lti_provider/';

$request = Request::createFromGlobals();
$response = new Response();

$em = Database::getManager();

$enabled = api_get_plugin_setting('lti_provider', 'enabled');
$name = api_get_plugin_setting('lti_provider', 'name');
$launchUrl = api_get_plugin_setting('lti_provider', 'launch_url');
$loginUrl = api_get_plugin_setting('lti_provider', 'login_url');
$redirectUrl = api_get_plugin_setting('lti_provider', 'redirect_url');
$jwksUrl = api_get_plugin_setting('lti_provider', 'jwks_url');
if (empty($jwksUrl)) {
    $jwksUrl = api_get_path(WEB_PLUGIN_PATH).LtiProviderPlugin::JWKS_URL;
}

try {
    if ($enabled !== 'true') {
        throw new Exception(get_lang('Forbidden'));
    }

    $html = '<div class="row">'
        .'<div class="col-xs-2 text-right"><strong>'.$plugin->get_lang('LaunchUrl').'</strong></div>'
        .'<div class="col-xs-10">'.$launchUrl.'</div>'
        .'</div>'
        .'<div class="row">'
        .'<div class="col-xs-2 text-right"><strong>'.$plugin->get_lang('LoginUrl').'</strong></div>'
        .'<div class="col-xs-10">'.$loginUrl.'</div>'
        .'</div>'
        .'<div class="row">'
        .'<div class="col-xs-2 text-right"><strong>'.$plugin->get_lang('RedirectUrl').'</strong></div>'
        .'<div class="col-xs-10">'.$redirectUrl.'</div>'
        .'</div>'
        .'<div class="row">'
        .'<div class="col-xs-2 text-right"><strong>'.$plugin->get_lang('KeySetUrlJwks').'</strong></div>'
        .'<div class="col-xs-10">'.$jwksUrl.'</div>'
        .'</div>';

    $response->setContent($html);
} catch (Exception $exception) {
    $response->setContent(
        Display::return_message($exception->getMessage(), 'error')
    );
}

$response->send();
