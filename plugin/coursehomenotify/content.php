<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../../main/inc/global.inc.php';

use Chamilo\PluginBundle\Entity\CourseHomeNotify\Notification;
use Chamilo\PluginBundle\Entity\CourseHomeNotify\NotificationRelUser;

api_block_anonymous_users(true);
api_protect_course_script(true);

$plugin = CourseHomeNotifyPlugin::create();
$userId = api_get_user_id();
$courseId = api_get_course_int_id();

if (
    empty($courseId) ||
    empty($userId) ||
    'true' !== $plugin->get(CourseHomeNotifyPlugin::SETTING_ENABLED)
) {
    api_not_allowed(true);
}

$user = api_get_user_entity($userId);
$course = api_get_course_entity($courseId);
$hash = isset($_GET['hash']) ? Security::remove_XSS($_GET['hash']) : null;

$em = Database::getManager();
/** @var Notification $notification */
$notification = $em
    ->getRepository('ChamiloPluginBundle:CourseHomeNotify\Notification')
    ->findOneBy(['course' => $course, 'hash' => $hash]);

if (!$notification) {
    api_not_allowed(true);
}

$notificationUser = $em
    ->getRepository('ChamiloPluginBundle:CourseHomeNotify\NotificationRelUser')
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
