<?php
require_once(api_get_path(LIBRARY_PATH).'events.lib.inc.php');

class TestEvents extends UnitTestCase {
	
	function testCreateEventExercice() {
		global $_user, $_cid, $_configuration;
		$exo_id='';
		$res=create_event_exercice($exo_id);
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}
	
	function testEventAccessCourse() {
		global $_configuration;
		global $_user;
		global $_cid;
		global $TABLETRACK_ACCESS;
		global $TABLETRACK_LASTACCESS;
		$res=event_access_course();
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}
	
	function testEventAccessTool() {
		global $_configuration;
		global $_cid;
		global $TABLETRACK_ACCESS;
		global $_configuration;
		global $_course;
		global $TABLETRACK_LASTACCESS;
		$res=event_access_tool();
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}
	
	function testEventDownload() {
		global $_configuration;
		global $_user;
		global $_cid;
		global $TABLETRACK_DOWNLOADS;	
		$doc_url='';
		$res=event_download();
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}

	function testEventLink() {
		global $_configuration;
		global $_user;
		global $_cid;
		global $TABLETRACK_LINKS;
		$link_id='';
		$res=event_link();
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}
		
	function testEventLogin() {
		global $_configuration;
		global $_user;
		global $TABLETRACK_LOGIN;
		$res=event_login();
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}
	
	function testEventOpen() {
		global $_configuration;
		global $TABLETRACK_OPEN;
		$res=event_open();
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}
	
	function testEventSystem() {
		global $_configuration;
		global $_user;
		global $TABLETRACK_DEFAULT;
		$event_type = Database::escape_string($event_type);
		$event_value_type = Database::escape_string($event_value_type);
		$event_value = Database::escape_string($event_value);
		$res=event_system($event_type, $event_value_type,$event_value);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}
	
	
	function testEventUpload() {
		global $_configuration;
		global $_user;
		global $_cid;
		global $TABLETRACK_UPLOADS;
		$doc_id='';
		$res=event_upload($doc_id);
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}
	
	function testExerciseAttempt() {
		$score = Database::escape_string($score);
		$answer = Database::escape_string($answer);
		$quesId = Database::escape_string($quesId);
		$exeId = Database::escape_string($exeId);
		$j = Database::escape_string($j);
		global $_configuration, $_user, $_cid;
		$res=exercise_attempt($score,$answer,$quesId,$exeId,$j);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}
	
	function testExerciseAttemptHotspot() {
		global $_configuration, $_user, $_cid;
		$exe_id='';
		$question_id='';
		$answer_id='';
		$correct='';
		$coords='';
		$res=exercise_attempt_hotspot($exe_id, $question_id, $answer_id, $correct, $coords);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}
	
	function testUpdateEventExercice() {
		$exeid='';
		$exo_id='';
		$score='';
		$weighting='';
		$session_id='';
		$duration='';
		$res=update_event_exercice($exeid,$exo_id, $score, $weighting,$session_id,$learnpath_id=0,$learnpath_item_id=0, $duration);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}
}
?>
