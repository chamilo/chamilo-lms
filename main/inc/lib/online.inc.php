<?php

/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * Code library for showing Who is online.
 *
 * @author Istvan Mandak, principal author
 * @author Denes Nagy, principal author
 * @author Bart Mollet
 * @author Roan Embrechts, cleaning and bugfixing
 * Insert a login reference for the current user into the track_e_online stats
 * table. This table keeps trace of the last login. Nothing else matters (we
 * don't keep traces of anything older).
 *
 * @param int user id
 */
function LoginCheck($uid)
{
    $uid = (int) $uid;
    if (!empty($uid)) {
        $online_table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ONLINE);
        $_course = api_get_course_info();
        $user_ip = '';
        if (!empty($_SERVER['REMOTE_ADDR'])) {
            $user_ip = Database::escape_string(api_get_real_ip());
        }

        $login_date = api_get_utc_datetime();
        $access_url_id = 1;
        if (api_get_multiple_access_url() && api_get_current_access_url_id() != -1) {
            $access_url_id = api_get_current_access_url_id();
        }
        $session_id = api_get_session_id();
        $cid = 0;
        if (is_array($_course) && count($_course) > 0 && !empty($_course['real_id'])) {
            $cid = intval($_course['real_id']);
        }
        $query = "SELECT login_id FROM $online_table WHERE login_user_id = $uid";
        $resLogin = Database::query($query);
        if (Database::num_rows($resLogin) > 0) {
            $query = "UPDATE $online_table SET
                      login_date = '$login_date',
                      user_ip = '$user_ip',
                      c_id = $cid,
                      session_id = $session_id,
                      access_url_id = $access_url_id
                      WHERE login_user_id = $uid";
            Database::query($query);
        } else {
            $query = "INSERT $online_table (
                login_user_id,
                login_date,
                user_ip,
                c_id,
                session_id,
                access_url_id
            ) values (
                $uid,
                '$login_date',
                '$user_ip',
                $cid,
                $session_id,
                $access_url_id
            )";
            Database::query($query);
        }
    }
}

/**
 * @param int $userId
 */
function preventMultipleLogin($userId)
{
    $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ONLINE);
    $userId = (int) $userId;
    if (api_get_setting('prevent_multiple_simultaneous_login') === 'true') {
        if (!empty($userId) && !api_is_anonymous()) {
            $isFirstLogin = Session::read('first_user_login');
            $currentIp = Session::read('current_ip');
            $differentIp = false;
            if (!empty($currentIp) && api_get_real_ip() !== $currentIp) {
                //$isFirstLogin = null;
                $differentIp = true;
            }

            if (empty($isFirstLogin)) {
                $sql = "SELECT login_id FROM $table
                        WHERE login_user_id = $userId
                        LIMIT 1";

                $result = Database::query($sql);
                $loginData = [];
                if (Database::num_rows($result)) {
                    $loginData = Database::fetch_array($result);
                }

                $userIsReallyOnline = user_is_online($userId);

                // Trying double login.
                if ((!empty($loginData) && $userIsReallyOnline) || $differentIp) {
                    session_regenerate_id();
                    Session::destroy();
                    header('Location: '.api_get_path(WEB_PATH).'index.php?loginFailed=1&error=multiple_connection_not_allowed');
                    exit;
                } else {
                    // First time
                    Session::write('first_user_login', 1);
                    Session::write('current_ip', api_get_real_ip());
                }
            }
        }
    }
}

/**
 * This function handles the logout and is called whenever there is a $_GET['logout'].
 *
 * @param int  $user_id
 * @param bool $logout_redirect
 *
 * @author Fernando P. García <fernando@develcuy.com>
 */
function online_logout($user_id = null, $logout_redirect = false)
{
    global $extAuthSource;

    // Database table definition
    $tbl_track_login = Database::get_main_table(TABLE_STATISTIC_TRACK_E_LOGIN);

    if (empty($user_id)) {
        $user_id = isset($_GET['uid']) ? intval($_GET['uid']) : 0;
    }

    // Changing global chat status to offline
    if (api_is_global_chat_enabled()) {
        $chat = new Chat();
        $chat->setUserStatus(0);
    }

    $chat = new Chat();
    $chat->close();

    // selecting the last login of the user
    $sql = "SELECT login_id, login_date
    		FROM $tbl_track_login
    		WHERE login_user_id = $user_id
    		ORDER BY login_date DESC
    		LIMIT 0,1";
    $q_last_connection = Database::query($sql);
    $i_id_last_connection = 0;
    if (Database::num_rows($q_last_connection) > 0) {
        $i_id_last_connection = Database::result($q_last_connection, 0, "login_id");
    }

    if (!isset($_SESSION['login_as']) && !empty($i_id_last_connection)) {
        $current_date = api_get_utc_datetime();
        $sql = "UPDATE $tbl_track_login SET logout_date='".$current_date."'
        		WHERE login_id='$i_id_last_connection'";
        Database::query($sql);
    }
    $logInfo = [
        'tool' => 'logout',
        'tool_id' => 0,
        'tool_id_detail' => 0,
    ];
    Event::registerLog($logInfo);

    UserManager::loginDelete($user_id);

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
                require_once $subarray['logout'];
                $logout_function = $uinfo['auth_source'].'_logout';
                if (function_exists($logout_function)) {
                    $logout_function($uinfo);
                }
            }
        }
    }

    // After logout redirect to
    $url = api_get_path(WEB_PATH).'index.php';

    if ($logout_redirect && api_get_plugin_setting('azure_active_directory', 'enable') === 'true') {
        if (ChamiloSession::read('_user_auth_source') === 'azure_active_directory') {
            $activeDirectoryPlugin = AzureActiveDirectory::create();
            $azureLogout = $activeDirectoryPlugin->getUrl(AzureActiveDirectory::URL_TYPE_LOGOUT);
            if (!empty($azureLogout)) {
                $url = $azureLogout;
            }
        }
    }

    if ('true' === api_get_plugin_setting('oauth2', 'enable')
        && 'oauth2' === ChamiloSession::read('_user_auth_source')
        && ChamiloSession::has('oauth2AccessToken')
    ) {
        if (!isset($oAuth2Plugin)) {
            $oAuth2Plugin = OAuth2::create();
        }
        $logoutUrl = $oAuth2Plugin->getLogoutUrl();
        if (!empty($logoutUrl)) {
            $url = $logoutUrl;
        }
    }

    api_delete_firstpage_parameter();
    Session::erase('last_id');
    CourseChatUtils::exitChat($user_id);
    session_regenerate_id();
    Session::destroy();

    $pluginKeycloak = api_get_plugin_setting('keycloak', 'tool_enable') === 'true';
    if ($pluginKeycloak && $uinfo['auth_source'] === 'keycloak') {
        $pluginUrl = api_get_path(WEB_PLUGIN_PATH).'keycloak/start.php?slo';
        header('Location: '.$pluginUrl);
        exit;
    }

    if ($uinfo['auth_source'] === CAS_AUTH_SOURCE && api_is_cas_activated()) {
        require_once __DIR__.'/../../auth/cas/cas_var.inc.php';
        if (phpCas::isInitialized()) {
            phpCAS::logout();
        }
    }

    if ($logout_redirect) {
        header("Location: $url");
        exit;
    }
}

/**
 * @param int $user_id
 *
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
    $user_id = (int) $user_id;

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
 * Gives a list of people online now (and in the last $valid minutes).
 *
 * @param $from
 * @param $number_of_items
 * @param null $column
 * @param null $direction
 * @param null $time_limit
 * @param bool $friends
 *
 * @return array|bool For each line, a list of user IDs and login dates, or FALSE on error or empty results
 */
function who_is_online(
    $from,
    $number_of_items,
    $column = null,
    $direction = null,
    $time_limit = null,
    $friends = false
) {
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
        if (!in_array(strtolower($direction), ['asc', 'desc'])) {
            $direction = 'DESC';
        }
    }

    $online_time = time() - $time_limit * 60;
    $current_date = api_get_utc_datetime($online_time);
    $track_online_table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ONLINE);
    $friend_user_table = Database::get_main_table(TABLE_MAIN_USER_REL_USER);
    $table_user = Database::get_main_table(TABLE_MAIN_USER);

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
                  ORDER BY `$column` $direction
                  LIMIT $from, $number_of_items";
    } else {
        $query = "SELECT DISTINCT login_user_id, login_date
                    FROM ".$track_online_table." e
                    INNER JOIN ".$table_user." u ON (u.id = e.login_user_id)
                  WHERE u.status != ".ANONYMOUS." AND login_date >= '".$current_date."'
                  ORDER BY `$column` $direction
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
                            ORDER BY `$column` $direction
                            LIMIT $from, $number_of_items";
            } else {
                // all users online
                $query = "SELECT login_user_id, login_date
                          FROM ".$track_online_table." track
                          INNER JOIN ".$table_user." u
                          ON (u.id=track.login_user_id)
                          WHERE u.status != ".ANONYMOUS." AND track.access_url_id =  $access_url_id AND
                                login_date >= '".$current_date."'
                          ORDER BY `$column` $direction
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
        $users_online = [];
        while (list($login_user_id, $login_date) = Database::fetch_row($result)) {
            $users_online[] = $login_user_id;
        }

        return $users_online;
    } else {
        return false;
    }
}

/**
 * @param string $time_limit
 */
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
        // who friends from social network is online
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
                // friends from social network is online
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
 *
 * @param    int User ID
 * @param    int Number of minutes
 * @param    string  Course code (could be empty, but then the function returns false)
 *
 * @return array Each line gives a user id and a login time
 */
function who_is_online_in_this_course($from, $number_of_items, $uid, $time_limit, $course_code)
{
    if (empty($course_code)) {
        return false;
    }

    $time_limit = (int) $time_limit;
    if (empty($time_limit)) {
        $time_limit = api_get_setting('time_limit_whosonline');
    }

    $online_time = time() - $time_limit * 60;
    $current_date = api_get_utc_datetime($online_time);
    $track_online_table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ONLINE);
    $tableUser = Database::get_main_table(TABLE_MAIN_USER);
    $course_code = Database::escape_string($course_code);
    $courseInfo = api_get_course_info($course_code);
    $courseId = $courseInfo['real_id'];

    $from = (int) $from;
    $number_of_items = (int) $number_of_items;

    $urlCondition = '';
    $urlJoin = '';
    if (api_is_multiple_url_enabled()) {
        $accessUrlUser = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        $urlId = api_get_current_access_url_id();
        $urlJoin = " INNER JOIN $accessUrlUser a ON (a.user_id = u.id) ";
        $urlCondition = " AND a.access_url_id = $urlId ";
    }

    $query = "SELECT o.login_user_id, o.login_date
              FROM $track_online_table o
              INNER JOIN $tableUser u
              ON (o.login_user_id = u.id)
              $urlJoin
              WHERE
                u.status <> '".ANONYMOUS."' AND
                o.c_id = $courseId AND
                o.login_date >= '$current_date'
                $urlCondition
              LIMIT $from, $number_of_items ";

    $result = Database::query($query);
    if ($result) {
        $users_online = [];
        while (list($login_user_id, $login_date) = Database::fetch_row($result)) {
            $users_online[] = $login_user_id;
        }

        return $users_online;
    } else {
        return false;
    }
}

/**
 * @param int    $uid
 * @param string $time_limit
 */
function who_is_online_in_this_course_count(
    $uid,
    $time_limit,
    $coursecode = null
) {
    if (empty($coursecode)) {
        return false;
    }
    $track_online_table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ONLINE);
    $tableUser = Database::get_main_table(TABLE_MAIN_USER);
    $time_limit = Database::escape_string($time_limit);
    $online_time = time() - $time_limit * 60;
    $current_date = api_get_utc_datetime($online_time);
    $courseId = api_get_course_int_id($coursecode);

    if (empty($courseId)) {
        return false;
    }

    $urlCondition = '';
    $urlJoin = '';
    if (api_is_multiple_url_enabled()) {
        $accessUrlUser = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        $urlId = api_get_current_access_url_id();
        $urlJoin = " INNER JOIN $accessUrlUser a ON (a.user_id = u.id) ";
        $urlCondition = " AND a.access_url_id = $urlId ";
    }

    $query = "SELECT count(login_user_id) as count
              FROM $track_online_table o
              INNER JOIN $tableUser u
              ON (login_user_id = u.id)
              $urlJoin
              WHERE
                u.status <> '".ANONYMOUS."' AND
                c_id = $courseId AND
                login_date >= '$current_date'
                $urlCondition
                ";
    $result = Database::query($query);
    if (Database::num_rows($result) > 0) {
        $row = Database::fetch_array($result);

        return $row['count'];
    } else {
        return false;
    }
}

/**
 * @param string $timeLimit
 * @param int    $sessionId
 *
 * @return bool
 */
function whoIsOnlineInThisSessionCount($timeLimit, $sessionId)
{
    if (!$sessionId) {
        return 0;
    }

    $tblTrackOnline = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ONLINE);
    $tableUser = Database::get_main_table(TABLE_MAIN_USER);

    $timeLimit = Database::escape_string($timeLimit);
    $online_time = time() - $timeLimit * 60;
    $current_date = api_get_utc_datetime($online_time);

    $urlCondition = '';
    $urlJoin = '';
    if (api_is_multiple_url_enabled()) {
        $accessUrlUser = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        $urlId = api_get_current_access_url_id();
        $urlJoin = " INNER JOIN $accessUrlUser a ON (a.user_id = u.id) ";
        $urlCondition = " AND a.access_url_id = $urlId ";
    }

    $query = "SELECT count(login_user_id) as count
              FROM $tblTrackOnline o
              INNER JOIN $tableUser u
              ON (login_user_id = u.id)
              $urlJoin
              WHERE
                    u.status <> '".ANONYMOUS."' AND
                    session_id = $sessionId AND
                    login_date >= '$current_date'
                    $urlCondition
            ";
    $result = Database::query($query);

    if (Database::num_rows($result) > 0) {
        $row = Database::fetch_assoc($result);

        return $row['count'];
    }

    return 0;
}
