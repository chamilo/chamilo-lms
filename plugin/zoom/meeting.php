<?php

/* For license terms, see /license.txt */

use Chamilo\PluginBundle\Zoom\Meeting;
use Chamilo\PluginBundle\Zoom\Webinar;

require_once __DIR__.'/config.php';

$meetingId = isset($_REQUEST['meetingId']) ? (int) $_REQUEST['meetingId'] : 0;
if (empty($meetingId)) {
    api_not_allowed(true);
}

$plugin = ZoomPlugin::create();
/** @var Meeting $meeting */
$meeting = $plugin->getMeetingRepository()->findOneBy(['meetingId' => $meetingId]);

if (null === $meeting) {
    api_not_allowed(true, $plugin->get_lang('MeetingNotFound'));
}

$course_plugin = 'zoom'; // needed in order to load the plugin lang variables
$returnURL = 'meetings.php';
$urlExtra = '';
if ($meeting->isCourseMeeting()) {
    api_protect_course_script(true);
    $this_section = SECTION_COURSES;
    $urlExtra = api_get_cidreq();
    $returnURL = 'start.php?'.$urlExtra;

    if (api_is_in_group()) {
        $interbreadcrumb[] = [
            'url' => api_get_path(WEB_CODE_PATH).'group/group.php?'.$urlExtra,
            'name' => get_lang('Groups'),
        ];
        $interbreadcrumb[] = [
            'url' => api_get_path(WEB_CODE_PATH).'group/group_space.php?'.$urlExtra,
            'name' => get_lang('GroupSpace').' '.$meeting->getGroup()->getName(),
        ];
    }
}

$logInfo = [
    'tool' => 'Videoconference Zoom',
];
Event::registerLog($logInfo);

$interbreadcrumb[] = [
    'url' => $returnURL,
    'name' => $plugin->get_lang('ZoomVideoConferences'),
];

$tpl = new Template($meeting->getMeetingId());

if ($plugin->userIsConferenceManager($meeting)) {
    // user can edit, start and delete meeting
    $tpl->assign('isConferenceManager', true);

    $tpl->assign('editMeetingForm', $plugin->getEditConferenceForm($meeting)->returnForm());

    if ($meeting instanceof Webinar) {
        $tpl->assign('deleteMeetingForm', $plugin->getDeleteWebinarForm($meeting, $returnURL)->returnForm());
    } elseif ($meeting instanceof Meeting) {
        $tpl->assign('deleteMeetingForm', $plugin->getDeleteMeetingForm($meeting, $returnURL)->returnForm());
    }

    if (false === $meeting->isGlobalMeeting() && false == $meeting->isCourseMeeting()) {
        if ('true' === $plugin->get('enableParticipantRegistration') && $meeting->requiresRegistration()) {
            $tpl->assign('registerParticipantForm', $plugin->getRegisterParticipantForm($meeting)->returnForm());
            $tpl->assign('registrants', $meeting->getRegistrants());
        }
    }

    if (ZoomPlugin::RECORDING_TYPE_NONE !== $plugin->getRecordingSetting() &&
        $meeting->hasCloudAutoRecordingEnabled()
    ) {
        $tpl->assign('fileForm', $plugin->getFileForm($meeting, $returnURL)->returnForm());
        $tpl->assign('recordings', $meeting->getRecordings());
    }
} elseif ($meeting->requiresRegistration()) {
    $userId = api_get_user_id();
    try {
        foreach ($meeting->getRegistrants() as $registrant) {
            if ($registrant->getUser()->getId() == $userId) {
                $tpl->assign('currentUserJoinURL', $registrant->getJoinUrl());
                break;
            }
        }
    } catch (Exception $exception) {
        Display::addFlash(
            Display::return_message($exception->getMessage(), 'error')
        );
    }
}

$tpl->assign('actions', $plugin->getToolbar());
$tpl->assign('meeting', $meeting);
$tpl->assign('url_extra', $urlExtra);
$tpl->assign('content', $tpl->fetch('zoom/view/meeting.tpl'));
$tpl->display_one_col_template();
