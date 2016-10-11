<?php
/**
 * This script contains a data filling procedure for users
 * @author Yannick Warnier <yannick.warnier@beeznest.com>
 *
 */

require '../../main/inc/global.inc.php';

/**
 * Executing
 */
//fill_many_users(100000);

/**
 * Loads the data and injects it into the Chamilo database, using the Chamilo
 * internal functions.
 * @return  array  List of user IDs for the users that have just been inserted
 */
function fill_many_users($num)
{
    $users = array(); //declare only to avoid parsing notice
    require_once 'data_users.php'; //fill the $users array
    $i = 1;
    $output = [];

    $batchSize = 20;
    $em = Database::getManager();

    while ($i < $num) {
        $output[] = array('title' => 'Users Filling Report:');
        foreach ($users as $j => $user) {
            //first check that the first item doesn't exist already
            $output[$i]['line-init'] = $user['firstname'];
            $res = UserManager::create_user(
                $user['firstname'],
                $user['lastname'],
                $user['status'],
                $i.'_'.$user['email'],
                $i.'_'.$user['username'],
                $user['pass'],
                null,
                null,
                null,
                null,
                $user['auth_source'],
                null,
                $user['active']
            );
            $output[$i]['line-info'] = ($res ? get_lang('Inserted') : get_lang('NotInserted')).' '.$user['username'].$i;
            $i++;

            if (($i % $batchSize) === 0) {
                $em->flush();
                $em->clear(); // Detaches all objects from Doctrine!
            }
        }
    }

    return $output;
}
