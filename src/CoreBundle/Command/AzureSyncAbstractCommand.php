<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Entity\AzureSyncState;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\AuthenticationConfigHelper;
use Chamilo\CoreBundle\Helpers\AzureAuthenticatorHelper;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\AzureSyncStateRepository;
use Chamilo\CoreBundle\Repository\Node\UsergroupRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Generator;
use GuzzleHttp\Exception\GuzzleException;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessTokenInterface;
use Symfony\Component\Console\Command\Command;
use TheNetworg\OAuth2\Client\Provider\Azure;

use const PHP_URL_QUERY;

abstract class AzureSyncAbstractCommand extends Command
{
    protected Azure $provider;

    protected array $providerParams;

    public function __construct(
        protected readonly ClientRegistry $clientRegistry,
        AuthenticationConfigHelper $configHelper,
        protected readonly AzureAuthenticatorHelper $azureHelper,
        protected readonly AzureSyncStateRepository $syncStateRepo,
        protected readonly EntityManagerInterface $entityManager,
        protected readonly UserRepository $userRepository,
        protected readonly UsergroupRepository $usergroupRepository,
        protected readonly AccessUrlHelper $accessUrlHelper,
        protected readonly SettingsManager $settingsManager,
        protected readonly UserHelper $userHelper,
    ) {
        parent::__construct();

        $this->providerParams = $configHelper->getOAuthProviderConfig('azure');

        $this->provider = $this->clientRegistry->getClient('azure')->getOAuth2Provider();
    }

    /**
     * @throws GuzzleException
     * @throws IdentityProviderException
     */
    protected function generateOrRefreshToken(?AccessTokenInterface &$token): void
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
     */
    protected function getAzureUsers(): Generator
    {
        if ($this->providerParams['script_users_delta']) {
            $usersDeltaLink = $this->syncStateRepo->findOneBy(['title' => AzureSyncState::USERS_DATALINK]);

            $query = $usersDeltaLink
                ? $usersDeltaLink->getValue()
                : \sprintf('$select=%s', implode(',', AzureAuthenticatorHelper::QUERY_USER_FIELDS));
        } else {
            $query = \sprintf(
                '$top=%d&$select=%s',
                AzureSyncState::API_PAGE_SIZE,
                implode(',', AzureAuthenticatorHelper::QUERY_USER_FIELDS)
            );
        }

        $token = null;

        do {
            try {
                $this->generateOrRefreshToken($token);

                $azureUsersRequest = $this->provider->request(
                    'get',
                    $this->providerParams['script_users_delta'] ? "/v1.0/users/delta?$query" : "/v1.0/users?$query",
                    $token
                );
            } catch (GuzzleException|Exception $e) {
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

            if ($this->providerParams['script_users_delta'] && !empty($azureUsersRequest['@odata.deltaLink'])) {
                $this->syncStateRepo->save(
                    AzureSyncState::USERS_DATALINK,
                    parse_url($azureUsersRequest['@odata.deltaLink'], PHP_URL_QUERY),
                );
            }
        } while ($hasNextLink);
    }

    /**
     * @throws Exception
     */
    protected function getAzureGroupMembers(string $groupUid): Generator
    {
        $query = \sprintf(
            '$top=%d&$select=%s',
            AzureSyncState::API_PAGE_SIZE,
            implode(',', AzureAuthenticatorHelper::QUERY_GROUP_MEMBERS_FIELDS)
        );

        $token = null;

        do {
            try {
                $this->generateOrRefreshToken($token);

                $azureGroupMembersRequest = $this->provider->request(
                    'get',
                    "/v1.0/groups/$groupUid/members?$query",
                    $token
                );
            } catch (GuzzleException|Exception $e) {
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

    /**
     * @throws Exception
     */
    protected function getAzureGroups(): Generator
    {
        $getUsergroupsDelta = 'true' === $this->providerParams['script_usergroups_delta'];

        if ($getUsergroupsDelta) {
            $usergroupsDeltaLink = $this->syncStateRepo->findOneBy(['title' => AzureSyncState::USERGROUPS_DATALINK]);

            $query = $usergroupsDeltaLink
                ? $usergroupsDeltaLink->getValue()
                : \sprintf('$select=%s', implode(',', AzureAuthenticatorHelper::QUERY_GROUP_FIELDS));
        } else {
            $query = \sprintf(
                '$top=%d&$select=%s',
                AzureSyncState::API_PAGE_SIZE,
                implode(',', AzureAuthenticatorHelper::QUERY_GROUP_FIELDS)
            );
        }

        $token = null;

        do {
            try {
                $this->generateOrRefreshToken($token);

                $azureGroupsRequest = $this->provider->request(
                    'get',
                    $getUsergroupsDelta ? "/v1.0/groups/delta?$query" : "/v1.0/groups?$query",
                    $token
                );
            } catch (Exception|GuzzleException $e) {
                throw new Exception('Exception when requesting groups from Azure: '.$e->getMessage());
            }

            $azureGroupsInfo = $azureGroupsRequest['value'] ?? [];

            foreach ($azureGroupsInfo as $azureGroupInfo) {
                if (!empty($this->providerParams['group_filter_regex'])
                    && !preg_match("/{$this->providerParams['group_filter_regex']}/", $azureGroupInfo['displayName'])
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
                $this->syncStateRepo->save(
                    AzureSyncState::USERGROUPS_DATALINK,
                    parse_url($azureGroupsRequest['@odata.deltaLink'], PHP_URL_QUERY),
                );
            }
        } while ($hasNextLink);
    }
}
