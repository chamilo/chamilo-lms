<?php
require_once(api_get_path(LIBRARY_PATH).'add_course.lib.inc.php');

class TestAddCourse extends UnitTestCase {

	function TestAddCourse() {
        $this->UnitTestCase('Courses creation tests');
    }

    function testRegisterCourse() {
        //($courseSysCode, $courseScreenCode, $courseRepository, $courseDbName,
		//$titular, $category, $title, $course_language, $uidCreator, $expiration_date = "", $teachers=array())
	    $course = array(
		    'courseSysCode'=> 'COD12',
		    'courseScreenCode' =>'221',
		    'courseRepository' =>'21',
		    'courseDbName' =>'ARITM',
		    'titular' =>'R. Wofgar',
		    'category' =>'Math',
		    'title' =>'metodologia de calculo diferencial',
		    'course_language' =>'English',
		    'uidCreator'=> '212',
			 	);
	 	$res = register_course($course['courseSysCode'],$course['courseScreenCode'],
	 						   $course['courseRepository'],$course['courseDbName'],$course['titular'],
	 						   $course['category'],$course['title'],$course['course_language'],
	 						   $course['uidCreator'],null,null);
	 	$this->assertPattern('/\d/',$res);

	}

    function TestCreateCourse(){
		//$wanted_code, $title, $tutor_name, $category_code,
		//$course_language, $course_admin_id, $db_prefix,
		//$firstExpirationDelay
		$course_datos = array(
				'wanted_code'=> 'COD12',
				'title'=>'metodologia de calculo diferencial',
				'tutor_name'=>'R. J. Wolfagan',
				'category_code'=>'2121',
				'course_language'=>'english',
				'course_admin_id'=>'1211',
				'db_prefix'=>'22',
				'firstExpirationDelay'=>'112'
				);
		//$keys = define_course_keys($wanted_code, "", $db_prefix);
		$res = create_course($course_datos['wanted_code'], $course_datos['title'],
							 $course_datos['tutor_name'], $course_datos['category_code'],
							 $course_datos['course_language'],$course_datos['course_admin_id'],
							 $course_datos['db_prefix'], $course_datos['firstExpirationDelay']);

		$this->assertFalse($res===0);

    }

    function TestGenerateCourseCode(){
    	global $charset;
    	$course_title = 'matemáticas';
    	$res = generate_course_code($course_title);
    	$this->assertTrue($res);

	}

	function TestDefineCourseKeys(){
		//$wantedCode, $prefix4all = "", $prefix4baseName = "",
		//$prefix4path = "", $addUniquePrefix = false, $useCodeInDepedentKeys = true
		global $prefixAntiNumber, $_configuration;
		$wantedCode = generate_course_code($wantedCode);
		$res = define_course_keys(generate_course_code($wantedCode), null, null, null,null, null);
		$this->assertTrue($res);

	}

	function TestPrepareCourseRepository(){
		$courseRepository = '';
		$courseId = '';
		$res = prepare_course_repository($courseRepository, $courseId);
		$this->assertTrue($res===0);
	}

	/**
	 * Function not implemented with test, because the functionality
	 * is very complex.
	 */
	/*
	function TestUpdateDbCourse(){
		global $_configuration;
		$dbcourse = array('courseDbName'=> 'curso');
		$res = update_Db_course($dbcourse['courseDbName']);
		$this->assertFalse($res===0);

	}*/

	function TestBrowseFolders(){
		$browse = array('path'=>'','file'=>'','media'=>'');
		$res = browse_folders($browse['path'], $browse['files'],$browse['media']);
		$this->assertFalse($res);

	}

	function TestSortPictures(){
		$files ='121212';
		$type='asasasasa';
		$res = sort_pictures($files, $type);
		$this->assertFalse($res);

	}

	function TestFillCourseRepository(){
		$courseRepository = '1212sder';
		$res = fill_course_repository($courseRepository);
		$this->assertTrue($res);

	}

	function TestLang2db(){
		$string = 'dsdzxcwqd';
		$res = lang2db($string);
		$this->assertTrue($res);

	}

	function TestFillDbCourse(){
		global $_configuration, $clarolineRepositoryWeb, $_user;
		$courseDbName = $_configuration['table_prefix'].$courseDbName.$_configuration['db_glue'];
		$courseRepository = 'sdffsdf';
		$language = 'english';
		$default_document_array ='1212121';
		$res = fill_Db_course($courseDbName, $courseRepository, $language,$default_document_array);
		$this->assertTrue($res === 0);

	}

	function TestString2Binary(){
		$variable = true;
		$res = string2binary($variable);
		$this->assertTrue($res);


	}

	function TestCheckArchive(){
		$pathToArchive ='';
		$res = checkArchive($pathToArchive);
		$this->assertTrue($res === TRUE);

	}

	/**
	 * Fatal Error at the call to undefined function printVar() Line 2404 in the
	 * add_course.lib.inc.php
	 */
	/*
	function TestReadPropertiesInArchive(){
		$archive='archive';
		$uid = api_get_user_id();
		printVar(dirname($archive), "Zip : ");
		$res = readPropertiesInArchive($archive, $isCompressed = TRUE);
		$this->assertTrue($res);


	 	ob_start();
	 	//readPropertiesInArchive($archive, $isCompressed = TRUE);
		$res = ob_get_contents(readPropertiesInArchive);
		ob_end_clean();
		$this->assertFalse($res);
		}
		*/



}