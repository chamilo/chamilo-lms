<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V210;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20260722010000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Rename name columns to title in justification documents and page layout templates';
    }

    public function up(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();

        if ($schemaManager->tablesExist(['justification_document'])) {
            $columns = $schemaManager->listTableColumns('justification_document');

            if (isset($columns['name']) && !isset($columns['title'])) {
                $this->addSql('ALTER TABLE justification_document CHANGE name title LONGTEXT DEFAULT NULL');
            }
        }

        if ($schemaManager->tablesExist(['page_layout_template'])) {
            $columns = $schemaManager->listTableColumns('page_layout_template');

            if (isset($columns['name']) && !isset($columns['title'])) {
                $this->addSql('ALTER TABLE page_layout_template CHANGE name title VARCHAR(255) DEFAULT NULL');
            }

            $this->addSql("UPDATE page_layout_template
                SET title = JSON_UNQUOTE(JSON_EXTRACT(layout, '$.page.title'))
                WHERE (title IS NULL OR title = '')
                  AND JSON_VALID(layout) = 1
                  AND JSON_UNQUOTE(JSON_EXTRACT(layout, '$.page.title')) IS NOT NULL");
        }
    }

    public function down(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();

        if ($schemaManager->tablesExist(['justification_document'])) {
            $columns = $schemaManager->listTableColumns('justification_document');

            if (isset($columns['title']) && !isset($columns['name'])) {
                $this->addSql('ALTER TABLE justification_document CHANGE title name LONGTEXT DEFAULT NULL');
            }
        }

        if ($schemaManager->tablesExist(['page_layout_template'])) {
            $columns = $schemaManager->listTableColumns('page_layout_template');

            if (isset($columns['title']) && !isset($columns['name'])) {
                $this->addSql('ALTER TABLE page_layout_template CHANGE title name VARCHAR(255) DEFAULT NULL');
            }
        }
    }
}
