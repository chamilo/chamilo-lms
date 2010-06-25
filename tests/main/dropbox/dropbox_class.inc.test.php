<?php

class TestDropbox extends UnitTestCase {
	
	public $ddropboxwork;
	public $ddropboxsentwork;
	public $dperson;
	
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
		$this->dperson = new Dropbox_Person(1, 1, 1);
	}
	
	public function tearDown() {		
		$this-> ddropboxwork = null;
		$this-> ddropboxsentwork = null;
		$this-> dperson = null;
	}
	
//Class Dropbox_Work

	/**
	 * Constructor calls private functions to create a new work or retreive an existing work from DB
	 * depending on the number of parameters
	 *
	 * @param unknown_type $arg1
	 * @param unknown_type $arg2
	 * @param unknown_type $arg3
	 * @param unknown_type $arg4
	 * @param unknown_type $arg5
	 * @param unknown_type $arg6
	 * @return Dropbox_Work
	 */
	 
	function testDropbox_Work() {			
		$arg1=1;
		$resu= $this->ddropboxwork->Dropbox_Work($arg1, $arg2=null, $arg3=null, $arg4=null, $arg5=null, $arg6=null);
		$this->assertTrue(is_null($resu));
        //var_dump($resu);
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
	 * Constructor calls private functions to create a new work or retreive an existing work from DB
	 * depending on the number of parameters
	 *
	 * @param unknown_type $arg1
	 * @param unknown_type $arg2
	 * @param unknown_type $arg3
	 * @param unknown_type $arg4
	 * @param unknown_type $arg5
	 * @param unknown_type $arg6
	 * @param unknown_type $arg7
	 * @return Dropbox_SentWork
	 */
	 	
	function testDropbox_SentWork() {
		$arg1 = 1;
		$resu= $this->ddropboxsentwork->Dropbox_SentWork($arg1, $arg2=null, $arg3=null, $arg4=null, $arg5=null, $arg6=null, $arg7=null);
		$this->assertTrue(is_null($resu));
	}	
		
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
	 
	function testCreateNewSentWork() {
		$recipient_ids = array(1,2);
		$uploader_id=1;
		$title='test';
		$description = 'testing';
		$author= 'test';
		$filename='test.txt';
		$filesize=125;
		$resu= $this->ddropboxsentwork->_createNewSentWork($uploader_id, $title, $description, $author, $filename, $filesize, $recipient_ids);
		$this->assertTrue(is_null($resu));
	}
	
	/**
	 * private function creating existing object by retreiving info from db
	 *
	 * @param unknown_type $id
	 */
	 
	function testCreateExistingSentWork() {
		$id = 1;
		$resu= $this->ddropboxsentwork->_createExistingSentWork($id);
		$this->assertTrue(is_null($resu));
	}
	
//Class Dropbox_SentWork
	
	/**
	 * Constructor for recreating the Dropbox_Person object
	 *
	 * @param unknown_type $userId
	 * @param unknown_type $isCourseAdmin
	 * @param unknown_type $isCourseTutor
	 * @return Dropbox_Person
	 */
	
	function testDropbox_Person() {
		$userId = 1;
		$isCourseAdmin = 1;
		$isCourseTutor = 1;
		$resu= $this->dperson->Dropbox_Person($userId, $isCourseAdmin, $isCourseTutor);
		$this->assertTrue(is_null($resu));
	}
	
	/**
	 * This private method is used by the usort function in  the
	 * orderSentWork and orderReceivedWork methods.
	 * It compares 2 work-objects by 1 of the properties of that object, dictated by the
	 * private property _orderBy
	 *
	 * @param unknown_type $a
	 * @param unknown_type $b
	 * @return -1, 0 or 1 dependent of the result of the comparison.
	 */
	 
	function testCmpWork() {
		$a = 1;
		$b = 1;
		$resu= $this->dperson->_cmpWork($a, $b);
		if(!is_numeric($resu)) {
		$this->assertTrue(is_null($resu));
		}
	}
	
	/**
	 * method that sorts the objects in the sentWork array, dependent on the $sort parameter.
	 * $sort can be lastDate, firstDate, title, size, ...
	 *
	 * @param unknown_type $sort
	 */
	 
	 function testorderSentWork() {
		$sort = 1;
		$resu= $this->dperson->orderSentWork($sort);
		if(!is_numeric($resu)) {
		$this->assertTrue(is_null($resu));
		}
	}
	
	/**
	 * method that sorts the objects in the receivedWork array, dependent on the $sort parameter.
	 * $sort can be lastDate, firstDate, title, size, ...
	 * @param unknown_type $sort
	 */
	 
	function testorderReceivedWork() {
		$sort = 1;
		$resu= $this->dperson->orderReceivedWork($sort);
		if(!is_numeric($resu)) {
		$this->assertTrue(is_null($resu));
		}
	}
	
	/**
	 * Updates feedback for received work of this person with id=$id
	 *
	 * @param unknown_type $id
	 * @param unknown_type $text
	 */
	 
	 function testupdateFeedback() {
		$id = 1;
		$text = 'test';
		$resu= $this->dperson->updateFeedback($id, $text);
		if(!is_bool($resu)) {
		$this->assertTrue(is_null($resu));
		}
	}
	
	/**
	 * Filter the received work
	 * @param string $type
	 * @param string $value
	 */
	 
	 function testfilter_received_work() {
		$type = 1;
		$value = 1;
		$resu= $this->dperson->filter_received_work($type,$value);
		if(!is_bool($resu)) {
		$this->assertTrue(is_null($resu));
		}
	}
	
	/**
	 * Deletes all the received work of this person
	 *
	 */
	 
	 function testdeleteAllReceivedWork() {
		$resu= $this->dperson->deleteAllReceivedWork();
		if(!is_numeric($resu)) {
		$this->assertTrue(is_null($resu));
		}
	}
	
	/**
	 * Deletes all the received categories and work of this person
	 */
	 
	function testdeleteReceivedWorkFolder() {
		$id = 1;
		$resu= $this->dperson->deleteReceivedWorkFolder($id);
		if(!is_bool($resu)) {
		$this->assertTrue(is_null($resu));
		}
	}
		
	/**
	 * Deletes a received dropbox file of this person with id=$id
	 *
	 * @param integer $id
	 */
	 
	 function testdeleteReceivedWork() {
		$id = 1;
		$resu= $this->dperson->deleteReceivedWork($id);
		if(!is_bool($resu)) {
		$this->assertTrue(is_null($resu));
		}
	}
	
	 /**
	 * Deletes all the sent dropbox files of this person
	 */
	
	 function testdeleteAllSentWork() {
		$resu= $this->dperson->deleteAllSentWork();
		if(!is_bool($resu)) {
		$this->assertTrue(is_null($resu));
		}
	}
	
	/**
	 * Deletes a sent dropbox file of this person with id=$id
	 *
	 * @param unknown_type $id
	 */
	
	function testdeleteSentWork() {
		$id = 1;
		$resu= $this->dperson->deleteSentWork($id);
		if(!is_bool($resu)) {
		$this->assertTrue(is_null($resu));
		}
	}
}
?>
