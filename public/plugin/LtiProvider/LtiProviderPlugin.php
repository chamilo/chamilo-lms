<?php
/* For license terms, see /license.txt */

use Chamilo\PluginBundle\LtiProvider\Entity\Platform;
use Chamilo\PluginBundle\LtiProvider\Entity\PlatformKey;
use Chamilo\PluginBundle\LtiProvider\Entity\Result;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\Tools\SchemaTool;

/**
 * Description of LtiProvider.
 *
 * @author Christian Beeznest <christian.fasanando@beeznest.com>
 */
class LtiProviderPlugin extends Plugin
{
    public const TABLE_PLATFORM = 'plugin_lti_provider_platform';
    public const TABLE_PLATFORM_KEY = 'plugin_lti_provider_platform_key';
    public const LAUNCH_PATH = 'LtiProvider/tool/start.php';
    public const LOGIN_PATH = 'LtiProvider/tool/login.php';
    public const REDIRECT_PATH = 'LtiProvider/tool/start.php';
    public const JWKS_URL = 'LtiProvider/tool/jwks.php';

    public $isAdminPlugin = true;

    protected function __construct()
    {
        $version = '1.1';
        $author = 'Christian Beeznest';

        $message = Display::return_message($this->get_lang('Description'));

        $launchUrlHtml = '';
        $loginUrlHtml = '';
        $redirectUrlHtml = '';
        $jwksUrlHtml = '';

        if ($this->areTablesCreated()) {
            $publicKey = $this->getPublicKey();

            $pkHtml = $this->getSettingHtmlReadOnly(
                $this->get_lang('PublicKey'),
                'public_key',
                $publicKey
            );
            $launchUrlHtml = $this->getSettingHtmlReadOnly(
                $this->get_lang('LaunchUrl'),
                'launch_url',
                api_get_path(WEB_PLUGIN_PATH).self::LAUNCH_PATH
            );
            $loginUrlHtml = $this->getSettingHtmlReadOnly(
                $this->get_lang('LoginUrl'),
                'login_url',
                api_get_path(WEB_PLUGIN_PATH).self::LOGIN_PATH
            );
            $redirectUrlHtml = $this->getSettingHtmlReadOnly(
                $this->get_lang('RedirectUrl'),
                'redirect_url',
                api_get_path(WEB_PLUGIN_PATH).self::REDIRECT_PATH
            );
            $jwksUrlHtml = $this->getSettingHtmlReadOnly(
                $this->get_lang('KeySetUrlJwks'),
                'jwks_url',
                api_get_path(WEB_PLUGIN_PATH).self::JWKS_URL
            );
        } else {
            $pkHtml = $this->get_lang('GenerateKeyPairInfo');
        }

        $settings = [
            $message => 'html',
            'name' => 'hidden',
            $launchUrlHtml => 'html',
            $loginUrlHtml => 'html',
            $redirectUrlHtml => 'html',
            $jwksUrlHtml => 'html',
            $pkHtml => 'html',
            'enabled' => 'boolean',
        ];

        parent::__construct($version, $author, $settings);
    }

    /**
     * Get the value by default and readonly for the configuration html form.
     *
     * @param mixed $label
     * @param mixed $id
     * @param mixed $value
     */
    public function getSettingHtmlReadOnly($label, $id, $value): string
    {
        return '<div class="form-group">
                    <label for="lti_provider_'.$id.'" class="col-sm-2 control-label">'
            .$label.'</label>
                    <div class="col-sm-8">
                        <pre>'.$value.'</pre>
                    </div>
                    <div class="col-sm-2"></div>
                    <input type="hidden" name="'.$id.'" value="'.$value.'" />
                </div>';
    }

    /**
     * Get a selectbox with quizzes in courses, used for a tool provider.
     */
    public function getQuizzesSelect($clientId = null): string
    {
        $courses = CourseManager::get_courses_list();
        $toolProvider = $this->getToolProvider($clientId);

        $htmlcontent = '<div class="form-group select-tool" id="select-quiz">
        <label for="lti_provider_create_platform_kid" class="col-sm-2 control-label">'.$this->get_lang('ToolProvider').'</label>
        <div class="col-sm-8">
            <select name="tool_provider" class="sbox-tool" id="sbox-tool-quiz" disabled="disabled">';
        $htmlcontent .= '<option value="">-- '.$this->get_lang('SelectOneActivity').' --</option>';

        foreach ($courses as $course) {
            $courseId = (int) ($course['id'] ?? 0);
            $courseCode = $course['code'] ?? '';
            $courseTitle = $this->normalizeActivityLabel($course['title'] ?? '');

            if ($courseId <= 0 || empty($courseCode)) {
                continue;
            }

            $optgroupLabel = $courseTitle.' : '.get_lang('Tests');
            $htmlcontent .= '<optgroup label="'.htmlspecialchars($optgroupLabel, ENT_QUOTES, 'UTF-8').'">';

            $exerciseList = ExerciseLib::get_all_exercises_for_course_id(
                $courseId,
                0,
                false
            );

            foreach ($exerciseList as $exercise) {
                $exerciseId = (int) ($exercise['iid'] ?? 0);
                $exerciseTitle = $this->normalizeActivityLabel($exercise['title'] ?? '');

                if ($exerciseId <= 0 || '' === $exerciseTitle) {
                    continue;
                }

                $selectValue = "{$courseCode}@@quiz-{$exerciseId}";
                $selected = $toolProvider === $selectValue ? ' selected="selected"' : '';

                $htmlcontent .= '<option value="'.htmlspecialchars($selectValue, ENT_QUOTES, 'UTF-8').'"'.$selected.'>'
                    .htmlspecialchars($exerciseTitle, ENT_QUOTES, 'UTF-8')
                    .'</option>';
            }

            $htmlcontent .= '</optgroup>';
        }

        $htmlcontent .= '</select>';
        $htmlcontent .= '</div>
        <div class="col-sm-2"></div>
    </div>';

        return $htmlcontent;
    }

    /**
     * Get a selectbox with learnpaths in courses, used for a tool provider.
     */
    public function getLearnPathsSelect($clientId = null): string
    {
        $courses = CourseManager::get_courses_list();
        $toolProvider = $this->getToolProvider($clientId);

        $htmlcontent = '<div class="form-group select-tool" id="select-lp" style="display:none">
        <label for="lti_provider_create_platform_kid" class="col-sm-2 control-label">'.$this->get_lang('ToolProvider').'</label>
        <div class="col-sm-8">
            <select name="tool_provider" class="sbox-tool" id="sbox-tool-lp" disabled="disabled">';
        $htmlcontent .= '<option value="">-- '.$this->get_lang('SelectOneActivity').' --</option>';

        foreach ($courses as $course) {
            $courseCode = $course['code'] ?? '';
            $courseTitle = $this->normalizeActivityLabel($course['title'] ?? '');

            if (empty($courseCode)) {
                continue;
            }

            $courseInfo = api_get_course_info($courseCode);
            if (empty($courseInfo)) {
                continue;
            }

            $optgroupLabel = $courseTitle.' : '.get_lang('Learning path');
            $htmlcontent .= '<optgroup label="'.htmlspecialchars($optgroupLabel, ENT_QUOTES, 'UTF-8').'">';

            $list = new LearnpathList(
                api_get_user_id(),
                $courseInfo
            );

            $flatList = $list->get_flat_list();
            foreach ($flatList as $id => $details) {
                $lpId = (int) $id;
                $lpName = $this->normalizeActivityLabel($details['lp_name'] ?? '');

                if ($lpId <= 0 || '' === $lpName) {
                    continue;
                }

                $selectValue = "{$courseCode}@@lp-{$lpId}";
                $selected = $toolProvider === $selectValue ? ' selected="selected"' : '';

                $htmlcontent .= '<option value="'.htmlspecialchars($selectValue, ENT_QUOTES, 'UTF-8').'"'.$selected.'>'
                    .htmlspecialchars($lpName, ENT_QUOTES, 'UTF-8')
                    .'</option>';
            }

            $htmlcontent .= '</optgroup>';
        }

        $htmlcontent .= '</select>';
        $htmlcontent .= '</div>
        <div class="col-sm-2"></div>
    </div>';

        return $htmlcontent;
    }

    /**
     * Get the public key.
     */
    public function getPublicKey(): string
    {
        if (!$this->areTablesCreated()) {
            return '';
        }

        $platformKey = $this->getOrCreatePlatformKey();

        return $platformKey ? $platformKey->getPublicKey() : '';
    }

    /**
     * Get or create the provider platform key pair.
     */
    public function getOrCreatePlatformKey(bool $flush = true): ?PlatformKey
    {
        if (!$this->areTablesCreated()) {
            return null;
        }

        $em = $this->getEntityManager();

        /** @var PlatformKey|null $platformKey */
        $platformKey = $em
            ->getRepository(PlatformKey::class)
            ->findOneBy([]);

        $mustGenerate = false;

        if (!$platformKey) {
            $platformKey = new PlatformKey();
            $mustGenerate = true;
        } else {
            if ('' === trim($platformKey->getKid())
                || '' === trim($platformKey->getPrivateKey())
                || '' === trim($platformKey->getPublicKey())
            ) {
                $mustGenerate = true;
            }
        }

        if ($mustGenerate) {
            $keyPair = self::generatePlatformKeys();

            $platformKey
                ->setKid($keyPair['kid'])
                ->setPublicKey($keyPair['public'])
                ->setPrivateKey($keyPair['private']);

            $em->persist($platformKey);

            if ($flush) {
                $em->flush();
            }
        }

        return $platformKey;
    }

    /**
     * Get the first access date of a user in a tool.
     */
    public function getUserFirstAccessOnToolLp($courseCode, $toolId, $userId): string
    {
        $dql = 'SELECT a.startDate
        FROM '.Result::class.' a
        WHERE
            a.courseCode = :courseCode AND
            a.toolName = :toolName AND
            a.toolId = :toolId AND
            a.userId = :userId
        ORDER BY a.startDate';

        $result = $this->getEntityManager()
            ->createQuery($dql)
            ->setParameter('courseCode', $courseCode)
            ->setParameter('toolName', 'lp')
            ->setParameter('toolId', (int) $toolId)
            ->setParameter('userId', (int) $userId)
            ->getArrayResult();

        $firstDate = '';
        if (isset($result[0])) {
            $startDate = $result[0]['startDate'];
            $firstDate = $startDate->format('Y-m-d H:i');
        }

        return $firstDate;
    }

    /**
     * Get the results of users in tools lti.
     */
    public function getToolLearnPathResult($startDate, $endDate): array
    {
        $dql = 'SELECT
            a.issuer,
            COUNT(DISTINCT a.userId) AS cnt
        FROM '.Result::class.' a
        WHERE
            a.toolName = :toolName AND
            a.startDate BETWEEN :startDate AND :endDate
        GROUP BY a.issuer';

        $issuersValues = $this->getEntityManager()
            ->createQuery($dql)
            ->setParameter('toolName', 'lp')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getResult();

        $result = [];

        if (!empty($issuersValues)) {
            foreach ($issuersValues as $issuerValue) {
                $issuer = $issuerValue['issuer'];

                $dqlLp = 'SELECT
                    a.toolId,
                    a.userId,
                    a.courseCode
                FROM '.Result::class.' a
                WHERE
                    a.toolName = :toolName AND
                    a.startDate BETWEEN :startDate AND :endDate AND
                    a.issuer = :issuer
                GROUP BY a.toolId, a.userId, a.courseCode';

                $lpValues = $this->getEntityManager()
                    ->createQuery($dqlLp)
                    ->setParameter('toolName', 'lp')
                    ->setParameter('startDate', $startDate)
                    ->setParameter('endDate', $endDate)
                    ->setParameter('issuer', $issuer)
                    ->getResult();

                $lps = [];
                foreach ($lpValues as $lp) {
                    $uinfo = api_get_user_info($lp['userId']);
                    $firstAccess = $this->getUserFirstAccessOnToolLp(
                        $lp['courseCode'],
                        $lp['toolId'],
                        $lp['userId']
                    );

                    $lps[$lp['toolId']]['users'][$lp['userId']] = [
                        'firstname' => $uinfo['firstname'],
                        'lastname' => $uinfo['lastname'],
                        'first_access' => $firstAccess,
                    ];
                }

                $result[] = [
                    'issuer' => $issuer,
                    'count_iss_users' => $issuerValue['cnt'],
                    'learnpaths' => $lps,
                ];
            }
        }

        return $result;
    }

    /**
     * Get the tool provider.
     */
    public function getToolProvider($clientId): string
    {
        $toolProvider = '';

        /** @var Platform|null $platform */
        $platform = $this->getEntityManager()
            ->getRepository(Platform::class)
            ->findOneBy(['clientId' => $clientId]);

        if ($platform) {
            $toolProvider = $platform->getToolProvider();
        }

        return $toolProvider;
    }

    public function getToolProviderVars($clientId): array
    {
        $toolProvider = $this->getToolProvider($clientId);

        if (empty($toolProvider) || !str_contains($toolProvider, '@@')) {
            return [
                'courseCode' => '',
                'toolName' => '',
                'toolId' => '',
            ];
        }

        [$courseCode, $tool] = explode('@@', $toolProvider, 2);

        if (!str_contains($tool, '-')) {
            return [
                'courseCode' => $courseCode,
                'toolName' => '',
                'toolId' => '',
            ];
        }

        [$toolName, $toolId] = explode('-', $tool, 2);

        return [
            'courseCode' => $courseCode,
            'toolName' => $toolName,
            'toolId' => $toolId,
        ];
    }

    /**
     * Get the class instance.
     */
    public static function create(): LtiProviderPlugin
    {
        static $result = null;

        return $result ?: $result = new self();
    }

    /**
     * Check whether the current user is a teacher in this context.
     */
    public static function isInstructor()
    {
        api_is_allowed_to_edit(false, true);
    }

    /**
     * Install the plugin. Set the database up.
     *
     * @throws \Doctrine\ORM\Tools\ToolsException
     * @throws \Doctrine\DBAL\Exception
     */
    public function install()
    {
        $em = $this->getEntityManager();
        $schemaManager = $em->getConnection()->createSchemaManager();

        if ($schemaManager->tablesExist([self::TABLE_PLATFORM, self::TABLE_PLATFORM_KEY])) {
            return;
        }

        $schemaTool = new SchemaTool($em);
        $schemaTool->createSchema(
            [
                $em->getClassMetadata(Platform::class),
                $em->getClassMetadata(PlatformKey::class),
                $em->getClassMetadata(Result::class),
            ]
        );

        $this->getOrCreatePlatformKey(true);
    }

    /**
     * Save configuration for plugin.
     *
     * @throws OptimisticLockException
     */
    public function performActionsAfterConfigure()
    {
        $em = $this->getEntityManager();

        /** @var PlatformKey|null $platformKey */
        $platformKey = $em
            ->getRepository(PlatformKey::class)
            ->findOneBy([]);

        if ('true' === $this->get('enabled')) {
            $this->getOrCreatePlatformKey(true);
        } else {
            if ($platformKey) {
                $em->remove($platformKey);
                $em->flush();
            }
        }

        return $this;
    }

    /**
     * Uninstall plugin. Clear the database.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function uninstall()
    {
        $em = $this->getEntityManager();

        if (!$em->getConnection()->createSchemaManager()->tablesExist([self::TABLE_PLATFORM])) {
            return;
        }

        $schemaTool = new SchemaTool($em);
        $schemaTool->dropSchema(
            [
                $em->getClassMetadata(Platform::class),
                $em->getClassMetadata(PlatformKey::class),
                $em->getClassMetadata(Result::class),
            ]
        );
    }

    public function trimParams(array &$params)
    {
        foreach ($params as $key => $value) {
            $newValue = preg_replace('/\s+/', ' ', $value);
            $params[$key] = trim($newValue);
        }
    }

    public function saveResult($values, $ltiLaunchId = null)
    {
        $em = $this->getEntityManager();

        if (!empty($ltiLaunchId)) {
            $repo = $em->getRepository(Result::class);

            /** @var Result|null $objResult */
            $objResult = $repo->findOneBy(
                [
                    'ltiLaunchId' => $ltiLaunchId,
                ]
            );

            if ($objResult) {
                $objResult->setScore($values['score']);
                $objResult->setProgress($values['progress']);
                $objResult->setDuration($values['duration']);
                $em->persist($objResult);
                $em->flush();

                return $objResult->getId();
            }
        } else {
            $objResult = new Result();
            $objResult
                ->setIssuer($values['issuer'])
                ->setUserId($values['user_id'])
                ->setClientUId($values['client_uid'])
                ->setCourseCode($values['course_code'])
                ->setToolId($values['tool_id'])
                ->setToolName($values['tool_name'])
                ->setScore(0)
                ->setProgress(0)
                ->setDuration(0)
                ->setStartDate(new DateTime())
                ->setUserIp(api_get_real_ip())
                ->setLtiLaunchId($values['lti_launch_id'])
            ;

            $em->persist($objResult);
            $em->flush();

            return $objResult->getId();
        }

        return false;
    }

    private function areTablesCreated(): bool
    {
        $entityManager = $this->getEntityManager();
        $connection = $entityManager->getConnection();

        return $connection->createSchemaManager()->tablesExist([self::TABLE_PLATFORM, self::TABLE_PLATFORM_KEY]);
    }

    /**
     * Generate a key pair and key id for the platform.
     *
     * Return a associative array like ['kid' => '...', 'private' => '...', 'public' => '...'].
     */
    private static function generatePlatformKeys(): array
    {
        $res = openssl_pkey_new(
            [
                'digest_alg' => 'sha256',
                'private_key_bits' => 2048,
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
            ]
        );

        $privateKey = '';
        openssl_pkey_export($res, $privateKey);

        $publicKey = openssl_pkey_get_details($res);

        return [
            'kid' => bin2hex(openssl_random_pseudo_bytes(10)),
            'private' => $privateKey,
            'public' => $publicKey['key'],
        ];
    }

    /**
     * Get a SimpleXMLElement object with the request received on php://input.
     *
     * @throws Exception
     */
    private function getRequestXmlElement(): ?SimpleXMLElement
    {
        $request = file_get_contents('php://input');

        if (empty($request)) {
            return null;
        }

        return new SimpleXMLElement($request);
    }

    private function normalizeActivityLabel(?string $label): string
    {
        $label = (string) $label;
        $label = html_entity_decode($label, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $label = strip_tags($label);
        $label = preg_replace('/\s+/u', ' ', $label);

        return trim($label);
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return Database::getManager();
    }
}
