<?php
require_once(api_get_path(LIBRARY_PATH).'message.lib.php');

class TestMessage extends UnitTestCase {

    public function __construct() {
        $this->UnitTestCase('Messages library - main/inc/lib/message.lib.test.php');
    }

	function testInboxDisplay() {
		global $charset;
		ob_start();
		$res=MessageManager::inbox_display();
		$this->assertTrue(is_null($res));
		ob_end_clean();
		//var_dump($res);
	}

	function testOutboxDisplay() {
		global $charset;
		ob_start();
		$res=MessageManager::outbox_display();
		$this->assertTrue(is_null($res));
		ob_end_clean();
		//var_dump($res);
	}
}
?>
