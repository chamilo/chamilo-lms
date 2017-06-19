<?php

require_once __DIR__.'/../inc/global.inc.php';

$object = new ProgrammedAnnouncement();
$messagesSent = $object->sendPendingMessages();

echo "Messages sent $messagesSent";
