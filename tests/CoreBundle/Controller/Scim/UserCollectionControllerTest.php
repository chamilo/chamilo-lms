<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Controller\Scim;

use Chamilo\CoreBundle\Controller\Scim\UserCollectionController;
use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserAuthSource;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\AuthenticationConfigHelper;
use Chamilo\CoreBundle\Helpers\ScimHelper;
use Chamilo\CoreBundle\Repository\ExtraFieldRepository;
use Chamilo\CoreBundle\Repository\ExtraFieldValuesRepository;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\Serializer;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * A user provisioned through SCIM must never get a password an attacker can
 * predict: with the "platform" auth source the normal login form accepts it,
 * so a fixed value is an account takeover of every provisioned user.
 */
final class UserCollectionControllerTest extends TestCase
{
    public function testProvisionedUsersGetUnpredictablePasswords(): void
    {
        $first = $this->provisionUser('victim.one');
        $second = $this->provisionUser('victim.two');

        foreach ([$first, $second] as $password) {
            $this->assertNotSame('', $password);
            $this->assertNotSame('scim', $password, 'The legacy fixed SCIM password must not be assigned.');
        }

        $this->assertNotSame($first, $second, 'Each provisioned user needs its own password.');
    }

    /**
     * Provisions a user with the default "platform" auth source and returns
     * the plain password the controller handed to the repository.
     */
    private function provisionUser(string $username): string
    {
        $accessUrlRepository = $this->createMock(AccessUrlRepository::class);
        $accessUrlRepository->method('getFirstId')->willReturn(1);
        $accessUrlRepository->method('find')->willReturn(new AccessUrl());
        $accessUrlHelper = new AccessUrlHelper($accessUrlRepository, new RequestStack());

        $configHelper = new AuthenticationConfigHelper(
            new ParameterBag([
                'authentication' => [
                    'default' => ['scim' => ['enabled' => true, 'auth_source' => UserAuthSource::PLATFORM]],
                ],
            ]),
            $accessUrlHelper,
        );

        $controller = new UserCollectionController(
            $this->createMock(TranslatorInterface::class),
            new ScimHelper(
                $this->createMock(ExtraFieldRepository::class),
                $this->createMock(ExtraFieldValuesRepository::class),
                $this->createMock(UserRepository::class),
            ),
            $accessUrlHelper,
            $configHelper,
        );

        $user = (new User())->setUsername($username);

        $serializer = $this->createMock(Serializer::class);
        $serializer->method('denormalize')->willReturn($user);
        $serializer->method('normalize')->willReturn(['meta' => ['location' => '/scim/v2/Users/1']]);

        $storedPassword = null;
        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->method('findOneBy')->willReturn(null);
        $userRepository->method('findOnePlatformAdmin')->willReturn(new User());
        $userRepository
            ->method('updateUser')
            ->willReturnCallback(static function (User $saved) use (&$storedPassword): void {
                $storedPassword = $saved->getPlainPassword();
            })
        ;

        $controller->createUser(
            new Request([], [], [], [], [], [], json_encode(['userName' => $username])),
            $this->createMock(EntityManagerInterface::class),
            $userRepository,
            $serializer,
        );

        return (string) $storedPassword;
    }
}
