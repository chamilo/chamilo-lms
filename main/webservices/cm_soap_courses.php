<?php
/* For licensing terms, see /license.txt */

/**
 * @package chamilo.webservices
 */
require_once __DIR__.'/cm_webservice_courses.php';
require_once __DIR__.'/cm_soap.php';

/**
 * Configures the WSCourse SOAP service.
 */
$s = WSCMSoapServer::singleton();

$s->register(
    'WSCMCourses.get_courses_code',
    [
        'username' => 'xsd:string',
        'password' => 'xsd:string',
    ],
    ['return' => 'xsd:string'],
    'urn:WSCMService',
    '',
    '',
    '',
    'Retorna o CODE dos cursos do username.'
);

$s->register(
    'WSCMCourses.get_course_title',
    [
        'username' => 'xsd:string',
        'password' => 'xsd:string',
        'course_code' => 'xsd:string',
    ],
    ['return' => 'xsd:string'],
    'urn:WSCMService',
    '',
    '',
    '',
    'Retorna o titulo/nome do curso de course_code informado'
);
