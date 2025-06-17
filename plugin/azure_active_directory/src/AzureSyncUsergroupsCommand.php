<?php

/* For license terms, see /license.txt */

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

            try {
                foreach ($this->getAzureGroupMembers($azureGroupUid) as $azureGroupMember) {
                    if ($userId = $this->plugin->getUserIdByVerificationOrder($azureGroupMember)) {
                        $newGroupMembers[] = $userId;
                    }
                }
            } catch (Exception $e) {
                yield $e->getMessage();

                continue;
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
}
