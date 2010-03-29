<?php
class TestNotebook extends UnitTestCase {
	
	public function __construct() {
		TestManager::create_test_course('COURSENOTEBOOK');
	}

	public function testJavascriptNotebook() {
		$res = NotebookManager::javascript_notebook(null);
		$lang = get_lang("NoteConfirmDelete");
		$this->assertTrue(is_string($res));
		$this->assertPattern('/'.addslashes($lang).'/m',$res);		
	}
		
	public function testSaveNote() {
		$resNull = NotebookManager::save_note(null);
		$this->assertFalse($resNull);
		$resFalse = NotebookManager::save_note(-1);
	 	$this->assertFalse($resFalse);		
		$res = NotebookManager::save_note(array());
		$this->assertTrue(is_bool($res)); 
	}

	public function testGetNoteInformation() {		
		$resNull = NotebookManager::get_note_information(null);
	 	$this->assertFalse($resNull);		
		$resFalse = NotebookManager::get_note_information(-1);
	 	$this->assertFalse($resFalse);		
		$res = NotebookManager::get_note_information(1);
	 	$this->assertTrue(is_array($res));	
	}	
	
	public function testUpdateNote() {
		$resNull = NotebookManager::update_note(null);
	 	$this->assertFalse($resNull);		
		$resFalse = NotebookManager::update_note('char');
	 	$this->assertFalse($resFalse);		
		$res = NotebookManager::update_note(array());
	 	$this->assertTrue(is_bool($res));	


	}	
	
	public function testDisplayNotes() {
		ob_start();
		$res = NotebookManager::display_notes();
		$this->assertTrue(is_null($res));
		ob_end_clean();
	}	
	
	public function testDeleteNote() {
		$resNull = NotebookManager::delete_note(null);
	 	$this->assertFalse($resNull);		
		$resFalse = NotebookManager::delete_note(-1);
	 	$this->assertFalse($resFalse);			
		$res = NotebookManager::delete_note(1);
		$this->assertTrue(is_bool($res));
	}	
	
	public function __destruct() {
		TestManager::delete_test_course('COURSENOTEBOOK');
	}
}
?>