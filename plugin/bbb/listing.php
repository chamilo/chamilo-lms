<?php
/* For license terms, see /license.txt */

/**
 * This script initiates a video conference session, calling the BigBlueButton API.
 *
 * @package chamilo.plugin.bigbluebutton
 */
$course_plugin = 'bbb'; //needed in order to load the plugin lang variables
require_once __DIR__.'/config.php';

$plugin = BBBPlugin::create();
$tool_name = $plugin->get_lang('Videoconference');

$htmlHeadXtra[] = api_get_js_simple(api_get_path(WEB_PLUGIN_PATH).'bbb/resources/utils.js');

$isGlobal = isset($_GET['global']) ? true : false;
$isGlobalPerUser = isset($_GET['user_id']) ? (int) $_GET['user_id'] : false;
$action = isset($_GET['action']) ? $_GET['action'] : '';

$bbb = new bbb('', '', $isGlobal, $isGlobalPerUser);

$conferenceManager = $bbb->isConferenceManager();
if ($bbb->isGlobalConference()) {
    api_block_anonymous_users();
} else {
    api_protect_course_script(true);
}

$courseInfo = api_get_course_info();
$courseCode = isset($courseInfo['code']) ? $courseInfo['code'] : '';

$message = '';
if ($conferenceManager) {
    switch ($action) {
        case 'add_to_calendar':
            if ($bbb->isGlobalConference()) {
                return false;
            }
            $courseInfo = api_get_course_info();
            $agenda = new Agenda('course');
            $id = (int) $_GET['id'];
            $title = sprintf($plugin->get_lang('VideoConferenceXCourseX'), $id, $courseInfo['name']);
            $content = Display::url($plugin->get_lang('GoToTheVideoConference'), $_GET['url']);

            $eventId = $agenda->addEvent(
                $_REQUEST['start'],
                null,
                'true',
                $title,
                $content,
                ['everyone']
            );
            if (!empty($eventId)) {
                $message = Display::return_message($plugin->get_lang('VideoConferenceAddedToTheCalendar'), 'success');
            } else {
                $message = Display::return_message(get_lang('Error'), 'error');
            }
            break;
        case 'copy_record_to_link_tool':
            $result = $bbb->copyRecordingToLinkTool($_GET['id']);
            if ($result) {
                $message = Display::return_message($plugin->get_lang('VideoConferenceAddedToTheLinkTool'), 'success');
            } else {
                $message = Display::return_message(get_lang('Error'), 'error');
            }
            break;
        case 'regenerate_record':
            if ($plugin->get('allow_regenerate_recording') !== 'true') {
                api_not_allowed(true);
            }
            $recordId = isset($_GET['record_id']) ? $_GET['record_id'] : '';
            $result = $bbb->regenerateRecording($_GET['id'], $recordId);
            if ($result) {
                $message = Display::return_message(get_lang('Success'), 'success');
            } else {
                $message = Display::return_message(get_lang('Error'), 'error');
            }

            Display::addFlash($message);
            header('Location: '.$bbb->getListingUrl());
            exit;
            break;
        case 'delete_record':
            $result = $bbb->deleteRecording($_GET['id']);
            if ($result) {
                $message = Display::return_message(get_lang('Deleted'), 'success');
            } else {
                $message = Display::return_message(get_lang('Error'), 'error');
            }

            Display::addFlash($message);
            header('Location: '.$bbb->getListingUrl());
            exit;
            break;
        case 'end':
            $bbb->endMeeting($_GET['id']);
            $message = Display::return_message(
                $plugin->get_lang('MeetingClosed').'<br />'.$plugin->get_lang('MeetingClosedComment'),
                'success',
                false
            );

            if (file_exists(__DIR__.'/config.vm.php')) {
                require __DIR__.'/../../vendor/autoload.php';
                require __DIR__.'/lib/vm/AbstractVM.php';
                require __DIR__.'/lib/vm/VMInterface.php';
                require __DIR__.'/lib/vm/DigitalOceanVM.php';
                require __DIR__.'/lib/VM.php';
                $config = require __DIR__.'/config.vm.php';

                $vm = new VM($config);
                $vm->resizeToMinLimit();
            }

            Display::addFlash($message);
            header('Location: '.$bbb->getListingUrl());
            exit;
            break;
        case 'publish':
            $bbb->publishMeeting($_GET['id']);
            Display::addFlash(Display::return_message(get_lang('Updated')));
            header('Location: '.$bbb->getListingUrl());
            exit;
            break;
        case 'unpublish':
            $bbb->unpublishMeeting($_GET['id']);
            Display::addFlash(Display::return_message(get_lang('Updated')));
            header('Location: '.$bbb->getListingUrl());
            exit;
            break;
        case 'logout':
            if ($plugin->get('allow_regenerate_recording') === 'true') {
                $allow = api_get_course_setting('bbb_force_record_generation', $courseInfo) == 1 ? true : false;
                if ($allow) {
                    $result = $bbb->getMeetingByRemoteId($_GET['remote_id']);
                    if (!empty($result)) {
                        $result = $bbb->regenerateRecording($result['id']);
                        if ($result) {
                            Display::addFlash(Display::return_message(get_lang('Success')));
                        } else {
                            Display::addFlash(Display::return_message(get_lang('Error'), 'error'));
                        }
                    }
                }
            }

            header('Location: '.$bbb->getListingUrl());
            exit;
            break;
        default:
            break;
    }
}

$meetings = $bbb->getMeetings(
    api_get_course_int_id(),
    api_get_session_id(),
    api_get_group_id()
);
if (!empty($meetings)) {
    $meetings = array_reverse($meetings);
}
$usersOnline = $bbb->getUsersOnlineInCurrentRoom();
$maxUsers = $bbb->getMaxUsersLimit();
$status = $bbb->isServerRunning();
$videoConferenceName = $bbb->getCurrentVideoConferenceName();
$meetingExists = $bbb->meetingExists($videoConferenceName);
$showJoinButton = false;

// Only conference manager can see the join button
$userCanSeeJoinButton = $conferenceManager;
if ($bbb->isGlobalConference() && $bbb->isGlobalConferencePerUserEnabled()) {
    // Any user can see the "join button" BT#12620
    $userCanSeeJoinButton = true;
}

if (($meetingExists || $userCanSeeJoinButton) && ($maxUsers == 0 || $maxUsers > $usersOnline)) {
    $showJoinButton = true;
}
$conferenceUrl = $bbb->getConferenceUrl();
$courseInfo = api_get_course_info();
$formToString = '';

if ($bbb->isGlobalConference() === false &&
    $conferenceManager &&
    !empty($courseInfo) &&
    $plugin->get('enable_conference_in_course_groups') === 'true'
) {
    $url = api_get_self().'?'.api_get_cidreq(true, false).'&gidReq=';
    $htmlHeadXtra[] = '<script>
        $(document).ready(function() {
            $("#group_select").on("change", function() {
                var groupId = $(this).find("option:selected").val();
                var url = "'.$url.'";
                window.location.replace(url+groupId);
            });
        });
        </script>';

    $form = new FormValidator(api_get_self().'?'.api_get_cidreq());
    $groupId = api_get_group_id();
    $groups = GroupManager::get_groups();
    if ($groups) {
        $meetingsInGroup = $bbb->getAllMeetingsInCourse(api_get_course_int_id(), api_get_session_id(), 1);
        $meetingsGroup = array_column($meetingsInGroup, 'status', 'group_id');

        foreach ($groups as &$groupData) {
            $itemGroupId = $groupData['id'];
            if (isset($meetingsGroup[$itemGroupId]) && $meetingsGroup[$itemGroupId] == 1) {
                $groupData['name'] .= ' ('.get_lang('Active').')';
            }
        }

        $groupList[0] = get_lang('Select');
        $groupList = array_merge($groupList, array_column($groups, 'name', 'iid'));

        $form->addSelect('group_id', get_lang('Groups'), $groupList, ['id' => 'group_select']);
        $form->setDefaults(['group_id' => $groupId]);
        $formToString = $form->returnForm();
    }
}

// Default URL
$urlList[] = Display::url(
    $plugin->get_lang('EnterConference'),
    $conferenceUrl,
    ['target' => '_blank', 'class' => 'btn btn-primary btn-large']
);

$type = $plugin->get('launch_type');
$warningInterfaceMessage = '';
$showClientOptions = false;

switch ($type) {
    case BBBPlugin::LAUNCH_TYPE_DEFAULT:
        $urlList = [];
        $urlList[] = Display::url(
            $plugin->get_lang('EnterConference'),
            $conferenceUrl.'&interface='.$plugin->get('interface'),
            ['target' => '_blank', 'class' => 'btn btn-primary btn-large']
        );
        break;
    case BBBPlugin::LAUNCH_TYPE_SET_BY_TEACHER:
        if ($conferenceManager) {
            $urlList = $plugin->getUrlInterfaceLinks($conferenceUrl);
            $warningInterfaceMessage = Display::return_message($plugin->get_lang('ParticipantsWillUseSameInterface'));
            $showClientOptions = true;
        } else {
            $meetingInfo = $bbb->getMeetingByName($videoConferenceName);
            switch ($meetingInfo['interface']) {
                case BBBPlugin::INTERFACE_FLASH:
                    $url = $plugin->getFlashUrl($conferenceUrl);
                    break;
                case BBBPlugin::INTERFACE_HTML5:
                    $url = $plugin->getHtmlUrl($conferenceUrl);
                    break;
            }
        }
        break;
    case BBBPlugin::LAUNCH_TYPE_SET_BY_STUDENT:
        if ($conferenceManager) {
            $urlList = $plugin->getUrlInterfaceLinks($conferenceUrl);
            $showClientOptions = true;
        } else {
            if ($meetingExists) {
                $urlList = $plugin->getUrlInterfaceLinks($conferenceUrl);
                $showClientOptions = true;
            }
        }

        break;
}
$tpl = new Template($tool_name);
$tpl->assign('allow_to_edit', $conferenceManager);
$tpl->assign('meetings', $meetings);
$tpl->assign('conference_url', $conferenceUrl);
$tpl->assign('users_online', $usersOnline);
$tpl->assign('conference_manager', $conferenceManager);
$tpl->assign('max_users_limit', $maxUsers);
$tpl->assign('bbb_status', $status);
$tpl->assign('show_join_button', $showJoinButton);
$tpl->assign('message', $message);
$tpl->assign('form', $formToString);
$tpl->assign('enter_conference_links', $urlList);
$tpl->assign('warning_inteface_msg', $warningInterfaceMessage);
$tpl->assign('show_client_options', $showClientOptions);

$listing_tpl = 'bbb/view/listing.tpl';
$content = $tpl->fetch($listing_tpl);

$actionLinks = '';
if (api_is_platform_admin()) {
    $actionLinks .= Display::toolbarButton(
        $plugin->get_lang('AdminView'),
        api_get_path(WEB_PLUGIN_PATH).'bbb/admin.php',
        'list',
        'primary'
    );

    $tpl->assign(
        'actions',
        Display::toolbarAction('toolbar', [$actionLinks])
    );
}

$tpl->assign('content', $content);
$tpl->display_one_col_template();
