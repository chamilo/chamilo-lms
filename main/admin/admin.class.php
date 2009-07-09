<?php
/* For licensing terms, see /dokeos_license.txt */
/**
==============================================================================
	@author Isaac flores - Added 9 july of 2009
==============================================================================
*/
/*
==============================================================================
		Class AdminManager
==============================================================================
*/
class AdminManager {

    function __construct() {
    }
    /**
     * Get all data of dokeos folder (forum.inc.php,gradebook.inc.php,notebook.inc.php)
     * @param String The dokeos path folder  (/var/www/my_dokeos/main/lang/spanish)
     * @return Array All file of dokeos folder
     */
    public static function get_all_data_of_dokeos_folder ($dokeos_path_folder) {
	   $content_dir=array();
	    if (is_dir($dokeos_path_folder)) {
		    if ($dh = opendir($dokeos_path_folder)) {
		        while (($file = readdir($dh)) !== false && $file != '.' && $file != '..') {
		           if ($file{0}<>'.') {
		           	  $content_dir[]=$file;
		           }
		           
		        }
		       
		    }
 			closedir($dh);
		    return $content_dir; 
		}	
    }
    /**
     * Get all information of sub-language
     * @param Integer The parent id(Language father id)
     * @return Array All information about sub-language
     */
	public static function get_all_information_of_sub_language ($parent_id) {
		$tbl_admin_languages 	= Database :: get_main_table(TABLE_MAIN_LANGUAGE);	
		$sql='SELECT * FROM '.$tbl_admin_languages.' WHERE parent_id="'.Database::escape_string($parent_id).'"';
		$rs=Database::query($sql,__FILE__,__LINE__);
		$all_information=array();
		while ($row=Database::fetch_array($rs,'ASSOC')) {
			$all_information=$row;
		}
		return $all_information;
	} 
    /**
     * Get all information of language
     * @param Integer The parent id(Language father id)
     * @return Array All information about language
     */	
   public static function get_all_information_of_language ($parent_id) {
 		$tbl_admin_languages 	= Database :: get_main_table(TABLE_MAIN_LANGUAGE);	
		$sql='SELECT * FROM '.$tbl_admin_languages.' WHERE id="'.Database::escape_string($parent_id).'"';
		$rs=Database::query($sql,__FILE__,__LINE__);
		$all_information=array();
		while ($row=Database::fetch_array($rs,'ASSOC')) {
			$all_information=$row;
		}
		return $all_information;  	
   }
     /**
     * Get all information of dokeos file 
     * @param String The dokeos path file (/var/www/my_dokeos/main/lang/spanish/gradebook.inc.php)
     * @return Array Contains all information of dokeos file
     */	  
   public static function get_all_language_variable_in_file ($dokeos_path_file) {
   	$info_file=file($dokeos_path_file);
   	foreach ($info_file as $line) {
        if (substr($line,0,1)!='$') { continue; }
    	list($var,$val) = split('=',$line,2);
        $var = trim($var); $val = trim($val);
        $res_list[$var] = $val;
    }
		return $res_list;
   }
   
     /**
     * Add file in sub-language directory and add header(tag php) 
     * @param String The dokeos path file (/var/www/my_dokeos/main/lang/spanish/gradebook.inc.php)
     * @return void()
     */	   
   public static function add_file_in_language_directory ($dokeos_path_file) {
   		file_put_contents($dokeos_path_file,'<?php'.PHP_EOL);
   }
     /**
     * Write in file of sub-language 
     * @param String The path file (/var/www/my_dokeos/main/lang/spanish/gradebook.inc.php)
     * @param String The new sub-language
     * @param String The language variable
     * @return void()
     */	    
   public static function write_data_in_file ($path_file,$new_sub_language,$variable_sub_language) {
   		$new_data=$variable_sub_language.'='.$new_sub_language;
   		$g_open_file = fopen($path_file, "a");
   		if (fwrite($g_open_file, $new_data.PHP_EOL) === false) {
   			//not allow to write
   		} 
   		fclose($g_open_file);
   }
     /**
     * Add directory for sub-language 
     * @param String The sub-language path directory ( /var/www/my_dokeos/main/lang/spanish_corporate )
     * @return boolean
     */	      
   public static function add_directory_of_sub_language($path_sub_language) {
   		$rs=@mkdir($path_sub_language,0777);
		if ($rs) {
			return true;
		} else {
			return false;
		}
   }
     /**
     * Delete sub-language
     * @param Integer The parent id
     * @return void()
     */   
   public static function removed_sub_language ($parent_id) {
   		$tbl_admin_languages 	= Database :: get_main_table(TABLE_MAIN_LANGUAGE);		
		$sql='DELETE FROM '.$tbl_admin_languages.' WHERE parent_id="'.Database::escape_string($parent_id).'"';
    	$rs=Database::query($sql,__FILE__,__LINE__);
   }
   	/**
	 * check if language exist by id
	 */
	public static function check_if_exist_language_by_id ($language_id) {
		$tbl_admin_languages 	= Database :: get_main_table(TABLE_MAIN_LANGUAGE);	
		$sql='SELECT count(*) as count FROM '.$tbl_admin_languages.' WHERE id="'.Database::escape_string($language_id).'"';
		$rs=Database::query($sql,__FILE__,__LINE__);
		if (Database::num_rows($rs)>0) {
			if (Database::result($rs,0,'count') ==1) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;;
		}	
	}

	/**
	 * Get name of language by id
	 */	
	function get_name_of_language_by_id ($language_id) {
		$tbl_admin_languages 	= Database :: get_main_table(TABLE_MAIN_LANGUAGE);	
		$sql='SELECT original_name FROM '.$tbl_admin_languages.' WHERE id="'.Database::escape_string($language_id).'"';
		$rs=Database::query($sql,__FILE__,__LINE__);
		if (Database::num_rows($rs)>0) {
			return Database::result($rs,0,'original_name');	
		} else {
			return '';
		}
	
	}	
}
?>