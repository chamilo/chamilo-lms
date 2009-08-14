<?php
ini_set('memory_limit','128M');
require_once('simpletest/unit_tester.php');
require_once(dirname(__FILE__).'/../main/inc/global.inc.php');
require_once('simpletest/web_tester.php');
require_once('simpletest/mock_objects.php');
require_once('simpletest/autorun.php');

$_SESSION['_user']['user_id'] = 1;
$_user= 1;

class AllTests2 extends TestSuite {
    function AllTests2() {
    	$this->TestSuite('All tests3');
    	
	$this->addTestFile(dirname(__FILE__).'/main/admin/class.test.php');
	//$this->addTestFile(dirname(__FILE__).'/main//class.test.php');
	//$this->addTestFile(dirname(__FILE__).'/main//class.test.php');
	//$this->addTestFile(dirname(__FILE__).'/main//class.test.php');
	
	}
}
$test = &new AllTests3();
//$test-> run( new HtmlReporter());
?>
