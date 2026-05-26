<?php
/* For licensing terms, see /license.txt */

declare(strict_types=1);

use Chamilo\CoreBundle\Framework\Container;
use Symfony\Component\HttpFoundation\Response;

$cidReset = true;

require_once __DIR__.'/../../main/inc/global.inc.php';
require_once __DIR__.'/LtiProviderPlugin.php';

api_protect_admin_script();

$plugin = LtiProviderPlugin::create();
$response = new Response();

try {
    $pluginEntity = Container::getPluginRepository()->findOneByTitle('LtiProvider');
    $currentAccessUrl = Container::getAccessUrlUtil()->getCurrent();
    $pluginConfiguration = $pluginEntity?->getConfigurationsByAccessUrl($currentAccessUrl);

    $isPluginEnabled = $pluginEntity
        && $pluginEntity->isInstalled()
        && $pluginConfiguration
        && $pluginConfiguration->isActive();

    if (!$isPluginEnabled) {
        throw new Exception(get_lang('Not allowed'));
    }

    $toolBaseUrl = rtrim(api_get_path(WEB_PLUGIN_PATH).'LtiProvider/tool/', '/').'/';

    $launchUrl = $toolBaseUrl.'start.php';
    $loginUrl = $toolBaseUrl.'login.php';
    $redirectUrl = $toolBaseUrl.'start.php';
    $jwksUrl = $toolBaseUrl.'jwks.php';

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

    $html .= ''
        .'<div class="rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-90">'
        .Security::remove_XSS($plugin->get_lang('ConnectionDetailsHelp'))
        .'</div>';

    foreach ($items as $item) {
        $label = Security::remove_XSS((string) $item['label']);
        $value = Security::remove_XSS((string) $item['value']);

        $html .= ''
            .'<div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">'
            .'<div class="mb-2 text-sm font-semibold text-gray-700">'.$label.'</div>'
            .'<div class="break-all rounded-md bg-gray-10 px-3 py-2 font-mono text-sm text-gray-900">'.$value.'</div>'
            .'</div>';
    }

    $html .= '</div>';

    $response->setContent($html);
} catch (Throwable $exception) {
    $response->setContent(
        Display::return_message($exception->getMessage(), 'error')
    );
}

$response->send();
