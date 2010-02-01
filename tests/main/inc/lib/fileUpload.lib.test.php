<?php
/** To can run this test you need comment "die() This code is in the line 278,1377,1386,1389,1542,1547,1571 file fileUpload.lib.php
 *  @author aportugal arthur.portugal@dokeos.com
 */

require_once(api_get_path(LIBRARY_PATH).'fileUpload.lib.php');
require_once(api_get_path(LIBRARY_PATH).'document.lib.php');
require_once(api_get_path(LIBRARY_PATH).'/pclzip/pclzip.lib.php');

class TestFileUpload extends UnitTestCase {

		function testAddAllDocumentsInFolderToDatabase() {
			global $_course, $user_id;
			$base_work_dir= api_get_path(SYS_PATH).'';
			$current_path = 'courses/';
			$to_group_id =0;
			$res=add_all_documents_in_folder_to_database($_course,$user_id,$base_work_dir,$current_path,$to_group_id);
	        $this->assertTrue(is_null($res));
	        //var_dump($res);
		}

		function testAddDocument() {
			global $_course;
			$path='';
			$filetype='';
			$filesize='';
			$title='';
			$res=add_document($_course,$path,$filetype,$filesize,$title);
			if(!is_numeric($res)) :
			$this->assertTrue(is_bool($res));
			endif;
			//var_dump($res);
		}

		function testAddExtOnMime() {
			$fileName='';
			$fileType='';
			$res=add_ext_on_mime($fileName,$fileType);
			$this->assertTrue(is_string($res));
			//var_dump($res);
		}

		function testApiReplaceLinksInHtml() {
			$upload_path='/var/www/chamilo1/courses/COURSEX/upload/blog';
			$full_file_name = 'doc.php';
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
		 	*/
		 	$replaceBy[$count] = " $param_name=\"" . $file_path_list[$count] . "\" target =\"_top\"";
			$replaceBy[$count] = $replaceWhat[$count];
			$buffer = str_replace($replaceWhat, $replaceBy, $buffer);
			$param_name="src";
			$res=api_replace_parameter($upload_path, $buffer, $param_name="src");
			$this->assertTrue(is_string($res));
			//var_dump($res);
		}

		function testBuildMissingFilesForm() {
			global	$_course;
			$missing_files = array();
			$upload_path=api_get_path(SYS_COURSE_PATH).'/COURSEX/document/image';
			$file_name = 'board.jpg';
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

		function testCleanUpPath() {
			
			$path = '/var/tmp/archivo123.txt';
			//$path_array = explode('/',$path);
			//$path = implode('/',$path_array);					

			$res = clean_up_path($path);
			$this->assertTrue(is_numeric($res));

			
		}

		/** To can test this function you need to comment "die ('can not create file')"
		 *  $res return void/
		 *
		 */

		function testCreateLinkFile() {
			
			$filePath = api_get_path(SYS_PATH).'documentation/readme.html';
			$url = 'http://www.chamilo.com';
			$res= create_link_file($filePath, $url);
			
			if (!is_numeric($res)) {
				$this->assertFalse($res);
			}
			else{
				 $this->assertTrue($res);
			}		
			//var_dump($res);	
		}

		function testCreateUnexistingDirectory()  {
			global $_course, $user_id;
			$to_group_id = 1;
			$to_user_id = 1;
			$base_work_dir = api_get_path(SYS_COURSE_PATH).'COURSEX/document';
			$desired_dir_name = 'images';
			$res= create_unexisting_directory($_course,$user_id,$to_group_id,$to_user_id,$base_work_dir,$desired_dir_name);
			
			if(is_string($res)){
				$this->assertTrue(is_string($res));
				$this->assertTrue($res);	
			} else {
				$this->assertTrue(is_bool($res));
				$this->assertFalse(is_string($res));
			} 
 			//var_dump($res);
			}


		function testDirTotalSpace() {
			$dirPath= api_get_path(SYS_COURSE_PATH).'COURSEX/document/';
			$res= dir_total_space($dirPath);
			$this->assertTrue(is_numeric($res));
			$this->assertTrue($res);
			//var_dump($res);
		}

		function testDisableDangerousFile() {
			$filename = 'index.php';
			$res= disable_dangerous_file($filename);
			$this->assertTrue(is_string($res));
			//var_dump($res);
		}


		function testdocuments_total_space() {
			$to_group_id='0';
			$res= documents_total_space($to_group_id);
			if(!is_null($res)):
			$this->assertTrue(is_numeric($res));
			endif;
			//var_dump($res);
		}

		function testenough_size() {
			$fileSize='';
			$dir='';
			$maxDirSpace='';
			$res= enough_size($fileSize, $dir, $maxDirSpace);
			$this->assertTrue(is_bool($res));
			//var_dump($res);
		}

		function enough_space() {
			$file_size='';
			$max_dir_space='';
			$res= enough_space($file_size, $max_dir_space);
			$this->assertTrue(is_bool($res));
			//var_dump($res);
		}

		function testfilter_extension() {
			$filename='index.php';
			$res= filter_extension($filename);
			$this->assertTrue(is_numeric($res));			
		}

		function testget_document_title() {
			$name='';
			$res= get_document_title($name);
			$this->assertTrue(is_string($res));
			//var_dump($res);
		}

		function testget_levels() {
			$filename='readme.txt';
			$res= get_levels($filename);
			$this->assertTrue(is_numeric($res));
			//var_dump($res);
		}

		function testhandle_uploaded_document() {
			global $_course;
			$uploaded_file='';
			$base_work_dir='';
			$upload_path='';
			$user_id='01';
			$to_group_id=0;
			$to_user_id=NULL;
			$maxFilledSpace='';
			$unzip=0;
			$what_if_file_exists='';
			$output=true;
			$clean_name = disable_dangerous_file($clean_name);
			$where_to_save = $base_work_dir.$upload_path;
			$new_name = unique_name($where_to_save, $clean_name);
			$new_file_path = $upload_path.$new_name;
			$clean_name = disable_dangerous_file($clean_name);
			$file_path = $upload_path.$clean_name;
			ob_start();
			$res= handle_uploaded_document($_course,$uploaded_file,$base_work_dir,$upload_path,$user_id,$to_group_id=0,$to_user_id=NULL,$maxFilledSpace='',$unzip=0,$what_if_file_exists='',$output=true);
			$this->assertTrue(is_null($res));
			ob_end_clean();
			//var_dump($res);
		}

		function testhtaccess2txt() {
			$filename = 'readme.txt';
			$res= htaccess2txt($filename);
			$this->assertTrue(is_string($res));
			//var_dump($res);
		}

		function testitem_property_update_on_folder() {
			global $_course, $user_id;
			$path=api_get_path(SYS_COURSE_PATH).'COURSEX/document/license.txt';
			$res= item_property_update_on_folder($_course,$path,$user_id);
			$this->assertTrue(is_null($res));
			//var_dump($res);
		}

		function testmove_uploaded_file_collection_into_directory() {
			global $_course;
			$uploaded_file_collection=array();
			$base_work_dir='';
			$missing_files_dir='';
			$user_id='';
			$to_group_id='';
			$to_user_id='';
			$max_filled_space='';
			$res= move_uploaded_file_collection_into_directory($_course, $uploaded_file_collection, $base_work_dir, $missing_files_dir,$user_id,$to_group_id,$to_user_id,$max_filled_space);
			$this->assertTrue(is_null($res));
			//var_dump($res);
		}

		function testphp2phps () {
			$fileName = 'index.php';
			$res= php2phps($fileName);
			$this->assertTrue(is_string($res));
			//var_dump($res);
		}

		function testprocess_uploaded_file() {
			$uploaded_file='';
			ob_start();
			$res= process_uploaded_file($uploaded_file);
			ob_end_clean();
			$this->assertTrue(is_bool($res));
			
		}

		function testreplace_img_path_in_html_file() {
			global $_course;
			$originalImgPath='';
			$newImgPath='';
			$htmlFile='file:///var/www/chamilo/documentation/credits.html';
			$res= replace_img_path_in_html_file($originalImgPath, $newImgPath, $htmlFile);
			$this->assertTrue(is_null($res));
			//var_dump($res);
		}

		function testsearch_img_from_html() {
			$imgFilePath = array();
			$htmlFile= 'file:///var/www/chamilo/documentation/credits.html';
			$res= search_img_from_html($htmlFile);
			if(is_array($res)){
			$this->assertTrue(is_array($res));
			} else {
			$this->assertTrue(is_null($res));
			}
			//var_dump($res);
		}

		function testset_default_settings() {
			global $_course, $_configuration, $cidReq;
			global $default_visibility, $dbTable;
			$dbTable = Database::get_course_table(TABLE_DOCUMENT);
			$upload_path = api_get_path(SYS_COURSE_PATH).'COURSEX/document';
			$filetype="file";
			$filename = substr($filename,0,-1);
			$res= set_default_settings($upload_path,$filename,$filetype);
			$this->assertTrue(is_null($res));
			//var_dump($res);
		}
		
		function testtreat_uploaded_file() {
			$uploadedFile['name']=stripslashes($uploadedFile['name']);
			$uploadedFile='';
			$baseWorkDir='';
			$uploadPath='';
			$maxFilledSpace='';
			$uncompress= '';
			$res= treat_uploaded_file($uploadedFile, $baseWorkDir, $uploadPath, $maxFilledSpace, $uncompress= '');
			$this->assertTrue(is_bool($res));
			//var_dump($res);
		}

		function testunique_name() {
			$path='';
			$name='';
			$res= unique_name($path,$name);
			$this->assertTrue(is_string($res));
			//var_dump($res);
		}

		function testunzip_uploaded_document() {
			//require_once(api_get_path(LIBRARY_PATH).'/pclzip/pclzip.lib.php');
			global $_course;
			global $_user;
			global $to_user_id;
			global $to_group_id;
			$uploaded_file='';
			$upload_path='';
			$base_work_dir='';
			$max_filled_space='';
			ob_start();
			$res= unzip_uploaded_document($uploaded_file, $upload_path, $base_work_dir, $max_filled_space, $output = true, $to_group_id=0);
			ob_end_clean();
			$this->assertTrue(is_bool($res));
			//var_dump($res);
		}

		function testunzip_uploaded_file() {
			global $_course;	
			$uploadedFile   = 'README.txt';
			$uploadPath     = api_get_path(SYS_PATH).$_course.'/document';
			$baseWorkDir    = api_get_path(SYS_PATH);
			$maxFilledSpace = 10;
			$res= unzip_uploaded_file($uploadedFile, $uploadPath, $baseWorkDir, $maxFilledSpace);
			$this->assertTrue(is_bool($res));
			//var_dump($res);
		}

		function testupdate_existing_document() {
			$_course='';
			$document_id='';
			$filesize='';
			$res= update_existing_document($_course,$document_id,$filesize,$readonly=0);
			$this->assertTrue(is_bool($res));
			//var_dump($res);
		}
		
		public function testdeleteCourse(){
		$this->dmanager = null;
	 	$code = 'COURSEX';				
		$res = CourseManager::delete_course($code);					
		}

}
?>
