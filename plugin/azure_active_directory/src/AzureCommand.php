<?php

/* For license terms, see /license.txt */

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessTokenInterface;
use TheNetworg\OAuth2\Client\Provider\Azure;

class AzureCommand
{
    /**
     * @var AzureActiveDirectory
     */
    protected $plugin;
    /**
     * @var Azure
     */
    protected $provider;

    public function __construct()
    {
        $this->plugin = AzureActiveDirectory::create();
        $this->plugin->get_settings(true);
        $this->provider = $this->plugin->getProviderForApiGraph();
    }

    /**
     * @throws IdentityProviderException
     */
    protected function getToken(?AccessTokenInterface $currentToken = null): AccessTokenInterface
    {
        if (!$currentToken || ($currentToken->getExpires() && !$currentToken->getRefreshToken())) {
            return $this->provider->getAccessToken(
                'client_credentials',
                ['resource' => $this->provider->resource]
            );
        }

        return $currentToken;
    }
}
