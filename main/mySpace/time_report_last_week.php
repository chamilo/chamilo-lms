<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';
api_block_anonymous_users(true);
if (api_is_student()) {
    api_not_allowed(true);
}

$studentId = isset($_GET['student']) ? (int) $_GET['student'] : 0;
if (empty($studentId)) {
    api_not_allowed(true);
}

$start = isset($_GET['start']) ? $_GET['start'] : '';
$end = isset($_GET['end']) ? $_GET['end'] : '';

if (empty($start) || empty($end)) {
    $aLastWeek = get_last_week();
    $start = date('Y-m-d', $aLastWeek[0]);
    $end = date('Y-m-d', $aLastWeek[6]);
}

$report = Tracking::generateReport('time_report', [$studentId], $start, $end);
$rows = [];
if (!empty($report)) {
    $rows = $report['rows'];
    array_unshift($rows, $report['headers']);
}

$export = isset($_GET['export']) ? $_GET['export'] : '';
if ($export === 'xls') {
    Export::arrayToXls($rows, 'time_report');
    exit;
} elseif ($export === 'pdf') {
    $params = ['filename' => 'time_report'];
    Export::export_table_pdf($rows, $params);
    exit;
}

$table = '';
if (!empty($rows)) {
    $headers = array_shift($rows);
    $colors = ['#cce5ff', '#ffe5b4'];
    $colorIndex = 0;

    // Group entries by starting date (column index 4)
    $grouped = [];
    foreach ($rows as $row) {
        $dateKey = substr($row[4], 0, 10);
        $grouped[$dateKey][] = $row;
    }

    $table = '<table class="data_table" style="border-collapse:collapse;border:1px solid #ccc;">';
    $table .= '<tr>';
    foreach ($headers as $header) {
        $table .= '<th style="border:1px solid #ccc;padding-left:5px;">'.htmlspecialchars($header).'</th>';
    }
    $table .= '</tr>';

    foreach ($grouped as $day => $dayRows) {
        $totalSeconds = 0;
        foreach ($dayRows as $row) {
            $table .= '<tr style="background-color:'.$colors[$colorIndex].'">';
            foreach ($row as $cell) {
                $table .= '<td style="border:1px solid #ccc;padding-left:5px;">'.htmlspecialchars($cell).'</td>';
            }
            $table .= '</tr>';

            $parts = explode(':', $row[6]);
            if (count($parts) === 3) {
                $totalSeconds += ($parts[0] * 3600) + ($parts[1] * 60) + $parts[2];
            }
        }

        $table .= '<tr style="background-color:'.$colors[$colorIndex].';font-weight:bold;">'
            .'<td colspan="'.(count($headers) - 1).'" style="text-align:right;border:1px solid #ccc;padding-left:5px;">'.get_lang('Total').'</td>'
            .'<td style="border:1px solid #ccc;padding-left:5px;">'.gmdate('H:i:s', $totalSeconds).'</td>'
            .'</tr>';

        $colorIndex = 1 - $colorIndex;
    }

    $table .= '</table>';
}

$nameTools = get_lang('TimeReport');
Display::display_header($nameTools);
$baseUrl = api_get_self().'?student='.$studentId.'&start='.$start.'&end='.$end;
echo '<div>'
    .'<a href="'.$baseUrl.'&export=pdf">'
    .Display::return_icon('pdf.png', get_lang('ExportPDF'), [], ICON_SIZE_MEDIUM)
    .'</a> '
    .'<a href="'.$baseUrl.'&export=xls">'
    .Display::return_icon('export_excel.png', get_lang('ExportAsXLS'), [], ICON_SIZE_MEDIUM)
    .'</a></div>';
echo $table;
Display::display_footer();