<?php
/* For licensing terms, see /license.txt */

/**
*	Code library for load functions than are needed to test
*
*	@author Arthur Portugal, Principal author
*	@package chamilo.tests
*/

/**
 * @todo shouldn't these settings be moved to the test_suite.php
 * if these are really configuration then we can make require_once in each tests file.
 * @todo use this file to load in the setup in each file test.
 * @todo use this file to destroy in the teardown in each file test.
 * @todo check for duplication of "require_once" files with test_suite.php
 * @author Arthur Portugal
 */
 
/*
		INIT SECTION
*/
ini_set('memory_limit','256M');
ini_set('max_execution_time','0');

/*
	Included libraries
*/
$maindir = dirname(__FILE__).'/../main/';
$incdir  = dirname(__FILE__).'/../main/inc/';
$libdir  = dirname(__FILE__).'/../main/inc/lib/';

/**Files inside '/../main' */
require_once $maindir.'permissions/permissions_functions.inc.php';
require_once $maindir.'admin/calendar.lib.php';
require_once $maindir.'admin/statistics/statistics.lib.php';
require_once $maindir.'dropbox/dropbox_class.inc.php';
require_once $maindir.'dropbox/dropbox_functions.inc.php';
require_once $maindir.'survey/survey.lib.php';
require_once $maindir.'exercice/export/scorm/scorm_classes.php';
require_once $maindir.'exercice/export/qti2/qti2_classes.php';
require_once $maindir.'exercice/export/exercise_import.inc.php';
require_once $maindir.'exercice/exercise_result.class.php';
require_once $maindir.'exercice/answer.class.php';
require_once $maindir.'exercice/exercise.class.php';
require_once $maindir.'exercice/fill_blanks.class.php';
require_once $maindir.'exercice/freeanswer.class.php';
require_once $maindir.'forum/forumfunction.inc.php';
require_once $maindir.'gradebook/lib/be/gradebookitem.class.php';
require_once $maindir.'gradebook/lib/be/abstractlink.class.php';
require_once $maindir.'gradebook/lib/be/evallink.class.php';
require_once $maindir.'gradebook/lib/be/linkfactory.class.php';
require_once $maindir.'gradebook/lib/be/attendancelink.class.php';
require_once $maindir.'gradebook/lib/be/category.class.php';
require_once $maindir.'gradebook/lib/be/dropboxlink.class.php';
require_once $maindir.'gradebook/lib/be/evaluation.class.php';
require_once $maindir.'gradebook/lib/be/exerciselink.class.php';
require_once $maindir.'gradebook/lib/be/dropboxlink.class.php';
require_once $maindir.'gradebook/lib/be/result.class.php';
require_once $maindir.'gradebook/lib/be/forumthreadlink.class.php';
require_once $maindir.'course_info/download.lib.php';



/**Files inside '/../main/lib/' */
require_once $libdir.'urlmanager.lib.php';
require_once $libdir.'fileDisplay.lib.php';
require_once $libdir.'groupmanager.lib.php';
require_once $libdir.'course.lib.php';
require_once $libdir.'usermanager.lib.php';
require_once $libdir.'social.lib.php';
require_once $libdir.'xht.lib.php';
require_once $libdir.'xmd.lib.php';
require_once $libdir.'formvalidator/FormValidator.class.php';
require_once $libdir.'exercise_show_functions.lib.php';
require_once $libdir.'fileManage.lib.php';
require_once $libdir.'notebook.lib.php';

/**This files need be inside a buffering to clean the objects*/ 
ob_start();
require_once $libdir.'main_api.lib.php';
require_once $libdir.'course_document.lib.php';
require_once $libdir.'banner.lib.php';
require_once $libdir.'add_course.lib.inc.php';
require_once $incdir.'banner.inc.php';
require_once $libdir.'geometry.lib.php';
ob_end_clean();

/**Problem with this file to test objects*/
//require_once $maindir.'exercice/exercise.lib.php';

class TestManager {
	
/* MAIN CODE */

/**
 * This function create in the database a test course and will also load sessions.
 * This name will be change each time is used in the tests.
 * @param string	Course name
 * @return void
 */

function create_test_course($course_code = 'TESTCOURSE') {
		
	/* Table definitions */	
	$table_course 		= Database::get_main_table(TABLE_MAIN_COURSE);
	$course_table 		= Database::get_main_table(TABLE_MAIN_COURSE);
	$course_cat_table 	= Database::get_main_table(TABLE_MAIN_CATEGORY);
	
	global $_configuration, $_user, $_course, $cidReq;
	$cidReq = $course_code;
	
	/*	Check if the course exists	*/
	
	$sql = "SELECT code FROM  $table_course WHERE code = '$cidReq' ";
	$rs = Database::query($sql, __FILE__, __LINE__);
	$row = Database::fetch_row($rs);
	
	/*	Create the course in the database */
	
	if (empty($row[0])) {
	    // Create a course
	    $course_data = array(
	            'wanted_code'=> $cidReq,
	            'title'=>$cidReq,
	            'tutor_name'=>'John Doe',
	            'category_code'=>'LANG',
	            'course_language'=>'spanish',
	            'course_admin_id'=>'001',
	            'db_prefix'=> $_configuration['db_prefix'],
	            'firstExpirationDelay'=>'999'
	            );
	    $res = create_course($course_data['wanted_code'], $course_data['title'],
	                         $course_data['tutor_name'], $course_data['category_code'],
	                         $course_data['course_language'],$course_data['course_admin_id'],
	                         $course_data['db_prefix'], $course_data['firstExpirationDelay']);
	}
	
	
	$sql =  "SELECT course.*, course_category.code faCode, course_category.name faName
	         FROM $course_table
	         LEFT JOIN $course_cat_table
	         ON course.category_code = course_category.code
	         WHERE course.code = '$cidReq'";
	         
	$result = Database::query($sql);
	
	/*	Create the session	*/
	
	if (Database::num_rows($result)>0) {
	    $cData = Database::fetch_array($result);
	    $_cid                            = $cData['code'             ];
	    $_course 						 = array();
	    $_course['id'          ]         = $cData['code'             ]; //auto-assigned integer
	    $_course['name'        ]         = $cData['title'         	 ];
	    $_course['official_code']        = $cData['visual_code'      ]; // use in echo
	    $_course['sysCode'     ]         = $cData['code'             ]; // use as key in db
	    $_course['path'        ]         = $cData['directory'		 ]; // use as key in path
	    $_course['dbName'      ]         = $cData['db_name'          ]; // use as key in db list
	    $_course['dbNameGlu'   ]         = $_configuration['table_prefix'] . $cData['db_name'] . $_configuration['db_glue']; // use in all queries
	    $_course['titular'     ]         = $cData['tutor_name'       ];
	    $_course['language'    ]         = $cData['course_language'  ];
	    $_course['extLink'     ]['url' ] = $cData['department_url'   ];
	    $_course['extLink'     ]['name'] = $cData['department_name'  ];
	    $_course['categoryCode']         = $cData['faCode'           ];
	    $_course['categoryName']         = $cData['faName'           ];
	    $_course['visibility'  ]         = $cData['visibility'		 ];
	    $_course['subscribe_allowed']    = $cData['subscribe'		 ];
	    $_course['unubscribe_allowed']   = $cData['unsubscribe'		 ];
	
	    Session::write('_cid',$_cid);
	    Session::write('_course',$_course);
	}
	   
	/*	Load the session	*/
	
	$_SESSION['_user']['user_id'] = 1;    
	$_SESSION['is_courseAdmin'] = 1;
	$_SESSION['show'] = showall;
	
	/*		Load the user	*/
	
	$_user['user_id'] = $_SESSION['_user']['user_id'];
}

/**
 * This function delete the test course from the database and destroy the sessions. 
 * @param string the course code than will be delete.
 * @return void
 */

function delete_test_course($course_code = 'TESTCOURSE') {
	$res = CourseManager::delete_course($course_code);
	$path = api_get_path(SYS_PATH).'archive';
	
	if ($handle = opendir($path)) {
		while (false !== ($file = readdir($handle))) {
			if (strpos($file,$course_code)!==false) {
				if (is_dir($path.'/'.$file)) {
					rmdirr($path.'/'.$file);
				}
			}
		}
		closedir($handle);
	}
		
	//	Check api session destroy
	if (!headers_sent() && session_id() != "") {
		$res=Session::destroy();
		}
	}
}
