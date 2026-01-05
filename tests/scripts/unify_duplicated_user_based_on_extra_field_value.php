<?php

/* For licensing terms, see /license.txt */

/**
 * This script takes a user extra field variabler and looks for users duplicated
 * based on this extra field values and unify them on the most recent account
 * or the first teacher account if one has a teacher status/role
 */
exit;
$now = date('Y-m-d H:i:s');
echo $now . " Starting " . __FILE__ . PHP_EOL;
require __DIR__.'/../../main/inc/global.inc.php';

// Filter to only manage those users based on the duplicate field's value:
//$filteredUsers = ['XYZ1','XYZ2','XYZ3']; // set the list of values of the duplicated field
if (!empty($filteredUsers)) {
    echo "Filtered users list is defined: ";
    foreach ($filteredUsers as $u) {
        echo $u . ' ';
    }
    echo PHP_EOL;
}
// Filter to only manage users from one sub-URL
//$filteredUrls = [4];
if (!empty($filteredUrls)) {
    echo "Filtered URLs list is defined: ";
    foreach ($filteredUrls as $u) {
        echo $u . ' ';
    }
    echo PHP_EOL;
}

// Filter user status to only search for user with those status
// $filteredUserStatusList = "1,5";
if (!empty($filteredUserStatusList)) {
    echo "Filtered user status list is defined: " . $filteredUserStatusList . PHP_EOL;
}

// set the extra field variable to use
$extraFieldVariable = 'dni';

// define if the unified accounts should be deleted or deactivated
$unifyMode = 'delete'; // 'delete' or 'deactivate'

$fieldInfo = MySpace::duGetUserExtraFieldByVariable($extraFieldVariable);
if (empty($fieldInfo)) {
    echo 'ExtraField not found: ' . $extraFieldVariable . PHP_EOL;
    exit;
}

$fieldId = (int) $fieldInfo['id'];
$accessURLs = api_get_access_urls();
foreach ($accessURLs as $accessURL) {
    $urlId = $accessURL['id'];
    if (!empty($filteredUrls) && !in_array($urlId, $filteredUrls)) {
        echo "URL " . $accessURL['url'] . " not in filtered list, skipping..." . PHP_EOL;
        continue;
    }
    echo 'Searching duplicates on ' . $accessURL['url'] . ' with id = ' . $urlId . PHP_EOL;
    $dups = MySpace::duGetDuplicateValues($fieldId, $urlId);
    if (empty($dups)) {
        echo 'No duplicates found' . PHP_EOL;
    } else {
        foreach ($dups as $g) {
            $value = $g['the_value'];
            if (!empty($filteredUsers) && !in_array($value, $filteredUsers)) {
                continue;
            } else {
                echo "Value $value is in the filtered list. Proceeding..." . PHP_EOL;
            }
            echo 'Analysing duplicates of ' . $value . ' ...' . PHP_EOL;
            $users = MySpace::duGetUsersByFieldValue($fieldId, $urlId, $value, $filteredUserStatusList);
            $userIdToUnifyOn = 0;
            $mostRecentRegistrationDate = 0;
            foreach ($users as $u) {
                $uid = (int)$u['user_id'];
                $userInfo = api_get_user_info($uid);
                // unify on the teacher account if exist
                if ($userInfo['status'] == 1) {
                    $userIdToUnifyOn = $uid;
                    break;
                }
                // unify on the most recent account if none is teacher
                if ($userInfo['registration_date'] > $mostRecentRegistrationDate) {
                    $mostRecentRegistrationDate = $userInfo['registration_date'];
                    $userIdToUnifyOn = $uid;
                }
            }
            foreach ($users as $u) {
                $uid = (int)$u['user_id'];
                if ($uid === $userIdToUnifyOn) { continue; }
                $now = date('Y-m-d H:i:s');
                echo $now . ' Unifying user ' . $uid . ' on user ' . $userIdToUnifyOn . PHP_EOL;
                MySpace::duUpdateAllUserRefsList($uid, $userIdToUnifyOn);
                MySpace::duDisableOrDeleteUser($uid, $unifyMode);
                $now = date('Y-m-d H:i:s');
                echo $now . ' User unified' . PHP_EOL;
            }
        }
    }
}
