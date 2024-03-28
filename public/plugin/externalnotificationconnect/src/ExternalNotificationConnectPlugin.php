<?php

/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\ExternalNotificationConnect\Entity\AccessToken;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\ToolsException;
use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ServerException;

class ExternalNotificationConnectPlugin extends Plugin implements HookPluginInterface
{
    public const SETTING_AUTH_URL = 'auth_url';
    public const SETTING_AUTH_USERNAME = 'auth_username';
    public const SETTING_AUTH_PASSWORD = 'auth_password';
    public const SETTING_NOTIFICATION_URL = 'notification_url';
    public const SETTING_NOTIFY_PORTFOLIO = 'notify_portfolio';
    public const SETTING_NOTIFY_LEARNPATH = 'notify_learnpath';

    protected function __construct()
    {
        $author = [
            'Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>',
        ];

        $settings = [
            'tool_enable' => 'boolean',
            self::SETTING_AUTH_URL => 'text',
            self::SETTING_AUTH_USERNAME => 'text',
            self::SETTING_AUTH_PASSWORD => 'text',
            self::SETTING_NOTIFICATION_URL => 'text',
            self::SETTING_NOTIFY_PORTFOLIO => 'boolean',
            self::SETTING_NOTIFY_LEARNPATH => 'boolean',
        ];

        parent::__construct(
            '1.0',
            implode('; ', $author),
            $settings
        );
    }

    public static function create(): ?ExternalNotificationConnectPlugin
    {
        static $result = null;

        return $result ?: $result = new self();
    }

    public function performActionsAfterConfigure(): ExternalNotificationConnectPlugin
    {
        $portfolioItemAddedEvent = HookPortfolioItemAdded::create();
        $portfolioItemEditedEvent = HookPortfolioItemEdited::create();
        $portfolioItemDeletedEvent = HookPortfolioItemDeleted::create();
        $portfolioItemVisibilityEvent = HookPortfolioItemVisibility::create();

        $portfolioItemAddedObserver = ExternalNotificationConnectPortfolioItemAddedHookObserver::create();
        $portfolioItemEditedObserver = ExternalNotificationConnectPortfolioItemEditedHookObserver::create();
        $portfolioItemDeletedObserver = ExternalNotificationConnectPortfolioItemDeletedHookObserver::create();
        $portfolioItemVisibilityObserver = ExternalNotificationConnectPortfolioItemVisibilityHookObserver::create();

        if ('true' === $this->get(self::SETTING_NOTIFY_PORTFOLIO)) {
            $portfolioItemAddedEvent->attach($portfolioItemAddedObserver);
            $portfolioItemEditedEvent->attach($portfolioItemEditedObserver);
            $portfolioItemDeletedEvent->attach($portfolioItemDeletedObserver);
            $portfolioItemVisibilityEvent->attach($portfolioItemVisibilityObserver);
        } else {
            $portfolioItemAddedEvent->detach($portfolioItemAddedObserver);
            $portfolioItemEditedEvent->detach($portfolioItemEditedObserver);
            $portfolioItemDeletedEvent->detach($portfolioItemDeletedObserver);
            $portfolioItemVisibilityEvent->detach($portfolioItemVisibilityObserver);
        }

        $lpCreatedEvent = HookLearningPathCreated::create();

        $lpCreatedObserver = ExternalNotificationConnectLearningPathCreatedHookObserver::create();

        if ('true' === $this->get(self::SETTING_NOTIFY_LEARNPATH)) {
            $lpCreatedEvent->attach($lpCreatedObserver);
        } else {
            $lpCreatedEvent->detach($lpCreatedObserver);
        }

        return $this;
    }

    public function installHook()
    {
    }

    public function uninstallHook()
    {
        $portfolioItemAddedEvent = HookPortfolioItemAdded::create();
        $portfolioItemEditedEvent = HookPortfolioItemEdited::create();
        $portfolioItemDeletedEvent = HookPortfolioItemDeleted::create();
        $portfolioItemVisibilityEvent = HookPortfolioItemVisibility::create();
        $lpCreatedEvent = HookLearningPathCreated::create();

        $portfolioItemAddedObserver = ExternalNotificationConnectPortfolioItemAddedHookObserver::create();
        $portfolioItemEditedObserver = ExternalNotificationConnectPortfolioItemEditedHookObserver::create();
        $portfolioItemDeletedObserver = ExternalNotificationConnectPortfolioItemDeletedHookObserver::create();
        $portfolioItemVisibilityObserver = ExternalNotificationConnectPortfolioItemVisibilityHookObserver::create();
        $lpCreatedObserver = ExternalNotificationConnectLearningPathCreatedHookObserver::create();

        $portfolioItemAddedEvent->detach($portfolioItemAddedObserver);
        $portfolioItemEditedEvent->detach($portfolioItemEditedObserver);
        $portfolioItemDeletedEvent->detach($portfolioItemDeletedObserver);
        $portfolioItemVisibilityEvent->detach($portfolioItemVisibilityObserver);
        $lpCreatedEvent->detach($lpCreatedObserver);
    }

    public function install()
    {
        $em = Database::getManager();

        $schemaManager = $em->getConnection()->getSchemaManager();

        $tableExists = $schemaManager->tablesExist(['plugin_ext_notif_conn_access_token']);

        if ($tableExists) {
            return;
        }

        $this->installDBTables();
        $this->installHook();
    }

    public function uninstall()
    {
        $this->uninstallHook();
        $this->uninstallDBTables();
    }

    /**
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\ORMException
     * @throws Exception
     */
    public function getAccessToken()
    {
        $em = Database::getManager();
        $tokenRepository = $em->getRepository(AccessToken::class);

        $accessToken = $tokenRepository->findOneBy(['isValid' => true]);

        if (!$accessToken) {
            $newToken = $this->requestAuthToken();

            $accessToken = (new AccessToken())
                ->setToken($newToken)
                ->setIsValid(true);

            $em->persist($accessToken);
            $em->flush();
        } else {
            $tks = explode('.', $accessToken->getToken());

            $payload = json_decode(JWT::urlsafeB64Decode($tks[1]), true);

            if (time() >= $payload['exp']) {
                $accessToken->setIsValid(false);

                $newToken = $this->requestAuthToken();

                $accessToken = (new AccessToken())
                    ->setToken($newToken)
                    ->setIsValid(true);

                $em->persist($accessToken);

                $em->flush();
            }
        }

        return $accessToken->getToken();
    }

    private function installDBTables()
    {
        $em = Database::getManager();

        try {
            (new SchemaTool($em))
                ->createSchema([
                    $em->getClassMetadata(AccessToken::class),
                ]);
        } catch (ToolsException $e) {
            return;
        }
    }

    private function uninstallDBTables()
    {
        $em = Database::getManager();

        (new SchemaTool($em))
            ->dropSchema([
                $em->getClassMetadata(AccessToken::class),
            ]);
    }

    /**
     * @throws Exception
     */
    private function requestAuthToken(): string
    {
        $client = new Client();

        try {
            $response = $client->request(
                'POST',
                $this->get(ExternalNotificationConnectPlugin::SETTING_AUTH_URL),
                [
                    'json' => [
                        'email' => $this->get(ExternalNotificationConnectPlugin::SETTING_AUTH_USERNAME),
                        'password' => $this->get(ExternalNotificationConnectPlugin::SETTING_AUTH_PASSWORD),
                    ],
                ]
            );
        } catch (ClientException|ServerException $e) {
            if (!$e->hasResponse()) {
                throw new Exception($e->getMessage());
            }

            $response = $e->getResponse();
        } catch (GuzzleException $e) {
            throw new Exception($e->getMessage());
        }

        $json = json_decode((string) $response->getBody(), true);

        if (201 !== $json['status']) {
            throw new Exception($json['message']);
        }

        return $json['data']['data']['token'];
    }
}
