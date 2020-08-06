<?php

/* For license terms, see /license.txt */

use Chamilo\PluginBundle\Zoom\Meeting;

$course_plugin = 'zoom'; // needed in order to load the plugin lang variables

require_once __DIR__.'/config.php';

$plugin = ZoomPlugin::create();
$tool_name = $plugin->get_lang('ZoomVideoConferences');
$tpl = new Template($plugin->get_lang('ZoomVideoConferences'));

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

$tpl->assign('actions', $plugin->getToolbar());
$tpl->assign('meeting', $meeting);
$tpl->assign('content', $tpl->fetch('zoom/view/activity.tpl'));
$tpl->display_one_col_template();
