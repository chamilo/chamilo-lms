<?php

/* For license terms, see /license.txt */

use Chamilo\UserBundle\Entity\User;

class AzureSyncUsersCommand extends AzureCommand
{
    /**
     * @throws Exception
     *
     * @return Generator<int, string>
     */
    public function __invoke(): Generator
    {
        yield 'Synchronizing users from Azure.';

        /** @var array<string, int> $existingUsers */
        $existingUsers = [];

        foreach ($this->getAzureUsers() as $azureUserInfo) {
            try {
                $userId = $this->plugin->registerUser($azureUserInfo, 'id');
            } catch (Exception $e) {
                yield $e->getMessage();

                continue;
            }

            $existingUsers[$azureUserInfo['id']] = $userId;

            yield sprintf('User (ID %d) with received info: %s ', $userId, serialize($azureUserInfo));
        }

        yield '----------------';
        yield 'Updating users status';

        $roleGroups = $this->plugin->getGroupUidByRole();
        $roleActions = $this->plugin->getUpdateActionByRole();

        $userManager = UserManager::getManager();
        $em = Database::getManager();

        foreach ($roleGroups as $userRole => $groupUid) {
            $azureGroupMembersInfo = iterator_to_array($this->getAzureGroupMembers($groupUid));
            $azureGroupMembersUids = array_column($azureGroupMembersInfo, 'id');

            foreach ($azureGroupMembersUids as $azureGroupMembersUid) {
                $userId = $existingUsers[$azureGroupMembersUid] ?? null;

                if (!$userId) {
                    continue;
                }

                if (isset($roleActions[$userRole])) {
                    /** @var User $user */
                    $user = $userManager->find($userId);

                    $roleActions[$userRole]($user);

                    yield sprintf('User (ID %d) status %s', $userId, $userRole);
                }
            }

            $em->flush();
        }

        if ('true' === $this->plugin->get(AzureActiveDirectory::SETTING_DEACTIVATE_NONEXISTING_USERS)) {
            yield '----------------';

            yield 'Trying deactivate non-existing users in Azure';

            $users = UserManager::getRepository()->findByAuthSource('azure');
            $userIdList = array_map(
                function ($user) {
                    return $user->getId();
                },
                $users
            );

            $nonExistingUsers = array_diff($userIdList, $existingUsers);

            UserManager::deactivate_users($nonExistingUsers);

            yield sprintf(
                'Deactivated users IDs: %s',
                implode(', ', $nonExistingUsers)
            );
        }
    }
}
