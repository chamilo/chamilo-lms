<?php

require_once(api_get_path(SYS_CODE_PATH).'exercice/question.class.php');
require_once(api_get_path(SYS_CODE_PATH).'exercice/exercise.class.php');
require_once(api_get_path(SYS_CODE_PATH).'exercice/unique_answer.class.php');
require_once(api_get_path(SYS_CODE_PATH).'exercice/multiple_answer.class.php');
require_once(api_get_path(SYS_CODE_PATH).'exercice/multiple_answer_combination.class.php');
require_once(api_get_path(SYS_CODE_PATH).'exercice/fill_blanks.class.php');
require_once(api_get_path(SYS_CODE_PATH).'exercice/freeanswer.class.php');
require_once(api_get_path(SYS_CODE_PATH).'exercice/hotspot.class.php');
require_once(api_get_path(SYS_CODE_PATH).'exercice/matching.class.php');
require_once(api_get_path(SYS_CODE_PATH).'exercice/hotspot.class.php');

class TestScormClasses extends UnitTestCase {

	public $sScormAnswerFillInBlanks;
	public $sScormAnswerFree;
	public $sScormAnswerHotspot;
	public $sScormAnswerMatching;
	public $sScormAnswerMultipleChoice;
	public $sScormAnswerTrueFalse;
	public $sScormQuestion;

	public function __construct() {
	   $this->UnitTestCase('SCORM exercises export library - main/exercice/export/scorm/scorm_classes.test.php');
	}

	public function setUp() {
		$this->sScormAnswerFillInBlanks = new ScormAnswerFillInBlanks(1);
		$this->sScormAnswerFree = new ScormAnswerFree(1);
		$this->sScormAnswerHotspot = new ScormAnswerHotspot(1);
		$this->sScormAnswerMatching = new ScormAnswerMatching(1);
		$this->sScormAnswerMultipleChoice = new ScormAnswerMultipleChoice(1);
		$this->sScormAnswerTrueFalse = new ScormAnswerTrueFalse(1);
		$this->sScormQuestion = new ScormQuestion(1);
	}

	public function tearDown() {
		$this-> sScormAnswerFillInBlanks = null;
		$this-> sScormAnswerFree = null;
		$this-> sScormAnswerHotspot = null;
		$this-> sScormAnswerMatching = null;
		$this-> sScormAnswerMultipleChoice = null;
		$this-> sScormAnswerTrueFalse = null;
		$this-> sScormQuestion = null;
	}


//Class sScormAnswerFillInBlanks

	/**
     * Export the text with missing words.
     *
     * As a side effect, it stores two lists in the class :
     * the missing words and their respective weightings.
     */

	function testexport() {
		$res= $this->sScormAnswerFillInBlanks->export();
		if(!is_null($res)){
		$this->assertTrue(is_array($res));
		}
        //var_dump($res);
	}

//Class sScormAnswerFree

	/**
     * Export the text with missing words.
     *
     * As a side effect, it stores two lists in the class :
     * the missing words and their respective weightings.
     *
     */
	function testexportsScormAnswerFree() {
		$res= $this->sScormAnswerFree->export();
		if(!is_null($res)){
		$this->assertTrue(is_array($res));
		}
        //var_dump($res);
	}

//Class sScormAnswerHotspot

	/**
	 * Returns the javascript code that goes with HotSpot exercises
	 * @return string	The JavaScript code
	 */

	function testexportsScormAnswerHotspot() {
		$res= $this->sScormAnswerHotspot->export();
		if(!is_null($res)){
		$this->assertTrue(is_array($res));
		}
        //var_dump($res);
	}

		function testgetjsheadersScormAnswerHotspot() {
		$res= $this->sScormAnswerHotspot->get_js_header();
		if(!is_null($res)){
		$this->assertTrue(is_string($res));
		}
        //var_dump($res);
	}

	//Class sScormAnswerMatching

	/**
     * Export the question part as a matrix-choice, with only one possible answer per line.
     * @author Amand Tihon <amand@alrj.org>
     */

	function testexportsScormAnswerMatching() {
		$res= $this->sScormAnswerMatching->export();
		if(!is_null($res)){
		$this->assertTrue(is_array($res));
		}
        //var_dump($res);
	}

//Class sScormAnswerMultipleChoice

	/**
	 * Return HTML code for possible answers
     */

	function testexportsScormAnswerMultipleChoice() {
		$res= $this->sScormAnswerMultipleChoice->export();
		if(!is_null($res)){
		$this->assertTrue(is_array($res));
		}
        //var_dump($res);
	}

//Class sScormAnswerTrueFalse

	/**
     * Return the XML flow for the possible answers.
     * That's one <response_lid>, containing several <flow_label>
     */

	function testexportsScormAnswerTrueFalse() {
		$res= $this->sScormAnswerTrueFalse->export();
		if(!is_null($res)){
		$this->assertTrue(is_array($res));
		}
        //var_dump($res);
	}

//Class sScormQuestion

	function testcreateAnswersFormsScormQuestion() {
		$form = '';
		$res= $this->sScormQuestion->createAnswersForm($form);
		if(!is_null($res)){
		$this->assertTrue(is_bool($res));
		}
        //var_dump($res);
	}

	/**
     * Return the XML flow for the possible answers.
     * That's one <response_lid>, containing several <flow_label>
     */

	/*function testexportsScormQuestion() {
		$res= $this->sScormQuestion->export();
		if(!is_null($res)){
		$this->assertTrue(is_array($res));
		}
        //var_dump($res);
	}*/

 	/**
     * Returns an HTML-formatted question
     */
     /*
	function testgetQuestionHTMLsScormQuestion() {
		$res= $this->sScormQuestion->getQuestionHTML();
		if(!is_null($res)){
		$this->assertTrue(is_array($res));
		}
        //var_dump($res);
	}*/

	/**
     * Return the JavaScript code bound to the question
     */
     /*
     function testgetQuestionJSsScormQuestion() {
		$res= ScormQuestion::getQuestionJS();
		if(!is_null($res)){
		$this->assertTrue(is_array($res));
		}
        //var_dump($res);
	}*/

	function testprocessAnswersCreationJSsScormQuestion() {
		$form = '';
		$res= $this->sScormQuestion->processAnswersCreation($form);
		if(!is_null($res)){
		$this->assertTrue(is_bool($res));
		}
        //var_dump($res);
	}

	 /**
	 * Include the correct answer class and create answer
	 */

	 function testsetAnswersCreationJSsScormQuestion() {
		$res= $this->sScormQuestion->setAnswer();
		if(!is_null($res)){
		$this->assertTrue(is_bool($res));
		}
        //var_dump($res);
	}
}
?>
