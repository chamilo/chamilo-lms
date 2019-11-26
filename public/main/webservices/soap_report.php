<?php
/* For licensing terms, see /license.txt */
/**
 * Configures the WSReport SOAP service.
 *
 * @package chamilo.webservices
 */
require_once __DIR__.'/webservice_report.php';
require_once __DIR__.'/soap.php';
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
    'course_id',
    'complexType',
    'struct',
    'all',
    '',
    [
        'course_id_field_name' => [
            'name' => 'course_id_field_name',
            'type' => 'xsd:string',
        ],
        'course_id_value' => [
            'name' => 'course_id_value',
            'type' => 'xsd:string',
        ],
    ]
);

$s->wsdl->addComplexType(
    'session_id',
    'complexType',
    'struct',
    'all',
    '',
    [
        'session_id_field_name' => [
            'name' => 'session_id_field_name',
            'type' => 'xsd:string',
        ],
        'session_id_value' => [
            'name' => 'session_id_value',
            'type' => 'xsd:string',
        ],
    ]
);

/*
$s->wsdl->addComplexType(
    'user_result',
    'complexType',
    'struct',
    'all',
    '',
    array(
        'user_id_value' => array('name' => 'user_id_value', 'type' => 'xsd:string'),
        'result' => array('name' => 'result', 'type' => 'tns:result')
    )
);*/

$s->wsdl->addComplexType(
    'user_result',
    'complexType',
    'struct',
    'all',
    '',
    [
        'id' => ['name' => 'id', 'type' => 'xsd:string'],
        'title' => ['name' => 'title', 'type' => 'xsd:string'],
    ]
);

$s->wsdl->addComplexType(
    'progress_result',
    'complexType',
    'struct',
    'all',
    '',
    [
        'progress_bar_mode' => [
            'name' => 'progress_bar_mode',
            'type' => 'xsd:string',
        ],
        'progress_db' => ['name' => 'progress_db', 'type' => 'xsd:string'],
    ]
);

$s->wsdl->addComplexType(
    'score_result',
    'complexType',
    'struct',
    'all',
    '',
    [
        'min_score' => ['name' => 'min_score', 'type' => 'xsd:string'],
        'max_score' => ['name' => 'max_score', 'type' => 'xsd:string'],
        'mastery_score' => [
            'name' => 'mastery_score',
            'type' => 'xsd:string',
        ],
        'current_score' => [
            'name' => 'current_score',
            'type' => 'xsd:string',
        ],
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
    'WSReport.GetTimeSpentOnPlatform',
    [
        'secret_key' => 'xsd:string',
        'user_id_field_name' => 'xsd:string',
        'user_id_value' => 'xsd:string',
    ],
    ['return' => 'xsd:string']
);

$s->register(
    'WSReport.GetTimeSpentOnCourse',
    [
        'secret_key' => 'xsd:string',
        'user_id_field_name' => 'xsd:string',
        'user_id_value' => 'xsd:string',
        'course_id_field_name' => 'xsd:string',
        'course_id_value' => 'xsd:string',
    ],
    ['return' => 'xsd:string']
);

$s->register(
    'WSReport.GetTimeSpentOnCourseInSession',
    [
        'secret_key' => 'xsd:string',
        'user_id_field_name' => 'xsd:string',
        'user_id_value' => 'xsd:string',
        'course_id_field_name' => 'xsd:string',
        'course_id_value' => 'xsd:string',
        'session_id_field_name' => 'xsd:string',
        'session_id_value' => 'xsd:string',
    ],
    ['return' => 'xsd:string']
);

$s->register(
    'WSReport.GetTimeSpentOnLearnpathInCourse',
    [
        'secret_key' => 'xsd:string',
        'user_id_field_name' => 'xsd:string',
        'user_id_value' => 'xsd:string',
        'course_id_field_name' => 'xsd:string',
        'course_id_value' => 'xsd:string',
        'learnpath_id' => 'xsd:string',
    ],
    ['return' => 'xsd:string']
);

$s->register(
    'WSReport.GetLearnpathsByCourse',
    [
        'secret_key' => 'xsd:string',
        'user_id_field_name' => 'xsd:string',
        'user_id_value' => 'xsd:string',
        'course_id_field_name' => 'xsd:string',
        'course_id_value' => 'xsd:string',
    ],
    ['return' => 'tns:user_result_array']
);

$s->register(
    'WSReport.GetLearnpathProgress',
    [
        'secret_key' => 'xsd:string',
        'user_id_field_name' => 'xsd:string',
        'user_id_value' => 'xsd:string',
        'course_id_field_name' => 'xsd:string',
        'course_id_value' => 'xsd:string',
        'learnpath_id' => 'xsd:string',
    ],
    ['return' => 'tns:progress_result']
);

$s->register(
    'WSReport.GetLearnpathHighestLessonLocation',
    [
        'secret_key' => 'xsd:string',
        'user_id_field_name' => 'xsd:string',
        'user_id_value' => 'xsd:string',
        'course_id_field_name' => 'xsd:string',
        'course_id_value' => 'xsd:string',
        'learnpath_id' => 'xsd:string',
    ],
    ['return' => 'xsd:string']
);

$s->register(
    'WSReport.GetLearnpathScoreSingleItem',
    [
        'secret_key' => 'xsd:string',
        'user_id_field_name' => 'xsd:string',
        'user_id_value' => 'xsd:string',
        'course_id_field_name' => 'xsd:string',
        'course_id_value' => 'xsd:string',
        'learnpath_id' => 'xsd:string',
        'learnpath_item_id' => 'xsd:string',
    ],
    ['return' => 'tns:score_result']
);

$s->register(
    'WSReport.GetLearnpathStatusSingleItem',
    [
        'secret_key' => 'xsd:string',
        'user_id_field_name' => 'xsd:string',
        'user_id_value' => 'xsd:string',
        'course_id_field_name' => 'xsd:string',
        'course_id_value' => 'xsd:string',
        'learnpath_id' => 'xsd:string',
        'learnpath_item_id' => 'xsd:string',
    ],
    ['return' => 'xsd:string']
);

$s->register(
    'WSReport.test',
    [],
    ['return' => 'xsd:string']
);
