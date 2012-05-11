<?php // $Id: $
/* For licensing terms, see /license.txt */
/**
 * Script
 * @package chamilo.gradebook
 */
/**
 * Init
 */
$language_file = 'gradebook';
//$cidReset = true;
require_once '../inc/global.inc.php';
require_once 'lib/be.inc.php';
require_once 'lib/fe/displaygradebook.php';
require_once 'lib/gradebook_functions.inc.php';
require_once 'lib/fe/evalform.class.php';
require_once 'lib/scoredisplay.class.php';
api_block_anonymous_users();
block_students();
$select_eval=Security::remove_XSS($_GET['selecteval']);
if (empty($select_eval)) {
	api_not_allowed();
}
$resultedit = Result :: load (null,null,$select_eval);
$evaluation = Evaluation :: load ($select_eval);

$evaluation[0]->check_lock_permissions();

$edit_result_form = new EvalForm(EvalForm :: TYPE_ALL_RESULTS_EDIT, $evaluation[0], $resultedit, 'edit_result_form', null, api_get_self() . '?&selecteval='.$select_eval);
$table = $edit_result_form->toHtml();
if ($edit_result_form->validate()) {
	$values = $edit_result_form->exportValues();
	$scores = ($values['score']);
	foreach ($scores as $row) {
		$resultedit = Result :: load (key($scores));
		$row_value = $row ;
		if ((!empty ($row_value)) || ($row_value == 0)) {
			$resultedit[0]->set_score(floatval(number_format($row_value, api_get_setting('gradebook_number_decimals'))));
		}
		$resultedit[0]->save();
		next($scores);
	}
	header('Location: gradebook_view_result.php?selecteval='.$select_eval.'&editallresults=');
	exit;
}

$interbreadcrumb[] = array (
	'url' => $_SESSION['gradebook_dest'],
	'name' => get_lang('Gradebook'
));
$interbreadcrumb[]= array (
	'url' => 'gradebook_view_result.php?selecteval='.$select_eval,
	'name' => get_lang('ViewResult'
));
Display :: display_header(get_lang('EditResult'));
DisplayGradebook :: display_header_result ($evaluation[0],null,0,0);
echo $table;
Display :: display_footer();
