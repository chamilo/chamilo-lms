<?php
/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\CourseHomeNotify\Entity\Notification;
use Chamilo\PluginBundle\CourseHomeNotify\Entity\NotificationRelUser;

require_once __DIR__.'/../../main/inc/global.inc.php';

api_block_anonymous_users(true);
api_protect_course_script(true);

header('Content-Type: application/json');

$plugin = CourseHomeNotifyPlugin::create();
$courseId = api_get_course_int_id();
$userId = api_get_user_id();

if (
    empty($courseId) ||
    empty($userId) ||
    !$plugin->isEnabled()
) {
    echo json_encode(['show' => false]);
    exit;
}

$course = api_get_course_entity($courseId);
$user = api_get_user_entity($userId);
$em = Database::getManager();
$schemaManager = $em->getConnection()->createSchemaManager();

if (
    !$schemaManager->tablesExist([
        'course_home_notify_notification',
        'course_home_notify_notification_rel_user',
    ])
) {
    echo json_encode(['show' => false]);
    exit;
}

/** @var Notification|null $notification */
$notification = $em
    ->getRepository(Notification::class)
    ->findOneBy(['course' => $course]);

if (!$notification) {
    echo json_encode(['show' => false]);
    exit;
}

$expirationLink = $notification->getExpirationLink();

if ($expirationLink) {
    /** @var NotificationRelUser|null $notificationUser */
    $notificationUser = $em
        ->getRepository(NotificationRelUser::class)
        ->findOneBy(['notification' => $notification, 'user' => $user]);

    if ($notificationUser) {
        echo json_encode(['show' => false]);
        exit;
    }
}

$contentUrl = '';

if ($expirationLink) {
    $contentUrl = api_get_path(WEB_PLUGIN_PATH)
        .$plugin->get_name()
        .'/content.php?hash='
        .urlencode($notification->getHash())
        .'&'
        .api_get_cidreq();
}

echo json_encode(
    [
        'show' => true,
        'title' => $plugin->get_lang('CourseNotice'),
        'content' => $notification->getContent(),
        'requiresLink' => !empty($expirationLink),
        'linkLabel' => $plugin->get_lang('PleaseFollowThisLink'),
        'contentUrl' => $contentUrl,
    ]
);
