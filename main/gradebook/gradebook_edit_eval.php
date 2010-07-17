<?php // $Id: $
/* For licensing terms, see /license.txt */
$language_file = 'gradebook';
//$cidReset = true;
require_once '../inc/global.inc.php';
require_once 'lib/be.inc.php';
require_once 'lib/gradebook_functions.inc.php';
require_once 'lib/fe/evalform.class.php';
api_block_anonymous_users();
block_students();

$evaledit = Evaluation :: load($_GET['editeval']);
$form = new EvalForm(EvalForm :: TYPE_EDIT, $evaledit[0], null, 'edit_eval_form',null,api_get_self() . '?editeval=' . Security::remove_XSS($_GET['editeval']));
if ($form->validate()) {
	$values = $form->exportValues();
	$eval = new Evaluation();
	$eval->set_id($values['hid_id']);
	$eval->set_name($values['name']);
	$eval->set_description($values['description']);
	$eval->set_user_id($values['hid_user_id']);
	$eval->set_course_code($values['hid_course_code']);
	$eval->set_category_id($values['hid_category_id']);
	$eval->set_weight($values['weight']);
	$eval->set_max($values['max']);
	if (empty ($values['visible'])) {
		$visible = 0;
	} else {
		$visible = 1;
	}
	$eval->set_visible($visible);
	$eval->save();
	header('Location: '.$_SESSION['gradebook_dest'].'?editeval=&selectcat=' . $eval->get_category_id());
	exit;
}
$selectcat_inter=isset($_GET['selectcat'])?Security::remove_XSS($_GET['selectcat']):'';
$interbreadcrumb[] = array (
	'url' => $_SESSION['gradebook_dest'].'?selectcat='.$selectcat_inter,
	'name' => get_lang('Gradebook'
));
Display :: display_header(get_lang('EditEvaluation'));
$form->display();
Display :: display_footer();
