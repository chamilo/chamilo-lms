<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Doctrine\DBAL\Schema\Schema;
use Exception;

final class Version20250403121200 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Set the first AccessUrl as default for all existing records in ticket_priority, ticket_project, ticket_status, and ticket_ticket if access_url_id is null.';
    }

    public function up(Schema $schema): void
    {
        $this->entityManager->beginTransaction();

        try {
            /** @var AccessUrlRepository $accessUrlRepo */
            $accessUrlRepo = $this->container->get(AccessUrlRepository::class);
            $firstAccessUrl = $accessUrlRepo->find($accessUrlRepo->getFirstId());

            if (!$firstAccessUrl) {
                throw new Exception('No AccessUrl found for migration');
            }

            $accessUrlId = $firstAccessUrl->getId();

            // Update ticket_priority
            $this->connection->executeStatement(
                'UPDATE ticket_priority SET access_url_id = :accessUrlId WHERE access_url_id IS NULL',
                ['accessUrlId' => $accessUrlId]
            );

            // Update ticket_project
            $this->connection->executeStatement(
                'UPDATE ticket_project SET access_url_id = :accessUrlId WHERE access_url_id IS NULL',
                ['accessUrlId' => $accessUrlId]
            );

            // Update ticket_status
            $this->connection->executeStatement(
                'UPDATE ticket_status SET access_url_id = :accessUrlId WHERE access_url_id IS NULL',
                ['accessUrlId' => $accessUrlId]
            );

            // Update ticket_ticket
            $this->connection->executeStatement(
                'UPDATE ticket_ticket SET access_url_id = :accessUrlId WHERE access_url_id IS NULL',
                ['accessUrlId' => $accessUrlId]
            );

            $this->entityManager->commit();
        } catch (Exception $e) {
            $this->entityManager->rollBack();
            error_log('[Migration] Failed to set default AccessUrl: '.$e->getMessage());
        }
    }

    public function down(Schema $schema): void
    {
        $this->connection->executeStatement('UPDATE ticket_priority SET access_url_id = NULL');
        $this->connection->executeStatement('UPDATE ticket_project SET access_url_id = NULL');
        $this->connection->executeStatement('UPDATE ticket_status SET access_url_id = NULL');
        $this->connection->executeStatement('UPDATE ticket_ticket SET access_url_id = NULL');
    }
}
