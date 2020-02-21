<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';
$file = 'all_session_careers_with_users.csv';
$data = Import::csvToArray($file);

$extraFieldValue = new ExtraFieldValue('session');
$count = 1;
$careerValue = new ExtraFieldValue('career');
foreach ($data as $row) {
    echo "Line $count ".PHP_EOL;
    $count++;
    $users = explode('|', $row['Users']);
    $externalCareerIdList = $row['extra_careerid'];

    if (substr($externalCareerIdList, 0, 1) === '[') {
        $externalCareerIdList = substr($externalCareerIdList, 1, -1);
        $externalCareerIds = preg_split('/,/', $externalCareerIdList);
    } else {
        $externalCareerIds = [$externalCareerIdList];
    }

    $chamiloCareerList = [];
    foreach ($externalCareerIds as $careerId) {
        $careerFound = $careerValue->get_item_id_from_field_variable_and_field_value('external_career_id', $careerId);
        if ($careerFound && isset($careerFound['item_id'])) {
            $chamiloCareerList[] = $careerFound['item_id'];
        }
    }

    if (empty($chamiloCareerList)) {
        echo "No career found ".PHP_EOL;
        continue;
    }

    foreach ($users as $username) {
        $userInfo = api_get_user_info_from_username($username);
        if ($userInfo) {
            $userId = $userInfo['user_id'];
            foreach ($chamiloCareerList as $careerId) {
                if (UserManager::userHasCareer($userId, $careerId)) {
                    echo "User $username (#$userId) has already the chamilo career # $careerId ".PHP_EOL;
                    continue;
                } else {
                    //UserManager::addUserCareer($userId, $careerId);
                    echo "Save career #$careerId to user $username (#$userId)  ".PHP_EOL;
                }
            }
        } else {
            echo "Username not found: $username ".PHP_EOL;
        }
    }
}
