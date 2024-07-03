<?php
/* For licensing terms, see /license.txt */

/**
 * Script.
 *
 * @package chamilo.gradebook
 */
require_once __DIR__.'/../inc/global.inc.php';
api_block_anonymous_users();
GradebookUtils::block_students();

$select_eval = (int) $_GET['selecteval'];
if (empty($select_eval)) {
    api_not_allowed();
}
$resultedit = Result::load(null, null, $select_eval);
$evaluation = Evaluation::load($select_eval);
$evaluation[0]->check_lock_permissions();
$courseInfo = api_get_course_info();
$sessionId = api_get_session_id();

$edit_result_form = new EvalForm(
    EvalForm::TYPE_ALL_RESULTS_EDIT,
    $evaluation[0],
    $resultedit,
    'edit_result_form',
    null,
    api_get_self().'?selecteval='.$select_eval.'&'.api_get_cidreq()
);
if ($edit_result_form->validate()) {
    $values = $edit_result_form->exportValues();
    $scores = $values['score'];
    $bestResult = 0;
    $scoreFinalList = [];
    foreach ($scores as $userId => $score) {
        /** @var array $resultedit */
        $resultedit = Result::load($userId);
        /** @var Result $result */
        $result = $resultedit[0];

        if (empty($score)) {
            $score = 0;
        }

        $scoreFinalList[$result->get_user_id()] = $score;

        if ($score > $bestResult) {
            $bestResult = $score;
        }
        $score = api_number_format($score, api_get_setting('gradebook_number_decimals'));
        $result->set_score($score);
        $result->save();

        $allowMultipleAttempts = api_get_configuration_value('gradebook_multiple_evaluation_attempts');
        if ($allowMultipleAttempts) {
            $table = Database::get_main_table(TABLE_MAIN_GRADEBOOK_RESULT_ATTEMPT);
            $now = api_get_utc_datetime();
            $params = [
                'result_id' => $result->get_id(),
                'score' => $score,
                'comment' => '',
                'created_at' => $now,
                'updated_at' => $now,
            ];
            Database::insert($table, $params);
        }
    }
    Display::addFlash(Display::return_message(get_lang('AllResultsEdited')));
    header('Location: gradebook_view_result.php?selecteval='.$select_eval.'&'.api_get_cidreq());
    exit;
}

$table = $edit_result_form->toHtml();

$interbreadcrumb[] = [
    'url' => Category::getUrl(),
    'name' => get_lang('Gradebook'),
];
$interbreadcrumb[] = [
    'url' => 'gradebook_view_result.php?selecteval='.$select_eval.'&'.api_get_cidreq(),
    'name' => get_lang('ViewResult'),
];
Display::display_header(get_lang('EditResult'));
DisplayGradebook::display_header_result($evaluation[0], null, 0, 0);
echo $table;
Display::display_footer();
