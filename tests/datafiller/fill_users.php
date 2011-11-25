<?php //$id$
/**
 * This script contains a data filling procedure for users
 * @author Yannick Warnier <yannick.warnier@beeznest.com>
 *
 */
/**
 * Initialisation section
 */

/**
 * Loads the data and injects it into the Chamilo database, using the Chamilo
 * internal functions.
 * @return  array  List of user IDs for the users that have just been inserted
 */
function fill_users() {
	$eol = PHP_EOL;
    $users = array(); //declare only to avoid parsing notice
    require_once 'data_users.php'; //fill the $users array
    $output = array();
    $output[] = array('title'=>'Users Filling Report:');
    $i = 1;
    foreach ($users as $i => $user) {
        //first check that the first item doesn't exist already
    	$output[$i]['line-init'] = $user['firstname'];
    	$output[$i]['line-info'] = ($res = UserManager::create_user($user['firstname'],$user['lastname'],$user['status'],$user['email'],$user['username'],$user['pass'],null,null,null,null,$user['auth_source'],null,$user['active'])?$res:get_lang('NotInserted'));
    	$i++;
    }
    return $output;
}
