<?php
/* For license terms, see /license.txt */

use Chamilo\PluginBundle\Entity\LtiProvider\Platform;
use Chamilo\PluginBundle\Entity\LtiProvider\PlatformKey;
use Chamilo\PluginBundle\Entity\LtiProvider\Result;
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
    public const LAUNCH_PATH = 'lti_provider/tool/start.php';
    public const LOGIN_PATH = 'lti_provider/tool/login.php';
    public const REDIRECT_PATH = 'lti_provider/tool/start.php';
    public const JWKS_URL = 'lti_provider/tool/jwks.php';

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
     * @param $label
     * @param $id
     * @param $value
     *
     * @return string
     */
    public function getSettingHtmlReadOnly($label, $id, $value)
    {
        $html = '<div class="form-group">
                    <label for="lti_provider_'.$id.'" class="col-sm-2 control-label">'
            .$label.'</label>
                    <div class="col-sm-8">
                        <pre>'.$value.'</pre>
                    </div>
                    <div class="col-sm-2"></div>
                    <input type="hidden" name="'.$id.'" value="'.$value.'" />
                </div>';

        return $html;
    }

    /**
     * Get a selectbox with quizzes in courses , used for a tool provider.
     *
     * @param null $clientId
     *
     * @return string
     */
    public function getQuizzesSelect($clientId = null)
    {
        $courses = CourseManager::get_courses_list();
        $toolProvider = $this->getToolProvider($clientId);
        $htmlcontent = '<div class="form-group select-tool" id="select-quiz">
            <label for="lti_provider_create_platform_kid" class="col-sm-2 control-label">'.$this->get_lang('ToolProvider').'</label>
            <div class="col-sm-8">
                <select name="tool_provider" class="sbox-tool" id="sbox-tool-quiz" disabled="disabled">';
        $htmlcontent .= '<option value="">-- '.$this->get_lang('SelectOneActivity').' --</option>';
        foreach ($courses as $course) {
            $courseInfo = api_get_course_info($course['code']);
            $optgroupLabel = "{$course['title']} : ".get_lang('Quizzes');
            $htmlcontent .= '<optgroup label="'.$optgroupLabel.'">';
            $exerciseList = ExerciseLib::get_all_exercises_for_course_id(
                $courseInfo,
                0,
                $course['id'],
                false
            );
            foreach ($exerciseList as $key => $exercise) {
                $selectValue = "{$course['code']}@@quiz-{$exercise['iid']}";
                $htmlcontent .= '<option value="'.$selectValue.'" '.($toolProvider == $selectValue ? ' selected="selected"' : '').'>'.Security::remove_XSS($exercise['title']).'</option>';
            }
            $htmlcontent .= '</optgroup>';
        }
        $htmlcontent .= "</select>";
        $htmlcontent .= '   </div>
                    <div class="col-sm-2"></div>
                    </div>';

        return $htmlcontent;
    }

    /**
     * Get a selectbox with quizzes in courses , used for a tool provider.
     *
     * @param null $clientId
     *
     * @return string
     */
    public function getLearnPathsSelect($clientId = null)
    {
        $courses = CourseManager::get_courses_list();
        $toolProvider = $this->getToolProvider($clientId);
        $htmlcontent = '<div class="form-group select-tool" id="select-lp" style="display:none">
            <label for="lti_provider_create_platform_kid" class="col-sm-2 control-label">'.$this->get_lang('ToolProvider').'</label>
            <div class="col-sm-8">
                <select name="tool_provider" class="sbox-tool" id="sbox-tool-lp" disabled="disabled">';
        $htmlcontent .= '<option value="">-- '.$this->get_lang('SelectOneActivity').' --</option>';
        foreach ($courses as $course) {
            $courseInfo = api_get_course_info($course['code']);
            $optgroupLabel = "{$course['title']} : ".get_lang('Learnpath');
            $htmlcontent .= '<optgroup label="'.$optgroupLabel.'">';

            $list = new LearnpathList(
                api_get_user_id(),
                $courseInfo
            );

            $flatList = $list->get_flat_list();
            foreach ($flatList as $id => $details) {
                $selectValue = "{$course['code']}@@lp-{$id}";
                $htmlcontent .= '<option value="'.$selectValue.'" '.($toolProvider == $selectValue ? ' selected="selected"' : '').'>'.Security::remove_XSS($details['lp_name']).'</option>';
            }
            $htmlcontent .= '</optgroup>';
        }
        $htmlcontent .= "</select>";
        $htmlcontent .= '   </div>
                    <div class="col-sm-2"></div>
                    </div>';

        return $htmlcontent;
    }

    /**
     * Get the public key.
     */
    public function getPublicKey(): string
    {
        $publicKey = '';
        $platformKey = Database::getManager()
           ->getRepository('ChamiloPluginBundle:LtiProvider\PlatformKey')
           ->findOneBy([]);

        if ($platformKey) {
            $publicKey = $platformKey->getPublicKey();
        }

        return $publicKey;
    }

    /**
     * Get the first access date of a user in a tool.
     *
     * @param $courseCode
     * @param $toolId
     * @param $userId
     *
     * @return string
     */
    public function getUserFirstAccessOnToolLp($courseCode, $toolId, $userId)
    {
        $dql = "SELECT
                    a.startDate
                FROM  ChamiloPluginBundle:LtiProvider\Result a
                WHERE
                    a.courseCode = '$courseCode' AND
                    a.toolName = 'lp' AND
                    a.toolId = $toolId AND
                    a.userId = $userId
                ORDER BY a.startDate";
        $qb = Database::getManager()->createQuery($dql);
        $result = $qb->getArrayResult();

        $firstDate = '';
        if (isset($result[0])) {
            $startDate = $result[0]['startDate'];
            $firstDate = $startDate->format('Y-m-d H:i');
        }

        return $firstDate;
    }

    /**
     * Get the results of users in tools lti.
     *
     * @param $startDate
     * @param $endDate
     *
     * @return array
     */
    public function getToolLearnPathResult($startDate, $endDate)
    {
        $dql = "SELECT
                    a.issuer,
                    count(DISTINCT(a.userId)) as cnt
                FROM
                    ChamiloPluginBundle:LtiProvider\Result a
                WHERE
                    a.toolName = 'lp' AND
                    a.startDate BETWEEN '$startDate' AND '$endDate'
                GROUP BY a.issuer";
        $qb = Database::getManager()->createQuery($dql);
        $issuersValues = $qb->getResult();

        $result = [];
        if (!empty($issuersValues)) {
            foreach ($issuersValues as $issuerValue) {
                $issuer = $issuerValue['issuer'];
                $dqlLp = "SELECT
                    a.toolId,
                    a.userId,
                    a.courseCode
                FROM
                    ChamiloPluginBundle:LtiProvider\Result a
                WHERE
                    a.toolName = 'lp' AND
                    a.startDate BETWEEN '$startDate' AND '$endDate' AND
                    a.issuer = '".$issuer."'
                GROUP BY a.toolId, a.userId";
                $qbLp = Database::getManager()->createQuery($dqlLp);
                $lpValues = $qbLp->getResult();

                $lps = [];
                foreach ($lpValues as $lp) {
                    $uinfo = api_get_user_info($lp['userId']);
                    $firstAccess = self::getUserFirstAccessOnToolLp($lp['courseCode'], $lp['toolId'], $lp['userId']);
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
        $platform = Database::getManager()
            ->getRepository('ChamiloPluginBundle:LtiProvider\Platform')
            ->findOneBy(['clientId' => $clientId]);

        if ($platform) {
            $toolProvider = $platform->getToolProvider();
        }

        return $toolProvider;
    }

    public function getToolProviderVars($clientId): array
    {
        $toolProvider = $this->getToolProvider($clientId);
        list($courseCode, $tool) = explode('@@', $toolProvider);
        list($toolName, $toolId) = explode('-', $tool);
        $vars = ['courseCode' => $courseCode, 'toolName' => $toolName, 'toolId' => $toolId];

        return $vars;
    }

    /**
     * Get the class instance.
     *
     * @staticvar LtiProviderPlugin $result
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
     * Get the plugin directory name.
     */
    public function get_name(): string
    {
        return 'lti_provider';
    }

    /**
     * Install the plugin. Set the database up.
     *
     * @throws \Doctrine\ORM\Tools\ToolsException
     */
    public function install()
    {
        $em = Database::getManager();

        if ($em->getConnection()->getSchemaManager()->tablesExist([self::TABLE_PLATFORM])) {
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
    }

    /**
     * Save configuration for plugin.
     *
     * Generate a new key pair for platform when enabling plugin.
     *
     * @throws OptimisticLockException
     * @throws \Doctrine\ORM\ORMException
     *
     * @return $this|Plugin
     */
    public function performActionsAfterConfigure()
    {
        $em = Database::getManager();

        /** @var PlatformKey $platformKey */
        $platformKey = $em
            ->getRepository('ChamiloPluginBundle:LtiProvider\PlatformKey')
            ->findOneBy([]);

        if ($this->get('enabled') === 'true') {
            if (!$platformKey) {
                $platformKey = new PlatformKey();
            }

            $keyPair = self::generatePlatformKeys();

            $platformKey->setKid($keyPair['kid']);
            $platformKey->publicKey = $keyPair['public'];
            $platformKey->setPrivateKey($keyPair['private']);

            $em->persist($platformKey);
        } else {
            if ($platformKey) {
                $em->remove($platformKey);
            }
        }

        $em->flush();

        return $this;
    }

    /**
     * Unistall plugin. Clear the database.
     */
    public function uninstall()
    {
        $em = Database::getManager();

        if (!$em->getConnection()->getSchemaManager()->tablesExist([self::TABLE_PLATFORM])) {
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
        $em = Database::getManager();
        if (!empty($ltiLaunchId)) {
            $repo = $em->getRepository(Result::class);

            /** @var Result $objResult */
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
        $entityManager = Database::getManager();
        $connection = $entityManager->getConnection();

        return $connection->getSchemaManager()->tablesExist(self::TABLE_PLATFORM);
    }

    /**
     * Generate a key pair and key id for the platform.
     *
     * Return a associative array like ['kid' => '...', 'private' => '...', 'public' => '...'].
     */
    private static function generatePlatformKeys(): array
    {
        // Create the private and public key
        $res = openssl_pkey_new(
            [
                'digest_alg' => 'sha256',
                'private_key_bits' => 2048,
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
            ]
        );

        // Extract the private key from $res to $privateKey
        $privateKey = '';
        openssl_pkey_export($res, $privateKey);

        // Extract the public key from $res to $publicKey
        $publicKey = openssl_pkey_get_details($res);

        return [
            'kid' => bin2hex(openssl_random_pseudo_bytes(10)),
            'private' => $privateKey,
            'public' => $publicKey["key"],
        ];
    }

    /**
     * Get a SimpleXMLElement object with the request received on php://input.
     *
     * @throws Exception
     */
    private function getRequestXmlElement(): ?SimpleXMLElement
    {
        $request = file_get_contents("php://input");

        if (empty($request)) {
            return null;
        }

        return new SimpleXMLElement($request);
    }
}
