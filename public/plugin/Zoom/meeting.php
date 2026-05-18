<?php

/* For license terms, see /license.txt */

use Chamilo\PluginBundle\Zoom\Meeting;

require_once __DIR__.'/config.php';


if (!function_exists('zoom_plugin_restore_course_context')) {
    function zoom_plugin_restore_course_context(Meeting $meeting): string
    {
        $course = $meeting->getCourse();
        if (null === $course) {
            return '';
        }

        if (!isset($_REQUEST['cid']) || empty($_REQUEST['cid'])) {
            $_GET['cid'] = $course->getId();
            $_REQUEST['cid'] = $course->getId();
        }

        $session = $meeting->getSession();
        if (!isset($_REQUEST['sid']) || '' === (string) $_REQUEST['sid']) {
            $sessionId = null !== $session ? (int) $session->getId() : 0;
            $_GET['sid'] = $sessionId;
            $_REQUEST['sid'] = $sessionId;
        }

        $group = $meeting->getGroup();
        if (!isset($_REQUEST['gid']) || '' === (string) $_REQUEST['gid']) {
            $groupId = 0;
            if (null !== $group) {
                if (method_exists($group, 'getIid')) {
                    $groupId = (int) $group->getIid();
                } elseif (method_exists($group, 'getId')) {
                    $groupId = (int) $group->getId();
                }
            }

            $_GET['gid'] = $groupId;
            $_REQUEST['gid'] = $groupId;
        }

        return api_get_cidreq();
    }
}


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
    $urlExtra = zoom_plugin_restore_course_context($meeting);
    api_protect_course_script(true);
    $this_section = SECTION_COURSES;
    $returnURL = 'start.php?'.$urlExtra;

    $group = $meeting->getGroup();
    if (api_is_in_group() && null !== $group) {
        $interbreadcrumb[] = [
            'url' => api_get_path(WEB_CODE_PATH).'group/group.php?'.$urlExtra,
            'name' => get_lang('Groups'),
        ];
        $interbreadcrumb[] = [
            'url' => api_get_path(WEB_CODE_PATH).'group/group_space.php?'.$urlExtra,
            'name' => get_lang('Group area').' '.$group->getTitle(),
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
$tpl->assign('isConferenceManager', false);
$tpl->assign('editMeetingForm', '');
$tpl->assign('deleteMeetingForm', '');
$tpl->assign('registerParticipantForm', '');
$tpl->assign('fileForm', '');
$tpl->assign('registrants', []);
$tpl->assign('currentUserJoinURL', '');

if ($plugin->userIsConferenceManager($meeting)) {
    // user can edit, start and delete meeting
    $tpl->assign('isConferenceManager', true);
    $tpl->assign('editMeetingForm', $plugin->getEditMeetingForm($meeting)->returnForm());
    $tpl->assign('deleteMeetingForm', $plugin->getDeleteMeetingForm($meeting, $returnURL)->returnForm());

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
$tpl->assign('content', $tpl->fetch('Zoom/view/meeting.tpl'));
$tpl->display_one_col_template();
