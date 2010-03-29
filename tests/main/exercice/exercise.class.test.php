<?php

class TestExercise extends UnitTestCase {
	
	public $eExercise;
	
	public function TestExercise() {
		$this->UnitTestCase('');
	}
	
	public function setUp() {

		$this->eExercise = new Exercise();			
	}
	
	public function tearDown() {		
		$this->eExercise = null;
	}
	
	/**
	 * adds a question into the question list
	 * @param - integer $questionId - question ID
	 * @return - boolean - true if the question has been added, otherwise false
	 */
		
	function testaddToList() {
		$questionId = 1;
		$res = $this->eExercise->addToList($questionId);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}
	
	/**
	 * Creates the form to create / edit an exercise
	 * @param FormValidator $form the formvalidator instance (by reference)
	 */
	 
	function testcreateForm() {
		global $id;
		$form = new FormValidator('introduction_text');
		$res = $this->eExercise->createForm($form, $type='full');
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}	
	
	
	/**
	 * disables the exercise
	 */
	 
	 function testdisable() {
		$res = $this->eExercise->disable();
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}	
	
	 function testdisable_results() {
		$res = $this->eExercise->disable_results();
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}
	
	/**
	 * enables the exercise
	 */	
	 
	 function testenable() {
		$res = $this->eExercise->enable();
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}
	
	function testenable_results() {
		$res = $this->eExercise->enable_results();
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}
	
	/**
	 * Same as isRandom() but has a name applied to values different than 0 or 1
	 */
	 
	 function testgetShuffle() {
		$res = $this->eExercise->getShuffle();
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}
	 
	 /**
	 * returns 'true' if the question ID is in the question list
	 *
	 * @author - Olivier Brouckaert
	 * @param - integer $questionId - question ID
	 * @return - boolean - true if in the list, otherwise false
	 */
	
	function testisInList() {
		$questionId = 1;
		$res = $this->eExercise->isInList($questionId);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}
	
	/**
	 * tells if questions are selected randomly, and if so returns the draws
	 *
	 * @author - Olivier Brouckaert
	 * @return - integer - 0 if not random, otherwise the draws
	 */
	
	function testisRandom() {
		$res = $this->eExercise->isRandom();
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}
	
	/**
	 * moves a question down in the list
	 * @param - integer $id - question ID to move down
	 */
	
	function testmoveDown() {
		$id=1;
		$res = $this->eExercise->moveDown($id);
		if(!is_null($res)){
		$this->assertTrue(is_numeric($res));
		}
		//var_dump($res);
	}
	
	/**
	 * moves a question up in the list
	 * @param - integer $id - question ID to move up
	 */
	
	function testmoveUp() {
		$id=1;
		$res = $this->eExercise->moveUp($id);
		if(!is_null($res)){
		$this->assertTrue(is_numeric($res));
		}
		//var_dump($res);
	}
	
	/**
	 * function which process the creation of exercises
	 * @param FormValidator $form the formvalidator instance
	 */
	 /*
	 function testprocessCreation() {
		$form = new FormValidator('exerciseTitle');
		$res = $this->eExercise->processCreation($form,$type='');
		if(!is_null($res)){
		$this->assertTrue(is_numeric($res));
		}
		//var_dump($res);
	}*/
	
	/**
	 * reads exercise informations from the data base
	 * @param - integer $id - exercise ID
	 * @return - boolean - true if exercise exists, otherwise false
	 */
	
	function testread() {
		global $_course;
       	global $_configuration;
        global $questionList;
        $id=1;
		$res = $this->eExercise->read($id);
		if(!is_null($res)){
		$this->assertTrue(is_bool($res));
		}
		//var_dump($res);
	}
	
	/**
	 * removes a question from the question list
	 * @param - integer $questionId - question ID
	 * @return - boolean - true if the question has been removed, otherwise false
	 */
	 
	 function testremoveFromList() {
		global $_course;
       	global $_configuration;
        global $questionList;
        $questionId=1;
		$res = $this->eExercise-> removeFromList($questionId);
		if(!is_null($res)){
		$this->assertTrue(is_bool($res));
		}
		//var_dump($res);
	}
	 
	 /**
	 * updates the exercise in the data base
	 */
	 
	 function testsave() {
		global $_course,$_user;
		$res = $this->eExercise-> save($type_e='');
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}
	
	 function testsearch_engine_delete() {
		$res = $this->eExercise-> search_engine_delete();
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}
	
	function testsearch_engine_save() {
		$res = $this->eExercise-> search_engine_save();
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}
	
	/**
	 * returns the number of attempts setted
	 * @return - numeric - exercise attempts
	 */
	 
	 function testselectAttempts() {
		$res = $this->eExercise-> selectAttempts();
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}
	
	/**
	 * returns the exercise description
	 * @return - string - exercise description
	 */
	 
	 function testselectDescription() {
		$res = $this->eExercise-> selectDescription();
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}
	
	/**
	 * returns the expired time
	 * @return - string - expired time
	 */
	 
	function testselectExpiredTime() {
		$res = $this->eExercise-> selectExpiredTime();
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}
	 
	/** returns the number of FeedbackType  *
	 *  0=>Feedback , 1=>DirectFeedback, 2=>NoFeedback
	 * @return - numeric - exercise attempts
	 */ 
	
	function testselectFeedbackType() {
		$res = $this->eExercise-> selectFeedbackType();
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}
	
	/**
	 * returns the exercise ID
	 * @return - integer - exercise ID
	 */
	 
	 function testselectId() {
		$res = $this->eExercise-> selectId();
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}
	
	/**
	 * returns the number of questions in this exercise
	 * @return - integer - number of questions
	 */
	 
	 function testselectNbrQuestions() {
		$res = $this->eExercise-> selectNbrQuestions();
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}
	 
	/**
	 * returns the array with the question ID list
	 * @return - array - question ID list
	 */
	 
	 function testselectQuestionList() {
		$res = $this->eExercise-> selectQuestionList();
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}
	  
	/**
	 * returns random answers status.
	 */ 
	 
	function testselectRandomAnswers() {
		$res = $this->eExercise-> selectRandomAnswers();
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}
	
	/**
     * selects questions randomly in the question list
	 *
	 * @author - Olivier Brouckaert
	 * @return - array - if the exercise is not set to take questions randomly, returns the question list
	 *					 without randomizing, otherwise, returns the list with questions selected randomly
     */
     
     function testselectRandomList() {
		$res = $this->eExercise-> selectRandomList();
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}
	
	/**
	 * tells if questions are selected randomly, and if so returns the draws
	 * @return - integer - results disabled exercise
	 */
	 
	 function testselectResultsDisabled() {
		$res = $this->eExercise-> selectResultsDisabled();
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}
	
	/**
	 * returns the exercise sound file
	 * @return - string - exercise description
	 */
	 
	 function testselectSound() {
		$res = $this->eExercise-> selectSound();
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}
	
	/**
	 * returns the exercise status (1 = enabled ; 0 = disabled)
	 * @return - int - true if enabled, otherwise false
	 */
	 
	 function testselectStatus() {
		$res = $this->eExercise-> selectStatus();
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}
	
	/**
	 * returns the time limit
	 * @return int
	 */
	 
	 function testselectTimeLimit() {
		$res = $this->eExercise-> selectTimeLimit();
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}
	
	/**
	 * returns the exercise title
	 * @return - string - exercise title
	 */
	 
	 function testselectTitle() {
		$res = $this->eExercise-> selectTitle();
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}
	
	/**
	 * returns the exercise type
	 * @return - integer - exercise type
	 */
	 
	 function testselectType() {
		$res = $this->eExercise-> selectType();
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}
	
	/**
	 * sets to 0 if questions are not selected randomly
	 * if questions are selected randomly, sets the draws
	 * @param - integer $random - 0 if not random, otherwise the draws
	 * @return void
	 */
	 
	 function testsetRandom() {
	 	$random = 1;
		$res = $this->eExercise-> setRandom($random);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}
	 
	/**
	 * update the table question
	 * @return void
	 */
	function testupdate_question_positions() {
		$res = $this->eExercise-> update_question_positions();
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}
	
	/**
	 * changes the exercise max attempts
	 * @param - numeric $attempts - exercise max attempts
	 * @return void
	 */
	 
	function testupdateAttempts() {
		$attempts = 1;
		$res = $this->eExercise-> updateAttempts($attempts);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}
	
	/**
	 * changes the exercise description
	 * @param - string $description - exercise description
	 * @return void
	 */
	 
	 function testupdateDescription() {
		$description = 'testdescription';
		$res = $this->eExercise-> updateDescription($description);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}
	
	/**
	* changes the exercise expired_time
	* @param - int The expired time of the quiz
	* @return void
	*/
	
	function testupdateExpiredTime() {
		$expired_time = 1;
		$res = $this->eExercise-> updateExpiredTime($expired_time);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}
	
	/**
	 * changes the exercise feedback type
	 * @param - numeric $attempts - exercise max attempts
	 * @return void
	 */
	 
	function testupdateFeedbackType() {
		$feedback_type = 1;
		$res = $this->eExercise-> updateFeedbackType($feedback_type);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}
	
	/**
	 * sets to 0 if answers are not selected randomly
	 * if answers are selected randomly
	 * @param - integer $random_answers - random answers
	 * @return void
	 */
	
	function testupdateRandomAnswers() {
		$random_answers = 0;
		$res = $this->eExercise-> updateRandomAnswers($random_answers);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}
	
	/**
	 * update the results
	 * @return void
	 */
	function testupdateResultsDisabled() {
		$results_disabled = 1;
		$res = $this->eExercise->updateResultsDisabled($results_disabled);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}
	
	/**
	 * changes the exercise sound file
	 * @param - string $sound - exercise sound file
	 * @param - string $delete - ask to delete the file
	 * @return void
	 */
	
	function testupdateSound() {
		global $audioPath, $documentPath,$_course, $_user;
		$sound = 'test';
		$delete = 'test';
		$res = $this->eExercise->updateSound($sound,$delete);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}
	
	/**
	 * changes the exercise title
	 * @param - string $title - exercise title
	 * @return void
	 */
	 
	function testupdateTitle() {
		$title = 'test';
		$res = $this->eExercise->updateTitle($title);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}
	
	/**
	 * changes the exercise type
	 * @param - integer $type - exercise type
	 */
	 
	 function testupdateType() {
		$type = 1;
		$res = $this->eExercise->updateType($type);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}
	
	/**
	 * deletes the exercise from the database
	 * Notice : leaves the question in the data base
	 */
	 
	 function testdelete() {
	 	global $_course,$_user;
		$res = $this->eExercise->delete();
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}	
}
?>
