<?php
require_once(api_get_path(LIBRARY_PATH).'import.lib.php');

class TestImport extends UnitTestCase {
	
	function testCsvToArray(){
		$filename='';
		$res=Import::csv_to_array($filename);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}
}
?>
