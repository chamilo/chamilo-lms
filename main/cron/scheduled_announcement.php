<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';

$object = new ScheduledAnnouncement();
$messagesSent = $object->sendPendingMessages();

echo "Messages sent $messagesSent";
