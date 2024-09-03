<?php
/* For license terms, see /license.txt */

require __DIR__ . '/../../../../main/inc/global.inc.php';

if (PHP_SAPI !== 'cli') {
    exit('Run this script through the command line or comment this line in the code');
}

$plugin = AzureActiveDirectory::create();

$provider = $plugin->getProviderForApiGraph();

echo 'Synchronizing users from Azure.'.PHP_EOL;

try {
    $token = $provider->getAccessToken(
        'client_credentials',
        ['resource' => $provider->resource]
    );

    $userFields = [
        'givenName',
        'surname',
        'mail',
        'userPrincipalName',
        'businessPhones',
        'mobilePhone',
        'accountEnabled',
        'mailNickname',
        'id'
    ];

    $azureUsersInfo = $provider->get(
        'users?$select='.implode(',', $userFields),
        $token
    );
} catch (Exception $e) {
    printf("%s - %s".PHP_EOL, time(), $e->getMessage());
    die;
}

printf("%s - Number of users obtained %d".PHP_EOL, time(), count($azureUsersInfo));

$existingUsers = [];

/** @var array $user */
foreach ($azureUsersInfo as $azureUserInfo) {
    try {
        $userId = $plugin->registerUser(
            $token,
            $provider,
            $azureUserInfo,
            'users/' . $azureUserInfo['id'] . '/memberOf',
            'id',
            'id'
        );

        $existingUsers[] = $userId;

        $userInfo = api_get_user_info($userId);

        printf("%s - UserInfo %s".PHP_EOL, time(), serialize($userInfo));
    } catch (Exception $e) {
        printf("%s - %s".PHP_EOL, time(), $e->getMessage());

        continue;
    }
}

if ('true' === $plugin->get(AzureActiveDirectory::SETTING_DEACTIVATE_NONEXISTING_USERS)) {
    echo '----------------'.PHP_EOL;
    printf('Trying deactivate non-existing users in Azure.'.PHP_EOL, time());

    $users = UserManager::getRepository()->findByAuthSource('azure');
    $userIdList = array_map(
        function ($user) {
            return $user->getId();
        },
        $users
    );

    $nonExistingUsers = array_diff($userIdList, $existingUsers);

    UserManager::deactivate_users($nonExistingUsers);
    printf(
        "%d - Deactivated users IDs: %s".PHP_EOL,
        time(),
        implode(', ', $nonExistingUsers)
    );
}
