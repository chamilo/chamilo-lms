<?php
require_once(api_get_path(LIBRARY_PATH).'urlmanager.lib.php');

class TestUrlManager extends UnitTestCase {

    public function __construct() {
        $this->UnitTestCase('URL manager library - main/inc/lib/urlmanager.lib.test.php');
    }

	function testadd() {
		$url='';
		$description='';
		$active='';
		$res=UrlManager::add($url, $description, $active);
		$this->assertTrue(is_bool($res));
        //var_dump($res);
	}

	function testadd_course_to_url() {
		$course_code=1;
		$res=UrlManager::add_course_to_url($course_code, $url_id=1);
		$this->assertTrue(is_bool($res));
       	//var_dump($res);
	}

	function testadd_courses_to_urls() {
		$url_list='';
		$course_list='';
		$res=UrlManager::add_courses_to_urls($course_list,$url_list);
		$this->assertTrue(is_array($res));
        //var_dump($res);
	}

	function testadd_user_to_url() {
		$user_id=1;
		$url_id=1;
		$res=UrlManager::add_user_to_url($user_id, $url_id);
		$this->assertTrue(is_bool($res));
        //var_dump($res);
	}

	function testadd_users_to_urls()  {
		$user_list='';
		$url_list='';
		$res=UrlManager::add_users_to_urls($user_list, $url_list);
		$this->assertTrue(is_array($res));
        //var_dump($res);
	}

	function testdelete() {
		$id='';
		$res=UrlManager::delete($id);
		$this->assertTrue(is_bool($res));
        //var_dump($res);
	}

	function testdelete_url_rel_course() {
		$course_code='';
		$url_id='';
		$res=UrlManager::delete_url_rel_course($course_code, $url_id);
		$this->assertTrue(is_bool($res));
        //var_dump($res);
	}

	function testdelete_url_rel_user() {
		$user_id='';
		$url_id='';
		$res=UrlManager::delete_url_rel_user($user_id, $url_id);
		$this->assertTrue(is_bool($res));
        //var_dump($res);
	}

	function testget_access_url_from_user() {
		$user_id='';
		$res=UrlManager::get_access_url_from_user($user_id);
		$this->assertTrue(is_array($res));
        //var_dump($res);
	}

	function testget_url_data() {
		$res=UrlManager::get_url_data();
		$this->assertTrue(is_array($res));
        //var_dump($res);
	}

	function testget_url_data_from_id() {
		$url_id=1;
		$resu=UrlManager::get_url_data_from_id($url_id);
		$this->assertTrue(is_array($resu));
		//var_dump($resu);
	}

	function testget_url_id() {
		$url='';
		$resu=UrlManager::get_url_id($url);
		$this->assertTrue(is_string($resu));
		//var_dump($resu);
	}

	function testget_url_rel_course_data() {
		$resu=UrlManager::get_url_rel_course_data($access_url_id='');
		$this->assertTrue(is_array($resu));
		//var_dump($resu);
	}

	function testget_url_rel_session_data() {
		$resu=UrlManager::get_url_rel_session_data($access_url_id='');
		$this->assertTrue(is_array($resu));
		//var_dump($resu);
	}

	function testget_url_rel_user_data() {
		$resu=UrlManager::get_url_rel_user_data($access_url_id='');
		$this->assertTrue(is_array($resu));
		//var_dump($resu);
	}

	function testrelation_url_course_exist() {
		$course_id = 'COURSETEST';
		$url_id=1;
		$resu=UrlManager::relation_url_course_exist($course_id, $url_id);
		if(!is_numeric($resu)){
			$this->assertTrue(is_bool($resu));
		}
		//var_dump($resu);
	}

	function testrelation_url_user_exist() {
		$user_id=1;
		$url_id=1;
		$res=UrlManager::relation_url_user_exist($user_id, $url_id);
		if(!is_numeric($res)){
			$this->assertTrue(is_bool($res));
		}
		//var_dump($res);
	}

	function testset_url_status() {
		$status='';
		$url_id='';
		$res=UrlManager::set_url_status($status, $url_id);
		if(!is_bool($res)) $this->assertTrue(is_null($res));
		//var_dump($res);
	}

	function testudpate() {
		$url_id='';
		$url='';
		$description='';
		$active='';
		$res=UrlManager::udpate($url_id, $url, $description, $active);
		if(!is_null($res))$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	function testupdate_urls_rel_course() {
		$course_list=array();
		$access_url_id=1;
		$res=UrlManager::update_urls_rel_course($course_list,$access_url_id);
		if(!is_null($res)) $this->assertTrue(is_array($res));
		//var_dump($res);
	}

	function testupdate_urls_rel_user() {
		$user_list=array();
		$access_url_id=1;
		$res=UrlManager::update_urls_rel_user($user_list,$access_url_id);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	function testurl_count() {
		$res=UrlManager::url_count();
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}

	function testurl_exist() {
		$url='';
		$res=UrlManager::url_exist($url);
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}

	function testurl_id_exist() {
		$url='';
		$res=UrlManager::url_id_exist($url);
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}
}
?>
