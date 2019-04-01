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

$s->wsdl->addComplexType(
    'user_id',
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
    ]
);

$s->wsdl->addComplexType(
    'user_result',
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
    'user_result_array',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    [],
    [
        [
            'ref' => 'SOAP-ENC:arrayType',
            'wsdl:arrayType' => 'tns:user_result[]',
        ],
    ],
    'tns:user_result'
);

$s->register(
    'WSUser.DisableUser',
    [
        'secret_key' => 'xsd:string',
        'user_id_field_name' => 'xsd:string',
        'user_id_value' => 'xsd:string',
    ]
);

$s->register(
    'WSUser.DisableUsers',
    ['secret_key' => 'xsd:string', 'users' => 'tns:user_id[]'],
    ['return' => 'tns:user_result_array']
);

$s->register(
    'WSUser.EnableUser',
    [
        'secret_key' => 'xsd:string',
        'user_id_field_name' => 'xsd:string',
        'user_id_value' => 'xsd:string',
    ]
);

$s->register(
    'WSUser.EnableUsers',
    ['secret_key' => 'xsd:string', 'users' => 'tns:user_id[]'],
    ['return' => 'tns:user_result_array']
);

$s->register(
    'WSUser.DeleteUser',
    [
        'secret_key' => 'xsd:string',
        'user_id_field_name' => 'xsd:string',
        'user_id_value' => 'xsd:string',
    ]
);

$s->register(
    'WSUser.DeleteUsers',
    ['secret_key' => 'xsd:string', 'users' => 'tns:user_id[]'],
    ['return' => 'tns:user_result_array']
);

$s->register(
    'WSUser.CreateUser',
    [
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
        'extras' => 'tns:extra_field',
    ],
    ['return' => 'xsd:int']
);

$s->wsdl->addComplexType(
    'user_create',
    'complexType',
    'struct',
    'all',
    '',
    [
        'firstname' => ['name' => 'firstname', 'type' => 'xsd:string'],
        'lastname' => ['name' => 'lastname', 'type' => 'xsd:string'],
        'status' => ['name' => 'status', 'type' => 'xsd:int'],
        'loginname' => ['name' => 'loginname', 'type' => 'xsd:string'],
        'password' => ['name' => 'password', 'type' => 'xsd:string'],
        'encrypt_method' => [
            'name' => 'encrypt_method',
            'type' => 'xsd:string',
        ],
        'user_id_field_name' => [
            'name' => 'user_id_field_name',
            'type' => 'xsd:string',
        ],
        'user_id_value' => [
            'name' => 'user_id_value',
            'type' => 'xsd:string',
        ],
        'visibility' => ['name' => 'visibility', 'type' => 'xsd:int'],
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
    'user_create_result',
    'complexType',
    'struct',
    'all',
    '',
    [
        'user_id_value' => [
            'name' => 'user_id_value',
            'type' => 'xsd:string',
        ],
        'user_id_generated' => [
            'name' => 'user_id_generated',
            'type' => 'xsd:int',
        ],
        'result' => ['name' => 'result', 'type' => 'tns:result'],
    ]
);

$s->wsdl->addComplexType(
    'user_create_result_array',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    [],
    [
        [
            'ref' => 'SOAP-ENC:arrayType',
            'wsdl:arrayType' => 'tns:user_create_result[]',
        ],
    ],
    'tns:user_create_result'
);

$s->register(
    'WSUser.CreateUsers',
    [
        'secret_key' => 'xsd:string',
        'users' => 'tns:user_create[]',
    ],
    ['return' => 'tns:user_create_result_array']
);

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
