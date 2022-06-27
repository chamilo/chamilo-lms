<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();
$isDrhOfCourse = CourseManager::isUserSubscribedInCourseAsDrh(
    api_get_user_id(),
    api_get_course_info()
);

if (!$isDrhOfCourse) {
    GradebookUtils::block_students();
}
$interbreadcrumb[] = [
    'url' => Category::getUrl(),
    'name' => get_lang('Gradebook'),
];

$categoryId = isset($_GET['selectcat']) ? (int) $_GET['selectcat'] : 0;
$evaluationId = isset($_GET['selecteval']) ? (int) $_GET['selecteval'] : 0;

$category = Category::load($categoryId);
$userId = Security::remove_XSS($_GET['userid']);
$allevals = $category[0]->get_evaluations($userId, true);
$alllinks = $category[0]->get_links($userId, true);

if (!empty($categoryId)) {
    $addparams = [
        'userid' => $userId,
        'selectcat' => $categoryId,
    ];
} else {
    $addparams = [
        'userid' => $userId,
        'selecteval' => $evaluationId,
    ];
}

$userTable = new UserTable($userId, $allevals, $alllinks, $addparams);

if (isset($_GET['exportpdf'])) {
    $datagen = new UserDataGenerator($userId, $allevals, $alllinks);
    $data_array = $datagen->get_data(
        UserDataGenerator::UDG_SORT_NAME,
        0,
        null,
        true
    );
    $newarray = [];
    $displayscore = ScoreDisplay::instance();
    foreach ($data_array as $data) {
        $newarray[] = array_slice($data, 1);
    }
    $userInfo = api_get_user_info($userId);
    $html = get_lang('Results').' : '.$userInfo['complete_name_with_username'].' ('.api_get_local_time().')';

    if ($displayscore->is_custom()) {
        $header_names = [
            get_lang('Evaluation'),
            get_lang('Course'),
            get_lang('Category'),
            get_lang('EvaluationAverage'),
            get_lang('Result'),
            get_lang('Display'),
        ];
    } else {
        $header_names = [
            get_lang('Evaluation'),
            get_lang('Course'),
            get_lang('Category'),
            get_lang('EvaluationAverage'),
            get_lang('Result'),
        ];
    }

    $table = new HTML_Table(['class' => 'table table-hover table-striped data_table']);
    $row = 0;
    $column = 0;
    foreach ($header_names as $item) {
        $table->setHeaderContents($row, $column, $item);
        $column++;
    }
    $row = 1;
    if (!empty($newarray)) {
        foreach ($newarray as $data) {
            $column = 0;
            $table->setCellContents($row, $column, $data);
            $table->updateCellAttributes($row, $column, 'align="center"');
            $column++;
            $row++;
        }
    }
    $html .= $table->toHtml();
    $pdf = new PDF();
    $pdf->content_to_pdf($html);
    exit;
}

$actions = '<div class="actions">';
if (!empty($categoryId)) {
    $interbreadcrumb[] = [
        'url' => 'gradebook_flatview.php?selectcat='.$categoryId.'&'.api_get_cidreq(),
        'name' => get_lang('FlatView'),
    ];
    $actions .= '<a href=gradebook_flatview.php?selectcat='.$categoryId.'&'.api_get_cidreq().'>'.
        Display::return_icon(
            'back.png',
            get_lang('BackTo').' '.get_lang('FlatView'),
            '',
            ICON_SIZE_MEDIUM
        ).
        '</a>';
}

if (!empty($evaluationId)) {
    $interbreadcrumb[] = [
        'url' => 'gradebook_view_result.php?selecteval='.$evaluationId.'&'.api_get_cidreq(),
        'name' => get_lang('ViewResult'),
    ];
    $actions .= '<a href="gradebook_view_result.php?selecteval='.$evaluationId.'&'.api_get_cidreq().'">
	'.Display::return_icon('back.png', get_lang('BackToEvaluation'), '', ICON_SIZE_MEDIUM).'</a>';
}

$actions .= '<a href="'.api_get_self().'?exportpdf=&userid='.$userId.'&selectcat='.$category[0]->get_id().'&'.api_get_cidreq().'" target="_blank">
'.Display::return_icon('pdf.png', get_lang('ExportPDF'), '', ICON_SIZE_MEDIUM).'</a>';

$actions .= '</div>';

Display::display_header(get_lang('ResultsPerUser'));
echo $actions;
DisplayGradebook::display_header_user($_GET['userid'], $category[0]->get_id());
$userTable->display();
Display::display_footer();
