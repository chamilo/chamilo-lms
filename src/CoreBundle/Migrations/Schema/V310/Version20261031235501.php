<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20261031235501 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add ungraded Hot Potatoes activity attempt tracking per user and course context.';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('hotpotatoes_attempt')) {
            return;
        }

        $this->addSql('CREATE TABLE hotpotatoes_attempt (
            id INT AUTO_INCREMENT NOT NULL,
            document_id INT NOT NULL,
            entrypoint VARCHAR(512) NOT NULL,
            user_id INT NOT NULL,
            course_id INT NOT NULL,
            session_id INT NOT NULL,
            group_id INT NOT NULL,
            started_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            completed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX idx_hp_attempt_course_document (course_id, document_id),
            INDEX idx_hp_attempt_user_started (user_id, started_at),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // Intentional: attempt history must not be dropped during a code rollback.
    }
}
