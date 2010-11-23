<?php
require_once(api_get_path(LIBRARY_PATH).'text.lib.php');

class TestText extends UnitTestCase {

	public function test_api_html_to_text() {
		$filename = api_get_path(SYS_PATH).'documentation/installation_guide.html';
		$res = @file_get_contents($filename);
		if ($res !== false) {
			$res = api_html_to_text($res);
			$this->assertTrue(is_string($res));
		} else {
			$this->assertTrue(true); // The file is missing, skip this test.
		}
		//var_dump('<pre>'.$res.'</pre>');
	}

	public function test_api_str_getcsv() {
		$strings = array('FirstName;LastName;Email', 'John;Doe;john.doe@mail.com', '"Иван";\\Чолаков;ivan@mail.com');
		$expected_results = array(array('FirstName', 'LastName', 'Email'), array('John', 'Doe', 'john.doe@mail.com'), array('Иван', 'Чолаков', 'ivan@mail.com'));
		$res = array();
		foreach ($strings as $string) {
			$res[] = api_str_getcsv($string, ';');
		}
		$this->assertTrue($res === $expected_results);
		//var_dump($res);
	}

	public function test_api_fgetcsv() {
		$filename = api_get_path(SYS_CODE_PATH).'admin/exemple.csv';
		$res = array();
		$handle = @fopen($filename, 'r');
		if ($handle !== false) {
			while (($line = @api_fgetcsv($handle, null, ';')) !== false) {
				$res[] = $line;
			}
			@fclose($handle);
			$this->assertTrue(is_array($res) && count($res) > 0);
		} else {
			$this->assertTrue(true); // The file is missing, skip this test.
		}
		//var_dump($res);
	}

	public function test_api_camel_case_to_underscore() {
		$input_strings    = array('myDocuments', 'MyProfile', 'CreateNewCourse', 'Create_New_course');
		$expected_results = array('my_documents', 'my_profile', 'create_new_course', 'create_new_course');
		$results = array_map('api_camel_case_to_underscore', $input_strings);
		$this->assertTrue($results == $expected_results);
		//var_dump($results);
	}

	function test_api_underscore_to_camel_case() {
		$input_strings     = array('my_documents', 'My_profile', 'create_new_course');
		$expected_results1 = array('MyDocuments', 'MyProfile', 'CreateNewCourse');
		$expected_results2 = array('myDocuments', 'MyProfile', 'createNewCourse');
		$func = create_function('$param', 'return api_underscore_to_camel_case($param, false);');
		$results1 = array('MyDocuments', 'MyProfile', 'CreateNewCourse');
		$results2 = array('myDocuments', 'MyProfile', 'createNewCourse');
		$results1 = array_map('api_underscore_to_camel_case', $input_strings);
		$results2 = array_map($func, $input_strings);
		$this->assertTrue($results1 == $expected_results1 && $results2 == $expected_results2);
		//var_dump($results1);
		//var_dump($results2);
	}

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

	function testApiParseTex(){
		$textext = 'abc';
		$res = api_parse_tex($textext); //this function is practically deprecated now, it doesn't do anything
		$this->assertEqual($textext,$res);
	}

 }
?>
