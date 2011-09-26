<?php
require_once(api_get_path(SYS_CODE_PATH).'newscorm/learnpath.class.php');
require_once(api_get_path(SYS_CODE_PATH).'newscorm/scorm.class.php');

class TestScormClass extends UnitTestCase {
/*
	public function testScorm() {
		//ob_start();
		$course_code = 'COURSETEST';
		$resource_id = 1;
		$user_id = 1;
		$obj = new scorm($course_code, $resource_id, $user_id); 
		$res = $obj->scorm($course_code=null,$resource_id=null,$user_id=null);
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}*/

	public function testOpen() {
		//ob_start();
		$id = 1;
		$res = scorm::open($id);
		$this->assertTrue(is_null($res)); 
		//ob_end_clean();
		//var_dump($res);
	}		
	
	public function testParseManifest() {
		//ob_start();
		$course_code = 'COURSETEST';
		$resource_id = 1;
		$user_id = 1;
		$obj = new scorm($course_code, $resource_id, $user_id); 
		$res = $obj->parse_manifest($file='');
		$this->assertTrue(is_null($res)); 
		//ob_end_clean();
		//var_dump($res);
	}	
	
	public function testImportManifest() {
		//ob_start();
		$course_code = 'COURSETEST';
		$resource_id = 1;
		$user_id = 1;
		$obj = new scorm($course_code, $resource_id, $user_id); 
		$res = $obj->import_manifest($course_code = 'COURSETEST');
		$this->assertTrue(is_null($res)); 
		//ob_end_clean();
		//var_dump($res);
	}
	/*
	public function testImportLocalPackage() {
		//ob_start();
		$course_code = 'COURSETEST';
		$resource_id = 1;
		$user_id = 1;
		$obj = new scorm($course_code, $resource_id, $user_id); 
		$res = $obj->import_local_package($file_path,$current_dir='');
		$this->assertTrue(is_string($res)); 
		//ob_end_clean();
		//var_dump($res);
	}
	
	public function testImportPackage() {
		//ob_start();
		$course_code = 'COURSETEST';
		$resource_id = 1;
		$user_id = 1;
		$obj = new scorm($course_code, $resource_id, $user_id); 
		$res = $obj->import_package($zip_file_info,$current_dir = '');
		$this->assertTrue(is_string($res)); 
		//ob_end_clean();
		//var_dump($res);
	}*/
	
	public function testSetProximity() {
		//ob_start();
		$course_code = 'COURSETEST';
		$resource_id = 1;
		$user_id = 1;
		$obj = new scorm($course_code, $resource_id, $user_id); 
		$res = $obj->set_proximity($proxy='');
		$this->assertTrue(is_bool($res)); 
		//ob_end_clean();
		//var_dump($res);
	}
	
	public function testSetTheme() {
		//ob_start();
		$course_code = 'COURSETEST';
		$resource_id = 1;
		$user_id = 1;
		$obj = new scorm($course_code, $resource_id, $user_id); 
		$res = $obj->set_theme($theme='Chamilo');
		$this->assertTrue(is_bool($res)); 
		//ob_end_clean();
		//var_dump($res);
	}
	
	public function testSetPreviewImage() {
		//ob_start();
		$course_code = 'COURSETEST';
		$resource_id = 1;
		$user_id = 1;
		$obj = new scorm($course_code, $resource_id, $user_id); 
		$res = $obj->set_preview_image($preview_image='');
		$this->assertTrue(is_bool($res)); 
		//ob_end_clean();
		//var_dump($res);
	}
	
	public function testSetAuthor() {
		//ob_start();
		$course_code = 'COURSETEST';
		$resource_id = 1;
		$user_id = 1;
		$obj = new scorm($course_code, $resource_id, $user_id); 
		$res = $obj->set_author($author='');
		$this->assertTrue(is_bool($res)); 
		//ob_end_clean();
		//var_dump($res);
	}
	
	public function testSetMaker() {
		//ob_start();
		$course_code = 'COURSETEST';
		$resource_id = 1;
		$user_id = 1;
		$obj = new scorm($course_code, $resource_id, $user_id); 
		$res = $obj->set_maker($maker='');
		$this->assertTrue(is_bool($res)); 
		//ob_end_clean();
		//var_dump($res);
	}
	
	public function testExportZip() {
		//ob_start();
		$course_code = 'COURSETEST';
		$resource_id = 1;
		$user_id = 1;
		$obj = new scorm($course_code, $resource_id, $user_id); 
		$res = $obj->export_zip($lp_id=null);
		$this->assertTrue(is_bool($res)); 
		//ob_end_clean();
		//var_dump($res);
	}
	
	public function testGetResPath() {
		//ob_start();
		$res = scorm::get_res_path($id=1);
		$this->assertTrue(is_string($res)); 
		//ob_end_clean();
		//var_dump($res);
	}
	
	public function testGetResType() {
		//ob_start();
		$res = scorm::get_res_type($id = 1);
		$this->assertTrue(is_string($res)); 
		//ob_end_clean();
		//var_dump($res);
	}
	
	public function testGetTitle() {
		//ob_start();
		$res = scorm::get_title();
		$this->assertTrue(is_string($res)); 
		//ob_end_clean();
		//var_dump($res);
	}
	
	public function testReimportManifest() {
		ob_start();
		$course_code = 'COURSETEST';
		$resource_id = 1;
		$user_id = 1;
		$obj = new scorm($course_code, $resource_id, $user_id); 
		$res = $obj->reimport_manifest($course  = 'COURSETEST',$lp_id=null,$imsmanifest_path='');
		$this->assertTrue(is_bool($res)); 
		ob_end_clean();
		//var_dump($res);
	}
}
?>