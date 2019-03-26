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

$formRecalculate = new FormValidator('recalculate');
$formRecalculate->addHidden('exercise', $exerciseId);
$formRecalculate->addButtonUpdate($plugin->get_lang('RecalculateQuestionScores'));

if ($formRecalculate->validate()) {
    $plugin->recalculateQuestionScore($exercise);

    Display::addFlash(
        Display::return_message($plugin->get_lang('QuestionsEvaluated'), 'success')
    );

    header(
        'Location: '.api_get_path(WEB_CODE_PATH).'exercise/exercise.php?'.api_get_cidreq()."&exerciseId=$exerciseId"
    );
    exit;
}

$formEvaluation = new FormValidator('evaluation');
$formEvaluation
    ->addRadio(
        'formula',
        $plugin->get_lang('EvaluationFormula'),
        [
            $plugin->get_lang('NoFormula'),
            $plugin->get_lang('Formula1'),
            $plugin->get_lang('Formula2'),
            $plugin->get_lang('Formula3'),
        ]
    )
    ->setColumnsSize([4, 7, 1]);
$formEvaluation->addButtonSave(get_lang('Save'))->setColumnsSize([4, 7, 1]);
$formEvaluation->addHidden('exercise', $exerciseId);

if ($formEvaluation->validate()) {
    $values = $formEvaluation->exportValues();
    $formula = isset($values['formula']) ? (int) $values['formula'] : 0;

    $plugin->saveFormulaForExercise($formula, $exercise);

    Display::addFlash(
        Display::return_message($plugin->get_lang('FormulaSaved'), 'success')
    );

    header(
        'Location: '.api_get_path(WEB_CODE_PATH).'exercise/exercise.php?'.api_get_cidreq()."&exerciseId=$exerciseId"
    );
    exit;
}

$formEvaluation->setDefaults(['formula' => $plugin->getFormulaForExercise($exercise->iId)]);

echo Display::return_message(
    $plugin->get_lang('QuizQuestionsScoreRulesTitleConfirm'),
    'warning'
);
echo '<hr>';
$formRecalculate->display();
echo '<hr>';
$formEvaluation->display();
