<?php //$id$
//require_once('../../../simpletest/autorun.php');
require_once (api_get_path(LIBRARY_PATH).'urlmanager.lib.php');
require_once(api_get_path(LIBRARY_PATH).'usermanager.lib.php');

class TestUserManager extends UnitTestCase 

{

    function testCreateUserReturnsInt()
    
    {
		$user = array('username' => 'ywarnier',
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
    	$this->assertFalse($res);
        $res = UserManager::delete_user($res);

    }
    
    function testCreateUserAddCount()

    {
		$user = array('username' => 'ywarnier',
	    			  'pass' => 'ywarnier',
	   				  'firstname' => 'Yannick',
	  				  'lastname' => 'Warnier',
	  				  'status' => 1,
	  				  'auth_source' => 'platform',
					  'email' => 'yannick.warnier@testdokeos.com',
	  				  'creator_id' => 1,
	 				  'active' => 1,
					 );
		$precount = UserManager::get_number_of_users();
    	$res = UserManager::create_user($user['firstname'],$user['lastname'],$user['status'],$user['email'],$user['username'],$user['pass'],null,null,null,null,$user['auth_source'],null,$user['active']);
        $this->assertTrue(!(bool)$res);
		$postcount = UserManager::get_number_of_users();
    	$this->assertFalse(($precount+1)===$postcount);
    	$precount = $postcount;
    	$res = UserManager::delete_user($res);
		$postcount = UserManager::get_number_of_users();
		$this->assertFalse(!(bool)$res);
    	$this->assertFalse(($precount-1)===$postcount);
    }
    
    function testDeleteUserReturnsTrue()
    
    {
        $user = array('username' => 'ywarnier',
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
        $res = UserManager::delete_user($res);
        $this->assertTrue($res);
    
    }
        
    function testCanDeleteUser(){
    	$_POST['user_id']=1;
    	$user_id=intval($_POST['user_id']);
    	$res = UserManager::can_delete_user($user_id);
    	$this->assertFalse($res);
    	
    }
    
    function testDeleteUser(){
    	$user_id='121';
    	$res = UserManager::delete_user($user_id);
    	$this->assertTrue($res);
    	$_configuration['user_id']='321';
    	$res= UserManager::delete_user($_configuration['user_id']);
    	$this->assertTrue($res);
    	
    }
    
    function testUpdateOpenid(){
    	$openid = array ('user_id'=>'12', 'openid'=>'default');
    	$res = UserManager:: update_openid($openid['user_id'],$openid['openid']);
    	$this->assertTrue($res);
    
    }
	
	function testUpdateUser(){
		$user = array('username' => 'ywarnier',
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
		
		$update = array('user_id'=>'12',
					    'firstname'=>'Ricardo', 
  						'lastname'=>'Rodriguez', 
						'username'=>'richi', 
						'email'=>'xxxx@xxxx.com', 
						'status'=>3, 
						'official_code'=>'2121', 
						'phone'=>'xxxxxxx', 
						'picture_uri'=>'image.jpg', 
						'expiration_date'=>'xx-xx-xxxx', 
						'active'=>1
					   );
		
		$res = UserManager::create_user($user['firstname'],$user['lastname'],$user['status'],$user['email'],$user['username'],$user['pass'],null,null,null,null,$user['auth_source'],null,$user['active']);
		$this->assertFalse($res);
		$res = UserManager::update_user($update['user_id'],$update['firstname'],$update['lastname'],$update['username'],null,null,$update['email'],$update['status'],$update['official_code'],$update['phone'],
										$update['picture_uri'],$update['expiration_date'],$update['active'],null,null,null,null);
		$this->assertTrue($res);
		$res=UserManager::delete_user($res);
		$this->assertFalse($res);
	}
	
    function testCreateExtraField(){
    	$extra = array('fieldvarname' =>'lalala',
					   'fieldtype' => '1121',
					   'fieldtitle' => 'english', 
                       'fielddefault' => 'default'
                      );
    	$res = UserManager::create_extra_field($extra['fieldvarname'],$extra['fieldtype'],$extra['fieldtitle'],$extra['fielddefault'],null);
    	$this->assertTrue('/\d/',$res);
    }
    
    function testcan_delete_user(){
    	$user_id='';
    	$res=UserManager::can_delete_user($user_id);	
    	$this->assertTrue($res);
    } 
  
    

}
?>










