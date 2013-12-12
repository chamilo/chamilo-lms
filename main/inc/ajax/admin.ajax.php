<?php
/* For licensing terms, see /license.txt */
/**
 * Responses to AJAX calls
 */

// Language files that need to be included.
$language_file = array('admin');
require_once '../global.inc.php';
require_once api_get_path(SYS_CODE_PATH).'admin/statistics/statistics.lib.php';

api_protect_admin_script();

$action = isset($_REQUEST['a']) ? $_REQUEST['a'] : null;

switch ($action) {
    case 'update_changeable_setting':
        $url_id = api_get_current_access_url_id();

        if (api_is_global_platform_admin() && $url_id == 1) {
            if (isset($_GET['id']) && !empty($_GET['id'])) {
                $params = array('variable = ? ' =>  array($_GET['id']));
                $data = api_get_settings_params($params);
                if (!empty($data)) {
                    foreach ($data as $item) {
                        $params = array('id' =>$item['id'], 'access_url_changeable' => $_GET['changeable']);
                        api_set_setting_simple($params);
                    }
                }
                echo '1';
            }
        }
        break;

    case 'version':
        echo version_check();
        break;
}


/**
 * Displays either the text for the registration or the message that the installation is (not) up to date
 *
 * @return string html code
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version august 2006
 * @todo have a 6 monthly re-registration
 */
function version_check()
{
    $tbl_settings = Database :: get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
    $sql = 'SELECT selected_value FROM '.$tbl_settings.' WHERE variable = "registered" ';
    $result = Database::query($sql);
    $row = Database::fetch_array($result, 'ASSOC');

    // The site has not been registered yet.
    $return = '';
    if ($row['selected_value'] == 'false') {
        $return .= get_lang('VersionCheckExplanation');
        $return .= '<form class="well" action="'.api_get_path(WEB_CODE_PATH).'admin/index.php" id="VersionCheck" name="VersionCheck" method="post">';
        $return .= '<label class="checkbox"><input type="checkbox" name="donotlistcampus" value="1" id="checkbox" />'.get_lang('HideCampusFromPublicPlatformsList');
        $return .= '</label><button type="submit" class="btn btn-primary" name="Register" value="'.get_lang('EnableVersionCheck').'" id="register" >'.get_lang('EnableVersionCheck').'</button>';
        $return .= '</form>';
        check_system_version();
    } else {
        // site not registered. Call anyway
        $return .= check_system_version();
    }
    return $return;
}

/**
 * Check if the current installation is up to date
 * The code is borrowed from phpBB and slighlty modified
 * @author The phpBB Group <support@phpbb.com> (the code)
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University (the modifications)
 * @author Yannick Warnier <ywarnier@beeznest.org> for the move to HTTP request
 * @copyright (C) 2001 The phpBB Group
 * @return string language string with some layout (color)
 */
function check_system_version()
{
    global $_configuration;
    $system_version = trim($_configuration['system_version']); // the chamilo version of your installation

    if (ini_get('allow_url_fopen') == 1) {
        // The number of courses
        $number_of_courses = Statistics::count_courses();

        // The number of users
        $number_of_users = Statistics::count_users();
        $number_of_active_users = Statistics::count_users(null, null, null, true);

        // The number of sessions
        $number_of_sessions = Statistics::count_sessions();

        $data = array(
            'url' => api_get_path(WEB_PATH),
            'campus' => api_get_setting('siteName'),
            'contact' => api_get_setting('emailAdministrator'),
            'version' => $system_version,
            'numberofcourses' => $number_of_courses,
            'numberofusers' => $number_of_users,
            'numberofactiveusers' => $number_of_active_users,
            'numberofsessions' => $number_of_sessions,
            //The donotlistcampus setting recovery should be improved to make
            // it true by default - this does not affect numbers counting
            'donotlistcampus' => api_get_setting('donotlistcampus'),
            'organisation' => api_get_setting('Institution'),
            'language' => api_get_setting('platformLanguage'),
            'adminname' => api_get_setting('administratorName').' '.api_get_setting('administratorSurname'),
        );

        $res = _http_request('version.chamilo.org', 80, '/version.php', $data);

        if ($res != 0) {
            $version_info = $res;

            if ($system_version != $version_info) {
                $output = '<br /><span style="color:red">' . get_lang('YourVersionNotUpToDate') . '. '.get_lang('LatestVersionIs').' <b>Chamilo '.$version_info.'</b>. '.get_lang('YourVersionIs').' <b>Chamilo '.$system_version. '</b>. '.str_replace('http://www.chamilo.org', '<a href="http://www.chamilo.org">http://www.chamilo.org</a>', get_lang('PleaseVisitOurWebsite')).'</span>';
            } else {
                $output = '<br /><span style="color:green">'.get_lang('VersionUpToDate').': Chamilo '.$version_info.'</span>';
            }
        } else {
            $output = '<span style="color:red">' . get_lang('ImpossibleToContactVersionServerPleaseTryAgain') . '</span>';
        }
    } else {
        $output = '<span style="color:red">' . get_lang('AllowurlfopenIsSetToOff') . '</span>';
    }
    return $output;
}

/**
 * Function to make an HTTP request through fsockopen (specialised for GET)
 * Derived from Jeremy Saintot: http://www.php.net/manual/en/function.fsockopen.php#101872
 * @param string IP or hostname
 * @param int    Target port
 * @param string URI (defaults to '/')
 * @param array  GET data
 * @param float  Timeout
 * @param bool   Include HTTP Request headers?
 * @param bool   Include HTTP Response headers?
 */
function _http_request($ip, $port = 80, $uri = '/', $getdata = array(), $timeout = 5, $req_hdr = false, $res_hdr = false)
{
    $verb = 'GET';
    $ret = '';
    $getdata_str = count($getdata) ? '?' : '';

    foreach ($getdata as $k => $v) {
                $getdata_str .= urlencode($k) .'='. urlencode($v) . '&';
    }

    $crlf = "\r\n";
    $req = $verb .' '. $uri . $getdata_str .' HTTP/1.1' . $crlf;
    $req .= 'Host: '. $ip . $crlf;
    $req .= 'User-Agent: Mozilla/5.0 Firefox/3.6.12' . $crlf;
    $req .= 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8' . $crlf;
    $req .= 'Accept-Language: en-us,en;q=0.5' . $crlf;
    $req .= 'Accept-Encoding: deflate' . $crlf;
    $req .= 'Accept-Charset: utf-8;q=0.7,*;q=0.7' . $crlf;

    $req .= $crlf;

    if ($req_hdr) {
        $ret .= $req;
    }
    if (($fp = @fsockopen($ip, $port, $errno, $errstr, $timeout)) == false) {
        return "Error $errno: $errstr\n";
    }

    stream_set_timeout($fp, $timeout);
    $r = @fwrite($fp, $req);
    $line = @fread($fp,512);
    $ret .= $line;
    fclose($fp);

    if (!$res_hdr) {
        $ret = substr($ret, strpos($ret, "\r\n\r\n") + 4);
    }

    return trim($ret);
}
