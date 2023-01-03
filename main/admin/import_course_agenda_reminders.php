<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';

api_protect_admin_script();

$isAgendaRemindersEnabled = api_get_configuration_value('agenda_reminders');

if (!$isAgendaRemindersEnabled) {
    api_not_allowed(true);
}

$tblPersonalAgenda = Database::get_main_table(TABLE_PERSONAL_AGENDA);

$tags = AnnouncementManager::getTags();
$tags[] = '((date_start))';
$tags[] = '((date_end))';

$tagsHelp = '<strong>'.get_lang('Tags').'</strong>'
    .'<pre>'.implode("\n", $tags).'</pre>';

$form = new FormValidator('agenda_reminders');
$form->addHeader(get_lang('CsvImport'));
$form->addFile(
    'events_file',
    [get_lang('ImportAsCSV'), get_lang('ImportCSVFileLocation')],
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

    foreach ($csvEvents as $csvEvent) {
        $hashDate = base64_encode($csvEvent['StartDate'].'||'.$csvEvent['EndDate']);

        $courseInfo = api_get_course_info($csvEvent['CourseCode']);

        if (!$courseInfo) {
            continue;
        }

        $userInfo = api_get_user_info_from_username($csvEvent['UserName']);

        if (!$userInfo) {
            continue;
        }

        $grouppedData[$courseInfo['code']][$hashDate][] = $userInfo['id'];
    }

    foreach ($grouppedData as $courseCode => $eventInfo) {
        foreach ($eventInfo as $hashDate => $userIdList) {
            $dateRange = base64_decode($hashDate);
            list($dateStart, $dateEnd) = explode('||', $dateRange);

            $dateStart = api_get_utc_datetime($dateStart);
            $dateEnd = api_get_utc_datetime($dateEnd);

            $strDateStart = api_format_date($dateStart, DATE_TIME_FORMAT_LONG_24H);
            $strDateEnd = api_format_date($dateEnd, DATE_TIME_FORMAT_LONG_24H);

            foreach ($userIdList as $userId) {
                $title = AnnouncementManager::parseContent($userId, $values['title'], $courseCode);
                $content = AnnouncementManager::parseContent($userId, $values['description'], $courseCode);

                $title = str_replace(['((date_start))', '((date_end))'], [$strDateStart, $strDateEnd], $title);
                $content = str_replace(['((date_start))', '((date_end))'], [$strDateStart, $strDateEnd], $content);

                $attributes = [
                    'user' => $userId,
                    'title' => $title,
                    'text' => $content,
                    'date' => $dateStart,
                    'enddate' => $dateEnd,
                    'all_day' => 0,
                    'color' => '',
                ];

                $eventId = Database::insert($tblPersonalAgenda, $attributes);

                if ($isAgendaRemindersEnabled) {
                    foreach ($reminders as $reminder) {
                        $agenda->addReminder($eventId, $reminder[0], $reminder[1]);
                    }
                }
            }
        }
    }

    Display::addFlash(
        Display::return_message(get_lang('FileImported'), 'success')
    );

    header('Location: '.api_get_self());
    exit;
}

$form->setDefaults(
    [
        'title' => get_lang('ImportCourseAgendaReminderTitleDefault'),
        'description' => get_lang('ImportCourseAgendaReminderDescriptionDefault'),
    ]
);

$htmlHeadXtra[] = '<script>$(function () {'
    .Agenda::getJsForReminders('#agenda_reminders_add_notification')
    .'});</script>';

$pageTitle = get_lang('ImportCourseEvents');

$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('PlatformAdmin')];

$template = new Template($pageTitle);
$template->assign('header', $pageTitle);
$template->assign('content', $form->returnForm());
$template->display_one_col_template();
