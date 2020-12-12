<?php

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\AccessUrlRepository;
use Chamilo\CoreBundle\Repository\UserRepository;
use Chamilo\CoreBundle\ToolChain;
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
        $em = $doctrine->getManager();
        /** @var Connection $connection */
        $connection = $em->getConnection();

        // Install tools.
        $toolChain = $container->get(ToolChain::class);
        $toolChain->createTools($em);

        //$urlRepo = $container->get(AccessUrlRepository::class);
        $urlRepo = $em->getRepository(AccessUrl::class);
        $userRepo = $container->get(UserRepository::class);

        $userList = [];
        $admin = $this->getAdmin();
        $adminId = $admin->getId();
        $userList[$adminId] = $admin;

        if (false === $admin->hasResourceNode()) {
            $resourceNode = $userRepo->addUserToResourceNode($adminId, $adminId);
            $em->persist($resourceNode);
        }

        $urls = $urlRepo->findAll();
        /** @var AccessUrl $url */
        foreach ($urls as $url) {
            if (false === $url->hasResourceNode()) {
                $urlRepo->addResourceNode($url, $admin);
                $em->persist($url);
            }
        }

        $em->flush();

        $sql = 'SELECT * FROM user';
        $result = $connection->executeQuery($sql);
        $users = $result->fetchAllAssociative();

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
        }

        $em->flush();
    }
}
