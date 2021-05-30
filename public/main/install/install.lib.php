<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\BranchSync;
use Chamilo\CoreBundle\Entity\Group;
use Chamilo\CoreBundle\Entity\TicketCategory;
use Chamilo\CoreBundle\Entity\TicketPriority;
use Chamilo\CoreBundle\Entity\TicketProject;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\ToolChain;
use Doctrine\Migrations\Configuration\Connection\ExistingConnection;
use Doctrine\Migrations\Configuration\Migration\PhpFile;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Query\Query;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\Container as SymfonyContainer;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/*
 * Chamilo LMS
 * This file contains functions used by the install and upgrade scripts.
 *
 * Ideas for future additions:
 * - a function get_old_version_settings to retrieve the config file settings
 *   of older versions before upgrading.
 */
define('SYSTEM_CONFIG_FILENAME', 'configuration.dist.php');
define('USERNAME_MAX_LENGTH', 100);

/**
 * This function detects whether the system has been already installed.
 * It should be used for prevention from second running the installation
 * script and as a result - destroying a production system.
 *
 * @return bool The detected result;
 *
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
 * @param string $extensionName Name of the PHP extension to be checked
 * @param string $returnSuccess Text to show when extension is available (defaults to 'Yes')
 * @param string $returnFailure Text to show when extension is available (defaults to 'No')
 * @param bool   $optional      Whether this extension is optional (then show unavailable text in orange rather than
 *                              red)
 * @param string $enabledTerm   If this string is not null, then use to check if the corresponding parameter is = 1.
 *                              If not, mention it's present but not enabled. For example, for opcache, this should be
 *                              'opcache.enable'
 *
 * @return string HTML string reporting the status of this extension. Language-aware.
 *
 * @author  Christophe Gesch??
 * @author  Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @author  Yannick Warnier <yannick.warnier@dokeos.com>
 */
function checkExtension(
    $extensionName,
    $returnSuccess = 'Yes',
    $returnFailure = 'No',
    $optional = false,
    $enabledTerm = ''
) {
    if (extension_loaded($extensionName)) {
        if (!empty($enabledTerm)) {
            $isEnabled = ini_get($enabledTerm);
            if ('1' == $isEnabled) {
                return Display::label($returnSuccess, 'success');
            } else {
                if ($optional) {
                    return Display::label(get_lang('Extension installed but not enabled'), 'warning');
                }

                return Display::label(get_lang('Extension installed but not enabled'), 'important');
            }
        }

        return Display::label($returnSuccess, 'success');
    } else {
        if ($optional) {
            return Display::label($returnFailure, 'warning');
        }

        return Display::label($returnFailure, 'important');
    }
}

/**
 * This function checks whether a php setting matches the recommended value.
 *
 * @param string $phpSetting       A PHP setting to check
 * @param string $recommendedValue A recommended value to show on screen
 * @param mixed  $returnSuccess    What to show on success
 * @param mixed  $returnFailure    What to show on failure
 *
 * @return string A label to show
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 */
function checkPhpSetting(
    $phpSetting,
    $recommendedValue,
    $returnSuccess = false,
    $returnFailure = false
) {
    $currentPhpValue = getPhpSetting($phpSetting);
    if ($currentPhpValue == $recommendedValue) {
        return Display::label($currentPhpValue.' '.$returnSuccess, 'success');
    }

    return Display::label($currentPhpValue.' '.$returnSuccess, 'important');
}

/**
 * This function return the value of a php.ini setting if not "" or if exists,
 * otherwise return false.
 *
 * @param string $phpSetting The name of a PHP setting
 *
 * @return mixed The value of the setting, or false if not found
 */
function checkPhpSettingExists($phpSetting)
{
    if ('' != ini_get($phpSetting)) {
        return ini_get($phpSetting);
    }

    return false;
}

/**
 * Returns a textual value ('ON' or 'OFF') based on a requester 2-state ini- configuration setting.
 *
 * @param string $val a php ini value
 *
 * @return bool ON or OFF
 *
 * @author Joomla <http://www.joomla.org>
 */
function getPhpSetting($val)
{
    $value = ini_get($val);
    switch ($val) {
        case 'display_errors':
            global $originalDisplayErrors;
            $value = $originalDisplayErrors;
            break;
    }

    return '1' == $value ? 'ON' : 'OFF';
}

/**
 * This function returns a string "true" or "false" according to the passed parameter.
 *
 * @param int $var The variable to present as text
 *
 * @return string the string "true" or "false"
 *
 * @author Christophe Gesch??
 */
function trueFalse($var)
{
    return $var ? 'true' : 'false';
}

/**
 * Detects browser's language.
 *
 * @return string Returns a language identificator, i.e. 'english', 'spanish', ...
 *
 * @author Ivan Tcholakov, 2010
 */
function detect_browser_language()
{
    static $language_index = [
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
        'yo' => 'yoruba',
    ];

    $system_available_languages = get_language_folder_list();
    if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        $accept_languages = strtolower(str_replace('_', '-', $_SERVER['HTTP_ACCEPT_LANGUAGE']));
        foreach ($language_index as $code => $language) {
            if (0 === strpos($accept_languages, $code)) {
                if (!empty($system_available_languages[$language])) {
                    return $language;
                }
            }
        }
    }

    if (isset($_SERVER['HTTP_USER_AGENT'])) {
        $user_agent = strtolower(str_replace('_', '-', $_SERVER['HTTP_USER_AGENT']));
        foreach ($language_index as $code => $language) {
            if (@preg_match("/[\[\( ]{$code}[;,_\-\)]/", $user_agent)) {
                if (!empty($system_available_languages[$language])) {
                    return $language;
                }
            }
        }
    }

    return 'english';
}

/**
 * This function checks if the given folder is writable.
 *
 * @param string $folder     Full path to a folder
 * @param bool   $suggestion Whether to show a suggestion or not
 *
 * @return string
 */
function check_writable($folder, $suggestion = false)
{
    if (is_writable($folder)) {
        return Display::label(get_lang('Writable'), 'success');
    } else {
        if ($suggestion) {
            return Display::label(get_lang('Not writable'), 'info');
        } else {
            return Display::label(get_lang('Not writable'), 'important');
        }
    }
}

/**
 * This function checks if the given folder is readable.
 *
 * @param string $folder     Full path to a folder
 * @param bool   $suggestion Whether to show a suggestion or not
 *
 * @return string
 */
function checkReadable($folder, $suggestion = false)
{
    if (is_readable($folder)) {
        return Display::label(get_lang('Readable'), 'success');
    } else {
        if ($suggestion) {
            return Display::label(get_lang('Not readable'), 'info');
        } else {
            return Display::label(get_lang('Not readable'), 'important');
        }
    }
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
 * Write the main system config file.
 *
 * @param string $path Path to the config file
 */
function writeSystemConfigFile($path)
{
    $content = file_get_contents(__DIR__.'/'.SYSTEM_CONFIG_FILENAME);
    $config['{DATE_GENERATED}'] = date('r');
    $config['{SECURITY_KEY}'] = md5(uniqid(rand().time()));

    foreach ($config as $key => $value) {
        $content = str_replace($key, $value, $content);
    }
    $fp = @fopen($path, 'w');

    if (!$fp) {
        echo '<strong>
                <font color="red">Your script doesn\'t have write access to the config directory</font></strong><br />
                <em>('.str_replace('\\', '/', realpath($path)).')</em><br /><br />
                You probably do not have write access on Chamilo root directory,
                i.e. you should <em>CHMOD 777</em> or <em>755</em> or <em>775</em>.<br /><br />
                Your problems can be related on two possible causes:<br />
                <ul>
                  <li>Permission problems.<br />Try initially with <em>chmod -R 777</em> and increase restrictions gradually.</li>
                  <li>PHP is running in <a href="http://www.php.net/manual/en/features.safe-mode.php" target="_blank">Safe-Mode</a>.
                  If possible, try to switch it off.</li>
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
function get_language_folder_list()
{
    return [
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
        'yo' => 'yoruba',
    ];
}

/**
 * This function returns the value of a parameter from the configuration file.
 *
 * WARNING - this function relies heavily on global variables $updateFromConfigFile
 * and $configFile, and also changes these globals. This can be rewritten.
 *
 * @param string $param      the parameter of which the value is returned
 * @param string $updatePath If we want to give the path rather than take it from POST
 *
 * @return string the value of the parameter
 *
 * @author Olivier Brouckaert
 * @author Reworked by Ivan Tcholakov, 2010
 */
function get_config_param($param, $updatePath = '')
{
    global $updateFromConfigFile;
    if (empty($updatePath) && !empty($_POST['updatePath'])) {
        $updatePath = $_POST['updatePath'];
    }

    if (empty($updatePath)) {
        $updatePath = api_get_path(SYMFONY_SYS_PATH);
    }
    $updatePath = api_add_trailing_slash(str_replace('\\', '/', realpath($updatePath)));

    if (empty($updateFromConfigFile)) {
        // If update from previous install was requested,
        if (file_exists($updatePath.'app/config/configuration.php')) {
            $updateFromConfigFile = 'app/config/configuration.php';
        } else {
            // Give up recovering.
            return null;
        }
    }

    if (file_exists($updatePath.$updateFromConfigFile) &&
        !is_dir($updatePath.$updateFromConfigFile)
    ) {
        require $updatePath.$updateFromConfigFile;
        $config = new Laminas\Config\Config($_configuration);

        return $config->get($param);
    }

    error_log('Config array could not be found in get_config_param()', 0);

    return null;
}

/**
 * Gets a configuration parameter from the database. Returns returns null on failure.
 *
 * @param string $param Name of param we want
 *
 * @return mixed The parameter value or null if not found
 */
function get_config_param_from_db($param = '')
{
    $param = Database::escape_string($param);

    if (false !== ($res = Database::query("SELECT * FROM settings_current WHERE variable = '$param'"))) {
        if (Database::num_rows($res) > 0) {
            $row = Database::fetch_array($res);

            return $row['selected_value'];
        }
    }

    return null;
}

/**
 * Connect to the database and returns the entity manager.
 *
 * @param string $host
 * @param string $username
 * @param string $password
 * @param string $databaseName
 * @param int    $port
 *
 * @return \Database
 */
function connectToDatabase(
    $host,
    $username,
    $password,
    $databaseName,
    $port = 3306
) {
    $database = new \Database();
    $database->connect(
        [
            'driver' => 'pdo_mysql',
            'host' => $host,
            'port' => $port,
            'user' => $username,
            'password' => $password,
            'dbname' => $databaseName,
        ]
    );

    return $database;
}

/**
 * This function prints class=active_step $current_step=$param.
 *
 * @param int $param A step in the installer process
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 */
function step_active($param)
{
    global $current_step;
    if ($param == $current_step) {
        echo 'active';
    }
}

/**
 * This function displays the Step X of Y -.
 *
 * @return string String that says 'Step X of Y' with the right values
 */
function display_step_sequence()
{
    global $current_step;

    return get_lang('Step'.$current_step).' &ndash; ';
}

/**
 * Displays a drop down box for selection the preferred language.
 */
function display_language_selection_box($name = 'language_list', $default_language = 'en')
{
    // Reading language list.
    $language_list = get_language_folder_list();

    // Sanity checks due to the possibility for customizations.
    if (!is_array($language_list) || empty($language_list)) {
        $language_list = ['en' => 'English'];
    }

    // Sorting again, if it is necessary.
    //asort($language_list);

    // More sanity checks.
    if (!array_key_exists($default_language, $language_list)) {
        if (array_key_exists('en', $language_list)) {
            $default_language = 'en';
        } else {
            $language_keys = array_keys($language_list);
            $default_language = $language_keys[0];
        }
    }

    // Displaying the box.
    return Display::select(
        'language_list',
        $language_list,
        $default_language,
        ['class' => 'form-control'],
        false
    );
}

/**
 * This function displays a language dropdown box so that the installatioin
 * can be done in the language of the user.
 */
function display_language_selection()
{
    ?>
        <div class="install-icon">
            <img width="150px;" src="chamilo-install.svg"/>
        </div>
        <h2 class="text-2xl">
            <?php echo display_step_sequence(); ?>
            <?php echo get_lang('Installation Language'); ?>
        </h2>
        <label for="language_list"><?php echo get_lang('Please select installation language'); ?></label>
        <div class="form-group">
            <?php echo display_language_selection_box('language_list', api_get_interface_language()); ?>
        </div>
        <button type="submit" name="step1" class="btn btn-success" value="<?php echo get_lang('Next'); ?>">
            <em class="fa fa-forward"> </em>
            <?php echo get_lang('Next'); ?>
        </button>
        <input type="hidden" name="is_executable" id="is_executable" value="-" />
        <div class="RequirementHeading">
            <?php echo get_lang('Cannot find your language in the list? Contact us at info@chamilo.org to contribute as a translator.'); ?>
        </div>
<?php
}

/**
 * This function displays the requirements for installing Chamilo.
 *
 * @param string $installType
 * @param bool   $badUpdatePath
 * @param bool   $badUpdatePath
 * @param string $updatePath         The updatePath given (if given)
 * @param array  $upgradeFromVersion The different subversions from version 1.9
 *
 * @author unknow
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 */
function display_requirements(
    $installType,
    $badUpdatePath,
    $updatePath = '',
    $upgradeFromVersion = []
) {
    global $_setting, $originalMemoryLimit;

    $dir = api_get_path(SYS_ARCHIVE_PATH).'temp/';
    $fileToCreate = 'test';

    $perms_dir = [0777, 0755, 0775, 0770, 0750, 0700];
    $perms_fil = [0666, 0644, 0664, 0660, 0640, 0600];
    $course_test_was_created = false;
    $dir_perm_verified = 0777;

    foreach ($perms_dir as $perm) {
        $r = @mkdir($dir, $perm);
        if (true === $r) {
            $dir_perm_verified = $perm;
            $course_test_was_created = true;
            break;
        }
    }

    $fil_perm_verified = 0666;
    $file_course_test_was_created = false;
    if (is_dir($dir)) {
        foreach ($perms_fil as $perm) {
            if (true == $file_course_test_was_created) {
                break;
            }
            $r = @touch($dir.'/'.$fileToCreate, $perm);
            if (true === $r) {
                $fil_perm_verified = $perm;
                $file_course_test_was_created = true;
            }
        }
    }

    @unlink($dir.'/'.$fileToCreate);
    @rmdir($dir);

    echo '<h2 class="install-title">'.display_step_sequence().get_lang('Requirements').'</h2>';
    echo '<div class="RequirementText">';
    echo '<strong>'.get_lang('Please read the following requirements thoroughly.').'</strong><br />';
    echo get_lang('For more details').'
        <a href="../../documentation/installation_guide.html" target="_blank">'.
        get_lang('Read the installation guide').'</a>.<br />'."\n";

    if ('update' == $installType) {
        echo get_lang(
            'If you plan to upgrade from an older version of Chamilo, you might want to <a href="../../documentation/changelog.html" target="_blank">have a look at the changelog</a> to know what\'s new and what has been changed').'<br />';
    }
    echo '</div>';

    //  SERVER REQUIREMENTS
    echo '<h4 class="install-subtitle">'.get_lang('Server requirements').'</h4>';
    $timezone = checkPhpSettingExists('date.timezone');
    if (!$timezone) {
        echo "<div class='alert alert-warning'>
            <i class=\"fa fa-exclamation-triangle\" aria-hidden=\"true\"></i>&nbsp;".
            get_lang('We have detected that your PHP installation does not define the date.timezone setting. This is a requirement of Chamilo. Please make sure it is configured by checking your php.ini configuration, otherwise you will run into problems. We warned you!').'</div>';
    }

    echo '<div class="install-requirement">'.get_lang('Server requirementsInfo').'</div>';
    echo '<div class="table-responsive">';
    echo '<table class="table table-bordered table-sm">
            <tr>
                <td class="requirements-item">'.get_lang('PHP version').' >= '.REQUIRED_PHP_VERSION.'</td>
                <td class="requirements-value">';
    if (version_compare(phpversion(), REQUIRED_PHP_VERSION, '>=') > 1) {
        echo '<strong class="text-danger">'.get_lang('PHP versionError').'</strong>';
    } else {
        echo '<strong class="text-success">'.get_lang('PHP versionOK').' '.phpversion().'</strong>';
    }
    echo '</td>
            </tr>
            <tr>
                <td class="requirements-item">
                    <a href="http://php.net/manual/en/book.session.php" target="_blank">Session</a>
                    '.get_lang('Support').'</td>
                <td class="requirements-value">'.
        checkExtension('session', get_lang('Yes'), get_lang('Sessions extension not available')).'</td>
            </tr>
            <tr>
                <td class="requirements-item">
                    <a href="http://php.net/manual/en/book.mysql.php" target="_blank">pdo_mysql</a> '.get_lang('Support').'</td>
                <td class="requirements-value">'.
                    checkExtension('pdo_mysql', get_lang('Yes'), get_lang('MySQL extension not available')).'</td>
            </tr>
            <tr>
                <td class="requirements-item">
                    <a href="http://php.net/manual/en/book.zip.php" target="_blank">Zip</a> '.get_lang('Support').'</td>
                <td class="requirements-value">'.
                checkExtension('zip', get_lang('Yes'), get_lang('Extension not available')).'</td>
            </tr>
            <tr>
                <td class="requirements-item">
                    <a href="http://php.net/manual/en/book.zlib.php" target="_blank">Zlib</a> '.get_lang('Support').'</td>
                <td class="requirements-value">'.
                checkExtension('zlib', get_lang('Yes'), get_lang('Zlib extension not available')).'</td>
            </tr>
            <tr>
                <td class="requirements-item">
                    <a href="http://php.net/manual/en/book.pcre.php" target="_blank">Perl-compatible regular expressions</a> '.get_lang('Support').'</td>
                <td class="requirements-value">'.
                    checkExtension('pcre', get_lang('Yes'), get_lang('PCRE extension not available')).'</td>
            </tr>
            <tr>
                <td class="requirements-item">
                    <a href="http://php.net/manual/en/book.xml.php" target="_blank">XML</a> '.get_lang('Support').'</td>
                <td class="requirements-value">'.
                    checkExtension('xml', get_lang('Yes'), get_lang('No')).'</td>
            </tr>
            <tr>
                <td class="requirements-item">
                    <a href="http://php.net/manual/en/book.intl.php" target="_blank">Internationalization</a> '.get_lang('Support').'</td>
                <td class="requirements-value">'.checkExtension('intl', get_lang('Yes'), get_lang('No')).'</td>
            </tr>
               <tr>
                <td class="requirements-item">
                    <a href="http://php.net/manual/en/book.json.php" target="_blank">JSON</a> '.get_lang('Support').'</td>
                <td class="requirements-value">'.checkExtension('json', get_lang('Yes'), get_lang('No')).'</td>
            </tr>
             <tr>
                <td class="requirements-item">
                    <a href="http://php.net/manual/en/book.image.php" target="_blank">GD</a> '.get_lang('Support').'</td>
                <td class="requirements-value">'.
                    checkExtension('gd', get_lang('Yes'), get_lang('GD Extension not available')).'</td>
            </tr>
            <tr>
                <td class="requirements-item">
                    <a href="http://php.net/manual/en/book.curl.php" target="_blank">cURL</a>'.get_lang('Support').'</td>
                <td class="requirements-value">'.
                checkExtension('curl', get_lang('Yes'), get_lang('No')).'</td>
            </tr>
            <tr>
                <td class="requirements-item">
                    <a href="http://php.net/manual/en/book.mbstring.php" target="_blank">Multibyte string</a> '.get_lang('Support').'</td>
                <td class="requirements-value">'.
                    checkExtension('mbstring', get_lang('Yes'), get_lang('MBString extension not available'), true).'</td>
            </tr>
           <tr>
                <td class="requirements-item">
                    <a href="http://php.net/manual/en/book.exif.php" target="_blank">Exif</a> '.get_lang('Support').'</td>
                <td class="requirements-value">'.
                    checkExtension('exif', get_lang('Yes'), get_lang('Exif extension not available'), true).'</td>
            </tr>
            <tr>
                <td class="requirements-item">
                    <a href="http://php.net/opcache" target="_blank">Zend OpCache</a> '.get_lang('Support').' ('.get_lang('Optional').')</td>
                <td class="requirements-value">'.
                    checkExtension('Zend OPcache', get_lang('Yes'), get_lang('No'), true, 'opcache.enable').'</td>
            </tr>
            <tr>
                <td class="requirements-item">
                    <a href="http://php.net/apcu" target="_blank">APCu</a> '.get_lang('Support').' ('.get_lang('Optional').')</td>
                <td class="requirements-value">'.
                    checkExtension('apcu', get_lang('Yes'), get_lang('No'), true, 'apc.enabled').'</td>
            </tr>
            <tr>
                <td class="requirements-item">
                    <a href="http://php.net/manual/en/book.iconv.php" target="_blank">Iconv</a> '.get_lang('Support').' ('.get_lang('Optional').')</td>
                <td class="requirements-value">'.
                    checkExtension('iconv', get_lang('Yes'), get_lang('No'), true).'</td>
            </tr>
            <tr>
                <td class="requirements-item">
                        <a href="http://php.net/manual/en/book.ldap.php" target="_blank">LDAP</a> '.get_lang('Support').' ('.get_lang('Optional').')</td>
                <td class="requirements-value">'.
                checkExtension('ldap', get_lang('Yes'), get_lang('LDAP Extension not available'), true).'</td>
            </tr>
            <tr>
                <td class="requirements-item">
                    <a href="http://xapian.org/" target="_blank">Xapian</a> '.get_lang('Support').' ('.get_lang('Optional').')</td>
                <td class="requirements-value">'.
                    checkExtension('xapian', get_lang('Yes'), get_lang('No'), true).'</td>
            </tr>
        </table>';
    echo '</div>';

    // RECOMMENDED SETTINGS
    // Note: these are the settings for Joomla, does this also apply for Chamilo?
    // Note: also add upload_max_filesize here so that large uploads are possible
    echo '<h4 class="install-subtitle">'.get_lang('(recommended) settings').'</h4>';
    echo '<div class="install-requirement">'.get_lang('(recommended) settingsInfo').'</div>';
    echo '<div class="table-responsive">';
    echo '<table class="table table-bordered table-sm">
            <tr>
                <th>'.get_lang('Setting').'</th>
                <th>'.get_lang('(recommended)').'</th>
                <th>'.get_lang('Currently').'</th>
            </tr>
            <tr>
                <td class="requirements-item">
                <a href="http://php.net/manual/ref.errorfunc.php#ini.display-errors">Display Errors</a></td>
                <td class="requirements-recommended">'.Display::label('OFF', 'success').'</td>
                <td class="requirements-value">'.checkPhpSetting('display_errors', 'OFF').'</td>
            </tr>
            <tr>
                <td class="requirements-item">
                <a href="http://php.net/manual/ini.core.php#ini.file-uploads">File Uploads</a></td>
                <td class="requirements-recommended">'.Display::label('ON', 'success').'</td>
                <td class="requirements-value">'.checkPhpSetting('file_uploads', 'ON').'</td>
            </tr>
            <tr>
                <td class="requirements-item">
                <a href="http://php.net/manual/ref.session.php#ini.session.auto-start">Session auto start</a></td>
                <td class="requirements-recommended">'.Display::label('OFF', 'success').'</td>
                <td class="requirements-value">'.checkPhpSetting('session.auto_start', 'OFF').'</td>
            </tr>
            <tr>
                <td class="requirements-item">
                <a href="http://php.net/manual/ini.core.php#ini.short-open-tag">Short Open Tag</a></td>
                <td class="requirements-recommended">'.Display::label('OFF', 'success').'</td>
                <td class="requirements-value">'.checkPhpSetting('short_open_tag', 'OFF').'</td>
            </tr>
            <tr>
                <td class="requirements-item">
                    <a href="http://www.php.net/manual/en/session.configuration.php#ini.session.cookie-httponly">Cookie HTTP Only</a></td>
                <td class="requirements-recommended">'.
                    Display::label('ON', 'success').'</td>
                <td class="requirements-value">'.checkPhpSetting('session.cookie_httponly', 'ON').'</td>
            </tr>
            <tr>
                <td class="requirements-item">
                    <a href="http://php.net/manual/ini.core.php#ini.upload-max-filesize">Maximum upload file size</a></td>
                <td class="requirements-recommended">'.
                    Display::label('>= '.REQUIRED_MIN_UPLOAD_MAX_FILESIZE.'M', 'success').'</td>
                <td class="requirements-value">'.compare_setting_values(ini_get('upload_max_filesize'), REQUIRED_MIN_UPLOAD_MAX_FILESIZE).'</td>
            </tr>
            <tr>
                <td class="requirements-item">
                    <a href="http://php.net/manual/ini.core.php#ini.post-max-size">Maximum post size</a></td>
                <td class="requirements-recommended">'.
                Display::label('>= '.REQUIRED_MIN_POST_MAX_SIZE.'M', 'success').'</td>
                <td class="requirements-value">'.compare_setting_values(ini_get('post_max_size'), REQUIRED_MIN_POST_MAX_SIZE).'</td>
            </tr>
            <tr>
                <td class="requirements-item">
                    <a href="http://www.php.net/manual/en/ini.core.php#ini.memory-limit">Memory Limit</a></td>
                <td class="requirements-recommended">'.
                    Display::label('>= '.REQUIRED_MIN_MEMORY_LIMIT.'M', 'success').'</td>
                <td class="requirements-value">'.compare_setting_values($originalMemoryLimit, REQUIRED_MIN_MEMORY_LIMIT).'</td>
            </tr>
          </table>';
    echo '</div>';

    // DIRECTORY AND FILE PERMISSIONS
    echo '<h4 class="install-subtitle">'.get_lang('Directory and files permissions').'</h4>';
    echo '<div class="install-requirement">'.get_lang('Directory and files permissionsInfo').'</div>';
    echo '<div class="table-responsive">';

    $_SESSION['permissions_for_new_directories'] = $_setting['permissions_for_new_directories'] = $dir_perm_verified;
    $_SESSION['permissions_for_new_files'] = $_setting['permissions_for_new_files'] = $fil_perm_verified;

    $dir_perm = Display::label('0'.decoct($dir_perm_verified), 'info');
    $file_perm = Display::label('0'.decoct($fil_perm_verified), 'info');

    $oldConf = '';
    if (file_exists(api_get_path(SYS_CODE_PATH).'inc/conf/configuration.php')) {
        $oldConf = '<tr>
            <td class="requirements-item">'.api_get_path(SYS_CODE_PATH).'inc/conf</td>
            <td class="requirements-value">'.check_writable(api_get_path(SYS_CODE_PATH).'inc/conf').'</td>
        </tr>';
    }
    $basePath = api_get_path(SYMFONY_SYS_PATH);
    echo '<table class="table table-bordered table-sm">
            '.$oldConf.'
            <tr>
                <td class="requirements-item">'.$basePath.'var/</td>
                <td class="requirements-value">'.check_writable($basePath.'var').'</td>
            </tr>
            <tr>
                <td class="requirements-item">'.$basePath.'.env.local</td>
                <td class="requirements-value">'.checkCanCreateFile($basePath.'.env.local').'</td>
            </tr>
            <tr>
                <td class="requirements-item">'.$basePath.'config/</td>
                <td class="requirements-value">'.check_writable($basePath.'config').'</td>
            </tr>
            <tr>
                <td class="requirements-item">'.get_lang('Permissions for new directories').'</td>
                <td class="requirements-value">'.$dir_perm.' </td>
            </tr>
            <tr>
                <td class="requirements-item">'.get_lang('Permissions for new files').'</td>
                <td class="requirements-value">'.$file_perm.' </td>
            </tr>
        </table>';
    echo '</div>';

    if ('update' === $installType && (empty($updatePath) || $badUpdatePath)) {
        if ($badUpdatePath) {
            echo '<div class="alert alert-warning">';
            echo get_lang('Error');
            echo '<br />';
            echo 'Chamilo '.implode('|', $upgradeFromVersion).' '.get_lang('has not been found in that directory').'</div>';
        } else {
            echo '<br />';
        } ?>
            <div class="row">
                <div class="col-md-12">
                    <p><?php echo get_lang('Old version\'s root path'); ?>:
                        <input
                            type="text"
                            name="updatePath" size="50"
                            value="<?php echo ($badUpdatePath && !empty($updatePath)) ? htmlentities($updatePath) : ''; ?>" />
                    </p>
                    <p>
                        <div class="btn-group">
                            <button type="submit" class="btn btn-secondary" name="step1" value="<?php echo get_lang('Back'); ?>" >
                                <em class="fa fa-backward"> <?php echo get_lang('Back'); ?></em>
                            </button>
                            <input type="hidden" name="is_executable" id="is_executable" value="-" />
                            <button
                                type="submit"
                                class="btn btn-success"
                                name="<?php echo isset($_POST['step2_update_6']) ? 'step2_update_6' : 'step2_update_8'; ?>"
                                value="<?php echo get_lang('Next'); ?> &gt;" >
                                <em class="fa fa-forward"> </em> <?php echo get_lang('Next'); ?>
                            </button>
                        </div>
                    </p>
                </div>
            </div>
        <?php
    } else {
        $error = false;
        // First, attempt to set writing permissions if we don't have them yet
        //$perm = api_get_permissions_for_new_directories();
        $perm = octdec('0777');
        //$perm_file = api_get_permissions_for_new_files();
        $perm_file = octdec('0666');
        $notWritable = [];

        $checked_writable = api_get_path(SYS_PUBLIC_PATH);
        if (!is_writable($checked_writable)) {
            $notWritable[] = $checked_writable;
            @chmod($checked_writable, $perm);
        }

        if (false == $course_test_was_created) {
            error_log('Installer: Could not create test course - Make sure permissions are fine.');
            $error = true;
        }

        $checked_writable = api_get_path(CONFIGURATION_PATH).'configuration.php';
        if (file_exists($checked_writable) && !is_writable($checked_writable)) {
            $notWritable[] = $checked_writable;
            @chmod($checked_writable, $perm_file);
        }

        // Second, if this fails, report an error
        //--> The user would have to adjust the permissions manually
        if (count($notWritable) > 0) {
            error_log('Installer: At least one needed directory or file is not writeable');
            $error = true; ?>
            <div class="text-danger">
                <h3 class="text-center"><?php echo get_lang('Warning !'); ?></h3>
                <p>
                    <?php printf(get_lang('Some files or folders don\'t have writing permission. To be able to install Chamilo you should first change their permissions (using CHMOD). Please read the %s installation guide %s'), '<a href="../../documentation/installation_guide.html" target="blank">', '</a>'); ?>
                </p>
            </div>
            <?php
            echo '<ul>';
            foreach ($notWritable as $value) {
                echo '<li class="text-danger">'.$value.'</li>';
            }
            echo '</ul>';
        } elseif (file_exists(api_get_path(CONFIGURATION_PATH).'configuration.php')) {
            // Check wether a Chamilo configuration file already exists.
            echo '<div class="alert alert-warning"><h4><center>';
            echo get_lang('Warning !ExistingLMSInstallationDetected');
            echo '</center></h4></div>';
        }

        $deprecated = [
            api_get_path(SYS_CODE_PATH).'exercice/',
            api_get_path(SYS_CODE_PATH).'newscorm/',
            api_get_path(SYS_PLUGIN_PATH).'ticket/',
            api_get_path(SYS_PLUGIN_PATH).'skype/',
        ];
        $deprecatedToRemove = [];
        foreach ($deprecated as $deprecatedDirectory) {
            if (!is_dir($deprecatedDirectory)) {
                continue;
            }
            $deprecatedToRemove[] = $deprecatedDirectory;
        }

        if (count($deprecatedToRemove) > 0) {
            ?>
            <p class="text-danger"><?php echo get_lang('Warning !ForDeprecatedDirectoriesForUpgrade'); ?></p>
            <ul>
                <?php foreach ($deprecatedToRemove as $deprecatedDirectory) {
                ?>
                    <li class="text-danger"><?php echo $deprecatedDirectory; ?></li>
                <?php
            } ?>
            </ul>
            <?php
        }

        // And now display the choice buttons (go back or install)?>
        <p align="center" style="padding-top:15px">
            <button
                type="submit"
                name="step1"
                class="btn btn-default"
                onclick="javascript: window.location='index.php'; return false;"
                value="<?php echo get_lang('Previous'); ?>" >
                <em class="fa fa-backward"> </em> <?php echo get_lang('Previous'); ?>
            </button>
            <button
                type="submit" name="step2_install"
                class="btn btn-success"
                value="<?php echo get_lang('New installation'); ?>" <?php if ($error) {
            echo 'disabled="disabled"';
        } ?> >
                <em class="fa fa-forward"> </em> <?php echo get_lang('New installation'); ?>
            </button>
            <input type="hidden" name="is_executable" id="is_executable" value="-" />
            <button
                type="submit"
                class="btn btn-default" <?php echo !$error ?: 'disabled="disabled"'; ?>
                name="step2_update_8"
                value="Upgrade from Chamilo 1.11.x">
                <em class="fa fa-forward" aria-hidden="true"></em>
                <?php echo get_lang('Upgrade Chamilo LMS version'); ?>
            </button>
            </p>
        <?php
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
    echo '<p>'.get_lang('Chamilo is free software distributed under the GNU General Public licence (GPL).').'</p>';
    echo '<p><a href="../../documentation/license.html" target="_blank">'.get_lang('Printable version').'</a></p>';
    $license = api_htmlentities(@file_get_contents(api_get_path(SYMFONY_SYS_PATH).'public/documentation/license.txt'));
    echo '</div>';

    echo '<div class="form-group">
        <pre style="overflow: auto; height: 200px; margin-top: 5px;">
            '.$license.'
        </pre>
    </div>
    <div class="form-group form-check">
        <input type="checkbox" name="accept" id="accept_licence" value="1">
        <label for="accept_licence">'.get_lang('I Accept').'</label>
    </div>
    <div class="row">
        <div class="col-md-12">
            <p class="alert alert-info">'.
            get_lang('The images and media galleries of Chamilo use images from Nuvola, Crystal Clear and Tango icon galleries. Other images and media like diagrams and Flash animations are borrowed from Wikimedia and Ali Pakdel\'s and Denis Hoa\'s courses with their agreement and released under BY-SA Creative Commons license. You may find the license details at <a href="http://creativecommons.org/licenses/by-sa/3.0/">the CC website</a>, where a link to the full text of the license is provided at the bottom of the page.').'
            </p>
        </div>
    </div>
    <!-- Contact information form -->
    <div class="section-parameters">
        <a href="javascript://" class = "advanced_parameters" >
        <span id="img_plus_and_minus">&nbsp;<i class="fa fa-eye" aria-hidden="true"></i>&nbsp;'.get_lang('Contact information').'</span>
        </a>
    </div>
    <div id="id_contact_form" style="display:block">
        <div class="normal-message">'.get_lang('Contact informationDescription').'</div>
        <div id="contact_registration">
            <p>'.get_contact_registration_form().'</p><br />
        </div>
    </div>
    <div class="text-center">
    <button type="submit" class="btn btn-default" name="step1" value="&lt; '.get_lang('Previous').'" >
        <em class="fa fa-backward"> </em> '.get_lang('Previous').'
    </button>
    <input type="hidden" name="is_executable" id="is_executable" value="-" />
    <button
        type="submit"
        id="license-next"
        class="btn btn-success" name="step3"
        onclick="javascript:if(!document.getElementById(\'accept_licence\').checked) { alert(\''.get_lang('You must accept the licence').'\');return false;}"
        value="'.get_lang('Next').' &gt;">
        <em class="fa fa-forward"> </em>'.get_lang('Next').'
    </button>
    </div>';
}

/**
 * Get contact registration form.
 */
function get_contact_registration_form()
{
    return '
    <div class="form-horizontal">
        <div class="panel panel-default">
        <div class="panel-body">
        <div id="div_sent_information"></div>
        <div class="form-group row">
                <label class="col-sm-3">
                <span class="form_required">*</span>'.get_lang('Name').'</label>
                <div class="col-sm-9">
                    <input id="person_name" class="form-control" type="text" name="person_name" size="30" />
                </div>
        </div>
        <div class="form-group row">
            <label class="col-sm-3">
            <span class="form_required">*</span>'.get_lang('e-mail').'</label>
            <div class="col-sm-9">
            <input id="person_email" class="form-control" type="text" name="person_email" size="30" /></div>
        </div>
        <div class="form-group row">
                <label class="col-sm-3">
                <span class="form_required">*</span>'.get_lang('Your company\'s name').'</label>
                <div class="col-sm-9">
                <input id="company_name" class="form-control" type="text" name="company_name" size="30" /></div>
        </div>
        <div class="form-group row">
            <label class="col-sm-3"><span class="form_required">*</span>'.get_lang('Your company\'s activity').'</label>
            <div class="col-sm-9">
                <select class="form-control show-tick" name="company_activity" id="company_activity" >
                    <option value="">--- '.get_lang('Select one').' ---</option>
                    <Option value="Advertising/Marketing/PR">Advertising/Marketing/PR</Option>
                    <Option value="Agriculture/Forestry">Agriculture/Forestry</Option>
                    <Option value="Architecture">Architecture</Option>
                    <Option value="Banking/Finance">Banking/Finance</Option>
                    <Option value="Biotech/Pharmaceuticals">Biotech/Pharmaceuticals</Option>
                    <Option value="Business Equipment">Business Equipment</Option>
                    <Option value="Business Services">Business Services</Option>
                    <Option value="Construction">Construction</Option>
                    <Option value="Consulting/Research">Consulting/Research</Option>
                    <Option value="Education">Education</Option>
                    <Option value="Engineering">Engineering</Option>
                    <Option value="Environmental">Environmental</Option>
                    <Option value="Government">Government</Option>
                    <Option value="Healthcare">Health Care</Option>
                    <Option value="Hospitality/Lodging/Travel">Hospitality/Lodging/Travel</Option>
                    <Option value="Insurance">Insurance</Option>
                    <Option value="Legal">Legal</Option><Option value="Manufacturing">Manufacturing</Option>
                    <Option value="Media/Entertainment">Media/Entertainment</Option>
                    <Option value="Mortgage">Mortgage</Option>
                    <Option value="Non-Profit">Non-Profit</Option>
                    <Option value="Real Estate">Real Estate</Option>
                    <Option value="Restaurant">Restaurant</Option>
                    <Option value="Retail">Retail</Option>
                    <Option value="Shipping/Transportation">Shipping/Transportation</Option>
                    <Option value="Technology">Technology</Option>
                    <Option value="Telecommunications">Telecommunications</Option>
                    <Option value="Other">Other</Option>
                </select>
            </div>
        </div>

        <div class="form-group row">
            <label class="col-sm-3"><span class="form_required">*</span>'.get_lang('Your job\'s description').'</label>
            <div class="col-sm-9">
                <select class="form-control show-tick" name="person_role" id="person_role" >
                    <option value="">--- '.get_lang('Select one').' ---</option>
                    <Option value="Administration">Administration</Option>
                    <Option value="CEO/President/ Owner">CEO/President/ Owner</Option>
                    <Option value="CFO">CFO</Option><Option value="CIO/CTO">CIO/CTO</Option>
                    <Option value="Consultant">Consultant</Option>
                    <Option value="Customer Service">Customer Service</Option>
                    <Option value="Engineer/Programmer">Engineer/Programmer</Option>
                    <Option value="Facilities/Operations">Facilities/Operations</Option>
                    <Option value="Finance/ Accounting Manager">Finance/ Accounting Manager</Option>
                    <Option value="Finance/ Accounting Staff">Finance/ Accounting Staff</Option>
                    <Option value="General Manager">General Manager</Option>
                    <Option value="Human Resources">Human Resources</Option>
                    <Option value="IS/IT Management">IS/IT Management</Option>
                    <Option value="IS/ IT Staff">IS/ IT Staff</Option>
                    <Option value="Marketing Manager">Marketing Manager</Option>
                    <Option value="Marketing Staff">Marketing Staff</Option>
                    <Option value="Partner/Principal">Partner/Principal</Option>
                    <Option value="Purchasing Manager">Purchasing Manager</Option>
                    <Option value="Sales/ Business Dev. Manager">Sales/ Business Dev. Manager</Option>
                    <Option value="Sales/ Business Dev.">Sales/ Business Dev.</Option>
                    <Option value="Vice President/Senior Manager">Vice President/Senior Manager</Option>
                    <Option value="Other">Other</Option>
                </select>
            </div>
        </div>

        <div class="form-group row">
            <label class="col-sm-3">
                <span class="form_required">*</span>'.get_lang('Your company\'s home country').'</label>
            <div class="col-sm-9">'.get_countries_list_from_array(true).'</div>
        </div>
        <div class="form-group row">
            <label class="col-sm-3">'.get_lang('Company city').'</label>
            <div class="col-sm-9">
                    <input type="text" class="form-control" id="company_city" name="company_city" size="30" />
            </div>
        </div>
        <div class="form-group row">
            <label class="col-sm-3">'.get_lang('Preferred contact language').'</label>
            <div class="col-sm-9">
                <select class="form-control show-tick" id="language" name="language">
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

        <div class="form-group row">
            <label class="col-sm-3">'.
                get_lang('Do you have the power to take financial decisions on behalf of your company?').'</label>
            <div class="col-sm-9">
                <div class="radio">
                    <label>
                        <input type="radio" name="financial_decision" id="financial_decision1" value="1" checked /> '.
                        get_lang('Yes').'
                    </label>
                </div>
                <div class="radio">
                    <label>
                        <input type="radio" name="financial_decision" id="financial_decision2" value="0" /> '.
                        get_lang('No').'
                    </label>
                </div>
            </div>
        </div>
        <div class="clear"></div>
        <div class="form-group row">
            <div class="col-sm-3">&nbsp;</div>
            <div class="col-sm-9">
            <button
                type="button"
                class="btn btn-default"
                onclick="javascript:send_contact_information();"
                value="'.get_lang('Send information').'" >
                <em class="fa fa-check"></em> '.get_lang('Send information').'
            </button>
            <span id="loader-button"></span></div>
        </div>
        <div class="form-group row">
            <div class="col-sm-3">&nbsp;</div>
            <div class="col-sm-9">
                <span class="form_required">*</span><small>'.get_lang('Mandatory field').'</small>
            </div>
        </div></div></div>
        </div>';
}

/**
 * Displays a parameter in a table row.
 * Used by the display_database_settings_form function.
 *
 * @param   string  Type of install
 * @param   string  Name of parameter
 * @param   string  Field name (in the HTML form)
 * @param   string  Field value
 * @param   string  Extra notice (to show on the right side)
 * @param   bool Whether to display in update mode
 * @param   string  Additional attribute for the <tr> element
 */
function displayDatabaseParameter(
    $installType,
    $parameterName,
    $formFieldName,
    $parameterValue,
    $extra_notice,
    $displayWhenUpdate = true
) {
    echo "<dt class='col-sm-4'>$parameterName</dt>";
    echo '<dd class="col-sm-8">';
    if (INSTALL_TYPE_UPDATE == $installType && $displayWhenUpdate) {
        echo '<input
                type="hidden"
                name="'.$formFieldName.'"
                id="'.$formFieldName.'"
                value="'.api_htmlentities($parameterValue).'" />'.$parameterValue;
    } else {
        $inputType = 'dbPassForm' === $formFieldName ? 'password' : 'text';
        //Slightly limit the length of the database prefix to avoid having to cut down the databases names later on
        $maxLength = 'dbPrefixForm' === $formFieldName ? '15' : MAX_FORM_FIELD_LENGTH;
        if (INSTALL_TYPE_UPDATE == $installType) {
            echo '<input
                type="hidden" name="'.$formFieldName.'" id="'.$formFieldName.'"
                value="'.api_htmlentities($parameterValue).'" />';
            echo api_htmlentities($parameterValue);
        } else {
            echo '<input
                        type="'.$inputType.'"
                        class="form-control"
                        size="'.DATABASE_FORM_FIELD_DISPLAY_LENGTH.'"
                        maxlength="'.$maxLength.'"
                        name="'.$formFieldName.'"
                        id="'.$formFieldName.'"
                        value="'.api_htmlentities($parameterValue).'" />
                    '.$extra_notice.'
                  ';
        }
    }
    echo '</dd>';
}

/**
 * Displays step 3 - a form where the user can enter the installation settings
 * regarding the databases - login and password, names, prefixes, single
 * or multiple databases, tracking or not...
 *
 * @param string $installType
 * @param string $dbHostForm
 * @param string $dbUsernameForm
 * @param string $dbPassForm
 * @param string $dbNameForm
 * @param int    $dbPortForm
 * @param string $installationProfile
 */
function display_database_settings_form(
    $installType,
    $dbHostForm,
    $dbUsernameForm,
    $dbPassForm,
    $dbNameForm,
    $dbPortForm = 3306,
    $installationProfile = ''
) {
    if ('update' === $installType) {
        $dbHostForm = get_config_param('db_host');
        $dbUsernameForm = get_config_param('db_user');
        $dbPassForm = get_config_param('db_password');
        $dbNameForm = get_config_param('main_database');
        $dbPortForm = get_config_param('db_port');

        echo '<div class="RequirementHeading"><h2>'.display_step_sequence().get_lang('Database settings').'</h2></div>';
        echo '<div class="RequirementContent">';
        echo get_lang('The upgrade script will recover and update the Chamilo database(s). In order to do this, this script will use the databases and settings defined below. Because our software runs on a wide range of systems and because all of them might not have been tested, we strongly recommend you do a full backup of your databases before you proceed with the upgrade!');
        echo '</div>';
    } else {
        echo '<div class="RequirementHeading"><h2>'.display_step_sequence().get_lang('Database settings').'</h2></div>';
        echo '<div class="RequirementContent">';
        echo get_lang('The install script will create (or use) the Chamilo database using the database name given here. Please make sure the user you give has the right to create the database by the name given here. If a database with this name exists, it will be overwritten. Please do not use the root user as the Chamilo database user. This can lead to serious security issues.');
        echo '</div>';
    }

    echo '
        <div class="card">
            <div class="card-body">
            <dl class="row">
                <dt class="col-sm-4">'.get_lang('Database Host').'</dt>';
    if ('update' === $installType) {
        echo '<dd class="col-sm-8">
                <input
                    type="hidden"
                    name="dbHostForm" value="'.htmlentities($dbHostForm).'" />'.$dbHostForm.'
                </dd>';
    } else {
        echo '<dd class="col-sm-8">
                <input
                    type="text"
                    class="form-control"
                    size="25"
                    maxlength="50" name="dbHostForm" value="'.htmlentities($dbHostForm).'" />
                    '.get_lang('ex.').'localhost
            </dd>';
    }

    echo '<dt class="col-sm-4">'.get_lang('Port').'</dt>';
    if ('update' === $installType) {
        echo '<dd class="col-sm-8">
            <input
                type="hidden"
                name="dbPortForm" value="'.htmlentities($dbPortForm).'" />'.$dbPortForm.'
            </dd>';
    } else {
        echo '
        <dd class="col-sm-8">
            <input
            type="text"
            class="form-control"
            size="25"
            maxlength="50" name="dbPortForm" value="'.htmlentities($dbPortForm).'" />
            '.get_lang('ex.').' 3306
        </dd>';
    }
    //database user username
    $example_login = get_lang('ex.').' root';
    displayDatabaseParameter(
        $installType,
        get_lang('Database Login'),
        'dbUsernameForm',
        $dbUsernameForm,
        $example_login
    );

    //database user password
    $example_password = get_lang('ex.').' '.api_generate_password();
    displayDatabaseParameter($installType, get_lang('Database Password'), 'dbPassForm', $dbPassForm, $example_password);
    // Database Name fix replace weird chars
    if (INSTALL_TYPE_UPDATE != $installType) {
        $dbNameForm = str_replace(['-', '*', '$', ' ', '.'], '', $dbNameForm);
    }
    displayDatabaseParameter(
        $installType,
        get_lang('Database name'),
        'dbNameForm',
        $dbNameForm,
        '&nbsp;',
        null,
        'id="optional_param1"'
    );
    echo '</div></div>';
    if (INSTALL_TYPE_UPDATE != $installType) { ?>
        <button type="submit" class="btn btn-primary" name="step3" value="step3">
            <em class="fa fa-sync"> </em>
            <?php echo get_lang('Check database connection'); ?>
        </button>
        <?php
    }

    $databaseExistsText = '';
    $manager = null;
    try {
        if ('update' === $installType) {
            /** @var \Database $manager */
            $manager = connectToDatabase(
                $dbHostForm,
                $dbUsernameForm,
                $dbPassForm,
                $dbNameForm,
                $dbPortForm
            );

            $connection = $manager->getConnection();
            $connection->connect();
            $schemaManager = $connection->getSchemaManager();

            // Test create/alter/drop table
            $table = 'zXxTESTxX_'.mt_rand(0, 1000);
            $sql = "CREATE TABLE $table (id INT AUTO_INCREMENT NOT NULL, name varchar(255), PRIMARY KEY(id))";
            $connection->executeQuery($sql);
            $tableCreationWorks = false;
            $tableDropWorks = false;
            if ($schemaManager->tablesExist($table)) {
                $sql = "ALTER TABLE $table ADD COLUMN name2 varchar(140) ";
                $connection->executeQuery($sql);
                $schemaManager->dropTable($table);
                $tableDropWorks = false === $schemaManager->tablesExist($table);
            }
        } else {
            $manager = connectToDatabase(
                $dbHostForm,
                $dbUsernameForm,
                $dbPassForm,
                null,
                $dbPortForm
            );

            $schemaManager = $manager->getConnection()->getSchemaManager();
            $databases = $schemaManager->listDatabases();
            if (in_array($dbNameForm, $databases)) {
                $databaseExistsText = '<div class="alert alert-warning">'.
                get_lang('A database with the same name <b>already exists</b>. It will be <b>deleted</b>.').
                    '</div>';
            }
        }
    } catch (Exception $e) {
        $databaseExistsText = $e->getMessage();
        $manager = false;
    }

    if ($manager && $manager->getConnection()->isConnected()) {
        echo $databaseExistsText; ?>
        <div id="db_status" class="alert alert-success">
            Database host: <strong><?php echo $manager->getConnection()->getHost(); ?></strong><br/>
            Database port: <strong><?php echo $manager->getConnection()->getPort(); ?></strong><br/>
            Database driver: <strong><?php echo $manager->getConnection()->getDriver()->getName(); ?></strong><br/>
            <?php
                if ('update' === $installType) {
                    echo get_lang('CreateTableWorks').' <strong>Ok</strong>';
                    echo '<br/ >';
                    echo get_lang('AlterTableWorks').' <strong>Ok</strong>';
                    echo '<br/ >';
                    echo get_lang('DropColumnWorks').' <strong>Ok</strong>';
                } ?>
        </div>
    <?php
    } else { ?>
        <div id="db_status" class="alert alert-danger">
            <p>
                <?php echo get_lang('The database connection has failed. This is generally due to the wrong user, the wrong password or the wrong database prefix being set above. Please review these settings and try again.'); ?>
            </p>
            <code><?php echo $databaseExistsText; ?></code>
        </div>
    <?php } ?>

   <div class="btn-group" role="group">
       <button type="submit" name="step2"
               class="btn btn-secondary" value="&lt; <?php echo get_lang('Previous'); ?>" >
           <em class="fa fa-backward"> </em> <?php echo get_lang('Previous'); ?>
       </button>
       <input type="hidden" name="is_executable" id="is_executable" value="-" />
       <?php if ($manager) {
        ?>
           <button type="submit" class="btn btn-success" name="step4" value="<?php echo get_lang('Next'); ?> &gt;" >
               <em class="fa fa-forward"> </em> <?php echo get_lang('Next'); ?>
           </button>
       <?php
    } else {
        ?>
           <button
                   disabled="disabled"
                   type="submit" class="btn btn-success disabled" name="step4" value="<?php echo get_lang('Next'); ?> &gt;" >
               <em class="fa fa-forward"> </em> <?php echo get_lang('Next'); ?>
           </button>
       <?php
    } ?>
   </div>
    <?php
}

/**
 * Displays a parameter in a table row.
 * Used by the display_configuration_settings_form function.
 *
 * @param string $installType
 * @param string $parameterName
 * @param string $formFieldName
 * @param string $parameterValue
 * @param string $displayWhenUpdate
 *
 * @return string
 */
function display_configuration_parameter(
    $installType,
    $parameterName,
    $formFieldName,
    $parameterValue,
    $displayWhenUpdate = 'true'
) {
    $html = '<div class="form-group row">';
    $html .= '<label class="col-sm-6 control-label">'.$parameterName.'</label>';
    if (INSTALL_TYPE_UPDATE == $installType && $displayWhenUpdate) {
        $html .= '<input
            type="hidden"
            name="'.$formFieldName.'"
            value="'.api_htmlentities($parameterValue, ENT_QUOTES).'" />'.$parameterValue;
    } else {
        $html .= '<div class="col-sm-6">
                    <input
                        class="form-control"
                        type="text"
                        size="'.FORM_FIELD_DISPLAY_LENGTH.'"
                        maxlength="'.MAX_FORM_FIELD_LENGTH.'"
                        name="'.$formFieldName.'"
                        value="'.api_htmlentities($parameterValue, ENT_QUOTES).'" />
                    '.'</div>';
    }
    $html .= '</div>';

    return $html;
}

/**
 * Displays step 4 of the installation - configuration settings about Chamilo itself.
 *
 * @param string $installType
 * @param string $urlForm
 * @param string $languageForm
 * @param string $emailForm
 * @param string $adminFirstName
 * @param string $adminLastName
 * @param string $adminPhoneForm
 * @param string $campusForm
 * @param string $institutionForm
 * @param string $institutionUrlForm
 * @param string $encryptPassForm
 * @param bool   $allowSelfReg
 * @param bool   $allowSelfRegProf
 * @param string $loginForm
 * @param string $passForm
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
    if ('update' !== $installType && empty($languageForm)) {
        $languageForm = $_SESSION['install_language'];
    }
    echo '<div class="RequirementHeading">';
    echo '<h2>'.display_step_sequence().get_lang('Configuration settings').'</h2>';
    echo '</div>';

    // Parameter 1: administrator's login
    if ('update' === $installType) {
        $rootSys = get_config_param('root_web');
        $html = display_configuration_parameter(
            $installType,
            get_lang('Chamilo URL'),
            'loginForm',
            $rootSys,
            true
        );
        $rootSys = get_config_param('root_sys');
        $html .= display_configuration_parameter(
            $installType,
            get_lang('Path'),
            'loginForm',
            $rootSys,
            true
        );
        $systemVersion = get_config_param('system_version');
        $html .= display_configuration_parameter(
            $installType,
            get_lang('Version'),
            'loginForm',
            $systemVersion,
            true
        );
        echo Display::panel($html, get_lang('System'));
    }

    $html = display_configuration_parameter(
        $installType,
        get_lang('Administrator login'),
        'loginForm',
        $loginForm,
        'update' == $installType
    );

    // Parameter 2: administrator's password
    if ('update' !== $installType) {
        $html .= display_configuration_parameter(
            $installType,
            get_lang('Administrator password (<font color="red">you may want to change this</font>)'),
            'passForm',
            $passForm,
            false
        );
    }

    // Parameters 3 and 4: administrator's names
    $html .= display_configuration_parameter(
        $installType,
        get_lang('Administrator first name'),
        'adminFirstName',
        $adminFirstName
    );
    $html .= display_configuration_parameter(
        $installType,
        get_lang('Administrator last name'),
        'adminLastName',
        $adminLastName
    );

    // Parameter 3: administrator's email
    $html .= display_configuration_parameter($installType, get_lang('Admin-mail'), 'emailForm', $emailForm);

    // Parameter 6: administrator's telephone
    $html .= display_configuration_parameter(
        $installType,
        get_lang('Administrator telephone'),
        'adminPhoneForm',
        $adminPhoneForm
    );
    echo Display::panel($html, get_lang('Administrator'));

    // First parameter: language.
    $html = '<div class="form-group row">';
    $html .= '<label class="col-sm-6 control-label">'.get_lang('Language').'</label>';
    if ('update' === $installType) {
        $html .= '<input
            type="hidden"
            name="languageForm" value="'.api_htmlentities($languageForm, ENT_QUOTES).'" />'.
            $languageForm;
    } else {
        $html .= '<div class="col-sm-6">';
        $html .= display_language_selection_box('languageForm', $languageForm);
        $html .= '</div>';
    }
    $html .= '</div>';

    // Second parameter: Chamilo URL
    if ('install' === $installType) {
        $html .= '<div class="form-group row">';
        $html .= '<label class="col-sm-6 control-label">'.get_lang('Chamilo URL').'</label>';
        $html .= '<div class="col-sm-6">';
        $html .= '<input
            class="form-control"
            type="text" size="40"
            required
            maxlength="100" name="urlForm" value="'.api_htmlentities($urlForm, ENT_QUOTES).'" />';
        $html .= '</div>';

        $html .= '</div>';
    }

    // Parameter 9: campus name
    $html .= display_configuration_parameter(
        $installType,
        get_lang('Your portal name'),
        'campusForm',
        $campusForm
    );

    // Parameter 10: institute (short) name
    $html .= display_configuration_parameter(
        $installType,
        get_lang('Your company short name'),
        'institutionForm',
        $institutionForm
    );

    // Parameter 11: institute (short) name
    $html .= display_configuration_parameter(
        $installType,
        get_lang('URL of this company'),
        'institutionUrlForm',
        $institutionUrlForm
    );

    $html .= '<div class="form-group row">
            <label class="col-sm-6 control-label">'.get_lang('Encryption method').'</label>
        <div class="col-sm-6">';
    if ('update' === $installType) {
        $html .= '<input type="hidden" name="encryptPassForm" value="'.$encryptPassForm.'" />'.$encryptPassForm;
    } else {
        $html .= '<div class="checkbox">
                    <label>
                        <input
                            type="radio"
                            name="encryptPassForm"
                            value="bcrypt"
                            id="encryptPass1" '.('bcrypt' === $encryptPassForm ? 'checked="checked" ' : '').'/> bcrypt
                    </label>';

        $html .= '<label>
                        <input
                            type="radio"
                            name="encryptPassForm"
                            value="sha1"
                            id="encryptPass1" '.('sha1' === $encryptPassForm ? 'checked="checked" ' : '').'/> sha1
                    </label>';

        $html .= '<label>
                        <input type="radio"
                            name="encryptPassForm"
                            value="md5"
                            id="encryptPass0" '.('md5' === $encryptPassForm ? 'checked="checked" ' : '').'/> md5
                    </label>';

        $html .= '<label>
                        <input
                            type="radio"
                            name="encryptPassForm"
                            value="none"
                            id="encryptPass2" '.
                            ('none' === $encryptPassForm ? 'checked="checked" ' : '').'/>'.get_lang('none').'
                    </label>';
        $html .= '</div>';
    }
    $html .= '</div></div>';

    $html .= '<div class="form-group row">
            <label class="col-sm-6 control-label">'.get_lang('Allow self-registration').'</label>
            <div class="col-sm-6">';
    if ('update' === $installType) {
        if ('true' === $allowSelfReg) {
            $label = get_lang('Yes');
        } elseif ('false' === $allowSelfReg) {
            $label = get_lang('No');
        } else {
            $label = get_lang('After approval');
        }
        $html .= '<input type="hidden" name="allowSelfReg" value="'.$allowSelfReg.'" />'.$label;
    } else {
        $html .= '<div class="control-group">';
        $html .= '<label class="checkbox-inline">
                    <input type="radio"
                        name="allowSelfReg" value="true"
                        id="allowSelfReg1" '.('true' == $allowSelfReg ? 'checked="checked" ' : '').' /> '.get_lang('Yes').'
                  </label>';
        $html .= '<label class="checkbox-inline">
                    <input
                        type="radio"
                        name="allowSelfReg"
                        value="false"
                        id="allowSelfReg0" '.('false' == $allowSelfReg ? '' : 'checked="checked" ').' /> '.get_lang('No').'
                </label>';
        $html .= '<label class="checkbox-inline">
                    <input
                        type="radio"
                        name="allowSelfReg"
                        value="approval"
                        id="allowSelfReg2" '.('approval' == $allowSelfReg ? '' : 'checked="checked" ').' /> '.get_lang('After approval').'
                </label>';
        $html .= '</div>';
    }
    $html .= '</div>';
    $html .= '</div>';

    $html .= '<div class="form-group row">';
    $html .= '<label class="col-sm-6 control-label">'.get_lang('Allow self-registrationProf').'</label>
                <div class="col-sm-6">';
    if ('update' === $installType) {
        if ('true' === $allowSelfRegProf) {
            $label = get_lang('Yes');
        } else {
            $label = get_lang('No');
        }
        $html .= '<input type="hidden" name="allowSelfRegProf" value="'.$allowSelfRegProf.'" />'.$label;
    } else {
        $html .= '<div class="control-group">
                <label class="checkbox-inline">
                    <input
                        type="radio"
                        name="allowSelfRegProf" value="1"
                        id="allowSelfRegProf1" '.($allowSelfRegProf ? 'checked="checked" ' : '').'/>
                '.get_lang('Yes').'
                </label>';
        $html .= '<label class="checkbox-inline">
                    <input
                        type="radio" name="allowSelfRegProf" value="0"
                        id="allowSelfRegProf0" '.($allowSelfRegProf ? '' : 'checked="checked" ').' />
                   '.get_lang('No').'
                </label>';
        $html .= '</div>';
    }
    $html .= '</div>
    </div>';
    echo Display::panel($html, get_lang('Portal')); ?>
    <div class='btn-group'>
        <button
            type="submit"
            class="btn btn-secondary "
            name="step3" value="&lt; <?php echo get_lang('Previous'); ?>" >
                <em class="fa fa-backward"> </em> <?php echo get_lang('Previous'); ?>
        </button>
        <input type="hidden" name="is_executable" id="is_executable" value="-" />
        <button class="btn btn-success" type="submit" name="step5">
            <em class="fa fa-forward"> </em> <?php echo get_lang('Next'); ?>
        </button>
    </div>
    <?php
}

/**
 * After installation is completed (step 6), this message is displayed.
 */
function display_after_install_message()
{
    $container = Container::$container;
    $trans = $container->get('translator');
    $html = '<div class="RequirementContent">'.
    $trans->trans(
        'When you enter your portal for the first time, the best way to understand it is to create a course with the \'Create course\' link in the menu and play around a little.').'</div>';
    $html .= '<div class="alert alert-warning">';
    $html .= '<strong>'.$trans->trans('Security advice').'</strong>';
    $html .= ': ';
    $html .= sprintf($trans->trans(
        'To protect your site, make the whole %s directory read-only (chmod -R 0555 on Linux) and delete the %s directory.'), 'var/config/', 'main/install/');
    $html .= '</div></form>
    <br />
    <a class="btn btn-success btn-block" href="../../">
        '.$trans->trans('Go to your newly created portal.').'
    </a>';

    return $html;
}

/**
 * This function return countries list from array (hardcoded).
 *
 * @param bool $combo (Optional) True for returning countries list with select html
 *
 * @return array|string countries list
 */
function get_countries_list_from_array($combo = false)
{
    $a_countries = [
        'Afghanistan', 'Albania', 'Algeria', 'Andorra', 'Angola', 'Antigua and Barbuda', 'Argentina', 'Armenia', 'Australia', 'Austria', 'Azerbaijan',
        'Bahamas', 'Bahrain', 'Bangladesh', 'Barbados', 'Belarus', 'Belgium', 'Belize', 'Benin', 'Bhutan', 'Bolivia', 'Bosnia and Herzegovina', 'Botswana', 'Brazil', 'Brunei', 'Bulgaria', 'Burkina Faso', 'Burundi',
        'Cambodia', 'Cameroon', 'Canada', 'Cape Verde', 'Central African Republic', 'Chad', 'Chile', 'China', 'Colombi', 'Comoros', 'Congo (Brazzaville)', 'Congo', 'Costa Rica', "Cote d'Ivoire", 'Croatia', 'Cuba', 'Cyprus', 'Czech Republic',
        'Denmark', 'Djibouti', 'Dominica', 'Dominican Republic',
        'East Timor (Timor Timur)', 'Ecuador', 'Egypt', 'El Salvador', 'Equatorial Guinea', 'Eritrea', 'Estonia', 'Ethiopia',
        'Fiji', 'Finland', 'France',
        'Gabon', 'Gambia, The', 'Georgia', 'Germany', 'Ghana', 'Greece', 'Grenada', 'Guatemala', 'Guinea', 'Guinea-Bissau', 'Guyana',
        'Haiti', 'Honduras', 'Hungary',
        'Iceland', 'India', 'Indonesia', 'Iran', 'Iraq', 'Ireland', 'Israel', 'Italy',
        'Jamaica', 'Japan', 'Jordan',
        'Kazakhstan', 'Kenya', 'Kiribati', 'Korea, North', 'Korea, South', 'Kuwait', 'Kyrgyzstan',
        'Laos', 'Latvia', 'Lebanon', 'Lesotho', 'Liberia', 'Libya', 'Liechtenstein', 'Lithuania', 'Luxembourg',
        'Macedonia', 'Madagascar', 'Malawi', 'Malaysia', 'Maldives', 'Mali', 'Malta', 'Marshall Islands', 'Mauritania', 'Mauritius', 'Mexico', 'Micronesia', 'Moldova', 'Monaco', 'Mongolia', 'Morocco', 'Mozambique', 'Myanmar',
        'Namibia', 'Nauru', 'Nepa', 'Netherlands', 'New Zealand', 'Nicaragua', 'Niger', 'Nigeria', 'Norway',
        'Oman',
        'Pakistan', 'Palau', 'Panama', 'Papua New Guinea', 'Paraguay', 'Peru', 'Philippines', 'Poland', 'Portugal',
        'Qatar',
        'Romania', 'Russia', 'Rwanda',
        'Saint Kitts and Nevis', 'Saint Lucia', 'Saint Vincent', 'Samoa', 'San Marino', 'Sao Tome and Principe', 'Saudi Arabia', 'Senegal', 'Serbia and Montenegro', 'Seychelles', 'Sierra Leone', 'Singapore', 'Slovakia', 'Slovenia', 'Solomon Islands', 'Somalia', 'South Africa', 'Spain', 'Sri Lanka', 'Sudan', 'Suriname', 'Swaziland', 'Sweden', 'Switzerland', 'Syria',
        'Taiwan', 'Tajikistan', 'Tanzania', 'Thailand', 'Togo', 'Tonga', 'Trinidad and Tobago', 'Tunisia', 'Turkey', 'Turkmenistan', 'Tuvalu',
        'Uganda', 'Ukraine', 'United Arab Emirates', 'United Kingdom', 'United States', 'Uruguay', 'Uzbekistan',
        'Vanuatu', 'Vatican City', 'Venezuela', 'Vietnam',
        'Yemen',
        'Zambia', 'Zimbabwe',
    ];
    if ($combo) {
        $country_select = '<select class="form-control show-tick" id="country" name="country">';
        $country_select .= '<option value="">--- '.get_lang('Select one').' ---</option>';
        foreach ($a_countries as $country) {
            $country_select .= '<option value="'.$country.'">'.$country.'</option>';
        }
        $country_select .= '</select>';

        return $country_select;
    }

    return $a_countries;
}

/**
 * Lock settings that can't be changed in other portals.
 */
function lockSettings()
{
    $settings = api_get_locked_settings();
    $table = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
    foreach ($settings as $setting) {
        $sql = "UPDATE $table SET access_url_locked = 1 WHERE variable  = '$setting'";
        Database::query($sql);
    }
}

/**
 * Update dir values.
 */
function updateDirAndFilesPermissions()
{
    $table = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
    $permissions_for_new_directories = isset($_SESSION['permissions_for_new_directories']) ? $_SESSION['permissions_for_new_directories'] : 0770;
    $permissions_for_new_files = isset($_SESSION['permissions_for_new_files']) ? $_SESSION['permissions_for_new_files'] : 0660;
    // use decoct() to store as string
    Database::update(
        $table,
        ['selected_value' => '0'.decoct($permissions_for_new_directories)],
        ['variable = ?' => 'permissions_for_new_directories']
    );

    Database::update(
        $table,
        ['selected_value' => '0'.decoct($permissions_for_new_files)],
        ['variable = ?' => 'permissions_for_new_files']
    );

    if (isset($_SESSION['permissions_for_new_directories'])) {
        unset($_SESSION['permissions_for_new_directories']);
    }

    if (isset($_SESSION['permissions_for_new_files'])) {
        unset($_SESSION['permissions_for_new_files']);
    }
}

/**
 * @param $current_value
 * @param $wanted_value
 *
 * @return string
 */
function compare_setting_values($current_value, $wanted_value)
{
    $current_value_string = $current_value;
    $current_value = (float) $current_value;
    $wanted_value = (float) $wanted_value;

    if ($current_value >= $wanted_value) {
        return Display::label($current_value_string, 'success');
    }

    return Display::label($current_value_string, 'important');
}

/**
 * Save settings values.
 *
 * @param string $organizationName
 * @param string $organizationUrl
 * @param string $siteName
 * @param string $adminEmail
 * @param string $adminLastName
 * @param string $adminFirstName
 * @param string $language
 * @param string $allowRegistration
 * @param string $allowTeacherSelfRegistration
 * @param string $installationProfile          The name of an installation profile file in main/install/profiles/
 */
function installSettings(
    $organizationName,
    $organizationUrl,
    $siteName,
    $adminEmail,
    $adminLastName,
    $adminFirstName,
    $language,
    $allowRegistration,
    $allowTeacherSelfRegistration,
    $installationProfile = ''
) {
    error_log('installSettings');
    $allowTeacherSelfRegistration = $allowTeacherSelfRegistration ? 'true' : 'false';

    $settings = [
        'institution' => $organizationName,
        'institution_url' => $organizationUrl,
        'site_name' => $siteName,
        'administrator_email' => $adminEmail,
        'administrator_surname' => $adminLastName,
        'administrator_name' => $adminFirstName,
        'platform_language' => $language,
        'allow_registration' => $allowRegistration,
        'allow_registration_as_teacher' => $allowTeacherSelfRegistration,
    ];

    foreach ($settings as $variable => $value) {
        $sql = "UPDATE settings_current
                SET selected_value = '$value'
                WHERE variable = '$variable'";
        Database::query($sql);
    }
    installProfileSettings($installationProfile);
}

/**
 * Executes DB changes based in the classes defined in
 * /src/CoreBundle/Migrations/Schema/V200/*.
 *
 * @return bool
 */
function migrate(EntityManager $manager)
{
    $debug = true;
    $connection = $manager->getConnection();
    $to = null; // if $to == null then schema will be migrated to latest version

    // Loading migration configuration.
    $config = new PhpFile('./migrations.php');
    $dependency = DependencyFactory::fromConnection($config, new ExistingConnection($connection));

    // Check if old "version" table exists from 1.11.x, use new version.
    $schema = $manager->getConnection()->getSchemaManager();
    $dropOldVersionTable = false;
    if ($schema->tablesExist('version')) {
        $columns = $schema->listTableColumns('version');
        if (in_array('id', array_keys($columns), true)) {
            $dropOldVersionTable = true;
        }
    }

    if ($dropOldVersionTable) {
        error_log('Drop version table');
        $schema->dropTable('version');
    }

    // Creates "version" table.
    $dependency->getMetadataStorage()->ensureInitialized();

    // Loading migrations.
    $migratorConfigurationFactory = $dependency->getConsoleInputMigratorConfigurationFactory();
    $result = '';
    $input = new Symfony\Component\Console\Input\StringInput($result);
    $migratorConfiguration = $migratorConfigurationFactory->getMigratorConfiguration($input);
    $migrator = $dependency->getMigrator();
    $planCalculator = $dependency->getMigrationPlanCalculator();
    $migrations = $planCalculator->getMigrations();
    $lastVersion = $migrations->getLast();

    $plan = $dependency->getMigrationPlanCalculator()->getPlanUntilVersion($lastVersion->getVersion());

    foreach ($plan->getItems() as $item) {
        error_log("Version to be executed: ".$item->getVersion());
        $item->getMigration()->setEntityManager($manager);
        $item->getMigration()->setContainer(Container::$container);
    }

    // Execute migration!!
    /** @var $migratedVersions */
    $versions = $migrator->migrate($plan, $migratorConfiguration);

    if ($debug) {
        /** @var Query[] $queries */
        $versionCounter = 1;
        foreach ($versions as $version => $queries) {
            $total = count($queries);
            echo '----------------------------------------------<br />';
            $message = "VERSION: $version";
            echo "$message<br/>";
            error_log('-------------------------------------');
            error_log($message);
            $counter = 1;
            foreach ($queries as $query) {
                $sql = $query->getStatement();
                echo "<code>$sql</code><br>";
                error_log("$counter/$total : $sql");
                $counter++;
            }
            $versionCounter++;
        }
        echo '<br/>DONE!<br />';
        error_log('DONE!');
    }

    return true;
}

/**
 * @param string $distFile
 * @param string $envFile
 * @param array  $params
 */
function updateEnvFile($distFile, $envFile, $params)
{
    $requirements = [
        'DATABASE_HOST',
        'DATABASE_PORT',
        'DATABASE_NAME',
        'DATABASE_USER',
        'DATABASE_PASSWORD',
        'APP_INSTALLED',
        'APP_ENCRYPT_METHOD',
    ];

    foreach ($requirements as $requirement) {
        if (!isset($params['{{'.$requirement.'}}'])) {
            throw new \Exception("The parameter $requirement is needed in order to edit the .env.local file");
        }
    }

    $contents = file_get_contents($distFile);
    $contents = str_replace(array_keys($params), array_values($params), $contents);
    file_put_contents($envFile, $contents);
    error_log("File env saved here: $envFile");
}

/**
 * @param EntityManager $manager
 */
function installGroups($manager)
{
    error_log('installGroups');
    // Creating fos_group (groups and roles)
    $groups = [
        [
            'code' => 'ADMIN',
            'title' => 'Administrators',
            'roles' => ['ROLE_ADMIN'],
        ],
        [
            'code' => 'STUDENT',
            'title' => 'Students',
            'roles' => ['ROLE_STUDENT'],
        ],
        [
            'code' => 'TEACHER',
            'title' => 'Teachers',
            'roles' => ['ROLE_TEACHER'],
        ],
        [
            'code' => 'RRHH',
            'title' => 'Human resources manager',
            'roles' => ['ROLE_RRHH'],
        ],
        [
            'code' => 'SESSION_MANAGER',
            'title' => 'Session',
            'roles' => ['ROLE_SESSION_MANAGER'],
        ],
        [
            'code' => 'QUESTION_MANAGER',
            'title' => 'Question manager',
            'roles' => ['ROLE_QUESTION_MANAGER'],
        ],
        [
            'code' => 'STUDENT_BOSS',
            'title' => 'Student boss',
            'roles' => ['ROLE_STUDENT_BOSS'],
        ],
        [
            'code' => 'INVITEE',
            'title' => 'Invitee',
            'roles' => ['ROLE_INVITEE'],
        ],
    ];
    $repo = $manager->getRepository('ChamiloCoreBundle:Group');
    foreach ($groups as $groupData) {
        $criteria = ['code' => $groupData['code']];
        $groupExists = $repo->findOneBy($criteria);
        if (!$groupExists) {
            $group = new Group($groupData['title']);
            $group
                ->setCode($groupData['code']);

            foreach ($groupData['roles'] as $role) {
                $group->addRole($role);
            }
            $manager->persist($group);
        }
    }
    $manager->flush();
}

function installTools($container, $manager, $upgrade = false)
{
    error_log('installTools');
    // Install course tools (table "tool")
    /** @var ToolChain $toolChain */
    $toolChain = $container->get(ToolChain::class);
    $toolChain->createTools($manager);
}

/**
 * @param SymfonyContainer $container
 * @param bool             $upgrade
 */
function installSchemas($container, $upgrade = false)
{
    error_log('installSchemas');
    $settingsManager = $container->get('chamilo.settings.manager');

    $urlRepo = $container->get(AccessUrlRepository::class);
    $accessUrl = $urlRepo->find(1);
    if (null === $accessUrl) {
        $em = Database::getManager();

        // Creating AccessUrl
        $accessUrl = new AccessUrl();
        $accessUrl
            ->setUrl('http://localhost/')
            ->setDescription('')
            ->setActive(1)
            ->setCreatedBy(1)
        ;
        $em->persist($accessUrl);
        $em->flush();

        error_log('AccessUrl created');
    }

    if ($upgrade) {
        error_log('Upgrade settings');
        $settingsManager->updateSchemas($accessUrl);
    } else {
        error_log('Install settings');
        // Installing schemas (filling settings_current table)
        $settingsManager->installSchemas($accessUrl);
    }
}

/**
 * @param SymfonyContainer $container
 */
function upgradeWithContainer($container)
{
    Container::setContainer($container);
    Container::setLegacyServices($container, false);
    error_log('setLegacyServices');
    $manager = Database::getManager();
    installGroups($manager);
    // @todo check if adminId = 1
    installTools($container, $manager, true);
    installSchemas($container, true);
}

/**
 * After the schema was created (table creation), the function adds
 * admin/platform information.
 *
 * @param \Psr\Container\ContainerInterface $container
 * @param string                            $sysPath
 * @param string                            $encryptPassForm
 * @param string                            $passForm
 * @param string                            $adminLastName
 * @param string                            $adminFirstName
 * @param string                            $loginForm
 * @param string                            $emailForm
 * @param string                            $adminPhoneForm
 * @param string                            $languageForm
 * @param string                            $institutionForm
 * @param string                            $institutionUrlForm
 * @param string                            $siteName
 * @param string                            $allowSelfReg
 * @param string                            $allowSelfRegProf
 * @param string                            $installationProfile Installation profile, if any was provided
 */
function finishInstallationWithContainer(
    $container,
    $sysPath,
    $encryptPassForm,
    $passForm,
    $adminLastName,
    $adminFirstName,
    $loginForm,
    $emailForm,
    $adminPhoneForm,
    $languageForm,
    $institutionForm,
    $institutionUrlForm,
    $siteName,
    $allowSelfReg,
    $allowSelfRegProf,
    $installationProfile = ''
) {
    error_log('finishInstallationWithContainer');
    $sysPath = !empty($sysPath) ? $sysPath : api_get_path(SYMFONY_SYS_PATH);
    Container::setContainer($container);
    Container::setLegacyServices($container, false);
    error_log('setLegacyServices');

    $manager = Database::getManager();
    $trans = $container->get('translator');

    // Add tickets defaults
    $ticketProject = new TicketProject();
    $ticketProject
        ->setName('Ticket System')
        ->setInsertUserId(1);

    $manager->persist($ticketProject);

    $categories = [
        $trans->trans('Enrollment') => $trans->trans('Tickets about enrollment'),
        $trans->trans('General information') => $trans->trans('Tickets about general information'),
        $trans->trans('Requests and paperwork') => $trans->trans('Tickets about requests and paperwork'),
        $trans->trans('Academic Incidents') => $trans->trans('Tickets about academic incidents, like exams, practices, tasks, etc.'),
        $trans->trans('Virtual campus') => $trans->trans('Tickets about virtual campus'),
        $trans->trans('Online evaluation') => $trans->trans('Tickets about online evaluation'),
    ];

    $i = 1;
    foreach ($categories as $category => $description) {
        // Online evaluation requires a course
        $ticketCategory = new TicketCategory();
        $ticketCategory
            ->setName($category)
            ->setDescription($description)
            ->setProject($ticketProject)
            ->setInsertUserId(1);

        $isRequired = 6 == $i;
        $ticketCategory->setCourseRequired($isRequired);

        $manager->persist($ticketCategory);
        $i++;
    }

    // Default Priorities
    $defaultPriorities = [
        TicketManager::PRIORITY_NORMAL => $trans->trans('Normal'),
        TicketManager::PRIORITY_HIGH => $trans->trans('High'),
        TicketManager::PRIORITY_LOW => $trans->trans('Low'),
    ];

    $i = 1;
    foreach ($defaultPriorities as $code => $priority) {
        $ticketPriority = new TicketPriority();
        $ticketPriority
            ->setName($priority)
            ->setCode($code)
            ->setInsertUserId(1);

        $manager->persist($ticketPriority);
        $i++;
    }
    error_log('Save ticket data');
    $manager->flush();

    $table = Database::get_main_table(TABLE_TICKET_STATUS);

    // Default status
    $defaultStatus = [
        TicketManager::STATUS_NEW => $trans->trans('New'),
        TicketManager::STATUS_PENDING => $trans->trans('Pending'),
        TicketManager::STATUS_UNCONFIRMED => $trans->trans('Unconfirmed'),
        TicketManager::STATUS_CLOSE => $trans->trans('Close'),
        TicketManager::STATUS_FORWARDED => $trans->trans('Forwarded'),
    ];

    $i = 1;
    foreach ($defaultStatus as $code => $status) {
        $attributes = [
            'id' => $i,
            'code' => $code,
            'name' => $status,
        ];
        Database::insert($table, $attributes);
        $i++;
    }

    installGroups($manager);

    error_log('Inserting data.sql');
    // Inserting default data
    $data = file_get_contents($sysPath.'public/main/install/data.sql');
    $result = $manager->getConnection()->prepare($data);
    $executeResult = $result->executeQuery();

    if ($executeResult) {
        error_log('data.sql Ok');
    }
    $result->free();

    UserManager::setPasswordEncryption($encryptPassForm);
    $timezone = api_get_timezone();

    error_log('user creation - admin');

    $now = new DateTime();
    // Creating admin user.
    $admin = new User();
    $admin
        ->setSkipResourceNode(true)
        ->setLastname($adminFirstName)
        ->setFirstname($adminLastName)
        ->setUsername($loginForm)
        ->setStatus(1)
        ->setPlainPassword($passForm)
        ->setEmail($emailForm)
        ->setOfficialCode('ADMIN')
        ->setCreatorId(1)
        ->setAuthSource(PLATFORM_AUTH_SOURCE)
        ->setPhone($adminPhoneForm)
        ->setLocale($languageForm)
        ->setRegistrationDate($now)
        ->setActive(1)
        ->setEnabled(1)
        ->setTimezone($timezone)
    ;

    $repo = Container::getUserRepository();
    $repo->updateUser($admin);
    UserManager::addUserAsAdmin($admin);

    $adminId = $admin->getId();

    error_log('user creation - anon');

    $anon = new User();
    $anon
        ->setSkipResourceNode(true)
        ->setLastname('Joe')
        ->setFirstname('Anonymous')
        ->setUsername('anon')
        ->setStatus(ANONYMOUS)
        ->setPlainPassword('anon')
        ->setEmail('anonymous@localhost')
        ->setOfficialCode('anonymous')
        ->setCreatorId(1)
        ->setAuthSource(PLATFORM_AUTH_SOURCE)
        ->setLocale($languageForm)
        ->setRegistrationDate($now)
        ->setActive(1)
        ->setEnabled(1)
        ->setTimezone($timezone)
    ;

    $repo->updateUser($anon);

    $anonId = $anon->getId();

    $userRepo = $container->get(UserRepository::class);
    $urlRepo = $container->get(AccessUrlRepository::class);

    installTools($container, $manager, false);

    /** @var User $admin */
    $admin = $userRepo->find($adminId);
    $admin->addRole('ROLE_GLOBAL_ADMIN');
    $manager->persist($admin);

    // Login as admin
    $token = new UsernamePasswordToken(
        $admin,
        $admin->getPassword(),
        'public',
        $admin->getRoles()
    );
    $container->get('security.token_storage')->setToken($token);

    $userRepo->addUserToResourceNode($adminId, $adminId);
    $userRepo->addUserToResourceNode($anonId, $adminId);

    $manager->persist($anon);

    $manager->flush();

    installSchemas($container);
    $accessUrl = $urlRepo->find(1);

    UrlManager::add_user_to_url($adminId, $adminId);
    UrlManager::add_user_to_url($anonId, $adminId);

    $branch = new BranchSync();
    $branch->setBranchName('localhost');
    $branch->setUrl($accessUrl);
    $manager->persist($branch);
    $manager->flush();

    // Set default language
    Database::update(
        Database::get_main_table(TABLE_MAIN_LANGUAGE),
        ['available' => 1],
        ['english_name = ?' => $languageForm]
    );

    // Install settings
    installSettings(
        $institutionForm,
        $institutionUrlForm,
        $siteName,
        $emailForm,
        $adminLastName,
        $adminFirstName,
        $languageForm,
        $allowSelfReg,
        $allowSelfRegProf,
        $installationProfile
    );

    lockSettings();
    updateDirAndFilesPermissions();
}

/**
 * Update settings based on installation profile defined in a JSON file.
 *
 * @param string $installationProfile The name of the JSON file in main/install/profiles/ folder
 *
 * @return bool false on failure (no bad consequences anyway, just ignoring profile)
 */
function installProfileSettings($installationProfile = '')
{
    error_log('installProfileSettings');
    if (empty($installationProfile)) {
        return false;
    }
    $jsonPath = api_get_path(SYS_PATH).'main/install/profiles/'.$installationProfile.'.json';
    // Make sure the path to the profile is not hacked
    if (!Security::check_abs_path($jsonPath, api_get_path(SYS_PATH).'main/install/profiles/')) {
        return false;
    }
    if (!is_file($jsonPath)) {
        return false;
    }
    if (!is_readable($jsonPath)) {
        return false;
    }
    if (!function_exists('json_decode')) {
        // The php-json extension is not available. Ignore profile.
        return false;
    }
    $json = file_get_contents($jsonPath);
    $params = json_decode($json);
    if (false === $params or null === $params) {
        return false;
    }
    $settings = $params->params;
    if (!empty($params->parent)) {
        installProfileSettings($params->parent);
    }

    $tblSettings = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);

    foreach ($settings as $id => $param) {
        $conditions = ['variable = ? ' => $param->variable];

        if (!empty($param->subkey)) {
            $conditions['AND subkey = ? '] = $param->subkey;
        }

        Database::update(
            $tblSettings,
            ['selected_value' => $param->selected_value],
            $conditions
        );
    }

    return true;
}

/**
 * Quick function to remove a directory with its subdirectories.
 *
 * @param $dir
 */
function rrmdir($dir)
{
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ('.' != $object && '..' != $object) {
                if ('dir' == filetype($dir.'/'.$object)) {
                    @rrmdir($dir.'/'.$object);
                } else {
                    @unlink($dir.'/'.$object);
                }
            }
        }
        reset($objects);
        rmdir($dir);
    }
}

/**
 * Control the different steps of the migration through a big switch.
 *
 * @param string        $fromVersion
 * @param EntityManager $manager
 * @param bool          $processFiles
 *
 * @return bool Always returns true except if the process is broken
 */
function migrateSwitch($fromVersion, $manager, $processFiles = true)
{
    error_log('-----------------------------------------');
    error_log('Starting migration process from '.$fromVersion.' ('.date('Y-m-d H:i:s').')');
    //echo '<a class="btn btn-secondary" href="javascript:void(0)" id="details_button">'.get_lang('Details').'</a><br />';
    //echo '<div id="details" style="display:none">';
    $connection = $manager->getConnection();

    switch ($fromVersion) {
        case '1.11.0':
        case '1.11.1':
        case '1.11.2':
        case '1.11.4':
        case '1.11.6':
        case '1.11.8':
        case '1.11.10':
        case '1.11.12':
        case '1.11.14':
            $start = time();
            // Migrate using the migration files located in:
            // /srv/http/chamilo2/src/CoreBundle/Migrations/Schema/V200
            $result = migrate($manager);
            error_log('-----------------------------------------');

            if ($result) {
                error_log('Migrations files were executed ('.date('Y-m-d H:i:s').')');
                $sql = "UPDATE settings_current SET selected_value = '2.0.0'
                        WHERE variable = 'chamilo_database_version'";
                $connection->executeQuery($sql);
                if ($processFiles) {
                    error_log('Update config files');
                    include __DIR__.'/update-files-1.11.0-2.0.0.inc.php';
                    // Only updates the configuration.inc.php with the new version
                    //include __DIR__.'/update-configuration.inc.php';
                }
                $finish = time();
                $total = round(($finish - $start) / 60);
                error_log('Database migration finished:  ('.date('Y-m-d H:i:s').') took '.$total.' minutes');
            } else {
                error_log('There was an error during running migrations. Check error.log');
                exit;
            }
            break;
        default:
            break;
    }

    //echo '</div>';

    return true;
}

/**
 * @return string
 */
function generateRandomToken()
{
    return hash('sha1', uniqid(mt_rand(), true));
}

/**
 * This function checks if the given file can be created or overwritten.
 *
 * @param string $file Full path to a file
 *
 * @return string An HTML coloured label showing success or failure
 */
function checkCanCreateFile($file)
{
    if (file_exists($file)) {
        if (is_writable($file)) {
            return Display::label(get_lang('Writable'), 'success');
        } else {
            return Display::label(get_lang('Not writable'), 'important');
        }
    } else {
        $write = @file_put_contents($file, '');
        if (false !== $write) {
            unlink($file);

            return Display::label(get_lang('Writable'), 'success');
        } else {
            return Display::label(get_lang('Not writable'), 'important');
        }
    }
}
