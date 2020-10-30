<?php
/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Entity\CTool;
use Chamilo\PluginBundle\Entity\XApi\SharedStatement;
use Chamilo\PluginBundle\Entity\XApi\ToolLaunch;
use Doctrine\ORM\Tools\SchemaTool;
use GuzzleHttp\RequestOptions;
use Http\Adapter\Guzzle6\Client;
use Http\Message\MessageFactory\GuzzleMessageFactory;
use Ramsey\Uuid\Uuid;
use Xabbuh\XApi\Client\XApiClientBuilder;
use Xabbuh\XApi\Client\XApiClientBuilderInterface;

/**
 * Class XApiPlugin.
 */
class XApiPlugin extends Plugin implements HookPluginInterface
{
    const SETTING_LRS_URL = 'lrs_url';
    const SETTING_LRS_AUTH = 'lrs_auth';
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
        $version = '0.1';
        $author = [
            'Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>',
        ];
        $settings = [
            self::SETTING_UUID_NAMESPACE => 'text',
            self::SETTING_LRS_URL => 'text',
            self::SETTING_LRS_AUTH => 'text',
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
            ['xapi_shared_statement']
        );

        if ($tablesExists) {
            return;
        }

        $this->installPluginDbTables();
        $this->installUuid();
        $this->deleteCourseTools();
        $this->addCourseTools();
    }

    /**
     * @throws \Doctrine\ORM\Tools\ToolsException
     */
    private function installPluginDbTables()
    {
        $em = Database::getManager();

        $schemaTool = new SchemaTool($em);
        $schemaTool->createSchema(
            [
                $em->getClassMetadata(SharedStatement::class),
                $em->getClassMetadata(ToolLaunch::class),
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
     * Process to uninstall plugin.
     */
    public function uninstall()
    {
        $this->uninstallHook();
        $this->uninstallPluginDbTables();
        $this->deleteCourseTools();
    }

    /**
     * @inheritDoc
     */
    public function uninstallHook()
    {
        $learningPathItemViewedHook = XApiLearningPathItemViewedHookObserver::create();
        $learningPathEndHook = XApiLearningPathEndHookObserver::create();
        $quizQuestionAnsweredHook = XApiQuizQuestionAnsweredHookObserver::create();
        $quizEndHook = XApiQuizEndHookObserver::create();

        HookLearningPathItemViewed::create()->attach($learningPathItemViewedHook);
        HookLearningPathEnd::create()->detach($learningPathEndHook);
        HookQuizQuestionAnswered::create()->attach($quizQuestionAnsweredHook);
        HookQuizEnd::create()->attach($quizEndHook);

        return 1;
    }

    public function uninstallPluginDbTables()
    {
        $em = Database::getManager();

        $schemaTool = new SchemaTool($em);
        $schemaTool->dropSchema(
            [
                $em->getClassMetadata(SharedStatement::class),
                $em->getClassMetadata(ToolLaunch::class),
            ]
        );
    }

    /**
     * @return \Xabbuh\XApi\Client\Api\StateApiClientInterface
     */
    public function getXApiStateClient()
    {
        return $this->createXApiClient()->getStateApiClient();
    }

    /**
     * @return \Xabbuh\XApi\Client\Api\StatementsApiClientInterface
     */
    public function getXApiStatementClient()
    {
        return $this->createXApiClient()->getStatementsApiClient();
    }

    /**
     * @return \Xabbuh\XApi\Client\XApiClientInterface
     */
    public function createXApiClient()
    {
        $baseUrl = trim($this->get(self::SETTING_LRS_URL), "/ \t\n\r\0\x0B");

        $clientBuilder = new XApiClientBuilder();
        $clientBuilder
            ->setHttpClient(Client::createWithConfig([RequestOptions::VERIFY => false]))
            ->setRequestFactory(new GuzzleMessageFactory())
            ->setBaseUrl($baseUrl);

        return $this
            ->setAuthMethodToClient($clientBuilder)
            ->build();
    }

    /**
     * @param \Xabbuh\XApi\Client\XApiClientBuilderInterface $clientBuilder
     *
     * @return \Xabbuh\XApi\Client\XApiClientBuilderInterface
     */
    private function setAuthMethodToClient(XApiClientBuilderInterface $clientBuilder)
    {
        $authString = $this->get(self::SETTING_LRS_AUTH);

        $parts = explode(':', $authString);

        if (!empty($parts)) {
            $method = strtolower($parts[0]);

            switch ($method) {
                case 'basic':
                    return $clientBuilder->setAuth($parts[1], $parts[2]);
                case 'oauth':
                    return $clientBuilder->setOAuthCredentials($parts[1], $parts[2]);
            }
        }

        return $clientBuilder;
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

        $this->deleteCourseTools();
        $this->addCourseTools();

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function installHook()
    {
        return 0;
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
     * @return string
     */
    public function generateIri($value, $type)
    {
        return api_get_path(WEB_PATH)."xapi/$type/$value";
    }

    private function addCourseTools()
    {
        $courses = Database::getManager()
            ->createQuery('SELECT c.id FROM ChamiloCoreBundle:Course c')
            ->getResult();

        foreach ($courses as $course) {
            $this->createLinkToCourseTool(
                $this->get_title().':teacher',
                $course['id'],
                null,
                'xapi/launch/list.php'
            );
        }
    }

    private function deleteCourseTools()
    {
        Database::getManager()
            ->createQuery('DELETE FROM ChamiloCourseBundle:CTool t WHERE t.category = :category AND t.link LIKE :link')
            ->execute(['category' => 'plugin', 'link' => 'xapi/launch/list.php%']);

        Database::getManager()
            ->createQuery('DELETE FROM ChamiloCourseBundle:CTool t WHERE t.category = :category AND t.link LIKE :link')
            ->execute(['category' => 'plugin', 'link' => 'xapi/launch/tool.php%']);
    }

    /**
     * @param \Chamilo\PluginBundle\Entity\XApi\ToolLaunch $toolLaunch
     *
     * @return \Chamilo\CourseBundle\Entity\CTool|null
     */
    public function createLaunchCourseTool(ToolLaunch $toolLaunch)
    {
        $link ='xapi/launch/tool.php?'.http_build_query(
            [
                'id' => $toolLaunch->getId(),
            ]
        );

        return $this->createLinkToCourseTool(
            $toolLaunch->getTitle(),
            $toolLaunch->getCourse()->getId(),
            null,
            $link
        );
    }

    /**
     * @param \Chamilo\PluginBundle\Entity\XApi\ToolLaunch $toolLaunch
     *
     * @return \Chamilo\CourseBundle\Entity\CTool
     */
    public function getCourseToolFromLaunchTool(ToolLaunch $toolLaunch)
    {
        /** @var CTool $tool */
        $tool = Database::getManager()
            ->getRepository(CTool::class)
            ->findOneBy([
                'link' => 'xapi/launch/tool.php?id='.$toolLaunch->getId(),
                'cId' => $toolLaunch->getCourse()->getId(),
            ]);

        return $tool;
    }
}
