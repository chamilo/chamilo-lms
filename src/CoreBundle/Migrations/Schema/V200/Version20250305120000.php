<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20250305120000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Creates ai_requests table to log AI-generated requests and responses, including the AI provider used';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE ai_requests (
                id INT AUTO_INCREMENT NOT NULL,
                user_id INT NOT NULL,
                tool_name VARCHAR(255) NOT NULL,
                requested_at DATETIME NOT NULL,
                request_text TEXT NOT NULL,
                prompt_tokens INT DEFAULT NULL,
                completion_tokens INT DEFAULT NULL,
                total_tokens INT DEFAULT NULL,
                ai_provider VARCHAR(50) NOT NULL,
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE ai_requests;');
    }
}
