<?php
require_once(api_get_path(LIBRARY_PATH).'glossary.lib.php');

class TestGlossary extends UnitTestCase {

    public function __construct() {
        $this->UnitTestCase('Glossary library - main/inc/lib/glossary.lib.test.php');
        //build a course for these tests
        TestManager::create_test_course('COURSEGLOSSARY');
    }

    public function __destruct() {
    	TestManager::delete_test_course('COURSEGLOSSARY');
    }

	function testGetGlossaryTerms() {
		$res = GlossaryManager::get_glossary_terms();
		$this->assertTrue(is_array($res));
	}

	function testSaveGlossaryIncomplete() {
		$values = 'stuffing';
        ob_start();
		$res = GlossaryManager::save_glossary($values);
        ob_end_clean();
		$this->assertFalse($res);
	}
	function testSaveGlossaryComplete() {
		$values = array('glossary_title'=>'stuffing','glossary_description'=>'something to fill');
        ob_start();
        $res = GlossaryManager::save_glossary($values);
        ob_end_clean();
        $this->assertTrue(is_numeric($res));
        $this->assertTrue($res > 0);
        //clean
        ob_start();
        GlossaryManager::delete_glossary($res);
        ob_end_clean();
	}

	function testGetGlossaryTermByGlossaryIdIsString() {
		$glossary_id = 1;
		$res = GlossaryManager::get_glossary_term_by_glossary_id($glossary_id);
		$this->assertTrue(is_string($res));
	}
	function testGetGlossaryTermByGlossaryIdIsEmpty() {
		$glossary_id = 1000000;
        $res = GlossaryManager::get_glossary_term_by_glossary_id($glossary_id);
        $this->assertEqual($res,'');
	}

	function testGetGlossaryTermByGlossaryName() {
		$glossary_name = '';
		$res = GlossaryManager::get_glossary_term_by_glossary_name($glossary_name);
		$this->assertTrue(is_string($res));
	}

	function testUpdateGlossary() {
		ob_start();
		$values = '';
		$res = GlossaryManager::update_glossary($values);
        ob_end_clean();
		$this->assertFalse($res);
	}

	function testGetMaxGlossaryItem() {
		$res = GlossaryManager::get_max_glossary_item();
		$this->assertTrue(is_numeric($res));
	}


	function testGlossaryExists() {
		$term = '';
		$not_id = 1;
		$res = GlossaryManager::glossary_exists($term,$not_id);
		$this->assertTrue(is_bool($res));
	}

	function testGetGlossaryInformation() {
		$glossary_id = 1;
		$res = GlossaryManager::get_glossary_information($glossary_id);
		$this->assertTrue(is_bool($res));
	}


	function testDisplayGlossary() {
		ob_start();
		$res = GlossaryManager::display_glossary();
        ob_end_clean();
		$this->assertTrue(is_null($res));
	}

	function testDisplayGlossaryList() {
		ob_start();
		$res = GlossaryManager::display_glossary_list();
		ob_end_clean();
        $this->assertTrue($res);
	}

	function testGetNumberGlossaryTerms() {
		$res = GlossaryManager::get_number_glossary_terms();
		$this->assertTrue(is_numeric($res));
	}

	function testGetGlossaryData() {
		$from = 1;
		$number_of_items = 2;
		$column = 1;
		$direction = 'ASC';
		$res = GlossaryManager::get_glossary_data($from, $number_of_items, $column, $direction);
		$this->assertTrue(is_array($res));
	}

	function testActionsFilter() {
		$glossary_id = 1;
		$url_params = '';
		$row = '';
		$res = GlossaryManager::actions_filter($glossary_id,$url_params,$row);
		$this->assertTrue(is_string($res));
	}

	function testJavascriptGlossary() {
		$res = GlossaryManager::javascript_glossary();
		$this->assertTrue(is_string($res));
	}

	function testReorderGlossary() {
		$res = GlossaryManager::reorder_glossary();
		$this->assertTrue(is_null($res));
	}

	function testMoveGlossary() {
		ob_start();
		$direction = '';
		$glossary_id = '';
		$res = GlossaryManager::move_glossary($direction, $glossary_id);
        ob_end_clean();
		$this->assertTrue(is_null($res));
	}

	function testDeleteGlossaryIsFalse() {
		ob_start();
		$glossary_id = 1;
		$res = GlossaryManager::delete_glossary($glossary_id);
        ob_end_clean();
		$this->assertFalse($res);
	}

	function testDeleteGlossaryIsTrue() {
		$values = array('glossary_title'=>'stuffing','glossary_description'=>'something to fill');
		ob_start();
		$id = GlossaryManager::save_glossary($values);
		$res = GlossaryManager::delete_glossary($id);
		ob_end_clean();
        $this->assertTrue($res);
	}
}