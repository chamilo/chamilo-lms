<?php
/* For license terms, see /license.txt */

$course_plugin = 'zoom'; // needed in order to load the plugin lang variables

require_once __DIR__.'/config.php';

api_protect_course_script(true);

// the section (for the tabs)
$this_section = SECTION_COURSES;

$logInfo = [
    'tool' => 'Videoconference Zoom',
];

Event::registerLog($logInfo);

$plugin = ZoomPlugin::create();
$tool_name = $plugin->get_lang('ZoomVideoconferences');
$tpl = new Template($tool_name);

if ($plugin->userIsCourseConferenceManager(api_get_course_entity())) {
    // user can create a new meeting
    $tpl->assign(
        'createInstantMeetingForm',
        $plugin->getCreateInstantMeetingForm(
            api_get_user_entity(api_get_user_id()),
            api_get_course_entity(),
            api_get_session_entity()
        )->returnForm()
    );
    $tpl->assign('scheduleMeetingForm', $plugin->getScheduleMeetingForm(
        api_get_user_entity(api_get_user_id()),
        api_get_course_entity(),
        api_get_session_entity()
    )->returnForm());
}

try {
    $tpl->assign(
        'scheduledMeetings',
        $plugin->getMeetingRepository()->courseMeetings(api_get_course_entity(), api_get_session_entity())
    );
} catch (Exception $exception) {
    Display::addFlash(
        Display::return_message('Could not retrieve scheduled meeting list: '.$exception->getMessage(), 'error')
    );
}

$tpl->assign('content', $tpl->fetch('zoom/view/start.tpl'));
$tpl->display_one_col_template();
