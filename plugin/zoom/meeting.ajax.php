<?php

/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\Zoom\Meeting;
use Symfony\Component\HttpFoundation\Request as HttpRequest;

require_once __DIR__.'/config.php';

api_block_anonymous_users(false);

$httpRequest = HttpRequest::createFromGlobals();

$meetingId = $httpRequest->get('meetingId', 0);

if (empty($meetingId)) {
    api_not_allowed();
}

$plugin = ZoomPlugin::create();
/** @var Meeting $meeting */
$meeting = $plugin->getMeetingRepository()->findOneBy(['meetingId' => $meetingId]);
$currentUserId = api_get_user_id();
$currentUser = api_get_user_entity($currentUserId);

if (null === $meeting) {
    api_not_allowed(false, $plugin->get_lang('MeetingNotFound'));
}

switch ($httpRequest->get('a')) {
    case 'sign_attempt':
        $registrant = $meeting->getRegistrantByUser($currentUser);

        if (!$meeting->isSignAttendance() ||
            null === $registrant
        ) {
            api_not_allowed();
        }

        $file = $httpRequest->request->get('file', '');

        $secToken = Security::get_token('zoom_signature');

        if (!Security::check_token($secToken, null, 'zoom_signature')) {
            api_not_allowed();
        }

        echo (int) $plugin->saveSignature($registrant, $file);
        exit;
}
