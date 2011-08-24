<?php
require_once(api_get_path(LIBRARY_PATH).'export.lib.inc.php');
require_once(api_get_path(LIBRARY_PATH).'document.lib.php');

/** Test about export csv using class document manager
 * @author Arthur portugal
 * To can test and show the var_dump is necesary comment inside the class
 * DocumentManager in the file document.lib.php the word "exit()", because
 * "exit" not permit show the result.
 */
class TestExport extends UnitTestCase {

    public function __construct() {
        $this->UnitTestCase('Export library - main/inc/lib/export.lib.inc.test.php');
    }

    /**
     * Checks the export_table_csv method.
     * @todo check that a new file is created in api_get_path(SYS_ARCHIVE_PATH)
     */
    function testExportTableCsv() {
        $data = array();
        // can only be tested if headers were not sent
        ob_start();
       	$res = Export::export_table_csv($data, $filename = 'export');
		$this->assertFalse($res);
		ob_end_clean();
    }


 	function testExportTableXls() {
		$data = array();
		$filename = 'export';
        ob_start();
		$res=Export::export_table_xls($data,$filename);
		$this->assertFalse($res);
        ob_end_clean();
 	}

 	function testExportTableXml() {
		$data = array();
		$filename = 'export';
		$item_tagname = 'item';
		$wrapper_tagname = null;
		$encoding=null;
        ob_start();
		$res=Export::export_table_xml($data,$filename,$item_tagname,
									  $wrapper_tagname,$encoding);
        $this->assertFalse($res);
        ob_end_clean();
 	}

 	function testExportComplexTableXml() {
		$data = array();
		$filename = 'export';
		$wrapper_tagname=null;
 		$encoding='ISO-8859-1';
        ob_start();
 		$res=Export::export_complex_table_xml($data,$filename,
	 		                                        $wrapper_tagname,$encoding);
        $this->assertFalse($res);
        ob_end_clean();
 	}

  	function testExportComplexTableXmlHelper() {
  		$data = array();
		$level=1;
        ob_start();
		$res=Export::_export_complex_table_xml_helper($data,$level);
        $this->assertTrue(is_string($res));
        ob_end_clean();
  	}
}
