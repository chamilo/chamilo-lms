<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20240515094800 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate LRS xapi_ tables';
    }

    public function up(Schema $schema): void
    {
        $hasTblStatement = $schema->hasTable('xapi_statement');
        $hasTblResult = $schema->hasTable('xapi_result');
        $hasTblActor = $schema->hasTable('xapi_actor');
        $hasTblAttachment = $schema->hasTable('xapi_attachment');
        $hasTblVerb = $schema->hasTable('xapi_verb');
        $hasTblObject = $schema->hasTable('xapi_object');
        $hasTblExtensions = $schema->hasTable('xapi_extensions');
        $hasTblContext = $schema->hasTable('xapi_context');

        if ($hasTblStatement) {
            $this->addSql("ALTER TABLE xapi_statement CHANGE created created INT DEFAULT NULL, CHANGE `stored` `stored` INT DEFAULT NULL, CHANGE hasAttachments has_attachments TINYINT(1) NOT NULL");
        } else {
            $this->addSql("CREATE TABLE xapi_statement (id VARCHAR(255) NOT NULL, actor_id INT DEFAULT NULL, verb_id INT DEFAULT NULL, object_id INT DEFAULT NULL, result_id INT DEFAULT NULL, authority_id INT DEFAULT NULL, context_id INT DEFAULT NULL, created INT DEFAULT NULL, `stored` INT DEFAULT NULL, has_attachments TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_BAF6663B10DAF24A (actor_id), UNIQUE INDEX UNIQ_BAF6663BC1D03483 (verb_id), UNIQUE INDEX UNIQ_BAF6663B232D562B (object_id), UNIQUE INDEX UNIQ_BAF6663B7A7B643 (result_id), UNIQUE INDEX UNIQ_BAF6663B81EC865B (authority_id), UNIQUE INDEX UNIQ_BAF6663B6B00C1CF (context_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC");
        }

        if ($hasTblResult) {
            $this->addSql("ALTER TABLE xapi_result CHANGE hasScore has_score TINYINT(1) NOT NULL");
        } else {
            $this->addSql("CREATE TABLE xapi_result (identifier INT AUTO_INCREMENT NOT NULL, extensions_id INT DEFAULT NULL, has_score TINYINT(1) NOT NULL, scaled DOUBLE PRECISION DEFAULT NULL, raw DOUBLE PRECISION DEFAULT NULL, min DOUBLE PRECISION DEFAULT NULL, max DOUBLE PRECISION DEFAULT NULL, success TINYINT(1) DEFAULT NULL, completion TINYINT(1) DEFAULT NULL, response VARCHAR(255) DEFAULT NULL, duration VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_5971ECBFD0A19400 (extensions_id), PRIMARY KEY(identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC");
        }

        if ($hasTblActor) {
            $this->addSql("ALTER TABLE xapi_actor CHANGE mboxSha1Sum mbox_sha1_sum VARCHAR(255) DEFAULT NULL, CHANGE openId open_id VARCHAR(255) DEFAULT NULL, CHANGE accountName account_name VARCHAR(255) DEFAULT NULL, CHANGE accountHomePage account_home_page VARCHAR(255) DEFAULT NULL");
        } else {
            $this->addSql("CREATE TABLE xapi_actor (identifier INT AUTO_INCREMENT NOT NULL, type VARCHAR(255) DEFAULT NULL, mbox VARCHAR(255) DEFAULT NULL, mbox_sha1_sum VARCHAR(255) DEFAULT NULL, open_id VARCHAR(255) DEFAULT NULL, account_name VARCHAR(255) DEFAULT NULL, account_home_page VARCHAR(255) DEFAULT NULL, name VARCHAR(255) DEFAULT NULL, PRIMARY KEY(identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC");
        }

        if ($hasTblAttachment) {
            $this->addSql("ALTER TABLE xapi_attachment CHANGE usageType usage_type VARCHAR(255) NOT NULL, CHANGE contentType content_type INT NOT NULL, CHANGE display display LONGTEXT NOT NULL COMMENT '(DC2Type:json)', CHANGE hasDescription has_description TINYINT(1) NOT NULL, CHANGE description description LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json)', CHANGE fileUrl file_url VARCHAR(255) DEFAULT NULL");
        } else {
            $this->addSql("CREATE TABLE xapi_attachment (identifier INT AUTO_INCREMENT NOT NULL, statement_id VARCHAR(255) DEFAULT NULL, usage_type VARCHAR(255) NOT NULL, content_type INT NOT NULL, length INT NOT NULL, sha2 VARCHAR(255) NOT NULL, display LONGTEXT NOT NULL COMMENT '(DC2Type:json)', has_description TINYINT(1) NOT NULL, description LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json)', file_url VARCHAR(255) DEFAULT NULL, content LONGTEXT DEFAULT NULL, INDEX IDX_7148C9A1849CB65B (statement_id), PRIMARY KEY(identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC");
        }

        if ($hasTblVerb) {
            $this->addSql("ALTER TABLE xapi_verb CHANGE display display LONGTEXT NOT NULL COMMENT '(DC2Type:json)'");
        } else {
            $this->addSql("CREATE TABLE xapi_verb (identifier INT AUTO_INCREMENT NOT NULL, id VARCHAR(255) NOT NULL, display LONGTEXT NOT NULL COMMENT '(DC2Type:json)', PRIMARY KEY(identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC");
        }

        if ($hasTblObject) {
            $tblObject = $schema->getTable('xapi_object');

            if ($tblObject->hasForeignKey('FK_E2B68640988A4CEC')) {
                $this->addSql("ALTER TABLE xapi_object DROP FOREIGN KEY FK_E2B68640988A4CEC");
            }

            if ($tblObject->hasForeignKey('FK_E2B68640303C7F1D')) {
                $this->addSql("ALTER TABLE xapi_object DROP FOREIGN KEY FK_E2B68640303C7F1D");
            }

            if ($tblObject->hasForeignKey('FK_E2B68640AEA1B132')) {
                $this->addSql("ALTER TABLE xapi_object DROP FOREIGN KEY FK_E2B68640AEA1B132");
            }

            if ($tblObject->hasForeignKey('FK_E2B686404F542860')) {
                $this->addSql("ALTER TABLE xapi_object DROP FOREIGN KEY FK_E2B686404F542860");
            }

            if ($tblObject->hasForeignKey('FK_E2B68640B73EEAB7')) {
                $this->addSql("ALTER TABLE xapi_object DROP FOREIGN KEY FK_E2B68640B73EEAB7");
            }

            if ($tblObject->hasIndex('IDX_E2B68640AEA1B132')) {
                $this->addSql("DROP INDEX IDX_E2B68640AEA1B132 ON xapi_object");
            }

            if ($tblObject->hasIndex('IDX_E2B68640B73EEAB7')) {
                $this->addSql("DROP INDEX IDX_E2B68640B73EEAB7 ON xapi_object");
            }

            if ($tblObject->hasIndex('IDX_E2B68640988A4CEC')) {
                $this->addSql("DROP INDEX IDX_E2B68640988A4CEC ON xapi_object");
            }

            if ($tblObject->hasIndex('IDX_E2B686404F542860')) {
                $this->addSql("DROP INDEX IDX_E2B686404F542860 ON xapi_object");
            }

            if ($tblObject->hasIndex('UNIQ_E2B68640303C7F1D')) {
                $this->addSql("DROP INDEX UNIQ_E2B68640303C7F1D ON xapi_object");
            }

            $this->addSql("ALTER TABLE xapi_object CHANGE activityExtensions_id activity_extensions_id INT DEFAULT NULL, CHANGE parentContext_id parent_context_id INT DEFAULT NULL, CHANGE groupingContext_id grouping_context_id INT DEFAULT NULL, CHANGE categoryContext_id category_context_id INT DEFAULT NULL, CHANGE otherContext_id other_context_id INT DEFAULT NULL, CHANGE activityId activity_id VARCHAR(255) DEFAULT NULL, CHANGE hasActivityDefinition has_activity_definition TINYINT(1) DEFAULT NULL, CHANGE hasActivityName has_activity_name TINYINT(1) DEFAULT NULL, CHANGE activityName activity_name LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json)', CHANGE hasActivityDescription has_activity_description TINYINT(1) DEFAULT NULL, CHANGE activityDescription activity_description LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json)', CHANGE activityType activity_type VARCHAR(255) DEFAULT NULL, CHANGE activityMoreInfo activity_more_info VARCHAR(255) DEFAULT NULL, CHANGE mboxSha1Sum mbox_sha1_sum VARCHAR(255) DEFAULT NULL, CHANGE openId open_id VARCHAR(255) DEFAULT NULL, CHANGE accountName account_name VARCHAR(255) DEFAULT NULL, CHANGE accountHomePage account_home_page VARCHAR(255) DEFAULT NULL, CHANGE referencedStatementId referenced_statement_id VARCHAR(255) DEFAULT NULL");
            $this->addSql("ALTER TABLE xapi_object ADD CONSTRAINT FK_E2B68640D1735DC4 FOREIGN KEY (activity_extensions_id) REFERENCES xapi_extensions (identifier)");
            $this->addSql("ALTER TABLE xapi_object ADD CONSTRAINT FK_E2B686402C43459F FOREIGN KEY (parent_context_id) REFERENCES xapi_context (identifier)");
            $this->addSql("ALTER TABLE xapi_object ADD CONSTRAINT FK_E2B68640C89A54F0 FOREIGN KEY (grouping_context_id) REFERENCES xapi_context (identifier)");
            $this->addSql("ALTER TABLE xapi_object ADD CONSTRAINT FK_E2B686404D1E91B1 FOREIGN KEY (category_context_id) REFERENCES xapi_context (identifier)");
            $this->addSql("ALTER TABLE xapi_object ADD CONSTRAINT FK_E2B68640D0D57945 FOREIGN KEY (other_context_id) REFERENCES xapi_context (identifier)");
            $this->addSql("CREATE UNIQUE INDEX UNIQ_E2B68640D1735DC4 ON xapi_object (activity_extensions_id)");
            $this->addSql("CREATE INDEX IDX_E2B686402C43459F ON xapi_object (parent_context_id)");
            $this->addSql("CREATE INDEX IDX_E2B68640C89A54F0 ON xapi_object (grouping_context_id)");
            $this->addSql("CREATE INDEX IDX_E2B686404D1E91B1 ON xapi_object (category_context_id)");
            $this->addSql("CREATE INDEX IDX_E2B68640D0D57945 ON xapi_object (other_context_id)");
        } else {
            $this->addSql("CREATE TABLE xapi_object (identifier INT AUTO_INCREMENT NOT NULL, actor_id INT DEFAULT NULL, verb_id INT DEFAULT NULL, object_id INT DEFAULT NULL, activity_extensions_id INT DEFAULT NULL, group_id INT DEFAULT NULL, parent_context_id INT DEFAULT NULL, grouping_context_id INT DEFAULT NULL, category_context_id INT DEFAULT NULL, other_context_id INT DEFAULT NULL, type VARCHAR(255) DEFAULT NULL, activity_id VARCHAR(255) DEFAULT NULL, has_activity_definition TINYINT(1) DEFAULT NULL, has_activity_name TINYINT(1) DEFAULT NULL, activity_name LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json)', has_activity_description TINYINT(1) DEFAULT NULL, activity_description LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json)', activity_type VARCHAR(255) DEFAULT NULL, activity_more_info VARCHAR(255) DEFAULT NULL, mbox VARCHAR(255) DEFAULT NULL, mbox_sha1_sum VARCHAR(255) DEFAULT NULL, open_id VARCHAR(255) DEFAULT NULL, account_name VARCHAR(255) DEFAULT NULL, account_home_page VARCHAR(255) DEFAULT NULL, name VARCHAR(255) DEFAULT NULL, referenced_statement_id VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_E2B6864010DAF24A (actor_id), UNIQUE INDEX UNIQ_E2B68640C1D03483 (verb_id), UNIQUE INDEX UNIQ_E2B68640232D562B (object_id), UNIQUE INDEX UNIQ_E2B68640D1735DC4 (activity_extensions_id), INDEX IDX_E2B68640FE54D947 (group_id), INDEX IDX_E2B686402C43459F (parent_context_id), INDEX IDX_E2B68640C89A54F0 (grouping_context_id), INDEX IDX_E2B686404D1E91B1 (category_context_id), INDEX IDX_E2B68640D0D57945 (other_context_id), PRIMARY KEY(identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC");
        }

        if ($hasTblExtensions) {
            $this->addSql("ALTER TABLE xapi_extensions CHANGE extensions extensions LONGTEXT NOT NULL COMMENT '(DC2Type:json)'");
        } else {
            $this->addSql("CREATE TABLE xapi_extensions (identifier INT AUTO_INCREMENT NOT NULL, extensions LONGTEXT NOT NULL COMMENT '(DC2Type:json)', PRIMARY KEY(identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC");
        }

        if ($hasTblContext) {
            $this->addSql("ALTER TABLE xapi_context CHANGE hasContextActivities has_context_activities TINYINT(1) DEFAULT NULL");
        } else {
            $this->addSql("CREATE TABLE xapi_context (identifier INT AUTO_INCREMENT NOT NULL, instructor_id INT DEFAULT NULL, team_id INT DEFAULT NULL, extensions_id INT DEFAULT NULL, registration VARCHAR(255) DEFAULT NULL, has_context_activities TINYINT(1) DEFAULT NULL, revision VARCHAR(255) DEFAULT NULL, platform VARCHAR(255) DEFAULT NULL, language VARCHAR(255) DEFAULT NULL, statement VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_3D7771908C4FC193 (instructor_id), UNIQUE INDEX UNIQ_3D777190296CD8AE (team_id), UNIQUE INDEX UNIQ_3D777190D0A19400 (extensions_id), PRIMARY KEY(identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC");
        }

        if (!$hasTblStatement) {
            $this->addSql("ALTER TABLE xapi_statement ADD CONSTRAINT FK_BAF6663B10DAF24A FOREIGN KEY (actor_id) REFERENCES xapi_object (identifier)");
            $this->addSql("ALTER TABLE xapi_statement ADD CONSTRAINT FK_BAF6663BC1D03483 FOREIGN KEY (verb_id) REFERENCES xapi_verb (identifier)");
            $this->addSql("ALTER TABLE xapi_statement ADD CONSTRAINT FK_BAF6663B232D562B FOREIGN KEY (object_id) REFERENCES xapi_object (identifier)");
            $this->addSql("ALTER TABLE xapi_statement ADD CONSTRAINT FK_BAF6663B7A7B643 FOREIGN KEY (result_id) REFERENCES xapi_result (identifier)");
            $this->addSql("ALTER TABLE xapi_statement ADD CONSTRAINT FK_BAF6663B81EC865B FOREIGN KEY (authority_id) REFERENCES xapi_object (identifier)");
            $this->addSql("ALTER TABLE xapi_statement ADD CONSTRAINT FK_BAF6663B6B00C1CF FOREIGN KEY (context_id) REFERENCES xapi_context (identifier)");
        }

        if (!$hasTblResult) {
            $this->addSql("ALTER TABLE xapi_result ADD CONSTRAINT FK_5971ECBFD0A19400 FOREIGN KEY (extensions_id) REFERENCES xapi_extensions (identifier)");
        }

        if (!$hasTblAttachment) {
            $this->addSql("ALTER TABLE xapi_attachment ADD CONSTRAINT FK_7148C9A1849CB65B FOREIGN KEY (statement_id) REFERENCES xapi_statement (id)");
        }

        if (!$hasTblObject) {
            $this->addSql("ALTER TABLE xapi_object ADD CONSTRAINT FK_E2B6864010DAF24A FOREIGN KEY (actor_id) REFERENCES xapi_object (identifier)");
            $this->addSql("ALTER TABLE xapi_object ADD CONSTRAINT FK_E2B68640C1D03483 FOREIGN KEY (verb_id) REFERENCES xapi_verb (identifier)");
            $this->addSql("ALTER TABLE xapi_object ADD CONSTRAINT FK_E2B68640232D562B FOREIGN KEY (object_id) REFERENCES xapi_object (identifier)");
            $this->addSql("ALTER TABLE xapi_object ADD CONSTRAINT FK_E2B68640D1735DC4 FOREIGN KEY (activity_extensions_id) REFERENCES xapi_extensions (identifier)");
            $this->addSql("ALTER TABLE xapi_object ADD CONSTRAINT FK_E2B68640FE54D947 FOREIGN KEY (group_id) REFERENCES xapi_object (identifier)");
            $this->addSql("ALTER TABLE xapi_object ADD CONSTRAINT FK_E2B686402C43459F FOREIGN KEY (parent_context_id) REFERENCES xapi_context (identifier)");
            $this->addSql("ALTER TABLE xapi_object ADD CONSTRAINT FK_E2B68640C89A54F0 FOREIGN KEY (grouping_context_id) REFERENCES xapi_context (identifier)");
            $this->addSql("ALTER TABLE xapi_object ADD CONSTRAINT FK_E2B686404D1E91B1 FOREIGN KEY (category_context_id) REFERENCES xapi_context (identifier)");
            $this->addSql("ALTER TABLE xapi_object ADD CONSTRAINT FK_E2B68640D0D57945 FOREIGN KEY (other_context_id) REFERENCES xapi_context (identifier)");
        }

        if (!$hasTblContext) {
            $this->addSql("ALTER TABLE xapi_context ADD CONSTRAINT FK_3D7771908C4FC193 FOREIGN KEY (instructor_id) REFERENCES xapi_object (identifier)");
            $this->addSql("ALTER TABLE xapi_context ADD CONSTRAINT FK_3D777190296CD8AE FOREIGN KEY (team_id) REFERENCES xapi_object (identifier)");
            $this->addSql("ALTER TABLE xapi_context ADD CONSTRAINT FK_3D777190D0A19400 FOREIGN KEY (extensions_id) REFERENCES xapi_extensions (identifier)");
        }
    }
}
