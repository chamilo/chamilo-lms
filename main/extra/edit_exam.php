<?php
/* For licensing terms, see /license.txt */

require_once '../inc/global.inc.php';

$allow = api_get_configuration_value('extra');
if (empty($allow)) {
    exit;
}

$pathopen = isset($_REQUEST['pathopen']) ? $_REQUEST['pathopen'] : null;
// name of the language file that needs to be included

$language_file[] = 'tracking';
$language_file[] = 'scorm';

include('../inc/global.inc.php');

//includes for SCORM and LP
require_once('../lp/learnpath.class.php');
require_once('../lp/learnpathItem.class.php');
require_once('../lp/learnpathList.class.php');
require_once('../lp/scorm.class.php');
require_once('../lp/scormItem.class.php');
require_once(api_get_path(LIBRARY_PATH).'tracking.lib.php');
require_once(api_get_path(LIBRARY_PATH).'course.lib.php');
require_once(api_get_path(LIBRARY_PATH).'usermanager.lib.php');
require_once (api_get_path(LIBRARY_PATH).'export.lib.inc.php');
  
api_block_anonymous_users();

$htmlHeadXtra[] = "<style type='text/css'>
/*<![CDATA[*/
.secLine {background-color : #E6E6E6;}
.content {padding-left : 15px;padding-right : 15px; }
.specialLink{color : #0000FF;}
/*]]>*/
</style>
<style media='print' type='text/css'>

</style>";


// regroup table names for maintenance purpose
$TABLETRACK_ACCESS      = Database::get_main_table(TABLE_STATISTIC_TRACK_E_LASTACCESS);
$tbl_stats_exercices 		= Database :: get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
$TABLETRACK_LINKS       = Database::get_main_table(TABLE_STATISTIC_TRACK_E_LINKS);
$TABLETRACK_DOWNLOADS   = Database::get_main_table(TABLE_STATISTIC_TRACK_E_DOWNLOADS);
$TABLETRACK_ACCESS_2    = Database::get_main_table("track_e_access");
$TABLECOURSUSER	        = Database::get_main_table(TABLE_MAIN_COURSE_USER);
$TABLECOURSE	        = Database::get_main_table(TABLE_MAIN_COURSE);
$TABLECOURSE_LINKS      = Database::get_course_table(TABLE_LINK);
$table_user = Database::get_main_table(TABLE_MAIN_USER);

$tbl_learnpath_main = Database::get_course_table('lp');
$tbl_learnpath_item = Database::get_course_table('lp_item');
$tbl_learnpath_view = Database::get_course_table('lp_view');
$tbl_learnpath_item_view = Database::get_course_table('lp_item_view');

$view = $_REQUEST['view'];


Display::display_header($nameTools, "Tracking");
include(api_get_path(LIBRARY_PATH)."statsUtils.lib.inc.php");
include("../resourcelinker/resourcelinker.inc.php");


$num=isset($_GET['num'])?$_GET['num']:"";
$student_idd =isset($_GET['student_id'])?$_GET['student_id']:"";

foreach($_POST as $index => $valeur) {
    $$index = Database::escape_string(trim($valeur));
}
?>
<form action="update_exam.php" method="post" name="save_exam">
<center><table class='data_table'>

<tr>
					<th colspan="6">
<?php echo get_lang('edit_save'); ?>

					</th><tr>
				<th><?php echo get_lang('module_no') ?>	</th>
	     <th><?php echo get_lang('result_exam') ?></th>
				<th><?php echo get_lang('result_rep_1') ?>
					</th>
					<th><?php echo get_lang('result_rep_2') ?>
					</th>
					<th><?php echo get_lang('comment') ?>
					</th>
					<th><?php echo get_lang('action') ?>
					</th>
    </tr>

						<?php

$sqlexam = "SELECT *
								 FROM $tbl_stats_exercices
								 WHERE exe_id =  $num

								 ";
					$resultexam = api_sql_query($sqlexam);

					while($a_exam = Database::fetch_array($resultexam))
					{
					 $exe_id =$a_exam['exe_id'];
           $mod_no =$a_exam['mod_no'];
            $score_ex =$a_exam['score_ex'];
             $score_rep1 =$a_exam['score_rep1'];
             $score_rep2 =$a_exam['score_rep2'];
             $coment =$a_exam['coment'];
            	echo"
				<tr>
					<td><input type=text style=width:20% name=mod_no size=1 value= ".$a_exam['mod_no']."
					</td>
				<td><input type=text style=width:20%  name=score_ex size=1 value=
						".$a_exam['score_ex']."
					</td>
				<td><input type=text style=width:20%  name=score_rep1 size=1 value=
						".$a_exam['score_rep1']."
					</td>
					<td><input type=text style=width:20%  name=score_rep2 size=1 value=
						".$a_exam['score_rep2']."
					</td>

				";
   
?>
			<td><textarea class="span5" name="coment"  cols="65" rows="2"><?php echo $coment; ?></textarea><br></td>
				<INPUT  type=hidden name=ex_idd value= <?php echo "$exe_id" ?> >
        	<INPUT  type=hidden name=student_id value= <?php echo "$student_idd" ?> >
				<td><input type="submit" value="Sauvegarder" name="B1"></td>
	</td>
      </tr>
<?php

}
?>


	</table>
   </form>
<?php
 


	/*
==============================================================================
		FOOTER
==============================================================================
*/

Display::display_footer();
?>
