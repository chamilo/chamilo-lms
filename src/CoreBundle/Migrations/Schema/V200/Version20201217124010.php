<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20201217124010 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Create and modify tables for peer assessment, autogroups, learning paths, group relations, and student publications.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE c_student_publication
            ADD IF NOT EXISTS student_delete_own_publication TINYINT(1) DEFAULT 0,
            ADD IF NOT EXISTS default_visibility TINYINT(1) DEFAULT 0,
            ADD IF NOT EXISTS extensions LONGTEXT DEFAULT NULL,
            ADD COLUMN group_category_id INT DEFAULT 0 NULL AFTER post_group_id
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE c_student_publication
            DROP IF EXISTS student_delete_own_publication,
            DROP IF EXISTS default_visibility,
            DROP IF EXISTS group_category_id,
            DROP IF EXISTS extensions
        ');
    }
}
