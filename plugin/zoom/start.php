<?php
/* For license terms, see /license.txt */

use Doctrine\Common\Collections\Criteria;

$course_plugin = 'zoom'; // needed in order to load the plugin lang variables

require_once __DIR__.'/config.php';

api_protect_course_script(true);

// the section (for the tabs)
$this_section = SECTION_COURSES;

$logInfo = [
    'tool' => 'Videoconference Zoom',
];

Event::registerLog($logInfo);

$tool_name = get_lang('ZoomVideoconferences');
$tpl = new Template($tool_name);

$plugin = ZoomPlugin::create();

if ($plugin->userIsConferenceManager()) {
    // user can create a new meeting
    $tpl->assign('createInstantMeetingForm', $plugin->getCreateInstantMeetingForm()->returnForm());
    $tpl->assign('scheduleMeetingForm', $plugin->getScheduleMeetingForm()->returnForm());
}

try {
    $tpl->assign('scheduledMeetings', $plugin->getScheduledMeetings());
} catch (Exception $exception) {
    Display::addFlash(
        Display::return_message('Could not retrieve scheduled meeting list: '.$exception->getMessage(), 'error')
    );
}

$tpl->assign('content', $tpl->fetch('zoom/view/start.tpl'));
$tpl->display_one_col_template();
