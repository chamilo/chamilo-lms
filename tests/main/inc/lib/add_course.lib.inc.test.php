<?php
/* For licensing terms, see /license.txt */

require_once api_get_path(LIBRARY_PATH).'add_course.lib.inc.php';
require_once api_get_path(LIBRARY_PATH).'course.lib.php';

class TestAddCourse extends UnitTestCase {

    function TestAddCourse() {
        $this->UnitTestCase('Courses creation - main/inc/lib/add_course.lib.inc.test.php');
    }
/*
    function TestCreateCourse(){
        global $_configuration;
        $course_datos = array(
                'wanted_code'=> 'testcourse',
                'title'=>'prueba01',
                'tutor_name'=>'John Doe',
                'category_code'=>'Lang',
                'course_language'=>'english',
                'course_admin_id'=>'1',
                'db_prefix'=> $_configuration['db_prefix'],
                'firstExpirationDelay'=>'120'
                );
        $res = create_course($course_datos['wanted_code'], $course_datos['title'],
                             $course_datos['tutor_name'], $course_datos['category_code'],
                             $course_datos['course_language'],$course_datos['course_admin_id'],
                             $course_datos['db_prefix'], $course_datos['firstExpirationDelay']);
        //should be code string if succeeded (false if failed)
        $this->assertTrue(is_string($res));
    }*/

    function testRegisterCourse() {
        global $_configuration;
         $course = array(
            'courseSysCode'=> 'testcourse',
            'courseScreenCode' =>'testcourse',
            'courseRepository' =>'testcourse',
            'courseDbName' => $_configuration['db_prefix'].'testcourse',
            'titular' =>'John Doe',
            'category' =>'Lang',
            'title' =>'test course',
            'course_language' =>'english',
            'uidCreator'=> '1',
            );
        $res = register_course($course['courseSysCode'],$course['courseScreenCode'],
                            $course['courseRepository'],$course['courseDbName'],
                            $course['titular'],$course['category'],$course['title'],
                            $course['course_language'],$course['uidCreator'],
                            null,null
                            );

        $this->assertTrue($res === 0);
        $res = CourseManager::delete_course($course['courseSysCode']);

    }

    function TestGenerateCourseCode(){
        global $charset;
        $course_title = 'testcourse';
        $res = generate_course_code($course_title);
        $this->assertTrue($res);
    }


    function TestDefineCourseKeys(){
        global $prefixAntiNumber, $_configuration;
        $wantedCode = generate_course_code($wantedCode);
        $res = define_course_keys(generate_course_code($wantedCode), null, null, null,null, null);
        $this->assertTrue($res);
    }
    
    function TestBrowseFolders(){
        $browse = array('path'=>'','file'=>'','media'=>'');
        $res = browse_folders($browse['path'], $browse['files'],$browse['media']);
        $this->assertFalse($res);
    }
    /*
    // 1 excepcion
    function TestSortPictures(){
        $picture = array('files'=>'science.jpg', 'type'=>'jpg');
        $res = sort_pictures($picture['file'],$picture['type']);
        $this->assertTrue(is_array($res));
        //var_dump($res);
    }
    */
    /*
    function TestFillCourseRepository(){
        $courseRepository = 'testcourse';
        $res = fill_course_repository($courseRepository);
        $this->assertTrue($res);
    }
*/
    function TestLang2db(){
        $string = 'test';
        $res = lang2db($string);
        $this->assertTrue($res);
    }

    function TestFillDbCourse(){
        global $_configuration, $_user;
        $courseDbName = $_configuration['table_prefix'].$courseDbName.$_configuration['db_glue'];
        $courseRepository = (api_get_path(SYS_COURSE_PATH).$courseRepository . "/dropbox/.htaccess");
        $language = 'english';
        $language_interface = $language;
        $default_document_array = array();
        $sys_course_path = api_get_path(SYS_COURSE_PATH);
        $courseDbName = $_configuration['db_prefix'].$courseDbName.$_configuration['db_glue'];
        $courseRepository = 'testcourse';
        $language = 'english';
        $default_document_array ='testdocument';
        $res = fill_Db_course($courseDbName, $courseRepository, $language,array());
        $this->assertTrue($res === 0);
    }

    function TestString2Binary(){
        $variable = true;
        $res = string2binary($variable);
        $this->assertTrue($res);
    }

    function TestCheckArchive(){
        $dirarchive = api_get_path(SYS_PATH);
        $pathToArchive = $dirarchive.'archive';
        $res = checkArchive($pathToArchive);
        $this->assertTrue($res === TRUE);
    }

    public function TestDeleteCourse(){
        $code = 'testcourse';
        $res = CourseManager::delete_course($code);
        $path = api_get_path(SYS_PATH).'archive';
        if ($handle = opendir($path)) {
            while (false !== ($file = readdir($handle))) {
                if (strpos($file,$code)!==false) {
                    if (is_dir($path.'/'.$file)) {
                        rmdirr($path.'/'.$file);
                    }
                }
            }
            closedir($handle);
        }
    }

}
