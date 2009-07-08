<?php
require_once(api_get_path(LIBRARY_PATH).'fileUpload.lib.php');
require_once(api_get_path(LIBRARY_PATH).'document.lib.php');
Mock::generate('DocumentManager');


/** To can test this functions you need to comment "die ('can not create file')"  line 1571 from fileUpload.lib.php
 *  @author aportugal arthur.portugal@dokeos.com
 */

class TestFileUpload extends UnitTestCase {
	
		function testAddAllDocumentsInFolderToDatabase() {
			$docman = new MockDocumentManager();
			$_course='';
			$user_id='';
			$base_work_dir='';
			$path = $base_work_dir;
			$handle=opendir($path);
			$file=readdir($handle);
			$safe_file=replace_dangerous_char($file);
			$res=add_all_documents_in_folder_to_database($_course,$user_id,$base_work_dir,$current_path='',$to_group_id=0);
        	$docman->expectOnce('DocumentManager::get_document_id',array($_course, $current_path.'/'.$safe_file));
			$this->assertTrue(is_object($docman));
	        //var_dump($docman);
		}
		
		function testAddDocument() {
			global $charset;
			$_course['dbName']='';
			$path='';
			$filetype='';
			$filesize='';
			$title='';
			$res=add_document($_course,$path,$filetype,$filesize,$title);
			$this->assertTrue(is_bool($res));
			//var_dump($_course);
		}
		
		function testAddExtOnMime() {
			$fileName='';
			$fileType='';
			$res=add_ext_on_mime($fileName,$fileType);
			$this->assertTrue(is_string($res));
			//var_dump($res);
		}
		
		function testApiReplaceLinksInHtml() {
			$upload_path='';
			$doc_url = $_GET['file'];
			$full_file_name = api_get_path(SYS_COURSE_PATH).api_get_course_path().'/upload/blog/'.$doc_url;
			$res=api_replace_links_in_html($upload_path,$full_file_name);
			$this->assertTrue(is_null($res));
			//var_dump($res);
		}
		
		function testApiReplaceLinksInString() {
			$upload_path='';
			$buffer=ob_get_contents();
			$res=api_replace_links_in_string($upload_path,$buffer);
			$this->assertTrue(is_string($res));
			//var_dump($res);
		}
		
		function testApiReplaceParameter() {
			$count = 0;
			$matches = array();
			$href_list=array();
			$file_path_list[] = $matches[1];
			$upload_path='';
			$replaceWhat[$count] = $href_list[$count];
			/** To can test this function you need to comment "die ('can not create file')" 
		 *  $res return void
		 */$replaceBy[$count] = " $param_name=\"" . $file_path_list[$count] . "\" target =\"_top\"";
			$replaceBy[$count] = $replaceWhat[$count];
			$buffer = str_replace($replaceWhat, $replaceBy, $buffer);
			$param_name="src";
			$res=api_replace_parameter($upload_path, $buffer, $param_name="src");
			$this->assertTrue(is_string($res));
			//var_dump($res);
		}
	
		function testBuildMissingFilesForm() {
			
			$_course['path']='';
			$courseDir   = $_course['path']."/document";
			$sys_course_path = api_get_path(SYS_COURSE_PATH);
			$base_work_dir = $sys_course_path.$courseDir;
			$missing_files = check_for_missing_files($base_work_dir.$new_path);
			$upload_path='';
			$file_name = '';
			$res=build_missing_files_form($missing_files,$upload_path,$file_name);
			$this->assertTrue(is_string($res));
			//var_dump($res);
		}
		
		function testCheckForMissingFiles() {
			$file='';
			$res=check_for_missing_files($file);
			$this->assertTrue(is_bool($res));
		}
		
		function testCleanUpFilesInZip() {
			$p_event='';
			$p_header['filename']='';
			$res=clean_up_files_in_zip($p_event, &$p_header);
			$this->assertTrue(is_numeric($res));
			//var_dump($res);
		}
		
		function testCleanUpPath(&$path) {
			$path_array = explode('/',$path);
			$path = implode('/',$path_array);
			$res=clean_up_path(&$path);
			$this->assertTrue(is_numeric($res));
			//var_dump($res);
		}
		
		/** To can test this function you need to comment "die ('can not create file')" 
		 *  $res return void/
		 * 
		 */
		 
		function testCreateLinkFile() {
			$filePath='';
			$url='';
			$res= create_link_file($filePath, $url);			
			$this->assertTrue(is_null($res));
			//var_dump($res);
		}
		
		function testCreateUnexistingDirectory()  {
			$_course='';
			$user_id='';
			$to_group_id='';
			$to_user_id='';
			$base_work_dir='';
			$desired_dir_name='';
			$res= create_unexisting_directory($_course,$user_id,$to_group_id,$to_user_id,$base_work_dir,$desired_dir_name);		
			$this->assertTrue(is_bool($res));
		}
		
		function testDirTotalSpace() {
			$dirPath='/var/www/path';
			$res= dir_total_space($dirPath);			
			$this->assertTrue(is_numeric($res));
			//var_dump($res);
		}
		
		function testDisableDangerousFile($filename) {
			$filename = php2phps($filename);
			$filename = htaccess2txt($filename);
			$res= disable_dangerous_file($filename);			
			$this->assertTrue(is_string($res));
			//var_dump($res);
		}
		
		
		//function documents_total_space() 
		
		
		
		
		
		
}
?>
