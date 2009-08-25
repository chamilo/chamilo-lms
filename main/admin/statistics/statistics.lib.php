<?php
// $Id: index.php 8216 2006-11-3 18:03:15 NushiFirefox $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2006 Bart Mollet <bart.mollet@hogent.be>

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact: Dokeos, 181 rue Royale, B-1000 Brussels, Belgium, info@dokeos.com
==============================================================================
*/
require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
/**
==============================================================================
* This class provides some functions for statistics
* @package dokeos.statistics
==============================================================================
*/
class Statistics
{
	/**
	 * Converts a number of bytes in a formatted string
	 * @param int $size
	 * @return string Formatted file size
	 */
	function make_size_string($size) {
		if ($size < pow(2,10)) return $size." bytes";
		if ($size >= pow(2,10) && $size < pow(2,20)) return round($size / pow(2,10), 0)." KB";
		if ($size >= pow(2,20) && $size < pow(2,30)) return round($size / pow(2,20), 1)." MB";
		if ($size > pow(2,30)) return round($size / pow(2,30), 2)." GB";
	}
	/**
	 * Count courses
	 * @param string $category_code  Code of a course category. Default: count
	 * all courses.
	 * @return int Number of courses counted
	 */
	function count_courses($category_code = NULL)
	{
		$course_table = Database :: get_main_table(TABLE_MAIN_COURSE);
		$sql = "SELECT COUNT(*) AS number FROM ".$course_table." ";
		if (isset ($category_code))
		{
			$sql .= " WHERE category_code = '".Database::escape_string($category_code)."'";
		}
		$res = api_sql_query($sql, __FILE__, __LINE__);
		$obj = Database::fetch_object($res);
		return $obj->number;
	}
	/**
	 * Count users
	 * @param int $status COURSEMANAGER or STUDENT
	 * @param string $category_code  Code of a course category. Default: count
	 * all users.
	 * @return int Number of users counted
	 */
	function count_users($status, $category_code = NULL, $count_invisible_courses = true)
	{
		// Database table definitions
		$course_user_table 	= Database :: get_main_table(TABLE_MAIN_COURSE_USER);
		$course_table 		= Database :: get_main_table(TABLE_MAIN_COURSE);
		$user_table 		= Database :: get_main_table(TABLE_MAIN_USER);
		
		$sql = "SELECT COUNT(DISTINCT(user_id)) AS number FROM $user_table WHERE status = ".intval(Database::escape_string($status))." ";
		if (isset ($category_code))
		{
			$sql = "SELECT COUNT(DISTINCT(cu.user_id)) AS number FROM $course_user_table cu, $course_table c WHERE cu.status = ".intval(Database::escape_string($status))." AND c.code = cu.course_code AND c.category_code = '".Database::escape_string($category_code)."'";
		}
		$res = api_sql_query($sql, __FILE__, __LINE__);
		$obj = Database::fetch_object($res);
		return $obj->number;
	}
	
	/**
	 * Count activities from track_e_default_table 
	 * @return int Number of activities counted
	 */
	function get_number_of_activities()
	{  
		// Database table definitions
		$track_e_default  = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_DEFAULT);  
		  
		$sql = "SELECT count(default_id) AS total_number_of_items FROM $track_e_default, $table_user user WHERE default_user_id = user.user_id ";  
		  
		if (isset($_GET['keyword'])) {
		$keyword = Database::escape_string($_GET['keyword']);
		$sql .= " AND (user.username LIKE '%".$keyword."%' OR default_event_type LIKE '%".$keyword."%' OR default_value_type LIKE '%".$keyword."%' OR default_value LIKE '%".$keyword."%') ";
		}
		   
		$res = api_sql_query($sql, __FILE__, __LINE__);
		$obj = Database::fetch_object($res);
		return $obj->total_number_of_items;
	}
	/**
	 * Get activities data to display
	 */
	function get_activities_data($from, $number_of_items, $column, $direction)
	{
		global $dateTimeFormatLong;
		$track_e_default 	= Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_DEFAULT);
		$table_user = Database::get_main_table(TABLE_MAIN_USER);
		$table_course = Database::get_main_table(TABLE_MAIN_COURSE);
		
		$sql = "SELECT
				 	default_event_type  as col0,
					default_value_type	as col1,
					default_value		as col2,																
					user.username 	as col3, 					
					default_date 	as col4									
				FROM $track_e_default track_default, $table_user user
				WHERE track_default.default_user_id = user.user_id ";
				
		if (isset($_GET['keyword'])) {
		$keyword = Database::escape_string($_GET['keyword']);
		$sql .= " AND (user.username LIKE '%".$keyword."%' OR default_event_type LIKE '%".$keyword."%' OR default_value_type LIKE '%".$keyword."%' OR default_value LIKE '%".$keyword."%') ";
		}		
						 				 
		if (!empty($column) && !empty($direction)) {						 				 
			$sql .=	" ORDER BY col$column $direction"; 
		} else {
			$sql .=	" ORDER BY col4 DESC ";
		}
		$sql .=	" LIMIT $from,$number_of_items ";				
											
		$res = api_sql_query($sql, __FILE__, __LINE__);
		$activities = array ();
		while ($row = Database::fetch_row($res)) {
			$row[4] = api_ucfirst(format_locale_date($dateTimeFormatLong,strtotime($row[4])));
			$activities[] = $row;
		}		
		return $activities;
	}
		
	/**
	 * Get all course categories
	 * @return array All course categories (code => name)
	 */
	function get_course_categories()
	{
		$category_table = Database :: get_main_table(TABLE_MAIN_CATEGORY);
		$sql = "SELECT * FROM $category_table ORDER BY tree_pos";
		$res = api_sql_query($sql, __FILE__, __LINE__);
		$categories = array ();
		while ($category = Database::fetch_object($res))
		{
			$categories[$category->code] = $category->name;
		}
		return $categories;
	}
	/**
	 * Rescale data
	 * @param array $data The data that should be rescaled
	 * @param int $max The maximum value in the rescaled data (default = 500);
	 * @return array The rescaled data, same key as $data
	 */
	function rescale($data, $max = 500)
	{
		$data_max = 1;
		foreach ($data as $index => $value)
		{
			$data_max = ($data_max < $value ? $value : $data_max);
		}
		reset($data);
		$result = array ();
		$delta = $max / $data_max;
		foreach ($data as $index => $value)
		{
			$result[$index] = (int) round($value * $delta);
		}
		return $result;
	}
	/**
	 * Show statistics
	 * @param string $title The title
	 * @param array $stats
	 * @param bool $show_total
	 * @param bool $is_file_size
	 */
	function print_stats($title, $stats, $show_total = true, $is_file_size = false)
	{
		$total = 0;
		$data = Statistics::rescale($stats);
		echo '<table class="data_table" cellspacing="0" cellpadding="3">
			  		  <tr><th colspan="'.($show_total ? '4' : '3').'">'.$title.'</th></tr>';
		$i = 0;
		foreach($stats as $subtitle => $number)
		{
			$total += $number;
		}
		foreach ($stats as $subtitle => $number)
		{
			$i = $i % 13;
			if (api_strlen($subtitle) > 30)
			{
				$subtitle = '<acronym title="'.$subtitle.'">'.api_substr($subtitle, 0, 27).'...</acronym>';
			}
			if(!$is_file_size)
			{
				$number_label = number_format($number, 0, ',', '.');
			}
			else
			{
				$number_label = Statistics::make_size_string($number);
			}
			echo '<tr class="row_'.($i%2 == 0 ? 'odd' : 'even').'">
								<td width="150">'.$subtitle.'</td>
								<td width="550">
						 			'.Display::return_icon('bar_1u.gif', get_lang('Statistics') ,array('width' => $data[$subtitle], 'height' => '10')).'
								</td>
								<td align="right">'.$number_label.'</td>';
			if($show_total)
			{
				echo '<td align="right"> '.($total>0?number_format(100*$number/$total, 1, ',', '.'):'0').'%</td>';
			}
			echo '</tr>';
			$i ++;
		}
		if ($show_total)
		{
			if(!$is_file_size)
			{
				$total_label = number_format($total, 0, ',', '.');
			}
			else
			{
				$total_label = Statistics::make_size_string($total);
			}
			echo '<tr><th  colspan="4" align="right">'.get_lang('Total').': '.$total_label.'</td></tr>';
		}
		echo '</table>';
	}
	/**
	 * Show some stats about the number of logins
	 * @param string $type month, hour or day
	 */
	function print_login_stats($type)
	{
		$table = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_LOGIN);
		switch($type)
		{
			case 'month':
				$months = api_get_months_long();
				$period = get_lang('PeriodMonth');
				$sql = "SELECT DATE_FORMAT( login_date, '%Y-%m' ) AS stat_date , count( login_id ) AS number_of_logins FROM ".$table." GROUP BY stat_date ORDER BY login_date ";
				break;
			case 'hour':
				$period = get_lang('PeriodHour');
				$sql = "SELECT DATE_FORMAT( login_date, '%H' ) AS stat_date , count( login_id ) AS number_of_logins FROM ".$table." GROUP BY stat_date ORDER BY stat_date ";
				break;
			case 'day':
				$week_days = api_get_week_days_long();
				$period = get_lang('PeriodDay');
				$sql = "SELECT DATE_FORMAT( login_date, '%w' ) AS stat_date , count( login_id ) AS number_of_logins FROM ".$table." GROUP BY stat_date ORDER BY DATE_FORMAT( login_date, '%w' ) ";
				break;
		}
		$res = api_sql_query($sql,__FILE__,__LINE__);
		$result = array();
		while($obj = Database::fetch_object($res))
		{
			$stat_date = $obj->stat_date;
			switch($type)
			{
				case 'month':
					$stat_date = explode('-', $stat_date);
					$stat_date[1] = $months[$stat_date[1] - 1];
					$stat_date = implode(' ', $stat_date);
					break;
				case 'day':
					$stat_date = $week_days[$stat_date];
					break;
			}
			$result[$stat_date] = $obj->number_of_logins;
		}
		Statistics::print_stats(get_lang('Logins').' ('.$period.')', $result, true);
	}
	/**
	 * Print the number of recent logins
	 */
	function print_recent_login_stats()
	{
		$total_logins = array();
		$table = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_LOGIN);
		$sql[get_lang('Thisday')] 	 = "SELECT count(login_user_id) AS number FROM $table WHERE DATE_ADD(login_date, INTERVAL 1 DAY) >= NOW()";
		$sql[get_lang('Last7days')]  = "SELECT count(login_user_id) AS number  FROM $table WHERE DATE_ADD(login_date, INTERVAL 7 DAY) >= NOW()";
		$sql[get_lang('Last31days')] = "SELECT count(login_user_id) AS number  FROM $table WHERE DATE_ADD(login_date, INTERVAL 31 DAY) >= NOW()";
		$sql[get_lang('Total')] 	 = "SELECT count(login_user_id) AS number  FROM $table";
		foreach($sql as $index => $query)
		{
			$res = api_sql_query($query,__FILE__,__LINE__);
			$obj = Database::fetch_object($res);
			$total_logins[$index] = $obj->number;
		}
		Statistics::print_stats(get_lang('Logins'),$total_logins,false);
	}
	/**
	 * Show some stats about the accesses to the different course tools
	 */
	function print_tool_stats()
	{
		$table = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ACCESS);
		$tools = array('announcement','assignment','calendar_event','chat','conference','course_description','document','dropbox','group','learnpath','link','quiz','student_publication','user','bb_forum');
		$sql = "SELECT access_tool, count( access_id ) AS number_of_logins FROM $table WHERE access_tool IN ('".implode("','",$tools)."') GROUP BY access_tool ";
		$res = api_sql_query($sql,__FILE__,__LINE__);
		$result = array();
		while($obj = Database::fetch_object($res))
		{
			$result[$obj->access_tool] = $obj->number_of_logins;
		}
		Statistics::print_stats(get_lang('PlatformToolAccess'),$result,true);
	}
	/**
	 * Show some stats about the number of courses per language
	 */
	function print_course_by_language_stats()
	{
		$table = Database::get_main_table(TABLE_MAIN_COURSE);
		$sql = "SELECT course_language, count( code ) AS number_of_courses FROM $table GROUP BY course_language ";
		$res = api_sql_query($sql,__FILE__,__LINE__);
		$result = array();
		while($obj = Database::fetch_object($res))
		{
			$result[$obj->course_language] = $obj->number_of_courses;
		}
		Statistics::print_stats(get_lang('CountCourseByLanguage'),$result,true);
	}
	/**
	 * Shows the number of users having their picture uploaded in Dokeos.
	 */
	function print_user_pictures_stats()
	{
		$user_table = Database :: get_main_table(TABLE_MAIN_USER);
		$sql = "SELECT COUNT(*) AS n FROM $user_table";
		$res = api_sql_query($sql,__FILE__,__LINE__);
		$count1 = Database::fetch_object($res);
		$sql = "SELECT COUNT(*) AS n FROM $user_table WHERE LENGTH(picture_uri) > 0";
		$res = api_sql_query($sql,__FILE__,__LINE__);
		$count2 = Database::fetch_object($res);
		$result[get_lang('No')] = $count1->n - $count2->n; // #users without picture
		$result[get_lang('Yes')] = $count2->n; // #users with picture
		Statistics::print_stats(get_lang('CountUsers').' ('.get_lang('UserPicture').')',$result,true);
	}
	
	function print_activities_stats() {				
		
		echo '<h4>'.get_lang('ImportantActivities').'</h4>';
				
		// Create a search-box
		$form = new FormValidator('search_simple','get',api_get_path(WEB_CODE_PATH).'admin/statistics/index.php?action=activities','','width=200px',false);
		$renderer =& $form->defaultRenderer();
		$renderer->setElementTemplate('<span>{element}</span> ');
		$form->addElement('hidden','action','activities');
		$form->addElement('hidden','activities_direction','DESC');
		$form->addElement('hidden','activities_column','4');		
		$form->addElement('text','keyword',get_lang('keyword'));
		$form->addElement('style_submit_button', 'submit', get_lang('SearchActivities'),'class="search"');							 
		echo '<div class="actions">';		
			$form->display();				
		echo '</div>';
		
				
		$table = new SortableTable('activities', array('Statistics','get_number_of_activities'), array('Statistics','get_activities_data'),4,50,'DESC');
		$parameters = array();
		
		$parameters['action'] = 'activities';		
		if (isset($_GET['keyword'])) {
			$parameters['keyword'] = Security::remove_XSS($_GET['keyword']);
		}
		
		$table->set_additional_parameters($parameters);											
		$table->set_header(0, get_lang('EventType'));
		$table->set_header(1, get_lang('DataType'));
		$table->set_header(2, get_lang('Value'));		
		$table->set_header(3, get_lang('UserName'));
		$table->set_header(4, get_lang('Date'));		
		$table->display();
		
	}
	
	/**
	 * Shows statistics about the time of last visit to each course.
	 */
	function print_course_last_visit()
	{
		$columns[0] = 'access_cours_code';
		$columns[1] = 'access_date';
		$sql_order[SORT_ASC] = 'ASC';
		$sql_order[SORT_DESC] = 'DESC';
		$per_page 	= isset($_GET['per_page']) ? intval($_GET['per_page']) : 10;
		$page_nr 	= isset($_GET['page_nr'])  ? intval($_GET['page_nr']) : 1;
		$column 	= isset($_GET['column'])   ? intval($_GET['column']) : 0;
		$date_diff 	= isset($_GET['date_diff'])? intval($_GET['date_diff']) : 60;
	    if(!in_array($_GET['direction'],array(SORT_ASC,SORT_DESC))){
	    	$direction = SORT_ASC;
	    } else {
	    	$direction = isset($_GET['direction']) ? $_GET['direction'] : SORT_ASC;
	    }		
		$form = new FormValidator('courselastvisit','get');
		$form->addElement('hidden','action','courselastvisit');
		$form->add_textfield('date_diff',get_lang('Days'),true);
		$form->addRule('date_diff','InvalidNumber','numeric');
		$form->addElement('submit','ok',get_lang('Ok'));
		$defaults['date_diff'] = 60;
		$form->setDefaults($defaults);
		if($form->validate()) {
			$form->display();
			$values = $form->exportValues();
			$date_diff = $values['date_diff'];
			$table = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_LASTACCESS);
			$sql = "SELECT * FROM $table GROUP BY access_cours_code HAVING access_cours_code <> '' AND DATEDIFF( NOW() , access_date ) >= ". $date_diff;
			$res = api_sql_query($sql,__FILE__,__LINE__);
			$number_of_courses = Database::num_rows($res);
			$sql .= ' ORDER BY '.$columns[$column].' '.$sql_order[$direction];
			$from = ($page_nr -1) * $per_page;
			$sql .= ' LIMIT '.$from.','.$per_page;
			echo '<p>'.get_lang('LastAccess').' &gt;= '.$date_diff.' '.get_lang('Days').'</p>';
			$res = api_sql_query($sql, __FILE__, __LINE__);
			if (Database::num_rows($res) > 0)
			{
				$courses = array ();
				while ($obj = Database::fetch_object($res))
				{
					$course = array ();
					$course[]= '<a href="'.api_get_path(WEB_PATH).'courses/'.$obj->access_cours_code.'">'.$obj->access_cours_code.' <a>';
					$course[] = $obj->access_date;
					$courses[] = $course;
				}
				$parameters['action'] = 'courselastvisit';
				$parameters['date_diff'] = $date_diff;
				$table_header[] = array ("Coursecode", true);
				$table_header[] = array ("Last login", true);
				Display :: display_sortable_table($table_header, $courses, array ('column'=>$column,'direction'=>$direction), array (), $parameters);
			}
			else
			{
				echo get_lang('NoSearchResults');
			}
		}
		else
		{
			$form->display();
		}
	}
}
?>
