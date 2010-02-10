<?php
require_once(api_get_path(LIBRARY_PATH).'glossary.lib.php');

class TestGlossary extends UnitTestCase {
	
	function testGetGlossaryTerms() {
		//ob_start();
		$res = GlossaryManager::get_glossary_terms();
		$this->assertTrue(is_array($res)); 
		//ob_end_clean();
		//var_dump($res);
	}
	
	function testSaveGlossary() {
		ob_start();
		$values = 'algo';
		$res = GlossaryManager::save_glossary($values);
		$this->assertTrue(is_null($res)); 
		ob_end_clean();
		//var_dump($res);
	}
		
	function testGetGlossaryTermByGlossaryId() {
		//ob_start();
		$glossary_id = 1;
		$res = GlossaryManager::get_glossary_term_by_glossary_id($glossary_id);
		$this->assertTrue(is_null($res)); 
		//ob_end_clean();
		//var_dump($res);
	}
	
	function testGetGlossaryTermByGlossaryName() {
		//ob_start();
		$glossary_name = '';
		$res = GlossaryManager::get_glossary_term_by_glossary_name($glossary_name);
		$this->assertTrue(is_string($res)); 
		//ob_end_clean();
		//var_dump($res);
	}

	
	function testUpdateGlossary() {
		ob_start();
		$values = '';
		$res = GlossaryManager::update_glossary($values);
		$this->assertTrue(is_null($res)); 
		ob_end_clean();
		//var_dump($res);
	}
	
	function testGetMaxGlossaryItem() {
		//ob_start();
		$res = GlossaryManager::get_max_glossary_item();
		$this->assertTrue(is_numeric($res)); 
		//ob_end_clean();
		//var_dump($res);
	}
	
	
	function testGlossaryExists() {
		//ob_start();
		$term = '';
		$not_id = 1;
		$res = GlossaryManager::glossary_exists($term,$not_id);
		$this->assertTrue(is_bool($res)); 
		//ob_end_clean();
		//var_dump($res);
	}
	
	function testGetGlossaryInformation() {
		//ob_start();
		$glossary_id = 1;
		$res = GlossaryManager::get_glossary_information($glossary_id);
		$this->assertTrue(is_bool($res)); 
		//ob_end_clean();
		//var_dump($res);
	}
	

	function testDisplayGlossary() {
		ob_start();
		$res = GlossaryManager::display_glossary();
		$this->assertTrue(is_null($res)); 
		ob_end_clean();
		//var_dump($res);
	}
	
	function testDisplayGlossaryList() {
		ob_start();
		$res = GlossaryManager::display_glossary_list();
		$this->assertTrue(is_null($res)); 
		ob_end_clean();
		//var_dump($res);
	}
	
	function testGetNumberGlossaryTerms() {
		//ob_start();
		$res = GlossaryManager::get_number_glossary_terms();
		$this->assertTrue(is_numeric($res)); 
		//ob_end_clean();
		//var_dump($res);
	}
	
	function testGetGlossaryData() {
		//ob_start();
		$from = 1;
		$number_of_items = 2;
		$column = 1;
		$direction = 'ASC';
		$res = GlossaryManager::get_glossary_data($from, $number_of_items, $column, $direction);
		$this->assertTrue(is_array($res)); 
		//ob_end_clean();
		//var_dump($res);
	}
	
	function testActionsFilter() {
		//ob_start();
		$glossary_id = 1;
		$url_params = '';
		$row = '';
		$res = GlossaryManager::actions_filter($glossary_id,$url_params,$row);
		$this->assertTrue(is_string($res)); 
		//ob_end_clean();
		//var_dump($res);
	}
	
	function testJavascriptGlossary() {
		//ob_start();
		$res = GlossaryManager::javascript_glossary();
		$this->assertTrue(is_string($res)); 
		//ob_end_clean();
		//var_dump($res);
	}
	
	function testReorderGlossary() {
		//ob_start();
		$res = GlossaryManager::reorder_glossary();
		$this->assertTrue(is_null($res)); 
		//ob_end_clean();
		//var_dump($res);
	}	
	
	function testMoveGlossary() {
		ob_start();
		$direction = '';
		$glossary_id = '';
		$res = GlossaryManager::move_glossary($direction, $glossary_id);
		$this->assertTrue(is_null($res)); 
		ob_end_clean();
		//var_dump($res);
	}
	
	function testDeleteGlossary() {
		ob_start();
		$glossary_id = 1;
		$res = GlossaryManager::delete_glossary($glossary_id);
		$this->assertTrue(is_null($res)); 
		ob_end_clean();
		//var_dump($res);
	}	
}