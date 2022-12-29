<?php

/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\Entity\XApi\ActivityProfile;
use Chamilo\PluginBundle\Entity\XApi\ActivityState;
use Chamilo\PluginBundle\Entity\XApi\Cmi5Item;
use Chamilo\PluginBundle\Entity\XApi\InternalLog;
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
use Xabbuh\XApi\Client\Api\StatementsApiClientInterface;
use Xabbuh\XApi\Client\XApiClientBuilder;
use Xabbuh\XApi\Model\Agent;
use Xabbuh\XApi\Model\IRI;
use Xabbuh\XApi\Serializer\Symfony\Serializer;

/**
 * Class XApiPlugin.
 */
class XApiPlugin extends Plugin implements HookPluginInterface
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

    /**
     * XApiPlugin constructor.
     */
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
                'xapi_activity_state',
                'xapi_activity_profile',
                'xapi_internal_log',

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
        $this->installInitialConfig();
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
        $portfolioItemAddedHook = XApiPortfolioItemAddedHookObserver::create();
        $portfolioItemCommentedHook = XApiPortfolioItemCommentedHookObserver::create();
        $portfolioItemHighlightedHook = XApiPortfolioItemHighlightedHookObserver::create();
        $portfolioDownloaded = XApiPortfolioDownloadedHookObserver::create();
        $portfolioItemScoredHook = XApiPortfolioItemScoredHookObserver::create();
        $portfolioCommentedScoredHook = XApiPortfolioCommentScoredHookObserver::create();
        $portfolioItemEditedHook = XApiPortfolioItemEditedHookObserver::create();
        $portfolioCommentEditedHook = XApiPortfolioCommentEditedHookObserver::create();

        HookLearningPathItemViewed::create()->detach($learningPathItemViewedHook);
        HookLearningPathEnd::create()->detach($learningPathEndHook);
        HookQuizQuestionAnswered::create()->detach($quizQuestionAnsweredHook);
        HookQuizEnd::create()->detach($quizEndHook);
        HookCreateCourse::create()->detach($createCourseHook);
        HookPortfolioItemAdded::create()->detach($portfolioItemAddedHook);
        HookPortfolioItemCommented::create()->detach($portfolioItemCommentedHook);
        HookPortfolioItemHighlighted::create()->detach($portfolioItemHighlightedHook);
        HookPortfolioDownloaded::create()->detach($portfolioDownloaded);
        HookPortfolioItemScored::create()->detach($portfolioItemScoredHook);
        HookPortfolioCommentScored::create()->detach($portfolioCommentedScoredHook);
        HookPortfolioItemEdited::create()->detach($portfolioItemEditedHook);
        HookPortfolioCommentEdited::create()->detach($portfolioCommentEditedHook);

        return 1;
    }

    public function uninstallPluginDbTables()
    {
        $em = Database::getManager();
        $pluginEm = self::getEntityManager();

        $schemaTool = new SchemaTool($em);
        $schemaTool->dropSchema(
            [
                $em->getClassMetadata(ActivityProfile::class),
                $em->getClassMetadata(ActivityState::class),
                $em->getClassMetadata(SharedStatement::class),
                $em->getClassMetadata(ToolLaunch::class),
                $em->getClassMetadata(LrsAuth::class),
                $em->getClassMetadata(Cmi5Item::class),
                $em->getClassMetadata(InternalLog::class),
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

    public function getXApiStatementClient(): StatementsApiClientInterface
    {
        return $this->createXApiClient()->getStatementsApiClient();
    }

    public function getXapiStatementCronClient(): StatementsApiClientInterface
    {
        $lrsUrl = $this->get(self::SETTING_CRON_LRS_URL);
        $lrsUsername = $this->get(self::SETTING_CRON_LRS_AUTH_USERNAME);
        $lrsPassword = $this->get(self::SETTING_CRON_LRS_AUTH_PASSWORD);

        return $this
            ->createXApiClient(
                empty($lrsUrl) ? null : $lrsUrl,
                empty($lrsUsername) ? null : $lrsUsername,
                empty($lrsPassword) ? null : $lrsPassword
            )
            ->getStatementsApiClient();
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
        $portfolioItemAddedHook = XApiPortfolioItemAddedHookObserver::create();
        $portfolioItemCommentedHook = XApiPortfolioItemCommentedHookObserver::create();
        $portfolioItemViewedHook = XApiPortfolioItemViewedHookObserver::create();
        $portfolioItemHighlightedHook = XApiPortfolioItemHighlightedHookObserver::create();
        $portfolioDownloadedHook = XApiPortfolioDownloadedHookObserver::create();
        $portfolioItemScoredHook = XApiPortfolioItemScoredHookObserver::create();
        $portfolioCommentScoredHook = XApiPortfolioCommentScoredHookObserver::create();
        $portfolioItemEditedHook = XApiPortfolioItemEditedHookObserver::create();
        $portfolioCommentEditedHook = XApiPortfolioCommentEditedHookObserver::create();

        $learningPathItemViewedEvent = HookLearningPathItemViewed::create();
        $learningPathEndEvent = HookLearningPathEnd::create();
        $quizQuestionAnsweredEvent = HookQuizQuestionAnswered::create();
        $quizEndEvent = HookQuizEnd::create();
        $portfolioItemAddedEvent = HookPortfolioItemAdded::create();
        $portfolioItemCommentedEvent = HookPortfolioItemCommented::create();
        $portfolioItemViewedEvent = HookPortfolioItemViewed::create();
        $portfolioItemHighlightedEvent = HookPortfolioItemHighlighted::create();
        $portfolioDownloadedEvent = HookPortfolioDownloaded::create();
        $portfolioItemScoredEvent = HookPortfolioItemScored::create();
        $portfolioCommentScoredEvent = HookPortfolioCommentScored::create();
        $portfolioItemEditedEvent = HookPortfolioItemEdited::create();
        $portfolioCommentEditedEvent = HookPortfolioCommentEdited::create();

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

        if ('true' === $this->get(self::SETTING_LRS_PORTFOLIO_ACTIVE)) {
            $portfolioItemAddedEvent->attach($portfolioItemAddedHook);
            $portfolioItemCommentedEvent->attach($portfolioItemCommentedHook);
            $portfolioItemViewedEvent->attach($portfolioItemViewedHook);
            $portfolioItemHighlightedEvent->attach($portfolioItemHighlightedHook);
            $portfolioDownloadedEvent->attach($portfolioDownloadedHook);
            $portfolioItemScoredEvent->attach($portfolioItemScoredHook);
            $portfolioCommentScoredEvent->attach($portfolioCommentScoredHook);
            $portfolioItemEditedEvent->attach($portfolioItemEditedHook);
            $portfolioCommentEditedEvent->attach($portfolioCommentEditedHook);
        } else {
            $portfolioItemAddedEvent->detach($portfolioItemAddedHook);
            $portfolioItemCommentedEvent->detach($portfolioItemCommentedHook);
            $portfolioItemViewedEvent->detach($portfolioItemViewedHook);
            $portfolioItemHighlightedEvent->detach($portfolioItemHighlightedHook);
            $portfolioDownloadedEvent->detach($portfolioDownloadedHook);
            $portfolioItemScoredEvent->detach($portfolioItemScoredHook);
            $portfolioCommentScoredEvent->detach($portfolioCommentScoredHook);
            $portfolioItemEditedEvent->detach($portfolioItemEditedHook);
            $portfolioCommentEditedEvent->detach($portfolioCommentEditedHook);
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
        // The $link param is set to "../plugin" as a hack to link correctly to the plugin URL in course tool.
        // Otherwise, the link en the course tool will link to "/main/" URL.
        $this->createLinkToCourseTool(
            $this->get_lang('ToolTinCan'),
            $courseId,
            'sessions_category.png',
            '../plugin/xapi/start.php',
            0,
            'authoring'
        );
    }

    /**
     * @param string $language
     *
     * @return mixed|string
     */
    public static function extractVerbInLanguage(Xabbuh\XApi\Model\LanguageMap $languageMap, $language)
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

    public function getLpResourceBlock(int $lpId)
    {
        $cidReq = api_get_cidreq(true, true, 'lp');
        $webPath = api_get_path(WEB_PLUGIN_PATH).'xapi/';
        $course = api_get_course_entity();
        $session = api_get_session_entity();

        $tools = Database::getManager()
            ->getRepository(ToolLaunch::class)
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

        /** @var ToolLaunch $tool */
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
                $em->getClassMetadata(ActivityState::class),
                $em->getClassMetadata(ActivityProfile::class),
                $em->getClassMetadata(InternalLog::class),
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
    private function installInitialConfig()
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

        api_add_setting(
            api_get_path(WEB_PATH).'plugin/xapi/lrs.php',
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
            ->execute(['category' => 'authoring', 'link' => '../plugin/xapi/start.php%']);
    }
}
