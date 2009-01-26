<?php //$id$
//require_once('simpletest/autorun.php');

class AllTests extends TestSuite {
    function AllTests() {
        $this->TestSuite('All tests');
        $this->addTestFile(dirname(__FILE__).'/dummy.test.php');
        $this->addTestFile(dirname(__FILE__).'/dummy2.test.php');
    }
}
?>