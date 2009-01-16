<?php //$id$
/**
 * This script contains a data filling procedure for users
 * @author Yannick Warnier <yannick.warnier@dokeos.com>
 * 
 */
/**
 * Initialisation section
 */
require_once '../../main/inc/global.inc.php';
require_once '../../main/inc/lib/usermanager.lib.php';
/**
 * Loads the data and injects it into the Dokeos database, using the Dokeos
 * internal functions.
 * @return  array  List of user IDs for the users that have just been inserted 
 */
function fill_users() {
    $users = array(); //declare only to avoid parsing notice
    require_once 'data_users.php'; //fill the $users array
    $output = array();
    foreach ($users as $i => $user) {
        //first check that the first item doesn't exist already
        echo $user['firstname'];
    	$output[] = UserManager::create_user($user['firstname'],$user['lastname'],$user['status'],$user['email'],$user['username'],$user['pass'],null,null,null,null,$user['auth_source'],null,$user['active']);
    }
    return $output;
}