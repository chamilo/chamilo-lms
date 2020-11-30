<?php

/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\XApi\Importer\Cmi5Importer;
use Chamilo\PluginBundle\XApi\Parser\Cmi5Parser;

require_once __DIR__.'/../../../main/inc/global.inc.php';

api_protect_course_script(true);
api_protect_teacher_script();

$course = api_get_course_entity();
$session = api_get_session_entity();

$plugin = XApiPlugin::create();

$frmActivity = new FormValidator('frm_cmi5', 'post', api_get_self().'?'.api_get_cidreq());
$frmActivity->addFile('file', $plugin->get_lang('Cmi5Package'));
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
        $packageFile = Cmi5Importer::create($zipFileInfo, $course)->import();

        $parser = Cmi5Parser::create($packageFile, $course, $session);

        $toolLaunch = $parser->parse();
    } catch (Exception $e) {
        Display::addFlash(
            Display::return_message($e->getMessage(), 'error')
        );

        exit;
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

    foreach ($parser->getToc() as $cmi5Item) {
        $cmi5Item->setTool($toolLaunch);

        $em->persist($cmi5Item);
    }

    $em->flush();

    Display::addFlash(
        Display::return_message($plugin->get_lang('ActivityImported'), 'success')
    );

    header('Location: '.api_get_course_url());
    exit;
}

$frmActivity->setDefaults(['allow_multiple_attempts' => true]);

$pageTitle = $plugin->get_title();
$pageContent = $frmActivity->returnForm();

$interbreadcrumb[] = ['url' => '../tincan/index.php', 'name' => $plugin->get_lang('ToolTinCan')];

$langAddActivity = $plugin->get_lang('AddActivity');


$view = new Template($langAddActivity);
$view->assign('header', $langAddActivity);
$view->assign('content', $pageContent);
$view->display_one_col_template();
