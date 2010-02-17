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
//Need to start to load the settings in the setup and teardown
$incdir = dirname(__FILE__).'/../main/inc/'; 
require_once $incdir.'global.inc.php';

//Files than need simpletest
require_once 'simpletest/unit_tester.php';
require_once 'simpletest/web_tester.php';
require_once 'simpletest/mock_objects.php';
require_once 'simpletest/autorun.php';

/*
==============================================================================
		TEST SUITE
==============================================================================
*/

class TestsSuite extends TestSuite {
    function TestsSuite() {
    	$this->TestSuite('All tests suite');
/*		$this->addTestFile(dirname(__FILE__).'/main/inc/lib/database.lib.test.php');
		$this->addTestFile(dirname(__FILE__).'/main/inc/lib/add_course.lib.inc.test.php');
		$this->addTestFile(dirname(__FILE__).'/main/inc/lib/course.lib.test.php');
		$this->addTestFile(dirname(__FILE__).'/main/inc/banner.lib.test.php');
		$this->addTestFile(dirname(__FILE__).'/main/admin/calendar.lib.test.php');
		$this->addTestFile(dirname(__FILE__).'/main/inc/lib/surveymanager.lib.test.php');
		$this->addTestFile(dirname(__FILE__).'/main/inc/lib/session_handler.class.test.php');
        $this->addTestFile(dirname(__FILE__).'/main/inc/lib/sessionmanager.lib.test.php');
        $this->addTestFile(dirname(__FILE__).'/main/inc/lib/classmanager.lib.test.php');
        $this->addTestFile(dirname(__FILE__).'/main/inc/lib/export.lib.inc.test.php');
        $this->addTestFile(dirname(__FILE__).'/main/inc/lib/legal.lib.test.php');
        $this->addTestFile(dirname(__FILE__).'/main/inc/lib/mail.lib.inc.test.php');   
        $this->addTestFile(dirname(__FILE__).'/main/inc/lib/message.lib.test.php');
        $this->addTestFile(dirname(__FILE__).'/main/inc/lib/online.inc.test.php'); 
        $this->addTestFile(dirname(__FILE__).'/main/inc/lib/security.lib.test.php');
		$this->addTestFile(dirname(__FILE__).'/main/inc/lib/specific_fields_manager.lib.test.php');  
   	    $this->addTestFile(dirname(__FILE__).'/main/inc/lib/social.lib.test.php');
   	    $this->addTestFile(dirname(__FILE__).'/main/inc/lib/sortabletable.class.test.php'); 
   	    $this->addTestFile(dirname(__FILE__).'/main/inc/lib/statsUtils.lib.inc.test.php');
   	    $this->addTestFile(dirname(__FILE__).'/main/inc/lib/tablesort.lib.test.php');
   	    $this->addTestFile(dirname(__FILE__).'/main/inc/lib/text.lib.test.php');
   	    $this->addTestFile(dirname(__FILE__).'/main/inc/lib/tracking.lib.test.php');
		$this->addTestFile(dirname(__FILE__).'/main/inc/lib/blog.lib.test.php');
		$this->addTestFile(dirname(__FILE__).'/main/inc/lib/urlmanager.lib.test.php');
		$this->addTestFile(dirname(__FILE__).'/main/inc/lib/stats.lib.inc.test.php');
		$this->addTestFile(dirname(__FILE__).'/main/inc/course_document.lib.test.php');
        $this->addTestFile(dirname(__FILE__).'/main/inc/tool_navigation_menu.inc.test.php');
        $this->addTestFile(dirname(__FILE__).'/main/inc/lib/display.lib.test.php');
		$this->addTestFile(dirname(__FILE__).'/main/inc/lib/document.lib.test.php');
		$this->addTestFile(dirname(__FILE__).'/main/inc/lib/events.lib.inc.test.php');
		$this->addTestFile(dirname(__FILE__).'/main/inc/lib/fileDisplay.lib.test.php');
		$this->addTestFile(dirname(__FILE__).'/main/inc/lib/fileManage.lib.test.php');
	    $this->addTestFile(dirname(__FILE__).'/main/inc/lib/geometry.lib.test.php');
    	$this->addTestFile(dirname(__FILE__).'/main/admin/statistics/statistics.lib.test.php');
     	$this->addTestFile(dirname(__FILE__).'/main/inc/lib/access_url_edit_courses_to_url_functions.lib.test.php');
    	$this->addTestFile(dirname(__FILE__).'/main/inc/lib/access_url_edit_sessions_to_url_functions.lib.test.php');
    	$this->addTestFile(dirname(__FILE__).'/main/inc/lib/access_url_edit_users_to_url_functions.lib.test.php');
    	$this->addTestFile(dirname(__FILE__).'/main/inc/lib/add_courses_to_sessions_functions.lib.test.php');
    	$this->addTestFile(dirname(__FILE__).'/main/inc/lib/add_many_session_to_category_functions.lib.test.php');
    	//$this->addTestFile(dirname(__FILE__).'/main/admin/access_urls.test.php');
    	$this->addTestFile(dirname(__FILE__).'/main/admin/sub_language.class.test.php');
    	$this->addTestFile(dirname(__FILE__).'/main/inc/lib/add_courses_to_sessions_functions.lib.test.php');
    	$this->addTestFile(dirname(__FILE__).'/main/auth/lost_password.lib.test.php');
        $this->addTestFile(dirname(__FILE__).'/main/auth/openid/xrds.lib.test.php');
        $this->addTestFile(dirname(__FILE__).'/main/chat/chat_functions.lib.test.php');
        $this->addTestFile(dirname(__FILE__).'/main/conference/get_translation.lib.test.php');
        $this->addTestFile(dirname(__FILE__).'/main/exercice/hotpotatoes.lib.test.php');      
        $this->addTestFile(dirname(__FILE__).'/main/newscorm/scorm.lib.test.php');
        $this->addTestFile(dirname(__FILE__).'/main/survey/survey.lib.test.php');
        $this->addTestFile(dirname(__FILE__).'/main/user/userInfoLib.test.php');        
        $this->addTestFile(dirname(__FILE__).'/main/webservices/user_import/import.lib.test.php');        
        $this->addTestFile(dirname(__FILE__).'/main/work/work.lib.test.php');
        $this->addTestFile(dirname(__FILE__).'/main/inc/lib/glossary.lib.test.php');
        $this->addTestFile(dirname(__FILE__).'/main/inc/lib/notebook.lib.test.php');
        $this->addTestFile(dirname(__FILE__).'/main/permissions/permissions_functions.inc.test.php');     
        $this->addTestFile(dirname(__FILE__).'/main/resourcelinker/resourcelinker.inc.test.php');
        $this->addTestFile(dirname(__FILE__).'/main/survey/survey.lib.test.php');
        $this->addTestFile(dirname(__FILE__).'/main/dropbox/dropbox_class.inc.test.php');
	  	$this->addTestFile(dirname(__FILE__).'/main/dropbox/dropbox_functions.inc.test.php');
	  	$this->addTestFile(dirname(__FILE__).'/main/search/search_suggestions.test.php');
	  	$this->addTestFile(dirname(__FILE__).'/main/exercice/export/qti2/qti2_classes.test.php');
	  	$this->addTestFile(dirname(__FILE__).'/main/exercice/export/scorm/scorm_classes.test.php');
        $this->addTestFile(dirname(__FILE__).'/main/inc/lib/usermanager.lib.test.php'); 
        $this->addTestFile(dirname(__FILE__).'/main/inc/lib/groupmanager.lib.test.php');	 
        $this->addTestFile(dirname(__FILE__).'/main/inc/lib/image.lib.test.php'); 
        $this->addTestFile(dirname(__FILE__).'/main/inc/lib/import.lib.test.php');
        $this->addTestFile(dirname(__FILE__).'/main/inc/lib/internationalization.lib.test.php');  
        $this->addTestFile(dirname(__FILE__).'/main/inc/lib/system_announcements.lib.test.php');
        $this->addTestFile(dirname(__FILE__).'/main/inc/lib/fileUpload.lib.test.php');
        $this->addTestFile(dirname(__FILE__).'/main/inc/lib/main_api.lib.test.php');//this file delete the course     
        $this->addTestFile(dirname(__FILE__).'/main/inc/lib/debug.lib.inc.test.php');//this file need be to the finish of the tests
       */ 
       
        /**This file was removed, now the functions was moved to install.lib*/
		//require_once $maindir.'install/install_upgrade.lib.php';
		//$this->addTestFile(dirname(__FILE__).'/main/install/install_upgrade.lib.test.php');
       
        /**This files has metadata*/
        //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/xht.lib.test.php');
	    //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/xmd.lib.test.php');
	    
	    /**This files are not used and is not finished implement*/
	    //$this->addTestFile(dirname(__FILE__).'/main/exercice/export/qti/qti_classes.test.php');
		//$this->addTestFile(dirname(__FILE__).'/main/exercice/export/qti2/qti2_export.test.php');
		//$this->addTestFile(dirname(__FILE__).'/main/exercice/export/exercise_import.inc.test.php');
		//$this->addTestFile(dirname(__FILE__).'/main/exercice/export/scorm/scorm_export.test.php');
		
		
		/**EXERCISES**/
		//$this->addTestFile(dirname(__FILE__).'/main/exercice/answer.class.test.php');
		//$this->addTestFile(dirname(__FILE__).'/main/exercice/exercise_result.class.test.php');
		//$this->addTestFile(dirname(__FILE__).'/main/inc/lib/exercise_show_functions.lib.test.php');
		//$this->addTestFile(dirname(__FILE__).'/main/exercice/exercise.class.test.php');
		
		/**This files have problem with class and call objects*/
		//$this->addTestFile(dirname(__FILE__).'/main/exercice/exercise.lib.test.php');
	  	//$this->addTestFile(dirname(__FILE__).'/main/exercice/fill_blanks.class.test.php');
	  	//$this->addTestFile(dirname(__FILE__).'/main/exercice/freeanswer.class.test.php');
	  	
	  	
	  	/**FORUM*/
	  	//$this->addFile(dirname(__FILE__).'/main/forum/forumfunction.inc.test.php');
	  	$this->addFile(api_get_path(SYS_TEST_PATH).'main/forum/forumfunction.inc.test.php');
	  	
	    //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/main_api.lib.test.php');      
        //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/debug.lib.inc.test.php');
        
                
	    
    }
}
$test = &new TestsSuite();
?>