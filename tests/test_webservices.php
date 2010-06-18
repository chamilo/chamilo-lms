<?php
/**
 * Set of unit tests for the web services
 * 
 * @author Guillaume Viguier <guillaume.viguier@beeznest.com>
 */

ini_set('soap.wsdl_cache_enabled', 0);

require_once(dirname(__FILE__).'/../main/inc/global.inc.php');

require_once(dirname(__FILE__).'/simpletest/autorun.php');

class TestSoapWebService extends UnitTestCase {
	protected $_secret_key;
	
	protected $_encrypt_method;
	
	protected $_client;
	
	public function __construct() {
		$configuration = $GLOBALS['_configuration'];
		$security_key = $configuration['security_key'];
		$ip_address = '::1';
		$this->_secret_key = sha1($ip_address.$security_key);
		$this->_encrypt_method = $_GLOBALS['userPasswordCrypted'];
		$this->_client = new SoapClient($configuration['root_web'].'main/webservices/soap.php?wsdl');
	}
	
	protected function getUserArray() {
		$user = array(
			'firstname' => 'Guillaume',
			'lastname' => 'Viguier',
			'status' => 5,
			'loginname' => 'guillaumev',
			'password' => 'guillaume',
			'encrypt_method' => '',
			'user_id_field_name' => 'chamilo_user_id',
			'user_id_field_value' => '',
			'visibility' => 1,
			'email' => 'guillaume.viguier@beeznest.com',
			'language' => 'english',
			'phone' => '123456',
			'expiration_date' => '0000-00-00 00:00:00',
			'extras' => array());
		return $user;
	}
	
	protected function soapCall($method, $arguments) {
		return $this->_client->__soapCall($method, $arguments);
	}
	
	public function testTest() {
		$result = $this->soapCall('WS.test', array());
		$this->assertEqual($result, "success");
	}
	
	public function testInvalidKey() {
		$secret_key = 'invalid';
		try {
			$this->soapCall('WSUser.DisableUser', array('secret_key' => $secret_key, 'user_id_field_name' => 'chamilo_user_id', 'user_id_value' => 3));
			$this->fail('Exception was expected');
		} catch(SOAPFault $f) {
			$this->pass();
		}
	}
	
	public function testCreateUser() {
		$user = $this->getUserArray();
		$result = $this->soapCall('WSUser.CreateUser', array_merge(array('secret_key' => $this->_secret_key), $user));
		$this->assertIsA($result, 'int');
		//Delete user created
		$this->soapCall('WSUser.DeleteUser', array('secret_key' => $this->_secret_key, 'user_id_field_name' => 'chamilo_user_id', 'user_id_value' => $result));
	}
	
	public function testCreateUserEncrypted() {
		$user = $this->getUserArray();
		$user['encrypt_method'] = $this->_encrypt_method;
		if($this->_encrypt_method == 'md5') {
			$user['password'] = md5('guillaume');
		} else if($this->_encrypt_method == 'sha1') {
			$user['password'] = sha1('guillaume');
		}
		$user['extras'] = array(array('field_name' => 'salt', 'field_value' => '1234'));
		$result = $this->soapCall('WSUser.CreateUser', array_merge(array('secret_key' => $this->_secret_key), $user));
		$this->assertIsA($result, 'int');
		//Delete user created
		$this->soapCall('WSUser.DeleteUser', array('secret_key' => $this->_secret_key, 'user_id_field_name' => 'chamilo_user_id', 'user_id_value' => $result));
	}
}


