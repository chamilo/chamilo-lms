<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Component\HTMLPurifier\Filter\AllowIframes;
use ChamiloSession as Session;

/**
 * This is the security library for Chamilo.
 *
 * This library is based on recommendations found in the PHP5 Certification
 * Guide published at PHP|Architect, and other recommendations found on
 * http://www.phpsec.org/
 * The principles here are that all data is tainted (most scripts of Chamilo are
 * open to the public or at least to a certain public that could be malicious
 * under specific circumstances). We use the white list approach, where as we
 * consider that data can only be used in the database or in a file if it has
 * been filtered.
 *
 * For session fixation, use ...
 * For session hijacking, use get_ua() and check_ua()
 * For Cross-Site Request Forgeries, use get_token() and check_tocken()
 * For basic filtering, use filter()
 * For files inclusions (using dynamic paths) use check_rel_path() and check_abs_path()
 *
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */

/**
 * Security class.
 *
 * Include/require it in your code and call Security::function()
 * to use its functionalities.
 *
 * This class can also be used as a container for filtered data, by creating
 * a new Security object and using $secure->filter($new_var,[more options])
 * and then using $secure->clean['var'] as a filtered equivalent, although
 * this is *not* mandatory at all.
 */
class Security
{
    public const CHAR_UPPER = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    public const CHAR_LOWER = 'abcdefghijklmnopqrstuvwxyz';
    public const CHAR_DIGITS = '0123456789';
    public const CHAR_SYMBOLS = '!"#$%&\'()*+,-./:;<=>?@[\]^_`{|}~';

    public static $clean = [];

    /**
     * Checks if the absolute path (directory) given is really under the
     * checker path (directory).
     *
     * @param string    Absolute path to be checked (with trailing slash)
     * @param string    Checker path under which the path
     * should be (absolute path, with trailing slash, get it from api_get_path(SYS_COURSE_PATH))
     *
     * @return bool True if the path is under the checker, false otherwise
     */
    public static function check_abs_path($abs_path, $checker_path)
    {
        // The checker path must be set.
        if (empty($checker_path)) {
            return false;
        }

        // Clean $abs_path.
        $abs_path = str_replace(['//', '../'], ['/', ''], $abs_path);
        $true_path = str_replace("\\", '/', realpath($abs_path));
        $checker_path = str_replace("\\", '/', realpath($checker_path));

        if (empty($checker_path)) {
            return false;
        }

        $found = strpos($true_path.'/', $checker_path);

        if ($found === 0) {
            return true;
        } else {
            // Code specific to Windows and case-insensitive behaviour
            if (api_is_windows_os()) {
                $found = stripos($true_path.'/', $checker_path);
                if ($found === 0) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Checks if the relative path (directory) given is really under the
     * checker path (directory).
     *
     * @param string    Relative path to be checked (relative to the current directory) (with trailing slash)
     * @param string    Checker path under which the path
     * should be (absolute path, with trailing slash, get it from api_get_path(SYS_COURSE_PATH))
     *
     * @return bool True if the path is under the checker, false otherwise
     */
    public static function check_rel_path($rel_path, $checker_path)
    {
        // The checker path must be set.
        if (empty($checker_path)) {
            return false;
        }
        $current_path = getcwd(); // No trailing slash.
        if (substr($rel_path, -1, 1) != '/') {
            $rel_path = '/'.$rel_path;
        }
        $abs_path = $current_path.$rel_path;
        $true_path = str_replace("\\", '/', realpath($abs_path));
        $found = strpos($true_path.'/', $checker_path);
        if ($found === 0) {
            return true;
        }

        return false;
    }

    /**
     * Filters dangerous filenames (*.php[.]?* and .htaccess) and returns it in
     * a non-executable form (for PHP and htaccess, this is still vulnerable to
     * other languages' files extensions).
     *
     * @param string $filename Unfiltered filename
     *
     * @return string
     */
    public static function filter_filename($filename)
    {
        return disable_dangerous_file($filename);
    }

    /**
     * @return string
     */
    public static function getTokenFromSession(string $prefix = '')
    {
        $secTokenVariable = self::generateSecTokenVariable($prefix);

        return Session::read($secTokenVariable);
    }

    /**
     * This function checks that the token generated in get_token() has been kept (prevents
     * Cross-Site Request Forgeries attacks).
     *
     * @param    string    The array in which to get the token ('get' or 'post')
     *
     * @return bool True if it's the right token, false otherwise
     */
    public static function check_token($requestType = 'post', FormValidator $form = null, string $prefix = '')
    {
        $secTokenVariable = self::generateSecTokenVariable($prefix);
        $sessionToken = Session::read($secTokenVariable);
        switch ($requestType) {
            case 'request':
                if (!empty($sessionToken) && isset($_REQUEST[$secTokenVariable]) && $sessionToken === $_REQUEST[$secTokenVariable]) {
                    return true;
                }

                return false;
            case 'get':
                if (!empty($sessionToken) && isset($_GET[$secTokenVariable]) && $sessionToken === $_GET[$secTokenVariable]) {
                    return true;
                }

                return false;
            case 'post':
                if (!empty($sessionToken) && isset($_POST[$secTokenVariable]) && $sessionToken === $_POST[$secTokenVariable]) {
                    return true;
                }

                return false;
            case 'form':
                $token = $form->getSubmitValue('protect_token');

                if (!empty($sessionToken) && !empty($token) && $sessionToken === $token) {
                    return true;
                }

                return false;
            default:
                if (!empty($sessionToken) && isset($requestType) && $sessionToken === $requestType) {
                    return true;
                }

                return false;
        }

        return false; // Just in case, don't let anything slip.
    }

    /**
     * Checks the user agent of the client as recorder by get_ua() to prevent
     * most session hijacking attacks.
     *
     * @return bool True if the user agent is the same, false otherwise
     */
    public static function check_ua()
    {
        $security = Session::read('sec_ua');
        $securitySeed = Session::read('sec_ua_seed');

        if ($security === $_SERVER['HTTP_USER_AGENT'].$securitySeed) {
            return true;
        }

        return false;
    }

    /**
     * Clear the security token from the session.
     */
    public static function clear_token(string $prefix = '')
    {
        $secTokenVariable = self::generateSecTokenVariable($prefix);

        Session::erase($secTokenVariable);
    }

    /**
     * This function sets a random token to be included in a form as a hidden field
     * and saves it into the user's session. Returns an HTML form element
     * This later prevents Cross-Site Request Forgeries by checking that the user is really
     * the one that sent this form in knowingly (this form hasn't been generated from
     * another website visited by the user at the same time).
     * Check the token with check_token().
     *
     * @return string Hidden-type input ready to insert into a form
     */
    public static function get_HTML_token(string $prefix = '')
    {
        $secTokenVariable = self::generateSecTokenVariable($prefix);
        $token = md5(uniqid(rand(), true));
        $string = '<input type="hidden" name="'.$secTokenVariable.'" value="'.$token.'" />';
        Session::write($secTokenVariable, $token);

        return $string;
    }

    /**
     * This function sets a random token to be included in a form as a hidden field
     * and saves it into the user's session.
     * This later prevents Cross-Site Request Forgeries by checking that the user is really
     * the one that sent this form in knowingly (this form hasn't been generated from
     * another website visited by the user at the same time).
     * Check the token with check_token().
     *
     * @return string Token
     */
    public static function get_token($prefix = '')
    {
        $secTokenVariable = self::generateSecTokenVariable($prefix);
        $token = md5(uniqid(rand(), true));
        Session::write($secTokenVariable, $token);

        return $token;
    }

    /**
     * @return string
     */
    public static function get_existing_token(string $prefix = '')
    {
        $secTokenVariable = self::generateSecTokenVariable($prefix);
        $token = Session::read($secTokenVariable);
        if (!empty($token)) {
            return $token;
        } else {
            return self::get_token($prefix);
        }
    }

    /**
     * Gets the user agent in the session to later check it with check_ua() to prevent
     * most cases of session hijacking.
     */
    public static function get_ua()
    {
        $seed = uniqid(rand(), true);
        Session::write('sec_ua_seed', $seed);
        Session::write('sec_ua', $_SERVER['HTTP_USER_AGENT'].$seed);
    }

    /**
     * This function returns a variable from the clean array. If the variable doesn't exist,
     * it returns null.
     *
     * @param string    Variable name
     *
     * @return mixed Variable or NULL on error
     */
    public static function get($varname)
    {
        if (isset(self::$clean[$varname])) {
            return self::$clean[$varname];
        }

        return null;
    }

    /**
     * This function tackles the XSS injections.
     * Filtering for XSS is very easily done by using the htmlentities() function.
     * This kind of filtering prevents JavaScript snippets to be understood as such.
     *
     * @param string The variable to filter for XSS, this params can be a string or an array (example : array(x,y))
     * @param int The user status,constant allowed (STUDENT, COURSEMANAGER, ANONYMOUS, COURSEMANAGERLOWSECURITY)
     * @param bool $filter_terms
     *
     * @return mixed Filtered string or array
     */
    public static function remove_XSS($var, $user_status = null, $filter_terms = false)
    {
        if ($filter_terms) {
            $var = self::filter_terms($var);
        }

        if (empty($user_status)) {
            if (api_is_anonymous()) {
                $user_status = ANONYMOUS;
            } else {
                if (api_is_allowed_to_edit()) {
                    $user_status = COURSEMANAGER;
                } else {
                    $user_status = STUDENT;
                }
            }
        }

        if ($user_status == COURSEMANAGERLOWSECURITY) {
            return $var; // No filtering.
        }

        static $purifier = [];
        if (!isset($purifier[$user_status])) {
            $cache_dir = api_get_path(SYS_ARCHIVE_PATH).'Serializer';
            if (!file_exists($cache_dir)) {
                $mode = api_get_permissions_for_new_directories();
                mkdir($cache_dir, $mode);
            }
            $config = HTMLPurifier_Config::createDefault();
            $config->set('Cache.SerializerPath', $cache_dir);
            $config->set('Core.Encoding', api_get_system_encoding());
            $config->set('HTML.Doctype', 'XHTML 1.0 Transitional');
            $config->set('HTML.MaxImgLength', '2560');
            $config->set('HTML.TidyLevel', 'light');
            $config->set('Core.ConvertDocumentToFragment', false);
            $config->set('Core.RemoveProcessingInstructions', true);

            if (api_get_setting('enable_iframe_inclusion') == 'true') {
                $config->set('Filter.Custom', [new AllowIframes()]);
            }

            // Shows _target attribute in anchors
            $config->set('Attr.AllowedFrameTargets', ['_blank', '_top', '_self', '_parent']);

            if ($user_status == STUDENT) {
                global $allowed_html_student;
                $config->set('HTML.SafeEmbed', true);
                $config->set('HTML.SafeObject', true);
                $config->set('Filter.YouTube', true);
                $config->set('HTML.FlashAllowFullScreen', true);
                $config->set('HTML.Allowed', $allowed_html_student);
            } elseif ($user_status == COURSEMANAGER) {
                global $allowed_html_teacher;
                $config->set('HTML.SafeEmbed', true);
                $config->set('HTML.SafeObject', true);
                $config->set('Filter.YouTube', true);
                $config->set('HTML.FlashAllowFullScreen', true);
                $config->set('HTML.Allowed', $allowed_html_teacher);
            } else {
                global $allowed_html_anonymous;
                $config->set('HTML.Allowed', $allowed_html_anonymous);
            }

            // We need it for example for the flv player (ids of surrounding div-tags have to be preserved).
            $config->set('Attr.EnableID', true);
            $config->set('CSS.AllowImportant', true);
            // We need for the flv player the css definition display: none;
            $config->set('CSS.AllowTricky', true);
            $config->set('CSS.Proprietary', true);

            // Allow uri scheme.
            $config->set('URI.AllowedSchemes', [
                'http' => true,
                'https' => true,
                'mailto' => true,
                'ftp' => true,
                'nntp' => true,
                'news' => true,
                'data' => true,
            ]);

            // Allow <video> tag
            //$config->set('HTML.Doctype', 'HTML 4.01 Transitional');
            $config->set('HTML.SafeIframe', true);

            // Set some HTML5 properties
            $config->set('HTML.DefinitionID', 'html5-definitions'); // unqiue id
            $config->set('HTML.DefinitionRev', 1);
            if ($def = $config->maybeGetRawHTMLDefinition()) {
                // https://html.spec.whatwg.org/dev/media.html#the-video-element
                $def->addElement(
                    'video',
                    'Block',
                    'Optional: (source, Flow) | (Flow, source) | Flow',
                    'Common',
                    [
                        'src' => 'URI',
                        'type' => 'Text',
                        'width' => 'Length',
                        'height' => 'Length',
                        'poster' => 'URI',
                        'preload' => 'Enum#auto,metadata,none',
                        'controls' => 'Bool',
                    ]
                );
                // https://html.spec.whatwg.org/dev/media.html#the-audio-element
                $def->addElement(
                    'audio',
                    'Block',
                    'Optional: (source, Flow) | (Flow, source) | Flow',
                    'Common',
                    [
                        'autoplay' => 'Bool',
                        'src' => 'URI',
                        'loop' => 'Bool',
                        'preload' => 'Enum#auto,metadata,none',
                        'controls' => 'Bool',
                        'muted' => 'Bool',
                    ]
                );
                $def->addElement(
                    'source',
                    'Block',
                    'Flow',
                    'Common',
                    ['src' => 'URI', 'type' => 'Text']
                );
            }

            $purifier[$user_status] = new HTMLPurifier($config);
        }

        if (is_array($var)) {
            return $purifier[$user_status]->purifyArray($var);
        } else {
            return $purifier[$user_status]->purify($var);
        }
    }

    /**
     * Filter content.
     *
     * @param string $text to be filter
     *
     * @return string
     */
    public static function filter_terms($text)
    {
        static $bad_terms = [];

        if (empty($bad_terms)) {
            $list = api_get_setting('filter_terms');
            if (!empty($list)) {
                $list = explode("\n", $list);
                $list = array_filter($list);
                if (!empty($list)) {
                    foreach ($list as $term) {
                        $term = str_replace(["\r\n", "\r", "\n", "\t"], '', $term);
                        $html_entities_value = api_htmlentities($term, ENT_QUOTES, api_get_system_encoding());
                        $bad_terms[] = $term;
                        if ($term != $html_entities_value) {
                            $bad_terms[] = $html_entities_value;
                        }
                    }
                }
                $bad_terms = array_filter($bad_terms);
            }
        }

        $replace = '***';
        if (!empty($bad_terms)) {
            // Fast way
            $new_text = str_ireplace($bad_terms, $replace, $text, $count);
            $text = $new_text;
        }

        return $text;
    }

    /**
     * This method provides specific protection (against XSS and other kinds of attacks)
     * for static images (icons) used by the system.
     * Image paths are supposed to be given by programmers - people who know what they do, anyway,
     * this method encourages a safe practice for generating icon paths, without using heavy solutions
     * based on HTMLPurifier for example.
     *
     * @param string $image_path the input path of the image, it could be relative or absolute URL
     *
     * @return string returns sanitized image path or an empty string when the image path is not secure
     *
     * @author Ivan Tcholakov, March 2011
     */
    public static function filter_img_path($image_path)
    {
        static $allowed_extensions = ['png', 'gif', 'jpg', 'jpeg', 'svg', 'webp'];
        $image_path = htmlspecialchars(trim($image_path)); // No html code is allowed.
        // We allow static images only, query strings are forbidden.
        if (strpos($image_path, '?') !== false) {
            return '';
        }
        if (($pos = strpos($image_path, ':')) !== false) {
            // Protocol has been specified, let's check it.
            if (stripos($image_path, 'javascript:') !== false) {
                // Javascript everywhere in the path is not allowed.
                return '';
            }
            // We allow only http: and https: protocols for now.
            //if (!preg_match('/^https?:\/\//i', $image_path)) {
            //    return '';
            //}
            if (stripos($image_path, 'http://') !== 0 && stripos($image_path, 'https://') !== 0) {
                return '';
            }
        }
        // We allow file extensions for images only.
        //if (!preg_match('/.+\.(png|gif|jpg|jpeg)$/i', $image_path)) {
        //    return '';
        //}
        if (($pos = strrpos($image_path, '.')) !== false) {
            if (!in_array(strtolower(substr($image_path, $pos + 1)), $allowed_extensions)) {
                return '';
            }
        } else {
            return '';
        }

        return $image_path;
    }

    /**
     * Get password requirements
     * It checks config value 'password_requirements' or uses the "classic"
     * Chamilo password requirements.
     *
     * @return array
     */
    public static function getPasswordRequirements()
    {
        // Default
        $requirements = [
            'min' => [
                'lowercase' => 0,
                'uppercase' => 0,
                'numeric' => 2,
                'length' => 5,
                'specials' => 1,
            ],
        ];

        $passwordRequirements = api_get_configuration_value('password_requirements');
        if (!empty($passwordRequirements)) {
            $requirements = $passwordRequirements;
        }

        return ['min' => $requirements['min']];
    }

    /**
     * Gets password requirements in the platform language using get_lang
     * based in platform settings. See function 'self::getPasswordRequirements'.
     */
    public static function getPasswordRequirementsToString(array $evaluatedConditions = []): string
    {
        $output = '';
        $setting = self::getPasswordRequirements();

        $passedIcon = Display::returnFontAwesomeIcon(
            'check',
            '',
            true,
            'text-success',
            get_lang('PasswordRequirementPassed')
        );
        $pendingIcon = Display::returnFontAwesomeIcon(
            'times',
            '',
            true,
            'text-danger',
            get_lang('PasswordRequirementPending')
        );

        foreach ($setting as $type => $rules) {
            foreach ($rules as $rule => $parameter) {
                if (empty($parameter)) {
                    continue;
                }

                $evaluatedCondition = $type.'_'.$rule;
                $icon = $passedIcon;

                if (array_key_exists($evaluatedCondition, $evaluatedConditions)
                    && false === $evaluatedConditions[$evaluatedCondition]
                ) {
                    $icon = $pendingIcon;
                }

                $output .= empty($evaluatedConditions) ? '' : $icon;
                $output .= sprintf(
                    get_lang(
                        'NewPasswordRequirement'.ucfirst($type).'X'.ucfirst($rule)
                    ),
                    $parameter
                );
                $output .= '<br />';
            }
        }

        return $output;
    }

    /**
     * Sanitize a string, so it can be used in the exec() command without
     * "jail-breaking" to execute other commands.
     *
     * @param string $param The string to filter
     */
    public static function sanitizeExecParam(string $param): string
    {
        $param = preg_replace('/[`;&|]/', '', $param);

        return escapeshellarg($param);
    }

    private static function generateSecTokenVariable(string $prefix = ''): string
    {
        if (empty($prefix)) {
            return 'sec_token';
        }

        return $prefix.'_sec_token';
    }
}
