<?php
//require_once(api_get_path(LIBRARY_PATH).'classManager.lib.php');


class TestFileManager extends UnitTestCase {

	public $fmanager;
	public function TestFileManager(){

		$this->UnitTestCase ('File Display Manage');


	}

	public function setUp(){

		$this->fmanager = new FileManager();
	}

	public function tearDown(){

		$this->fmanager = null;
	}

	//todo public function testUpdatedbInfo
	//todo public function testCheckNameExist
	//todo public function testMyDelete
	//todo public function testRemoveDir
	//todo public function testMyRename
	//todo public function testMove
	//todo public function testCopyDirTo
	//todo public function testIndexDir
	//todo public function testIndexAndSortDir
	//todo public function testFormDirList
	//todo public function testMkpath
	//todo public function testGetextension
	//todo public function testDirsize
	//todo public function testListAllDirectories
	//todo public function testListAllFiles
	//todo public function testCompatLoadFile
	//todo public function testSetDefaultSettings
	//todo public function testMkdirs

	public function testUpdatedbInfo(){
		$action ='';
		$oldPath ='';
		$res = update_db_info($action, $oldPath, $newPath="");
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	public function testCheckNameExist(){
		$filePath ='';
		$res = check_name_exist($filePath);
		$this->assertFalse($res);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === false);
		//var_dump($res);
	}

	public function testMyDelete(){
		$file='';
		$res = my_delete($file);
		$this->assertFalse($res);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res===false);
		//var_dump($res);
	}

	public function testRemoveDir(){
		$dir='';
		$res = removeDir($dir);
		$this->assertTrue(is_bool($res));
		$this->assertFalse($res === true);
		//var_dump($res);
	}

	public function testMyRename(){
		$filePath ='documents';
		$newFileName='';
		$res = my_rename($filePath, $newFileName);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === false);
		//var_dump($res);
	}

	public function testMove(){
		$source ='';
		$target ='';
		$res = move($source, $target);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === false);
		$this->assertFalse($res);
		//var_dump($res);
	}

	public function testCopyDirTo(){
		$origDirPath=api_get_path(SYS_COURSE_PATH).'COURSEX/document/audio';
		$destination=api_get_path(SYS_COURSE_PATH).'COURSEX/document/flash/audio';
		$res = copyDirTo($origDirPath, $destination, $move = false);
		$this->assertTrue($res===null);
		$this->assertNull($res);
	}

	public function testIndexDir(){
		$path=api_get_path(SYS_COURSE_PATH).'COURSEX/document';
	  	$res = index_dir($path);
		$this->assertTrue(is_array($res));
		$this->assertTrue($res);
		//var_dump($res);
	}

	public function testIndexAndSortDir(){
		$path=api_get_path(SYS_COURSE_PATH).'COURSEX/document';
		$res = index_and_sort_dir($path);
		$this->assertTrue($res);
		$this->assertTrue(is_array($res));
		$this->assertFalse(is_bool($res));
		$this->assertFalse($res === array());
		//var_dump($res);
	}

	public function testFormDirList(){
		$sourceType = '';
		$sourceComponent = '';
		$command = '';
		$baseWorkDir = api_get_path(SYS_COURSE_PATH).'COURSEX/document/';
		$res = form_dir_list($sourceType, $sourceComponent, $command, $baseWorkDir);
		$this->assertTrue($res);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	public function testMkpath(){
		$path=api_get_path(SYS_COURSE_PATH).'COURSEX/document';
		$res =mkpath($path, $verbose=false);
		$this->assertFalse($res);
		$this->assertNull($res);
		$this->assertTrue($res === null);
		//var_dump($res);
	}

	public function testGetextension(){
		$filename='documents';
		$res =getextension($filename);
		$this->assertTrue($res);
		$this->assertTrue(is_array($res));
	}

	public function testDirsize(){
		$root='';
		$res =dirsize($root,$recursive=true);
		$this->assertFalse($res);
		$this->assertTrue(is_numeric($res));
		$this->assertTrue($res ===0);
		//var_dump($res);
	}

	public function testListAllDirectories(){
		$path=api_get_path(SYS_COURSE_PATH).'COURSEX/document';
		$res = $this->fmanager->list_all_directories($path);
		$this->assertTrue($res);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}

	public function testListAllFiles(){
		$dirArray = array('COURSEX, document, images');
		$res = $this->fmanager->list_all_files($dirArray);
		$this->assertFalse($res);
		$this->assertTrue(is_array($res));
		$this->assertTrue($res === array());
		//var_dump($res);
	}

	public function testCompatLoadFile(){
		$file_name='README.txt';
		$res = $this->fmanager->compat_load_file($file_name);
		$this->assertFalse($res);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	public function testSetDefaultSettings(){
		global $_course, $_configuration;
		$upload_path=api_get_path(SYS_COURSE_PATH).'COURSEX';
		$filename='index.html';
		$glue_table = $_course['dbName'].'.document';		
		$res = $this->fmanager->set_default_settings($upload_path, $filename, $filetype="file", $glue_table, $default_visibility='v');
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	public function testMkdirs(){
		$path=api_get_path(SYS_COURSE_PATH).'COURSEX/document';
		$res = $this->fmanager->mkdirs($path);
		$this->assertFalse($res);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === false);
		//var_dump($res);
	}
}


?>
