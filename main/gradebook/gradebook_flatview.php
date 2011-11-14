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
require_once 'lib/be.inc.php';
require_once 'lib/gradebook_functions.inc.php';
require_once 'lib/fe/dataform.class.php';
require_once 'lib/fe/userform.class.php';
require_once 'lib/flatview_data_generator.class.php';
require_once 'lib/fe/flatviewtable.class.php';
require_once 'lib/fe/displaygradebook.php';
require_once 'lib/fe/exportgradebook.php';
require_once 'lib/scoredisplay.class.php';
require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/gradebook_functions.inc.php';
require_once api_get_path(LIBRARY_PATH).'pdf.lib.php';

if (!class_exists('HTML_Table')) { require_once api_get_path(LIBRARY_PATH).'pear/HTML/Table.php'; }

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

if (isset ($_GET['exportpdf']))	{

	$interbreadcrumb[] = array (
		'url' => api_get_self().'?selectcat=' . Security::remove_XSS($_GET['selectcat']),
		'name' => get_lang('FlatView')
	);

	$export_pdf_form = new DataForm(DataForm::TYPE_EXPORT_PDF, 'export_pdf_form', null, api_get_self().'?exportpdf=&offset='.intval($_GET['offset']).'&selectcat='.intval($_GET['selectcat']), '_blank', '');

	if ($export_pdf_form->validate()) {

		// Beginning of PDF report creation

		$printable_data = get_printable_data($users, $alleval, $alllinks);
		$export = $export_pdf_form->exportValues();

		// Reading report's CSS

		//$css_file = api_get_path(TO_SYS, WEB_CSS_PATH).api_get_setting('stylesheets').'/print.css';
		$css_file = api_get_path(SYS_CODE_PATH).'gradebook/print.css';
		$css = file_exists($css_file) ? @file_get_contents($css_file) : '';

		// HTML report creation first

		$time = time();
		$cat_name = trim($cat[0]->get_name());
		$course_code = trim($cat[0]->get_course_code());
		$organization = api_get_setting('Institution');
		
		$displayscore = ScoreDisplay :: instance();
		$customdisplays = $displayscore->get_custom_score_display_settings();
		
		if (is_array($customdisplays) && count(($customdisplays))) {
			$total = array();
			foreach($customdisplays  as $custom) {
				$total[$custom['display']]  = 0; 
 			}			
			$user_results = $flatviewtable->datagen->get_data_to_graph2();
			foreach($user_results  as $user_result) {
				$total[$user_result[count($user_result)-1][1]]++;
			}
		}
		
		$html = '';
		
		$img = api_get_path(SYS_CODE_PATH).'css/'.api_get_visual_theme().'/images/header-logo.png';
		if (file_exists($img)) {
			$img = api_get_path(WEB_CODE_PATH).'css/'.api_get_visual_theme().'/images/header-logo.png';
			$html .= "<img src='$img'>";			
		} else {
			if (!empty($organization)) {			  
				$html .= '<h2 align="left">'.$organization.'</h2>';
			}
		}
		$html .= '<h2 align="center">'.get_lang('FlatView').'</h2>';
		
		$html .= '<table align="center" width="100%"><tr><td valign="top">';
		
		$html .= '<table align="left" width="33%">';		
		$session_name = api_get_session_name(api_get_session_id());
		$teacher_list = CourseManager::get_teacher_list_from_course_code_to_string($course_code);
		if (!empty($session_name)) {
			$html .= Display::tag('tr', Display::tag('td', get_lang('Session')).Display::tag('td', Display::tag('strong', $session_name)));
		}		
		$html .= Display::tag('tr', Display::tag('td', get_lang('Course')).Display::tag('td', Display::tag('strong', $course_code)));
		$html .= Display::tag('tr', Display::tag('td', get_lang('Date')).Display::tag('td', Display::tag('strong', api_convert_and_format_date(date('Y-m-d', time()), DATE_TIME_FORMAT_LONG))));
		$html .= Display::tag('tr', Display::tag('td', get_lang('Teacher')).Display::tag('td', Display::tag('strong', $teacher_list)));
		$html .= '</table></td>';
		
		$html .= '<td valign="top"><table align="left" width="33%">';
		
		if (!empty($total)) {			
			foreach($total as $label => $count) {
				$total_custom_score = round($count/count($user_results), 2) *100;
				$html .= Display::tag('tr', Display::tag('td', $label).Display::tag('td', Display::tag('strong', $total_custom_score.' %')));
			}
		}
		$html .= '</table></td>';
		
		$html .= '<td valign="top"><table align="left" width="33%">';
		$headers = $printable_data[0];
		unset($headers[0]);
		unset($headers[1]);
		unset($headers[count($headers)+1]);
		
		foreach ($headers as $head) {
			$html .= Display::tag('tr', Display::tag('td', 'P1').Display::tag('td', Display::tag('strong', $head)));		
		}		
		$html .= '</table></td></table><br />';		

		$columns = count($printable_data[0]);
		$has_data = is_array($printable_data[1]) && count($printable_data[1]) > 0;

		if (api_is_western_name_order()) {
			// Choosing the right person name order according to the current language.
			list($printable_data[0][0], $printable_data[0][1]) = array($printable_data[0][1], $printable_data[0][0]);
			if ($has_data) {
				foreach ($printable_data[1] as &$printable_data_row) {
					list($printable_data_row[0], $printable_data_row[1]) = array($printable_data_row[1], $printable_data_row[0]);
				}
			}
		}

		$table = new HTML_Table(array('class' => 'data_table'));
		$row = 0;
		$column = 0;
		foreach ($printable_data[0] as $printable_data_cell) {
			$table->setHeaderContents($row, $column, $printable_data_cell);
			$column++;
		}
		$row++;
		if ($has_data) {
			foreach ($printable_data[1] as &$printable_data_row) {
				$column = 0;
				foreach ($printable_data_row as &$printable_data_cell) {
					$table->setCellContents($row, $column, $printable_data_cell);
					$table->updateCellAttributes($row, $column, 'align="center"');
					$column++;
				}
				$table->updateRowAttributes($row, $row % 2 ? 'class="row_even"' : 'class="row_odd"', true);
				$row++;
			}
		} else {
			$column = 0;
			$table->setCellContents($row, $column, get_lang('NoResults'));
			$table->updateCellAttributes($row, $column, 'colspan="'.$columns.'" align="center" class="row_odd"');
		}

		$html .= $table->toHtml();
		
		//echo $html;exit;
		// Memory release

		unset($printable_data);
		unset($table);

		// Conversion of the created HTML report to a PDF report

		$html         = api_utf8_encode($html);
        //@todo this is really a must?
		$creator_pdf  = api_utf8_encode($creator);
		$title_pdf    = api_utf8_encode($report_name);
		$subject_pdf  = api_utf8_encode(get_lang('FlatView'));
		$keywods_pdf  = api_utf8_encode($course_code);

		$page_format = $export['orientation'] == 'landscape' ? 'A4-L' : 'A4';
        $pdf = new PDF($page_format, $export['orientation']);
        
        // Sending the created PDF report to the client
        $file_name = date('YmdHi_', $time);
        if (!empty($course_code)) {
            $file_name .= $course_code.'_';
        }
        $file_name .= get_lang('FlatView').'.pdf';
        $pdf->content_to_pdf($html, $css, $file_name, api_get_course_id());
        exit;	
	} else {
		Display :: display_header(get_lang('ExportPDF'));
	}
}

if (isset ($_GET['print']))	{
	$printable_data = get_printable_data ($users,$alleval, $alllinks);
	echo print_table($printable_data[1],$printable_data[0], get_lang('FlatView'), $cat[0]->get_name());
	exit;
}

if (!empty($_POST['export_report']) && $_POST['export_report'] == 'export_report') {
	if (api_is_platform_admin() || api_is_course_admin() || api_is_course_coach()) {
		$user_id = null;

		if (empty($_SESSION['export_user_fields'])) {
			$_SESSION['export_user_fields'] = false;
		}
		if (!api_is_allowed_to_edit(false, false) and !api_is_course_tutor()) {
			$user_id = api_get_user_id();
		}

		require_once 'gradebook_result.class.php';
		$printable_data = get_printable_data($users, $alleval, $alllinks);
		switch($_POST['export_format']) {
			case 'xls':
				$export = new GradeBookResult();
				$export->exportCompleteReportXLS($printable_data);
				exit;
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
	echo '<div class="normal-message">';
	$export_pdf_form->display();
	echo '</div>';
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
