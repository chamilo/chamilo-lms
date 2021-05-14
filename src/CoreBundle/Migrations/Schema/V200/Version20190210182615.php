<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20190210182615 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Session changes';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('session');
        if (false === $table->hasColumn('position')) {
            $this->addSql('ALTER TABLE session ADD COLUMN position INT DEFAULT 0 NOT NULL');
        } else {
            $this->addSql('ALTER TABLE session CHANGE position position INT DEFAULT 0 NOT NULL');
        }

        $this->addSql('UPDATE session SET promotion_id = NULL WHERE promotion_id = 0');
        if (false === $table->hasForeignKey('FK_D044D5D4139DF194')) {
            $this->addSql('ALTER TABLE session ADD CONSTRAINT FK_D044D5D4139DF194 FOREIGN KEY (promotion_id) REFERENCES promotion (id) ON DELETE CASCADE');
            $this->addSql('CREATE INDEX IDX_D044D5D4139DF194 ON session (promotion_id);');
        }

        if (false === $table->hasColumn('status')) {
            $this->addSql('ALTER TABLE session ADD COLUMN status INT NOT NULL');
        }

        if (false === $table->hasForeignKey('FK_D044D5D4EF87E278')) {
            $this->addSql('ALTER TABLE session ADD CONSTRAINT FK_D044D5D4EF87E278 FOREIGN KEY(session_admin_id) REFERENCES user(id);');
        }

        $this->addSql('UPDATE session_category SET date_start = NULL WHERE date_start = "0000-00-00"');
        $this->addSql('UPDATE session_category SET date_end = NULL WHERE date_end = "0000-00-00"');

        $table = $schema->getTable('session_rel_course_rel_user');
        if ($table->hasForeignKey('FK_720167E91D79BD3')) {
            $this->addSql('ALTER TABLE session_rel_course_rel_user DROP FOREIGN KEY FK_720167E91D79BD3');
            $this->addSql('ALTER TABLE session_rel_course_rel_user ADD CONSTRAINT FK_720167E91D79BD3 FOREIGN KEY (c_id) REFERENCES course (id) ON DELETE CASCADE');
        } else {
            $this->addSql('ALTER TABLE session_rel_course_rel_user ADD CONSTRAINT FK_720167E91D79BD3 FOREIGN KEY (c_id) REFERENCES course (id) ON DELETE CASCADE');
        }
    }

    public function down(Schema $schema): void
    {
    }
}
