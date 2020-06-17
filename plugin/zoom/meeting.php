<?php
/* For license terms, see /license.txt */

use Chamilo\PluginBundle\Zoom\API\MeetingSettings;

if (!isset($returnURL)) {
    exit;
}

$course_plugin = 'zoom'; // needed in order to load the plugin lang variables

$logInfo = [
    'tool' => 'Videoconference Zoom',
];

Event::registerLog($logInfo);

$interbreadcrumb[] = [ // used in templates
    'url' => $returnURL,
    'name' => get_lang('ZoomVideoconferences'),
];

if (!array_key_exists('meetingId', $_REQUEST)) {
    throw new Exception('MeetingNotFound');
}
$plugin = ZoomPlugin::create();

$meeting = $plugin->getMeeting($_REQUEST['meetingId']);

$tpl = new Template($meeting->id);

if ($plugin->userIsConferenceManager()) {
    // user can edit, start and delete meeting
    $tpl->assign('isConferenceManager', true);

    $editMeetingForm = new FormValidator('editMeetingForm', 'post', $_SERVER['REQUEST_URI']);

    if ($meeting::TYPE_SCHEDULED === $meeting->type
        ||
        $meeting::TYPE_RECURRING_WITH_FIXED_TIME === $meeting->type
    ) {
        $startTimeDatePicker = $editMeetingForm->addDateTimePicker('start_time', get_lang('StartTime'));
        $editMeetingForm->setRequired($startTimeDatePicker);
        $durationNumeric = $editMeetingForm->addNumeric('duration', get_lang('DurationInMinutes'));
        $editMeetingForm->setRequired($durationNumeric);
    }
    $topicText = $editMeetingForm->addText('topic', get_lang('Topic'));
    $agendaTextArea = $editMeetingForm->addTextarea('agenda', get_lang('Agenda'), ['maxlength' => 2000]);
    // $passwordText = $editMeetingForm->addText('password', get_lang('Password'), false, ['maxlength' => '10']);
    $editMeetingForm->addButtonUpdate(get_lang('UpdateMeeting'));
    if ($editMeetingForm->validate()) {
        $meeting->start_time = $editMeetingForm->getSubmitValue('start_time');
        $meeting->timezone = date_default_timezone_get();
        $meeting->duration = $editMeetingForm->getSubmitValue('duration');
        $meeting->topic = $editMeetingForm->getSubmitValue('topic');
        $meeting->agenda = $editMeetingForm->getSubmitValue('agenda');
        try {
            $plugin->updateMeeting($meeting->id, $meeting);
            Display::addFlash(
                Display::return_message(get_lang('MeetingUpdated'), 'confirm')
            );
        } catch (Exception $exception) {
            Display::addFlash(
                Display::return_message($exception->getMessage(), 'error')
            );
        }
    }
    try {
        $editMeetingForm->setDefaults(
            [
                'start_time' => $meeting->startDateTime->format('c'),
                'duration' => $meeting->duration,
                'topic' => $meeting->topic,
                'agenda' => $meeting->agenda,
            ]
        );
    } catch (Exception $exception) {
        Display::addFlash(
            Display::return_message($exception->getMessage(), 'error')
        );
    }
    $tpl->assign('editMeetingForm', $editMeetingForm->returnForm());

    $deleteMeetingForm = new FormValidator('deleteMeetingForm', 'post', $_SERVER['REQUEST_URI']);
    $deleteMeetingForm->addButtonDelete(get_lang('DeleteMeeting'));
    $tpl->assign('deleteMeetingForm', $deleteMeetingForm->returnForm());

    if ($deleteMeetingForm->validate()) {
        try {
            $plugin->deleteMeeting($meeting->id);
            Display::addFlash(
                Display::return_message(get_lang('MeetingDeleted'), 'confirm')
            );
            location($returnURL);
        } catch (Exception $exception) {
            Display::addFlash(
                Display::return_message($exception->getMessage(), 'error')
            );
        }
    }

    if ($plugin->get('enableParticipantRegistration')
        && MeetingSettings::APPROVAL_TYPE_NO_REGISTRATION_REQUIRED != $meeting->settings->approval_type) {
        $tpl->assign('enableParticipantRegistration', true);

        $registerParticipantForm = new FormValidator('registerParticipantForm', 'post', $_SERVER['REQUEST_URI']);
        $userIdSelect = $registerParticipantForm->addSelect('userIds', get_lang('RegisteredUsers'));
        $userIdSelect->setMultiple(true);
        $registerParticipantForm->addButtonSend(get_lang('UpdateRegisteredUserList'));

        $users = $meeting->getCourseAndSessionUsers();
        foreach ($users as $user) {
            $userIdSelect->addOption(api_get_person_name($user->getFirstname(), $user->getLastname()), $user->getId());
        }

        if ($registerParticipantForm->validate()) {
            $selectedUserIds = $userIdSelect->getValue();
            $selectedUsers = [];
            foreach ($users as $user) {
                if (in_array($user->getId(), $selectedUserIds)) {
                    $selectedUsers[] = $user;
                }
            }
            try {
                $plugin->updateRegistrantList($meeting->id, $selectedUsers);
                Display::addFlash(
                    Display::return_message(get_lang('RegisteredUserListWasUpdated'), 'confirm')
                );
            } catch (Exception $exception) {
                Display::addFlash(
                    Display::return_message($exception->getMessage(), 'error')
                );
            }
        }

        try {
            $registrants = $plugin->getRegistrants($meeting->id);
            $tpl->assign('registrants', $registrants);
        } catch (Exception $exception) {
            Display::addFlash(
                Display::return_message($exception->getMessage(), 'error')
            );
            $registrants = [];
        }

        $registeredUserIds = [];
        foreach ($registrants as $registrant) {
            $registeredUserIds[] = $registrant->userId;
        }
        $userIdSelect->setSelected($registeredUserIds);
        $tpl->assign('registerParticipantForm', $registerParticipantForm->returnForm());
    }

    if ($plugin->get('enableCloudRecording')
        && 'cloud' === $meeting->settings->auto_recording
        // && 'finished' === $meeting->status
    ) {
        $instances = [];
        foreach ($plugin->getEndedMeetingInstances($meeting->id) as $instance) {
            // $instance->instanceDetails = $plugin->getPastMeetingInstanceDetails($instance->uuid);
            try {
                $instance->recordings = $plugin->getRecordings($instance->uuid);
            } catch (Exception $exception) {
                Display::addFlash(
                    Display::return_message($exception->getMessage(), 'error')
                );
            }
            foreach ($instance->recordings->recording_files as &$file) {
                $copyToCourseForm = new FormValidator(
                    'copyToCourseForm'.$file->id,
                    'post',
                    $_SERVER['REQUEST_URI']
                );
                $copyToCourseForm->addButtonCopy(get_lang('CopyRecordingToCourse'));
                if ($copyToCourseForm->validate()) {
                    try {
                        $plugin->copyRecordingToCourse($meeting, $file);
                        Display::addFlash(
                            Display::return_message(get_lang('RecordingWasCopiedToCourse'), 'confirm')
                        );
                    } catch (Exception $exception) {
                        Display::addFlash(
                            Display::return_message($exception->getMessage(), 'error')
                        );
                    }
                }
                $file->copyToCourseForm = $copyToCourseForm->returnForm();
            }

            $copyAllRecordingsToCourseForm = new FormValidator(
                'copyAllRecordingsToCourseForm'.$instance->uuid,
                'post',
                $_SERVER['REQUEST_URI']
            );
            $copyAllRecordingsToCourseForm->addButtonCopy(get_lang('CopyAllRecordingsToCourse'));
            if ($copyAllRecordingsToCourseForm->validate()) {
                try {
                    $plugin->copyAllRecordingsToCourse($instance->uuid);
                    Display::addFlash(
                        Display::return_message(get_lang('AllRecordingsWereCopiedToCourse'), 'confirm')
                    );
                } catch (Exception $exception) {
                    Display::addFlash(
                        Display::return_message($exception->getMessage(), 'error')
                    );
                }
            }
            $instance->copyAllRecordingsToCourseForm = $copyAllRecordingsToCourseForm->returnForm();

            $deleteRecordingsForm = new FormValidator(
                'deleteRecordingsForm'.$instance->uuid,
                'post',
                $_SERVER['REQUEST_URI']
            );
            $deleteRecordingsForm->addButtonSend(get_lang('DeleteRecordings'));
            if ($deleteRecordingsForm->validate()) {
                try {
                    $plugin->deleteRecordings($instance->uuid);
                    Display::addFlash(
                        Display::return_message(get_lang('RecordingsWereDeleted'), 'confirm')
                    );
                } catch (Exception $exception) {
                    Display::addFlash(
                        Display::return_message($exception->getMessage(), 'error')
                    );
                }
            }
            $instance->deleteRecordingsForm = $deleteRecordingsForm->returnForm();

            // $instance->participants = $plugin->getParticipants($instance->uuid);

            $instances[] = $instance;
        }
        $tpl->assign('instances', $instances);
    }
} elseif (MeetingSettings::APPROVAL_TYPE_NO_REGISTRATION_REQUIRED != $meeting->settings->approval_type) {
    $userId = api_get_user_id();
    try {
        foreach ($plugin->getRegistrants($meeting->id) as $registrant) {
            if ($registrant->userId == $userId) {
                $tpl->assign('currentUserJoinURL', $registrant->join_url);
                break;
            }
        }
    } catch (Exception $exception) {
        Display::addFlash(
            Display::return_message($exception->getMessage(), 'error')
        );
    }
}
$tpl->assign('meeting', $meeting);
$tpl->assign('content', $tpl->fetch('zoom/view/meeting.tpl'));
$tpl->display_one_col_template();
