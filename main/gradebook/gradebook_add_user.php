<?php //$Id: gradebook_add_user.php 21153 2009-06-01 01:51:43Z yannoo $
/* For licensing terms, see /dokeos_license.txt */
$language_file = 'gradebook';
require_once '../inc/global.inc.php';
$this_section = SECTION_MYGRADEBOOK;
require_once ('lib/be.inc.php');
require_once ('lib/fe/displaygradebook.php');
require_once ('lib/gradebook_functions.inc.php');
require_once ('lib/fe/evalform.class.php');
require_once ('lib/scoredisplay.class.php');
api_block_anonymous_users();
block_students();

$evaluation= Evaluation :: load($_GET['selecteval']);
$newstudents = $evaluation[0]->get_not_subscribed_students();

if (count($newstudents) == '0') {
	header('Location: gradebook_view_result.php?nouser=&selecteval=' . Security::remove_XSS($_GET['selecteval']));
	exit;
}
$add_user_form= new EvalForm(EvalForm :: TYPE_ADD_USERS_TO_EVAL,
							 $evaluation[0],
							 null,
							 'add_users_to_evaluation',
							 null,
							 api_get_self() . '?selecteval=' . $_GET['selecteval'],
							 $_GET['firstletter'],
							 $newstudents);

if ( isset($_POST['submit_button']) ) {
	$users= is_array($_POST['add_users']) ? $_POST['add_users'] : array ();
	foreach ($users as $key => $value){
		$users[$key]= intval($value);
	}

	if (count($users) == 0) {
		header('Location: ' . api_get_self() . '?erroroneuser=&selecteval=' .Security::remove_XSS($_GET['selecteval']));
		exit;
	} else {
		foreach ($users as $user_id) {
			$result= new Result();
			$result->set_user_id($user_id);
			$result->set_evaluation_id($_GET['selecteval']);
			$result->set_date(time());
			$result->add();
			}
		}
	header('Location: gradebook_view_result.php?adduser=&selecteval=' .Security::remove_XSS($_GET['selecteval']));
	exit;
	} elseif ($_POST['firstLetterUser']) {
		$firstletter= $_POST['firstLetterUser'];
		if (!empty ($firstletter)) {
			header('Location: ' . api_get_self() . '?firstletter=' . Security::remove_XSS($firstletter) . '&selecteval=' . Security::remove_XSS($_GET['selecteval']));
			exit;
		}
}

$interbreadcrumb[]= array (
	'url' => $_SESSION['gradebook_dest'],
	'name' => get_lang('Gradebook'
));
$interbreadcrumb[]= array (
	'url' => 'gradebook_view_result.php?selecteval=' .Security::remove_XSS($_GET['selecteval']),
	'name' => get_lang('ViewResult'
));
Display :: display_header(get_lang('AddUserToEval'));
if (isset ($_GET['erroroneuser'])){
	Display :: display_warning_message(get_lang('AtLeastOneUser'),false);
}
DisplayGradebook :: display_header_result($evaluation[0], null, 0,0);
echo '<div class="main">';
echo $add_user_form->toHtml();
echo '</div>';
Display :: display_footer();