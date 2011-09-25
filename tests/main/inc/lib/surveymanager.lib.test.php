<?php
require_once api_get_path(LIBRARY_PATH).'surveymanager.lib.php';
//This class is deprecated
/*
class TestSurveyManager extends UnitTestCase {

	public function TestSurveyManager(){
		$this->UnitTestCase('Surveys library - main/inc/lib/surveymanager.lib.test.php');
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
		$res=SurveyManager::create_survey($surveycode,$surveytitle, $surveysubtitle, $author, $survey_language, $availablefrom, $availabletill,$isshare, $surveytemplate, $surveyintroduction, $surveythanks, $table_survey, $table_group);
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
		$res=SurveyManager::create_survey_attach($surveycode,$surveytitle, $surveysubtitle, $author, $survey_language, $availablefrom, $availabletill,$isshare, $surveytemplate, $surveyintroduction, $surveythanks, $table_survey, $table_group);
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}

	function testattach_survey() {
		$surveyid='';
		$newsurveyid='';
		$db_name='';
		$curr_dbname='';
		$res=SurveyManager::attach_survey($surveyid,$newsurveyid,$db_name,$curr_dbname);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	function testcreate_course_survey_rel() {
		$cidReq='';
		$survey_id='';
		$table_course='';
		$table_course_survey_rel='';
		$res=SurveyManager::create_course_survey_rel($cidReq,$survey_id,$table_course,$table_course_survey_rel);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	function testdisplay_imported_group() {
		$sid='';
		$table_group='';
		$table_question='';
		ob_start();
		$res=SurveyManager::display_imported_group($sid,$table_group,$table_question);
		$this->assertTrue(is_null($res));
		ob_end_clean();
		//var_dump($res);
	}

	function testget_all_datas() {
		global $_course;
		$id='';
		$res=SurveyManager::get_all_datas($id);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	function testget_author() {
		$db_name='';
		$survey_id='';
		$res=SurveyManager::get_author($db_name,$survey_id);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	function testget_data() {
		$id='';
		$field='';
		$res=SurveyManager::get_data($id, $field);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	function testget_groupname() {
	    $db_name='';
		$gid='0011';
		$res=SurveyManager::get_groupname($db_name,$gid);
		if(!is_null($res))$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	function testget_question_type() {
		$questionid='';
		$res=SurveyManager::get_question_type($questionid);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	function testget_status() {
		global $_user;
		$table_user = Database::get_main_table(TABLE_MAIN_USER);
		$sqlm = "SELECT  status FROM  $table_user WHERE user_id = '".Database::escape_string($_user['user_id'])."'";
		$resm = Database::query($sqlm);
		$objm=@Database::fetch_object($resm);
		$ss = $objm->status ;
		$res=SurveyManager::get_status();
		if(!$res = null ){
		$this->assertTrue(is_null($res));
		}else{
		$this->assertTrue(is_string($res));
		}
		//var_dump($res);
	}

	function testget_survey_author() {
		$user_table = Database :: get_main_table(TABLE_MAIN_USER);
		$authorid = Database::escape_string($authorid);
		$sql_query = "SELECT * FROM $user_table WHERE user_id='$authorid'";
		$res = Database::query($sql_query);
		$firstname=@Database::result($res,0,'firstname');
		$res=SurveyManager::get_survey_author($authorid);
		$this->assertTrue(is_null($firstname));
		$this->assertNull($res);
		//var_dump($firstname);
	}

	function testget_survey_code() {
		$table_survey='';
		$survey_code='';
		$res=SurveyManager::get_survey_code($table_survey,$survey_code);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	function testget_survey_list() {
		ob_start();
		$res=SurveyManager::get_survey_list();
		$this->assertTrue(is_null($res));
		ob_end_clean();
		//var_dump($res);
	}

	function testget_surveyid() {
		$db_name='';
		$group_id='';
		$res=SurveyManager::get_surveyid($db_name,$group_id);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	function testget_surveyname() {
		$db_name='';
		$sid='';
		$res=SurveyManager::get_surveyname($db_name,$sid);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	function testget_surveyname_display() {
		$sid='';
		$res=SurveyManager::get_surveyname_display($sid);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	function testimport_questions() {
		$import_type='';
		$ids='';
		$res=SurveyManager::import_questions($import_type, $ids);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	function testinsert_into_group() {
		$survey_id='';
		$group_title='';
		$introduction='';
		$tb='';
		$res=SurveyManager::insert_into_group($survey_id,$group_title,$introduction,$tb);
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}

	function testno_of_question() {
		$db_name='';
		$gid='';
		$res=SurveyManager::no_of_question($db_name,$gid);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	function testpick_author() {
		$survey_id='';
		$res=SurveyManager::pick_author($survey_id);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	 function testpick_surveyname() {
	 	$sid='';
	 	$res=SurveyManager::pick_surveyname($sid);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	 }

	function testselect_group_list() {
		$survey_id='';
		$seleced_groupid='';
		$extra_script='';
		$res=SurveyManager::select_group_list($survey_id, $seleced_groupid='', $extra_script='');
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	function testupdate_group() {
	 	$groupid='';
	 	$surveyid='';
	 	$groupnamme='';
	 	$introduction='';
	 	$curr_dbname='';
	 	$res=SurveyManager::update_group($groupid,$surveyid,$groupnamme,$introduction,$curr_dbname);
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
	 	$res=SurveyManager::update_question($qid,$qtype,$caption,$alignment,$answers,$open_ans,$curr_dbname);
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
	 	$res=SurveyManager::update_survey($surveyid,$surveycode,$surveytitle, $surveysubtitle, $author, $survey_language, $availablefrom, $availabletill,$isshare, $surveytemplate, $surveyintroduction, $surveythanks, $cidReq,$table_course);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	 }
	function testdelete_group() {
		$group_id='';
		$res=SurveyManager::delete_group($group_id);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	function testdelete_survey() {
		$survey_id='';
		$res=SurveyManager::delete_survey($survey_id);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}
}
*/
class TestSurveyTree extends UnitTestCase {

	var $surveylist;
	var $plainsurveylist;
	var $numbersurveys;
	public function TestSurveTree(){
		$this->UnitTestCase('Survey trees library - main/inc/lib/surveymanager.lib.test.php');
	}
	function testgetParentId() {
		$id='';
		$res=SurveyTree::getParentId($id);
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}
	function testcreateList() {
		$list=array();
		$res=SurveyTree::createList($list);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}
}
?>
