<?php

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Cocur\Slugify\Slugify;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;

final class Version20201212114910 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Create tools. Migrate portals and users';
    }

    public function up(Schema $schema): void
    {
        $container = $this->getContainer();
        $doctrine = $container->get('doctrine');
        /** @var Slugify $slugify */
        $slugify = new Slugify();
        $em = $doctrine->getManager();
        /** @var Connection $connection */
        $connection = $em->getConnection();

        $urlRepo = $container->get(AccessUrlRepository::class);
        $userRepo = $container->get(UserRepository::class);

        $userList = [];
        // Adding first admin as main creator also adding to the resource node tree.
        $admin = $this->getAdmin();

        $this->abortIf(null === $admin, 'Admin not found in the system');

        $adminId = $admin->getId();
        $userList[$adminId] = $admin;
        if (false === $admin->hasResourceNode()) {
            $resourceNode = $userRepo->addUserToResourceNode($adminId, $adminId);
            $em->persist($resourceNode);
        }

        // Adding portals (AccessUrl) to the resource node tree.
        $urls = $urlRepo->findAll();
        /** @var AccessUrl $url */
        foreach ($urls as $url) {
            if (false === $url->hasResourceNode()) {
                /*$cleanUrl = $slugify->slugify($url->getUrl());
                $cleanUrl = str_replace(['http-', 'https-'], '', $cleanUrl);*/
                $resourceNode = $urlRepo->addResourceNode($url, $admin);
                //$resourceNode->setTitle($cleanUrl);
                $em->persist($url);
            }
        }
        $em->flush();

        // Adding users to the resource node tree.
        $sql = 'SELECT * FROM user';
        $result = $connection->executeQuery($sql);
        $users = $result->fetchAllAssociative();
        $batchSize = self::BATCH_SIZE;
        $counter = 1;
        foreach ($users as $user) {
            /** @var User $userEntity */
            $userEntity = $userRepo->find($user['id']);
            if ($userEntity->hasResourceNode()) {
                continue;
            }
            $creatorId = $user['creator_id'];
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

            $resourceNode = $userRepo->addUserToResourceNode($adminId, $creator->getId());
            $em->persist($resourceNode);
            if (0 === $counter % $batchSize) {
                $em->flush();
                $em->clear(); // Detaches all objects from Doctrine!
            }
            $counter++;
        }
        $em->flush();
        $em->clear();
    }
}
