<?php
/* For licensing terms, see /license.txt */
/*
User account CASification interactive script

to move user accounts from LDAP authentication to CAS authentication

Creates the "cas_user" extra field if missing, then for each registred user:
- copies over the right CAS identifier to the "cas_user" extra field
- ensures the "username" is spelled right
- updates the "auth_source".

This script should be run from a terminal.
It does not read any parameter from the command line, but uses the global configuration arrays
 $extldap_config
and
 $extldap_user_correspondance
defined in app/config/auth.conf.php.

The username is used to search the LDAP directory, in both attributes
$extldap_user_correspondance['username']
and
$extldap_user_correspondance['extra']['cas_user'].

Any user account with no match or more than one matches in the LDAP directory is skipped.

All the corrections are only applied in phase 2, and take time.

Phase 1 only builds a TO-DO list.

Phase 2 starts with the script asking the operator confirmation for each modification category:
- fix usernames
- add missing CAS identifiers
- fix wrong CAS identifiers
- fix auth source

Planned modifications and progress are displayed.

Diagnostics and modifications can be saved using command script(1).

This script does not need to be run more than once,
but can be run several times.
In case phase 2 is stopped before the end, one should run this script again.
If this script is run after all user accounts were CASified, it just stops after Phase 1.
This can be used to check whether no work is left to do.
*/
if (php_sapi_name() !== 'cli') {
    exit("this script is supposed to be run from the command-line\n");
}
require __DIR__.'/../../cli-config.php';
require_once __DIR__.'/../../app/config/auth.conf.php';
require_once __DIR__.'/../../main/inc/lib/api.lib.php';
require_once __DIR__.'/../../main/inc/lib/database.constants.inc.php';
require_once __DIR__.'/../../main/inc/lib/internationalization.lib.php';
require_once __DIR__.'/../../main/inc/lib/text.lib.php';

// Bind to LDAP server

$ldap = false;
foreach ($extldap_config['host'] as $ldapHost) {
    $ldap = array_key_exists('port', $extldap_config)
        ? ldap_connect($ldapHost, $extldap_config['port'])
        : ldap_connect($ldapHost);
    if (false !== $ldap) {
        break;
    }
}
if (false === $ldap) {
    exit("ldap_connect() failed\n");
}
echo "Connected to LDAP server $ldapHost.\n";

ldap_set_option(
    $ldap,
    LDAP_OPT_PROTOCOL_VERSION,
    array_key_exists('protocol_version', $extldap_config) ? $extldap_config['protocol_version'] : 2
);

ldap_set_option(
    $ldap,
    LDAP_OPT_REFERRALS,
    array_key_exists('referrals', $extldap_config) ? $extldap_config['referrals'] : false
);

ldap_bind($ldap, $extldap_config['admin_dn'], $extldap_config['admin_password'])
or exit('ldap_bind() failed: '.ldap_error($ldap)."\n");
echo "Bound to LDAP server as ${extldap_config['admin_dn']}.\n";

// set a few variables for LDAP search

$baseDn = $extldap_config['base_dn']
or exit("cannot read the LDAP directory base DN where to search for user entries\n");
echo "Base DN is '$baseDn'.\n";

$ldapCASUserAttribute = $extldap_user_correspondance['extra']['cas_user']
or exit("cannot read the name of the LDAP attribute where to find the CAS user code\n");
echo "LDAP CAS user code attribute is '$ldapCASUserAttribute'.\n";

$ldapUsernameAttribute = $extldap_user_correspondance['username']
or exit("cannot read the name of the LDAP attribute where to find the username\n");
echo "LDAP username attribute is '$ldapUsernameAttribute'.\n";

$filters = [
    "$ldapCASUserAttribute=*",
    "$ldapUsernameAttribute=*",
];
if (array_key_exists('filter', $extldap_config)) {
    $filters[] = $extldap_config['filter'];
}

// read 'cas_user' extra field id from internal database

$extraField = new ExtraField('user');
$extraFieldData = $extraField->get_handler_field_info_by_field_variable('cas_user');
if (empty($extraFieldData)) {
    if ('y' === readline("Create missing 'cas_user' extra field ? (type 'y' to confirm) ")) {
        $fieldId = $extraField->save(
            [
                'variable' => 'cas_user',
                'field_type' => ExtraField::FIELD_TYPE_TEXT,
                'visible_to_self' => true,
                'filter' => true,
                'display_text' => get_lang('CAS User Identifier'),
            ]
        );
        if (false === $fieldId) {
            exit("failed to create extra field\n");
        }
    } else {
        exit("Required extra field is missing\n");
    }
} else {
    $fieldId = $extraFieldData['id'];
}
echo "'cas_user' extra field id is $fieldId.\n";

// read cas_user extra field existing values as an associative array ( user id => CAS code )

$extraFieldValueModel = new ExtraFieldValue('user');
$recordList = $extraFieldValueModel->getValuesByFieldId($fieldId);
$existingCasUserValues = [];
if (false !== $recordList) {
    foreach ($recordList as $value) {
        $existingCasUserValues[$value['item_id']] = $value['value'];
    }
}
echo count($existingCasUserValues)." users have their cas_user value set already.\n";

// read all users from the internal database and check their LDAP CAS code to build a to-do list

$userRepository = Database::getManager()->getRepository('ChamiloUserBundle:User');
$databaseUsers = $userRepository->findAll();
$count = count($databaseUsers);
echo "$count users are registered in the internal database.\n";

$userNamesInUse = [];
foreach ($databaseUsers as $user) {
    $userNamesInUse[$user->getUsername()] = $user->getId();
}

$missingCASCodes = [];
$wrongCASCodes = [];
$wrongUserNames = [];
$wrongAuthSources = [];

$checked = 0;
foreach ($databaseUsers as $user) {
    $username = $user->getUsername();
    echo "Checked $checked / $count users - now checking '$username'…\r";
    $filter = '(&('
        .join(
            ')(',
            array_merge($filters, ["|($ldapUsernameAttribute=$username)($ldapCASUserAttribute=$username)"])
        )
        .'))';
    $searchResult = ldap_search($ldap, $baseDn, $filter, [$ldapCASUserAttribute, $ldapUsernameAttribute]);
    if (false === $searchResult) {
        exit('ldap_search() failed: '.ldap_error($ldap)."\n");
    }
    $userId = $user->getId();
    echo "$username ($userId): ";
    switch (ldap_count_entries($ldap, $searchResult)) {
        case 0:
            print "does not exist in the LDAP directory, skipping.\n";
            break;
        case 1:
            $entry = ldap_first_entry($ldap, $searchResult);
            if (false === $entry) {
                exit('ldap_first_entry() failed: '.ldap_error($ldap)."\n");
            }
            $ldapCASUser = ldap_get_values($ldap, $entry, $ldapCASUserAttribute)[0];
            if (false === $ldapCASUser) {
                exit('cannot read CAS user code from LDAP entry: '.ldap_error($ldap)."\n");
            }
            $ldapUsername = ldap_get_values($ldap, $entry, $ldapUsernameAttribute)[0];
            if (false === $ldapUsername) {
                exit('cannot read username from LDAP entry: '.ldap_error($ldap)."\n");
            }
            echo "\033[2K\r$ldapUsernameAttribute: $ldapUsername, $ldapCASUserAttribute: $ldapCASUser, ";
            $problems = [];
            if ($username === $ldapUsername) {
                //true;
            } elseif (in_array(
                strtolower(trim($username)),
                [strtolower(trim($ldapUsername)), strtolower(trim($ldapCASUser))]
            )) {
                if (array_key_exists($ldapUsername, $userNamesInUse)) {
                    echo "wrong username but '$ldapUsername' is already taken, skipping.\n";
                    break;
                } else {
                    $problems[] = "wrong username";
                    $wrongUserNames[$userId] = $ldapUsername;
                    $userNamesInUse[$ldapUsername] = $userId;
                }
            } else {
                exit("LDAP search result does not match username; our filter is wrong: $filter\n");
            }
            if (array_key_exists($userId, $existingCasUserValues)) {
                $currentValue = $existingCasUserValues[$userId];
                if ($currentValue !== $ldapCASUser) {
                    $problems[] = "wrong current CAS user code '$currentValue'";
                    $wrongCASCodes[$userId] = $ldapCASUser;
                }
            } else {
                $problems[] = "CAS user code missing in database";
                $missingCASCodes[$userId] = $ldapCASUser;
            }
            $currentAuthSource = $user->getAuthSource();
            if (CAS_AUTH_SOURCE !== $currentAuthSource) {
                $problems[] = "wrong auth source '$currentAuthSource'";
                $wrongAuthSources[$userId] = true;
            }
            echo empty($problems) ? "ok\r" : (join(', ', $problems)."\n");
            break;
        default:
            print "more than 1 entries for username '$username' in the LDAP directory for user id=$userId, skipping.\n";
    }
    $checked++;
}
echo "\033[2K\r";

// ask for confirmation and write changes to the database

$fixUsernames = (
    !empty($wrongUserNames)
    &&
    ('y' === readline("Fix wrong user names for ".count($wrongUserNames)." users ? (type 'y' to confirm) "))
);
if ($fixUsernames) {
    echo "I will fix user names.\n";
}

$fixMissingCASCodes = (
    !empty($missingCASCodes)
    &&
    ('y' === readline("Fix missing CAS codes for ".count($missingCASCodes)." users ? (type 'y' to confirm) "))
);
if ($fixMissingCASCodes) {
    echo "I will fix missing CAS codes.\n";
}

$fixWrongCASCodes = (
    !empty($wrongCASCodes)
    &&
    ('y' === readline("Fix wrong CAS codes for ".count($wrongCASCodes)." users ? (type 'y' to confirm) "))
);
if ($fixWrongCASCodes) {
    echo "I will fix wrong CAS codes.\n";
}

$fixWrongAuthSources = (
    !empty($wrongAuthSources)
    &&
    ('y' === readline("Fix auth source for ".count($wrongAuthSources)." users ? (type 'y' to confirm) "))
);
if ($fixWrongAuthSources) {
    echo "I will fix wrong authentication sources.\n";
}

if ($fixUsernames || $fixWrongAuthSources || $fixWrongCASCodes || $fixMissingCASCodes) {
    $usersToFix = [];
    foreach ($databaseUsers as $user) {
        $userId = $user->getId();
        if ($fixUsernames && array_key_exists($userId, $wrongUserNames)
            || $fixWrongAuthSources && array_key_exists($userId, $wrongAuthSources)
            || $fixMissingCASCodes && array_key_exists($userId, $missingCASCodes)
            || $fixWrongCASCodes && array_key_exists($userId, $wrongCASCodes)
        ) {
            $usersToFix[] = $user;
        }
    }
    $fixCount = count($usersToFix);
    echo "Now fixing $fixCount out of $count database users…\n";
    $done = 0;
    foreach ($usersToFix as $user) {
        $userId = $user->getId();
        $dirty = false;
        if ($fixUsernames && array_key_exists($userId, $wrongUserNames)) {
            $user->setUsername($wrongUserNames[$userId]);
            $dirty = true;
        }
        if ($fixWrongAuthSources && array_key_exists($userId, $wrongAuthSources)) {
            $user->setAuthSource(CAS_AUTH_SOURCE);
            $dirty = true;
        }
        if ($dirty) {
            try {
                UserManager::getManager()->save($user);
            } catch (Exception $exception) {
                echo $exception->getMessage()."\n";
                exit("Script stopped before the end.\n");
            }
        }
        if ($fixMissingCASCodes && array_key_exists($userId, $missingCASCodes)) {
            UserManager::update_extra_field_value($userId, 'cas_user', $missingCASCodes[$userId]);
        } elseif ($fixWrongCASCodes && array_key_exists($userId, $wrongCASCodes)) {
            UserManager::update_extra_field_value($userId, 'cas_user', $wrongCASCodes[$userId]);
        }
        $done++;
        echo "Fixed $done / $fixCount users\r";
    }
    echo "\n";
}

echo "End of script.\n";
