<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\Uid\Uuid;

final class Version20201212114911 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate access_url, users';
    }

    public function up(Schema $schema): void
    {
        $urlRepo = $this->container->get(AccessUrlRepository::class);
        $userRepo = $this->container->get(UserRepository::class);

        $userList = [];
        // Adding first admin as main creator also adding to the resource node tree.
        $admin = $this->getAdmin();
        $admin->addRole('ROLE_ADMIN');

        $adminId = $admin->getId();
        $userList[$adminId] = $admin;

        $this->write('Adding admin user');
        if (false === $admin->hasResourceNode()) {
            $resourceNode = $userRepo->addUserToResourceNode($adminId, $adminId);
            $this->entityManager->persist($resourceNode);
        }

        // Adding portals (AccessUrl) to the resource node tree.
        $urls = $urlRepo->findAll();

        /** @var AccessUrl $url */
        foreach ($urls as $url) {
            if (false === $url->hasResourceNode()) {
                $urlRepo->createNodeForResourceWithNoParent($url, $admin);
                $this->entityManager->persist($url);
            }
        }
        $this->entityManager->flush();

        $sql = 'SELECT DISTINCT(user_id) FROM admin';
        $result = $this->entityManager->getConnection()->executeQuery($sql);
        $results = $result->fetchAllAssociative();
        $adminList = [];
        if (!empty($results)) {
            $adminList = array_map('intval', array_column($results, 'user_id'));
        }

        // Adding users to the resource node tree.
        $batchSize = self::BATCH_SIZE;
        $counter = 1;
        $q = $this->entityManager->createQuery('SELECT u FROM Chamilo\CoreBundle\Entity\User u');

        $this->write('Migrating users');

        /** @var User $userEntity */
        foreach ($q->toIterable() as $userEntity) {
            if ($userEntity->hasResourceNode()) {
                continue;
            }

            $userId = $userEntity->getId();
            $this->write("Migrating user: #$userId");

            $userEntity
                ->setUuid(Uuid::v4())
                ->setRoles([])
                ->setRoleFromStatus($userEntity->getStatus())
            ;

            if (\in_array($userId, $adminList, true)) {
                $userEntity->addRole('ROLE_ADMIN');
            }

            if ($userEntity::ANONYMOUS === $userEntity->getStatus()) {
                $userEntity->addRole('ROLE_ANONYMOUS');
            }

            $creatorId = $userEntity->getCreatorId();
            $creator = null;
            if (isset($userList[$adminId])) {
                $creator = $userList[$adminId];
            } else {
                $creator = $userRepo->find($creatorId);
                $userList[$adminId] = $creator;
            }
            if (null === $creator) {
                $creator = $admin;
            }

            $resourceNode = $userRepo->addUserToResourceNode($userId, $creator->getId());
            $this->entityManager->persist($resourceNode);

            if (($counter % $batchSize) === 0) {
                $this->entityManager->flush();
                $this->entityManager->clear(); // Detaches all objects from Doctrine!
            }
            $counter++;
        }
        $this->entityManager->flush();
        $this->entityManager->clear();

        $table = $schema->getTable('user');
        if (false === $table->hasIndex('UNIQ_8D93D649D17F50A6')) {
            $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649D17F50A6 ON user (uuid);');
        }
    }
}
