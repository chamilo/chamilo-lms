<?php
/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\CourseHomeNotify\Entity\Notification;
use Chamilo\PluginBundle\CourseHomeNotify\Entity\NotificationRelUser;

require_once __DIR__.'/../../main/inc/global.inc.php';

api_block_anonymous_users(true);
api_protect_course_script(true);

$plugin = CourseHomeNotifyPlugin::create();
$userId = api_get_user_id();
$courseId = api_get_course_int_id();

if (
    empty($courseId) ||
    empty($userId) ||
    !$plugin->isEnabled()
) {
    api_not_allowed(true);
}

$user = api_get_user_entity($userId);
$course = api_get_course_entity($courseId);
$hash = isset($_GET['hash']) ? Security::remove_XSS($_GET['hash']) : null;

$em = Database::getManager();
/** @var Notification $notification */
$notification = $em
    ->getRepository(Notification::class)
    ->findOneBy(['course' => $course, 'hash' => $hash]);

if (!$notification || empty($notification->getExpirationLink())) {
    api_not_allowed(true);
}

$notificationUser = $em
    ->getRepository(NotificationRelUser::class)
    ->findOneBy(['notification' => $notification, 'user' => $user]);

if (!$notificationUser) {
    $notificationUser = new NotificationRelUser();
    $notificationUser
        ->setUser($user)
        ->setNotification($notification);

    $em->persist($notificationUser);
    $em->flush();
}

header('Location: '.$notification->getExpirationLink());
exit;
