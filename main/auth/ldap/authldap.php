<?php
/* For licensing terms, see /license.txt */
/**
 *    LDAP module functions.
 *
 *    If the application uses LDAP, these functions are used
 *    for logging in, searching user info, adding this info
 *    to the Chamilo database...
    - function ldap_authentication_check()
    - function ldap_find_user_info()
    - function ldap_login()
    - function ldap_put_user_info_locally()
    - ldap_set_version()

    known bugs
    ----------
    - (fixed 18 june 2003) code has been internationalized
    - (fixed 07/05/2003) fixed some non-relative urls or includes
    - (fixed 28/04/2003) we now use global config.inc variables instead of local ones
    - (fixed 22/04/2003) the last name of a user was restricted to the first part
    - (fixed 11/04/2003) the user was never registered as a course manager

    version history
    ---------------
    This historial has been discontinued. Please use the Mercurial logs for more
    3.2 - updated to allow for specific term search for teachers identification
    3.1 - updated code to use database settings, to respect coding conventions
 *        as much as possible (camel-case removed) and to allow for non-anonymous login
    - Patrick Cool: fixing security hole

 *    @author Roan Embrechts
 *
 *    @version 3.0
 *
 *    @package chamilo.auth.ldap
 * Note:
 * If you are using a firewall, you might need to check port 389 is open in
 * order for Chamilo to communicate with the LDAP server.
 * See http://support.chamilo.org/issues/4675 for details.
 */
/**
 * Inclusions.
 */
use ChamiloSession as Session;

/**
 * Code.
 */
require_once api_get_path(SYS_CODE_PATH).'auth/external_login/ldap.inc.php';
require 'ldap_var.inc.php';
/**
 *    Check login and password with LDAP.
 *
 *    @return bool when login & password both OK, false otherwise
 *
 *    @author Roan Embrechts (based on code from Universit� Jean Monet)
 */
function ldap_login($login, $password)
{
    //error_log('Entering ldap_login('.$login.','.$password.')',0);
    $res = ldap_authentication_check($login, $password);

    // res=-1 -> the user does not exist in the ldap database
    // res=1 -> invalid password (user does exist)

    if ($res == 1) { //WRONG PASSWORD
        //$errorMessage = "LDAP User or password incorrect, try again.<br />";
        if (isset($log)) {
            unset($log);
        }
        if (isset($uid)) {
            unset($uid);
        }
        $loginLdapSucces = false;
    }
    if ($res == -1) { //WRONG USERNAME
        //$errorMessage =  "LDAP User or password incorrect, try again.<br />";
        $login_ldap_success = false;
    }
    if ($res == 0) { //LOGIN & PASSWORD OK - SUCCES
        //$errorMessage = "Successful login w/ LDAP.<br>";
        $login_ldap_success = true;
    }

    //$result = "This is the result: $errorMessage";
    $result = $login_ldap_success;

    return $result;
}

/**
 *    Find user info in LDAP.
 *
 *    @return array Array with indexes: "firstname", "name", "email", "employeenumber"
 *
 *    @author Stefan De Wannemacker
 *    @author Roan Embrechts
 */
function ldap_find_user_info($login)
{
    //error_log('Entering ldap_find_user_info('.$login.')',0);
    global $ldap_host, $ldap_port, $ldap_basedn, $ldap_rdn, $ldap_pass, $ldap_search_dn;
    // basic sequence with LDAP is connect, bind, search,
    // interpret search result, close connection

    //echo "Connecting ...";
    $ldap_connect = ldap_connect($ldap_host, $ldap_port);
    ldap_set_version($ldap_connect);
    if ($ldap_connect) {
        //echo " Connect to LDAP server successful ";
        //echo "Binding ...";
        $ldap_bind = false;
        $ldap_bind_res = ldap_handle_bind($ldap_connect, $ldap_bind);
        if ($ldap_bind_res) {
            //echo " LDAP bind successful... ";
            //echo " Searching for uid... ";
            // Search surname entry
            //OLD: $sr=ldap_search($ldapconnect,"dc=rug, dc=ac, dc=be", "uid=$login");
            //echo "<p> ldapDc = '$LDAPbasedn' </p>";
            if (!empty($ldap_search_dn)) {
                $sr = ldap_search($ldap_connect, $ldap_search_dn, "uid=$login");
            } else {
                $sr = ldap_search($ldap_connect, $ldap_basedn, "uid=$login");
            }
            //echo " Search result is ".$sr;
            //echo " Number of entries returned is ".ldap_count_entries($ldapconnect,$sr);
            //echo " Getting entries ...";
            $info = ldap_get_entries($ldap_connect, $sr);
            //echo "Data for ".$info["count"]." items returned:<p>";
        } // else could echo "LDAP bind failed...";
        //echo "Closing LDAP connection<hr>";
        ldap_close($ldap_connect);
    } // else could echo "<h3>Unable to connect to LDAP server</h3>";
    //DEBUG: $result["firstname"] = "Jan"; $result["name"] = "De Test"; $result["email"] = "email@ugent.be";
    $result["firstname"] = $info[0]["cn"][0];
    $result["name"] = $info[0]["sn"][0];
    $result["email"] = $info[0]["mail"][0];
    $tutor_field = api_get_setting('ldap_filled_tutor_field');
    $result[$tutor_field] = $info[0][$tutor_field]; //employeenumber by default

    return $result;
}

/**
 *    This function uses the data from ldap_find_user_info()
 *    to add the userdata to Chamilo
 *    "firstname", "name", "email", "isEmployee".
 *
 *    @author Roan Embrechts
 */
function ldap_put_user_info_locally($login, $info_array)
{
    //error_log('Entering ldap_put_user_info_locally('.$login.',info_array)',0);
    global $ldap_pass_placeholder;
    global $submitRegistration, $submit, $uname, $email,
            $nom, $prenom, $password, $password1, $status;
    global $platformLanguage;
    global $loginFailed, $uidReset, $_user;

    /*----------------------------------------------------------
        1. set the necessary variables
    ------------------------------------------------------------ */
    $uname = $login;
    $email = $info_array["email"];
    $nom = $info_array["name"];
    $prenom = $info_array["firstname"];
    $password = $ldap_pass_placeholder;
    $password1 = $ldap_pass_placeholder;
    $official_code = '';

    define("STUDENT", 5);
    define("COURSEMANAGER", 1);

    $tutor_field = api_get_setting('ldap_filled_tutor_field');
    $tutor_value = api_get_setting('ldap_filled_tutor_field_value');
    if (empty($tutor_field)) {
        $status = STUDENT;
    } else {
        if (empty($tutor_value)) {
            //in this case, we are assuming that the admin didn't give a criteria
            // so that if the field is not empty, it is a tutor
            if (!empty($info_array[$tutor_field])) {
                $status = COURSEMANAGER;
            } else {
                $status = STUDENT;
            }
        } else {
            //the tutor_value is filled, so we need to check the contents of the LDAP field
            if (is_array($info_array[$tutor_field]) && in_array($tutor_value, $info_array[$tutor_field])) {
                $status = COURSEMANAGER;
            } else {
                $status = STUDENT;
            }
        }
    }
    //$official_code = xxx; //example: choose an attribute

    /*----------------------------------------------------------
        2. add info to Chamilo
    ------------------------------------------------------------ */

    $language = api_get_setting('platformLanguage');
    if (empty($language)) {
        $language = 'english';
    }
    $_userId = UserManager::create_user(
        $prenom,
        $nom,
        $status,
        $email,
        $uname,
        $password,
        $official_code,
        $language,
        '',
        '',
        'ldap'
    );

    //echo "new user added to Chamilo, id = $_userId";

    //user_id, username, password, auth_source

    /*----------------------------------------------------------
        3. register session
    ------------------------------------------------------------ */

    $uData['user_id'] = $_userId;
    $uData['username'] = $uname;
    $uData['auth_source'] = "ldap";

    $loginFailed = false;
    $uidReset = true;
    $_user['user_id'] = $uData['user_id'];
    Session::write('_uid', $_user['user_id']);
}

/**
 * The code of UGent uses these functions to authenticate.
 * function AuthVerifEnseignant ($uname, $passwd)
 * function AuthVerifEtudiant ($uname, $passwd)
 * function Authentif ($uname, $passwd).
 *
 * @todo translate the comments and code to english
 * @todo let these functions use the variables in config.inc instead of ldap_var.inc
 */
/**
 * Checks the existence of a member in LDAP.
 *
 * @param string username input on keyboard
 * @param string password given by user
 *
 * @return int 0 if authentication succeeded, 1 if password was incorrect, -1 if it didn't belong to LDAP
 */
function ldap_authentication_check($uname, $passwd)
{
    //error_log('Entering ldap_authentication_check('.$uname.','.$passwd.')',0);
    global $ldap_host, $ldap_port, $ldap_basedn, $ldap_host2, $ldap_port2, $ldap_rdn, $ldap_pass;
    //error_log('Entering ldap_authentication_check('.$uname.','.$passwd.')',0);
    // Establish anonymous connection with LDAP server
    // Etablissement de la connexion anonyme avec le serveur LDAP
    $ds = ldap_connect($ldap_host, $ldap_port);
    ldap_set_version($ds);

    $test_bind = false;
    $test_bind_res = ldap_handle_bind($ds, $test_bind);
    //if problem, use the replica
    if ($test_bind_res === false) {
        $ds = ldap_connect($ldap_host2, $ldap_port2);
        ldap_set_version($ds);
    } // else: error_log('Connected to server '.$ldap_host);
    if ($ds !== false) {
        //Creation of filter containing values input by the user
        // Here it might be necessary to use $filter="(samaccountName=$uname)"; - see http://support.chamilo.org/issues/4675
        $filter = "(uid=$uname)";
        // Open anonymous LDAP connection
        $result = false;
        $ldap_bind_res = ldap_handle_bind($ds, $result);
        // Executing the search with the $filter parametr
        //error_log('Searching for '.$filter.' on LDAP server',0);
        $sr = ldap_search($ds, $ldap_basedn, $filter);
        $info = ldap_get_entries($ds, $sr);
        $dn = ($info[0]["dn"]);
        // debug !!    echo"<br> dn = $dn<br> pass = $passwd<br>";
        // closing 1st connection
        ldap_close($ds);
    }

    // test the Distinguish Name from the 1st connection
    if ($dn == "") {
        return -1; // doesn't belong to the addressbook
    }
    //bug ldap.. if password empty, return 1!
    if ($passwd == "") {
        return 1;
    }
    // Opening 2nd LDAP connection : Connection user for password check
    $ds = ldap_connect($ldap_host, $ldap_port);
    ldap_set_version($ds);
    if (!$test_bind) {
        $ds = ldap_connect($ldap_host2, $ldap_port2);
        ldap_set_version($ds);
    }
    // return in case of wrong password connection error
    if (@ldap_bind($ds, $dn, $passwd) === false) {
        return 1; // invalid password
    } else {// connection successfull
        return 0;
    }
} // end of check
/**
 * Set the protocol version with version from config file (enables LDAP version 3).
 *
 * @param    resource    resource LDAP connexion resource, passed by reference
 */
function ldap_set_version(&$resource)
{
    //error_log('Entering ldap_set_version(&$resource)',0);
    global $ldap_version;
    if ($ldap_version > 2) {
        ldap_set_option($resource, LDAP_OPT_PROTOCOL_VERSION, 3);
        //ok - don't do anything
        //failure - should switch back to version 2 by default
    }
}
/**
 * Handle bind (whether authenticated or not).
 *
 * @param    resource    The LDAP handler to which we are connecting (by reference)
 * @param    resource    The LDAP bind handler we will be modifying
 * @param bool $ldap_bind
 *
 * @return bool Status of the bind assignment. True for success, false for failure.
 */
function ldap_handle_bind(&$ldap_handler, &$ldap_bind)
{
    //error_log('Entering ldap_handle_bind(&$ldap_handler,&$ldap_bind)',0);
    global $ldap_rdn, $ldap_pass, $extldap_config;
    $ldap_rdn = $extldap_config['admin_dn'];
    $ldap_pass = $extldap_config['admin_password'];
    if (!empty($ldap_rdn) and !empty($ldap_pass)) {
        //error_log('Trying authenticated login :'.$ldap_rdn.'/'.$ldap_pass,0);
        $ldap_bind = ldap_bind($ldap_handler, $ldap_rdn, $ldap_pass);
        if (!$ldap_bind) {
            //error_log('Authenticated login failed',0);
            //try in anonymous mode, you never know...
            $ldap_bind = ldap_bind($ldap_handler);
        }
    } else {
        // this is an "anonymous" bind, typically read-only access:
        $ldap_bind = ldap_bind($ldap_handler);
    }
    if (!$ldap_bind) {
        return false;
    } else {
        //error_log('Login finally OK',0);
        return true;
    }
}
/**
 * Get the total number of users on the platform.
 *
 * @see SortableTable#get_total_number_of_items()
 *
 * @author    Mustapha Alouani
 */
function ldap_get_users()
{
    global $ldap_basedn, $ldap_host, $ldap_port, $ldap_rdn, $ldap_pass, $ldap_search_dn, $extldap_user_correspondance;

    $keyword_firstname = isset($_GET['keyword_firstname']) ? trim(Database::escape_string($_GET['keyword_firstname'])) : '';
    $keyword_lastname = isset($_GET['keyword_lastname']) ? trim(Database::escape_string($_GET['keyword_lastname'])) : '';
    $keyword_username = isset($_GET['keyword_username']) ? trim(Database::escape_string($_GET['keyword_username'])) : '';
    $keyword_type = isset($_GET['keyword_type']) ? Database::escape_string($_GET['keyword_type']) : '';

    $ldap_query = [];

    if ($keyword_username != "") {
        $ldap_query[] = str_replace('%username%', $keyword_username, $ldap_search_dn);
    } else {
        if ($keyword_lastname != "") {
            $ldap_query[] = "(".$extldap_user_correspondance['lastname']."=".$keyword_lastname."*)";
        }
        if ($keyword_firstname != "") {
            $ldap_query[] = "(".$extldap_user_correspondance['firstname']."=".$keyword_firstname."*)";
        }
    }
    if ($keyword_type != "" && $keyword_type != "all") {
        $ldap_query[] = "(employeeType=".$keyword_type.")";
    }

    if (count($ldap_query) > 1) {
        $str_query = "(& ";
        foreach ($ldap_query as $query) {
            $str_query .= " $query";
        }
        $str_query .= " )";
    } else {
        $str_query = count($ldap_query) > 0 ? $ldap_query[0] : null;
    }

    $ds = ldap_connect($ldap_host, $ldap_port);
    ldap_set_version($ds);
    if ($ds && count($ldap_query) > 0) {
        $r = false;
        $res = ldap_handle_bind($ds, $r);
        //$sr = ldap_search($ds, "ou=test-ou,$ldap_basedn", $str_query);
        $sr = ldap_search($ds, $ldap_basedn, $str_query);
        //echo "Le nombre de resultats est : ".ldap_count_entries($ds,$sr)."<p>";
        $info = ldap_get_entries($ds, $sr);

        return $info;
    } else {
        if (count($ldap_query) != 0) {
            echo Display::return_message(get_lang('LDAPConnectionError'), 'error');
        }

        return [];
    }
}

/**
 * Get the total number of users on the platform.
 *
 * @see SortableTable#get_total_number_of_items()
 *
 * @author    Mustapha Alouani
 */
function ldap_get_number_of_users()
{
    $info = ldap_get_users();
    if (count($info) > 0) {
        return $info['count'];
    } else {
        return 0;
    }
}

/**
 * Get the users to display on the current page.
 *
 * @see SortableTable#get_table_data($from)
 *
 * @author    Mustapha Alouani
 */
function ldap_get_user_data($from, $number_of_items, $column, $direction)
{
    global $extldap_user_correspondance;

    $users = [];
    $is_western_name_order = api_is_western_name_order();
    if (isset($_GET['submit'])) {
        $info = ldap_get_users();
        if ($info['count'] > 0) {
            for ($key = 0; $key < $info["count"]; $key++) {
                $user = [];
                // Get uid from dn
                //YW: this might be a variation between LDAP 2 and LDAP 3, but in LDAP 3, the uid is in
                //the corresponding index of the array
                //$dn_array=ldap_explode_dn($info[$key]["dn"],1);
                //$user[] = $dn_array[0]; // uid is first key
                //$user[] = $dn_array[0]; // uid is first key
                $user[] = $info[$key][$extldap_user_correspondance['username']][0];
                $user[] = $info[$key][$extldap_user_correspondance['username']][0];
                if ($is_western_name_order) {
                    $user[] = api_convert_encoding($info[$key][$extldap_user_correspondance['firstname']][0], api_get_system_encoding(), 'UTF-8');
                    $user[] = api_convert_encoding($info[$key][$extldap_user_correspondance['lastname']][0], api_get_system_encoding(), 'UTF-8');
                } else {
                    $user[] = api_convert_encoding($info[$key][$extldap_user_correspondance['firstname']][0], api_get_system_encoding(), 'UTF-8');
                    $user[] = api_convert_encoding($info[$key][$extldap_user_correspondance['lastname']][0], api_get_system_encoding(), 'UTF-8');
                }
                $user[] = $info[$key]['mail'][0];
                $user[] = $info[$key][$extldap_user_correspondance['username']][0];
                $users[] = $user;
            }
        } else {
            echo Display::return_message(get_lang('NoUser'), 'error');
        }
    }

    return $users;
}

/**
 * Build the modify-column of the table.
 *
 * @param int    $user_id    The user id
 * @param string $url_params
 *
 * @return string Some HTML-code with modify-buttons
 *
 * @author    Mustapha Alouani
 */
function modify_filter($user_id, $url_params, $row)
{
    $query_string = "id[]=".$row[0];
    if (!empty($_GET['id_session'])) {
        $query_string .= '&amp;id_session='.Security::remove_XSS($_GET['id_session']);
    }
    $icon = '';
    if (UserManager::is_username_available($user_id)) {
        $icon = 'invitation_friend.png';
    } else {
        $icon = 'reload.png';
    }
    //$url_params_id="id=".$row[0];
    $result = '<a href="ldap_users_list.php?action=add_user&amp;user_id='.$user_id.'&amp;'.$query_string.'&amp;sec_token='.Security::getTokenFromSession().'"  onclick="javascript:if(!confirm('."'".addslashes(api_htmlentities(get_lang("ConfirmYourChoice"), ENT_QUOTES, api_get_system_encoding()))."'".')) return false;">'.Display::return_icon($icon, get_lang('AddUsers')).'</a>';

    return $result;
}

/**
 * Adds a user to the Chamilo database or updates its data.
 *
 * @param    string    username (and uid inside LDAP)
 *
 * @author    Mustapha Alouani
 */
function ldap_add_user($login)
{
    if ($ldap_user = extldap_authenticate($login, 'nopass', true)) {
        return extldap_add_user_by_array($ldap_user);
    }
}

function ldap_add_user_by_array($data, $update_if_exists = true)
{
    $lastname = api_convert_encoding($data['sn'][0], api_get_system_encoding(), 'UTF-8');
    $firstname = api_convert_encoding($data['cn'][0], api_get_system_encoding(), 'UTF-8');
    $email = $data['mail'][0];
    // Get uid from dn
    $dn_array = ldap_explode_dn($data['dn'], 1);
    $username = $dn_array[0]; // uid is first key
    $outab[] = $data['edupersonprimaryaffiliation'][0]; // Here, "student"
    //$val = ldap_get_values_len($ds, $entry, "userPassword");
    //$val = ldap_get_values_len($ds, $data, "userPassword");
    //$password = $val[0];
    // TODO the password, if encrypted at the source, will be encrypted twice, which makes it useless. Try to fix that.
    $password = $data['userPassword'][0];
    $structure = $data['edupersonprimaryorgunitdn'][0];
    $array_structure = explode(",", $structure);
    $array_val = explode("=", $array_structure[0]);
    $etape = $array_val[1];
    $array_val = explode("=", $array_structure[1]);
    $annee = $array_val[1];
    // To ease management, we add the step-year (etape-annee) code
    $official_code = $etape."-".$annee;
    $auth_source = 'ldap';
    // No expiration date for students (recover from LDAP's shadow expiry)
    $expiration_date = '';
    $active = 1;
    if (empty($status)) {
        $status = 5;
    }
    if (empty($phone)) {
        $phone = '';
    }
    if (empty($picture_uri)) {
        $picture_uri = '';
    }
    // Adding user
    $user_id = 0;
    if (UserManager::is_username_available($username)) {
        $user_id = UserManager::create_user(
            $firstname,
            $lastname,
            $status,
            $email,
            $username,
            $password,
            $official_code,
            api_get_setting('platformLanguage'),
            $phone,
            $picture_uri,
            $auth_source,
            $expiration_date,
            $active
        );
    } else {
        if ($update_if_exists) {
            $user = api_get_user_info($username);
            $user_id = $user['user_id'];
            UserManager::update_user(
                $user_id,
                $firstname,
                $lastname,
                $username,
                null,
                null,
                $email,
                $status,
                $official_code,
                $phone,
                $picture_uri,
                $expiration_date,
                $active
            );
        }
    }

    return $user_id;
}

/**
 * Adds a list of users to one session.
 *
 * @param    array    Array of user ids
 * @param    string    Course code
 */
function ldap_add_user_to_session($UserList, $id_session)
{
    // Database Table Definitions
    $tbl_session_rel_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);

    $id_session = (int) $id_session;
    // Once users are imported in the users base, we can assign them to the session
    $result = Database::query("SELECT c_id FROM $tbl_session_rel_course WHERE session_id ='$id_session'");
    $CourseList = [];
    while ($row = Database::fetch_array($result)) {
        $CourseList[] = $row['c_id'];
    }

    SessionManager::insertUsersInCourses($UserList, $CourseList, $id_session);
}

/**
 * Synchronize users from the configured LDAP connection (in auth.conf.php). If
 * configured to disable old users,.
 *
 * @param bool $disableOldUsers Whether to disable users who have disappeared from LDAP (true) or just leave them be (default: false)
 * @param bool $deleteStudents  Go one step further and delete completely students missing from LDAP
 * @param bool $deleteTeachers  Go even one step further and also delete completely teachers missing from LDAP
 *
 * @return int Total number of users added (not counting possible removals)
 */
function syncro_users(
  $disableOldUsers = false,
  $deleteStudents = false,
  $deleteTeachers = false
) {
    global $ldap_basedn, $ldap_host, $ldap_port, $ldap_rdn, $ldap_pass, $ldap_search_dn, $debug;
    $i = 0;
    if ($debug) {
        error_log('Connecting... ('.__FUNCTION__.')');
    }
    $ldapConnect = ldap_connect($ldap_host, $ldap_port);
    ldap_set_version($ldapConnect);
    if ($ldapConnect) {
        if ($debug) {
            error_log('Connected to LDAP server successfully! Binding... ('.__FUNCTION__.')');
        }
        $ldapBind = false;
        $ldapBindRes = ldap_handle_bind($ldapConnect, $ldapBind);
        if ($ldapBindRes) {
            if ($debug) {
                error_log('Bind successful! Searching for uid in LDAP DC: '.$ldap_search_dn);
            }
            $allUserQuery = "uid=*";
            if (!empty($ldap_search_dn)) {
                $sr = ldap_search($ldapConnect, $ldap_search_dn, $allUserQuery);
            } else {
                //OLD: $sr=ldap_search($ldapconnect,"dc=rug, dc=ac, dc=be", "uid=$login");
                $sr = ldap_search($ldapConnect, $ldap_basedn, $allUserQuery);
            }
            if ($debug) {
                error_log('Entries returned: '.ldap_count_entries($ldapConnect, $sr));
            }
            $info = ldap_get_entries($ldapConnect, $sr);
            for ($key = 0; $key < $info['count']; $key++) {
                $user_id = ldap_add_user_by_array($info[$key], false);
                if ($user_id) {
                    if ($debug) {
                        error_log('User #'.$user_id.' created from LDAP');
                    }
                    $i++;
                } else {
                    if ($debug) {
                        error_log('User '.$info[$key]['sn'][0].' ('.$info[$key]['mail'][0].') could not be created');
                    }
                }
            }
            if ($disableOldUsers === true) {
                if ($debug) {
                    error_log('Disable mode selected in '.__FUNCTION__);
                    if ($deleteStudents) {
                        error_log('...with complete deletion of users if disabled');
                    }
                }
                // Get a big array of all user IDs, usernames only if they are
                // registered as auth_source = 'ldap'
                // This array will take about 60 bytes per user in memory, so
                // having  100K users should only take a few (6?) MB and will
                // highly reduce the number of DB queries
                $usersDBShortList = [];
                $usersLDAPShortList = [];
                $sql = "SELECT id, username, status FROM user WHERE auth_source = 'ldap' ORDER BY username";
                $res = Database::query($sql);
                if ($res !== false) {
                    // First build a list of users present in LDAP
                    for ($key = 0; $key < $info['count']; $key++) {
                        $dn_array = ldap_explode_dn($info[$key]['dn'], 1);
                        $usersLDAPShortList[$dn_array[0]] = 1;
                    }
                    // Go through all 'extldap' users. For any that cannot
                    // be found in the LDAP list, disable
                    while ($row = Database::fetch_assoc($res)) {
                        $usersDBShortList[$row['username']] = $row['id'];
                        // If any of those users is NOT in LDAP, disable or remove
                        if (empty($usersLDAPShortList[$row['username']])) {
                            if ($deleteStudents === true && $row['status'] == 5) {
                                UserManager::delete_user($usersDBShortList[$row['username']]);
                                if ($debug) {
                                    error_log('Student '.$row['username'].' removed from Chamilo');
                                }
                            } elseif ($deleteTeachers === true && $row['status'] == 1) {
                                UserManager::delete_user($usersDBShortList[$row['username']]);
                                if ($debug) {
                                    error_log('Teacher '.$row['username'].' removed from Chamilo');
                                }
                            } else {
                                UserManager::disable($usersDBShortList[$row['username']]);
                                if ($debug) {
                                    error_log('User '.$row['username'].' disabled in Chamilo');
                                }
                            }
                        }
                    }
                }
            }
            if ($debug) {
                error_log('Data for '.$info['count'].' items processed');
            }
            //echo "Data for ".$info["count"]." items returned:<p>";
        } else {
            error_log('Could not bind to LDAP server');
        }
        ldap_close($ldapConnect);
    } else {
        error_log('Could not connect to LDAP server');
    }
    error_log('Ended execution of function '.__FUNCTION__);
}
