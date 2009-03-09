<?php
require ('../../inc/global.inc.php');
$libpath = api_get_path(LIBRARY_PATH);
require_once($libpath.'nusoap/nusoap.php');
require_once ($libpath.'fileManage.lib.php');
include_once ($libpath.'usermanager.lib.php');
require_once ($libpath.'fileUpload.lib.php');
require_once(api_get_path(INCLUDE_PATH).'lib/mail.lib.inc.php');
require_once ($libpath.'add_course.lib.inc.php');
require_once($libpath.'course.lib.php');

// Create the server instance
$server = new soap_server();
// Initialize WSDL support
$server->configureWSDL('WSRegistration', 'urn:WSRegistration');

/* Register DokeosWebServiceCreateUser function */
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
		'original_user_id_name' => array('name' => 'original_user_id_name', 'type' => 'xsd:string'),
		'original_user_id_value' => array('name' => 'original_user_id_value', 'type' => 'xsd:string'),
		'secret_key' => array('name' => 'secret_key', 'type' => 'xsd:string'),			
		'language' => array('name' => 'language', 'type' => 'xsd:string'),
		'phone' => array('name' => 'phone', 'type' => 'xsd:string'),		
		'expiration_date' => array('name' => 'expiration_date', 'type' => 'xsd:string')								
	)
);

// Register the method to expose
$server->register('DokeosWebServiceCreateUser',		// method name
	array('createUser' => 'tns:createUser'),		// input parameters
	array('return' => 'xsd:int'),					// output parameters
	'urn:WSRegistration',							// namespace
	'urn:WSRegistration#DokeosWebServiceCreateUser',// soapaction
	'rpc',											// style
	'encoded',										// use
	'This service adds a user from wiener'			// documentation
);

// Define the method DokeosWebServiceCreateUser
function DokeosWebServiceCreateUser($params) {

	global $_user, $userPasswordCrypted,$_configuration;
	
	$secret_key = $params['secret_key'];

	if ( $secret_key != $_configuration['security_key']) {
   		return -1; // secret key is incorrect
   	}
			
	$firstName = $params['firstname'];			$lastName = $params['lastname'];
	$status = $params['status'];				$email = $params['email'];	
	$loginName = $params['loginname'];			$password = $params['password'];
	$official_code = '';$language='';$phone = '';$picture_uri = '';$auth_source = PLATFORM_AUTH_SOURCE; 
	$expiration_date = '0000-00-00 00:00:00'; $active = 1; $hr_dept_id=0; $extra=null;
	$original_user_id_name= $params['original_user_id_name'];
	$original_user_id_value = $params['original_user_id_value'];
					
	if (!empty($params['language'])) { $language=$params['language'];}
	if (!empty($params['phone'])) { $phone = $params['phone'];}
	if (!empty($params['expiration_date'])) { $expiration_date = $params['expiration_date'];}			
	
	// database table definition
	$table_user = Database::get_main_table(TABLE_MAIN_USER); 		
	$t_uf = Database::get_main_table(TABLE_MAIN_USER_FIELD);		
	$t_ufv = Database::get_main_table(TABLE_MAIN_USER_FIELD_VALUES);
	
	// check if exits wiener_user_id into user_field_values table
	$sql = "SELECT field_value,user_id	FROM $t_uf uf,$t_ufv ufv WHERE ufv.field_id=uf.id AND field_variable='$original_user_id_name' AND field_value='$original_user_id_value'";
	$res = api_sql_query($sql,__FILE__,__LINE__);
	$row = Database::fetch_row($res);
	
	if (!empty($row)) {		
		// check if user is not active 		
		$sql = "SELECT user_id FROM $table_user WHERE user_id ='".$row[1]."' AND active= '0'";
		$resu = api_sql_query($sql,__FILE__,__LINE__);
		$r_check_user = Database::fetch_row($resu);	
		if (!empty($r_check_user)) {			
			$sql = "UPDATE $table_user SET
			lastname='".Database::escape_string($lastName)."',
			firstname='".Database::escape_string($firstName)."',
			username='".Database::escape_string($loginName)."',";
			if(!is_null($password))
			{
				$password = $userPasswordCrypted ? md5($password) : $password;
				$sql .= " password='".Database::escape_string($password)."',";
			}
			if(!is_null($auth_source))
			{
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
			api_sql_query($sql,__FILE__,__LINE__);	
			return $r_check_user[0]; // 			
		} else {
			return 0;	// user id already exits
		}
	}
	 	 	 			
	// default language
	if ($language=='')
	{
		$language = api_get_setting('platformLanguage');
	}

	if ($_user['user_id'])
	{
		$creator_id = $_user['user_id'];
	}
	else
	{
		$creator_id = '';
	}
	// First check wether the login already exists
	if (! UserManager::is_username_available($loginName)) {	
		if(api_set_failure('login-pass already taken')) {
			$msg = 'Se ha producido un error!!!';		
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
	$result = api_sql_query($sql);
	if ($result) {
		//echo "id returned";		
		$return=Database::get_last_insert_id();
		global $_configuration;
		require_once (api_get_path(LIBRARY_PATH).'urlmanager.lib.php');
		if ($_configuration['multiple_access_urls']==true) {
			if (api_get_current_access_url_id()!=-1)
				UrlManager::add_user_to_url($return, api_get_current_access_url_id());
			else
				UrlManager::add_user_to_url($return, 1);
		} else {
			//we are adding by default the access_url_user table with access_url_id = 1
			UrlManager::add_user_to_url($return, 1);
		}
		// save new fieldlabel into user_field table
		$field_id = UserManager::create_extra_field($original_user_id_name,1,$original_user_id_name,'');
		// save the wiener's id into user_field_value table'	
		$res = UserManager::update_extra_field_value($return,$original_user_id_name,$original_user_id_value);	
	} else {				
		$return=0;
	}
					
	return $return;		
}

/* Register DokeosWebServiceEditUser function */
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
		'secret_key' => array('name' => 'secret_key', 'type' => 'xsd:string')		
	)
);

// Register the method to expose
$server->register('DokeosWebServiceEditUser',		// method name
	array('editUser' => 'tns:editUser'),			// input parameters
	array('return' => 'xsd:int'),					// output parameters
	'urn:WSRegistration',							// namespace
	'urn:WSRegistration#DokeosWebServiceEditUser',	// soapaction
	'rpc',											// style
	'encoded',										// use
	'This service edits a user from wiener'			// documentation
);

// Define the method DokeosWebServiceEditUser
function DokeosWebServiceEditUser($params)
{
	global $userPasswordCrypted,$_configuration;
	
	$secret_key = $params['secret_key'];
	
	if ( $secret_key != $_configuration['security_key']) {
   		return -1; // secret key is incorrect
   	}
	
	$original_user_id_value = $params['original_user_id_value'];
	$original_user_id_name = $params['original_user_id_name'];	
	$firstname = $params['firstname']; 
	$lastname = $params['lastname'];
	$username = $params['username'];
	$password = null; $auth_source = null; 
	$email = $params['email']; $status = $params['status'];
	$official_code = ''; $phone = $params['phone'];
	$picture_uri = ''; $expiration_date = $params['expiration_date']; $active = 1; 
	$creator_id= null; $hr_dept_id=0; $extra=null;
		
	if (!empty($params['password'])) { $password = $params['password'];}	
	
	// get user id from id wiener
	$table_user = Database :: get_main_table(TABLE_MAIN_USER);
	$t_uf = Database::get_main_table(TABLE_MAIN_USER_FIELD);		
	$t_ufv = Database::get_main_table(TABLE_MAIN_USER_FIELD_VALUES);
	$sql = "SELECT user_id FROM $t_uf uf,$t_ufv ufv WHERE ufv.field_id=uf.id AND field_variable='$original_user_id_name' AND field_value='$original_user_id_value'";
	$res = api_sql_query($sql,__FILE__,__LINE__);
	$row = Database::fetch_row($res);	
	$user_id = $row[0];

	if (empty($user_id)) {
		return 0; // original_user_id_value doesn't exits
	} else {
		$sql = "SELECT user_id FROM $table_user WHERE user_id ='$user_id' AND active= '0'";
		$resu = api_sql_query($sql,__FILE__,__LINE__);
		$r_check_user = Database::fetch_row($resu);
		if (!empty($r_check_user[0])) {
			return 0; // user_id is not active 
		}
	}
	
	// check if username already exits
	$sql = "SELECT username FROM $table_user WHERE username ='$username'";
	$res_un = api_sql_query($sql,__FILE__,__LINE__);
	$r_username = Database::fetch_row($res_un);
	
	if (!empty($r_username[0])) {
		return 0; // username already exits
	}
					
	$sql = "UPDATE $table_user SET
			lastname='".Database::escape_string($lastname)."',
			firstname='".Database::escape_string($firstname)."',
			username='".Database::escape_string($username)."',";
	if(!is_null($password))
	{
		$password = $userPasswordCrypted ? md5($password) : $password;
		$sql .= " password='".Database::escape_string($password)."',";
	}
	if(!is_null($auth_source))
	{
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
			
	if(!is_null($creator_id))
	{
		$sql .= ", creator_id='".Database::escape_string($creator_id)."'";
	}
	$sql .=	" WHERE user_id='$user_id'";
	$return = @api_sql_query($sql,__FILE__,__LINE__);	
	
	return $return;	
}

/* Register DokeosWebServiceDeleteUser function */
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

$server->register('DokeosWebServiceDeleteUser',		// method name
	array('deleteUser'=>'tns:deleteUser'),			// input parameters
	array('return' => 'xsd:int'),					// output parameters
	'urn:WSRegistration',							// namespace
	'urn:WSRegistration#DokeosWebServiceDeleteUser',// soapaction
	'rpc',											// style
	'encoded',										// use
	'This service deletes a user  '					// documentation
);

// Define the method DokeosWebServiceDeleteUser
function DokeosWebServiceDeleteUser($params)
{
	global $_configuration;
	
	$secret_key = $params['secret_key'];
	
	if ( $secret_key != $_configuration['security_key'] ) {
   		return -1;
   	}
   	$original_user_id_name = $params['original_user_id_name'];
   	$original_user_id_value = $params['original_user_id_value'];
   		
	// get user id from id wiener
	$table_user = Database :: get_main_table(TABLE_MAIN_USER);
	$t_uf = Database::get_main_table(TABLE_MAIN_USER_FIELD);		
	$t_ufv = Database::get_main_table(TABLE_MAIN_USER_FIELD_VALUES);
	$sql = "SELECT user_id FROM $t_uf uf,$t_ufv ufv WHERE ufv.field_id=uf.id AND field_variable='$original_user_id_name' AND field_value='$original_user_id_value'";		
	$res = api_sql_query($sql,__FILE__,__LINE__);
	$row = Database::fetch_row($res);	
	$user_id = $row[0];
	
	if (empty($user_id)) {
		return 0;
	} else {
		$sql = "SELECT user_id FROM $table_user WHERE user_id ='$user_id' AND active= '0'";
		$resu = api_sql_query($sql,__FILE__,__LINE__);
		$r_check_user = Database::fetch_row($resu);
		if (!empty($r_check_user[0])) {
			return 0;
		}
	}
	
	// update active to 0 	
	$sql = "UPDATE $table_user SET active='0' WHERE user_id = '$user_id'";
	$res = api_sql_query($sql,__FILE__,__LINE__);
		
	return 1;	
	
}

/* Register DokeosWebServiceAddCourse function */
// Register the data structures used by the service
$server->wsdl->addComplexType(
	'createCourse',
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
		'secret_key' => array('name' => 'secret_key', 'type' => 'xsd:string')		
	)
);

// Register the method to expose
$server->register('DokeosWebServiceCreateCourse',		// method name
	array('createCourse' => 'tns:createCourse'),			// input parameters
	array('return' => 'xsd:string'),				// output parameters
	'urn:WSRegistration',							// namespace
	'urn:WSRegistration#DokeosWebServiceCreateCourse',	// soapaction
	'rpc',											// style
	'encoded',										// use
	'This service adds a course into dokeos  '		// documentation
);

// Define the method DokeosWebServiceCreateCourse
function DokeosWebServiceCreateCourse($params) {
	
	global $firstExpirationDelay,$_configuration;
	
	$secret_key = $params['secret_key'];
	
	if ( $secret_key != $_configuration['security_key']) {
   		return -1; //secret key is incorrect
   	}
	
	$title=$params['title'];
	$category_code=$params['category_code'];
	$wanted_code=$params['wanted_code'];
	$tutor_name=$params['tutor_name'];
	$course_language=$params['course_language'];
	$original_course_id_name= $params['original_course_id_name'];
	$original_course_id_value = $params['original_course_id_value'];	
	
	$t_cfv = Database::get_main_table(TABLE_MAIN_COURSE_FIELD_VALUES);
	$table_field 		= Database::get_main_table(TABLE_MAIN_COURSE_FIELD);
	$table_course_category = Database :: get_main_table(TABLE_MAIN_CATEGORY);
	$table_course = Database :: get_main_table(TABLE_MAIN_COURSE);
	// check if exits $wiener_course_code into user_field_values table
	$sql = "SELECT field_value,course_code FROM $table_field cf,$t_cfv cfv WHERE cfv.field_id=cf.id AND field_variable='$original_course_id_name' AND field_value='$original_course_id_value'";
	$res = api_sql_query($sql,__FILE__,__LINE__);
	$row = Database::fetch_row($res);

	if (!empty($row[0])) {		
		// check if user is not active 		
		$sql = "SELECT code FROM $table_course WHERE code ='".$row[1]."' AND visibility= '0'";
		$resu = api_sql_query($sql,__FILE__,__LINE__);
		$r_check_course = Database::fetch_row($resu);	
		if (!empty($r_check_course[0])) {
			$sql = "UPDATE $table_course SET course_language='".Database::escape_string($course_language)."',
								title='".Database::escape_string($title)."',
								category_code='".Database::escape_string($category_code)."',
								tutor_name='".Database::escape_string($tutor_name)."',
								visual_code='".Database::escape_string($wanted_code)."',										
								visibility = '3'								
					WHERE code='".Database::escape_string($r_check_course[0])."'";			
			api_sql_query($sql,__FILE__,__LINE__);	
			return $r_check_course[0];
		} else {
			return 0; // original course id already exits		
		}
	}			
		
	$dbnamelength = strlen($_configuration['db_prefix']);
	//Ensure the database prefix + database name do not get over 40 characters
	$maxlength = 40 - $dbnamelength;

	// Set default values
	if (isset($_user["language"]) && $_user["language"]!="") {
		$values['course_language'] = $_user["language"];
	} else {
		$values['course_language'] = get_setting('platformLanguage');
	}
	
	$values['tutor_name'] = $_user['firstName']." ".$_user['lastName'];
		
		if (trim($wanted_code) == '') {
			$wanted_code = generate_course_code(substr($title,0,$maxlength));
		}
		
		$keys = define_course_keys($wanted_code, "", $_configuration['db_prefix']);
		
		$sql_check = sprintf('SELECT * FROM '.$table_course.' WHERE visual_code = "%s"',Database :: escape_string($wanted_code));
		$result_check = api_sql_query($sql_check,__FILE__,__LINE__); //I don't know why this api function doesn't work...
		if ( Database::num_rows($result_check)<1 ) {
			if (sizeof($keys)) {
				$visual_code = $keys["currentCourseCode"];
				$code = $keys["currentCourseId"];
				$db_name = $keys["currentCourseDbName"];
				$directory = $keys["currentCourseRepository"];
				$expiration_date = time() + $firstExpirationDelay;
				prepare_course_repository($directory, $code);
				update_Db_course($db_name);
				$pictures_array=fill_course_repository($directory);
				fill_Db_course($db_name, $directory, $course_language,$pictures_array);
				$return = register_course($code, $visual_code, $directory, $db_name, $tutor_name, $category_code, $title, $course_language, api_get_user_id(), $expiration_date);

				$time = time();
				$sql_field = "SELECT id FROM $table_field WHERE field_variable = '".Database::escape_string($original_course_id_name)."'";
				$res_field = api_sql_query($sql_field,__FILE__,__LINE__);
				$r_field = Database::fetch_row($res_field);
				if (!empty($r_field[0])) {					
					$field_id = $r_field[0];
				} else {
					// save new fieldlabel into course_field table								
					$sql = "SELECT MAX(field_order) FROM $table_field";
					$res = api_sql_query($sql,__FILE__,__LINE__);
					$order = 0;
					if(Database::num_rows($res)>0)
					{
						$row = Database::fetch_array($res);
						$order = $row[0]+1;
					}
					
					$sql = "INSERT INTO $table_field
								                SET field_type = '1',
								                field_variable = '".Database::escape_string($original_course_id_name)."',
								                field_display_text = '".Database::escape_string($original_course_id_name)."',
								                field_order = '$order',									                									                							                
								                tms = FROM_UNIXTIME($time)";
					$result = api_sql_query($sql,__FILE__,__LINE__);
					$field_id=Database::get_last_insert_id();	
				}

				// save the original course id into course_field_value table'							
				$sqli = "INSERT INTO $t_cfv (course_code,field_id,field_value,tms)
						VALUES ('$code',$field_id,'$original_course_id_value',FROM_UNIXTIME($time))";
				$resi = api_sql_query($sqli,__FILE__,__LINE__);						
			}
	        return $code;
		} else {
			return 0;
		}
}

/* Register DokeosWebServiceEditCourse function */
// Register the data structures used by the service

$server->wsdl->addComplexType(
	'editCourse',
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
		'secret_key' => array('name' => 'secret_key', 'type' => 'xsd:string')		
	)
);

// Register the method to expose
$server->register('DokeosWebServiceEditCourse',		// method name
	array('editCourse' => 'tns:editCourse'),			// input parameters
	array('return' => 'xsd:string'),				// output parameters
	'urn:WSRegistration',							// namespace
	'urn:WSRegistration#DokeosWebServiceEditCourse',// soapaction
	'rpc',											// style
	'encoded',										// use
	'This service edits a course into dokeos'		// documentation
);

// Define the method DokeosWebServiceEditCourse
function DokeosWebServiceEditCourse($params){
	
	global $_configuration;
	
	$secret_key = $params['secret_key'];
	
	if ( $secret_key != $_configuration['security_key']) {
   		return -1; // secret key is incorrect
   	} 
	
	$tutor_id=$params['tutor_id'];
	$title=$params['title'];
	$category_code =$params['category_code'];
	$department_name =$params['department_name'];
	$department_url =$params['department_url'];
	$course_language =$params['course_language'];	
	$visibility=$params['visibility'];
	$subscribe=$params['subscribe'];
	$unsubscribe=$params['unsubscribe'];
	$visual_code = $params['visual_code'];
	$original_course_id_name = $params['original_course_id_name'];
	$original_course_id_value = $params['original_course_id_value'];
	
	$course_table = Database::get_main_table(TABLE_MAIN_COURSE);
	$course_user_table = Database::get_main_table(TABLE_MAIN_COURSE_USER);	
	$t_cfv 			= Database::get_main_table(TABLE_MAIN_COURSE_FIELD_VALUES);
	$table_field 	= Database::get_main_table(TABLE_MAIN_COURSE_FIELD);
	// get course code from id wiener
	$sql = "SELECT course_code	FROM $table_field cf,$t_cfv cfv WHERE cfv.field_id=cf.id AND field_variable='$original_course_id_name' AND field_value='$original_course_id_value'";
	$res = api_sql_query($sql,__FILE__,__LINE__);
	$row = Database::fetch_row($res);	
		
	$course_code=$row[0];

	if (empty($course_code)) {
		return 0; // original_course_id_value doesn't exits
	} else {
		$sql = "SELECT code FROM $course_table WHERE code ='$course_code' AND visibility = '0'";
		$resu = api_sql_query($sql,__FILE__,__LINE__);
		$r_check_code = Database::fetch_row($resu);
		if (!empty($r_check_code[0])) {
			return 0; // this code is not active
		}
	}

	$table_user = Database :: get_main_table(TABLE_MAIN_USER);
	$sql = "SELECT concat(lastname,'',firstname) as tutor_name FROM $table_user WHERE status='1' AND user_id = '$tutor_id' ORDER BY lastname,firstname";
	$res = api_sql_query($sql,__FILE__,__LINE__);
	$tutor_name = Database::fetch_row($res);
		
	$disk_quota = '50000';
	$tutor_name=$tutor_name[0];
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
	$res = api_sql_query($sql, __FILE__, __LINE__);
	if ($res) {
		return 1;	
	} else {
		return 0;
	}		
}


/* Register DokeosWebServiceDeleteCourse function */
$server->wsdl->addComplexType(
	'deleteCourse',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'original_course_id_value' => array('name' => 'original_course_id_value', 'type' => 'xsd:string'),		
		'original_course_id_name' => array('name' => 'original_course_id_name', 'type' => 'xsd:string'),
		'secret_key' => array('name' => 'secret_key', 'type' => 'xsd:string')		
	)
);

$server->register('DokeosWebServiceDeleteCourse',		// method name
	array('deleteCourse' => 'tns:deleteCourse'),		// input parameters
	array('return' => 'xsd:int'),						// output parameters
	'urn:WSRegistration',								// namespace
	'urn:WSRegistration#DokeosWebServiceDeleteCourse',	// soapaction
	'rpc',												// style
	'encoded',											// use
	'This service deletes a course '					// documentation
);


// define the method DokeosWebServiceDeleteCourse
function DokeosWebServiceDeleteCourse($params) {
	
		global $_configuration;
		
		$secret_key = $params['secret_key'];
				
		if ( $secret_key != $_configuration['security_key']) {
   			return -1; // secret key is incorrect
   		}
		
		$original_course_id_value = $params['original_course_id_value'];
		$original_course_id_name = $params['original_course_id_name'];			
		
		$table_course = Database :: get_main_table(TABLE_MAIN_COURSE);
		$t_cfv 			= Database::get_main_table(TABLE_MAIN_COURSE_FIELD_VALUES);
		$table_field 	= Database::get_main_table(TABLE_MAIN_COURSE_FIELD);
		// get course code from id wiener
		$sql_course = "SELECT course_code	FROM $table_field cf,$t_cfv cfv WHERE cfv.field_id=cf.id AND field_variable='$original_course_id_name' AND field_value='$original_course_id_value'";
		$res_course = api_sql_query($sql_course,__FILE__,__LINE__);
		$row_course = Database::fetch_row($res_course);
			
		$code=$row_course[0];

		if (empty($code)) {
			return 0; // original_course_id_value doesn't exits
		} else {
			$sql = "SELECT code FROM $table_course WHERE code ='$code' AND visibility = '0'";
			$resu = api_sql_query($sql,__FILE__,__LINE__);
			$r_check_code = Database::fetch_row($resu);
			if (!empty($r_check_code[0])) {
				return 0; // this code is not active
			}
		}
								
		$sql= "UPDATE $table_course SET visibility = '0' WHERE code = '$code'";
		$return = api_sql_query($sql,__FILE__,__LINE__);
		return $return;
		
}

/* Register DokeosWebServiceCreateSession function */
// Register the data structures used by the service
$server->wsdl->addComplexType(
	'createSession',
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
		'nb_days_acess_before' => array('name' => 'nb_days_acess_before', 'type' => 'xsd:string'),
		'nb_days_acess_after' => array('name' => 'nb_days_acess_after', 'type' => 'xsd:string'),		
		'nolimit' => array('name' => 'nolimit', 'type' => 'xsd:string'),
		'coach_username' => array('name' => 'coach_username', 'type' => 'xsd:string'),
		'original_session_id_name' => array('name' => 'original_session_id_name', 'type' => 'xsd:string'),
		'original_session_id_value' => array('name' => 'original_session_id_value', 'type' => 'xsd:string'),
		'secret_key' => array('name' => 'secret_key', 'type' => 'xsd:string')
	)
);

// Register the method to expose
$server->register('DokeosWebServiceCreateSession',			// method name
	array('createSession' => 'tns:createSession'),			// input parameters
	array('return' => 'xsd:int'),					// output parameters
	'urn:WSRegistration',								// namespace
	'urn:WSRegistration#DokeosWebServiceCreateSession',	// soapaction
	'rpc',												// style
	'encoded',											// use
	'This service edits a session'						// documentation
);


// define the method DokeosWebServiceCreateSession
function DokeosWebServiceCreateSession($params) {
	
	global $_user,$_configuration;
	
	$secret_key = $params['secret_key'];
	
	if ( $secret_key != $_configuration['security_key'] ) {
   		return -1;
   	}
		
	$tbl_user		= Database::get_main_table(TABLE_MAIN_USER);
	$tbl_session	= Database::get_main_table(TABLE_MAIN_SESSION);
					
	$name= trim($params['name']); 
	$year_start= intval($params['year_start']); 
	$month_start=intval($params['month_start']);
	$day_start=intval($params['day_start']); 
	$year_end=intval($params['year_end']); 
	$month_end=intval($params['month_end']); 
	$day_end=intval($params['day_end']); 
	$nb_days_acess_before = intval($params['nb_days_acess_before']); 
	$nb_days_acess_after = intval($params['nb_days_acess_after']);
	$coach_username = $params['coach_username'];
	$original_session_id_name = $params['original_session_id_name'];
	$original_session_id_value = $params['original_session_id_value'];	
	
	$t_sf = Database::get_main_table(TABLE_MAIN_SESSION_FIELD);		
	$t_sfv = Database::get_main_table(TABLE_MAIN_SESSION_FIELD_VALUES);
	
	// check if exits wiener session id into session_field_values table
	$sql = "SELECT field_value	FROM $t_sf sf,$t_sfv sfv WHERE sfv.field_id=sf.id AND field_variable='$original_session_id_name' AND field_value='$original_session_id_value'";
	$res = api_sql_query($sql,__FILE__,__LINE__);
	$row = Database::fetch_row($res);
	if (!empty($row)) {		
		return 0;				
	}
	
	$sql = 'SELECT user_id FROM '.$tbl_user.' WHERE username="'.Database::escape_string($coach_username).'"';
	$rs = api_sql_query($sql, __FILE__, __LINE__);
	$id_coach = Database::result($rs,0,'user_id');

	if (empty($params['nolimit'])){
		$date_start="$year_start-".(($month_start < 10)?"0$month_start":$month_start)."-".(($day_start < 10)?"0$day_start":$day_start);
		$date_end="$year_end-".(($month_end < 10)?"0$month_end":$month_end)."-".(($day_end < 10)?"0$day_end":$day_end);
	} else {
		$date_start="000-00-00";
		$date_end="000-00-00";
	}
	if(empty($name)) $msg='SessionNameIsRequired';
	elseif(empty($params['nolimit']) && (!$month_start || !$day_start || !$year_start || !checkdate($month_start,$day_start,$year_start))) $msg='InvalidStartDate';
	elseif(empty($params['nolimit']) && (!$month_end || !$day_end || !$year_end || !checkdate($month_end,$day_end,$year_end))) $msg='InvalidEndDate';
	elseif(empty($params['nolimit']) && $date_start >= $date_end) $msg='StartDateShouldBeBeforeEndDate';
	else
	{
		$rs = api_sql_query("SELECT 1 FROM $tbl_session WHERE name='".addslashes($name)."'");
		if(Database::num_rows($rs)){
			return 0;
		} else {
			api_sql_query("INSERT INTO $tbl_session(name,date_start,date_end,id_coach,session_admin_id, nb_days_access_before_beginning, nb_days_access_after_end) VALUES('".addslashes($name)."','$date_start','$date_end','$id_coach',".intval($_user['user_id']).",".$nb_days_acess_before.", ".$nb_days_acess_after.")",__FILE__,__LINE__);
			$id_session=Database::get_last_insert_id();	
			
			$time = time();
			$sql_field = "SELECT id FROM $t_sf WHERE field_variable = '".Database::escape_string($original_session_id_name)."'";
			$res_field = api_sql_query($sql_field,__FILE__,__LINE__);
			$r_field = Database::fetch_row($res_field);
			if (!empty($r_field[0])) {					
				$field_id = $r_field[0];
			} else {
				// save new fieldlabel into user_field table								
				$sql = "SELECT MAX(field_order) FROM $t_sf";
				$res = api_sql_query($sql,__FILE__,__LINE__);
				$order = 0;
				if(Database::num_rows($res)>0)
				{
					$row = Database::fetch_array($res);
					$order = $row[0]+1;
				}
				$sql = "INSERT INTO $t_sf
							                SET field_type = '1',
							                field_variable = '".Database::escape_string($original_session_id_name)."',
							                field_display_text = '".Database::escape_string($original_session_id_name)."',
							                field_order = '$order',									                									                							                
							                tms = FROM_UNIXTIME($time)";
				$result = api_sql_query($sql,__FILE__,__LINE__);
				$field_id=Database::get_last_insert_id();
			}
								
			// save the wiener's id into user_field_value table'							
			$sqli = "INSERT INTO $t_sfv (session_id,field_id,field_value,tms)
					VALUES ('$id_session',$field_id,'$original_session_id_value',FROM_UNIXTIME($time))";
			$resi = api_sql_query($sqli,__FILE__,__LINE__);		
						
			return $id_session;		
		}
	}
	
}

/* Register DokeosWebServiceEditSession function */
// Register the data structures used by the service
$server->wsdl->addComplexType(
	'editSession',
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
		'nb_days_acess_before' => array('name' => 'nb_days_acess_before', 'type' => 'xsd:string'),
		'nb_days_acess_after' => array('name' => 'nb_days_acess_after', 'type' => 'xsd:string'),		
		'nolimit' => array('name' => 'nolimit', 'type' => 'xsd:string'),
		'coach_username' => array('name' => 'coach_username', 'type' => 'xsd:string'),
		'original_session_id_name' => array('name' => 'original_session_id_name', 'type' => 'xsd:string'),
		'original_session_id_value' => array('name' => 'original_session_id_value', 'type' => 'xsd:string'),
		'secret_key' => array('name' => 'secret_key', 'type' => 'xsd:string')
	)
);

// Register the method to expose
$server->register('DokeosWebServiceEditSession',		// method name
	array('editSession' => 'tns:editSession'),			// input parameters
	array('return' => 'xsd:int'),						// output parameters
	'urn:WSRegistration',								// namespace
	'urn:WSRegistration#DokeosWebServiceEditSession',	// soapaction
	'rpc',												// style
	'encoded',											// use
	'This service edits a session'						// documentation
);

// define the method DokeosWebServiceEditSession
function DokeosWebServiceEditSession($params) {
	
	global $_user,$_configuration;	
	
	$secret_key = $params['secret_key'];	
	
	if ( $secret_key != $_configuration['security_key']) {
   		return -1;
   	}
		
	$tbl_user		= Database::get_main_table(TABLE_MAIN_USER);
	$tbl_session	= Database::get_main_table(TABLE_MAIN_SESSION);
		
	$name= trim($params['name']); 
	$year_start= intval($params['year_start']); 
	$month_start=intval($params['month_start']); 
	$day_start=intval($params['day_start']); 
	$year_end=intval($params['year_end']); 
	$month_end=intval($params['month_end']);
	$day_end=intval($params['day_end']); 
	$nb_days_acess_before = intval($params['nb_days_acess_before']); 
	$nb_days_acess_after = intval($params['nb_days_acess_after']); 
	$original_session_id_value = $params['original_session_id_value'];
	$original_session_id_name = $params['original_session_id_name'];
	$coach_username = $params['coach_username'];
	$nolimit = $params['nolimit'];
	
	$t_sf = Database::get_main_table(TABLE_MAIN_SESSION_FIELD);		
	$t_sfv = Database::get_main_table(TABLE_MAIN_SESSION_FIELD_VALUES);
	
	// get session id from original session id
	$sql = "SELECT session_id FROM $t_sf sf,$t_sfv sfv WHERE sfv.field_id=sf.id AND field_variable='$original_session_id_name' AND field_value='$original_session_id_value'";		
	$res = api_sql_query($sql,__FILE__,__LINE__);
	$row = Database::fetch_row($res);	
		
	$id=intval($row[0]);
	
	if (empty($id)) {
		return 0;
	}
	
	$sql = 'SELECT user_id FROM '.$tbl_user.' WHERE username="'.Database::escape_string($coach_username).'"';
	$rs = api_sql_query($sql, __FILE__, __LINE__);
	$id_coach = Database::result($rs,0,'user_id');

	if (empty($params['nolimit'])) {
		$date_start="$year_start-".(($month_start < 10)?"0$month_start":$month_start)."-".(($day_start < 10)?"0$day_start":$day_start);
		$date_end="$year_end-".(($month_end < 10)?"0$month_end":$month_end)."-".(($day_end < 10)?"0$day_end":$day_end);
	} else {
		$date_start="000-00-00";
		$date_end="000-00-00";
	}
	if(empty($name)) $msg='SessionNameIsRequired';
	elseif(empty($nolimit) && (!$month_start || !$day_start || !$year_start || !checkdate($month_start,$day_start,$year_start))) $msg='InvalidStartDate';
	elseif(empty($nolimit) && (!$month_end || !$day_end || !$year_end || !checkdate($month_end,$day_end,$year_end))) $msg='InvalidEndDate';
	elseif(empty($nolimit) && $date_start >= $date_end) $msg='StartDateShouldBeBeforeEndDate';
	else {
		$sql="UPDATE $tbl_session SET " .
				"name='".addslashes($name)."', " .
				"date_start='".$date_start."', " .
				"date_end='".$date_end."', " .
				"id_coach='".		$id_coach."', " .
				"session_admin_id='".		intval($_user['user_id'])."', " .
				"nb_days_access_before_beginning='".		$nb_days_acess_before."', " .
				"nb_days_access_after_end='".		$nb_days_acess_after."'" .
				" WHERE id='".$id."'";		
		api_sql_query($sql,__FILE__,__LINE__);
		$id_session=Database::get_last_insert_id();	
		return 1;		
	}
	
}

/* Register DokeosWebServiceDeleteSession function */
$server->wsdl->addComplexType(
	'deleteSession',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'original_session_id_value' => array('name' => 'original_session_id_value', 'type' => 'xsd:string'),
		'original_session_id_name' => array('name' => 'original_session_id_name', 'type' => 'xsd:string'),		
		'secret_key' => array('name' => 'secret_key', 'type' => 'xsd:string')		
	)
);

$server->register('DokeosWebServiceDeleteSession',		// method name
	array('deleteSession' => 'tns:deleteSession'),		// input parameters
	array('return' => 'xsd:int'),						// output parameters
	'urn:WSRegistration',								// namespace
	'urn:WSRegistration#DokeosWebServiceDeleteSession',	// soapaction
	'rpc',												// style
	'encoded',											// use
	'This service deletes a session '					// documentation
);

// define the method DokeosWebServiceDeleteSession
function DokeosWebServiceDeleteSession($params) {

	global $_configuration;
	
	$secret_key = $params['secret_key'];	
	
	if ( $secret_key != $_configuration['security_key'] ) {
   		return -1;
   	}	
	
	$original_session_id_value = $params['original_session_id_value'];
	$original_session_id_name = $params['original_session_id_name'];
	
	$t_sf = Database::get_main_table(TABLE_MAIN_SESSION_FIELD);		
	$t_sfv = Database::get_main_table(TABLE_MAIN_SESSION_FIELD_VALUES);
	
	// get session id from original session id
	$sql = "SELECT session_id,field_id FROM $t_sf sf,$t_sfv sfv WHERE sfv.field_id=sf.id AND field_variable='$original_session_id_name' AND field_value='$original_session_id_value'";
	$res = api_sql_query($sql,__FILE__,__LINE__);
	$row = Database::fetch_row($res);	
		
	$idChecked=intval($row[0]);
	$field_id = intval($row[1]);
	if (empty($idChecked)) {
		return 0; // session id don't exist
	}
			
	$tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
	$tbl_session_rel_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
	$tbl_session_rel_course_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
	$tbl_session_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_USER);
	$tbl_user = Database::get_main_table(TABLE_MAIN_USER);	
	
		
	$sql_session_field = "DELETE FROM $t_sf WHERE id = '$field_id'";
	api_sql_query($sql_session_field,__FILE__,__LINE__);
	$sql_session_field_value = "DELETE FROM $t_sfv WHERE session_id = '$idChecked'";
	api_sql_query($sql_session_field_value,__FILE__,__LINE__);	
	$sql_session = "DELETE FROM $tbl_session WHERE id = '$idChecked'";
	api_sql_query($sql_session,__FILE__,__LINE__);
	$sql_session_rel_course = "DELETE FROM $tbl_session_rel_course WHERE id_session = '$idChecked'"; 
	api_sql_query($sql_session_rel_course,__FILE__,__LINE__);
	$sql_session_rel_course_rel_user = "DELETE FROM $tbl_session_rel_course_rel_user WHERE id_session = '$idChecked'";
	api_sql_query($sql_session_rel_course_rel_user,__FILE__,__LINE__);	
	$sql_session_rel_course = "DELETE FROM $tbl_session_rel_user WHERE id_session = '$idChecked'"; 
	api_sql_query($sql_session_rel_course,__FILE__,__LINE__);
	return 1;
}



/* Register DokeosWebServiceSubscribeUserToCourse function */
// Register the data structures used by the service
$server->wsdl->addComplexType(
	'subscribeUserToCourse',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'original_user_id_value' => array('name' => 'original_user_id_value', 'type' => 'xsd:string'),
		'original_user_id_name' => array('name' => 'original_user_id_name', 'type' => 'xsd:string'),
		'original_course_id_value' => array('name' => 'original_course_id_value', 'type' => 'xsd:string'),
		'original_course_id_name' => array('name' => 'original_course_id_value', 'type' => 'xsd:string'),
		'status' => array('name' => 'status', 'type' => 'xsd:string'),
		'secret_key' => array('name' => 'secret_key', 'type' => 'xsd:string')		
	)
);

// Register the method to expose
$server->register('DokeosWebServiceSubscribeUserToCourse',			// method name
	array('subscribeUserToCourse' => 'tns:subscribeUserToCourse'),	// input parameters
	array('return' => 'xsd:int'),									// output parameters
	'urn:WSRegistration',											// namespace
	'urn:WSRegistration#DokeosWebServiceSubscribeUserToCourse',		// soapaction
	'rpc',															// style
	'encoded',														// use
	'This service subscribes a user to a course' 					// documentation
);

// define the method DokeosWebServiceSubscribeUserToCourse
function DokeosWebServiceSubscribeUserToCourse($params) {
    
    global $_configuration;
    
    $secret_key = $params['secret_key'];
    
    if ( $secret_key != $_configuration['security_key']) {
   		return -1;
   	}
    
    $original_user_id_value = $params['original_user_id_value'];
    $original_user_id_name = $params['original_user_id_name']; 
    $original_course_id_value = $params['original_course_id_value'];
    $original_course_id_name = $params['original_course_id_name'];

    // get user id from original user id
    $user_table = Database :: get_main_table(TABLE_MAIN_USER);
	$t_uf = Database::get_main_table(TABLE_MAIN_USER_FIELD);		
	$t_ufv = Database::get_main_table(TABLE_MAIN_USER_FIELD_VALUES);
	$sql_user = "SELECT user_id FROM $t_uf uf,$t_ufv ufv WHERE ufv.field_id=uf.id AND field_variable='$original_user_id_name' AND field_value='$original_user_id_value'";
	$res_user = api_sql_query($sql_user,__FILE__,__LINE__);
	$row_user = Database::fetch_row($res_user);	
	$user_id = $row_user[0];
    
    if (empty($user_id)) {
    	return 0;
    } else {
		$sql = "SELECT user_id FROM $user_table WHERE user_id ='$user_id' AND active= '0'";
		$resu = api_sql_query($sql,__FILE__,__LINE__);
		$r_check_user = Database::fetch_row($resu);
		if (!empty($r_check_user[0])) {
			return 0; // user_id is not active 
		}
    }
    
    // get course code from original course id
    $course_table = Database :: get_main_table(TABLE_MAIN_COURSE);
	$t_cfv 			= Database::get_main_table(TABLE_MAIN_COURSE_FIELD_VALUES);
	$table_field 	= Database::get_main_table(TABLE_MAIN_COURSE_FIELD);
	$sql_course = "SELECT course_code	FROM $table_field cf,$t_cfv cfv WHERE cfv.field_id=cf.id AND field_variable='$original_course_id_name' AND field_value='$original_course_id_value'";
	$res_course = api_sql_query($sql_course,__FILE__,__LINE__);
	$row_course = Database::fetch_row($res_course);	
		
	$course_code=$row_course[0];
	
	if (empty($course_code)) {
		return 0; // original_course_id_value doesn't exits
	} else {
		$sql = "SELECT code FROM $course_table WHERE code ='$course_code' AND visibility = '0'";
		$resc = api_sql_query($sql,__FILE__,__LINE__);
		$r_check_code = Database::fetch_row($resc);
		if (!empty($r_check_code[0])) {
			return 0; // this code is not active
		}
	}
	
    $status = STUDENT;
    
    if (!empty($params['status'])) {
    	$status = $params['status'];
    }
       
    if ( $user_id != strval(intval($user_id))) {
   	return 0; //detected possible SQL injection
    }
		
	$course_user_table = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
	$location_table = Database :: get_main_table(MAIN_LOCATION_TABLE);
	$user_role_table = Database :: get_main_table(MAIN_USER_ROLE_TABLE);
	$tbl_session_rel_course_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
	$tbl_session_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_USER);
	$tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
	
	$status = ($status == STUDENT || $status == COURSEMANAGER) ? $status : STUDENT;
	$role_id = ($status == COURSEMANAGER) ? COURSE_ADMIN : NORMAL_COURSE_MEMBER;
        $course_code = Database::escape_string($course_code);
	if (empty ($user_id) || empty ($course_code)) {
		return 0;
	} else {
		
		// previously check if the user are already registered on the platform
			$handle = @api_sql_query("SELECT status FROM ".$user_table."
													WHERE user_id = '$user_id' ", __FILE__, __LINE__);
		if (Database::num_rows($handle) == 0){
			return 0; // the user isn't registered to the platform
		} else {
			//check if user isn't already subscribed to the course
			$handle = @api_sql_query("SELECT * FROM ".$course_user_table."
																WHERE user_id = '$user_id'
																AND course_code ='$course_code'", __FILE__, __LINE__);
			if (Database::num_rows($handle) > 0) {
				return 0; // the user is already subscribed to the course
			} else {
				if (!empty($_SESSION["id_session"])) {
					
					//check if user isn't already estore to the session_rel_course_user table
					$sql1 = "SELECT * FROM $tbl_session_rel_course_user
							WHERE course_code = '".$_SESSION['_course']['id']."'
							AND id_session ='".$_SESSION["id_session"]."'
							AND id_user = '".$user_id."'";
					$result1 = @api_sql_query($sql1,__FILE__,__LINE__);
					$check1 = Database::num_rows($result1);
					
					//check if user isn't already estore to the session_rel_user table
					$sql2 = "SELECT * FROM $tbl_session_rel_user
							WHERE id_session ='".$_SESSION["id_session"]."'
							AND id_user = '".$user_id."'";
					$result2 = @api_sql_query($sql2,__FILE__,__LINE__);
					$check2 = Database::num_rows($result2);
					
				if ($check1 > 0 || $check2 > 0) {
						return 0;
					} else {
						// add in table session_rel_course_rel_user	
						$add_session_course_rel = "INSERT INTO $tbl_session_rel_course_user 	
												  SET id_session ='".$_SESSION["id_session"]."',
												  course_code = '".$_SESSION['_course']['id']."',
												  id_user = '".$user_id."'";
					    $result = @api_sql_query($add_session_course_rel,__FILE__, __LINE__);					    	
					    // add in table session_rel_user			    
					    $add_session_rel_user = "INSERT INTO $tbl_session_rel_user 	
												  SET id_session ='".$_SESSION["id_session"]."',											  
												  id_user = '".$user_id."'";
					    $result = @api_sql_query($add_session_rel_user,__FILE__, __LINE__);					    
					    // update the table session
				    	$sql = "SELECT COUNT(*) from $tbl_session_rel_user WHERE id_session = '".$_SESSION["id_session"]."'";
				    	$result = @api_sql_query($sql,__FILE__, __LINE__);				    	
				    	$row = Database::fetch_array($result);						    			    	
				 		$count = $row[0]; // number of users by session				 		
				 		$update_user_session = "UPDATE $tbl_session set nbr_users = '$count' WHERE id = '".$_SESSION["id_session"]."'" ;  	
						$result = @api_sql_query($update_user_session,__FILE__,__LINE__);
					}					
				} else {
				$course_sort = CourseManager :: userCourseSort($user_id,$course_code);
				$add_course_user_entry_sql = "INSERT INTO ".$course_user_table."
									SET course_code = '$course_code',
									user_id    = '$user_id',
									status    = '".$status."',
									sort  =   '". ($course_sort)."'";
				$result = @api_sql_query($add_course_user_entry_sql, __FILE__, __LINE__);
				}
				if ($result) {
					return 1;
				} else {
					return 0;
				}
			}
		}
	}
}

/* Register DokeosWebServiceSubscribeUserToCourse function */
// Register the data structures used by the service
$server->wsdl->addComplexType(
	'unsuscribeUserFromCourse',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'original_user_id_value' => array('name' => 'original_user_id_value', 'type' => 'xsd:string'),
		'original_user_id_name' => array('name' => 'original_user_id_name', 'type' => 'xsd:string'),
		'original_course_id_value' => array('name' => 'original_course_id_value', 'type' => 'xsd:string'),
		'original_course_id_name' => array('name' => 'original_course_id_name', 'type' => 'xsd:string'),
		'secret_key' => array('name' => 'secret_key', 'type' => 'xsd:string')	
	)
);

// Register the method to expose
$server->register('DokeosWebServiceUnsubscribeUserFromCourse',			// method name
	array('unsuscribeUserFromCourse' => 'tns:unsuscribeUserFromCourse'),// input parameters
	array('return' => 'xsd:int'),										// output parameters
	'urn:WSRegistration',												// namespace
	'urn:WSRegistration#DokeosWebServiceUnsubscribeUserToCourse',		// soapaction
	'rpc',																// style
	'encoded',															// use
	'This service unsubscribes a user from a course' 					// documentation
);

// define the method DokeosWebServiceUnsubscribeUserToCourse
function DokeosWebServiceUnsubscribeUserFromCourse($params)
{
	global $_configuration;
	$secret_key = $params['secret_key'];
	
	if ( $secret_key != $_configuration['security_key']) {
   		return -1;
   	}
	
	$original_user_id_value 	= $params['original_user_id_value'];
    $original_user_id_name 		= $params['original_user_id_name']; 
    $original_course_id_value 	= $params['original_course_id_value'];
    $original_course_id_name 	= $params['original_course_id_name'];
    
    // get user id from original user id
    $user_table = Database::get_main_table(TABLE_MAIN_USER);
	$t_uf = Database::get_main_table(TABLE_MAIN_USER_FIELD);		
	$t_ufv = Database::get_main_table(TABLE_MAIN_USER_FIELD_VALUES);
	$sql_user = "SELECT user_id FROM $t_uf uf,$t_ufv ufv WHERE ufv.field_id=uf.id AND field_variable='$original_user_id_name' AND field_value='$original_user_id_value'";
	$res_user = api_sql_query($sql_user,__FILE__,__LINE__);
	$row_user = Database::fetch_row($res_user);	
	$user_id = (int)$row_user[0];
            
    if (empty($user_id)) {
    	return 0;
    } else {
		$sql = "SELECT user_id FROM $user_table WHERE user_id ='$user_id' AND active= '0'";
		$resu = api_sql_query($sql,__FILE__,__LINE__);
		$r_check_user = Database::fetch_row($resu);
		if (!empty($r_check_user[0])) {
			return 0; // user_id is not active 
		}
    }
	
    // get course code from original course id
    $table_course 	= Database :: get_main_table(TABLE_MAIN_COURSE);
    $table_course_user = Database :: get_main_table(TABLE_MAIN_COURSE_USER);	
	$t_cfv 			= Database::get_main_table(TABLE_MAIN_COURSE_FIELD_VALUES);
	$table_field 	= Database::get_main_table(TABLE_MAIN_COURSE_FIELD);
	$sql_course 	= "SELECT course_code	FROM $table_field cf,$t_cfv cfv WHERE cfv.field_id=cf.id AND field_variable='$original_course_id_name' AND field_value='$original_course_id_value'";
	$res_course 	= api_sql_query($sql_course,__FILE__,__LINE__);
	$row_course 	= Database::fetch_row($res_course);	
		
	$course_code = $row_course[0];
          				 
	if (empty($course_code)) {
		return 0; // original_course_id_value doesn't exits
	} else {
		$sql = "SELECT code FROM $table_course WHERE code ='$course_code' AND visibility = '0'";
		$resul = api_sql_query($sql,__FILE__,__LINE__);
		$r_check_code = Database::fetch_row($resul);
		if (!empty($r_check_code[0])) {
			return 0; // this code is not active
		}
	}  
   
	if (!is_array($user_id)) {
		$user_id = array($user_id);
	}
	if(count($user_id) == 0) {
		return 0;
	}
	 
	$user_ids = implode(',', $user_id);
	
    $course_code = Database::escape_string($course_code);    
		
	$sql = "DELETE FROM $table_course_user WHERE user_id IN (".$user_ids.") AND course_code = '".$course_code."'";
	api_sql_query($sql, __FILE__, __LINE__);		
	$return = Database::affected_rows(); 
	return $return;	
}

/* Register DokeosWebServiceSuscribeUsersToSession function */
// Register the data structures used by the service
$server->wsdl->addComplexType(
'originalUsersList',
'complexType',
'array',
'',
'SOAP-ENC:Array',
array(),
array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'string[]')),'xsd:string'
);

$server->wsdl->addComplexType(
	'subscribeUsersToSession',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'original_session_id_value' => array('name' => 'original_session_id_value', 'type' => 'xsd:string'),
		'original_session_id_name' => array('name' => 'original_session_id_name', 'type' => 'xsd:string'),
		'original_user_id_values' => array('name' => 'original_user_id_values', 'type' => 'tns:originalUsersList'),
		'original_user_id_name' => array('name' => 'original_user_id_name', 'type' => 'xsd:string'),		
		'empty_users' => array('name' => 'empty_users', 'type' => 'xsd:boolean'),
		'secret_key' => array('name' => 'secret_key', 'type' => 'xsd:string')	
	)
);

// Register the method to expose
$server->register('DokeosWebServiceSuscribeUsersToSession',				// method name
	array('subscribeUsersToSession' => 'tns:subscribeUsersToSession'),	// input parameters
	array('return' => 'xsd:string'),									// output parameters
	'urn:WSRegistration',												// namespace
	'urn:WSRegistration#DokeosWebServiceSuscribeUsersToSession',		// soapaction
	'rpc',																// style
	'encoded',															// use
	'This service subscribes a user to a session' 						// documentation
);

// define the method DokeosWebServiceSuscribeUsersToSession
function DokeosWebServiceSuscribeUsersToSession($params){
 	
 	global $_configuration;
 	
 	$secret_key = $params['secret_key'];
 	
 	if ( $secret_key != $_configuration['security_key']) {
   		return -1;
   	}
   	 
   	$original_session_id_value = $params['original_session_id_value'];
	$original_session_id_name = $params['original_session_id_name'];
	$original_user_id_name = $params['original_user_id_name'];
	$original_user_id_values = $params['original_user_id_values'];
	
	$user_table = Database::get_main_table(TABLE_MAIN_USER);
 	$t_uf = Database::get_main_table(TABLE_MAIN_USER_FIELD);		
	$t_ufv = Database::get_main_table(TABLE_MAIN_USER_FIELD_VALUES);		
	$t_sf = Database::get_main_table(TABLE_MAIN_SESSION_FIELD);		
	$t_sfv = Database::get_main_table(TABLE_MAIN_SESSION_FIELD_VALUES);
	
	// get session id from original session id
	$sql_session = "SELECT session_id FROM $t_sf sf,$t_sfv sfv WHERE sfv.field_id=sf.id AND field_variable='$original_session_id_name' AND field_value='$original_session_id_value'";		
	$res_session = api_sql_query($sql_session,__FILE__,__LINE__);
	$row_session = Database::fetch_row($res_session);	
 	 	
 	$id_session = $row_session[0];
 	
 	if (empty($id_session)) {
		return 0;
	}
 	
 	$UserList = array();
 	foreach ($original_user_id_values as $row_original_user_list) {
 		$sql_user = "SELECT user_id FROM $t_uf uf,$t_ufv ufv WHERE ufv.field_id=uf.id AND field_variable='$original_user_id_name' AND field_value = '$row_original_user_list'";
 		$res_user = api_sql_query($sql_user,__FILE__,__LINE__);
 		$row_user = Database::fetch_row($res_user); 		
 		if (empty($row_user[0])) {
	    	continue; // user_id don't exist'
	    } else {
			$sql = "SELECT user_id FROM $user_table WHERE user_id ='".$row_user[0]."' AND active= '0'";
			$resu = api_sql_query($sql,__FILE__,__LINE__);
			$r_check_user = Database::fetch_row($resu);
			if (!empty($r_check_user[0])) {
				continue; // user_id is not active 
			}
	    }
	    $UserList[] = $row_user[0];	     		
 	}
	
	if (empty($UserList)) {
		return 0;
	}
	 	 
 	$empty_users=$params['empty_users'];
 	
  	if ($id_session!= strval(intval($id_session))) return 0;
   	foreach($UserList as $intUser){
   		if ($intUser!= strval(intval($intUser))) return 0;
   	}
   	$tbl_session_rel_course				= Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
	$tbl_session_rel_course_rel_user	= Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
   	$tbl_session_rel_user 				= Database::get_main_table(TABLE_MAIN_SESSION_USER);
   	$tbl_session						= Database::get_main_table(TABLE_MAIN_SESSION);
   	
   	$sql = "SELECT id_user FROM $tbl_session_rel_user WHERE id_session='$id_session'";
	$result = api_sql_query($sql,__FILE__,__LINE__);
	$existingUsers = array();
	while($row = Database::fetch_array($result)){
		$existingUsers[] = $row['id_user'];
	}
	$sql = "SELECT course_code FROM $tbl_session_rel_course WHERE id_session='$id_session'";
	$result=api_sql_query($sql,__FILE__,__LINE__);
	$CourseList=array();

	while($row=Database::fetch_array($result)) {
		$CourseList[]=$row['course_code'];
	}

	foreach ($CourseList as $enreg_course) {
		// for each course in the session
		$nbr_users=0;
	    $enreg_course = Database::escape_string($enreg_course);
			// delete existing users
		if ($empty_users!==false) {
			foreach ($existingUsers as $existing_user) {
				if(!in_array($existing_user, $UserList)) {
					$sql = "DELETE FROM $tbl_session_rel_course_rel_user WHERE id_session='$id_session' AND course_code='$enreg_course' AND id_user='$existing_user'";
					api_sql_query($sql,__FILE__,__LINE__);
	
					if(Database::affected_rows()) {
						$nbr_users--;
					}
				}
			}
		}
		// insert new users into session_rel_course_rel_user and ignore if they already exist
		foreach ($UserList as $enreg_user) {
			if(!in_array($enreg_user, $existingUsers)) {
	               $enreg_user = Database::escape_string($enreg_user);
				$insert_sql = "INSERT IGNORE INTO $tbl_session_rel_course_rel_user(id_session,course_code,id_user) VALUES('$id_session','$enreg_course','$enreg_user')";
				api_sql_query($insert_sql,__FILE__,__LINE__);
					if(Database::affected_rows()) {
					$nbr_users++;
				}
			}
		}
		// count users in this session-course relation
		$sql = "SELECT COUNT(id_user) as nbUsers FROM $tbl_session_rel_course_rel_user WHERE id_session='$id_session' AND course_code='$enreg_course'";
		$rs = api_sql_query($sql, __FILE__, __LINE__);
		list($nbr_users) = Database::fetch_array($rs);
		// update the session-course relation to add the users total
		$update_sql = "UPDATE $tbl_session_rel_course SET nbr_users=$nbr_users WHERE id_session='$id_session' AND course_code='$enreg_course'";
		api_sql_query($update_sql,__FILE__,__LINE__);
		}
		// delete users from the session
	if ($empty_users!==false){
		api_sql_query("DELETE FROM $tbl_session_rel_user WHERE id_session = $id_session",__FILE__,__LINE__);
	}
		// insert missing users into session
	$nbr_users = 0;
	foreach ($UserList as $enreg_user) {
        $enreg_user = Database::escape_string($enreg_user);
		$nbr_users++;
		$insert_sql = "INSERT IGNORE INTO $tbl_session_rel_user(id_session, id_user) VALUES('$id_session','$enreg_user')";
		api_sql_query($insert_sql,__FILE__,__LINE__);
	}
	// update number of users in the session
	$nbr_users = count($UserList);
	$update_sql = "UPDATE $tbl_session SET nbr_users= $nbr_users WHERE id='$id_session' ";
	api_sql_query($update_sql,__FILE__,__LINE__);
	$return = Database::affected_rows();
	if (!empty($result)) {
		return 1;	
	} else {
		return $return;
	}
	
}

// Use the request to (try to) invoke the service
$HTTP_RAW_POST_DATA = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : '';
$server->service($HTTP_RAW_POST_DATA);
?>