<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Plugin as PluginEntity;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\Tool;
use Chamilo\CoreBundle\Entity\XApiToolLaunch;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CTool;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\SimplifiedXmlDriver;
use Doctrine\ORM\ORMException;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Uid\Uuid;

/**
 * Class XApiPlugin.
 */
class XApiPlugin extends Plugin
{
    public const SETTING_LRS_URL = 'lrs_url';
    public const SETTING_LRS_AUTH_USERNAME = 'lrs_auth_username';
    public const SETTING_LRS_AUTH_PASSWORD = 'lrs_auth_password';
    public const SETTING_CRON_LRS_URL = 'cron_lrs_url';
    public const SETTING_CRON_LRS_AUTH_USERNAME = 'cron_lrs_auth_username';
    public const SETTING_CRON_LRS_AUTH_PASSWORD = 'cron_lrs_auth_password';
    public const SETTING_UUID_NAMESPACE = 'uuid_namespace';
    public const SETTING_LRS_LP_ITEM_ACTIVE = 'lrs_lp_item_viewed_active';
    public const SETTING_LRS_LP_ACTIVE = 'lrs_lp_end_active';
    public const SETTING_LRS_QUIZ_ACTIVE = 'lrs_quiz_active';
    public const SETTING_LRS_QUIZ_QUESTION_ACTIVE = 'lrs_quiz_question_active';
    public const SETTING_LRS_PORTFOLIO_ACTIVE = 'lrs_portfolio_active';

    public const STATE_FIRST_LAUNCH = 'first_launch';
    public const STATE_LAST_LAUNCH = 'last_launch';

    public $isAdminPlugin = true;
    public $isCoursePlugin = true;

    protected function __construct()
    {
        $version = '0.3 (beta)';
        $author = [
            'Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>',
        ];
        $settings = [
            self::SETTING_UUID_NAMESPACE => 'text',

            self::SETTING_LRS_URL => 'text',
            self::SETTING_LRS_AUTH_USERNAME => 'text',
            self::SETTING_LRS_AUTH_PASSWORD => 'text',

            self::SETTING_CRON_LRS_URL => 'text',
            self::SETTING_CRON_LRS_AUTH_USERNAME => 'text',
            self::SETTING_CRON_LRS_AUTH_PASSWORD => 'text',

            self::SETTING_LRS_LP_ITEM_ACTIVE => 'boolean',
            self::SETTING_LRS_LP_ACTIVE => 'boolean',
            self::SETTING_LRS_QUIZ_ACTIVE => 'boolean',
            self::SETTING_LRS_QUIZ_QUESTION_ACTIVE => 'boolean',
            self::SETTING_LRS_PORTFOLIO_ACTIVE => 'boolean',
        ];

        parent::__construct(
            $version,
            implode(', ', $author),
            $settings
        );
    }

    public static function create()
    {
        static $result = null;

        return $result ?: $result = new self();
    }

    public function install()
    {
        $this->initializePluginConfigurationForCurrentUrl();
        $this->addCourseTools();
    }

    public function uninstall()
    {
        $this->deleteCourseTools();
    }

    public function performActionsAfterConfigure()
    {
        $this->initializePluginConfigurationForCurrentUrl();

        return $this;
    }

    private function initializePluginConfigurationForCurrentUrl(): void
    {
        $em = Database::getManager();
        $pluginRepository = Container::getPluginRepository();
        $currentAccessUrl = Container::getAccessUrlUtil()->getCurrent();

        $pluginEntity = $pluginRepository->findOneByTitle($this->get_name());

        if (!$pluginEntity) {
            $pluginEntity = (new PluginEntity())
                ->setTitle($this->get_name())
                ->setInstalled(true)
                ->setInstalledVersion($this->get_version());

            if (AppPlugin::isOfficial($this->get_name())) {
                $pluginEntity->setSource(PluginEntity::SOURCE_OFFICIAL);
            }

            $em->persist($pluginEntity);
        }

        $pluginConfiguration = $pluginEntity->getOrCreatePluginConfiguration($currentAccessUrl);
        $configuration = $pluginConfiguration->getConfiguration() ?? [];

        $defaultLrsUrl = api_get_path(WEB_PLUGIN_PATH).'XApi/lrs';

        $defaults = [
            self::SETTING_UUID_NAMESPACE => Uuid::v1()->toRfc4122(),
            self::SETTING_LRS_URL => $defaultLrsUrl,
            self::SETTING_LRS_AUTH_USERNAME => '',
            self::SETTING_LRS_AUTH_PASSWORD => '',
            self::SETTING_CRON_LRS_URL => '',
            self::SETTING_CRON_LRS_AUTH_USERNAME => '',
            self::SETTING_CRON_LRS_AUTH_PASSWORD => '',
            self::SETTING_LRS_LP_ITEM_ACTIVE => false,
            self::SETTING_LRS_LP_ACTIVE => false,
            self::SETTING_LRS_QUIZ_ACTIVE => false,
            self::SETTING_LRS_QUIZ_QUESTION_ACTIVE => false,
            self::SETTING_LRS_PORTFOLIO_ACTIVE => false,
        ];

        foreach ($defaults as $key => $value) {
            if (!array_key_exists($key, $configuration) || null === $configuration[$key] || '' === $configuration[$key]) {
                $configuration[$key] = $value;
            }
        }

        $configuration[self::SETTING_LRS_URL] = $this->normalizeLrsUrl(
            isset($configuration[self::SETTING_LRS_URL]) ? (string) $configuration[self::SETTING_LRS_URL] : $defaultLrsUrl
        ) ?? $defaultLrsUrl;

        $pluginConfiguration->setConfiguration($configuration);

        $em->persist($pluginEntity);
        $em->flush();
    }

    public function getLangMap($variable)
    {
        $platformLanguage = api_get_setting('platformLanguage');
        $platformLanguageIso = api_get_language_isocode($platformLanguage);

        $map = [];
        $map[$platformLanguageIso] = $this->getLangFromFile($variable, $platformLanguage);

        if (function_exists('api_get_interface_language')) {
            $interfaceLanguage = api_get_interface_language();

            if (!empty($interfaceLanguage) && $platformLanguage !== $interfaceLanguage) {
                $interfaceLanguageIso = api_get_language_isocode($interfaceLanguage);
                $map[$interfaceLanguageIso] = $this->getLangFromFile($variable, $interfaceLanguage);
            }
        }

        return $map;
    }

    public function generateIri($value, $type): string
    {
        return api_get_path(WEB_PATH)."xapi/$type/$value";
    }

    /**
     * Build a plain xAPI actor payload compatible with cmi5 launch URLs and LRS requests.
     */
    public function buildActorPayload($user): array
    {
        $homePage = rtrim(api_get_path(WEB_PATH), '/').'/';
        $accountName = method_exists($user, 'getUsername')
            ? (string) $user->getUsername()
            : (string) $user->getFullName();

        return [
            'objectType' => 'Agent',
            'name' => (string) $user->getFullName(),
            'account' => [
                'homePage' => $homePage,
                'name' => $accountName,
            ],
        ];
    }

    public function buildTinCanActorPayload($user): array
    {
        $email = method_exists($user, 'getEmail')
            ? trim((string) $user->getEmail())
            : '';

        if ('' === $email) {
            return $this->buildActorPayload($user);
        }

        return [
            'objectType' => 'Agent',
            'name' => (string) $user->getFullName(),
            'mbox' => 'mailto:'.$email,
        ];
    }

    public function getTinCanStateId(int $toolId): string
    {
        return $this->generateIri('tool-'.$toolId, 'state');
    }

    public function fetchActivityStateDocument(
        string $activityId,
        array|string $actor,
        string $stateId,
        ?string $registration = null,
        ?string $customLrsUrl = null,
        ?string $customLrsUsername = null,
        ?string $customLrsPassword = null
    ): ?array {
        [$lrsUrl, $lrsAuthUsername, $lrsAuthPassword] = $this->resolveLrsConfiguration(
            $customLrsUrl,
            $customLrsUsername,
            $customLrsPassword
        );

        if ('' === $lrsUrl) {
            throw new Exception('LRS URL is not configured.');
        }

        $query = [
            'activityId' => $activityId,
            'agent' => json_encode(
                $this->normalizeActorPayload($actor),
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            ),
            'stateId' => $stateId,
        ];

        if (null !== $registration && '' !== trim($registration)) {
            $query['registration'] = trim($registration);
        }

        $url = rtrim($lrsUrl, '/').'/activities/state?'.http_build_query(
                $query,
                '',
                '&',
                PHP_QUERY_RFC3986
            );

        $response = $this->requestXApi(
            'GET',
            $url,
            null,
            $lrsAuthUsername,
            $lrsAuthPassword
        );

        if (404 === $response['status']) {
            return null;
        }

        if ($response['status'] < 200 || $response['status'] >= 300) {
            $message = 'xAPI state request failed with HTTP '.$response['status'].'.';

            if ('' !== trim($response['content'])) {
                $message .= ' '.$response['content'];
            }

            throw new Exception($message);
        }

        if ('' === trim($response['content'])) {
            return [];
        }

        $decoded = json_decode($response['content'], true);

        if (JSON_ERROR_NONE !== json_last_error() || !is_array($decoded)) {
            throw new Exception('Invalid state document JSON received from LRS.');
        }

        return $decoded;
    }

    public function storeActivityStateDocument(
        string $activityId,
        array|string $actor,
        string $stateId,
        array $documentData,
        ?string $registration = null,
        ?string $customLrsUrl = null,
        ?string $customLrsUsername = null,
        ?string $customLrsPassword = null
    ): void {
        [$lrsUrl, $lrsAuthUsername, $lrsAuthPassword] = $this->resolveLrsConfiguration(
            $customLrsUrl,
            $customLrsUsername,
            $customLrsPassword
        );

        if ('' === $lrsUrl) {
            throw new Exception('LRS URL is not configured.');
        }

        $query = [
            'activityId' => $activityId,
            'agent' => json_encode(
                $this->normalizeActorPayload($actor),
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            ),
            'stateId' => $stateId,
        ];

        if (null !== $registration && '' !== trim($registration)) {
            $query['registration'] = trim($registration);
        }

        $url = rtrim($lrsUrl, '/').'/activities/state?'.http_build_query(
                $query,
                '',
                '&',
                PHP_QUERY_RFC3986
            );

        $this->sendXApiRequest(
            'PUT',
            $url,
            $documentData,
            $lrsAuthUsername,
            $lrsAuthPassword
        );
    }

    public function fetchStatementsByRegistration(
        string $registration,
        ?string $customLrsUrl = null,
        ?string $customLrsUsername = null,
        ?string $customLrsPassword = null
    ): array {
        $registration = trim($registration);

        if ('' === $registration) {
            return [];
        }

        [$lrsUrl, $lrsAuthUsername, $lrsAuthPassword] = $this->resolveLrsConfiguration(
            $customLrsUrl,
            $customLrsUsername,
            $customLrsPassword
        );

        if ('' === $lrsUrl) {
            throw new Exception('LRS URL is not configured.');
        }

        $url = rtrim($lrsUrl, '/').'/statements?'.http_build_query(
                ['registration' => $registration],
                '',
                '&',
                PHP_QUERY_RFC3986
            );

        $response = $this->requestXApi(
            'GET',
            $url,
            null,
            $lrsAuthUsername,
            $lrsAuthPassword
        );

        if (404 === $response['status']) {
            return [];
        }

        if ($response['status'] < 200 || $response['status'] >= 300) {
            $message = 'xAPI statements request failed with HTTP '.$response['status'].'.';

            if ('' !== trim($response['content'])) {
                $message .= ' '.$response['content'];
            }

            throw new Exception($message);
        }

        if ('' === trim($response['content'])) {
            return [];
        }

        $decoded = json_decode($response['content'], true);

        if (JSON_ERROR_NONE !== json_last_error() || !is_array($decoded)) {
            throw new Exception('Invalid statements JSON received from LRS.');
        }

        $statements = [];
        if (isset($decoded['statements']) && is_array($decoded['statements'])) {
            $statements = $decoded['statements'];
        } elseif ($this->isSequentialArray($decoded)) {
            $statements = $decoded;
        }

        $filtered = array_filter(
            $statements,
            fn ($statement): bool => is_array($statement) && $this->statementMatchesRegistration($statement, $registration)
        );

        return array_values($filtered);
    }

    private function statementMatchesRegistration(array $statement, string $registration): bool
    {
        $topLevelRegistration = $statement['registration'] ?? null;
        if (is_string($topLevelRegistration) && '' !== trim($topLevelRegistration)) {
            return trim($topLevelRegistration) === $registration;
        }

        $contextRegistration = $statement['context']['registration'] ?? null;
        if (is_string($contextRegistration) && '' !== trim($contextRegistration)) {
            return trim($contextRegistration) === $registration;
        }

        return false;
    }

    private function isSequentialArray(array $array): bool
    {
        return array_keys($array) === range(0, count($array) - 1);
    }

    /**
     * Store the cmi5 LMS.LaunchData state document using a native HTTP request.
     */
    public function storeCmi5LaunchDataDocument(
        string $activityId,
        array $actor,
        string $registration,
        array $documentData,
        ?string $customLrsUrl = null,
        ?string $customLrsUsername = null,
        ?string $customLrsPassword = null
    ): void {
        [$lrsUrl, $lrsAuthUsername, $lrsAuthPassword] = $this->resolveLrsConfiguration(
            $customLrsUrl,
            $customLrsUsername,
            $customLrsPassword
        );

        if ('' === $lrsUrl) {
            throw new Exception('LRS URL is not configured.');
        }

        $url = rtrim($lrsUrl, '/').'/activities/state?'.http_build_query(
                [
                    'activityId' => $activityId,
                    'agent' => json_encode($actor, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    'stateId' => 'LMS.LaunchData',
                    'registration' => $registration,
                ],
                '',
                '&',
                PHP_QUERY_RFC3986
            );

        $this->sendXApiRequest(
            'PUT',
            $url,
            $documentData,
            $lrsAuthUsername,
            $lrsAuthPassword
        );
    }

    /**
     * Keep a lightweight statement client for compatibility with existing code paths.
     */
    public function getXApiStatementClient()
    {
        [$lrsUrl, $lrsAuthUsername, $lrsAuthPassword] = $this->resolveLrsConfiguration();

        return new class($this, $lrsUrl, $lrsAuthUsername, $lrsAuthPassword) {
            public function __construct(
                private XApiPlugin $plugin,
                private string $lrsUrl,
                private string $lrsAuthUsername,
                private string $lrsAuthPassword
            ) {
            }

            public function storeStatement(array $statement): void
            {
                $this->plugin->storeStatement(
                    $statement,
                    $this->lrsUrl,
                    $this->lrsAuthUsername,
                    $this->lrsAuthPassword
                );
            }
        };
    }

    public function getXapiStatementCronClient()
    {
        [$lrsUrl, $lrsAuthUsername, $lrsAuthPassword] = $this->resolveLrsConfiguration(
            $this->get(self::SETTING_CRON_LRS_URL),
            $this->get(self::SETTING_CRON_LRS_AUTH_USERNAME),
            $this->get(self::SETTING_CRON_LRS_AUTH_PASSWORD)
        );

        return new class($this, $lrsUrl, $lrsAuthUsername, $lrsAuthPassword) {
            public function __construct(
                private XApiPlugin $plugin,
                private string $lrsUrl,
                private string $lrsAuthUsername,
                private string $lrsAuthPassword
            ) {
            }

            public function storeStatement(array $statement): void
            {
                $this->plugin->storeStatement(
                    $statement,
                    $this->lrsUrl,
                    $this->lrsAuthUsername,
                    $this->lrsAuthPassword
                );
            }
        };
    }

    public function storeStatement(
        array $statement,
        ?string $customLrsUrl = null,
        ?string $customLrsUsername = null,
        ?string $customLrsPassword = null
    ): void {
        [$lrsUrl, $lrsAuthUsername, $lrsAuthPassword] = $this->resolveLrsConfiguration(
            $customLrsUrl,
            $customLrsUsername,
            $customLrsPassword
        );

        if ('' === $lrsUrl) {
            throw new Exception('LRS URL is not configured.');
        }

        $url = rtrim($lrsUrl, '/').'/statements';

        $this->sendXApiRequest(
            'POST',
            $url,
            $statement,
            $lrsAuthUsername,
            $lrsAuthPassword
        );
    }

    /**
     * Accept array-based language maps instead of old library objects.
     */
    public static function extractVerbInLanguage($languageMap, $language): string
    {
        if (is_string($languageMap)) {
            return trim($languageMap);
        }

        if (!is_array($languageMap) || empty($languageMap)) {
            return '';
        }

        $normalizedNeedle = strtolower(str_replace('_', '-', (string) $language));

        foreach ($languageMap as $key => $value) {
            $normalizedKey = strtolower(str_replace('_', '-', (string) $key));

            if ($normalizedKey === $normalizedNeedle) {
                return trim((string) $value);
            }

            if (str_starts_with($normalizedKey, $normalizedNeedle.'-')) {
                return trim((string) $value);
            }

            if (str_starts_with($normalizedNeedle, $normalizedKey.'-')) {
                return trim((string) $value);
            }
        }

        if (isset($languageMap['und'])) {
            return trim((string) $languageMap['und']);
        }

        foreach ($languageMap as $value) {
            if ('' !== trim((string) $value)) {
                return trim((string) $value);
            }
        }

        return '';
    }

    public static function findLanguageIso(array $haystack, $needle)
    {
        $normalizedNeedle = strtolower(str_replace('_', '-', (string) $needle));

        foreach ($haystack as $language) {
            $normalizedLanguage = strtolower(str_replace('_', '-', (string) $language));

            if ($normalizedLanguage === $normalizedNeedle) {
                return $language;
            }

            if (str_starts_with($normalizedLanguage, $normalizedNeedle.'-')) {
                return $language;
            }
        }

        return $haystack[0] ?? $needle;
    }

    public function generateLaunchUrl(
        $type,
        $launchUrl,
        $activityId,
        array|string $actor,
        $attemptId,
        $customLrsUrl = null,
        $customLrsUsername = null,
        $customLrsPassword = null,
        $viewSessionId = null
    ) {
        [$lrsUrl, $lrsAuthUsername, $lrsAuthPassword] = $this->resolveLrsConfiguration(
            $customLrsUrl,
            $customLrsUsername,
            $customLrsPassword
        );

        $lrsUrl = $this->normalizeLrsUrl($lrsUrl) ?? '';
        $actorJson = $this->normalizeActorForLaunch($actor);
        $courseContext = $this->getCourseContextQuery();

        $queryData = [
            'endpoint' => rtrim($lrsUrl, "/ \t\n\r\0\x0B"),
            'actor' => $actorJson,
            'registration' => (string) $attemptId,
        ];

        if ('tincan' === $type) {
            $queryData['auth'] = 'Basic '.base64_encode(
                    trim((string) $lrsAuthUsername).':'.trim((string) $lrsAuthPassword)
                );
            $queryData['activity_id'] = $activityId;
        } elseif ('cmi5' === $type) {
            $fetchUrl = api_get_path(WEB_PLUGIN_PATH).'XApi/cmi5/token.php';

            $queryData['fetch'] = $this->appendQueryToUrl(
                $fetchUrl,
                array_merge(
                    $courseContext,
                    [
                        'session' => (string) $viewSessionId,
                    ]
                )
            );
            $queryData['activityId'] = $activityId;
        }

        $finalUrl = $this->appendQueryToUrl($launchUrl, $queryData);

        // When the AU URL is local to Chamilo, preserve the course/session context.
        if ($this->isLocalChamiloUrl($launchUrl)) {
            $finalUrl = $this->appendQueryToUrl($finalUrl, $courseContext);
        }

        return $finalUrl;
    }

    /**
     * Normalize the actor payload for launch URLs.
     */
    private function normalizeActorForLaunch(array|string $actor): string
    {
        if (\is_array($actor)) {
            $json = json_encode($actor, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            if (false === $json) {
                throw new RuntimeException('Unable to encode actor payload.');
            }

            return $json;
        }

        $actor = trim($actor);

        if ('' === $actor) {
            throw new RuntimeException('Actor payload cannot be empty.');
        }

        json_decode($actor, true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new RuntimeException('Actor payload must be valid JSON.');
        }

        return $actor;
    }

    /**
     * Build the current Chamilo course context query params.
     */
    private function getCourseContextQuery(): array
    {
        return [
            'cid' => (int) api_get_course_int_id(),
            'sid' => (int) api_get_session_id(),
            'gid' => (int) api_get_group_id(),
            'gradebook' => isset($_GET['gradebook']) ? (int) $_GET['gradebook'] : 0,
            'origin' => (string) (api_get_origin() ?: ''),
        ];
    }

    /**
     * Append query parameters to a URL while preserving existing parameters.
     */
    private function appendQueryToUrl(string $url, array $params): string
    {
        $parts = parse_url($url);

        $existingQuery = [];
        if (!empty($parts['query'])) {
            parse_str($parts['query'], $existingQuery);
        }

        $mergedQuery = array_merge($existingQuery, $params);

        $scheme = isset($parts['scheme']) ? $parts['scheme'].'://' : '';
        $user = $parts['user'] ?? '';
        $pass = isset($parts['pass']) ? ':'.$parts['pass'] : '';
        $auth = '' !== $user ? $user.$pass.'@' : '';
        $host = $parts['host'] ?? '';
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $path = $parts['path'] ?? '';
        $fragment = isset($parts['fragment']) ? '#'.$parts['fragment'] : '';

        $query = http_build_query($mergedQuery, '', '&', PHP_QUERY_RFC3986);

        return $scheme.$auth.$host.$port.$path.('' !== $query ? '?'.$query : '').$fragment;
    }

    /**
     * Detect whether the launch URL points to this Chamilo instance.
     */
    private function isLocalChamiloUrl(string $url): bool
    {
        $url = trim($url);

        if ('' === $url) {
            return false;
        }

        // Relative URLs are local by definition.
        if (!preg_match('#^https?://#i', $url)) {
            return true;
        }

        $target = parse_url($url);
        $current = parse_url(api_get_path(WEB_PATH));

        if (empty($target['host']) || empty($current['host'])) {
            return false;
        }

        return 0 === strcasecmp((string) $target['host'], (string) $current['host']);
    }

    public static function getEntityManager()
    {
        $em = Database::getManager();

        $prefixes = [
            __DIR__.'/../php-xapi/repository-doctrine-orm/metadata' => 'XApi\Repository\Doctrine\Mapping',
        ];

        $driver = new SimplifiedXmlDriver($prefixes);
        $driver->setGlobalBasename('global');

        $config = Database::getDoctrineConfig(api_get_configuration_value('root_sys'));
        $config->setMetadataDriverImpl($driver);

        try {
            return EntityManager::create($em->getConnection()->getParams(), $config);
        } catch (ORMException $e) {
            api_not_allowed(true, $e->getMessage());
        }

        return null;
    }

    public function getAdminUrl()
    {
        $webPath = api_get_path(WEB_PLUGIN_PATH).$this->get_name();

        return "$webPath/admin.php";
    }

    public function getLpResourceBlock(int $lpId)
    {
        $cidReq = api_get_cidreq(true, true, 'lp');
        $webPath = api_get_path(WEB_PLUGIN_PATH).'XApi/';
        $course = api_get_course_entity();
        $session = api_get_session_entity();

        $tools = Database::getManager()
            ->getRepository(XApiToolLaunch::class)
            ->findByCourseAndSession($course, $session);

        $importIcon = Display::return_icon('import_scorm.png');
        $moveIcon = Display::url(
            Display::return_icon('move_everywhere.png', get_lang('Move'), [], ICON_SIZE_TINY),
            '#',
            ['class' => 'moved']
        );

        $return = '<ul class="lp_resource"><li class="lp_resource_element">'
            .$importIcon
            .Display::url(
                get_lang('Import'),
                $webPath."tool_import.php?$cidReq&".http_build_query(['lp_id' => $lpId])
            )
            .'</li>';

        foreach ($tools as $tool) {
            $toolAnchor = Display::url(
                Security::remove_XSS($tool->getTitle()),
                api_get_self()."?$cidReq&"
                .http_build_query(
                    ['action' => 'add_item', 'type' => TOOL_XAPI, 'file' => $tool->getId(), 'lp_id' => $lpId]
                ),
                ['class' => 'moved']
            );

            $return .= Display::tag(
                'li',
                $moveIcon.$importIcon.$toolAnchor,
                [
                    'class' => 'lp_resource_element',
                    'data_id' => $tool->getId(),
                    'data_type' => TOOL_XAPI,
                    'title' => $tool->getTitle(),
                ]
            );
        }

        $return .= '</ul>';

        return $return;
    }

    private function installInitialConfig()
    {
        $uuidNamespace = Uuid::v1()->toRfc4122();

        $pluginName = $this->get_name();
        $urlId = api_get_current_access_url_id();

        api_add_setting(
            $uuidNamespace,
            $pluginName.'_'.self::SETTING_UUID_NAMESPACE,
            $pluginName,
            'setting',
            'Plugins',
            $pluginName,
            '',
            '',
            '',
            $urlId,
            1
        );

        api_add_setting(
            api_get_path(WEB_PATH).'plugin/XApi/lrs',
            $pluginName.'_'.self::SETTING_LRS_URL,
            $pluginName,
            'setting',
            'Plugins',
            $pluginName,
            '',
            '',
            '',
            $urlId,
            1
        );
    }

    private function addCourseTools(): void
    {
        $this->install_course_fields_in_all_courses(true);
    }

    private function deleteCourseTools(): void
    {
        $this->uninstall_course_fields_in_all_courses();
    }

    private function getCourseToolIdentifier(): string
    {
        return 'XApi';
    }

    private function getCourseToolLabel(): string
    {
        return 'XApi';
    }

    private function resolveLrsConfiguration(
        ?string $customLrsUrl = null,
        ?string $customLrsUsername = null,
        ?string $customLrsPassword = null
    ): array {
        $rawLrsUrl = trim((string) ($customLrsUrl ?: $this->get(self::SETTING_LRS_URL)));
        $lrsUrl = $this->normalizeLrsUrl($rawLrsUrl) ?? '';

        $lrsAuthUsername = trim((string) ($customLrsUsername ?: $this->get(self::SETTING_LRS_AUTH_USERNAME)));
        $lrsAuthPassword = trim((string) ($customLrsPassword ?: $this->get(self::SETTING_LRS_AUTH_PASSWORD)));

        return [$lrsUrl, $lrsAuthUsername, $lrsAuthPassword];
    }

    private function normalizeActorPayload($actor): array
    {
        if (is_array($actor)) {
            return $actor;
        }

        if (is_string($actor)) {
            $decoded = json_decode($actor, true);
            if (JSON_ERROR_NONE === json_last_error() && is_array($decoded)) {
                return $decoded;
            }

            return [
                'objectType' => 'Agent',
                'name' => $actor,
            ];
        }

        return [
            'objectType' => 'Agent',
            'name' => get_lang('User'),
        ];
    }

    private function sendXApiRequest(
        string $method,
        string $url,
        array $payload,
        ?string $lrsAuthUsername = null,
        ?string $lrsAuthPassword = null
    ): void {
        $response = $this->requestXApi(
            $method,
            $url,
            $payload,
            $lrsAuthUsername,
            $lrsAuthPassword
        );

        if ($response['status'] < 200 || $response['status'] >= 300) {
            $message = 'xAPI request failed with HTTP '.$response['status'].'.';

            if (!empty($response['content'])) {
                $message .= ' '.$response['content'];
            }

            throw new Exception($message);
        }
    }

    private function requestXApi(
        string $method,
        string $url,
        ?array $payload = null,
        ?string $lrsAuthUsername = null,
        ?string $lrsAuthPassword = null
    ): array {
        $headers = [
            'Accept' => 'application/json',
            'X-Experience-API-Version' => '1.0.3',
        ];

        if (null !== $payload) {
            $headers['Content-Type'] = 'application/json';
        }

        if (!empty($lrsAuthUsername) || !empty($lrsAuthPassword)) {
            $headers['Authorization'] = 'Basic '.base64_encode(
                    (string) $lrsAuthUsername.':'.(string) $lrsAuthPassword
                );
        }

        $options = [
            'headers' => $headers,
            'verify_peer' => false,
            'verify_host' => false,
        ];

        if (null !== $payload) {
            $options['body'] = json_encode(
                $payload,
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            );
        }

        $client = HttpClient::create([
            'verify_peer' => false,
            'verify_host' => false,
        ]);

        $response = $client->request($method, $url, $options);

        return [
            'status' => $response->getStatusCode(),
            'content' => $response->getContent(false),
            'headers' => $response->getHeaders(false),
        ];
    }

    public function addCourseToolForTinCan(int $courseId): ?CTool
    {
        if (!$this->addCourseTool) {
            return null;
        }

        $course = api_get_course_entity($courseId);
        if (!$course) {
            return null;
        }

        $em = Database::getManager();
        $toolRepository = $em->getRepository(Tool::class);

        $toolIdentifier = $this->getCourseToolIdentifier();
        $toolLabel = $this->getCourseToolLabel();

        /** @var Tool|null $tool */
        $tool = $toolRepository->findOneBy(['title' => $toolIdentifier]);

        if (!$tool) {
            $tool = (new Tool())->setTitle($toolIdentifier);
            $em->persist($tool);
            $em->flush();
        }

        $existingCourseTools = $this->findCourseTools($courseId);

        if (!empty($existingCourseTools)) {
            /** @var CTool $courseTool */
            $courseTool = reset($existingCourseTools);
            $courseTool->setTitle($toolLabel);
            $em->persist($courseTool);
            $em->flush();

            return $courseTool;
        }

        $visibility = $this->isIconVisibleByDefault()
            ? ResourceLink::VISIBILITY_PUBLISHED
            : ResourceLink::VISIBILITY_DRAFT;

        $creator = api_get_user_entity();
        if (!$creator) {
            $creator = $course->getCreator();
        }

        $courseTool = (new CTool())
            ->setTool($tool)
            ->setTitle($toolLabel)
            ->setCourse($course)
            ->setParent($course)
            ->setCreator($creator)
            ->addCourseLink($course, null, null, $visibility);

        $em->persist($courseTool);
        $em->flush();

        return $courseTool;
    }

    public function install_course_fields($courseId, $add_tool_link = true)
    {
        if (!$add_tool_link) {
            return true;
        }

        $this->addCourseToolForTinCan((int) $courseId);

        return true;
    }

    public function uninstall_course_fields($courseId)
    {
        $course = api_get_course_entity((int) $courseId);

        if (!$course) {
            return false;
        }

        $em = Database::getManager();
        $courseTools = $this->findCourseTools((int) $courseId);

        foreach ($courseTools as $courseTool) {
            $em->remove($courseTool);
        }

        $em->flush();

        return true;
    }

    private function findCourseTools(int $courseId): array
    {
        $course = api_get_course_entity($courseId);

        if (!$course) {
            return [];
        }

        $em = Database::getManager();

        return $em->createQuery(
            'SELECT ct
             FROM Chamilo\CourseBundle\Entity\CTool ct
             LEFT JOIN ct.tool t
             WHERE ct.course = :course
               AND ct.session IS NULL
               AND (
                    t.title = :toolIdentifier
                    OR ct.title = :toolLabel
               )'
        )
            ->setParameter('course', $course)
            ->setParameter('toolIdentifier', $this->getCourseToolIdentifier())
            ->setParameter('toolLabel', $this->getCourseToolLabel())
            ->getResult();
    }

    public function normalizeLrsUrl(?string $lrsUrl): ?string
    {
        if (null === $lrsUrl) {
            return null;
        }

        $lrsUrl = trim($lrsUrl);

        if ('' === $lrsUrl) {
            return null;
        }

        $normalized = preg_replace(
            '#/plugin/XApi/lrs\.php(?=/|$)#',
            '/plugin/XApi/lrs',
            $lrsUrl
        );

        return $normalized ?: $lrsUrl;
    }
}
