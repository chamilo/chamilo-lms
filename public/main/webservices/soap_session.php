<?php
/* For licensing terms, see /license.txt */
/**
 * Configures the WSSession SOAP service.
 *
 * @package chamilo.webservices
 */
require_once __DIR__.'/webservice_session.php';
require_once __DIR__.'/soap.php';

$s = WSSoapServer::singleton();

$s->register(
    'WSSession.CreateSession',
    [
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
        'extras' => 'tns:extra_field',
    ],
    ['return' => 'xsd:int']
);

$s->register(
    'WSSession.DeleteSession',
    [
        'secret_key' => 'xsd:string',
        'session_id_field_name' => 'xsd:string',
        'session_id_value' => 'xsd:string',
    ]
);

$s->register(
    'WSSession.EditSession',
    [
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
        'extras' => 'tns:extra_field',
    ]
);

$s->register(
    'WSSession.SubscribeUserToSession',
    [
        'secret_key' => 'xsd:string',
        'user_id_field_name' => 'xsd:string',
        'user_id_value' => 'xsd:string',
        'session_id_field_name' => 'xsd:string',
        'session_id_value' => 'xsd:string',
    ]
);

$s->register(
    'WSSession.UnsubscribeUserFromSession',
    [
        'secret_key' => 'xsd:string',
        'user_id_field_name' => 'xsd:string',
        'user_id_value' => 'xsd:string',
        'session_id_field_name' => 'xsd:string',
        'session_id_value' => 'xsd:string',
    ]
);

$s->register(
    'WSSession.SubscribeTeacherToSessionCourse',
    [
        'secret_key' => 'xsd:string',
        'user_id_field_name' => 'xsd:string',
        'user_id_value' => 'xsd:string',
        'session_id_field_name' => 'xsd:string',
        'session_id_value' => 'xsd:string',
        'course_id_field_name' => 'xsd:string',
        'course_id_value' => 'xsd:string',
    ]
);

$s->register(
    'WSSession.UnsubscribeTeacherFromSessionCourse',
    [
        'secret_key' => 'xsd:string',
        'user_id_field_name' => 'xsd:string',
        'user_id_value' => 'xsd:string',
        'session_id_field_name' => 'xsd:string',
        'session_id_value' => 'xsd:string',
        'course_id_field_name' => 'xsd:string',
        'course_id_value' => 'xsd:string',
    ]
);

$s->register(
    'WSSession.SubscribeCourseToSession',
    [
        'secret_key' => 'xsd:string',
        'course_id_field_name' => 'xsd:string',
        'course_id_value' => 'xsd:string',
        'session_id_field_name' => 'xsd:string',
        'session_id_value' => 'xsd:string',
    ]
);

$s->register(
    'WSSession.UnsubscribeCourseFromSession',
    [
        'secret_key' => 'xsd:string',
        'course_id_field_name' => 'xsd:string',
        'course_id_value' => 'xsd:string',
        'session_id_field_name' => 'xsd:string',
        'session_id_value' => 'xsd:string',
    ]
);
