<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\TicketMessageAttachment;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\TicketMessageAttachmentRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Doctrine\DBAL\Schema\Schema;

class Version20211005154000 extends AbstractMigrationChamilo
{
    private const int ORM_FLUSH_BATCH_SIZE = 100;

    public function getDescription(): string
    {
        return 'Migrate ticket attachment files';
    }

    /**
     * Ticket attachments are committed in explicit ORM batches.
     * This makes the migration resumable and avoids losing hours of work if
     * the process is interrupted.
     */
    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $attachmentRepo = $this->container->get(TicketMessageAttachmentRepository::class);
        $userRepo = $this->container->get(UserRepository::class);

        $items = $this->connection->fetchAllAssociative(
            'SELECT id, sys_insert_user_id, path
             FROM ticket_message_attachments
             WHERE resource_node_id IS NULL
             ORDER BY id'
        );

        $processed = 0;

        foreach ($items as $item) {
            $id = (int) $item['id'];

            /** @var TicketMessageAttachment|null $messageAttachment */
            $messageAttachment = $attachmentRepo->find($id);

            if (!$messageAttachment instanceof TicketMessageAttachment || $messageAttachment->hasResourceNode()) {
                continue;
            }

            $ticket = $messageAttachment->getTicket();
            $user = $userRepo->find((int) $item['sys_insert_user_id']);

            if (null === $user) {
                continue;
            }

            $attachmentRepo->addResourceNode($messageAttachment, $user, $user);

            if (null !== $ticket->getAssignedLastUser()) {
                $messageAttachment->addUserLink($ticket->getAssignedLastUser());
            }

            $attachmentRepo->create($messageAttachment);

            $filePath = $this->getUpdateRootPath().'/app/upload/ticket_attachment/'.$item['path'];
            error_log('MIGRATIONS :: $filePath -- '.$filePath.' ...');
            $this->addLegacyFileToResource($filePath, $attachmentRepo, $messageAttachment, $id);

            $this->entityManager->persist($messageAttachment);
            ++$processed;

            if (0 === $processed % self::ORM_FLUSH_BATCH_SIZE) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
        }

        $this->entityManager->flush();
        $this->entityManager->clear();
    }
}
