<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\DataFixtures;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class AccessUserUrlFixtures extends Fixture
{
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly SettingsManager $settingsManager,
    ) {}

    public function load(ObjectManager $manager): void
    {
        /** @var User $admin */
        $admin = $this->getReference(AccessUserFixtures::ADMIN_USER_REFERENCE);
        $anon = $this->getReference(AccessUserFixtures::ANON_USER_REFERENCE);

        // Login as admin
        $token = new UsernamePasswordToken(
            $admin,
            'public',
            $admin->getRoles()
        );
        $this->tokenStorage->setToken($token);
        // retrieve the test user

        // simulate $testUser being logged in
        /*$client = static::createClient();
        $client->loginUser($admin);*/

        $accessUrl = (new AccessUrl())
            ->setUrl(AccessUrl::DEFAULT_ACCESS_URL)
            ->setDescription('')
            ->setActive(1)
            ->setCreatedBy(1)
        ;

        $manager->persist($accessUrl);
        $manager->flush();

        $accessUrl->addUser($admin);
        $accessUrl->addUser($anon);
        $manager->flush();

        $this->addReference(AccessUserFixtures::ACCESS_URL_REFERENCE, $accessUrl);

        $this->settingsManager->installSchemas($accessUrl);

        $manager->flush();
    }
}
