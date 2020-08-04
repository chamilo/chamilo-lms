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
$tool_name = $plugin->get_lang('ZoomVideoConferences');
$tpl = new Template($tool_name);
$course = api_get_course_entity();
$session = api_get_session_entity();
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

if ($plugin->userIsCourseConferenceManager($course)) {
    switch ($action) {
        case 'delete':
            $meeting = $plugin->getMeetingRepository()->findOneBy(['meetingId' => $_REQUEST['meetingId']]);
            $plugin->deleteMeeting($meeting, api_get_self().'?'.api_get_cidreq());

            break;
    }

    $user = api_get_user_entity(api_get_user_id());
    // user can create a new meeting
    $tpl->assign(
        'createInstantMeetingForm',
        $plugin->getCreateInstantMeetingForm(
            $user,
            $course,
            $session
        )->returnForm()
    );
    $tpl->assign('scheduleMeetingForm', $plugin->getScheduleMeetingForm(
        $user,
        $course,
        $session
    )->returnForm());
}

try {
    $tpl->assign(
        'scheduledMeetings',
        $plugin->getMeetingRepository()->courseMeetings($course, $session)
    );
} catch (Exception $exception) {
    Display::addFlash(
        Display::return_message('Could not retrieve scheduled meeting list: '.$exception->getMessage(), 'error')
    );
}

$tpl->assign('content', $tpl->fetch('zoom/view/start.tpl'));
$tpl->display_one_col_template();
