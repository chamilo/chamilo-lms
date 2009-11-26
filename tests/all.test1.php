<?php


ini_set('memory_limit','256M');
require_once('simpletest/unit_tester.php');
require_once(dirname(__FILE__).'/../main/inc/global.inc.php');
require_once('simpletest/web_tester.php');
//require_once('simpletest/mock_objects.php');
require_once('simpletest/autorun.php');

ob_start();
require_once(dirname(__FILE__).'/../main/inc/lib/course_document.lib.php');
require_once(dirname(__FILE__).'/../main/inc/lib/banner.lib.php');
require_once(dirname(__FILE__).'/../main/inc/tool_navigation_menu.inc.php');
require_once(dirname(__FILE__).'/../main/inc/banner.inc.php');
ob_end_clean();
$_SESSION['_user']['user_id'] = 1;


class AllTests1 extends TestSuite {
    function AllTests1() {
    
    	global $loginFailed;	
    	
       $this->TestSuite('All tests1');
       //echo dirname(__FILE__).'/main/inc/banner.lib.test.php';

		//var_dump($_SESSION);
       //$loginFailed = false;
       //$_REQUEST['login'] = 'admin'; 
       //$_REQUEST['password'] = 'admin';
	   $this->addTestFile(dirname(__FILE__).'/main/inc/banner.lib.test.php');       
       $this->addTestFile(dirname(__FILE__).'/main/inc/course_document.lib.test.php');
       $this->addTestFile(dirname(__FILE__).'/main/inc/tool_navigation_menu.inc.test.php');
       
       
           	
     }
}
$test = &new AllTests1();
//$test-> run( new HtmlReporter());
?>