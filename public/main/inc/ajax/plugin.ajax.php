<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Michelf\MarkdownExtra;
use Chamilo\CoreBundle\Entity\Plugin as PluginEntity;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CourseBundle\Entity\CDocument;

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

/**
 * From here on, everything returns JSON.
 */
header('Content-Type: application/json; charset=utf-8');
api_block_anonymous_users();

if ($action === 'list_documents') {
    try {
        header('Content-Type: application/json; charset=utf-8');

        $courseId = api_get_course_int_id();
        $isAdmin  = api_is_platform_admin();

        // Require edit rights inside a course; otherwise only admins can list globally
        if ($courseId > 0) {
            if (!api_is_allowed_to_edit()) {
                http_response_code(403);
                echo json_encode(['error' => 'Forbidden']);
                exit;
            }
        } else {
            if (!$isAdmin) {
                http_response_code(403);
                echo json_encode(['error' => 'Forbidden (admin required for global listing)']);
                exit;
            }
        }

        $em   = Database::getManager();
        $repo = $em->getRepository(ResourceNode::class);

        $qb = $em->createQueryBuilder()
            ->select('DISTINCT d')
            ->from(CDocument::class, 'd')
            ->innerJoin('d.resourceNode', 'rn')
            ->innerJoin('rn.resourceFiles', 'rf')
            ->innerJoin('rn.resourceLinks', 'rl')
            ->where('d.filetype = :type')
            ->setParameter('type', 'file');

        if ($courseId > 0) {
            $qb->andWhere('IDENTITY(rl.course) = :cId')
                ->setParameter('cId', (int)$courseId);
        }

        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 500;
        $limit = max(1, min($limit, 2000));
        $qb->setMaxResults($limit)
            ->orderBy('d.iid', 'DESC');

        $docs = $qb->getQuery()->getResult();
        $out  = [];

        $sysBase = rtrim(str_replace('/public/', '', api_get_path(SYS_PATH)), '/');
        foreach ($docs as $doc) {
            $files = $doc->getResourceNode()->getResourceFiles();
            if ($files->isEmpty()) {
                continue;
            }

            $file = $files->first();
            $orig = $file->getOriginalName();
            $ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
            if (!in_array($ext, ['pdf','ppt','pptx','odp'], true)) {
                continue;
            }

            $relPath  = $repo->getFilename($file); // e.g. "/a1/b2/file.pdf"
            $diskPath = $sysBase . '/var/upload/resource' . $relPath;

            // Only list entries that truly exist on disk
            if (!is_file($diskPath) || !is_readable($diskPath)) {
                continue;
            }

            $out[] = [
                'id'       => $doc->getIid(),
                'url'      => $diskPath,
                'filename' => $orig,
                'size'     => @filesize($diskPath) ?: null,
            ];
        }

        echo json_encode($out);
    } catch (\Throwable $e) {
        error_log('[plugin.ajax list_documents] '.$e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Internal error']);
    }
    exit;
}

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

            // BBB persists the plugin entity in its installation process. It throws a duplicate key exception.
            $pluginEntity = $pluginRepository->findOneBy(['title' => $pluginTitle]);

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

            // If it is a course plugin, propagate enable/disable to all courses
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
