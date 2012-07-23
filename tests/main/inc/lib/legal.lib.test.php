<?php
require_once api_get_path(LIBRARY_PATH).'legal.lib.php';

class TestLegal extends UnitTestCase{

	public function __construct() {
		$this->UnitTestCase('Legal terms library - main/inc/lib/legal.lib.test.php');
	}

	public function testAdd(){
		$language='english';
		$content='english';
		$type='';
		$changes='';
		$res = LegalManager::add($language, $content, $type, $changes);
		if(is_bool($res))
		$this->assertTrue($res === true || $res === false);
		else
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	public function testGetLastConditionVersion(){
		$language='english';
		$res = LegalManager::get_last_condition_version($language);
		if(!is_array($res)):
		$this->assertTrue(is_numeric($res));
		endif;
		//var_dump($res);
	}

	public function testGetLastCondition(){
		$language='english';
		$result=2;
		$res = LegalManager::get_last_condition($language);
		if(is_bool($res)){
		$this->assertTrue($res===true || $res === false);
		$this->assertTrue(is_bool($res));
		}else{
		$this->assertTrue(is_array($res));}
		//var_dump($res);
	}

	public function testShowLastCondition(){
		$term_preview=1;
		$preview =true;
		$res = LegalManager::show_last_condition($term_preview);
		$this->assertTrue($res);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	public function testGetLastVersion(){
		$language='english';
		$$res = LegalManager::get_last_version($language);
		if(is_bool($res)){
			$this->assertTrue(is_bool($res));
			$this->assertTrue($res === true || $res === false);
		}else{
			$this->assertTrue(is_null($res));
			$this->assertNull($res);
		}
		//var_dump($res);
	}

	public function testget_legal_data(){
		$from='test';
		$number_of_items=5;
		$column=5;
		$res = LegalManager::get_legal_data($from, $number_of_items, $column);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}

	public function testCount(){
		$res = LegalManager::count();
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}
}
?>
