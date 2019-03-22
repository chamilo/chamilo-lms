<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../../main/inc/global.inc.php';

api_protect_teacher_script();
api_protect_course_script();

$exerciseId = isset($_REQUEST['exercise']) ? (int) $_REQUEST['exercise'] : 0;

if (empty($exerciseId)) {
    echo Display::return_message(get_lang('NotAllowed'), 'error');

    exit;
}

$exercise = new Exercise();

if (!$exercise->read($exerciseId)) {
    echo Display::return_message(get_lang('ExerciseNotFound'), 'error');

    exit;
}

$plugin = QuestionOptionsEvaluationPlugin::create();

if ($plugin->get('enable') !== 'true') {
    echo Display::return_message(get_lang('NotAllowed'), 'error');

    exit;
}

$form = new FormValidator('evaluation');

$form->addRadio(
    'formula',
    $plugin->get_lang('EvaluationFormula'),
    [
        $plugin->get_lang('NoFormula'),
        $plugin->get_lang('Formula1'),
        $plugin->get_lang('Formula2'),
        $plugin->get_lang('Formula3'),
    ]
)->setColumnsSize([4, 7, 1]);
$form->addButtonSave(get_lang('Save'))->setColumnsSize([4, 7, 1]);
$form->addHidden('exercise', $exerciseId);

if ($form->validate()) {
    $values = $form->exportValues();
    $formula = isset($values['formula']) ? (int) $values['formula'] : 0;

    $nbrQuestions = count($exercise->questionList);

    foreach ($exercise->questionList as $questionId) {
        $question = Question::read($questionId);

        if (!in_array($question->selectType(), [UNIQUE_ANSWER, MULTIPLE_ANSWER])) {
            continue;
        }

        $questionAnswers = new Answer($questionId, 0, $exercise);
        $counts = array_count_values($questionAnswers->correct);
        $weighting = [];
        foreach ($questionAnswers->correct as $i => $correct) {
            // Success
            if (1 == $correct) {
                $weighting[$i] = 10 / $counts[1] / $nbrQuestions;

                continue;
            }

            // failures
            switch ($formula) {
                case 0:
                default:
                    $weighting[$i] = isset($questionAnswers->weighting[$i]) ? $questionAnswers->weighting[$i] : 0;
                    break;
                case 1:
                    $weighting[$i] = (-10 / $counts[0]) / $nbrQuestions;
                    break;
                case 2:
                    $weighting[$i] = (-10 / $counts[0]) / 2 / $nbrQuestions;
                    break;
                case 3:
                    $weighting[$i] = (-10 / $counts[0]) / 3 / $nbrQuestions;
                    break;
            }
        }

        $weighting = array_map(
            function ($weight) {
                return float_format($weight);
            },
            $weighting
        );

        $questionAnswers->new_nbrAnswers = $questionAnswers->nbrAnswers;
        $questionAnswers->new_answer = $questionAnswers->answer;
        $questionAnswers->new_comment = $questionAnswers->comment;
        $questionAnswers->new_correct = $questionAnswers->correct;
        $questionAnswers->new_weighting = $weighting;
        $questionAnswers->new_position = $questionAnswers->position;
        $questionAnswers->new_destination = $questionAnswers->destination;
        $questionAnswers->new_hotspot_coordinates = $questionAnswers->hotspot_coordinates;
        $questionAnswers->new_hotspot_type = $questionAnswers->hotspot_type;

        $allowedWeights = array_filter(
            $weighting,
            function ($weight) {
                return $weight > 0;
            }
        );

        $questionAnswers->save();
        $question->updateWeighting(array_sum($allowedWeights));
        $question->save($exercise);
    }

    Display::addFlash(
        Display::return_message($plugin->get_lang('QuestionsEvaluated'))
    );

    header(
        'Location: '.api_get_path(WEB_CODE_PATH).'exercise/exercise.php?'.api_get_cidreq()."&exerciseId=$exerciseId"
    );
    exit;
}

echo Display::return_message(
    $plugin->get_lang('QuizQuestionsScoreRulesTitleConfirm'),
    'warning'
);

$form->display();
