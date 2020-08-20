<?php

/* For license terms, see /license.txt */

use Chamilo\PluginBundle\Zoom\Meeting;

require_once __DIR__.'/config.php';

api_block_anonymous_users();

$course_plugin = 'zoom'; // needed in order to load the plugin lang variables
$meetingId = isset($_REQUEST['meetingId']) ? (int) $_REQUEST['meetingId'] : 0;
if (empty($meetingId)) {
    api_not_allowed(true);
}

$plugin = ZoomPlugin::create();
$content = '';
/** @var Meeting $meeting */
$meeting = $plugin->getMeetingRepository()->findOneBy(['meetingId' => $meetingId]);
if (null === $meeting) {
    api_not_allowed(true, $plugin->get_lang('MeetingNotFound'));
}

if ($meeting->isCourseMeeting()) {
    api_protect_course_script(true);
    if (api_is_in_group()) {
        $interbreadcrumb[] = [
            'url' => api_get_path(WEB_CODE_PATH).'group/group.php?'.api_get_cidreq(),
            'name' => get_lang('Groups'),
        ];
        $interbreadcrumb[] = [
            'url' => api_get_path(WEB_CODE_PATH).'group/group_space.php?'.api_get_cidreq(),
            'name' => get_lang('GroupSpace').' '.$meeting->getGroup()->getName(),
        ];
    }
}

try {
    $startJoinURL = $plugin->getStartOrJoinMeetingURL($meeting);
    $content .= $meeting->getIntroduction();

    if (!empty($startJoinURL)) {
        $content .= Display::url($plugin->get_lang('EnterMeeting'), $startJoinURL, ['class' => 'btn btn-primary']);
    } else {
        $content .= Display::return_message($plugin->get_lang('ConferenceNotAvailable'), 'warning');
    }

    if ($plugin->userIsConferenceManager($meeting)) {
        $content .= '&nbsp;'.Display::url(
            get_lang('Details'),
            api_get_path(WEB_PLUGIN_PATH).'zoom/meeting.php?meetingId='.$meeting->getMeetingId(),
            ['class' => 'btn btn-default']
        );
    }
} catch (Exception $exception) {
    Display::addFlash(
        Display::return_message($exception->getMessage(), 'warning')
    );
}

Display::display_header($plugin->get_title());
echo $plugin->getToolbar();
echo $content;
Display::display_footer();
