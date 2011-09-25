<?php
class TestNotebook extends UnitTestCase {

	public function __construct() {
        $this->UnitTestCase('Glossary library - main/inc/lib/notebook.lib.test.php');
		TestManager::create_test_course('COURSENOTEBOOK');
	}

    public function __destruct() {
        TestManager::delete_test_course('COURSENOTEBOOK');
    }

	public function testJavascriptNotebookOutputsString() {
		$res = NotebookManager::javascript_notebook(null);
		$lang = get_lang("NoteConfirmDelete");
		$this->assertTrue(is_string($res));
		$this->assertPattern('/script/m',$res);
        $this->assertPattern('/\/script/m',$res);
	}

	public function testSaveNoteIsFalseWhenNoteIsNull() {
		$resNull = NotebookManager::save_note(null);
		$this->assertFalse($resNull);
    }

    public function testSaveNoteIsFalseWhenNoteIsNegativeInt() {
		$resFalse = NotebookManager::save_note(-1);
	 	$this->assertFalse($resFalse);
    }

    public function testSaveNoteIsFalseWhenNoteIsEmptyArray() {
	 	$res = NotebookManager::save_note(array());
		$this->assertTrue(is_bool($res));
	}

	public function testGetNoteInformationIsFalseWhenNoteIsNull() {
		$resNull = NotebookManager::get_note_information(null);
	 	$this->assertFalse($resNull);
    }

    public function testGetNoteInformationIsFalseWhenNoteIsNegativeInt() {
	 	$resFalse = NotebookManager::get_note_information(-1);
	 	$this->assertFalse($resFalse);
    }

    public function testGetNoteInformationIsArray() {
	 	$res = NotebookManager::get_note_information(1);
	 	$this->assertTrue(is_array($res));
	}

	public function testUpdateNoteIsFalseWhenNoteIsNull() {
		$resNull = NotebookManager::update_note(null);
	 	$this->assertFalse($resNull);
    }

    public function testUpdateNoteIsFalseWhenNoteDoesNotExist() {
	 	$resFalse = NotebookManager::update_note('char');
	 	$this->assertFalse($resFalse);
    }

    public function testUpdateNoteIsFalseWhenNoteIsEmptyArray() {
	 	$res = NotebookManager::update_note(array());
	 	$this->assertFalse($res);
	}

	public function testDisplayNotes() {
		ob_start();
		$res = NotebookManager::display_notes();
		$this->assertTrue(is_null($res));
		ob_end_clean();
	}

	public function testDeleteNoteIsFalseWhenNoteIsNull() {
		$resNull = NotebookManager::delete_note(null);
	 	$this->assertFalse($resNull);
    }

    public function testDeleteNoteIsFalseWhenNoteIsNegative() {
	 	$resFalse = NotebookManager::delete_note(-1);
	 	$this->assertFalse($resFalse);
    }

    public function testDeleteNoteISFalseWhenNoteDoesNotExist() {
	 	$res = NotebookManager::delete_note(1);
		$this->assertFalse($res);
	}
}