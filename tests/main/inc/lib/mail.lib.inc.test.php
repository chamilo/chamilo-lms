<?php
require_once(api_get_path(LIBRARY_PATH).'mail.lib.inc.php');


Mock::generate('PHPMailer');

class TestMail extends UnitTestCase {

	function testApiMail() {
		
		global $regexp;
 		global $platform_email;
 		$mail = new MockPHPMailer();
 		$recipient_name='';
 		$recipient_email='';
 		$subject='';
 		$message='';
 		$res=api_mail($recipient_name, $recipient_email, $subject, $message);
 		$mail->expectOnce($recipient_name, $recipient_email, $subject, $message, $sender_name="", $sender_email="", $extra_headers="");
		$this->assertTrue(is_object($mail));
		//var_dump($mail);
	}
	/*
	function testApiMail() {
		global $regexp;
 		global $platform_email;
 		$recipient_name='';
 		$recipient_email='';
 		$subject='';
 		$message='';
 		$mail='';
 		$res=api_mail($recipient_name, $recipient_email, $subject, $message);
		$this->assertTrue(is_numeric($res));
		var_dump($mail);
	}*/

	



	
}
?>
