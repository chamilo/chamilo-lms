<?php
/* For licensing terms, see /license.txt */
/**
 * Script
 * @package chamilo.gradebook
 */
/**
 * Init
 */

$language_file= 'gradebook';
$cidReset= true;
require_once '../inc/global.inc.php';
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
require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/gradebook_functions.inc.php';

api_block_anonymous_users();
if (!api_is_allowed_to_create_course()) {
	header('Location: /index.php');
	exit;
}

$my_selectcat=isset($_GET['selectcat']) ? Security::remove_XSS($_GET['selectcat']) : '';

if (empty($my_selectcat)) {
	api_not_allowed();
}

// 	DISPLAY HEADERS AND MESSAGES                           -

if (!isset($_GET['exportpdf']) and !isset($_GET['export_certificate'])) {
	if (isset ($_GET['studentoverview'])) {
		$interbreadcrumb[]= array ('url' => Security::remove_XSS($_SESSION['gradebook_dest']).'?selectcat=' . Security::remove_XSS($_GET['selectcat']),'name' => get_lang('Gradebook'));
		Display :: display_header(get_lang('FlatView'));
	} elseif (isset ($_GET['search'])) {
		$interbreadcrumb[]= array ('url' => Security::remove_XSS($_SESSION['gradebook_dest']).'?selectcat=' . Security::remove_XSS($_GET['selectcat']),'name' => get_lang('Gradebook'));
		Display :: display_header(get_lang('SearchResults'));
	} else {
		$interbreadcrumb[] = array ('url' => Security::remove_XSS($_SESSION['gradebook_dest']).'?selectcat=1', 'name' => get_lang('Gradebook'));
		$interbreadcrumb[] = array ('url' => Security::remove_XSS($_SESSION['gradebook_dest']).'?&selectcat='.Security::remove_XSS($_GET['selectcat']),'name' => get_lang('EditAllWeights'));
		Display :: display_header('');
	}
}



$table_link           = Database::get_main_table(TABLE_MAIN_GRADEBOOK_LINK);
$table_evaluation     = Database::get_main_table(TABLE_MAIN_GRADEBOOK_EVALUATION);
//$table_forum_thread=Database::get_course_table(TABLE_FORUM_THREAD);

$my_db_name=get_database_name_by_link_id($my_selectcat);
$tbl_forum_thread     = Database :: get_course_table(TABLE_FORUM_THREAD,$my_db_name);
$tbl_work             = Database :: get_course_table(TABLE_STUDENT_PUBLICATION,$my_db_name);
$tbl_attendance       = Database :: get_course_table(TABLE_ATTENDANCE,$my_db_name);
/*
if($_SERVER['REQUEST_METHOD']=='POST'):
	foreach($_POST['link'] as $key => $value){
		Database::query('UPDATE '.$table_link.' SET weight = '."'".$value."'".' WHERE id = '.$key);
	}
	foreach($_POST['evaluation'] as $key => $value){
		Database::query('UPDATE '.$table_evaluation.' SET weight = '."'".$value."'".' WHERE id = '.$key);
	}
	Display :: display_normal_message(get_lang('GradebookWeightUpdated')) . '<br /><br />';
endif;*/
/*
define('LINK_EXERCISE',1);
define('LINK_DROPBOX',2);
define('LINK_STUDENTPUBLICATION',3);
define('LINK_LEARNPATH',4);
define('LINK_FORUM_THREAD',5);
*/
$table_evaluated[LINK_EXERCISE]           = array(TABLE_QUIZ_TEST, 'title', 'id', get_lang('Exercise'));
$table_evaluated[LINK_DROPBOX]            = array(TABLE_DROPBOX_FILE, 'name','id', get_lang('Dropbox'));
$table_evaluated[LINK_STUDENTPUBLICATION] = array(TABLE_STUDENT_PUBLICATION, 'url','id', get_lang('Student_publication'));
$table_evaluated[LINK_LEARNPATH]          = array(TABLE_LP_MAIN, 'name','id', get_lang('Learnpath'));
$table_evaluated[LINK_FORUM_THREAD]       = array(TABLE_FORUM_THREAD, 'thread_title_qualify', 'thread_id', get_lang('Forum'));
$table_evaluated[LINK_ATTENDANCE]         = array(TABLE_ATTENDANCE, 'attendance_title_qualify', 'id', get_lang('Attendance'));
$table_evaluated[LINK_SURVEY]             = array(TABLE_SURVEY, 'code', 'survey_id', get_lang('Survey'));

$submitted=isset($_POST['submitted'])?$_POST['submitted']:'';
if($submitted==1) {
	Display :: display_confirmation_message(get_lang('GradebookWeightUpdated')) . '<br /><br />';
	if (isset($_POST['evaluation'])) {
		require_once 'lib/be/evaluation.class.php';
		$eval_log = new Evaluation();
	}

	if(isset($_POST['link'])){
		require_once 'lib/be/abstractlink.class.php';
		//$eval_link_log = new AbstractLink();
	}

}

$category_id = (int)$_GET['selectcat'];
$output='';
$sql='SELECT * FROM '.$table_link.' WHERE category_id = '.$category_id;
$result = Database::query($sql);
	while($row = Database ::fetch_array($result)) {
		//update only if value changed
		if(isset($_POST['link'][$row['id']]) && $_POST['link'][$row['id']] != $row['weight']) {

			AbstractLink::add_link_log($row['id']);
			Database::query('UPDATE '.$table_link.' SET weight = '."'".Database::escape_string(trim($_POST['link'][$row['id']]))."'".' WHERE id = '.$row['id']);
			$row['weight'] = trim($_POST['link'][$row['id']]);

			//Update weight for attendance
			$sql = 'SELECT ref_id FROM '.$table_link.' WHERE id = '.intval($row['id']).' AND type='.LINK_ATTENDANCE;
			$rs_attendance  = Database::query($sql);
			if (Database::num_rows($rs_attendance) > 0) {
				$row_attendance = Database::fetch_array($rs_attendance);
				$upd_attendance = 'UPDATE '.$tbl_attendance.' SET attendance_weight ='.floatval($_POST['link'][$row['id']]).' WHERE id = '.intval($row_attendance['ref_id']);
				Database::query($upd_attendance);
			}
			//Update weight into forum thread
			$sql_t='UPDATE '.$tbl_forum_thread.' SET thread_weight='.floatval($_POST['link'][$row['id']]).' WHERE thread_id= (SELECT ref_id FROM '.$table_link.' WHERE id='.intval($row['id']).' AND type='.LINK_FORUM_THREAD.');';
			Database::query($sql_t);
			//Update weight into student publication(work)
			$sql_t='UPDATE '.$tbl_work.' SET weight='.floatval($_POST['link'][$row['id']]).' WHERE id = (SELECT ref_id FROM '.$table_link.' WHERE id='.intval($row['id']).' AND type = '.LINK_STUDENTPUBLICATION.');';
			Database::query($sql_t);
		}

		$tempsql = Database::query('SELECT * FROM '.get_table_type_course($row['type'],$row['course_code']).' WHERE '.$table_evaluated[$row['type']][2].' = '.$row['ref_id']);
		$resource_name = Database ::fetch_array($tempsql);
		//var_dump($resource_name['lp_type']);
		if (isset($resource_name['lp_type'])) {
			$resource_name=$resource_name[2];
		} else {
			$resource_name=$resource_name[1];
		}
		$output.= '<tr><td>'.build_type_icon_tag($row['type']).'</td><td> [ '.$table_evaluated[$row['type']][3].' ] '.$resource_name.'</td><td><input type="hidden" name="link_'.$row['id'].'" value="'.$resource_name.'" /><input size="10" type="text" name="link['.$row['id'].']" value="'.$row['weight'].'"/></td></tr>';
	}

	$sql = Database::query('SELECT * FROM '.$table_evaluation.' WHERE category_id = '.$category_id);
	while($row = Database ::fetch_array($sql)) {

		//update only if value changed
		if(isset($_POST['evaluation'][$row['id']]) && $_POST['evaluation'][$row['id']] != $row['weight']) {
			Evaluation::add_evaluation_log($row['id']);
			Database::query('UPDATE '.$table_evaluation.' SET weight = '."'".Database::escape_string(trim($_POST['evaluation'][$row['id']]))."'".' WHERE id = '.$row['id']);
			$row['weight'] = trim($_POST['evaluation'][$row['id']]);
		}
	$type_evaluated = isset($row['type']) ? $table_evaluated[$type_evaluated][3] : null;
	$output.= '<tr><td>'.build_type_icon_tag('evalnotempty').'</td><td> [ '.get_lang('Evaluation').$type_evaluated.' ] '.$row['name'].'</td><td><input type="hidden" name="eval_'.$row['id'].'" value="'.$row['name'].'" /><input type="text" size="10" name="evaluation['.$row['id'].']" value="'.$row['weight'].'"/></td></tr>';
}
//by iflorespaz
$my_category=array();
$cat=new Category();
$my_category   = $cat->shows_all_information_an_category($my_selectcat);
$my_api_cidreq = api_get_cidreq();
if ($my_api_cidreq=='') {
	$my_api_cidreq='cidReq='.$my_category['course_code'];
}
?>
<div class="actions">
<a href="<?php echo Security::remove_XSS($_SESSION['gradebook_dest']).'?id_session='.api_get_session_id().'&amp;'.$my_api_cidreq ?>&selectcat=<?php echo $category_id ?>"> <?php echo Display::return_icon('back.png',get_lang('FolderView'),'','32'); ?></a>
</div>
<form method="post" action="gradebook_edit_all.php?id_session=<?php echo $_SESSION['id_session'].'&amp;'.$my_api_cidreq ?>&selectcat=<?php echo $category_id?>">
<table class="data_table">
		 <tr class="row_odd">
		  <th style="width: 35px;"><?php echo get_lang('Type'); ?></th>
		  <th><?php echo get_lang('Resource'); ?></th>
		  <th><?php echo get_lang('Weight'); ?></th>
		 </tr>
		 <?php echo $output; ?>
 </table>
 <input type="hidden" name="submitted" value="1" />
 <br />
 <button class="save" type="submit" name="name" value="<?php echo get_lang('Save') ?>"><?php echo get_lang('SaveScoringRules') ?></button>
</form>
<?php
Display :: display_footer();
?>
