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
    'name' => get_lang('ZoomVideoConferences'),
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
            $plugin->updateMeeting($meeting);
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
            $plugin->deleteMeeting($meeting);
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
                $plugin->updateRegistrantList($meeting, $selectedUsers);
                Display::addFlash(
                    Display::return_message(get_lang('RegisteredUserListWasUpdated'), 'confirm')
                );
            } catch (Exception $exception) {
                Display::addFlash(
                    Display::return_message($exception->getMessage(), 'error')
                );
            }
        }

        $registrants = $plugin->getRegistrants($meeting);
        $tpl->assign('registrants', $registrants);

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
        $recordings = $plugin->getMeetingRecordings($meeting);
        $fileForm = new FormValidator('fileForm', 'post', $_SERVER['REQUEST_URI']);
        $fileIdSelect = $fileForm->addSelect('fileIds', get_lang('Files'));
        $fileIdSelect->setMultiple(true);
        foreach ($recordings as &$recording) {
            // $recording->instanceDetails = $plugin->getPastMeetingInstanceDetails($instance->uuid);
            $options = [];
            foreach ($recording->recording_files as $file) {
                $options[] = [
                    'text' => sprintf("%s.%s (%s)", $file->recording_type, $file->file_type, $file->formattedFileSize),
                    'value' => $file->id,
                ];
            }
            $fileIdSelect->addOptGroup(
                $options,
                sprintf("%s (%s)", $recording->formattedStartTime, $recording->formattedDuration)
            );
        }
        $actionRadio = $fileForm->addRadio(
            'action',
            get_lang('Action'),
            [
                'CreateLinkInCourse' => get_lang('CreateLinkInCourse'),
                'CopyToCourse' => get_lang('CopyToCourse'),
                'DeleteFile' => get_lang('DeleteFile'),
            ]
        );
        $fileForm->addButtonUpdate(get_lang('DoIt'));
        if ($fileForm->validate()) {
            foreach ($recordings as $recording) {
                foreach ($recording->recording_files as $file) {
                    if (in_array($file->id, $fileIdSelect->getValue())) {
                        $name = sprintf(
                            get_lang('XRecordingOfMeetingXFromXDurationXDotX'),
                            $file->recording_type,
                            $meeting->id,
                            $recording->formattedStartTime,
                            $recording->formattedDuration,
                            $file->file_type
                        );
                        if ('CreateLinkInCourse' === $actionRadio->getValue()) {
                            try {
                                $plugin->createLinkToFileInCourse($meeting, $file, $name);
                                Display::addFlash(
                                    Display::return_message(get_lang('LinkToFileWasCreatedInCourse'), 'success')
                                );
                            } catch (Exception $exception) {
                                Display::addFlash(
                                    Display::return_message($exception->getMessage(), 'error')
                                );
                            }
                        } elseif ('CopyToCourse' === $actionRadio->getValue()) {
                            try {
                                $plugin->copyFileToCourse($meeting, $file, $name);
                                Display::addFlash(
                                    Display::return_message(get_lang('FileWasCopiedToCourse'), 'confirm')
                                );
                            } catch (Exception $exception) {
                                Display::addFlash(
                                    Display::return_message($exception->getMessage(), 'error')
                                );
                            }
                        } elseif ('DeleteFile' === $actionRadio->getValue()) {
                            try {
                                $plugin->deleteFile($file);
                                Display::addFlash(
                                    Display::return_message(get_lang('FileWasDeleted'), 'confirm')
                                );
                            } catch (Exception $exception) {
                                Display::addFlash(
                                    Display::return_message($exception->getMessage(), 'error')
                                );
                            }
                        }
                    }
                }
            }
        }
        $tpl->assign('recordings', $recordings);
        $tpl->assign('fileForm', $fileForm->returnForm());
    }
} elseif (MeetingSettings::APPROVAL_TYPE_NO_REGISTRATION_REQUIRED != $meeting->settings->approval_type) {
    $userId = api_get_user_id();
    try {
        foreach ($plugin->getRegistrants($meeting) as $registrant) {
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
