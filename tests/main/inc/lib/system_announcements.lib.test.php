<?php
require_once(api_get_path(LIBRARY_PATH).'system_announcements.lib.php');
require_once(api_get_path(LIBRARY_PATH).'mail.lib.inc.php');


class TestSystemAnnouncementManager extends UnitTestCase {

    public function __construct(){
        $this->UnitTestCase('System announcements library - main/inc/lib/system_announcements.lib.test.php');
    }
	function test_add_announcement() {
		$title='Anuncio';
		$content='Contenido del anuncio';
		$date_start='2010-01-02';
		$date_end='2010-01-03';
		$visible_teacher = 0;
		$visible_student = 0;
		$visible_guest = 0;
		$lang = null;
		$send_mail=0;
		$res=SystemAnnouncementManager::add_announcement($title, $content, $date_start, $date_end, $visible_teacher, $visible_student, $visible_guest, $lang, $send_mail);
        $this->assertTrue(is_bool($res));
	}

	function test_count_nb_announcement() {
		$res=SystemAnnouncementManager::count_nb_announcement($start = 0,$user_id = '');
		$this->assertTrue(is_numeric($res));
	}

	function test_delete_announcement() {
		$id='';
		$res=SystemAnnouncementManager::delete_announcement($id);
		$this->assertTrue(is_bool($res));
	}

	function test_display_all_announcements() {
		$visible='';
		$res=SystemAnnouncementManager::display_all_announcements($visible, $id = -1,$start = 0,$user_id='');
		$this->assertTrue(is_null($res));
	}

	function test_display_announcements() {
		$visible='';
		$res=SystemAnnouncementManager::display_announcements($visible, $id = -1);
		$this->assertNull($res);
	}

	function test_display_arrow() {
		$user_id='';
		$res=SystemAnnouncementManager::display_arrow($user_id);
		$this->assertTrue(is_null($res));
        //var_dump($res);
	}

	function test_get_all_announcements() {
		$res=SystemAnnouncementManager::get_all_announcements();
		$this->assertTrue(is_array($res));
        //var_dump($res);
	}

	function test_get_announcement() {
		$id='';
		$res=SystemAnnouncementManager::get_announcement($id);
		$this->assertTrue(is_bool($res));
        //var_dump($res);
	}

	function test_send_system_announcement_by_email_is_true_on_teacher_null() {
		global $_user, $_setting, $charset;
		$title   = $content = $teacher = $student = '';
		$res=SystemAnnouncementManager::send_system_announcement_by_email($title,$content,$teacher, $student);
		$this->assertTrue($res);
	}

    //Test removed until we can make sure the e-mail is sent to a valid e-mail address, otherwise the function returns false because the e-mail sending fails
    /*
	function test_send_system_announcement_by_email_is_true_on_teacher_not_null() {
		global $_user, $_setting, $charset;
		$title   = 'abc';
		$content = 'bcd';
		$student = 0;
		$teacher = 1;
		$res=SystemAnnouncementManager::send_system_announcement_by_email($title,$content,$teacher, $student);
		$this->assertTrue($res);
	}*/

	function test_set_visibility() {
		$announcement_id = $user = $visible='';
		$res=SystemAnnouncementManager::set_visibility($announcement_id, $user, $visible);
		$this->assertTrue(is_bool($res));
        //var_dump($res);
	}

	function test_update_announcement() {
		$id=1;
		$title='Anuncio';
		$content='Contenido';
		$date_start='2010-01-02';
		$date_end='2010-01-03';
		$send_mail=0;
		ob_start();
		$res=SystemAnnouncementManager::update_announcement($id, $title, $content, $date_start, $date_end, $visible_teacher = 0, $visible_student = 0, $visible_guest = 0,$lang=null, $send_mail);
		$this->assertTrue(is_bool($res));
		ob_end_clean();
        //var_dump($res);
	}
}
?>
