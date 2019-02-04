<?php
/* For licensing terms, see /license.txt */
/**
 * Configures the WSUser SOAP service.
 *
 * @package chamilo.webservices
 */
require_once __DIR__.'/webservice_user.php';
require_once __DIR__.'/soap.php';

/**
 * Configures the WSUser SOAP service.
 *
 * @package chamilo.webservices
 */
$s = WSSoapServer::singleton();

$s->register(
    'WSUser.EditUser',
    [
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
        'extras' => 'tns:extra_field',
    ]
);

$s->wsdl->addComplexType(
    'user_edit',
    'complexType',
    'struct',
    'all',
    '',
    [
        'user_id_field_name' => [
            'name' => 'user_id_field_name',
            'type' => 'xsd:string',
        ],
        'user_id_value' => [
            'name' => 'user_id_value',
            'type' => 'xsd:string',
        ],
        'firstname' => ['name' => 'firstname', 'type' => 'xsd:string'],
        'lastname' => ['name' => 'lastname', 'type' => 'xsd:string'],
        'status' => ['name' => 'status', 'type' => 'xsd:int'],
        'loginname' => ['name' => 'loginname', 'type' => 'xsd:string'],
        'password' => ['name' => 'password', 'type' => 'xsd:string'],
        'encrypt_method' => [
            'name' => 'encrypt_method',
            'type' => 'xsd:string',
        ],
        'email' => ['name' => 'email', 'type' => 'xsd:string'],
        'language' => ['name' => 'language', 'type' => 'xsd:string'],
        'phone' => ['name' => 'phone', 'type' => 'xsd:string'],
        'expiration_date' => [
            'name' => 'expiration_date',
            'type' => 'xsd:string',
        ],
        'extras' => ['name' => 'extras', 'type' => 'tns:extra_field'],
    ]
);

$s->wsdl->addComplexType(
    'user_edit_result',
    'complexType',
    'struct',
    'all',
    '',
    [
        'user_id_value' => [
            'name' => 'user_id_value',
            'type' => 'xsd:string',
        ],
        'result' => ['name' => 'result', 'type' => 'tns:result'],
    ]
);

$s->wsdl->addComplexType(
    'user_edit_result_array',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    [],
    [
        [
            'ref' => 'SOAP-ENC:arrayType',
            'wsdl:arrayType' => 'tns:user_edit_result[]',
        ],
    ],
    'tns:user_edit_result'
);

$s->register(
    'WSUser.EditUsers',
    [
        'secret_key' => 'xsd:string',
        'users' => 'tns:user_edit[]',
    ],
    ['return' => 'tns:user_edit_result_array']
);
