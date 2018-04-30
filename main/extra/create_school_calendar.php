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



$title =isset($_POST['title'])?$_POST['title']:"";



$je=isset($_POST['je'])?$_POST['je']:"";

$me=isset($_POST['me'])?$_POST['me']:"";

$ye=isset($_POST['ye'])?$_POST['ye']:"";



foreach($_POST as $index => $valeur) {

    $$index = Database::escape_string(trim($valeur));

}





?>

<?php

$start_time =  "$y-$m-$j";

$end_time =  "$ye-$me-$je";

 // On vérifie si les champs sont vides

if(empty($title))

    {

    echo '<font color="red">Attention, vous avez oubliez le nom du calendrier</font>';

    }

?>



<form action="save_school_calendar.php" method="post" name="save_cal">





<center><table class='data_table'>



<tr>

					<th colspan="3">

<?php echo get_lang('edit_save'); ?>



					</th><tr>

				<th><?php echo get_lang('title_calendar') ?>	</th>

	     		<th><?php echo get_lang('period') ?></th>

				<th><?php echo get_lang('action') ?>

					</th>

    </tr>

    		<td><center><input type=texte name=title value=<?php echo "$title" ?> > </td>



			<td><center><input  SIZE=25 NAME=period value=<?php echo "$langFrom",":","$start_time","$langTo", "$end_time"?> > </td>









<?php



  $date1= strtotime($start_time); //Premiere date

   $date2= strtotime($end_time); //Deuxieme date

      $nbjour=($date2-$date1)/60/60/24;//Nombre de jours entre les deux

      //$number=0;

     // $number=$number+1;

      $nbcol=2;

     echo "<table border='1'><tr>";

    if($i%$nbcol==0)



  for($i=0;$i<=$nbjour;$i++)

     {





      echo "<td><input type='text' NAME='date_case' size='8' value=".date('Y-m-d',$date1)."> ";





     $date1+=60*60*24; //On additionne d'un jour (en seconde)

     echo'<br>' ;

     echo'</td>';

     echo "<td><input type='text' NAME='day_number' size='4' value=".$number."></td>";



     echo "<td><input type='text' NAME='d_title' size='4' value=".$title."></td>";



     $sql4 = "INSERT INTO set_module "."(cal_name,cal_day_num,cal_date) " .

			"VALUES "."('$title','$number','" .date('Y-m-d',$date1). "')";



			api_sql_query($sql4);// OR die("<p>Erreur Mysql2<br/>$sql4<br/>".mysql_error()."</p>");



  		if($i%$nbcol==($nbcol-1))

 		 echo "</tr>";

        // $number=$number+1;







     }

?>



	</td>

      </tr>



 <input type="submit" value="Sauvegarder" name="B1">





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

