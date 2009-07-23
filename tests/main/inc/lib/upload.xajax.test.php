<?php
/*
 * error generate 
 * Fatal error: Call to undefined 
 * function uploadprogress_get_info() in 
 * /var/www/dokeossvn186/main/inc/lib/upload.xajax.php 
 * on line 17
 *
 */
/*  
require_once(api_get_path(LIBRARY_PATH).'upload.xajax.php');
require_once api_get_path(LIBRARY_PATH).'xajax/xajax.inc.php';	

//require_once('main/inc/lib/xajax/xajax.inc.php');


class TestUpdateProgress extends UnitTestCase {

	public function TestUpdateProgress1(){
		$this->UnitTestCase('Update Progress test');
	}
	public function  testUpdateProgress(){
		$div_id='';
		$upload_id='';
		$waitAfterupload=false;
		//$res1=uploadprogress_get_info($upload_id);
		$res = updateProgress($div_id, $upload_id, $waitAfterupload);

		$this->assertTrue($res);
	}
}*/
?>
