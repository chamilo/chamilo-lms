<?php

/* For license terms, see /license.txt */

$course_plugin = 'zoom'; // needed in order to load the plugin lang variables

$cidReset = true;
require_once __DIR__.'/config.php';

if (!ZoomPlugin::currentUserCanJoinGlobalMeeting()) {
    api_not_allowed(true);
}

$plugin = ZoomPlugin::create();
$user = api_get_user_entity(api_get_user_id());

$form = $plugin->getAdminSearchForm();
$startDate = new DateTime($form->getElement('start')->getValue());
$endDate = new DateTime($form->getElement('end')->getValue());
$scheduleForm = $plugin->getScheduleMeetingForm($user);
$tpl = new Template();
$tpl->assign('meetings', $plugin->getMeetingRepository()->periodUserMeetings($startDate, $endDate, $user));
$tpl->assign('allow_recording', $plugin->hasRecordingAvailable());
$tpl->assign('actions', $plugin->getToolbar());
$tpl->assign('search_form', $form->returnForm());
$tpl->assign('schedule_form', $scheduleForm->returnForm());
$tpl->assign('content', $tpl->fetch('zoom/view/meetings.tpl'));
$tpl->display_one_col_template();
