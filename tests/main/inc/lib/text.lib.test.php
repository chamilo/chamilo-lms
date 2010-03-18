<?php
require_once(api_get_path(LIBRARY_PATH).'text.lib.php');

class TestText extends UnitTestCase {

	function test_text_parse_glossary() {
		$input='';
		$res=_text_parse_glossary($input);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	function test_text_parse_tex() {
		$textext='';
		$res=_text_parse_tex($textext);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	function test_text_parse_texexplorer() {
		$textext='';
		$res=_text_parse_texexplorer($textext);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	function test_text_parse_tool() {
		$input='';
		$res=_text_parse_tool($input);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	function testcut() {
		$text='';
		$maxchar='';
		$res=cut($text,$maxchar,$embed=false);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	function testdate_to_str_ago() {
		$date='';
		$res=date_to_str_ago($date);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	function testfloat_format() {
		$number='';
		$res=float_format($number, $flag = 1);
		if(!is_numeric($res) or !is_float($res)) {
			$this->assertTrue(is_null($res));
		}
		//var_dump($res);
	}

	function testlatex_gif_renderer() {
		ob_start();
		$latex_code="";
		global $_course;
		$res=latex_gif_renderer($latex_code);
		ob_end_clean();
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	function testmake_clickable() {
		$string='';
		$res=make_clickable($string);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	function testtext_filter() {
		$input='';
		$res=text_filter($input, $filter=true);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}
 }
?>
