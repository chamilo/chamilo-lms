<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\TicketMessageAttachment;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\TicketMessageAttachmentRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
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
     * {@inheritDoc}
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

        $sql = 'SELECT * FROM ticket_message_attachments ORDER BY id';

        $result = $connection->executeQuery($sql);
        $items = $result->fetchAllAssociative();

        foreach ($items as $item) {
            /** @var TicketMessageAttachment $messageAttachment */
            $messageAttachment = $attachmentRepo->find($item['id']);

            if ($messageAttachment->hasResourceNode()) {
                continue;
            }

            $ticket = $messageAttachment->getTicket();
            $user = $userRepo->find($item['sys_insert_user_id']);

            $attachmentRepo->addResourceNode($messageAttachment, $user, $user);

            if (null !== $ticket->getAssignedLastUser()) {
                $messageAttachment->addUserLink($ticket->getAssignedLastUser());
            }

            $attachmentRepo->create($messageAttachment);

            $filePath = $rootPath.'/app/upload/ticket_attachment/'.$item['path'];
            $this->addLegacyFileToResource($filePath, $attachmentRepo, $messageAttachment, $item['id']);

            $em->persist($messageAttachment);
            $em->flush();
        }
    }
}
