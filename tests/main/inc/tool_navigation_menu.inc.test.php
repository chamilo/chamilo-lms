<?php

class TestToolNavigationMenu extends UnitTestCase{

    public function TestToolNavigationMenu(){
        $this->UnitTestCase('Navigation menu display library - main/inc/tool_navigation_menu.inc.test.php');
    }
	public function testGetNavigationItemsIsArray() {
		global $is_courseMember;
		global $_user;
		global $_course;
		$include_admin_tools = false;
		$res = get_navigation_items($include_admin_tools);
		$this->assertTrue(is_array($res));
	}

	public function testShowNavigationMenuIsNull() {
		ob_start();
		$navigation_items = get_navigation_items(true);
		$course_id = api_get_course_id();
		$res = show_navigation_menu();
		$this->assertNull($res);
		ob_end_clean();
	}

	public function testShowNavigationToolShortcutsIsNull() {
		ob_start();
		$orientation = SHORTCUTS_HORIZONTAL;
		$res = show_navigation_tool_shortcuts($orientation);
		$this->assertNull($res);
		ob_end_clean();
	}
}