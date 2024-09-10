<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20240811221980 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migration to apply schema changes for notification_event_rel_user, justification_document_rel_users, and lti_external_tool tables.';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('notification_event_rel_user')) {
            $this->addSql('ALTER TABLE notification_event_rel_user DROP FOREIGN KEY IF EXISTS FK_9F7995A671F7E88B;');
            $this->addSql('ALTER TABLE notification_event_rel_user DROP FOREIGN KEY IF EXISTS FK_9F7995A6A76ED395;');
        }

        $this->addSql('DROP TABLE IF EXISTS notification_event_rel_user;');

        $this->addSql('ALTER TABLE notification_event MODIFY id INT(11) NOT NULL AUTO_INCREMENT;');

        $this->addSql('
            CREATE TABLE notification_event_rel_user (
                id INT AUTO_INCREMENT NOT NULL,
                event_id INT NOT NULL,
                user_id INT NOT NULL,
                INDEX IDX_9F7995A671F7E88B (event_id),
                INDEX IDX_9F7995A6A76ED395 (user_id),
                PRIMARY KEY(id),
                CONSTRAINT FK_9F7995A671F7E88B FOREIGN KEY (event_id) REFERENCES notification_event (id) ON DELETE CASCADE,
                CONSTRAINT FK_9F7995A6A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC;
        ');

        $this->addSql('ALTER TABLE notification_event_rel_user DROP FOREIGN KEY IF EXISTS FK_9F7995A671F7E88B;');
        $this->addSql('ALTER TABLE notification_event_rel_user DROP FOREIGN KEY IF EXISTS FK_9F7995A6A76ED395;');

        $this->addSql('ALTER TABLE notification_event_rel_user ADD CONSTRAINT FK_9F7995A671F7E88B FOREIGN KEY (event_id) REFERENCES notification_event (id);');
        $this->addSql('ALTER TABLE notification_event_rel_user ADD CONSTRAINT FK_9F7995A6A76ED395 FOREIGN KEY (user_id) REFERENCES user (id);');

        if ($schema->hasTable('justification_document_rel_users')) {
            $this->addSql('ALTER TABLE justification_document_rel_users CHANGE justification_document_id justification_document_id INT DEFAULT NULL;');
        }

        $this->addSql('ALTER TABLE lti_external_tool DROP FOREIGN KEY IF EXISTS FK_DB0E04E41BAD783F;');
        $this->addSql('ALTER TABLE lti_external_tool DROP INDEX IF EXISTS FK_DB0E04E41BAD783F;');
        $table = $schema->getTable('lti_external_tool');
        if (false === $table->hasIndex('UNIQ_DB0E04E41BAD783F')) {
            $this->addSql('ALTER TABLE lti_external_tool ADD UNIQUE INDEX UNIQ_DB0E04E41BAD783F (resource_node_id);');
        }
        $this->addSql('ALTER TABLE lti_external_tool DROP FOREIGN KEY IF EXISTS FK_DB0E04E482F80D8B;');
        $this->addSql('ALTER TABLE lti_external_tool DROP FOREIGN KEY IF EXISTS FK_DB0E04E491D79BD3;');
        $this->addSql('ALTER TABLE lti_external_tool DROP FOREIGN KEY IF EXISTS FK_DB0E04E4727ACA70;');
        $this->addSql('DROP INDEX IF EXISTS fk_db0e04e491d79bd3 ON lti_external_tool;');
        if (false === $table->hasIndex('IDX_DB0E04E491D79BD3')) {
            $this->addSql('CREATE INDEX IDX_DB0E04E491D79BD3 ON lti_external_tool (c_id);');
        }
        $this->addSql('DROP INDEX IF EXISTS fk_db0e04e482f80d8b ON lti_external_tool;');
        if (false === $table->hasIndex('IDX_DB0E04E482F80D8B')) {
            $this->addSql('CREATE INDEX IDX_DB0E04E482F80D8B ON lti_external_tool (gradebook_eval_id);');
        }
        $this->addSql('DROP INDEX IF EXISTS fk_db0e04e4727aca70 ON lti_external_tool;');
        if (false === $table->hasIndex('IDX_DB0E04E4727ACA70')) {
            $this->addSql('CREATE INDEX IDX_DB0E04E4727ACA70 ON lti_external_tool (parent_id);');
        }
        if (!$table->hasForeignKey('FK_DB0E04E482F80D8B')) {
            $this->addSql('ALTER TABLE lti_external_tool ADD CONSTRAINT FK_DB0E04E482F80D8B FOREIGN KEY (gradebook_eval_id) REFERENCES gradebook_evaluation (id) ON DELETE SET NULL;');
        }
        if (!$table->hasForeignKey('FK_DB0E04E491D79BD3')) {
            $this->addSql('ALTER TABLE lti_external_tool ADD CONSTRAINT FK_DB0E04E491D79BD3 FOREIGN KEY (c_id) REFERENCES course (id);');
        }
        if (!$table->hasForeignKey('FK_DB0E04E4727ACA70')) {
            $this->addSql('ALTER TABLE lti_external_tool ADD CONSTRAINT FK_DB0E04E4727ACA70 FOREIGN KEY (parent_id) REFERENCES lti_external_tool (id);');
        }
    }

    public function down(Schema $schema): void {}
}
