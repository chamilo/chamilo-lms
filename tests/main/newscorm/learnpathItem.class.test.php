<?php
require_once(api_get_path(SYS_CODE_PATH).'newscorm/learnpathItem.class.php');
require_once(api_get_path(SYS_CODE_PATH).'newscorm/learnpath.class.php');


class TestLearnpathItem extends UnitTestCase {
	
	
	public function TestScorm() {
		$this->UnitTestCase('Test Scorm');
	}
	

	public function __construct() {
		// The constructor acts like a global setUp for the class			
		require_once api_get_path(SYS_TEST_PATH).'setup.inc.php';
	}
	
	
	public function testAddLp() {
		//ob_start();
		$course = 'COURSETEST';
		$name = 'Leccion';
		$description = 'Leccion';
		$learnpath = 'guess';
		$origin = 'zip';
		$zipname = '';
		$res = learnpath::add_lp($course, $name, $description, $learnpath, $origin, $zipname);
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	
	public function testAddChild() {
		//ob_start();
		$res = learnpathItem::add_child($item = 1);
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}

	public function testAddInteraction() {
		//ob_start();
		$res = learnpathItem::add_interaction($index = 1,$params = array());
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}

	public function testAddObjective() {
		//ob_start();
		$res = learnpathItem::add_objective($index = 1,$params = array());
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	/*
	public function testClose() {
		//ob_start();
		$obj = new learnpathItem($db_id = 1, $user_id = 1); 
		$res = $obj->close();
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testDelete() {
		//ob_start();
		$obj = new learnpathItem($db_id = 1, $user_id = 1); 
		$res = $obj->delete();
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	*/
	public function testDropChild() {
		//ob_start();
		$res = learnpathItem::drop_child($item = 1);
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testGetAttemptId() {
		//ob_start();
		$res = learnpathItem::get_attempt_id();
	 	$this->assertTrue(is_numeric($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testGetChildren() {
		//ob_start();
		$res = learnpathItem::get_children();
	 	$this->assertTrue(is_array($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testGetCoreExit() {
		//ob_start();
		$res = learnpathItem::get_core_exit();
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testGetCurrentStartTime() {
		//ob_start();
		$res = learnpathItem::get_current_start_time();
	 	$this->assertTrue(is_numeric($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testGetDescription() {
		//ob_start();
		$res = learnpathItem::get_description();
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testGetFilePath() {
		//ob_start();
		$obj = new learnpathItem($db_id = 1, $user_id = 1); 
		$res = $obj->get_file_path($path_to_scorm_dir='');
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testGetId() {
		//ob_start();
		$res = learnpathItem::get_id();
	 	$this->assertTrue(is_numeric($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	/*
	public function testLoadInteractions() {
		//ob_start();
		$obj = new learnpathItem($db_id = 1, $user_id = 1); 
		$res = $obj->load_interactions();
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	*/
	public function testGetInteractionsCount() {
		//ob_start();
		$res = learnpathItem::get_interactions_count($checkdb=false);
	 	$this->assertTrue(is_numeric($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testGetInteractionsJsArray() {
		//ob_start();
		$res = learnpathItem::get_interactions_js_array($checkdb=false);
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testGetObjectivesCount() {
		//ob_start();
		$res = learnpathItem::get_objectives_count();
	 	$this->assertTrue(is_numeric($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testGetLaunchData() {
		//ob_start();
		$res = learnpathItem::get_launch_data();
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testGetLessonLocation() {
		//ob_start();
		$res = learnpathItem::get_lesson_location();
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testGetLessonMode() {
		//ob_start();
		$obj = new learnpathItem($db_id = 1, $user_id = 1); 
		$res = $obj->get_lesson_mode();
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testGetLevel() {
		//ob_start();
		$res = learnpathItem::get_level();
	 	$this->assertTrue(is_numeric($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testGetMasteryScore() {
		//ob_start();
		$res = learnpathItem::get_mastery_score();
	 	$this->assertTrue(is_numeric($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testGetMax() {
		//ob_start();
		$res = learnpathItem::get_max();
	 	$this->assertTrue(is_numeric($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testGetMaxTimeAllowed() {
		//ob_start();
		$res = learnpathItem::get_max_time_allowed();
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testGetMin() {
		//ob_start();
		$res = learnpathItem::get_min();
	 	$this->assertTrue(is_numeric($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testGetParent() {
		//ob_start();
		$res = learnpathItem::get_parent();
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testGetPath() {
		//ob_start();
		$res = learnpathItem::get_path();
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testGetPrereqString() {
		//ob_start();
		$obj = new learnpathItem($db_id = 1, $user_id = 1); 
		$res = $obj->get_prereq_string;
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	///var_dump($res);
	}
	
	public function testGetPreventReinit() {
		//ob_start();
		$res = learnpathItem::get_prevent_reinit();
	 	$this->assertTrue(is_numeric($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testGetRef() {
		//ob_start();
		$res = learnpathItem::get_ref();
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testGetResourcesFromSource() {
		//ob_start();
		$obj = new learnpathItem($db_id = 1, $user_id = 1); 
		$res = $obj->get_resources_from_source($type=null,$abs_path=null, $recursivity=1);
	 	$this->assertTrue(is_array($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testGetScore() {
		//ob_start();
		$res = learnpathItem::get_score();
	 	$this->assertTrue(is_numeric($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testGetStatus() {
		//ob_start();
		$obj = new learnpathItem($db_id = 1, $user_id = 1); 
		$res = $obj->get_status($check_db=true,$update_local=false);
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testGetSuspendData() {
		//ob_start();
		$res = learnpathItem::get_suspend_data();
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testGetScormTime() {
		//ob_start();
		$res = learnpathItem::get_scorm_time($origin='php',$given_time=null);
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testGetTerms() {
		//ob_start();
		$res = learnpathItem::get_terms();
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testGetTitle() {
		//ob_start();
		$res = learnpathItem::get_title();
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testGetTotalTime() {
		//ob_start();
		$res = learnpathItem::get_total_time();
	 	$this->assertTrue(is_numeric($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testGetType() {
		//ob_start();
		$res = learnpathItem::get_type();
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testGetViewCount() {
		//ob_start();
		$res = learnpathItem::get_view_count();
	 	$this->assertTrue(is_numeric($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testIsDone() {
		//ob_start();
		$obj = new learnpathItem($db_id = 1, $user_id = 1); 
		$res = $obj->is_done();
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testIsRestartAllowed() {
		//ob_start();
		$obj = new learnpathItem($db_id = 1, $user_id = 1); 
		$res = $obj->is_restart_allowed();
	 	$this->assertTrue(is_numeric($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testOpen() {
		//ob_start();
		$res = learnpathItem::open($allow_new_attempt=false);
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testOutput() {
		//ob_start();
		$res = learnpathItem::output();
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testParsePrereq() {
		//ob_start();
		$res = learnpathItem::parse_prereq($prereqs_string = '', $items = array(), $refs_list = array(),$user_id = 1);
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	/*
	public function testRestart() {
		//ob_start();
		$obj = new learnpathItem($db_id = 1, $user_id = 1); 
		$res = $obj->restart();
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testSave() {
		//ob_start();
		$obj = new learnpathItem($db_id = 1, $user_id = 1); 
		$res = $obj->save($from_outside=true,$prereqs_complete=false);
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	*/
	public function testSetAttemptId() {
		//ob_start();
		$res = learnpathItem::set_attempt_id($num = 1);
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testSetCoreExit() {
		//ob_start();
		$res = learnpathItem::set_core_exit($value = 1);
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testSetDescription() {
		//ob_start();
		$res = learnpathItem::set_description($string = '');
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testSetLessonLocation() {
		//ob_start();
		$res = learnpathItem::set_lesson_location($location = '');
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testSetLevel() {
		//ob_start();
		$res = learnpathItem::set_level($int=0);
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testSetLpView() {
		//ob_start();
		$obj = new learnpathItem($db_id = 1, $user_id = 1); 
		$res = $obj->set_lp_view($lp_view_id = 1);
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testSetPath() {
		//ob_start();
		$res = learnpathItem::set_path($string='');
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testSetPreventReinit() {
		//ob_start();
		$res = learnpathItem::set_prevent_reinit($prevent = 1);
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testSetScore() {
		//ob_start();
		$obj = new learnpathItem($db_id = 1, $user_id = 1);
		$res = $obj->set_score($score = 1.56);
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testSetMaxScore() {
		//ob_start();
		$res = learnpathItem::set_max_score($score = 1.56);
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	/*
	public function testSetStatus() {
		//ob_start();
		$res = learnpathItem::set_status($status = '');
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	*/
	public function testSetTerms() {
		//ob_start();
		$obj = new learnpathItem($db_id = 1, $user_id = 1); 
		$res = $obj->set_terms($terms = '');
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testGetSearchDid() {
		//ob_start();
		$res = learnpathItem::get_search_did();
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testSetTime() {
		//ob_start();
		$obj = new learnpathItem($db_id = 1, $user_id = 1); 
		$res = $obj->set_time($scorm_time = '',$format='scorm');
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testSetTitle() {
		//ob_start();
		$res = learnpathItem::set_title($string='');
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testSetType() {
		//ob_start();
		$res = learnpathItem::set_type($string='');
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testStatusIs() {
		//ob_start();
		$obj = new learnpathItem($db_id = 1, $user_id = 1); 
		$res = $obj->status_is($list=array());
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testUpdateTime() {
		//ob_start();
		$res = learnpathItem::update_time($total_sec=0);
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testWriteObjectivesToDb() {
		//ob_start();
		$res = learnpathItem::write_objectives_to_db();
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	/*
	public function testWriteToDb() {
		//ob_start();
		$obj = new learnpathItem($db_id = 1, $user_id = 1); 
		$res = $obj->write_to_db();
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	*/
	public function __destruct() {
		// The destructor acts like a global tearDown for the class			
	//	require_once api_get_path(SYS_TEST_PATH).'teardown.inc.php';			
	}
}