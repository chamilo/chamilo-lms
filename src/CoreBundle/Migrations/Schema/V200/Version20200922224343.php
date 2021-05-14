<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20200922224343 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'sys_announcement changes';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('sys_announcement');
        if ($table->hasColumn('visible_drh')) {
            $this->addSql('ALTER TABLE sys_announcement CHANGE visible_drh visible_drh TINYINT(1) NOT NULL');
        } else {
            $this->addSql('ALTER TABLE sys_announcement ADD COLUMN visible_drh TINYINT(1) NOT NULL');
        }

        if (false === $table->hasColumn('career_id')) {
            $this->addSql('ALTER TABLE sys_announcement ADD career_id INT DEFAULT NULL');
        }

        if (false === $table->hasColumn('promotion_id')) {
            $this->addSql('ALTER TABLE sys_announcement ADD promotion_id INT DEFAULT NULL');
        }

        if ($table->hasColumn('visible_session_admin')) {
            $this->addSql(
                'ALTER TABLE sys_announcement CHANGE visible_session_admin visible_session_admin TINYINT(1) NOT NULL'
            );
        } else {
            $this->addSql(
                'ALTER TABLE sys_announcement ADD COLUMN visible_session_admin TINYINT(1) NOT NULL'
            );
        }
        if ($table->hasColumn('visible_boss')) {
            $this->addSql('ALTER TABLE sys_announcement CHANGE visible_boss visible_boss TINYINT(1) NOT NULL');
        } else {
            $this->addSql('ALTER TABLE sys_announcement ADD COLUMN visible_boss TINYINT(1) NOT NULL');
        }

        if ($table->hasColumn('career_id')) {
            $this->addSql('ALTER TABLE sys_announcement ADD career_id INT DEFAULT NULL');
        }

        if ($table->hasColumn('promotion_id')) {
            $this->addSql('ALTER TABLE sys_announcement ADD promotion_id INT DEFAULT NULL;');
        }
    }

    public function down(Schema $schema): void
    {
    }
}
