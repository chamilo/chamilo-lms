<?php //$id$
require_once('../main/inc/global.inc.php');
$_SESSION['_user']['user_id'] = 1;
require_once('../main/inc/global.inc.php');
require_once('simpletest/autorun.php');

class AllTests extends TestSuite {
    function AllTests() {
        $this->TestSuite('All tests');
        //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/usermanager.lib.test.php');
        $this->addTestFile(dirname(__FILE__).'/main/inc/lib/main_api.lib.test.php');
      
    }
}
$test = &new AllTests();
//$test-> run( new HtmlReporter());
?>