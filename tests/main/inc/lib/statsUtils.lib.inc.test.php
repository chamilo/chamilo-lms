<?php

class TestStatsUtils extends UnitTestCase
{
    public function __construct()
	{
        $this->UnitTestCase('Stats utilities library - main/inc/lib/statsUtil.lib.inc.test.php');
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
}
