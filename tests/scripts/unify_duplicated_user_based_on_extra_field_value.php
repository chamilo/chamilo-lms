<?php

/* For licensing terms, see /license.txt */

/**
 * This script takes a user extra field variabler and looks for users duplicated
 * based on this extra field values and unify them on the most recent account
 */
exit;
require __DIR__.'/../../main/inc/global.inc.php';

// Filter to only manage those users based on their user_id :
//$filteredUsers = [11,12,13]; // set the list of user_id

// set the extra field variable to use 
$extraFieldVariable = 'dni';

// define if the unified accounts should be deleted or deactivated
$unifyMode = 'delete'; // 'delete' or 'deactivate'

$fieldInfo = MySpace::duGetUserExtraFieldByVariable($extraFieldVariable);
if (empty($fieldInfo)) {
    echo 'ExtraField not found : '. $extraFieldVariable . PHP_EOL;
    exit;
}

$fieldId = (int) $fieldInfo['id'];
$accessURLs = api_get_access_urls();
foreach($accessURLs as $accessURL) {
    $urlId = $accessURL['id'];
    echo 'Searching duplicates on ' . $accessURL['url'] . ' with id = ' . $urlId . PHP_EOL; 
    $dups = MySpace::duGetDuplicateValues($fieldId, $urlId);
    if (empty($dups)) {
        echo 'No duplicates found' . PHP_EOL;
    } else {
        foreach ($dups as $g) {
            $value = $g['the_value'];
            echo 'Analysing duplicates of ' . $value . PHP_EOL;
            $users = MySpace::duGetUsersByFieldValue($fieldId, $urlId, $value);
            $userIdToUnifyOn = 0;
            $mostRecentRegistrationDate = 0;
            foreach ($users as $u) {
                $uid = (int)$u['user_id'];
		if (isset($filteredUsers) && !in_array($uid, $filteredUsers) {
                    continue;
                }
                $userInfo = api_get_user_info($uid);
		if ($userInfo['registration_date'] > $mostRecentRegistrationDate) {
                    $mostRecentRegistrationDate = $userInfo['registration_date'];
                    $userIdToUnifyOn = $uid;
                }
            }
            foreach ($users as $u) {
                $uid = (int)$u['user_id'];
		if (isset($filteredUsers) && !in_array($uid, $filteredUsers) {
                    continue;
                }
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
