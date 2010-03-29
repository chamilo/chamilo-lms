<?php
require_once api_get_path(SYS_CODE_PATH).'exercice/question.class.php';

class TestExerciseLib extends UnitTestCase {
	/*public $eQuestion;
	
	public function TestExerciseLib() {
		$this->UnitTestCase('');
	}
	
	public function setUp() {

		$this->eQuestion = new Question();			
	}
	
	public function tearDown() {		
		$this->eQuestion = null;
	}*/
	/**
	 * @param int question id
	 * @param boolean only answers
	 * @param boolean origin i.e = learnpath
	 * @param int current item from the list of questions
	 * @param int number of total questions
	 */
 	 
 	 function testshowQuestion() {
 	 	global $_course;
 	 	$questionId = 1;
 	 	$current_item = 1 ;
 	 	$total_item = 1;
 	 	//$objQuestionTmp = $question->read($questionId);
 	 	$res = showQuestion($questionId, $onlyAnswers=false, $origin=false,$current_item, $total_item);
 	 	$this->assertTrue(is_null($res));
 	 	var_dump($res);
 	 }
	
	
	
	
}	
?>
