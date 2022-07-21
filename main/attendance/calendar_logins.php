<?php
/* For licensing terms, see /license.txt */

// See AttendanceController::calendarLogins function
$actionsLeft = '<a href="index.php?'.api_get_cidreq().'&action=calendar_list">'.
Display::return_icon('back.png', get_lang('AttendanceCalendar'), '', ICON_SIZE_MEDIUM).'</a>';

$actionsRight = '<a
    href="'.api_get_self().'?'.api_get_cidreq().'&action=calendar_logins&format=pdf">
    '.Display::return_icon('export_pdf.png', get_lang('ExportAsPDF'), '', ICON_SIZE_MEDIUM).'</a>';
$actionsRight .= '<a
    href="'.api_get_self().'?'.api_get_cidreq().'&action=calendar_logins&format=csv">
    '.Display::return_icon('export_csv.png', get_lang('ExportAsCSV'), '', ICON_SIZE_MEDIUM).'</a>';
$actionsRight .= '<a
    href="'.api_get_self().'?'.api_get_cidreq().'&action=calendar_logins&format=xls">
    '.Display::return_icon('export_excel.png', get_lang('ExportAsXLS'), '', ICON_SIZE_MEDIUM).'</a>';

echo Display::toolbarAction(
    'toolbar-attendance',
    [$actionsLeft, '', $actionsRight],
    [4, 6, 2]
);

echo $form;
echo $table;
