<?php
/* For licensing terms, see /license.txt */
/**
 * Script
 * @package chamilo.gradebook
 */
/**
 * Init
 */

$language_file = 'gradebook';
require_once '../inc/global.inc.php';
$current_course_tool  = TOOL_GRADEBOOK;

api_protect_course_script();

require_once 'lib/be.inc.php';      
require_once 'lib/fe/dataform.class.php';
require_once 'lib/fe/userform.class.php';
require_once 'lib/flatview_data_generator.class.php';
require_once 'lib/fe/flatviewtable.class.php';
require_once 'lib/fe/displaygradebook.php';
require_once 'lib/fe/exportgradebook.php';
require_once 'lib/scoredisplay.class.php';
require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/gradebook_functions.inc.php';
require_once api_get_path(LIBRARY_PATH).'pdf.lib.php';

api_block_anonymous_users();
block_students();

if (isset ($_POST['submit']) && isset ($_POST['keyword'])) {
	header('Location: '.api_get_self().'?selectcat='.Security::remove_XSS($_GET['selectcat']).'&search='.Security::remove_XSS($_POST['keyword']));
	exit;
}

$interbreadcrumb[] = array ('url' => $_SESSION['gradebook_dest'].'?selectcat=1', 'name' => get_lang('ToolGradebook'));

$showeval = isset($_POST['showeval']) ? '1' : '0';
$showlink = isset($_POST['showlink']) ? '1' : '0';
if (($showlink == '0') && ($showeval == '0')) {
	$showlink = '1';
	$showeval = '1';
}
$cat = Category::load($_REQUEST['selectcat']);

if (isset($_GET['userid'])) {
	$userid = Security::remove_XSS($_GET['userid']);
} else {
	$userid = '';
}

if ($showeval) {
	$alleval = $cat[0]->get_evaluations($userid, true);
} else {
	$alleval = null;
}

if ($showlink) {
	$alllinks = $cat[0]->get_links($userid, true);
} else {
	$alllinks = null;
}

if (isset($export_flatview_form) && (!$file_type == 'pdf')) {
	Display :: display_normal_message($export_flatview_form->toHtml(), false);
}

if (isset($_GET['selectcat'])) {
	$category_id = Security::remove_XSS($_GET['selectcat']);
} else {
	$category_id = '';
}

$simple_search_form = new UserForm(UserForm :: TYPE_SIMPLE_SEARCH, null, 'simple_search_form', null, api_get_self() . '?selectcat=' . $category_id);
$values = $simple_search_form->exportValues();

$keyword = '';
if (isset($_GET['search']) && !empty($_GET['search'])) {
	$keyword = Security::remove_XSS($_GET['search']);
}
if ($simple_search_form->validate() && (empty($keyword))) {
	$keyword = $values['keyword'];
}

if (!empty($keyword)) {
	$users = find_students($keyword);
    
} else {
	if (isset($alleval) && isset($alllinks)) {
		$users = get_all_users($alleval, $alllinks);        
	} else {
		$users = null;
	}
}

$offset = isset($_GET['offset']) ? $_GET['offset'] : '0';

$flatviewtable = new FlatViewTable($cat[0], $users, $alleval, $alllinks, true, $offset, $addparams);

$parameters=array('selectcat'=>intval($_GET['selectcat']));
$flatviewtable->set_additional_parameters($parameters);

if (isset($_GET['export_pdf']) && $_GET['export_pdf'] == 'category') {  
    $params = array();
    $params['only_total_category'] = true;
    $params['join_firstname_lastname'] = true;
    $params['show_official_code'] = true;
    $params['export_pdf'] = true;
    
    if ($cat[0]->is_locked() == true || api_is_platform_admin()) {
        Display :: set_header(null, false, false);
        export_pdf_flatview($cat, $users, $alleval, $alllinks, $params);
    }
}
    
if (isset($_GET['exportpdf']))	{
	$interbreadcrumb[] = array (
		'url' => api_get_self().'?selectcat=' . Security::remove_XSS($_GET['selectcat']),
		'name' => get_lang('FlatView')
	);
    
    $export_pdf_form = new DataForm(DataForm::TYPE_EXPORT_PDF, 'export_pdf_form', null, api_get_self().'?exportpdf=&offset='.intval($_GET['offset']).'&selectcat='.intval($_GET['selectcat']), '_blank', '');

	if ($export_pdf_form->validate()) {        
        $params = $export_pdf_form->exportValues();
        Display :: set_header(null, false, false);
        $params['join_firstname_lastname'] = true;
        $params['show_usercode'] = true;
        $params['export_pdf'] = true;        
        $params['only_total_category'] = false;
		export_pdf_flatview($cat, $users, $alleval, $alllinks, $params);
	} else {
		Display :: display_header(get_lang('ExportPDF'));
	}
}

if (isset ($_GET['print']))	{
	$printable_data = get_printable_data($cat[0], $users, $alleval, $alllinks);
	echo print_table($printable_data[1],$printable_data[0], get_lang('FlatView'), $cat[0]->get_name());
	exit;
}
       
        
if (!empty($_GET['export_report']) && $_GET['export_report'] == 'export_report') {    
	if (api_is_platform_admin() || api_is_course_admin() || api_is_course_coach()) {
		$user_id = null;

		if (empty($_SESSION['export_user_fields'])) {
			$_SESSION['export_user_fields'] = false;
		}
		if (!api_is_allowed_to_edit(false, false) and !api_is_course_tutor()) {
			$user_id = api_get_user_id();
		}

		require_once 'gradebook_result.class.php';
		$printable_data = get_printable_data($cat[0], $users, $alleval, $alllinks);
        
		switch($_GET['export_format']) {
			case 'xls':
				$export = new GradeBookResult();
				$export->exportCompleteReportXLS($printable_data);
				break;
			case 'doc':
				$export = new GradeBookResult();
				$export->exportCompleteReportDOC($printable_data);
                exit;
				break;
			case 'csv':
			default:
				$export = new GradeBookResult();
				$export->exportCompleteReportCSV($printable_data);
				exit;
				break;
		}
	} else {
		api_not_allowed(true);
	}
}

$addparams = array ('selectcat' => $cat[0]->get_id());
if (isset($_GET['search'])) {
	$addparams['search'] = $keyword;
}


$this_section = SECTION_COURSES;

if (isset($_GET['exportpdf'])) {	
	$export_pdf_form->display();	
} else {
	Display :: display_header(get_lang('FlatView'));
}

if (isset($_GET['isStudentView']) && $_GET['isStudentView'] == 'false') {
	DisplayGradebook :: display_header_reduce_flatview($cat[0], $showeval, $showlink, $simple_search_form);
	$flatviewtable->display();
} elseif (isset($_GET['selectcat']) && ($_SESSION['studentview'] == 'teacherview')) {
	DisplayGradebook :: display_header_reduce_flatview($cat[0], $showeval, $showlink, $simple_search_form);
	
	// main graph
	$flatviewtable->display();	
	// @todo this needs a fix
	//$image_file = $flatviewtable->display_graph();
	//@todo load images with jquery
    echo '<div id="contentArea" style="text-align: center;" >';        		
	if (!empty($image_file)) {
		echo '<img src="'.$image_file.'">';
	}        
	$flatviewtable->display_graph_by_resource();
	echo '</div>';
}

Display :: display_footer();