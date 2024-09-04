<?php

/* For license terms, see /license.txt */

use League\OAuth2\Client\Token\AccessTokenInterface;

class AzureSyncUsergroupsCommand extends AzureCommand
{
    /**
     * @throws Exception
     *
     * @return Generator<int, string>
     */
    public function __invoke(): Generator
    {
        yield 'Synchronizing groups from Azure.';

        $token = $this->provider->getAccessToken(
            'client_credentials',
            ['resource' => $this->provider->resource]
        );

        foreach ($this->getAzureGroups($token) as $azureGroupInfo) {
            $usergroup = new UserGroup();

            if ($usergroup->usergroup_exists($azureGroupInfo['displayName'])) {
                $groupId = $usergroup->getIdByName($azureGroupInfo['displayName']);

                if ($groupId) {
                    $usergroup->subscribe_users_to_usergroup($groupId, []);

                    yield sprintf('Class exists, all users unsubscribed: %s', $azureGroupInfo['displayName']);
                }
            } else {
                $groupId = $usergroup->save([
                    'name' => $azureGroupInfo['displayName'],
                    'description' => $azureGroupInfo['description'],
                ]);

                if ($groupId) {
                    yield sprintf('Class created: %s', $azureGroupInfo['displayName']);
                }
            }

            $newGroupMembers = [];

            foreach ($this->getAzureGroupMembers($token, $azureGroupInfo['id']) as $azureGroupMember) {
                if ($userId = $this->plugin->getUserIdByVerificationOrder($azureGroupMember, 'id')) {
                    $newGroupMembers[] = $userId;
                }
            }

            $usergroup->subscribe_users_to_usergroup($groupId, $newGroupMembers);

            yield sprintf(
                'User IDs subscribed in class %s: %s',
                $azureGroupInfo['displayName'],
                implode(', ', $newGroupMembers)
            );
        }
    }

    /**
     * @throws Exception
     *
     * @return Generator<int, array<string, string>>
     */
    private function getAzureGroups(AccessTokenInterface $token): Generator
    {
        $groupFields = [
            'id',
            'displayName',
            'description',
        ];

        $query = sprintf(
            '$top=%d&$select=%s',
            AzureActiveDirectory::API_PAGE_SIZE,
            implode(',', $groupFields)
        );

        do {
            try {
                $azureGroupsRequest = $this->provider->request('get', "groups?$query", $token);
            } catch (Exception $e) {
                throw new Exception('Exception when requesting groups from Azure: '.$e->getMessage());
            }

            $azureGroupsInfo = $azureGroupsRequest['value'] ?? [];

            foreach ($azureGroupsInfo as $azureGroupInfo) {
                yield $azureGroupInfo;
            }

            $hasNextLink = false;

            if (!empty($azureGroupsRequest['@odata.nextLink'])) {
                $hasNextLink = true;
                $query = parse_url($azureGroupsRequest['@odata.nextLink'], PHP_URL_QUERY);
            }
        } while ($hasNextLink);
    }

    /**
     * @throws Exception
     *
     * @return Generator<int, array<string, string>>
     */
    private function getAzureGroupMembers(AccessTokenInterface $token, string $groupObjectId): Generator
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
        $hasNextLink = false;

        do {
            try {
                $azureGroupMembersRequest = $this->provider->request(
                    'get',
                    "groups/$groupObjectId/members?$query",
                    $token
                );
            } catch (Exception $e) {
                throw new Exception('Exception when requesting group members from Azure: '.$e->getMessage());
            }

            $azureGroupMembers = $azureGroupMembersRequest['value'] ?? [];

            foreach ($azureGroupMembers as $azureGroupMember) {
                yield $azureGroupMember;
            }

            if (!empty($azureGroupMembersRequest['@odata.nextLink'])) {
                $hasNextLink = true;
                $query = parse_url($azureGroupMembersRequest['@odata.nextLink'], PHP_URL_QUERY);
            }
        } while ($hasNextLink);
    }
}
