<?php //$id$
/**
 * This script contains a data filling procedure for users
 * @author Yannick Warnier <yannick.warnier@beeznest.com>
 *
 */
/**
 * Initialisation section
 */
require '../../main/inc/global.inc.php';
/**
 * Executing
 */
fill_many_users(100000);
/**
 * Loads the data and injects it into the Chamilo database, using the Chamilo
 * internal functions.
 * @return  array  List of user IDs for the users that have just been inserted
 */
function fill_many_users($num) {
	$eol = PHP_EOL;
    $users = array(); //declare only to avoid parsing notice
    require_once 'data_users.php'; //fill the $users array
    $i = 1;
    while ($i < $num) {
      $output = array();
      $output[] = array('title'=>'Users Filling Report:');
      foreach ($users as $j => $user) {
        //first check that the first item doesn't exist already
    	$output[$i]['line-init'] = $user['firstname'];
        $res = UserManager::create_user($user['firstname'],$user['lastname'],$user['status'],$user['email'],$user['username'].$i,$user['pass'],null,null,null,null,$user['auth_source'],null,$user['active']);
    	$output[$i]['line-info'] = ($res ? get_lang('Inserted') : get_lang('NotInserted')).' '.$user['username'].$i;
    	$i++;
      }
      print_r($output);
    }
    //return $output;
}
