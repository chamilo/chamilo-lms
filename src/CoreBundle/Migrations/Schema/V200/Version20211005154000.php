<?php

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\TicketMessageAttachment;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Repository\Node\TicketMessageAttachmentRepository;
use Chamilo\Kernel;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;

class Version20211005154000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate ticket attachment files';
    }

    /**
     * @inheritDoc
     */
    public function up(Schema $schema): void
    {
        $container = $this->getContainer();
        $doctrine = $container->get('doctrine');
        $em = $doctrine->getManager();
        /** @var Connection $connection */
        $connection = $em->getConnection();

        /** @var Kernel $kernel */
        $kernel = $container->get('kernel');
        $rootPath = $kernel->getProjectDir();

        $attachmentRepo = $container->get(TicketMessageAttachmentRepository::class);
        $userRepo = $container->get(UserRepository::class);

        $sql = "SELECT * FROM ticket_message_attachments ORDER BY id";

        $result = $connection->executeQuery($sql);
        $items = $result->fetchAllAssociative();

        $counter = 1;

        foreach ($items as $item) {
            /** @var TicketMessageAttachment $resource */
            $resource = $attachmentRepo->find($item['id']);

            if ($resource->hasResourceNode()) {
                continue;
            }

            $user = $userRepo->find($item['sys_insert_user_id']);
//
//            $resource
//                ->addUserLink($user)
//                ->setParent($user);

            $attachmentRepo->addResourceNode($resource, $user, $user);

            $attachmentRepo->create($resource);

            $filePath = $rootPath.'/app/upload/ticket_attachment/'.$item['path'];
            $this->addLegacyFileToResource($filePath, $attachmentRepo, $resource, $item['id']);

            $em->persist($resource);

            if (($counter % self::BATCH_SIZE) === 0) {
                $em->flush();
                $em->clear();
            }

            $counter++;
        }
    }
}