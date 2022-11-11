<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CourseBundle\Entity\CItemProperty;

require __DIR__.'/../inc/global.inc.php';

if (php_sapi_name() != 'cli') {
    exit; //do not run from browser
}

if (!api_get_configuration_value('course_announcement_scheduled_by_date')) {
    exit;
}

// Get all pending (visible) announcements where e-mail sent is empty
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

// For each announcement, check rules about sending the notification at a
// specific date
foreach ($result as $announcement) {
    $sendNotification = $extraFieldValue->get_values_by_handler_and_field_variable($announcement->getId(), 'send_notification_at_a_specific_date');

    if ($sendNotification['value'] == 1) {
        $dateToSend = $extraFieldValue->get_values_by_handler_and_field_variable($announcement->getId(), 'date_to_send_notification');

        if ($today >= $dateToSend['value']) {
            $query = "SELECT ip FROM ChamiloCourseBundle:CItemProperty ip
                        WHERE ip.ref = :announcementId
                        AND ip.course = :courseId
                        AND ip.tool = '".TOOL_ANNOUNCEMENT."'
                        ORDER BY ip.iid DESC";

            $sql = Database::getManager()->createQuery($query);
            $itemProperty = $sql->execute(['announcementId' => $announcement->getId(), 'courseId' => $announcement->getCId()]);
            if (empty($itemProperty) or !isset($itemProperty[0])) {
                continue;
            }
            /* @var CItemProperty $itemPropertyObject */
            $itemPropertyObject = $itemProperty[0];
            // Check if the last record for this announcement was not a removal
            if ($itemPropertyObject->getLastEditType() == 'AnnouncementDeleted' or $itemPropertyObject->getVisibility() == 2) {
                continue;
            }
            /* @var Session $sessionObject */
            $sessionObject = $itemPropertyObject->getSession();
            if (!empty($sessionObject)) {
                $sessionId = $sessionObject->getId();
            } else {
                $sessionId = null;
            }
            $courseInfo = api_get_course_info_by_id($announcement->getCId());
            $senderId = $itemPropertyObject->getInsertUser()->getId();
            // Check if we need to send it to all users of all sessions that
            // include this course.
            $sendToUsersInSession = (int) $extraFieldValue->get_values_by_handler_and_field_variable(
                $announcement->getId(),
                'send_to_users_in_session'
            )['value'];

            $messageSentTo = AnnouncementManager::sendEmail(
                $courseInfo,
                $sessionId,
                $announcement->getId(),
                $sendToUsersInSession,
                false,
                null,
                $senderId,
                false,
                true
            );
        }
    }
}
