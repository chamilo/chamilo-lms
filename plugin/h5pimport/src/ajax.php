<?php

use Chamilo\PluginBundle\Entity\H5pImport\H5pImport;
use Chamilo\PluginBundle\Entity\H5pImport\H5pImportResults;

require_once __DIR__.'/../../../main/inc/global.inc.php';

$action = $_REQUEST['action'] ?? null;
$h5pId = isset($_REQUEST['h5pId']) ? intval($_REQUEST['h5pId']) : 0;

$course = api_get_course_entity(api_get_course_int_id());
$session = api_get_session_entity(api_get_session_id());

$plugin = H5pImportPlugin::create();
$em = Database::getManager();
$h5pImportRepo = $em->getRepository('ChamiloPluginBundle:H5pImport\H5pImport');
$user = api_get_user_entity(api_get_user_id());


if ($action === 'set_finished' && $h5pId !== 0) {

    if (!H5PCore::validToken('result', filter_input(INPUT_GET, 'token'))) {
        H5PCore::ajaxError($plugin->get_lang('h5p_error_invalid_token'));
    }

    if (is_numeric($_POST['score']) && is_numeric($_POST['maxScore'])) {

        /** @var H5pImport|null $h5pImport */
        $h5pImport = $h5pImportRepo->find($h5pId);
        $entityManager = Database::getManager();

        $h5pImportResults = new H5pImportResults();
        $h5pImportResults->setH5pImport($h5pImport);
        $h5pImportResults->setCourse($course);
        $h5pImportResults->setSession($session);
        $h5pImportResults->setUser($user);
        $h5pImportResults->setScore($_POST['score']);
        $h5pImportResults->setMaxScore($_POST['maxScore']);
        $entityManager->persist($h5pImportResults);
        $entityManager->flush();

        H5PCore::ajaxSuccess();
    } else {
        H5PCore::ajaxError();
    }

} elseif ($action === 'content_user_data' && $h5pId !== 0) {

    if (!H5PCore::validToken('content', filter_input(INPUT_GET, 'token'))) {
        H5PCore::ajaxError($plugin->get_lang('h5p_error_invalid_token'));
    }

    /** @var H5pImport|null $h5pImport */
    $h5pImport = $h5pImportRepo->find($h5pId);

} else{
    H5PCore::ajaxError(get_lang('InvalidAction'));
}
