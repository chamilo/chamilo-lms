<?php
require_once('back_compat.inc.php');

$max_dsp_lp = 0;
$courses_list = array();
$courses_id_list = array();
$courses_dir_list = array();
$sql = "SELECT * FROM ".Database::get_main_table(TABLE_MAIN_COURSE)."";
echo $sql."<br />\n";
$res = api_sql_query($sql,__FILE__,__LINE__);
while ($row = Database::fetch_array($res))
{
	//TODO change this db name construction to use DB instead of configuration.php settings
	$course_pref = Database::get_course_table_prefix();
	$dbname = $row['db_name'].'.'.$course_pref;
	$courses_list[] = $row['db_name'];
	$courses_id_list[$row['code']] = $row['db_name'];
	$courses_dir_list[$row['code']] = $row['directory']; 
}
foreach($courses_list as $db)
{
	echo "Using course db $db<br/>\n";
	$lp = Database::get_course_table('lp',$db);
	$sql = "TRUNCATE TABLE $lp";
	echo "$sql<br />\n";
	$res = @mysql_query($sql);
	$lp = Database::get_course_table('lp_item',$db);
	$sql = "TRUNCATE TABLE $lp";
	echo "$sql<br />\n";
	$res = @mysql_query($sql);
	$lp = Database::get_course_table('lp_view',$db);
	$sql = "TRUNCATE TABLE $lp";
	echo "$sql<br />\n";
	$res = @mysql_query($sql);
	$lp = Database::get_course_table('lp_item_view',$db);
	$sql = "TRUNCATE TABLE $lp";
	echo "$sql<br />\n";
	$res = @mysql_query($sql);
}
echo "All done";
?>