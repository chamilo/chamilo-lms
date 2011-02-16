<?php
/* For licensing terms, see /license.txt */
/**
 * Configures the WSSession SOAP service
 * @package chamilo.webservices
 */

require_once(dirname(__FILE__).'/webservice_session.php');
require_once(dirname(__FILE__).'/soap.php');

$s = WSSoapServer::singleton();

$s->register(
	'WSSession.CreateSession',
	array(
		'secret_key' => 'xsd:string',
		'name' => 'xsd:string',
		'start_date' => 'xsd:string',
		'end_date' => 'xsd:string',
		'nb_days_access_before' => 'xsd:int',
		'nb_days_access_after' => 'xsd:int',
		'nolimit' => 'xsd:int',
		'visibility' => 'xsd:int',
		'user_id_field_name' => 'xsd:string',
		'user_id_value' => 'xsd:string',
		'session_id_field_name' => 'xsd:string',
		'session_id_value' => 'xsd:string',
		'extras' => 'tns:extra_field'
	),
	array('return' => 'xsd:int')
);

$s->register(
	'WSSession.DeleteSession',
	array(
		'secret_key' => 'xsd:string',
		'session_id_field_name' => 'xsd:string',
		'session_id_value' => 'xsd:string'
	)
);

$s->register(
	'WSSession.EditSession',
	array(
		'secret_key' => 'xsd:string',
		'name' => 'xsd:string',
		'start_date' => 'xsd:string',
		'end_date' => 'xsd:string',
		'nb_days_access_before' => 'xsd:int',
		'nb_days_access_after' => 'xsd:int',
		'nolimit' => 'xsd:int',
		'visibility' => 'xsd:int',
		'user_id_field_name' => 'xsd:string',
		'user_id_value' => 'xsd:string',
		'session_id_field_name' => 'xsd:string',
		'session_id_value' => 'xsd:string',
		'extras' => 'tns:extra_field'
	)
);

$s->register(
	'WSSession.SubscribeUserToSession',
	array(
		'secret_key' => 'xsd:string',
		'user_id_field_name' => 'xsd:string',
		'user_id_value' => 'xsd:string',
		'session_id_field_name' => 'xsd:string',
		'session_id_value' => 'xsd:string'
	)
);

$s->register(
	'WSSession.UnsubscribeUserFromSession',
	array(
		'secret_key' => 'xsd:string',
		'user_id_field_name' => 'xsd:string',
		'user_id_value' => 'xsd:string',
		'session_id_field_name' => 'xsd:string',
		'session_id_value' => 'xsd:string'
	)
);

$s->register(
	'WSSession.SubscribeCourseToSession',
	array(
		'secret_key' => 'xsd:string',
		'course_id_field_name' => 'xsd:string',
		'course_id_value' => 'xsd:string',
		'session_id_field_name' => 'xsd:string',
		'session_id_value' => 'xsd:string'
	)
);

$s->register(
	'WSSession.UnsubscribeCourseFromSession',
	array(
		'secret_key' => 'xsd:string',
		'course_id_field_name' => 'xsd:string',
		'course_id_value' => 'xsd:string',
		'session_id_field_name' => 'xsd:string',
		'session_id_value' => 'xsd:string'
	)
);
