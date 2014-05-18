<?php

require_once '../inc/global.inc.php';
require_once(dirname(__FILE__).'/cm_webservice.php');
$libpath = api_get_path(LIBRARY_PATH);
require_once $libpath.'nusoap/nusoap.php';

/**
 * SOAP error handler. Handles an error sending a SOAP fault
 */
class WSCMSoapErrorHandler implements WSCMErrorHandler {
	/**
	 * Handles the error by sending a SOAP fault through the server
	 * 
	 * @param WSError Error to handle
	 */
	public function handle($error) {
		$server = WSCMSoapServer::singleton();
		$server->fault(strval($error->code), $error->message);
	}
}

/**
 * SOAP server wrapper implementing a Singleton
 */
class WSCMSoapServer {
	/**
	 * SOAP server instance
	 * 
	 * @var soap_server
	 */
	private static $_instance;
	
	/**
	 * Private constructor
	 */
	private function __construct() {
	}
	
	/**
	 * Singleton method
	 */
	public static function singleton() {
		if(!isset(self::$_instance)) {
			self::$_instance = new soap_server();
			// Set the error handler
			WSCMError::setErrorHandler(new WSCMSoapErrorHandler());
			// Configure the service
			self::$_instance->configureWSDL('WSCMService', 'urn:WSCMService');
		}
		
		return self::$_instance;
	}
}
			
$s = WSCMSoapServer::singleton();

$s->wsdl->addComplexType(
	'result',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'code' => array('name' => 'code', 'type' => 'xsd:int'),
		'message' => array('name' => 'message', 'type' => 'xsd:string')
	)
);

$s->wsdl->addComplexType(
	'extra_field',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'field_name' => array('name' => 'field_name', 'type' => 'xsd:string'),
		'field_value' => array('name' => 'field_value', 'type' => 'xsd:string')
	)
);

$s->register(
	'WSCM.verifyUserPass',
	array(
		'username' => 'xsd:string',
		'password' => 'xsd:string',
	),
	array('return' => 'xsd:string')
);

$s->register(
        'WSCM.encryptPass',
        array('password' => 'xsd:string'),
        array('return' => 'xsd:string')
);

$s->register(
	'WSCM.test',
	array(),
	array('return' => 'xsd:string'),
        'urn:WSCMService',
        '',
        '',
        '',
        ''
);

require_once(dirname(__FILE__).'/cm_soap_inbox.php');
require_once(dirname(__FILE__).'/cm_soap_user.php');
require_once(dirname(__FILE__).'/cm_soap_courses.php');
require_once(dirname(__FILE__).'/cm_soap_announcements.php');
require_once(dirname(__FILE__).'/cm_soap_forum.php');

// Use the request to (try to) invoke the service
$HTTP_RAW_POST_DATA = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : '';
$s->service($HTTP_RAW_POST_DATA);
