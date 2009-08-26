<?php
require_once(api_get_path(SYS_CODE_PATH).'auth/lost_password.lib.php');

Mock::generate('Display');
class TestLostPassword extends UnitTestCase {

	function testget_email_headers(){
		global $charset;
		$res = get_email_headers();
 		$this->assertTrue($res);
 		$this->assertTrue(is_string($res));
 		//var_dump($res);
	}
	
	function testget_secret_word(){
		global $_configuration;
		$add='';
		$res = get_secret_word($add);
 		$this->assertTrue($res);
 		$this->assertTrue(is_string($res));
 		//var_dump($res);
	}
	
	function testget_user_account_list(){
		global $_configuration;
		$user='';
		$thisUser=array();
		$secretword = get_secret_word($thisUser["email"]);
		$reset_link = get_lang('Pass')." : $thisUser[password]";
		$userAccountList[] = get_lang('YourRegistrationData')." : \n".get_lang('UserName').' : '.$thisUser["loginName"]."\n".get_lang('ResetLink').' : '.$reset_link.'';
		$res = get_user_account_list($user, $reset = false);
 		$this->assertTrue($userAccountList);
 		$this->assertTrue(is_array($userAccountList));
 		//var_dump($userAccountList);
	}
	
	function testhandle_encrypted_password() {
		require_once api_get_path (LIBRARY_PATH).'mail.lib.inc.php'; 
		global $charset;
		global $_configuration;
		ob_start();
		$user=array('abc');
		$emailHeaders = get_email_headers(); // Email Headers
		$emailSubject = "[".api_get_setting('siteName')."] ".get_lang('LoginRequest'); // SUBJECT
		$userAccountList = get_user_account_list($user, true); // BODY
		$emailTo = $user[0]["email"];
		$secretword = get_secret_word($emailTo);	
		$emailBody = get_lang('DearUser')." :\n".get_lang("password_request")."\n\n";
		$emailBody .= "-----------------------------------------------\n".$userAccountList."\n-----------------------------------------------\n\n";
		$emailBody .=get_lang('PasswordEncryptedForSecurity');
		$emailBody .="\n\n".get_lang('Formula').",\n".get_lang('PlataformAdmin');
		$sender_name = api_get_setting('administratorName').' '.api_get_setting('administratorSurname');
    	$email_admin = api_get_setting('emailAdministrator');
		$res=handle_encrypted_password($user);
		if(!is_array($res))$this->assertTrue(is_null($res));
		ob_end_clean();
		//var_dump($res);	
	}
	
	function testreset_password(){
		$secret='1234567891011';
		$id=5;
		$res=reset_password($secret, $id);
		$this->assertTrue($res);
 		$this->assertTrue(is_string($res));
 		//var_dump($res);
	}
	
	function testsend_password_to_user() {
		$user=array();
		ob_start();
		$res=send_password_to_user($user);
		if(!is_null($res))$this->assertTrue(is_array($res));
		ob_end_clean();
 		//var_dump($res);
	}
}
?>
