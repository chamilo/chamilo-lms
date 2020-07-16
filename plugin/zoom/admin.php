<?php
/* For license terms, see /license.txt */

$course_plugin = 'zoom'; // needed in order to load the plugin lang variables
$cidReset = true;

require_once __DIR__.'/config.php';

api_protect_admin_script();

$tool_name = get_lang('ZoomVideoConferences');

$plugin = ZoomPlugin::create();

// the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;

$form = $plugin->getAdminSearchForm();
$startDate = new DateTime($form->getElement('start')->getValue());
$endDate = new DateTime($form->getElement('end')->getValue());
$type = $form->getElement('type')->getValue();

$tpl = new Template($tool_name);
$tpl->assign('meetings', $plugin->getPeriodMeetings($type, $startDate, $endDate));
if ($plugin->get('enableCloudRecording')) {
    $tpl->assign('recordings', $plugin->getRecordings($startDate, $endDate));
}
$tpl->assign('search_form', $form->returnForm());
$tpl->assign('content', $tpl->fetch('zoom/view/admin.tpl'));
$tpl->display_one_col_template();
