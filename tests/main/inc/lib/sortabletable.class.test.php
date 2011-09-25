<?php
require_once api_get_path(LIBRARY_PATH).'sortabletable.class.php';
require_once api_get_path(LIBRARY_PATH).'export.lib.inc.php';

class TestSortableTable extends UnitTestCase {

    public function __construct() {
        $this->UnitTestCase('Sortabletable library - main/inc/lib/sortabletable.class.test.php');
    }

	function testdisplay() {
		$instancia = new SortableTable();
		global $charset;
		ob_start();
		$res=$instancia->display();
		$this->assertTrue(is_null($res));
		ob_end_clean();
		//var_dump($res);
	}

	function testfilter_data() {
		$instancia = new SortableTable();
		$row=array();
		$res=$instancia->filter_data($row);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}

	function testget_additional_url_paramstring() {
		$instancia = new SortableTable();
		$res=$instancia->get_additional_url_paramstring();
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	function testget_navigation_html() {
		$instancia = new SortableTable();
		$res=$instancia->get_navigation_html();
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	function testget_page_select_form() {
		$instancia = new SortableTable();
		$res=$instancia->get_page_select_form();
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	function testget_pager() {
		$instancia = new SortableTable();
		$res=$instancia->get_pager();
		$this->assertTrue(is_object($res));
		//var_dump($res);
	}

	function testget_sortable_table_param_string() {
		$instancia = new SortableTable();
		$res=$instancia->get_sortable_table_param_string();
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	function testget_table_data() {
		$instancia = new SortableTable();
		$res=$instancia->get_table_data();
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}

	function testget_table_html() {
		$instancia = new SortableTable();
		$res=$instancia->get_table_html();
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	function testget_table_title() {
		$instancia = new SortableTable();
		$res=$instancia->get_table_title();
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	function testget_total_number_of_items() {
		$instancia = new SortableTable();
		$res=$instancia->get_total_number_of_items();
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}

	function testset_additional_parameters() {
		$instancia = new SortableTable();
		$parameters='';
		$res=$instancia->set_additional_parameters($parameters);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	function testset_column_filter() {
		$instancia = new SortableTable();
		$column='';
		$function='';
		$res=$instancia->set_column_filter($column, $function);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	function testset_form_actions() {
		$instancia = new SortableTable();
		$actions='';
		$checkbox_name = 'id';
		$res=$instancia->set_form_actions($actions, $checkbox_name = 'id');
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	function testset_header() {
		$instancia = new SortableTable();
		$column='';
		$label='';
		$res=$instancia->set_header($column, $label, $sortable = true, $th_attributes = null, $td_attributes = null);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	function testset_other_tables() {
		$instancia = new SortableTable();
		$tablenames='';
		$res=$instancia->set_other_tables($tablenames);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}
}

class TestSortableTableFromArray extends UnitTestCase {

	function testget_table_data() {
		$res=SortableTableFromArray::get_table_data($from=1);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}

	function testget_total_number_of_items() {
		$res=SortableTableFromArray::get_total_number_of_items();
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}
}

class TestSortableTableFromArrayConfig extends UnitTestCase {

	public function testget_table_data() {
		$res=SortableTableFromArray::get_table_data($from=1);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}

	public function testget_total_number_of_items() {
		$res=SortableTableFromArray::get_total_number_of_items();
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}
}
?>
