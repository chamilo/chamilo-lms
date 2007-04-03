<?php
/*
 * Created on 28 juil. 2006 by Elixir Interactive http://www.elixir-interactive.com
 */
ob_start();
 $nameTools= 'Sessions';
 // name of the language file that needs to be included 
$language_file = array ('registration', 'index','trad4all','tracking');
 $cidReset=true;
 require ('../inc/global.inc.php');
 require_once (api_get_path(LIBRARY_PATH).'tracking.lib.php');
 require_once (api_get_path(LIBRARY_PATH).'export.lib.inc.php');



api_block_anonymous_users();


 $this_section = "session_my_space";
 
 api_block_anonymous_users();
  $interbreadcrumb[] = array ("url" => "index.php", "name" => get_lang('MySpace'));
 Display :: display_header($nameTools);

// Database Table Definitions 
$tbl_course_user 		= Database :: get_main_table(TABLE_MAIN_COURSE_USER);
$tbl_sessions 			= Database :: get_main_table(TABLE_MAIN_SESSION);
$tbl_session_course 	= Database :: get_main_table(TABLE_MAIN_SESSION_COURSE);
$tbl_course 			= Database :: get_main_table(TABLE_MAIN_COURSE);
 
 
$export_csv = isset($_GET['export']) && $_GET['export'] == 'csv' ? true : false;
if($export_csv)
{
	ob_start();
	$csv_content = array();
}



 /*
 ===============================================================================
 	FUNCTION
 ===============================================================================  
 */
 
function count_sessions_coached()
{
	global $nb_sessions;
	return $nb_sessions;
}

function sort_sessions($a, $b)
{
	global $tracking_column;
	if($a[$tracking_column] > $b[$tracking_column])
		return 1;
	else 
		return -1;
}


 /*
 ===============================================================================
 	MAIN CODE
 ===============================================================================  
 */
 
$a_sessions = Tracking :: get_sessions_coached_by_user ($_user['user_id']);
$nb_sessions = count($a_sessions);

if($nb_sessions > 0)
{
	echo '<div align="right">
			<a href="#" onclick="window.print()"><img align="absbottom" src="../img/printmgr.gif">&nbsp;'.get_lang('Print').'</a>
			<a href="'.api_get_self().'?export=csv"><img align="absbottom" src="../img/excel.gif">&nbsp;'.get_lang('ExportAsCSV').'</a>
		  </div>';
		  
		  
	$table = new SortableTable('tracking', 'count_sessions_coached');
	$table -> set_header(0, get_lang('Title'));
	$table -> set_header(1, get_lang('Status'));
	$table -> set_header(2, get_lang('Date'));
	$table -> set_header(3, get_lang('Details'),false);
	
	$all_datas = array();
	
	foreach ($a_sessions as $session)
	{		
		$row = array();
		$row[] = $session['name'];
		$row[] = $session['status'];
		$row[] = get_lang('From').' '.format_locale_date("%B %Y %d",strtotime($session['date_start'])).' '.get_lang('To').' '.format_locale_date("%B %Y %d",strtotime($session['date_end']));
		if($export_csv)
		{
			$csv_content[] = $row;
		}
		$row[] = '<a href="cours.php?id_session='.$session['id'].'"><img src="'.api_get_path(WEB_IMG_PATH).'2rightarrow.gif" border="0" /></a>';
	
		$all_datas[] = $row;
	}
	
	if(!isset($tracking_column))
		$tracking_column = 0;
		
	usort($all_datas, 'sort_sessions');
	if($_GET['tracking_direction'] == 'DESC')
	{
		rsort($all_datas);
	}
	
	if($export_csv)
	{
		usort($csv_content, 'sort_sessions');
	}
	
	foreach($all_datas as $row)
	{
		$table -> addRow($row);	
	}
	
	$table -> setColAttributes(3,array('align'=>'center'));
	$table -> display();
	
	
	if($export_csv)
	{
		ob_end_clean();
		Export :: export_table_csv($csv_content, 'reporting_student_list');
	}
}
else
{
	get_lang('NoSession');
}

/*
==============================================================================
	FOOTER
==============================================================================
*/

Display::display_footer();

?>
