<?php

require_once(dirname(__FILE__).'/cm_webservice_courses.php');
require_once(dirname(__FILE__).'/cm_soap.php');

/**
 * Configures the WSCourse SOAP service
 */
$s = WSCMSoapServer::singleton();



$s->register(
	'WSCMCourses.get_courses_code',
	array(
		'username' => 'xsd:string',
		'password' => 'xsd:string'
	),
	array('return' => 'xsd:string'),
        'urn:WSCMService',
        '',
        '',
        '',
        'Retorna o CODE dos cursos do username.'
        
);

$s->register(
	'WSCMCourses.get_course_title',
	array(
		'username' => 'xsd:string',
		'password' => 'xsd:string',
                'course_code' => 'xsd:string',
	),
	array('return' => 'xsd:string'),
        'urn:WSCMService',
        '',
        '',
        '',
        'Retorna o titulo/nome do curso de course_code informado'

);



