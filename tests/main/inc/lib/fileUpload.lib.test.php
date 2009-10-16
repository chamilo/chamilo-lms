<?php
/** To can run this test you need comment "die() This code is in the line 278,1377,1386,1389,1542,1547,1571 file fileUpload.lib.php
 *  @author aportugal arthur.portugal@dokeos.com
 */
require_once(api_get_path(LIBRARY_PATH).'fileUpload.lib.php');
require_once(api_get_path(LIBRARY_PATH).'document.lib.php');

Mock::generate('DocumentManager');
Mock::generate('Display');
Mock::generate('Database');

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
			$filename='';
			$res= filter_extension($filename);
			$this->assertTrue(is_numeric($res));
			//var_dump($res);
		}

		function testget_document_title() {
			$name='';
			$res= get_document_title($name);
			$this->assertTrue(is_string($res));
			//var_dump($res);
		}

		function testget_levels() {
			$filename='';
			$res= get_levels($filename);
			$this->assertTrue(is_numeric($res));
			//var_dump($res);
		}

		function testhandle_uploaded_document() {
			$docman = new MockDisplay();
			$_course='';
			$uploaded_file='';
			$base_work_dir='';
			$upload_path='';
			$user_id='';
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
			$docman->expectOnce(Display::display_error_message(get_lang('UplNotEnoughSpace')));
			$docman->expectOnce(Display::display_error_message(get_lang('UplNotAZip')." ".get_lang('PleaseTryAgain')));
			$docman->expectOnce(Display::display_error_message(get_lang('UplUnableToSaveFileFilteredExtension')));
			$docman->expectOnce(Display::display_error_message(get_lang('DestDirectoryDoesntExist').' ('.$upload_path.')'));
			$docman->expectOnce(DocumentManager::get_document_id($_course,$file_path));
			$docman->expectOnce(Display::display_confirmation_message(get_lang('UplUploadSucceeded')."<br/>".$file_path .' '. get_lang('UplFileOverwritten'),false));
			$docman->expectOnce(Display::display_confirmation_message(get_lang('UplUploadSucceeded')."<br/>".$file_path,false));
			$docman->expectOnce(Display::display_error_message(get_lang('UplUnableToSaveFile')));
			$docman->expectOnce(Display::display_confirmation_message(get_lang('UplUploadSucceeded'). "<br>" .get_lang('UplFileSavedAs') . $new_file_path,false));
			$docman->expectOnce(Display::display_error_message(get_lang('UplUnableToSaveFile')));
			$docman->expectOnce(Display::display_error_message($clean_name.' '.get_lang('UplAlreadyExists')));
			$docman->expectOnce(Display::display_confirmation_message(get_lang('UplUploadSucceeded')."<br/>".$file_path,false));
			$docman->expectOnce(Display::display_error_message(get_lang('UplUnableToSaveFile')));
			$this->assertTrue(is_null($res));
			ob_end_clean();
			//var_dump($res);
		}

		function testhtaccess2txt() {
			$filename = str_replace('.htaccess', 'htaccess.txt', $filename);
			$filename = str_replace('.HTACCESS', 'htaccess.txt', $filename);
			$res= htaccess2txt($filename);
			$this->assertTrue(is_string($res));
			//var_dump($res);
		}

		function testitem_property_update_on_folder() {
			$path='/main/document/document.php';
			$_course='';
			$user_id='';
			$res= item_property_update_on_folder($_course,$path,$user_id);
			$this->assertTrue(is_null($res));
			//var_dump($res);
		}

		function testmove_uploaded_file_collection_into_directory() {
			$_course=array();
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
			$fileName = preg_replace('/\.(php.?|phtml.?)(\.){0,1}.*$/i', '.phps', $fileName);
			$res= php2phps($fileName);
			$this->assertTrue(is_string($res));
			//var_dump($res);
		}

		function testprocess_uploaded_file() {
			$docman = new MockDisplay();
			$uploaded_file='';
			ob_start();
			$res= process_uploaded_file($uploaded_file);
			$docman->expectOnce(Display::display_error_message(get_lang('UplExceedMaxServerUpload'). ini_get('upload_max_filesize')));
			$docman->expectOnce(Display::display_error_message(get_lang('UplExceedMaxPostSize'). round($_POST['MAX_FILE_SIZE']/1024) ." KB"));
			$docman->expectOnce(Display::display_error_message(get_lang('$UplPartialUpload')." ".get_lang('PleaseTryAgain')));
			$docman->expectOnce(Display::display_error_message(get_lang('UplNoFileUploaded')." ". get_lang('UplSelectFileFirst')));
			$docman->expectOnce(Display::display_error_message(get_lang('UplNoFileUploaded')));
			$docman->expectOnce(Display::display_error_message(get_lang('UplFileTooBig')));
			$docman->expectOnce(Display::display_error_message(get_lang('UplUploadFailed')));
			$this->assertTrue(is_bool($res));
			ob_end_clean();
		}

		function testreplace_img_path_in_html_file() {
			global $_course;
			$originalImgPath='';
			$newImgPath='';
			$htmlFile='';
			$res= replace_img_path_in_html_file($originalImgPath, $newImgPath, $htmlFile);
			$this->assertTrue(is_null($res));
			//var_dump($res);
		}

		function testsearch_img_from_html() {
			$htmlFile='';
			$res= search_img_from_html($htmlFile);
			$this->assertTrue(is_null($res));
			//var_dump($res);
		}

		function testset_default_settings() {
			global $dbTable,$_configuration;
			global $default_visibility;
			$upload_path=str_replace('\\','/',$upload_path);
			$upload_path=str_replace("//","/",$upload_path);
			$filename=substr($filename,0,-1);
			$res= set_default_settings($upload_path,$filename,$filetype="file");
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
			$docman = new MockDisplay();
			require_once(api_get_path(LIBRARY_PATH).'/pclzip/pclzip.lib.php');
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
			$docman->expectOnce(Display::display_error_message(get_lang('UplNotEnoughSpace')));
			$this->assertTrue(is_bool($res));
			ob_end_clean();
			//var_dump($res);
		}

		function testunzip_uploaded_file() {
			$uploadedFile='';
			$uploadPath='';
			$baseWorkDir='';
			$maxFilledSpace='';
			$res= unzip_uploaded_file($uploadedFile, $uploadPath, $baseWorkDir, $maxFilledSpace);
			$this->assertTrue(is_bool($res));
			//var_dump($res);
		}

		function testupdate_existing_document() {
			$docman = new MockDatabase();
			$_course='';
			$document_id='';
			$filesize='';
			$res= update_existing_document($_course,$document_id,$filesize,$readonly=0);
			$docman->expectOnce(Database::get_course_table(TABLE_DOCUMENT,$_course['dbName']));
			$this->assertTrue(is_bool($res));
			//var_dump($res);
		}
}
?>
