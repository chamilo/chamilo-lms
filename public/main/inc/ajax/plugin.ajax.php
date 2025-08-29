<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Michelf\MarkdownExtra;
use Chamilo\CoreBundle\Entity\Plugin as PluginEntity;

/**
 * Responses to AJAX calls.
 */
require_once __DIR__.'/../global.inc.php';

$action = $_REQUEST['a'] ?? '';

if ($action === 'md_to_html') {
    header('Content-Type: text/html; charset=utf-8');
    api_block_anonymous_users();

    try {
        $plugin = $_GET['plugin'] ?? '';
        $appPlugin = new AppPlugin();

        $pluginPaths = $appPlugin->read_plugins_from_path();
        if (!in_array($plugin, $pluginPaths, true)) {
            echo Display::return_message(
                get_lang('You are not allowed to see this page. Either your connection has expired or you are trying to access a page for which you do not have the sufficient privileges.'),
                'error',
                false
            );
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
    } catch (\Throwable $e) {
        error_log('[plugin.ajax md_to_html] '.$e->getMessage());
        http_response_code(500);
        echo Display::return_message(get_lang('Internal error.'), 'error', false);
    }
    exit;
}

header('Content-Type: application/json; charset=utf-8');
api_block_anonymous_users();

try {
    if (!api_is_platform_admin()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Forbidden']);
        exit;
    }

    if (!in_array($action, ['install','uninstall','enable','disable'], true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
        exit;
    }

    $pluginTitle = $_POST['plugin'] ?? '';
    if ($pluginTitle === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing plugin parameter']);
        exit;
    }

    $em = Database::getManager();
    $pluginRepository = $em->getRepository(PluginEntity::class);

    $accessUrlUtil = Container::getAccessUrlUtil();
    $currentAccessUrl = $accessUrlUtil->getCurrent();

    $version = '0.0.0';
    $plugin_info = [];
    $pluginPath = api_get_path(SYS_PLUGIN_PATH).$pluginTitle.'/plugin.php';
    if (is_file($pluginPath) && is_readable($pluginPath)) {
        require $pluginPath;
        if (!empty($plugin_info['version'])) {
            $version = (string) $plugin_info['version'];
        }
    }

    $pluginEntity = $pluginRepository->findOneBy(['title' => $pluginTitle]);

    if (!$pluginEntity && $action !== 'install') {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Plugin not found']);
        exit;
    }

    $appPlugin = new AppPlugin();

    switch ($action) {
        case 'install':
            $appPlugin->install($pluginTitle);

            if (!$pluginEntity) {
                $pluginEntity = new PluginEntity();
            }

            $pluginEntity
                ->setTitle($pluginTitle)
                ->setInstalled(true)
                ->setInstalledVersion($version);

            if (AppPlugin::isOfficial($pluginTitle)) {
                $pluginEntity->setSource(PluginEntity::SOURCE_OFFICIAL);
            }

            $em->persist($pluginEntity);
            break;

        case 'uninstall':
            $appPlugin->uninstall($pluginTitle);

            $pluginEntity->uninstall($currentAccessUrl);
            $em->persist($pluginEntity);
            break;

        case 'enable':
        case 'disable':
            if (!$pluginEntity || !$pluginEntity->isInstalled()) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Cannot enable/disable an uninstalled plugin']);
                exit;
            }

            if ($action === 'enable') {
                $pluginEntity->enable($currentAccessUrl);
            } else {
                $pluginEntity->disable($currentAccessUrl);
            }
            $em->persist($pluginEntity);
            break;
    }

    $em->flush();

    if (in_array($action, ['enable','disable','uninstall'], true)) {
        try {
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
        } catch (\Throwable $postEx) {
            error_log('[plugin.ajax post] '.$postEx->getMessage());
        }
    }

    echo json_encode([
        'success' => true,
        'message' => "Plugin action '{$action}' applied to '{$pluginTitle}'.",
    ]);
} catch (\Throwable $e) {
    error_log('[plugin.ajax] '.$e->getMessage().' | '.$e->getTraceAsString());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal error while processing plugin action. Check logs.',
    ]);
    exit;
}
