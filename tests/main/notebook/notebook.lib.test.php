<?php

//require_once api_get_path(SYS_CODE_PATH).'notebook/index.php';
//require_once api_get_path(SYS_CODE_PATH).'inc/global.inc.php';
class TestNotebook extends UnitTestCase {

	function testJavascriptNotebook() {
		ob_start();
		$res = NotebookManager::javascript_notebook();
		$this->assertTrue(is_string($res)); 
		ob_end_clean();
		//var_dump($res);
	}
		
	function testSaveNote() {
		ob_start();
		$values =array();
		$res = NotebookManager::save_note($values);
		$this->assertTrue(is_bool($res)); 
		ob_end_clean();
		//var_dump($res);
	}

	function testGetNoteInformation() {		
		ob_start();
		$notebook_id=1;
		$res = NotebookManager::get_note_information($notebook_id);
		$this->assertTrue(!(bool)$res);		
		ob_end_clean();
		//var_dump($res);	
	}	
	
	function testUpdateNote() {
		ob_start();
		$values=array();
		$res = NotebookManager::update_note($values);
		$this->assertTrue(is_bool($res));
		ob_end_clean();
		//var_dump($res);	
	}	
	
	function testDisplayNotes() {
		ob_start();
		$res = NotebookManager::display_notes();
		$this->assertTrue(is_null($res));
		ob_end_clean();
		//var_dump($res);
	}	
	
	function testDeleteNote() {
		ob_start();
		$notebook_id=1;
		$res = NotebookManager::delete_note($notebook_id);
		$this->assertTrue(is_bool($res));
		ob_end_clean();
		//var_dump($res);
	}	
	
	
}
?>