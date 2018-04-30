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









//$name = isset($_POST['name'])?$_POST['name']:"";



//$c_date= isset($_POST['c_date'])?$_POST['c_date']:"";

//$d_id = isset($_POST['d_id'])?$_POST['d_id']:"";

//$d_number = isset($_POST['d_number'])?$_POST['d_number']:"";

//$aaa = isset($_POST['aaa'])?$_POST['aaa']:"";

Display::display_header($nameTools, "Tracking");

include(api_get_path(LIBRARY_PATH)."statsUtils.lib.inc.php");

include("../resourcelinker/resourcelinker.inc.php");







      foreach($_POST as $x) {

         echo "$x <br />";

      }









foreach($_POST as $index => $valeur) {

    $$index = Database::escape_string(trim($valeur));

}



?>



					<th colspan="6">

<?php echo get_lang('edit_save'); ?>





<?php





$sql4 = "UPDATE set_module SET cal_day_num='$d_number'

		WHERE id = '$d_id'

    ";

			api_sql_query($sql4); //OR die("<p>Erreur Mysql2<br/>$sql4<br/>".mysql_error()."</p>");

 print_r(unserialize($_POST['aaa']));

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



