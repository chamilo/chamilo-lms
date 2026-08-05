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

    public const HTTP_TIMEOUT = 8.0;
    public const HTTP_CONNECT_TIMEOUT = 4.0;
    private const TOKEN_EXPIRATION_MARGIN_SECONDS = 60;

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

    public function hasCompleteConfiguration(): bool
    {
        return '' !== trim((string) $this->get(self::SETTING_AUTH_URL))
            && '' !== trim((string) $this->get(self::SETTING_AUTH_USERNAME))
            && '' !== trim((string) $this->get(self::SETTING_AUTH_PASSWORD))
            && '' !== trim((string) $this->get(self::SETTING_NOTIFICATION_URL));
    }

    public function isPortfolioNotificationEnabled(): bool
    {
        return $this->hasCompleteConfiguration()
            && 'true' === $this->get(self::SETTING_NOTIFY_PORTFOLIO);
    }

    public function isLearningPathNotificationEnabled(): bool
    {
        return $this->hasCompleteConfiguration()
            && 'true' === $this->get(self::SETTING_NOTIFY_LEARNPATH);
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws Exception
     */
    public function getAccessToken(): string
    {
        $em = Database::getManager();
        $tokenRepository = $em->getRepository(AccessToken::class);

        /** @var AccessToken|null $accessToken */
        $accessToken = $tokenRepository->findOneBy(['isValid' => true]);

        if (!$accessToken instanceof AccessToken) {
            return $this->createAccessToken();
        }

        if ($this->isTokenExpired($accessToken->getToken())) {
            $accessToken->setIsValid(false);
            $em->persist($accessToken);
            $em->flush();

            return $this->createAccessToken();
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
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws Exception
     */
    private function createAccessToken(): string
    {
        $em = Database::getManager();

        $newToken = $this->requestAuthToken();

        $accessToken = (new AccessToken())
            ->setToken($newToken)
            ->setIsValid(true);

        $em->persist($accessToken);
        $em->flush();

        return $accessToken->getToken();
    }

    private function isTokenExpired(string $token): bool
    {
        $parts = explode('.', $token);

        if (!isset($parts[1])) {
            return true;
        }

        $payload = json_decode(JWT::urlsafeB64Decode($parts[1]), true);

        if (!\is_array($payload) || !isset($payload['exp']) || !is_numeric($payload['exp'])) {
            return true;
        }

        return time() >= ((int) $payload['exp'] - self::TOKEN_EXPIRATION_MARGIN_SECONDS);
    }

    /**
     * @throws Exception
     */
    private function requestAuthToken(): string
    {
        if (!$this->hasCompleteConfiguration()) {
            throw new Exception('External notification plugin configuration is incomplete.');
        }

        $client = new Client();

        try {
            $response = $client->request(
                'POST',
                trim((string) $this->get(self::SETTING_AUTH_URL)),
                [
                    'connect_timeout' => self::HTTP_CONNECT_TIMEOUT,
                    'http_errors' => false,
                    'json' => [
                        'email' => $this->get(self::SETTING_AUTH_USERNAME),
                        'password' => $this->get(self::SETTING_AUTH_PASSWORD),
                    ],
                    'timeout' => self::HTTP_TIMEOUT,
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

        if (!\is_array($json)) {
            throw new Exception('Authentication endpoint returned an invalid JSON response.');
        }

        $status = (int) ($json['status'] ?? $response->getStatusCode());

        if (201 !== $status) {
            $message = (string) ($json['message'] ?? 'Authentication endpoint rejected the request.');

            throw new Exception($message);
        }

        $token = (string) ($json['data']['data']['token'] ?? '');

        if ('' === trim($token)) {
            throw new Exception('Authentication endpoint did not return an access token.');
        }

        return $token;
    }
}
