<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\PortfolioAttachment;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\PortfolioCommentRepository;
use Chamilo\CoreBundle\Repository\Node\PortfolioRepository;
use Chamilo\CoreBundle\Repository\PortfolioAttachmentRepository;
use Doctrine\DBAL\Schema\Schema;

class Version20250927180004 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate portfolio attachments';
    }

    /**
     * @inheritDoc
     */
    public function up(Schema $schema): void
    {
        /** @var PortfolioAttachmentRepository $attachmentRepo */
        $attachmentRepo = $this->container->get(PortfolioAttachmentRepository::class);
        /** @var PortfolioRepository $itemRepo */
        $itemRepo = $this->container->get(PortfolioRepository::class);
        /** @var PortfolioCommentRepository $itemRepo */
        $commentRepo = $this->container->get(PortfolioCommentRepository::class);

        $attachmentRows = $this->connection
            ->executeQuery("SELECT * FROM portfolio_attachment")
            ->fetchAllAssociative();

        foreach ($attachmentRows as $attachmentRow) {
            $userId = 0;
            $resource = null;
            $resourceRepo = null;

            if (PortfolioAttachment::TYPE_ITEM === (int) $attachmentRow['origin_type']) {
                $resourceRepo = $itemRepo;
                $resource = $itemRepo->find($attachmentRow['origin_id']);

                $itemRow = $this->connection
                    ->executeQuery("SELECT user_id FROM portfolio WHERE id = {$attachmentRow['origin_id']}")
                    ->fetchAssociative();

                $userId = $itemRow['user_id'] ?? 0;
            } elseif (PortfolioAttachment::TYPE_COMMENT === (int) $attachmentRow['origin_type']) {
                $resourceRepo = $commentRepo;
                $resource = $commentRepo->find($attachmentRow['origin_id']);

                $commentRow = $this->connection
                    ->executeQuery("SELECT author_id FROM portfolio_comment WHERE id = {$attachmentRow['origin_id']}")
                    ->fetchAssociative();

                $userId = $commentRow['author_id'] ?? 0;
            }

            if (!$resource) {
                continue;
            }

            $folderId = ((string) $userId)[0];
            $path = $attachmentRow['path'];

            $filePath = $this->getUpdateRootPath()."/app/upload/users/$folderId/$userId/portfolio_attachments/$path";

            $this->write('MIGRATIONS :: $filePath -- '.$filePath.' ...');

            if (!file_exists($filePath)) {
                continue;
            }

            $this->addLegacyFileToResource(
                $filePath,
                $resourceRepo,
                $resource,
                $attachmentRow['origin_id'],
                $attachmentRow['filename'],
                $attachmentRow['comment']
            );
        }

        $this->entityManager->flush();
    }
}
