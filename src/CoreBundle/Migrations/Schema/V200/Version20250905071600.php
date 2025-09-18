<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20250905071600 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Extend ai_requests with tool_item_id, ai_model, ai_endpoint and add composite index for lookup.';
    }

    public function up(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();
        $table = $schemaManager->introspectTable('ai_requests');

        $hasToolItemId = $table->hasColumn('tool_item_id');
        $hasAiModel = $table->hasColumn('ai_model');
        $hasAiEndpoint = $table->hasColumn('ai_endpoint');

        if (!$hasToolItemId) {
            $this->addSql('ALTER TABLE ai_requests ADD tool_item_id BIGINT NULL AFTER tool_name');
        }

        if (!$hasAiModel) {
            $this->addSql('ALTER TABLE ai_requests ADD ai_model VARCHAR(255) NULL');
        }

        if (!$hasAiEndpoint) {
            $this->addSql('ALTER TABLE ai_requests ADD ai_endpoint TEXT NULL');
        }

        // Add composite index for quick lookups when rendering
        $indexes = array_map(static fn ($i) => $i->getName(), $table->getIndexes());
        if (!\in_array('idx_ai_requests_lookup', $indexes, true)) {
            $this->addSql('CREATE INDEX idx_ai_requests_lookup ON ai_requests (tool_name, tool_item_id)');
        }
    }

    public function down(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();
        $table = $schemaManager->introspectTable('ai_requests');

        $indexes = array_map(static fn ($i) => $i->getName(), $table->getIndexes());
        if (\in_array('idx_ai_requests_lookup', $indexes, true)) {
            $this->addSql('DROP INDEX idx_ai_requests_lookup ON ai_requests');
        }

        if ($table->hasColumn('ai_endpoint')) {
            $this->addSql('ALTER TABLE ai_requests DROP COLUMN ai_endpoint');
        }
        if ($table->hasColumn('ai_model')) {
            $this->addSql('ALTER TABLE ai_requests DROP COLUMN ai_model');
        }
        if ($table->hasColumn('tool_item_id')) {
            $this->addSql('ALTER TABLE ai_requests DROP COLUMN tool_item_id');
        }
    }
}
