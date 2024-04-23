<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20201211124910 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Insert default values to table resource_format';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('resource_format')) {
            $result = $this->connection->executeQuery(" SELECT * FROM resource_format WHERE title = 'html'");
            $exists = $result->fetchAllAssociative();
            if (empty($exists)) {
                $this->addSql("INSERT INTO resource_format SET title = 'html', created_at = NOW(), updated_at = NOW();");
            }
            $result = $this->connection->executeQuery(" SELECT * FROM resource_format WHERE title = 'txt'");
            $exists = $result->fetchAllAssociative();
            if (empty($exists)) {
                $this->addSql("INSERT INTO resource_format SET title = 'txt', created_at = NOW(), updated_at = NOW();");
            }
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('resource_format')) {
            $result = $this->connection->executeQuery(" SELECT * FROM resource_format WHERE title = 'txt'");
            $exists = $result->fetchAllAssociative();
            if (!empty($exists)) {
                $this->addSql("DELETE FROM resource_format WHERE title = 'txt';");
            }
            $result = $this->connection->executeQuery(" SELECT * FROM resource_format WHERE title = 'html'");
            $exists = $result->fetchAllAssociative();
            if (!empty($exists)) {
                $this->addSql("DELETE FROM resource_format WHERE title = 'html';");
            }
        }
    }
}
