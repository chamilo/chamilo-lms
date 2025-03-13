<?php
/* For licensing terms, see /license.txt */

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
        $pluginTitle = $_POST['plugin'] ?? '';

        $criteria = ['title' => $pluginTitle];

        if ($accessUrlHelper->isMultiple()) {
            $criteria['accessUrlId'] = $accessUrlHelper->getCurrent()->getId();
        }

        $plugin = $pluginRepository->findOneBy($criteria);
        if (!$plugin) {
            die(json_encode(['error' => 'Plugin not found']));
        }

        $appPlugin = new AppPlugin();

        if ($action === 'install') {
            $appPlugin->install($pluginTitle);

            $plugin->setInstalled(true);
        } elseif ($action === 'uninstall') {
            $appPlugin->uninstall($pluginTitle);

            $plugin->setInstalled(false);
            $plugin->setActive(false);

            $appPlugin->uninstall($pluginTitle);
        } elseif ($action === 'enable') {
            if ($plugin->isInstalled()) {
                $plugin->setActive(true);
            } else {
                die(json_encode(['error' => 'Cannot enable an uninstalled plugin']));
            }
        } elseif ($action === 'disable') {
            $plugin->setActive(false);
        }

        $em->flush();

        echo json_encode(['success' => true, 'message' => "Plugin action '$action' applied to '$pluginTitle'."]);
        break;

    default:
        echo json_encode(['error' => 'Invalid action']);
}
