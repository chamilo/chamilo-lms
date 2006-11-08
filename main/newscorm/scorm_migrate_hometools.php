<?php //$id: $
/**
 * Script handling the migration between an old Dokeos platform (<1.8.0) to 
 * setup the course's tools so that they use the new code directory for SCORM
 * @package dokeos.scorm 
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */
/**
 * Include mandatory libraries
 */
require_once('back_compat.inc.php');

$sql = "SELECT * FROM ".Database::get_main_table(MAIN_COURSE_TABLE)."";
$res = api_sql_query($sql,__FILE__,__LINE__);
while ($row = Database::fetch_array($res))
{
	//TODO change this db name construction to use DB instead of claro_main.conf settings
	$course_pref = Database::get_course_table_prefix();
	$dbname = $row['db_name'].'.'.$course_pref;
	$courses_list[] = $row['db_name'];
	
	//TODO add check for learnpath element. If not exist, create one.
	$tbl_tool = Database::get_course_table(TOOL_LIST_TABLE,$row['db_name']);
	$sql_t = "UPDATE $tbl_tool SET link = 'newscorm/lp_controller.php' WHERE name='learnpath'";
	$res_t = api_sql_query($sql_t,__FILE__,__LINE__);
	if(!$res_t){
		echo "SQL error with query: ".$sql_t." - ignoring<br/>\n";
	}
	$sql_s = "SELECT * FROM $tbl_tool WHERE link LIKE '%scorm/showinframes%'";
	$res_s = api_sql_query($sql_s,__FILE__,__LINE__);
	if(!$res_s){
		echo "SQL error with query: ".$sql_s." - ignoring<br/>\n";
	}else{
		$lp_id = 1; //distribute lp_ids at random, course tutors will modify links afterwards if needed
		while($row_s = Database::fetch_array($res_s)){
			error_log('YWUPDTOOL - '.$row['code'].' -'.$row_s['link'],0);
			$link = 'newscorm/lp_controller.php?cidReq='.$row['code'].'&action=view&lp_id='.$lp_id;
			$sql_r = "UPDATE $tbl_tool SET link = '$link' WHERE id=".$row_s['id'];
            //make sure we can revert by printing a list of updated links
            echo $sql_r." (AND link='".$row_s['link']."')<br/>\n";
            $res_r = api_sql_query($sql_r,__FILE__,__LINE__);
			if(!$res_r){
				echo "SQL error with query: ".$sql_r." - ignoring<br/>\n";
			}
			$lp_id++;
			
		}
	}
}
?>