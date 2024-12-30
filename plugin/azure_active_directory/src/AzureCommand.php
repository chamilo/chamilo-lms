<?php

/* For license terms, see /license.txt */

use Chamilo\PluginBundle\Entity\AzureActiveDirectory\AzureSyncState;
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
     * @throws Exception
     *
     * @return Generator<int, array<string, string>>
     */
    protected function getAzureUsers(): Generator
    {
        $userFields = [
            'givenName',
            'surname',
            'mail',
            'userPrincipalName',
            'businessPhones',
            'mobilePhone',
            'accountEnabled',
            'mailNickname',
            'id',
        ];

        $getUsersDelta = 'true' === $this->plugin->get(AzureActiveDirectory::SETTING_GET_USERS_DELTA);

        if ($getUsersDelta) {
            $usersDeltaLink = $this->plugin->getSyncState(AzureSyncState::USERS_DATALINK);

            $query = $usersDeltaLink
                ? $usersDeltaLink->getValue()
                : sprintf('$select=%s', implode(',', $userFields));
        } else {
            $query = sprintf(
                '$top=%d&$select=%s',
                AzureActiveDirectory::API_PAGE_SIZE,
                implode(',', $userFields)
            );
        }

        $token = null;

        do {
            $this->generateOrRefreshToken($token);

            try {
                $azureUsersRequest = $this->provider->request(
                    'get',
                    $getUsersDelta ? "users/delta?$query" : "users?$query",
                    $token
                );
            } catch (Exception $e) {
                throw new Exception('Exception when requesting users from Azure: '.$e->getMessage());
            }

            $azureUsersInfo = $azureUsersRequest['value'] ?? [];

            foreach ($azureUsersInfo as $azureUserInfo) {
                $azureUserInfo['mail'] = $azureUserInfo['mail'] ?? null;
                $azureUserInfo['surname'] = $azureUserInfo['surname'] ?? null;
                $azureUserInfo['givenName'] = $azureUserInfo['givenName'] ?? null;

                yield $azureUserInfo;
            }

            $hasNextLink = false;

            if (!empty($azureUsersRequest['@odata.nextLink'])) {
                $hasNextLink = true;
                $query = parse_url($azureUsersRequest['@odata.nextLink'], PHP_URL_QUERY);
            }

            if ($getUsersDelta && !empty($azureUsersRequest['@odata.deltaLink'])) {
                $this->plugin->saveSyncState(
                    AzureSyncState::USERS_DATALINK,
                    parse_url($azureUsersRequest['@odata.deltaLink'], PHP_URL_QUERY),
                );
            }
        } while ($hasNextLink);
    }

    /**
     * @throws Exception
     *
     * @return Generator<int, array<string, string>>
     */
    protected function getAzureGroups(): Generator
    {
        $groupFilter = $this->plugin->get(AzureActiveDirectory::SETTING_GROUP_FILTER);

        $groupFields = [
            'id',
            'displayName',
            'description',
        ];

        $getUsergroupsDelta = 'true' === $this->plugin->get(AzureActiveDirectory::SETTING_GET_USERGROUPS_DELTA);

        if ($getUsergroupsDelta) {
            $usergroupsDeltaLink = $this->plugin->getSyncState(AzureSyncState::USERGROUPS_DATALINK);

            $query = $usergroupsDeltaLink
                ? $usergroupsDeltaLink->getValue()
                : sprintf('$select=%s', implode(',', $groupFields));
        } else {
            $query = sprintf(
                '$top=%d&$select=%s',
                AzureActiveDirectory::API_PAGE_SIZE,
                implode(',', $groupFields)
            );
        }

        $token = null;

        do {
            $this->generateOrRefreshToken($token);

            try {
                $azureGroupsRequest = $this->provider->request(
                    'get',
                    $getUsergroupsDelta ? "groups/delta?$query" : "groups?$query",
                    $token
                );
            } catch (Exception $e) {
                throw new Exception('Exception when requesting groups from Azure: '.$e->getMessage());
            }

            $azureGroupsInfo = $azureGroupsRequest['value'] ?? [];

            foreach ($azureGroupsInfo as $azureGroupInfo) {
                if (!empty($groupFilter) &&
                    !preg_match("/$groupFilter/", $azureGroupInfo['displayName'])
                ) {
                    continue;
                }

                yield $azureGroupInfo;
            }

            $hasNextLink = false;

            if (!empty($azureGroupsRequest['@odata.nextLink'])) {
                $hasNextLink = true;
                $query = parse_url($azureGroupsRequest['@odata.nextLink'], PHP_URL_QUERY);
            }

            if ($getUsergroupsDelta && !empty($azureGroupsRequest['@odata.deltaLink'])) {
                $this->plugin->saveSyncState(
                    AzureSyncState::USERGROUPS_DATALINK,
                    parse_url($azureGroupsRequest['@odata.deltaLink'], PHP_URL_QUERY),
                );
            }
        } while ($hasNextLink);
    }

    /**
     * @throws Exception
     *
     * @return Generator<int, array<string, string>>
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
