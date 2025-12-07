<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;

require_once __DIR__.'/../inc/global.inc.php';

$urlList = Container::getAccessUrlRepository()->findAll();
foreach ($urlList as $url) {
    $_configuration['access_url'] = $url->getId();
    echo "Portal: # ".$url->getId()." - ".$url->getUrl().'-'.api_get_path(WEB_CODE_PATH).PHP_EOL;
    $object = new ScheduledAnnouncement();
    $messagesSent = $object->sendPendingMessages($url->getId());
    echo "Messages sent $messagesSent".PHP_EOL;
}
