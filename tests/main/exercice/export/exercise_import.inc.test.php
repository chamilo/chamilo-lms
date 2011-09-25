<?php


//Is possible than this functions still are not implemented int he Chamilo systems or some are deprecated


class TestExerciseImport extends UnitTestCase {
	
	function testelementData() {
		global $element_pile;
		$element_pile = array();
		$parser= array();
		$data = array();
		$res = elementData($parser,$data);
		$this->assertFalse(is_array($res));
		if(!is_null){
			$this->assertTrue($res);
		}
		//var_dump($res);
	} 
	
	function testendElement() {
		global $element_pile;
		$element_pile = array();
		$parser= array();
		$data = array();
		$res = endElement($parser,$data);
		$this->assertFalse(is_array($res));
		if(!is_null){
			$this->assertTrue($res);
		}
		//var_dump($res);
	} 
	
	/**
	 * possible deprecated
	 * @return the path of the temporary directory where the exercise was uploaded and unzipped
	 */
 
	/*function testgetandunzipuploadedexercise() {
		include_once (realpath(dirname(__FILE__) . '/../../inc/lib/pclzip/') . '/pclzip.lib.php');
		$res = get_and_unzip_uploaded_exercise();
		$this->assertFalse(is_array($res));
		if(!is_null){
			$this->assertTrue($res);
		}
		//var_dump($res);
	} */
	
	/**
	 * main function to import an exercise,
	 * Possible deprecated
	 * @return an array as a backlog of what was really imported, and error or debug messages to display
	 */
 
	/*function testimport_exercise() {
		$file = '';
		$res = import_exercise($file);
		$this->assertFalse(is_array($res));
		if(!is_null){
			$this->assertTrue($res);
		}
		var_dump($res);
	}*/
	
	function testparse_file() {
		$file = '';
		$exercisePath = '';
		$questionFile = '';
		$res = parse_file($exercisePath, $file, $questionFile);
		$this->assertTrue(is_array($res));
		if(!is_null){
			$this->assertTrue($res);
		}
		//var_dump($res);
	}
	
	function teststartElement() {
		$parser = 'test';
		$name = 'test';
		$attributes = array();
		$res = startElement($parser, $name, $attributes);
		$this->assertFalse(is_array($res));
		if(!is_null){
			$this->assertTrue($res);
		}
		//var_dump($res);
	}
	
	function testtempdir() {
		$dir = '/tmp';
		$res = tempdir($dir, $prefix='tmp', $mode=0777);
		$this->assertFalse(is_array($res));
		if(!is_null){
			$this->assertTrue(is_string($res));
		}
		//var_dump($res);
	}
}
?>
