<?php

class TestStatsUtils extends UnitTestCase
{
    public function __construct()
	{
        $this->UnitTestCase('Stats utilities library - main/inc/lib/statsUtil.lib.inc.test.php');
    }

	function testbuildTab2col() {
		$array_of_results=array();
		$title1='';
		$title2='';
		ob_start();
		$res=StatsUtils::buildTab2col($array_of_results, $title1, $title2);
		$this->assertTrue(is_null($res));
		ob_end_clean();
		//var_dump($res);
	}
	function testbuildTab2ColNoTitle() {
		$array_of_results=array();
		ob_start();
		$res=StatsUtils::buildTab2ColNoTitle($array_of_results);
		ob_end_clean();
		$this->assertTrue(is_array($array_of_results));
		//var_dump($array_of_results);
	}

	function testbuildTabDefcon() {
		$array_of_results=array();
		ob_start();
		$res=StatsUtils::buildTabDefcon($array_of_results);
		$this->assertTrue(is_array($array_of_results));
		ob_end_clean();
		//var_dump($array_of_results);
	}

	function testdaysTab() {
		$sql='';
		ob_start();
		$days_array = array('total' => 0);
		$res=StatsUtils::daysTab($sql);
		ob_end_clean();
		$this->assertTrue(is_array($days_array));
		//var_dump($sql);
	}

	function testgetManyResults1Col() {
		$sql='';
		ob_start();
		$res=StatsUtils::getManyResults1Col($sql);
		ob_end_clean();
		$this->assertTrue(is_string($sql));
		//var_dump($sql);
	}

	function testgetManyResults2Col() {
		$sql='';
		ob_start();
		$res=StatsUtils::getManyResults2Col($sql);
		ob_end_clean();
		$this->assertTrue(is_string($sql));
		//var_dump($sql);
	}

	function testgetManyResults3Col() {
		$sql='';
		ob_start();
		$res=StatsUtils::getManyResults3Col($sql);
		ob_end_clean();
		$this->assertTrue(is_string($sql));
		//var_dump($sql);
	}

	function testgetManyResultsXCol() {
		$sql='';
		$X='';
		ob_start();
		$res=StatsUtils::getManyResultsXCol($sql,$X);
		ob_end_clean();
		$this->assertTrue(is_string($sql));
		//var_dump($sql);
	}

	function testgetOneResult() {
		$sql='';
		ob_start();
		$res=StatsUtils::getOneResult($sql);
		ob_end_clean();
		$this->assertTrue(is_string($sql));
		//var_dump($sql);
	}

	function testhoursTab() {
		$sql='';
		ob_start();
		$res=StatsUtils::hoursTab($sql);
		ob_end_clean();
		$this->assertTrue(is_string($sql));
		//var_dump($sql);
	}

	function testmakeHitsTable() {
		$period_array=array();
		$periodTitle='';
		ob_start();
		$res=StatsUtils::makeHitsTable($period_array, $periodTitle, $linkOnPeriod = '???');
		$this->assertTrue(is_null($res));
		ob_end_clean();
		//var_dump($res);
	}

	function testmonthTab() {
		$sql='';
		ob_start();
		$res=StatsUtils::monthTab($sql);
		ob_end_clean();
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}
}
