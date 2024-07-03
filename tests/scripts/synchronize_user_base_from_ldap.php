<?php

/* For licensing terms, see /license.txt */

/*
User account synchronisation from LDAP

This script
creates new user accounts found in the LDAP directory (if multiURL is enable, it creates the user on the URL for which the LDAP has been configured)
disables user accounts not found in the LDAP directory (it disbales the user for all URLs) 
or delete the user depending on the variable deleteUsersNotFoundInLDAP (only if the user has auth_source === extldap)
updates existing user accounts found in the LDAP directory, re-enabling them if disabled (it applies for all URLs) only if option reenableUsersFoundInLDAP is set to true.
anonymizes user accounts disabled for more than 3 years (applies for all URLs) only if the variable is set to true (by default).

This script can be run unattended.

It does not read any parameter from the command line, but uses the global configuration arrays
 $extldap_config
and
 $extldap_user_correspondance
defined in app/config/auth.conf.php or overriden in app/config/configuration.php in MultiURL case.

username field is used to identify and match LDAP and Chamilo accounts together.
($extldap_user_correspondance['username'])
*/
exit;
// Change this to the absolute path to chamilo root folder if you move the script out of tests/scripts
$chamiloRoot = __DIR__.'/../..';

// Set to true in order to get a trace of changes made by this script
$debug = false;

// Set to test mode by default to only show the output, put this test variable to 0 to enable creation, modificaction and deletion of users
$test = 1;

// It defines if the user not find in the LDAP but present in Chamilo should be deleted or disabled. By default it will be disabled.
// Set it to true for users to be deleted.
$deleteUsersNotFoundInLDAP = false;

// Re-enable users found in LDAP and that where present but inactivated in Chamilo
$reenableUsersFoundInLDAP = false;

// Anonymize user accounts disabled for more than 3 years
$anonymizeUserAccountsDisbaledFor3Years = false;

// List of username of accounts that should not be disabled or deleted if not present in LDAP 
// For exemple the first admin and the anonymous user that has no username ('')
//$usernameListNotToTouchEvenIfNotInLDAP = ['admin','','test'];

// List of LDAP attributes that are not in extldap_user_correspondance but are needed in this script
//$extraLdapAttributes[0][] = 'description';
//$extraLdapAttributes[0][] = 'userAccountControl';

// Extra field to be emptied when user is anonimized to really make it anonyme, for example the sso id of the user
// extraFieldToEmpty = "cas_user";


use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\TrackEDefault;
use Chamilo\UserBundle\Entity\User;
use Doctrine\DBAL\FetchMode;
use Doctrine\ORM\OptimisticLockException;

if (php_sapi_name() !== 'cli') {
    die("this script is supposed to be run from the command-line\n");
}

require $chamiloRoot.'/cli-config.php';
require_once $chamiloRoot.'/main/inc/lib/api.lib.php';
require_once $chamiloRoot.'/app/config/auth.conf.php';
require_once $chamiloRoot.'/main/inc/lib/database.constants.inc.php';
require_once $chamiloRoot.'/main/auth/external_login/ldap.inc.php';

ini_set('memory_limit', -1);

// Retreive information from $extldap_user_correspondance and extra fields
// into $tableFields, $extraFields, $allFields and $ldapAttributes

$generalTableFieldMap = $extldap_user_correspondance;
$multipleUrlLDAPConfig = false;
$allLdapUsers = [];
const EXTRA_ARRAY_KEY = 'extra';

// read all users from the internal database

$userRepository = Database::getManager()->getRepository('ChamiloUserBundle:User');
$dbUsers = [];
foreach ($userRepository->findAll() as $user) {
    if ($user->getId() > 1) {
        $username = strtolower($user->getUsername());
        array_key_exists($username, $dbUsers) and die("duplicate username $username found in the database\n");
        $dbUsers[$username] = $user;
    }
}
if ($debug) {
    echo count($dbUsers) . " users with id > 1 found in internal database\n";
}

if (api_is_multiple_url_enabled()) {
    $accessUrls = api_get_access_urls(0,100000,'id');
    $multipleUrlLDAPConfig = true;
    if (!empty($extldap_config) && array_key_exists('host', $extldap_config) && !empty($extldap_config['host'])) {
        $multipleUrlLDAPConfig = false;
    }    
}

if (!$multipleUrlLDAPConfig) {
    $accessUrls[0]['id'] = 0;
    $generalTableFieldMap[0] = $generalTableFieldMap;
}
if ($debug) {
    echo "accessUrls = " . print_r($accessUrls,1);
}
foreach ($accessUrls as $accessUrl) {
    $tableFields = [];
    $extraFields = [];
    $extraFieldMap = [];
    $accessUrlId = $accessUrl['id'];
    global $_configuration;
    $_configuration['access_url'] = $accessUrlId;
    $extldap_config[$accessUrlId] = api_get_configuration_value('extldap_config');
    $generalTableFieldMap[$accessUrlId] = $extldap_user_correspondance[$accessUrlId] = api_get_configuration_value('extldap_user_correspondance');
    $ldapAttributes = $extraLdapAttributes[$accessUrlId];
    if (array_key_exists($accessUrlId, $generalTableFieldMap) && is_array($generalTableFieldMap[$accessUrlId])) {
        $tableFieldMap = $generalTableFieldMap[$accessUrlId];
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
                    ) or die("Cannot find user extra field '$name'\n");
                    foreach ($extraFieldValueRepository->findBy(['field' => $userField->extraField]) as $extraFieldValue) {
                        $userField->extraFieldValues[$extraFieldValue->getItemId()] = $extraFieldValue;
                    }
                    $extraFields[] = $userField;
                } elseif ($name !== 'admin') {
                    try {
                        $userField->getter = new ReflectionMethod(
                            '\Chamilo\UserBundle\Entity\User',
                            'get' . str_replace('_', '', ucfirst($name))
                        );
                        $userField->setter = new ReflectionMethod(
                            '\Chamilo\UserBundle\Entity\User',
                            'set' . str_replace('_', '', ucfirst($name))
                        );
                    } catch (ReflectionException $exception) {
                        die($exception->getMessage() . "\n");
                    }
                    $tableFields[] = $userField;
                }
            }
        }
        $allFields = array_merge($tableFields, $extraFields);
    }
    // Retrieve source information from LDAP

    if ($debug) {
        echo ' Entering ldap search ' . "\n";
        echo ' extldap_config = ' . print_r($extldap_config,1) . "\n";
    }
    if (!$multipleUrlLDAPConfig) {
        $extldap_config[$accessUrlId] = $extldap_config;
    }
    $ldap = false;
    if (array_key_exists($accessUrlId, $extldap_config) && is_array($extldap_config[$accessUrlId])) {
        foreach ($extldap_config[$accessUrlId]['host'] as $ldapHost) {
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
            array_key_exists('protocol_version', $extldap_config[$accessUrlId]) ? $extldap_config[$accessUrlId]['protocol_version'] : 2
        );
    
        ldap_set_option(
            $ldap,
            LDAP_OPT_REFERRALS,
            array_key_exists('referrals', $extldap_config[$accessUrlId]) ? $extldap_config[$accessUrlId]['referrals'] : false
        );

        ldap_bind($ldap, $extldap_config[$accessUrlId]['admin_dn'], $extldap_config[$accessUrlId]['admin_password'])
        or die('ldap_bind() failed: ' . ldap_error($ldap) . "\n");
        if ($debug) {
            $adminDn = $extldap_config[$accessUrlId]['admin_dn'];
            echo "Bound to LDAP server as $adminDn .\n";
        }

	$baseDn = $extldap_config[$accessUrlId]['base_dn']
        or die("cannot read the LDAP directory base DN where to search for user entries\n");

	if (!$multipleUrlLDAPConfig) {
            $extldap_user_correspondance[$accessUrlId] = $extldap_user_correspondance;
        }
        $ldapUsernameAttribute = $extldap_user_correspondance[$accessUrlId]['username']
        or die("cannot read the name of the LDAP attribute where to find the username\n");

        $filter = "$ldapUsernameAttribute=*";

        if (array_key_exists('filter', $extldap_config[$accessUrlId])) {
            $filter = '(&('.$filter.')('.$extldap_config[$accessUrlId]['filter'].'))';
        }

        $searchResult = ldap_search($ldap, $baseDn, $filter, $ldapAttributes)
        or die("ldap_search(\$ldap, '$baseDn', '$filter', [".join(',', $ldapAttributes).']) failed: '.ldap_error($ldap)."\n");

        if ($debug) {
            echo ldap_count_entries($ldap, $searchResult) . " LDAP entries found\n";
        }

        $ldapUsers = [];

        $entry = ldap_first_entry($ldap, $searchResult);
        while (false !== $entry) {
            $attributes = ldap_get_attributes($ldap, $entry);
	    $ldapUser = [];
            foreach ($allFields as $userField) {
                if (!is_null($userField->constant)) {
                    $value = $userField->constant;
		} elseif ($userField->function) {
                    $func = "extldap_get_$userField->name";
                    if (function_exists($func)) {
                        $value = extldap_purify_string($func($attributes));
                    } else {
                        die("'func' not implemented for $userField->name\n");
                    }
                } else {
                    if (array_key_exists($userField->ldapAttribute, $attributes)) {
                        $values = ldap_get_values($ldap, $entry, $userField->ldapAttribute)
                        or die(
                            'could not read value of attribute ' . $userField->ldapAttribute
                            . ' of entry ' . ldap_get_dn($ldap, $entry)
                            . "\n"
                        );
                        (1 === $values['count'])
                        or die(
                            $values['count'] . ' values found (expected only one)'
                            . ' in attribute ' . $userField->ldapAttribute
                            . ' of entry ' . ldap_get_dn($ldap, $entry)
                            . "\n"
                        );
                        $value = $values[0];
                    } else {
                        $value = '';
                    }
                }
                $ldapUser[$userField->name] = $value;
            }
	    $username = strtolower($ldapUser['username']);
            array_key_exists($username, $ldapUsers) and die("duplicate username '$username' found in LDAP\n");
            $ldapUsers[$username] = $ldapUser;
            if ($debug) {
                echo 'Adding user ' . $username . ' to ldapUsersArray ' . "\n";
                echo "ldapUser = " . print_r($ldapUser,1) . "\n";
            }
            $entry = ldap_next_entry($ldap, $entry);
        }
    
        ldap_close($ldap);
        if ($debug) {
            echo "ldapUsers = " . print_r($ldapUsers,1) . "\n";
        }

        // create new user accounts found in the LDAP directory and update the existing ones, re-enabling if necessary
        foreach ($ldapUsers as $username => $ldapUser) {
            if (array_key_exists($username, $dbUsers)) {
                $user = $dbUsers[$username];
                if ($debug) {
                    echo "User in DB = " . $username . " and user id = " . $user->getId() . "\n";
                }
            } else {
                if (!$test) {
                    $user = new User();
                    $dbUsers[$username] = $user;
                    $user->setUsernameCanonical($username);
                }
                if ($debug) {
                    echo 'Created ' . $username . "\n";
                    echo "ldapUser = " . print_r($ldapUser,1) . "\n";
                }
	    }
            if ($test) {
                if ($debug) {
                    echo 'Updated ' . $username . ' fields '."\n";
                }
            } else {
                foreach ($tableFields as $userField) {
                    $value = $ldapUser[$userField->name];
                    if ($userField->getter->invoke($user) !== $value) {
                        $userField->setter->invoke($user, $value);
                        if ($debug) {
                            echo 'Updated ' . $username . ' field '.$userField->name."\n";
                        }
                        if ($userField->name == 'email') {
                            $user->setEmailCanonical($value);
                        }
                    }
	        }
                if (!$user->isActive() and $reenableUsersFoundInLDAP) {
                    $user->setActive(true);
                }
                Database::getManager()->persist($user);
                try {
                    Database::getManager()->flush();
                } catch (OptimisticLockException $exception) {
                    die($exception->getMessage()."\n");
                }
                if($debug) {
                    echo 'Sent to DB ' . $username . " with user id = " . $user->getId() . "\n";
                }
                if ($multipleUrlLDAPConfig) {
                    UrlManager::add_user_to_url($user->getId(), $accessUrlId);
                } elseif (!api_is_multiple_url_enabled()) {
                    //we are adding by default the access_url_user table with access_url_id = 1
                    UrlManager::add_user_to_url($user->getId(), 1);
                }
            }
        }


        // also update extra field values

        if ($test) {
            if ($debug) {
                echo 'Updated ' . $username . ' extra fields ' . "\n";
            }
        } else {
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
                                echo 'Updated ' . $username . ' extra field ' . $userField->name . "\n";
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
                            echo 'Created ' . $username . ' extra field ' . $userField->name . "\n";
                        }
                    }
                }
            }
            try {
                Database::getManager()->flush();
            } catch (OptimisticLockException $exception) {
                die($exception->getMessage()."\n");
            }
        }
        $allLdapUsers = array_merge($allLdapUsers, $ldapUsers);
    }
}

// disable or delete user accounts not found in the LDAP directories depending on $deleteUsersNotFoundInLDAP

$now = new DateTime();
foreach (array_diff(array_keys($dbUsers), array_keys($allLdapUsers)) as $usernameToDisable) {
    if (in_array($usernameToDisable, $usernameListNotToTouchEvenIfNotInLDAP)) {
        if ($debug) {
            echo 'User not modified even if not present in LDAP : ' . $usernameToDisable . "\n";
        }
    } else {
        $user = $dbUsers[$usernameToDisable];
        if ($deleteUsersNotFoundInLDAP) {
            if (!$test) {
                if (!UserManager::delete_user($user->getId())) {
                    if ($debug) {
                        echo 'Unable to delete user ' . $usernameToDisable . "\n";
                    }
                }
            }
            if ($debug) {
                echo 'Deleted user ' . $usernameToDisable . "\n";
            } 
        } else {
            if (!$test) {
                if ($user->isActive()) {
                    // In order to avoid slow individual SQL updates, we do not call
                    // UserManager::disable($user->getId());
                    $user->setActive(false);
                    Database::getManager()->persist($user);
                    // In order to avoid slow individual SQL updates, we do not call
                    // Event::addEvent(LOG_USER_DISABLE, LOG_USER_ID, $user->getId());
                    $trackEDefault = new TrackEDefault();
                    $trackEDefault->setDefaultUserId(1);
                    $trackEDefault->setDefaultDate($now);
                    $trackEDefault->setDefaultEventType(LOG_USER_DISABLE);
                    $trackEDefault->setDefaultValueType(LOG_USER_ID);
                    $trackEDefault->setDefaultValue($user->getId());
                    Database::getManager()->persist($trackEDefault);
                }
            }
            if ($debug) {
                echo 'Disabled ' . $user->getUsername() . "\n";
            }
        }    
    }
}
if (!$test) {
    try {
        // Saving everything together
        Database::getManager()->flush();
    } catch (OptimisticLockException $exception) {
        die($exception->getMessage()."\n");
    }
}


// anonymize user accounts disabled for more than 3 years
if ($anonymizeUserAccountsDisbaledFor3Years) {
    $longDisabledUserIds = [];
    foreach (Database::query(
        'select default_value
        from track_e_default
        where default_event_type=\'user_disable\' and default_value_type=\'user_id\'
        group by default_value
        having max(default_date) < date_sub(now(), interval 3 year)'
    )->fetchAll(FetchMode::COLUMN) as $userId) {
        $longDisabledUserIds[] = $userId;
    }
    $anonymizedUserIds = [];
    foreach (Database::query(
        'select distinct default_value
        from track_e_default
        where default_event_type=\'user_anonymized\' and default_value_type=\'user_id\''
    )->fetchAll(FetchMode::COLUMN) as $userId) {
        $anonymizedUserIds[] = $userId;
    }
    foreach (array_diff($longDisabledUserIds, $anonymizedUserIds) as $userId) {
        $user = $userRepository->find($userId);
        if ($user && !$user->isEnabled()) {
            if (!$test) {
                try {
                    UserManager::anonymize($userId)
                    or die("could not anonymize user $userId\n");
                } catch (Exception $exception) {
                    die($exception->getMessage()."\n");
		}
                if (isset($extraFieldToEmpty)) {
                    UserManager::update_extra_field_value($userId,$extraFieldToEmpty,'');
                }
            }
            if ($debug) {
                echo "Anonymized user $userId\n";
            }
        }
    }
}
