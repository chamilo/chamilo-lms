<?php

//Set the time limit for the tests
ini_set('memory_limit','256M');

//List of files than need the tests
require_once('simpletest/unit_tester.php');
require_once('simpletest/web_tester.php');
require_once('simpletest/mock_objects.php');
require_once('simpletest/autorun.php');
require_once ('load_global.php');

//Need the ob start and clean else will show the objects 
require_once(dirname(__FILE__).'/../main/inc/global.inc.php');
ob_start();
require_once(dirname(__FILE__).'/../main/inc/lib/course_document.lib.php');
require_once(dirname(__FILE__).'/../main/inc/lib/banner.lib.php');
require_once(dirname(__FILE__).'/../main/inc/tool_navigation_menu.inc.php');
require_once(dirname(__FILE__).'/../main/inc/banner.inc.php');
ob_end_clean();

//List of files than need the tests since chamilo
require_once(api_get_path(SYS_CODE_PATH).'admin/calendar.lib.php');
require_once(api_get_path(SYS_CODE_PATH).'admin/statistics/statistics.lib.php');
require_once(api_get_path(LIBRARY_PATH).'usermanager.lib.php');
require_once(api_get_path(SYS_CODE_PATH).'/survey/survey.lib.php');
require_once(api_get_path(SYS_CODE_PATH).'/install/install_upgrade.lib.php');


class AllTestsSuite extends TestSuite {
    function AllTestsSuite() {
    	  	
    	$this->TestSuite('All tests suite');

		//List of files from all.test.php
		//List of files from all.test1.php
		$this->addTestFile(dirname(__FILE__).'/main/inc/banner.lib.test.php');       
        $this->addTestFile(dirname(__FILE__).'/main/inc/course_document.lib.test.php');
        $this->addTestFile(dirname(__FILE__).'/main/inc/tool_navigation_menu.inc.test.php');
		//List of files from all.test2.php
    	$this->addTestFile(dirname(__FILE__).'/main/admin/calendar.lib.test.php');
    	$this->addTestFile(dirname(__FILE__).'/main/admin/statistics/statistics.lib.test.php');
        $this->addTestFile(dirname(__FILE__).'/main/auth/lost_password.lib.test.php');
        $this->addTestFile(dirname(__FILE__).'/main/auth/openid/xrds.lib.test.php');
        $this->addTestFile(dirname(__FILE__).'/main/chat/chat_functions.lib.test.php');
        $this->addTestFile(dirname(__FILE__).'/main/conference/get_translation.lib.test.php');
        $this->addTestFile(dirname(__FILE__).'/main/exercice/hotpotatoes.lib.test.php');
        $this->addTestFile(dirname(__FILE__).'/main/newscorm/scorm.lib.test.php');
        $this->addTestFile(dirname(__FILE__).'/main/survey/survey.lib.test.php');
        $this->addTestFile(dirname(__FILE__).'/main/user/userInfoLib.test.php');        
        $this->addTestFile(dirname(__FILE__).'/main/webservices/user_import/import.lib.test.php');        
        $this->addTestFile(dirname(__FILE__).'/main/work/work.lib.test.php');       
        $this->addTestFile(dirname(__FILE__).'/main/install/install_upgrade.lib.test.php');
        //List of files from all.test3.php
        $this->addTestFile(dirname(__FILE__).'/main/admin/sub_language.class.test.php');

    }
}
$test = &new AllTestsSuite();



?>
