<?php
/* For licensing terms, see /license.txt */
/**
 *
 *                             SCRIPT PURPOSE
 *
 * This script initializes and manages Chamilo session information. It
 * keeps available session information up to date.
 *
 * You can request a course id. It will check if the course Id requested is the
 * same as the current one. If it isn't it will update session information from
 * the database. You can also force the course reset if you want ($cidReset).
 *
 * All the course information is stored in the $_course array.
 *
 * You can request a group id. The script will check if the group id requested is the
 * same as the current one. If it isn't it will update session information from
 * the database. You can also force the course reset if you want ($gidReset).
 *
 * The course id is stored in $_cid session variable.
 * The group  id is stored in $_gid session variable.
 *
 *
 *                    VARIABLES AFFECTING THE SCRIPT BEHAVIOR
 *
 * string  $login
 * string  $password
 * boolean $logout
 *
 * string  $cidReq   : course id requested
 * boolean $cidReset : ask for a course Reset, if no $cidReq is provided in the
 *                     same time, all course informations is removed from the
 *                     current session
 *
 * int     $gidReq   : group Id requested
 * boolean $gidReset : ask for a group Reset, if no $gidReq is provided in the
 *                     same time, all group informations is removed from the
 *                     current session
 *
 *
 *                   VARIABLES SET AND RETURNED BY THE SCRIPT
 *
 * All the variables below are set and returned by this script.
 *
 * USER VARIABLES
 *
 * string    $_user ['firstName'   ]
 * string    $_user ['lastName'    ]
 * string    $_user ['mail'        ]
 * string    $_user ['lastLogin'   ]
 * string    $_user ['official_code']
 * string    $_user ['picture_uri'  ]
 * string    $_user['user_id']
 *
 * boolean $is_platformAdmin
 * boolean $is_allowedCreateCourse
 *
 * COURSE VARIABLES
 *
 * string  $_cid (the course id)
 *
 * int     $_course['id'          ] - auto-assigned integer
 * string  $_course['name'        ] - the title of the course
 * string  $_course['official_code']    - the visual / fake / official code
 * string  $_course['sysCode'     ]
 * string  $_course['path'        ]
 * string  $_course['dbName'      ]
 * string  $_course['dbNameGlu'   ]
 * string  $_course['titular'     ]
 * string  $_course['language'    ]
 * string  $_course['extLink'     ]['url' ]
 * string  $_course['extLink'     ]['name']
 * string  $_course['categoryCode']
* string  $_course['categoryName']

* boolean $is_courseMember
* boolean $is_courseTutor
* boolean $is_courseAdmin
*
*
* GROUP VARIABLES
*
* int     $_gid (the group id)
*
*
*                       IMPORTANT ADVICE FOR DEVELOPERS
*
* We strongly encourage developers to use a connection layer at the top of
* their scripts rather than use these variables, as they are, inside the core
* of their scripts. It will make code maintenance much easier.
*
*    Many if the functions you need you can already find in the
*    main_api.lib.php
*
* We encourage you to use functions to access these global "kernel" variables.
* You can add them to e.g. the main API library.
*
*
*                               SCRIPT STRUCTURE
*
* 1. The script determines if there is an authentication attempt. This part
* only chek if the login name and password are valid. Afterwards, it set the
* $_user['user_id'] (user id) and the $uidReset flag. Other user informations are retrieved
* later. It's also in this section that optional external authentication
* devices step in.
*
* 2. The script determines what other session informations have to be set or
* reset, setting correctly $cidReset (for course) and $gidReset (for group).
*
* 3. If needed, the script retrieves the other user informations (first name,
		* last name, ...) and stores them in session.
*
* 4. If needed, the script retrieves the course information and stores them
* in session
*
* 5. The script initializes the user permission status and permission for the
* course level
*
* 6. If needed, the script retrieves group informations an store them in
* session.
*
* 7. The script initializes the user status and permission for the group level.
*
*    @package chamilo.include
*/

/*
	 INIT SECTION
	 variables should be initialised here
 */

require_once api_get_path(LIBRARY_PATH).'conditionallogin.lib.php';
// verified if exists the username and password in session current

if (isset($_SESSION['info_current_user'][1]) && isset($_SESSION['info_current_user'][2])) {	
	require_once api_get_path(LIBRARY_PATH).'legal.lib.php';
}

//Conditional login
if (isset($_SESSION['conditional_login']['uid']) && $_SESSION['conditional_login']['can_login']=== true){	
	$uData = UserManager::get_user_info_by_id($_SESSION['conditional_login']['uid']);
	ConditionalLogin::check_conditions($uData);
	
	$_user['user_id'] = $_SESSION['conditional_login']['uid'];
	$_user['status']  = $uData['status'];
	api_session_register('_user');
	api_session_unregister('conditional_login');
	$uidReset=true;
	event_login();
} 

// parameters passed via GET
$logout = isset($_GET["logout"]) ? $_GET["logout"] : '';
$gidReq = isset($_GET["gidReq"]) ? Database::escape_string($_GET["gidReq"]) : '';

//this fixes some problems with generic functionalities like
//My Agenda & What's New icons linking to courses
// $cidReq can be set in the index.php file of a course-area
$cidReq = isset($cidReq) ? Database::escape_string($cidReq) : '';
// $cidReq can be set in URL-parameter
$cidReq = isset($_GET["cidReq"]) ? Database::escape_string($_GET["cidReq"]) : $cidReq;

$cidReset = isset($cidReset) ? Database::escape_string($cidReset) : '';

// $cidReset can be set in URL-parameter
$cidReset = (isset($_GET['cidReq']) && ((isset($_SESSION['_cid']) && $_GET['cidReq']!=$_SESSION['_cid']) || (!isset($_SESSION['_cid'])))) ? Database::escape_string($_GET["cidReq"]) : $cidReset;

// $cDir is a special url param sent by courses/.htaccess
$cDir = (!empty($_GET['cDir']) ? $_GET['cDir'] : null);

$gidReset = isset($gidReset) ? $gidReset : '';
// $gidReset can be set in URL-parameter

// parameters passed via POST
$login = isset($_POST["login"]) ? $_POST["login"] : '';

// passed through other means
//$cidReq -- passed from course folder index.php

/*
	 MAIN CODE
 */

if (!empty($_SESSION['_user']['user_id']) && ! ($login || $logout)) {
	// uid is in session => login already done, continue with this value
	$_user['user_id'] = $_SESSION['_user']['user_id'];
} else {
	if (isset($_user['user_id'])) {
		unset($_user['user_id']);
	}

	//$_SESSION['info_current_user'][1] is user name
	//$_SESSION['info_current_user'][2] is current password encrypted
	//$_SESSION['update_term_and_condition'][1] is current user id, of user in session
	if (api_get_setting('allow_terms_conditions')=='true') {
		if (isset($_POST['login']) && isset($_POST['password']) && isset($_SESSION['update_term_and_condition'][1])) {
			$user_id=$_SESSION['update_term_and_condition'][1];    // user id
			// update the terms & conditions

			//verify type of terms and conditions
			$info_legal = explode(':',$_POST['legal_info']);
			$legal_type = LegalManager::get_type_of_terms_and_conditions($info_legal[0],$info_legal[1]);

			//is necessary verify check
			if ($legal_type==1) {
				if ((isset($_POST['legal_accept']) && $_POST['legal_accept']=='1')) {
					$legal_option=true;
				} else {
					$legal_option=false;

				}
			}
			//no is check option
			if ($legal_type==0) {
				$legal_option=true;
			}

			if (isset($_POST['legal_accept_type']) && $legal_option===true) {
				$cond_array = explode(':',$_POST['legal_accept_type']);
				if (!empty($cond_array[0]) && !empty($cond_array[1])){
					$time = time();
					$condition_to_save = intval($cond_array[0]).':'.intval($cond_array[1]).':'.$time;
					UserManager::update_extra_field_value($user_id,'legal_accept',$condition_to_save);
				}
			}
		}
	}

	//IF cas is activated and user isn't logged in	
	if (api_get_setting('cas_activate') == 'true') { 
		$cas_activated = true;
	} else {
		$cas_activated = false;
	}

	$cas_login=false;
	if ($cas_activated  AND !isset($_user['user_id']) and !isset($_POST['login'])  && !$logout) { 
		require_once(api_get_path(SYS_PATH).'main/auth/cas/authcas.php');
		$cas_login = cas_is_authenticated();
	}
	if ( ( isset($_POST['login']) AND  isset($_POST['password']) ) OR ($cas_login) )  {
		// $login && $password are given to log in
		if ( $cas_login  && empty($_POST['login']) ) {
			$login = $cas_login;
		} else {
			$login      = $_POST['login'];
			$password   = $_POST['password'];
		}

		//lookup the user in the main database
		$user_table = Database::get_main_table(TABLE_MAIN_USER);
		$sql = "SELECT user_id, username, password, auth_source, active, expiration_date, status FROM $user_table
			    WHERE username = '".Database::escape_string($login)."'";
		$result = Database::query($sql);

		if (Database::num_rows($result) > 0) {
			$uData = Database::fetch_array($result);

			if ($uData['auth_source'] == PLATFORM_AUTH_SOURCE) {
				//the authentification of this user is managed by Chamilo itself
				$password = trim(stripslashes($password));

				if (api_get_setting('allow_terms_conditions')=='true') {
					if (isset($_POST['password']) && isset($_SESSION['info_current_user'][2]) && $_POST['password']==$_SESSION['info_current_user'][2]) {
						$password=$_POST['password'];
					} else {
                        $password = api_get_encrypted_password($password);
					}
				} else {
					$password = api_get_encrypted_password($password);
				}
				if (api_get_setting('allow_terms_conditions')=='true') {
					if ($password == $uData['password'] AND (trim($login) == $uData['username']) OR $cas_login ) {
						$temp_user_id = $uData['user_id'];
						$term_and_condition_status=api_check_term_condition($temp_user_id);//false or true
						if ($term_and_condition_status===false) {
							$_SESSION['update_term_and_condition']=array(true,$temp_user_id);
							$_SESSION['info_current_user']=array(true,$login,$password);
							header('Location: '.api_get_path(WEB_CODE_PATH).'auth/inscription.php');
							exit;
						} else {
							unset($_SESSION['update_term_and_condition']);
							unset($_SESSION['info_current_user']);
						}
					}
				}

				// Check the user's password
				if ( ($password == $uData['password']  OR $cas_login) AND (trim($login) == $uData['username'])) {
                    require_once(api_get_path(LIBRARY_PATH).'usermanager.lib.php');
                    $update_type = UserManager::get_extra_user_data_by_field($uData['user_id'], 'update_type');
                    $update_type= $update_type['update_type'];
                    if (!empty($extAuthSource[$update_type]['updateUser']) && file_exists($extAuthSource[$update_type]['updateUser'])) {
                        include_once($extAuthSource[$update_type]['updateUser']);
                    }
					// Check if the account is active (not locked)
					if ($uData['active']=='1') {
                        
						// Check if the expiration date has not been reached
                        if ($uData['expiration_date'] > date('Y-m-d H:i:s') OR $uData['expiration_date'] == '0000-00-00 00:00:00') {
							global $_configuration;

                            if (isset($_configuration['multiple_access_urls']) && $_configuration['multiple_access_urls']) {
								$admin_table = Database::get_main_table(TABLE_MAIN_ADMIN);

								//Check if user is an admin
								$sql = "SELECT user_id FROM $admin_table
										WHERE user_id = '".intval($uData['user_id'])."' LIMIT 1";
								$result = Database::query($sql);

								$my_user_is_admin = false;
								if (Database::num_rows($result) > 0) {
									$my_user_is_admin = true;
								}

								// This user is subscribed in these sites => $my_url_list
								$my_url_list = api_get_access_url_from_user($uData['user_id']);

								//Check the access_url configuration setting if the user is registered in the access_url_rel_user table
								//Getting the current access_url_id of the platform
								$current_access_url_id = api_get_current_access_url_id();

								if ($my_user_is_admin === false) {

									if (is_array($my_url_list) && count($my_url_list)>0 ) {
										// the user have the permissions to enter at this site
										if (in_array($current_access_url_id, $my_url_list)) {
											ConditionalLogin::check_conditions($uData);
                                            
											$_user['user_id'] = $uData['user_id'];
											$_user['status']  = $uData['status'];
                                            session_regenerate_id();
											api_session_register('_user');
											event_login();
										} else {
											$loginFailed = true;
											api_session_unregister('_uid');
											header('Location: '.api_get_path(WEB_PATH).'index.php?loginFailed=1&error=access_url_inactive');
											exit;
										}
									} else {
										$loginFailed = true;
										api_session_unregister('_uid');
										header('Location: '.api_get_path(WEB_PATH).'index.php?loginFailed=1&error=access_url_inactive');
										exit;
									}
								} else { //Only admins of the "main" (first) Chamilo portal can login wherever they want
									//var_dump($current_access_url_id, $my_url_list); exit;
									if (in_array(1, $my_url_list)) { //Check if this admin have the access_url_id = 1 which means the principal
										ConditionalLogin::check_conditions($uData);
										$_user['user_id'] = $uData['user_id'];
										$_user['status']  = $uData['status'];
										session_regenerate_id();
										api_session_register('_user');
										event_login();
									} else {
										//This means a secondary admin wants to login so we check as he's a normal user
										if (in_array($current_access_url_id, $my_url_list)) {
											$_user['user_id'] = $uData['user_id'];
											$_user['status']  = $uData['status'];
											session_regenerate_id();
											api_session_register('_user');
											event_login();
										} else {
											$loginFailed = true;
											api_session_unregister('_uid');
											header('Location: '.api_get_path(WEB_PATH).'index.php?loginFailed=1&error=access_url_inactive');
											exit;
										}
									}
								}
                            } else {
								ConditionalLogin::check_conditions($uData);
                                $_user['user_id'] = $uData['user_id'];
                                $_user['status']  = $uData['status'];
                                
                                session_regenerate_id();                                                           
                                api_session_register('_user');
                                event_login();                                
							}
						} else {
							$loginFailed = true;
							api_session_unregister('_uid');
							header('Location: '.api_get_path(WEB_PATH).'index.php?loginFailed=1&error=account_expired');
							exit;
						}
					} else {
						$loginFailed = true;
						api_session_unregister('_uid');
						header('Location: '.api_get_path(WEB_PATH).'index.php?loginFailed=1&error=account_inactive');
						exit;
					}
				} else {
					// login failed: username or password incorrect
					$loginFailed = true;
					api_session_unregister('_uid');
					header('Location: '.api_get_path(WEB_PATH).'index.php?loginFailed=1&error=user_password_incorrect');
					exit;
				}

				if (isset($uData['creator_id']) && $_user['user_id'] != $uData['creator_id']) {
					//first login for a not self registred
					//e.g. registered by a teacher
					//do nothing (code may be added later)
				}
			} elseif (!empty($extAuthSource[$uData['auth_source']]['login']) && file_exists($extAuthSource[$uData['auth_source']]['login'])) {
				/*
				 * Process external authentication
				 * on the basis of the given login name
				 */
				$loginFailed = true;  // Default initialisation. It could
				// change after the external authentication
				$key = $uData['auth_source']; //'ldap','shibboleth'...
				/* >>>>>>>> External authentication modules <<<<<<<<< */
				// see configuration.php to define these
				include_once($extAuthSource[$key]['login']);
				/* >>>>>>>> External authentication modules <<<<<<<<< */
			} else { // no standard Chamilo login - try external authentification
				//huh... nothing to do... we shouldn't get here
				error_log('Chamilo Authentication file '. $extAuthSource[$uData['auth_source']]['login']. ' could not be found - this might prevent your system from doing the corresponding authentication process',0);
			}
		} else {
			// login failed, Database::num_rows($result) <= 0
			$loginFailed = true;  // Default initialisation. It could
			// change after the external authentication

			/*
			 * In this section:
			 * there is no entry for the $login user in the Chamilo
			 * database. This also means there is no auth_source for the user.
			 * We let all external procedures attempt to add him/her
			 * to the system.
			 *
			 * Process external login on the basis
			 * of the authentication source list
			 * provided by the configuration settings.
			 * If the login succeeds, for going further,
			 * Chamilo needs the $_user['user_id'] variable to be
			 * set and registered in the session. It's the
			 * responsability of the external login script
			 * to provide this $_user['user_id'].
			 */

			if (isset($extAuthSource) && is_array($extAuthSource)) {
				foreach($extAuthSource as $thisAuthSource) {
					if (!empty($thisAuthSource['newUser']) && file_exists($thisAuthSource['newUser'])) {
						include_once($thisAuthSource['newUser']);
					} else {
						error_log('Chamilo Authentication file '. $thisAuthSource['newUser']. ' could not be found - this might prevent your system from using the authentication process in the user creation process',0);
					}
				}
			} //end if is_array($extAuthSource)
			if ($loginFailed) { //If we are here username given is wrong
				header('Location: '.api_get_path(WEB_PATH).'index.php?loginFailed=1&error=user_password_incorrect');
			}
		} //end else login failed
	} elseif (api_get_setting('sso_authentication')==='true' &&  !in_array('webservices', explode('/', $_SERVER['REQUEST_URI']))) {
		/**
		 * TODO:
		 * - Work on a better validation for webservices paths. Current is very poor and exit
		 */
		$subsso = api_get_setting('sso_authentication_subclass');
		require_once(api_get_path(SYS_CODE_PATH).'auth/sso/sso.class.php');
		if (!empty($subsso)) {
			require_once(api_get_path(SYS_CODE_PATH).'auth/sso/sso.'.$subsso.'.class.php');
			$subsso = 'sso'.$subsso;
			$osso = new $subsso(); //load the subclass
		} else {
			$osso = new sso();
		}
		if (isset($_SESSION['_user']['user_id'])) {
			if ($logout) {
                
				// Prevent index.php to redirect
				global $logout_no_redirect;
				$logout_no_redirect = TRUE;
				// Make custom redirect after logout
				online_logout();
				$osso->logout(); //redirects and exits
			}
		} elseif(!$logout) {
			// Handle cookie comming from Master Server
			if (!isset($_GET['sso_referer']) && !isset($_GET['loginFailed'])) {
				// Redirect to master server
				$osso->ask_master();
			} elseif (isset($_GET['sso_cookie'])) {
				$protocol = api_get_setting('sso_authentication_protocol');
				$master_url = api_get_setting('sso_authentication_domain').api_get_setting('sso_authentication_auth_uri');
				if (isset($_GET['sso_referer']) ? $_GET['sso_referer'] === $protocol.$master_url  : FALSE) {
					//make all the process of checking 
					//if the user exists (delegated to the sso class)
					$osso->check_user();
				} else {
					//Request comes from unknown source
					$loginFailed = true;
					api_session_unregister('_uid');
					header('Location: '.api_get_path(WEB_PATH).'index.php?loginFailed=1&error=unrecognize_sso_origin');
					exit;
				}
			}
		}//end logout
	} elseif (api_get_setting('openid_authentication')=='true') {
		if (!empty($_POST['openid_url'])) {
			include 'main/auth/openid/login.php';
			openid_begin(trim($_POST['openid_url']),api_get_path(WEB_PATH).'index.php');
			//this last function should trigger a redirect, so we can die here safely
			die('Openid login redirection should be in progress');
		} elseif (!empty($_GET['openid_identity'])) {
			//it's usual for PHP to replace '.' (dot) by '_' (underscore) in URL parameters
			include('main/auth/openid/login.php');
			$res = openid_complete($_GET);
			if ($res['status'] == 'success') {
				$id1 = Database::escape_string($res['openid.identity']);
				//have another id with or without the final '/'
				$id2 = (substr($id1,-1,1)=='/'?substr($id1,0,-1):$id1.'/');
				//lookup the user in the main database
				$user_table = Database::get_main_table(TABLE_MAIN_USER);
				$sql = "SELECT user_id, username, password, auth_source, active, expiration_date
					FROM $user_table
					WHERE openid = '$id1'
					OR openid = '$id2' ";
				$result = Database::query($sql);
				if ($result !== false) {
					if (Database::num_rows($result)>0) {
						//$row = Database::fetch_array($res);
						$uData = Database::fetch_array($result);

						if ($uData['auth_source'] == PLATFORM_AUTH_SOURCE) {
							//the authentification of this user is managed by Chamilo itself

							// check if the account is active (not locked)
							if ($uData['active']=='1') {
								// check if the expiration date has not been reached
								if ($uData['expiration_date']>date('Y-m-d H:i:s') OR $uData['expiration_date']=='0000-00-00 00:00:00') {
									$_user['user_id'] = $uData['user_id'];
									$_user['status']  = $uData['status'];
									session_regenerate_id();
									api_session_register('_user');
									event_login();
								} else {
									$loginFailed = true;
									api_session_unregister('_uid');
									header('Location: index.php?loginFailed=1&error=account_expired');
									exit;
								}
							} else {
								$loginFailed = true;
								api_session_unregister('_uid');
								header('Location: index.php?loginFailed=1&error=account_inactive');
								exit;
							}
							if (isset($uData['creator_id']) && $_user['user_id'] != $uData['creator_id']) {
								//first login for a not self registred
								//e.g. registered by a teacher
								//do nothing (code may be added later)
							}
						}
					} else {
						//Redirect to the subscription form
						header('Location: '.api_get_path(WEB_CODE_PATH).'auth/inscription.php?username='.$res['openid.sreg.nickname'].'&email='.$res['openid.sreg.email'].'&openid='.$res['openid.identity'].'&openid_msg=idnotfound');
						//$loginFailed = true;
					}
				} else {
					$loginFailed = true;
				}
			} else {
				$loginFailed = true;
			}
		}
	}

	//    else {} => continue as anonymous user
	$uidReset = true;

	//    $cidReset = true;
	//    $gidReset = true;
} // end else

//Now check for anonymous user mode
if (isset($use_anonymous) && $use_anonymous) {
	//if anonymous mode is set, then try to set the current user as anonymous
	//if he doesn't have a login yet
	api_set_anonymous();
} else {
	//if anonymous mode is not set, then check if this user is anonymous. If it
	//is, clean it from being anonymous (make him a nobody :-))
	api_clear_anonymous();
}

// if there is a cDir parameter in the URL (coming from courses/.htaccess redirection)
if (!empty($cDir)) {    
	$c = CourseManager::get_course_id_from_path($cDir);
	if ($c) { $cidReq = $c; }
}

// if the requested course is different from the course in session

if (!empty($cidReq) && (!isset($_SESSION['_cid']) or (isset($_SESSION['_cid']) && $cidReq != $_SESSION['_cid']))) {
	$cidReset = true;
	$gidReset = true;    // As groups depend from courses, group id is reset
}

// if the requested group is different from the group in session
$gid = isset($_SESSION['_gid']) ? $_SESSION['_gid'] : '';
if ($gidReq && $gidReq != $gid) {
	$gidReset = true;
}


/* USER INIT */

if (isset($uidReset) && $uidReset) {    // session data refresh requested
    $is_platformAdmin       = false; 
    $is_allowedCreateCourse = false;

    if (isset($_user['user_id']) && $_user['user_id']) {
        // a uid is given (log in succeeded)
		$user_table     = Database::get_main_table(TABLE_MAIN_USER);
		$admin_table    = Database::get_main_table(TABLE_MAIN_ADMIN);
		$track_e_login  = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_LOGIN);

		$sql = "SELECT user.*, a.user_id is_admin, UNIX_TIMESTAMP(login.login_date) login_date
			FROM $user_table
			LEFT JOIN $admin_table a
			ON user.user_id = a.user_id
			LEFT JOIN $track_e_login login
			ON user.user_id  = login.login_user_id
			WHERE user.user_id = '".$_user['user_id']."'
			ORDER BY login.login_date DESC LIMIT 1";    

			$result = Database::query($sql);

		if (Database::num_rows($result) > 0) {
			// Extracting the user data

			$uData = Database::fetch_array($result);

			$_user ['firstName']        = $uData ['firstname' ];
			$_user ['lastName' ]        = $uData ['lastname'  ];
			$_user ['mail'     ]        = $uData ['email'     ];
			$_user ['lastLogin']        = $uData ['login_date'];
			$_user ['official_code']    = $uData ['official_code'];
			$_user ['picture_uri']      = $uData ['picture_uri'];
			$_user ['user_id']          = $uData ['user_id'];
			$_user ['language']         = $uData ['language'];
			$_user ['auth_source']      = $uData ['auth_source'];
			$_user ['theme']            = $uData ['theme'];
			$_user ['status']           = $uData ['status'];

			$is_platformAdmin           = (bool) (! is_null( $uData['is_admin']));
			$is_allowedCreateCourse     = (bool) (($uData ['status'] == 1) or (api_get_setting('drhCourseManagerRights') and $uData['status'] == 4));
			ConditionalLogin::check_conditions($uData);

			api_session_register('_user');
			UserManager::update_extra_field_value($_user['user_id'], 'already_logged_in', 'true');
			api_session_register('is_platformAdmin');
			api_session_register('is_allowedCreateCourse');

      // If request_uri is settd we have to go further to have course permissions
      if (empty($_SESSION['request_uri']) || !isset($_SESSION['request_uri'])) {
        require_once api_get_path(LIBRARY_PATH).'loginredirection.lib.php';
        LoginRedirection::redirect();
      }

		} else {
			header('location:'.api_get_path(WEB_PATH));
			//exit("WARNING UNDEFINED UID !! ");
		}
	} else { // no uid => logout or Anonymous
		api_session_unregister('_user');
		api_session_unregister('_uid');
	}
    
	api_session_register('is_platformAdmin');
	api_session_register('is_allowedCreateCourse');
} else { // continue with the previous values
    $_user                    = $_SESSION['_user'];
    $is_platformAdmin         = $_SESSION['is_platformAdmin'];
    $is_allowedCreateCourse   = $_SESSION['is_allowedCreateCourse'];
}

/*  COURSE INIT */

if (isset($cidReset) && $cidReset) { // course session data refresh requested or empty data    
	if ($cidReq) {
		$course_table = Database::get_main_table(TABLE_MAIN_COURSE);
		$course_cat_table = Database::get_main_table(TABLE_MAIN_CATEGORY);
		$sql =  "SELECT course.*, course_category.code faCode, course_category.name faName
			FROM $course_table
			LEFT JOIN $course_cat_table
			ON course.category_code = course_category.code
			WHERE course.code = '$cidReq'";
		$result = Database::query($sql);

		if (Database::num_rows($result) > 0) {
			$course_data = Database::fetch_array($result);
			//@TODO real_cid should be cid, for working with numeric course id
			$_real_cid                      = $course_data['id'];

			$_cid                           = $course_data['code'];
			$_course = array();
			$_course['real_id']             = $course_data['id'];
			$_course['id']                  = $course_data['code']; //auto-assigned integer
			$_course['code']                = $course_data['code']; 
			$_course['name']                = $course_data['title'];
			$_course['official_code']       = $course_data['visual_code']; // use in echo
			$_course['sysCode']             = $course_data['code']; // use as key in db
			$_course['path']                = $course_data['directory']; // use as key in path
			$_course['dbName']              = $course_data['db_name']; // use as key in db list
			$_course['db_name']             = $course_data['db_name']; // not needed in Chamilo 1.8
			$_course['dbNameGlu']           = $_configuration['table_prefix'] . $course_data['db_name'] . $_configuration['db_glue']; // use in all queries //not needed in Chamilo 1.8
			$_course['titular']             = $course_data['tutor_name'];// this should be deprecated and use the table course_rel_user
			$_course['language']            = $course_data['course_language'];
			$_course['extLink']['url' ]     = $course_data['department_url'];
			$_course['extLink']['name']     = $course_data['department_name'];
			$_course['categoryCode']        = $course_data['faCode'];
			$_course['categoryName']        = $course_data['faName'];
			$_course['visibility']          = $course_data['visibility'];
			$_course['subscribe_allowed']   = $course_data['subscribe'];
			$_course['unubscribe_allowed']  = $course_data['unsubscribe'];
            $_course['activate_legal']      = $course_data['activate_legal'];

			api_session_register('_cid');
			api_session_register('_course');
			//@TODO real_cid should be cid, for working with numeric course id
			api_session_register('_real_cid');

			// if a session id has been given in url, we store the session
			if (api_get_setting('use_session_mode') == 'true') {
				// Database Table Definitions
				$tbl_session                 = Database::get_main_table(TABLE_MAIN_SESSION);				
				$tbl_session_course          = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
				$tbl_session_course_user     = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

				if (!empty($_GET['id_session'])) {
					$_SESSION['id_session'] = intval($_GET['id_session']);
					$sql = 'SELECT name FROM '.$tbl_session . ' WHERE id="'.intval($_SESSION['id_session']) . '"';
					$rs = Database::query($sql);
					list($_SESSION['session_name']) = Database::fetch_array($rs);
				} else {
					api_session_unregister('session_name');
					api_session_unregister('id_session');
				}
			}

            if (!isset($_SESSION['login_as'])) {
				//Course login
				event_course_login($_course['sysCode'], $_user['user_id'], api_get_session_id());
            }
        } else {
            //exit("WARNING UNDEFINED CID !! ");
            header('location:'.api_get_path(WEB_PATH));
        }
    } else {
        api_session_unregister('_cid');
        api_session_unregister('_real_cid');
        api_session_unregister('_course');
        
        if (!empty($_SESSION)) {
                foreach($_SESSION as $key=>$session_item) {
                if (strpos($key,'lp_autolunch_') === false) {
                    continue;               
                } else {
                    if(isset($_SESSION[$key])) {
                        api_session_unregister($key);   
                    }
                }
            }   
        }             
        //Deleting session info 
        if (api_get_session_id()) {                
            api_session_unregister('id_session');
            api_session_unregister('session_name');
        }   
    }
} else { // continue with the previous values
    if (empty($_SESSION['_course']) OR empty($_SESSION['_cid'])) { //no previous values...
        $_cid         = -1;        //set default values that will be caracteristic of being unset
        $_course      = -1;
    } else {
        $_cid         = $_SESSION['_cid'   ];
           $_course   = $_SESSION['_course'];

           // these lines are usefull for tracking. Indeed we can have lost the id_session and not the cid.
           // Moreover, if we want to track a course with another session it can be usefull        
        if (!empty($_GET['id_session'])) {
            $tbl_session                 = Database::get_main_table(TABLE_MAIN_SESSION);
            $_SESSION['id_session']      = intval($_GET['id_session']);
            $sql = 'SELECT name FROM '.$tbl_session . ' WHERE id="'.intval($_SESSION['id_session']). '"';
            $rs = Database::query($sql);
            list($_SESSION['session_name']) = Database::fetch_array($rs);
        }
        
        if (!isset($_SESSION['login_as'])) {            
            $save_course_access = true;
            
            //The value  $_dont_save_user_course_access should be added before the call of global.inc.php see the main/inc/chat.ajax.php file
            //Disables the updates in the TRACK_E_COURSE_ACCESS table
            if (isset($_dont_save_user_course_access) && $_dont_save_user_course_access == true) {
                $save_course_access = false;
            }
            
            if ($save_course_access) {
                $course_tracking_table = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
                
                /* 
                * When $_configuration['session_lifetime'] is too big 100 hours (in order to let users take exercises with no problems)
                * the function Tracking::get_time_spent_on_the_course() returns big values (200h) due the condition: 
                * login_course_date > now() - INTERVAL $session_lifetime SECOND
                * 
                */
                /*
                if (isset($_configuration['session_lifetime'])) {
                    $session_lifetime    = $_configuration['session_lifetime'];
                } else {
                    $session_lifetime    = 3600; // 1 hour
                }*/
                
                $session_lifetime    = 3600; // 1 hour

                $course_code = $_course['sysCode'];
                $time = api_get_datetime();

                //We select the last record for the current course in the course tracking table
                //But only if the login date is < than now + max_life_time            
                $sql = "SELECT course_access_id FROM $course_tracking_table
                        WHERE   user_id     = ".intval($_user ['user_id'])." AND
                                course_code = '$course_code' AND 
                                session_id  = ".api_get_session_id()." AND
                                login_course_date > now() - INTERVAL $session_lifetime SECOND
                            ORDER BY login_course_date DESC LIMIT 0,1";
                $result = Database::query($sql);

                if (Database::num_rows($result) > 0) {
                    $i_course_access_id = Database::result($result,0,0);
                    //We update the course tracking table
                    $sql="UPDATE $course_tracking_table  SET logout_course_date = '$time', counter = counter+1 ".
                        "WHERE course_access_id=".intval($i_course_access_id)." AND session_id = ".api_get_session_id();
                    //error_log($sql);
                    Database::query($sql);
                } else {
                    $sql="INSERT INTO $course_tracking_table (course_code, user_id, login_course_date, logout_course_date, counter, session_id)" .
                        "VALUES('".$course_code."', '".$_user['user_id']."', '$time', '$time', '1','".api_get_session_id()."')";
                    //error_log($sql);
                    Database::query($sql);
                }
            }
            
        }
    }
}

/*  COURSE / USER REL. INIT */

$session_id = api_get_session_id();

if ((isset($uidReset) && $uidReset) || (isset($cidReset) && $cidReset)) { // session data refresh requested
    if (isset($_user['user_id']) && $_user['user_id'] && isset($_cid) && $_cid) { // have keys to search data
        if (api_get_setting('use_session_mode') != 'true') {

            $course_user_table = Database::get_main_table(TABLE_MAIN_COURSE_USER);
            $sql = "SELECT * FROM $course_user_table
                   WHERE user_id  = '".$_user['user_id']."' AND relation_type <> ".COURSE_RELATION_TYPE_RRHH."
                   AND course_code = '$cidReq'";

            $result = Database::query($sql);

            if (Database::num_rows($result) > 0) { // this  user have a recorded state for this course
                $cuData = Database::fetch_array($result);
       

                $is_courseMember     = true;
                $is_courseTutor      = (bool) ($cuData['tutor_id' ] == 1 );
                $is_courseAdmin      = (bool) ($cuData['status'] == 1 );

                api_session_register('_courseUser');
            } else { // this user has no status related to this course
                $is_courseMember = false;
                $is_courseAdmin  = false;
                $is_courseTutor  = false;
            }

            $is_courseAdmin = (bool) ($is_courseAdmin || $is_platformAdmin);

        } else {

            $tbl_course_user = Database :: get_main_table(TABLE_MAIN_COURSE_USER);

            $sql = "SELECT * FROM ".$tbl_course_user."
                     WHERE  user_id  = '".$_user['user_id']."' AND 
                            relation_type<>".COURSE_RELATION_TYPE_RRHH." AND
                            course_code = '$cidReq'";
            $result = Database::query($sql);

            if (Database::num_rows($result) > 0) { // this  user have a recorded state for this course
                $cuData = Database::fetch_array($result);                
                
                if ($_course['activate_legal'] == 1 && !api_is_platform_admin()) {                    
                    $user_is_subscribed = CourseManager::is_user_accepted_legal($_user['user_id'], $_course['id'], $session_id);                    
                    if (!$user_is_subscribed) {
                        $url = api_get_path(WEB_CODE_PATH).'course_info/legal.php?course_code='.$_course['code'].'&session_id='.$session_id;
                        header('Location: '.$url);
                        exit;
                    }
                }

                $_courseUser['role'] = $cuData['role'];
                $is_courseMember     = true;
                $is_courseTutor      = (bool) ($cuData['tutor_id' ] == 1 );
                $is_courseAdmin      = (bool) ($cuData['status'] == 1 );

                api_session_register('_courseUser');
            }
            
            if (!isset($is_courseAdmin)) { // this user has no status related to this course
                // is it the session coach or the session admin ?

                $tbl_session             = Database :: get_main_table(TABLE_MAIN_SESSION);
                $tbl_session_course      = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE);
                $tbl_session_course_user = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

				//Session coach, session admin, course coach admin 
                $sql = "SELECT session.id_coach, session_admin_id, session_rcru.id_user
                		FROM $tbl_session session,$tbl_session_course_user session_rcru
					    WHERE  session_rcru.id_session = session.id AND 
					           session_rcru.course_code = '$_cid' AND 
					           session_rcru.id_user = '{$_user['user_id']}' AND 
					           session_rcru.status = 2";

				$result = Database::query($sql);
                $row 	= Database::store_result($result);
           
                if (isset($row) && isset($row[0]) && $row[0]['id_coach'] == $_user['user_id']) {
					$_courseUser['role'] = 'Professor';
	                $is_courseMember     = true;
	                $is_courseTutor      = true;
	                $is_courseCoach      = true;
	                $is_sessionAdmin     = false;
	
	                if (api_get_setting('extend_rights_for_coach')=='true') {
	                	$is_courseAdmin = true;
					} else {
	                	$is_courseAdmin = false;
					}
					api_session_register('_courseUser');
	            } elseif( isset($row) && isset($row[0]) && $row[0]['session_admin_id']==$_user['user_id']) { 
					$_courseUser['role'] = 'Professor';
	                $is_courseMember     = false;
	                $is_courseTutor      = false;
	                $is_courseAdmin      = false;
	                $is_courseCoach      = false;
	                $is_sessionAdmin     = true;
				} else {				
					// Check if the current user is the course coach
					$sql = "SELECT 1 FROM ".$tbl_session_course_user."
                            WHERE   course_code='$_cid' AND 
                                    id_user = '".$_user['user_id']."' AND
                                    id_session = '".$session_id."' AND
                                    status = 2";
					$result = Database::query($sql);
					if ($row = Database::fetch_array($result)) {
						$_courseUser['role'] = 'Professor';
						$is_courseMember     = true;
						$is_courseTutor      = true;
						$is_courseCoach      = true;
						$is_sessionAdmin     = false;

						if (api_get_setting('extend_rights_for_coach')=='true') {
							$is_courseAdmin = true;
						} else {
							$is_courseAdmin = false;
						}
						api_session_register('_courseUser');
					} else {
						if ($session_id != 0) {
							// Check if the user is a student is this session
							$sql = "SELECT * FROM ".$tbl_session_course_user."
								    WHERE   id_user      = '".$_user['user_id']."' AND
								            id_session   = '".$session_id."' AND
								            course_code  = '$cidReq' AND status NOT IN(2)";
							$result = Database::query($sql);
							if (Database::num_rows($result) > 0) { // this  user have a recorded state for this course
								while($row = Database::fetch_array($result)) {
								    
									$is_courseMember     = true;
									$is_courseTutor      = false;
									$is_courseAdmin      = false;
									$is_sessionAdmin     = false;
									api_session_register('_courseUser');
								}
							}
						} else {
							//unregister user
							$is_courseMember     = false;
							$is_courseTutor      = false;
							$is_courseAdmin      = false;
							$is_sessionAdmin     = false;
							api_session_unregister('_courseUser');
							//$_course['visibility'] = 0; this depends the
						}
					}
				}
            }
        }
    } else { // keys missing => not anymore in the course - user relation
        // course
        $is_courseMember = false;
        $is_courseAdmin  = false;
        $is_courseTutor  = false;
        $is_courseCoach  = false;
        $is_sessionAdmin     = false;
        api_session_unregister('_courseUser');
    }

	//DEPRECATED
	//$is_courseAllowed=($_cid && ($_course['visibility'] || $is_courseMember || $is_platformAdmin))?true:false;

	//NEW
	if (isset($_course)) {
		if ($_course['visibility'] == COURSE_VISIBILITY_OPEN_WORLD)
			$is_allowed_in_course = true;
		elseif ($_course['visibility'] == COURSE_VISIBILITY_OPEN_PLATFORM && isset($_user['user_id']) && !api_is_anonymous($_user['user_id']))
			$is_allowed_in_course = true;
		elseif ($_course['visibility'] == COURSE_VISIBILITY_REGISTERED && ($is_platformAdmin || $is_courseMember))
			$is_allowed_in_course = true;
		elseif ($_course['visibility'] == COURSE_VISIBILITY_CLOSED && ($is_platformAdmin || $is_courseAdmin))
			$is_allowed_in_course = true;
		else $is_allowed_in_course = false;
	}

	// requires testing!!!
	// check the session visibility
	if (!empty($is_allowed_in_course)) {        
		$my_session_id = api_get_session_id();
		//if I'm in a session
		//var_dump($is_platformAdmin, $is_courseTutor,api_is_coach());
		if ($my_session_id!=0)
			if (!$is_platformAdmin) {
				// admin and session coach are *not* affected to the invisible session mode
				// the coach is not affected because he can log in some days after the end date of a session
				$session_visibility = api_get_session_visibility($my_session_id);
				if ($session_visibility==SESSION_INVISIBLE)
					$is_allowed_in_course =false;
			}

	}

	// save the states

	api_session_register('is_courseMember');
	api_session_register('is_courseAdmin');
	//api_session_register('is_courseAllowed'); //deprecated old permission var
	api_session_register('is_courseTutor');
	api_session_register('is_allowed_in_course'); //new permission var
	api_session_register('is_courseCoach');
	api_session_register('is_sessionAdmin');
} else { // continue with the previous values

	if (isset($_SESSION ['_courseUser'])) {
		$_courseUser          = $_SESSION ['_courseUser'];
	}

	$is_courseMember      = $_SESSION ['is_courseMember' ];
	$is_courseAdmin       = $_SESSION ['is_courseAdmin'  ];
	//$is_courseAllowed     = $_SESSION ['is_courseAllowed']; //deprecated
	$is_allowed_in_course = $_SESSION ['is_allowed_in_course'];
	$is_courseTutor       = $_SESSION ['is_courseTutor'  ];
	$is_courseCoach       = $_SESSION ['is_courseCoach'  ];
}

/*  GROUP INIT */

if ((isset($gidReset) && $gidReset) || (isset($cidReset) && $cidReset)) { // session data refresh requested
	if ($gidReq && $_cid && !empty($_course['real_id'])) { // have keys to search data
		$group_table = Database::get_course_table(TABLE_GROUP);            
		$sql = "SELECT * FROM $group_table WHERE c_id = ".$_course['real_id']." AND id = '$gidReq'";
		$result = Database::query($sql);
		if (Database::num_rows($result) > 0) { // This group has recorded status related to this course
			$gpData = Database::fetch_array($result);
			$_gid = $gpData ['id'];
			api_session_register('_gid');
		} else {
            api_session_unregister('_gid');			
		}
	} elseif (isset($_SESSION['_gid']) or isset($_gid)) { // Keys missing => not anymore in the group - course relation
		api_session_unregister('_gid');
	}
} elseif (isset($_SESSION['_gid'])) { // continue with the previous values
	$_gid = $_SESSION ['_gid'];
} else { //if no previous value, assign caracteristic undefined value
	$_gid = -1;
}

//set variable according to student_view_enabled choices
if (api_get_setting('student_view_enabled') == "true") {
	if (isset($_GET['isStudentView'])) {
		if ($_GET['isStudentView'] == 'true') {
			if (isset($_SESSION['studentview'])) {
				if (!empty($_SESSION['studentview'])) {
					// switching to studentview
					$_SESSION['studentview'] = 'studentview';
				}
			}
		} elseif ($_GET['isStudentView'] == 'false') {
			if (isset($_SESSION['studentview'])) {
				if (!empty($_SESSION['studentview'])) {
					// switching to teacherview
					$_SESSION['studentview'] = 'teacherview';
				}
			}
		}
	} elseif (!empty($_SESSION['studentview'])) {
		//all is fine, no change to that, obviously
	} elseif (empty($_SESSION['studentview'])) {
		// We are in teacherview here
		$_SESSION['studentview'] = 'teacherview';
	}
}

if (isset($_cid)) {
	$tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);
	$time = api_get_datetime();
	$sql="UPDATE $tbl_course SET last_visit= '$time' WHERE code='$_cid'";
	Database::query($sql);
}
if (!empty($_SESSION['request_uri'])){
    $req= $_SESSION['request_uri'];
    unset($_SESSION['request_uri']);
    header('Location: '.$req);
}
