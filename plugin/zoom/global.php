<?php

/* For license terms, see /license.txt */

$course_plugin = 'zoom'; // needed in order to load the plugin lang variables

require_once __DIR__.'/config.php';

if (!ZoomPlugin::currentUserCanJoinGlobalMeeting()) {
    api_not_allowed(true);
}

api_location('join_meeting.php?meetingId='.ZoomPlugin::create()->getGlobalMeeting()->getMeetingId());
