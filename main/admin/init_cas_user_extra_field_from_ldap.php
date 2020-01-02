<?php
if (php_sapi_name() !== 'cli') die('this script is supposed to be run from the command-line');
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
if (false === $ldap) die('ldap_connect() failed');
print "Connected to LDAP server $ldapHost.\n";

ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION,
    array_key_exists('protocol_version', $extldap_config) ? $extldap_config['protocol_version'] : 2);

ldap_set_option($ldap, LDAP_OPT_REFERRALS,
    array_key_exists('referrals', $extldap_config) ? $extldap_config['referrals'] : false);

ldap_bind($ldap, $extldap_config['admin_dn'], $extldap_config['admin_password'])
or die('ldap_bind() failed: '.ldap_error($ldap));
print "Bound to LDAP server as ${extldap_config['admin_dn']}.\n";


// set a few variables for LDAP search

$baseDn = $extldap_config['base_dn']
or die('cannot read the LDAP directory base DN where to search for user entries');
print "Base DN is '$baseDn'.\n";

$ldapCASUserAttribute = $extldap_user_correspondance['extra']['cas_user']
or die('cannot read the name of the LDAP attribute where to find the CAS user code');
print "LDAP CAS user code attribute is '$ldapCASUserAttribute'.\n";

$ldapUsernameAttribute = $extldap_user_correspondance['username']
or die('cannot read the name of the LDAP attribute where to find the username');
print "LDAP username attribute is '$ldapUsernameAttribute'.\n";

$filters = [
    "$ldapCASUserAttribute=*",
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
            die('failed to create extra field');
        }
    } else {
        die('Required extra field is missing');
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

$usersMissingCasCode = [];
$usersWithWrongCASCode = [];
$checked = 0;
foreach($databaseUsers as $user) {
    $username = $user->getUsername();
    print "Checked $checked / $count users - now checking '$username'…\r";
    $filter = '(&(' . join(')(', array_merge($filters, ["$ldapUsernameAttribute=$username"])). '))';
    $searchResult = ldap_search($ldap, $baseDn, $filter, [$ldapCASUserAttribute]);
    if (false === $searchResult) die('ldap_search() failed: '.ldap_error($ldap));
    switch (ldap_count_entries($ldap, $searchResult)) {
        case 0:
            print "User '$username' does not exist in the LDAP directory, skipping.\n";
            break;
        case 1:
            $entry = ldap_first_entry($ldap, $searchResult);
            if (false === $entry) die('ldap_first_entry() failed: '.ldap_error($ldap));
            $casUser = ldap_get_values($ldap, $entry, $ldapCASUserAttribute)[0];
            if (false === $casUser) die('cannot read CAS user code from LDAP entry: '.ldap_error($ldap));
            print "User '$username' LDAP entry has CAS user code '$casUser'";
            $userId = $user->getId();
            if (array_key_exists($userId, $existingCasUserValues)) {
                $currentValue = $existingCasUserValues[$userId];
                if ($currentValue === $casUser) {
                    print ", which is the same as current value in database. No change.\n";
                } else {
                    print ", which is DIFFERENT from its current value in database, '$currentValue'.\n";
                    $usersWithWrongCASCode[] = [$user, $casUser];
                }
            } else {
                print ", missing from the database.\n";
                $usersMissingCasCode[] = [$user, $casUser];
            }
            break;
        default:
            print "more than one entries for username '$username' in the LDAP directory, skipping.\n";
    }
    $checked ++;
}


// ask for confirmation and write changes to the database

foreach ([ 'missing CAS codes' => $usersMissingCasCode,
             'wrong CAS codes' => $usersWithWrongCASCode ] as $title => $list) {
    if (!empty($list)) {
        $count = count($list);
        if ('y' === readline(
                "Fix $title for $count users and set their auth source to 'cas' ?"
                . " (type 'y' to confirm) "
            )
        ) {
            $done = 0;
            foreach ($list as $userAndCasCode) {
                $user = $userAndCasCode[0];
                $casUser = $userAndCasCode[1];
                UserManager::update_extra_field_value($user->getId(), 'cas_user', $casUser);
                $user->setAuthSource(CAS_AUTH_SOURCE);
                UserManager::getManager()->save($user);
                $done ++;
                print "Fixed $done / $count users\r";
            }
        } else {
            print "Not fixing $title.\n";
        }
    }
}

print "End of script.\n";
