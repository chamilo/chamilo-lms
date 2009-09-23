<?php
require_once(api_get_path(LIBRARY_PATH).'mail.lib.inc.php');

Mock::generate('PHPMailer');
class TestMail extends UnitTestCase {

	function testApiMail() {
		$mensajee = new MockPHPMailer();
		global $regexp;
 		global $platform_email;
 		$recipient_name='';
 		$recipient_email='';
 		$subject='';
 		$message='';
 		$res=api_mail($recipient_name, $recipient_email, $subject, $message);
 		$mensajee->expectOnce($mail);
 		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}

	function testApiMailHtml() {
		$mensajee = new MockPHPMailer();
		global $regexp;
 		global $platform_email;
 		$recipient_name='';
 		$recipient_email='';
 		$subject='';
 		$res=api_mail_html($recipient_name, $recipient_email, $subject);
 		$mensajee->expectOnce($mail);
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}
}
?>
