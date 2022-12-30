<?php
/* For licensing terms, see /license.txt */

/**
 * Class SubLanguageManager.
 *
 * @package chamilo.admin.sublanguage
 */
class SubLanguageManager
{
    /**
     * Constructor.
     */
    public function __construct()
    {
    }

    /**
     * Get all the languages.
     *
     * @param bool $onlyActive Whether to return only active languages (default false)
     *
     * @return array All information about sub-language
     */
    public static function getAllLanguages($onlyActive = false)
    {
        $table = Database::get_main_table(TABLE_MAIN_LANGUAGE);
        $sql = 'SELECT * FROM '.$table;
        if ($onlyActive) {
            $sql .= ' WHERE available = 1';
        }
        $rs = Database::query($sql);
        $all_languages = [];
        while ($row = Database::fetch_array($rs, 'ASSOC')) {
            $all_languages[$row['dokeos_folder']] = $row;
        }

        return $all_languages;
    }

    /**
     * Get all files of lang folder (forum.inc.php,gradebook.inc.php,notebook.inc.php).
     *
     * @param string $path           The lang path folder  (/var/www/my_lms/main/lang/spanish)
     * @param bool   $only_main_name true if we only want the "subname" trad4all instead of trad4all.inc.php
     *
     * @return array All file of lang folder
     */
    public static function get_lang_folder_files_list($path, $only_main_name = false)
    {
        $content_dir = [];
        if (is_dir($path)) {
            if ($dh = opendir($path)) {
                while (($file = readdir($dh)) !== false) {
                    if ($file[0] != '.' && substr($file, -4, strlen($file)) == '.php') {
                        if ($only_main_name) {
                            if ($file != '' && strpos($file, '.inc.php')) {
                                $content_dir[] = substr($file, 0, strpos($file, '.inc.php'));
                            }
                        } else {
                            $content_dir[] = $file;
                        }
                    }
                }
            }
            closedir($dh);
        }

        return $content_dir;
    }

    /**
     * Get all information of sub-language.
     *
     * @param int $parent_id       The parent id(Language father id)
     * @param int $sub_language_id The sub language id
     *
     * @return array All information about sub-language
     */
    public static function get_all_information_of_sub_language($parent_id, $sub_language_id)
    {
        $table = Database::get_main_table(TABLE_MAIN_LANGUAGE);
        $parent_id = intval($parent_id);
        $sub_language_id = intval($sub_language_id);
        $sql = "SELECT * FROM $table
                WHERE
                    parent_id = $parent_id AND
                    id = $sub_language_id";
        $rs = Database::query($sql);
        $all_information = [];
        while ($row = Database::fetch_array($rs, 'ASSOC')) {
            $all_information = $row;
        }

        return $all_information;
    }

    /**
     * Get all information of language.
     *
     * @param int $parent_id The parent id(Language father id)
     *
     * @return array All information about language
     */
    public static function get_all_information_of_language($parent_id)
    {
        $table = Database::get_main_table(TABLE_MAIN_LANGUAGE);
        $sql = 'SELECT * FROM '.$table.' WHERE id = "'.intval($parent_id).'"';
        $rs = Database::query($sql);
        $all_information = [];
        while ($row = Database::fetch_array($rs, 'ASSOC')) {
            $all_information = $row;
        }

        return $all_information;
    }

    /**
     * Get all information of chamilo file.
     *
     * @param string $system_path_file    The chamilo path file (/var/www/chamilo/main/lang/spanish/gradebook.inc.php)
     * @param bool   $get_as_string_index Whether we want to remove the '$' prefix in the results or not
     *
     * @return array Contains all information of chamilo file
     */
    public static function get_all_language_variable_in_file($system_path_file, $get_as_string_index = false)
    {
        $res_list = [];
        if (!is_readable($system_path_file)) {
            return $res_list;
        }
        $info_file = file($system_path_file);
        foreach ($info_file as $line) {
            if (substr($line, 0, 1) != '$') {
                continue;
            }
            list($var, $val) = explode('=', $line, 2);
            $var = trim($var);
            $val = trim($val);
            if ($get_as_string_index) { //remove the prefix $
                $var = substr($var, 1);
            }
            $res_list[$var] = $val;
        }

        return $res_list;
    }

    /**
     * Add file in sub-language directory and add header(tag php).
     *
     * @param string $system_path_file The chamilo path file (/var/www/chamilo/main/lang/spanish/gradebook.inc.php)
     *
     * @return bool
     */
    public static function add_file_in_language_directory($system_path_file)
    {
        $return_value = @file_put_contents($system_path_file, '<?php'.PHP_EOL);

        return $return_value;
    }

    /**
     * Write in file of sub-language.
     *
     * @param string $path_file    The path file (/var/www/chamilo/main/lang/spanish/gradebook.inc.php)
     * @param string $new_term     The new sub-language
     * @param string $new_variable The language variable
     *
     * @return bool True on success, False on error
     */
    public static function write_data_in_file($path_file, $new_term, $new_variable)
    {
        $return_value = false;
        $new_data = $new_variable.'='.$new_term;
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
     * Add directory for sub-language.
     *
     * @param string $sub_language_dir The sub-language directory ( e.g. 'spanish_corporate' )
     *
     * @return bool True on success, false on failure
     */
    public static function add_language_directory($sub_language_dir)
    {
        if (empty($sub_language_dir)) {
            return false;
        }
        $dir = api_get_path(SYS_LANG_PATH).$sub_language_dir;
        if (is_dir($dir)) {
            return true;
        } //even if the dir already exists, we reach the objective of having the directory there

        return @mkdir($dir, api_get_permissions_for_new_directories());
    }

    /**
     * Delete sub-language.
     * In order to avoid deletion of main laguages, we check the existence of a parent.
     *
     * @param int  $parent_id       The parent id
     * @param bool $sub_language_id
     *
     * @return mixed True on success, false on error
     */
    public static function remove_sub_language($parent_id, $sub_language_id)
    {
        if (empty($parent_id) ||
            (intval($parent_id) != $parent_id) ||
            empty($sub_language_id) ||
            (intval($sub_language_id) != $sub_language_id)
        ) {
            return false;
        }
        $table = Database::get_main_table(TABLE_MAIN_LANGUAGE);
        $sql = 'SELECT dokeos_folder FROM '.$table.'
                WHERE parent_id = '.$parent_id.' and id = '.$sub_language_id;
        $res = Database::query($sql);
        if ($res === false or Database::num_rows($res) < 1) {
            return false;
        }
        $row = Database::fetch_assoc($res);
        $res = self::remove_language_directory($row['dokeos_folder']);
        if ($res === false) {
            return false;
        } //can't delete dir, so do not delete language record
        $sql = 'DELETE FROM '.$table.'
                WHERE id= '.intval($sub_language_id);
        $res = Database::query($sql);

        return $res;
    }

    /**
     * Remove directory for sub-language.
     *
     * @param string $sub_language_dir The sub-language path directory ( e.g. 'spanish_corporate'' )
     *
     * @return bool True on success, false on failure
     */
    public static function remove_language_directory($sub_language_dir)
    {
        if (empty($sub_language_dir)) {
            return false;
        }
        $dir = api_get_path(SYS_LANG_PATH).$sub_language_dir;
        if (!is_dir($dir)) {
            return true;
        } //even if the dir does not exist, we reach the objective of not having the directory there
        $content = self::get_lang_folder_files_list($dir);

        if (count($content) > 0) {
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
     * check if language exist by id.
     *
     * @param int $language_id
     *
     * @return bool
     */
    public static function check_if_exist_language_by_id($language_id)
    {
        $table = Database::get_main_table(TABLE_MAIN_LANGUAGE);
        $sql = 'SELECT count(*) as count
                FROM '.$table.'
                WHERE id="'.intval($language_id).'"';
        $rs = Database::query($sql);
        if (Database::num_rows($rs) > 0) {
            if (Database::result($rs, 0, 'count') == 1) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Get name of language by id.
     *
     * @param int $language_id The language id
     *
     * @return string The original name of language
     */
    public static function get_name_of_language_by_id($language_id)
    {
        $table = Database::get_main_table(TABLE_MAIN_LANGUAGE);
        $language_id = intval($language_id);
        $sql = "SELECT original_name
                FROM $table
                WHERE id = $language_id";
        $rs = Database::query($sql);
        if (Database::num_rows($rs) > 0) {
            return Database::result($rs, 0, 'original_name');
        } else {
            return '';
        }
    }

    /**
     * Verified if language is sub-language.
     *
     * @param int $language_id
     *
     * @return bool
     */
    public static function check_if_language_is_sub_language($language_id)
    {
        $table = Database::get_main_table(TABLE_MAIN_LANGUAGE);
        $sql = 'SELECT count(*) AS count FROM '.$table.'
                WHERE id = '.intval($language_id).' AND NOT ISNULL(parent_id)';
        $rs = Database::query($sql);

        if (Database::num_rows($rs) > 0 && Database::result($rs, '0', 'count') == 1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param int $language_id
     *
     * @return bool
     */
    public static function check_if_language_is_used($language_id)
    {
        $language_info = self::get_all_information_of_language($language_id);
        $table = Database::get_main_table(TABLE_MAIN_USER);
        $sql = 'SELECT count(*) AS count FROM '.$table.'
                WHERE language = "'.Database::escape_string($language_info['english_name']).'"';

        $rs = Database::query($sql);

        if (Database::num_rows($rs) > 0 && Database::result($rs, '0', 'count') >= 1) {
            return true;
        } else {
            $table = Database::get_main_table(TABLE_MAIN_COURSE);
            $sql = 'SELECT count(*) AS count FROM '.$table.'
                WHERE course_language = "'.Database::escape_string($language_info['english_name']).'"';

            $rs = Database::query($sql);

            return Database::num_rows($rs) > 0 && Database::result($rs, '0', 'count') >= 1;
        }
    }

    /**
     * Verified if language is father of an sub-language.
     *
     * @param int $language_id The language id
     *
     * @return bool
     */
    public static function check_if_language_is_father($language_id)
    {
        $table = Database::get_main_table(TABLE_MAIN_LANGUAGE);
        $sql = 'SELECT count(*) AS count FROM '.$table.'
                WHERE parent_id= '.intval($language_id).' AND NOT ISNULL(parent_id);';
        $rs = Database::query($sql);

        if (Database::num_rows($rs) > 0 && Database::result($rs, '0', 'count') == 1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Make unavailable the language.
     *
     * @param int $language_id The language id
     *
     * @return bool
     */
    public static function make_unavailable_language($language_id)
    {
        $tbl_admin_languages = Database::get_main_table(TABLE_MAIN_LANGUAGE);
        $sql = "UPDATE $tbl_admin_languages SET available='0'
                WHERE id = ".intval($language_id)."";
        $result = Database::query($sql);

        return $result !== false; //only return false on sql error
    }

    /**
     * Make available the language.
     *
     * @param int $language_id language id
     *
     * @return bool
     */
    public static function make_available_language($language_id)
    {
        $tbl_admin_languages = Database::get_main_table(TABLE_MAIN_LANGUAGE);
        $sql = "UPDATE $tbl_admin_languages SET available='1'
                WHERE id = ".intval($language_id)."";
        $result = Database::query($sql);

        return $result !== false; //only return false on sql error
    }

    /**
     * Set platform language.
     *
     * @param int $language_id The language id
     *
     * @return bool
     */
    public static function set_platform_language($language_id)
    {
        if (empty($language_id) || (intval($language_id) != $language_id)) {
            return false;
        }
        $language_id = intval($language_id);
        $tbl_admin_languages = Database::get_main_table(TABLE_MAIN_LANGUAGE);
        $tbl_settings_current = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
        $sql = "SELECT english_name FROM $tbl_admin_languages
                WHERE id = $language_id";
        $result = Database::query($sql);
        $lang = Database::fetch_array($result);
        $sql_update_2 = "UPDATE $tbl_settings_current SET selected_value = '".$lang['english_name']."'
                         WHERE variable='platformLanguage'";
        $result_2 = Database::query($sql_update_2);
        Event::addEvent(
            LOG_PLATFORM_LANGUAGE_CHANGE,
            LOG_PLATFORM_LANGUAGE,
            $lang['english_name']
        );

        return $result_2 !== false;
    }

    /**
     * Get platform language ID.
     *
     * @return int The platform language ID
     */
    public static function get_platform_language_id()
    {
        $name = api_get_setting('platformLanguage');
        $tbl_admin_languages = Database::get_main_table(TABLE_MAIN_LANGUAGE);
        $sql = "SELECT id FROM $tbl_admin_languages WHERE english_name ='$name'";
        $res = Database::query($sql);
        if (Database::num_rows($res) < 1) {
            return false;
        }
        $row = Database::fetch_array($res);

        return (int) $row['id'];
    }

    /**
     * Get parent language path (or null if no parent).
     *
     * @param string $language_path Children language path
     *
     * @return string Parent language path or null
     */
    public static function get_parent_language_path($language_path)
    {
        $tbl_admin_languages = Database::get_main_table(TABLE_MAIN_LANGUAGE);
        $sql = "SELECT dokeos_folder
                FROM ".$tbl_admin_languages."
                WHERE id = (
                    SELECT parent_id FROM ".$tbl_admin_languages."
                    WHERE dokeos_folder = '".Database::escape_string($language_path)."'
                )
                ";
        $result = Database::query($sql);
        if (Database::num_rows($result) == 0) {
            return null;
        }
        $row = Database::fetch_array($result);

        return $row['dokeos_folder'];
    }

    /**
     * Get language matching isocode.
     *
     * @param string $isocode The language isocode (en, es, fr, zh-TW, etc)
     *
     * @return mixed English name of the matching language, or false if no active language could be found
     */
    public static function getLanguageFromIsocode($isocode)
    {
        $isocode = Database::escape_string($isocode);
        $adminLanguagesTable = Database::get_main_table(TABLE_MAIN_LANGUAGE);
        // select language - if case several languages match, get the last (more recent) one
        $sql = "SELECT english_name
                FROM ".$adminLanguagesTable."
                WHERE
                    isocode ='$isocode' AND
                    available = 1
                ORDER BY id
                DESC LIMIT 1";
        $res = Database::query($sql);
        if (Database::num_rows($res) < 1) {
            return false;
        }
        $row = Database::fetch_assoc($res);

        return $row['english_name'];
    }

    /**
     * Get best language in browser preferences.
     *
     * @param string $preferences The browser-configured language preferences (e.g. "en,es;q=0.7;en-us;q=0.3", etc)
     *
     * @return mixed English name of the matching language, or false if no active language could be found
     */
    public static function getLanguageFromBrowserPreference($preferences)
    {
        if (empty($preferences)) {
            return false;
        }

        $preferencesArray = explode(',', $preferences);

        if (count($preferencesArray) > 0) {
            foreach ($preferencesArray as $pref) {
                $s = strpos($pref, ';');
                if ($s >= 2) {
                    $code = substr($pref, 0, $s);
                } else {
                    $code = $pref;
                }
                $name = self::getLanguageFromIsocode($code);

                if ($name !== false) {
                    return $name;
                }
            }
        }

        return false;
    }
}
