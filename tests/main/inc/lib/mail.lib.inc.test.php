<?php
require_once(api_get_path(LIBRARY_PATH).'mail.lib.inc.php');

class TestMail extends UnitTestCase {

    public function __construct() {
        $this->UnitTestCase('Mailing library - main/inc/lib/mail.lib.inc.test.php');
    }

	function testApiMail() {
		ob_start();
 		$recipient_name='';
 		$recipient_email='';
 		$subject='';
 		$message='';
 		$res=@api_mail($recipient_name, $recipient_email, $subject, $message);
 		ob_end_clean();
 		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}

	function testApiMailHtml() {
		ob_start();
 		$recipient_name='';
 		$recipient_email='';
 		$subject='';
 		$res=@api_mail_html($recipient_name, $recipient_email, $subject);
 		ob_end_clean();
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}
}
?>
