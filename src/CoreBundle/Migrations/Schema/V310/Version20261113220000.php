<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20261113220000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add c_student_publication_comment.teacher_only for private teacher notes on assignment submissions.';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('c_student_publication_comment')) {
            return;
        }

        if ($schema->getTable('c_student_publication_comment')->hasColumn('teacher_only')) {
            return;
        }

        $this->addSql('ALTER TABLE c_student_publication_comment ADD teacher_only TINYINT(1) DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('c_student_publication_comment')
            && $schema->getTable('c_student_publication_comment')->hasColumn('teacher_only')
        ) {
            $this->addSql('ALTER TABLE c_student_publication_comment DROP teacher_only');
        }
    }
}
