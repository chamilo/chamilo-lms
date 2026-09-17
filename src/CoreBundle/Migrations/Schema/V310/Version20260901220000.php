<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V300;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20260901220000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add legacy Gradebook certificate history archive';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('gradebook_certificate_legacy_reference')) {
            return;
        }

        $this->addSql(
            <<<'SQL'
CREATE TABLE gradebook_certificate_legacy_reference (
    certificate_id BIGINT NOT NULL,
    legacy_user_id INT NOT NULL,
    cat_id INT DEFAULT NULL,
    score_certificate DOUBLE PRECISION NOT NULL,
    created_at DATETIME NOT NULL,
    path_certificate LONGTEXT DEFAULT NULL,
    html_content LONGTEXT DEFAULT NULL,
    content_sha256 CHAR(64) DEFAULT NULL,
    imported_at DATETIME NOT NULL,
    INDEX idx_gclr_legacy_user_id (legacy_user_id),
    INDEX idx_gclr_cat_id (cat_id),
    PRIMARY KEY(certificate_id)
) DEFAULT CHARACTER SET utf8mb4
  COLLATE `utf8mb4_unicode_ci`
  ENGINE = InnoDB
  ROW_FORMAT = DYNAMIC
SQL
        );
    }

    public function down(Schema $schema): void
    {
        // Historical certificate references are intentionally preserved.
    }
}
