<?php

/* For licensing terms, see /license.txt */

/**
 * Updates an user extra field
 * a file is needed with this format:
 *
 * user;country
 * julio;France
 *
 * Where:
 * "country "is the name of the user extra field,
 * "France" is the value to save.
 * "julio" is the username of the user to be updated
 *
 */

exit;

require __DIR__.'/../../main/inc/global.inc.php';

// Define origin and destination courses' code
$extraFieldName = 'dni';
$debug = true;
api_protect_admin_script();

$extraField = new ExtraField('user');
$file = 'file.csv';
$users = Import :: csvToArray($file);
foreach ($users as $user) {
    $userInfo = api_get_user_info_from_username($user['user']);
    if (!empty($userInfo)) {
        if ($debug == false) {
            UserManager::update_extra_field_value(
                $userInfo['user_id'],
                $extraFieldName,
                $user[$extraFieldName]
            );
        }
        echo 'Updating extrafield "'.$extraFieldName.'":  '.$user[$extraFieldName].'<br />';
    } else {
        echo 'User does not exists: '.$user['user'].'<br />';
    }
}
