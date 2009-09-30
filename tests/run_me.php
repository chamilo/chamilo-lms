<?php
require_once('simpletest/autorun.php');
require_once('simpletest/web_tester.php');
require_once('../blog.php');
// mock section start
require_once('simpletest/unit_tester.php');
require_once('simpletest/mock_objects.php');


Mock::generate('Vector');

class MyTestCase extends UnitTestCase {
    function testSomething() {
        $thing = &new Thing();
        $vector = &new MockVector();
        $vector->setReturnReference('get', $thing, array(12));

        //var_dump($vector->get(12));
    }
}
// mock section end
//Mock::generate('DatabaseConnection', 'MyMockDatabaseConnection', array('setOptions'));
?>
