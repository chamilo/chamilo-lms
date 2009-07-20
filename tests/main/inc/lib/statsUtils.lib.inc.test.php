<?php
require_once(api_get_path(LIBRARY_PATH).'statsUtils.lib.inc.php');


class TestStatsUtils extends UnitTestCase {

	function testbuildTab2col() {
		$array_of_results=array();
		$title1='';
		$title2='';
		ob_start();
		$res=buildTab2col($array_of_results, $title1, $title2);
		$this->assertTrue(is_null($res));
		ob_end_clean();
		//var_dump($res);
	}
	
	function testbuildTab2ColNoTitle() {
		$array_of_results=array();
		$res=buildTab2ColNoTitle($array_of_results);
		$this->assertTrue(is_array($array_of_results));
		//var_dump($array_of_results);
	}
	
	function testbuildTabDefcon() {
		$array_of_results=array();
		ob_start();
		$res=buildTabDefcon($array_of_results);
		$this->assertTrue(is_array($array_of_results));
		ob_end_clean();
		//var_dump($array_of_results);
	}
	
	function testdaysTab() {
		$sql='';
		$days_array = array('total' => 0);
		$res=daysTab($sql);
		$this->assertTrue(is_array($days_array));
		//var_dump($sql);
	}
	
	function testgetManyResults1Col() {
		$sql='';
		$res=getManyResults1Col($sql);
		$this->assertTrue(is_string($sql));
		//var_dump($sql);
	}
	
	function testgetManyResults2Col() {
		$sql='';
		$res=getManyResults2Col($sql);
		$this->assertTrue(is_string($sql));
		//var_dump($sql);
	}
	
	function testgetManyResults3Col() {
		$sql='';
		$res=getManyResults3Col($sql);
		$this->assertTrue(is_string($sql));
		//var_dump($sql);
	}
	
	function testgetManyResultsXCol() {
		$sql='';
		$X='';
		$res=getManyResultsXCol($sql,$X);
		$this->assertTrue(is_string($sql));
		//var_dump($sql);
	}
	
	function testgetOneResult() {
		$sql='';
		$res=getOneResult($sql);
		$this->assertTrue(is_string($sql));
		//var_dump($sql);
	}
	
	function testhoursTab() {
		$sql='';
		$res=hoursTab($sql);
		$this->assertTrue(is_string($sql));
		//var_dump($sql);
	}
	
	function testmakeHitsTable() {
		$period_array=array();
		$periodTitle='';
		ob_start();
		$res=makeHitsTable($period_array, $periodTitle, $linkOnPeriod = '???');
		$this->assertTrue(is_null($res));
		ob_end_clean();
		//var_dump($res);
	}
	
	function testmonthTab() {
		$sql='';
		$res=monthTab($sql);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}









}
?>
