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
	 * @param bool $curriculum True if only curriculum-courses should be counted
	 * @param $string faculty  Code of a faculty. Default: count all courses.
	 * @return int Number of courses counted
	 */
	function count_courses($faculty = NULL)
	{
		$course_table = Database :: get_main_table(MAIN_COURSE_TABLE);
		$sql = "SELECT COUNT(*) AS number FROM ".$course_table." ";
		if (isset ($faculty))
		{
			$sql .= " WHERE category_code = '$faculty'";
		}
		$res = api_sql_query($sql, __FILE__, __LINE__);
		$obj = mysql_fetch_object($res);
		return $obj->number;
	}
	/**
	 * Count users
	 * @param int $status COURSEMANAGER or STUDENT
	 * @param $string faculty  Code of a faculty. Default: count all students.
	 * @return int Number of students counted
	 */
	function count_users($status, $faculty = NULL, $count_invisible_courses = true)
	{
		$course_user_table = Database :: get_main_table(MAIN_COURSE_USER_TABLE);
		$course_table = Database :: get_main_table(MAIN_COURSE_TABLE);
		$sql = "SELECT COUNT(DISTINCT(cu.user_id)) AS number FROM $course_user_table cu, $course_table c WHERE cu.status = $status AND cu.course_code = c.code";
		if (isset ($faculty))
		{
			$sql = "SELECT COUNT(DISTINCT(cu.user_id)) AS number FROM $course_user_table cu, $course_table c WHERE cu.status = $status AND c.code = cu.course_code AND c.category_code = '$faculty'";
		}
		$res = api_sql_query($sql, __FILE__, __LINE__);
		$obj = mysql_fetch_object($res);
		return $obj->number;
	}
	/**
	 * Get all faculties
	 * @return array All faculties (code => name)
	 */
	function get_faculties()
	{
		$category_table = Database :: get_main_table(MAIN_CATEGORY_TABLE);
		$sql = "SELECT * FROM $category_table ORDER BY tree_pos";
		$res = api_sql_query($sql, __FILE__, __LINE__);
		$faculties = array ();
		while ($fac = mysql_fetch_object($res))
		{
			$faculties[$fac->code] = $fac->name;
		}
		return $faculties;
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
		echo '<table class="statTable data_table" cellspacing="0" cellpadding="3">
			  		  <tr><th colspan="4" class="statHeader">'.$title.'</th></tr>';
		$i = 0;
		foreach($stats as $subtitle => $number)
		{
			$total += $number;
		}
		foreach ($stats as $subtitle => $number)
		{
			$i = $i % 13;
			$short_subtitle = str_replace(get_lang('Statistics_Departement'),'',$subtitle);
			$short_subtitle = str_replace(get_lang('Statistics_Central_Administration'),'',$short_subtitle);
			$short_subtitle = trim($short_subtitle);
			if (strlen($subtitle) > 20)
			{
				$short_subtitle = '<acronym title="'.$subtitle.'">'.substr($short_subtitle, 0, 17).'...</acronym>';
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
								<td width="150">'.$short_subtitle.'</td>
								<td width="550">
						 			<img src="../../img/bar_1u.gif" width="'.$data[$subtitle].'" height="10"/>
								</td>
								<td align="right">'.$number_label.'</td>
								<td align="right"> '.number_format(100*$number/$total, 1, ',', '.').'%</td>
				 			</tr>';
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
	 * Show statistics about the number of curriculum courses per year
	 */
	function print_curriculum_courses_stats_by_year()
	{
		$faculties = Statistics::get_faculties();
		for($year = CURRENT_ACADEMIC_YEAR; $year >= FIRST_ACADEMIC_YEAR; $year--)
		{
			$cdb = 'cdb'.($year != CURRENT_ACADEMIC_YEAR ? $year : '');
			$sql = 'SELECT c.category_code AS cat_code,COUNT(c.code) AS number_of_courses FROM dokeos_main.course c, '.$cdb.'.vak v WHERE c.visual_code = v.vak GROUP BY c.category_code';
			$res = api_sql_query($sql,__FILE__,__LINE__);
			$result = array();
			while($obj = mysql_fetch_object($res))
			{
				$result[$faculties[$obj->cat_code]] = $obj->number_of_courses;
			}
			Statistics::print_stats(get_lang('Statistics_NumberOfCourses').$year.'-'.($year+1),$result,true);
		}
	}
	/**
	 * Show statistics about the number of curriculum courses per year per department
	 */
	function print_curriculum_courses_stats_by_category()
	{
		$result = array();
		$faculties = Statistics::get_faculties();
		for($year = CURRENT_ACADEMIC_YEAR; $year >= FIRST_ACADEMIC_YEAR; $year--)
		{
			$cdb = 'cdb'.($year != CURRENT_ACADEMIC_YEAR ? $year : '');
			$sql = 'SELECT c.category_code AS cat_code,COUNT(c.code) AS number_of_courses FROM dokeos_main.course c, '.$cdb.'.vak v WHERE c.visual_code = v.vak GROUP BY c.category_code';
			$res = api_sql_query($sql,__FILE__,__LINE__);
			while($obj = mysql_fetch_object($res))
			{
				$result[$faculties[$obj->cat_code]][$year.'-'.($year+1)] = $obj->number_of_courses;
			}
		}
		foreach($result as $faculty => $stats)
		{
			Statistics::print_stats(get_lang('Statistics_CurriculumCourses').$faculty,$stats,true);
		}
	}
	/**
	 * Show some stats about the number of logins
	 * @param string $type month, hour or day
	 */
	function print_login_stats($type)
	{
		switch($type)
		{
			case 'month':
				$sql = "SELECT DATE_FORMAT( login_date, '%Y %b' ) AS stat_date , count( login_id ) AS number_of_logins FROM dokeos_stats.track_e_login GROUP BY stat_date ORDER BY login_date ";
				break;
			case 'hour':
				$sql = "SELECT DATE_FORMAT( login_date, '%H' ) AS stat_date , count( login_id ) AS number_of_logins FROM dokeos_stats.track_e_login GROUP BY stat_date ORDER BY stat_date ";
				break;
			case 'day':
				$sql = "SELECT DATE_FORMAT( login_date, '%a' ) AS stat_date , count( login_id ) AS number_of_logins FROM dokeos_stats.track_e_login GROUP BY stat_date ORDER BY DATE_FORMAT( login_date, '%w' ) ";
				break;
		}
		$res = api_sql_query($sql,__FILE__,__LINE__);
		$result = array();
		while($obj = mysql_fetch_object($res))
		{
			$result[$obj->stat_date] = $obj->number_of_logins;
		}
		Statistics::print_stats(get_lang('Statistics_count_logins'),$result,true);
	}
	/**
	 * Print the number of resent logins (last minute, hour, day, month)
	 */
	function print_recent_login_stats()
	{
		$total_logins = 0;

		$sql['... ' & get_lang('Statistics_the_past_minute')] = "SELECT count(DISTINCT access_user_id) AS number FROM dokeos_stats.track_e_lastaccess WHERE DATE_ADD(access_date, INTERVAL 1 MINUTE) >= NOW()";
		$sql['... ' & get_lang('Statistics_the_past_hour')] = "SELECT count(DISTINCT access_user_id) AS number  FROM dokeos_stats.track_e_lastaccess WHERE DATE_ADD(access_date, INTERVAL 1 HOUR) >= NOW()";
		$sql['... ' & get_lang('Statistics_the_past_day')] = "SELECT count(DISTINCT access_user_id) AS number  FROM dokeos_stats.track_e_lastaccess WHERE DATE_ADD(access_date, INTERVAL 1 DAY) >= NOW()";
		$sql['... ' & get_lang('Statistics_the_past_month')] = "SELECT count(DISTINCT access_user_id) AS number  FROM dokeos_stats.track_e_lastaccess WHERE DATE_ADD(access_date, INTERVAL 1 MONTH) >= NOW()";


		foreach($sql as $index => $query)
		{
			#
			#$index.': ';
			$res = api_sql_query($query,__FILE__,__LINE__);
			$obj = mysql_fetch_object($res);

			/*
			 * echo number_format($obj->number, 0, ',', '.');
			 * echo '<br/>';
			*/
				$total_logins += number_format($obj->number, 0, ',', '.');
		}


		Statistics::print_stats(get_lang('Statistics_Number_of_active_users_on_dokeos'),$total_logins,true);
	}
	/**
	 * Show some stats about the accesses to the different course tools
	 */
	function print_tool_stats()
	{
		$tools = array('announcement','assignment','calendar_event','chat','conference','course_description','document','dropbox','group','learnpath','link','quiz','student_publication','user','bb_forum');
		$sql = "SELECT access_tool, count( access_id ) AS number_of_logins FROM dokeos_stats.track_e_access WHERE access_tool IN ('".implode("','",$tools)."') GROUP BY access_tool ";
		$res = api_sql_query($sql,__FILE__,__LINE__);
		$result = array();
		while($obj = mysql_fetch_object($res))
		{
			$result[$obj->access_tool] = $obj->number_of_logins;
		}
		Statistics::print_stats(get_lang('Statistics_Acces_to_coursemodules_hits'),$result,true);
		$sql = "SELECT access_tool, count( access_id ) AS number_of_logins FROM dokeos_stats.track_e_lastaccess WHERE access_tool IN ('".implode("','",$tools)."') GROUP BY access_tool ";
		$res = api_sql_query($sql,__FILE__,__LINE__);
		$result = array();
		while($obj = mysql_fetch_object($res))
		{
			$result[$obj->access_tool] = $obj->number_of_logins;
		}
		Statistics::print_stats(get_lang('Statistics_Acces_to_coursemodules_use'),$result,true);
	}
	/**
	 * Show some stats about the access to old course tools
	 */
	function print_access_to_old_courses_stats()
	{
	$currentDate =  getDate();
	$my_year =  $currentDate["year"];
		$sql = "SELECT 	count( access_id ) AS number,
						IF( LOCATE( 'ALG', access_cours_code ) ,
							'Algemene cursus',
							IF( LENGTH( access_cours_code ) =14, 'Curr. " + my_year-2 + "-" + my_year-1 + "', 'Curr. "+my_year-1 +"-"+ my_year +")
						) AS type
				FROM dokeos_stats.track_e_access
				GROUP BY TYPE";
		$res = api_sql_query($sql,__FILE__,__LINE__);
		$result = array();
		while($obj = mysql_fetch_object($res))
		{
			$result[$obj->type] = $obj->number;
		}
		Statistics::print_stats(get_lang('Statistics_Acces_to_old_curriculum_courses'),$result,true);
	}



	/**
	 * Shows the number of users having their picture uploaded in Dokeos.
	 */
	function print_user_pictures_stats()
	{
		$sql = "SELECT COUNT(*) AS n FROM dokeos_main.user";
		$res = api_sql_query($sql,__FILE__,__LINE__);
		$count1 = mysql_fetch_object($res);
		$sql = "SELECT COUNT(*) AS n FROM dokeos_main.user WHERE picture_uri != ''";
		$res = api_sql_query($sql,__FILE__,__LINE__);
		$count2 = mysql_fetch_object($res);
		Statistics::print_stats(get_lang('user_who_have_picture_in_dokeos'),'('.number_format(($count2->n/$count1->n*100), 0, ',', '.').'%)',true);

	#	echo $count2->n.' ' & get_lang('Statistics_user_who_have_picture_in_dokeos') & '('.number_format(($count2->n/$count1->n*100), 0, ',', '.').'%)';
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

		$per_page = isset($_GET['per_page']) ? $_GET['per_page'] : 10;
		$page_nr = isset($_GET['page_nr']) ? $_GET['page_nr'] : 1;
		$column = isset($_GET['column']) ? $_GET['column'] : 0;
		$date_diff = isset($_GET['date_diff']) ? $_GET['date_diff'] : 60;
		$direction = isset($_GET['direction']) ? $_GET['direction'] : SORT_ASC;
		?>
		<form method="get" action="index.php">
		<input type="hidden" name="action" value="courselastvisit"/>
		<input type="text" name="date_diff" value="<?php echo $date_diff; ?>"/>
		<input type="submit" value="<?php echo get_lang('Search'); ?>"/>
		</form>
		<?php
		$sql = "SELECT * FROM dokeos_stats.track_e_lastaccess GROUP BY access_cours_code HAVING access_cours_code <> '' AND DATEDIFF( NOW() , access_date ) > ". $date_diff;
		$res = api_sql_query($sql,__FILE__,__LINE__);
		$number_of_courses = mysql_num_rows($res);
		$sql .= ' ORDER BY '.$columns[$column].' '.$sql_order[$direction];
		$from = ($page_nr -1) * $per_page;
		$sql .= ' LIMIT '.$from.','.$per_page;
		echo Get_lang('Statistics_Last_login_more_than').$date_diff.get_lang('Statistics_Days_ago');
		$res = api_sql_query($sql, __FILE__, __LINE__);
		if (mysql_num_rows($res) > 0)
		{
			$courses = array ();
			while ($obj = mysql_fetch_object($res))
			{
				$course = array ();
				$course[]= '<a href="http://dokeos.hogent.be/courses/'.$obj->access_cours_code.'">'.$obj->access_cours_code.' <a>';
				$course[] = $obj->access_date;
				$courses[] = $course;
			}

			$table_header[] = array ("Coursecode", true);
			$table_header[] = array ("Last login", true);
				HoGent::display_page_navigation($number_of_courses,$per_page, $page_nr,array_merge($parameters,$_GET));
			Display :: display_sortable_table($table_header, $courses, array ('column'=>$column,'direction'=>$direction), array (), $parameters);
			HoGent::display_page_navigation($number_of_courses,$per_page, $page_nr,array_merge($parameters,$_GET));
			foreach(array_merge($parameters,$_GET) as $id => $value)
			{
				if ($id!='selectall'){
					$link .= $id.'='.$value.'&amp;';
				}
			}

		}
		else
		{
			echo get_lang('NoSearchResults');
		}
	}

}
?>