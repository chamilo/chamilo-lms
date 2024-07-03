<?php

/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\XApi\Importer\PackageImporter;
use Chamilo\PluginBundle\XApi\Parser\PackageParser;

require_once __DIR__.'/../../main/inc/global.inc.php';

api_protect_course_script(true);
api_protect_teacher_script();

$httpRequest = \Symfony\Component\HttpFoundation\Request::createFromGlobals();

$course = api_get_course_entity();
$session = api_get_session_entity();
$cidReq = api_get_cidreq();
$origin = api_get_origin();
$userId = api_get_user_id();

$plugin = XApiPlugin::create();
$pluginIndex = "./start.php?$cidReq";

$lp = null;

if ('lp' === $origin && $httpRequest->query->has('lp_id')) {
    $lp = new learnpath('', $httpRequest->query->getInt('lp_id'), $userId);

    if (!empty($lp->lp_id)) {
        $pluginIndex = api_get_path(WEB_CODE_PATH)."lp/lp_controller.php?$cidReq&"
            .http_build_query(['action' => 'add_item', 'type' => 'step', 'lp' => $lp->lp_id, 'lp_build_selected' => 8]);
    }
}

$langAddActivity = $plugin->get_lang('AddActivity');

$formAction = api_get_self()."?$cidReq&".($lp ? http_build_query(['lp_id' => $lp->lp_id]) : '');

$frmActivity = new FormValidator('frm_activity', 'post', $formAction);
$frmActivity->addFile('file', $plugin->get_lang('XApiPackage'));
$frmActivity->addButtonAdvancedSettings('advanced_params');
$frmActivity->addHtml('<div id="advanced_params_options" style="display:none">');
$frmActivity->addText('title', get_lang('Title'), false);
$frmActivity->addTextarea('description', get_lang('Description'));
$frmActivity->addCheckBox('allow_multiple_attempts', '', $plugin->get_lang('TinCanAllowMultipleAttempts'));
$frmActivity->addHtml('</div>');
$frmActivity->addButtonAdvancedSettings('lrs_params', $plugin->get_lang('LrsConfiguration'));
$frmActivity->addHtml('<div id="lrs_params_options" style="display:none">');
$frmActivity->addText(
    'lrs_url',
    [
        $plugin->get_lang('lrs_url'),
        $plugin->get_lang('lrs_url_help'),
    ],
    false
);
$frmActivity->addText(
    'lrs_auth_username',
    [
        $plugin->get_lang('lrs_auth_username'),
        $plugin->get_lang('lrs_auth_username_help'),
    ],
    false
);
$frmActivity->addText(
    'lrs_auth_password',
    [
        $plugin->get_lang('lrs_auth_password'),
        $plugin->get_lang('lrs_auth_password_help'),
    ],
    false
);
$frmActivity->addHtml('</div>');
$frmActivity->addButtonImport(get_lang('Import'));
$frmActivity->addRule('file', get_lang('ThisFileIsRequired'), 'required');
$frmActivity->addRule(
    'file',
    $plugin->get_lang('OnlyZipOrXmlAllowed'),
    'filetype',
    ['zip', 'xml']
);
$frmActivity->applyFilter('title', 'trim');
$frmActivity->applyFilter('description', 'trim');
$frmActivity->applyFilter('lrs_url', 'trim');
$frmActivity->applyFilter('lrs_auth', 'trim');

if ($frmActivity->validate()) {
    $values = $frmActivity->exportValues();
    $zipFileInfo = $_FILES['file'];

    try {
        $importer = PackageImporter::create($zipFileInfo, $course);
        $packageFile = $importer->import();

        $parser = PackageParser::create(
            $importer->getPackageType(),
            $packageFile,
            $course,
            $session
        );
        $toolLaunch = $parser->parse();
    } catch (Exception $e) {
        Display::addFlash(
            Display::return_message($e->getMessage(), 'error')
        );

        header("Location: $pluginIndex");
        exit;
    }

    if ('tincan' === $importer->getPackageType() && isset($values['allow_multiple_attempts'])) {
        $toolLaunch->setAllowMultipleAttempts(true);
    }

    if (!empty($values['title'])) {
        $toolLaunch->setTitle($values['title']);
    }

    if (!empty($values['description'])) {
        $toolLaunch->setDescription($values['description']);
    }

    if (!empty($values['lrs_url'])
        && !empty($values['lrs_auth_username'])
        && !empty($values['lrs_auth_password'])
    ) {
        $toolLaunch
            ->setLrsUrl($values['lrs_url'])
            ->setLrsAuthUsername($values['lrs_auth_username'])
            ->setLrsAuthPassword($values['lrs_auth_password']);
    }

    $em = Database::getManager();
    $em->persist($toolLaunch);
    $em->flush();

    Display::addFlash(
        Display::return_message($plugin->get_lang('ActivityImported'), 'success')
    );

    header("Location: $pluginIndex");
    exit;
}

$frmActivity->setDefaults(['allow_multiple_attempts' => true]);

$actions = Display::url(
    Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
    $pluginIndex
);

$pageContent = $frmActivity->returnForm();

if ($lp) {
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'lp/lp_controller.php?action=list&'.api_get_cidreq(),
        'name' => get_lang('LearningPaths'),
    ];
    $interbreadcrumb[] = [
        'url' => $pluginIndex,
        'name' => $lp->getNameNoTags(),
    ];
} else {
    $interbreadcrumb[] = ['url' => $pluginIndex, 'name' => $plugin->get_lang('ToolTinCan')];
}

$view = new Template($langAddActivity);
$view->assign('header', $langAddActivity);
$view->assign(
    'actions',
    Display::toolbarAction(
        'xapi_actions',
        [$actions]
    )
);
$view->assign('content', $pageContent);
$view->display_one_col_template();
