<?php

//Set the time limit for the tests
ini_set('memory_limit','256M');

//List of files than need some tests
require_once('simpletest/autorun.php');
require_once('simpletest/web_tester.php');
require_once('simpletest/mock_objects.php');
require_once('simpletest/unit_tester.php');
require_once(dirname(__FILE__).'/../main/inc/global.inc.php');


$_SESSION['_user']['user_id'] = 1;
$_user= 1;

class AllTests3 extends TestSuite {
    function AllTests3() {
    	$this->TestSuite('All tests3');

	$this->addTestFile(dirname(__FILE__).'/main/admin/sub_language.class.test.php');

	}
}
$test = &new AllTests3();
//$test-> run( new HtmlReporter());
?>
