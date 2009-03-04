<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2009 Dokeos SPRL
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) various contributors
	Copyright (c) Bart Mollet, Hogeschool Gent

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium, info@dokeos.com
==============================================================================
*/
/**
==============================================================================
*	This library provides functions for user management.
*	Include/require it in your code to use its functionality.
*
*	@package dokeos.library
==============================================================================
*/
require_once('display.lib.php');
class SessionManager{
	
	 /** Create a session 
	  * @author Carlos Vargas <carlos.vargas@dokeos.com>,
	  * @param	array	name, year_start,month_start, day_start,year_end,month_end,day_end,nb_days_acess_before,nb_days_acess_after
	  **/
	function AddSession($name,$year_start,$month_start,$day_start,$year_end,$month_end,$day_end,$nb_days_acess_before,$nb_days_acess_after,$nolimit,$coach_username) {
		$tbl_user		= Database::get_main_table(TABLE_MAIN_USER);
		$tbl_session	= Database::get_main_table(TABLE_MAIN_SESSION);
		global $_user;	
		
		$sql = 'SELECT user_id FROM '.$tbl_user.' WHERE username="'.Database::escape_string($coach_username).'"';
		$rs = api_sql_query($sql, __FILE__, __LINE__);
		$id_coach = Database::result($rs,0,'user_id');
	
		if (empty($nolimit)){
			$date_start="$year_start-".(($month_start < 10)?"0$month_start":$month_start)."-".(($day_start < 10)?"0$day_start":$day_start);
			$date_end="$year_end-".(($month_end < 10)?"0$month_end":$month_end)."-".(($day_end < 10)?"0$day_end":$day_end);
		} else {
			$date_start="000-00-00";
			$date_end="000-00-00";
		}
		if(empty($name)) {
			Display::display_normal_message(get_lang('SessionNameIsRequired'));
		} elseif (empty($coach_username))   {
			Display::display_normal_message(get_lang('CoachIsRequired'));
		} elseif (empty($nolimit) && (!$month_start || !$day_start || !$year_start || !checkdate($month_start,$day_start,$year_start))) {
			Display::display_normal_message(get_lang('InvalidStartDate'));
		} elseif (empty($nolimit) && (!$month_end || !$day_end || !$year_end || !checkdate($month_end,$day_end,$year_end))) {
			Display::display_normal_message(get_lang('InvalidEndDate'));
		} elseif(empty($nolimit) && $date_start >= $date_end) {
			Display::display_normal_message(get_lang('StartDateShouldBeBeforeEndDate'));
		} else {
			$rs = api_sql_query("SELECT 1 FROM $tbl_session WHERE name='".addslashes($name)."'");
			if(Database::num_rows($rs)) {
				Display::display_normal_message(get_lang('SessionNameSoonExists'));
			} else {
				api_sql_query("INSERT INTO $tbl_session(name,date_start,date_end,id_coach,session_admin_id, nb_days_access_before_beginning, nb_days_access_after_end) VALUES('".addslashes($name)."','$date_start','$date_end','$id_coach',".intval($_user['user_id']).",".$nb_days_acess_before.", ".$nb_days_acess_after.")",__FILE__,__LINE__);
				$id_session=Database::get_last_insert_id();	
				return $id_session; 
			}
		}
	}	
	/** Edit a session 
	 * @author Carlos Vargas <carlos.vargas@dokeos.com>,
	 * @param	array	name, year_start,month_start, day_start,year_end,month_end,day_end,nb_days_acess_before,nb_days_acess_after,id
	 * The parameter id is a primary key
	**/
	function EditSession($name,$year_start,$month_start,$day_start,$year_end,$month_end,$day_end,$nb_days_acess_before,$nb_days_acess_after,$nolimit,$id_coach,$id) {
			
		$tbl_user		= Database::get_main_table(TABLE_MAIN_USER);
		$tbl_session	= Database::get_main_table(TABLE_MAIN_SESSION);
		$tbl_session_rel_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
		global $_user;
			
		if (empty($nolimit)) {
			$date_start="$year_start-".(($month_start < 10)?"0$month_start":$month_start)."-".(($day_start < 10)?"0$day_start":$day_start);
			$date_end="$year_end-".(($month_end < 10)?"0$month_end":$month_end)."-".(($day_end < 10)?"0$day_end":$day_end);
		} else {
			$date_start="000-00-00";
			$date_end="000-00-00";
		}
		if(empty($name)){
			Display::display_normal_message(get_lang('SessionNameIsRequired'));
		} elseif(!empty($nolimit) && (!$month_start || !$day_start || !$year_start || !checkdate($month_start,$day_start,$year_start))) {
			Display::display_normal_message(get_lang('InvalidStartDate'));
		} elseif(!empty($nolimit) && (!$month_end || !$day_end || !$year_end || !checkdate($month_end,$day_end,$year_end))) {
			Display::display_normal_message(get_lang('InvalidEndDate'));
		} elseif(!empty($nolimit) && $date_start >= $date_end) {
			Display::display_normal_message(get_lang('StartDateShouldBeBeforeEndDate'));
		} else {		
			$rs = api_sql_query("SELECT id FROM $tbl_session WHERE name='".addslashes($name)."'");
			$exists = false;
			while($row = mysql_fetch_array($rs)) {
				if($row['id']!=$id)
					$exists = true;
			}
			if ($exists) {
				Display::display_normal_message(get_lang('SessionNameSoonExists'));
			} else {
				$sql="UPDATE $tbl_session " .
					"SET name='".addslashes($name)."',
						date_start='".$date_start."',
						date_end='".$date_end."',
						id_coach='".$id_coach."',
						nb_days_access_before_beginning = ".$nb_days_acess_before.",
						nb_days_access_after_end = ".$nb_days_acess_after." 
					  WHERE id='$id'";
				api_sql_query($sql,__FILE__,__LINE__);
				$sqlu = "UPDATE $tbl_session_rel_course " .
						  " SET id_coach='$id_coach'" .
						  " WHERE id_session='$id'";
				api_sql_query($sqlu,__FILE__,__LINE__);
				return $id;
			}
		}
	}
	  /** Delete session 
	  * @author Carlos Vargas <carlos.vargas@dokeos.com>,
	  * @param	array	idChecked
	  * The parameters is a array to delete sessions 
	  **/
	
	function DeleteSession($idChecked) {
		$tbl_session=Database::get_main_table(TABLE_MAIN_SESSION);
		$tbl_session_rel_course=Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
		$tbl_session_rel_course_rel_user=Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
		$tbl_session_rel_user=Database::get_main_table(TABLE_MAIN_SESSION_USER);
		$tbl_user = Database::get_main_table(TABLE_MAIN_USER);
		global $_user;
		if(is_array($idChecked)) {
			$idChecked=Database::escape_string(implode(',',$idChecked));
		} else {
			$idChecked=intval($idChecked);
		}
		
		if (!api_is_platform_admin()) {
			$sql = 'SELECT session_admin_id FROM '.Database :: get_main_table(TABLE_MAIN_SESSION).' WHERE id='.$idChecked;
			$rs = api_sql_query($sql,__FILE__,__LINE__);
			if (Database::result($rs,0,0)!=$_user['user_id']) {
				api_not_allowed(true);
			}
		}
	
		api_sql_query("DELETE FROM $tbl_session WHERE id IN($idChecked)",__FILE__,__LINE__);
		api_sql_query("DELETE FROM $tbl_session_rel_course WHERE id_session IN($idChecked)",__FILE__,__LINE__);
		api_sql_query("DELETE FROM $tbl_session_rel_course_rel_user WHERE id_session IN($idChecked)",__FILE__,__LINE__);	
		api_sql_query("DELETE FROM $tbl_session_rel_user WHERE id_session IN($idChecked)",__FILE__,__LINE__);
	}	
	
	 /**Subscribes users to the given session and optionally (default) unsubscribes previous users
	 * @author Carlos Vargas <carlos.vargas@dokeos.com>,
     * @param	int		Session ID
     * @param	array	List of user IDs
     * @param	bool	Whether to unsubscribe existing users (true, default) or not (false)
     * @return	void	Nothing, or false on error  
     */
	function suscribe_users_to_session($id_session,$UserList,$empty_users=true){
	 	
	  	if ($id_session!= strval(intval($id_session))) return false;
	   	foreach($UserList as $intUser){
	   		if ($intUser!= strval(intval($intUser))) return false;
	   	}
	   	$tbl_session_rel_course				= Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
		$tbl_session_rel_course_rel_user	= Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
	   	$tbl_session_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_USER);
	   	$tbl_session						= Database::get_main_table(TABLE_MAIN_SESSION);
	   	
	   	$sql = "SELECT id_user FROM $tbl_session_rel_user WHERE id_session='$id_session'";
		$result = api_sql_query($sql,__FILE__,__LINE__);
		$existingUsers = array();
		while($row = Database::fetch_array($result)){
			$existingUsers[] = $row['id_user'];
		}
		$sql = "SELECT course_code FROM $tbl_session_rel_course WHERE id_session='$id_session'";
		$result=api_sql_query($sql,__FILE__,__LINE__);
		$CourseList=array();
	
		while($row=Database::fetch_array($result)) {
			$CourseList[]=$row['course_code'];
		}
	
		foreach ($CourseList as $enreg_course) {
			// for each course in the session
			$nbr_users=0;
	        $enreg_course = Database::escape_string($enreg_course);
				// delete existing users
			if ($empty_users!==false) {
				foreach ($existingUsers as $existing_user) {
					if(!in_array($existing_user, $UserList)) {
						$sql = "DELETE FROM $tbl_session_rel_course_rel_user WHERE id_session='$id_session' AND course_code='$enreg_course' AND id_user='$existing_user'";
						api_sql_query($sql,__FILE__,__LINE__);
	
						if(Database::affected_rows()) {
							$nbr_users--;
						}
					}
				}
			}
			// insert new users into session_rel_course_rel_user and ignore if they already exist
			foreach ($UserList as $enreg_user) {
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
		if ($empty_users!==false){
			api_sql_query("DELETE FROM $tbl_session_rel_user WHERE id_session = $id_session",__FILE__,__LINE__);
		}
			// insert missing users into session
		$nbr_users = 0;
		foreach ($UserList as $enreg_user) {
	        $enreg_user = Database::escape_string($enreg_user);
			$nbr_users++;
			$insert_sql = "INSERT IGNORE INTO $tbl_session_rel_user(id_session, id_user) VALUES('$id_session','$enreg_user')";
			api_sql_query($insert_sql,__FILE__,__LINE__);
		}
		// update number of users in the session
		$nbr_users = count($UserList);
		$update_sql = "UPDATE $tbl_session SET nbr_users= $nbr_users WHERE id='$id_session' ";
		api_sql_query($update_sql,__FILE__,__LINE__);
	}
	 /**Subscribes courses to the given session and optionally (default) unsubscribes previous users
	 * @author Carlos Vargas <carlos.vargas@dokeos.com>,
     * @param	int		Session ID
     * @param	array	List of courses IDs
     * @param	bool	Whether to unsubscribe existing users (true, default) or not (false)
     * @return	void	Nothing, or false on error  
     */
     function add_courses_to_session($id_session, $CourseList, $empty_courses=true){
     	
     	if ($id_session!= strval(intval($id_session))) return false;
	   	foreach($CourseList as $intCourse){
	   		if ($intCourse!= strval(intval($intCourse))) return false;
	   	}
		$tbl_session_rel_course_rel_user	= Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
		$tbl_session						= Database::get_main_table(TABLE_MAIN_SESSION);
		$tbl_session_rel_user				= Database::get_main_table(TABLE_MAIN_SESSION_USER);
		$tbl_session_rel_course				= Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
		$tbl_course							= Database::get_main_table(TABLE_MAIN_COURSE);
     	
		$id_coach = api_sql_query("SELECT id_coach FROM $tbl_session WHERE id=$id_session");
		$id_coach = Database::fetch_array($id_coach);
		$id_coach = $id_coach[0];
	
		$rs = api_sql_query("SELECT course_code FROM $tbl_session_rel_course WHERE id_session=$id_session");
		$existingCourses = api_store_result($rs);
	
		$sql="SELECT id_user
			FROM $tbl_session_rel_user
			WHERE id_session = $id_session";
		$result=api_sql_query($sql,__FILE__,__LINE__);
		$UserList=api_store_result($result);
	
	
		foreach($CourseList as $enreg_course) {
			$enreg_course = Database::escape_string($enreg_course);
			$exists = false;
			foreach($existingCourses as $existingCourse) {
				if($enreg_course == $existingCourse['course_code']) {
					$exists=true;
				}
			}
			if(!$exists) {			
				$sql_insert_rel_course= "INSERT INTO $tbl_session_rel_course(id_session,course_code, id_coach) VALUES('$id_session','$enreg_course','$id_coach')";
				api_sql_query($sql_insert_rel_course ,__FILE__,__LINE__);			
				//We add in the existing courses table the current course, to not try to add another time the current course
				$existingCourses[]=array('course_code'=>$enreg_course);
				$nbr_users=0;
				foreach ($UserList as $enreg_user) {				
					$enreg_user = Database::escape_string($enreg_user['id_user']);
					$sql_insert = "INSERT IGNORE INTO $tbl_session_rel_course_rel_user(id_session,course_code,id_user) VALUES('$id_session','$enreg_course','$enreg_user')";
					api_sql_query($sql_insert,__FILE__,__LINE__);
					if(Database::affected_rows()) {
						$nbr_users++;
					}
				}
				api_sql_query("UPDATE $tbl_session_rel_course SET nbr_users=$nbr_users WHERE id_session='$id_session' AND course_code='$enreg_course'",__FILE__,__LINE__);
			}
	
		}
		if ($empty_courses!==false) {
			foreach($existingCourses as $existingCourse) {
				if(!in_array($existingCourse['course_code'], $CourseList)){
					api_sql_query("DELETE FROM $tbl_session_rel_course WHERE course_code='".$existingCourse['course_code']."' AND id_session=$id_session");
					api_sql_query("DELETE FROM $tbl_session_rel_course_rel_user WHERE course_code='".$existingCourse['course_code']."' AND id_session=$id_session");
		
				}
			}
		}
		$nbr_courses=count($CourseList);
		api_sql_query("UPDATE $tbl_session SET nbr_courses=$nbr_courses WHERE id='$id_session'",__FILE__,__LINE__);
     }
}
?>
