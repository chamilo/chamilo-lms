<?php

require_once(api_get_path(LIBRARY_PATH).'sessionmanager.lib.php');

class TestSessionManager extends UnitTestCase {

	public function TestSessionManager(){
		$this->UnitTestCase('Sessions manager library - main/inc/lib/sessionmanager.lib.test.php');
	}
	function testadd_courses_to_session() {
		$id_session='';
		$course_list='';
		ob_start();
		$res=SessionManager::add_courses_to_session($id_session,$course_list);
		if(!empty($res)) {
			$this->assertTrue(is_null($res));
		} else {
			$this->assertTrue(is_bool($res));
		}
		ob_end_clean();
		//var_dump($res);
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



	function testget_session_by_name() {
		$session_name='';
		$res=SessionManager::get_session_by_name($session_name);
		if(!is_bool($res)) $this->assertTrue(is_array($res));
		//var_dump($res);
	}

	function testrelation_session_course_exist() {
		$session_id=1;
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
