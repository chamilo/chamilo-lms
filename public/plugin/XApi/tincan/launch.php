<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\XApiToolLaunch;
use Chamilo\CoreBundle\Framework\Container;

require_once __DIR__.'/../../../main/inc/global.inc.php';

api_block_anonymous_users();
api_protect_course_script(true);

$request = Container::getRequest();

$user = api_get_user_entity(api_get_user_id());

$em = Database::getManager();

$attemptId = trim((string) $request->request->get('attempt_id'));
$toolLaunch = $em->find(
    XApiToolLaunch::class,
    $request->request->getInt('id')
);

if ('' === $attemptId
    || null === $toolLaunch
    || $toolLaunch->getCourse()->getId() !== api_get_course_entity()->getId()
) {
    api_not_allowed(true);
}

$plugin = XApiPlugin::create();
$actor = $plugin->buildTinCanActorPayload($user);
$stateId = $plugin->getTinCanStateId($toolLaunch->getId());

$nowDate = api_get_utc_datetime(null, false, true)->format('c');

try {
    $stateDocument = $plugin->fetchActivityStateDocument(
        (string) $toolLaunch->getActivityId(),
        $actor,
        $stateId,
        null,
        $toolLaunch->getLrsUrl(),
        $toolLaunch->getLrsAuthUsername(),
        $toolLaunch->getLrsAuthPassword()
    );

    if (!is_array($stateDocument)) {
        $stateDocument = [];
    }

    if (isset($stateDocument[$attemptId]) && is_array($stateDocument[$attemptId])) {
        $stateDocument[$attemptId][XApiPlugin::STATE_LAST_LAUNCH] = $nowDate;
    } else {
        $stateDocument[$attemptId] = [
            XApiPlugin::STATE_FIRST_LAUNCH => $nowDate,
            XApiPlugin::STATE_LAST_LAUNCH => $nowDate,
        ];
    }

    uasort(
        $stateDocument,
        static function ($attemptA, $attemptB): int {
            $timeA = isset($attemptA[XApiPlugin::STATE_LAST_LAUNCH])
                ? strtotime((string) $attemptA[XApiPlugin::STATE_LAST_LAUNCH])
                : 0;
            $timeB = isset($attemptB[XApiPlugin::STATE_LAST_LAUNCH])
                ? strtotime((string) $attemptB[XApiPlugin::STATE_LAST_LAUNCH])
                : 0;

            return $timeB <=> $timeA;
        }
    );

    $plugin->storeActivityStateDocument(
        (string) $toolLaunch->getActivityId(),
        $actor,
        $stateId,
        $stateDocument,
        null,
        $toolLaunch->getLrsUrl(),
        $toolLaunch->getLrsAuthUsername(),
        $toolLaunch->getLrsAuthPassword()
    );
} catch (Exception $exception) {
    Display::addFlash(
        Display::return_message($exception->getMessage(), 'error')
    );

    header('Location: '.api_get_course_url());
    exit;
}

$activityLaunchUrl = $plugin->generateLaunchUrl(
    'tincan',
    (string) $toolLaunch->getLaunchUrl(),
    (string) $toolLaunch->getActivityId(),
    $actor,
    $attemptId,
    $toolLaunch->getLrsUrl(),
    $toolLaunch->getLrsAuthUsername(),
    $toolLaunch->getLrsAuthPassword()
);

header("Location: $activityLaunchUrl");
exit;
