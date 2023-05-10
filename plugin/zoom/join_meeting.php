<?php

/* For license terms, see /license.txt */

use Chamilo\PluginBundle\Zoom\Meeting;

require_once __DIR__.'/config.php';

api_block_anonymous_users();

$course_plugin = 'zoom'; // needed in order to load the plugin lang variables
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

if ($meeting->isCourseMeeting()) {
    api_protect_course_script(true);
    if (api_is_in_group()) {
        $interbreadcrumb[] = [
            'url' => api_get_path(WEB_CODE_PATH).'group/group.php?'.api_get_cidreq(),
            'name' => get_lang('Groups'),
        ];
        $interbreadcrumb[] = [
            'url' => api_get_path(WEB_CODE_PATH).'group/group_space.php?'.api_get_cidreq(),
            'name' => get_lang('GroupSpace').' '.$meeting->getGroup()->getName(),
        ];
    }
}

$startJoinURL = '';
$detailsURL = '';
$signature = '';
$btnAnnouncement = '';

$currentUser = api_get_user_entity(api_get_user_id());
$isConferenceManager = $plugin->userIsConferenceManager($meeting);

try {
    $startJoinURL = $plugin->getStartOrJoinMeetingURL($meeting);

    if (empty($startJoinURL)) {
        Display::addFlash(
            Display::return_message($plugin->get_lang('ConferenceNotAvailable'), 'warning')
        );
    }

    if ($meeting->isSignAttendance() && !$isConferenceManager) {
        $registrant = $meeting->getRegistrantByUser($currentUser);
        $signature = $registrant ? $registrant->getSignature() : null;

        Security::get_token('zoom_signature');
    }

    if ($isConferenceManager) {
        $detailsURL = api_get_path(WEB_PLUGIN_PATH).'zoom/meeting.php?meetingId='.$meeting->getMeetingId();
    }

    $allowAnnouncementsToSessionAdmin = api_get_configuration_value('session_admin_access_system_announcement');

    if (api_is_platform_admin($allowAnnouncementsToSessionAdmin)) {
        $announcementUrl = '';

        if ($announcement = $meeting->getSysAnnouncement()) {
            $announcementUrl = api_get_path(WEB_CODE_PATH).'admin/system_announcements.php?'
                .http_build_query(
                    [
                        'action' => 'edit',
                        'id' => $announcement->getId(),
                    ]
                );
        } else {
            $announcementUrl = api_get_path(WEB_CODE_PATH).'admin/system_announcements.php?'
                .http_build_query(
                    [
                        'action' => 'add',
                        'type' => 'zoom_conference',
                        'meeting' => $meeting->getMeetingId(),
                    ]
                );
        }

        $btnAnnouncement = Display::toolbarButton(
            $announcement ? get_lang('EditSystemAnnouncement') : get_lang('AddSystemAnnouncement'),
            $announcementUrl,
            'bullhorn'
        );
    }
} catch (Exception $exception) {
    Display::addFlash(
        Display::return_message($exception->getMessage(), 'warning')
    );
}

$htmlHeadXtra[] = api_get_asset('signature_pad/signature_pad.umd.js');

$tpl = new Template($meeting->getMeetingId());
$tpl->assign('meeting', $meeting);
$tpl->assign('start_url', $startJoinURL);
$tpl->assign('details_url', $detailsURL);
$tpl->assign('btn_announcement', $btnAnnouncement);
$tpl->assign('is_conference_manager', $isConferenceManager);
$tpl->assign('signature', $signature);
$content = $tpl->fetch('zoom/view/join.tpl');
$tpl->assign('actions', $plugin->getToolbar());
$tpl->assign('content', $content);
$tpl->display_one_col_template();
