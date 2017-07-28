<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';

$urlList = UrlManager::get_url_data();
foreach ($urlList as $url) {
    echo "Portal: # ".$url['id']." - ".$url['url'].PHP_EOL;
    // Set access_url to set correct paths.
    $configuration['access_url'] = $url['id'];
    $object = new ScheduledAnnouncement();
    $messagesSent = $object->sendPendingMessages($url['id']);
    echo "Messages sent $messagesSent".PHP_EOL;
}
