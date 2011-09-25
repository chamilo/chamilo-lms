<?php

require_once(api_get_path(SYS_CODE_PATH).'newscorm/scormItem.class.php');
require_once(api_get_path(SYS_CODE_PATH).'newscorm/learnpathItem.class.php');

class TestScormItem extends UnitTestCase {

	public function testGetFlatList() {
		//ob_start();
		$obj = new scormItem($type='manifest',&$element); 
		$res = $obj->get_flat_list(&$list,&$abs_order,$rel_order=1,$level=0);
		$this->assertTrue(is_null($res)); 
		//ob_end_clean();
		//var_dump($res);
	}
/*
	public function testSave() {
		//ob_start();
		$obj = new scormItem($type='manifest',&$element); 
		$res = $obj->save($from_outside=true,$prereqs_complete=false);
		$this->assertTrue(is_null($res)); 
		//ob_end_clean();
		//var_dump($res);
	}*/
}
?>