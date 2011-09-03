<?php
/* For licensing terms, see /license.txt */
/**
 * @package chamilo.library
 */
/**
 * Code
 */
define('VISIBLE_GUEST', 1);
define('VISIBLE_STUDENT', 2);
define('VISIBLE_TEACHER', 3);
/**
*	This is the system announcements library for Dokeos.
*
*	@package chamilo.library
*/
class SystemAnnouncementManager {
	/**
	 * Displays all announcements
	 * @param int $visible VISIBLE_GUEST, VISIBLE_STUDENT or VISIBLE_TEACHER
	 * @param int $id The identifier of the announcement to display
	 */
	public static function display_announcements($visible, $id = -1) {
		$user_selected_language = api_get_interface_language();
		$db_table = Database :: get_main_table(TABLE_MAIN_SYSTEM_ANNOUNCEMENTS);		
		
		$sql = "SELECT *, DATE_FORMAT(date_start,'%d-%m-%Y %h:%i:%s') AS display_date FROM ".$db_table." WHERE (lang='$user_selected_language' OR lang IS NULL) AND ((NOW() BETWEEN date_start AND date_end) OR date_end='0000-00-00') ";
		switch ($visible) {
			case VISIBLE_GUEST :
				$sql .= " AND visible_guest = 1 ";
				break;
			case VISIBLE_STUDENT :
				$sql .= " AND visible_student = 1 ";
				break;
			case VISIBLE_TEACHER :
				$sql .= " AND visible_teacher = 1 ";
				break;
		}	
		$sql .= " AND access_url_id = ".api_get_current_access_url_id()." ";
		$sql .= " ORDER BY date_start DESC LIMIT 0,7";
		
		$announcements = Database::query($sql);
		if (Database::num_rows($announcements) > 0) {
			$query_string = ereg_replace('announcement=[1-9]+', '', $_SERVER['QUERY_STRING']);
			$query_string = ereg_replace('&$', '', $query_string);
			$url = api_get_self();
			echo '<div class="system_announcements">';
			
			echo '<h3>'.get_lang('SystemAnnouncements').'</h3>';
			
			echo '<div style="margin:10px;text-align:right;"><a href="news_list.php">'.get_lang('More').'</a></div>';		
			
			while ($announcement = Database::fetch_object($announcements)) {
				if ($id != $announcement->id) {
					if (strlen($query_string) > 0) {
						$show_url = 'news_list.php#'.$announcement->id;
					} else {
						$show_url = 'news_list.php#'.$announcement->id;
					}
			        $display_date = api_convert_and_format_date($announcement->display_date, DATE_FORMAT_LONG);					
					echo '<a name="'.$announcement->id.'"></a>
						<div class="system_announcement">
							<div class="system_announcement_title"><a name="ann'.$announcement->id.'" href="'.$show_url.'">'.$announcement->title.'</a></div><div class="system_announcement_date">'.$display_date.'</div>
					  	</div>';				
				} else {
					echo '<div class="system_announcement">
							<div class="system_announcement_title">'
								.$announcement->display_date.'
								<a name="ann'.$announcement->id.'" href="'.$url.'?'.$query_string.'#ann'.$announcement->id.'">'.$announcement->title.'</a>
							</div>';
				}
				echo '<br />';
			}
			echo '</div>';
		}
		return;
	}

	public static function display_all_announcements($visible, $id = -1, $start = 0,$user_id='') {
		$user_selected_language = api_get_interface_language();
		$start	= intval($start);				

		$db_table = Database :: get_main_table(TABLE_MAIN_SYSTEM_ANNOUNCEMENTS);
		$sql = "SELECT *, DATE_FORMAT(date_start,'%d-%m-%Y %h:%i:%s') AS display_date FROM ".$db_table."
				WHERE (lang='$user_selected_language' OR lang IS NULL) AND ((NOW() BETWEEN date_start AND date_end)	OR date_end='0000-00-00')";
				
		switch ($visible) {
			case VISIBLE_GUEST :
				$sql .= " AND visible_guest = 1 ";
				break;
			case VISIBLE_STUDENT :
				$sql .= " AND visible_student = 1 ";
				break;
			case VISIBLE_TEACHER :
				$sql .= " AND visible_teacher = 1 ";
				break;
		}
		
		global $_configuration;
		$current_access_url_id = 1;
		if ($_configuration['multiple_access_urls']) {
			$current_access_url_id = api_get_current_access_url_id();
		}
		$sql .= " AND access_url_id = '$current_access_url_id' ";		

		if(!isset($_GET['start']) || $_GET['start'] == 0) {
			$sql .= " ORDER BY date_start DESC LIMIT ".$start.",20";
		} else {
			$sql .= " ORDER BY date_start DESC LIMIT ".($start+1).",20";
		}
		$announcements = Database::query($sql);

		if (Database::num_rows($announcements) > 0) {
			$query_string = ereg_replace('announcement=[1-9]+', '', $_SERVER['QUERY_STRING']);
			$query_string = ereg_replace('&$', '', $query_string);
			$url = api_get_self();
			echo '<div class="system_announcements">';
			echo '<h3>'.get_lang('SystemAnnouncements').'</h3>';
			echo '<table align="center">';
				echo '<tr>';
					echo '<td>';
						SystemAnnouncementManager :: display_arrow($user_id);
					echo '</td>';
				echo '</tr>';
			echo '</table>';
			echo '<table align="center" border="0" width="900px">';			
			while ($announcement = Database::fetch_object($announcements)) {
				$display_date = api_convert_and_format_date($announcement->display_date, DATE_FORMAT_LONG);
				echo '<tr><td>';
				echo '<a name="'.$announcement->id.'"></a>
						<div class="system_announcement">
						<div class="system_announcement_title">'.$announcement->title.'</div><div class="system_announcement_date">'.$display_date.'</div>
						<br />
					  	<div class="system_announcement_content">'
					  			.$announcement->content.'
						</div>
					  </div><br />';
				echo '</tr></td>';
			}
			echo '</table>';
			
			echo '<table align="center">';
				echo '<tr>';
					echo '<td>';
						SystemAnnouncementManager :: display_arrow($user_id);
					echo '</td>';
				echo '</tr>';
			echo '</table>';
			echo '</div>';
		}
	}

	public static function display_arrow($user_id) {
		$start = (int)$_GET['start'];
		$nb_announcement = SystemAnnouncementManager :: count_nb_announcement($start,$user_id);
		$next = ((int)$_GET['start']+19);
		$prev = ((int)$_GET['start']-19);

		if(!isset($_GET['start']) || $_GET['start'] == 0) {
			if($nb_announcement > 20) {
				echo '<a href="news_list.php?start='.$next.'">'.get_lang('NextBis').' >> </a>';
			}
		} else {
			echo '<a href="news_list.php?start='.$prev.'"> << '.get_lang('Prev').'</a>';
			if ($nb_announcement > 20) {
				echo '<a href="news_list.php?start='.$next.'">'.get_lang('NextBis').' >> </a>';
			}
		}
	}

	public static function count_nb_announcement($start = 0, $user_id = '') {
		$start = intval($start);
		$visibility = api_is_allowed_to_create_course() ? VISIBLE_TEACHER : VISIBLE_STUDENT;
		$user_selected_language = api_get_interface_language();
		$db_table = Database :: get_main_table(TABLE_MAIN_SYSTEM_ANNOUNCEMENTS);
		$sql = 'SELECT id FROM '.$db_table.' WHERE (lang="'.$user_selected_language.'" OR lang IS NULL) ';
		if (isset($user_id)) {
			switch ($visibility) {
				case VISIBLE_GUEST :
					$sql .= " AND visible_guest = 1 ";
					break;
				case VISIBLE_STUDENT :
					$sql .= " AND visible_student = 1 ";
					break;
				case VISIBLE_TEACHER :
					$sql .= " AND visible_teacher = 1 ";
					break;
			}
 		}
 		
 		global $_configuration;
		$current_access_url_id = 1;
		if ($_configuration['multiple_access_urls']) {
			$current_access_url_id = api_get_current_access_url_id();
		}
		$sql .= " AND access_url_id = '$current_access_url_id' ";
		
		
		$sql .= 'LIMIT '.$start.',21';
		$announcements = Database::query($sql);
		$i = 0;
		while($rows = Database::fetch_array($announcements)) {
			$i++;
		}
		return $i;
	}

	/**
	 * Get all announcements
	 * @return array An array with all available system announcements (as php
	 * objects)
	 */
	public static function get_all_announcements() {
		$db_table = Database :: get_main_table(TABLE_MAIN_SYSTEM_ANNOUNCEMENTS);
		$now = api_get_utc_datetime();
		$sql = "SELECT *, IF( '$now'  >= date_start AND '$now' <= date_end, '1', '0') AS visible FROM ".$db_table." ";		
		
		global $_configuration;
		$current_access_url_id = 1;
		if ($_configuration['multiple_access_urls']) {
			$current_access_url_id = api_get_current_access_url_id();
		}
		$sql .= " WHERE access_url_id = '$current_access_url_id' ";
		$sql .= " ORDER BY date_start ASC";		
		
		$announcements = Database::query($sql);
		$all_announcements = array();
		while ($announcement = Database::fetch_object($announcements)) {
			$all_announcements[] = $announcement;
		}
		return $all_announcements;
	}
	
	/**
	 * Adds an announcement to the database
	 * @param string Title of the announcement
	 * @param string Content of the announcement
	 * @param string Start date (YYYY-MM-DD HH:II: SS)
	 * @param string End date (YYYY-MM-DD HH:II: SS)
	 * @param int    Whether the announcement should be visible to teachers (1) or not (0)
	 * @param int    Whether the announcement should be visible to students (1) or not (0)
	 * @param int    Whether the announcement should be visible to anonymous users (1) or not (0)
	 * @param string The language for which the announvement should be shown. Leave null for all langages
	 * @param int    Whether to send an e-mail to all users (1) or not (0)
	 * @return bool  True on success, false on failure
	 */
	public static function add_announcement($title, $content, $date_start, $date_end, $visible_teacher = 0, $visible_student = 0, $visible_guest = 0, $lang = null, $send_mail = 0, $add_to_calendar = false ) {
		$a_dateS = explode(' ',$date_start);
		$a_arraySD = explode('-',$a_dateS[0]);
		$a_arraySH = explode(':',$a_dateS[1]);
		$date_start_to_compare = array_merge($a_arraySD,$a_arraySH);

		$a_dateE = explode(' ',$date_end);
		$a_arrayED = explode('-',$a_dateE[0]);
		$a_arrayEH = explode(':',$a_dateE[1]);
		$date_end_to_compare = array_merge($a_arrayED,$a_arrayEH);

		$db_table = Database :: get_main_table(TABLE_MAIN_SYSTEM_ANNOUNCEMENTS);

		if (!checkdate($date_start_to_compare[1], $date_start_to_compare[2], $date_start_to_compare[0])) {
			Display :: display_normal_message(get_lang('InvalidStartDate'));
			return false;
		}
		if (($date_end_to_compare[1] || $date_end_to_compare[2] || $date_end_to_compare[0]) && !checkdate($date_end_to_compare[1], $date_end_to_compare[2], $date_end_to_compare[0])) {
			Display :: display_normal_message(get_lang('InvalidEndDate'));
			return false;
		}
		if( strlen(trim($title)) == 0) {
			Display::display_normal_message(get_lang('InvalidTitle'));
			return false;
		}
		
		$start    = api_get_utc_datetime($date_start);
		$end      = api_get_utc_datetime($date_end);		
		
		$title = Database::escape_string($title);
		$content = Database::escape_string($content);

		//Fixing urls that are sent by email
		$content = str_replace('src=\"/home/', 'src=\"'.api_get_path(WEB_PATH).'home/', $content);
		$content = str_replace('file=/home/', 'file='.api_get_path(WEB_PATH).'home/', $content);

		$langsql = is_null($lang) ? 'NULL' : "'".Database::escape_string($lang)."'";
				
		global $_configuration;
		$current_access_url_id = 1;
		if ($_configuration['multiple_access_urls']) {
			$current_access_url_id = api_get_current_access_url_id();
		}
		
		$sql = "INSERT INTO ".$db_table." (title,content,date_start,date_end,visible_teacher,visible_student,visible_guest, lang, access_url_id)
				VALUES ('".$title."','".$content."','".$start."','".$end."','".$visible_teacher."','".$visible_student."','".$visible_guest."',".$langsql.", ".$current_access_url_id.")";
		if ($send_mail==1) {
			SystemAnnouncementManager::send_system_announcement_by_email($title, $content,$visible_teacher, $visible_student, $lang);
		}
		$res = Database::query($sql);
		if ($res === false) {
			Debug::log_s(mysql_error());
			return false;
		}		
		if ($add_to_calendar) {
		    require_once 'calendar.lib.php';
		    $agenda_id = agenda_add_item($title, $content, $date_start, $date_end);
		}
		return true;
	}
	/**
	 * Updates an announcement to the database
	 * @param integer $id      : id of the announcement
	 * @param string  $title   : title of the announcement
	 * @param string  $content : content of the announcement
	 * @param array $date_start: start date of announcement (0 => day ; 1 => month ; 2 => year ; 3 => hour ; 4 => minute)
	 * @param array $date_end : end date of announcement (0 => day ; 1 => month ; 2 => year ; 3 => hour ; 4 => minute)
	 * @return	bool	True on success, false on failure
	 */
	public static function update_announcement($id, $title, $content, $date_start, $date_end, $visible_teacher = 0, $visible_student = 0, $visible_guest = 0,$lang=null, $send_mail=0) {
		$a_dateS = explode(' ',$date_start);
		$a_arraySD = explode('-',$a_dateS[0]);
		$a_arraySH = explode(':',$a_dateS[1]);
		$date_start_to_compare = array_merge($a_arraySD,$a_arraySH);

		$a_dateE = explode(' ',$date_end);
		$a_arrayED = explode('-',$a_dateE[0]);
		$a_arrayEH = explode(':',$a_dateE[1]);
		$date_end_to_compare = array_merge($a_arrayED,$a_arrayEH);
		
		$langsql = is_null($lang) ? 'NULL' : "'".Database::escape_string($lang)."'";
		$db_table = Database :: get_main_table(TABLE_MAIN_SYSTEM_ANNOUNCEMENTS);
		
		if (!checkdate($date_start_to_compare[1], $date_start_to_compare[2], $date_start_to_compare[0])) {
			Display :: display_normal_message(get_lang('InvalidStartDate'));
			return false;
		}
		if (($date_end_to_compare[1] || $date_end_to_compare[2] || $date_end_to_compare[0]) && !checkdate($date_end_to_compare[1], $date_end_to_compare[2], $date_end_to_compare[0])) {
			Display :: display_normal_message(get_lang('InvalidEndDate'));
			return false;
		}
		if( strlen(trim($title)) == 0) {
			Display::display_normal_message(get_lang('InvalidTitle'));
			return false;
		}
	    $start    = api_get_utc_datetime($date_start);
        $end      = api_get_utc_datetime($date_end);
        
		$title = Database::escape_string($title);
		$content = Database::escape_string($content);

		//Fixing urls that are sent by email
		$content = str_replace('src=\"/home/', 'src=\"'.api_get_path(WEB_PATH).'home/', $content);
		$content = str_replace('file=/home/', 'file='.api_get_path(WEB_PATH).'home/', $content);
		
		$id = intval($id);
		$sql = "UPDATE ".$db_table." SET lang=$langsql,title='".$title."',content='".$content."',date_start='".$start."',date_end='".$end."', ";
		$sql .= " visible_teacher = '".$visible_teacher."', visible_student = '".$visible_student."', visible_guest = '".$visible_guest."' , access_url_id = '".api_get_current_access_url_id()."'  WHERE id = ".$id;

		if ($send_mail==1) {
			SystemAnnouncementManager::send_system_announcement_by_email($title, $content,$visible_teacher, $visible_student, $lang);
		}
		$res = Database::query($sql);
		if ($res === false) {
			Debug::log_s(mysql_error());
			return false;
		}
		return true;
	}
	/**
	 * Deletes an announcement
	 * @param 	int $id The identifier of the announcement that should be
	 * @return	bool	True on success, false on failure
	 */
	public static function delete_announcement($id) {
		$db_table = Database :: get_main_table(TABLE_MAIN_SYSTEM_ANNOUNCEMENTS);
		$id = intval($id);
		$sql = "DELETE FROM ".$db_table." WHERE id =".$id;
		$res = Database::query($sql);
		if ($res === false) {
			Debug::log_s(mysql_error());
			return false;
		}
		return true;
	}
	/**
	 * Gets an announcement
	 * @param 	int		$id The identifier of the announcement that should be
	 * @return	object	Object of class StdClass or the required class, containing the query result row
	 */
	public static function get_announcement($id) {
		$db_table = Database :: get_main_table(TABLE_MAIN_SYSTEM_ANNOUNCEMENTS);
		$id = intval($id);
		$sql = "SELECT * FROM ".$db_table." WHERE id = ".$id;
		$announcement = Database::fetch_object(Database::query($sql));
		return $announcement;
	}
	/**
	 * Change the visibility of an announcement
	 * @param 	int $announcement_id
	 * @param 	int $user For who should the visibility be changed (possible values are VISIBLE_TEACHER, VISIBLE_STUDENT, VISIBLE_GUEST)
	 * @return 	bool	True on success, false on failure
	 */
	public static function set_visibility($announcement_id, $user, $visible) {
		$db_table 			= Database::get_main_table(TABLE_MAIN_SYSTEM_ANNOUNCEMENTS);
		$visible			= intval($visible);
		$announcement_id 	= intval($announcement_id);

		$field = ($user == VISIBLE_TEACHER ? 'visible_teacher' : ($user == VISIBLE_STUDENT ? 'visible_student' : 'visible_guest'));
		$sql = "UPDATE ".$db_table." SET ".$field." = '".$visible."' WHERE id='".$announcement_id."'";
		$res = Database::query($sql);
		if ($res === false) {
			Debug::log_s(mysql_error());
			return false;
		}
		return true;
	}

	/**
	 * Send a system announcement by e-mail to all teachers/students depending on parameters
	 * @param	string	Title
	 * @param	string	Content
	 * @param	int		Whether to send to all teachers (1) or not (0)
	 * @param	int		Whether to send to all students (1) or not (0)
	 * @param	string	Language (optional, considered for all languages if left empty)
     * @return  bool    True if the message was sent or there was no destination matching. False on database or e-mail sending error.
	 */
	public static function send_system_announcement_by_email($title, $content, $teacher, $student, $language=null) {
		global $_user;
		global $_setting;
		global $charset;
		$user_table = Database :: get_main_table(TABLE_MAIN_USER);
		
		if ($teacher <> 0 AND $student == 0) {
			$sql = "SELECT firstname, lastname, email, status FROM $user_table WHERE email<>'' AND status = '1' AND active = 1";
		}
		if ($teacher == 0 AND $student <> 0) {
			$sql = "SELECT firstname, lastname, email, status FROM $user_table WHERE email<>'' AND status = '5' AND active = 1 ";
		}
		if ($teacher<> 0 AND $student <> 0) {
			$sql = "SELECT firstname, lastname, email FROM $user_table WHERE email<>'' AND active = 1 ";
		}
		if (!empty($language)) { //special condition because language was already treated for SQL insert before
			$sql .= " AND language = '".Database::escape_string($language)."' ";
		}
		
		global $_configuration;
		$current_access_url_id = 1;
		if ($_configuration['multiple_access_urls']) {
			$current_access_url_id = api_get_current_access_url_id();
		}		
		$sql .= " AND access_url_id = '".$current_access_url_id."' ";
		
		if ((empty($teacher) or $teacher == '0') AND  (empty($student) or $student == '0')) {
			return true;
		}
		$result = Database::query($sql);
		if ($result === false) {
			return false;
		}
		while($row = Database::fetch_array($result,'ASSOC')) {
			$res =  @api_mail_html(api_get_person_name($row['firstname'], $row['lastname'], null, PERSON_NAME_EMAIL_ADDRESS), $row['email'], api_html_entity_decode(stripslashes($title), ENT_QUOTES, $charset), api_html_entity_decode(stripslashes(str_replace(array('\r\n', '\n', '\r'),'',$content)), ENT_QUOTES, $charset), api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname'), null, PERSON_NAME_EMAIL_ADDRESS), api_get_setting('emailAdministrator')) || $res;
		}
		return $res; //true if at least one e-mail was sent
	}
	
	
	/**
	* Displays announcements as an slideshow
	* @param int $visible VISIBLE_GUEST, VISIBLE_STUDENT or VISIBLE_TEACHER
	* @param int $id The identifier of the announcement to display
	*/
	public static function display_announcements_slider($visible, $id = -1) {
		$user_selected_language = Database::escape_string(api_get_interface_language());
		$db_table 				= Database :: get_main_table(TABLE_MAIN_SYSTEM_ANNOUNCEMENTS);
		
		$now  = api_get_utc_datetime();
		
		$sql = "SELECT * FROM ".$db_table." 
				WHERE ( lang = '$user_selected_language' OR lang IS NULL) AND ( '$now' >= date_start AND '$now' <= date_end) ";
		
		switch ($visible) {
			case VISIBLE_GUEST :
				$sql .= " AND visible_guest = 1 ";
				break;
			case VISIBLE_STUDENT :
				$sql .= " AND visible_student = 1 ";
				break;
			case VISIBLE_TEACHER :
				$sql .= " AND visible_teacher = 1 ";
				break;
		}
		$sql .= " ORDER BY date_start DESC LIMIT 0,7";
	
		$announcements = Database::query($sql);
		$html = '';
		if (Database::num_rows($announcements) > 0) {
			$query_string = ereg_replace('announcement=[1-9]+', '', $_SERVER['QUERY_STRING']);
			$query_string = ereg_replace('&$', '', $query_string);
			$url = api_get_self();
	
			$html .= '<div class="system_announcements">';
			$html .=  '<h3>'.get_lang('SystemAnnouncements').'</h3>';
			//echo '<div style="margin:10px;text-align:right;"><a href="news_list.php">'.get_lang('More').'</a></div>';
			
			$html .=  '<div id="container-slider">
					<ul id="slider">';
			while ($announcement = Database::fetch_object($announcements)) {				
				if ($id != $announcement->id) {
					$html .=  '<li><h1>'.$announcement->title.'</h1>'.$announcement->content.'</li>';
				} else {
					$html .=  '<li><h1>'.$announcement->title.'</h1>'.$announcement->content.'</li>';
				}
			}
			$html .=  '</ul></div></div>';			
		}
		return $html;
	}
	
}
