<?php
/* For licensing terms, see /license.txt */
/**
 * 
 * @package chamilo.gradebook
 */
$language_file= 'gradebook';
// $cidReset : This is the main difference with gradebook.php, here we say,
// basically, that we are inside a course, and many things depend from that
$cidReset= false;
$_in_course = true;
require_once '../inc/global.inc.php';
$current_course_tool  = TOOL_GRADEBOOK;

api_protect_course_script();

$course_code 	= api_get_course_id();
$stud_id        = api_get_user_id();
$session_id		= api_get_session_id();

//make sure the destination for scripts is index.php instead of gradebook.php
$_SESSION['gradebook_dest'] = 'index.php';

$this_section = SECTION_COURSES;

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
require_once api_get_path(LIBRARY_PATH).'gradebook.lib.php';

/*
$htmlHeadXtra[] = api_get_css(api_get_path(WEB_LIBRARY_PATH).'javascript/jqplot/jquery.jqplot.min.css');
$htmlHeadXtra[] = api_get_js('jqplot/jquery.jqplot.min.js');
$htmlHeadXtra[] = api_get_js('jqplot/plugins/jqplot.donutRenderer.min.js');*/

$htmlHeadXtra[] = '<script>
    
var show_icon = "../img/view_more_stats.gif";
var hide_icon = "../img/view_less_stats.gif";

$(document).ready(function() {

    $(".view_children").live("click", function() {
        var id = $(this).attr("data-cat-id");
        $(".hidden_"+id).removeClass("hidden");    
        $(this).removeClass("view_children");        
        $(this).find("img").attr("src", hide_icon);        
        $(this).attr("class", "hide_children");
    });

    $(".hide_children").live("click", function(event) {    
        var id = $(this).attr("data-cat-id");        
        $(".hidden_"+id).addClass("hidden");    
        $(this).removeClass("hide_children");
        $(this).addClass("view_children");
        $(this).find("img").attr("src", show_icon);
    });
 
    
    

/*
  var s1 = [["a",25]];
  var s2 = [["a", 0], ["a", 10], ["a", 10], ["a", 5]];
     
  var plot3 = $.jqplot("chart3", [s1, s2], {        
  colors: ["#000", "#fff"],
    seriesDefaults: {
      // make this a donut chart.
      renderer:$.jqplot.DonutRenderer,
      rendererOptions:{
        // Donuts can be cut into slices like pies.
        sliceMargin: 3 ,
        // Pies and donuts can start at any arbitrary angle.
        startAngle: -90,
        showDataLabels: true,
        // By default, data labels show the percentage of the donut/pie.
        // You can show the data "value" or data "label" instead.
        dataLabels: "value"
        
      }
    }
  });*/

	for (i=0;i<$(".actions").length;i++) {
		if ($(".actions:eq("+i+")").html()=="<table border=\"0\"></table>" || $(".actions:eq("+i+")").html()=="" || $(".actions:eq("+i+")").html()==null || $(".actions:eq("+i+")").html().split("<TBODY></TBODY>").length==2) {
			$(".actions:eq("+i+")").hide();
		}
	}
});
</script>';
api_block_anonymous_users();
$htmlHeadXtra[]= '<script type="text/javascript">
function confirmation() {
	if (confirm("' . get_lang('DeleteAll') . '?")) {
		return true;
	} else {
		return false;
	}
}
</script>';

$tbl_forum_thread = Database :: get_course_table(TABLE_FORUM_THREAD);
$tbl_attendance   = Database :: get_course_table(TABLE_ATTENDANCE);
$tbl_grade_links  = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_LINK);
//$status           = CourseManager::get_user_in_course_status($stud_id, $course_code);
$filter_confirm_msg = true;
$filter_warning_msg = true;

///direct access to one evaluation
$cats = Category :: load(null, null, $course_code, null, null, $session_id, false); //already init
$first_time = null; 
if (empty($cats)) {
	$cats = Category :: load(0, null, $course_code, null, null, $session_id, false);//first time
	$first_time=1;
}
$_GET['selectcat'] = $cats[0]->get_id();

if (isset($_GET['isStudentView'])) {
	if ( (isset($_GET['selectcat']) && $_GET['selectcat']>0) && (isset($_SESSION['studentview']) && $_SESSION['studentview']=='studentview') ) {
		$interbreadcrumb[]= array ('url' => 'index.php'.'?selectcat=0&amp;isStudentView='.$_GET['isStudentView'],'name' => get_lang('ToolGradebook'));
	}
}

if ( (isset($_GET['selectcat']) && $_GET['selectcat']>0) && (isset($_SESSION['studentview']) && $_SESSION['studentview']=='studentview') ) {
	Display :: display_header();

	//Introduction tool: student view
	Display::display_introduction_section(TOOL_GRADEBOOK, array('ToolbarSet' => 'AssessmentsIntroduction'));

	$category= $_GET['selectcat'];		
	
	$cats = Category :: load ($category, null, null, null, null, null, false);
	$allcat= $cats[0]->get_subcategories($stud_id, $course_code, $session_id);
	$alleval= $cats[0]->get_evaluations($stud_id);
	$alllink= $cats[0]->get_links($stud_id);
	$addparams=array();
	$gradebooktable= new GradebookTable($cats[0], $allcat, $alleval,$alllink, $addparams);
	$gradebooktable->display();
	Display :: display_footer();
	exit;
} else {
	if ( !isset($_GET['selectcat']) && ($_SESSION['studentview']=='studentview') || (isset($_GET['isStudentView']) && $_GET['isStudentView']=='true') ) {
		//	if ( !isset($_GET['selectcat']) && ($_SESSION['studentview']=='studentview') && ($status<>1 && !api_is_platform_admin()) || (isset($_GET['isStudentView']) && $_GET['isStudentView']=='true' && $status<>1 && !api_is_platform_admin()) ) {
		Display :: display_header(get_lang('Gradebook'));

		//Introduction tool: student view
		Display::display_introduction_section(TOOL_GRADEBOOK, array('ToolbarSet' => 'AssessmentsIntroduction'));
		$addparams=array();
		$cats = Category :: load (0, null, null, null, null, null, false);
		$allcat= $cats[0]->get_subcategories($stud_id, $course_code, $session_id);
		$alleval= $cats[0]->get_evaluations($stud_id);
		$alllink= $cats[0]->get_links($stud_id);
		$gradebooktable= new GradebookTable($cats[0], $allcat, $alleval,$alllink, $addparams);
		$gradebooktable->display();
		Display :: display_footer();
		exit;
	}
}

// ACTIONS
//this is called when there is no data for the course admin
if (isset ($_GET['createallcategories'])) {
	block_students();
	$coursecat= Category :: get_not_created_course_categories($stud_id);
	if (!count($coursecat) == 0) {

		foreach ($coursecat as $row) {
			$cat= new Category();
			$cat->set_name($row[1]);
			$cat->set_course_code($row[0]);
			$cat->set_description(null);
			$cat->set_user_id($stud_id);
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

//show logs evaluations
if (isset ($_GET['visiblelog'])) {
	header('Location: ' . api_get_self().'/gradebook_showlog_eval.php');
	exit;
}

//move a category
if (isset ($_GET['movecat'])) {
	block_students();
	$cats= Category :: load($_GET['movecat']);
	if (!isset ($_GET['targetcat'])) {
		$move_form= new CatForm(CatForm :: TYPE_MOVE,
		$cats[0],
		'move_cat_form',
		null,
		api_get_self() . '?movecat=' . Security::remove_XSS($_GET['movecat'])
		. '&selectcat=' . Security::remove_XSS($_GET['selectcat']));
			if ($move_form->validate()) {
				header('Location: ' . api_get_self() . '?selectcat=' . Security::remove_XSS($_GET['selectcat'])
				 . '&movecat=' . Security::remove_XSS($_GET['movecat'])
				 . '&targetcat=' . $move_form->exportValue('move_cat'));
				 exit;
			}
		} else {
			$targetcat= Category :: load($_GET['targetcat']);
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
	$evals= Evaluation :: load($_GET['moveeval']);
	if (!isset ($_GET['targetcat'])) {

		$move_form= new EvalForm(EvalForm :: TYPE_MOVE,
		$evals[0],
		null,
		'move_eval_form',
		null,
		api_get_self() . '?moveeval=' . Security::remove_XSS($_GET['moveeval'])
		. '&selectcat=' . Security::remove_XSS($_GET['selectcat']));

		if ($move_form->validate()) {
			header('Location: ' .api_get_self() . '?selectcat=' . Security::remove_XSS($_GET['selectcat'])
			. '&moveeval=' . Security::remove_XSS($_GET['moveeval'])
			. '&targetcat=' . $move_form->exportValue('move_cat'));
			exit;
			}
		} else {
		$targetcat= Category :: load($_GET['targetcat']);
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
	$link= LinkFactory :: load($_GET['movelink']);
	$move_form= new LinkForm(LinkForm :: TYPE_MOVE, null, $link[0], 'move_link_form', null, api_get_self() . '?movelink=' . $_GET['movelink'] . '&selectcat=' . Security::remove_XSS($_GET['selectcat']));

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
	$cats= Category :: load($_GET['visiblecat']);
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
if (isset($_GET['deletecat'])) {
	block_students();
	$cats = Category :: load($_GET['deletecat']);
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
	$eval= Evaluation :: load($_GET['visibleeval']);
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
//parameters for evaluations
if (isset($_GET['lockedeval'])) {
	block_students();
	$locked = Security::remove_XSS($_GET['lockedeval']);
	if (isset($_GET['typelocked']) && api_is_platform_admin()){
		$type_locked = 0;
		$confirmation_message = get_lang('EvaluationHasBeenUnLocked');
	} else {
		$type_locked = 1;
		$confirmation_message = get_lang('EvaluationHasBeenLocked');
	}
	$eval = Evaluation :: load($locked);
	if ($eval[0] != null) {
		$eval[0]->lock($type_locked);
	}
	
	$filter_confirm_msg = false;	

}
if (isset ($_GET['deleteeval'])) {
	block_students();
	$eval= Evaluation :: load($_GET['deleteeval']);
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
	} else {
		$visibility_command= 0;
	}
	$link= LinkFactory :: load($_GET['visiblelink']);
	if (isset($link) && isset($link[0])) {
		$link[0]->set_visible($visibility_command);
		$link[0]->save();
	}
	unset ($link);
	if ($visibility_command) {
		$confirmation_message = get_lang('ViMod');
		$filter_confirm_msg = false;
	} else {
		$confirmation_message = get_lang('InViMod');
		$filter_confirm_msg = false;
	}
}

$course_id = api_get_course_int_id();

if (isset ($_GET['deletelink'])) {
	block_students();
	$get_delete_link=Security::remove_XSS($_GET['deletelink']);
	//fixing #5229
	if (!empty($get_delete_link)) {
		$link= LinkFactory :: load($get_delete_link);
		if ($link[0] != null) {
			// clean forum qualify
			$sql = 'UPDATE '.$tbl_forum_thread.' SET thread_qualify_max=0,thread_weight=0,thread_title_qualify="" 
					WHERE c_id = '.$course_id.' AND thread_id = (SELECT ref_id FROM '.$tbl_grade_links.' WHERE id='.$get_delete_link.' AND type = '.LINK_FORUM_THREAD.');';
			Database::query($sql);
			// clean attendance
			$sql = 'UPDATE '.$tbl_attendance.' SET attendance_qualify_max=0, attendance_weight = 0, attendance_qualify_title="" 
				 	WHERE c_id = '.$course_id.' AND id = (SELECT ref_id FROM '.$tbl_grade_links.' WHERE id='.$get_delete_link.' AND type = '.LINK_ATTENDANCE.');';
			Database::query($sql);
			$link[0]->delete();
		}
		unset ($link);
		$confirmation_message = get_lang('LinkDeleted');
		$filter_confirm_msg = false;
	}
}

if (!empty($course_to_crsind) && !isset($_GET['confirm'])) {
	block_students();

	if (!isset($_GET['movecat']) && !isset($_GET['moveeval'])) {
		die ('Error: movecat or moveeval not defined');
	}
	$button = '<form name="confirm" method="post" action="'.api_get_self() .'?confirm='
					.(isset($_GET['movecat']) ? '&movecat=' . Security::remove_XSS($_GET['movecat'])
					: '&moveeval=' . Security::remove_XSS($_GET['moveeval']) )
					.'&selectcat=' . Security::remove_XSS($_GET['selectcat'])
					.'&targetcat=' . Security::remove_XSS($_GET['targetcat']).'">
			   <input type="submit" value="'.get_lang('Ok').'">
			   </form>';
	$warning_message = get_lang('MoveWarning').'<br><br>'.$button;
	$filter_warning_msg = false;
}

$action = isset($_GET['action']) ? $_GET['action'] : null;

switch ($action) {
    case 'lock':
        $category_to_lock = Category :: load($_GET['category_id']);
        $category_to_lock[0]->lock_all_items(1);
        $confirmation_message = get_lang('GradebookLockedAlert');
        break;
    case 'unlock':
        if (api_is_platform_admin()) {
            $category_to_lock = Category :: load($_GET['category_id']);
            $category_to_lock[0]->lock_all_items(0);
            $confirmation_message = get_lang('EvaluationHasBeenUnLocked');
        }
        break;    
}

//actions on the sortabletable
if (isset ($_POST['action'])) {
	block_students();
	$number_of_selected_items= count($_POST['id']);

	if ($number_of_selected_items == '0') {
		$warning_message = get_lang('NoItemsSelected');
		$filter_warning_msg = false;
	} else {
		switch ($_POST['action']) {
 
			case 'deleted' :
				$number_of_deleted_categories= 0;
				$number_of_deleted_evaluations= 0;
				$number_of_deleted_links= 0;
				foreach ($_POST['id'] as $indexstr) {
					if (substr($indexstr, 0, 4) == 'CATE') {
						$cats= Category :: load(substr($indexstr, 4));
						if ($cats[0] != null) {
							$cats[0]->delete_all();
						}
						$number_of_deleted_categories++;
					}
					if (substr($indexstr, 0, 4) == 'EVAL') {
						$eval= Evaluation :: load(substr($indexstr, 4));
						if ($eval[0] != null) {
							$eval[0]->delete_with_results();
						}

						$number_of_deleted_evaluations++;
					}
					if (substr($indexstr, 0, 4) == 'LINK') {
						//fixing #5229
						$id = substr($indexstr, 4);
						if (!empty($id)) {
							$link= LinkFactory :: load($id);
							if ($link[0] != null) {
								$link[0]->delete();
							}
							$number_of_deleted_links++;
						}
					}
				}
				$confirmation_message = get_lang('DeletedCategories') . ' : <b>' . $number_of_deleted_categories . '</b><br />' . get_lang('DeletedEvaluations') . ' : <b>' . $number_of_deleted_evaluations . '</b><br />' . get_lang('DeletedLinks') . ' : <b>' . $number_of_deleted_links . '</b><br /><br />' . get_lang('TotalItems') . ' : <b>' . $number_of_selected_items . '</b>';
				$filter_confirm_msg = false;
				break;
			case 'setvisible' :
				foreach ($_POST['id'] as $indexstr) {
					if (substr($indexstr, 0, 4) == 'CATE') {
						$cats= Category :: load(substr($indexstr, 4));
						$cats[0]->set_visible(1);
						$cats[0]->save();
						$cats[0]->apply_visibility_to_children();
					}
					if (substr($indexstr, 0, 4) == 'EVAL') {
						$eval= Evaluation :: load(substr($indexstr, 4));
						$eval[0]->set_visible(1);
						$eval[0]->save();
					}
					if (substr($indexstr, 0, 4) == 'LINK') {
						$link= LinkFactory :: load(substr($indexstr, 4));
						$link[0]->set_visible(1);
						$link[0]->save();
					}
				}
				$confirmation_message = get_lang('ItemsVisible');
				$filter_confirm_msg = false;
				break;
			case 'setinvisible' :
				foreach ($_POST['id'] as $indexstr) {
					if (substr($indexstr, 0, 4) == 'CATE') {
						$cats= Category :: load(substr($indexstr, 4));
						$cats[0]->set_visible(0);
						$cats[0]->save();
						$cats[0]->apply_visibility_to_children();
					}
					if (substr($indexstr, 0, 4) == 'EVAL') {
						$eval= Evaluation :: load(substr($indexstr, 4));
						$eval[0]->set_visible(0);
						$eval[0]->save();
					}
					if (substr($indexstr, 0, 4) == 'LINK') {
						$link= LinkFactory :: load(substr($indexstr, 4));
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


// DISPLAY HEADERS AND MESSAGES
if (!isset($_GET['exportpdf'])) {
	if (isset ($_GET['studentoverview'])) {
		$interbreadcrumb[]= array ('url' => $_SESSION['gradebook_dest'].'?selectcat=' . Security::remove_XSS($_GET['selectcat']),'name' => get_lang('ToolGradebook'));
		Display :: display_header(get_lang('FlatView'));
	} elseif (isset ($_GET['search'])) {
		$interbreadcrumb[]= array ('url' => $_SESSION['gradebook_dest'].'?selectcat=' . Security::remove_XSS($_GET['selectcat']),'name' => get_lang('ToolGradebook'));
		Display :: display_header(get_lang('SearchResults'));
	} elseif(isset ($_GET['selectcat'])) {
		$interbreadcrumb[]= array (	'url' =>'#','name' => get_lang('ToolGradebook'));
		if (!isset($_GET['gradebooklist_direction'])) {
			//$interbreadcrumb[]= array ('url' => $_SESSION['gradebook_dest'].'?selectcat=' . Security::remove_XSS($_GET['selectcat']),'name' => get_lang('Details'));
		}
		Display :: display_header('');
	} else {
		Display :: display_header(get_lang('ToolGradebook'));
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
if (isset ($_GET['nolinkitems'])){
	Display :: display_warning_message(get_lang('NoLinkItems'),false);
}
if (isset ($_GET['addallcat'])){
	Display :: display_normal_message(get_lang('AddAllCat'),false);
}
if (isset ($confirmation_message)){
	Display :: display_confirmation_message($confirmation_message,$filter_confirm_msg);
}
if (isset ($warning_message)){
	Display :: display_warning_message($warning_message,$filter_warning_msg);
}
if (isset ($move_form)){
	Display :: display_normal_message($move_form->toHtml(),false);
}

// LOAD DATA & DISPLAY TABLE

$is_platform_admin  = api_is_platform_admin();
$is_course_admin    = api_is_allowed_to_edit(null, true);

//load data for category, evaluation and links
if (empty ($_GET['selectcat'])) {
	$category= 0;
} else {
	$category= $_GET['selectcat'];
}
$simple_search_form='';

if (isset ($_GET['studentoverview'])) {    
    //@todo this code also seems to be deprecated ...    
	$cats= Category :: load($category);
	$stud_id= (api_is_allowed_to_edit() ? null : $stud_id);
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
		$pdf->ezText(get_lang('FlatView').' ('. api_convert_and_format_date(null, DATE_FORMAT_SHORT). ' ' . api_convert_and_format_date(null, TIME_NO_SEC_FORMAT) .')',12,array('justification'=>'center'));
		$pdf->line(50,790,550,790);
		$pdf->line(50,40,550,40);
		$pdf->ezSetY(750);
		$pdf->ezTable($newarray,$header_names,'',array('showHeadings'=>1,'shaded'=>1,'showLines'=>1,'rowGap'=>3,'width'=> 500));
		$pdf->ezStream();
		exit;
    }
} else {
    //Student view
    
    //in any other case (no search, no pdf), print the available gradebooks
    // Important note: loading a category will actually load the *contents* of
    // this category. This means that, to show the categories of a course,
    // we have to show the root category and show its subcategories that
    // are inside this course. This is done at the time of calling
    // $cats[0]->get_subcategories(), not at the time of doing Category::load()
    // $category comes from GET['selectcat']    
    
    //if $category = 0 (which happens when GET['selectcat'] is undefined)
    // then Category::load() will create a new 'root' category with empty
    // course and session fields in memory (Category::create_root_category())
    if ($_in_course === true) {
        // When *inside* a course, we want to make sure there is one (and only
        // one) category for this course or for this session.

		//hack for delete a gradebook from inside course
		/*
		$clean_deletecat = isset($_GET['deletecat']) ? intval($_GET['deletecat']) : null;
		if (!empty($clean_deletecat)) {
			exit;
		}
		//end hack*/

	    $cats = Category :: load(null, null, $course_code, null, null, $session_id, false);
        if (empty($cats)) {
            // There is no category for this course+session, so create one
            $cat= new Category();     
            if (!empty($session_id)) {            	
                $s_name = api_get_session_name($session_id);
            	$cat->set_name($course_code.' - '.get_lang('Session').' '.$s_name);
                $cat->set_session_id($session_id);
            } else {
                $cat->set_name($course_code);
            }
            $cat->set_course_code($course_code);
            $cat->set_description(null);
            $cat->set_user_id($stud_id);
            $cat->set_parent_id(0);
            $cat->set_weight(100);
            $cat->set_visible(0);
            $can_edit = api_is_allowed_to_edit(true, true);
            if ($can_edit) {
                $cat->add();
            }
            unset ($cat);
        }
        unset($cats);
    }
    $cats = Category :: load ($category, null, null, null, null, null, false);

    //with this fix the teacher only can view 1 gradebook
    if (api_is_platform_admin()) {
        $stud_id = (api_is_allowed_to_edit() ? null : api_get_user_id());
    } else {
    	$stud_id = $stud_id;
    }
    
	$allcat  = $cats[0]->get_subcategories($stud_id, $course_code, $session_id);
	$alleval = $cats[0]->get_evaluations($stud_id);
	$alllink = $cats[0]->get_links($stud_id);
    //whether we found a category or not, we now have a category object with
    // empty or full subcats
}

// add params to the future links (in the table shown)
$addparams = array ('selectcat' => $cats[0]->get_id());

if (isset ($_GET['studentoverview'])) {
	$addparams['studentoverview'] = '';
}
//$addparams['cidReq']='';
if (isset($_GET['cidReq']) && $_GET['cidReq']!='') {
	$addparams['cidReq']=Security::remove_XSS($_GET['cidReq']);
} else {
	$addparams['cidReq']='';
}
$no_qualification = false;

//here we are in a sub category
if ($category != '0') {
	$cat = new Category();
	$category_id   = intval($_GET['selectcat']);
	$course_id     = Database::get_course_by_category($category_id);
	$show_message  = $cat->show_message_resource_delete($course_id);
    
	if ($show_message=='') {

		//student
		if (!api_is_allowed_to_edit()) {

			// generating the total score for a course			
			$cats_course     = Category :: load ($category_id, null, null, null, null, null, false);	
                        		
			$alleval_course  = $cats_course[0]->get_evaluations($stud_id,true);
			$alllink_course  = $cats_course[0]->get_links($stud_id,true);
			
			$evals_links = array_merge($alleval_course, $alllink_course);
			$item_value = 0;
			$item_total = 0;
            
            //@todo move these in a function            
            $sum_categories_weight_array = array();     
            if (isset($cats_course) && !empty($cats_course)) {            
                $categories = Category::load(null, null, null, $category_id);
                if (!empty($categories)) {
                    foreach($categories as $category) {                  
                        $sum_categories_weight_array[$category->get_id()] = $category->get_weight();
                    }
                } else {
                    $sum_categories_weight_array[$category_id] = $cats_course[0]->get_weight();
                }
            }
              
			$item_total_value = 0;      
            $item_value = 0;      
           
			for ($count=0; $count < count($evals_links); $count++) {
				$item = $evals_links[$count];
				$score = $item->calc_score($stud_id);
				
				$score_denom    = ($score[1]==0) ? 1 : $score[1];
				$item_value     = $score[0]/$score_denom*$item->get_weight();
                
                $item_total         += $item->get_weight();                
                $item_total_value   += $item_value;                                
			}			
                        
		    $item_total_value = (float)$item_total_value;            
			
			$cattotal = Category :: load($category_id);
            
			$scoretotal= $cattotal[0]->calc_score(api_get_user_id());            
					
			//Do not remove this the gradebook/lib/fe/gradebooktable.class.php file load this variable as a global
            $scoredisplay = ScoreDisplay :: instance();
            
			//$my_score_in_gradebook =  round($scoretotal[0],2);
            $my_score_in_gradebook = $scoredisplay->display_score($scoretotal, SCORE_SIMPLE);
			
			//Show certificate
			$certificate_min_score = $cats[0]->get_certificate_min_score();	            		
			
			$scoretotal_display = $scoredisplay->display_score($scoretotal, SCORE_DIV_PERCENT); //a student always sees only the teacher's repartition
			//var_dump($certificate_min_score, $item_total_value);
			if (isset($certificate_min_score) && $item_total_value >= $certificate_min_score) {
				$my_certificate = get_certificate_by_user_id($cats[0]->get_id(), api_get_user_id());								
				if (empty($my_certificate)) {
					register_user_info_about_certificate($category_id, api_get_user_id(), $my_score_in_gradebook, api_get_utc_datetime());
					$my_certificate = get_certificate_by_user_id($cats[0]->get_id(), api_get_user_id());
				}
				
				if (!empty($my_certificate)) {
					$url  = api_get_path(WEB_PATH) .'certificates/index.php?id='.$my_certificate['id'];	
					$certificates = Display::url(Display::return_icon('certificate.png', get_lang('Certificates'), array(), 32), $url, array('target'=>'_blank'));					
					echo '<div class="actions" align="right">';					
					echo Display::url($url, $url, array('target'=>'_blank'));
					echo $certificates;
					echo '</div>';
				}
			}
		} //end hack
	}
}

if (api_is_allowed_to_edit(null, true)) {
	// Tool introduction
	Display::display_introduction_section(TOOL_GRADEBOOK, array('ToolbarSet' => 'AssessmentsIntroduction'));

	if ( (isset ($_GET['selectcat']) && $_GET['selectcat']<>0) ) {	    
	//
	} else {	    
        if (((isset ($_GET['selectcat']) && $_GET['selectcat']==0) || ((isset($_GET['cidReq']) && $_GET['cidReq']!==''))) || isset($_GET['isStudentView']) && $_GET['isStudentView']=='false') {
            $cats = Category :: load(null, null, $course_code, null, null, $session_id, false);		
		}
	}
}

if (isset($first_time) && $first_time==1 && api_is_allowed_to_edit(null,true)) {
	echo '<meta http-equiv="refresh" content="0;url='.api_get_self().'?cidReq='.$course_code.'" />';
} else {    
    $cats = Category :: load(null, null, $course_code, null, null, $session_id, false); //already init
    
	if (!empty($cats)) {
        
        if ( (api_get_setting('gradebook_enable_grade_model') == 'true') && 
             (api_is_platform_admin() || (api_is_allowed_to_edit(null, true) && api_get_setting('teachers_can_change_grade_model_settings') == 'true'))) {
            
            //Getting grade models
            $obj = new GradeModel();
            $grade_models = $obj->get_all();
            $grade_model_id = $cats[0]->get_grade_model_id();        
                        
            //No children
            if (count($cats) == 1 && empty($grade_model_id)) {
                if (!empty($grade_models)) {                   
                    $form_grade = new FormValidator('grade_model_settings');                    
                    $obj->fill_grade_model_select_in_form($form_grade, 'grade_model_id');
                    $form_grade->addElement('style_submit_button', 'submit', get_lang('Save'), 'class="save"');
                    
                    if ($form_grade->validate()) {
                        $value = $form_grade->exportValue('grade_model_id');
                        
                        $gradebook = new Gradebook();
                        $gradebook->update(array('id'=> $cats[0]->get_id(), 'grade_model_id' => $value), true);                 

                        //do something                        
                        $obj = new GradeModel();                             
                        $components = $obj->get_components($value);

                        foreach ($components as $component) {
                            $gradebook =  new Gradebook();
                            $params = array();

                            $params['name']             = $component['acronym'];
                            $params['description']      = $component['title'];
                            $params['user_id']          = api_get_user_id();
                            $params['parent_id']        = $cats[0]->get_id();
                            $params['weight']           = $component['percentage'];
                            $params['session_id']       = api_get_session_id();
                            $params['course_code']      = api_get_course_id();
                            $params['grade_model_id']   = api_get_session_id();

                            $gradebook->save($params);
                        }
                        //Reloading cats
                        $cats = Category :: load(null, null, $course_code, null, null, $session_id, false);
                    } else {
                        $form_grade->display();   
                    }
                }
            }
        }
        
		$i = 0;
        
		foreach ($cats as $cat) {			
			$allcat  = $cat->get_subcategories($stud_id, $course_code, $session_id);            
			$alleval = $cat->get_evaluations($stud_id);
			$alllink = $cat->get_links($stud_id);
			
			if ($cat->get_parent_id() != 0 ) {
				$i++;
			} else {
				//This is the father				
				//Create gradebook/add gradebook links
                DisplayGradebook::display_header_gradebook($cat, 0, $cat->get_id(), $is_course_admin, $is_platform_admin, $simple_search_form, false, true);				
				
				if (api_is_allowed_to_edit(null,true) && api_get_setting('gradebook_enable_grade_model') == 'true') {
					//Showing the grading system
					if (!empty($grade_models[$grade_model_id])) {             
                        Display::display_normal_message(get_lang('GradeModel').': '.$grade_models[$grade_model_id]['name']);
                    }					
				}                                
				$gradebooktable = new GradebookTable($cat, $allcat, $alleval, $alllink, $addparams);                
				$gradebooktable->display();				
			}
		}
	}
}
Display :: display_footer();