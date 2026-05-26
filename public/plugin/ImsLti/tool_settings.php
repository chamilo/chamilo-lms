<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\LtiBundle\Entity\ExternalTool;
use Symfony\Component\HttpFoundation\Response;

$cidReset = true;

require_once __DIR__.'/../../main/inc/global.inc.php';

api_protect_admin_script();

$plugin = ImsLtiPlugin::create();
$request = Container::getRequest();
$response = new Response();

$pluginEntity = Container::getPluginRepository()->findOneByTitle('ImsLti');
$currentAccessUrl = Container::getAccessUrlUtil()->getCurrent();
$pluginConfiguration = $pluginEntity?->getConfigurationsByAccessUrl($currentAccessUrl);

$isPluginEnabled = $pluginEntity
    && $pluginEntity->isInstalled()
    && $pluginConfiguration
    && $pluginConfiguration->isActive();

$em = Database::getManager();

$escape = static function (?string $value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$renderField = static function (string $label, string $value, string $id) use ($escape): string {
    $escapedLabel = $escape($label);
    $escapedValue = $escape($value);
    $escapedId = $escape($id);

    return '
        <div class="space-y-2">
            <label for="'.$escapedId.'" class="font-semibold text-gray-90">'.$escapedLabel.'</label>
            <input
                id="'.$escapedId.'"
                type="text"
                class="form-control w-full"
                readonly
                value="'.$escapedValue.'"
                onclick="this.select();"
            >
        </div>
    ';
};

try {
    if (!$isPluginEnabled) {
        throw new Exception(get_lang('Forbidden'));
    }

    $toolId = (int) $request->query->get('id');

    /** @var ExternalTool|null $tool */
    $tool = $em->find(ExternalTool::class, $toolId);

    if (!$tool) {
        throw new Exception($plugin->get_lang('NoTool'));
    }

    $platformId = ImsLtiPlugin::getIssuerUrl();
    $deploymentId = (string) ($tool->getParent() ? $tool->getParent()->getId() : $tool->getId());
    $clientId = (string) $tool->getClientId();
    $authUrl = api_get_path(WEB_PLUGIN_PATH).'ImsLti/auth.php';
    $tokenUrl = api_get_path(WEB_PLUGIN_PATH).'ImsLti/token.php';
    $keySetUrl = api_get_path(WEB_PLUGIN_PATH).'ImsLti/jwks.php';

    $toolTitle = (string) $tool->getTitle();
    $toolVersion = (string) $tool->getVersion();
    $launchUrl = (string) $tool->getLaunchUrl();

    $html = '
        <div class="space-y-6">
            <div class="rounded-xl border border-gray-20 bg-gray-5 p-4">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                    <div class="min-w-0">
                        <h5 class="text-h5 mb-2">'.$escape($toolTitle).'</h5>
                        <div class="space-y-2 text-body-2 text-gray-70">
                            <div>
                                <span class="font-semibold">'.$escape(get_lang('Name')).':</span>
                                <span>'.$escape($toolTitle).'</span>
                            </div>';

    if (!empty($toolVersion)) {
        $html .= '
                            <div>
                                <span class="font-semibold">'.$escape($plugin->get_lang('LtiVersion')).':</span>
                                <span>'.$escape($toolVersion).'</span>
                            </div>';
    }

    $html .= '
                            <div class="break-all">
                                <span class="font-semibold">'.$escape($plugin->get_lang('LaunchUrl')).':</span>
                                <span>'.$escape($launchUrl).'</span>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-lg border border-primary/20 bg-primary/5 px-3 py-2 text-body-2 text-gray-80">
                        '.$escape($plugin->get_lang('ConfigSettingsForTool')).'
                    </div>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">'
        .$renderField($plugin->get_lang('PlatformId'), $platformId, 'imslti_platform_id')
        .$renderField($plugin->get_lang('DeploymentId'), $deploymentId, 'imslti_deployment_id')
        .$renderField($plugin->get_lang('ClientId'), $clientId, 'imslti_client_id')
        .$renderField($plugin->get_lang('AuthUrl'), $authUrl, 'imslti_auth_url')
        .$renderField($plugin->get_lang('TokenUrl'), $tokenUrl, 'imslti_token_url')
        .$renderField($plugin->get_lang('KeySetUrl'), $keySetUrl, 'imslti_keyset_url').'
            </div>
        </div>
    ';

    $response->setContent($html);
} catch (Exception $exception) {
    $response->setContent(
        Display::return_message($exception->getMessage(), 'error')
    );
}

$response->send();
