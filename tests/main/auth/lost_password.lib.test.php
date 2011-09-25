<?php
require_once(api_get_path(LIBRARY_PATH).'login.lib.php');
require_once(api_get_path(LIBRARY_PATH).'course.lib.php');

class TestLostPassword extends UnitTestCase {

    public function __construct(){
        $this->UnitTestCase('Lost password library - main/auth/lost_password.lib.test.php');
    }
	/* function commented in platform code
	function testget_email_headers(){
		global $charset;
		$res = get_email_headers();
 		$this->assertTrue($res);
 		$this->assertTrue(is_string($res));
 		//var_dump($res);
	}
	*/

	function testget_secret_word(){
		global $_configuration;
		$add='';
		$res = Login::get_secret_word($add);
 		// Expects an string of 32 chars
 		$this->assertEqual(strlen($res),32);

 		$this->assertTrue(is_string($res));
 		//var_dump($res);
	}

	function testget_user_account_list(){
		global $_configuration;
		$user='';
		$thisUser=array();
		$secretword = Login::get_secret_word($thisUser["email"]);
		$reset_link = get_lang('Pass')." : $thisUser[password]";
		$userAccountList[] = get_lang('YourRegistrationData')." : \n".get_lang('UserName').' : '.$thisUser["loginName"]."\n".get_lang('ResetLink').' : '.$reset_link.'';
		$res = Login::get_user_account_list($user, $reset = false);
 		$this->assertTrue(is_array($userAccountList));
 		//var_dump($userAccountList);
	}

	function testhandle_encrypted_password() {
		require_once api_get_path (LIBRARY_PATH).'mail.lib.inc.php';
		global $charset;
		global $_configuration;
		ob_start();
		$user=array('abc');
		$emailSubject = "[".api_get_setting('siteName')."] ".get_lang('LoginRequest'); // SUBJECT
		$userAccountList = Login::get_user_account_list($user, true); // BODY
		$emailTo = $user[0]["email"];
		$secretword = Login::get_secret_word($emailTo);
		$emailBody = get_lang('DearUser')." :\n".get_lang("password_request")."\n\n";
		$emailBody .= "-----------------------------------------------\n".$userAccountList."\n-----------------------------------------------\n\n";
		$emailBody .=get_lang('PasswordEncryptedForSecurity');
		$emailBody .="\n\n".get_lang('Formula').",\n".get_lang('PlataformAdmin');
		$sender_name = api_get_setting('administratorName').' '.api_get_setting('administratorSurname');
    	$email_admin = api_get_setting('emailAdministrator');
		$res=Login::handle_encrypted_password($user);

		if(!is_array($res))$this->assertTrue(is_null($res));
		ob_end_clean();
		//var_dump($res);
	}

	function testreset_password(){
		$secret='1234567891011';
		$id=5;
		$res=Login::reset_password($secret, $id);
		$this->assertTrue($res);
 		$this->assertTrue(is_string($res));
 		//var_dump($res);
	}

	function testsend_password_to_user() {
		$user=array();
		ob_start();
		$res=Login::send_password_to_user($user);
		if(!is_null($res))$this->assertTrue(is_array($res));
		ob_end_clean();
 		//var_dump($res);
	}
}
?>
