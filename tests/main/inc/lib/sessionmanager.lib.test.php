<?php

require_once(api_get_path(LIBRARY_PATH).'sessionmanager.lib.php');

Mock::generate('Database');
Mock::generate('api_failure');


class TestSessionManager extends UnitTestCase {

	function testadd_courses_to_session() {
		$id_session='';
		$course_list='';
		ob_start();
		$res=SessionManager::add_courses_to_session();
		if(!empty($res)) {
			$this->assertTrue(is_null($res));
		} else {
			$this->assertTrue(is_bool($res));
		}
		ob_end_clean();
		//var_dump($res);
	}

	function testcreate_session() {
		$idsesion = new MockDatabase();
		global $_user;
		$sname='';
		$syear_start='';
		$smonth_start='';
		$sday_start='';
		$syear_end='';
		$smonth_end='';
		$sday_end='';
		$snb_days_acess_before='';
		$snb_days_acess_after='';
		$nolimit='';
		$coach_username='';
		$id_session=Database::get_last_insert_id();
		ob_start();
		$res=SessionManager::create_session($sname,$syear_start,$smonth_start,$sday_start,$syear_end,$smonth_end,$sday_end,$snb_days_acess_before,$snb_days_acess_after,$nolimit,$coach_username);
		$idsesion->expectOnce(Database::get_last_insert_id());
		$this->assertTrue(is_object($idsesion));
		$this->assertTrue(is_numeric($id_session));
		$this->assertTrue(is_string($res));
		ob_end_clean();
		//var_dump($idsesion);
	}

	function testcreate_session_extra_field() {
		$fieldvarname='';
		$fieldtype='';
		$fieldtitle='';
		ob_start();
		$res=SessionManager::create_session_extra_field($fieldvarname, $fieldtype, $fieldtitle);
		$this->assertTrue(is_numeric($res));
		ob_end_clean();
		//var_dump($res);
	}
	/*
	//Esta prueba muestra pantallaso, lo dejo comentado
	function testdelete_session() {
		$idsesion = new MockDatabase();
		$idse = new Mockapi_failure();
		global $_user;
		$id_checked='';
		$this->sessionmanager = new SessionManager();
		$res=SessionManager::delete_session($id_checked);
		$idsesion->expectOnce(Database :: get_main_table(TABLE_MAIN_SESSION));
		$this->assertTrue(is_object($idsesion));
		$this->assertTrue(is_null($res));
		var_dump($res);
	}
	*/

	function testedit_session() {
		$idsesion = new MockDatabase();
		global $_user;
		$id=1;
		$name='';
		$year_start='';
		$month_start='';
		$day_start='';
		$year_end='';
		$month_end='';
		$day_end='';
		$nb_days_acess_before='';
		$nb_days_acess_after='';
		$nolimit='';
		$id_coach='';
		$res=SessionManager::edit_session($id,$name,$year_start,$month_start,$day_start,$year_end,$month_end,$day_end,$nb_days_acess_before,$nb_days_acess_after,$nolimit,$id_coach);
		$idsesion->expectOnce(Database::escape_string($name));
		$this->assertTrue(is_object($idsesion));
		$this->assertTrue(is_numeric($id));
		$this->assertTrue(is_string($res));
		//var_dump($id);
	}

	function testget_session_by_name() {
		$session_name='';
		$res=SessionManager::get_session_by_name($session_name);
		if(!is_bool($res)) $this->assertTrue(is_array($res));
		//var_dump($res);
	}

	function testrelation_session_course_exist() {
		$session_id='';
		$course_id='';
		$res=SessionManager::relation_session_course_exist($session_id, $course_id);
		if(!is_numeric($res)) $this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	function testsuscribe_users_to_session() {
		$id_session='';
		$user_list='';
		$res=SessionManager::suscribe_users_to_session($id_session,$user_list,$empty_users=true);
		if(!is_null($res)) $this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	function testupdate_session_extra_field_value() {
		$session_id='';
		$fname='';
		$fvalue='';
		$res=SessionManager::update_session_extra_field_value($session_id,$fname,$fvalue='');
		$this->assertTrue(is_bool($res));
	}
}
?>
