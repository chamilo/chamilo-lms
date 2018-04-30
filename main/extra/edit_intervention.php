<?php
/*
==============================================================================

==============================================================================
		INIT SECTION
==============================================================================
*/
$pathopen = isset($_REQUEST['pathopen']) ? $_REQUEST['pathopen'] : null;
// name of the language file that needs to be included

$language_file[] = 'tracking';
$language_file[] = 'scorm';

include('../inc/global.inc.php');

/*
$is_allowedToTrack = $is_courseAdmin || $is_platformAdmin;

if(!$is_allowedToTrack)
{
	Display :: display_header();
	api_not_allowed();
	Display :: display_footer();
}
*/
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


/*
-----------------------------------------------------------
	Constants and variables
-----------------------------------------------------------
*/
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

foreach($_GET as $index => $valeur) {
    $$index = Database::escape_string(trim($valeur));
}
?>
<form action="update_intervention.php" method="post" name="save_intercention">
<center><table class='data_table'>

<tr>
					<th colspan="4">
<?php echo get_lang('edit_save'); ?>

					 <tr>   
				<th><?php echo get_lang('level') ?>	</th>
	<th>
						<?php echo get_lang('lang_date') ?>
	</th>
					<th>
						<?php echo get_lang('interventions_commentaires') ?>
					</th>
					
					<th>
						<?php echo get_lang('action') ?>
					</th>
    </tr>

						<?php

$sqlinter = "SELECT *
								 FROM $tbl_stats_exercices
								 WHERE exe_id =  $num

								 ";
						$resultinter = api_sql_query($sqlinter);

			while($a_inter = Database::fetch_array($resultinter))
		{  
					 $level =$a_inter['level'];
           $mod_no =$a_inter['mod_no'];
            $score_ex =$a_inter['score_ex'];
             $inter_coment = stripslashes ($a_inter['inter_coment']);
             echo"
				<tr><center>
					<td> ".$a_inter['level']."
					</td>
				<td><center>
						".$a_inter['exe_date']."
					</td>
				  					
				";
				$exe_id = $a_inter['exe_id'];
?>
			<td><textarea  class="span5" name="inter_coment"  cols="65" rows="2"><?php echo $inter_coment; ?></textarea><br></td>
				<INPUT  type=hidden name=ex_id value= <?php echo "$exe_id" ?> >
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
