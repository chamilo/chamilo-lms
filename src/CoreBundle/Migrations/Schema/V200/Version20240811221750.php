<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Doctrine\DBAL\Schema\Schema;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;

final class Version20240811221750 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migration to add foreign key constraints to LTI-related tables.';
    }

    public function up(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();

        if ($schema->hasTable('lti_external_tool')) {
            $foreignKeys = $schemaManager->listTableForeignKeys('lti_external_tool');

            if (!$this->hasForeignKey($foreignKeys, 'FK_DB0E04E41BAD783F')) {
                $this->addSql('
                    ALTER TABLE lti_external_tool
                    ADD CONSTRAINT FK_DB0E04E41BAD783F FOREIGN KEY (resource_node_id)
                    REFERENCES resource_node (id) ON DELETE CASCADE;
                ');
            }

            if (!$this->hasForeignKey($foreignKeys, 'FK_DB0E04E491D79BD3')) {
                $this->addSql('
                    ALTER TABLE lti_external_tool
                    ADD CONSTRAINT FK_DB0E04E491D79BD3 FOREIGN KEY (c_id)
                    REFERENCES course (id);
                ');
            }

            if (!$this->hasForeignKey($foreignKeys, 'FK_DB0E04E482F80D8B')) {
                $this->addSql('
                    ALTER TABLE lti_external_tool
                    ADD CONSTRAINT FK_DB0E04E482F80D8B FOREIGN KEY (gradebook_eval_id)
                    REFERENCES gradebook_evaluation (id) ON DELETE SET NULL;
                ');
            }

            if (!$this->hasForeignKey($foreignKeys, 'FK_DB0E04E4727ACA70')) {
                $this->addSql('
                    ALTER TABLE lti_external_tool
                    ADD CONSTRAINT FK_DB0E04E4727ACA70 FOREIGN KEY (parent_id)
                    REFERENCES lti_external_tool (id);
                ');
            }
        }

        if ($schema->hasTable('lti_token')) {
            $foreignKeys = $schemaManager->listTableForeignKeys('lti_token');

            if (!$this->hasForeignKey($foreignKeys, 'FK_EA71C468F7B22CC')) {
                $this->addSql('
                    ALTER TABLE lti_token
                    ADD CONSTRAINT FK_EA71C468F7B22CC FOREIGN KEY (tool_id)
                    REFERENCES lti_external_tool (id) ON DELETE CASCADE;
                ');
            }
        }

        if ($schema->hasTable('lti_lineitem')) {
            $foreignKeys = $schemaManager->listTableForeignKeys('lti_lineitem');

            if (!$this->hasForeignKey($foreignKeys, 'FK_5C76B75D8F7B22CC')) {
                $this->addSql('
                    ALTER TABLE lti_lineitem
                    ADD CONSTRAINT FK_5C76B75D8F7B22CC FOREIGN KEY (tool_id)
                    REFERENCES lti_external_tool (id) ON DELETE CASCADE;
                ');
            }

            if (!$this->hasForeignKey($foreignKeys, 'FK_5C76B75D1323A575')) {
                $this->addSql('
                    ALTER TABLE lti_lineitem
                    ADD CONSTRAINT FK_5C76B75D1323A575 FOREIGN KEY (evaluation)
                    REFERENCES gradebook_evaluation (id) ON DELETE CASCADE;
                ');
            }
        }
    }

    public function down(Schema $schema): void {}

    private function hasForeignKey(array $foreignKeys, string $foreignKeyName): bool
    {
        foreach ($foreignKeys as $foreignKey) {
            if ($foreignKey->getName() === $foreignKeyName) {
                return true;
            }
        }
        return false;
    }
}
