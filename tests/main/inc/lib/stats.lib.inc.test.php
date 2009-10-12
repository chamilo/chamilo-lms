<?php
require_once(api_get_path(LIBRARY_PATH).'stats.lib.inc.php');
class TestStats extends UnitTestCase {

	function testaddBrowser() {
		$browser='';
		$browsers_array=array();
		$res=addBrowser($browser,$browsers_array);
		$this->assertTrue(is_array($res));
	}

	function testaddCountry(){
		$country='';
		$countries_array=array();
		$res=addCountry($country,$countries_array);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}

	function testaddOs() {
		$os='';
		$os_array=array();
		$res=addOs($os,$os_array);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}

	function testaddProvider() {
		$provider='';
		$providers_array=array();
		$res=addProvider($provider,$providers_array);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}

	function testaddReferer() {
		$referer='';
		$referers_array=array();
		$res=addReferer($referer,$referers_array);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}

	function testcleanProcessedRecords() {
		global $TABLETRACK_OPEN;
		$limit='';
		$res=cleanProcessedRecords($limit);
		if(!is_null($res)) $this->assertTrue(is_string($res));
		//var_dump($res);
	}

	function testdecodeOpenInfos() {
    	global $TABLETRACK_OPEN;
    	$ignore = ignore_user_abort();
      	$res=decodeOpenInfos();
    	$this->assertTrue(is_null($res));
    	$this->assertTrue(is_numeric($ignore));
    	//var_dump($res);
 	}

	function testextractAgent() {
		$user_agent=$_SERVER['HTTP_USER_AGENT'];
		$list_browsers=array();
		$list_os=array();
		$res=extractAgent($user_agent, $list_browsers, $list_os );
		$this->assertTrue(is_string($res));
    	//var_dump($res);
	}

	function testextractCountry() {
		$remhost= @getHostByAddr($_SERVER['REMOTE_ADDR']);
		$list_countries=array();
		$res=extractCountry($remhost,$list_countries);
		if(!is_null($res))$this->assertTrue(is_string($res));
    	//var_dump($res);
	}

	function testextractProvider() {
		$remhost=@getHostByAddr($_SERVER['REMOTE_ADDR']);
		$res=extractProvider($remhost);
		if(!is_null($res))$this->assertTrue(is_string($res));
    	//var_dump($res);
	}

	function testfillBrowsersTable() {
		global $TABLESTATS_BROWSERS;
		$browsers_array=array();
		$res=fillBrowsersTable($browsers_array);
		if(!is_null($res)) $this->assertTrue(is_array($res));
    	//var_dump($res);
	}

	function testfillCountriesTable() {
		global $TABLESTATS_COUNTRIES;
		$countries_array=array();
		$res=fillCountriesTable($countries_array);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	function testfillOsTable() {
		global $TABLESTATS_OS;
		$os_array=array();
		$res=fillOsTable($os_array);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	function testfillProvidersTable() {
		global $TABLESTATS_PROVIDERS;
		$providers_array=array();
		$res=fillProvidersTable($providers_array);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	function testfillReferersTable() {
		global $TABLESTATS_REFERERS;
		$referers_array=array();
		$res=fillReferersTable($referers_array);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	function testloadBrowsers() {
		$res=loadBrowsers();
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}

	function testloadCountries() {
		global $TABLESTATS_COUNTRIES;
		$sql = "SELECT code, country
            FROM `".$TABLESTATS_COUNTRIES."`";
    	$i=$i++;
        $resu = mysql_query( $sql );
        $row = Database::fetch_array( $resu );
        $list_countries[$i][0] = $row["code"];
        $list_countries[$i][1] = $row["country"];
		$res=loadCountries();
	    $this->assertTrue(is_array($list_countries));
		//var_dump($list_countries);
	}

	function testloadOs() {
		$res=loadOs();
	    $this->assertTrue(is_array($res));
		//var_dump($res);
	}
}
?>
