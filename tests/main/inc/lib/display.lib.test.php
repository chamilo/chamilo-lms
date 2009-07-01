<?php
require_once(api_get_path(LIBRARY_PATH).'display.lib.php');
require_once(api_get_path(LIBRARY_PATH).'main_api.lib.php');

class TestDisplay extends UnitTestCase {
	
	public function testdisplay_introduction_section() {
		$tool=api_get_tools_lists($my_tool=null);
		$res=Display::display_introduction_section($tool);
		$this->assertTrue(is_array($tool));
		//var_dump($tool);
	}
	
	public function testdisplay_localised_html_file(){
		global $language_interface;
		$doc_url = str_replace('/..', '', $doc_url);
		$full_file_name=api_get_path(SYS_COURSE_PATH).api_get_course_path().'/upload/blog/'.$doc_url;
		$res=Display::display_localised_html_file($full_file_name);
		$this->assertTrue(is_string($full_file_name));
		//var_dump($full_file_name);
	}
	
	public function testdisplay_table_header(){
		$res=Display::display_table_header();
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}
	
	public function testdisplay_complex_table_header() {
		$properties='';
		$column_header='';
		$res=Display::display_complex_table_header($properties, $column_header);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}
	
	public function testdisplay_table_row() {
		$bgcolor='HTML_WHITE';
		$table_row='';
		$res=Display::display_table_row($bgcolor, $table_row);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}
	
	public function testdisplay_complex_table_row() {
		$properties='';
		$table_row='';
		$res=Display::display_complex_table_row($properties, $table_row);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}
	
	public function testdisplay_table_footer() {
		$res=Display::display_table_footer();
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}
	
	public function testdisplay_sortable_table() {
		$header='';
		$content='';
		global $origin;
		ob_start();
		Display::display_sortable_table($header, $content);
		$res= ob_get_contents();
		$this->assertTrue(is_string($res));
		ob_end_clean();
		//var_dump($res);
	}
	
	public function testdisplay_sortable_config_table() {
		$header='';
		$content='';
		global $origin;
		ob_start();
		Display::display_sortable_config_table($header, $content);
		$res= ob_get_contents();
		$this->assertTrue(is_string($res));
		ob_end_clean();
	}
	
	public function testdisplay_normal_message() {
		$message='';
		global $charset;
		$res=Display::display_normal_message($message);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}
	
}
?>