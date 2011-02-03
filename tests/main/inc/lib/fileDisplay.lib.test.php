<?php
require_once(api_get_path(LIBRARY_PATH).'fileDisplay.lib.php');
class TestFileDisplay extends UnitTestCase {

	public function TestFileDisplay(){
		$this->UnitTestCase('File display library - main/inc/lib/fileDisplay.lib.test.php');
	}
	//todo public function testArraySearch()
	//todo public function testChooseImage()
	//todo public function testFormatFileSize()
	//todo public function testFormatDate()
	//todo public function testFormatUrl()
	//todo public function testRecentModifiedFileTime()
	//todo public function testFolderSize()
	//todo public function testGetTotalFolderSize()

	public function testChooseImage(){
		global $_course;

		static $type, $image;
		$file_name = '';
		$res = choose_image($file_name);
		$this->assertEqual($res,'defaut.gif');
		$this->assertTrue(is_string($res));
		//var_dump($file_name);
	}

	public function testFormatFileSize(){
		$file_size = '100';
		$res = format_file_size($file_size);
		$this->assertTrue($res);
		$this->assertTrue(is_string($res));
		//var_dump($res);

	}

	public function testFormatDate(){
		$date = '11/02/2009';
		$res = format_date($date);
		$this->assertTrue($res);
		//var_dump($res);

	}

	public function testFormatUrl(){
		$file_path ='/var/www/path/';
		$res = format_url($file_path);
		$this->assertTrue($res);
		$this->assertTrue(is_string($res));
		//var_dump($res);

	}

	public function testRecentModifiedFileTime(){
		$dir_name = '';
		$$do_recursive =true;
		$res = recent_modified_file_time($dir_name, $do_recursive);
		$this->assertTrue(is_numeric($res));
		//var_dump($res);

	}

	public function testFolderSize(){
		$dir_name ='';
		$res = folder_size($dir_name);
		$this->assertFalse($res);
		$this->assertTrue(is_numeric($res));
		//var_dump($res);


	}

}


?>
