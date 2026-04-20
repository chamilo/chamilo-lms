<?php

/* For licensing terms, see /license.txt */

//$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';
$current_course_tool = TOOL_GRADEBOOK;

api_protect_course_script(true);
api_block_anonymous_users();
GradebookUtils::block_students();

$selectEval = isset($_GET['selecteval']) ? (int) $_GET['selecteval'] : 0;

$resultadd = new Result();
$resultadd->set_evaluation_id($selectEval);
$evaluation = Evaluation::load($selectEval);
$category = !empty($_GET['selectcat']) ? (int) $_GET['selectcat'] : '';
$add_result_form = new EvalForm(
    EvalForm::TYPE_RESULT_ADD,
    $evaluation[0],
    $resultadd,
    'add_result_form',
    null,
    api_get_self().'?selectcat='.$category.'&selecteval='.$selectEval.'&'.api_get_cidreq()
);

$hasInvalidScores = false;
$maxScore = (float) $evaluation[0]->get_max();

if ('POST' === $_SERVER['REQUEST_METHOD']) {
    $postedValues = $add_result_form->exportValues();

    foreach (($postedValues['score'] ?? []) as $userId => $score) {
        if ($score === '' || null === $score) {
            continue;
        }

        if (!is_numeric($score) || (float) $score < 0 || (float) $score > $maxScore) {
            $hasInvalidScores = true;

            $add_result_form->setElementError(
                'score['.$userId.']',
                'Score must be between 0 and '.$maxScore.'.'
            );
        }
    }

    if ($hasInvalidScores) {
        Display::addFlash(
            Display::return_message(
                'There is at least one invalid score. Please correct the highlighted fields.',
                'warning',
                false
            )
        );
    }
}

$table = $add_result_form->toHtml();

if ($add_result_form->validate() && !$hasInvalidScores) {
    $values = $add_result_form->exportValues();
    $nr_users = $values['nr_users'];

    if ('0' == $nr_users) {
        Display::addFlash(
            Display::return_message(
                get_lang('There are no learners to add results for'),
                'warning',
                false
            )
        );
        header('Location: gradebook_view_result.php?addresultnostudents=&selecteval='.$selectEval.'&'.api_get_cidreq());
        exit;
    }

    $scores = $values['score'];

    foreach ($scores as $userId => $row) {
        $res = new Result();
        $res->set_evaluation_id($values['evaluation_id']);
        $res->set_user_id($userId);

        if (!empty($row) || '0' == $row) {
            $res->set_score($row);
        }

        if ($res->exists()) {
            $res->addResultLog($userId, $values['evaluation_id']);
            $res->save();
        } else {
            $res->add();
        }

        next($scores);
    }

    Evaluation::generateStats($values['evaluation_id']);
    Display::addFlash(Display::return_message(get_lang('Result added'), 'confirmation', false));
    header('Location: gradebook_view_result.php?addresult=&selecteval='.$selectEval.'&'.api_get_cidreq());
    exit;
}
$interbreadcrumb[] = [
    'url' => Category::getUrl(),
    'name' => get_lang('Assessments'),
];
Display::display_header(get_lang('Grade learners'));
DisplayGradebook::display_header_result($evaluation[0], null, 0, 0);
echo '<div class="tw-gradebook-edit-container mx-auto w-full px-4 sm:px-4 lg:px-4">';
echo $table;
echo '</div>';
Display::display_footer();
