<?php
require_once(dirname(__FILE__).'/cm_webservice_user.php');
require_once(dirname(__FILE__).'/cm_soap.php');

/**
 * Configures the WSCourse SOAP service
 */
$s = WSCMSoapServer::singleton();

$s->register(
	'WSCMUser.find_id_user',
	array(
		'username' => 'xsd:string',
		'password' => 'xsd:string',
                'name' => 'xsd:string',
	),
	array('return' => 'xsd:string'),
        'urn:WSCMService',
        '',
        '',
        '',
        'Retorna o id de um usuario que contenha o parametro \'nome\' nos campos nome, sobrenome ou email (ordenado por nome).'
);

$s->register(
	'WSCMUser.get_user_name',
	array(
		'username' => 'xsd:string',
		'password' => 'xsd:string',
                'id' => 'xsd:string',
                'field' => 'xsd:string'
	),
	array('return' => 'xsd:string'),
        'urn:WSCMService',
        '',
        '',
        '',
        'Retorna o primeiro, ultimo ou os dois nomes de um usuarios. No campo field deve ser informado firstname, lastname, bothfl (para fistname lastname) ou bothlf (para lastname firstname)'
);

$s->register(
	'WSCMUser.get_link_user_picture',
	array(
		'username' => 'xsd:string',
		'password' => 'xsd:string',
                'id' => 'xsd:string'
	),
	array('return' => 'xsd:string'),
        'urn:WSCMService',
        '',
        '',
        '',
        'Retorna o link para a imagem do perfil do usuario.'
);

$s->register(
	'WSCMUser.send_invitation',
	array(
		'username' => 'xsd:string',
		'password' => 'xsd:string',
                'userfriend_id' => 'xsd:string',
                'content_message' => 'xsd:string'
	),
	array('return' => 'xsd:string'),
        'urn:WSCMService',
        '',
        '',
        '',
        'Envia um convite para estabelecer amizado no portal. O campo userfriend_id o id do possivel amigo e o campo content_message e a mensagem de solicitacao.'
);

$s->register(
	'WSCMUser.accept_friend',
	array(
		'username' => 'xsd:string',
		'password' => 'xsd:string',
                'userfriend_id' => 'xsd:string'
	),
	array('return' => 'xsd:string'),
        'urn:WSCMService',
        '',
        '',
        '',
        'Aceita o convite realizado pelo userfriend_id.'
);

$s->register(
	'WSCMUser.denied_invitation',
	array(
		'username' => 'xsd:string',
		'password' => 'xsd:string',
                'userfriend_id' => 'xsd:string'
	),
	array('return' => 'xsd:string'),
        'urn:WSCMService',
        '',
        '',
        '',
        'Recusa o contive de amizade feito pelo usuario userfriend_id.'
);

?>
