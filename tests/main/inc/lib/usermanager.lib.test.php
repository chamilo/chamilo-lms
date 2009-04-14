<?php //$id$
//require_once('simpletest/autorun.php');
require_once(api_get_path(LIBRARY_PATH).'usermanager.lib.php');
class TestUserManager extends UnitTestCase 
{
    function testCreateUserReturnsInt()
    {
		$user = array(
	    'username' => 'ywarnier',
	    'pass' => 'ywarnier',
	    'firstname' => 'Yannick',
	    'lastname' => 'Warnier',
	    'status' => 1,
	    'auth_source' => 'platform',
	    'email' => 'yannick.warnier@testdokeos.com',
	    'status' => 1,
	    'creator_id' => 1,
	    'active' => 1,
		);
    	$res = UserManager::create_user($user['firstname'],$user['lastname'],$user['status'],$user['email'],$user['username'],$user['pass'],null,null,null,null,$user['auth_source'],null,$user['active']);
    	$this->assertPattern('/\d/',$res);
    	$res = UserManager::delete_user($res);
    	$this->assertTrue($res);
    }
    function testCreateUserAddCount()
    {
		$user = array(
	    'username' => 'ywarnier',
	    'pass' => 'ywarnier',
	    'firstname' => 'Yannick',
	    'lastname' => 'Warnier',
	    'status' => 1,
	    'auth_source' => 'platform',
	    'email' => 'yannick.warnier@testdokeos.com',
	    'status' => 1,
	    'creator_id' => 1,
	    'active' => 1,
		);
		$precount = UserManager::get_number_of_users();
    	$res = UserManager::create_user($user['firstname'],$user['lastname'],$user['status'],$user['email'],$user['username'],$user['pass'],null,null,null,null,$user['auth_source'],null,$user['active']);
		$postcount = UserManager::get_number_of_users();
		$this->assertFalse(!(bool)$res);
    	$this->assertTrue(($precount+1)===$postcount);
    	$precount = $postcount;
    	$res = UserManager::delete_user($res);
		$postcount = UserManager::get_number_of_users();
		$this->assertFalse(!(bool)$res);
    	$this->assertTrue(($precount-1)===$postcount);
    }
}
?>