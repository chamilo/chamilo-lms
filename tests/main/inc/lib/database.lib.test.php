<?php
require_once(api_get_path(LIBRARY_PATH).'database.lib.php');
require_once(api_get_path(LIBRARY_PATH).'add_course.lib.inc.php');
require_once(api_get_path(LIBRARY_PATH).'course.lib.php');
class TestDatabase extends UnitTestCase {

	 public $dbase;
	 public function TestDatabase() {
	 	$this->UnitTestCase('data base test');
	 }

	 public function setUp() {
	 	global $_configuration;
 	 	$this->dbase = new Database();
 	 	
 	 	$course_datos = array(
				'wanted_code'=> 'CURSO1',
				'title'=>'CURSO1',
				'tutor_name'=>'R. J. Wolfagan',
				'category_code'=>'2121',
				'course_language'=>'english',
				'course_admin_id'=>'1211',
				'db_prefix'=> $_configuration['db_prefix'],
				'firstExpirationDelay'=>'112'
				);
		$res = create_course($course_datos['wanted_code'], $course_datos['title'],
							 $course_datos['tutor_name'], $course_datos['category_code'],
							 $course_datos['course_language'],$course_datos['course_admin_id'],
							 $course_datos['db_prefix'], $course_datos['firstExpirationDelay']);
 	 	
	 }

	 public function tearDown() {
	 	$this->dbase = null;
	 	$code = 'CURSO1';				
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
	
	public function testAffectedRows() {
		$res=$this->dbase->affected_rows();
		$this->assertTrue(is_numeric($res));
	}

	public function testCountRows() {
		$table='class';
		$res=$this->dbase->count_rows($table);
		$this->assertTrue(is_numeric($res));
	}

	public function testError() {
		$res=$this->dbase->error();
		$this->assertTrue(is_string($res));
	}

	public function testEscapeString() {
		$string='Lore"May';
		$res=$this->dbase->escape_string($string);
		//print_r($string);
		$this->assertTrue(is_string($res));
	}

	public function testFetchArray() {
		$sql = 'select 1';
		$res=Database::query($sql,__FILE__,__LINE__);
		$resu=$this->dbase->fetch_array($res);
		$this->assertTrue(is_array($resu));
		$this->assertFalse(is_null($resu));
	}

	public function testFetchArrayError() {
		$sql ="SELECT  1";
		$res=Database::query($sql,__FILE__,__LINE__);
		$resu=$this->dbase->fetch_array($res);
		$this->assertTrue(is_array($resu));
	}

	function testFetchObject() {
		$sql ="SELECT  1";
		$res=Database::query($sql,__FILE__,__LINE__);
		$resu=$this->dbase->fetch_object($res);
		$this->assertTrue(is_object($resu));
	}

	function testFetchRow() {
		$sql ="SELECT  1";
		$res=Database::query($sql,__FILE__,__LINE__);
		$resu=$this->dbase->fetch_row($res);
		$this->assertTrue(is_array($resu));
	}

	function testFixDatabaseParameterEmpty() {
		$course_info = api_get_course_info();
		$database_name= $course_info[""];
		$res=$this->dbase->fix_database_parameter($database_name);
		if(!is_null($res)) :
		$this->assertTrue(is_string($res));
		endif;
		//var_dump($res);
	}

	function testFixDatabaseParameterReturnString() {
		$course_info = api_get_course_info();
		$database_name= $course_info["dbName"];
		$res=$this->dbase->fix_database_parameter($course_info);
		$this->assertTrue(is_string($res));
	}

	function testFormatGluedCourseTableName()  {
		$database_name_with_glue='';
		$table='';
		$res=$this->dbase->format_glued_course_table_name($database_name_with_glue, $table);
		$this->assertTrue(is_string($res));
	}

	function testFormatTableName() {
		$database='';
		$table='';
		$res=$this->dbase->format_table_name($database, $table);
		$this->assertTrue(is_string($res));
	}

	function testGenerateAbstractCourseFieldNames() {
		$result_array='';
		$res=$this->dbase->generate_abstract_course_field_names($result_array);
		$this->assertTrue(is_array($res));
	}

	function testGenerateAbstractUserFieldNames() {
		$result_array='';
		$res=$this->dbase->generate_abstract_user_field_names($result_array);
		$this->assertTrue(is_array($res));
	}

	function get_course_by_category() {
		$category_id='1';
		$res=$this->dbase->get_course_by_category($category_id);
		$this->assertTrue(is_string($res));
	}

	function testGetCourseChatConnectedTable() {
		$database_name='dokeosla';
		$res=$this->dbase->get_course_chat_connected_table($database_name);
		$this->assertTrue(is_string($res));
	}

	function testGetCourseInfo() {
		$course_code='AYDD';
		$res=$this->dbase->get_course_info($course_code);
		$this->assertTrue(is_array($res));
	}

	function testGetCourseList() {
		$sql_query = "SELECT * FROM $table";
		$res=$this->dbase->get_course_list($sql_query);
		$this->assertTrue(is_array($res));
	}

	function testGetCourseTable() {
		$short_table_name='';
		$database_name='';
		$res=$this->dbase->get_course_table($short_table_name,$database_name);
		$this->assertTrue(is_string($res));
	}

	function testGetCourseTableFromCode() {
		$course_code='AYDD';
		$table='course';
		$ret = NULL;
		$res=$this->dbase->get_course_table_from_code($course_code, $table);
		$this->assertTrue(is_string($res));
	}

	function testGetCourseTablePrefix() {
		global $_configuration;
		$res=$this->dbase->get_course_table_prefix($_configuration);
		$this->assertTrue(is_string($res));
	}

	function testGetCurrentCourseDatabase() {
		$res=$this->dbase->get_current_course_database();
		if(!is_null($res)) :
		$this->assertTrue(is_string($res));
		endif;
		//var_dump($res);
	}

	function testGetCurrentCourseGluedDatabase() {
		$res=$this->dbase->get_current_course_glued_database();
		if(!is_null($res)):
		$this->assertTrue(is_string($res));
		endif;
		//var_dump($res);
	}

	function testGetDatabaseGlue()
	{
		global $_configuration;
		$res=$this->dbase->get_database_glue($_configuration);
		$this->assertTrue(is_string($res));
	}

	function testGetDatabaseNamePrefix() {
		global $_configuration;
		$res=$this->dbase->get_database_name_prefix($_configuration);
		$this->assertTrue(is_string($res));
	}

	function testGetMainDatabase() {
		global $_configuration;
		$res=$this->dbase->get_main_database();
		$this->assertTrue(is_string($res));
	}

	function testGetMainTable() {
		$short_table_name='';
		$res=$this->dbase->get_main_table($short_table_name);
		$this->assertTrue(is_string($res));
	}

	function testGetScormDatabase() {
		global $_configuration;
		$res=$this->dbase->get_scorm_database();
		$this->assertTrue(is_string($res));
	}

		function testGetScorm_table() {
		$short_table_name='';
		$res=$this->dbase->get_scorm_table($short_table_name);
		$this->assertTrue(is_string($res));
		$this->assertTrue($res);
	}

	function testGetStatisticDatabase() {
		global $_configuration;
		$res=$this->dbase->get_statistic_database($_configuration);
		$this->assertTrue(is_string($res));
		$this->assertTrue($res);
	}

	function testGetStatisticTable() {
		$short_table_name='';
		$res=$this->dbase->get_statistic_table($short_table_name);
		$this->assertTrue(is_string($res));
		$this->assertTrue($res);
	}

	function testGetUserInfoFromId() {
		$user_id = '';
		$res=$this->dbase->get_user_info_from_id($user_id);
		$this->assertTrue(is_array($res));
		$this->assertTrue($res);
		//var_dump($res);
	}

	function testGetUserPersonalDatabase() {
		global $_configuration;
		$res=$this->dbase->get_user_personal_database($_configuration);
		$this->assertTrue(is_string($res));
		$this->assertTrue($res);
	}

	function testGetUserPersonalTable(){
		$short_table_name='';
		$res=$this->dbase->	get_user_personal_table($short_table_name);
		$this->assertTrue(is_string($res));
		$this->assertTrue($res);
	}

	function testGlueCourseDatabaseName() {
		$database_name='';
		$res=$this->dbase->glue_course_database_name($database_name);
		$this->assertTrue(is_string($res));
		$this->assertTrue($res);
	}

	function testInsertId() {
		$res=$this->dbase->insert_id();
		$this->assertTrue(is_numeric($res));
	}

	function testNumRows() {
		$sql= 	"SELECT * FROM chamilo_main.user";
		$res = Database::query($sql);		
		$resul=Database::num_rows($res);
		$this->assertTrue(is_numeric($resul));
		//var_Dump($res);
	}

	function testQuery() {
		$sql ="SELECT 1";
		$res=$this->dbase->query($sql,__FILE__,__LINE__);
		$this->assertTrue(is_resource($res));
	}

	function testResult() {
		$sql="SELECT * FROM chamilo_main.user";
		$resource= Database::query($sql,__FILE__,__LINE__);
		$row= 1;
		$res= Database::result($resource, $row);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	function testStoreResult(){
		$sql="SELECT 1";
		$resource=$this->dbase->query($sql,__FILE__,__LINE__);
		$res = $this->dbase->store_result($resource);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}

}
?>