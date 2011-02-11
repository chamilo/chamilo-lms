<?php
/* For licensing terms, see /license.txt */
/**
 * Configures the WSReport SOAP service
 * @package chamilo.webservices
 */
require_once(dirname(__FILE__).'/webservice_report.php');
require_once(dirname(__FILE__).'/soap.php');
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
    'course_id',
    'complexType',
    'struct',
    'all',
    '',
    array(
        'course_id_field_name' => array('name' => 'course_id_field_name', 'type' => 'xsd:string'),
        'course_id_value' => array('name' => 'course_id_value', 'type' => 'xsd:string')
    )
);

$s->wsdl->addComplexType(
    'session_id',
    'complexType',
    'struct',
    'all',
    '',
    array(
        'session_id_field_name' => array('name' => 'session_id_field_name', 'type' => 'xsd:string'),
        'session_id_value' => array('name' => 'session_id_value', 'type' => 'xsd:string')
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
	'WSReport.get_time_spent_on_platform',
	array('secret_key' => 'xsd:string', 'user_id_field_name' => 'xsd:string', 'user_id_value' => 'xsd:string'),
    array('return' => 'tns:user_result_array')
);

$s->register(
	'WSReport.get_time_spent_on_course',
    array('secret_key' => 'xsd:string', 'user_id_field_name' => 'xsd:string', 'user_id_value' => 'xsd:string', 'course_id_field_name' => 'xsd:string', 'course_id_value' => 'xsd:string'),
	array('return' => 'tns:user_result_array')
);

$s->register(
	'WSReport.get_time_spent_on_course_in_session',
    array('secret_key' => 'xsd:string', 'user_id_field_name' => 'xsd:string', 'user_id_value' => 'xsd:string', 'course_id_field_name' => 'xsd:string', 'course_id_value' => 'xsd:string', 'session_id_field_name' => 'xsd:string', 'session_id_value' => 'xsd:string'),
    array('return' => 'tns:user_result_array')
);

$s->register(
    'WSReport.get_time_spent_on_learnpath_in_course',
    array('secret_key' => 'xsd:string', 'user_id_field_name' => 'xsd:string', 'user_id_value' => 'xsd:string', 'course_id_field_name' => 'xsd:string', 'course_id_value' => 'xsd:string', 'learnpath_id' => 'xsd:string'),
    array('return' => 'tns:user_result_array')
);
