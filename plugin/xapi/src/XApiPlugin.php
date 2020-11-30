<?php

/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\Entity\XApi\Cmi5Item;
use Chamilo\PluginBundle\Entity\XApi\LrsAuth;
use Chamilo\PluginBundle\Entity\XApi\SharedStatement;
use Chamilo\PluginBundle\Entity\XApi\ToolLaunch;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\SimplifiedXmlDriver;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Tools\SchemaTool;
use GuzzleHttp\RequestOptions;
use Http\Adapter\Guzzle6\Client;
use Http\Message\MessageFactory\GuzzleMessageFactory;
use Ramsey\Uuid\Uuid;
use Xabbuh\XApi\Client\XApiClientBuilder;
use Xabbuh\XApi\Model\Agent;
use Xabbuh\XApi\Model\IRI;
use Xabbuh\XApi\Serializer\Symfony\Serializer;

/**
 * Class XApiPlugin.
 */
class XApiPlugin extends Plugin implements HookPluginInterface
{
    const SETTING_LRS_URL = 'lrs_url';
    const SETTING_LRS_AUTH_USERNAME = 'lrs_auth_username';
    const SETTING_LRS_AUTH_PASSWORD = 'lrs_auth_password';
    const SETTING_UUID_NAMESPACE = 'uuid_namespace';
    const SETTING_LRS_LP_ITEM_ACTIVE = 'lrs_lp_item_viewed_active';
    const SETTING_LRS_LP_ACTIVE = 'lrs_lp_end_active';
    const SETTING_LRS_QUIZ_ACTIVE = 'lrs_quiz_active';
    const SETTING_LRS_QUIZ_QUESTION_ACTIVE = 'lrs_quiz_question_active';

    const VERB_TERMINATED = 'http://adlnet.gov/expapi/verbs/terminated';
    const VERB_COMPLETED = 'http://adlnet.gov/expapi/verbs/completed';
    const VERB_ANSWERED = 'http://adlnet.gov/expapi/verbs/answered';
    const VERB_VIEWED = 'http://id.tincanapi.com/verb/viewed';

    const IRI_QUIZ = 'http://adlnet.gov/expapi/activities/assessment';
    const IRI_QUIZ_QUESTION = 'http://adlnet.gov/expapi/activities/question';
    const IRI_LESSON = 'http://adlnet.gov/expapi/activities/lesson';
    const IRI_RESOURCE = 'http://id.tincanapi.com/activitytype/resource';
    const IRI_INTERACTION = 'http://adlnet.gov/expapi/activities/cmi.interaction';

    const DATA_TYPE_ATTEMPT = 'e_attempt';
    const DATA_TYPE_EXERCISE = 'e_exercise';
    const DATA_TYPE_LP_ITEM_VIEW = 'lp_item_view';
    const DATA_TYPE_LP_VIEW = 'lp_view';

    const TYPE_QUIZ = 'quiz';
    const TYPE_QUIZ_QUESTION = 'quiz_question';
    const TYPE_LP = 'lp';
    const TYPE_LP_ITEM = 'lp_item';

    const STATE_FIRST_LAUNCH = 'first_launch';
    const STATE_LAST_LAUNCH = 'last_launch';

    /**
     * XApiPlugin constructor.
     */
    protected function __construct()
    {
        $version = '0.1 (beta)';
        $author = [
            'Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>',
        ];
        $settings = [
            self::SETTING_UUID_NAMESPACE => 'text',

            self::SETTING_LRS_URL => 'text',
            self::SETTING_LRS_AUTH_USERNAME => 'text',
            self::SETTING_LRS_AUTH_PASSWORD => 'text',

            self::SETTING_LRS_LP_ITEM_ACTIVE => 'boolean',
            self::SETTING_LRS_LP_ACTIVE => 'boolean',
            self::SETTING_LRS_QUIZ_ACTIVE => 'boolean',
            self::SETTING_LRS_QUIZ_QUESTION_ACTIVE => 'boolean',
        ];

        parent::__construct(
            $version,
            implode(', ', $author),
            $settings
        );
    }

    /**
     * @return \XApiPlugin
     */
    public static function create()
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }

    /**
     * Process to install plugin.
     */
    public function install()
    {
        $em = Database::getManager();

        $tablesExists = $em->getConnection()->getSchemaManager()->tablesExist(
            [
                'xapi_shared_statement',
                'xapi_tool_launch',
                'xapi_lrs_auth',
                'xapi_cmi5_item',

                'xapi_attachment',
                'xapi_object',
                'xapi_result',
                'xapi_verb',
                'xapi_extensions',
                'xapi_context',
                'xapi_actor',
                'xapi_statement',
            ]
        );

        if ($tablesExists) {
            return;
        }

        $this->installPluginDbTables();
        $this->installUuid();
        $this->addCourseTools();
        $this->installHook();
    }

    /**
     * Process to uninstall plugin.
     */
    public function uninstall()
    {
        $this->uninstallHook();
        $this->uninstallPluginDbTables();
        $this->deleteCourseTools();
    }

    /**
     * {@inheritdoc}
     */
    public function uninstallHook()
    {
        $learningPathItemViewedHook = XApiLearningPathItemViewedHookObserver::create();
        $learningPathEndHook = XApiLearningPathEndHookObserver::create();
        $quizQuestionAnsweredHook = XApiQuizQuestionAnsweredHookObserver::create();
        $quizEndHook = XApiQuizEndHookObserver::create();
        $createCourseHook = XApiCreateCourseHookObserver::create();

        HookLearningPathItemViewed::create()->detach($learningPathItemViewedHook);
        HookLearningPathEnd::create()->detach($learningPathEndHook);
        HookQuizQuestionAnswered::create()->detach($quizQuestionAnsweredHook);
        HookQuizEnd::create()->detach($quizEndHook);
        HookCreateCourse::create()->detach($createCourseHook);

        return 1;
    }

    public function uninstallPluginDbTables()
    {
        $em = Database::getManager();
        $pluginEm = self::getEntityManager();

        $schemaTool = new SchemaTool($em);
        $schemaTool->dropSchema(
            [
                $em->getClassMetadata(SharedStatement::class),
                $em->getClassMetadata(ToolLaunch::class),
                $em->getClassMetadata(LrsAuth::class),
                $em->getClassMetadata(Cmi5Item::class),
            ]
        );

        $pluginSchemaTool = new SchemaTool($pluginEm);
        $pluginSchemaTool->dropSchema(
            [
                $pluginEm->getClassMetadata(\XApi\Repository\Doctrine\Mapping\Attachment::class),
                $pluginEm->getClassMetadata(\XApi\Repository\Doctrine\Mapping\StatementObject::class),
                $pluginEm->getClassMetadata(\XApi\Repository\Doctrine\Mapping\Result::class),
                $pluginEm->getClassMetadata(\XApi\Repository\Doctrine\Mapping\Verb::class),
                $pluginEm->getClassMetadata(\XApi\Repository\Doctrine\Mapping\Extensions::class),
                $pluginEm->getClassMetadata(\XApi\Repository\Doctrine\Mapping\Context::class),
                $pluginEm->getClassMetadata(\XApi\Repository\Doctrine\Mapping\Actor::class),
                $pluginEm->getClassMetadata(\XApi\Repository\Doctrine\Mapping\Statement::class),
            ]
        );
    }

    /**
     * @param string|null $lrsUrl
     * @param string|null $lrsAuthUsername
     * @param string|null $lrsAuthPassword
     *
     * @return \Xabbuh\XApi\Client\Api\StateApiClientInterface
     */
    public function getXApiStateClient($lrsUrl = null, $lrsAuthUsername = null, $lrsAuthPassword = null)
    {
        return $this
            ->createXApiClient($lrsUrl, $lrsAuthUsername, $lrsAuthPassword)
            ->getStateApiClient();
    }

    /**
     * @return \Xabbuh\XApi\Client\Api\StatementsApiClientInterface
     */
    public function getXApiStatementClient()
    {
        return $this->createXApiClient()->getStatementsApiClient();
    }

    /**
     * Perform actions after save the plugin configuration.
     *
     * @return \XApiPlugin
     */
    public function performActionsAfterConfigure()
    {
        $learningPathItemViewedHook = XApiLearningPathItemViewedHookObserver::create();
        $learningPathEndHook = XApiLearningPathEndHookObserver::create();
        $quizQuestionAnsweredHook = XApiQuizQuestionAnsweredHookObserver::create();
        $quizEndHook = XApiQuizEndHookObserver::create();

        $learningPathItemViewedEvent = HookLearningPathItemViewed::create();
        $learningPathEndEvent = HookLearningPathEnd::create();
        $quizQuestionAnsweredEvent = HookQuizQuestionAnswered::create();
        $quizEndEvent = HookQuizEnd::create();

        if ('true' === $this->get(self::SETTING_LRS_LP_ITEM_ACTIVE)) {
            $learningPathItemViewedEvent->attach($learningPathItemViewedHook);
        } else {
            $learningPathItemViewedEvent->detach($learningPathItemViewedHook);
        }

        if ('true' === $this->get(self::SETTING_LRS_LP_ACTIVE)) {
            $learningPathEndEvent->attach($learningPathEndHook);
        } else {
            $learningPathEndEvent->detach($learningPathEndHook);
        }

        if ('true' === $this->get(self::SETTING_LRS_QUIZ_ACTIVE)) {
            $quizQuestionAnsweredEvent->attach($quizQuestionAnsweredHook);
        } else {
            $quizQuestionAnsweredEvent->detach($quizQuestionAnsweredHook);
        }

        if ('true' === $this->get(self::SETTING_LRS_QUIZ_QUESTION_ACTIVE)) {
            $quizEndEvent->attach($quizEndHook);
        } else {
            $quizEndEvent->detach($quizEndHook);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function installHook()
    {
        $createCourseHook = XApiCreateCourseHookObserver::create();

        HookCreateCourse::create()->attach($createCourseHook);
    }

    /**
     * @param string $variable
     *
     * @return array
     */
    public function getLangMap($variable)
    {
        $platformLanguage = api_get_setting('platformLanguage');
        $platformLanguageIso = api_get_language_isocode($platformLanguage);

        $map = [];
        $map[$platformLanguageIso] = $this->getLangFromFile($variable, $platformLanguage);

        try {
            $interfaceLanguage = api_get_interface_language();
        } catch (Exception $e) {
            return $map;
        }

        if (!empty($interfaceLanguage) && $platformLanguage !== $interfaceLanguage) {
            $interfaceLanguageIso = api_get_language_isocode($interfaceLanguage);

            $map[$interfaceLanguageIso] = $this->getLangFromFile($variable, $interfaceLanguage);
        }

        return $map;
    }

    /**
     * @param string $value
     * @param string $type
     *
     * @return \Xabbuh\XApi\Model\IRI
     */
    public function generateIri($value, $type)
    {
        return IRI::fromString(
            api_get_path(WEB_PATH)."xapi/$type/$value"
        );
    }

    /**
     * @param int $courseId
     */
    public function addCourseToolForTinCan($courseId)
    {
        $this->createLinkToCourseTool(
            $this->get_lang('ToolTinCan'),
            $courseId,
            null,
            'xapi/tincan/index.php'
        );
    }

    /**
     * @param string $language
     *
     * @return mixed|string
     */
    public static function extractVerbInLanguage(\Xabbuh\XApi\Model\LanguageMap $languageMap, $language)
    {
        $iso = self::findLanguageIso($languageMap->languageTags(), $language);

        $text = current($languageMap);

        if (isset($languageMap[$iso])) {
            $text = trim($languageMap[$iso]);
        } elseif (isset($languageMap['und'])) {
            $text = $languageMap['und'];
        }

        return $text;
    }

    /**
     * @param array  $haystack
     * @param string $needle
     *
     * @return string
     */
    public static function findLanguageIso(array $haystack, $needle)
    {
        if (in_array($needle, $haystack)) {
            return $needle;
        }

        foreach ($haystack as $language) {
            if (strpos($language, $needle) === 0) {
                return $language;
            }
        }

        return $haystack[0];
    }

    public function generateLaunchUrl(
        $type,
        $launchUrl,
        $activityId,
        Agent $actor,
        $attemptId,
        $customLrsUrl = null,
        $customLrsUsername = null,
        $customLrsPassword = null,
        $viewSessionId = null
    ) {
        $lrsUrl = $customLrsUrl ?: $this->get(self::SETTING_LRS_URL);
        $lrsAuthUsername = $customLrsUsername ?: $this->get(self::SETTING_LRS_AUTH_USERNAME);
        $lrsAuthPassword = $customLrsPassword ?: $this->get(self::SETTING_LRS_AUTH_PASSWORD);

        $queryData = [
            'endpoint' => trim($lrsUrl, "/ \t\n\r\0\x0B"),
            'actor' => Serializer::createSerializer()->serialize($actor, 'json'),
            'registration' => $attemptId,
        ];

        if ('tincan' === $type) {
            $queryData['auth'] = 'Basic '.base64_encode(trim($lrsAuthUsername).':'.trim($lrsAuthPassword));
            $queryData['activity_id'] = $activityId;
        } elseif ('cmi5' === $type) {
            $queryData['fetch'] = api_get_path(WEB_PLUGIN_PATH).'xapi/cmi5/token.php?session='.$viewSessionId;
            $queryData['activityId'] = $activityId;
        }

        return $launchUrl.'?'.http_build_query($queryData, null, '&', PHP_QUERY_RFC3986);
    }

    /**
     * @return \Doctrine\ORM\EntityManager|null
     */
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

    /**
     * {@inheritdoc}
     */
    public function getAdminUrl()
    {
        $webPath = api_get_path(WEB_PLUGIN_PATH).$this->get_name();

        return "$webPath/admin.php";
    }

    /**
     * @throws \Doctrine\ORM\Tools\ToolsException
     */
    private function installPluginDbTables()
    {
        $em = Database::getManager();
        $pluginEm = self::getEntityManager();

        $schemaTool = new SchemaTool($em);
        $schemaTool->createSchema(
            [
                $em->getClassMetadata(SharedStatement::class),
                $em->getClassMetadata(ToolLaunch::class),
                $em->getClassMetadata(LrsAuth::class),
                $em->getClassMetadata(Cmi5Item::class),
            ]
        );

        $pluginSchemaTool = new SchemaTool($pluginEm);
        $pluginSchemaTool->createSchema(
            [
                $pluginEm->getClassMetadata(\XApi\Repository\Doctrine\Mapping\Attachment::class),
                $pluginEm->getClassMetadata(\XApi\Repository\Doctrine\Mapping\StatementObject::class),
                $pluginEm->getClassMetadata(\XApi\Repository\Doctrine\Mapping\Result::class),
                $pluginEm->getClassMetadata(\XApi\Repository\Doctrine\Mapping\Verb::class),
                $pluginEm->getClassMetadata(\XApi\Repository\Doctrine\Mapping\Extensions::class),
                $pluginEm->getClassMetadata(\XApi\Repository\Doctrine\Mapping\Context::class),
                $pluginEm->getClassMetadata(\XApi\Repository\Doctrine\Mapping\Actor::class),
                $pluginEm->getClassMetadata(\XApi\Repository\Doctrine\Mapping\Statement::class),
            ]
        );
    }

    /**
     * @throws \Exception
     */
    private function installUuid()
    {
        $uuidNamespace = Uuid::uuid1();

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
    }

    /**
     * @param string|null $lrsUrl
     * @param string|null $lrsAuthUsername
     * @param string|null $lrsAuthPassword
     *
     * @return \Xabbuh\XApi\Client\XApiClientInterface
     */
    private function createXApiClient($lrsUrl = null, $lrsAuthUsername = null, $lrsAuthPassword = null)
    {
        $baseUrl = $lrsUrl ?: $this->get(self::SETTING_LRS_URL);
        $lrsAuthUsername = $lrsAuthUsername ?: $this->get(self::SETTING_LRS_AUTH_USERNAME);
        $lrsAuthPassword = $lrsAuthPassword ?: $this->get(self::SETTING_LRS_AUTH_PASSWORD);

        $clientBuilder = new XApiClientBuilder();
        $clientBuilder
            ->setHttpClient(Client::createWithConfig([RequestOptions::VERIFY => false]))
            ->setRequestFactory(new GuzzleMessageFactory())
            ->setBaseUrl(trim($baseUrl, "/ \t\n\r\0\x0B"))
            ->setAuth(trim($lrsAuthUsername), trim($lrsAuthPassword));

        return $clientBuilder->build();
    }

    private function addCourseTools()
    {
        $courses = Database::getManager()
            ->createQuery('SELECT c.id FROM ChamiloCoreBundle:Course c')
            ->getResult();

        foreach ($courses as $course) {
            $this->addCourseToolForTinCan($course['id']);
        }
    }

    private function deleteCourseTools()
    {
        Database::getManager()
            ->createQuery('DELETE FROM ChamiloCourseBundle:CTool t WHERE t.category = :category AND t.link LIKE :link')
            ->execute(['category' => 'plugin', 'link' => 'xapi/tincan/index.php%']);

        Database::getManager()
            ->createQuery('DELETE FROM ChamiloCourseBundle:CTool t WHERE t.category = :category AND t.link LIKE :link')
            ->execute(['category' => 'plugin', 'link' => 'xapi/cmi5/index.php%']);
    }
}
