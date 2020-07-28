<?php

/* For license terms, see /license.txt */

$course_plugin = 'zoom'; // needed in order to load the plugin lang variables
$cidReset = true;

require_once __DIR__.'/config.php';

api_protect_admin_script();

$plugin = ZoomPlugin::create();
$tool_name = $plugin->get_lang('ZoomVideoConferences');
$this_section = SECTION_PLATFORM_ADMIN;

$form = $plugin->getAdminSearchForm();
$startDate = new DateTime($form->getSubmitValue('start'));
$endDate = new DateTime($form->getSubmitValue('end'));

$tpl = new Template($tool_name);

$tpl->assign('meetings', $plugin->getMeetingRepository()->periodMeetings($startDate, $endDate));
if ('true' === $plugin->get('enableCloudRecording')) {
    $tpl->assign('recordings', $plugin->getRecordingRepository()->getPeriodRecordings($startDate, $endDate));
}
$tpl->assign('actions', $plugin->getToolbar());
$tpl->assign('search_form', $form->returnForm());
$tpl->assign('content', $tpl->fetch('zoom/view/admin.tpl'));
$tpl->display_one_col_template();
