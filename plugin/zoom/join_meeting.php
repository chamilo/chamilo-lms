<?php

/* For license terms, see /license.txt */

use Chamilo\PluginBundle\Zoom\MeetingEntity;

require_once __DIR__.'/config.php';

api_block_anonymous_users();

$course_plugin = 'zoom'; // needed in order to load the plugin lang variables
$meetingId = isset($_REQUEST['meetingId']) ? (int) $_REQUEST['meetingId'] : 0;
if (empty($meetingId)) {
    api_not_allowed(true);
}

$plugin = ZoomPlugin::create();

Display::display_header($plugin->get_title());
echo $plugin->getToolbar();
/** @var MeetingEntity $meeting */
$meeting = $plugin->getMeetingRepository()->findOneBy(['meetingId' => $meetingId]);
try {
    if (null === $meeting) {
        throw new Exception($plugin->get_lang('Meeting not found'));
    }

    $startJoinURL = $plugin->getStartOrJoinMeetingURL($meeting);
    echo $meeting->getIntroduction();

    if (!empty($startJoinURL)) {
        echo Display::url($plugin->get_lang('EnterMeeting'), $startJoinURL, ['class' => 'btn btn-primary']);
    } else {
        //echo Display::return_message($plugin->get_lang('ConferenceNotStarted'), 'warning');
    }

    if ($plugin->userIsConferenceManager($meeting)) {
        echo '&nbsp;'.Display::url(
            get_lang('Details'),
            api_get_path(WEB_PLUGIN_PATH).'zoom/meeting.php?type=admin&meetingId='.$meeting->getMeetingId(),
            ['class' => 'btn btn-default']
        );
    }
} catch (Exception $exception) {
    Display::addFlash(
        Display::return_message($exception->getMessage(), 'error')
    );
}

Display::display_footer();
