<?php

/**
 * A standalone test for the internationalization library.
 * @author Ricardo Rodriguez Salazar, 2009.
 * @author Ivan Tcholakov, September 2009.
 * For licensing terms, see /dokeos_license.txt
 */

//ini_set('memory_limit','128M');

$_current_dir = dirname(__FILE__).'/';

$_sys_code_path = $_current_dir.'../main/';
$_sys_include_path = $_sys_code_path.'inc/';
$_sys_library_path = $_sys_code_path.'inc/lib/';

$_test_sys_code_path = $_current_dir.'main/';
$_test_sys_include_path = $_test_sys_code_path.'inc/';
$_test_sys_library_path = $_test_sys_code_path.'inc/lib/';


require_once($_current_dir.'simpletest/unit_tester.php');

require_once($_sys_include_path.'global.inc.php');

//header('Content-Type: text/html; charset=' . $charset);
header('Content-Type: text/html; charset=' . 'UTF-8');

require_once($_current_dir.'simpletest/web_tester.php');
require_once($_current_dir.'simpletest/mock_objects.php');
require_once($_current_dir.'simpletest/autorun.php');

$_SESSION['_user']['user_id'] = 1;

class InternationalizationTests extends TestSuite {

    function  InternationalizationTests() {
        $this->TestSuite('Internationalization Tests');

        global $_test_sys_library_path;
		$this->addTestFile($_test_sys_library_path.'internationalization.lib.test.php');
    }

}

$test = & new InternationalizationTests();
//$test-> run( new HtmlReporter());

?>