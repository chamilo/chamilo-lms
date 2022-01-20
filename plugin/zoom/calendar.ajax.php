<?php

/* For license terms, see /license.txt */

use Chamilo\PluginBundle\Zoom\Meeting;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request as HttpRequest;

$course_plugin = 'zoom'; // needed in order to load the plugin lang variables

$cidReset = true;

require_once __DIR__.'/config.php';

api_protect_admin_script();

$request = HttpRequest::createFromGlobals();

$plugin = ZoomPlugin::create();
$user = api_get_user_entity(api_get_user_id());

$action = $request->get('a');

if ($action == 'get_events') {
    $startDate = $request->query->get('start');
    $endDate = $request->query->get('end');

    $startDate = api_get_utc_datetime($startDate, true, true);
    $endDate = api_get_utc_datetime($endDate, true, true);

    $meetings = $plugin
        ->getMeetingRepository()
        ->periodMeetings($startDate, $endDate);

    $meetingsAsEvents = array_map(
        function (Meeting $meeting) {
            $meetingInfo = $meeting->getMeetingInfoGet();

            $endDate = new DateTime($meeting->formattedStartTime);
            $endDate->sub($meeting->durationInterval);

            return [
                'id' => 'meeting_'.$meeting->getId(),
                'title' => $meetingInfo->topic,
                'editable' => false,
                'start' => $meeting->formattedStartTime,
                'start_date_localtime' => $meeting->formattedStartTime,
                'end' => $endDate->format('Y-m-d H:i'),
                'end_date_localtime' => $endDate->format('Y-m-d H:i'),
                'duration' => $meeting->formattedDuration,
                'description' => $meetingInfo->agenda,
                'allDay' => false,
                'accountEmail' => $meeting->getAccountEmail(),
            ];
        },
        $meetings
    );

    $response = JsonResponse::create($meetingsAsEvents);
    $response->send();
}
