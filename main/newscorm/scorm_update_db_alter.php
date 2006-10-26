<?php //$id: $
/**
 * Script that updates the four new learning path tables (that allow scorm data) to add several fields
 * @package dokeos.learnpath
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */
/**
 * Script
 */
require_once('back_compat.inc.php');
require_once('learnpath.class.php');
require_once('scorm.class.php');
/**
 * New tables definition:
 */
//table replacing learnpath_main
$new_lp = 'lp';
$alter_lp = "ALTER TABLE XXX_$new_lp ADD COLUMN (" .
		"content_license	text not null default ''" . //content license
		")";
$alter_lp2 = "ALTER TABLE XXX_$new_lp ADD COLUMN (" .
		"prevent_reinit tinyint		unsigned not null default 1" . //stores the default behaviour regarding items re-initialisation when viewed a second time after success
		")";
$alter_lp3 = "ALTER TABLE XXX_$new_lp ADD COLUMN (" .
		"debug tinyint		unsigned not null default 0" . //stores the default behaviour regarding items re-initialisation when viewed a second time after success
		")";
//new table, aimed at keeping track of attempts made to one learning path
//no row exists if nobody has opened any learning path yet. A row is only written when someone opens a learnpath
$new_lp_view = 'lp_view';
$alter_lp_view = "ALTER TABLE XXX_$new_lp_view ADD COLUMN " .
		"progress		int		unsigned	default 0" .
		"";
//table replacing the learnpath_user table
$new_lp_item_view = 'lp_item_view';
$alter_lp_item_view = "ALTER TABLE XXX_$new_lp_item_view ADD COLUMN " .
		"lesson_location text null default ''" .
		"";

$new_lp_iv_interaction = 'lp_iv_interaction';
$alter_lp_iv_interaction = "CREATE TABLE XXX_$new_lp_iv_interaction(" .
		"id				bigint unsigned AUTO_INCREMENT PRIMARY KEY," .
		"order_id		smallint unsigned	not null default 0,". //internal order (0->...) given by Dokeos
		"lp_iv_id		bigint unsigned not null," . //identifier of the related sco_view
		"interaction_id		varchar(255) not null default ''," . //sco-specific, given by the sco
		"interaction_type	varchar(255) not null default ''," . //literal values, SCORM-specific (see p.63 of SCORM 1.2 RTE)
		"weighting			double not null default 0," .
		"completion_time	varchar(16) not null default ''," . //completion time for the interaction (timestamp in a day's time) - expected output format is scorm time
		"correct_responses	text not null default ''," . //actually a serialised array. See p.65 os SCORM 1.2 RTE)
		"student_response	text not null default ''," . //student response (format depends on type)
		"result			varchar(255) not null default ''," . //textual result
		"latency		varchar(16)	not null default ''" . //time necessary for completion of the interaction
		")";
$alter_lp_iv_interaction2 = "ALTER TABLE XXX_$new_lp_iv_interaction CHANGE `time` completion_time VARCHAR(16) NOT NULL DEFAULT '0'";
$alter_lp_iv_interaction3 = "ALTER TABLE XXX_$new_lp_iv_interaction CHANGE `type` interaction_type VARCHAR(255) NOT NULL DEFAULT ''";

/**
 * First create the lp, lp_view, lp_item and lp_item_view tables in each course's DB
 */
$main_db = Database::get_main_database();
$sql = "SELECT * FROM $main_db.course";
echo "$sql<br />\n";
$res = api_sql_query($sql);

$courses_list = array();
$courses_id_list = array();
$courses_dir_list = array();
while ($row = Database::fetch_array($res))
{
	//TODO change this db name construction to use DB instead of claro_main.conf settings
	$course_pref = Database::get_course_table_prefix();
	$dbname = $row['db_name'].'.'.$course_pref;
	$courses_list[] = $row['db_name'];
	$courses_id_list[$row['code']] = $row['db_name'];
	$courses_dir_list[$row['code']] = $row['directory']; 
	if(empty($_GET['delete'])){
		echo "Updating tables for ".$row['db_name']."<br />\n";
		if (mysql_query("SELECT content_license FROM $new_lp")==false)
		{
			$create_table = str_replace('XXX_',$dbname,$alter_lp);
			echo "$create_table<br />\n";
			api_sql_query($create_table);
		}
		if (mysql_query("SELECT prevent_reinit FROM $new_lp")==false)
		{
			$create_table = str_replace('XXX_',$dbname,$alter_lp2);
			echo "$create_table<br />\n";
			api_sql_query($create_table);
		}
		if (mysql_query("SELECT debug FROM $new_lp")==false)
		{
			$create_table = str_replace('XXX_',$dbname,$alter_lp3);
			echo "$create_table<br />\n";
			api_sql_query($create_table);
		}
		if (mysql_query("SELECT progress FROM $new_lp_view")==false)
		{
			$create_table = str_replace('XXX_',$dbname,$alter_lp_view);
			echo "$create_table<br />\n";
			api_sql_query($create_table);
		}
		if (mysql_query("SELECT lesson_location FROM $new_lp_item_view")==false)
		{
			$create_table = str_replace('XXX_',$dbname,$alter_lp_item_view);
			echo "$create_table<br />\n";
			api_sql_query($create_table);
		}
		if(mysql_query("SELECT id FROM $new_lp_iv_interaction")==false){
			$create_table = str_replace('XXX_',$dbname,$alter_lp_iv_interaction);
			echo "$create_table<br/>\n";
			api_sql_query($create_table);
		}
		if(mysql_query("SELECT `type` FROM $new_lp_iv_interaction")==false){
			$create_table = str_replace('XXX_',$dbname,$alter_lp_iv_interaction2);
			echo "$create_table<br/>\n";
			api_sql_query($create_table);
		}
		if(mysql_query("SELECT `time` FROM $new_lp_iv_interaction")==false){
			$create_table = str_replace('XXX_',$dbname,$alter_lp_iv_interaction3);
			echo "$create_table<br/>\n";
			api_sql_query($create_table);
		}
		echo "<br /><br />\n";
	}
}
echo "Tables updated for all courses<br />\n";
?>