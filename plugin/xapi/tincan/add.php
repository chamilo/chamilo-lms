<?php

/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\XApi\Importer\TinCanImporter;
use Chamilo\PluginBundle\XApi\Parser\TinCanParser;

require_once __DIR__.'/../../../main/inc/global.inc.php';

api_protect_course_script(true);
api_protect_teacher_script();

$course = api_get_course_entity();
$session = api_get_session_entity();

$plugin = XApiPlugin::create();
$langAddActivity = $plugin->get_lang('AddActivity');

$frmActivity = new FormValidator('frm_activity', 'post', api_get_self().'?'.api_get_cidreq());
$frmActivity->addFile('file', $plugin->get_lang('TinCanPackage'));
$frmActivity->addCheckBox('allow_multiple_attempts', '', get_lang('AllowMultipleAttempts'));
$frmActivity->addButtonAdvancedSettings('advanced_params');
$frmActivity->addHtml('<div id="advanced_params_options" style="display:none">');
$frmActivity->addText('title', get_lang('Title'), false);
$frmActivity->addTextarea('description', get_lang('Description'));
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
    'lrs_auth',
    [
        $plugin->get_lang('lrs_auth_username'),
        $plugin->get_lang('lrs_auth_username_help'),
    ],
    false
);
$frmActivity->addText(
    'lrs_auth',
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
    $plugin->get_lang('OnlyZipAllowed'),
    'filetype',
    ['zip']
);
$frmActivity->applyFilter('title', 'trim');
$frmActivity->applyFilter('description', 'trim');
$frmActivity->applyFilter('lrs_url', 'trim');
$frmActivity->applyFilter('lrs_auth', 'trim');

if ($frmActivity->validate()) {
    $values = $frmActivity->exportValues();
    $zipFileInfo = $_FILES['file'];

    try {
        $tinCanFile = TinCanImporter::create($zipFileInfo, $course)->import();

        $toolLaunch = TinCanParser::create($tinCanFile, $course, $session)->parse();
    } catch (Exception $e) {
        Display::addFlash(
            Display::return_message($e->getMessage(), 'error')
        );

        exit;
    }

    $toolLaunch->setAllowMultipleAttempts(
        isset($values['allow_multiple_attempts'])
    );

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
            ->setLrsAuthUsername($values['lrs_auth_password']);
    }

    $em = Database::getManager();
    $em->persist($toolLaunch);
    $em->flush();

    Display::addFlash(
        Display::return_message($plugin->get_lang('ActivityImported'), 'success')
    );

    header('Location: '.api_get_course_url());
    exit;
}

$frmActivity->setDefaults(['allow_multiple_attempts' => true]);

$actions = Display::url(
    Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
    'index.php?'.api_get_cidreq()
);

$pageContent = $frmActivity->returnForm();

$interbreadcrumb[] = ['url' => 'index.php', 'name' => $plugin->get_lang('ToolTinCan')];

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
