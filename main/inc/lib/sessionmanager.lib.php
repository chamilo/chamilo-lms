<?php //$id: $
/* For licensing terms, see /dokeos_license.txt */
/**
==============================================================================
*	This class provides methods for sessions management.
*	Include/require it in your code to use its features.
*
*	@package dokeos.library
==============================================================================
*/
require_once('display.lib.php');
class SessionManager {
	private function __construct() {

	}
    /**
     * Fetches a session from the database
     * @param   int     Session ID
     * @return  array   Session details (id, id_coach, name, nbr_courses, nbr_users, nbr_classes, date_start, date_end, nb_days_access_before_beginning,nb_days_access_after_end, session_admin_id)
     */
    public static function fetch($id) {
    	$t = Database::get_main_table(TABLE_MAIN_SESSION);
        if ($id != strval(intval($id))) { return array(); }
        $s = "SELECT * FROM $t WHERE id = $id";
        $r = Database::query($s,__FILE__,__LINE__);
        if (Database::num_rows($r) != 1) { return array(); }
        return Database::fetch_array($r,'ASSOC');
    }
	 /**
	  * Create a session
	  * @author Carlos Vargas <carlos.vargas@dokeos.com>,from existing code
	  * @param	string 		name
	  * @param 	integer		year_start
	  * @param 	integer		month_start
	  * @param 	integer		day_start
	  * @param 	integer		year_end
	  * @param 	integer		month_end
	  * @param 	integer		day_end
	  * @param 	integer		nb_days_acess_before
	  * @param 	integer		nb_days_acess_after
	  * @param 	integer		nolimit
	  * @param 	string		coach_username
	  * @return $id_session;
	  **/
	public static function create_session ($sname,$syear_start,$smonth_start,$sday_start,$syear_end,$smonth_end,$sday_end,$snb_days_acess_before,$snb_days_acess_after,$nolimit,$coach_username) {
		global $_user;
		$name= trim($sname);
		$year_start= intval($syear_start);
		$month_start=intval($smonth_start);
		$day_start=intval($sday_start);
		$year_end=intval($syear_end);
		$month_end=intval($smonth_end);
		$day_end=intval($sday_end);
		$nb_days_acess_before = intval($snb_days_acess_before);
		$nb_days_acess_after = intval($snb_days_acess_after);

		$tbl_user		= Database::get_main_table(TABLE_MAIN_USER);
		$tbl_session	= Database::get_main_table(TABLE_MAIN_SESSION);

		$sql = 'SELECT user_id FROM '.$tbl_user.' WHERE username="'.Database::escape_string($coach_username).'"';
		$rs = api_sql_query($sql, __FILE__, __LINE__);
		$id_coach = Database::result($rs,0,'user_id');

		if (empty($nolimit)) {
			$date_start="$year_start-".(($month_start < 10)?"0$month_start":$month_start)."-".(($day_start < 10)?"0$day_start":$day_start);
			$date_end="$year_end-".(($month_end < 10)?"0$month_end":$month_end)."-".(($day_end < 10)?"0$day_end":$day_end);
		} else {
			$date_start="000-00-00";
			$date_end="000-00-00";
		}
		if (empty($name)) {
			$msg=get_lang('SessionNameIsRequired');
			return $msg;
		} elseif (empty($coach_username))   {
			$msg=get_lang('CoachIsRequired');
			return $msg;
		} elseif (empty($nolimit) && (!$month_start || !$day_start || !$year_start || !checkdate($month_start,$day_start,$year_start))) {
			$msg=get_lang('InvalidStartDate');
			return $msg;
		} elseif (empty($nolimit) && (!$month_end || !$day_end || !$year_end || !checkdate($month_end,$day_end,$year_end))) {
			$msg=get_lang('InvalidEndDate');
			return $msg;
		} elseif(empty($nolimit) && $date_start >= $date_end) {
			$msg=get_lang('StartDateShouldBeBeforeEndDate');
			return $msg;
		} else {
			$rs = api_sql_query("SELECT 1 FROM $tbl_session WHERE name='".addslashes($name)."'");
			if(Database::num_rows($rs)) {
				$msg=get_lang('SessionNameAlreadyExists');
				return $msg;
			} else {
				api_sql_query("INSERT INTO $tbl_session(name,date_start,date_end,id_coach,session_admin_id, nb_days_access_before_beginning, nb_days_access_after_end) VALUES('".Database::escape_string($name)."','$date_start','$date_end','$id_coach',".intval($_user['user_id']).",".$nb_days_acess_before.", ".$nb_days_acess_after.")",__FILE__,__LINE__);
				$id_session=Database::get_last_insert_id();

				// add event to system log
				$time = time();
				$user_id = api_get_user_id();
				event_system(LOG_SESSION_CREATE, LOG_SESSION_ID, $id_session, $time, $user_id);

				return $id_session;
			}
		}
	}
	/**
	 * Edit a session
	 * @author Carlos Vargas <carlos.vargas@dokeos.com>,from existing code
	 * @param	integer		id
	 * @param	string 		name
	 * @param 	integer		year_start
	 * @param 	integer		month_start
	 * @param 	integer		day_start
	 * @param 	integer		year_end
	 * @param 	integer		month_end
	 * @param 	integer		day_end
	 * @param 	integer		nb_days_acess_before
	 * @param 	integer		nb_days_acess_after
	 * @param 	integer		nolimit
	 * @param 	integer		id_coach
	 * @return $id;
	 * The parameter id is a primary key
	**/
	public static function edit_session ($id,$name,$year_start,$month_start,$day_start,$year_end,$month_end,$day_end,$nb_days_acess_before,$nb_days_acess_after,$nolimit,$id_coach) {
		global $_user;
		$name=trim(stripslashes($name));
		$year_start=intval($year_start);
		$month_start=intval($month_start);
		$day_start=intval($day_start);
		$year_end=intval($year_end);
		$month_end=intval($month_end);
		$day_end=intval($day_end);
		$id_coach= intval($id_coach);
		$nb_days_acess_before= intval($nb_days_acess_before);
		$nb_days_acess_after = intval($nb_days_acess_after);

		$tbl_user		= Database::get_main_table(TABLE_MAIN_USER);
		$tbl_session	= Database::get_main_table(TABLE_MAIN_SESSION);
		$tbl_session_rel_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);


		if (empty($nolimit)) {
			$date_start="$year_start-".(($month_start < 10)?"0$month_start":$month_start)."-".(($day_start < 10)?"0$day_start":$day_start);
			$date_end="$year_end-".(($month_end < 10)?"0$month_end":$month_end)."-".(($day_end < 10)?"0$day_end":$day_end);
		} else {
			$date_start="000-00-00";
			$date_end="000-00-00";
		}
		if (empty($name)) {
			$msg=get_lang('SessionNameIsRequired');
			return $msg;
		} elseif (empty($id_coach))   {
			$msg=get_lang('CoachIsRequired');
			return $msg;
		} elseif (empty($nolimit) && (!$month_start || !$day_start || !$year_start || !checkdate($month_start,$day_start,$year_start))) {
			$msg=get_lang('InvalidStartDate');
			return $msg;
		} elseif (empty($nolimit) && (!$month_end || !$day_end || !$year_end || !checkdate($month_end,$day_end,$year_end))) {
			$msg=get_lang('InvalidEndDate');
			return $msg;
		} elseif (empty($nolimit) && $date_start >= $date_end) {
			$msg=get_lang('StartDateShouldBeBeforeEndDate');
			return $msg;
		} else {
			$rs = api_sql_query("SELECT id FROM $tbl_session WHERE name='".Database::escape_string($name)."'");
			$exists = false;
			while ($row = Database::fetch_array($rs)) {
				if($row['id']!=$id)
					$exists = true;
			}
			if ($exists) {
				$msg=get_lang('SessionNameAlreadyExists');
				return $msg;
			} else {
				$sql="UPDATE $tbl_session " .
					"SET name='".Database::escape_string($name)."',
						date_start='".$date_start."',
						date_end='".$date_end."',
						id_coach='".$id_coach."',
						nb_days_access_before_beginning = ".$nb_days_acess_before.",
						nb_days_access_after_end = ".$nb_days_acess_after."
					  WHERE id='$id'";
				api_sql_query($sql,__FILE__,__LINE__);
				/*$sqlu = "UPDATE $tbl_session_rel_course " .
						  " SET id_coach='$id_coach'" .
						  " WHERE id_session='$id'";
				api_sql_query($sqlu,__FILE__,__LINE__);*/
				return $id;
			}
		}
	}
	/**
	 * Delete session
	 * @author Carlos Vargas <carlos.vargas@dokeos.com>, from existing code
	 * @param	array	id_checked
     * @return	void	Nothing, or false on error
	 * The parameters is a array to delete sessions
	 **/
	public static function delete_session ($id_checked) {
		$tbl_session=						Database::get_main_table(TABLE_MAIN_SESSION);
		$tbl_session_rel_course=			Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
		$tbl_session_rel_course_rel_user=	Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
		$tbl_session_rel_user=				Database::get_main_table(TABLE_MAIN_SESSION_USER);
		$tbl_user = 						Database::get_main_table(TABLE_MAIN_USER);
		global $_user;
		if(is_array($id_checked)) {
			$id_checked=Database::escape_string(implode(',',$id_checked));
		} else {
			$id_checked=intval($id_checked);
		}

		if (!api_is_platform_admin()) {
			$sql = 'SELECT session_admin_id FROM '.Database :: get_main_table(TABLE_MAIN_SESSION).' WHERE id='.$id_checked;
			$rs = api_sql_query($sql,__FILE__,__LINE__);
			if (Database::result($rs,0,0)!=$_user['user_id']) {
				api_not_allowed(true);
			}
		}
		api_sql_query("DELETE FROM $tbl_session WHERE id IN($id_checked)",__FILE__,__LINE__);
		api_sql_query("DELETE FROM $tbl_session_rel_course WHERE id_session IN($id_checked)",__FILE__,__LINE__);
		api_sql_query("DELETE FROM $tbl_session_rel_course_rel_user WHERE id_session IN($id_checked)",__FILE__,__LINE__);
		api_sql_query("DELETE FROM $tbl_session_rel_user WHERE id_session IN($id_checked)",__FILE__,__LINE__);

		// delete extra session fields
		$t_sf 		= Database::get_main_table(TABLE_MAIN_SESSION_FIELD);
		$t_sfv 		= Database::get_main_table(TABLE_MAIN_SESSION_FIELD_VALUES);

		$sql = "SELECT distinct field_id FROM $t_sfv  WHERE session_id = '$id_checked'";
		$res_field_ids = @api_sql_query($sql,__FILE__,__LINE__);

		while($row_field_id = Database::fetch_row($res_field_ids)){
			$field_ids[] = $row_field_id[0];
		}

		//delete from table_session_field_value from a given session id

		$sql_session_field_value = "DELETE FROM $t_sfv WHERE session_id = '$id_checked'";
		@api_sql_query($sql_session_field_value,__FILE__,__LINE__);

		$sql = "SELECT distinct field_id FROM $t_sfv";
		$res_field_all_ids = @api_sql_query($sql,__FILE__,__LINE__);

		while($row_field_all_id = Database::fetch_row($res_field_all_ids)){
			$field_all_ids[] = $row_field_all_id[0];
		}

		if (count($field_ids) > 0 && count($field_all_ids) > 0) {
			foreach($field_ids as $field_id) {
				// check if field id is used into table field value
				if (in_array($field_id,$field_all_ids)) {
					continue;
				} else {
					$sql_session_field = "DELETE FROM $t_sf WHERE id = '$field_id'";
					api_sql_query($sql_session_field,__FILE__,__LINE__);
				}
			}
		}

		// add event to system log
		$time = time();
		$user_id = api_get_user_id();
		event_system(LOG_SESSION_DELETE, LOG_SESSION_ID, $id_checked, $time, $user_id);

	}


	 /**
	  * Subscribes users to the given session and optionally (default) unsubscribes previous users
	  * @author Carlos Vargas <carlos.vargas@dokeos.com>,from existing code
	  * @param	integer		Session ID
	  * @param	array		List of user IDs
	  * @param	bool		Whether to unsubscribe existing users (true, default) or not (false)
	  * @return	void		Nothing, or false on error
	  **/
	public static function suscribe_users_to_session ($id_session,$user_list,$empty_users=true) {

	  	if ($id_session!= strval(intval($id_session))) return false;
	   	foreach($user_list as $intUser){
	   		if ($intUser!= strval(intval($intUser))) return false;
	   	}
	   	$tbl_session_rel_course				= Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
		$tbl_session_rel_course_rel_user	= Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
	   	$tbl_session_rel_user 				= Database::get_main_table(TABLE_MAIN_SESSION_USER);
	   	$tbl_session						= Database::get_main_table(TABLE_MAIN_SESSION);

	   	$sql = "SELECT id_user FROM $tbl_session_rel_user WHERE id_session='$id_session'";
		$result = api_sql_query($sql,__FILE__,__LINE__);
		$existingUsers = array();
		while($row = Database::fetch_array($result)){
			$existingUsers[] = $row['id_user'];
		}
		$sql = "SELECT course_code FROM $tbl_session_rel_course WHERE id_session='$id_session'";
		$result=api_sql_query($sql,__FILE__,__LINE__);
		$course_list=array();

		while($row=Database::fetch_array($result)) {
			$course_list[]=$row['course_code'];
		}

		foreach ($course_list as $enreg_course) {
			// for each course in the session
			$nbr_users=0;
	        $enreg_course = Database::escape_string($enreg_course);
				// delete existing users
			if ($empty_users!==false) {
				foreach ($existingUsers as $existing_user) {
					if(!in_array($existing_user, $user_list)) {
						$sql = "DELETE FROM $tbl_session_rel_course_rel_user WHERE id_session='$id_session' AND course_code='$enreg_course' AND id_user='$existing_user'";
						api_sql_query($sql,__FILE__,__LINE__);

						if(Database::affected_rows()) {
							$nbr_users--;
						}
					}
				}
			}
			// insert new users into session_rel_course_rel_user and ignore if they already exist
			foreach ($user_list as $enreg_user) {
				if(!in_array($enreg_user, $existingUsers)) {
	                $enreg_user = Database::escape_string($enreg_user);
					$insert_sql = "INSERT IGNORE INTO $tbl_session_rel_course_rel_user(id_session,course_code,id_user) VALUES('$id_session','$enreg_course','$enreg_user')";
					api_sql_query($insert_sql,__FILE__,__LINE__);
					if(Database::affected_rows()) {
						$nbr_users++;
					}
				}
			}
			// count users in this session-course relation
			$sql = "SELECT COUNT(id_user) as nbUsers FROM $tbl_session_rel_course_rel_user WHERE id_session='$id_session' AND course_code='$enreg_course'";
			$rs = api_sql_query($sql, __FILE__, __LINE__);
			list($nbr_users) = Database::fetch_array($rs);
			// update the session-course relation to add the users total
			$update_sql = "UPDATE $tbl_session_rel_course SET nbr_users=$nbr_users WHERE id_session='$id_session' AND course_code='$enreg_course'";
			api_sql_query($update_sql,__FILE__,__LINE__);
		}
		// delete users from the session
		if ($empty_users===true){
			api_sql_query("DELETE FROM $tbl_session_rel_user WHERE id_session = $id_session",__FILE__,__LINE__);
		}
			// insert missing users into session
		$nbr_users = 0;
		foreach ($user_list as $enreg_user) {
	        $enreg_user = Database::escape_string($enreg_user);
			$nbr_users++;
			$insert_sql = "INSERT IGNORE INTO $tbl_session_rel_user(id_session, id_user) VALUES('$id_session','$enreg_user')";
			api_sql_query($insert_sql,__FILE__,__LINE__);
		}
		// update number of users in the session
		$nbr_users = count($user_list);
		$update_sql = "UPDATE $tbl_session SET nbr_users= $nbr_users WHERE id='$id_session' ";
		api_sql_query($update_sql,__FILE__,__LINE__);
	}
	 /** Subscribes courses to the given session and optionally (default) unsubscribes previous users
	 * @author Carlos Vargas <carlos.vargas@dokeos.com>,from existing code
     * @param	int		Session ID
     * @param	array	List of courses IDs
     * @param	bool	Whether to unsubscribe existing users (true, default) or not (false)
     * @return	void	Nothing, or false on error
     **/
     public static function add_courses_to_session ($id_session, $course_list, $empty_courses=true) {
     	// security checks
     	if ($id_session!= strval(intval($id_session))) { return false; }
	   	foreach($course_list as $intCourse){
	   		if ($intCourse!= strval(intval($intCourse))) { return false; }
	   	}
	   	// initialisation
		$tbl_session_rel_course_rel_user	= Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
		$tbl_session						= Database::get_main_table(TABLE_MAIN_SESSION);
		$tbl_session_rel_user				= Database::get_main_table(TABLE_MAIN_SESSION_USER);
		$tbl_session_rel_course				= Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
		$tbl_course							= Database::get_main_table(TABLE_MAIN_COURSE);
     	// get general coach ID
		$id_coach = api_sql_query("SELECT id_coach FROM $tbl_session WHERE id=$id_session");
		$id_coach = Database::fetch_array($id_coach);
		$id_coach = $id_coach[0];
		// get list of courses subscribed to this session
		$rs = api_sql_query("SELECT course_code FROM $tbl_session_rel_course WHERE id_session=$id_session");
		$existingCourses = api_store_result($rs);
		$nbr_courses=count($existingCourses);
		// get list of users subscribed to this session
		$sql="SELECT id_user
			FROM $tbl_session_rel_user
			WHERE id_session = $id_session";
		$result=api_sql_query($sql,__FILE__,__LINE__);
		$user_list=api_store_result($result);

		// remove existing courses from the session
		if ($empty_courses===true) {
			foreach ($existingCourses as $existingCourse) {
				if (!in_array($existingCourse['course_code'], $course_list)){
					api_sql_query("DELETE FROM $tbl_session_rel_course WHERE course_code='".$existingCourse['course_code']."' AND id_session=$id_session");
					api_sql_query("DELETE FROM $tbl_session_rel_course_rel_user WHERE course_code='".$existingCourse['course_code']."' AND id_session=$id_session");

				}
			}
			$nbr_courses=0;
		}

		// Pass through the courses list we want to add to the session
		foreach ($course_list as $enreg_course) {
			$enreg_course = Database::escape_string($enreg_course);
			$exists = false;
			// check if the course we want to add is already subscribed
			foreach ($existingCourses as $existingCourse) {
				if ($enreg_course == $existingCourse['course_code']) {
					$exists=true;
				}
			}
			if (!$exists) {
				//if the course isn't subscribed yet
				$sql_insert_rel_course= "INSERT INTO $tbl_session_rel_course (id_session,course_code, id_coach) VALUES ('$id_session','$enreg_course','$id_coach')";
				api_sql_query($sql_insert_rel_course ,__FILE__,__LINE__);
				//We add the current course in the existing courses array, to avoid adding another time the current course
				$existingCourses[]=array('course_code'=>$enreg_course);
				$nbr_courses++;

				// subscribe all the users from the session to this course inside the session
				$nbr_users=0;
				foreach ($user_list as $enreg_user) {
					$enreg_user_id = Database::escape_string($enreg_user['id_user']);
					$sql_insert = "INSERT IGNORE INTO $tbl_session_rel_course_rel_user (id_session,course_code,id_user) VALUES ('$id_session','$enreg_course','$enreg_user_id')";
					api_sql_query($sql_insert,__FILE__,__LINE__);
					if (Database::affected_rows()) {
						$nbr_users++;
					}
				}
				api_sql_query("UPDATE $tbl_session_rel_course SET nbr_users=$nbr_users WHERE id_session='$id_session' AND course_code='$enreg_course'",__FILE__,__LINE__);
			}
		}
		api_sql_query("UPDATE $tbl_session SET nbr_courses=$nbr_courses WHERE id='$id_session'",__FILE__,__LINE__);
     }

  /**
  * Creates a new extra field for a given session
  * @param	string	Field's internal variable name
  * @param	int		Field's type
  * @param	string	Field's language var name
  * @return int     new extra field id
  */
	public static function create_session_extra_field ($fieldvarname, $fieldtype, $fieldtitle) {
		// database table definition
		$t_sf 			= Database::get_main_table(TABLE_MAIN_SESSION_FIELD);
		$fieldvarname 	= Database::escape_string($fieldvarname);
		$fieldtitle 	= Database::escape_string($fieldtitle);
		$fieldtype = (int)$fieldtype;
		$time = time();
		$sql_field = "SELECT id FROM $t_sf WHERE field_variable = '$fieldvarname'";
		$res_field = api_sql_query($sql_field,__FILE__,__LINE__);

		$r_field = Database::fetch_row($res_field);

		if (Database::num_rows($res_field)>0) {
			$field_id = $r_field[0];
		} else {
			// save new fieldlabel into course_field table
			$sql = "SELECT MAX(field_order) FROM $t_sf";
			$res = api_sql_query($sql,__FILE__,__LINE__);

			$order = 0;
			if (Database::num_rows($res)>0) {
				$row = Database::fetch_row($res);
				$order = $row[0]+1;
			}

			$sql = "INSERT INTO $t_sf
						                SET field_type = '$fieldtype',
						                field_variable = '$fieldvarname',
						                field_display_text = '$fieldtitle',
						                field_order = '$order',
						                tms = FROM_UNIXTIME($time)";
			$result = api_sql_query($sql,__FILE__,__LINE__);

			$field_id=Database::get_last_insert_id();
		}
		return $field_id;
	}

/**
 * Update an extra field value for a given session
 * @param	integer	Course ID
 * @param	string	Field variable name
 * @param	string	Field value
 * @return	boolean	true if field updated, false otherwise
 */
	public static function update_session_extra_field_value ($session_id,$fname,$fvalue='') {

		$t_sf 			= Database::get_main_table(TABLE_MAIN_SESSION_FIELD);
		$t_sfv 			= Database::get_main_table(TABLE_MAIN_SESSION_FIELD_VALUES);
		$fname = Database::escape_string($fname);
		$session_id = (int)$session_id;
		$fvalues = '';
		if(is_array($fvalue))
		{
			foreach($fvalue as $val)
			{
				$fvalues .= Database::escape_string($val).';';
			}
			if(!empty($fvalues))
			{
				$fvalues = substr($fvalues,0,-1);
			}
		}
		else
		{
			$fvalues = Database::escape_string($fvalue);
		}

		$sqlsf = "SELECT * FROM $t_sf WHERE field_variable='$fname'";
		$ressf = api_sql_query($sqlsf,__FILE__,__LINE__);
		if(Database::num_rows($ressf)==1)
		{ //ok, the field exists
			//	Check if enumerated field, if the option is available
			$rowsf = Database::fetch_array($ressf);

			$tms = time();
			$sqlsfv = "SELECT * FROM $t_sfv WHERE session_id = '$session_id' AND field_id = '".$rowsf['id']."' ORDER BY id";
			$ressfv = api_sql_query($sqlsfv,__FILE__,__LINE__);
			$n = Database::num_rows($ressfv);
			if ($n>1) {
				//problem, we already have to values for this field and user combination - keep last one
				while($rowsfv = Database::fetch_array($ressfv))
				{
					if($n > 1)
					{
						$sqld = "DELETE FROM $t_sfv WHERE id = ".$rowsfv['id'];
						$resd = api_sql_query($sqld,__FILE__,__LINE__);
						$n--;
					}
					$rowsfv = Database::fetch_array($ressfv);
					if($rowsfv['field_value'] != $fvalues)
					{
						$sqlu = "UPDATE $t_sfv SET field_value = '$fvalues', tms = FROM_UNIXTIME($tms) WHERE id = ".$rowsfv['id'];
						$resu = api_sql_query($sqlu,__FILE__,__LINE__);
						return($resu?true:false);
					}
					return true;
				}
			} else if ($n==1) {
				//we need to update the current record
				$rowsfv = Database::fetch_array($ressfv);
				if($rowsfv['field_value'] != $fvalues)
				{
					$sqlu = "UPDATE $t_sfv SET field_value = '$fvalues', tms = FROM_UNIXTIME($tms) WHERE id = ".$rowsfv['id'];
					//error_log('UM::update_extra_field_value: '.$sqlu);
					$resu = api_sql_query($sqlu,__FILE__,__LINE__);
					return($resu?true:false);
				}
				return true;
			} else {
				$sqli = "INSERT INTO $t_sfv (session_id,field_id,field_value,tms) " .
					"VALUES ('$session_id',".$rowsf['id'].",'$fvalues',FROM_UNIXTIME($tms))";
				//error_log('UM::update_extra_field_value: '.$sqli);
				$resi = api_sql_query($sqli,__FILE__,__LINE__);
				return($resi?true:false);
			}
		} else {
			return false; //field not found
		}
	}

	/**
	* Checks the relationship between a session and a course.
	* @param int $session_id
	* @param int $course_id
	* @return bool				Returns TRUE if the session and the course are related, FALSE otherwise.
	* */
	public static function relation_session_course_exist ($session_id, $course_id) {
		$tbl_session_course	= Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
		$return_value = false;
		$sql= "SELECT course_code FROM $tbl_session_course WHERE id_session = ".Database::escape_string($session_id)." AND course_code = '".Database::escape_string($course_id)."'";
		$result = api_sql_query($sql,  __FILE__, __LINE__);
		$num = Database::num_rows($result);
		if ($num>0) {
			$return_value = true;
		}
		return $return_value;
	}

	/**
	* Get the session information by name
	* @param string session name
	* @return mixed false if the session does not exist, array if the session exist
	* */
	public static function get_session_by_name ($session_name) {
		$tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
		$sql = 'SELECT id, id_coach, date_start, date_end FROM '.$tbl_session.' WHERE name="'.Database::escape_string($session_name).'"';
		$result = api_sql_query($sql,  __FILE__, __LINE__);
		$num = Database::num_rows($result);
		if ($num>0){
			return Database::fetch_array($result);
		} else {
			return false;
		}
	}
}