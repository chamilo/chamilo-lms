<?php
/** To can run this test you need change the line 17 in upload.xajax.php by:
 * $ul_info = array();
 *	if(function_exists('uploadprogress_get_info'))
 *	{
 *		$ul_info = uploadprogress_get_info($upload_id);
 *	}
 */

require_once(api_get_path(LIBRARY_PATH).'upload.xajax.php');
require_once api_get_path(LIBRARY_PATH).'xajax/xajax.inc.php';
require_once api_get_path(LIBRARY_PATH).'xajax/xajaxResponse.inc.php';	

	

class TestUpdateXajax extends UnitTestCase {

	function testUpdateProgress(){
		$div_id='';
		$upload_id='';
		$res = updateProgress($div_id, $upload_id, $waitAfterupload=false);
		$this->assertTrue($res);
	}
}
?>
