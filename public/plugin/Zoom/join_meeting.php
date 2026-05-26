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


api_block_anonymous_users();

$course_plugin = 'zoom'; // needed in order to load the plugin lang variables
$meetingId = isset($_REQUEST['meetingId']) ? (int) $_REQUEST['meetingId'] : 0;
if (empty($meetingId)) {
    api_not_allowed(true);
}

$plugin = ZoomPlugin::create();
/** @var Meeting|null $meeting */
$meeting = $plugin->getMeetingRepository()->findOneBy(['meetingId' => $meetingId]);
if (null === $meeting) {
    $meeting = $plugin->getMeetingRepository()->find($meetingId);
}
if (null === $meeting) {
    api_not_allowed(true, $plugin->get_lang('MeetingNotFound'));
}

$urlExtra = '';
if ($meeting->isCourseMeeting()) {
    $requestWasMissingCourseContext = empty($_GET['cid'])
        || !\array_key_exists('sid', $_GET)
        || !\array_key_exists('gid', $_GET)
    ;

    $urlExtra = zoom_plugin_restore_course_context($meeting);
    if ($requestWasMissingCourseContext && '' !== $urlExtra && !headers_sent()) {
        header('Location: '.api_get_path(WEB_PLUGIN_PATH).'Zoom/join_meeting.php?meetingId='.$meeting->getMeetingId().'&'.$urlExtra);
        exit;
    }

    api_protect_course_script(true);

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

$content = '';
try {
    $startJoinURL = $plugin->getStartOrJoinMeetingURL($meeting);
    $meetingInfo = $meeting->getMeetingInfoGet();
    $meetingTitle = null !== $meetingInfo ? (string) ($meetingInfo->topic ?? '') : '';
    if ('' === $meetingTitle) {
        $meetingTitle = $plugin->get_title();
    }
    $introduction = trim((string) $meeting->getIntroduction());

    $content .= '<section class="rounded-2xl border border-gray-25 bg-white p-6 shadow-sm">';
    $content .= '<div class="mb-6 flex flex-col gap-4 md:flex-row md:items-start md:justify-between">';
    $content .= '<div>';
    $content .= '<p class="mb-2 text-sm font-semibold uppercase tracking-wide text-primary">'.$plugin->get_lang('ZoomVideoConferences').'</p>';
    $content .= '<h1 class="text-2xl font-bold text-gray-90">'.htmlspecialchars($meetingTitle, ENT_QUOTES, 'UTF-8').'</h1>';
    $content .= '</div>';

    if (!empty($startJoinURL)) {
        $content .= Display::url(
            $plugin->get_lang('EnterMeeting'),
            $startJoinURL,
            [
                'class' => 'inline-flex items-center justify-center rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary/90',
                'target' => '_blank',
                'rel' => 'noopener noreferrer',
            ]
        );
    }
    $content .= '</div>';

    if ('' !== $introduction) {
        $content .= '<div class="prose max-w-none text-gray-90">'.$introduction.'</div>';
    }

    if (empty($startJoinURL)) {
        $content .= Display::return_message($plugin->get_lang('ConferenceNotAvailable'), 'warning');
    }

    if ($plugin->userIsConferenceManager($meeting)) {
        $content .= '<div class="mt-6 border-t border-gray-25 pt-4">';
        $content .= Display::url(
            get_lang('Details'),
            api_get_path(WEB_PLUGIN_PATH).'Zoom/meeting.php?meetingId='.$meeting->getMeetingId().('' !== $urlExtra ? '&'.$urlExtra : ''),
            [
                'class' => 'inline-flex items-center justify-center rounded-lg border border-gray-50 bg-white px-4 py-2 text-sm font-semibold text-gray-90 hover:bg-gray-15',
            ]
        );
        $content .= '</div>';
    }

    $content .= '</section>';
} catch (Exception $exception) {
    Display::addFlash(
        Display::return_message($exception->getMessage(), 'warning')
    );
}

Display::display_header($plugin->get_title());
echo $plugin->getToolbar();
echo $content;
Display::display_footer();
