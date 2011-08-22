<?php

require_once(dirname(__FILE__).'/cm_webservice_course.php');
require_once(dirname(__FILE__).'/cm_soap.php');

/**
 * Configures the WSCourse SOAP service
 */
$s = WSCMSoapServer::singleton();

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
	'course_result',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'course_id_value' => array('name' => 'course_id_value', 'type' => 'xsd:string'),
		'result' => array('name' => 'result', 'type' => 'tns:result')
	)
);

$s->wsdl->addComplexType(
	'course_result_array',
	'complexType',
	'array',
	'',
	'SOAP-ENC:Array',
	array(),
	array(array('ref'=>'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:course_result[]')),
	'tns:course_result'
);

$s->register(
	'WSCMCourse.DeleteCourse',
	array('secret_key' => 'xsd:string', 'course_id_field_name' => 'xsd:string', 'course_id_value' => 'xsd:string')
);

$s->register(
	'WSCMCourse.DeleteCourses',
	array('secret_key' => 'xsd:string', 'courses' => 'tns:course_id[]'),
	array('return' => 'tns:course_result_array')
);

$s->register(
	'WSCMCourse.CreateCourse',
	array(
		'secret_key' => 'xsd:string',
		'title' => 'xsd:string',
		'category_code' => 'xsd:string',
		'wanted_code' => 'xsd:string',
		'tutor_name' => 'xsd:string',
		'course_admin_user_id_field_name' => 'xsd:string',
		'course_admin_user_id_value' => 'xsd:string',
		'language' => 'xsd:string',
		'course_id_field_name' => 'xsd:string',
		'course_id_value' => 'xsd:string',
		'extras' => 'tns:extra_field[]'
	),
	array('return' => 'xsd:int')
);

$s->wsdl->addComplexType(
	'course_create',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'title' => array('name' => 'title', 'type' => 'xsd:string'),
		'category_code' => array('name' => 'category_code', 'type' => 'xsd:string'),
		'wanted_code' => array('name' => 'wanted_code', 'type' => 'xsd:int'),
		'tutor_name' => array('name' => 'tutor_name', 'type' => 'xsd:string'),
		'course_admin_user_id_field_name' => array('name' => 'course_admin_user_id_field_name', 'type' => 'xsd:string'),
		'course_admin_user_id_value' => array('name' => 'course_admin_user_id_value', 'type' => 'xsd:string'),
		'language' => array('name' => 'language', 'type' => 'xsd:string'),
		'course_id_field_name' => array('name' => 'course_id_field_name', 'type' => 'xsd:string'),
		'course_id_value' => array('name' => 'course_id_value', 'type' => 'xsd:string'),
		'extras' => array('name' => 'extras', 'type' => 'tns:extra_field[]')
	)
);

$s->wsdl->addComplexType(
	'course_create_result',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'course_id_value' => array('name' => 'course_id_value', 'type' => 'xsd:string'),
		'course_id_generated' => array('name' => 'course_id_generated', 'type' => 'xsd:int'),
		'result' => array('name' => 'result', 'type' => 'tns:result')
	)
);

$s->wsdl->addComplexType(
	'course_create_result_array',
	'complexType',
	'array',
	'',
	'SOAP-ENC:Array',
	array(),
	array(array('ref'=>'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:course_create_result[]')),
	'tns:course_create_result'
);

$s->register(
	'WSCMCourse.CreateCourses',
	array(
		'secret_key' => 'xsd:string',
		'courses' => 'tns:course_create[]'
	),
	array('return' => 'tns:course_create_result_array')
);

$s->register(
	'WSCMCourse.EditCourse',
	array(
		'secret_key' => 'xsd:string',
		'course_id_field_name' => 'xsd:string',
		'course_id_value' => 'xsd:string',
		'title' => 'xsd:string',
		'category_code' => 'xsd:string',
		'department_name' => 'xsd:string',
		'department_url' => 'xsd:string',
		'language' => 'xsd:string',
		'visibility' => 'xsd:int',
		'subscribe' => 'xsd:int',
		'unsubscribe' => 'xsd:int',
		'visual_code' => 'xsd:string',
		'extras' => 'tns:extra_field[]'
	)
);

$s->wsdl->addComplexType(
	'course',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'id' => array('name' => 'id', 'type' => 'xsd:int'),
		'code' => array('name' => 'code', 'type' => 'xsd:string'),
		'title' => array('name' => 'title', 'type' => 'xsd:string'),
		'language' => array('name' => 'language', 'type' => 'xsd:string'),
		'visibility' => array('name' => 'visibility', 'type' => 'xsd:int'),
		'category_name' => array('name' => 'category_name', 'type' => 'xsd:string'),
		'number_students' => array('name' => 'number_students', 'type' => 'xsd:int'),
		'external_course_id' => array('name' => 'external_course_id', 'type' => 'xsd:string'),
	)
);

$s->wsdl->addComplexType(
	'course_array',
	'complexType',
	'array',
	'',
	'SOAP-ENC:Array',
	array(),
	array(array('ref'=>'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:course[]')),
	'tns:course'
);

$s->register(
	'WSCMCourse.ListCourses',
	array(
		'secret_key' => 'xsd:string',
		'course_id_field_name' => 'xsd:string'
	),
	array('return' => 'tns:course_array')
);

$s->register(
	'WSCMCourse.SubscribeUserToCourse',
	array(
		'secret_key' => 'xsd:string',
		'course_id_field_name' => 'xsd:string',
		'course_id_value' => 'xsd:string',
		'user_id_field_name' => 'xsd:string',
		'user_id_value' => 'xsd:string',
		'status' => 'xsd:int'
	)
);

$s->register(
	'WSCMCourse.UnsubscribeUserFromCourse',
	array(
		'secret_key' => 'xsd:string',
		'course_id_field_name' => 'xsd:string',
		'course_id_value' => 'xsd:string',
		'user_id_field_name' => 'xsd:string',
		'user_id_value' => 'xsd:string'
	)
);

$s->wsdl->addComplexType(
	'course_description',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'course_desc_id' => array('name' => 'course_desc_id', 'type' => 'xsd:int'),
		'course_desc_title' => array('name' => 'course_desc_title', 'type' => 'xsd:string'),
		'course_desc_content' => array('name' => 'course_desc_content', 'type' => 'xsd:string')
	)
);

$s->wsdl->addComplexType(
	'course_description_array',
	'complexType',
	'array',
	'',
	'SOAP-ENC:Array',
	array(),
	array(array('ref'=>'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:course_description[]')),
	'tns:course_description'
);

$s->register(
	'WSCMCourse.GetCourseDescriptions',
	array(
		'secret_key' => 'xsd:string',
		'course_id_field_name' => 'xsd:string',
		'course_id_value' => 'xsd:string'
	),
	array('return' => 'tns:course_description_array')
);

$s->register(
	'WSCMCourse.EditCourseDescription',
	array(
		'secret_key' => 'xsd:string',
		'course_id_field_name' => 'xsd:string',
		'course_id_value' => 'xsd:string',
		'course_desc_id' => 'xsd:int',
		'course_desc_title' => 'xsd:string',
		'course_desc_content' => 'xsd:string'
	)
);

$s->register(
	'WSCMCourse.unreadMessage',
	array(
		'username' => 'xsd:string',
		'password' => 'xsd:string',
	),
	array('return' => 'xsd:string'),
        'urn:WSCMService',
        '',
        '',
        '',
        'Retorna a quantidade de mensagens nao lidas na caixa de entrada do usuario.'

);


$s->register(
	'WSCMCourse.getIdMessage',
	array(
		'username' => 'xsd:string',
		'password' => 'xsd:string',
	),
	array('return' => 'xsd:string'),
        'urn:WSCMService',
        '',
        '',
        '',
        'Retorna o ID das mensagens.'

);


$s->register(
            'WSCMCourse.nada',
            array('username' => 'xsd:string', 'password' => 'xsd:string'),
            array('return' => 'xsd:string')
        );

