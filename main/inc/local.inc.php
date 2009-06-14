<?php
/*
============================================================================== 
	Dokeos - elearning and course management software
	
	Copyright (c) 2004-2009 Dokeos SPRL
	Copyright (c) 2003-2005 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Hugues Peeters
	Copyright (c) Roan Embrechts (Vrije Universiteit Brussel)
	Copyright (c) Patrick Cool
	Copyright (c) Julio Montoya Armas
	
	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".
	
	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.
	
	See the GNU General Public License for more details.
	
	Contact address: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
============================================================================== 
*/
/**
==============================================================================
 *
 *                             SCRIPT PURPOSE
 *
 * This script initializes and manages Dokeos session information. It
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
The course id is stored in $_cid session variable.
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
 * string	$_user ['firstName'   ]
 * string	$_user ['lastName'    ]
 * string	$_user ['mail'        ]
 * string	$_user ['lastLogin'   ]
 * string	$_user ['official_code']
 * string	$_user ['picture_uri'  ]
 * string 	$_user['user_id']
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
 * string  $_course['official_code']	- the visual / fake / official code
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
 *	Many if the functions you need you can already find in the
 *	main_api.lib.php
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
 *	@package dokeos.include
==============================================================================
*/
/*
==============================================================================
		INIT SECTION
		variables should be initialised here
==============================================================================
*/

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
==============================================================================
		MAIN CODE
==============================================================================
*/

if (!empty($_SESSION['_user']['user_id']) && ! ($login || $logout)) {
    // uid is in session => login already done, continue with this value
    $_user['user_id'] = $_SESSION['_user']['user_id'];
} else {
	if (isset($_user['user_id'])) {	
		unset($_user['user_id']); 
	}

    if (isset($_POST['login']) && isset($_POST['password'])) {
    	// $login && $password are given to log in
		$login = $_POST['login'];
		$password = $_POST['password'];

        //lookup the user in the main database
		$user_table = Database::get_main_table(TABLE_MAIN_USER);
        $sql = "SELECT user_id, username, password, auth_source, active, expiration_date
                FROM $user_table
                WHERE username = '".trim(addslashes($login))."'";
					
        $result = api_sql_query($sql,__FILE__,__LINE__);

        if (Database::num_rows($result) > 0) {
            $uData = Database::fetch_array($result);

            if ($uData['auth_source'] == PLATFORM_AUTH_SOURCE) {
                //the authentification of this user is managed by Dokeos itself

                $password = trim(stripslashes($password));

                // determine if the password needs to be encrypted before checking
                // $userPasswordCrypted is set in an external configuration file

                /*if ($userPasswordCrypted) {
                	$password = md5($password);
                } */
                $password = api_get_encrypted_password($password);                
                
                // check the user's password
                if ($password == $uData['password'] AND (trim($login) == $uData['username'])) {
                	// check if the account is active (not locked)
                	if ($uData['active']=='1') {
                		// check if the expiration date has not been reached
                		if ($uData['expiration_date']>date('Y-m-d H:i:s') OR $uData['expiration_date']=='0000-00-00 00:00:00') {
                			global $_configuration;
                			if ($_configuration['multiple_access_urls']==true) {
								//check the access_url configuration setting if the user is registered in the access_url_rel_user table
								//getting the current access_url_id of the platform                  						 
                				$current_access_url_id = api_get_current_access_url_id();
                				// my user is subscribed in these sites => $my_url_list   
                				$my_url_list = api_get_access_url_from_user($uData['user_id']);
                				                				
                				if (is_array($my_url_list) && count($my_url_list)>0 ){
                					// the user have the permissions to enter at this site
                					if (in_array($current_access_url_id, $my_url_list)) {                						
                						$_user['user_id'] = $uData['user_id'];
										api_session_register('_user');
										if (!function_exists('event_login')){
											include(api_get_path(LIBRARY_PATH)."events.lib.inc.php");
											event_login();
										}                						
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
                			} else {           				
                				$_user['user_id'] = $uData['user_id'];
								api_session_register('_user');
								if (!function_exists('event_login')){
									include(api_get_path(LIBRARY_PATH)."events.lib.inc.php");
									event_login();
								}							
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
            } else // no standard Dokeos login - try external authentification
            {
            	//huh... nothing to do... we shouldn't get here
            	error_log('Dokeos Authentication file '. $extAuthSource[$uData['auth_source']]['login']. ' could not be found - this might prevent your system from doing the corresponding authentication process',0);
            }
            
    	    if (!empty($_SESSION['request_uri'])) {
      	        $req = $_SESSION['request_uri'];
      	        unset($_SESSION['request_uri']);
      	        header('location: '.$req);
    	    } else {
    	    	if (isset($param)) {    	    	
    	    		header('location: '.api_get_path(WEB_PATH).api_get_setting('page_after_login').$param);
    	    	} else {
    	    		header('location: '.api_get_path(WEB_PATH).api_get_setting('page_after_login'));
    	    	}
    	    	
    	    }
        } else {
        	// login failed, mysql_num_rows($result) <= 0
            $loginFailed = true;  // Default initialisation. It could
                                  // change after the external authentication

            /*
             * In this section:
             * there is no entry for the $login user in the Dokeos
             * database. This also means there is no auth_source for the user.
             * We let all external procedures attempt to add him/her
             * to the system.
             *
             * Process external login on the basis
             * of the authentication source list
             * provided by the configuration settings.
             * If the login succeeds, for going further,
             * Dokeos needs the $_user['user_id'] variable to be
             * set and registered in the session. It's the
             * responsability of the external login script
             * to provide this $_user['user_id'].
             */

            if (isset($extAuthSource) && is_array($extAuthSource)) {
                foreach($extAuthSource as $thisAuthSource) {
                	if (!empty($thisAuthSource['newUser']) && file_exists($thisAuthSource['newUser'])) {
                    	include_once($thisAuthSource['newUser']);
                	} else {
		            	error_log('Dokeos Authentication file '. $thisAuthSource['newUser']. ' could not be found - this might prevent your system from using the authentication process in the user creation process',0);
                	}
                }
            } //end if is_array($extAuthSource)

        } //end else login failed
    } elseif(api_get_setting('sso_authentication')==='true' &&  !in_array('webservices', explode('/', $_SERVER['REQUEST_URI']))) {
    	/**
    	 * TODO:
    	 * - Implement user interface for api_get_setting('sso_authentication')
    	 *   } elseif (api_get_setting('sso_authentication')=='true') {
    	 * - Work on a better validation for webservices paths. Current is very poor and exit
    	 * - $master variable should be recovered from dokeos settings.
    	*/
        $master = array(
    		'domain' => api_get_setting('sso_authentication_domain'), 			//	'localhost/project/drupal5',
    		'auth_uri' => api_get_setting('sso_authentication_auth_uri'),		//	'/?q=user',
    		'deauth_uri' => api_get_setting('sso_authentication_unauth_uri'),	//	'/?q=logout',
    		'protocol' => api_get_setting('sso_authentication_protocol')		//	'http://',
        );
        $referer = $master['protocol'] . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        if (isset($_SESSION['_user']['user_id'])) {
            if ($logout) {
                // Library needed by index.php
                include_once api_get_path(LIBRARY_PATH) . 'online.inc.php';
                include_once (api_get_path(LIBRARY_PATH).'course.lib.php');
                // Prevent index.php to redirect
                global $logout_no_redirect;
                $logout_no_redirect = TRUE;
                // Make custom redirect after logout
                online_logout();
                header('Location: '. $master['protocol'] . $master['domain'] . $master['deauth_uri']);
                exit;
            }
      } elseif(!$logout) {
          $master_url = $master['domain'] . $master['auth_uri'];
          // Handle cookie comming from Master Server
          if (!isset($_GET['sso_referer']) && !isset($_GET['loginFailed'])) {
              // Target to redirect after success SSO
              $target = api_get_path(WEB_PATH);
              // Redirect to master server
              header('Location: ' . $master['protocol'] . $master_url . '&sso_referer=' . urlencode($referer) . '&sso_target=' . urlencode($target));
              exit;
          } elseif (isset($_GET['sso_cookie'])) {
              if (isset($_GET['sso_referer']) ? $_GET['sso_referer'] === $master['protocol']. $master_url : FALSE) {
                  $sso = unserialize(base64_decode($_GET['sso_cookie']));
                  //lookup the user in the main database
                  $user_table = Database::get_main_table(TABLE_MAIN_USER);
                  $sql = "SELECT user_id, username, password, auth_source, active, expiration_date
                          FROM $user_table
                          WHERE username = '".trim(addslashes($sso['username']))."'";
              
                  $result = api_sql_query($sql,__FILE__,__LINE__);

                  if (Database::num_rows($result) > 0) {
                      $uData = Database::fetch_array($result);
                      // check the user's password
                      if ($uData['auth_source'] == PLATFORM_AUTH_SOURCE) {
                          // Make sure password is encrypted with md5
                          if (!$userPasswordCrypted) {
                              $uData['password'] = md5($uData['password']);
                          }
                          //the authentification of this user is managed by Dokeos itself// check the user's password
                          // password hash comes into a sha1
                          if ($sso['secret'] === sha1($uData['password']) && ($sso['username'] == $uData['username'])) {
                              // check if the account is active (not locked)
                              if ($uData['active']=='1') {
                                  // check if the expiration date has not been reached
                                  if ($uData['expiration_date']>date('Y-m-d H:i:s') OR $uData['expiration_date']=='0000-00-00 00:00:00') {
                                      global $_configuration;
                                      if ($_configuration['multiple_access_urls']==true) {
                                          //check the access_url configuration setting if the user is registered in the access_url_rel_user table
                                          //getting the current access_url_id of the platform                              
                                          $current_access_url_id = api_get_current_access_url_id();
                                          // my user is subscribed in these sites => $my_url_list   
                                          $my_url_list = api_get_access_url_from_user($uData['user_id']);
                                                
                                          if (is_array($my_url_list) && count($my_url_list)>0 ) {
                                              if (in_array($current_access_url_id, $my_url_list)) {
                                                  // the user has permission to enter at this site
                                                  $_user['user_id'] = $uData['user_id'];
                                                  api_session_register('_user');
                                                  if (!function_exists('event_login')){
                                                      include(api_get_path(LIBRARY_PATH)."events.lib.inc.php");
                                                      event_login();
                                                  }
                                                  // Redirect to homepage
                                                  $sso_target = isset($sso['target']) ? $sso['target'] : api_get_path(WEB_PATH) .'.index.php';
                                                  header('Location: '. $sso_target);
                                              } else {
                                                  // user does not have permission for this site
                                                  $loginFailed = true;
                                                  api_session_unregister('_uid');
                                                  header('Location: '.api_get_path(WEB_PATH).'index.php?loginFailed=1&error=access_url_inactive');
                                                  exit;
                                              }                       
                                          } else {
                                              // there is no URL in the multiple urls list for this user
                                              $loginFailed = true;
                                              api_session_unregister('_uid');
                                              header('Location: '.api_get_path(WEB_PATH).'index.php?loginFailed=1&error=access_url_inactive');
                                              exit;                         
                                          }
                                      } else {
                                            //single URL access
                                            $_user['user_id'] = $uData['user_id'];
                                            api_session_register('_user');
                                            if (!function_exists('event_login')){
                                                include(api_get_path(LIBRARY_PATH)."events.lib.inc.php");
                                                event_login();
                                            }
                                            // Redirect to homepage
                                            $sso_target = isset($sso['target']) ? $sso['target'] : api_get_path(WEB_PATH) .'.index.php';
                                            header('Location: '. $sso_target);           
                                        }
                                    } else {
                                        // user account expired
                                        $loginFailed = true;
                                        api_session_unregister('_uid');
                                        header('Location: '.api_get_path(WEB_PATH).'index.php?loginFailed=1&error=account_expired');
                                        exit;
                                    }
                                } else {
                                    //user not active
                                    $loginFailed = true;
                                    api_session_unregister('_uid');
                                    header('Location: '.api_get_path(WEB_PATH).'index.php?loginFailed=1&error=account_inactive');
                                    exit;
                                }
                            } else {
                              //sha1 of password is wrong
                              $loginFailed = true;
                              api_session_unregister('_uid');
                              header('Location: '.api_get_path(WEB_PATH).'index.php?loginFailed=1&error=account_inactive');
                              exit;
                            }
                        } else {
                            //auth_source is wrong
                            $loginFailed = true;
                            api_session_unregister('_uid');
                            header('Location: '.api_get_path(WEB_PATH).'index.php?loginFailed=1&error=account_inactive');
                            exit;
                        }
                    } else {
                        //no user by that login
                        $loginFailed = true;
                        api_session_unregister('_uid');
                        header('Location: '.api_get_path(WEB_PATH).'index.php?loginFailed=1&error=account_inactive');
                        exit;
                    }
                } else {
                    //request comes from unknown source
                    $loginFailed = true;
                    api_session_unregister('_uid');
                    header('Location: '.api_get_path(WEB_PATH).'index.php?loginFailed=1&error=account_inactive');
                    exit;
                }
            }
        }
    } elseif (api_get_setting('openid_authentication')=='true') {
		if (!empty($_POST['openid_url'])) {
	    	include('main/auth/openid/login.php');
	    	openid_begin(trim($_POST['openid_url']),api_get_path(WEB_PATH).'index.php');
	    	//this last function should trigger a redirect, so we can die here safely
	    	die('Openid login redirection should be in progress');
		} elseif (!empty($_GET['openid_identity']))
    	{	//it's usual for PHP to replace '.' (dot) by '_' (underscore) in URL parameters
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
		        $result = api_sql_query($sql);
		        if ($result !== false) {
		        	if (Database::num_rows($result)>0) {
		        		//$row = Database::fetch_array($res);
			            $uData = Database::fetch_array($result);
			
			            if ($uData['auth_source'] == PLATFORM_AUTH_SOURCE) {
			                //the authentification of this user is managed by Dokeos itself
			
		                	// check if the account is active (not locked)
		                	if ($uData['active']=='1') {
		                		// check if the expiration date has not been reached
		                		if ($uData['expiration_date']>date('Y-m-d H:i:s') OR $uData['expiration_date']=='0000-00-00 00:00:00') {
									$_user['user_id'] = $uData['user_id'];
									api_session_register('_user');
									if (!function_exists('event_login')){
										include(api_get_path(LIBRARY_PATH)."events.lib.inc.php");
										event_login();
									}
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
			
			                if (isset($uData['creator_id']) && $_user['user_id'] != $uData['creator_id'])
			                {
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
}

//Now check for anonymous user mode
if (isset($use_anonymous) && $use_anonymous == true) {
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
	require_once api_get_path(LIBRARY_PATH).'course.lib.php';
    $c = CourseManager::get_course_id_from_path($cDir);
    if ($c != false) { $cidReq = $c; }
}

// if the requested course is different from the course in session

if (!empty($cidReq) && (!isset($_SESSION['_cid']) or (isset($_SESSION['_cid']) && $cidReq != $_SESSION['_cid']))) {
    $cidReset = true;
    $gidReset = true;    // As groups depend from courses, group id is reset
}

// if the requested group is different from the group in session
$gid = isset($_SESSION['_gid'])?$_SESSION['_gid']:'';
if ($gidReq && $gidReq != $gid) {
    $gidReset = true;
}


//////////////////////////////////////////////////////////////////////////////
// USER INIT
//////////////////////////////////////////////////////////////////////////////

if (isset($uidReset) && $uidReset) // session data refresh requested
{
    $is_platformAdmin = false; $is_allowedCreateCourse = false;

    if (isset($_user['user_id']) && $_user['user_id']) // a uid is given (log in succeeded)
    {
        $user_table = Database::get_main_table(TABLE_MAIN_USER);
        $admin_table = Database::get_main_table(TABLE_MAIN_ADMIN);
        if ($_configuration['tracking_enabled']) {
            $sql = "SELECT user.*, a.user_id is_admin,
                            UNIX_TIMESTAMP(login.login_date) login_date
                     FROM $user_table
                     LEFT JOIN $admin_table a
                     ON user.user_id = a.user_id
                     LEFT JOIN ".$_configuration['statistics_database'].".track_e_login login
                     ON user.user_id  = login.login_user_id
                     WHERE user.user_id = '".$_user['user_id']."'
                     ORDER BY login.login_date DESC LIMIT 1";
        } else {
            $sql = "SELECT user.*, a.user_id is_admin
                    FROM $user_table
                    LEFT JOIN $admin_table a
                    ON user.user_id = a.user_id
                    WHERE user.user_id = '".$_user['user_id']."'";
        }

        $result = api_sql_query($sql,__FILE__,__LINE__);

        if (Database::num_rows($result) > 0) {
			// Extracting the user data

            $uData = Database::fetch_array($result);

            $_user ['firstName'] = $uData ['firstname' ];
            $_user ['lastName' ] = $uData ['lastname'  ];
            $_user ['mail'     ] = $uData ['email'     ];
            $_user ['lastLogin'] = $uData ['login_date'];
            $_user ['official_code'] = $uData ['official_code'];
            $_user ['picture_uri'] = $uData ['picture_uri'];
			$_user ['user_id'] = $uData ['user_id'];
			$_user ['language'] = $uData ['language'];
			$_user ['auth_source'] = $uData ['auth_source'];
			$_user ['theme']	= $uData ['theme'];
			$_user ['status']	= $uData ['status'];

            $is_platformAdmin        = (bool) (! is_null( $uData['is_admin']));
            $is_allowedCreateCourse  = (bool) (($uData ['status'] == 1) or (api_get_setting('drhCourseManagerRights') and $uData['status'] == 4));

            api_session_register('_user');
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
    $_user = $_SESSION['_user'];
    $is_platformAdmin = $_SESSION['is_platformAdmin'];
    $is_allowedCreateCourse = $_SESSION['is_allowedCreateCourse'];
}

//////////////////////////////////////////////////////////////////////////////
// COURSE INIT
//////////////////////////////////////////////////////////////////////////////

if (isset($cidReset) && $cidReset) { // course session data refresh requested or empty data
    if ($cidReq) {
    	$course_table = Database::get_main_table(TABLE_MAIN_COURSE);
    	$course_cat_table = Database::get_main_table(TABLE_MAIN_CATEGORY);
        $sql =    "SELECT course.*, course_category.code faCode, course_category.name faName
                 FROM $course_table
                 LEFT JOIN $course_cat_table
                 ON course.category_code = course_category.code
                 WHERE course.code = '$cidReq'";
        $result = api_sql_query($sql,__FILE__,__LINE__);

        if (Database::num_rows($result)>0) {
            $cData = Database::fetch_array($result);
            $_cid                            = $cData['code'             ];
			$_course = array();
			$_course['id'          ]         = $cData['code'             ]; //auto-assigned integer
			$_course['name'        ]         = $cData['title'         ];
            $_course['official_code']         = $cData['visual_code'        ]; // use in echo
            $_course['sysCode'     ]         = $cData['code'             ]; // use as key in db
            $_course['path'        ]         = $cData['directory'        ]; // use as key in path
            $_course['dbName'      ]         = $cData['db_name'           ]; // use as key in db list
            $_course['dbNameGlu'   ]         = $_configuration['table_prefix'] . $cData['db_name'] . $_configuration['db_glue']; // use in all queries
            $_course['titular'     ]         = $cData['tutor_name'       ];
            $_course['language'    ]         = $cData['course_language'   ];
            $_course['extLink'     ]['url' ] = $cData['department_url'    ];
            $_course['extLink'     ]['name'] = $cData['department_name'];
            $_course['categoryCode']         = $cData['faCode'           ];
            $_course['categoryName']         = $cData['faName'           ];

            $_course['visibility'  ]         = $cData['visibility'];
            $_course['subscribe_allowed']    = $cData['subscribe'];
			$_course['unubscribe_allowed']   = $cData['unsubscribe'];

            api_session_register('_cid');
            api_session_register('_course');
			
			if ($_configuration['tracking_enabled'] && !isset($_SESSION['login_as'])) {
	            //We add a new record in the course tracking table
	            $course_tracking_table = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);        
				$time = api_get_datetime();
		        $sql="INSERT INTO $course_tracking_table(course_code, user_id, login_course_date, logout_course_date, counter)" .
							"VALUES('".$_course['sysCode']."', '".$_user['user_id']."', '$time', '$time', '1')";
		
				api_sql_query($sql,__FILE__,__LINE__);
			}
			
			// if a session id has been given in url, we store the session
			if (api_get_setting('use_session_mode')=='true') {
				// Database Table Definitions
				$tbl_session 				= Database::get_main_table(TABLE_MAIN_SESSION);
				$tbl_user 					= Database::get_main_table(TABLE_MAIN_USER);
				$tbl_session_course 		= Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
				$tbl_session_course_user 	= Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
				
				if (!empty($_GET['id_session'])) {
					$_SESSION['id_session'] = Database::escape_string($_GET['id_session']);
					$sql = 'SELECT name FROM '.$tbl_session . ' WHERE id="'.$_SESSION['id_session'] . '"';
					$rs = api_sql_query($sql,__FILE__,__LINE__);
					list($_SESSION['session_name']) = Database::fetch_array($rs);
				} else {
					api_session_unregister('session_name');
					api_session_unregister('id_session');
				}		
			}
        } else {
            //exit("WARNING UNDEFINED CID !! ");
            header('location:'.api_get_path(WEB_PATH));
        }
    } else {
        api_session_unregister('_cid');
        api_session_unregister('_course');

    }
} else { // continue with the previous values
	if (empty($_SESSION['_course']) OR empty($_SESSION['_cid'])) { //no previous values...
		$_cid = -1;		//set default values that will be caracteristic of being unset
		$_course = -1;
	} else {
		$_cid 		= $_SESSION['_cid'   ];
   		$_course    = $_SESSION['_course'];
   		
   		// these lines are usefull for tracking. Indeed we can have lost the id_session and not the cid.
   		// Moreover, if we want to track a course with another session it can be usefull
		if (!empty($_GET['id_session'])) {
			$tbl_session 				= Database::get_main_table(TABLE_MAIN_SESSION);
			$_SESSION['id_session'] = Database::escape_string($_GET['id_session']);
			$sql = 'SELECT name FROM '.$tbl_session . ' WHERE id="'.$_SESSION['id_session'] . '"';
			$rs = api_sql_query($sql,__FILE__,__LINE__);
			list($_SESSION['session_name']) = Database::fetch_array($rs);
		}

		if ($_configuration['tracking_enabled'] && !isset($_SESSION['login_as'])) {
       	    $course_tracking_table = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
            $time = api_get_datetime();
	   		//We select the last record for the current course in the course tracking table
	   		$sql="SELECT course_access_id FROM $course_tracking_table WHERE user_id=".intval($_user ['user_id'])." ORDER BY login_course_date DESC LIMIT 0,1";
	   		$result=api_sql_query($sql,__FILE__,__LINE__);
	   		if (Database::num_rows($result)>0) {
		   		$i_course_access_id = Database::result($result,0,0);
		
		   		//We update the course tracking table
		   		$sql="UPDATE $course_tracking_table " .
		   				"SET logout_course_date = '$time', " .
		   					"counter = counter+1 " .
						"WHERE course_access_id=".intval($i_course_access_id);
				
				api_sql_query($sql,__FILE__,__LINE__);
	   		} else {
	            $sql="INSERT INTO $course_tracking_table(course_code, user_id, login_course_date, logout_course_date, counter)" .
						"VALUES('".$_course['sysCode']."', '".$_user['user_id']."', '$time', '$time', '1')";
				api_sql_query($sql,__FILE__,__LINE__);	
	   		}		
		}
	}
}

//////////////////////////////////////////////////////////////////////////////
// COURSE / USER REL. INIT
//////////////////////////////////////////////////////////////////////////////

if ((isset($uidReset) && $uidReset) || (isset($cidReset) && $cidReset)) { // session data refresh requested
    if (isset($_user['user_id']) && $_user['user_id'] && isset($_cid) && $_cid) { // have keys to search data
    	if (api_get_setting('use_session_mode') != 'true') {

	    	$course_user_table = Database::get_main_table(TABLE_MAIN_COURSE_USER);
	        $sql = "SELECT * FROM $course_user_table
	               WHERE user_id  = '".$_user['user_id']."'
	               AND course_code = '$cidReq'";

	        $result = api_sql_query($sql,__FILE__,__LINE__);

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
               WHERE user_id  = '".$_user['user_id']."'
               AND course_code = '$cidReq'";

	        $result = api_sql_query($sql,__FILE__,__LINE__);

	        if (Database::num_rows($result) > 0) { // this  user have a recorded state for this course
	            $cuData = Database::fetch_array($result);

	            $_courseUser['role'] = $cuData['role'  ];
	            $is_courseMember     = true;
	            $is_courseTutor      = (bool) ($cuData['tutor_id' ] == 1 );
	            $is_courseAdmin      = (bool) ($cuData['status'] == 1 );
				
	            api_session_register('_courseUser');
	        }
	        if (empty($is_courseAdmin)) { // this user has no status related to this course
		    	// is it the session coach or the session admin ?
		    	
		    	$tbl_session = Database :: get_main_table(TABLE_MAIN_SESSION);
		    	$tbl_session_course = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE);
		    	$tbl_session_course_user = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
		    	
		        $sql = "SELECT session.id_coach, session_admin_id
						FROM ".$tbl_session." as session
						INNER JOIN ".$tbl_session_course."
							ON session_rel_course.id_session = session.id
							AND session_rel_course.course_code='$_cid'";

		        $result = api_sql_query($sql,__FILE__,__LINE__);
		        $row = api_store_result($result);
		        
		        if ($row[0]['id_coach']==$_user['user_id']) {
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
		        } elseif ($row[0]['session_admin_id']==$_user['user_id']) {
		        	$_courseUser['role'] = 'Professor';
		            $is_courseMember     = false;
		            $is_courseTutor      = false;
		            $is_courseAdmin      = false;
		            $is_courseCoach      = false;
		            $is_sessionAdmin     = true;
		        } else {
		        	// Check if the current user is the course coach
		        	$sql = "SELECT 1
							FROM ".$tbl_session_course."
							WHERE session_rel_course.course_code='$_cid'
							AND session_rel_course.id_coach = '".$_user['user_id']."'";
			        $result = api_sql_query($sql,__FILE__,__LINE__);
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
		        		// Check if the user is a student is this session
				        $sql = "SELECT * FROM ".$tbl_session_course_user." 
				        		WHERE id_user  = '".$_user['user_id']."'
								AND course_code = '$cidReq'";
	
				        $result = api_sql_query($sql,__FILE__,__LINE__);
	
				        if (Database::num_rows($result) > 0) { // this  user have a recorded state for this course
				        	while($row = Database::fetch_array($result)){
					            $is_courseMember     = true;
					            $is_courseTutor      = false;
					            $is_courseAdmin      = false;
					            $is_sessionAdmin     = false;
	
					            api_session_register('_courseUser');
				        	}
	
						}
			        }
	        	}
	        }
    	}
    } else { // keys missing => not anymore in the course - user relation
        //// course
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


//////////////////////////////////////////////////////////////////////////////
// GROUP INIT
//////////////////////////////////////////////////////////////////////////////


if ((isset($gidReset) && $gidReset) || (isset($cidReset) && $cidReset)) { // session data refresh requested
    if ($gidReq && $_cid ) { // have keys to search data
    	$group_table = Database::get_course_table(TABLE_GROUP);
        $sql = "SELECT * FROM $group_table WHERE id = '$gidReq'";
        $result = api_sql_query($sql,__FILE__,__LINE__);
        if (Database::num_rows($result) > 0) { // This group has recorded status related to this course
            $gpData = Database::fetch_array($result);
            $_gid                   = $gpData ['id'             ];
            api_session_register('_gid');
        } else {
            exit("WARNING UNDEFINED GID !! ");
        }
    } elseif (isset($_SESSION['_gid']) or isset($_gid)) { // Keys missing => not anymore in the group - course relation
        api_session_unregister('_gid');
    }
} elseif (isset($_SESSION['_gid'])) { // continue with the previous values
    $_gid             = $_SESSION ['_gid'            ];
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
	api_sql_query($sql,__FILE__,__LINE__);
}
