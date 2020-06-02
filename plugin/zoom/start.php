<?php
/* For license terms, see /license.txt */

$course_plugin = 'zoom'; // needed in order to load the plugin lang variables

require_once __DIR__.'/config.php';

$logInfo = [
    'tool' => 'Videoconference Zoom',
];

Event::registerLog($logInfo);

$tool_name = get_lang('ZoomVideoconference');
$tpl = new Template($tool_name);

api_protect_course_script(true);

$plugin = ZoomPlugin::create();


if ($plugin->userIsConferenceManager()) {
    // user can create a new meeting

    // one form to fast and easily create and start an instant meeting
    $createInstantMeetingForm = new FormValidator('createInstantMeetingForm');
    $createInstantMeetingForm->addButton('startButton', get_lang('StartInstantMeeting'));
    $tpl->assign('createInstantMeetingForm', $createInstantMeetingForm->returnForm());

    // instant meeting creation
    if ($createInstantMeetingForm->validate()) {
        try {
            $newInstantMeeting = $plugin->createInstantMeeting();
            header('Location: '.$newInstantMeeting->start_url);
            exit;
        } catch (Exception $exception) {
            Display::addFlash(
                Display::return_message($exception->getMessage(), 'error')
            );
        }
    }

    // another form to schedule a meeting
    $scheduleMeetingForm = new FormValidator('scheduleMeetingForm');
    $startTimeDatePicker = $scheduleMeetingForm->addDateTimePicker('start_time', get_lang('StartTime'));
    $scheduleMeetingForm->setRequired($startTimeDatePicker);
    $durationNumeric = $scheduleMeetingForm->addNumeric('duration', get_lang('Duration'));
    $topicText = $scheduleMeetingForm->addText('topic', get_lang('Topic'), true);
    $agendaTextArea = $scheduleMeetingForm->addTextarea('agenda', get_lang('Agenda'), [ 'maxlength' => 2000 ]);
    // $passwordText = $scheduleMeetingForm->addText('password', get_lang('Password'), false, [ 'maxlength' => '10' ]);
    $scheduleMeetingForm->addButtonCreate(get_lang('ScheduleMeeting'));

    // meeting scheduling
    if ($scheduleMeetingForm->validate()) {
        try {
            $newMeeting = $plugin->createScheduledMeeting(
                new DateTime($startTimeDatePicker->getValue()),
                $durationNumeric->getValue(),
                $topicText->getValue(),
                $agendaTextArea->getValue(),
                '' // $passwordText->getValue()
            );
            Display::addFlash(
                Display::return_message($plugin->get_lang('NewMeetingCreated'))
            );
            $tpl->assign('newMeeting', $newMeeting);
        } catch (Exception $exception) {
            Display::addFlash(
                Display::return_message($exception->getMessage(), 'error')
            );
        }
    } else {
        $scheduleMeetingForm->setDefaults(
            [
                'duration' => 60,
                'topic' => api_get_course_info()['title'],
            ]
        );
    }
    $tpl->assign('scheduleMeetingForm', $scheduleMeetingForm->returnForm());
}

try {
    $tpl->assign('liveMeetings', $plugin->getLiveMeetings());
} catch (Exception $exception) {
    Display::addFlash(
        Display::return_message('Could not retrieve live meeting list: '.$exception->getMessage(), 'error')
    );
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
