<?php

class TestTNMenu extends UnitTestCase{

	public function TestTNMenu(){

		$this->UnitTestCase('Navigation menu display code function tests');

	}
	public function testGetNavigationItems(){
		global $is_courseMember;
		global $_user;
		global $_course;
		$include_admin_tools = false;
		$res = get_navigation_items($include_admin_tools);
		$this->assertTrue(is_array($res));
	}

	public function testShowNavigationMenu(){
		ob_start();
		$navigation_items = get_navigation_items(true);
		$course_id = api_get_course_id();
		$res = show_navigation_menu();
		$this->assertTrue(is_null($res));
		ob_end_clean();
	}

	public function testShowNavigationToolShortcuts(){
		$orientation = SHORTCUTS_HORIZONTAL;
		$res = show_navigation_tool_shortcuts($orientation);
		$this->assertTrue(is_null($res));
	}


}



?>
