<?php
require_once(api_get_path(LIBRARY_PATH).'course.lib.php');

class TestSurvey extends UnitTestCase {

	public $smanager;
	public $squestion;
	public $syesno;
	public $multiplechoice;
	public $personality;
	public $multipleresponse;
	public function TestSurvey() {

	$this->UnitTestCase('');

	}
	public function setUp() {				
		$this->smanager = new survey_manager();
		$this->squestion = new question();
		$this->syesno = new yesno();
		$this->smultiplechoice = new multiplechoice();
		$this->spersonality = new personality();
		$this->smultipleresponse = new multipleresponse();		
	}

	public function tearDown() {		
		$this-> smanager = null;
		$this-> squestion = null;
		$this-> syesno = null;
		$this->smultiplechoice = null;
		$this->personality = null;
		$this->multipleresponse = null;							
	}
	
	
	public function testStoreSurvey() {
		global $_user,$cidReq;
		$values = array(
						  'survey_code' => 'Survey1',
						  'survey_title' => '<p>Survey</p>',
						  'survey_subtitle' => '',
						  'survey_language' => 'spanish',
						  'start_date' => '2010-01-19',
						  'end_date' => '2010-01-29',
						  'survey_introduction' => '',
						  'survey_thanks' =>  '',
						  'survey_type' =>  '0',
						  'parent_id' =>  '0', 
						  'submit_survey' => ''		
					   );	
		$res = $this->smanager->store_survey($values);
		$this->assertTrue($res);
		$this->assertTrue(is_array($res));				
	}

	
	public function testGetSurvey() {			   
		$course_code = 'COURSETEST';			   											 	
		$survey_id=1;
		$res3 = $this->smanager->get_survey($survey_id,0,$course_code);
		$this->assertTrue(is_array($res3));		
	}

	public function testStoreSharedSurvey() {
		global $_user,$cidReq;
		$values = array(
						  'survey_code' => 'Survey1',
						  'survey_title' => '<p>Survey</p>',
						  'survey_subtitle' => 'Survey subtitle',
						  'survey_language' => 'spanish',
						  'start_date' => '2010-01-19',
						  'end_date' => '2010-01-29',
						  'survey_introduction' => 'introduction',
						  'survey_thanks' =>  '',
						  'survey_type' =>  '1',
						  'parent_id' =>  '1', 
						  'submit_survey' => ''		
					   );	
		$res = $this->smanager->store_shared_survey($values);
		$this->assertTrue($res);
		//var_dump($res);
	}

	//Build the form

	public function testQuestionCreateForm() {
		global $charset;
		global $survey_data;
		$form_content = array();
		$res = $this->squestion->create_form($form_content);
		$this->assertTrue(is_string($res));
	}

	public function testQuestionRenderForm() {
		ob_start();
		$this->squestion->render_form();
		ob_end_clean();
		$this->assertNotNull($this->squestion->html);
		//var_dump($res);
	}
	
	public function testYesNoCreateForm() {
		$form_content=array();
		$res1 = $this->syesno->create_form($form_content);
		$this->assertNull($res1);
	}

	public function testMultipleChoiceCreateForm() {		
		$form_content=array();
		$res2 = $this->smultiplechoice->create_form($form_content);
		$this->assertNull($res2);		
	}
	

	public function testPersonalityCreateForm() {
		$form_content=array();
		$this->spersonality->create_form($form_content);
		$this->assertNotNull($this->spersonality->html);
		$this->assertTrue($this->spersonality->html);
	}

	public function testMultipleResponseCreateForm() {
		$form_content=array();
		$this->smultipleresponse->create_form($form_content);
		$this->assertNotNull($this->smultipleresponse->html);
		$this->assertTrue($this->smultipleresponse->html);
	}
	
	public function testQuestionRenderQuestion() {
		ob_start();
		$form_content=array();
		$res = $this->squestion->render_question($form_content);
		$this->assertNull($res);
		$this->assertTrue(is_null($res));
		ob_end_clean();
	}
	
	public function testMultipleChoiseRenderQuestion() {
		ob_start();
		$form_content=array();
		$answers=array();
		$this->smultiplechoice->render_question($form_content,$answers);
		$this->assertNull($this->smultiplechoice->html);
		ob_end_clean();
	}
	
	public function testYesNoRenderQuestion() {
		ob_start();
		$form_content=array();
		$answers=array();
		$this->syesno->render_question($form_content,$answers);
		$this->assertNull($this->syesno->html);
		ob_end_clean();
	}

	public function testPersonalityRenderQuestion() {
		ob_start();
		$form_content=array();
		$answers=array();
		$this->spersonality->render_question($form_content,$answers);
		$this->assertNull($this->spersonality->html);
		$this->assertFalse($this->spersonality->html);
		ob_end_clean();
	}

	public function testAddRemoveButtons() {
		$form_content = array();
		$res = $this->squestion->add_remove_buttons($form_content);
		$this->assertTrue($res);
		//var_dump($res);
	}

	//save the survey
	 
	 public function testCopySurvey() {
		$parent_survey = Database::escape_string($parent_survey);
		$new_survey_id = '1';
		$res = $this->smanager->copy_survey($parent_survey,$new_survey_id);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res);
		//var_dump($res);

	}


	public function testGetCompleteSurveyStructure() {
		$survey_id='';
		$shared=0;
		$res = $this->smanager->get_complete_survey_structure($survey_id, $shared);
		$this->assertNull($res);
		$this->assertTrue($res=== null);
		//var_dump($res);
	}

	public function testIconQuestion() {
		$type='open';
		$res = $this->smanager->icon_question($type);
		if(is_bool($res)) {
		$this->assertTrue($res ===false);
		$this->assertTrue(is_bool($res));
		}else{
		$this->assertTrue($res);
		$this->assertTrue(is_string($res));
		}
		//var_dump($res);
	}
	
	public function testSaveQuestion() {
		$form_content=array();
		$res = $this->smanager->save_question($form_content);
		$this->assertTrue($res);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	public function testSaveSharedQuestion() {
		$form_content=array('');
		$survey_data=array('survey_share');
		$res = $this->smanager->save_shared_question($form_content,$survey_data);
		$this->assertTrue($res);
		//var_dump($res);
	}

	public function testSaveQuestionOptions() {
		$form_content=array();
		$survey_data=array('survey_share');
		$res = $this->smanager->save_question_options($form_content,$survey_data);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	public function testSaveSharedQuestionOptions() {
		$form_content=array();
		$survey_data=array();
		$res = $this->smanager->save_shared_question_options($form_content,$survey_data);
		$this->assertTrue(is_null($res));
		$this->assertNull($res);
		//var_dump($res);
	}
 
	//get the survey

	public function testGetPeopleWhoFilledSurvey() {
		$survey_id=1;
		$all_user_info=false;
		$survey_data = survey_manager::get_survey($survey_id);
		$result = $this->smanager->get_people_who_filled_survey($survey_id,false);
		$this->assertTrue(is_array($result));
		//var_dump($result);
	}

	public function testGetQuestion() {
		$question_id=1;
		$res = $this->smanager->get_question($question_id,false);
		$this->assertTrue($res);
		//var_dump($res);
		//var_dump($result);
	}

	public function testGetQuestions() {
		$survey_id =1;
		$res= $this->smanager->get_questions($survey_id);
		$this->assertNull($res);
		//var_dump($res);
	}
	
	//move the survey
 
	public function testMoveSurveyQuestion() {
		$direction='moveup';
		$survey_question_id=1;
		$survey_id=1;
		$res = $this->smanager->move_survey_question($direction,$survey_question_id,$survey_id);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	//epmty the survey
	
	public function testEmpty_survey() {
		$survey_id=null;
		$res = $this->smanager->empty_survey($survey_id);
		$this->assertTrue($res);
		//var_dump($res);
	}
	
	//functions delete
	 
	 public function testHandleAction() {
		$form_content = array('');
		$res = $this->squestion->handle_action($form_content);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}
	 
		public function testDeleteAllSurveyQuestions() {
		$survey_id=1;
		$shared=false;
		$result = $this->smanager->delete_all_survey_questions($survey_id,$shared);
		$this->assertTrue(is_null($result));
		//var_dump($result);
	}

	public function testDeleteSurveyQuestion() {
		$survey_id =1;
		$question_id=01;
		$shared=false;
		$result = $this->smanager->delete_survey_question($survey_id,$question_id);
		$this->assertTrue(is_null($result));
		//var_dump($result);
		//var_dump($res);
	}
	
	public function testDeleteSharedSurveyQuestion() {
		$survey_id=1;
		$question_id=01;
		$res = $this->smanager->delete_shared_survey_question($survey_id,$question_id);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}
	
		public function testDeleteSurvey() {
		$survey_id=1;
		$shared=false;
		$course_code=001;
		$res = $this->smanager->delete_survey($survey_id, $shared, $course_code);
		$this->assertTrue($res);
		//var_dump($res);
	}
	
	public function testDeleteAllSurveyQuestionsOptions() {
		$survey_id=1;
		$shared=false;
		$result = $this->smanager->delete_all_survey_questions_options($survey_id,$shared);
		$this->assertTrue($result);
		//var_dump($result);
	}

	public function testDeleteSurveyQuestionOption() {
		$survey_id=1;
		$question_id=01;
		$shared=false;
		$result = $this->smanager->delete_survey_question_option($survey_id,$question_id,$shared);
		if(is_bool($result))
		$this->assertTrue(is_bool($result));
		$this->assertTrue($result === true || $result===false);
		$this->assertTrue($result);	
		//var_dump($result);
	}

	public function testDeleteAllSurveyAnswers() {
		$survey_id=1;
		$res = $this->smanager->delete_all_survey_answers($survey_id);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res);
		$this->assertTrue($res === true || $res === false);
		//var_dump($res);
	}
	
	//Contest the answer
	 
	public function testUpdateSurveyAnswered() {
		global $user;
		$survey_code = 'Survey1';
		$survey_id = '1';
		$result = $this->smanager->update_survey_answered($survey_id, $user, $survey_code);
		$this->assertTrue(is_null($result));
		//var_dump($result);
	}


/**
 * This functon only is added to the end of the test and the end of the files in the all test.
 */
/*	public function testDeleteCourse() {
		global $cidReq;			
		$resu = CourseManager::delete_course($cidReq);				
	}*/
 
}

?>
