<?php

/* For licensing terms, see /license.txt */

/*
User account synchronisation from CSV file

This script
creates new user accounts found in the CSV files (if multiURL is enable, then we use 1 csv file per URL, the CSV file name contains the URL ID in the form url_URLID_synchroUsers.csv replacing URLID by the real ID of the URL)
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
$chamiloRoot = __DIR__.'/../../public';

// Change this to the absolute path to the folder of the csv files (by default it is in the same folder as the script)
$CSVFilesPath = '.';

// Set to true in order to get a trace of changes made by this script
$debug = true;

// Set to test mode by default to only show the output, put this test variable to 0 to enable creation, modificaction y deletion of users
$test = 0;

// It defines if the user not found in any of the CSV files but present in Chamilo should be deleted or disabled. By default it will be disabled.
// Set it to true for users to be deleted.
$deleteUsersNotFoundInCSV = false;

// Re-enable users found in CSV file and that were present but inactivated in Chamilo
$reenableUsersFoundInCSV = false;

// Anonymize user accounts disabled for more than 3 years
$anonymizeUserAccountsDisbaledFor3Years = false;

// List of username of accounts that should not be disabled or deleted if not present in CSV
// For example the first admin and the anonymous user that has no username ('')
//$usernameListNotToTouchEvenIfNotInCSV = ['admin','','test'];

// Extra field to be emptied when user is anonymized to really make it anonymous, for example the sso id of the user
// $extraFieldToEmpty = "cas_user";

use Chamilo\CoreBundle\Entity\Admin;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\TrackEDefault;
use Chamilo\CoreBundle\Entity\User;
use Doctrine\ORM\OptimisticLockException;

if (php_sapi_name() !== 'cli') {
    die("this script is supposed to be run from the command-line\n");
}

require_once $chamiloRoot.'/main/inc/global.inc.php';
require_once $chamiloRoot.'/main/inc/lib/api.lib.php';
require_once $chamiloRoot.'/main/inc/lib/database.constants.inc.php';

ini_set('memory_limit', -1);

$statusList = [
    'teacher' => 1,        // COURSEMANAGER
    'session_admin' => 3,  // SESSIONADMIN
    'drh' => 4,            // DRH
    'user' => 5,           // STUDENT
    'anonymous' => 6,      // ANONYMOUS
    'invited' => 20        // INVITEE
];

$entityManager = Database::getManager();
$allCSVUsers = [];
const EXTRA_KEY = 'extra_';

// Read all users from the internal database
$userRepository = $entityManager->getRepository(User::class);
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

$adminRepo = $entityManager->getRepository(Admin::class);
$firstAdmin = $adminRepo->createQueryBuilder('a')
    ->setMaxResults(1)
    ->getQuery()
    ->getOneOrNullResult();

if ($firstAdmin) {
    $creator = $firstAdmin->getUser();
} else {
    die("No admin found in the database\n");
}

$accessUrls = api_get_access_urls(0, 100000, 'id');
foreach ($accessUrls as $accessUrl) {
    $accessUrlId = $accessUrl['id'];
    $filename = $CSVFilesPath . "/url_" . $accessUrlId . "_synchroUsers.csv";

    if (!file_exists($filename)) {
        if ($debug) {
            echo "CSV file not found: $filename\n";
        }
        continue;
    }

    $urlUsersIdList = [];
    $CSVUsers = Import::csvToArray($filename, ',');

    if (!$CSVUsers) {
        die("Failed to parse CSV file: $filename\n");
    }

    // Debug message
    echo "Processing file: $filename with " . count($CSVUsers) . " users\n";

    $fieldMapping = [
        'username' => 'setUsername',
        'lastname' => 'setLastname',
        'firstname' => 'setFirstname',
        'email' => 'setEmail',
        'officialcode' => 'setOfficialCode',
        'phonenumber' => 'setPhone',
        'status' => 'setStatus',
        'expirydate' => 'setExpirationDate',
        'active' => 'setActive',
        'language' => 'setLocale',
        'password' => 'setPlainPassword',
        'authsource' => 'setAuthSource'
    ];

    // Create new user accounts found in the CSV and update the existing ones, re-enabling if necessary
    foreach ($CSVUsers as $CSVuser) {

        try {

            $newUser = false;
            $CSVuser = array_change_key_case($CSVuser); // Convert keys to lowercase

            if (empty($CSVuser['username'])) {
                echo "Skipping user with empty username\n";
                continue;
            }
            $username = strtolower($CSVuser['username']);
            if (array_key_exists($username, $dbUsers)) {
                $user = $dbUsers[$username];
                if ($debug) {
                    echo "User in DB = " . $username . " and user id = " . $user->getId() . "\n";
                }
            } else {
                if (!$test) {
                    $user = new User();
                    $dbUsers[$username] = $user;
                    $user->setUsername($username);
                    $user->setUsernameCanonical($username);
                    $newUser = true;
                }
                if ($debug) {
                    echo 'Created ' . $username . "\n";
                    echo "CSVUser = " . print_r($CSVuser, 1) . "\n";
                }
            }
            if ($debug) {
                echo 'Updating ' . $username . ' fields ' . "\n";
            }

            if (!$test) {
                $passwordSet = false;
                foreach ($CSVuser as $fieldName => $fieldValue) {
                    if (isset($fieldMapping[$fieldName])) {
                        $setter = $fieldMapping[$fieldName];
                        if ($setter === 'setExpirationDate') {
                            $fieldValue = new DateTime($fieldValue);
                        }
                        if ($setter === 'setPlainPassword') {
                            $passwordSet = true;
                        }
                        if ($setter === 'setStatus') {
                            if (isset($statusList[$fieldValue])) {
                                $fieldValue = $statusList[$fieldValue];
                                $user->setRoleFromStatus($fieldValue);
                            } else {
                                die("Status value '$fieldValue' not found in status list\n");
                            }
                        }
                        if (method_exists($user, $setter)) {
                            $user->$setter($fieldValue);
                        } else {
                            die("Setter method '$setter' not found in User entity\n");
                        }
                    }
                }

                if (!$passwordSet && $newUser) {
                    $user->setPlainPassword(api_generate_password());
                }

                if (!$user->isActive() && $reenableUsersFoundInCSV) {
                    $user->setActive(true);
                }

                $user->setCreator($creator);
                $userRepository->updateUser($user, $newUser);

                foreach ($CSVuser as $fieldName => $fieldValue) {
                    if (strpos($fieldName, EXTRA_KEY) === 0) {
                        $extraFieldName = substr($fieldName, strlen(EXTRA_KEY));
                        $extraField = $entityManager->getRepository(ExtraField::class)->findOneBy(['variable' => $extraFieldName]);
                        if ($extraField) {
                            $extraFieldValue = $entityManager->getRepository(ExtraFieldValues::class)->findOneBy(['field' => $extraField, 'itemId' => $user->getId()]);
                            if (!$extraFieldValue) {
                                $extraFieldValue = new ExtraFieldValues();
                                $extraFieldValue->setField($extraField);
                                $extraFieldValue->setItemId($user->getId());
                            }
                            $extraFieldValue->setFieldValue($fieldValue);
                            $entityManager->persist($extraFieldValue);
                        } else {
                            die("Extra field '$extraFieldName' not found in database\n");
                        }
                    }
                }
            }

            $urlUsersIdList[] = $user->getId();
            $allCSVUsers[$username] = $user;

        } catch (Exception $e) {
            echo "Error processing user '{$username}': " . $e->getMessage() . "\n";
            error_log("Error processing user '{$username}': " . $e->getMessage());
            echo "Trace: " . $e->getTraceAsString() . "\n";
            continue;
        }
    }
    try {
        $entityManager->flush();
    } catch (OptimisticLockException $e) {
        echo "Error processing users for URL '{$accessUrlId}': " . $e->getMessage() . "\n";
        error_log("Error processing users for URL '{$accessUrlId}': " . $e->getMessage());
        echo "Trace: " . $e->getTraceAsString() . "\n";
        continue;
    }
    if ($debug) {
        echo 'Sent users ' . print_r($urlUsersIdList,1) . ' to DB for URL ' . $accessUrlId . "\n";
    }
    $accessUrlList = []; 
    $accessUrlList[] = $accessUrlId; 
    UrlManager::add_users_to_urls($urlUsersIdList, $accessUrlList);

}

// Disable or delete user accounts not found in any CSV file depending on $deleteUsersNotFoundInCSV
$now = new DateTime();
foreach (array_diff(array_keys($dbUsers), array_keys($allCSVUsers)) as $usernameToDisable) {
    if (isset($usernameListNotToTouchEvenIfNotInCSV) && in_array($usernameToDisable, $usernameListNotToTouchEvenIfNotInCSV)) {
        if ($debug) {
            echo 'User not modified even if not present in CSV: ' . $usernameToDisable . "\n";
        }
    } else {
        $user = $dbUsers[$usernameToDisable];
        if ($deleteUsersNotFoundInCSV) {
            if (!$test) {
                if (!UserManager::delete_user($user->getId())) {
                    if ($debug) {
                        echo 'Unable to delete user ' . $usernameToDisable . "\n";
                    }
                } else {
                    if ($debug) {
                        echo 'Deleted user ' . $usernameToDisable . "\n";
                    }
                }
            } else {
                if ($debug) {
                    echo 'Test mode: User ' . $usernameToDisable . ' would have been deleted\n';
                }
            }
        } else {
            if (!$test) {
                if ($user->isActive()) {
                    $user->setActive(false);
                    $entityManager->persist($user);

                    $trackEDefault = new TrackEDefault();
                    $trackEDefault->setDefaultUserId($firstAdmin->getId());
                    $trackEDefault->setDefaultDate($now);
                    $trackEDefault->setDefaultEventType(LOG_USER_DISABLE);
                    $trackEDefault->setDefaultValueType(LOG_USER_ID);
                    $trackEDefault->setDefaultValue((string) $user->getId());
                    $entityManager->persist($trackEDefault);

                    if ($debug) {
                        echo 'Disabled user ' . $usernameToDisable . "\n";
                    }
                } else {
                    if ($debug) {
                        echo 'User ' . $usernameToDisable . ' is already disabled\n';
                    }
                }
            } else {
                if ($debug) {
                    echo 'Test mode: User ' . $usernameToDisable . ' would have been disabled\n';
                }
            }
        }
    }
}

if (!$test) {
    try {
        $entityManager->flush();
    } catch (OptimisticLockException $e) {
        error_log("Error processing user " . $e->getMessage());
        echo "Trace: " . $e->getTraceAsString() . "\n";
    }
}

// Anonymize user accounts disabled for more than 3 years
if ($anonymizeUserAccountsDisbaledFor3Years) {
    echo "Anonymizing user accounts disabled for more than 3 years\n";

    $longDisabledUserIds = $entityManager->createQueryBuilder()
        ->select('t.defaultValue')
        ->from(TrackEDefault::class, 't')
        ->where('t.defaultEventType = :eventType')
        ->andWhere('t.defaultValueType = :valueType')
        ->andWhere('t.defaultDate < :date')
        ->groupBy('t.defaultValue')
        ->setParameter('eventType', 'user_disable')
        ->setParameter('valueType', 'user_id')
        ->setParameter('date', (new DateTime())->modify('-3 years'))
        ->getQuery()
        ->getSingleColumnResult();

    $anonymizedUserIds = $entityManager->createQueryBuilder()
        ->select('t.defaultValue')
        ->from(TrackEDefault::class, 't')
        ->where('t.defaultEventType = :eventType')
        ->andWhere('t.defaultValueType = :valueType')
        ->distinct()
        ->setParameter('eventType', 'user_anonymized')
        ->setParameter('valueType', 'user_id')
        ->getQuery()
        ->getSingleColumnResult();

    foreach (array_diff($longDisabledUserIds, $anonymizedUserIds) as $userId) {
        $user = $userRepository->find($userId);
        if ($user && !$user->isEnabled()) {
            if (!$test) {
                try {
                    UserManager::anonymize($userId) or die("could not anonymize user $userId\n");
                } catch (Exception $exception) {
                    die($exception->getMessage() . "\n");
                }
                if (isset($extraFieldToEmpty)) {
                    UserManager::update_extra_field_value($userId, $extraFieldToEmpty, '');
                }
            }
            if ($debug) {
                echo "Anonymized user $userId\n";
            }
        }
    }
}
