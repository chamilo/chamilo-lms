<?php
require_once(api_get_path(LIBRARY_PATH).'surveymanager.lib.php');

class TestSurveyManager extends UnitTestCase {
	
	public $smanager;
	public function TestSurveyManager() {
	 	$this->UnitTestCase('Survey Manager Manipulation tests');
	 }
	 
	public function setUp() {
 	 	$this-> smanager = new SurveyManager();
	 }
	 
	public function tearDown() {
	 	$this->smanager = null;
	 }
		
	function testattach_survey() {
		$surveyid='';
		$newsurveyid='';
		$db_name='';
		$curr_dbname='';
		$res=$this->smanager->attach_survey($surveyid,$newsurveyid,$db_name,$curr_dbname);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}
	
	function testcreate_course_survey_rel() {
		$cidReq='';
		$survey_id='';
		$table_course='';
		$table_course_survey_rel='';
		$res=$this->smanager->create_course_survey_rel($cidReq,$survey_id,$table_course,$table_course_survey_rel);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}
	
	function testcreate_group() {
		$survey_id='';
		$group_title=' ';
		$introduction='';
		$table_group='';
		$res=$this->smanager->create_group($survey_id,$group_title,$introduction,$table_group);
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}
	
	function testcreate_question() {
		$gid='';
		$surveyid='';
		$qtype='';
		$caption='';
		$alignment='';
		$answers='';
		$open_ans='';
		$answerT='';
		$answerD='';
		$rating='';
		$curr_dbname='';
		$res=$this->smanager->create_question($gid,$surveyid,$qtype,$caption,$alignment,$answers,$open_ans,$answerT,$answerD,$rating,$curr_dbname);
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}
	
	function testcreate_survey() {
		$surveycode='';
		$surveytitle='';
		$surveysubtitle='';
		$author='';
		$survey_language='';
		$availablefrom='';
		$availabletill='';
		$isshare='';
		$surveytemplate='';
		$surveyintroduction='';
		$surveythanks='';
		$table_survey='';
		$table_group='';
		$res=$this->smanager->create_survey($surveycode,$surveytitle, $surveysubtitle, $author, $survey_language, $availablefrom, $availabletill,$isshare, $surveytemplate, $surveyintroduction, $surveythanks, $table_survey, $table_group);
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}
	
	function testcreate_survey_attach() {
		$surveycode='';
		$surveytitle='';
		$surveysubtitle='';
		$author='';
		$survey_language='';
		$availablefrom='';
		$availabletill='';
		$isshare='';
		$surveytemplate='';
		$surveyintroduction='';
		$surveythanks='';
		$table_survey='';
		$table_group='';
		$res=$this->smanager->create_survey_attach($surveycode,$surveytitle, $surveysubtitle, $author, $survey_language, $availablefrom, $availabletill,$isshare, $surveytemplate, $surveyintroduction, $surveythanks, $table_survey, $table_group);
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}
	
	function testcreate_survey_in_another_language() {
		global $_course;
		$id='';
		$lang='';
		$res=$this->smanager->create_survey_in_another_language($id, $lang);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}
	
	function testdelete_group() {
		$group_id='';
		$res=$this->smanager->delete_group($group_id);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}
	
	function testdelete_survey() {
		$survey_id='';
		$res=$this->smanager->delete_survey($survey_id);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}
	
	function testdisplay_imported_group() {
		$sid='';
		$table_group='';
		$table_question='';
		ob_start();
		$res=$this->smanager->display_imported_group($sid,$table_group,$table_question);
		$this->assertTrue(is_null($res));
		ob_end_clean();
		//var_dump($res);
	}
	
	function testget_all_datas() {
		global $_course;
		$id='';
		$res=$this->smanager->get_all_datas($id);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}
	
	function testget_author() {
		$db_name='';
		$survey_id='';
		$res=$this->smanager->get_author($db_name,$survey_id);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}
	
	function testget_data() {
		$id='';
		$field='';
		$res=$this->smanager->get_data($id, $field);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}
	
	function testget_groupname() {
		$db_name='';
		$gid='';
		$res=$this->smanager->get_groupname($db_name,$gid);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}
	
	function testget_question_data() {
		$qid='';
		$curr_dbname='';
		$res=$this->smanager->get_question_data($qid,$curr_dbname);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}
	
	function testget_question_type() {
		$questionid='';
		$res=$this->smanager->get_question_type($questionid);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}
	
	function testget_questions_move() {
		$curr_dbname='';
		$question1=array("caption"=> $row['caption'], "qid" => $row['qid'],"sortby" => $row['sortby']);
		$res=$this->smanager->get_questions_move($curr_dbname);
		$this->assertTrue(is_array($question1));
		//var_dump($question1);
	}
	
	function testget_status() {
		global $_user;
		$table_user = Database::get_main_table(TABLE_MAIN_USER);
		$sqlm = "SELECT  status FROM  $table_user WHERE user_id = '".mysql_real_escape_string($_user['user_id'])."'";
		$resm = api_sql_query($sqlm,__FILE__,__LINE__);
		$objm=@mysql_fetch_object($resm);
		$ss = $objm->status ;
		$res=$this->smanager->get_status();
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}
	
	function testget_survey_author() {
		$user_table = Database :: get_main_table(TABLE_MAIN_USER);
		$authorid = Database::escape_string($authorid);
		$sql_query = "SELECT * FROM $user_table WHERE user_id='$authorid'";
		$res = api_sql_query($sql_query, __FILE__, __LINE__);
		$firstname=@mysql_result($res,0,'firstname');
		$res=$this->smanager->get_survey_author($authorid);
		$this->assertTrue(is_bool($firstname));
		//var_dump($firstname);
	}
	
	function testget_survey_code() {
		$table_survey='';
		$survey_code='';
		$res=$this->smanager->get_survey_code($table_survey,$survey_code);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}
	
	function testget_survey_list() {
		ob_start();
		$res=$this->smanager->get_survey_list();
		$this->assertTrue(is_null($res));
		ob_end_clean();
		//var_dump($res);
	}
	
	function testget_surveyid() {
		$db_name='';
		$group_id='';
		$res=$this->smanager->get_surveyid($db_name,$group_id);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}
	
	function testget_surveyname() {
		$db_name='';
		$sid='';
		$res=$this->smanager->get_surveyname($db_name,$sid);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}
	
	function testget_surveyname_display() {
		$sid='';
		$res=$this->smanager->get_surveyname_display($sid);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}
	
	function testgetUserAnswersDetails() {
		$id_userAnswers='';
		$params='';
		$res=$this->smanager->getUserAnswersDetails($id_userAnswers, $params);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}
	
	function testimport_existing_question() {
		$surveyid='';
		$qids='';
		$table_group='';
		$table_question='';
		$yes='';
		$res=$this->smanager->import_existing_question($surveyid,$qids,$table_group,$table_question,$yes);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}
	
	function testimport_group() {
		$sid='';
		$gids='';
		$db_name='';
		$curr_dbname='';
		$res=$this->smanager->import_group($sid,$gids,$db_name,$curr_dbname);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}
	
	function testimport_question() {
		$surveyid='';
		$qids='';
		$table_group='';
		$table_question='';
		$db_name='';
		$cidReq='';
		$yes='';
		$res=$this->smanager->import_question($surveyid,$qids,$table_group,$table_question,$db_name,$cidReq,$yes);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}
	
	function testimport_questions() {
		$import_type='';
		$ids='';
		$res=$this->smanager->import_questions($import_type, $ids);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}
	
	function testinsert_existing_groups() {
		$sid='';
		$gids='';
		$table_group='';
		$table_question='';
		$res=$this->smanager->insert_existing_groups($sid,$gids,$table_group,$table_question );
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}
		
	function testinsert_groups() {
		$sid='';
		$newgid='';
		$gids='';
		$table_group='';
		$table_question='';
		$res=$this->smanager->insert_groups($sid,$gids,$table_group,$table_question );
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}
	
	function testinsert_into_group() {
		$survey_id='';
		$group_title='';
		$introduction='';
		$tb='';
		$res=$this->smanager->insert_into_group($survey_id,$group_title,$introduction,$tb);
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}
	
	function testinsert_old_groups() {
		$sid='';
		$gids='';
		$table_group='';
		$table_question='';
		$db_name='';
		$cidReq='';
		$res=$this->smanager->insert_old_groups($sid,$gids,$table_group,$table_question,$db_name,$cidReq);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}
	
	function testinsert_questions() {
		$sid='';
		$newgid='';
		$gid='';
		$table_group='';
		$res=$this->smanager->insert_questions($sid,$newgid,$gid,$table_group);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}
	
	function testlistAnswers() {
		$qid='';
		$res=$this->smanager->listAnswers($qid);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	} 
	
	function testlistGroups() {
		$id_survey='';
		$fields = '*';
		$res=$this->smanager->listGroups($id_survey, $fields);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}
		
	function testlistQuestions() {
		$id_survey='';
		$fields = '*';
		$res=$this->smanager->listQuestions($id_survey, $fields);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}
	
	function testlistUsers() {
		$survey_id='';
		$dbname='';
		$res=$this->smanager->listUsers($survey_id, $dbname);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}
	
	function testmove_question() {
		$direction='';
		$qid='';
		$sort='';
		$curr_dbname='';
		$res=$this->smanager->move_question($direction,$qid,$sort,$curr_dbname);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}
		
	function testno_of_question() {
		$db_name='';
		$gid='';
		$res=$this->smanager->no_of_question($db_name,$gid);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}
	
	function testpick_author() {
		$survey_id='';
		$res=$this->smanager->pick_author($survey_id);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}
	
	 function testpick_surveyname() {
	 	$sid='';
	 	$res=$this->smanager->pick_surveyname($sid);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	 }
	
	function testques_id_group_name() {
		$qid='';
		$res=$this->smanager->ques_id_group_name($qid);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}
	
	function testquestion_import() {
		$surveyid='';
		$qids='';
		$db_name='';
		$curr_dbname='';
		$res=$this->smanager->question_import($surveyid,$qids,$db_name,$curr_dbname);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}
	
	function testselect_group_list() {
		$survey_id='';
		$seleced_groupid=''; 
		$extra_script='';
		$res=$this->smanager->select_group_list($survey_id, $seleced_groupid='', $extra_script='');
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}
	
	function testselect_survey_list() {
		$seleced_surveyid='';
		$extra_script='';
		$res=$this->smanager->select_survey_list($seleced_surveyid='', $extra_script='');
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}
	
	 function testupdate_group() {
	 	$groupid='';
	 	$surveyid='';
	 	$groupnamme='';
	 	$introduction='';
	 	$curr_dbname='';
	 	$res=$this->smanager->update_group($groupid,$surveyid,$groupnamme,$introduction,$curr_dbname);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	 }
	 
	 function testupdate_question() {
	 	$qid='';
	 	$qtype='';
	 	$caption='';
	 	$alignment='';
	 	$answers='';
	 	$open_ans='';
	 	$curr_dbname='';
	 	$res=$this->smanager->update_question($qid,$qtype,$caption,$alignment,$answers,$open_ans,$curr_dbname);
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	 }
	 
	 function testupdate_survey() {
	 	$surveyid='';
	 	$surveycode='';
	 	$surveytitle='';
	 	$surveysubtitle='';
	 	$author='';
	 	$survey_language='';
	 	$availablefrom='';
	 	$availabletill='';
	 	$isshare='';
	 	$surveytemplate='';
	 	$surveyintroduction='';
	 	$surveythanks='';
	 	$cidReq='';
	 	$table_course='';
	 	$res=$this->smanager->update_survey($surveyid,$surveycode,$surveytitle, $surveysubtitle, $author, $survey_language, $availablefrom, $availabletill,$isshare, $surveytemplate, $surveyintroduction, $surveythanks, $cidReq,$table_course);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	 }
}
	

class TestSurveyTree extends UnitTestCase {

	var $surveylist;	 
	var $plainsurveylist;
	var $numbersurveys;
		 
	
/*	//is a construct it can not test 
	function test__construct() {
		$res=SurveyTree::__construct();
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}*/
	
	
	function testcreateList() {
		$list='';
		$res=SurveyTree::createList($list);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}
	
	function testget_children() {
		$list='';
		$id='';
		$res=SurveyTree::get_children($list,$id);
		$this->assertTrue(is_array($res));
		//var_dump($res);	
	}
	
	function testget_last_children_from_branch() {
		$list='';
		$res=SurveyTree::get_last_children_from_branch($list);
		$this->assertTrue(is_array($res));
		//var_dump($res);	
	}
	
	function testgetParentId() {
		$id='';
		$res=SurveyTree::getParentId($id);
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}
	
	function testlastSibling() {
		$id='';
		$res=SurveyTree::lastSibling($id);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}
	
	function testnextSibling() {
		$id='';
		$res=SurveyTree::nextSibling($id);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}
}
?>
