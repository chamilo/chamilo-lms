<?php

ini_set('memory_limit','128M');
require_once('simpletest/unit_tester.php');
require_once(dirname(__FILE__).'/../main/inc/global.inc.php');
//require_once(api_get_path(SYS_CODE_PATH).'inc/course_document.inc.php');
require_once('simpletest/web_tester.php');
require_once('simpletest/mock_objects.php');
require_once('simpletest/autorun.php');

class AllTests1 extends TestSuite {
    function AllTests1() {
    	$this->TestSuite('All tests1');
        
        $this->addTestFile(dirname(__FILE__).'/main/inc/banner.inc.test.php');
        /**
         * Problemas con la funcion prueba, no se ejecuta de manera adecuada.
         * $this->addTestFile(dirname(__FILE__).'/main/inc/course_document.inc.test.php');
         **/
        //$this->addTestFile(dirname(__FILE__).'/main/inc/footer.inc.test.php');
       /*$this->addTestFile(dirname(__FILE__).'/main/inc/global.inc.test.php');
      	$this->addTestFile(dirname(__FILE__).'/main/inc/header.inc.test.php');
     	$this->addTestFile(dirname(__FILE__).'/main/inc/introductionSection.inc.test.php');
   	  	$this->addTestFile(dirname(__FILE__).'/main/inc/latex.inc.test.php');
     	$this->addTestFile(dirname(__FILE__).'/main/inc/local.inc.test.php');
    	$this->addTestFile(dirname(__FILE__).'/main/inc/reduced_header.inc.test.php');*/
      	$this->addTestFile(dirname(__FILE__).'/main/inc/tool_navigation_menu.inc.test.php');
   		ob_end_clean();
     }
}
$test = &new AllTests1();
//$test-> run( new HtmlReporter());