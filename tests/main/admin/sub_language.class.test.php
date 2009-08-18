<?php
require_once(api_get_path(SYS_CODE_PATH).'admin/sub_language.class.php');

class TestSubLanguageManager extends UnitTestCase {
	
	   
    /**
     * Add directory for sub-language 
     * @param String The sub-language path directory ( /var/www/my_dokeos/main/lang/spanish_corporate )
     * @return boolean
     */	 
     public static function testadd_directory_of_sub_language() {
     	$path_sub_language='/var/www/nose';
     	$res=SubLanguageManager::add_directory_of_sub_language($path_sub_language);
     	$this->assertTrue(is_bool($path_sub_language));
     	var_dump($res);
     }
    
    
		
	
}
?>
