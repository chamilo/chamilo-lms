<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';
require_once __DIR__.'/webservice.php';

/**
 * SOAP error handler. Handles an error sending a SOAP fault.
 */
class WSSoapErrorHandler implements WSErrorHandler
{
    /**
     * Handles the error by sending a SOAP fault through the server.
     *
     * @param WSError Error to handle
     */
    public function handle($error)
    {
        $server = WSSoapServer::singleton();
        $server->fault(strval($error->code), $error->message);
    }
}

/**
 * SOAP server wrapper implementing a Singleton.
 */
class WSSoapServer
{
    /**
     * SOAP server instance.
     *
     * @var soap_server
     */
    private static $_instance;

    /**
     * Constructor.
     */
    public function __construct()
    {
    }

    /**
     * Singleton method.
     */
    public static function singleton()
    {
        if (!isset(self::$_instance)) {
            self::$_instance = new soap_server();
            // Set the error handler
            WSError::setErrorHandler(new WSSoapErrorHandler());
            // Configure the service
            self::$_instance->configureWSDL('WSService', 'urn:WSService');
        }

        return self::$_instance;
    }
}

$s = WSSoapServer::singleton();

$s->wsdl->addComplexType(
    'result',
    'complexType',
    'struct',
    'all',
    '',
    [
        'code' => ['name' => 'code', 'type' => 'xsd:int'],
        'message' => ['name' => 'message', 'type' => 'xsd:string'],
    ]
);

$s->wsdl->addComplexType(
    'extras',
    'complexType',
    'struct',
    'all',
    '',
    [
        'field_name' => ['name' => 'field_name', 'type' => 'xsd:string'],
        'field_value' => ['name' => 'field_value', 'type' => 'xsd:string'],
    ]
);

$s->wsdl->addComplexType(
    'extra_field',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    [],
    [
        [
            'ref' => 'SOAP-ENC:arrayType',
            'wsdl:arrayType' => 'tns:extras[]',
        ],
    ],
    'tns:extras'
);

/*
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
*/

$s->register(
    'WS.test',
    [],
    ['return' => 'xsd:string']
);

require_once __DIR__.'/soap_user.php';
require_once __DIR__.'/soap_course.php';
require_once __DIR__.'/soap_session.php';
require_once __DIR__.'/soap_report.php';

// Use the request to (try to) invoke the service
$HTTP_RAW_POST_DATA = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : '';
$s->service($HTTP_RAW_POST_DATA);
