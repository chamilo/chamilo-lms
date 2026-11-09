<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20201212195010 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add course.language_id before the migrations that load courses through the ORM';
    }

    public function up(Schema $schema): void
    {
        // The Course entity maps language_id, which V310\Version20261005120000 creates: without it, the
        // migrations below cannot load a course. It stays nullable and course_language stays the source
        // of truth until that migration refills language_id, enforces it and drops course_language.
        $courseTable = $schema->getTable('course');
        if ($courseTable->hasColumn('language_id') || !$courseTable->hasColumn('course_language')) {
            return;
        }

        $this->addSql('ALTER TABLE course ADD language_id INT DEFAULT NULL');
        $this->addSql(
            'UPDATE course c
             INNER JOIN language l ON l.isocode = c.course_language
             SET c.language_id = l.id'
        );
    }
}
