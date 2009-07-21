<?php
require_once(api_get_path(LIBRARY_PATH).'system_announcements.lib.php');
require_once(api_get_path(LIBRARY_PATH).'mail.lib.inc.php');


class TestSystemAnnouncementManager extends UnitTestCase {
	
	function testadd_announcement() {
		$title='';
		$content='';
		$date_start='';
		$date_end='';
		ob_start();
		$res=SystemAnnouncementManager::add_announcement($title, $content, $date_start, $date_end, $visible_teacher = 0, $visible_student = 0, $visible_guest = 0, $lang = null, $send_mail=0);
        $this->assertTrue(is_bool($res));
        ob_end_clean();
        //var_dump($res);
	}
	
	function testcount_nb_announcement() {
		$res=SystemAnnouncementManager::count_nb_announcement($start = 0,$user_id = '');
		$this->assertTrue(is_numeric($res));
        //var_dump($res);
	}
	
	function testdelete_announcement() {
		$id='';
		$res=SystemAnnouncementManager::delete_announcement($id);
		$this->assertTrue(is_bool($res));
        //var_dump($res);
	}
	
	function testdisplay_all_announcements() {
		$visible='';
		$res=SystemAnnouncementManager::display_all_announcements($visible, $id = -1,$start = 0,$user_id='');
		$this->assertTrue(is_null($res));
        //var_dump($res);
	}
	
	function testdisplay_announcements() {
		$visible='';
		$res=SystemAnnouncementManager::display_announcements($visible, $id = -1);
		$this->assertTrue(is_null($res));
        //var_dump($res);
	}
	
	function testdisplay_fleche() {
		$user_id='';
		$res=SystemAnnouncementManager::display_fleche($user_id);
		$this->assertTrue(is_null($res));
        //var_dump($res);
	}
	
	function testget_all_announcements() {
		$res=SystemAnnouncementManager::get_all_announcements();
		$this->assertTrue(is_array($res));
        //var_dump($res);
	}
	
	function testget_announcement() {
		$id='';
		$res=SystemAnnouncementManager::get_announcement($id);
		$this->assertTrue(is_bool($res));
        //var_dump($res);
	}
	
	function testsend_system_announcement_by_email() {
		global $_user; 
		global $_setting;
		global $charset; 
		$title='';
		$content='';
		$teacher='';
		$student='';
		$res=SystemAnnouncementManager::send_system_announcement_by_email($title,$content,$teacher, $student);
		$this->assertTrue(is_null($res));
        //var_dump($res);
	}
	
	function testset_visibility() {
		$announcement_id='';
		$user='';
		$visible='';
		$res=SystemAnnouncementManager::set_visibility();
		$this->assertTrue(is_bool($res));
        //var_dump($res);
	}
	
	function testupdate_announcement() {
		$id='';
		$title='';
		$content='';
		$date_start=array();
		$date_end=array();
		ob_start();
		$res=SystemAnnouncementManager::update_announcement($id, $title, $content, $date_start, $date_end, $visible_teacher = 0, $visible_student = 0, $visible_guest = 0,$lang=null, $send_mail=0);
		$this->assertTrue(is_bool($res));
		ob_end_clean();
        //var_dump($res);
	}
		
	
}
?>
