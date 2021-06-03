<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\DataFixtures;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class AccessUrlFixtures extends Fixture implements ContainerAwareInterface
{
    private ContainerInterface $container;

    public function setContainer(ContainerInterface $container = null): void
    {
        $this->container = $container;
    }

    public function load(ObjectManager $manager): void
    {
        $container = $this->container;
        /** @var User $admin */
        $admin = $this->getReference(AccessUrlAdminFixtures::ADMIN_USER_REFERENCE);
        $anon = $this->getReference(AccessUrlAdminFixtures::ANON_USER_REFERENCE);

        // Login as admin
        $token = new UsernamePasswordToken(
            $admin,
            $admin->getPassword(),
            'public',
            $admin->getRoles()
        );
        $container->get('security.token_storage')->setToken($token);
        // retrieve the test user

        // simulate $testUser being logged in
        /*$client = static::createClient();
        $client->loginUser($admin);*/

        $accessUrl = (new AccessUrl())
            ->setUrl('http://localhost/')
            ->setDescription('')
            ->setActive(1)
            ->setCreatedBy(1)
        ;

        $manager->persist($accessUrl);
        $manager->flush();

        $accessUrl->addUser($admin);
        $accessUrl->addUser($anon);
        $manager->flush();

        $this->addReference(AccessUrlAdminFixtures::ACCESS_URL_REFERENCE, $accessUrl);

        $settingsManager = $container->get('chamilo.settings.manager');
        $settingsManager->installSchemas($accessUrl);

        $manager->flush();
    }
}
