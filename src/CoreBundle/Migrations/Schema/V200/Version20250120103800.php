<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20250120103800 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add min_score column to gradebook_evaluation and gradebook_link tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE gradebook_evaluation
            ADD COLUMN min_score FLOAT DEFAULT NULL
        ');

        $this->addSql('
            ALTER TABLE gradebook_link
            ADD COLUMN min_score FLOAT DEFAULT NULL
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE gradebook_evaluation DROP COLUMN min_score');
        $this->addSql('ALTER TABLE gradebook_link DROP COLUMN min_score');
    }
}
