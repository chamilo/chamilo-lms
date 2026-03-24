<?php

// For licensing terms, see /license.txt

use Chamilo\PluginBundle\H5pImport\Entity\H5pImport;
use Chamilo\PluginBundle\H5pImport\H5pImporter\H5pImplementation;
use Chamilo\PluginBundle\H5pImport\H5pImporter\H5pPackageTools;

require_once __DIR__.'/../../main/inc/global.inc.php';

api_block_anonymous_users();
api_protect_course_script(true);

$plugin = H5pImportPlugin::create();
if (!$plugin->isToolEnabled()) {
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

if (
    !$h5pImport
    || $course->getId() !== $h5pImport->getCourse()->getId()
    || (($session && $h5pImport->getSession()) && $session->getId() !== $h5pImport->getSession()->getId())
) {
    api_not_allowed(
        true,
        Display::return_message($plugin->get_lang('ContentNotFound'), 'danger')
    );
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

$formTarget = $originIsLearnpath ? '_self' : '_blank';
$htmlContent = '';

if (isset($_REQUEST['view'])) {
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
            $integration = H5pPackageTools::getCoreSettings($h5pImport, $h5pCore);
            $integration['contents']['cid-'.$h5pNode['contentId']] = H5pPackageTools::getContentSettings($h5pNode, $h5pCore);
            $integration['loadedJs'] = [];
            $integration['loadedCss'] = [];

            $embedType = H5PCore::determineEmbedType($h5pNode['embedType'], $h5pNode['library']['embedTypes']);
            $preloadedDependencies = $h5pCore->loadContentDependencies($h5pNode['id'], 'preloaded');
            $files = $h5pCore->getDependenciesFiles(
                $preloadedDependencies,
                api_get_path(WEB_COURSE_PATH).$course->getDirectory().'/h5p'
            );

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
                    $integration['loadedJs'][] = $assetPath;
                }

                foreach ($files['styles'] as $style) {
                    $assetPath = $style->path.$style->version;
                    $htmlHeadXtra[] = api_get_css($assetPath);
                    $integration['loadedCss'][] = $assetPath;
                }

                $htmlContent = '<div class="h5p-content" data-content-id="'.$h5pNode['contentId'].'"></div>';
            } elseif ('iframe' === $embedType) {
                $integration['core']['scripts'] = $coreAssets['js'];
                $integration['core']['styles'] = $coreAssets['css'];
                $integration['contents']['cid-'.$h5pNode['contentId']]['styles'] = $h5pCore->getAssetsUrls($files['styles']);
                $integration['contents']['cid-'.$h5pNode['contentId']]['scripts'] = $h5pCore->getAssetsUrls($files['scripts']);

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
            }

            if ('' !== $htmlContent) {
                $htmlContent .= '<script>window.H5PIntegration = '.json_encode($integration).';</script>';
            } else {
                Display::addFlash(
                    Display::return_message($plugin->get_lang('h5p_error_loading'), 'danger')
                );
            }
        }
    }
} else {
    $frmNewAttempt = new FormValidator(
        'view',
        'post',
        $plugin->getViewUrl($h5pImport).'&view=1',
        '',
        ['target' => $formTarget],
        FormValidator::LAYOUT_INLINE
    );
    $frmNewAttempt->addHidden('id', $h5pImport->getIid());
    $frmNewAttempt->addButton(
        'submit',
        $plugin->get_lang('start_attempt'),
        'external-link fa-fw',
        'success'
    );

    $htmlContent = Display::div($frmNewAttempt->returnForm(), ['class' => 'exercise_overview_options']);
}

$view = new Template($h5pImport->getName());
$view->assign('header', $h5pImport->getName());

if (!$originIsLearnpath) {
    $view->assign('actions', Display::toolbarAction($plugin->get_name(), [$actions]));
}

$view->assign('content', $htmlContent);
$view->display_one_col_template();
