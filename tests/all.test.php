<?php
ini_set('memory_limit','128M');
require_once('simpletest/unit_tester.php');
require_once('../main/inc/global.inc.php');
require_once('simpletest/web_tester.php');
require_once('simpletest/mock_objects.php');
require_once('simpletest/autorun.php');
$_SESSION['_user']['user_id'] = 1;

class AllTests extends TestSuite {
    
    function AllTests() {
        $this->TestSuite('All tests');
        $this->addFile(dirname(__FILE__).'/main/inc/lib/add_course.lib.inc.test.php');
        $this->addFile(dirname(__FILE__).'/main/inc/lib/main_api.lib.test.php');
        $this->addTestFile(dirname(__FILE__).'/main/inc/lib/blog.lib.test.php');
        $this->addTestFile(dirname(__FILE__).'/main/inc/lib/classmanager.lib.test.php');
        $this->addTestFile(dirname(__FILE__).'/main/inc/lib/database.lib.test.php');
        $this->addTestFile(dirname(__FILE__).'/main/inc/lib/debug.lib.inc.test.php');
        $this->addTestFile(dirname(__FILE__).'/main/inc/lib/display.lib.test.php');
        $this->addTestFile(dirname(__FILE__).'/main/inc/lib/usermanager.lib.test.php');
        $this->addTestFile(dirname(__FILE__).'/main/inc/lib/document.lib.test.php');
        $this->addTestFile(dirname(__FILE__).'/main/inc/lib/events.lib.inc.test.php'); 
        //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/export.lib.inc.test.php');
        //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/fileDisplay.lib.test.php');
        $this->addTestFile(dirname(__FILE__).'/run_me.php');   
        //$this->assertTrue(file_exists('/temp/test.log'));
    }
}
$test = &new AllTests();
//$test-> run( new HtmlReporter());
?>