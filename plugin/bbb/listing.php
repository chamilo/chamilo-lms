<?php
/**
 * This script initiates a video conference session, calling the BigBlueButton API
 * @package chamilo.plugin.bigbluebutton
 */

$course_plugin = 'bbb'; //needed in order to load the plugin lang variables
require_once __DIR__.'/config.php';

$plugin = BBBPlugin::create();
$tool_name = $plugin->get_lang('Videoconference');

$htmlHeadXtra[] = api_get_js_simple(
    api_get_path(WEB_PLUGIN_PATH).'bbb/resources/utils.js'
);
$htmlHeadXtra[] = "<script>var _p = {web_plugin: '".api_get_path(WEB_PLUGIN_PATH)."'}</script>";

$tpl = new Template($tool_name);

$isGlobal = isset($_GET['global']) ? true : false;
$isGlobalPerUser = isset($_GET['user_id']) ? (int) $_GET['user_id'] : false;

$bbb = new bbb('', '', $isGlobal, $isGlobalPerUser);
$action = isset($_GET['action']) ? $_GET['action'] : null;

$conferenceManager = $bbb->isConferenceManager();
if ($bbb->isGlobalConference()) {
    api_block_anonymous_users();
} else {
    api_protect_course_script(true);
}

$message = null;

if ($conferenceManager) {
    switch ($action) {
        case 'add_to_calendar':
            if ($bbb->isGlobalConference()) {
                return false;
            }
            $courseInfo = api_get_course_info();
            $agenda = new Agenda('course');
            $id = intval($_GET['id']);
            $title = sprintf($plugin->get_lang('VideoConferenceXCourseX'), $id, $courseInfo['name']);
            $content = Display::url($plugin->get_lang('GoToTheVideoConference'), $_GET['url']);

            $eventId = $agenda->addEvent(
                $_REQUEST['start'],
                null,
                'true',
                $title,
                $content,
                array('everyone')
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
        case 'delete_record':
            $result = $bbb->deleteRecording($_GET['id']);
            if ($result) {
                $message = Display::return_message(get_lang('Deleted'), 'success');
            } else {
                $message = Display::return_message(get_lang('Error'), 'error');
            }

            Display::addFlash($message);
            header('Location: '.$bbb->getListingUrl());
            break;
        case 'end':
            $bbb->endMeeting($_GET['id']);
            $message = Display::return_message(
                $plugin->get_lang('MeetingClosed').'<br />'.$plugin->get_lang(
                    'MeetingClosedComment'
                ),
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
            $result = $bbb->publishMeeting($_GET['id']);
            break;
        case 'unpublish':
            $result = $bbb->unpublishMeeting($_GET['id']);
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
$meetingExists = $bbb->meetingExists($bbb->getCurrentVideoConferenceName());
$showJoinButton = false;
if (($meetingExists || $conferenceManager) && ($maxUsers == 0 || $maxUsers > $usersOnline)) {
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
        $(document).ready(function(){
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

$listing_tpl = 'bbb/listing.tpl';
$content = $tpl->fetch($listing_tpl);

if (api_is_platform_admin()) {
    $actionLinks = Display::toolbarButton(
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
