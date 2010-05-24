<?php
//par Andre Boivin pour remettre le lp a zéro
$language_file[] = 'tracking';  
require '../inc/global.inc.php';
  
$course= isset($_GET['course'])?$_GET['course']:"";
$lp_id= isset($_GET['lp_id'])?$_GET['lp_id']:"";
$student=isset($_GET['student_id'])?$_GET['student_id']:"";   

$tbl_course= Database :: get_main_table(TABLE_MAIN_COURSE);

$sql1 = "	SELECT *
					 FROM $tbl_course
					 	WHERE code = '$course' ";
				
		$result1 = api_sql_query($sql1,__FILE__,__LINE__);
		$data1 =Database::fetch_array($result1);
		 $cours_db = $data1['db_name'] ;


$sqlview = "	SELECT *  
					 FROM ".$cours_db.".lp_view
					 	WHERE user_id = $student ";
				
		$resultview = api_sql_query($sqlview,__FILE__,__LINE__);
		$view =Database::fetch_array($resultview);
		 $view_id=  $view['id'] ;
		
$sql2 = "DELETE FROM ".$cours_db.".lp_item_view
 where lp_view_id = $view_id ";
mysql_query($sql2) or die('Erreur SQL !<br>'.$sql2.'<br>'.mysql_error());

$sql3 = "DELETE 
 FROM ".$cours_db.".lp_view
					 	WHERE user_id = $student 
             AND lp_id= $lp_id";
mysql_query($sql3) or die('Erreur SQL !<br>'.$sql3.'<br>'.mysql_error()); 

header("Location: myStudents.php?student= $student");
 
?>