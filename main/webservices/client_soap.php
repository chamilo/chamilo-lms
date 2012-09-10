<?php
/* For licensing terms, see /license.txt */

/* 
 * 
 * 1. This script creates users everytime the page is executed using the Chamilo Webservices 
 * 2. The username is generated everytime with a random value from 0 to 1000
 * 3. The default user extra field (profile) is "uid" is created when calling the WSCreateUserPasswordCrypted for the first time, you can change this value. 
 *    In this field your third party user_id will be registered. See the main/admin/user_fields.php to view the current user fields.
 * 4. You need to create manually a course called Test(with code TEST) After the user was created the new user will be added to this course via webservices.
 
 * 
 */

exit; //Uncomment this in order to execute the page

require_once '../inc/global.inc.php';
$libpath = api_get_path(LIBRARY_PATH);
require_once $libpath.'nusoap/nusoap.php';

// Create the client instance
$url = api_get_path(WEB_CODE_PATH)."webservices/registration.soap.php?wsdl";

$security_key = $_configuration['security_key']; // see the main/inc/configuration.php file to get this value

$client = new nusoap_client($url, true);

$soap_error = $client->getError();

if (!empty($soap_error)) {
    $error_message = 'Nusoap object creation failed: ' . $soap_error;
    throw new Exception($error_message);
}

$client->debug_flag = true;

$ip_address = $_SERVER['SERVER_ADDR']; // This should be the IP address of the client

//Secret key
$secret_key = sha1($ip_address.$security_key);// Hash of the combination of IP Address + Chamilo security key

//Creating a random user name and a random user id, this values need to be provided from your system
$random_user_id = rand(0, 1000);
$generate_user_name = 'jbrion'.$random_user_id;
$generate_password = sha1($generate_user_name);
$user_field = 'uid';

// 1. Create user

$params = array(    'firstname'                 => 'Jon',
                    'lastname'                  => 'Brion',
                    'status'                    => '5', // STUDENT
                    'email'                     => 'jon@example.com',
                    'loginname'                 => $generate_user_name,
                    'password'                  => $generate_password, // encrypted using sha1
                    'encrypt_method'            => 'sha1',
                    'language'                  => 'english',
                    'official_code'             => 'official',
                    'phone'                     => '00000000',
                    'expiration_date'           => '0000-00-00',
                    'original_user_id_name'     => $user_field, // the extra user field that will be automatically created in the user profile see: main/admin/user_fields.php
                    'original_user_id_value'    => $random_user_id, // third party user id
                    'secret_key'                => $secret_key
);

$user_id = $client->call('WSCreateUserPasswordCrypted', array('createUserPasswordCrypted' => $params));

if (!empty($user_id) && is_numeric($user_id)) {
    
    // 2. Get user info of the user
    echo '<h2>Trying to create an user via webservices</h2>';
    $original_params = $params;
    
    $params = array('original_user_id_value'    => $random_user_id, // third party user id
                    'original_user_id_name'     => $user_field, // the system field in the user profile (See Profiling)
                    'secret_key'                => $secret_key);

    $result = $client->call('WSGetUser', array('GetUser' => $params));
    
    if ($result) {
        echo "Random user was created user_id: $user_id <br /><br />";
        echo 'User info: <br />';
        print_r($original_params);
        echo '<br /><br />';
    } else {
        echo $result;
    }
        
    //3.Adding user to the course TEST. The course TEST must be created manually in Chamilo
    
    echo '<h2>Trying to add user to a course called TEST via webservices</h2>';
    
    $course_info = api_get_course_info('TEST');
    
    if (!empty($course_info)) {    
        $params = array('course'        => 'TEST', //Chamilo string course code
                        'user_id'       => $user_id,
                        'secret_key'    => $secret_key);
        $result = $client->call('WSSubscribeUserToCourseSimple', array('subscribeUserToCourseSimple' => $params));
    } else {
        echo 'Course TEST does not exists please create one course with code "TEST"';
    }
    
    if ($result == 1) {
        echo "User $user_id was added to course TEST";
    } else {
        echo $result;
    }  
    
    //4. Adding course Test to the Session Session1
       
    $course_id_list = array (
                            array('course_code' => 'TEST1'), 
                            array('course_code' => 'TEST2')
                        );
    $params = array('coursessessions' => array(
                                                array('original_course_id_values'   => $course_id_list,
                                                      'original_course_id_name'     => 'course_id_name',
                                                      'original_session_id_value'   => '1',
                                                      'original_session_id_name'    => 'session_id_value')      
                                                ),    
                    'secret_key' => $secret_key);
    
    //$result = $client->call('WSSuscribeCoursesToSession', array('subscribeCoursesToSession' => $params));
        
    
    
    // ------------------------
    //Calling the WSSubscribeUserToCourse
    /*
    $course_array = array(   'original_course_id_name' => 'TEST',
                             'original_course_id_value' => 'TEST'
                            );
    
    $user_array     = array('original_user_id_value' =>  $user_id, 
                            'original_user_id_name' => 'name');
    $user_courses   = array();
    
    $user_courses[] = array (   'course_id' => $course_array,
                                'user_id'   => $user_array,
                                'status'    => '1'
                            );
    
    $params = array (
                    'userscourses'       => $user_courses,
                    'secret_key'         => $secret_key);

    $result = $client->call('WSSubscribeUserToCourse', array('subscribeUserToCourse' => $params));
    var_dump($result);*/
    
      
} else {
    echo 'User was not created, activate the debug=true in the registration.soap.php file and see the error logs';
}

// Check for an error
$err = $client->getError();

if ($err) {
    // Display the error
    echo '<h2>Constructor error</h2><pre>' . $err . '</pre>';
}

if ($client->fault) {
    echo '<h2>Fault</h2><pre>';
    print_r($result);
    echo '</pre>';
} else {
    // Check for errors
    $err = $client->getError();
    if ($err) {
        // Display the error
        echo '<h2>Error</h2><pre>' . $err . '</pre>';
    } else {
        // Display the result
        echo '<h2>There are no errors</h2>';
        var_dump($result);
    }
}