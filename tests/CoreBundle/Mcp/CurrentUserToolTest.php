<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Mcp;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Mcp\CurrentUserTool;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

final class CurrentUserToolTest extends KernelTestCase
{
    public function testReturnsTheAuthenticatedUsersLocaleAlongsideIdentityAndRoles(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);
        $tool = $container->get(CurrentUserTool::class);

        $user = $em->getRepository(User::class)->find(1);
        if (!$user instanceof User) {
            self::markTestSkipped('User #1 does not exist in this DB, skipping.');
        }

        $user->setLocale('fr_FR');

        $container->get(TokenStorageInterface::class)->setToken(
            new UsernamePasswordToken($user, 'api', $user->getRoles())
        );

        $result = $tool->getCurrentUser();

        self::assertSame($user->getId(), $result['user_id']);
        self::assertSame($user->getUsername(), $result['username']);
        self::assertSame('fr_FR', $result['locale']);
        self::assertIsArray($result['roles']);
    }
}
