<?php

/* For licensing terms, see /license.txt */

/**
 * Class Diagnoser
 * Class that is responsible for generating diagnostic information about the system.
 *
 * @author Ivan Tcholakov, 2008, initial proposal and sample code.
 * @author spou595, 2009, implementation for Chamilo 2.x
 * @author Julio Montoya <gugli100@gmail.com>, 2010, port to chamilo 1.8.7, Some fixes
 */
class Diagnoser
{
    public const STATUS_OK = 1;
    public const STATUS_WARNING = 2;
    public const STATUS_ERROR = 3;
    public const STATUS_INFORMATION = 4;

    /**
     * Contructor.
     */
    public function __construct()
    {
    }

    /**
     * Show html table.
     */
    public function show_html()
    {
        $sections = [
            'chamilo' => [
                'lang' => 'Chamilo',
                'info' => 'State of Chamilo requirements',
            ],
            'php' => [
                'lang' => 'PHP',
                'info' => 'State of PHP settings on the server',
            ],
            'database' => [
                'lang' => 'Database',
                'info' => 'Configuration settings of the database server. To check the database consistency after an upgrade, if you have access to the command line, you can use "php bin/doctrine.php orm:schema-tool:update --dump-sql". This will print a list of database changes that should be applied to your system in order to get the right structure. Index name changes can be ignored. Use "--force" instead of "--dump" to try and execute them in order.',
            ],
            'webserver' => [
                'lang' => get_lang('Web server'),
                'info' => 'Information about your webserver\'s configuration ',
            ],
            'paths' => [
                'lang' => 'Paths',
                'info' => 'The following paths are called by their constant throughout Chamilo\'s code using the api_get_path() function. Here is a list of these paths and what they would be translated to on this portal.',
            ],
            'courses_space' => [
                'lang' => 'Courses space',
                'info' => 'Information about space used by courses on disk. The space used on disk represents the total space used, whereas the quota only limits files in the documents tool. Only 1000 courses are shown, by order of last access and alphabetical code order. For more, please go to the courses folder and use "du -sh *" to get the size of the courses.',
            ],
        ];
        $currentSection = isset($_GET['section']) ? $_GET['section'] : '';
        if (!in_array(trim($currentSection), array_keys($sections))) {
            $currentSection = 'chamilo';
        }

        $html = '<div class="tabbable"><ul class="nav nav-tabs">';

        foreach ($sections as $section => $details) {
            if ($currentSection === $section) {
                $html .= '<li class="active">';
            } else {
                $html .= '<li>';
            }
            $params['section'] = $section;
            $html .= '<a href="system_status.php?section='.$section.'">'.$details['lang'].'</a></li>';
        }

        $html .= '</ul><div class="tab-pane">';

        $data = call_user_func([$this, 'get_'.$currentSection.'_data']);
        echo $html;

        if ($currentSection === 'paths') {
            echo '<br />';
            echo Display::return_message($sections[$currentSection]['info'], 'normal');

            $headers = $data['headers'];
            $results = $data['data'];
            $table = new HTML_Table(['class' => 'table table-hover table-striped data_table']);

            $column = 0;
            foreach ($headers as $header) {
                $table->setHeaderContents(0, $column, $header);
                $column++;
            }
            $row = 1;
            foreach ($results as $index => $rowData) {
                $table->setCellContents(
                    $row,
                    0,
                    $rowData
                );
                $table->setCellContents(
                    $row,
                    1,
                    $index
                );
                $row++;
            }
            $table->display();
        } elseif ($currentSection == 'courses_space') {
            echo '<br />';
            echo Display::return_message($sections[$currentSection]['info'], 'normal');

            $table = new SortableTableFromArray($data, 1, 1000);
            $table->set_additional_parameters(['section' => 'courses_space']);
            $table->set_header(0, '', false);
            $table->set_header(1, get_lang('CourseCode'), true);
            $table->set_header(2, 'Space used on disk (MB)', true);
            $table->set_header(3, 'Set max course space (MB)', false);
            $table->set_header(4, get_lang('Edit'), false);
            $table->set_header(5, get_lang('LastVisit'), true);
            $table->set_header(6, get_lang('CurrentDirectory'), false);

            $table->display();
        } else {
            echo '<br />';
            echo Display::return_message($sections[$currentSection]['info'], 'normal');

            $table = new SortableTableFromArray($data, 1, 100);
            $table->set_header(0, '', false);
            $table->set_header(1, get_lang('Section'), false);
            $table->set_header(2, get_lang('Setting'), false);
            $table->set_header(3, get_lang('Current'), false);
            $table->set_header(4, get_lang('Expected'), false);
            $table->set_header(5, get_lang('Comment'), false);

            $table->display();
        }
        echo '</div></div>';
    }

    /**
     * @return array
     */
    public function get_paths_data()
    {
        global $paths;
        $list = $paths[api_get_path(WEB_PATH)];
        //$list['url_append'] = api_get_configuration_value('url_append');
        asort($list);

        return [
            'headers' => ['Path', 'constant'],
            'data' => $list,
        ];
    }

    /**
     * Functions to get the data for the chamilo diagnostics.
     *
     * @return array of data
     */
    public function get_chamilo_data()
    {
        $array = [];
        $writable_folders = [
            api_get_path(SYS_ARCHIVE_PATH).'cache',
            api_get_path(SYS_PATH).'upload/users/',
        ];
        foreach ($writable_folders as $index => $folder) {
            $writable = is_writable($folder);
            $status = $writable ? self::STATUS_OK : self::STATUS_ERROR;
            $array[] = $this->build_setting(
                $status,
                '[FILES]',
                get_lang('Is writable').': '.$folder,
                'http://be2.php.net/manual/en/function.is-writable.php',
                $writable,
                1,
                'yes_no',
                get_lang('The directory must be writable by the web server')
            );
        }

        $exists = file_exists(api_get_path(SYS_CODE_PATH).'install');
        $status = $exists ? self::STATUS_WARNING : self::STATUS_OK;
        $array[] = $this->build_setting(
            $status,
            '[FILES]',
            get_lang('The directory exists').': /install',
            'http://be2.php.net/file_exists',
            $exists,
            0,
            'yes_no',
            get_lang('The directory should be removed (it is no longer necessary)')
        );

        $app_version = api_get_setting('platform.chamilo_database_version');
        $array[] = $this->build_setting(
            self::STATUS_INFORMATION,
            '[DB]',
            'chamilo_database_version',
            '#',
            $app_version,
            0,
            null,
            'Chamilo DB version'
        );

        $access_url_id = api_get_current_access_url_id();

        if (1 === $access_url_id) {
            $size = '-';
            global $_configuration;
            $message2 = '';
            if (1 === $access_url_id) {
                if (api_is_windows_os()) {
                    $message2 .= get_lang('The space used on disk cannot be measured properly on Windows-based systems.');
                } else {
                    $dir = api_get_path(SYS_PATH);
                    $du = exec('du -sh '.$dir, $err);
                    list($size, $none) = explode("\t", $du);
                    unset($none);
                    $limit = 0;
                    if (isset($_configuration[$access_url_id])) {
                        if (isset($_configuration[$access_url_id]['hosting_limit_disk_space'])) {
                            $limit = $_configuration[$access_url_id]['hosting_limit_disk_space'];
                        }
                    }
                    $message2 .= sprintf(get_lang('Total space used by portal %s limit is %s MB'), $size, $limit);
                }
            }

            $array[] = $this->build_setting(
                self::STATUS_OK,
                '[FILES]',
                'hosting_limit_disk_space',
                '#',
                $size,
                0,
                null,
                $message2
            );
        }
        $new_version = '-';
        $new_version_status = '';
        $file = api_get_path(SYS_CODE_PATH).'install/version.php';
        if (is_file($file)) {
            @include $file;
        }
        $array[] = $this->build_setting(
            self::STATUS_INFORMATION,
            '[CONFIG]',
            get_lang('Version from the version file'),
            '#',
            $new_version.' '.$new_version_status,
            '-',
            null,
            get_lang('The version from the version.php file is updated with each version but only available if the main/install/ directory is present.')
        );
        $array[] = $this->build_setting(
            self::STATUS_INFORMATION,
            '[CONFIG]',
            get_lang('Version from the config file'),
            '#',
            api_get_configuration_value('system_version'),
            $new_version,
            null,
            get_lang('The version from the main configuration file shows on the main administration page, but has to be changed manually on upgrade.')
        );

        return $array;
    }

    /**
     * Functions to get the data for the php diagnostics.
     *
     * @return array of data
     */
    public function get_php_data()
    {
        $array = [];

        // General Functions

        $version = phpversion();
        $status = $version > REQUIRED_PHP_VERSION ? self::STATUS_OK : self::STATUS_ERROR;
        $array[] = $this->build_setting(
            $status,
            '[PHP]',
            'phpversion()',
            'https://php.net/manual/en/function.phpversion.php',
            phpversion(),
            '>= '.REQUIRED_PHP_VERSION,
            null,
            get_lang('PHP version')
        );

        $setting = ini_get('output_buffering');
        $req_setting = 1;
        $status = $setting >= $req_setting ? self::STATUS_OK : self::STATUS_ERROR;
        $array[] = $this->build_setting(
            $status,
            '[INI]',
            'output_buffering',
            'https://php.net/manual/en/outcontrol.configuration.php#ini.output-buffering',
            $setting,
            $req_setting,
            'on_off',
            get_lang('Output buffering setting is "On" for being enabled or "Off" for being disabled. This setting also may be enabled through an integer value (4096 for example) which is the output buffer size.')
        );

        $setting = ini_get('file_uploads');
        $req_setting = 1;
        $status = $setting == $req_setting ? self::STATUS_OK : self::STATUS_ERROR;
        $array[] = $this->build_setting(
            $status,
            '[INI]',
            'file_uploads',
            'https://php.net/manual/en/ini.core.php#ini.file-uploads',
            $setting,
            $req_setting,
            'on_off',
            get_lang('File uploads indicate whether file uploads are authorized at all')
        );

        $setting = ini_get('magic_quotes_runtime');
        $req_setting = 0;
        $status = $setting == $req_setting ? self::STATUS_OK : self::STATUS_ERROR;
        $array[] = $this->build_setting(
            $status,
            '[INI]',
            'magic_quotes_runtime',
            'https://php.net/manual/en/ini.core.php#ini.magic-quotes-runtime',
            $setting,
            $req_setting,
            'on_off',
            get_lang('This is a highly unrecommended feature which converts values returned by all functions that returned external values to slash-escaped values. This feature should *not* be enabled.')
        );

        $setting = ini_get('safe_mode');
        $req_setting = 0;
        $status = $setting == $req_setting ? self::STATUS_OK : self::STATUS_WARNING;
        $array[] = $this->build_setting(
            $status,
            '[INI]',
            'safe_mode',
            'https://php.net/manual/en/ini.core.php#ini.safe-mode',
            $setting,
            $req_setting,
            'on_off',
            get_lang('Safe mode is a deprecated PHP feature which (badly) limits the access of PHP scripts to other resources. It is recommended to leave it off.')
        );

        $setting = ini_get('register_globals');
        $req_setting = 0;
        $status = $setting == $req_setting ? self::STATUS_OK : self::STATUS_ERROR;
        $array[] = $this->build_setting(
            $status,
            '[INI]',
            'register_globals',
            'https://php.net/manual/en/ini.core.php#ini.register-globals',
            $setting,
            $req_setting,
            'on_off',
            get_lang('Whether to use the register globals feature or not. Using it represents potential security risks with this software.')
        );

        $setting = ini_get('short_open_tag');
        $req_setting = 0;
        $status = $setting == $req_setting ? self::STATUS_OK : self::STATUS_WARNING;
        $array[] = $this->build_setting(
            $status,
            '[INI]',
            'short_open_tag',
            'https://php.net/manual/en/ini.core.php#ini.short-open-tag',
            $setting,
            $req_setting,
            'on_off',
            get_lang('Whether to allow for short open tags to be used or not. This feature should not be used.')
        );

        $setting = ini_get('magic_quotes_gpc');
        $req_setting = 0;
        $status = $setting == $req_setting ? self::STATUS_OK : self::STATUS_ERROR;
        $array[] = $this->build_setting(
            $status,
            '[INI]',
            'magic_quotes_gpc',
            'https://php.net/manual/en/ini.core.php#ini.magic_quotes_gpc',
            $setting,
            $req_setting,
            'on_off',
            get_lang('Whether to automatically escape values from GET, POST and COOKIES arrays. A similar feature is provided for the required data inside this software, so using it provokes double slash-escaping of values.')
        );

        $setting = ini_get('display_errors');
        $req_setting = 0;
        $status = $setting == $req_setting ? self::STATUS_OK : self::STATUS_WARNING;
        $array[] = $this->build_setting(
            $status,
            '[INI]',
            'display_errors',
            'https://php.net/manual/en/ini.core.php#ini.display_errors',
            $setting,
            $req_setting,
            'on_off',
            get_lang('Show errors on screen. Turn this on on development servers, off on production servers.')
        );

        $setting = ini_get('default_charset');
        if ('' == $setting) {
            $setting = null;
        }
        $req_setting = 'UTF-8';
        $status = $setting == $req_setting ? self::STATUS_OK : self::STATUS_ERROR;
        $array[] = $this->build_setting(
            $status,
            '[INI]',
            'default_charset',
            'https://php.net/manual/en/ini.core.php#ini.default-charset',
            $setting,
            $req_setting,
            null,
            get_lang('The default character set to be sent when returning pages')
        );

        $setting = ini_get('max_execution_time');
        $req_setting = '300 ('.get_lang('minimum').')';
        $status = $setting >= 300 ? self::STATUS_OK : self::STATUS_WARNING;
        $array[] = $this->build_setting(
            $status,
            '[INI]',
            'max_execution_time',
            'https://php.net/manual/en/ini.core.php#ini.max-execution-time',
            $setting,
            $req_setting,
            null,
            get_lang('Maximum time a script can take to execute. If using more than that, the script is abandoned to avoid slowing down other users.')
        );

        $setting = ini_get('max_input_time');
        $req_setting = '300 ('.get_lang('minimum').')';
        $status = $setting >= 300 ? self::STATUS_OK : self::STATUS_WARNING;
        $array[] = $this->build_setting(
            $status,
            '[INI]',
            'max_input_time',
            'https://php.net/manual/en/ini.core.php#ini.max-input-time',
            $setting,
            $req_setting,
            null,
            get_lang('The maximum time allowed for a form to be processed by the server. If it takes longer, the process is abandonned and a blank page is returned.')
        );

        $setting = ini_get('memory_limit');
        $req_setting = '>= '.REQUIRED_MIN_MEMORY_LIMIT.'M';
        $status = self::STATUS_ERROR;
        if ((float) $setting >= REQUIRED_MIN_MEMORY_LIMIT) {
            $status = self::STATUS_OK;
        }
        $array[] = $this->build_setting(
            $status,
            '[INI]',
            'memory_limit',
            'https://php.net/manual/en/ini.core.php#ini.memory-limit',
            $setting,
            $req_setting,
            null,
            get_lang('Maximum memory limit for one single script run. If the memory needed is higher, the process will stop to avoid consuming all the server\'s available memory and thus slowing down other users.')
        );

        $setting = ini_get('post_max_size');
        $req_setting = '>= '.REQUIRED_MIN_POST_MAX_SIZE.'M';
        $status = self::STATUS_ERROR;
        if ((float) $setting >= REQUIRED_MIN_POST_MAX_SIZE) {
            $status = self::STATUS_OK;
        }
        $array[] = $this->build_setting(
            $status,
            '[INI]',
            'post_max_size',
            'https://php.net/manual/en/ini.core.php#ini.post-max-size',
            $setting,
            $req_setting,
            null,
            get_lang('This is the maximum size of uploads through forms using the POST method (i.e. classical file upload forms)')
        );

        $setting = ini_get('upload_max_filesize');
        $req_setting = '>= '.REQUIRED_MIN_UPLOAD_MAX_FILESIZE.'M';
        $status = self::STATUS_ERROR;
        if ((float) $setting >= REQUIRED_MIN_UPLOAD_MAX_FILESIZE) {
            $status = self::STATUS_OK;
        }
        $array[] = $this->build_setting(
            $status,
            '[INI]',
            'upload_max_filesize',
            'https://php.net/manual/en/ini.core.php#ini.upload_max_filesize',
            $setting,
            $req_setting,
            null,
            get_lang('Maximum volume of an uploaded file. This setting should, most of the time, be matched with the post_max_size variable.')
        );

        $setting = ini_get('upload_tmp_dir');
        $status = self::STATUS_OK;
        $array[] = $this->build_setting(
            $status,
            '[INI]',
            'upload_tmp_dir',
            'https://php.net/manual/en/ini.core.php#ini.upload_tmp_dir',
            $setting,
            '',
            null,
            get_lang('The temporary upload directory is a space on the server where files are uploaded before being filtered and treated by PHP.')
        );

        $setting = ini_get('variables_order');
        $req_setting = 'GPCS';
        $status = $setting == $req_setting ? self::STATUS_OK : self::STATUS_ERROR;
        $array[] = $this->build_setting(
            $status,
            '[INI]',
            'variables_order',
            'https://php.net/manual/en/ini.core.php#ini.variables-order',
            $setting,
            $req_setting,
            null,
            get_lang('The order of precedence of Environment, GET, POST, COOKIES and SESSION variables')
        );

        $setting = ini_get('session.gc_maxlifetime');
        $req_setting = '4320';
        $status = $setting == $req_setting ? self::STATUS_OK : self::STATUS_WARNING;
        $array[] = $this->build_setting(
            $status,
            '[SESSION]',
            'session.gc_maxlifetime',
            'https://php.net/manual/en/ini.core.php#session.gc-maxlifetime',
            $setting,
            $req_setting,
            null,
            get_lang('The session garbage collector maximum lifetime indicates which maximum time is given between two runs of the garbage collector.')
        );

        if (api_check_browscap()) {
            $setting = true;
        } else {
            $setting = false;
        }
        $req_setting = true;
        $status = $setting == $req_setting ? self::STATUS_OK : self::STATUS_WARNING;
        $array[] = $this->build_setting(
            $status,
            '[INI]',
            'browscap',
            'https://php.net/manual/en/misc.configuration.php#ini.browscap',
            $setting,
            $req_setting,
            'on_off',
            get_lang('Browscap loading browscap.ini file that contains a large amount of data on the browser and its capabilities, so it can be used by the function get_browser () PHP')
        );

        // Extensions
        $extensions = [
            'gd' => [
                'link' => 'https://php.net/gd',
                'expected' => 1,
                'comment' => get_lang('This extension must be loaded.'),
            ],
            'pdo_mysql' => [
                'link' => 'https://php.net/manual/en/ref.pdo-mysql.php',
                'expected' => 1,
                'comment' => get_lang('This extension must be loaded.'),
            ],
            'pcre' => [
                'link' => 'https://php.net/pcre',
                'expected' => 1,
                'comment' => get_lang('This extension must be loaded.'),
            ],
            'session' => [
                'link' => 'https://php.net/session',
                'expected' => 1,
                'comment' => get_lang('This extension must be loaded.'),
            ],
            'standard' => [
                'link' => 'https://php.net/spl',
                'expected' => 1,
                'comment' => get_lang('This extension must be loaded.'),
            ],
            'zlib' => [
                'link' => 'https://php.net/zlib',
                'expected' => 1,
                'comment' => get_lang('ExtensionMustBeLoaded'),
            ],
            'curl' => [
                'link' => 'https://php.net/curl',
                'expected' => 1,
                'comment' => get_lang('ExtensionMustBeLoaded'),
            ],
            'fileinfo' => [
                'link' => 'https://php.net/fileinfo',
                'expected' => 1,
                'comment' => get_lang('ExtensionMustBeLoaded'),
            ],
            'xsl' => [
                'link' => 'https://php.net/xsl',
                'expected' => 2,
                'comment' => get_lang('ExtensionShouldBeLoaded'),
            ],
            'Zend OPcache' => [
                'link' => 'https://php.net/opcache',
                'expected' => 2,
                'comment' => get_lang('This extension should be loaded.'),
            ],
            'apcu' => [
                'link' => 'https://php.net/apcu',
                'expected' => 2,
                'comment' => get_lang('This extension should be loaded.'),
            ],
            'exif' => [
                'link' => 'https://www.php.net/exif',
                'expected' => 1,
                'comment' => get_lang('This extension should be loaded.'),
            ],
            'mbstring' => [
                'link' => 'https://www.php.net/mbstring',
                'expected' => 1,
                'comment' => get_lang('This extension should be loaded.'),
            ],
            'openssl' => [ //required only for DKIM e-mail signatures
                'link' => 'https://php.net/openssl',
                'expected' => 2,
                'comment' => get_lang('ExtensionShouldBeLoaded'),
            ],
            'bcmath' => [
                'link' => 'https://php.net/bcmath',
                'expected' => 2,
                'comment' => get_lang('ExtensionShouldBeLoaded'),
            ],
        ];

        foreach ($extensions as $extension => $data) {
            $url = $data['link'];
            $expected_value = $data['expected'];
            $comment = $data['comment'];

            $loaded = extension_loaded($extension);
            $status = $loaded ? self::STATUS_OK : self::STATUS_ERROR;
            $array[] = $this->build_setting(
                $status,
                '[EXTENSION]',
                get_lang('Extension loaded').': '.$extension,
                $url,
                $loaded,
                $expected_value,
                'yes_no_optional',
                $comment
            );
        }

        return $array;
    }

    /**
     * Functions to get the data for the mysql diagnostics.
     *
     * @return array of data
     */
    public function get_database_data()
    {
        $array = [];
        $em = Database::getManager();
        $connection = $em->getConnection();
        $host = $connection->getHost();
        $db = $connection->getDatabase();
        $port = $connection->getPort();
        $driver = $connection->getDriver()->getName();

        $array[] = $this->build_setting(
            self::STATUS_INFORMATION,
            '[Database]',
            'driver',
            '',
            $driver,
            null,
            null,
            get_lang('Driver')
        );

        $array[] = $this->build_setting(
            self::STATUS_INFORMATION,
            '[Database]',
            'host',
            '',
            $host,
            null,
            null,
            get_lang('MySQL server host')
        );

        $array[] = $this->build_setting(
            self::STATUS_INFORMATION,
            '[Database]',
            'port',
            '',
            $port,
            null,
            null,
            get_lang('Port')
        );

        $array[] = $this->build_setting(
            self::STATUS_INFORMATION,
            '[Database]',
            'Database name',
            '',
            $db,
            null,
            null,
            get_lang('Name')
        );

        return $array;
    }

    /**
     * Functions to get the data for the webserver diagnostics.
     *
     * @return array of data
     */
    public function get_webserver_data()
    {
        $array = [];

        $array[] = $this->build_setting(
            self::STATUS_INFORMATION,
            '[SERVER]',
            '$_SERVER["SERVER_NAME"]',
            'http://be.php.net/reserved.variables.server',
            $_SERVER["SERVER_NAME"],
            null,
            null,
            get_lang('Server name (as used in your request)')
        );
        $array[] = $this->build_setting(
            self::STATUS_INFORMATION,
            '[SERVER]',
            '$_SERVER["SERVER_ADDR"]',
            'http://be.php.net/reserved.variables.server',
            $_SERVER["SERVER_ADDR"],
            null,
            null,
            get_lang('Server address')
        );
        $array[] = $this->build_setting(
            self::STATUS_INFORMATION,
            '[SERVER]',
            '$_SERVER["SERVER_PORT"]',
            'http://be.php.net/reserved.variables.server',
            $_SERVER["SERVER_PORT"],
            null,
            null,
            get_lang('Server port')
        );
        $array[] = $this->build_setting(
            self::STATUS_INFORMATION,
            '[SERVER]',
            '$_SERVER["SERVER_SOFTWARE"]',
            'http://be.php.net/reserved.variables.server',
            $_SERVER["SERVER_SOFTWARE"],
            null,
            null,
            get_lang('Software running as a web server')
        );
        $array[] = $this->build_setting(
            self::STATUS_INFORMATION,
            '[SERVER]',
            '$_SERVER["REMOTE_ADDR"]',
            'http://be.php.net/reserved.variables.server',
            $_SERVER["REMOTE_ADDR"],
            null,
            null,
            get_lang('Remote address (your address as received by the server)')
        );
        $array[] = $this->build_setting(
            self::STATUS_INFORMATION,
            '[SERVER]',
            '$_SERVER["HTTP_USER_AGENT"]',
            'http://be.php.net/reserved.variables.server',
            $_SERVER["HTTP_USER_AGENT"],
            null,
            null,
            get_lang('Your user agent as received by the server')
        );
        $array[] = $this->build_setting(
            self::STATUS_INFORMATION,
            '[SERVER]',
            '$_SERVER["SERVER_PROTOCOL"]',
            'http://be.php.net/reserved.variables.server',
            $_SERVER["SERVER_PROTOCOL"],
            null,
            null,
            get_lang('Protocol used by this server')
        );
        $array[] = $this->build_setting(
            self::STATUS_INFORMATION,
            '[SERVER]',
            'php_uname()',
            'http://be2.php.net/php_uname',
            php_uname(),
            null,
            null,
            get_lang('Information on the system the current server is running on')
        );
        $array[] = $this->build_setting(
            self::STATUS_INFORMATION,
            '[SERVER]',
            '$_SERVER["HTTP_X_FORWARDED_FOR"]',
            'http://be.php.net/reserved.variables.server',
            (!empty($_SERVER["HTTP_X_FORWARDED_FOR"]) ? $_SERVER["HTTP_X_FORWARDED_FOR"] : ''),
            null,
            null,
            get_lang('If the server is behind a proxy or firewall (and only in those cases), it might be using the X_FORWARDED_FOR HTTP header to show the remote user IP (yours, in this case).')
        );

        return $array;
    }

    /**
     * Functions to get the data for the courses space usage.
     *
     * @throws Exception
     *
     * @return array of data
     */
    public function get_courses_space_data()
    {
        $array = [];

        $em = Database::getManager();
        $connection = $em->getConnection();
        $res = $connection->query('SELECT id, code, directory, disk_quota, last_visit FROM course ORDER BY last_visit DESC, code LIMIT 500');
        $systemPath = api_get_path(SYS_COURSE_PATH);
        $webPath = api_get_path(WEB_COURSE_PATH);
        $courseHomeIcon = Display::return_icon('home.png', get_lang('CourseHome'));
        $courseEditIcon = Display::return_icon('edit.png', get_lang('Edit'));
        $windows = api_is_windows_os();
        $courseEditPath = api_get_path(WEB_CODE_PATH).'admin/course_edit.php?id=';
        while ($row = $res->fetch()) {
            $quota = $row['disk_quota'] / (1024 * 1024);
            $dir = $systemPath.$row['directory'].'/';
            $path = '<a href="'.$webPath.$row['code'].'/index.php?id_session=0">'.$courseHomeIcon.'</a>';
            $size = '-';
            $courseEditLink = '<a href="'.$courseEditPath.$row['id'].'">'.$courseEditIcon.'</a>';

            if (!$windows) {
                $err = [];
                $du = exec('du -s '.$dir, $err);
                list($size, $none) = explode("\t", $du);
                unset($none);
                $size = intval($size);
                if ($size < 1024) {
                    $size = 1;
                } else {
                    $size = round($size / 1024);
                }
            }
            $array[] = [
                $path,
                $row['code'],
                $size,
                round($quota),
                $courseEditLink,
                $row['last_visit'],
                $dir,
            ];
        }

        return $array;
    }

    /**
     * Functions to get the number of courses in the database.
     *
     * @throws Exception
     *
     * @return array of data
     */
    public function get_courses_space_count()
    {
        $em = Database::getManager();
        $connection = $em->getConnection();
        $res = $connection->query('SELECT count(id) FROM course');
        while ($row = $res->fetch()) {
            $count = $row[0];
        }

        return $count;
    }

    /**
     * Additional functions needed for fast integration.
     *
     * @param int    $status         Status constant defining which icon to use to illustrate the info
     * @param string $section        The name of the section this setting is included in
     * @param string $title          The name of the setting (usually a translated string)
     * @param string $url            A URL to point the user to regarding this setting, or '#' otherwise
     * @param mixed  $current_value  The current value for this setting
     * @param mixed  $expected_value The expected value for this setting
     * @param string $formatter      If this setting is expressed in some kind of format, which format to use
     * @param string $comment        A translated string explaining what this setting represents
     *
     * @return array A list of elements to show in an array's row
     */
    public function build_setting(
        $status,
        $section,
        $title,
        $url,
        $current_value,
        $expected_value,
        $formatter,
        $comment
    ) {
        switch ($status) {
            case self::STATUS_OK:
                $img = 'bullet_green.png';
                break;
            case self::STATUS_WARNING:
                $img = 'bullet_orange.png';
                break;
            case self::STATUS_ERROR:
                $img = 'bullet_red.png';
                break;
            case self::STATUS_INFORMATION:
            default:
                $img = 'bullet_blue.png';
                break;
        }

        $image = Display::return_icon($img, $status);
        $url = $this->get_link($title, $url);

        $formatted_current_value = $current_value;
        $formatted_expected_value = $expected_value;

        if ($formatter) {
            if (method_exists($this, 'format_'.$formatter)) {
                $formatted_current_value = call_user_func([$this, 'format_'.$formatter], $current_value);
                $formatted_expected_value = call_user_func([$this, 'format_'.$formatter], $expected_value);
            }
        }

        return [$image, $section, $url, $formatted_current_value, $formatted_expected_value, $comment];
    }

    /**
     * Create a link with a url and a title.
     *
     * @param $title
     * @param $url
     *
     * @return string the url
     */
    public function get_link($title, $url)
    {
        return '<a href="'.$url.'" target="about:bank">'.$title.'</a>';
    }

    /**
     * @param int $value
     *
     * @return string
     */
    public function format_yes_no_optional($value)
    {
        $return = '';
        switch ($value) {
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

    /**
     * @param $value
     *
     * @return string
     */
    public function format_yes_no($value)
    {
        return $value ? get_lang('Yes') : get_lang('No');
    }

    /**
     * @param int $value
     *
     * @return string
     */
    public function format_on_off($value)
    {
        $value = intval($value);
        if ($value > 1) {
            // Greater than 1 values are shown "as-is", they may be interpreted as "On" later.
            return $value;
        }
        // These are the values 'On' and 'Off' used in the php-ini file. Translation (get_lang()) is not needed here.
        return $value ? 'On' : 'Off';
    }
}
