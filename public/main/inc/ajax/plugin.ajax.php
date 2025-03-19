<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\AccessUrlRelPlugin;
use Chamilo\CoreBundle\Framework\Container;
use Michelf\MarkdownExtra;
use Chamilo\CoreBundle\Entity\Plugin;

/**
 * Responses to AJAX calls.
 */
require_once __DIR__.'/../global.inc.php';

api_block_anonymous_users();

$action = $_REQUEST['a'];
$em = Database::getManager();
$pluginRepository = $em->getRepository(Plugin::class);

$accessUrlHelper = Container::getAccessUrlHelper();
$currentAccessUrl = $accessUrlHelper->getCurrent();

switch ($action) {
    case 'md_to_html':
        $plugin = $_GET['plugin'] ?? '';
        $appPlugin = new AppPlugin();

        $pluginPaths = $appPlugin->read_plugins_from_path();
        if (!in_array($plugin, $pluginPaths)) {
            echo Display::return_message(get_lang('NotAllowed'), 'error', false);
            exit;
        }

        $pluginInfo = $appPlugin->getPluginInfo($plugin);

        $html = '';
        if (!empty($pluginInfo)) {
            $file = api_get_path(SYS_PLUGIN_PATH).$plugin.'/README.md';
            if (file_exists($file)) {
                $content = file_get_contents($file);
                $html = MarkdownExtra::defaultTransform($content);
            }
        }
        echo $html;
        break;

    case 'install':
    case 'uninstall':
    case 'enable':
    case 'disable':
        $pluginTitle = api_replace_dangerous_char($_POST['plugin'] ?? '');
        $plugin_info = [
            'version' => '0.0.1',
            'title' => $pluginTitle,
        ];

        $criteria = ['title' => $pluginTitle];

        if ($accessUrlHelper->isMultiple()) {
            $criteria['accessUrlId'] = $currentAccessUrl->getId();
        }

        $plugin = match ($action) {
            'uninstall', 'enable', 'disable' => $pluginRepository->findOneBy($criteria),
            'install' => new Plugin(),
            default => die(json_encode(['error' => 'Plugin not found'])),
        };

        $pluginPath = api_get_path(SYS_PLUGIN_PATH).$pluginTitle.'/plugin.php';

        if (is_file($pluginPath) && is_readable($pluginPath)) {
            require $pluginPath;
        }

        $appPlugin = new AppPlugin();

        if ($action === 'install') {
            $appPlugin->install($pluginTitle);

            $plugin
                ->setTitle($plugin_info['title'])
                ->setInstalledVersion($plugin_info['version'])
                ->setInstalled(true)
            ;

            if (AppPlugin::isOfficial($plugin_info['title'])) {
                $plugin->setSource(Plugin::SOURCE_OFFICIAL);
            }

            $em->persist($plugin);
        } elseif ($plugin && $action === 'uninstall') {
            $appPlugin->uninstall($pluginTitle);

            $plugin->setInstalled(false);
        } elseif (('enable' === $action || 'disable' === $action)
            && $plugin && $plugin->isInstalled()
        ) {
            $pluginConfiguration = $plugin->getConfigurationsByAccessUrl($currentAccessUrl);

            if (!$pluginConfiguration) {
                $pluginConfiguration = (new AccessUrlRelPlugin())->setUrl($currentAccessUrl);

                $plugin->addConfigurationsInUrl($pluginConfiguration);
            }

            match($action) {
                'enable' => $pluginConfiguration->setActive(true),
                'disable' => $pluginConfiguration->setActive(false),
            };
        } else {
            die(json_encode(['error' => 'Cannot enable an uninstalled plugin']));
        }

        $em->flush();

        echo json_encode(['success' => true, 'message' => "Plugin action '$action' applied to '$pluginTitle'."]);
        break;

    default:
        echo json_encode(['error' => 'Invalid action']);
}
