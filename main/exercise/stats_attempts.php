<?php
/* See license terms in /license.txt */

require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_COURSES;

api_protect_course_script(true, false, true);

$showPage = false;
if (api_is_platform_admin() || api_is_course_admin() ||
    api_is_course_tutor() || api_is_session_general_coach() || api_is_allowed_to_edit(null, true)
) {
    $showPage = true;
}

if (!$showPage) {
    api_not_allowed(true);
}

$exportXls = isset($_GET['export_xls']) && !empty($_GET['export_xls']) ? (int) $_GET['export_xls'] : 0;
$exerciseId = isset($_GET['exerciseId']) && !empty($_GET['exerciseId']) ? (int) $_GET['exerciseId'] : 0;
$objExercise = new Exercise();
$result = $objExercise->read($exerciseId);

if (!$result) {
    api_not_allowed(true);
}

$stats = ExerciseLib::getTrackExerciseAttemptsTable($objExercise);

$table = new HTML_Table(['class' => 'table table-hover table-striped data_table']);
$row = 0;
$column = 0;
foreach ($stats['headers'] as $header) {
    $table->setHeaderContents($row, $column, $header);
    $column++;
}
if ($exportXls) {
    $tableXls[] = $stats['headers_xls'];
}
$row++;
foreach ($stats['rows'] as $rowTable) {
    $column = 0;
    foreach ($rowTable as $key => $cell) {
        $table->setCellContents($row, $column, $cell);
        $table->updateCellAttributes($row, $column, 'align="center"');
        if ($exportXls) {
            $rowTable[$key] = strip_tags($cell);
        }
        $column++;
    }
    $table->updateRowAttributes($row, $row % 2 ? 'class="row_even"' : 'class="row_odd"', true);
    if ($exportXls) {
        $tableXls[] = $rowTable;
    }
    $row++;
}

$nameTools = get_lang('Results').': '.$objExercise->selectTitle(true);
$content = '<h2 class="page-header">'.$nameTools.'</h2>';
$content .= '<div style="width: 100%;overflow: auto;">';
$content .= $table->toHtml();
$content .= '</div>';

if ($exportXls) {
    $fileName = get_lang('Report').'_'.api_get_course_id().'_'.api_get_local_time();
    $tableXls = array_merge($tableXls);
    Export::arrayToXls($tableXls, $fileName);
    exit;
}

$interbreadcrumb[] = [
    "url" => "exercise.php?".api_get_cidreq(),
    "name" => get_lang('Exercises'),
];
$interbreadcrumb[] = [
    'url' => "admin.php?exerciseId=$exerciseId&".api_get_cidreq(),
    'name' => $objExercise->selectTitle(true),
];

$tpl = new Template(get_lang('ReportByAttempts'));
$actions = '<a href="exercise_report.php?exerciseId='.$exerciseId.'&'.api_get_cidreq().'">'.
    Display::return_icon(
        'back.png',
        get_lang('GoBackToQuestionList'),
        '',
        ICON_SIZE_MEDIUM
    )
    .'</a>';
$actions .= Display::url(
    Display::return_icon('excel.png', get_lang('ExportAsXLS'), [], ICON_SIZE_MEDIUM),
    'stats_attempts.php?exerciseId='.$exerciseId.'&export_xls=1&'.api_get_cidreq()
);

$actions = Display::div($actions, ['class' => 'actions']);
$content = $actions.$content;
$tpl->assign('content', $content);

$tpl->display_one_col_template();
