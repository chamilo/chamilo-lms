<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20261029065000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Allow showing or hiding the maximum question score during each exercise.';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('c_quiz') && !$schema->getTable('c_quiz')->hasColumn('show_question_max_score')) {
            $this->addSql('ALTER TABLE c_quiz ADD show_question_max_score TINYINT(1) DEFAULT 1 NOT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('c_quiz') && $schema->getTable('c_quiz')->hasColumn('show_question_max_score')) {
            $this->addSql('ALTER TABLE c_quiz DROP show_question_max_score');
        }
    }
}
