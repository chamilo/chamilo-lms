<?php

/* For licensing terms, see /license.txt */

/**
 * This files is included by newUser.ldap.php and login.ldap.php
 * It implements the functions nedded by both files.
 * */
require_once __DIR__.'/../../inc/global.inc.php';

$debug = false;

/**
 * Returns a transcoded and trimmed string.
 *
 * @param string
 *
 * @return string
 *
 * @author ndiechburg <noel@cblue.be>
 * */
function extldap_purify_string($string)
{
    global $extldap_config;
    if (isset($extldap_config['encoding'])) {
        return trim(api_to_system_encoding($string, $extldap_config['encoding']));
    } else {
        return trim($string);
    }
}

/**
 * Establishes a connection to the LDAP server and sets the protocol version.
 *
 * @return resource|bool ldap link identifier or false
 *
 * @author ndiechburg <noel@cblue.be>
 * */
function extldap_connect()
{
    global $extldap_config, $debug;

    if (!is_array($extldap_config['host'])) {
        $extldap_config['host'] = [$extldap_config['host']];
    }

    foreach ($extldap_config['host'] as $host) {
        //Trying to connect
        if (isset($extldap_config['port'])) {
            $ds = ldap_connect($host, $extldap_config['port']);
        } else {
            $ds = ldap_connect($host);
        }
        if (!$ds) {
            $port = isset($extldap_config['port']) ? $extldap_config['port'] : 389;
            if ($debug) {
                error_log(
                    'EXTLDAP ERROR : cannot connect to '.$extldap_config['host'].':'.$port
                );
            }
        } else {
            break;
        }
    }
    if (!$ds) {
        if ($debug) {
            error_log('EXTLDAP ERROR : no valid server found');
        }

        return false;
    }
    // Setting protocol version
    if (isset($extldap_config['protocol_version'])) {
        if (!ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, $extldap_config['protocol_version'])) {
            ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 2);
        }
    }

    // Setting protocol version
    if (isset($extldap_config['referrals'])) {
        if (!ldap_set_option($ds, LDAP_OPT_REFERRALS, $extldap_config['referrals'])) {
            ldap_set_option($ds, LDAP_OPT_REFERRALS, $extldap_config['referrals']);
        }
    }

    return $ds;
}

/**
 * Authenticate user on external ldap server and return user ldap entry if that succeeds.
 *
 * @param string $password
 *
 * @return mixed false if user cannot authenticate on ldap, user ldap entry if tha succeeds
 *
 * @author ndiechburg <noel@cblue.be>
 * Modified by hubert.borderiou@grenet.fr
 * Add possibility to get user info from LDAP without check password (if CAS auth and LDAP profil update)
 *
 * */
function extldap_authenticate($username, $password, $in_auth_with_no_password = false)
{
    global $extldap_config, $debug;

    if (empty($username) || empty($password)) {
        return false;
    }

    $ds = extldap_connect();
    if (!$ds) {
        return false;
    }

    // Connection as admin to search dn of user
    $ldapbind = @ldap_bind($ds, $extldap_config['admin_dn'], $extldap_config['admin_password']);
    if ($ldapbind === false) {
        if ($debug) {
            error_log(
                'EXTLDAP ERROR : cannot connect with admin login/password'
            );
        }

        return false;
    }
    $user_search = extldap_get_user_search_string($username);
    // Search distinguish name of user
    $sr = ldap_search($ds, $extldap_config['base_dn'], $user_search);
    if (!$sr) {
        if ($debug) {
            error_log(
                'EXTLDAP ERROR : ldap_search('.$ds.', '.$extldap_config['base_dn'].", $user_search) failed"
            );
        }

        return false;
    }

    $entries_count = ldap_count_entries($ds, $sr);

    if ($entries_count > 1) {
        if ($debug) {
            error_log(
                'EXTLDAP ERROR : more than one entry for that user ( ldap_search(ds, '.$extldap_config['base_dn'].", $user_search) )"
            );
        }

        return false;
    }
    if ($entries_count < 1) {
        if ($debug) {
            error_log(
                'EXTLDAP ERROR :  No entry for that user ( ldap_search(ds, '.$extldap_config['base_dn'].", $user_search) )"
            );
        }

        return false;
    }
    $users = ldap_get_entries($ds, $sr);
    $user = $users[0];

    // If we just want to have user info from LDAP and not to check password
    if ($in_auth_with_no_password) {
        return $user;
    }

    // now we try to autenthicate the user in the ldap
    $ubind = @ldap_bind($ds, $user['dn'], $password);
    if ($ubind !== false) {
        return $user;
    } else {
        if ($debug) {
            error_log('EXTLDAP : Wrong password for '.$user['dn']);
        }

        return false;
    }
}

/**
 * Return an array with userinfo compatible with chamilo using $extldap_user_correspondance
 * configuration array declared in ldap.conf.php file.
 *
 * @param array ldap user
 * @param array correspondance array (if not set use extldap_user_correspondance declared in auth.conf.php
 *
 * @return array userinfo array
 *
 * @author ndiechburg <noel@cblue.be>
 * */
function extldap_get_chamilo_user($ldap_user, $cor = null)
{
    global $extldap_user_correspondance, $debug;
    if (is_null($cor)) {
        $cor = $extldap_user_correspondance;
    }

    $chamilo_user = [];
    foreach ($cor as $chamilo_field => $ldap_field) {
        if (is_array($ldap_field)) {
            $chamilo_user[$chamilo_field] = extldap_get_chamilo_user($ldap_user, $ldap_field);
            continue;
        }

        switch ($ldap_field) {
            case 'func':
                $func = "extldap_get_$chamilo_field";
                if (function_exists($func)) {
                    $chamilo_user[$chamilo_field] = extldap_purify_string($func($ldap_user));
                } else {
                    if ($debug) {
                        error_log(
                            "EXTLDAP WARNING : You forgot to declare $func"
                        );
                    }
                }
                break;
            default:
                //if string begins with "!", then this is a constant
                if ($ldap_field[0] === '!') {
                    $chamilo_user[$chamilo_field] = trim($ldap_field, "!\t\n\r\0");
                    break;
                }
                if (!array_key_exists($ldap_field, $ldap_user)) {
                    $lowerCaseFieldName = strtolower($ldap_field);
                    if (array_key_exists($lowerCaseFieldName, $ldap_user)) {
                        $ldap_field = $lowerCaseFieldName;
                    }
                }
                if (isset($ldap_user[$ldap_field][0])) {
                    $chamilo_user[$chamilo_field] = extldap_purify_string($ldap_user[$ldap_field][0]);
                } else {
                    if ($debug) {
                        error_log(
                            'EXTLDAP WARNING : '.$ldap_field.'[0] field is not set in ldap array'
                        );
                    }
                }
                break;
        }
    }

    return $chamilo_user;
}

/**
 * Please declare here all the function you use in extldap_user_correspondance
 * All these functions must have an $ldap_user parameter. This parameter is the
 * array returned by the ldap for the user.
 * */
function extldap_get_status($ldap_user)
{
    return STUDENT;
}

function extldap_get_admin($ldap_user)
{
    return false;
}

/**
 * return the string used to search a user in ldap.
 *
 * @param string username
 *
 * @return string the serach string
 *
 * @author ndiechburg <noel@cblue.be>
 * */
function extldap_get_user_search_string($username)
{
    global $extldap_config;
    // init
    $filter = '('.$extldap_config['user_search'].')';
    // replacing %username% by the actual username
    $filter = str_replace('%username%', $username, $filter);
    // append a global filter if needed
    if (isset($extldap_config['filter']) && $extldap_config['filter'] != "") {
        $filter = '(&'.$filter.'('.$extldap_config['filter'].'))';
    }

    return $filter;
}

/**
 * Imports all LDAP users into Chamilo.
 *
 * @return false|null false on error, true otherwise
 */
function extldap_import_all_users()
{
    global $extldap_config, $debug;
    //echo "Connecting...\n";
    $ds = extldap_connect();
    if (!$ds) {
        return false;
    }
    //echo "Binding...\n";
    $ldapbind = false;
    //Connection as admin to search dn of user
    $ldapbind = @ldap_bind($ds, $extldap_config['admin_dn'], $extldap_config['admin_password']);
    if ($ldapbind === false) {
        if ($debug) {
            error_log(
                'EXTLDAP ERROR : cannot connect with admin login/password'
            );
        }

        return false;
    }
    //browse ASCII values from a to z to avoid 1000 results limit of LDAP
    $count = 0;
    $alphanum = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    for ($a = 97; $a <= 122; $a++) {
        $alphanum[] = chr($a);
    }
    foreach ($alphanum as $char1) {
        foreach ($alphanum as $char2) {
            $user_search = $extldap_config['user_search_import_all_users'];
            //Search distinguish name of user
            $sr = ldap_search($ds, $extldap_config['base_dn'], $user_search);
            if (!$sr) {
                if ($debug) {
                    error_log(
                        'EXTLDAP ERROR : ldap_search('.$ds.', '.$extldap_config['base_dn'].", $user_search) failed"
                    );
                }

                return false;
            }
            //echo "Getting entries\n";
            $users = ldap_get_entries($ds, $sr);
            //echo "Entries: ".$users['count']."\n";
            for ($key = 0; $key < $users['count']; $key++) {
                $user_id = extldap_add_user_by_array($users[$key], true);
                $count++;
            }
        }
    }
    //echo "Found $count users in total\n";
    @ldap_close($ds);
}

/**
 * Insert users from an array of user fields.
 */
function extldap_add_user_by_array($data, $update_if_exists = true)
{
    global $extldap_user_correspondance;

    $lastname = api_convert_encoding($data[$extldap_user_correspondance['lastname']][0], api_get_system_encoding(), 'UTF-8');
    $firstname = api_convert_encoding($data[$extldap_user_correspondance['firstname']][0], api_get_system_encoding(), 'UTF-8');
    $email = $data[$extldap_user_correspondance['email']][0];
    $username = $data[$extldap_user_correspondance['username']][0];

    // TODO the password, if encrypted at the source, will be encrypted twice, which makes it useless. Try to fix that.
    $passwordKey = isset($extldap_user_correspondance['password']) ? $extldap_user_correspondance['password'] : 'userPassword';
    $password = $data[$passwordKey][0];

    // To ease management, we add the step-year (etape-annee) code
    //$official_code = $etape."-".$annee;
    $official_code = api_convert_encoding($data[$extldap_user_correspondance['official_code']][0], api_get_system_encoding(), 'UTF-8');
    $auth_source = 'ldap';

    // No expiration date for students (recover from LDAP's shadow expiry)
    $expiration_date = '';
    $active = 1;
    $status = 5;
    $phone = '';
    $picture_uri = '';
    // Adding user
    $user_id = 0;
    if (UserManager::is_username_available($username)) {
        //echo "$username\n";
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
            //echo "$username\n";
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
 * Get one user's single attribute value.
 * User is identified by filter.
 * $extldap_config['filter'] is also applied in complement, if defined.
 *
 * @param $filter string LDAP entry filter, such as '(uid=10000)'
 * @param $attribute string name of the LDAP attribute to read the value from
 *
 * @throws Exception if more than one entries matched or on internal error
 *
 * @return string|bool the single matching user entry's single attribute value or false if not found
 */
function extldapGetUserAttributeValue($filter, $attribute)
{
    global $extldap_config;

    if (array_key_exists('filter', $extldap_config) && !empty($extldap_config['filter'])) {
        $filter = '(&'.$filter.'('.$extldap_config['filter'].'))';
    }

    $ldap = extldap_connect();
    if (false === $ldap) {
        throw new Exception(get_lang('LDAPConnectFailed'));
    }

    if (false === ldap_bind($ldap, $extldap_config['admin_dn'], $extldap_config['admin_password'])) {
        throw new Exception(get_lang('LDAPBindFailed'));
    }

    $searchResult = ldap_search($ldap, $extldap_config['base_dn'], $filter, [$attribute]);
    if (false === $searchResult) {
        throw new Exception(get_lang('LDAPSearchFailed'));
    }

    switch (ldap_count_entries($ldap, $searchResult)) {
        case 0:
            return false;
        case 1:
            $entry = ldap_first_entry($ldap, $searchResult);
            if (false === $entry) {
                throw new Exception(get_lang('LDAPFirstEntryFailed'));
            }
            $values = ldap_get_values($ldap, $entry, $attribute);
            if (false == $values) {
                throw new Exception(get_lang('LDAPGetValuesFailed'));
            }
            if ($values['count'] == 1) {
                return $values[0];
            }
            throw new Exception(get_lang('MoreThanOneAttributeValueFound'));
        default:
            throw new Exception(get_lang('MoreThanOneUserMatched'));
    }
}

/**
 * Get the username from the CAS-supplied user identifier.
 *
 * searches in attribute $extldap_user_correspondance['extra']['cas_user'] or 'uid' by default
 * reads value from attribute $extldap_user_correspondance['username'] or 'uid' by default
 *
 * @param $casUser string code returned from the CAS server to identify the user
 *
 * @throws Exception on error
 *
 * @return string|bool user login name, false if not found
 */
function extldapCasUserLogin($casUser)
{
    global $extldap_user_correspondance;

    // which LDAP attribute is the cas user identifier stored in ?
    $attributeToFilterOn = 'uid';
    if (is_array($extldap_user_correspondance) && array_key_exists('extra', $extldap_user_correspondance)) {
        $extra = $extldap_user_correspondance['extra'];
        if (is_array($extra) && array_key_exists('cas_user', $extra) && !empty($extra['cas_user'])) {
            $attributeToFilterOn = $extra['cas_user'];
        }
    }

    // which LDAP attribute is the username ?
    $attributeToRead = 'uid';
    if (is_array($extldap_user_correspondance)
        && array_key_exists('username', $extldap_user_correspondance)
        && !empty($extldap_user_correspondance['username'])
    ) {
        $attributeToRead = $extldap_user_correspondance['username'];
    }

    // return the value
    return extldapGetUserAttributeValue("($attributeToFilterOn=$casUser)", $attributeToRead);
}
