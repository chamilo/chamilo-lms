<?php

require_once(dirname(__FILE__).'/cm_webservice_forum.php');
require_once(dirname(__FILE__).'/cm_soap.php');

/**
 * Configures the WSCourse SOAP service
 */
$s = WSCMSoapServer::singleton();



$s->register(
        'WSCMForum.get_foruns_id',
	array(
		'username' => 'xsd:string',
		'password' => 'xsd:string',
                'course_code' => 'xsd:string'
	),
	array('return' => 'xsd:string'),
        'urn:WSCMService',
        '',
        '',
        '',
        'Retorna o ID dos foruns de uma disciplina.'      
);

$s->register(
	'WSCMForum.get_forum_title',
	array(
		'username' => 'xsd:string',
		'password' => 'xsd:string',
                'course_code' => 'xsd:string',
                'forum_id' => 'xsd:string'
	),
	array('return' => 'xsd:string'),
        'urn:WSCMService',
        '',
        '',
        '',
        'Retorna o valor do titulo de um forum_id.'
);

$s->register(
	'WSCMForum.get_forum_threads_id',
	array(
		'username' => 'xsd:string',
		'password' => 'xsd:string',
                'course_code' => 'xsd:string',
                'forum_id' => 'xsd:string'
	),
	array('return' => 'xsd:string'),
        'urn:WSCMService',
        '',
        '',
        '',
        'Retorna o ID das threads de um forum_id.'
);

$s->register(
	'WSCMForum.get_forum_thread_data',
	array(
		'username' => 'xsd:string',
		'password' => 'xsd:string',
                'course_code' => 'xsd:string',
                'thread_id' => 'xsd:string',
                'field' => 'xsd:string'
	),
	array('return' => 'xsd:string'),
        'urn:WSCMService',
        '',
        '',
        '',
        'Retorna o campo field de um thread_id. Campos possiveis: title, date, sender, sender_name.'
);

$s->register(
	'WSCMForum.get_forum_thread_title',
	array(
		'username' => 'xsd:string',
		'password' => 'xsd:string',
                'course_code' => 'xsd:string',
                'thread_id' => 'xsd:string'
	),
	array('return' => 'xsd:string'),
        'urn:WSCMService',
        '',
        '',
        '',
        'Retorna o campo title de uma thread_id.'
);


$s->register(
	'WSCMForum.get_posts_id',
	array(
		'username' => 'xsd:string',
		'password' => 'xsd:string',
                'course_code' => 'xsd:string',
                'thread_id' => 'xsd:string'
	),
	array('return' => 'xsd:string'),
        'urn:WSCMService',
        '',
        '',
        '',
        'Retorna o ID dos posts de uma thread.'
);

$s->register(
	'WSCMForum.get_post_data',
	array(
		'username' => 'xsd:string',
		'password' => 'xsd:string',
                'course_code' => 'xsd:string',
                'post_id' => 'xsd:string',
                'field' => 'xsd:string'
	),
	array('return' => 'xsd:string'),
        'urn:WSCMService',
        '',
        '',
        '',
        'Retorna o campo field de um post_id. Campos possiveis: title, text, date, sender ou sender_name.'
);


$s->register(
	'WSCMForum.send_post',
	array(
		'username' => 'xsd:string',
		'password' => 'xsd:string',
                'course_code' => 'xsd:string',
                'forum_id' => 'xsd:string',
                'thread_id' => 'xsd:string',
                'title' => 'xsd:string',
                'content' => 'xsd:string'
	),
	array('return' => 'xsd:string'),
        'urn:WSCMService',
        '',
        '',
        '',
        'Envia um novo post ao forum_id.'
);
