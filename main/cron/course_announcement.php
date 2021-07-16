<?php
/* For licensing terms, see /license.txt */

require __DIR__ . '/../inc/global.inc.php';

if (!api_get_configuration_value('course_announcement_scheduled_by_date')) {
    exit;
}

$now = api_get_utc_datetime();

$dql = "SELECT a
            FROM ChamiloCourseBundle:CAnnouncement a
            JOIN ChamiloCourseBundle:CItemProperty ip
            WITH a.id = ip.ref AND a.cId = ip.course
            WHERE
                (a.emailSent != 1 OR
                a.emailSent IS NULL) AND
                ip.tool = 'announcement' AND
                ip.visibility = 1
            ORDER BY a.displayOrder DESC";

$qb = Database::getManager()->createQuery($dql);
$result = $qb->execute();

if (!$result) {
    exit;
}

$extraFieldValue = new ExtraFieldValue('course_announcement');

foreach ($result as $announcement) {

    $send_notification = $extraFieldValue->get_values_by_handler_and_field_variable($announcement->getId(), 'send_notification_at_a_specific_date');

    if ($send_notification['value'] == 1) {

        $date_to_send = $extraFieldValue->get_values_by_handler_and_field_variable($announcement->getId(), 'date_to_send_notification');
        $today = date('Y-m-d');

        if ($today >= $date_to_send['value']) {
            $course_info = api_get_course_info_by_id($announcement->getCId());
            $email = new AnnouncementEmail($course_info, 0, $announcement->getId());
            $send_to = $email->send();
        }
    }
}
