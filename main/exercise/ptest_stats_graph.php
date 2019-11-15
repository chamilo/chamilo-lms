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

$exerciseId = isset($_GET['exerciseId']) && !empty($_GET['exerciseId']) ? (int) $_GET['exerciseId'] : 0;
$objExercise = new Exercise();
$result = $objExercise->read($exerciseId);

if (!$result) {
    api_not_allowed(true);
}

$sessionId = api_get_session_id();
$courseCode = api_get_course_id();

if (empty($sessionId)) {
    $students = CourseManager:: get_student_list_from_course_code(
        $courseCode,
        false
    );
} else {
    $students = CourseManager:: get_student_list_from_course_code(
        $courseCode,
        true,
        $sessionId
    );
}
$count_students = count($students);
$question_list = $objExercise->get_validated_question_list();

$cList = PTestCategory::getCategoryListInfo($objExercise->id);

$categoryList = [];
foreach ($cList as $item) {
    $categoryList[$item->id]['label'] = $item->name;
    $categoryList[$item->id]['num'] = 0;
}

if (!empty($question_list)) {
    foreach ($question_list as $question_id) {
        $questionObj = Question::read($question_id, null, null, true);

        $answer = new Answer($question_id);
        $answer_count = $answer->selectNbrAnswers();

        for ($answer_id = 1; $answer_id <= $answer_count; $answer_id++) {
            $real_answer_id = $answer->selectAutoId($answer_id);
            $categoryId = $answer->selectPtCategory($answer_id);

            // Overwriting values depending of the question
            switch ($questionObj->type) {
                case QUESTION_PT_TYPE_CATEGORY_RANKING:
                    $count = ExerciseLib::getNumberStudentsAnswerCountGraph(
                        $real_answer_id,
                        $question_id,
                        $exerciseId,
                        $courseCode,
                        $sessionId,
                        $questionObj->type
                    );
                    $categoryList[$categoryId]['num'] += $count;
                    break;
                case QUESTION_PT_TYPE_AGREE_OR_DISAGREE:
                    $count = ExerciseLib::getNumberStudentsAnswerCountGraph(
                        $real_answer_id,
                        $question_id,
                        $exerciseId,
                        $courseCode,
                        $sessionId,
                        $questionObj->type
                    );
                    $categoryList[$categoryId]['num'] += $count[0];
                    $categoryList[$categoryId]['num'] -= $count[1];
                    break;
                case QUESTION_PT_TYPE_AGREE_SCALE:
                    $count = ExerciseLib::getNumberStudentsAnswerCountGraph(
                        $real_answer_id,
                        $question_id,
                        $exerciseId,
                        $courseCode,
                        $sessionId,
                        $questionObj->type
                    );
                    $categoryList[$categoryId]['num'] += $count;
                    break;
                case QUESTION_PT_TYPE_AGREE_REORDER:
                    $count = ExerciseLib::getNumberStudentsAnswerCountGraph(
                        $real_answer_id,
                        $question_id,
                        $exerciseId,
                        $courseCode,
                        $sessionId,
                        $questionObj->type
                    );
                    $categoryList[$categoryId]['num'] += $count;
                    break;
            }
        }
    }
}

$labels = [];
$num = [];
foreach ($categoryList as $item) {
    $labels[] = $item['label'];
    $data[] = (int) $item['num'];
}

$html = '';
$html .= '<div class="row">';
$html .= '<div class="col-md-6">';
$html .= '<canvas id="myChart"></canvas>';
$html .= '</div>';
$html .= '<div class="col-md-6">';
$html .= '<canvas id="myChart2"></canvas>';
$html .= '</div>';
$html .= '</div><br>';

$html .= '<script>
    var data = {
      labels: '.json_encode($labels).',
      datasets: [
          {
              "label": "'.get_lang('RadarPlotRepresentation').'",
              "fill":true,
              "backgroundColor":"rgba(100, 170, 255, 0.2)",
              "borderColor":"rgb(46, 117, 163)",
              "pointBackgroundColor":"rgb(64, 128, 255)",
              "pointBorderColor":"#fff",
              "pointHoverBackgroundColor":"#fff",
              "pointHoverBorderColor":"rgb(255, 99, 132)",
              "data": '.json_encode($data).'
          }
      ]
    }

    var options = {
        scale: {
            angleLines: {
                display: true
            },
            ticks: {
                suggestedMin: 0,
                suggestedMax: '.max($data).'
            }
        }
    };

    var ctx = new Chart(document.getElementById("myChart"), {
        type: "radar",
        data: data,
        options: options
    });

    var data2 = {
      labels: '.json_encode($labels).',
      datasets: [
          {
              "label": "'.get_lang('BarGraphRepresentation').'",
              "barPercentage": 0.5,
              "barThickness": 6,
              "maxBarThickness": 8,
              "minBarLength": 2,
              "data": '.json_encode($data).'
          }
      ]
    }

    var options2 = {
        scales: {
            "yAxes":[{"ticks":{"beginAtZero":true}}],
            "xAxes":[{"gridLines": {"offsetGridLines": true}}]
        }
    };

    var myBarChart = new Chart(document.getElementById("myChart2"), {
        type: "bar",
        data: data2,
        options: options2
    });
</script>';

$interbreadcrumb[] = [
    "url" => "exercise.php?".api_get_cidreq(),
    "name" => get_lang('Exercises'),
];
$interbreadcrumb[] = [
    "url" => "admin.php?exerciseId=$exerciseId&".api_get_cidreq(),
    "name" => $objExercise->selectTitle(true),
];

$htmlHeadXtra[] = api_get_js('chartjs/Chart.min.js');

$tpl = new Template(get_lang('ReportByQuestion'));
$actions = '<a href="ptest_exercise_report.php?exerciseId='.$exerciseId.'&'.api_get_cidreq().'">'.
    Display:: return_icon(
        'back.png',
        get_lang('GoBackToQuestionList'),
        '',
        ICON_SIZE_MEDIUM
    )
    .'</a>';
$actions = Display::div($actions, ['class' => 'actions']);
$content = $actions.$html;
$tpl->assign('content', $content);
$tpl->display_one_col_template();
