<?php // $Id: $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2009 Dokeos Latinoamerica SAC
	Copyright (c) 2006 Dokeos SPRL
	Copyright (c) 2006 Ghent University (UGent)
	Copyright (c) various contributors

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/
$language_file= 'gradebook';
//$cidReset= true;
require_once ('../inc/global.inc.php');
require_once ('lib/be.inc.php');
require_once ('lib/gradebook_functions.inc.php');
require_once ('lib/fe/dataform.class.php');
require_once ('lib/fe/userform.class.php');
require_once ('lib/flatview_data_generator.class.php');
require_once ('lib/fe/flatviewtable.class.php');
require_once ('lib/fe/displaygradebook.php');
require_once ('lib/fe/exportgradebook.php');
require_once (api_get_path(LIBRARY_PATH).'ezpdf/class.ezpdf.php');
require_once ('lib/scoredisplay.class.php');
api_block_anonymous_users();
block_students();

if (isset ($_POST['submit']) && isset ($_POST['keyword'])) {
	header('Location: ' . api_get_self() . '?selectcat=' . Security::remove_XSS($_GET['selectcat'])
										   . '&search='.Security::remove_XSS($_POST['keyword']));
	exit;
}

$htmlHeadXtra[] = '<script src="../inc/lib/javascript/jquery.js" type="text/javascript" language="javascript"></script>'; //jQuery

$interbreadcrumb[]= array (
	'url' => $_SESSION['gradebook_dest'],
	'name' => get_lang('Gradebook'
));

$showeval= (isset ($_POST['showeval']) ? '1' : '0');
$showlink= (isset ($_POST['showlink']) ? '1' : '0');
if (($showlink == '0') && ($showeval == '0')) {
	$showlink= '1';
	$showeval= '1';
}
$cat= Category :: load($_REQUEST['selectcat']);

if (isset($_GET['userid'])) {
	$userid=Security::remove_XSS($_GET['userid']);
} else {
	$userid='';
}

if ($showeval) {
	$alleval= $cat[0]->get_evaluations($userid, true);
} else {
	$alleval=null;
}

if ($showlink) {
	$alllinks= $cat[0]->get_links($userid, true);
} else {
	$alllinks=null;
}



if (isset ($export_flatview_form) && (!$file_type == 'pdf')) {
	Display :: display_normal_message($export_flatview_form->toHtml(),false);
}
if (isset($_GET['selectcat'])) {
	$category_id=Security::remove_XSS($_GET['selectcat']);
} else {
	$category_id='';
}

$simple_search_form= new UserForm(UserForm :: TYPE_SIMPLE_SEARCH, null, 'simple_search_form', null, api_get_self() . '?selectcat=' .$category_id);
$values= $simple_search_form->exportValues();
$keyword = '';
if (isset($_GET['search']) && !empty($_GET['search'])) {
	$keyword = Security::remove_XSS($_GET['search']);
}
if ($simple_search_form->validate() && (empty($keyword))) {
	$keyword = $values['keyword'];
}


if (!empty($keyword)) {
	$users= find_students($keyword);
} else {
	if (isset($alleval) && isset($alllinks)) {
		$users= get_all_users($alleval, $alllinks);
	}else {
		$users=null;
	}
}
if (isset ($_GET['exportpdf']))	{
	$interbreadcrumb[]= array (
	'url' => api_get_self().'?selectcat=' . Security::remove_XSS($_GET['selectcat']),
	'name' => get_lang('FlatView')
	);
	$export_pdf_form= new DataForm(DataForm :: TYPE_EXPORT_PDF, 'export_pdf_form', null, api_get_self() . '?exportpdf=&offset='.$_GET['offset'].'&selectcat=' . $_GET['selectcat'],'_blank');
	if (!$export_pdf_form->validate()) {
		Display :: display_header(get_lang('ExportPDF'));
	}
	if ($export_pdf_form->validate()) {
		$printable_data = get_printable_data ($users,$alleval, $alllinks);
		$export= $export_pdf_form->exportValues();
		$format = $export['orientation'];
		$pdf =& new Cezpdf('a4',$format); //format is 'portrait' or 'landscape'
		$clear_printable_data=array();
		$clear_send_printable_data=array();
		//var_dump(count($printable_data[1]));
		for ($i=0;$i<count($printable_data[1]);$i++) {
			for ($k=0;$k<count($printable_data[1][$i]);$k++) {
				$clear_printable_data[]=strip_tags($printable_data[1][$i][$k]);
			}
			$clear_send_printable_data[]=$clear_printable_data;
			$clear_printable_data=array();
		}
		/*var_dump($printable_data[1]);
		var_dump('--------------');
		var_dump($clear_send_printable_data);*/
		export_pdf($pdf,$clear_send_printable_data,$printable_data[0],$format);
		exit;
	}
}

if (isset ($_GET['print']))	{
	$printable_data = get_printable_data ($users,$alleval, $alllinks);
	echo print_table($printable_data[1],$printable_data[0], get_lang('FlatView'), $cat[0]->get_name());
	exit;
}

if(!empty($_POST['export_report']) && $_POST['export_report'] == 'export_report'){
	if(api_is_platform_admin() || api_is_course_admin() || api_is_course_coach())	{
		$user_id = null;

		if(empty($_SESSION['export_user_fields'])) {
			$_SESSION['export_user_fields'] = false;
		}
		if(!api_is_allowed_to_edit(false,false) and !api_is_course_tutor()) {
			$user_id = api_get_user_id();
		}

		require_once('gradebook_result.class.php');
		$printable_data = get_printable_data ($users,$alleval, $alllinks);

		switch($_POST['export_format']) {
			case 'xls':
				$export = new GradeBookResult();
				$export->exportCompleteReportXLS($printable_data );
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

$addparams= array ('selectcat' => $cat[0]->get_id());
if (isset($_GET['search'])) {
	$addparams['search'] = $keyword;
}
$offset = (isset($_GET['offset'])?$_GET['offset']:'0');

$flatviewtable= new FlatViewTable($cat[0], $users, $alleval, $alllinks, true, $offset, $addparams);

if (isset($_GET['exportpdf'])) {
	echo '<div class="normal-message">';
	$export_pdf_form->display();
	echo '</div>';
} else {
	Display :: display_header(get_lang('FlatView'));
}
if (isset($_GET['isStudentView']) && $_GET['isStudentView']=='false') {
		DisplayGradebook :: display_header_reduce_flatview($cat[0], $showeval, $showlink, $simple_search_form);
		$flatviewtable->display();
} elseif (isset($_GET['selectcat']) && ($_SESSION['studentview']=='teacherview')) {
		DisplayGradebook :: display_header_reduce_flatview($cat[0], $showeval, $showlink, $simple_search_form);
		/*echo '<div id="contentLoading" class="contentLoading">';
		echo Display::display_icon('loader.gif');
		echo '</div>';*/

		// main graph
		//@todo load images with jquery
		echo '<div id="contentArea" style="text-align:center;" >';
			$image_file = $flatviewtable->display_graph();
			$my_info_path_img=array();
			$my_info_path_img=explode('/',$image_file);
			if (strlen($my_info_path_img[5])==32) {
				echo '<img  src="'.$image_file.'">';
			}
			$flatviewtable->display();
			$flatviewtable->display_graph_by_resource();
		echo '</div>';
}
Display :: display_footer();

function get_printable_data($users,$alleval, $alllinks) {
	$datagen = new FlatViewDataGenerator ($users,$alleval, $alllinks);
	$offset = (isset($_GET['offset'])?$_GET['offset']:'0');
	$count = (($offset+10) > $datagen->get_total_items_count()) ?
  		 	 ($datagen->get_total_items_count()-$offset) : 10;
	$header_names = $datagen->get_header_names($offset,$count);
	$data_array = $datagen->get_data(FlatViewDataGenerator :: FVDG_SORT_LASTNAME,0,null,$offset,$count,true);
	$newarray = array();
	foreach ($data_array as $data) {
		$newarray[] = array_slice($data, 1);
	}
	return array ($header_names, $newarray);
}