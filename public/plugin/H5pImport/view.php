<?php
/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\H5pImport\Entity\H5pImport;
use Chamilo\PluginBundle\H5pImport\H5pImporter\H5pImplementation;
use Chamilo\PluginBundle\H5pImport\H5pImporter\H5pPackageTools;
use Chamilo\PluginBundle\H5pImport\Entity\H5pImportResults;
use ChamiloSession as Session;

$course_plugin = 'h5pimport';
require_once __DIR__.'/config.php';

if (!function_exists('h5pimport_build_view_url')) {
    function h5pimport_build_view_url(string $baseUrl, array $params = []): string
    {
        if (empty($params)) {
            return $baseUrl;
        }

        $separator = '' !== (string) parse_url($baseUrl, PHP_URL_QUERY) ? '&' : '?';

        return $baseUrl.$separator.http_build_query($params);
    }
}

api_block_anonymous_users();
api_protect_course_script(true);

$plugin = H5pImportPlugin::create();

if (!h5pimport_is_plugin_active()) {
    api_not_allowed(true);
}

$em = Database::getManager();
$embedRepo = $em->getRepository(H5pImport::class);

$course = api_get_course_entity(api_get_course_int_id());
$session = api_get_session_entity(api_get_session_id());

$h5pImportId = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
$originIsLearnpath = 'learnpath' === api_get_origin();

if (!$h5pImportId) {
    api_not_allowed(true);
}

/** @var H5pImport|null $h5pImport */
$h5pImport = $embedRepo->find($h5pImportId);

$matchesContext = static function (H5pImport $item) use ($course, $session): bool {
    if ($course->getId() !== $item->getCourse()->getId()) {
        return false;
    }

    $itemSession = $item->getSession();

    if (null === $session) {
        return null === $itemSession;
    }

    if (null === $itemSession) {
        return false;
    }

    return $session->getId() === $itemSession->getId();
};

if (!$h5pImport || !$matchesContext($h5pImport)) {
    api_not_allowed(
        true,
        Display::return_message($plugin->get_lang('ContentNotFound'), 'danger')
    );
}

if (!isset($_REQUEST['view'])) {
    $redirectUrl = h5pimport_build_view_url($plugin->getViewUrl($h5pImport), ['view' => 1]);
    header('Location: '.$redirectUrl);
    exit;
}

if (!$originIsLearnpath) {
    $interbreadcrumb[] = [
        'name' => $plugin->getToolTitle(),
        'url' => api_get_path(WEB_PLUGIN_PATH).$plugin->get_name().'/start.php?'.api_get_cidreq(),
    ];

    $actions = Display::url(
        Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
        api_get_path(WEB_PLUGIN_PATH).$plugin->get_name().'/start.php?'.api_get_cidreq()
    );
}

$htmlContent = '';

$launchSessionKey = 'h5p_import_launch_id_'.$h5pImport->getIid();

if (!api_is_anonymous()) {
    $resultsRepo = $em->getRepository(H5pImportResults::class);
    $existingLaunchId = Session::read($launchSessionKey);

    /** @var H5pImportResults|null $existingLaunch */
    $existingLaunch = null;

    if (!empty($existingLaunchId)) {
        $existingLaunch = $resultsRepo->find((int) $existingLaunchId);

        if (
            $existingLaunch instanceof H5pImportResults
            && (
                $existingLaunch->getH5pImport()->getIid() !== $h5pImport->getIid()
                || $existingLaunch->getCourse()->getId() !== $course->getId()
                || $existingLaunch->getUser()->getId() !== api_get_user_id()
            )
        ) {
            $existingLaunch = null;
        }
    }

    $shouldCreateLaunch = true;

    if ($existingLaunch instanceof H5pImportResults) {
        $startedAt = (int) $existingLaunch->getStartTime();
        $elapsed = time() - $startedAt;

        if ($startedAt > 0 && $elapsed >= 0 && $elapsed < 30) {
            $shouldCreateLaunch = false;
        }
    }

    if ($shouldCreateLaunch) {
        $launch = new H5pImportResults();
        $launch->setH5pImport($h5pImport);
        $launch->setCourse($course);
        $launch->setSession($session);
        $launch->setUser(api_get_user_entity(api_get_user_id()));
        $launch->setScore(0);
        $launch->setMaxScore(0);
        $launch->setStartTime(time());
        $launch->setTotalTime(0);

        $em->persist($launch);
        $em->flush();

        Session::write($launchSessionKey, $launch->getIid());
    }
}

$interface = new H5pImplementation($h5pImport);
$h5pCore = new H5PCore(
    $interface,
    $h5pImport->getPath(),
    api_get_self(),
    'en',
    false
);

$h5pNode = $h5pCore->loadContent($h5pImport->getIid());

if (empty($h5pNode)) {
    Display::addFlash(Display::return_message(get_lang('Error'), 'error'));
} else {
    $coreAssets = H5pPackageTools::getCoreAssets();

    if (!$coreAssets) {
        Display::addFlash(
            Display::return_message($plugin->get_lang('h5p_error_missing_core_asset'), 'danger')
        );
    } else {
        $packageTools = new H5pPackageTools();

        $integration = H5pPackageTools::getCoreSettings($h5pImport, $h5pCore);
        $integration['contents']['cid-'.$h5pNode['contentId']] = $packageTools->getContentSettings($h5pNode);

        $mainLibrary = $h5pImport->getMainLibrary();
        if (null !== $mainLibrary) {
            $integration['contents']['cid-'.$h5pNode['contentId']]['library'] =
                $mainLibrary->getMachineName().' '.$mainLibrary->getMajorVersion().'.'.$mainLibrary->getMinorVersion();
        }

        $integration['loadedJs'] = [];
        $integration['loadedCss'] = [];

        $embedType = H5PCore::determineEmbedType(
            $h5pNode['embedType'],
            $h5pNode['library']['embedTypes']
        );

        $preloadedDependencies = $h5pCore->loadContentDependencies($h5pNode['id'], 'preloaded');
        $files = $h5pCore->getDependenciesFiles(
            $preloadedDependencies,
            H5pPackageTools::getCourseLibrariesAssetBaseUrl($course)
        );

        if ('div' === $embedType) {
            foreach ($files['scripts'] as $script) {
                $integration['loadedJs'][] = $script->path.$script->version;
            }

            foreach ($files['styles'] as $style) {
                $integration['loadedCss'][] = $style->path.$style->version;
            }
        } elseif ('iframe' === $embedType) {
            $integration['core']['scripts'] = $coreAssets['js'];
            $integration['core']['styles'] = $coreAssets['css'];
            $integration['contents']['cid-'.$h5pNode['contentId']]['styles'] = $h5pCore->getAssetsUrls($files['styles']);
            $integration['contents']['cid-'.$h5pNode['contentId']]['scripts'] = $h5pCore->getAssetsUrls($files['scripts']);
        }

        $htmlHeadXtra[] = '<script>window.H5PIntegration = '.json_encode(
                $integration,
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            ).';</script>';

        foreach ($coreAssets['js'] as $script) {
            $htmlHeadXtra[] = api_get_js_simple($script);
        }

        foreach ($coreAssets['css'] as $style) {
            $htmlHeadXtra[] = api_get_css($style);
        }

        if ('div' === $embedType) {
            foreach ($files['scripts'] as $script) {
                $assetPath = $script->path.$script->version;
                $htmlHeadXtra[] = api_get_js_simple($assetPath);
            }

            foreach ($files['styles'] as $style) {
                $assetPath = $style->path.$style->version;
                $htmlHeadXtra[] = api_get_css($assetPath);
            }

            $htmlContent = '<div class="h5p-content" data-content-id="'.$h5pNode['contentId'].'"></div>';
        } elseif ('iframe' === $embedType) {
            $htmlContent = '<div class="h5p-iframe-wrapper">
                <iframe
                    id="h5p-iframe-'.$h5pNode['contentId'].'"
                    class="h5p-iframe"
                    data-content-id="'.$h5pNode['contentId'].'"
                    style="height:1px"
                    src="about:blank"
                    frameBorder="0"
                    scrolling="no"
                    allowfullscreen="allowfullscreen"
                    allow="geolocation *; microphone *; camera *; midi *; encrypted-media *"
                    title="'.Security::remove_XSS($h5pNode['title']).'">
                </iframe>
            </div>';
        } else {
            Display::addFlash(
                Display::return_message($plugin->get_lang('h5p_error_loading'), 'danger')
            );
        }
    }
}

$view = new Template($h5pImport->getName());
$view->assign('header', $h5pImport->getName());

if (!$originIsLearnpath) {
    $view->assign('actions', Display::toolbarAction($plugin->get_name(), [$actions]));
}

$view->assign('content', $htmlContent);
$view->display_one_col_template();
