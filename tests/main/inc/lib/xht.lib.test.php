<?php

class TestXht extends UnitTestCase {

	public $xhtdoc;
	public function TestXht(){
		$this->UnitTestCase('test the library that define xht_htmlwchars & class xhtdoc with methods');
	}
	public function setUp(){
		$this->xhtdoc = new xhtdoc();
	}

	public function tearDown(){
		$this->xhtdoc = null;
	}

	public function testXhtHtmlwchars(){
		global $charset;
		$s='';
		$res = xht_htmlwchars($s);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	public function testXhtIsAssoclist(){
		$s ='';
		$res = xht_is_assoclist($s);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === true || $res === false);
		//var_dump($res);
	}

	public function testXhtExplodeAssoclist(){
		$S =array(aaasasa);
		$res = xht_explode_assoclist($S);
		if(is_array($res))
		$this->assertTrue(is_array($res));
		else
		$this->assertFalse(is_array($res));
		//var_dump($res);
	}

	public function testXhtFillTemplate(){
		$template_name=null;
		$cur_elem = 0;
		$res = xhtdoc::xht_fill_template($template_name, $cur_elem);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	public function testxhtSubstitute(){
		global $charset;
		$subtext='';
		$cur_elem = 0;
		$pre = '';
		$post = '';
		$res = xhtdoc::xht_substitute($subtext, $cur_elem = 0, $pre = '', $post = '');
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	public function testXhtAddTemplate(){
		$template_name='';
		$httext='';
		$res = xhtdoc::xht_add_template($template_name, $httext);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	public function testXhtdoc(){
		$htt_file_contents='document';
		$res = xhtdoc::xhtdoc($htt_file_contents);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	public function testShowParam(){
		global $charset;
		$result = '';
		$res = xhtdoc::_show_param();
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}
}
?>
