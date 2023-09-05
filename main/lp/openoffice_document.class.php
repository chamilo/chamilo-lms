<?php

/* For licensing terms, see /license.txt */

/**
 * Defines the OpenofficeDocument class, which is meant as a mother class
 * to help in the conversion of Office documents to learning paths.
 *
 * @package chamilo.learnpath
 *
 * @author	Eric Marguin <eric.marguin@dokeos.com>
 * @author Julio Montoya
 * @license	GNU/GPL
 */

/**
 * Defines the "OpenofficeDocument" child of class "learnpath".
 */
abstract class OpenofficeDocument extends learnpath
{
    public $first_item = 0;
    public $original_charset = 'utf-8';
    public $original_locale = 'en_US.UTF-8';
    public $slide_width;
    public $slide_height;

    /**
     * Class constructor. Based on the parent constructor.
     *
     * @param	string	Course code
     * @param	int	Learnpath ID in DB
     * @param	int	User ID
     */
    public function __construct($course_code = null, $resource_id = null, $user_id = null)
    {
        if (!empty($course_code) && !empty($resource_id) && !empty($user_id)) {
            parent::__construct($course_code, $resource_id, $user_id);
        }
    }

    /**
     * Calls the LibreOffice server to convert the PPTs to a set of HTML + png files in a learning path.
     *
     * @param string $file
     * @param string $action_after_conversion
     * @param string $size                    The size to which we want the slides to be generated
     *
     * @return bool|int
     */
    public function convert_document($file, $action_after_conversion = 'make_lp', $size = null)
    {
        $_course = api_get_course_info();
        $this->file_name = pathinfo($file['name'], PATHINFO_FILENAME);
        // Create the directory
        $result = $this->generate_lp_folder($_course, $this->file_name);

        // Create the directory
        $this->base_work_dir = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document';
        ///learning_path/ppt_dirname directory
        $this->created_dir = $result['dir'];
        if (substr($this->created_dir, -1, 1) == '/') {
            $this->file_path = $this->created_dir.api_replace_dangerous_char($file['name']);
        } else {
            $this->file_path = $this->created_dir.'/'.api_replace_dangerous_char($file['name']);
        }

        $dirMode = api_get_permissions_for_new_directories();
        $fileMode = api_get_permissions_for_new_files();

        if (!empty($size)) {
            list($w, $h) = explode('x', $size);
            if (!empty($w) && !empty($h)) {
                $this->slide_width = (int) $w;
                $this->slide_height = (int) $h;
            }
        }

        $ppt2lp_host = api_get_setting('service_ppt2lp', 'host');

        if ($ppt2lp_host == 'localhost') {
            move_uploaded_file($file['tmp_name'], $this->base_work_dir.'/'.$this->file_path);
            $perm = api_get_setting('permissions_for_new_files');

            if (IS_WINDOWS_OS) { // IS_WINDOWS_OS has been defined in main_api.lib.php
                $converter_path = str_replace('/', '\\', api_get_path(SYS_PATH).'main/inc/lib/ppt2png');
                $class_path = $converter_path.';'.$converter_path.'/jodconverter-2.2.2.jar;'.$converter_path.'/jodconverter-cli-2.2.2.jar';
                //$cmd = 'java -cp "'.$class_path.'" DokeosConverter';
                $cmd = 'java -Dfile.encoding=UTF-8 -cp "'.$class_path.'" DokeosConverter';
            } else {
                $converter_path = api_get_path(SYS_PATH).'main/inc/lib/ppt2png';
                //$class_path = '-cp .:jodconverter-2.2.1.jar:jodconverter-cli-2.2.1.jar';
                $class_path = ' -Dfile.encoding=UTF-8 -cp .:jodconverter-2.2.2.jar:jodconverter-cli-2.2.2.jar';
                $cmd = 'cd '.$converter_path.' && java '.$class_path.' DokeosConverter';
            }

            $cmd .= ' -p '.api_get_setting('service_ppt2lp', 'port');
            // Call to the function implemented by child.
            $cmd .= $this->add_command_parameters();
            // To allow openoffice to manipulate docs.
            @chmod($this->base_work_dir, $dirMode);
            @chmod($this->base_work_dir.$this->created_dir, $dirMode);
            @chmod($this->base_work_dir.$this->file_path, $fileMode);

            $locale = $this->original_locale; // TODO: Improve it because we're not sure this locale is present everywhere.
            putenv('LC_ALL='.$locale);

            $files = [];
            $return = 0;
            $cmd = escapeshellcmd($cmd);
            $shell = exec($cmd, $files, $return);

            if ($return != 0) { // If the java application returns an error code.
                switch ($return) {
                    case 1:
                        // Can't connect to openoffice.
                        $this->error = get_lang('CannotConnectToOpenOffice');
                        break;
                    case 2:
                        // Conversion failed in openoffice.
                        $this->error = get_lang('OogieConversionFailed');
                        break;
                    case 255:
                        // Conversion can't be launch because command failed.
                        $this->error = get_lang('OogieUnknownError');
                        break;
                }
                DocumentManager::delete_document($_course, $this->created_dir, $this->base_work_dir);

                return false;
            }
        } else {
            // get result from webservices
            $result = $this->_get_remote_ppt2lp_files($file, $size);
            $result = unserialize($result);
            // Save remote images to server
            chmod($this->base_work_dir.$this->created_dir, api_get_permissions_for_new_directories());
            if (!empty($result['images'])) {
                foreach ($result['images'] as $image => $img_data) {
                    $image_path = $this->base_work_dir.$this->created_dir;
                    @file_put_contents($image_path.'/'.$image, base64_decode($img_data));
                    @chmod($image_path.'/'.$image, $fileMode);
                }
            }

            // files info
            $files = $result['files'];
        }

        if (!empty($files)) {
            // Create lp
            $this->lp_id = learnpath::add_lp($_course['id'], $this->file_name, '', 'guess', 'manual');
            // make sure we have a course code available for later
            $this->cc = $_course['id'];
            $this->course_info = $_course;

            // Call to the function implemented by child following action_after_conversion parameter.
            switch ($action_after_conversion) {
                case 'make_lp':
                    $this->make_lp($files);
                    break;
                case 'add_docs_to_visio':
                    $this->add_docs_to_visio($files);
                    break;
            }
            @chmod($this->base_work_dir, api_get_permissions_for_new_directories());
        }

        return $this->first_item;
    }

    abstract public function make_lp();

    abstract public function add_docs_to_visio();

    abstract public function add_command_parameters();

    /**
     * Used to convert copied from document.
     *
     * @param string $originalPath
     * @param string $convertedPath
     * @param string $convertedTitle
     *
     * @return bool
     */
    public function convertCopyDocument($originalPath, $convertedPath, $convertedTitle)
    {
        $_course = api_get_course_info();
        $ids = [];
        $originalPathInfo = pathinfo($originalPath);
        $convertedPathInfo = pathinfo($convertedPath);
        $this->base_work_dir = $originalPathInfo['dirname'];
        $this->file_path = $originalPathInfo['basename'];
        $this->created_dir = $convertedPathInfo['basename'];
        $ppt2lpHost = api_get_setting('service_ppt2lp', 'host');
        $permissionFile = api_get_permissions_for_new_files();
        $permissionFolder = api_get_permissions_for_new_directories();
        if (file_exists($this->base_work_dir.'/'.$this->created_dir)) {
            return $ids;
        }

        if ($ppt2lpHost == 'localhost') {
            if (IS_WINDOWS_OS) { // IS_WINDOWS_OS has been defined in main_api.lib.php
                $converterPath = str_replace('/', '\\', api_get_path(SYS_PATH).'main/inc/lib/ppt2png');
                $classPath = $converterPath.';'.$converterPath.'/jodconverter-2.2.2.jar;'.$converterPath.'/jodconverter-cli-2.2.2.jar';
                $cmd = 'java -Dfile.encoding=UTF-8 -jar "'.$classPath.'/jodconverter-2.2.2.jar"';
            } else {
                $converterPath = api_get_path(SYS_PATH).'main/inc/lib/ppt2png';
                $classPath = ' -Dfile.encoding=UTF-8 -jar jodconverter-cli-2.2.2.jar';
                $cmd = 'cd '.$converterPath.' && java '.$classPath.' ';
            }

            $cmd .= ' -p '.api_get_setting('service_ppt2lp', 'port');
            // Call to the function implemented by child.
            $cmd .= ' "'.Security::sanitizeExecParam($this->base_work_dir.'/'.$this->file_path)
                .'"  "'
                .Security::sanitizeExecParam($this->base_work_dir.'/'.$this->created_dir).'"';
            // To allow openoffice to manipulate docs.
            @chmod($this->base_work_dir, $permissionFolder);
            @chmod($this->base_work_dir.'/'.$this->file_path, $permissionFile);

            $locale = $this->original_locale; // TODO: Improve it because we're not sure this locale is present everywhere.
            putenv('LC_ALL='.$locale);

            $files = [];
            $return = 0;
            $cmd = escapeshellcmd($cmd);
            $shell = exec($cmd, $files, $return);
            // TODO: Chown is not working, root keep user privileges, should be www-data
            @chown($this->base_work_dir.'/'.$this->created_dir, 'www-data');
            @chmod($this->base_work_dir.'/'.$this->created_dir, $permissionFile);

            if ($return != 0) { // If the java application returns an error code.
                switch ($return) {
                    case 1:
                        // Can't connect to openoffice.
                        $this->error = get_lang('CannotConnectToOpenOffice');
                        break;
                    case 2:
                        // Conversion failed in openoffice.
                        $this->error = get_lang('OogieConversionFailed');
                        break;
                    case 255:
                        // Conversion can't be launch because command failed.
                        $this->error = get_lang('OogieUnknownError');
                        break;
                }
                DocumentManager::delete_document($_course, $this->created_dir, $this->base_work_dir);

                return false;
            }
        } else {
            /*
             * @TODO Create method to use webservice
            // get result from webservices
            $result = $this->_get_remote_ppt2lp_files($file);
            $result = unserialize(base64_decode($result));

            // Save remote images to server
            chmod($this->base_work_dir.$this->created_dir, api_get_permissions_for_new_directories());
            if (!empty($result['images'])) {
                foreach ($result['images'] as $image => $img_data) {
                    $image_path = $this->base_work_dir.$this->created_dir;
                    @file_put_contents($image_path . '/' . $image, base64_decode($img_data));
                    @chmod($image_path . '/' . $image, 0777);
                }
            }

            // files info
            $files = $result['files'];
            */
        }

        if (file_exists($this->base_work_dir.'/'.$this->created_dir)) {
            // Register Files to Document tool
            $ids[] = add_document(
                $_course,
                '/'.$this->created_dir,
                'file',
                filesize($this->base_work_dir.'/'.$this->created_dir),
                $convertedTitle,
                sprintf(
                    get_lang('FileConvertedFromXToY'),
                    strtoupper($originalPathInfo['extension']),
                    strtoupper($convertedPathInfo['extension'])
                ),
                0,
                true,
                null,
                api_get_session_id()
            );
            chmod($this->base_work_dir, $permissionFolder);
        }

        return $ids;
    }

    /**
     * Get images files from remote host (with webservices).
     *
     * @param array  $file current ppt file details
     * @param string $size The expected final size of the rendered slides
     *
     * @return array images files
     */
    private function _get_remote_ppt2lp_files($file, $size = null)
    {
        // host
        $ppt2lp_host = api_get_setting('service_ppt2lp', 'host');
        // SOAP URI (just the host)
        $matches = [];
        $uri = '';
        $result = preg_match('/^([a-zA-Z0-9]*):\/\/([^\/]*)\//', $ppt2lp_host, $matches);
        if ($result) {
            $uri = $matches[1].'://'.$matches[2].'/';
        } else {
            $uri = $ppt2lp_host;
        }
        // secret key
        $secret_key = sha1(api_get_setting('service_ppt2lp', 'ftp_password'));

        // client
        $options = [
            'location' => $ppt2lp_host,
            'uri' => $uri,
            'trace' => 1,
            'exceptions' => true,
            'cache_wsdl' => WSDL_CACHE_NONE,
            'keep_alive' => false,
            'features' => SOAP_SINGLE_ELEMENT_ARRAYS,
            'compression' => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP | 9,
        ];
        if (substr($ppt2lp_host, 0, 5) === 'https') {
            $options['ssl_method'] = SOAP_SSL_METHOD_TLS;
            // If using SSL, please note that *not* supporting the SSLv2
            // (broken in terms of security), the server tends to generate
            // the following issue:
            // SoapClient::__doRequest(): SSL: Connection reset by peer
        }
        $client = new SoapClient(null, $options);
        $result = '';
        $file_data = base64_encode(file_get_contents($file['tmp_name']));
        $file_name = $file['name'];
        if (empty($size)) {
            $size = api_get_setting('service_ppt2lp', 'size');
        }
        $params = [
            'secret_key' => $secret_key,
            'file_data' => $file_data,
            'file_name' => $file_name,
            'service_ppt2lp_size' => $size,
        ];

        try {
            $result = $client->wsConvertPpt($params);
        } catch (Exception $e) {
            error_log('['.time().'] Chamilo SOAP call error: '.$e->getMessage());
        }
        // Make sure we destroy the SOAP client as it may generate SSL connection
        // binding issue (if using SSL)
        unset($client);

        return $result;
    }
}
