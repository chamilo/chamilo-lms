<?php

define('SORT_DATE', 3);
define('SORT_IMAGE',4);


class TestTablesort extends UnitTestCase{

	//public $table;
	public function TestTablesort(){

		$this->UnitTestCase('All main tablesort function tests');
	}
	/*
	public function setUp(){
		$this->table = new TableSort();
	}

	public function tearDown(){
		$this->table = null;
	}
	*/
	public function testOrderingstring(){
		$txt='abc';
		$res=TableSort::orderingstring($txt);
		$this->assertTrue($res);
		//var_dump($res);
	}

	public function testSortTable(){
		$data= array(a, cd, efd);
		$column = 0;
		$direction = SORT_ASC;
		$type = SORT_REGULAR;
		$res =TableSort::sort_table($data, $column, $direction, $type);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}

	public function testIsNumericColumn(){
		$data=array(aeiou, abcde, acedrf);
		$column=0;
		$res = TableSort::is_numeric_column($data, $column);
		if(!is_numeric($res)):
		$this->assertTrue(is_bool($res));
		endif;
		$this->assertTrue($res === 1 || $res === 0);
		//var_dump($res);
	}

	public function testIsDateColumn(){
		$data='';
		$column='';
		$res = TableSort::is_date_column($data, $column);
		if(is_bool($res))
		$this->assertTrue(($res));
		else{
		$this->assertTrue(is_numeric($res));
		}
		//var_dump($res);
	}

	public function testIsImageColumn(){
		$data='';
		$column='';
		$res = TableSort::is_image_column($data, $column);
		if(is_bool($res)){
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res);
		}
		else{
		$this->assertTrue(is_numeric($res));
		}
		//var_dump($res);
	}

	public function testSortTableConfig(){
		$data=array(a,b,c,d,e,z);
		$column = 0;
		$direction = SORT_ASC;
		$column_show=null;
        $column_order=null;
		$type = SORT_REGULAR;
		$res =  TableSort::sort_table_config($data, $column, $direction, $column_show, $column_order,$type);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}
}
?>
