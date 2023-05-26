<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\DataFixtures\LanguageFixtures;
use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Repository\GroupRepository;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\CoreBundle\Tool\ToolChain;
use Doctrine\Migrations\Configuration\Connection\ExistingConnection;
use Doctrine\Migrations\Configuration\Migration\PhpFile;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Query\Query;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\Container as SymfonyContainer;

/*
 * Chamilo LMS
 * This file contains functions used by the install and upgrade scripts.
 *
 * Ideas for future additions:
 * - a function get_old_version_settings to retrieve the config file settings
 *   of older versions before upgrading.
 */
define('SYSTEM_CONFIG_FILENAME', 'configuration.dist.php');

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
 * @return array
 *
 * @author  Christophe Gesch??
 * @author  Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @author  Yannick Warnier <yannick.warnier@dokeos.com>
 */
function checkExtension(
    string $extensionName,
    string $returnSuccess = 'Yes',
    string $returnFailure = 'No',
    bool $optional = false,
    string $enabledTerm = ''
): array {
    if (extension_loaded($extensionName)) {
        if (!empty($enabledTerm)) {
            $isEnabled = ini_get($enabledTerm);
            if ('1' == $isEnabled) {
                return [
                    'severity' => 'success',
                    'message' => $returnSuccess,
                ];
            } else {
                if ($optional) {
                    return [
                        'severity' => 'warning',
                        'message' => get_lang('Extension installed but not enabled'),
                    ];
                }

                return [
                    'severity' => 'danger',
                    'message' => get_lang('Extension installed but not enabled'),
                ];
            }
        }

        return [
            'severity' => 'success',
            'message' => $returnSuccess,
        ];
    } else {
        if ($optional) {
            return [
                'severity' => 'warning',
                'message' => $returnFailure,
            ];
        }

        return [
            'severity' => 'danger',
            'message' => $returnFailure,
        ];
    }
}

/**
 * This function checks whether a php setting matches the recommended value.
 *
 * @param string $phpSetting       A PHP setting to check
 * @param string $recommendedValue A recommended value to show on screen
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 */
function checkPhpSetting(
    string $phpSetting,
    string $recommendedValue
): array {
    $currentPhpValue = getPhpSetting($phpSetting);

    return $currentPhpValue == $recommendedValue
        ? ['severity' => 'success', 'value' => $currentPhpValue]
        : ['severity' => 'danger', 'value' => $currentPhpValue];
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
        return Display::label(get_lang('Not writable'), 'important');
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
        echo 'install-steps__step--active';
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
function display_language_selection_box($name = 'language_list', $default_language = 'en_US')
{
    // Displaying the box.
    return Display::select(
        'language_list',
        array_column(LanguageFixtures::getLanguages(), 'english_name', 'isocode'),
        $default_language,
        [],
        false
    );
}

/**
 * This function displays the requirements for installing Chamilo.
 *
 * @param string $updatePath         The updatePath given (if given)
 * @param array  $upgradeFromVersion The different subversions from version 1.9
 *
 * @author unknow
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 */
function display_requirements(
    string $installType,
    bool $badUpdatePath,
    string $updatePath = '',
    array $upgradeFromVersion = []
): array {
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
            if ($file_course_test_was_created) {
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

    //  SERVER REQUIREMENTS
    $timezone = checkPhpSettingExists('date.timezone');

    $phpVersion = phpversion();
    $isVersionPassed = version_compare($phpVersion, REQUIRED_PHP_VERSION, '<=') <= 1;

    $extensions = [];
    $extensions[] = [
        'title' => get_lang('Session support'),
        'url' => 'https://php.net/manual/en/book.session.php',
        'status' => checkExtension(
            'session',
            get_lang('Yes'),
            get_lang('Sessions extension not available')
        ),
    ];
    $extensions[] = [
        'title' => get_lang('MySQL Functions support'),
        'url' => 'https://php.net/manual/en/book.mysql.php',
        'status' => checkExtension(
            'pdo_mysql',
            get_lang('Yes'),
            get_lang('MySQL extension not available')
        ),
    ];
    $extensions[] = [
        'title' => get_lang('Zip support'),
        'url' => 'https://php.net/manual/en/book.zip.php',
        'status' => checkExtension(
            'zip',
            get_lang('Yes'),
            get_lang('Extension not available')
        ),
    ];
    $extensions[] = [
        'title' => get_lang('Zlib support'),
        'url' => 'https://php.net/manual/en/book.zlib.php',
        'status' => checkExtension(
            'zlib',
            get_lang('Yes'),
            get_lang('Zlib extension not available')
        ),
    ];
    $extensions[] = [
        'title' => get_lang('Perl-compatible regular expressions support'),
        'url' => 'https://php.net/manual/en/book.pcre.php',
        'status' => checkExtension(
            'pcre',
            get_lang('Yes'),
            get_lang('PCRE extension not available')
        ),
    ];
    $extensions[] = [
        'title' => get_lang('XML support'),
        'url' => 'https://php.net/manual/en/book.xml.php',
        'status' => checkExtension(
            'xml',
            get_lang('Yes'),
            get_lang('No')
        ),
    ];
    $extensions[] = [
        'title' => get_lang('Internationalization support'),
        'url' => 'https://php.net/manual/en/book.intl.php',
        'status' => checkExtension(
            'intl',
            get_lang('Yes'),
            get_lang('No')
        ),
    ];
    $extensions[] = [
        'title' => get_lang('JSON support'),
        'url' => 'https://php.net/manual/en/book.json.php',
        'status' => checkExtension(
            'json',
            get_lang('Yes'),
            get_lang('No')
        ),
    ];
    $extensions[] = [
        'title' => get_lang('GD support'),
        'url' => 'https://php.net/manual/en/book.image.php',
        'status' => checkExtension(
            'gd',
            get_lang('Yes'),
            get_lang('GD Extension not available')
        ),
    ];
    $extensions[] = [
        'title' => get_lang('cURL support'),
        'url' => 'https://php.net/manual/en/book.curl.php',
        'status' => checkExtension(
            'curl',
            get_lang('Yes'),
            get_lang('No')
        ),
    ];
    $extensions[] = [
        'title' => get_lang('Multibyte string support'),
        'url' => 'https://php.net/manual/en/book.mbstring.php',
        'status' => checkExtension(
            'mbstring',
            get_lang('Yes'),
            get_lang('MBString extension not available'),
            true
        ),
    ];
    $extensions[] = [
        'title' => get_lang('Exif support'),
        'url' => 'https://php.net/manual/en/book.exif.php',
        'status' => checkExtension(
            'exif',
            get_lang('Yes'),
            get_lang('Exif extension not available'),
            true
        ),
    ];
    $extensions[] = [
        'title' => get_lang('Zend OpCache support'),
        'url' => 'https://php.net/opcache',
        'status' => checkExtension(
            'Zend OPcache',
            get_lang('Yes'),
            get_lang('No'),
            true,
            'opcache.enable'
        ),
    ];
    $extensions[] = [
        'title' => get_lang('APCu support'),
        'url' => 'https://php.net/apcu',
        'status' => checkExtension(
            'apcu',
            get_lang('Yes'),
            get_lang('No'),
            true,
            'apc.enabled'
        ),
    ];
    $extensions[] = [
        'title' => get_lang('Iconv support'),
        'url' => 'https://php.net/manual/en/book.iconv.php',
        'status' => checkExtension(
            'iconv',
            get_lang('Yes'),
            get_lang('No'),
            true
        ),
    ];
    $extensions[] = [
        'title' => get_lang('LDAP support'),
        'url' => 'https://php.net/manual/en/book.ldap.php',
        'status' => checkExtension(
            'ldap',
            get_lang('Yes'),
            get_lang('LDAP Extension not available'),
            true
        ),
    ];
    $extensions[] = [
        'title' => get_lang('Xapian support'),
        'url' => 'https://xapian.org/',
        'status' => checkExtension(
            'xapian',
            get_lang('Yes'),
            get_lang('No'),
            true
        ),
    ];

    // RECOMMENDED SETTINGS
    // Note: these are the settings for Joomla, does this also apply for Chamilo?
    // Note: also add upload_max_filesize here so that large uploads are possible
    $phpIni = [];
    $phpIni[] = [
        'title' => 'Display Errors',
        'url' => 'https://php.net/manual/ref.errorfunc.php#ini.display-errors',
        'recommended' => 'OFF',
        'current' => checkPhpSetting('display_errors', 'OFF'),
    ];
    $phpIni[] = [
        'title' => 'File Uploads',
        'url' => 'https://php.net/manual/ini.core.php#ini.file-uploads',
        'recommended' => 'ON',
        'current' => checkPhpSetting('file_uploads', 'ON'),
    ];
    $phpIni[] = [
        'title' => 'Session auto start',
        'url' => 'https://php.net/manual/ref.session.php#ini.session.auto-start',
        'recommended' => 'OFF',
        'current' => checkPhpSetting('session.auto_start', 'OFF'),
    ];
    $phpIni[] = [
        'title' => 'Short Open Tag',
        'url' => 'https://php.net/manual/ini.core.php#ini.short-open-tag',
        'recommended' => 'OFF',
        'current' => checkPhpSetting('short_open_tag', 'OFF'),
    ];
    $phpIni[] = [
        'title' => 'Cookie HTTP Only',
        'url' => 'https://www.php.net/manual/en/session.configuration.php#ini.session.cookie-httponly',
        'recommended' => 'ON',
        'current' => checkPhpSetting('session.cookie_httponly', 'ON'),
    ];
    $phpIni[] = [
        'title' => 'Maximum upload file size',
        'url' => 'https://php.net/manual/ini.core.php#ini.upload-max-filesize',
        'recommended' => '>= '.REQUIRED_MIN_UPLOAD_MAX_FILESIZE.'M',
        'current' => compare_setting_values(ini_get('upload_max_filesize'), REQUIRED_MIN_UPLOAD_MAX_FILESIZE),
    ];
    $phpIni[] = [
        'title' => 'Maximum post size',
        'url' => 'https://php.net/manual/ini.core.php#ini.post-max-size',
        'recommended' => '>= '.REQUIRED_MIN_POST_MAX_SIZE.'M',
        'current' => compare_setting_values(ini_get('post_max_size'), REQUIRED_MIN_POST_MAX_SIZE),
    ];
    $phpIni[] = [
        'title' => 'Memory Limit',
        'url' => 'https://www.php.net/manual/en/ini.core.php#ini.memory-limit',
        'recommended' => '>= '.REQUIRED_MIN_MEMORY_LIMIT.'M',
        'current' => compare_setting_values($originalMemoryLimit, REQUIRED_MIN_MEMORY_LIMIT),
    ];

    // DIRECTORY AND FILE PERMISSIONS
    $_SESSION['permissions_for_new_directories'] = $_setting['permissions_for_new_directories'] = $dir_perm_verified;
    $_SESSION['permissions_for_new_files'] = $_setting['permissions_for_new_files'] = $fil_perm_verified;

    $dirPerm = '0'.decoct($dir_perm_verified);
    $filePerm = '0'.decoct($fil_perm_verified);

    $pathPermissions = [];

    if (file_exists(api_get_path(SYS_CODE_PATH).'inc/conf/configuration.php')) {
        $pathPermissions[] = [
            'requirement' => api_get_path(SYS_CODE_PATH).'inc/conf',
            'status' => is_writable(api_get_path(SYS_CODE_PATH).'inc/conf'),
        ];
    }
    $basePath = api_get_path(SYMFONY_SYS_PATH);

    $pathPermissions[] = [
        'item' => $basePath.'var/',
        'status' => is_writable($basePath.'var'),
    ];
    $pathPermissions[] = [
        'item' => $basePath.'.env.local',
        'status' => checkCanCreateFile($basePath.'.env.local'),
    ];
    $pathPermissions[] = [
        'item' => $basePath.'config/',
        'status' => is_writable($basePath.'config'),
    ];
    $pathPermissions[] = [
        'item' => get_lang('Permissions for new directories'),
        'status' => $dirPerm,
    ];
    $pathPermissions[] = [
        'item' => get_lang('Permissions for new files'),
        'status' => $filePerm,
    ];

    $notWritable = [];
    $deprecatedToRemove = [];

    $error = false;

    if ('update' !== $installType || !empty($updatePath) && !$badUpdatePath) {
        // First, attempt to set writing permissions if we don't have them yet
        //$perm = api_get_permissions_for_new_directories();
        $perm = octdec('0777');
        //$perm_file = api_get_permissions_for_new_files();
        $perm_file = octdec('0666');

        $checked_writable = api_get_path(SYS_PUBLIC_PATH);
        if (!is_writable($checked_writable)) {
            $notWritable[] = $checked_writable;
            @chmod($checked_writable, $perm);
        }

        if (!$course_test_was_created) {
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
            $error = true;
        }

        $deprecated = [
            api_get_path(SYS_CODE_PATH).'exercice/',
            api_get_path(SYS_CODE_PATH).'newscorm/',
            api_get_path(SYS_PLUGIN_PATH).'ticket/',
            api_get_path(SYS_PLUGIN_PATH).'skype/',
        ];

        foreach ($deprecated as $deprecatedDirectory) {
            if (!is_dir($deprecatedDirectory)) {
                continue;
            }
            $deprecatedToRemove[] = $deprecatedDirectory;
        }
    }

    return [
        'timezone' => $timezone,
        'isVersionPassed' => $isVersionPassed,
        'phpVersion' => $phpVersion,
        'extensions' => $extensions,
        'phpIni' => $phpIni,
        'pathPermissions' => $pathPermissions,
        'step2_update_6' => isset($_POST['step2_update_6']),
        'notWritable' => $notWritable,
        'existsConfigurationFile' => file_exists(api_get_path(CONFIGURATION_PATH).'configuration.php'),
        'deprecatedToRemove' => $deprecatedToRemove,
        'installError' => $error,
    ];
}

/**
 * Displays the license (GNU GPL) as step 2, with
 * - an "I accept" button named step3 to proceed to step 3;
 * - a "Back" button named step1 to go back to the first step.
 */
function display_license_agreement(): array
{
    $license = api_htmlentities(@file_get_contents(api_get_path(SYMFONY_SYS_PATH).'public/documentation/license.txt'));

    $activtiesList = [
        'Advertising/Marketing/PR',
        'Agriculture/Forestry',
        'Architecture',
        'Banking/Finance',
        'Biotech/Pharmaceuticals',
        'Business Equipment',
        'Business Services',
        'Construction',
        'Consulting/Research',
        'Education',
        'Engineering',
        'Environmental',
        'Government',
        'Health Care',
        'Hospitality/Lodging/Travel',
        'Insurance',
        'Legal',
        'Manufacturing',
        'Media/Entertainment',
        'Mortgage',
        'Non-Profit',
        'Real Estate',
        'Restaurant',
        'Retail',
        'Shipping/Transportation',
        'Technology',
        'Telecommunications',
        'Other',
    ];

    $rolesList = [
        'Administration',
        'CEO/President/ Owner',
        'CFO',
        'CIO/CTO',
        'Consultant',
        'Customer Service',
        'Engineer/Programmer',
        'Facilities/Operations',
        'Finance/ Accounting Manager',
        'Finance/ Accounting Staff',
        'General Manager',
        'Human Resources',
        'IS/IT Management',
        'IS/ IT Staff',
        'Marketing Manager',
        'Marketing Staff',
        'Partner/Principal',
        'Purchasing Manager',
        'Sales/ Business Dev. Manager',
        'Sales/ Business Dev.',
        'Vice President/Senior Manager',
        'Other',
    ];

    $countriesList = get_countries_list_from_array();

    $languagesList = [
        ['bulgarian', 'Bulgarian'],
        ['indonesian', 'Bahasa Indonesia'],
        ['bosnian', 'Bosanski'],
        ['german', 'Deutsch'],
        ['english', 'English'],
        ['spanish', 'Spanish'],
        ['french', 'Français'],
        ['italian', 'Italian'],
        ['hungarian', 'Magyar'],
        ['dutch', 'Nederlands'],
        ['brazilian', 'Português do Brasil'],
        ['portuguese', 'Português europeu'],
        ['slovenian', 'Slovenčina'],
    ];

    return [
        'license' => $license,
        'activitiesList' => $activtiesList,
        'rolesList' => $rolesList,
        'countriesList' => $countriesList,
        'languagesList' => $languagesList,
    ];
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
 */
function display_database_settings_form(
    string $installType,
    string $dbHostForm,
    string $dbUsernameForm,
    string $dbPassForm,
    string $dbNameForm,
    int $dbPortForm = 3306
): array {
    if ('update' === $installType) {
        $dbHostForm = get_config_param('db_host');
        $dbUsernameForm = get_config_param('db_user');
        $dbPassForm = get_config_param('db_password');
        $dbNameForm = get_config_param('main_database');
        $dbPortForm = get_config_param('db_port');
    }

    $databaseExists = false;
    $databaseConnectionError = '';
    $connectionParams = null;

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

            $schemaManager = $manager->getConnection()->createSchemaManager();
            $databases = $schemaManager->listDatabases();
            $databaseExists = in_array($dbNameForm, $databases);
        }
    } catch (Exception $e) {
        $databaseConnectionError = $e->getMessage();
        $manager = null;
    }

    if ($manager && $manager->getConnection()->isConnected()) {
        $connectionParams = $manager->getConnection()->getParams();
    }

    return [
        'dbHostForm' => $dbHostForm,
        'dbPortForm' => $dbPortForm,
        'dbUsernameForm' => $dbUsernameForm,
        'dbPassForm' => $dbPassForm,
        'dbNameForm' => $dbNameForm,
        'examplePassword' => api_generate_password(8, false),
        'dbExists' => $databaseExists,
        'dbConnError' => $databaseConnectionError,
        'connParams' => $connectionParams,
    ];
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
    $html .= '<label class="col-sm-6 p-2 control-label">'.$parameterName.'</label>';
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
 */
function display_configuration_settings_form(
    string $installType,
    string $urlForm,
    string $languageForm,
    string $emailForm,
    string $adminFirstName,
    string $adminLastName,
    string $adminPhoneForm,
    string $campusForm,
    string $institutionForm,
    string $institutionUrlForm,
    string $encryptPassForm,
    string $allowSelfReg,
    string $allowSelfRegProf,
    string $loginForm,
    string $passForm
): array {
    if ('update' !== $installType && empty($languageForm)) {
        $languageForm = $_SESSION['install_language'];
    }

    $stepData = [];

    if ('update' === $installType) {
        $stepData['rootWeb'] = get_config_param('root_web');
        $stepData['rootSys'] = get_config_param('root_sys');
        $stepData['systemVersion'] = get_config_param('system_version');
    }

    $stepData['loginForm'] = $loginForm;
    $stepData['passForm'] = $passForm;
    $stepData['adminFirstName'] = $adminFirstName;
    $stepData['adminLastName'] = $adminLastName;
    $stepData['emailForm'] = $emailForm;
    $stepData['adminPhoneForm'] = $adminPhoneForm;
    $stepData['languageForm'] = $languageForm;
    $stepData['urlForm'] = $urlForm;
    $stepData['campusForm'] = $campusForm;
    $stepData['institutionForm'] = $institutionForm;
    $stepData['institutionUrlForm'] = $institutionUrlForm;
    $stepData['encryptPassForm'] = $encryptPassForm;
    $stepData['allowSelfReg'] = $allowSelfReg;
    $stepData['allowSelfRegProf'] = $allowSelfRegProf;

    return $stepData;
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
    $options = array_combine($a_countries, $a_countries);
    if ($combo) {
        return Display::select(
            'country',
            $options + ['' => get_lang('Select one')],
            '',
            ['id' => 'country'],
            false
        );
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

function compare_setting_values(string $current_value, string $wanted_value): array
{
    $current_value_string = $current_value;
    $current_value = (float) $current_value;
    $wanted_value = (float) $wanted_value;

    return $current_value >= $wanted_value
        ? ['severity' => 'success', 'value' => $current_value_string]
        : ['severity' => 'danger', 'value' => $current_value_string];
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

function installTools($container, $manager, $upgrade = false)
{
    error_log('installTools');
    // Install course tools (table "tool")
    /** @var ToolChain $toolChain */
    $toolChain = $container->get(ToolChain::class);
    $toolChain->createTools();
}

/**
 * @param SymfonyContainer $container
 * @param bool             $upgrade
 */
function installSchemas($container, $upgrade = false)
{
    error_log('installSchemas');
    $settingsManager = $container->get(Chamilo\CoreBundle\Settings\SettingsManager::class);

    $urlRepo = $container->get(AccessUrlRepository::class);
    $accessUrl = $urlRepo->find(1);
    if (null === $accessUrl) {
        $em = Database::getManager();

        // Creating AccessUrl.
        $accessUrl = new AccessUrl();
        $accessUrl
            ->setUrl(AccessUrl::DEFAULT_ACCESS_URL)
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
    Container::setLegacyServices($container);
    error_log('setLegacyServices');
    $manager = Database::getManager();

    /** @var GroupRepository $repo */
    $repo = $container->get(GroupRepository::class);
    $repo->createDefaultGroups();

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
    Container::setContainer($container);
    Container::setLegacyServices($container);

    $timezone = api_get_timezone();

    $repo = Container::getUserRepository();
    /** @var User $admin */
    $admin = $repo->findOneBy(['username' => 'admin']);

    $admin
        ->setLastname($adminLastName)
        ->setFirstname($adminFirstName)
        ->setUsername($loginForm)
        ->setStatus(1)
        ->setPlainPassword($passForm)
        ->setEmail($emailForm)
        ->setOfficialCode('ADMIN')
        ->setAuthSource(PLATFORM_AUTH_SOURCE)
        ->setPhone($adminPhoneForm)
        ->setLocale($languageForm)
        ->setTimezone($timezone)
    ;

    $repo->updateUser($admin);

    $repo = Container::getUserRepository();
    $repo->updateUser($admin);

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
    //echo '<a class="btn btn--secondary" href="javascript:void(0)" id="details_button">'.get_lang('Details').'</a><br />';
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
 */
function checkCanCreateFile(string $file): bool
{
    if (file_exists($file)) {
        return is_writable($file);
    }

    $write = @file_put_contents($file, '');

    if (false !== $write) {
        unlink($file);

        return true;
    }

    return false;
}
