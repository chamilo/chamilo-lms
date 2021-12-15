<?php
/* For licensing terms, see /license.txt */

/**
 * Updates an user when official code is not empty
 *
 * If official_code is not null then
 * Update password by username encrypted and username by official_code
 * else
 * nothing to do
 *
 */

exit;

require __DIR__.'/../../main/inc/global.inc.php';

$user = Database::get_main_table(TABLE_MAIN_USER);

$sql = "SELECT id, username, official_code FROM $user WHERE (official_code is not null AND official_code != '') AND user_id = 65";
$rs = Database::query($sql);
if (Database::num_rows($rs) > 0) {
    $userManager = UserManager::getManager();
    while ($row = Database::fetch_assoc($rs)) {
        $user = api_get_user_entity($row['id']);
        $loginName = $row['official_code'];
        $password = $row['username'];
        echo 'Updating official_code "'.$row['official_code'].'": username: '.$row['username'].'<br />';
        $user
            ->setUsername($loginName)
            ->setPlainPassword($password)
        ;
        $userManager->updateUser($user);
    }
}
