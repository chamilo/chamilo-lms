<?php
class TestFreeanswer extends UnitTestCase {
	
	/**
	 * function which redifines Question::createAnswersForm
	 * @param the formvalidator instance
	 */
	 
	function testcreateAnswersForm () {
		$form = new FormValidator('exercise_admin', 'post', api_get_self().'?exerciseId='.$_GET['exerciseId']);
		$res =FreeAnswer::createAnswersForm($form);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}	
	
	/**
	 * abstract function which creates the form to create / edit the answers of the question
	 * @param the formvalidator instance
	 */
	
	function testprocessAnswersCreation () {
		$form = new FormValidator('exercise_admin', 'post', api_get_self().'?exerciseId='.$_GET['exerciseId']);
		$res =FreeAnswer::processAnswersCreation($form);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}
	
}
?>
