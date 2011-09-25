<?php

require_once api_get_path(SYS_CODE_PATH).'course_info/download.lib.php';


class TestCreateBackupIsAdmin extends UnitTestCase {
    public function __construct() {
        $this->UnitTestCase('Course download library - main/course_info/download.lib.test.php');
    }
    public function Testcreate_backup_is_admin()
    {
        $_GET = array('archive' => 'index.html');

        $resTrue =  create_backup_is_admin(true);
        $this->assertTrue(is_bool($resTrue));
        $resFalse = create_backup_is_admin(false);
        $this->assertFalse($resFalse);
        $this->assertEqual($resTrue , $resFalse);
        //var_dump($resTrue, $resFalse);
    }
}

?>
