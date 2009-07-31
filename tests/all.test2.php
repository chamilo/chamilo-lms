<?php

ini_set('memory_limit','128M');
require_once('simpletest/unit_tester.php');
require_once(dirname(__FILE__).'/../main/inc/global.inc.php');
require_once('simpletest/web_tester.php');
require_once('simpletest/mock_objects.php');
require_once('simpletest/autorun.php');
require_once(api_get_path(SYS_CODE_PATH).'admin/calendar.lib.php');
require_once(api_get_path(SYS_CODE_PATH).'admin/statistics/statistics.lib.php');

$_SESSION['_user']['user_id'] = 1;
$_user= 1;

class AllTests2 extends TestSuite {
    function AllTests2() {
    	$this->TestSuite('All tests2');
        //$this->addTestFile(dirname(__FILE__).'/main/admin/calendar.lib.test.php');
        $this->addTestFile(dirname(__FILE__).'/main/admin/statistics/statistics.lib.test.php');
    }
}
$test = &new AllTests2();
//$test-> run( new HtmlReporter());

?>
