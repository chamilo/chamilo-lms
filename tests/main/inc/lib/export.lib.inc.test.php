<?php
require_once(api_get_path(LIBRARY_PATH).'export.lib.inc.php');
require_once(api_get_path(LIBRARY_PATH).'document.lib.php');

Mock::generate('DocumentManager');

class TestExport extends UnitTestCase {
	
	/** Test about export csv using class document manager 
	 * @author Arthur portugal
	 * To can test and show the var_dump is necesary comment inside the class DocumentManager in the file document.lib.php the word "exit()", 
	 * because "exit" not permit show the result.  
	 */
    function testExportTableCsv() {
        $docman = new MockDocumentManager();
		$data = array();
		$filename = 'export';
		$res=Export::export_table_csv($data,$filename);
        $docman->expectOnce('DocumentManager::file_send_for_download',array($filename,true,$filename.'.csv'));
		$this->assertTrue(is_null($res));
        //var_dump($docman);
        //var_dump($res);
    }
 
 	function testExportTableXls() {
 		$docman = new MockDocumentManager();
		$data = array();
		$filename = 'export';
		$res=Export::export_table_xls($data,$filename);
        $docman->expectOnce('DocumentManager::file_send_for_download',array($filename,true,$filename.'.xls'));
		$this->assertTrue(is_null($res));
        //var_dump($docman);
        //var_dump($export);
 	}
 
 	function testExportTableXml() {
 		$docman = new MockDocumentManager();
		$data = array();
		$filename = 'export';
		$item_tagname = 'item';
		$wrapper_tagname = null;
		$encoding=null;
		$res=Export::export_table_xml($data,$filename,$item_tagname,$wrapper_tagname,$encoding);
 		$docman->expectOnce('DocumentManager::file_send_for_download',array($filename,true,$filename.'.xml'));
		$this->assertTrue(is_null($res));
		//var_dump($docman);
        //var_dump($export);
 	}
 
 	function testExportComplexTableXml() {
 		$docman = new MockDocumentManager();
		$data = array();
		$filename = 'export';
		$wrapper_tagname=null;
 		$encoding='ISO-8859-1';
 		$res=Export::export_complex_table_xml($data,$filename,$wrapper_tagname,$encoding);
 		$docman->expectOnce('DocumentManager::file_send_for_download',array($filename,true,$filename.'.xml'));
		$this->assertTrue(is_null($res));
		//var_dump($docman);
 	}
 
  	function testExportComplexTableXmlHelper() {
  		$data = array();
		$level=1;
		$res=Export::_export_complex_table_xml_helper($data,$level);
		$this->assertTrue(is_string($res));
		//var_dump($res);
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
 		global $error_msg, $error_no, $db, $archiveRepositorySys, $archiveRepositoryWeb, $appendCourse, $appendMainDb, $archiveName,
 				 $_configuration, $_course, $TABLEUSER, $TABLECOURSUSER, $TABLECOURS, $TABLEANNOUNCEMENT;
 		
		$exportedCourseId='';
		$res=makeTheBackup($exportedCourseId);
		$this->assertTrue(is_bool($res));
		//var_dump($res);		
 	}
}	
?>
