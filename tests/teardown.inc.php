<?php
// $Id: teardown.inc.php 2010-02-17 14:20:00Z aportugal $

/* For licensing terms, see /chamilo_license.txt */
/**
==============================================================================
*  This is the settings file destroy than need some functions to finish the test
*
*	It destroy:
*	- require_once
*	- constructs
*   - creation course
*	- session
*	- api_allow_edit
*	- api_session
*
*
*	@todo rewrite code to separate display, logic, database code
*	@package chamilo.main
==============================================================================
*/

/**
 * @todo shouldn't these settings be moved to the test_suite.php.
 * 		 if these are really configuration then we can make require_once in each tests.
 * @todo use this file to destroy the setup in each file test.
 * @todo check for duplication of require with test_suite.php
 */
 
/*
-----------------------------------------------------------
	Included libraries
-----------------------------------------------------------
*/
require_once(api_get_path(LIBRARY_PATH).'course.lib.php');

/*
==============================================================================
		MAIN CODE
==============================================================================
*/
$code = 'COURSETEST';
$res = CourseManager::delete_course($code);
$path = api_get_path(SYS_PATH).'archive';
if ($handle = opendir($path)) {
	while (false !== ($file = readdir($handle))) {
		if (strpos($file,$code)!==false) {
			if (is_dir($path.'/'.$file)) {
				rmdirr($path.'/'.$file);
			}
		}
	}
	closedir($handle);
}
	
	
	
	
	
		$dirname = api_get_path(SYS_LANG_PATH);
		$perm_dir = substr(sprintf('%o', fileperms($dirname)), -4);
		if ($perm_dir != '0777') {
			$msg = "Error";
			$this->assertTrue(is_string($msg));
		} else {
			$path = $dirname.'upload';
			$filemode = '0777';
			$res = api_chmod_R($path, $filemode);
			unlink($path);
			$this->assertTrue($res || IS_WINDOWS_OS); // We know, it does not work for Windows.
		}
	/*	
		function testApiIsAllowed(){
	    global $_course, $_user;
	  	$tool= 'full';
	 	$action = 'delete';
	 	$res=api_is_allowed($tool, $action, $task_id=0);
	 	if(!is_bool($res)){
	  	$this->assertTrue(is_null($res));
	  	}
	  	$this->assertTrue($action);
	 	$this->assertTrue($_user['user_id']);
	}

 
 function testApiNotAllowed(){
		ob_start();
		//api_not_allowed($print_headers = false);
		$res = ob_get_contents();
		$this->assertEqual($res,'');
		ob_end_clean();
	}
	
		function testApiIsAllowedToCreateCourse() {
		$res=api_is_allowed_to_create_course();
		if(!is_bool($res)){
			$this->assertTrue(is_null($res));
		}
   	}
   	
   	   	function testApiIsCoach(){
		global $_user;
		global $sessionIsCoach;
		$_user['user_id']=2;
		$sessionIsCoach=Database::store_result($result=false);
		$res=api_is_coach();
		$this->assertTrue(is_bool($res));
		//var_dump($res);
		$this->assertTrue($_user['user_id']);
		$this->assertTrue(is_array($sessionIsCoach));
		//var_dump($sessionIsCoach);
   	}

   	function testApiIsSessionAdmin(){
		global $_user;
		$_user['status']=true;
		$res=api_is_session_admin();
		$this->assertTrue(is_bool($res));
		$this->assertTrue(is_array($_user));
		//var_dump($_user);

    }
    
    
    function testApiIsCourseCoach() {
		$res=api_is_course_coach();
		if(!is_bool($res)){
			$this->assertTrue(is_null($res));
		}
   	}
   	
   	   	function testApiIsSessionAdmin(){
		global $_user;
		$_user['status']=true;
		$res=api_is_session_admin();
		$this->assertTrue(is_bool($res));
		$this->assertTrue(is_array($_user));
		//var_dump($_user);

    }
    
    function testApiNotAllowed(){
		ob_start();
		//api_not_allowed($print_headers = false);
		$res = ob_get_contents();
		$this->assertEqual($res,'');
		ob_end_clean();
	}
	
	function testApiSessionDestroy(){
		 if (!headers_sent()) {
			$res=api_session_destroy();
		 }
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}
	
		function testApiSessionStart(){
		if (!headers_sent()) {
		$res = api_session_start($already_sintalled=true);
		}
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}
 */