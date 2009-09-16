<?php
ini_set('memory_limit','128M');
require_once('simpletest/unit_tester.php');
require_once(dirname(__FILE__).'/../main/inc/global.inc.php');
require_once(api_get_path(LIBRARY_PATH).'urlmanager.lib.php');
require_once('main/inc/lib/main_api.lib.test.php');
require_once('main/inc/lib/add_course.lib.inc.test.php');
require_once('main/inc/lib/blog.lib.test.php');
require_once(api_get_path(LIBRARY_PATH).'/fileDisplay.lib.php');
require_once(api_get_path(SYS_CODE_PATH) .'permissions/permissions_functions.inc.php');
require_once(api_get_path(LIBRARY_PATH) .'/groupmanager.lib.php');
require_once(api_get_path(LIBRARY_PATH).'course.lib.php');
require_once(api_get_path(LIBRARY_PATH).'usermanager.lib.php');
require_once(api_get_path(LIBRARY_PATH).'social.lib.php');
require_once(api_get_path(LIBRARY_PATH).'upload.xajax.php');
require_once(api_get_path(LIBRARY_PATH).'xht.lib.php');
require_once(api_get_path(LIBRARY_PATH).'xmd.lib.php');
require_once('main/inc/lib/fileDisplay.lib.test.php');
require_once('main/inc/lib/fileManager.lib.test.php');
require_once('main/inc/lib/geometry.lib.test.php');
require_once('simpletest/web_tester.php');
require_once('simpletest/mock_objects.php');
require_once('simpletest/autorun.php');
include_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
$_SESSION['_user']['user_id'] = 1;

class AllTests extends TestSuite {

    function AllTests() {
        $this->TestSuite('All tests');

      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/add_course.lib.inc.test.php');
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/blog.lib.test.php');
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/classmanager.lib.test.php');
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/course.lib.test.php');
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/database.lib.test.php');
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/debug.lib.inc.test.php');
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/display.lib.test.php');
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/document.lib.test.php');
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/events.lib.inc.test.php');
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/export.lib.inc.test.php');
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/fileDisplay.lib.test.php');
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/fileManager.lib.test.php');
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/fileUpload.lib.test.php');
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/geometry.lib.test.php');
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/groupmanager.lib.test.php');
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/image.lib.test.php');
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/import.lib.test.php');
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/mail.lib.inc.test.php');
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/main_api.lib.test.php');
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/media.lib.test.php');
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/message.lib.test.php');
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/multibyte_string_functions.lib.test.php');
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/online.inc.test.php');
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/security.lib.test.php');
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/session_handler.class.test.php');
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/sessionmanager.lib.test.php');
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/social.lib.test.php');
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/sortabletable.class.test.php');
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/specific_fields_manager.lib.test.php');
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/stats.lib.inc.test.php');
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/statsUtils.lib.inc.test.php');
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/surveymanager.lib.test.php');
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/system_announcements.lib.test.php');
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/tablesort.lib.test.php');
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/text.lib.test.php');
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/tracking.lib.test.php');
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/upload.xajax.test.php');
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/xht.lib.test.php');
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/tracking.lib.test.php');
	  //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/upload.xajax.test.php');
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/urlmanager.lib.test.php');
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/usermanager.lib.test.php');
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/xht.lib.test.php');
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/xmd.lib.test.php');
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/xht.lib.test.php');
      //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/xmd.lib.test.php');



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
      $this->addTestFile(dirname(__FILE__).'/main/inc/lib/media.lib.test.php');
      $this->addTestFile(dirname(__FILE__).'/main/inc/lib/message.lib.test.php');
      $this->addTestFile(dirname(__FILE__).'/main/inc/lib/multibyte_string_functions.lib.test.php');
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
      $this->addTestFile(dirname(__FILE__).'/main/inc/lib/upload.xajax.test.php');
      $this->addTestFile(dirname(__FILE__).'/main/inc/lib/urlmanager.lib.test.php');
      $this->addTestFile(dirname(__FILE__).'/main/inc/lib/usermanager.lib.test.php');
      $this->addTestFile(dirname(__FILE__).'/main/inc/lib/xht.lib.test.php');
      $this->addTestFile(dirname(__FILE__).'/main/inc/lib/xmd.lib.test.php');
      //$this->assertTrue(file_exists('/temp/test.log'));
    }
}
  $test = &new AllTests();
//$test-> run( new HtmlReporter());
?>