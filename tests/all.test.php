<?php

//Set the time limit for the tests
ini_set('memory_limit','128M');

//List of files than need some tests
ob_start();
require_once 'simpletest/unit_tester.php';
require_once 'simpletest/web_tester.php';
require_once 'simpletest/mock_objects.php';
require_once 'simpletest/autorun.php';
require_once dirname(__FILE__).'/../main/inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'urlmanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'/fileDisplay.lib.php';
require_once api_get_path(SYS_CODE_PATH) .'permissions/permissions_functions.inc.php';
require_once api_get_path(LIBRARY_PATH) .'/groupmanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'course.lib.php';
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'social.lib.php';
require_once api_get_path(LIBRARY_PATH).'xht.lib.php';
require_once api_get_path(LIBRARY_PATH).'xmd.lib.php';
include_once api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php';

$_SESSION['_user']['user_id'] = 1;
ob_end_clean();
class AllTests extends TestSuite {

    function AllTests() {
        $this->TestSuite('All tests');
ob_start();
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/add_course.lib.inc.test.php'); // 431 excepciones
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/blog.lib.test.php');  // 137 excepciones
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/classmanager.lib.test.php'); // sin excepciones
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/course.lib.test.php'); // 91 excepciones
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/database.lib.test.php'); // 4 excepciones
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/debug.lib.inc.test.php'); // sin excepciones
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/display.lib.test.php'); // 6 excepciones
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/document.lib.test.php'); // 14 excepciones
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/events.lib.inc.test.php'); // 3 excepciones
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/export.lib.inc.test.php'); // 24 excepciones
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/fileDisplay.lib.test.php'); // sin excepciones
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/fileManager.lib.test.php');  // 14 excepciones
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/fileUpload.lib.test.php'); // 33 excepciones
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/geometry.lib.test.php'); // 4 excepciones
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/groupmanager.lib.test.php'); // 75 excepciones
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/image.lib.test.php'); // sin excepciones
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/import.lib.test.php'); // sin excepciones
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/mail.lib.inc.test.php'); // 3 excepciones
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/main_api.lib.test.php'); // 30 excepciones
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/message.lib.test.php'); //15 excepciones
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/internationalization.lib.test.php'); // sin excepciones
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/online.inc.test.php'); // sin excepciones
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/security.lib.test.php'); // sin excepciones
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/session_handler.class.test.php'); // sin excepciones
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/sessionmanager.lib.test.php'); // 9 excepciones
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/social.lib.test.php'); //22 excepciones
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/sortabletable.class.test.php'); // sin excepciones
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/specific_fields_manager.lib.test.php'); // 2 excepciones
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/stats.lib.inc.test.php'); // 5 excepciones
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/surveymanager.lib.test.php'); //49 excepciones
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/system_announcements.lib.test.php'); // 5 excepciones
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/tablesort.lib.test.php'); // 2 excepciones
        $this->addTestFile(dirname(__FILE__).'/main/inc/lib/text.lib.test.php'); // sin excepciones
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/tracking.lib.test.php'); // 12 excepciones
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/upload.xajax.test.php');  deprecated
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/xht.lib.test.php'); // 9 excepciones
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/tracking.lib.test.php'); // 12 excepciones
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/urlmanager.lib.test.php'); // 9 excepciones
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/usermanager.lib.test.php'); // 4 excepciones
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/xht.lib.test.php'); // 9 excepciones
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/xmd.lib.test.php'); // 26 excepciones

ob_end_clean();
/*
      $this->addTestFile(dirname(__FILE__).'/main/inc/lib/add_course.lib.inc.test.php');
      $this->addTestFile(dirname(__FILE__).'/main/inc/lib/blog.lib.test.php');
      $this->addTestFile(dirname(__FILE__).'/main/inc/lib/classmanager.lib.test.php');
      $this->addTestFile(dirname(__FILE__).'/main/inc/lib/course.lib.test.php');
      $this->addTestFile(dirname(__FILE__).'/main/inc/lib/database.lib.test.php');
      $this->addTestFile(dirname(__FILE__).'/main/inc/lib/debug.lib.inc.test.php');
      $this->addTestFile(dirname(__FILE__).'/main/inc/lib/display.lib.test.php');
      $this->addTestFile(dirname(__FILE__).'/main/inc/lib/document.lib.test.php');
      $this->addTestFile(dirname(__FILE__).'/main/inc/lib/events.lib.inc.test.php');
      $this->addTestFile(dirname(__FILE__).'/main/inc/lib/export.lib.inc.test.php');
      $this->addTestFile(dirname(__FILE__).'/main/inc/lib/fileDisplay.lib.test.php');
      $this->addTestFile(dirname(__FILE__).'/main/inc/lib/fileManager.lib.test.php');
      $this->addTestFile(dirname(__FILE__).'/main/inc/lib/fileUpload.lib.test.php');
      $this->addTestFile(dirname(__FILE__).'/main/inc/lib/geometry.lib.test.php');
      $this->addTestFile(dirname(__FILE__).'/main/inc/lib/groupmanager.lib.test.php');
      $this->addTestFile(dirname(__FILE__).'/main/inc/lib/image.lib.test.php');
      $this->addTestFile(dirname(__FILE__).'/main/inc/lib/import.lib.test.php');
      $this->addTestFile(dirname(__FILE__).'/main/inc/lib/legal.lib.test.php');
      $this->addTestFile(dirname(__FILE__).'/main/inc/lib/mail.lib.inc.test.php');
      $this->addTestFile(dirname(__FILE__).'/main/inc/lib/main_api.lib.test.php');
      $this->addTestFile(dirname(__FILE__).'/main/inc/lib/message.lib.test.php');
      $this->addTestFile(dirname(__FILE__).'/main/inc/lib/internationalization.lib.test.php');
      $this->addTestFile(dirname(__FILE__).'/main/inc/lib/online.inc.test.php');
      $this->addTestFile(dirname(__FILE__).'/main/inc/lib/security.lib.test.php');
      $this->addTestFile(dirname(__FILE__).'/main/inc/lib/session_handler.class.test.php');
      $this->addTestFile(dirname(__FILE__).'/main/inc/lib/sessionmanager.lib.test.php');
      $this->addTestFile(dirname(__FILE__).'/main/inc/lib/social.lib.test.php');
      $this->addTestFile(dirname(__FILE__).'/main/inc/lib/sortabletable.class.test.php');
      $this->addTestFile(dirname(__FILE__).'/main/inc/lib/specific_fields_manager.lib.test.php');
      $this->addTestFile(dirname(__FILE__).'/main/inc/lib/stats.lib.inc.test.php');
      $this->addTestFile(dirname(__FILE__).'/main/inc/lib/statsUtils.lib.inc.test.php');
      $this->addTestFile(dirname(__FILE__).'/main/inc/lib/surveymanager.lib.test.php');
      $this->addTestFile(dirname(__FILE__).'/main/inc/lib/system_announcements.lib.test.php');
      $this->addTestFile(dirname(__FILE__).'/main/inc/lib/tablesort.lib.test.php');
      $this->addTestFile(dirname(__FILE__).'/main/inc/lib/text.lib.test.php');
      $this->addTestFile(dirname(__FILE__).'/main/inc/lib/tracking.lib.test.php');
//      $this->addTestFile(dirname(__FILE__).'/main/inc/lib/upload.xajax.test.php'); -- deprecated library
      $this->addTestFile(dirname(__FILE__).'/main/inc/lib/urlmanager.lib.test.php');
      $this->addTestFile(dirname(__FILE__).'/main/inc/lib/usermanager.lib.test.php');
      $this->addTestFile(dirname(__FILE__).'/main/inc/lib/xht.lib.test.php');
      $this->addTestFile(dirname(__FILE__).'/main/inc/lib/xmd.lib.test.php');
      //$this->assertTrue(file_exists('/temp/test.log'));
  */  }
}
  $test = &new AllTests();
//$test-> run( new HtmlReporter());
?>
