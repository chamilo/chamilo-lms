<?php

/* For license terms, see /license.txt */

use Chamilo\PluginBundle\Zoom\API\JWTClient;

$course_plugin = 'zoom'; // needed in order to load the plugin lang variables
$cidReset = true;

require_once __DIR__.'/config.php';

api_protect_admin_script();

$tool_name = get_lang('ZoomVideoconference');

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
        JWTClient::MEETING_LIST_TYPE_SCHEDULED => get_lang('ScheduledMeetings'),
        JWTClient::MEETING_LIST_TYPE_LIVE => get_lang('LiveMeetings'),
        JWTClient::MEETING_LIST_TYPE_UPCOMING => get_lang('UpcomingMeetings'),
    ]
);
$form->addButtonSearch(get_lang('Search'));
if ($form->validate()) {
    $startDate = new DateTime($startDatePicker->getValue());
    $endDate = new DateTime($endDatePicker->getValue());
    $type = $typeSelect->getValue();
} else {
    $startDate = new DateTime();
    $endDate = new DateTime();
    $endDate->add(new DateInterval('P1M'));
    $type = JWTClient::MEETING_LIST_TYPE_SCHEDULED;
}
$form->setDefaults([
    'search_meeting_start' => $startDate->format('Y-m-d'),
    'search_meeting_end' => $endDate->format('Y-m-d'),
    'type' => $type,
]);

$tpl = new Template($tool_name);
$tpl->assign('meetings', $plugin->getPeriodMeetings($type, $startDate, $endDate));
$tpl->assign('search_form', $form->returnForm());
$tpl->assign('content', $tpl->fetch('zoom/view/admin.tpl'));
$tpl->display_one_col_template();
