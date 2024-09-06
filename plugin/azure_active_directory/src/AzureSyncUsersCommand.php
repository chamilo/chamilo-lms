<?php

/* For license terms, see /license.txt */

use League\OAuth2\Client\Token\AccessTokenInterface;

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

        $token = $this->getToken();

        $existingUsers = [];

        foreach ($this->getAzureUsers($token) as $azureUserInfo) {
            try {
                $token = $this->getToken($token);

                $userId = $this->plugin->registerUser(
                    $token,
                    $this->provider,
                    $azureUserInfo,
                    'users/'.$azureUserInfo['id'].'/memberOf',
                    'id',
                    'id'
                );
            } catch (Exception $e) {
                yield $e->getMessage();

                continue;
            }

            $existingUsers[] = $userId;

            $userInfo = api_get_user_info($userId);

            yield sprintf('User info: %s', serialize($userInfo));
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

    /**
     * @throws Exception
     *
     * @return Generator<int, array<string, string>>
     */
    private function getAzureUsers(AccessTokenInterface $token): Generator
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

        $query = sprintf(
            '$top=%d&$select=%s',
            AzureActiveDirectory::API_PAGE_SIZE,
            implode(',', $userFields)
        );

        do {
            $token = $this->getToken($token);

            try {
                $azureUsersRequest = $this->provider->request(
                    'get',
                    "users?$query",
                    $token
                );
            } catch (Exception $e) {
                throw new Exception('Exception when requesting users from Azure: '.$e->getMessage());
            }

            $azureUsersInfo = $azureUsersRequest['value'] ?? [];

            foreach ($azureUsersInfo as $azureUserInfo) {
                yield $azureUserInfo;
            }

            $hasNextLink = false;

            if (!empty($azureUsersRequest['@odata.nextLink'])) {
                $hasNextLink = true;
                $query = parse_url($azureUsersRequest['@odata.nextLink'], PHP_URL_QUERY);
            }
        } while ($hasNextLink);
    }
}
