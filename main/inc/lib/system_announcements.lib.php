<?php
/*
============================================================================== 
	Dokeos - elearning and course management software
	
	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) 2004 Bart Mollet (HoGent)
	
	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".
	
	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.
	
	See the GNU General Public License for more details.
	
	Contact address: Dokeos, 44 rue des palais, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
============================================================================== 
*/
define('VISIBLE_GUEST', 1);
define('VISIBLE_STUDENT', 2);
define('VISIBLE_TEACHER', 3);
/**
============================================================================== 
*	This is the system announcements library for Dokeos.
*
*	@package dokeos.library
============================================================================== 
*/
class SystemAnnouncementManager
{
	/** 
	 * Displays all announcements
	 * @param int $visible VISIBLE_GUEST, VISIBLE_STUDENT or VISIBLE_TEACHER
	 * @param int $id The identifier of the announcement to display
	 */
	function display_announcements($visible, $id = -1)
	{
		$user_selected_language = $_SESSION["user_language_choice"] ? $_SESSION["user_language_choice"] : get_setting('platformLanguage');

		$db_table = Database :: get_main_table(MAIN_SYSTEM_ANNOUNCEMENTS_TABLE);
		$sql = "SELECT *, DATE_FORMAT(date_start,'%d-%m-%Y') AS display_date FROM ".$db_table." WHERE lang='$user_selected_language' AND (NOW() BETWEEN date_start AND date_end) OR date_end='0000-00-00' ";
		switch ($visible)
		{
			case VISIBLE_GUEST :
				$sql .= " AND visible_guest = '1'";
				break;
			case VISIBLE_STUDENT :
				$sql .= " AND visible_student = '1'";
				break;
			case VISIBLE_TEACHER :
				$sql .= " AND visible_teacher = '1'";
				break;
		}
		$sql .= " ORDER BY date_start DESC LIMIT 0,7";
	
		$announcements = api_sql_query($sql,__FILE__,__LINE__);
		if (mysql_num_rows($announcements))
		{
			$query_string = ereg_replace('announcement=[1-9]+', '', $_SERVER['QUERY_STRING']);
			$query_string = ereg_replace('&$', '', $query_string);
			$url = $_SERVER['PHP_SELF'];
			echo '<div class="system_announcements">';
			echo '<h3>'.get_lang('SystemAnnouncements').'</h3>';
			echo '<table border="0">';
			while ($announcement = mysql_fetch_object($announcements))
			{
				
				if ($id != $announcement->id)
				{
					if (strlen($query_string) > 0)
					{
						$show_url = 'newsList.php#'.$announcement->id;
						//$show_url = $url.'?'.$query_string.'&announcement='.$announcement->id;
					}
					else
					{
						$show_url = 'newsList.php#'.$announcement->id;
						//$show_url = $url.'?announcement='.$announcement->id;
					}
					echo '<tr class="system_announcement">
							<td width="80px" valign="top" class="system_announcement_title">'
								.$announcement->display_date.'
							</td>
							<td valign="top">
								<a name="ann'.$announcement->id.'" href="'.$show_url.'">'.$announcement->title.'</a>
							</td>
						</tr>';
				}
				else
				{
					echo '<div class="system_announcement">
							<div class="system_announcement_title">'
								.$announcement->display_date.' 
								<a name="ann'.$announcement->id.'" href="'.$url.'?'.$query_string.'#ann'.$announcement->id.'">'.$announcement->title.'</a>
							</div>
							<div class="system_announcement_content">'
								.$announcement->content.'
							</div>';
				}
				
			}
			
			echo '<tr><td height="15px"></td></tr>';
			echo '<tr><td colspan="2">';
			echo '<a href="newsList.php">'.get_lang("More").'</a>';
			echo '</td></tr>';
			echo '</table>';
			echo '</div>';
		}
		return;
	}
	
	function display_all_announcements($visible, $id = -1,$start = 0,$user_id)
	{	
		
		$user_selected_language = $_SESSION["user_language_choice"] ? $_SESSION["user_language_choice"] : get_setting('platformLanguage');
		
		$db_table = Database :: get_main_table(MAIN_SYSTEM_ANNOUNCEMENTS_TABLE);
		$sql = "SELECT *, DATE_FORMAT(date_start,'%d-%m-%Y') AS display_date FROM ".$db_table." 
				WHERE lang='$user_selected_language' AND (NOW() BETWEEN date_start AND date_end) 
				OR date_end='0000-00-00'";
		
		switch ($visible)
		{
			case VISIBLE_GUEST :
				$sql .= " AND visible_guest = 'true'";
				break;
			case VISIBLE_STUDENT :
				$sql .= " AND visible_student = 'true'";
				break;
			case VISIBLE_TEACHER :
				$sql .= " AND visible_teacher = 'true'";
				break;
		}
		
		if(!isset($_GET['start']) || $_GET['start'] == 0)
		{
			$sql .= " ORDER BY date_start DESC LIMIT ".$start.",20";
		}
		else
		{
			$sql .= " ORDER BY date_start DESC LIMIT ".($start+1).",20";
		}
		$announcements = api_sql_query($sql,__FILE__,__LINE__);
		
		if (mysql_num_rows($announcements))
		{
			$query_string = ereg_replace('announcement=[1-9]+', '', $_SERVER['QUERY_STRING']);
			$query_string = ereg_replace('&$', '', $query_string);
			$url = $_SERVER['PHP_SELF'];
			echo '<div class="system_announcements">';
			echo '<h3>'.get_lang('SystemAnnouncements').'</h3>';
			echo '<table align="center">';
				echo '<tr>';
					echo '<td>';
						SystemAnnouncementManager :: display_fleche($user_id);
					echo '</td>';
				echo '</tr>';
			echo '</table>';
			echo '<table align="center" border="0" width="900px">';
			while ($announcement = mysql_fetch_object($announcements))
			{
					echo '<tr><td>';
					echo '<a name="'.$announcement->id.'"></a>
							<div class="system_announcement">
							<div class="system_announcement_title">'
								.$announcement->display_date.' <strong>'.$announcement->title.'</strong>
							</div>
							<br />
						  	<div class="system_announcement_content">'
						  			.$announcement->content.'
							</div>
						  </div>
							<br />
						  <hr noshade size="1">';
					echo '</tr></td>';
			}
			echo '</table>';
			echo '<table align="center">';
				echo '<tr>';
					echo '<td>';
						SystemAnnouncementManager :: display_fleche($user_id);
					echo '</td>';
				echo '</tr>';
			echo '</table>';
			echo '</div>';
		}
		return;
	}
	
	function display_fleche($user_id)
	{
		$start = (int)$_GET['start'];
		$nb_announcement = SystemAnnouncementManager :: count_nb_announcement($start,$user_id);
		$next = ((int)$_GET['start']+19);
		$prev = ((int)$_GET['start']-19);
		
		if(!isset($_GET['start']) || $_GET['start'] == 0)
		{
			
			if($nb_announcement > 20)
			{
				echo '<a href="newsList.php?start='.$next.'">'.get_lang('NextBis').' >> </a>';
			}
			
		}
		else
		{
			echo '<a href="newsList.php?start='.$prev.'"> << '.get_lang('Prev').'</a>';
			
			if($nb_announcement > 20)
			{
				echo '<a href="newsList.php?start='.$next.'">'.get_lang('NextBis').' >> </a>';
			}
			
	}
		
	}
	
	function count_nb_announcement($start = 0,$user_id = '')
	{
		$visibility = api_is_allowed_to_create_course() ? VISIBLE_TEACHER : VISIBLE_STUDENT;
		$user_selected_language = $_SESSION["user_language_choice"] ? $_SESSION["user_language_choice"] : get_setting('platformLanguage');
		$db_table = Database :: get_main_table(MAIN_SYSTEM_ANNOUNCEMENTS_TABLE);
		$sql = 'SELECT id 
				FROM '.$db_table.'
				WHERE lang="'.$user_selected_language.'"';
		if (isset($user_id))
 		{		
			switch ($visibility)
			{
				case VISIBLE_GUEST :
					$sql .= " AND visible_guest = 'true'";
					break;
				case VISIBLE_STUDENT :
					$sql .= " AND visible_student = 'true'";
					break;
				case VISIBLE_TEACHER :
					$sql .= " AND visible_teacher = 'true'";
					break;
			}
 		}
		$sql .= 'LIMIT '.$start.',21';
		$announcements = api_sql_query($sql,__FILE__,__LINE__);
		$i = 0;
		while($rows = mysql_fetch_array($announcements))
		{
			$i++;
		}
		return $i;
	}
	
	/**
	 * Get all announcements
	 * @return array An array with all available system announcements (as php
	 * objects)
	 */
	function get_all_announcements($lang)
	{
		$db_table = Database :: get_main_table(MAIN_SYSTEM_ANNOUNCEMENTS_TABLE);

		$where="";

		if(!empty($lang))
		{
                    $where="WHERE lang='$lang'";
		}

		$sql = "SELECT *, IF( NOW() BETWEEN date_start AND date_end, '1', '0') AS visible FROM ".$db_table." $where ORDER BY date_start ASC";
		$announcements = api_sql_query($sql,__FILE__,__LINE__);
		$all_announcements = array();
		while ($announcement = mysql_fetch_object($announcements))
		{
			$all_announcements[] = $announcement;
		}
		return $all_announcements;
	}
	/**
	 * Adds an announcement to the database
	 * @param string $title Title of the announcement
	 * @param string $content Content of the announcement
	 * @param string $date_start Start date (YYYY-MM-DD HH:II: SS)
	 * @param string $date_end End date (YYYY-MM-DD HH:II: SS)
	 */
	function add_announcement($title, $content, $date_start, $date_end, $visible_teacher = 'false', $visible_student = 'false', $visible_guest = 'false', $lang = 'dutch')
	{
		
		$a_dateS = explode(' ',$date_start);
		$a_arraySD = explode('-',$a_dateS[0]);
		$a_arraySH = explode(':',$a_dateS[1]);
		$date_start = array_merge($a_arraySD,$a_arraySH);
		
		$a_dateE = explode(' ',$date_end);
		$a_arrayED = explode('-',$a_dateE[0]);
		$a_arrayEH = explode(':',$a_dateE[1]);
		$date_end = array_merge($a_arrayED,$a_arrayEH);
		
		$db_table = Database :: get_main_table(MAIN_SYSTEM_ANNOUNCEMENTS_TABLE);
		
		if (!checkdate($date_start[1], $date_start[2], $date_start[0]))
		{
			Display :: display_normal_message(get_lang('InvalidStartDate'));
			return false;
		}
		if (($date_end[1] || $date_end[2] || $date_end[0]) && !checkdate($date_end[1], $date_end[2], $date_end[0]))
		{			
			Display :: display_normal_message(get_lang('InvalidEndDate'));
			return false;
		}
		if( strlen(trim($title)) == 0)
		{
			Display::display_normal_message(get_lang('InvalidTitle'));
			return false;	
		}
		$start = $date_start[0]."-".$date_start[1]."-".$date_start[2]." ".$date_start[3].":".$date_start[4].":".$date_start[5];
		$end = $date_end[0]."-".$date_end[1]."-".$date_end[2]." ".$date_end[3].":".$date_end[4].":".$date_start[5];
		$sql = "INSERT INTO ".$db_table." (`title`,`content`,`date_start`,`date_end`,`visible_teacher`,`visible_student`,`visible_guest`, `lang`) 
												VALUES ('".$title."','".$content."','".$start."','".$end."','".$visible_teacher."','".$visible_student."','".$visible_guest."','".$lang."')";
		
		return api_sql_query($sql,__FILE__,__LINE__);
	}
	/**
	 * Updates an announcement to the database
	 * @param integer $id      : id of the announcement
	 * @param string  $title   : title of the announcement
	 * @param string  $content : content of the announcement
	 * @param array $date_start: start date of announcement (0 => day ; 1 => month ; 2 => year ; 3 => hour ; 4 => minute)
	 * @param array $date_end : end date of announcement (0 => day ; 1 => month ; 2 => year ; 3 => hour ; 4 => minute)
	 */
	function update_announcement($id, $title, $content, $date_start, $date_end, $visible_teacher = 'false', $visible_student = 'false', $visible_guest = 'false',$lang='dutch')
	{
		
		$a_dateS = explode(' ',$date_start);
		$a_arraySD = explode('-',$a_dateS[0]);
		$a_arraySH = explode(':',$a_dateS[1]);
		$date_start = array_merge($a_arraySD,$a_arraySH);
		
		$a_dateE = explode(' ',$date_end);
		$a_arrayED = explode('-',$a_dateE[0]);
		$a_arrayEH = explode(':',$a_dateE[1]);
		$date_end = array_merge($a_arrayED,$a_arrayEH);
		
		$db_table = Database :: get_main_table(MAIN_SYSTEM_ANNOUNCEMENTS_TABLE);
if (!checkdate($date_start[1], $date_start[2], $date_start[0]))
		{
			Display :: display_normal_message(get_lang('InvalidStartDate'));
			return false;
		}
		if (($date_end[1] || $date_end[2] || $date_end[0]) && !checkdate($date_end[1], $date_end[2], $date_end[0]))
		{			
			Display :: display_normal_message(get_lang('InvalidEndDate'));
			return false;
		}
		if( strlen(trim($title)) == 0)
		{
			Display::display_normal_message(get_lang('InvalidTitle'));
			return false;	
		}
		$start = $date_start[0]."-".$date_start[1]."-".$date_start[2]." ".$date_start[3].":".$date_start[4].":".$date_start[5];
		$end = $date_end[0]."-".$date_end[1]."-".$date_end[2]." ".$date_end[3].":".$date_end[4].":".$date_start[5];
		$sql = "UPDATE ".$db_table." SET lang='$lang',title='".$title."',content='".$content."',date_start='".$start."',date_end='".$end."', ";
		$sql .= " visible_teacher = '".$visible_teacher."', visible_student = '".$visible_student."', visible_guest = '".$visible_guest."' WHERE id='".$id."'";
		return api_sql_query($sql,__FILE__,__LINE__);
	}
	/**
	 * Deletes an announcement
	 * @param integer $id The identifier of the announcement that should be
	 * deleted
	 */
	function delete_announcement($id)
	{
		$db_table = Database :: get_main_table(MAIN_SYSTEM_ANNOUNCEMENTS_TABLE);
		$sql = "DELETE FROM ".$db_table." WHERE id='".$id."'";
		return api_sql_query($sql,__FILE__,__LINE__);
	}
	/**
	 * Gets an announcement
	 * @param integer $id The identifier of the announcement that should be
	 * deleted
	 */
	function get_announcement($id)
	{
		$db_table = Database :: get_main_table(MAIN_SYSTEM_ANNOUNCEMENTS_TABLE);
		$sql = "SELECT * FROM ".$db_table." WHERE id='".$id."'";
		$announcement = mysql_fetch_object(api_sql_query($sql,__FILE__,__LINE__));
		return $announcement;
	}
	/**
	 * Change the visibility of an announcement
	 * @param integer $announcement_id
	 * @param integer $user For who should the visibility be changed (possible
	 * values are VISIBLE_TEACHER, VISIBLE_STUDENT, VISIBLE_GUEST)
	 */
	function set_visibility($announcement_id, $user, $visible)
	{
		$db_table = Database :: get_main_table(MAIN_SYSTEM_ANNOUNCEMENTS_TABLE);
		$field = ($user == VISIBLE_TEACHER ? 'visible_teacher' : ($user == VISIBLE_STUDENT ? 'visible_student' : 'visible_guest'));
		$sql = "UPDATE ".$db_table." SET ".$field." = '".$visible."' WHERE id='".$announcement_id."'";
		return api_sql_query($sql,__FILE__,__LINE__);
	}
}
?>