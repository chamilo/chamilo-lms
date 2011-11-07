<?php
//require_once('../../../simpletest/autorun.php');
require_once (api_get_path(LIBRARY_PATH).'urlmanager.lib.php');
require_once(api_get_path(LIBRARY_PATH).'usermanager.lib.php');

class TestUserManager extends UnitTestCase {
        public function __construct() {
            $this->UnitTestCase('User Manager library - main/inc/lib/usermanager.lib.test.php');
        }
    	public function testCreateUser() {
	    	$firstName='test_first';
	    	$lastName='test_last';
	    	$status='1';
	    	$email='test@chamilo.org';
	    	$loginName='testlogin';
	    	$password='testlogin';
    		$official_code='testcode';
    		$language='english';
    		$phone = '';
    		$picture_uri ='';
    		global $_user;
    		ob_start();
    		$res= ob_get_contents();
    		UserManager::create_user($firstName, $lastName, $status, $email, $loginName, $password, $official_code, $language, $phone, $picture_uri);
    		ob_end_clean();
        	$this->assertTrue(is_string($res));
    	}
/*
    function testDeleteUserReturnsTrue() {
        $user = array('username' => 'ywarnier',
        			  'pass' => 'ywarnier',
      				  'firstname' => 'Yannick',
     				  'lastname' => 'Warnier',
      				  'status' => 6,
     				  'auth_source' => 'platform',
     				  'email' => 'yannick.warnier@testdokeos.com',
     				  'creator_id' => 1,
      				  'active' => 1,
        			 );
        $res = UserManager::create_user($user['firstname'],$user['lastname'],$user['status'],$user['email'],$user['username'],$user['pass'],null,null,null,null,$user['auth_source'],null,$user['active']);
        $res = UserManager::delete_user($res);
        $this->assertTrue($res);
    }
*/
    function testDeleteUser() {
    	$user_id='';
    	$res = UserManager::delete_user($user_id);
    	$this->assertTrue(is_bool($res));
    	$_configuration['user_id']='';
    	$res1= UserManager::delete_user($_configuration['user_id']);
    	$this->assertTrue(is_bool($res1));
    	//var_dump($res1);
    }
    /*
    function testUpdateUser() {
		$user = array('username' => 'ywarnier',
	    			  'pass' => 'ywarnier',
	    			  'firstname' => 'Yannick',
	    			  'lastname' => 'Warnier',
	    			  'status' => 6,
	    			  'auth_source' => 'platform',
	   				  'email' => 'yannick.warnier@testdokeos.com',
 			          'creator_id' => 1,
	  				  'active' => 1,
					 );

		$update = array('user_id'=>'12',
					    'firstname'=>'Ricardo',
  						'lastname'=>'Rodriguez',
						'username'=>'richi',
						'email'=>'xxxx@xxxx.com',
						'status'=>6,
						'official_code'=>'2121',
						'phone'=>'',
						'picture_uri'=>'',
						'expiration_date'=>'',
						'active'=>1
					   );

		$res = UserManager::create_user($user['firstname'],$user['lastname'],$user['status'],$user['email'],$user['username'],$user['pass'],null,null,null,null,$user['auth_source'],null,$user['active']);
		$this->assertTrue(is_bool($res));
		$res = UserManager::update_user($update['user_id'],$update['firstname'],$update['lastname'],$update['username'],null,null,$update['email'],$update['status'],$update['official_code'],$update['phone'],
										$update['picture_uri'],$update['expiration_date'],$update['active'],null,null,null,null);
		$this->assertTrue($res);
		$res=UserManager::delete_user($res);
		$this->assertTrue(is_bool($res));
	}*/
	/*
    function testCreateExtraField() {
    	$extra = array('fieldvarname' =>'nuevo campo',
					   'fieldtype' => '2',
					   'fieldtitle' => 'english',
                       'fielddefault' => 'default'
                      );
    	$res = UserManager::create_extra_field($extra['fieldvarname'],$extra['fieldtype'],$extra['fieldtitle'],$extra['fielddefault'],null);
    	$this->assertTrue('/\d/',$res);
    }*/
    function testCreateExtraField() {
    	$fieldvarname='nuevo campo';
    	$fieldtype='1';
    	$fieldtitle='english';
    	$fielddefault='5';
    	$res=UserManager::create_extra_field($fieldvarname, $fieldtype, $fieldtitle, $fielddefault,null);
    	//var_dump($res);
    	$this->assertTrue(is_numeric($fieldtype));
    }

    function testCanDeleteUser() {
    	$res=UserManager::can_delete_user(1);
    	$this->assertTrue(is_bool($res));
    }

    function testAddApiKey() {
      	$res=UserManager::add_api_key();
    	$this->assertTrue(is_bool($res));
    }

    function testBuildProductionListIsFalse() {
    	$res = UserManager::build_production_list(1, false, false);
    	$this->assertFalse($res);
    }

	function testDeleteApiKey() {
		$res=UserManager::delete_api_key(1);
		//var_dump($res);
		$this->assertTrue(is_bool($res));
	}

	function testGetApiKeyIdEmptyServiceReturnsFalse() {
		$api_service = '';
		$res = UserManager::get_api_key_id(1,$api_service);
		$this->assertFalse($res);
	}
	function testGetApiKeyIdNonEmptyServiceWithExtremeUserIdReturnsFalse() {
		$api_service = '';
		$res = UserManager::get_api_key_id(5000000,$api_service);
		$this->assertFalse($res);
	}

	function testGetApiKeys() {
		$res=UserManager::get_api_keys();
		$this->assertTrue(is_bool($res));
	}

	function testGetExtraFieldInformation() {
		$res=UserManager::get_extra_field_information(1);
		$this->assertTrue(is_array($res));
	}

	function testGetExtraFieldInformationByName() {
		$sql="SELECT 1";
		$field_variable=Database::query($sql);
		$res=UserManager::get_extra_field_information_by_name($field_variable);
		//var_dump($res);
		$this->assertTrue(is_bool($res));
	}

	function testGetExtraFieldOptions() {
		$res=UserManager::get_extra_field_options('field name');
		//var_dump($res);
		$this->assertTrue(!(bool)$res);
	}

	function testGetEextraFields() {
		$res=UserManager::get_extra_fields(0, 0, 5, 'ASC', true);
		$this->assertTrue($res);
	}

	function testGetExtraUserData() {
		$res=UserManager::get_extra_user_data(1, null,null,null);
		$this->assertTrue(is_array($res),'Somehow there were no visible fields for user 1');
        //since Chamilo 1.8.8 we always have 3 visible fields for notifications
	}

	function testGetExtraUserDataByField() {
		$res=UserManager::get_extra_user_data_by_field(1, 'field variable', null,null, null);
		$this->assertTrue(is_array($res));
	}

	function testGetExtraUserDataByValue(){
		$res=UserManager::get_extra_user_data_by_value('able', 45, null);
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
		global $_configuration;
		$res=UserManager::get_personal_session_course_list(1);
		//var_dump($res);
		$this->assertTrue(is_array($res));
	}

	function testGetPictureUser() {
		$res=UserManager::get_picture_user(1, 'unknown.jpg', 200, null, null);
		$this->assertFalse(!(bool)$res);
	}

	function testGetTeacherList() {
		ob_start();
		UserManager::get_teacher_list(1212,null);
		$res =ob_get_contents();
		ob_end_clean();
		$this->assertFalse(!(bool)$res);
	}

	function testGetUserIdFromUsername() {
		$res=UserManager::get_user_id_from_username('arthur3');
		$this->assertTrue(!(bool)$res);
	}

	function testGetUserInfo() {
		$res=UserManager::get_user_info('arthur2');
		$this->assertTrue(!(bool)$res);
	}

	function testGetUserInfoById() {
		$res=UserManager::get_user_info_by_id(2);
		if(isset($user_id) && ($user_fields = false)){
			$this->assertTrue(is_array($res));
		}	 else {
				$this->assertTrue($res);
		}
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
	 	//var_dump($res);
	 	$this->assertTrue(is_bool($res));
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

	 function testRemoveUserProductionIsFalseWhenProductionDoesNotExist() {
	 	$user_id=1;
	 	$production='my_files/abc'; //shouldn't exist
	 	$res=UserManager::remove_user_production($user_id,$production);
	 	$this->assertFalse($res);
	 }

	function testResizePictureEmptyPicture() {
		$file='';
		$max_size_for_picture='';
		$res=UserManager::resize_picture($file, $max_size_for_picture);
	 	$this->assertNull($res);
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
/*
//DEPRECATED
	function testSuscribeUsersToSession() {
	  	$id_session=1;
	  	$UserList='';
	  	$res=UserManager::suscribe_users_to_session($id_session,$UserList);
	 	$this->assertTrue(is_null($res));
	}
*/
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
		$fid=5;
		$columns=array();
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
