<?php

/* For license terms, see /license.txt */

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessTokenInterface;
use TheNetworg\OAuth2\Client\Provider\Azure;

abstract class AzureCommand
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
    protected function generateOrRefreshToken(?AccessTokenInterface &$token)
    {
        if (!$token || ($token->getExpires() && !$token->getRefreshToken())) {
            $token = $this->provider->getAccessToken(
                'client_credentials',
                ['resource' => $this->provider->resource]
            );
        }
    }

    /**
     * @return Generator<int, array<string, string>>
     *
     * @throws Exception
     */
    protected function getAzureGroupMembers(string $groupUid): Generator
    {
        $userFields = [
            'mail',
            'mailNickname',
            'id',
        ];

        $query = sprintf(
            '$top=%d&$select=%s',
            AzureActiveDirectory::API_PAGE_SIZE,
            implode(',', $userFields)
        );

        $token = null;

        do {
            $this->generateOrRefreshToken($token);

            try {
                $azureGroupMembersRequest = $this->provider->request(
                    'get',
                    "groups/$groupUid/members?$query",
                    $token
                );
            } catch (Exception $e) {
                throw new Exception('Exception when requesting group members from Azure: '.$e->getMessage());
            }

            $azureGroupMembers = $azureGroupMembersRequest['value'] ?? [];

            foreach ($azureGroupMembers as $azureGroupMember) {
                yield $azureGroupMember;
            }

            $hasNextLink = false;

            if (!empty($azureGroupMembersRequest['@odata.nextLink'])) {
                $hasNextLink = true;
                $query = parse_url($azureGroupMembersRequest['@odata.nextLink'], PHP_URL_QUERY);
            }
        } while ($hasNextLink);
    }
}
