<?php
/* For licensing terms, see /license.txt */

/*
 *
 * 1. This script creates users every time the page is executed using the Chamilo Webservices
 * 2. The username is generated every time with a random value from 0 to 1000
 * 3. The default user extra field (profile) is "uid" is created
 *
 * when calling the WSCreateUserPasswordCrypted for the first time, you can change this value.
 * In this field your third party user_id will be registered.
 * See the main/admin/user_fields.php to view the current user fields.
 * 4. You need to create manually a course called Test(with code TEST)
 * After the user was created the new user will be added to this course via webservices.
 *
 */
exit;
require_once __DIR__.'/../inc/global.inc.php';
// Create the client instance
$url = api_get_path(WEB_CODE_PATH).'webservices/registration.soap.php?wsdl';
//$url = api_get_path(WEB_CODE_PATH)."webservices/access_url.php?wsdl";

global $_configuration;
// see the main/inc/configuration.php file to get this value
$security_key = $_configuration['security_key'];

$client = new nusoap_client($url, true);
/*$client->xml_encoding = 'UTF-8';
$client->http_encoding = 'UTF-8';
$client->charencoding = 'UTF-8';*/

$soap_error = $client->getError();

if (!empty($soap_error)) {
    $error_message = 'Nusoap object creation failed: '.$soap_error;
    throw new Exception($error_message);
}
$client->setDebugLevel(10000);
$client->debug_flag = true;

// This should be the IP address of the client
$ip_address = $_SERVER['SERVER_ADDR'];
$ip_address = "192.168.1.54";
$ip_address = "127.0.0.1";

//Secret key
$secret_key = sha1($ip_address.$security_key); // Hash of the combination of IP Address + Chamilo security key
//$secret_key = sha1($security_key);

//Creating a random user_id, this values need to be provided from your system
$random_user_id = rand(0, 1000);
//Creating a random username this values need to be provided from your system
$generate_user_name = 'jbrion'.$random_user_id;
//Creating a password (the username)
$generate_password = sha1($generate_user_name);
$user_field = 'uid';
$sessionField = 'external_session_id';

$params = [
    'firstname' => 'Jon',
    'lastname' => 'Brion',
    'status' => '5', // 5 STUDENT - 1 TEACHER
    'email' => 'jon@example.com',
    'loginname' => $generate_user_name,
    'password' => $generate_password, // encrypted using sha1
    'encrypt_method' => 'bcrypt',
    'language' => 'english',
    'official_code' => 'official',
    'phone' => '00000000',
    'expiration_date' => '0000-00-00',
    /* the extra user field that will be automatically created
    in the user profile see: main/admin/user_fields.php */
    'original_user_id_name' => $user_field,
    // third party user id
    'original_user_id_value' => $random_user_id,
    'secret_key' => $secret_key,
    // Extra fields
    'extra' => [
        ['field_name' => 'ruc', 'field_value' => '123'],
        ['field_name' => 'DNI', 'field_value' => '4200000'],
    ],
];

//1. Create user webservice
$user_id = $client->call(
    'WSCreateUserPasswordCrypted',
    ['createUserPasswordCrypted' => $params]
);

// Check for an error
$err = $client->getError();

if ($err) {
    // Display the error
    echo '<h2>Constructor error</h2><pre>'.$err.'</pre>';
}

$sessionValueRandom = uniqid();

$params = [
    'sessions' => [
        [
            'name' => 'session from ws: '.$sessionValueRandom,
            'year_start' => '2015',
            'month_start' => '10',
            'day_start' => '1',
            'year_end' => '',
            'month_end' => '',
            'day_end' => '',
            'nb_days_access_before' => 0,
            'nb_days_access_after' => 0,
            'nolimit' => 1,
            'user_id' => 1,
            'original_session_id_name' => $sessionField,
            'original_session_id_value' => $sessionValueRandom,
            'extra' => '',
        ],
    ],
    'secret_key' => $secret_key,
];

$sessionId = $client->call(
    'WSCreateSession',
    ['createSession' => $params]
);

$data = [
    'secret_key' => $secret_key,
    'userssessions' => [
        [
            'original_user_id_name' => $user_field,
            'original_session_id_value' => $sessionValueRandom,
            'original_session_id_name' => $sessionField,
            'original_user_id_values' => [
                [
                    'original_user_id_value' => $random_user_id,
                ],
            ],
        ],
    ],
];

$result = $client->call(
    'WSSuscribeUsersToSession',
    ['subscribeUsersToSession' => $data]
);
$err = $client->getError();
var_dump($result);
var_dump($err);
var_dump($user_id);

if (!empty($user_id) && is_numeric($user_id)) {
    // 2. Get user info of the new user
    echo '<h2>Trying to create an user via webservices</h2>';
    $original_params = $params;

    $params = [
        'original_user_id_value' => $random_user_id, // third party user id
        'original_user_id_name' => $user_field, // the system field in the user profile (See Profiling)
        'secret_key' => $secret_key,
    ];

    $result = $client->call('WSGetUser', ['GetUser' => $params]);

    if ($result) {
        echo "Random user was created user_id: $user_id <br /><br />";
        echo 'User info: <br />';
        print_r($original_params);
        echo '<br /><br />';
    } else {
        echo $result;
    }

    // 3. Updating user info
    $params = [
        'firstname' => 'Jon edited',
        'lastname' => 'Brion edited',
        'status' => '5',
        // STUDENT
        'email' => 'jon@example.com',
        'username' => $generate_user_name,
        'password' => $generate_password,
        // encrypted using sha1
        'encrypt_method' => 'sha1',
        'phone' => '00000000',
        'expiration_date' => '0000-00-00',
        'original_user_id_name' => $user_field,
        // the extra user field that will be automatically created in the user profile see: main/admin/user_fields.php
        'original_user_id_value' => $random_user_id,
        // third party user id
        'secret_key' => $secret_key,
        'extra' => [
            ['field_name' => 'ruc', 'field_value' => '666 edited'],
            ['field_name' => 'DNI', 'field_value' => '888 edited'],
        ],
    ];
    $result = $client->call('WSEditUserPasswordCrypted', ['editUserPasswordCrypted' => $params]);

    if ($result) {
        echo "Random user was update user_id: $user_id <br /><br />";
        echo 'User info: <br />';
        print_r($params);
        echo '<br /><br />';
    } else {
        $err = $client->getError();
        var_dump($result);
        var_dump($err);
    }

    $params = [
        'ids' => [
            [
                'original_user_id_name' => $user_field,
                'original_user_id_value' => $random_user_id,
            ],
        ],
        'secret_key' => $secret_key,
    ];

    // Disable user
    $result = $client->call('WSDisableUsers', ['user_ids' => $params]);

    // Enable user
    $result = $client->call('WSEnableUsers', ['user_ids' => $params]);

    // 4 Creating course TEST123
    $params = [
        'courses' => [
            [
                'title' => 'PRUEBA', //Chamilo string course code
                'category_code' => 'LANG',
                'wanted_code' => '',
                'course_language' => 'english',
                'original_course_id_name' => 'course_id_test',
                'original_course_id_value' => '666',
            ],
        ],
        'secret_key' => $secret_key,
    ];

    $result = $client->call('WSCreateCourse', ['createCourse' => $params]);

    // 5 .Adding user to the course TEST. The course TEST must be created manually in Chamilo
    echo '<h2>Trying to add user to a course called TEST via webservices</h2>';

    $course_info = api_get_course_info('TEST123');

    if (!empty($course_info)) {
        $params = [
            'course' => 'TEST', //Chamilo string course code
            'user_id' => $user_id,
            'secret_key' => $secret_key,
        ];
        $result = $client->call('WSSubscribeUserToCourseSimple', ['subscribeUserToCourseSimple' => $params]);
    } else {
        echo 'Course TEST does not exists please create one course with code "TEST"';
    }

    if ($result == 1) {
        echo "User $user_id was added to course TEST";
    } else {
        echo $result;
    }

    // 4. Adding course Test to the Session Session1
    $course_id_list = [
        ['course_code' => 'TEST1'],
        ['course_code' => 'TEST2'],
    ];
    $params = [
        'coursessessions' => [
            [
                'original_course_id_values' => $course_id_list,
                'original_course_id_name' => 'course_id_name',
                'original_session_id_value' => '1',
                'original_session_id_name' => 'session_id_value',
            ],
        ],
        'secret_key' => $secret_key,
    ];

    //$result = $client->call('WSSuscribeCoursesToSession', array('subscribeCoursesToSession' => $params));

    // ------------------------
    //Calling the WSSubscribeUserToCourse
    $course_array = [
        'original_course_id_name' => 'TEST',
        'original_course_id_value' => 'TEST',
    ];

    $user_array = [
        'original_user_id_value' => $random_user_id,
        'original_user_id_name' => $user_field,
    ];
    $user_courses = [];

    $user_courses[] = [
        'course_id' => $course_array,
        'user_id' => $user_array,
        'status' => '1',
    ];

    $params = [
        'userscourses' => $user_courses,
        'secret_key' => $secret_key,
    ];

    $result = $client->call('WSSubscribeUserToCourse', ['subscribeUserToCourse' => $params]);
    var_dump($result);
    $params = [
        'secret_key' => $secret_key,
        'ids' => [
            'original_user_id_value' => $random_user_id,
            'original_user_id_name' => $user_field,
        ],
    ];

    // Delete user
    $result = $client->call('WSDeleteUsers', ['user_ids' => $params]);
    exit;
} else {
    echo 'User was not created, activate the debug=true in the registration.soap.php file and see the error logs';
}

// Check for an error
$err = $client->getError();

if ($err) {
    // Display the error
    echo '<h2>Constructor error</h2><pre>'.$err.'</pre>';
}

//1. Create user webservice
$result = $client->call(
    'WSGetPortals',
    ['getPortals' => ['secret_key' => $secret_key]]
);

$result = $client->call(
    'WSAddUserToPortal',
    ['addUserToPortal' => ['user_id' => 1, 'portal_id' => 1, 'secret_key' => $secret_key]]
);

$result = $client->call(
    'WSGetPortalListFromUser',
    ['getPortalListFromUser' => ['user_id' => 1, 'secret_key' => $secret_key]]
);

$result = $client->call(
    'WSGetPortalListFromCourse',
    ['getPortalListFromCourse' => ['course_id' => 20, 'secret_key' => $secret_key]]
);

$result = $client->call(
    'WSAddCourseToPortal',
    ['addCourseToPortal' => ['course_id' => 20, 'portal_id' => 1, 'secret_key' => $secret_key]]
);

$result = $client->call(
    'WSRemoveUserFromPortal',
    ['removeUserFromPortal' => ['course_id' => 20, 'portal_id' => 1, 'secret_key' => $secret_key]]
);
var_dump($user_id); exit;

if ($client->fault) {
    echo '<h2>Fault</h2><pre>';
    print_r($result);
    echo '</pre>';
} else {
    // Check for errors
    $err = $client->getError();
    if ($err) {
        // Display the error
        echo '<h2>Error</h2><pre>'.$err.'</pre>';
    } else {
        // Display the result
        echo '<h2>There are no errors</h2>';
        var_dump($result);
    }
}
