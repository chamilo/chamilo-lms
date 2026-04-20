<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Michelf\MarkdownExtra;
use Chamilo\CoreBundle\Entity\Plugin as PluginEntity;
use Chamilo\CoreBundle\Entity\PersonalFile;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CourseBundle\Entity\CDocument;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

/**
 * Responses to AJAX calls.
 */
require_once __DIR__.'/../global.inc.php';

$action = $_REQUEST['a'] ?? '';

if ($action === 'md_to_html') {
    header('Content-Type: text/html; charset=utf-8');
    api_block_anonymous_users();

    if (!api_is_platform_admin()) {
        http_response_code(403);
        echo Display::return_message(get_lang('Forbidden'), 'error', false);
        exit;
    }

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

        $readmeFile = null;
        $basePath = rtrim(api_get_path(SYS_PLUGIN_PATH).$plugin, '/').'/';
        $candidates = [
            'README.md',
            'Readme.md',
            'readme.md',
            'README.MD',
        ];

        foreach ($candidates as $candidate) {
            $fullPath = $basePath.$candidate;
            if (is_file($fullPath) && is_readable($fullPath)) {
                $readmeFile = $fullPath;
                break;
            }
        }

        if (null === $readmeFile) {
            echo Display::return_message('README file not found for this plugin.', 'warning', false);
            exit;
        }

        $content = file_get_contents($readmeFile);
        $html = MarkdownExtra::defaultTransform($content);

        if ('' === trim((string) $html)) {
            $html = Display::return_message('README file is empty.', 'warning', false);
        }
        echo '<div class="prose">'.$html.'</div>';
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

        // Require edit rights inside a course; otherwise check BBB conference manager for global context
        if ($courseId > 0) {
            if (!api_is_allowed_to_edit()) {
                http_response_code(403);
                echo json_encode(['error' => 'Forbidden']);
                exit;
            }
        } else {
            if (!$isAdmin) {
                $allowed = false;
                $isGlobal = isset($_GET['global']);
                $isGlobalPerUser = isset($_GET['user_id']) ? (int) $_GET['user_id'] : false;
                if ($isGlobal || $isGlobalPerUser) {
                    $bbb = new Bbb('', '', $isGlobal, $isGlobalPerUser);
                    $allowed = $bbb->isConferenceManager();
                }
                if (!$allowed) {
                    http_response_code(403);
                    echo json_encode(['error' => 'Forbidden']);
                    exit;
                }
            }
        }

        $em   = Database::getManager();
        $repo = $em->getRepository(ResourceNode::class);

        if ($courseId > 0) {
            // Course context: list course documents
            $qb = $em->createQueryBuilder()
                ->select('DISTINCT d')
                ->from(CDocument::class, 'd')
                ->innerJoin('d.resourceNode', 'rn')
                ->innerJoin('rn.resourceFiles', 'rf')
                ->innerJoin('rn.resourceLinks', 'rl')
                ->where('d.filetype = :type')
                ->andWhere('IDENTITY(rl.course) = :cId')
                ->setParameter('type', 'file')
                ->setParameter('cId', (int) $courseId);
        } else {
            // Global context: list only the current user's personal files
            $qb = $em->createQueryBuilder()
                ->select('DISTINCT d')
                ->from(PersonalFile::class, 'd')
                ->innerJoin('d.resourceNode', 'rn')
                ->innerJoin('rn.resourceFiles', 'rf')
                ->where('rn.creator = :userId')
                ->setParameter('userId', api_get_user_id());
        }

        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 500;
        $limit = max(1, min($limit, 2000));
        $orderField = $courseId > 0 ? 'd.iid' : 'd.id';
        $qb->setMaxResults($limit)
            ->orderBy($orderField, 'DESC');

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

            $docId = $doc instanceof CDocument ? $doc->getIid() : $doc->getId();
            $out[] = [
                'id'       => $docId,
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

/**
 * Normalize a DB id list to unique positive integers.
 */
function plugin_normalize_int_ids(array $ids): array
{
    $ids = array_map('intval', $ids);
    $ids = array_filter($ids, static fn (int $id): bool => $id > 0);

    return array_values(array_unique($ids));
}

/**
 * Remove all IMS/LTI shortcuts and tool resource nodes before uninstalling the plugin.
 *
 * This keeps disable non-destructive, while uninstall performs a real cleanup.
 */
function plugin_cleanup_imslti_data_before_uninstall(Connection $connection): void
{
    $externalToolNodeIds = $connection->fetchFirstColumn(
        'SELECT resource_node_id
         FROM lti_external_tool
         WHERE resource_node_id IS NOT NULL'
    );

    $externalToolNodeIds = plugin_normalize_int_ids($externalToolNodeIds);

    if (empty($externalToolNodeIds)) {
        return;
    }

    $shortcutNodeIds = $connection->fetchFirstColumn(
        'SELECT resource_node_id
         FROM c_shortcut
         WHERE shortcut_node_id IN (?)',
        [$externalToolNodeIds],
        [ArrayParameterType::INTEGER]
    );

    $shortcutNodeIds = plugin_normalize_int_ids($shortcutNodeIds);

    $connection->beginTransaction();

    try {
        // Remove shortcut rows first.
        if (!empty($shortcutNodeIds)) {
            $connection->executeStatement(
                'DELETE FROM c_shortcut WHERE resource_node_id IN (?)',
                [$shortcutNodeIds],
                [ArrayParameterType::INTEGER]
            );

            // Remove the resource nodes that belonged to those shortcuts.
            $connection->executeStatement(
                'DELETE FROM resource_node WHERE id IN (?)',
                [$shortcutNodeIds],
                [ArrayParameterType::INTEGER]
            );
        }

        // Remove LTI tool resource nodes.
        // This will also cascade-delete lti_external_tool rows.
        $connection->executeStatement(
            'DELETE FROM resource_node WHERE id IN (?)',
            [$externalToolNodeIds],
            [ArrayParameterType::INTEGER]
        );

        $connection->commit();
    } catch (\Throwable $e) {
        $connection->rollBack();
        throw $e;
    }
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
            if ('ImsLti' === $pluginTitle) {
                plugin_cleanup_imslti_data_before_uninstall($em->getConnection());
            }

            $appPlugin->uninstall($pluginTitle);

            $pluginEntity->setInstalled(false);
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

    if (in_array($action, ['enable','disable'], true)) {
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

            if ($instance) {
                if (method_exists($instance, 'syncPlatformKeyPairWithPluginState')) {
                    $instance->syncPlatformKeyPairWithPluginState();
                } elseif (method_exists($instance, 'syncPlatformKeyWithPluginState')) {
                    $instance->syncPlatformKeyWithPluginState();
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

    if ('uninstall' === $action && null !== $pluginEntity->getId()) {
        $pluginId = $pluginEntity->getId();

        $deletedRows = $em->getConnection()->executeStatement(
            'DELETE FROM access_url_rel_plugin WHERE plugin_id = :pluginId',
            ['pluginId' => $pluginId]
        );

        error_log(
            sprintf(
                '[plugin.ajax uninstall] Deleted %d access_url_rel_plugin row(s) for plugin_id %d',
                $deletedRows,
                $pluginId
            )
        );
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
