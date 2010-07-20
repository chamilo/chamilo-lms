<?php
require_once(api_get_path(LIBRARY_PATH).'import.lib.php');

class TestImport extends UnitTestCase {

    public function __construct(){
        $this->UnitTestCase('Import library - main/inc/lib/import.lib.test.php');
    }
	function testCsvToArray(){
		$filename='';
		$res=Import::csv_to_array($filename);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}
}
?>
