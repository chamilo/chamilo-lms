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
define('COURSES_HTACCESS_FILENAME', 'htaccess.dist');
define('SYSTEM_CONFIG_FILENAME', 'configuration.dist.php');

/*      COMMON PURPOSE FUNCTIONS    */

/**
 * This function detects whether the system has been already installed.
 * It should be used for prevention from second running the installation
 * script and as a result - destroying a production system.
 * @return bool     The detected result;
 * @author Ivan Tcholakov, 2010;
 */
function isAlreadyInstalledSystem()
{
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
    if (isset($_configuration['system_version'])) {
        $current_version = trim($_configuration['system_version']);
    }

    // If the current version is old, upgrading is assumed, the installer goes ahead.
    return empty($current_version) ? false : version_compare($current_version, $new_version, '>=');
}

/**
 * This function checks if a php extension exists or not and returns an HTML status string.
 *
 * @param   string  $extensionName Name of the PHP extension to be checked
 * @param   string  $returnSuccess Text to show when extension is available (defaults to 'Yes')
 * @param   string  $returnFailure Text to show when extension is available (defaults to 'No')
 * @param   boolean $optional Whether this extension is optional (then show unavailable text in orange rather than red)
 * @return  string  HTML string reporting the status of this extension. Language-aware.
 * @author  Christophe Gesch??
 * @author  Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @author  Yannick Warnier <yannick.warnier@dokeos.com>
 */
function checkExtension($extensionName, $returnSuccess = 'Yes', $returnFailure = 'No', $optional = false)
{
    if (extension_loaded($extensionName)) {
        return Display::label($returnSuccess, 'success');
    } else {
        if ($optional) {
            return Display::label($returnFailure, 'warning');
        } else {
            return Display::label($returnFailure, 'important');
        }
    }
}

/**
 * This function checks whether a php setting matches the recommended value
 * @param   string $phpSetting A PHP setting to check
 * @param   string  $recommendedValue A recommended value to show on screen
 * @param   mixed  $returnSuccess What to show on success
 * @param   mixed  $returnFailure  What to show on failure
 * @return  string  A label to show
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 */
function checkPhpSetting($phpSetting, $recommendedValue, $returnSuccess = false, $returnFailure = false)
{
    $currentPhpValue = getPhpSetting($phpSetting);
    if ($currentPhpValue == $recommendedValue) {
        return Display::label($currentPhpValue.' '.$returnSuccess, 'success');
    } else {
        return Display::label($currentPhpValue.' '.$returnSuccess, 'important');
    }
}


/**
 * This function return the value of a php.ini setting if not "" or if exists,
 * otherwise return false
 * @param   string  $phpSetting The name of a PHP setting
 * @return  mixed   The value of the setting, or false if not found
 */
function checkPhpSettingExists($phpSetting)
{
    if (ini_get($phpSetting) != "") {
        return ini_get($phpSetting);
    }

    return false;
}

/**
 * Returns a textual value ('ON' or 'OFF') based on a requester 2-state ini- configuration setting.
 *
 * @param string $val a php ini value
 * @return boolean: ON or OFF
 * @author Joomla <http://www.joomla.org>
 */
function getPhpSetting($val)
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
function trueFalse($var)
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
 * @param   string  $folder Full path to a folder
 * @param   bool    $suggestion Whether to show a suggestion or not
 * @return  string
 */
function check_writable($folder, $suggestion = false)
{
    if (is_writable($folder)) {
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
 * We assume this function is called from install scripts that reside inside the install folder.
 */
function set_file_folder_permissions()
{
    @chmod('.', 0755); //set permissions on install dir
    @chmod('..', 0755); //set permissions on parent dir of install dir
}

/**
 * Add's a .htaccess file to the courses directory
 * @param string $url_append The path from your webroot to your chamilo root
 * @return bool Result of writing the file
 */
function write_courses_htaccess_file($url_append)
{
    $content = file_get_contents(dirname(__FILE__).'/'.COURSES_HTACCESS_FILENAME);
    $content = str_replace('{CHAMILO_URL_APPEND_PATH}', $url_append, $content);
    $fp = @fopen(api_get_path(SYS_PATH).'courses/.htaccess', 'w');
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
    $config['{DATABASE_MAIN}'] = $dbNameForm;
    $config['{ROOT_WEB}'] = $urlForm;
    $config['{ROOT_SYS}'] = $root_sys;
    $config['{URL_APPEND_PATH}'] = $urlAppendPath;
    $config['{PLATFORM_LANGUAGE}'] = $languageForm;
    $config['{SECURITY_KEY}'] = md5(uniqid(rand().time()));
    $config['{ENCRYPT_PASSWORD}'] = $encryptPassForm;

    $config['SESSION_LIFETIME'] = $session_lifetime;
    $config['{NEW_VERSION}'] = $new_version;
    $config['NEW_VERSION_STABLE'] = trueFalse($new_version_stable);

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
function & get_language_folder_list()
{
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
                if (is_file($dirname.$entries.'/install_disabled')) {
                    // Skip all languages that have this file present, just for
                    // the install process (languages incomplete)
                    continue;
                }
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
 * @param   string  $directory  Full path to a directory
 * @return  array   A list of files and dirs in the directory
 */
function my_directory_to_array($directory)
{
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
function get_config_param($param, $updatePath = '')
{
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
        // try to recover config file from Chamilo 1.9.x
        if (file_exists($updatePath.'main/inc/conf/configuration.php')) {
            $updateFromConfigFile = 'main/inc/conf/configuration.php';
        } else {
            // Give up recovering.
            //error_log('Chamilo Notice: Could not find previous config file at '.$updatePath.'main/inc/conf/configuration.php nor at '.$updatePath.'claroline/inc/conf/claro_main.conf.php in get_config_param(). Will start new config (in '.__FILE__.', line '.__LINE__.')', 0);
            return null;
        }
    }

    if (file_exists($updatePath.$updateFromConfigFile) &&
        !is_dir($updatePath.$updateFromConfigFile)
    ) {
        require $updatePath.$updateFromConfigFile;
        $config = new Zend\Config\Config($_configuration);
        return $config->get($param);
    }

    error_log('Config array could not be found in get_config_param()', 0);
    return null;

    /*if (file_exists($updatePath.$updateFromConfigFile)) {
        return $val;
    } else {
        error_log('Config array could not be found in get_config_param()', 0);
        return null;
    }*/
}

/*      DATABASE RELATED FUNCTIONS */

/**
 * Gets a configuration parameter from the database. Returns returns null on failure.
 * @param   string  $host DB Host
 * @param   string  $login DB login
 * @param   string  $pass DB pass
 * @param   string  $dbName DB name
 * @param   string  $param Name of param we want
 * @return  mixed   The parameter value or null if not found
 */
function get_config_param_from_db($param = '')
{
    if (($res = Database::query("SELECT * FROM settings_current WHERE variable = '$param'")) !== false) {
        if (Database::num_rows($res) > 0) {
            $row = Database::fetch_array($res);
            return $row['selected_value'];
        }
    }
    return null;
}

/**
 * In step 3. Tests establishing connection to the database server.
 * If it's a single database environment the function checks if the database exist.
 * If the database does not exist we check the creation permissions.
 * @param   string  $dbHostForm DB host
 * @param   string  $dbUsernameForm DB username
 * @param   string  $dbPassForm DB password
 * @return \Doctrine\ORM\EntityManager
 */
function testDbConnect($dbHostForm, $dbUsernameForm, $dbPassForm, $dbNameForm)
{
    $dbParams = array(
        'driver' => 'pdo_mysql',
        'host' => $dbHostForm,
        'user' => $dbUsernameForm,
        'password' => $dbPassForm,
        'dbname' => $dbNameForm
    );

    $database = new \Database();
    $database->connect($dbParams);

    return $database->getManager();
}

/*      DISPLAY FUNCTIONS */

/**
 * This function prints class=active_step $current_step=$param
 * @param   int $param  A step in the installer process
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
    return get_lang('Step'.$current_step).' &ndash; ';
}

/**
 * Displays a drop down box for selection the preferred language.
 */
function display_language_selection_box($name = 'language_list', $default_language = 'english')
{
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
function display_language_selection()
{ ?>
    <h2><?php get_lang('WelcomeToTheChamiloInstaller'); ?></h2>
    <div class="RequirementHeading">
        <h2><?php echo display_step_sequence(); ?>
            <?php echo get_lang('InstallationLanguage');?>
        </h2>
        <p><?php echo get_lang('PleaseSelectInstallationProcessLanguage'); ?>:</p>
        <form id="lang_form" method="post" action="<?php echo api_get_self(); ?>">
        <?php display_language_selection_box('language_list', api_get_interface_language()); ?>
        <button type="submit" name="step1" class="btn btn-success" value="<?php echo get_lang('Next'); ?>">
            <i class="fa fa-forward"> </i>
            <?php echo get_lang('Next'); ?></button>
        <input type="hidden" name="is_executable" id="is_executable" value="-" />
        </form>
        <br /><br />
    </div>
    <div class="RequirementHeading">
        <?php echo get_lang('YourLanguageNotThereContactUs'); ?>
    </div>
<?php
}

/**
 * This function displays the requirements for installing Chamilo.
 *
 * @param string $installType
 * @param boolean $badUpdatePath
 * @param string The updatePath given (if given)
 * @param array $update_from_version_8 The different subversions from version 1.9
 *
 * @author unknow
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 */
function display_requirements(
    $installType,
    $badUpdatePath,
    $updatePath = '',
    $update_from_version_8 = array()
) {
    global $_setting;
    echo '<div class="RequirementHeading"><h2>'.display_step_sequence().get_lang('Requirements')."</h2></div>";
    echo '<div class="RequirementText">';
    echo '<strong>'.get_lang('ReadThoroughly').'</strong><br />';
    echo get_lang('MoreDetails').' <a href="../../documentation/installation_guide.html" target="_blank">'.get_lang('ReadTheInstallGuide').'</a>.<br />'."\n";

    if ($installType == 'update') {
        echo get_lang('IfYouPlanToUpgradeFromOlderVersionYouMightWantToHaveAlookAtTheChangelog').'<br />';
    }
    echo '</div>';

    //  SERVER REQUIREMENTS
    echo '<div class="RequirementHeading"><h2>'.get_lang('ServerRequirements').'</h2>';

    $timezone = checkPhpSettingExists("date.timezone");
    if (!$timezone) {
        echo "<div class='warning-message'>".
            Display::return_icon('warning.png', get_lang('Warning'), '', ICON_SIZE_MEDIUM).
            get_lang("DateTimezoneSettingNotSet")."</div>";
    }

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
                <td class="requirements-value">'.checkExtension('session', get_lang('Yes'), get_lang('ExtensionSessionsNotAvailable')).'</td>
            </tr>
            <tr>
                <td class="requirements-item"><a href="http://php.net/manual/en/book.mysql.php" target="_blank">MySQL</a> '.get_lang('support').'</td>
                <td class="requirements-value">'.checkExtension('mysql', get_lang('Yes'), get_lang('ExtensionMySQLNotAvailable')).'</td>
            </tr>
            <tr>
                <td class="requirements-item"><a href="http://php.net/manual/en/book.zlib.php" target="_blank">Zlib</a> '.get_lang('support').'</td>
                <td class="requirements-value">'.checkExtension('zlib', get_lang('Yes'), get_lang('ExtensionZlibNotAvailable')).'</td>
            </tr>
            <tr>
                <td class="requirements-item"><a href="http://php.net/manual/en/book.pcre.php" target="_blank">Perl-compatible regular expressions</a> '.get_lang('support').'</td>
                <td class="requirements-value">'.checkExtension('pcre', get_lang('Yes'), get_lang('ExtensionPCRENotAvailable')).'</td>
            </tr>
            <tr>
                <td class="requirements-item"><a href="http://php.net/manual/en/book.xml.php" target="_blank">XML</a> '.get_lang('support').'</td>
                <td class="requirements-value">'.checkExtension('xml', get_lang('Yes'), get_lang('No')).'</td>
            </tr>
            <tr>
                <td class="requirements-item"><a href="http://php.net/manual/en/book.intl.php" target="_blank">Internationalization</a> '.get_lang('support').'</td>
                <td class="requirements-value">'.checkExtension('intl', get_lang('Yes'), get_lang('No')).'</td>
            </tr>
               <tr>
                <td class="requirements-item"><a href="http://php.net/manual/en/book.json.php" target="_blank">JSON</a> '.get_lang('support').'</td>
                <td class="requirements-value">'.checkExtension('json', get_lang('Yes'), get_lang('No')).'</td>
            </tr>

             <tr>
                <td class="requirements-item"><a href="http://php.net/manual/en/book.image.php" target="_blank">GD</a> '.get_lang('support').'</td>
                <td class="requirements-value">'.checkExtension('gd', get_lang('Yes'), get_lang('ExtensionGDNotAvailable')).'</td>
            </tr>

            <tr>
                <td class="requirements-item"><a href="http://php.net/manual/en/book.mbstring.php" target="_blank">Multibyte string</a> '.get_lang('support').' ('.get_lang('Optional').')</td>
                <td class="requirements-value">'.checkExtension('mbstring', get_lang('Yes'), get_lang('ExtensionMBStringNotAvailable'), true).'</td>
            </tr>
            <tr>
                <td class="requirements-item"><a href="http://php.net/manual/en/book.iconv.php" target="_blank">Iconv</a> '.get_lang('support').' ('.get_lang('Optional').')</td>
                <td class="requirements-value">'.checkExtension('iconv', get_lang('Yes'), get_lang('No'), true).'</td>
            </tr>
            <tr>
                <td class="requirements-item"><a href="http://php.net/manual/en/book.ldap.php" target="_blank">LDAP</a> '.get_lang('support').' ('.get_lang('Optional').')</td>
                <td class="requirements-value">'.checkExtension('ldap', get_lang('Yes'), get_lang('ExtensionLDAPNotAvailable'), true).'</td>
            </tr>
            <tr>
                <td class="requirements-item"><a href="http://xapian.org/" target="_blank">Xapian</a> '.get_lang('support').' ('.get_lang('Optional').')</td>
                <td class="requirements-value">'.checkExtension('xapian', get_lang('Yes'), get_lang('No'), true).'</td>
            </tr>

            <tr>
                <td class="requirements-item"><a href="http://php.net/manual/en/book.curl.php" target="_blank">cURL</a> '.get_lang('support').' ('.get_lang('Optional').')</td>
                <td class="requirements-value">'.checkExtension('curl', get_lang('Yes'), get_lang('No'), true).'</td>
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
                <td class="requirements-value">'.checkPhpSetting('safe_mode', 'OFF').'</td>
            </tr>
            <tr>
                <td class="requirements-item"><a href="http://php.net/manual/ref.errorfunc.php#ini.display-errors">Display Errors</a></td>
                <td class="requirements-recommended">'.Display::label('OFF', 'success').'</td>
                <td class="requirements-value">'.checkPhpSetting('display_errors', 'OFF').'</td>
            </tr>
            <tr>
                <td class="requirements-item"><a href="http://php.net/manual/ini.core.php#ini.file-uploads">File Uploads</a></td>
                <td class="requirements-recommended">'.Display::label('ON', 'success').'</td>
                <td class="requirements-value">'.checkPhpSetting('file_uploads', 'ON').'</td>
            </tr>
            <tr>
                <td class="requirements-item"><a href="http://php.net/manual/ref.info.php#ini.magic-quotes-gpc">Magic Quotes GPC</a></td>
                <td class="requirements-recommended">'.Display::label('OFF', 'success').'</td>
                <td class="requirements-value">'.checkPhpSetting('magic_quotes_gpc', 'OFF').'</td>
            </tr>
            <tr>
                <td class="requirements-item"><a href="http://php.net/manual/ref.info.php#ini.magic-quotes-runtime">Magic Quotes Runtime</a></td>
                <td class="requirements-recommended">'.Display::label('OFF', 'success').'</td>
                <td class="requirements-value">'.checkPhpSetting('magic_quotes_runtime', 'OFF').'</td>
            </tr>
            <tr>
                <td class="requirements-item"><a href="http://php.net/manual/security.globals.php">Register Globals</a></td>
                <td class="requirements-recommended">'.Display::label('OFF', 'success').'</td>
                <td class="requirements-value">'.checkPhpSetting('register_globals', 'OFF').'</td>
            </tr>
            <tr>
                <td class="requirements-item"><a href="http://php.net/manual/ref.session.php#ini.session.auto-start">Session auto start</a></td>
                <td class="requirements-recommended">'.Display::label('OFF', 'success').'</td>
                <td class="requirements-value">'.checkPhpSetting('session.auto_start', 'OFF').'</td>
            </tr>
            <tr>
                <td class="requirements-item"><a href="http://php.net/manual/ini.core.php#ini.short-open-tag">Short Open Tag</a></td>
                <td class="requirements-recommended">'.Display::label('OFF', 'success').'</td>
                <td class="requirements-value">'.checkPhpSetting('short_open_tag', 'OFF').'</td>
            </tr>
            <tr>
                <td class="requirements-item"><a href="http://www.php.net/manual/en/session.configuration.php#ini.session.cookie-httponly">Cookie HTTP Only</a></td>
                <td class="requirements-recommended">'.Display::label('ON', 'success').'</td>
                <td class="requirements-value">'.checkPhpSetting('session.cookie_httponly', 'ON').'</td>
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
    @unlink($course_dir.'/test.php');
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
    $file_course_test_was_created = false;

    if (is_dir($course_dir)) {
        foreach ($perms_fil as $perm) {
            if ($file_course_test_was_created == true) {
                break;
            }
            $r = @touch($course_dir.'/test.php',$perm);
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

    $_SESSION['permissions_for_new_directories'] = $_setting['permissions_for_new_directories'] = $dir_perm_verified;
    $_SESSION['permissions_for_new_files'] = $_setting['permissions_for_new_files'] = $fil_perm_verified;

    $dir_perm = Display::label('0'.decoct($dir_perm_verified), 'info');
    $file_perm = Display::label('0'.decoct($fil_perm_verified), 'info');

    $courseTestLabel = Display::label(get_lang('No'), 'important');

    if ($course_test_was_created && $file_course_test_was_created) {
        $courseTestLabel = Display::label(get_lang('Yes'), 'success');
    }

    if ($course_test_was_created && !$file_course_test_was_created) {
        $courseTestLabel = Display::label(
            sprintf(
                get_lang('InstallWarningCouldNotInterpretPHP'),
                api_get_path(WEB_COURSE_PATH).$course_attempt_name.'/test.php'
            ),
            'warning'
        );
    }

    if (!$course_test_was_created && !$file_course_test_was_created) {
        $courseTestLabel = Display::label(get_lang('No'), 'important');
    }

    echo '<table class="table">
            <tr>
                <td class="requirements-item">'.api_get_path(SYS_CODE_PATH).'inc/conf/</td>
                <td class="requirements-value">'.check_writable(api_get_path(SYS_CODE_PATH).'inc/conf/').'</td>
            </tr>
            <tr>
                <td class="requirements-item">'.api_get_path(SYS_CODE_PATH).'upload/users/</td>
                <td class="requirements-value">'.check_writable(api_get_path(SYS_CODE_PATH).'upload/users/').'</td>
            </tr>
            <tr>
                <td class="requirements-item">'.api_get_path(SYS_CODE_PATH).'upload/sessions/</td>
                <td class="requirements-value">'.check_writable(api_get_path(SYS_CODE_PATH).'upload/sessions/').'</td>
            </tr>
            <tr>
                <td class="requirements-item">'.api_get_path(SYS_CODE_PATH).'upload/courses/</td>
                <td class="requirements-value">'.check_writable(api_get_path(SYS_CODE_PATH).'upload/courses/').'</td>
            </tr>
            <tr>
                <td class="requirements-item">'.api_get_path(SYS_CODE_PATH).'default_course_document/images/</td>
                <td class="requirements-value">'.check_writable(api_get_path(SYS_CODE_PATH).'default_course_document/images/').'</td>
            </tr>
            <tr>
                <td class="requirements-item">'.api_get_path(SYS_ARCHIVE_PATH).'</td>
                <td class="requirements-value">'.check_writable(api_get_path(SYS_ARCHIVE_PATH)).'</td>
            </tr>
            <tr>
                <td class="requirements-item">'.api_get_path(SYS_DATA_PATH).'</td>
                <td class="requirements-value">'.check_writable(api_get_path(SYS_DATA_PATH)).'</td>
            </tr>
            <tr>
                <td class="requirements-item">'.api_get_path(SYS_COURSE_PATH).'</td>
                <td class="requirements-value">'.check_writable(api_get_path(SYS_COURSE_PATH)).' </td>
            </tr>
            <tr>
                <td class="requirements-item">'.get_lang('CourseTestWasCreated').'</td>
                <td class="requirements-value">'.$courseTestLabel.' </td>
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
                <td class="requirements-item">'.api_get_path(SYS_PATH).'home/</td>
                <td class="requirements-value">'.check_writable(api_get_path(SYS_PATH).'home/').'</td>
            </tr>
            <tr>
                <td class="requirements-item">'.api_get_path(SYS_CODE_PATH).'css/</td>
                <td class="requirements-value">'.check_writable(api_get_path(SYS_CODE_PATH).'css/', true).' ('.get_lang('SuggestionOnlyToEnableCSSUploadFeature').')</td>
            </tr>
            <tr>
                <td class="requirements-item">'.api_get_path(SYS_CODE_PATH).'lang/</td>
                <td class="requirements-value">'.check_writable(api_get_path(SYS_CODE_PATH).'lang/', true).' ('.get_lang('SuggestionOnlyToEnableSubLanguageFeature').')</td>
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
                Chamilo <?php echo implode('|', $update_from_version_8).' '.get_lang('HasNotBeenFoundInThatDir'); ?>.
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
                <button type="submit" class="btn btn-default" name="step1" value="&lt; <?php echo get_lang('Back'); ?>" ><i class="fa fa-backward"><?php echo get_lang('Back'); ?></i></button>
                <input type="hidden" name="is_executable" id="is_executable" value="-" />
                <button type="submit" class="btn btn-success" name="<?php echo (isset($_POST['step2_update_6']) ? 'step2_update_6' : 'step2_update_8'); ?>" value="<?php echo get_lang('Next'); ?> &gt;" ><i class="fa fa-forward"> </i> <?php echo get_lang('Next'); ?></button>
            </td>
            </tr>
            </table>
        <?php
    } else {
        $error = false;
        // First, attempt to set writing permissions if we don't have them yet
        $perm = api_get_permissions_for_new_directories();
        $perm_file = api_get_permissions_for_new_files();

        $notWritable = array();
        $curdir = getcwd();

        $checked_writable = api_get_path(CONFIGURATION_PATH);
        if (!is_writable($checked_writable)) {
            $notWritable[] = $checked_writable;
            @chmod($checked_writable, $perm);
        }

        $checked_writable = api_get_path(SYS_CODE_PATH).'upload/users/';
        if (!is_writable($checked_writable)) {
            $notWritable[] = $checked_writable;
            @chmod($checked_writable, $perm);
        }

        $checkedWritable = api_get_path(SYS_CODE_PATH).'upload/sessions/';
        if (!is_writable($checkedWritable)) {
            $notWritable[] = $checkedWritable;
            @chmod($checkedWritable, $perm);
        }

        $checkedWritable = api_get_path(SYS_CODE_PATH).'upload/courses/';
        if (!is_writable($checkedWritable)) {
            $notWritable[] = $checkedWritable;
            @chmod($checkedWritable, $perm);
        }

        $checked_writable = api_get_path(SYS_CODE_PATH).'default_course_document/images/';
        if (!is_writable($checked_writable)) {
            $notWritable[] = $checked_writable;
            @chmod($checked_writable, $perm);
        }

        $checked_writable = api_get_path(SYS_ARCHIVE_PATH);
        if (!is_writable($checked_writable)) {
            $notWritable[] = $checked_writable;
            @chmod($checked_writable, $perm);
        }

        $checked_writable = api_get_path(SYS_DATA_PATH);
        if (!is_writable($checked_writable)) {
            $notWritable[] = $checked_writable;
            @chmod($checked_writable, $perm);
        }

        $checked_writable = api_get_path(SYS_COURSE_PATH);
        if (!is_writable($checked_writable)) {
            $notWritable[] = $checked_writable;
            @chmod($checked_writable, $perm);
        }

        if ($course_test_was_created == false) {
            $error = true;
        }

        $checked_writable = api_get_path(SYS_PATH).'home/';
        if (!is_writable($checked_writable)) {
            $notWritable[] = realpath($checked_writable);
            @chmod($checked_writable, $perm);
        }

        $checked_writable = api_get_path(CONFIGURATION_PATH).'configuration.php';
        if (file_exists($checked_writable) && !is_writable($checked_writable)) {
            $notWritable[] = $checked_writable;
            @chmod($checked_writable, $perm_file);
        }

        // Second, if this fails, report an error

        //--> The user would have to adjust the permissions manually
        if (count($notWritable) > 0) {
            $error = true;
            echo '<div class="error-message">';
                echo '<center><h3>'.get_lang('Warning').'</h3></center>';
                printf(get_lang('NoWritePermissionPleaseReadInstallGuide'), '</font>
                <a href="../../documentation/installation_guide.html" target="blank">', '</a> <font color="red">');
            echo '</div>';
            echo '<ul>';
            foreach ($notWritable as $value) {
                echo '<li>'.$value.'</li>';
            }
            echo '</ul>';
        } elseif (file_exists(api_get_path(CONFIGURATION_PATH).'configuration.php')) {
            // Check wether a Chamilo configuration file already exists.
            echo '<div class="warning-message"><h4><center>';
            echo get_lang('WarningExistingLMSInstallationDetected');
            echo '</center></h4></div>';
        }

        // And now display the choice buttons (go back or install)
        ?>
        <p align="center" style="padding-top:15px">
        <button type="submit" name="step1" class="btn btn-default" onclick="javascript: window.location='index.php'; return false;" value="&lt; <?php echo get_lang('Previous'); ?>" ><i class="fa fa-backward"> </i> <?php echo get_lang('Previous'); ?></button>
        <button type="submit" name="step2_install" class="btn btn-success" value="<?php echo get_lang("NewInstallation"); ?>" <?php if ($error) echo 'disabled="disabled"'; ?> ><i class="fa fa-forward"> </i> <?php echo get_lang('NewInstallation'); ?></button>
        <input type="hidden" name="is_executable" id="is_executable" value="-" />
        <?php
        // Real code
        echo '<button type="submit" class="btn btn-default" name="step2_update_8" value="Upgrade from Chamilo 1.9.x"';
        if ($error) echo ' disabled="disabled"';
        echo ' ><i class="fa fa-forward"> </i> '.get_lang('UpgradeFromLMS19x').'</button>';

        echo '</p>';
    }
}

/**
 * Displays the license (GNU GPL) as step 2, with
 * - an "I accept" button named step3 to proceed to step 3;
 * - a "Back" button named step1 to go back to the first step.
 */

function display_license_agreement()
{
    echo '<div class="RequirementHeading"><h2>'.display_step_sequence().get_lang('Licence').'</h2>';
    echo '<p>'.get_lang('LMSLicenseInfo').'</p>';
    echo '<p><a href="../../documentation/license.html" target="_blank">'.get_lang('PrintVers').'</a></p>';
    echo '</div>';
    ?>
    <table>
        <tr><td>
            <pre style="overflow: auto; height: 150px; margin-top: 5px;" class="col-md-7">
                <?php echo api_htmlentities(@file_get_contents(api_get_path(SYS_PATH).'documentation/license.txt')); ?>
            </pre>
        </td>
        </tr>
        <tr><td>
            <p>
                <label class="checkbox">
                    <input type="checkbox" name="accept" id="accept_licence" value="1" />
                    <?php echo get_lang('IAccept'); ?>
                </label>
            </p>
            </td>
        </tr>
        <tr><td><p style="color:#666"><br /><?php echo get_lang('LMSMediaLicense'); ?></p></td></tr>
        <tr>
            <td>
            <table width="100%">
                <tr>
                    <td></td>
                    <td align="center">
                        <button type="submit" class="btn btn-default" name="step1" value="&lt; <?php echo get_lang('Previous'); ?>" ><i class="fa fa-backward"> </i> <?php echo get_lang('Previous'); ?></button>
                        <input type="hidden" name="is_executable" id="is_executable" value="-" />
                        <button type="submit" class="btn btn-success" name="step3" onclick="javascript: if(!document.getElementById('accept_licence').checked) { alert('<?php echo get_lang('YouMustAcceptLicence')?>');return false;}" value="<?php echo get_lang('Next'); ?> &gt;" ><i class="fa fa-forward"> </i> <?php echo get_lang('Next'); ?></button>
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
function get_contact_registration_form()
{

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
            <div class="controls"><input id="person_email" type="text" name="person_email" size="30" /></div>
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
            <div class="controls"><button type="button" class="btn btn-default" onclick="javascript:send_contact_information();" value="'.get_lang('SendInformation').'" ><i class="fa fa-floppy-o"> </i> '.get_lang('SendInformation').'</button></div>
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
function displayDatabaseParameter(
    $installType,
    $parameterName,
    $formFieldName,
    $parameterValue,
    $extra_notice,
    $displayWhenUpdate = true,
    $tr_attribute = ''
) {
    echo "<tr ".$tr_attribute.">";
    echo "<td>$parameterName&nbsp;&nbsp;</td>";

    if ($installType == INSTALL_TYPE_UPDATE && $displayWhenUpdate) {
        echo '<td><input type="hidden" name="'.$formFieldName.'" id="'.$formFieldName.'" value="'.api_htmlentities($parameterValue).'" />'.$parameterValue."</td>";
    } else {
        $inputType = $formFieldName == 'dbPassForm' ? 'password' : 'text';

        //Slightly limit the length of the database prefix to avoid having to cut down the databases names later on
        $maxLength = $formFieldName == 'dbPrefixForm' ? '15' : MAX_FORM_FIELD_LENGTH;
        if ($installType == INSTALL_TYPE_UPDATE) {
            echo '<input type="hidden" name="'.$formFieldName.'" id="'.$formFieldName.'" value="'.api_htmlentities($parameterValue).'" />';
            echo '<td>'.api_htmlentities($parameterValue)."</td>";
        } else {
            echo '<td><input type="'.$inputType.'" size="'.DATABASE_FORM_FIELD_DISPLAY_LENGTH.'" maxlength="'.$maxLength.'" name="'.$formFieldName.'" id="'.$formFieldName.'" value="'.api_htmlentities($parameterValue).'" />'."</td>";
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
    $dbNameForm
) {
    if ($installType == 'update') {
        global $_configuration;
        $dbHostForm         = $_configuration['db_host'];
        $dbUsernameForm     = $_configuration['db_user'];
        $dbPassForm         = $_configuration['db_password'];
        $dbNameForm         = $_configuration['main_database'];

        echo '<div class="RequirementHeading"><h2>' . display_step_sequence() .get_lang('DBSetting') . '</h2></div>';
        echo '<div class="RequirementContent">';
        echo get_lang('DBSettingUpgradeIntro');
        echo '</div>';
    } else {
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
    displayDatabaseParameter($installType, get_lang('DBLogin'), 'dbUsernameForm', $dbUsernameForm, $example_login);

    //database user password
    $example_password = get_lang('EG').' '.api_generate_password();
    displayDatabaseParameter($installType, get_lang('DBPassword'), 'dbPassForm', $dbPassForm, $example_password);

    //Database Name fix replace weird chars
    if ($installType != INSTALL_TYPE_UPDATE) {
        $dbNameForm = str_replace(array('-','*', '$', ' ', '.'), '', $dbNameForm);
        $dbNameForm = replace_dangerous_char($dbNameForm);
    }

    displayDatabaseParameter(
        $installType,
        get_lang('MainDB'),
        'dbNameForm',
        $dbNameForm,
        '&nbsp;',
        null,
        'id="optional_param1"'
    );

    if ($installType != INSTALL_TYPE_UPDATE) {
    ?>
    <tr>
        <td></td>
        <td>
            <button type="submit" class="btn btn-primary" name="step3" value="step3">
                <i class="fa fa-refresh"> </i>
                <?php echo get_lang('CheckDatabaseConnection'); ?>
            </button>
        </td>
    </tr>
    <?php } ?>

    <tr>
        <td>
        <?php

        $database_exists_text = '';
        $manager = null;
        try {
            $manager = testDbConnect(
                $dbHostForm,
                $dbUsernameForm,
                $dbPassForm,
                null
            );
            $databases = $manager->getConnection()->getSchemaManager()->listDatabases();
            if (in_array($dbNameForm, $databases)) {
                $database_exists_text = '<div class="warning-message">'.get_lang('ADatabaseWithTheSameNameAlreadyExists').'</div>';
            }
        } catch (Exception $e) {
            $database_exists_text = $e->getMessage();
        }

        if ($manager->getConnection()->isConnected()): ?>
        <td colspan="2">
            <?php echo $database_exists_text ?>
            <div id="db_status" class="confirmation-message">
                Database host: <strong><?php echo $manager->getConnection()->getHost(); ?></strong><br />
                Database driver: <strong><?php echo $manager->getConnection()->getDriver()->getName(); ?></strong><br />
                <div style="clear:both;"></div>
            </div>
        </td>
        <?php else: ?>
        <td colspan="2">
            <?php echo $database_exists_text ?>
            <div id="db_status" style="float:left;" class="error-message">
                <div style="float:left;">
                    <strong><?php echo get_lang('FailedConectionDatabase'); ?></strong><br />
                </div>
            </div>
        </td>
        <?php endif; ?>
    </tr>
    <tr>
      <td>
          <button type="submit" name="step2" class="btn btn-default" value="&lt; <?php echo get_lang('Previous'); ?>" >
          <i class="fa fa-backward"> </i> <?php echo get_lang('Previous'); ?>
          </button>
      </td>
      <td>&nbsp;</td>
      <td align="right">
          <input type="hidden" name="is_executable" id="is_executable" value="-" />
           <?php if ($manager) { ?>
            <button type="submit"  class="btn btn-success" name="step4" value="<?php echo get_lang('Next'); ?> &gt;" >
                <i class="fa fa-forward"> </i> <?php echo get_lang('Next'); ?>
            </button>
          <?php } else { ?>
            <button disabled="disabled" type="submit" class="btn btn-success disabled" name="step4" value="<?php echo get_lang('Next'); ?> &gt;" >
                <i class="fa fa-forward"> </i> <?php echo get_lang('Next'); ?>
            </button>
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
    $installType,
    $parameterName,
    $formFieldName,
    $parameterValue,
    $displayWhenUpdate = 'true'
) {
    echo "<tr>";
    echo "<td>$parameterName</td>";
    if ($installType == INSTALL_TYPE_UPDATE && $displayWhenUpdate) {
        echo '<td><input type="hidden" name="'.$formFieldName.'" value="'.api_htmlentities($parameterValue, ENT_QUOTES).'" />'.$parameterValue."</td>\n";
    } else {
        echo '<td><input type="text" size="'.FORM_FIELD_DISPLAY_LENGTH.'" maxlength="'.MAX_FORM_FIELD_LENGTH.'" name="'.$formFieldName.'" value="'.api_htmlentities($parameterValue, ENT_QUOTES).'" />'."</td>\n";
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
    echo "<h2>" . display_step_sequence() . get_lang("CfgSetting") . "</h2>";
    echo '</div>';
    echo '<div class="RequirementContent">';
    echo '<p>'.get_lang('ConfigSettingsInfo').' <strong>main/inc/conf/configuration.php</strong></p>';
    echo '</div>';

    echo '<fieldset>';
    echo '<legend>'.get_lang('Administrator').'</legend>';
    echo '<table class="data_table_no_border">';

    // Parameter 1: administrator's login

    display_configuration_parameter($installType, get_lang('AdminLogin'), 'loginForm', $loginForm, $installType == 'update');

    // Parameter 2: administrator's password
    if ($installType != 'update') {
        display_configuration_parameter($installType, get_lang('AdminPass'), 'passForm', $passForm, false);
    }

    // Parameters 3 and 4: administrator's names
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
              <label class="radio inline">
                  <input  type="radio" name="encryptPassForm" value="sha1" id="encryptPass1" <?php echo ($encryptPassForm == 'sha1') ? 'checked="checked" ': ''; ?>/><?php echo 'sha1'; ?>
              </label>

              <label class="radio inline">
                  <input type="radio" name="encryptPassForm" value="md5" id="encryptPass0" <?php echo $encryptPassForm == 1 ? 'checked="checked" ' : ''; ?>/><?php echo 'md5'; ?>
              </label>

              <label class="radio inline">
                  <input type="radio" name="encryptPassForm" value="none" id="encryptPass2" <?php echo $encryptPassForm === '0' or $encryptPassForm === 0 ? 'checked="checked" ':''; ?>/><?php echo get_lang('None'); ?>
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
            <label class="radio inline">
                <input type="radio" name="allowSelfReg" value="1" id="allowSelfReg1" <?php echo $allowSelfReg ? 'checked="checked" ' : ''; ?>/> <?php echo get_lang('Yes'); ?>
            </label>
            <label class="radio inline">
                <input type="radio" name="allowSelfReg" value="0" id="allowSelfReg0" <?php echo $allowSelfReg ? '' : 'checked="checked" '; ?>/><?php echo get_lang('No'); ?>
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
            <label class="radio inline">
                <input type="radio" name="allowSelfRegProf" value="1" id="allowSelfRegProf1" <?php echo $allowSelfRegProf ? 'checked="checked" ' : ''; ?>/>
            <?php echo get_lang('Yes'); ?>
            </label>
            <label class="radio inline">
                <input type="radio" name="allowSelfRegProf" value="0" id="allowSelfRegProf0" <?php echo $allowSelfRegProf ? '' : 'checked="checked" '; ?>/>
            <?php echo get_lang('No'); ?>
            </label>
          </div>
      </td>
      <?php endif; ?>

    </tr>
    <tr>
        <td>
            <button type="submit" class="btn btn-default" name="step3" value="&lt; <?php echo get_lang('Previous'); ?>" ><i class="fa fa-backward"> </i> <?php echo get_lang('Previous'); ?></button>
        </td>
        <td align="right">
            <input type="hidden" name="is_executable" id="is_executable" value="-" />
            <button class="btn btn-success" type="submit" name="step5" value="<?php echo get_lang('Next'); ?> &gt;" ><i class="fa fa-forward"> </i> <?php echo get_lang('Next'); ?></button></td>
    </tr>
    </table>
    </fieldset>
    <?php
}

/**
 * After installation is completed (step 6), this message is displayed.
 */
function display_after_install_message($installType)
{
    echo '<div class="RequirementContent">'.get_lang('FirstUseTip').'</div>';
    echo '<div class="warning-message">';
    echo '<strong>'.get_lang('SecurityAdvice').'</strong>';
    echo ': ';
    printf(get_lang('ToProtectYourSiteMakeXReadOnlyAndDeleteY'), 'main/inc/conf/', 'main/install/');
    echo '</div>';
    ?></form>
    <br />
    <a class="btn btn-success btn-large btn-install" href="../../index.php">
        <?php echo get_lang('GoToYourNewlyCreatedPortal'); ?>
    </a>
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
 * Lock settings that can't be changed in other portals
 */
function lockSettings()
{
    $access_url_locked_settings = api_get_locked_settings();
    $table = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
    foreach ($access_url_locked_settings as $setting) {
        $sql = "UPDATE $table SET access_url_locked = 1 WHERE variable  = '$setting'";
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
    $current_value = (float)$current_value;
    $wanted_value = (float)$wanted_value;

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
        if ($handler = @fopen($file_name, "w")) {
            //write content
            if (fwrite($handler, $content)) {
                $sock_errno = '';
                $sock_errmsg = '';
                $url = api_get_path(WEB_COURSE_PATH).$course_attempt_name.'/'.$file;

                $parsed_url = parse_url($url);
                //$scheme = isset($parsedUrl['scheme']) ? $parsedUrl['scheme'] : ''; //http
                $host = isset($parsed_url['host']) ? $parsed_url['host'] : '';
                // Patch if the host is the default host and is used through
                // the IP address (sometimes the host is not taken correctly
                // in this case)
                if (empty($host) && !empty($_SERVER['HTTP_HOST'])) {
                    $host = $_SERVER['HTTP_HOST'];
                    $url = preg_replace('#:///#', '://'.$host.'/', $url);
                }
                $path = isset($parsed_url['path']) ? $parsed_url['path'] : '/';
                $port = isset($parsed_url['port']) ? $parsed_url['port'] : '80';

                //Check fsockopen (doesn't work with https)
                if ($fp = @fsockopen(str_replace('http://', '', $url), -1, $sock_errno, $sock_errmsg, 60)) {
                    $out  = "GET $path HTTP/1.1\r\n";
                    $out .= "Host: $host\r\n";
                    $out .= "Connection: Close\r\n\r\n";

                    fwrite($fp, $out);
                    while (!feof($fp)) {
                        $result = str_replace("\r\n", '',fgets($fp, 128));
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
                    $result = curl_exec ($ch);
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


function installSettings(
    $organizationName,
    $organizationUrl,
    $campusName,
    $adminEmail,
    $adminLastName,
    $adminFirstName,
    $language,
    $allowRegistration,
    $allowTeacherSelfRegistration
) {
    $sql = "
        INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES
        ('Institution',NULL,'textfield','Platform','$organizationName','InstitutionTitle','InstitutionComment','platform',NULL, 1),
        ('InstitutionUrl',NULL,'textfield','Platform','$organizationUrl','InstitutionUrlTitle','InstitutionUrlComment',NULL,NULL, 1),
        ('siteName',NULL,'textfield','Platform','$campusName','SiteNameTitle','SiteNameComment',NULL,NULL, 1),
        ('emailAdministrator',NULL,'textfield','Platform','$adminEmail','emailAdministratorTitle','emailAdministratorComment',NULL,NULL, 1),
        ('administratorSurname',NULL,'textfield','Platform','$adminLastName','administratorSurnameTitle','administratorSurnameComment',NULL,NULL, 1),
        ('administratorName',NULL,'textfield','Platform','$adminFirstName','administratorNameTitle','administratorNameComment',NULL,NULL, 1),
        ('show_administrator_data',NULL,'radio','Platform','true','ShowAdministratorDataTitle','ShowAdministratorDataComment',NULL,NULL, 1),
        ('show_tutor_data',NULL,'radio','Session','true','ShowTutorDataTitle','ShowTutorDataComment',NULL,NULL, 1),
        ('show_teacher_data',NULL,'radio','Platform','true','ShowTeacherDataTitle','ShowTeacherDataComment',NULL,NULL, 1),
        ('homepage_view',NULL,'radio','Course','activity_big','HomepageViewTitle','HomepageViewComment',NULL,NULL, 1),
        ('show_toolshortcuts',NULL,'radio','Course','false','ShowToolShortcutsTitle','ShowToolShortcutsComment',NULL,NULL, 0),
        ('allow_group_categories',NULL,'radio','Course','false','AllowGroupCategories','AllowGroupCategoriesComment',NULL,NULL, 0),
        ('server_type',NULL,'radio','Platform','production','ServerStatusTitle','ServerStatusComment',NULL,NULL, 0),
        ('platformLanguage',NULL,'link','Languages','$language','PlatformLanguageTitle','PlatformLanguageComment',NULL,NULL, 0),
        ('showonline','world','checkbox','Platform','true','ShowOnlineTitle','ShowOnlineComment',NULL,'ShowOnlineWorld', 0),
        ('showonline','users','checkbox','Platform','true','ShowOnlineTitle','ShowOnlineComment',NULL,'ShowOnlineUsers', 0),
        ('showonline','course','checkbox','Platform','true','ShowOnlineTitle','ShowOnlineComment',NULL,'ShowOnlineCourse', 0),
        ('profile','name','checkbox','User','false','ProfileChangesTitle','ProfileChangesComment',NULL,'name', 0),
        ('profile','officialcode','checkbox','User','false','ProfileChangesTitle','ProfileChangesComment',NULL,'officialcode', 0),
        ('profile','email','checkbox','User','false','ProfileChangesTitle','ProfileChangesComment',NULL,'Email', 0),
        ('profile','picture','checkbox','User','true','ProfileChangesTitle','ProfileChangesComment',NULL,'UserPicture', 0),
        ('profile','login','checkbox','User','false','ProfileChangesTitle','ProfileChangesComment',NULL,'Login', 0),
        ('profile','password','checkbox','User','true','ProfileChangesTitle','ProfileChangesComment',NULL,'UserPassword', 0),
        ('profile','language','checkbox','User','true','ProfileChangesTitle','ProfileChangesComment',NULL,'Language', 0),
        ('default_document_quotum',NULL,'textfield','Course','100000000','DefaultDocumentQuotumTitle','DefaultDocumentQuotumComment',NULL,NULL, 0),
        ('registration','officialcode','checkbox','User','false','RegistrationRequiredFormsTitle','RegistrationRequiredFormsComment',NULL,'OfficialCode', 0),
        ('registration','email','checkbox','User','true','RegistrationRequiredFormsTitle','RegistrationRequiredFormsComment',NULL,'Email', 0),
        ('registration','language','checkbox','User','true','RegistrationRequiredFormsTitle','RegistrationRequiredFormsComment',NULL,'Language', 0),
        ('default_group_quotum',NULL,'textfield','Course','5000000','DefaultGroupQuotumTitle','DefaultGroupQuotumComment',NULL,NULL, 0),
        ('allow_registration',NULL,'radio','Platform','$allowRegistration','AllowRegistrationTitle','AllowRegistrationComment',NULL,NULL, 0),
        ('allow_registration_as_teacher',NULL,'radio','Platform','$allowTeacherSelfRegistration','AllowRegistrationAsTeacherTitle','AllowRegistrationAsTeacherComment',NULL,NULL, 0),
        ('allow_lostpassword',NULL,'radio','Platform','true','AllowLostPasswordTitle','AllowLostPasswordComment',NULL,NULL, 0),
        ('allow_user_headings',NULL,'radio','Course','false','AllowUserHeadings','AllowUserHeadingsComment',NULL,NULL, 0),
        ('course_create_active_tools','course_description','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'CourseDescription', 0),
        ('course_create_active_tools','agenda','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'Agenda', 0),
        ('course_create_active_tools','documents','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'Documents', 0),
        ('course_create_active_tools','learning_path','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'LearningPath', 0),
        ('course_create_active_tools','links','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'Links', 0),
        ('course_create_active_tools','announcements','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'Announcements', 0),
        ('course_create_active_tools','forums','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'Forums', 0),
        ('course_create_active_tools','dropbox','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'Dropbox', 0),
        ('course_create_active_tools','quiz','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'Quiz', 0),
        ('course_create_active_tools','users','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'Users', 0),
        ('course_create_active_tools','groups','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'Groups', 0),
        ('course_create_active_tools','chat','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'Chat', 0),
        ('course_create_active_tools','online_conference','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'OnlineConference', 0),
        ('course_create_active_tools','student_publications','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'StudentPublications', 0),
        ('allow_personal_agenda',NULL,'radio','User','true','AllowPersonalAgendaTitle','AllowPersonalAgendaComment',NULL,NULL, 0),
        ('display_coursecode_in_courselist',NULL,'radio','Platform','false','DisplayCourseCodeInCourselistTitle','DisplayCourseCodeInCourselistComment',NULL,NULL, 0),
        ('display_teacher_in_courselist',NULL,'radio','Platform','true','DisplayTeacherInCourselistTitle','DisplayTeacherInCourselistComment',NULL,NULL, 0),
        ('permanently_remove_deleted_files',NULL,'radio','Tools','false','PermanentlyRemoveFilesTitle','PermanentlyRemoveFilesComment',NULL,NULL, 0),
        ('dropbox_allow_overwrite',NULL,'radio','Tools','true','DropboxAllowOverwriteTitle','DropboxAllowOverwriteComment',NULL,NULL, 0),
        ('dropbox_max_filesize',NULL,'textfield','Tools','100000000','DropboxMaxFilesizeTitle','DropboxMaxFilesizeComment',NULL,NULL, 0),
        ('dropbox_allow_just_upload',NULL,'radio','Tools','true','DropboxAllowJustUploadTitle','DropboxAllowJustUploadComment',NULL,NULL, 0),
        ('dropbox_allow_student_to_student',NULL,'radio','Tools','true','DropboxAllowStudentToStudentTitle','DropboxAllowStudentToStudentComment',NULL,NULL, 0),
        ('dropbox_allow_group',NULL,'radio','Tools','true','DropboxAllowGroupTitle','DropboxAllowGroupComment',NULL,NULL, 0),
        ('dropbox_allow_mailing',NULL,'radio','Tools','false','DropboxAllowMailingTitle','DropboxAllowMailingComment',NULL,NULL, 0),
        ('administratorTelephone',NULL,'textfield','Platform','(000) 001 02 03','administratorTelephoneTitle','administratorTelephoneComment',NULL,NULL, 1),
        ('extended_profile',NULL,'radio','User','false','ExtendedProfileTitle','ExtendedProfileComment',NULL,NULL, 0),
        ('student_view_enabled',NULL,'radio','Platform','true','StudentViewEnabledTitle','StudentViewEnabledComment',NULL,NULL, 0),
        ('show_navigation_menu',NULL,'radio','Course','false','ShowNavigationMenuTitle','ShowNavigationMenuComment',NULL,NULL, 0),
        ('enable_tool_introduction',NULL,'radio','course','false','EnableToolIntroductionTitle','EnableToolIntroductionComment',NULL,NULL, 0),
        ('page_after_login', NULL, 'radio','Platform','user_portal.php', 'PageAfterLoginTitle','PageAfterLoginComment', NULL, NULL, 0),
        ('time_limit_whosonline', NULL, 'textfield','Platform','30', 'TimeLimitWhosonlineTitle','TimeLimitWhosonlineComment', NULL, NULL, 0),
        ('breadcrumbs_course_homepage', NULL, 'radio','Course','course_title', 'BreadCrumbsCourseHomepageTitle','BreadCrumbsCourseHomepageComment', NULL, NULL, 0),
        ('example_material_course_creation', NULL, 'radio','Platform','true', 'ExampleMaterialCourseCreationTitle','ExampleMaterialCourseCreationComment', NULL, NULL, 0),
        ('account_valid_duration',NULL, 'textfield','Platform','3660', 'AccountValidDurationTitle','AccountValidDurationComment', NULL, NULL, 0),
        ('use_session_mode', NULL, 'radio','Session','true', 'UseSessionModeTitle','UseSessionModeComment', NULL, NULL, 0),
        ('allow_email_editor', NULL, 'radio', 'Tools', 'false', 'AllowEmailEditorTitle', 'AllowEmailEditorComment', NULL, NULL, 0),
        ('registered', NULL, 'textfield', NULL, 'false', NULL, NULL, NULL, NULL, 0),
        ('donotlistcampus', NULL, 'textfield', NULL, 'false', NULL, NULL, NULL, NULL,0 ),
        ('show_email_addresses', NULL,'radio','Platform','false','ShowEmailAddresses','ShowEmailAddressesComment',NULL,NULL, 1),
        ('profile','phone','checkbox','User','false','ProfileChangesTitle','ProfileChangesComment',NULL,'phone', 0),
        ('service_visio', 'active', 'radio',NULL,'false', 'VisioEnable','', NULL, NULL, 0),
        ('service_visio', 'visio_host', 'textfield',NULL,'', 'VisioHost','', NULL, NULL, 0),
        ('service_visio', 'visio_port', 'textfield',NULL,'1935', 'VisioPort','', NULL, NULL, 0),
        ('service_visio', 'visio_pass', 'textfield',NULL,'', 'VisioPassword','', NULL, NULL, 0),
        ('service_ppt2lp', 'active', 'radio',NULL,'false', 'ppt2lp_actived','', NULL, NULL, 0),
        ('service_ppt2lp', 'host', 'textfield', NULL, NULL, 'Host', NULL, NULL, NULL, 0),
        ('service_ppt2lp', 'port', 'textfield', NULL, 2002, 'Port', NULL, NULL, NULL, 0),
        ('service_ppt2lp', 'user', 'textfield', NULL, NULL, 'UserOnHost', NULL, NULL, NULL, 0),
        ('service_ppt2lp', 'ftp_password', 'textfield', NULL, NULL, 'FtpPassword', NULL, NULL, NULL, 0),
        ('service_ppt2lp', 'path_to_lzx', 'textfield', NULL, NULL, '', NULL, NULL, NULL, 0),
        ('service_ppt2lp', 'size', 'radio', NULL, '720x540', '', NULL, NULL, NULL, 0),
        ('stylesheets', NULL, 'textfield','stylesheets','chamilo','',NULL, NULL, NULL, 1),
        ('upload_extensions_list_type', NULL, 'radio', 'Security', 'blacklist', 'UploadExtensionsListType', 'UploadExtensionsListTypeComment', NULL, NULL, 0),
        ('upload_extensions_blacklist', NULL, 'textfield', 'Security', '', 'UploadExtensionsBlacklist', 'UploadExtensionsBlacklistComment', NULL, NULL, 0),
        ('upload_extensions_whitelist', NULL, 'textfield', 'Security', 'htm;html;jpg;jpeg;gif;png;swf;avi;mpg;mpeg;mov;flv;doc;docx;xls;xlsx;ppt;pptx;odt;odp;ods;pdf', 'UploadExtensionsWhitelist', 'UploadExtensionsWhitelistComment', NULL, NULL, 0),
        ('upload_extensions_skip', NULL, 'radio', 'Security', 'true', 'UploadExtensionsSkip', 'UploadExtensionsSkipComment', NULL, NULL, 0),
        ('upload_extensions_replace_by', NULL, 'textfield', 'Security', 'dangerous', 'UploadExtensionsReplaceBy', 'UploadExtensionsReplaceByComment', NULL, NULL, 0),
        ('show_number_of_courses', NULL, 'radio','Platform','false', 'ShowNumberOfCourses','ShowNumberOfCoursesComment', NULL, NULL, 0),
        ('show_empty_course_categories', NULL, 'radio','Platform','true', 'ShowEmptyCourseCategories','ShowEmptyCourseCategoriesComment', NULL, NULL, 0),
        ('show_back_link_on_top_of_tree', NULL, 'radio','Platform','false', 'ShowBackLinkOnTopOfCourseTree','ShowBackLinkOnTopOfCourseTreeComment', NULL, NULL, 0),
        ('show_different_course_language', NULL, 'radio','Platform','true', 'ShowDifferentCourseLanguage','ShowDifferentCourseLanguageComment', NULL, NULL, 1),
        ('split_users_upload_directory', NULL, 'radio','Tuning','true', 'SplitUsersUploadDirectory','SplitUsersUploadDirectoryComment', NULL, NULL, 0),
        ('hide_dltt_markup', NULL, 'radio','Languages','true', 'HideDLTTMarkup','HideDLTTMarkupComment', NULL, NULL, 0),
        ('display_categories_on_homepage',NULL,'radio','Platform','false','DisplayCategoriesOnHomepageTitle','DisplayCategoriesOnHomepageComment',NULL,NULL, 1),
        ('permissions_for_new_directories', NULL, 'textfield', 'Security', '0777', 'PermissionsForNewDirs', 'PermissionsForNewDirsComment', NULL, NULL, 0),
        ('permissions_for_new_files', NULL, 'textfield', 'Security', '0666', 'PermissionsForNewFiles', 'PermissionsForNewFilesComment', NULL, NULL, 0),
        ('show_tabs', 'campus_homepage', 'checkbox', 'Platform', 'true', 'ShowTabsTitle','ShowTabsComment',NULL,'TabsCampusHomepage', 1),
        ('show_tabs', 'my_courses', 'checkbox', 'Platform', 'true', 'ShowTabsTitle','ShowTabsComment',NULL,'TabsMyCourses', 1),
        ('show_tabs', 'reporting', 'checkbox', 'Platform', 'true', 'ShowTabsTitle','ShowTabsComment',NULL,'TabsReporting', 1),
        ('show_tabs', 'platform_administration', 'checkbox', 'Platform', 'true', 'ShowTabsTitle','ShowTabsComment',NULL,'TabsPlatformAdministration', 1),
        ('show_tabs', 'my_agenda', 'checkbox', 'Platform', 'true', 'ShowTabsTitle','ShowTabsComment',NULL,'TabsMyAgenda', 1),
        ('show_tabs', 'my_profile', 'checkbox', 'Platform', 'true', 'ShowTabsTitle','ShowTabsComment',NULL,'TabsMyProfile', 1),
        ('default_forum_view', NULL, 'radio', 'Course', 'flat', 'DefaultForumViewTitle','DefaultForumViewComment',NULL,NULL, 0),
        ('platform_charset',NULL,'textfield','Languages','UTF-8','PlatformCharsetTitle','PlatformCharsetComment','platform',NULL, 0),
        ('noreply_email_address', '', 'textfield', 'Platform', '', 'NoReplyEmailAddress', 'NoReplyEmailAddressComment', NULL, NULL, 0),
        ('survey_email_sender_noreply', '', 'radio', 'Course', 'coach', 'SurveyEmailSenderNoReply', 'SurveyEmailSenderNoReplyComment', NULL, NULL, 0),
        ('openid_authentication',NULL,'radio','Security','false','OpenIdAuthentication','OpenIdAuthenticationComment',NULL,NULL, 0),
        ('profile','openid','checkbox','User','false','ProfileChangesTitle','ProfileChangesComment',NULL,'OpenIDURL', 0),
        ('gradebook_enable',NULL,'radio','Gradebook','false','GradebookActivation','GradebookActivationComment',NULL,NULL, 0),
        ('show_tabs','my_gradebook','checkbox','Platform','true','ShowTabsTitle','ShowTabsComment',NULL,'TabsMyGradebook', 1),
        ('gradebook_score_display_coloring','my_display_coloring','checkbox','Gradebook','false','GradebookScoreDisplayColoring','GradebookScoreDisplayColoringComment',NULL,'TabsGradebookEnableColoring', 0),
        ('gradebook_score_display_custom','my_display_custom','checkbox','Gradebook','false','GradebookScoreDisplayCustom','GradebookScoreDisplayCustomComment',NULL,'TabsGradebookEnableCustom', 0),
        ('gradebook_score_display_colorsplit',NULL,'textfield','Gradebook','50','GradebookScoreDisplayColorSplit','GradebookScoreDisplayColorSplitComment',NULL,NULL, 0),
        ('gradebook_score_display_upperlimit','my_display_upperlimit','checkbox','Gradebook','false','GradebookScoreDisplayUpperLimit','GradebookScoreDisplayUpperLimitComment',NULL,'TabsGradebookEnableUpperLimit', 0),
        ('gradebook_number_decimals', NULL, 'select', 'Gradebook', '0', 'GradebookNumberDecimals', 'GradebookNumberDecimalsComment', NULL, NULL, 0),
        ('user_selected_theme',NULL,'radio','Platform','false','UserThemeSelection','UserThemeSelectionComment',NULL,NULL, 0),
        ('profile','theme','checkbox','User','false','ProfileChangesTitle','ProfileChangesComment',NULL,'UserTheme', 0),
        ('allow_course_theme',NULL,'radio','Course','true','AllowCourseThemeTitle','AllowCourseThemeComment',NULL,NULL, 0),
        ('display_mini_month_calendar',NULL,'radio','Tools', 'true', 'DisplayMiniMonthCalendarTitle', 'DisplayMiniMonthCalendarComment', NULL, NULL, 0),
        ('display_upcoming_events',NULL,'radio','Tools','true','DisplayUpcomingEventsTitle','DisplayUpcomingEventsComment',NULL,NULL, 0),
        ('number_of_upcoming_events',NULL,'textfield','Tools','1','NumberOfUpcomingEventsTitle','NumberOfUpcomingEventsComment',NULL,NULL, 0),
        ('show_closed_courses',NULL,'radio','Platform','false','ShowClosedCoursesTitle','ShowClosedCoursesComment',NULL,NULL, 0),
        ('service_visio', 'visio_use_rtmpt', 'radio',null,'false', 'VisioUseRtmptTitle','VisioUseRtmptComment', NULL, NULL, 0),
        ('extendedprofile_registration', 'mycomptetences', 'checkbox','User','false', 'ExtendedProfileRegistrationTitle','ExtendedProfileRegistrationComment', NULL, 'MyCompetences', 0),
        ('extendedprofile_registration', 'mydiplomas', 'checkbox','User','false', 'ExtendedProfileRegistrationTitle','ExtendedProfileRegistrationComment', NULL, 'MyDiplomas', 0),
        ('extendedprofile_registration', 'myteach', 'checkbox','User','false', 'ExtendedProfileRegistrationTitle','ExtendedProfileRegistrationComment', NULL, 'MyTeach', 0),
        ('extendedprofile_registration', 'mypersonalopenarea', 'checkbox','User','false', 'ExtendedProfileRegistrationTitle','ExtendedProfileRegistrationComment', NULL, 'MyPersonalOpenArea', 0),
        ('extendedprofile_registrationrequired', 'mycomptetences', 'checkbox','User','false', 'ExtendedProfileRegistrationRequiredTitle','ExtendedProfileRegistrationRequiredComment', NULL, 'MyCompetences', 0),
        ('extendedprofile_registrationrequired', 'mydiplomas', 'checkbox','User','false', 'ExtendedProfileRegistrationRequiredTitle','ExtendedProfileRegistrationRequiredComment', NULL, 'MyDiplomas', 0),
        ('extendedprofile_registrationrequired', 'myteach', 'checkbox','User','false', 'ExtendedProfileRegistrationRequiredTitle','ExtendedProfileRegistrationRequiredComment', NULL, 'MyTeach', 0),
        ('extendedprofile_registrationrequired', 'mypersonalopenarea', 'checkbox','User','false', 'ExtendedProfileRegistrationRequiredTitle','ExtendedProfileRegistrationRequiredComment', NULL, 'MyPersonalOpenArea', 0),
        ('registration','phone','textfield','User','false','RegistrationRequiredFormsTitle','RegistrationRequiredFormsComment',NULL,'Phone', 0),
        ('add_users_by_coach',NULL,'radio','Session','false','AddUsersByCoachTitle','AddUsersByCoachComment',NULL,NULL, 0),
        ('extend_rights_for_coach',NULL,'radio','Security','false','ExtendRightsForCoachTitle','ExtendRightsForCoachComment',NULL,NULL, 0),
        ('extend_rights_for_coach_on_survey',NULL,'radio','Security','true','ExtendRightsForCoachOnSurveyTitle','ExtendRightsForCoachOnSurveyComment',NULL,NULL, 0),
        ('course_create_active_tools','wiki','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'Wiki', 0),
        ('show_session_coach', NULL, 'radio','Session','false', 'ShowSessionCoachTitle','ShowSessionCoachComment', NULL, NULL, 0),
        ('course_create_active_tools','gradebook','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'Gradebook', 0),
        ('allow_users_to_create_courses',NULL,'radio','Platform','true','AllowUsersToCreateCoursesTitle','AllowUsersToCreateCoursesComment',NULL,NULL, 0),
        ('course_create_active_tools','survey','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'Survey', 0),
        ('course_create_active_tools','glossary','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'Glossary', 0),
        ('course_create_active_tools','notebook','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'Notebook', 0),
        ('course_create_active_tools','attendances','checkbox','Tools','false','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'Attendances', 0),
        ('course_create_active_tools','course_progress','checkbox','Tools','false','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'CourseProgress', 0),
        ('allow_reservation', NULL, 'radio', 'Tools', 'false', 'AllowReservationTitle', 'AllowReservationComment', NULL, NULL, 0),
        ('profile','apikeys','checkbox','User','false','ProfileChangesTitle','ProfileChangesComment',NULL,'ApiKeys', 0),
        ('allow_message_tool', NULL, 'radio', 'Tools', 'true', 'AllowMessageToolTitle', 'AllowMessageToolComment', NULL, NULL,1),
        ('allow_social_tool', NULL, 'radio', 'Tools', 'true', 'AllowSocialToolTitle', 'AllowSocialToolComment', NULL, NULL,1),
        ('allow_students_to_browse_courses',NULL,'radio','Platform','true','AllowStudentsToBrowseCoursesTitle','AllowStudentsToBrowseCoursesComment',NULL,NULL, 1),
        ('show_session_data', NULL, 'radio', 'Session', 'false', 'ShowSessionDataTitle', 'ShowSessionDataComment', NULL, NULL, 1),
        ('allow_use_sub_language', NULL, 'radio', 'Languages', 'false', 'AllowUseSubLanguageTitle', 'AllowUseSubLanguageComment', NULL, NULL,0),
        ('show_glossary_in_documents', NULL, 'radio', 'Course', 'none', 'ShowGlossaryInDocumentsTitle', 'ShowGlossaryInDocumentsComment', NULL, NULL,1),
        ('allow_terms_conditions', NULL, 'radio', 'Platform', 'false', 'AllowTermsAndConditionsTitle', 'AllowTermsAndConditionsComment', NULL, NULL,0),
        ('course_create_active_tools','enable_search','checkbox','Tools','false','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'Search',0),
        ('search_enabled',NULL,'radio','Search','false','EnableSearchTitle','EnableSearchComment',NULL,NULL,1),
        ('search_prefilter_prefix',NULL, NULL,'Search','','SearchPrefilterPrefix','SearchPrefilterPrefixComment',NULL,NULL,0),
        ('search_show_unlinked_results',NULL,'radio','Search','true','SearchShowUnlinkedResultsTitle','SearchShowUnlinkedResultsComment',NULL,NULL,1),
        ('show_courses_descriptions_in_catalog', NULL, 'radio', 'Course', 'true', 'ShowCoursesDescriptionsInCatalogTitle', 'ShowCoursesDescriptionsInCatalogComment', NULL, NULL, 1),
        ('allow_coach_to_edit_course_session',NULL,'radio','Session','true','AllowCoachsToEditInsideTrainingSessions','AllowCoachsToEditInsideTrainingSessionsComment',NULL,NULL, 0),
        ('show_glossary_in_extra_tools', NULL, 'radio', 'Course', 'none', 'ShowGlossaryInExtraToolsTitle', 'ShowGlossaryInExtraToolsComment', NULL, NULL,1),
        ('send_email_to_admin_when_create_course',NULL,'radio','Platform','false','SendEmailToAdminTitle','SendEmailToAdminComment',NULL,NULL, 1),
        ('go_to_course_after_login',NULL,'radio','Course','false','GoToCourseAfterLoginTitle','GoToCourseAfterLoginComment',NULL,NULL, 0),
        ('math_mimetex',NULL,'radio','Editor','false','MathMimetexTitle','MathMimetexComment',NULL,NULL, 0),
        ('math_asciimathML',NULL,'radio','Editor','false','MathASCIImathMLTitle','MathASCIImathMLComment',NULL,NULL, 0),
        ('enabled_asciisvg',NULL,'radio','Editor','false','AsciiSvgTitle','AsciiSvgComment',NULL,NULL, 0),
        ('include_asciimathml_script',NULL,'radio','Editor','false','IncludeAsciiMathMlTitle','IncludeAsciiMathMlComment',NULL,NULL, 0),
        ('youtube_for_students',NULL,'radio','Editor','true','YoutubeForStudentsTitle','YoutubeForStudentsComment',NULL,NULL, 0),
        ('block_copy_paste_for_students',NULL,'radio','Editor','false','BlockCopyPasteForStudentsTitle','BlockCopyPasteForStudentsComment',NULL,NULL, 0),
        ('more_buttons_maximized_mode',NULL,'radio','Editor','true','MoreButtonsForMaximizedModeTitle','MoreButtonsForMaximizedModeComment',NULL,NULL, 0),
        ('students_download_folders',NULL,'radio','Tools','true','AllowStudentsDownloadFoldersTitle','AllowStudentsDownloadFoldersComment',NULL,NULL, 0),
        ('users_copy_files',NULL,'radio','Tools','true','AllowUsersCopyFilesTitle','AllowUsersCopyFilesComment',NULL,NULL, 1),
        ('show_tabs', 'social', 'checkbox', 'Platform', 'true', 'ShowTabsTitle','ShowTabsComment',NULL,'TabsSocial', 0),
        ('allow_students_to_create_groups_in_social',NULL,'radio','Tools','false','AllowStudentsToCreateGroupsInSocialTitle','AllowStudentsToCreateGroupsInSocialComment',NULL,NULL, 0),
        ('allow_send_message_to_all_platform_users',NULL,'radio','Tools','true','AllowSendMessageToAllPlatformUsersTitle','AllowSendMessageToAllPlatformUsersComment',NULL,NULL, 0),
        ('message_max_upload_filesize',NULL,'textfield','Tools','20971520','MessageMaxUploadFilesizeTitle','MessageMaxUploadFilesizeComment',NULL,NULL, 0),
        ('show_tabs', 'dashboard', 'checkbox', 'Platform', 'true', 'ShowTabsTitle', 'ShowTabsComment', NULL, 'TabsDashboard', 1),
        ('use_users_timezone', 'timezones', 'radio', 'Timezones', 'true', 'UseUsersTimezoneTitle','UseUsersTimezoneComment',NULL,'Timezones', 1),
        ('timezone_value', 'timezones', 'select', 'Timezones', '', 'TimezoneValueTitle','TimezoneValueComment',NULL,'Timezones', 1),
        ('allow_user_course_subscription_by_course_admin', NULL, 'radio', 'Security', 'true', 'AllowUserCourseSubscriptionByCourseAdminTitle', 'AllowUserCourseSubscriptionByCourseAdminComment', NULL, NULL, 1),
        ('show_link_bug_notification', NULL, 'radio', 'Platform', 'true', 'ShowLinkBugNotificationTitle', 'ShowLinkBugNotificationComment', NULL, NULL, 0),
        ('course_validation', NULL, 'radio', 'Platform', 'false', 'EnableCourseValidation', 'EnableCourseValidationComment', NULL, NULL, 1),
        ('course_validation_terms_and_conditions_url', NULL, 'textfield', 'Platform', '', 'CourseValidationTermsAndConditionsLink', 'CourseValidationTermsAndConditionsLinkComment', NULL, NULL, 1),
        ('sso_authentication',NULL,'radio','Security','false','EnableSSOTitle','EnableSSOComment',NULL,NULL,1),
        ('sso_authentication_domain',NULL,'textfield','Security','','SSOServerDomainTitle','SSOServerDomainComment',NULL,NULL,1),
        ('sso_authentication_auth_uri',NULL,'textfield','Security','/?q=user','SSOServerAuthURITitle','SSOServerAuthURIComment',NULL,NULL,1),
        ('sso_authentication_unauth_uri',NULL,'textfield','Security','/?q=logout','SSOServerUnAuthURITitle','SSOServerUnAuthURIComment',NULL,NULL,1),
        ('sso_authentication_protocol',NULL,'radio','Security','http://','SSOServerProtocolTitle','SSOServerProtocolComment',NULL,NULL,1),
        ('enabled_wiris',NULL,'radio','Editor','false','EnabledWirisTitle','EnabledWirisComment',NULL,NULL, 0),
        ('allow_spellcheck',NULL,'radio','Editor','false','AllowSpellCheckTitle','AllowSpellCheckComment',NULL,NULL, 0),
        ('force_wiki_paste_as_plain_text',NULL,'radio','Editor','false','ForceWikiPasteAsPlainTextTitle','ForceWikiPasteAsPlainTextComment',NULL,NULL, 0),
        ('enabled_googlemaps',NULL,'radio','Editor','false','EnabledGooglemapsTitle','EnabledGooglemapsComment',NULL,NULL, 0),
        ('enabled_imgmap',NULL,'radio','Editor','true','EnabledImageMapsTitle','EnabledImageMapsComment',NULL,NULL, 0),
        ('enabled_support_svg',				NULL,'radio',		'Tools',	'true',	'EnabledSVGTitle','EnabledSVGComment',NULL,NULL, 0),
        ('pdf_export_watermark_enable',		NULL,'radio',		'Platform',	'false','PDFExportWatermarkEnableTitle',	'PDFExportWatermarkEnableComment',	'platform',NULL, 1),
        ('pdf_export_watermark_by_course',	NULL,'radio',		'Platform',	'false','PDFExportWatermarkByCourseTitle',	'PDFExportWatermarkByCourseComment','platform',NULL, 1),
        ('pdf_export_watermark_text',		NULL,'textfield',	'Platform',	'',		'PDFExportWatermarkTextTitle',		'PDFExportWatermarkTextComment',	'platform',NULL, 1),
        ('enabled_insertHtml',				NULL,'radio',		'Editor',	'true','EnabledInsertHtmlTitle',			'EnabledInsertHtmlComment',NULL,NULL, 0),
        ('students_export2pdf',				NULL,'radio',		'Tools',	'true',	'EnabledStudentExport2PDFTitle',	'EnabledStudentExport2PDFComment',NULL,NULL, 0),
        ('exercise_min_score', 				NULL,'textfield',	'Course',	'',		'ExerciseMinScoreTitle',			'ExerciseMinScoreComment','platform',NULL, 	1),
        ('exercise_max_score', 				NULL,'textfield',	'Course',	'',		'ExerciseMaxScoreTitle',			'ExerciseMaxScoreComment','platform',NULL, 	1),
        ('show_users_folders',				NULL,'radio',		'Tools',	'true',	'ShowUsersFoldersTitle','ShowUsersFoldersComment',NULL,NULL, 0),
        ('show_default_folders',			NULL,'radio',		'Tools',	'true',	'ShowDefaultFoldersTitle','ShowDefaultFoldersComment',NULL,NULL, 0),
        ('show_chat_folder',				NULL,'radio',		'Tools',	'true',	'ShowChatFolderTitle','ShowChatFolderComment',NULL,NULL, 0),
        ('enabled_text2audio',				NULL,'radio',		'Tools',	'false',	'Text2AudioTitle','Text2AudioComment',NULL,NULL, 0),
        ('course_hide_tools','course_description','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'CourseDescription', 1),
        ('course_hide_tools','calendar_event','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Agenda', 1),
        ('course_hide_tools','document','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Documents', 1),
        ('course_hide_tools','learnpath','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'LearningPath', 1),
        ('course_hide_tools','link','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Links', 1),
        ('course_hide_tools','announcement','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Announcements', 1),
        ('course_hide_tools','forum','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Forums', 1),
        ('course_hide_tools','dropbox','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Dropbox', 1),
        ('course_hide_tools','quiz','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Quiz', 1),
        ('course_hide_tools','user','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Users', 1),
        ('course_hide_tools','group','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Groups', 1),
        ('course_hide_tools','chat','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Chat', 1),
        ('course_hide_tools','student_publication','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'StudentPublications', 1),
        ('course_hide_tools','wiki','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Wiki', 1),
        ('course_hide_tools','gradebook','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Gradebook', 1),
        ('course_hide_tools','survey','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Survey', 1),
        ('course_hide_tools','glossary','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Glossary', 1),
        ('course_hide_tools','notebook','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Notebook', 1),
        ('course_hide_tools','attendance','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Attendances', 1),
        ('course_hide_tools','course_progress','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'CourseProgress', 1),
        ('course_hide_tools','blog_management','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Blog',1),
        ('course_hide_tools','tracking','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Stats',1),
        ('course_hide_tools','course_maintenance','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Maintenance',1),
        ('course_hide_tools','course_setting','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'CourseSettings',1),
        ('enabled_support_pixlr',NULL,'radio','Tools','false','EnabledPixlrTitle','EnabledPixlrComment',NULL,NULL, 0),
        ('show_groups_to_users',NULL,'radio','Session','false','ShowGroupsToUsersTitle','ShowGroupsToUsersComment',NULL,NULL, 0),
        ('accessibility_font_resize',NULL,'radio','Platform','false','EnableAccessibilityFontResizeTitle','EnableAccessibilityFontResizeComment',NULL,NULL, 1),
        ('hide_courses_in_sessions',NULL,'radio', 'Session','false','HideCoursesInSessionsTitle',	'HideCoursesInSessionsComment','platform',NULL, 1),
        ('enable_quiz_scenario',  NULL,'radio','Course','false','EnableQuizScenarioTitle','EnableQuizScenarioComment',NULL,NULL, 1),
        ('enable_nanogong',NULL,'radio','Tools','false','EnableNanogongTitle','EnableNanogongComment',NULL,NULL, 0),
        ('filter_terms',NULL,'textarea','Security','','FilterTermsTitle','FilterTermsComment',NULL,NULL, 0),
        ('header_extra_content', NULL, 'textarea', 'Tracking', '', 'HeaderExtraContentTitle', 'HeaderExtraContentComment', NULL, NULL, 1),
        ('footer_extra_content', NULL, 'textarea', 'Tracking', '', 'FooterExtraContentTitle', 'FooterExtraContentComment', NULL, NULL, 1),
        ('show_documents_preview', NULL, 'radio', 'Tools', 'false', 'ShowDocumentPreviewTitle', 'ShowDocumentPreviewComment', NULL, NULL, 1),
        ('htmlpurifier_wiki', NULL, 'radio', 'Editor', 'false', 'HtmlPurifierWikiTitle', 'HtmlPurifierWikiComment', NULL, NULL, 0),
        ('cas_activate', NULL, 'radio', 'CAS', 'false', 'CasMainActivateTitle', 'CasMainActivateComment', NULL, NULL, 0),
        ('cas_server', NULL, 'textfield', 'CAS', '', 'CasMainServerTitle', 'CasMainServerComment', NULL, NULL, 0),
        ('cas_server_uri', NULL, 'textfield', 'CAS', '', 'CasMainServerURITitle', 'CasMainServerURIComment', NULL, NULL, 0),
        ('cas_port', NULL, 'textfield', 'CAS', '', 'CasMainPortTitle', 'CasMainPortComment', NULL, NULL, 0),
        ('cas_protocol', NULL, 'radio', 'CAS', '', 'CasMainProtocolTitle', 'CasMainProtocolComment', NULL, NULL, 0),
        ('cas_add_user_activate', NULL, 'radio', 'CAS', 'false', 'CasUserAddActivateTitle', 'CasUserAddActivateComment', NULL, NULL, 0),
        ('update_user_info_cas_with_ldap', NULL, 'radio', 'CAS', 'true', 'UpdateUserInfoCasWithLdapTitle', 'UpdateUserInfoCasWithLdapComment', NULL, NULL, 0),
        ('student_page_after_login', NULL, 'textfield', 'Platform', '', 'StudentPageAfterLoginTitle', 'StudentPageAfterLoginComment', NULL, NULL, 0),
        ('teacher_page_after_login', NULL, 'textfield', 'Platform', '', 'TeacherPageAfterLoginTitle', 'TeacherPageAfterLoginComment', NULL, NULL, 0),
        ('drh_page_after_login', NULL, 'textfield', 'Platform', '', 'DRHPageAfterLoginTitle', 'DRHPageAfterLoginComment', NULL, NULL, 0),
        ('sessionadmin_page_after_login', NULL, 'textfield', 'Session', '', 'SessionAdminPageAfterLoginTitle', 'SessionAdminPageAfterLoginComment', NULL, NULL, 0),
        ('student_autosubscribe', NULL, 'textfield', 'Platform', '', 'StudentAutosubscribeTitle', 'StudentAutosubscribeComment', NULL, NULL, 0),
        ('teacher_autosubscribe', NULL, 'textfield', 'Platform', '', 'TeacherAutosubscribeTitle', 'TeacherAutosubscribeComment', NULL, NULL, 0),
        ('drh_autosubscribe', NULL, 'textfield', 'Platform', '', 'DRHAutosubscribeTitle', 'DRHAutosubscribeComment', NULL, NULL, 0),
        ('sessionadmin_autosubscribe', NULL, 'textfield', 'Session', '', 'SessionadminAutosubscribeTitle', 'SessionadminAutosubscribeComment', NULL, NULL, 0),
        ('scorm_cumulative_session_time', NULL, 'radio', 'Course', 'true', 'ScormCumulativeSessionTimeTitle', 'ScormCumulativeSessionTimeComment', NULL, NULL, 0),
        ('allow_hr_skills_management', NULL, 'radio', 'Gradebook', 'true', 'AllowHRSkillsManagementTitle', 'AllowHRSkillsManagementComment', NULL, NULL, 1),
        ('enable_help_link', NULL, 'radio', 'Platform', 'true', 'EnableHelpLinkTitle', 'EnableHelpLinkComment', NULL, NULL, 0),
        ('teachers_can_change_score_settings', NULL, 'radio', 'Gradebook', 'true', 'TeachersCanChangeScoreSettingsTitle', 'TeachersCanChangeScoreSettingsComment', NULL, NULL, 1),
        ('allow_users_to_change_email_with_no_password', NULL, 'radio', 'User', 'false', 'AllowUsersToChangeEmailWithNoPasswordTitle', 'AllowUsersToChangeEmailWithNoPasswordComment', NULL, NULL, 0),
        ('show_admin_toolbar', NULL, 'radio', 'Platform', 'show_to_admin', 'ShowAdminToolbarTitle', 'ShowAdminToolbarComment', NULL, NULL, 1),
        ('allow_global_chat', NULL, 'radio', 'Platform', 'true', 'AllowGlobalChatTitle', 'AllowGlobalChatComment', NULL, NULL, 1),
        ('languagePriority1', NULL, 'radio', 'Languages', 'course_lang', 'LanguagePriority1Title', 'LanguagePriority1Comment', NULL, NULL, 0),
        ('languagePriority2', NULL, 'radio', 'Languages','user_profil_lang', 'LanguagePriority2Title', 'LanguagePriority2Comment', NULL, NULL, 0),
        ('languagePriority3', NULL, 'radio', 'Languages','user_selected_lang', 'LanguagePriority3Title', 'LanguagePriority3Comment', NULL, NULL, 0),
        ('languagePriority4', NULL, 'radio', 'Languages', 'platform_lang','LanguagePriority4Title', 'LanguagePriority4Comment', NULL, NULL, 0),
        ('login_is_email', NULL, 'radio', 'Platform', 'false', 'LoginIsEmailTitle', 'LoginIsEmailComment', NULL, NULL, 0),
        ('courses_default_creation_visibility', NULL, 'radio', 'Course', '2', 'CoursesDefaultCreationVisibilityTitle', 'CoursesDefaultCreationVisibilityComment', NULL, NULL, 1),
        ('allow_browser_sniffer', NULL, 'radio', 'Tuning', 'false', 'AllowBrowserSnifferTitle', 'AllowBrowserSnifferComment', NULL, NULL, 0),
        ('enable_wami_record',NULL,'radio','Tools','false','EnableWamiRecordTitle','EnableWamiRecordComment',NULL,NULL, 0),
        ('gradebook_enable_grade_model', NULL, 'radio', 'Gradebook', 'false', 'GradebookEnableGradeModelTitle', 'GradebookEnableGradeModelComment', NULL, NULL, 1),
        ('teachers_can_change_grade_model_settings', NULL, 'radio', 'Gradebook', 'true', 'TeachersCanChangeGradeModelSettingsTitle', 'TeachersCanChangeGradeModelSettingsComment', NULL, NULL, 1),
        ('gradebook_default_weight', NULL, 'textfield', 'Gradebook', '100', 'GradebookDefaultWeightTitle', 'GradebookDefaultWeightComment', NULL, NULL, 0),
        ('ldap_description', NULL, 'radio', 'LDAP', NULL, 'LdapDescriptionTitle', 'LdapDescriptionComment', NULL, NULL, 0),
        ('shibboleth_description', NULL, 'radio', 'Shibboleth', 'false', 'ShibbolethMainActivateTitle', 'ShibbolethMainActivateComment', NULL, NULL, 0),
        ('facebook_description', NULL, 'radio', 'Facebook', 'false', 'FacebookMainActivateTitle', 'FacebookMainActivateComment', NULL, NULL, 0),
        ('gradebook_locking_enabled', NULL, 'radio', 'Gradebook', 'false', 'GradebookEnableLockingTitle', 'GradebookEnableLockingComment', NULL, NULL, 0),
        ('gradebook_default_grade_model_id', NULL, 'select', 'Gradebook', '', 'GradebookDefaultGradeModelTitle', 'GradebookDefaultGradeModelComment', NULL, NULL, 1),
        ('allow_session_admins_to_manage_all_sessions', NULL, 'radio', 'Session', 'false', 'AllowSessionAdminsToSeeAllSessionsTitle', 'AllowSessionAdminsToSeeAllSessionsComment', NULL, NULL, 1),
        ('allow_skills_tool', NULL, 'radio', 'Platform', 'false', 'AllowSkillsToolTitle', 'AllowSkillsToolComment', NULL, NULL, 1),
        ('allow_public_certificates', NULL, 'radio', 'Course', 'false', 'AllowPublicCertificatesTitle', 'AllowPublicCertificatesComment', NULL, NULL, 1),
        ('platform_unsubscribe_allowed', NULL, 'radio', 'Platform', 'false', 'PlatformUnsubscribeTitle', 'PlatformUnsubscribeComment', NULL, NULL, 1),
        ('activate_email_template', NULL, 'radio', 'Platform', 'false', 'ActivateEmailTemplateTitle', 'ActivateEmailTemplateComment', NULL, NULL, 0),
        ('enable_iframe_inclusion', NULL, 'radio', 'Editor', 'false', 'EnableIframeInclusionTitle', 'EnableIframeInclusionComment', NULL, NULL, 1),
        ('show_hot_courses', NULL, 'radio', 'Platform', 'true', 'ShowHotCoursesTitle', 'ShowHotCoursesComment', NULL, NULL, 1),
        ('enable_webcam_clip',NULL,'radio','Tools','false','EnableWebCamClipTitle','EnableWebCamClipComment',NULL,NULL, 0),
        ('use_custom_pages', NULL, 'radio','Platform','false','UseCustomPagesTitle','UseCustomPagesComment', NULL, NULL, 1),
        ('tool_visible_by_default_at_creation','documents','checkbox','Tools','true','ToolVisibleByDefaultAtCreationTitle','ToolVisibleByDefaultAtCreationComment',NULL,'Documents', 1),
        ('tool_visible_by_default_at_creation','learning_path','checkbox','Tools','true','ToolVisibleByDefaultAtCreationTitle','ToolVisibleByDefaultAtCreationComment',NULL,'LearningPath', 1),
        ('tool_visible_by_default_at_creation','links','checkbox','Tools','true','ToolVisibleByDefaultAtCreationTitle','ToolVisibleByDefaultAtCreationComment',NULL,'Links', 1),
        ('tool_visible_by_default_at_creation','announcements','checkbox','Tools','true','ToolVisibleByDefaultAtCreationTitle','ToolVisibleByDefaultAtCreationComment',NULL,'Announcements', 1),
        ('tool_visible_by_default_at_creation','forums','checkbox','Tools','true','ToolVisibleByDefaultAtCreationTitle','ToolVisibleByDefaultAtCreationComment',NULL,'Forums', 1),
        ('tool_visible_by_default_at_creation','quiz','checkbox','Tools','true','ToolVisibleByDefaultAtCreationTitle','ToolVisibleByDefaultAtCreationComment',NULL,'Quiz', 1),
        ('tool_visible_by_default_at_creation','gradebook','checkbox','Tools','true','ToolVisibleByDefaultAtCreationTitle','ToolVisibleByDefaultAtCreationComment',NULL,'Gradebook', 1),
        ('prevent_session_admins_to_manage_all_users', NULL, 'radio', 'Session', 'false', 'PreventSessionAdminsToManageAllUsersTitle', 'PreventSessionAdminsToManageAllUsersComment', NULL, NULL, 1),
        ('documents_default_visibility_defined_in_course', NULL,'radio','Tools','false','DocumentsDefaultVisibilityDefinedInCourseTitle','DocumentsDefaultVisibilityDefinedInCourseComment',NULL, NULL, 1),
        ('enabled_mathjax', NULL, 'radio', 'Editor', 'false', 'EnableMathJaxTitle', 'EnableMathJaxComment', NULL, NULL, 0),
        ('chamilo_database_version', NULL, 'textfield',NULL, '0', 'DatabaseVersion','', NULL, NULL, 0);";
    Database::query($sql);

    $sql = "INSERT INTO settings_options (variable, value, display_text) VALUES
        ('show_administrator_data','true','Yes'),
        ('show_administrator_data','false','No'),
        ('show_tutor_data','true','Yes'),
        ('show_tutor_data','false','No'),
        ('show_teacher_data','true','Yes'),
        ('show_teacher_data','false','No'),
        ('homepage_view','activity','HomepageViewActivity'),
        ('homepage_view','2column','HomepageView2column'),
        ('homepage_view','3column','HomepageView3column'),
        ('homepage_view','vertical_activity','HomepageViewVerticalActivity'),
        ('homepage_view','activity_big','HomepageViewActivityBig'),
        ('show_toolshortcuts','true','Yes'),
        ('show_toolshortcuts','false','No'),
        ('allow_group_categories','true','Yes'),
        ('allow_group_categories','false','No'),
        ('server_type','production','ProductionServer'),
        ('server_type','test','TestServer'),
        ('allow_name_change','true','Yes'),
        ('allow_name_change','false','No'),
        ('allow_officialcode_change','true','Yes'),
        ('allow_officialcode_change','false','No'),
        ('allow_registration','true','Yes'),
        ('allow_registration','false','No'),
        ('allow_registration','approval','AfterApproval'),
        ('allow_registration_as_teacher','true','Yes'),
        ('allow_registration_as_teacher','false','No'),
        ('allow_lostpassword','true','Yes'),
        ('allow_lostpassword','false','No'),
        ('allow_user_headings','true','Yes'),
        ('allow_user_headings','false','No'),
        ('allow_personal_agenda','true','Yes'),
        ('allow_personal_agenda','false','No'),
        ('display_coursecode_in_courselist','true','Yes'),
        ('display_coursecode_in_courselist','false','No'),
        ('display_teacher_in_courselist','true','Yes'),
        ('display_teacher_in_courselist','false','No'),
        ('permanently_remove_deleted_files','true','YesWillDeletePermanently'),
        ('permanently_remove_deleted_files','false','NoWillDeletePermanently'),
        ('dropbox_allow_overwrite','true','Yes'),
        ('dropbox_allow_overwrite','false','No'),
        ('dropbox_allow_just_upload','true','Yes'),
        ('dropbox_allow_just_upload','false','No'),
        ('dropbox_allow_student_to_student','true','Yes'),
        ('dropbox_allow_student_to_student','false','No'),
        ('dropbox_allow_group','true','Yes'),
        ('dropbox_allow_group','false','No'),
        ('dropbox_allow_mailing','true','Yes'),
        ('dropbox_allow_mailing','false','No'),
        ('extended_profile','true','Yes'),
        ('extended_profile','false','No'),
        ('student_view_enabled','true','Yes'),
        ('student_view_enabled','false','No'),
        ('show_navigation_menu','false','No'),
        ('show_navigation_menu','icons','IconsOnly'),
        ('show_navigation_menu','text','TextOnly'),
        ('show_navigation_menu','iconstext','IconsText'),
        ('enable_tool_introduction','true','Yes'),
        ('enable_tool_introduction','false','No'),
        ('page_after_login', 'index.php', 'CampusHomepage'),
        ('page_after_login', 'user_portal.php', 'MyCourses'),
        ('page_after_login', 'main/auth/courses.php', 'CourseCatalog'),
        ('breadcrumbs_course_homepage', 'get_lang', 'CourseHomepage'),
        ('breadcrumbs_course_homepage', 'course_code', 'CourseCode'),
        ('breadcrumbs_course_homepage', 'course_title', 'CourseTitle'),
        ('example_material_course_creation', 'true', 'Yes'),
        ('example_material_course_creation', 'false', 'No'),
        ('use_session_mode', 'true', 'Yes'),
        ('use_session_mode', 'false', 'No'),
        ('allow_email_editor', 'true' ,'Yes'),
        ('allow_email_editor', 'false', 'No'),
        ('show_email_addresses','true','Yes'),
        ('show_email_addresses','false','No'),
        ('upload_extensions_list_type', 'blacklist', 'Blacklist'),
        ('upload_extensions_list_type', 'whitelist', 'Whitelist'),
        ('upload_extensions_skip', 'true', 'Remove'),
        ('upload_extensions_skip', 'false', 'Rename'),
        ('show_number_of_courses', 'true', 'Yes'),
        ('show_number_of_courses', 'false', 'No'),
        ('show_empty_course_categories', 'true', 'Yes'),
        ('show_empty_course_categories', 'false', 'No'),
        ('show_back_link_on_top_of_tree', 'true', 'Yes'),
        ('show_back_link_on_top_of_tree', 'false', 'No'),
        ('show_different_course_language', 'true', 'Yes'),
        ('show_different_course_language', 'false', 'No'),
        ('split_users_upload_directory', 'true', 'Yes'),
        ('split_users_upload_directory', 'false', 'No'),
        ('hide_dltt_markup', 'false', 'No'),
        ('hide_dltt_markup', 'true', 'Yes'),
        ('display_categories_on_homepage','true','Yes'),
        ('display_categories_on_homepage','false','No'),
        ('default_forum_view', 'flat', 'Flat'),
        ('default_forum_view', 'threaded', 'Threaded'),
        ('default_forum_view', 'nested', 'Nested'),
        ('survey_email_sender_noreply', 'coach', 'CourseCoachEmailSender'),
        ('survey_email_sender_noreply', 'noreply', 'NoReplyEmailSender'),
        ('openid_authentication','true','Yes'),
        ('openid_authentication','false','No'),
        ('gradebook_enable','true','Yes'),
        ('gradebook_enable','false','No'),
        ('user_selected_theme','true','Yes'),
        ('user_selected_theme','false','No'),
        ('allow_course_theme','true','Yes'),
        ('allow_course_theme','false','No'),
        ('display_mini_month_calendar', 'true', 'Yes'),
        ('display_mini_month_calendar', 'false', 'No'),
        ('display_upcoming_events', 'true', 'Yes'),
        ('display_upcoming_events', 'false', 'No'),
        ('show_closed_courses', 'true', 'Yes'),
        ('show_closed_courses', 'false', 'No'),
        ('ldap_version', '2', 'LDAPVersion2'),
        ('ldap_version', '3', 'LDAPVersion3'),
        ('visio_use_rtmpt','true','Yes'),
        ('visio_use_rtmpt','false','No'),
        ('add_users_by_coach', 'true', 'Yes'),
        ('add_users_by_coach', 'false', 'No'),
        ('extend_rights_for_coach', 'true', 'Yes'),
        ('extend_rights_for_coach', 'false', 'No'),
        ('extend_rights_for_coach_on_survey', 'true', 'Yes'),
        ('extend_rights_for_coach_on_survey', 'false', 'No'),
        ('show_session_coach', 'true', 'Yes'),
        ('show_session_coach', 'false', 'No'),
        ('allow_users_to_create_courses','true','Yes'),
        ('allow_users_to_create_courses','false','No'),
        ('breadcrumbs_course_homepage', 'session_name_and_course_title', 'SessionNameAndCourseTitle'),
        ('allow_reservation', 'true', 'Yes'),
        ('allow_reservation', 'false', 'No'),
        ('allow_message_tool', 'true', 'Yes'),
        ('allow_message_tool', 'false', 'No'),
        ('allow_social_tool', 'true', 'Yes'),
        ('allow_social_tool', 'false', 'No'),
        ('allow_students_to_browse_courses','true','Yes'),
        ('allow_students_to_browse_courses','false','No'),
        ('show_email_of_teacher_or_tutor ', 'true', 'Yes'),
        ('show_email_of_teacher_or_tutor ', 'false', 'No'),
        ('show_session_data ', 'true', 'Yes'),
        ('show_session_data ', 'false', 'No'),
        ('allow_use_sub_language', 'true', 'Yes'),
        ('allow_use_sub_language', 'false', 'No'),
        ('show_glossary_in_documents', 'none', 'ShowGlossaryInDocumentsIsNone'),
        ('show_glossary_in_documents', 'ismanual', 'ShowGlossaryInDocumentsIsManual'),
        ('show_glossary_in_documents', 'isautomatic', 'ShowGlossaryInDocumentsIsAutomatic'),
        ('allow_terms_conditions', 'true', 'Yes'),
        ('allow_terms_conditions', 'false', 'No'),
        ('search_enabled', 'true', 'Yes'),
        ('search_enabled', 'false', 'No'),
        ('search_show_unlinked_results', 'true', 'SearchShowUnlinkedResults'),
        ('search_show_unlinked_results', 'false', 'SearchHideUnlinkedResults'),
        ('show_courses_descriptions_in_catalog', 'true', 'Yes'),
        ('show_courses_descriptions_in_catalog', 'false', 'No'),
        ('allow_coach_to_edit_course_session','true','Yes'),
        ('allow_coach_to_edit_course_session','false','No'),
        ('show_glossary_in_extra_tools', 'none', 'None'),
        ('show_glossary_in_extra_tools', 'exercise', 'Exercise'),
        ('show_glossary_in_extra_tools', 'lp', 'Learning path'),
        ('show_glossary_in_extra_tools', 'exercise_and_lp', 'ExerciseAndLearningPath'),
        ('send_email_to_admin_when_create_course','true','Yes'),
        ('send_email_to_admin_when_create_course','false','No'),
        ('go_to_course_after_login','true','Yes'),
        ('go_to_course_after_login','false','No'),
        ('math_mimetex','true','Yes'),
        ('math_mimetex','false','No'),
        ('math_asciimathML','true','Yes'),
        ('math_asciimathML','false','No'),
        ('enabled_asciisvg','true','Yes'),
        ('enabled_asciisvg','false','No'),
        ('include_asciimathml_script','true','Yes'),
        ('include_asciimathml_script','false','No'),
        ('youtube_for_students','true','Yes'),
        ('youtube_for_students','false','No'),
        ('block_copy_paste_for_students','true','Yes'),
        ('block_copy_paste_for_students','false','No'),
        ('more_buttons_maximized_mode','true','Yes'),
        ('more_buttons_maximized_mode','false','No'),
        ('students_download_folders','true','Yes'),
        ('students_download_folders','false','No'),
        ('users_copy_files','true','Yes'),
        ('users_copy_files','false','No'),
        ('allow_students_to_create_groups_in_social','true','Yes'),
        ('allow_students_to_create_groups_in_social','false','No'),
        ('allow_send_message_to_all_platform_users','true','Yes'),
        ('allow_send_message_to_all_platform_users','false','No'),
        ('use_users_timezone', 'true', 'Yes'),
        ('use_users_timezone', 'false', 'No'),
        ('allow_user_course_subscription_by_course_admin', 'true', 'Yes'),
        ('allow_user_course_subscription_by_course_admin', 'false', 'No'),
        ('show_link_bug_notification', 'true', 'Yes'),
        ('show_link_bug_notification', 'false', 'No'),
        ('course_validation', 'true', 'Yes'),
        ('course_validation', 'false', 'No'),
        ('sso_authentication', 'true', 'Yes'),
        ('sso_authentication', 'false', 'No'),
        ('sso_authentication_protocol', 'http://', 'http://'),
        ('sso_authentication_protocol', 'https://', 'https://'),
        ('enabled_wiris','true','Yes'),
        ('enabled_wiris','false','No'),
        ('allow_spellcheck','true','Yes'),
        ('allow_spellcheck','false','No'),
        ('force_wiki_paste_as_plain_text','true','Yes'),
        ('force_wiki_paste_as_plain_text','false','No'),
        ('enabled_googlemaps','true','Yes'),
        ('enabled_googlemaps','false','No'),
        ('enabled_imgmap','true','Yes'),
        ('enabled_imgmap','false','No'),
        ('enabled_support_svg','true','Yes'),
        ('enabled_support_svg','false','No'),
        ('pdf_export_watermark_enable','true','Yes'),
        ('pdf_export_watermark_enable','false','No'),
        ('pdf_export_watermark_by_course','true','Yes'),
        ('pdf_export_watermark_by_course','false','No'),
        ('enabled_insertHtml','true','Yes'),
        ('enabled_insertHtml','false','No'),
        ('students_export2pdf','true','Yes'),
        ('students_export2pdf','false','No'),
        ('show_users_folders','true','Yes'),
        ('show_users_folders','false','No'),
        ('show_default_folders','true','Yes'),
        ('show_default_folders','false','No'),
        ('show_chat_folder','true','Yes'),
        ('show_chat_folder','false','No'),
        ('enabled_text2audio','true','Yes'),
        ('enabled_text2audio','false','No'),
        ('enabled_support_pixlr','true','Yes'),
        ('enabled_support_pixlr','false','No'),
        ('show_groups_to_users','true','Yes'),
        ('show_groups_to_users','false','No'),
        ('accessibility_font_resize', 'true', 'Yes'),
        ('accessibility_font_resize', 'false', 'No'),
        ('hide_courses_in_sessions','true','Yes'),
        ('hide_courses_in_sessions','false','No'),
        ('enable_quiz_scenario', 'true', 'Yes'),
        ('enable_quiz_scenario', 'false', 'No'),
        ('enable_nanogong','true','Yes'),
        ('enable_nanogong','false','No'),
        ('show_documents_preview', 'true', 'Yes'),
        ('show_documents_preview', 'false', 'No'),
        ('htmlpurifier_wiki', 'true', 'Yes'),
        ('htmlpurifier_wiki', 'false', 'No'),
        ('cas_activate', 'true', 'Yes'),
        ('cas_activate', 'false', 'No'),
        ('cas_protocol', 'CAS1', 'CAS1Text'),
        ('cas_protocol', 'CAS2', 'CAS2Text'),
        ('cas_protocol', 'SAML', 'SAMLText'),
        ('cas_add_user_activate', 'false', 'No'),
        ('cas_add_user_activate', 'platform', 'casAddUserActivatePlatform'),
        ('cas_add_user_activate', 'extldap', 'casAddUserActivateLDAP'),
        ('update_user_info_cas_with_ldap', 'true', 'Yes'),
        ('update_user_info_cas_with_ldap', 'false', 'No'),
        ('scorm_cumulative_session_time','true','Yes'),
        ('scorm_cumulative_session_time','false','No'),
        ('allow_hr_skills_management', 'true', 'Yes'),
        ('allow_hr_skills_management', 'false', 'No'),
        ('enable_help_link', 'true', 'Yes'),
        ('enable_help_link', 'false', 'No'),
        ('allow_users_to_change_email_with_no_password', 'true', 'Yes'),
        ('allow_users_to_change_email_with_no_password', 'false', 'No'),
        ('show_admin_toolbar', 'do_not_show', 'DoNotShow'),
        ('show_admin_toolbar', 'show_to_admin', 'ShowToAdminsOnly'),
        ('show_admin_toolbar', 'show_to_admin_and_teachers', 'ShowToAdminsAndTeachers'),
        ('show_admin_toolbar', 'show_to_all', 'ShowToAllUsers'),
        ('use_custom_pages','true','Yes'),
        ('use_custom_pages','false','No'),
        ('languagePriority1','platform_lang','PlatformLanguage'),
        ('languagePriority1','user_profil_lang','UserLanguage'),
        ('languagePriority1','user_selected_lang','UserSelectedLanguage'),
        ('languagePriority1','course_lang','CourseLanguage'),
        ('languagePriority2','platform_lang','PlatformLanguage'),
        ('languagePriority2','user_profil_lang','UserLanguage'),
        ('languagePriority2','user_selected_lang','UserSelectedLanguage'),
        ('languagePriority2','course_lang','CourseLanguage'),
        ('languagePriority3','platform_lang','PlatformLanguage'),
        ('languagePriority3','user_profil_lang','UserLanguage'),
        ('languagePriority3','user_selected_lang','UserSelectedLanguage'),
        ('languagePriority3','course_lang','CourseLanguage'),
        ('languagePriority4','platform_lang','PlatformLanguage'),
        ('languagePriority4','user_profil_lang','UserLanguage'),
        ('languagePriority4','user_selected_lang','UserSelectedLanguage'),
        ('languagePriority4','course_lang','CourseLanguage'),
        ('allow_global_chat', 'true', 'Yes'),
        ('allow_global_chat', 'false', 'No'),
        ('login_is_email','true','Yes'),
        ('login_is_email','false','No'),
        ('courses_default_creation_visibility', '3', 'OpenToTheWorld'),
        ('courses_default_creation_visibility', '2', 'OpenToThePlatform'),
        ('courses_default_creation_visibility', '1', 'Private'),
        ('courses_default_creation_visibility', '0', 'CourseVisibilityClosed'),
        ('allow_browser_sniffer', 'true', 'Yes'),
        ('allow_browser_sniffer', 'false', 'No'),
        ('enable_wami_record', 'true', 'Yes'),
        ('enable_wami_record', 'false', 'No'),
        ('teachers_can_change_score_settings', 'true', 'Yes'),
        ('teachers_can_change_score_settings', 'false', 'No'),
        ('teachers_can_change_grade_model_settings', 'true', 'Yes'),
        ('teachers_can_change_grade_model_settings', 'false', 'No'),
        ('gradebook_locking_enabled', 'true', 'Yes'),
        ('gradebook_locking_enabled', 'false', 'No'),
        ('gradebook_enable_grade_model', 'true', 'Yes'),
        ('gradebook_enable_grade_model', 'false', 'No'),
        ('allow_session_admins_to_manage_all_sessions', 'true', 'Yes'),
        ('allow_session_admins_to_manage_all_sessions', 'false', 'No'),
        ('allow_skills_tool', 'true', 'Yes'),
        ('allow_skills_tool', 'false', 'No'),
        ('allow_public_certificates', 'true', 'Yes'),
        ('allow_public_certificates', 'false', 'No'),
        ('platform_unsubscribe_allowed', 'true', 'Yes'),
        ('platform_unsubscribe_allowed', 'false', 'No'),
        ('activate_email_template', 'true', 'Yes'),
        ('activate_email_template', 'false', 'No'),
         ('enable_iframe_inclusion', 'true', 'Yes'),
        ('enable_iframe_inclusion', 'false', 'No'),
        ('show_hot_courses', 'true', 'Yes'),
        ('show_hot_courses', 'false', 'No'),
        ('enable_webcam_clip', 'true', 'Yes'),
        ('enable_webcam_clip', 'false', 'No'),
        ('prevent_session_admins_to_manage_all_users', 'true', 'Yes'),
        ('prevent_session_admins_to_manage_all_users', 'false', 'No'),
        ('documents_default_visibility_defined_in_course', 'true', 'Yes'),
        ('documents_default_visibility_defined_in_course', 'false', 'No'),
        ('enabled_mathjax','true','Yes'),
        ('enabled_mathjax','false','No');
    ";
    Database::query($sql);
}

function migrate($to, $chamiloVersion, $dbNameForm, $dbUsernameForm, $dbPassForm, $dbHostForm)
{
    $debug = true;
    // Config doctrine migrations

    $db = \Doctrine\DBAL\DriverManager::getConnection(array(
        'dbname' => $dbNameForm,
        'user' => $dbUsernameForm,
        'password' => $dbPassForm,
        'host' => $dbHostForm,
        'driver' => 'pdo_mysql',
        'charset' => 'utf8',
        'driverOptions' => array(
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'
        )
    ));

    $config = new \Doctrine\DBAL\Migrations\Configuration\Configuration($db);

    // Table name that will store migrations log (will be created automatically, default name is: doctrine_migration_versions)
    $config->setMigrationsTableName('version');
    // Namespace of your migration classes, do not forget escape slashes, do not add last slash
    $config->setMigrationsNamespace('Chamilo\CoreBundle\Migrations\Schema\v'.$chamiloVersion);
    // Directory where your migrations are located

    $config->setMigrationsDirectory(api_get_path(SYS_PATH).'src/Chamilo/CoreBundle/Migrations/Schema/v'.$chamiloVersion);
    // Load your migrations
    $config->registerMigrationsFromDirectory($config->getMigrationsDirectory());
    $migration = new \Doctrine\DBAL\Migrations\Migration($config);
    //$to = 'Version110';
    // Retrieve SQL queries that should be run to migrate you schema to $to version, if $to == null - schema will be migrated to latest version
    $versions = $migration->getSql($to);
    if ($debug) {
        $nl = '<br>';
        foreach ($versions as $version => $queries) {
            echo 'VERSION: '.$version.$nl;
            echo '----------------------------------------------'.$nl.$nl;

            foreach ($queries as $query) {
                echo $query.$nl.$nl;
            }
            echo $nl.$nl;
        }
    }

    try {
        $migration->migrate($to); // Execute migration!
        if ($debug) {
            echo 'DONE'.$nl;
        }
    } catch (Exception $ex) {
        if ($debug) {
            echo 'ERROR: '.$ex->getMessage().$nl;
        }
    }
}


