<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Symfony\Component\HttpFoundation\Response;

$cidReset = true;

require_once __DIR__.'/../../main/inc/global.inc.php';
require_once __DIR__.'/LtiProviderPlugin.php';

api_protect_admin_script();

$plugin = LtiProviderPlugin::create();
$response = new Response();

$pluginEntity = Container::getPluginRepository()->findOneByTitle('LtiProvider');
$currentAccessUrl = Container::getAccessUrlUtil()->getCurrent();
$pluginConfiguration = $pluginEntity?->getConfigurationsByAccessUrl($currentAccessUrl);

$isPluginEnabled = $pluginEntity
    && $pluginEntity->isInstalled()
    && $pluginConfiguration
    && $pluginConfiguration->isActive();

$launchUrl = api_get_plugin_setting('lti_provider', 'launch_url');
$loginUrl = api_get_plugin_setting('lti_provider', 'login_url');
$redirectUrl = api_get_plugin_setting('lti_provider', 'redirect_url');
$jwksUrl = api_get_plugin_setting('lti_provider', 'jwks_url');

if (empty($jwksUrl)) {
    $jwksUrl = api_get_path(WEB_PLUGIN_PATH).LtiProviderPlugin::JWKS_URL;
}

try {
    if (!$isPluginEnabled) {
        throw new Exception(get_lang('Not allowed'));
    }

    $items = [
        [
            'label' => $plugin->get_lang('LaunchUrl'),
            'value' => $launchUrl,
        ],
        [
            'label' => $plugin->get_lang('LoginUrl'),
            'value' => $loginUrl,
        ],
        [
            'label' => $plugin->get_lang('RedirectUrl'),
            'value' => $redirectUrl,
        ],
        [
            'label' => $plugin->get_lang('KeySetUrlJwks'),
            'value' => $jwksUrl,
        ],
    ];

    $html = '<div class="space-y-4">';

    foreach ($items as $item) {
        $label = Security::remove_XSS((string) $item['label']);
        $value = Security::remove_XSS((string) $item['value']);

        $html .= ''
            .'<div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">'
            .'<div class="mb-2 text-sm font-semibold text-gray-700">'.$label.'</div>'
            .'<div class="break-all text-sm text-gray-900">'.$value.'</div>'
            .'</div>';
    }

    $html .= '</div>';

    $response->setContent($html);
} catch (Exception $exception) {
    $response->setContent(
        Display::return_message($exception->getMessage(), 'error')
    );
}

$response->send();
