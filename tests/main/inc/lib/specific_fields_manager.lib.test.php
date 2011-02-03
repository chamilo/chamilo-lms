<?php
require_once(api_get_path(LIBRARY_PATH).'specific_fields_manager.lib.php');

class TestSpecificFieldsManager extends UnitTestCase {

    public function __construct() {
        $this->UnitTestCase('Specific (extra) fields library - main/inc/lib/specific_fields_manager.lib.test.php');
    }

	function testadd_specific_field() {
		$name='';
		$res=add_specific_field($name);
		if(!is_bool($res)) {$this->assertTrue(is_string($res));}
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	function testadd_specific_field_value() {
		$id_specific_field='';
		$course_id='';
		$tool_id='';
		$ref_id='';
		$value='';
		$res=add_specific_field_value($id_specific_field, $course_id, $tool_id, $ref_id, $value);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	function testdelete_all_specific_field_value() {
		$course_id='';
		$id_specific_field='';
		$tool_id='';
		$ref_id='';
		$res=delete_all_specific_field_value($course_id, $id_specific_field, $tool_id, $ref_id);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	function testdelete_all_values_for_item() {
		$course_id='';
		$tool_id='';
		$ref_id='';
		$id_specific_field='';
		$res=delete_all_specific_field_value($course_id, $id_specific_field, $tool_id, $ref_id);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	function testdelete_specific_field() {
		$id='';
		$res=delete_specific_field($id);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	function testedit_specific_field() {
		$id='';
		$name='';
		$res=edit_specific_field($id,$name);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	function testget_specific_field_code_from_name() {
		$name='';
		$res=get_specific_field_code_from_name($name);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	function testget_specific_field_list() {
		$res=get_specific_field_list($conditions = array(), $order_by = array());
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}

	function testget_specific_field_values_list() {
		$res=get_specific_field_values_list($conditions = array(), $order_by = array());
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}

	function testget_specific_field_values_list_by_prefix() {
		$prefix='';
		$course_code='';
		$tool_id='';
		$ref_id=1;
		$table_sf = Database :: get_main_table(TABLE_MAIN_SPECIFIC_FIELD);
  		$table_sfv = Database :: get_main_table(TABLE_MAIN_SPECIFIC_FIELD_VALUES);
		$sql = sprintf($sql, $table_sf, $table_sfv, $prefix, $course_code, $tool_id, $ref_id);
		$res=get_specific_field_values_list_by_prefix($prefix, $course_code, $tool_id, $ref_id);
		$return_array = array();
		$this->assertTrue(is_null($res));
		$this->assertTrue(is_array($return_array));
		//var_dump($res);
		//var_dump($return_array);
	}
}
?>
