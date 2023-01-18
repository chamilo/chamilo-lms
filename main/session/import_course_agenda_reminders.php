<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Session;

require_once __DIR__.'/../inc/global.inc.php';

$sessionId = api_get_session_id();
$sessionUrl = api_get_path(WEB_CODE_PATH).'session/resume_session.php?id_session='.$sessionId;
$selfUrl = api_get_self()."?session_id=$sessionId";

api_protect_admin_script(true);

$isAgendaRemindersEnabled = api_get_configuration_value('agenda_reminders');

if (!$isAgendaRemindersEnabled) {
    api_not_allowed(true);
}

$tblPersonalAgenda = Database::get_main_table(TABLE_PERSONAL_AGENDA);

$tags = AnnouncementManager::getTags([
    '((course_title))',
    '((course_link))',
    '((teachers))',
    '((coaches))',
]);
$tags[] = '((date_start))';
$tags[] = '((date_end))';
$tags[] = '((session_name))';

$tagsHelp = '<strong>'.get_lang('Tags').'</strong>'
    .'<pre>'.implode("\n", $tags).'</pre>';

$fileHelpText = get_lang('ImportCSVFileLocation').'<br>'
    .Display::url(
        get_lang('ExampleCSVFile'),
        'importCourseEventInSessionExample.csv',
        [
            'target' => '_blank',
            'download' => 'importCourseEventInSessionExample.csv',
        ]
    )
    .'<pre>StartDate;EndDate<br>YYYY-MM-DD HH:ii:ss;YYYY-MM-DD HH:ii:ss</pre>';

$form = new FormValidator('agenda_reminders', 'post', $selfUrl);
$form->addHeader(get_lang('CsvImport'));
$form->addFile(
    'events_file',
    [get_lang('ImportAsCSV'), $fileHelpText],
    ['accept' => 'text/csv']
);
$form->addRule('events_file', get_lang('ThisFieldIsRequired'), 'required');
$form->addRule('events_file', get_lang('InvalidExtension'), 'filetype', ['csv']);
$form->addHeader(get_lang('AddEventInCourseCalendar'));
$form->addText(
    'title',
    [get_lang('ItemTitle'), get_lang('TagsCanBeUsed')],
    true,
    ['cols-size' => [2, 7, 3]]
);
$form->applyFilter('title', 'trim');
$form->addHtmlEditor(
    'description',
    [get_lang('Description'), null, $tagsHelp],
    true,
    false,
    ['ToolbarSet' => 'Minimal', 'cols-size' => [2, 7, 3]]
);
//$form->applyFilter('description', 'html_filter_teacher');

if ($isAgendaRemindersEnabled) {
    $form->addHeader(get_lang('NotifyBeforeTheEventStarts'));
    $form->addHtml('<div id="notification_list"></div>');
    $form->addButton('add_notification', get_lang('AddNotification'), 'bell-o')->setType('button');
}

$form->addHtml('<hr>');
$form->addButtonImport(get_lang('Import'));

if ($form->validate()) {
    $values = $form->exportValues();
    $uploadInfo = pathinfo($_FILES['events_file']['name']);
    $notificationCount = $_POST['notification_count'] ?? [];
    $notificationPeriod = $_POST['notification_period'] ?? [];
    $session = api_get_session_entity($sessionId);

    $reminders = $notificationCount ? array_map(null, $notificationCount, $notificationPeriod) : [];

    if ('csv' !== $uploadInfo['extension']) {
        Display::addFlash(
            Display::return_message(get_lang('NotCSV'), 'error')
        );

        header('Location: '.api_get_self());
        exit;
    }

    $csvEvents = Import::csvToArray($_FILES['events_file']['tmp_name']);

    if (empty($csvEvents)) {
        exit;
    }

    $agenda = new Agenda('personal');

    $grouppedData = [];

    $studentList = SessionManager::get_users_by_session($sessionId, Session::STUDENT);

    foreach ($csvEvents as $csvEvent) {
        $hashDate = base64_encode($csvEvent['StartDate'].'||'.$csvEvent['EndDate']);

        foreach ($studentList as $studentInfo) {
            $grouppedData[$hashDate][] = $studentInfo['user_id'];
        }
    }

    foreach ($grouppedData as $hashDate => $userIdList) {
        $dateRange = base64_decode($hashDate);
        list($dateStart, $dateEnd) = explode('||', $dateRange);

        $dateStart = api_get_utc_datetime($dateStart);
        $dateEnd = api_get_utc_datetime($dateEnd);

        $strDateStart = api_format_date($dateStart, DATE_TIME_FORMAT_LONG_24H);
        $strDateEnd = api_format_date($dateEnd, DATE_TIME_FORMAT_LONG_24H);

        foreach ($userIdList as $userId) {
            $title = AnnouncementManager::parseContent($userId, $values['title'], '', $sessionId);
            $content = AnnouncementManager::parseContent($userId, $values['description'], '', $sessionId);

            $title = str_replace(['((date_start))', '((date_end))', '((session_name))'], [$strDateStart, $strDateEnd, $session->getName()], $title);
            $content = str_replace(['((date_start))', '((date_end))', '((session_name))'], [$strDateStart, $strDateEnd, $session->getName()], $content);

            $eventId = Database::insert(
                $tblPersonalAgenda,
                [
                    'user' => $userId,
                    'title' => $title,
                    'text' => $content,
                    'date' => $dateStart,
                    'enddate' => $dateEnd,
                    'all_day' => 0,
                    'color' => '',
                ]
            );

            if ($isAgendaRemindersEnabled) {
                foreach ($reminders as $reminder) {
                    $agenda->addReminder($eventId, $reminder[0], $reminder[1]);
                }
            }
        }
    }

    Display::addFlash(
        Display::return_message(get_lang('FileImported'), 'success')
    );

    header("Location: $selfUrl");
    exit;
}

$form->setDefaults(
    [
        'title' => get_lang('ImportSessionAgendaReminderTitleDefault'),
        'description' => get_lang('ImportSessionAgendaReminderDescriptionDefault'),
    ]
);

$htmlHeadXtra[] = '<script>$(function () {'
    .Agenda::getJsForReminders('#agenda_reminders_add_notification')
    .'});</script>';

$pageTitle = get_lang('ImportCourseEvents');

$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('PlatformAdmin')];
$interbreadcrumb[] = [
    'url' => 'session_list.php',
    'name' => get_lang('SessionList'),
];
$interbreadcrumb[] = [
    'url' => $sessionUrl,
    'name' => get_lang('SessionOverview'),
];

$template = new Template($pageTitle);
$template->assign('header', $pageTitle);
$template->assign('content', $form->returnForm());
$template->display_one_col_template();
