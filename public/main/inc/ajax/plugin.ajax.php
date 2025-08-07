<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CDocument;
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

$accessUrlUtil = Container::getAccessUrlUtil();
$currentAccessUrl = $accessUrlUtil->getCurrent();

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
        $plugin_info = [
            'version' => '0.0.1',
            'title' => $pluginTitle,
        ];

        $criteria = ['title' => $pluginTitle];

        if ($accessUrlUtil->isMultiple()) {
            $criteria['accessUrlId'] = $currentAccessUrl->getId();
        }

        $plugin = $pluginRepository->findOneBy($criteria);

        if (empty($plugin)) {
            if ('install' === $action) {
                $plugin = new Plugin();
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
                $plugin->setSource(Plugin::SOURCE_OFFICIAL);
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

        echo json_encode(['success' => true, 'message' => "Plugin action '$action' applied to '$pluginTitle'."]);
        break;
    case 'list_documents':
        $courseId = api_get_course_int_id();
        $em       = Database::getManager();
        $repo     = $em->getRepository(ResourceNode::class);

        $qb = $em->createQueryBuilder()
            ->select('DISTINCT d')
            ->from(CDocument::class, 'd')
            ->innerJoin('d.resourceNode','rn')
            ->innerJoin('rn.resourceFiles','rf')
            ->where('d.filetype = :type')
            ->setParameter('type','file');

        if ($courseId > 0) {
            $qb->innerJoin('rn.resourceLinks','rl')
                ->andWhere('rl.course = :c')
                ->setParameter('c',$courseId);
        }

        $docs = $qb->getQuery()->getResult();
        $out  = [];

        foreach ($docs as $doc) {
            $files = $doc->getResourceNode()->getResourceFiles();
            if ($files->isEmpty()) continue;

            $file = $files->first();
            $orig = $file->getOriginalName();
            $ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
            if (! in_array($ext, ['pdf','ppt','pptx','odp'], true)) {
                continue;
            }

            $path      = '/var/upload/resource'.$repo->getFilename($file);
            $base      = str_replace('/public/', '', api_get_path(SYS_PATH));
            $sysPath = $base . $path;

            $out[] = [
                'id'       => $doc->getIid(),
                'url'      => $sysPath,
                'filename' => $orig,
            ];
        }

        header('Content-Type: application/json');
        echo json_encode($out);
        exit;
    default:
        echo json_encode(['error' => 'Invalid action']);
}
