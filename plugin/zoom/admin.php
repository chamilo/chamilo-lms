<?php

/* For license terms, see /license.txt */

use Chamilo\PluginBundle\Zoom\CourseMeetingList;

$course_plugin = 'zoom'; // needed in order to load the plugin lang variables
$cidReset = true;

require_once __DIR__.'/config.php';

api_protect_admin_script();

$tool_name = get_lang('ZoomVideoConferences');

$plugin = ZoomPlugin::create();

// the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;

$form = new FormValidator(get_lang('Search'));
$startDatePicker = $form->addDatePicker('search_meeting_start', get_lang('StartDate'));
$endDatePicker = $form->addDatePicker('search_meeting_end', get_lang('EndDate'));
$typeSelect = $form->addRadio(
    'type',
    get_lang('Type'),
    [
        CourseMeetingList::TYPE_SCHEDULED => get_lang('ScheduledMeetings'),
        CourseMeetingList::TYPE_LIVE => get_lang('LiveMeetings'),
        CourseMeetingList::TYPE_UPCOMING => get_lang('UpcomingMeetings'),
    ]
);
$form->addButtonSearch(get_lang('Search'));
if ($form->validate()) {
    $startDate = new DateTime($startDatePicker->getValue());
    $endDate = new DateTime($endDatePicker->getValue());
    $type = $typeSelect->getValue();
} else {
    $oneMonth = new DateInterval('P1M');
    $startDate = new DateTime();
    $startDate->sub($oneMonth);
    $endDate = new DateTime();
    $endDate->add($oneMonth);
    $type = CourseMeetingList::TYPE_SCHEDULED;
}
$form->setDefaults([
    'search_meeting_start' => $startDate->format('Y-m-d'),
    'search_meeting_end' => $endDate->format('Y-m-d'),
    'type' => $type,
]);

$tpl = new Template($tool_name);
$tpl->assign('meetings', $plugin->getPeriodMeetings($type, $startDate, $endDate));
if ($plugin->get('enableCloudRecording')) {
    $tpl->assign('recordings', $plugin->getRecordings($startDate, $endDate));
}
$tpl->assign('search_form', $form->returnForm());
$tpl->assign('content', $tpl->fetch('zoom/view/admin.tpl'));
$tpl->display_one_col_template();
