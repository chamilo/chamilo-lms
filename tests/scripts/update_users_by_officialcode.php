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

$dql = "SELECT u FROM ChamiloUserBundle:User u WHERE (u.officialCode is not null AND u.officialCode != '')";
$qb = Database::getManager()->createQuery($dql);
$users = $qb->execute();

if (count($users) > 0) {
    $userManager = UserManager::getManager();
    foreach ($users as $user) {
        $loginName = $user->getOfficialCode();
        $password = $user->getUsername();
        echo 'Updating official_code "'.$user->getOfficialCode().'": username: '.$user->getUsername().'<br />';
        $user
            ->setUsername($loginName)
            ->setPlainPassword($password)
        ;
        $userManager->updateUser($user);
    }
}
