<?php

// For licensing terms, see /license.txt

use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Entity\CLpItemView;
use Chamilo\PluginBundle\Entity\H5pImport\H5pImport;
use Chamilo\PluginBundle\Entity\H5pImport\H5pImportResults;
use ChamiloSession as Session;

require_once __DIR__.'/../../../main/inc/global.inc.php';

$action = $_REQUEST['action'] ?? null;
$h5pId = isset($_REQUEST['h5pId']) ? intval($_REQUEST['h5pId']) : 0;

$course = api_get_course_entity(api_get_course_int_id());
$session = api_get_session_entity(api_get_session_id());

$plugin = H5pImportPlugin::create();
$em = Database::getManager();
$h5pImportRepo = $em->getRepository('ChamiloPluginBundle:H5pImport\H5pImport');
$user = api_get_user_entity(api_get_user_id());

if ('set_finished' === $action && 0 !== $h5pId) {
    if (!H5PCore::validToken('result', filter_input(INPUT_GET, 'token'))) {
        H5PCore::ajaxError($plugin->get_lang('h5p_error_invalid_token'));
    }

    if (is_numeric($_POST['score']) && is_numeric($_POST['maxScore'])) {
        /** @var null|H5pImport $h5pImport */
        $h5pImport = $h5pImportRepo->find($h5pId);
        $entityManager = Database::getManager();

        $h5pImportResults = new H5pImportResults();
        $h5pImportResults->setH5pImport($h5pImport);
        $h5pImportResults->setCourse($course);
        $h5pImportResults->setSession($session);
        $h5pImportResults->setUser($user);
        $h5pImportResults->setScore((int) $_POST['score']);
        $h5pImportResults->setMaxScore((int) $_POST['maxScore']);
        $h5pImportResults->setStartTime((int) $_POST['opened']);
        $h5pImportResults->setTotalTime(time() - $_POST['opened']);

        $entityManager->persist($h5pImportResults);

        // If it comes from an LP, update in c_lp_item_view
        if (1 == $_REQUEST['learnpath'] && Session::has('oLP')) {
            $lpObject = Session::read('oLP');
            $clpItemViewRepo = $em->getRepository('ChamiloCourseBundle:CLpItemView');

            /** @var null|CLpItemView $lpItemView */
            $lpItemView = $clpItemViewRepo->findOneBy(
                [
                    'lpViewId' => $lpObject->lp_view_id,
                    'lpItemId' => $lpObject->current,
                ]
            );

            /** @var null|CLpItem $lpItem */
            $lpItem = $entityManager->find('ChamiloCourseBundle:CLpItem', $lpItemView->getLpItemId());
            if ('h5p' !== $lpItem->getItemType()) {
                return null;
            }

            $lpItemView->setScore($_POST['score']);
            $lpItemView->setMaxScore($_POST['maxScore']);
            $lpItemView->setStatus('completed');
            $lpItemView->setTotalTime($lpItemView->getTotalTime() + $h5pImportResults->getTotalTime());
            $lpItem->setMaxScore($_POST['maxScore']);
            $h5pImportResults->setCLpItemView($lpItemView);
            $entityManager->persist($h5pImportResults);
            $entityManager->persist($lpItem);
            $entityManager->persist($lpItemView);
        }
        $entityManager->flush();

        H5PCore::ajaxSuccess();
    } else {
        H5PCore::ajaxError();
    }
} elseif ('content_user_data' === $action && 0 !== $h5pId) {
    if (!H5PCore::validToken('content', filter_input(INPUT_GET, 'token'))) {
        H5PCore::ajaxError($plugin->get_lang('h5p_error_invalid_token'));
    }

    /** @var null|H5pImport $h5pImport */
    $h5pImport = $h5pImportRepo->find($h5pId);
} else {
    H5PCore::ajaxError(get_lang('InvalidAction'));
}
