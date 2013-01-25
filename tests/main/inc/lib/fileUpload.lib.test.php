<?php
require_once(api_get_path(LIBRARY_PATH).'document.lib.php');

class TestFileUpload extends UnitTestCase
{

    public function __construct()
    {
        $this->UnitTestCase('File upload library - main/inc/lib/fileUpload.lib.test.php');
    }

    //Creating

    function testCreateUnexistingDirectory()
    {
        global $_course, $user_id;
        $to_group_id = 1;
        $to_user_id = 1;
        $base_work_dir = api_get_path(SYS_COURSE_PATH).'document/';
        $desired_dir_name = 'images';
        $res = FileManager::create_unexisting_directory(
            $_course,
            $user_id,
            $to_group_id,
            $to_user_id,
            $base_work_dir,
            $desired_dir_name
        );

        if (!is_null($res)) {
            $this->assertTrue(is_bool($res));
            $this->assertFalse($res);
        }
    }

    /**
     * This recursive function can be used during the upgrade process form older versions of Dokeos
     * It crawls the given directory, checks if the file is in the DB and adds it if it's not
     *
     * @param string $base_work_dir
     * @param string $current_path, needed for recursivity
     */
    function testAddAllDocumentsInFolderToDatabase()
    {
        global $_course, $user_id;
        $base_work_dir = api_get_path(SYS_PATH).'';
        $current_path = 'courses/';
        $to_group_id = 0;
        $res = add_all_documents_in_folder_to_database($_course, $user_id, $base_work_dir, $current_path, $to_group_id);
        $this->assertTrue(is_null($res));
        //var_dump($res);
    }

    function testAddDocument()
    {
        global $_course;
        $path = '';
        $filetype = '';
        $filesize = '';
        $title = '';
        $res = FileManager::add_document($_course, $path, $filetype, $filesize, $title);
        if (!is_numeric($res)) :
            $this->assertTrue(is_bool($res));
        endif;
        //var_dump($res);
    }

    function testAddExtOnMime()
    {
        $fileName = '';
        $fileType = '';
        $res = add_ext_on_mime($fileName, $fileType);
        $this->assertTrue(is_string($res));
        //var_dump($res);
    }

    //build

    function testBuildMissingFilesForm()
    {
        global $_course;
        $missing_files = array();
        $upload_path = api_get_path(SYS_CODE_PATH).'default_course_document/images';
        $file_name = 'board.jpg';
        $res = FileManager::check_for_missing_files($missing_files, $upload_path, $file_name);
        $this->assertTrue(is_string($res));
        //var_dump($res);
    }

    //check

    function testCheckForMissingFiles()
    {
        $file = '';
        $res = check_for_missing_files($file);
        $this->assertTrue(is_bool($res));
    }

    //space

    function testDirTotalSpace()
    {
        $dirPath = api_get_path(SYS_CODE_PATH).'default_course_document/images';
        $res = dir_total_space($dirPath);
        $this->assertTrue($res > 0, 'The default_course_document/images dir should be larger than 0 bytes');
    }

    //filter

    function testfilter_extension()
    {
        $filename = 'index.php';
        $res = filter_extension($filename);
        $this->assertTrue(is_numeric($res));
    }

    //get

    function testget_document_title()
    {
        $name = '';
        $res = FileManager::get_document_title($name);
        $this->assertTrue(is_string($res));
        //var_dump($res);
    }

    function testget_levels()
    {
        $filename = 'readme.txt';
        $res = get_levels($filename);
        $this->assertTrue(is_numeric($res));
        //var_dump($res);
    }

    //access

    function testhtaccess2txt()
    {
        $filename = 'readme.txt';
        $res = htaccess2txt($filename);
        $this->assertTrue(is_string($res));
        //var_dump($res);
    }

    //moving

    function testmove_uploaded_file_collection_into_directory()
    {
        global $_course;
        $uploaded_file_collection = array();
        $base_work_dir = api_get_path(SYS_COURSE_PATH).'upload/';
        $missing_files_dir = '';
        $user_id = 1;
        $to_group_id = '';
        $to_user_id = '';
        $max_filled_space = '';
        $res = move_uploaded_file_collection_into_directory(
            $_course,
            $uploaded_file_collection,
            $base_work_dir,
            $missing_files_dir,
            $user_id,
            $to_group_id,
            $to_user_id,
            $max_filled_space
        );
        $this->assertTrue(is_null($res));
        //var_dump($res);
    }

    //php?

		function testFileManage::php2phps () {
			$fileName = 'index.php';
			$res= FileManage::php2phps($fileName);
			$this->assertTrue(is_string($res));
			//var_dump($res);
		}

//searching

function testsearch_img_from_html()
{
    $imgFilePath = array();
    $htmlFile = 'file:///var/www/chamilotest/documentation/credits.html';
    $res = search_img_from_html($htmlFile);
    if (is_array($res)) {
        $this->assertTrue(is_array($res));
    } else {
        $this->assertTrue(is_null($res));
    }
    //var_dump($res);
}

//uploading

function testprocess_uploaded_file()
{
    $uploaded_file = '';
    ob_start();
    $res = process_uploaded_file($uploaded_file);
    ob_end_clean();
    $this->assertTrue(is_bool($res));
}

function testhandle_uploaded_document()
{
    global $_course;
    $uploaded_file = '';
    $base_work_dir = '';
    $upload_path = '';
    $user_id = 1;
    $to_group_id = 0;
    $to_user_id = null;
    $unzip = 0;
    $what_if_file_exists = '';
    $output = true;
    $clean_name = FileManage::disable_dangerous_file($clean_name);
    $where_to_save = $base_work_dir.$upload_path;
    $new_name = unique_name($where_to_save, $clean_name);
    $new_file_path = $upload_path.$new_name;
    $clean_name = FileManage::disable_dangerous_file($clean_name);
    $file_path = $upload_path.$clean_name;
    ob_start();
    $res = handle_uploaded_document(
        $_course,
        $uploaded_file,
        $base_work_dir,
        $upload_path,
        $user_id,
        $to_group_id = 0,
        $to_user_id = null,
        $unzip = 0,
        $what_if_file_exists = '',
        $output = true
    );
    $this->assertTrue(is_null($res));
    ob_end_clean();
    //var_dump($res);
}

//updating

		function testFileManager::update_existing_document(){
    $_course = '';
			$document_id = '';
			$filesize = '';
			$res = FileManager::update_existing_document($_course, $document_id, $filesize, $readonly = 0);
			$this->assertTrue(is_bool($res));
			//var_dump($res);
		}

		function testFileManager::item_property_update_on_folder(){
			global $_course, $user_id;
			$path = api_get_path(SYS_COURSE_PATH).'document/license.txt';
			$res = FileManager::item_property_update_on_folder($_course, $path, $user_id);
			$this->assertTrue(is_null($res));
			//var_dump($res);
		}

		//unique name

		function testunique_name()
        {
            $path = '';
            $name = '';
            $res = unique_name($path, $name);
            $this->assertTrue(is_string($res));
            //var_dump($res);
        }

		//unzipping

		function testunzip_uploaded_document()
        {
            //require_once(api_get_path(LIBRARY_PATH).'pclzip/pclzip.lib.php');
            global $_course;
            global $_user;
            global $to_user_id;
            global $to_group_id;
            $uploaded_file = '';
            $upload_path = '';
            $base_work_dir = '';
            $max_filled_space = '';
            ob_start();
            $res = unzip_uploaded_document(
                $uploaded_file,
                $upload_path,
                $base_work_dir,
                $max_filled_space,
                $output = true,
                $to_group_id = 0
            );
            ob_end_clean();
            $this->assertTrue(is_bool($res));
            //var_dump($res);
        }

		function testunzip_uploaded_file()
        {
            global $_course;
            $uploadedFile = 'README.txt';
            $uploadPath = api_get_path(SYS_PATH).$_course.'/document';
            $baseWorkDir = api_get_path(SYS_PATH);
            $maxFilledSpace = 1000;
            $res = unzip_uploaded_file($uploadedFile, $uploadPath, $baseWorkDir, $maxFilledSpace);
            $this->assertTrue(is_bool($res));
            //var_dump($res);
        }

		//disable

		function testDisableDangerousFile()
        {
            $filename = 'index.php';
            $res = FileManage::disable_dangerous_file($filename);
            $this->assertTrue(is_string($res));
            //var_dump($res);
        }

		//replace

		function testreplace_img_path_in_html_file()
        {
            global $_course;
            $originalImgPath = '';
            $newImgPath = '';
            $htmlFile = 'file:///var/www/chamilotest/documentation/credits.html';
            $res = replace_img_path_in_html_file($originalImgPath, $newImgPath, $htmlFile);
            $this->assertTrue(is_null($res));
            //var_dump($res);
        }

		function testCleanUpFilesInZip()
        {
            $p_event = '';
            $p_header['filename'] = '';
            $res = clean_up_files_in_zip($p_event, &$p_header);
            $this->assertTrue(is_numeric($res));
            //var_dump($res);
        }

		function testCleanUpPath()
        {
            $base_work_dir = api_get_path(SYS_COURSE_PATH);
            $path = $base_work_dir.'upload/blog';
            $res = clean_up_path($path);
            $this->assertTrue(is_numeric($res));

        }
}