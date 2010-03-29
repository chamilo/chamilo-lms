<?php

class TestFillBlanksClass extends UnitTestCase {
	
	public $fFillBlanks;
	
	public function TestFillBlanksClass() {
		$this->UnitTestCase('');
	}
	
	public function setUp() {
		$this->fFillBlanks = new FillBlanks();			
	}
	
	public function tearDown() {		
		$this->fFillBlanks = null;
	}
		
	/**
	 * function which redifines Question::createAnswersForm
	 * @param the formvalidator instance
	 */
	 /*
	function testcreateAnswersForm() {
		$form = new FormValidator('introduction_text');
		$res = $this->fFillBlanks->createAnswersForm($form);
		$this->assertTrue(is_null($res));
		var_dump($res);
	}*/
	
	/**
	 * abstract function which creates the form to create / edit the answers of the question
	 * @param the formvalidator instance
	 */
	/* 
	function testprocessAnswersCreation() {
		global $charset;
		$form = new FormValidator('introduction_text');
		$res = $this->fFillBlanks->processAnswersCreation($form);
		$this->assertTrue(is_null($res));
		var_dump($res);
	}*/

}
?>
