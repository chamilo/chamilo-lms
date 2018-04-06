<?php
/* For licensing terms, see /license.txt */

/**
 * @package chamilo.webservices
 */
require_once __DIR__.'/cm_webservice_forum.php';
require_once __DIR__.'/cm_soap.php';

/**
 * Configures the WSCourse SOAP service.
 */
$s = WSCMSoapServer::singleton();

$s->register(
    'WSCMForum.get_foruns_id',
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
    'Retorna o ID dos foruns de uma disciplina.'
);

$s->register(
    'WSCMForum.get_forum_title',
    [
        'username' => 'xsd:string',
        'password' => 'xsd:string',
        'course_code' => 'xsd:string',
        'forum_id' => 'xsd:string',
    ],
    ['return' => 'xsd:string'],
    'urn:WSCMService',
    '',
    '',
    '',
    'Retorna o valor do titulo de um forum_id.'
);

$s->register(
    'WSCMForum.get_forum_threads_id',
    [
        'username' => 'xsd:string',
        'password' => 'xsd:string',
        'course_code' => 'xsd:string',
        'forum_id' => 'xsd:string',
    ],
    ['return' => 'xsd:string'],
    'urn:WSCMService',
    '',
    '',
    '',
    'Retorna o ID das threads de um forum_id.'
);

$s->register(
    'WSCMForum.get_forum_thread_data',
    [
        'username' => 'xsd:string',
        'password' => 'xsd:string',
        'course_code' => 'xsd:string',
        'thread_id' => 'xsd:string',
        'field' => 'xsd:string',
    ],
    ['return' => 'xsd:string'],
    'urn:WSCMService',
    '',
    '',
    '',
    'Retorna o campo field de um thread_id. Campos possiveis: title, date, sender, sender_name.'
);

$s->register(
    'WSCMForum.get_forum_thread_title',
    [
        'username' => 'xsd:string',
        'password' => 'xsd:string',
        'course_code' => 'xsd:string',
        'thread_id' => 'xsd:string',
    ],
    ['return' => 'xsd:string'],
    'urn:WSCMService',
    '',
    '',
    '',
    'Retorna o campo title de uma thread_id.'
);

$s->register(
    'WSCMForum.get_posts_id',
    [
        'username' => 'xsd:string',
        'password' => 'xsd:string',
        'course_code' => 'xsd:string',
        'thread_id' => 'xsd:string',
    ],
    ['return' => 'xsd:string'],
    'urn:WSCMService',
    '',
    '',
    '',
    'Retorna o ID dos posts de uma thread.'
);

$s->register(
    'WSCMForum.get_post_data',
    [
        'username' => 'xsd:string',
        'password' => 'xsd:string',
        'course_code' => 'xsd:string',
        'post_id' => 'xsd:string',
        'field' => 'xsd:string',
    ],
    ['return' => 'xsd:string'],
    'urn:WSCMService',
    '',
    '',
    '',
    'Retorna o campo field de um post_id. Campos possiveis: title, text, date, sender ou sender_name.'
);

$s->register(
    'WSCMForum.send_post',
    [
        'username' => 'xsd:string',
        'password' => 'xsd:string',
        'course_code' => 'xsd:string',
        'forum_id' => 'xsd:string',
        'thread_id' => 'xsd:string',
        'title' => 'xsd:string',
        'content' => 'xsd:string',
    ],
    ['return' => 'xsd:string'],
    'urn:WSCMService',
    '',
    '',
    '',
    'Envia um novo post ao forum_id.'
);
