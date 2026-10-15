<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Security\Authenticator;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserAuthSource;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\AuthenticationConfigHelper;
use Chamilo\CoreBundle\Helpers\AzureAuthenticatorHelper;
use Chamilo\CoreBundle\Helpers\LanguageHelper;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\ExtraFieldRepository;
use Chamilo\CoreBundle\Repository\ExtraFieldValuesRepository;
use Chamilo\CoreBundle\Repository\LanguageRepository;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Security\Authenticator\OAuth2\FacebookAuthenticator;
use Chamilo\CoreBundle\Security\Authenticator\OAuth2\KeycloakAuthenticator;
use Cocur\Slugify\SlugifyInterface;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface;
use League\OAuth2\Client\Provider\FacebookUser;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Stevenmaguire\OAuth2\Client\Provider\KeycloakResourceOwner;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

/**
 * An SSO login must never leave a predictable password behind. These users
 * can keep the "platform" auth source, which makes the password login form
 * accept whatever the SSO code stored. Two rules are locked here:
 * a new user gets an unpredictable password, and an existing user keeps
 * the password they already have.
 */
final class SsoPasswordTest extends TestCase
{
    private const string EXISTING_HASH = 'existing-password-hash';

    public function testKeycloakGivesNewUserAnUnpredictablePassword(): void
    {
        $user = $this->loadKeycloakUser(null);

        $this->assertUnpredictable($user, 'keycloak');
    }

    public function testKeycloakKeepsThePasswordOfAnExistingUser(): void
    {
        $user = $this->loadKeycloakUser($this->createExistingUser());

        $this->assertExistingPasswordIsUntouched($user);
    }

    public function testFacebookGivesNewUserAnUnpredictablePassword(): void
    {
        $user = $this->loadFacebookUser(null);

        $this->assertUnpredictable($user, 'facebook');
    }

    public function testFacebookKeepsThePasswordOfAnExistingUser(): void
    {
        $user = $this->loadFacebookUser($this->createExistingUser());

        $this->assertExistingPasswordIsUntouched($user);
    }

    public function testAzureGivesNewUserAnUnpredictablePassword(): void
    {
        $user = $this->registerAzureUser(null);

        $this->assertUnpredictable($user, 'azure');
    }

    public function testAzureKeepsThePasswordOfAnExistingUser(): void
    {
        $user = $this->registerAzureUser($this->createExistingUser());

        $this->assertExistingPasswordIsUntouched($user);
    }

    private function assertUnpredictable(User $user, string $legacyPassword): void
    {
        $password = (string) $user->getPlainPassword();

        $this->assertNotSame('', $password);
        $this->assertNotSame($legacyPassword, $password, 'The legacy fixed password must not be assigned.');
    }

    private function assertExistingPasswordIsUntouched(User $user): void
    {
        $this->assertNull($user->getPlainPassword(), 'An SSO login must not overwrite an existing password.');
        $this->assertSame(self::EXISTING_HASH, $user->getPassword());
    }

    private function createExistingUser(): User
    {
        return (new User())
            ->setUsername('existing.user')
            ->setEmail('existing.user@example.com')
            ->setPassword(self::EXISTING_HASH)
            ->addAuthSourceByAuthentication(UserAuthSource::PLATFORM, $this->createAccessUrl())
        ;
    }

    private function createRootUser(): User
    {
        $root = new User();
        (new ReflectionProperty(User::class, 'id'))->setValue($root, 1);

        return $root;
    }

    private function createAccessUrl(): AccessUrl
    {
        return new AccessUrl();
    }

    private function createAccessUrlHelper(): AccessUrlHelper
    {
        $repository = $this->createMock(AccessUrlRepository::class);
        $repository->method('getFirstId')->willReturn(1);
        $repository->method('find')->willReturn($this->createAccessUrl());

        return new AccessUrlHelper($repository, new RequestStack());
    }

    private function createConfigHelper(): AuthenticationConfigHelper
    {
        return new AuthenticationConfigHelper(new ParameterBag([]), $this->createAccessUrlHelper());
    }

    private function createUserRepository(?User $existingUser): UserRepository
    {
        $repository = $this->createMock(UserRepository::class);
        $repository->method('findOneBy')->willReturn($existingUser);
        $repository->method('getRootUser')->willReturn($this->createRootUser());

        return $repository;
    }

    private function createClientRegistry(object $resourceOwner): ClientRegistry
    {
        $client = $this->createMock(OAuth2ClientInterface::class);
        $client->method('fetchUserFromToken')->willReturn($resourceOwner);

        $registry = $this->createMock(ClientRegistry::class);
        $registry->method('getClient')->willReturn($client);

        return $registry;
    }

    private function loadKeycloakUser(?User $existingUser): User
    {
        $owner = $this->createMock(KeycloakResourceOwner::class);
        $owner->method('getUsername')->willReturn('existing.user');
        $owner->method('getId')->willReturn('keycloak-id');
        $owner->method('getFirstName')->willReturn('First');
        $owner->method('getLastName')->willReturn('Last');
        $owner->method('getEmail')->willReturn('existing.user@example.com');

        $authenticator = new KeycloakAuthenticator(
            $this->createClientRegistry($owner),
            $this->createMock(RouterInterface::class),
            $this->createUserRepository($existingUser),
            $this->createConfigHelper(),
            $this->createAccessUrlHelper(),
            $this->createMock(EntityManagerInterface::class),
            new NullLogger(),
        );

        return $this->callUserLoader($authenticator);
    }

    private function loadFacebookUser(?User $existingUser): User
    {
        $owner = $this->createMock(FacebookUser::class);
        $owner->method('getFirstName')->willReturn('First');
        $owner->method('getLastName')->willReturn('Last');
        $owner->method('getEmail')->willReturn('existing.user@example.com');

        $slugify = $this->createMock(SlugifyInterface::class);
        $slugify->method('slugify')->willReturn('existing.user');

        $authenticator = new FacebookAuthenticator(
            $this->createClientRegistry($owner),
            $this->createMock(RouterInterface::class),
            $this->createUserRepository($existingUser),
            $this->createConfigHelper(),
            $this->createAccessUrlHelper(),
            $this->createMock(EntityManagerInterface::class),
            new NullLogger(),
            $slugify,
        );

        return $this->callUserLoader($authenticator);
    }

    private function callUserLoader(object $authenticator): User
    {
        $method = new ReflectionMethod($authenticator, 'userLoader');

        return $method->invoke($authenticator, new AccessToken(['access_token' => 'token']));
    }

    private function registerAzureUser(?User $existingUser): User
    {
        $extraFieldRepository = $this->createMock(ExtraFieldRepository::class);
        $extraFieldRepository->method('findByVariable')->willReturn(new ExtraField());

        $extraFieldValuesRepository = $this->createMock(ExtraFieldValuesRepository::class);
        $extraFieldValuesRepository->method('findByVariableAndValue')->willReturn([]);

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->method('findByEmailCaseInsensitive')->willReturn($existingUser);
        $userRepository->method('findByUsernameCaseInsensitive')->willReturn(null);
        $userRepository->method('getRootUser')->willReturn($this->createRootUser());

        $configHelper = new AuthenticationConfigHelper(
            new ParameterBag([
                'authentication' => [
                    'default' => [
                        'oauth2' => [
                            'azure' => [
                                'enabled' => true,
                                'provisioning' => true,
                                'update_users' => true,
                                'existing_user_verification_order' => '',
                            ],
                        ],
                    ],
                ],
            ]),
            $this->createAccessUrlHelper(),
        );

        $helper = new AzureAuthenticatorHelper(
            $extraFieldValuesRepository,
            $extraFieldRepository,
            $userRepository,
            $this->createMock(EntityManagerInterface::class),
            $this->createAccessUrlHelper(),
            new LanguageHelper(
                $this->createMock(LanguageRepository::class),
                new RequestStack(),
                (new ReflectionClass(UserHelper::class))->newInstanceWithoutConstructor(),
            ),
            $configHelper,
        );

        return $helper->registerUser([
            'givenName' => 'First',
            'surname' => 'Last',
            'mail' => 'existing.user@example.com',
            'userPrincipalName' => 'existing.user@example.com',
            'mailNickname' => 'existing.user',
            'id' => 'azure-id',
            'accountEnabled' => true,
        ]);
    }
}
