<?php

/* For licensing terms, see /license.txt */

/**
 * Defines the OpenofficeDocument class, which is meant as a mother class
 * to help in the conversion of Office documents to learning paths
 * @package chamilo.learnpath
 * @author	Eric Marguin <eric.marguin@dokeos.com>
 * @author Julio Montoya
 * @license	GNU/GPL
 */

/**
 * Defines the "OpenofficeDocument" child of class "learnpath"
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
     * @param	string	Course code
     * @param	integer	Learnpath ID in DB
     * @param	integer	User ID
     */
    public function __construct($course_code = null, $resource_id = null, $user_id = null)
    {
        if ($this->debug > 0) {
            error_log('In OpenofficeDocument::OpenofficeDocument()', 0);
        }
        if (!empty($course_code) && !empty($resource_id) && !empty($user_id)) {
            parent::__construct($course_code, $resource_id, $user_id);
        }
    }

    /**
     * Calls the LibreOffice server to convert the PPTs to a set of HTML + png files in a learning path
     * @param string $file
     * @param string $action_after_conversion
     * @param string $size The size to which we want the slides to be generated
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
            $this->file_path = $this->created_dir . api_replace_dangerous_char($file['name']);
        } else {
            $this->file_path = $this->created_dir . '/' . api_replace_dangerous_char($file['name']);
        }

        //var_dump($this->file_name, $this->file_path, $this->base_work_dir, $this->created_dir);

        /*
         * Original code
        global $_course, $_user, $_configuration;

        $this->file_name = (strrpos($file['name'], '.') > 0 ? substr($file['name'], 0, strrpos($file['name'], '.')) : $file['name']);
        $this->file_name = api_replace_dangerous_char($this->file_name, 'strict');
        $this->file_name = strtolower($this->file_name);

        $visio_dir = ($action_after_conversion == 'add_docs_to_visio') ? VIDEOCONF_UPLOAD_PATH : '';

        $this->file_path = $visio_dir.'/'.$this->file_name.'.'.pathinfo($file['name'], PATHINFO_EXTENSION);

        $dir_name = $visio_dir.'/'.$this->file_name;


        // Create the directory.
        $this->base_work_dir = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document';

        $this->created_dir = create_unexisting_directory($_course, $_user['user_id'], api_get_session_id(), 0, 0, $this->base_work_dir, $dir_name);

            var_dump($this->file_name, $this->file_path, $this->base_work_dir, $this->created_dir);

        */
        if (!empty($size)) {
            list($w, $h) = explode('x', $size);
            if (!empty($w) && !empty($h)) {
                $this->slide_width = $w;
                $this->slide_height = $h;
            }
        }

        $ppt2lp_host = api_get_setting('service_ppt2lp', 'host');

        if ($ppt2lp_host == 'localhost') {
            move_uploaded_file($file['tmp_name'], $this->base_work_dir.'/'.$this->file_path);
            //var_dump( $this->base_work_dir.$this->created_dir.$this->file_path);
            $perm = api_get_setting('permissions_for_new_files');

            if (IS_WINDOWS_OS) { // IS_WINDOWS_OS has been defined in main_api.lib.php
                $converter_path = str_replace('/', '\\', api_get_path(SYS_PATH) . 'main/inc/lib/ppt2png');
                $class_path = $converter_path . ';' . $converter_path . '/jodconverter-2.2.2.jar;' . $converter_path . '/jodconverter-cli-2.2.2.jar';
                //$cmd = 'java -cp "'.$class_path.'" DokeosConverter';
                $cmd = 'java -Dfile.encoding=UTF-8 -cp "' . $class_path . '" DokeosConverter';
            } else {
                $converter_path = api_get_path(SYS_PATH) . 'main/inc/lib/ppt2png';
                //$class_path = '-cp .:jodconverter-2.2.1.jar:jodconverter-cli-2.2.1.jar';
                $class_path = ' -Dfile.encoding=UTF-8 -cp .:jodconverter-2.2.2.jar:jodconverter-cli-2.2.2.jar';
                $cmd = 'cd ' . $converter_path . ' && java ' . $class_path . ' DokeosConverter';
            }

            $cmd .= ' -p ' . api_get_setting('service_ppt2lp', 'port');
            // Call to the function implemented by child.
            $cmd .= $this->add_command_parameters();
            // To allow openoffice to manipulate docs.
            @chmod($this->base_work_dir, 0777);
            @chmod($this->base_work_dir.$this->created_dir, 0777);
            @chmod($this->base_work_dir.$this->file_path, 0777);

            $locale = $this->original_locale; // TODO: Improve it because we're not sure this locale is present everywhere.
            putenv('LC_ALL=' . $locale);

            $files = array();
            $return = 0;
            $shell = exec($cmd, $files, $return);

            if ($return != 0) { // If the java application returns an error code.
                switch ($return) {
                    // Can't connect to openoffice.
                    case 1: $this->error = get_lang('CannotConnectToOpenOffice');
                        break;
                    // Conversion failed in openoffice.
                    case 2: $this->error = get_lang('OogieConversionFailed');
                        break;
                    // Conversion can't be launch because command failed.
                    case 255: $this->error = get_lang('OogieUnknownError');
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
            chmod($this->base_work_dir . $this->created_dir, api_get_permissions_for_new_directories());
            if (!empty($result['images'])) {
                foreach ($result['images'] as $image => $img_data) {
                    $image_path = $this->base_work_dir.$this->created_dir;
                    @file_put_contents($image_path . '/' . $image, base64_decode($img_data));
                    @chmod($image_path . '/' . $image, 0777);
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
            chmod($this->base_work_dir, api_get_permissions_for_new_directories());
        }
        return $this->first_item;
    }

    /**
     * Get images files from remote host (with webservices)
     * @param   array $file current ppt file details
     * @param   string  $size The expected final size of the rendered slides
     * @return  array images files
     */
    private function _get_remote_ppt2lp_files($file, $size = null)
    {
        // host
        $ppt2lp_host = api_get_setting('service_ppt2lp', 'host');

        // secret key
        $secret_key = sha1(api_get_setting('service_ppt2lp', 'ftp_password'));

        // client
        $options = array(
            'location' => $ppt2lp_host,
            'uri' => $ppt2lp_host,
            'trace' => 1,
            'exception' => 1,
            'cache_wsdl' => WSDL_CACHE_NONE,
        );
        $client = new SoapClient(null, $options);
        $result = '';

        $file_data = base64_encode(file_get_contents($file['tmp_name']));
        $file_name = $file['name'];
        if (empty($size)) {
            $size = api_get_setting('service_ppt2lp', 'size');
        }
        $params = array(
            'secret_key' => $secret_key,
            'file_data' => $file_data,
            'file_name' => $file_name,
            'service_ppt2lp_size' => $size,
        );

        $result = $client->__call('wsConvertPpt', array('pptData' => $params));

        return $result;
    }

    abstract function make_lp();

    abstract function add_docs_to_visio();

    abstract function add_command_parameters();

    /**
     * Used to convert copied from document
     * @param string $originalPath
     * @param string $convertedPath
     * @param string $convertedTitle
     * @return bool
     */
    function convertCopyDocument($originalPath, $convertedPath, $convertedTitle)
    {
        $_course = api_get_course_info();
        $ids = array();
        $originalPathInfo = pathinfo($originalPath);
        $convertedPathInfo = pathinfo($convertedPath);
        $this->base_work_dir = $originalPathInfo['dirname'];
        $this->file_path = $originalPathInfo['basename'];
        $this->created_dir = $convertedPathInfo['basename'];
        $ppt2lpHost = api_get_setting('service_ppt2lp', 'host');
        $permissionFile = api_get_permissions_for_new_files();
        $permissionFolder = api_get_permissions_for_new_directories();
        if (file_exists($this->base_work_dir . '/' . $this->created_dir)) {

            return $ids;
        }

        if ($ppt2lpHost == 'localhost') {
            if (IS_WINDOWS_OS) { // IS_WINDOWS_OS has been defined in main_api.lib.php
                $converterPath = str_replace('/', '\\', api_get_path(SYS_PATH) . 'main/inc/lib/ppt2png');
                $classPath = $converterPath . ';' . $converterPath . '/jodconverter-2.2.2.jar;' . $converterPath . '/jodconverter-cli-2.2.2.jar';
                $cmd = 'java -Dfile.encoding=UTF-8 -jar "' . $classPath . '/jodconverter-2.2.2.jar"';
            } else {
                $converterPath = api_get_path(SYS_PATH) . 'main/inc/lib/ppt2png';
                $classPath = ' -Dfile.encoding=UTF-8 -jar jodconverter-cli-2.2.2.jar';
                $cmd = 'cd ' . $converterPath . ' && java ' . $classPath . ' ';
            }

            $cmd .= ' -p ' . api_get_setting('service_ppt2lp', 'port');
            // Call to the function implemented by child.
            $cmd .= ' "' . $this->base_work_dir . '/' . $this->file_path . '"  "' . $this->base_work_dir . '/' . $this->created_dir . '"';
            // To allow openoffice to manipulate docs.
            @chmod($this->base_work_dir, $permissionFolder);
            @chmod($this->base_work_dir . '/' . $this->file_path, $permissionFile);

            $locale = $this->original_locale; // TODO: Improve it because we're not sure this locale is present everywhere.
            putenv('LC_ALL=' . $locale);

            $files = array();
            $return = 0;
            $shell = exec($cmd, $files, $return);
            // TODO: Chown is not working, root keep user privileges, should be www-data
            @chown($this->base_work_dir . '/' . $this->created_dir, 'www-data');
            @chmod($this->base_work_dir . '/' . $this->created_dir, $permissionFile);

            if ($return != 0) { // If the java application returns an error code.
                switch ($return) {
                    // Can't connect to openoffice.
                    case 1: $this->error = get_lang('CannotConnectToOpenOffice');
                        break;
                    // Conversion failed in openoffice.
                    case 2: $this->error = get_lang('OogieConversionFailed');
                        break;
                    // Conversion can't be launch because command failed.
                    case 255: $this->error = get_lang('OogieUnknownError');
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

        if (file_exists($this->base_work_dir . '/' . $this->created_dir)) {

            // Register Files to Document tool
            $ids[] = add_document(
                $_course,
                '/' . $this->created_dir,
                'file',
                filesize($this->base_work_dir . '/' . $this->created_dir),
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
}
