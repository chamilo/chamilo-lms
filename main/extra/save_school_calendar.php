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





//$d_title = isset($_POST['d_title'])?$_POST['d_title']:"";

/*

$d_cal_date= isset($_POST['d_cal_date'])?$_POST['d_cal_date']:"";

$d_id = isset($_POST['d_id'])?$_POST['d_id']:"";

$d_number = isset($_POST['d_number'])?$_POST['d_number']:"";

  */

Display::display_header($nameTools, "Tracking");

include(api_get_path(LIBRARY_PATH)."statsUtils.lib.inc.php");

include("../resourcelinker/resourcelinker.inc.php");









foreach($_POST as $index => $valeur) {

    $$index = Database::escape_string(trim($valeur));

}



?>

  <form action="upgrade_school_calendar.php" method="post" name="upgrade_cal">

					<th colspan="6">

<?php echo get_lang('edit_save'); ?>



					</th><tr>



					</th>

    </tr>





<?php



  				 echo "<table border='1'><tr>";

               if($i%$nbcol==0)



$sqlexam = "SELECT *

								 FROM set_module

								 WHERE cal_name =  '$d_title'



								 ";

					$resultexam = api_sql_query($sqlexam);



					while($a_exam = Database::fetch_array($resultexam))

					{

					 $name =$a_exam['cal_name'];

                     $id =$a_exam['id'];

            		$num =$a_exam['cal_day_num'];

             	$c_date =$a_exam['cal_date'];





            	echo"



				<td><input type=text  name=d_cal_date size=8 value=

						".$c_date."

					</td>

                 <td><input type=text name=d_number size=5 value=

                       ".$num."

					</td>

					<td><input type=text  name=d_title size=8 value=

						".$name."

					</td>

					<td><input  name=d_id size=8 value=

						". $id."

					</td>



				";

                if($i%$nbcol==($nbcol-1))

 		 echo "</tr>";



   }

   $nb=count($d_number);

   $nbcol=2;



?>



	</td>

      </tr>



  <input type=hidden name=aaa  value=<?=serialize( Database::fetch_array($resultexam));?> />

 <input type="submit" value="Sauvegarder" name="B1">



<?php

 // print_r(unserialize($_POST['aaa']));





 echo  $id, $tableau;



?>





</form>



</table>



<?php

	/*

==============================================================================

		FOOTER

==============================================================================

*/



Display::display_footer();

?>



