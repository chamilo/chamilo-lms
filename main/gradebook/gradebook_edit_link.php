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
$course_id 		= get_course_id_by_link_id(Security::remove_XSS($_GET['editlink']));
$tbl_forum_thread 		= Database :: get_course_table(TABLE_FORUM_THREAD);
$tbl_work 				= Database :: get_course_table(TABLE_STUDENT_PUBLICATION);
$tbl_attendance 		= Database :: get_course_table(TABLE_ATTENDANCE);
$linkarray 				= LinkFactory :: load(Security::remove_XSS($_GET['editlink']));
$link = $linkarray[0];
$linkcat  = isset($_GET['selectcat']) ? Security::remove_XSS($_GET['selectcat']):'';
$linkedit = isset($_GET['editlink']) ? Security::remove_XSS($_GET['editlink']):'';

$form = new LinkAddEditForm(LinkAddEditForm :: TYPE_EDIT,
							null,
							null,
							$link,
							'edit_link_form',
							api_get_self() . '?selectcat=' . $linkcat
												 . '&editlink=' . $linkedit);

if ($form->validate()) {
	$values = $form->exportValues();
	$link->set_weight($values['weight']);
	$link->set_visible(empty ($values['visible']) ? 0 : 1);
	$link->save();

	//Update weight for attendance
	$sql = 'SELECT ref_id FROM '.$tbl_grade_links.' WHERE c_id = '.$course_id.' AND  id = '.intval($_GET['editlink']).' AND type='.LINK_ATTENDANCE;
	$rs_attendance  = Database::query($sql);
	if (Database::num_rows($rs_attendance) > 0) {
		$row_attendance = Database::fetch_array($rs_attendance);
		$attendance_id  = $row_attendance['ref_id'];
		$upd_attendance = 'UPDATE '.$tbl_attendance.' SET attendance_weight ='.floatval($values['weight']).' WHERE c_id = '.$course_id.' AND  id = '.intval($attendance_id);
		Database::query($upd_attendance);
	}

	//Update weight into forum thread
	$sql_t = 'UPDATE '.$tbl_forum_thread.' SET thread_weight='.$values['weight'].' 
			  WHERE c_id = '.$course_id.' AND thread_id=(SELECT ref_id FROM '.$tbl_grade_links.' WHERE id='.intval($_GET['editlink']).' and type=5 AND c_id = '.$course_id.'  ) ';
	Database::query($sql_t);
	//Update weight into student publication(work)
	$sql_t = 'UPDATE '.$tbl_work.' SET weight='.$values['weight'].' 
			  WHERE c_id = '.$course_id.' AND id = (SELECT ref_id FROM '.$tbl_grade_links.' WHERE c_id = '.$course_id.' AND id='.intval($_GET['editlink'] ).' AND  type=3 )';
	Database::query($sql_t);
	header('Location: '.$_SESSION['gradebook_dest'].'?linkedited=&selectcat=' . $link->get_category_id());
	exit;
}

$interbreadcrumb[] = array ('url' => Security::remove_XSS($_SESSION['gradebook_dest']).'?selectcat='.$linkcat,'name' => get_lang('Gradebook'));

Display :: display_header(get_lang('EditLink'));
$form->display();
Display :: display_footer();