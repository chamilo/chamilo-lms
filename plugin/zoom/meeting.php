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
    $tpl->assign('editMeetingForm', $plugin->getEditMeetingForm($meeting)->returnForm());
    $tpl->assign('deleteMeetingForm', $plugin->getDeleteMeetingForm($meeting, $returnURL)->returnForm());
    if ($plugin->get('enableParticipantRegistration')
        && MeetingSettings::APPROVAL_TYPE_NO_REGISTRATION_REQUIRED != $meeting->settings->approval_type) {
        list($registerParticipantForm, $registrants) = $plugin->getRegisterParticipantForm($meeting);
        $tpl->assign('registerParticipantForm', $registerParticipantForm->returnForm());
        $tpl->assign('registrants', $registrants); // FIXME cache
    }
    if ($plugin->get('enableCloudRecording')
        && 'cloud' === $meeting->settings->auto_recording
        // && 'finished' === $meeting->status
    ) {
        list($fileForm, $recordings) = $plugin->getFileForm($meeting);
        $tpl->assign('fileForm', $fileForm->returnForm());
        $tpl->assign('recordings', $recordings);
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
