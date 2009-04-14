<?php //$id$
require_once('../main/inc/global.inc.php');
require_once('simpletest/autorun.php');

class AllTests extends TestSuite {
    function AllTests() {
        $this->TestSuite('All tests');
        $this->addTestFile(dirname(__FILE__).'/main/inc/lib/usermanager.lib.test.php');
    }
}
?>