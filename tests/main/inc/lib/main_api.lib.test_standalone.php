<?php //$id:$
/**
 * A simple set of tests for the main API.
 * @author Ivan Tcholakov, 2009.
 * For licensing terms, see /license.txt
 */

class TestMainApi extends UnitTestCase {

    function TestMainApi() {
        $this->UnitTestCase('Main API tests');
    }

    public function testApiGetPath() {

        $common_paths = array(
            WEB_PATH,
            SYS_PATH,
            REL_PATH,
            WEB_SERVER_ROOT_PATH,
            SYS_SERVER_ROOT_PATH,
            WEB_COURSE_PATH,
            SYS_COURSE_PATH,
            REL_COURSE_PATH,
            REL_CODE_PATH,
            WEB_CODE_PATH,
            SYS_CODE_PATH,
            SYS_LANG_PATH,
            WEB_IMG_PATH,
            WEB_CSS_PATH,
            SYS_PLUGIN_PATH,
            WEB_PLUGIN_PATH,
            SYS_ARCHIVE_PATH,
            WEB_ARCHIVE_PATH,
            INCLUDE_PATH,
            LIBRARY_PATH,
            CONFIGURATION_PATH,
            WEB_LIBRARY_PATH
        );

        $specific_paths = array(
            FLASH_PLAYER_AUDIO,
            FLASH_PLAYER_VIDEO,
            SCRIPT_SWFOBJECT,
            SCRIPT_ASCIIMATHML,
            DRAWING_ASCIISVG
        );

        $res = array();
        $is_ok = array();
        $message = array();
        $paths = array();

        $message[] = '';
        $message[] = '<strong>A test about api_get_path()</strong>';
        $message[] = '---------------------------------------------------------------------------------------------------------------';
        $message[] = '';

        $message[] = '';
        $message[] = 'Changed behaviour of the function api_get_path() after Dokeos 1.8.6.1, i.e. as of Chamilo 1.8.6.2.';
        $message[] = '---------------------------------------------------------------------------------------------------------------';
        $message[] = '';
        $message[] = 'Old behaviour (1.8.6.1) api_get_path(INCLUDE_PATH) = '.api_get_path_1_8_6_1(INCLUDE_PATH).'&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;'.'New behaviour (1.8.6.2) api_get_path(INCLUDE_PATH) = '.api_get_path(INCLUDE_PATH);
        $message[] = '* Reason for this change: Difference here is due to the fact that the etalonic old function api_get_path() has ben moved in this file ( see api_get_path_1_8_6_1() ). Even for such rare, hypothetical cases, this widely used function should be stable. Now, after installation, the function returns results based on configuration settings only, as it should be.';
        $message[] = '';
        $message[] = 'Old behaviour (1.8.6.1) api_get_path(WEB_CSS_PATH) = '.api_get_path_1_8_6_1(WEB_CSS_PATH).'&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;'.'New behaviour (1.8.6.2) api_get_path(WEB_CSS_PATH) = '.api_get_path(WEB_CSS_PATH);
        $message[] = '* This is a proposed implementation. Retrieving css paths through user\'s configuration options has not been implemented yet.';
        $message[] = '';

        $message[] = '';
        $message[] = 'Reading common purpose paths';
        $message[] = '---------------------------------------------------------------------------------------------------------------';
        $message[] = '';

        foreach ($common_paths as $path) {

            $test_case = "api_get_path($path)";
            $res[$test_case] = api_get_path($path);
            switch ($path) {
                case INCLUDE_PATH:
                case WEB_CSS_PATH:
                    $is_ok[$test_case] = is_string($res[$test_case]) && !empty($res[$test_case]);
                    break;
                default:
                    $is_ok[$test_case] = is_string($res[$test_case]) && !empty($res[$test_case]) && $res[$test_case] == api_get_path_1_8_6_1($path);
            }
            $message[] = ($is_ok[$test_case] ? '<span style="color: green; font-weight: bold;">Ok</span>' : '<span style="color: red; font-weight: bold;">Failed</span>').' : '.$test_case.' => '.$res[$test_case];
        }

        $message[] = '';
        $message[] = '';
        $message[] = 'Reading specific purpose paths';
        $message[] = '---------------------------------------------------------------------------------------------------------------';
        $message[] = '';

        foreach ($specific_paths as $path) {

            $test_case = "api_get_path(TO_WEB, $path)";
            $test_case = str_replace(array('{', '}'), '', $test_case);
            $res[$test_case] = api_get_path(TO_WEB, $path);
            $is_ok[$test_case] = is_string($res[$test_case]) && !empty($res[$test_case]);
            $message[] = ($is_ok[$test_case] ? '<span style="color: green; font-weight: bold;">Ok</span>' : '<span style="color: red; font-weight: bold;">Failed</span>').' : '.$test_case.' => '.$res[$test_case];
            $paths[] = $path;

            $test_case = "api_get_path(TO_SYS, $path)";
            $test_case = str_replace(array('{', '}'), '', $test_case);
            $res[$test_case] = api_get_path(TO_SYS, $path);
            $is_ok[$test_case] = is_string($res[$test_case]) && !empty($res[$test_case]);
            $message[] = ($is_ok[$test_case] ? '<span style="color: green; font-weight: bold;">Ok</span>' : '<span style="color: red; font-weight: bold;">Failed</span>').' : '.$test_case.' => '.$res[$test_case];
            $paths[] = $path;

            $test_case = "api_get_path(TO_REL, $path)";
            $test_case = str_replace(array('{', '}'), '', $test_case);
            $res[$test_case] = api_get_path(TO_REL, $path);
            $is_ok[$test_case] = is_string($res[$test_case]) && !empty($res[$test_case]);
            $message[] = ($is_ok[$test_case] ? '<span style="color: green; font-weight: bold;">Ok</span>' : '<span style="color: red; font-weight: bold;">Failed</span>').' : '.$test_case.' => '.$res[$test_case];
            $paths[] = $path;
        }

        $message[] = '';
        $message[] = '';
        $message[] = 'Testing path conversions';
        $message[] = '---------------------------------------------------------------------------------------------------------------';
        $message[] = '';

        $paths = array();
        foreach ($common_paths as $path) {
            $paths[] = array($path, api_get_path($path));
        }
        foreach ($specific_paths as $path) {
            $paths[] = array($path, api_get_path(TO_WEB, $path));
            $paths[] = array($path, api_get_path(TO_SYS, $path));
            $paths[] = array($path, api_get_path(TO_REL, $path));
        }

        foreach ($paths as $path) {

            $test_case = 'api_get_path(TO_WEB, '.$path[0].')';
            $test_case = str_replace(array('{', '}'), '', $test_case);
            $res[$test_case] = api_get_path(TO_WEB, $path[0]);
            $test_case_1 = 'api_get_path(TO_WEB, \''.$path[1].'\')';
            $res[$test_case_1] = api_get_path(TO_WEB, $path[1]);
            $is_ok[$test_case] =
                is_string($res[$test_case]) && !empty($res[$test_case])
                && is_string($res[$test_case_1]) && !empty($res[$test_case_1])
                && $res[$test_case] == $res[$test_case_1];
            $message[] = ($is_ok[$test_case] ? '<span style="color: green; font-weight: bold;">Ok</span>' : '<span style="color: red; font-weight: bold;">Failed</span>').' : ';
            $message[] = $test_case.' => '.$res[$test_case];
            $message[] = $test_case_1.' => '.$res[$test_case_1];
            $message[] = '';

            $test_case = 'api_get_path(TO_SYS, '.$path[0].')';
            $test_case = str_replace(array('{', '}'), '', $test_case);
            $res[$test_case] = api_get_path(TO_SYS, $path[0]);
            $test_case_1 = 'api_get_path(TO_SYS, \''.$path[1].'\')';
            $res[$test_case_1] = api_get_path(TO_SYS, $path[1]);
            $is_ok[$test_case] =
                is_string($res[$test_case]) && !empty($res[$test_case])
                && is_string($res[$test_case_1]) && !empty($res[$test_case_1])
                && $res[$test_case] == $res[$test_case_1];
            $message[] = ($is_ok[$test_case] ? '<span style="color: green; font-weight: bold;">Ok</span>' : '<span style="color: red; font-weight: bold;">Failed</span>').' : ';
            $message[] = $test_case.' => '.$res[$test_case];
            $message[] = $test_case_1.' => '.$res[$test_case_1];
            $message[] = '';

            $test_case = 'api_get_path(TO_REL, '.$path[0].')';
            $test_case = str_replace(array('{', '}'), '', $test_case);
            $res[$test_case] = api_get_path(TO_REL, $path[0]);
            $test_case_1 = 'api_get_path(TO_REL, \''.$path[1].'\')';
            $res[$test_case_1] = api_get_path(TO_REL, $path[1]);
            $is_ok[$test_case] =
                is_string($res[$test_case]) && !empty($res[$test_case])
                && is_string($res[$test_case_1]) && !empty($res[$test_case_1])
                && $res[$test_case] == $res[$test_case_1];
            $message[] = ($is_ok[$test_case] ? '<span style="color: green; font-weight: bold;">Ok</span>' : '<span style="color: red; font-weight: bold;">Failed</span>').' : ';
            $message[] = $test_case.' => '.$res[$test_case];
            $message[] = $test_case_1.' => '.$res[$test_case_1];
            $message[] = '';

        }

        $message[] = '';
        $message[] = 'Random examples, check them visually';
        $message[] = '---------------------------------------------------------------------------------------------------------------';
        $message[] = '';
        $message[] = '$_SERVER[\'REQUEST_URI\'] => '.$_SERVER['REQUEST_URI'];
        $message[] = '<strong>Note:</strong> Try some query strings. They should be removed from the results.';
        $message[] = 'api_get_path(TO_WEB, $_SERVER[\'REQUEST_URI\']) => '.api_get_path(TO_WEB, $_SERVER['REQUEST_URI']);
        $message[] = 'api_get_path(TO_SYS, $_SERVER[\'REQUEST_URI\']) => '.api_get_path(TO_SYS, $_SERVER['REQUEST_URI']);
        $message[] = 'api_get_path(TO_REL, $_SERVER[\'REQUEST_URI\']) => '.api_get_path(TO_REL, $_SERVER['REQUEST_URI']);
        $message[] = '';
        $message[] = '__FILE__ => '.__FILE__;
        $message[] = 'api_get_path(TO_WEB, __FILE__) => '.api_get_path(TO_WEB, __FILE__);
        $message[] = 'api_get_path(TO_SYS, __FILE__) => '.api_get_path(TO_SYS, __FILE__);
        $message[] = 'api_get_path(TO_REL, __FILE__) => '.api_get_path(TO_REL, __FILE__);
        $message[] = '';
        $message[] = '$_SERVER[\'PHP_SELF\'] => '.$_SERVER['PHP_SELF'];
        $message[] = 'api_get_path(TO_WEB, $_SERVER[\'PHP_SELF\']) => '.api_get_path(TO_WEB, $_SERVER['PHP_SELF']);
        $message[] = 'api_get_path(TO_SYS, $_SERVER[\'PHP_SELF\']) => '.api_get_path(TO_SYS, $_SERVER['PHP_SELF']);
        $message[] = 'api_get_path(TO_REL, $_SERVER[\'PHP_SELF\']) => '.api_get_path(TO_REL, $_SERVER['PHP_SELF']);
        $message[] = '';

        $message[] = '';
        $message[] = '---------------------------------------------------------------------------------------------------------------';
        $message[] = 'This test and changes of behaviour of api_get_path() have been done by Ivan Tcholakov, September 22, 2009.';
        $message[] = '';

        $result = !in_array(false, $is_ok);
         $this->assertTrue($result);
         //var_dump($res);
         foreach ($message as $line) { echo $line.'<br />'; }

         // Sample code for showing results in different context.
         /*
        $common_paths = array(
            WEB_PATH,
            SYS_PATH,
            REL_PATH,
            WEB_SERVER_ROOT_PATH,
            SYS_SERVER_ROOT_PATH,
            WEB_COURSE_PATH,
            SYS_COURSE_PATH,
            REL_COURSE_PATH,
            REL_CODE_PATH,
            WEB_CODE_PATH,
            SYS_CODE_PATH,
            SYS_LANG_PATH,
            WEB_IMG_PATH,
            WEB_CSS_PATH,
            SYS_PLUGIN_PATH,
            WEB_PLUGIN_PATH,
            SYS_ARCHIVE_PATH,
            WEB_ARCHIVE_PATH,
            INCLUDE_PATH,
            LIBRARY_PATH,
            CONFIGURATION_PATH,
            WEB_LIBRARY_PATH
        );

        $specific_paths = array(
            FLASH_PLAYER_AUDIO,
            FLASH_PLAYER_VIDEO,
            SCRIPT_SWFOBJECT,
            SCRIPT_ASCIIMATHML,
            DRAWING_ASCIISVG
        );

        $res = array();
        $is_ok = array();
        $message = array();
        $paths = array();

        $message[] = '';
        $message[] = 'Reading common purpose paths';
        $message[] = '---------------------------------------------------------------------------------------------------------------';
        $message[] = '';

        foreach ($common_paths as $path) {

            $test_case = "api_get_path($path)";
            $res[$test_case] = api_get_path($path);
            $message[] = $test_case.' => '.$res[$test_case];
        }

        $message[] = '';
        $message[] = '';
        $message[] = 'Reading specific purpose paths';
        $message[] = '---------------------------------------------------------------------------------------------------------------';
        $message[] = '';

        foreach ($specific_paths as $path) {

            $test_case = "api_get_path(TO_WEB, $path)";
            $test_case = str_replace(array('{', '}'), '', $test_case);
            $res[$test_case] = api_get_path(TO_WEB, $path);
            $message[] = $test_case.' => '.$res[$test_case];
            $paths[] = $path;

            $test_case = "api_get_path(TO_SYS, $path)";
            $test_case = str_replace(array('{', '}'), '', $test_case);
            $res[$test_case] = api_get_path(TO_SYS, $path);
            $message[] = $test_case.' => '.$res[$test_case];
            $paths[] = $path;

            $test_case = "api_get_path(TO_REL, $path)";
            $test_case = str_replace(array('{', '}'), '', $test_case);
            $res[$test_case] = api_get_path(TO_REL, $path);
            $message[] = $test_case.' => '.$res[$test_case];
            $paths[] = $path;
        }

        foreach ($message as $line) { echo $line.'<br />'; }
        */
    }

    public function testApiIsInternalPath() {
        $path1 = api_get_path(WEB_IMG_PATH);
        $path2 = 'http://kdflskfsenfnmzsdn/fnefsdsmdsdmsdfsdcmxaddfdafada/index.html';
        $path3 = api_get_path(TO_SYS, WEB_IMG_PATH);
        $path4 = 'C:\Inetpub\wwwroot\fnefsdsmdsdmsdfsdcmxaddfdafada/index.html';
        $path5 = api_get_path(TO_REL, WEB_IMG_PATH);
        $path6 = '/fnefsdsmdsdmsdfsdcmxaddfdafada/index.html';
        $res1 = api_is_internal_path($path1);
        $res2 = api_is_internal_path($path2);
        $res3 = api_is_internal_path($path3);
        $res4 = api_is_internal_path($path4);
        $res5 = api_is_internal_path($path5);
        $res6 = api_is_internal_path($path6);
        $this->assertTrue(is_bool($res1) && is_bool($res2) && is_bool($res3) && is_bool($res4) && is_bool($res5) && is_bool($res6)
            && $res1 && !$res2 && $res3 && !$res4 && $res5 && !$res6);
        //var_dump($res1);
        //var_dump($res2);
        //var_dump($res1);
        //var_dump($res2);
        //var_dump($res1);
        //var_dump($res2);
    }

    public function testApiAddTrailingSlash() {
        $string1 = 'path';
        $string2 = 'path/';
        $res1 = api_add_trailing_slash($string1);
        $res2 = api_add_trailing_slash($string2);
        $this->assertTrue(is_string($res1) && is_string($res2) && $res1 == $res2);
        //var_dump($res1);
        //var_dump($res2);
    }

    public function testRemoveAddTrailingSlash() {
        $string1 = 'path';
        $string2 = 'path/';
        $res1 = api_remove_trailing_slash($string1);
        $res2 = api_remove_trailing_slash($string2);
        $this->assertTrue(is_string($res1) && is_string($res2) && $res1 == $res2);
        //var_dump($res1);
        //var_dump($res2);
    }
}

/**
 *	Returns a full path to a certain Dokeos area, which you specify
 *	through a parameter.
 *
 *	See $_configuration['course_folder'] in the configuration.php
 *	to alter the WEB_COURSE_PATH and SYS_COURSE_PATH parameters.
 *
 *	@param one of the following constants:
 *	WEB_SERVER_ROOT_PATH, SYS_SERVER_ROOT_PATH,
 *	WEB_PATH, SYS_PATH, REL_PATH, WEB_COURSE_PATH, SYS_COURSE_PATH,
 *	REL_COURSE_PATH, REL_CODE_PATH, WEB_CODE_PATH, SYS_CODE_PATH,
 *	SYS_LANG_PATH, WEB_IMG_PATH, GARBAGE_PATH, WEB_PLUGIN_PATH, SYS_PLUGIN_PATH, WEB_ARCHIVE_PATH, SYS_ARCHIVE_PATH,
 *	INCLUDE_PATH, WEB_LIBRARY_PATH, LIBRARY_PATH, CONFIGURATION_PATH
 *
 * 	@example assume that your server root is /var/www/ chamilo is installed in a subfolder chamilo/ and the URL of your campus is http://www.mychamilo.com
 * 	The other configuration paramaters have not been changed.
 * 	The different api_get_paths will give
 *	WEB_SERVER_ROOT_PATH	http://www.mychamilo.com/
 *	SYS_SERVER_ROOT_PATH	/var/www/ - This is the physical folder where the system Dokeos has been placed. It is not always equal to $_SERVER['DOCUMENT_ROOT'].
 * 	WEB_PATH				http://www.mychamilo.com/chamilo/
 * 	SYS_PATH				/var/www/chamilo/
 * 	REL_PATH				chamilo/
 * 	WEB_COURSE_PATH			http://www.mychamilo.com/chamilo/courses/
 * 	SYS_COURSE_PATH			/var/www/chamilo/courses/
 *	REL_COURSE_PATH			/chamilo/courses/
 * 	REL_CODE_PATH			/chamilo/main/
 * 	WEB_CODE_PATH			http://www.mychamilo.com/chamilo/main/
 * 	SYS_CODE_PATH			/var/www/chamilo/main/
 * 	SYS_LANG_PATH			/var/www/chamilo/main/lang/
 * 	WEB_IMG_PATH			http://www.mychamilo.com/chamilo/main/img/
 * 	GARBAGE_PATH
 * 	WEB_PLUGIN_PATH			http://www.mychamilo.com/chamilo/plugin/
 * 	SYS_PLUGIN_PATH			/var/www/chamilo/plugin/
 * 	WEB_ARCHIVE_PATH		http://www.mychamilo.com/chamilo/archive/
 * 	SYS_ARCHIVE_PATH		/var/www/chamilo/archive/
 *	INCLUDE_PATH			/var/www/chamilo/main/inc/
 * 	WEB_LIBRARY_PATH		http://www.mychamilo.com/chamilo/main/inc/lib/
 * 	LIBRARY_PATH			/var/www/chamilo/main/inc/lib/
 * 	CONFIGURATION_PATH		/var/www/chamilo/main/inc/conf/
 */
function api_get_path_1_8_6_1($path_type) {

    global $_configuration;
    if (!isset($_configuration['access_url']) || $_configuration['access_url']==1 || $_configuration['access_url']=='') {
        //by default we call the $_configuration['root_web'] we don't query to the DB
        //$url_info= api_get_access_url(1);
        //$root_web = $url_info['url'];
        if(isset($_configuration['root_web']))
            $root_web = $_configuration['root_web'];
    } else {
        //we look into the DB the function api_get_access_url
        //this funcion have a problem because we can't called to the Database:: functions
        $url_info= api_get_access_url($_configuration['access_url']);
        if ($url_info['active']==1) {
            $root_web = $url_info['url'];
        } else {
            $root_web = $_configuration['root_web'];
        }
    }

    switch ($path_type) {

        case WEB_SERVER_ROOT_PATH:
            // example: http://www.mychamilo.com/
            $result = preg_replace('@'.api_get_path(REL_PATH).'$@', '', api_get_path(WEB_PATH));
            if (substr($result, -1) == '/') {
                return $result;
            } else {
                return $result.'/';
            }
            break;

        case SYS_SERVER_ROOT_PATH:
            $result = preg_replace('@'.api_get_path(REL_PATH).'$@', '', api_get_path(SYS_PATH));
            if (substr($result, -1) == '/') {
                return $result;
            } else {
                return $result.'/';
            }
            break;

        case WEB_PATH :
            // example: http://www.mychamilo.com/ or http://www.mychamilo.com/chamilo/ if you're using
            // a subdirectory of your document root for Dokeos
            if (substr($root_web,-1) == '/') {
                return $root_web;
            } else {
                return $root_web.'/';
            }
            break;

        case SYS_PATH :
            // example: /var/www/chamilo/
            if (substr($_configuration['root_sys'],-1) == '/') {
                return $_configuration['root_sys'];
            } else {
                return $_configuration['root_sys'].'/';
            }
            break;

        case REL_PATH :
            // example: chamilo/
            if (substr($_configuration['url_append'], -1) === '/') {
                return $_configuration['url_append'];
            } else {
                return $_configuration['url_append'].'/';
            }
            break;

        case WEB_COURSE_PATH :
            // example: http://www.mychamilo.com/courses/
            return $root_web.$_configuration['course_folder'];
            break;

        case SYS_COURSE_PATH :
            // example: /var/www/chamilo/courses/
            return $_configuration['root_sys'].$_configuration['course_folder'];
            break;

        case REL_COURSE_PATH :
            // example: courses/ or chamilo/courses/
            return api_get_path(REL_PATH).$_configuration['course_folder'];
            break;

        case REL_CODE_PATH :
            // example: main/ or chamilo/main/
            return api_get_path(REL_PATH).$_configuration['code_append'];
            break;

        case WEB_CODE_PATH :
            // example: http://www.mychamilo.com/main/
            return $root_web.$_configuration['code_append'];
            break;

        case SYS_CODE_PATH :
            // example: /var/www/chamilo/main/
            return $_configuration['root_sys'].$_configuration['code_append'];
            break;

        case SYS_LANG_PATH :
            // example: /var/www/chamilo/main/lang/
            return api_get_path(SYS_CODE_PATH).'lang/';
            break;

        case WEB_IMG_PATH :
            // example: http://www.mychamilo.com/main/img/
            return api_get_path(WEB_CODE_PATH).'img/';
            break;

        case SYS_PLUGIN_PATH :
            // example: /var/www/chamilo/plugin/
            return api_get_path(SYS_PATH).'plugin/';
            break;

        case WEB_PLUGIN_PATH :
            // example: http://www.mychamilo.com/plugin/
            return api_get_path(WEB_PATH).'plugin/';
            break;

        case GARBAGE_PATH : //now set to be same as archive
        case SYS_ARCHIVE_PATH :
            // example: /var/www/chamilo/archive/
            return api_get_path(SYS_PATH).'archive/';
            break;

        case WEB_ARCHIVE_PATH :
            // example: http://www.mychamilo.com/archive/
            return api_get_path(WEB_PATH).'archive/';
            break;

        case INCLUDE_PATH :
            // Generated by main/inc/global.inc.php
            // example: /var/www/chamilo/main/inc/
            $incpath = realpath(dirname(__FILE__).'/../');
            return str_replace('\\', '/', $incpath).'/';
            break;

        case LIBRARY_PATH :
            // example: /var/www/chamilo/main/inc/lib/
            return api_get_path(INCLUDE_PATH).'lib/';
            break;

        case WEB_LIBRARY_PATH :
            // example: http://www.mychamilo.com/main/inc/lib/
            return api_get_path(WEB_CODE_PATH).'inc/lib/';
            break;

        case CONFIGURATION_PATH :
            // example: /var/www/chamilo/main/inc/conf/
            return api_get_path(INCLUDE_PATH).'conf/';
            break;

        default :
            return;
            break;
    }
}

?>
