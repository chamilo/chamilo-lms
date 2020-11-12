<?php

/* For licensing terms, see /license.txt */

/**
 * Move user fields "ruc" and "razon_social" to (social) groups (create groups)
 * and assign the related users to those groups.
 */
exit;
if (PHP_SAPI != 'cli') {
    die('This script can only be launched from the command line');
}
require __DIR__ . '/../../main/inc/global.inc.php';

// We assume all these fields represent the same value, so they are on a 1-1
// relationship.
$referenceFields = array('razon_social', 'ruc');

$tUserField = Database::get_main_table(TABLE_EXTRA_FIELD);
$tUserFieldValue = Database::get_main_table(TABLE_EXTRA_FIELD_VALUES);

$tUser = Database::get_main_table(TABLE_MAIN_USER);

// First get the IDs of the selected fields
$sql = "SELECT id, field_type, variable FROM $tUserField";
$result = Database::query($sql);
$foundFields = array();
$fieldsNames = array();
while ($row = Database::fetch_assoc($result)) {
    if ($row['field_type'] == 1 && in_array($row['variable'], $referenceFields)) {
        $foundFields[$row['variable']] = array('id' => $row['id']);
        $fieldsNames[$row['id']] = $row['variable'];
    }
}

// Second get all the possible values of this field (in user data)
$usersData = array();
foreach ($foundFields as $key => $value) {
    $sql = "SELECT item_id as user_id, value  FROM $tUserFieldValue WHERE field_id = " . $value['id'];
    $result = Database::query($sql);
    while ($row = Database::fetch_assoc($result)) {
        $foundFields[$key]['options'][$row['value']][] = $row['user_id'];
        if (empty($usersData[$row['user_id']])) {
            $usersData[$row['user_id']] = '';
        }
        if ($referenceFields[0] == $key) {
            $usersData[$row['user_id']] = $row['value'] . ' - ' . $usersData[$row['user_id']];
        } else {
            $usersData[$row['user_id']] .= $row['value'] . ' - ';
        }
    }
}
// Clean the user string
$distinctGroups = array();
foreach ($usersData as $userId => $value) {
    $usersData[$userId] = substr($usersData[$userId], 0, -3);
    $distinctGroups[$usersData[$userId]][] = $userId;
}

// Third, we create groups based on the combined strings by user and insert
// users in them (as reader)
/*foreach ($distinctGroups as $name => $usersList) {
    $now = api_get_utc_datetime();
    $sql = "INSERT INTO $tGroup (name, visibility, updated_on, created_on) VALUES ('$name', 1, '$now', '$now')";
    echo $sql . PHP_EOL;
    $result = Database::query($sql);
    $groupId = Database::insert_id();
    echo $groupId . PHP_EOL;
    foreach ($usersList as $user) {
        $sql = "INSERT INTO $tGroupUser (group_id, user_id, relation_type) VALUES ($groupId, $user, 2)";
        echo $sql . PHP_EOL;
        $result = Database::query($sql);
    }
}*/
