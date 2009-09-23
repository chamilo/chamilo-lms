<?php
	require_once('simpletest/unit_tester.php');
	require_once('../main/inc/global.inc.php');
	$_SESSION['_user']['user_id'] = 1;
	require('../main/inc/global.inc.php');
	require_once('main/inc/lib/main_api.lib.test.php');
	require_once(api_get_path(SYS_CODE_PATH) . 'permissions/permissions_functions.inc.php');
	require_once('simpletest/autorun.php');

	class Testing extends TestSuite {
        function Testing() {
            $this->TestSuite('Testing');
            $this->addTestFile(dirname(__FILE__).'/main/inc/lib/main_api.lib.test.php');
        }

    }
      $test = &new Testing();
   // $test->run(new HtmlReporter());
?>
