<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20250630093000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Create push_subscription table for browser push notifications.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            "CREATE TABLE push_subscription (
                id INT AUTO_INCREMENT NOT NULL,
                user_id INT DEFAULT NULL,
                endpoint LONGTEXT NOT NULL,
                public_key LONGTEXT NOT NULL,
                auth_token LONGTEXT NOT NULL,
                content_encoding VARCHAR(20) DEFAULT 'aesgcm',
                user_agent LONGTEXT DEFAULT NULL,
                created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)',
                updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)',
                INDEX idx_push_subscription_user (user_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC;"
        );

        $this->addSql(
            "ALTER TABLE push_subscription
                ADD CONSTRAINT FK_562830F3A76ED395
                FOREIGN KEY (user_id)
                REFERENCES user (id)
                ON DELETE CASCADE;"
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP TABLE push_subscription;");
    }
}
