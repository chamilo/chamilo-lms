<?php
require_once(api_get_path(LIBRARY_PATH).'export.lib.inc.php');
require_once(api_get_path(LIBRARY_PATH).'document.lib.php');
//require_once('/tests/simpletest/mock_objects.php');

//Mock::generate('Export');
Mock::generate('DocumentManager');

class TestExport extends UnitTestCase {
    function testExportTableCsv() {
        //$DocumentManager = &new DocumentManager();
        //$export = &new MockExport();
        //$export->expectOnce(export_table_csv,file_send_for_download);
        $docman = new MockDocumentManager();

		$data = array();
		$filename = 'export';
		$this->export = new Export();
		$res=$this->export->export_table_csv($data,$filename);
        
        $docman->expectOnce('DocumentManager::file_send_for_download',array($filename,true,$filename.'.csv'));
		$this->assertTrue(is_object($this->export));
        var_dump($docman);
        //var_dump($export);
    }
}
	 /*
	public function testexport_table_csv() {
		$data=array();
		$filename = 'export';
		$file = api_get_path(SYS_ARCHIVE_PATH).uniqid('').'.csv';
		$handle = @fopen($file, 'a+');
		$res=$this->xport->export_table_csv($handle);
		$this->assertTrue(is_object($handle));
		
		@fclose($handle);				
		DocumentManager :: file_send_for_download($file, true, $filename.'.csv');	
		exit();
		var_dump($handle);
		*/
		
	 


	

?>
