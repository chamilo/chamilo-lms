<?php

/* For licensing terms, see /license.txt */

/*
User account synchronisation from CSV file

This script
creates new user accounts found in the CSV files (if multiURL is enable, then we use 1 csv file per URL, the CSV file name contains the URL ID)
disables user accounts not found in any CSV files (it disables the user for all URLs)
or delete the user depending on the variable deleteUsersNotFoundInCSV (only if the user has auth_source === OpenId)
updates existing user accounts found in the CSV, re-enabling them if disabled (it applies for all URLs) only if option reenableUsersFoundInCSV is set to true.
anonymizes user accounts disabled for more than 3 years (applies for all URLs) only if the variable is set to true (by default).

This script can be run unattended.

For the field correspondance we use the title of the columns, each title corresponding to a user field and if the column start with extra_ then it is considered as an extrafield. 

username field is used to identify and match CSV and Chamilo accounts together.
*/
exit;
// Change this to the absolute path to chamilo root folder if you move the script out of tests/scripts
$chamiloRoot = __DIR__.'/../..';

// Set to true in order to get a trace of changes made by this script
$debug = false;

// Set to test mode by default to only show the output, put this test variable to 0 to enable creation, modificaction and deletion of users
$test = 1;

// It defines if the user not found in any of the CSV files but present in Chamilo should be deleted or disabled. By default it will be disabled.
// Set it to true for users to be deleted.
$deleteUsersNotFoundInCSV = false;

// Re-enable users found in CSV file and that where present but inactivated in Chamilo
$reenableUsersFoundInCSV = false;

// Anonymize user accounts disabled for more than 3 years
$anonymizeUserAccountsDisbaledFor3Years = false;

// List of username of accounts that should not be disabled or deleted if not present in CSV
// For exemple the first admin and the anonymous user that has no username ('')
//$usernameListNotToTouchEvenIfNotInCSV = ['admin','','test'];

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
require_once $chamiloRoot.'/main/inc/lib/database.constants.inc.php';

ini_set('memory_limit', -1);

$allCSVUsers = [];
const EXTRA_KEY = 'extra_';

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
}

if ($debug) {
    echo "accessUrls = " . print_r($accessUrls,1);
}

$allCSVUsers = [];
foreach ($accessUrls as $accessUrl) {
    $accessUrlId = $accessUrl['id'];
    // Read the content of the csv file for this url (file name is URLID_users.csv so for $accessUrlId = 0 the file name would be 0_users.csv
    $filename = $accessUrlId . "_users.csv"
    $CSVUsers = Import :: csvToArray($filename);

    // create new user accounts found in the CSV and update the existing ones, re-enabling if necessary
    foreach ($CSVUsers as $CSVuser) {
        if (empty($CSVuser['username']) {
	    continue;
	}
	$username = $CSVuser['username'];
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
                echo "CSVUser = " . print_r($CSVUser,1) . "\n";
            }
        }
        if ($debug) {
            echo 'Updating ' . $username . ' fields '."\n";
        }
        if (!$test) {
            foreach ($CSVuser as $fieldValue => $fieldName) {
                // verify if it's an extra field or not (if it contains EXTRA_KEY at the begining of the name)
                
                // update every field and extra field of the user
                
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
                UrlManager::add_user_to_url($user->getId(), $accessUrlId);
            }
        }
        $allCSVUsers[$username] = $user;
    }
}

// disable or delete user accounts not found in any CSV file depending on $deleteUsersNotFoundInLDAP

$now = new DateTime();
foreach (array_diff(array_keys($dbUsers), array_keys($allCSVUsers)) as $usernameToDisable) {
    if (in_array($usernameToDisable, $usernameListNotToTouchEvenIfNotInCSV)) {
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
