<?php
/* For licensing terms, see /license.txt */

/**
 * Script
 * @package chamilo.gradebook
 */
require_once __DIR__.'/../inc/global.inc.php';
api_block_anonymous_users();
GradebookUtils::block_students();
$select_eval = Security::remove_XSS($_GET['selecteval']);
if (empty($select_eval)) {
    api_not_allowed();
}
$resultedit = Result::load(null, null, $select_eval);
$evaluation = Evaluation::load($select_eval);

$evaluation[0]->check_lock_permissions();

$edit_result_form = new EvalForm(
    EvalForm :: TYPE_ALL_RESULTS_EDIT,
    $evaluation[0],
    $resultedit,
    'edit_result_form',
    null,
    api_get_self().'?selecteval='.$select_eval.'&'.api_get_cidreq()
);
if ($edit_result_form->validate()) {
    $values = $edit_result_form->exportValues();
    $scores = $values['score'];
    foreach ($scores as $userId => $score) {
        /** @var array $resultedit */
        $resultedit = Result::load($userId);
        /** @var Result $result */
        $result = $resultedit[0];

        if (empty($score)) {
            $score = 0;
        }
        $result->set_score(api_number_format($score, api_get_setting('gradebook_number_decimals')));
        $result->save();
    }
    Display::addFlash(Display::return_message(get_lang('AllResultsEdited')));
    header('Location: gradebook_view_result.php?selecteval='.$select_eval.'&'.api_get_cidreq());
    exit;
}

$table = $edit_result_form->toHtml();

$interbreadcrumb[] = array(
    'url' => $_SESSION['gradebook_dest'],
    'name' => get_lang('Gradebook')
);
$interbreadcrumb[] = array(
    'url' => 'gradebook_view_result.php?selecteval='.$select_eval.'&'.api_get_cidreq(),
    'name' => get_lang('ViewResult')
);
Display::display_header(get_lang('EditResult'));
DisplayGradebook::display_header_result($evaluation[0], null, 0, 0);
echo $table;
Display :: display_footer();
