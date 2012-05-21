<?php
require_once(api_get_path(LIBRARY_PATH).'session_handler.class.php');

class TestSessionHandler extends UnitTestCase {

	var $connection;
	var $connection_handler;
	var $lifetime;
	var $session_name;


	public function TestSessionHandler(){
		$this->UnitTestCase('Session handler library - main/inc/lib/session_handler.class.test.php');
	}
	function testClose() {
		$instancia = new SessionHandler();
		$res=$instancia->close();
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	function testdestroy() {
		$instancia = new SessionHandler();
		$sess_id='';
		$res=$instancia->destroy($sess_id);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	function testgarbage() {
		$instancia = new SessionHandler();
		$lifetime='';
		$res=$instancia->garbage($lifetime);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	function testopen() {
		$instancia = new SessionHandler();
		$path='';
		$name='';
		$res=$instancia->open($path,$name);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	function testread() {
		$instancia = new SessionHandler();
		$sess_id='';
		$res=$instancia->read($sess_id);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	/*
	//No se puede probar por tener el mismo nombre de la clase
	function testsessionhandler() {
		$instancia = new session_handler();
		global $_configuration;
		$res=$instancia->session_handler();
		$this->assertTrue(is_string($res));
		var_dump($res);
	}
	*/

	function testsqlClose() {
		$instancia = new SessionHandler();
		$res=$instancia->sqlClose();
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	function testsqlConnect() {
		$instancia = new SessionHandler();
		$res=$instancia->sqlConnect();
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	function testsqlQuery() {

		global $_configuration;

		$tbl_user = Database::get_main_table(TABLE_MAIN_USER);
		$query = 'select user_id from '.$tbl_user;

		$instancia = new SessionHandler();
		$instancia->connection_handler = mysql_connect($_configuration['db_host'],
												$_configuration['db_user'],
												$_configuration['db_password']);

		$res= $instancia->sqlQuery($query, false);
		$this->assertTrue(is_resource($res));
		$this->assertTrue($res);

	}

	function testwrite() {
		$instancia = new SessionHandler();
		$sess_id='';
		$sess_value='';
		$res=$instancia->write($sess_id,$sess_value);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}
}
?>
