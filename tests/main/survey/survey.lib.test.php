<?php
Mock::generate('Database');
Mock::generate('Display');
$config['survey']['debug'] = false;
require_once(api_get_path(LIBRARY_PATH).'add_course.lib.inc.php');

class TestSurvey extends UnitTestCase {
	
	public $smanager;
	public $squestion;
	public function TestSurvey() {
	
	$this->UnitTestCase('');
	
	}
	public function setUp() {
		$this-> smanager = new survey_manager();
		$this-> squestion = new question();
	}
	
	public function tearDown() {
		$this-> smanager = null;
		$this-> squestion = null;
	}
	/*
	public function testGetSurvey() {
		$instans = new MockDatabase();
		global $_course;
		$survey_id=1;
		$shared=1;
		$my_course_id=$_GET['course'];
		$res = $this->smanager->get_survey($survey_id,$shared);
		$my_course_info=api_get_course_info($my_course_id);
		$table_survey = Database :: get_course_table(TABLE_SURVEY, $my_course_info['dbName']);
		$sql = "SELECT * FROM $table_survey WHERE survey_id='".Database::escape_string($survey_id)."'";
		$result = api_sql_query($sql, __FILE__, __LINE__);
		$instans->expectCallCount($table_survey);
		$this->assertTrue(is_array($res));
		$this->assertFalse($result);
		//var_dump($table_survey);
		//var_dump($result);
		//var_dump($res);
	}
	
	public function testStoreSurvey(){
		$instans = new MockDatabase();
		global $_user;
		$values=array('002','003','003');
		$table_survey=Database::get_course_table(TABLE_SURVEY);
		$shared_survey_id=0;
		$instans->expectCallCount($table_survey);
		//if(!$values['survey_id'] OR !is_numeric($values['survey_id']))
		$res = $this->smanager->store_survey($values);
		$this->assertTrue($res);
		$this->assertTrue(is_array($res));
		$this->assertTrue($res);
		$this->assertTrue($table_survey);
		//var_dump($res);
		//var_dump($table_survey);
	}
	/*
	public function testStoreSharedSurvey($values){
		$instans = new MockDatabase();
		$values=array('');
		global $_user;
		global $_course;
		$table_survey=Database::get_main_table(TABLE_MAIN_SHARED_SURVEY);
		$instans->expectCallCount(Database::get_main_table(TABLE_MAIN_SHARED_SURVEY));
		if(!$values['survey_id'] OR !is_numeric($values['survey_id']) OR $values['survey_share']['survey_share'] == 'true') {
			$sql = "INSERT INTO $table_survey (code, title, subtitle, author, lang, template, intro, surveythanks, creation_date, course_code)";
			$result = api_sql_query($sql, __FILE__, __LINE__);
			$return	= Database::insert_id();
		}else{
			$sql = "UPDATE $table_survey SET";	
			$result = api_sql_query($sql, __FILE__, __LINE__);
			$return	= $values['survey_share']['survey_share'];	
		}
		$res = $this->smanager->store_shared_survey($values);		
		$this->assertTrue($res);
		//var_dump($res);
		//var_dump($table_survey);
		//var_dump($table_survey);
	}*/
	
	public function testDeleteSurvey(){
		$instans = new MockDatabase();
		$survey_id=1;
		$shared=false;
		$course_code=001;
		$table_survey= Database :: get_course_table(TABLE_SURVEY,$course_code);
		$table_survey_question_group = Database :: get_course_table(TABLE_SURVEY_QUESTION_GROUP,$course_code);
		$instans->expectOnce($table_survey);
		$res = $this->smanager->delete_survey($survey_id, $shared, $course_code);
		$this->assertTrue($res);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($table_survey);
		//var_dump($table_survey_question_group);
		//var_dump($res);
	}	
	
	public function testCopySurvey(){
		$instans = new MockDatabase();
		$parent_survey=null;
		$new_survey_id=null;
		$instans->expectCallCount(Database::get_course_table(TABLE_SURVEY));		
		$sql = "SELECT * from $table_survey_question_group " .
			   "WHERE survey_id='".$parent_survey."'";
		$result = api_sql_query($sql, __FILE__, __LINE__);
		$res = $this->smanager->copy_survey($parent_survey,$new_survey_id);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res);
		$this->assertTrue($instans);
		//var_dump($res);	
		//var_dump($result);
	}
	
	public function testEmpty_survey(){
		$instans = new MockDatabase();
		$table_survey_invitation = Database :: get_course_table(TABLE_SURVEY_INVITATION);
		$table_survey_answer = Database :: get_course_table(TABLE_SURVEY_ANSWER);
		$table_survey = Database :: get_course_table(TABLE_SURVEY);
		$survey_id=null;
		$instans->expectOnce($table_survey);
		$instans->expectCallCount(count($table_survey));
		$instans->expectCallCount($table_survey);
		$res = $this->smanager->empty_survey($survey_id);
		$this->assertTrue($res);
		$this->assertTrue(is_object($instans));
		//var_dump($res);
		//var_dump($table_survey);
	}
	
	public function testUpdateSurveyAnswered(){
		$instans = new MockDatabase();
		global $_course;
		$survey_id=1;
		$user=001;
		$survey_code=001;
		$table_survey= Database :: get_course_table(TABLE_SURVEY, $_course['db_name']);
		$table_survey_invitation 	= Database :: get_course_table(TABLE_SURVEY_INVITATION, $_course['db_name']);
		$instans->expectCallCount($table_survey);
		$instans->expectCallCount(count($table_survey_invitation));
		$sql = "UPDATE $table_survey_invitation SET answered='1' WHERE session_id='".api_get_session_id()."' AND user='".Database::escape_string($user)."' AND survey_code='".Database::escape_string($survey_code)."'";
		$res = api_sql_query($sql, __FILE__, __LINE__);
		$result = $this->smanager->update_survey_answered($survey_id, $user, $survey_code);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === false);
		//var_dump($result);
		//var_dump($res);
		
	}
	
	public function testGetCompleteSurveyStructure(){
		$survey_id='';
		$shared=0;
		$res = $this->smanager->get_complete_survey_structure($survey_id, $shared);
		$this->assertNull($res);
		$this->assertTrue($res=== null);
		//var_dump($res);
	}
	
	public function testIconQuestion(){
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
	
	public function testGetQuestion(){
		$instans = new MockDatabase();
		$question_id=01;
		$shared=false;
		$tbl_survey_question = Database :: get_course_table(TABLE_SURVEY_QUESTION);
		$table_survey_question_option = Database :: get_course_table(TABLE_SURVEY_QUESTION_OPTION);
		$sql = "SELECT * FROM $tbl_survey_question WHERE question_id='".Database::escape_string($question_id)."' ORDER BY `sort`";
		$result = api_sql_query($sql, __FILE__, __LINE__);
		$row = Database::fetch_array($result,'ASSOC');
		$res = $this->smanager->get_question($question_id,$shared);
		$this->assertTrue($res);
		$instans->expectOnce(count($res));
		//var_dump($res);
		//var_dump($result);
	}
	
	public function testGetQuestions(){
		$get_questions = new MockDatabase();
		$survey_id =1;
		$tbl_survey_question = Database :: get_course_table(TABLE_SURVEY_QUESTION);
		$table_survey_question_option = Database :: get_course_table(TABLE_SURVEY_QUESTION_OPTION);
		$sql = "SELECT * FROM $tbl_survey_question WHERE survey_id='".Database::escape_string($survey_id)."'";
		$result = api_sql_query($sql, __FILE__, __LINE__);
		$row = Database::fetch_array($result,'ASSOC');
		$res= $this->smanager->get_questions($survey_id);
		$get_questions->expectCallCount($result);
		$this->assertNull($res);
		//var_dump($res);
		//var_dump($row);
		//var_dump($get_questions);
	}
	
	public function testSaveQuestion(){
		global $survey_data;
		global $_course;
		$form_content=array('question'=>'121212');
		$res = $this->smanager->save_question($form_content['question']);
		$this->assertTrue($res);
		$this->assertTrue(is_string($res));
		//var_dump($res);
		
	}
	
	public function testSaveSharedQuestion(){
		$instans = new MockDatabase();
		global $_course;
		$form_content=array('');
		$survey_data=array('');
		$tbl_survey_question= Database :: get_main_table(TABLE_MAIN_SHARED_SURVEY_QUESTION);
		$res = $this->smanager->save_shared_question($form_content,$survey_data);
		$instans->expectOnce($tbl_survey_question);
		$this->assertTrue($res);
		$this->assertTrue($instans);
		//var_dump($res);
		//var_dump($instans);
	}
	
	public function testMoveSurveyQuestion(){
		$instans = new MockDatabase();
		$table_survey_question 	= Database :: get_course_table(TABLE_SURVEY_QUESTION);
		$direction='moveup';
		$survey_question_id=01;
		$survey_id=1;
		/*$sql = "SELECT * FROM $table_survey_question WHERE survey_id='".Database::escape_string($survey_id)."' ORDER BY sort $sort";
		$result = api_sql_query($sql, __FILE__, __LINE__);
		*/
		$res = $this->smanager->move_survey_question($direction,$survey_question_id,$survey_id);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}
	
	public function testDeleteAllSurveyQuestions(){
		$instans = new MockDatabase();
		$table_survey_question 	= Database :: get_course_table(TABLE_SURVEY_QUESTION);
		$survey_id=1;
		$shared=false;
		$sql = "DELETE from $table_survey_question WHERE survey_id='".Database::escape_string($survey_id)."'";
		$res = api_sql_query($sql, __FILE__, __LINE__);
		$result = $this->smanager->delete_all_survey_questions($survey_id,$shared);
		$instans->expectOnce($res);
		$this->assertTrue(is_null($result));
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === true || $res === false);
		//var_dump($result);
		//var_dump($res);
	}
	
	public function testDeleteSurveyQuestion(){
		$instans = new MockDatabase();
		$table_survey_question 	= Database :: get_course_table(TABLE_SURVEY_QUESTION);
		$survey_id =1;
		$question_id=01;
		$shared=false;
		$sql = "DELETE from $table_survey_question WHERE survey_id='".Database::escape_string($survey_id)."' AND question_id='".Database::escape_string($question_id)."'";
		$res = api_sql_query($sql, __FILE__, __LINE__);
		$instans->expectOnce($res);
		$result = $this->smanager->delete_survey_question($survey_id,$question_id);
		$this->assertTrue(is_null($result));
		$this->assertTrue(is_object($instans));
		$this->assertFalse($res);
		//var_dump($result);
		//var_dump($res); 
	}
	
	public function testDeleteSharedSurveyQuestion(){
		$instans = new MockDatabase();
		$survey_id=1;
		$question_id=01;
		$table_survey_question 	= Database :: get_main_table(TABLE_MAIN_SHARED_SURVEY_QUESTION);
		$table_survey_question_option 	= Database :: get_main_table(TABLE_MAIN_SHARED_SURVEY_QUESTION_OPTION);
		$res = $this->smanager->delete_shared_survey_question($survey_id,$question_id);
		$instans->expectOnce($table_survey_question);
		$this->assertTrue(is_null($res));
		$this->assertTrue(is_object($instans));
		//var_dump($res);
	}
		
	public function testSaveQuestionOptions(){
		$instans = new MockDatabase();
		$form_content=array('percentage');
		$survey_data=array('survey_share');
		$table_survey_question_option 	= Database :: get_course_table(TABLE_SURVEY_QUESTION_OPTION);
		$instans->expectOnce($table_survey_question_option);	
		$res = $this->smanager->save_question_options($form_content,$survey_data);
		$this->assertTrue(is_null($res));
		$this->assertTrue(is_object($instans));
		//var_dump($res);
		//var_dump($table_survey_question_option);
	}
	
	public function testSaveSharedQuestionOptions(){
		$instans = new MockDatabase();
		$form_content=array('answers');
		$survey_data=array('');
		$table_survey_question_option 	= Database :: get_main_table(TABLE_MAIN_SHARED_SURVEY_QUESTION_OPTION);
		$sql = "DELETE FROM $table_survey_question_option WHERE question_id = '".Database::escape_string($form_content['shared_question_id'])."'";
		$result = api_sql_query($sql, __FILE__, __LINE__);
		$instans->expectCallCount($result);
		$res = $this->smanager->save_shared_question_options($form_content,$survey_data);
		$this->assertTrue(is_bool($result));
		$this->assertTrue($result === true || $result === false);
		$this->assertTrue(is_null($res));
		$this->assertNull($res);
		//var_dump($res);
		//var_dump($result);
	}
	
	public function testDeleteAllSurveyQuestionsOptions(){
		$instans = new MockDatabase();
		$table_survey_question_option 	= Database :: get_course_table(TABLE_SURVEY_QUESTION_OPTION);
		$survey_id=1;
		$shared=false;
		$sql = "DELETE from $table_survey_question_option WHERE survey_id='".Database::escape_string($survey_id)."'";
		$res = api_sql_query($sql, __FILE__, __LINE__);
		$result = $this->smanager->delete_all_survey_questions_options($survey_id,$shared);
		$instans->expectCallCount($res);
		$this->assertTrue($result);
		$this->assertFalse($res);
		$this->assertTrue(is_bool($res));
		//var_dump($result);
		//var_dump($res);
	}

	public function testDeleteSurveyQuestionOption(){
		$instans = new MockDatabase();
		$survey_id=1;
		$question_id=01;
		$shared=false;
		$table_survey_question_option = Database :: get_course_table(TABLE_SURVEY_QUESTION_OPTION);
		$sql = "DELETE from $table_survey_question_option WHERE survey_id='".Database::escape_string($survey_id)."' AND question_id='".Database::escape_string($question_id)."'";
		$res = api_sql_query($sql, __FILE__, __LINE__);
		$instans->expectOnce($res);
		$instans->expectCallCount($res);
		$result = $this->smanager->delete_survey_question_option($survey_id,$question_id,$shared);
		if(is_bool($result))
		$this->assertTrue(is_bool($result));
		$this->assertTrue($result === true || $result===false);
		$this->assertTrue($result);
		if(is_bool($res))
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === true || $res===false);
		$this->assertFalse($res);
		//var_dump($result);
		//var_dump($res);
	}
	
	public function testDeleteAllSurveyAnswers(){
		$instans = new MockDatabase();
		$survey_id=1;
		$table_survey_answer 	= Database :: get_course_table(TABLE_SURVEY_ANSWER);
		$instans->expectCallCount($table_survey_answer);
		$res = $this->smanager->delete_all_survey_answers($survey_id);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res);
		$this->assertTrue($res === true || $res === false);
		$this->assertTrue($table_survey_answer);
		//var_dump($res);
		//var_dump($table_survey_answer);
	}
	
	public function testGetPeopleWhoFilledSurvey(){
		$instans = new MockDatabase();
		$survey_id=1;
		$all_user_info=false;
		global $_course;
		$table_survey_answer = Database :: get_course_table(TABLE_SURVEY_ANSWER, $_course['db_name']);
		$table_user	= Database :: get_main_table('user');
		$survey_data = survey_manager::get_survey($survey_id);
		$sql = "SELECT DISTINCT user FROM $table_survey_answer WHERE survey_id= '".Database::escape_string($survey_data['survey_id'])."'";
		$res = api_sql_query($sql, __FILE__, __LINE__);
		$instans->expectCallCount($table_user);
		$result = $this->smanager->get_people_who_filled_survey($survey_id,$all_user_info);
		$this->assertTrue(is_array($result));
		$this->assertNotNull($res);
		$this->assertFalse($res);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res=== true || $res === false);
		$this->assertTrue($sql);
		//var_dump($res);
		//var_dump($result);
		//var_dump($sql);
	}

	public function testCreateForm(){
		$instans = new MockDisplay();
		global $charset;
		global $survey_data;
		$tool_name = 'AddQuestion';
		$tool_name = Display::return_icon(survey_manager::icon_question(Security::remove_XSS($_GET['type'])),get_lang(ucfirst(Security::remove_XSS($_GET['type']))),array('align'=>'middle', 'height'=>'22px')).' ';
		$form_content=array('');
		$res = $this->squestion->create_form($form_content);
		$instans->expectCallCount($tool_name);
		$this->assertTrue($res);
		$this->assertTrue($tool_name);
		//var_dump($res);
		//var_dump($tool_name);
	}
	
	public function testRenderForm(){
		ob_start();
		$res = $this->squestion->render_form();
		ob_end_clean();
		$this->assertTrue(is_null($res));
		$this->assertNull($res);
		//var_dump($res);		
	}
	
	public function testHandleAction(){
		global $config;
		$form_content['answers']=array('');
		$message = 'PleaseEnterAQuestion';
		$max_answer = count($form_content['answers']);
		foreach ($_POST['delete_answer'] as $key=>$value) {
		$delete = $key;
		}
		$res = $this->squestion->handle_action($form_content);
		$this->assertTrue(is_array($res));
		$this->assertTrue(isset($form_content['answers'][$max_answer-1]));
		//var_dump($res);
	}
	
	public function testAddRemoveButtons(){
		$form_content['answers'] =array();
		$res = $this->squestion->add_remove_buttons($form_content);
		$this->assertTrue($res);
	}
	
	

	
}

?>
