<?php
// $Id: test_suite.php 2010-02-17 12:07:00Z aportugal $

/* For licensing terms, see /chamilo_license.txt */
/**
==============================================================================
*	This is the index file load when a user is testing functions in Chamilo.
*
*	It load:
*	- global.inc
*	- files of simpletest
*	- files with functions tests
*
*	@todo rewrite code to separate display, logic, database code
*	@package chamilo.main
==============================================================================
*/

/**
 * @todo shouldn't these settings be moved to the test_suite.php.
 * 		 if these are really configuration then we can make require_once in each tests.
 * @todo use this file to load the setup in each file test.
 * @todo check for duplication of require with test_suite.php
 * @author aportugal
 */

/*
-----------------------------------------------------------
	Included libraries
-----------------------------------------------------------
*/
//The global.inc.php be need be load here to can load the settings files
$incdir = dirname(__FILE__).'/../main/inc/'; 
require_once $incdir.'global.inc.php';

//This file load the functions to create and destroy the course
require_once api_get_path(SYS_TEST_PATH).'test_manager.inc.php';

//Files than need simpletest to can test
require_once 'simpletest/unit_tester.php';
require_once 'simpletest/web_tester.php';
require_once 'simpletest/mock_objects.php';
require_once 'simpletest/autorun.php';

/*
==============================================================================
		TEST SUITE
==============================================================================
Start to load the tests files
*/

class TestsSuite extends TestSuite {
	
    function TestsSuite() {
    	
    	/**Name of the test suite*/
    	$this->TestSuite('All tests suite');
    	
    	/**Files will be tested*/
/*		$this->addFile(api_get_path(SYS_TEST_PATH).'/main/inc/lib/database.lib.test.php');
		$this->addFile(api_get_path(SYS_TEST_PATH).'/main/inc/lib/add_course.lib.inc.test.php');
		$this->addFile(api_get_path(SYS_TEST_PATH).'/main/inc/lib/course.lib.test.php');
		$this->addFile(api_get_path(SYS_TEST_PATH).'/main/inc/banner.lib.test.php');
		$this->addFile(api_get_path(SYS_TEST_PATH).'/main/admin/calendar.lib.test.php');
		$this->addFile(api_get_path(SYS_TEST_PATH).'/main/inc/lib/surveymanager.lib.test.php');
		$this->addFile(api_get_path(SYS_TEST_PATH).'/main/inc/lib/session_handler.class.test.php');
        $this->addFile(api_get_path(SYS_TEST_PATH).'/main/inc/lib/sessionmanager.lib.test.php');
        $this->addFile(api_get_path(SYS_TEST_PATH).'/main/inc/lib/classmanager.lib.test.php');
        $this->addFile(api_get_path(SYS_TEST_PATH).'/main/inc/lib/export.lib.inc.test.php');
        $this->addFile(api_get_path(SYS_TEST_PATH).'/main/inc/lib/legal.lib.test.php');
        $this->addFile(api_get_path(SYS_TEST_PATH).'/main/inc/lib/mail.lib.inc.test.php');   
        $this->addFile(api_get_path(SYS_TEST_PATH).'/main/inc/lib/message.lib.test.php');
        $this->addFile(api_get_path(SYS_TEST_PATH).'/main/inc/lib/online.inc.test.php'); 
        $this->addFile(api_get_path(SYS_TEST_PATH).'/main/inc/lib/security.lib.test.php');
		$this->addFile(api_get_path(SYS_TEST_PATH).'/main/inc/lib/specific_fields_manager.lib.test.php');  
   	    $this->addFile(api_get_path(SYS_TEST_PATH).'/main/inc/lib/social.lib.test.php');
   	    $this->addFile(api_get_path(SYS_TEST_PATH).'/main/inc/lib/sortabletable.class.test.php'); 
   	    $this->addFile(api_get_path(SYS_TEST_PATH).'/main/inc/lib/statsUtils.lib.inc.test.php');
   	    $this->addFile(api_get_path(SYS_TEST_PATH).'/main/inc/lib/tablesort.lib.test.php');
   	    $this->addFile(api_get_path(SYS_TEST_PATH).'/main/inc/lib/text.lib.test.php');
   	    $this->addFile(api_get_path(SYS_TEST_PATH).'/main/inc/lib/tracking.lib.test.php');
		$this->addFile(api_get_path(SYS_TEST_PATH).'/main/inc/lib/blog.lib.test.php');
		$this->addFile(api_get_path(SYS_TEST_PATH).'/main/inc/lib/urlmanager.lib.test.php');
		$this->addFile(api_get_path(SYS_TEST_PATH).'/main/inc/lib/stats.lib.inc.test.php');
		$this->addFile(api_get_path(SYS_TEST_PATH).'/main/inc/course_document.lib.test.php');
        $this->addFile(api_get_path(SYS_TEST_PATH).'/main/inc/tool_navigation_menu.inc.test.php');
        $this->addFile(api_get_path(SYS_TEST_PATH).'/main/inc/lib/display.lib.test.php');
		$this->addFile(api_get_path(SYS_TEST_PATH).'/main/inc/lib/document.lib.test.php');
		$this->addFile(api_get_path(SYS_TEST_PATH).'/main/inc/lib/events.lib.inc.test.php');
		$this->addFile(api_get_path(SYS_TEST_PATH).'/main/inc/lib/fileDisplay.lib.test.php');
		$this->addFile(api_get_path(SYS_TEST_PATH).'/main/inc/lib/fileManage.lib.test.php');
	    $this->addFile(api_get_path(SYS_TEST_PATH).'/main/inc/lib/geometry.lib.test.php');
    	$this->addFile(api_get_path(SYS_TEST_PATH).'/main/admin/statistics/statistics.lib.test.php');
     	$this->addFile(api_get_path(SYS_TEST_PATH).'/main/inc/lib/access_url_edit_courses_to_url_functions.lib.test.php');
    	$this->addFile(api_get_path(SYS_TEST_PATH).'/main/inc/lib/access_url_edit_sessions_to_url_functions.lib.test.php');
    	$this->addFile(api_get_path(SYS_TEST_PATH).'/main/inc/lib/access_url_edit_users_to_url_functions.lib.test.php');
    	$this->addFile(api_get_path(SYS_TEST_PATH).'/main/inc/lib/add_courses_to_sessions_functions.lib.test.php');
    	$this->addFile(api_get_path(SYS_TEST_PATH).'/main/inc/lib/add_many_session_to_category_functions.lib.test.php');
    	//$this->addFile(api_get_path(SYS_TEST_PATH).'/main/admin/access_urls.test.php');
    	$this->addFile(api_get_path(SYS_TEST_PATH).'/main/admin/sub_language.class.test.php');
    	$this->addFile(api_get_path(SYS_TEST_PATH).'/main/inc/lib/add_courses_to_sessions_functions.lib.test.php');
    	$this->addFile(api_get_path(SYS_TEST_PATH).'/main/auth/lost_password.lib.test.php');
        $this->addFile(api_get_path(SYS_TEST_PATH).'/main/auth/openid/xrds.lib.test.php');
        $this->addFile(api_get_path(SYS_TEST_PATH).'/main/chat/chat_functions.lib.test.php');
        $this->addFile(api_get_path(SYS_TEST_PATH).'/main/conference/get_translation.lib.test.php');
        $this->addFile(api_get_path(SYS_TEST_PATH).'/main/exercice/hotpotatoes.lib.test.php');      
        $this->addFile(api_get_path(SYS_TEST_PATH).'/main/newscorm/scorm.lib.test.php');
        $this->addFile(api_get_path(SYS_TEST_PATH).'/main/survey/survey.lib.test.php');
        $this->addFile(api_get_path(SYS_TEST_PATH).'/main/user/userInfoLib.test.php');        
        $this->addFile(api_get_path(SYS_TEST_PATH).'/main/webservices/user_import/import.lib.test.php');        
        $this->addFile(api_get_path(SYS_TEST_PATH).'/main/work/work.lib.test.php');
        $this->addFile(api_get_path(SYS_TEST_PATH).'/main/inc/lib/glossary.lib.test.php');
        $this->addFile(api_get_path(SYS_TEST_PATH).'/main/inc/lib/notebook.lib.test.php');
        $this->addFile(api_get_path(SYS_TEST_PATH).'/main/permissions/permissions_functions.inc.test.php');     
        $this->addFile(api_get_path(SYS_TEST_PATH).'/main/resourcelinker/resourcelinker.inc.test.php');
        $this->addFile(api_get_path(SYS_TEST_PATH).'/main/survey/survey.lib.test.php');
        $this->addFile(api_get_path(SYS_TEST_PATH).'/main/dropbox/dropbox_class.inc.test.php');
	  	$this->addFile(api_get_path(SYS_TEST_PATH).'/main/dropbox/dropbox_functions.inc.test.php');
	  	$this->addFile(api_get_path(SYS_TEST_PATH).'/main/search/search_suggestions.test.php');
	  	$this->addFile(api_get_path(SYS_TEST_PATH).'/main/exercice/export/qti2/qti2_classes.test.php');
	  	$this->addFile(api_get_path(SYS_TEST_PATH).'/main/exercice/export/scorm/scorm_classes.test.php');
        $this->addFile(api_get_path(SYS_TEST_PATH).'/main/inc/lib/usermanager.lib.test.php'); 
        $this->addFile(api_get_path(SYS_TEST_PATH).'/main/inc/lib/groupmanager.lib.test.php');	 
        $this->addFile(api_get_path(SYS_TEST_PATH).'/main/inc/lib/image.lib.test.php'); 
        $this->addFile(api_get_path(SYS_TEST_PATH).'/main/inc/lib/import.lib.test.php');
        $this->addFile(api_get_path(SYS_TEST_PATH).'/main/inc/lib/internationalization.lib.test.php');  
        $this->addFile(api_get_path(SYS_TEST_PATH).'/main/inc/lib/system_announcements.lib.test.php');
        $this->addFile(api_get_path(SYS_TEST_PATH).'/main/inc/lib/fileUpload.lib.test.php');
        $this->addFile(api_get_path(SYS_TEST_PATH).'/main/inc/lib/main_api.lib.test.php');//this file delete the course     
        $this->addFile(api_get_path(SYS_TEST_PATH).'/main/inc/lib/debug.lib.inc.test.php');//this file need be to the finish of the tests
       */ 
            
        /**This files has metadata, is not available to test*/
        //$this->addFile(api_get_path(SYS_TEST_PATH).'/main/inc/lib/xht.lib.test.php');
	    //$this->addFile(api_get_path(SYS_TEST_PATH).'/main/inc/lib/xmd.lib.test.php');
	    
	    /**This files are not used and is not finished implement, is not available to test*/
	    //$this->addFile(api_get_path(SYS_TEST_PATH).'/main/exercice/export/qti/qti_classes.test.php');
		//$this->addFile(api_get_path(SYS_TEST_PATH).'/main/exercice/export/qti2/qti2_export.test.php');
		//$this->addFile(api_get_path(SYS_TEST_PATH).'/main/exercice/export/exercise_import.inc.test.php');
		//$this->addFile(api_get_path(SYS_TEST_PATH).'/main/exercice/export/scorm/scorm_export.test.php');
		
		/**EXERCISES**/
		//$this->addFile(api_get_path(SYS_TEST_PATH).'/main/exercice/answer.class.test.php');
		//$this->addFile(api_get_path(SYS_TEST_PATH).'/main/exercice/exercise_result.class.test.php');
		//$this->addFile(api_get_path(SYS_TEST_PATH).'/main/inc/lib/exercise_show_functions.lib.test.php');
		//$this->addFile(api_get_path(SYS_TEST_PATH).'/main/exercice/exercise.class.test.php');
		/**This files have problem with class and call objects, is not available to test
		//$this->addFile(api_get_path(SYS_TEST_PATH).'/main/exercice/exercise.lib.test.php');
	  	//$this->addFile(api_get_path(SYS_TEST_PATH).'/main/exercice/fill_blanks.class.test.php');
	  	//$this->addFile(api_get_path(SYS_TEST_PATH).'/main/exercice/freeanswer.class.test.php');
	  	*/
	  	
	  	/**FORUM*/
	  	//$this->addFile(api_get_path(SYS_TEST_PATH).'main/forum/forumfunction.inc.test.php');
	  	
        /**GRADEBOOK*/
		//$this->addFile(api_get_path(SYS_TEST_PATH).'main/gradebook/lib/be/attendancelink.class.test.php');                
		//$this->addFile(api_get_path(SYS_TEST_PATH).'main/gradebook/lib/be/category.class.test.php');
		//$this->addFile(api_get_path(SYS_TEST_PATH).'main/gradebook/lib/be/dropboxlink.class.test.php');
		//$this->addFile(api_get_path(SYS_TEST_PATH).'main/gradebook/lib/be/evaluation.class.test.php');
		//$this->addFile(api_get_path(SYS_TEST_PATH).'main/gradebook/lib/be/exerciselink.class.test.php');
		//$this->addFile(api_get_path(SYS_TEST_PATH).'main/gradebook/lib/be/forumthreadlink.class.test.php');
	    
	    /**NEW SCORM*/
	    //$this->addTestFile(dirname(__FILE__).'/main/newscorm/openoffice_presentation.class.test.php');
    }
}
$test = &new TestsSuite();
?>