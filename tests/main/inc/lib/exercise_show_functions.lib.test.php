<?php

class TestExerciseShowFunctions extends UnitTestCase {
	
	/**
	 * Shows the answer to a fill-in-the-blanks question, as HTML
	 * @param string    Answer text
	 * @param int       Exercise ID
	 * @param int       Question ID
	 * @return void
	 */
	
	function testdisplay_fill_in_blanks_answer() {
		$answer= 'test';
		$id=1;
		$questionId=1;
		ob_start();
		$res = ExerciseShowFunctions::display_fill_in_blanks_answer($answer,$id,$questionId);
		ob_end_clean();
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}
	
	/**
	 * Shows the answer to a free-answer question, as HTML
	 * @param string    Answer text
	 * @param int       Exercise ID
	 * @param int       Question ID
	 * @return void
	 */
	 
	function testdisplay_free_answer() {
		$answer= 'test';
		$id=1;
		$questionId=1;
		ob_start();
		$res = ExerciseShowFunctions::display_free_answer($answer,$id,$questionId);
		ob_end_clean();
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}
	
	/**
	 * Displays the answer to a hotspot question
	 * @param int $answerId
	 * @param string $answer
	 * @param string $studentChoice
	 * @param string $answerComment
	 * @return void
	 */
	 
	 function testdisplay_hotspot_answer() {
	 	$answerId = 1;
		$answer= 'testanswer';
		$studentChoice='testchoise';
		$answerComment='testcomment';
		ob_start();
		$res = ExerciseShowFunctions::display_hotspot_answer($answerId, $answer, $studentChoice, $answerComment);
		ob_end_clean();
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}
	
	/**
	 * Display the answers to a multiple choice question
	 * @param integer Answer type
	 * @param integer Student choice
	 * @param string  Textual answer
	 * @param string  Comment on answer
	 * @param string  Correct answer comment
	 * @param integer Exercise ID
	 * @param integer Question ID
	 * @param boolean Whether to show the answer comment or not
	 * @return void
 	 */
 	 
	 function testdisplay_unique_or_multiple_answer() {
	 	global $feedback_type, $_course;
	 	$answerType = 1;
		$studentChoice='testchoise';
		$answer= 'testanswer';
		$answerComment='testcomment';
		$answerCorrect='testcorrect';
		$id = 1;
		$questionId = 1;
		$ans=true;
		ob_start();
		$res = ExerciseShowFunctions::display_unique_or_multiple_answer($answerType, $studentChoice, $answer, $answerComment, $answerCorrect, $id, $questionId, $ans);
		ob_end_clean();
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}
	
	/**
	 * This function gets the comments of an exercise
	 *
	 * @param int $id
	 * @param int $question_id
	 * @return str the comment
	 */
	 
	 function testget_comments() {
		$id = 1;
		$question_id = 1;
		ob_start();
		$res = ExerciseShowFunctions::get_comments($id,$question_id);
		ob_end_clean();
		$this->assertTrue(is_null($res));
		//var_dump($res);
	} 
	
	 function testsend_notification() {
		$arrques = 'test';
		$arrans = 'test';
		$to = 'test';
		$res = ExerciseShowFunctions::send_notification($arrques, $arrans, $to);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	} 	
}
?>
