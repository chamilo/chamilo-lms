<?php
/* For licensing terms, see /license.txt */

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

if (!$h5pImportId) {
    api_not_allowed(true);
}

/** @var H5pImport|null $h5pImport */
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

$interbreadcrumb[] = [
    'name' => $plugin->getToolTitle(),
    'url' => api_get_path(WEB_PLUGIN_PATH).$plugin->get_name().'/start.php',
];

$actions = Display::url(
    Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
    api_get_path(WEB_PLUGIN_PATH).$plugin->get_name().'/start.php?'.api_get_cidreq()
);

//ToDo Visualizar paquete h5P

$aux = new H5pImplementation($h5pImport);
$aux2 = new H5PCore($aux, $h5pImport->getPath(), 'as');

$h5pNode = $aux2->loadContent($h5pImport->getIid());

$coreSettings = H5pPackageTools::getCoreSettings($h5pImport);

$embed = H5PCore::determineEmbedType($h5pNode['embedType'], $h5pNode['library']['embedTypes']);

die(print_r($embed));

// -------------------

$view = new Template($h5pImport->getName());
$view->assign('header', $h5pImport->getName());
$view->assign('actions', Display::toolbarAction($plugin->get_name(), [$actions]));
$view->assign(
    'content',
    '<p> hola</p>'
        .PHP_EOL
        .Security::remove_XSS($h5pImport->getName(), COURSEMANAGERLOWSECURITY)
);
$view->display_one_col_template();
