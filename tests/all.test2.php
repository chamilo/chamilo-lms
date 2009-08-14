<?php

ini_set('memory_limit','128M');
require_once('simpletest/unit_tester.php');
require_once(dirname(__FILE__).'/../main/inc/global.inc.php');
require_once('simpletest/web_tester.php');
require_once('simpletest/mock_objects.php');
require_once('simpletest/autorun.php');
require_once(api_get_path(SYS_CODE_PATH).'admin/calendar.lib.php');
require_once(api_get_path(SYS_CODE_PATH).'admin/statistics/statistics.lib.php');
require_once(api_get_path(LIBRARY_PATH).'usermanager.lib.php');
require_once(api_get_path(SYS_CODE_PATH).'/survey/survey.lib.php');
require_once(api_get_path(SYS_CODE_PATH).'/install/install_upgrade.lib.php');

$_SESSION['_user']['user_id'] = 1;
$_user= 1;

class AllTests2 extends TestSuite {
    function AllTests2() {
    	$this->TestSuite('All tests2');

 	  	//$this->addTestFile(dirname(__FILE__).'/main/admin/calendar.lib.test.php');
    	//$this->addTestFile(dirname(__FILE__).'/main/admin/statistics/statistics.lib.test.php');
        //$this->addTestFile(dirname(__FILE__).'/main/auth/lost_password.lib.test.php');
        //$this->addTestFile(dirname(__FILE__).'/main/auth/openid/xrds.lib.test.php');
        //$this->addTestFile(dirname(__FILE__).'/main/auth/openid/openid.lib.test.php');
        //$this->addTestFile(dirname(__FILE__).'/main/chat/chat_functions.lib.test.php');
        //$this->addTestFile(dirname(__FILE__).'/main/conference/get_translation.lib.test.php');
        //$this->addTestFile(dirname(__FILE__).'/main/exercice/hotpotatoes.lib.test.php');
        //$this->addTestFile(dirname(__FILE__).'/main/newscorm/scorm.lib.test.php');
        //$this->addTestFile(dirname(__FILE__).'/main/install/install_upgrade.lib.test.php');
        //$this->addTestFile(dirname(__FILE__).'/main/survey/survey.lib.test.php');
        //$this->addTestFile(dirname(__FILE__).'/main/user/userInfoLib.test.php');
        //$this->addTestFile(dirname(__FILE__).'/main/webservices/user_import/import.lib.test.php');
        //$this->addTestFile(dirname(__FILE__).'/main/work/work.lib.test.php');

		
    	$this->addTestFile(dirname(__FILE__).'/main/admin/calendar.lib.test.php');
    	$this->addTestFile(dirname(__FILE__).'/main/admin/statistics/statistics.lib.test.php');
        $this->addTestFile(dirname(__FILE__).'/main/auth/lost_password.lib.test.php');
        $this->addTestFile(dirname(__FILE__).'/main/auth/openid/xrds.lib.test.php');
        $this->addTestFile(dirname(__FILE__).'/main/auth/openid/openid.lib.test.php');
        $this->addTestFile(dirname(__FILE__).'/main/chat/chat_functions.lib.test.php');
        $this->addTestFile(dirname(__FILE__).'/main/conference/get_translation.lib.test.php');
        $this->addTestFile(dirname(__FILE__).'/main/exercice/hotpotatoes.lib.test.php');
        $this->addTestFile(dirname(__FILE__).'/main/newscorm/scorm.lib.test.php');
        $this->addTestFile(dirname(__FILE__).'/main/install/install_upgrade.lib.test.php');
        $this->addTestFile(dirname(__FILE__).'/main/survey/survey.lib.test.php');
        $this->addTestFile(dirname(__FILE__).'/main/user/userInfoLib.test.php');
        $this->addTestFile(dirname(__FILE__).'/main/webservices/user_import/import.lib.test.php');
        $this->addTestFile(dirname(__FILE__).'/main/work/work.lib.test.php');        
       
    }
}
$test = &new AllTests2();
//$test-> run( new HtmlReporter());

?>
