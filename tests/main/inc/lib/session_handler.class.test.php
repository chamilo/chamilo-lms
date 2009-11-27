<?php
// To can run this test you need comment the line 94 "exit"
require_once(api_get_path(LIBRARY_PATH).'session_handler.class.php');

class TestSessionHandler extends UnitTestCase {

	var $connexion;
	var $idConnexion;
	var $lifetime;
	var $sessionName;


	function testClose() {
		$instancia = new session_handler();
		$res=$instancia->close();
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	function testdestroy() {
		$instancia = new session_handler();
		$sess_id='';
		$res=$instancia->destroy($sess_id);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	function testgarbage() {
		$instancia = new session_handler();
		$lifetime='';
		$res=$instancia->garbage($lifetime);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	function testopen() {
		$instancia = new session_handler();
		$path='';
		$name='';
		$res=$instancia->open($path,$name);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	function testread() {
		$instancia = new session_handler();
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
		$instancia = new session_handler();
		$res=$instancia->sqlClose();
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	function testsqlConnect() {
		$instancia = new session_handler();
		$res=$instancia->sqlConnect();
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	function testsqlQuery() {
		$instancia = new session_handler();
		$res=$instancia->sqlQuery();
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	function testwrite() {
		$instancia = new session_handler();
		$sess_id='';
		$sess_value='';
		$res=$instancia->write();
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}
}
?>
