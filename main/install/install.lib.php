<?php
/* For licensing terms, see /license.txt */

/**
 * Chamilo LMS
 * This file contains functions used by the install and upgrade scripts.
 *
 * Ideas for future additions:
 * - a function get_old_version_settings to retrieve the config file settings
 *   of older versions before upgrading.
 */

/*      CONSTANTS */

define('SYSTEM_MAIN_DATABASE_FILE', 'db_main.sql');
define('COUNTRY_DATA_FILENAME',     'country_data.csv');
define('COURSES_HTACCESS_FILENAME', 'htaccess.dist');
define('SYSTEM_CONFIG_FILENAME',    'configuration.dist.php');

/*      COMMON PURPOSE FUNCTIONS    */

/**
 * This function detects whether the system has been already installed.
 * It should be used for prevention from second running the installation
 * script and as a result - destroying a production system.
 * @return bool     The detected result;
 * @author Ivan Tcholakov, 2010;
 */
function is_already_installed_system() {
    global $new_version, $_configuration;

    if (empty($new_version)) {
        return true; // Must be initialized.
    }

    $current_config_file = api_get_path(CONFIGURATION_PATH).'configuration.php';
    if (!file_exists($current_config_file)) {
        return false; // Configuration file does not exist, install the system.
    }
    require $current_config_file;
    
    $current_version = null;
    if (isset($_configuration['dokeos_version'])) {
        $current_version = trim($_configuration['dokeos_version']);
    }
    if (empty($current_version)) {
        $current_version = trim($_configuration['system_version']);
    }

    // If the current version is old, upgrading is assumed, the installer goes ahead.
    return empty($current_version) ? false : version_compare($current_version, $new_version, '>=');
}

/**
 * This function checks if a php extension exists or not and returns an HTML status string.
 *
 * @param   string  Name of the PHP extension to be checked
 * @param   string  Text to show when extension is available (defaults to 'Yes')
 * @param   string  Text to show when extension is available (defaults to 'No')
 * @param   boolean Whether this extension is optional (in this case show unavailable text in orange rather than red)
 * @return  string  HTML string reporting the status of this extension. Language-aware.
 * @author  Christophe Gesch??
 * @author  Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @author  Yannick Warnier <yannick.warnier@dokeos.com>
 * @version Dokeos 1.8.1, May 2007
 */
function check_extension($extension_name, $return_success = 'Yes', $return_failure = 'No', $optional = false) {
    if (extension_loaded($extension_name)) {
        return Display::label($return_success, 'success');
    } else {
        if ($optional) {
            return Display::label($return_failure, 'warning');
            //return '<strong><font color="#ff9900">'.$return_failure.'</font></strong>';
        } else {
            return Display::label($return_failure, 'important');
            //return '<strong><font color="red">'.$return_failure.'</font></strong>';
        }
    }
}

/**
 * This function checks whether a php setting matches the recommended value
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version Dokeos 1.8, august 2006
 */
function check_php_setting($php_setting, $recommended_value, $return_success = false, $return_failure = false) {
    $current_php_value = get_php_setting($php_setting);
    if ($current_php_value == $recommended_value) {
        return Display::label($current_php_value.' '.$return_success, 'success');
    } else {
        return Display::label($current_php_value.' '.$return_success, 'important');        
    }
}

/**
 * Returns a textual value ('ON' or 'OFF') based on a requester 2-state ini- configuration setting.
 *
 * @param string $val a php ini value
 * @return boolean: ON or OFF
 * @author Joomla <http://www.joomla.org>
 */
function get_php_setting($val) {
    return ini_get($val) == '1' ? 'ON' : 'OFF';
}

/**
 * This function returns a string "true" or "false" according to the passed parameter.
 *
 * @param integer  $var  The variable to present as text
 * @return  string  the string "true" or "false"
 * @author Christophe Gesch??
 */
function true_false($var) {
    return $var ? 'true' : 'false';
}

/**
 * Removes memory and time limits as much as possible.
 */
function remove_memory_and_time_limits() {
    if (function_exists('ini_set')) {
        ini_set('memory_limit', -1);
        ini_set('max_execution_time', 0);
    } else {
        error_log('Update-db script: could not change memory and time limits', 0);
    }
}

/**
 * Detects browser's language.
 * @return string       Returns a language identificator, i.e. 'english', 'spanish', ...
 * @author Ivan Tcholakov, 2010
 */
function detect_browser_language() {
    static $language_index = array(
        'ar' => 'arabic',
        'ast' => 'asturian',
        'bg' => 'bulgarian',
        'bs' => 'bosnian',
        'ca' => 'catalan',
        'zh' => 'simpl_chinese',
        'zh-tw' => 'trad_chinese',
        'cs' => 'czech',
        'da' => 'danish',
        'prs' => 'dari',
        'de' => 'german',
        'el' => 'greek',
        'en' => 'english',
        'es' => 'spanish',
        'eo' => 'esperanto',
        'eu' => 'basque',
        'fa' => 'persian',
        'fr' => 'french',
        'fur' => 'friulian',
        'gl' => 'galician',
        'ka' => 'georgian',
        'hr' => 'croatian',
        'he' => 'hebrew',
        'hi' => 'hindi',
        'id' => 'indonesian',
        'it' => 'italian',
        'ko' => 'korean',
        'lv' => 'latvian',
        'lt' => 'lithuanian',
        'mk' => 'macedonian',
        'hu' => 'hungarian',
        'ms' => 'malay',
        'nl' => 'dutch',
        'ja' => 'japanese',
        'no' => 'norwegian',
        'oc' => 'occitan',
        'ps' => 'pashto',
        'pl' => 'polish',
        'pt' => 'portuguese',
        'pt-br' => 'brazilian',
        'ro' => 'romanian',
        'qu' => 'quechua_cusco',
        'ru' => 'russian',
        'sk' => 'slovak',
        'sl' => 'slovenian',
        'sr' => 'serbian',
        'fi' => 'finnish',
        'sv' => 'swedish',
        'th' => 'thai',
        'tr' => 'turkish',
        'uk' => 'ukrainian',
        'vi' => 'vietnamese',
        'sw' => 'swahili',
        'yo' => 'yoruba'
    );

    $system_available_languages = & get_language_folder_list();

    $accept_languages = strtolower(str_replace('_', '-', $_SERVER['HTTP_ACCEPT_LANGUAGE']));
    foreach ($language_index as $code => $language) {
        if (strpos($accept_languages, $code) === 0) {
            if (!empty($system_available_languages[$language])) {
                return $language;
            }
        }
    }

    $user_agent = strtolower(str_replace('_', '-', $_SERVER['HTTP_USER_AGENT']));
    foreach ($language_index as $code => $language) {
        if (@preg_match("/[\[\( ]{$code}[;,_\-\)]/", $user_agent)) {
            if (!empty($system_available_languages[$language])) {
                return $language;
            }
        }
    }

    return 'english';
}


/*      FILESYSTEM RELATED FUNCTIONS */

/**
 * This function checks if the given folder is writable
 */
function check_writable($folder, $suggestion = false) {
    if (is_writable(api_get_path(SYS_CODE_PATH).$folder)) {
        return Display::label(get_lang('Writable'), 'success');
    } else {
        if ($suggestion) {
            return Display::label(get_lang('NotWritable'), 'info');            
        } else {
            return Display::label(get_lang('NotWritable'), 'important');            
        }
    }
}

/**
 * This function is similar to the core file() function, except that it
 * works with line endings in Windows (which is not the case of file())
 * @param   string  File path
 * @return  array   The lines of the file returned as an array
 */
function file_to_array($filename) {
    if(!is_readable($filename) || is_dir($filename)){
        return array();
    }
    $fp = fopen($filename, 'rb');
    $buffer = fread($fp, filesize($filename));
    fclose($fp);
    return explode('<br />', nl2br($buffer));
}

/**
 * We assume this function is called from install scripts that reside inside the install folder.
 */
function set_file_folder_permissions() {
    @chmod('.', 0755); //set permissions on install dir
    @chmod('..', 0755); //set permissions on parent dir of install dir
    @chmod('country_data.csv.csv', 0755);
}

/**
 * Add's a .htaccess file to the courses directory
 * @param string $url_append The path from your webroot to your chamilo root
 */
function write_courses_htaccess_file($url_append) {
    $content = file_get_contents(dirname(__FILE__).'/'.COURSES_HTACCESS_FILENAME);
    $content = str_replace('{CHAMILO_URL_APPEND_PATH}', $url_append, $content);
    $fp = @ fopen(api_get_path(SYS_PATH).'courses/.htaccess', 'w');
    if ($fp) {
        fwrite($fp, $content);
        return fclose($fp);
    }
    return false;
}

/**
 * Write the main system config file
 * @param string $path Path to the config file
 */
function write_system_config_file($path) {

    global $dbHostForm;
    global $dbUsernameForm;
    global $dbPassForm;
    global $enableTrackingForm;
    global $singleDbForm;
    global $dbPrefixForm;
    global $dbNameForm;
    global $dbStatsForm;
    global $dbScormForm;
    global $dbUserForm;
    global $urlForm;
    global $pathForm;
    global $urlAppendPath;
    global $languageForm;
    global $encryptPassForm;
    global $installType;
    global $updatePath;
    global $session_lifetime;
    global $new_version;
    global $new_version_stable;

    $root_sys = api_add_trailing_slash(str_replace('\\', '/', realpath($pathForm)));
    $content = file_get_contents(dirname(__FILE__).'/'.SYSTEM_CONFIG_FILENAME);

    $config['{DATE_GENERATED}']         = date('r');
    $config['{DATABASE_HOST}']          = $dbHostForm;
    $config['{DATABASE_USER}']          = $dbUsernameForm;
    $config['{DATABASE_PASSWORD}']      = $dbPassForm;
    $config['TRACKING_ENABLED']         = true_false($enableTrackingForm);
    $config['SINGLE_DATABASE']          = true_false($singleDbForm);
    $config['{COURSE_TABLE_PREFIX}']    = ($singleDbForm ? 'crs_' : '');    
    $config['{DATABASE_GLUE}']          = ($singleDbForm ? '_' : '`.`');  
    $config['{DATABASE_PREFIX}']        = '';
    $config['{DATABASE_MAIN}']          = $dbNameForm;
    $config['{DATABASE_STATS}']         = $dbNameForm;
    $config['{DATABASE_SCORM}']         = $dbNameForm;
    $config['{DATABASE_PERSONAL}']      = $dbNameForm;
    $config['{ROOT_WEB}']               = $urlForm;
    $config['{ROOT_SYS}']               = $root_sys;
    $config['{URL_APPEND_PATH}']        = $urlAppendPath;
    $config['{PLATFORM_LANGUAGE}']      = $languageForm;
    $config['{SECURITY_KEY}']           = md5(uniqid(rand().time()));
    $config['{ENCRYPT_PASSWORD}']       = $encryptPassForm;

    $config['SESSION_LIFETIME']         = $session_lifetime;
    $config['{NEW_VERSION}']            = $new_version;
    $config['NEW_VERSION_STABLE']       = true_false($new_version_stable);

    foreach ($config as $key => $value) {
        $content = str_replace($key, $value, $content);
    }

    $fp = @ fopen($path, 'w');

    if (!$fp) {
        echo '<strong><font color="red">Your script doesn\'t have write access to the config directory</font></strong><br />
                        <em>('.str_replace('\\', '/', realpath($path)).')</em><br /><br />
                        You probably do not have write access on Chamilo root directory,
                        i.e. you should <em>CHMOD 777</em> or <em>755</em> or <em>775</em>.<br /><br />
                        Your problems can be related on two possible causes:<br />
                        <ul>
                          <li>Permission problems.<br />Try initially with <em>chmod -R 777</em> and increase restrictions gradually.</li>
                          <li>PHP is running in <a href="http://www.php.net/manual/en/features.safe-mode.php" target="_blank">Safe-Mode</a>. If possible, try to switch it off.</li>
                        </ul>
                        <a href="http://forum.chamilo.org/" target="_blank">Read about this problem in Support Forum</a><br /><br />
                        Please go back to step 5.
                        <p><input type="submit" name="step5" value="&lt; Back" /></p>
                        </td></tr></table></form></body></html>';
        exit;
    }

    fwrite($fp, $content);
    fclose($fp);
}

/**
 * Returns a list of language directories.
 */
function & get_language_folder_list() {
    static $result;
    if (!is_array($result)) {
        $result = array();
        $exceptions = array('.', '..', 'CVS', '.svn');
        $search       = array('_latin',   '_unicode',   '_corporate',   '_org'  , '_KM',   '_');
        $replace_with = array(' (Latin)', ' (unicode)', ' (corporate)', ' (org)', ' (KM)', ' ');
        $dirname = api_get_path(SYS_LANG_PATH);
        $handle = opendir($dirname);
        while ($entries = readdir($handle)) {
            if (in_array($entries, $exceptions)) {
                continue;
            }
            if (is_dir($dirname.$entries)) {
                $result[$entries] = ucwords(str_replace($search, $replace_with, $entries));
            }
        }
        closedir($handle);
        asort($result);
    }
    return $result;
}

/**
 * TODO: my_directory_to_array() - maybe within the main API there is already a suitable function?
 */
function my_directory_to_array($directory) {
    $array_items = array();
    if ($handle = opendir($directory)) {
        while (false !== ($file = readdir($handle))) {
            if ($file != "." && $file != "..") {
                if (is_dir($directory. "/" . $file)) {
                    $array_items = array_merge($array_items, my_directory_to_array($directory. '/' . $file));
                    $file = $directory . "/" . $file;
                    $array_items[] = preg_replace("/\/\//si", '/', $file);
                }
            }
        }
        closedir($handle);
    }
    return $array_items;
}

/**
 * This function returns the value of a parameter from the configuration file
 *
 * WARNING - this function relies heavily on global variables $updateFromConfigFile
 * and $configFile, and also changes these globals. This can be rewritten.
 *
 * @param   string  $param  the parameter of which the value is returned
 * @param   string  If we want to give the path rather than take it from POST
 * @return  string  the value of the parameter
 * @author Olivier Brouckaert
 * @author Reworked by Ivan Tcholakov, 2010
 */
function get_config_param($param, $updatePath = '') {
    global $configFile, $updateFromConfigFile;

    // Look if we already have the queried parameter.
    if (is_array($configFile) && isset($configFile[$param])) {
        return $configFile[$param];
    }
    if (empty($updatePath) && !empty($_POST['updatePath'])) {
        $updatePath = $_POST['updatePath'];
    }
    if (empty($updatePath)) {
        $updatePath = api_get_path(SYS_PATH);
    }
    $updatePath = api_add_trailing_slash(str_replace('\\', '/', realpath($updatePath)));
    $updateFromInstalledVersionFile = '';

    if (empty($updateFromConfigFile)) {
        // If update from previous install was requested,
        // try to recover old config file from dokeos 1.8.x.
        if (file_exists($updatePath.'main/inc/conf/configuration.php')) {
            $updateFromConfigFile = 'main/inc/conf/configuration.php';
        } elseif (file_exists($updatePath.'claroline/inc/conf/claro_main.conf.php')) {
            $updateFromConfigFile = 'claroline/inc/conf/claro_main.conf.php';
        } else {
            // Give up recovering.
            //error_log('Chamilo Notice: Could not find previous config file at '.$updatePath.'main/inc/conf/configuration.php nor at '.$updatePath.'claroline/inc/conf/claro_main.conf.php in get_config_param(). Will start new config (in '.__FILE__.', line '.__LINE__.')', 0);
            return null;
        }
    }

    if (file_exists($updatePath.$updateFromConfigFile) && !is_dir($updatePath.$updateFromConfigFile)) {
     
        // The parameter was not found among the global variables, so look into the old configuration file.

        // Make sure the installedVersion file is read first so it is overwritten
        // by the config file if the config file contains the version (from 1.8.4).
        $config_data_2 = array();
        if (file_exists($updatePath.$updateFromInstalledVersionFile)) {
            $config_data_2 = file_to_array($updatePath.$updateFromInstalledVersionFile);
        }
        $configFile = array();
        $config_data = file_to_array($updatePath.$updateFromConfigFile);
        $config_data = array_merge($config_data, $config_data_2);
        $val = '';

        // Parse the configuration file, statement by statement (line by line, actually).
        foreach ($config_data as $php_statement) {

            if (strpos($php_statement, '=') !== false) {
                // Variable assignment statement have been detected (probably).
                // It is expected to be as follows:
                // $variable = 'some_value'; // A comment that is not mandatory.

                // Split the statement into its left and right sides.
                $php_statement = explode('=', $php_statement);
                $variable = trim($php_statement[0]);
                $value = $php_statement[1];

                if (substr($variable, 0, 1) == '$') {
                    // We have for sure a php variable assignment detected.

                    // On the left side: Retrieve the pure variable's name
                    $variable = trim(str_replace('$', '', $variable));

                    // On the right side: Remove the comment, if it exists.
                    list($value) = explode(' //', $value);
                    // Remove extra whitespace, if any. Remove the trailing semicolon (;).
                    $value = substr(trim($value), 0, -1);
                    // Remove surroundig quotes, restore escaped quotes.
                    $value = str_replace('\"', '"', preg_replace('/^"|"$/', '', $value));
                    $value = str_replace('\'', '"', preg_replace('/^\'|\'$/', '', $value));

                    if (strtolower($value) == 'true') {

                        // A boolean true value have been recognized.
                        $value = 1;

                    } elseif (strtolower($value) == 'false') {

                        // A boolean false value have been recognized.
                        $value = 0;

                    } else {

                        // Probably we have a string value, but also we have to check
                        // possible string concatenations that may include string values
                        // and other configuration variables. I this case we have to
                        // get the calculated result of the concatenation.
                        $implode_string = ' ';
                        if (!strstr($value, '." ".') && strstr($value, '.$')) {
                            // Yes, there is concatenation, insert a special separator string.
                            $value = str_replace('.$', '." ".$', $value);
                            $implode_string = '';
                        }

                        // Split the concatenated values, if they are more than one.
                        $sub_strings = explode('." ".', $value);

                        // Seek for variables and retrieve their values.
                        foreach ($sub_strings as $key => & $sub_string) {
                            if (preg_match('/^\$[a-zA-Z_][a-zA-Z0-9_]*$/', $sub_string)) {
                                // A variable has been detected, read it by recursive call.
                                $sub_string = get_config_param(str_replace('$', '', $sub_string));
                            }
                        }

                        // Concatenate everything into the final, the calculated string value.
                        $value = implode($implode_string, $sub_strings);
                    }

                    // Cache the result value.
                    $configFile[$variable] = $value;

                    $a = explode("'", $variable);
                    $key_tmp = isset($a[1]) ? $a[1] : null;
                    if ($key_tmp == $param) {
                        $val = $value;
                    }
                }
            }
        }
    }

    if($param == 'dbGlu' && empty($val)){
        return '`.`';
    }
    //Special treatment for dokeos_version parameter due to Dokeos 1.8.3 have the dokeos_version in the main/inc/installedVersion.inc.php file
    if ($param == 'dokeos_version') {
        //dokeos_version from configuration.php if empty
        $dokeos_version = $val;

        if (empty($dokeos_version)) {
            //checking the dokeos_version value exists in main/inc/installedVersion.inc.php
            if (file_exists($updatePath.'main/inc/installedVersion.inc.php')) {
                $updateFromInstalledVersionFile = $updatePath.'main/inc/installedVersion.inc.php';
                require ($updateFromInstalledVersionFile); //there are only 2 variables here: $stable & $dokeos_version
                $stable = false;
            }
        }
        return $dokeos_version;
    } else {
        if (file_exists($updatePath.$updateFromConfigFile)) {
            return  $val;
        } else {
            error_log('Config array could not be found in get_config_param()', 0);
            return null;
        }
    }
}

/*      DATABASE RELATED FUNCTIONS */

/**
 * Gets a configuration parameter from the database. Returns returns null on failure.
 * @param   string  DB Host
 * @param   string  DB login
 * @param   string  DB pass
 * @param   string  DB name
 * @param   string  Name of param we want
 * @return  mixed   The parameter value or null if not found
 */
function get_config_param_from_db($host, $login, $pass, $db_name, $param = '') {

    Database::connect(array('server' => $host, 'username' => $login, 'password' => $pass));
    Database::query("set session sql_mode='';"); // Disabling special SQL modes (MySQL 5)
    Database::select_db($db_name);

    if (($res = Database::query("SELECT * FROM settings_current WHERE variable = '$param'")) !== false) {
        if (Database::num_rows($res) > 0) {
            $row = Database::fetch_array($res);
            return $row['selected_value'];
        }
    }
    return null;
}

/**
 * Connects to the database server.
 */
function database_server_connect() {
    global $dbHostForm, $dbUsernameForm, $dbPassForm;
    if (($res = @Database::connect(array('server' => $dbHostForm, 'username' => $dbUsernameForm, 'password' => $dbPassForm))) === false) {
        $no = Database::errno();
        $msg = Database::error();
        echo '<hr />#'.$no.': '.$msg.'<hr />';
        echo get_lang('DBServerDoesntWorkOrLoginPassIsWrong').'.<br /><br />'.
            get_lang('PleaseCheckTheseValues').' :<br /><br />'.
            '<strong>'.get_lang('DBHost').'</strong> : '.$dbHostForm.'<br />'.
            '<strong>'.get_lang('DBLogin').'</strong> : '.$dbUsernameForm.'<br />'.
            '<strong>'.get_lang('DBPassword').'</strong> : '.$dbPassForm.'<br /><br />'.
            get_lang('PleaseGoBackToStep').' '. (defined('SYSTEM_INSTALLATION') ? '3' : '1').'.'.
            '<p><button type="submit" class="back" name="step'. (defined('SYSTEM_INSTALLATION') ? '3' : '1').'" value="&lt; '.get_lang('Back').'">'.get_lang('Back').'</button></p>'.
            '</td></tr></table></form></body></html>';
        exit ();
    }
    @Database::query("set session sql_mode='';"); // Disabling special SQL modes (MySQL 5)
}

/**
 * Database exists for the MYSQL user
 * @param type $database_name
 * @return boolean 
 */
function database_exists($database_name) {    
    if (empty($database_name)) {
        return false;
    }    
    $select_database = @Database::select_db($database_name);    
    $show_database = false;    
    $sql = "SHOW DATABASES LIKE '".addslashes($database_name)."'";    
    $result = @Database::query($sql);
    if (Database::num_rows($result)) {
        $show_database = true;
    }    
    return $select_database || $show_database;
}

/**
 * In step 3. Tests establishing connection to the database server. 
 * If it's a single database environment the function checks if the database exist. 
 * If the database doesn't exist we check the creation permissions. 
 * 
 * @return int      1 when there is no problem;
 *                  0 when a new database is impossible to be created, then the single/multiple database configuration is impossible too
 *                 -1 when there is no connection established.
 */
function test_db_connect($dbHostForm, $dbUsernameForm, $dbPassForm, $singleDbForm, $dbPrefixForm, $dbNameForm) {
    $dbConnect = -1;
    //Checking user credentials
    if (@Database::connect(array('server' => $dbHostForm, 'username' => $dbUsernameForm, 'password' => $dbPassForm)) !== false) {
        $dbConnect = 1; 
    } else {
        $dbConnect = -1;
    }    
    return $dbConnect; //return 1, if no problems, "0" if, in case we can't create a new DB and "-1" if there is no connection.
}

/**
 * Fills the countries table with a list of countries.
 */
function fill_track_countries_table($track_countries_table) {
    $file_path = dirname(__FILE__).'/'.COUNTRY_DATA_FILENAME;
    $countries = file($file_path);
    $add_country_sql = "INSERT INTO $track_countries_table (id, code, country, counter) VALUES ";
    foreach ($countries as $line) {
        $elems = explode(',',$line);
        $add_country_sql .= '('.intval($elems[0]).',\''.Database::escape_string($elems[1]).'\',\''.Database::escape_string($elems[2]).'\','.intval($elems[3]).'),';
    }
    $add_country_sql = substr($add_country_sql,0,-1);
    //$add_country_sql = "LOAD DATA INFILE '".Database::escape_string($file_path)."' INTO TABLE $track_countries_table FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '\'';";
    @ Database::query($add_country_sql);
}

/**
 * Creates the structure of the main database and fills it
 * with data. Placeholder symbols in the main database file
 * have to be replaced by the settings entered by the user during installation.
 *
 * @param array $installation_settings list of settings entered by the user
 * @param string  optional path about the script for database
 * @return void
 */
function load_main_database($installation_settings, $db_script = '') {
    if (!empty($db_script)) {
        if (file_exists($db_script)) {
            $sql_text = file_get_contents($db_script);
        }
    } else {
        $db_script = api_get_path(SYS_CODE_PATH).'install/'.SYSTEM_MAIN_DATABASE_FILE;
        if (file_exists($db_script)) {
            $sql_text = file_get_contents($db_script);
        }
    }

    //replace symbolic parameters with user-specified values
    foreach ($installation_settings as $key => $value) {
        $sql_text = str_replace($key, Database::escape_string($value), $sql_text);
    }

    //split in array of sql strings
    $sql_instructions = array();
    $success = split_sql_file($sql_instructions, $sql_text);

    //execute the sql instructions
    $count = count($sql_instructions);
    for ($i = 0; $i < $count; $i++) {
        $this_sql_query = $sql_instructions[$i]['query'];
        Database::query($this_sql_query);
    }
}

/**
 * Creates the structure of the stats database
 * @param   string  Name of the file containing the SQL script inside the install directory
 */
function load_database_script($db_script) {
    $db_script = api_get_path(SYS_CODE_PATH).'install/'.$db_script;
    if (file_exists($db_script)) {
        $sql_text = file_get_contents($db_script);
    }

    //split in array of sql strings
    $sql_instructions = array();
    $success = split_sql_file($sql_instructions, $sql_text);

    //execute the sql instructions
    $count = count($sql_instructions);
    for ($i = 0; $i < $count; $i++) {
        $this_sql_query = $sql_instructions[$i]['query'];    
        Database::query($this_sql_query);
    }    
}

/**
 * Function copied and adapted from phpMyAdmin 2.6.0 PMA_splitSqlFile (also GNU GPL)
 * Removes comment lines and splits up large sql files into individual queries
 * Last revision: September 23, 2001 - gandon
 * @param   array    the splitted sql commands
 * @param   string   the sql commands
 * @param   integer  the MySQL release number (because certains php3 versions
 *                   can't get the value of a constant from within a function)
 * @return  boolean  always true
 */
function split_sql_file(&$ret, $sql) {
    // do not trim, see bug #1030644
    //$sql          = trim($sql);
    $sql          = rtrim($sql, "\n\r");
    $sql_len      = strlen($sql);
    $char         = '';
    $string_start = '';
    $in_string    = false;
    $nothing      = true;
    $time0        = time();

    for ($i = 0; $i < $sql_len; ++$i) {
        $char = $sql[$i];

        // We are in a string, check for not escaped end of strings except for
        // backquotes that can't be escaped
        if ($in_string) {
            for (;;) {
                $i         = strpos($sql, $string_start, $i);
                // No end of string found -> add the current substring to the
                // returned array
                if (!$i) {
                    $ret[] = $sql;
                    return true;
                }
                // Backquotes or no backslashes before quotes: it's indeed the
                // end of the string -> exit the loop
                elseif ($string_start == '`' || $sql[$i - 1] != '\\') {
                    $string_start      = '';
                    $in_string         = false;
                    break;
                }
                // one or more Backslashes before the presumed end of string...
                else {
                    // ... first checks for escaped backslashes
                    $j                     = 2;
                    $escaped_backslash     = false;
                    while ($i - $j > 0 && $sql[$i - $j] == '\\') {
                        $escaped_backslash = !$escaped_backslash;
                        $j++;
                    }
                    // ... if escaped backslashes: it's really the end of the
                    // string -> exit the loop
                    if ($escaped_backslash) {
                        $string_start  = '';
                        $in_string     = false;
                        break;
                    }
                    // ... else loop
                    else {
                        $i++;
                    }
                } // end if...elseif...else
            } // end for
        } // end if (in string)

        // lets skip comments (/*, -- and #)
        elseif (($char == '-' && $sql_len > $i + 2 && $sql[$i + 1] == '-' && $sql[$i + 2] <= ' ') || $char == '#' || ($char == '/' && $sql_len > $i + 1 && $sql[$i + 1] == '*')) {
            $i = strpos($sql, $char == '/' ? '*/' : "\n", $i);
            // didn't we hit end of string?
            if ($i === false) {
                break;
            }
            if ($char == '/') $i++;
        }

        // We are not in a string, first check for delimiter...
        elseif ($char == ';') {
            // if delimiter found, add the parsed part to the returned array
            $ret[]      = array('query' => substr($sql, 0, $i), 'empty' => $nothing);
            $nothing    = true;
            $sql        = ltrim(substr($sql, min($i + 1, $sql_len)));
            $sql_len    = strlen($sql);
            if ($sql_len) {
                $i      = -1;
            } else {
                // The submited statement(s) end(s) here
                return true;
            }
        } // end elseif (is delimiter)

        // ... then check for start of a string,...
        elseif (($char == '"') || ($char == '\'') || ($char == '`')) {
            $in_string    = true;
            $nothing      = false;
            $string_start = $char;
        } // end elseif (is start of string)

        elseif ($nothing) {
            $nothing = false;
        }

        // loic1: send a fake header each 30 sec. to bypass browser timeout
        $time1     = time();
        if ($time1 >= $time0 + 30) {
            $time0 = $time1;
            header('X-pmaPing: Pong');
        } // end if
    } // end for

    // add any rest to the returned array
    if (!empty($sql) && preg_match('@[^[:space:]]+@', $sql)) {
        $ret[] = array('query' => $sql, 'empty' => $nothing);
    }

    return true;
} // end of the 'split_sql_file()' function

/**
 * Get an SQL file's contents
 *
 * This function bases its parsing on the pre-set format of the specific SQL files in
 * the install/upgrade procedure:
 * Lines starting with "--" are comments (but need to be taken into account as they also hold sections names)
 * Other lines are considered to be one-line-per-query lines (this is checked quickly by this function)
 * @param   string  File to parse (in the current directory)
 * @param   string  Section to return
 * @param   boolean Print (true) or hide (false) error texts when they occur
 */
function get_sql_file_contents($file, $section, $print_errors = true) {
    //check given parameters
    if (empty($file)) {
        $error = "Missing name of file to parse in get_sql_file_contents()";
        if ($print_errors) echo $error;
        return false;
    }
    if (!in_array($section, array('main', 'user', 'stats', 'scorm', 'course'))) {
        $error = "Section '$section' is not authorized in get_sql_file_contents()";
        if ($print_errors) echo $error;
        return false;
    }
    $filepath = getcwd().'/'.$file;
    if (!is_file($filepath) or !is_readable($filepath)) {
        $error = "File $filepath not found or not readable in get_sql_file_contents()";
        if ($print_errors) echo $error;
        return false;
    }
    //read the file in an array
    // Empty lines should not be executed as SQL statements, because errors occur, see Task #2167.
    $file_contents = file($filepath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($file_contents) or count($file_contents) < 1) {
        $error = "File $filepath looks empty in get_sql_file_contents()";
        if ($print_errors) echo $error;
        return false;
    }

    //prepare the resulting array
    $section_contents = array();
    $record = false;
    foreach ($file_contents as $index => $line) {
        if (substr($line, 0, 2) == '--') {
            //This is a comment. Check if section name, otherwise ignore
            $result = array();
            if (preg_match('/^-- xx([A-Z]*)xx/', $line, $result)) { //we got a section name here
                if ($result[1] == strtoupper($section)) {
                    //we have the section we are looking for, start recording
                    $record = true;
                } else {
                    //we have another section's header. If we were recording, stop now and exit loop
                    if ($record) {
                        break;
                    }
                    $record = false;
                }
            }
        } else {
            if ($record) {
                if (!empty($line)) {
                    $section_contents[] = $line;
                }
            }
        }
    }
    //now we have our section's SQL statements group ready, return
    return $section_contents;
}

/**
 * Adds a new document to the database - specific to version 1.8.0
 *
 * @param array $_course
 * @param string $path
 * @param string $filetype
 * @param int $filesize
 * @param string $title
 * @return id if inserted document
 */
function add_document_180($_course, $path, $filetype, $filesize, $title, $comment = null) {
    $table_document = Database::get_course_table(TABLE_DOCUMENT, $_course['dbName']);
    $sql = "INSERT INTO $table_document
    (`path`,`filetype`,`size`,`title`, `comment`)
    VALUES ('$path','$filetype','$filesize','".
    Database::escape_string($title)."', '$comment')";
    if (Database::query($sql)) {
        //display_message("Added to database (id ".Database::insert_id().")!");
        return Database::insert_id();
    } else {
        //display_error("The uploaded file could not be added to the database (".Database::error().")!");
        return false;
    }
}

/*      DISPLAY FUNCTIONS */

/**
 * This function prints class=active_step $current_step=$param
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 */
function step_active($param) {
    global $current_step;
    if ($param == $current_step) {
        echo 'class="current_step" ';
    }
}

/**
 * This function displays the Step X of Y -
 * @return  string  String that says 'Step X of Y' with the right values
 */
function display_step_sequence() {
    global $current_step;
    return get_lang('Step'.$current_step).' &ndash; ';
}

/**
 * Displays a drop down box for selection the preferred language.
 */
function display_language_selection_box($name = 'language_list', $default_language = 'english') {
    // Reading language list.
    $language_list = get_language_folder_list();

    /*
    // Reduction of the number of languages shown. Enable this fragment of code for customization purposes.
    // Modify the language list according to your preference. Don't exclude the 'english' item.
    $language_to_display = array('asturian', 'bulgarian', 'english', 'italian', 'french', 'slovenian', 'slovenian_unicode', 'spanish');
    foreach ($language_list as $key => & $value) {
        if (!in_array($key, $language_to_display)) {
            unset($language_list[$key]);
        }
    }
    */

    // Sanity checks due to the possibility for customizations.
    if (!is_array($language_list) || empty($language_list)) {
        $language_list = array('english' => 'English');
    }

    // Sorting again, if it is necessary.
    //asort($language_list);

    // More sanity checks.
    if (!array_key_exists($default_language, $language_list)) {
        if (array_key_exists('english', $language_list)) {
            $default_language = 'english';
        } else {
            $language_keys = array_keys($language_list);
            $default_language = $language_keys[0];
        }
    }

    // Displaying the box.
    echo "\t\t<select name=\"$name\">\n";
    foreach ($language_list as $key => $value) {
        if ($key == $default_language) {
            $option_end = ' selected="selected">';
        } else {
            $option_end = '>';
        }
        echo "\t\t\t<option value=\"$key\"$option_end";
        echo $value;
        echo "</option>\n";
    }
    echo "\t\t</select>\n";
}

/**
 * This function displays a language dropdown box so that the installatioin
 * can be done in the language of the user
 */
function display_language_selection() { ?>
    <h2><?php get_lang('WelcomeToTheDokeosInstaller'); ?></h2>
    <div class="RequirementHeading">
        <h2><?php echo display_step_sequence(); ?><?php echo get_lang('InstallationLanguage'); ?></h2>
        <p><?php echo get_lang('PleaseSelectInstallationProcessLanguage'); ?>:</p>
        <form id="lang_form" method="post" action="<?php echo api_get_self(); ?>">
        <?php display_language_selection_box('language_list', api_get_interface_language()); ?>
        <button type="submit" name="step1" class="btn next" value="<?php echo get_lang('Next'); ?>"><?php echo get_lang('Next'); ?></button>
        <input type="hidden" name="is_executable" id="is_executable" value="-" />
        </form>
    </div>
<?php
}

/**
 * This function displays the requirements for installing Chamilo.
 *
 * @param string $installType
 * @param boolean $badUpdatePath
 * @param string The updatePath given (if given)
 * @param array $update_from_version_8 The different subversions from version 1.8
 * @param array $update_from_version_6 The different subversions from version 1.6
 *
 * @author unknow
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 */
function display_requirements($installType, $badUpdatePath, $updatePath = '', $update_from_version_8 = array(), $update_from_version_6 = array()) {
    global $_setting;
    echo '<div class="RequirementHeading"><h2>'.display_step_sequence().get_lang('Requirements')."</h2></div>";
    echo '<div class="RequirementText">';
    echo '<strong>'.get_lang('ReadThoroughly').'</strong><br />';
    echo get_lang('MoreDetails').' <a href="../../documentation/installation_guide.html" target="_blank">'.get_lang('ReadTheInstallGuide').'</a>.<br />'."\n";
    
    if ($installType == 'update')  {
        echo get_lang('IfYouPlanToUpgradeFromOlderVersionYouMightWantToHaveAlookAtTheChangelog').'<br />';
    }
    echo '</div>';

    //  SERVER REQUIREMENTS
    echo '<div class="RequirementHeading"><h2>'.get_lang('ServerRequirements').'</h2>';
    echo '<div class="RequirementText">'.get_lang('ServerRequirementsInfo').'</div>';
    echo '<div class="RequirementContent">';
    echo '<table class="table">
            <tr>
                <td class="requirements-item">'.get_lang('PHPVersion').' >= '.REQUIRED_PHP_VERSION.'</td>
                <td class="requirements-value">';
    if (phpversion() < REQUIRED_PHP_VERSION) {
        echo '<strong><font color="red">'.get_lang('PHPVersionError').'</font></strong>';
    } else {
        echo '<strong><font color="green">'.get_lang('PHPVersionOK'). ' '.phpversion().'</font></strong>';
    }
    echo '</td>
            </tr>
            <tr>
                <td class="requirements-item"><a href="http://php.net/manual/en/book.session.php" target="_blank">Session</a> '.get_lang('support').'</td>
                <td class="requirements-value">'.check_extension('session', get_lang('Yes'), get_lang('ExtensionSessionsNotAvailable')).'</td>
            </tr>
            <tr>
                <td class="requirements-item"><a href="http://php.net/manual/en/book.mysql.php" target="_blank">MySQL</a> '.get_lang('support').'</td>
                <td class="requirements-value">'.check_extension('mysql', get_lang('Yes'), get_lang('ExtensionMySQLNotAvailable')).'</td>
            </tr>
            <tr>
                <td class="requirements-item"><a href="http://php.net/manual/en/book.zlib.php" target="_blank">Zlib</a> '.get_lang('support').'</td>
                <td class="requirements-value">'.check_extension('zlib', get_lang('Yes'), get_lang('ExtensionZlibNotAvailable')).'</td>
            </tr>
            <tr>
                <td class="requirements-item"><a href="http://php.net/manual/en/book.pcre.php" target="_blank">Perl-compatible regular expressions</a> '.get_lang('support').'</td>
                <td class="requirements-value">'.check_extension('pcre', get_lang('Yes'), get_lang('ExtensionPCRENotAvailable')).'</td>
            </tr>
            <tr>
                <td class="requirements-item"><a href="http://php.net/manual/en/book.xml.php" target="_blank">XML</a> '.get_lang('support').'</td>
                <td class="requirements-value">'.check_extension('xml', get_lang('Yes'), get_lang('No')).'</td>
            </tr>
            <tr>
                <td class="requirements-item"><a href="http://php.net/manual/en/book.mbstring.php" target="_blank">Multibyte string</a> '.get_lang('support').' ('.get_lang('Optional').')</td>
                <td class="requirements-value">'.check_extension('mbstring', get_lang('Yes'), get_lang('ExtensionMBStringNotAvailable'), true).'</td>
            </tr>
            <tr>
                <td class="requirements-item"><a href="http://php.net/manual/en/book.iconv.php" target="_blank">Iconv</a> '.get_lang('support').' ('.get_lang('Optional').')</td>
                <td class="requirements-value">'.check_extension('iconv', get_lang('Yes'), get_lang('No'), true).'</td>
            </tr>
            <tr>
                <td class="requirements-item"><a href="http://php.net/manual/en/book.intl.php" target="_blank">Internationalization</a> '.get_lang('support').' ('.get_lang('Optional').')</td>
                <td class="requirements-value">'.check_extension('intl', get_lang('Yes'), get_lang('No'), true).'</td>
            </tr>
            <tr>
                <td class="requirements-item"><a href="http://php.net/manual/en/book.image.php" target="_blank">GD</a> '.get_lang('support').'</td>
                <td class="requirements-value">'.check_extension('gd', get_lang('Yes'), get_lang('ExtensionGDNotAvailable')).'</td>
            </tr>
            <tr>
                <td class="requirements-item"><a href="http://php.net/manual/en/book.json.php" target="_blank">JSON</a> '.get_lang('support').'</td>
                <td class="requirements-value">'.check_extension('json', get_lang('Yes'), get_lang('No')).'</td>
            </tr>
            <tr>
                <td class="requirements-item"><a href="http://php.net/manual/en/book.ldap.php" target="_blank">LDAP</a> '.get_lang('support').' ('.get_lang('Optional').')</td>
                <td class="requirements-value">'.check_extension('ldap', get_lang('Yes'), get_lang('ExtensionLDAPNotAvailable'), true).'</td>
            </tr>
            <tr>
                <td class="requirements-item"><a href="http://xapian.org/" target="_blank">Xapian</a> '.get_lang('support').' ('.get_lang('Optional').')</td>
                <td class="requirements-value">'.check_extension('xapian', get_lang('Yes'), get_lang('No'), true).'</td>
            </tr>
            
            <tr>
                <td class="requirements-item"><a href="http://php.net/manual/en/book.curl.php" target="_blank">cURL</a> '.get_lang('support').' ('.get_lang('Optional').')</td>
                <td class="requirements-value">'.check_extension('curl', get_lang('Yes'), get_lang('No'), true).'</td>
            </tr>

          </table>';
    echo '  </div>';
    echo '</div>';

    // RECOMMENDED SETTINGS
    // Note: these are the settings for Joomla, does this also apply for Chamilo?
    // Note: also add upload_max_filesize here so that large uploads are possible
    echo '<div class="RequirementHeading"><h2>'.get_lang('RecommendedSettings').'</h2>';
    echo '<div class="RequirementText">'.get_lang('RecommendedSettingsInfo').'</div>';
    echo '<div class="RequirementContent">';
    echo '<table class="table">
            <tr>
                <th>'.get_lang('Setting').'</th>
                <th>'.get_lang('Recommended').'</th>
                <th>'.get_lang('Actual').'</th>
            </tr>
            <tr>
                <td class="requirements-item"><a href="http://php.net/manual/features.safe-mode.php">Safe Mode</a></td>
                <td class="requirements-recommended">'.Display::label('OFF', 'success').'</td>
                <td class="requirements-value">'.check_php_setting('safe_mode','OFF').'</td>
            </tr>
            <tr>
                <td class="requirements-item"><a href="http://php.net/manual/ref.errorfunc.php#ini.display-errors">Display Errors</a></td>
                <td class="requirements-recommended">'.Display::label('OFF', 'success').'</td>
                <td class="requirements-value">'.check_php_setting('display_errors','OFF').'</td>
            </tr>
            <tr>
                <td class="requirements-item"><a href="http://php.net/manual/ini.core.php#ini.file-uploads">File Uploads</a></td>
                <td class="requirements-recommended">'.Display::label('ON', 'success').'</td>
                <td class="requirements-value">'.check_php_setting('file_uploads','ON').'</td>
            </tr>
            <tr>
                <td class="requirements-item"><a href="http://php.net/manual/ref.info.php#ini.magic-quotes-gpc">Magic Quotes GPC</a></td>
                <td class="requirements-recommended">'.Display::label('OFF', 'success').'</td>
                <td class="requirements-value">'.check_php_setting('magic_quotes_gpc','OFF').'</td>
            </tr>
            <tr>
                <td class="requirements-item"><a href="http://php.net/manual/ref.info.php#ini.magic-quotes-runtime">Magic Quotes Runtime</a></td>
                <td class="requirements-recommended">'.Display::label('OFF', 'success').'</td>
                <td class="requirements-value">'.check_php_setting('magic_quotes_runtime','OFF').'</td>
            </tr>
            <tr>
                <td class="requirements-item"><a href="http://php.net/manual/security.globals.php">Register Globals</a></td>
                <td class="requirements-recommended">'.Display::label('OFF', 'success').'</td>
                <td class="requirements-value">'.check_php_setting('register_globals','OFF').'</td>
            </tr>
            <tr>
                <td class="requirements-item"><a href="http://php.net/manual/ref.session.php#ini.session.auto-start">Session auto start</a></td>
                <td class="requirements-recommended">'.Display::label('OFF', 'success').'</td>
                <td class="requirements-value">'.check_php_setting('session.auto_start','OFF').'</td>
            </tr>
            <tr>
                <td class="requirements-item"><a href="http://php.net/manual/ini.core.php#ini.short-open-tag">Short Open Tag</a></td>
                <td class="requirements-recommended">'.Display::label('OFF', 'success').'</td>
                <td class="requirements-value">'.check_php_setting('short_open_tag','OFF').'</td>
            </tr>
            <tr>
                <td class="requirements-item"><a href="http://www.php.net/manual/en/session.configuration.php#ini.session.cookie-httponly">Cookie HTTP Only</a></td>
                <td class="requirements-recommended">'.Display::label('ON', 'success').'</td>
                <td class="requirements-value">'.check_php_setting('session.cookie_httponly','ON').'</td>
            </tr>
            <tr>
                <td class="requirements-item"><a href="http://php.net/manual/ini.core.php#ini.upload-max-filesize">Maximum upload file size</a></td>
                <td class="requirements-recommended">'.Display::label('>= '.REQUIRED_MIN_UPLOAD_MAX_FILESIZE.'M', 'success').'</td>
                <td class="requirements-value">'.compare_setting_values(ini_get('upload_max_filesize'), REQUIRED_MIN_UPLOAD_MAX_FILESIZE).'</td>
            </tr>
            <tr>
                <td class="requirements-item"><a href="http://php.net/manual/ini.core.php#ini.post-max-size">Maximum post size</a></td>
                <td class="requirements-recommended">'.Display::label('>= '.REQUIRED_MIN_POST_MAX_SIZE.'M', 'success').'</td>
                <td class="requirements-value">'.compare_setting_values(ini_get('post_max_size'), REQUIRED_MIN_POST_MAX_SIZE).'</td>
            </tr>            
            <tr>
                <td class="requirements-item"><a href="http://www.php.net/manual/en/ini.core.php#ini.memory-limit">Memory Limit</a></td>
                <td class="requirements-recommended">'.Display::label('>= '.REQUIRED_MIN_MEMORY_LIMIT.'M', 'success').'</td>
                <td class="requirements-value">'.compare_setting_values(ini_get('memory_limit'), REQUIRED_MIN_MEMORY_LIMIT).'</td>
            </tr>
          </table>';
    echo '  </div>';
    echo '</div>';

    // DIRECTORY AND FILE PERMISSIONS
    echo '<div class="RequirementHeading"><h2>'.get_lang('DirectoryAndFilePermissions').'</h2>';
    echo '<div class="RequirementText">'.get_lang('DirectoryAndFilePermissionsInfo').'</div>';
    echo '<div class="RequirementContent">';
    
    $course_attempt_name = '__XxTestxX__';
    $course_dir = api_get_path(SYS_COURSE_PATH).$course_attempt_name;    
    
    //Just in case
    @unlink($course_dir.'/test.txt');
    @rmdir($course_dir);
    
    $perms_dir = array(0777, 0755, 0775, 0770, 0750, 0700);
    $perms_fil = array(0666, 0644, 0664, 0660, 0640, 0600);
    
    $course_test_was_created = false;
    
    $dir_perm_verified = 0777;
    foreach ($perms_dir as $perm) {
        $r = @mkdir($course_dir, $perm);        
        if ($r === true) { 
            $dir_perm_verified = $perm;
            $course_test_was_created = true;
            break;
        }
    }
    
    $fil_perm_verified = 0666;
    
    if (is_dir($course_dir)) {    
        foreach ($perms_fil as $perm) {
            $r = @touch($course_dir.'/test.txt',$perm);
            if ($r === true) { 
                $fil_perm_verified = $perm;
                break;
            }
        }
    }   
    
    @unlink($course_dir.'/test.txt');
    @rmdir($course_dir);
    
    $_SESSION['permissions_for_new_directories'] = $_setting['permissions_for_new_directories'] = $dir_perm_verified;
    $_SESSION['permissions_for_new_files']       = $_setting['permissions_for_new_files'] = $fil_perm_verified;
    
    $dir_perm = Display::label('0'.decoct($dir_perm_verified), 'info');
    $file_perm = Display::label('0'.decoct($fil_perm_verified), 'info');
    
    $course_test_was_created  = $course_test_was_created == true ? Display::label(get_lang('Yes'), 'success') : Display::label(get_lang('No'), 'warning');

    echo '<table class="table">
            <tr>
                <td class="requirements-item">chamilo/main/inc/conf/</td>
                <td class="requirements-value">'.check_writable('inc/conf/').'</td>
            </tr>
            <tr>
                <td class="requirements-item">chamilo/main/upload/users/</td>
                <td class="requirements-value">'.check_writable('upload/users/').'</td>
            </tr>
            <tr>
                <td class="requirements-item">chamilo/main/default_course_document/images/</td>
                <td class="requirements-value">'.check_writable('default_course_document/images/').'</td>
            </tr>
            <tr>
                <td class="requirements-item">chamilo/archive/</td>
                <td class="requirements-value">'.check_writable('../archive/').'</td>
            </tr>
            <tr>
                <td class="requirements-item">chamilo/courses/</td>
                <td class="requirements-value">'.check_writable('../courses/').' </td>
            </tr>            
            <tr>
                <td class="requirements-item">'.get_lang('CourseTestWasCreated').'</td>
                <td class="requirements-value">'.$course_test_was_created.' </td>
            </tr>            
            <tr>
                <td class="requirements-item">'.get_lang('PermissionsForNewDirs').'</td>
                <td class="requirements-value">'.$dir_perm.' </td>
            </tr>            
            <tr>
                <td class="requirements-item">'.get_lang('PermissionsForNewFiles').'</td>
                <td class="requirements-value">'.$file_perm.' </td>
            </tr>            
            <tr>
                <td class="requirements-item">chamilo/home/</td>
                <td class="requirements-value">'.check_writable('../home/').'</td>
            </tr>
            <tr>
                <td class="requirements-item">chamilo/main/css/</td>
                <td class="requirements-value">'.check_writable('css/', true).' ('.get_lang('SuggestionOnlyToEnableCSSUploadFeature').')</td>
            </tr>
            <tr>
                <td class="requirements-item">chamilo/main/lang/</td>
                <td class="requirements-value">'.check_writable('lang/', true).' ('.get_lang('SuggestionOnlyToEnableSubLanguageFeature').')</td>
            </tr>'.
            //'<tr>
            //    <td class="requirements-item">chamilo/searchdb/</td>
            //    <td class="requirements-value">'.check_writable('../searchdb/').'</td>
            //</tr>'.
            //'<tr>
            //    <td class="requirements-item">'.session_save_path().'</td>
            //    <td class="requirements-value">'.(is_writable(session_save_path())
            //      ? '<strong><font color="green">'.get_lang('Writable').'</font></strong>'
            //      : '<strong><font color="red">'.get_lang('NotWritable').'</font></strong>').'</td>
            //</tr>'.
            '';
    echo '    </table>';
    echo '  </div>';
    echo '</div>';

    if ($installType == 'update' && (empty($updatePath) || $badUpdatePath)) {
        if ($badUpdatePath) { ?>
            <div class="error-message">
                <?php echo get_lang('Error'); ?>!<br />
                Chamilo <?php echo (isset($_POST['step2_update_6']) ? implode('|', $update_from_version_6) : implode('|', $update_from_version_8)).' '.get_lang('HasNotBeenFoundInThatDir'); ?>.
            </div>
        <?php }
        else {
            echo '<br />';
        }
        ?>
            <table border="0" cellpadding="5" align="center">
            <tr>
            <td><?php echo get_lang('OldVersionRootPath'); ?>:</td>
            <td><input type="text" name="updatePath" size="50" value="<?php echo ($badUpdatePath && !empty($updatePath)) ? htmlentities($updatePath) : api_get_path(SYS_SERVER_ROOT_PATH).'old_version/'; ?>" /></td>
            </tr>
            <tr>
            <td colspan="2" align="center">
                <button type="submit" class="back" name="step1" value="&lt; <?php echo get_lang('Back'); ?>" ><?php echo get_lang('Back'); ?></button>
                <input type="hidden" name="is_executable" id="is_executable" value="-" />
                <button type="submit" class="btn next" name="<?php echo (isset($_POST['step2_update_6']) ? 'step2_update_6' : 'step2_update_8'); ?>" value="<?php echo get_lang('Next'); ?> &gt;" ><?php echo get_lang('Next'); ?></button>
            </td>
            </tr>
            </table>
        <?php
    } else {
        $error = false;
        // First, attempt to set writing permissions if we don't have them yet
        $perm = api_get_permissions_for_new_directories();        
        $perm_file = api_get_permissions_for_new_files();

        $notwritable = array();
        $curdir = getcwd();

        $checked_writable = api_get_path(CONFIGURATION_PATH);
        if (!is_writable($checked_writable)) {
            $notwritable[] = $checked_writable;
            @chmod($checked_writable, $perm);
        }

        $checked_writable = api_get_path(SYS_CODE_PATH).'upload/users/';
        if (!is_writable($checked_writable)) {
            $notwritable[] = $checked_writable;
            @chmod($checked_writable, $perm);
        }

        $checked_writable = api_get_path(SYS_CODE_PATH).'default_course_document/images/';
        if (!is_writable($checked_writable)) {
            $notwritable[] = $checked_writable;
            @chmod($checked_writable, $perm);
        }

        $checked_writable = api_get_path(SYS_ARCHIVE_PATH);
        if (!is_writable($checked_writable)) {
            $notwritable[] = $checked_writable;
            @chmod($checked_writable, $perm);
        }

        $checked_writable = api_get_path(SYS_COURSE_PATH);
        if (!is_writable($checked_writable)) {
            $notwritable[] = $checked_writable;
            @chmod($checked_writable, $perm);
        }
        
        if ($course_test_was_created == false) {
            $error = true;
        }
        

        $checked_writable = api_get_path(SYS_PATH).'home/';
        if (!is_writable($checked_writable)) {
            $notwritable[] = realpath($checked_writable);
            @chmod($checked_writable, $perm);
        }

        $checked_writable = api_get_path(CONFIGURATION_PATH).'configuration.php';
        if (file_exists($checked_writable) && !is_writable($checked_writable)) {
            $notwritable[] = $checked_writable;
            @chmod($checked_writable, $perm_file);
        }

        // Second, if this fails, report an error

        //--> The user would have to adjust the permissions manually
        if (count($notwritable) > 0) {
            $error = true;
            echo '<div class="error-message">';
	            echo '<center><h3>'.get_lang('Warning').'</h3></center>';
	            printf(get_lang('NoWritePermissionPleaseReadInstallGuide'), '</font>
	            <a href="../../documentation/installation_guide.html" target="blank">', '</a> <font color="red">');
			echo '</div>';
				
            echo '<ul>';
            foreach ($notwritable as $value) {
                echo '<li>'.$value.'</li>';
            }
            echo '</ul>';
            
        }

        // Check wether a Chamilo configuration file already exists.
        elseif (file_exists(api_get_path(CONFIGURATION_PATH).'configuration.php')) {
            echo '<div class="warning-message"><h4><center>';
            echo get_lang('WarningExistingDokeosInstallationDetected');
            echo '</center></h4></div>';
        }

        // And now display the choice buttons (go back or install)
        ?>
        <p align="center" style="padding-top:15px">
        <button type="submit" name="step1" class="back" onclick="javascript: window.location='index.php'; return false;" value="&lt; <?php echo get_lang('Previous'); ?>" ><?php echo get_lang('Previous'); ?></button>
        <button type="submit" name="step2_install" class="add" value="<?php echo get_lang("NewInstallation"); ?>" <?php if ($error) echo 'disabled="disabled"'; ?> ><?php echo get_lang('NewInstallation'); ?></button>
        <input type="hidden" name="is_executable" id="is_executable" value="-" />
        <?php
        // Real code
        echo '<button type="submit" class="save" name="step2_update_8" value="Upgrade from Dokeos 1.8.x"';
        if ($error) echo ' disabled="disabled"';
        // Temporary code for alpha version, disabling upgrade
        //echo '<input type="submit" name="step2_update" value="Upgrading is not possible in this beta version"';
        //echo ' disabled="disabled"';
        //end temp code
        echo ' >'.get_lang('UpgradeFromDokeos18x').'</button>';
        echo ' <button type="submit" class="save" name="step2_update_6" value="Upgrade from Dokeos 1.6.x"';
        if ($error) echo ' disabled="disabled"';
        echo ' >'.get_lang('UpgradeFromDokeos16x').'</button>';
        echo '</p>';
    }
}

/**
 * Displays the license (GNU GPL) as step 2, with
 * - an "I accept" button named step3 to proceed to step 3;
 * - a "Back" button named step1 to go back to the first step.
 */

function display_license_agreement() {
    echo '<div class="RequirementHeading"><h2>'.display_step_sequence().get_lang('Licence').'</h2>';
    echo '<p>'.get_lang('DokeosLicenseInfo').'</p>';
    echo '<p><a href="../../documentation/license.html" target="_blank">'.get_lang('PrintVers').'</a></p>';
    echo '</div>';
    ?>
    <table>
		<tr><td>
            <p style="font-size:90%">
            <textarea cols="90" rows="7" class="span6">
            	<?php echo api_htmlentities(@file_get_contents(api_get_path(SYS_PATH).'documentation/license.txt')); ?>
            </textarea></p>
        </td>
        </tr>
        <tr><td>
              <label class="checkbox">  
                <input type="checkbox" name="accept" id="accept_licence" value="1" />
                <?php echo get_lang('IAccept'); ?>
              </label>
            </td>
		</tr>
        <tr><td><p style="color:#666"><br /><?php echo get_lang('DokeosArtLicense'); ?></p></td></tr>
        <tr>
        	<td>
            <table width="100%">
            	<tr>
                	<td></td>
                	<td align="center">
                    	<button type="submit" class="btn back" name="step1" value="&lt; <?php echo get_lang('Previous'); ?>" ><?php echo get_lang('Previous'); ?></button>
                    	<input type="hidden" name="is_executable" id="is_executable" value="-" />
                    	<button type="submit" class="btn next" name="step3" onclick="javascript: if(!document.getElementById('accept_licence').checked) { alert('<?php echo get_lang('YouMustAcceptLicence')?>');return false;}" value="<?php echo get_lang('Next'); ?> &gt;" ><?php echo get_lang('Next'); ?></button>
                	</td>
            	</tr>
            </table>
            </td>
		</tr>
	</table>

    <!-- Contact information form -->
	<div>
    	
        	<a href="javascript://" class = "advanced_parameters" >
            	<span id="img_plus_and_minus">&nbsp;<img src="<?php echo api_get_path(WEB_IMG_PATH) ?>div_hide.gif" alt="<?php echo get_lang('Hide') ?>" title="<?php echo get_lang('Hide')?>" style ="vertical-align:middle" />&nbsp;<?php echo get_lang('ContactInformation') ?></span>
           	</a>
		
	</div>
                    
    <div id="id_contact_form" style="display:block">
    	<div class="normal-message"><?php echo get_lang('ContactInformationDescription') ?></div>
        <div id="contact_registration">
        	<p><?php echo get_contact_registration_form() ?></p><br />
    	</div>	
	</div>
    <?php
}


/**
 * Get contact registration form
 */
function get_contact_registration_form() {

    $html ='
   <form class="form-horizontal">    
   <fieldset style="width:95%;padding:15px;border:1pt solid #eee">
    <div id="div_sent_information"></div>  
    <div class="control-group">
            <label class="control-label"><span class="form_required">*</span>'.get_lang('Name').'</label>
            <div class="controls"><input id="person_name" type="text" name="person_name" size="30" /></div>
    </div>
    <div class="control-group">
            <label class="control-label"><span class="form_required">*</span>'.get_lang('Email').'</label>
            <div class="controls"><input id="person_name" type="text" name="person_email" size="30" /></div>
    </div>
    <div class="control-group">
            <label class="control-label"><span class="form_required">*</span>'.get_lang('CompanyName').'</label>
            <div class="controls"><input id="company_name" type="text" name="company_name" size="30" /></div>
    </div>
    <div class="control-group">
            <div class="control-label"><span class="form_required">*</span>'.get_lang('CompanyActivity').'</div>
            <div class="controls">
                    <select name="company_activity" id="company_activity" >
                            <option value="">--- '.get_lang('SelectOne').' ---</option>
                            <Option value="Advertising/Marketing/PR">Advertising/Marketing/PR</Option><Option value="Agriculture/Forestry">Agriculture/Forestry</Option>
                            <Option value="Architecture">Architecture</Option><Option value="Banking/Finance">Banking/Finance</Option>
                            <Option value="Biotech/Pharmaceuticals">Biotech/Pharmaceuticals</Option><Option value="Business Equipment">Business Equipment</Option>
                            <Option value="Business Services">Business Services</Option><Option value="Construction">Construction</Option>
                            <Option value="Consulting/Research">Consulting/Research</Option><Option value="Education">Education</Option>
                            <Option value="Engineering">Engineering</Option><Option value="Environmental">Environmental</Option>
                            <Option value="Government">Government</Option><Option value="Healthcare">Health Care</Option>
                            <Option value="Hospitality/Lodging/Travel">Hospitality/Lodging/Travel</Option><Option value="Insurance">Insurance</Option>
                            <Option value="Legal">Legal</Option><Option value="Manufacturing">Manufacturing</Option>
                            <Option value="Media/Entertainment">Media/Entertainment</Option><Option value="Mortgage">Mortgage</Option>
                            <Option value="Non-Profit">Non-Profit</Option><Option value="Real Estate">Real Estate</Option>
                            <Option value="Restaurant">Restaurant</Option><Option value="Retail">Retail</Option>
                            <Option value="Shipping/Transportation">Shipping/Transportation</Option>
                            <Option value="Technology">Technology</Option><Option value="Telecommunications">Telecommunications</Option>
                            <Option value="Other">Other</Option>
                    </select>
            </div>
    </div>

    <div class="control-group">
            <div class="control-label"><span class="form_required">*</span>'.get_lang('PersonRole').'</div>
            <div class="controls">
                    <select name="person_role" id="person_role" >
                            <option value="">--- '.get_lang('SelectOne').' ---</option>
                            <Option value="Administration">Administration</Option><Option value="CEO/President/ Owner">CEO/President/ Owner</Option>
                            <Option value="CFO">CFO</Option><Option value="CIO/CTO">CIO/CTO</Option>
                            <Option value="Consultant">Consultant</Option><Option value="Customer Service">Customer Service</Option>
                            <Option value="Engineer/Programmer">Engineer/Programmer</Option><Option value="Facilities/Operations">Facilities/Operations</Option>
                            <Option value="Finance/ Accounting Manager">Finance/ Accounting Manager</Option><Option value="Finance/ Accounting Staff">Finance/ Accounting Staff</Option>
                            <Option value="General Manager">General Manager</Option><Option value="Human Resources">Human Resources</Option>
                            <Option value="IS/IT Management">IS/IT Management</Option><Option value="IS/ IT Staff">IS/ IT Staff</Option>
                            <Option value="Marketing Manager">Marketing Manager</Option><Option value="Marketing Staff">Marketing Staff</Option>
                            <Option value="Partner/Principal">Partner/Principal</Option><Option value="Purchasing Manager">Purchasing Manager</Option>
                            <Option value="Sales/ Business Dev. Manager">Sales/ Business Dev. Manager</Option><Option value="Sales/ Business Dev.">Sales/ Business Dev.</Option>
                            <Option value="Vice President/Senior Manager">Vice President/Senior Manager</Option><Option value="Other">Other</Option>
                    </select>
            </div>
    </div>
    
    <div class="control-group">
            <div class="control-label"><span class="form_required">*</span>'.get_lang('CompanyCountry').'</div>
            <div class="controls">'.get_countries_list_from_array(true).'</div>
    </div>
    <div class="control-group">
            <div class="control-label">'.get_lang('CompanyCity').'</div>
            <div class="controls">
                    <input type="text" id="company_city" name="company_city" size="30" />
            </div>
    </div>
    <div class="control-group">
            <div class="control-label">'.get_lang('WhichLanguageWouldYouLikeToUseWhenContactingYou').'</div>
            <div class="controls">
                    <select id="language" name="language">
                            <option value="bulgarian">Bulgarian</option>
                            <option value="indonesian">Bahasa Indonesia</option>
                            <option value="bosnian">Bosanski</option>
                            <option value="german">Deutsch</option>
                            <option selected="selected" value="english">English</option>
                            <option value="spanish">Spanish</option>
                            <option value="french">Français</option>
                            <option value="italian">Italian</option>
                            <option value="hungarian">Magyar</option>
                            <option value="dutch">Nederlands</option>
                            <option value="brazilian">Português do Brasil</option>
                            <option value="portuguese">Português europeu</option>
                            <option value="slovenian">Slovenčina</option>
                    </select>
            </div>
    </div>
    
    <div class="control-group">
            <div class="control-label">'.get_lang('HaveYouThePowerToTakeFinancialDecisions').'</div>
            <div class="controls">
                    <input type="radio" name="financial_decision" id="financial_decision1" value="1" checked />'.get_lang('Yes').'
                    <input type="radio" name="financial_decision" id="financial_decision2" value="0" />'.get_lang('No').'
            </div>
    </div>
    <div class="clear"></div>
    <div class="control-group">
            <div class="control-label">&nbsp;</div>
            <div class="controls"><button type="button" class="save" onclick="javascript:send_contact_information();" value="'.get_lang('SendInformation').'" >'.get_lang('SendInformation').'</button></div>
    </div>
    <div class="control-group">
            <div class="control-label">&nbsp;</div>
            <div class="controls"><span class="form_required">*</span><small>'.get_lang('FieldRequired').'</small></div>
    </div>    
</fieldset></form>';

return $html;
}

/**
 * Displays a parameter in a table row.
 * Used by the display_database_settings_form function.
 * @param   string  Type of install
 * @param   string  Name of parameter
 * @param   string  Field name (in the HTML form)
 * @param   string  Field value
 * @param   string  Extra notice (to show on the right side)
 * @param   boolean Whether to display in update mode
 * @param   string  Additional attribute for the <tr> element
 * @return  void    Direct output
 */
function display_database_parameter($install_type, $parameter_name, $form_field_name, $parameter_value, $extra_notice, $display_when_update = true, $tr_attribute = '') {
    echo "<tr ".$tr_attribute.">";
    echo "<td>$parameter_name&nbsp;&nbsp;</td>";
    
    if ($install_type == INSTALL_TYPE_UPDATE && $display_when_update) {
        echo '<td><input type="hidden" name="'.$form_field_name.'" id="'.$form_field_name.'" value="'.api_htmlentities($parameter_value).'" />'.$parameter_value."</td>";
    } else {
        $inputtype = $form_field_name == 'dbPassForm' ? 'password' : 'text';

        //Slightly limit the length of the database prefix to avoid having to cut down the databases names later on
        $maxlength = $form_field_name == 'dbPrefixForm' ? '15' : MAX_FORM_FIELD_LENGTH;
        if ($install_type == INSTALL_TYPE_UPDATE) {
            echo '<input type="hidden" name="'.$form_field_name.'" id="'.$form_field_name.'" value="'.api_htmlentities($parameter_value).'" />';
            echo '<td>'.api_htmlentities($parameter_value)."</td>";            
        } else {
            echo '<td><input type="'.$inputtype.'" size="'.DATABASE_FORM_FIELD_DISPLAY_LENGTH.'" maxlength="'.$maxlength.'" name="'.$form_field_name.'" id="'.$form_field_name.'" value="'.api_htmlentities($parameter_value).'" />'."</td>";
            echo "<td>$extra_notice</td>";
        }
        
    }
    echo "</tr>";
}

/**
 * Displays step 3 - a form where the user can enter the installation settings
 * regarding the databases - login and password, names, prefixes, single
 * or multiple databases, tracking or not...
 */
function display_database_settings_form($installType, $dbHostForm, $dbUsernameForm, $dbPassForm, $dbPrefixForm, $enableTrackingForm, $singleDbForm, $dbNameForm, $dbStatsForm, $dbScormForm, $dbUserForm) {

    if ($installType == 'update') {
        global $_configuration, $update_from_version_6;

        if (in_array($_POST['old_version'], $update_from_version_6)) {
            $dbHostForm     	= get_config_param('dbHost');
            
            $dbUsernameForm 	= get_config_param('dbLogin');
            $dbPassForm     	= get_config_param('dbPass');
            $dbPrefixForm   	= get_config_param('dbNamePrefix');
            $enableTrackingForm = get_config_param('is_trackingEnabled');
            $singleDbForm   	= get_config_param('singleDbEnabled');
            $dbHostForm     	= get_config_param('mainDbName');
                        
            $dbStatsForm    	= get_config_param('statsDbName');
            $dbScormForm    	= get_config_param('scormDbName');
            $dbUserForm     	= get_config_param('user_personal_database');
            $dbScormExists  	= true;
        } else {
            $dbHostForm     	= $_configuration['db_host'];
            $dbUsernameForm 	= $_configuration['db_user'];
            $dbPassForm     	= $_configuration['db_password'];
            $dbPrefixForm   	= $_configuration['db_prefix'];
            $enableTrackingForm = $_configuration['tracking_enabled'];
            $singleDbForm   	= $_configuration['single_database'];
            $dbNameForm     	= $_configuration['main_database'];
            $dbStatsForm    	= $_configuration['statistics_database'];
            $dbScormForm    	= $_configuration['scorm_database'];
            $dbUserForm     	= $_configuration['user_personal_database'];
            $dbScormExists  	= true;
        }

        if (empty($dbScormForm)) {
            if ($singleDbForm) {
                $dbScormForm = $dbNameForm;
            } else {
                $dbScormForm = $dbPrefixForm.'scorm';
                $dbScormExists = false;
            }
        }
        
        if (empty($dbUserForm)) {
            $dbUserForm = $singleDbForm ? $dbNameForm : $dbPrefixForm.'chamilo_user';
        }
        echo '<div class="RequirementHeading"><h2>' . display_step_sequence() .get_lang('DBSetting') . '</h2></div>';
        echo '<div class="RequirementContent">';
        echo get_lang('DBSettingUpgradeIntro');
        echo '</div>';
    } else {
        if (empty($dbPrefixForm)) { //make sure there is a default value for db prefix
            $dbPrefixForm = '';
        }
        echo '<div class="RequirementHeading"><h2>' . display_step_sequence() .get_lang('DBSetting') . '</h2></div>';
        echo '<div class="RequirementContent">';
        echo get_lang('DBSettingIntro');
        echo '</div>';                
    }
    ?>    
    </td>
    </tr>
    <tr>
    <td>    
    <table class="data_table_no_border">
    <tr>
      <td width="40%"><?php echo get_lang('DBHost'); ?> </td>
      <?php if ($installType == 'update'): ?>
      <td width="30%"><input type="hidden" name="dbHostForm" value="<?php echo htmlentities($dbHostForm); ?>" /><?php echo $dbHostForm; ?></td>
      <td width="30%">&nbsp;</td>
      <?php else: ?>
      <td width="30%"><input type="text" size="25" maxlength="50" name="dbHostForm" value="<?php echo htmlentities($dbHostForm); ?>" /></td>
      <td width="30%"><?php echo get_lang('EG').' localhost'; ?></td>
      <?php endif; ?>
    </tr>
    <tr>
    <?php	
    //database user username
    $example_login = get_lang('EG').' root';
    display_database_parameter($installType, get_lang('DBLogin'), 'dbUsernameForm', $dbUsernameForm, $example_login);

    //database user password
    $example_password = get_lang('EG').' '.api_generate_password();
    display_database_parameter($installType, get_lang('DBPassword'), 'dbPassForm', $dbPassForm, $example_password);

    echo '<input type="hidden" name="enableTrackingForm" value="1" />';
        
    $style = '';    
    if ($installType == INSTALL_TYPE_UPDATE) {
        $style = '';
    } 
       
    //Database Name fix replace weird chars
    if ($installType != INSTALL_TYPE_UPDATE) {
        $dbNameForm = str_replace(array('-','*', '$', ' ', '.'), '', $dbNameForm);
        $dbNameForm = replace_dangerous_char($dbNameForm);        
    }
    
    display_database_parameter($installType, get_lang('MainDB'), 'dbNameForm',  $dbNameForm,  '&nbsp;', null, 'id="optional_param1" '.$style);
    
    //Only for updates we show this options
    if ($installType == INSTALL_TYPE_UPDATE) {
    	display_database_parameter($installType, get_lang('StatDB'), 'dbStatsForm', $dbStatsForm, '&nbsp;', null, 'id="optional_param2" '.$style);
	    if ($installType == INSTALL_TYPE_UPDATE && in_array($_POST['old_version'], $update_from_version_6)) {
        	display_database_parameter($installType, get_lang('ScormDB'), 'dbScormForm', $dbScormForm, '&nbsp;', null, 'id="optional_param3" '.$style);
    	}    
    	display_database_parameter($installType, get_lang('UserDB'), 'dbUserForm', $dbUserForm, '&nbsp;', null, 'id="optional_param4" '.$style);
    }
    ?>
    <tr>
        <td></td>
        <td>
            <button type="submit" class="btn" name="step3" value="<?php echo get_lang('CheckDatabaseConnection'); ?>" >
                <?php echo get_lang('CheckDatabaseConnection'); ?></button>
        </td>
    </tr>    
    <tr>
        <td>
        
        <?php
        
        $dbConnect = test_db_connect($dbHostForm, $dbUsernameForm, $dbPassForm, $singleDbForm, $dbPrefixForm, $dbNameForm);        
        
        $database_exists_text = '';
        
        if (database_exists($dbNameForm)) {
            $database_exists_text = '<div class="warning-message">'.get_lang('ADatabaseWithTheSameNameAlreadyExists').'</div>';
        } else {            
            if ($dbConnect == -1) {
                 $database_exists_text = '<div class="warning-message">'.sprintf(get_lang('UserXCantHaveAccessInTheDatabaseX'), $dbUsernameForm, $dbNameForm).'</div>';                 
            } else {
                 //Try to create the database
                $user_can_create_databases = false;            
                $multipleDbCheck = @Database::query("CREATE DATABASE ".mysql_real_escape_string($dbNameForm));
                if ($multipleDbCheck !== false) {
                    $multipleDbCheck = @Database::query("DROP DATABASE IF EXISTS ".mysql_real_escape_string($dbNameForm));                
                    $user_can_create_databases = true;
                }             

                if ($user_can_create_databases) {
                    $database_exists_text = '<div class="normal-message">'.sprintf(get_lang('DatabaseXWillBeCreated'), $dbNameForm, $dbUsernameForm).'</div>';
                } else {
                    $dbConnect = 0;
                    $database_exists_text = '<div class="warning-message">'.sprintf(get_lang('DatabaseXCantBeCreatedUserXDoestHaveEnoughPermissions'), $dbNameForm, $dbUsernameForm).'</div>';                
                }
            }       
        }         
            
        if ($dbConnect == 1): ?>
        <td colspan="2">
            <?php echo $database_exists_text ?>
            <div id="db_status" class="confirmation-message">                
                Database host: <strong><?php echo Database::get_host_info(); ?></strong><br />
                Database server version: <strong><?php echo Database::get_server_info(); ?></strong><br />
                Database client version: <strong><?php echo Database::get_client_info(); ?></strong><br />
                Database protocol version: <strong><?php echo Database::get_proto_info(); ?></strong>
                <div style="clear:both;"></div>
            </div>
        </td>
        <?php else: ?>        
        <td colspan="2">
            <?php echo $database_exists_text ?>
            <div id="db_status" style="float:left;" class="error-message">                
                <div style="float:left;">
                    <strong><?php echo get_lang('FailedConectionDatabase'); ?></strong><br />
	                <strong>Database error: <?php echo Database::errno(); ?></strong><br />
	                <?php echo Database::error().'<br />'; ?>
	                
                </div>
            </div>
        </td>
        <?php endif; ?>
    </tr>
    <tr>
      <td>
          <button type="submit" name="step2" class="back" value="&lt; <?php echo get_lang('Previous'); ?>" ><?php echo get_lang('Previous'); ?></button>
      </td>
      <td>&nbsp;</td>
      <td align="right">
          <input type="hidden" name="is_executable" id="is_executable" value="-" />
           <?php if ($dbConnect == 1) { ?>          
            <button type="submit"  class="btn next" name="step4" value="<?php echo get_lang('Next'); ?> &gt;" /><?php echo get_lang('Next'); ?></button>
          <?php } else { ?>
            <button disabled="disabled" type="submit" class="btn next disabled" name="step4" value="<?php echo get_lang('Next'); ?> &gt;" /><?php echo get_lang('Next'); ?></button>
          <?php } ?>  
      </td>
    </tr>
    </table>
    <?php
}

/**
 * Displays a parameter in a table row.
 * Used by the display_configuration_settings_form function.
 */
function display_configuration_parameter($install_type, $parameter_name, $form_field_name, $parameter_value, $display_when_update = 'true') {
    echo "<tr>";
    echo "<td>$parameter_name</td>";
    if ($install_type == INSTALL_TYPE_UPDATE && $display_when_update) {
        echo '<td><input type="hidden" name="'.$form_field_name.'" value="'.api_htmlentities($parameter_value, ENT_QUOTES).'" />'.$parameter_value."</td>\n";
    } else {
        echo '<td><input type="text" size="'.FORM_FIELD_DISPLAY_LENGTH.'" maxlength="'.MAX_FORM_FIELD_LENGTH.'" name="'.$form_field_name.'" value="'.api_htmlentities($parameter_value, ENT_QUOTES).'" />'."</td>\n";
    }
    echo "</tr>";
}

/**
 * Displays step 4 of the installation - configuration settings about Chamilo itself.
 */
function display_configuration_settings_form($installType, $urlForm, $languageForm, $emailForm, $adminFirstName, $adminLastName, $adminPhoneForm, $campusForm, $institutionForm, $institutionUrlForm, $encryptPassForm, $allowSelfReg, $allowSelfRegProf, $loginForm, $passForm) {
    if ($installType != 'update' && empty($languageForm)) {
        $languageForm = $_SESSION['install_language'];
    }
    echo '<div class="RequirementHeading">';
    echo "<h2>" . display_step_sequence() . get_lang("CfgSetting") . "</h2>";
    echo '</div>';
    echo '<div class="RequirementContent">';
    echo '<p>'.get_lang('ConfigSettingsInfo').' <strong>main/inc/conf/configuration.php</strong></p>';
    echo '</div>';
    
    echo '<fieldset>';
    echo '<legend>'.get_lang('Administrator').'</legend>';
    echo '<table class="data_table_no_border">';
    
    //Parameter 1: administrator's login
    
    display_configuration_parameter($installType, get_lang('AdminLogin'), 'loginForm', $loginForm, $installType == 'update');
    
    //Parameter 2: administrator's password
    if ($installType != 'update') {
        display_configuration_parameter($installType, get_lang('AdminPass'), 'passForm', $passForm, false);
    }    
    
    //Parameters 3 and 4: administrator's names
    if (api_is_western_name_order()) {
        display_configuration_parameter($installType, get_lang('AdminFirstName'), 'adminFirstName', $adminFirstName);
        display_configuration_parameter($installType, get_lang('AdminLastName'), 'adminLastName', $adminLastName);
    } else {
        display_configuration_parameter($installType, get_lang('AdminLastName'), 'adminLastName', $adminLastName);
        display_configuration_parameter($installType, get_lang('AdminFirstName'), 'adminFirstName', $adminFirstName);
    }
    
    //Parameter 3: administrator's email
    display_configuration_parameter($installType, get_lang('AdminEmail'), 'emailForm', $emailForm);

    //Parameter 6: administrator's telephone
    display_configuration_parameter($installType, get_lang('AdminPhone'), 'adminPhoneForm', $adminPhoneForm);
    
    echo '</table>';    
    echo '</fieldset>';
    
    echo '<fieldset>';
    echo '<legend>'.get_lang('Platform').'</legend>';
    
    echo '<table class="data_table_no_border">';    
    
    //First parameter: language
    echo "<tr>";
    echo '<td>'.get_lang('MainLang')."&nbsp;&nbsp;</td>";
    if ($installType == 'update') {
        echo '<td><input type="hidden" name="languageForm" value="'.api_htmlentities($languageForm, ENT_QUOTES).'" />'.$languageForm."</td>";

    } else { // new installation
        echo '<td>';
        display_language_selection_box('languageForm', $languageForm);
        echo "</td>\n";
    }
    echo "</tr>\n";
    
    
    //Second parameter: Chamilo URL
    echo "<tr>";
    echo '<td>'.get_lang('ChamiloURL').' (<font color="red">'.get_lang('ThisFieldIsRequired')."</font>)&nbsp;&nbsp;</td>";

    if ($installType == 'update') {
        echo '<td>'.api_htmlentities($urlForm, ENT_QUOTES)."</td>\n";
    } else {
        echo '<td><input type="text" size="40" maxlength="100" name="urlForm" value="'.api_htmlentities($urlForm, ENT_QUOTES).'" />'."</td>";
    }
    echo "</tr>";


    //Parameter 9: campus name
    display_configuration_parameter($installType, get_lang('CampusName'), 'campusForm', $campusForm);

    //Parameter 10: institute (short) name
    display_configuration_parameter($installType, get_lang('InstituteShortName'), 'institutionForm', $institutionForm);

    //Parameter 11: institute (short) name
    display_configuration_parameter($installType, get_lang('InstituteURL'), 'institutionUrlForm', $institutionUrlForm);

    ?>
    <tr>
      <td><?php echo get_lang("EncryptMethodUserPass"); ?> :</td>
      <?php if ($installType == 'update') { ?>
      <td><input type="hidden" name="encryptPassForm" value="<?php echo $encryptPassForm; ?>" /><?php echo $encryptPassForm; ?></td>
      <?php } else { ?>
      <td>
          <div class="control-group">
              <label class="checkbox inline">
                <input class="checkbox" type="radio" name="encryptPassForm" value="sha1" id="encryptPass1" <?php echo ($encryptPassForm == 'sha1') ? 'checked="checked" ': ''; ?>/><?php echo 'sha1'; ?>
              </label>
       
              <label class="checkbox inline">  
                <input class="checkbox" type="radio" name="encryptPassForm" value="md5" id="encryptPass0" <?php echo $encryptPassForm == 1 ? 'checked="checked" ' : ''; ?>/><?php echo 'md5'; ?>
              </label>
            
                <label class="checkbox inline">
                    <input class="checkbox" type="radio" name="encryptPassForm" value="none" id="encryptPass2" <?php echo $encryptPassForm === '0' or $encryptPassForm === 0 ? 'checked="checked" ':''; ?>/><?php echo get_lang('None'); ?>
                </label>
           
          </div>
          </td>
      <?php } ?>
    </tr>
    <tr>
      <td><?php echo get_lang('AllowSelfReg'); ?> :</td>

      <?php if ($installType == 'update'): ?>
      <td><input type="hidden" name="allowSelfReg" value="<?php echo $allowSelfReg; ?>" /><?php echo $allowSelfReg ? get_lang('Yes') : get_lang('No'); ?></td>
      <?php else: ?>
      <td>
          <div class="control-group">
            <label class="checkbox inline">
                <input class="checkbox" type="radio" name="allowSelfReg" value="1" id="allowSelfReg1" <?php echo $allowSelfReg ? 'checked="checked" ' : ''; ?>/> <?php echo get_lang('Yes'); ?>
            </label>
            <label class="checkbox inline">
                <input class="checkbox" type="radio" name="allowSelfReg" value="0" id="allowSelfReg0" <?php echo $allowSelfReg ? '' : 'checked="checked" '; ?>/><?php echo get_lang('No'); ?>
            </label>
          </div>
      </td>
      <?php endif; ?>

    </tr>
    <tr>
      <td><?php echo get_lang('AllowSelfRegProf'); ?> :</td>

      <?php if ($installType == 'update'): ?>
      <td><input type="hidden" name="allowSelfRegProf" value="<?php echo $allowSelfRegProf; ?>" /><?php echo $allowSelfRegProf? get_lang('Yes') : get_lang('No'); ?></td>
      <?php else: ?>
      <td>
          <div class="control-group">
            <label class="checkbox inline">
                <input class="checkbox" type="radio" name="allowSelfRegProf" value="1" id="allowSelfRegProf1" <?php echo $allowSelfRegProf ? 'checked="checked" ' : ''; ?>/>
            <?php echo get_lang('Yes'); ?>
            </label>
            <label class="checkbox inline">
                <input class="checkbox" type="radio" name="allowSelfRegProf" value="0" id="allowSelfRegProf0" <?php echo $allowSelfRegProf ? '' : 'checked="checked" '; ?>/>
            <?php echo get_lang('No'); ?>
            </label>
          </div>
      </td>
      <?php endif; ?>

    </tr>
    <tr>
        <td>
            <button type="submit" class="btn back" name="step3" value="&lt; <?php echo get_lang('Previous'); ?>" /><?php echo get_lang('Previous'); ?></button>
        </td>
        <td align="right">
            <input type="hidden" name="is_executable" id="is_executable" value="-" />
            <button class="btn next" type="submit" name="step5" value="<?php echo get_lang('Next'); ?> &gt;" /><?php echo get_lang('Next'); ?></button></td>
    </tr>
    </fieldset>
    </table>
    <?php
}

/**
 * After installation is completed (step 6), this message is displayed.
 */
function display_after_install_message($installType) {
    echo '<div class="RequirementContent">'.get_lang('FirstUseTip').'</div>';
    echo '<div class="warning-message">';
    echo '<strong>'.get_lang('SecurityAdvice').'</strong>';
    echo ': ';
    printf(get_lang('ToProtectYourSiteMakeXReadOnlyAndDeleteY'), 'main/inc/conf/', 'main/install/');
    echo '</div>';
    ?></form>
    <br />
    <a class="btn btn-success btn-large btn-install" href="../../index.php"><?php echo get_lang('GoToYourNewlyCreatedPortal'); ?></a>
    <?php
}

/**
 * This function return countries list from array (hardcoded)
 * @param   bool    (Optional) True for returning countries list with select html
 * @return  array|string countries list
 */
function get_countries_list_from_array($combo = false) {
    $a_countries = array(
        "Afghanistan", "Albania", "Algeria", "Andorra", "Angola", "Antigua and Barbuda", "Argentina", "Armenia", "Australia", "Austria", "Azerbaijan",
        "Bahamas", "Bahrain", "Bangladesh", "Barbados", "Belarus", "Belgium", "Belize", "Benin", "Bhutan", "Bolivia", "Bosnia and Herzegovina", "Botswana", "Brazil", "Brunei", "Bulgaria", "Burkina Faso", "Burundi",
        "Cambodia", "Cameroon", "Canada", "Cape Verde", "Central African Republic", "Chad", "Chile", "China", "Colombi", "Comoros", "Congo (Brazzaville)", "Congo", "Costa Rica", "Cote d'Ivoire", "Croatia", "Cuba", "Cyprus", "Czech Republic",
        "Denmark", "Djibouti", "Dominica", "Dominican Republic",
        "East Timor (Timor Timur)", "Ecuador", "Egypt", "El Salvador", "Equatorial Guinea", "Eritrea", "Estonia", "Ethiopia",
        "Fiji", "Finland", "France",
        "Gabon", "Gambia, The", "Georgia", "Germany", "Ghana", "Greece", "Grenada", "Guatemala", "Guinea", "Guinea-Bissau", "Guyana",
        "Haiti", "Honduras", "Hungary",
        "Iceland", "India", "Indonesia", "Iran", "Iraq", "Ireland", "Israel", "Italy",
        "Jamaica", "Japan", "Jordan",
        "Kazakhstan", "Kenya", "Kiribati", "Korea, North", "Korea, South", "Kuwait", "Kyrgyzstan",
        "Laos", "Latvia", "Lebanon", "Lesotho", "Liberia", "Libya", "Liechtenstein", "Lithuania", "Luxembourg",
        "Macedonia", "Madagascar", "Malawi", "Malaysia", "Maldives", "Mali", "Malta", "Marshall Islands", "Mauritania", "Mauritius", "Mexico", "Micronesia", "Moldova", "Monaco", "Mongolia", "Morocco", "Mozambique", "Myanmar",
        "Namibia", "Nauru", "Nepa", "Netherlands", "New Zealand", "Nicaragua", "Niger", "Nigeria", "Norway",
        "Oman",
        "Pakistan", "Palau", "Panama", "Papua New Guinea", "Paraguay", "Peru", "Philippines", "Poland","Portugal",
        "Qatar",
        "Romania", "Russia", "Rwanda",
        "Saint Kitts and Nevis", "Saint Lucia", "Saint Vincent", "Samoa", "San Marino", "Sao Tome and Principe", "Saudi Arabia", "Senegal", "Serbia and Montenegro", "Seychelles", "Sierra Leone", "Singapore", "Slovakia", "Slovenia", "Solomon Islands", "Somalia", "South Africa", "Spain", "Sri Lanka", "Sudan", "Suriname", "Swaziland", "Sweden", "Switzerland", "Syria",
        "Taiwan", "Tajikistan", "Tanzania", "Thailand", "Togo", "Tonga", "Trinidad and Tobago", "Tunisia", "Turkey", "Turkmenistan", "Tuvalu",
        "Uganda", "Ukraine", "United Arab Emirates", "United Kingdom", "United States", "Uruguay", "Uzbekistan",
        "Vanuatu", "Vatican City", "Venezuela", "Vietnam",
        "Yemen",
        "Zambia", "Zimbabwe"
    );

    $country_select = '';
    if ($combo) {
        $country_select = '<select id="country" name="country">';
        $country_select .= '<option value="">--- '.get_lang('SelectOne').' ---</option>';
        foreach ($a_countries as $country) {
            $country_select .= '<option value="'.$country.'">'.$country.'</option>';
        }
        $country_select .= '</select>';
        return $country_select;
    }

    return $a_countries;
}

/**
 * Lockis settings that can't be changed in other portals 
 */
function locking_settings() {
    $access_url_locked_settings = api_get_locked_settings();
    $table = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
    foreach ($access_url_locked_settings as $setting) {
        $sql = "UPDATE $table SET access_url_locked = 1 WHERE variable  = '$setting'";
        Database::query($sql);
    }
}

function update_dir_and_files_permissions() {    
    $table = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);    
    $permissions_for_new_directories = isset($_SESSION['permissions_for_new_directories']) ? $_SESSION['permissions_for_new_directories'] : 0770;
    $permissions_for_new_files = isset($_SESSION['permissions_for_new_files']) ? $_SESSION['permissions_for_new_files'] : 0660;
    // use decoct() to store as string
    $sql = "UPDATE $table SET selected_value = '0".decoct($permissions_for_new_directories)."' WHERE variable  = 'permissions_for_new_directories'";
    Database::query($sql);
     
    $sql = "UPDATE $table SET selected_value = '0".decoct($permissions_for_new_files)."' WHERE variable  = 'permissions_for_new_files'";
    Database::query($sql);
    
    unset($_SESSION['permissions_for_new_directories']);
    unset($_SESSION['permissions_for_new_files']);
}

function compare_setting_values($current_value, $wanted_value) {
    $current_value_string = $current_value;
    $current_value = (float)$current_value;    
    $wanted_value = (float)$wanted_value;
    
    if ($current_value >= $wanted_value) {
        return Display::label($current_value_string, 'success');
    } else {
        return Display::label($current_value_string, 'important');
    }    
}


    
    

/* Executed only before create_course_tables() */
function drop_course_tables() {
    $list = CourseManager::get_course_tables();
    foreach ($list as $table) {
        $sql = "DROP TABLE IF EXISTS ".DB_COURSE_PREFIX.$table;
        Database::query($sql);
    }
}



/**
 * Creates all the necessary tables for a new course
 */
function create_course_tables($course_db_name = null) {
    global $_configuration;
    $charset_clause = ' DEFAULT CHARACTER SET utf8 DEFAULT COLLATE utf8_general_ci';
    $use_one_db = true;

    if ($use_one_db) {
    	$course_db_name = DB_COURSE_PREFIX;
    } else {
	    if (!$_configuration['single_database']) {
	        Database::query("CREATE DATABASE IF NOT EXISTS " . $course_db_name . "" . $charset_clause);
	    }
	    $course_db_name = $_configuration['table_prefix'].$course_db_name.$_configuration['db_glue'];
    }

    //@todo define the backticks inside those table names directly (instead of adding them afterwards)
    $tbl_course_homepage        = $course_db_name . 'tool';
    $TABLEINTROS                = $course_db_name . 'tool_intro';
    $TABLEGROUPS                = $course_db_name . 'group_info';
    $TABLEGROUPCATEGORIES       = $course_db_name . 'group_category';
    $TABLEGROUPUSER             = $course_db_name . 'group_rel_user';
    $TABLEGROUPTUTOR            = $course_db_name . 'group_rel_tutor';
    $TABLEITEMPROPERTY          = $course_db_name . 'item_property';
    $TABLETOOLUSERINFOCONTENT   = $course_db_name . 'userinfo_content';
    $TABLETOOLUSERINFODEF       = $course_db_name . 'userinfo_def';
    $TABLETOOLCOURSEDESC        = $course_db_name . 'course_description';
    $TABLETOOLAGENDA            = $course_db_name . 'calendar_event';
    $TABLETOOLAGENDAREPEAT      = $course_db_name . 'calendar_event_repeat';
    $TABLETOOLAGENDAREPEATNOT   = $course_db_name . 'calendar_event_repeat_not';
    $TABLETOOLAGENDAATTACHMENT  = $course_db_name . 'calendar_event_attachment';

    // Announcements
    $TABLETOOLANNOUNCEMENTS             = $course_db_name . 'announcement';
    $TABLETOOLANNOUNCEMENTSATTACHMENT   = $course_db_name . 'announcement_attachment';

    // Resourcelinker
    $TABLEADDEDRESOURCES        = $course_db_name . 'resource';

    // Student Publication
    $TABLETOOLWORKS             = $course_db_name . 'student_publication';
    $TABLETOOLWORKSASS          = $course_db_name . 'student_publication_assignment';

    // Document
    $TABLETOOLDOCUMENT          = $course_db_name . 'document';

    // Forum
    $TABLETOOLFORUMCATEGORY     = $course_db_name . 'forum_category';
    $TABLETOOLFORUM             = $course_db_name . 'forum_forum';
    $TABLETOOLFORUMTHREAD       = $course_db_name . 'forum_thread';
    $TABLETOOLFORUMPOST         = $course_db_name . 'forum_post';
    $TABLETOOLFORUMMAILCUE      = $course_db_name . 'forum_mailcue';
    $TABLETOOLFORUMATTACHMENT   = $course_db_name . 'forum_attachment';
    $TABLETOOLFORUMNOTIFICATION = $course_db_name . 'forum_notification';
    $TABLETOOLFORUMQUALIFY      = $course_db_name . 'forum_thread_qualify';
    $TABLETOOLFORUMQUALIFYLOG   = $course_db_name . 'forum_thread_qualify_log';

    // Link
    $TABLETOOLLINK              = $course_db_name . 'link';
    $TABLETOOLLINKCATEGORIES    = $course_db_name . 'link_category';

    $TABLETOOLONLINECONNECTED   = $course_db_name . 'online_connected';
    $TABLETOOLONLINELINK        = $course_db_name . 'online_link';

    // Chat
    $TABLETOOLCHATCONNECTED     = $course_db_name . 'chat_connected';

    // Quiz (a.k.a. exercises)
    $TABLEQUIZ                  = $course_db_name . 'quiz';
    $TABLEQUIZQUESTION          = $course_db_name . 'quiz_rel_question';
    $TABLEQUIZQUESTIONLIST      = $course_db_name . 'quiz_question';
    $TABLEQUIZANSWERSLIST       = $course_db_name . 'quiz_answer';
    $TABLEQUIZQUESTIONOPTION    = $course_db_name . 'quiz_question_option';
	$table_quiz_question_category    	 = $course_db_name . 'quiz_question_category';
	$table_quiz_question_rel_category    = $course_db_name . 'quiz_question_rel_category';

    // Dropbox
    $TABLETOOLDROPBOXPOST       = $course_db_name . 'dropbox_post';
    $TABLETOOLDROPBOXFILE       = $course_db_name . 'dropbox_file';
    $TABLETOOLDROPBOXPERSON     = $course_db_name . 'dropbox_person';
    $TABLETOOLDROPBOXCATEGORY   = $course_db_name . 'dropbox_category';
    $TABLETOOLDROPBOXFEEDBACK   = $course_db_name . 'dropbox_feedback';

    // New Learning path
    $TABLELP                    = $course_db_name . 'lp';
    $TABLELPITEM                = $course_db_name . 'lp_item';
    $TABLELPVIEW                = $course_db_name . 'lp_view';
    $TABLELPITEMVIEW            = $course_db_name . 'lp_item_view';
    $TABLELPIVINTERACTION       = $course_db_name . 'lp_iv_interaction';
    $TABLELPIVOBJECTIVE         = $course_db_name . 'lp_iv_objective';

    // Blogs
    $tbl_blogs                  = $course_db_name . 'blog';
    $tbl_blogs_comments         = $course_db_name . 'blog_comment';
    $tbl_blogs_posts            = $course_db_name . 'blog_post';
    $tbl_blogs_rating           = $course_db_name . 'blog_rating';
    $tbl_blogs_rel_user         = $course_db_name . 'blog_rel_user';
    $tbl_blogs_tasks            = $course_db_name . 'blog_task';
    $tbl_blogs_tasks_rel_user   = $course_db_name . 'blog_task_rel_user';
    $tbl_blogs_attachment       = $course_db_name . 'blog_attachment';

    //Blogs permissions
    $tbl_permission_group       = $course_db_name . 'permission_group';
    $tbl_permission_user        = $course_db_name . 'permission_user';
    $tbl_permission_task        = $course_db_name . 'permission_task';

    //Blog roles
    $tbl_role                   = $course_db_name . 'role';
    $tbl_role_group             = $course_db_name . 'role_group';
    $tbl_role_permissions       = $course_db_name . 'role_permissions';
    $tbl_role_user              = $course_db_name . 'role_user';

    //Survey variables for course homepage;
    $TABLESURVEY                = $course_db_name . 'survey';
    $TABLESURVEYQUESTION        = $course_db_name . 'survey_question';
    $TABLESURVEYQUESTIONOPTION  = $course_db_name . 'survey_question_option';
    $TABLESURVEYINVITATION      = $course_db_name . 'survey_invitation';
    $TABLESURVEYANSWER          = $course_db_name . 'survey_answer';
    $TABLESURVEYGROUP           = $course_db_name . 'survey_group';

    // Wiki
    $TABLETOOLWIKI              = $course_db_name . 'wiki';
    $TABLEWIKICONF              = $course_db_name . 'wiki_conf';
    $TABLEWIKIDISCUSS           = $course_db_name . 'wiki_discuss';
    $TABLEWIKIMAILCUE           = $course_db_name . 'wiki_mailcue';

    // Course settings
    $TABLESETTING               = $course_db_name . 'course_setting';

    // Glossary
    $TBL_GLOSSARY               = $course_db_name . 'glossary';

    // Notebook
    $TBL_NOTEBOOK               = $course_db_name . 'notebook';

    // Attendance
    $TBL_ATTENDANCE             = $course_db_name . 'attendance';
    $TBL_ATTENDANCE_SHEET       = $course_db_name . 'attendance_sheet';
    $TBL_ATTENDANCE_CALENDAR    = $course_db_name . 'attendance_calendar';
    $TBL_ATTENDANCE_RESULT      = $course_db_name . 'attendance_result';
    $TBL_ATTENDANCE_SHEET_LOG   = $course_db_name . 'attendance_sheet_log';

    // Thematic
    $TBL_THEMATIC               = $course_db_name . 'thematic';
    $TBL_THEMATIC_PLAN          = $course_db_name . 'thematic_plan';
    $TBL_THEMATIC_ADVANCE       = $course_db_name . 'thematic_advance';
    $TBL_METADATA               = $course_db_name . 'metadata';

    $add_to_all_tables = ' c_id INT NOT NULL, ';
    

    /*  Announcement tool	*/

    $sql = "
        CREATE TABLE `".$TABLETOOLANNOUNCEMENTS . "` (
        $add_to_all_tables
        id int unsigned NOT NULL auto_increment,
        title text,
        content mediumtext,
        end_date date default NULL,
        display_order mediumint NOT NULL default 0,
        email_sent tinyint default 0,
        session_id int default 0,
        PRIMARY KEY (c_id, id)
        )" . $charset_clause;
    Database::query($sql);

    $sql = "ALTER TABLE `".$TABLETOOLANNOUNCEMENTS . "` ADD INDEX ( session_id ) ";
    Database::query($sql);

    // Announcement Attachment
    $sql = "CREATE TABLE  `".$TABLETOOLANNOUNCEMENTSATTACHMENT."` (
			$add_to_all_tables
            id int NOT NULL auto_increment,
            path varchar(255) NOT NULL,
            comment text,
            size int NOT NULL default 0,
            announcement_id int NOT NULL,
            filename varchar(255) NOT NULL,
            PRIMARY KEY (c_id, id)
            )" . $charset_clause;
    Database::query($sql);

    /*
            Resources
    */

    $sql = "
        CREATE TABLE `".$TABLEADDEDRESOURCES . "` (
        $add_to_all_tables
        id int unsigned NOT NULL auto_increment,
        source_type varchar(50) default NULL,
        source_id int unsigned default NULL,
        resource_type varchar(50) default NULL,
        resource_id int unsigned default NULL,
        PRIMARY KEY (c_id, id)
        )" . $charset_clause;
    Database::query($sql);

    $sql = "
        CREATE TABLE `".$TABLETOOLUSERINFOCONTENT . "` (
        $add_to_all_tables
        id int unsigned NOT NULL auto_increment,
        user_id int unsigned NOT NULL,
        definition_id int unsigned NOT NULL,
        editor_ip varchar(39) default NULL,
        edition_time datetime default NULL,
        content text NOT NULL,
        PRIMARY KEY (c_id, id),
        KEY user_id (user_id)
        )" . $charset_clause;
    Database::query($sql);

    // Unused table. Temporarily ignored for tests.
    // Reused because of user/userInfo and user/userInfoLib scripts
    $sql = "
        CREATE TABLE `".$TABLETOOLUSERINFODEF . "` (
        $add_to_all_tables
        id int unsigned NOT NULL auto_increment,
        title varchar(80) NOT NULL default '',
        comment text,
        line_count tinyint unsigned NOT NULL default 5,
        rank tinyint unsigned NOT NULL default 0,
        PRIMARY KEY (c_id, id)
        )" . $charset_clause;
    Database::query($sql);

    /* Forum tool	*/

    // Forum Category
    $sql = "
        CREATE TABLE `".$TABLETOOLFORUMCATEGORY . "` (
		 $add_to_all_tables
         cat_id int NOT NULL auto_increment,
         cat_title varchar(255) NOT NULL default '',
         cat_comment text,
         cat_order int NOT NULL default 0,
         locked int NOT NULL default 0,
         session_id int unsigned NOT NULL default 0,
         PRIMARY KEY (c_id, cat_id)
        )" . $charset_clause;
    Database::query($sql);

    $sql = "ALTER TABLE `".$TABLETOOLFORUMCATEGORY . "` ADD INDEX ( session_id ) ";
    Database::query($sql);

    // Forum
    $sql = "
        CREATE TABLE `".$TABLETOOLFORUM . "` (
        $add_to_all_tables
         forum_id int NOT NULL auto_increment,
         forum_title varchar(255) NOT NULL default '',
         forum_comment text,
         forum_threads int default 0,
         forum_posts int default 0,
         forum_last_post int default 0,
         forum_category int default NULL,
         allow_anonymous int default NULL,
         allow_edit int default NULL,
         approval_direct_post varchar(20) default NULL,
         allow_attachments int default NULL,
         allow_new_threads int default NULL,
         default_view varchar(20) default NULL,
         forum_of_group varchar(20) default NULL,
         forum_group_public_private varchar(20) default 'public',
         forum_order int default NULL,
         locked int NOT NULL default 0,
         session_id int NOT NULL default 0,
         forum_image varchar(255) NOT NULL default '',
         start_time datetime NOT NULL default '0000-00-00 00:00:00',
         end_time datetime NOT NULL default '0000-00-00 00:00:00',
         PRIMARY KEY (c_id, forum_id)
        )" . $charset_clause;
    Database::query($sql);

    // Forum Threads
    $sql = "
        CREATE TABLE `".$TABLETOOLFORUMTHREAD . "` (
         $add_to_all_tables
         thread_id int NOT NULL auto_increment,
         thread_title varchar(255) default NULL,
         forum_id int default NULL,
         thread_replies int default 0,
         thread_poster_id int default NULL,
         thread_poster_name varchar(100) default '',
         thread_views int default 0,
         thread_last_post int default NULL,
         thread_date datetime default '0000-00-00 00:00:00',
         thread_sticky tinyint unsigned default 0,
         locked int NOT NULL default 0,
         session_id int unsigned default NULL,
         thread_title_qualify varchar(255) default '',
         thread_qualify_max float(6,2) UNSIGNED NOT NULL default 0,
         thread_close_date datetime default '0000-00-00 00:00:00',
         thread_weight float(6,2) UNSIGNED NOT NULL default 0,
         PRIMARY KEY (c_id, thread_id)
        )" . $charset_clause;
    Database::query($sql);

    $sql = "ALTER TABLE `".$TABLETOOLFORUMTHREAD . "` ADD INDEX idx_forum_thread_forum_id (forum_id)";
    Database::query($sql);

    // Forum Posts
    $sql = "
        CREATE TABLE `".$TABLETOOLFORUMPOST . "` (
         $add_to_all_tables
         post_id int NOT NULL auto_increment,
         post_title varchar(250) default NULL,
         post_text text,
         thread_id int default 0,
         forum_id int default 0,
         poster_id int default 0,
         poster_name varchar(100) default '',
         post_date datetime default '0000-00-00 00:00:00',
         post_notification tinyint default 0,
         post_parent_id int default 0,
         visible tinyint default 1,
         PRIMARY KEY (c_id, post_id),
         KEY poster_id (poster_id),
         KEY forum_id (forum_id)
        )" . $charset_clause;
    Database::query($sql);

    $sql = "ALTER TABLE `".$TABLETOOLFORUMPOST . "` ADD INDEX idx_forum_post_thread_id (thread_id)";
    Database::query($sql);

    $sql = "ALTER TABLE `".$TABLETOOLFORUMPOST . "` ADD INDEX idx_forum_post_visible (visible)";
    Database::query($sql);    
    
    // Forum Mailcue
    $sql = "
        CREATE TABLE `".$TABLETOOLFORUMMAILCUE."` (
         $add_to_all_tables
         id int NOT NULL auto_increment,
         user_id int default NULL, 
         thread_id int default NULL, 
         post_id int default NULL,
         PRIMARY KEY (id, c_id, thread_id, user_id, post_id )
        )" . $charset_clause;
    Database::query($sql);

    // Forum Attachment
    $sql = "CREATE TABLE  `".$TABLETOOLFORUMATTACHMENT."` (
    		  $add_to_all_tables
              id int NOT NULL auto_increment,
              path varchar(255) NOT NULL,
              comment text,
              size int NOT NULL default 0,
              post_id int NOT NULL,
              filename varchar(255) NOT NULL,
              PRIMARY KEY (c_id, id)
            )" . $charset_clause;
    Database::query($sql);

    // Forum notification
    $sql = "CREATE TABLE  `".$TABLETOOLFORUMNOTIFICATION."` (
    		  $add_to_all_tables
              id int NOT NULL auto_increment,
              user_id int,
              forum_id int,
              thread_id int,
              post_id int,
              KEY user_id (user_id),
              KEY forum_id (forum_id),
              PRIMARY KEY  (id, c_id, user_id, forum_id, thread_id, post_id )
            )" . $charset_clause;
    Database::query($sql);

    // Forum thread qualify :Add table forum_thread_qualify
    $sql = "CREATE TABLE  `".$TABLETOOLFORUMQUALIFY."` (
    		$add_to_all_tables
            id int unsigned AUTO_INCREMENT,
            user_id int unsigned NOT NULL,
            thread_id int NOT NULL,
            qualify float(6,2) NOT NULL default 0,
            qualify_user_id int  default NULL,
            qualify_time datetime default '0000-00-00 00:00:00',
            session_id int  default NULL,
            PRIMARY KEY (c_id, id)
            )" . $charset_clause;
    Database::query($sql);

    $sql = "ALTER TABLE `".$TABLETOOLFORUMQUALIFY . "` ADD INDEX (user_id, thread_id)";
    Database::query($sql);

    //Forum thread qualify: Add table forum_thread_qualify_historical
    $sql = "CREATE TABLE  `".$TABLETOOLFORUMQUALIFYLOG."` (
    		$add_to_all_tables
            id int unsigned AUTO_INCREMENT,
            user_id int unsigned NOT NULL,
            thread_id int NOT NULL,
            qualify float(6,2) NOT NULL default 0,
            qualify_user_id int default NULL,
            qualify_time datetime default '0000-00-00 00:00:00',
            session_id int default NULL,
            PRIMARY KEY (c_id, id)
            )" . $charset_clause;
    Database::query($sql);

    $sql = "ALTER TABLE `".$TABLETOOLFORUMQUALIFYLOG. "` ADD INDEX (user_id, thread_id)";
    Database::query($sql);

    /*
     * Exercise tool
    */

    // Exercise tool - Tests/exercises
    $sql = "CREATE TABLE `".$TABLEQUIZ . "` (
        $add_to_all_tables
        id int unsigned NOT NULL auto_increment,
        title varchar(255) NOT NULL,
        description text default NULL,
        sound varchar(255) default NULL,
        type tinyint unsigned NOT NULL default 1,
        random int NOT NULL default 0,
        random_answers tinyint unsigned NOT NULL default 0,
        active tinyint NOT NULL default 0,
        results_disabled INT UNSIGNED NOT NULL DEFAULT 0,
        access_condition TEXT DEFAULT NULL,
        max_attempt int NOT NULL default 0,
        start_time datetime NOT NULL default '0000-00-00 00:00:00',
        end_time datetime NOT NULL default '0000-00-00 00:00:00',
        feedback_type int NOT NULL default 0,
        expired_time int NOT NULL default '0',
        session_id int default 0,
        propagate_neg INT NOT NULL DEFAULT 0,
        review_answers INT NOT NULL DEFAULT 0,
        random_by_category INT NOT NULL DEFAULT 0,
        text_when_finished TEXT default NULL,
        display_category_name INT NOT NULL DEFAULT 1,
        pass_percentage INT DEFAULT NULL,
        PRIMARY KEY (c_id, id)
        )" . $charset_clause;
    Database::query($sql);

    $sql = "ALTER TABLE `".$TABLEQUIZ . "` ADD INDEX ( session_id ) ";
    Database::query($sql);

    // Exercise tool - questions
    $sql = "
        CREATE TABLE `".$TABLEQUIZQUESTIONLIST . "` (
        $add_to_all_tables
        id int unsigned NOT NULL auto_increment,
        question TEXT NOT NULL,
        description text default NULL,
        ponderation float(6,2) NOT NULL default 0,
        position mediumint unsigned NOT NULL default 1,
        type    tinyint unsigned NOT NULL default 2,
        picture varchar(50) default NULL,
        level   int unsigned NOT NULL default 0,
        extra   varchar(255) default NULL,
        question_code char(10) default '',
        PRIMARY KEY (c_id, id)
        )" . $charset_clause;
    Database::query($sql);

    $sql = "ALTER TABLE `".$TABLEQUIZQUESTIONLIST . "` ADD INDEX (position)";
    Database::query($sql);

    // Exercise tool - answers
    $sql = "
        CREATE TABLE `".$TABLEQUIZANSWERSLIST . "` (
        $add_to_all_tables
        id int unsigned NOT NULL,
        id_auto int NOT NULL AUTO_INCREMENT,
        question_id int unsigned NOT NULL,
        answer text NOT NULL,
        correct mediumint unsigned default NULL,
        comment text default NULL,
        ponderation float(6,2) NOT NULL default 0,
        position mediumint unsigned NOT NULL default 1,
        hotspot_coordinates text,
        hotspot_type enum('square','circle','poly','delineation','oar') default NULL,
        destination text NOT NULL,        
        answer_code char(10) default '',
        PRIMARY KEY (c_id, id_auto)  
        )" . $charset_clause;
    Database::query($sql);

    // Exercise tool - answer options
    $sql = "
        CREATE TABLE `".$TABLEQUIZQUESTIONOPTION . "` (
        $add_to_all_tables
        id          int NOT NULL auto_increment,
        question_id int NOT NULL,
        name        varchar(255),
        position    int unsigned NOT NULL,
        PRIMARY KEY (c_id, id)
        )" . $charset_clause;
    Database::query($sql);


    // Exercise tool - Test/question relations
    $sql = "
        CREATE TABLE `".$TABLEQUIZQUESTION . "` (
        $add_to_all_tables
        question_id int unsigned NOT NULL,
        exercice_id int unsigned NOT NULL,
        question_order int unsigned NOT NULL default 1,
        PRIMARY KEY (c_id, question_id,exercice_id)
        )" . $charset_clause;
    Database::query($sql);



    $sql = "CREATE TABLE `".$table_quiz_question_category . "` (
	  $add_to_all_tables
	  id int NOT NULL AUTO_INCREMENT,
	  title varchar(255) NOT NULL,
	  description text NOT NULL,
	  PRIMARY KEY (c_id,id)
	)" . $charset_clause;
    Database::query($sql);


	$sql = "CREATE TABLE `".$table_quiz_question_rel_category . "` (
	  $add_to_all_tables
	  question_id int NOT NULL,
	  category_id int NOT NULL,
	  PRIMARY KEY (c_id,question_id)
    )" . $charset_clause;
    Database::query($sql);


    /*        Course description	*/

    $sql = "
        CREATE TABLE `".$TABLETOOLCOURSEDESC . "` (
        $add_to_all_tables
        id int UNSIGNED NOT NULL auto_increment,
        title VARCHAR(255),
        content TEXT,
        session_id int default 0,
        description_type tinyint unsigned NOT NULL default 0,
        progress INT NOT NULL default 0,
        PRIMARY KEY (c_id, id)
        )" . $charset_clause;
    Database::query($sql);

    $sql = "ALTER TABLE `".$TABLETOOLCOURSEDESC . "` ADD INDEX ( session_id ) ";
    Database::query($sql);

    /*  Course homepage tool list    */

    $sql = "
        CREATE TABLE `" . $tbl_course_homepage . "` (
        $add_to_all_tables
        id int unsigned NOT NULL auto_increment,
        name varchar(255) NOT NULL,
        link varchar(255) NOT NULL,
        image varchar(255) default NULL,
        visibility tinyint unsigned default 0,
        admin varchar(255) default NULL,
        address varchar(255) default NULL,
        added_tool tinyint unsigned default 1,
        target enum('_self','_blank') NOT NULL default '_self',
        category varchar(20) not null default 'authoring',
        session_id int default 0,
        PRIMARY KEY (c_id, id)
        )" . $charset_clause;
    Database::query($sql);
    $sql = "ALTER TABLE `".$tbl_course_homepage . "` ADD INDEX ( session_id ) ";
    Database::query($sql);

    /*        Agenda tool	   */

    $sql = "
        CREATE TABLE `".$TABLETOOLAGENDA . "` (
        $add_to_all_tables
        id int unsigned NOT NULL auto_increment,
        title varchar(255) NOT NULL,
        content text,
        start_date datetime NOT NULL default '0000-00-00 00:00:00',
        end_date datetime NOT NULL default '0000-00-00 00:00:00',
        parent_event_id INT NULL,
        session_id int unsigned NOT NULL default 0,
        all_day INT NOT NULL DEFAULT 0,
        PRIMARY KEY (c_id, id)
        )" . $charset_clause;
    Database::query($sql);

    $sql = "ALTER TABLE `".$TABLETOOLAGENDA . "` ADD INDEX ( session_id ) ;";
    Database::query($sql);

    $sql = "
        CREATE TABLE `".$TABLETOOLAGENDAREPEAT. "` (
        $add_to_all_tables
        cal_id INT DEFAULT 0 NOT NULL,
        cal_type VARCHAR(20),
        cal_end INT,
        cal_frequency INT DEFAULT 1,
        cal_days CHAR(7),
        PRIMARY KEY (c_id, cal_id)
        )" . $charset_clause;
    Database::query($sql);

    $sql = "
        CREATE TABLE `".$TABLETOOLAGENDAREPEATNOT."` (
        $add_to_all_tables
        cal_id INT NOT NULL,
        cal_date INT NOT NULL,
        PRIMARY KEY (c_id, cal_id, cal_date )
        )" . $charset_clause;
    Database::query($sql);

    // Agenda Attachment
    $sql = "CREATE TABLE  `".$TABLETOOLAGENDAATTACHMENT."` (
    			$add_to_all_tables
              id int NOT NULL auto_increment,
              path varchar(255) NOT NULL,
              comment text,
              size int NOT NULL default 0,
              agenda_id int NOT NULL,
              filename varchar(255) NOT NULL,
              PRIMARY KEY (c_id, id)
            )" . $charset_clause;
    Database::query($sql);

    /*
        Document tool
    */

    $sql = "
        CREATE TABLE `".$TABLETOOLDOCUMENT . "` (
        	$add_to_all_tables
            id int unsigned NOT NULL auto_increment,
            path varchar(255) NOT NULL default '',
            comment text,
            title varchar(255) default NULL,
            filetype set('file','folder') NOT NULL default 'file',
            size int NOT NULL default 0,
            readonly TINYINT UNSIGNED NOT NULL,
            session_id int UNSIGNED NOT NULL default 0,
            PRIMARY KEY (c_id, id)
        )" . $charset_clause;
    Database::query($sql);

    /*
        Student publications
    */
    $sql = "
        CREATE TABLE `".$TABLETOOLWORKS . "` (
        $add_to_all_tables
        id int unsigned NOT NULL auto_increment,
        url varchar(255) default NULL,
        title varchar(255) default NULL,
        description text default NULL,
        author varchar(255) default NULL,
        active tinyint default NULL,
        accepted tinyint default 0,
        post_group_id int DEFAULT 0 NOT NULL,
        sent_date datetime NOT NULL default '0000-00-00 00:00:00',
        filetype set('file','folder') NOT NULL default 'file',
        has_properties int UNSIGNED NOT NULL DEFAULT 0,
        view_properties tinyint NULL,
        qualification float(6,2) UNSIGNED NOT NULL DEFAULT 0,
        date_of_qualification datetime NOT NULL default '0000-00-00 00:00:00',
        parent_id INT UNSIGNED NOT NULL DEFAULT 0,
        qualificator_id INT UNSIGNED NOT NULL DEFAULT 0,
        weight float(6,2) UNSIGNED NOT NULL default 0,
        session_id INT UNSIGNED NOT NULL default 0,
        user_id INTEGER  NOT NULL,
		allow_text_assignment INTEGER NOT NULL DEFAULT 0,
		contains_file INTEGER NOT NULL DEFAULT 0,
        PRIMARY KEY (c_id, id)
        )" . $charset_clause;
    Database::query($sql);

    $sql = "
        CREATE TABLE `".$TABLETOOLWORKSASS."` (
        $add_to_all_tables
        id int NOT NULL auto_increment,
        expires_on datetime NOT NULL default '0000-00-00 00:00:00',
        ends_on datetime NOT NULL default '0000-00-00 00:00:00',
        add_to_calendar tinyint NOT NULL,
        enable_qualification tinyint NOT NULL,
        publication_id int NOT NULL,
        PRIMARY KEY (c_id, id)
        )" . $charset_clause;
    Database::query($sql);

    $sql = "ALTER TABLE `".$TABLETOOLWORKS . "` ADD INDEX ( session_id )" ;
    Database::query($sql);

    /*
            Links tool
    */

    $sql = "
        CREATE TABLE `".$TABLETOOLLINK . "` (
        $add_to_all_tables
        id int unsigned NOT NULL auto_increment,
        url TEXT NOT NULL,
        title varchar(150) default NULL,
        description text,
        category_id int unsigned default NULL,
        display_order int unsigned NOT NULL default 0,
        on_homepage enum('0','1') NOT NULL default '0',
        target char(10) default '_self',
        session_id int default 0,
        PRIMARY KEY (c_id, id)
        )" . $charset_clause;
    Database::query($sql);

    $sql = "ALTER TABLE `".$TABLETOOLLINK . "` ADD INDEX ( session_id ) ";
    Database::query($sql);

    $sql = "
        CREATE TABLE `".$TABLETOOLLINKCATEGORIES . "` (
        $add_to_all_tables
        id int unsigned NOT NULL auto_increment,
        category_title varchar(255) NOT NULL,
        description text,
        display_order mediumint unsigned NOT NULL default 0,
        session_id int default 0,
        PRIMARY KEY (c_id, id)
        )" . $charset_clause;
    Database::query($sql);

    $sql = "ALTER TABLE `".$TABLETOOLLINKCATEGORIES . "` ADD INDEX ( session_id ) ";
    Database::query($sql);

    /* Wiki   */

    $sql = "CREATE TABLE `".$TABLETOOLWIKI . "` (
    	$add_to_all_tables
        id int NOT NULL auto_increment,
        page_id int NOT NULL default 0,
        reflink varchar(255) NOT NULL default 'index',
        title varchar(255) NOT NULL,
        content mediumtext NOT NULL,
        user_id int NOT NULL default 0,
        group_id int DEFAULT NULL,
        dtime datetime NOT NULL default '0000-00-00 00:00:00',
        addlock int NOT NULL default 1,
        editlock int NOT NULL default 0,
        visibility int NOT NULL default 1,
        addlock_disc int NOT NULL default 1,
        visibility_disc int NOT NULL default 1,
        ratinglock_disc int NOT NULL default 1,
        assignment int NOT NULL default 0,
        comment text NOT NULL,
        progress text NOT NULL,
        score int NULL default 0,
        version int default NULL,
        is_editing int NOT NULL default 0,
        time_edit datetime NOT NULL default '0000-00-00 00:00:00',
        hits int default 0,
        linksto text NOT NULL,
        tag text NOT NULL,
        user_ip varchar(39) NOT NULL,
        session_id int default 0,
        PRIMARY KEY (c_id, id),
        KEY reflink (reflink),
        KEY group_id (group_id),
        KEY page_id (page_id),
        KEY session_id (session_id)
        )" . $charset_clause;
    Database::query($sql);

    $sql = "CREATE TABLE `".$TABLEWIKICONF . "` (
    	$add_to_all_tables
        page_id int NOT NULL default 0,
        task text NOT NULL,
        feedback1 text NOT NULL,
        feedback2 text NOT NULL,
        feedback3 text NOT NULL,
        fprogress1 varchar(3) NOT NULL,
        fprogress2 varchar(3) NOT NULL,
        fprogress3 varchar(3) NOT NULL,
        max_size int default NULL,
        max_text int default NULL,
        max_version int default NULL,
        startdate_assig datetime NOT NULL default '0000-00-00 00:00:00',
        enddate_assig datetime  NOT NULL default '0000-00-00 00:00:00',
        delayedsubmit int NOT NULL default 0,
        KEY page_id (page_id),
        PRIMARY KEY  ( c_id, page_id )
        )" . $charset_clause;
    Database::query($sql);

    $sql = "CREATE TABLE `".$TABLEWIKIDISCUSS . "` (
    	$add_to_all_tables
        id int NOT NULL auto_increment,
        publication_id int NOT NULL default 0,
        userc_id int NOT NULL default 0,
        comment text NOT NULL,
        p_score varchar(255) default NULL,
        dtime datetime NOT NULL default '0000-00-00 00:00:00',
        PRIMARY KEY (c_id, id)
        )" . $charset_clause;
    Database::query($sql);

    $sql = "CREATE TABLE `".$TABLEWIKIMAILCUE . "` (
    	$add_to_all_tables
        id int NOT NULL,
        user_id int NOT NULL,
        type text NOT NULL,
        group_id int DEFAULT NULL,
        session_id int default 0,
        KEY (c_id, id),
        PRIMARY KEY  ( c_id, id, user_id )
        )" . $charset_clause;
    Database::query($sql);

    /*
        Online
    */

    $sql = "
        CREATE TABLE `".$TABLETOOLONLINECONNECTED . "` (
		$add_to_all_tables
        user_id int unsigned NOT NULL,
        last_connection datetime NOT NULL default '0000-00-00 00:00:00',
        PRIMARY KEY (c_id, user_id)
        )" . $charset_clause;
    Database::query($sql);

    $sql = "
        CREATE TABLE `".$TABLETOOLONLINELINK . "` (
        $add_to_all_tables
        id int unsigned NOT NULL auto_increment,
        name char(50) NOT NULL default '',
        url char(100) NOT NULL,
        PRIMARY KEY (c_id, id)
        )" . $charset_clause;
    Database::query($sql);

    $sql = "
        CREATE TABLE `".$TABLETOOLCHATCONNECTED . "` (
        $add_to_all_tables
        id int unsigned NOT NULL auto_increment,
        user_id int unsigned NOT NULL default '0',
        last_connection datetime NOT NULL default '0000-00-00 00:00:00',
        session_id  INT NOT NULL default 0,
        to_group_id INT NOT NULL default 0,
        PRIMARY KEY  (c_id, id, user_id, last_connection)
        )" . $charset_clause;
    Database::query($sql);

    $sql = "ALTER TABLE `".$TABLETOOLCHATCONNECTED . "` ADD INDEX char_connected_index(user_id, session_id, to_group_id) ";
    Database::query($sql);

    /*
        Groups tool
    */

    Database::query("CREATE TABLE `".$TABLEGROUPS . "` (
    	$add_to_all_tables
        id int unsigned NOT NULL auto_increment,
        name varchar(100) default NULL,
        category_id int unsigned NOT NULL default 0,
        description text,
        max_student int unsigned NOT NULL default 8,
        doc_state tinyint unsigned NOT NULL default 1,
        calendar_state tinyint unsigned NOT NULL default 0,
        work_state tinyint unsigned NOT NULL default 0,
        announcements_state tinyint unsigned NOT NULL default 0,
        forum_state tinyint unsigned NOT NULL default 0,
        wiki_state tinyint unsigned NOT NULL default 1,
        chat_state tinyint unsigned NOT NULL default 1,
        secret_directory varchar(255) default NULL,
        self_registration_allowed tinyint unsigned NOT NULL default '0',
        self_unregistration_allowed tinyint unsigned NOT NULL default '0',
        session_id int unsigned NOT NULL default 0,
        PRIMARY KEY (c_id, id)
        )" . $charset_clause);

    Database::query("ALTER TABLE `".$TABLEGROUPS . "` ADD INDEX ( session_id )");

    Database::query("CREATE TABLE `".$TABLEGROUPCATEGORIES . "` (
    	$add_to_all_tables
        id int unsigned NOT NULL auto_increment,
        title varchar(255) NOT NULL default '',
        description text NOT NULL,
        doc_state tinyint unsigned NOT NULL default 1,
        calendar_state tinyint unsigned NOT NULL default 1,
        work_state tinyint unsigned NOT NULL default 1,
        announcements_state tinyint unsigned NOT NULL default 1,
        forum_state tinyint unsigned NOT NULL default 1,
        wiki_state tinyint unsigned NOT NULL default 1,
        chat_state tinyint unsigned NOT NULL default 1,
        max_student int unsigned NOT NULL default 8,
        self_reg_allowed tinyint unsigned NOT NULL default 0,
        self_unreg_allowed tinyint unsigned NOT NULL default 0,
        groups_per_user int unsigned NOT NULL default 0,
        display_order int unsigned NOT NULL default 0,
        PRIMARY KEY (c_id, id)
        )" . $charset_clause);

    Database::query("CREATE TABLE `".$TABLEGROUPUSER . "` (
    	$add_to_all_tables
        id int unsigned NOT NULL auto_increment,
        user_id int unsigned NOT NULL,
        group_id int unsigned NOT NULL default 0,
        status int NOT NULL default 0,
        role char(50) NOT NULL,
        PRIMARY KEY (c_id, id)
        )" . $charset_clause);

    Database::query("CREATE TABLE `".$TABLEGROUPTUTOR . "` (
    	$add_to_all_tables
        id int NOT NULL auto_increment,
        user_id int NOT NULL,
        group_id int NOT NULL default 0,
        PRIMARY KEY (c_id, id)
        )" . $charset_clause);

    Database::query("CREATE TABLE `".$TABLEITEMPROPERTY . "` (
    	$add_to_all_tables
        id int NOT NULL auto_increment,
        tool varchar(100) NOT NULL default '',
        insert_user_id int unsigned NOT NULL default '0',
        insert_date datetime NOT NULL default '0000-00-00 00:00:00',
        lastedit_date datetime NOT NULL default '0000-00-00 00:00:00',
        ref int NOT NULL default '0',
        lastedit_type varchar(100) NOT NULL default '',
        lastedit_user_id int unsigned NOT NULL default '0',
        to_group_id int unsigned default NULL,
        to_user_id int unsigned default NULL,
        visibility tinyint NOT NULL default '1',
        start_visible datetime NOT NULL default '0000-00-00 00:00:00',
        end_visible datetime NOT NULL default '0000-00-00 00:00:00',
        id_session INT NOT NULL DEFAULT 0,
        PRIMARY KEY (c_id, id)
        )" . $charset_clause);

    Database::query("ALTER TABLE `$TABLEITEMPROPERTY` ADD INDEX idx_item_property_toolref (tool, ref)");
    Database::query("ALTER TABLE `$TABLEITEMPROPERTY` ADD INDEX idx_itemprop_id_tool (c_id, tool(8))");

    /*           Tool introductions    */
    Database::query("
        CREATE TABLE `".$TABLEINTROS . "` (
        $add_to_all_tables
        id varchar(50) NOT NULL,
        intro_text MEDIUMTEXT NOT NULL,
        session_id INT  NOT NULL DEFAULT 0,
        PRIMARY KEY (c_id, id, session_id)
        )" . $charset_clause);

    /* Dropbox tool */

    Database::query("
        CREATE TABLE `".$TABLETOOLDROPBOXFILE . "` (
        $add_to_all_tables
        id int unsigned NOT NULL auto_increment,
        uploader_id int unsigned NOT NULL default 0,
        filename varchar(250) NOT NULL default '',
        filesize int unsigned NOT NULL,
        title varchar(250) default '',
        description varchar(250) default '',
        author varchar(250) default '',
        upload_date datetime NOT NULL default '0000-00-00 00:00:00',
        last_upload_date datetime NOT NULL default '0000-00-00 00:00:00',
        cat_id int NOT NULL default 0,
        session_id int UNSIGNED NOT NULL,
        PRIMARY KEY (c_id, id),
        UNIQUE KEY UN_filename (filename)
        )" . $charset_clause);

    Database::query("ALTER TABLE `$TABLETOOLDROPBOXFILE` ADD INDEX ( session_id )");

    Database::query("
        CREATE TABLE `".$TABLETOOLDROPBOXPOST . "` (
        $add_to_all_tables
        file_id int unsigned NOT NULL,
        dest_user_id int unsigned NOT NULL default 0,
        feedback_date datetime NOT NULL default '0000-00-00 00:00:00',
        feedback text default '',
        cat_id int NOT NULL default 0,
        session_id int UNSIGNED NOT NULL,
        PRIMARY KEY (c_id, file_id, dest_user_id)
        )" . $charset_clause);

    Database::query("ALTER TABLE `$TABLETOOLDROPBOXPOST` ADD INDEX ( session_id )");

    Database::query("
        CREATE TABLE `".$TABLETOOLDROPBOXPERSON . "` (
        $add_to_all_tables
        file_id int unsigned NOT NULL,
        user_id int unsigned NOT NULL default 0,
        PRIMARY KEY (c_id, file_id, user_id)
        )" . $charset_clause);

    $sql = "CREATE TABLE `".$TABLETOOLDROPBOXCATEGORY."` (
    		  $add_to_all_tables
              cat_id int NOT NULL auto_increment,
              cat_name text NOT NULL,
              received tinyint unsigned NOT NULL default 0,
              sent tinyint unsigned NOT NULL default 0,
              user_id int NOT NULL default 0,
              session_id int NOT NULL default 0,
              PRIMARY KEY  (c_id, cat_id)
              )" . $charset_clause;
    Database::query($sql);

    $sql = "ALTER TABLE `".$TABLETOOLDROPBOXCATEGORY . "` ADD INDEX ( session_id ) ";
    Database::query($sql);

    $sql = "CREATE TABLE `".$TABLETOOLDROPBOXFEEDBACK."` (
    			$add_to_all_tables
              feedback_id int NOT NULL auto_increment,
              file_id int NOT NULL default 0,
              author_user_id int NOT NULL default 0,
              feedback text NOT NULL,
              feedback_date datetime NOT NULL default '0000-00-00 00:00:00',
              PRIMARY KEY  (c_id, feedback_id),
              KEY file_id (file_id),
              KEY author_user_id (author_user_id)
              )" . $charset_clause;
    Database::query($sql);

    /*
        New learning path tool
    */

    $sql = "CREATE TABLE IF NOT EXISTS `$TABLELP` (
    	$add_to_all_tables
    	" .
        "id             int unsigned        auto_increment," .  // unique ID, generated by MySQL
        "lp_type        int unsigned   not null," .                    // lp_types can be found in the main database's lp_type table
        "name           varchar(255)        not null," .                    // name is the text name of the learning path (e.g. Word 2000)
        "ref            tinytext            null," .                        // ref for SCORM elements is the SCORM ID in imsmanifest. For other learnpath types, just ignore
        "description    text                null,".                         // textual description
        "path           text                not null," .                    // path, starting at the platforms root (so all paths should start with 'courses/...' for now)
        "force_commit   tinyint unsigned    not null default 0, " .         // stores the default behaviour regarding SCORM information
        "default_view_mod   char(32)        not null default 'embedded'," . // stores the default view mode (embedded or fullscreen)
        "default_encoding   char(32)        not null default 'UTF-8', " .   // stores the encoding detected at learning path reading
        "display_order  int unsigned        not null default 0," .          // order of learnpaths display in the learnpaths list - not really important
        "content_maker  tinytext            not null default ''," .         // the content make for this course (ENI, Articulate, ...)
        "content_local  varchar(32)         not null default 'local'," .    // content localisation ('local' or 'distant')
        "content_license    text            not null default ''," .         // content license
        "prevent_reinit tinyint unsigned    not null default 1," .          // stores the default behaviour regarding items re-initialisation when viewed a second time after success
        "js_lib         tinytext            not null default ''," .         // the JavaScript library to load for this lp
        "debug          tinyint unsigned    not null default 0," .          // stores the default behaviour regarding items re-initialisation when viewed a second time after success
        "theme          varchar(255)        not null default '', " .        // stores the theme of the LP
        "preview_image  varchar(255)        not null default '', " .        // stores the theme of the LP
        "author         varchar(255)        not null default '', " .        // stores the theme of the LP
        "session_id     int unsigned        not null default 0, " .         // the session_id
		"prerequisite  	int	unsigned 		not null default 0," .			// pre requisite for next lp
		"hide_toc_frame tinyint 			NOT NULL DEFAULT 0, ".
        "seriousgame_mode tinyint 			NOT NULL DEFAULT 0, ".
        "use_max_score  int unsigned        not null default 1, " .
        "autolunch      int unsigned        not null default 0, " .          // auto lunch LP
        "created_on     DATETIME 			NOT NULL DEFAULT '0000-00-00 00:00:00', " .
        "modified_on    DATETIME 			NOT NULL DEFAULT '0000-00-00 00:00:00', " .
        "publicated_on  DATETIME 			NOT NULL DEFAULT '0000-00-00 00:00:00', " .
        "expired_on     DATETIME 			NOT NULL DEFAULT '0000-00-00 00:00:00',
    	 PRIMARY KEY  (c_id, id)
        )" . $charset_clause;

    Database::query($sql);

    $sql = "CREATE TABLE IF NOT EXISTS `$TABLELPVIEW` (
    	$add_to_all_tables" .
        "id             int unsigned        auto_increment," .  // unique ID from MySQL
        "lp_id          int unsigned        not null," .                    // learnpath ID from 'lp'
        "user_id        int unsigned        not null," .                    // user ID from main.user
        "view_count     int unsigned   not null default 0," .          // integer counting the amount of times this learning path has been attempted
        "last_item      int unsigned        not null default 0," .          // last item seen in this view
        "progress       int unsigned        default 0," .
        "session_id     int                 not null default 0,
         PRIMARY KEY  (c_id, id)
    	)" . $charset_clause; // lp's progress for this user

    Database::query($sql);

    $sql = "ALTER TABLE `$TABLELPVIEW` ADD INDEX (lp_id) ";
    Database::query($sql);

    $sql = "ALTER TABLE `$TABLELPVIEW` ADD INDEX (user_id) ";
    Database::query($sql);

    $sql = "ALTER TABLE `$TABLELPVIEW` ADD INDEX (session_id) ";
    Database::query($sql);

    $sql = "CREATE TABLE IF NOT EXISTS `$TABLELPITEM` (
    	$add_to_all_tables
    	" .
        "id              int unsigned       auto_increment," .  // unique ID from MySQL
        "lp_id          int unsigned        not null," .                    // lp_id from 'lp'
        "item_type      char(32)            not null default 'dokeos_document'," .  // can be dokeos_document, dokeos_chapter or scorm_asset, scorm_sco, scorm_chapter
        "ref            tinytext            not null default ''," .         // the ID given to this item in the imsmanifest file
        "title          varchar(511)        not null," .                    // the title/name of this item (to display in the T.O.C.)
        "description    varchar(511)        not null default ''," .         // the description of this item - deprecated
        "path           text                not null," .                    // the path to that item, starting at 'courses/...' level
        "min_score      float unsigned      not null default 0," .          // min score allowed
        "max_score      float unsigned      default 100," .                 // max score allowed
        "mastery_score  float unsigned      null," .                        // minimum score to pass the test
        "parent_item_id     int unsigned    not null default 0," .          // the item one level higher
        "previous_item_id   int unsigned    not null default 0," .          // the item before this one in the sequential learning order (MySQL id)
        "next_item_id       int unsigned    not null default 0," .          // the item after this one in the sequential learning order (MySQL id)
        "display_order      int unsigned    not null default 0," .          // this is needed for ordering items under the same parent (previous_item_id doesn't give correct order after reordering)
        "prerequisite   text                null default null," .           // prerequisites in AICC scripting language as defined in the SCORM norm (allow logical operators)
        "parameters     text                null," .                        // prerequisites in AICC scripting language as defined in the SCORM norm (allow logical operators)
        "launch_data    text                not null default ''," .         // data from imsmanifest <item>
        "max_time_allowed   char(13)        NULL default ''," .             // data from imsmanifest <adlcp:maxtimeallowed>
        "terms          TEXT                NULL," .                        // contains the indexing tags (search engine)
        "search_did     INT                 NULL,".                         // contains the internal search-engine id of this element
        "audio          VARCHAR(250),
        PRIMARY KEY  (c_id, id)

    	)" . $charset_clause;                   // contains the audio file that goes with the learning path step

    Database::query($sql);

    $sql = "ALTER TABLE `$TABLELPITEM` ADD INDEX (lp_id)";
    Database::query($sql);
    
    $sql = "ALTER TABLE $TABLELPITEM ADD INDEX idx_c_lp_item_cid_lp_id (c_id, lp_id)";
    Database::query($sql);
    
    $sql = "CREATE TABLE IF NOT EXISTS `$TABLELPITEMVIEW` (
    	$add_to_all_tables
    	" .
        "id             bigint unsigned auto_increment," .      // unique ID
        "lp_item_id     int unsigned    not null," .                        // item ID (MySQL id)
        "lp_view_id     int unsigned    not null," .                        // learning path view id (attempt)
        "view_count     int unsigned    not null default 0," .              // how many times this item has been viewed in the current attempt (generally 0 or 1)
        "start_time     int unsigned    not null," .                        // when did the user open it?
        "total_time     int unsigned    not null default 0," .              // after how many seconds did he close it?
        "score          float unsigned  not null default 0," .              // score returned by SCORM or other techs
        "status         char(32)        not null default 'not attempted'," .    // status for this item (SCORM)
		"suspend_data	longtext null default ''," .
        "lesson_location    text        null default ''," .
        "core_exit      varchar(32)     not null default 'none'," .
        "max_score      varchar(8)      default '',
        PRIMARY KEY  (c_id, id)
        )" . $charset_clause;

    Database::query($sql);

    $sql = "ALTER TABLE `$TABLELPITEMVIEW` ADD INDEX (lp_item_id) ";
    Database::query($sql);

    $sql = "ALTER TABLE `$TABLELPITEMVIEW` ADD INDEX (lp_view_id) ";
    Database::query($sql);
    
    $sql = "ALTER TABLE $TABLELPITEMVIEW ADD INDEX idx_c_lp_item_view_cid_lp_view_id_lp_item_id (c_id, lp_view_id, lp_item_id) ";
    Database::query($sql);
    
    
    $sql = "CREATE TABLE IF NOT EXISTS `$TABLELPIVINTERACTION`(
    	 $add_to_all_tables" .
        "id             bigint unsigned     auto_increment," .
        "order_id       int unsigned   not null default 0,".           // internal order (0->...) given by Dokeos
        "lp_iv_id       bigint unsigned     not null," .                    // identifier of the related sco_view
        "interaction_id varchar(255)        not null default ''," .         // sco-specific, given by the sco
        "interaction_type   varchar(255)    not null default ''," .         // literal values, SCORM-specific (see p.63 of SCORM 1.2 RTE)
        "weighting          double          not null default 0," .
        "completion_time    varchar(16)     not null default ''," .         // completion time for the interaction (timestamp in a day's time) - expected output format is scorm time
        "correct_responses  text            not null default ''," .         // actually a serialised array. See p.65 os SCORM 1.2 RTE)
        "student_response   text            not null default ''," .         // student response (format depends on type)
        "result         varchar(255)        not null default ''," .         // textual result
        "latency        varchar(16)         not null default ''," .          // time necessary for completion of the interaction
    	"PRIMARY KEY  (c_id, id)".
        ")" . $charset_clause;

    Database::query($sql);

    $sql = "ALTER TABLE `$TABLELPIVINTERACTION` ADD INDEX (lp_iv_id) ";
    Database::query($sql);

    $sql = "CREATE TABLE IF NOT EXISTS `$TABLELPIVOBJECTIVE`(
    	$add_to_all_tables" .
        "id             bigint unsigned     auto_increment," .
        "lp_iv_id       bigint unsigned     not null," .                    // identifier of the related sco_view
        "order_id       int unsigned   not null default 0,".           // internal order (0->...) given by Dokeos
        "objective_id   varchar(255)        not null default ''," .         // sco-specific, given by the sco
        "score_raw      float unsigned      not null default 0," .          // score
        "score_max      float unsigned      not null default 0," .          // max score
        "score_min      float unsigned      not null default 0," .          // min score
        "status         char(32)            not null default 'not attempted', " . //status, just as sco status
    	"PRIMARY KEY  (c_id, id) ".
        ")" . $charset_clause;

    Database::query($sql);

    $sql = "ALTER TABLE `$TABLELPIVOBJECTIVE` ADD INDEX (lp_iv_id) ";
    Database::query($sql);

    /* Blogs */

    $sql = "
        CREATE TABLE `" . $tbl_blogs . "` (
            $add_to_all_tables
            blog_id int NOT NULL AUTO_INCREMENT ,
            blog_name varchar( 250 ) NOT NULL default '',
            blog_subtitle varchar( 250 ) default NULL ,
            date_creation datetime NOT NULL default '0000-00-00 00:00:00',
            visibility tinyint unsigned NOT NULL default 0,
            session_id int default 0,
            PRIMARY KEY (c_id, blog_id )
        )" . $charset_clause . " COMMENT = 'Table with blogs in this course';";

    Database::query($sql);

    $sql = "ALTER TABLE `".$tbl_blogs . "` ADD INDEX ( session_id ) ";
    Database::query($sql);

    $sql = "
        CREATE TABLE `" . $tbl_blogs_comments . "` (
        	$add_to_all_tables
            comment_id int NOT NULL AUTO_INCREMENT ,
            title varchar( 250 ) NOT NULL default '',
            comment longtext NOT NULL ,
            author_id int NOT NULL default 0,
            date_creation datetime NOT NULL default '0000-00-00 00:00:00',
            blog_id int NOT NULL default 0,
            post_id int NOT NULL default 0,
            task_id int default NULL ,
            parent_comment_id int NOT NULL default 0,
            PRIMARY KEY (c_id, comment_id )
        )" . $charset_clause . " COMMENT = 'Table with comments on posts in a blog';";

    Database::query($sql);

    $sql = "
        CREATE TABLE `" . $tbl_blogs_posts . "` (
        	$add_to_all_tables
            post_id int NOT NULL AUTO_INCREMENT ,
            title varchar( 250 ) NOT NULL default '',
            full_text longtext NOT NULL ,
            date_creation datetime NOT NULL default '0000-00-00 00:00:00',
            blog_id int NOT NULL default 0,
            author_id int NOT NULL default 0,
            PRIMARY KEY (c_id, post_id )
        )" . $charset_clause . " COMMENT = 'Table with posts / blog.';";

    Database::query($sql);

    $sql = "
        CREATE TABLE `" . $tbl_blogs_rating . "` (
        	$add_to_all_tables
            rating_id int NOT NULL AUTO_INCREMENT ,
            blog_id int NOT NULL default 0,
            rating_type enum( 'post', 'comment' ) NOT NULL default 'post',
            item_id int NOT NULL default 0,
            user_id int NOT NULL default 0,
            rating int NOT NULL default 0,
            PRIMARY KEY (c_id, rating_id )
        )" . $charset_clause . " COMMENT = 'Table with ratings for post/comments in a certain blog';";

    Database::query($sql);

    $sql = "
        CREATE TABLE `" . $tbl_blogs_rel_user . "` (
        	$add_to_all_tables
            blog_id int NOT NULL default 0,
            user_id int NOT NULL default 0,
            PRIMARY KEY ( c_id, blog_id , user_id )
        )" . $charset_clause . " COMMENT = 'Table representing users subscribed to a blog';";

    Database::query($sql);

    $sql = "
        CREATE TABLE `" . $tbl_blogs_tasks . "` (
        	$add_to_all_tables
            task_id int NOT NULL AUTO_INCREMENT ,
            blog_id int NOT NULL default 0,
            title varchar( 250 ) NOT NULL default '',
            description text NOT NULL ,
            color varchar( 10 ) NOT NULL default '',
            system_task tinyint unsigned NOT NULL default 0,
            PRIMARY KEY (c_id, task_id )
        )" . $charset_clause . " COMMENT = 'Table with tasks for a blog';";

    Database::query($sql);

    $sql = "
        CREATE TABLE `" . $tbl_blogs_tasks_rel_user . "` (
        	$add_to_all_tables
            blog_id int NOT NULL default 0,
            user_id int NOT NULL default 0,
            task_id int NOT NULL default 0,
            target_date date NOT NULL default '0000-00-00',
            PRIMARY KEY (c_id, blog_id , user_id , task_id )
        )" . $charset_clause . " COMMENT = 'Table with tasks assigned to a user in a blog';";

    Database::query($sql);

    $sql ="CREATE TABLE  `" .$tbl_blogs_attachment."` (
    	  $add_to_all_tables
          id int unsigned NOT NULL auto_increment,
          path varchar(255) NOT NULL COMMENT 'the real filename',
          comment text,
          size int NOT NULL default '0',
          post_id int NOT NULL,
          filename varchar(255) NOT NULL COMMENT 'the user s file name',
          blog_id int NOT NULL,
          comment_id int NOT NULL default '0',
          PRIMARY KEY  (c_id, id)
        )" . $charset_clause;

    Database::query($sql);

    $sql = "
        CREATE TABLE `" . $tbl_permission_group . "` (
        	$add_to_all_tables
            id int NOT NULL AUTO_INCREMENT ,
            group_id int NOT NULL default 0,
            tool varchar( 250 ) NOT NULL default '',
            action varchar( 250 ) NOT NULL default '',
            PRIMARY KEY (c_id, id)
        )" . $charset_clause;

    Database::query($sql);

    $sql = "
        CREATE TABLE `" . $tbl_permission_user . "` (
        	$add_to_all_tables
            id int NOT NULL AUTO_INCREMENT ,
            user_id int NOT NULL default 0,
            tool varchar( 250 ) NOT NULL default '',
            action varchar( 250 ) NOT NULL default '',
            PRIMARY KEY (c_id, id )
        )" . $charset_clause;

    Database::query($sql);

    $sql = "
        CREATE TABLE `" . $tbl_permission_task . "` (
        	$add_to_all_tables
            id int NOT NULL AUTO_INCREMENT,
            task_id int NOT NULL default 0,
            tool varchar( 250 ) NOT NULL default '',
            action varchar( 250 ) NOT NULL default '',
            PRIMARY KEY (c_id, id )
        )" . $charset_clause;

    Database::query($sql);

    $sql = "
        CREATE TABLE `" . $tbl_role . "` (
        	$add_to_all_tables            
            role_id int NOT NULL AUTO_INCREMENT,
            role_name varchar( 250 ) NOT NULL default '',
            role_comment text,
            default_role tinyint default 0,
            PRIMARY KEY (c_id, role_id)
        )" . $charset_clause;

    Database::query($sql);
    
    $sql = "
        CREATE TABLE `" . $tbl_role_group . "` (
        	$add_to_all_tables
            id int NOT NULL AUTO_INCREMENT,
            role_id int NOT NULL default 0,
            scope varchar( 20 ) NOT NULL default 'course',
            group_id int NOT NULL default 0,
            PRIMARY KEY  (id, c_id, group_id )
        )" . $charset_clause;

    Database::query($sql);

    $sql = "
        CREATE TABLE `" . $tbl_role_permissions . "` (
        	$add_to_all_tables
            id int NOT NULL AUTO_INCREMENT,
            role_id int NOT NULL default 0,
            tool varchar( 250 ) NOT NULL default '',
            action varchar( 50 ) NOT NULL default '',
            default_perm tinyint NOT NULL default 0,
            PRIMARY KEY  (id, c_id, role_id, tool, action )
        )" . $charset_clause;

    Database::query($sql);
    
    $sql = "
        CREATE TABLE `" . $tbl_role_user . "` (
        	$add_to_all_tables
            role_id int NOT NULL default 0,
            scope varchar( 20 ) NOT NULL default 'course',
            user_id int NOT NULL default 0,
            PRIMARY KEY  ( c_id, role_id, user_id )
        )" . $charset_clause;

    Database::query($sql);

    /*
     * Course Config Settings
     *
     */

    Database::query("
        CREATE TABLE `".$TABLESETTING . "` (
        $add_to_all_tables
        id          int unsigned NOT NULL auto_increment,
        variable    varchar(255) NOT NULL default '',
        subkey      varchar(255) default NULL,
        type        varchar(255) default NULL,
        category    varchar(255) default NULL,
        value       varchar(255) NOT NULL default '',
        title       varchar(255) NOT NULL default '',
        comment     varchar(255) default NULL,
        subkeytext  varchar(255) default NULL,
        PRIMARY KEY (c_id, id)
         )" . $charset_clause);

    /*
        Survey
    */

    $sql = "CREATE TABLE `".$TABLESURVEY."` (
    		$add_to_all_tables
              survey_id int unsigned NOT NULL auto_increment,
              code varchar(20) default NULL,
              title text default NULL,
              subtitle text default NULL,
              author varchar(20) default NULL,
              lang varchar(20) default NULL,
              avail_from date default NULL,
              avail_till date default NULL,
              is_shared char(1) default '1',
              template varchar(20) default NULL,
              intro text,
              surveythanks text,
              creation_date datetime NOT NULL default '0000-00-00 00:00:00',
              invited int NOT NULL,
              answered int NOT NULL,
              invite_mail text NOT NULL,
              reminder_mail text NOT NULL,
              mail_subject VARCHAR( 255 ) NOT NULL,
              anonymous enum('0','1') NOT NULL default '0',
              access_condition TEXT DEFAULT NULL,
              shuffle bool NOT NULL default '0',
              one_question_per_page bool NOT NULL default '0',
              survey_version varchar(255) NOT NULL default '',
              parent_id int unsigned NOT NULL,
              survey_type int NOT NULL default 0,
              show_form_profile int NOT NULL default 0,
              form_fields TEXT NOT NULL,
              session_id int unsigned NOT NULL default 0,
              PRIMARY KEY  (c_id, survey_id)
            )" . $charset_clause;
    $result = Database::query($sql);

    $sql = "ALTER TABLE `".$TABLESURVEY."` ADD INDEX ( session_id )";
    Database::query($sql);

    $sql = "CREATE TABLE `".$TABLESURVEYINVITATION."` (
    		  $add_to_all_tables
              survey_invitation_id int unsigned NOT NULL auto_increment,
              survey_code varchar(20) NOT NULL,
              user varchar(250) NOT NULL,
              invitation_code varchar(250) NOT NULL,
              invitation_date datetime NOT NULL,
              reminder_date datetime NOT NULL,
              answered int NOT NULL default 0,
              session_id int UNSIGNED NOT NULL default 0,
              PRIMARY KEY  (c_id, survey_invitation_id)
            )" . $charset_clause;
    $result = Database::query($sql);

    $sql = "CREATE TABLE `".$TABLESURVEYQUESTION."` (
    		  $add_to_all_tables
              question_id int unsigned NOT NULL auto_increment,
              survey_id int unsigned NOT NULL,
              survey_question text NOT NULL,
              survey_question_comment text NOT NULL,
              type varchar(250) NOT NULL,
              display varchar(10) NOT NULL,
              sort int NOT NULL,
              shared_question_id int,
              max_value int,
              survey_group_pri int unsigned NOT NULL default '0',
              survey_group_sec1 int unsigned NOT NULL default '0',
              survey_group_sec2 int unsigned NOT NULL default '0',
              PRIMARY KEY  (c_id, question_id)
            )" . $charset_clause;
    $result = Database::query($sql);

    $sql ="CREATE TABLE `".$TABLESURVEYQUESTIONOPTION."` (
    	$add_to_all_tables
      question_option_id int unsigned NOT NULL auto_increment,
      question_id int unsigned NOT NULL,
      survey_id int unsigned NOT NULL,
      option_text text NOT NULL,
      sort int NOT NULL,
      value int NOT NULL default '0',
      PRIMARY KEY  (c_id, question_option_id)
    )" . $charset_clause;
    
    $result = Database::query($sql);

    $sql = "CREATE TABLE `".$TABLESURVEYANSWER."` (
    		  $add_to_all_tables
              answer_id int unsigned NOT NULL auto_increment,
              survey_id int unsigned NOT NULL,
              question_id int unsigned NOT NULL,
              option_id TEXT NOT NULL,
              value int unsigned NOT NULL,
              user varchar(250) NOT NULL,
              PRIMARY KEY  (c_id, answer_id)
            )" . $charset_clause;
    $result = Database::query($sql);

    $sql = "CREATE TABLE `".$TABLESURVEYGROUP."` (
				$add_to_all_tables
              id int unsigned NOT NULL auto_increment,
              name varchar(20) NOT NULL,
              description varchar(255) NOT NULL,
              survey_id int unsigned NOT NULL,
              PRIMARY KEY  (c_id, id)
            )" . $charset_clause;
    $result = Database::query($sql);

    // Table glosary
    $sql = "CREATE TABLE `".$TBL_GLOSSARY."` (
    		  $add_to_all_tables
              glossary_id int unsigned NOT NULL auto_increment,
              name varchar(255) NOT NULL,
              description text not null,
              display_order int,
              session_id int default 0,
              PRIMARY KEY  (c_id, glossary_id)
            )" . $charset_clause;
    $result = Database::query($sql);

    $sql = "ALTER TABLE `".$TBL_GLOSSARY . "` ADD INDEX ( session_id ) ";
    Database::query($sql);

    // Table notebook
    $sql = "CREATE TABLE `".$TBL_NOTEBOOK."` (
    		  $add_to_all_tables
              notebook_id int unsigned NOT NULL auto_increment,
              user_id int unsigned NOT NULL,
              course varchar(40) not null,
              session_id int NOT NULL default 0,
              title varchar(255) NOT NULL,
              description text NOT NULL,
              creation_date datetime NOT NULL default '0000-00-00 00:00:00',
              update_date datetime NOT NULL default '0000-00-00 00:00:00',
              status int,
              PRIMARY KEY  (c_id, notebook_id)
            )" . $charset_clause;
    $result = Database::query($sql);

    /* Attendance tool */

    // Attendance table
    $sql = "
        CREATE TABLE `".$TBL_ATTENDANCE."` (
        	$add_to_all_tables
            id int NOT NULL auto_increment,
            name text NOT NULL,
            description TEXT NULL,
            active tinyint NOT NULL default 1,
            attendance_qualify_title varchar(255) NULL,
            attendance_qualify_max int NOT NULL default 0,
            attendance_weight float(6,2) NOT NULL default '0.0',
            session_id int NOT NULL default 0,
            locked int NOT NULL default 0,
            PRIMARY KEY  (c_id, id)
        )" . $charset_clause;
    $result = Database::query($sql);

    $sql  = "ALTER TABLE `".$TBL_ATTENDANCE . "` ADD INDEX (session_id)";
    Database::query($sql);

    $sql  = "ALTER TABLE `".$TBL_ATTENDANCE . "` ADD INDEX (active)";
    Database::query($sql);

    // Attendance sheet table
    $sql = "
        CREATE TABLE `".$TBL_ATTENDANCE_SHEET."` (
        	$add_to_all_tables
            user_id int NOT NULL,
            attendance_calendar_id int NOT NULL,
            presence tinyint NOT NULL DEFAULT 0,
            PRIMARY KEY(c_id, user_id, attendance_calendar_id)
        )" . $charset_clause;
    $result = Database::query($sql);

    $sql  = "ALTER TABLE `".$TBL_ATTENDANCE_SHEET . "` ADD INDEX (presence) ";
    Database::query($sql);

    // Attendance calendar table
    $sql = "
        CREATE TABLE `".$TBL_ATTENDANCE_CALENDAR."` (
        	$add_to_all_tables
            id int NOT NULL auto_increment,
            attendance_id int NOT NULL ,
            date_time datetime NOT NULL default '0000-00-00 00:00:00',
            done_attendance tinyint NOT NULL default 0,
            PRIMARY KEY(c_id, id)
        )" . $charset_clause;
    $result = Database::query($sql);

    $sql  = "ALTER TABLE `".$TBL_ATTENDANCE_CALENDAR."` ADD INDEX (attendance_id)";
    Database::query($sql);

    $sql  = "ALTER TABLE `".$TBL_ATTENDANCE_CALENDAR."` ADD INDEX (done_attendance)";
    Database::query($sql);

    // Attendance result table
    $sql = "
        CREATE TABLE `".$TBL_ATTENDANCE_RESULT."` (
        	$add_to_all_tables
            id int NOT NULL auto_increment,
            user_id int NOT NULL,
            attendance_id int NOT NULL,
            score int NOT NULL DEFAULT 0,
            PRIMARY KEY  (c_id, id)
        )" . $charset_clause;
    $result = Database::query($sql);

    $sql    = "ALTER TABLE `".$TBL_ATTENDANCE_RESULT."` ADD INDEX (attendance_id)";
    Database::query($sql);

    $sql    = "ALTER TABLE `".$TBL_ATTENDANCE_RESULT."` ADD INDEX (user_id)";
    Database::query($sql);

    // attendance sheet log table
    $sql = "CREATE TABLE `".$TBL_ATTENDANCE_SHEET_LOG."` (
    			  $add_to_all_tables
                  id int  NOT NULL auto_increment,
                  attendance_id int  NOT NULL DEFAULT 0,
                  lastedit_date datetime  NOT NULL DEFAULT '0000-00-00 00:00:00',
                  lastedit_type varchar(200)  NOT NULL,
                  lastedit_user_id int  NOT NULL DEFAULT 0,
                  calendar_date_value datetime NULL,
                  PRIMARY KEY (c_id, id)
                )" . $charset_clause;
    $result = Database::query($sql) or die(Database::error());


    // Thematic table
    $sql = "CREATE TABLE `".$TBL_THEMATIC."` (
    			$add_to_all_tables
                id int NOT NULL auto_increment,
                title varchar(255) NOT NULL,
                content text NULL,
                display_order int unsigned NOT NULL DEFAULT 0,
                active tinyint NOT NULL DEFAULT 0,
                session_id int NOT NULL DEFAULT 0,
                PRIMARY KEY  (c_id, id)
            )" . $charset_clause;
    $result = Database::query($sql);

    $sql    = "ALTER TABLE `".$TBL_THEMATIC."` ADD INDEX (active, session_id)";
    Database::query($sql);

    // thematic plan table
    $sql = "CREATE TABLE `".$TBL_THEMATIC_PLAN."` (
            	$add_to_all_tables
                id int NOT NULL auto_increment,
                thematic_id int NOT NULL,
                title varchar(255) NOT NULL,
                description text NULL,
                description_type int NOT NULL,
                PRIMARY KEY  (c_id, id)
            )" . $charset_clause;
    $result = Database::query($sql);

    $sql    = "ALTER TABLE `".$TBL_THEMATIC_PLAN."` ADD INDEX (thematic_id, description_type)";
    Database::query($sql);

    // thematic advance table
    $sql = "
            CREATE TABLE `".$TBL_THEMATIC_ADVANCE."` (
            	$add_to_all_tables
                id int NOT NULL auto_increment,
                thematic_id int NOT NULL,
                attendance_id int NOT NULL DEFAULT 0,
                content text NULL,
                start_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
                duration int NOT NULL DEFAULT 0,
                done_advance tinyint NOT NULL DEFAULT 0,
                PRIMARY KEY  (c_id, id)
            )" . $charset_clause;
    $result = Database::query($sql);

    $sql    = "ALTER TABLE `".$TBL_THEMATIC_ADVANCE."` ADD INDEX (thematic_id)";
    Database::query($sql);


    $sql = "CREATE TABLE IF NOT EXISTS " . $TBL_METADATA . " (    " .
            $add_to_all_tables.
            "eid VARCHAR(250) NOT NULL," .      // entry-id, e.g. doc.1
            "mdxmltext TEXT default ''," .      // MD-text, XML-formatted
            "md5 CHAR(32) default ''," .        // hash-validator
            "htmlcache1 TEXT default ''," .     // cached HTML, part 1
            "htmlcache2 TEXT default ''," .     // cached HTML, part 2
            "indexabletext TEXT default ''," .  // indexable for search
            "PRIMARY KEY (c_id, eid)           )".$charset_clause;

    Database::query($sql);

    return 0;
}