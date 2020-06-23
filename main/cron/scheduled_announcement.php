<?php
/* For licensing terms, see /license.txt */

require __DIR__.'/../inc/global.inc.php';

$urlList = UrlManager::get_url_data();
foreach ($urlList as $url) {
    $urlId = $url['id'];
    $_configuration['access_url'] = $urlId;
    echo "Portal: # ".$urlId." - ".$url['url'].'-'.api_get_path(WEB_CODE_PATH).PHP_EOL;
    $object = new ScheduledAnnouncement();
    $messagesSent = $object->sendPendingMessages($urlId);
    echo "Messages sent $messagesSent".PHP_EOL;
}
