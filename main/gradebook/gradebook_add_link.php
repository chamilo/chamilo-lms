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
//$cidReset = true;
require_once '../inc/global.inc.php';
require_once 'lib/be.inc.php';
require_once 'lib/gradebook_functions.inc.php';
require_once 'lib/fe/linkform.class.php';
require_once 'lib/fe/linkaddeditform.class.php';
require_once '../forum/forumfunction.inc.php';
$current_course_tool  = TOOL_GRADEBOOK;

api_protect_course_script();
api_block_anonymous_users();
block_students();

$course_info = api_get_course_info($_GET['course_code']);

$tbl_forum_thread = Database :: get_course_table(TABLE_FORUM_THREAD);
$tbl_link=Database::get_main_table(TABLE_MAIN_GRADEBOOK_LINK);

$session_id = api_get_session_id();
$all_categories = Category :: load(null, null, api_get_course_id(), null, null, $session_id);

$category = Category :: load($_GET['selectcat']);
$url = api_get_self() . '?selectcat=' . Security::remove_XSS($_GET['selectcat']). '&newtypeselected=' . (isset($_GET['typeselected']) ? Security::remove_XSS($_GET['typeselected']) : ''). '&course_code=' . (empty($_GET['course_code'])?'':Security::remove_XSS($_GET['course_code']));
$typeform = new LinkForm(LinkForm :: TYPE_CREATE, $category[0], null, 'create_link', null, $url , $_GET['typeselected']);

// if user selected a link type
if ($typeform->validate() && isset($_GET['newtypeselected'])) {
	// reload page, this time with a parameter indicating the selected type
	header('Location: '.api_get_self() . '?selectcat=' . Security::remove_XSS($_GET['selectcat'])
											 . '&typeselected='.$typeform->exportValue('select_link')
											 . '&course_code=' . Security::remove_XSS($_GET['course_code']));
	exit;
}

// link type selected, show 2nd form to retrieve the link data
if (isset($_GET['typeselected']) && $_GET['typeselected'] != '0') {
	$url = api_get_self() . '?selectcat=' . Security::remove_XSS($_GET['selectcat']).'&typeselected=' . Security::remove_XSS($_GET['typeselected']) . '&course_code=' . Security::remove_XSS($_GET['course_code']);
	$addform = new LinkAddEditForm(LinkAddEditForm :: TYPE_ADD, $all_categories, intval($_GET['typeselected']),null, 'add_link', $url);
    
	if ($addform->validate()) {
		$addvalues = $addform->exportValues();
        
		$link= LinkFactory :: create($_GET['typeselected']);
        
		$link->set_user_id(api_get_user_id());
		/*
		if ($category[0]->get_course_code() == '' && !empty($_GET['course_code'])) {
			$link->set_course_code($_GET['course_code']);
		} else {
			$link->set_course_code($category[0]->get_course_code());
		}*/
		$link->set_course_code(api_get_course_id());
		
		$link->set_category_id($addvalues['select_gradebook']);

		if ($link->needs_name_and_description()) {
			$link->set_name($addvalues['name']);
		} else {
			$link->set_ref_id($addvalues['select_link']);
		}
		$link->set_weight($addvalues['weight']);

		if ($link->needs_max()) {
			$link->set_max($addvalues['max']);
		}

		if ($link->needs_name_and_description()) {
			$link->set_description($addvalues['description']);
		}
		$link->set_visible(empty ($addvalues['visible']) ? 0 : 1);

		//update view_properties		
		if (isset($_GET['typeselected']) && 5 == $_GET['typeselected'] && (isset($addvalues['select_link']) && $addvalues['select_link']<>"")) {

			$sql1 = 'SELECT thread_title from '.$tbl_forum_thread.' 
					 WHERE c_id = '.$course_info['real_id'].' AND thread_id='.$addvalues['select_link'];
			$res1	= Database::query($sql1);
			$rowtit	= Database::fetch_row($res1);
			$course_id = api_get_course_id();
			$sql_l='SELECT count(*) FROM '.$tbl_link.' WHERE c_id = '.$course_info['real_id'].' AND ref_id='.$addvalues['select_link'].' and course_code="'.$course_id.'" and type=5;';
			$res_l=Database::query($sql_l);
			$row=Database::fetch_row($res_l);
			if ( $row[0]==0 ) {
				$link->add();
				$sql = 'UPDATE '.$tbl_forum_thread.' SET thread_qualify_max='.$addvalues['weight'].',thread_weight='.$addvalues['weight'].',thread_title_qualify="'.$rowtit[0].'" 
						WHERE thread_id='.$addvalues['select_link'].' AND c_id = '.$course_info['real_id'].' ';
				Database::query($sql);
			}
		}
        
		$link->add();
				
		$addvalue_result=!empty($addvalues['addresult'])?$addvalues['addresult']:array();
		if ($addvalue_result == 1) {
			header('Location: gradebook_add_result.php?selecteval=' . $link->get_ref_id());
			exit;
		} else {
			header('Location: '.Security::remove_XSS($_SESSION['gradebook_dest']).'?linkadded=&selectcat=' . Security::remove_XSS($_GET['selectcat']));
			exit;
		}
	}
}


$interbreadcrumb[]= array ('url' => $_SESSION['gradebook_dest'].'?selectcat=' .Security::remove_XSS($_GET['selectcat']),'name' => get_lang('Gradebook'));
$this_section = SECTION_COURSES;

$htmlHeadXtra[] = '<script type="text/javascript">
$(document).ready( function() {

    $("#hide_category_id").change(function() {        
       $("#hide_category_id option:selected").each(function () {
           var cat_id = $(this).val();
            $.ajax({ 
                url: "'.api_get_path(WEB_AJAX_PATH).'gradebook.ajax.php?a=get_gradebook_weight", 
                data: "cat_id="+cat_id,
                success: function(return_value) {
                    if (return_value != 0 ) {
                        $("#max_weight").html(return_value);                                             
                    }                    
                },            
            });    
       });
    });
});
</script>';

Display :: display_header(get_lang('MakeLink'));
if (isset ($typeform)) {
	$typeform->display();
}
if (isset ($addform)) {
	$addform->display();
}
Display :: display_footer();
