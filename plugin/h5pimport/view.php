<?php

// For licensing terms, see /license.txt

use Chamilo\PluginBundle\Entity\H5pImport\H5pImport;
use Chamilo\PluginBundle\H5pImport\H5pImporter\H5pImplementation;
use Chamilo\PluginBundle\H5pImport\H5pImporter\H5pPackageTools;

require_once __DIR__.'/../../main/inc/global.inc.php';

api_block_anonymous_users();
api_protect_course_script(true);

$plugin = H5pImportPlugin::create();

if ('false' === $plugin->get('tool_enabled')) {
    api_not_allowed(true);
}

$isAllowedToEdit = api_is_allowed_to_edit(true);

$em = Database::getManager();
$embedRepo = $em->getRepository('ChamiloPluginBundle:H5pImport\H5pImport');

$course = api_get_course_entity(api_get_course_int_id());
$session = api_get_session_entity(api_get_session_id());

$h5pImportId = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
$originIsLearnpath = 'learnpath' === api_get_origin();

if (!$h5pImportId) {
    api_not_allowed(true);
}

/** @var null|H5pImport $h5pImport */
$h5pImport = $embedRepo->find($h5pImportId);

if (!$h5pImport) {
    api_not_allowed(
        true,
        Display::return_message($plugin->get_lang('ContentNotFound'), 'danger')
    );
}

if ($course->getId() !== $h5pImport->getCourse()->getId()) {
    api_not_allowed(true);
}

if ($session && $h5pImport->getSession()) {
    if ($session->getId() !== $h5pImport->getSession()->getId()) {
        api_not_allowed(true);
    }
}

if (!$originIsLearnpath) {
    $interbreadcrumb[] = [
        'name' => $plugin->getToolTitle(),
        'url' => api_get_path(WEB_PLUGIN_PATH).$plugin->get_name().'/start.php',
    ];

    $actions = Display::url(
        Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
        api_get_path(WEB_PLUGIN_PATH).$plugin->get_name().'/start.php?'.api_get_cidreq()
    );
}

$formTarget = $originIsLearnpath ? '_self' : '_blank';
$htmlContent = '';
if ($_REQUEST['view']) {
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
        Display::addFlash(
            Display::return_message(get_lang('Error'), 'error')
        );
    } else {
        $coreAssets = H5pPackageTools::getCoreAssets();

        if (!$coreAssets) {
            Display::addFlash(
                Display::return_message($plugin->get_lang('h5p_error_missing_core_asset'), 'danger')
            );
        } else {
            $htmlContent .= Display::div(
                ['class' => 'exercise_overview_options']
            );
            $integration = H5pPackageTools::getCoreSettings($h5pImport, $h5pCore);
            $embedType = H5PCore::determineEmbedType($h5pNode['embedType'], $h5pNode['library']['embedTypes']);
            $integration['contents']['cid-'.$h5pNode['contentId']] =
                H5pPackageTools::getContentSettings($h5pNode, $h5pCore);

            $preloadedDependencies = $h5pCore->loadContentDependencies($h5pNode['id'], 'preloaded');
            $files = $h5pCore->getDependenciesFiles(
                $preloadedDependencies,
                api_get_path(WEB_COURSE_PATH).$course->getDirectory().'/h5p'
            );
            $libraryList = H5pPackageTools::h5pDependenciesToLibraryList($preloadedDependencies);

            foreach ($coreAssets['js'] as $script) {
                $htmlHeadXtra[] = api_get_js_simple($script);
            }
            foreach ($coreAssets['css'] as $style) {
                $htmlHeadXtra[] = api_get_css($style);
            }

            if ('div' === $embedType) {
                foreach ($files['scripts'] as $script) {
                    $htmlHeadXtra[] = api_get_js_simple($script->path.$script->version);
                    $integration['loadedJs'] = $script->path.$script->version;
                }
                foreach ($files['styles'] as $script) {
                    $htmlHeadXtra[] = api_get_css($script->path.$script->version);
                    $integration['loadedCss'][] = $script->path.$script->version;
                }

                $htmlContent = '<div class="h5p-content" data-content-id="'.$h5pNode['contentId'].'"></div>';
            } elseif ('iframe' === $embedType) {
                $integration['core']['scripts'] = $coreAssets['js'];
                $integration['core']['styles'] = $coreAssets['css'];
                $integration['contents']['cid-'.$h5pNode['contentId']]['styles'] =
                    $h5pCore->getAssetsUrls($files['styles']);
                $integration['contents']['cid-'.$h5pNode['contentId']]['scripts'] =
                    $h5pCore->getAssetsUrls($files['scripts']);

                $htmlContent = '<div class="h5p-iframe-wrapper">
                        <iframe
                            id="h5p-iframe-'.$h5pNode['contentId'].'"
                            class="h5p-iframe"
                            data-content-id="'.$h5pNode['contentId'].'"
                            style="height:1px"
                            src="about:blank" frameBorder="0" scrolling="no"
                            allowfullscreen="allowfullscreen"
                            allow="geolocation *; microphone *; camera *; midi *; encrypted-media *"
                            title="'.$h5pNode['title'].'">
                        </iframe>
                    </div>';
            }

            if (!isset($htmlContent)) {
                Display::addFlash(
                    Display::return_message($plugin->get_lang('h5p_error_loading'), 'danger')
                );
            } else {
                $htmlContent .= '<script> H5PIntegration = '.json_encode($integration).'</script>';
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
    $htmlContent = Display::div(
        $frmNewAttempt->returnForm(),
        ['class' => 'exercise_overview_options']
    );
}

$view = new Template($h5pImport->getName());
$view->assign('header', $h5pImport->getName());
if (!$originIsLearnpath) {
    $view->assign('actions', Display::toolbarAction($plugin->get_name(), [$actions]));
}
$view->assign(
    'content',
    $htmlContent
);

$view->display_one_col_template();
