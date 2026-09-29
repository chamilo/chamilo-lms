<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20260928132000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add categories to course glossary terms.';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('c_glossary_category')) {
            $this->addSql(
                'CREATE TABLE c_glossary_category (
                    iid INT AUTO_INCREMENT NOT NULL,
                    resource_node_id INT DEFAULT NULL,
                    title LONGTEXT NOT NULL,
                    UNIQUE INDEX UNIQ_GLOSSARY_CATEGORY_RESOURCE_NODE (resource_node_id),
                    PRIMARY KEY(iid)
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC'
            );
            $this->addSql(
                'ALTER TABLE c_glossary_category ADD CONSTRAINT FK_GLOSSARY_CATEGORY_RESOURCE_NODE FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE'
            );
        }

        if ($schema->hasTable('c_glossary') && !$schema->getTable('c_glossary')->hasColumn('category_id')) {
            $this->addSql('ALTER TABLE c_glossary ADD category_id INT DEFAULT NULL');
            $this->addSql('CREATE INDEX IDX_GLOSSARY_CATEGORY ON c_glossary (category_id)');
            $this->addSql(
                'ALTER TABLE c_glossary ADD CONSTRAINT FK_GLOSSARY_CATEGORY FOREIGN KEY (category_id) REFERENCES c_glossary_category (iid) ON DELETE SET NULL'
            );
        }

        $this->addSql(
            "INSERT INTO resource_type (title, tool_id, created_at, updated_at)
             SELECT 'glossary_categories', t.id, NOW(), NOW()
             FROM tool t
             WHERE t.title = 'glossary'
               AND NOT EXISTS (
                   SELECT 1 FROM resource_type WHERE title = 'glossary_categories'
               )"
        );

        $this->addSql(
            "UPDATE resource_type rt
             JOIN tool t ON t.title = 'glossary'
             SET rt.tool_id = t.id
             WHERE rt.title = 'glossary_categories'
               AND (rt.tool_id IS NULL OR rt.tool_id <> t.id)"
        );
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('c_glossary') && $schema->getTable('c_glossary')->hasColumn('category_id')) {
            $this->addSql('ALTER TABLE c_glossary DROP FOREIGN KEY FK_GLOSSARY_CATEGORY');
            $this->addSql('DROP INDEX IDX_GLOSSARY_CATEGORY ON c_glossary');
            $this->addSql('ALTER TABLE c_glossary DROP category_id');
        }

        if ($schema->hasTable('c_glossary_category')) {
            $this->addSql('DROP TABLE c_glossary_category');
        }

        $this->addSql(
            "DELETE rt
             FROM resource_type rt
             LEFT JOIN resource_node rn ON rn.resource_type_id = rt.id
             WHERE rt.title = 'glossary_categories'
               AND rn.id IS NULL"
        );
    }
}
