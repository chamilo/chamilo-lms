<?php
/* For license terms, see /license.txt */

use Chamilo\PluginBundle\Zoom\MeetingEntity;

$course_plugin = 'zoom'; // needed in order to load the plugin lang variables

require_once __DIR__.'/config.php';

if (!api_user_is_login()) {
    api_not_allowed(true);
    exit(); // just in case
}

$plugin = ZoomPlugin::create();

Display::display_header($plugin->get_title());
echo $plugin->getToolbar();
if (array_key_exists('meetingId', $_REQUEST)) {
    /** @var MeetingEntity $meeting */
    $meeting = $plugin->getMeetingRepository()->find($_REQUEST['meetingId']);
    try {
        if (is_null($meeting)) {
            throw new Exception($plugin->get_lang('Meeting not found'));
        }

        $startJoinURL = $plugin->getStartOrJoinMeetingURL($meeting);
        echo $meeting->getIntroduction();
        if (!empty($startJoinURL)) {
            echo Display::url($plugin->get_lang('EnterMeeting'), $startJoinURL, ['class' => 'btn btn-primary']);
        } else {
            echo Display::return_message($plugin->get_lang('ConferenceNotStarted'), 'warning');
        }

        if ($plugin->userIsConferenceManager($meeting)) {
            echo '&nbsp;'.Display::url(
                get_lang('Details'),
                api_get_path(WEB_PLUGIN_PATH).'zoom/meeting_from_admin.php?meetingId='.$meeting->getId(),
                ['class' => 'btn btn-default']
            );
        }
    } catch (Exception $exception) {
        Display::addFlash(
            Display::return_message($exception->getMessage(), 'error')
        );
    }
}

Display::display_footer();
