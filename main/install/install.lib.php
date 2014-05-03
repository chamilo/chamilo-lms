<?php
/* For licensing terms, see /license.txt */

/**
 * Chamilo LMS
 * This file contains functions used by the install and upgrade scripts.
 */



/**
 * Check if current system is allowed to install
 * @return bool
 */
function checkRequiredSettings()
{
    $requirements = getRequirements();
    $requiredSettings = $requirements['required'];

    foreach ($requiredSettings as $extension => $options) {
        if (!extension_loaded($extension)) {
            return false;
        }
    }

    return true;
}


/**
 * @param Symfony\Component\Translation\Translator $translator
 * @return null|string
 */
function drawRequirements($translator)
{
    $requirements = getRequirements();
    $html = null;
    $html .= '<tr>
                <td>
                    '.$translator->trans('Required').'
                </td>
                <td>
                </td>
              </tr>';

    foreach ($requirements['required'] as $extension => $req) {
        $checkExtension = check_extension(
            $extension,
            $translator->trans('Yes'),
            $translator->trans('No')
        );
        $html .= '<tr>
                    <td>
                        <a href="'.$req['url'].'">'.$extension.'</a>
                    </td>
                    <td>
                        '.$checkExtension.'
                    </td>
                  </tr>';
    }

    $html .= '<tr>
                <td>
                    '.$translator->trans('Optional').'
                </td>
                <td>
                </td>
              </tr>';

    foreach ($requirements['optional'] as $extension => $req) {

        $checkExtension = check_extension(
            $extension,
            $translator->trans('Yes'),
            $translator->trans('No')
        );

        $html .= '<tr>
                    <td>
                        <a href="'.$req['url'].'">'.$extension.'</a>
                    </td>
                    <td>
                        '.$checkExtension.'
                    </td>
                  </tr>';
    }

    return $html;
}

function drawOptions($translator)
{
    $options = getOptions($translator);
    $html = null;
    foreach ($options as $option) {
        $html .= '<tr>
                    <td>
                        <a href="'.$option['url'].'">'.$option['name'].'</a>
                    </td>
                    <td>
                        '.$option['recommended'].'
                    </td>
                    <td>
                        '.$option['current'].'
                    </td>
                  </tr>';
    }

    return $html;
}



function getRequirements()
{
    return
        array(
            'required' => array(
                //'session' => array('url' => 'http://php.net/manual/en/book.session.php', 'recommend' => Display::label('OFF', 'success')),
                'mysql' => array('url' => 'http://php.net/manual/en/book.mysql.php'),
                'curl' => array('url' => 'http://php.net/manual/fr/book.curl.php'),
                'zlib' => array('url' => 'http://php.net/manual/en/book.zlib.php'),
                'pcre' => array('url' => 'http://php.net/manual/en/book.pcre.php'),
                'xml' => array('url' => 'http://php.net/manual/en/book.xml.php'),
                'mbstring' => array('url' => 'http://php.net/manual/en/book.mbstring.php'),
                'iconv' => array('url' => 'http://php.net/manual/en/book.iconv.php'),
                'intl' => array('url' => 'http://php.net/manual/en/book.intl.php'),
                'gd' => array('url' => 'http://php.net/manual/en/book.image.php'),
                'json' => array('url' => 'http://php.net/manual/en/book.json.php')
            ),
            'optional' =>  array(
                'imagick' => array('url' => 'http://php.net/manual/en/book.imagick.php'),
                'ldap' => array('url' => 'http://php.net/manual/en/book.ldap.php'),
                'xapian' => array('url' => 'http://php.net/manual/en/book.xapian.php')
            )
        );
}

/**
 * @param Symfony\Component\Translation\Translator $translator
 * @return array
 */
function getOptions($translator)
{
    return array(
        array(
            'name' => 'Safe Mode',
            'url' => 'http://php.net/manual/features.safe-mode.php',
            'recommended' => Display::label('OFF', 'success'),
            'current' => check_php_setting('safe_mode', 'OFF'),
        ),
        array(
            'name' => 'Display Errors',
            'url' => 'http://php.net/manual/ref.errorfunc.php#ini.display-errors',
            'recommended' => Display::label('ON', 'success'),
            'current' => check_php_setting('display_errors', 'OFF'),
        ),
        array(
            'name' => 'File Uploads',
            'url' => 'http://php.net/manual/ini.core.php#ini.file-uploads',
            'recommended' => Display::label('OFF', 'success'),
            'current' => check_php_setting('file_uploads', 'ON'),
        ),
        array(
            'name' => 'Magic Quotes GPC',
            'url' => 'http://php.net/manual/ref.info.php#ini.magic-quotes-gpc',
            'recommended' => Display::label('OFF', 'success'),
            'current' => check_php_setting('magic_quotes_gpc', 'OFF'),
        ),
        array(
            'name' => 'Magic Quotes Runtime',
            'url' => 'http://php.net/manual/ref.info.php#ini.magic-quotes-runtime',
            'recommended' => Display::label('OFF', 'success'),
            'current' => check_php_setting('magic_quotes_runtime', 'OFF'),
        ),
        array(
            'name' => 'Register Globals',
            'url' => 'http://php.net/manual/security.globals.php',
            'recommended' => Display::label('OFF', 'success'),
            'current' => check_php_setting('register_globals', 'OFF'),
        ),
        array(
            'name' => 'Session auto start',
            'url' => 'http://php.net/manual/ref.session.php#ini.session.auto-start',
            'recommended' => Display::label('OFF', 'success'),
            'current' => check_php_setting('auto_start', 'OFF'),
        ),
        array(
            'name' => 'Short Open Tag',
            'url' => 'http://php.net/manual/ini.core.php#ini.short-open-tag',
            'recommended' => Display::label('OFF', 'success'),
            'current' => check_php_setting('short_open_tag', 'OFF'),
        ),
        array(
            'name' => 'Cookie HTTP Only',
            'url' => 'http://www.php.net/manual/en/session.configuration.php#ini.session.cookie-httponly',
            'recommended' => Display::label('ON', 'success'),
            'current' => check_php_setting('session.cookie_httponly', 'ON'),
        ),
        array(
            'name' => 'Maximum upload file size',
            'url' => 'http://php.net/manual/ini.core.php#ini.upload-max-filesize',
            'recommended' => Display::label('>= '.REQUIRED_MIN_UPLOAD_MAX_FILESIZE.'M', 'success'),
            'current' => compare_setting_values(ini_get('upload_max_filesize'), REQUIRED_MIN_UPLOAD_MAX_FILESIZE),
        ),
        array(
            'name' => 'Maximum post size',
            'url' => 'http://php.net/manual/ini.core.php#ini.post-max-size',
            'recommended' => Display::label('>= '.REQUIRED_MIN_POST_MAX_SIZE.'M', 'success'),
            'current' => compare_setting_values(ini_get('post_max_size'), REQUIRED_MIN_POST_MAX_SIZE),
        ),
        array(
            'name' => 'Memory Limit',
            'url' => 'http://www.php.net/manual/en/ini.core.php#ini.memory-limit',
            'recommended' => Display::label('>= '.REQUIRED_MIN_MEMORY_LIMIT.'M', 'success'),
            'current' => compare_setting_values(ini_get('memory_limit'), REQUIRED_MIN_MEMORY_LIMIT),
        )
    );
}

function translate($variable)
{
    global $app;

    return $app['translator']->trans($variable);
}

/**
 * This function checks if a php extension exists or not and returns an HTML status string.
 *
 * @param   string  Name of the PHP extension to be checked
 * @param   string  Text to show when extension is available (defaults to 'Yes')
 * @param   string  Text to show when extension is available (defaults to 'No')
 * @param   boolean Whether this extension is optional (in this case show unavailable text in orange rather than red)
 * @return  string  HTML string reporting the status of this extension. Language-aware.
 * @author  Christophe Gesch??
 * @author  Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @author  Yannick Warnier <yannick.warnier@dokeos.com>
 * @version Dokeos 1.8.1, May 2007
 */
function check_extension($extension_name, $return_success = 'Yes', $return_failure = 'No', $optional = false)
{
    if (extension_loaded($extension_name)) {
        return Display::label($return_success, 'success');
    } else {
        if ($optional) {
            return Display::label($return_failure, 'warning');
            //return '<strong><font color="#ff9900">'.$return_failure.'</font></strong>';
        } else {
            return Display::label($return_failure, 'important');
            //return '<strong><font color="red">'.$return_failure.'</font></strong>';
        }
    }
}


/**
 * This function checks whether a php setting matches the recommended value
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version Dokeos 1.8, august 2006
 */
function check_php_setting($php_setting, $recommended_value, $return_success = false, $return_failure = false)
{
    $current_php_value = get_php_setting($php_setting);
    if ($current_php_value == $recommended_value) {
        return Display::label($current_php_value.' '.$return_success, 'success');
    } else {
        return Display::label($current_php_value.' '.$return_success, 'important');
    }
}


/**
 * Returns a textual value ('ON' or 'OFF') based on a requester 2-state ini- configuration setting.
 *
 * @param string $val a php ini value
 * @return boolean: ON or OFF
 * @author Joomla <http://www.joomla.org>
 */
function get_php_setting($val)
{
    return ini_get($val) == '1' ? 'ON' : 'OFF';
}

function compare_setting_values($current_value, $wanted_value)
{
    $current_value_string = $current_value;
    $current_value = (float) $current_value;
    $wanted_value = (float) $wanted_value;

    if ($current_value >= $wanted_value) {
        return Display::label($current_value_string, 'success');
    } else {
        return Display::label($current_value_string, 'important');
    }
}



function drawPermissionsSettings($app)
{
    $html  = null;

    // DIRECTORY AND FILE PERMISSIONS
    $html .= '<div class="RequirementContent">';

    $course_attempt_name = '__XxTestxX__';
    $course_dir = api_get_path(SYS_COURSE_PATH).$course_attempt_name;

    // Just in case.
    if (is_file($course_dir.'/test.txt')) {
        unlink($course_dir.'/test.txt');
    }
    if (is_dir($course_dir)) {
        rmdir($course_dir);
    }

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
            $r = touch($course_dir.'/test.php', $perm);
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

    $app['session']->set('permissions_for_new_directories', decoct($dir_perm_verified));
    $app['session']->set('permissions_for_new_files', decoct($fil_perm_verified));

    $dir_perm = Display::label('0'.decoct($dir_perm_verified), 'info');
    $file_perm = Display::label('0'.decoct($fil_perm_verified), 'info');

    $course_test_was_created  = ($course_test_was_created == true && $file_course_test_was_created == true) ? Display::label(translate('Yes'), 'success') : Display::label(translate('No'), 'important');

    $html .= '<table class="table">
            <tr>
                <td class="requirements-item">[chamilo]/config</td>
                <td class="requirements-value">'.check_writable_root_path('config/').'</td>
            </tr>
            <tr>
                <td class="requirements-item">[chamilo]/data</td>
                <td class="requirements-value">'.check_writable_root_path('data').'</td>
            </tr>
            <tr>
                <td class="requirements-item">[chamilo]/logs</td>
                <td class="requirements-value">'.check_writable_root_path('logs').'</td>
            </tr>
            <tr>
                <td class="requirements-item">'.translate('CourseTestWasCreated').'</td>
                <td class="requirements-value">'.$course_test_was_created.' </td>
            </tr>
            <tr>
                <td class="requirements-item">'.translate('PermissionsForNewDirs').'</td>
                <td class="requirements-value">'.$dir_perm.' </td>
            </tr>
            <tr>
                <td class="requirements-item">'.translate('PermissionsForNewFiles').'</td>
                <td class="requirements-value">'.$file_perm.' </td>
            </tr>';

    $html .= '    </table>';
    $html .= '  </div>';
    $html .= '</div>';

    $error = false;
    // First, attempt to set writing permissions if we don't have them yet
    $perm = $app['session']->get('permissions_for_new_directories');
    $perm_file = $app['session']->get('permissions_for_new_files');

    $notwritable = array();

    $checked_writable = api_get_path(SYS_CONFIG_PATH);
    if (!is_writable($checked_writable)) {
        $notwritable[] = $checked_writable;
        @chmod($checked_writable, $perm);
    }

    $checked_writable = api_get_path(SYS_DATA_PATH);
    if (!is_writable($checked_writable)) {
        $notwritable[] = $checked_writable;
        @chmod($checked_writable, $perm);
    }

    $checked_writable = api_get_path(SYS_DEFAULT_COURSE_DOCUMENT_PATH).'images/';
    if (!is_writable($checked_writable)) {
        $notwritable[] = $checked_writable;
        @chmod($checked_writable, $perm);
    }

    $checked_writable = api_get_path(SYS_ARCHIVE_PATH);
    if (!is_writable($checked_writable)) {
        $notwritable[] = $checked_writable;
        @chmod($checked_writable, $perm);
    }

    $checked_writable = api_get_path(SYS_LOG_PATH);
    if (!is_writable($checked_writable)) {
        $notwritable[] = $checked_writable;
        @chmod($checked_writable, $perm);
    }

    /*$checked_writable = api_get_path(SYS_COURSE_PATH);
    if (!is_writable($checked_writable)) {
        $notwritable[] = $checked_writable;
        @chmod($checked_writable, $perm);
    }*/

    if ($course_test_was_created == false || $file_course_test_was_created == false) {
        $error = true;
    }

    /*$checked_writable = api_get_path(SYS_PATH).'home/';
    if (!is_writable($checked_writable)) {
        $notwritable[] = realpath($checked_writable);
        @chmod($checked_writable, $perm);
    }*/

    /*$checked_writable = api_get_path(CONFIGURATION_PATH).'configuration.php';
    if (file_exists($checked_writable) && !is_writable($checked_writable)) {
        $notwritable[] = $checked_writable;
        @chmod($checked_writable, $perm_file);
    }*/

    // Second, if this fails, report an error

    // The user would have to adjust the permissions manually

    if (count($notwritable) > 0) {
        $html .= '<div class="error-message">';
        $html .= '<center><h3>'.translate('Warning').'</h3></center>';
        $html .=  sprintf(
            translate('NoWritePermissionPleaseReadInstallGuide'),
            '</font>
            <a href="../../documentation/installation_guide.html" target="blank">',
            '</a> <font color="red">'
        );
        $html .= '</div>';

        $html .= '<ul>';
        foreach ($notwritable as $value) {
            $html .= '<li>'.$value.'</li>';
        }
        $html .= '</ul>';
    } elseif (file_exists(api_get_path(CONFIGURATION_PATH).'configuration.php')) {
        // Check wether a Chamilo configuration file already exists.
        $html .= '<div class="warning-message"><h4><center>';
        $html .= translate('WarningExistingDokeosInstallationDetected');
        $html .= '</center></h4></div>';
    }

    return $html;
}



function check_course_script_interpretation($course_dir, $course_attempt_name, $file = 'test.php')
{
    $output = false;
    //Write in file
    $file_name = $course_dir.'/'.$file;
    $content = '<?php echo "123"; exit;';

    if (is_writable($file_name)) {
        if ($handler = @fopen($file_name, "w")) {
            //write content
            if (fwrite($handler , $content)) {

                $file = api_get_path(SYS_COURSE_PATH).$course_attempt_name.'/'.$file;
                if (file_exists($file)) {
                    return true;
                }

                //You can't access to a course file like this. You will be prompted to the installation process.
                //If you access
                $sock_errno = '';
                $sock_errmsg = '';

                $url = api_get_path(WEB_COURSE_PATH).$course_attempt_name.'/'.$file;

                $parsed_url = parse_url($url);
                //$scheme = isset($parsedUrl['scheme']) ? $parsedUrl['scheme'] : ''; //http
                $host = isset($parsed_url['host']) ? $parsed_url['host'] : '';
                $path = isset($parsed_url['path']) ? $parsed_url['path'] : '/';
                $port = isset($parsed_url['port']) ? $parsed_url['port'] : '80';

                //Check fsockopen
                if ($fp = @fsockopen(str_replace('http://', '', $url), -1, $sock_errno, $sock_errmsg, 60)) {
                    $out  = "GET $path HTTP/1.1\r\n";
                    $out .= "Host: $host\r\n";
                    $out .= "Connection: Close\r\n\r\n";

                    fwrite($fp, $out);
                    while (!feof($fp)) {
                        $result = str_replace("\r\n", '', fgets($fp, 128));
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
                    $result = curl_exec($ch);
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


/**
 * This function checks if the given folder is writable
 */
function check_writable_root_path($folder, $suggestion = false)
{
    if (is_writable(api_get_path(SYS_PATH).$folder)) {
        return Display::label(translate('Writable'), 'success');
    } else {
        if ($suggestion) {
            return Display::label(translate('NotWritable'), 'info');
        } else {
            return Display::label(translate('NotWritable'), 'important');
        }
    }
}
