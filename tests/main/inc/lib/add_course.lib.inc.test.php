<?php
require_once(api_get_path(LIBRARY_PATH).'add_course.lib.inc.php');

class TestAddCourse extends UnitTestCase {
	
	function TestAddCourse() {
        $this->UnitTestCase('Courses creation tests');
    }
	
	function testRegisterCourseValue() {
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
}