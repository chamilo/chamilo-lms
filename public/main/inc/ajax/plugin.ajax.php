<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Michelf\MarkdownExtra;
use Chamilo\CoreBundle\Entity\Plugin as PluginEntity;

/**
 * Responses to AJAX calls.
 */
require_once __DIR__.'/../global.inc.php';

api_block_anonymous_users();

$action = $_REQUEST['a'];
$em = Database::getManager();
$pluginRepository = $em->getRepository(PluginEntity::class);

$accessUrlUtil = Container::getAccessUrlUtil();
$currentAccessUrl = $accessUrlUtil->getCurrent();

switch ($action) {
    case 'md_to_html':
        $plugin = $_GET['plugin'] ?? '';
        $appPlugin = new AppPlugin();

        $pluginPaths = $appPlugin->read_plugins_from_path();
        if (!in_array($plugin, $pluginPaths)) {
            echo Display::return_message(get_lang('You are not allowed to see this page. Either your connection has expired or you are trying to access a page for which you do not have the sufficient privileges.'), 'error', false);
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
        $plugin_info = [
            'version' => '0.0.1',
            'title' => $pluginTitle,
        ];

        $plugin = $pluginRepository->findOneBy(['title' => $pluginTitle]);

        if (empty($plugin)) {
            if ('install' === $action) {
                $plugin = new PluginEntity();
            } else {
                die(json_encode(['error' => 'Plugin not found']));
            }
        }

        $pluginPath = api_get_path(SYS_PLUGIN_PATH).$pluginTitle.'/plugin.php';

        if (is_file($pluginPath) && is_readable($pluginPath)) {
            require $pluginPath;
        }

        $appPlugin = new AppPlugin();

        if ($action === 'install') {
            // Call the install logic inside the plugin itself.
            $appPlugin->install($pluginTitle);

            $plugin
                ->setTitle($pluginTitle)
                ->setInstalledVersion($plugin_info['version'])
                ->setInstalled(true)
            ;

            if (AppPlugin::isOfficial($pluginTitle)) {
                $plugin->setSource(PluginEntity::SOURCE_OFFICIAL);
            }

            // ✅ Removed: persist($plugin) here
            // The install() method of the plugin handles persistence already.
        } elseif ($plugin && $action === 'uninstall') {
            $appPlugin->uninstall($pluginTitle);

            $plugin->uninstall($currentAccessUrl);
        } elseif (('enable' === $action || 'disable' === $action)
            && $plugin && $plugin->isInstalled()
        ) {
            match($action) {
                'enable' => $plugin->enable($currentAccessUrl),
                'disable' => $plugin->disable($currentAccessUrl),
            };
        } else {
            die(json_encode(['error' => 'Cannot enable an uninstalled plugin']));
        }

        $em->flush();

        if (in_array($action, ['enable','disable','uninstall'], true)) {
            $appPlugin = new AppPlugin();
            $info = $appPlugin->getPluginInfo($pluginTitle, true);
            $pluginClass = $info['plugin_class'] ?? null;

            if (!$pluginClass) {
                $guess = ucfirst(strtolower($pluginTitle)).'Plugin';
                if (class_exists($guess, false)) {
                    $pluginClass = $guess;
                }
            }

            $instance = null;
            if ($pluginClass && class_exists($pluginClass, false)) {
                if (method_exists($pluginClass, 'create')) {
                    $instance = $pluginClass::create();
                } else {
                    $instance = new $pluginClass();
                }
            }

            if ($instance && !empty($instance->isCoursePlugin)) {
                if ($action === 'enable') {
                    $instance->install_course_fields_in_all_courses(true);
                } else {
                    $instance->uninstall_course_fields_in_all_courses();
                }
            }
        }

        echo json_encode(['success' => true, 'message' => "Plugin action '$action' applied to '$pluginTitle'."]);
        break;

    default:
        echo json_encode(['error' => 'Invalid action']);
}
