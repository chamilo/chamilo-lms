<?php

/* For license terms, see /license.txt */

$course_plugin = 'zoom'; // needed in order to load the plugin lang variables

require_once __DIR__.'/config.php';

if (!ZoomPlugin::currentUserCanCreateUserMeeting()) {
    api_not_allowed(true);
}

$plugin = ZoomPlugin::create();
$user = api_get_user_entity(api_get_user_id());

$form = $plugin->getAdminSearchForm();
$startDate = new DateTime($form->getElement('start')->getValue());
$endDate = new DateTime($form->getElement('end')->getValue());

$tpl = new Template();

$tpl->assign('meetings', $plugin->getMeetingRepository()->periodUserMeetings($startDate, $endDate, $user));
if ('true' === $plugin->get('enableCloudRecording')) {
    $tpl->assign(
        'recordings',
        $plugin->getRecordingRepository()->getPeriodUserRecordings($startDate, $endDate)
    );
}

$tpl->assign('actions', $plugin->getToolbar());
$tpl->assign('search_form', $form->returnForm());
$tpl->assign('schedule_form', $plugin->getScheduleMeetingForm($user)->returnForm());
$tpl->assign('type', 'user');
$tpl->assign('content', $tpl->fetch('zoom/view/list.tpl'));
$tpl->display_one_col_template();
