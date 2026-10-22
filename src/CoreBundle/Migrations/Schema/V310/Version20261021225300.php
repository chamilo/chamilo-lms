<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20261021225300 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add opt-in confidential welfare reporting, encrypted evidence metadata and investigator permissions';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('welfare_report')) {
            $this->addSql(<<<'SQL'
CREATE TABLE welfare_report (
    id INT AUTO_INCREMENT NOT NULL,
    reference_token VARCHAR(32) NOT NULL,
    access_url_id INT NOT NULL,
    target_user_id INT NOT NULL,
    reporter_digest VARCHAR(64) NOT NULL,
    reporter_cipher LONGTEXT NOT NULL,
    subject_cipher LONGTEXT NOT NULL,
    body_cipher LONGTEXT NOT NULL,
    share_contact TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    UNIQUE INDEX uniq_welfare_report_reference (reference_token),
    INDEX idx_welfare_report_target (access_url_id, target_user_id),
    INDEX idx_welfare_report_owner (access_url_id, reporter_digest),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC
SQL);
        }

        if (!$schema->hasTable('welfare_message')) {
            $this->addSql(<<<'SQL'
CREATE TABLE welfare_message (
    id INT AUTO_INCREMENT NOT NULL,
    report_id INT NOT NULL,
    reference_token VARCHAR(32) NOT NULL,
    author_kind VARCHAR(16) NOT NULL,
    author_cipher LONGTEXT NOT NULL,
    body_cipher LONGTEXT NOT NULL,
    created_at DATETIME NOT NULL,
    UNIQUE INDEX uniq_welfare_message_reference (reference_token),
    INDEX idx_welfare_message_report (report_id),
    PRIMARY KEY(id),
    CONSTRAINT fk_welfare_message_report FOREIGN KEY (report_id) REFERENCES welfare_report (id) ON DELETE CASCADE
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC
SQL);
        }

        if (!$schema->hasTable('welfare_evidence')) {
            $this->addSql(<<<'SQL'
CREATE TABLE welfare_evidence (
    id INT AUTO_INCREMENT NOT NULL,
    report_id INT NOT NULL,
    storage_key VARCHAR(32) NOT NULL,
    filename_cipher LONGTEXT NOT NULL,
    mime_type VARCHAR(80) NOT NULL,
    file_size INT NOT NULL,
    created_at DATETIME NOT NULL,
    UNIQUE INDEX uniq_welfare_evidence_key (storage_key),
    INDEX idx_welfare_evidence_report (report_id),
    PRIMARY KEY(id),
    CONSTRAINT fk_welfare_evidence_report FOREIGN KEY (report_id) REFERENCES welfare_report (id) ON DELETE CASCADE
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC
SQL);
        }

        if ($schema->hasTable('permission') && $schema->hasTable('permission_rel_role') && $schema->hasTable('role')) {
            $this->addSql(<<<'SQL'
INSERT INTO permission (title, slug, description)
SELECT 'Investigate confidential welfare reports', 'welfare:investigate',
       'Review eligible confidential welfare cases and communicate anonymously with reporters'
WHERE NOT EXISTS (SELECT 1 FROM permission WHERE slug = 'welfare:investigate')
SQL);
            $this->addSql(<<<'SQL'
INSERT INTO permission_rel_role (permission_id, role_id, changeable, updated_at)
SELECT p.id, r.id, 1, UTC_TIMESTAMP()
FROM permission p CROSS JOIN role r
WHERE p.slug = 'welfare:investigate'
  AND r.code IN ('ADMIN', 'GLOBAL_ADMIN', 'HR')
  AND NOT EXISTS (
      SELECT 1 FROM permission_rel_role prr
      WHERE prr.permission_id = p.id AND prr.role_id = r.id
  )
SQL);
        }
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Confidential welfare reports and evidence must not be destroyed by rollback.');
    }
}
