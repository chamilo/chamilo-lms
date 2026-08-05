<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../../main/inc/global.inc.php';

api_protect_teacher_script();
api_protect_course_script();

$exerciseId = isset($_REQUEST['exercise']) ? (int) $_REQUEST['exercise'] : 0;

if ($exerciseId <= 0) {
    echo Display::return_message(get_lang('You are not allowed to see this page. Either your connection has expired or you are trying to access a page for which you do not have the sufficient privileges.'), 'error');

    exit;
}

$exercise = new Exercise();

if (!$exercise->read($exerciseId, false)) {
    echo Display::return_message(get_lang('Test not found or not visible'), 'error');

    exit;
}

$plugin = QuestionOptionsEvaluationPlugin::create();

if (!$plugin->isEnabled()) {
    echo Display::return_message(get_lang('You are not allowed to see this page. Either your connection has expired or you are trying to access a page for which you do not have the sufficient privileges.'), 'error');

    exit;
}

$charset = api_get_system_encoding();
$escape = static function ($value) use ($charset): string {
    return api_htmlentities((string) $value, ENT_QUOTES, $charset);
};

$formulaOptions = [
    QuestionOptionsEvaluationPlugin::FORMULA_NONE => $plugin->get_lang('NoFormula'),
    QuestionOptionsEvaluationPlugin::FORMULA_RECALCULATE => $plugin->get_lang('RecalculateQuestionScores'),
    QuestionOptionsEvaluationPlugin::FORMULA_SUCCESS_MINUS_FAILURES => $plugin->get_lang('Formula1'),
    QuestionOptionsEvaluationPlugin::FORMULA_SUCCESS_MINUS_HALF_FAILURES => $plugin->get_lang('Formula2'),
    QuestionOptionsEvaluationPlugin::FORMULA_SUCCESS_MINUS_THIRD_FAILURES => $plugin->get_lang('Formula3'),
];

$selectedFormula = $plugin->getStoredFormulaForExercise($exerciseId);
$successMessage = '';
$errorMessage = '';

if ('POST' === $_SERVER['REQUEST_METHOD']) {
    if (!Security::check_token('post')) {
        $errorMessage = get_lang('Invalid token');
    } else {
        $postedFormula = isset($_POST['formula']) ? (int) $_POST['formula'] : QuestionOptionsEvaluationPlugin::FORMULA_NONE;
        if (!array_key_exists($postedFormula, $formulaOptions)) {
            $postedFormula = QuestionOptionsEvaluationPlugin::FORMULA_NONE;
        }

        $exercise->read($exerciseId, true);
        $plugin->saveFormulaForExercise($postedFormula, $exercise);
        $selectedFormula = $postedFormula;
        $successMessage = sprintf(
            $plugin->get_lang('FormulaSavedForExerciseX'),
            $exercise->selectTitle(true)
        );

        Security::clear_token();
    }
}

$securityToken = Security::get_token();
$exerciseTitle = $exercise->selectTitle(true);
$formAction = api_get_path(WEB_PLUGIN_PATH).'QuestionOptionsEvaluation/evaluation.php?exercise='.$exerciseId;
$courseRequest = api_get_cidreq();

if (!empty($courseRequest)) {
    $formAction .= '&'.$courseRequest;
}

echo '<div id="qoe-root" class="ch space-y-4 p-1 text-gray-90">';

if ('' !== $successMessage) {
    echo '<div class="rounded-xl border border-success bg-success/10 px-4 py-3 text-sm text-gray-90">';
    echo '<div class="font-semibold text-success">'.$escape(get_lang('Saved')).'</div>';
    echo '<div class="mt-1">'.$escape($successMessage).'</div>';
    echo '</div>';
}

if ('' !== $errorMessage) {
    echo '<div class="rounded-xl border border-danger bg-danger/10 px-4 py-3 text-sm text-gray-90">';
    echo '<div class="font-semibold text-danger">'.$escape(get_lang('Error')).'</div>';
    echo '<div class="mt-1">'.$escape($errorMessage).'</div>';
    echo '</div>';
}

echo '<div class="rounded-2xl border border-gray-25 bg-white shadow-sm">';
echo '<div class="border-b border-gray-25 px-5 py-4">';
echo '<p class="text-tiny font-semibold uppercase tracking-wide text-primary">'.$escape($plugin->get_lang('plugin_title')).'</p>';
echo '<h2 class="mt-1 text-lg font-semibold text-gray-90">'.$escape($exerciseTitle).'</h2>';
echo '</div>';

$ajaxSubmit = "var f=this;var root=document.getElementById('qoe-root');var status=document.getElementById('qoe-inline-status');if(status){status.textContent='Saving...';}fetch(f.action,{method:'POST',body:new FormData(f),credentials:'same-origin'}).then(function(response){return response.text();}).then(function(html){var doc=new DOMParser().parseFromString(html,'text/html');var next=doc.getElementById('qoe-root');if(root&&next){root.outerHTML=next.outerHTML;return;}if(status){status.textContent='Saved';}}).catch(function(){if(status){status.textContent='Unable to save. Please try again.';}});return false;";

echo '<form method="post" action="'.$escape($formAction).'" onsubmit="'.$escape($ajaxSubmit).'" class="space-y-4 px-5 py-5">';
echo '<input type="hidden" name="exercise" value="'.$exerciseId.'">';
echo '<input type="hidden" name="qoe_save" value="1">';
echo '<input type="hidden" name="sec_token" value="'.$escape($securityToken).'">';

echo '<div class="rounded-xl border border-warning bg-warning/10 px-4 py-3 text-sm leading-6 text-gray-90">';
echo $escape($plugin->get_lang('QuizQuestionsScoreRulesTitleConfirm'));
echo '</div>';

echo '<fieldset class="space-y-3">';
echo '<legend class="text-sm font-semibold text-gray-90">'.$escape($plugin->get_lang('EvaluationFormula')).'</legend>';

foreach ($formulaOptions as $formulaValue => $formulaLabel) {
    $formulaValue = (int) $formulaValue;
    $checked = $formulaValue === (int) $selectedFormula ? ' checked="checked"' : '';
    $selectedClasses = $checked
        ? 'border-primary bg-primary/5 shadow-sm'
        : 'border-gray-25 bg-white hover:border-primary hover:bg-support-2';

    echo '<label class="flex cursor-pointer items-start gap-3 rounded-xl border '.$selectedClasses.' p-4 transition">';
    echo '<input type="radio" name="formula" value="'.$formulaValue.'" class="mt-1 h-4 w-4 border-gray-25 text-primary focus:ring-primary"'.$checked.'>';
    echo '<span class="block">';
    echo '<span class="block text-sm font-semibold text-gray-90">'.$escape($formulaLabel).'</span>';
    echo '</span>';
    echo '</label>';
}

echo '</fieldset>';

echo '<div id="qoe-inline-status" class="min-h-5 text-sm font-semibold text-success" aria-live="polite"></div>';

echo '<div class="flex items-center justify-end gap-3 border-t border-gray-25 pt-4">';
echo '<button type="submit" name="submit" value="1" class="inline-flex items-center justify-center rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-button-text shadow-sm transition hover:bg-primary-gradient focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">';
echo $escape(get_lang('Save'));
echo '</button>';
echo '</div>';

echo '</form>';
echo '</div>';
echo '</div>';
