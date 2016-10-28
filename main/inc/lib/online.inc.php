<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
*	Code library for showing Who is online
*
*	@author Istvan Mandak, principal author
*	@author Denes Nagy, principal author
*	@author Bart Mollet
*	@author Roan Embrechts, cleaning and bugfixing
*	@package chamilo.whoisonline
*/

/**
 * Insert a login reference for the current user into the track_e_online stats table.
 * This table keeps trace of the last login. Nothing else matters (we don't keep traces of anything older)
 * @param int user id
 * @return void
 */

function LoginCheck($uid)
{
    $_course = api_get_course_info();
    $uid = (int) $uid;
    $online_table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ONLINE);
    if (!empty($uid)) {
        $user_ip = '';
        if (!empty($_SERVER['REMOTE_ADDR'])) {
            $user_ip = Database::escape_string(api_get_real_ip());
        }

        $login_date = api_get_utc_datetime();
        $access_url_id = 1;
        if (api_get_multiple_access_url() && api_get_current_access_url_id()!=-1) {
            $access_url_id = api_get_current_access_url_id();
        }
        $session_id = api_get_session_id();
        // if the $_course array exists this means we are in a course and we have to store this in the who's online table also
        // to have the x users in this course feature working
        if (is_array($_course) && count($_course)>0 && !empty($_course['id'])) {
            $query = "REPLACE INTO ".$online_table ." (login_id,login_user_id,login_date,user_ip, c_id, session_id, access_url_id)
                      VALUES ($uid,$uid,'$login_date','$user_ip', '".$_course['real_id']."' , '$session_id' , '$access_url_id' )";
        } else {
            $query = "REPLACE INTO ".$online_table ." (login_id,login_user_id,login_date,user_ip, c_id, session_id, access_url_id)
                      VALUES ($uid,$uid,'$login_date','$user_ip', 0, '$session_id', '$access_url_id')";
        }
        Database::query($query);
    }
}

/**
 * @param int $userId
 */
function preventMultipleLogin($userId)
{
    $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ONLINE);
    $userId = intval($userId);
    if (api_get_setting('prevent_multiple_simultaneous_login') === 'true') {
        if (!empty($userId) && !api_is_anonymous()) {
            $isFirstLogin = Session::read('first_user_login');
            if (empty($isFirstLogin)) {
                $sql = "SELECT login_id FROM $table
                        WHERE login_user_id = $userId 
                        LIMIT 1";

                $result = Database::query($sql);
                $loginData = array();
                if (Database::num_rows($result)) {
                    $loginData = Database::fetch_array($result);
                }

                $userIsReallyOnline = user_is_online($userId);

                // Trying double login.
                if (!empty($loginData) && $userIsReallyOnline == true) {
                    session_regenerate_id();
                    Session::destroy();
                    header('Location: '.api_get_path(WEB_PATH).'index.php?loginFailed=1&error=multiple_connection_not_allowed');
                    exit;
                } else {
                    // First time
                    Session::write('first_user_login', 1);
                }
            }
        }
    }
}

/**
 * This function handles the logout and is called whenever there is a $_GET['logout']
 * @return void  Directly redirects the user or leaves him where he is, but doesn't return anything
 * @author Fernando P. García <fernando@develcuy.com>
 */
function online_logout($user_id = null, $logout_redirect = false)
{
    global $extAuthSource;

    // Database table definition
    $tbl_track_login = Database :: get_main_table(TABLE_STATISTIC_TRACK_E_LOGIN);

    if (empty($user_id)) {
        $user_id = isset($_GET['uid']) ? intval($_GET['uid']) : 0;
    }

    //Changing global chat status to offline
    if (api_is_global_chat_enabled()) {
        $chat = new Chat();
        $chat->setUserStatus(0);
    }

    // selecting the last login of the user
    $sql = "SELECT login_id, login_date
    		FROM $tbl_track_login
    		WHERE login_user_id = $user_id
    		ORDER BY login_date DESC
    		LIMIT 0,1";
    $q_last_connection = Database::query($sql);
    if (Database::num_rows($q_last_connection)>0) {
        $i_id_last_connection = Database::result($q_last_connection, 0, "login_id");
    }

    if (!isset($_SESSION['login_as'])) {
        $current_date = api_get_utc_datetime();
        $sql = "UPDATE $tbl_track_login SET logout_date='".$current_date."'
        		WHERE login_id='$i_id_last_connection'";
        Database::query($sql);
    }

    LoginDelete($user_id); //from inc/lib/online.inc.php - removes the "online" status

    //the following code enables the use of an external logout function.
    //example: define a $extAuthSource['ldap']['logout']="file.php" in configuration.php
    // then a function called ldap_logout() inside that file
    // (using *authent_name*_logout as the function name) and the following code
    // will find and execute it
    $uinfo = api_get_user_info($user_id);
    if (($uinfo['auth_source'] != PLATFORM_AUTH_SOURCE) && is_array($extAuthSource)) {
        if (is_array($extAuthSource[$uinfo['auth_source']])) {
            $subarray = $extAuthSource[$uinfo['auth_source']];
            if (!empty($subarray['logout']) && file_exists($subarray['logout'])) {
                require_once($subarray['logout']);
                $logout_function = $uinfo['auth_source'].'_logout';
                if (function_exists($logout_function)) {
                    $logout_function($uinfo);
                }
            }
        }
    }

    CourseChatUtils::exitChat($user_id);
    session_regenerate_id();
    Session::destroy();
    if ($logout_redirect) {
        header("Location: ".api_get_path(WEB_PATH)."index.php");
        exit;
    }
}

/**
 * Remove all login records from the track_e_online stats table, for the given user ID.
 * @param int User ID
 * @return void
 */
function LoginDelete($user_id)
{
    $online_table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ONLINE);
    $user_id = intval($user_id);
    $query = "DELETE FROM " . $online_table . " WHERE login_user_id = $user_id";
    Database::query($query);
}

/**
 * @param int $user_id
 * @return bool
 */
function user_is_online($user_id)
{
    $track_online_table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ONLINE);
    $table_user = Database::get_main_table(TABLE_MAIN_USER);

    $access_url_id = api_get_current_access_url_id();
    $time_limit = api_get_setting('time_limit_whosonline');

    $online_time = time() - $time_limit * 60;
    $limit_date = api_get_utc_datetime($online_time);
    $user_id = intval($user_id);

    $query = " SELECT login_user_id, login_date
               FROM $track_online_table track
               INNER JOIN $table_user u 
               ON (u.id=track.login_user_id)
               WHERE
                    track.access_url_id =  $access_url_id AND
                    login_date >= '".$limit_date."'  AND
                    u.id =  $user_id
               LIMIT 1 ";

    $result = Database::query($query);
    if (Database::num_rows($result)) {
        return true;
    }

    return false;

}

/**
 * Gives a list of people online now (and in the last $valid minutes)
 *
 * @param $from
 * @param $number_of_items
 * @param null $column
 * @param null $direction
 * @param null $time_limit
 * @param bool $friends
 * @return  array|bool For each line, a list of user IDs and login dates, or FALSE on error or empty results
 */
function who_is_online($from, $number_of_items, $column = null, $direction = null, $time_limit = null, $friends = false)
{
    // Time limit in seconds?
    if (empty($time_limit)) {
        $time_limit = api_get_setting('time_limit_whosonline');
    } else {
        $time_limit = intval($time_limit);
    }

    $from = intval($from);
    $number_of_items = intval($number_of_items);

    if (empty($column)) {
        $column = 'picture_uri';
        if ($friends) {
            $column = 'login_date';
        }
    }

    if (empty($direction)) {
        $direction = 'DESC';
    } else {
        if (!in_array(strtolower($direction), array('asc', 'desc'))) {
            $direction = 'DESC';
        }
    }

    $online_time = time() - $time_limit * 60;
    $current_date = api_get_utc_datetime($online_time);
    $track_online_table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ONLINE);
    $friend_user_table = Database::get_main_table(TABLE_MAIN_USER_REL_USER);
    $table_user	= Database::get_main_table(TABLE_MAIN_USER);

    if ($friends) {
        // 	who friends from social network is online
        $query = "SELECT DISTINCT login_user_id, login_date
                  FROM $track_online_table INNER JOIN $friend_user_table
                  ON (friend_user_id = login_user_id)
                  WHERE
                    login_date >= '".$current_date."' AND
                    friend_user_id <> '".api_get_user_id()."' AND
                    relation_type='".USER_RELATION_TYPE_FRIEND."' AND
                    user_id = '".api_get_user_id()."'
                  ORDER BY $column $direction
                  LIMIT $from, $number_of_items";
    } else {
        $query = "SELECT DISTINCT login_user_id, login_date
                    FROM ".$track_online_table ." e
                    INNER JOIN ".$table_user ." u ON (u.id = e.login_user_id)
                  WHERE u.status != ".ANONYMOUS." AND login_date >= '".$current_date."'
                  ORDER BY $column $direction
                  LIMIT $from, $number_of_items";
    }

    if (api_get_multiple_access_url()) {
        $access_url_id = api_get_current_access_url_id();
        if ($access_url_id != -1) {
            if ($friends) {
                // 	friends from social network is online
                $query = "SELECT distinct login_user_id, login_date
                            FROM $track_online_table track INNER JOIN $friend_user_table
                            ON (friend_user_id = login_user_id)
                            WHERE   track.access_url_id =  $access_url_id AND
                                    login_date >= '".$current_date."' AND
                                    friend_user_id <> '".api_get_user_id()."' AND
                                    relation_type='".USER_RELATION_TYPE_FRIEND."'
                            ORDER BY $column $direction
                            LIMIT $from, $number_of_items";
            } else {
                // all users online
                $query = "SELECT login_user_id, login_date
                          FROM ".$track_online_table ." track
                          INNER JOIN ".$table_user ." u
                          ON (u.id=track.login_user_id)
                          WHERE u.status != ".ANONYMOUS." AND track.access_url_id =  $access_url_id AND
                                login_date >= '".$current_date."'
                          ORDER BY $column $direction
                          LIMIT $from, $number_of_items";
            }
        }
    }

	//This query will show all registered users. Only for dev purposes.
	/*$query = "SELECT DISTINCT u.id as login_user_id, login_date
	        FROM $track_online_table e, $table_user u
            GROUP by u.id
            ORDER BY $column $direction
            LIMIT $from, $number_of_items";*/

    $result = Database::query($query);
    if ($result) {
        $users_online = array();
        while (list($login_user_id, $login_date) = Database::fetch_row($result)) {
            $users_online[] = $login_user_id;
        }

        return $users_online;
    } else {

        return false;
    }
}

function who_is_online_count($time_limit = null, $friends = false)
{
    if (empty($time_limit)) {
        $time_limit = api_get_setting('time_limit_whosonline');
    } else {
        $time_limit = intval($time_limit);
    }
	$track_online_table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ONLINE);
	$friend_user_table = Database::get_main_table(TABLE_MAIN_USER_REL_USER);
	$table_user = Database::get_main_table(TABLE_MAIN_USER);
	$online_time = time() - $time_limit * 60;
	$current_date = api_get_utc_datetime($online_time);

	if ($friends) {
		// 	who friends from social network is online
		$query = "SELECT DISTINCT count(login_user_id) as count
				  FROM $track_online_table INNER JOIN $friend_user_table
                  ON (friend_user_id = login_user_id)
				  WHERE
				        login_date >= '$current_date' AND
				        friend_user_id <> '".api_get_user_id()."' AND
				        relation_type='".USER_RELATION_TYPE_FRIEND."' AND
				        user_id = '".api_get_user_id()."' ";
	} else {
		// All users online
		$query = "SELECT count(login_id) as count
                  FROM $track_online_table track INNER JOIN $table_user u
                  ON (u.id=track.login_user_id)
                  WHERE u.status != ".ANONYMOUS." AND login_date >= '$current_date'  ";
	}

	if (api_get_multiple_access_url()) {
		$access_url_id = api_get_current_access_url_id();
		if ($access_url_id != -1) {
			if ($friends) {
				// 	friends from social network is online
				$query = "SELECT DISTINCT count(login_user_id) as count
							FROM $track_online_table track
							INNER JOIN $friend_user_table ON (friend_user_id = login_user_id)
							WHERE
							    track.access_url_id = $access_url_id AND
							    login_date >= '".$current_date."' AND
							    friend_user_id <> '".api_get_user_id()."' AND
							    relation_type='".USER_RELATION_TYPE_FRIEND."'  ";
			} else {
				// all users online
				$query = "SELECT count(login_id) as count FROM $track_online_table  track
                          INNER JOIN $table_user u ON (u.id=track.login_user_id)
						  WHERE
						    u.status != ".ANONYMOUS." AND
						    track.access_url_id =  $access_url_id AND
						    login_date >= '$current_date' ";
			}
		}
	}

    // Dev purposes show all users online
    /*$table_user = Database::get_main_table(TABLE_MAIN_USER);
    $query = "SELECT count(*)  as count FROM ".$table_user;*/

	$result = Database::query($query);
	if (Database::num_rows($result) > 0) {
		$row = Database::fetch_array($result);
		return $row['count'];
	} else {
		return false;
	}
}


/**
* Returns a list (array) of users who are online and in this course.
* @param    int User ID
* @param    int Number of minutes
* @param    string  Course code (could be empty, but then the function returns false)
* @return   array   Each line gives a user id and a login time
*/
function who_is_online_in_this_course($from, $number_of_items, $uid, $time_limit, $course_code)
{
    if (empty($course_code)) {
        return false;
    }

    if (empty($time_limit)) {
        $time_limit = api_get_setting('time_limit_whosonline');
    } else {
        $time_limit = intval($time_limit);
    }

    $online_time = time() - $time_limit * 60;
    $current_date = api_get_utc_datetime($online_time);
    $track_online_table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ONLINE);
    $course_code = Database::escape_string($course_code);
    $courseInfo = api_get_course_info($course_code);
	$courseId = $courseInfo['real_id'];

    $from = intval($from);
    $number_of_items = intval($number_of_items);

	$query = "SELECT login_user_id, login_date FROM $track_online_table
              WHERE login_user_id <> 2 AND c_id = $courseId AND login_date >= '$current_date'
              LIMIT $from, $number_of_items ";

	$result = Database::query($query);
	if ($result) {
		$users_online = array();

		while(list($login_user_id, $login_date) = Database::fetch_row($result)) {
            $users_online[] = $login_user_id;
		}
		return $users_online;
	} else {
		return false;
	}
}

function who_is_online_in_this_course_count($uid, $time_limit, $coursecode=null)
{
	if (empty($coursecode)) {
		return false;
	}
	$track_online_table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ONLINE);
	$time_limit = Database::escape_string($time_limit);

    $online_time = time() - $time_limit * 60;
    $current_date = api_get_utc_datetime($online_time);
	$courseId = api_get_course_int_id($coursecode);

	if (empty($courseId)) {
		return false;
	}

	$query = "SELECT count(login_user_id) as count
              FROM $track_online_table
              WHERE login_user_id <> 2 AND c_id = $courseId AND login_date >= '$current_date' ";
	$result = Database::query($query);
	if (Database::num_rows($result) > 0) {
		$row = Database::fetch_array($result);
		return $row['count'];
	} else {
		return false;
	}
}
