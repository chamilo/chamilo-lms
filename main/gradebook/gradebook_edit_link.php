<?php
/* For licensing terms, see /license.txt */
/**
 * Script
 * @package chamilo.gradebook
 */
/**
 * Init
 */
$language_file = array('gradebook','link');
//$cidReset = true;
require_once '../inc/global.inc.php';
require_once 'lib/be.inc.php';
require_once 'lib/gradebook_functions.inc.php';
require_once 'lib/fe/linkform.class.php';
require_once 'lib/fe/linkaddeditform.class.php';

api_block_anonymous_users();
block_students();
$tbl_grade_links = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_LINK);
//selected name of database
$course_id              = get_course_id_by_link_id($_GET['editlink']);
$tbl_forum_thread 		= Database :: get_course_table(TABLE_FORUM_THREAD);
$tbl_work 				= Database :: get_course_table(TABLE_STUDENT_PUBLICATION);
$tbl_attendance 		= Database :: get_course_table(TABLE_ATTENDANCE);
$linkarray 				= LinkFactory :: load($_GET['editlink']);
$link = $linkarray[0];

if ($link->is_locked() && !api_is_platform_admin()) {
    api_not_allowed();
}

$linkcat  = isset($_GET['selectcat']) ? Security::remove_XSS($_GET['selectcat']):'';
$linkedit = isset($_GET['editlink']) ? Security::remove_XSS($_GET['editlink']):'';

$cats = Category :: load(null, null, $course_code, null, null, $session_id, false); //already init

$form = new LinkAddEditForm(LinkAddEditForm :: TYPE_EDIT, $cats, null, $link, 'edit_link_form', api_get_self() . '?selectcat=' . $linkcat. '&editlink=' . $linkedit);
if ($form->validate()) {
	$values = $form->exportValues();    
    $parent_cat = Category :: load($values['select_gradebook']);    
        
    $final_weight = null;
    if ($parent_cat[0]->get_parent_id() == 0) {
        $final_weight = $values['weight_mask'];    
    } else {
        $cat = Category :: load($parent_cat[0]->get_parent_id());
        $global_weight = $cat[0]->get_weight();
        $final_weight = $values['weight_mask']/$global_weight*$parent_cat[0]->get_weight();        
    }
    
	$link->set_weight($final_weight);
    
    if (!empty($values['select_gradebook'])) {
        $link->set_category_id($values['select_gradebook']);
    }
	$link->set_visible(empty ($values['visible']) ? 0 : 1);
	$link->save();

	//Update weight for attendance
	$sql = 'SELECT ref_id FROM '.$tbl_grade_links.' WHERE id = '.intval($_GET['editlink']).' AND type='.LINK_ATTENDANCE;
	$rs_attendance  = Database::query($sql);
	if (Database::num_rows($rs_attendance) > 0) {
		$row_attendance = Database::fetch_array($rs_attendance);
		$attendance_id  = $row_attendance['ref_id'];
		$upd_attendance = 'UPDATE '.$tbl_attendance.' SET attendance_weight ='.floatval($final_weight).' WHERE c_id = '.$course_id.' AND  id = '.intval($attendance_id);
		Database::query($upd_attendance);
	}

	//Update weight into forum thread
	$sql_t = 'UPDATE '.$tbl_forum_thread.' SET thread_weight='.$final_weight.' 
			  WHERE c_id = '.$course_id.' AND thread_id=(SELECT ref_id FROM '.$tbl_grade_links.' WHERE id='.intval($_GET['editlink']).' and type=5) ';
    
	Database::query($sql_t);
    
	//Update weight into student publication(work)
	$sql_t = 'UPDATE '.$tbl_work.' SET weight='.$final_weight.' 
			  WHERE c_id = '.$course_id.' AND id = (SELECT ref_id FROM '.$tbl_grade_links.' WHERE id='.intval($_GET['editlink'] ).' AND type=3 )';
    
	Database::query($sql_t);
	header('Location: '.$_SESSION['gradebook_dest'].'?linkedited=&selectcat=' . $link->get_category_id());
	exit;
}

$interbreadcrumb[] = array ('url' => Security::remove_XSS($_SESSION['gradebook_dest']).'?selectcat='.$linkcat,'name' => get_lang('Gradebook'));
$htmlHeadXtra[] = '<script type="text/javascript">
$(document).ready( function() {

    $("#hide_category_id").change(function(){        
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

Display :: display_header(get_lang('EditLink'));
$form->display();
Display :: display_footer();