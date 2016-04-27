<?php

use Cocur\Slugify\Slugify;
use Symfony\Component\Finder\Finder;
require_once 'lib/bootlib.php';
require_once 'lib/vchamilo_plugin.class.php';

function vchamilo_hook_configuration(&$_configuration)
{
    global $VCHAMILO;

    if (defined('CLI_SCRIPT') && !defined('CLI_VCHAMILO_OVERRIDE')) {
        return;
    }

    // provides an effective value for the virtual root_web    based on domain analysis
    vchamilo_get_hostname($_configuration);

    // We are on physical chamilo. Let original config play
    $virtualChamiloWebRoot = $_configuration['vchamilo_web_root'].'/';

    if ($_configuration['root_web'] == $virtualChamiloWebRoot) {
        $VCHAMILO = [];

        return;
    }

    // pre hook to chamilo main table and get alternate configuration.
    // sure Database object is not set up. Soo use bootstrap connection
    /** @var \Doctrine\DBAL\Connection $connection */
    $connection = vchamilo_boot_connection($_configuration);

    $table = 'vchamilo';
    $query = "SELECT * FROM $table WHERE root_web = '$virtualChamiloWebRoot'";
    $result = $connection->executeQuery($query);

    $excludes = array('id', 'name');

    $query = "SELECT * FROM settings_current WHERE subkey = 'vchamilo'";
    $virtualSettings = $connection->executeQuery($query);
    $virtualSettings = $virtualSettings->fetchAll();

    $homePath = '';
    $coursePath = '';
    $archivePath = '';

    foreach ($virtualSettings as $setting) {
        switch ($setting['variable']) {
            case 'vchamilo_home_real_root':
                $homePath = $setting['selected_value'];
                break;
            case 'vchamilo_course_real_root':
                $coursePath = $setting['selected_value'];
                break;
            case 'vchamilo_archive_real_root':
                $archivePath = $setting['selected_value'];
                break;
        }
    }

    if (empty($homePath) || empty($coursePath) || empty($archivePath)) {
        echo 'Configure correctly the vchamilo plugin';
        exit;
    }

    if ($result->rowCount()) {
        $data = $result->fetch();
        // Only load if is visible
        if ($data['visible']) {
            foreach ($data as $key => $value) {
                if (!in_array($key, $excludes)) {
                    $_configuration[$key] = $value;
                }
                $_configuration['virtual'] = $data['root_web'].'/';
            }

            $data['SYS_ARCHIVE_PATH'] = $archivePath.'/'.$data['slug'];
            $data['SYS_HOME_PATH'] = $homePath.'/'.$data['slug'];
            $data['SYS_COURSE_PATH'] = $coursePath.'/'.$data['slug'];

            $VCHAMILO = $data;
        }
    } else {
        //die ("VChamilo : No configuration for this host. May be faked.");
        die ("VChamilo : Could not fetch virtual chamilo configuration");
    }
}

/**
 * @param array $_configuration
 */
function vchamilo_get_hostname(&$_configuration)
{
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
        $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' || !empty($_configuration['force_https_forwarded_proto'])
    ) {
        $protocol = 'https';
    } else {
        $protocol = 'http';
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

    $_configuration['vchamilo_web_root'] = "{$protocol}://".@$_SERVER['HTTP_HOST'];
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
* provides a side connection to a vchamilo database
* @param array $vchamilo
* @return a connection
*/
function vchamilo_boot_connection(&$_configuration)
{
    $dbParams = array(
        'driver' => 'pdo_mysql',
        'host' => $_configuration['db_host'],
        'user' => $_configuration['db_user'],
        'password' => $_configuration['db_password'],
        'dbname' => isset($_configuration['main_database']) ? $_configuration['main_database'] : '',
        // Only relevant for pdo_sqlite, specifies the path to the SQLite database.
        'path' => isset($_configuration['db_path']) ? $_configuration['db_path'] : '',
        // Only relevant for pdo_mysql, pdo_pgsql, and pdo_oci/oci8,
        'port' => isset($_configuration['db_port']) ? $_configuration['db_port'] : '',
    );
    try {
        $database = new \Database();
        $connection = $database->connect($dbParams, $_configuration['root_sys'], $_configuration['root_sys'], true);
    } catch (Exception $e) {
        echo('Side connection failure with '.$_configuration['db_host'].', '.$_configuration['db_user'].', ******** ');
        die();
    }

    return $connection;
}

function vchamilo_redirect($url) {
    if (preg_match('#https?://#', $url)) {
        header('location: '.$url);
    } else {
        header('location: ' . api_get_path(WEB_PATH).$url);
    }
    exit;
}

function vchamilo_get_htaccess_fragment($course_folder)
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
}

function vchamilo_get_default_course_index_fragment() {
    return "<html><head></head><body></body></html>";
}

function vchamilo_template_exists($template)
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
* drop a vchamilo instance databases using the physical connection
* @param object $vchamilo
* @param handle $side_cnx
* return an array of errors or false if ok
*/
function vchamilo_drop_databases(&$vchamilo)
{
    global $plugininstance;

    if (is_array($vchamilo)) $vchamilo = (object)$vchamilo;

    if (empty($vchamilo->main_database)) {
        Display::addFlash(Display::return_message('No database found'));
        return;
    }

    // Drop databases you need to drop
    $sqls = array(" DROP DATABASE `{$vchamilo->main_database}` ");

    Display::addFlash(Display::return_message("Dropping database: ".$vchamilo->main_database));

    foreach ($sqls as $sql){
        $res = Database::query($sql);
        if (!$res) {
            Display::addFlash(Display::return_message($plugininstance->get_lang('couldnotdropdb')));
        }
    }

    return false;
}

/**
 * Create all needed databases.
 * @uses    $CFG            The global configuration.
 * @param    $vmoodledata    object        All the Host_form data.
 * @param    $outputfile        array        The variables to inject in setup template SQL.
 * @return    bool    If TRUE, loading database from template was sucessful, otherwise FALSE.
 */
function vchamilo_create_databases($vchamilo)
{
    // availability of SQL commands
    $createstatement = 'CREATE DATABASE %DATABASE% DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ';

    $dbs = array($vchamilo->main_database);

    foreach($dbs as $adb){
        Display::addFlash(Display::return_message("Creating DB $adb"));
        $sql = str_replace('%DATABASE%', $adb, $createstatement);
        Database::query($sql);
        /*if(!$DB->execute_sql($sql)){
            print_error('noexecutionfor','block_vmoodle', $sql);
            return false;
        }*/

    }
    return true;
}

/**
* get a proper SQLdump command
* @param object $vmoodledata the complete new host information
* @return string the shell command
*/
function vchamilo_get_database_dump_cmd($vchamilodata)
{
    $pgm = vchamilo_get_config('vchamilo', 'mysql_cmd');

    if (!$pgm) {
        $pgm = '/usr/bin/mysql';
        Display::addFlash(Display::return_message("Using default database command $pgm "));
    }

    $phppgm = str_replace("\\", '/', $pgm);
    $phppgm = str_replace("\"", '', $phppgm);
    $pgm = str_replace("/", DIRECTORY_SEPARATOR, $pgm);

    if (!is_executable($phppgm)){
        print_error('databasecommanddoesnotmatchanexecutablefile');
        return false;
    }

    // Retrieves the host configuration (more secure).
    $vchamilodata = vchamilo_make_this();
    if (strstr($vchamilodata->db_host, ':') !== false) {
        list($vchamilodata->db_host, $vchamilodata->db_port) = explode(':', $vchamilodata->db_host);
    }

    // Password.
    if (!empty($vchamilodata->db_password)) {
        $vchamilodata->db_password = '-p'.escapeshellarg($vchamilodata->db_password).' ';
    }

    // Making the command line (see 'vconfig.php' file for defining the right paths).
    $sqlcmd = $pgm.' -h'.$vchamilodata->db_host.(isset($vchamilodata->db_port) ? ' -P'.$vchamilodata->db_port.' ' : ' ' );
    $sqlcmd .= '-u'.$vchamilodata->db_user.' '.$vchamilodata->db_password;
    $sqlcmd .= '%DATABASE% < ';

    return $sqlcmd;
}

function vchamilo_load_db_template($vchamilo, $template)
{
    global $_configuration;

    // Make template directory (files and SQL).
    $separator = DIRECTORY_SEPARATOR;
    $templatefoldername = 'plugin'.$separator.'vchamilo'.$separator.'templates';
    $absolute_datadir = $_configuration['root_sys'].$templatefoldername.$separator.$template.$separator.'dump.sql';

    if (!$sqlcmd = vchamilo_get_database_dump_cmd($vchamilo)) {
        return false;
    }

    $sqlcmd = str_replace('%DATABASE%', $vchamilo->main_database, $sqlcmd);

    // Make final commands to execute, depending on the database type.
    $import = $sqlcmd.$absolute_datadir;

    // Execute the command.
    Display::addFlash(Display::return_message("load_database_from_dump : executing feeding sql as \n $import "));

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
* load a bulk sql in database that is given through a vchamilo configuration record.
* @param object $vchamilo
* @param string $bulfile a bulk file of queries to process on the database
* @param handle $cnx
* @param array $vars an array of vars to inject in the bulk file before processing
*/
function vchamilo_execute_db_sql(&$vchamilo, $bulkfile, $cnx = null, $vars = null, $filter = null)
{
   if (file_exists($bulkfile)) {
        $erroritem = new stdClass();
        $erroritem->message = "vchamilo_load_db_template : Bulk file $bulkfile not found";
        $erroritems[] = $erroritem;

        return $erroritem;
    }

    $local_cnx = 0;
    if (is_null($cnx)){
        $cnx = vchamilo_make_connection($vchamilo, true);
        $local_cnx = 1;
    }

    /// get dump file
    $sql = file($bulkfile);

    // converts into an array of text lines
    $dumpfile = implode('', $sql);
    if ($filter){
        foreach($filter as $from => $to){
            $dumpfile = mb_ereg_replace(preg_quote($from), $to, $dumpfile);
        }
    }
    // insert any external vars
    if (!empty($vars)){
        foreach($vars as $key => $value){
            // for debug : echo "$key => $value";
            $dumpfile = str_replace("<%%$key%%>", $value, $dumpfile);
        }
    }
    $sql = explode ("\n", $dumpfile);
    // cleanup unuseful things
    $sql = preg_replace("/^--.*/", "", $sql);
    $sql = preg_replace("/^\/\*.*/", "", $sql);
    $dumpfile = implode("\n", $sql);

    /// split into single queries
    $dumpfile = str_replace("\r\n", "\n", $dumpfile); // translates to Unix LF
    $queries = preg_split("/;\n/", $dumpfile);
    /// feed queries in database
    $i = 0;
    $j = 0;
    $l = 0;
    if (!empty($queries)){
        foreach($queries as $query){
            $query = trim($query); // get rid of trailing spaces and returns
            if ($query == '') continue; // avoid empty queries
            $query = mb_convert_encoding($query, 'iso-8859-1', 'auto');
            if (!$res = vchamilo_execute_query($vchamilo, $query, $cnx)){
                $erroritem = new stdClass();
                $erroritem->message = "vchamilo_load_db_template : Load error on query $l";
                $erroritem->content = $query;
                $erroritems[] = $erroritem;
                $j++;
            } else {
                $i++;
            }
            $l++;
        }
    }
    echo "loaded : $i queries succeeded, $j queries failed<br/>";

    if ($local_cnx){
        vchamilo_close_connection($vchamilo, $cnx);
    }

    if (!empty($erroritems)){
        return $erroritems;
    }

    return false;
}

/**
 * Dumps a SQL database for having a snapshot.
 * @param        $vchamilo    object        The Vchamilo object.
 * @param        $outputfilerad    string        The output SQL file radical.
 * @return        bool    If TRUE, dumping database was a success, otherwise FALSE.
 */
function vchamilo_dump_databases($vchamilo, $outputfilerad)
{
    // Separating host and port, if sticked.
    if (strstr($vchamilo->db_host, ':') !== false){
        list($host, $port) = split(':', $vchamilo->db_host);
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
    $cmds = array();
    //if ($CFG->ostype == 'WINDOWS') {
    if (false) {
        $cmd_main = "-h{$host} -P{$port} -u{$vchamilo->db_user} {$pass} {$vchamilo->main_database}";
        $cmds[] = $cmd_main . ' > ' . $outputfilerad;
    } else {
        $cmd_main = "-h{$host} -P{$port} -u{$vchamilo->db_user} {$pass} {$vchamilo->main_database}";
        $cmds[] = $cmd_main . ' > ' . escapeshellarg($outputfilerad);
    }

    $mysqldumpcmd = vchamilo_get_config('vchamilo', 'cmd_mysqldump', true);

    $pgm = !empty($mysqldumpcmd) ? stripslashes($mysqldumpcmd) : false ;

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
        foreach ($cmds as $cmd){

            // Final command.
            $cmd = $pgm.' '.$cmd;
            // Prints log messages in the page and in 'cmd.log'.
            /*if ($LOG = fopen(dirname($outputfilerad).'/cmd.log', 'a')){
                fwrite($LOG, $cmd."\n");
            }*/

            // Executes the SQL command.
            exec($cmd, $execoutput, $returnvalue);
            /*if ($LOG){
                foreach($execoutput as $execline) fwrite($LOG, $execline."\n");
                fwrite($LOG, $returnvalue."\n");
                fclose($LOG);
            }*/
        }
    }

    // End with success.
    return 1;
}

/**
* read manifest values in vchamilo template.
*/
function vchamilo_get_vmanifest($version)
{
    $file = api_get_path(SYS_PATH).'/plugin/vchamilo/templates/'.$version.'/manifest.php';
    if (file_exists($file)) {

        include($file);

        $manifest = new stdClass();
        $manifest->templatewwwroot = $templatewwwroot;
        //    $manifest->templatevdbprefix = $templatevdbprefix;
        //    $manifest->coursefolder = $coursefolder;

        return $manifest;
    }

    return false;
}

/**
* make a fake vchamilo that represents the current host
*/
function vchamilo_make_this()
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
 * @return        array        The available templates, or EMPTY array.
 */
function vchamilo_get_available_templates()
{
    global $_configuration;
    global $plugininstance;

    $separator = DIRECTORY_SEPARATOR;

    $templatefoldername = 'plugin'.$separator.'vchamilo'.$separator.'templates';
    $tempDir = $_configuration['root_sys'].$templatefoldername;

    // Scans the templates.
    if (!is_dir($tempDir)) {
        mkdir($tempDir, 0777, true);
    }

    $finder = new \Symfony\Component\Finder\Finder();
    $dirs = $finder->in($tempDir)->depth('== 0');

    // Retrieves template(s) name(s). Should be hostnames.
    $templates = [];
    /*if ($addEmptyTemplate) {
        $templates = array('' => $plugininstance->get_lang('emptysite'));
    }*/

    $template = vchamilo_get_config('vchamilo', 'default_template');

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

function vchamilo_print_error($errortrace, $return = false)
{
    $str = '';
    if (!empty($errortrace)){
        $str .= '<div class="vchamilo-errors" style="border:1px solid #a0a0a0;background-color:#ffa0a0;padding:5px;font-size:10px">';
        $str .= '<pre>';
        foreach($errortrace as $error){
            $str .= $error->message.'<br/>';
            $str .= @$error->content;
        }
        $str .= '</pre>';
        $str .= '</div>';
    }

    if ($return) return $str;
    Display::addFlash(Display::return_message($str));
}

/**
* this function set will map standard moodle API calls to chamilo
* internal primitives. This avoids too many changes to do in imported
* code
*
*/
function vchamilo_get_config($module, $key, $isplugin = true)
{
    if ($isplugin) {
        $key = $module.'_'.$key;
    }

    $params = array('variable = ? AND subkey = ?' => [$key, $module]);
    $result = api_get_settings_params_simple($params);
    if ($result) {
        return $result['selected_value'];
    }

    return false;
}

function vchamilo_load_files_from_template($vchamilo, $template)
{
    global $_configuration;

    // Make template directory (files and SQL).
    $separator = DIRECTORY_SEPARATOR;
    $absolute_template_datadir = $_configuration['root_sys'].'plugin'.$separator.'vchamilo'.$separator.'templates'.$separator.$template;

    $vchamilo->virtual = true;

    // Get Vchamilo known record.
    $vcoursepath = api_get_path(SYS_COURSE_PATH, (array)$vchamilo);
    $vhomepath = api_get_path(SYS_HOME_PATH, (array)$vchamilo);
    $varchivepath = api_get_path(SYS_ARCHIVE_PATH, (array)$vchamilo);

    // Rename some dirs top match instance requirements
    $manifest = vchamilo_get_vmanifest($template);
    if ($manifest) {

        // get the protocol free hostname
        Display::addFlash(
            Display::return_message("Copying {$absolute_template_datadir}/data/courses => $vcoursepath")
        );
        Display::addFlash(
            Display::return_message("Copying {$absolute_template_datadir}/data/archive => $varchivepath")
        );
        Display::addFlash(
            Display::return_message("Copying {$absolute_template_datadir}/data/home => $vhomepath")
        );

        copyDirTo(
            chop_last_slash($absolute_template_datadir.'/data/courses'),
            chop_last_slash($vcoursepath),
            false
        );
        copyDirTo(
            chop_last_slash($absolute_template_datadir.'/data/archive'),
            chop_last_slash($varchivepath),
            false
        );
        copyDirTo(
            chop_last_slash($absolute_template_datadir.'/data/home'),
            chop_last_slash($vhomepath),
            false
        );
    }
}

function chop_last_slash($path)
{
    return preg_replace('/\/$/', '', $path);
}

// from moot
define('PARAM_BOOL', 1);
define('PARAM_INT', 2);
define('PARAM_TEXT', 3);
define('PARAM_RAW', 4);

/**
 * this function set will map standard moodle API calls to chamilo
 * internal primitives. This avoids too many changes to do in imported
 * code
 *
 */
function get_config($module, $key = false, $isplugin = true) {
    global $_configuration, $DB;
    static $static_settings;

    if (!isset($static_settings)) {
        include_once $_configuration['root_sys'].'local/ent_installer/static_settings.php';
    }

    if ($isplugin){
        $configkey = $module.'_'.$key;
    } else {
        $configkey = $key;
    }

    if ($module == 'ent_installer') {
        $dyna_setting = $DB->get_field(TABLE_MAIN_SETTINGS_CURRENT, 'selected_value', array('subkey' => 'ent_installer', 'variable' => $configkey));
        if (!is_null($dyna_setting)) {
            return $dyna_setting;
        }

        if (empty($config)) {
            Display::addFlash(Display::return_message("Wrap to static setting $module,$configkey "));
            if (array_key_exists($key, $static_settings)){
                return $static_settings[$key];
            }
        }
    } else {
        if (!$module) {
            return $DB->get_field(TABLE_MAIN_SETTINGS_CURRENT, 'selected_value', array('variable' => $configkey));
        }
        if ($key) {
            return $DB->get_field(TABLE_MAIN_SETTINGS_CURRENT, 'selected_value', array('variable' => $configkey, 'subkey' => $module));
        } else {
            // Get all config from a subkey as an object
            $configs = $DB->get_records(TABLE_MAIN_SETTINGS_CURRENT, array('subkey' => $module));
            if (!empty($configs)) {
                $config = new StdClass;
                foreach ($configs as $cf) {
                    $key = str_replace($module.'_', '', $cf->variable);
                    $config->$key = $cf->selected_value;
                }
                return $config;
            }
            return null;
        }
    }
}

function set_config($key, $value, $module, $isplugin = false)
{
    if ($isplugin) {
        $key = $module.'_'.$key;
    }

    if ($isplugin) {
        // ensure setting is actually in database
        api_update_setting($value, $key, $module);
    } else {
        api_update_setting($value, $module, $key);
    }
}

/**
 * gets a string from a component
 *
 */
function get_string($key, $component = 'local_ent_installer', $a = '') {
    global $_configuration;
    static $strings;
    static $fallbackstrings;

    if ($component == 'local_ent_installer') {
        $fallbackpath = $_configuration['root_sys'].'local/ent_installer/lang/english/local_ent_installer.php';

        if (!isset($strings)) {
            $lang = api_get_language_from_type('platform_lang');
            if (empty($lang)) $lang = 'english';
            $path = $_configuration['root_sys'].'local/ent_installer/lang/'.$lang.'/local_ent_installer.php';

            if (!file_exists($path)) {
                if (!file_exists($path)) {
                    print_error('missinglang', null);
                    die;
                }

                if (!isset($fallbackstrings)) {
                    include $fallbackpath;
                    $fallbackstrings = $string;
                }
            }

            include $path;
            $strings = $string;
        }

        if (!array_key_exists($key, $strings)) {
            if (!isset($fallbackstrings)) {
                include $fallbackpath;
                $fallbackstrings = $string;
            }
            if (!array_key_exists($key, $fallbackstrings)) {
                return "[[$key]]";
            }
            if (is_scalar($a)) {
                return str_replace('{$a}', $a, $fallbackstrings[$key]);
            }
            if (is_array($a)) {
                $a = (object)$a;
            }
            if (is_object($a)) {
                return replace_string_vars($a, $fallbackstrings[$key]);
            }
            debugging('String insertion not supported', 1);
            die;
        }

        if (is_scalar($a)) {
            return str_replace('{$a}', $a, $strings[$key]);
        }
        if (is_array($a)){
            $a = (object)$a;
        }
        if (is_object($a)){
            return replace_string_vars($a, $strings[$key]);
        }
        debugging('String insertion not supported', 1);
        die;
    } else {
        return get_lang($key);
    }
}

function replace_string_vars($a, $str){
    preg_match_all('/{\$a-\>(.+?)}/', $str, $matches);
    if (!empty($matches[1])){
        foreach($matches[1] as $replacekey){
            $str = str_replace('{$a->'.$replacekey.'}', $a->$replacekey, $str);
        }
    }
    return $str;
}

function print_error($key, $component = '', $passthru = false, $extrainfo = ''){
    global $debuglevel;
    global $debugdisplay;

    if ($component === null){
        $str = $key;
    } else {
        $str = get_string($string, $component);
    }
    ctrace('ERROR: '. $str);
    if (!empty($extrainfo)){
        ctrace('Extra: '. $extrainfo);
    }
    if ($debugdisplay >= 3){
        debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    }
    if (!$passthru) die;
}

function debugging($message, $level) {
    global $debuglevel;
    global $debugdisplay;

    if ($level <= $debuglevel) {
        ctrace('DEBUG: '.$message);
        if ($debugdisplay >= 3){
            debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        }
    }
}

/**
 * Wrap moodle to chamilo side
 *
 */
function mtrace($message){
    ctrace($message);
}

function ctrace($str) {
    if (!defined('CLI_SCRIPT')) echo "<pre>";
    Display::addFlash(Display::return_message($str));
    if (!defined('CLI_SCRIPT')) echo "</pre>";
}

/**
 * Sets a platform configuration setting to a given value, creating it if necessary
 * @param string    The value we want to record
 * @param string    The variable name we want to insert
 * @param string    The subkey for the variable we want to insert
 * @param string    The type for the variable we want to insert
 * @param string    The category for the variable we want to insert
 * @param string    The title
 * @param string    The comment
 * @param string    The scope
 * @param string    The subkey text
 * @param int       The access_url for which this parameter is valid
 * @param int       The changeability of this setting for non-master urls
 * @return boolean  true on success, false on failure
 */
function api_update_setting(
    $val,
    $var,
    $sk = null,
    $type = 'textfield',
    $c = null,
    $title = '',
    $com = '',
    $sc = null,
    $skt = null,
    $a = 1,
    $v = 0
) {
    global $_setting;

    if (empty($var) || !isset($val)) {
        return false;
    }

    $t_settings = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
    $var = Database::escape_string($var);
    $val = Database::escape_string($val);
    $a = (int) $a;

    if (empty($a)) { $a = 1; }

    // Check if this variable doesn't exist already
    $select = "SELECT id FROM $t_settings WHERE variable = '$var' ";

    if (!empty($sk)) {
        $sk = Database::escape_string($sk);
        $select .= " AND subkey = '$sk'";
    }

    if ($a > 1) {
        $select .= " AND access_url = $a";
    } else {
        $select .= " AND access_url = 1 ";
    }

    $res = Database::query($select);
    if (Database::num_rows($res) > 0) { // Found item for this access_url.
        $row = Database::fetch_array($res);
        // update value
        $update['selected_value'] = $val;
        Database::update($t_settings, $update, array('id = ?' => $row['id']));
        return $row['id'];

        // update in memory setting value
        $_setting[$var][$sk] = $val;
    }

    // Item not found for this access_url, we have to check if the whole thing is missing
    // (in which case we ignore the insert) or if there *is* a record but just for access_url = 1
    $insert = "INSERT INTO $t_settings " .
        "(variable,selected_value," .
        "type,category," .
        "subkey,title," .
        "comment,scope," .
        "subkeytext,access_url,access_url_changeable)" .
        " VALUES ('$var','$val',";
    if (isset($type)) {
        $type = Database::escape_string($type);
        $insert .= "'$type',";
    } else {
        $insert .= "NULL,";
    }
    if (isset($c)) { // Category
        $c = Database::escape_string($c);
        $insert .= "'$c',";
    } else {
        $insert .= "NULL,";
    }
    if (isset($sk)) { // Subkey
        $sk = Database::escape_string($sk);
        $insert .= "'$sk',";
    } else {
        $insert .= "NULL,";
    }
    if (isset($title)) { // Title
        $title = Database::escape_string($title);
        $insert .= "'$title',";
    } else {
        $insert .= "NULL,";
    }
    if (isset($com)) { // Comment
        $com = Database::escape_string($com);
        $insert .= "'$com',";
    } else {
        $insert .= "NULL,";
    }
    if (isset($sc)) { // Scope
        $sc = Database::escape_string($sc);
        $insert .= "'$sc',";
    } else {
        $insert .= "NULL,";
    }
    if (isset($skt)) { // Subkey text
        $skt = Database::escape_string($skt);
        $insert .= "'$skt',";
    } else {
        $insert .= "NULL,";
    }
    $insert .= "$a,$v)";
    $res = Database::query($insert);

    // update in memory setting value
    $_setting[$var][$sk] = $value;

    return $res;
}

/**
 * converts a timestamp to sql tms
 * @param lint $time a unix timestamp
 */
function make_tms($time) {
    $tms = date('Y-m-d H:i:s', $time);

    return $tms;
}

/**
 * Makes sure the data is using valid utf8, invalid characters are discarded.
 *
 * Note: this function is not intended for full objects with methods and private properties.
 *
 * @param mixed $value
 * @return mixed with proper utf-8 encoding
 */
function fix_utf8($value) {
    if (is_null($value) or $value === '') {
        return $value;

    } else if (is_string($value)) {
        if ((string)(int)$value === $value) {
            // shortcut
            return $value;
        }

        // Lower error reporting because glibc throws bogus notices.
        $olderror = error_reporting();
        if ($olderror & E_NOTICE) {
            error_reporting($olderror ^ E_NOTICE);
        }

        // Note: this duplicates min_fix_utf8() intentionally.
        static $buggyiconv = null;
        if ($buggyiconv === null) {
            $buggyiconv = (!function_exists('iconv') or iconv('UTF-8', 'UTF-8//IGNORE', '100'.chr(130).'\80') !== '100\80');
        }

        if ($buggyiconv) {
            if (function_exists('mb_convert_encoding')) {
                $subst = mb_substitute_character();
                mb_substitute_character('');
                $result = mb_convert_encoding($value, 'utf-8', 'utf-8');
                mb_substitute_character($subst);

            } else {
                // Warn admins on admin/index.php page.
                $result = $value;
            }

        } else {
            $result = iconv('UTF-8', 'UTF-8//IGNORE', $value);
        }

        if ($olderror & E_NOTICE) {
            error_reporting($olderror);
        }

        return $result;

    } else if (is_array($value)) {
        foreach ($value as $k=>$v) {
            $value[$k] = fix_utf8($v);
        }
        return $value;

    } else if (is_object($value)) {
        $value = clone($value); // do not modify original
        foreach ($value as $k=>$v) {
            $value->$k = fix_utf8($v);
        }
        return $value;

    } else {
        // this is some other type, no utf-8 here
        return $value;
    }
}

function print_object($obj) {
    echo '<pre>';
    print_r($obj);
    echo '</pre>';
}

function require_js($file, $component, $return = false)
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

function require_css($file, $component, $return = false)
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
 *
 */
function required_param($key, $type = 0)
{
    if (array_key_exists($key, $_REQUEST)) {
        $value = $_REQUEST[$key];
        $value = param_filter_type($value, $type);
        return $value;
    }
    die("Missing expected param $key in request input");
}

function optional_param($key, $default, $type = 0)
{
    if (array_key_exists($key, $_REQUEST)) {
        $value = $_REQUEST[$key];
        $value = param_filter_type($value, $type);
        return $value;
    }
    return $default;
}

function param_filter_type($value, $type)
{
    switch($type) {
        case 0:
            return $value; // no filtering
        case PARAM_BOOL:
            return $value == 0; // forces outputing boolean
        case PARAM_INT:
            if (preg_match('/^([1-90]+)/', $value, $matches)) {
                return $matches[1];
            }
            return 0;
        case PARAM_TEXT:
            // TODO more filtering here
            return $value;
    }
}

function redirect($url) {
    header("Location: $url\n\n");
}

function vchamilo_get_slug_from_url($url)
{
    $slugify = new Slugify();
    $urlInfo = parse_url($url);
    return $slugify->slugify($urlInfo['host']);
}

/**
 * Check if all settings are complete
 */
function vchamilo_check_settings()
{
    $enabled = vchamilo_get_config('vchamilo', 'enable_virtualisation');

    if (empty($enabled)) {
        api_not_allowed(true, 'Plugin is not enabled');
    }

    $coursePath = vchamilo_get_config('vchamilo', 'course_real_root');
    $homePath = vchamilo_get_config('vchamilo', 'home_real_root');
    $archivePath = vchamilo_get_config('vchamilo', 'archive_real_root');
    $cmdSql = vchamilo_get_config('vchamilo', 'cmd_mysql');
    $cmdMySql = vchamilo_get_config('vchamilo', 'cmd_mysqldump');

    if (empty($coursePath) || empty($homePath) || empty($archivePath) || empty($cmdSql)|| empty($cmdMySql)) {
        api_not_allowed(true, 'You have to complete all plugin settings.');
    }

}