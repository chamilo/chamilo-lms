<?php
/* For license terms, see /license.txt */

require __DIR__ . '/../../../../main/inc/global.inc.php';

if (PHP_SAPI !== 'cli') {
    exit('Run this script through the command line or comment this line in the code');
}

$plugin = AzureActiveDirectory::create();

$provider = $plugin->getProviderForApiGraph();

echo 'Synchronizing groups from Azure.'.PHP_EOL;

try {
    $token = $provider->getAccessToken(
        'client_credentials',
        ['resource' => $provider->resource]
    );

    $groupFields = [
        'id',
        'displayName',
        'description',
    ];

    $azureGroupsInfo = $provider->get(
        'groups?$select='.implode(',', $groupFields),
        $token
    );
} catch (Exception $e) {
    printf("%s - %s".PHP_EOL, time(), $e->getMessage());
    die;
}

printf("%s - Number of groups obtained %d".PHP_EOL, time(), count($azureGroupsInfo));

/** @var array<string, string> $azureGroupInfo */
foreach ($azureGroupsInfo as $azureGroupInfo) {
    $usergroup = new UserGroup();

    $exists = $usergroup->usergroup_exists($azureGroupInfo['displayName']);

    if (!$exists) {
        $groupId = $usergroup->save([
            'name' => $azureGroupInfo['displayName'],
            'description' => $azureGroupInfo['description'],
        ]);

        if ($groupId) {
            printf('%d - Class created: %s'.PHP_EOL, time(), $azureGroupInfo['displayName']);
        }
    } else {
        $groupId = $usergroup->getIdByName($azureGroupInfo['displayName']);

        if ($groupId) {
            $usergroup->subscribe_users_to_usergroup($groupId, []);

            printf('%d - Class exists, all users unsubscribed: %s'.PHP_EOL, time(), $azureGroupInfo['displayName']);
        }
    }

    try {
        $userFields = [
            'mail',
            'mailNickname',
            'id'
        ];
        $azureGroupMembers = $provider->get(
            sprintf('groups/%s/members?$select=%s', $azureGroupInfo['id'], implode(',', $userFields)),
            $token
        );
    } catch (Exception $e) {
        printf("%s - %s".PHP_EOL, time(), $e->getMessage());

        continue;
    }

    $newGroupMembers = [];

    foreach ($azureGroupMembers as $azureGroupMember) {
        $userId = $plugin->getUserIdByVerificationOrder($azureGroupMember, 'id');

        if ($userId) {
            $newGroupMembers[] = $userId;
        }
    }

    $usergroup->subscribe_users_to_usergroup($groupId, $newGroupMembers);
    printf(
        '%d - User IDs subscribed in class %s: %s'.PHP_EOL,
        time(),
        $azureGroupInfo['displayName'],
        implode(', ', $newGroupMembers)
    );
}
