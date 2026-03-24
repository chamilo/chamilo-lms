<?php

// For licensing terms, see /license.txt

use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Entity\CLpItemView;
use Chamilo\PluginBundle\H5pImport\Entity\H5pImport;
use Chamilo\PluginBundle\H5pImport\Entity\H5pImportResults;
use ChamiloSession as Session;

require_once __DIR__.'/../../../main/inc/global.inc.php';

api_block_anonymous_users();
api_protect_course_script(true);

$plugin = H5pImportPlugin::create();
if (!$plugin->isToolEnabled()) {
    H5PCore::ajaxError(get_lang('NotAllowed'));
}

$action = $_REQUEST['action'] ?? null;
$h5pId = isset($_REQUEST['h5pId']) ? (int) $_REQUEST['h5pId'] : 0;

$course = api_get_course_entity(api_get_course_int_id());
$session = api_get_session_entity(api_get_session_id());
$user = api_get_user_entity(api_get_user_id());

$em = Database::getManager();
$h5pImportRepo = $em->getRepository(H5pImport::class);

/** @var H5pImport|null $h5pImport */
$h5pImport = $h5pId > 0 ? $h5pImportRepo->find($h5pId) : null;

if (
    !$h5pImport
    || $course->getId() !== $h5pImport->getCourse()->getId()
    || (($session && $h5pImport->getSession()) && $session->getId() !== $h5pImport->getSession()->getId())
) {
    H5PCore::ajaxError($plugin->get_lang('ContentNotFound'));
}

if ('set_finished' === $action) {
    if (!H5PCore::validToken('result', (string) filter_input(INPUT_GET, 'token'))) {
        H5PCore::ajaxError($plugin->get_lang('h5p_error_invalid_token'));
    }

    $score = filter_input(INPUT_POST, 'score', FILTER_VALIDATE_INT);
    $maxScore = filter_input(INPUT_POST, 'maxScore', FILTER_VALIDATE_INT);
    $opened = filter_input(INPUT_POST, 'opened', FILTER_VALIDATE_INT);

    if (false === $score || false === $maxScore || false === $opened) {
        H5PCore::ajaxError();
    }

    $h5pImportResults = new H5pImportResults();
    $h5pImportResults->setH5pImport($h5pImport);
    $h5pImportResults->setCourse($course);
    $h5pImportResults->setSession($session);
    $h5pImportResults->setUser($user);
    $h5pImportResults->setScore((int) $score);
    $h5pImportResults->setMaxScore((int) $maxScore);
    $h5pImportResults->setStartTime((int) $opened);
    $h5pImportResults->setTotalTime(max(0, time() - (int) $opened));

    $em->persist($h5pImportResults);

    if (1 === (int) ($_REQUEST['learnpath'] ?? 0) && Session::has('oLP')) {
        $lpObject = Session::read('oLP');
        $clpItemViewRepo = $em->getRepository(CLpItemView::class);

        /** @var CLpItemView|null $lpItemView */
        $lpItemView = $clpItemViewRepo->findOneBy([
            'lpViewId' => $lpObject->lp_view_id,
            'lpItemId' => $lpObject->current,
        ]);

        if ($lpItemView instanceof CLpItemView) {
            /** @var CLpItem|null $lpItem */
            $lpItem = $em->find(CLpItem::class, $lpItemView->getLpItemId());

            if ($lpItem instanceof CLpItem && 'h5p' === $lpItem->getItemType()) {
                $lpItemView->setScore((int) $score);
                $lpItemView->setMaxScore((int) $maxScore);
                $lpItemView->setStatus('completed');
                $lpItemView->setTotalTime($lpItemView->getTotalTime() + $h5pImportResults->getTotalTime());
                $lpItem->setMaxScore((int) $maxScore);
                $h5pImportResults->setCLpItemView($lpItemView);

                $em->persist($lpItem);
                $em->persist($lpItemView);
            }
        }
    }

    $em->flush();
    H5PCore::ajaxSuccess();
}

if ('content_user_data' === $action) {
    if (!H5PCore::validToken('content', (string) filter_input(INPUT_GET, 'token'))) {
        H5PCore::ajaxError($plugin->get_lang('h5p_error_invalid_token'));
    }

    H5PCore::ajaxSuccess([
        [
            'state' => '{}',
        ],
    ]);
}

H5PCore::ajaxError(get_lang('InvalidAction'));
