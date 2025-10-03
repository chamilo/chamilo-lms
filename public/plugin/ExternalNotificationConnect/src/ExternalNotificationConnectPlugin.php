<?php

/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\ExternalNotificationConnect\Entity\AccessToken;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\ToolsException;
use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ServerException;

class ExternalNotificationConnectPlugin extends Plugin
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

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function install(): void
    {
        $em = Database::getManager();

        $schemaManager = $em->getConnection()->createSchemaManager();

        $tableExists = $schemaManager->tablesExist(['plugin_ext_notif_conn_access_token']);

        if ($tableExists) {
            return;
        }

        $this->installDBTables();
    }

    public function uninstall(): void
    {
        $this->uninstallDBTables();
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
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

    private function installDBTables(): void
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

    private function uninstallDBTables(): void
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

    public function get_name()
    {
        return 'ExternalNotificationConnect';
    }
}
