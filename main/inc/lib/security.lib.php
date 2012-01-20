<?php
/* For licensing terms, see /license.txt */

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
 * @package chamilo.library
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */

/**
 * Security class
 *
 * Include/require it in your code and call Security::function()
 * to use its functionalities.
 *
 * This class can also be used as a container for filtered data, by creating
 * a new Security object and using $secure->filter($new_var,[more options])
 * and then using $secure->clean['var'] as a filtered equivalent, although
 * this is *not* mandatory at all.
 */
class Security {
    public static $clean = array();

    /**
     * Checks if the absolute path (directory) given is really under the
     * checker path (directory)
     * @param	string	Absolute path to be checked (with trailing slash)
     * @param	string	Checker path under which the path should be (absolute path, with trailing slash, get it from api_get_path(SYS_COURSE_PATH))
     * @return	bool	True if the path is under the checker, false otherwise
     */
    public static function check_abs_path($abs_path, $checker_path) {
        global $_configuration;
        if (empty($checker_path)) { return false; } // The checker path must be set.

        $true_path = str_replace("\\", '/', realpath($abs_path));
        $found = strpos($true_path.'/', $checker_path);
        
        if ($found === 0) {
            return true;
        } else {
            // Code specific to courses directory stored on other disk.
            $checker_path = str_replace(api_get_path(SYS_COURSE_PATH), $_configuration['symbolic_course_folder_abs'], $checker_path);
            $found = strpos($true_path.'/', $checker_path);
            if ($found === 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Checks if the relative path (directory) given is really under the
     * checker path (directory)
     * @param	string	Relative path to be checked (relative to the current directory) (with trailing slash)
     * @param	string	Checker path under which the path should be (absolute path, with trailing slash, get it from api_get_path(SYS_COURSE_PATH))
     * @return	bool	True if the path is under the checker, false otherwise
     */
    public static function check_rel_path($rel_path, $checker_path) {
        if (empty($checker_path)) { return false; } // The checker path must be set.
        $current_path = getcwd(); // No trailing slash.
        if (substr($rel_path, -1, 1) != '/') {
            $rel_path = '/'.$rel_path;
        }
        $abs_path = $current_path.$rel_path;
        $true_path=str_replace("\\", '/', realpath($abs_path));
        $found = strpos($true_path.'/', $checker_path);
        if ($found === 0) {
            return true;
        }
        return false;
    }

    /**
     * Filters dangerous filenames (*.php[.]?* and .htaccess) and returns it in
     * a non-executable form (for PHP and htaccess, this is still vulnerable to
     * other languages' files extensions)
     * @param   string  Unfiltered filename
     * @param   string  Filtered filename
     */
    public static function filter_filename($filename) {
        require_once api_get_path(LIBRARY_PATH).'fileUpload.lib.php';
        return disable_dangerous_file($filename);
    }

    /**
     * This function checks that the token generated in get_token() has been kept (prevents
     * Cross-Site Request Forgeries attacks)
     * @param	string	The array in which to get the token ('get' or 'post')
     * @return	bool	True if it's the right token, false otherwise
     */
    public static function check_token($request_type = 'post') {
        switch ($request_type) {
            case 'request':
                if (isset($_SESSION['sec_token']) && isset($_REQUEST['sec_token']) && $_SESSION['sec_token'] === $_REQUEST['sec_token']) {
                    return true;
                }
                return false;
            case 'get':
                if (isset($_SESSION['sec_token']) && isset($_GET['sec_token']) && $_SESSION['sec_token'] === $_GET['sec_token']) {
                    return true;
                }
                return false;
            case 'post':
                if (isset($_SESSION['sec_token']) && isset($_POST['sec_token']) && $_SESSION['sec_token'] === $_POST['sec_token']) {
                    return true;
                }
                return false;
            default:
                if (isset($_SESSION['sec_token']) && isset($request_type) && $_SESSION['sec_token'] === $request_type) {
                    return true;
                }
                return false;
        }
        return false; // Just in case, don't let anything slip.
    }

    /**
     * Checks the user agent of the client as recorder by get_ua() to prevent
     * most session hijacking attacks.
     * @return	bool	True if the user agent is the same, false otherwise
     */
    public static function check_ua() {
        if (isset($_SESSION['sec_ua']) and $_SESSION['sec_ua'] === $_SERVER['HTTP_USER_AGENT'].$_SESSION['sec_ua_seed']) {
            return true;
        }
        return false;
    }

    /**
     * Clear the security token from the session
     * @return void
     */
    public static function clear_token() {
        $_SESSION['sec_token'] = null;
        unset($_SESSION['sec_token']);
    }

    /**
     * This function sets a random token to be included in a form as a hidden field
     * and saves it into the user's session. Returns an HTML form element
     * This later prevents Cross-Site Request Forgeries by checking that the user is really
     * the one that sent this form in knowingly (this form hasn't been generated from
     * another website visited by the user at the same time).
     * Check the token with check_token()
     * @return	string	Hidden-type input ready to insert into a form
     */
    public static function get_HTML_token() {
        $token = md5(uniqid(rand(), TRUE));
        $string = '<input type="hidden" name="sec_token" value="'.$token.'" />';
        $_SESSION['sec_token'] = $token;
        return $string;
    }

    /**
     * This function sets a random token to be included in a form as a hidden field
     * and saves it into the user's session.
     * This later prevents Cross-Site Request Forgeries by checking that the user is really
     * the one that sent this form in knowingly (this form hasn't been generated from
     * another website visited by the user at the same time).
     * Check the token with check_token()
     * @return	string	Token
     */
    public static function get_token() {
        $token = md5(uniqid(rand(), TRUE));
        $_SESSION['sec_token'] = $token;
        return $token;
    }

    /**
     * Gets the user agent in the session to later check it with check_ua() to prevent
     * most cases of session hijacking.
     * @return void
     */
    public static function get_ua() {
        $_SESSION['sec_ua_seed'] = uniqid(rand(), TRUE);
        $_SESSION['sec_ua'] = $_SERVER['HTTP_USER_AGENT'].$_SESSION['sec_ua_seed'];
    }

    /**
     * This function filters a variable to the type given, with the options given
     * @param	mixed	The variable to be filtered
     * @param	string	The type of variable we expect (bool,int,float,string)
     * @param	array	Additional options
     * @return	bool	True if variable was filtered and added to the current object, false otherwise
     */
    public static function filter($var, $type = 'string', $options = array()) {
        // This function has not been finished! Do not use!
        $result = false;
        // Get variable name and value.
        $args = func_get_args();
        $names = array_keys($args);
        $name = $names[0];
        $value = $args[$name];
        switch ($type) {
            case 'bool':
                $result = (bool) $var;
                break;
            case 'int':
                $result = (int) $var;
                break;
            case 'float':
                $result = (float) $var;
                break;
            case 'string/html':
                $result = self::remove_XSS($var);
                break;
            case 'string/db':
                $result = Database::escape_string($var);
                break;
            case 'array':
                // An array variable shouldn't be given to the filter.
                return false;
            default:
                return false;
        }
        if (!empty($option['save'])) {
            $this->clean[$name] = $result;
        }
        return $result;
    }

    /**
     * This function returns a variable from the clean array. If the variable doesn't exist,
     * it returns null
     * @param	string	Variable name
     * @return	mixed	Variable or NULL on error
     */
    public static function get($varname) {
        if (isset(self::$clean[$varname])) {
            return self::$clean[$varname];
        }
        return NULL;
    }

    /**
     * This function tackles the XSS injections.
     * Filtering for XSS is very easily done by using the htmlentities() function.
     * This kind of filtering prevents JavaScript snippets to be understood as such.
     * @param	mixed	The variable to filter for XSS, this params can be a string or an array (example : array(x,y))
     * @param   integer The user status,constant allowed (STUDENT, COURSEMANAGER, ANONYMOUS, COURSEMANAGERLOWSECURITY)
     * @return	mixed	Filtered string or array
     */
    public static function remove_XSS($var, $user_status = ANONYMOUS, $filter_terms = false) {
    	if ($filter_terms) {
    		$var = self::filter_terms($var);
    	}
    	
        if ($user_status == COURSEMANAGERLOWSECURITY) {
            return $var;  // No filtering.
        }
        static $purifier = array();
        if (!isset($purifier[$user_status])) {
            if (!class_exists('HTMLPurifier')) {
                // Lazy loading.
                require api_get_path(LIBRARY_PATH).'htmlpurifier/library/HTMLPurifier.auto.php';
            }
            $cache_dir = api_get_path(SYS_ARCHIVE_PATH).'Serializer';
            if (!file_exists($cache_dir)) {
                mkdir($cache_dir, 0777);
            }
            $config = HTMLPurifier_Config::createDefault();
            //$config->set('Cache.DefinitionImpl', null); // Enable this line for testing purposes, for turning off caching. Don't forget to disable this line later!
            $config->set('Cache.SerializerPath', $cache_dir);
            $config->set('Core.Encoding', api_get_system_encoding());
            $config->set('HTML.Doctype', 'XHTML 1.0 Transitional');
            $config->set('HTML.TidyLevel', 'light');
            $config->set('Core.ConvertDocumentToFragment', false);
            $config->set('Core.RemoveProcessingInstructions', true);
            //Shows _target attribute in anchors
            $config->set('Attr.AllowedFrameTargets', array('_blank','_top','_self', '_parent')); 
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
            $config->set('Attr.EnableID', true); // We need it for example for the flv player (ids of surrounding div-tags have to be preserved).
            $config->set('CSS.AllowImportant', true);
            $config->set('CSS.AllowTricky', true); // We need for the flv player the css definition display: none;
            $config->set('CSS.Proprietary', true);
            $purifier[$user_status] = new HTMLPurifier($config);
        }
        if (is_array($var)) {
            return $purifier[$user_status]->purifyArray($var);
        } else {
            return $purifier[$user_status]->purify($var);
        }
    }
    
    
    /**
     * 
     * Filter content 
     * @param	string content to be filter
     * @return 	string
     */
    function filter_terms($text) {
    	static $bad_terms = array();
    	    	
    	if (empty($bad_terms)) {    		
    		$list = api_get_setting('filter_terms');    	    	
    		$list = explode("\n", $list);    		
    		$list = array_filter($list);    		
    		if (!empty($list)) {
    			foreach($list as $term) {
    				$term = str_replace(array("\r\n", "\r", "\n", "\t"), '', $term);
    				$html_entities_value = api_htmlentities($term, ENT_QUOTES, api_get_system_encoding()); 
    				$bad_terms[] = $term;
    				if ($term != $html_entities_value) {    				 
    					$bad_terms[] = $html_entities_value;
    				}
    			}
    			$bad_terms = array_filter($bad_terms);
    		}
    	}
    	    	
    	$replace = '***';    	
    	if (!empty($bad_terms)) {
    		//Fast way
    		$new_text = str_ireplace($bad_terms, $replace, $text, $count);
    		
    		//We need statistics
    		/*
    		if (strlen($new_text) != strlen($text)) {
    			$table = Database::get_main_table(TABLE_STATISTIC_TRACK_FILTERED_TERMS);
    			$attributes = array();
    			
    			
    			$attributes['user_id'] 		=
    			$attributes['course_id'] 	=
    			$attributes['session_id'] 	=
    			$attributes['tool_id'] 		=
    			$attributes['term'] 		=
    			$attributes['created_at'] 	= api_get_utc_datetime();
    			$sql = Database::insert($table, $attributes);
    		}
    		*/
    		$text = $new_text;
    		
    	}        	
		return $text;
    }
    

    /**
     * This method provides specific protection (against XSS and other kinds of attacks) for static images (icons) used by the system.
     * Image paths are supposed to be given by programmers - people who know what they do, anyway, this method encourages
     * a safe practice for generating icon paths, without using heavy solutions based on HTMLPurifier for example.
     * @param string $img_path          The input path of the image, it could be relative or absolute URL.
     * @return string                   Returns sanitized image path or an empty string when the image path is not secure.
     * @author Ivan Tcholakov, March 2011
     */
    public static function filter_img_path($image_path) {
        static $allowed_extensions = array('png', 'gif', 'jpg', 'jpeg');
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
}
