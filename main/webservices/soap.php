<?php

require_once '../inc/global.inc.php';
require_once(dirname(__FILE__).'/webservice.php');
$libpath = api_get_path(LIBRARY_PATH);
require_once $libpath.'nusoap/nusoap.php';

/**
 * SOAP error handler. Handles an error sending a SOAP fault
 */
class WSSoapErrorHandler implements WSErrorHandler {
	/**
	 * SOAP server
	 * 
	 * @var soap_server
	 */
	protected $_server;
	
	/**
	 * Constructor
	 */
	public function __construct($server) {
		$this->_server = $server;
	}
	
	/**
	 * Handles the error by sending a SOAP fault through the server
	 * 
	 * @param WSError Error to handle
	 */
	public function handle($error) {
		$this->_server->fault(strval($error->code), $error->message);
	}
}


$s = new soap_server();

$error_handler = new WSSoapErrorHandler($s);
WSError::setErrorHandler($error_handler);

// Initialize WSDL support
$s->configureWSDL('WSService', 'urn:WSService');

$s->register(
	'WS.test',
	array(),
	array('return' => 'xsd:string')
);

$s->register(
	'WS.DisableUser',
	array('secret_key' => 'xsd:string', 'user_id_field_name' => 'xsd:string', 'user_id_field_value' => 'xsd:string')
);

$s->register(
	'WS.EnableUser',
	array('secret_key' => 'xsd:string', 'user_id_field_name' => 'xsd:string', 'user_id_field_value' => 'xsd:string')
);

$s->register(
	'WS.DeleteUser',
	array('secret_key' => 'xsd:string', 'user_id_field_name' => 'xsd:string', 'user_id_field_value' => 'xsd:string')
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
	'WS.CreateUser',
	array(
		'secret_key' => 'xsd:string',
		'firstname' => 'xsd:string',
		'lastname' => 'xsd:string',
		'status' => 'xsd:int',
		'loginname' => 'xsd:string',
		'password' => 'xsd:string',
		'encrypt_method' => 'xsd:string',
		'user_id_field_name' => 'xsd:string',
		'user_id_value' => 'xsd:string',
		'visibility' => 'xsd:int',
		'email' => 'xsd:string',
		'language' => 'xsd:string',
		'phone' => 'xsd:string',
		'expiration_date' => 'xsd:string',
		'extras' => 'tns:extra_field[]'
	),
	array('return' => 'xsd:int')
);

// Use the request to (try to) invoke the service
$HTTP_RAW_POST_DATA = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : '';
$s->service($HTTP_RAW_POST_DATA);
