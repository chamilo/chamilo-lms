<?php
/* For license terms, see /license.txt */

require __DIR__ . '/../../../../main/inc/global.inc.php';

if (PHP_SAPI !== 'cli') {
    exit('Run this script through the command line or comment this line in the code');
}

$plugin = AzureActiveDirectory::create();

$provider = $plugin->getProvider();
$provider->urlAPI = "https://graph.microsoft.com/v1.0/";
$provider->resource = "https://graph.microsoft.com/";
$provider->tenant = $plugin->get(AzureActiveDirectory::SETTING_TENANT_ID);
$provider->authWithResource = false;

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

        $userInfo = api_get_user_info($userId);

        printf("%s - UserInfo %s".PHP_EOL, time(), serialize($userInfo));
    } catch (Exception $e) {
        printf("%s - %s".PHP_EOL, time(), $e->getMessage());

        continue;
    }
}
