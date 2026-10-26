<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Branch;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Repository\BranchRepository;
use Chamilo\CoreBundle\Service\Update\InstalledChamiloVersionProvider;
use Chamilo\CoreBundle\Service\Update\UpdateAvailabilityChecker;
use Chamilo\CoreBundle\Service\Update\UpdateConfiguration;
use Chamilo\CoreBundle\Service\Update\UpdateManifestClient;
use GuzzleHttp\Client;
use League\Flysystem\Filesystem;

require_once __DIR__.'/../global.inc.php';

api_protect_admin_script();

$action = isset($_REQUEST['a']) ? $_REQUEST['a'] : null;

switch ($action) {
    case 'update_changeable_setting':
        $url_id = api_get_current_access_url_id();
        $platformAdmin = api_is_platform_admin();

        // Close the session as we don't need it any further
        session_write_close();

        if ($platformAdmin && 1 == $url_id) {
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
        // Close the session as we don't need it any further
        session_write_close();

        echo version_check();
        break;
    case 'get_extra_content':
        // Close the session as we don't need it any further
        session_write_close();

        $blockName = isset($_POST['block']) ? Security::remove_XSS($_POST['block']) : null;

        if (empty($blockName)) {
            exit;
        }

        /** @var Filesystem $fileSystem */
        $fileSystem = Container::$container->get('home_filesystem');

        $dir = 'admin/';

        if (api_is_multiple_url_enabled()) {
            $accessUrlId = api_get_current_access_url_id();

            if (-1 != $accessUrlId) {
                $urlInfo = api_get_access_url($accessUrlId);
                $url = api_remove_trailing_slash(preg_replace('/https?:\/\//i', '', $urlInfo['url']));
                $cleanUrl = str_replace('/', '-', $url);
                $dir = "$cleanUrl/admin/";
            }
        }

        $filePath = $dir.$blockName.'_extra.html';

        if ($fileSystem->has($filePath)) {
            echo $fileSystem->read($dir.$blockName.'_extra.html');
        }
        break;
    case 'get_latest_news':
        session_write_close();

        if ('true' !== (string) api_get_setting('admin.chamilo_latest_news')) {
            break;
        }

        try {
            $json = getLatestNews();
            $items = json_decode($json, true);

            if (!is_array($items)) {
                throw new Exception('Unexpected news payload');
            }

            usort($items, fn (array $a, array $b) => strcmp($b['date'] ?? '', $a['date'] ?? ''));

            $latestNews = '';
            foreach (array_slice($items, 0, 3) as $item) {
                $date = htmlspecialchars(
                    api_format_date((string) ($item['date'] ?? ''), DATE_FORMAT_SHORT),
                    ENT_QUOTES,
                    'UTF-8'
                );
                $text = Security::remove_XSS((string) ($item['text'] ?? ''), COURSEMANAGER);

                $latestNews .= '<div class="news-item">'
                    .'<h5 class="news-item__date">'.$date.'</h5>'
                    .'<div class="news-item__text">'.$text.'</div>'
                    .'</div>';
            }

            echo appendSystemUpdateNotice($latestNews);
        } catch (\Throwable $e) {
            echo appendSystemUpdateNotice(
                Security::remove_XSS(get_lang('Could not load latest news at this time.'), COURSEMANAGER)
            );
        }
        break;
    case 'get_support':
        session_write_close();

        try {
            $json = getProSupport();
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

            echo Security::remove_XSS($data['text'] ?? '', COURSEMANAGER);
        } catch (\Throwable $e) {
            echo Security::remove_XSS(get_lang('Could not load support info at this time.'), COURSEMANAGER);
        }
        break;
}

/**
 * Notify the opted-in Chamilo registration server without checking versions.
 */
function version_check(): string
{
    // Portal registration is a separate opt-in from automatic update checks.
    // An unregistered portal must never send installation statistics.
    if ('true' !== (string) api_get_setting('platform.registered')) {
        return '';
    }

    return sendPortalRegistration();
}

/**
 * Report an explicitly registered portal to Chamilo. This is not a version
 * comparison: available updates are handled only by the modern updater.
 */
function sendPortalRegistration(): string
{
    try {
        /** @var InstalledChamiloVersionProvider $versionProvider */
        $versionProvider = Container::$container->get(InstalledChamiloVersionProvider::class);

        $uniqueId = '';
        /** @var BranchRepository $repository */
        $repository = Database::getManager()->getRepository(Branch::class);
        $branch = $repository->getTopBranch();
        if ($branch instanceof Branch) {
            $uniqueId = $branch->getUniqueId();
        }

        $data = [
            'url' => api_get_path(WEB_PATH),
            'campus' => api_get_setting('siteName'),
            'contact' => api_get_setting('emailAdministrator'),
            'version' => $versionProvider->getInstalledVersion(),
            'numberofcourses' => Statistics::countCourses(),
            'numberofusers' => Statistics::countUsers(),
            'numberofactiveusers' => Statistics::countUsers(null, null, true, true),
            'numberofsessions' => SessionManager::count_sessions(api_get_current_access_url_id()),
            'donotlistcampus' => api_get_setting('donotlistcampus'),
            'organisation' => api_get_setting('Institution'),
            'language' => api_get_setting('platformLanguage'),
            'adminname' => api_get_setting('administratorName').' '.api_get_setting('administratorSurname'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            'packager' => api_get_env_variable('PACKAGER', 'chamilo'),
            'unique_id' => $uniqueId,
        ];

        $response = (new Client(['timeout' => 6]))->request('GET', 'https://version.chamilo.org', [
            'query' => $data,
            'allow_redirects' => false,
        ]);

        if (200 !== $response->getStatusCode()) {
            throw new RuntimeException('Unexpected portal registration response.');
        }

        // The remote X-Chamilo-Version header is deliberately ignored. The
        // updater manifest is the only source used to announce new versions.
        return htmlspecialchars(get_lang('Registered'), ENT_QUOTES, 'UTF-8');
    } catch (\Throwable) {
        return htmlspecialchars(get_lang('Could not contact the Chamilo registration server.'), ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Display the latest news from the Chamilo Association for admins.
 *
 * @throws \GuzzleHttp\Exception\GuzzleException
 * @throws Exception
 *
 * @return string|void
 */
function getLatestNews(): string
{
    $url = 'https://version.chamilo.org/c/news/latest.php';
    $client = new Client([
        'verify' => false,
        'timeout' => 6,
        'http_errors' => false,
        'headers' => ['Accept' => 'application/json'],
    ]);
    $lang = str_replace('-', '_', (string) api_get_language_isocode());

    if ($lang === '') {
        $lang = 'en_US';
    }

    $response = $client->request('GET', $url, [
        'query' => ['language' => $lang, 'n' => 3],
    ]);

    if (200 !== $response->getStatusCode()) {
        throw new Exception(get_lang('Access denied'));
    }

    return (string) $response->getBody();
}

/**
 * Appends an update notice when the configured manifest reports a newer version.
 *
 * Development environments may use the local test manifest when development
 * update tools are explicitly enabled. Normal installations use the configured
 * HTTPS manifest source.
 */
function appendSystemUpdateNotice(string $html): string
{
    // News and automatic update checks are independent, and this gate must live
    // on the server so a direct request cannot trigger an unwanted lookup.
    if ('true' !== (string) api_get_setting('admin.automatic_update_checks')) {
        return $html;
    }

    try {
        /** @var UpdateConfiguration $configuration */
        $configuration = Container::$container->get(UpdateConfiguration::class);

        /** @var UpdateManifestClient $manifestClient */
        $manifestClient = Container::$container->get(UpdateManifestClient::class);

        /** @var UpdateAvailabilityChecker $availabilityChecker */
        $availabilityChecker = Container::$container->get(UpdateAvailabilityChecker::class);

        $manifestSource = $configuration->getDefaultManifestSource();
        $noticeSource = 'official';

        if ($configuration->allowsDevelopmentUpdateTools()) {
            $localManifestSource = $configuration->getLocalTestManifestSource();

            if (
                null !== $localManifestSource
                && is_file($localManifestSource)
                && is_readable($localManifestSource)
            ) {
                $manifestSource = $localManifestSource;
                $noticeSource = 'local-test';
            }
        }

        if (null === $manifestSource || '' === trim($manifestSource)) {
            return $html;
        }

        $manifest = $manifestClient->load($manifestSource);
        $availability = $availabilityChecker->check($manifest)->toArray();

        if (true !== ($availability['updateAvailable'] ?? false)) {
            return $html;
        }

        if ('local-test' === $noticeSource) {
            $updateUrl = api_get_path(WEB_PATH).'admin/system-update?'.http_build_query([
                'source' => 'local-test',
                'check' => '1',
            ]);

            $title = 'Test update available';
            $description = 'A local update package is available for testing the new update workflow.';
            $buttonLabel = 'Review test update';
        } else {
            $updateUrl = api_get_path(WEB_PATH).'admin/system-update?'.http_build_query([
                'source' => 'official',
                'check' => '1',
            ]);

            $targetVersion = htmlspecialchars(
                (string) ($availability['targetVersion'] ?? $manifest->getVersion()),
                ENT_QUOTES,
                'UTF-8'
            );

            $title = get_lang('Your version is not up-to-date');
            $description = get_lang('The latest version is').' <b>Chamilo '.$targetVersion.'</b>.';
            $buttonLabel = get_lang('System update');
        }

        $safeUpdateUrl = htmlspecialchars($updateUrl, ENT_QUOTES, 'UTF-8');
        $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $safeButtonLabel = htmlspecialchars($buttonLabel, ENT_QUOTES, 'UTF-8');

        return $html.'
            <div style="margin-top: 1rem; padding: 0.75rem; border: 1px solid #2563eb; border-radius: 0.5rem; background: #eff6ff;">
                <div style="font-weight: 600; color: #1e3a8a; margin-bottom: 0.35rem;">
                    '.$safeTitle.'
                </div>
                <div style="color: #1e40af; margin-bottom: 0.6rem;">
                    '.$description.'
                </div>
                <a href="'.$safeUpdateUrl.'" style="display: inline-block; padding: 0.45rem 0.8rem; border-radius: 0.35rem; background: #2563eb; color: #ffffff; text-decoration: none; font-weight: 600;">
                    '.$safeButtonLabel.'
                </a>
            </div>
        ';
    } catch (\Throwable) {
        // An unavailable update server must never break the administration dashboard.
        return $html;
    }
}

/**
 * Display the latest support services block.
 *
 * @throws \GuzzleHttp\Exception\GuzzleException
 * @throws Exception
 *
 * @return string|void
 */
function getProSupport(): string
{
    $url = 'https://version.chamilo.org/c/support/latest.php';
    $client = new Client([
        'verify' => false,
        'timeout' => 6,
        'http_errors' => false,
        'headers' => ['Accept' => 'application/json'],
    ]);
    $lang = str_replace('-', '_', (string) api_get_language_isocode() ?: 'en_US');

    $response = $client->request('GET', $url, [
        'query' => ['language' => $lang],
    ]);

    if (200 !== $response->getStatusCode()) {
        throw new Exception(get_lang('Access denied'));
    }

    return (string) $response->getBody();
}
