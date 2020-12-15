<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\BranchSync;
use Chamilo\CoreBundle\Entity\Repository\BranchSyncRepository;
use GuzzleHttp\Client;

/**
 * Responses to AJAX calls.
 */
require_once __DIR__.'/../global.inc.php';

api_protect_admin_script();

$action = isset($_REQUEST['a']) ? $_REQUEST['a'] : null;

switch ($action) {
    case 'update_changeable_setting':
        $url_id = api_get_current_access_url_id();

        if (api_is_global_platform_admin() && $url_id == 1) {
            if (isset($_GET['id']) && !empty($_GET['id'])) {
                $params = ['variable = ? ' => [$_GET['id']]];
                $data = api_get_settings_params($params);
                if (!empty($data)) {
                    foreach ($data as $item) {
                        $params = ['id' => $item['id'], 'access_url_changeable' => $_GET['changeable']];
                        api_set_setting_simple($params);
                    }
                }
                echo '1';
            }
        }
        break;
    case 'version':
        // Fix session block when loading admin/index.php and changing page
        session_write_close();
        echo version_check();
        break;
    case 'get_extra_content':
        $blockName = isset($_POST['block']) ? Security::remove_XSS($_POST['block']) : null;

        if (empty($blockName)) {
            exit;
        }

        if (api_is_multiple_url_enabled()) {
            $accessUrlId = api_get_current_access_url_id();

            if ($accessUrlId == -1) {
                exit;
            }

            $urlInfo = api_get_access_url($accessUrlId);
            $url = api_remove_trailing_slash(preg_replace('/https?:\/\//i', '', $urlInfo['url']));
            $cleanUrl = str_replace('/', '-', $url);
            $newUrlDir = api_get_path(SYS_APP_PATH)."home/$cleanUrl/admin/";
        } else {
            $newUrlDir = api_get_path(SYS_APP_PATH)."home/admin/";
        }

        if (!file_exists($newUrlDir)) {
            exit;
        }

        if (!Security::check_abs_path("{$newUrlDir}{$blockName}_extra.html", $newUrlDir)) {
            exit;
        }

        if (!file_exists("{$newUrlDir}{$blockName}_extra.html")) {
            exit;
        }

        echo file_get_contents("{$newUrlDir}{$blockName}_extra.html");
        break;
    case 'get_latest_news':
        if (api_get_configuration_value('admin_chamilo_announcements_disable') === true) {
            break;
        }

        try {
            $latestNews = getLatestNews();
            $latestNews = json_decode($latestNews, true);

            echo Security::remove_XSS($latestNews['text'], COURSEMANAGER);
            break;
        } catch (Exception $e) {
            break;
        }
}

/**
 * Displays either the text for the registration or the message that the installation is (not) up to date.
 *
 * @return string html code
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 *
 * @version august 2006
 *
 * @todo have a 6 monthly re-registration
 */
function version_check()
{
    $tbl_settings = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
    $sql = 'SELECT selected_value FROM '.$tbl_settings.' WHERE variable = "registered" ';
    $result = Database::query($sql);
    $row = Database::fetch_array($result, 'ASSOC');

    // The site has not been registered yet.
    $return = '';
    if ($row['selected_value'] == 'false') {
        $return .= get_lang('VersionCheckExplanation');
        $return .= '<form class="version-checking" action="'.api_get_path(WEB_CODE_PATH).'admin/index.php" id="VersionCheck" name="VersionCheck" method="post">';
        $return .= '<label class="checkbox"><input type="checkbox" name="donotlistcampus" value="1" id="checkbox" />'.get_lang('HideCampusFromPublicPlatformsList');
        $return .= '</label><button type="submit" class="btn btn-primary btn-block" name="Register" value="'.get_lang('EnableVersionCheck').'" id="register" >'.get_lang('EnableVersionCheck').'</button>';
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
 * The code is borrowed from phpBB and slighlty modified.
 *
 * @throws \Exception
 * @throws \InvalidArgumentException
 *
 * @return string language string with some layout (color)
 */
function check_system_version()
{
    // Check if curl is available.
    if (!in_array('curl', get_loaded_extensions())) {
        return '<span style="color:red">'.get_lang('ImpossibleToContactVersionServerPleaseTryAgain').'</span>';
    }

    $url = 'https://version.chamilo.org';
    $options = [
        'verify' => false,
    ];

    $urlValidated = false;

    try {
        $client = new GuzzleHttp\Client();
        $res = $client->request('GET', $url, $options);
        if ($res->getStatusCode() == '200' || $res->getStatusCode() == '301') {
            $urlValidated = true;
        }
    } catch (Exception $e) {
    }

    // the chamilo version of your installation
    $system_version = trim(api_get_configuration_value('system_version'));

    if ($urlValidated) {
        // The number of courses
        $number_of_courses = Statistics::countCourses();

        // The number of users
        $number_of_users = Statistics::countUsers();
        $number_of_active_users = Statistics::countUsers(
            null,
            null,
            null,
            true
        );

        // The number of sessions
        $number_of_sessions = SessionManager::count_sessions(api_get_current_access_url_id());
        $packager = api_get_configuration_value('packager');
        if (empty($packager)) {
            $packager = 'chamilo';
        }

        $uniqueId = '';
        $entityManager = Database::getManager();
        /** @var BranchSyncRepository $branch */
        $repository = $entityManager->getRepository('ChamiloCoreBundle:BranchSync');
        /** @var BranchSync $branch */
        $branch = $repository->getTopBranch();
        if (is_a($branch, '\Chamilo\CoreBundle\Entity\BranchSync')) {
            $uniqueId = $branch->getUniqueId();
        }

        $data = [
            'url' => api_get_path(WEB_PATH),
            'campus' => api_get_setting('siteName'),
            'contact' => api_get_setting('emailAdministrator'), // the admin's e-mail, with the only purpose of being able to contact admins to inform about critical security issues
            'version' => $system_version,
            'numberofcourses' => $number_of_courses, // to sum up into non-personal statistics - see https://version.chamilo.org/stats/
            'numberofusers' => $number_of_users, // to sum up into non-personal statistics
            'numberofactiveusers' => $number_of_active_users, // to sum up into non-personal statistics
            'numberofsessions' => $number_of_sessions,
            //The donotlistcampus setting recovery should be improved to make
            // it true by default - this does not affect numbers counting
            'donotlistcampus' => api_get_setting('donotlistcampus'),
            'organisation' => api_get_setting('Institution'),
            'language' => api_get_setting('platformLanguage'), //helps us know the spread of language usage for campuses, by main language
            'adminname' => api_get_setting('administratorName').' '.api_get_setting('administratorSurname'), //not sure this is necessary...
            'ip' => $_SERVER['REMOTE_ADDR'], //the admin's IP address, with the only purpose of trying to geolocate portals around the globe to draw a map
            // Reference to the packager system or provider through which
            // Chamilo is installed/downloaded. Packagers can change this in
            // the default config file (main/install/configuration.dist.php)
            // or in the installed config file. The default value is 'chamilo'
            'packager' => $packager,
            'unique_id' => $uniqueId,
        ];

        $version = null;
        $client = new GuzzleHttp\Client();
        $url .= '?';
        foreach ($data as $k => $v) {
            $url .= urlencode($k).'='.urlencode($v).'&';
        }
        $res = $client->request('GET', $url, $options);
        if ($res->getStatusCode() == '200') {
            $versionData = $res->getHeader('X-Chamilo-Version');
            if (isset($versionData[0])) {
                $version = trim($versionData[0]);
            }
        }

        if (version_compare($system_version, $version, '<')) {
            $output = '<span style="color:red">'.get_lang('YourVersionNotUpToDate').'<br />
                       '.get_lang('LatestVersionIs').' <b>Chamilo '.$version.'</b>.  <br />
                       '.get_lang('YourVersionIs').' <b>Chamilo '.$system_version.'</b>.  <br />'.str_replace('http://www.chamilo.org', '<a href="http://www.chamilo.org">http://www.chamilo.org</a>', get_lang('PleaseVisitOurWebsite')).'</span>';
        } else {
            $output = '<span style="color:green">'.get_lang('VersionUpToDate').': Chamilo '.$version.'</span>';
        }

        return $output;
    }

    return '<span style="color:red">'.get_lang('ImpossibleToContactVersionServerPleaseTryAgain').'</span>';
}

/**
 * Display the latest news from the Chamilo Association for admins.
 *
 * @throws \GuzzleHttp\Exception\GuzzleException
 * @throws Exception
 *
 * @return string|void
 */
function getLatestNews()
{
    $url = 'https://version.chamilo.org/news/latest.php';

    $client = new Client();
    $response = $client->request(
        'GET',
        $url,
        [
            'query' => [
                'language' => api_get_interface_language(),
            ],
        ]
    );

    if ($response->getStatusCode() !== 200) {
        throw new Exception(get_lang('DenyEntry'));
    }

    return $response->getBody()->getContents();
}
