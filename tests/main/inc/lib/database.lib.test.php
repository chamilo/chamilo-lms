<?php
require_once(api_get_path(LIBRARY_PATH).'database.lib.php');

class TestDatabase extends UnitTestCase {

	 public $dbase;
	 public function TestDatabase() {
	 	$this->UnitTestCase('data base test');
	 }

	 public function setUp() {
 	 	$this->dbase = new Database();
	 }

	 public function tearDown() {
	 	$this->dbase = null;
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
		$res=$this->dbase->get_scorm_table();
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
	}

	function testGetUserPersonalDatabase() {
		global $_configuration;
		$res=$this->dbase->get_user_personal_database($_configuration);
		$this->assertTrue(is_string($res));
		$this->assertTrue($res);
	}

	function testGetUserPersonalTable(){
		$short_table_name='';
		$res=$this->dbase->	get_user_personal_table();
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
		$res='';
		$resul=$this->dbase->num_rows($res);
		$this->assertTrue(is_string($res));
	}

	function testQuery() {
		$sql ="SELECT 1";
		$res=$this->dbase->query($sql,__FILE__,__LINE__);
		$this->assertTrue(is_resource($res));
	}

	function testResult() {
		$sql="SELECT 1";
		$resource=$this->dbase->query($sql,__FILE__,__LINE__);
		$rows='1';
		$res=$this->dbase->result($resource,$rows);
		//var_dump($res);
		$this->assertTrue(is_bool($res));
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