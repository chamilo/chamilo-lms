<?php
/* For licensing terms, see /license.txt */

$language_file= 'gradebook';

require_once '../inc/global.inc.php';
require_once 'lib/be.inc.php';
require_once 'lib/gradebook_functions.inc.php';
require_once 'lib/fe/userform.class.php';
require_once 'lib/user_data_generator.class.php';
require_once 'lib/fe/usertable.class.php';
require_once 'lib/fe/displaygradebook.php';
require_once 'lib/scoredisplay.class.php';

require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';

api_block_anonymous_users();
block_students();

$interbreadcrumb[]= array (
	'url' => $_SESSION['gradebook_dest'],
	'name' => get_lang('Gradebook'
));
$category= Category :: load(0);
$my_user_id=Security::remove_XSS($_GET['userid']);
$allevals= $category[0]->get_evaluations($my_user_id, true);
$alllinks= $category[0]->get_links($my_user_id, true);
if ($_GET['selectcat'] != null) {
	$addparams= array (
		'userid' => $my_user_id,
		'selectcat' => Security::remove_XSS($_GET['selectcat'])
	);
} else {
	$addparams= array (
		'userid' => $my_user_id,
		'selecteval' => Security::remove_XSS($_GET['selecteval'])
	);
}

$user_table= new UserTable($my_user_id, $allevals, $alllinks, $addparams);
if (isset ($_GET['exportpdf'])) {	

	$datagen       = new UserDataGenerator($my_user_id, $allevals,$alllinks);
	$data_array    = $datagen->get_data(UserDataGenerator :: UDG_SORT_NAME, 0, null, true);
    
	$newarray      = array ();
	$displayscore  = Scoredisplay :: instance();
	$newitem       = array ();
	foreach ($data_array as $data) {
        $newarray[] = array_slice($data, 1);
	}
	$userinfo = get_user_info_from_id($my_user_id);    
	$html .= get_lang('Results').' : '.api_get_person_name($userinfo['firstname'], $userinfo['lastname']).' ('. api_convert_and_format_date(null, DATE_FORMAT_SHORT). ' ' . api_convert_and_format_date(null, TIME_NO_SEC_FORMAT) .')';
	
	if ($displayscore->is_custom()) {
		$header_names= array (
            get_lang('Evaluation'), get_lang('Course'), get_lang('Category'), get_lang('EvaluationAverage'),get_lang('Result'),get_lang('Display'));
	} else {
		$header_names= array (
			get_lang('Evaluation'), get_lang('Course'), get_lang('Category'), get_lang('EvaluationAverage'),get_lang('Result'));
	}
    
    $table = new HTML_Table(array('class' => 'data_table'));
    $row = 0;
    $column = 0;
    foreach ($header_names as $item) {
        $table->setHeaderContents($row, $column, $item);
        $column++;
    }
    $row = 1;
    if (!empty($newarray)) {
        foreach ($newarray as $data) {
            $column = 0;
            $table->setCellContents($row, $column, $data);
            $table->updateCellAttributes($row, $column, 'align="center"');
            $column++;
            $row++;
        }
    }    
    $html .= $table->toHtml();
    
    require_once api_get_path(LIBRARY_PATH).'pdf.lib.php';
    $pdf = new PDF();
    $pdf->content_to_pdf($html);
	exit;
}
$actions = '<div class="actions">';

if (isset ($_GET['selectcat'])) {
	$interbreadcrumb[]= array ('url' => 'gradebook_flatview.php?selectcat=' . Security::remove_XSS($_GET['selectcat']), 'name' => get_lang('FlatView'));
	$actions.= '<a href=gradebook_flatview.php?selectcat=' .Security::remove_XSS($_GET['selectcat']) . '>' . Display::return_icon('back.png', get_lang('BackTo').' '.get_lang('FlatView'),'','32').'</a>';

}
if (isset ($_GET['selecteval'])) {
	$interbreadcrumb[]= array (
		'url' => 'gradebook_view_result.php?selecteval=' . Security::remove_XSS($_GET['selecteval']),
		'name' => get_lang('ViewResult'
	));
	$actions.= '<a href=gradebook_view_result.php?selecteval=' . Security::remove_XSS($_GET['selecteval']) . '>
	'.Display::return_icon('back.png', get_lang('BackToEvaluation'),'','32').'</a>';
}
$actions.= '<a href="' . api_get_self() . '?exportpdf=&userid='.Security::remove_XSS($_GET['userid']).'&selectcat=' . $category[0]->get_id() . '" target="_blank">
' . Display::return_icon('pdf.png', get_lang('ExportPDF'),'','32').'</a>';

$actions.='</div>';

Display :: display_header(get_lang('ResultsPerUser'));
DisplayGradebook :: display_header_user($_GET['userid']);
echo $actions;
$user_table->display();
Display :: display_footer();
