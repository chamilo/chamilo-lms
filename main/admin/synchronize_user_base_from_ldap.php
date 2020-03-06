<?php
/* For licensing terms, see /license.txt */
/*
User account synchronisation from LDAP

This script
creates new user accounts found in the LDAP directory
disables user accounts not found in the LDAP directory
updates existing user accounts found in the LDAP directory, re-enabling them if disabled
anonymizes user accounts disabled for more than 3 years

This script can be run unattended.

It does not read any parameter from the command line, but uses the global configuration arrays
 $extldap_config
and
 $extldap_user_correspondance
defined in app/config/auth.conf.php.

username field is used to identify and match LDAP and Chamilo accounts together.
($extldap_user_correspondance['username'])

If more than one LDAP entries share the same username, a warning is printed on stderr and the user account is skipped.

All the corrections are only applied in phase 2, and take time.
*/

use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\UserBundle\Entity\User;
use Doctrine\ORM\OptimisticLockException;

if (php_sapi_name() !== 'cli') {
    die("this script is supposed to be run from the command-line\n");
}

require __DIR__.'/../../cli-config.php';
require_once __DIR__.'/../../app/config/auth.conf.php';
require_once __DIR__.'/../../main/inc/lib/api.lib.php';
require_once __DIR__.'/../../main/inc/lib/database.constants.inc.php';

$debug = true;


// Retreive information from $extldap_user_correspondance and extra fields
// into $tableFields, $extraFields, $allFields and $ldapAttributes

$tableFields = [];
$extraFields = [];
$ldapAttributes = [];
$tableFieldMap = $extldap_user_correspondance;
$extraFieldMap = [];
const EXTRA_ARRAY_KEY = 'extra';
if (array_key_exists(EXTRA_ARRAY_KEY, $tableFieldMap) and is_array($tableFieldMap[EXTRA_ARRAY_KEY])) {
    $extraFieldMap = $tableFieldMap[EXTRA_ARRAY_KEY];
    unset($tableFieldMap[EXTRA_ARRAY_KEY]);
}
$extraFieldRepository = Database::getManager()->getRepository('ChamiloCoreBundle:ExtraField');
$extraFieldValueRepository = Database::getManager()->getRepository('ChamiloCoreBundle:ExtraFieldValues');
foreach ([false => $tableFieldMap, true => $extraFieldMap] as $areExtra => $fields) {
    foreach ($fields as $name => $value) {
        $userField = (object)[
            'name' => $name,
            'constant' => '!' === $value[0] ? substr($value, 1) : null,
            'function' => 'func' === $value,
            'ldapAttribute' => ('!' !== $value[0] and 'func' !== $value) ? $value : null,
        ];
        if (!$userField->constant and !$userField->function) {
            $ldapAttributes[] = $value;
        }
        if ($areExtra) {
            $userField->extraField = $extraFieldRepository->findOneBy(
                [
                    'extraFieldType' => ExtraField::USER_FIELD_TYPE,
                    'variable' => $name,
                ]
            ) or die("Cannot find user extraFieldMap field '$name'\n");
            foreach ($extraFieldValueRepository->findBy(['field' => $userField->extraField]) as $extraFieldValue) {
                $userField->extraFieldValues[$extraFieldValue->getItemId()] = $extraFieldValue;
            }
            $extraFields[] = $userField;
        } else {
            try {
                $userField->getter = new ReflectionMethod('\Chamilo\UserBundle\Entity\User', 'get' . ucfirst($name));
                $userField->setter = new ReflectionMethod('\Chamilo\UserBundle\Entity\User', 'set' . ucfirst($name));
            } catch (ReflectionException $exception) {
                die($exception->getMessage()."\n");
            }
            $tableFields[] = $userField;
        }
    }
}

$allFields = array_merge($tableFields, $extraFields);

// Retrieve source information from LDAP

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
    die("ldap_connect() failed\n");
}
if ($debug) {
    echo "Connected to LDAP server $ldapHost.\n";
}

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
or die('ldap_bind() failed: ' . ldap_error($ldap) . "\n");
if ($debug) {
    echo "Bound to LDAP server as ${extldap_config['admin_dn']}.\n";
}

$baseDn = $extldap_config['base_dn']
or die("cannot read the LDAP directory base DN where to search for user entries\n");

$ldapUsernameAttribute = $extldap_user_correspondance['username']
or die("cannot read the name of the LDAP attribute where to find the username\n");

$filter = "$ldapUsernameAttribute=*";

if (array_key_exists('filter', $extldap_config)) {
    $filter = '(&('.$filter.')('.$extldap_config['filter'].'))';
}

$searchResult = ldap_search($ldap, $baseDn, $filter, $ldapAttributes)
or die("ldap_search(\$ldap, '$baseDn', '$filter', [".join(',', $ldapAttributes).']) failed: '.ldap_error($ldap)."\n");

if ($debug) {
    echo ldap_count_entries($ldap, $searchResult) . " LDAP entries found\n";
}

$ldapUsers = [];
$entry = ldap_first_entry($ldap, $searchResult);
while (false !== $entry) {
    $ldapUser = [];
    foreach ($allFields as $userField) {
        if (!is_null($userField->constant)) {
            $value = $userField->constant;
        } elseif ($userField->function) {
            switch ($userField->name) {
                case 'status':
                    $value = STUDENT;
                    break;
                case 'admin':
                    $value = false;
                    break;
                default:
                    die("'func' not implemented for $userField->name\n");
            }
        } else {
            $values = ldap_get_values($ldap, $entry, $userField->ldapAttribute)
            or die(
                'cannot read attribute ' . $userField->ldapAttribute
                . ' from entry ' . print_r($entry, true)
                . "\n"
            );
            (1 === $values['count']) or die(
                $values['count'] . ' values found'
                . ' in attribute ' . $userField->ldapAttribute
                . ' of entry ' . print_r($entry, true)
                . "\n"
            );
            $value = $values[0];
        }
        $ldapUser[$userField->name] = $value;
    }
    $username = $ldapUser['username'];
    array_key_exists($username, $ldapUsers)
    and die("duplicate username '$username' found in LDAP\n");
    $ldapUsers[$username] = $ldapUser;
    $entry = ldap_next_entry($ldap, $entry);
}

ldap_close($ldap);


// read all users from the internal database

$userRepository = Database::getManager()->getRepository('ChamiloUserBundle:User');
$dbUsers = [];
foreach ($userRepository->findAll() as $user) {
    if ($user->getId() > 1) {
        $username = $user->getUsername();
        array_key_exists($username, $dbUsers) and die("username $username found twice in the database\n");
        $dbUsers[$username] = $user;
    }
}
if ($debug) {
    echo count($dbUsers) . " non-admin users found in internal database\n";
}


// disable user accounts not found in the LDAP directory

foreach (array_diff(array_keys($dbUsers), array_keys($ldapUsers)) as $usernameToDisable) {
    $user = $dbUsers[$usernameToDisable];
    if ($user->isActive()) {
        $user->setActive(false);
        UserManager::getManager()->save($user, false);
        if ($debug) {
            echo 'Disabled ' . $user->getUsername() . "\n";
        }
    }
}
try {
    Database::getManager()->flush();
} catch (OptimisticLockException $exception) {
    die($exception->getMessage()."\n");
}


// create new user accounts found in the LDAP directory and update the existing ones, re-enabling them if disabled

foreach ($ldapUsers as $username => $ldapUser) {
    if (array_key_exists($username, $dbUsers)) {
        $user = $dbUsers[$username];
    } else {
        $user = new User();
        $dbUsers[$username] = $user;
        if ($debug) {
            echo 'Created ' . $username . "\n";
        }
    }
    foreach ($tableFields as $userField) {
        $value = $ldapUser[$userField->name];
        if ($userField->getter->invoke($user) !== $value) {
            $userField->setter->invoke($user, $value);
            if ($debug) {
                echo 'Updated ' . $username . ' field '.$userField->name."\n";
            }
        }
    }
    if (!$user->isActive()) {
        $user->setActive(true);
    }
    UserManager::getManager()->save($user, false);
}
try {
    Database::getManager()->flush();
} catch (OptimisticLockException $exception) {
    die($exception->getMessage()."\n");
}


// also update extraFieldMap field values

foreach ($ldapUsers as $username => $ldapUser) {
    $user = $dbUsers[$username];
    foreach ($extraFields as $userField) {
        $value = $ldapUser[$userField->name];
        if (array_key_exists($user->getId(), $userField->extraFieldValues)) {
            /**
             * @var ExtraFieldValues $extraFieldValue
             */
            $extraFieldValue = $userField->extraFieldValues[$user->getId()];
            if ($extraFieldValue->getValue() !== $value) {
                $extraFieldValue->setValue($value);
                Database::getManager()->persist($extraFieldValue);
                if ($debug) {
                    echo 'Updated ' . $username . ' extraFieldMap field ' . $userField->name . "\n";
                }
            }
        } else {
            $extraFieldValue = new ExtraFieldValues();
            $extraFieldValue->setValue($value);
            $extraFieldValue->setField($userField->extraField);
            $extraFieldValue->setItemId($user->getId());
            Database::getManager()->persist($extraFieldValue);
            $userField->extraFieldValues[$user->getId()] = $extraFieldValue;
            if ($debug) {
                echo 'Created ' . $username . ' extraFieldMap field ' . $userField->name . "\n";
            }
        }
    }
}
try {
    Database::getManager()->flush();
} catch (OptimisticLockException $exception) {
    die($exception->getMessage()."\n");
}

// anonymize user accounts disabled for more than 3 years

$result = Database::query(
    'select default_value
from track_e_default
where default_event_type=\'user_disable\' and default_value_type = \'user_id\'
group by default_value
having max(default_date) < date_sub(now(), interval 3 year)
except
select default_value
from track_e_default
where default_event_type=\'user_anonymized\' and default_value_type = \'user_id\'
group by default_value'
);
if ($result->errorCode() !== '00000') {
    die('Could not retreive anonymizable user id list from database:'.print_r($result->errorInfo(), true."\n"));
}
$userId = $result->fetchColumn();
while (false !== $userId) {
    try {
        UserManager::anonymize($userId)
        or die("could not anonymize user $userId\n");
    } catch (Exception $exception) {
        die($exception->getMessage()."\n");
    }
    if ($debug) {
        echo "Anonymized user $userId\n";
    }
    $userId = $result->fetchColumn();
}
