<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20261122100000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add independent antivirus scan audit history (GH-3818).';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('antivirus_scan')) {
            $this->addSql('CREATE TABLE antivirus_scan (id INT AUTO_INCREMENT NOT NULL, access_url_id INT NOT NULL, user_id INT DEFAULT NULL, course_id INT DEFAULT NULL, session_id INT DEFAULT NULL, filename VARCHAR(255) NOT NULL, file_size BIGINT NOT NULL, sha256 VARCHAR(64) DEFAULT NULL, scanner VARCHAR(32) NOT NULL, status VARCHAR(16) NOT NULL, threat_name VARCHAR(255) DEFAULT NULL, source VARCHAR(255) NOT NULL, scanned_at DATETIME NOT NULL, duration_ms INT NOT NULL, INDEX idx_av_portal_id (access_url_id, id), INDEX idx_av_portal_status_id (access_url_id, status, id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        }
    }

    public function down(Schema $schema): void
    {
        // Preserve audit evidence. Disable the setting before rolling back code.
    }
}
