<?php
/* For licensing terms, see /license.txt */
/**
 * Configures the WSUser SOAP service
 * @package chamilo.webservices
 */
require_once(dirname(__FILE__).'/webservice_user.php');
require_once(dirname(__FILE__).'/soap.php');

/**
 * Configures the WSUser SOAP service
 * @package chamilo.webservices
 */
$s = WSSoapServer::singleton();

$s->wsdl->addComplexType(
	'user_id',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'user_id_field_name' => array('name' => 'user_id_field_name', 'type' => 'xsd:string'),
		'user_id_value' => array('name' => 'user_id_value', 'type' => 'xsd:string')
	)
);

$s->wsdl->addComplexType(
	'user_result',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'user_id_value' => array('name' => 'user_id_value', 'type' => 'xsd:string'),
		'result' => array('name' => 'result', 'type' => 'tns:result')
	)
);

$s->wsdl->addComplexType(
	'user_result_array',
	'complexType',
	'array',
	'',
	'SOAP-ENC:Array',
	array(),
	array(array('ref'=>'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:user_result[]')),
	'tns:user_result'
);

$s->register(
	'WSUser.DisableUser',
	array('secret_key' => 'xsd:string', 'user_id_field_name' => 'xsd:string', 'user_id_value' => 'xsd:string')
);

$s->register(
	'WSUser.DisableUsers',
	array('secret_key' => 'xsd:string', 'users' => 'tns:user_id[]'),
	array('return' => 'tns:user_result_array')
);

$s->register(
	'WSUser.EnableUser',
	array('secret_key' => 'xsd:string', 'user_id_field_name' => 'xsd:string', 'user_id_value' => 'xsd:string')
);

$s->register(
	'WSUser.EnableUsers',
	array('secret_key' => 'xsd:string', 'users' => 'tns:user_id[]'),
	array('return' => 'tns:user_result_array')
);

$s->register(
	'WSUser.DeleteUser',
	array('secret_key' => 'xsd:string', 'user_id_field_name' => 'xsd:string', 'user_id_value' => 'xsd:string')
);

$s->register(
	'WSUser.DeleteUsers',
	array('secret_key' => 'xsd:string', 'users' => 'tns:user_id[]'),
	array('return' => 'tns:user_result_array')
);

$s->register(
	'WSUser.CreateUser',
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
		'extras' => 'tns:extra_field'
	),
	array('return' => 'xsd:int')
);

$s->wsdl->addComplexType(
	'user_create',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'firstname' => array('name' => 'firstname', 'type' => 'xsd:string'),
		'lastname' => array('name' => 'lastname', 'type' => 'xsd:string'),
		'status' => array('name' => 'status', 'type' => 'xsd:int'),
		'loginname' => array('name' => 'loginname', 'type' => 'xsd:string'),
		'password' => array('name' => 'password', 'type' => 'xsd:string'),
		'encrypt_method' => array('name' => 'encrypt_method', 'type' => 'xsd:string'),
		'user_id_field_name' => array('name' => 'user_id_field_name', 'type' => 'xsd:string'),
		'user_id_value' => array('name' => 'user_id_value', 'type' => 'xsd:string'),
		'visibility' => array('name' => 'visibility', 'type' => 'xsd:int'),
		'email' => array('name' => 'email', 'type' => 'xsd:string'),
		'language' => array('name' => 'language', 'type' => 'xsd:string'),
		'phone' => array('name' => 'phone', 'type' => 'xsd:string'),
		'expiration_date' => array('name' => 'expiration_date', 'type' => 'xsd:string'),
		'extras' => array('name' => 'extras', 'type' => 'tns:extra_field')
	)
);

$s->wsdl->addComplexType(
	'user_create_result',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'user_id_value' => array('name' => 'user_id_value', 'type' => 'xsd:string'),
		'user_id_generated' => array('name' => 'user_id_generated', 'type' => 'xsd:int'),
		'result' => array('name' => 'result', 'type' => 'tns:result')
	)
);

$s->wsdl->addComplexType(
	'user_create_result_array',
	'complexType',
	'array',
	'',
	'SOAP-ENC:Array',
	array(),
	array(array('ref'=>'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:user_create_result[]')),
	'tns:user_create_result'
);

$s->register(
	'WSUser.CreateUsers',
	array(
		'secret_key' => 'xsd:string',
		'users' => 'tns:user_create[]'
	),
	array('return' => 'tns:user_create_result_array')
);

$s->register(
	'WSUser.EditUser',
	array(
		'secret_key' => 'xsd:string',
		'user_id_field_name' => 'xsd:string',
		'user_id_value' => 'xsd:string',
		'firstname' => 'xsd:string',
		'lastname' => 'xsd:string',
		'status' => 'xsd:int',
		'loginname' => 'xsd:string',
		'password' => 'xsd:string',
		'encrypt_method' => 'xsd:string',
		'email' => 'xsd:string',
		'language' => 'xsd:string',
		'phone' => 'xsd:string',
		'expiration_date' => 'xsd:string',
		'extras' => 'tns:extra_field'
	)
);

$s->wsdl->addComplexType(
	'user_edit',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'user_id_field_name' => array('name' => 'user_id_field_name', 'type' => 'xsd:string'),
		'user_id_value' => array('name' => 'user_id_value', 'type' => 'xsd:string'),
		'firstname' => array('name' => 'firstname', 'type' => 'xsd:string'),
		'lastname' => array('name' => 'lastname', 'type' => 'xsd:string'),
		'status' => array('name' => 'status', 'type' => 'xsd:int'),
		'loginname' => array('name' => 'loginname', 'type' => 'xsd:string'),
		'password' => array('name' => 'password', 'type' => 'xsd:string'),
		'encrypt_method' => array('name' => 'encrypt_method', 'type' => 'xsd:string'),
		'email' => array('name' => 'email', 'type' => 'xsd:string'),
		'language' => array('name' => 'language', 'type' => 'xsd:string'),
		'phone' => array('name' => 'phone', 'type' => 'xsd:string'),
		'expiration_date' => array('name' => 'expiration_date', 'type' => 'xsd:string'),
		'extras' => array('name' => 'extras', 'type' => 'tns:extra_field')
	)
);

$s->wsdl->addComplexType(
	'user_edit_result',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'user_id_value' => array('name' => 'user_id_value', 'type' => 'xsd:string'),
		'result' => array('name' => 'result', 'type' => 'tns:result')
	)
);

$s->wsdl->addComplexType(
	'user_edit_result_array',
	'complexType',
	'array',
	'',
	'SOAP-ENC:Array',
	array(),
	array(array('ref'=>'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:user_edit_result[]')),
	'tns:user_edit_result'
);

$s->register(
	'WSUser.EditUsers',
	array(
		'secret_key' => 'xsd:string',
		'users' => 'tns:user_edit[]'
	),
	array('return' => 'tns:user_edit_result_array')
);
