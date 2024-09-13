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

        $usergroup = new UserGroup();

        $groupIdByUid = [];

        foreach ($this->getAzureGroups() as $azureGroupInfo) {
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

            $groupIdByUid[$azureGroupInfo['id']] = $groupId;
        }

        yield '----------------';
        yield 'Subscribing users to groups';

        foreach ($groupIdByUid as $azureGroupUid => $groupId) {
            $newGroupMembers = [];

            yield sprintf('Obtaining members for group (ID %d)', $groupId);

            foreach ($this->getAzureGroupMembers($azureGroupUid) as $azureGroupMember) {
                if ($userId = $this->plugin->getUserIdByVerificationOrder($azureGroupMember, 'id')) {
                    $newGroupMembers[] = $userId;
                }
            }

            if ($newGroupMembers) {
                $usergroup->subscribe_users_to_usergroup($groupId, $newGroupMembers);

                yield sprintf(
                    'User IDs subscribed in class (ID %d): %s',
                    $groupId,
                    implode(', ', $newGroupMembers)
                );
            }
        }
    }

    /**
     * @throws Exception
     *
     * @return Generator<int, array<string, string>>
     */
    private function getAzureGroups(): Generator
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

        $token = null;

        do {
            $this->generateOrRefreshToken($token);

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
}
