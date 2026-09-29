<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\State;

use ApiPlatform\Metadata\Get;
use Chamilo\CoreBundle\ApiResource\CurrentUserProfile;
use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\CoreBundle\State\CurrentUserProfileStateProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionProperty;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class CurrentUserProfileStateProviderTest extends TestCase
{
    public function testReturnsAuthenticatedUserProfile(): void
    {
        $user = $this->createUser();
        $accessUrl = $this->createMock(AccessUrl::class);
        $accessUrl->method('getId')->willReturn(1);

        $provider = $this->createProvider($user, $accessUrl, true);

        $profile = $provider->provide(
            new Get(name: 'current_user_profile')
        );

        self::assertInstanceOf(CurrentUserProfile::class, $profile);
        self::assertSame(42, $profile->id);
        self::assertSame('beeznest', $profile->username);
        self::assertSame('Bee', $profile->firstname);
        self::assertSame('Znest', $profile->lastname);
        self::assertSame('Bee Znest', $profile->fullName);
        self::assertSame('beeznest@example.com', $profile->email);
        self::assertSame('en_US', $profile->locale);
        self::assertSame('America/Lima', $profile->timezone);
        self::assertSame(['ROLE_STUDENT', 'ROLE_USER'], $profile->roles);
        self::assertSame('current-user', $profile->getResourceIdentifier());
    }

    public function testRejectsAnonymousAccess(): void
    {
        $provider = $this->createProvider(null, null, false);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Authentication is required.');

        $provider->provide(
            new Get(name: 'current_user_profile')
        );
    }

    public function testRejectsUserInactiveOnCurrentAccessUrl(): void
    {
        $user = $this->createUser();
        $accessUrl = $this->createMock(AccessUrl::class);
        $accessUrl->method('getId')->willReturn(1);

        $provider = $this->createProvider($user, $accessUrl, false);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('The authenticated user is not active on this access URL.');

        $provider->provide(
            new Get(name: 'current_user_profile')
        );
    }

    private function createProvider(
        ?User $user,
        ?AccessUrl $accessUrl,
        bool $isActiveOnAccessUrl,
    ): CurrentUserProfileStateProvider {
        $tokenStorage = new TokenStorage();
        if ($user instanceof User) {
            $tokenStorage->setToken(
                new UsernamePasswordToken($user, 'api', $user->getRoles())
            );
        }

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->method('get')
            ->with('security.token_storage')
            ->willReturn($tokenStorage)
        ;

        $userHelper = new UserHelper(new Security($container));

        $accessUrlRepository = $this->createMock(AccessUrlRepository::class);
        if ($accessUrl instanceof AccessUrl) {
            $accessUrlRepository->method('getFirstId')->willReturn(1);
            $accessUrlRepository->method('find')->with(1)->willReturn($accessUrl);
            $accessUrlRepository
                ->method('isUrlActiveForUser')
                ->with($accessUrl, $user)
                ->willReturn($isActiveOnAccessUrl)
            ;
        }

        $accessUrlHelper = new AccessUrlHelper(
            $accessUrlRepository,
            new RequestStack()
        );

        return new CurrentUserProfileStateProvider(
            $userHelper,
            $accessUrlHelper,
            $accessUrlRepository,
        );
    }

    private function createUser(): User
    {
        $user = (new User())
            ->setUsername('beeznest')
            ->setFirstname('Bee')
            ->setLastname('Znest')
            ->setEmail('beeznest@example.com')
            ->setLocale('en_US')
            ->setTimezone('America/Lima')
            ->setRoles(['ROLE_STUDENT'])
        ;

        $id = new ReflectionProperty(User::class, 'id');
        $id->setValue($user, 42);

        return $user;
    }
}
