<?php
/* For licensing terms, see /license.txt */

require __DIR__.'/../inc/global.inc.php';

if (php_sapi_name() != 'cli') {
    exit; //do not run from browser
}

if (!api_get_configuration_value('course_announcement_scheduled_by_date')) {
    exit;
}

$dql = "SELECT a
            FROM ChamiloCourseBundle:CAnnouncement a
            JOIN ChamiloCourseBundle:CItemProperty ip
            WITH a.id = ip.ref AND a.cId = ip.course
            WHERE
                (a.emailSent != 1 OR
                a.emailSent IS NULL) AND
                ip.tool = '".TOOL_ANNOUNCEMENT."' AND
                ip.visibility = 1
            ORDER BY a.displayOrder DESC";

$qb = Database::getManager()->createQuery($dql);
$result = $qb->execute();

if (!$result) {
    exit;
}

$extraFieldValue = new ExtraFieldValue('course_announcement');
$today = date('Y-m-d');

foreach ($result as $announcement) {

    $sendNotification = $extraFieldValue->get_values_by_handler_and_field_variable($announcement->getId(), 'send_notification_at_a_specific_date');

    if ($sendNotification['value'] == 1) {

        $dateToSend = $extraFieldValue->get_values_by_handler_and_field_variable($announcement->getId(), 'date_to_send_notification');

        if ($today >= $dateToSend['value']) {

            $query = "SELECT ip FROM ChamiloCourseBundle:CItemProperty ip
                        WHERE ip.ref = :announcementId
                        AND ip.course = :courseId
                        AND ip.tool = '".TOOL_ANNOUNCEMENT."'
                        ORDER BY iid DESC";

            $sql = Database::getManager()->createQuery($query);
            $itemProperty = $sql->execute(['announcementId' => $announcement->getId(), 'courseId' => $announcement->getCId()]);
            if (empty($itemProperty) or !isset($itemProperty[0])) {
                continue;
            }
            // Check if the last record for this announcement was not a removal
            if ($itemProperty[0]['lastedit_type'] == 'AnnouncementDeleted' or $itemProperty[0]['visibility'] == 2) {
                continue;
            }
            /* @var \Chamilo\CoreBundle\Entity\Session $sessionObject */
            $sessionObject = $itemProperty[0]->getSession();
            $sessionId = $sessionObject->getId();
            $courseInfo = api_get_course_info_by_id($announcement->getCId());
            $senderId = $itemProperty[0]->getInsertUser()->getId();
            $sendToUsersInSession = (int) $extraFieldValue->get_values_by_handler_and_field_variable($announcement->getId(), 'send_to_users_in_session')['value'];

            $messageSentTo = AnnouncementManager::sendEmail(
                $courseInfo,
                $sessionId,
                $announcement->getId(),
                $sendToUsersInSession,
                false,
                null,
                $senderId
            );
        }
    }
}
