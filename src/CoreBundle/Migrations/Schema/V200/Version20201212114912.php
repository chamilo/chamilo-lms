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

final class Version20201212114912 extends AbstractMigrationChamilo
{
    private const int USER_BATCH_SIZE = 200;

    public function getDescription(): string
    {
        return 'Migrate access_url, users';
    }

    public function up(Schema $schema): void
    {
        $urlRepo = $this->container->get(AccessUrlRepository::class);
        $userRepo = $this->container->get(UserRepository::class);

        // Adding first admin as main creator also adding to the resource node tree.
        $admin = $this->getAdmin();
        $admin->addRole('ROLE_ADMIN');
        $adminId = $admin->getId();

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
        $this->entityManager->clear();
        unset($urls, $admin);

        $adminList = array_fill_keys(
            array_map(
                'intval',
                $this->connection->fetchFirstColumn('SELECT DISTINCT user_id FROM admin')
            ),
            true
        );

        // Avoid one long-lived ORM iterator for all users. A keyset loop releases the
        // UnitOfWork after every small batch and can resume naturally because users that
        // already have a resource node are skipped.
        $lastUserId = 0;
        $migratedUsers = 0;
        $processedUsers = 0;

        $this->write('Migrating users');

        while (true) {
            $userIds = $this->connection->fetchFirstColumn(
                'SELECT id
                 FROM user
                 WHERE id > :lastUserId
                   AND resource_node_id IS NULL
                 ORDER BY id
                 LIMIT '.self::USER_BATCH_SIZE,
                ['lastUserId' => $lastUserId]
            );

            if ([] === $userIds) {
                break;
            }

            foreach ($userIds as $userIdValue) {
                $userId = (int) $userIdValue;
                $lastUserId = $userId;
                ++$processedUsers;

                /** @var User|null $userEntity */
                $userEntity = $userRepo->find($userId);
                if (null === $userEntity || $userEntity->hasResourceNode()) {
                    continue;
                }

                $userEntity
                    ->setUuid(Uuid::v4())
                    ->setRoles([])
                    ->setRoleFromStatus($userEntity->getStatus())
                ;

                if (isset($adminList[$userId])) {
                    $userEntity->addRole('ROLE_ADMIN');
                }

                if ($userEntity::ANONYMOUS === $userEntity->getStatus()) {
                    $userEntity->addRole('ROLE_ANONYMOUS');
                }

                // Historical behavior uses the platform admin as creator for migrated user nodes.
                $resourceNode = $userRepo->addUserToResourceNode($userId, $adminId);
                $this->entityManager->persist($resourceNode);
                ++$migratedUsers;
            }

            $this->entityManager->flush();
            $this->entityManager->clear();

            if (0 === $processedUsers % 2000) {
                $this->getLogger()->info('Legacy user resource migration progress.', [
                    'last_user_id' => $lastUserId,
                    'processed_users' => $processedUsers,
                    'migrated_users' => $migratedUsers,
                ]);
            }
        }

        $this->getLogger()->info('Migrated legacy user resources.', [
            'processed_users' => $processedUsers,
            'migrated_users' => $migratedUsers,
        ]);

        $table = $schema->getTable('user');
        if (false === $table->hasIndex('UNIQ_8D93D649D17F50A6')) {
            $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649D17F50A6 ON user (uuid);');
        }
    }
}
