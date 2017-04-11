<?php
/**
 * This script initiates a video conference session, calling the BigBlueButton API
 * @package chamilo.plugin.bigbluebutton
 */

use Chamilo\UserBundle\Entity\User;

$course_plugin = 'bbb'; //needed in order to load the plugin lang variables
$cidReset = true;

require_once __DIR__.'/../../main/inc/global.inc.php';

api_protect_admin_script();

$plugin = BBBPlugin::create();
$tool_name = $plugin->get_lang('Videoconference');

$isGlobal = isset($_GET['global']) ? true : false;

$bbb = new bbb('', '', $isGlobal);
$action = isset($_GET['action']) ? $_GET['action'] : null;

$currentMonth = date('n');
$dateStart = isset($_REQUEST['search_meeting_start']) ? $_REQUEST['search_meeting_start'] : date('Y-m-d', mktime(1, 1, 1, $currentMonth, 1, date('Y')));
$dateEnd = isset($_REQUEST['search_meeting_end']) ? $_REQUEST['search_meeting_end'] : date('Y-m-d', mktime(1, 1, 1, ++$currentMonth, 0, date('Y')));

$dateRange = [
    'search_meeting_start' => $dateStart,
    'search_meeting_end' => $dateEnd
];

$form = new FormValidator(get_lang('Search'));
$form->addDatePicker('search_meeting_start', get_lang('DateStart'));
$form->addDatePicker('search_meeting_end', get_lang('DateEnd'));
$form->addButtonSearch(get_lang('Search'));
$form->setDefaults($dateRange);

if ($form->validate()) {
    $dateRange = $form->getSubmitValues();
}

$meetings = $bbb->getMeetings(0, 0, 0, true, $dateRange);

foreach ($meetings as &$meeting) {
    $participants = $bbb->findMeetingParticipants($meeting['id']);

    foreach ($participants as $meetingParticipant) {
        /** @var User $participant */
        $participant = $meetingParticipant['participant'];
        $meeting['participants'][] = $participant->getCompleteName()
            . ' ('.$participant->getEmail().')';
    }
}

if ($action) {
    switch ($action) {
        case 'export':
            $dataToExport = [
                [$tool_name, $plugin->get_lang('RecordList')],
                [],
                [
                    get_lang('CreatedAt'),
                    get_lang('Status'),
                    $plugin->get_lang('Records'),
                    get_lang('Course'),
                    get_lang('Session'),
                    get_lang('Participants'),
                ]
            ];

            foreach ($meetings as $meeting) {
                $dataToExport[] = [
                    $meeting['created_at'],
                    $meeting['status'] == 1 ? $plugin->get_lang('MeetingOpened') : $plugin->get_lang('MeetingClosed'),
                    $meeting['record'] == 1 ? get_lang('Yes') : get_lang('No'),
                    $meeting['course'] ? $meeting['course']->getTitle() : '-',
                    $meeting['session'] ? $meeting['session']->getName() : '-',
                    isset($meeting['participants']) ? implode(PHP_EOL, $meeting['participants']) : null
                ];
            }

            Export::arrayToXls($dataToExport);
            break;
    }
}

if (!empty($meetings)) {
    $meetings = array_reverse($meetings);
}

if (!$bbb->isServerRunning()) {
    Display::addFlash(
        Display::return_message(get_lang('ServerIsNotRunning'), 'error')
    );
}

$htmlHeadXtra[] = api_get_js_simple(
    api_get_path(WEB_PLUGIN_PATH).'bbb/resources/utils.js'
);
$htmlHeadXtra[] = "<script>var _p = {web_plugin: '".api_get_path(WEB_PLUGIN_PATH)."'}</script>";

$tpl = new Template($tool_name);
$tpl->assign('meetings', $meetings);
$tpl->assign('search_form', $form->returnForm());

$content = $tpl->fetch('bbb/admin.tpl');

if ($meetings) {
    $actions = Display::toolbarButton(
        get_lang('ExportInExcel'),
        api_get_self().'?'.http_build_query([
            'action' => 'export',
            'search_meeting_start' => $dateStart,
            'search_meeting_end' => $dateEnd
        ]),
        'file-excel-o',
        'success'
    );

    $tpl->assign(
        'actions',
        Display::toolbarAction('toolbar', [$actions])
    );
}

$tpl->assign('header', $plugin->get_lang('RecordList'));
$tpl->assign('content', $content);
$tpl->display_one_col_template();
