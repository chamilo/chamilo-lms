<?php
/* For licensing terms, see /license.txt */
/**
 * 
 * * Class that is responsible for generating diagnostic information about the system
 * 
 *
 * @package chamilo.diagnoser
 * @author Ivan Tcholakov, 2008, initiall proposal and sample code.
 * @author spou595, 2009, implementation for Chamilo 2.x
 * @author Julio Montoya <gugli100@gmail.com>, 2010, port to chamilo 1.8.7, Some fixes
 * 
 */
/**
 * Diagnoser class
 * @package chamilo.diagnoser
 */
class Diagnoser
{
    /**
     * The status's
     */
    const STATUS_OK 			= 1;
    const STATUS_WARNING 		= 2;
    const STATUS_ERROR 			= 3;
    const STATUS_INFORMATION 	= 4;

    function __construct() {
    }

    function show_html() {

        $sections = array('chamilo', 'php', 'mysql', 'webserver');

        if (!in_array($_GET['section'], $sections)) {
            $current_section = 'chamilo';
        } else {
            $current_section = $_GET['section'];
        }

        $html = '<div class="tabbable"><ul class="nav nav-tabs">';

        foreach ($sections as $section) {
            
            if ($current_section == $section) {                
                $html .= '<li class="active">';
            } else {
                $html .= '<li>';
            }
            $params['section'] = $section;
            $html .='<a href="system_status.php?section='.$section.'">'.get_lang($section).'</a></li>';            
        }

        $html .= '</ul><div class="tab-pane">';

        $data = call_user_func(array($this, 'get_' . $current_section . '_data'));
        echo $html;
        $table = new SortableTableFromArray($data, 1, 100);

        $table->set_header(0,'', false);
        $table->set_header(1,get_lang('Section'), false);
        $table->set_header(2,get_lang('Setting'), false);
        $table->set_header(3,get_lang('Current'), false);
        $table->set_header(4,get_lang('Expected'), false);
        $table->set_header(5,get_lang('Comment'), false);

        $table->display();
        echo '</div></div>';
    }

    /**
     * Functions to get the data for the chamilo diagnostics
     * @return array of data
     */
    function get_chamilo_data() {
        $array = array();
        $writable_folders = array('archive', 'courses', 'home', 'main/upload/users/', 'main/default_course_document/images/');
        foreach ($writable_folders as $index => $folder) {            
            $writable = is_writable(api_get_path(SYS_PATH) . $folder);
            $status = $writable ? self :: STATUS_OK : self :: STATUS_ERROR;
            $array[] = $this->build_setting($status, '[FILES]', get_lang('IsWritable') . ': ' . $folder, 'http://be2.php.net/manual/en/function.is-writable.php', $writable, 1, 'yes_no', get_lang('DirectoryMustBeWritable'));
        }
        
        $exists = file_exists(api_get_path(SYS_CODE_PATH).'install');        
        $status = $exists ? self :: STATUS_WARNING : self :: STATUS_OK;         
        $array[] = $this->build_setting($status, '[FILES]', get_lang('DirectoryExists') . ': /install', 'http://be2.php.net/file_exists', $exists, 0, 'yes_no', get_lang('DirectoryShouldBeRemoved'));

        return $array;
    }

    /**
     * Functions to get the data for the php diagnostics
     * @return array of data
     */
    function get_php_data() {
        $array = array();

        // General Functions

        $version = phpversion();
        $status = $version > REQUIRED_PHP_VERSION ? self :: STATUS_OK : self :: STATUS_ERROR;
        $array[] = $this->build_setting($status, '[PHP]', 'phpversion()', 'http://www.php.net/manual/en/function.phpversion.php', phpversion(), '>= '.REQUIRED_PHP_VERSION, null, get_lang('PHPVersionInfo'));

        $setting = ini_get('output_buffering');
        $req_setting = 1;
        $status = $setting >= $req_setting ? self :: STATUS_OK : self :: STATUS_ERROR;
        $array[] = $this->build_setting($status, '[INI]', 'output_buffering', 'http://www.php.net/manual/en/outcontrol.configuration.php#ini.output-buffering', $setting, $req_setting, 'on_off', get_lang('OutputBufferingInfo'));

        $setting = ini_get('file_uploads');
        $req_setting = 1;
        $status = $setting == $req_setting ? self :: STATUS_OK : self :: STATUS_ERROR;
        $array[] = $this->build_setting($status, '[INI]', 'file_uploads', 'http://www.php.net/manual/en/ini.core.php#ini.file-uploads', $setting, $req_setting, 'on_off', get_lang('FileUploadsInfo'));

        $setting = ini_get('magic_quotes_runtime');
        $req_setting = 0;
        $status = $setting == $req_setting ? self :: STATUS_OK : self :: STATUS_ERROR;
        $array[] = $this->build_setting($status, '[INI]', 'magic_quotes_runtime', 'http://www.php.net/manual/en/ini.core.php#ini.magic-quotes-runtime', $setting, $req_setting, 'on_off', get_lang('MagicQuotesRuntimeInfo'));

        $setting = ini_get('safe_mode');
        $req_setting = 0;
        $status = $setting == $req_setting ? self :: STATUS_OK : self :: STATUS_WARNING;
        $array[] = $this->build_setting($status, '[INI]', 'safe_mode', 'http://www.php.net/manual/en/ini.core.php#ini.safe-mode', $setting, $req_setting, 'on_off', get_lang('SafeModeInfo'));

        $setting = ini_get('register_globals');
        $req_setting = 0;
        $status = $setting == $req_setting ? self :: STATUS_OK : self :: STATUS_ERROR;
        $array[] = $this->build_setting($status, '[INI]', 'register_globals', 'http://www.php.net/manual/en/ini.core.php#ini.register-globals', $setting, $req_setting, 'on_off', get_lang('RegisterGlobalsInfo'));

        $setting = ini_get('short_open_tag');
        $req_setting = 0;
        $status = $setting == $req_setting ? self :: STATUS_OK : self :: STATUS_WARNING;
        $array[] = $this->build_setting($status, '[INI]', 'short_open_tag', 'http://www.php.net/manual/en/ini.core.php#ini.short-open-tag', $setting, $req_setting, 'on_off', get_lang('ShortOpenTagInfo'));

        $setting = ini_get('magic_quotes_gpc');
        $req_setting = 0;
        $status = $setting == $req_setting ? self :: STATUS_OK : self :: STATUS_ERROR;
        $array[] = $this->build_setting($status, '[INI]', 'magic_quotes_gpc', 'http://www.php.net/manual/en/ini.core.php#ini.magic_quotes_gpc', $setting, $req_setting, 'on_off', get_lang('MagicQuotesGpcInfo'));

        $setting = ini_get('display_errors');
        $req_setting = 0;
        $status = $setting == $req_setting ? self :: STATUS_OK : self :: STATUS_WARNING;
        $array[] = $this->build_setting($status, '[INI]', 'display_errors', 'http://www.php.net/manual/en/ini.core.php#ini.display_errors', $setting, $req_setting, 'on_off', get_lang('DisplayErrorsInfo'));

        $setting = ini_get('upload_max_filesize');
        $req_setting = '10M - 100M - ...';
        if ($setting < 10)
            $status = self :: STATUS_ERROR;
        if ($setting >= 10 && $setting < 100)
            $status = self :: STATUS_WARNING;
        if ($setting >= 100)
            $status = self :: STATUS_OK;
        $array[] = $this->build_setting($status, '[INI]', 'upload_max_filesize', 'http://www.php.net/manual/en/ini.core.php#ini.upload_max_filesize', $setting, $req_setting, null, get_lang('UploadMaxFilesizeInfo'));

        $setting = ini_get('default_charset');
        if ($setting == '')
            $setting = null;
        $req_setting = null;
        $status = $setting == $req_setting ? self :: STATUS_OK : self :: STATUS_ERROR;
        $array[] = $this->build_setting($status, '[INI]', 'default_charset', 'http://www.php.net/manual/en/ini.core.php#ini.default-charset', $setting, $req_setting, null, get_lang('DefaultCharsetInfo'));

        $setting = ini_get('max_execution_time');
        $req_setting = '300 (' . get_lang('minimum') . ')';
        $status = $setting >= 300 ? self :: STATUS_OK : self :: STATUS_WARNING;
        $array[] = $this->build_setting($status, '[INI]', 'max_execution_time', 'http://www.php.net/manual/en/ini.core.php#ini.max-execution-time', $setting, $req_setting, null, get_lang('MaxExecutionTimeInfo'));

        $setting = ini_get('max_input_time');
        $req_setting = '300 (' . get_lang('minimum') . ')';
        $status = $setting >= 300 ? self :: STATUS_OK : self :: STATUS_WARNING;
        $array[] = $this->build_setting($status, '[INI]', 'max_input_time', 'http://www.php.net/manual/en/ini.core.php#ini.max-input-time', $setting, $req_setting, null, get_lang('MaxInputTimeInfo'));

        $setting = ini_get('memory_limit');
        $req_setting = '10M - 100M - ...';
        if ($setting < 10)
            $status = self :: STATUS_ERROR;
        if ($setting >= 10 && $setting < 100)
            $status = self :: STATUS_WARNING;
        if ($setting >= 100)
            $status = self :: STATUS_OK;
        $array[] = $this->build_setting($status, '[INI]', 'memory_limit', 'http://www.php.net/manual/en/ini.core.php#ini.memory-limit', $setting, $req_setting, null, get_lang('MemoryLimitInfo'));

        $setting = ini_get('post_max_size');
        $req_setting = '10M - 100M - ...';
        if ($setting < 10)
            $status = self :: STATUS_ERROR;
        if ($setting >= 10 && $setting < 100)
            $status = self :: STATUS_WARNING;
        if ($setting >= 100)
            $status = self :: STATUS_OK;
        $array[] = $this->build_setting($status, '[INI]', 'post_max_size', 'http://www.php.net/manual/en/ini.core.php#ini.post-max-size', $setting, $req_setting, null, get_lang('PostMaxSizeInfo'));

        $setting = ini_get('variables_order');
        $req_setting = 'GPCS';
        $status = $setting == $req_setting ? self :: STATUS_OK : self :: STATUS_ERROR;
        $array[] = $this->build_setting($status, '[INI]', 'variables_order', 'http://www.php.net/manual/en/ini.core.php#ini.variables-order', $setting, $req_setting, null, get_lang('VariablesOrderInfo'));

        $setting = ini_get('session.gc_maxlifetime');
        $req_setting = '4320';
        $status = $setting == $req_setting ? self :: STATUS_OK : self :: STATUS_WARNING;
        $array[] = $this->build_setting($status, '[SESSION]', 'session.gc_maxlifetime', 'http://www.php.net/manual/en/ini.core.php#session.gc-maxlifetime', $setting, $req_setting, null, get_lang('SessionGCMaxLifetimeInfo'));

        if (api_check_browscap()){$setting = true;}else{$setting=false;}
        $req_setting = true;
        $status = $setting == $req_setting ? self :: STATUS_OK : self :: STATUS_WARNING;
        $array[] = $this->build_setting($status, '[INI]', 'browscap', 'http://www.php.net/manual/en/misc.configuration.php#ini.browscap', $setting, $req_setting, 'on_off', get_lang('BrowscapInfo'));

        //Extensions
        $extensions = array('gd' 		=> array('link'=>'http://www.php.net/gd', 		'expected' => 1, 'comment' => get_lang('ExtensionMustBeLoaded')), 
        					'mysql' 	=> array('link'=>'http://www.php.net/mysql', 	'expected' => 1, 'comment' => get_lang('ExtensionMustBeLoaded')),
        					'pcre' 		=> array('link'=>'http://www.php.net/pcre', 	'expected' => 1, 'comment' => get_lang('ExtensionMustBeLoaded')),
        					'session' 	=> array('link'=>'http://www.php.net/session', 	'expected' => 1, 'comment' => get_lang('ExtensionMustBeLoaded')),
        					'standard' 	=> array('link'=>'http://www.php.net/spl', 		'expected' => 1, 'comment' => get_lang('ExtensionMustBeLoaded')),
        					'zlib' 		=> array('link'=>'http://www.php.net/zlib', 	'expected' => 1, 'comment' => get_lang('ExtensionMustBeLoaded')),
        					'xsl' 		=> array('link'=>'http://be2.php.net/xsl', 		'expected' => 2, 'comment' => get_lang('ExtensionShouldBeLoaded')),
        					'curl' 		=> array('link'=>'http://www.php.net/curl', 	'expected' => 2, 'comment' => get_lang('ExtensionShouldBeLoaded')),
        );

        foreach ($extensions as $extension => $data) {
        	$url  	  		= $data['link'];
        	$expected_value = $data['expected'];
        	$comment 		= $data['comment'];
        	
            $loaded = extension_loaded($extension);
            $status = $loaded ? self :: STATUS_OK : self :: STATUS_ERROR;
            $array[] = $this->build_setting($status, '[EXTENSION]', get_lang('LoadedExtension') . ': ' . $extension, $url, $loaded, $expected_value, 'yes_no_optional', $comment);
        }

        return $array;
    }

    /**
     * Functions to get the data for the mysql diagnostics
     * @return array of data
     */
    function get_mysql_data() {
        $array = array();

        // A note: Maybe it would be better if all "MySQL"-like variable names and words on the page to be replaced with "Database"-like ones.

        $array[] = $this->build_setting(self :: STATUS_INFORMATION, '[MySQL]', 'mysql_get_host_info()', 'http://www.php.net/manual/en/function.mysql-get-host-info.php', Database::get_host_info(), null, null, get_lang('MysqlHostInfo'));

        $array[] = $this->build_setting(self :: STATUS_INFORMATION, '[MySQL]', 'mysql_get_server_info()', 'http://www.php.net/manual/en/function.mysql-get-server-info.php', Database::get_server_info(), null, null, get_lang('MysqlServerInfo'));

        $array[] = $this->build_setting(self :: STATUS_INFORMATION, '[MySQL]', 'mysql_get_proto_info()', 'http://www.php.net/manual/en/function.mysql-get-proto-info.php', Database::get_proto_info(), null, null, get_lang('MysqlProtoInfo'));

        $array[] = $this->build_setting(self :: STATUS_INFORMATION, '[MySQL]', 'mysql_get_client_info()', 'http://www.php.net/manual/en/function.mysql-get-client-info.php', Database::get_client_info(), null, null, get_lang('MysqlClientInfo'));

        return $array;
    }

    /**
     * Functions to get the data for the webserver diagnostics
     * @return array of data
     */
    function get_webserver_data() {
        $array = array();

        $array[] = $this->build_setting(self :: STATUS_INFORMATION, '[SERVER]', '$_SERVER["SERVER_NAME"]', 'http://be.php.net/reserved.variables.server', $_SERVER["SERVER_NAME"], null, null, get_lang('ServerNameInfo'));

        $array[] = $this->build_setting(self :: STATUS_INFORMATION, '[SERVER]', '$_SERVER["SERVER_ADDR"]', 'http://be.php.net/reserved.variables.server', $_SERVER["SERVER_ADDR"], null, null, get_lang('ServerAddessInfo'));

        $array[] = $this->build_setting(self :: STATUS_INFORMATION, '[SERVER]', '$_SERVER["SERVER_PORT"]', 'http://be.php.net/reserved.variables.server', $_SERVER["SERVER_PORT"], null, null, get_lang('ServerPortInfo'));

        $array[] = $this->build_setting(self :: STATUS_INFORMATION, '[SERVER]', '$_SERVER["SERVER_SOFTWARE"]', 'http://be.php.net/reserved.variables.server', $_SERVER["SERVER_SOFTWARE"], null, null, get_lang('ServerSoftwareInfo'));

        $array[] = $this->build_setting(self :: STATUS_INFORMATION, '[SERVER]', '$_SERVER["REMOTE_ADDR"]', 'http://be.php.net/reserved.variables.server', $_SERVER["REMOTE_ADDR"], null, null, get_lang('ServerRemoteInfo'));

        $array[] = $this->build_setting(self :: STATUS_INFORMATION, '[SERVER]', '$_SERVER["HTTP_USER_AGENT"]', 'http://be.php.net/reserved.variables.server', $_SERVER["HTTP_USER_AGENT"], null, null, get_lang('ServerUserAgentInfo'));

        /*$path = $this->manager->get_url(array('section' => Request :: get('section')));
        $request = $_SERVER["REQUEST_URI"];
        $status = $request != $path ? self :: STATUS_ERROR : self :: STATUS_OK;
        $array[] = $this->build_setting($status, '[SERVER]', '$_SERVER["REQUEST_URI"]', 'http://be.php.net/reserved.variables.server', $request, $path, null, get_lang('RequestURIInfo'));
        */
        $array[] = $this->build_setting(self :: STATUS_INFORMATION, '[SERVER]', '$_SERVER["SERVER_PROTOCOL"]', 'http://be.php.net/reserved.variables.server', $_SERVER["SERVER_PROTOCOL"], null, null, get_lang('ServerProtocolInfo'));

        $array[] = $this->build_setting(self :: STATUS_INFORMATION, '[SERVER]', 'php_uname()', 'http://be2.php.net/php_uname', php_uname(), null, null, get_lang('UnameInfo'));

        return $array;
    }

    /**
     * Additional functions needed for fast integration
     */

    function build_setting($status, $section, $title, $url, $current_value, $expected_value, $formatter, $comment, $img_path = null) {
        switch ($status) {
            case self :: STATUS_OK :
                $img = 'bullet_green.gif';
                break;
            case self :: STATUS_WARNING :
                $img = 'bullet_orange.gif';
                break;
            case self :: STATUS_ERROR :
                $img = 'bullet_red.gif';
                break;
            case self :: STATUS_INFORMATION :
                $img = 'bullet_blue.gif';
                break;
        }

        if (! $img_path) {
            $img_path = api_get_path(WEB_IMG_PATH);
        }

        $image = '<img src="' . $img_path . $img . '" alt="' . $status . '" />';
        $url = $this->get_link($title, $url);

        $formatted_current_value = $current_value;
        $formatted_expected_value = $expected_value;

        if ($formatter) {
            if (method_exists($this, 'format_' . $formatter)) {
                $formatted_current_value = call_user_func(array($this, 'format_' . $formatter), $current_value);
                $formatted_expected_value = call_user_func(array($this, 'format_' . $formatter), $expected_value);
            }
        }

        return array($image, $section, $url, $formatted_current_value, $formatted_expected_value, $comment);
    }

    /**
     * Create a link with a url and a title
     * @param $title
     * @param $url
     * @return string the url
     */
    function get_link($title, $url) {
        return '<a href="' . $url . '" target="about:bank">' . $title . '</a>';
    }

    function format_yes_no_optional($value) {
    	$return = '';
    	switch($value) {
     		case 0:
     			$return = get_lang('No');
     			break;
     		case 1:
     			$return = get_lang('Yes');
     			break;
			case 2:
				$return = get_lang('Optional');
				break;     			
    	}
    	return $return;
    	
    }
    
    function format_yes_no($value) {
        return $value ? get_lang('Yes') : get_lang('No');
    }

    function format_on_off($value) {
        $value = intval($value);
        if ($value > 1) {
            // Greater than 1 values are shown "as-is", they may be interpreted as "On" later.
            return $value;
        }
        // These are the values 'On' and 'Off' used in the php-ini file. Translation (get_lang()) is not needed here.
        return $value ? 'On' : 'Off';
    }
}
