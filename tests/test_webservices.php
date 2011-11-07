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
		$this->_encrypt_method = $configuration['password_encryption'];
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
	
	protected function getCourseArray() {
		$course = array(
			'title' => 'My test course',
			'category_code' => 'LANG',
			'wanted_code' => '110',
			'tutor_name' => 'Guillaume Viguier',
			'course_admin_user_id_field_name' => 'chamilo_user_id',
			'course_admin_user_id_value' => '1',
			'language' => 'spanish',
			'course_id_field_name' => 'chamilo_course_id',
			'course_id_value' => '',
			'extras' => array());
		return $course;
	}
	
	protected function getSessionArray() {
		$end_date = date('Y') + 1;
		$end_date .= '-'.date('m-d');
		$session = array(
			'name' => 'My session',
			'start_date' => date('Y-m-d'),
			'end_date' => $end_date,
			'nb_days_access_before' => 0,
			'nb_days_access_after' => 0,
			'nolimit' => 0,
			'visibility' => 1,
			'user_id_field_name' => 'chamilo_user_id',
			'user_id_value' => '1',
			'session_id_field_name' => 'chamilo_session_id', 
			'session_id_value' => '',
			'extras' => array());
		return $session;
	}
	
	protected function soapCall($method, $arguments) {
		return $this->_client->__soapCall($method, $arguments);
	}
	
	protected function createUser() {
		$user = $this->getUserArray();
		$result = $this->soapCall('WSUser.CreateUser', array_merge(array('secret_key' => $this->_secret_key), $user));
		return $result;
	}
	
	protected function createCourse() {
		$course = $this->getCourseArray();
		$result = $this->soapCall('WSCourse.CreateCourse', array_merge(array('secret_key' => $this->_secret_key), $course));
		return $result;
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
	
	public function testCourseCreation() {
		$course = $this->getCourseArray();
		$result = $this->soapCall('WSCourse.CreateCourse', array_merge(array('secret_key' => $this->_secret_key), $course));
		$this->assertIsA($result, 'int');
		// Delete course created
		$this->soapCall('WSCourse.DeleteCourse', array('secret_key' => $this->_secret_key, 'course_id_field_name' => 'chamilo_course_id', 'course_id_value' => $result));
	}
	
	/*public function testCourseSubscriptionAndUnsubscription() {
		//$course_id = $this->createCourse();
		//$user_id = $this->createUser();
		//echo $course_id.';'.$user_id;
		//$this->soapCall('WSCourse.SubscribeUserToCourse', array('secret_key' => $this->_secret_key, 'course_id_field_name' => 'chamilo_course_id', 'course_id_value' => 8, 'user_id_field_name' => 'chamilo_user_id', 'user_id_value' => 38, 'status' => 1));
		//$this->soapCall('WSCourse.UnsubscribeUserFromCourse', array('secret_key' => $this->_secret_key, 'course_id_field_name' => 'chamilo_course_id', 'course_id_value' => 8, 'user_id_field_name' => 'chamilo_user_id', 'user_id_value' => 38));
	}*/
	
	/*public function testCourseDescriptions() {
		//$this->soapCall('WSCourse.EditCourseDescription', array('secret_key' => $this->_secret_key, 'course_id_field_name' => 'chamilo_course_id', 'course_id_value' => 8, 'course_desc_id' => 1, 'course_desc_title' => 'My description', 'course_desc_content' => 'This is my new description'));
		//$result = $this->soapCall('WSCourse.GetCourseDescriptions', array('secret_key' => $this->_secret_key, 'course_id_field_name' => 'chamilo_course_id', 'course_id_value' => 8));
		//var_dump($result);
		$result = $this->soapCall('WSCourse.ListCourses', array('secret_key' => $this->_secret_key, 'course_id_field_name' => 'chamilo_course_id'));
		var_dump($result);
	}*/
	
	public function testSessionCreation() {
		$session = $this->getSessionArray();
		$result = $this->soapCall('WSSession.CreateSession', array_merge(array('secret_key' => $this->_secret_key), $session));
		$this->assertIsA($result, 'int');
		$this->soapCall('WSSession.DeleteSession', array('secret_key' => $this->_secret_key, 'session_id_field_name' => 'chamilo_session_id', 'session_id_value' => $result));
	}
	
	/*public function testUserSessionSubscriptionAndUnsubscription() {
		$this->soapCall('WSSession.UnsubscribeCourseFromSession', array('secret_key' => $this->_secret_key, 'course_id_field_name' => 'chamilo_course_id', 'course_id_value' => 8, 'session_id_field_name' => 'chamilo_session_id', 'session_id_value' => 3));
		//$this->soapCall('WSSession.UnsubscribeUserFromSession', array('secret_key' => $this->_secret_key, 'user_id_field_name' => 'chamilo_user_id', 'user_id_value' => 38, 'session_id_field_name' => 'chamilo_session_id', 'session_id_value' => 3));
	}*/
	
	
}


