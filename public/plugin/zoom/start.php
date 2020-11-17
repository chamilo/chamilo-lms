<?php

/* For license terms, see /license.txt */

$course_plugin = 'zoom'; // needed in order to load the plugin lang variables

require_once __DIR__.'/config.php';

api_protect_course_script(true);

$this_section = SECTION_COURSES;
$logInfo = [
    'tool' => 'Videoconference Zoom',
];
Event::registerLog($logInfo);

$course = api_get_course_entity();
if (null === $course) {
    api_not_allowed(true);
}

$group = api_get_group_entity();
$session = api_get_session_entity();
$plugin = ZoomPlugin::create();

if (null !== $group) {
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'group/group.php?'.api_get_cidreq(),
        'name' => get_lang('Groups'),
    ];
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'group/group_space.php?'.api_get_cidreq(),
        'name' => get_lang('GroupSpace').' '.$group->getName(),
    ];
}

$url = api_get_self().'?'.api_get_cidreq(true, false).'&gidReq=';
$htmlHeadXtra[] = '<script>
 $(function() {
    $("#group_select").on("change", function() {
        var groupId = $(this).find("option:selected").val();
        var url = "'.$url.'";
        window.location.replace(url+groupId);
    });
});
</script>';

$tool_name = $plugin->get_lang('ZoomVideoConferences');
$tpl = new Template($tool_name);

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

$isManager = $plugin->userIsCourseConferenceManager();
if ($isManager) {
    $groupId = api_get_group_id();
    $groups = GroupManager::get_groups();
    if (!empty($groups)) {
        $form = new FormValidator('group_filter');
        $groupList[0] = get_lang('Select');
        foreach ($groups as $groupData) {
            $itemGroupId = $groupData['iid'];
            /*if (isset($meetingsGroup[$itemGroupId]) && $meetingsGroup[$itemGroupId] == 1) {
                $groupData['name'] .= ' ('.get_lang('Active').')';
            }*/
            $groupList[$itemGroupId] = $groupData['name'];
        }
        $form->addSelect('group_id', get_lang('Groups'), $groupList, ['id' => 'group_select']);
        $form->setDefaults(['group_id' => $groupId]);
        $formToString = $form->returnForm();

        $tpl->assign('group_form', $formToString);
    }

    switch ($action) {
        case 'delete':
            $meeting = $plugin->getMeetingRepository()->findOneBy(['meetingId' => $_REQUEST['meetingId']]);
            if ($meeting && $meeting->isCourseMeeting()) {
                $plugin->deleteMeeting($meeting, api_get_self().'?'.api_get_cidreq());
            }
            break;
    }

    $user = api_get_user_entity(api_get_user_id());

    $tpl->assign(
        'instant_meeting_form',
        $plugin->getCreateInstantMeetingForm(
            $user,
            $course,
            $group,
            $session
        )->returnForm()
    );
    $tpl->assign(
        'schedule_meeting_form',
        $plugin->getScheduleMeetingForm(
            $user,
            $course,
            $group,
            $session
        )->returnForm()
    );
}

try {
    $tpl->assign(
        'meetings',
        $plugin->getMeetingRepository()->courseMeetings($course, $group, $session)
    );
} catch (Exception $exception) {
    Display::addFlash(
        Display::return_message('Could not retrieve scheduled meeting list: '.$exception->getMessage(), 'error')
    );
}

$tpl->assign('is_manager', $isManager);
$tpl->assign('content', $tpl->fetch('zoom/view/start.tpl'));
$tpl->display_one_col_template();
