<?php
/* For license terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\LtiBundle\Entity\ExternalTool;

$cidReset = true;

require_once __DIR__.'/../../main/inc/global.inc.php';

api_protect_admin_script();

$plugin = ImsLtiPlugin::create();

$pluginEntity = Container::getPluginRepository()->findOneByTitle('ImsLti');
$currentAccessUrl = Container::getAccessUrlUtil()->getCurrent();
$pluginConfiguration = $pluginEntity?->getConfigurationsByAccessUrl($currentAccessUrl);

$isPluginEnabled = $pluginEntity
    && $pluginEntity->isInstalled()
    && $pluginConfiguration
    && $pluginConfiguration->isActive();

if (!$isPluginEnabled) {
    api_not_allowed(true);
}

$em = Database::getManager();

/** @var ExternalTool[] $tools */
$tools = $em->getRepository(ExternalTool::class)->findAll();

usort(
    $tools,
    static function (ExternalTool $a, ExternalTool $b): int {
        return ($a->getId() ?? 0) <=> ($b->getId() ?? 0);
    }
);

$toolRows = array_map(
    static function (ExternalTool $tool) use ($plugin): array {
        $isLti13 = ImsLti::V_1P3 === $tool->getVersion();

        $launchUrl = trim((string) $tool->getLaunchUrl());
        $loginUrl = trim((string) $tool->getLoginUrl());
        $redirectUrl = trim((string) $tool->getRedirectUrl());
        $jwksUrl = trim((string) $tool->getJwksUrl());
        $publicKey = trim((string) $tool->publicKey);
        $clientId = trim((string) $tool->getClientId());

        $missingFields = [];

        if ($isLti13) {
            if ('' === $launchUrl) {
                $missingFields[] = $plugin->get_lang('LaunchUrl');
            }

            if ('' === $loginUrl) {
                $missingFields[] = $plugin->get_lang('LoginUrl');
            }

            if ('' === $redirectUrl) {
                $missingFields[] = $plugin->get_lang('RedirectUrl');
            }

            if ('' === $jwksUrl && '' === $publicKey) {
                $missingFields[] = $plugin->get_lang('JwksUrlOrRsaKey');
            }

            if ('' === $clientId) {
                $missingFields[] = $plugin->get_lang('ClientId');
            }
        } else {
            if ('' === $launchUrl) {
                $missingFields[] = $plugin->get_lang('LaunchUrl');
            }
        }

        $isReadyForCourses = empty($missingFields);
        $incompleteMessage = $isReadyForCourses
            ? ''
            : sprintf(
                $plugin->get_lang('CompleteParamsLti'),
                implode(', ', $missingFields)
            );

        return [
            'id' => $tool->getId(),
            'title' => (string) $tool->getTitle(),
            'version' => (string) $tool->getVersion(),
            'client_id' => $clientId,
            'launch_url' => $launchUrl,
            'is_lti13' => $isLti13,
            'is_ready_for_courses' => $isReadyForCourses,
            'incomplete_message' => $incompleteMessage,
        ];
    },
    $tools
);

$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'admin/index.php',
    'name' => get_lang('PlatformAdmin'),
];

$htmlHeadXtra[] = api_get_css(
    api_get_path(WEB_PLUGIN_PATH).'ImsLti/assets/style.css'
);

$template = new Template($plugin->get_title());
$template->assign('tools', $toolRows);

$content = $template->fetch('ImsLti/view/admin.tpl');

$template->assign('header', $plugin->get_title());
$template->assign('content', $content);
$template->display_one_col_template();
