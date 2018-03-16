<?php
/* For licensing terms, see /license.txt */

/**
 * @package chamilo.webservices
 */
require_once __DIR__.'/cm_webservice_inbox.php';
require_once __DIR__.'/cm_soap.php';

/**
 * Configures the WSCourse SOAP service.
 */
$s = WSCMSoapServer::singleton();

$s->register(
    'WSCMInbox.unreadMessage',
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
    'WSCMInbox.get_message_id',
    [
        'username' => 'xsd:string',
        'password' => 'xsd:string',
        'from' => 'xsd:string',
        'number_of_items' => 'xsd:string',
    ],
    ['return' => 'xsd:string'],
    'urn:WSCMService',
    '',
    '',
    '',
    'Retorna o ID das mensagens de entrada entre o intervalo de from até number_of_items.'
);

$s->register(
    'WSCMInbox.get_message_data',
    [
        'username' => 'xsd:string',
        'password' => 'xsd:string',
        'id' => 'xsd:string',
        'field' => 'xsd:string',
    ],
    ['return' => 'xsd:string'],
    'urn:WSCMService',
    '',
    '',
    '',
    'Retorna o conteudo do campo informado em field da mensagem de entrada id. Os campos retornados sao: sender, title, date, status e content.'
);

$s->register(
    'WSCMInbox.get_message_id_sent',
    [
        'username' => 'xsd:string',
        'password' => 'xsd:string',
        'from' => 'xsd:string',
        'number_of_items' => 'xsd:string',
    ],
    ['return' => 'xsd:string'],
    'urn:WSCMService',
    '',
    '',
    '',
    'Retorna o ID das mensagens de saida entre o intervalo de from até number_of_items.'
);

$s->register(
    'WSCMInbox.get_message_data_sent',
    [
        'username' => 'xsd:string',
        'password' => 'xsd:string',
        'id' => 'xsd:string',
        'field' => 'xsd:string',
    ],
    ['return' => 'xsd:string'],
    'urn:WSCMService',
    '',
    '',
    '',
    'Retorna o conteudo do campo informado em field da mensagem de saida id. Os campos retornados sao: sender, title, date, status e content.'
);

$s->register(
    'WSCMInbox.message_send',
    [
        'username' => 'xsd:string',
        'password' => 'xsd:string',
        'receiver_user_id' => 'xsd:string',
        'subject' => 'xsd:string',
        'content' => 'xsd:string',
    ],
    ['return' => 'xsd:string'],
    'urn:WSCMService',
    '',
    '',
    '',
    'Envia uma mensagem via rede social. Retorna o id da mensagem enviada.'
);
