<?php
require_once(api_get_path(SYS_CODE_PATH).'newscorm/scorm.lib.php');

class TestScormLib extends UnitTestCase {

    public function __construct() {
        $this->UnitTestCase('SCORM library - main/newscorm/scorm.lib.test.php');
    }

/**
 * This public function gets a list of scorm paths located in a given directory
 * @param	string	Base directory path
 * @param	string	Current directory
 * @param	array	Reference to a list of paths that exist in the database
 * @return	array	Array(type=>array(),size=>array(),date=>array())
 */
	public function testget_scorm_paths_from_dir() {
		$basedir='';
		$curdir='';
		$attribute=array('abc');
	 	$res=get_scorm_paths_from_dir($basedir, $curdir, &$attribute);
	 	$this->assertTrue(is_array($res));
	 	//var_dump($res);
	}

/**
 * Detects the SCORM version from an imsmanifest.xml file
 * @param	string	Path to imsmanifest.xml
 * @return	string	SCORM version (1.0,1.1,1.2,1.3)
 * @todo Implement this public function
 */
	public function testget_scorm_version() {
		$path_name = api_get_path(SYS_COURSE_PATH);
		$path=$path_name.'scorm/';
		$res=get_scorm_version($path);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

/**
 * Delete a scorm directory (check for imsmanifest and if found, deletes the related rows in scorm tables also)
 * @param       string          Dir path
 * @return      boolean True on success, false otherwise
 */
	public function testremovescormDirFalse() {
		$path_name = api_get_path(SYS_COURSE_PATH);
		$dir=$path_name.'scorm/';
		$res=removescormDir($dir);
		$this->assertFalse($res);
		//var_dump($res);
	}

	public function testremovescormDirTrue() {
		$path_name = api_get_path(SYS_COURSE_PATH);
		$dir=$path_name.'scorm/';
		$res=removescormDir($dir);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

/**
 * This public function removes a directory if it exists
 * @param               string          Dir path
 * @return      boolean True on success, false otherwise
 * @uses        removescormDir()        to actually remove the directory
 */
	public function testscorm_delete() {
		require_once(api_get_path(LIBRARY_PATH).'fileManage.lib.php');
		$path_name = api_get_path(SYS_COURSE_PATH);
		$file=$path_name.'scorm/';
		$res=scorm_delete($file);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}
}
?>