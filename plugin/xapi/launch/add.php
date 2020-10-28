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
$frmActivity->addHeader($langAddActivity);
$frmActivity->addFile('file', $plugin->get_lang('TinCanPackage'));
$frmActivity->addButtonAdvancedSettings('advanced_params');
$frmActivity->addHtml('<div id="advanced_params_options" style="display:none">');
$frmActivity->addText('title', get_lang('Title'), false);
$frmActivity->addTextarea('description', get_lang('Description'));
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

    if (!empty($values['title'])) {
        $toolLaunch->setTitle($values['title']);
    }

    if (!empty($values['description'])) {
        $toolLaunch->setDescription($values['description']);
    }

    $em = Database::getManager();
    $em->persist($toolLaunch);
    $em->flush();

    $plugin->createLaunchCourseTool($toolLaunch);

    Display::addFlash(
        Display::return_message($plugin->get_lang('ActivityImported'), 'success')
    );

    header('Location: '.api_get_course_url());
    exit;
}

$pageTitle = $plugin->get_title();
$pageContent = $frmActivity->returnForm();

$interbreadcrumb[] = ['url' => 'list.php', 'name' => $pageTitle];

$view = new Template($langAddActivity);
$view->assign('header', $pageTitle);
$view->assign('content', $pageContent);
$view->display_one_col_template();
