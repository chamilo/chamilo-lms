<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\XApiToolLaunch;
use Chamilo\CoreBundle\Framework\Container;

require_once __DIR__.'/../../main/inc/global.inc.php';

/**
 * Check whether the launch belongs to the current course/session context.
 */
function xapi_tool_matches_current_context(XApiToolLaunch $toolLaunch): bool
{
    $currentCourse = api_get_course_entity();
    $currentSession = api_get_session_entity();

    if (null === $currentCourse || null === $toolLaunch->getCourse()) {
        return false;
    }

    if ($toolLaunch->getCourse()->getId() !== $currentCourse->getId()) {
        return false;
    }

    $toolSession = $toolLaunch->getSession();

    if (null === $currentSession && null === $toolSession) {
        return true;
    }

    if (null === $currentSession || null === $toolSession) {
        return false;
    }

    return $currentSession->getId() === $toolSession->getId();
}

api_protect_course_script(true);
api_protect_teacher_script();

$request = Container::getRequest();
$em = Database::getManager();
$connection = $em->getConnection();

$toolId = $request->query->getInt('delete');

/** @var XApiToolLaunch|null $toolLaunch */
$toolLaunch = $em->find(XApiToolLaunch::class, $toolId);

if (null === $toolLaunch || !xapi_tool_matches_current_context($toolLaunch)) {
    api_not_allowed(true);
}

$plugin = XApiPlugin::create();
$toolLaunchRepository = $em->getRepository(XApiToolLaunch::class);

if ($toolLaunchRepository->wasAddedInLp($toolLaunch)) {
    Display::addFlash(
        Display::return_message(
            'The xAPI activity cannot be deleted because it is still linked to a learning path.',
            'error'
        )
    );

    header('Location: start.php?'.api_get_cidreq());
    exit;
}

$toolTable = 'xapi_tool_launch';
$itemTable = 'xapi_cmi5_item';
$stateTable = Database::get_main_table('xapi_activity_state');

$toolActivityId = trim((string) $toolLaunch->getActivityId());
$toolActivityType = strtolower(trim((string) $toolLaunch->getActivityType()));
$tincanStateId = $plugin->getTinCanStateId((int) $toolLaunch->getId());

try {
    $connection->beginTransaction();

    // Break tree self-references first to avoid FK violations.
    $connection->executeStatement(
        "UPDATE $itemTable
         SET root_id = NULL, parent_id = NULL
         WHERE tool_id = :toolId",
        [
            'toolId' => $toolId,
        ]
    );

    // Delete all cmi5 tree items for this launch.
    $connection->executeStatement(
        "DELETE FROM $itemTable
         WHERE tool_id = :toolId",
        [
            'toolId' => $toolId,
        ]
    );

    // Remove lightweight state documents related to this launch.
    if ('' !== $toolActivityId) {
        $connection->executeStatement(
            "DELETE FROM $stateTable
             WHERE activity_id = :activityId
               AND state_id = :stateId",
            [
                'activityId' => $toolActivityId,
                'stateId' => $tincanStateId,
            ]
        );

        if ('cmi5' === $toolActivityType) {
            $connection->executeStatement(
                "DELETE FROM $stateTable
                 WHERE activity_id = :activityId
                   AND state_id = :stateId",
                [
                    'activityId' => $toolActivityId,
                    'stateId' => 'LMS.LaunchData',
                ]
            );
        }
    }

    // Clear the EntityManager so Doctrine does not keep stale child collections.
    $em->clear();

    /** @var XApiToolLaunch|null $managedToolLaunch */
    $managedToolLaunch = $em->find(XApiToolLaunch::class, $toolId);

    if (null === $managedToolLaunch) {
        throw new RuntimeException('The xAPI activity could not be reloaded for deletion.');
    }

    $em->remove($managedToolLaunch);
    $em->flush();

    $connection->commit();

    Display::addFlash(
        Display::return_message($plugin->get_lang('ActivityDeleted'), 'success')
    );
} catch (Throwable $exception) {
    if ($connection->isTransactionActive()) {
        $connection->rollBack();
    }

    Display::addFlash(
        Display::return_message($exception->getMessage(), 'error')
    );
}

header('Location: start.php?'.api_get_cidreq());
exit;
