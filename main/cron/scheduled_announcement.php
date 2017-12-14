<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';

$urlList = UrlManager::get_url_data();
foreach ($urlList as $url) {
    // Set access_url to set correct paths.
    $_configuration['access_url'] = $url['id'];
    echo "Portal: # ".$url['id']." - ".$url['url'].'-'.api_get_path(WEB_CODE_PATH).PHP_EOL;
    $object = new ScheduledAnnouncement();
    $messagesSent = $object->sendPendingMessages($url['id']);
    echo "Messages sent $messagesSent".PHP_EOL;
}
