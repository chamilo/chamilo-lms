<?php

/* See license terms in /license.txt */

require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_COURSES;
$exercise_id = isset($_GET['exerciseId']) && !empty($_GET['exerciseId']) ? (int) ($_GET['exerciseId']) : 0;

api_protect_course_script(true);
if (!api_is_allowed_to_edit()) {
    api_not_allowed();
}

$objExercise = new Exercise();
$result = $objExercise->read($exercise_id);

if (!$result) {
    api_not_allowed(true);
}

$interbreadcrumb[] = [
    "url" => "exercise.php?".api_get_cidreq(),
    "name" => get_lang('Exercises'),
];
$interbreadcrumb[] = [
    'url' => "admin.php?exerciseId=$exercise_id&".api_get_cidreq(),
    'name' => $objExercise->selectTitle(true),
];

//Add the JS needed to use the jqgrid
$htmlHeadXtra[] = api_get_jqgrid_js();

// The header.
Display::display_header(get_lang('StudentsWhoAreTakingTheExerciseRightNow'));

//jqgrid will use this URL to do the selects
$minutes = 60;
$url = api_get_path(WEB_AJAX_PATH).
    'exercise.ajax.php?'.api_get_cidreq().'&a=get_live_stats&exercise_id='.$objExercise->iid.'&minutes='.$minutes;

//The order is important you need to check the the $column variable in the model.ajax.php file
$columns = [
    get_lang('FirstName'),
    get_lang('LastName'),
    get_lang('Time'),
    get_lang('QuestionsAlreadyAnswered'),
    get_lang('Score'),
];

//Column config
$column_model = [
    [
        'name' => 'firstname',
        'index' => 'firstname',
        'width' => '100',
        'align' => 'left',
    ],
    [
        'name' => 'lastname',
        'index' => 'lastname',
        'width' => '100',
        'align' => 'left',
    ],
    [
        'name' => 'start_date',
        'index' => 'start_date',
        'width' => '100',
        'align' => 'left',
    ],
    [
        'name' => 'question',
        'index' => 'count_questions',
        'width' => '60',
        'align' => 'left',
        'sortable' => 'false',
    ],
    [
        'name' => 'score',
        'index' => 'score',
        'width' => '50',
        'align' => 'left',
        'sortable' => 'false',
    ],
];
$extra_params['autowidth'] = 'true';
$extra_params['height'] = 'auto';
?>
<script>

function refreshGrid() {
    var grid = $("#live_stats");
    grid.trigger("reloadGrid");
    t = setTimeout("refreshGrid()", 10000);
}

$(function() {
    <?php
    echo Display::grid_js(
        'live_stats',
        $url,
        $columns,
        $column_model,
        $extra_params,
        [],
        null,
        true
    );
    ?>
    refreshGrid();
});
</script>
<?php

$actions = '<a href="exercise_report.php?exerciseId='.$exercise_id.'&'.api_get_cidreq().'">'.
    Display::return_icon('back.png', get_lang('GoBackToQuestionList'), '', ICON_SIZE_MEDIUM).'</a>';
echo $actions = Display::div($actions, ['class' => 'actions']);
echo Display::grid_html('live_stats');
Display::display_footer();
