<?php
/* For licensing terms, see /license.txt */
/**
 * Responses to AJAX calls
 */

api_protect_admin_script();

$action = isset($_REQUEST['a']) ? $_REQUEST['a'] : null;

switch ($action) {
    case 'user_exists':
        $user_info = api_get_user_info($_REQUEST['id']);
        if (empty($user_info)) {
            echo 0;
        } else {
            echo 1;
        }
        break;
    case 'find_coaches':
        $coaches = SessionManager::get_coaches_by_keyword($_REQUEST['tag']);
        $json_coaches = array();
        if (!empty($coaches)) {
            foreach ($coaches as $coach) {
                $json_coaches[] = array(
                    'key' => $coach['user_id'],
                    'value' => api_get_person_name($coach['firstname'], $coach['lastname'])
                );
            }
        }
        echo json_encode($json_coaches);
        break;
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
        exit;
        break;
}


/**
 * Displays either the text for the registration or the message that the installation is (not) up to date
 *
 * @return string html code
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version august 2006
 * @todo have a 6monthly re-registration
 */
function version_check()
{
    $tbl_settings = Database :: get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
    $sql = 'SELECT selected_value FROM  '.$tbl_settings.' WHERE variable="registered" ';
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

        $data = array(
            'url' => api_get_path(WEB_PATH),
            'campus' => api_get_setting('siteName'),
            'contact' => api_get_setting('emailAdministrator'),
            'version' => $system_version,
            'numberofcourses' => $number_of_courses,
            'numberofusers' => $number_of_users,
            'numberofactiveusers' => $number_of_active_users,
            //The donotlistcampus setting recovery should be improved to make
            // it true by default - this does not affect numbers counting
            'donotlistcampus' => api_get_setting('donotlistcampus'),
            'organisation' => api_get_setting('Institution'),
            'language' => api_get_setting('platformLanguage'),
            'adminname' => api_get_setting('administratorName').' '.api_get_setting('administratorSurname'),
        );

        $res = api_http_request('version.chamilo.org', 80, '/version.php', $data);

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
exit;
