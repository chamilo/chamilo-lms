<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\DataFixtures;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\ToolChain;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AccessUrlAdminFixtures extends Fixture implements ContainerAwareInterface
{
    public const ADMIN_USER_REFERENCE = 'admin-user';
    public const ANON_USER_REFERENCE = 'anon';
    public const ACCESS_URL_REFERENCE = 'access-url';

    private ContainerInterface $container;

    public function setContainer(ContainerInterface $container = null): void
    {
        $this->container = $container;
    }

    public function load(ObjectManager $manager): void
    {
        $timezone = 'Europe\Paris';

        $container = $this->container;

        $toolChain = $container->get(ToolChain::class);
        $toolChain->createTools();

        $admin = (new User())
            ->setSkipResourceNode(true)
            ->setLastname('Doe')
            ->setFirstname('Joe')
            ->setUsername('admin')
            ->setStatus(1)
            ->setPlainPassword('admin')
            ->setEmail('admin@example.org')
            ->setOfficialCode('ADMIN')
            ->setCreatorId(1)
            ->setAuthSource(PLATFORM_AUTH_SOURCE)
            ->setTimezone($timezone)
            ->addUserAsAdmin()
            ->addRole('ROLE_GLOBAL_ADMIN')
        ;

        $manager->persist($admin);

        /** @var UserRepository $userRepo */
        $userRepo = $container->get(UserRepository::class);
        $userRepo->updateUser($admin);

        $anon = (new User())
            ->setSkipResourceNode(true)
            ->setLastname('Joe')
            ->setFirstname('Anonymous')
            ->setUsername('anon')
            ->setStatus(ANONYMOUS)
            ->setPlainPassword('anon')
            ->setEmail('anonymous@localhost')
            ->setOfficialCode('anonymous')
            ->setCreatorId(1)
            ->setAuthSource(PLATFORM_AUTH_SOURCE)
            ->setTimezone($timezone)
        ;
        $manager->persist($anon);
        $manager->flush();

        $userRepo->addUserToResourceNode($admin->getId(), $admin->getId());
        $userRepo->addUserToResourceNode($anon->getId(), $admin->getId());

        $manager->flush();

        $this->addReference(self::ADMIN_USER_REFERENCE, $admin);
        $this->addReference(self::ANON_USER_REFERENCE, $anon);
    }
}
