<?php
require_once(api_get_path(LIBRARY_PATH).'legal.lib.php');
Mock::generate('Database');


class TestLegal extends UnitTestCase{

	public $lmanager;
	public function TestLegal(){
		
		$this->UnitTestCase('');
	}
	
	public function setUp(){
		
		$this->lmanager = new LegalManager();
	}
	
	public function tearDown(){
		
		$this->lmanager = null;
		
	}
	
	public function testAdd(){
		$instans = new MockDatabase();
		$language='';
		$content=''; 
		$type=''; 
		$changes='';
		$res = LegalManager::add($language, $content, $type, $changes);
		$instans->expectMaximumCallCount(Database,13);
		if(is_bool($res))
		$this->assertTrue($res === true || $res === false);
		else
		$this->assertTrue(is_null($res));
		$this->assertTrue(is_object($instans));
		//var_dump($res);
		//var_dump($instans);
	}
	/*
	public function testDelete(){
		
	}*/
	
	public function testGetLastConditionVersion(){
		$instans = new MockDatabase();
		$language='english';
		$res = LegalManager::get_last_condition_version($language);
		$instans->expectCallCount(Database);
		if(!is_array($res)):
		$this->assertTrue(is_numeric($res));
		endif;
		$this->assertTrue(is_object($instans));
		//var_dump($res);
		//var_dump($instans);
	}
	
	public function testGetLastCondition(){
		$instans = new MockDatabase();
		$language='english';
		$result=2;
		$res = LegalManager::get_last_condition($language);
		$instans->expectOnce(Database::fetch_array($result));
		if(is_bool($res)){
		$this->assertTrue($res===true || $res === false);
		$this->assertTrue(is_bool($res));
		}else{
		$this->assertTrue(is_array($res));}
		$this->assertTrue(is_object($instans));
		//var_dump($res);
		//var_dump($instans);
	}
	
	public function testShowLastCondition(){
		$term_preview=1;
		$preview ='';
		$res = LegalManager::show_last_condition($term_preview);
		$this->assertTrue($res);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}
	
	public function testGetLastVersion(){
		$instans = new MockDatabase();
		$language='en';
		$$res = LegalManager::get_last_version($language);
		$instans->expectCallCount(Database);
		if(is_bool($res)){
			$this->assertTrue(is_bool($res));
			$this->assertTrue($res === true || $res === false);
		}else{
			$this->assertTrue(is_null($res));
			$this->assertNull($res);
		}
		$this->assertTrue(is_object($instans));
		//var_dump($res);
		//var_dump($instans);
	}
	
	public function testget_legal_data(){
		$instans = new MockDatabase();
		$from=''; 
		$number_of_items=''; 
		$column='';
		$res = LegalManager::get_legal_data($from, $number_of_items, $column);
		$instans->expectOnce(Database);
		$this->assertTrue(is_array($res));
		$this->assertTrue($instans);
		//var_dump($res);
		//var_dump($instans);	
	}
	
	public function testCount(){
		$instans = new MockDatabase();
		$res = LegalManager::count();
		$instans->expectCallCount(Database);
		$this->assertTrue(is_string($res));
		$this->assertTrue(is_object($instans));
		var_dump($res);
		var_dump($instans);
	}
}
?>
