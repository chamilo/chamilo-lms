<?php
require_once(api_get_path(LIBRARY_PATH).'message.lib.php');

class TestMessage extends UnitTestCase {

    public function __construct() {
        $this->UnitTestCase('Messages library - main/inc/lib/message.lib.test.php');
    }

	function testGetNumberOfMessagesMask() {
		$res=get_number_of_messages_mask();
		$this->assertTrue(is_string($res));
        //var_dump($res);
	}

	function testGetMessageDataMask() {
		$from='';
		$number_of_items=get_number_of_messages_mask();
		$column='3';
		$direction='';
		$res=get_message_data_mask($from, $number_of_items, $column, $direction);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}

	function testGetMessageDataSendMask() {
		$from='';
		$number_of_items=get_number_of_messages_send_mask();
		$column= '3';
		$direction='';
		$res=get_message_data_send_mask($from, $number_of_items, $column, $direction);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}

	function testGetNumberOfMessagesSendMask() {
		$res=get_number_of_messages_send_mask();
		$this->assertTrue(is_string($res));
        //var_dump($res);

	}

	function testInboxDisplay() {
		global $charset;
		ob_start();
		$res=inbox_display();
		$this->assertTrue(is_null($res));
		ob_end_clean();
		//var_dump($res);
	}

	function testOutboxDisplay() {
		global $charset;
		ob_start();
		$res=outbox_display();
		$this->assertTrue(is_null($res));
		ob_end_clean();
		//var_dump($res);
	}
}
?>
