<?php /* For licensing terms, see /license.txt */
/**
 * SubLanguageManager class definition file
 * @package chamilo.admin.sublanguage 
 * @todo clean this lib and move to main/inc/lib
 */

class SubLanguageManager {

    private function __construct() {
        //void
    }
    /**
     * Get all files of lang folder (forum.inc.php,gradebook.inc.php,notebook.inc.php)
     * @param String The lang path folder  (/var/www/my_lms/main/lang/spanish)
     * @param bool true if we only want the "subname" trad4all instead of  trad4all.inc.php
     * @return Array All file of lang folder
     *
     */
    public static function get_lang_folder_files_list ($path,$only_main_name=false) {
       $content_dir=array();
        if (is_dir($path)) {
            if ($dh = opendir($path)) {
                while (($file = readdir($dh)) !== false) {
                   if ($file[0]<>'.' && substr($file,-4,strlen($file))=='.php') {
                           if ($only_main_name) {
                               if ($file!='' && strpos($file, '.inc.php'))
                                   $content_dir[]=substr($file, 0, strpos($file, '.inc.php'));
                           } else {
                                 $content_dir[]=$file;
                           }
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
     * @param Integer The sub language id
     * @return Array All information about sub-language
     */
    public static function get_all_information_of_sub_language ($parent_id,$sub_language_id) {
        $tbl_admin_languages     = Database :: get_main_table(TABLE_MAIN_LANGUAGE);
         $sql='SELECT * FROM '.$tbl_admin_languages.' WHERE parent_id="'.Database::escape_string($parent_id).'" AND id="'.Database::escape_string($sub_language_id).'"';
        $rs=Database::query($sql);
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
         $tbl_admin_languages     = Database :: get_main_table(TABLE_MAIN_LANGUAGE);
        $sql='SELECT * FROM '.$tbl_admin_languages.' WHERE id = "'.intval($parent_id).'"';
        $rs=Database::query($sql);
        $all_information=array();
        while ($row=Database::fetch_array($rs, 'ASSOC')) {
            $all_information=$row;
        }
        return $all_information;
   }
     /**
     * Get all information of chamilo file
     * @param String The chamilo path file (/var/www/chamilo/main/lang/spanish/gradebook.inc.php)
     * @patam Bool Whether we want to remove the '$' prefix in the results or not
     * @return Array Contains all information of chamilo file
     */
   public static function get_all_language_variable_in_file($system_path_file, $get_as_string_index=false) {
           $res_list = array();
           if (!is_readable($system_path_file)) {
               return $res_list;
           }
           $info_file=file($system_path_file);
           foreach ($info_file as $line) {
            if (substr($line,0,1)!='$') { continue; }
            list($var,$val) = split('=',$line,2);
            $var = trim($var); $val = trim($val);
            if ($get_as_string_index) { //remove the prefix $
                $var = substr($var,1);
            }
            $res_list[$var] = $val;
        }
        return $res_list;
   }

     /**
     * Add file in sub-language directory and add header(tag php)
     * @param String The chamilo path file (/var/www/chamilo/main/lang/spanish/gradebook.inc.php)
     * @return bool
     */
    public static function add_file_in_language_directory($system_path_file) {
        $return_value = false;        
        $return_value  = @file_put_contents($system_path_file,'<?php'.PHP_EOL);
        return $return_value;
    }
     /**
     * Write in file of sub-language
     * @param String The path file (/var/www/chamilo/main/lang/spanish/gradebook.inc.php)
     * @param String The new sub-language
     * @param String The language variable
     * @return void()
     */
    public static function write_data_in_file($path_file, $new_term, $new_variable) {
        $return_value = false;
           $new_data=$new_variable.'='.$new_term;
           $resource = @fopen($path_file, "a");
           if (file_exists($path_file) && $resource) {
               if (fwrite($resource, $new_data.PHP_EOL) === false) {
                   //not allow to write
                   $return_value = false;
               } else {
                   $return_value = true;    
               }               
               fclose($resource);
           }
           return $return_value;
   }
     /**
     * Add directory for sub-language
     * @param String The sub-language directory ( e.g. 'spanish_corporate' )
     * @return boolean  True on success, false on failure
     */
   public static function add_language_directory($sub_language_dir) {
        if (empty($sub_language_dir)) { return false; }
        $dir = api_get_path(SYS_LANG_PATH).$sub_language_dir;
        if (is_dir($dir)) { return true; } //even if the dir already exists, we reach the objective of having the directory there
           return @mkdir($dir, api_get_permissions_for_new_directories());
   }
     /**
     * Delete sub-language.
     * In order to avoid deletion of main laguages, we check the existence of a parent
     * @param Integer The parent id
     * @return bool    True on success, false on error
     */
    public static function remove_sub_language ($parent_id,$sub_language_id) {
        if (empty($parent_id) or (intval($parent_id)!=$parent_id) or empty($sub_language_id) or (intval($sub_language_id) != $sub_language_id)) { return false; }
        $tbl_admin_languages = Database :: get_main_table(TABLE_MAIN_LANGUAGE);
        $sql = 'SELECT dokeos_folder FROM '.$tbl_admin_languages.' WHERE parent_id = '.$parent_id.' and id = '.$sub_language_id;
        $res = Database::query($sql);
        if ($res === false or Database::num_rows($res)<1) { return false; }
        $row = Database::fetch_assoc($res);
        $res = SubLanguageManager::remove_language_directory($row['dokeos_folder']);
        if ($res === false) { return false; } //can't delete dir, so do not delete language record
        $sql = 'DELETE FROM '.$tbl_admin_languages.' WHERE id="'.Database::escape_string($sub_language_id).'" ';
        $res = Database::query($sql);
        return $res;
   }

    /**
     * Remove directory for sub-language
     * @param String The sub-language path directory ( e.g. 'spanish_corporate'' )
     * @return boolean  True on success, false on failure
     */
    public static function remove_language_directory($sub_language_dir) {
        if (empty($sub_language_dir)) { return false; }
        $dir = api_get_path(SYS_LANG_PATH).$sub_language_dir;
        if (!is_dir($dir)) { return true; } //even if the dir does not exist, we reach the objective of not having the directory there
        $content = SubLanguageManager::get_lang_folder_files_list($dir);
    
        if (count($content)>0) {
            foreach ($content as $value_content) {
                $path_file = $dir.'/'.$value_content;
                unlink($path_file);
            }
            return @rmdir($dir);
        } else {
            return @rmdir($dir);
        }
    }

       /**
     * check if language exist by id
     * @param Integer The language id
     * @return Boolean
     */
    public static function check_if_exist_language_by_id ($language_id) {
        $tbl_admin_languages     = Database :: get_main_table(TABLE_MAIN_LANGUAGE);
        $sql='SELECT count(*) as count FROM '.$tbl_admin_languages.' WHERE id="'.intval($language_id).'"';
        $rs=Database::query($sql);
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
     * @param Integer The language id
     * @return String The original name of language
     */
    public static function get_name_of_language_by_id ($language_id) {
        $tbl_admin_languages     = Database :: get_main_table(TABLE_MAIN_LANGUAGE);
        $sql='SELECT original_name FROM '.$tbl_admin_languages.' WHERE id="'.Database::escape_string($language_id).'"';
        $rs=Database::query($sql);
        if (Database::num_rows($rs)>0) {
            return Database::result($rs,0,'original_name');
        } else {
            return '';
        }

    }
    /**
     * Verified if language is sub-language
     * @param Integer The language id
     * @return Boolean
     */
    public static function check_if_language_is_sub_language ($language_id) {
    $tbl_admin_languages     = Database :: get_main_table(TABLE_MAIN_LANGUAGE);
    $sql='SELECT count(*) AS count FROM '.$tbl_admin_languages.' WHERE id="'.Database::escape_string($language_id).'" AND NOT ISNULL(parent_id)';
    $rs=Database::query($sql);

    if (Database::num_rows($rs)>0 && Database::result($rs,'0','count')==1) {
        return true;
        } else {
        return false;
     }
    }
    /**
     * Verified if language is father of an sub-language
     * @param Integer The language id
     * @return Boolean
     */
    public static function check_if_language_is_father ($language_id) {
        $tbl_admin_languages     = Database :: get_main_table(TABLE_MAIN_LANGUAGE);
        $sql='SELECT count(*) AS count FROM '.$tbl_admin_languages.' WHERE parent_id="'.Database::escape_string($language_id).'" AND NOT ISNULL(parent_id);';
        $rs=Database::query($sql);

        if (Database::num_rows($rs)>0 && Database::result($rs,'0','count')==1) {
            return true;
        } else {
            return false;
        }
    }
    /**
     * Make unavailable the language
     * @param Integer The language id
     * @return void()
     */
    public static function make_unavailable_language ($language_id) {
        $tbl_admin_languages= Database :: get_main_table(TABLE_MAIN_LANGUAGE);
        $sql_make_unavailable = "UPDATE $tbl_admin_languages SET available='0' WHERE id='".Database::escape_string($language_id)."'";
        $result = Database::query($sql_make_unavailable);
        return $result !== false; //only return false on sql error
    }
    /**
     * Make available the language
     * @param Integer The language id
     * @return void
     */
    public static function make_available_language ($language_id) {
         $tbl_admin_languages= Database :: get_main_table(TABLE_MAIN_LANGUAGE);
         $sql_make_available = "UPDATE $tbl_admin_languages SET available='1' WHERE id='".Database::escape_string($language_id)."'";
        $result = Database::query($sql_make_available);
        return $result !== false; //only return false on sql error
    }
    /**
     * Set platform language
     * @param Integer The language id
     * @return void()
     */
    public static function set_platform_language ($language_id) {
        if (empty($language_id) or (intval($language_id)!=$language_id)) { return false; }
        $tbl_admin_languages = Database :: get_main_table(TABLE_MAIN_LANGUAGE);
        $tbl_settings_current     = Database :: get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
        $sql_update = "SELECT english_name FROM ". $tbl_admin_languages." WHERE id='".Database::escape_string($language_id)."'";
        $result = Database::query($sql_update);
        $lang=Database::fetch_array($result);
        $sql_update_2 = "UPDATE ".$tbl_settings_current." SET selected_value='".$lang['english_name']."' WHERE variable='platformLanguage'";
        $result_2 = Database::query($sql_update_2);
        return $result_2 !== false;
    }
    /**
     * Get platform language ID
     * @return     int     The platform language ID
     */
    public static function get_platform_language_id () {
        $name = api_get_setting('platformLanguage');
        $tbl_admin_languages = Database :: get_main_table(TABLE_MAIN_LANGUAGE);
        $sql = "SELECT id FROM ". $tbl_admin_languages." WHERE english_name ='$name'";
        $res = Database::query($sql);
        if (Database::num_rows($res)<1) { return false;}
        $row = Database::fetch_array($res);
        return $row['id'];
    }
    /*
     * Get parent language path (or null if no parent)
     * @param    string  Children language path
     * @return   string  Parent language path or null
     */
    public static function get_parent_language_path ($language_path) {
        $tbl_admin_languages = Database :: get_main_table(TABLE_MAIN_LANGUAGE);
        $tbl_settings_current   = Database :: get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
        $sql_update = "SELECT dokeos_folder FROM ". $tbl_admin_languages." WHERE id=(SELECT parent_id FROM ". $tbl_admin_languages." WHERE dokeos_folder = '".Database::escape_string($language_path)."')";
        $result = Database::query($sql_update);
        if (Database::num_rows($result) == 0) {
            return null;
        }
        $row = Database::fetch_array($result);
        return $row['dokeos_folder'];
    }
}
?>
