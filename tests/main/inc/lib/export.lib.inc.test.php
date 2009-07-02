<?php
require_once(api_get_path(LIBRARY_PATH).'export.lib.inc.php');

class TestExport extends UnitTestCase {
	
	function testexport_table_csv() {
		$data='';
		$res=Export::export_table_csv($data);
		$this->assertTrue($res);
		var_dump($res);
	}	 


	
}
?>
