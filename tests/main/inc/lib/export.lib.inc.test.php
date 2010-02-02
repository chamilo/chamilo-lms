<?php
require_once(api_get_path(LIBRARY_PATH).'export.lib.inc.php');
require_once(api_get_path(LIBRARY_PATH).'document.lib.php');

class TestExport extends UnitTestCase {

	/** Test about export csv using class document manager
	 * @author Arthur portugal
	 * To can test and show the var_dump is necesary comment inside the class DocumentManager in the file document.lib.php the word "exit()",
	 * because "exit" not permit show the result.
	 */
	
/**
 * the simpletest has a conflict with the headers because, the simpletest
 * framework's, send first the prints and then the headers, but in this function
 * the headers are sending first.
 */	 
 
 
    function testExportTableCsv() {
       $data = array();
	   if (!headers_sent()) {	
	       $res = Export::export_table_csv($data, $filename = 'export');
	   }
	   if(is_null($res)) {
			$this->assertFalse($res);
		} else {
			$this->assertTrue(is_string($res));
		}
    }
    

 	function testExportTableXls() {
		$data = array();
		$filename = 'export';
		if (!headers_sent()) {
			$res=Export::export_table_xls($data,$filename);
		}
		
		if(is_null($res)) {
			$this->assertFalse(is_bool($res));
		} else {
			$this->assertTrue(is_bool($res));
		}
 	}

 	function testExportTableXml() {
		$data = array();
		$filename = 'export';
		$item_tagname = 'item';
		$wrapper_tagname = null;
		$encoding=null;
		if (!headers_sent()) {
			$res=Export::export_table_xml($data,$filename,$item_tagname,
										  $wrapper_tagname,$encoding);
		}
		
		if(is_null($res)) {
			$this->assertFalse($res);
		} else {
			$this->assertTrue(is_bool($res));
		}
 	}
 
 	function testExportComplexTableXml() {
		$data = array();
		$filename = 'export';
		$wrapper_tagname=null;
 		$encoding='ISO-8859-1';
 		if (!headers_sent()) {
	 		$res=Export::export_complex_table_xml($data,$filename,$wrapper_tagname,
	 		                                      $encoding);
 		}
 		
 		if(is_null($res)) {
			$this->assertFalse(is_string($res));
		} else {
			$this->assertTrue(is_bool($res));
		}
 	}

  	function testExportComplexTableXmlHelper() {
  		$data = array();
		$level=1;
		if (!headers_sent()) {
			$res=Export::_export_complex_table_xml_helper($data,$level);

		}
		if(is_null($res)) {
			$this->assertFalse($res);
		} else {
			$this->assertTrue(is_string($res));
		}
			
 	}
 	
 	function testBackupDatabase() {
 		$link='';
 		$db_name='';
 		$structure='';
 		$donnees='';
 		$format = 'SQL';
 		$whereSave = '.';
 		$insertComplet = '';
 		$verbose = false;
 		global $error_msg, $error_no;
 		$res=backupDatabase($link, $db_name, $structure, $donnees);
 		$this->assertTrue(is_bool($res));
 		//var_dump($res);
 	}
 	/* DEPRECATED
 	function testCopydir() {
 		$origine='';
 		$destination='';
 		$verbose = '';
 		$res =Export::copydir($origine, $destination, $verbose = false);
 		$this->assertTrue($res);
 		var_dump($verbose);
 	}*/

 	function testmakeTheBackup() {
 		global $error_msg, $error_no, $db, $archiveRepositorySys, 
 		       $archiveRepositoryWeb, $appendCourse, $appendMainDb, $archiveName,
 			   $_configuration, $_course, $TABLEUSER, $TABLECOURSUSER, 
 			   $TABLECOURS, $TABLEANNOUNCEMENT;
		$exportedCourseId='';
		$res=makeTheBackup($exportedCourseId);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
 	}
}
?>
