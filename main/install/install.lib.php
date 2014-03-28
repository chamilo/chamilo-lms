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

/* CONSTANTS */
define('COURSES_HTACCESS_FILENAME', 'htaccess.dist');
define('SYSTEM_CONFIG_FILENAME', 'configuration.dist.php');

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
function check_extension($extension_name, $return_success = 'Yes', $return_failure = 'No', $optional = false)
{
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
function check_php_setting($php_setting, $recommended_value, $return_success = false, $return_failure = false)
{
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
function get_php_setting($val)
{
    return ini_get($val) == '1' ? 'ON' : 'OFF';
}

/**
 * This function returns a string "true" or "false" according to the passed parameter.
 *
 * @param integer  $var  The variable to present as text
 * @return  string  the string "true" or "false"
 * @author Christophe Gesch??
 */
function true_false($var)
{
    return $var ? 'true' : 'false';
}

/**
 * Removes memory and time limits as much as possible.
 */
function remove_memory_and_time_limits()
{
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
function detect_browser_language()
{
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
function check_writable($folder, $suggestion = false)
{
    if (is_writable(api_get_path(SYS_CODE_PATH).$folder)) {
        return Display::label(translate('Writable'), 'success');
    } else {
        if ($suggestion) {
            return Display::label(translate('NotWritable'), 'info');
        } else {
            return Display::label(translate('NotWritable'), 'important');
        }
    }
}

/**
 * This function checks if the given folder is writable
 */
function check_writable_root_path($folder, $suggestion = false)
{
    if (is_writable(api_get_path(SYS_PATH).$folder)) {
        return Display::label(translate('Writable'), 'success');
    } else {
        if ($suggestion) {
            return Display::label(translate('NotWritable'), 'info');
        } else {
            return Display::label(translate('NotWritable'), 'important');
        }
    }
}

/**
 * This function is similar to the core file() function, except that it
 * works with line endings in Windows (which is not the case of file())
 * @param   string  File path
 * @return  array   The lines of the file returned as an array
 */
function file_to_array($filename)
{
    if (!is_readable($filename) || is_dir($filename)) {
        return array();
    }
    $fp = fopen($filename, 'rb');
    $buffer = fread($fp, filesize($filename));
    fclose($fp);

    return explode('<br />', nl2br($buffer));
}

/**
 * Add's a .htaccess file to the courses directory
 * @param string $url_append The path from your webroot to your chamilo root
 */
function write_courses_htaccess_file($url_append)
{
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
function write_system_config_file($path)
{
    global $dbHostForm;
    global $dbUsernameForm;
    global $dbPassForm;
    global $singleDbForm;
    global $dbPrefixForm;
    global $dbNameForm;
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

    $config['{DATE_GENERATED}'] = date('r');
    $config['{DATABASE_HOST}'] = $dbHostForm;
    $config['{DATABASE_USER}'] = $dbUsernameForm;
    $config['{DATABASE_PASSWORD}'] = $dbPassForm;
    $config['SINGLE_DATABASE'] = true_false($singleDbForm);
    $config['{COURSE_TABLE_PREFIX}'] = ($singleDbForm ? 'crs_' : '');
    $config['{DATABASE_GLUE}'] = ($singleDbForm ? '_' : '`.`');
    $config['{DATABASE_PREFIX}'] = '';
    $config['{DATABASE_MAIN}'] = $dbNameForm;

    $config['{ROOT_WEB}'] = $urlForm;
    $config['{ROOT_SYS}'] = $root_sys;
    $config['{URL_APPEND_PATH}'] = $urlAppendPath;
    $config['{PLATFORM_LANGUAGE}'] = $languageForm;
    $config['{SECURITY_KEY}'] = md5(uniqid(rand().time()));
    $config['{ENCRYPT_PASSWORD}'] = $encryptPassForm;

    $config['SESSION_LIFETIME'] = $session_lifetime;
    $config['{NEW_VERSION}'] = $new_version;
    $config['NEW_VERSION_STABLE'] = true_false($new_version_stable);

    foreach ($config as $key => $value) {
        $content = str_replace($key, $value, $content);
    }

    $fp = @ fopen($path, 'w');

    if (!$fp) {
        echo '<strong>
        <font color="red">Your script doesn\'t have write access to the config directory</font></strong>
        <br />
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
function & get_language_folder_list()
{
    static $result;
    if (!is_array($result)) {
        $result = array();
        $exceptions = array('.', '..', 'CVS', '.svn');
        $search = array('_latin', '_unicode', '_corporate', '_org', '_KM', '_');
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
function my_directory_to_array($directory)
{
    $array_items = array();
    if ($handle = opendir($directory)) {
        while (false !== ($file = readdir($handle))) {
            if ($file != "." && $file != "..") {
                if (is_dir($directory."/".$file)) {
                    $array_items = array_merge($array_items, my_directory_to_array($directory.'/'.$file));
                    $file = $directory."/".$file;
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
function get_config_param($param, $updatePath = '')
{
    global $configFile, $updateFromConfigFile, $_configuration;

    if (isset($_configuration[$param])) {
        return $_configuration[$param];
    } else {
        return null;
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
function get_config_param_from_db($host, $login, $pass, $db_name, $param = '')
{

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
function database_server_connect()
{
    global $dbHostForm, $dbUsernameForm, $dbPassForm;
    if (($res = @Database::connect(
        array('server' => $dbHostForm, 'username' => $dbUsernameForm, 'password' => $dbPassForm)
    )) === false
    ) {
        $no = Database::errno();
        $msg = Database::error();
        echo '<hr />#'.$no.': '.$msg.'<hr />';
        echo translate('DBServerDoesntWorkOrLoginPassIsWrong').'.<br /><br />'.
            translate('PleaseCheckTheseValues').' :<br /><br />'.
            '<strong>'.translate('DBHost').'</strong> : '.$dbHostForm.'<br />'.
            '<strong>'.translate('DBLogin').'</strong> : '.$dbUsernameForm.'<br />'.
            '<strong>'.translate('DBPassword').'</strong> : '.$dbPassForm.'<br /><br />'.
            translate('PleaseGoBackToStep').' '.(defined('SYSTEM_INSTALLATION') ? '3' : '1').'.'.
            '<p><button type="submit" class="back" name="step'.(defined(
            'SYSTEM_INSTALLATION'
        ) ? '3' : '1').'" value="&lt; '.translate('Back').'">'.translate('Back').'</button></p>'.
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
function database_exists($database_name)
{
    if (empty($database_name)) {
        return false;
    }
    $select_database = Database::select_db($database_name);
    $show_database = false;
    $sql = "SHOW DATABASES LIKE '".addslashes($database_name)."'";
    $result = Database::query($sql);
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
function testDatabaseConnect($dbHostForm, $dbUsernameForm, $dbPassForm, $singleDbForm, $dbPrefixForm, $dbNameForm)
{
     $connection = array(
        'driver'    => 'pdo_mysql',
        'dbname'    => $dbNameForm,
        'user'      => $dbUsernameForm,
        'password'  => $dbPassForm,
        'host'      => $dbHostForm,
    );
    $config = new \Doctrine\DBAL\Configuration();
    $conn = \Doctrine\DBAL\DriverManager::getConnection($connection, $config);

    try {
        $connect = $conn->connect();
        $sm = $conn->getSchemaManager();
        $databases = $sm->listDatabases();

        if (in_array($dbNameForm, $databases)) {
            echo '<div class="warning-message">'.translate('ADatabaseWithTheSameNameAlreadyExists').'</div>';
        }
        $database = new Database($conn);

        return $connect;
    } catch (Exception $e) {
        /*echo '<div class="error-message">';
        echo $e->getMessage();
        echo '</div>';*/

        return -1;
    }

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
function load_main_database($installation_settings, $db_script = '')
{
    $sql_text = null;
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
    parse_sql_queries($sql_text);
}

/**
 * Creates the structure of the stats database
 * @param   string  Name of the file containing the SQL script inside the install directory
 */
function load_database_script($db_script)
{
    $db_script = api_get_path(SYS_CODE_PATH).'install/'.$db_script;
    if (file_exists($db_script)) {
        $sql_text = file_get_contents($db_script);
    }
    parse_sql_queries($sql_text);
}

function parse_sql_queries($sql_text)
{
    //split in array of sql strings
    $sql_instructions = array();
    split_sql_file($sql_instructions, $sql_text);

    //execute the sql instructions
    $count = count($sql_instructions);
    for ($i = 0; $i < $count; $i++) {
        $this_sql_query = $sql_instructions[$i]['query'];

        //UTF8 fix see #5678
        if (strpos(strtolower($this_sql_query), 'create table') === false) {
            iDatabase::query($this_sql_query);
        } else {
            //$this_sql_query .= substr($this_sql_query, strlen($this_sql_query), strlen($this_sql_query)-1);
            $this_sql_query .= ' DEFAULT CHARACTER SET utf8 DEFAULT COLLATE utf8_general_ci ';
            iDatabase::query($this_sql_query);
        }
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
function split_sql_file(&$ret, $sql)
{
    // do not trim, see bug #1030644
    //$sql          = trim($sql);
    $sql = rtrim($sql, "\n\r");
    $sql_len = strlen($sql);
    $char = '';
    $string_start = '';
    $in_string = false;
    $nothing = true;
    $time0 = time();

    for ($i = 0; $i < $sql_len; ++$i) {
        $char = $sql[$i];

        // We are in a string, check for not escaped end of strings except for
        // backquotes that can't be escaped
        if ($in_string) {
            for (; ;) {
                $i = strpos($sql, $string_start, $i);
                // No end of string found -> add the current substring to the
                // returned array
                if (!$i) {
                    $ret[] = $sql;

                    return true;
                } // Backquotes or no backslashes before quotes: it's indeed the
                // end of the string -> exit the loop
                elseif ($string_start == '`' || $sql[$i - 1] != '\\') {
                    $string_start = '';
                    $in_string = false;
                    break;
                } // one or more Backslashes before the presumed end of string...
                else {
                    // ... first checks for escaped backslashes
                    $j = 2;
                    $escaped_backslash = false;
                    while ($i - $j > 0 && $sql[$i - $j] == '\\') {
                        $escaped_backslash = !$escaped_backslash;
                        $j++;
                    }
                    // ... if escaped backslashes: it's really the end of the
                    // string -> exit the loop
                    if ($escaped_backslash) {
                        $string_start = '';
                        $in_string = false;
                        break;
                    } // ... else loop
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
            if ($char == '/') {
                $i++;
            }
        } // We are not in a string, first check for delimiter...
        elseif ($char == ';') {
            // if delimiter found, add the parsed part to the returned array
            $ret[] = array('query' => substr($sql, 0, $i), 'empty' => $nothing);
            $nothing = true;
            $sql = ltrim(substr($sql, min($i + 1, $sql_len)));
            $sql_len = strlen($sql);
            if ($sql_len) {
                $i = -1;
            } else {
                // The submited statement(s) end(s) here
                return true;
            }
        } // end elseif (is delimiter)

        // ... then check for start of a string,...
        elseif (($char == '"') || ($char == '\'') || ($char == '`')) {
            $in_string = true;
            $nothing = false;
            $string_start = $char;
        } // end elseif (is start of string)

        elseif ($nothing) {
            $nothing = false;
        }

        // loic1: send a fake header each 30 sec. to bypass browser timeout
        $time1 = time();
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
function get_sql_file_contents($file, $section, $print_errors = true)
{
    //check given parameters
    if (empty($file)) {
        $error = "Missing name of file to parse in get_sql_file_contents()";
        if ($print_errors) {
            echo $error;
        }

        return false;
    }
    if (!in_array($section, array('main', 'user', 'stats', 'scorm', 'course'))) {
        $error = "Section '$section' is not authorized in get_sql_file_contents()";
        if ($print_errors) {
            echo $error;
        }

        return false;
    }
    $filepath = getcwd().'/'.$file;
    if (!is_file($filepath) or !is_readable($filepath)) {
        $error = "File $filepath not found or not readable in get_sql_file_contents()";
        if ($print_errors) {
            echo $error;
        }

        return false;
    }
    //read the file in an array
    // Empty lines should not be executed as SQL statements, because errors occur, see Task #2167.
    $file_contents = file($filepath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($file_contents) or count($file_contents) < 1) {
        $error = "File $filepath looks empty in get_sql_file_contents()";
        if ($print_errors) {
            echo $error;
        }

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
function add_document_180($_course, $path, $filetype, $filesize, $title, $comment = null)
{
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
function step_active($param)
{
    global $current_step;
    if ($param == $current_step) {
        echo 'class="current_step" ';
    }
}

/**
 * This function displays the Step X of Y -
 * @return  string  String that says 'Step X of Y' with the right values
 */
function display_step_sequence()
{
    global $current_step;

    return translate('Step'.$current_step).' &ndash; ';
}

/**
 * Displays a drop down box for selection the preferred language.
 */
function display_language_selection_box($name = 'language_list', $default_language = 'english')
{
    // Reading language list.
    $language_list = get_language_folder_list();

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
function display_language_selection()
{
    ?>
<h2><?php translate('WelcomeToTheDokeosInstaller'); ?></h2>
<div class="RequirementHeading">
    <h2><?php echo display_step_sequence(); ?><?php echo translate('InstallationLanguage'); ?></h2>

    <p><?php echo translate('PleaseSelectInstallationProcessLanguage'); ?>:</p>

    <form id="lang_form" method="post" action="<?php echo api_get_self(); ?>">
        <?php display_language_selection_box('language_list', api_get_interface_language()); ?>
        <button type="submit" name="step1" class="btn next" autofocus="autofocus"
                value="<?php echo translate('Next'); ?>"><?php echo translate('Next'); ?></button>
        <input type="hidden" name="is_executable" id="is_executable" value="-"/>
    </form>
</div>
<?php
}

function translate($variable)
{
    global $app;

    return $app['translator']->trans($variable);
}

function getRequirements()
{
    return
        array(
            'required' => array(
                //'session' => array('url' => 'http://php.net/manual/en/book.session.php', 'recommend' => Display::label('OFF', 'success')),
                'mysql' => array('url' => 'http://php.net/manual/en/book.mysql.php'),
                'curl' => array('url' => 'http://php.net/manual/fr/book.curl.php'),
                'zlib' => array('url' => 'http://php.net/manual/en/book.zlib.php'),
                'pcre' => array('url' => 'http://php.net/manual/en/book.pcre.php'),
                'xml' => array('url' => 'http://php.net/manual/en/book.xml.php'),
                'mbstring' => array('url' => 'http://php.net/manual/en/book.mbstring.php'),
                'iconv' => array('url' => 'http://php.net/manual/en/book.iconv.php'),
                'intl' => array('url' => 'http://php.net/manual/en/book.intl.php'),
                'gd' => array('url' => 'http://php.net/manual/en/book.image.php'),
                'json' => array('url' => 'http://php.net/manual/en/book.json.php')
            ),
            'optional' =>  array(
                'imagick' => array('url' => 'http://php.net/manual/en/book.imagick.php'),
                'ldap' => array('url' => 'http://php.net/manual/en/book.ldap.php'),
                'xapian' => array('url' => 'http://php.net/manual/en/book.xapian.php')
            )
        );
}

/**
 * @param Symfony\Component\Translation\Translator $translator
 * @return array
 */
function getOptions($translator)
{
    return array(
        array(
            'name' => 'Safe Mode',
            'url' => 'http://php.net/manual/features.safe-mode.php',
            'recommended' => Display::label('OFF', 'success'),
            'current' => check_php_setting('safe_mode', 'OFF'),
        ),
        array(
            'name' => 'Display Errors',
            'url' => 'http://php.net/manual/ref.errorfunc.php#ini.display-errors',
            'recommended' => Display::label('ON', 'success'),
            'current' => check_php_setting('display_errors', 'OFF'),
        ),
        array(
            'name' => 'File Uploads',
            'url' => 'http://php.net/manual/ini.core.php#ini.file-uploads',
            'recommended' => Display::label('OFF', 'success'),
            'current' => check_php_setting('file_uploads', 'ON'),
        ),
        array(
            'name' => 'Magic Quotes GPC',
            'url' => 'http://php.net/manual/ref.info.php#ini.magic-quotes-gpc',
            'recommended' => Display::label('OFF', 'success'),
            'current' => check_php_setting('magic_quotes_gpc', 'OFF'),
        ),
        array(
            'name' => 'Magic Quotes Runtime',
            'url' => 'http://php.net/manual/ref.info.php#ini.magic-quotes-runtime',
            'recommended' => Display::label('OFF', 'success'),
            'current' => check_php_setting('magic_quotes_runtime', 'OFF'),
        ),
        array(
            'name' => 'Register Globals',
            'url' => 'http://php.net/manual/security.globals.php',
            'recommended' => Display::label('OFF', 'success'),
            'current' => check_php_setting('register_globals', 'OFF'),
        ),
        array(
            'name' => 'Session auto start',
            'url' => 'http://php.net/manual/ref.session.php#ini.session.auto-start',
            'recommended' => Display::label('OFF', 'success'),
            'current' => check_php_setting('auto_start', 'OFF'),
        ),
        array(
            'name' => 'Short Open Tag',
            'url' => 'http://php.net/manual/ini.core.php#ini.short-open-tag',
            'recommended' => Display::label('OFF', 'success'),
            'current' => check_php_setting('short_open_tag', 'OFF'),
        ),
        array(
            'name' => 'Cookie HTTP Only',
            'url' => 'http://www.php.net/manual/en/session.configuration.php#ini.session.cookie-httponly',
            'recommended' => Display::label('ON', 'success'),
            'current' => check_php_setting('session.cookie_httponly', 'ON'),
        ),
        array(
            'name' => 'Maximum upload file size',
            'url' => 'http://php.net/manual/ini.core.php#ini.upload-max-filesize',
            'recommended' => Display::label('>= '.REQUIRED_MIN_UPLOAD_MAX_FILESIZE.'M', 'success'),
            'current' => compare_setting_values(ini_get('upload_max_filesize'), REQUIRED_MIN_UPLOAD_MAX_FILESIZE),
        ),
        array(
            'name' => 'Maximum post size',
            'url' => 'http://php.net/manual/ini.core.php#ini.post-max-size',
            'recommended' => Display::label('>= '.REQUIRED_MIN_POST_MAX_SIZE.'M', 'success'),
            'current' => compare_setting_values(ini_get('post_max_size'), REQUIRED_MIN_POST_MAX_SIZE),
        ),
        array(
            'name' => 'Memory Limit',
            'url' => 'http://www.php.net/manual/en/ini.core.php#ini.memory-limit',
            'recommended' => Display::label('>= '.REQUIRED_MIN_MEMORY_LIMIT.'M', 'success'),
            'current' => compare_setting_values(ini_get('memory_limit'), REQUIRED_MIN_MEMORY_LIMIT),
        )
    );
}

/**
 * Check if current system is allowed to install
 * @return bool
 */
function checkRequiredSettings()
{
    $requirements = getRequirements();
    $requiredSettings = $requirements['required'];

    foreach ($requiredSettings as $extension => $options) {
        if (!extension_loaded($extension)) {
            return false;
        }
    }

    return true;
}

/**
 * @param Symfony\Component\Translation\Translator $translator
 * @return null|string
 */
function drawRequirements($translator)
{
    $requirements = getRequirements();
    $html = null;
    $html .= '<tr>
                <td>
                    '.$translator->trans('Required').'
                </td>
                <td>
                </td>
              </tr>';

    foreach ($requirements['required'] as $extension => $req) {
        $checkExtension = check_extension(
            $extension,
            $translator->trans('Yes'),
            $translator->trans('No')
        );
        $html .= '<tr>
                    <td>
                        <a href="'.$req['url'].'">'.$extension.'</a>
                    </td>
                    <td>
                        '.$checkExtension.'
                    </td>
                  </tr>';
    }

    $html .= '<tr>
                <td>
                    '.$translator->trans('Optional').'
                </td>
                <td>
                </td>
              </tr>';

    foreach ($requirements['optional'] as $extension => $req) {

        $checkExtension = check_extension(
            $extension,
            $translator->trans('Yes'),
            $translator->trans('No')
        );

        $html .= '<tr>
                    <td>
                        <a href="'.$req['url'].'">'.$extension.'</a>
                    </td>
                    <td>
                        '.$checkExtension.'
                    </td>
                  </tr>';
    }

    return $html;
}

function drawOptions($translator)
{
    $options = getOptions($translator);
    $html = null;
    foreach ($options as $option) {
        $html .= '<tr>
                    <td>
                        <a href="'.$option['url'].'">'.$option['name'].'</a>
                    </td>
                    <td>
                        '.$option['recommended'].'
                    </td>
                    <td>
                        '.$option['current'].'
                    </td>
                  </tr>';
    }

    return $html;
}

function drawPermissionsSettings($app)
{
    $html  = null;

    // DIRECTORY AND FILE PERMISSIONS
    $html .= '<div class="RequirementContent">';

    $course_attempt_name = '__XxTestxX__';
    $course_dir = api_get_path(SYS_COURSE_PATH).$course_attempt_name;

    // Just in case.
    if (is_file($course_dir.'/test.txt')) {
        unlink($course_dir.'/test.txt');
    }
    if (is_dir($course_dir)) {
        rmdir($course_dir);
    }

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
    $file_course_test_was_created = false;

    if (is_dir($course_dir)) {
        foreach ($perms_fil as $perm) {
            if ($file_course_test_was_created == true) {
                break;
            }
            $r = touch($course_dir.'/test.php', $perm);
            if ($r === true) {
                $fil_perm_verified = $perm;
                if (check_course_script_interpretation($course_dir, $course_attempt_name, 'test.php')) {
                    $file_course_test_was_created = true;
                }
            }
        }
    }

    @unlink($course_dir.'/test.php');
    @rmdir($course_dir);

    $app['session']->set('permissions_for_new_directories', decoct($dir_perm_verified));
    $app['session']->set('permissions_for_new_files', decoct($fil_perm_verified));

    $dir_perm = Display::label('0'.decoct($dir_perm_verified), 'info');
    $file_perm = Display::label('0'.decoct($fil_perm_verified), 'info');

    $course_test_was_created  = ($course_test_was_created == true && $file_course_test_was_created == true) ? Display::label(translate('Yes'), 'success') : Display::label(translate('No'), 'important');

    $html .= '<table class="table">
            <tr>
                <td class="requirements-item">[chamilo]/config</td>
                <td class="requirements-value">'.check_writable_root_path('config/').'</td>
            </tr>
            <tr>
                <td class="requirements-item">[chamilo]/data</td>
                <td class="requirements-value">'.check_writable_root_path('data').'</td>
            </tr>
            <tr>
                <td class="requirements-item">[chamilo]/logs</td>
                <td class="requirements-value">'.check_writable_root_path('logs').'</td>
            </tr>
            <tr>
                <td class="requirements-item">'.translate('CourseTestWasCreated').'</td>
                <td class="requirements-value">'.$course_test_was_created.' </td>
            </tr>
            <tr>
                <td class="requirements-item">'.translate('PermissionsForNewDirs').'</td>
                <td class="requirements-value">'.$dir_perm.' </td>
            </tr>
            <tr>
                <td class="requirements-item">'.translate('PermissionsForNewFiles').'</td>
                <td class="requirements-value">'.$file_perm.' </td>
            </tr>';

    $html .= '    </table>';
    $html .= '  </div>';
    $html .= '</div>';

    $error = false;
    // First, attempt to set writing permissions if we don't have them yet
    $perm = $app['session']->get('permissions_for_new_directories');
    $perm_file = $app['session']->get('permissions_for_new_files');

    $notwritable = array();

    $checked_writable = api_get_path(SYS_CONFIG_PATH);
    if (!is_writable($checked_writable)) {
        $notwritable[] = $checked_writable;
        @chmod($checked_writable, $perm);
    }

    $checked_writable = api_get_path(SYS_DATA_PATH);
    if (!is_writable($checked_writable)) {
        $notwritable[] = $checked_writable;
        @chmod($checked_writable, $perm);
    }

    $checked_writable = api_get_path(SYS_DEFAULT_COURSE_DOCUMENT_PATH).'images/';
    if (!is_writable($checked_writable)) {
        $notwritable[] = $checked_writable;
        @chmod($checked_writable, $perm);
    }

    $checked_writable = api_get_path(SYS_ARCHIVE_PATH);
    if (!is_writable($checked_writable)) {
        $notwritable[] = $checked_writable;
        @chmod($checked_writable, $perm);
    }

    $checked_writable = api_get_path(SYS_LOG_PATH);
    if (!is_writable($checked_writable)) {
        $notwritable[] = $checked_writable;
        @chmod($checked_writable, $perm);
    }

    /*$checked_writable = api_get_path(SYS_COURSE_PATH);
    if (!is_writable($checked_writable)) {
        $notwritable[] = $checked_writable;
        @chmod($checked_writable, $perm);
    }*/

    if ($course_test_was_created == false || $file_course_test_was_created == false) {
        $error = true;
    }

    /*$checked_writable = api_get_path(SYS_PATH).'home/';
    if (!is_writable($checked_writable)) {
        $notwritable[] = realpath($checked_writable);
        @chmod($checked_writable, $perm);
    }*/

    /*$checked_writable = api_get_path(CONFIGURATION_PATH).'configuration.php';
    if (file_exists($checked_writable) && !is_writable($checked_writable)) {
        $notwritable[] = $checked_writable;
        @chmod($checked_writable, $perm_file);
    }*/

    // Second, if this fails, report an error

    // The user would have to adjust the permissions manually

    if (count($notwritable) > 0) {
        $html .= '<div class="error-message">';
        $html .= '<center><h3>'.translate('Warning').'</h3></center>';
        $html .=  sprintf(
            translate('NoWritePermissionPleaseReadInstallGuide'),
            '</font>
            <a href="../../documentation/installation_guide.html" target="blank">',
            '</a> <font color="red">'
        );
        $html .= '</div>';

        $html .= '<ul>';
        foreach ($notwritable as $value) {
            $html .= '<li>'.$value.'</li>';
        }
        $html .= '</ul>';
    } elseif (file_exists(api_get_path(CONFIGURATION_PATH).'configuration.php')) {
        // Check wether a Chamilo configuration file already exists.
        $html .= '<div class="warning-message"><h4><center>';
        $html .= translate('WarningExistingDokeosInstallationDetected');
        $html .= '</center></h4></div>';
    }

    return $html;
}

/**
 * Displays the license (GNU GPL) as step 2, with
 * - an "I accept" button named step3 to proceed to step 3;
 * - a "Back" button named step1 to go back to the first step.
 */

function display_license_agreement()
{
    echo '<div class="RequirementHeading"><h2>'.display_step_sequence().translate('Licence').'</h2>';
    echo '<p>'.translate('DokeosLicenseInfo').'</p>';
    echo '<p><a href="../../documentation/license.html" target="_blank">'.translate('PrintVers').'</a></p>';
    echo '</div>';
    ?>
<table>
    <tr>
        <td>
            <p style="font-size:90%">
                <textarea cols="90" rows="7" class="span6">
                    <?php echo api_htmlentities(
                    @file_get_contents(api_get_path(SYS_PATH).'documentation/license.txt')
                ); ?>
                </textarea></p>
        </td>
    </tr>
    <tr>
        <td>
            <label class="checkbox">
                <input type="checkbox" name="accept" id="accept_licence" value="1" autofocus="autofocus"/>
                <?php echo translate('IAccept'); ?>
            </label>
        </td>
    </tr>
    <tr>
        <td><p style="color:#666"><br/><?php echo translate('DokeosArtLicense'); ?></p></td>
    </tr>
    <tr>
        <td>
            <table width="100%">
                <tr>
                    <td></td>
                    <td align="center">
                        <button type="submit" class="btn back" name="step1"
                                value="&lt; <?php echo translate('Previous'); ?>"><?php echo translate(
                            'Previous'
                        ); ?></button>
                        <input type="hidden" name="is_executable" id="is_executable" value="-"/>
                        <button type="submit" class="btn next" name="step3"
                                onclick="javascript: if (!document.getElementById('accept_licence').checked) { alert('<?php echo translate(
                                    'YouMustAcceptLicence'
                                )?>');return false;}" value="<?php echo translate('Next'); ?> &gt;"><?php echo translate(
                            'Next'
                        ); ?></button>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<!-- Contact information form -->
<div>

    <a href="javascript://" class="advanced_parameters">
        <span id="img_plus_and_minus">&nbsp;<img src="<?php echo api_get_path(WEB_IMG_PATH) ?>div_hide.gif"
                                                 alt="<?php echo translate('Hide') ?>"
                                                 title="<?php echo translate('Hide')?>" style="vertical-align:middle"/>&nbsp;<?php echo translate(
            'ContactInformation'
        ) ?></span>
    </a>

</div>

<div id="id_contact_form" style="display:block">
    <div class="normal-message"><?php echo translate('ContactInformationDescription') ?></div>
    <div id="contact_registration">
        <p><?php echo get_contact_registration_form() ?></p><br/>
    </div>
</div>
<?php
}


/**
 * Get contact registration form
 */
function get_contact_registration_form()
{

    $html = '
   <form class="form-horizontal">
   <fieldset style="width:95%;padding:15px;border:1pt solid #eee">
    <div id="div_sent_information"></div>
    <div class="control-group">
            <label class="control-label"><span class="form_required">*</span>'.translate('Name').'</label>
            <div class="controls"><input id="person_name" type="text" name="person_name" size="30" /></div>
    </div>
    <div class="control-group">
            <label class="control-label"><span class="form_required">*</span>'.translate('Email').'</label>
            <div class="controls"><input id="person_email" type="text" name="person_email" size="30" /></div>
    </div>
    <div class="control-group">
            <label class="control-label"><span class="form_required">*</span>'.translate('CompanyName').'</label>
            <div class="controls"><input id="company_name" type="text" name="company_name" size="30" /></div>
    </div>
    <div class="control-group">
            <div class="control-label"><span class="form_required">*</span>'.translate('CompanyActivity').'</div>
            <div class="controls">
                    <select name="company_activity" id="company_activity" >
                            <option value="">--- '.translate('SelectOne').' ---</option>
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
            <div class="control-label"><span class="form_required">*</span>'.translate('PersonRole').'</div>
            <div class="controls">
                    <select name="person_role" id="person_role" >
                            <option value="">--- '.translate('SelectOne').' ---</option>
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
            <div class="control-label"><span class="form_required">*</span>'.translate('CompanyCountry').'</div>
            <div class="controls">'.get_countries_list_from_array(true).'</div>
    </div>
    <div class="control-group">
            <div class="control-label">'.translate('CompanyCity').'</div>
            <div class="controls">
                    <input type="text" id="company_city" name="company_city" size="30" />
            </div>
    </div>
    <div class="control-group">
            <div class="control-label">'.translate('WhichLanguageWouldYouLikeToUseWhenContactingYou').'</div>
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
            <div class="control-label">'.translate('HaveYouThePowerToTakeFinancialDecisions').'</div>
            <div class="controls">
                    <input type="radio" name="financial_decision" id="financial_decision1" value="1" checked />'.translate(
        'Yes'
    ).'
                    <input type="radio" name="financial_decision" id="financial_decision2" value="0" />'.translate('No').'
            </div>
    </div>
    <div class="clear"></div>
    <div class="control-group">
            <div class="control-label">&nbsp;</div>
            <div class="controls"><button type="button" class="save" onclick="javascript:send_contact_information();" value="'.translate(
        'SendInformation'
    ).'" >'.translate('SendInformation').'</button></div>
    </div>
    <div class="control-group">
            <div class="control-label">&nbsp;</div>
            <div class="controls"><span class="form_required">*</span><small>'.translate('FieldRequired').'</small></div>
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
function display_database_parameter(
    $install_type,
    $parameter_name,
    $form_field_name,
    $parameter_value,
    $extra_notice,
    $display_when_update = true,
    $tr_attribute = ''
) {
    echo "<tr ".$tr_attribute.">";
    echo "<td>$parameter_name&nbsp;&nbsp;</td>";

    if ($install_type == INSTALL_TYPE_UPDATE && $display_when_update) {
        echo '<td><input type="hidden" name="'.$form_field_name.'" id="'.$form_field_name.'" value="'.api_htmlentities(
            $parameter_value
        ).'" />'.$parameter_value."</td>";
    } else {
        $inputtype = $form_field_name == 'dbPassForm' ? 'password' : 'text';

        //Slightly limit the length of the database prefix to avoid having to cut down the databases names later on
        $maxlength = $form_field_name == 'dbPrefixForm' ? '15' : MAX_FORM_FIELD_LENGTH;
        if ($install_type == INSTALL_TYPE_UPDATE) {
            echo '<input type="hidden" name="'.$form_field_name.'" id="'.$form_field_name.'" value="'.api_htmlentities(
                $parameter_value
            ).'" />';
            echo '<td>'.api_htmlentities($parameter_value)."</td>";
        } else {
            echo '<td><input type="'.$inputtype.'" size="'.DATABASE_FORM_FIELD_DISPLAY_LENGTH.'" maxlength="'.$maxlength.'" name="'.$form_field_name.'" id="'.$form_field_name.'" value="'.api_htmlentities(
                $parameter_value
            ).'" />'."</td>";
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
function display_database_settings_form(
    $installType,
    $dbHostForm,
    $dbUsernameForm,
    $dbPassForm,
    $dbPrefixForm,
    $enableTrackingForm,
    $singleDbForm,
    $dbNameForm
) {

    if ($installType == 'update') {
        global $_configuration, $update_from_version_6;

        if (in_array($_POST['old_version'], $update_from_version_6)) {
            $dbHostForm = get_config_param('dbHost');

            $dbUsernameForm = get_config_param('dbLogin');
            $dbPassForm = get_config_param('dbPass');
            $dbPrefixForm = get_config_param('dbNamePrefix');
            $enableTrackingForm = get_config_param('is_trackingEnabled');
            $singleDbForm = get_config_param('singleDbEnabled');
            $dbHostForm = get_config_param('mainDbName');

            $dbStatsForm = get_config_param('statsDbName');
            $dbScormForm = get_config_param('scormDbName');
            $dbUserForm = get_config_param('user_personal_database');
            $dbScormExists = true;
        } else {
            $dbHostForm = $_configuration['db_host'];
            $dbUsernameForm = $_configuration['db_user'];
            $dbPassForm = $_configuration['db_password'];
            $dbPrefixForm = $_configuration['db_prefix'];
            $enableTrackingForm = isset($_configuration['tracking_enabled']) ? $_configuration['tracking_enabled'] : null;
            $singleDbForm = isset($_configuration['single_database']) ? $_configuration['single_database'] : null;
            $dbNameForm = $_configuration['main_database'];
            $dbStatsForm = isset($_configuration['statistics_database']) ? $_configuration['statistics_database'] : null;
            $dbScormForm = isset($_configuration['scorm_database']) ? $_configuration['scorm_database'] : null;
            $dbUserForm = isset($_configuration['user_personal_database']) ? $_configuration['user_personal_database'] : null;
            $dbScormExists = true;
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

        echo '<div class="RequirementHeading"><h2>'.display_step_sequence().translate('DBSetting').'</h2></div>';
        echo '<div class="RequirementContent">';
        echo translate('DBSettingUpgradeIntro');
        echo '</div>';
    } else {
        if (empty($dbPrefixForm)) { //make sure there is a default value for db prefix
            $dbPrefixForm = '';
        }
        echo '<div class="RequirementHeading"><h2>'.display_step_sequence().translate('DBSetting').'</h2></div>';
        echo '<div class="RequirementContent">';
        echo translate('DBSettingIntro');
        echo '</div>';
    }

    ?>
</td>
</tr>
    <tr>
    <td>
    <table class="data_table_no_border">
        <tr>
            <td width="40%"><?php echo translate('DBHost'); ?> </td>
            <?php if ($installType == 'update'): ?>
            <td width="30%"><input type="hidden" name="dbHostForm"
                                   value="<?php echo htmlentities($dbHostForm); ?>"/><?php echo $dbHostForm; ?></td>
            <td width="30%">&nbsp;</td>
            <?php else: ?>
            <td width="30%">
                <input type="text" size="25" maxlength="50" name="dbHostForm" value="<?php echo htmlentities($dbHostForm); ?>" /></td>
            <td width="30%"><?php echo translate('EG').' localhost'; ?></td>
            <?php endif; ?>
        </tr>
        <tr>
            <?php
            //database user username
            $example_login = translate('EG').' root';
            display_database_parameter(
                $installType,
                translate('DBLogin'),
                'dbUsernameForm',
                $dbUsernameForm,
                $example_login
            );

            //database user password
            $example_password = translate('EG').' '.api_generate_password();
            display_database_parameter(
                $installType,
                translate('DBPassword'),
                'dbPassForm',
                $dbPassForm,
                $example_password
            );

            echo '<input type="hidden" name="enableTrackingForm" value="1" />';

            $style = '';
            if ($installType == INSTALL_TYPE_UPDATE) {
                $style = '';
            }

            //Database Name fix replace weird chars
            if ($installType != INSTALL_TYPE_UPDATE) {
                $dbNameForm = str_replace(array('-', '*', '$', ' ', '.'), '', $dbNameForm);
                $dbNameForm = api_replace_dangerous_char($dbNameForm);
            }

            display_database_parameter(
                $installType,
                translate('MainDB'),
                'dbNameForm',
                $dbNameForm,
                '&nbsp;',
                null,
                'id="optional_param1" '.$style
            );
            ?>
        <tr>
            <td></td>
            <td>
                <button type="submit" class="btn" name="step3"value="<?php echo translate('CheckDatabaseConnection'); ?>">
                    <?php echo translate('CheckDatabaseConnection'); ?></button>
            </td>
        </tr>
        <tr>
        <td>
            <?php

            $dbConnect = testDatabaseConnect($dbHostForm, $dbUsernameForm, $dbPassForm, $singleDbForm, $dbPrefixForm, $dbNameForm);

            $database_exists_text = '';
            if ($dbConnect) {
                $multipleDbCheck = Database::query("CREATE DATABASE ".mysql_real_escape_string($dbNameForm));
                if ($multipleDbCheck !== false) {
                    Database::query(
                        "DROP DATABASE IF EXISTS ".mysql_real_escape_string($dbNameForm)
                    );
                    $user_can_create_databases = true;
                }
                if ($user_can_create_databases) {
                    $database_exists_text = '<div class="normal-message">'.sprintf(
                        translate('DatabaseXWillBeCreated'),
                        $dbNameForm,
                        $dbUsernameForm
                    ).'</div>';
                } else {
                    $dbConnect = 0;
                    $database_exists_text = '<div class="warning-message">'.sprintf(
                        translate('DatabaseXCantBeCreatedUserXDoestHaveEnoughPermissions'),
                        $dbNameForm,
                        $dbUsernameForm
                    ).'</div>';
                }

            } else {
                echo '<div class="warning-message">'.sprintf(
                        translate('UserXCantHaveAccessInTheDatabaseX'),
                        $dbUsernameForm,
                        $dbNameForm
                    ).'</div>';
            }

            if ($dbConnect == 1): ?>
                <td colspan="2">
                    <?php echo $database_exists_text ?>
                    <div id="db_status" class="confirmation-message">


                        <div style="clear:both;"></div>
                    </div>
                </td>
                <?php else: ?>
                <td colspan="2">
                    <?php echo $database_exists_text ?>
                    <div id="db_status" style="float:left;" class="error-message">
                        <div style="float:left;">
                            <strong><?php echo translate('FailedConectionDatabase'); ?></strong><br/>

                        </div>
                    </div>
                </td>
                <?php endif; ?>
        </tr>
        <tr>
            <td>
                <button type="submit" name="step2" class="back"
                        value="&lt; <?php echo translate('Previous'); ?>"><?php echo translate('Previous'); ?></button>
            </td>
            <td>&nbsp;</td>
            <td align="right">
                <input type="hidden" name="is_executable" id="is_executable" value="-"/>
                <?php if ($dbConnect == 1) { ?>
                <button type="submit" class="btn next" name="step4"
                        value="<?php echo translate('Next'); ?> &gt;" <?php if ($dbConnect == 1) {
                    echo 'autofocus="autofocus"';
                } ?> /><?php echo translate('Next'); ?></button>
                <?php } else { ?>
                <button disabled="disabled" type="submit" class="btn next disabled" name="step4"
                        value="<?php echo translate('Next'); ?> &gt;"/><?php echo translate('Next'); ?></button>
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
function display_configuration_parameter(
    $install_type,
    $parameter_name,
    $form_field_name,
    $parameter_value,
    $display_when_update = 'true'
) {
    echo "<tr>";
    echo "<td>$parameter_name</td>";
    if ($install_type == INSTALL_TYPE_UPDATE && $display_when_update) {
        echo '<td><input type="hidden" name="'.$form_field_name.'" value="'.api_htmlentities(
            $parameter_value,
            ENT_QUOTES
        ).'" />'.$parameter_value."</td>\n";
    } else {
        echo '<td><input type="text" size="'.FORM_FIELD_DISPLAY_LENGTH.'" maxlength="'.MAX_FORM_FIELD_LENGTH.'" name="'.$form_field_name.'" value="'.api_htmlentities(
            $parameter_value,
            ENT_QUOTES
        ).'" '.($form_field_name == 'loginForm' ? 'autofocus="autofocus"' : '').' />'."</td>\n";
    }
    echo "</tr>";
}

/**
 * Displays step 4 of the installation - configuration settings about Chamilo itself.
 */
function display_configuration_settings_form(
    $installType,
    $urlForm,
    $languageForm,
    $emailForm,
    $adminFirstName,
    $adminLastName,
    $adminPhoneForm,
    $campusForm,
    $institutionForm,
    $institutionUrlForm,
    $encryptPassForm,
    $allowSelfReg,
    $allowSelfRegProf,
    $loginForm,
    $passForm
) {
    if ($installType != 'update' && empty($languageForm)) {
        $languageForm = $_SESSION['install_language'];
    }
    echo '<div class="RequirementHeading">';
    echo "<h2>".display_step_sequence().translate("CfgSetting")."</h2>";
    echo '</div>';
    echo '<div class="RequirementContent">';
    echo '<p>'.translate('ConfigSettingsInfo').' '.Display::label('config/configuration.php', 'info').'</p>';
    echo '</div>';

    echo '<fieldset>';
    echo '<legend>'.translate('Administrator').'</legend>';
    echo '<table class="data_table_no_border">';

    //Parameter 1: administrator's login

    display_configuration_parameter(
        $installType,
        translate('AdminLogin'),
        'loginForm',
        $loginForm,
        $installType == 'update'
    );

    //Parameter 2: administrator's password
    if ($installType != 'update') {
        display_configuration_parameter($installType, translate('AdminPass'), 'passForm', $passForm, false);
    }

    //Parameters 3 and 4: administrator's names
    if (api_is_western_name_order()) {
        display_configuration_parameter($installType, translate('AdminFirstName'), 'adminFirstName', $adminFirstName);
        display_configuration_parameter($installType, translate('AdminLastName'), 'adminLastName', $adminLastName);
    } else {
        display_configuration_parameter($installType, translate('AdminLastName'), 'adminLastName', $adminLastName);
        display_configuration_parameter($installType, translate('AdminFirstName'), 'adminFirstName', $adminFirstName);
    }

    //Parameter 3: administrator's email
    display_configuration_parameter($installType, translate('AdminEmail'), 'emailForm', $emailForm);

    //Parameter 6: administrator's telephone
    display_configuration_parameter($installType, translate('AdminPhone'), 'adminPhoneForm', $adminPhoneForm);

    echo '</table>';
    echo '</fieldset>';

    echo '<fieldset>';
    echo '<legend>'.translate('Platform').'</legend>';

    echo '<table class="data_table_no_border">';

    //First parameter: language
    echo "<tr>";
    echo '<td>'.translate('MainLang')."&nbsp;&nbsp;</td>";
    if ($installType == 'update') {
        echo '<td><input type="hidden" name="languageForm" value="'.api_htmlentities(
            $languageForm,
            ENT_QUOTES
        ).'" />'.$languageForm."</td>";

    } else { // new installation
        echo '<td>';
        display_language_selection_box('languageForm', $languageForm);
        echo "</td>\n";
    }
    echo "</tr>\n";

    //Second parameter: Chamilo URL
    echo "<tr>";
    echo '<td>'.translate('ChamiloURL').' (<font color="red">'.translate(
        'ThisFieldIsRequired'
    )."</font>)&nbsp;&nbsp;</td>";

    if ($installType == 'update') {
        echo '<td>'.api_htmlentities($urlForm, ENT_QUOTES)."</td>\n";
    } else {
        echo '<td><input type="text" size="40" maxlength="100" name="urlForm" value="'.api_htmlentities(
            $urlForm,
            ENT_QUOTES
        ).'" />'."</td>";
    }
    echo "</tr>";

    //Parameter 9: campus name
    display_configuration_parameter($installType, translate('CampusName'), 'campusForm', $campusForm);

    //Parameter 10: institute (short) name
    display_configuration_parameter($installType, translate('InstituteShortName'), 'institutionForm', $institutionForm);

    //Parameter 11: institute (short) name
    display_configuration_parameter($installType, translate('InstituteURL'), 'institutionUrlForm', $institutionUrlForm);

    ?>
<tr>
    <td><?php echo translate("EncryptMethodUserPass"); ?> :</td>
    <?php if ($installType == 'update') { ?>
    <td><input type="hidden" name="encryptPassForm"
               value="<?php echo $encryptPassForm; ?>"/><?php echo $encryptPassForm; ?></td>
    <?php } else { ?>
    <td>
        <div class="control-group">
            <label class="checkbox inline">
                <input class="checkbox" type="radio" name="encryptPassForm" value="sha1"
                       id="encryptPass1" <?php echo ($encryptPassForm == 'sha1') ? 'checked="checked" ' : ''; ?>/><?php echo 'sha1'; ?>
            </label>

            <label class="checkbox inline">
                <input class="checkbox" type="radio" name="encryptPassForm" value="md5"
                       id="encryptPass0" <?php echo $encryptPassForm == 1 ? 'checked="checked" ' : ''; ?>/><?php echo 'md5'; ?>
            </label>

            <label class="checkbox inline">
                <input class="checkbox" type="radio" name="encryptPassForm" value="none"
                       id="encryptPass2" <?php echo $encryptPassForm === '0' or $encryptPassForm === 0 ? 'checked="checked" ' : ''; ?>/><?php echo translate(
                'None'
            ); ?>
            </label>

        </div>
    </td>
    <?php } ?>
</tr>
<tr>
    <td><?php echo translate('AllowSelfReg'); ?> :</td>

    <?php if ($installType == 'update'): ?>
    <td><input type="hidden" name="allowSelfReg"
               value="<?php echo $allowSelfReg; ?>"/><?php echo $allowSelfReg ? translate('Yes') : translate('No'); ?>
    </td>
    <?php else: ?>
    <td>
        <div class="control-group">
            <label class="checkbox inline">
                <input class="checkbox" type="radio" name="allowSelfReg" value="1"
                       id="allowSelfReg1" <?php echo $allowSelfReg ? 'checked="checked" ' : ''; ?>/> <?php echo translate(
                'Yes'
            ); ?>
            </label>
            <label class="checkbox inline">
                <input class="checkbox" type="radio" name="allowSelfReg" value="0"
                       id="allowSelfReg0" <?php echo $allowSelfReg ? '' : 'checked="checked" '; ?>/><?php echo translate(
                'No'
            ); ?>
            </label>
        </div>
    </td>
    <?php endif; ?>

</tr>
<tr>
    <td><?php echo translate('AllowSelfRegProf'); ?> :</td>

    <?php if ($installType == 'update'): ?>
    <td><input type="hidden" name="allowSelfRegProf"
               value="<?php echo $allowSelfRegProf; ?>"/><?php echo $allowSelfRegProf ? translate('Yes') : translate(
        'No'
    ); ?></td>
    <?php else: ?>
    <td>
        <div class="control-group">
            <label class="checkbox inline">
                <input class="checkbox" type="radio" name="allowSelfRegProf" value="1"
                       id="allowSelfRegProf1" <?php echo $allowSelfRegProf ? 'checked="checked" ' : ''; ?>/>
                <?php echo translate('Yes'); ?>
            </label>
            <label class="checkbox inline">
                <input class="checkbox" type="radio" name="allowSelfRegProf" value="0"
                       id="allowSelfRegProf0" <?php echo $allowSelfRegProf ? '' : 'checked="checked" '; ?>/>
                <?php echo translate('No'); ?>
            </label>
        </div>
    </td>
    <?php endif; ?>
</tr>
<tr>
    <td>
        <button type="submit" class="btn back" name="step3" value="&lt; <?php echo translate('Previous'); ?>"/>
            <?php echo translate('Previous'); ?></button>
    </td>
    <td align="right">
        <input type="hidden" name="is_executable" id="is_executable" value="-"/>
        <button class="btn next" type="submit" name="step5" value="<?php echo translate('Next'); ?> &gt;"/>
            <?php echo translate('Next'); ?></button></td>
</tr>
</fieldset>
</table>
    <?php
}

/**
 * After installation is completed (step 6), this message is displayed.
 */
function display_after_install_message($installType)
{
    echo '<div class="RequirementContent">'.translate('FirstUseTip').'</div>';
    echo '<div class="warning-message">';
    echo '<strong>'.translate('SecurityAdvice').'</strong>';
    echo ': ';
    printf(translate('ToProtectYourSiteMakeXReadOnlyAndDeleteY'), 'main/inc/conf/', 'main/install/');
    echo '</div>';
    ?></form>
<br/>
<a class="btn btn-success btn-large btn-install" href="../../index.php" autofocus="autofocus"><?php echo translate(
    'GoToYourNewlyCreatedPortal'
); ?></a>
<?php
}

/**
 * This function return countries list from array (hardcoded)
 * @param   bool    (Optional) True for returning countries list with select html
 * @return  array|string countries list
 */
function get_countries_list_from_array($combo = false)
{
    $a_countries = array(
        "Afghanistan",
        "Albania",
        "Algeria",
        "Andorra",
        "Angola",
        "Antigua and Barbuda",
        "Argentina",
        "Armenia",
        "Australia",
        "Austria",
        "Azerbaijan",
        "Bahamas",
        "Bahrain",
        "Bangladesh",
        "Barbados",
        "Belarus",
        "Belgium",
        "Belize",
        "Benin",
        "Bhutan",
        "Bolivia",
        "Bosnia and Herzegovina",
        "Botswana",
        "Brazil",
        "Brunei",
        "Bulgaria",
        "Burkina Faso",
        "Burundi",
        "Cambodia",
        "Cameroon",
        "Canada",
        "Cape Verde",
        "Central African Republic",
        "Chad",
        "Chile",
        "China",
        "Colombi",
        "Comoros",
        "Congo (Brazzaville)",
        "Congo",
        "Costa Rica",
        "Cote d'Ivoire",
        "Croatia",
        "Cuba",
        "Cyprus",
        "Czech Republic",
        "Denmark",
        "Djibouti",
        "Dominica",
        "Dominican Republic",
        "East Timor (Timor Timur)",
        "Ecuador",
        "Egypt",
        "El Salvador",
        "Equatorial Guinea",
        "Eritrea",
        "Estonia",
        "Ethiopia",
        "Fiji",
        "Finland",
        "France",
        "Gabon",
        "Gambia, The",
        "Georgia",
        "Germany",
        "Ghana",
        "Greece",
        "Grenada",
        "Guatemala",
        "Guinea",
        "Guinea-Bissau",
        "Guyana",
        "Haiti",
        "Honduras",
        "Hungary",
        "Iceland",
        "India",
        "Indonesia",
        "Iran",
        "Iraq",
        "Ireland",
        "Israel",
        "Italy",
        "Jamaica",
        "Japan",
        "Jordan",
        "Kazakhstan",
        "Kenya",
        "Kiribati",
        "Korea, North",
        "Korea, South",
        "Kuwait",
        "Kyrgyzstan",
        "Laos",
        "Latvia",
        "Lebanon",
        "Lesotho",
        "Liberia",
        "Libya",
        "Liechtenstein",
        "Lithuania",
        "Luxembourg",
        "Macedonia",
        "Madagascar",
        "Malawi",
        "Malaysia",
        "Maldives",
        "Mali",
        "Malta",
        "Marshall Islands",
        "Mauritania",
        "Mauritius",
        "Mexico",
        "Micronesia",
        "Moldova",
        "Monaco",
        "Mongolia",
        "Morocco",
        "Mozambique",
        "Myanmar",
        "Namibia",
        "Nauru",
        "Nepa",
        "Netherlands",
        "New Zealand",
        "Nicaragua",
        "Niger",
        "Nigeria",
        "Norway",
        "Oman",
        "Pakistan",
        "Palau",
        "Panama",
        "Papua New Guinea",
        "Paraguay",
        "Peru",
        "Philippines",
        "Poland",
        "Portugal",
        "Qatar",
        "Romania",
        "Russia",
        "Rwanda",
        "Saint Kitts and Nevis",
        "Saint Lucia",
        "Saint Vincent",
        "Samoa",
        "San Marino",
        "Sao Tome and Principe",
        "Saudi Arabia",
        "Senegal",
        "Serbia and Montenegro",
        "Seychelles",
        "Sierra Leone",
        "Singapore",
        "Slovakia",
        "Slovenia",
        "Solomon Islands",
        "Somalia",
        "South Africa",
        "Spain",
        "Sri Lanka",
        "Sudan",
        "Suriname",
        "Swaziland",
        "Sweden",
        "Switzerland",
        "Syria",
        "Taiwan",
        "Tajikistan",
        "Tanzania",
        "Thailand",
        "Togo",
        "Tonga",
        "Trinidad and Tobago",
        "Tunisia",
        "Turkey",
        "Turkmenistan",
        "Tuvalu",
        "Uganda",
        "Ukraine",
        "United Arab Emirates",
        "United Kingdom",
        "United States",
        "Uruguay",
        "Uzbekistan",
        "Vanuatu",
        "Vatican City",
        "Venezuela",
        "Vietnam",
        "Yemen",
        "Zambia",
        "Zimbabwe"
    );

    $country_select = '';
    if ($combo) {
        $country_select = '<select id="country" name="country">';
        $country_select .= '<option value="">--- '.translate('SelectOne').' ---</option>';
        foreach ($a_countries as $country) {
            $country_select .= '<option value="'.$country.'">'.$country.'</option>';
        }
        $country_select .= '</select>';

        return $country_select;
    }

    return $a_countries;
}

/**
 * Locking settings that can't be changed in other portals
 */
function locking_settings()
{
    $access_url_locked_settings = api_get_locked_settings();
    $table = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
    foreach ($access_url_locked_settings as $setting) {
        $sql = "UPDATE $table SET access_url_locked = 1 WHERE variable = '$setting'";
        Database::query($sql);
    }
}

function update_dir_and_files_permissions()
{
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

function compare_setting_values($current_value, $wanted_value)
{
    $current_value_string = $current_value;
    $current_value = (float) $current_value;
    $wanted_value = (float) $wanted_value;

    if ($current_value >= $wanted_value) {
        return Display::label($current_value_string, 'success');
    } else {
        return Display::label($current_value_string, 'important');
    }
}


function check_course_script_interpretation($course_dir, $course_attempt_name, $file = 'test.php')
{
    $output = false;
    //Write in file
    $file_name = $course_dir.'/'.$file;
    $content = '<?php echo "123"; exit;';

    if (is_writable($file_name)) {
        if ($handler = @fopen($file_name, "w")) {
            //write content
            if (fwrite($handler , $content)) {

                $file = api_get_path(SYS_COURSE_PATH).$course_attempt_name.'/'.$file;
                if (file_exists($file)) {
                    return true;
                }

                //You can't access to a course file like this. You will be prompted to the installation process.
                //If you access
                $sock_errno = '';
                $sock_errmsg = '';

                $url = api_get_path(WEB_COURSE_PATH).$course_attempt_name.'/'.$file;

                $parsed_url = parse_url($url);
                //$scheme = isset($parsedUrl['scheme']) ? $parsedUrl['scheme'] : ''; //http
                $host = isset($parsed_url['host']) ? $parsed_url['host'] : '';
                $path = isset($parsed_url['path']) ? $parsed_url['path'] : '/';
                $port = isset($parsed_url['port']) ? $parsed_url['port'] : '80';

                //Check fsockopen
                if ($fp = @fsockopen(str_replace('http://', '', $url), -1, $sock_errno, $sock_errmsg, 60)) {
                    $out  = "GET $path HTTP/1.1\r\n";
                    $out .= "Host: $host\r\n";
                    $out .= "Connection: Close\r\n\r\n";

                    fwrite($fp, $out);
                    while (!feof($fp)) {
                        $result = str_replace("\r\n", '', fgets($fp, 128));
                        if (!empty($result) && $result == '123') {
                            $output = true;
                        }
                    }
                    fclose($fp);
                    //Check allow_url_fopen
                } elseif (ini_get('allow_url_fopen')) {
                    if ($fp = @fopen($url, 'r')) {
                        while ($result = fgets($fp, 1024)) {
                            if (!empty($result) && $result == '123') {
                                $output = true;
                            }
                        }
                        fclose($fp);
                    }
                    // Check if has support for cURL
                } elseif (function_exists('curl_init')) {
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_HEADER, 0);
                    curl_setopt($ch, CURLOPT_URL, $url);
                    //curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    $result = curl_exec($ch);
                    if (!empty($result) && $result == '123') {
                        $output = true;
                    }
                    curl_close($ch);
                }
            }
            @fclose($handler);
        }
    }

    return $output;
}

/* Executed only before create_course_tables() */
function drop_course_tables()
{
    $list = CourseManager::get_course_tables();
    foreach ($list as $table) {
        $sql = "DROP TABLE IF EXISTS ".DB_COURSE_PREFIX.$table;
        Database::query($sql);
    }
}

/**
 * Copy users files in the new data directory
 */
function movingFilesInDataFolder()
{
    $sysPath = api_get_path(SYS_PATH);
    $moveDirs = array(
        $sysPath.'searchdb' => api_get_path(SYS_DATA_PATH).'searchdb',
        $sysPath.'home' => api_get_path(SYS_DATA_PATH).'home',
        $sysPath.'courses' => api_get_path(SYS_DATA_PATH).'courses',
        $sysPath.'main/upload/users' => api_get_path(SYS_DATA_PATH).'upload/users',
    );
    error_log("Copying files to the new data folder");
    foreach ($moveDirs as $from => $to) {
        if (is_dir($from)) {
            $copy = "cp -r $from/* $to";
            error_log($copy);
            system($copy);
        }
    }
}
