<?php

require_once(api_get_path(SYS_CODE_PATH).'/exercice/answer.class.php');
require_once(api_get_path(SYS_CODE_PATH).'/exercice/question.class.php');
require_once(api_get_path(SYS_CODE_PATH).'/exercice/exercise.class.php');
require_once(api_get_path(SYS_CODE_PATH).'/exercice/hotspot.class.php');
require_once(api_get_path(SYS_CODE_PATH).'/exercice/unique_answer.class.php');
require_once(api_get_path(SYS_CODE_PATH).'/exercice/multiple_answer.class.php');
require_once(api_get_path(SYS_CODE_PATH).'/exercice/matching.class.php');
require_once(api_get_path(SYS_CODE_PATH).'/exercice/freeanswer.class.php');
require_once(api_get_path(SYS_CODE_PATH).'/exercice/fill_blanks.class.php');

class TestQti2 extends UnitTestCase {

	public $qIms2Question;
	public $qImsAnswerFillInBlanks;
	public $qImsAnswerFree;
	public $qImsAnswerHotspot;
	public $qImsAnswerMatching;
	public $qImsAnswerMultipleChoice;

	public function __construct() {
        $this->UnitTestCase('QTI2 library - main/exercice/export/qti2/qti2_classes.test.php');
	}

	public function setUp() {
		$this->qIms2Question = new Ims2Question();
		$this->qImsAnswerFillInBlanks = new ImsAnswerFillInBlanks(1);
		$this->qImsAnswerFree = new ImsAnswerFree(1);
		$this->qImsAnswerHotspot = new ImsAnswerHotspot(1);
		$this->qImsAnswerMatching = new ImsAnswerMatching(1);
		$this->qImsAnswerMultipleChoice = new ImsAnswerMultipleChoice(1);
	}

	public function tearDown() {
		$this-> qIms2Question = null;
		$this-> qImsAnswerFillInBlanks = null;
		$this-> qImsAnswerFree = null;
		$this-> qImsAnswerHotspot = null;
		$this-> qImsAnswerMatching = null;
		$this-> qImsAnswerMultipleChoice = null;
	}

//Class qIms2Question
	 /**
     * Include the correct answer class and create answer
     */

	function testsetAnswer() {
		$res=Ims2Question::setAnswer();
		if(!is_null){
			$this->assertTrue(is_bool($res));
		}
		//var_dump($res);
	}

	function testcreateAnswersForm() {
		$form = array(1);
		$res=Ims2Question::createAnswersForm($form);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	function testprocessAnswersCreation() {
		$form = array(1);
		$res=Ims2Question::processAnswersCreation($form);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}


//Class qImsAnswerFillInBlanks

 	 /**
     * Export the text with missing words.
     *
     *
     */
	function testimsExportResponses() {
		$questionIdent = array(1);
		$questionStatment = array(1);
		$res=$this->qImsAnswerFillInBlanks->imsExportResponses($questionIdent, $questionStatment);
			if(!is_null){
				$this->assertTrue(is_bool($res));
			}
		//var_dump($res);
	}

	function testimsExportResponsesDeclaration() {
		$questionIdent = array(1);
		$res=$this->qImsAnswerFillInBlanks->imsExportResponsesDeclaration($questionIdent);
			if(!is_null){
				$this->assertTrue(is_bool($res));
			}
		//var_dump($res);
	}

//Class qImsAnswerFree

	/**
     * TODO implement
     * Export the question part as a matrix-choice, with only one possible answer per line.
     */

	function testImsExportResponsesqImsAnswerFree() {
		$questionIdent = array('');
		$questionStatment = array('');
		$res=$this->qImsAnswerFree->imsExportResponses($questionIdent, $questionStatment, $questionDesc='', $questionMedia='');
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	function testImsExportResponsesDeclarationqImsAnswerFree() {
		$questionIdent = array(1);
		$res=$this->qImsAnswerFree->imsExportResponsesDeclaration($questionIdent);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}


//Class qImsAnswerHotspot

	/**
     * TODO update this to match hotspots instead of copying matching
     * Export the question part as a matrix-choice, with only one possible answer per line.
     */

	function testimsExportResponsesqImsAnswerHotspot() {
		$questionIdent = array(1);
		$questionStatment = array(1);
		$res=$this->qImsAnswerHotspot->imsExportResponses($questionIdent, $questionStatment, $questionDesc='', $questionMedia='');
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}


	function testimsExportResponsesDeclarationqImsAnswerHotspot() {
		$questionIdent = array(1);
		$res=$this->qImsAnswerHotspot->imsExportResponsesDeclaration($questionIdent);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

//Class qImsAnswerMatching

    /**
     * Export the question part as a matrix-choice, with only one possible answer per line.
     */

	function testimsExportResponsesqImsAnswerMatching() {
		$questionIdent = array(1);
		$questionStatment = array(1);
		$res=$this->qImsAnswerMatching->imsExportResponses($questionIdent, $questionStatment, $questionDesc='', $questionMedia='');
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}


	function testimsExportResponsesDeclarationqImsAnswerMatching() {
		$questionIdent = array(1);
		$res=$this->qImsAnswerMatching->imsExportResponsesDeclaration($questionIdent);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

//Class qImsAnswerMultipleChoice

     /**
     * Return the XML flow for the possible answers.
     *
     */

	function testimsExportResponsesqImsAnswerMultipleChoice() {
		$questionIdent = array(1);
		$questionStatment = array(1);
		$res=$this->qImsAnswerMultipleChoice->imsExportResponses($questionIdent, $questionStatment, $questionDesc='', $questionMedia='');
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}


	function testimsExportResponsesDeclarationqImsAnswerMultipleChoice() {
		$questionIdent = array(1);
		$res=$this->qImsAnswerMultipleChoice->imsExportResponsesDeclaration($questionIdent);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}
}
?>
