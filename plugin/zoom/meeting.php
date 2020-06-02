<?php
/* For license terms, see /license.txt */

use Chamilo\PluginBundle\Zoom\Meeting;

$course_plugin = 'zoom'; // needed in order to load the plugin lang variables

require_once __DIR__.'/config.php';

$logInfo = [
    'tool' => 'Videoconference Zoom',
];

Event::registerLog($logInfo);

$tool_name = get_lang('ZoomVideoconference');
$tpl = new Template($tool_name);

$plugin = ZoomPlugin::create();

if (!array_key_exists('meetingId', $_REQUEST)) {
    header('Location: start.php');
    exit;
}
$meeting = $plugin->getMeeting($_REQUEST['meetingId']);

if ($plugin->userIsConferenceManager()) {
    // user can edit, start and delete meeting
    $tpl->assign('isConferenceManager', true);

    $editMeetingForm = new FormValidator('editMeetingForm');
    $editMeetingForm->addHidden('meetingId', $meeting->id);

    if (Meeting::TYPE_SCHEDULED === $meeting->type
        ||
        Meeting::TYPE_RECURRING_WITH_FIXED_TIME === $meeting->type
    ) {
        $startTimeDatePicker = $editMeetingForm->addDateTimePicker('start_time', get_lang('StartTime'));
        $durationNumeric = $editMeetingForm->addNumeric('duration', get_lang('Duration'));
    }
    $topicText = $editMeetingForm->addText('topic', get_lang('Topic'));
    $agendaTextArea = $editMeetingForm->addTextarea('agenda', get_lang('Agenda'), [ 'maxlength' => 2000 ]);
    // $passwordText = $editMeetingForm->addText('password', get_lang('Password'), false, [ 'maxlength' => '10' ]);
    $editMeetingForm->addButtonUpdate(get_lang('UpdateMeeting'));
    if ($editMeetingForm->validate()) {
        $meeting->start_time = $editMeetingForm->getSubmitValue('start_time');
        $meeting->duration = $editMeetingForm->getSubmitValue('duration');
        $meeting->topic = $editMeetingForm->getSubmitValue('topic');
        $meeting->agenda = $editMeetingForm->getSubmitValue('agenda');
        try {
            $plugin->updateMeeting($meeting->id, $meeting);
            $meeting = $plugin->getMeeting($meeting->id);
        } catch (Exception $exception) {
            Display::addFlash(
                Display::return_message($exception->getMessage(), 'error')
            );
        }
    }
    try {
        $editMeetingForm->setDefaults(
            [
                'start_time' => $meeting->start_time,
                'duration' => $meeting->duration,
                'topic' => $meeting->topic,
                'agenda' => $meeting->extra_data['stripped_agenda'],
            ]
        );
    } catch (Exception $exception) {
        Display::addFlash(
            Display::return_message($exception->getMessage(), 'error')
        );
    }
    $tpl->assign('editMeetingForm', $editMeetingForm->returnForm());

    $deleteMeetingForm = new FormValidator('deleteMeetingForm');
    $deleteMeetingForm->addHidden('meetingId', $meeting->id);
    $deleteMeetingForm->addButtonDelete(get_lang('DeleteMeeting'));
    $tpl->assign('deleteMeetingForm', $deleteMeetingForm->returnForm());

    if ($deleteMeetingForm->validate()) {
        try {
            $newInstantMeeting = $plugin->deleteMeeting($meeting->id);
            header('Location: start.php');
            exit;
        } catch (Exception $exception) {
            Display::addFlash(
                Display::return_message($exception->getMessage(), 'error')
            );
        }
    }
}
$tpl->assign('meeting', $meeting);
try {
    $tpl->assign('recordings', $plugin->getRecordings($meeting->uuid));
} catch (Exception $exception) {
    if ($exception->getCode() === 404) { // No recording for this meeting
        // fine, ignoring
    } else {
        Display::addFlash(
            Display::return_message($exception->getMessage(), 'error')
        );
    }
}
try {
    $tpl->assign('participants', $plugin->getParticipants($meeting->uuid));
} catch (Exception $exception) {
    if ($exception->getCode() === 400) { // Only available pour paid account
        // pass
    } else {
        Display::addFlash(
            Display::return_message($exception->getMessage(), 'error')
        );
    }
}
$tpl->assign('content', $tpl->fetch('zoom/view/meeting.tpl'));
$tpl->display_one_col_template();
