<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Doctrine\DBAL\Schema\Schema;

use const PASSWORD_DEFAULT;

class Version20230720142900 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Adds a fallback user to the user table.';
    }

    public function up(Schema $schema): void
    {
        $repo = $this->container->get(UserRepository::class);

        // Check if fallback user already exists
        $existingFallbackUser = $repo->findOneBy(['username' => 'fallback_user']);

        if ($existingFallbackUser) {
            // User already exists, no need to create it again
            error_log('Fallback user already exists: ' . $existingFallbackUser->getFullname());
            return;
        }

        // Create fallback user if not exists
        $plainPassword = 'fallback_user';
        $encodedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);

        $fallbackUser = new User();
        $fallbackUser
            ->setUsername('fallback_user')
            ->setEmail('fallback@example.com')
            ->setPassword($encodedPassword)
            ->setCreatorId(1)
            ->setStatus(User::ROLE_FALLBACK)
            ->setLastname('Fallback')
            ->setFirstname('User')
            ->setOfficialCode('FALLBACK')
            ->setAuthSource('platform')
            ->setPhone('0000000000')
            ->setLocale('en')
            ->setActive(User::SOFT_DELETED)
            ->setTimezone('UTC')
        ;

        $this->entityManager->persist($fallbackUser);
        $this->entityManager->flush();

        error_log('Fallback user created: ' . $fallbackUser->getFullname());

        $repo->updateUser($fallbackUser);
    }
}
