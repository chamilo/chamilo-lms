<?php
require_once(api_get_path(LIBRARY_PATH).'message.lib.php');

Mock::generate('MessageManager');
Mock::generate('Display');

class TestMessage extends UnitTestCase {

	function testget_number_of_messages_mask() {
		$docme = new MockMessageManager();
		$res=get_number_of_messages_mask();
        $docme->expectOnce(MessageManager::get_number_of_messages());
		$this->assertTrue(is_string($res));
        //var_dump($res);
	}

	function testget_message_data_mask() {
		$docme = new MockMessageManager();
		$from='';
		$number_of_items=get_number_of_messages_mask();
		$column='3';
		$direction='';
		$res=get_message_data_mask();
        $docme->expectOnce(MessageManager::get_message_data($from, $number_of_items, $column, $direction));
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}

	function testget_message_data_send_mask() {
		$docme = new MockMessageManager();
		$from='';
		$number_of_items=get_number_of_messages_send_mask();
		$column= '3';
		$direction='';
		$res=get_message_data_send_mask();
		$docme->expectOnce(MessageManager::get_number_of_messages_sent());
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}

	function testget_number_of_messages_send_mask() {
		$docme = new MockMessageManager();
		$res=get_number_of_messages_send_mask();
        $docme->expectOnce(MessageManager::get_number_of_messages_sent());
		$this->assertTrue(is_string($res));
        //var_dump($res);

	}

	function testinbox_display() {
		$docme = new MockMessageManager();
		global $charset;
		ob_start();
		$res=inbox_display();
        $docme->expectOnce(MessageManager::delete_message_by_user_receiver(api_get_user_id(), $message_id));
		$this->assertTrue(is_null($res));
		ob_end_clean();
		//var_dump($res);
	}

	function testoutbox_display() {
		$docme = new MockMessageManager();
		$disp = new MockDisplay();
		global $charset;
		ob_start();
		$res=outbox_display();
        $docme->expectOnce(MessageManager::delete_message_by_user_receiver(api_get_user_id(), $_GET['id']));
        $disp->expectOnce(Display::display_confirmation_message(api_xml_http_response_encode($success),false));
		$this->assertTrue(is_null($res));
		ob_end_clean();
		//var_dump($res);
	}
}
?>
