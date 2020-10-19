<?php
/* For licensing terms, see /license.txt */

use GuzzleHttp\RequestOptions;
use Http\Adapter\Guzzle6\Client;
use Http\Message\MessageFactory\GuzzleMessageFactory;
use Ramsey\Uuid\Uuid;
use Xabbuh\XApi\Client\XApiClientBuilder;
use Xabbuh\XApi\Client\XApiClientBuilderInterface;

/**
 * Class XApiPlugin.
 */
class XApiPlugin extends Plugin
{
    const SETTING_LRS_URL = 'lrs_url';
    const SETTING_LRS_AUTH = 'lrs_auth';
    const SETTING_UUID_NAMESPACE = 'uuid_namespace';

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
        $this->installUuid();
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
}
