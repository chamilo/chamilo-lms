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
$table = $add_result_form->toHtml();
if ($add_result_form->validate()) {
    $values = $add_result_form->exportValues();
    $nr_users = $values['nr_users'];
    if ($nr_users == '0') {
        Display::addFlash(Display::return_message(get_lang('AddResultNoStudents'), 'warning', false));
        header('Location: gradebook_view_result.php?addresultnostudents=&selecteval='.$selectEval.'&'.api_get_cidreq());
        exit;
    }

    $scores = $values['score'];
    $sumResult = 0;
    $bestResult = 0;
    $studentScoreList = [];
    foreach ($scores as $userId => $row) {
        $res = new Result();
        $res->set_evaluation_id($values['evaluation_id']);
        $res->set_user_id(key($scores));
        //if no scores are given, don't set the score
        if (!empty($row) || $row == '0') {
            $res->set_score($row);
        }

        // To prevent error editing when going back in the browser,
        // check if a record exists for this user and evaluation_id
        if ($res->exists()) {
            $res->addResultLog($userId, $values['evaluation_id']);
            $res->save();
        } else {
            $res->add();
        }

        next($scores);
    }

    Evaluation::generateStats($values['evaluation_id']);

    Display::addFlash(Display::return_message(get_lang('ResultAdded'), 'confirmation', false));
    header('Location: gradebook_view_result.php?addresult=&selecteval='.$selectEval.'&'.api_get_cidreq());
    exit;
}
$interbreadcrumb[] = [
    'url' => Category::getUrl(),
    'name' => get_lang('Gradebook'),
];
Display::display_header(get_lang('AddResult'));
DisplayGradebook::display_header_result($evaluation[0], null, 0, 0);
echo $table;
Display::display_footer();
