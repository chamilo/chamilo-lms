<?php

/* For license terms, see /license.txt */

use Chamilo\PluginBundle\Zoom\Meeting;

$course_plugin = 'zoom'; // needed in order to load the plugin lang variables

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


$plugin = ZoomPlugin::create();
$tool_name = $plugin->get_lang('ZoomVideoConferences');
$meetingId = isset($_REQUEST['meetingId']) ? (int) $_REQUEST['meetingId'] : 0;
if (empty($meetingId)) {
    api_not_allowed(true);
}

$content = '';
/** @var Meeting $meeting */
$meeting = $plugin->getMeetingRepository()->findOneBy(['meetingId' => $meetingId]);
if (null === $meeting) {
    api_not_allowed(true);
}

if (!$plugin->userIsConferenceManager($meeting)) {
    api_not_allowed(true);
}
$returnURL = 'meetings.php';
$urlExtra = '';
if ($meeting->isCourseMeeting()) {
    $urlExtra = zoom_plugin_restore_course_context($meeting);
    api_protect_course_script(true);
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

$interbreadcrumb[] = [
    'url' => $returnURL,
    'name' => $plugin->get_lang('ZoomVideoConferences'),
];
$tpl = new Template($meeting->getMeetingId());
$tpl->assign('actions', $plugin->getToolbar());
$tpl->assign('meeting', $meeting);
$tpl->assign('url_extra', $urlExtra);
$tpl->assign('content', $tpl->fetch('Zoom/view/activity.tpl'));
$tpl->display_one_col_template();
