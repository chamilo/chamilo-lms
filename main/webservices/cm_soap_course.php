<?php
/* For licensing terms, see /license.txt */

/**
 * @package chamilo.webservices
 */
require_once __DIR__.'/cm_webservice_course.php';
require_once __DIR__.'/cm_soap.php';

/**
 * Configures the WSCourse SOAP service.
 */
$s = WSCMSoapServer::singleton();

$s->wsdl->addComplexType(
    'course_id',
    'complexType',
    'struct',
    'all',
    '',
    [
        'course_id_field_name' => ['name' => 'course_id_field_name', 'type' => 'xsd:string'],
        'course_id_value' => ['name' => 'course_id_value', 'type' => 'xsd:string'],
    ]
);

$s->wsdl->addComplexType(
    'course_result',
    'complexType',
    'struct',
    'all',
    '',
    [
        'course_id_value' => ['name' => 'course_id_value', 'type' => 'xsd:string'],
        'result' => ['name' => 'result', 'type' => 'tns:result'],
    ]
);

$s->wsdl->addComplexType(
    'course_result_array',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    [],
    [['ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:course_result[]']],
    'tns:course_result'
);

$s->register(
    'WSCMCourse.DeleteCourse',
    ['secret_key' => 'xsd:string', 'course_id_field_name' => 'xsd:string', 'course_id_value' => 'xsd:string']
);

$s->register(
    'WSCMCourse.DeleteCourses',
    ['secret_key' => 'xsd:string', 'courses' => 'tns:course_id[]'],
    ['return' => 'tns:course_result_array']
);

$s->register(
    'WSCMCourse.CreateCourse',
    [
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
        'extras' => 'tns:extra_field[]',
    ],
    ['return' => 'xsd:int']
);

$s->wsdl->addComplexType(
    'course_create',
    'complexType',
    'struct',
    'all',
    '',
    [
        'title' => ['name' => 'title', 'type' => 'xsd:string'],
        'category_code' => ['name' => 'category_code', 'type' => 'xsd:string'],
        'wanted_code' => ['name' => 'wanted_code', 'type' => 'xsd:int'],
        'tutor_name' => ['name' => 'tutor_name', 'type' => 'xsd:string'],
        'course_admin_user_id_field_name' => ['name' => 'course_admin_user_id_field_name', 'type' => 'xsd:string'],
        'course_admin_user_id_value' => ['name' => 'course_admin_user_id_value', 'type' => 'xsd:string'],
        'language' => ['name' => 'language', 'type' => 'xsd:string'],
        'course_id_field_name' => ['name' => 'course_id_field_name', 'type' => 'xsd:string'],
        'course_id_value' => ['name' => 'course_id_value', 'type' => 'xsd:string'],
        'extras' => ['name' => 'extras', 'type' => 'tns:extra_field[]'],
    ]
);

$s->wsdl->addComplexType(
    'course_create_result',
    'complexType',
    'struct',
    'all',
    '',
    [
        'course_id_value' => ['name' => 'course_id_value', 'type' => 'xsd:string'],
        'course_id_generated' => ['name' => 'course_id_generated', 'type' => 'xsd:int'],
        'result' => ['name' => 'result', 'type' => 'tns:result'],
    ]
);

$s->wsdl->addComplexType(
    'course_create_result_array',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    [],
    [['ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:course_create_result[]']],
    'tns:course_create_result'
);

$s->register(
    'WSCMCourse.CreateCourses',
    [
        'secret_key' => 'xsd:string',
        'courses' => 'tns:course_create[]',
    ],
    ['return' => 'tns:course_create_result_array']
);

$s->register(
    'WSCMCourse.EditCourse',
    [
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
        'extras' => 'tns:extra_field[]',
    ]
);

$s->wsdl->addComplexType(
    'course',
    'complexType',
    'struct',
    'all',
    '',
    [
        'id' => ['name' => 'id', 'type' => 'xsd:int'],
        'code' => ['name' => 'code', 'type' => 'xsd:string'],
        'title' => ['name' => 'title', 'type' => 'xsd:string'],
        'language' => ['name' => 'language', 'type' => 'xsd:string'],
        'visibility' => ['name' => 'visibility', 'type' => 'xsd:int'],
        'category_name' => ['name' => 'category_name', 'type' => 'xsd:string'],
        'number_students' => ['name' => 'number_students', 'type' => 'xsd:int'],
        'external_course_id' => ['name' => 'external_course_id', 'type' => 'xsd:string'],
    ]
);

$s->wsdl->addComplexType(
    'course_array',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    [],
    [['ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:course[]']],
    'tns:course'
);

$s->register(
    'WSCMCourse.ListCourses',
    [
        'secret_key' => 'xsd:string',
        'course_id_field_name' => 'xsd:string',
    ],
    ['return' => 'tns:course_array']
);

$s->register(
    'WSCMCourse.SubscribeUserToCourse',
    [
        'secret_key' => 'xsd:string',
        'course_id_field_name' => 'xsd:string',
        'course_id_value' => 'xsd:string',
        'user_id_field_name' => 'xsd:string',
        'user_id_value' => 'xsd:string',
        'status' => 'xsd:int',
    ]
);

$s->register(
    'WSCMCourse.UnsubscribeUserFromCourse',
    [
        'secret_key' => 'xsd:string',
        'course_id_field_name' => 'xsd:string',
        'course_id_value' => 'xsd:string',
        'user_id_field_name' => 'xsd:string',
        'user_id_value' => 'xsd:string',
    ]
);

$s->wsdl->addComplexType(
    'course_description',
    'complexType',
    'struct',
    'all',
    '',
    [
        'course_desc_id' => ['name' => 'course_desc_id', 'type' => 'xsd:int'],
        'course_desc_title' => ['name' => 'course_desc_title', 'type' => 'xsd:string'],
        'course_desc_content' => ['name' => 'course_desc_content', 'type' => 'xsd:string'],
    ]
);

$s->wsdl->addComplexType(
    'course_description_array',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    [],
    [['ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:course_description[]']],
    'tns:course_description'
);

$s->register(
    'WSCMCourse.GetCourseDescriptions',
    [
        'secret_key' => 'xsd:string',
        'course_id_field_name' => 'xsd:string',
        'course_id_value' => 'xsd:string',
    ],
    ['return' => 'tns:course_description_array']
);

$s->register(
    'WSCMCourse.EditCourseDescription',
    [
        'secret_key' => 'xsd:string',
        'course_id_field_name' => 'xsd:string',
        'course_id_value' => 'xsd:string',
        'course_desc_id' => 'xsd:int',
        'course_desc_title' => 'xsd:string',
        'course_desc_content' => 'xsd:string',
    ]
);

$s->register(
    'WSCMCourse.unreadMessage',
    [
        'username' => 'xsd:string',
        'password' => 'xsd:string',
    ],
    ['return' => 'xsd:string'],
    'urn:WSCMService',
    '',
    '',
    '',
    'Retorna a quantidade de mensagens nao lidas na caixa de entrada do usuario.'
);

$s->register(
    'WSCMCourse.getIdMessage',
    [
        'username' => 'xsd:string',
        'password' => 'xsd:string',
    ],
    ['return' => 'xsd:string'],
    'urn:WSCMService',
    '',
    '',
    '',
    'Retorna o ID das mensagens.'
);

$s->register(
    'WSCMCourse.nada',
    ['username' => 'xsd:string', 'password' => 'xsd:string'],
    ['return' => 'xsd:string']
);
