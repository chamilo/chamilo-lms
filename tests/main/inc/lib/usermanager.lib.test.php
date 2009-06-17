<?php //$id$
//require_once('../../../simpletest/autorun.php');
require_once (api_get_path(LIBRARY_PATH).'urlmanager.lib.php');
require_once(api_get_path(LIBRARY_PATH).'usermanager.lib.php');

class TestUserManager extends UnitTestCase 

{

    function testCreateUserReturnsInt() {
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
    
    function testCreateUserAddCount() {
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
    
    function testDeleteUserReturnsTrue() {
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
    
    function testDeleteUser() {
    	$user_id='121';
    	$res = UserManager::delete_user($user_id);
    	$this->assertTrue($res);
    	$_configuration['user_id']='321';
    	$res= UserManager::delete_user($_configuration['user_id']);
    	$this->assertTrue($res);
    }
    
    function testUpdateUser() {
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
		$this->assertTrue(is_bool($res));
		$res = UserManager::update_user($update['user_id'],$update['firstname'],$update['lastname'],$update['username'],null,null,$update['email'],$update['status'],$update['official_code'],$update['phone'],
										$update['picture_uri'],$update['expiration_date'],$update['active'],null,null,null,null);
		$this->assertTrue($res);
		$res=UserManager::delete_user($res);
		$this->assertFalse($res);
		$this->assertTrue(is_bool($res));
	}
	
    function testCreateExtraField() {
    	$extra = array('fieldvarname' =>'lalala',
					   'fieldtype' => '1121',
					   'fieldtitle' => 'english', 
                       'fielddefault' => 'default'
                      );
    	$res = UserManager::create_extra_field($extra['fieldvarname'],$extra['fieldtype'],$extra['fieldtitle'],$extra['fielddefault'],null);
    	$this->assertTrue('/\d/',$res);
    }
    
    function testCanDeleteUser() {
    	$user_id='121';
    	$res=UserManager::can_delete_user($user_id);	
    	$this->assertTrue($res);
    } 
  
    function testAddApiKey() {
      	$res=UserManager::add_api_key($user_id=null,$api_service='dokeos');
    	$this->assertFalse($res);
    }
    
    function testBuildProductionList() {
    	$user_id= '121';
    	$res=UserManager::build_production_list($user_id, $force = false, $showdelete=false);
    	$this->assertTrue(!(bool)$res);
    }
	
	function testDeleteApiKey() {
		$key_id= '1';
		$res=UserManager::delete_api_key($key_id);
		$this->assertFalse($res);
	}

	function testGetApiKeyId() {
		$user_id= '1';
		$api_service= 'Dokeos';
		$res=UserManager::get_api_key_id($user_id,$api_service);
		$this->assertTrue(is_numeric($res));
	}
	
	function testGetApiKeys() {
		$res=UserManager::get_api_keys($user_id=null,$api_service='dokeos');
		$this->assertTrue(!(bool)$res);
	}
	
	function testGetExtraFieldInformation() {
		$field_id='';
		$res=UserManager::get_extra_field_information($field_id);
		$this->assertTrue(is_array($res));
	}
	
	function testGetExtraFieldInformationByName() {
		$field_variable='';
		$res=UserManager::get_extra_field_information_by_name($field_variable);
		$this->assertTrue(is_array($res));
	}
	
	function testGetExtraFieldOptions() {
		$field_name='field name';
		$res=UserManager::get_extra_field_options($field_name);
		$this->assertTrue(!(bool)$res);
	}
	
	function testGetEextraFields() {
		$res=UserManager::get_extra_fields($from=0, $number_of_items=0, $column=5, $direction='ASC', $all_visibility=true);
		$this->assertTrue($res);
	}
	
	function testGetExtraUserData() {
		$user_id=1;
		$res=UserManager::get_extra_user_data($user_id, null,null,null);
		$this->assertFalse($res);
	}
	
	function testGetExtraUserDataByField() {
		$user_id=1;
		$field_variable='field variable';
		$res=UserManager::get_extra_user_data_by_field($user_id, $field_variable, null,null, null);
		$this->assertTrue(is_array($res));
	}
	
	function testGetExtraUserDataByValue(){
		$field_variable='able';
		$field_value=454;
		$res=UserManager::get_extra_user_data_by_value($field_variable, $field_value, null);
		$this->assertTrue(is_array($res));
	}
	
	function testGetNumberOfExtraFields() {
		$res=UserManager::get_number_of_extra_fields($all_visibility=true);
		$this->assertFalse(!(bool)$res);
		
	}
	
	function testGetNumberOfUsers() {
		$res=UserManager::get_number_of_users();
		$this->assertFalse(!(bool)$res);
	}
	
	function testGetPersonalSessionCourseList() {
		$user_id=1;
		global $_configuration;
		$res=UserManager::get_personal_session_course_list($user_id);
		$this->assertTrue($res);
	}
	
	function testGetPictureUser() {
		$user_id=1;
		$picture_file='unknown.jpg'; 
		$height= 200;
		$res=UserManager::get_picture_user($user_id, $picture_file, $height, null, null);
		$this->assertFalse(!(bool)$res);
	}
	
	function testGetTeacherList() {
		ob_start();
		$course_id='1212';
		UserManager::get_teacher_list($course_id,null);
		$res =ob_get_contents();
		ob_end_clean();
		$this->assertFalse(!(bool)$res);
	}
	
	function testGetUserIdFromUsername() {
		$username='arthur3';
		$res=UserManager::get_user_id_from_username($username);
		$this->assertTrue(!(bool)$res);
	} 
	
	function testGetUserInfo() {
		$username='arthur2';
		$res=UserManager::get_user_info($username);
		$this->assertTrue(!(bool)$res);
	}
	
	function testGetUserInfoById() {
		$user_id=1;
		$res=UserManager::get_user_info_by_id($user_id,null);
		$this->assertFalse(!(bool)$res);
	}
	
	function testGetUserList() {
		$res=UserManager::get_user_list(null,null);
		$this->assertTrue(is_array($res));
	}
	
	function testGetUserListLike() {
		$res=UserManager::get_user_list_like(null,null);
		$this->assertTrue(is_array($res));	
	}
	
	function testGetUserPicturePathById() {
		$id=5;
		$res=UserManager::get_user_picture_path_by_id($id,null,null,null);
		$this->assertTrue(is_array($res));	
	}
	
	function testGetUserProductions() {
		$user_id='1';
		$res=UserManager::get_user_productions($user_id);
		$this->assertFalse(is_array($res===0));
	}
	
	function testGetUserUploadFilesByCourse() {
		$user_id='1';
		$course='MATH';
		$res=UserManager::get_user_upload_files_by_course($user_id, $course,null);
		$this->assertTrue(is_string($res));
	}
	
	 function testIsAdmin() {
	 	$user_id=1;
	 	$res=UserManager::is_admin($user_id);
	 	$this->assertTrue($res);
	 }
	 
	 function testIsExtraFieldAvailable() {
	 	$fieldname='name3';
	 	$res=UserManager::is_extra_field_available($fieldname);
	 	$this->assertFalse(is_string($res));
	 }
	 
	 function testIsUsernameAvailable() {
	 	$username='Arthur';
	 	$res=UserManager::is_username_available($username);
	 	$this->assertFalse(is_string($res));
	 }
	 
	 function testRemoveUserProduction() {
	 	$user_id='121';
	 	$production='field variable';
	 	$res=UserManager::remove_user_production($user_id,$production);
	 	$this->assertTrue(is_null($res));
	 }
	
	function testResizePicture() {
		$file='';
		$max_size_for_picture='';
		$res=UserManager::resize_picture($file, $max_size_for_picture);
	 	$this->assertTrue($res instanceof image);	
	}
	
	function testSaveExtraFieldChanges() {
		$fieldid='1';
		$fieldvarname='name';
		$fieldtype='1';
		$fieldtitle='title';
		$fielddefault='5';
		$res=UserManager::save_extra_field_changes($fieldid, $fieldvarname, $fieldtype, $fieldtitle, $fielddefault, null);
	 	$this->assertFalse(is_a($res,UserManager));	
	 	$this->assertNotNull($res,'');
	 	$this->assertTrue($res);		
	} 
	
	function testSendMessageInOutbox() {
		$email_administrator='arthur@dokeos.com';
		$user_id='1';
		$title='hola í';
		$content='prueba de este í mensaje';
		global $charset;
		$res=UserManager::send_message_in_outbox($email_administrator,$user_id,$title, $content);
	 	$this->assertNull($res);
	 	$this->assertTrue(is_null($res));
		
	}
	
	function testSuscribeUsersToSession() {
	  	$id_session='123';
	  	$UserList='';
	  	$res=UserManager::suscribe_users_to_session($id_session,$UserList,null);
	 	$this->assertTrue(is_null($res));
	}
	
	function testUpdateApiKey() {
		$user_id=121;
		$api_service='string';
		$res=UserManager::update_api_key($user_id,$api_service);
	 	$this->assertTrue(is_numeric($user_id),is_string($api_service));
	 	$this->assertTrue(is_numeric($res));
	 	$this->assertTrue($this->$res===null);
	 	$this->assertNull(null,$res);	
	}
	
	function testUpdateExtraField() {
		$fid='5';
		$columns=null;
		$res=UserManager::update_extra_field($fid,$columns);
	 	$this->assertTrue(is_bool($res));
	}
	
	function testUpdateExtraFieldValue() {
		$user_id='121';
		$fname='name';
		$res=UserManager::update_extra_field_value($user_id,$fname,null);
	 	$this->assertTrue(is_bool($res));	
	}
	
	function testUpdateOpenid() {
		$user_id='121';
		$openid='default';
		$res=UserManager::update_openid($user_id, $openid);
	 	$this->assertTrue(is_bool($res));
	}
}
?>