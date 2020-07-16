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

if (array_key_exists('meetingId', $_REQUEST)) {
    /** @var MeetingEntity $meeting */
    $meeting = $plugin->getMeetingRepository()->find($_REQUEST['meetingId']);
    try {
        if (is_null($meeting)) {
            throw new Exception('Meeting not found');
        }
        // TODO introduce the meeting
        printf(
            '<div class="embed-responsive embed-responsive-16by9">
 <a class="embed-responsive-item" href="%s" target="_blank">%s</a>
</div>',
            $plugin->getUserMeetingURL($meeting),
            get_lang('JoinMeetingAsMyself')
        );

    } catch (Exception $exception) {
        Display::addFlash(
            Display::return_message($exception->getMessage(), 'error')
        );
    }
}

Display::display_footer();
