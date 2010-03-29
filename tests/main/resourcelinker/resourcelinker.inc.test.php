<?php
require_once api_get_path(SYS_CODE_PATH).'resourcelinker/resourcelinker.inc.php';

class TestResourcelinker extends UnitTestCase {
	
	function testCheckAddedResources() {
		//ob_start();
		$type='';
		$id=1;
		$res = check_added_resources($type, $id);
		$this->assertTrue(is_bool($res)); 
		//ob_end_clean();
		//var_dump($res);
	}
	
	function testDisplayAddedResources() {
		//ob_start();
		$type=1;
		$id=1;
		$style='';
		$res = display_added_resources($type, $id, $style);
		if(!is_null($res))
		$this->assertTrue(is_resource($res)); 
		//ob_end_clean();
		//var_dump($res);
	}
	
	function testDisplayAddedresourceLink() {
		//ob_start();
		$type='';
		$id=1;
		$style='';
		$res = display_addedresource_link($type, $id, $style);
		$this->assertTrue(is_null($res)); 
		//ob_end_clean();
		//var_dump($res);
	}
	
	function testDisplayAddedresourceLinkInLearnpath() {
		//ob_start();
		$type='';
		$id=1;
		$completed='';
		$id_in_path='';
		$builder='';
		$icon='';
		$level = 0;
		$res = display_addedresource_link_in_learnpath($type, $id, $completed, $id_in_path, $builder, $icon, $level);
		$this->assertTrue(is_null($res)); 
		//ob_end_clean();
		//var_dump($res);
	}
	
	function testDisplayResources() {
		//ob_start();
		$showdeleteimg='';
		$res = display_resources($showdeleteimg);
		$this->assertTrue(is_null($res)); 
		//ob_end_clean();
		//var_dump($res);
	}
	
	function testEdit_addedResources() {
		//ob_start();
		$type='';
		$id=1;
		$res = edit_added_resources($type, $id);
		$this->assertTrue(is_null($res)); 
		//ob_end_clean();
		//var_dump($res);
	}
	
	function testFileOrFolder() {
		//ob_start();
		$filefolder='';
		$res = file_or_folder($filefolder);
		$this->assertTrue(is_numeric($res)); 
		//ob_end_clean();
		//var_dump($res);
	}
	
	function testGetAddedresourceLinkInLearnpath() {
		//ob_start();
		$type='';
		$id=1;
		$id_in_path='';
		$res = get_addedresource_link_in_learnpath($type, $id, $id_in_path);
		$this->assertTrue(is_string($res)); 
		//ob_end_clean();
		//var_dump($res);
	}
	
	function testShowAddresourceButton() {
		ob_start();
		$additionalparameters = '';
		$res = show_addresource_button($additionalparameters);
		$this->assertTrue(is_null($res)); 
		ob_end_clean();
		//var_dump($res);
	}
	
	function testShowDocuments() {
		ob_start();
		$folder='';
		$res = show_documents($folder);
		$this->assertTrue(is_null($res)); 
		ob_end_clean();
		//var_dump($res);
	}
	
	function testShowFolderUp() {
		//ob_start();
		$res = show_folder_up();
		$this->assertTrue(is_null($res)); 
		//ob_end_clean();
		//var_dump($res);
	}
	
	function testShoworhideAddresourcelink() {
		ob_start();
		$type='';
		$id=1;
		$res = showorhide_addresourcelink($type, $id);
		$this->assertTrue(is_null($res)); 
		ob_end_clean();
		//var_dump($res);
	}
	
	function testStoreResources() {
		//ob_start();
		$source_type='';
		$source_id='';
		$res = store_resources($source_type, $source_id);
		$this->assertTrue(is_null($res)); 
		//ob_end_clean();
		//var_dump($res);
	}
	
	function testUnsetSessionResources() {
		//ob_start();
		$res = unset_session_resources();
		$this->assertTrue(is_null($res)); 
		//ob_end_clean();
		//var_dump($res);
	}
	
	function testUpdateAddedResources() {
		//ob_start();
		$type='';
		$id=1;
		$res = update_added_resources($type, $id);
		$this->assertTrue(is_null($res)); 
		//ob_end_clean();
		//var_dump($res);
	}
	
	/*function testDeleteOneAddedResource() {
		//ob_start();
		$source_type='';
		$source_id=1;
		$resource_type='';
		$resource_id=1;
		$res =delete_one_added_resource($source_type, $source_id, $resource_type, $resource_id);
		$this->assertTrue(is_null($res)); 
		//ob_end_clean();
		//var_dump($res);
	}*/
	
	function testRemoveResource() {
		//ob_start();
		$resource_key='';
		$res = remove_resource($resource_key);
		$this->assertTrue(is_null($res)); 
		//ob_end_clean();
		//var_dump($res);
	}
	
	function testDeleteAddedResource() {
		//ob_start();
		$type='';
		$id=1;
		$res = delete_added_resource($type, $id);
		$this->assertTrue(is_null($res)); 
		//ob_end_clean();
		//var_dump($res);
	}
	
	function testDeleteAllResources_type() {
		//ob_start();
		$type='';
		$res = delete_all_resources_type($type);
		$this->assertTrue(is_null($res)); 
		//ob_end_clean();
		//var_dump($res);
	}	
}
?>