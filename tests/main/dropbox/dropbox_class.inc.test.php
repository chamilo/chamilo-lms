<?php

class TestDropbox extends UnitTestCase {
	
	public $ddropboxwork;
	public $ddropboxsentwork;
//	public $dperson;
	
	
	
	public function TestDropbox() {
	$this->UnitTestCase('');
	}
	
	public function setUp() {				
		global $dropbox_cnf; 
		$dropbox_cnf['tbl_post'] 		= Database::get_course_table(TABLE_DROPBOX_POST);
		$dropbox_cnf['tbl_file'] 		= Database::get_course_table(TABLE_DROPBOX_FILE);
		$dropbox_cnf['tbl_person'] 		= Database::get_course_table(TABLE_DROPBOX_PERSON);
		$dropbox_cnf['tbl_intro'] 		= Database::get_course_table(TABLE_TOOL_INTRO);
		$dropbox_cnf['tbl_user'] 		= Database::get_main_table(TABLE_MAIN_USER);
		$dropbox_cnf['tbl_course_user']	= Database::get_main_table(TABLE_MAIN_COURSE_USER);
		$dropbox_cnf['tbl_category'] 	= Database::get_course_table(TABLE_DROPBOX_CATEGORY);
		$dropbox_cnf['tbl_feedback'] 	= Database::get_course_table(TABLE_DROPBOX_FEEDBACK);
		$this->ddropboxwork = new Dropbox_Work(1);			
		$this->ddropboxsentwork = new Dropbox_SentWork(1);
		//	$this->dperson = new Dropbox_Person();
	}
	
	public function tearDown() {		
		$this-> ddropboxwork = null;
		$this-> ddropboxsentwork = null;
		//$this-> dperson = null;
	}
	
	/**
	 * private function creating a new work object
	 *
	 * @param unknown_type $uploader_id
	 * @param unknown_type $title
	 * @param unknown_type $description
	 * @param unknown_type $author
	 * @param unknown_type $filename
	 * @param unknown_type $filesize
	 *
	 * @todo 	$author was originally a field but this has now been replaced by the first and lastname of the uploader (to prevent anonymous uploads)
	 * 			As a consequence this parameter can be removed
	 */
	function testCreateNewWork() {
		global $dropbox_cnf;				
		$uploader_id=1;
		$title='test';
		$description = 'testing';
		$author= 'test';
		$filename='test.txt';
		$filesize=125;
		$resu= $this->ddropboxwork->_createNewWork($uploader_id, $title, $description, $author, $filename, $filesize);
		$this->assertTrue(is_null($resu));
        //var_dump($resu);
	}
	
	
	/**
	 * private function creating existing object by retreiving info from db
	 *
	 * @param unknown_type $id
	 */
	/*
	function testCreateExistingWork() {
		global $dropbox_cnf;
		$dropbox_cnf['tbl_file'] 		= Database::get_course_table(TABLE_DROPBOX_FILE);
		$dropbox_cnf['tbl_feedback'] 	= Database::get_course_table(TABLE_DROPBOX_FEEDBACK);
		$id = 1;
		$resu= $this->ddropboxwork->_createExistingWork($id);
		$this->assertTrue(is_null($resu));
        //var_dump($resu);
	}
	
	//Class Dropbox_SentWork
		
	/**
	 * private function creating a new SentWork object
	 *
	 * @param unknown_type $uploader_id
	 * @param unknown_type $title
	 * @param unknown_type $description
	 * @param unknown_type $author
	 * @param unknown_type $filename
	 * @param unknown_type $filesize
	 * @param unknown_type $recipient_ids
	 */
	 /*
	function testCreateNewSentWork() {
		$recipient_ids = array();
		$uploader_id=1;
		$title='test';
		$description = 'testing';
		$author= 'test';
		$filename='test.txt';
		$filesize=125;
		$resu= $this->ddropboxsentwork->_createNewSentWork($uploader_id, $title, $description, $author, $filename, $filesize, $recipient_ids);
		$this->assertTrue(is_null($resu));
        var_dump($resu);
	}

	
	*/
	
	
	
	
	
	
	
	
	
	
	
}
?>
