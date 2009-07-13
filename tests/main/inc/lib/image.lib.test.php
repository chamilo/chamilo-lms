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

	public function testAddBackGround() {
			$bgfile='';
			$res=$this->timage->addbackground($bgfile);
			$this->assertTrue(is_null($res)); 
			//var_dump($bgfile);
		}

	public function testAddLogo() {
			$file='';
			$res=$this->timage->addlogo($file);
			$this->assertTrue(is_null($res)); 
			//var_dump($res);
	}

	public function testaddtext() {
			$text='';
			$res=$this->timage->addtext($text);
			$this->assertTrue(is_null($res)); 
			//var_dump($res);
	}

	public function testcreateimagefromtype() {
			$file='';
			$handler='';
			$res=$this->timage->createimagefromtype($file,$handler);
			$this->assertTrue(is_null($res)); 		
			//var_dump($res);
	}
	
	public function testimagenaddback() {
			$bgfile='';
			$res=$this->timage->image($bgfile);
			$this->assertTrue(is_null($res)); 		
			//var_dump($res);
		
	}
	
	public function testmakecolor() {
	 		$red='';
	 		$green='';
	 		$blue='';
	 		$res=$this->timage->makecolor($red, $green, $blue);
			$this->assertTrue(is_null($res)); 		
			//var_dump($res);
	 }
	
	public function testmergelogo() {
	 		$x='';
	 		$y='';
	 		$res=$this->timage->mergelogo($x,$y);
			$this->assertTrue(is_bool($res));
			//var_dump($res);
	 }

	public function testresize() {
			$thumbw='';
			$thumbh='';
			$border='';
			$res=$this->timage->resize($thumbw , $thumbh , $border);
			$this->assertTrue(is_null($res));
			//var_dump($res);
	}

	 public function testsend_image() {
	 		$type='';
	 		$res=$this->timage->send_image($type);
			$this->assertTrue(is_numeric($res));
			//var_dump($res);
	 }
	

	public function testsetfont() {
			$fontfile=$this->fontfile;
			$res=$this->timage->setfont($fontfile);
			$this->assertTrue(is_null($res));
			//var_dump($res);
	}












	
}
?>
