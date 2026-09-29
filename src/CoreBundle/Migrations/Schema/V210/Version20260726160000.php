<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V210;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20260726160000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add portal-scoped mobile push installations for native applications.';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('mobile_push_installation')) {
            return;
        }

        $this->addSql(<<<'SQL'
CREATE TABLE mobile_push_installation (
    id INT AUTO_INCREMENT NOT NULL,
    user_id INT NOT NULL,
    access_url_id INT NOT NULL,
    installation_id VARCHAR(36) NOT NULL,
    token LONGTEXT NOT NULL,
    token_hash VARCHAR(64) NOT NULL,
    platform VARCHAR(20) NOT NULL,
    created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)',
    updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)',
    last_seen_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)',
    INDEX idx_mobile_push_installation_user_url (user_id, access_url_id),
    UNIQUE INDEX uniq_mobile_push_installation_url_id (access_url_id, installation_id),
    UNIQUE INDEX uniq_mobile_push_installation_url_token (access_url_id, token_hash),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC
SQL);

        $this->addSql('ALTER TABLE mobile_push_installation ADD CONSTRAINT FK_MOBILE_PUSH_INSTALLATION_USER FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE mobile_push_installation ADD CONSTRAINT FK_MOBILE_PUSH_INSTALLATION_ACCESS_URL FOREIGN KEY (access_url_id) REFERENCES access_url (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable('mobile_push_installation')) {
            return;
        }

        $this->addSql('DROP TABLE mobile_push_installation');
    }
}
