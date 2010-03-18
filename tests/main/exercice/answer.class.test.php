<?php

class TestAnswer extends UnitTestCase {

	public $aAnswer;
	
	public function TestAnswer() {
		$this->UnitTestCase('');
	}
	
	public function setUp() {
		/*
		//Create a new exercise
		require_once api_get_path(SYS_CODE_PATH).'exercice/exercise.class.php';
		require_once api_get_path(SYS_CODE_PATH).'inc/lib/pear/HTML/QuickForm.php';
		$this->aAnswer = new Answer(2);
		$this->aAnswer->read();
		$this->aAnswer = new Exercise();
		
		$form = array(
		'exerciseTitle'=>'testtitle',
		'exerciseDescription'=>'testdescription',
		'exerciseAttempts'=>'0',
		'exerciseFeedbackType'=>'0',
		'exerciseType'=>'1',
		'randomQuestions'=>'0',
		'randomAnswers'=>'0',
		'results_disabled'=>'0',
    	'enabletimercontroltotalminutes'=>'0');
    	$res = Exercise::processCreation($form,$type='1');
    	*/
		$this->aAnswer = new Answer(2);
					
		
	}
	
	public function tearDown() {		
		$this->aAnswer = null;
	}
	
	/**
	 * constructor of the class
	 *
	 * @author 	Olivier Brouckaert
	 * @param 	integer	Question ID that answers belong to
	 */
	 
	 /*
	function testAnswerConstructor() {
		$questionId = 1;
		$res = $this->aAnswer->Answer($questionId);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}  
	*/
	
	 
	
	/**
	 * creates a new answer
	 *
	 * @author Olivier Brouckaert
	 * @param string 	answer title
	 * @param integer 	0 if bad answer, not 0 if good answer
	 * @param string 	answer comment
	 * @param integer 	answer weighting
	 * @param integer 	answer position
	 * @param coordinates 	Coordinates for hotspot exercises (optional)
	 * @param integer		Type for hotspot exercises (optional)
	 */
	 
	 function testcreateAnswer() {
	 	$answer = 'test';
	 	$correct = 1;
	 	$comment ='test';
	 	$weighting = 2;
	 	$position = 1;
		$res = $this->aAnswer->createAnswer($answer,$correct,$comment,$weighting,$position,$new_hotspot_coordinates = 1, $new_hotspot_type = 1,$destination='');
		$this->assertTrue(is_null($res));
		//var_dump($res);
	} 
	
	/**
	 * duplicates answers by copying them into another question
	 * @param - integer $newQuestionId - ID of the new question
	 */
	 
	 function testduplicate() {
	 	$newQuestionId = 1;
		$res = $this->aAnswer->duplicate($newQuestionId);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	} 
	
	/**
	 * Returns a list of answers
	 * @return array	List of answers where each answer is an array of (id, answer, comment, grade) and grade=weighting
	 */
	 
	 function testgetAnswersList() {
		$res = $this->aAnswer->getAnswersList();
		$this->assertTrue(is_array($res));
		//var_dump($res);
	} 
	 
	/**
	 * Returns a list of grades
	 * @return array	List of grades where grade=weighting (?)
	 */
	 
	 function testgetGradesList() {
		$res = $this->aAnswer->getGradesList();
		$this->assertTrue(is_array($res));
		//var_dump($res);
	} 
	
	/**
	  * Returns the question type
	  * @return	integer	The type of the question this answer is bound to
	  */
	  
	  function testgetQuestionType() {
		$res = $this->aAnswer->getQuestionType();
		if(!is_null($res)){
			$this->assertTrue(is_numeric($res));
		}
		//var_dump($res);
	}
	
	/**
	 * tells if answer is correct or not
	 * @param - integer $id - answer ID
	 * @return - integer - 0 if bad answer, not 0 if good answer
	 */
	 
	 function testisCorrect() {
	 	$id = 1;
		$res = $this->aAnswer->isCorrect($id);
		if(!is_null($res)){
			$this->assertTrue(is_numeric($res));
		}
		//var_dump($res);
	}
	
	/**
	 * reads answer informations from the data base
	 */
	 
	 function testread() {
		$res = $this->aAnswer->read();
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}
	
	/**
	 * reads answer informations from the data base ordered by parameter
	 * @param	string	Field we want to order by
	 * @param	string	DESC or ASC
	 */
	 
	 function testreadOrderedBy() {
	 	global $_course;
	 	$field = 'position';
		$res = $this->aAnswer->readOrderedBy($field,$order=ASC);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}
	
	/**
	 * records answers into the data base
	 */
	 
	 function testsave() {
		$res = $this->aAnswer->save();
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}
	
	/**
	 * returns the answer title
	 * @param - integer $id - answer ID
	 * @return - string - answer title
	 */
	 
	 function testselectAnswer() {
	 	$id = 1;
		$res = $this->aAnswer->selectAnswer($id);
		if(!is_null($res)){
			$this->assertTrue(is_string($res));
		}
		//var_dump($res);
	}
	
	/**
	 * @param - integer $id - answer ID
	 * @return - bool - answer by id
	 */
	function testselectAnswerByAutoId() {
	 	$auto_id = 1;
		$res = $this->aAnswer->selectAnswerByAutoId($auto_id);
		if(!is_null($res)){
			$this->assertTrue(is_array($res));
		} else {
			$this->assertTrue(is_bool($res));
		}
		//var_dump($res);
	}
	
	/**
	 * returns the answer title from an answer's position
	 * @param - integer $id - answer ID
	 * @return - bool - answer title
	 */
	 
	 function testselectAnswerIdByPosition() {
	 	$pos = 1;
		$res = $this->aAnswer->selectAnswerIdByPosition($pos);
		if(!is_null($res)){
			$this->assertTrue(is_bool($res));
		}
		//var_dump($res);
	}
	
	/**
	 * returns the autoincrement id identificator
	 * @return - integer - answer num
	 */
	 
	 function testselectAutoId() {
	 	$id = 1;
		$res = $this->aAnswer->selectAutoId($id);
		if(!is_null($res)){
			$this->assertTrue(is_numeric($res));
		}
		//var_dump($res);
	}
	
	/**
	 * returns answer comment
	 * @param - integer $id - answer ID
	 * @return - string - answer comment
	 */
	 
	 function testselectComment() {
	 	$id = 1;
		$res = $this->aAnswer->selectComment($id);
		if(!is_null($res)){
			$this->assertTrue(is_string($res));
		}
		//var_dump($res);
	}	
	
	/**
	 * returns the question ID of the destination question
	 * @return - integer - the question ID
	 */
	 
	 function testselectDestination() {
	 	$id = 1;
		$res = $this->aAnswer->selectDestination($id);
		if(!is_null($res)){
			$this->assertTrue(is_string($res));
		}
		//var_dump($res);
	}	
	
	/**
	 * returns answer hotspot coordinates
	 * @param	integer	Answer ID
	 * @return	integer	Answer position
	 */
	 
	 function testselectHotspotCoordinates() {
	 	$id = 1;
		$res = $this->aAnswer->selectHotspotCoordinates($id);
		if(!is_null($res)){
			$this->assertTrue(is_string($res));
		}
	}
	
	/**
	 * returns answer hotspot type
	 *
	 * @author	Toon Keppens
	 * @param	integer		Answer ID
	 * @return	integer		Answer position
	 */
	
	function testselectHotspotType() {
	 	$id = 1;
		$res = $this->aAnswer->selectHotspotType($id);
		if(!is_null($res)){
			$this->assertTrue(is_string($res));
		}
	}
	
	/**
	 * returns the number of answers in this question
	 *
	 * @author - Olivier Brouckaert
	 * @return - integer - number of answers
	 */
	 
	 function testselectNbrAnswers() {
		$res = $this->aAnswer->selectNbrAnswers();
		if(!is_null($res)){
			$this->assertTrue(is_numeric($res));
		}
	}
	 
	/**
	 * returns answer position
	 *
	 * @author - Olivier Brouckaert
	 * @param - integer $id - answer ID
	 * @return - integer - answer position
	 */
	 
	 function testselectPosition() {
	 	$id = 1;
		$res = $this->aAnswer->selectPosition($id);
		if(!is_null($res)){
			$this->assertTrue(is_numeric($res));
		}
	}
	
	/**
	 * returns the question ID which the answers belong to
	 *
	 * @author - Olivier Brouckaert
	 * @return - integer - the question ID
	 */
	 
	 function testselectQuestionId() {
		$res = $this->aAnswer->selectQuestionId();
		if(!is_null($res)){
			$this->assertTrue(is_numeric($res));
		}
	}
	
	/**
	 * returns answer weighting
	 *
	 * @author - Olivier Brouckaert
	 * @param - integer $id - answer ID
	 * @return - integer - answer weighting
	 */
	
	function testselectWeighting() {
		$id = 1;
		$res = $this->aAnswer->selectWeighting($id);
		if(!is_null($res)){
			$this->assertTrue(is_numeric($res));
		}
	}
	
	 /**
	 * updates an answer
	 *
	 * @author Toon Keppens
	 * @param	string	Answer title
	 * @param	string	Answer comment
	 * @param	integer	Answer weighting
	 * @param	integer	Answer position
	 */
	 
	 function testupdateAnswers() {
		$answer = '';
		$comment = '';
		$weighting=2;
		$position=1;
		$destination='0@@0@@0@@0';
		$res = $this->aAnswer->updateAnswers($answer,$comment,$weighting,$position,$destination);
		if(!is_null($res)){
			$this->assertTrue(is_numeric($res));
		}
	}
	
	/**
	 *  clears $new_* arrays
	 */
	 
	 function testcancel() {
		$res = $this->aAnswer->cancel();
		$this->assertTrue(is_null($res));
		//var_dump($res);
	} 
}
?>
