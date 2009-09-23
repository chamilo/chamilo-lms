<?php //$id: $
require '../inc/global.inc.php';
$libpath = api_get_path(LIBRARY_PATH);
require_once $libpath.'nusoap/nusoap.php';
require_once $libpath.'fileManage.lib.php';
require_once $libpath.'usermanager.lib.php';
require_once $libpath.'fileUpload.lib.php';
require_once api_get_path(INCLUDE_PATH).'lib/mail.lib.inc.php';
require_once $libpath.'add_course.lib.inc.php';
require_once $libpath.'course.lib.php';
require_once $libpath.'sessionmanager.lib.php';

// Create the server instance
$server = new soap_server();
// Initialize WSDL support
$server->configureWSDL('WSRegistration', 'urn:WSRegistration');


/* Register DokeosWSCreateUsers function */
// Register the data structures used by the service


// Prepare input params
$server->wsdl->addComplexType(
'extras',
'complexType',
'struct',
'all',
'',
array(
		'field_name' => array('name' => 'field_name', 'type' => 'xsd:string'),
		'field_value' => array('name' => 'field_value', 'type' => 'xsd:string')
     )
);

$server->wsdl->addComplexType(
'extrasList',
'complexType',
'array',
'',
'SOAP-ENC:Array',
array(),
array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType' => 'tns:extras[]')),'tns:extras'
);

$server->wsdl->addComplexType(
	'usersParams',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'firstname' => array('name' => 'firstname', 'type' => 'xsd:string'),
		'lastname' => array('name' => 'lastname', 'type' => 'xsd:string'),
		'status' => array('name' => 'status', 'type' => 'xsd:string'),
		'email' => array('name' => 'email', 'type' => 'xsd:string'),
		'loginname' => array('name' => 'loginname', 'type' => 'xsd:string'),
		'password' => array('name' => 'password', 'type' => 'xsd:string'),
		'language' => array('name' => 'language', 'type' => 'xsd:string'),
		'phone' => array('name' => 'phone', 'type' => 'xsd:string'),
		'expiration_date' => array('name' => 'expiration_date', 'type' => 'xsd:string'),
		'original_user_id_name' => array('name' => 'original_user_id_name', 'type' => 'xsd:string'),
		'original_user_id_value' => array('name' => 'original_user_id_value', 'type' => 'xsd:string'),
		'extra' => array('name' => 'extra', 'type' => 'tns:extrasList')
	)
);

$server->wsdl->addComplexType(
'usersParamsList',
'complexType',
'array',
'',
'SOAP-ENC:Array',
array(),
array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType' => 'tns:usersParams[]')),'tns:usersParams'
);

$server->wsdl->addComplexType(
	'createUsers',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'users' => array('name' => 'users', 'type' => 'tns:usersParamsList'),
		'secret_key' => array('name' => 'secret_key', 'type' => 'xsd:string')
	)
);

// Prepare output params, in this case will return an array
$server->wsdl->addComplexType(
'result_createUsers',
'complexType',
'struct',
'all',
'',
array(
		'original_user_id_value' => array('name' => 'original_user_id_value', 'type' => 'xsd:string'),
		'result' => array('name' => 'result', 'type' => 'xsd:string')
     )
);

$server->wsdl->addComplexType(
'results_createUsers',
'complexType',
'array',
'',
'SOAP-ENC:Array',
array(),
array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType' => 'tns:result_createUsers[]')),'tns:result_createUsers'
);

// Register the method to expose
$server->register('DokeosWSCreateUsers',			// method name
	array('createUsers' => 'tns:createUsers'),		// input parameters
	array('return' => 'tns:results_createUsers'),	// output parameters
	'urn:WSRegistration',							// namespace
	'urn:WSRegistration#DokeosWSCreateUsers',		// soapaction
	'rpc',											// style
	'encoded',										// use
	'This service adds a user'						// documentation
);


// Define the method DokeosWSCreateUsers
function DokeosWSCreateUsers($params) {

	global $_user, $userPasswordCrypted, $_configuration;

	$secret_key = $params['secret_key'];
	$security_key = $_SERVER['REMOTE_ADDR'].$_configuration['security_key'];

	if (!api_is_valid_secret_key($secret_key, $security_key)) {
		return -1; // The secret key is incorrect.
	}

	// database table definition
	$table_user = Database::get_main_table(TABLE_MAIN_USER);
	$t_uf 		= Database::get_main_table(TABLE_MAIN_USER_FIELD);
	$t_ufv 		= Database::get_main_table(TABLE_MAIN_USER_FIELD_VALUES);

	$users_params = $params['users'];
	$results = array();
	$orig_user_id_value = array();

	foreach($users_params as $user_param) {

		$firstName = $user_param['firstname'];
		$lastName = $user_param['lastname'];
		$status = $user_param['status'];
		$email = $user_param['email'];
		$loginName = $user_param['loginname'];
		$password = $user_param['password'];
		$official_code = '';
		$language = '';
		$phone = '';
		$picture_uri = '';
		$auth_source = PLATFORM_AUTH_SOURCE;
		$expiration_date = '0000-00-00 00:00:00';
		$active = 1;
		$hr_dept_id = 0;
		$extra = null;
		$original_user_id_name = $user_param['original_user_id_name'];
		$original_user_id_value = $user_param['original_user_id_value'];
		$orig_user_id_value[] = $user_param['original_user_id_value'];
		$extra_list = $user_param['extra'];
		if (!empty($user_param['language'])) { $language = $user_param['language'];}
		if (!empty($user_param['phone'])) { $phone = $user_param['phone'];}
		if (!empty($user_param['expiration_date'])) { $expiration_date = $user_param['expiration_date'];}

		// Check if exits x_user_id into user_field_values table.
		$sql = "SELECT field_value,user_id	FROM $t_uf uf,$t_ufv ufv WHERE ufv.field_id=uf.id AND field_variable='$original_user_id_name' AND field_value='$original_user_id_value'";
		$res = Database::query($sql, __FILE__, __LINE__);
		$row = Database::fetch_row($res);
		$count_row = Database::num_rows($res);
		if ($count_row > 0) {
			// Check if user is not active.
			$sql = "SELECT user_id FROM $table_user WHERE user_id ='".$row[1]."' AND active= '0'";
			$resu = Database::query($sql, __FILE__, __LINE__);
			$r_check_user = Database::fetch_row($resu);
			$count_user_id = Database::num_rows($resu);
			if ($count_user_id > 0) {
				$sql = "UPDATE $table_user SET
				lastname='".Database::escape_string($lastName)."',
				firstname='".Database::escape_string($firstName)."',
				username='".Database::escape_string($loginName)."',";
				if (!is_null($password)) {
					$password = $userPasswordCrypted ? md5($password) : $password;
					$sql .= " password='".Database::escape_string($password)."',";
				}
				if (!is_null($auth_source)) {
					$sql .=	" auth_source='".Database::escape_string($auth_source)."',";
				}
				$sql .=	"
						email='".Database::escape_string($email)."',
						status='".Database::escape_string($status)."',
						official_code='".Database::escape_string($official_code)."',
						phone='".Database::escape_string($phone)."',
						expiration_date='".Database::escape_string($expiration_date)."',
						active='1',
						hr_dept_id=".intval($hr_dept_id);
				$sql .=	" WHERE user_id='".$r_check_user[0]."'";
				Database::query($sql, __FILE__, __LINE__);
				$results[] = $r_check_user[0];
				continue;
				//return $r_check_user[0];
			} else {
				$results[] = 0;
				continue;
				//return 0;
				// user id already exits.
			}
		}

		// Default language.
		if (empty($language)) {
			$language = api_get_setting('platformLanguage');
		}

		if (!empty($_user['user_id'])) {
			$creator_id = $_user['user_id'];
		} else {
			$creator_id = '';
		}

		// First check wether the login already exists.
		if (!UserManager::is_username_available($loginName)) {
			if (api_set_failure('login-pass already taken')) {
				$results[] = 0;
				continue;
			}
		}

		$password = ($userPasswordCrypted ? md5($password) : $password);
		$sql = "INSERT INTO $table_user
					                SET lastname = '".Database::escape_string(trim($lastName))."',
					                firstname = '".Database::escape_string(trim($firstName))."',
					                username = '".Database::escape_string(trim($loginName))."',
					                status = '".Database::escape_string($status)."',
					                password = '".Database::escape_string($password)."',
					                email = '".Database::escape_string($email)."',
					                official_code	= '".Database::escape_string($official_code)."',
					                picture_uri 	= '".Database::escape_string($picture_uri)."',
					                creator_id  	= '".Database::escape_string($creator_id)."',
					                auth_source = '".Database::escape_string($auth_source)."',
				                    phone = '".Database::escape_string($phone)."',
				                    language = '".Database::escape_string($language)."',
				                    registration_date = now(),
				                    expiration_date = '".Database::escape_string($expiration_date)."',
									hr_dept_id = '".Database::escape_string($hr_dept_id)."',
									active = '".Database::escape_string($active)."'";
		$result = Database::query($sql, __FILE__, __LINE__);
		if ($result) {
			//echo "id returned";
			$return = Database::get_last_insert_id();
			require_once api_get_path(LIBRARY_PATH).'urlmanager.lib.php';
			if ($_configuration['multiple_access_urls'] == true) {
				if (api_get_current_access_url_id() != -1) {
					UrlManager::add_user_to_url($return, api_get_current_access_url_id());
				} else {
					UrlManager::add_user_to_url($return, 1);
				}
			} else {
				// We add by default the access_url_user table with access_url_id = 1
				UrlManager::add_user_to_url($return, 1);
			}

			// Save new fieldlabel into user_field table.
			$field_id = UserManager::create_extra_field($original_user_id_name, 1, $original_user_id_name, '');
			// Save the external system's id into user_field_value table.
			$res = UserManager::update_extra_field_value($return, $original_user_id_name, $original_user_id_value);

			if (is_array($extra_list) && count($extra_list) > 0) {
				foreach ($extra_list as $extra) {
					$extra_field_name = $extra['field_name'];
					$extra_field_value = $extra['field_value'];
					// Save new fieldlabel into user_field table.
					$field_id = UserManager::create_extra_field($extra_field_name, 1, $extra_field_name, '');
					// Save the external system's id into user_field_value table.
					$res = UserManager::update_extra_field_value($return, $extra_field_name, $extra_field_value);
				}
			}
		} else {
			$results[] = 0;
			continue;
		}

		$results[] =  $return;

	} // end principal foreach

	$count_results = count($results);
	$output = array();
	for ($i = 0; $i < $count_results; $i++) {
		$output[] = array('original_user_id_value' => $orig_user_id_value[$i], 'result' => $results[$i]);
	}

	return $output;
}

/* Register DokeosWSCreateUser function */
// Register the data structures used by the service


$server->wsdl->addComplexType(
	'createUser',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'firstname' => array('name' => 'firstname', 'type' => 'xsd:string'),
		'lastname' => array('name' => 'lastname', 'type' => 'xsd:string'),
		'status' => array('name' => 'status', 'type' => 'xsd:string'),
		'email' => array('name' => 'email', 'type' => 'xsd:string'),
		'loginname' => array('name' => 'loginname', 'type' => 'xsd:string'),
		'password' => array('name' => 'password', 'type' => 'xsd:string'),
		'language' => array('name' => 'language', 'type' => 'xsd:string'),
		'phone' => array('name' => 'phone', 'type' => 'xsd:string'),
		'expiration_date' => array('name' => 'expiration_date', 'type' => 'xsd:string'),
		'original_user_id_name' => array('name' => 'original_user_id_name', 'type' => 'xsd:string'),
		'original_user_id_value' => array('name' => 'original_user_id_value', 'type' => 'xsd:string'),
		'extra' => array('name' => 'extra', 'type' => 'tns:extrasList'),
		'secret_key' => array('name' => 'secret_key', 'type' => 'xsd:string')
	)
);


// Register the method to expose
$server->register('DokeosWSCreateUser',				// method name
	array('createUser' => 'tns:createUser'),		// input parameters
	array('return' => 'xsd:string'),	            // output parameters
	'urn:WSRegistration',							// namespace
	'urn:WSRegistration#DokeosWSCreateUser',		// soapaction
	'rpc',											// style
	'encoded',										// use
	'This service adds a user'						// documentation
);


// Define the method DokeosWSCreateUser
function DokeosWSCreateUser($params) {

	global $_user, $userPasswordCrypted, $_configuration;

	$secret_key = $params['secret_key'];
	$security_key = $_SERVER['REMOTE_ADDR'].$_configuration['security_key'];

	if (!api_is_valid_secret_key($secret_key, $security_key)) {
		return -1; // The secret key is incorrect.
	}

	// database table definition
	$table_user = Database::get_main_table(TABLE_MAIN_USER);
	$t_uf 		= Database::get_main_table(TABLE_MAIN_USER_FIELD);
	$t_ufv 		= Database::get_main_table(TABLE_MAIN_USER_FIELD_VALUES);

	$firstName = $params['firstname'];
	$lastName = $params['lastname'];
	$status = $params['status'];
	$email = $params['email'];
	$loginName = $params['loginname'];
	$password = $params['password'];
	$official_code = '';
	$language = '';
	$phone = '';
	$picture_uri = '';
	$auth_source = PLATFORM_AUTH_SOURCE;
	$expiration_date = '0000-00-00 00:00:00';
	$active = 1;
	$hr_dept_id = 0;
	$extra = null;
	$original_user_id_name = $params['original_user_id_name'];
	$original_user_id_value = $params['original_user_id_value'];
	$extra_list = $params['extra'];
	if (!empty($params['language'])) { $language = $params['language'];}
	if (!empty($params['phone'])) { $phone = $params['phone'];}
	if (!empty($params['expiration_date'])) { $expiration_date = $params['expiration_date'];}

	// check if exits x_user_id into user_field_values table
	$sql = "SELECT field_value,user_id	FROM $t_uf uf,$t_ufv ufv WHERE ufv.field_id=uf.id AND field_variable='$original_user_id_name' AND field_value='$original_user_id_value'";
	$res = Database::query($sql, __FILE__, __LINE__);
	$row = Database::fetch_row($res);
	$count_row = Database::num_rows($res);
	if ($count_row > 0) {
		// Check whether user is not active.
		$sql = "SELECT user_id FROM $table_user WHERE user_id ='".$row[1]."' AND active= '0'";
		$resu = Database::query($sql, __FILE__, __LINE__);
		$r_check_user = Database::fetch_row($resu);
		$count_user_id = Database::num_rows($resu);
		if ($count_user_id > 0) {
			$sql = "UPDATE $table_user SET
			lastname='".Database::escape_string($lastName)."',
			firstname='".Database::escape_string($firstName)."',
			username='".Database::escape_string($loginName)."',";
			if (!is_null($password)) {
				$password = $userPasswordCrypted ? md5($password) : $password;
				$sql .= " password='".Database::escape_string($password)."',";
			}
			if (!is_null($auth_source)) {
				$sql .=	" auth_source='".Database::escape_string($auth_source)."',";
			}
			$sql .=	"
					email='".Database::escape_string($email)."',
					status='".Database::escape_string($status)."',
					official_code='".Database::escape_string($official_code)."',
					phone='".Database::escape_string($phone)."',
					expiration_date='".Database::escape_string($expiration_date)."',
					active='1',
					hr_dept_id=".intval($hr_dept_id);
			$sql .=	" WHERE user_id='".$r_check_user[0]."'";
			Database::query($sql, __FILE__, __LINE__);

			return  $r_check_user[0];

		} else {
			return 0;
			//return 0;	// user id already exits
		}
	}

	// Default language
	if (empty($language)) {
		$language = api_get_setting('platformLanguage');
	}

	if (!empty($_user['user_id'])) {
		$creator_id = $_user['user_id'];
	} else {
		$creator_id = '';
	}

	// First check wether the login already exists
	if (!UserManager::is_username_available($loginName)) {
		if(api_set_failure('login-pass already taken')) {
			return 0;
		}
	}

	$password = ($userPasswordCrypted ? md5($password) : $password);
	$sql = "INSERT INTO $table_user
				                SET lastname = '".Database::escape_string(trim($lastName))."',
				                firstname = '".Database::escape_string(trim($firstName))."',
				                username = '".Database::escape_string(trim($loginName))."',
				                status = '".Database::escape_string($status)."',
				                password = '".Database::escape_string($password)."',
				                email = '".Database::escape_string($email)."',
				                official_code	= '".Database::escape_string($official_code)."',
				                picture_uri 	= '".Database::escape_string($picture_uri)."',
				                creator_id  	= '".Database::escape_string($creator_id)."',
				                auth_source = '".Database::escape_string($auth_source)."',
			                    phone = '".Database::escape_string($phone)."',
			                    language = '".Database::escape_string($language)."',
			                    registration_date = now(),
			                    expiration_date = '".Database::escape_string($expiration_date)."',
								hr_dept_id = '".Database::escape_string($hr_dept_id)."',
								active = '".Database::escape_string($active)."'";
	$result = Database::query($sql, __FILE__, __LINE__);
	if ($result) {
		//echo "id returned";
		$return = Database::get_last_insert_id();
		require_once api_get_path(LIBRARY_PATH).'urlmanager.lib.php';
		if ($_configuration['multiple_access_urls'] == true) {
			if (api_get_current_access_url_id() != -1) {
				UrlManager::add_user_to_url($return, api_get_current_access_url_id());
			} else {
				UrlManager::add_user_to_url($return, 1);
			}
		} else {
			// We add by default the access_url_user table with access_url_id = 1
			UrlManager::add_user_to_url($return, 1);
		}

		// Save new fieldlabel into user_field table.
		$field_id = UserManager::create_extra_field($original_user_id_name, 1, $original_user_id_name, '');
		// Save the external system's id into user_field_value table.
		$res = UserManager::update_extra_field_value($return, $original_user_id_name, $original_user_id_value);

		if (is_array($extra_list) && count($extra_list) > 0) {
			foreach ($extra_list as $extra) {
				$extra_field_name = $extra['field_name'];
				$extra_field_value = $extra['field_value'];
				// Save new fieldlabel into user_field table.
				$field_id = UserManager::create_extra_field($extra_field_name, 1, $extra_field_name, '');
				// Save the external system's id into user_field_value table.
				$res = UserManager::update_extra_field_value($return, $extra_field_name, $extra_field_value);
			}
		}
	} else {
		return 0;
	}

	return  $return;
}

/* Register DokeosWSCreateUsersPasswordCrypted function */
// Register the data structures used by the service

// Prepare input params.

// Input params for editing users
$server->wsdl->addComplexType(
	'createUsersPassEncryptParams',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'firstname' => array('name' => 'firstname', 'type' => 'xsd:string'),
		'lastname' => array('name' => 'lastname', 'type' => 'xsd:string'),
		'status' => array('name' => 'status', 'type' => 'xsd:string'),
		'email' => array('name' => 'email', 'type' => 'xsd:string'),
		'loginname' => array('name' => 'loginname', 'type' => 'xsd:string'),
		'password' => array('name' => 'password', 'type' => 'xsd:string'),
		'encrypt_method' => array('name' => 'encrypt_method', 'type' => 'xsd:string'),
		'language' => array('name' => 'language', 'type' => 'xsd:string'),
		'phone' => array('name' => 'phone', 'type' => 'xsd:string'),
		'expiration_date' => array('name' => 'expiration_date', 'type' => 'xsd:string'),
		'original_user_id_name' => array('name' => 'original_user_id_name', 'type' => 'xsd:string'),
		'original_user_id_value' => array('name' => 'original_user_id_value', 'type' => 'xsd:string'),
		'extra' => array('name' => 'extra', 'type' => 'tns:extrasList')
	)
);


$server->wsdl->addComplexType(
'createUsersPassEncryptParamsList',
'complexType',
'array',
'',
'SOAP-ENC:Array',
array(),
array(array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:createUsersPassEncryptParams[]')),
'tns:createUsersPassEncryptParams'
);


// Register the data structures used by the service
$server->wsdl->addComplexType(
	'createUsersPasswordCrypted',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'users' => array('name' => 'users', 'type' => 'tns:createUsersPassEncryptParamsList'),
		'secret_key' => array('name' => 'secret_key', 'type' => 'xsd:string')
	)
);

// Prepare output params, in this case will return an array
$server->wsdl->addComplexType(
'result_createUsersPassEncrypt',
'complexType',
'struct',
'all',
'',
array(
		'original_user_id_value' => array('name' => 'original_user_id_value', 'type' => 'xsd:string'),
		'result' => array('name' => 'result', 'type' => 'xsd:string')
     )
);

$server->wsdl->addComplexType(
'results_createUsersPassEncrypt',
'complexType',
'array',
'',
'SOAP-ENC:Array',
array(),
array(array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:result_createUsersPassEncrypt[]')),
'tns:result_createUsersPassEncrypt'
);

// Register the method to expose
$server->register('DokeosWSCreateUsersPasswordCrypted',						    // method name
	array('createUsersPasswordCrypted' => 'tns:createUsersPasswordCrypted'),	// input parameters
	array('return' => 'tns:results_createUsersPassEncrypt'),					// output parameters
	'urn:WSRegistration',													    // namespace
	'urn:WSRegistration#DokeosWSCreateUsersPasswordCrypted',					// soapaction
	'rpc',																	    // style
	'encoded',																    // use
	'This service adds users to dokeos'									        // documentation
);

// Define the method DokeosWSCreateUsersPasswordCrypted
function DokeosWSCreateUsersPasswordCrypted($params) {

	global $_user, $userPasswordCrypted, $_configuration;

	$secret_key = $params['secret_key'];
	$security_key = $_SERVER['REMOTE_ADDR'].$_configuration['security_key'];

	if (!api_is_valid_secret_key($secret_key, $security_key)) {
		return -1; // The secret key is incorrect.
	}

	// database table definition
	$table_user = Database::get_main_table(TABLE_MAIN_USER);
	$t_uf = Database::get_main_table(TABLE_MAIN_USER_FIELD);
	$t_ufv = Database::get_main_table(TABLE_MAIN_USER_FIELD_VALUES);

	$users_params = $params['users'];
	$results = array();
	$orig_user_id_value = array();

	foreach ($users_params as $user_param) {

		$password = $user_param['password'];
	  	$encrypt_method = $user_param['encrypt_method'];

	  	$firstName = $user_param['firstname'];
	  	$lastName = $user_param['lastname'];
		$status = $user_param['status'];
		$email = $user_param['email'];
		$loginName = $user_param['loginname'];

		$official_code = '';
		$language = '';
		$phone = '';
		$picture_uri = '';
		$auth_source = PLATFORM_AUTH_SOURCE;
		$expiration_date = '0000-00-00 00:00:00';
		$active = 1;
		$hr_dept_id = 0;
		$extra = null;
		$original_user_id_name = $user_param['original_user_id_name'];
		$original_user_id_value = $user_param['original_user_id_value'];
		$orig_user_id_value[] = $user_param['original_user_id_value'];
		$extra_list = $user_param['extra'];
		$salt = '';

		if ($userPasswordCrypted === $encrypt_method ) {
			if ($encrypt_method == 'md5' && !preg_match('/^[A-Fa-f0-9]{32}$/', $password)) {
				$msg = "Encryption $encrypt_method is invalid";
				$results[] = $msg;
				continue;
			} else if ($encrypt_method == 'sha1' && !preg_match('/^[A-Fa-f0-9]{40}$/', $password)) {
				$msg = "Encryption $encrypt_method is invalid";
				$results[] = $msg;
				continue;
			}
		} else {
			$msg = "This encryption $encrypt_method is not configured into dokeos ";
			$results[] = $msg;
			continue;
		}

		if (is_array($extra_list) && count($extra_list) > 0) {
			foreach ($extra_list as $extra) {
				if($extra['field_name'] == 'salt') {
					$salt = $extra['field_value'];
					break;
				}
			}
		}

		if (!empty($user_param['language'])) { $language = $user_param['language']; }
		if (!empty($user_param['phone'])) { $phone = $user_param['phone']; }
		if (!empty($user_param['expiration_date'])) { $expiration_date = $user_param['expiration_date']; }

		// Check whether x_user_id exists into user_field_values table.
		$sql = "SELECT field_value,user_id	FROM $t_uf uf,$t_ufv ufv WHERE ufv.field_id=uf.id AND field_variable='$original_user_id_name' AND field_value='$original_user_id_value'";
		$res = Database::query($sql, __FILE__, __LINE__);
		$row = Database::fetch_row($res);
		$count_row = Database::num_rows($res);
		if ($count_row > 0) {
			// Check if user is not active.
			$sql = "SELECT user_id FROM $table_user WHERE user_id ='".$row[1]."' AND active= '0'";
			$resu = Database::query($sql, __FILE__, __LINE__);
			$r_check_user = Database::fetch_row($resu);
			$count_check_user = Database::num_rows($resu);
			if ($count_check_user > 0) {
				$sql = "UPDATE $table_user SET
				lastname='".Database::escape_string($lastName)."',
				firstname='".Database::escape_string($firstName)."',
				username='".Database::escape_string($loginName)."',";

				if (!is_null($auth_source)) {
					$sql .=	" auth_source='".Database::escape_string($auth_source)."',";
				}
				$sql .=	"
						password='".Database::escape_string($password)."',
						email='".Database::escape_string($email)."',
						status='".Database::escape_string($status)."',
						official_code='".Database::escape_string($official_code)."',
						phone='".Database::escape_string($phone)."',
						expiration_date='".Database::escape_string($expiration_date)."',
						active='1',
						hr_dept_id=".intval($hr_dept_id);
				$sql .=	" WHERE user_id='".$r_check_user[0]."'";
				Database::query($sql, __FILE__, __LINE__);

				if (is_array($extra_list) && count($extra_list) > 0) {
					foreach ($extra_list as $extra) {
						$extra_field_name = $extra['field_name'];
						$extra_field_value = $extra['field_value'];
						// Save the external system's id into user_field_value table.
						$res = UserManager::update_extra_field_value($r_check_user[0], $extra_field_name, $extra_field_value);
					}
				}

				$results[] = $r_check_user[0];
				continue;
			} else {
				$results[] = 0;
				continue; // User id already exits.
			}
		}

		// Default language.
		if (empty($language)) {
			$language = api_get_setting('platformLanguage');
		}

		if (!empty($_user['user_id'])) {
			$creator_id = $_user['user_id'];
		} else {
			$creator_id = '';
		}
		// First check wether the login already exists
		if (!UserManager::is_username_available($loginName)) {
			if(api_set_failure('login-pass already taken')) {
				$results[] = 0;
				continue;
			}
		}

		$sql = "INSERT INTO $table_user
					                SET lastname = '".Database::escape_string(trim($lastName))."',
					                firstname = '".Database::escape_string(trim($firstName))."',
					                username = '".Database::escape_string(trim($loginName))."',
					                status = '".Database::escape_string($status)."',
					                password = '".Database::escape_string($password)."',
					                email = '".Database::escape_string($email)."',
					                official_code	= '".Database::escape_string($official_code)."',
					                picture_uri 	= '".Database::escape_string($picture_uri)."',
					                creator_id  	= '".Database::escape_string($creator_id)."',
					                auth_source = '".Database::escape_string($auth_source)."',
				                    phone = '".Database::escape_string($phone)."',
				                    language = '".Database::escape_string($language)."',
				                    registration_date = now(),
				                    expiration_date = '".Database::escape_string($expiration_date)."',
									hr_dept_id = '".Database::escape_string($hr_dept_id)."',
									active = '".Database::escape_string($active)."'";
		$result = Database::query($sql, __FILE__, __LINE__);
		if ($result) {
			//echo "id returned";
			$return = Database::get_last_insert_id();
			require_once api_get_path(LIBRARY_PATH).'urlmanager.lib.php';
			if ($_configuration['multiple_access_urls'] == true) {
				if (api_get_current_access_url_id() != -1) {
					UrlManager::add_user_to_url($return, api_get_current_access_url_id());
				} else {
					UrlManager::add_user_to_url($return, 1);
				}
			} else {
				// We add by default the access_url_user table with access_url_id = 1
				UrlManager::add_user_to_url($return, 1);
			}
			// Save new fieldlabel into user_field table.
			$field_id = UserManager::create_extra_field($original_user_id_name, 1, $original_user_id_name, '');
			// Save the remote system's id into user_field_value table.
			$res = UserManager::update_extra_field_value($return, $original_user_id_name, $original_user_id_value);

			if (is_array($extra_list) && count($extra_list) > 0) {
				foreach ($extra_list as $extra) {
					$extra_field_name = $extra['field_name'];
					$extra_field_value = $extra['field_value'];
					// Save new fieldlabel into user_field table.
					$field_id = UserManager::create_extra_field($extra_field_name, 1, $extra_field_name, '');
					// Save the external system's id into user_field_value table.
					$res = UserManager::update_extra_field_value($return, $extra_field_name, $extra_field_value);
				}
			}
		} else {
			$results[] = 0;
			continue;
		}
		$results[] = $return;

	} // end principal foreach

  	$count_results = count($results);
	$output = array();
	for($i = 0; $i < $count_results; $i++) {
		$output[] = array('original_user_id_value' => $orig_user_id_value[$i], 'result' => $results[$i]);
	}

	return $output;
}

/* Register DokeosWSCreateUserPasswordCrypted function */
// Register the data structures used by the service

//prepare input params

// Input params for editing users
$server->wsdl->addComplexType(
	'createUserPasswordCrypted',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'firstname' => array('name' => 'firstname', 'type' => 'xsd:string'),
		'lastname' => array('name' => 'lastname', 'type' => 'xsd:string'),
		'status' => array('name' => 'status', 'type' => 'xsd:string'),
		'email' => array('name' => 'email', 'type' => 'xsd:string'),
		'loginname' => array('name' => 'loginname', 'type' => 'xsd:string'),
		'password' => array('name' => 'password', 'type' => 'xsd:string'),
		'encrypt_method' => array('name' => 'encrypt_method', 'type' => 'xsd:string'),
		'language' => array('name' => 'language', 'type' => 'xsd:string'),
		'phone' => array('name' => 'phone', 'type' => 'xsd:string'),
		'expiration_date' => array('name' => 'expiration_date', 'type' => 'xsd:string'),
		'original_user_id_name' => array('name' => 'original_user_id_name', 'type' => 'xsd:string'),
		'original_user_id_value' => array('name' => 'original_user_id_value', 'type' => 'xsd:string'),
		'extra' => array('name' => 'extra', 'type' => 'tns:extrasList'),
		'secret_key' => array('name' => 'secret_key', 'type' => 'xsd:string')
	)
);

// Register the method to expose
$server->register('DokeosWSCreateUserPasswordCrypted',						// method name
	array('createUserPasswordCrypted' => 'tns:createUserPasswordCrypted'),	// input parameters
	array('return' => 'xsd:string'),								        // output parameters
	'urn:WSRegistration',													// namespace
	'urn:WSRegistration#DokeosWSCreateUserPasswordCrypted',					// soapaction
	'rpc',																	// style
	'encoded',																// use
	'This service adds users to dokeos'									    // documentation
);

// Define the method DokeosWSCreateUserPasswordCrypted
function DokeosWSCreateUserPasswordCrypted($params) {

	global $_user, $userPasswordCrypted, $_configuration;

	$secret_key = $params['secret_key'];
	$security_key = $_SERVER['REMOTE_ADDR'].$_configuration['security_key'];

	if (!api_is_valid_secret_key($secret_key, $security_key)) {
		return -1; // Secret key is incorrect.
	}

	// Database table definition.
	$table_user = Database::get_main_table(TABLE_MAIN_USER);
	$t_uf = Database::get_main_table(TABLE_MAIN_USER_FIELD);
	$t_ufv = Database::get_main_table(TABLE_MAIN_USER_FIELD_VALUES);

	//$users_params = $params['users'];
	$results = array();
	$orig_user_id_value = array();

	$password = $params['password'];
  	$encrypt_method = $params['encrypt_method'];

  	$firstName = $params['firstname'];
  	$lastName = $params['lastname'];
	$status = $params['status'];
	$email = $params['email'];
	$loginName = $params['loginname'];

	$official_code = '';
	$language='';
	$phone = '';
	$picture_uri = '';
	$auth_source = PLATFORM_AUTH_SOURCE;
	$expiration_date = '0000-00-00 00:00:00'; $active = 1; $hr_dept_id = 0; $extra = null;
	$original_user_id_name= $params['original_user_id_name'];
	$original_user_id_value = $params['original_user_id_value'];
	$orig_user_id_value[] = $params['original_user_id_value'];
	$extra_list = $params['extra'];
	$salt = '';

	if ($userPasswordCrypted === $encrypt_method ) {
		if ($encrypt_method == 'md5' && !preg_match('/^[A-Fa-f0-9]{32}$/', $password)) {
			$msg = "Encryption $encrypt_method is invalid";
			return $msg;

		} else if ($encrypt_method == 'sha1' && !preg_match('/^[A-Fa-f0-9]{40}$/', $password)) {
			$msg = "Encryption $encrypt_method is invalid";
			return $msg;
		}
	} else {
		$msg = "This encryption $encrypt_method is not configured into dokeos ";
		return $msg;
	}

	if (!empty($params['language'])) { $language = $params['language'];}
	if (!empty($params['phone'])) { $phone = $params['phone'];}
	if (!empty($params['expiration_date'])) { $expiration_date = $params['expiration_date'];}

	// Check whether x_user_id exists into user_field_values table.
	$sql = "SELECT field_value,user_id	FROM $t_uf uf,$t_ufv ufv WHERE ufv.field_id=uf.id AND field_variable='$original_user_id_name' AND field_value='$original_user_id_value'";
	$res = Database::query($sql, __FILE__, __LINE__);
	$row = Database::fetch_row($res);
	$count_row = Database::num_rows($res);
	if ($count_row > 0) {
		// Check whether user is not active.
		$sql = "SELECT user_id FROM $table_user WHERE user_id ='".$row[1]."' AND active= '0'";
		$resu = Database::query($sql, __FILE__, __LINE__);
		$r_check_user = Database::fetch_row($resu);
		$count_check_user = Database::num_rows($resu);
		if ($count_check_user > 0) {
			$sql = "UPDATE $table_user SET
			lastname='".Database::escape_string($lastName)."',
			firstname='".Database::escape_string($firstName)."',
			username='".Database::escape_string($loginName)."',";

			if (!is_null($auth_source)) {
				$sql .=	" auth_source='".Database::escape_string($auth_source)."',";
			}
			$sql .=	"
					password='".Database::escape_string($password)."',
					email='".Database::escape_string($email)."',
					status='".Database::escape_string($status)."',
					official_code='".Database::escape_string($official_code)."',
					phone='".Database::escape_string($phone)."',
					expiration_date='".Database::escape_string($expiration_date)."',
					active='1',
					hr_dept_id=".intval($hr_dept_id);
			$sql .=	" WHERE user_id='".$r_check_user[0]."'";
			Database::query($sql, __FILE__, __LINE__);

			if (is_array($extra_list) && count($extra_list) > 0) {
				foreach ($extra_list as $extra) {
					$extra_field_name = $extra['field_name'];
					$extra_field_value = $extra['field_value'];
					// Save the external system's id into user_field_value table.
					$res = UserManager::update_extra_field_value($r_check_user[0], $extra_field_name, $extra_field_value);
				}
			}

			return $r_check_user[0];
		} else {
			return 0;
		}
	}

	// Default language.
	if (empty($language)) {
		$language = api_get_setting('platformLanguage');
	}

	if (!empty($_user['user_id'])) {
		$creator_id = $_user['user_id'];
	} else {
		$creator_id = '';
	}
	// First check wether the login already exists
	if (! UserManager::is_username_available($loginName)) {
		if(api_set_failure('login-pass already taken')) {
			return 0;
		}
	}

	$sql = "INSERT INTO $table_user
				                SET lastname = '".Database::escape_string(trim($lastName))."',
				                firstname = '".Database::escape_string(trim($firstName))."',
				                username = '".Database::escape_string(trim($loginName))."',
				                status = '".Database::escape_string($status)."',
				                password = '".Database::escape_string($password)."',
				                email = '".Database::escape_string($email)."',
				                official_code	= '".Database::escape_string($official_code)."',
				                picture_uri 	= '".Database::escape_string($picture_uri)."',
				                creator_id  	= '".Database::escape_string($creator_id)."',
				                auth_source = '".Database::escape_string($auth_source)."',
			                    phone = '".Database::escape_string($phone)."',
			                    language = '".Database::escape_string($language)."',
			                    registration_date = now(),
			                    expiration_date = '".Database::escape_string($expiration_date)."',
								hr_dept_id = '".Database::escape_string($hr_dept_id)."',
								active = '".Database::escape_string($active)."'";
	$result = Database::query($sql, __FILE__, __LINE__);
	if ($result) {
		//echo "id returned";
		$return = Database::get_last_insert_id();
		require_once api_get_path(LIBRARY_PATH).'urlmanager.lib.php';
		if ($_configuration['multiple_access_urls'] == true) {
			if (api_get_current_access_url_id() != -1) {
				UrlManager::add_user_to_url($return, api_get_current_access_url_id());
			} else {
				UrlManager::add_user_to_url($return, 1);
			}
		} else {
			// We add by default the access_url_user table with access_url_id = 1
			UrlManager::add_user_to_url($return, 1);
		}
		// Save new fieldlabel into user_field table.
		$field_id = UserManager::create_extra_field($original_user_id_name, 1, $original_user_id_name, '');
		// Save the remote system's id into user_field_value table.
		$res = UserManager::update_extra_field_value($return, $original_user_id_name, $original_user_id_value);

		if (is_array($extra_list) && count($extra_list) > 0) {
			foreach ($extra_list as $extra) {
				$extra_field_name = $extra['field_name'];
				$extra_field_value = $extra['field_value'];
				// save new fieldlabel into user_field table
				$field_id = UserManager::create_extra_field($extra_field_name, 1, $extra_field_name, '');
				// save the external system's id into user_field_value table'
				$res = UserManager::update_extra_field_value($return, $extra_field_name, $extra_field_value);
			}
		}
	} else {
		return 0;
	}
	return $return;
}

/* Register DokeosWSEditUsers function */
// Register the data structures used by the service
$server->wsdl->addComplexType(
	'editUsersParams',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'original_user_id_value' => array('name' => 'original_user_id_value', 'type' => 'xsd:string'),
		'original_user_id_name' => array('name' => 'original_user_id_name', 'type' => 'xsd:string'),
		'firstname' => array('name' => 'firstname', 'type' => 'xsd:string'),
		'lastname' => array('name' => 'lastname', 'type' => 'xsd:string'),
		'username' => array('name' => 'username', 'type' => 'xsd:string'),
		'password' => array('name' => 'password', 'type' => 'xsd:string'),
		'email' => array('name' => 'email', 'type' => 'xsd:string'),
		'status' => array('name' => 'status', 'type' => 'xsd:string'),
		'phone' => array('name' => 'phone', 'type' => 'xsd:string'),
		'expiration_date' => array('name' => 'expiration_date', 'type' => 'xsd:string'),
		'extra' => array('name' => 'extra', 'type' => 'tns:extrasList')
	)
);

$server->wsdl->addComplexType(
'editUsersParamsList',
'complexType',
'array',
'',
'SOAP-ENC:Array',
array(),
array(array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:editUsersParams[]')),
'tns:editUsersParams'
);

$server->wsdl->addComplexType(
	'editUsers',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'users' => array('name' => 'users', 'type' => 'tns:editUsersParamsList'),
		'secret_key' => array('name' => 'secret_key', 'type' => 'xsd:string')
	)
);

// Prepare output params, in this case will return an array
$server->wsdl->addComplexType(
'result_editUsers',
'complexType',
'struct',
'all',
'',
array(
		'original_user_id_value' => array('name' => 'original_user_id_value', 'type' => 'xsd:string'),
		'result' => array('name' => 'result', 'type' => 'xsd:string')
     )
);

$server->wsdl->addComplexType(
'results_editUsers',
'complexType',
'array',
'',
'SOAP-ENC:Array',
array(),
array(array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:result_editUsers[]')),
'tns:result_editUsers'
);

// Register the method to expose
$server->register('DokeosWSEditUsers',				// method name
	array('editUsers' => 'tns:editUsers'),			// input parameters
	array('return' => 'tns:results_editUsers'),		// output parameters
	'urn:WSRegistration',							// namespace
	'urn:WSRegistration#DokeosWSEditUsers',			// soapaction
	'rpc',											// style
	'encoded',										// use
	'This service edits a user from wiener'			// documentation
);

// Define the method DokeosWSEditUsers
function DokeosWSEditUsers($params) {
	global $userPasswordCrypted,$_configuration;

	$secret_key = $params['secret_key'];
	$security_key = $_SERVER['REMOTE_ADDR'].$_configuration['security_key'];

	if (!api_is_valid_secret_key($secret_key, $security_key)) {
		return -1; // The secret key is incorrect.
	}

	$table_user = Database :: get_main_table(TABLE_MAIN_USER);
	$t_uf = Database::get_main_table(TABLE_MAIN_USER_FIELD);
	$t_ufv = Database::get_main_table(TABLE_MAIN_USER_FIELD_VALUES);

	$users_params = $params['users'];
	$results = array();
	$orig_user_id_value = array();

	foreach($users_params as $user_param) {

		$original_user_id_value = $user_param['original_user_id_value'];
		$original_user_id_name = $user_param['original_user_id_name'];
		$orig_user_id_value[] = $original_user_id_value;
		$firstname = $user_param['firstname'];
		$lastname = $user_param['lastname'];
		$username = $user_param['username'];
		$password = null;
		$auth_source = null;
		$email = $user_param['email'];
		$status = $user_param['status'];
		$official_code = '';
		$phone = $user_param['phone'];
		$picture_uri = '';
		$expiration_date = $user_param['expiration_date'];
		$active = 1;
		$creator_id = null;
		$hr_dept_id = 0;
		$extra = null;
		$extra_list = $user_param['extra'];

		if (!empty($user_param['password'])) { $password = $user_param['password']; }

		// Get user id from id wiener

		$sql = "SELECT user_id FROM $t_uf uf,$t_ufv ufv WHERE ufv.field_id=uf.id AND field_variable='$original_user_id_name' AND field_value='$original_user_id_value'";
		$res = Database::query($sql, __FILE__, __LINE__);
		$row = Database::fetch_row($res);
		$user_id = $row[0];

		if (empty($user_id)) {
			$results[] = 0; // Original_user_id_value doesn't exist.
			continue;
		} else {
			$sql = "SELECT user_id FROM $table_user WHERE user_id ='$user_id' AND active= '0'";
			$resu = Database::query($sql, __FILE__, __LINE__);
			$r_check_user = Database::fetch_row($resu);
			if (!empty($r_check_user[0])) {
				$results[] = 0; // user_id is not active.
				continue;
			}
		}

		// Check whether username already exits.
		$sql = "SELECT username FROM $table_user WHERE username = '$username' AND user_id <> '$user_id'";
		$res_un = Database::query($sql, __FILE__, __LINE__);
		$r_username = Database::fetch_row($res_un);

		if (!empty($r_username[0])) {
			$results[] = 0; // username already exits.
			continue;
		}

		$sql = "UPDATE $table_user SET
				lastname='".Database::escape_string($lastname)."',
				firstname='".Database::escape_string($firstname)."',
				username='".Database::escape_string($username)."',";
		if (!is_null($password)) {
			$password = $userPasswordCrypted ? md5($password) : $password;
			$sql .= " password='".Database::escape_string($password)."',";
		}
		if (!is_null($auth_source)) {
			$sql .=	" auth_source='".Database::escape_string($auth_source)."',";
		}
		$sql .=	"
				email='".Database::escape_string($email)."',
				status='".Database::escape_string($status)."',
				official_code='".Database::escape_string($official_code)."',
				phone='".Database::escape_string($phone)."',
				picture_uri='".Database::escape_string($picture_uri)."',
				expiration_date='".Database::escape_string($expiration_date)."',
				active='".Database::escape_string($active)."',
				hr_dept_id=".intval($hr_dept_id);

		if (!is_null($creator_id)) {
			$sql .= ", creator_id='".Database::escape_string($creator_id)."'";
		}
		$sql .=	" WHERE user_id='$user_id'";
		$return = @Database::query($sql, __FILE__, __LINE__);

		if (is_array($extra_list) && count($extra_list) > 0) {
			foreach ($extra_list as $extra) {
				$extra_field_name = $extra['field_name'];
				$extra_field_value = $extra['field_value'];
				// Save the external system's id into user_field_value table.
				$res = UserManager::update_extra_field_value($user_id, $extra_field_name, $extra_field_value);
			}
		}

		$results[] = $return;
		continue;
	}

	$count_results = count($results);
	$output = array();
	for($i = 0; $i < $count_results; $i++) {
		$output[] = array('original_user_id_value' => $orig_user_id_value[$i], 'result' => $results[$i]);
	}

	return $output;
}

/* Register DokeosWSEditUser function */
// Register the data structures used by the service
$server->wsdl->addComplexType(
	'editUser',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'original_user_id_value' => array('name' => 'original_user_id_value', 'type' => 'xsd:string'),
		'original_user_id_name' => array('name' => 'original_user_id_name', 'type' => 'xsd:string'),
		'firstname' => array('name' => 'firstname', 'type' => 'xsd:string'),
		'lastname' => array('name' => 'lastname', 'type' => 'xsd:string'),
		'username' => array('name' => 'username', 'type' => 'xsd:string'),
		'password' => array('name' => 'password', 'type' => 'xsd:string'),
		'email' => array('name' => 'email', 'type' => 'xsd:string'),
		'status' => array('name' => 'status', 'type' => 'xsd:string'),
		'phone' => array('name' => 'phone', 'type' => 'xsd:string'),
		'expiration_date' => array('name' => 'expiration_date', 'type' => 'xsd:string'),
		'extra' => array('name' => 'extra', 'type' => 'tns:extrasList'),
		'secret_key' => array('name' => 'secret_key', 'type' => 'xsd:string')
	)
);

// Register the method to expose
$server->register('DokeosWSEditUser',		        // method name
	array('editUser' => 'tns:editUser'),			// input parameters
	array('return' => 'xsd:string'),                // output parameters
	'urn:WSRegistration',							// namespace
	'urn:WSRegistration#DokeosWSEditUser',          // soapaction
	'rpc',											// style
	'encoded',										// use
	'This service edits a user from wiener'			// documentation
);

// Define the method DokeosWSEditUser
function DokeosWSEditUser($params) {
	global $userPasswordCrypted, $_configuration;

	$secret_key = $params['secret_key'];
	$security_key = $_SERVER['REMOTE_ADDR'].$_configuration['security_key'];

	if (!api_is_valid_secret_key($secret_key, $security_key)) {
		return -1; // The secret key is incorrect.
	}

	$table_user = Database :: get_main_table(TABLE_MAIN_USER);
	$t_uf = Database::get_main_table(TABLE_MAIN_USER_FIELD);
	$t_ufv = Database::get_main_table(TABLE_MAIN_USER_FIELD_VALUES);

	$original_user_id_value = $params['original_user_id_value'];
	$original_user_id_name = $params['original_user_id_name'];
	$firstname = $params['firstname'];
	$lastname = $params['lastname'];
	$username = $params['username'];
	$password = null;
	$auth_source = null;
	$email = $params['email'];
	$status = $params['status'];
	$official_code = '';
	$phone = $params['phone'];
	$picture_uri = '';
	$expiration_date = $params['expiration_date'];
	$active = 1;
	$creator_id = null;
	$hr_dept_id = 0;
	$extra = null;
	$extra_list = $params['extra'];

	if (!empty($params['password'])) { $password = $params['password']; }

	// Get user id from id wiener

	$sql = "SELECT user_id FROM $t_uf uf,$t_ufv ufv WHERE ufv.field_id=uf.id AND field_variable='$original_user_id_name' AND field_value='$original_user_id_value'";
	$res = Database::query($sql, __FILE__, __LINE__);
	$row = Database::fetch_row($res);
	$user_id = $row[0];

	if (empty($user_id)) {
		return 0;
	} else {
		$sql = "SELECT user_id FROM $table_user WHERE user_id ='$user_id' AND active= '0'";
		$resu = Database::query($sql, __FILE__, __LINE__);
		$r_check_user = Database::fetch_row($resu);
		if (!empty($r_check_user[0])) {
			return 0;
		}
	}

	// Check whether username already exits.
	$sql = "SELECT username FROM $table_user WHERE username = '$username' AND user_id <> '$user_id'";
	$res_un = Database::query($sql, __FILE__, __LINE__);
	$r_username = Database::fetch_row($res_un);

	if (!empty($r_username[0])) {
		return 0;
	}

	$sql = "UPDATE $table_user SET
			lastname='".Database::escape_string($lastname)."',
			firstname='".Database::escape_string($firstname)."',
			username='".Database::escape_string($username)."',";
	if (!is_null($password)) {
		$password = $userPasswordCrypted ? md5($password) : $password;
		$sql .= " password='".Database::escape_string($password)."',";
	}
	if (!is_null($auth_source)) {
		$sql .=	" auth_source='".Database::escape_string($auth_source)."',";
	}
	$sql .=	"
			email='".Database::escape_string($email)."',
			status='".Database::escape_string($status)."',
			official_code='".Database::escape_string($official_code)."',
			phone='".Database::escape_string($phone)."',
			picture_uri='".Database::escape_string($picture_uri)."',
			expiration_date='".Database::escape_string($expiration_date)."',
			active='".Database::escape_string($active)."',
			hr_dept_id=".intval($hr_dept_id);

	if (!is_null($creator_id)) {
		$sql .= ", creator_id='".Database::escape_string($creator_id)."'";
	}
	$sql .=	" WHERE user_id='$user_id'";
	$return = @Database::query($sql, __FILE__, __LINE__);

	if (is_array($extra_list) && count($extra_list) > 0) {
		foreach ($extra_list as $extra) {
			$extra_field_name = $extra['field_name'];
			$extra_field_value = $extra['field_value'];
			// Save the external system's id into user_field_value table.
			$res = UserManager::update_extra_field_value($user_id, $extra_field_name, $extra_field_value);
		}
	}

	return  $return;
}

/* Register DokeosWSEditUsersPasswordCrypted function */
// Register the data structures used by the service
$server->wsdl->addComplexType(
	'editUsersPasswordCryptedParams',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'original_user_id_value' => array('name' => 'original_user_id_value', 'type' => 'xsd:string'),
		'original_user_id_name' => array('name' => 'original_user_id_name', 'type' => 'xsd:string'),
		'firstname' => array('name' => 'firstname', 'type' => 'xsd:string'),
		'lastname' => array('name' => 'lastname', 'type' => 'xsd:string'),
		'username' => array('name' => 'username', 'type' => 'xsd:string'),
		'password' => array('name' => 'password', 'type' => 'xsd:string'),
		'encrypt_method' => array('name' => 'encrypt_method', 'type' => 'xsd:string'),
		'email' => array('name' => 'email', 'type' => 'xsd:string'),
		'status' => array('name' => 'status', 'type' => 'xsd:string'),
		'phone' => array('name' => 'phone', 'type' => 'xsd:string'),
		'expiration_date' => array('name' => 'expiration_date', 'type' => 'xsd:string'),
		'extra' => array('name' => 'extra', 'type' => 'tns:extrasList')
	)
);

$server->wsdl->addComplexType(
'editUsersPasswordCryptedParamsList',
'complexType',
'array',
'',
'SOAP-ENC:Array',
array(),
array(array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:editUsersPasswordCryptedParams[]')),
'tns:editUsersPasswordCryptedParams'
);

$server->wsdl->addComplexType(
	'editUsersPasswordCrypted',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'users' => array('name' => 'users', 'type' => 'tns:editUsersPasswordCryptedParamsList'),
		'secret_key' => array('name' => 'secret_key', 'type' => 'xsd:string')
	)
);

// Prepare output params, in this case will return an array
$server->wsdl->addComplexType(
'result_editUsersPasswordCrypted',
'complexType',
'struct',
'all',
'',
array(
		'original_user_id_value' => array('name' => 'original_user_id_value', 'type' => 'xsd:string'),
		'result' => array('name' => 'result', 'type' => 'xsd:string')
     )
);

$server->wsdl->addComplexType(
'results_editUsersPasswordCrypted',
'complexType',
'array',
'',
'SOAP-ENC:Array',
array(),
array(array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:result_editUsersPasswordCrypted[]')),
'tns:result_editUsersPasswordCrypted'
);

// Register the method to expose
$server->register('DokeosWSEditUsersPasswordCrypted',					// method name
	array('editUsersPasswordCrypted' => 'tns:editUsersPasswordCrypted'),	// input parameters
	array('return' => 'tns:results_editUsersPasswordCrypted'),			// output parameters
	'urn:WSRegistration',												// namespace
	'urn:WSRegistration#DokeosWSEditUsersPasswordCrypted',				// soapaction
	'rpc',																// style
	'encoded',															// use
	'This service edits a user'											// documentation
);

// Define the method DokeosWSEditUsersPasswordCrypted
function DokeosWSEditUsersPasswordCrypted($params) {
	global $userPasswordCrypted, $_configuration, $userPasswordCrypted;

	$secret_key = $params['secret_key'];
	$security_key = $_SERVER['REMOTE_ADDR'].$_configuration['security_key'];

	if (!api_is_valid_secret_key($secret_key, $security_key)) {
		return -1; //secret key is incorrect
	}

	// get user id from id of remote system
	$table_user = Database :: get_main_table(TABLE_MAIN_USER);
	$t_uf = Database::get_main_table(TABLE_MAIN_USER_FIELD);
	$t_ufv = Database::get_main_table(TABLE_MAIN_USER_FIELD_VALUES);


	$users_params = $params['users'];
	$results = array();
	$orig_user_id_value = array();

	foreach ($users_params as $user_param) {

		$original_user_id_value = $user_param['original_user_id_value'];
		$original_user_id_name = $user_param['original_user_id_name'];
		$orig_user_id_value[] = $original_user_id_value;
		$firstname = $user_param['firstname'];
		$lastname = $user_param['lastname'];
		$username = $user_param['username'];
		$password = null;
		$auth_source = null;
		$email = $user_param['email'];
		$status = $user_param['status'];
		$official_code = '';
		$phone = $user_param['phone'];
		$picture_uri = '';
		$expiration_date = $user_param['expiration_date'];
		$active = 1;
		$creator_id = null;
		$hr_dept_id = 0;
		$extra = null;
		$extra_list = $user_param['extra'];

		if (!empty($user_param['password']) && !empty($user_param['encrypt_method'])) {

			$password = $user_param['password'];
			$encrypt_method = $user_param['encrypt_method'];
			if ($userPasswordCrypted === $encrypt_method ) {
				if ($encrypt_method == 'md5' && !preg_match('/^[A-Fa-f0-9]{32}$/', $password)) {
				    $msg = "Encryption $encrypt_method is invalid";
				    $results[] = $msg;
					continue;
				} else if ($encrypt_method == 'sha1' && !preg_match('/^[A-Fa-f0-9]{40}$/', $password)) {
					$msg = "Encryption $encrypt_method is invalid";
					$results[] = $msg;
					continue;
				}
			} else {
				$msg = "This encryption $encrypt_method is not configured into dokeos ";
				$results[] = $msg;
				continue;
			}
		} elseif (!empty($user_param['password']) && empty($user_param['encrypt_method'])){
			$msg = "If password is not empty the encrypt_method param is required ";
			$results[] = $msg;
			continue;
		} elseif (empty($user_param['password']) && !empty($user_param['encrypt_method'])){
			$msg = "If encrypt_method is not empty the password param is required ";
			$results[] = $msg;
			continue;
		}

		$sql = "SELECT user_id FROM $t_uf uf,$t_ufv ufv WHERE ufv.field_id=uf.id AND field_variable='$original_user_id_name' AND field_value='$original_user_id_value'";
		$res = Database::query($sql, __FILE__, __LINE__);
		$row = Database::fetch_row($res);
		$user_id = $row[0];

		if (empty($user_id)) {
			$results[] = 0; // Original_user_id_value doesn't exist.
			continue;
		} else {
			$sql = "SELECT user_id FROM $table_user WHERE user_id ='$user_id' AND active= '0'";
			$resu = Database::query($sql, __FILE__, __LINE__);
			$r_check_user = Database::fetch_row($resu);
			if (!empty($r_check_user[0])) {
				$results[] = 0; // user_id is not active
				continue;
			}
		}

		// Check if username already exits.
		$sql = "SELECT username FROM $table_user WHERE username ='$username' AND user_id <> '$user_id'";
		$res_un = Database::query($sql, __FILE__, __LINE__);
		$r_username = Database::fetch_row($res_un);

		if (!empty($r_username[0])) {
			$results[] = 0;
			continue; // username already exits
		}

		$sql = "UPDATE $table_user SET
				lastname='".Database::escape_string($lastname)."',
				firstname='".Database::escape_string($firstname)."',
				username='".Database::escape_string($username)."',";
		if (!is_null($password)) {
			$sql .= " password='".Database::escape_string($password)."',";
		}
		if (!is_null($auth_source)) {
			$sql .=	" auth_source='".Database::escape_string($auth_source)."',";
		}
		$sql .=	"
				email='".Database::escape_string($email)."',
				status='".Database::escape_string($status)."',
				official_code='".Database::escape_string($official_code)."',
				phone='".Database::escape_string($phone)."',
				picture_uri='".Database::escape_string($picture_uri)."',
				expiration_date='".Database::escape_string($expiration_date)."',
				active='".Database::escape_string($active)."',
				hr_dept_id=".intval($hr_dept_id);

		if (!is_null($creator_id)) {
			$sql .= ", creator_id='".Database::escape_string($creator_id)."'";
		}
		$sql .=	" WHERE user_id='$user_id'";
		$return = @Database::query($sql, __FILE__, __LINE__);

		if (is_array($extra_list) && count($extra_list) > 0) {
			foreach ($extra_list as $extra) {
				$extra_field_name = $extra['field_name'];
				$extra_field_value = $extra['field_value'];
				// Save the external system's id into user_field_value table.
				$res = UserManager::update_extra_field_value($user_id, $extra_field_name, $extra_field_value);
			}
		}

		$results[] = $return;
		continue;
	} //end principal foreach

	$count_results = count($results);
	$output = array();
	for($i = 0; $i < $count_results; $i++) {
		$output[] = array('original_user_id_value' => $orig_user_id_value[$i], 'result' => $results[$i]);
	}

	return $output;
}

/* Register DokeosWSEditUserPasswordCrypted function */
// Register the data structures used by the service
$server->wsdl->addComplexType(
	'editUserPasswordCrypted',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'original_user_id_value' => array('name' => 'original_user_id_value', 'type' => 'xsd:string'),
		'original_user_id_name' => array('name' => 'original_user_id_name', 'type' => 'xsd:string'),
		'firstname' => array('name' => 'firstname', 'type' => 'xsd:string'),
		'lastname' => array('name' => 'lastname', 'type' => 'xsd:string'),
		'username' => array('name' => 'username', 'type' => 'xsd:string'),
		'password' => array('name' => 'password', 'type' => 'xsd:string'),
		'encrypt_method' => array('name' => 'encrypt_method', 'type' => 'xsd:string'),
		'email' => array('name' => 'email', 'type' => 'xsd:string'),
		'status' => array('name' => 'status', 'type' => 'xsd:string'),
		'phone' => array('name' => 'phone', 'type' => 'xsd:string'),
		'expiration_date' => array('name' => 'expiration_date', 'type' => 'xsd:string'),
		'extra' => array('name' => 'extra', 'type' => 'tns:extrasList'),
		'secret_key' => array('name' => 'secret_key', 'type' => 'xsd:string')
	)
);

// Register the method to expose
$server->register('DokeosWSEditUserPasswordCrypted',					// method name
	array('editUserPasswordCrypted' => 'tns:editUserPasswordCrypted'),	// input parameters
	array('return' => 'xsd:string'),									// output parameters
	'urn:WSRegistration',												// namespace
	'urn:WSRegistration#DokeosWSEditUserPasswordCrypted',				// soapaction
	'rpc',																// style
	'encoded',															// use
	'This service edits a user'											// documentation
);

// Define the method DokeosWSEditUserPasswordCrypted
function DokeosWSEditUserPasswordCrypted($params) {
	global $userPasswordCrypted,$_configuration, $userPasswordCrypted;

	$secret_key = $params['secret_key'];
	$security_key = $_SERVER['REMOTE_ADDR'].$_configuration['security_key'];

	if (!api_is_valid_secret_key($secret_key, $security_key)) {
		return -1; // The secret key is incorrect.
	}

	$table_user = Database :: get_main_table(TABLE_MAIN_USER);
	$t_uf = Database::get_main_table(TABLE_MAIN_USER_FIELD);
	$t_ufv = Database::get_main_table(TABLE_MAIN_USER_FIELD_VALUES);

	$original_user_id_value = $params['original_user_id_value'];
	$original_user_id_name = $params['original_user_id_name'];
	$firstname = $params['firstname'];
	$lastname = $params['lastname'];
	$username = $params['username'];
	$password = null;
	$auth_source = null;
	$email = $params['email'];
	$status = $params['status'];
	$official_code = '';
	$phone = $params['phone'];
	$picture_uri = '';
	$expiration_date = $params['expiration_date'];
	$active = 1;
	$creator_id = null;
	$hr_dept_id = 0;
	$extra = null;
	$extra_list = $params['extra'];

	if (!empty($params['password']) && !empty($params['encrypt_method'])) {

		$password = $params['password'];
		$encrypt_method = $params['encrypt_method'];
		if ($userPasswordCrypted === $encrypt_method ) {
			if ($encrypt_method == 'md5' && !preg_match('/^[A-Fa-f0-9]{32}$/', $password)) {
			    $msg = "Encryption $encrypt_method is invalid";
			    return $msg;
			} else if ($encrypt_method == 'sha1' && !preg_match('/^[A-Fa-f0-9]{40}$/', $password)) {
				$msg = "Encryption $encrypt_method is invalid";
				return $msg;
			}
		} else {
			$msg = "This encryption $encrypt_method is not configured into dokeos ";
			return $msg;
		}
	} elseif (!empty($params['password']) && empty($params['encrypt_method'])) {
		$msg = "If password is not empty the encrypt_method param is required ";
		return $msg;
	} elseif (empty($params['password']) && !empty($params['encrypt_method'])) {
		$msg = "If encrypt_method is not empty the password param is required ";
		return $msg;
	}

	$sql = "SELECT user_id FROM $t_uf uf,$t_ufv ufv WHERE ufv.field_id=uf.id AND field_variable='$original_user_id_name' AND field_value='$original_user_id_value'";
	$res = Database::query($sql, __FILE__, __LINE__);
	$row = Database::fetch_row($res);
	$user_id = $row[0];

	if (empty($user_id)) {
		return 0;
	} else {
		$sql = "SELECT user_id FROM $table_user WHERE user_id ='$user_id' AND active= '0'";
		$resu = Database::query($sql, __FILE__, __LINE__);
		$r_check_user = Database::fetch_row($resu);
		if (!empty($r_check_user[0])) {
			return 0;
		}
	}

	// Check whether username already exits.
	$sql = "SELECT username FROM $table_user WHERE username ='$username' AND user_id <> '$user_id'";
	$res_un = Database::query($sql, __FILE__, __LINE__);
	$r_username = Database::fetch_row($res_un);

	if (!empty($r_username[0])) {
		return 0;
	}

	$sql = "UPDATE $table_user SET
				lastname='".Database::escape_string($lastname)."',
				firstname='".Database::escape_string($firstname)."',
				username='".Database::escape_string($username)."',";
	if (!is_null($password)) {
		$sql .= " password='".Database::escape_string($password)."',";
	}
	if (!is_null($auth_source)) {
		$sql .=	" auth_source='".Database::escape_string($auth_source)."',";
	}
	$sql .=	"
				email='".Database::escape_string($email)."',
				status='".Database::escape_string($status)."',
				official_code='".Database::escape_string($official_code)."',
				phone='".Database::escape_string($phone)."',
				picture_uri='".Database::escape_string($picture_uri)."',
				expiration_date='".Database::escape_string($expiration_date)."',
				active='".Database::escape_string($active)."',
				hr_dept_id=".intval($hr_dept_id);

	if (!is_null($creator_id)) {
		$sql .= ", creator_id='".Database::escape_string($creator_id)."'";
	}
	$sql .=	" WHERE user_id='$user_id'";
	$return = @Database::query($sql, __FILE__, __LINE__);

	if (is_array($extra_list) && count($extra_list) > 0) {
		foreach ($extra_list as $extra) {
			$extra_field_name = $extra['field_name'];
			$extra_field_value = $extra['field_value'];
			// save the external system's id into user_field_value table'
			$res = UserManager::update_extra_field_value($user_id, $extra_field_name, $extra_field_value);
		}
	}

	return $return;
}

/* Register DokeosWSDeleteUsers function */
$server->wsdl->addComplexType(
	'deleteUsersParam',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'original_user_id_value' => array('name' => 'original_user_id_value', 'type' => 'xsd:string'),
		'original_user_id_name' => array('name' => 'original_user_id_name', 'type' => 'xsd:string')
	)
);

$server->wsdl->addComplexType(
'deleteUsersParamList',
'complexType',
'array',
'',
'SOAP-ENC:Array',
array(),
array(array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:deleteUsersParam[]')),
'tns:deleteUsersParam'
);

// Register the data structures used by the service
$server->wsdl->addComplexType(
	'deleteUsers',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'users' => array('name' => 'users', 'type' => 'tns:deleteUsersParamList'),
		'secret_key' => array('name' => 'secret_key', 'type' => 'xsd:string')
	)
);

// Prepare output params, in this case will return an array
$server->wsdl->addComplexType(
'result_deleteUsers',
'complexType',
'struct',
'all',
'',
array(
		'original_user_id_value' => array('name' => 'original_user_id_value', 'type' => 'xsd:string'),
		'result' => array('name' => 'result', 'type' => 'xsd:string')
     )
);

$server->wsdl->addComplexType(
'results_deleteUsers',
'complexType',
'array',
'',
'SOAP-ENC:Array',
array(),
array(array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:result_deleteUsers[]')),
'tns:result_deleteUsers'
);

$server->register('DokeosWSDeleteUsers',			// method name
	array('deleteUsers'=>'tns:deleteUsers'),		// input parameters
	array('return' => 'tns:results_deleteUsers'),	// output parameters
	'urn:WSRegistration',							// namespace
	'urn:WSRegistration#DokeosWSDeleteUsers',		// soapaction
	'rpc',											// style
	'encoded',										// use
	'This service deletes a user  '					// documentation
);

// Define the method DokeosWSDeleteUsers
function DokeosWSDeleteUsers($params) {
	global $_configuration;

	$secret_key = $params['secret_key'];
	$security_key = $_SERVER['REMOTE_ADDR'].$_configuration['security_key'];

	if (!api_is_valid_secret_key($secret_key, $security_key)) {
		return -1; // The secret key is incorrect.
	}

	$table_user = Database :: get_main_table(TABLE_MAIN_USER);
	$t_uf = Database::get_main_table(TABLE_MAIN_USER_FIELD);
	$t_ufv = Database::get_main_table(TABLE_MAIN_USER_FIELD_VALUES);

	$users_params = $params['users'];
	$results = array();
	$orig_user_id_value = array();

	foreach ($users_params as $user_param) {

		$original_user_id_name = $user_param['original_user_id_name'];
	   	$original_user_id_value = $user_param['original_user_id_value'];
	   	$orig_user_id_value[] = $user_param['original_user_id_value'];
		$sql = "SELECT user_id FROM $t_uf uf,$t_ufv ufv WHERE ufv.field_id=uf.id AND field_variable='$original_user_id_name' AND field_value='$original_user_id_value'";
		$res = Database::query($sql, __FILE__, __LINE__);
		$row = Database::fetch_row($res);
		$user_id = $row[0];

		if (empty($user_id)) {
			$results[] = 0;
			continue;
		} else {
			$sql = "SELECT user_id FROM $table_user WHERE user_id ='$user_id' AND active= '0'";
			$resu = Database::query($sql, __FILE__, __LINE__);
			$r_check_user = Database::fetch_row($resu);
			if (!empty($r_check_user[0])) {
				$results[] = 0;
				continue;
			}
		}

		// Update active to 0
		$sql = "UPDATE $table_user SET active='0' WHERE user_id = '$user_id'";
		$res = Database::query($sql, __FILE__, __LINE__);
		$results[] = 1;
		continue;
	}

   	$count_results = count($results);
	$output = array();
	for($i = 0; $i < $count_results; $i++) {
		$output[] = array('original_user_id_value' => $orig_user_id_value[$i], 'result' => $results[$i]);
	}

	return $output;
}

/* Register DokeosWSDeleteUser function */
$server->wsdl->addComplexType(
	'deleteUser',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'original_user_id_value' => array('name' => 'original_user_id_value', 'type' => 'xsd:string'),
		'original_user_id_name' => array('name' => 'original_user_id_name', 'type' => 'xsd:string'),
		'secret_key' => array('name' => 'secret_key', 'type' => 'xsd:string')
	)
);


$server->register('DokeosWSDeleteUser',			// method name
	array('deleteUser'=>'tns:deleteUser'),		// input parameters
	array('return' => 'xsd:string'),			// output parameters
	'urn:WSRegistration',						// namespace
	'urn:WSRegistration#DokeosWSDeleteUser',	// soapaction
	'rpc',										// style
	'encoded',									// use
	'This service deletes a user  '				// documentation
);

// Define the method DokeosWSDeleteUser
function DokeosWSDeleteUser($params) {
	global $_configuration;

	$secret_key = $params['secret_key'];
	$security_key = $_SERVER['REMOTE_ADDR'].$_configuration['security_key'];

	if (!api_is_valid_secret_key($secret_key, $security_key)) {
		return -1; // Secret key is incorrect.
	}

	$table_user = Database :: get_main_table(TABLE_MAIN_USER);
	$t_uf = Database::get_main_table(TABLE_MAIN_USER_FIELD);
	$t_ufv = Database::get_main_table(TABLE_MAIN_USER_FIELD_VALUES);

	$original_user_id_name = $params['original_user_id_name'];
   	$original_user_id_value = $params['original_user_id_value'];
	$sql = "SELECT user_id FROM $t_uf uf,$t_ufv ufv WHERE ufv.field_id=uf.id AND field_variable='$original_user_id_name' AND field_value='$original_user_id_value'";
	$res = Database::query($sql, __FILE__, __LINE__);
	$row = Database::fetch_row($res);
	$user_id = $row[0];

	if (empty($user_id)) {
		return 0;
	} else {
		$sql = "SELECT user_id FROM $table_user WHERE user_id ='$user_id' AND active= '0'";
		$resu = Database::query($sql, __FILE__, __LINE__);
		$r_check_user = Database::fetch_row($resu);
		if (!empty($r_check_user[0])) {
			return 0;
		}
	}

	// Update active to 0
	$sql = "UPDATE $table_user SET active='0' WHERE user_id = '$user_id'";
	$res = Database::query($sql, __FILE__, __LINE__);
	return 1;
}

/* Register DokeosWSCreateCourse function */
// Register the data structures used by the service

$server->wsdl->addComplexType(
	'createCourseParams',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'title' => array('name' => 'title', 'type' => 'xsd:string'),
		'category_code' => array('name' => 'category_code', 'type' => 'xsd:string'),
		'wanted_code' => array('name' => 'wanted_code', 'type' => 'xsd:string'),
		'tutor_name' => array('name' => 'tutor_name', 'type' => 'xsd:string'),
		'course_language' => array('name' => 'course_language', 'type' => 'xsd:string'),
		'original_course_id_name' => array('name' => 'original_course_id_name', 'type' => 'xsd:string'),
		'original_course_id_value' => array('name' => 'original_course_id_value', 'type' => 'xsd:string'),
		'extra' => array('name' => 'extra', 'type' => 'tns:extrasList')
	)
);

$server->wsdl->addComplexType(
'createCourseParamsList',
'complexType',
'array',
'',
'SOAP-ENC:Array',
array(),
array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType' => 'tns:createCourseParams[]')),'tns:createCourseParams'
);

// Register the data structures used by the service
$server->wsdl->addComplexType(
	'createCourse',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'courses' => array('name' => 'courses', 'type' => 'tns:createCourseParamsList'),
		'secret_key' => array('name' => 'secret_key', 'type' => 'xsd:string')
	)
);

// Prepare output params, in this case will return an array
$server->wsdl->addComplexType(
'result_createCourse',
'complexType',
'struct',
'all',
'',
array(
		'original_course_id_value' => array('name' => 'original_course_id_value', 'type' => 'xsd:string'),
		'result' => array('name' => 'result', 'type' => 'xsd:string')
     )
);

$server->wsdl->addComplexType(
'results_createCourse',
'complexType',
'array',
'',
'SOAP-ENC:Array',
array(),
array(array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:result_createCourse[]')),
'tns:result_createCourse'
);

// Register the method to expose
$server->register('DokeosWSCreateCourse',			// method name
	array('createCourse' => 'tns:createCourse'),	// input parameters
	array('return' => 'tns:results_createCourse'),	// output parameters
	'urn:WSRegistration',							// namespace
	'urn:WSRegistration#DokeosWSCreateCourse',		// soapaction
	'rpc',											// style
	'encoded',										// use
	'This service adds a course into dokeos  '		// documentation
);

// Define the method DokeosWSCreateCourse
function DokeosWSCreateCourse($params) {

	global $firstExpirationDelay, $_configuration;

	$secret_key = $params['secret_key'];
	$security_key = $_SERVER['REMOTE_ADDR'].$_configuration['security_key'];

	//return $secret_key;
	if (!api_is_valid_secret_key($secret_key,$security_key)) {
		return -1; // The secret key is incorrect.
	}

	$t_cfv = Database::get_main_table(TABLE_MAIN_COURSE_FIELD_VALUES);
	$table_field = Database::get_main_table(TABLE_MAIN_COURSE_FIELD);
	$table_course_category = Database :: get_main_table(TABLE_MAIN_CATEGORY);
	$table_course = Database :: get_main_table(TABLE_MAIN_COURSE);

	$courses_params = $params['courses'];
	$results = array();
	$orig_course_id_value = array();

	foreach ($courses_params as $course_param) {

		$title = $course_param['title'];
		$category_code = $course_param['category_code'];
		$wanted_code = $course_param['wanted_code'];
		$tutor_name = $course_param['tutor_name'];
		$course_language = 'english'; // TODO: A hard-coded value.
		$original_course_id_name = $course_param['original_course_id_name'];
		$original_course_id_value = $course_param['original_course_id_value'];
		$orig_course_id_value[] = $course_param['original_course_id_value'];
		$extra_list = $course_param['extra'];

		// Check whether exits $x_course_code into user_field_values table.
		$sql = "SELECT field_value,course_code FROM $table_field cf,$t_cfv cfv WHERE cfv.field_id=cf.id AND field_variable='$original_course_id_name' AND field_value='$original_course_id_value'";
		$res = Database::query($sql, __FILE__, __LINE__);
		$row = Database::fetch_row($res);

		if (!empty($row[0])) {
			// Check whether user is not active.
			$sql = "SELECT code FROM $table_course WHERE code ='".$row[1]."' AND visibility= '0'";
			$resu = Database::query($sql, __FILE__, __LINE__);
			$r_check_course = Database::fetch_row($resu);
			if (!empty($r_check_course[0])) {
				$sql = "UPDATE $table_course SET course_language='".Database::escape_string($course_language)."',
									title='".Database::escape_string($title)."',
									category_code='".Database::escape_string($category_code)."',
									tutor_name='".Database::escape_string($tutor_name)."',
									visual_code='".Database::escape_string($wanted_code)."',
									visibility = '3'
						WHERE code='".Database::escape_string($r_check_course[0])."'";
				Database::query($sql, __FILE__, __LINE__);
				if (is_array($extra_list) && count($extra_list) > 0) {
					foreach ($extra_list as $extra) {
						$extra_field_name = $extra['field_name'];
						$extra_field_value = $extra['field_value'];
						// Save the external system's id into course_field_value table.
						$res = CourseManager::update_course_extra_field_value($r_check_course[0], $extra_field_name, $extra_field_value);
					}
				}
				$results[] = $r_check_course[0];
				continue;
			} else {
				$results[] = 0;
				continue; // Original course id already exits.
			}
		}

		if (!empty($course_param['course_language'])) {
			$course_language = $course_param['course_language'];
		}

		$dbnamelength = strlen($_configuration['db_prefix']);
		//Ensure the database prefix + database name do not get over 40 characters
		$maxlength = 40 - $dbnamelength;

		// Set default values
		if (isset($_user['language']) && $_user['language'] != '') {
			$values['course_language'] = $_user['language'];
		} else {
			$values['course_language'] = api_get_setting('platformLanguage');
		}

		$values['tutor_name'] = api_get_person_name($_user['firstName'], $_user['lastName'], null, null, $values['course_language']);

		if (trim($wanted_code) == '') {
			$wanted_code = generate_course_code(substr($title, 0, $maxlength));
		}

		$keys = define_course_keys($wanted_code, '', $_configuration['db_prefix']);

		$sql_check = sprintf('SELECT * FROM '.$table_course.' WHERE visual_code = "%s"',Database :: escape_string($wanted_code));
		$result_check = Database::query($sql_check, __FILE__, __LINE__); // I don't know why this api function doesn't work...
		if (Database::num_rows($result_check) < 1) {
			if (sizeof($keys)) {
				$visual_code = $keys['currentCourseCode'];
				$code = $keys['currentCourseId'];
				$db_name = $keys['currentCourseDbName'];
				$directory = $keys['currentCourseRepository'];
				$expiration_date = time() + $firstExpirationDelay;
				prepare_course_repository($directory, $code);
				update_Db_course($db_name);
				$pictures_array = fill_course_repository($directory);
				fill_Db_course($db_name, $directory, $course_language, $pictures_array);
				$return = register_course($code, $visual_code, $directory, $db_name, $tutor_name, $category_code, $title, $course_language, api_get_user_id(), $expiration_date);

				// Save new fieldlabel into course_field table.
				$field_id = CourseManager::create_course_extra_field($original_course_id_name, 1, $original_course_id_name);

				// Save the external system's id into user_field_value table.
				$res = CourseManager::update_course_extra_field_value($code, $original_course_id_name, $original_course_id_value);

				if (is_array($extra_list) && count($extra_list) > 0) {
					foreach ($extra_list as $extra) {
						$extra_field_name = $extra['field_name'];
						$extra_field_value = $extra['field_value'];
						// Save new fieldlabel into course_field table.
						$field_id = CourseManager::create_course_extra_field($extra_field_name, 1, $extra_field_name);
						// Save the external system's id into course_field_value table.
						$res = CourseManager::update_course_extra_field_value($code, $extra_field_name, $extra_field_value);
					}
				}
			}
			$results[] = $code;
			continue;
		} else {
			$results[] = 0;
			continue;
		}

	} // end principal foreach

	$count_results = count($results);
	$output = array();
	for($i = 0; $i < $count_results; $i++) {
		$output[] = array('original_course_id_value' => $orig_course_id_value[$i], 'result' => $results[$i]);
	}

	return $output;
}

/* Register DokeosWSCreateCourseByTitle function */
// Register the data structures used by the service
$server->wsdl->addComplexType(
	'createCourseByTitleParams',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'title' => array('name' => 'title', 'type' => 'xsd:string'),
		'tutor_name' => array('name' => 'tutor_name', 'type' => 'xsd:string'),
		'original_course_id_name' => array('name' => 'original_course_id_name', 'type' => 'xsd:string'),
		'original_course_id_value' => array('name' => 'original_course_id_value', 'type' => 'xsd:string'),
		'extra' => array('name' => 'extra', 'type' => 'tns:extrasList')
	)
);

$server->wsdl->addComplexType(
'createCourseByTitleParamsList',
'complexType',
'array',
'',
'SOAP-ENC:Array',
array(),
array(array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:createCourseByTitleParams[]')),
'tns:createCourseByTitleParams'
);

// Register the data structures used by the service
$server->wsdl->addComplexType(
	'createCourseByTitle',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'courses' => array('name' => 'courses', 'type' => 'tns:createCourseByTitleParamsList'),
		'secret_key' => array('name' => 'secret_key', 'type' => 'xsd:string')
	)
);

// Prepare output params, in this case will return an array
$server->wsdl->addComplexType(
'result_createCourseByTitle',
'complexType',
'struct',
'all',
'',
array(
		'original_course_id_value' => array('name' => 'original_course_id_value', 'type' => 'xsd:string'),
		'result' => array('name' => 'result', 'type' => 'xsd:string')
     )
);

$server->wsdl->addComplexType(
'results_createCourseByTitle',
'complexType',
'array',
'',
'SOAP-ENC:Array',
array(),
array(array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:result_createCourseByTitle[]')),
'tns:result_createCourseByTitle'
);


// Register the method to expose
$server->register('DokeosWSCreateCourseByTitle',					// method name
	array('createCourseByTitle' => 'tns:createCourseByTitle'),		// input parameters
	array('return' => 'tns:results_createCourseByTitle'),			// output parameters
	'urn:WSRegistration',											// namespace
	'urn:WSRegistration#DokeosWSCreateCourseByTitle',				// soapaction
	'rpc',															// style
	'encoded',														// use
	'This service adds a course by title into dokeos '				// documentation
);

// Define the method DokeosWSCreateCourseByTitle
function DokeosWSCreateCourseByTitle($params) {

	global $firstExpirationDelay, $_configuration;

	$secret_key = $params['secret_key'];
	$security_key = $_SERVER['REMOTE_ADDR'].$_configuration['security_key'];

	if (!api_is_valid_secret_key($secret_key, $security_key)) {
		return -1; // The secret key is incorrect.
	}

	$t_cfv 					= Database::get_main_table(TABLE_MAIN_COURSE_FIELD_VALUES);
	$table_field 			= Database::get_main_table(TABLE_MAIN_COURSE_FIELD);
	$table_course_category 	= Database::get_main_table(TABLE_MAIN_CATEGORY);
	$table_course 			= Database::get_main_table(TABLE_MAIN_COURSE);

	$courses_params = $params['courses'];
	$results = array();
	$orig_course_id_value = array();

	foreach($courses_params as $course_param) {

		$title = $course_param['title'];
		$category_code = 'LANG'; // TODO: A hard-coded value.
		$wanted_code = '';
		$tutor_firstname = api_get_setting('administratorName');
		$tutor_lastname = api_get_setting('administratorSurname');
		$course_language = 'spanish'; // TODO: Incorrect default value, it should 'english'.
		if (!empty($course_param['course_language'])) {
			$course_language = $course_param['course_language'];
		}
		$tutor_name = api_get_person_name($tutor_firstname, $tutor_lastname, null, null, $course_language);
		if (!empty($course_param['tutor_name'])) {
			$tutor_name = $course_param['tutor_name'];
		}
		$original_course_id_name = $course_param['original_course_id_name'];
		$original_course_id_value = $course_param['original_course_id_value'];
		$orig_course_id_value[] = $course_param['original_course_id_value'];
		$extra_list = $course_param['extra'];

		$dbnamelength = strlen($_configuration['db_prefix']);
		// Ensure the database prefix + database name do not get over 40 characters
		$maxlength = 40 - $dbnamelength;

		if (empty($wanted_code)) {
			$wanted_code = generate_course_code(substr($title, 0, $maxlength));
		}

		// Check if exits $x_course_code into user_field_values table.
		$sql = "SELECT field_value,course_code FROM $table_field cf,$t_cfv cfv WHERE cfv.field_id=cf.id AND field_variable='$original_course_id_name' AND field_value='$original_course_id_value'";
		$res = Database::query($sql, __FILE__, __LINE__);
		$row = Database::fetch_row($res);

		if (!empty($row[0])) {
			// Check whether user is not active.
			$sql = "SELECT code FROM $table_course WHERE code ='".$row[1]."' AND visibility= '0'";
			$resu = Database::query($sql, __FILE__, __LINE__);
			$r_check_course = Database::fetch_row($resu);
			if (!empty($r_check_course[0])) {
				$sql = "UPDATE $table_course SET course_language='".Database::escape_string($course_language)."',
									title='".Database::escape_string($title)."',
									category_code='".Database::escape_string($category_code)."',
									tutor_name='".Database::escape_string($tutor_name)."',
									visual_code='".Database::escape_string($wanted_code)."',
									visibility = '3'
						WHERE code='".Database::escape_string($r_check_course[0])."'";
				Database::query($sql, __FILE__, __LINE__);
				$results[] = $r_check_course[0];
				continue;
			} else {
				$results[] = 0;
				continue;
			}
		}

		// Set default values.
		if (isset($_user['language']) && $_user['language'] != '') {
			$values['course_language'] = $_user['language'];
		} else {
			$values['course_language'] = api_get_setting('platformLanguage');
		}

		$values['tutor_name'] = api_get_person_name($_user['firstName'], $_user['lastName'], null, null, $values['course_language']);

		$keys = define_course_keys($wanted_code, '', $_configuration['db_prefix']);

		$sql_check = sprintf('SELECT * FROM '.$table_course.' WHERE visual_code = "%s"', Database :: escape_string($wanted_code));
		$result_check = Database::query($sql_check, __FILE__, __LINE__); // I don't know why this api function doesn't work...
		if (Database::num_rows($result_check) < 1) {
			if (sizeof($keys)) {
				$visual_code = $keys['currentCourseCode'];
				$code = $keys['currentCourseId'];
				$db_name = $keys['currentCourseDbName'];
				$directory = $keys['currentCourseRepository'];
				$expiration_date = time() + $firstExpirationDelay;
				prepare_course_repository($directory, $code);
				update_Db_course($db_name);
				$pictures_array = fill_course_repository($directory);
				fill_Db_course($db_name, $directory, $course_language, $pictures_array);
				$return = register_course($code, $visual_code, $directory, $db_name, $tutor_name, $category_code, $title, $course_language, api_get_user_id(), $expiration_date);

				// Save new fieldlabel into course_field table.
				$field_id = CourseManager::create_course_extra_field($original_course_id_name, 1, $original_course_id_name);

				// Save the external system's id into user_field_value table.
				$res = CourseManager::update_course_extra_field_value($code, $original_course_id_name, $original_course_id_value);

				if (is_array($extra_list) && count($extra_list) > 0) {
					foreach ($extra_list as $extra) {
						$extra_field_name = $extra['field_name'];
						$extra_field_value = $extra['field_value'];
						// Save new fieldlabel into course_field table.
						$field_id = CourseManager::create_course_extra_field($extra_field_name, 1, $extra_field_name);
						// Save the external system's id into course_field_value table.
						$res = CourseManager::update_course_extra_field_value($code, $extra_field_name, $extra_field_value);
					}
				}
			}
			$results[] = $code;
			continue;

		} else {
			$results[] = 0;
			continue;
		}

	} // end principal foreach

	$count_results = count($results);
	$output = array();
	for ($i = 0; $i < $count_results; $i++) {
		$output[] = array('original_course_id_value' => $orig_course_id_value[$i], 'result' => $results[$i]);
	}

	return $output;
}

/* Register DokeosWSEditCourse function */
// Register the data structures used by the service

$server->wsdl->addComplexType(
	'editCourseParams',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'tutor_id' => array('name' => 'tutor_id', 'type' => 'xsd:string'),
		'title' => array('name' => 'title', 'type' => 'xsd:string'),
		'category_code' => array('name' => 'category_code', 'type' => 'xsd:string'),
		'department_name' => array('name' => 'department_name', 'type' => 'xsd:string'),
		'department_url' => array('name' => 'department_url', 'type' => 'xsd:string'),
		'course_language' => array('name' => 'course_language', 'type' => 'xsd:string'),
		'visibility' => array('name' => 'visibility', 'type' => 'xsd:string'),
		'subscribe' => array('name' => 'subscribe', 'type' => 'xsd:string'),
		'unsubscribe' => array('name' => 'unsubscribe', 'type' => 'xsd:string'),
		'visual_code' => array('name' => 'visual_code', 'type' => 'xsd:string'),
		'original_course_id_name' => array('name' => 'original_course_id_name', 'type' => 'xsd:string'),
		'original_course_id_value' => array('name' => 'original_course_id_value', 'type' => 'xsd:string'),
		'extra' => array('name' => 'extra', 'type' => 'tns:extrasList')
	)
);

$server->wsdl->addComplexType(
'editCourseParamsList',
'complexType',
'array',
'',
'SOAP-ENC:Array',
array(),
array(array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:editCourseParams[]')),
'tns:editCourseParams'
);

$server->wsdl->addComplexType(
	'editCourse',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'courses' => array('name' => 'courses', 'type' => 'tns:editCourseParamsList'),
		'secret_key' => array('name' => 'secret_key', 'type' => 'xsd:string')
	)
);

// Prepare output params, in this case will return an array
$server->wsdl->addComplexType(
'result_editCourse',
'complexType',
'struct',
'all',
'',
array(
		'original_course_id_value' => array('name' => 'original_course_id_value', 'type' => 'xsd:string'),
		'result' => array('name' => 'result', 'type' => 'xsd:string')
     )
);

$server->wsdl->addComplexType(
'results_editCourse',
'complexType',
'array',
'',
'SOAP-ENC:Array',
array(),
array(array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:result_editCourse[]')),
'tns:result_editCourse'
);

// Register the method to expose
$server->register('DokeosWSEditCourse',			// method name
	array('editCourse' => 'tns:editCourse'),	// input parameters
	array('return' => 'tns:results_editCourse'),			// output parameters
	'urn:WSRegistration',						// namespace
	'urn:WSRegistration#DokeosWSEditCourse',	// soapaction
	'rpc',										// style
	'encoded',									// use
	'This service edits a course into dokeos'	// documentation
);

// Define the method DokeosWSEditCourse
function DokeosWSEditCourse($params){

	global $_configuration;

	$secret_key = $params['secret_key'];
	$security_key = $_SERVER['REMOTE_ADDR'].$_configuration['security_key'];

	if (!api_is_valid_secret_key($secret_key, $security_key)) {
		return -1; // The secret key is incorrect.
	}

	$course_table = Database::get_main_table(TABLE_MAIN_COURSE);
	$course_user_table = Database::get_main_table(TABLE_MAIN_COURSE_USER);
	$t_cfv 			= Database::get_main_table(TABLE_MAIN_COURSE_FIELD_VALUES);
	$table_field 	= Database::get_main_table(TABLE_MAIN_COURSE_FIELD);

	$courses_params = $params['courses'];
	$results = array();
	$orig_course_id_value = array();

	foreach ($courses_params as $course_param) {

		$tutor_id = $course_param['tutor_id'];
		$title = $course_param['title'];
		$category_code = $course_param['category_code'];
		$department_name = $course_param['department_name'];
		$department_url = $course_param['department_url'];
		$course_language = $course_param['course_language'];
		$visibility = $course_param['visibility'];
		$subscribe = $course_param['subscribe'];
		$unsubscribe = $course_param['unsubscribe'];
		$visual_code = $course_param['visual_code'];

		$original_course_id_name = $course_param['original_course_id_name'];
		$original_course_id_value = $course_param['original_course_id_value'];
		$orig_course_id_value[] = $original_course_id_value;
		$extra_list = $course_param['extra'];

		// Get course code from id from remote system.
		$sql = "SELECT course_code	FROM $table_field cf,$t_cfv cfv WHERE cfv.field_id=cf.id AND field_variable='$original_course_id_name' AND field_value='$original_course_id_value'";
		$res = Database::query($sql, __FILE__, __LINE__);
		$row = Database::fetch_row($res);

		$course_code = $row[0];

		if (empty($course_code)) {
			$results[] = 0; // Original_course_id_value doesn't exist.
			continue;
		}

		$table_user = Database :: get_main_table(TABLE_MAIN_USER);
		$sql = "SELECT concat(lastname,'',firstname) as tutor_name FROM $table_user WHERE status='1' AND user_id = '$tutor_id' ORDER BY lastname,firstname";
		$res = Database::query($sql, __FILE__, __LINE__);
		$tutor_name = Database::fetch_row($res);

		$dbnamelength = strlen($_configuration['db_prefix']);
		$maxlength = 40 - $dbnamelength;

		if (empty($visual_code)) {
			$visual_code = generate_course_code(substr($title, 0, $maxlength));
		}

		$disk_quota = '50000'; // TODO: A hard-coded value.
		$tutor_name = $tutor_name[0];
		$sql = "UPDATE $course_table SET course_language='".Database::escape_string($course_language)."',
									title='".Database::escape_string($title)."',
									category_code='".Database::escape_string($category_code)."',
									tutor_name='".Database::escape_string($tutor_name)."',
									visual_code='".Database::escape_string($visual_code)."',
									department_name='".Database::escape_string($department_name)."',
									department_url='".Database::escape_string($department_url)."',
									disk_quota='".Database::escape_string($disk_quota)."',
									visibility = '".Database::escape_string($visibility)."',
									subscribe = '".Database::escape_string($subscribe)."',
									unsubscribe='".Database::escape_string($unsubscribe)."'
								WHERE code='".Database::escape_string($course_code)."'";
		$res = Database::query($sql, __FILE__, __LINE__);

		if (is_array($extra_list) && count($extra_list) > 0) {
			foreach ($extra_list as $extra) {
				$extra_field_name = $extra['field_name'];
				$extra_field_value = $extra['field_value'];
				// Save the external system's id into course_field_value table.
				$res = CourseManager::update_course_extra_field_value($course_code, $extra_field_name, $extra_field_value);
			}
		}

		if ($res) {
			$results[] = 1;
			continue;
		} else {
			$results[] = 0;
			continue;
		}

	} // end principal foreach

	$count_results = count($results);
	$output = array();
	for ($i = 0; $i < $count_results; $i++) {
		$output[] = array('original_course_id_value' => $orig_course_id_value[$i], 'result' => $results[$i]);
	}

	return $output;
}

/* Register DokeosWSCourseDescription function */
// Register the data structures used by the service

$server->wsdl->addComplexType(
	'courseDescription',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'original_course_id_name' => array('name' => 'original_course_id_name', 'type' => 'xsd:string'),
		'original_course_id_value' => array('name' => 'original_course_id_value', 'type' => 'xsd:string'),
		'secret_key' => array('name' => 'secret_key', 'type' => 'xsd:string')
	)
);

// Prepare output params, in this case will return an array
$server->wsdl->addComplexType(
'fields_course_desc',
'complexType',
'struct',
'all',
'',
array(
		'course_desc_id' => array('name' => 'course_desc_id', 'type' => 'xsd:string'),
		'course_desc_default_title' => array('name' => 'course_desc_default_title', 'type' => 'xsd:string'),
		'course_desc_title' => array('name' => 'course_desc_title', 'type' => 'xsd:string'),
		'course_desc_content' => array('name' => 'course_desc_content', 'type' => 'xsd:string')
     )
);

$server->wsdl->addComplexType(
'fields_course_desc_list',
'complexType',
'array',
'',
'SOAP-ENC:Array',
array(),
array(array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:fields_course_desc[]')),
'tns:fields_course_desc'
);


// Register the method to expose
$server->register('DokeosWSCourseDescription',				// method name
	array('courseDescription' => 'tns:courseDescription'),	// input parameters
	array('return' => 'tns:fields_course_desc_list'),		// output parameters
	'urn:WSRegistration',									// namespace
	'urn:WSRegistration#DokeosWSCourseDescription',			// soapaction
	'rpc',													// style
	'encoded',												// use
	'This service edits a course description into dokeos'	// documentation
);

// Define the method DokeosWSCourseDescription
function DokeosWSCourseDescription($params) {

	global $_configuration, $_course;

	$secret_key = $params['secret_key'];
	$security_key = $_SERVER['REMOTE_ADDR'].$_configuration['security_key'];

	if (!api_is_valid_secret_key($secret_key, $security_key)) {
		return -1; // The secret key is incorrect.
	}

	$course_table = Database::get_main_table(TABLE_MAIN_COURSE);
	$course_user_table = Database::get_main_table(TABLE_MAIN_COURSE_USER);
	$t_cfv 			= Database::get_main_table(TABLE_MAIN_COURSE_FIELD_VALUES);
	$table_field 	= Database::get_main_table(TABLE_MAIN_COURSE_FIELD);

	$array_course_desc_id = array();
	$array_course__desc_default_title = array();
	$array_course_desc_title = array();
	$array_course_desc_content = array();

	$original_course_id_name = $params['original_course_id_name'];
	$original_course_id_value = $params['original_course_id_value'];

	// Get course code from id from remote system.
	$sql = "SELECT course_code	FROM $table_field cf,$t_cfv cfv WHERE cfv.field_id=cf.id AND field_variable='$original_course_id_name' AND field_value='$original_course_id_value'";
	$res = Database::query($sql, __FILE__, __LINE__);
	$row = Database::fetch_row($res);

	$course_code=$row[0];

	if (Database::num_rows($res) < 1) {
		return 0; // Original_course_id_value doesn't exist.
		//continue;
	} else {
		$sql = "SELECT code FROM $course_table WHERE code ='$course_code' AND visibility = '0'";
		$resu = Database::query($sql, __FILE__, __LINE__);
		$r_check_code = Database::fetch_row($resu);
		if (Database::num_rows($resu) > 0) {
			return  0; // This code is not active.
			//continue;
		}
	}

	$course_ifo = api_get_course_info($course_code);

	$t_course_desc = Database::get_course_table(TABLE_COURSE_DESCRIPTION, $course_ifo['dbName']);

	$sql = "SELECT * FROM $t_course_desc";
	$result = Database::query($sql, __FILE__, __LINE__);

	/*$default_titles = array(
							get_lang('GeneralDescription'),
							get_lang('Objectives'),
							get_lang('Topics'),
							get_lang('Methodology'),
							get_lang('CourseMaterial'),
							get_lang('HumanAndTechnicalResources'),
							get_lang('Assessment'),
							get_lang('AddCat'));*/

	// TODO: Hard-coded Spanish texts.
	$default_titles = array('Descripcion general', 'Objetivos', 'Contenidos', 'Metodologia', 'Materiales', 'Recursos humanos y tecnicos', 'Evaluacion', 'Apartado');

	for ($x = 1; $x < 9; $x++) {
		$array_course_desc_id[$x] = $x;
		$array_course_desc_default_title[$x] = $default_titles[$x - 1];
		$array_course_desc_title[$x] = '';
		$array_course_desc_content[$x] = '';
	}

	while ($row = Database::fetch_array($result)) {
		$ind = (int)$row['id'];
		$array_course_desc_title[$ind] = $row['title'];
		$array_course_desc_content[$ind] = $row['content'];
	}

	$count_results = count($default_titles);
	$output = array();
	for($i = 1; $i <= $count_results; $i++) {
		$output[] = array(
			'course_desc_id' => $array_course_desc_id[$i],
			'course_desc_default_title' => $array_course_desc_default_title[$i],
			'course_desc_title' => $array_course_desc_title[$i],
			'course_desc_content' => $array_course_desc_content[$i]
		);
	}

	return $output;
}

/* Register DokeosWSEditCourseDescription function */
// Register the data structures used by the service

$server->wsdl->addComplexType(
	'editCourseDescriptionParams',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'course_desc_id' => array('name' => 'course_desc_id', 'type' => 'xsd:string'),
		'course_desc_title' => array('name' => 'course_desc_title', 'type' => 'xsd:string'),
		'course_desc_content' => array('name' => 'course_desc_content', 'type' => 'xsd:string'),
		'original_course_id_name' => array('name' => 'original_course_id_name', 'type' => 'xsd:string'),
		'original_course_id_value' => array('name' => 'original_course_id_value', 'type' => 'xsd:string')
	)
);

$server->wsdl->addComplexType(
'editCourseDescriptionParamsList',
'complexType',
'array',
'',
'SOAP-ENC:Array',
array(),
array(array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:editCourseDescriptionParams[]')),
'tns:editCourseDescriptionParams'
);

$server->wsdl->addComplexType(
	'editCourseDescription',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'course_desc' => array('name' => 'course_desc', 'type' => 'tns:editCourseDescriptionParamsList'),
		'secret_key' => array('name' => 'secret_key', 'type' => 'xsd:string')
	)
);


// Prepare output params, in this case will return an array
$server->wsdl->addComplexType(
'result_editCourseDescription',
'complexType',
'struct',
'all',
'',
array(
		'original_course_id_value' => array('name' => 'original_course_id_value', 'type' => 'xsd:string'),
		'result' => array('name' => 'result', 'type' => 'xsd:string')
     )
);

$server->wsdl->addComplexType(
'results_editCourseDescription',
'complexType',
'array',
'',
'SOAP-ENC:Array',
array(),
array(array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:result_editCourseDescription[]')),
'tns:result_editCourseDescription'
);


// Register the method to expose
$server->register('DokeosWSEditCourseDescription',			// method name
	array('editCourseDescription' => 'tns:editCourseDescription'),				// input parameters
	array('return' => 'tns:results_editCourseDescription'),						// output parameters
	'urn:WSRegistration',									// namespace
	'urn:WSRegistration#DokeosWSEditCourseDescription',		// soapaction
	'rpc',													// style
	'encoded',												// use
	'This service edits a course description into dokeos'	// documentation
);

// Define the method DokeosWSEditCourseDescription
function DokeosWSEditCourseDescription($params) {

	global $_configuration, $_course;

	$secret_key = $params['secret_key'];
	$security_key = $_SERVER['REMOTE_ADDR'].$_configuration['security_key'];

	if (!api_is_valid_secret_key($secret_key, $security_key)) {
		return -1; // The secret key is incorrect.
	}

	$course_table = Database::get_main_table(TABLE_MAIN_COURSE);
	$course_user_table = Database::get_main_table(TABLE_MAIN_COURSE_USER);
	$t_cfv 			= Database::get_main_table(TABLE_MAIN_COURSE_FIELD_VALUES);
	$table_field 	= Database::get_main_table(TABLE_MAIN_COURSE_FIELD);

	$courses_params = $params['course_desc'];
	$results = array();
	$orig_course_id_value = array();

	foreach ($courses_params as $course_param) {

		$original_course_id_name = $course_param['original_course_id_name'];
		$original_course_id_value = $course_param['original_course_id_value'];
		$course_desc_id = $course_param['course_desc_id'];
		$course_desc_title = $course_param['course_desc_title'];
		$course_desc_content = $course_param['course_desc_content'];
		$orig_course_id_value[] = $original_course_id_value;

		// Get course code from id from the remote system.
		$sql = "SELECT course_code	FROM $table_field cf,$t_cfv cfv WHERE cfv.field_id=cf.id AND field_variable='$original_course_id_name' AND field_value='$original_course_id_value'";
		$res = Database::query($sql, __FILE__, __LINE__);
		$row = Database::fetch_row($res);

		$course_code = $row[0];

		if (Database::num_rows($res) < 1) {
			$results[] = 0;
			continue; // Original_course_id_value doesn't exist.
		} else {
			$sql = "SELECT code FROM $course_table WHERE code ='$course_code' AND visibility = '0'";
			$resu = Database::query($sql, __FILE__, __LINE__);
			$r_check_code = Database::fetch_row($resu);
			if (Database::num_rows($resu) > 0) {
				$results[] = 0;
				continue;
			}
		}

		$course_ifo = api_get_course_info($course_code);

		$t_course_desc = Database::get_course_table(TABLE_COURSE_DESCRIPTION,$course_ifo['dbName']);

		$course_desc_id = Database::escape_string($course_desc_id);
		$course_desc_title = Database::escape_string($course_desc_title);
		$course_desc_content = Database::escape_string($course_desc_content);

		$course_desc_id = (int)$course_desc_id;
		if ($course_desc_id > 8 && $course_desc_id < 1) {
			$results[] = 0; // course_desc_id invalid.
			continue;
		}

		// Check whether data already exits into course_description table.
		$sql_check_id = "SELECT * FROM $t_course_desc WHERE id ='$course_desc_id'";
		$res_check_id = Database::query($sql_check_id, __FILE__, __LINE__);

		if (Database::num_rows($res_check_id) > 0) {
			$sql = "UPDATE $t_course_desc SET title='$course_desc_title', content = '$course_desc_content' WHERE id = '".$course_desc_id."'";
			Database::query($sql, __FILE__, __LINE__);
		} else {
			$sql = "INSERT IGNORE INTO $t_course_desc SET id = '".$course_desc_id."', title = '$course_desc_title', content = '$course_desc_content'";
			Database::query($sql, __FILE__, __LINE__);
		}

		$results[] = 1;

	} // end principal foreach

	$count_results = count($results);
	$output = array();
	for($i = 0; $i < $count_results; $i++) {
		$output[] = array('original_course_id_value' => $orig_course_id_value[$i], 'result' => $results[$i]);
	}

	return $output;
}

/* Register DokeosWSDeleteCourse function */
// Register the data structures used by the service
$server->wsdl->addComplexType(
	'deleteCourseParams',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'original_course_id_value' => array('name' => 'original_course_id_value', 'type' => 'xsd:string'),
		'original_course_id_name' => array('name' => 'original_course_id_name', 'type' => 'xsd:string')
	)
);

$server->wsdl->addComplexType(
'deleteCourseParamsList',
'complexType',
'array',
'',
'SOAP-ENC:Array',
array(),
array(array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:deleteCourseParams[]')),
'tns:deleteCourseParams'
);

// Register the data structures used by the service.
$server->wsdl->addComplexType(
	'deleteCourse',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'courses' => array('name' => 'courses', 'type' => 'tns:deleteCourseParamsList'),
		'secret_key' => array('name' => 'secret_key', 'type' => 'xsd:string')
	)
);

// Prepare output params, in this case will return an array.
$server->wsdl->addComplexType(
'result_deleteCourse',
'complexType',
'struct',
'all',
'',
array(
		'original_course_id_value' => array('name' => 'original_course_id_value', 'type' => 'xsd:string'),
		'result' => array('name' => 'result', 'type' => 'xsd:string')
     )
);

$server->wsdl->addComplexType(
'results_deleteCourse',
'complexType',
'array',
'',
'SOAP-ENC:Array',
array(),
array(array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:result_deleteCourse[]')),
'tns:result_deleteCourse'
);

$server->register('DokeosWSDeleteCourse',			// method name
	array('deleteCourse' => 'tns:deleteCourse'),	// input parameters
	array('return' => 'tns:results_deleteCourse'),	// output parameters
	'urn:WSRegistration',							// namespace
	'urn:WSRegistration#DokeosWSDeleteCourse',		// soapaction
	'rpc',											// style
	'encoded',										// use
	'This service deletes a course '				// documentation
);


// Define the method DokeosWSDeleteCourse
function DokeosWSDeleteCourse($params) {

	global $_configuration;

	$secret_key = $params['secret_key'];
	$security_key = $_SERVER['REMOTE_ADDR'].$_configuration['security_key'];

	if (!api_is_valid_secret_key($secret_key, $security_key)) {
		return -1; // The secret key is incorrect.
	}

	$table_course = Database :: get_main_table(TABLE_MAIN_COURSE);
	$t_cfv 			= Database::get_main_table(TABLE_MAIN_COURSE_FIELD_VALUES);
	$table_field 	= Database::get_main_table(TABLE_MAIN_COURSE_FIELD);

	$courses_params = $params['courses'];
	$results = array();
	$orig_course_id_value = array();

	foreach ($courses_params as $course_param) {

		$original_course_id_value = $course_param['original_course_id_value'];
		$original_course_id_name = $course_param['original_course_id_name'];
		$orig_course_id_value[] = $original_course_id_value;
		// Get course code from id from the remote system.
		$sql_course = "SELECT course_code	FROM $table_field cf,$t_cfv cfv WHERE cfv.field_id=cf.id AND field_variable='$original_course_id_name' AND field_value='$original_course_id_value'";
		$res_course = Database::query($sql_course, __FILE__, __LINE__);
		$row_course = Database::fetch_row($res_course);

		$code = $row_course[0];

		if (empty($code)) {
			$results[] = 0; // Original_course_id_value doesn't exist.
			continue;
		} else {
			$sql = "SELECT code FROM $table_course WHERE code ='$code' AND visibility = '0'";
			$resu = Database::query($sql, __FILE__, __LINE__);
			$r_check_code = Database::fetch_row($resu);
			if (!empty($r_check_code[0])) {
				$results[] = 0; // This code is not active.
				continue;
			}
		}

		$sql = "UPDATE $table_course SET visibility = '0' WHERE code = '$code'";
		$return = Database::query($sql, __FILE__, __LINE__);
		$results[] = $return;
	}

	$count_results = count($results);
	$output = array();
	for ($i = 0; $i < $count_results; $i++) {
		$output[] = array('original_course_id_value' => $orig_course_id_value[$i], 'result' => $results[$i]);
	}

	return $output;
}

/* Register DokeosWSCreateSession function */
// Register data structures used by the service.
$server->wsdl->addComplexType(
	'createSessionParam',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'name' => array('name' => 'name', 'type' => 'xsd:string'),
		'year_start' => array('name' => 'year_start', 'type' => 'xsd:string'),
		'month_start' => array('name' => 'month_start', 'type' => 'xsd:string'),
		'day_start' => array('name' => 'day_start', 'type' => 'xsd:string'),
		'year_end' => array('name' => 'year_end', 'type' => 'xsd:string'),
		'month_end' => array('name' => 'month_end', 'type' => 'xsd:string'),
		'day_end' => array('name' => 'day_end', 'type' => 'xsd:string'),
		'nb_days_access_before' => array('name' => 'nb_days_access_before', 'type' => 'xsd:string'),
		'nb_days_access_after' => array('name' => 'nb_days_access_after', 'type' => 'xsd:string'),
		'nolimit' => array('name' => 'nolimit', 'type' => 'xsd:string'),
		'user_id' => array('name' => 'user_id', 'type' => 'xsd:string'),
		'original_session_id_name' => array('name' => 'original_session_id_name', 'type' => 'xsd:string'),
		'original_session_id_value' => array('name' => 'original_session_id_value', 'type' => 'xsd:string'),
		'extra' => array('name' => 'extra', 'type' => 'tns:extrasList')
	)
);

$server->wsdl->addComplexType(
'createSessionParamList',
'complexType',
'array',
'',
'SOAP-ENC:Array',
array(),
array(array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:createSessionParam[]')),
'tns:createSessionParam'
);

// Register the data structures used by the service
$server->wsdl->addComplexType(
	'createSession',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'sessions' => array('name' => 'sessions', 'type' => 'tns:createSessionParamList'),
		'secret_key' => array('name' => 'secret_key', 'type' => 'xsd:string')
	)
);

// Prepare output params, in this case will return an array
$server->wsdl->addComplexType(
'result_createSession',
'complexType',
'struct',
'all',
'',
array(
		'original_session_id_value' => array('name' => 'original_session_id_value', 'type' => 'xsd:string'),
		'result' => array('name' => 'result', 'type' => 'xsd:string')
     )
);

$server->wsdl->addComplexType(
'results_createSession',
'complexType',
'array',
'',
'SOAP-ENC:Array',
array(),
array(array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:result_createSession[]')),
'tns:result_createSession'
);

// Register the method to expose
$server->register('DokeosWSCreateSession',			// method name
	array('createSession' => 'tns:createSession'),	// input parameters
	array('return' => 'tns:results_createSession'),	// output parameters
	'urn:WSRegistration',							// namespace
	'urn:WSRegistration#DokeosWSCreateSession',		// soapaction
	'rpc',											// style
	'encoded',										// use
	'This service edits a session'					// documentation
);


// define the method DokeosWSCreateSession
function DokeosWSCreateSession($params) {

	global $_user,$_configuration;

	$secret_key = $params['secret_key'];
	$security_key = $_SERVER['REMOTE_ADDR'].$_configuration['security_key'];

	if (!api_is_valid_secret_key($secret_key,$security_key)) {
		return -1; // The secret key is incorrect.
	}

	$tbl_user		= Database::get_main_table(TABLE_MAIN_USER);
	$tbl_session	= Database::get_main_table(TABLE_MAIN_SESSION);
	$t_sf 			= Database::get_main_table(TABLE_MAIN_SESSION_FIELD);
	$t_sfv 			= Database::get_main_table(TABLE_MAIN_SESSION_FIELD_VALUES);

	$sessions_params = $params['sessions'];
	$results = array();
	$orig_session_id_value = array();

	foreach ($sessions_params as $session_param) {

		$name = trim($session_param['name']);
		$year_start = intval($session_param['year_start']);
		$month_start = intval($session_param['month_start']);
		$day_start = intval($session_param['day_start']);
		$year_end = intval($session_param['year_end']);
		$month_end = intval($session_param['month_end']);
		$day_end = intval($session_param['day_end']);
		$nb_days_acess_before = intval($session_param['nb_days_access_before']);
		$nb_days_acess_after = intval($session_param['nb_days_access_after']);
		$id_coach = $session_param['user_id'];
		$nolimit = $session_param['nolimit'];
		$original_session_id_name = $session_param['original_session_id_name'];
		$original_session_id_value = $session_param['original_session_id_value'];
		$orig_session_id_value[] = $session_param['original_session_id_value'];
		$extra_list = $session_param['extra'];
		// Check if exits remote system's session id into session_field_values table.
		$sql = "SELECT field_value	FROM $t_sf sf,$t_sfv sfv WHERE sfv.field_id=sf.id AND field_variable='$original_session_id_name' AND field_value='$original_session_id_value'";
		$res = Database::query($sql, __FILE__, __LINE__);
		$row = Database::fetch_row($res);
		if (Database::num_rows($res) > 0) {
			$results[] = 0;
			continue;
		}

		if (empty($nolimit)){
			$date_start="$year_start-".(($month_start < 10)?"0$month_start":$month_start)."-".(($day_start < 10)?"0$day_start":$day_start);
			$date_end="$year_end-".(($month_end < 10)?"0$month_end":$month_end)."-".(($day_end < 10)?"0$day_end":$day_end);
		} else {
			$date_start="000-00-00";
			$date_end="000-00-00";
		}

		if(empty($name)) {
			$results[] = 0;
			continue;
		} elseif (empty($nolimit) && (!$month_start || !$day_start || !$year_start || !checkdate($month_start, $day_start, $year_start))) {
			$results[] = 0;
			continue;
		} elseif (empty($nolimit) && (!$month_end || !$day_end || !$year_end || !checkdate($month_end,$day_end,$year_end))) {
			$results[] = 0;
			continue;
		} elseif (empty($nolimit) && $date_start >= $date_end) {
			$results[] = 0;
			continue;
		} else {
			$rs = Database::query("SELECT 1 FROM $tbl_session WHERE name='".addslashes($name)."'", __FILE__, __LINE__);
			if (Database::num_rows($rs)) {
				$results[] = 0;
				continue;
			} else {
			Database::query("INSERT INTO $tbl_session(name,date_start,date_end,id_coach,session_admin_id, nb_days_access_before_beginning, nb_days_access_after_end) VALUES('".addslashes($name)."','$date_start','$date_end','$id_coach',".intval($_user['user_id']).",".$nb_days_acess_before.", ".$nb_days_acess_after.")", __FILE__, __LINE__);
				$id_session = Database::get_last_insert_id();

				// Save new fieldlabel into course_field table.
				$field_id = SessionManager::create_session_extra_field($original_session_id_name, 1, $original_session_id_name);

				// Save the external system's id into user_field_value table.
				$res = SessionManager::update_session_extra_field_value($id_session, $original_session_id_name, $original_session_id_value);

				if (is_array($extra_list) && count($extra_list) > 0) {
					foreach ($extra_list as $extra) {
						$extra_field_name = $extra['field_name'];
						$extra_field_value = $extra['field_value'];
						// Save new fieldlabel into course_field table.
						$field_id = SessionManager::create_session_extra_field($extra_field_name, 1, $extra_field_name);
						// Save the external system's id into course_field_value table.
						$res = SessionManager::update_session_extra_field_value($id_session, $extra_field_name, $extra_field_value);
					}
				}
				$results[] = $id_session;
				continue;
			}
		}
	} // end principal foreach

	$count_results = count($results);
	$output = array();
	for($i = 0; $i < $count_results; $i++) {
		$output[] = array('original_session_id_value' => $orig_session_id_value[$i], 'result' => $results[$i]);
	}

	return $output;
}

/* Register DokeosWSEditSession function */
// Register the data structures used by the service
$server->wsdl->addComplexType(
	'editSessionParams',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'name' => array('name' => 'name', 'type' => 'xsd:string'),
		'year_start' => array('name' => 'year_start', 'type' => 'xsd:string'),
		'month_start' => array('name' => 'month_start', 'type' => 'xsd:string'),
		'day_start' => array('name' => 'day_start', 'type' => 'xsd:string'),
		'year_end' => array('name' => 'year_end', 'type' => 'xsd:string'),
		'month_end' => array('name' => 'month_end', 'type' => 'xsd:string'),
		'day_end' => array('name' => 'day_end', 'type' => 'xsd:string'),
		'nb_days_access_before' => array('name' => 'nb_days_access_before', 'type' => 'xsd:string'),
		'nb_days_access_after' => array('name' => 'nb_days_access_after', 'type' => 'xsd:string'),
		'nolimit' => array('name' => 'nolimit', 'type' => 'xsd:string'),
		'user_id' => array('name' => 'user_id', 'type' => 'xsd:string'),
		'original_session_id_name' => array('name' => 'original_session_id_name', 'type' => 'xsd:string'),
		'original_session_id_value' => array('name' => 'original_session_id_value', 'type' => 'xsd:string'),
		'extra' => array('name' => 'extra', 'type' => 'tns:extrasList')
	)
);

$server->wsdl->addComplexType(
'editSessionParamsList',
'complexType',
'array',
'',
'SOAP-ENC:Array',
array(),
array(array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:editSessionParams[]')),
'tns:editSessionParams'
);

$server->wsdl->addComplexType(
	'editSession',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'sessions' => array('name' => 'sessions', 'type' => 'tns:editSessionParamsList'),
		'secret_key' => array('name' => 'secret_key', 'type' => 'xsd:string')
	)
);

// Prepare output params, in this case will return an array
$server->wsdl->addComplexType(
'result_editSession',
'complexType',
'struct',
'all',
'',
array(
		'original_session_id_value' => array('name' => 'original_session_id_value', 'type' => 'xsd:string'),
		'result' => array('name' => 'result', 'type' => 'xsd:string')
     )
);

$server->wsdl->addComplexType(
'results_editSession',
'complexType',
'array',
'',
'SOAP-ENC:Array',
array(),
array(array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:result_editSession[]')),
'tns:result_editSession'
);


// Register the method to expose
$server->register('DokeosWSEditSession',		// method name
	array('editSession' => 'tns:editSession'),	// input parameters
	array('return' => 'tns:results_editSession'),				// output parameters
	'urn:WSRegistration',						// namespace
	'urn:WSRegistration#DokeosWSEditSession',	// soapaction
	'rpc',										// style
	'encoded',									// use
	'This service edits a session'				// documentation
);

// define the method DokeosWSEditSession
function DokeosWSEditSession($params) {

	global $_user, $_configuration;

	$secret_key = $params['secret_key'];
	$security_key = $_SERVER['REMOTE_ADDR'].$_configuration['security_key'];

	if (!api_is_valid_secret_key($secret_key,$security_key)) {
		return -1; // The secret key is incorrect.
	}

	$tbl_user		= Database::get_main_table(TABLE_MAIN_USER);
	$tbl_session	= Database::get_main_table(TABLE_MAIN_SESSION);
	$t_sf = Database::get_main_table(TABLE_MAIN_SESSION_FIELD);
	$t_sfv = Database::get_main_table(TABLE_MAIN_SESSION_FIELD_VALUES);

	$sessions_params = $params['sessions'];
	$results = array();
	$orig_session_id_value = array();

	foreach ($sessions_params as $session_param) {

		$name = trim($session_param['name']);
		$year_start = intval($session_param['year_start']);
		$month_start = intval($session_param['month_start']);
		$day_start = intval($session_param['day_start']);
		$year_end = intval($session_param['year_end']);
		$month_end = intval($session_param['month_end']);
		$day_end = intval($session_param['day_end']);
		$nb_days_acess_before = intval($session_param['nb_days_access_before']);
		$nb_days_acess_after = intval($session_param['nb_days_access_after']);
		$original_session_id_value = $session_param['original_session_id_value'];
		$original_session_id_name = $session_param['original_session_id_name'];
		$orig_session_id_value[] = $original_session_id_value;
		$coach_username = $session_param['coach_username'];
		$nolimit = $session_param['nolimit'];
		$id_coach = $session_param['user_id'];
		$extra_list = $session_param['extra'];
		// Get session id from original session id
		$sql = "SELECT session_id FROM $t_sf sf,$t_sfv sfv WHERE sfv.field_id=sf.id AND field_variable='$original_session_id_name' AND field_value='$original_session_id_value'";
		$res = Database::query($sql, __FILE__, __LINE__);
		$row = Database::fetch_row($res);

		$id = intval($row[0]);

		if (Database::num_rows($res) < 1) {
			$results[] = 0;
			continue;
		}

		if (empty($nolimit)) {
			$date_start="$year_start-".(($month_start < 10)?"0$month_start":$month_start)."-".(($day_start < 10)?"0$day_start":$day_start);
			$date_end="$year_end-".(($month_end < 10)?"0$month_end":$month_end)."-".(($day_end < 10)?"0$day_end":$day_end);
		} else {
			$date_start="000-00-00";
			$date_end="000-00-00";
		}
		if (empty($name)) {
			$results[] = 0; //SessionNameIsRequired
			continue;
		} elseif (empty($nolimit) && (!$month_start || !$day_start || !$year_start || !checkdate($month_start, $day_start, $year_start))) {
			$results[] = 0; //InvalidStartDate
			continue;
		} elseif (empty($nolimit) && (!$month_end || !$day_end || !$year_end || !checkdate($month_end, $day_end, $year_end))) {
			$results[] = 0; //InvalidEndDate
			continue;
		} elseif (empty($nolimit) && $date_start >= $date_end) {
			$results[] = 0; //StartDateShouldBeBeforeEndDate
			continue;
		} else {
			$sql = "UPDATE $tbl_session SET " .
					"name='".addslashes($name)."', " .
					"date_start='".$date_start."', " .
					"date_end='".$date_end."', " .
					"id_coach='".		$id_coach."', " .
					"session_admin_id='".		intval($_user['user_id'])."', " .
					"nb_days_access_before_beginning='".		$nb_days_acess_before."', " .
					"nb_days_access_after_end='".		$nb_days_acess_after."'" .
					" WHERE id='".$id."'";
			Database::query($sql, __FILE__, __LINE__);
			$id_session = Database::get_last_insert_id();

			if (is_array($extra_list) && count($extra_list) > 0) {
				foreach ($extra_list as $extra) {
					$extra_field_name = $extra['field_name'];
					$extra_field_value = $extra['field_value'];
					// Save the external system's id into session_field_value table.
					$res = SessionManager::update_session_extra_field_value($id, $extra_field_name, $extra_field_value);
				}
			}

			$results[] = 1;
			continue;
		}

	} // end principal foreach

	$count_results = count($results);
	$output = array();
	for($i = 0; $i < $count_results; $i++) {
		$output[] = array('original_session_id_value' => $orig_session_id_value[$i], 'result' => $results[$i]);
	}

	return $output;
}

/* Register DokeosWSDeleteSession function */
$server->wsdl->addComplexType(
	'deleteSessionParams',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'original_session_id_value' => array('name' => 'original_session_id_value', 'type' => 'xsd:string'),
		'original_session_id_name' => array('name' => 'original_session_id_name', 'type' => 'xsd:string')
	)
);

$server->wsdl->addComplexType(
'deleteSessionParamsList',
'complexType',
'array',
'',
'SOAP-ENC:Array',
array(),
array(array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:deleteSessionParams[]')),
'tns:deleteSessionParams'
);

// Register the data structures used by the service
$server->wsdl->addComplexType(
	'deleteSession',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'sessions' => array('name' => 'sessions', 'type' => 'tns:deleteSessionParamsList'),
		'secret_key' => array('name' => 'secret_key', 'type' => 'xsd:string')
	)
);

// Prepare output params, in this case will return an array
$server->wsdl->addComplexType(
'result_deleteSession',
'complexType',
'struct',
'all',
'',
array(
		'original_session_id_value' => array('name' => 'original_session_id_value', 'type' => 'xsd:string'),
		'result' => array('name' => 'result', 'type' => 'xsd:string')
     )
);

$server->wsdl->addComplexType(
'results_deleteSession',
'complexType',
'array',
'',
'SOAP-ENC:Array',
array(),
array(array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:result_deleteSession[]')),
'tns:result_deleteSession'
);

$server->register('DokeosWSDeleteSession',			// method name
	array('deleteSession' => 'tns:deleteSession'),	// input parameters
	array('return' => 'tns:results_deleteSession'),	// output parameters
	'urn:WSRegistration',							// namespace
	'urn:WSRegistration#DokeosWSDeleteSession',		// soapaction
	'rpc',											// style
	'encoded',										// use
	'This service deletes a session '				// documentation
);

// define the method DokeosWSDeleteSession
function DokeosWSDeleteSession($params) {

	global $_configuration;

	$secret_key = $params['secret_key'];
	$security_key = $_SERVER['REMOTE_ADDR'].$_configuration['security_key'];

	if (!api_is_valid_secret_key($secret_key, $security_key)) {
		return -1; // The secret key is incorrect.
	}

	$t_sf = Database::get_main_table(TABLE_MAIN_SESSION_FIELD);
	$t_sfv = Database::get_main_table(TABLE_MAIN_SESSION_FIELD_VALUES);
	$tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
	$tbl_session_rel_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
	$tbl_session_rel_course_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
	$tbl_session_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_USER);
	$tbl_user = Database::get_main_table(TABLE_MAIN_USER);

	$session_params = $params['sessions'];
	$results = array();
	$orig_session_id_value = array();

	foreach ($session_params as $session_param) {

		$original_session_id_value = $session_param['original_session_id_value'];
		$original_session_id_name = $session_param['original_session_id_name'];
		$orig_session_id_value[] = $original_session_id_name;
		// get session id from original session id
		$sql = "SELECT session_id FROM $t_sf sf,$t_sfv sfv WHERE sfv.field_id=sf.id AND field_variable='$original_session_id_name' AND field_value='$original_session_id_value'";
		$res = @Database::query($sql, __FILE__, __LINE__);
		$row = Database::fetch_row($res);

		$idChecked = intval($row[0]);
		if (empty($idChecked)) {
			$results[] = 0;
			continue;
		}

		$session_ids[] = $idChecked;

		$sql_session = "DELETE FROM $tbl_session WHERE id = '$idChecked'";
		@Database::query($sql_session, __FILE__, __LINE__);
		$sql_session_rel_course = "DELETE FROM $tbl_session_rel_course WHERE id_session = '$idChecked'";
		@Database::query($sql_session_rel_course, __FILE__, __LINE__);
		$sql_session_rel_course_rel_user = "DELETE FROM $tbl_session_rel_course_rel_user WHERE id_session = '$idChecked'";
		@Database::query($sql_session_rel_course_rel_user, __FILE__, __LINE__);
		$sql_session_rel_course = "DELETE FROM $tbl_session_rel_user WHERE id_session = '$idChecked'";
		@Database::query($sql_session_rel_course, __FILE__, __LINE__);
		$results[] = 1;
		continue;
	}

	// Get fields id from all extra fields about a given session id
	$cad_session_ids = implode(',', $session_ids);

	$sql = "SELECT distinct field_id FROM $t_sfv  WHERE session_id IN ($cad_session_ids)";
	$res_field_ids = @Database::query($sql, __FILE__, __LINE__);

	while($row_field_id = Database::fetch_row($res_field_ids)){
		$field_ids[] = $row_field_id[0];
	}

	//delete from table_session_field_value from a given session_id
	foreach ($session_ids as $session_id) {
		$sql_session_field_value = "DELETE FROM $t_sfv WHERE session_id = '$session_id'";
		@Database::query($sql_session_field_value, __FILE__, __LINE__);
	}

	$sql = "SELECT distinct field_id FROM $t_sfv";
	$res_field_all_ids = @Database::query($sql, __FILE__, __LINE__);

	while($row_field_all_id = Database::fetch_row($res_field_all_ids)){
		$field_all_ids[] = $row_field_all_id[0];
	}

	foreach ($field_ids as $field_id) {
		// Check whether field id is used into table field value.
		if (in_array($field_id,$field_all_ids)) {
			continue;
		} else {
			$sql_session_field = "DELETE FROM $t_sf WHERE id = '$field_id'";
			Database::query($sql_session_field, __FILE__, __LINE__);
		}
	}

	// Preparing output.
	$count_results = count($results);
	$output = array();
	for($i = 0; $i < $count_results; $i++) {
		$output[] = array('original_session_id_value' => $orig_session_id_value[$i], 'result' => $results[$i]);
	}

	return $output;
}



/* Register DokeosWSSubscribeUserToCourse function */
// Register the data structures used by the service
$server->wsdl->addComplexType(
'originalUsersList',
'complexType',
'array',
'',
'SOAP-ENC:Array',
array(),
array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType' => 'string[]')),'xsd:string'
);

$server->wsdl->addComplexType(
	'subscribeUserToCourseParams',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'original_user_id_values' => array('name' => 'original_user_id_values', 'type' => 'tns:originalUsersList'),
		'original_user_id_name' => array('name' => 'original_user_id_name', 'type' => 'xsd:string'),
		'original_course_id_value' => array('name' => 'original_course_id_value', 'type' => 'xsd:string'),
		'original_course_id_name' => array('name' => 'original_course_id_value', 'type' => 'xsd:string')
	)
);

$server->wsdl->addComplexType(
'subscribeUserToCourseParamsList',
'complexType',
'array',
'',
'SOAP-ENC:Array',
array(),
array(array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:subscribeUserToCourseParams[]')),
'tns:subscribeUserToCourseParams'
);

$server->wsdl->addComplexType(
	'subscribeUserToCourse',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'userscourses' => array('name' => 'userscourses', 'type' => 'tns:subscribeUserToCourseParamsList'),
		'secret_key' => array('name' => 'secret_key', 'type' => 'xsd:string')
	)
);

// Prepare output params, in this case will return an array
$server->wsdl->addComplexType(
'result_subscribeUserToCourse',
'complexType',
'struct',
'all',
'',
array(
		'original_user_id_value' => array('name' => 'original_user_id_value', 'type' => 'xsd:string'),
		'original_course_id_value' => array('name' => 'original_course_id_value', 'type' => 'xsd:string'),
		'result' => array('name' => 'result', 'type' => 'xsd:string')
     )
);

$server->wsdl->addComplexType(
'results_subscribeUserToCourse',
'complexType',
'array',
'',
'SOAP-ENC:Array',
array(),
array(array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:result_subscribeUserToCourse[]')),
'tns:result_subscribeUserToCourse'
);


// Register the method to expose
$server->register('DokeosWSSubscribeUserToCourse',					// method name
	array('subscribeUserToCourse' => 'tns:subscribeUserToCourse'),	// input parameters
	array('return' => 'tns:results_subscribeUserToCourse'),			// output parameters
	'urn:WSRegistration',											// namespace
	'urn:WSRegistration#DokeosWSSubscribeUserToCourse',				// soapaction
	'rpc',															// style
	'encoded',														// use
	'This service subscribes a user to a course' 					// documentation
);

// define the method DokeosWSSubscribeUserToCourse
function DokeosWSSubscribeUserToCourse($params) {

    global $_configuration;

    $secret_key = $params['secret_key'];
    $security_key = $_SERVER['REMOTE_ADDR'].$_configuration['security_key'];

	if (!api_is_valid_secret_key($secret_key, $security_key)) {
		return -1; // The secret key is incorrect.
	}

    $user_table = Database :: get_main_table(TABLE_MAIN_USER);
	$t_uf = Database::get_main_table(TABLE_MAIN_USER_FIELD);
	$t_ufv = Database::get_main_table(TABLE_MAIN_USER_FIELD_VALUES);
	$course_table = Database :: get_main_table(TABLE_MAIN_COURSE);
	$t_cfv 			= Database::get_main_table(TABLE_MAIN_COURSE_FIELD_VALUES);
	$table_field 	= Database::get_main_table(TABLE_MAIN_COURSE_FIELD);
	$course_user_table = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
	$location_table = Database :: get_main_table(MAIN_LOCATION_TABLE);
	$user_role_table = Database :: get_main_table(MAIN_USER_ROLE_TABLE);
	$tbl_session_rel_course_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
	$tbl_session_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_USER);
	$tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);

    $userscourses_params = $params['userscourses'];
	$results = array();
	$orig_user_id_value = array();
	$orig_course_id_value = array();
	foreach ($userscourses_params as $usercourse_param) {

		$original_user_id_values = $usercourse_param['original_user_id_values'];
	    $original_user_id_name = $usercourse_param['original_user_id_name'];
	    $original_course_id_value = $usercourse_param['original_course_id_value'];
	    $original_course_id_name = $usercourse_param['original_course_id_name'];
	    $orig_course_id_value[] = $original_course_id_value;

		$status = STUDENT;

	    // Get user id from original user id
	    $usersList = array();
	    foreach ($original_user_id_values as $row_original_user_list) {
	 		$sql_user = "SELECT user_id FROM $t_uf uf,$t_ufv ufv WHERE ufv.field_id=uf.id AND field_variable='$original_user_id_name' AND field_value = '$row_original_user_list'";
	 		// return $sql_user;
	 		$res_user = Database::query($sql_user, __FILE__, __LINE__);
	 		$row_user = Database::fetch_row($res_user);
	 		if (empty($row_user[0])) {
		    	continue; // user_id doesn't exist.
		    } else {
				$sql = "SELECT user_id FROM $user_table WHERE user_id ='".$row_user[0]."' AND active= '0'";
				$resu = Database::query($sql, __FILE__, __LINE__);
				$r_check_user = Database::fetch_row($resu);
				if (!empty($r_check_user[0])) {
					continue; // user_id is not active.
				}
		    }
		    $usersList[] = $row_user[0];
	 	}

	    $orig_user_id_value[] = implode(',', $usersList);
	    // Get course code from original course id

		$sql_course = "SELECT course_code FROM $table_field cf,$t_cfv cfv WHERE cfv.field_id=cf.id AND field_variable='$original_course_id_name' AND field_value='$original_course_id_value'";
		$res_course = Database::query($sql_course, __FILE__, __LINE__);
		$row_course = Database::fetch_row($res_course);

		$course_code = $row_course[0];

		if (empty($course_code)) {
			$results[] = 0; // original_course_id_value doesn't exist
			continue;
		} else {
			$sql = "SELECT code FROM $course_table WHERE code ='$course_code' AND visibility = '0'";
			$resc = Database::query($sql, __FILE__, __LINE__);
			$r_check_code = Database::fetch_row($resc);
			if (!empty($r_check_code[0])) {
				$results[] = 0; // this code is not active
				continue;
			}
		}

		$status = ($status == STUDENT || $status == COURSEMANAGER) ? $status : STUDENT;
		$role_id = ($status == COURSEMANAGER) ? COURSE_ADMIN : NORMAL_COURSE_MEMBER;
	        $course_code = Database::escape_string($course_code);

		if (empty ($usersList) || empty ($course_code)) {
			$results[] = 0;
			continue;
		} else {

			foreach($usersList as $user_id) {
				// previously check if the user are already registered on the platform
					$handle = @Database::query("SELECT status FROM ".$user_table."
															WHERE user_id = '$user_id' ", __FILE__, __LINE__);
				if (Database::num_rows($handle) == 0){
					//$results[] = 7; // the user isn't registered to the platform
					continue;
				} else {
					//check if user isn't already subscribed to the course
					$handle = @Database::query("SELECT * FROM ".$course_user_table."
																		WHERE user_id = '$user_id'
																		AND course_code ='$course_code'", __FILE__, __LINE__);
					if (Database::num_rows($handle) > 0) {
						//$results[] = 8; // the user is already subscribed to the course
						continue;
					} else {

						$course_sort = CourseManager :: userCourseSort($user_id,$course_code);
						$add_course_user_entry_sql = "INSERT INTO ".$course_user_table."
											SET course_code = '$course_code',
											user_id    = '$user_id',
											status    = '".$status."',
											sort  =   '". ($course_sort)."'";
						$result = @Database::query($add_course_user_entry_sql, __FILE__, __LINE__);

					}
				}
			} // end foreach usersList
		}
		$results[] = 1;
		continue;
	} // end principal foreach

    $count_results = count($results);
	$output = array();
	for($i = 0; $i < $count_results; $i++) {
		$output[] = array('original_user_id_value' => $orig_user_id_value[$i], 'original_course_id_value' => $orig_course_id_value[$i], 'result' => $results[$i]);
	}

	return $output;
}

/* Register DokeosWSUnsubscribeUserFromCourse function */
// Register the data structures used by the service
$server->wsdl->addComplexType(
	'unsuscribeUserFromCourseParams',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'original_user_id_values' => array('name' => 'original_user_id_values', 'type' => 'tns:originalUsersList'),
		'original_user_id_name' => array('name' => 'original_user_id_name', 'type' => 'xsd:string'),
		'original_course_id_value' => array('name' => 'original_course_id_value', 'type' => 'xsd:string'),
		'original_course_id_name' => array('name' => 'original_course_id_name', 'type' => 'xsd:string'),
	)
);

$server->wsdl->addComplexType(
'unsuscribeUserFromCourseParamsList',
'complexType',
'array',
'',
'SOAP-ENC:Array',
array(),
array(array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:unsuscribeUserFromCourseParams[]')),
'tns:unsuscribeUserFromCourseParams'
);

$server->wsdl->addComplexType(
	'unsuscribeUserFromCourse',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'userscourses' => array('name' => 'userscourses', 'type' => 'tns:unsuscribeUserFromCourseParamsList'),
		'secret_key' => array('name' => 'secret_key', 'type' => 'xsd:string')
	)
);

// Prepare output params, in this case will return an array
$server->wsdl->addComplexType(
'result_unsuscribeUserFromCourse',
'complexType',
'struct',
'all',
'',
array(
		'original_user_id_values' => array('name' => 'original_user_id_values', 'type' => 'xsd:string'),
		'original_course_id_value' => array('name' => 'original_course_id_value', 'type' => 'xsd:string'),
		'result' => array('name' => 'result', 'type' => 'xsd:string')
     )
);

$server->wsdl->addComplexType(
'results_unsuscribeUserFromCourse',
'complexType',
'array',
'',
'SOAP-ENC:Array',
array(),
array(array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:result_unsuscribeUserFromCourse[]')),
'tns:result_unsuscribeUserFromCourse'
);

// Register the method to expose
$server->register('DokeosWSUnsubscribeUserFromCourse',					// method name
	array('unsuscribeUserFromCourse' => 'tns:unsuscribeUserFromCourse'),// input parameters
	array('return' => 'tns:results_unsuscribeUserFromCourse'),			// output parameters
	'urn:WSRegistration',												// namespace
	'urn:WSRegistration#DokeosWSUnsubscribeUserFromCourse',				// soapaction
	'rpc',																// style
	'encoded',															// use
	'This service unsubscribes a user from a course' 					// documentation
);

// define the method DokeosWSUnsubscribeUserFromCourse
function DokeosWSUnsubscribeUserFromCourse($params) {
	global $_configuration;
	$secret_key = $params['secret_key'];
	$security_key = $_SERVER['REMOTE_ADDR'].$_configuration['security_key'];

	if (!api_is_valid_secret_key($secret_key, $security_key)) {
		return -1; // The secret key is incorrect.
	}

	$user_table = Database::get_main_table(TABLE_MAIN_USER);
	$t_uf = Database::get_main_table(TABLE_MAIN_USER_FIELD);
	$t_ufv = Database::get_main_table(TABLE_MAIN_USER_FIELD_VALUES);
	$table_course 	= Database :: get_main_table(TABLE_MAIN_COURSE);
    $table_course_user = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
	$t_cfv 			= Database::get_main_table(TABLE_MAIN_COURSE_FIELD_VALUES);
	$table_field 	= Database::get_main_table(TABLE_MAIN_COURSE_FIELD);

	$userscourses_params = $params['userscourses'];
	$results = array();
	$orig_user_id_value = array();
	$orig_course_id_value = array();
	foreach($userscourses_params as $usercourse_param) {

		$original_user_id_values 	= $usercourse_param['original_user_id_values'];
	    $original_user_id_name 		= $usercourse_param['original_user_id_name'];
	    $original_course_id_value 	= $usercourse_param['original_course_id_value'];
	    $original_course_id_name 	= $usercourse_param['original_course_id_name'];
	    $orig_course_id_value[] = $original_course_id_value;

		// Get user id from original user id
	    $usersList = array();
	    foreach ($original_user_id_values as $row_original_user_list) {
	 		$sql_user = "SELECT user_id FROM $t_uf uf,$t_ufv ufv WHERE ufv.field_id=uf.id AND field_variable='$original_user_id_name' AND field_value = '$row_original_user_list'";
	 		//return $sql_user;
	 		$res_user = Database::query($sql_user, __FILE__, __LINE__);
	 		$row_user = Database::fetch_row($res_user);
	 		if (empty($row_user[0])) {
		    	continue; // user_id doesn't exist.
		    } else {
				$sql = "SELECT user_id FROM $user_table WHERE user_id ='".$row_user[0]."' AND active= '0'";
				$resu = Database::query($sql, __FILE__, __LINE__);
				$r_check_user = Database::fetch_row($resu);
				if (!empty($r_check_user[0])) {
					continue; // user_id is not active.
				}
		    }
		    $usersList[] = $row_user[0];
	 	}

	    $orig_user_id_value[] = implode(',',$usersList);

	    // Get course code from original course id

		$sql_course 	= "SELECT course_code	FROM $table_field cf,$t_cfv cfv WHERE cfv.field_id=cf.id AND field_variable='$original_course_id_name' AND field_value='$original_course_id_value'";
		$res_course 	= Database::query($sql_course, __FILE__, __LINE__);
		$row_course 	= Database::fetch_row($res_course);

		$course_code = $row_course[0];

		if (empty($course_code)) {
			$results[] = 0;
			continue;
		} else {
			$sql = "SELECT code FROM $table_course WHERE code ='$course_code' AND visibility = '0'";
			$resul = Database::query($sql, __FILE__, __LINE__);
			$r_check_code = Database::fetch_row($resul);
			if (!empty($r_check_code[0])) {
				$results[] = 0;
				continue;
			}
		}

		if (count($usersList) == 0) {
			$results[] = 0;
			continue;
		}

		foreach($usersList as $user_id) {
		    $course_code = Database::escape_string($course_code);
			$sql = "DELETE FROM $table_course_user WHERE user_id = '$user_id' AND course_code = '".$course_code."'";
			Database::query($sql, __FILE__, __LINE__);
			$return = Database::affected_rows();
		}
		$results[] = 1;
		continue;
	} // end principal foreach

	$count_results = count($results);
	$output = array();
	for($i = 0; $i < $count_results; $i++) {
		$output[] = array('original_user_id_values' => $orig_user_id_value[$i],'original_course_id_value' => $orig_course_id_value[$i], 'result' => $results[$i]);
	}

	return $output;
}

/* Register DokeosWSSuscribeUsersToSession function */
// Register the data structures used by the service
$server->wsdl->addComplexType(
	'subscribeUsersToSessionParams',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'original_user_id_values' => array('name' => 'original_user_id_values', 'type' => 'tns:originalUsersList'),
		'original_user_id_name' => array('name' => 'original_user_id_name', 'type' => 'xsd:string'),
		'original_session_id_value' => array('name' => 'original_session_id_value', 'type' => 'xsd:string'),
		'original_session_id_name' => array('name' => 'original_session_id_name', 'type' => 'xsd:string')
	)
);

$server->wsdl->addComplexType(
'subscribeUsersToSessionParamsList',
'complexType',
'array',
'',
'SOAP-ENC:Array',
array(),
array(array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:subscribeUsersToSessionParams[]')),
'tns:subscribeUsersToSessionParams'
);

$server->wsdl->addComplexType(
	'subscribeUsersToSession',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'userssessions' => array('name' => 'userssessions', 'type' => 'tns:subscribeUsersToSessionParamsList'),
		'secret_key' => array('name' => 'secret_key', 'type' => 'xsd:string')
	)
);

// Prepare output params, in this case will return an array.
$server->wsdl->addComplexType(
'result_subscribeUsersToSession',
'complexType',
'struct',
'all',
'',
array(
		'original_user_id_values' => array('name' => 'original_user_id_values', 'type' => 'xsd:string'),
		'original_session_id_value' => array('name' => 'original_session_id_value', 'type' => 'xsd:string'),
		'result' => array('name' => 'result', 'type' => 'xsd:string')
     )
);

$server->wsdl->addComplexType(
'results_subscribeUsersToSession',
'complexType',
'array',
'',
'SOAP-ENC:Array',
array(),
array(array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:result_subscribeUsersToSession[]')),
'tns:result_subscribeUsersToSession'
);

// Register the method to expose
$server->register('DokeosWSSuscribeUsersToSession',						// method name
	array('subscribeUsersToSession' => 'tns:subscribeUsersToSession'),	// input parameters
	array('return' => 'tns:results_subscribeUsersToSession'),			// output parameters
	'urn:WSRegistration',												// namespace
	'urn:WSRegistration#DokeosWSSuscribeUsersToSession',				// soapaction
	'rpc',																// style
	'encoded',															// use
	'This service subscribes a user to a session' 						// documentation
);

// define the method DokeosWSSuscribeUsersToSession
function DokeosWSSuscribeUsersToSession($params){

 	global $_configuration;

 	$secret_key = $params['secret_key'];
 	$security_key = $_SERVER['REMOTE_ADDR'].$_configuration['security_key'];

	if (!api_is_valid_secret_key($secret_key, $security_key)) {
		return -1; // The secret key is incorrect.
	}

	$user_table = Database::get_main_table(TABLE_MAIN_USER);
 	$t_uf = Database::get_main_table(TABLE_MAIN_USER_FIELD);
	$t_ufv = Database::get_main_table(TABLE_MAIN_USER_FIELD_VALUES);
	$t_sf = Database::get_main_table(TABLE_MAIN_SESSION_FIELD);
	$t_sfv = Database::get_main_table(TABLE_MAIN_SESSION_FIELD_VALUES);
	$tbl_session_rel_course				= Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
	$tbl_session_rel_course_rel_user	= Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
   	$tbl_session_rel_user 				= Database::get_main_table(TABLE_MAIN_SESSION_USER);
   	$tbl_session						= Database::get_main_table(TABLE_MAIN_SESSION);

   	$userssessions_params = $params['userssessions'];
	$results = array();
	$orig_user_id_value = array();
	$orig_session_id_value = array();
	foreach($userssessions_params as $usersession_params) {

	   	$original_session_id_value = $usersession_params['original_session_id_value'];
		$original_session_id_name = $usersession_params['original_session_id_name'];
		$original_user_id_name = $usersession_params['original_user_id_name'];
		$original_user_id_values = $usersession_params['original_user_id_values'];
	   	$orig_session_id_value[] = $original_session_id_value;
		// get session id from original session id
		$sql_session = "SELECT session_id FROM $t_sf sf,$t_sfv sfv WHERE sfv.field_id=sf.id AND field_variable='$original_session_id_name' AND field_value='$original_session_id_value'";
		$res_session = Database::query($sql_session, __FILE__, __LINE__);
		$row_session = Database::fetch_row($res_session);

	 	$id_session = $row_session[0];

	 	if (Database::num_rows($res_session) < 1) {
			$results[] = 0;
			continue;
		}

	 	$usersList = array();
	 	foreach ($original_user_id_values as $row_original_user_list) {
	 		$sql_user = "SELECT user_id FROM $t_uf uf,$t_ufv ufv WHERE ufv.field_id=uf.id AND field_variable='$original_user_id_name' AND field_value = '$row_original_user_list'";
	 		$res_user = Database::query($sql_user, __FILE__, __LINE__);
	 		$row_user = Database::fetch_row($res_user);
	 		if (empty($row_user[0])) {
		    	continue; // user_id doesn't exist.
		    } else {
				$sql = "SELECT user_id FROM $user_table WHERE user_id ='".$row_user[0]."' AND active= '0'";
				$resu = Database::query($sql, __FILE__, __LINE__);
				$r_check_user = Database::fetch_row($resu);
				if (!empty($r_check_user[0])) {
					continue; // user_id is not active.
				}
		    }
		    $usersList[] = $row_user[0];
	 	}

		if (empty($usersList)) {
			$results[] = 0;
			continue;
		}

	 	$orig_user_id_value[] = implode(',', $usersList);

	  	if ($id_session!= strval(intval($id_session))) {
	  		$results[] = 0;
			continue;
	  	}

	   	$sql = "SELECT id_user FROM $tbl_session_rel_user WHERE id_session='$id_session'";
		$result = Database::query($sql, __FILE__, __LINE__);
		$existingUsers = array();
		while($row = Database::fetch_array($result)){
			$existingUsers[] = $row['id_user'];
		}
		$sql = "SELECT course_code FROM $tbl_session_rel_course WHERE id_session='$id_session'";
		$result=Database::query($sql, __FILE__, __LINE__);
		$CourseList = array();
		while($row = Database::fetch_array($result)) {
			$CourseList[] = $row['course_code'];
		}

		foreach ($CourseList as $enreg_course) {
			// For each course in the session...
			$nbr_users = 0;
		    $enreg_course = Database::escape_string($enreg_course);

			// insert new users into session_rel_course_rel_user and ignore if they already exist
			foreach ($usersList as $enreg_user) {
				if(!in_array($enreg_user, $existingUsers)) {
		            $enreg_user = Database::escape_string($enreg_user);
					$insert_sql = "INSERT IGNORE INTO $tbl_session_rel_course_rel_user(id_session,course_code,id_user) VALUES('$id_session','$enreg_course','$enreg_user')";
					Database::query($insert_sql, __FILE__, __LINE__);
						if (Database::affected_rows()) {
						$nbr_users++;
					}
				}
			}
			// count users in this session-course relation
			$sql = "SELECT COUNT(id_user) as nbUsers FROM $tbl_session_rel_course_rel_user WHERE id_session='$id_session' AND course_code='$enreg_course'";
			$rs = Database::query($sql, __FILE__, __LINE__);
			list($nbr_users) = Database::fetch_array($rs);
			// update the session-course relation to add the users total
			$update_sql = "UPDATE $tbl_session_rel_course SET nbr_users=$nbr_users WHERE id_session='$id_session' AND course_code='$enreg_course'";
			Database::query($update_sql, __FILE__, __LINE__);
		}

		// insert missing users into session
		$nbr_users = 0;
		foreach ($usersList as $enreg_user) {
	        $enreg_user = Database::escape_string($enreg_user);
			$nbr_users++;
			$insert_sql = "INSERT IGNORE INTO $tbl_session_rel_user(id_session, id_user) VALUES('$id_session','$enreg_user')";
			Database::query($insert_sql, __FILE__, __LINE__);
		}
		// update number of users in the session
		$nbr_users = count($usersList);
		$update_sql = "UPDATE $tbl_session SET nbr_users= $nbr_users WHERE id='$id_session' ";
		Database::query($update_sql, __FILE__, __LINE__);
		$return = Database::affected_rows();
		$results[] = 1;
		continue;

	} // end principal foreach

	$count_results = count($results);
	$output = array();
	for($i = 0; $i < $count_results; $i++) {
		$output[] = array('original_user_id_values' => $orig_user_id_value[$i], 'original_session_id_value' => $orig_session_id_value[$i], 'result' => $results[$i]);
	}

	return $output;
}

/* Register DokeosWSUnsuscribeUsersFromSession function */
// Register the data structures used by the service
$server->wsdl->addComplexType(
	'unsubscribeUsersFromSessionParams',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'original_user_id_values' => array('name' => 'original_user_id_values', 'type' => 'tns:originalUsersList'),
		'original_user_id_name' => array('name' => 'original_user_id_name', 'type' => 'xsd:string'),
		'original_session_id_value' => array('name' => 'original_session_id_value', 'type' => 'xsd:string'),
		'original_session_id_name' => array('name' => 'original_session_id_name', 'type' => 'xsd:string')
	)
);

$server->wsdl->addComplexType(
'unsubscribeUsersFromSessionParamsList',
'complexType',
'array',
'',
'SOAP-ENC:Array',
array(),
array(array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:unsubscribeUsersFromSessionParams[]')),
'tns:unsubscribeUsersFromSessionParams'
);

$server->wsdl->addComplexType(
	'unsubscribeUsersFromSession',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'userssessions' => array('name' => 'userssessions', 'type' => 'tns:subscribeUsersToSessionParamsList'),
		'secret_key' => array('name' => 'secret_key', 'type' => 'xsd:string')
	)
);

// Prepare output params, in this case will return an array
$server->wsdl->addComplexType(
'result_unsubscribeUsersFromSession',
'complexType',
'struct',
'all',
'',
array(
		'original_user_id_values' => array('name' => 'original_user_id_values', 'type' => 'xsd:string'),
		'original_session_id_value' => array('name' => 'original_session_id_value', 'type' => 'xsd:string'),
		'result' => array('name' => 'result', 'type' => 'xsd:string')
     )
);

$server->wsdl->addComplexType(
'results_unsubscribeUsersFromSession',
'complexType',
'array',
'',
'SOAP-ENC:Array',
array(),
array(array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:result_unsubscribeUsersFromSession[]')),
'tns:result_unsubscribeUsersFromSession'
);

// Register the method to expose
$server->register('DokeosWSUnsuscribeUsersFromSession',							// method name
	array('unsubscribeUsersFromSession' => 'tns:unsubscribeUsersFromSession'),	// input parameters
	array('return' => 'tns:results_unsubscribeUsersFromSession'),				// output parameters
	'urn:WSRegistration',														// namespace
	'urn:WSRegistration#DokeosWSUnsuscribeUsersFromSession',					// soapaction
	'rpc',																		// style
	'encoded',																	// use
	'This service unsubscribes a user to a session' 							// documentation
);

// define the method DokeosWSUnsuscribeUsersFromSession
function DokeosWSUnsuscribeUsersFromSession($params) {

 	global $_configuration;

 	$secret_key = $params['secret_key'];
 	$security_key = $_SERVER['REMOTE_ADDR'].$_configuration['security_key'];

	if (!api_is_valid_secret_key($secret_key, $security_key)) {
		return -1; // The secret key is incorrect.
	}

	$user_table = Database::get_main_table(TABLE_MAIN_USER);
 	$t_uf = Database::get_main_table(TABLE_MAIN_USER_FIELD);
	$t_ufv = Database::get_main_table(TABLE_MAIN_USER_FIELD_VALUES);
	$t_sf = Database::get_main_table(TABLE_MAIN_SESSION_FIELD);
	$t_sfv = Database::get_main_table(TABLE_MAIN_SESSION_FIELD_VALUES);
	$tbl_session_rel_course				= Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
	$tbl_session_rel_course_rel_user	= Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
   	$tbl_session_rel_user 				= Database::get_main_table(TABLE_MAIN_SESSION_USER);
   	$tbl_session						= Database::get_main_table(TABLE_MAIN_SESSION);

   	$userssessions_params = $params['userssessions'];
	$results = array();
	$orig_user_id_value = array();
	$orig_session_id_value = array();

	foreach ($userssessions_params as $usersession_params) {

	   	$original_session_id_value = $usersession_params['original_session_id_value'];
		$original_session_id_name = $usersession_params['original_session_id_name'];
		$original_user_id_name = $usersession_params['original_user_id_name'];
		$original_user_id_values = $usersession_params['original_user_id_values'];
	   	$orig_session_id_value[] = $original_session_id_value;
		// get session id from original session id
		$sql_session = "SELECT session_id FROM $t_sf sf,$t_sfv sfv WHERE sfv.field_id=sf.id AND field_variable='$original_session_id_name' AND field_value='$original_session_id_value'";
		$res_session = Database::query($sql_session, __FILE__, __LINE__);
		$row_session = Database::fetch_row($res_session);

	 	$id_session = $row_session[0];

	 	if (Database::num_rows($res_session) < 1) {
			$results[] = 0;
			continue;
		}

	 	$usersList = array();
	 	foreach ($original_user_id_values as $row_original_user_list) {
	 		$sql_user = "SELECT user_id FROM $t_uf uf,$t_ufv ufv WHERE ufv.field_id=uf.id AND field_variable='$original_user_id_name' AND field_value = '$row_original_user_list'";
	 		$res_user = Database::query($sql_user, __FILE__, __LINE__);
	 		$row_user = Database::fetch_row($res_user);
	 		if (empty($row_user[0])) {
		    	continue; // user_id doesn't exist.
		    } else {
				$sql = "SELECT user_id FROM $user_table WHERE user_id ='".$row_user[0]."' AND active= '0'";
				$resu = Database::query($sql, __FILE__, __LINE__);
				$r_check_user = Database::fetch_row($resu);
				if (!empty($r_check_user[0])) {
					continue; // user_id is not active.
				}
		    }
		    $usersList[] = $row_user[0];
	 	}

		if (empty($usersList)) {
			$results[] = 0;
			continue;
		}

	 	$orig_user_id_value[] = implode(',', $usersList);

	  	if ($id_session!= strval(intval($id_session))) {
	  		$results[] = 0;
			continue;
	  	}

	   	$sql = "SELECT id_user FROM $tbl_session_rel_user WHERE id_session='$id_session'";
		$result = Database::query($sql, __FILE__, __LINE__);
		$existingUsers = array();
		while($row = Database::fetch_array($result)){
			$existingUsers[] = $row['id_user'];
		}
		$sql = "SELECT course_code FROM $tbl_session_rel_course WHERE id_session='$id_session'";
		$result = Database::query($sql, __FILE__, __LINE__);
		$CourseList = array();
		while($row = Database::fetch_array($result)) {
			$CourseList[] = $row['course_code'];
		}

		foreach ($CourseList as $enreg_course) {
			// for each course in the session
			$nbr_users = 0;
		    $enreg_course = Database::escape_string($enreg_course);

			foreach ($existingUsers as $existing_user) {
				if (!in_array($existing_user, $usersList)) {
					$sql = "DELETE FROM $tbl_session_rel_course_rel_user WHERE id_session='$id_session' AND course_code='$enreg_course' AND id_user='$existing_user'";
					Database::query($sql, __FILE__, __LINE__);

					if (Database::affected_rows()) {
						$nbr_users--;
					}
				}
			}
			// Count users in this session-course relation.
			$sql = "SELECT COUNT(id_user) as nbUsers FROM $tbl_session_rel_course_rel_user WHERE id_session='$id_session' AND course_code='$enreg_course'";
			$rs = Database::query($sql, __FILE__, __LINE__);
			list($nbr_users) = Database::fetch_array($rs);
			// update the session-course relation to add the users total
			$update_sql = "UPDATE $tbl_session_rel_course SET nbr_users=$nbr_users WHERE id_session='$id_session' AND course_code='$enreg_course'";
			Database::query($update_sql, __FILE__, __LINE__);
		}

		// Insert missing users into session.

		foreach ($usersList as $enreg_user) {
	        $enreg_user = Database::escape_string($enreg_user);
			$delete_sql = "DELETE FROM $tbl_session_rel_user WHERE id_session = '$id_session' AND id_user ='$enreg_user'";
			Database::query($delete_sql, __FILE__, __LINE__);
			$return = Database::affected_rows();
		}
		$nbr_users = 0;
		$sql = "SELECT nbr_users FROM $tbl_session WHERE id = '$id_session'";
		$res_nbr_users = Database::query($sql, __FILE__, __LINE__);
		$row_nbr_users = Database::fetch_row($res_nbr_users);

		if (Database::num_rows($res_nbr_users) > 0) {
		   $nbr_users = ($row_nbr_users[0] - $return);
		}

		// Update number of users in the session.
		$update_sql = "UPDATE $tbl_session SET nbr_users= $nbr_users WHERE id='$id_session' ";
		Database::query($update_sql, __FILE__, __LINE__);
		$return = Database::affected_rows();
		$results[] = 1;
		continue;

	} // end principal foreach

	$count_results = count($results);
	$output = array();
	for ($i = 0; $i < $count_results; $i++) {
		$output[] = array('original_user_id_values' => $orig_user_id_value[$i], 'original_session_id_value' => $orig_session_id_value[$i], 'result' => $results[$i]);
	}

	return $output;
}

/* Register DokeosWSSuscribeCoursesToSession function */
// Register the data structures used by the service
$server->wsdl->addComplexType(
'originalCoursesList',
'complexType',
'array',
'',
'SOAP-ENC:Array',
array(),
array(array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'string[]')),
'xsd:string'
);

$server->wsdl->addComplexType(
	'subscribeCoursesToSessionParams',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'original_course_id_values' => array('name' => 'original_course_id_values', 'type' => 'tns:originalCoursesList'),
		'original_course_id_name' => array('name' => 'original_course_id_name', 'type' => 'xsd:string'),
		'original_session_id_value' => array('name' => 'original_session_id_value', 'type' => 'xsd:string'),
		'original_session_id_name' => array('name' => 'original_session_id_name', 'type' => 'xsd:string')
	)
);

$server->wsdl->addComplexType(
'subscribeCoursesToSessionParamsList',
'complexType',
'array',
'',
'SOAP-ENC:Array',
array(),
array(array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:subscribeCoursesToSessionParams[]')),
'tns:subscribeCoursesToSessionParams'
);

$server->wsdl->addComplexType(
	'subscribeCoursesToSession',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'coursessessions' => array('name' => 'coursessessions', 'type' => 'tns:subscribeCoursesToSessionParamsList'),
		'secret_key' => array('name' => 'secret_key', 'type' => 'xsd:string')
	)
);

// Prepare output params, in this case will return an array
$server->wsdl->addComplexType(
'result_subscribeCoursesToSession',
'complexType',
'struct',
'all',
'',
array(
		'original_course_id_values' => array('name' => 'original_course_id_values', 'type' => 'xsd:string'),
		'original_session_id_value' => array('name' => 'original_session_id_value', 'type' => 'xsd:string'),
		'result' => array('name' => 'result', 'type' => 'xsd:string')
     )
);

$server->wsdl->addComplexType(
'results_subscribeCoursesToSession',
'complexType',
'array',
'',
'SOAP-ENC:Array',
array(),
array(array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:result_subscribeCoursesToSession[]')),
'tns:result_subscribeCoursesToSession'
);


// Register the method to expose
$server->register('DokeosWSSuscribeCoursesToSession',						// method name
	array('subscribeCoursesToSession' => 'tns:subscribeCoursesToSession'),	// input parameters
	array('return' => 'tns:results_subscribeCoursesToSession'),				// output parameters
	'urn:WSRegistration',													// namespace
	'urn:WSRegistration#DokeosWSSuscribeCoursesToSession',					// soapaction
	'rpc',																	// style
	'encoded',																// use
	'This service subscribes a course to a session' 						// documentation
);

// Define the method DokeosWSSuscribeCoursesToSession
function DokeosWSSuscribeCoursesToSession($params) {

	global $_configuration;

	$secret_key = $params['secret_key'];
	$security_key = $_SERVER['REMOTE_ADDR'].$_configuration['security_key'];

	if (!api_is_valid_secret_key($secret_key, $security_key)) {
		return -1; // The secret key is incorrect.
	}

   	// initialisation
	$tbl_session_rel_course_rel_user	= Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
	$tbl_session						= Database::get_main_table(TABLE_MAIN_SESSION);
	$tbl_session_rel_user				= Database::get_main_table(TABLE_MAIN_SESSION_USER);
	$tbl_session_rel_course				= Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
	$tbl_course							= Database::get_main_table(TABLE_MAIN_COURSE);
	$t_sf 		= Database::get_main_table(TABLE_MAIN_SESSION_FIELD);
	$t_sfv 		= Database::get_main_table(TABLE_MAIN_SESSION_FIELD_VALUES);
 	$t_cfv 		= Database::get_main_table(TABLE_MAIN_COURSE_FIELD_VALUES);
	$t_cf 		= Database::get_main_table(TABLE_MAIN_COURSE_FIELD);

   	$coursessessions_params = $params['coursessessions'];
	$results = array();
	$orig_course_id_value = array();
	$orig_session_id_value = array();
	foreach($coursessessions_params as $coursesession_param) {

		$original_session_id_value = $coursesession_param['original_session_id_value'];
		$original_session_id_name = $coursesession_param['original_session_id_name'];
		$original_course_id_name = $coursesession_param['original_course_id_name'];
		$original_course_id_values = $coursesession_param['original_course_id_values'];
	 	$orig_session_id_value[] = $original_session_id_value;
	 	// get session id from original session id
		$sql_session = "SELECT session_id FROM $t_sf sf,$t_sfv sfv WHERE sfv.field_id=sf.id AND field_variable='$original_session_id_name' AND field_value='$original_session_id_value'";
		$res_session = Database::query($sql_session, __FILE__, __LINE__);
		$row_session = Database::fetch_row($res_session);

	 	$id_session = $row_session[0];

	 	if (empty($id_session)) {
			$results[] = 0;
			continue;
		}

	    // Get course list from row_original_course_id_values
	    $course_list = array();
	 	foreach ($original_course_id_values as $row_original_course_list) {
	 		$sql_course = "SELECT course_code FROM $t_cf cf,$t_cfv cfv WHERE cfv.field_id=cf.id AND field_variable='$original_course_id_name' AND field_value = '$row_original_course_list'";
	 		$res_course = Database::query($sql_course, __FILE__, __LINE__);
	 		$row_course = Database::fetch_row($res_course);
	 		if (empty($row_course[0])) {
		    	continue; // course_code doesn't exist.
		    } else {
				$sql = "SELECT code FROM $tbl_course WHERE code ='".$row_course[0]."' AND visibility = '0'";
				$resu = Database::query($sql, __FILE__, __LINE__);
				$r_check_course = Database::fetch_row($resu);
				if (!empty($r_check_course[0])) {
					continue; // user_id is not active.
				}
		    }
		    $course_list[] = $row_course[0];
	 	}

		if (empty($course_list)) {
			$results[] = 0;
			continue;
		}

	 	$orig_course_id_value[] = implode(',', $course_list);

	 	// Get general coach ID
	 	$sql = "SELECT id_coach FROM $tbl_session WHERE id='$id_session'";
		$id_coach = Database::query($sql, __FILE__, __LINE__);
		$id_coach = Database::fetch_array($id_coach);
		$id_coach = $id_coach[0];

		// get list of courses subscribed to this session
		$sql = "SELECT course_code FROM $tbl_session_rel_course WHERE id_session='$id_session'";

		$rs = Database::query($sql, __FILE__, __LINE__);
		$existingCourses = api_store_result($rs);
		$nbr_courses=count($existingCourses);

		// get list of users subscribed to this session
		$sql="SELECT id_user
			FROM $tbl_session_rel_user
			WHERE id_session = '$id_session'";
		$result=Database::query($sql, __FILE__, __LINE__);
		$user_list=api_store_result($result);

		$course_directory = array();
		// Pass through the courses list we want to add to the session.
		foreach ($course_list as $enreg_course) {
			$enreg_course = Database::escape_string($enreg_course);
			$exists = false;

			// Check if the course we want to add is already subscribed.
			foreach ($existingCourses as $existingCourse) {
				if ($enreg_course == $existingCourse['course_code']) {
					$exists = true;
				}
			}

			if (!$exists) {
				// if the course isn't subscribed yet

				$sql_insert_rel_course= "INSERT INTO $tbl_session_rel_course (id_session,course_code, id_coach) VALUES ('$id_session','$enreg_course','$id_coach')";
				Database::query($sql_insert_rel_course, __FILE__, __LINE__);

				// We add the current course in the existing courses array, to avoid adding another time the current course
				$existingCourses[] = array('course_code' => $enreg_course);
				$nbr_courses++;

				// subscribe all the users from the session to this course inside the session
				$nbr_users = 0;

				foreach ($user_list as $enreg_user) {
					$enreg_user_id = Database::escape_string($enreg_user['id_user']);
					$sql_insert = "INSERT IGNORE INTO $tbl_session_rel_course_rel_user (id_session,course_code,id_user) VALUES ('$id_session','$enreg_course','$enreg_user_id')";
					Database::query($sql_insert, __FILE__, __LINE__);
					if (Database::affected_rows()) {
						$nbr_users++;
					}
				}
				Database::query("UPDATE $tbl_session_rel_course SET nbr_users=$nbr_users WHERE id_session='$id_session' AND course_code='$enreg_course'", __FILE__, __LINE__);

				$sql_directory = "SELECT directory FROM $tbl_course WHERE code = '$enreg_course'";
				$res_directory = Database::query($sql_directory, __FILE__, __LINE__);
				$row_directory = Database::fetch_row($res_directory);
				$course_directory[] = $row_directory[0];
			}
		}
		Database::query("UPDATE $tbl_session SET nbr_courses=$nbr_courses WHERE id='$id_session'", __FILE__, __LINE__);
		$course_directory[] = $id_session;
		$cad_course_directory = implode(',', $course_directory);

		$results[] = $cad_course_directory;
		continue;
	}

   	$count_results = count($results);
	$output = array();
	for($i = 0; $i < $count_results; $i++) {
		$output[] = array('original_course_id_values' => $orig_course_id_value[$i], 'original_session_id_value' => $orig_session_id_value[$i], 'result' => $results[$i]);
	}

	return $output;
}

/* Register DokeosWSUnsuscribeCoursesFromSession function */
// Register the data structures used by the service
$server->wsdl->addComplexType(
	'unsubscribeCoursesFromSessionParams',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'original_course_id_values' => array('name' => 'original_course_id_values', 'type' => 'tns:originalCoursesList'),
		'original_course_id_name' => array('name' => 'original_course_id_name', 'type' => 'xsd:string'),
		'original_session_id_value' => array('name' => 'original_session_id_value', 'type' => 'xsd:string'),
		'original_session_id_name' => array('name' => 'original_session_id_name', 'type' => 'xsd:string')
	)
);

$server->wsdl->addComplexType(
'unsubscribeCoursesFromSessionParamsList',
'complexType',
'array',
'',
'SOAP-ENC:Array',
array(),
array(array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:unsubscribeCoursesFromSessionParams[]')),
'tns:unsubscribeCoursesFromSessionParams'
);

$server->wsdl->addComplexType(
	'unsubscribeCoursesFromSession',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'coursessessions' => array('name' => 'coursessessions', 'type' => 'tns:unsubscribeCoursesFromSessionParamsList'),
		'secret_key' => array('name' => 'secret_key', 'type' => 'xsd:string')
	)
);

// Prepare output params, in this case will return an array
$server->wsdl->addComplexType(
'result_unsubscribeCoursesFromSession',
'complexType',
'struct',
'all',
'',
array(
		'original_course_id_values' => array('name' => 'original_course_id_values', 'type' => 'xsd:string'),
		'original_session_id_value' => array('name' => 'original_session_id_value', 'type' => 'xsd:string'),
		'result' => array('name' => 'result', 'type' => 'xsd:string')
     )
);

$server->wsdl->addComplexType(
'results_unsubscribeCoursesFromSession',
'complexType',
'array',
'',
'SOAP-ENC:Array',
array(),
array(array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:result_unsubscribeCoursesFromSession[]')),
'tns:result_unsubscribeCoursesFromSession'
);


// Register the method to expose
$server->register('DokeosWSUnsuscribeCoursesFromSession',							// method name
	array('unsubscribeCoursesFromSession' => 'tns:unsubscribeCoursesFromSession'),	// input parameters
	array('return' => 'tns:results_unsubscribeCoursesFromSession'),					// output parameters
	'urn:WSRegistration',															// namespace
	'urn:WSRegistration#DokeosWSUnsuscribeCoursesFromSession',						// soapaction
	'rpc',																			// style
	'encoded',																		// use
	'This service subscribes a course to a session' 								// documentation
);

// define the method DokeosWSUnsuscribeCoursesFromSession
function DokeosWSUnsuscribeCoursesFromSession($params) {

	global $_configuration;

	$secret_key = $params['secret_key'];
	$security_key = $_SERVER['REMOTE_ADDR'].$_configuration['security_key'];

	if (!api_is_valid_secret_key($secret_key, $security_key)) {
		return -1; // The secret key is incorrect
	}

   	// Initialisation
	$tbl_session_rel_course_rel_user	= Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
	$tbl_session						= Database::get_main_table(TABLE_MAIN_SESSION);
	$tbl_session_rel_user				= Database::get_main_table(TABLE_MAIN_SESSION_USER);
	$tbl_session_rel_course				= Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
	$tbl_course							= Database::get_main_table(TABLE_MAIN_COURSE);
	$t_sf 		= Database::get_main_table(TABLE_MAIN_SESSION_FIELD);
	$t_sfv 		= Database::get_main_table(TABLE_MAIN_SESSION_FIELD_VALUES);
 	$t_cfv 		= Database::get_main_table(TABLE_MAIN_COURSE_FIELD_VALUES);
	$t_cf 		= Database::get_main_table(TABLE_MAIN_COURSE_FIELD);

   	$coursessessions_params = $params['coursessessions'];
	$results = array();
	$orig_course_id_value = array();
	$orig_session_id_value = array();

	foreach ($coursessessions_params as $coursesession_param) {

		$original_session_id_value = $coursesession_param['original_session_id_value'];
		$original_session_id_name = $coursesession_param['original_session_id_name'];
		$original_course_id_name = $coursesession_param['original_course_id_name'];
		$original_course_id_values = $coursesession_param['original_course_id_values'];
	 	$orig_session_id_value[] = $original_session_id_value;
	 	// Get session id from original session id
		$sql_session = "SELECT session_id FROM $t_sf sf,$t_sfv sfv WHERE sfv.field_id=sf.id AND field_variable='$original_session_id_name' AND field_value='$original_session_id_value'";
		$res_session = Database::query($sql_session, __FILE__, __LINE__);
		$row_session = Database::fetch_row($res_session);

	 	$id_session = $row_session[0];

	 	if (empty($id_session)) {
			$results[] = 0;
			continue;
		}

	    // Get courses list from row_original_course_id_values
	    $course_list = array();
	 	foreach ($original_course_id_values as $row_original_course_list) {
	 		$sql_course = "SELECT course_code FROM $t_cf cf,$t_cfv cfv WHERE cfv.field_id=cf.id AND field_variable='$original_course_id_name' AND field_value = '$row_original_course_list'";
	 		$res_course = Database::query($sql_course, __FILE__, __LINE__);
	 		$row_course = Database::fetch_row($res_course);
	 		if (empty($row_course[0])) {
		    	continue; // Course_code doesn't exist'
		    } else {
				$sql = "SELECT code FROM $tbl_course WHERE code ='".$row_course[0]."' AND visibility = '0'";
				$resu = Database::query($sql, __FILE__, __LINE__);
				$r_check_course = Database::fetch_row($resu);
				if (!empty($r_check_course[0])) {
					continue; // user_id is not active.
				}
		    }
		    $course_list[] = $row_course[0];
	 	}

		if (empty($course_list)) {
			$results[] = 0;
			continue;
		}

	 	$orig_course_id_value[] = implode(',', $course_list);

		foreach ($course_list as $enreg_course) {
	        $enreg_course = Database::escape_string($enreg_course);
	        Database::query("DELETE FROM $tbl_session_rel_course WHERE course_code='$enreg_course' AND id_session='$id_session'", __FILE__, __LINE__);
			Database::query("DELETE FROM $tbl_session_rel_course_rel_user WHERE course_code='$enreg_course' AND id_session='$id_session'", __FILE__, __LINE__);
			$return = Database::affected_rows();
		}

		$nbr_courses = 0;
		$sql = "SELECT nbr_courses FROM $tbl_session WHERE id = '$id_session'";
		$res_nbr_courses = Database::query($sql, __FILE__, __LINE__);
		$row_nbr_courses = Database::fetch_row($res_nbr_courses);

		if (Database::num_rows($res_nbr_courses) > 0) {
		   $nbr_users = ($row_nbr_courses[0] - $return);
		}

		// Update number of users in the session.
		$update_sql = "UPDATE $tbl_session SET nbr_courses= $nbr_courses WHERE id='$id_session' ";
		Database::query($update_sql, __FILE__, __LINE__);

		$results[] = 1;
		continue;
	}

   	$count_results = count($results);
	$output = array();
	for($i = 0; $i < $count_results; $i++) {
		$output[] = array('original_course_id_values' => $orig_course_id_value[$i], 'original_session_id_value' => $orig_session_id_value[$i], 'result' => $results[$i]);
	}

	return $output;
}

// Use the request to (try to) invoke the service
$HTTP_RAW_POST_DATA = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : '';
$server->service($HTTP_RAW_POST_DATA);