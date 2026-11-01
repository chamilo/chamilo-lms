<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use RuntimeException;

final class Version20261101010000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Record append-only teacher reviews for completed Hot Potatoes attempts.';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('hotpotatoes_attempt')) {
            throw new RuntimeException('Apply the GH3018 attempt-tracking migration first.');
        }

        if ($schema->hasTable('hotpotatoes_attempt_review')) {
            return;
        }

        $this->addSql('CREATE TABLE hotpotatoes_attempt_review (
            id INT AUTO_INCREMENT NOT NULL,
            attempt_id INT NOT NULL,
            score_percent SMALLINT NOT NULL,
            reviewed_by_user_id INT NOT NULL,
            reviewed_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX idx_hp_review_attempt_id (attempt_id, id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // Reviews are audit records; code rollback intentionally retains them.
    }
}
