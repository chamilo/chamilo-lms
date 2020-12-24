<?php

/* For licensing terms, see /license.txt */

exit;

require_once __DIR__.'/../../main/inc/global.inc.php';

$testUserId = 1;

$em = Database::getManager();
/** @var \Chamilo\CoreBundle\Entity\Repository\ItemPropertyRepository $itemRepo */
$itemRepo = $em->getRepository('ChamiloCourseBundle:CItemProperty');

$usersTable = Database::get_main_table(TABLE_MAIN_USER);
$sql = "SELECT id FROM $usersTable ";
$result = Database::query($sql);
$log = '';
while ($row = Database::fetch_array($result)) {
    $userId = $row['id'];

    if (!empty($testUserId) && $testUserId != $row['id']) {
        continue;
    }

    echo PHP_EOL."Migrating user #$userId".PHP_EOL;

    $sql = "SELECT DISTINCT c_id, lp_id, session_id
            FROM c_lp_view
            WHERE user_id = $userId";

    $lpResult = Database::query($sql);
    $currentUser = api_get_user_entity($userId);

    while ($lpView = Database::fetch_array($lpResult)) {
        $lpId = $lpView['lp_id'];

        if (empty($lpView['c_id'])) {
            echo 'Course is empty '.PHP_EOL;
            continue;
        }

        $course = api_get_course_entity($lpView['c_id']);

        if (null === $course) {
            echo 'Course not found: #'.$lpView['c_id'].' '.PHP_EOL;
            continue;
        }

        $session = api_get_session_entity($lpView['session_id']);
        $itemRepo->subscribeUsersToItem(
            $currentUser,
            'learnpath',
            $course,
            $session,
            $lpId,
            [$userId],
            false
        );
        echo "Subscribing to LP: $lpId - c_id: ".$lpView['c_id'].' session: #'.$lpView['session_id'].' '.PHP_EOL;
    }
}
