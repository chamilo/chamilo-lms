<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Entity\AzureSyncState;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AuthenticationConfigHelper;
use Chamilo\CoreBundle\Helpers\AzureAuthenticatorHelper;
use Chamilo\CoreBundle\Repository\AzureSyncStateRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Generator;
use GuzzleHttp\Exception\GuzzleException;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\Provider\AzureClient;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessTokenInterface;
use Symfony\Component\Console\Command\Command;
use TheNetworg\OAuth2\Client\Provider\Azure;

use const PHP_URL_QUERY;

abstract class AzureSyncAbstractCommand extends Command
{
    protected AzureClient $client;

    protected Azure $provider;

    protected array $providerParams = [];

    public function __construct(
        protected readonly ClientRegistry $clientRegistry,
        readonly AuthenticationConfigHelper $configHelper,
        readonly protected AzureAuthenticatorHelper $azureHelper,
        readonly protected AzureSyncStateRepository $syncStateRepo,
        protected readonly EntityManagerInterface $entityManager,
        protected readonly UserRepository $userRepository,
    ) {
        parent::__construct();

        $this->client = $this->clientRegistry->getClient('azure');
        $this->provider = $this->client->getOAuth2Provider();
        $this->providerParams = $configHelper->getProviderConfig('azure');
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

        if ($this->providerParams['script_users_delta']) {
            $usersDeltaLink = $this->syncStateRepo->findOneBy(['title' => AzureSyncState::USERS_DATALINK]);

            $query = $usersDeltaLink
                ? $usersDeltaLink->getValue()
                : \sprintf('$select=%s', implode(',', $userFields));
        } else {
            $query = \sprintf(
                '$top=%d&$select=%s',
                AzureSyncState::API_PAGE_SIZE,
                implode(',', $userFields)
            );
        }

        $token = null;

        do {
            try {
                $this->generateOrRefreshToken($token);

                $azureUsersRequest = $this->provider->request(
                    'get',
                    $this->providerParams['script_users_delta'] ? "users/delta?$query" : "users?$query",
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
     * @return array<string, string|false>
     */
    public function getGroupUidByRole(): array
    {
        $groupUidList = [
            'admin' => $this->providerParams['group_id_admin'],
            'sessionAdmin' => $this->providerParams['group_id_session_admin'],
            'teacher' => $this->providerParams['group_id_teacher'],
        ];

        return array_filter($groupUidList);
    }

    /**
     * @return array<string, callable>
     */
    public function getUpdateActionByRole(): array
    {
        return [
            'admin' => function (User $user): void {
                $user
                    ->setStatus(COURSEMANAGER)
                    ->addUserAsAdmin()
                    ->setRoleFromStatus(COURSEMANAGER)
                ;
            },
            'sessionAdmin' => function (User $user): void {
                $user
                    ->setStatus(SESSIONADMIN)
                    ->removeUserAsAdmin()
                    ->setRoleFromStatus(SESSIONADMIN)
                ;
            },
            'teacher' => function (User $user): void {
                $user
                    ->setStatus(COURSEMANAGER)
                    ->removeUserAsAdmin()
                    ->setRoleFromStatus(COURSEMANAGER)
                ;
            },
        ];
    }

    /**
     * @throws Exception
     */
    protected function getAzureGroupMembers(string $groupUid): Generator
    {
        $userFields = [
            'mail',
            'mailNickname',
            'id',
        ];

        $query = \sprintf(
            '$top=%d&$select=%s',
            AzureSyncState::API_PAGE_SIZE,
            implode(',', $userFields)
        );

        $token = null;

        do {
            try {
                $this->generateOrRefreshToken($token);

                $azureGroupMembersRequest = $this->provider->get(
                    "groups/$groupUid/members?$query",
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
        } while ($hasNextLink = false);
    }
}
