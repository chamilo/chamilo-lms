<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2008 Dokeos Latinoamerica SAC
	Copyright (c) 2006-2008 Dokeos SPRL
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
// $cidReset : This is the main difference with gradebook.php, here we say,
// basically, that we are inside a course, and many things depend from that
$cidReset= true;
$_in_course = false;
//make sure the destination for scripts is index.php instead of gradebook.php
require_once '../inc/global.inc.php';
$_SESSION['gradebook_dest'] = 'gradebook.php';
$this_section = SECTION_MYGRADEBOOK;
require_once 'lib/be.inc.php';
require_once 'lib/scoredisplay.class.php';
require_once 'lib/gradebook_functions.inc.php';
require_once 'lib/fe/catform.class.php';
require_once 'lib/fe/evalform.class.php';
require_once 'lib/fe/linkform.class.php';
require_once 'lib/gradebook_data_generator.class.php';
require_once 'lib/fe/gradebooktable.class.php';
require_once 'lib/fe/displaygradebook.php';
require_once 'lib/fe/userform.class.php';
require_once api_get_path(LIBRARY_PATH).'ezpdf/class.ezpdf.php';
$htmlHeadXtra[] = '<script src="../inc/lib/javascript/jquery.js" type="text/javascript" language="javascript"></script>'; //jQuery
$htmlHeadXtra[] = '<script type="text/javascript">
$(document).ready( function() {
	for (i=0;i<$(".actions").length;i++) {
		if ($(".actions:eq("+i+")").html()=="<table border=\"0\"></table>" || $(".actions:eq("+i+")").html()=="" || $(".actions:eq("+i+")").html()==null) {
			$(".actions:eq("+i+")").hide();
		}
	}
 } );
 </script>';
api_block_anonymous_users();

$htmlHeadXtra[]= '<script type="text/javascript">
function confirmation ()
{
	if (confirm("' . get_lang('DeleteAll') . '?"))
		{return true;}
	else
		{return false;}
}
</script>';
$filter_confirm_msg = true;
$filter_warning_msg = true;
// --------------------------------------------------------------------------------
// -                                  ACTIONS                                     -
// --------------------------------------------------------------------------------

$my_selectcat =isset($_GET['selectcat']) ? Security::remove_XSS($_GET['selectcat']) : '';
if ($my_selectcat!='') {
	$my_db_name       = get_database_name_by_link_id($my_selectcat);
	$tbl_forum_thread = Database :: get_course_table(TABLE_FORUM_THREAD,$my_db_name);
	$tbl_grade_links  = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_LINK);
}

//this is called when there is no data for the course admin
if (isset ($_GET['createallcategories'])) {
	block_students();
	$coursecat= Category :: get_not_created_course_categories(api_get_user_id());
	if (!count($coursecat) == 0) {
		foreach ($coursecat as $row) {
			$cat= new Category();
			$cat->set_name($row[1]);
			$cat->set_course_code($row[0]);
			$cat->set_description(null);
			$cat->set_user_id(api_get_user_id());
			$cat->set_parent_id(0);
			$cat->set_weight(0);
			$cat->set_visible(0);
			$cat->add();
			unset ($cat);
		}
	}
	header('Location: '.$_SESSION['gradebook_dest'].'?addallcat=&selectcat=0');
	exit;
}
//move a category
$selectcat=isset($_GET['selectcat']) ?  Security::remove_XSS($_GET['selectcat']) : '';

if (isset ($_GET['movecat'])) {
	$move_cat=Security::remove_XSS($_GET['movecat']);
	block_students();
	$cats= Category :: load($move_cat);
	if (!isset ($_GET['targetcat'])) {
		$move_form= new CatForm(CatForm :: TYPE_MOVE,
								$cats[0],
								'move_cat_form',
								null,
								api_get_self() . '?movecat=' . $move_cat
													 . '&selectcat=' . Security::remove_XSS($_GET['selectcat']));
		if ($move_form->validate()) {
			header('Location: ' . api_get_self() . '?selectcat=' . Security::remove_XSS($_GET['selectcat'])
													   . '&movecat=' . $move_cat
													   . '&targetcat=' . $move_form->exportValue('move_cat'));
			exit;
		}
	} else {
		$get_target_cat=Security::remove_XSS($_GET['targetcat']);
		$targetcat= Category :: load($get_target_cat);
		$course_to_crsind = ($cats[0]->get_course_code() != null && $targetcat[0]->get_course_code() == null);

		if (!($course_to_crsind && !isset($_GET['confirm']))) {
			$cats[0]->move_to_cat($targetcat[0]);
			header('Location: ' . api_get_self() . '?categorymoved=&selectcat=' . Security::remove_XSS($_GET['selectcat']));
			exit;
		}
		unset ($targetcat);
	}
	unset ($cats);
}

//move an evaluation
if (isset ($_GET['moveeval'])) {
	block_students();
	$get_move_eval=Security::remove_XSS($_GET['moveeval']);
	$evals= Evaluation :: load($get_move_eval);
	if (!isset ($_GET['targetcat'])) {

		$move_form= new EvalForm(EvalForm :: TYPE_MOVE,
								 $evals[0],
								 null,
								 'move_eval_form',
								 null,
								 api_get_self() . '?moveeval=' . $get_move_eval
								 					  . '&selectcat=' . Security::remove_XSS($_GET['selectcat']));

		if ($move_form->validate()) {
			header('Location: ' .api_get_self() . '?selectcat=' . Security::remove_XSS($_GET['selectcat'])
													   . '&moveeval=' . $get_move_eval
													   . '&targetcat=' . $move_form->exportValue('move_cat'));
			exit;
		}
	} else {
		$get_target_cat=Security::remove_XSS($_GET['targetcat']);
		$targetcat= Category :: load($get_target_cat);
		$course_to_crsind = ($evals[0]->get_course_code() != null && $targetcat[0]->get_course_code() == null);

		if (!($course_to_crsind && !isset($_GET['confirm']))) {
			$evals[0]->move_to_cat($targetcat[0]);
			header('Location: ' . api_get_self() . '?evaluationmoved=&selectcat=' . Security::remove_XSS($_GET['selectcat']));
			exit;
		}
		unset ($targetcat);
	}
	unset ($evals);
}

//move a link
if (isset ($_GET['movelink'])) {
	block_students();
	$get_move_link=Security::remove_XSS($_GET['movelink']);
	$link= LinkFactory :: load($get_move_link);
	$move_form= new LinkForm(LinkForm :: TYPE_MOVE, null, $link[0], 'move_link_form', null, api_get_self() . '?movelink=' . $get_move_link . '&selectcat=' . Security::remove_XSS($_GET['selectcat']));
	if ($move_form->validate()) {
		$targetcat= Category :: load($move_form->exportValue('move_cat'));
		$link[0]->move_to_cat($targetcat[0]);
		unset ($link);
		header('Location: ' . api_get_self(). '?linkmoved=&selectcat=' . Security::remove_XSS($_GET['selectcat']));
		exit;
	}
}

//parameters for categories
if (isset ($_GET['visiblecat'])) {
	block_students();
	if (isset ($_GET['set_visible'])) {
		$visibility_command= 1;
	} else {
		$visibility_command= 0;
	}
	$cats= Category :: load(Security::remove_XSS($_GET['visiblecat']));
	$cats[0]->set_visible($visibility_command);
	$cats[0]->save();
	$cats[0]->apply_visibility_to_children();
	unset ($cats);
	if ($visibility_command) {
		$confirmation_message = get_lang('ViMod');
		$filter_confirm_msg = false;
	} else {
		$confirmation_message = get_lang('InViMod');
		$filter_confirm_msg = false;
	}
}
if (isset ($_GET['deletecat'])) {
	block_students();
	$cats= Category :: load(Security::remove_XSS($_GET['deletecat']));
	//delete all categories,subcategories and results
	if ($cats[0] != null) {
		if ($cats[0]->get_id() != 0) {
			// better don't try to delete the root...
			$cats[0]->delete_all();
		}
	}
	$confirmation_message = get_lang('CategoryDeleted');
	$filter_confirm_msg = false;
}
//parameters for evaluations
if (isset ($_GET['visibleeval'])) {
	block_students();
	if (isset ($_GET['set_visible'])) {
		$visibility_command= 1;
	} else {
		$visibility_command= 0;
	}

	$eval= Evaluation :: load(Security::remove_XSS($_GET['visibleeval']));
	$eval[0]->set_visible($visibility_command);
	$eval[0]->save();
	unset ($eval);
	if ($visibility_command) {
		$confirmation_message = get_lang('ViMod');
		$filter_confirm_msg = false;
	} else {
		$confirmation_message = get_lang('InViMod');
		$filter_confirm_msg = false;
	}
}
if (isset ($_GET['deleteeval'])) {
	block_students();
	$eval= Evaluation :: load(Security::remove_XSS($_GET['deleteeval']));
	if ($eval[0] != null) {
		$eval[0]->delete_with_results();
	}
	$confirmation_message = get_lang('GradebookEvaluationDeleted');
	$filter_confirm_msg = false;
}
//parameters for links
if (isset ($_GET['visiblelink'])) {
	block_students();
	if (isset ($_GET['set_visible'])) {
		$visibility_command= 1;
	}else {
		$visibility_command= 0;
	}
	$link= LinkFactory :: load(Security::remove_XSS($_GET['visiblelink']));
	$link[0]->set_visible($visibility_command);
	$link[0]->save();
	unset ($link);
	if ($visibility_command) {
		$confirmation_message = get_lang('ViMod');
		$filter_confirm_msg = false;
	} else {
		$confirmation_message = get_lang('InViMod');
		$filter_confirm_msg = false;
	}
}
if (isset ($_GET['deletelink'])) {
	block_students();
	$link= LinkFactory :: load(Security::remove_XSS($_GET['deletelink']));
	if ($link[0] != null) {
		$sql='UPDATE '.$tbl_forum_thread.' SET thread_qualify_max=0,thread_weight=0,thread_title_qualify="" WHERE thread_id=(SELECT ref_id FROM '.$tbl_grade_links.' where id='.Security::remove_XSS($_GET['deletelink']).');';
		api_sql_query($sql);
		$link[0]->delete();
	}
	unset ($link);
	$confirmation_message = get_lang('LinkDeleted');
	$filter_confirm_msg = false;
}
$course_to_crsind = isset ($course_to_crsind) ? $course_to_crsind : '';
if ($course_to_crsind && !isset($_GET['confirm'])) {
	block_students();
	if (!isset($_GET['movecat']) && !isset($_GET['moveeval'])) {
		die ('Error: movecat or moveeval not defined');
	}
	$button = '<form name="confirm"
					 method="post"
					 action="'.api_get_self() .'?confirm='
													.(isset($_GET['movecat']) ? '&movecat=' . Security::remove_XSS($_GET['movecat'])
																			  : '&moveeval=' . Security::remove_XSS($_GET['moveeval']) )
													.'&selectcat=' . Security::remove_XSS($_GET['selectcat'])
													.'&targetcat=' . Security::remove_XSS($_GET['targetcat']).'">
			   <input type="submit" value="'.'  '.get_lang('Ok').'  '.'">
			   </form>';

	$warning_message = get_lang('MoveWarning').'<br><br>'.$button;
	$filter_warning_msg = false;
}
//actions on the sortabletable
if (isset ($_POST['action'])) {
	block_students();
	$number_of_selected_items= count($_POST['id']);
	if ($number_of_selected_items == '0') {
		$warning_message = get_lang('NoItemsSelected');
		$filter_warning_msg = false;
	}
	else {
		switch ($_POST['action']) {
			case 'deleted' :
				$number_of_deleted_categories= 0;
				$number_of_deleted_evaluations= 0;
				$number_of_deleted_links= 0;
				foreach ($_POST['id'] as $indexstr) {
					if (api_substr($indexstr, 0, 4) == 'CATE') {
						$cats= Category :: load(api_substr($indexstr, 4));
						if ($cats[0] != null) {
							$cats[0]->delete_all();
						}
						$number_of_deleted_categories++;
					}
					if (api_substr($indexstr, 0, 4) == 'EVAL') {
						$eval= Evaluation :: load(api_substr($indexstr, 4));
						if ($eval[0] != null) {
						$eval[0]->delete_with_results();
						}
						$number_of_deleted_evaluations++;
					}
					if (api_substr($indexstr, 0, 4) == 'LINK') {
						$link= LinkFactory :: load(api_substr($indexstr, 4));
						if ($link[0] != null) {
							$link[0]->delete();
						}
						$number_of_deleted_links++;
					}
				}
				$confirmation_message = get_lang('DeletedCategories') . ' : <b>' . $number_of_deleted_categories . '</b><br />' . get_lang('DeletedEvaluations') . ' : <b>' . $number_of_deleted_evaluations . '</b><br />' . get_lang('DeletedLinks') . ' : <b>' . $number_of_deleted_links . '</b><br /><br />' . get_lang('TotalItems') . ' : <b>' . $number_of_selected_items . '</b>';
				$filter_confirm_msg = false;
				break;
			case 'setvisible' :
				foreach ($_POST['id'] as $indexstr)
				{
					if (api_substr($indexstr, 0, 4) == 'CATE')
					{
						$cats= Category :: load(api_substr($indexstr, 4));
						$cats[0]->set_visible(1);
						$cats[0]->save();
						$cats[0]->apply_visibility_to_children();
					}
					if (api_substr($indexstr, 0, 4) == 'EVAL')
					{
						$eval= Evaluation :: load(api_substr($indexstr, 4));
						$eval[0]->set_visible(1);
						$eval[0]->save();
					}
					if (api_substr($indexstr, 0, 4) == 'LINK')
					{
						$link= LinkFactory :: load(api_substr($indexstr, 4));
						$link[0]->set_visible(1);
						$link[0]->save();
					}
				}
				$confirmation_message = get_lang('ItemsVisible');
				$filter_confirm_msg = false;
				break;
			case 'setinvisible' :
				foreach ($_POST['id'] as $indexstr)
				{
					if (api_substr($indexstr, 0, 4) == 'CATE')
					{
						$cats= Category :: load(api_substr($indexstr, 4));
						$cats[0]->set_visible(0);
						$cats[0]->save();
						$cats[0]->apply_visibility_to_children();
					}
					if (api_substr($indexstr, 0, 4) == 'EVAL')
					{
						$eval= Evaluation :: load(api_substr($indexstr, 4));
						$eval[0]->set_visible(0);
						$eval[0]->save();
					}
					if (api_substr($indexstr, 0, 4) == 'LINK')
					{
						$link= LinkFactory :: load(api_substr($indexstr, 4));
						$link[0]->set_visible(0);
						$link[0]->save();
					}
				}
				$confirmation_message = get_lang('ItemsInVisible');
				$filter_confirm_msg = false;
				break;
		}
	}
}

if (isset ($_POST['submit']) && isset ($_POST['keyword'])) {
	header('Location: ' . api_get_self() . '?selectcat=' . Security::remove_XSS($_GET['selectcat'])
											   . '&search='.Security::remove_XSS($_POST['keyword']));
	exit;
}
// --------------------------------------------------------------------------------
// -                       DISPLAY HEADERS AND MESSAGES                           -
// --------------------------------------------------------------------------------

if (!isset($_GET['exportpdf']) and !isset($_GET['export_certificate'])) {
	if (isset ($_GET['studentoverview'])) {
		$interbreadcrumb[]= array (
			'url' => $_SESSION['gradebook_dest'].'?selectcat=' . Security::remove_XSS($_GET['selectcat']),
			'name' => get_lang('Gradebook')
		);
		Display :: display_header(get_lang('FlatView'));
	}
	elseif (isset ($_GET['search'])) {
		$interbreadcrumb[]= array (
			'url' => $_SESSION['gradebook_dest'].'?selectcat=' . Security::remove_XSS($_GET['selectcat']),
			'name' => get_lang('Gradebook')
		);
		Display :: display_header(get_lang('SearchResults'));
	} else {
			$interbreadcrumb[]= array (
				'url' => $_SESSION['gradebook_dest'],
				'name' => get_lang('Gradebook')
			);

		if ((isset($_GET['selectcat']) && $_GET['selectcat']>0)) {
			$interbreadcrumb[]= array (
				'url' => $_SESSION['gradebook_dest'].'?selectcat=0',
				'name' => get_lang('Details')
			);
		}
	 Display :: display_header('');
	}
}

if (isset ($_GET['categorymoved'])) {
	Display :: display_confirmation_message(get_lang('CategoryMoved'),false);
}
if (isset ($_GET['evaluationmoved'])) {
	Display :: display_confirmation_message(get_lang('EvaluationMoved'),false);
}
if (isset ($_GET['linkmoved'])) {
	Display :: display_confirmation_message(get_lang('LinkMoved'),false);
}
if (isset ($_GET['addcat'])) {
	Display :: display_confirmation_message(get_lang('CategoryAdded'),false);
}
if (isset ($_GET['linkadded'])) {
	Display :: display_confirmation_message(get_lang('LinkAdded'),false);
}
if (isset ($_GET['addresult'])) {
	Display :: display_confirmation_message(get_lang('ResultAdded'),false);
}
if (isset ($_GET['editcat'])) {
	Display :: display_confirmation_message(get_lang('CategoryEdited'),false);
}
if (isset ($_GET['editeval'])) {
	Display :: display_confirmation_message(get_lang('EvaluationEdited'),false);
}
if (isset ($_GET['linkedited'])) {
	Display :: display_confirmation_message(get_lang('LinkEdited'),false);
}
if (isset ($_GET['nolinkitems'])) {
	Display :: display_warning_message(get_lang('NoLinkItems'),false);
}
if (isset ($_GET['addallcat'])) {
	Display :: display_normal_message(get_lang('AddAllCat'),false);
}
if (isset ($confirmation_message)) {
	Display :: display_confirmation_message($confirmation_message,$filter_confirm_msg);
}
if (isset ($warning_message)) {
	Display :: display_warning_message($warning_message,$filter_warning_msg);
}
if (isset ($move_form)) {
	Display :: display_normal_message($move_form->toHtml(),false);
}
// --------------------------------------------------------------------------------
// -                        LOAD DATA & DISPLAY TABLE                             -
// --------------------------------------------------------------------------------
$is_platform_admin= api_is_platform_admin();
$is_course_admin= api_is_allowed_to_create_course();
//load data for category, evaluation and links
if (!isset ($_GET['selectcat']) || empty ($_GET['selectcat'])) {
	$category= 0;
	} else {
	$category= Security::remove_XSS($_GET['selectcat']);
	}
// search form

$simple_search_form= new UserForm(UserForm :: TYPE_SIMPLE_SEARCH, null, 'simple_search_form', null, api_get_self() . '?selectcat=' . $selectcat);
$values= $simple_search_form->exportValues();
$keyword = '';
if (isset($_GET['search']) && !empty($_GET['search'])) {
	$keyword = Security::remove_XSS($_GET['search']);
}
if ($simple_search_form->validate() && (empty($keyword))) {
	$keyword = $values['keyword'];
}
if (!empty($keyword)) {
	$cats= Category :: load($category);
	$allcat= array ();
	if ((isset($_GET['selectcat']) && $_GET['selectcat']==0) && isset($_GET['search'])) {
		$allcat= $cats[0]->get_subcategories(null);
		$allcat_info = Category    :: find_category($keyword,$allcat);
		$alleval=array();
		$alllink=array();
	} else {
		$alleval	 = Evaluation  :: find_evaluations($keyword, $cats[0]->get_id());
		$alllink	 = LinkFactory :: find_links($keyword, $cats[0]->get_id());
	}

} elseif (isset ($_GET['studentoverview'])) {
	$cats= Category :: load($category);
	$stud_id= (api_is_allowed_to_create_course() ? null : api_get_user_id());
	$allcat= array ();
	$alleval= $cats[0]->get_evaluations($stud_id, true);
	$alllink= $cats[0]->get_links($stud_id, true);
	if (isset ($_GET['exportpdf'])) {
		$datagen = new GradebookDataGenerator ($allcat,$alleval, $alllink);
		$header_names = array(get_lang('Name'),get_lang('Description'),get_lang('Weight'),get_lang('Date'),get_lang('Results'));
		$data_array = $datagen->get_data(GradebookDataGenerator :: GDG_SORT_NAME,0,null,true);
		$newarray = array();
		foreach ($data_array as $data) {
			$newarray[] = array_slice($data, 1);
		}
		$pdf= new Cezpdf();
		$pdf->selectFont(api_get_path(LIBRARY_PATH).'ezpdf/fonts/Courier.afm');
		$pdf->ezSetMargins(30, 30, 50, 30);
		$pdf->ezSetY(810);
		$pdf->ezText(get_lang('FlatView').' ('. date('j/n/Y g:i') .')',12,array('justification'=>'center'));
		$pdf->line(50,790,550,790);
		$pdf->line(50,40,550,40);
		$pdf->ezSetY(750);
		$pdf->ezTable($newarray,$header_names,'',array('showHeadings'=>1,'shaded'=>1,'showLines'=>1,'rowGap'=>3,'width'=> 500));
		$pdf->ezStream();
		exit;
	}
} elseif (!empty($_GET['export_certificate'])){
	$user_id = strval(intval($_GET['user']));
	if (!api_is_allowed_to_edit(true,true)) {
		$user_id = api_get_user_id();
	}
	$category = Category :: load (Security::remove_XSS($_GET['cat']));
	if ($category[0]->is_certificate_available($user_id)) {
		$user= get_user_info_from_id($user_id);
		$scoredisplay = ScoreDisplay :: instance();
		$scorecourse = $category[0]->calc_score($user_id);
		$scorecourse_display = (isset($scorecourse) ? $scoredisplay->display_score($scorecourse,SCORE_AVERAGE) : get_lang('NoResultsAvailable'));

		$cattotal = Category :: load(0);
		$scoretotal= $cattotal[0]->calc_score($user_id);
		$scoretotal_display = (isset($scoretotal) ? $scoredisplay->display_score($scoretotal,SCORE_PERCENT) : get_lang('NoResultsAvailable'));

		//prepare all necessary variables:
		$organization_name = api_get_setting('Institution');
		$portal_name = api_get_setting('siteName');
		$stud_fn = $user['firstname'];
		$stud_ln = $user['lastname'];
		$certif_text = sprintf(get_lang('CertificateWCertifiesStudentXFinishedCourseYWithGradeZ'),$organization_name,$stud_fn.' '.$stud_ln,$category[0]->get_name(),$scorecourse_display);
		$certif_text = str_replace("\\n","\n",$certif_text);
		$date = date('d/m/Y',time());

		$pdf= new Cezpdf('a4','landscape');
		$pdf->selectFont(api_get_path(LIBRARY_PATH).'ezpdf/fonts/Courier.afm');
		$pdf->ezSetMargins(30, 30, 50, 50);
		//line Y coordinates in landscape mode are upside down (500 is on top, 10 is on the bottom)
		$pdf->line(50,50,790,50);
		$pdf->line(50,550,790,550);
		$pdf->ezSetY(450);
		$pdf->ezImage(api_get_path(SYS_CODE_PATH).'img/dokeos_logo_certif.png',1,400,'','center','');
		$pdf->ezSetY(480);
		$pdf->ezText($certif_text,28,array('justification'=>'center'));
		//$pdf->ezSetY(750);
		$pdf->ezSetY(50);
		$pdf->ezText($date,18,array('justification'=>'center'));
		$pdf->ezSetY(580);
		$pdf->ezText($organization_name,22,array('justification'=>'left'));
		$pdf->ezSetY(580);
		$pdf->ezText($portal_name,22,array('justification'=>'right'));
		$pdf->ezStream();
	}
	exit;
} else {
	$cats= Category :: load($category);
	$stud_id= (api_is_allowed_to_create_course() ? null : api_get_user_id());
	$allcat= $cats[0]->get_subcategories($stud_id);
	$alleval= $cats[0]->get_evaluations($stud_id);
	$alllink= $cats[0]->get_links($stud_id);
}
$addparams = array ('selectcat' => $cats[0]->get_id());
if (isset($_GET['search'])) {
	$addparams['search'] = $keyword;
}
if (isset ($_GET['studentoverview'])) {
	$addparams['studentoverview'] = '';
}
if (count($allcat_info)>=0 && (isset($_GET['selectcat']) && $_GET['selectcat']==0) && isset($_GET['search']) && strlen(trim($_GET['search']))>0 ) {
	$allcat=$allcat_info;
} else {
	$allcat=$allcat;
}
$gradebooktable= new GradebookTable($cats[0], $allcat, $alleval, $alllink, $addparams);
if (((empty ($allcat)) && (empty ($alleval)) && (empty ($alllink)) && (!$is_platform_admin) && ($is_course_admin) && (!isset ($_GET['selectcat']))) && api_is_course_tutor()) {
	Display :: display_normal_message(get_lang('GradebookWelcomeMessage') . '<br /><br /><form name="createcat" method="post" action="' . api_get_self() . '?createallcategories=1"><input type="submit" value="' . get_lang('CreateAllCat') . '"></form>',false);
}
//here we are in a sub category
if ($category != '0') {
	DisplayGradebook :: display_header_gradebook($cats[0], 1, $_GET['selectcat'], $is_course_admin, $is_platform_admin, $simple_search_form);
} else {
	//this is the root category
	DisplayGradebook :: display_header_gradebook($cats[0], (((count($allcat) == '0') && (!isset ($_GET['search']))) ? 0 : 1), 0, $is_course_admin, $is_platform_admin, $simple_search_form);
}
$gradebooktable->display();
Display :: display_footer();
