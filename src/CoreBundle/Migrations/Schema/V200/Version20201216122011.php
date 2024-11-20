<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20201216122011 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Create and modify tables for peer assessment, autogroups, learning paths, group relations, and student publications.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE c_lp
            ADD IF NOT EXISTS subscribe_user_by_date TINYINT(1) DEFAULT 0 NOT NULL,
            ADD IF NOT EXISTS display_not_allowed_lp TINYINT(1) DEFAULT 0
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE c_lp
            DROP IF EXISTS subscribe_user_by_date,
            DROP IF EXISTS display_not_allowed_lp
        ');
    }
}
