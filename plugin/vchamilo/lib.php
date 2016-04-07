<?php
require_once('lib/bootlib.php');
require_once('lib/vchamilo_plugin.class.php');

function vchamilo_hook_configuration(&$_configuration) {
    global $VCHAMILO;

    if (defined('CLI_SCRIPT') && !defined('CLI_VCHAMILO_OVERRIDE')) return;

    // provides an effective value for the virtual root_web    based on domain analysis
    vchamilo_get_hostname($_configuration);

    $plugin = VChamiloPlugin::create();

    // We are on physical chamilo. Let original config play
    if ($_configuration['root_web'] == $_configuration['vchamilo_web_root'].'/'){
        $VCHAMILO = 'main';
        return;
    }

    // pre hook to chamilo main table and get alternate configuration.
    // sure Database object is not set up. Soo use bootstrap connection
    $side_cnx = vchamilo_boot_connection($_configuration, 'main');

    $table = 'vchamilo';
    
    $query = "
        SELECT * FROM $table WHERE root_web = '{$_configuration['vchamilo_web_root']}'
    ";

    $excludes = array('id', 'name');

    $res = mysql_query($query, $side_cnx);
    if ($res) {
        if (mysql_num_rows($res)) {
            $vchamilo = mysql_fetch_assoc($res);
            foreach($vchamilo as $key => $value){
                if (!in_array($key, $excludes)){
                    $_configuration[$key] = $value;
                }

                // take first domain fragment as radical
                $arr = preg_replace('#https?://#', '', $_configuration['vchamilo_name']);
                $domain = explode('.', $arr);
                $vchamilo_radical = array_shift($domain);
                $VCHAMILO = $vchamilo_radical;
            }
        } else {
            die ("VChamilo : No configuration for this host. May be faked.");
        }
    } else {
        die ("VChamilo : Could not fetch virtual chamilo configuration");
    }
}

/**
*
*
*/
function vchamilo_get_hostname(&$_configuration) {

    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' || !empty($_configuration['force_https_forwarded_proto'])) {
        $protocol = 'https';
    } else {
        $protocol = 'http';
    }
    
    if (defined('CLI_VCHAMILO_OVERRIDE')) {
        $_configuration['vchamilo_web_root'] = CLI_VCHAMILO_OVERRIDE;
        $_configuration['vchamilo_name'] = preg_replace('#https?://#', '', CLI_VCHAMILO_OVERRIDE); // remove radical from override for name

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
function vchamilo_boot_connection(&$_configuration, $binddb = false) {

    // Important : force new link here
    $mysql_side_cnx = mysql_connect($_configuration['db_host'], $_configuration['db_user'], $_configuration['db_password'], true);
    if (!$mysql_side_cnx) {
        // echo('Side connection failure with '.$_configuration['db_host'].', '.$_configuration['db_user'].', '.$_configuration['db_password']);
        echo('Side connection failure with '.$_configuration['db_host'].', '.$_configuration['db_user'].', ******** ');
        return false;
    }
    mysql_set_charset('utf8', $mysql_side_cnx);
    if (!empty($binddb)) {
        if (!in_array($binddb, array('main', 'statistics', 'user_personal'), true)) {
            echo('Not a chamilo database. should be one of "main", "statistics" or "user_personal"');
            mysql_close($mysql_side_cnx);
            return false;
        }

        if (!mysql_select_db($_configuration[$binddb.'_database'], $mysql_side_cnx)) {
            echo("vchamilo_make_connection : Database not found<br/>");
            mysql_close($mysql_side_cnx);
            return false;
        }
    }
    return $mysql_side_cnx;
}

function vchamilo_redirect($url) {
    if (preg_match('#https?://#', $url)) {
        header('location: '.$url);
    } else {
        header('location: ' . api_get_path(WEB_PATH).$url);
    }
}

function vchamilo_get_htaccess_fragment($course_folder) {

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

function vchamilo_template_exists($template) {
    global $_configuration;

    // Find and checktemplate directory (files and SQL).
    $separator    =    DIRECTORY_SEPARATOR;
    $templatefoldername    =    'plugin'.$separator.'vchamilo'.$separator.'templates';
    $absolute_templatesdir = $_configuration['root_sys'].$templatefoldername;
    $relative_datadir    =    $templatefoldername.$separator.$template.'_sql';
    $absolute_datadir    =    $_configuration['root_sys'].$relative_datadir;
    
    return is_dir($absolute_datadir);
}

/**
* drop a vchamilo instance databases using the physical connection
* @param object $vchamilo
* @param handle $side_cnx
* return an array of errors or false if ok
*/
function vchamilo_drop_databases(&$vchamilo){
    global $plugininstance;
    
    if (is_array($vchamilo)) $vchamilo = (object)$vchamilo;

    // Drop databases you need to drop
    $sqls = array(" DROP DATABASE `{$vchamilo->main_database}` ");

    if (!empty($vchamilo->statistics_database) && ($vchamilo->main_database != $vchamilo->statistics_database)) {
        $sqls[] = " DROP DATABASE `{$vchamilo->statistics_database}` ";
    };

    if (!empty($vchamilo->user_personal_database) && ($vchamilo->user_personal_database != $vchamilo->statistics_database) && ($vchamilo->main_database != $vchamilo->user_personal_database)) {
        $sqls[] = " DROP DATABASE `{$vchamilo->user_personal_database}` ";
    }

    foreach($sqls as $sql){
        $res = Database::query($sql);
        if (!$res){
            $erroritem = new StdClass();
            $erroritem->message = $plugininstance->get_lang('couldnotdropdb');
            $erroritem->on = 'db';
            $erroritems[] = $erroritem;
        }
    }

    if (!empty($erroritems)){
        return $erroritems;
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
function vchamilo_create_databases($vchamilo, $cnx = null) {
    global $_configuration, $DB;

    // availability of SQL commands

    $createstatement = 'CREATE DATABASE %DATABASE% DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ';

    $dbs = array($vchamilo->main_database);

    if (!empty($vchamilo->statistics_database) && $vchamilo->main_database != $vchamilo->statistics_database){
        $dbs[] = $vchamilo->statistics_database;
    }

    if (!empty($vchamilo->user_personal_database) && ($vchamilo->main_database != $vchamilo->user_personal_database) && ($vchamilo->statistics_database != $vchamilo->user_personal_database)){
        $dbs[] = $vchamilo->user_personal_database;
    }

    foreach($dbs as $adb){
        ctrace("Creating DB $adb ");
        $sql = str_replace('%DATABASE%', $adb, $createstatement);
        if(!$DB->execute_sql($sql)){
            print_error('noexecutionfor','block_vmoodle', $sql);
            return false;
        }

    }
    return true;
}

/**
* get a proper SQLdump command
* @param object $vmoodledata the complete new host information
* @return string the shell command 
*/
function vchamilo_get_database_dump_cmd($vchamilodata){
    global $CFG;

    $pgm = vchamilo_get_config('mysql_cmd');

    // Checks the needed program.
    ctrace("load_database_from_dump : checking database command");
    if(!$pgm){
        $pgm = '/usr/bin/mysql';
        ctrace("Using default database command $pgm ");
    }

    $phppgm = str_replace("\\", '/', $pgm);
    $phppgm = str_replace("\"", '', $phppgm);
    $pgm = str_replace("/", DIRECTORY_SEPARATOR, $pgm);

    ctrace('load_database_from_dump : checking command is available');
    if(!is_executable($phppgm)){
        print_error('databasecommanddoesnotmatchanexecutablefile');
        return false;
    }

    // Retrieves the host configuration (more secure).
    $vchamilodata = vchamilo_make_this();
    if (strstr($vchamilodata->db_host, ':') !== false){
        list($vchamilodata->db_host, $vchamilodata->db_port) = split(':', $vchamilodata->db_host);
    }

    // Password.
    if (!empty($vchamilodata->db_password)){
        $vchamilodata->db_password = '-p'.escapeshellarg($vchamilodata->db_password).' ';
    }

    // Making the command line (see 'vconfig.php' file for defining the right paths).
    $sqlcmd    = $pgm.' -h'.$vchamilodata->db_host.(isset($vchamilodata->db_port) ? ' -P'.$vchamilodata->db_port.' ' : ' ' );
    $sqlcmd .= '-u'.$vchamilodata->db_user.' '.$vchamilodata->db_password;
    $sqlcmd .= '%DATABASE% < ';

    return $sqlcmd;
}

function vchamilo_load_db_template($vchamilo, $dbtemplate, $template){
    global $_configuration;

    // Make template directory (files and SQL).
    $separator    =    DIRECTORY_SEPARATOR;
    $templatefoldername    =    'plugin'.$separator.'vchamilo'.$separator.'templates';
    $absolute_templatesdir = $_configuration['root_sys'].$templatefoldername;
    $relative_datadir    =    $templatefoldername.$separator.$template.'_sql';
    $absolute_datadir    =    $_configuration['root_sys'].$relative_datadir;
    
    $filerad = preg_replace('/_database$/', '', $dbtemplate);
    $sqlfile = 'chamilo_master_'.$filerad.'.sql';

    if(!$sqlcmd = vchamilo_get_database_dump_cmd($vchamilo)){
        return false;
    }

    $sqlcmd = str_replace('%DATABASE%', $vchamilo->$dbtemplate, $sqlcmd);

    // Make final commands to execute, depending on the database type.
    $import = $sqlcmd.$absolute_datadir.'/'.$sqlfile;

    // Execute the command.
    ctrace("load_database_from_dump : executing feeding sql as \n $import ");

    if (!defined('CLI_SCRIPT')){
        putenv('LANG=en_US.utf-8'); 
    }
    // ensure utf8 is correctly handled by php exec()
    // @see http://stackoverflow.com/questions/10028925/call-a-program-via-shell-exec-with-utf-8-text-input

    exec($import, $output, $return);

    ctrace(implode("\n", $output)."\n");
    return true;
}

/**
* load a bulk sql in database that is given through a vchamilo configuration record.
* @param object $vchamilo
* @param string $bulfile a bulk file of queries to process on the database
* @param handle $cnx
* @param array $vars an array of vars to inject in the bulk file before processing
*/
function vchamilo_execute_db_sql(&$vchamilo, $bulkfile, $cnx = null, $vars=null, $filter=null){
    global $_configuration;

   if (file_exists($bulkfile)){
        $erroritem = new StdClass();
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
                $erroritem = new StdClass();
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
function vchamilo_dump_databases($vchamilo, $outputfilerad){
    global $CFG;

    // Separating host and port, if sticked.
    if (strstr($vchamilo->db_host, ':') !== false){
        list($host, $port) = split(':', $vchamilo->db_host);
    } else {
        $host = $vchamilo->db_host;
    }

    // By default, empty password.
    $pass = '';
    $pgm = null;
  
    if (empty($port)){
        $port = 3306;
    }

    // Password.
    if (!empty($vchamilo->db_password)){
        $pass = "-p".escapeshellarg($vchamilo->db_password);
    }

    // Making the commands for each database.
    $cmds = array();
    if ($CFG->ostype == 'WINDOWS'){
        $cmd_main = "-h{$host} -P{$port} -u{$vchamilo->db_user} {$pass} {$vchamilo->main_database}";
        $cmds[] = $cmd_main . ' > ' . $outputfilerad.'_main.sql';

        if ($vchamilo->statistics_database != $vchamilo->main_database){
            $cmd_stats = "-h{$host} -P{$port} -u{$vchamilo->db_user} {$pass} {$vchamilo->statistics_database}";
            $cmds[] = $cmd_stats . ' > ' . $outputfilerad.'_statistics.sql';
        }

        if (($vchamilo->user_personal_database != $vchamilo->main_database) && ($vchamilo->user_personal_database != $vchamilo->statistics_database)) {
            $cmd_user = "-h{$host} -P{$port} -u{$vchamilo->db_user} {$pass} {$vchamilo->user_personal_database}";
            $cmds[] = $cmd_user . ' > ' . $outputfilerad.'_user_personal.sql';
        }

    } else {
        $cmd_main = "-h{$host} -P{$port} -u{$vchamilo->db_user} {$pass} {$vchamilo->main_database}";
        $cmds[] = $cmd_main . ' > ' . escapeshellarg($outputfilerad.'_main.sql');

        if ($vchamilo->statistics_database != $vchamilo->main_database){
            $cmd_stats = "-h{$host} -P{$port} -u{$vchamilo->db_user} {$pass} {$vchamilo->statistics_database}";
            $cmds[] = $cmd_stats . ' > ' . escapeshellarg($outputfilerad.'_statistics.sql');
        }

        if (($vchamilo->user_personal_database != $vchamilo->main_database) && ($vchamilo->user_personal_database != $vchamilo->statistics_database)) {
            $cmd_user = "-h{$host} -P{$port} -u{$vchamilo->db_user} {$pass} {$vchamilo->user_personal_database}";
            $cmds[] = $cmd_user . ' > ' . escapeshellarg($outputfilerad.'_user_personal.sql');
        }
    }
    
    $mysqldumpcmd = vchamilo_get_config('vchamilo', 'cmd_mysqldump', true);

    $pgm = (!empty($mysqldumpcmd)) ? stripslashes($mysqldumpcmd) : false ;

    if(!$pgm){
        $erroritem = new StdClass();
        $erroritem->message = "Database dump command not available";
        return array($erroritem);
    } else {
        $phppgm = str_replace("\\", '/', $pgm);
        $phppgm = str_replace("\"", '', $phppgm);
        $pgm = str_replace('/', DIRECTORY_SEPARATOR, $pgm);

        if (!is_executable($phppgm)){
            $erroritem = new StdClass();
            $erroritem->message = "Database dump command $phppgm does not match any executable";
            return array($erroritem);
        }

        // executing all commands
        foreach($cmds as $cmd){

            // Final command.
            $cmd = $pgm.' '.$cmd;
            // ctrace($cmd); // Be carefull there, this could divulgate DB password

            // Prints log messages in the page and in 'cmd.log'.
            if ($LOG = fopen(dirname($outputfilerad).'/cmd.log', 'a')){
                fwrite($LOG, $cmd."\n");
            }
    
            // Executes the SQL command.
            exec($cmd, $execoutput, $returnvalue);
            if ($LOG){
                foreach($execoutput as $execline) fwrite($LOG, $execline."\n");
                fwrite($LOG, $returnvalue."\n");
                fclose($LOG);
            }
        }
    }

    // End with success.
    return 0;
}

/**
* read manifest values in vchamilo template.
* @uses $CFG
*/
function vchamilo_get_vmanifest($version){
    global $_configuration;

    include(api_get_path(SYS_PATH, SYS_PATH).'/plugin/vchamilo/templates/'.$version.'_sql/manifest.php');
    $manifest->templatewwwroot = $templatewwwroot;
    $manifest->templatevdbprefix = $templatevdbprefix;
    $manifest->coursefolder = $coursefolder;
    return $manifest;
}

/**
* make a fake vchamilo that represents the current host
*/
function vchamilo_make_this(){
    global $_configuration;

    $thischamilo->root_web = $_configuration['root_web'];

    $thischamilo->db_host = $_configuration['db_host'];
    $thischamilo->db_user = $_configuration['db_user'];
    $thischamilo->db_password = $_configuration['db_password'];
    $thischamilo->db_prefix = $_configuration['db_prefix'];
    $thischamilo->main_database = $_configuration['main_databse'];
    $thischamilo->statistics_database = $_configuration['statistics_databse'];
    $thischamilo->user_personal_database = $_configuration['user_personal_databse'];
    $thischamilo->table_prefix = $_configuration['table_prefix'];
    $thischamilo->single_database = $_configuration['single_database'];
    $thischamilo->tracking_enabled = $_configuration['tracking_enabled'];
    return $thischamilo;
}

/**
 * Get available templates for defining a new virtual host.
 * @return        array        The availables templates, or EMPTY array.
 */
function vchamilo_get_available_templates() {
    global $_configuration;
    global $plugininstance;

    $separator = DIRECTORY_SEPARATOR;

    $templatefoldername    = 'plugin'.$separator.'vchamilo'.$separator.'templates';
    $absolute_templatesdir = $_configuration['root_sys'].$templatefoldername;

    // Scans the templates.
    if(!is_dir($absolute_templatesdir)){
        mkdir($absolute_templatesdir, 0777, true);
    }
    $dirs = glob($absolute_templatesdir.'/*');
    $vtemplates = preg_grep("/[^\/](.*)_vchamilodata$/", $dirs);

    // Retrieves template(s) name(s). Should be hostnames.
    $templatesarray = array('' => $plugininstance->get_lang('emptysite'));
    if ($vtemplates){
        foreach($vtemplates as $vtemplatedir){
            preg_match("/([^\/]*)_vchamilodata/", $vtemplatedir, $matches);
            $templatesarray[$matches[1]] = $matches[1];
            if (!isset($first)) $first = $matches[1];
        }
    }

    return $templatesarray;
}

function vchamilo_print_error($errortrace, $return = false){

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
    echo $str;
}

/**
* this function set will map standard moodle API calls to chamilo
* internal primitives. This avoids too many changes to do in imported 
* code
*
*/
function vchamilo_get_config($module, $key, $isplugin = true){
    global $_configuration, $DB;
    
    if ($isplugin){
        $key = $module.'_'.$key;
    }
    if($configrec = $DB->get_record(TABLE_MAIN_SETTINGS_CURRENT, array('variable' => $key, 'subkey' => $module))){
        return $configrec->selected_value;
    }
    return false;
}

function vchamilo_load_files_from_template($vchamilo, $template){
    global $_configuration;

    // Make template directory (files and SQL).
    $separator    =    DIRECTORY_SEPARATOR;
    $templatefoldername    =    'plugin'.$separator.'vchamilo'.$separator.'templates';
    $relative_template_datadir    =    $templatefoldername.$separator.$template.'_vchamilodata';

    $absolute_templatesdir = $_configuration['root_sys'].$templatefoldername;
    $absolute_template_datadir = $_configuration['root_sys'].$relative_template_datadir;

    $vchamilo->virtual = true;

    // Get Vchamilo known record.
    $vcoursepath = api_get_path(TO_SYS, SYS_COURSE_PATH, (array)$vchamilo);
    $vhomepath = api_get_path(TO_SYS, SYS_HOME_PATH, (array)$vchamilo);
    $varchivepath = api_get_path(TO_SYS, SYS_ARCHIVE_PATH, (array)$vchamilo);
    
    echo "archiveapth : $varchivepath";

    // Rename some dirs top match instance requirements
    $manifest = vchamilo_get_vmanifest($template);
        
    // get the protocol free hostname
    $originarchivedir = preg_replace('/https?:\/\//', '', $manifest->templatewwwroot);
    $originhomedir = preg_replace('/https?:\/\//', '', $manifest->templatewwwroot);

    ctrace("Copying {$absolute_template_datadir}/{$manifest->coursefolder} => $vcoursepath");
    ctrace("Copying {$absolute_template_datadir}/archive/{$originarchivedir} => $varchivepath");
    ctrace("Copying {$absolute_template_datadir}/home/{$originhomedir} => $vhomepath");

    copyDirContentTo(chop_last_slash($absolute_template_datadir.'/'.$manifest->coursefolder), chop_last_slash($vcoursepath), false);
    copyDirContentTo(chop_last_slash($absolute_template_datadir.'/archive/'.$originarchivedir), chop_last_slash($varchivepath), false);
    copyDirContentTo(chop_last_slash($absolute_template_datadir.'/home/'.$originhomedir), chop_last_slash($vhomepath), false);

}

function chop_last_slash($path){
    return preg_replace('/\/$/', '', $path);
}

/**
 * Moves a directory and its content to an other area
 *
 * @author - Hugues Peeters <peeters@ipm.ucl.ac.be>
 * @param  - $orig_dir_path (string) - the path of the directory to move
 * @param  - $destination (string) - the path of the new area
 * @return - no return
 */
function copyDirContentTo($source, $destination, $move = true) {

    // Extract directory name - create it at destination - update destination trail
    if (!is_dir($source)) {
        return;
    }

    if (!is_dir($destination)) {
        mkdir($destination, api_get_permissions_for_new_directories());
    }

    $DIR = opendir($source);

    while ($element = readdir($DIR)) {
        if ($element == '.' || $element == '..') {
            continue; // Skip the current and parent directories
        } elseif (is_file($source.'/'.$element)) {
            copy($source.'/'.$element, $destination.'/'.$element);

            if ($move) {
                unlink($source.'/'.$element) ;
            }
        } elseif (is_dir($source.'/'.$element)) {
            $dirs_to_copy[] = $element;
        }
    }

    closedir($DIR) ;

    if (sizeof($dirs_to_copy) > 0) {
        foreach ($dirs_to_copy as $dir) {
            copyDirContentTo($source.'/'.$dir, $destination.'/'.$dir, $move); // Recursivity
        }
    }

    if ($move) {
        rmdir($source) ;
    }
}

