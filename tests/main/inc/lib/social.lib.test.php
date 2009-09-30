<?php
require_once(api_get_path(LIBRARY_PATH).'social.lib.php');
require_once(api_get_path(LIBRARY_PATH).'usermanager.lib.php');
require_once(api_get_path(LIBRARY_PATH).'main_api.lib.php');
Mock::generate('Database');
Mock::generate('Display');
Mock::generate('UserManager');
Mock::generate('MessageManager');



class TestSocial extends UnitTestCase{
	/*
	public $social;
	public function TestSocial(){

		$this->UnitTestCase('All main social function tests');
	}

	public function setUp(){
		$this->social = new UserFriend();
	}

	public function tearDown(){
		$this->social = null;
	}
	*/
	public function testRegisterFriend(){
		$instans= new MockDatabase();
		$friend_id=4;
		$my_user_id=1;
		$relation_type=2;
		$res= UserFriend::register_friend ($friend_id,$my_user_id,$relation_type);
		$instans->expectCallCount(Database);
		$this->assertTrue(is_object($instans));
		$this->assertTrue(is_null($res));
		$this->assertNull($res);
		//var_dump($res);

	}

	public function testRemovedFriend(){
		$instans= new MockDatabase();
		$instans1= new MockDatabase();
		$friend_id=4;
		$res = UserFriend::removed_friend ($friend_id);
		$instans->expectCallCount(Database,8,'' );
		$instans1->expectMaximumCallCount(Database,10,'');
		$this->assertTrue(is_object($instans));
		$this->assertTrue(is_object($instans1));
		$this->assertTrue(is_null($res));
		//var_dump($res);
		//var_dump($instans);
		//var_dump($instans1);
	}

	public function testShowListTypeFriends(){
		$instans= new MockDatabase();
		$instans1= new MockDatabase();
		$res =UserFriend::show_list_type_friends();
		$instans->expectOnce(Database);
		$instans1->expectMaximumCallCount(Database,10,'');
		$this->assertTrue(is_object($instans) || is_object($instans1));
		$this->assertTrue($res);
		$this->assertTrue(is_array($res));
		$this->assertTrue($instans);
		//var_dump($res);
		//var_dump($instans1);
	}

	public function testGetRelationTypeByName (){
		$relation_type_name=1;
		$res = UserFriend::get_relation_type_by_name ($relation_type_name);
		if(!is_null($res)):
		$this->assertTrue(is_numeric($res));
		$this->assertTrue($res);
		endif;
		//var_dump($res);
	}

	public function testGetRelationBetweenContacts (){
		$instans= new MockDatabase();
		$instans1= new MockDatabase();
		$instans2= new MockDatabase();
		$user_id=1;
		$user_friend=3;
		$res =UserFriend::get_relation_between_contacts ($user_id,$user_friend);
		$instans->expectOnce(Database);
		$instans1->expectMinimumCallCount(Database);
		$instans2->expectMaximumCallCount(Database);
		if(!is_numeric($res)):
		$this->assertTrue(is_string($res));
		$this->assertTrue($res);
		endif;
		$this->assertTrue(is_object($instans));
		$this->assertTrue(is_object($instans1));
		$this->assertTrue(is_object($instans2));
		//var_dump($res);
		//var_dump($instans);
		//var_dump($instans1);
		//var_dump($instans2);
	}

	public function testGetListIdFriendsByUserId (){
		$instans= new MockDatabase();
		$user_id = 1;
		$id_group=3;
		$search_name='group';
		$res = UserFriend::get_list_id_friends_by_user_id ($user_id,$id_group,$search_name);
		$instans->expectOnce(Database);
		$this->assertTrue(is_array($res));
		$this->assertTrue(is_object($instans));
		//var_dump($res);
		//var_dump($instans);
	}

	public function testGetListPathWebByUserId(){
		$instans = new MockUserManager();
		$user_id=1;
		$id_group=null;
		$search_name=null;
		$res = UserFriend::get_list_path_web_by_user_id ($user_id,$id_group,$search_name);
		$instans->expectOnce(UserManager::get_user_picture_path_by_id($values_ids['friend_user_id'],'web',false,true));
		if(!($res===true)):
		$this->assertTrue(is_object($instans));
		$this->assertTrue(is_array($res));
		endif;
		//var_dump($res);
		//var_dump($instans);
	}

	public function testGetListWebPathUserInvitationByUserId(){
		$instans = new MockUserManager();
		$user_id=1;
		$res = UserFriend::get_list_web_path_user_invitation_by_user_id($user_id);
		$instans->expectOnce(UserManager::get_user_picture_path_by_id($values_ids['user_sender_id'],'web',false,true));
		if(is_array($res))
		$this->assertTrue(is_array($res));
		else
		$this->assertTrue(is_null($res));
		$this->assertTrue(is_object($instans));
		//var_dump($res);
		//var_dump($instans);
	}

	public function testSendInvitationFriend(){
		$instans = new MockDatabase();
		$user_id=1;
		$friend_id=3;
		$message_title='hello';
		$message_content='hola';
		$res = UserFriend ::send_invitation_friend ($user_id,$friend_id,$message_title,$message_content);
		$instans->expectMaximumCallCount(Database);
		if(!is_null($res)):
		$this->assertTrue(is_bool($res));
		$this->assertTrue(is_bool($res===true || $res === false));
		endif;
		$this->assertTrue(is_object($instans));
		//var_dump($res);
		//var_dump($instans);
	}

	public function testGetMessageNumberInvitationByUserId(){
		$instans = new MockDatabase();
		$user_receiver_id=3;
		$res = UserFriend::get_message_number_invitation_by_user_id ($user_receiver_id);
		$instans->expectMaximumCallCount(Database);
		if(is_string($res)):
		$this->assertTrue(is_numeric($res));
		endif;
		$this->assertTrue(is_object($instans));

		//var_dump($res);
		//var_dump($instans);
	}

	public function testGetListInvitationOfFriendsByUserId(){
		$instans = new MockDatabase();
		$user_id=1;
		$res = UserFriend::get_list_invitation_of_friends_by_user_id ($user_id);
		$instans->expectCallCount(Database,3,'');
		$this->assertTrue(is_array($res));
		$this->assertTrue(is_object($instans));
		//var_dump($res);
		//var_dump($instans);
	}

	public function testInvitationAccepted(){
		$instans = new MockDatabase();
		$instans1= new MockDatabase();
		$user_send_id=1;
		$user_receiver_id=3;
		$res = UserFriend::invitation_accepted ($user_send_id,$user_receiver_id);
		$instans->expectOnce(Database::get_main_table(TABLE_MAIN_MESSAGE));
		$instans1->expectOnce(Database::query($sql,__FILE__,__LINE__));
		$this->assertTrue(is_null($res));
		$this->assertTrue(is_object($instans));
		$this->assertTrue(is_object($instans1));
		//var_dump($res);
		//var_dump($instans);
		//var_dump($instans1);
	}

	public function testInvitationDenied(){
		$instans = new MockDatabase();
		$user_send_id=1;
		$user_receiver_id=3;
		$res = UserFriend::invitation_denied($user_send_id,$user_receiver_id);
		$instans->expectOnce(Database::query($sql,__FILE__,__LINE__));
		$this->assertTrue(is_object($instans));
		$this->assertTrue(is_null($res));
		//var_dump($res);
		//var_dump($instans);
	}



	public function testQualifyFriend(){
		$instans = new MockDatabase();
		$id_friend_qualify=2;
		$type_qualify=1;
		$res = UserFriend::qualify_friend($id_friend_qualify,$type_qualify);
		$instans->expectOnce(Database::query($sql,__FILE__,__LINE__));
		$this->assertTrue(is_null($res));
		$this->assertTrue(is_object($instans));
		//var_dump($res);
	}
	/*
	public function testSendInvitationFriendUser(){
		$instans = new MockDisplay();
		$instans1 = new MockDatabase();
		//$instans2 = new MockMessageManager();
		$userfriend_id=1;
		$subject_message='';
		$content_message='';
		$res = MessageManager::send_invitation_friend_user($userfriend_id,$subject_message,$content_message);
		$instans  = expectOnce(Display);
		$instans1 = expectOnce(Database);
		//$instans2 = expectOnce(MessageManager);
		$this->assertTrue(is_string($res));
		$this->assertTrue(is_object($instans));
		$this->assertTrue(is_object($instans1));
	//	$this->assertTrue(is_object($instans2));
		var_dump($res);
		var_dump($instans);
		var_dump($instans1);
	}*/
}
?>
