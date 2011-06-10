<?php
require_once(api_get_path(LIBRARY_PATH).'image.lib.php');

class TestImage extends UnitTestCase {

	public $timage;
	public function TestImage(){
		$this->UnitTestCase('All main image function tests');
	}

	public function setUp(){
		$this->timage = new image();
	}

	public function tearDown(){
		$this->timage = null;
	}

	/*public function testAddBackGround() {
			$bgfile='';
			$res = image::addbackground($bgfile);
			$this->assertTrue(is_null($res));
			//var_dump($bgfile);
	}

	public function testAddLogo() {
			$file='';
			$res = image::addlogo($file);
			$this->assertTrue(is_null($res));
			//var_dump($res);
	}

	public function testaddtext() {
			$text='';
			$res = image::addtext($text);
			$this->assertTrue(is_null($res));
			//var_dump($res);
	}

	public function testcreateimagefromtype() {
			$file='';
			$handler='';
			$res = image::createimagefromtype($file,$handler);
			$this->assertTrue(is_null($res));
			//var_dump($res);
	}

	public function testimageaddback() {
			$bgfile='';
			$res = image::image($bgfile);
			$this->assertTrue(is_null($res));
			//var_dump($res);
	}

	public function testmakecolor() {
	 		$red='';
	 		$green='';
	 		$blue='';
	 		$res = image::makecolor($red, $green, $blue);
			$this->assertTrue(is_null($res));
			//var_dump($res);
	}

	public function testmergelogo() {
	 		$x='';
	 		$y='';
	 		$res = image::mergelogo($x,$y);
			$this->assertTrue(is_bool($res));
			//var_dump($res);
	}*/

	public function testresize() {
			$thumbw='';
			$thumbh='';
			$border='';
			$res = image::resize($thumbw , $thumbh , $border);
			$this->assertTrue(is_null($res));
			//var_dump($res);
	}

	public function testsend_image() {
	 		$type='';
	 		$res = image::send_image($type);
			$this->assertTrue(is_numeric($res));
			//var_dump($res);
	}

/*
	public function testsetfont() {
			$fontfile=$this->fontfile;
			$res = image::setfont($fontfile);
			$this->assertTrue(is_null($res));
			//var_dump($res);
	}*/
/*
	public function TestDeleteCourse(){				
		$code = 'COURSETEST';				
		$res = CourseManager::delete_course($code);			
		$path = api_get_path(SYS_PATH).'archive';		
		if ($handle = opendir($path)) {
			while (false !== ($file = readdir($handle))) {				
				if (strpos($file,$code)!==false) {										
					if (is_dir($path.'/'.$file)) {						
						rmdirr($path.'/'.$file);						
					}				
				}				
			}
			closedir($handle);
		}
	}
*/
}