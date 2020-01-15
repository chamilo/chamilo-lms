<?php
if (php_sapi_name() !== 'cli') die("this script is supposed to be run from the command-line\n");
require __DIR__.'/../../cli-config.php';
require_once __DIR__.'/../../app/config/auth.conf.php';
require_once __DIR__.'/../../main/inc/lib/api.lib.php';
require_once __DIR__.'/../../main/inc/lib/database.constants.inc.php';
require_once __DIR__.'/../../main/inc/lib/internationalization.lib.php';
require_once __DIR__.'/../../main/inc/lib/text.lib.php';


// Bind to LDAP server

$ldap = false;
foreach($extldap_config['host'] as $ldapHost) {
    $ldap = array_key_exists('port', $extldap_config)
        ? ldap_connect($ldapHost, $extldap_config['port'])
        : ldap_connect($ldapHost);
    if (false !== $ldap) {
        break;
    }
}
if (false === $ldap) die("ldap_connect() failed\n");
print "Connected to LDAP server $ldapHost.\n";

ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION,
    array_key_exists('protocol_version', $extldap_config) ? $extldap_config['protocol_version'] : 2);

ldap_set_option($ldap, LDAP_OPT_REFERRALS,
    array_key_exists('referrals', $extldap_config) ? $extldap_config['referrals'] : false);

ldap_bind($ldap, $extldap_config['admin_dn'], $extldap_config['admin_password'])
or die('ldap_bind() failed: '.ldap_error($ldap)."\n");
print "Bound to LDAP server as ${extldap_config['admin_dn']}.\n";


// set a few variables for LDAP search

$baseDn = $extldap_config['base_dn']
or die("cannot read the LDAP directory base DN where to search for user entries\n");
print "Base DN is '$baseDn'.\n";

$ldapCASUserAttribute = $extldap_user_correspondance['extra']['cas_user']
or die("cannot read the name of the LDAP attribute where to find the CAS user code\n");
print "LDAP CAS user code attribute is '$ldapCASUserAttribute'.\n";

$ldapUsernameAttribute = $extldap_user_correspondance['username']
or die("cannot read the name of the LDAP attribute where to find the username\n");
print "LDAP username attribute is '$ldapUsernameAttribute'.\n";

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
    if ('y' === readline(
            "Create missing 'cas_user' extra field ?"
            . " (type 'y' to confirm) "
        )) {
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
            die("failed to create extra field\n");
        }
    } else {
        die("Required extra field is missing\n");
    }
} else {
    $fieldId = $extraFieldData['id'];
}
print "'cas_user' extra field id is $fieldId.\n";


// read cas_user extra field existing values as an associative array ( user id => CAS code )

$extraFieldValueModel = new ExtraFieldValue('user');
$recordList = $extraFieldValueModel->getValuesByFieldId($fieldId);
$existingCasUserValues = [];
if (false !== $recordList) {
    foreach($recordList as $value) {
        $existingCasUserValues[$value['item_id']] = $value['value'];
    }
}
print count($existingCasUserValues)." users have their cas_user value set already.\n";


// read all users from the internal database and check their LDAP CAS code to build a to-do list

$userRepository = Database::getManager()->getRepository('ChamiloUserBundle:User');
$databaseUsers = $userRepository->findAll();
$count = count($databaseUsers);
print "$count users are registered in the internal database.\n";

$userNamesInUse = [];
foreach($databaseUsers as $user) {
    $userNamesInUse[$user->getUsername()] = $user->getId();
}

$missingCASCodes = [];
$wrongCASCodes = [];
$wrongUserNames = [];
$wrongAuthSources = [];

$checked = 0;
foreach($databaseUsers as $user) {
    $username = $user->getUsername();
    print "Checked $checked / $count users - now checking '$username'…\r";
    $filter = '(&('.join(
            ')(',
            array_merge($filters, ["|($ldapUsernameAttribute=$username)($ldapCASUserAttribute=$username)"])).'))';
    $searchResult = ldap_search($ldap, $baseDn, $filter, [$ldapCASUserAttribute, $ldapUsernameAttribute]);
    if (false === $searchResult) die('ldap_search() failed: '.ldap_error($ldap)."\n");
    $userId = $user->getId();
    print "$username ($userId): ";
    switch (ldap_count_entries($ldap, $searchResult)) {
        case 0:
            print "does not exist in the LDAP directory, skipping.\n";
            break;
        case 1:
            $entry = ldap_first_entry($ldap, $searchResult);
            if (false === $entry) die('ldap_first_entry() failed: '.ldap_error($ldap)."\n");
            $ldapCASUser = ldap_get_values($ldap, $entry, $ldapCASUserAttribute)[0];
            if (false === $ldapCASUser) die('cannot read CAS user code from LDAP entry: '.ldap_error($ldap)."\n");
            $ldapUsername = ldap_get_values($ldap, $entry, $ldapUsernameAttribute)[0];
            if (false === $ldapUsername) die('cannot read username from LDAP entry: '.ldap_error($ldap)."\n");
            print "\033[2K\r$ldapUsernameAttribute: $ldapUsername, $ldapCASUserAttribute: $ldapCASUser, ";
            $problems = [];
            if ($username === $ldapUsername) {
                // fine
            } else if (
                strtolower(trim($username)) === strtolower(trim($ldapUsername))
                ||
                strtolower(trim($username)) === strtolower(trim($ldapCASUser))
            ) {
                if (array_key_exists($ldapUsername, $userNamesInUse)) {
                    print "wrong username but '$ldapUsername' is already taken, skipping.\n";
                    break;
                } else {
                    $problems[] = "wrong username";
                    $wrongUserNames[$userId] = $ldapUsername;
                    $userNamesInUse[$ldapUsername] = $userId;
                }
            } else {
                die("LDAP search result does not match username; our filter is wrong: $filter\n");
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
            if (CAS_AUTH_SOURCE === $currentAuthSource) {
            } else {
                $problems[] = "wrong auth source '$currentAuthSource'";
                $wrongAuthSources[$userId] = true;
            }
            print (empty($problems) ? "ok\r" : (join(', ', $problems)."\n"));
            break;
        default:
            print "more than one entries for username '$username' in the LDAP directory for user id=$userId, skipping.\n";
    }
    $checked ++;
}
print "\033[2K\r";


// ask for confirmation and write changes to the database

$fixUsernames = (
    !empty($wrongUserNames)
    &&
    ('y' === readline(
            "Fix wrong user names for ".count($wrongUserNames)." users ?"
            . " (type 'y' to confirm) "
        )
    )
);
if ($fixUsernames) print "I will fix user names.\n";

$fixMissingCASCodes = (
    !empty($missingCASCodes)
    &&
    ('y' === readline(
            "Fix missing CAS codes for ".count($missingCASCodes)." users ?"
            . " (type 'y' to confirm) "
        )
    )
);
if ($fixMissingCASCodes) print "I will fix missing CAS codes.\n";

$fixWrongCASCodes = (
    !empty($wrongCASCodes)
    &&
    ('y' === readline(
            "Fix wrong CAS codes for ".count($wrongCASCodes)." users ?"
            . " (type 'y' to confirm) "
        )
    )
);
if ($fixWrongCASCodes) print "I will fix wrong CAS codes.\n";

$fixWrongAuthSources = (
    !empty($wrongAuthSources)
    &&
    ('y' === readline(
            "Fix auth source for ".count($wrongAuthSources)." users ?"
            . " (type 'y' to confirm) "
        )
    )
);
if ($fixWrongAuthSources) print "I will fix wrong authentication sources.\n";

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
    print "Now fixing $fixCount out of $count database users…\n";
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
            } catch(Exception $exception) {
                print $exception->getMessage()."\n";
                die ("Script stopped before the end.\n");
            }
        }
        if ($fixMissingCASCodes && array_key_exists($userId, $missingCASCodes)) {
            UserManager::update_extra_field_value($userId, 'cas_user', $missingCASCodes[$userId]);
        } else if ($fixWrongCASCodes && array_key_exists($userId, $wrongCASCodes)) {
            UserManager::update_extra_field_value($userId, 'cas_user', $wrongCASCodes[$userId]);
        }
        $done ++;
        print "Fixed $done / $fixCount users\r";
    }
    print "\n";
}

print "End of script.\n";
