<?php
/* For licensing terms, see /license.txt */

use Cocur\Slugify\Slugify;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Input\ArrayInput;

/**
 * Class Virtual.
 */
class Virtual
{
    /**
     * @param array $_configuration
     */
    public static function hookConfiguration(&$_configuration)
    {
        global $virtualChamilo;

        if (defined('CLI_SCRIPT') && !defined('CLI_VCHAMILO_OVERRIDE')) {
            return;
        }

        // provides an effective value for the virtual root_web based on domain analysis
        self::getHostName($_configuration);

        // We are on physical chamilo. Let original config play
        $virtualChamiloWebRoot = rtrim($_configuration['vchamilo_web_root'], '/').'/';

        $virtualChamilo = [];
        if ($_configuration['root_web'] == $virtualChamiloWebRoot) {
            return;
        }

        // pre hook to chamilo main table and get alternate configuration.
        // sure Database object is not set up. Soo use bootstrap connection
        /** @var \Doctrine\DBAL\Connection $connection */
        $connection = self::bootConnection($_configuration);

        $query = "SELECT * FROM vchamilo WHERE root_web = '$virtualChamiloWebRoot'";
        $result = $connection->executeQuery($query);

        if ($result->rowCount()) {
            $data = $result->fetch();
            $excludes = ['id', 'name'];
            $query = "SELECT * FROM settings_current WHERE subkey = 'vchamilo'";
            $virtualSettings = $connection->executeQuery($query);
            $virtualSettings = $virtualSettings->fetchAll();

            $homePath = '';
            $coursePath = '';
            $archivePath = '';
            $uploadPath = '';
            $passwordEncryption = '';
            foreach ($virtualSettings as $setting) {
                switch ($setting['variable']) {
                    case 'vchamilo_upload_real_root':
                        $uploadPath = $setting['selected_value'];
                        break;
                    case 'vchamilo_home_real_root':
                        $homePath = $setting['selected_value'];
                        break;
                    case 'vchamilo_course_real_root':
                        $coursePath = $setting['selected_value'];
                        break;
                    case 'vchamilo_archive_real_root':
                        $archivePath = $setting['selected_value'];
                        break;
                    case 'vchamilo_password_encryption':
                        $passwordEncryption = $setting['selected_value'];
                        break;
                }
            }

            if (empty($homePath) || empty($coursePath) || empty($archivePath) || empty($uploadPath)) {
                echo 'Configure correctly the vchamilo plugin';
                exit;
            }

            // Only load if is visible
            if ($data && $data['visible'] === '1') {
                foreach ($data as $key => $value) {
                    if (!in_array($key, $excludes)) {
                        // Avoid empty password_encryption
                        if ($key == 'password_encryption' && empty($value)) {
                            continue;
                        }
                        $_configuration[$key] = $value;
                    }
                    $_configuration['virtual'] = $data['root_web'].'/';
                }

                $data['SYS_ARCHIVE_PATH'] = self::addTrailingSlash($archivePath).$data['slug'];
                $data['SYS_HOME_PATH'] = self::addTrailingSlash($homePath).$data['slug'];
                $data['SYS_COURSE_PATH'] = self::addTrailingSlash($coursePath).$data['slug'];
                $data['SYS_UPLOAD_PATH'] = self::addTrailingSlash($uploadPath).$data['slug'];

                $data['WEB_HOME_PATH'] = self::addTrailingSlash($data['home_url']);
                $data['WEB_UPLOAD_PATH'] = self::addTrailingSlash($data['upload_url']);
                $data['WEB_ARCHIVE_PATH'] = self::addTrailingSlash($data['archive_url']);

                if (!empty($passwordEncryption)) {
                    $_configuration['password_encryption'] = $passwordEncryption;
                }

                // Instance cannot have multiple urls
                $_configuration['multiple_access_urls'] = false;
                $_configuration['virtual_css_theme_folder'] = '';
                if (isset($data['css_theme_folder']) && !empty($data['css_theme_folder'])) {
                    $_configuration['virtual_css_theme_folder'] = $data['css_theme_folder'];
                }
                $virtualChamilo = $data;
            } else {
                exit("This portal is disabled. Please contact your administrator");
            }
        } // otherwise it means the plugin was not configured yet
    }

    /**
     * @param array $_configuration
     */
    public static function getHostName(&$_configuration)
    {
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
            $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' || !empty($_configuration['force_https_forwarded_proto'])
        ) {
            $protocol = 'https';
        } else {
            if (!empty($_SERVER['HTTPS'])) {
                $protocol = 'https';
            } else {
                $protocol = 'http';
            }
        }

        if (defined('CLI_VCHAMILO_OVERRIDE')) {
            $_configuration['vchamilo_web_root'] = CLI_VCHAMILO_OVERRIDE;
            $_configuration['vchamilo_name'] = preg_replace('#https?://#', '', CLI_VCHAMILO_OVERRIDE);
            // remove radical from override for name

            // fake the server signature
            global $_SERVER;

            $_SERVER['SERVER_NAME'] = $_configuration['vchamilo_name'];
            $_SERVER['HTTP_HOST'] = $_configuration['vchamilo_name'];
            $_SERVER['QUERY_STRING'] = '';
            $_SERVER['REQUEST_URI'] = CLI_VCHAMILO_OVERRIDE;

            return;
        }

        $contentPrefix = '/';
        if (isset($_SERVER['CONTEXT_PREFIX']) && !empty($_SERVER['CONTEXT_PREFIX'])) {
            $contentPrefix = $_SERVER['CONTEXT_PREFIX'];
        } else {
            // Getting url_append from URL
            if (isset($_SERVER['REQUEST_URI'])) {
                $requestUri = $_SERVER['REQUEST_URI'];
                if (strpos($requestUri, '/courses/') !== false) {
                    $result = substr($requestUri, 0, strpos($requestUri, '/courses/'));
                    if (!empty($result) && $result != '/') {
                        $contentPrefix = $result;
                    }
                }
            }
        }

        $_configuration['vchamilo_web_root'] = "{$protocol}://".@$_SERVER['HTTP_HOST'].$contentPrefix;

        $_configuration['vchamilo_name'] = @$_SERVER['HTTP_HOST'];
        if (empty($_configuration['vchamilo_name'])) { // try again with another source if has failed
            $_configuration['vchamilo_name'] = "{$protocol}://".$_SERVER['SERVER_NAME'];
            if ($_SERVER['SERVER_PORT'] != 80) {
                $_configuration['vchamilo_name'] .= ':'.$_SERVER['SERVER_PORT'];
            }
            $_configuration['vchamilo_name'] = $_SERVER['SERVER_NAME'];
        }
    }

    /**
     * @param string $path
     *
     * @return string
     */
    public static function addTrailingSlash($path)
    {
        return substr($path, -1) == '/' ? $path : $path.'/';
    }

    /**
     * provides a side connection to a vchamilo database.
     *
     * @param array $_configuration
     *
     * @return \Doctrine\DBAL\Driver\Connection
     */
    public static function bootConnection(&$_configuration)
    {
        $dbParams = [
            'driver' => 'pdo_mysql',
            'host' => $_configuration['db_host'],
            'user' => $_configuration['db_user'],
            'password' => $_configuration['db_password'],
            'dbname' => isset($_configuration['main_database']) ? $_configuration['main_database'] : '',
            // Only relevant for pdo_sqlite, specifies the path to the SQLite database.
            'path' => isset($_configuration['db_path']) ? $_configuration['db_path'] : '',
            // Only relevant for pdo_mysql, pdo_pgsql, and pdo_oci/oci8,
            'port' => isset($_configuration['db_port']) ? $_configuration['db_port'] : '',
        ];

        try {
            $database = new \Database();
            $connection = $database->connect(
                $dbParams,
                $_configuration['root_sys'],
                $_configuration['root_sys'],
                true
            );
        } catch (Exception $e) {
            echo 'Side connection failure with '.$_configuration['db_host'].', '.$_configuration['db_user'].', ******** ';
            exit();
        }

        return $connection;
    }

    /**
     * @param string $url
     */
    public static function redirect($url)
    {
        if (preg_match('#https?://#', $url)) {
            header('location: '.$url);
        } else {
            header('location: '.api_get_path(WEB_PATH).$url);
        }
        exit;
    }

    /**
     * @param string $course_folder
     *
     * @return string
     */
    public static function getHtaccessFragment($course_folder)
    {
        $str = "
        # Change this file to fit your configuration and save it as .htaccess in the courses folder #
        # Chamilo mod rewrite
        # Comment lines start with # and are not processed
        
        <IfModule mod_rewrite.c>
        RewriteEngine On
        
        # Rewrite base is the dir chamilo is installed in with trailing slash
        RewriteBase /{$course_folder}/
        
        # Do not rewrite on the main dir
        # Change this path to the path of your main folder
        RewriteCond %{REQUEST_URI} !^/main/
        
        #replace nasty ampersands by 3 slashes, we change these back in download.php
        RewriteRule ([^/]+)/document/(.*)&(.*)$ $1/document/$2///$3 [N]
        
        # Rewrite everything in the scorm folder of a course to the download script
        RewriteRule ([^/]+)/scorm/(.*)$ /main/document/download_scorm.php?doc_url=/$2&cDir=$1 [QSA,L]
        
        # Rewrite everything in the document folder of a course to the download script
        RewriteRule ([^/]+)/document/(.*)$ /main/document/download.php?doc_url=/$2&cDir=$1 [QSA,L]
        
        # Rewrite everything in the work folder
        RewriteRule ([^/]+)/work/(.*)$ /main/work/download.php?file=work/$2&cDir=$1 [QSA,L]
        </IfModule>
        ";

        return $str;
    }

    /**
     * @return string
     */
    public static function getDefaultCourseIndexFragment()
    {
        return "<html><head></head><body></body></html>";
    }

    /**
     * @param string $template
     *
     * @return bool
     */
    public static function templateExists($template)
    {
        global $_configuration;

        // Find and checktemplate directory (files and SQL).
        $separator = DIRECTORY_SEPARATOR;
        $templatefoldername = 'plugin'.$separator.'vchamilo'.$separator.'templates';
        $relative_datadir = $templatefoldername.$separator.$template.'_sql';
        $absolute_datadir = $_configuration['root_sys'].$relative_datadir;

        return is_dir($absolute_datadir);
    }

    /**
     * drop a vchamilo instance databases using the physical connection.
     *
     * @param stdClass $params
     *                         return an array of errors or false if ok
     */
    public static function dropDatabase($params)
    {
        $params = clone $params;

        if (empty($params->main_database)) {
            Display::addFlash(Display::return_message('No database found'));

            return;
        }

        $databaseToDelete = $params->main_database;
        unset($params->main_database);
        $connection = self::getConnectionFromInstance($params);
        if ($connection) {
            $databases = $connection->getSchemaManager()->listDatabases();

            if (in_array($databaseToDelete, $databases)) {
                $connection->getSchemaManager()->dropDatabase(
                    $databaseToDelete
                );
                Display::addFlash(
                    Display::return_message(
                        'Database deleted: '.$databaseToDelete
                    )
                );
            } else {
                Display::addFlash(
                    Display::return_message(
                        'Database does not exist: '.$databaseToDelete
                    )
                );
            }
        } else {
            Display::addFlash(
                Display::return_message(
                    "Cannot connect DB: $databaseToDelete"
                )
            );
        }

        return false;
    }

    /**
     * @param stdClass $params
     *
     * @return bool
     */
    public static function createDatabase($params)
    {
        $params = clone $params;
        $databaseName = $params->main_database;
        unset($params->main_database);

        $connection = self::getConnectionFromInstance($params);
        if ($connection) {
            $databaseList = $connection->getSchemaManager()->listDatabases();

            if (!in_array($databaseName, $databaseList)) {
                $connection->getSchemaManager()->createDatabase($databaseName);
                Display::addFlash(
                    Display::return_message("Creating DB ".$databaseName)
                );
            } else {
                Display::addFlash(
                    Display::return_message("DB already exists: ".$databaseName)
                );
            }

            return true;
        }

        return false;
    }

    /**
     * get a proper SQLdump command.
     *
     * @param object $vchamilodata the complete new host information
     *
     * @return string the shell command
     */
    public static function getDatabaseDumpCmd($vchamilodata)
    {
        $pgm = self::getConfig('vchamilo', 'mysql_cmd');

        if (!$pgm) {
            $pgm = '/usr/bin/mysql';
        }

        $phppgm = str_replace("\\", '/', $pgm);
        $phppgm = str_replace("\"", '', $phppgm);
        $pgm = str_replace("/", DIRECTORY_SEPARATOR, $pgm);

        if (!is_executable($phppgm)) {
            throw new Exception('databasecommanddoesnotmatchanexecutablefile');
        }

        // Retrieves the host configuration (more secure).
        $vchamilodata = empty($vchamilodata) ? self::makeThis() : $vchamilodata;
        if (strstr($vchamilodata->db_host, ':') !== false) {
            list($vchamilodata->db_host, $vchamilodata->db_port) = explode(
                ':',
                $vchamilodata->db_host
            );
        }

        // Password.
        $databasePassword = '';
        if (!empty($vchamilodata->db_password)) {
            $databasePassword = '-p'.escapeshellarg($vchamilodata->db_password).' ';
        }

        // Making the command line (see 'vconfig.php' file for defining the right paths).
        $sqlcmd = $pgm.' -h'.$vchamilodata->db_host.(isset($vchamilodata->db_port) ? ' -P'.$vchamilodata->db_port.' ' : ' ');
        $sqlcmd .= '-u'.$vchamilodata->db_user.' '.$databasePassword;
        $sqlcmd .= '%DATABASE% < ';

        return $sqlcmd;
    }

    /**
     * @param stdClass $vchamilo
     * @param string   $template
     *
     * @return bool
     */
    public static function loadDbTemplate($vchamilo, $template)
    {
        global $_configuration;

        // Make template directory (files and SQL).
        $separator = DIRECTORY_SEPARATOR;
        $templatefoldername = 'plugin'.$separator.'vchamilo'.$separator.'templates';
        $absolute_datadir = $_configuration['root_sys'].$templatefoldername.$separator.$template.$separator.'dump.sql';

        if (!$sqlcmd = self::getDatabaseDumpCmd($vchamilo)) {
            return false;
        }

        $sqlcmd = str_replace('%DATABASE%', $vchamilo->main_database, $sqlcmd);

        // Make final commands to execute, depending on the database type.
        $import = $sqlcmd.$absolute_datadir;

        // Execute the command.
        Display::addFlash(Display::return_message("Load database from template dump: \n $import "));

        if (!defined('CLI_SCRIPT')) {
            putenv('LANG=en_US.utf-8');
        }
        // ensure utf8 is correctly handled by php exec()
        // @see http://stackoverflow.com/questions/10028925/call-a-program-via-shell-exec-with-utf-8-text-input

        exec($import, $output, $return);
        if (!empty($output)) {
            Display::addFlash(Display::return_message(implode("\n", $output)."\n"));
        }

        return true;
    }

    /**
     * Backups a database for having a snapshot.
     *
     * @param $vchamilo      object        The Vchamilo object
     * @param $outputfilerad string        The output SQL file radical
     *
     * @return bool if TRUE, dumping database was a success, otherwise FALSE
     */
    public static function backupDatabase($vchamilo, $outputfilerad)
    {
        // Separating host and port, if sticked.
        if (strstr($vchamilo->db_host, ':') !== false) {
            list($host, $port) = explode(':', $vchamilo->db_host);
        } else {
            $host = $vchamilo->db_host;
        }

        // By default, empty password.
        $pass = '';
        $pgm = null;

        if (empty($port)) {
            $port = 3306;
        }

        // Password.
        if (!empty($vchamilo->db_password)) {
            $pass = "-p".escapeshellarg($vchamilo->db_password);
        }

        // Making the commands for each database.
        $cmds = [];
        // Windows environments are not supported for this plugin at this time
        //if ($CFG->ostype == 'WINDOWS') {
        //    $cmd_main = "-h{$host} -P{$port} -u{$vchamilo->db_user} {$pass} {$vchamilo->main_database}";
        //    $cmds[] = $cmd_main . ' > ' . $outputfilerad;
        //} else {
        $cmd_main = "-h{$host} -P{$port} -u{$vchamilo->db_user} {$pass} {$vchamilo->main_database}";
        $cmds[] = $cmd_main.' > '.escapeshellarg($outputfilerad);
        //}

        $mysqldumpcmd = self::getConfig('vchamilo', 'cmd_mysqldump', true);

        $pgm = !empty($mysqldumpcmd) ? stripslashes($mysqldumpcmd) : false;

        if (!$pgm) {
            $message = "Database dump command not available check here: ";
            $url = api_get_path(WEB_CODE_PATH).'admin/configure_plugin.php?name=vchamilo';
            $message .= Display::url($url, $url);
            Display::addFlash(Display::return_message($message));

            return false;
        } else {
            $phppgm = str_replace("\\", '/', $pgm);
            $phppgm = str_replace("\"", '', $phppgm);
            $pgm = str_replace('/', DIRECTORY_SEPARATOR, $pgm);

            if (!is_executable($phppgm)) {
                $message = "Database dump command $phppgm does not match any executable";
                Display::addFlash(Display::return_message($message));

                return false;
            }

            // executing all commands
            foreach ($cmds as $cmd) {
                // Final command.
                $cmd = $pgm.' '.$cmd;

                // Executes the SQL command.
                exec($cmd, $execoutput, $returnvalue);
            }
        }

        // End with success.
        return 1;
    }

    /**
     * read manifest values in vchamilo template.
     */
    public static function getVmanifest($version)
    {
        $templatewwwroot = '';
        // Define the $templatewwwroot content, found in manifest.php for this template
        $file = api_get_path(SYS_PATH).'/plugin/vchamilo/templates/'.$version.'/manifest.php';
        if (file_exists($file)) {
            include $file;

            $manifest = new stdClass();
            $manifest->templatewwwroot = $templatewwwroot;
            //    $manifest->templatevdbprefix = $templatevdbprefix;
            //    $manifest->coursefolder = $coursefolder;

            return $manifest;
        }

        return false;
    }

    /**
     * make a fake vchamilo that represents the current host.
     */
    public static function makeThis()
    {
        global $_configuration;

        $thisPortal = new stdClass();
        $thisPortal->root_web = $_configuration['root_web'];
        $thisPortal->db_host = $_configuration['db_host'];
        $thisPortal->db_user = $_configuration['db_user'];
        $thisPortal->db_password = $_configuration['db_password'];
        $thisPortal->main_database = $_configuration['main_database'];

        return $thisPortal;
    }

    /**
     * Get available templates for defining a new virtual host.
     *
     * @return array the available templates, or EMPTY array
     */
    public static function getAvailableTemplates()
    {
        global $_configuration;

        $separator = DIRECTORY_SEPARATOR;

        $templatefoldername = 'plugin'.$separator.'vchamilo'.$separator.'templates';
        $tempDir = $_configuration['root_sys'].$templatefoldername;

        // Scans the templates.
        if (!is_dir($tempDir)) {
            $mode = api_get_permissions_for_new_directories();
            mkdir($tempDir, $mode, true);
        }

        $finder = new \Symfony\Component\Finder\Finder();
        $dirs = $finder->in($tempDir)->depth('== 0');

        // Retrieves template(s) name(s). Should be hostnames.
        $templates = [];
        /*if ($addEmptyTemplate) {
            $templates = array('' => $plugin->get_lang('emptysite'));
        }*/

        $template = self::getConfig('vchamilo', 'default_template');

        if ($dirs) {
            /** @var Symfony\Component\Finder\SplFileInfo $dir */
            foreach ($dirs as $dir) {
                if (is_dir($dir->getPathname())) {
                    // A template is considered when a dump.sql exists.
                    if (file_exists($dir->getPathname().'/dump.sql')) {
                        $templateName = $dir->getRelativePathname();
                        if ($templateName == $template) {
                            $templateName .= ' (default)';
                        }
                        $templates[$dir->getRelativePathname()] = $templateName;
                    }
                }
            }
        }

        return $templates;
    }

    /**
     * this function set will map standard moodle API calls to chamilo
     * internal primitives. This avoids too many changes to do in imported
     * code.
     */
    public static function getConfig($module, $key, $isplugin = true)
    {
        if ($isplugin) {
            $key = $module.'_'.$key;
        }

        $params = ['variable = ? AND subkey = ?' => [$key, $module]];
        $result = api_get_settings_params_simple($params);
        if ($result) {
            return $result['selected_value'];
        }

        return false;
    }

    /**
     * @param stdClass $vchamilo
     * @param string   $template
     */
    public static function loadFilesFromTemplate($vchamilo, $template)
    {
        global $_configuration;

        // Make template directory (files and SQL).
        $separator = DIRECTORY_SEPARATOR;
        $templateDir = $_configuration['root_sys'].'plugin'.$separator.'vchamilo'.$separator.'templates'.$separator.$template;
        $vchamilo->virtual = true;
        $coursePath = self::getConfig('vchamilo', 'course_real_root').$separator.$vchamilo->slug;
        $homePath = self::getConfig('vchamilo', 'home_real_root').$separator.$vchamilo->slug;
        $archivePath = self::getConfig('vchamilo', 'archive_real_root').$separator.$vchamilo->slug;
        $uploadPath = self::getConfig('vchamilo', 'upload_real_root').$separator.$vchamilo->slug;

        // get the protocol free hostname
        Display::addFlash(
            Display::return_message("Copying {$templateDir}/data/courses => $coursePath")
        );

        copyDirTo(
            self::chopLastSlash($templateDir.'/data/courses'),
            self::chopLastSlash($coursePath),
            false
        );

        Display::addFlash(
            Display::return_message("Copying {$templateDir}/data/archive => $archivePath")
        );

        copyDirTo(
            self::chopLastSlash($templateDir.'/data/archive'),
            self::chopLastSlash($archivePath),
            false
        );

        Display::addFlash(
            Display::return_message("Copying {$templateDir}/data/home => $homePath")
        );

        copyDirTo(
            self::chopLastSlash($templateDir.'/data/home'),
            self::chopLastSlash($homePath),
            false
        );

        // Upload
        Display::addFlash(
            Display::return_message("Copying {$templateDir}/data/upload => $uploadPath")
        );

        copyDirTo(
            self::chopLastSlash($templateDir.'/data/upload/'),
            self::chopLastSlash($uploadPath),
            false
        );
    }

    /**
     * @param string $path
     *
     * @return mixed
     */
    public static function chopLastSlash($path)
    {
        return preg_replace('/\/$/', '', $path);
    }

    /**
     * @param string $str
     */
    public static function ctrace($str)
    {
        error_log($str);
        Display::addFlash(Display::return_message($str, 'normal', false));
    }

    /**
     * @param $file
     * @param $component
     * @param bool $return
     *
     * @return string
     */
    public static function requireJs($file, $component, $return = false)
    {
        global $_configuration, $htmlHeadXtra;

        if (preg_match('/^local_/', $component)) {
            $component = str_replace('local_', '', $component);
            $path = 'local/';
        } else {
            $path = 'plugin/';
        }

        // Secure the postslashing of the roots.
        $root_web = $_configuration['root_web'].'/';
        $root_web = preg_replace('#//$#', '/', $root_web);

        $str = '<script type="text/javascript" src="'.$root_web.$path.$component.'/js/'.$file.'"></script>'."\n";
        if ($return === 'head') {
            $htmlHeadXtra[] = $str;
        }

        if ($return) {
            return $str;
        }
        echo $str;
    }

    /**
     * @param $file
     * @param $component
     * @param bool $return
     *
     * @return string
     */
    public static function requireCss($file, $component, $return = false)
    {
        global $_configuration, $htmlHeadXtra;

        if (preg_match('/^local_/', $component)) {
            $component = str_replace('local_', '', $component);
            $path = 'local/';
        } else {
            $path = 'plugin/';
        }

        // Secure the postslashing of the roots.
        $root_web = $_configuration['root_web'].'/';
        $root_web = preg_replace('#//$#', '/', $root_web);

        $str = '<link rel="stylesheet" type="text/css" href="'.$root_web.$path.$component.'/'.$file.'.css" />'."\n";
        if ($return === 'head') {
            $htmlHeadXtra[] = $str;
        }
        if ($return) {
            return $str;
        }
        echo $str;
    }

    /**
     * @param string $url
     *
     * @return string
     */
    public static function getSlugFromUrl($url)
    {
        $slugify = new Slugify();
        $urlInfo = parse_url($url);
        if (isset($urlInfo['host'])) {
            $path = $urlInfo['path'] != '/' ? '_'.$urlInfo['path'] : '';

            return $slugify->slugify($urlInfo['host'].$path);
        }

        return false;
    }

    /**
     * Check if all settings are complete.
     */
    public static function checkSettings()
    {
        $enabled = self::getConfig('vchamilo', 'enable_virtualisation');

        if (empty($enabled)) {
            api_not_allowed(true, 'Plugin is not enabled');
        }

        global $virtualChamilo;
        if (!isset($virtualChamilo)) {
            api_not_allowed(
                true,
                'You have to edit the configuration.php. Please check the readme file.'
            );
        }

        $coursePath = self::getConfig('vchamilo', 'course_real_root');
        $homePath = self::getConfig('vchamilo', 'home_real_root');
        $archivePath = self::getConfig('vchamilo', 'archive_real_root');
        $uploadPath = self::getConfig('vchamilo', 'upload_real_root');
        $cmdSql = self::getConfig('vchamilo', 'cmd_mysql');
        $cmdMySql = self::getConfig('vchamilo', 'cmd_mysqldump');

        if (empty($coursePath) || empty($homePath) || empty($uploadPath) || empty($archivePath) || empty($cmdSql) || empty($cmdMySql)) {
            api_not_allowed(true, 'You have to complete all plugin settings.');
        }

        $separator = DIRECTORY_SEPARATOR;
        $templatePath = api_get_path(SYS_PATH).'plugin'.$separator.'vchamilo'.$separator.'templates';

        $paths = [
            $coursePath,
            $homePath,
            $archivePath,
            $uploadPath,
            $templatePath,
        ];

        foreach ($paths as $path) {
            $path = trim($path);
            if (is_dir($path)) {
                if (!is_writable($path)) {
                    Display::addFlash(
                        Display::return_message("Directory must have writable permissions: '$path'", 'warning')
                    );
                }
            } else {
                Display::addFlash(
                    Display::return_message("Directory doesn't exist: '$path'", 'warning')
                );
            }
        }
    }

    /**
     * @param object $instance
     *
     * @return bool|\Doctrine\DBAL\Connection
     */
    public static function getConnectionFromInstance($instance, $getManager = false)
    {
        $dbParams = [
            'driver' => 'pdo_mysql',
            'host' => $instance->db_host,
            'user' => $instance->db_user,
            'password' => $instance->db_password,
            //'dbname' => $instance->main_database,
            // Only relevant for pdo_sqlite, specifies the path to the SQLite database.
            //'path' => isset($_configuration['db_path']) ? $_configuration['db_path'] : '',
            // Only relevant for pdo_mysql, pdo_pgsql, and pdo_oci/oci8,
            //'port' => isset($_configuration['db_port']) ? $_configuration['db_port'] : '',
        ];

        if (!empty($instance->main_database)) {
            $dbParams['dbname'] = $instance->main_database;
        }

        try {
            $database = new \Database();
            $manager = $database->connect(
                $dbParams,
                api_get_configuration_value('root_sys'),
                api_get_configuration_value('root_sys'),
                false,
                true
            );

            if ($getManager) {
                return $manager;
            }

            return $manager->getConnection();
        } catch (Exception $e) {
            error_log($e->getMessage());
        }

        return false;
    }

    /**
     * @param $data
     */
    public static function addInstance($data)
    {
        if (isset($data->what)) {
            unset($data->what);
        }
        if (isset($data->submitbutton)) {
            unset($data->submitbutton);
        }
        if (isset($data->id)) {
            unset($data->id);
        }
        if (isset($data->vid)) {
            unset($data->vid);
        }
        if (isset($data->testconnection)) {
            unset($data->testconnection);
        }
        if (isset($data->testdatapath)) {
            unset($data->testdatapath);
        }

        $registeronly = $data->registeronly;
        unset($data->registeronly);
        $data->lastcron = 0;
        $data->lastcrongap = 0;
        $data->croncount = 0;

        if (isset($data->template) && !empty($data->template)) {
            $template = $data->template;
        } else {
            $template = '';
        }

        $mainDatabase = api_get_configuration_value('main_database');

        if ($mainDatabase == $data->main_database) {
            Display::addFlash(
                Display::return_message('You cannot use the same database as the chamilo master', 'error')
            );

            return;
        }

        $databaseName = $data->main_database;
        $data->main_database = '';
        $connection = self::getConnectionFromInstance($data);
        $data->main_database = $databaseName;
        if (!$connection) {
            Display::addFlash(
                Display::return_message(
                    'Cannot connect to database with params: '.print_r($data, 1),
                    'error'
                )
            );

            return;
        }

        $data->root_web = api_add_trailing_slash($data->root_web);

        $data->archive_url = api_add_trailing_slash($data->archive_url);
        $data->home_url = api_add_trailing_slash($data->home_url);
        $data->upload_url = api_add_trailing_slash($data->upload_url);
        $data->course_url = api_add_trailing_slash($data->course_url);

        if (substr($data->root_web, 0, 4) != 'http') {
            $data->root_web = api_get_protocol().'://'.$data->root_web;
        }

        self::ctrace('Registering: '.$data->root_web);
        $tablename = Database::get_main_table('vchamilo');
        $sql = "SELECT * FROM $tablename 
                WHERE root_web = '".Database::escape_string($data->root_web)."'";
        $result = Database::query($sql);

        if (Database::num_rows($result)) {
            Database::update($tablename, $data, ['root_web = ?' => $data->root_web]);
            $virtualInfo = Database::fetch_array($result);
            $slug = $virtualInfo['slug'];
        } else {
            $slug = $data->slug = self::getSlugFromUrl($data->root_web);
            if (empty($slug)) {
                Display::addFlash(
                    Display::return_message('Cannot create slug from url: '.$data->root_web, 'error')
                );

                return;
            }
            Database::insert($tablename, (array) $data);
        }

        if ($registeronly) {
            // Stop it now.
            self::ctrace('Registering only. out.');
            self::redirect(api_get_path(WEB_PLUGIN_PATH).'vchamilo/views/manage.php');
        }

        // or we continue with physical creation
        self::createDirsFromSlug($slug);

        if (!$template) {
            // Create empty database for install
            self::ctrace("Creating database");
            self::createDatabase($data);
        } else {
            // Deploy template database
            self::ctrace("Creating databases from template '$template'");
            self::createDatabase($data);
            self::ctrace("Loading data template '$template'");
            self::loadDbTemplate($data, $template);
            self::ctrace("Coying files from template '$template'");
            self::loadFilesFromTemplate($data, $template);
        }

        // pluging in site name institution
        $settingstable = $data->main_database.'.settings_current';
        $accessurltable = $data->main_database.'.access_url';

        $sitename = Database::escape_string($data->sitename);
        $institution = Database::escape_string($data->institution);

        $sqls[] = "UPDATE {$settingstable} SET selected_value = '{$sitename}' 
                   WHERE variable = 'siteName' AND category = 'Platform' ";

        $sqls[] = "UPDATE {$settingstable} SET selected_value = '{$institution}' 
                   WHERE variable = 'institution' AND category = 'Platform' ";

        $sqls[] = "UPDATE {$accessurltable} SET url = '{$data->root_web}' WHERE id = '1' ";

        foreach ($sqls as $sql) {
            Database::query($sql);
        }

        self::ctrace("Finished");
    }

    /**
     * @param stdClass $data
     * @param string   $fromVersion
     */
    public static function importInstance($data, $fromVersion)
    {
        if (isset($data->what)) {
            unset($data->what);
        }
        if (isset($data->submitbutton)) {
            unset($data->submitbutton);
        }
        if (isset($data->id)) {
            unset($data->id);
        }
        if (isset($data->vid)) {
            unset($data->vid);
        }
        if (isset($data->testconnection)) {
            unset($data->testconnection);
        }
        if (isset($data->testdatapath)) {
            unset($data->testdatapath);
        }

        $fromCoursePath = $data->course_path;
        $fromHomePath = $data->home_path;
        $fromUploadPath = $data->upload_path;

        unset($data->course_path);
        unset($data->home_path);
        unset($data->upload_path);

        $newDatabase = clone $data;
        $newDatabase->main_database = $newDatabase->import_to_main_database;
        $newDatabase->db_user = $newDatabase->import_to_db_user;
        $newDatabase->db_password = $newDatabase->import_to_db_password;
        $newDatabase->db_host = $newDatabase->import_to_db_host;

        unset($newDatabase->import_to_main_database);
        unset($newDatabase->import_to_db_user);
        unset($newDatabase->import_to_db_password);
        unset($newDatabase->import_to_db_host);

        unset($data->import_to_main_database);
        unset($data->import_to_db_user);
        unset($data->import_to_db_password);
        unset($data->import_to_db_host);

        $data->lastcron = 0;
        $data->lastcrongap = 0;
        $data->croncount = 0;

        $mainDatabase = api_get_configuration_value('main_database');

        if ($mainDatabase == $data->main_database) {
            Display::addFlash(
                Display::return_message('You cannot use the same database as the chamilo master', 'error')
            );

            return false;
        }

        self::ctrace('Registering: '.$data->root_web);

        $table = Database::get_main_table('vchamilo');
        $sql = "SELECT * FROM $table 
                WHERE root_web = '".Database::escape_string($data->root_web)."'";
        $result = Database::query($sql);
        $id = null;
        if (Database::num_rows($result)) {
            Display::addFlash(
                Display::return_message('Instance was already added: '.$data->root_web, 'error')
            );

            return false;
        } else {
            /** @var EntityManager $em */
            $em = self::getConnectionFromInstance($data, true);
            if ($em) {
                $connection = $em->getConnection();
                $statement = $connection->query('SELECT * FROM settings_current');
                $settings = $statement->fetchAll();
                $settings = array_column(
                    $settings,
                    'selected_value',
                    'variable'
                );
                $institution = $settings['Institution'];
                $siteName = $settings['siteName'];
                $newDatabase->sitename = $siteName;
                $newDatabase->institution = $institution;
                $slug = $newDatabase->slug = $data->slug = self::getSlugFromUrl($data->root_web);
                $id = Database::insert($table, (array) $newDatabase);
            }
        }

        if (!$id) {
            // Show data detail to help debug
            //var_dump($data);
            throw new Exception('New/Imported instance was not registered - edit '.__FILE__.' on line '.__LINE__.'to var_dump');
        }

        if (empty($slug)) {
            throw new Exception('Slug is empty');
        }

        self::createDirsFromSlug($slug);
        $databaseCreated = self::createDatabase($newDatabase);
        if (!$databaseCreated) {
            Display::addFlash(
                Display::return_message('Error while creating a DB', 'error')
            );

            return false;
        }

        $coursePath = self::getConfig('vchamilo', 'course_real_root').'/'.$slug;
        $homePath = self::getConfig('vchamilo', 'home_real_root').'/'.$slug;
        $uploadPath = self::getConfig('vchamilo', 'upload_real_root').'/'.$slug;

        $dumpFile = api_get_path(SYS_ARCHIVE_PATH).uniqid($data->main_database.'_dump_', true).'.sql';
        self::ctrace('Create backup from "'.$data->main_database.'" here: '.$dumpFile.' ');
        self::backupDatabase($data, $dumpFile);

        $sqlcmd = self::getDatabaseDumpCmd($newDatabase);
        $sqlcmd = str_replace('%DATABASE%', $newDatabase->main_database, $sqlcmd);
        $import = $sqlcmd.$dumpFile;

        // Execute the command.
        if (!defined('CLI_SCRIPT')) {
            putenv('LANG=en_US.utf-8');
        }

        // ensure utf8 is correctly handled by php exec()
        // @see http://stackoverflow.com/questions/10028925/call-a-program-via-shell-exec-with-utf-8-text-input
        $result = exec($import, $output, $return);

        self::ctrace('Restore backup here "'.$newDatabase->main_database.'" : <br />'.$import.' ');
        self::ctrace($result);

        $command = new \Chash\Command\Installation\UpgradeDatabaseCommand();
        // Creates the helper set
        $helperSet = \Doctrine\ORM\Tools\Console\ConsoleRunner::createHelperSet($em);

        $helpers = [
            'configuration' => new Chash\Helpers\ConfigurationHelper(),
            'dialog' => new \Symfony\Component\Console\Helper\QuestionHelper(),
        ];

        foreach ($helpers as $name => $helper) {
            $helperSet->set($helper, $name);
        }

        $command->setHelperSet($helperSet);

        $tmpFile = tmpfile();
        $outputStream = new \Symfony\Component\Console\Output\BufferedOutput($tmpFile);

        $arguments = [
            'from-version' => $fromVersion, // @todo change value
            'to-version' => '1.11.x',
            'host' => $newDatabase->db_host,
            'username' => $newDatabase->db_user,
            'password' => $newDatabase->db_password,
            'db_name' => $newDatabase->main_database,
            'root_sys' => api_get_configuration_value('root_sys'),
        ];

        $input = new ArrayInput($arguments);
        $command->run($input, $outputStream);

        error_log($outputStream->fetch());

        if (file_exists($dumpFile)) {
            unlink($dumpFile);
        }

        // Course
        self::ctrace("Copy from '$fromCoursePath' to backup '$coursePath' ");
        copyDirTo(
            self::chopLastSlash($fromCoursePath),
            self::chopLastSlash($coursePath),
            false
        );

        // Home
        self::ctrace("Copy from '$fromHomePath' to backup '$homePath' ");
        copyDirTo(
            self::chopLastSlash($fromHomePath),
            self::chopLastSlash($homePath),
            false
        );

        // Upload
        self::ctrace("Copy from '$fromUploadPath' to backup '$uploadPath' ");
        copyDirTo(
            self::chopLastSlash($fromUploadPath),
            self::chopLastSlash($uploadPath),
            false
        );

        self::ctrace("Finished");
    }

    /**
     * @param string $slug
     *
     * @return string
     */
    public static function createDirsFromSlug($slug)
    {
        // We continue with physical creation

        // Create course directory for operations.
        // this is very important here (DO NOT USE api_get_path() !!) because storage may be remotely located
        $absAlternateCourse = self::getConfig('vchamilo', 'course_real_root');
        $courseDir = $absAlternateCourse.'/'.$slug;
        $mode = api_get_permissions_for_new_directories();
        if (!is_dir($courseDir)) {
            self::ctrace("Creating physical course dir in $courseDir");
            mkdir($courseDir, $mode, true);
            // initiate default index
            $indexFile = $courseDir.'/index.html';
            if ($indexFile) {
                file_put_contents($indexFile, self::getDefaultCourseIndexFragment());
            }

            $htaccessFile = $courseDir.'/.htaccess';
            if ($htaccessFile) {
                file_put_contents($htaccessFile, self::getHtaccessFragment($slug));
            }
        }

        $absAlternateHome = self::getConfig('vchamilo', 'home_real_root');
        $absAlternateArchive = self::getConfig('vchamilo', 'archive_real_root');
        $absAlternateUpload = self::getConfig('vchamilo', 'upload_real_root');

        // absalternatehome is a vchamilo config setting that tells where the
        // real physical storage for home pages are.
        $homeDir = $absAlternateHome.'/'.$slug;
        $archiveDir = $absAlternateArchive.'/'.$slug;
        $uploadDir = $absAlternateUpload.'/'.$slug;

        $dirs = [
            $homeDir,
            $archiveDir,
            $uploadDir,
        ];

        foreach ($dirs as $dir) {
            self::ctrace("Making dir as $dir");

            if (!is_dir($dir)) {
                if (!mkdir($dir, $mode, true)) {
                    self::ctrace("Error creating dir $dir \n");
                }
            }
        }
    }

    /**
     * @param $id
     *
     * @return array|mixed
     */
    public static function getInstance($id)
    {
        $vhost = new stdClass();
        if ($id) {
            $id = (int) $id;
            $sql = "SELECT * FROM vchamilo WHERE id = $id";
            $result = Database::query($sql);
            $vhost = (object) Database::fetch_array($result, 'ASSOC');
        }

        return $vhost;
    }

    /**
     * @param stdClass $instance
     *
     * @return bool|string returns the original version of the app
     */
    public static function canBeUpgraded($instance)
    {
        $connection = self::getConnectionFromInstance($instance);
        if ($connection) {
            $sql = 'SELECT * FROM settings_current WHERE variable = "chamilo_database_version"';
            $statement = $connection->query($sql);
            $settings = $statement->fetchAll();
            $settings = array_column($settings, 'selected_value', 'variable');
            $version = $settings['chamilo_database_version'];
            $versionParts = explode('.', $version);
            $version = implode('.', [$versionParts[0], $versionParts[1], '0']);

            $currentVersion = api_get_setting('chamilo_database_version');
            $versionParts = explode('.', $currentVersion);
            $currentVersion = implode(
                '.',
                [$versionParts[0], $versionParts[1], '0']
            );

            if (version_compare($version, $currentVersion, '<')) {
                return $version;
            }
        }

        return false;
    }

    /**
     * @return array
     */
    public static function getEncryptList()
    {
        $encryptList = [
            'bcrypt',
            'sha1',
            'md5',
            'none',
        ];

        return array_combine($encryptList, $encryptList);
    }
}
